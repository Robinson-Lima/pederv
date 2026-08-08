<?php
// ============================================================
// NFC-e — integração direta com SEFAZ (0 custo por nota)
// Gera XML, assina com certificado A1 e transmite via SOAP.
// ============================================================

function fiscal_config(){
  return [
    'ambiente'   => setting_get('nf_ambiente','2'),
    'regime'     => setting_get('nf_regime','1'),
    'cnpj'       => preg_replace('/\D/','',setting_get('nf_cnpj','')),
    'ie'         => setting_get('nf_ie',''),
    'uf'         => setting_get('nf_uf','SP'),
    'cmun'       => setting_get('nf_cmun',''),
    'razao'      => setting_get('nf_razao', cfg('restaurante')),
    'csc'        => setting_get('nf_csc',''),
    'csc_id'     => setting_get('nf_csc_id','1'),
    'serie'      => setting_get('nf_serie','1'),
    'auto'       => setting_get('nf_auto','0'),
    'cert_senha' => setting_get('nf_cert_senha',''),
    'cert_file'  => __DIR__.'/../data/certificado.pfx',
  ];
}

function fiscal_proximo_numero(){
  $n = (int) setting_get('nf_numero','0') + 1;
  setting_set('nf_numero', (string)$n);
  return $n;
}

