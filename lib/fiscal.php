<?php
// ============================================================
// NFC-e — camada fiscal MULTI-EMISSOR
// O cliente escolhe o emissor no painel e cola a API dele.
// Todos os drivers entregam o mesmo resultado pro painel:
// status (autorizada / rejeitada / cancelada / pendente / erro).
//
// Nenhum driver dispara sem credencial: se faltar API/token,
// a nota fica 'pendente' e o sistema nunca quebra.
// ============================================================

// Emissores disponíveis + campos de credencial de cada um.
// (é isso que monta o seletor e os campos na aba ⚙ Config)
function fiscal_emissores(){
  return [
    'plugnotas' => [
      'label' => 'PlugNotas',
      'campos'=> ['plug_token'=>'API Token (X-API-KEY)'],
    ],
    'focus' => [
      'label' => 'Focus NFe',
      'campos'=> ['focus_token'=>'Token de acesso'],
    ],
    'enotas' => [
      'label' => 'eNotas',
      'campos'=> ['enotas_apikey'=>'API Key', 'enotas_empresa'=>'ID da empresa'],
    ],
    'nfcefacil' => [
      'label' => 'NFC-e Fácil',
      'campos'=> ['nfcef_token'=>'Token'],
    ],
    'sped' => [
      'label' => 'Direto na SEFAZ (sped-nfe · 0 por nota)',
      'campos'=> ['nf_cert_senha'=>'Senha do certificado A1 (.pfx em data/)'],
    ],
  ];
}

function fiscal_config(){
  return [
    'driver'     => setting_get('nf_driver','plugnotas'),
    'ambiente'   => setting_get('nf_ambiente','2'),   // 1=produção, 2=homologação
    'regime'     => setting_get('nf_regime','1'),     // 1=Simples Nacional
    'cnpj'       => preg_replace('/\D/','',setting_get('nf_cnpj','')),
    'ie'         => setting_get('nf_ie',''),
    'uf'         => setting_get('nf_uf','SP'),
    'cmun'       => setting_get('nf_cmun',''),
    'razao'      => setting_get('nf_razao', cfg('restaurante')),
    'csc'        => setting_get('nf_csc',''),
    'csc_id'     => setting_get('nf_csc_id','1'),
    'serie'      => setting_get('nf_serie','1'),
    'auto'       => setting_get('nf_auto','0'),
    // credenciais por emissor
    'plug_token'    => setting_get('plug_token',''),
    'focus_token'   => setting_get('focus_token',''),
    'enotas_apikey' => setting_get('enotas_apikey',''),
    'enotas_empresa'=> setting_get('enotas_empresa',''),
    'nfcef_token'   => setting_get('nfcef_token',''),
    'cert_senha'    => setting_get('nf_cert_senha',''),
    'cert_file'     => __DIR__.'/../data/certificado.pfx',
  ];
}

function fiscal_proximo_numero(){
  $n = (int) setting_get('nf_numero','0') + 1;
  setting_set('nf_numero', (string)$n);
  return $n;
}

