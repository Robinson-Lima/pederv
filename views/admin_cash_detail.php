<?php
$tot=['venda'=>0,'retirada'=>0,'suprimento'=>0];foreach($sales as $s)$tot[$s['tipo']]=($tot[$s['tipo']]??0)+(float)$s['valor'];
$expected=(float)$cash['saldo_inicial']+$tot['venda']+$tot['suprimento']-$tot['retirada'];
?>
<div class="wrap dash cash-detail">
  <div class="orders-title"><div><h2>Extrato do caixa #<?= (int)$cash['id'] ?></h2><p><?= e($cash['operador']?:'Operador não informado') ?> · aberto em <?= e($cash['aberto_em']) ?></p></div><a class="btn" href="?r=admin_caixa">← Voltar ao caixa</a></div>
  <div class="stats" style="grid-template-columns:repeat(4,1fr)">
    <div class="stat"><div class="l">Saldo inicial</div><div class="v"><?= money($cash['saldo_inicial']) ?></div></div>
    <div class="stat"><div class="l">Vendas</div><div class="v"><?= money($tot['venda']) ?></div></div>
    <div class="stat"><div class="l">Retiradas</div><div class="v"><?= money($tot['retirada']) ?></div></div>
    <div class="stat"><div class="l">Esperado</div><div class="v"><?= money($expected) ?></div></div>
  </div>
  <div style="background:#fff;border:1px solid #ddd;border-radius:14px;padding:18px;overflow:auto;margin-top:18px">
    <h3>Vendas do caixa (<?= count(array_filter($sales,fn($s)=>$s['tipo']==='venda')) ?>)</h3>
    <table style="width:100%;border-collapse:collapse"><thead><tr style="background:#111827;color:#fff"><th>Venda</th><th>Hora</th><th>Cliente</th><th>Pagamento</th><th>Total</th><th>Status</th><th>Itens</th></tr></thead><tbody>
    <?php foreach($sales as $s): if($s['tipo']!=='venda')continue; ?><tr style="border-bottom:1px solid #ddd"><td><?= e($s['codigo']?:'#'.$s['order_id']) ?></td><td><?= e(substr($s['criado_em'],11,5)) ?></td><td><?= e($s['cliente_nome']?:'Consumidor') ?></td><td><?= e(payment_label($s['pagamento_metodo'])) ?></td><td><?= money($s['valor']) ?></td><td><?= e($s['pagamento_status']==='pago'?'Finalizada':'Pendente') ?></td><td><details><summary>Ver itens</summary><?php foreach($items[$s['order_id']]??[] as $it): ?><div><?= (int)$it['qtd'] ?>× <?= e($it['nome']) ?> — <?= money($it['qtd']*$it['preco']) ?></div><?php endforeach; ?></details></td></tr><?php endforeach; ?>
    </tbody></table>
  </div>
</div>
<style>.cash-detail th,.cash-detail td{padding:12px;text-align:left}.cash-detail summary{cursor:pointer;font-weight:800;color:#f3702b}.btn-mini{display:inline-block;margin-top:6px;color:#f3702b;font-weight:800}</style>