function fiscal_itens($orderId){
  $it = db()->prepare("SELECT oi.*, p.ncm,p.cfop,p.cst,p.origem,p.unidade
                       FROM order_items oi LEFT JOIN products p ON p.nome=oi.nome
                       WHERE oi.order_id=?");
  $it->execute([$orderId]);
  return $it->fetchAll();
}

// Gera certificado A1 de teste (auto-assinado) para simulação
function fiscal_gerar_cert_teste(){
  $dir = __DIR__.'/../data/';
  if(!is_dir($dir)) mkdir($dir, 0755, true);
  $cnpj = preg_replace('/\D/','',setting_get('nf_cnpj','00000000000000'));
  $razao = setting_get('nf_razao','EMPRESA TESTE');
  $dn = ['commonName'=>$razao.':'.$cnpj,'organizationName'=>$razao,'countryName'=>'BR','stateOrProvinceName'=>setting_get('nf_uf','SP')];
  $pk = openssl_pkey_new(['private_key_bits'=>2048,'private_key_type'=>OPENSSL_KEYTYPE_RSA]);
  $csr = openssl_csr_new($dn, $pk);
  $cert = openssl_csr_sign($csr, null, $pk, 365);
  openssl_pkcs12_export($cert, $pfxOut, $pk, 'teste123');
  file_put_contents($dir.'certificado.pfx', $pfxOut);
  setting_set('nf_cert_senha','teste123');
  return true;
}

function fiscal_emitir($orderId, $ator='sistema'){
  $d = db();
  $o = $d->prepare("SELECT * FROM orders WHERE id=?"); $o->execute([$orderId]); $o=$o->fetch();
  if(!$o) return ['ok'=>false,'motivo'=>'pedido não encontrado'];

  $ex=$d->prepare("SELECT id FROM notas WHERE order_id=? AND status='autorizada'"); $ex->execute([$orderId]);
  if($ex->fetch()) return ['ok'=>true,'status'=>'autorizada','motivo'=>'já emitida'];

  $cfg = fiscal_config();
  $simular = setting_get('nf_simular','0')==='1';
  $numero = fiscal_proximo_numero();
  $d->prepare("INSERT INTO notas(order_id,numero,serie,modelo,status,ambiente,valor,motivo)
               VALUES(?,?,?, '65','pendente',?,?, 'aguardando emissão')")
    ->execute([$orderId,$numero,$cfg['serie'],$cfg['ambiente'],$o['total']]);
  $notaId = $d->lastInsertId();
  $itens = fiscal_itens($orderId);

  if(!extension_loaded('openssl')) return _fiscal_pendente($notaId,'Extensão OpenSSL não disponível no servidor.');
  if(!is_file($cfg['cert_file'])) return _fiscal_pendente($notaId,'Certificado A1 não encontrado. Faça upload em Config. fiscal.');
  if($cfg['cert_senha']==='') return _fiscal_pendente($notaId,'Senha do certificado não informada.');
  if($cfg['cnpj']==='') return _fiscal_pendente($notaId,'CNPJ não configurado. Preencha em Config. fiscal.');

  require_once __DIR__.'/nfce.php';
  $nfce = new NFCe($cfg);
  if(!$nfce->loadCert()) return _fiscal_erro($notaId,'erro','Não foi possível ler o certificado A1. Verifique o arquivo e a senha.');

  $result = $nfce->montarXML($o, $itens, $numero);
  $xmlAssinado = $nfce->assinarXML($result['xml']);

  $xmlDir = __DIR__.'/../data/nfce/';
  if(!is_dir($xmlDir)) mkdir($xmlDir, 0755, true);
  $xmlPath = $xmlDir.$result['chave'].'-nfce.xml';
  file_put_contents($xmlPath, $xmlAssinado);
  db()->prepare("UPDATE notas SET xml_path=?, chave=? WHERE id=?")->execute([$xmlPath, $result['chave'], $notaId]);

  if($simular){
    $protocolo = 'SIM'.date('YmdHis').rand(100,999);
    $danfeHtml = $nfce->danfeHTML($o, $itens, $result['chave'], $protocolo);
    file_put_contents($xmlDir.$result['chave'].'-danfe.html', $danfeHtml);
    return _fiscal_ok($notaId, $result['chave'], $protocolo, 'data/nfce/'.$result['chave'].'-danfe.html');
  }

  if($cfg['csc']==='') return _fiscal_pendente($notaId,'CSC não configurado. Preencha em Config. fiscal.');

  $ret = $nfce->enviar($xmlAssinado);
  if($ret['ok']){
    if(!empty($ret['xmlRetorno'])) file_put_contents($xmlDir.$result['chave'].'-prot.xml', $ret['xmlRetorno']);
    $danfeHtml = $nfce->danfeHTML($o, $itens, $ret['chave'], $ret['protocolo']);
    file_put_contents($xmlDir.$result['chave'].'-danfe.html', $danfeHtml);
    return _fiscal_ok($notaId, $ret['chave'], $ret['protocolo'], 'data/nfce/'.$result['chave'].'-danfe.html');
  }

  if(!empty($ret['xmlRetorno'])) file_put_contents($xmlDir.$result['chave'].'-ret.xml', $ret['xmlRetorno']);
  return _fiscal_erro($notaId, 'rejeitada', $ret['motivo'] ?? 'Erro na comunicação com SEFAZ');
}

function _fiscal_pendente($notaId,$motivo){
  db()->prepare("UPDATE notas SET status='pendente', motivo=? WHERE id=?")->execute([$motivo,$notaId]);
  return ['ok'=>false,'status'=>'pendente','motivo'=>$motivo];
}
function _fiscal_ok($notaId,$chave,$prot,$danfe=''){
  db()->prepare("UPDATE notas SET status='autorizada', chave=?, protocolo=?, danfe_url=?, motivo='Autorizada' WHERE id=?")
    ->execute([$chave,$prot,$danfe,$notaId]);
  n8n_event('nota_autorizada',['nota_id'=>$notaId,'chave'=>$chave]);
  return ['ok'=>true,'status'=>'autorizada','chave'=>$chave];
}
function _fiscal_erro($notaId,$status,$motivo){
  db()->prepare("UPDATE notas SET status=?, motivo=? WHERE id=?")->execute([$status,$motivo,$notaId]);
  return ['ok'=>false,'status'=>$status,'motivo'=>$motivo];
}

function fiscal_cancelar($notaId,$justificativa){
  $n=db()->prepare("SELECT * FROM notas WHERE id=?"); $n->execute([$notaId]); $n=$n->fetch();
  if(!$n) return ['ok'=>false];
  $cfg = fiscal_config();
  if($n['chave'] && $n['protocolo']){
    require_once __DIR__.'/nfce.php';
    NFCeCancela::cancelar($n['chave'], $n['protocolo'], $justificativa, $cfg);
  }
  db()->prepare("UPDATE notas SET status='cancelada', motivo=? WHERE id=?")->execute([$justificativa,$notaId]);
  n8n_event('nota_cancelada',['nota_id'=>$notaId]);
  return ['ok'=>true,'status'=>'cancelada'];
}

function fiscal_auto_ao_pagar($orderId){
  if(setting_get('nf_auto','0')==='1') fiscal_emitir($orderId,'auto');
}
