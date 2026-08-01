<?php
$ok=!empty($result['ok']);
$checkout=$result['checkout']??[];
$slug=(string)($result['slug']??'');
if($slug===''&&!empty($result['client_id'])){
  $q=master_db()->prepare("SELECT slug FROM saas_clients WHERE id=?");
  $q->execute([(int)$result['client_id']]);$slug=(string)($q->fetch()['slug']??'');
}
$scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';
$host=$_SERVER['HTTP_HOST']??'pederv.com.br';
$panel=$slug!==''?$scheme.'://'.$host.'/painel/'.$slug:'';
$menu=$slug!==''?$scheme.'://'.$host.'/cardapio/'.$slug:'';
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $ok?'Assinatura confirmada':'Confirmação do pagamento' ?> | PedeRV</title>
  <style>
    *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:#fffaf6;color:#17130f;font-family:Inter,system-ui,sans-serif}
    main{width:min(610px,100%);background:#fff;border:1px solid #f0d8c9;border-radius:24px;padding:38px;box-shadow:0 24px 70px #5b2c1518;text-align:center}
    .icon{width:70px;height:70px;border-radius:50%;display:grid;place-items:center;margin:0 auto 20px;font-size:34px;background:<?= $ok?'#e9f8f0':'#fff3e9' ?>;color:<?= $ok?'#138a5b':'#f26422' ?>}
    h1{font-size:clamp(27px,6vw,39px);margin:0 0 12px}p{color:#64605c;line-height:1.65;margin:0 0 22px}
    .actions{display:grid;gap:11px}.btn{display:flex;justify-content:center;align-items:center;min-height:52px;border-radius:13px;text-decoration:none;font-weight:800}
    .primary{background:#ff6726;color:#fff}.secondary{border:1px solid #e6d8cf;color:#17130f;background:#fff}
    small{display:block;color:#87817b;margin-top:20px;line-height:1.5}
  </style>
</head>
<body>
<main>
  <div class="icon"><?= $ok?'✓':'…' ?></div>
  <?php if($ok): ?>
    <h1>Pagamento confirmado!</h1>
    <p>Sua assinatura do PedeRV está ativa. O painel do restaurante já está pronto para você configurar o cardápio e começar a receber pedidos.</p>
    <div class="actions">
      <?php if($panel): ?><a class="btn primary" href="<?= e($panel) ?>">Acessar meu painel</a><?php endif; ?>
      <?php if($menu): ?><a class="btn secondary" href="<?= e($menu) ?>">Abrir meu cardápio</a><?php endif; ?>
      <?php if(!empty($checkout['receipt_url'])): ?><a class="btn secondary" href="<?= e($checkout['receipt_url']) ?>" target="_blank" rel="noopener">Ver comprovante</a><?php endif; ?>
    </div>
    <small>Use o e-mail e a senha informados antes do pagamento.</small>
  <?php else: ?>
    <h1>Aguardando confirmação</h1>
    <p><?= e($result['erro']??'Ainda não recebemos a confirmação da InfinitePay.') ?> Se o valor já saiu da sua conta, aguarde alguns segundos e atualize esta página.</p>
    <div class="actions">
      <a class="btn primary" href="javascript:location.reload()">Verificar novamente</a>
      <a class="btn secondary" href="/">Voltar ao site</a>
    </div>
  <?php endif; ?>
</main>
</body>
</html>
