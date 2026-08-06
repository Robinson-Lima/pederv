<?php
$logo='assets/rv-logo.png';
// Versionamento automático dos assets pela data de modificação do arquivo.
$asset=function($p){$full=__DIR__.'/../'.$p;return $p.'?v='.(@filemtime($full)?:'1');};
// Contraste automático do texto sobre a cor escolhida (preto ou branco).
$onColor=function($hex){$hex=ltrim((string)$hex,'#');if(strlen($hex)!==6)return '#111';$r=hexdec(substr($hex,0,2));$g=hexdec(substr($hex,2,2));$b=hexdec(substr($hex,4,2));return ((0.299*$r+0.587*$g+0.114*$b)/255)>0.62?'#111':'#fff';};
$hex6=function($v,$def){$v=trim((string)$v);return preg_match('/^#[0-9a-fA-F]{6}$/',$v)?$v:$def;};
$brandLogo=setting_get('brand_logo','');
$nomeRestaurante=setting_get('store_name','') ?: cfg('restaurante');  // nome do tenant ou fallback global
$corCardapio=$hex6(setting_get('brand_color',''),'#EE7430');   // cor do cardápio (cliente)
$corPainel  =$hex6(setting_get('panel_color',''),'#EE7430');   // cor do painel (sistema)
$corLateral =$hex6(setting_get('panel_side',''),'#0D1320');    // cor da barra lateral
$menuBg     =setting_get('menu_bg_image','');                  // fundo enviado pelo cliente
$r=$_GET['r']??'menu';
$adminRoutes=['admin_orders','admin_pdv','admin_customers','admin_areas','admin_payments','admin_ifood','admin_ifood_add','admin_tables','admin_qr_tables','admin_couriers','admin_caixa','admin_cash_detail','admin_settings','admin_notas','admin_fiscal','admin_produtos','admin_import','admin_import_raw','admin_financeiro','admin_whatsapp','admin_bot_messages','admin_salon','admin_reports','admin_general','admin_recovery','admin_links'];
$isAdmin=in_array($r,$adminRoutes,true);
// PWA: cada área instala como um app próprio, com o ícone abrindo direto na tela certa.
$pwaApp = in_array($r,['admin_caixa','admin_pdv'],true) ? 'caixa'
  : ($isAdmin ? 'painel'
  : (in_array($r,['kds'],true) ? 'cozinha'
  : (in_array($r,['waiter','waiter_comanda','waiter_pick'],true) ? 'garcom'
  : (in_array($r,['courier'],true) ? 'motoboy' : 'cardapio'))));
