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
  <form class="payment-webhook-card" method="post" action="?r=admin_payment_webhook_settings" style="margin:16px 0;padding:18px;background:#fff;border:1px solid #ddd;border-radius:14px">
    <h3>Confirmação automática de Pix e cartão online</h3>
    <p>Seu provedor de pagamento ou o n8n deve avisar este endereço quando o pagamento for aprovado.</p>
    <label>Endereço do webhook</label><input readonly value="<?= e((isset($_SERVER['HTTPS'])?'https':'http').'://'.($_SERVER['HTTP_HOST']??'rvautomacao.com.br').'/cardapio/?r=payment_webhook') ?>" style="width:100%;padding:10px">
    <label>Segredo de segurança</label><input name="secret" value="<?= e(setting_get('payment_webhook_secret','')) ?>" placeholder="Deixe vazio para gerar automaticamente" style="width:100%;padding:10px">
    <button class="markpago" style="margin-top:10px">Salvar / gerar segredo</button>
  </form>
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
