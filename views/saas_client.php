<?php
$statusMap=['ativo'=>['Ativo','ok'],'trial'=>['Em teste','trial'],'bloqueado'=>['Bloqueado','block'],'cancelado'=>['Cancelado','cancel']];
$sp=$statusMap[$c['status']]??[$c['status'],''];
$dv=saas_dias_venc($c);
$ref=$c['status']==='trial'?$c['trial_ate']:$c['proximo_venc'];
$pi=saas_plan_info($c['plano']);
?>
<div class="saas-head">
  <div><a class="saas-back" href="?r=saas_clients">← Clientes</a><h1><?= e($c['restaurante']) ?> <span class="pill <?= $sp[1] ?>"><?= e($sp[0]) ?></span></h1>
    <p><?= e($c['responsavel']?:'—') ?> · <?= e($c['whatsapp']?:'sem WhatsApp') ?> <?= $c['dominio']?' · '.e($c['dominio']):'' ?></p>
    <?php if(!empty($c['slug'])): $sl=e($c['slug']); $base=(isset($_SERVER['HTTPS'])?'https':'http').'://'.($_SERVER['HTTP_HOST']??''); ?><p class="saas-links"><b>Links:</b> <a href="<?= $base ?>/cardapio/<?= $sl ?>" target="_blank">Cardápio</a> · <a href="<?= $base ?>/painel/<?= $sl ?>" target="_blank">Painel</a> · <a href="<?= $base ?>/cozinha/<?= $sl ?>" target="_blank">Cozinha</a> · <a href="<?= $base ?>/garcom/<?= $sl ?>" target="_blank">Garçom</a> · <a href="<?= $base ?>/motoboy/<?= $sl ?>" target="_blank">Motoboy</a></p><?php endif; ?>
  </div>
  <div class="saas-head-actions">
    <?php if($c['status']==='bloqueado'): ?>
      <form method="post" action="?r=saas_unblock" onsubmit="return confirm('Desbloquear <?= e($c['restaurante']) ?>?')"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="saas-btn ok">🔓 Desbloquear</button></form>
    <?php elseif($c['status']!=='cancelado'): ?>
      <form method="post" action="?r=saas_block" onsubmit="return confirm('Bloquear o acesso de <?= e($c['restaurante']) ?>?')"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="saas-btn danger">🔒 Bloquear</button></form>
    <?php endif; ?>
    <button class="saas-btn" onclick="document.getElementById('editCli').classList.add('on')">Editar</button>
  </div>
</div>

<?php if($c['status']==='bloqueado'): ?>
  <div class="saas-alert block">Acesso bloqueado <?= $c['bloqueio_manual']?'manualmente':'automaticamente (15 dias após o vencimento)' ?>. Registre um pagamento ou desbloqueie manualmente para reativar.</div>
<?php elseif($dv!==null && $dv>0): ?>
  <div class="saas-alert warn">Vencido há <?= $dv ?> dia(s). Bloqueio automático em <?= max(0,15-$dv) ?> dia(s) se não houver pagamento.</div>
<?php elseif($c['status']==='trial'): ?>
  <div class="saas-alert trial">Em teste grátis até <?= $ref?date('d/m/Y',strtotime($ref)):'—' ?><?= $dv!==null?' ('.abs($dv).' dia(s) restantes)':'' ?>.</div>
<?php endif; ?>

