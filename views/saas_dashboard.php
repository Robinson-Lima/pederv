<?php
function saas_status_pill($s){
  $map=['ativo'=>['Ativo','ok'],'trial'=>['Em teste','trial'],'bloqueado'=>['Bloqueado','block'],'cancelado'=>['Cancelado','cancel']];
  $x=$map[$s]??[$s,'']; return '<span class="pill '.$x[1].'">'.e($x[0]).'</span>';
}
?>
<div class="saas-head">
  <div><h1>Visão geral</h1><p>Resumo da operação da plataforma · <?= date('d/m/Y') ?></p></div>
  <a class="saas-btn primary" href="?r=saas_clients">Gerenciar clientes →</a>
</div>

<div class="saas-cards">
  <div class="saas-card"><span class="c-l">Receita recorrente (MRR)</span><span class="c-v"><?= money($m['mrr']) ?></span><span class="c-s"><?= $m['ativos'] ?> assinaturas ativas</span></div>
  <div class="saas-card"><span class="c-l">Recebido no mês</span><span class="c-v"><?= money($m['receita_mes']) ?></span><span class="c-s">Total histórico: <?= money($m['receita_total']) ?></span></div>
  <div class="saas-card"><span class="c-l">Clientes ativos</span><span class="c-v"><?= $m['ativos'] ?></span><span class="c-s"><?= $m['trial'] ?> em teste grátis</span></div>
  <div class="saas-card <?= $m['inadimplentes']?'alert':'' ?>"><span class="c-l">Bloqueados / inadimplentes</span><span class="c-v"><?= $m['bloqueados'] ?></span><span class="c-s"><?= $m['cancelados'] ?> cancelados</span></div>
</div>

