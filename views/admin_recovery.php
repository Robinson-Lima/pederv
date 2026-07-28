<?php $active=setting_get('recovery_active','0')==='1'; ?>
<div class="wrap settings-page">
  <div class="page-title-row"><div><h2>Recuperador de vendas</h2><p>Identifique compras esquecidas e envie um lembrete automático pelo WhatsApp.</p></div></div>
  <?php if(isset($_GET['salvo'])): ?><div class="oknote">Configuração do recuperador salva.</div><?php endif; ?>
  <section class="recovery-hero">
    <div><span class="recovery-icon">↻</span><h3>Recuperador automático</h3><p>Quando a Evolution estiver conectada, o sistema envia o link do carrinho para clientes que informaram telefone e não concluíram a compra.</p></div>
    <span class="integration-pill <?= evolution_configured()?'ok':'off' ?>"><?= evolution_configured()?'Evolution conectada':'Aguardando Evolution' ?></span>
  </section>
  <div class="recovery-grid">
    <section class="settings-panel"><form method="post">
      <label class="setting-row"><span><b>Ativar recuperador de vendas</b><small>Os lembretes só serão enviados depois que a Evolution estiver configurada.</small></span><span class="switch-ui"><input type="hidden" name="s[recovery_active]" value="0"><input type="checkbox" name="s[recovery_active]" value="1" <?= $active?'checked':'' ?>><i></i></span></label>
      <label class="setting-field">Enviar depois de quantos minutos?<input type="number" min="5" max="1440" name="s[recovery_delay]" value="<?= e(setting_get('recovery_delay','30')) ?>"></label>
      <label class="setting-field">Mensagem automática<textarea name="s[recovery_message]" rows="6"><?= e(setting_get('recovery_message','Olá {NOME}! Você deixou itens no carrinho. Finalize seu pedido aqui: {LINK_CARDAPIO}')) ?></textarea><small>Use {NOME} e {LINK_CARDAPIO} para personalizar.</small></label>
      <button class="save settings-save">Salvar configuração</button>
    </form></section>
    <section class="recovery-list"><div class="recovery-list-head"><div><h3>Carrinhos recentes</h3><p><?= count($carts) ?> registro(s) encontrado(s).</p></div></div>
      <?php if(!$carts): ?><div class="empty-config">Nenhum carrinho abandonado até agora.</div><?php endif; ?>
      <?php foreach($carts as $c): $status=$c['convertido_order_id']?'convertido':($c['aviso_enviado_em']?'notificado':'aberto'); ?>
        <article class="recovery-item"><div><b><?= e($c['nome']?:$c['email']?:'Cliente não identificado') ?></b><small><?= e($c['telefone']?:$c['email']?:'Contato ainda não informado') ?></small></div><strong><?= money($c['total']) ?></strong><span class="recovery-status <?= $status ?>"><?= $status==='convertido'?'Pedido concluído':($status==='notificado'?'Lembrete enviado':'Aguardando') ?></span></article>
      <?php endforeach; ?>
    </section>
  </div>
</div>