$waConnected=$isAdmin && evolution_configured();
$store = (current_slug()!=='') ? store_status() : ['closed'=>false,'until'=>'','reason'=>'','message'=>''];
$waCount=0;
if($isAdmin){try{$waCount=(int)db()->query("SELECT COUNT(*) c FROM whatsapp_messages WHERE direcao='entrada' AND date(criado_em)=date('now','localtime')")->fetch()['c'];}catch(Exception $e){}}
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#073f5f"><base href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']??'/'),'/\\').'/' ?>"><title><?= e($nomeRestaurante) ?></title><link rel="manifest" href="?r=manifest&app=<?= e($pwaApp) ?>"><link rel="apple-touch-icon" href="assets/icon-192.png"><link rel="icon" href="assets/icon-192.png"><link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="<?= $asset('assets/style.css') ?>"><link rel="stylesheet" href="<?= $asset('assets/v2.css') ?>"><link rel="stylesheet" href="<?= $asset('assets/admin-shell.css') ?>"><?php
  // Cor de destaque conforme o contexto: painel usa a cor do sistema, o restante (cardápio/cliente) usa a cor do cardápio.
  $accent = $isAdmin ? $corPainel : $corCardapio; $onAccent=$onColor($accent);
  // Cor do texto dos botões: 'auto' calcula pelo contraste; senão usa a cor escolhida.
  $txtCard=$hex6(setting_get('menu_btn_text',''),''); $txtPanel=$hex6(setting_get('panel_btn_text',''),'');
  $onFinal = $isAdmin ? ($txtPanel?:$onAccent) : ($txtCard?:$onAccent);
  $promoTxt=$txtCard?:$hex6(setting_get('promo_text_color',''), $onFinal);
  echo '<style>:root{--brand:'.$accent.';--brand2:'.$accent.';--brandsoft:'.$accent.'26;--on-brand:'.$onFinal.'!important;--promo-text:'.$promoTxt.'!important}';
  if($isAdmin) echo '.admin-shell .admin-side{background:'.$corLateral.'!important}'
    .'.admin-shell .side-brand{background:#fff!important}'
    .'.admin-shell .side-search{background:#fff!important;color:#5b6b7a!important;border:1px solid rgba(0,0,0,.05)}'
    .'.admin-shell .side-search span{color:#5b6b7a!important}'
    .'.admin-shell .side-user{background:rgba(255,255,255,.09)!important;border-color:rgba(255,255,255,.16)!important}';
  if($r==='menu'){
    $_menuTema=setting_get('menu_theme','gourmet');
    // Override v18.css white CSS variables for dark themes (specificity 0,2,1 beats v18 0,1,0)
    $_darkUbg=['gourmet'=>'#12161F','carvao'=>'#0C0E12','noite'=>'#08090B','madeira'=>'#1A130A','bistro'=>'#0D1712','vinho'=>'#160A0E','escuro'=>'#0B0F14'];
    if($menuBg!==''){
      echo 'body.menu-uber{background:linear-gradient(rgba(8,9,11,.70),rgba(8,9,11,.82)),url("'.e($menuBg).'") center/cover fixed no-repeat!important}';
      echo 'body.menu-uber{--u-bg:#0B0F14;--u-ink:#e8e8e8;--u-body:#9a9a9a;--u-line:rgba(255,255,255,.08);--u-soft:rgba(255,255,255,.06);--u-thumb:rgba(255,255,255,.10)}';
    } elseif(isset($_darkUbg[$_menuTema])){
      $_dbg=$_darkUbg[$_menuTema];
      echo 'body.theme-'.$_menuTema.'.menu-uber{--u-bg:'.$_dbg.';--u-ink:#e8e8e8;--u-body:#9a9a9a;--u-line:rgba(255,255,255,.08);--u-soft:rgba(255,255,255,.06);--u-thumb:rgba(255,255,255,.10)}';
    }
  }
  echo '</style>';
