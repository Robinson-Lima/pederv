<div class="wrap dash">
  <h2><?= e(cfg('restaurante')) ?></h2>
  <?= admin_subnav('admin_couriers') ?>

  <?php $motoOff=setting_get('motoboy_app_off','0')==='1'; ?>
  <form method="post" action="?r=admin_delivery_mode" id="delivmode" style="margin:6px 0 12px;padding:12px 14px;background:#fff;border:1px solid var(--line);border-radius:12px">
    <label style="display:flex;align-items:center;gap:12px;cursor:pointer;margin:0">
      <input type="hidden" name="motoboy_app_off" value="0">
      <input type="checkbox" name="motoboy_app_off" value="1" <?= $motoOff?'checked':'' ?> onchange="this.form.submit()" style="width:22px;height:22px;flex-shrink:0">
      <span><b>Desativar app do motoboy — operar entregas manualmente</b><br><small style="color:var(--muted)">Você despacha e clica em <b>“✓ Marcar entregue”</b> aqui no painel, sem precisar do app do motoboy.</small></span>
    </label>
  </form>

  <?php $pendTotal=0; foreach($pendAcerto as $p)$pendTotal+=$p['total']; ?>
  <div class="stats" style="grid-template-columns:repeat(4,1fr)">
    <div class="stat"><div class="l">Em rota</div><div class="v"><?= count($emrota) ?></div></div>
    <div class="stat"><div class="l">A despachar</div><div class="v"><?= count($aguardando) ?></div></div>
    <div class="stat"><div class="l">Entregues hoje</div><div class="v"><?= (int)$entregues ?></div></div>
    <div class="stat <?= $pendAcerto?'acerto-stat':'' ?>"><div class="l">🔴 A acertar no caixa</div><div class="v"><?= count($pendAcerto) ?></div><small><?= $pendAcerto?money($pendTotal).' com os motoboys':'tudo em dia ✔' ?></small></div>
  </div>

  <?php if($pendAcerto): ?>
  <div class="acerto-panel">
    <div class="acerto-panel-head">
      <div><b>💰 Acerto com o caixa</b><small>Entregas concluídas em que o motoboy ainda não entregou o dinheiro ou o comprovante da maquininha. Confirme para marcar como PAGO e lançar no caixa.</small></div>
    </div>
    <?php foreach($pendAcerto as $o): ?>
      <div class="acerto-row">
        <div class="oid"><?= e($o['codigo']) ?></div>
        <div class="w">
          <div class="nm"><?= e($o['cliente_nome']) ?> · <b><?= money($o['total']) ?></b></div>
          <div class="mt">🛵 <?= e($o['cnome']?:'sem motoboy') ?> · <?= e(payment_label($o['pagamento_metodo'])) ?> · entregue em <?= e(substr($o['atualizado_em']??'',0,16)) ?></div>
        </div>
        <span class="acerto-tipo <?= $o['pagamento_metodo']==='dinheiro'?'din':'maq' ?>"><?= $o['pagamento_metodo']==='dinheiro'?'💵 Dinheiro no caixa':'🧾 Comprovante da maquininha' ?></span>
        <form method="post" action="?r=admin_acerto_pago" onsubmit="return confirm('Confirmar acerto do pedido <?= e($o['codigo']) ?>?')">
          <input type="hidden" name="id" value="<?= $o['id'] ?>">
          <button class="acerto-ok-btn">✔ Confirmar acerto</button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="acerto-panel extrato-panel">
    <div class="acerto-panel-head"><div><b>📒 Extrato do dia por motoboy</b><small>Entregas de hoje, valores transportados e pendências com o caixa (inclui dias anteriores não acertados).</small></div></div>
    <?php if(!$extrato): ?><p style="color:#727884;font-size:13px;margin:8px 0 0">Nenhum motoboy cadastrado.</p><?php endif; ?>
    <table class="extrato-table">
      <?php if($extrato): ?><tr><th>Motoboy</th><th>Entregas hoje</th><th>💵 Dinheiro</th><th>🧾 Maquininha</th><th>Total do dia</th><th>Pendência com o caixa</th><th></th></tr><?php endif; ?>
      <?php foreach($extrato as $x): $pd=$pendPorMoto[(int)$x['id']]??null; ?>
        <tr class="<?= $pd?'tem-pend':'' ?>">
          <td><b><?= e($x['nome']) ?></b> <span class="dot <?= $x['online']?'on':'' ?>">●</span></td>
          <td><?= (int)$x['entregas'] ?></td>
          <td><?= money($x['dinheiro']) ?></td>
          <td><?= money($x['maquina']) ?></td>
          <td><b><?= money($x['valor']) ?></b></td>
          <td><?php if($pd): ?><span class="pend-pill">⚠ <?= (int)$pd['q'] ?> pedido(s) · <?= money($pd['v']) ?></span><?php else: ?><span class="ok-pill">✔ em dia</span><?php endif; ?></td>
          <td><?php if($pd): ?>
            <form method="post" action="?r=admin_acerto_pago" onsubmit="return confirm('Acertar TODAS as pendências de <?= e(addslashes($x['nome'])) ?> (<?= money($pd['v']) ?>)?')">
              <input type="hidden" name="courier_id" value="<?= $x['id'] ?>">
              <button class="acerto-ok-btn small">Acertar tudo</button>
            </form>
          <?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <div class="push-panel <?= (!empty($pushSupported)&&!empty($vapidOk))?'ok':'warn' ?>">
    <div>
      <b>🔔 Notificação push do motoboy</b>
      <?php if(empty($pushSupported)): ?>
        <small>Indisponível neste servidor: <?= e($pushMsg) ?> Enquanto isso, o aviso pelo WhatsApp continua funcionando normalmente.</small>
      <?php elseif(empty($vapidOk)): ?>
        <small>Recurso novo — gere a chave de segurança uma única vez para ativar.</small>
      <?php else: ?>
        <small><?= (int)$pushCount ?> motoboy(s) com alerta ativado neste(s) aparelho(s). Cada motoboy ativa no próprio app, tocando em "Ativar alertas".</small>
      <?php endif; ?>
    </div>
    <?php if(!empty($pushSupported)&&empty($vapidOk)): ?><button type="button" class="push-btn" onclick="gerarVapid()">Gerar chave agora</button><?php endif; ?>
  </div>
  <script>
  async function gerarVapid(){
    const btn=event.target;btn.disabled=true;btn.textContent='Gerando…';
    const r=await fetch('?r=vapid_generate',{method:'POST'}).then(x=>x.json()).catch(()=>({ok:false}));
    if(r.ok){location.reload();return}
    alert(r.erro||'Não foi possível gerar as chaves.');btn.disabled=false;btn.textContent='Gerar chave agora';
  }
  </script>

  <div class="mtgrid">
    <div>
      <div class="paysec">🛵 Despachar pedido</div>
      <?php if(!$aguardando): ?>
        <p style="color:#727884;font-size:13px">Nenhum pedido de entrega aguardando.</p>
      <?php endif; ?>
      <?php foreach($aguardando as $o): ?>
        <form class="dispatch" method="post" action="?r=admin_assign_courier">
          <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
          <div class="di">
            <div class="oid"><?= e($o['codigo']) ?></div>
            <div class="w"><div class="nm"><?= e($o['cliente_nome']) ?></div>
              <div class="mt"><?= e($o['endereco'] ?: 'sem endereço') ?> · <?= money($o['total']) ?></div></div>
          </div>
          <div class="dact">
            <select name="courier_id" required>
              <option value="">Escolher motoboy…</option>
              <?php foreach($couriers as $c): ?>
                <option value="<?= $c['id'] ?>"><?= e($c['nome']) ?><?= $c['status']==='em_rota'?' (em rota)':'' ?></option>
              <?php endforeach; ?>
            </select>
            <button class="disp">Despachar →</button>
          </div>
        </form>
      <?php endforeach; ?>
    </div>

    <div>
      <div class="paysec">📍 Em rota agora</div>
      <?php if(!$emrota): ?>
        <p style="color:#727884;font-size:13px">Nenhuma entrega em rota.</p>
      <?php endif; ?>
      <?php foreach($emrota as $o): ?>
        <div class="pcol">
          <div class="oid"><?= e($o['codigo']) ?></div>
          <div class="w"><div class="nm"><?= e($o['cliente_nome']) ?></div>
            <div class="mt">🛵 <?= e($o['cnome'] ?: 'sem motoboy') ?> · <?= e($o['endereco'] ?: '') ?></div></div>
          <div class="amt" style="color:#8B78FF">a caminho</div>
        </div>
      <?php endforeach; ?>

      <div class="paysec" style="margin-top:18px">👥 Motoboys cadastrados</div>
      <form method="post" action="?r=courier_salvar" class="cxlanc" style="margin-bottom:10px">
        <div class="fisc2">
          <input name="nome" placeholder="Nome do motoboy" required>
          <input name="telefone" placeholder="Telefone / WhatsApp">
        </div>
        <button class="cxgo or">+ Cadastrar motoboy</button>
      </form>
      <?php foreach($couriers as $c): ?>
        <div class="pcol">
          <div class="w"><div class="nm"><?= e($c['nome']) ?></div>
            <div class="mt"><?= e($c['telefone'] ?: 'sem telefone') ?> · <?= $c['status']==='em_rota'?'Em rota':'Disponível' ?></div></div>
          <div class="amt" style="color:<?= $c['status']==='em_rota'?'#8B78FF':'#22A06B' ?>">
            ● <?= $c['status']==='em_rota'?'ocupado':'livre' ?>
            <form method="post" action="?r=courier_excluir" style="display:inline" onsubmit="return confirm('Remover <?= e(addslashes($c['nome'])) ?>?')">
              <input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="del" style="margin-left:8px">×</button></form></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<script>
// Recarrega para acompanhar as rotas, mas não interrompe quem está digitando
// ou confirmando um acerto (input focado ou envio de formulário em andamento).
let rvSubmitting=false;document.querySelectorAll('form').forEach(f=>f.addEventListener('submit',()=>rvSubmitting=true));
setInterval(()=>{const a=document.activeElement;if(rvSubmitting||(a&&['INPUT','SELECT','TEXTAREA'].includes(a.tagName)))return;location.reload()},10000);
</script>
