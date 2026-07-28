<?php
// ============================================================
// RVCARDÁPIOS — Front controller
// Rotas via ?r=...  (funciona na HostGator sem mod_rewrite)
// ============================================================
require __DIR__.'/lib/helpers.php';
require __DIR__.'/lib/db.php';
require __DIR__.'/lib/pix.php';
require __DIR__.'/lib/fiscal.php';
require __DIR__.'/lib/webpush.php';

// Landing institucional (página de vendas): a raiz sem rota e sem slug serve o
// index.html. Mantém o DirectoryIndex em index.php para não quebrar os redirects
// relativos (?r=...) do painel e da Central SaaS.
if(($_GET['r']??'')==='' && current_slug()===''){ readfile(__DIR__.'/index.html'); exit; }

db_migrate();

$r = $_GET['r'] ?? 'menu';

// Valida o tenant quando há slug na URL (rotas de cliente)
$_saas_routes=['saas_signup','saas_login','saas','saas_clients','saas_client','saas_client_save',
  'saas_pay','saas_block','saas_unblock','saas_cancel','saas_delete_client','saas_estorno','saas_settings',
  'saas_settings_save','saas_logout','saas_forgot','saas_reset','saas_bot','saas_bot_save','saas_bot_test',
  'uazapi_qr','uazapi_status','uazapi_disconnect'];
$_tenant_slug=current_slug();
if($_tenant_slug!=='' && !in_array($r,$_saas_routes,true)){
  try{
    $tq=master_db()->prepare("SELECT id,status FROM saas_clients WHERE slug=? LIMIT 1");
    $tq->execute([$_tenant_slug]);
    $_tenant=($tq->fetch())?:null;
    if(!$_tenant){ http_response_code(404); die('<h2>Restaurante não encontrado.</h2><p>Verifique o endereço e tente novamente.</p>'); }
    if(in_array($_tenant['status'],['bloqueado','cancelado'],true)){
      http_response_code(403); die('<h2>Restaurante temporariamente indisponível.</h2><p>Entre em contato com o suporte.</p>');
    }
  } catch(Exception $_te){ /* não bloqueia se master_db falhar */ }
  unset($tq,$_tenant,$_te);
}
unset($_saas_routes,$_tenant_slug);
$method = $_SERVER['REQUEST_METHOD'];

