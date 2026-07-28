<?php $r=$_GET['r']??'saas'; $asset=function($p){$full=__DIR__.'/../'.$p;return $p.'?v='.(@filemtime($full)?:'1');}; ?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>PedeRV · Central SaaS</title><base href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']??'/'),'/\\').'/' ?>">
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $asset('assets/saas.css') ?>">
</head><body class="saas">
<?php if(saas_logged()): ?>
<div class="saas-shell">
  <aside class="saas-side">
    <div class="saas-brand">
      <img src="assets/pederv-logo.png" style="width:44px;height:44px;object-fit:contain;border-radius:10px;background:#fff;padding:4px">
      <div><b>PedeRV</b><small>Central SaaS</small></div>
    </div>
    <nav>
      <a href="?r=saas" class="<?= $r==='saas'?'on':'' ?>"><i>▤</i> Visão geral</a>
      <a href="?r=saas_clients" class="<?= in_array($r,['saas_clients','saas_client'],true)?'on':'' ?>"><i>▦</i> Clientes</a>
      <a href="?r=saas_bot" class="<?= $r==='saas_bot'?'on':'' ?>"><i>🤖</i> Robô de vendas</a>
      <a href="?r=saas_settings" class="<?= $r==='saas_settings'?'on':'' ?>"><i>⚙</i> Planos e ajustes</a>
    </nav>
    <div class="saas-side-foot">
      <a class="saas-new" href="?r=saas_clients">＋ Novo cliente</a>
      <a class="saas-logout" href="?r=saas_logout">Sair</a>
    </div>
  </aside>
  <main class="saas-main">
    <div class="saas-mobile-top" style="display:none">
      <img src="assets/pederv-logo.png" alt="PedeRV">
      <div><b>PedeRV</b><small>Central SaaS</small></div>
    </div>
    <?= $content ?>
  </main>
</div>
<nav class="saas-mobile-nav">
  <a href="?r=saas" class="<?= $r==='saas'?'on':'' ?>"><i>▤</i>Geral</a>
  <a href="?r=saas_clients" class="<?= in_array($r,['saas_clients','saas_client'],true)?'on':'' ?>"><i>▦</i>Clientes</a>
  <a href="?r=saas_bot" class="<?= $r==='saas_bot'?'on':'' ?>"><i>🤖</i>Robô</a>
  <a href="?r=saas_settings" class="<?= $r==='saas_settings'?'on':'' ?>"><i>⚙</i>Ajustes</a>
  <a href="?r=saas_logout" style="color:#555"><i>↩</i>Sair</a>
</nav>
<?php else: ?>
<main class="saas-auth"><?= $content ?></main>
<?php endif; ?>
</body></html>
