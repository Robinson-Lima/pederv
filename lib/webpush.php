<?php
// ============================================================
// WEB PUSH (VAPID) — notificação real no celular do motoboy,
// mesmo com o app em segundo plano (WhatsApp, vídeo, etc).
//
// Implementação própria em PHP puro (RFC 8291 + RFC 8188 + RFC 8292),
// usando apenas a extensão OpenSSL — sem bibliotecas externas, porque
// este servidor não tem acesso à internet para baixar pacotes via composer.
//
// ⚠ Este código não foi testado contra um serviço de push real (o ambiente
// onde foi escrito não tem acesso à rede). Se a notificação não chegar,
// o aviso por WhatsApp continua funcionando normalmente como reforço —
// veja evolution_delivery_alert() em helpers.php.
//
// Requer PHP 8.1+ (função openssl_pkey_derive). Use webpush_supported()
// para checar antes de usar.
// ============================================================

function webpush_supported(){
  return function_exists('openssl_pkey_derive')
      && function_exists('openssl_encrypt')
      && extension_loaded('openssl')
      && defined('OPENSSL_KEYTYPE_EC');
}

function webpush_capability_message(){
  if(webpush_supported()) return '';
  if(!extension_loaded('openssl')) return 'A extensão OpenSSL do PHP não está ativa neste servidor.';
  if(!function_exists('openssl_pkey_derive')) return 'Este servidor está em uma versão do PHP anterior à 8.1. Peça para trocar a versão do PHP no cPanel (MultiPHP Manager) para 8.1 ou mais nova.';
  return 'Este servidor não tem suporte a notificação push no momento.';
}

// ---------- base64url ----------
function b64url_encode($data){ return rtrim(strtr(base64_encode($data),'+/','-_'),'='); }
function b64url_decode($data){
  $data=strtr($data,'-_','+/');
  $pad=strlen($data)%4;
  if($pad) $data.=str_repeat('=',4-$pad);
  return base64_decode($data);
}

// ---------- DER helpers (para montar/ler estruturas EC sem biblioteca) ----------
function der_len_encode($n){
  if($n<128) return chr($n);
  $bytes=''; $tmp=$n;
  while($tmp>0){ $bytes = chr($tmp & 0xFF) . $bytes; $tmp >>= 8; }
  return chr(0x80 | strlen($bytes)) . $bytes;
}
function ec_coord_pad($bin){ return str_pad((string)$bin, 32, "\x00", STR_PAD_LEFT); }

// Monta um PEM de chave pública EC (P-256) a partir do ponto bruto (65 bytes: 0x04||X||Y)
function ec_raw_point_to_pem($x32,$y32){
  $point = "\x04".$x32.$y32; // ponto não comprimido, 65 bytes
  $bitstring_content = "\x00".$point; // byte de "unused bits" + ponto
  $bitstring = "\x03".der_len_encode(strlen($bitstring_content)).$bitstring_content;
  $oid_ec = hex2bin('06072a8648ce3d0201');   // id-ecPublicKey
  $oid_curve = hex2bin('06082a8648ce3d030107'); // prime256v1 / secp256r1
  $alg_content = $oid_ec.$oid_curve;
  $alg_seq = "\x30".der_len_encode(strlen($alg_content)).$alg_content;
  $outer_content = $alg_seq.$bitstring;
  $der = "\x30".der_len_encode(strlen($outer_content)).$outer_content;
  return "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($der),64,"\n")."-----END PUBLIC KEY-----\n";
}

