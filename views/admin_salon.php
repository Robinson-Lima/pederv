<?php
$tabs=['meu_salao'=>'Meu salão','garcons'=>'Meus garçons','app_garcom'=>'App Garçom','comandas'=>'Comandas','pdv'=>'Pedido Balcão (PDV)','taxa'=>'Taxa de serviço','impressora'=>'Impressora','balanca'=>'Balança'];
$ck=function($key,$def='0'){return setting_get($key,$def)==='1'?'checked':'';};
$toggle=function($key,$label,$help,$def='0')use($ck){ ?><label class="setting-row"><span><b><?= e($label) ?></b><small><?= e($help) ?></small></span><span class="switch-ui"><input type="hidden" name="s[<?= e($key) ?>]" value="0"><input type="checkbox" name="s[<?= e($key) ?>]" value="1" <?= $ck($key,$def) ?>><i></i></span></label><?php };
?>
<div class="wrap settings-page">
  <div class="page-title-row"><div><h2>Configuração do salão</h2><p>Cada assunto fica separado para a operação continuar simples e organizada.</p></div></div>
  <?php if(isset($_GET['salvo'])): ?><div class="oknote">Configurações do salão salvas.</div><?php endif; ?>
  <div class="settings-shell">
    <nav class="settings-nav"><?php foreach($tabs as $key=>$label): ?><a class="<?= $sec===$key?'on':'' ?>" href="?r=admin_salon&sec=<?= $key ?>"><span><?= e($label) ?><small>Clique para configurar</small></span></a><?php endforeach; ?></nav>
    <section class="settings-panel"><form method="post" action="?r=admin_salon&sec=<?= e($sec) ?>">
      <?php if($sec==='meu_salao'): ?>
        <h3>Dados do salão</h3><p>Defina a estrutura e o modelo principal de atendimento.</p>
        <?php $toggle('salon_ativo','Atendimento no salão','Habilita mesas, comandas e pedidos para consumo local.','1'); ?>
        <div class="settings-grid"><label>Quantidade de mesas<input type="number" min="0" name="s[salon_mesas]" value="<?= e(setting_get('salon_mesas','9')) ?>"></label><label>Modelo principal<select name="s[salon_modelo]"><?php foreach(['À la carte','Buffet / self-service','Rodízio'] as $v): ?><option <?= setting_get('salon_modelo','À la carte')===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select></label></div>
        <label class="setting-field">Formas de atendimento<input name="s[salon_service_modes]" value="<?= e(setting_get('salon_service_modes','Mesa, balcão e autoatendimento')) ?>"><small>Ex.: mesa, comanda individual, balcão, retirada ou autoatendimento.</small></label>
      <?php elseif($sec==='garcons'): ?>
        <h3>Meus garçons</h3><p>Cada garçom usa seu próprio usuário e senha.</p>
        <?php if(!$waiters): ?><div class="empty-config">Nenhum garçom cadastrado.</div><?php endif; ?>
        <?php foreach($waiters as $w): ?><div class="staff-setting"><span><?= strtoupper(substr($w['nome'],0,1)) ?></span><div><b><?= e($w['nome']) ?></b><small>@<?= e($w['usuario']) ?> · <?= e($w['telefone']) ?> · <?= $w['ativo']?'Ativo':'Inativo' ?></small></div></div><?php endforeach; ?>
        <a class="save inline-link" href="?r=admin_settings#usuarios">Cadastrar ou editar garçons</a>
      <?php elseif($sec==='app_garcom'): ?>
        <h3>Aplicativo do Garçom</h3><p>Controle exatamente o que o garçom pode fazer.</p>
        <?php $toggle('waiter_edit','Permitir editar pedidos','O garçom pode corrigir os itens antes do fechamento.','1'); ?>
        <?php $toggle('waiter_cancel','Permitir cancelar pedidos','O cancelamento continuará registrado no histórico.','0'); ?>
        <?php $toggle('waiter_status','Exibir status da cozinha','Mostra Pendente, Em preparo e Pronto.','1'); ?>
        <?php $toggle('waiter_open_empty','Abrir comanda sem pedido','Permite ocupar a mesa antes de lançar itens.','0'); ?>
        <?php $toggle('waiter_auto_print','Impressão automática','Imprime ao gerar o pedido do garçom.','0'); ?>
      <?php elseif($sec==='comandas'): ?>
        <h3>Configurações de comandas</h3><p>Personalize como as comandas são abertas e identificadas.</p>
        <?php $toggle('comanda_mesa','Vincular comanda a uma mesa','Mantém número exclusivo de comanda a cada abertura.','1'); ?>
        <?php $toggle('comanda_cpf','Solicitar CPF do cliente','Adiciona um campo opcional na abertura.','0'); ?>
        <?php $toggle('comanda_open_empty','Abrir comanda sem pedido','Útil para comandas abertas na entrada do restaurante.','0'); ?>
      <?php elseif($sec==='pdv'): ?>
        <h3>Pedido Balcão (PDV)</h3><p>Configure a aparência e o comportamento do caixa.</p>
        <?php $toggle('pdv_categories','Exibir menu de categorias','Facilita a navegação pelos produtos.','1'); ?>
        <?php $toggle('pdv_hide_soldout','Ocultar itens esgotados','Mostra apenas produtos disponíveis.','1'); ?>
        <?php $toggle('pdv_fullscreen','Abrir PDV em tela cheia','Aumenta o espaço de trabalho do operador.','0'); ?>
        <label class="setting-field">Exibição dos produtos<select name="s[pdv_layout]"><option value="cards" <?= setting_get('pdv_layout','cards')==='cards'?'selected':'' ?>>Itens em cartões</option><option value="list" <?= setting_get('pdv_layout')==='list'?'selected':'' ?>>Itens em lista</option></select></label>
      <?php elseif($sec==='taxa'): ?>
        <h3>Taxa de serviço</h3><p>Configure a cobrança para mesas e comandas.</p>
        <?php $toggle('salon_service_active','Permitir taxa de serviço','A taxa aparece no fechamento da conta.','1'); ?>
        <label class="setting-field">Porcentagem da taxa<input type="number" min="0" max="20" step="0.1" name="s[salon_service_rate]" value="<?= e(setting_get('salon_service_rate','10')) ?>"><small>O cliente deve poder conferir e remover a taxa quando aplicável.</small></label>
      <?php elseif($sec==='impressora'): ?>
        <h3>Impressora</h3><p>Configure a impressão de pedidos e comandas.</p>
        <div class="settings-grid"><label>Nome da impressora<input name="s[printer_name]" value="<?= e(setting_get('printer_name','Impressora térmica')) ?>"></label><label>Largura<select name="s[printer_width]"><option value="80" <?= setting_get('printer_width','80')==='80'?'selected':'' ?>>80 mm</option><option value="58" <?= setting_get('printer_width')==='58'?'selected':'' ?>>58 mm</option></select></label></div>
        <?php $toggle('printer_auto','Impressão automática','Imprime ao aceitar um pedido.','0'); ?>
        <?php $toggle('printer_include_table','Incluir mesa e comanda','Destaca a origem de pedidos locais.','1'); ?>
        <?php $toggle('printer_group_items','Agrupar itens iguais','Economiza papel na impressão.','1'); ?>
      <?php else: ?>
        <h3>Configuração da balança</h3><p>Selecione o equipamento usado para produtos vendidos por peso.</p>
        <?php $toggle('scale_active','Ativar balança','Habilita campos de peso no PDV.','0'); ?>
        <label class="setting-field">Modelo<select name="s[scale_model]"><?php foreach(['Toledo Prix 3 Fit','Urano Pop S','Elgin DP','Outro'] as $v): ?><option <?= setting_get('scale_model')===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select></label>
      <?php endif; ?>
      <?php if($sec!=='garcons'): ?><button class="save settings-save">Salvar alterações</button><?php endif; ?>
    </form></section>
  </div>
</div>
