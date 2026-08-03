<div class="saas-head"><div><h1>Planos e ajustes</h1><p>Preços das assinaturas, período de teste e acesso ao painel.</p></div></div>
<?php if(!empty($salvo)): ?><div class="saas-alert ok">Ajustes salvos.</div><?php endif; ?>

<form method="post" action="?r=saas_settings" class="saas-form saas-panel">
  <?= saas_csrf_field() ?>
  <div class="saas-panel-h"><b>Planos de assinatura</b></div>
  <div class="grid2">
    <label>Plano Pró — valor mensal (R$)<input name="preco_pro" value="<?= number_format(saas_plan_price('pro'),2,'.','') ?>"></label>
    <label>Plano Premium — valor mensal (R$)<input name="preco_premium" value="<?= number_format(saas_plan_price('premium'),2,'.','') ?>"></label>
    <label>Plano Pró Anual — valor total/ano (R$)<input name="preco_pro_anual" value="<?= number_format(saas_plan_price('pro_anual'),2,'.','') ?>"></label>
    <label>Plano Premium Anual — valor total/ano (R$)<input name="preco_premium_anual" value="<?= number_format(saas_plan_price('premium_anual'),2,'.','') ?>"></label>
  </div>
  <label>Dias de teste grátis (novos cadastros)<input name="trial_dias" type="number" min="1" max="60" value="<?= (int)setting_get('saas_trial_dias','7') ?>"></label>

  <div class="saas-panel-h" style="margin-top:14px"><b>uazapi — WhatsApp por QR Code (clientes)</b></div>
  <p style="color:#666;font-size:13px;margin:0 0 10px">Configure uma vez. Cada restaurante conectará o próprio número escaneando um QR Code, sem precisar de chave de API. <a href="https://docs.uazapi.com" target="_blank">Ver docs ↗</a></p>
  <div class="grid2">
    <label>URL da sua instância uazapi<input name="saas_uaz_url" value="<?= e(_uaz_cfg('saas_uaz_url')) ?>" placeholder="https://api.uazapi.com"></label>
    <label>API Key (master)<input name="saas_uaz_key" type="password" value="<?= _uaz_cfg('saas_uaz_key')!==''?'••••••••':'' ?>" placeholder="sua-chave-aqui" autocomplete="off"></label>
  </div>
  <div class="grid2" style="margin-top:8px">
    <label>Nome da instância do robô SaaS (seu número interno)<input name="saas_uaz_instance" value="<?= e(_uaz_cfg('saas_uaz_instance')?:'' ) ?>" placeholder="pederv-sales"><small style="font-size:11px;color:#888">Nome exato da instância no uazapi que será usada como robô interno do PedeRV. Ex: pederv-sales</small></label>
  </div>
  <?php if(_uaz_cfg('saas_uaz_url')): ?><p style="color:#2e7d32;font-size:13px">✅ uazapi configurado — clientes verão a opção "QR Code" no painel deles.</p><?php endif; ?>

  <div class="saas-panel-h" style="margin-top:14px"><b>Importação de cardápio — ScraperAPI</b></div>
  <p style="color:#666;font-size:13px;margin:0 0 10px">Permite que clientes importem cardápios do Anota AI, Goomer e outros apenas colando um link. Crie uma conta grátis em <a href="https://www.scraperapi.com" target="_blank">scraperapi.com</a> e cole a chave abaixo (1.000 importações/mês grátis).</p>
  <label>Chave da ScraperAPI<input name="saas_scraper_key" type="password" value="<?= setting_get('saas_scraper_key','')!==''?'••••••••':'' ?>" placeholder="Cole sua API key aqui" autocomplete="off"></label>
  <?php if(setting_get('saas_scraper_key','')): ?><p style="color:#2e7d32;font-size:13px">✅ ScraperAPI configurada — clientes podem importar cardápios pelo link.</p><?php endif; ?>

  <div class="saas-panel-h" style="margin-top:14px"><b>💳 Pagamento online — InfinitePay</b></div>
  <p style="color:#666;font-size:13px;margin:0 0 10px">Configure sua InfiniteTag para receber pagamentos de assinaturas por cartão. O cliente é redirecionado para a página segura da InfinitePay.</p>
  <div class="grid2">
    <label>Sua InfiniteTag (sem o $)<input name="saas_infinitepay_handle" value="<?= e(setting_get('saas_infinitepay_handle','')) ?>" placeholder="ex: pederv" autocomplete="off"></label>
    <label>Provedor<input value="InfinitePay" disabled style="background:#f5f5f5"></label>
  </div>
  <?php if(setting_get('saas_infinitepay_handle','')): ?><p style="color:#2e7d32;font-size:13px">✅ InfinitePay configurado — tag: <b>$<?= e(setting_get('saas_infinitepay_handle','')) ?></b></p><?php else: ?><p style="color:#b45309;font-size:13px">⚠️ Preencha a tag para ativar pagamentos por cartão nas assinaturas.</p><?php endif; ?>

  <div class="saas-panel-h" style="margin-top:14px"><b>PIX para assinaturas</b></div>
  <p style="color:#666;font-size:13px;margin:0 0 10px">Chave PIX exibida no botão "Assinar" do site. Clientes verão esta chave para pagar via PIX.</p>
  <div class="grid2">
    <label>Chave PIX<input name="saas_pix_key" value="<?= e(setting_get('saas_pix_key','')) ?>" placeholder="Chave PIX (CPF, CNPJ, e-mail ou aleatória)"></label>
    <label>Nome exibido no PIX<input name="saas_pix_nome" value="<?= e(setting_get('saas_pix_nome','PedeRV')) ?>" placeholder="PedeRV"></label>
  </div>

  <div class="saas-panel-h" style="margin-top:14px"><b>Segurança</b></div>
  <label>Nova senha do painel (deixe vazio para manter)<input name="nova_senha" type="password" placeholder="••••••••"></label>

  <div class="saas-panel-h" style="margin-top:14px"><b>⏰ Cron de inadimplência</b></div>
  <p style="color:#666;font-size:13px;margin:0 0 10px">Configure no cPanel do HostGator uma tarefa agendada para chamar a URL abaixo a cada hora. Isso bloqueia inadimplentes e envia avisos de trial automaticamente, sem depender de alguém abrir o painel.</p>
  <?php $cronTok=setting_get('saas_cron_token',''); ?>
  <?php if($cronTok): ?>
    <p style="font-size:12px;background:#f0f8ff;border:1px solid #c8dcf0;border-radius:8px;padding:10px;word-break:break-all;margin:0 0 8px">
      <b>URL do cron:</b><br>
      <code><?= (isset($_SERVER['HTTPS'])?'https':'http').'://'.($_SERVER['HTTP_HOST']??'pederv.com.br') ?>/?r=saas_cron&token=<?= e($cronTok) ?></code>
    </p>
  <?php else: ?>
    <p style="color:#b45309;font-size:13px">Nenhum token gerado ainda. Clique em "Gerar token do cron" abaixo para ativar.</p>
  <?php endif; ?>
  <label style="display:flex;align-items:center;gap:10px;margin:8px 0 0">
    <input type="checkbox" name="gerar_cron_token" value="1"> <span style="font-size:13px"><?= $cronTok?'Regenerar token (invalidará a URL antiga)':'Gerar token do cron' ?></span>
  </label>

  <p class="saas-note">Regra de cobrança: teste grátis por <?= (int)setting_get('saas_trial_dias','7') ?> dias → assinatura mensal → bloqueio automático 15 dias após o vencimento não pago. Desbloqueio manual disponível na ficha do cliente.</p>
  <button class="saas-btn primary">Salvar ajustes</button>
</form>