// Monta os itens já com os campos fiscais do produto
function fiscal_itens($orderId){
  $it = db()->prepare("SELECT oi.*, p.ncm,p.cfop,p.cst,p.origem,p.unidade
                       FROM order_items oi LEFT JOIN products p ON p.nome=oi.nome
                       WHERE oi.order_id=?");
  $it->execute([$orderId]);
  return $it->fetchAll();
}

// ============================================================
// DISPATCHER — registra a nota e chama o driver escolhido
// ============================================================
function fiscal_emitir($orderId, $ator='sistema'){
  $d = db();
  $o = $d->prepare("SELECT * FROM orders WHERE id=?"); $o->execute([$orderId]); $o=$o->fetch();
  if(!$o) return ['ok'=>false,'motivo'=>'pedido não encontrado'];

  $ex=$d->prepare("SELECT id FROM notas WHERE order_id=? AND status='autorizada'"); $ex->execute([$orderId]);
  if($ex->fetch()) return ['ok'=>true,'status'=>'autorizada','motivo'=>'já emitida'];

  $cfg = fiscal_config();
  $numero = fiscal_proximo_numero();
  $d->prepare("INSERT INTO notas(order_id,numero,serie,modelo,status,ambiente,valor,motivo)
               VALUES(?,?,?, '65','pendente',?,?, 'aguardando emissão')")
    ->execute([$orderId,$numero,$cfg['serie'],$cfg['ambiente'],$o['total']]);
  $notaId = $d->lastInsertId();

  $itens = fiscal_itens($orderId);
  $driver = $cfg['driver'];
  $fn = '_fiscal_emit_'.$driver;
  if(!function_exists($fn)){
    return _fiscal_pendente($notaId,'Emissor desconhecido: '.$driver);
  }
  return $fn($notaId, $o, $itens, $cfg);
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

// Monta o corpo padrão do pedido para as APIs (formato genérico)
function _fiscal_body($o,$itens,$cfg){
  $items=[];
  foreach($itens as $i){
    $items[] = [
      'descricao'=>$i['nome'], 'quantidade'=>(int)$i['qtd'],
      'valor_unitario'=>(float)$i['preco'],
      'ncm'=>$i['ncm']?:'21069090', 'cfop'=>$i['cfop']?:'5102',
      'cst_csosn'=>$i['cst']?:'102', 'origem'=>$i['origem']?:'0',
      'unidade'=>$i['unidade']?:'UN',
    ];
  }
  return [
    'ambiente'=>$cfg['ambiente']==='1'?'producao':'homologacao',
    'cnpj'=>$cfg['cnpj'], 'ie'=>$cfg['ie'], 'uf'=>$cfg['uf'],
    'regime'=>$cfg['regime'], 'serie'=>$cfg['serie'],
    'consumidor'=>['nome'=>$o['cliente_nome'],'documento'=>null],
    'itens'=>$items, 'valor_total'=>(float)$o['total'],
    'pagamento'=>$o['pagamento_metodo'],
  ];
}

// POST JSON simples (usado pelos drivers de API)
function _fiscal_post($url,$headers,$body){
  if(!function_exists('curl_init')) return [0,'cURL indisponível no servidor'];
  $ch=curl_init($url);
  curl_setopt_array($ch,[
    CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>json_encode($body),
    CURLOPT_HTTPHEADER=>array_merge(['Content-Type: application/json'],$headers),
    CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>25,
  ]);
  $resp=curl_exec($ch); $code=curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
  return [$code,$resp];
}

// ============================================================
// DRIVERS  (esqueleto pronto — ativam quando tiver credencial)
// ============================================================

// --- PlugNotas ---
function _fiscal_emit_plugnotas($notaId,$o,$itens,$cfg){
  if($cfg['plug_token']==='') return _fiscal_pendente($notaId,'PlugNotas: informe o API Token em ⚙ Config.');
  $body=_fiscal_body($o,$itens,$cfg);
  [$code,$resp]=_fiscal_post('https://api.plugnotas.com.br/nfce',
     ['X-API-KEY: '.$cfg['plug_token']], $body);
  $j=json_decode($resp,true);
  if($code>=200 && $code<300 && $j){
    return _fiscal_ok($notaId, $j['chave']??($j['id']??''), $j['protocolo']??'', $j['danfe']??'');
  }
  return _fiscal_erro($notaId,'rejeitada', 'PlugNotas: '.substr((string)$resp,0,300));
}

// --- Focus NFe ---
function _fiscal_emit_focus($notaId,$o,$itens,$cfg){
  if($cfg['focus_token']==='') return _fiscal_pendente($notaId,'Focus NFe: informe o Token em ⚙ Config.');
  $base = $cfg['ambiente']==='1' ? 'https://api.focusnfe.com.br' : 'https://homologacao.focusnfe.com.br';
  $body=_fiscal_body($o,$itens,$cfg);
  [$code,$resp]=_fiscal_post($base.'/v2/nfce?ref=RV'.$o['id'],
     ['Authorization: Basic '.base64_encode($cfg['focus_token'].':')], $body);
  $j=json_decode($resp,true);
  if($code>=200 && $code<300 && $j){
    return _fiscal_ok($notaId, $j['chave_nfe']??'', $j['numero']??'', $j['caminho_danfe']??'');
  }
  return _fiscal_erro($notaId,'rejeitada', 'Focus: '.substr((string)$resp,0,300));
}

// --- eNotas ---
function _fiscal_emit_enotas($notaId,$o,$itens,$cfg){
  if($cfg['enotas_apikey']==='' || $cfg['enotas_empresa']==='')
    return _fiscal_pendente($notaId,'eNotas: informe API Key e ID da empresa em ⚙ Config.');
  $body=_fiscal_body($o,$itens,$cfg);
  [$code,$resp]=_fiscal_post('https://api.enotasgw.com.br/v2/empresas/'.$cfg['enotas_empresa'].'/nfce',
     ['Authorization: Basic '.base64_encode($cfg['enotas_apikey'].':')], $body);
  $j=json_decode($resp,true);
  if($code>=200 && $code<300){
    return _fiscal_ok($notaId, $j['id']??'', '', '');
  }
  return _fiscal_erro($notaId,'rejeitada', 'eNotas: '.substr((string)$resp,0,300));
}

// --- NFC-e Fácil ---
function _fiscal_emit_nfcefacil($notaId,$o,$itens,$cfg){
  if($cfg['nfcef_token']==='') return _fiscal_pendente($notaId,'NFC-e Fácil: informe o Token em ⚙ Config.');
  $body=_fiscal_body($o,$itens,$cfg);
  [$code,$resp]=_fiscal_post('https://api.nf-e.com.br/nfce',
     ['Authorization: Bearer '.$cfg['nfcef_token']], $body);
  $j=json_decode($resp,true);
  if($code>=200 && $code<300 && $j){
    return _fiscal_ok($notaId, $j['chave']??'', $j['protocolo']??'', $j['danfe']??'');
  }
  return _fiscal_erro($notaId,'rejeitada', 'NFC-e Fácil: '.substr((string)$resp,0,300));
}

// --- Direto SEFAZ (sped-nfe, 0 por nota) ---
function _fiscal_emit_sped($notaId,$o,$itens,$cfg){
  $temLib = class_exists('\\NFePHP\\NFe\\Tools') && extension_loaded('soap') && extension_loaded('openssl');
  if(!$temLib || !is_file($cfg['cert_file'])){
    return _fiscal_pendente($notaId,
      'Direto SEFAZ: precisa do sped-nfe (vendor/), extensão SOAP e do certificado A1 em data/certificado.pfx. '
      .'Normalmente roda no VPS. Ver FISCAL-README.md.');
  }
  // Aqui entra a montagem/assinatura/transmissão via sped-nfe (Make + signNFe + sefazEnviaLote).
  // Mantido guardado; ver FISCAL-README.md para o mapeamento completo dos campos.
  return _fiscal_pendente($notaId,'Direto SEFAZ: ambiente pronto — finalizar montagem do XML no VPS.');
}

// ============================================================
function fiscal_cancelar($notaId,$justificativa){
  $n=db()->prepare("SELECT * FROM notas WHERE id=?"); $n->execute([$notaId]); $n=$n->fetch();
  if(!$n) return ['ok'=>false];
  // cada emissor tem seu endpoint de cancelamento; guardado como a emissão.
  db()->prepare("UPDATE notas SET status='cancelada', motivo=? WHERE id=?")->execute([$justificativa,$notaId]);
  n8n_event('nota_cancelada',['nota_id'=>$notaId]);
  return ['ok'=>true,'status'=>'cancelada'];
}

// Gatilho automático: emite quando o pedido é pago (se ligado no painel)
function fiscal_auto_ao_pagar($orderId){
  if(setting_get('nf_auto','0')==='1') fiscal_emitir($orderId,'auto');
}
