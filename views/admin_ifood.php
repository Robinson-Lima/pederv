<?php $ifoodOn = cfg('ifood')['ativo'] ?? false; ?>
<div class="wrap dash">
  <h2><?= e(cfg('restaurante')) ?></h2>
  <?= admin_subnav('admin_ifood') ?>

  <div class="ifhead">
    <div class="lg">iF</div>
    <div class="tt"><b>Pedidos iFood — no mesmo painel</b>
      <div>Aceite, prepare e despache sem sair do RVCARDÁPIOS</div></div>
    <div class="conn" style="color:<?= $ifoodOn?'#22A06B':'#9a6512' ?>">
      ● <?= $ifoodOn ? 'Conectado' : 'Não conectado' ?></div>
  </div>

  <div class="auto-box" style="max-width:520px">
    <label class="auto-accept">
      <input type="checkbox" <?= !empty($autoAcceptIfood)?'checked':'' ?> onchange="toggleAutoIfood(this.checked)"><span></span>
      <b>Aceitar automaticamente os pedidos do iFood</b>
    </label>
    <small>Desmarcado (recomendado): o pedido do iFood cai na coluna <b>Novos</b> e só vai pra cozinha depois que você clicar em Aceitar.</small>
  </div>
  <script>
  async function toggleAutoIfood(v){await fetch('?r=admin_toggle_auto_accept',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'chave=ifood&enabled='+(v?1:0)})}
  </script>

  <?php if(!$ifoodOn): ?>
    <div class="note">Integração oficial do iFood ainda não configurada (preencha <b>ifood</b> no <code>config.php</code>).
      Enquanto isso, use o botão abaixo para simular um pedido e ver o fluxo funcionando.</div>
    <a class="simbtn" href="?r=admin_sim_ifood">+ Simular pedido iFood</a>
  <?php endif; ?>

  <?php
    $col=['novo'=>[], 'em_preparo'=>[], 'saiu_entrega'=>[], 'concluido'=>[]];
    foreach($orders as $o){ $k=in_array($o['status'],['entregue','concluido'])?'concluido':$o['status']; $col[$k][]=$o; }
    $labels=['novo'=>'🔴 Novos (aceitar)','em_preparo'=>'🟠 Em preparo','saiu_entrega'=>'🟣 Pronto / coleta','concluido'=>'🟢 Concluído'];
    $next=['novo'=>'em_preparo','em_preparo'=>'saiu_entrega','saiu_entrega'=>'concluido'];
    $nextlbl=['novo'=>'Aceitar','em_preparo'=>'Marcar pronto','saiu_entrega'=>'Concluir'];
  ?>
  <div class="kan" style="margin-top:14px">
    <?php foreach($labels as $key=>$label): ?>
      <div class="kcol">
        <div class="kh"><span><?= $label ?></span><span class="c"><?= count($col[$key]) ?></span></div>
        <?php foreach($col[$key] as $o): ?>
          <div class="tk <?= e($o['status']) ?>">
            <div class="id"><?= e($o['codigo']) ?> <span style="color:#727884">entrega</span></div>
            <div class="nm"><?= e($o['cliente_nome']) ?></div>
            <div class="it"><?= e($o['itens'] ?? '') ?></div>
            <div class="ft"><span class="chp ifood">iFOOD</span><span class="val"><?= money($o['total']) ?></span></div>
            <?php if(isset($next[$o['status']])): ?>
              <button class="adv" onclick="setStatus(<?= $o['id'] ?>,'<?= $next[$o['status']] ?>')"><?= $nextlbl[$o['status']] ?> →</button>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<script>
async function setStatus(id,status){
  await fetch('?r=admin_set_status',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+id+'&status='+status});
  location.reload();
}
</script>
