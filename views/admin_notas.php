<?php
$cfg = fiscal_config();
$certOk = is_file(__DIR__.'/../data/certificado.pfx') && $cfg['cert_senha']!=='' && $cfg['cnpj']!=='' && $cfg['csc']!=='';
$badge = function($s){
  $m=['autorizada'=>['#E5F7EF','#0c6b4a','Autorizada'],'rejeitada'=>['#FDE7E7','#b71c1c','Rejeitada'],
      'cancelada'=>['#EEE','#555','Cancelada'],'erro'=>['#FDE7E7','#b71c1c','Erro'],
      'pendente'=>['#FFF1DE','#9a6512','Pendente']];
  $c=$m[$s]??$m['pendente'];
  return '<span class="nbadge" style="background:'.$c[0].';color:'.$c[1].'">'.$c[2].'</span>';
};
?>
<div class="wrap dash">
  <h2><?= e(cfg('restaurante')) ?></h2>
  <?= admin_subnav('admin_notas') ?>

  <div class="ifhead" style="border-left-color:#3B82F6">
    <div class="lg" style="background:#3B82F6">NF</div>
    <div class="tt"><b>Notas Fiscais (NFC-e) — Direto SEFAZ</b>
      <div>Ambiente: <?= $cfg['ambiente']==='1'?'Produção':'Homologação' ?>
        · Emissão automática: <?= $cfg['auto']==='1'?'ligada':'desligada' ?></div></div>
    <div class="conn" style="color:<?= $certOk?'#22A06B':'#9a6512' ?>">
      ● <?= $certOk?'Configurada':'Configuração pendente' ?></div>
  </div>

  <?php if(setting_get('nf_simular','0')==='1'): ?>
    <div class="note" style="background:#FFF1DE;border-left:3px solid #F59E0B;color:#9a6512"><b>Modo simulação ativo</b> — notas são geradas localmente mas NÃO enviadas ao SEFAZ. <a href="?r=admin_fiscal&tab=fiscal">Configurar</a></div>
  <?php elseif(!$certOk): ?>
    <div class="note">Configure o certificado A1 e os dados fiscais em
      <a href="?r=admin_fiscal"><b>Config. fiscal</b></a> para emitir notas.</div>
  <?php endif; ?>

  <div class="two">
    <div>
      <div class="paysec">Pedidos pagos sem nota</div>
      <?php if(!$semNota): ?><p style="color:#727884;font-size:13px">Nenhum pedido pago pendente de nota.</p><?php endif; ?>
      <?php foreach($semNota as $o): ?>
        <div class="pcol">
          <div class="oid"><?= e($o['codigo']) ?></div>
          <div class="w"><div class="nm"><?= e($o['cliente_nome']) ?></div>
            <div class="mt"><?= e($o['pagamento_metodo']) ?> · <?= money($o['total']) ?></div></div>
          <form method="post" action="?r=nota_emitir" style="margin:0">
            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
            <button class="markpago">Emitir NFC-e</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>

    <div>
      <div class="paysec">Notas recentes</div>
      <?php if(!$notas): ?><p style="color:#727884;font-size:13px">Nenhuma nota emitida ainda.</p><?php endif; ?>
      <?php foreach($notas as $n): ?>
        <div class="pcol" style="align-items:flex-start;flex-direction:column;gap:6px">
          <div style="display:flex;align-items:center;gap:10px;width:100%">
            <div class="oid">NFC-e <?= (int)$n['numero'] ?>/<?= e($n['serie']) ?></div>
            <div class="w"><div class="nm"><?= e($n['codigo']) ?> · <?= e($n['cliente_nome']) ?></div>
              <div class="mt"><?= money($n['valor']) ?> · <?= e(substr($n['criado_em'],11,5)) ?></div></div>
            <?= $badge($n['status']) ?>
          </div>
          <?php if($n['chave']): ?>
            <div class="mono" style="font-size:10px;color:#727884;word-break:break-all"><?= e($n['chave']) ?></div>
          <?php endif; ?>
          <?php if($n['motivo'] && $n['status']!=='autorizada'): ?>
            <div style="font-size:11px;color:#9a6512"><?= e($n['motivo']) ?></div>
          <?php endif; ?>
          <div style="display:flex;gap:8px">
            <?php if($n['danfe_url']): ?><a class="markpago" style="background:#3B82F6;color:#fff" href="<?= e($n['danfe_url']) ?>" target="_blank">DANFE</a><?php endif; ?>
            <?php if($n['status']==='autorizada'): ?>
              <form method="post" action="?r=nota_cancelar" style="margin:0" onsubmit="return confirm('Cancelar esta nota?')">
                <input type="hidden" name="nota_id" value="<?= $n['id'] ?>">
                <input type="hidden" name="justificativa" value="Cancelamento solicitado pelo estabelecimento">
                <button class="markpago" style="background:#c8342b;color:#fff">Cancelar</button>
              </form>
            <?php elseif(in_array($n['status'],['pendente','erro','rejeitada'])): ?>
              <form method="post" action="?r=nota_emitir" style="margin:0">
                <input type="hidden" name="order_id" value="<?= $n['order_id'] ?>">
                <button class="markpago">Tentar de novo</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