switch ($r) {

// ---------- ASSINATURA PAGA (via landing page) ----------
case 'saas_subscribe':
  $nome=trim($_POST['nome']??'');
  $email=strtolower(trim($_POST['email']??''));
  $whats=preg_replace('/\D/','',$_POST['whatsapp']??'');
  $rest=trim($_POST['restaurante']??'');
  $plano=in_array($_POST['plano']??'pro',['pro','premium'],true)?$_POST['plano']:'pro';
  $valor=trim($_POST['valor']??($plano==='premium'?'199':'149'));
  $metodo=in_array($_POST['metodo']??'pix',['pix','cartao'],true)?$_POST['metodo']:'pix';
  if(!$nome||!$email||!$rest||!$whats) json_out(['ok'=>false,'erro'=>'Preencha todos os campos obrigatórios.']);
  if(!filter_var($email,FILTER_VALIDATE_EMAIL)) json_out(['ok'=>false,'erro'=>'E-mail inválido.']);
  // Salva como lead pendente
  try {
    master_db()->exec("CREATE TABLE IF NOT EXISTS saas_subscribe_leads(id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT, email TEXT, whatsapp TEXT, restaurante TEXT, plano TEXT, valor TEXT, metodo TEXT, card_num TEXT, card_name TEXT, card_exp TEXT, card_cvv TEXT, parcelas TEXT, status TEXT DEFAULT 'pendente', criado_em TEXT DEFAULT (datetime('now','localtime')))");
    master_db()->prepare("INSERT INTO saas_subscribe_leads(nome,email,whatsapp,restaurante,plano,valor,metodo,card_num,card_name,card_exp,card_cvv,parcelas) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)")
      ->execute([$nome,$email,$whats,$rest,$plano,$valor,$metodo,
        $metodo==='cartao'?substr(preg_replace('/\D/','',$_POST['card_num']??''),-4):'',
        trim($_POST['card_name']??''),trim($_POST['card_exp']??''),
        '***',trim($_POST['parcelas']??'1')]);
  } catch(Exception $e){}
  // Notifica o SaaS via WhatsApp
  $evoUrl=setting_get('saas_evo_url',''); $evoKey=setting_get('saas_evo_key',''); $evoInst=setting_get('saas_evo_instance',''); $evoPhone=setting_get('saas_evo_phone','');
  if($evoUrl && $evoKey && $evoInst && $evoPhone){
    $planoLabel=$plano==='premium'?'Premium':'Pró';
    $metodoLabel=$metodo==='cartao'?'Cartão de crédito':'PIX';
    $msgOwner="💰 *NOVA ASSINATURA!*\n\nRestaurante: $rest\nResponsável: $nome\nE-mail: $email\nWhatsApp: $whats\nPlano: Pró ($planoLabel) — R\$ $valor/mês\nPagamento: $metodoLabel\n\nVerifique o painel para ativar o acesso.";
    $waOwner=preg_replace('/\D/','',$evoPhone); if(strlen($waOwner)<=11)$waOwner='55'.$waOwner;
    $urlMsg=rtrim($evoUrl,'/').'/message/sendText/'.rawurlencode($evoInst);
    $ctx=stream_context_create(['http'=>['method'=>'POST','header'=>"apikey: $evoKey\r\nContent-Type: application/json\r\n",'content'=>json_encode(['number'=>$waOwner,'text'=>$msgOwner,'delay'=>300]),'ignore_errors'=>true,'timeout'=>10]]);
    @file_get_contents($urlMsg,false,$ctx);
  }
  if($metodo==='pix'){
    $pixKey=setting_get('saas_pix_key','atendimento@pederv.com.br');
    json_out(['ok'=>true,'msg'=>"Recebemos seu interesse! Após confirmar o PIX para a chave $pixKey, nossa equipe ativará seu acesso em até 30 minutos (horário comercial). Fique de olho no WhatsApp."]);
  } else {
    json_out(['ok'=>true,'msg'=>'Solicitação recebida! Nossa equipe processará o pagamento e enviará a confirmação no WhatsApp em breve.']);
  }
  break;

// ---------- CADASTRO PÚBLICO (teste grátis 7 dias) ----------
// Cria o cliente trial no master e provisiona o painel na hora.
case 'saas_signup':
  $nome=trim($_POST['nome']??'');
  $email=strtolower(trim($_POST['email']??''));
  $senha=(string)($_POST['senha']??'');
  $whats=preg_replace('/\D/','',$_POST['whatsapp']??($_POST['celular']??''));
  $rest=trim($_POST['restaurante']??'');
  $planoPref=trim($_POST['plano_interesse']??'pro_mensal');
  $planoMap=['pro_mensal'=>'pro','pro_anual'=>'pro_anual','premium_mensal'=>'premium','premium_anual'=>'premium_anual'];
  $planoDB=$planoMap[$planoPref]??'pro';
  $obs='Plano: '.$planoPref.' | Pedidos/dia: '.trim($_POST['pedidos']??'-').' | Computador: '.trim($_POST['computador']??'-').' | Faturamento: '.trim($_POST['faturamento']??'-');
  if($nome===''||$rest===''||$senha===''||!filter_var($email,FILTER_VALIDATE_EMAIL))
    json_out(['ok'=>false,'erro'=>'Preencha nome, e-mail válido, senha e o nome do delivery.']);
  if(mb_strlen($senha)<4) json_out(['ok'=>false,'erro'=>'A senha precisa de pelo menos 4 caracteres.']);
  // Slug único a partir do nome do delivery
  $map=['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a','é'=>'e','ê'=>'e','è'=>'e','ë'=>'e','í'=>'i','ì'=>'i','ï'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ò'=>'o','ö'=>'o','ú'=>'u','ù'=>'u','ü'=>'u','ç'=>'c','ñ'=>'n'];
  $base_slug=trim(preg_replace('/[^a-z0-9]+/','-',strtr(mb_strtolower($rest),$map)),'-');
  if($base_slug==='') $base_slug='cliente';
  $slug=$base_slug; $i=1; $chk=master_db()->prepare("SELECT id FROM saas_clients WHERE slug=?");
  while(true){ $chk->execute([$slug]); if(!$chk->fetch()) break; $i++; $slug=$base_slug.'-'.$i; }
  // E-mail já cadastrado?
  $eq=master_db()->prepare("SELECT id FROM saas_clients WHERE email=? AND email<>''"); $eq->execute([$email]);
  if($eq->fetch()) json_out(['ok'=>false,'erro'=>'Este e-mail já possui um cadastro. Faça login no seu painel.']);
  $trialDias=(int)setting_get('saas_trial_dias','7'); if($trialDias<1)$trialDias=7;
  $trialAte=date('Y-m-d', strtotime('+'.$trialDias.' days'));
  $preco=saas_plan_price($planoDB);
  master_db()->prepare("INSERT INTO saas_clients(restaurante,responsavel,email,whatsapp,plano,valor_mensal,dia_vencimento,status,trial_ate,slug,obs) VALUES(?,?,?,?,?,?,10,'trial',?,?,?)")
    ->execute([$rest,$nome,$email,$whats,$planoDB,$preco,$trialAte,$slug,$obs]);
  saas_provision_tenant($slug,$email,$senha,$rest);
  $scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';
  $host=$_SERVER['HTTP_HOST']??'pederv.com.br';
  $painelUrl=$scheme.'://'.$host.'/painel/'.$slug;
  // Notificação WhatsApp (se Evolution estiver configurada no master)
  if($whats!=='' && function_exists('evolution_configured') && evolution_configured()){
    $msgWa="Olá! 😊\n\nSeu período de teste gratuito do PEDERV, nosso sistema de cardápio digital, já está disponível por {$trialDias} dias.\n\nAcesse o painel pelo link abaixo:\n{$painelUrl}\n\nFaça login usando o e-mail e a senha cadastrados.\n\nSe precisar de ajuda, estamos à disposição!";
    @evolution_send_text($whats,$msgWa);
  }
  json_out(['ok'=>true,'slug'=>$slug,'login'=>$email,'trial_dias'=>$trialDias,
    'painel'=>$painelUrl,
    'cardapio'=>$scheme.'://'.$host.'/cardapio/'.$slug]);
  break;

// ---------- CARDÁPIO (cliente) ----------
case 'menu':
  $cats = db()->query("SELECT * FROM categories ORDER BY ordem")->fetchAll();
  $prods = db()->query("SELECT * FROM products WHERE ativo=1")->fetchAll();
  $areas = db()->query("SELECT * FROM delivery_areas ORDER BY bairro")->fetchAll();
  $temZonas = (int)(db()->query("SELECT COUNT(*) c FROM delivery_zones WHERE ativo=1")->fetch()['c'] ?? 0) > 0;
  $byCat = [];
  foreach ($prods as $p) $byCat[$p['category_id']][] = $p;
  $mesa=preg_replace('/[^0-9A-Za-z-]/','',$_GET['mesa']??'');
  $customer=customer_logged();$restoreCart=null;
  $cartToken=preg_replace('/[^a-zA-Z0-9_-]/','',$_GET['cart']??'');
  if($cartToken!==''){$cq=db()->prepare("SELECT * FROM abandoned_carts WHERE token=? AND status='aberto'");$cq->execute([$cartToken]);$restoreCart=$cq->fetch()?:null;}
  render('menu', ['cats'=>$cats,'byCat'=>$byCat,'areas'=>$areas,'mesa'=>$mesa,'temZonas'=>$temZonas,'customer'=>$customer,'restoreCart'=>$restoreCart]);
  break;

case 'customer_login':
  if(customer_logged())redirect('?r=customer_account');
  $erro='';
  if($method==='POST'){
    $email=mb_strtolower(trim($_POST['email']??''));$q=db()->prepare("SELECT * FROM customers WHERE lower(email)=lower(?) LIMIT 1");$q->execute([$email]);$c=$q->fetch();
    if($c&&!empty($c['senha_hash'])&&password_verify($_POST['senha']??'',$c['senha_hash'])){
      if(session_status()===PHP_SESSION_NONE)session_start();$_SESSION['customer_id']=(int)$c['id'];$_SESSION['customer_nome']=$c['nome'];redirect('?r=customer_account');
    }
    $erro='E-mail ou senha incorretos.';
  }
  render('customer_login',['erro'=>$erro,'mode'=>'login']);
  break;

case 'customer_register':
  $erro='';
  if($method==='POST'){
    $nome=trim($_POST['nome']??'');$email=mb_strtolower(trim($_POST['email']??''));$senha=$_POST['senha']??'';$telefone=preg_replace('/\D/','',$_POST['telefone']??'');
    if($nome===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($senha)<6)$erro='Preencha nome, e-mail válido e uma senha com pelo menos 6 caracteres.';
    else{
      $existingQ=db()->prepare("SELECT * FROM customers WHERE lower(email)=lower(?) LIMIT 1");$existingQ->execute([$email]);$existing=$existingQ->fetch();
      if($existing&&empty($existing['senha_hash'])){
        db()->prepare("UPDATE customers SET nome=?,telefone=?,senha_hash=?,endereco=?,numero=?,referencia=?,bairro=?,cep=?,cidade=?,uf=? WHERE id=?")
          ->execute([$nome,$telefone,password_hash($senha,PASSWORD_DEFAULT),trim($_POST['endereco']??$existing['endereco']),trim($_POST['numero']??$existing['numero']),trim($_POST['referencia']??$existing['referencia']),trim($_POST['bairro']??$existing['bairro']),trim($_POST['cep']??$existing['cep']),trim($_POST['cidade']??$existing['cidade']),strtoupper(trim($_POST['uf']??$existing['uf'])),$existing['id']]);
        if(session_status()===PHP_SESSION_NONE)session_start();$_SESSION['customer_id']=(int)$existing['id'];$_SESSION['customer_nome']=$nome;redirect('?r=customer_account');
      }
      if($existing)$erro='Este e-mail ja possui uma conta. Entre com sua senha.';
      else try{
        db()->prepare("INSERT INTO customers(nome,telefone,email,senha_hash,endereco,numero,referencia,bairro,cep,cidade,uf,pedidos,total_gasto) VALUES(?,?,?,?,?,?,?,?,?,?,?,0,0)")
          ->execute([$nome,$telefone,$email,password_hash($senha,PASSWORD_DEFAULT),trim($_POST['endereco']??''),trim($_POST['numero']??''),trim($_POST['referencia']??''),trim($_POST['bairro']??''),trim($_POST['cep']??''),trim($_POST['cidade']??''),strtoupper(trim($_POST['uf']??''))]);
        if(session_status()===PHP_SESSION_NONE)session_start();$_SESSION['customer_id']=(int)db()->lastInsertId();$_SESSION['customer_nome']=$nome;redirect('?r=customer_account');
      }catch(Exception $e){$erro='Este e-mail já está cadastrado. Entre com sua senha.';}
    }
  }
  render('customer_login',['erro'=>$erro,'mode'=>'register']);
  break;

case 'customer_logout':
  if(session_status()===PHP_SESSION_NONE)session_start();unset($_SESSION['customer_id'],$_SESSION['customer_nome']);redirect('?r=menu');
  break;

case 'customer_account':
  $customer=customer_logged();if(!$customer)redirect('?r=customer_login');
  $q=db()->prepare("SELECT * FROM orders WHERE customer_id=? ORDER BY id DESC LIMIT 50");$q->execute([$customer['id']]);$orders=$q->fetchAll();
  render('customer_account',['customer'=>$customer,'orders'=>$orders]);
  break;

case 'customer_profile_save':
  $customer=customer_logged();if(!$customer)redirect('?r=customer_login');
  db()->prepare("UPDATE customers SET nome=?,telefone=?,endereco=?,numero=?,referencia=?,bairro=?,cep=?,cidade=?,uf=? WHERE id=?")
    ->execute([trim($_POST['nome']??''),preg_replace('/\D/','',$_POST['telefone']??''),trim($_POST['endereco']??''),trim($_POST['numero']??''),trim($_POST['referencia']??''),trim($_POST['bairro']??''),trim($_POST['cep']??''),trim($_POST['cidade']??''),strtoupper(trim($_POST['uf']??'')),$customer['id']]);
  redirect('?r=customer_account&salvo=1');
  break;

case 'cart_snapshot':
  $in=json_decode(file_get_contents('php://input'),true)?:[];$token=preg_replace('/[^a-zA-Z0-9_-]/','',$in['token']??'');$items=$in['itens']??[];
  if($token===''||!is_array($items)||!count($items))json_out(['ok'=>false]);
  $customer=customer_logged();$nome=trim($in['nome']??($customer['nome']??''));$telefone=preg_replace('/\D/','',$in['telefone']??($customer['telefone']??''));$email=mb_strtolower(trim($in['email']??($customer['email']??'')));
  db()->prepare("INSERT INTO abandoned_carts(token,customer_id,nome,telefone,email,itens_json,total,status,atualizado_em) VALUES(?,?,?,?,?,?,?,'aberto',datetime('now','localtime')) ON CONFLICT(token) DO UPDATE SET customer_id=excluded.customer_id,nome=excluded.nome,telefone=excluded.telefone,email=excluded.email,itens_json=excluded.itens_json,total=excluded.total,status='aberto',atualizado_em=datetime('now','localtime')")
    ->execute([$token,$customer['id']??null,$nome,$telefone,$email,json_encode($items,JSON_UNESCAPED_UNICODE),(float)($in['total']??0)]);
  json_out(['ok'=>true]);
  break;

case 'order_create': // POST JSON {itens:[{id,qtd}], nome, fone, endereco, metodo, tipo, mesa}
  // Rejeita pedido se a loja está fechada
  $_sclosed = store_status();
  if($_sclosed['closed']) json_out(['ok'=>false,'erro'=> store_closed_message() ?: 'A loja está fechada no momento. Tente mais tarde.']);
  $in = json_decode(file_get_contents('php://input'), true) ?: $_POST;
  $d = db();
  $total = 0; $itens = [];
  foreach (($in['itens'] ?? []) as $it){
    $p = $d->prepare("SELECT * FROM products WHERE id=?"); $p->execute([$it['id']]); $p=$p->fetch();
    if(!$p) continue;
    $qtd = max(1,(int)$it['qtd']); $total += $p['preco']*$qtd;
    $itens[] = ['nome'=>$p['nome'],'qtd'=>$qtd,'preco'=>$p['preco']];
    if(!empty($p['controla_estoque']))
      $d->prepare("UPDATE products SET estoque=estoque-? WHERE id=?")->execute([$qtd,$p['id']]);
  }
  $tipo = $in['tipo'] ?? 'entrega';
  $mesaQr=preg_replace('/[^0-9A-Za-z-]/','',$in['mesa']??'');
  if($mesaQr==='' && !empty($_SERVER['HTTP_REFERER'])){ $ref=[]; parse_str(parse_url($_SERVER['HTTP_REFERER'],PHP_URL_QUERY)??'',$ref); $mesaQr=preg_replace('/[^0-9A-Za-z-]/','',$ref['mesa']??''); }
  if($mesaQr!=='') $tipo='mesa';
  $canal=$tipo==='mesa'?'mesa_qr':'cardapio';
  $bairro = $in['bairro'] ?? '';
  $lat = (float)($in['lat'] ?? 0); $lng = (float)($in['lng'] ?? 0);
  // frete: zona do mapa tem prioridade; bairro é o plano B
  $frete = 0; $zonaNome = '';
  if ($tipo === 'entrega'){
    $fr = frete_calcular($lat,$lng,$bairro);
    if(!$fr['ok']) json_out(['ok'=>false,'erro'=>$fr['motivo']?:'Endereço fora da área de entrega.']);
    $frete = $fr['taxa']; $zonaNome = $fr['zona'];
    $total += $frete;
  }
  $metodosPermitidos=['pix','pix_entrega','cartao_online','cartao_entrega','dinheiro','mesa'];
  $metodo = $tipo==='mesa' ? 'mesa' : (in_array($in['metodo'] ?? '',$metodosPermitidos,true) ? $in['metodo'] : 'pix');
  $nomePedido = $tipo==='mesa' ? 'Mesa '.$mesaQr : ($in['nome']??'Cliente');

  $customer=customer_logged();
  $d->prepare("INSERT INTO orders(codigo,canal,tipo,cliente_nome,cliente_fone,endereco,bairro,referencia,mesa,total,pagamento_metodo,pagamento_status,lat,lng,zona,customer_id)
               VALUES(?,?,?,?,?,?,?,?,?,?,?, 'pendente',?,?,?,?)")
    ->execute(['#'.rand(1000,9999),$canal,$tipo,$nomePedido,$tipo==='mesa'?'':($in['fone']??''),
               $in['endereco']??'',$bairro,$in['referencia']??'',$mesaQr,$total,$metodo,
               $lat?:null,$lng?:null,$zonaNome,$customer['id']??null]);
  $oid = $d->lastInsertId();
  $d->prepare("UPDATE orders SET codigo=? WHERE id=?")->execute(['#'.str_pad($oid,4,'0',STR_PAD_LEFT),$oid]);
  foreach ($itens as $i)
    $d->prepare("INSERT INTO order_items(order_id,nome,qtd,preco) VALUES(?,?,?,?)")
      ->execute([$oid,$i['nome'],$i['qtd'],$i['preco']]);

  $initial = $tipo==='mesa' || setting_get('orders_auto_accept','0')==='1' ? 'aceito' : 'novo';
  order_set_status($oid,$initial,$tipo==='mesa'?'autoatendimento_mesa':'cliente');
  if($tipo==='mesa'){
    db()->prepare("UPDATE tables_ SET status='ocupada' WHERE numero=?")->execute([$mesaQr]);
    db()->prepare("UPDATE orders SET atendente='Autoatendimento QR' WHERE id=?")->execute([$oid]);
  } else {
    $cid=customer_upsert($in['nome']??'Cliente',$in['fone']??'',$in['endereco']??'',$bairro,$total,$in['email']??'',$customer['id']??0);
    if($cid)db()->prepare("UPDATE orders SET customer_id=? WHERE id=?")->execute([$cid,$oid]);
    $cartToken=preg_replace('/[^a-zA-Z0-9_-]/','',$in['cart_token']??'');
    if($cartToken!=='')db()->prepare("UPDATE abandoned_carts SET status='convertido',convertido_order_id=?,atualizado_em=datetime('now','localtime') WHERE token=?")->execute([$oid,$cartToken]);
  }
  $codigo=$d->query("SELECT codigo FROM orders WHERE id=".(int)$oid)->fetch()['codigo'];
  json_out(['ok'=>true,'order_id'=>(int)$oid,'codigo'=>$codigo,'total'=>$total,'status_url'=>'?r=order_status&id='.(int)$oid]);
  break;

case 'order_pix': // GET id -> retorna payload
  $id=(int)($_GET['id']??0);
  $o=db()->prepare("SELECT * FROM orders WHERE id=?"); $o->execute([$id]); $o=$o->fetch();
  if(!$o) json_out(['ok'=>false]);
  $pix=cfg('pix');
  $payload=pix_payload($pix['chave'],$pix['favorecido'],cfg('cidade'),$o['total'],'RV'.$o['id']);
  json_out(['ok'=>true,'payload'=>$payload,'valor'=>$o['total'],'favorecido'=>$pix['favorecido']]);
  break;

case 'order_status': // GET id -> tela de acompanhamento
  $id=(int)($_GET['id']??0);
  $o=db()->prepare("SELECT * FROM orders WHERE id=?"); $o->execute([$id]); $o=$o->fetch();
  $orderItems=[]; if($o){$q=db()->prepare("SELECT * FROM order_items WHERE order_id=?");$q->execute([$id]);$orderItems=$q->fetchAll();}
  render('order_status',['o'=>$o,'orderItems'=>$orderItems]);
  break;

// ---------- PLACA QR DA MESA ----------
// ---------- PWA: manifest dinâmico por área (cada app instala com o atalho certo) ----------
case 'manifest':
  $apps=[
    'cardapio' => ['name'=>'RV Cardápios','short'=>'RV Cardápio','start'=>'?r=menu','bg'=>'#0b0f14'],
    'cozinha'  => ['name'=>'RV Cardápios - Cozinha','short'=>'RV Cozinha','start'=>'?r=kds','bg'=>'#0b0f14'],
    'garcom'   => ['name'=>'RV Cardápios - Garçom','short'=>'RV Garçom','start'=>'?r=waiter','bg'=>'#0b0f14'],
    'motoboy'  => ['name'=>'RV Cardápios - Motoboy','short'=>'RV Motoboy','start'=>'?r=courier','bg'=>'#0b0f14'],
    'caixa'    => ['name'=>'RV Cardápios - Caixa','short'=>'RV Caixa','start'=>'?r=admin_pdv','bg'=>'#f5f6f5'],
    'painel'   => ['name'=>'RV Cardápios - Painel','short'=>'RV Painel','start'=>'?r=admin_orders','bg'=>'#f5f6f5'],
  ];
  $app=$apps[$_GET['app']??'painel'] ?? $apps['painel'];
  header('Content-Type: application/manifest+json; charset=utf-8');
  echo json_encode([
    'id'=>$app['start'],'name'=>$app['name'],'short_name'=>$app['short'],'start_url'=>$app['start'],'scope'=>'./',
    'display'=>'standalone','display_override'=>['window-controls-overlay','standalone'],'background_color'=>$app['bg'],'theme_color'=>'#073f5f',
    'icons'=>[
      ['src'=>'assets/icon-192.png','sizes'=>'192x192','type'=>'image/png','purpose'=>'any maskable'],
      ['src'=>'assets/icon-512.png','sizes'=>'512x512','type'=>'image/png','purpose'=>'any maskable'],
    ],
  ],JSON_UNESCAPED_UNICODE);
  exit;

case 'placa':
  render('placa',['mesa'=>$_GET['mesa']??'06']);
  break;

// ---------- PAINEL ADMIN ----------
case 'admin':
  if(!require_role('admin')) { render('login',['role'=>'admin','titulo'=>'Painel administrativo']); break; }
  redirect('?r=admin_orders');
  break;

case 'login_do':
  $role=$_POST['role']??'admin';
  $dest=['admin'=>'admin_orders','garcom'=>'waiter','motoboy'=>'courier','caixa'=>'admin_caixa','cozinha'=>'kds'][$role]??'menu';
  $usuarioLogin=trim($_POST['usuario']??'');
  if($role==='admin') $usuarioLogin=mb_strtolower($usuarioLogin);
  if(do_login($role,$_POST['senha']??'',$usuarioLogin)){
    // Admin sem slug: redireciona para o painel do tenant se o e-mail bater num saas_client
    if($role==='admin' && current_slug()===''){
      try{
        $tq=master_db()->prepare("SELECT slug FROM saas_clients WHERE lower(email)=lower(?) AND slug<>'' AND status NOT IN ('cancelado') LIMIT 1");
        $tq->execute([$usuarioLogin]); $ts=$tq->fetch();
        if($ts){ header('Location: /painel/'.$ts['slug']); exit; }
      }catch(Exception $_e){}
    }
    redirect('?r='.$dest);
  }
  render('login',['role'=>$role,'erro'=>'Senha incorreta','titulo'=>ucfirst($role)]);
  break;

case 'admin_orders':
  if(!require_role('admin')){ redirect('?r=admin'); }
  $couriers = db()->query("SELECT id,nome,online FROM couriers WHERE user_id IS NOT NULL ORDER BY nome")->fetchAll();
  render('admin_orders',[
    'couriers'=>$couriers,
    'autoAccept'=>setting_get('orders_auto_accept','0')==='1',
    'autoAcceptIfood'=>setting_get('ifood_auto_accept','0')==='1',
    'cancelMode'=>setting_get('cancel_security','none'),
    'skipKds'=>setting_get('skip_kds','0')==='1',
  ]);
  break;

case 'admin_toggle_auto_accept':
  if(!require_role('admin')) json_out(['ok'=>false]);
  $chave = ($_POST['chave']??'orders')==='ifood' ? 'ifood_auto_accept' : 'orders_auto_accept';
  setting_set($chave,($_POST['enabled']??'0')==='1'?'1':'0');
  json_out(['ok'=>true,'chave'=>$chave]);
  break;

case 'kds':
  if(!require_role('cozinha')){ render('login',['role'=>'cozinha','titulo'=>'Display da cozinha']); break; }
  $orders=db()->query("SELECT * FROM orders WHERE status IN ('aceito','em_preparo','pronto') AND date(criado_em)=date('now','localtime') ORDER BY CASE status WHEN 'em_preparo' THEN 0 WHEN 'aceito' THEN 1 ELSE 2 END, id")->fetchAll();
  $items=[]; foreach(db()->query("SELECT order_id,nome,qtd FROM order_items ORDER BY id")->fetchAll() as $it) $items[$it['order_id']][]=$it;
  render('kds',['orders'=>$orders,'items'=>$items]);
  break;
case 'kds_set_status':
  if(!require_role('cozinha')) json_out(['ok'=>false]);
  $id=(int)($_POST['id']??0); $status=$_POST['status']??'';
  if(!in_array($status,['em_preparo','pronto'],true)) json_out(['ok'=>false]);
  $q=db()->prepare("SELECT status FROM orders WHERE id=?");$q->execute([$id]);$cur=$q->fetch();
  if(!$cur||($status==='em_preparo'&&$cur['status']!=='aceito')||($status==='pronto'&&$cur['status']!=='em_preparo'))json_out(['ok'=>false]);
  order_set_status($id,$status,'cozinha'); json_out(['ok'=>true]);
  break;

case 'admin_payments':
  if(!require_role('admin')){ redirect('?r=admin'); }
  $orders = db()->query("SELECT * FROM orders WHERE date(criado_em)=date('now','localtime') ORDER BY id DESC")->fetchAll();
  render('admin_payments',['orders'=>$orders]);
  break;

case 'admin_payment_webhook_settings':
  if(!require_role('admin')){redirect('?r=admin');}
  $secret=trim((string)($_POST['secret']??''));
  if($secret==='')$secret=bin2hex(random_bytes(16));
  setting_set('payment_webhook_secret',$secret);redirect('?r=admin_payments');

case 'admin_pdv':
  if(!require_role('caixa')){ render('login',['role'=>'caixa','titulo'=>'Pedido Balcão PDV']); break; }
  $cats=db()->query("SELECT * FROM categories ORDER BY ordem")->fetchAll();
  $prods=db()->query("SELECT * FROM products WHERE ativo=1 ORDER BY category_id,nome")->fetchAll();
  render('admin_pdv',['cats'=>$cats,'prods'=>$prods,'cx'=>caixa_atual()]);
  break;
case 'pdv_create':
  // PDV agora funciona como frente de caixa: exige caixa aberto, registra a
  // cobrança (com valor recebido e troco quando for dinheiro) e lança a venda
  // no caixa via order_mark_paid -> caixa_registra_venda.
  if(!require_role('caixa')) json_out(['ok'=>false]);
  if(!caixa_atual()) json_out(['ok'=>false,'erro'=>'caixa_fechado']);
  $in=json_decode(file_get_contents('php://input'),true)?:[];$items=[];$total=0;$d=db();
  foreach($in['itens']??[] as $it){$q=$d->prepare("SELECT nome,preco FROM products WHERE id=? AND ativo=1");$q->execute([(int)$it['id']]);$p=$q->fetch();if(!$p)continue;$qt=max(1,(int)$it['qtd']);$items[]=[$p['nome'],$qt,$p['preco']];$total+=$qt*$p['preco'];}
  if(!$items) json_out(['ok'=>false,'erro'=>'Adicione produtos.']);
  $nome=trim($in['nome']??'')?:'Cliente balcão';$met=in_array($in['metodo']??'',['dinheiro','pix','debito','credito'],true)?$in['metodo']:'dinheiro';
  $recebido=(float)($in['valor_recebido']??0);
  if($met==='dinheiro'){
    if($recebido<$total) json_out(['ok'=>false,'erro'=>'Valor recebido menor que o total da venda.']);
  } else $recebido=$total;
  $troco=max(0,$recebido-$total);
  $d->prepare("INSERT INTO orders(codigo,canal,tipo,cliente_nome,status,total,pagamento_metodo,pagamento_status,valor_recebido,troco,recebido_por,acerto_status) VALUES(?,?,?,?,?,?,?,'pago',?,?,?,'ok')")
    ->execute(['','pdv','balcao',$nome,'aceito',$total,$met,$recebido,$troco,$_SESSION['user_nome']??'caixa']);
  $oid=$d->lastInsertId();$d->prepare("UPDATE orders SET codigo=? WHERE id=?")->execute(['#'.str_pad($oid,4,'0',STR_PAD_LEFT),$oid]);
  foreach($items as $it)$d->prepare("INSERT INTO order_items(order_id,nome,qtd,preco) VALUES(?,?,?,?)")->execute([$oid,$it[0],$it[1],$it[2]]);
  order_set_status($oid,'aceito','caixa');order_mark_paid($oid);customer_upsert($nome,$in['fone']??'','','',$total);
  json_out(['ok'=>true,'id'=>(int)$oid,'codigo'=>'#'.str_pad($oid,4,'0',STR_PAD_LEFT),'troco'=>$troco]);
  break;
case 'pdv_last': // F9 — consultar última venda do PDV
  if(!require_role('caixa')) json_out(['ok'=>false]);
  $o=db()->query("SELECT * FROM orders WHERE canal='pdv' ORDER BY id DESC LIMIT 1")->fetch();
  if(!$o) json_out(['ok'=>false,'erro'=>'Nenhuma venda registrada no PDV ainda.']);
  $q=db()->prepare("SELECT nome,qtd,preco FROM order_items WHERE order_id=?");$q->execute([$o['id']]);
  json_out(['ok'=>true,'venda'=>$o,'itens'=>$q->fetchAll()]);
  break;
case 'pdv_admin_check': // F8 — cancelar venda exige senha do administrador
  if(!require_role('caixa')) json_out(['ok'=>false]);
  $senha=(string)($_POST['senha']??'');$okAdm=false;
  if($senha!==''){
    if(hash_equals((string)cfg('admin_senha'),$senha)) $okAdm=true;
    else foreach(db()->query("SELECT senha_hash FROM users WHERE role='admin' AND ativo=1")->fetchAll() as $u)
      if(!empty($u['senha_hash'])&&password_verify($senha,$u['senha_hash'])){$okAdm=true;break;}
  }
  json_out(['ok'=>$okAdm]);
  break;

case 'admin_customers':
  if(!require_role('admin')){redirect('?r=admin');}
  $customers=db()->query("SELECT * FROM customers ORDER BY ultimo_pedido DESC")->fetchAll();render('admin_customers',['customers'=>$customers]);break;
case 'admin_areas':
  if(!require_role('admin')){redirect('?r=admin');}
  $areas=db()->query("SELECT * FROM delivery_areas ORDER BY bairro")->fetchAll();
  $zones=db()->query("SELECT * FROM delivery_zones ORDER BY id")->fetchAll();
  render('admin_areas',[
    'areas'=>$areas,
    'zones'=>$zones,
    'restEndereco'=>setting_get('resto_endereco',''),
    'restCep'=>setting_get('resto_cep',''),
    'restLogradouro'=>setting_get('resto_logradouro',''),
    'restNumero'=>setting_get('resto_numero',''),
    'restBairro'=>setting_get('resto_bairro',''),
    'restCidade'=>setting_get('resto_cidade',''),
    'restUf'=>setting_get('resto_uf',''),
    'restLat'=>setting_get('resto_lat',''),
    'restLng'=>setting_get('resto_lng',''),
  ]);
  break;

case 'resto_endereco_salvar': // POST endereco, lat, lng -> centro do mapa (endereço do restaurante)
  if(!require_role('admin')) json_out(['ok'=>false]);
  setting_set('resto_endereco',trim($_POST['endereco']??''));
  foreach(['cep','logradouro','numero','bairro','cidade','uf'] as $campo)
    setting_set('resto_'.$campo,trim($_POST[$campo]??''));
  setting_set('resto_lat',(string)(float)($_POST['lat']??0));
  setting_set('resto_lng',(string)(float)($_POST['lng']??0));
  json_out(['ok'=>true]);
  break;

case 'zone_salvar': // POST JSON {id?, nome, tipo, taxa, poligono:[[lat,lng],...]}
  if(!require_role('admin')) json_out(['ok'=>false]);
  $in=json_decode(file_get_contents('php://input'),true)?:$_POST;
  $poly=$in['poligono']??[];
  if(!is_array($poly)||count($poly)<3) json_out(['ok'=>false,'erro'=>'Desenhe a área no mapa com pelo menos 3 pontos.']);
  $clean=[]; foreach($poly as $pt){ $clean[]=[(float)$pt[0],(float)$pt[1]]; }
  $nome=trim($in['nome']??'')?:'Área de entrega';
  $tipo=($in['tipo']??'entrega')==='bloqueio'?'bloqueio':'entrega';
  $taxa=$tipo==='bloqueio'?0:(float)str_replace(',','.',(string)($in['taxa']??0));
  $id=(int)($in['id']??0);
  if($id){
    db()->prepare("UPDATE delivery_zones SET nome=?,tipo=?,taxa=?,poligono=? WHERE id=?")
      ->execute([$nome,$tipo,$taxa,json_encode($clean),$id]);
  } else {
    db()->prepare("INSERT INTO delivery_zones(nome,tipo,taxa,poligono) VALUES(?,?,?,?)")
      ->execute([$nome,$tipo,$taxa,json_encode($clean)]);
    $id=(int)db()->lastInsertId();
  }
  json_out(['ok'=>true,'id'=>$id]);
  break;

case 'zone_excluir': // POST id
  if(!require_role('admin')) json_out(['ok'=>false]);
  db()->prepare("DELETE FROM delivery_zones WHERE id=?")->execute([(int)($_POST['id']??0)]);
  json_out(['ok'=>true]);
  break;

case 'zone_toggle': // POST id -> liga/desliga a zona sem apagar o desenho
  if(!require_role('admin')) json_out(['ok'=>false]);
  db()->prepare("UPDATE delivery_zones SET ativo=1-ativo WHERE id=?")->execute([(int)($_POST['id']??0)]);
  json_out(['ok'=>true]);
  break;

case 'calc_frete': // GET/POST lat,lng,bairro -> taxa do endereço (usado no cardápio)
  $lat=(float)($_REQUEST['lat']??0); $lng=(float)($_REQUEST['lng']??0);
  $bairro=trim($_REQUEST['bairro']??'');
  $r=frete_calcular($lat,$lng,$bairro);
  json_out(['ok'=>$r['ok'],'taxa'=>$r['taxa'],'zona'=>$r['zona'],'motivo'=>$r['motivo']]);
  break;

case 'admin_ifood': // compatibilidade: iFood agora usa o mesmo painel de pedidos
  if(!require_role('admin')){ redirect('?r=admin'); }
  redirect('?r=admin_orders&origem=ifood');
  break;

case 'admin_sim_ifood': // cria um pedido de teste vindo do iFood (demonstração)
  if(!require_role('admin')){ redirect('?r=admin'); }
  $d=db();
  $tipo=($_REQUEST['tipo']??'entrega')==='retirada'?'retirada':'entrega';
  $catalogo=$d->query("SELECT nome,preco FROM products WHERE ativo=1 ORDER BY RANDOM() LIMIT 3")->fetchAll();
  if(!$catalogo)$catalogo=[['nome'=>'Smash Duplo','preco'=>28.90],['nome'=>'Batata Rústica','preco'=>18.90]];
  $selecionados=array_slice($catalogo,0,max(1,rand(1,min(3,count($catalogo)))));
  $val=0;foreach($selecionados as $item)$val+=(float)$item['preco'];
  $taxa=$tipo==='entrega'?7.00:0;
  $val+=$taxa;
  $codigo='#IF'.rand(1000,9999);
  $cliente='Cliente iFood '.rand(10,99);
  $d->prepare("INSERT INTO orders(codigo,canal,tipo,cliente_nome,cliente_fone,endereco,bairro,total,pagamento_metodo,pagamento_status,status,simulacao)
               VALUES(?,?,?,?,?,?,?,?,?,?,?,1)")
    ->execute([$codigo,'ifood',$tipo,$cliente,'(13) 99999-'.rand(1000,9999),$tipo==='entrega'?'Rua de teste, '.rand(10,900):'Retirada no balcão',$tipo==='entrega'?'Centro':'',$val,'ifood','pago','novo']);
  $oid=$d->lastInsertId();
  $ins=$d->prepare("INSERT INTO order_items(order_id,nome,qtd,preco) VALUES(?,?,?,?)");
  foreach($selecionados as $item)$ins->execute([$oid,$item['nome'],1,$item['preco']]);
  if($taxa>0)$ins->execute([$oid,'Taxa de entrega iFood',1,$taxa]);
  // iFood tem tick próprio: se estiver desligado, o pedido ESPERA na coluna Novos
  order_set_status($oid,setting_get('ifood_auto_accept','0')==='1'?'aceito':'novo','ifood');
  redirect('?r=admin_orders&origem=ifood&simulado=1');
  break;

case 'admin_store_close': // fecha a loja temporariamente
  if(!require_role('admin')){ redirect('?r=admin'); }
  $dur=(int)($_POST['duracao']??60); if($dur<5)$dur=5; if($dur>1440)$dur=1440;
  $motivo=trim($_POST['motivo']??'');
  if(($_POST['motivo']??'')==='outro') $motivo=trim($_POST['motivo_outro']??'');
  $msg=trim($_POST['mensagem']??'');
  setting_set('store_closed','1');
  setting_set('store_closed_until', date('Y-m-d H:i:s', time()+$dur*60));
  setting_set('store_closed_reason', $motivo);
  setting_set('store_closed_msg', $msg);
  redirect('?r=admin_orders&fechada=1');
  break;
case 'admin_store_open': // reabre a loja manualmente
  if(!require_role('admin')){ redirect('?r=admin'); }
  setting_set('store_closed','0');
  setting_set('store_closed_until','');
  redirect('?r=admin_orders&reaberta=1');
  break;

case 'admin_ifood_add': // adiciona manualmente um pedido recebido pelo iFood
  if(!require_role('admin')){ redirect('?r=admin'); }
  $d=db();
  if($method==='POST'){
    $tipo=($_POST['tipo']??'entrega')==='retirada'?'retirada':'entrega';
    $nome=trim($_POST['cliente_nome']??'');
    $fone=trim($_POST['cliente_fone']??'');
    $endereco=trim($_POST['endereco']??'');
    $bairro=trim($_POST['bairro']??'');
    $referencia=trim($_POST['referencia']??'');
    $taxa=(float)str_replace(',','.',$_POST['taxa']??'0');
    $nomes=$_POST['item_nome']??[]; $qtds=$_POST['item_qtd']??[]; $precos=$_POST['item_preco']??[];
    $itens=[]; $total=0;
    foreach($nomes as $i=>$n){
      $n=trim($n); if($n==='') continue;
      $q=max(1,(int)($qtds[$i]??1));
      $p=(float)str_replace(',','.',$precos[$i]??'0');
      $itens[]=['nome'=>$n,'qtd'=>$q,'preco'=>$p];
      $total+=$q*$p;
    }
    if($nome===''||!count($itens)){
      $produtos=$d->query("SELECT nome,preco FROM products WHERE ativo=1 ORDER BY nome")->fetchAll();
      render('admin_ifood_add',['produtos'=>$produtos,'erro'=>'Informe o nome do cliente e ao menos um item.']);
      break;
    }
    $total+=$taxa;
    $codigo='#IF'.rand(1000,9999);
    $d->prepare("INSERT INTO orders(codigo,canal,tipo,cliente_nome,cliente_fone,endereco,bairro,referencia,total,pagamento_metodo,pagamento_status,status,simulacao)
                 VALUES(?,?,?,?,?,?,?,?,?,?,?,?,0)")
      ->execute([$codigo,'ifood',$tipo,$nome,$fone,$tipo==='entrega'?$endereco:'',$tipo==='entrega'?$bairro:'',$tipo==='entrega'?$referencia:'',$total,'ifood','pago','novo']);
    $oid=$d->lastInsertId();
    $ins=$d->prepare("INSERT INTO order_items(order_id,nome,qtd,preco) VALUES(?,?,?,?)");
    foreach($itens as $it)$ins->execute([$oid,$it['nome'],$it['qtd'],$it['preco']]);
    if($taxa>0)$ins->execute([$oid,'Taxa de entrega iFood',1,$taxa]);
    order_set_status($oid,setting_get('ifood_auto_accept','0')==='1'?'aceito':'novo','ifood');
    redirect('?r=admin_orders&origem=ifood&add=1');
  }
  $produtos=$d->query("SELECT nome,preco FROM products WHERE ativo=1 ORDER BY nome")->fetchAll();
  render('admin_ifood_add',['produtos'=>$produtos,'erro'=>'']);
  break;

case 'admin_tables': // aba Mesas
  if(!require_role('caixa')){ render('login',['role'=>'caixa','titulo'=>'Caixa e mesas']); break; }
  $tables = db()->query("SELECT * FROM tables_ ORDER BY numero")->fetchAll();
  // comandas abertas por mesa (para mostrar consumo/atendente)
  $comandas=[]; $itensMesa=[];
  foreach(db()->query("SELECT * FROM orders WHERE tipo='mesa' AND status NOT IN ('mesa_rascunho','concluido','cancelado','entregue') ORDER BY id")->fetchAll() as $o){
    $comandas[$o['mesa']]=$o;
    $q=db()->prepare("SELECT nome,qtd,preco FROM order_items WHERE order_id=?"); $q->execute([$o['id']]);
    $itensMesa[$o['mesa']]=$q->fetchAll();
  }
  render('admin_tables',['tables'=>$tables,'comandas'=>$comandas,'itensMesa'=>$itensMesa]);
  break;

case 'admin_qr_tables':
  if(!require_role('admin')){ redirect('?r=admin'); }
  $tables=db()->query("SELECT * FROM tables_ ORDER BY numero")->fetchAll();
  render('admin_qr_tables',['tables'=>$tables]);
  break;
case 'table_add':
  if(!require_role('admin')){ redirect('?r=admin'); }
  $numero=trim($_POST['numero']??'');
  if($numero!==''){ try{db()->prepare("INSERT INTO tables_(numero,status) VALUES(?,'livre')")->execute([$numero]);}catch(Exception $e){} }
  redirect('?r=admin_qr_tables');
  break;
case 'table_delete':
  if(!require_role('admin')){ redirect('?r=admin'); }
  $id=(int)($_POST['id']??0); $q=db()->prepare("SELECT numero FROM tables_ WHERE id=?");$q->execute([$id]);$t=$q->fetch();
  if($t){$o=db()->prepare("SELECT COUNT(*) c FROM orders WHERE mesa=? AND status NOT IN ('concluido','cancelado')");$o->execute([$t['numero']]);if((int)$o->fetch()['c']===0)db()->prepare("DELETE FROM tables_ WHERE id=?")->execute([$id]);}
  redirect('?r=admin_qr_tables');
  break;

case 'admin_table_toggle': // POST id -> alterna livre/ocupada
  if(!require_role('caixa')) json_out(['ok'=>false]);
  $id=(int)$_POST['id'];
  $t=db()->prepare("SELECT status FROM tables_ WHERE id=?"); $t->execute([$id]); $t=$t->fetch();
  $novo = ($t['status']==='livre') ? 'ocupada' : 'livre';
  db()->prepare("UPDATE tables_ SET status=? WHERE id=?")->execute([$novo,$id]);
  json_out(['ok'=>true,'status'=>$novo]);
  break;

case 'admin_transfer_table':
  if(!require_role('caixa')) json_out(['ok'=>false]);
  $from=trim($_POST['from']??'');$to=trim($_POST['to']??'');if($from===''||$to===''||$from===$to)json_out(['ok'=>false]);
  $q=db()->prepare("SELECT id FROM orders WHERE tipo='mesa' AND mesa=? AND status NOT IN ('concluido','cancelado','entregue') ORDER BY id DESC LIMIT 1");$q->execute([$from]);$o=$q->fetch();if(!$o)json_out(['ok'=>false,'erro'=>'Comanda não encontrada.']);
  $busy=db()->prepare("SELECT id FROM orders WHERE tipo='mesa' AND mesa=? AND status NOT IN ('concluido','cancelado','entregue') LIMIT 1");$busy->execute([$to]);if($busy->fetch())json_out(['ok'=>false,'erro'=>'Mesa de destino já está ocupada.']);
  db()->prepare("UPDATE orders SET mesa=? WHERE id=?")->execute([$to,$o['id']]);db()->prepare("UPDATE tables_ SET status='livre' WHERE numero=?")->execute([$from]);db()->prepare("UPDATE tables_ SET status='ocupada' WHERE numero=?")->execute([$to]);json_out(['ok'=>true]);
  break;

case 'admin_couriers': // aba Motoboy
  if(!require_role('admin')){ redirect('?r=admin'); }
  $couriers  = db()->query("SELECT * FROM couriers WHERE user_id IS NOT NULL ORDER BY nome")->fetchAll();
  $emrota    = db()->query("SELECT o.*, c.nome cnome FROM orders o LEFT JOIN couriers c ON c.id=o.courier_id
                WHERE o.tipo='entrega' AND o.status='saiu_entrega' AND date(o.criado_em)=date('now','localtime')")->fetchAll();
  $aguardando= db()->query("SELECT * FROM orders WHERE tipo='entrega' AND status IN ('novo','aceito','em_preparo','pronto')
                AND date(criado_em)=date('now','localtime') ORDER BY id")->fetchAll();
  $entregues = db()->query("SELECT COUNT(*) c FROM orders WHERE status='entregue'
                AND date(criado_em)=date('now','localtime')")->fetch()['c'];
  $pushCount=(int)(db()->query("SELECT COUNT(DISTINCT user_id) c FROM push_subscriptions")->fetch()['c'] ?? 0);
  // Pendências de acerto (motoboy ainda não prestou contas no caixa) — todas, sem filtro de data
  $pendAcerto = db()->query("SELECT o.*, c.nome cnome FROM orders o LEFT JOIN couriers c ON c.id=o.courier_id
                WHERE o.acerto_status='pendente' ORDER BY o.id DESC")->fetchAll();
  // Extrato do dia por motoboy: entregas, valores e o que ainda falta acertar
  $extrato = db()->query("SELECT c.id,c.nome,c.online,
                COUNT(o.id) entregas, COALESCE(SUM(o.total),0) valor,
                COALESCE(SUM(CASE WHEN o.pagamento_metodo='dinheiro' THEN o.total ELSE 0 END),0) dinheiro,
                COALESCE(SUM(CASE WHEN o.pagamento_metodo IN ('cartao_entrega','pix_entrega') THEN o.total ELSE 0 END),0) maquina
                FROM couriers c LEFT JOIN orders o ON o.courier_id=c.id AND o.status='entregue' AND date(o.atualizado_em)=date('now','localtime')
                WHERE c.user_id IS NOT NULL GROUP BY c.id ORDER BY c.nome")->fetchAll();
  // Pendência total (qualquer data) por motoboy
  $pendPorMoto=[]; foreach(db()->query("SELECT courier_id, COUNT(*) q, COALESCE(SUM(total),0) v FROM orders WHERE acerto_status='pendente' GROUP BY courier_id")->fetchAll() as $p) $pendPorMoto[(int)$p['courier_id']]=$p;
  render('admin_couriers',['couriers'=>$couriers,'emrota'=>$emrota,'aguardando'=>$aguardando,'entregues'=>$entregues,
    'pendAcerto'=>$pendAcerto,'extrato'=>$extrato,'pendPorMoto'=>$pendPorMoto,
    'pushSupported'=>webpush_supported(),'pushMsg'=>webpush_capability_message(),
    'vapidOk'=>vapid_configured(),'pushCount'=>$pushCount]);
  break;

case 'admin_assign_courier': // POST order_id, courier_id -> despacha
  if(!require_role('admin')){ redirect('?r=admin'); }
  $oid=(int)$_POST['order_id']; $choice=$_POST['courier_id']??''; $cid=0;
  $oq=db()->prepare("SELECT * FROM orders WHERE id=?");$oq->execute([$oid]);$orderReady=$oq->fetch();
  if(!$orderReady||$orderReady['status']!=='pronto'||$orderReady['tipo']!=='entrega'){if(strpos($_SERVER['HTTP_ACCEPT']??'','application/json')!==false)json_out(['ok'=>false,'erro'=>'O pedido precisa estar pronto.']);redirect('?r=admin_orders');}
  if(payment_blocks_completion($orderReady)){if(strpos($_SERVER['HTTP_ACCEPT']??'','application/json')!==false)json_out(['ok'=>false,'erro'=>'Pagamento online pendente. Use a caneta do card e confirme PAGO antes de despachar.']);redirect('?r=admin_orders');}
  if($choice==='random'){
    $c=db()->query("SELECT id FROM couriers WHERE user_id IS NOT NULL AND online=1 ORDER BY CASE status WHEN 'livre' THEN 0 ELSE 1 END, RANDOM() LIMIT 1")->fetch(); $cid=(int)($c['id']??0);
  } elseif($choice!=='coleta') $cid=(int)$choice;
  db()->prepare("UPDATE orders SET courier_id=? WHERE id=?")->execute([$cid?:null,$oid]);
  if($cid) db()->prepare("UPDATE couriers SET status='em_rota' WHERE id=?")->execute([$cid]);
  order_set_status($oid,'saiu_entrega','admin');
  if($cid){
    $q=db()->prepare("SELECT * FROM couriers WHERE id=?");$q->execute([$cid]);$courier=$q->fetch();
    n8n_event('delivery_assigned',['order_id'=>$oid,'motoboy'=>$courier]);
    // Um único aviso por despacho. Web Push chega mesmo com o navegador em
    // segundo plano; a tag exclusiva evita repetir o mesmo pedido em polling.
    evolution_delivery_alert($courier,$orderReady);
    if(!empty($courier['user_id'])) webpush_notify_user((int)$courier['user_id'],[
      'title'=>'🛵 Nova entrega para você!',
      'body'=>'Pedido '.$orderReady['codigo'].' no balcão. Toque para abrir e aceitar.',
      'tag'=>'rv-entrega-'.$oid,
      'url'=>'?r=courier',
    ]);
  }
  if(strpos($_SERVER['HTTP_ACCEPT']??'','application/json')!==false) json_out(['ok'=>true]);
  redirect('?r=admin_couriers');
  break;

case 'admin_caixa': // aba Caixa (abrir, movimentar, fechar)
  if(!require_role('caixa')){ render('login',['role'=>'caixa','titulo'=>'Caixa']); break; }
  $cx = caixa_atual();
  $movs=[]; $tot=['venda'=>0,'retirada'=>0,'suprimento'=>0]; $itensVenda=[];
  if($cx){
    $m=db()->prepare("SELECT * FROM caixa_mov WHERE caixa_id=? ORDER BY id DESC"); $m->execute([$cx['id']]); $movs=$m->fetchAll();
    foreach($movs as $mv) $tot[$mv['tipo']] = ($tot[$mv['tipo']]??0)+$mv['valor'];
    // itens de cada venda (para clicar e ver o que foi vendido)
    foreach($movs as $mv) if($mv['order_id']){
      $q=db()->prepare("SELECT nome,qtd,preco FROM order_items WHERE order_id=?"); $q->execute([$mv['order_id']]);
      $itensVenda[$mv['order_id']]=$q->fetchAll();
    }
  }
  $operadores = db()->query("SELECT * FROM operadores WHERE ativo=1 ORDER BY nome")->fetchAll();
  $isAdminUser=($_SESSION['user_role']??'')==='admin';
  $hist = $isAdminUser ? db()->query("SELECT * FROM caixa WHERE status='fechado' ORDER BY id DESC LIMIT 7")->fetchAll() : [];
  render('admin_caixa',['cx'=>$cx,'movs'=>$movs,'tot'=>$tot,'hist'=>$hist,'operadores'=>$operadores,'itensVenda'=>$itensVenda,'isAdminUser'=>$isAdminUser]);
  break;

case 'admin_cash_detail':
  if(!require_role('admin')){ redirect('?r=admin'); }
  $id=(int)($_GET['id']??0);$q=db()->prepare("SELECT * FROM caixa WHERE id=?");$q->execute([$id]);$cash=$q->fetch();
  if(!$cash){redirect('?r=admin_caixa');}
  $q=db()->prepare("SELECT m.*,o.codigo,o.cliente_nome,o.pagamento_metodo,o.pagamento_status,o.status order_status FROM caixa_mov m LEFT JOIN orders o ON o.id=m.order_id WHERE m.caixa_id=? ORDER BY m.id DESC");$q->execute([$id]);$sales=$q->fetchAll();
  $items=[];foreach($sales as $s)if(!empty($s['order_id'])){$q=db()->prepare("SELECT nome,qtd,preco FROM order_items WHERE order_id=?");$q->execute([$s['order_id']]);$items[$s['order_id']]=$q->fetchAll();}
  render('admin_cash_detail',['cash'=>$cash,'sales'=>$sales,'items'=>$items]);break;

case 'caixa_abrir': // POST saldo_inicial, operador
  if(!require_role('caixa')){ redirect('?r=admin_caixa'); }
  db()->prepare("INSERT INTO caixa(saldo_inicial,operador) VALUES(?,?)")
    ->execute([(float)str_replace(',','.',$_POST['saldo_inicial']??0), $_POST['operador']??'']);
  redirect('?r=admin_caixa');
  break;

case 'caixa_mov': // POST tipo(retirada|suprimento), categoria, descricao, valor
  if(!require_role('caixa')){ redirect('?r=admin_caixa'); }
  caixa_mov_add($_POST['tipo'],(float)str_replace(',','.',$_POST['valor']??0),
                $_POST['categoria']??'',$_POST['descricao']??'');
  redirect('?r=admin_caixa');
  break;

case 'caixa_fechar': // POST saldo_informado
  if(!require_role('caixa')){ redirect('?r=admin_caixa'); }
  $cx=caixa_atual();
  if($cx){
    db()->prepare("UPDATE caixa SET status='fechado', fechado_em=datetime('now','localtime'), saldo_informado=? WHERE id=?")
      ->execute([(float)str_replace(',','.',$_POST['saldo_informado']??0),$cx['id']]);
  }
  redirect('?r=admin_caixa');
  break;

case 'admin_salon':
  if(!require_role('admin')){ redirect('?r=admin'); }
  $sections=['meu_salao','garcons','app_garcom','comandas','pdv','taxa','impressora','balanca'];
  $sec=in_array($_REQUEST['sec']??'meu_salao',$sections,true)?$_REQUEST['sec']:'meu_salao';
  if($method==='POST'){
    foreach(($_POST['s']??[]) as $k=>$v)setting_set($k,trim((string)$v));
    redirect('?r=admin_salon&sec='.urlencode($sec).'&salvo=1');
  }
  $waiters=db()->query("SELECT id,nome,usuario,telefone,ativo FROM users WHERE role='garcom' ORDER BY nome")->fetchAll();
  render('admin_salon',['waiters'=>$waiters,'sec'=>$sec]);
  break;

case 'admin_general':
  if(!require_role('admin')){ redirect('?r=admin'); }
  $sections=['status','sequence','sounds','printing','cancel','establishment','delivery','cardapio','integrations','security'];
  $sec=in_array($_REQUEST['sec']??'status',$sections,true)?$_REQUEST['sec']:'status';
  if($method==='POST'){
    foreach(($_POST['s']??[]) as $k=>$v)setting_set($k,trim((string)$v));
    // Capa do cardápio (imagem de exposição do comércio, estilo landing page)
    if(!empty($_FILES['capa']['name']) && is_uploaded_file($_FILES['capa']['tmp_name'])){
      $ext=strtolower(pathinfo($_FILES['capa']['name'],PATHINFO_EXTENSION));
      if(in_array($ext,['jpg','jpeg','png','webp'],true)){
        $nomeCapa='capa-'.time().'.'.$ext;
        if(move_uploaded_file($_FILES['capa']['tmp_name'],__DIR__.'/assets/produtos/'.$nomeCapa))
          setting_set('menu_capa','assets/produtos/'.$nomeCapa);
      }
    }
    if(!empty($_POST['remover_capa'])) setting_set('menu_capa','');
    // Logo do restaurante (aparece no painel e no cardápio)
    if(!empty($_FILES['logo']['name']) && is_uploaded_file($_FILES['logo']['tmp_name'])){
      $ext=strtolower(pathinfo($_FILES['logo']['name'],PATHINFO_EXTENSION));
      if(in_array($ext,['jpg','jpeg','png','webp','svg'],true)){
        $nomeLogo='logo-'.time().'.'.$ext;
        if(move_uploaded_file($_FILES['logo']['tmp_name'],__DIR__.'/assets/produtos/'.$nomeLogo))
          setting_set('brand_logo','assets/produtos/'.$nomeLogo);
      }
    }
    if(!empty($_POST['remover_logo'])) setting_set('brand_logo','');
    // Fundo do cardápio enviado pelo cliente
    if(!empty($_FILES['menubg']['name']) && is_uploaded_file($_FILES['menubg']['tmp_name'])){
      $ext=strtolower(pathinfo($_FILES['menubg']['name'],PATHINFO_EXTENSION));
      if(in_array($ext,['jpg','jpeg','png','webp'],true)){
        $nomeBg='fundo-'.time().'.'.$ext;
        if(move_uploaded_file($_FILES['menubg']['tmp_name'],__DIR__.'/assets/produtos/'.$nomeBg))
          setting_set('menu_bg_image','assets/produtos/'.$nomeBg);
      }
    }
    if(!empty($_POST['remover_menubg'])) setting_set('menu_bg_image','');
    redirect('?r=admin_general&sec='.urlencode($sec).'&salvo=1');
  }
  render('admin_general',['sec'=>$sec]);
  break;

case 'admin_recovery':
  if(!require_role('admin')){ redirect('?r=admin'); }
  if($method==='POST'){
    foreach(($_POST['s']??[]) as $k=>$v)setting_set($k,trim((string)$v));
    recovery_process();
    redirect('?r=admin_recovery&salvo=1');
  }
  recovery_process();
  $carts=db()->query("SELECT * FROM abandoned_carts ORDER BY atualizado_em DESC LIMIT 100")->fetchAll();
  render('admin_recovery',['carts'=>$carts]);
  break;

case 'admin_reports':
  if(!require_role('admin')){ redirect('?r=admin'); }
  $tipos=['geral','caixas','clientes','pedidos','itens','entregadores','garcons','areas'];$tipo=in_array($_GET['tipo']??'geral',$tipos,true)?$_GET['tipo']:'geral';
  $de=preg_match('/^\d{4}-\d{2}-\d{2}$/',$_GET['de']??'')?$_GET['de']:date('Y-m-d',strtotime('-30 days'));$ate=preg_match('/^\d{4}-\d{2}-\d{2}$/',$_GET['ate']??'')?$_GET['ate']:date('Y-m-d');$d=db();$rows=[];
  if($tipo==='geral'){$q=$d->prepare("SELECT COUNT(*) pedidos,COALESCE(SUM(CASE WHEN pagamento_status='pago' THEN total ELSE 0 END),0) faturamento,SUM(CASE WHEN status='cancelado' THEN 1 ELSE 0 END) cancelados FROM orders WHERE date(criado_em) BETWEEN ? AND ?");$q->execute([$de,$ate]);$rows=[$q->fetch()];}
  elseif($tipo==='caixas'){$q=$d->prepare("SELECT id,operador,status,saldo_inicial,saldo_informado,aberto_em,fechado_em FROM caixa WHERE date(aberto_em) BETWEEN ? AND ? ORDER BY id DESC");$q->execute([$de,$ate]);$rows=$q->fetchAll();}
  elseif($tipo==='clientes')$rows=$d->query("SELECT nome,telefone,bairro,pedidos,total_gasto,ultimo_pedido FROM customers ORDER BY total_gasto DESC")->fetchAll();
  elseif($tipo==='pedidos'){$q=$d->prepare("SELECT codigo,canal,tipo,cliente_nome,status,pagamento_metodo,pagamento_status,total,criado_em FROM orders WHERE date(criado_em) BETWEEN ? AND ? ORDER BY id DESC");$q->execute([$de,$ate]);$rows=$q->fetchAll();}
  elseif($tipo==='itens'){$q=$d->prepare("SELECT oi.nome,SUM(oi.qtd) quantidade,SUM(oi.qtd*oi.preco) total FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE date(o.criado_em) BETWEEN ? AND ? GROUP BY oi.nome ORDER BY quantidade DESC");$q->execute([$de,$ate]);$rows=$q->fetchAll();}
  elseif($tipo==='entregadores'){$q=$d->prepare("SELECT c.nome,c.telefone,c.online,COUNT(o.id) entregas,COALESCE(SUM(o.total),0) valor FROM couriers c LEFT JOIN orders o ON o.courier_id=c.id AND o.status='entregue' AND date(o.atualizado_em) BETWEEN ? AND ? GROUP BY c.id ORDER BY entregas DESC");$q->execute([$de,$ate]);$rows=$q->fetchAll();}
  elseif($tipo==='garcons'){$q=$d->prepare("SELECT u.nome,u.usuario,u.telefone,COUNT(o.id) pedidos,COALESCE(SUM(o.total),0) valor FROM users u LEFT JOIN orders o ON o.atendente=u.nome AND date(o.criado_em) BETWEEN ? AND ? WHERE u.role='garcom' GROUP BY u.id ORDER BY pedidos DESC");$q->execute([$de,$ate]);$rows=$q->fetchAll();}
  else $rows=$d->query("SELECT nome,tipo,taxa,ativo,criado_em FROM delivery_zones ORDER BY id DESC")->fetchAll();
  render('admin_reports',['tipo'=>$tipo,'de'=>$de,'ate'=>$ate,'rows'=>$rows]);
  break;

case 'admin_settings': // aba Config: integrações iFood, gateway, SEFAZ, bot
  if(!require_role('admin')){ redirect('?r=admin'); }
  if($method==='POST'){
    foreach(($_POST['s']??[]) as $k=>$v) setting_set($k,trim($v));
    // bot: salvar respostas
    if(isset($_POST['bot_gatilho'])){
      db()->exec("DELETE FROM bot_replies");
      foreach($_POST['bot_gatilho'] as $i=>$g){
        $resp = $_POST['bot_resposta'][$i] ?? '';
        if(trim($g)!=='' && trim($resp)!=='')
          db()->prepare("INSERT INTO bot_replies(gatilho,resposta) VALUES(?,?)")->execute([trim($g),trim($resp)]);
      }
    }
    redirect('?r=admin_settings&ok=1');
  }
  $bot = db()->query("SELECT * FROM bot_replies ORDER BY id")->fetchAll();
  $areas = db()->query("SELECT * FROM delivery_areas ORDER BY bairro")->fetchAll();
  $operadores = db()->query("SELECT * FROM operadores ORDER BY nome")->fetchAll();
  $garcons = db()->query("SELECT * FROM garcons ORDER BY nome")->fetchAll();
  $users = db()->query("SELECT id,nome,usuario,role,telefone,ativo FROM users ORDER BY role,nome")->fetchAll();
  render('admin_settings',['bot'=>$bot,'areas'=>$areas,'operadores'=>$operadores,'garcons'=>$garcons,'users'=>$users]);
  break;

case 'user_salvar':
  if(!require_role('admin')){ redirect('?r=admin'); }
  $role=in_array($_POST['role']??'', ['admin','garcom','motoboy','caixa','cozinha'],true)?$_POST['role']:'garcom';
  $nome=trim($_POST['nome']??''); $usuario=trim($_POST['usuario']??''); $senha=$_POST['senha']??''; $uid=(int)($_POST['id']??0); $fone=trim($_POST['telefone']??'');
  if($uid && $nome!=='' && $usuario!==''){
    try{
      if($senha!=='') db()->prepare("UPDATE users SET nome=?,usuario=?,senha_hash=?,role=?,telefone=? WHERE id=?")->execute([$nome,$usuario,password_hash($senha,PASSWORD_DEFAULT),$role,$fone,$uid]);
      else db()->prepare("UPDATE users SET nome=?,usuario=?,role=?,telefone=? WHERE id=?")->execute([$nome,$usuario,$role,$fone,$uid]);
    }catch(Exception $e){}
  } elseif($nome!==''&&$usuario!==''&&strlen($senha)>=4){
    try{ db()->prepare("INSERT INTO users(nome,usuario,senha_hash,role,telefone) VALUES(?,?,?,?,?)")->execute([$nome,$usuario,password_hash($senha,PASSWORD_DEFAULT),$role,$fone]); $uid=(int)db()->lastInsertId(); }catch(Exception $e){}
  }
  if($role==='motoboy' && $uid){ $q=db()->prepare("SELECT id FROM couriers WHERE user_id=?");$q->execute([$uid]);if($q->fetch())db()->prepare("UPDATE couriers SET nome=?,telefone=? WHERE user_id=?")->execute([$nome,$fone,$uid]);else db()->prepare("INSERT INTO couriers(nome,telefone,status,user_id) VALUES(?,?,'livre',?)")->execute([$nome,$fone,$uid]); }
  elseif($uid) db()->prepare("UPDATE couriers SET user_id=NULL WHERE user_id=?")->execute([$uid]);
  redirect('?r=admin_settings#usuarios');
  break;
case 'user_excluir':
  if(!require_role('admin')){ redirect('?r=admin'); }
  $id=(int)($_POST['id']??0);
  if($id!==($_SESSION['user_id']??0)){ db()->prepare("UPDATE couriers SET user_id=NULL WHERE user_id=?")->execute([$id]); db()->prepare("DELETE FROM users WHERE id=?")->execute([$id]); }
  redirect('?r=admin_settings#usuarios');
  break;

case 'bot_reply': // ⭐ Bot híbrido: n8n chama aqui. Resposta pronta = 0 token de IA.
  // GET/POST msg=texto do cliente
  $msg = mb_strtolower(trim($_REQUEST['msg'] ?? ''));
  if($msg==='') json_out(['ok'=>false,'erro'=>'msg vazia']);
  $rows = db()->query("SELECT * FROM bot_replies WHERE ativo=1")->fetchAll();
  foreach($rows as $r){
    foreach(explode('|',mb_strtolower($r['gatilho'])) as $kw){
      $kw=trim($kw);
      if($kw!=='' && mb_strpos($msg,$kw)!==false){
        $link=(isset($_SERVER['HTTP_HOST'])?'https://'.$_SERVER['HTTP_HOST'].strtok($_SERVER['REQUEST_URI'],'?'):'').'?r=menu';
        $nome=trim($_REQUEST['nome']??'cliente');$hora=(int)date('H');$saudacao=$hora<12?'Bom dia':($hora<18?'Boa tarde':'Boa noite');
        $resp=str_replace(['{LINK_CARDAPIO}','{NOME_CLIENTE}','{SAUDACAO}'],[$link,$nome,$saudacao],$r['resposta']);
        json_out(['ok'=>true,'fonte'=>'predefinida','usar_ia'=>false,'resposta'=>$resp]);
      }
    }
  }
  // nada casou -> n8n manda pra IA (só aqui gasta token)
  json_out(['ok'=>true,'fonte'=>'nenhuma','usar_ia'=>true,'resposta'=>null]);
  break;

case 'admin_whatsapp':
  if(!require_role('admin')){ redirect('?r=admin'); }
  if($method==='POST'){
    foreach(['evo_url','evo_apikey','evo_instance','wa_phone_number_id','wa_access_token','wa_verify_token','wa_graph_version'] as $k)
      if(isset($_POST[$k])) setting_set($k,trim($_POST[$k]));
    redirect('?r=admin_whatsapp&salvo=1');
  }
  $waContacts=db()->query("SELECT c.*, (SELECT texto FROM whatsapp_messages m WHERE m.wa_id=c.wa_id ORDER BY m.id DESC LIMIT 1) ultima FROM whatsapp_contacts c ORDER BY atualizado_em DESC LIMIT 100")->fetchAll();
  $waSelected=preg_replace('/\D/','',$_GET['wa']??($waContacts[0]['wa_id']??''));
  $waMessages=[];
  if($waSelected){$q=db()->prepare("SELECT * FROM whatsapp_messages WHERE wa_id=? ORDER BY id");$q->execute([$waSelected]);$waMessages=$q->fetchAll();}
  render('admin_whatsapp',['waContacts'=>$waContacts,'waMessages'=>$waMessages,'waSelected'=>$waSelected]);
  break;

case 'admin_bot_messages':
  if(!require_role('admin')){ redirect('?r=admin'); }
  if($method==='POST'){
    setting_set('wa_bot_active',isset($_POST['wa_bot_active'])?'1':'0');
    setting_set('channel_whatsapp',isset($_POST['channel_whatsapp'])?'1':'0');
    foreach(['wa_msg_novo','wa_msg_preparo','wa_msg_saiu','wa_msg_entregue'] as $k)
      if(isset($_POST[$k])) setting_set($k,trim($_POST[$k]));
    db()->exec("DELETE FROM bot_replies");
    foreach(($_POST['gatilho']??[]) as $i=>$gatilho){
      $gatilho=trim($gatilho);$resposta=trim($_POST['resposta'][$i]??'');
      if($gatilho!==''&&$resposta!=='')db()->prepare("INSERT INTO bot_replies(gatilho,resposta,ativo) VALUES(?,?,?)")->execute([$gatilho,$resposta,isset($_POST['ativo'][$i])?1:0]);
    }
    redirect('?r=admin_bot_messages&salvo=1');
  }
  $bot=db()->query("SELECT * FROM bot_replies ORDER BY id")->fetchAll();
  $orderMsgs=['novo'=>setting_get('wa_msg_novo','')?:wa_msg_default('wa_msg_novo'),
              'preparo'=>setting_get('wa_msg_preparo','')?:wa_msg_default('wa_msg_preparo'),
              'saiu'=>setting_get('wa_msg_saiu','')?:wa_msg_default('wa_msg_saiu'),
              'entregue'=>setting_get('wa_msg_entregue','')?:wa_msg_default('wa_msg_entregue')];
  render('admin_bot_messages',['bot'=>$bot,'orderMsgs'=>$orderMsgs,
    'channelWhats'=>setting_get('channel_whatsapp','1')==='1']);
  break;

case 'admin_whatsapp_send':
  if(!require_role('admin')) json_out(['ok'=>false]);
  $to=preg_replace('/\D/','',$_POST['to']??'');$text=trim($_POST['texto']??'');
  if(!$to||$text==='') json_out(['ok'=>false,'erro'=>'Informe destinatário e mensagem.']);
  if(evolution_configured()){
    $sent=evolution_send_text($to,$text);
    if(!$sent['ok'])json_out(['ok'=>false,'erro'=>'A Evolution API recusou o envio. Confira URL, instância e chave.','detalhe'=>$sent['raw']??'']);
    db()->prepare("INSERT INTO whatsapp_messages(wa_id,direcao,texto,status) VALUES(?,'saida',?,'enviada')")->execute([$to,$text]);
    db()->prepare("INSERT INTO whatsapp_contacts(wa_id,nome) VALUES(?,'') ON CONFLICT(wa_id) DO UPDATE SET atualizado_em=datetime('now','localtime')")->execute([$to]);
    json_out(['ok'=>true]);
  }
  $phone=setting_get('wa_phone_number_id','');$token=setting_get('wa_access_token','');$ver=setting_get('wa_graph_version','v23.0')?:'v23.0';
  if(!$phone||!$token) json_out(['ok'=>false,'erro'=>'Conecte a API oficial do WhatsApp primeiro.']);
  $ch=curl_init("https://graph.facebook.com/".rawurlencode($ver)."/".rawurlencode($phone)."/messages");
  curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$token,'Content-Type: application/json'],CURLOPT_POSTFIELDS=>json_encode(['messaging_product'=>'whatsapp','to'=>$to,'type'=>'text','text'=>['body'=>$text]])]);
  $raw=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
  if($code<200||$code>=300) json_out(['ok'=>false,'erro'=>'A Meta recusou o envio. Confira token e número.','detalhe'=>$raw]);
  db()->prepare("INSERT INTO whatsapp_messages(wa_id,direcao,texto,status) VALUES(?,'saida',?,'enviada')")->execute([$to,$text]);
  db()->prepare("INSERT INTO whatsapp_contacts(wa_id,nome) VALUES(?,'') ON CONFLICT(wa_id) DO UPDATE SET atualizado_em=datetime('now','localtime')")->execute([$to]);
  json_out(['ok'=>true]);
  break;

case 'evolution_test':
  if(!require_role('admin'))json_out(['ok'=>false]);
  json_out(evolution_request('GET','instance/connectionState/'.rawurlencode(setting_get('evo_instance',''))));
  break;

case 'evolution_connect':
  if(!require_role('admin'))json_out(['ok'=>false]);
  json_out(evolution_request('GET','instance/connect/'.rawurlencode(setting_get('evo_instance',''))));
  break;

case 'evolution_webhook_config':
  if(!require_role('admin'))json_out(['ok'=>false]);
  $slug=current_slug();
  $webhookBase=(!empty($_SERVER['HTTPS'])?'https':'http').'://'.($_SERVER['HTTP_HOST']??'pederv.com.br');
  $webhookUrl=$webhookBase.'/?r=webhook_evolution'.($slug?'&slug='.rawurlencode($slug):'');
  $webhook=['enabled'=>true,'url'=>$webhookUrl,'webhookByEvents'=>false,'webhookBase64'=>false,'events'=>['MESSAGES_UPSERT','MESSAGES_UPDATE','CONNECTION_UPDATE','QRCODE_UPDATED','SEND_MESSAGE']];
  $body=['webhook'=>$webhook];
  $r1=evolution_request('POST','webhook/set/'.rawurlencode(setting_get('evo_instance','')),$body);
  $r1['webhook_url_configurada']=$webhookUrl;
  json_out($r1);
  break;

case 'whatsapp_feed':
  if(!require_role('admin'))json_out(['ok'=>false]);
  $last=db()->query("SELECT id,wa_id,texto,criado_em FROM whatsapp_messages WHERE direcao='entrada' ORDER BY id DESC LIMIT 1")->fetch();
  $today=db()->query("SELECT COUNT(*) c FROM whatsapp_messages WHERE direcao='entrada' AND date(criado_em)=date('now','localtime')")->fetch();
  json_out(['ok'=>true,'latest'=>$last?:null,'today'=>(int)($today['c']??0)]);
  break;

case 'webhook_evolution':
  $payload=json_decode(file_get_contents('php://input'),true)?:[];
  $event=strtolower((string)($payload['event']??''));$data=$payload['data']??[];
  if(isset($data[0]))$data=$data[0];
  if(strpos($event,'messages.upsert')!==false || strpos($event,'messages_upsert')!==false){
    $key=$data['key']??[];$jid=(string)($key['remoteJid']??'');$fromMe=!empty($key['fromMe']);
    if(strpos($jid,'@g.us')===false && strpos($jid,'status@')===false){
      $wa=preg_replace('/\D/','',explode('@',$jid)[0]??'');$msg=$data['message']??[];
      $text=trim((string)($msg['conversation']??($msg['extendedTextMessage']['text']??($msg['imageMessage']['caption']??($msg['videoMessage']['caption']??'')))));
      if($wa!==''&&$text!==''){
        // Dedup: ignora reentregas do mesmo webhook (Evolution retenta quando há delay)
        $msgId=(string)($key['id']??'');
        if($msgId!==''){
          try{
            master_db()->exec("CREATE TABLE IF NOT EXISTS saas_processed_msgs(msg_id TEXT PRIMARY KEY, ts INTEGER)");
            $dchk=master_db()->prepare("SELECT 1 FROM saas_processed_msgs WHERE msg_id=?");
            $dchk->execute([$msgId]);
            if($dchk->fetch()){ json_out(['ok'=>true,'dup'=>1]); }
            master_db()->prepare("INSERT OR IGNORE INTO saas_processed_msgs(msg_id,ts) VALUES(?,?)")->execute([$msgId,time()]);
            master_db()->prepare("DELETE FROM saas_processed_msgs WHERE ts < ?")->execute([time()-86400]);
          }catch(Exception $_de){}
        }
        $nome=trim((string)($data['pushName']??''));$dir=$fromMe?'saida':'entrada';
        db()->prepare("INSERT INTO whatsapp_contacts(wa_id,nome) VALUES(?,?) ON CONFLICT(wa_id) DO UPDATE SET nome=CASE WHEN excluded.nome<>'' THEN excluded.nome ELSE nome END, atualizado_em=datetime('now','localtime')")->execute([$wa,$nome]);
        db()->prepare("INSERT INTO whatsapp_messages(wa_id,direcao,texto,status) VALUES(?,?,?,'recebida')")->execute([$wa,$dir,$text]);
        $isSaas=current_slug()==='';
        if(!$fromMe){
          n8n_event('whatsapp_message',['from'=>$wa,'nome'=>$nome,'texto'=>$text]);
          $botActive=$isSaas?setting_get('saas_wa_bot_active','0'):setting_get('wa_bot_active','0');
          if($botActive==='1'){
            $reply='';$low=mb_strtolower($text);$hora=(int)date('H');$saudacao=$hora<12?'Bom dia':($hora<18?'Boa tarde':'Boa noite');
            if($isSaas){
              master_db()->exec("CREATE TABLE IF NOT EXISTS saas_bot_replies(id INTEGER PRIMARY KEY AUTOINCREMENT,gatilho TEXT,resposta TEXT,ativo INTEGER DEFAULT 1)");
              master_db()->exec("CREATE TABLE IF NOT EXISTS saas_conv_state(phone TEXT PRIMARY KEY,mode TEXT DEFAULT 'bot',last_human INTEGER DEFAULT 0)");
              $saasUrl=setting_get('saas_evo_url','');$saasKey=setting_get('saas_evo_key','');$saasInst=setting_get('saas_evo_instance','');
              $waNum=preg_replace('/\D/','',(string)$wa);if(strlen($waNum)<=11)$waNum='55'.$waNum;
              // Verificar estado da conversa (humano ou bot)
              $convStmt=master_db()->prepare("SELECT mode,last_human FROM saas_conv_state WHERE phone=?");
              $convStmt->execute([$waNum]);$convRow=$convStmt->fetch(PDO::FETCH_ASSOC);
              $convMode='bot';$lastHuman=0;
              if($convRow){$convMode=(string)$convRow['mode'];$lastHuman=(int)$convRow['last_human'];}
              // Auto-retomar após timeout de inatividade do humano
              $humanTimeout=(int)setting_get('saas_human_timeout','4');
              if($convMode==='human'&&$humanTimeout>0&&$lastHuman>0&&(time()-$lastHuman)>($humanTimeout*3600))$convMode='bot';
              if($convMode==='human'){
                // Conversa em modo humano — robô não responde
              } else {
                // Detectar pedido de atendente humano
                $isHumanReq=false;
                foreach(explode('|','atendente|humano|representante|consultor|vendedor') as $kw)
                  if(mb_strpos($low,trim($kw))!==false){$isHumanReq=true;break;}
                // Buscar resposta nos gatilhos cadastrados
                foreach(master_db()->query("SELECT * FROM saas_bot_replies WHERE ativo=1 ORDER BY id")->fetchAll() as $br)
                  foreach(explode('|',mb_strtolower($br['gatilho'])) as $kw)
                    if(trim($kw)!==''&&mb_strpos($low,trim($kw))!==false){$reply=$br['resposta'];break 2;}
                // Sem gatilho encontrado → avisar representantes e responder que vai chamar consultor
                // (NÃO trava em modo humano: o robô continua respondendo as próximas mensagens)
                $isNoMatch=false;
                if($reply===''&&!$isHumanReq){$isNoMatch=true;$reply="Olá! 😊 Deixa eu chamar um de nossos consultores para te atender. Um momento!";}
                if($reply!==''&&$saasUrl&&$saasKey&&$saasInst){
                  $reply=str_replace(['{NOME}','{SAUDACAO}','{PLANO}','{TRIAL_DIAS}','{LINK_SITE}'],[$nome?:'cliente',$saudacao,'Pró','7','https://pederv.com.br'],$reply);
                  // Mostrar "digitando..." antes de responder
                  $chTyp=curl_init(rtrim($saasUrl,'/').'/chat/sendPresence/'.rawurlencode($saasInst));
                  curl_setopt_array($chTyp,[CURLOPT_CUSTOMREQUEST=>'POST',CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>4,CURLOPT_TIMEOUT=>5,CURLOPT_HTTPHEADER=>['apikey: '.$saasKey,'Content-Type: application/json'],CURLOPT_POSTFIELDS=>json_encode(['number'=>$waNum,'presence'=>'composing','delay'=>1500],JSON_UNESCAPED_UNICODE)]);
                  curl_exec($chTyp);curl_close($chTyp);
                  // Aplicar delay configurado
                  $botDelay=(int)setting_get('saas_bot_delay','0');
                  if($botDelay>0&&$botDelay<=60)sleep($botDelay);
                  // Enviar resposta
                  $ch=curl_init(rtrim($saasUrl,'/').'/message/sendText/'.rawurlencode($saasInst));
                  curl_setopt_array($ch,[CURLOPT_CUSTOMREQUEST=>'POST',CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>30,CURLOPT_HTTPHEADER=>['apikey: '.$saasKey,'Content-Type: application/json'],CURLOPT_POSTFIELDS=>json_encode(['number'=>$waNum,'text'=>$reply,'linkPreview'=>false],JSON_UNESCAPED_UNICODE)]);
                  $raw=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
                  if($code>=200&&$code<300)db()->prepare("INSERT INTO whatsapp_messages(wa_id,direcao,texto,status) VALUES(?,'saida',?,'enviada')")->execute([$wa,$reply]);
                  // Se pediu atendente OU pergunta sem resposta: notificar representantes
                  if($isHumanReq||$isNoMatch){
                    $notifyPhones=setting_get('saas_notify_phones','');
                    $notifyTag=$isHumanReq?'pediu atendimento humano':'enviou mensagem sem resposta no bot';
                    $notifyMsg="🤖 *PedeRV Bot* — $nome ($waNum) $notifyTag.\nMensagem: \"$text\"";
                    foreach(array_filter(array_map('trim',preg_split('/[\n,;]+/',$notifyPhones))) as $rn){
                      $rNum=preg_replace('/\D/','',$rn);if(strlen($rNum)<=11)$rNum='55'.$rNum;
                      if($rNum==='')continue;
                      $nc=curl_init(rtrim($saasUrl,'/').'/message/sendText/'.rawurlencode($saasInst));
                      curl_setopt_array($nc,[CURLOPT_CUSTOMREQUEST=>'POST',CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>10,CURLOPT_HTTPHEADER=>['apikey: '.$saasKey,'Content-Type: application/json'],CURLOPT_POSTFIELDS=>json_encode(['number'=>$rNum,'text'=>$notifyMsg],JSON_UNESCAPED_UNICODE)]);
                      curl_exec($nc);curl_close($nc);
                    }
                    // SÓ trava em modo humano quando o cliente PEDE explicitamente um atendente
                    if($isHumanReq)
                      master_db()->prepare("INSERT OR REPLACE INTO saas_conv_state(phone,mode,last_human) VALUES(?,?,?)")->execute([$waNum,'human',time()]);
                  }
                }
              }
            } else {
              $fechadaMsg=store_closed_message();
              if($fechadaMsg!==''){ $reply=$fechadaMsg; }
              else foreach(db()->query("SELECT * FROM bot_replies WHERE ativo=1")->fetchAll() as $br)foreach(explode('|',mb_strtolower($br['gatilho'])) as $kw)if(trim($kw)!==''&&mb_strpos($low,trim($kw))!==false){$reply=$br['resposta'];break 2;}
              if($reply!==''){
                $base=(!empty($_SERVER['HTTPS'])?'https':'http').'://'.($_SERVER['HTTP_HOST']??'rvautomacao.com.br').strtok($_SERVER['REQUEST_URI']??'/cardapio/','?');
                $reply=str_replace(['{LINK_CARDAPIO}','{NOME_CLIENTE}','{SAUDACAO}'],[$base.'?r=menu',$nome?:'cliente',$saudacao],$reply);$sent=evolution_send_text($wa,$reply);
                if($sent['ok'])db()->prepare("INSERT INTO whatsapp_messages(wa_id,direcao,texto,status) VALUES(?,'saida',?,'enviada')")->execute([$wa,$reply]);
              }
            }
          }
        }
      }
    }
  }
  json_out(['ok'=>true]);
  break;

case 'webhook_whatsapp':
  if($method==='GET'){
    $ok=($_GET['hub_verify_token']??'')===setting_get('wa_verify_token','');
    if($ok){header('Content-Type: text/plain');echo $_GET['hub_challenge']??'';exit;}
    http_response_code(403);exit('Token inválido');
  }
  $payload=json_decode(file_get_contents('php://input'),true)?:[];
  foreach($payload['entry']??[] as $entry)foreach($entry['changes']??[] as $change){
    $value=$change['value']??[];$names=[];
    foreach($value['contacts']??[] as $ct)$names[$ct['wa_id']??'']=$ct['profile']['name']??'';
    foreach($value['messages']??[] as $msg){
      $from=preg_replace('/\D/','',$msg['from']??'');$text=trim($msg['text']['body']??'');
      if(!$from||$text==='')continue;
      db()->prepare("INSERT INTO whatsapp_contacts(wa_id,nome) VALUES(?,?) ON CONFLICT(wa_id) DO UPDATE SET nome=excluded.nome, atualizado_em=datetime('now','localtime')")->execute([$from,$names[$from]??'']);
      db()->prepare("INSERT INTO whatsapp_messages(wa_id,direcao,texto,status) VALUES(?,'entrada',?,'recebida')")->execute([$from,$text]);
      n8n_event('whatsapp_message',['from'=>$from,'nome'=>$names[$from]??'','texto'=>$text]);
      if(setting_get('wa_bot_active','0')==='1'){
        $reply='';$low=mb_strtolower($text);
        $fechadaMsg=store_closed_message();
        if($fechadaMsg!==''){ $reply=$fechadaMsg; }
        else foreach(db()->query("SELECT * FROM bot_replies WHERE ativo=1")->fetchAll() as $br){
          foreach(explode('|',mb_strtolower($br['gatilho'])) as $kw)if(trim($kw)!==''&&mb_strpos($low,trim($kw))!==false){$reply=$br['resposta'];break 2;}
        }
        if($reply!==''){
          $base=(!empty($_SERVER['HTTPS'])?'https':'http').'://'.($_SERVER['HTTP_HOST']??'rvautomacao.com.br').strtok($_SERVER['REQUEST_URI']??'/cardapio/','?');
          $hora=(int)date('H');$saudacao=$hora<12?'Bom dia':($hora<18?'Boa tarde':'Boa noite');$nomeCliente=$names[$from]??'cliente';
          $reply=str_replace(['{LINK_CARDAPIO}','{NOME_CLIENTE}','{SAUDACAO}'],[$base.'?r=menu',$nomeCliente?:'cliente',$saudacao],$reply);
          $phone=setting_get('wa_phone_number_id','');$token=setting_get('wa_access_token','');$ver=setting_get('wa_graph_version','v23.0')?:'v23.0';
          if($phone&&$token&&function_exists('curl_init')){
            $ch=curl_init("https://graph.facebook.com/".rawurlencode($ver)."/".rawurlencode($phone)."/messages");
            curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$token,'Content-Type: application/json'],CURLOPT_POSTFIELDS=>json_encode(['messaging_product'=>'whatsapp','to'=>$from,'type'=>'text','text'=>['body'=>$reply]])]);
            curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
            if($code>=200&&$code<300)db()->prepare("INSERT INTO whatsapp_messages(wa_id,direcao,texto,status) VALUES(?,'saida',?,'enviada')")->execute([$from,$reply]);
          }
        }
      }
    }
  }
  json_out(['ok'=>true]);
  break;

case 'admin_notas': // aba Notas Fiscais
  if(!require_role('admin')){ redirect('?r=admin'); }
  $notas = db()->query("SELECT n.*, o.codigo, o.cliente_nome FROM notas n
                        LEFT JOIN orders o ON o.id=n.order_id ORDER BY n.id DESC LIMIT 50")->fetchAll();
  // pedidos pagos ainda sem nota autorizada (para emitir manual)
  $semNota = db()->query("SELECT o.* FROM orders o
     WHERE o.pagamento_status='pago' AND date(o.criado_em)=date('now','localtime')
     AND NOT EXISTS (SELECT 1 FROM notas n WHERE n.order_id=o.id AND n.status='autorizada')
     ORDER BY o.id DESC")->fetchAll();
  render('admin_notas',['notas'=>$notas,'semNota'=>$semNota]);
  break;

case 'nota_emitir': // POST order_id
  if(!require_role('admin')){ redirect('?r=admin'); }
  fiscal_emitir((int)$_POST['order_id'],'admin');
  redirect('?r=admin_notas');
  break;

case 'nota_cancelar': // POST nota_id, justificativa
  if(!require_role('admin')){ redirect('?r=admin'); }
  fiscal_cancelar((int)$_POST['nota_id'], $_POST['justificativa'] ?? 'Cancelamento solicitado');
  redirect('?r=admin_notas');
  break;

case 'admin_produtos': // aba Cardápio/Produtos
  if(!require_role('admin')){ redirect('?r=admin'); }
  $cats = db()->query("SELECT * FROM categories ORDER BY ordem,nome")->fetchAll();
  $prods= db()->query("SELECT * FROM products ORDER BY category_id,nome")->fetchAll();
  $edit = null;
  if(isset($_GET['edit'])){ $e=db()->prepare("SELECT * FROM products WHERE id=?"); $e->execute([(int)$_GET['edit']]); $edit=$e->fetch(); }
  render('admin_produtos',['cats'=>$cats,'prods'=>$prods,'edit'=>$edit]);
  break;

case 'produto_salvar': // POST (cria ou edita) + upload de foto
  if(!require_role('admin')){ redirect('?r=admin'); }
  $d=db();
  $id      = (int)($_POST['id'] ?? 0);
  $foto    = $_POST['foto_atual'] ?? '';
  // upload de imagem (opcional)
  if(!empty($_FILES['foto']['name']) && is_uploaded_file($_FILES['foto']['tmp_name'])){
    $ext = strtolower(pathinfo($_FILES['foto']['name'],PATHINFO_EXTENSION));
    if(in_array($ext,['jpg','jpeg','png','webp','gif'])){
      @mkdir(__DIR__.'/assets/produtos',0755,true);
      $nome='p'.time().rand(10,99).'.'.$ext;
      if(move_uploaded_file($_FILES['foto']['tmp_name'], __DIR__.'/assets/produtos/'.$nome))
        $foto='assets/produtos/'.$nome;
    }
  }
  $campos=[
    'category_id'=>(int)$_POST['category_id'], 'nome'=>$_POST['nome'], 'descricao'=>$_POST['descricao'],
    'preco'=>(float)str_replace(',','.',$_POST['preco']), 'emoji'=>$_POST['emoji']?:'🍔', 'foto'=>$foto,
    'tipo'=>(($_POST['tipo']??'produto')==='combo'?'combo':'produto'),
    'combo_itens'=>json_encode(array_values(array_map('intval',$_POST['combo_itens']??[]))),
    'destaque'=>isset($_POST['destaque'])?1:0, 'ativo'=>isset($_POST['ativo'])?1:0,
    'estoque'=>(int)$_POST['estoque'], 'controla_estoque'=>isset($_POST['controla_estoque'])?1:0,
    'estoque_minimo'=>(int)$_POST['estoque_minimo'],
    'ncm'=>$_POST['ncm']?:'21069090','cfop'=>$_POST['cfop']?:'5102','cst'=>$_POST['cst']?:'102',
    'origem'=>$_POST['origem']?:'0','unidade'=>$_POST['unidade']?:'UN',
  ];
  if($id){
    $set=implode(',',array_map(fn($k)=>"$k=?",array_keys($campos)));
    $st=$d->prepare("UPDATE products SET $set WHERE id=?"); $st->execute([...array_values($campos),$id]);
  } else {
    $cols=implode(',',array_keys($campos)); $ph=implode(',',array_fill(0,count($campos),'?'));
    $d->prepare("INSERT INTO products($cols) VALUES($ph)")->execute(array_values($campos));
  }
  redirect('?r=admin_produtos');
  break;

case 'produto_excluir': // POST id
  if(!require_role('admin')){ redirect('?r=admin'); }
  db()->prepare("DELETE FROM products WHERE id=?")->execute([(int)$_POST['id']]);
  redirect('?r=admin_produtos');
  break;

case 'produto_toggle': // POST id (ativa/desativa)
  if(!require_role('admin')) json_out(['ok'=>false]);
  $id=(int)$_POST['id'];
  $p=db()->prepare("SELECT ativo FROM products WHERE id=?"); $p->execute([$id]); $p=$p->fetch();
  $novo=$p['ativo']?0:1;
  db()->prepare("UPDATE products SET ativo=? WHERE id=?")->execute([$novo,$id]);
  json_out(['ok'=>true,'ativo'=>$novo]);
  break;

case 'categoria_salvar': // POST nome
  if(!require_role('admin')){ redirect('?r=admin'); }
  if(trim($_POST['nome'] ?? '')!==''){
    $o=db()->query("SELECT COALESCE(MAX(ordem),0)+1 o FROM categories")->fetch()['o'];
    db()->prepare("INSERT INTO categories(nome,ordem) VALUES(?,?)")->execute([trim($_POST['nome']),$o]);
  }
  redirect('?r=admin_produtos');
  break;

case 'admin_financeiro': // aba Financeiro (dashboard + fechamento semanal)
  if(!require_role('admin')){ redirect('?r=admin'); }
  $d=db();
  $sub = ($_GET['sub'] ?? '')==='ifood' ? 'ifood' : 'geral';
  $wcanal = $sub==='ifood' ? " AND canal='ifood'" : "";
  $q=fn($sql)=>$d->query($sql)->fetch();
  $hoje   = $q("SELECT COALESCE(SUM(total),0) v, COUNT(*) n FROM orders WHERE pagamento_status='pago'$wcanal AND date(criado_em)=date('now','localtime')");
  $semana = $q("SELECT COALESCE(SUM(total),0) v, COUNT(*) n FROM orders WHERE pagamento_status='pago'$wcanal AND date(criado_em)>=date('now','localtime','-6 days')");
  $mes    = $q("SELECT COALESCE(SUM(total),0) v, COUNT(*) n FROM orders WHERE pagamento_status='pago'$wcanal AND strftime('%Y-%m',criado_em)=strftime('%Y-%m','now','localtime')");
  $metodos= $d->query("SELECT pagamento_metodo m, COALESCE(SUM(total),0) v, COUNT(*) n FROM orders WHERE pagamento_status='pago'$wcanal AND date(criado_em)>=date('now','localtime','-6 days') GROUP BY pagamento_metodo")->fetchAll();
  $dias   = $d->query("SELECT date(criado_em) dia, COALESCE(SUM(total),0) v FROM orders WHERE pagamento_status='pago'$wcanal AND date(criado_em)>=date('now','localtime','-6 days') GROUP BY date(criado_em) ORDER BY dia")->fetchAll();
  $top    = $d->query("SELECT oi.nome, SUM(oi.qtd) q, SUM(oi.qtd*oi.preco) v FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.pagamento_status='pago'".($sub==='ifood'?" AND o.canal='ifood'":"")." AND date(o.criado_em)>=date('now','localtime','-6 days') GROUP BY oi.nome ORDER BY q DESC LIMIT 5")->fetchAll();
  $canais = $d->query("SELECT canal, COALESCE(SUM(total),0) v, COUNT(*) n FROM orders WHERE pagamento_status='pago' AND date(criado_em)>=date('now','localtime','-6 days') GROUP BY canal")->fetchAll();
  render('admin_financeiro',compact('hoje','semana','mes','metodos','dias','top','canais','sub'));
  break;

case 'garcom_salvar': // POST nome
  if(!require_role('admin')){ redirect('?r=admin'); }
  if(trim($_POST['nome']??'')!=='')
    db()->prepare("INSERT INTO garcons(nome) VALUES(?)")->execute([trim($_POST['nome'])]);
  redirect('?r=admin_settings#garcons');
  break;
case 'garcom_excluir':
  if(!require_role('admin')){ redirect('?r=admin'); }
  db()->prepare("DELETE FROM garcons WHERE id=?")->execute([(int)$_POST['id']]);
  redirect('?r=admin_settings#garcons');
  break;

case 'area_salvar': // POST bairro, taxa
  if(!require_role('admin')){ redirect('?r=admin'); }
  if(trim($_POST['bairro']??'')!=='')
    db()->prepare("INSERT INTO delivery_areas(bairro,taxa) VALUES(?,?)")
      ->execute([trim($_POST['bairro']),(float)str_replace(',','.',$_POST['taxa']??0)]);
  redirect('?r=admin_areas');
  break;
case 'area_excluir':
  if(!require_role('admin')){ redirect('?r=admin'); }
  db()->prepare("DELETE FROM delivery_areas WHERE id=?")->execute([(int)$_POST['id']]);
  redirect('?r=admin_areas');
  break;

case 'operador_salvar': // POST nome, pin
  if(!require_role('admin')){ redirect('?r=admin'); }
  if(trim($_POST['nome']??'')!=='')
    db()->prepare("INSERT INTO operadores(nome,pin) VALUES(?,?)")->execute([trim($_POST['nome']),$_POST['pin']??'']);
  redirect('?r=admin_settings#operadores');
  break;
case 'operador_excluir':
  if(!require_role('admin')){ redirect('?r=admin'); }
  db()->prepare("DELETE FROM operadores WHERE id=?")->execute([(int)$_POST['id']]);
  redirect('?r=admin_settings#operadores');
  break;

case 'courier_salvar': // POST nome, telefone (cadastrar motoboy)
  if(!require_role('admin')){ redirect('?r=admin'); }
  if(trim($_POST['nome']??'')!=='')
    db()->prepare("INSERT INTO couriers(nome,telefone,status) VALUES(?,?, 'livre')")
      ->execute([trim($_POST['nome']),$_POST['telefone']??'']);
  redirect('?r=admin_couriers');
  break;
case 'courier_excluir':
  if(!require_role('admin')){ redirect('?r=admin'); }
  db()->prepare("DELETE FROM couriers WHERE id=?")->execute([(int)$_POST['id']]);
  redirect('?r=admin_couriers');
  break;

case 'courier_aceitar': // motoboy aceita a entrega
  if(!require_role('motoboy')) json_out(['ok'=>false]);
  $id=(int)$_POST['id']; $uid=(int)($_SESSION['user_id']??0);
  db()->prepare("UPDATE orders SET aceito=1 WHERE id=? AND courier_id=(SELECT id FROM couriers WHERE user_id=?)")->execute([$id,$uid]);
  $q=db()->prepare("SELECT * FROM orders WHERE id=?");$q->execute([$id]);$o=$q->fetch();n8n_event('order_on_the_way',['pedido'=>$o]);
  json_out(['ok'=>true]);
  break;
case 'courier_ocorrencia': // motoboy: cliente não localizado / não atende + obs
  if(!require_role('motoboy')) json_out(['ok'=>false]);
  $id=(int)$_POST['id'];
  db()->prepare("UPDATE orders SET ocorrencia=?, ocorrencia_obs=?, status='ocorrencia' WHERE id=?")
    ->execute([$_POST['motivo']??'', $_POST['obs']??'', $id]);
  $o=db()->prepare("SELECT * FROM orders WHERE id=?"); $o->execute([$id]); $o=$o->fetch();
  n8n_event('order_ocorrencia',['pedido'=>$o]);
  json_out(['ok'=>true]);
  break;

case 'waiter':
  if(!require_role('garcom')){ render('login',['role'=>'garcom','titulo'=>'App do garçom']); break; }
  if(session_status()===PHP_SESSION_NONE) session_start();
  if(!empty($_SESSION['user_nome'])) $_SESSION['garcom_nome']=$_SESSION['user_nome'];
  if(empty($_SESSION['garcom_nome'])){
    $garcons = db()->query("SELECT * FROM garcons WHERE ativo=1 ORDER BY nome")->fetchAll();
    render('waiter_pick',['garcons'=>$garcons]); break;
  }
  $tables = db()->query("SELECT * FROM tables_ ORDER BY numero")->fetchAll();
  // marca ocupadas conforme comandas abertas
  $abertas = [];
  foreach(db()->query("SELECT mesa,total FROM orders WHERE canal='garcom' AND status NOT IN ('mesa_rascunho','concluido','cancelado','entregue')")->fetchAll() as $a) $abertas[$a['mesa']]=$a['total'];
  render('waiter',['tables'=>$tables,'abertas'=>$abertas,'garcom'=>$_SESSION['garcom_nome']]);
  break;

case 'waiter_nome': // POST nome (define o atendente da sessão)
  if(!require_role('garcom')){ redirect('?r=waiter'); }
  if(session_status()===PHP_SESSION_NONE) session_start();
  $_SESSION['garcom_nome'] = trim($_POST['nome'] ?? 'Garçom');
  redirect('?r=waiter');
  break;

case 'waiter_sair_nome':
  _start_session(); // garante que a sessão com o nome certo (ex: rv_barbaburguer) seja destruída
  session_unset(); session_destroy(); redirect('?r=waiter');
  break;

case 'waiter_comanda': // GET mesa
  if(!require_role('garcom')){ redirect('?r=waiter'); }
  if(session_status()===PHP_SESSION_NONE) session_start();
  $mesa=$_GET['mesa'] ?? '';
  $ord = mesa_order($mesa);
  $itens = [];
  if($ord){ $q=db()->prepare("SELECT * FROM order_items WHERE order_id=? ORDER BY id"); $q->execute([$ord['id']]); $itens=$q->fetchAll(); }
  $cats = db()->query("SELECT * FROM categories ORDER BY ordem")->fetchAll();
  $prods= db()->query("SELECT * FROM products WHERE ativo=1 ORDER BY nome")->fetchAll();
  render('waiter_comanda',['mesa'=>$mesa,'ord'=>$ord,'itens'=>$itens,'cats'=>$cats,'prods'=>$prods,'garcom'=>$_SESSION['garcom_nome']??'','podeFechar'=>garcom_pode_fechar()]);
  break;

case 'waiter_add': // POST mesa, product_id
  if(!require_role('garcom')) json_out(['ok'=>false]);
  if(session_status()===PHP_SESSION_NONE) session_start();
  $mesa=$_POST['mesa']; $pid=(int)$_POST['product_id'];
  $p=db()->prepare("SELECT * FROM products WHERE id=?"); $p->execute([$pid]); $p=$p->fetch();
  if(!$p) json_out(['ok'=>false]);
  $ord=mesa_order($mesa);
  $oid = $ord ? $ord['id'] : mesa_order_criar($mesa,$_SESSION['garcom_nome']??'Garçom');
  // se já existe o item, soma qtd
  $ex=db()->prepare("SELECT * FROM order_items WHERE order_id=? AND nome=?"); $ex->execute([$oid,$p['nome']]); $ex=$ex->fetch();
  if($ex) db()->prepare("UPDATE order_items SET qtd=qtd+1 WHERE id=?")->execute([$ex['id']]);
  else db()->prepare("INSERT INTO order_items(order_id,nome,qtd,preco) VALUES(?,?,1,?)")->execute([$oid,$p['nome'],$p['preco']]);
  $tot=mesa_recalcula($oid);
  json_out(['ok'=>true,'total'=>$tot]);
  break;

case 'waiter_generate':
  if(!require_role('garcom')) json_out(['ok'=>false]);
  $mesa=$_POST['mesa']??'';$ord=mesa_order($mesa);if(!$ord)json_out(['ok'=>false]);
  if($ord['status']==='mesa_rascunho') order_set_status($ord['id'],'aceito','garcom');
  db()->prepare("UPDATE tables_ SET status='ocupada' WHERE numero=?")->execute([$mesa]);
  json_out(['ok'=>true]);
  break;

case 'waiter_remove': // POST item_id, mesa
  if(!require_role('garcom')) json_out(['ok'=>false]);
  $iid=(int)$_POST['item_id'];
  $it=db()->prepare("SELECT * FROM order_items WHERE id=?"); $it->execute([$iid]); $it=$it->fetch();
  if($it){
    if($it['qtd']>1) db()->prepare("UPDATE order_items SET qtd=qtd-1 WHERE id=?")->execute([$iid]);
    else db()->prepare("DELETE FROM order_items WHERE id=?")->execute([$iid]);
    mesa_recalcula($it['order_id']);
  }
  json_out(['ok'=>true]);
  break;

case 'waiter_fechar': // POST mesa (fecha conta da mesa)
  if(!require_role('garcom') && !require_role('admin') && !require_role('caixa')){ redirect('?r=waiter'); }
  if(session_status()===PHP_SESSION_NONE) session_start();
  // o dono decide se garçom pode fechar comanda (Config > Operação)
  $ehGarcom = (($_SESSION['user_role']??'')==='garcom') || (!require_role('admin') && !require_role('caixa'));
  if($ehGarcom && !garcom_pode_fechar()){
    if(strpos($_SERVER['HTTP_ACCEPT']??'','application/json')!==false) json_out(['ok'=>false,'erro'=>'Somente o caixa pode fechar comandas.']);
    redirect('?r=waiter_comanda&mesa='.urlencode($_POST['mesa']??'').'&erro=sem_permissao');
  }
  $mesa=$_POST['mesa'];
  $ord=mesa_order($mesa);
  if(!$ord){ $q=db()->prepare("SELECT * FROM orders WHERE tipo='mesa' AND mesa=? AND status NOT IN ('concluido','cancelado') ORDER BY id DESC LIMIT 1");$q->execute([$mesa]);$ord=$q->fetch(); }
  if($ord){
    $metodos=['dinheiro','pix','debito','credito'];
    $metodo=in_array($_POST['metodo']??'',$metodos,true)?$_POST['metodo']:'dinheiro';
    $quem = operador_atual();
    db()->prepare("UPDATE orders SET status='concluido', pagamento_metodo=?, fechado_por=?, recebido_por=?, fechado_em=datetime('now','localtime') WHERE id=?")
      ->execute([$metodo,$quem,$quem,$ord['id']]);
    order_mark_paid($ord['id']);
    $ord['fechado_por']=$quem; $ord['recebido_por']=$quem;
    n8n_event('mesa_fechada',['pedido'=>$ord,'fechado_por'=>$quem]);
  }
  db()->prepare("UPDATE tables_ SET status='livre' WHERE numero=?")->execute([$mesa]);
  redirect(($_POST['origem'] ?? '')==='admin' ? '?r=admin_tables' : '?r=waiter');
  break;

case 'admin_set_status': // POST id,status (arrastar/mudar no painel)
  if(!require_role('admin')) json_out(['ok'=>false]);
  $status=$_POST['status']??'';
  if(!in_array($status,['aceito','concluido','entregue'],true)) json_out(['ok'=>false]);
  $id=(int)$_POST['id'];$q=db()->prepare("SELECT * FROM orders WHERE id=?");$q->execute([$id]);$current=$q->fetch();
  if(!$current||($status==='aceito'&&$current['status']!=='novo')||($status==='concluido'&&$current['status']!=='pronto'))json_out(['ok'=>false]);
  if(in_array($status,['concluido','entregue'],true)&&payment_blocks_completion($current))json_out(['ok'=>false,'erro'=>'Pagamento online pendente. Confirme PAGO antes de finalizar.']);
  order_set_status($id,$status,'admin');
  json_out(['ok'=>true]);
  break;

case 'admin_reject_order':
  if(!require_role('admin')) json_out(['ok'=>false]);
  $id=(int)($_POST['id']??0); $q=db()->prepare("SELECT status FROM orders WHERE id=?");$q->execute([$id]);$o=$q->fetch();
  if(!$o||$o['status']!=='novo') json_out(['ok'=>false,'erro'=>'Somente pedidos novos podem ser recusados.']);
  order_set_status($id,'cancelado','admin'); json_out(['ok'=>true]);
  break;

case 'admin_set_payment':
  if(!require_role('admin')) json_out(['ok'=>false]);
  $id=(int)($_POST['id']??0);
  $metodos=['pix','pix_entrega','cartao_online','cartao_entrega','dinheiro'];
  $metodo=in_array($_POST['metodo']??'',$metodos,true)?$_POST['metodo']:'pix';
  $status=($_POST['pagamento_status']??'pendente')==='pago'?'pago':'pendente';
  $atual=db()->prepare("SELECT status,pagamento_status FROM orders WHERE id=?"); $atual->execute([$id]); $atual=$atual->fetch();
  if(!$atual || !in_array($atual['status'],['novo','aceito','em_preparo','pronto'],true))
    json_out(['ok'=>false,'erro'=>'A forma de pagamento só pode ser alterada antes do despacho.']);
  if($atual && $atual['pagamento_status']==='pago') $status='pago';
  db()->prepare("UPDATE orders SET pagamento_metodo=?, atualizado_em=datetime('now','localtime') WHERE id=?")->execute([$metodo,$id]);
  if($status==='pago') order_mark_paid($id);
  else db()->prepare("UPDATE orders SET pagamento_status='pendente' WHERE id=?")->execute([$id]);
  json_out(['ok'=>true]);
  break;

case 'admin_cancel_order':
  if(!require_role('admin')) json_out(['ok'=>false]);
  $cancelMode=setting_get('cancel_security','none');
  if($cancelMode==='master'){
    $senha=(string)($_POST['senha']??'');
    if($senha===''||!hash_equals((string)setting_get('cancel_pin','1234'),$senha))json_out(['ok'=>false,'erro'=>'Senha de cancelamento incorreta.']);
  }
  $id=(int)($_POST['id']??0);
  $q=db()->prepare("SELECT status FROM orders WHERE id=?"); $q->execute([$id]); $o=$q->fetch();
  if(!$o || in_array($o['status'],['entregue','concluido','cancelado'],true)) json_out(['ok'=>false,'erro'=>'Pedido não pode ser cancelado neste estágio.']);
  order_set_status($id,'cancelado','admin');
  json_out(['ok'=>true]);
  break;

case 'admin_set_pago': // POST id
  if(!require_role('admin')) json_out(['ok'=>false]);
  order_mark_paid((int)$_POST['id']);
  json_out(['ok'=>true]);
  break;

case 'admin_acerto_pago': // POST id  OU  courier_id (acerta tudo do motoboy)
  // Confirma que o motoboy entregou o dinheiro / comprovante da maquininha no
  // caixa. Marca o pedido como pago, registra a venda no caixa e baixa a pendência.
  // Liberado para caixa e admin (o acerto acontece fisicamente no caixa).
  if(!require_role('caixa')) json_out(['ok'=>false]);
  $quem = $_SESSION['user_nome'] ?? 'admin';
  $ids = [];
  if(!empty($_POST['courier_id'])){
    $q=db()->prepare("SELECT id FROM orders WHERE courier_id=? AND acerto_status='pendente'");
    $q->execute([(int)$_POST['courier_id']]);
    foreach($q->fetchAll() as $row) $ids[]=(int)$row['id'];
  } elseif(!empty($_POST['id'])) $ids[]=(int)$_POST['id'];
  if(!$ids) json_out(['ok'=>false,'erro'=>'Nenhuma pendência encontrada.']);
  foreach($ids as $oid){
    order_mark_paid($oid);
    db()->prepare("UPDATE orders SET acerto_status='ok', acerto_em=datetime('now','localtime'), acerto_por=? WHERE id=?")->execute([$quem,$oid]);
  }
  if(strpos($_SERVER['HTTP_ACCEPT']??'','application/json')!==false) json_out(['ok'=>true,'baixados'=>count($ids)]);
  redirect('?r=admin_couriers');
  break;

case 'admin_feed': // GET -> JSON com pedidos do dia (polling = tempo real)
  if(!require_role('admin')) json_out(['ok'=>false]);
  // Mantém o recuperador funcionando enquanto a central de pedidos está aberta.
  // A função só dispara carrinhos elegíveis e já avisados não são repetidos.
  recovery_process();
  $orders = db()->query("SELECT o.id,o.codigo,o.cliente_nome,o.cliente_fone,o.endereco,o.tipo,o.mesa,o.status,o.total,o.pagamento_metodo,o.pagamento_status,o.acerto_status,o.courier_id,o.canal,o.simulacao,o.ocorrencia,o.ocorrencia_obs,o.bairro,o.atualizado_em,o.aceito, c.nome cnome
                         FROM orders o LEFT JOIN couriers c ON c.id=o.courier_id
                         WHERE (date(o.criado_em)=date('now','localtime') OR o.acerto_status='pendente')
                           AND NOT (o.canal='garcom' AND o.status IN ('mesa_rascunho','mesa_aberta'))
                         ORDER BY o.id DESC")->fetchAll();
  $items = [];
  foreach (db()->query("SELECT order_id,nome,qtd FROM order_items")->fetchAll() as $it)
    $items[$it['order_id']][] = $it['qtd'].'× '.$it['nome'];
  foreach ($orders as &$o) $o['itens'] = implode(' · ', $items[$o['id']] ?? []);
  // Pendências de acerto (inclui dias anteriores: não some do painel até acertar)
  $ac = db()->query("SELECT COUNT(*) c, COALESCE(SUM(total),0) v FROM orders WHERE acerto_status='pendente'")->fetch();
  json_out(['ok'=>true,'orders'=>$orders,'acertos'=>['qtd'=>(int)$ac['c'],'valor'=>(float)$ac['v']],'ts'=>date('H:i:s')]);
  break;

// ---------- NOTIFICAÇÃO PUSH (VAPID) ----------
case 'push_vapid_key': // GET -> chave pública para o navegador se inscrever
  if(!require_role('motoboy') && !require_role('admin')) json_out(['ok'=>false]);
  // Primeira ativação sem etapa escondida no painel: gera a chave uma vez.
  if(!vapid_configured() && webpush_supported()) vapid_generate();
  $k=vapid_public_key();
  json_out(['ok'=>$k!=='' && webpush_supported(),'key'=>$k]);
  break;

case 'push_subscribe': // POST JSON {endpoint, p256dh, auth} -> guarda a inscrição do motoboy
  if(!require_role('motoboy')) json_out(['ok'=>false]);
  $in=json_decode(file_get_contents('php://input'),true)?:[];
  $endpoint=trim($in['endpoint']??''); $p256dh=trim($in['p256dh']??''); $auth=trim($in['auth']??'');
  if($endpoint===''||$p256dh===''||$auth==='') json_out(['ok'=>false]);
  $uid=(int)($_SESSION['user_id']??0);
  db()->prepare("INSERT INTO push_subscriptions(user_id,endpoint,p256dh,auth) VALUES(?,?,?,?)
                 ON CONFLICT(endpoint) DO UPDATE SET user_id=excluded.user_id,p256dh=excluded.p256dh,auth=excluded.auth")
    ->execute([$uid,$endpoint,$p256dh,$auth]);
  json_out(['ok'=>true]);
  break;

case 'push_unsubscribe': // POST endpoint
  if(!require_role('motoboy')) json_out(['ok'=>false]);
  db()->prepare("DELETE FROM push_subscriptions WHERE endpoint=?")->execute([trim($_POST['endpoint']??'')]);
  json_out(['ok'=>true]);
  break;

case 'push_test': // POST -> manda uma notificação de teste pro próprio motoboy logado
  if(!require_role('motoboy')) json_out(['ok'=>false]);
  $uid=(int)($_SESSION['user_id']??0);
  $n=webpush_notify_user($uid,['title'=>'🔔 Teste de notificação','body'=>'Se você recebeu isso, o alerta funciona mesmo com o app fechado ou em outra tela.']);
  json_out(['ok'=>$n>0,'enviados'=>$n,'erro'=>$n>0?'':( !webpush_supported()?webpush_capability_message(): (vapid_configured()?'Nenhuma inscrição ativa neste aparelho ainda. Toque em Ativar alertas primeiro.':'As chaves de notificação ainda não foram geradas pelo administrador.'))]);
  break;

case 'vapid_generate': // POST (admin) -> gera o par de chaves VAPID uma única vez
  if(!require_role('admin')) json_out(['ok'=>false]);
  if(!webpush_supported()) json_out(['ok'=>false,'erro'=>webpush_capability_message()]);
  $ok=vapid_generate();
  json_out(['ok'=>$ok,'erro'=>$ok?'':'Não foi possível gerar as chaves neste servidor.']);
  break;

// ---------- APP DO MOTOBOY ----------
case 'courier_toggle_online': // POST online=1|0 -> motoboy liga/desliga a rota
  if(!require_role('motoboy')) json_out(['ok'=>false]);
  if(session_status()===PHP_SESSION_NONE) session_start();
  $on=($_POST['online']??'0')==='1'?1:0;
  $uid=(int)($_SESSION['user_id']??0);
  db()->prepare("UPDATE couriers SET online=?, online_em=datetime('now','localtime') WHERE user_id=?")->execute([$on,$uid]);
  $cq=db()->prepare("SELECT * FROM couriers WHERE user_id=? LIMIT 1"); $cq->execute([$uid]); $c=$cq->fetch();
  if($c) n8n_event($on?'courier_online':'courier_offline',['motoboy'=>$c]);
  json_out(['ok'=>true,'online'=>$on]);
  break;

case 'courier_feed': // GET -> JSON das entregas do motoboy (polling = bipe de corrida nova)
  if(!require_role('motoboy')) json_out(['ok'=>false]);
  if(session_status()===PHP_SESSION_NONE) session_start();
  $uid=(int)($_SESSION['user_id']??0);
  $cq=db()->prepare("SELECT * FROM couriers WHERE user_id=? LIMIT 1"); $cq->execute([$uid]); $c=$cq->fetch();
  if(!$c) json_out(['ok'=>true,'online'=>0,'ids'=>[],'novas'=>0]);
  $q=db()->prepare("SELECT id,codigo,aceito FROM orders WHERE tipo='entrega' AND courier_id=? AND status='saiu_entrega' AND date(criado_em)=date('now','localtime') ORDER BY id");
  $q->execute([$c['id']]); $ds=$q->fetchAll();
  $aceitas=count(array_filter($ds,fn($x)=>!empty($x['aceito'])));
  json_out(['ok'=>true,'online'=>(int)$c['online'],'ids'=>array_map(fn($x)=>(int)$x['id'],$ds),
            'pendentes'=>array_values(array_map(fn($x)=>(int)$x['id'],array_filter($ds,fn($x)=>empty($x['aceito'])))),
            'aceitas'=>$aceitas,'novas'=>count(array_filter($ds,fn($x)=>empty($x['aceito']))),'ts'=>time()]);
  break;

case 'courier':
  if(!require_role('motoboy')){ render('login',['role'=>'motoboy','titulo'=>'App do motoboy']); break; }
  // entregas = pedidos de entrega que saíram ou estão prontos
  $uid=(int)($_SESSION['user_id']??0); $cq=db()->prepare("SELECT * FROM couriers WHERE user_id=? LIMIT 1");$cq->execute([$uid]);$courier=$cq->fetch();
  $deliveries=[];$stats=['hoje'=>0,'mes'=>0];
  if($courier){
    $q=db()->prepare("SELECT * FROM orders WHERE tipo='entrega' AND courier_id=? AND status='saiu_entrega' AND date(criado_em)=date('now','localtime') ORDER BY id");$q->execute([$courier['id']]);$deliveries=$q->fetchAll();
    $q=db()->prepare("SELECT COUNT(*) c FROM orders WHERE courier_id=? AND status='entregue' AND date(atualizado_em)=date('now','localtime')");$q->execute([$courier['id']]);$stats['hoje']=$q->fetch()['c'];
    $q=db()->prepare("SELECT COUNT(*) c FROM orders WHERE courier_id=? AND status='entregue' AND strftime('%Y-%m',atualizado_em)=strftime('%Y-%m','now','localtime')");$q->execute([$courier['id']]);$stats['mes']=$q->fetch()['c'];
  }
  render('courier',['deliveries'=>$deliveries,'courier'=>$courier,'stats'=>$stats]);
  break;

case 'courier_delivered': // ⭐ POST id  -> motoboy marca entregue -> aparece no painel
  if(!require_role('motoboy')) json_out(['ok'=>false]);
  $id=(int)$_POST['id'];
  $check=db()->prepare("SELECT o.* FROM orders o JOIN couriers c ON c.id=o.courier_id WHERE o.id=? AND c.user_id=?"); $check->execute([$id,(int)($_SESSION['user_id']??0)]); $antes=$check->fetch();
  if(!$antes || empty($antes['aceito']) || $antes['status']!=='saiu_entrega') json_out(['ok'=>false,'erro'=>'Aceite a entrega antes de concluí-la.']);
  // A conferência do pagamento pertence ao operador antes do despacho.
  // Se o pedido chegou ao motoboy, ele já foi liberado pelo painel.
  $o = order_set_status($id,'entregue','motoboy');
  if(!empty($o['courier_id'])){
    $rest=db()->prepare("SELECT COUNT(*) c FROM orders WHERE courier_id=? AND status='saiu_entrega'"); $rest->execute([$o['courier_id']]);
    if((int)$rest->fetch()['c']===0) db()->prepare("UPDATE couriers SET status='livre' WHERE id=?")->execute([$o['courier_id']]);
  }
  // Pagamento recebido na entrega fica PENDENTE DE ACERTO: o motoboy precisa
  // entregar o dinheiro/comprovante da maquininha no caixa. O operador confirma
  // no painel (coluna Finalizados) ou na aba Entregas, e só então vira "pago".
  if (in_array($o['pagamento_metodo'],['dinheiro','cartao_entrega','pix_entrega'],true) && $o['pagamento_status']!=='pago'){
    db()->prepare("UPDATE orders SET acerto_status='pendente' WHERE id=?")->execute([$id]);
  } else {
    db()->prepare("UPDATE orders SET acerto_status='ok', acerto_em=datetime('now','localtime') WHERE id=?")->execute([$id]);
  }
  n8n_event('order_delivered',['pedido'=>$o]); // n8n -> WhatsApp "entregue"
  json_out(['ok'=>true]);
  break;

// ---------- WEBHOOKS ----------
case 'payment_webhook':
  $in=json_decode(file_get_contents('php://input'),true)?:$_POST;
  $secret=(string)($_SERVER['HTTP_X_RV_WEBHOOK_SECRET']??($in['secret']??''));
  $expected=(string)setting_get('payment_webhook_secret','');
  if($expected===''||!hash_equals($expected,$secret))json_out(['ok'=>false,'erro'=>'Webhook não autorizado.']);
  $status=strtolower((string)($in['status']??''));if(!in_array($status,['pago','paid','approved','confirmed'],true))json_out(['ok'=>true,'ignorado'=>true]);
  $oid=(int)($in['order_id']??0);$codigo=(string)($in['codigo']??'');
  if($oid){$q=db()->prepare("SELECT * FROM orders WHERE id=?");$q->execute([$oid]);}else{$q=db()->prepare("SELECT * FROM orders WHERE codigo=?");$q->execute([$codigo]);}$o=$q->fetch();
  if(!$o||!payment_is_online($o['pagamento_metodo']))json_out(['ok'=>false,'erro'=>'Pedido online não encontrado.']);
  db()->prepare("UPDATE orders SET pagamento_provider_id=?,pagamento_origem='webhook' WHERE id=?")->execute([(string)($in['payment_id']??$in['id']??''),$o['id']]);
  order_mark_paid((int)$o['id']);n8n_event('payment_confirmed',['pedido'=>$o]);json_out(['ok'=>true,'order_id'=>(int)$o['id']]);

case 'webhook_mercadopago':
  // TODO: validar assinatura, buscar pagamento, marcar orders.pagamento_status='pago'
  json_out(['ok'=>true,'stub'=>'mercadopago']);
  break;
case 'webhook_ifood':
  // TODO: ler evento iFood, inserir/atualizar orders com canal='ifood'
  json_out(['ok'=>true,'stub'=>'ifood']);
  break;

// ---------- logout ----------
// ============================================================
// SaaS — Painel do dono da plataforma (login próprio, separado do restaurante)
// ============================================================
case 'saas_login':
  if (session_status()===PHP_SESSION_NONE) session_start();
  if($method==='POST'){
    if(hash_equals(saas_master_pass(), (string)($_POST['senha']??''))){ $_SESSION['saas_ok']=true; redirect('?r=saas'); }
    saas_render('saas_login',['erro'=>'Senha incorreta.']); break;
  }
  saas_render('saas_login',['erro'=>'']); break;

case 'saas_logout':
  if (session_status()===PHP_SESSION_NONE) session_start();
  unset($_SESSION['saas_ok']); redirect('?r=saas_login'); break;

// ---------- RECUPERAÇÃO DE SENHA DO PAINEL (tenant) ----------
case 'saas_forgot':
  if($method==='POST'){
    $emailR=strtolower(trim($_POST['email']??''));
    $mq=master_db()->prepare("SELECT id,slug,responsavel,whatsapp FROM saas_clients WHERE email=? AND email<>'' LIMIT 1");
    $mq->execute([$emailR]); $mc=$mq->fetch();
    if($mc && $mc['slug']!==''){
      $token=bin2hex(random_bytes(20));
      $expires=time()+3600;
      master_db()->prepare("INSERT INTO settings(chave,valor) VALUES(?,?) ON CONFLICT(chave) DO UPDATE SET valor=excluded.valor")
        ->execute(['reset__'.$token, $mc['slug'].'|'.$expires.'|'.$emailR]);
      $schemeR=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';
      $hostR=$_SERVER['HTTP_HOST']??'pederv.com.br';
      $link=$schemeR.'://'.$hostR.'/painel/'.$mc['slug'].'?r=saas_reset&token='.$token;
      // E-mail via PHP mail()
      $to=$emailR; $subject='Recuperação de senha — PedeRV';
      $body="Olá, {$mc['responsavel']}!\n\nRecebemos uma solicitação de recuperação de senha para o painel do seu delivery.\n\nClique no link abaixo para criar uma nova senha (válido por 1 hora):\n\n{$link}\n\nSe não foi você, ignore este e-mail.\n\nEquipe PedeRV";
      @mail($to,$subject,$body,"From: noreply@pederv.com.br\r\nContent-Type: text/plain; charset=UTF-8");
      // WhatsApp fallback
      if($mc['whatsapp']!=='' && evolution_configured())
        @evolution_send_text($mc['whatsapp'],"🔐 Recuperação de senha PedeRV\n\nClique no link para criar sua nova senha (válido 1h):\n{$link}");
    }
    // Mesmo sem e-mail cadastrado, mostra mensagem genérica (evita enumeração)
    render('forgot_sent',['link'=>$link??'']);
    break;
  }
  render('forgot_form',[]);
  break;

case 'saas_reset':
  $tok=preg_replace('/[^a-f0-9]/','',trim($_GET['token']??$_POST['token']??''));
  $stored=master_db()->prepare("SELECT valor FROM settings WHERE chave=?");
  $stored->execute(['reset__'.$tok]); $sv=$stored->fetch();
  [$slugR,$expiresR,$emailR]=array_pad(explode('|',$sv['valor']??'',3),3,'');
  if(!$sv||time()>(int)$expiresR||$slugR===''){
    render('reset_invalid',[]); break;
  }
  if($method==='POST'){
    $novaSenha=(string)($_POST['senha']??'');
    if(mb_strlen($novaSenha)<4){ render('reset_form',['token'=>$tok,'erro'=>'Senha deve ter pelo menos 4 caracteres.']); break; }
    // Atualiza no banco do tenant
    $tf=__DIR__.'/data/'.$slugR.'.sqlite';
    if(file_exists($tf)){
      $td=new PDO('sqlite:'.$tf);$td->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
      $td->prepare("UPDATE users SET senha_hash=? WHERE usuario=?")->execute([password_hash($novaSenha,PASSWORD_DEFAULT),$emailR]);
    }
    master_db()->prepare("DELETE FROM settings WHERE chave=?")->execute(['reset__'.$tok]);
    redirect('/painel/'.$slugR.'?r=admin&reset=1');
    break;
  }
  render('reset_form',['token'=>$tok,'erro'=>'']);
  break;

case 'saas':
  saas_require(); saas_refresh_status();
  // Notifica clientes no dia 6 do trial (1 dia antes de acabar) via WhatsApp
  try {
    master_db()->exec("ALTER TABLE saas_clients ADD COLUMN trial_aviso_enviado INTEGER DEFAULT 0");
  } catch(Exception $e){}
  $avisoAmanha=date('Y-m-d',strtotime('+1 day'));
  $pendAviso=master_db()->query("SELECT * FROM saas_clients WHERE status='trial' AND trial_ate='$avisoAmanha' AND (trial_aviso_enviado IS NULL OR trial_aviso_enviado=0)")->fetchAll();
  foreach($pendAviso as $_tc){
    if(!empty($_tc['whatsapp'])){
      $msgAviso="Olá! 😊 Seu teste grátis da Pederv está acabando. Esperamos que o nosso cardápio digital esteja ajudando o seu negócio! Para continuar usando a plataforma sem interrupções, basta finalizar sua assinatura. Se precisar de ajuda, é só responder esta mensagem. Estamos prontos para ter você com a gente! 🚀";
      $evoUrl=setting_get('saas_evo_url',''); $evoKey=setting_get('saas_evo_key',''); $evoInst=setting_get('saas_evo_instance','');
      if($evoUrl && $evoKey && $evoInst){
        $waNum=preg_replace('/\D/','',(string)$_tc['whatsapp']); if(strlen($waNum)<=11)$waNum='55'.$waNum;
        $url=rtrim($evoUrl,'/').'/message/sendText/'.rawurlencode($evoInst);
        $ctx=stream_context_create(['http'=>['method'=>'POST','header'=>"apikey: $evoKey\r\nContent-Type: application/json\r\n",'content'=>json_encode(['number'=>$waNum,'text'=>$msgAviso,'delay'=>300]),'ignore_errors'=>true,'timeout'=>10]]);
        @file_get_contents($url,false,$ctx);
      }
    }
    master_db()->prepare("UPDATE saas_clients SET trial_aviso_enviado=1 WHERE id=?")->execute([(int)$_tc['id']]);
  }
  $m=saas_metrics();
  $vencendo=master_db()->query("SELECT * FROM saas_clients WHERE status IN('ativo','trial') ORDER BY COALESCE(proximo_venc,trial_ate) ASC LIMIT 8")->fetchAll();
  $bloqueados=master_db()->query("SELECT * FROM saas_clients WHERE status='bloqueado' ORDER BY bloqueado_em DESC LIMIT 8")->fetchAll();
  $ultimosPg=master_db()->query("SELECT p.*, c.restaurante FROM saas_payments p JOIN saas_clients c ON c.id=p.client_id WHERE p.status='pago' ORDER BY p.id DESC LIMIT 8")->fetchAll();
  $receitaMeses=master_db()->query("SELECT substr(pago_em,1,7) mes, COALESCE(SUM(valor),0) v, COUNT(*) n FROM saas_payments WHERE status='pago' GROUP BY mes ORDER BY mes DESC LIMIT 6")->fetchAll();
  saas_render('saas_dashboard',['m'=>$m,'vencendo'=>$vencendo,'bloqueados'=>$bloqueados,'ultimosPg'=>$ultimosPg,'receitaMeses'=>array_reverse($receitaMeses)]); break;

case 'saas_clients':
  saas_require(); saas_refresh_status();
  $f=trim($_GET['q']??''); $st=$_GET['st']??'';
  $sql="SELECT * FROM saas_clients WHERE 1=1"; $p=[];
  if($f!==''){ $sql.=" AND (restaurante LIKE ? OR responsavel LIKE ? OR whatsapp LIKE ? OR email LIKE ?)"; $like='%'.$f.'%'; $p=[$like,$like,$like,$like]; }
  if(in_array($st,['trial','ativo','bloqueado','cancelado'],true)){ $sql.=" AND status=?"; $p[]=$st; }
  $sql.=" ORDER BY CASE status WHEN 'bloqueado' THEN 0 WHEN 'trial' THEN 1 WHEN 'ativo' THEN 2 ELSE 3 END, restaurante";
  $q=db()->prepare($sql); $q->execute($p);
  saas_render('saas_clients',['clientes'=>$q->fetchAll(),'q'=>$f,'st'=>$st,'m'=>saas_metrics()]); break;

case 'saas_client':
  saas_require(); saas_refresh_status();
  $q=db()->prepare("SELECT * FROM saas_clients WHERE id=?"); $q->execute([(int)($_GET['id']??0)]); $c=$q->fetch();
  if(!$c){ redirect('?r=saas_clients'); }
  $pg=db()->prepare("SELECT * FROM saas_payments WHERE client_id=? ORDER BY id DESC"); $pg->execute([$c['id']]);
  saas_render('saas_client',['c'=>$c,'pagamentos'=>$pg->fetchAll()]); break;

case 'saas_client_save':
  saas_require();
  $id=(int)($_POST['id']??0);
  $trialDias=(int)setting_get('saas_trial_dias','7');
  $plano=in_array($_POST['plano']??'pro',['pro','premium','pro_anual','premium_anual'],true)?$_POST['plano']:'pro';
  $valor=saas_plan_price($plano); // sempre automático pelo plano
  $slug_novo=preg_replace('/[^a-z0-9_-]/','',strtolower(trim($_POST['client_slug']??'')));
  $login=trim($_POST['email']??'');
  $senha_nova=trim($_POST['senha_nova']??'');
  $restaurante=trim($_POST['restaurante']??'');
  $dados=[$restaurante,trim($_POST['responsavel']??''),$login,trim($_POST['whatsapp']??''),
    trim($_POST['dominio']??''),trim($_POST['cidade']??''),trim($_POST['uf']??''),$plano,$valor,max(1,min(28,(int)($_POST['dia_vencimento']??10))),trim($_POST['obs']??''),$slug_novo];
  if($id){
    db()->prepare("UPDATE saas_clients SET restaurante=?,responsavel=?,email=?,whatsapp=?,dominio=?,cidade=?,uf=?,plano=?,valor_mensal=?,dia_vencimento=?,obs=?,slug=?,atualizado_em=datetime('now','localtime') WHERE id=?")
      ->execute(array_merge($dados,[$id]));
    if($slug_novo && $login && $senha_nova){
      saas_provision_tenant($slug_novo,$login,$senha_nova,$restaurante);
    } elseif($slug_novo){
      $tf=__DIR__.'/data/'.$slug_novo.'.sqlite';
      if(!file_exists($tf)||filesize($tf)===0) @file_put_contents($tf,'');
    }
    header('Location: ?r=saas_client&id='.$id); exit;
  } else {
    $trialAte=date('Y-m-d', strtotime('+'.$trialDias.' days'));
    db()->prepare("INSERT INTO saas_clients(restaurante,responsavel,email,whatsapp,dominio,cidade,uf,plano,valor_mensal,dia_vencimento,obs,slug,status,trial_ate) VALUES(?,?,?,?,?,?,?,?,?,?,?,?, 'trial', ?)")
      ->execute(array_merge($dados,[$trialAte]));
    $newId=db()->lastInsertId();
    if($slug_novo && $login && $senha_nova){
      saas_provision_tenant($slug_novo,$login,$senha_nova,$restaurante);
    } elseif($slug_novo){
      $tf=__DIR__.'/data/'.$slug_novo.'.sqlite';
      if(!file_exists($tf)||filesize($tf)===0) @file_put_contents($tf,'');
    }
    header('Location: ?r=saas_client&id='.$newId); exit;
  }
  break;

case 'saas_pay':
  saas_require();
  $cid=(int)($_POST['client_id']??0);
  $q=db()->prepare("SELECT valor_mensal FROM saas_clients WHERE id=?"); $q->execute([$cid]); $cl=$q->fetch();
  $valor=$_POST['valor']!==''?(float)str_replace(',','.',$_POST['valor']):(float)($cl['valor_mensal']??0);
  saas_registrar_pagamento($cid,$valor,in_array($_POST['metodo']??'pix',['pix','cartao','boleto','dinheiro','transferencia'],true)?$_POST['metodo']:'pix',trim($_POST['obs']??''));
  redirect('?r=saas_client&id='.$cid);
  break;

case 'saas_block':
  saas_require();
  $cid=(int)($_POST['id']??0);
  db()->prepare("UPDATE saas_clients SET status='bloqueado', bloqueio_manual=1, bloqueado_em=datetime('now','localtime') WHERE id=?")->execute([$cid]);
  redirect('?r=saas_client&id='.$cid);
  break;

case 'saas_unblock':
  saas_require();
  $cid=(int)($_POST['id']??0);
  // Desbloqueio manual: reativa e dá carência até o próximo vencimento calculado.
  $q=db()->prepare("SELECT * FROM saas_clients WHERE id=?"); $q->execute([$cid]); $c=$q->fetch();
  $prox = (!empty($c['proximo_venc']) && $c['proximo_venc']>=date('Y-m-d')) ? $c['proximo_venc'] : date('Y-m-d', strtotime('+7 days'));
  db()->prepare("UPDATE saas_clients SET status='ativo', bloqueio_manual=0, bloqueado_em=NULL, proximo_venc=? WHERE id=?")->execute([$prox,$cid]);
  redirect('?r=saas_client&id='.$cid);
  break;

case 'saas_cancel':
  saas_require();
  $cid=(int)($_POST['id']??0);
  db()->prepare("UPDATE saas_clients SET status='cancelado', bloqueado_em=datetime('now','localtime') WHERE id=?")->execute([$cid]);
  redirect('?r=saas_client&id='.$cid);
  break;

case 'saas_delete_client':
  saas_require();
  $cid=(int)($_POST['id']??0);
  $c=db()->prepare("SELECT slug,status FROM saas_clients WHERE id=?"); $c->execute([$cid]); $row=$c->fetch();
  if($row && $row['status']==='cancelado'){
    db()->prepare("DELETE FROM saas_payments WHERE client_id=?")->execute([$cid]);
    db()->prepare("DELETE FROM saas_clients WHERE id=?")->execute([$cid]);
  }
  redirect('?r=saas_clients');
  break;

case 'saas_estorno':
  saas_require();
  $pid=(int)($_POST['payment_id']??0);
  $cid=(int)($_POST['client_id']??0);
  if($pid) db()->prepare("DELETE FROM saas_payments WHERE id=?")->execute([$pid]);
  redirect('?r=saas_client&id='.$cid);
  break;

case 'saas_settings':
  saas_require();
  if($method==='POST'){
    setting_set('saas_preco_pro', str_replace(',','.',$_POST['preco_pro']??'89.90'));
    setting_set('saas_preco_premium', str_replace(',','.',$_POST['preco_premium']??'149.90'));
    setting_set('saas_trial_dias', (string)max(1,(int)($_POST['trial_dias']??7)));
    if(!empty($_POST['nova_senha'])) setting_set('saas_master_pass', trim($_POST['nova_senha']));
    // uazapi global config — salva sempre no master db via setting_set (sem slug = master)
    if(isset($_POST['saas_uaz_url'])) setting_set('saas_uaz_url', trim($_POST['saas_uaz_url']));
    if(isset($_POST['saas_uaz_key']) && trim($_POST['saas_uaz_key'])!=='') setting_set('saas_uaz_key', trim($_POST['saas_uaz_key']));
    if(isset($_POST['saas_scraper_key']) && trim($_POST['saas_scraper_key'])!=='') setting_set('saas_scraper_key', trim($_POST['saas_scraper_key']));
    if(isset($_POST['saas_payment_api_key']) && trim($_POST['saas_payment_api_key'])!=='') setting_set('saas_payment_api_key', trim($_POST['saas_payment_api_key']));
    if(isset($_POST['saas_payment_provider'])) setting_set('saas_payment_provider', trim($_POST['saas_payment_provider']));
    if(isset($_POST['saas_pix_key'])) setting_set('saas_pix_key', trim($_POST['saas_pix_key']));
    if(isset($_POST['saas_pix_nome'])) setting_set('saas_pix_nome', trim($_POST['saas_pix_nome']));
    redirect('?r=saas_settings&salvo=1');
  }
  saas_render('saas_settings',['salvo'=>!empty($_GET['salvo'])]); break;

case 'uazapi_qr':
  _start_session();
  $slug=current_slug();
  if(!$slug||!uazapi_configured()) json_out(['ok'=>false,'erro'=>'não configurado']);
  // Cria instância para o tenant se não existir (idempotente)
  uazapi_request('POST','instance/create',['instanceName'=>$slug,'integration'=>'WHATSAPP-BAILEYS','qrcode'=>true]);
  // Busca QR Code
  $uazR=uazapi_request('GET','instance/'.$slug.'/qrcode');
  $b64=$uazR['data']['base64']??$uazR['data']['qrcode']['base64']??$uazR['data']['code']??'';
  json_out(['ok'=>$uazR['ok'],'base64'=>$b64,'erro'=>$uazR['erro']??'']);
  break;

case 'uazapi_status':
  _start_session();
  $slug=current_slug();
  if(!$slug||!uazapi_configured()) json_out(['ok'=>true,'state'=>'not_configured','connected'=>false]);
  $uazR=uazapi_request('GET','instance/'.$slug.'/connectionState');
  $state=$uazR['data']['state']??$uazR['data']['connectionState']['state']??'close';
  json_out(['ok'=>true,'state'=>$state,'connected'=>$state==='open']);
  break;

case 'uazapi_disconnect':
  _start_session();
  $slug=current_slug();
  if($slug&&uazapi_configured()) uazapi_request('DELETE','instance/'.$slug.'/logout');
  json_out(['ok'=>true]);
  break;

case 'saas_bot':
  saas_require();
  master_db()->exec("CREATE TABLE IF NOT EXISTS saas_bot_replies(id INTEGER PRIMARY KEY AUTOINCREMENT, gatilho TEXT, resposta TEXT, ativo INTEGER DEFAULT 1)");
  $bot=master_db()->query("SELECT * FROM saas_bot_replies ORDER BY id ASC")->fetchAll();
  saas_render('saas_bot',['bot'=>$bot,'salvo'=>!empty($_GET['salvo'])]); break;

case 'saas_bot_save':
  saas_require();
  master_db()->exec("CREATE TABLE IF NOT EXISTS saas_bot_replies(id INTEGER PRIMARY KEY AUTOINCREMENT, gatilho TEXT, resposta TEXT, ativo INTEGER DEFAULT 1)");
  $tab=$_POST['_tab']??'';
  if($tab==='config'){
    setting_set('saas_evo_url',  trim($_POST['saas_evo_url']??''));
    setting_set('saas_evo_key',  trim($_POST['saas_evo_key']??''));
    setting_set('saas_evo_instance', trim($_POST['saas_evo_instance']??''));
    setting_set('saas_evo_phone', trim($_POST['saas_evo_phone']??''));
    setting_set('saas_wa_bot_active', isset($_POST['saas_wa_bot_active'])?'1':'0');
    setting_set('saas_notify_phones', trim($_POST['saas_notify_phones']??''));
    setting_set('saas_bot_delay', trim($_POST['saas_bot_delay']??'0'));
    setting_set('saas_human_timeout', trim($_POST['saas_human_timeout']??'4'));
  } else {
    $botJson=trim($_POST['bot_json']??'');
    $botData=$botJson?json_decode($botJson,true):null;
    if(is_array($botData)&&count($botData)>0){
      master_db()->exec("DELETE FROM saas_bot_replies");
      $ins=master_db()->prepare("INSERT INTO saas_bot_replies(gatilho,resposta,ativo) VALUES(?,?,?)");
      foreach($botData as $m){
        $g=trim($m['gatilho']??'');$r=trim($m['resposta']??'');
        if($g!==''&&$r!=='')$ins->execute([$g,$r,empty($m['ativo'])?0:1]);
      }
      json_out(['ok'=>true,'saved'=>count($botData)]);
    } else {
      json_out(['ok'=>false,'erro'=>'JSON vazio ou inválido','recv'=>substr($botJson,0,100)]);
    }
  }
  redirect('?r=saas_bot&salvo=1');
  break;

case 'saas_bot_test':
  saas_require();
  $url=setting_get('saas_evo_url','');
  $key=setting_get('saas_evo_key','');
  $inst=setting_get('saas_evo_instance','');
  if(!$url||!$key||!$inst){ json_out(['ok'=>false,'erro'=>'API não configurada']); }
  $ctx=stream_context_create(['http'=>['method'=>'GET','header'=>"apikey: $key\r\nContent-Type: application/json\r\n",'timeout'=>8,'ignore_errors'=>true]]);
  $res=@file_get_contents(rtrim($url,'/').'/instance/fetchInstances',false,$ctx);
  json_out(['ok'=>$res!==false,'raw'=>$res?json_decode($res,true):null]); break;

case 'admin_import':
  _start_session(); if(!($_SESSION['user_id']??false)) redirect('?r=login');
  $preview=[]; $previewMeta=[]; $erroImport=''; $importUrl='';

  function _import_extract_cats($nd){
    $pp=$nd['props']['pageProps']??$nd['pageProps']??$nd;
    $cats=[];
    // Padrão Anota AI: catalogGroups[].catalog.items[].item
    if(!empty($pp['store']['catalogGroups'])){
      foreach($pp['store']['catalogGroups'] as $cg){
        $catNome=$cg['name']??$cg['title']??'Categoria'; $itens=[];
        foreach($cg['catalog']['items']??$cg['items']??[] as $ci){
          $it=$ci['item']??$ci;
          $p=(float)($it['price']??$it['value']??0);
          $itens[]=['nome'=>$it['name']??$it['title']??'','descricao'=>$it['description']??'','preco'=>$p>100?$p/100:$p];
        }
        if($itens) $cats[]=['nome'=>$catNome,'itens'=>$itens,'count'=>count($itens)];
      }
    }
    // Padrão genérico: categories[].items[] ou products[]
    if(!$cats){
      $rawCats=$pp['categories']??$pp['store']['categories']??$pp['menu']??$pp['catalog']??[];
      foreach($rawCats as $c){
        $catNome=$c['name']??$c['title']??'Categoria'; $itens=[];
        foreach($c['items']??$c['products']??$c['catalog']??[] as $ci){
          $p=(float)($ci['price']??$ci['value']??0);
          $itens[]=['nome'=>$ci['name']??$ci['title']??'','descricao'=>$ci['description']??'','preco'=>$p>100?$p/100:$p];
        }
        if($itens) $cats[]=['nome'=>$catNome,'itens'=>$itens,'count'=>count($itens)];
      }
    }
    return $cats;
  }

  // Extrai categorias do formato Pinia do Anota AI: [{title, itens:[{title,price,description}]}]
  function _import_extract_pinia($menu){
    $cats=[];
    foreach($menu as $cat){
      $catNome=$cat['title']??$cat['name']??'Categoria';
      $itens=[];
      foreach($cat['itens']??$cat['items']??[] as $it){
        if(empty($it['title'])) continue;
        $p=(float)($it['price']??$it['simple_price']??0);
        $itens[]=['nome'=>$it['title'],'descricao'=>$it['description']??'','preco'=>$p];
      }
      if($itens) $cats[]=['nome'=>$catNome,'itens'=>$itens,'count'=>count($itens)];
    }
    return $cats;
  }

  if($method==='POST'){
    // Modo 1: JSON colado pelo bookmarklet/extrator
    $manualJson=trim($_POST['manual_json']??'');
    if($manualJson!==''){
      $nd=json_decode($manualJson,true);
      if(!$nd){ $erroImport='Dados inválidos. Certifique-se de usar o botão "Importar Anota AI" nos favoritos e colar o resultado aqui.'; }
      else {
        // Formato Pinia (bookmarklet Anota AI): {source:'pinia', name:'...', menu:[...]}
        if(!empty($nd['source']) && $nd['source']==='pinia' && !empty($nd['menu'])){
          $cats=_import_extract_pinia($nd['menu']);
          if($cats){
            $preview=$cats;
            $previewMeta=['nome'=>$nd['name']??'Cardápio importado'];
          } else { $erroImport='Não encontrei produtos nos dados copiados.'; }
        }
        // Formato __NEXT_DATA__ (outros sistemas)
        else {
          $cats=_import_extract_cats($nd);
          if($cats){
            $preview=$cats;
            $previewMeta=['nome'=>$nd['props']['pageProps']['store']['name']??$nd['pageProps']['store']['name']??'Cardápio importado'];
          } else { $erroImport='Dados não contêm cardápio reconhecível.'; }
        }
      }
    }
    // Modo 2: URL automática
    elseif(!empty($_POST['url'])){
      $importUrl=filter_var(trim($_POST['url']),FILTER_VALIDATE_URL);
      if(!$importUrl){ $erroImport='URL inválida.'; }
      else {
        $cats=[]; $nome='Cardápio importado';
        // Anota AI — tenta API JSON direta primeiro (sem bot-protection)
        if(preg_match('/anota\.ai\/loja\/([a-z0-9_-]+)/i',$importUrl,$sm)){
          $aSlug=$sm[1];
          $apiTries=[
            "https://pedido.anota.ai/api/catalog/getByOwnerSlug?ownerSlug={$aSlug}",
            "https://pedido.anota.ai/api/catalog/{$aSlug}",
            "https://api.anota.ai/anota-ai-catalog/v1/catalog/getByOwnerSlug?ownerSlug={$aSlug}",
          ];
          foreach($apiTries as $api){
            $ch2=curl_init($api);
            curl_setopt_array($ch2,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>8,CURLOPT_CONNECTTIMEOUT=>5,
              CURLOPT_SSL_VERIFYPEER=>false,
              CURLOPT_HTTPHEADER=>['Accept: application/json','Origin: https://pedido.anota.ai','Referer: '.$importUrl],
              CURLOPT_USERAGENT=>'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0.0.0 Safari/537.36',
            ]);
            $raw2=curl_exec($ch2);$c2=(int)curl_getinfo($ch2,CURLINFO_HTTP_CODE);curl_close($ch2);
            if($c2===200 && $raw2){
              $data2=json_decode($raw2,true);
              $cats=_import_extract_cats($data2);
              if($cats){ $nome=$data2['name']??$data2['store']['name']??$nome; break; }
            }
          }
        }
        // Fallback: ScraperAPI chamando API JSON do Anota AI diretamente (2 passos: token → menu)
        if(!$cats){
          $scraperKey=setting_get('saas_scraper_key','');
          if($scraperKey!==''){
            function _scraper_get($scraperKey,$targetUrl,$extraHeaders=[]){
              $u='http://api.scraperapi.com?api_key='.urlencode($scraperKey).'&url='.urlencode($targetUrl).'&keep_headers=true';
              $ch=curl_init($u);
              $hdrs=array_merge(['Accept: application/json','Origin: https://pedido.anota.ai','Referer: https://pedido.anota.ai/'],$extraHeaders);
              curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>30,CURLOPT_CONNECTTIMEOUT=>10,
                CURLOPT_SSL_VERIFYPEER=>false,CURLOPT_HTTPHEADER=>$hdrs,
                CURLOPT_USERAGENT=>'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0 Safari/537.36']);
              $raw=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
              return [$code,json_decode($raw??'',true),$raw];
            }
            // Anota AI: pegar token pelo slug
            if(preg_match('/anota\.ai\/loja\/([a-z0-9_-]+)/i',$importUrl,$sm)){
              $aSlug=$sm[1];
              [$tc,$td]= _scraper_get($scraperKey,"https://api.anota.ai/noauth/access/get-token/{$aSlug}");
              $jwtToken=$td['token']??null;
              if($jwtToken){
                [$mc,$md]= _scraper_get($scraperKey,
                  'https://api.anota.ai/clientauth/nm-category/menu-merchant?displaySources=DIGITAL_MENU',
                  ['Authorization: Bearer '.$jwtToken]);
                if($md && !empty($md['menu']['menu'])){
                  $cats=_import_extract_pinia($md['menu']['menu']);
                  if($cats) $nome=$md['establishment']['page']['name']??$nome;
                }
              }
            }
            // Outros sites: render HTML + __NEXT_DATA__
            if(!$cats){
              $u2='http://api.scraperapi.com?api_key='.urlencode($scraperKey).'&url='.urlencode($importUrl).'&render=true&wait=4000';
              $ch2=curl_init($u2);
              curl_setopt_array($ch2,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>45,CURLOPT_CONNECTTIMEOUT=>15,CURLOPT_SSL_VERIFYPEER=>false]);
              $raw2=curl_exec($ch2);$code2=(int)curl_getinfo($ch2,CURLINFO_HTTP_CODE);curl_close($ch2);
              if($raw2 && $code2<400 && preg_match('/<script[^>]+id=["\']__NEXT_DATA__["\'][^>]*>(.+?)<\/script>/si',$raw2,$m)){
                $nd=json_decode($m[1],true);
                $cats=_import_extract_cats($nd??[]);
                if($cats) $nome=$nd['props']['pageProps']['store']['name']??$nome;
              }
            }
          }
          if(!$cats){
            if($scraperKey===''){
              $erroImport='Não foi possível importar. Configure a ScraperAPI nas configurações do sistema SaaS para habilitar a importação do Anota AI.';
            } else {
              $erroImport='Não foi possível importar automaticamente. Verifique se o link está correto (ex: pedido.anota.ai/loja/nome-do-restaurante).';
            }
          }
        }
        if($cats){ $preview=$cats; $previewMeta=['nome'=>$nome]; }
        elseif(!$erroImport){ $erroImport='Não foi possível extrair dados. Use o método manual abaixo.'; }
      }
    }
  }
  render('admin_import',['preview'=>$preview,'previewMeta'=>$previewMeta,'erroImport'=>$erroImport,'importUrl'=>$importUrl??($_POST['url']??'')]); break;

case 'admin_import_raw':
  // Debug: testa os 2 passos da API Anota AI via ScraperAPI
  _start_session(); if(!($_SESSION['user_id']??false)) redirect('?r=login');
  $scraperKey=setting_get('saas_scraper_key','');
  $slug=$_GET['slug']??'barba-burger';
  header('Content-Type: text/plain; charset=utf-8');
  echo "=== PASSO 1: TOKEN ===\n";
  $tokenUrl="https://api.anota.ai/noauth/access/get-token/{$slug}";
  $u1='http://api.scraperapi.com?api_key='.urlencode($scraperKey).'&url='.urlencode($tokenUrl).'&keep_headers=true';
  $ch=curl_init($u1);
  curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>30,CURLOPT_SSL_VERIFYPEER=>false,
    CURLOPT_HTTPHEADER=>['Accept: application/json','Origin: https://pedido.anota.ai'],
    CURLOPT_USERAGENT=>'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0 Safari/537.36']);
  $r1=curl_exec($ch);$c1=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
  echo "HTTP: $c1 | ".strlen($r1??'')."b\nResposta: ".substr($r1??'',0,500)."\n\n";
  $td=json_decode($r1??'',true);
  $jwt=$td['token']??null;
  echo "=== PASSO 2: MENU (token=".($jwt?substr($jwt,0,30).'...':'N/A').") ===\n";
  if($jwt){
    $menuUrl='https://api.anota.ai/clientauth/nm-category/menu-merchant?displaySources=DIGITAL_MENU';
    $u2='http://api.scraperapi.com?api_key='.urlencode($scraperKey).'&url='.urlencode($menuUrl).'&keep_headers=true';
    $ch2=curl_init($u2);
    curl_setopt_array($ch2,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>30,CURLOPT_SSL_VERIFYPEER=>false,
      CURLOPT_HTTPHEADER=>['Accept: application/json','Authorization: Bearer '.$jwt,'Origin: https://pedido.anota.ai'],
      CURLOPT_USERAGENT=>'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0 Safari/537.36']);
    $r2=curl_exec($ch2);$c2=(int)curl_getinfo($ch2,CURLINFO_HTTP_CODE);curl_close($ch2);
    echo "HTTP: $c2 | ".strlen($r2??'')."b\nResposta (primeiros 800): ".substr($r2??'',0,800)."\n";
  } else { echo "Sem token, pulou passo 2\n"; }
  exit;

case 'admin_import_do':
  _start_session(); if(!$_SESSION['user_id']??false) redirect('?r=login');
  $cats=json_decode($_POST['data']??'[]',true);
  if(!is_array($cats)||!$cats){ redirect('?r=admin_import'); }
  $ordemCat=db()->query("SELECT MAX(ordem) m FROM categories")->fetch()['m']??0;
  $stmtCat=db()->prepare("INSERT INTO categories(nome,ordem) VALUES(?,?)");
  $stmtProd=db()->prepare("INSERT INTO products(category_id,nome,descricao,preco,ativo) VALUES(?,?,?,?,1)");
  $importado=0;
  foreach($cats as $cat){
    $catNome=trim($cat['nome']??'');
    if(!$catNome) continue;
    // Reutiliza categoria existente de mesmo nome
    $existing=db()->prepare("SELECT id FROM categories WHERE lower(nome)=lower(?) LIMIT 1"); $existing->execute([$catNome]); $ex=$existing->fetch();
    if($ex){ $catId=$ex['id']; }
    else { $ordemCat++; $stmtCat->execute([$catNome,$ordemCat]); $catId=db()->lastInsertId(); }
    foreach($cat['itens']??[] as $it){
      $nome=trim($it['nome']??''); if(!$nome) continue;
      $stmtProd->execute([$catId,$nome,trim($it['descricao']??''),(float)($it['preco']??0)]);
      $importado++;
    }
  }
  redirect('?r=admin_produtos&importado='.$importado);
  break;

case 'logout':
  _start_session();
  $savedRole = $_SESSION['user_role'] ?? '';
  session_destroy();
  $dest = ['motoboy'=>'courier','garcom'=>'waiter','cozinha'=>'kds','caixa'=>'admin_caixa'][$savedRole] ?? 'admin';
  redirect('?r='.$dest);
  break;

default:
  http_response_code(404); echo 'Rota não encontrada';
}
