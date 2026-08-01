<?php $pago=$pago??false; $order=$order??[]; $receipt_url=$receipt_url??''; $status_url=$status_url??''; ?>
<div class="wrap" style="max-width:520px;margin:40px auto;text-align:center;padding:24px">
  <div style="width:64px;height:64px;border-radius:50%;display:grid;place-items:center;margin:0 auto 16px;font-size:30px;background:<?= $pago?'#e9f8f0':'#fff3e9' ?>;color:<?= $pago?'#138a5b':'#f26422' ?>"><?= $pago?'✓':'⏳' ?></div>
  <?php if($pago): ?>
    <h2 style="margin:0 0 8px">Pagamento confirmado!</h2>
    <p style="color:#64605c">Pedido <b><?= e($order['codigo']??'') ?></b> pago com sucesso. O restaurante já recebeu a confirmação.</p>
  <?php else: ?>
    <h2 style="margin:0 0 8px">Aguardando confirmação</h2>
    <p style="color:#64605c">Ainda não recebemos a confirmação do pagamento. Se o valor já saiu da sua conta, aguarde alguns segundos e atualize esta página.</p>
    <a href="javascript:location.reload()" style="display:inline-block;margin:12px 0;padding:12px 28px;background:#ff6726;color:#fff;border-radius:10px;text-decoration:none;font-weight:700">Verificar novamente</a>
  <?php endif; ?>
  <?php if($receipt_url): ?><div style="margin:12px 0"><a href="<?= e($receipt_url) ?>" target="_blank" rel="noopener" style="color:#ff6726">Ver comprovante</a></div><?php endif; ?>
  <a href="<?= e($status_url) ?>" style="display:inline-block;margin:8px 0;padding:12px 28px;border:1px solid #e6d8cf;border-radius:10px;text-decoration:none;color:#17130f;font-weight:700">Acompanhar meu pedido</a>
</div>