?></head>
<body class="<?= $r==='menu' ? 'menu-uber'.(isset($_menuTema)?' theme-'.$_menuTema:'') : ((in_array($r,['courier','waiter','waiter_comanda','placa','order_status','kds','customer_account'],true)||strpos($r,'login')!==false||strpos($r,'customer_')===0)?'dark':'') ?>"><link rel="stylesheet" href="<?= $asset('assets/qr-admin.css') ?>"><link rel="stylesheet" href="<?= $asset('assets/workflow.css') ?>"><link rel="stylesheet" href="<?= $asset('assets/v11.css') ?>"><link rel="stylesheet" href="<?= $asset('assets/v15.css') ?>"><link rel="stylesheet" href="<?= $asset('assets/v16.css') ?>"><link rel="stylesheet" href="<?= $asset('assets/v17.css') ?>"><link rel="stylesheet" href="<?= $asset('assets/v18.css') ?>"><link rel="stylesheet" href="<?= $asset('assets/v19.css') ?>"><?php if($isAdmin): ?><script defer src="<?= $asset('assets/admin-notify.js') ?>"></script><?php endif; ?>
<?php if($isAdmin): ?>
<div class="admin-shell">
  <aside class="admin-side">
    <div class="side-brand"><?php if($brandLogo): ?><img class="brand-custom" src="<?= e($brandLogo) ?>" alt="<?= e($nomeRestaurante) ?>"><div><b><?= e($nomeRestaurante) ?></b><small>Gestão do restaurante</small></div><?php else: ?><img src="<?= $logo ?>" onerror="this.style.display='none'"><div><b><?= e($nomeRestaurante) ?></b><small>Gestão do restaurante</small></div><?php endif; ?></div>
    <div class="side-search">⌕ <span>Encontre uma função</span></div>
    <nav>
      <small>OPERAÇÃO</small>
      <a class="<?= $r==='admin_orders'?'on':'' ?>" href="?r=admin_orders">📋 Meus pedidos</a>
      <a class="<?= $r==='admin_pdv'?'on':'' ?>" href="?r=admin_pdv">🏪 Pedido Balcão (PDV)</a>
      <a class="<?= $r==='admin_customers'?'on':'' ?>" href="?r=admin_customers">👥 Clientes</a>
      <a class="<?= $r==='admin_tables'?'on':'' ?>" href="?r=admin_tables">🪑 Mesas e comandas</a>
      <a class="<?= $r==='admin_qr_tables'?'on':'' ?>" href="?r=admin_qr_tables">📷 QR Codes do salão</a>
      <a href="?r=kds">👨‍🍳 Cozinha (KDS)</a>
      <a class="<?= $r==='admin_couriers'?'on':'' ?>" href="?r=admin_couriers">🛵 Entregas</a>
      <small>GESTÃO</small>
      <a class="<?= $r==='admin_produtos'?'on':'' ?>" href="?r=admin_produtos">🍽️ Cardápio, produtos e combos</a>
      <a class="<?= $r==='admin_areas'?'on':'' ?>" href="?r=admin_areas">📍 Taxas de entrega</a>
      <a class="<?= $r==='admin_caixa'?'on':'' ?>" href="?r=admin_caixa">💵 Caixa</a>
      <a class="<?= $r==='admin_payments'?'on':'' ?>" href="?r=admin_payments">💰 Pagamentos</a>
      <a class="<?= $r==='admin_notas'?'on':'' ?>" href="?r=admin_notas">🧾 Notas fiscais</a>
      <a class="<?= $r==='admin_fiscal'?'on':'' ?>" href="?r=admin_fiscal">📋 Config. fiscal</a>
      <a class="<?= $r==='admin_recovery'?'on':'' ?>" href="?r=admin_recovery">🔄 Recuperador de vendas</a>
      <details class="side-group" <?= $r==='admin_reports'?'open':'' ?>><summary>📊 Relatórios <span>⌄</span></summary><div><?php foreach(['geral'=>'Geral','caixas'=>'Caixas','clientes'=>'Clientes','pedidos'=>'Pedidos','itens'=>'Itens','estoque'=>'Estoque','entregadores'=>'Entregadores','garcons'=>'Garçons','areas'=>'Área de entrega'] as $tipo=>$lbl): ?><a class="<?= $r==='admin_reports'&&($_GET['tipo']??'geral')===$tipo?'on':'' ?>" href="?r=admin_reports&tipo=<?= $tipo ?>"><?= $lbl ?></a><?php endforeach; ?></div></details>
      <small>CONFIGURAÇÕES</small>
      <a class="<?= $r==='admin_salon'?'on':'' ?>" href="?r=admin_salon">🏠 Configuração do salão</a>
      <a class="<?= $r==='admin_general'?'on':'' ?>" href="?r=admin_general">⚙️ Configurações gerais</a>
      <a class="<?= in_array($r,['admin_bot_messages','admin_whatsapp'],true)?'on':'' ?>" href="?r=admin_bot_messages">🤖 Configuração Robô</a>
      <a class="<?= $r==='admin_links'?'on':'' ?>" href="?r=admin_links">🔗 Acessos e links</a>
      <a class="<?= $r==='admin_settings'?'on':'' ?>" href="?r=admin_settings">👤 Usuários e integrações</a>
    </nav>
    <div class="side-user"><span><?= e(mb_substr($_SESSION['user_nome']??'A',0,1)) ?></span><div><b><?= e($_SESSION['user_nome']??'Administrador') ?></b><small>● Loja aberta</small></div><a href="?r=logout">Sair</a></div>
  </aside>
  <section class="admin-main">
    <div class="admin-top"><button class="side-toggle" onclick="document.body.classList.toggle('side-open')">☰</button><span class="admin-top-spacer"></span>
      <?php if($store['closed']): ?>
        <span class="store-status closed" title="<?= e($store['reason']) ?>">● Fechada<?php if($store['until']!==''): ?> · reabre <?= date('H:i',strtotime($store['until'])) ?><?php endif; ?></span>
        <form method="post" action="?r=admin_store_open" style="display:inline"><button class="store-btn reopen" type="submit">Reabrir loja</button></form>
      <?php else: ?>
        <span class="store-status open">● Aberta</span>
        <button class="store-btn close" type="button" onclick="document.getElementById('closeStoreModal').classList.add('on')">Fechar loja</button>
      <?php endif; ?>
      <a href="?r=admin_caixa">▣ Caixa</a>
      <div class="wa-head-wrap"><button type="button" class="wa-head-button <?= $waConnected?'connected':'offline' ?>" onclick="document.getElementById('waHeadMenu').classList.toggle('open')" aria-label="WhatsApp e robô"><span class="wa-bot"><svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="8" width="16" height="11" rx="3.2"/><path d="M12 8V4.5M12 4.5a1.4 1.4 0 1 0 0-2.8 1.4 1.4 0 0 0 0 2.8Z"/><path d="M2 12.5v3M22 12.5v3"/><circle cx="9.2" cy="13.4" r="1.25" fill="currentColor" stroke="none"/><circle cx="14.8" cy="13.4" r="1.25" fill="currentColor" stroke="none"/><path d="M9.5 16.4h5"/></svg></span><b>WhatsApp</b><em><?= $waCount ?></em></button><div class="wa-head-menu" id="waHeadMenu"><strong><?= $waConnected?'Robô conectado':'Robô desconectado' ?></strong><small><?= $waCount ?> mensagem(ns) recebida(s) hoje</small><a href="?r=admin_whatsapp"><?= $waConnected?'Gerenciar conexão':'Conectar WhatsApp' ?></a><a href="?r=admin_bot_messages">Personalizar mensagens do robô</a><a href="https://web.whatsapp.com/" target="_blank">Abrir conversas ↗</a><a class="wa-new-order" href="?r=admin_pdv&origem=whatsapp">＋ Criar pedido da conversa</a></div></div>
    </div>
