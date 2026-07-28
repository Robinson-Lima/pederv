<div class="mhead"><div class="mh"><div class="lg">🍽</div>
  <div><h2>Mapa de mesas</h2><div class="meta">Atendente: <?= e($garcom) ?> · <a href="?r=waiter_sair_nome" onclick="return confirm('Sair para outro garçom entrar neste tablet?')" style="color:#EE7430">Sair</a></div></div></div></div>
<div style="padding:8px 16px">
  <div class="legend"><span><i class="lg" style="background:#1f4a38"></i> Livre</span><span><i class="lg" style="background:var(--brand)"></i> Ocupada</span></div>
  <div class="mesas">
    <?php foreach($tables as $t): $ocupada=isset($abertas[$t['numero']]); ?>
      <a href="?r=waiter_comanda&mesa=<?= e($t['numero']) ?>" class="mesa <?= $ocupada?'ocup':'livre' ?>" style="text-decoration:none">
        <div class="num">Mesa <?= e($t['numero']) ?></div>
        <?php if($ocupada): ?><div class="val"><?= money($abertas[$t['numero']]) ?></div><?php else: ?><div class="st">Livre</div><?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>
  <a href="?r=admin_orders" class="gbtn" style="background:#171C25;color:#fff;text-decoration:none">🛵 Ver pedidos delivery</a>
</div>
