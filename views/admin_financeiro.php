<?php
$maxDia = 0; foreach($dias as $d) $maxDia=max($maxDia,(float)$d['v']);
$diaNome=['Sun'=>'Dom','Mon'=>'Seg','Tue'=>'Ter','Wed'=>'Qua','Thu'=>'Qui','Fri'=>'Sex','Sat'=>'Sáb'];
$metColor=['pix'=>'#00B67A','cartao'=>'#3B82F6','dinheiro'=>'#22A06B','ifood'=>'#EA1D2C','whatsapp'=>'#25A35A'];
?>
<div class="wrap dash">
  <h2><?= e(cfg('restaurante')) ?></h2>
  <?= admin_subnav('admin_financeiro') ?>

  <div class="subnav" style="margin-top:0">
    <a href="?r=admin_financeiro" class="<?= $sub!=='ifood'?'on':'' ?>">Geral</a>
    <a href="?r=admin_financeiro&sub=ifood" class="<?= $sub==='ifood'?'on':'' ?>">🔴 Vendas iFood</a>
  </div>

  <div class="stats" style="grid-template-columns:repeat(4,1fr)">
    <div class="stat"><div class="l">Vendas hoje</div><div class="v"><?= money($hoje['v']) ?></div><div style="font-size:11px;color:var(--muted)"><?= (int)$hoje['n'] ?> pedidos</div></div>
    <div class="stat"><div class="l">Últimos 7 dias</div><div class="v"><?= money($semana['v']) ?></div><div style="font-size:11px;color:var(--muted)"><?= (int)$semana['n'] ?> pedidos</div></div>
    <div class="stat"><div class="l">Este mês</div><div class="v"><?= money($mes['v']) ?></div><div style="font-size:11px;color:var(--muted)"><?= (int)$mes['n'] ?> pedidos</div></div>
    <div class="stat"><div class="l">Ticket médio (7d)</div><div class="v"><?= money($semana['n']?($semana['v']/$semana['n']):0) ?></div></div>
  </div>

  <div class="two">
    <div class="cfgcard">
      <h3>📈 Vendas por dia (7 dias)</h3>
      <div class="bars">
        <?php if(!$dias): ?><p style="color:var(--muted);font-size:13px">Sem vendas ainda.</p><?php endif; ?>
        <?php foreach($dias as $d):
          $h = $maxDia>0 ? round(((float)$d['v']/$maxDia)*130)+6 : 6;
          $lbl = $diaNome[date('D',strtotime($d['dia']))] ?? substr($d['dia'],8,2);
        ?>
          <div class="bar"><div class="bval"><?= money($d['v']) ?></div><div class="bfill" style="height:<?= $h ?>px"></div><div class="blbl"><?= $lbl ?></div></div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="cfgcard">
      <h3>💳 Por forma de pagamento (7d)</h3>
      <?php if(!$metodos): ?><p style="color:var(--muted);font-size:13px">Sem dados.</p><?php endif; ?>
      <?php foreach($metodos as $m):
        $pct = $semana['v']>0 ? round(((float)$m['v']/$semana['v'])*100) : 0;
        $cor = $metColor[$m['m']] ?? '#8A909C';
      ?>
        <div class="metrow">
          <div class="mlbl"><span class="dot2" style="background:<?= $cor ?>"></span><?= e(ucfirst($m['m'])) ?></div>
          <div class="mbar"><div style="width:<?= $pct ?>%;background:<?= $cor ?>"></div></div>
          <div class="mval"><?= money($m['v']) ?> <small><?= $pct ?>%</small></div>
        </div>
      <?php endforeach; ?>

      <h3 style="margin-top:16px">📡 Por canal (7d)</h3>
      <?php foreach($canais as $c): ?>
        <div class="metrow"><div class="mlbl"><?= e(ucfirst($c['canal'])) ?></div>
          <div class="mval"><?= money($c['v']) ?> <small><?= (int)$c['n'] ?> ped.</small></div></div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="two">
    <div class="cfgcard">
      <h3>🏆 Produtos mais vendidos (7d)</h3>
      <?php if(!$top): ?><p style="color:var(--muted);font-size:13px">Sem vendas ainda.</p><?php endif; ?>
      <?php foreach($top as $i=>$t): ?>
        <div class="metrow"><div class="mlbl"><b style="color:var(--brand)"><?= $i+1 ?>º</b> <?= e($t['nome']) ?></div>
          <div class="mval"><?= (int)$t['q'] ?> un · <?= money($t['v']) ?></div></div>
      <?php endforeach; ?>
    </div>

    <div class="cfgcard">
      <h3>🗓 Fechamento da semana</h3>
      <div class="fechrow"><span>Total recebido (7 dias)</span><b><?= money($semana['v']) ?></b></div>
      <div class="fechrow"><span>Pedidos pagos</span><b><?= (int)$semana['n'] ?></b></div>
      <div class="fechrow"><span>Ticket médio</span><b><?= money($semana['n']?($semana['v']/$semana['n']):0) ?></b></div>
      <?php foreach($metodos as $m): ?>
        <div class="fechrow sub"><span><?= e(ucfirst($m['m'])) ?></span><span><?= money($m['v']) ?></span></div>
      <?php endforeach; ?>
      <p style="font-size:11px;color:var(--muted);margin-top:10px">Resumo automático das vendas pagas dos últimos 7 dias. Para o caixa físico do dia, use a aba <b>Caixa</b>.</p>
    </div>
  </div>
</div>
