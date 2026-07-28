<div class="kds-head"><div><span>RV KDS</span><h1>Display da cozinha</h1></div><div class="kds-clock" id="clock"></div><a href="?r=logout">Sair</a></div>
<div class="kds-tabs"><button class="on">Pedidos ativos <b><?= count($orders) ?></b></button><button onclick="location.reload()">Atualizar</button></div>
<main class="kds-grid">
<?php foreach($orders as $o): $mins=max(0,(time()-strtotime($o['criado_em']))/60); ?>
  <article class="kds-ticket <?= e($o['status']) ?>">
    <header><div><b><?= e($o['codigo']) ?></b><span><?= e($o['tipo']) ?><?= $o['mesa']?' · Mesa '.e($o['mesa']):'' ?></span></div><time><?= (int)$mins ?> min</time></header>
    <h2><?= e($o['cliente_nome']) ?></h2>
    <?php if(!empty($o['atendente'])): ?><div class="kds-waiter">👤 Pedido lançado por: <b><?= e($o['atendente']) ?></b></div><?php endif; ?>
    <ul><?php foreach($items[$o['id']]??[] as $it): ?><li><strong><?= (int)$it['qtd'] ?>×</strong> <?= e($it['nome']) ?></li><?php endforeach; ?></ul>
    <?php if($o['status']==='aceito'): ?><button class="kds-action start" onclick="move(<?= $o['id'] ?>,'em_preparo')">Iniciar preparação</button>
    <?php elseif($o['status']==='em_preparo'): ?><button class="kds-action ready" onclick="move(<?= $o['id'] ?>,'pronto')">Pedido pronto</button>
    <?php else: ?><div class="kds-done">✓ Pronto para retirada/entrega</div><?php endif; ?>
  </article>
<?php endforeach; ?>
<?php if(!$orders): ?><div class="kds-empty">✓ Tudo em dia<br><small>Nenhum pedido aguardando preparo.</small></div><?php endif; ?>
</main>
<script>
setInterval(()=>clock.textContent=new Date().toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'}),1000);
async function move(id,status){await fetch('?r=kds_set_status',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`id=${id}&status=${status}`});location.reload()}
setTimeout(()=>location.reload(),10000);
</script>
