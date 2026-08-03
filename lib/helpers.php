<?php
// ============================================================
// Helpers
// ============================================================

// Retorna o slug do tenant atual (vazio para o painel SaaS master)
function current_slug(){
  static $s=null;
  if($s!==null) return $s;
  $s=preg_replace('/[^a-z0-9_-]/','',strtolower((string)($_GET['slug']??$_POST['slug']??'')));
  return $s;
}

// Inicia sessão isolada por slug (tenants nunca compartilham sessão entre si)
function _start_session(){
  if(session_status()!==PHP_SESSION_NONE) return;
  $slug=current_slug();
  if($slug) session_name('rv_'.$slug);
  ini_set('session.gc_maxlifetime', 28800);
  session_set_cookie_params(['lifetime'=>28800,'path'=>'/']);
  session_start();
}

function cfg($k=null){
  static $c=null;
  if($c===null){
    $c=require __DIR__.'/../config.php';
  }
  if($k===null) return $c;
  return $c[$k] ?? null;
}

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($v){ return 'R$ '.number_format((float)$v,2,',','.'); }
function json_out($data){ header('Content-Type: application/json; charset=utf-8'); echo json_encode($data); exit; }

// Preserva o slug do tenant em todos os redirecionamentos internos
function redirect($to){
  $slug=current_slug();
  if($slug && strpos($to,'slug=')===false && (strpos($to,'?')===0 || strpos($to,'index.php')!==false)){
    $sep=(strpos($to,'?')!==false ? '&' : '?');
    $hash=strpos($to,'#');
    if($hash!==false){
      $to=substr($to,0,$hash).$sep.'slug='.$slug.substr($to,$hash);
    } else {
      $to.=$sep.'slug='.$slug;
    }
  }
  header('Location: '.$to);
  exit;
}

function view($name,$vars=[]){
  extract($vars);
  $view_file = __DIR__.'/../views/'.$name.'.php';
  ob_start(); include $view_file; return ob_get_clean();
}
function render($name,$vars=[]){
  $content = view($name,$vars);
  echo view('layout', array_merge($vars,['content'=>$content]));
}

// Sessões simples de acesso
function require_role($role){
  _start_session();
  if(($_SESSION['user_role']??'')===$role || ($_SESSION['user_role']??'')==='admin') return true;
  $senha = cfg($role.'_senha');
  if (($_SESSION['role_'.$role] ?? '') === $senha) return true;
  return false;
}
function do_login($role,$senha,$usuario=''){
  _start_session();
  if($usuario!==''){
    // Tenta no banco do tenant atual; fallback para o master (usuários criados sem slug)
    foreach([db(), master_db()] as $_lpdo){
      try{
        $q=$_lpdo->prepare("SELECT * FROM users WHERE usuario=? AND role=? AND ativo=1 LIMIT 1");
        $q->execute([$usuario,$role]); $u=$q->fetch();
        if($u && password_verify($senha,$u['senha_hash'])){
          $_SESSION['user_id']=$u['id']; $_SESSION['user_role']=$u['role']; $_SESSION['user_nome']=$u['nome'];
          return true;
        }
      }catch(Exception $e){}
    }
    return false;
  }
  if ($senha === cfg($role.'_senha')){ $_SESSION['role_'.$role] = $senha; if($role==='admin') $_SESSION['user_role']='admin'; return true; }
  return false;
}

// Dispara evento para o n8n (não trava o fluxo se falhar)
function n8n_event($evento,$payload){
  $n = cfg('n8n');
  $url = setting_get('n8n_webhook','');           // painel tem prioridade
  if ($url==='' && !empty($n['ativo'])) $url = $n['webhook_url'] ?? '';
  if ($url==='') return;
  $n = ['webhook_url'=>$url];
  $data = json_encode(['evento'=>$evento] + $payload);
  if (function_exists('curl_init')){
    $ch = curl_init($n['webhook_url']);
    curl_setopt_array($ch,[
      CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>$data,
      CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
      CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>4,
    ]);
    @curl_exec($ch); curl_close($ch);
  }
}

// uazapi: WhatsApp via QR Code gerenciado centralmente pelo SaaS (sem config pelo cliente).
// URL e key ficam no master db (saas_uaz_url / saas_uaz_key). Cada tenant usa seu slug como instância.
function _uaz_cfg($k){
  try{ $q=master_db()->prepare("SELECT valor FROM settings WHERE chave=?"); $q->execute([$k]); $r=$q->fetch(); return $r?$r['valor']:''; }
  catch(Exception $e){ return ''; }
}
function uazapi_configured(){
  return _uaz_cfg('saas_uaz_url')!=='' && _uaz_cfg('saas_uaz_key')!=='';
}
// Requisição com admin token (criação de instâncias)
function uazapi_request($method,$path,$body=null){
  if(!uazapi_configured()||!function_exists('curl_init')) return ['ok'=>false,'erro'=>'uazapi não configurado.'];
  return _uazapi_curl(_uaz_cfg('saas_uaz_url'),$path,$method,$body,'admintoken: '._uaz_cfg('saas_uaz_key'));
}
// Requisição com token da instância (connect, status, disconnect)
function uazapi_instance_request($tok,$method,$path,$body=null){
  if(!uazapi_configured()||!function_exists('curl_init')) return ['ok'=>false,'erro'=>'uazapi não configurado.'];
  return _uazapi_curl(_uaz_cfg('saas_uaz_url'),$path,$method,$body,'token: '.$tok);
}
function _uazapi_curl($base,$path,$method,$body,$authHeader){
  $url=rtrim($base,'/').'/'.ltrim($path,'/');
  $ch=curl_init($url);
  curl_setopt_array($ch,[CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>6,CURLOPT_TIMEOUT=>20,CURLOPT_HTTPHEADER=>[$authHeader,'Content-Type: application/json']]);
  if($body!==null)curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($body,JSON_UNESCAPED_UNICODE));
  $raw=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$err=curl_error($ch);curl_close($ch);
  $data=json_decode((string)$raw,true);
  return ['ok'=>$code>=200&&$code<300,'status'=>$code,'data'=>$data,'raw'=>$raw,'erro'=>$err?:''];
}