<?php
  // Banner de fim do teste grátis: avisa o cliente a partir de ~2 dias antes do bloqueio.
  $__slugTrial=current_slug();
  if($__slugTrial){
    try{
      $__tc=master_db()->prepare("SELECT status,trial_ate FROM saas_clients WHERE slug=? LIMIT 1");
      $__tc->execute([$__slugTrial]); $__tc=$__tc->fetch();
      if($__tc && $__tc['status']==='trial' && !empty($__tc['trial_ate'])){
        $__dl=(int)ceil((strtotime($__tc['trial_ate'])-strtotime(date('Y-m-d')))/86400);
        if($__dl<=2){
          $__msg = $__dl<=0 ? 'Seu teste grátis termina <b>hoje</b>.' : ('Faltam <b>'.$__dl.' dia'.($__dl>1?'s':'').'</b> do seu teste grátis.');
          echo '<div class="trial-banner"><span>⏳ '.$__msg.' Complete seu cadastro e ative a assinatura para não perder o acesso ao painel.</span><a href="https://wa.me/5513997540052?text='.rawurlencode('Quero ativar minha assinatura PedeRV do painel '.$__slugTrial).'" target="_blank" rel="noopener">Ativar assinatura →</a></div>';
        }
      }
    }catch(Exception $__e){}
  }
?>
<?php if(!$store['closed']): ?>
<div class="store-modal" id="closeStoreModal">
  <div class="store-modal-in">
    <div class="store-modal-h"><b>Fechar loja temporariamente</b><button type="button" onclick="document.getElementById('closeStoreModal').classList.remove('on')">×</button></div>
    <p class="store-modal-sub">Enquanto fechada, o cardápio mostra o aviso e o robô responde automaticamente quem chamar no WhatsApp.</p>
    <form method="post" action="?r=admin_store_close">
      <label class="store-fld">Por quanto tempo?
        <select name="duracao" onchange="scUpdate()" id="scDur">
          <option value="30">30 minutos</option>
          <option value="60" selected>1 hora</option>
          <option value="120">2 horas</option>
          <option value="180">3 horas</option>
          <option value="360">Resto do dia (6h)</option>
        </select>
      </label>
      <label class="store-fld">Motivo
        <select name="motivo" onchange="scUpdate()" id="scMot">
          <option value="alta demanda de pedidos">Alta demanda de pedidos</option>
          <option value="problemas técnicos">Problemas técnicos</option>
          <option value="fim do horário de atendimento">Fim do horário de atendimento</option>
          <option value="falta de ingredientes">Falta de ingredientes</option>
          <option value="outro">Outro (escrever)</option>
        </select>
      </label>
      <label class="store-fld" id="scOutroWrap" style="display:none">Descreva o motivo
        <input name="motivo_outro" placeholder="Ex.: manutenção da cozinha">
      </label>
      <label class="store-fld">Mensagem enviada ao cliente (opcional)
        <textarea name="mensagem" id="scMsg" rows="3" placeholder="Deixe em branco para usar a mensagem automática"></textarea>
        <small class="store-hint">Prévia automática: <span id="scPreview"></span></small>
      </label>
      <button class="store-submit" type="submit">🔒 Fechar loja agora</button>
    </form>
  </div>