// Converte assinatura ECDSA em DER (SEQUENCE{INTEGER r,INTEGER s}) para o formato
// bruto r||s de tamanho fixo exigido pelo JWT ES256 (RFC 7515).
function der_ecdsa_signature_to_raw($der,$len=32){
  $off=0;
  if(strlen($der)<8 || ord($der[0])!==0x30) return false;
  $off=1;
  $seqLen=ord($der[$off]); $off++;
  if($seqLen & 0x80){ $n=$seqLen & 0x7F; $seqLen=0; for($i=0;$i<$n;$i++){ $seqLen=($seqLen<<8)|ord($der[$off]); $off++; } }
  if(ord($der[$off])!==0x02) return false; $off++;
  $rLen=ord($der[$off]); $off++;
  $r=substr($der,$off,$rLen); $off+=$rLen;
  if(ord($der[$off])!==0x02) return false; $off++;
  $sLen=ord($der[$off]); $off++;
  $s=substr($der,$off,$sLen); $off+=$sLen;
  $r=ltrim($r,"\x00"); $s=ltrim($s,"\x00");
  return str_pad($r,$len,"\x00",STR_PAD_LEFT).str_pad($s,$len,"\x00",STR_PAD_LEFT);
}

// ---------- HKDF (RFC 5869) ----------
function hkdf_extract($salt,$ikm){ return hash_hmac('sha256',$ikm,$salt,true); }
function hkdf_expand($prk,$info,$length){
  $t=''; $okm=''; $i=1;
  while(strlen($okm)<$length){ $t=hash_hmac('sha256',$t.$info.chr($i),$prk,true); $okm.=$t; $i++; }
  return substr($okm,0,$length);
}

// ---------- Chaves VAPID (geradas uma vez, guardadas em settings) ----------
function vapid_generate(){
  if(!webpush_supported()) return false;
  $res=openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
  if(!$res) return false;
  openssl_pkey_export($res,$privPem);
  $details=openssl_pkey_get_details($res);
  if(empty($details['ec']['x'])||empty($details['ec']['y'])) return false;
  $pubRaw="\x04".ec_coord_pad($details['ec']['x']).ec_coord_pad($details['ec']['y']);
  setting_set('vapid_private_pem', base64_encode($privPem));
  setting_set('vapid_public_raw', b64url_encode($pubRaw));
  return true;
}
function vapid_public_key(){ return setting_get('vapid_public_raw',''); }
function vapid_private_pem(){ $b=setting_get('vapid_private_pem',''); return $b?base64_decode($b):''; }
function vapid_configured(){ return vapid_public_key()!=='' && vapid_private_pem()!==''; }

function vapid_jwt($audience){
  $priv=vapid_private_pem();
  if(!$priv) return false;
  $header=['typ'=>'JWT','alg'=>'ES256'];
  $claims=['aud'=>$audience,'exp'=>time()+12*3600,'sub'=>'mailto:'.(setting_get('store_email','contato@rvautomacao.com.br'))];
  $h=b64url_encode(json_encode($header));
  $c=b64url_encode(json_encode($claims));
  $signingInput=$h.'.'.$c;
  $sigDer='';
  if(!openssl_sign($signingInput,$sigDer,$priv,OPENSSL_ALGO_SHA256)) return false;
  $sigRaw=der_ecdsa_signature_to_raw($sigDer,32);
  if($sigRaw===false) return false;
  return $signingInput.'.'.b64url_encode($sigRaw);
}

// ---------- Criptografia da mensagem (RFC 8291 / aes128gcm - RFC 8188) ----------
function webpush_encrypt($payload,$uaPublicRaw,$authSecret){
  $uaX=substr($uaPublicRaw,1,32); $uaY=substr($uaPublicRaw,33,32);
  $uaPem=ec_raw_point_to_pem($uaX,$uaY);

  $ephemeral=openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
  if(!$ephemeral) return false;
  $ephDetails=openssl_pkey_get_details($ephemeral);
  $asPubRaw="\x04".ec_coord_pad($ephDetails['ec']['x']).ec_coord_pad($ephDetails['ec']['y']);

  $shared=@openssl_pkey_derive($uaPem,$ephemeral);
  if($shared===false) return false;
  if(strlen($shared)>32) $shared=substr($shared,-32);
  $shared=str_pad($shared,32,"\x00",STR_PAD_LEFT);

  $prkKey=hkdf_extract($authSecret,$shared);
  $keyInfo="WebPush: info\x00".$uaPublicRaw.$asPubRaw;
  $ikm=hkdf_expand($prkKey,$keyInfo,32);

  $salt=openssl_random_pseudo_bytes(16);
  $prk=hkdf_extract($salt,$ikm);
  $cek=hkdf_expand($prk,"Content-Encoding: aes128gcm\x00",16);
  $nonce=hkdf_expand($prk,"Content-Encoding: nonce\x00",12);

  $padded=$payload."\x02"; // delimitador de fim de registro único (sem padding extra)
  $tag='';
  $ciphertext=openssl_encrypt($padded,'aes-128-gcm',$cek,OPENSSL_RAW_DATA,$nonce,$tag,'',16);
  if($ciphertext===false) return false;

  $rs=4096; // tamanho de registro declarado (folga generosa; só existe 1 registro)
  $header=$salt.pack('N',$rs).chr(strlen($asPubRaw)).$asPubRaw;
  return $header.$ciphertext.$tag;
}