<div class="saas-cols">
  <section class="saas-panel">
    <div class="saas-panel-h"><b>Planos ativos</b></div>
    <?php $tot=max(1,$m['basico']+$m['basico_anual']+$m['pro']+$m['premium']+$m['pro_anual']+$m['premium_anual']); ?>
    <div class="plan-bar">
      <div class="plan-row"><span>Básico mensal</span><div class="bar"><i style="width:<?= round($m['basico']/$tot*100) ?>%;background:#64748b"></i></div><b><?= $m['basico'] ?></b></div>
      <div class="plan-row"><span>Básico anual</span><div class="bar"><i style="width:<?= round($m['basico_anual']/$tot*100) ?>%;background:#94a3b8"></i></div><b><?= $m['basico_anual'] ?></b></div>
      <div class="plan-row"><span>Pró mensal</span><div class="bar"><i style="width:<?= round($m['pro']/$tot*100) ?>%"></i></div><b><?= $m['pro'] ?></b></div>
      <div class="plan-row"><span>Pró anual</span><div class="bar" style=""><i style="width:<?= round($m['pro_anual']/$tot*100) ?>%;background:#16a06d"></i></div><b><?= $m['pro_anual'] ?></b></div>
      <div class="plan-row"><span>Premium mensal</span><div class="bar prem"><i style="width:<?= round($m['premium']/$tot*100) ?>%"></i></div><b><?= $m['premium'] ?></b></div>
      <div class="plan-row"><span>Premium anual</span><div class="bar prem"><i style="width:<?= round($m['premium_anual']/$tot*100) ?>%;background:#c2410c"></i></div><b><?= $m['premium_anual'] ?></b></div>
    </div>
    <div class="plan-prices" style="display:grid;grid-template-columns:1fr 1fr;gap:4px 16px;font-size:12px;color:#6b7280;margin-top:12px">
      <span>Básico <?= money(saas_plan_price('basico')) ?>/mês</span>
      <span>Assinatura BASICO Anual <?= money(saas_plan_price('basico_anual')) ?>/ano</span>
      <span>Pró <?= money(saas_plan_price('pro')) ?>/mês</span>
      <span>Assinatura Pró Anual <?= money(saas_plan_price('pro_anual')) ?>/ano</span>
      <span>Premium <?= money(saas_plan_price('premium')) ?>/mês</span>
      <span>Assinatura Premium Anual <?= money(saas_plan_price('premium_anual')) ?>/ano</span>
    </div>
  </section>

  <section class="saas-panel">
    <div class="saas-panel-h"><b>Faturamento por mês</b></div>
    <?php if(!$receitaMeses): ?>
      <p class="empty" style="padding:18px 0;color:#9a938a;font-size:13px">Nenhum pagamento registrado ainda.</p>
    <?php else: ?>
      <?php $maxV=max(array_column($receitaMeses,'v')?:[1]); ?>
      <div style="display:grid;gap:8px;margin-top:4px">
      <?php foreach($receitaMeses as $rm): ?>
        <?php $pct=$maxV>0?round($rm['v']/$maxV*100):0; $label=date('M/y',strtotime($rm['mes'].'-01')); ?>
        <div style="display:grid;grid-template-columns:52px 1fr 80px;align-items:center;gap:8px;font-size:12.5px">
          <span style="color:#6b7280"><?= $label ?></span>
          <div style="background:#f0f3f6;border-radius:99px;height:8px;overflow:hidden">
            <div style="width:<?= $pct ?>%;height:100%;background:#0797e5;border-radius:99px"></div>
          </div>
          <span style="text-align:right;font-weight:700;color:#141210"><?= money($rm['v']) ?><small style="color:#9a938a;font-weight:400"> (<?= $rm['n'] ?>)</small></span>
        </div>
      <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="saas-panel">
    <div class="saas-panel-h"><b>Próximos vencimentos</b><a href="?r=saas_clients">ver todos</a></div>
    <table class="saas-table">
      <?php if(!$vencendo): ?><tr><td class="empty">Nenhum cliente cadastrado ainda.</td></tr><?php endif; ?>
      <?php foreach($vencendo as $c): $dv=saas_dias_venc($c); ?>
        <tr onclick="location.href='?r=saas_client&id=<?= $c['id'] ?>'">
          <td><b><?= e($c['restaurante']) ?></b><small><?= saas_plan_label($c['plano']) ?></small></td>
          <td><?= saas_status_pill($c['status']) ?></td>
          <td class="r"><?php $ref=$c['status']==='trial'?$c['trial_ate']:$c['proximo_venc']; echo $ref?date('d/m',strtotime($ref)):'—'; ?><small><?= $dv===null?'':($dv>0?$dv.'d em atraso':abs($dv).'d restantes') ?></small></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </section>

  <section class="saas-panel">
    <div class="saas-panel-h"><b>Bloqueados</b></div>
    <table class="saas-table">
      <?php if(!$bloqueados): ?><tr><td class="empty">Nenhum cliente bloqueado. 🎉</td></tr><?php endif; ?>
      <?php foreach($bloqueados as $c): ?>
        <tr onclick="location.href='?r=saas_client&id=<?= $c['id'] ?>'">
          <td><b><?= e($c['restaurante']) ?></b><small><?= e($c['whatsapp']) ?></small></td>
          <td class="r"><?= $c['bloqueio_manual']?'manual':'automático' ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </section>

  <section class="saas-panel">
    <div class="saas-panel-h"><b>Últimos pagamentos</b></div>
    <table class="saas-table">
      <?php if(!$ultimosPg): ?><tr><td class="empty">Nenhum pagamento registrado.</td></tr><?php endif; ?>
      <?php foreach($ultimosPg as $p): ?>
        <tr><td><b><?= e($p['restaurante']) ?></b><small><?= e($p['metodo']) ?> · <?= date('d/m H:i',strtotime($p['pago_em'])) ?></small></td><td class="r green">+ <?= money($p['valor']) ?></td></tr>
      <?php endforeach; ?>
    </table>
  </section>
</div>