// ============================================================
// UAZAPI — restaurante (painel único, não SaaS)
// Auth por instância: header 'token'. Admin: header 'admintoken'.
// Settings: uaz_url, uaz_admintoken, uaz_token, uaz_instance_name
// ============================================================
function uaz_r_request($method,$path,$body=null,$admin=false){
  $base=rtrim(setting_get('uaz_url',''),'/');
  if(!$base||!function_exists('curl_init')) return ['ok'=>false,'erro'=>'UazAPI não configurada.'];
  $token=$admin?setting_get('uaz_admintoken',''):setting_get('uaz_token','');
  if(!$token) return ['ok'=>false,'erro'=>$admin?'Admin token não configurado.':'Token da instância não configurado.'];
  $ch=curl_init($base.'/'.ltrim($path,'/'));
  $headers=[($admin?'admintoken':'token').': '.$token,'Content-Type: application/json'];
  curl_setopt_array($ch,[CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>6,CURLOPT_TIMEOUT=>20,CURLOPT_HTTPHEADER=>$headers]);
  if($body!==null)curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($body,JSON_UNESCAPED_UNICODE));
  $raw=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$err=curl_error($ch);curl_close($ch);
  $data=json_decode((string)$raw,true);
  return ['ok'=>$code>=200&&$code<300,'status'=>$code,'data'=>$data,'raw'=>$raw,'erro'=>$err?:''];
}
function uaz_r_configured(){
  return setting_get('uaz_url','')!==''&&setting_get('uaz_token','')!=='';
}
function uaz_r_send_text($number,$text){
  $number=preg_replace('/\D/','',(string)$number);
  if(strlen($number)<=11)$number='55'.$number;
  return uaz_r_request('POST','/send/text',['number'=>$number,'text'=>$text,'delay'=>800]);
}

// Evolution API: canal WhatsApp usado pela Central e pelos avisos de entrega.
function evolution_configured(){
  return setting_get('evo_url','')!=='' && setting_get('evo_apikey','')!=='' && setting_get('evo_instance','')!=='';
}
function evolution_request($method,$path,$body=null){
  if(!evolution_configured() || !function_exists('curl_init')) return ['ok'=>false,'erro'=>'Evolution API não configurada.'];
  $url=rtrim(setting_get('evo_url',''),'/').'/'.ltrim($path,'/');
  $ch=curl_init($url);$headers=['apikey: '.setting_get('evo_apikey',''),'Content-Type: application/json'];
  curl_setopt_array($ch,[CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>15,CURLOPT_HTTPHEADER=>$headers]);
  if($body!==null)curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($body,JSON_UNESCAPED_UNICODE));
  $raw=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$err=curl_error($ch);curl_close($ch);
  $data=json_decode((string)$raw,true);
  return ['ok'=>$code>=200&&$code<300,'status'=>$code,'data'=>$data,'raw'=>$raw,'erro'=>$err?:($code>=200&&$code<300?'':'Evolution recusou a solicitação.')];
}
function evolution_send_text($number,$text){
  $number=preg_replace('/\D/','',(string)$number);if(strlen($number)<=11)$number='55'.$number;
  return evolution_request('POST','message/sendText/'.rawurlencode(setting_get('evo_instance','')),['number'=>$number,'text'=>$text,'delay'=>300,'linkPreview'=>true]);
}
function evolution_delivery_alert($courier,$order){
  if(empty($courier['telefone'])||!evolution_configured())return false;
  $codigo=trim((string)($order['codigo']??''));
  $cliente=trim((string)($order['cliente_nome']??''));
  $detalhe=$codigo!==''?'\nPedido '.$codigo.($cliente!==''?' · '.$cliente:''):'';
  $texto="🛵 NOVA ENTREGA PARA VOCÊ".$detalhe."\nRetire agora no balcão. Abra seu app de motoboy para consultar e aceitar com segurança.";
  return evolution_send_text($courier['telefone'],$texto)['ok']??false;
}

