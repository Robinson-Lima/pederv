<div class="saas-head"><div><h1>Planos e ajustes</h1><p>Preços das assinaturas, período de teste e acesso ao painel.</p></div></div>
<?php if(!empty($salvo)): ?><div class="saas-alert ok">Ajustes salvos.</div><?php endif; ?>

<form method="post" action="?r=saas_settings" class="saas-form saas-panel">
  <div class="saas-panel-h"><b>Planos de assinatura</b></div>
  <div class="grid2">
    <label>Plano Pró — valor mensal (R$)<input name="preco_pro" value="<?= number_format(saas_plan_price('pro'),2,'.','') ?>"></label>
    <label>Plano Premium — valor mensal (R$)<input name="preco_premium" value="<?= number_format(saas_plan_price('premium'),2,'.','') ?>"></label>
  </div>
  <label>Dias de teste grátis (novos cadastros)<input name="trial_dias" type="number" min="1" max="60" value="<?= (int)setting_get('saas_trial_dias','7') ?>"></label>

  <div class="saas-panel-h" style="margin-top:14px"><b>uazapi — WhatsApp por QR Code (clientes)</b></div>
  <p style="color:#666;font-size:13px;margin:0 0 10px">Configure uma vez. Cada restaurante conectará o próprio número escaneando um QR Code, sem precisar de chave de API. <a href="https://docs.uazapi.com" target="_blank">Ver docs ↗</a></p>
  <div class="grid2">
    <label>URL da sua instância uazapi<input name="saas_uaz_url" value="<?= e(_uaz_cfg('saas_uaz_url')) ?>" placeholder="https://api.uazapi.com"></label>
    <label>API Key (master)<input name="saas_uaz_key" type="password" value="<?= e(_uaz_cfg('saas_uaz_key')) ?>" placeholder="sua-chave-aqui" autocomplete="off"></label>
  </div>
  <?php if(_uaz_cfg('saas_uaz_url')): ?><p style="color:#2e7d32;font-size:13px">✅ uazapi configurado — clientes verão a opção "QR Code" no painel deles.</p><?php endif; ?>

  <div class="saas-panel-h" style="margin-top:14px"><b>Importação de cardápio — ScraperAPI</b></div>
  <p style="color:#666;font-size:13px;margin:0 0 10px">Permite que clientes importem cardápios do Anota AI, Goomer e outros apenas colando um link. Crie uma conta grátis em <a href="https://www.scraperapi.com" target="_blank">scraperapi.com</a> e cole a chave abaixo (1.000 importações/mês grátis).</p>
  <label>Chave da ScraperAPI<input name="saas_scraper_key" type="password" value="<?= e(setting_get('saas_scraper_key','')) ?>" placeholder="Cole sua API key aqui" autocomplete="off"></label>
  <?php if(setting_get('saas_scraper_key','')): ?><p style="color:#2e7d32;font-size:13px">✅ ScraperAPI configurada — clientes podem importar cardápios pelo link.</p><?php endif; ?>

  <div class="saas-panel-h" style="margin-top:14px"><b>💳 Pagamento online (maquininha de cartão)</b></div>
  <p style="color:#666;font-size:13px;margin:0 0 10px">Cole aqui a chave da API da sua maquininha (PagSeguro, Mercado Pago, Cielo, Stone, etc.) para aceitar cartão online nas assinaturas do PedeRV.</p>
  <div class="grid2">
    <label>Chave da API de pagamentos<input name="saas_payment_api_key" type="password" value="<?= e(setting_get('saas_payment_api_key','')) ?>" placeholder="Cole a API Key do seu gateway" autocomplete="off"></label>
    <label>Provedor (ex: mercadopago, pagseguro, cielo)<input name="saas_payment_provider" value="<?= e(setting_get('saas_payment_provider','')) ?>" placeholder="mercadopago"></label>
  </div>
  <?php if(setting_get('saas_payment_api_key','')): ?><p style="color:#2e7d32;font-size:13px">✅ Gateway de pagamento configurado — clientes poderão pagar com cartão online.</p><?php endif; ?>

  <div class="saas-panel-h" style="margin-top:14px"><b>PIX para assinaturas</b></div>
  <p style="color:#666;font-size:13px;margin:0 0 10px">Chave PIX exibida no botão "Assinar" do site. Clientes verão esta chave para pagar via PIX.</p>
  <div class="grid2">
    <label>Chave PIX<input name="saas_pix_key" value="<?= e(setting_get('saas_pix_key','')) ?>" placeholder="Chave PIX (CPF, CNPJ, e-mail ou aleatória)"></label>
    <label>Nome exibido no PIX<input name="saas_pix_nome" value="<?= e(setting_get('saas_pix_nome','PedeRV')) ?>" placeholder="PedeRV"></label>
  </div>

  <div class="saas-panel-h" style="margin-top:14px"><b>Segurança</b></div>
  <label>Nova senha do painel (deixe vazio para manter)<input name="nova_senha" type="password" placeholder="••••••••"></label>

  <p class="saas-note">Regra de cobrança: teste grátis por <?= (int)setting_get('saas_trial_dias','7') ?> dias → assinatura mensal → bloqueio automático 15 dias após o vencimento não pago. Desbloqueio manual disponível na ficha do cliente.</p>
  <button class="saas-btn primary">Salvar ajustes</button>
</form>