</div>
<script>
(function(){
  var loja=<?= json_encode(setting_get('store_name','') ?: cfg('restaurante')) ?>;
  window.scUpdate=function(){
    var dur=document.getElementById('scDur'),mot=document.getElementById('scMot');
    var min=parseInt(dur.value),motivo=mot.value;
    document.getElementById('scOutroWrap').style.display=motivo==='outro'?'block':'none';
    var tempo=min>=60?('aproximadamente '+(min/60)+'h'):('aproximadamente '+min+' minutos');
    var mtxt=motivo==='outro'?'(seu motivo)':motivo;
    document.getElementById('scPreview').textContent='Olá! 😊 No momento estamos fechados por '+tempo+' devido a '+mtxt+'. Logo reabriremos os pedidos. Obrigado! — '+loja;
  };
  scUpdate();
})();
</script>
<?php endif; ?>
<?php endif; ?>
<?= $content ?>
<?php if($isAdmin): ?></section></div><script>document.addEventListener('click',e=>{const w=document.querySelector('.wa-head-wrap');if(w&&!w.contains(e.target))document.getElementById('waHeadMenu')?.classList.remove('open')})</script><?php endif; ?>
<?php if($r!=='courier'): ?><button type="button" id="pwaInstallBtn" class="pwa-install-fab" hidden>📲 Instalar app</button><?php endif; ?>
<script>
if('serviceWorker'in navigator)window.addEventListener('load',()=>navigator.serviceWorker.register('sw.js?v=16').catch(()=>{}));
(function(){var n=document.querySelector('.admin-side nav');if(!n)return;n.scrollTop=+localStorage.getItem('rv_nav_scroll')||0;var t;n.addEventListener('scroll',function(){clearTimeout(t);t=setTimeout(function(){localStorage.setItem('rv_nav_scroll',n.scrollTop)},120)},{passive:true})})();
<?php $__slug=current_slug(); if($__slug): ?>(function(){
  var S='<?= e($__slug) ?>';
  function addSlug(url){if(typeof url!=='string'||url.indexOf('slug=')!==-1)return url;if(url.charAt(0)==='?'||url.indexOf('index.php')!==-1){return url+(url.indexOf('?')!==-1?'&':'?')+'slug='+S;}return url;}
  var _f=window.fetch;window.fetch=function(url,o){return _f.call(this,addSlug(url),o);};
  var _o=XMLHttpRequest.prototype.open;XMLHttpRequest.prototype.open=function(m,url){return _o.apply(this,[m,addSlug(url)].concat([].slice.call(arguments,2)));};
  document.addEventListener('DOMContentLoaded',function(){
    document.querySelectorAll('a[href]').forEach(function(a){var h=a.getAttribute('href');if(h)a.setAttribute('href',addSlug(h));});
    document.querySelectorAll('form').forEach(function(f){if(!f.querySelector('[name=slug]')){var i=document.createElement('input');i.type='hidden';i.name='slug';i.value=S;f.appendChild(i);}});
  });
})();<?php endif; ?>
<?php if($r!=='courier'): ?>
(function(){
  let prompt=null;const btn=document.getElementById('pwaInstallBtn');if(!btn)return;
  if(!window.matchMedia('(display-mode: standalone)').matches)btn.hidden=false;
  window.addEventListener('beforeinstallprompt',e=>{e.preventDefault();prompt=e;btn.hidden=false});
  window.addEventListener('appinstalled',()=>{btn.hidden=true;prompt=null});
  btn.addEventListener('click',async()=>{if(!prompt){alert('No celular, abra o menu do navegador e escolha “Adicionar à tela inicial” ou “Instalar app”. No computador, use o ícone de instalação na barra de endereço.');return}prompt.prompt();await prompt.userChoice;prompt=null;btn.hidden=true});
})();
<?php endif; ?>
</script>
</body></html>