// Envia a notificação para UMA inscrição. Retorna ['ok'=>bool,'expirado'=>bool,'erro'=>string]
function webpush_send_one($subscription,$payloadArray,$ttl=60){
  if(!webpush_supported()) return ['ok'=>false,'expirado'=>false,'erro'=>webpush_capability_message()];
  if(!vapid_configured()) return ['ok'=>false,'expirado'=>false,'erro'=>'Gere as chaves de notificação primeiro.'];
  $endpoint=$subscription['endpoint'];
  $p256dh=b64url_decode($subscription['p256dh']);
  $auth=b64url_decode($subscription['auth']);
  if(strlen($p256dh)!==65||strlen($auth)!==16) return ['ok'=>false,'expirado'=>true,'erro'=>'Inscrição inválida.'];

  $payload=json_encode($payloadArray,JSON_UNESCAPED_UNICODE);
  $body=webpush_encrypt($payload,$p256dh,$auth);
  if($body===false) return ['ok'=>false,'expirado'=>false,'erro'=>'Falha ao criptografar a notificação.'];

  $parts=parse_url($endpoint);
  if(!$parts||empty($parts['host'])) return ['ok'=>false,'expirado'=>true,'erro'=>'Endpoint inválido.'];
  $audience=$parts['scheme'].'://'.$parts['host'].(isset($parts['port'])?':'.$parts['port']:'');
  $jwt=vapid_jwt($audience);
  if($jwt===false) return ['ok'=>false,'expirado'=>false,'erro'=>'Não foi possível assinar a notificação (chaves VAPID).'];

  $headers=[
    'Content-Type: application/octet-stream',
    'Content-Encoding: aes128gcm',
    'TTL: '.(int)$ttl,
    'Authorization: vapid t='.$jwt.', k='.vapid_public_key(),
  ];
  if(!function_exists('curl_init')) return ['ok'=>false,'expirado'=>false,'erro'=>'cURL indisponível.'];
  $ch=curl_init($endpoint);
  curl_setopt_array($ch,[
    CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>$body, CURLOPT_HTTPHEADER=>$headers,
    CURLOPT_RETURNTRANSFER=>true, CURLOPT_CONNECTTIMEOUT=>5, CURLOPT_TIMEOUT=>10,
  ]);
  $resp=curl_exec($ch); $code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE); $err=curl_error($ch); curl_close($ch);
  $expirado=in_array($code,[404,410],true);
  return ['ok'=>$code>=200&&$code<300,'expirado'=>$expirado,'erro'=>$err?:($code>=200&&$code<300?'':'Serviço de push recusou (HTTP '.$code.').')];
}

// Envia para TODAS as inscrições ativas de um usuário (ex: um motoboy pode ter
// mais de um celular/navegador). Remove sozinho as inscrições expiradas.
function webpush_notify_user($userId,$payloadArray){
  if(!$userId) return 0;
  $q=db()->prepare("SELECT * FROM push_subscriptions WHERE user_id=?"); $q->execute([$userId]);
  $subs=$q->fetchAll(); $enviados=0;
  foreach($subs as $s){
    $r=webpush_send_one($s,$payloadArray);
    if(!empty($r['ok'])) $enviados++;
    elseif(!empty($r['expirado'])) db()->prepare("DELETE FROM push_subscriptions WHERE id=?")->execute([$s['id']]);
  }
  return $enviados;
}
