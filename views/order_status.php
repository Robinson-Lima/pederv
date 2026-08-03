<?php
$steps=['novo'=>'Pedido recebido','em_preparo'=>'Em preparo','saiu_entrega'=>'Saiu para entrega','entregue'=>'Entregue','concluido'=>'Concluído'];
$order=array_keys($steps); $current=$o?array_search($o['status'],$order,true):false;
$_bSlug=current_slug(); $_backMenu=$_bSlug?('/cardapio/'.$_bSlug):'?r=menu';
?>
<div class="status-page">
  <?php if(!$o): ?><h2>Pedido não encontrado</h2><a href="<?= $_backMenu ?>">Voltar ao cardápio</a>
  <?php else: ?>
    <div class="status-head"><span>Acompanhar pedido</span><h2><?= e($o['codigo']) ?></h2><p>Atualização automática do restaurante</p></div>
    <?php if($o['status']==='cancelado'): ?><div class="status-canceled"><b>Pedido cancelado</b><span>Entre em contato com o restaurante se precisar de ajuda.</span></div>
    <?php else: ?><div class="timeline"><?php foreach($steps as $key=>$label): $idx=array_search($key,$order,true); $on=$current!==false&&$idx<=$current; ?><div class="time-step <?= $on?'on':'' ?>"><i><?= $on?'✓':'' ?></i><div><b><?= $label ?></b><?php if($key===$o['status']): ?><small>Status atual</small><?php endif; ?></div></div><?php endforeach; ?></div><div class="delivery-forecast">◷ Previsão: <b><?= date('H:i',strtotime($o['criado_em'].' +45 minutes')) ?></b></div><?php endif; ?>
    <div class="status-items"><h3>Itens do pedido</h3><?php foreach($orderItems??[] as $it): ?><div><span><?= (int)$it['qtd'] ?>× <?= e($it['nome']) ?></span><b><?= money($it['qtd']*$it['preco']) ?></b></div><?php endforeach; ?></div>
    <div class="status-payment"><span>Pagamento</span><b><?= e(payment_label($o['pagamento_metodo'])) ?></b><em class="<?= $o['pagamento_status']==='pago'?'paid':'pending' ?>"><?= e(payment_situation_label($o)) ?></em></div>
    <div class="status-total"><span>Total</span><b><?= money($o['total']) ?></b></div>
    <a class="status-back" href="<?= $_backMenu ?>">Voltar ao cardápio</a>
  <?php endif; ?>
</div>
<?php if($o && !in_array($o['status'],['entregue','concluido','cancelado'],true)): ?><script>setTimeout(()=>location.reload(),15000)</script><?php endif; ?>
