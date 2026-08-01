<?php
$pagos=[]; $pend=[]; $totPago=0; $totPend=0;
foreach($orders as $o){
  if($o['pagamento_status']==='pago'){$pagos[]=$o;$totPago+=$o['total'];}
  else {$pend[]=$o;$totPend+=$o['total'];}
}
?>
<div class="wrap dash">
  <h2><?= e(cfg('restaurante')) ?></h2>
  <?= admin_subnav('admin_payments') ?>
  <?php
    $gwAtivo=setting_get('gw_nome','');
    $ipayHandle=setting_get('gw_infinitepay_handle','');
    $pixKey=setting_get('pix_key','');
  ?>
  <div style="margin:16px 0;padding:18px;background:#fff;border:1px solid #ddd;border-radius:14px">
    <h3>Formas de pagamento online ativas</h3>
    <p style="margin:8px 0;font-size:14px">
      <?php if($gwAtivo==='InfinitePay'&&$ipayHandle!==''): ?>
        💳 <b>Cartão online</b> via InfinitePay (tag: <b>$<?= e($ipayHandle) ?></b>) — ativo
      <?php elseif($gwAtivo!==''&&$gwAtivo!=='InfinitePay'): ?>
        💳 <b>Cartão online</b> via <?= e($gwAtivo) ?> — configurar em <a href="?r=admin_settings">Configurações</a>
      <?php else: ?>
        💳 Cartão online — <b>não configurado</b>. <a href="?r=admin_settings">Configurar gateway</a>
      <?php endif; ?>
    </p>
    <p style="margin:8px 0;font-size:14px">
      <?php if($pixKey!==''): ?>
        💠 <b>Pix</b> — ativo (chave: <?= e(mb_substr($pixKey,0,6)) ?>…)
      <?php else: ?>
        💠 Pix — <b>não configurado</b>. <a href="?r=admin_settings">Configurar chave Pix</a>
      <?php endif; ?>
    </p>
    <p style="color:#87817b;font-size:12px;margin-top:10px">A confirmação de pagamentos online é automática via InfinitePay.</p>
  </div>
  <div class="stats" style="grid-template-columns:1fr 1fr">
    <div class="stat"><div class="l">✔ Recebido (pago)</div><div class="v"><?= money($totPago) ?></div></div>
    <div class="stat"><div class="l">⏳ Pendente</div><div class="v"><?= money($totPend) ?></div></div>
  </div>
  <div class="paycols">
    <div>
      <div class="paysec">🟠 Pendentes (<?= count($pend) ?>)</div>
      <?php foreach($pend as $o): ?>
        <div class="pcol">
          <div class="oid"><?= e($o['codigo']) ?></div>
          <div class="w"><div class="nm"><?= e($o['cliente_nome']) ?></div>
            <div class="mt"><?= e($o['pagamento_metodo']) ?> · <?= e($o['status']) ?></div></div>
          <div class="amt"><?= money($o['total']) ?><br>
            <button class="markpago" onclick="pago(<?= $o['id'] ?>)">marcar pago</button></div>
        </div>
      <?php endforeach; ?>
      <?php if(!$pend): ?><p style="color:#727884;font-size:13px">Nada pendente 🎉</p><?php endif; ?>
    </div>
    <div>
      <div class="paysec">🟢 Pagos (<?= count($pagos) ?>)</div>
      <?php foreach($pagos as $o): ?>
        <div class="pcol">
          <div class="oid"><?= e($o['codigo']) ?></div>
          <div class="w"><div class="nm"><?= e($o['cliente_nome']) ?></div>
            <div class="mt"><?= e($o['pagamento_metodo']) ?><?= $o['pagamento_metodo']==='pix'?' · 0% taxa':'' ?></div>
            <?php if(!empty($o['recebido_por'])): ?><div class="mt recebido">🧾 Fechado e recebido por <b><?= e($o['recebido_por']) ?></b><?= !empty($o['fechado_em'])?' · '.e(substr($o['fechado_em'],11,5)):'' ?></div><?php endif; ?></div>
          <div class="amt" style="color:#22A06B"><?= money($o['total']) ?></div>
        </div>
      <?php endforeach; ?>
      <?php if(!$pagos): ?><p style="color:#727884;font-size:13px">Ainda sem pagamentos.</p><?php endif; ?>
    </div>
  </div>
</div>
<script>
async function pago(id){await fetch('?r=admin_set_pago',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+id});location.reload();}
</script>