// Settings (chave/valor no banco — integrações preenchidas pelo painel)
function setting_get($k,$def=''){
  $s=db()->prepare("SELECT valor FROM settings WHERE chave=?"); $s->execute([$k]); $r=$s->fetch();
  if($r!==false) return $r['valor'];
  return $def;
}
function setting_set($k,$v){
  db()->prepare("INSERT INTO settings(chave,valor) VALUES(?,?)
                 ON CONFLICT(chave) DO UPDATE SET valor=excluded.valor")->execute([$k,$v]);
}

// ============================================================
// FECHAMENTO TEMPORÁRIO DA LOJA
// Retorna o status atual e reabre automaticamente quando o prazo passa.
// ============================================================
function store_status(){
  static $st=null;
  if($st!==null) return $st;
  $fechada = setting_get('store_closed','0')==='1';
  $ate = setting_get('store_closed_until','');
  // Reabertura automática quando o prazo já passou
  if($fechada && $ate!=='' && strtotime($ate) <= time()){
    setting_set('store_closed','0');
    $fechada=false;
  }
  $st=[
    'closed'  => $fechada,
    'until'   => $ate,
    'reason'  => setting_get('store_closed_reason',''),
    'message' => setting_get('store_closed_msg',''),
  ];
  return $st;
}
// Monta a mensagem automática que o bot envia quando a loja está fechada.
function store_closed_message(){
  $s=store_status();
  if(!$s['closed']) return '';
  if(trim($s['message'])!=='') return $s['message'];
  $loja = setting_get('store_name','') ?: cfg('restaurante');
  $tempo='';
  if($s['until']!==''){
    $min = max(0, (int)round((strtotime($s['until'])-time())/60));
    if($min>=60){ $h=floor($min/60); $tempo=' por aproximadamente '.$h.'h'.($min%60?' '.($min%60).'min':''); }
    elseif($min>0){ $tempo=' por aproximadamente '.$min.' minutos'; }
  }
  $motivo = $s['reason']!=='' ? ' devido a '.$s['reason'] : '';
  return "Olá! 😊 No momento estamos *fechados*{$tempo}{$motivo}. Logo reabriremos os pedidos. Obrigado pela compreensão! — {$loja}";
}

// ============================================================
// SaaS — painel do dono da plataforma
// ============================================================
function saas_master_pass(){ return setting_get('saas_master_pass','') ?: cfg('saas_senha'); }

function saas_csrf_token(){
  _start_session();
  if(empty($_SESSION['saas_csrf'])) $_SESSION['saas_csrf']=bin2hex(random_bytes(16));
  return $_SESSION['saas_csrf'];
}
function saas_csrf_field(){ return '<input type="hidden" name="saas_csrf" value="'.htmlspecialchars(saas_csrf_token(),ENT_QUOTES).'">'; }
function saas_csrf_check(){
  _start_session();
  if(!hash_equals(saas_csrf_token(),(string)($_POST['saas_csrf']??''))){
    http_response_code(403); die('Requisição inválida. Recarregue a página e tente novamente.');
  }
}

function _rl_file($action='login'){
  $ip=preg_replace('/[^0-9a-f:.\[\]]/','',strtolower($_SERVER['REMOTE_ADDR']??'0'));
  return __DIR__.'/../data/rl_'.md5($action.$ip).'.json';
}
function saas_rate_limit_check($action='login',$max=5,$window=900){
  $f=_rl_file($action); $d=json_decode(@file_get_contents($f)?:'{}',true)??[];
  if(($d['until']??0)>time()){
    $mins=ceil(($d['until']-time())/60);
    $msg="Muitas tentativas. Aguarde {$mins} minuto(s).";
    if($action==='signup'){ header('Content-Type:application/json'); echo json_encode(['ok'=>false,'erro'=>$msg]); exit; }
    saas_render('saas_login',['erro'=>$msg]); exit;
  }
  return $d;
}
function saas_rate_limit_fail(array $d,$action='login',$max=5,$window=900){
  $f=_rl_file($action);
  $d['fails']=($d['fails']??0)+1;
  if($d['fails']>=$max){ $d=['fails'=>0,'until'=>time()+$window]; }
  file_put_contents($f,json_encode($d));
}
function saas_rate_limit_reset($action='login'){
  @unlink(_rl_file($action));
}
function saas_render($name,$vars=[]){
  $content = view($name,$vars);
  echo view('saas_layout', array_merge($vars,['content'=>$content]));
}
function saas_logged(){ _start_session(); return !empty($_SESSION['saas_ok']); }
function saas_require(){ if(!saas_logged()){ saas_render('saas_login',['erro'=>'']); exit; } }
function saas_plan_price($plano){
  // Planos anuais armazenam o valor TOTAL anual (não mensal)
  $defaults=['basico'=>'99.00','basico_anual'=>'954.00','pro'=>'149.00','premium'=>'199.00','pro_anual'=>'1548.00','premium_anual'=>'2148.00'];
  return (float)setting_get('saas_preco_'.$plano, $defaults[$plano]??'149.00');
}
function saas_plan_info($plano){
  $pBas=saas_plan_price('basico'); $pBasAn=saas_plan_price('basico_anual');
  $pPro=saas_plan_price('pro'); $pPrem=saas_plan_price('premium');
  $pProAn=saas_plan_price('pro_anual'); $pPremAn=saas_plan_price('premium_anual');
  $map=[
    'basico'        =>['label'=>'Básico',        'anual'=>false,'valor'=>$pBas,   'mensal'=>$pBas,               'economia'=>0],
    'basico_anual'  =>['label'=>'Básico Anual',  'anual'=>true, 'valor'=>$pBasAn, 'mensal'=>round($pBasAn/12,2), 'economia'=>round($pBas*12-$pBasAn,2)],
    'pro'           =>['label'=>'Pró',           'anual'=>false,'valor'=>$pPro,   'mensal'=>$pPro,               'economia'=>0],
    'pro_anual'     =>['label'=>'Pró Anual',     'anual'=>true, 'valor'=>$pProAn, 'mensal'=>round($pProAn/12,2), 'economia'=>round($pPro*12-$pProAn,2)],
    'premium'       =>['label'=>'Premium',        'anual'=>false,'valor'=>$pPrem,  'mensal'=>$pPrem,              'economia'=>0],
    'premium_anual' =>['label'=>'Premium Anual', 'anual'=>true, 'valor'=>$pPremAn,'mensal'=>round($pPremAn/12,2),'economia'=>round($pPrem*12-$pPremAn,2)],
  ];
  return $map[$plano]??$map['pro'];
}
function saas_plan_label($plano){
  return ['basico'=>'Básico','basico_anual'=>'Básico Anual','pro'=>'Pró','pro_anual'=>'Pró Anual','premium'=>'Premium','premium_anual'=>'Premium Anual'][$plano]??'Pró';
}
// Remove sufixo _anual para obter o plano base
function saas_plan_base($plano){ return preg_replace('/_anual$/','',(string)$plano); }
// Verifica se um plano dá acesso a uma feature (bot, cozinha)
function saas_plan_feature_default($feat,$plano,$status){
  if($status==='trial') return true;
  $base=saas_plan_base($plano);
  if($feat==='bot')     return $base!=='basico';  // básico não tem robô
  if($feat==='cozinha') return $base==='premium'; // só premium tem cozinha (KDS)
  return true;
}
// Retorna ['bot'=>bool,'cozinha'=>bool] do restaurante (plano + override manual)
function client_features($slug){
  static $cache=[];
  $slug=(string)$slug;
  if($slug==='') return ['bot'=>true,'cozinha'=>true];
  if(isset($cache[$slug])) return $cache[$slug];
  $f=['bot'=>true,'cozinha'=>true];
  try{
    $q=master_db()->prepare("SELECT status,plano,feat_bot,feat_cozinha FROM saas_clients WHERE slug=? LIMIT 1");
    $q->execute([$slug]); $c=$q->fetch();
    if($c){
      if($c['status']==='trial'){ $f=['bot'=>true,'cozinha'=>true]; }
      else{
        $f['bot']     = $c['feat_bot']===null     ? saas_plan_feature_default('bot',$c['plano'],$c['status'])     : ((int)$c['feat_bot']===1);
        $f['cozinha'] = $c['feat_cozinha']===null ? saas_plan_feature_default('cozinha',$c['plano'],$c['status']) : ((int)$c['feat_cozinha']===1);
      }
    }
  }catch(\Throwable $e){}
  return $cache[$slug]=$f;
}
// Robô só responde se o plano libera E o cliente ativou
function tenant_bot_active(){
  return setting_get('wa_bot_active','0')==='1' && client_features(current_slug())['bot'];
}
// Pula a cozinha (KDS) se o cliente marcou OU se o plano não libera cozinha
function tenant_skip_kds(){
  if(setting_get('skip_kds','0')==='1') return true;
  return !client_features(current_slug())['cozinha'];
}
// App do motoboy desligado (restaurante opera entregas manualmente pelo painel)
function tenant_motoboy_off(){
  return setting_get('motoboy_app_off','0')==='1';
}

// Recalcula o status de cada cliente: aplica bloqueio automático 15 dias após o
// vencimento (a menos que já esteja cancelado ou desbloqueado manualmente).
function saas_refresh_status(){
  $d=db(); $hoje=date('Y-m-d');
  foreach($d->query("SELECT * FROM saas_clients WHERE status<>'cancelado'")->fetchAll() as $c){
    $novo=$c['status']; 
    // Fim do trial: se acabou e não há vencimento futuro pago, vira bloqueado
    if($c['status']==='trial'){
      if(!empty($c['trial_ate']) && $c['trial_ate']<$hoje) $novo='bloqueado';
    }
    if(in_array($c['status'],['ativo','bloqueado'],true) && empty($c['bloqueio_manual'])){
      if(!empty($c['proximo_venc'])){
        $limite=date('Y-m-d', strtotime($c['proximo_venc'].' +15 days'));
        if($hoje>$limite) $novo='bloqueado';
        elseif($c['status']==='bloqueado' && $hoje<=$c['proximo_venc']) $novo='ativo';
      }
    }
    if($novo!==$c['status']){
      $set="status=?"; $params=[$novo];
      if($novo==='bloqueado'){ $set.=", bloqueado_em=datetime('now','localtime')"; }
      $d->prepare("UPDATE saas_clients SET $set WHERE id=?")->execute(array_merge($params,[$c['id']]));
    }
  }
}
// Dias em atraso (positivo) ou faltando (negativo) até o vencimento
function saas_dias_venc($c){
  $ref=$c['status']==='trial' ? ($c['trial_ate']??'') : ($c['proximo_venc']??'');
  if(!$ref) return null;
  return (int)floor((strtotime(date('Y-m-d'))-strtotime($ref))/86400);
}
function saas_metrics(){
  $d=db(); $mes=date('Y-m');
  $r=$d->query("SELECT
    COUNT(*) total,
    SUM(status='ativo') ativos,
    SUM(status='trial') trial,
    SUM(status='bloqueado') bloqueados,
    SUM(status='cancelado') cancelados,
    SUM(status='bloqueado') inadimplentes,
    SUM(CASE WHEN status='ativo' AND plano NOT LIKE '%_anual' THEN valor_mensal
             WHEN status='ativo' AND plano     LIKE '%_anual' THEN valor_mensal/12.0
             ELSE 0 END) mrr,
    SUM(status='ativo' AND plano='basico') basico,
    SUM(status='ativo' AND plano='basico_anual') basico_anual,
    SUM(status='ativo' AND plano='pro') pro,
    SUM(status='ativo' AND plano='premium') premium,
    SUM(status='ativo' AND plano='pro_anual') pro_anual,
    SUM(status='ativo' AND plano='premium_anual') premium_anual
    FROM saas_clients")->fetch();
  $m=array_map(fn($v)=>(int)$v??0,(array)$r);
  $m['mrr']=(float)($r['mrr']??0);
  $m['receita_mes']=(float)($d->query("SELECT COALESCE(SUM(valor),0) v FROM saas_payments WHERE status='pago' AND substr(pago_em,1,7)='$mes'")->fetch()['v']??0);
  $m['receita_total']=(float)($d->query("SELECT COALESCE(SUM(valor),0) v FROM saas_payments WHERE status='pago'")->fetch()['v']??0);
  return $m;
}
function saas_audit($action,$clientId,$details=''){
  try{
    master_db()->exec("CREATE TABLE IF NOT EXISTS saas_audit_log(id INTEGER PRIMARY KEY AUTOINCREMENT,action TEXT,client_id INTEGER,details TEXT,user_ip TEXT,ts TEXT DEFAULT(datetime('now','localtime')))");
    master_db()->prepare("INSERT INTO saas_audit_log(action,client_id,details,user_ip) VALUES(?,?,?,?)")->execute([$action,(int)$clientId,$details,$_SERVER['REMOTE_ADDR']??'']);
  }catch(Exception $e){}
}
// Calcula a data de vencimento ancorando ao dia fixo do cliente.
function _venc_prox($base,$plano,$dia){
  $dia=max(1,min(28,(int)$dia));
  $ts=strtotime($base.(strpos($plano??'','_anual')!==false?' +1 year':' +1 month'));
  $y=(int)date('Y',$ts); $mo=(int)date('m',$ts);
  $dim=(int)date('t',mktime(0,0,0,$mo,1,$y));
  return sprintf('%04d-%02d-%02d',$y,$mo,min($dia,$dim));
}
// Registra pagamento e empurra o próximo vencimento; reativa se bloqueado.
function saas_registrar_pagamento($clientId,$valor,$metodo,$obs=''){
  $d=db(); $q=$d->prepare("SELECT * FROM saas_clients WHERE id=?"); $q->execute([$clientId]); $c=$q->fetch();
  if(!$c) return false;
  // DATA-04: não reativar cliente cancelado via pagamento; bloqueio_manual permanece
  if($c['status']==='cancelado') return false;
  $d->beginTransaction();
  try{
    $d->prepare("INSERT INTO saas_payments(client_id,competencia,valor,metodo,status,obs) VALUES(?,?,?,?, 'pago', ?)")
      ->execute([$clientId, date('Y-m'), $valor, $metodo, $obs]);
    $base = !empty($c['proximo_venc']) ? $c['proximo_venc'] : date('Y-m-d');
    $prox = _venc_prox($base, $c['plano']??'pro', $c['dia_vencimento']??10);
    $d->prepare("UPDATE saas_clients SET status='ativo', proximo_venc=?, bloqueado_em=NULL, bloqueio_manual=0, atualizado_em=datetime('now','localtime') WHERE id=?")
      ->execute([$prox,$clientId]);
    $d->commit();
    return true;
  }catch(Exception $e){ $d->rollBack(); return false; }
}

// Caixa
function caixa_atual(){
  return db()->query("SELECT * FROM caixa WHERE status='aberto' ORDER BY id DESC LIMIT 1")->fetch() ?: null;
}
function caixa_mov_add($tipo,$valor,$categoria='',$descricao='',$orderId=null){
  $c = caixa_atual();
  if(!$c) return false; // caixa fechado: não registra
  db()->prepare("INSERT INTO caixa_mov(caixa_id,tipo,categoria,descricao,valor,order_id) VALUES(?,?,?,?,?,?)")
    ->execute([$c['id'],$tipo,$categoria,$descricao,$valor,$orderId]);
  return true;
}
// Registra a venda no caixa quando o pedido é pago
function caixa_registra_venda($orderId){
  $o=db()->prepare("SELECT * FROM orders WHERE id=?"); $o->execute([$orderId]); $o=$o->fetch();
  if(!$o) return false;
  $ja=db()->prepare("SELECT id FROM caixa_mov WHERE tipo='venda' AND order_id=? LIMIT 1");
  $ja->execute([$orderId]);
  if($ja->fetch()) return true;
  return caixa_mov_add('venda',$o['total'],$o['pagamento_metodo'],'Pedido '.$o['codigo'],$orderId);
}

function order_mark_paid($orderId){
  $q=db()->prepare("SELECT pagamento_status FROM orders WHERE id=?"); $q->execute([$orderId]); $o=$q->fetch();
  if(!$o) return false;
  if($o['pagamento_status']!=='pago')
    db()->prepare("UPDATE orders SET pagamento_status='pago', pagamento_detectado_em=COALESCE(pagamento_detectado_em,datetime('now','localtime')), atualizado_em=datetime('now','localtime') WHERE id=?")->execute([$orderId]);
  caixa_registra_venda($orderId);
  fiscal_auto_ao_pagar($orderId);
  return true;
}

function payment_is_online($method){
  return in_array((string)$method,['pix','cartao_online','online'],true);
}
function payment_blocks_completion($order){
  return $order && payment_is_online($order['pagamento_metodo']??'') && ($order['pagamento_status']??'pendente')!=='pago';
}
function payment_situation_label($order){
  if(($order['pagamento_status']??'pendente')==='pago') return 'PAGO - nao receber novamente';
  if(payment_is_online($order['pagamento_metodo']??'')) return 'AGUARDANDO CONFIRMACAO DO PAGAMENTO';
  return 'RECEBER DO CLIENTE';
}

function payment_label($method){
  return ['pix'=>'Pix online','pix_entrega'=>'Pix na entrega','debito'=>'Cartão de débito','credito'=>'Cartão de crédito','cartao_online'=>'Cartão online','online'=>'Cartão online (InfinitePay)','cartao_entrega'=>'Cartão na entrega','dinheiro'=>'Dinheiro','ifood'=>'iFood','mesa'=>'Mesa'][$method] ?? ucfirst(str_replace('_',' ',(string)$method));
}

// Comanda (pedido) aberta de uma mesa
function mesa_order($numero){
  $o=db()->prepare("SELECT * FROM orders WHERE tipo='mesa' AND mesa=? AND status NOT IN ('concluido','cancelado','entregue') ORDER BY id DESC LIMIT 1");
  $o->execute([$numero]); return $o->fetch();
}
function customer_upsert($nome,$telefone,$endereco='',$bairro='',$total=0,$email='',$customerId=0){
  $telefone=preg_replace('/\D/','',(string)$telefone);$email=mb_strtolower(trim((string)$email));
  if(!$customerId&&$telefone===''&&$email==='') return 0;
  if($customerId){$q=db()->prepare("SELECT id FROM customers WHERE id=? LIMIT 1");$q->execute([$customerId]);}
  elseif($email!==''){$q=db()->prepare("SELECT id FROM customers WHERE lower(email)=lower(?) LIMIT 1");$q->execute([$email]);}
  else{$q=db()->prepare("SELECT id FROM customers WHERE telefone=? LIMIT 1");$q->execute([$telefone]);}
  $c=$q->fetch();
  if($c){
    db()->prepare("UPDATE customers SET nome=?,telefone=CASE WHEN ?<>'' THEN ? ELSE telefone END,email=CASE WHEN ?<>'' THEN ? ELSE email END,endereco=CASE WHEN ?<>'' THEN ? ELSE endereco END,bairro=CASE WHEN ?<>'' THEN ? ELSE bairro END,pedidos=pedidos+1,total_gasto=total_gasto+?,ultimo_pedido=datetime('now','localtime') WHERE id=?")
      ->execute([$nome,$telefone,$telefone,$email,$email,$endereco,$endereco,$bairro,$bairro,$total,$c['id']]);
    return (int)$c['id'];
  }
  db()->prepare("INSERT INTO customers(nome,telefone,email,endereco,bairro,pedidos,total_gasto) VALUES(?,?,?,?,?,1,?)")->execute([$nome,$telefone,$email,$endereco,$bairro,$total]);
  return (int)db()->lastInsertId();
}

function customer_logged(){
  _start_session();
  $id=(int)($_SESSION['customer_id']??0);if(!$id)return null;
  $q=db()->prepare("SELECT * FROM customers WHERE id=?");$q->execute([$id]);return $q->fetch()?:null;
}

function recovery_process(){
  if(setting_get('recovery_active','0')!=='1'||!evolution_configured())return 0;
  $delay=max(5,(int)setting_get('recovery_delay','30'));
  $q=db()->prepare("SELECT * FROM abandoned_carts WHERE status='aberto' AND aviso_enviado_em IS NULL AND telefone<>'' AND atualizado_em<=datetime('now','localtime',?) ORDER BY id LIMIT 10");
  $q->execute(['-'.$delay.' minutes']);$sent=0;
  $msg=setting_get('recovery_message','Olá {NOME}! Você deixou itens no carrinho. Finalize seu pedido aqui: {LINK_CARDAPIO}');
  $base=rtrim((isset($_SERVER['HTTPS'])?'https':'http').'://'.($_SERVER['HTTP_HOST']??'').dirname($_SERVER['SCRIPT_NAME']??'/cardapio/index.php'),'/');
  foreach($q->fetchAll() as $c){
    $text=str_replace(['{NOME}','{LINK_CARDAPIO}'],[$c['nome']?:'cliente',$base.'/?r=menu&cart='.$c['token']],$msg);
    $r=evolution_send_text($c['telefone'],$text);
    if(!empty($r['ok'])){db()->prepare("UPDATE abandoned_carts SET aviso_enviado_em=datetime('now','localtime') WHERE id=?")->execute([$c['id']]);$sent++;}
  }
  return $sent;
}
function mesa_order_criar($numero,$atendente){
  db()->prepare("INSERT INTO orders(codigo,canal,tipo,mesa,atendente,status,total,pagamento_metodo,pagamento_status)
                 VALUES(?,?,?,?,?,?,0,'','pendente')")
    ->execute(['','garcom','mesa',$numero,$atendente,'mesa_rascunho']);
  $id=db()->lastInsertId();db()->prepare("UPDATE orders SET codigo=? WHERE id=?")->execute(['#'.str_pad($id,4,'0',STR_PAD_LEFT),$id]);return $id;
}
function mesa_recalcula($orderId){
  $t=db()->prepare("SELECT COALESCE(SUM(qtd*preco),0) t FROM order_items WHERE order_id=?"); $t->execute([$orderId]);
  $tot=$t->fetch()['t'];
  db()->prepare("UPDATE orders SET total=? WHERE id=?")->execute([$tot,$orderId]);
  return $tot;
}

// Sub-navegação do painel (abas)
function admin_subnav($active){
  $items = [
    'admin_orders'    => 'Pedidos',
    'kds'             => 'Cozinha KDS',
    'admin_produtos'  => 'Produtos',
    'admin_ifood'     => 'iFood',
    'admin_tables'    => 'Mesas',
    'admin_couriers'  => 'Motoboy',
    'admin_caixa'     => 'Caixa',
    'admin_financeiro'=> 'Financeiro',
    'admin_notas'     => 'Notas',
    'admin_payments'  => 'Pagamentos',
    'admin_settings'  => '⚙ Config',
  ];
  $h = '<div class="subnav">';
  foreach ($items as $r=>$label){
    $on = ($active===$r) ? ' class="on"' : '';
    $h .= '<a href="?r='.$r.'"'.$on.'>'.$label.'</a>';
  }
  return $h.'</div>';
}

// Muda status do pedido + registra histórico + dispara n8n
function order_set_status($orderId,$status,$ator){
  $d = db();
  $d->prepare("UPDATE orders SET status=?, atualizado_em=datetime('now','localtime') WHERE id=?")
    ->execute([$status,$orderId]);
  $ev=$d->prepare("SELECT COUNT(*) c FROM order_status_events WHERE order_id=?"); $ev->execute([$orderId]);
  $primeiroEvento = ((int)$ev->fetch()['c'])===0;
  $d->prepare("INSERT INTO order_status_events(order_id,status,ator) VALUES(?,?,?)")
    ->execute([$orderId,$status,$ator]);
  $o = $d->prepare("SELECT * FROM orders WHERE id=?"); $o->execute([$orderId]); $o=$o->fetch();
  n8n_event('order_status_'.$status, ['pedido'=>$o]);
  customer_status_notify($o,$status,$primeiroEvento);
  return $o;
}

// ============================================================
// WHATSAPP: acompanhamento automático do pedido pro cliente
// Dispara uma mensagem pronta a cada etapa (recebido, preparo, saiu, entregue).
// ============================================================
function wa_msg_default($chave){
  $defaults=[
    'wa_msg_novo'=>"✅ *NOVO PEDIDO*
-----------------------------
Pedido {CODIGO}
▶️ *RESUMO DO PEDIDO*
{ITENS}

▶️ *Dados para entrega*

*Nome:* {NOME}
*Endereço:* {ENDERECO}
*Bairro:* {BAIRRO}
*Telefone:* {TELEFONE}

*Taxa de Entrega:* {TAXA}

🕙 *Tempo de Entrega:* aprox. {TEMPO_ENTREGA}
-------------------------------
▶️ *TOTAL* = *{TOTAL}*
------------------------------

▶️ *PAGAMENTO*

* {PAGAMENTO_STATUS}

{PAGAMENTO}",
    'wa_msg_preparo'=>"Oi {NOME}! Seu pedido {CODIGO} está sendo preparado agora 🔥",
    'wa_msg_saiu'=>"Olá {NOME}, seu pedido {CODIGO} acabou de sair para entrega, junto com mais pedidos da mesma região.
Muito obrigado pela preferência, tenha uma ótima noite 😊",
    'wa_msg_entregue'=>"Oi {NOME}! Seu pedido {CODIGO} foi entregue. Bom apetite! 😋 Volte sempre 💚",
  ];
  return $defaults[$chave] ?? '';
}
function customer_status_notify($order,$status,$primeiroEvento){
  if(!$order || empty($order['cliente_fone'])) return false;
  if(($order['tipo']??'')==='mesa') return false; // cliente está no salão, não precisa de WhatsApp
  if(setting_get('channel_whatsapp','1')!=='1') return false;
  if(!uaz_r_configured()) return false;

  $chave=null;
  if($primeiroEvento && in_array($status,['novo','aceito'],true)) $chave='wa_msg_novo';
  elseif($status==='em_preparo') $chave='wa_msg_preparo';
  elseif($status==='saiu_entrega') $chave='wa_msg_saiu';
  elseif($status==='entregue') $chave='wa_msg_entregue';
  if(!$chave) return false;

  $tpl=setting_get($chave,'')!=='' ? setting_get($chave,'') : wa_msg_default($chave);
  if(trim($tpl)==='') return false;

  $d=db();
  $it=$d->prepare("SELECT nome,qtd,preco FROM order_items WHERE order_id=?"); $it->execute([$order['id']]);
  $itens=$it->fetchAll();
  $somaItens=0; $linhasItens=[];
  foreach($itens as $i){ $somaItens+=$i['qtd']*$i['preco']; $linhasItens[]='  ('.$i['qtd'].'x) '.mb_strtoupper($i['nome']).' ('.money($i['preco']).')'; }
  $taxa=max(0,(float)$order['total']-$somaItens);
  $tempo=trim(setting_get('delivery_min','30').' A '.setting_get('delivery_max','50').'MIN');

  $subst=[
    '{NOME}'=>explode(' ',trim((string)$order['cliente_nome']))[0] ?: 'cliente',
    '{CODIGO}'=>$order['codigo'],
    '{ITENS}'=>implode("
",$linhasItens),
    '{ENDERECO}'=>trim((string)$order['endereco']),
    '{BAIRRO}'=>trim((string)$order['bairro']),
    '{TELEFONE}'=>$order['cliente_fone'],
    '{TAXA}'=>money($taxa),
    '{TOTAL}'=>money($order['total']),
    '{PAGAMENTO}'=>payment_label($order['pagamento_metodo']),
    '{PAGAMENTO_STATUS}'=>$order['pagamento_status']==='pago'?'PAGO':'PENDENTE',
    '{TEMPO_ENTREGA}'=>$tempo,
    '{LINK_ACOMPANHAR}'=>(isset($_SERVER['HTTP_HOST'])?'https://'.$_SERVER['HTTP_HOST'].strtok($_SERVER['REQUEST_URI']??'','?'):'').'?r=order_status&id='.$order['id'],
  ];
  $texto=str_replace(array_keys($subst),array_values($subst),$tpl);
  return uaz_r_send_text($order['cliente_fone'],$texto)['ok'] ?? false;
}

// ============================================================
// ZONAS DE ENTREGA NO MAPA (lógica estilo iFood)
// Cada zona é um polígono desenhado pelo dono no mapa.
//   tipo='entrega'  -> pode entregar, cobra a taxa da zona
//   tipo='bloqueio' -> NÃO entrega nessa região (tem prioridade)
// ============================================================

// Ray casting: o ponto está dentro do polígono?
function point_in_polygon($lat,$lng,$poly){
  $inside=false; $n=count($poly);
  for($i=0,$j=$n-1;$i<$n;$j=$i++){
    $yi=(float)$poly[$i][0]; $xi=(float)$poly[$i][1];
    $yj=(float)$poly[$j][0]; $xj=(float)$poly[$j][1];
    if((($yi>$lat)!=($yj>$lat)) && ($lng < ($xj-$xi)*($lat-$yi)/(($yj-$yi)?:1e-12)+$xi)) $inside=!$inside;
  }
  return $inside;
}

function delivery_zones($ativas=true){
  $sql="SELECT * FROM delivery_zones".($ativas?" WHERE ativo=1":"")." ORDER BY CASE tipo WHEN 'bloqueio' THEN 0 ELSE 1 END, taxa";
  return db()->query($sql)->fetchAll();
}

// Retorna ['ok'=>bool,'taxa'=>float,'zona'=>string,'motivo'=>string]
// Regra (igual iFood):
//  - ponto dentro de zona de bloqueio  -> não entrega, sempre.
//  - existe alguma zona de entrega     -> só entrega se o ponto cair dentro de uma delas.
//  - só existem zonas de bloqueio      -> entrega no resto, cobrando a taxa do bairro/padrão.
function frete_por_ponto($lat,$lng){
  $lat=(float)$lat; $lng=(float)$lng;
  $zonas = delivery_zones(true);
  $temEntrega=false;
  foreach($zonas as $z) if($z['tipo']!=='bloqueio') $temEntrega=true;
  if(!$zonas) return ['ok'=>false,'taxa'=>0,'zona'=>'','motivo'=>'sem_zonas'];
  if(!$lat || !$lng)
    return ['ok'=>false,'taxa'=>0,'zona'=>'','motivo'=>$temEntrega?'sem_ponto':'sem_zonas'];
  foreach($zonas as $z){
    $poly = json_decode($z['poligono'],true);
    if(!is_array($poly) || count($poly)<3) continue;
    if(point_in_polygon($lat,$lng,$poly)){
      if($z['tipo']==='bloqueio')
        return ['ok'=>false,'taxa'=>0,'zona'=>$z['nome'],'motivo'=>'Infelizmente não entregamos nesse endereço.'];
      return ['ok'=>true,'taxa'=>(float)$z['taxa'],'zona'=>$z['nome'],'motivo'=>''];
    }
  }
  // fora de todas as zonas
  if($temEntrega) return ['ok'=>false,'taxa'=>0,'zona'=>'','motivo'=>'Endereço fora da nossa área de entrega.'];
  return ['ok'=>false,'taxa'=>0,'zona'=>'','motivo'=>'sem_zonas']; // só havia bloqueio: segue no plano B
}

// Taxa final do pedido: mapa manda; bairro/padrão é o plano B
function frete_calcular($lat,$lng,$bairro){
  $r = frete_por_ponto($lat,$lng);
  if(!in_array($r['motivo'],['sem_zonas','sem_ponto'],true)) return $r;
  $taxa = (float)cfg('taxa_entrega');
  if($bairro!==''){
    $a=db()->prepare("SELECT taxa FROM delivery_areas WHERE bairro=? LIMIT 1"); $a->execute([$bairro]); $a=$a->fetch();
    if($a) $taxa=(float)$a['taxa'];
  }
  return ['ok'=>true,'taxa'=>$taxa,'zona'=>$bairro,'motivo'=>''];
}

// O garçom pode fechar comanda? (o dono decide em Config)
function garcom_pode_fechar(){ return setting_get('garcom_fecha_comanda','1')==='1'; }

// Quem está operando agora (para registrar quem fechou/recebeu)
function operador_atual(){
  _start_session();
  return $_SESSION['user_nome'] ?? ($_SESSION['garcom_nome'] ?? 'Operador');
}
