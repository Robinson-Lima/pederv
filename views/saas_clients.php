<?php
function saas_status_pill($s){
  $map=['ativo'=>['Ativo','ok'],'trial'=>['Em teste','trial'],'bloqueado'=>['Bloqueado','block'],'cancelado'=>['Cancelado','cancel']];
  $x=$map[$s]??[$s,'']; return '<span class="pill '.$x[1].'">'.e($x[0]).'</span>';
}
?>
<?php if(!empty($_GET['erro'])): ?>
  <div class="saas-alert block">Slug "<?= e($_GET['slug']??'') ?>" já está em uso por outro cliente. Escolha um diferente.</div>
<?php endif; ?>
<div class="saas-head">
  <div><h1>Clientes</h1><p><?= $m['total'] ?> cadastrados · <?= $m['ativos'] ?> ativos · <?= $m['trial'] ?> em teste · <?= $m['bloqueados'] ?> bloqueados</p></div>
  <button class="saas-btn primary" onclick="document.getElementById('novoCli').classList.add('on')">＋ Novo cliente</button>
</div>

<form class="saas-filter" method="get" action="">
  <input type="hidden" name="r" value="saas_clients">
  <input name="q" value="<?= e($q) ?>" placeholder="⌕ Buscar por nome, responsável, WhatsApp ou e-mail">
  <select name="st" onchange="this.form.submit()">
    <option value="">Todos os status</option>
    <?php foreach(['trial'=>'Em teste','ativo'=>'Ativos','bloqueado'=>'Bloqueados','cancelado'=>'Cancelados'] as $k=>$v): ?>
      <option value="<?= $k ?>" <?= $st===$k?'selected':'' ?>><?= $v ?></option>
    <?php endforeach; ?>
  </select>
  <button class="saas-btn">Filtrar</button>
</form>

<div class="saas-panel">
  <table class="saas-table wide">
    <tr class="head"><th>Restaurante</th><th>Plano</th><th>Status</th><th>Mensal</th><th>Vencimento</th><th>WhatsApp</th></tr>
    <?php if(!$clientes): ?><tr><td colspan="6" class="empty">Nenhum cliente encontrado.</td></tr><?php endif; ?>
    <?php foreach($clientes as $c): $dv=saas_dias_venc($c); $ref=$c['status']==='trial'?$c['trial_ate']:$c['proximo_venc']; ?>
      <tr onclick="location.href='?r=saas_client&id=<?= $c['id'] ?>'">
        <td><b><?= e($c['restaurante']) ?></b><small><?= e($c['responsavel']?:$c['cidade']) ?></small></td>
        <td><span class="plan-tag <?= $c['plano'] ?>"><?= saas_plan_label($c['plano']) ?></span></td>
        <td><?= saas_status_pill($c['status']) ?></td>
        <td><?= money($c['valor_mensal']) ?></td>
        <td><?= $ref?date('d/m/Y',strtotime($ref)):'—' ?><?php if($dv!==null): ?><small class="<?= $dv>0?'late':'' ?>"><?= $dv>0?$dv.'d em atraso':abs($dv).'d restantes' ?></small><?php endif; ?></td>
        <td><?= e($c['whatsapp']) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>

<div class="saas-modal" id="novoCli">
  <div class="saas-modal-in">
    <div class="saas-modal-h"><b>Novo cliente</b><button onclick="document.getElementById('novoCli').classList.remove('on')">×</button></div>
    <form method="post" action="?r=saas_client_save" class="saas-form">
      <?= saas_csrf_field() ?>
      <div class="grid2">
        <label>Restaurante *<input name="restaurante" required></label>
        <label>Responsável<input name="responsavel"></label>
        <label>WhatsApp<input name="whatsapp" placeholder="(00) 00000-0000"></label>
        <label>E-mail (login) *<input name="email" type="email" required></label>
        <label>Slug (URL) *<input name="client_slug" required pattern="[a-z0-9_-]+" placeholder="barbaburguer" title="Apenas letras minúsculas, números e hífen"></label>
        <label>Senha *<input name="senha_nova" type="password" required minlength="6" placeholder="Mínimo 6 caracteres"></label>
        <label>Domínio / link<input name="dominio" placeholder="cliente.rvcardapios.com.br"></label>
        <label>Cidade<input name="cidade"></label>
        <label>UF<input name="uf" maxlength="2"></label>
        <label>Plano
          <select name="plano">
            <option value="pro">Pró — <?= money(saas_plan_price('pro')) ?>/mês</option>
            <option value="pro_anual">Pró Anual — <?= money(saas_plan_price('pro_anual')) ?>/ano</option>
            <option value="premium">Premium — <?= money(saas_plan_price('premium')) ?>/mês</option>
            <option value="premium_anual">Premium Anual — <?= money(saas_plan_price('premium_anual')) ?>/ano</option>
          </select>
        </label>
        <label>Dia do vencimento<input name="dia_vencimento" type="number" min="1" max="28" value="10"></label>
      </div>
      <label>Observações<textarea name="obs" rows="2"></textarea></label>
      <p class="saas-note">O cliente começa com <b><?= (int)setting_get('saas_trial_dias','7') ?> dias de teste grátis</b>. Após o vencimento, o bloqueio é automático 15 dias depois.</p>
      <button class="saas-btn primary full">Cadastrar cliente</button>
    </form>
  </div>
</div>