<div class="saas-cols">
  <div>
    <div class="saas-panel info">
      <div class="saas-panel-h"><b>Assinatura</b></div>
      <div class="info-grid">
        <div><span>Plano</span><b class="plan-tag <?= $c['plano'] ?>"><?= saas_plan_label($c['plano']) ?></b></div>
        <div><span>Valor</span><b>
          <?php if($pi['anual']): ?>
            R$ <?= number_format($pi['valor'],2,',','.') ?>/ano &nbsp;·&nbsp; 12× R$ <?= number_format($pi['mensal'],2,',','.') ?> &nbsp;·&nbsp; <span style="color:#22c55e">Economia R$ <?= number_format($pi['economia'],0,',','.') ?>/ano</span>
          <?php else: ?>
            R$ <?= number_format($pi['mensal'],2,',','.') ?>/mês
          <?php endif; ?>
        </b></div>
        <?php if($c['email']): ?><div><span>Login de acesso</span><b><?= e($c['email']) ?></b></div><?php endif; ?>
        <div><span>Dia do vencimento</span><b>dia <?= (int)$c['dia_vencimento'] ?></b></div>
        <div><span>Próximo vencimento</span><b><?= $c['proximo_venc']?date('d/m/Y',strtotime($c['proximo_venc'])):'—' ?></b></div>
        <div><span>Início do teste</span><b><?= $c['trial_ate']?date('d/m/Y',strtotime($c['trial_ate'])).' (fim)':'—' ?></b></div>
        <div><span>Cliente desde</span><b><?= date('d/m/Y',strtotime($c['criado_em'])) ?></b></div>
      </div>
      <?php if($c['obs']): ?><p class="saas-obs"><?= nl2br(e($c['obs'])) ?></p><?php endif; ?>
    </div>

    <div class="saas-panel">
      <div class="saas-panel-h"><b>Histórico de pagamentos</b></div>
      <table class="saas-table">
        <?php if(!$pagamentos): ?><tr><td class="empty">Nenhum pagamento registrado ainda.</td></tr><?php endif; ?>
        <?php foreach($pagamentos as $p): ?>
          <tr>
            <td><b><?= e($p['competencia']) ?></b><small><?= e($p['metodo']) ?> · <?= date('d/m/Y H:i',strtotime($p['pago_em'])) ?><?= $p['obs']?' · '.e($p['obs']):'' ?></small></td>
            <td class="r green">+ <?= money($p['valor']) ?></td>
            <td class="r"><form method="post" action="?r=saas_estorno" onsubmit="return confirm('Estornar este pagamento de <?= money($p['valor']) ?>? O valor será removido do histórico.')" style="margin:0"><input type="hidden" name="payment_id" value="<?= $p['id'] ?>"><input type="hidden" name="client_id" value="<?= $c['id'] ?>"><button class="saas-btn danger" style="padding:.2rem .5rem;font-size:.8rem">Estornar</button></form></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  </div>

  <div>
    <div class="saas-panel pay">
      <div class="saas-panel-h"><b>Registrar pagamento</b></div>
      <form method="post" action="?r=saas_pay" class="saas-form">
        <input type="hidden" name="client_id" value="<?= $c['id'] ?>">
        <label>Valor recebido (R$)<input name="valor" value="<?= number_format($c['valor_mensal'],2,'.','') ?>"></label>
        <label>Forma
          <select name="metodo"><option value="pix">Pix</option><option value="cartao">Cartão</option><option value="boleto">Boleto</option><option value="transferencia">Transferência</option><option value="dinheiro">Dinheiro</option></select>
        </label>
        <label>Observação<input name="obs" placeholder="opcional"></label>
        <button class="saas-btn primary full">✔ Confirmar pagamento</button>
        <small class="saas-note">Reativa o cliente (se bloqueado) e empurra o vencimento em 1 mês.</small>
      </form>
    </div>
    <?php if($c['status']!=='cancelado'): ?>
    <div class="saas-panel danger-zone">
      <div class="saas-panel-h"><b>Cancelar assinatura</b></div>
      <form method="post" action="?r=saas_cancel" onsubmit="return confirm('Cancelar definitivamente a assinatura de <?= e($c['restaurante']) ?>?')">
        <input type="hidden" name="id" value="<?= $c['id'] ?>">
        <button class="saas-btn danger full">Cancelar cliente</button>
      </form>
    </div>
    <?php else: ?>
    <div class="saas-panel danger-zone">
      <div class="saas-panel-h"><b>Excluir permanentemente</b></div>
      <p style="font-size:.85rem;color:var(--muted);margin:0 0 .75rem">Remove o cliente e todo o histórico de pagamentos. Irreversível.</p>
      <form method="post" action="?r=saas_delete_client" onsubmit="return confirm('Excluir PERMANENTEMENTE <?= e($c['restaurante']) ?> e todo o histórico? Não pode ser desfeito.')">
        <input type="hidden" name="id" value="<?= $c['id'] ?>">
        <button class="saas-btn danger full">🗑️ Excluir permanentemente</button>
      </form>
    </div>
    <?php endif; ?>
  </div>
</div>

<div class="saas-modal" id="editCli">
  <div class="saas-modal-in">
    <div class="saas-modal-h"><b>Editar cliente</b><button onclick="document.getElementById('editCli').classList.remove('on')">×</button></div>
    <form method="post" action="?r=saas_client_save" class="saas-form">
      <input type="hidden" name="id" value="<?= $c['id'] ?>">
      <div class="grid2">
        <label>Restaurante *<input name="restaurante" value="<?= e($c['restaurante']) ?>" required></label>
        <label>Responsável<input name="responsavel" value="<?= e($c['responsavel']) ?>"></label>
        <label>Login de acesso (e-mail ou usuário)<input name="email" type="text" value="<?= e($c['email']) ?>" placeholder="email@dominio.com ou nome-de-usuario" autocomplete="off"></label>
        <label>WhatsApp<input name="whatsapp" value="<?= e($c['whatsapp']) ?>"></label>
        <label>Slug (URL)<input name="client_slug" value="<?= e($c['slug']??'') ?>" readonly style="background:#f5f5f5;cursor:not-allowed" title="Alteração de slug desativada temporariamente — renomear apagaria os dados do cliente"><small style="color:#b45309;font-size:11px">⚠ Bloqueado — alterar o slug apagaria os dados do restaurante.</small></label>
        <label>Domínio / link<input name="dominio" value="<?= e($c['dominio']) ?>"></label>
        <label>Cidade<input name="cidade" value="<?= e($c['cidade']) ?>"></label>
        <label>UF<input name="uf" maxlength="2" value="<?= e($c['uf']) ?>"></label>
        <label>Plano<select name="plano">
          <option value="pro" <?= $c['plano']==='pro'?'selected':'' ?>>Pró — R$ 149/mês</option>
          <option value="pro_anual" <?= $c['plano']==='pro_anual'?'selected':'' ?>>Pró Anual — R$ 1.548/ano · 12× R$ 129 · Economize R$ 240</option>
          <option value="premium" <?= $c['plano']==='premium'?'selected':'' ?>>Premium — R$ 199/mês</option>
          <option value="premium_anual" <?= $c['plano']==='premium_anual'?'selected':'' ?>>Premium Anual — R$ 2.148/ano · 12× R$ 179 · Economize R$ 240</option>
        </select></label>
        <label>Dia do vencimento<input name="dia_vencimento" type="number" min="1" max="28" value="<?= (int)$c['dia_vencimento'] ?>"></label>
      </div>
      <label>Nova senha (deixe em branco para não alterar)<input name="senha_nova" type="password" autocomplete="new-password" placeholder="Somente preencha para definir/redefinir a senha do cliente"></label>
      <label>Observações<textarea name="obs" rows="2"><?= e($c['obs']) ?></textarea></label>
      <button class="saas-btn primary full">Salvar alterações</button>
    </form>
  </div>
</div>
