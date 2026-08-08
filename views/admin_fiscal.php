<?php
$cfg = fiscal_config();
$tab = $_GET['tab'] ?? 'empresa';
$certFile = __DIR__.'/../data/certificado.pfx';
$certOk = is_file($certFile);
$certInfo = null;
if($certOk && $cfg['cert_senha']!==''){
  $pfx = file_get_contents($certFile); $certs=[];
  if(openssl_pkcs12_read($pfx,$certs,$cfg['cert_senha'])){
    $d=openssl_x509_parse($certs['cert']);
    $certInfo=['cn'=>$d['subject']['CN']??'','valido_ate'=>date('d/m/Y',$d['validTo_time_t']??0),'expirado'=>time()>($d['validTo_time_t']??0)];
  }
}
$pronto = $certOk && $cfg['cnpj']!=='' && $cfg['csc']!=='' && $cfg['ie']!=='';
?>
<div class="wrap">
  <div class="page-title-row"><div><h2>Configuração Fiscal (NFC-e)</h2><p>Configure os dados para emissão de nota fiscal direto na SEFAZ — sem custo por nota.</p></div>
    <span style="padding:6px 16px;border-radius:20px;font-size:12px;font-weight:700;background:<?= $pronto?'#E5F7EF':'#FDE7E7' ?>;color:<?= $pronto?'#0c6b4a':'#b71c1c' ?>"><?= $pronto?'Pronta para emitir':'Configuração pendente' ?></span>
  </div>

  <?php if(!empty($_GET['salvo'])): ?>
    <div style="background:#E5F7EF;color:#0c6b4a;padding:10px 16px;border-radius:8px;margin-bottom:16px;font-size:13px">Configurações salvas com sucesso.</div>
  <?php endif; ?>
  <?php if(!empty($_GET['erro'])): ?>
    <div style="background:#FDE7E7;color:#b71c1c;padding:10px 16px;border-radius:8px;margin-bottom:16px;font-size:13px"><?= e($_GET['erro']) ?></div>
  <?php endif; ?>
  <?php if(!empty($_GET['cert_ok'])): ?>
    <div style="background:#E5F7EF;color:#0c6b4a;padding:10px 16px;border-radius:8px;margin-bottom:16px;font-size:13px">Certificado instalado: <?= e($_GET['cert_ok']) ?></div>
  <?php endif; ?>

  <div class="fiscal-tabs">
    <a class="<?= $tab==='empresa'?'on':'' ?>" href="?r=admin_fiscal&tab=empresa">1. Dados da empresa</a>
    <a class="<?= $tab==='fiscal'?'on':'' ?>" href="?r=admin_fiscal&tab=fiscal">2. Certificado e SEFAZ</a>
    <a class="<?= $tab==='automacao'?'on':'' ?>" href="?r=admin_fiscal&tab=automacao">3. Automação</a>
  </div>

  <?php if($tab==='empresa'): ?>
  <form method="post" action="?r=admin_fiscal_save&tab=empresa">
  <div class="cfgcard">
    <h3>Dados da empresa</h3>
    <div class="fisc-grid3">
      <div class="field"><label>CNPJ *</label><input name="nf_cnpj" value="<?= e(setting_get('nf_cnpj')) ?>" placeholder="00.000.000/0000-00" inputmode="numeric"></div>
      <div class="field"><label>Razão Social *</label><input name="nf_razao" value="<?= e(setting_get('nf_razao',cfg('restaurante'))) ?>" placeholder="Razão social da empresa"></div>
      <div class="field"><label>Nome fantasia *</label><input name="nf_fantasia" value="<?= e(setting_get('nf_fantasia',cfg('restaurante'))) ?>" placeholder="Nome fantasia"></div>
    </div>
    <div class="fisc-grid3">
      <div class="field"><label>CEP *</label><input name="nf_cep" value="<?= e(setting_get('nf_cep')) ?>" placeholder="00000-000" inputmode="numeric"></div>
      <div class="field"><label>Rua *</label><input name="nf_rua" value="<?= e(setting_get('nf_rua')) ?>" placeholder="Logradouro"></div>
      <div class="field"><label>Número *</label><input name="nf_numero_end" value="<?= e(setting_get('nf_numero_end')) ?>" placeholder="Nº"></div>
    </div>
    <div class="fisc-grid3">
      <div class="field"><label>Bairro *</label><input name="nf_bairro" value="<?= e(setting_get('nf_bairro')) ?>" placeholder="Bairro"></div>
      <div class="field"><label>Cidade *</label><input name="nf_cidade" value="<?= e(setting_get('nf_cidade')) ?>" placeholder="Cidade"></div>
      <div class="field"><label>Estado *</label>
        <select name="nf_uf">
          <?php $uf=setting_get('nf_uf','SP'); foreach(['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'] as $u): ?>
          <option value="<?= $u ?>" <?= $uf===$u?'selected':'' ?>><?= $u ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="fisc-grid2">
      <div class="field"><label>Código do Município (IBGE) *</label><input name="nf_cmun" value="<?= e(setting_get('nf_cmun')) ?>" placeholder="Ex: 3550308" inputmode="numeric"></div>
      <div class="field"><label>Telefone</label><input name="nf_telefone" value="<?= e(setting_get('nf_telefone')) ?>" placeholder="(00) 00000-0000"></div>
    </div>
  </div>
  <div style="display:flex;gap:12px;margin-top:20px">
    <button type="submit" class="btn btn-primary">Salvar dados da empresa</button>
  </div>
  </form>

  <?php elseif($tab==='fiscal'): ?>

  <!-- Certificado A1 -->
  <div class="cfgcard">
    <h3>Certificado Digital A1</h3>
    <p>Faça upload do arquivo .pfx do certificado digital. O certificado é necessário para assinar e transmitir as notas ao SEFAZ.</p>
    <?php if($certInfo): ?>
      <div style="background:<?= $certInfo['expirado']?'#FDE7E7':'#E5F7EF' ?>;color:<?= $certInfo['expirado']?'#b71c1c':'#0c6b4a' ?>;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:13px">
        <b><?= $certInfo['expirado']?'Certificado EXPIRADO':'Certificado instalado' ?></b><br>
        <?= e($certInfo['cn']) ?> — válido até <?= $certInfo['valido_ate'] ?>
      </div>
    <?php elseif($certOk): ?>
      <div style="background:#FFF1DE;color:#9a6512;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:13px">
        Arquivo do certificado encontrado, mas não foi possível ler. Verifique a senha.
      </div>
    <?php else: ?>
      <div style="background:#FDE7E7;color:#b71c1c;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:13px">
        Nenhum certificado instalado. Faça upload abaixo.
      </div>
    <?php endif; ?>
    <form method="post" action="?r=cert_upload" enctype="multipart/form-data">
      <div class="fisc-grid2">
        <div class="field"><label>Arquivo (.pfx ou .p12)</label><input type="file" name="cert_file" accept=".pfx,.p12" style="padding:8px;background:#fff"></div>
        <div class="field"><label>Senha do certificado</label><input type="password" name="cert_senha" value="<?= e(setting_get('nf_cert_senha')) ?>" placeholder="Senha do .pfx"></div>
      </div>
      <button type="submit" class="btn" style="background:#3B82F6;color:#fff;margin-top:8px">Enviar certificado</button>
    </form>
    <div style="border-top:1px solid #eee;margin-top:16px;padding-top:16px">
      <p style="font-size:12px;color:#888;margin:0 0 8px">Não tem certificado ainda? Gere um de teste para simular o fluxo completo.</p>
      <a href="?r=cert_gerar_teste" class="btn" style="background:#F59E0B;color:#fff;font-size:13px;padding:8px 20px" onclick="return confirm('Isso gera um certificado fictício e ativa o modo simulação. As notas NÃO serão enviadas ao SEFAZ. Continuar?')">Gerar certificado de teste</a>
    </div>
  </div>

  <!-- Modo simulação -->
  <?php $simular=setting_get('nf_simular','0')==='1'; ?>
  <?php if($simular): ?>
  <div style="background:#FFF1DE;border:1px solid #F59E0B;color:#9a6512;padding:14px 20px;border-radius:10px;margin-bottom:20px;font-size:13px;display:flex;align-items:center;justify-content:space-between">
    <div><b>Modo simulação ativo</b> — as notas são geradas (XML + DANFE) mas NÃO enviadas ao SEFAZ. Para emitir notas reais, desative e use um certificado A1 válido.</div>
    <form method="post" action="?r=admin_fiscal_save&tab=fiscal" style="margin:0">
      <input type="hidden" name="nf_simular" value="0">
      <button type="submit" class="btn" style="background:#c8342b;color:#fff;font-size:12px;padding:6px 16px;white-space:nowrap">Desativar simulação</button>
    </form>
  </div>
  <?php endif; ?>

  <!-- Dados fiscais -->
  <form method="post" action="?r=admin_fiscal_save&tab=fiscal">
  <div class="cfgcard">
    <h3>Dados fiscais e SEFAZ</h3>
    <div class="fisc-grid2">
      <div class="field"><label>Inscrição Estadual (IE) *</label><input name="nf_ie" value="<?= e(setting_get('nf_ie')) ?>" placeholder="Inscrição Estadual"></div>
      <div class="field"><label>Regime Tributário (CRT) *</label>
        <select name="nf_regime">
          <?php $rg=setting_get('nf_regime','1'); ?>
          <option value="1" <?= $rg==='1'?'selected':'' ?>>Simples Nacional</option>
          <option value="2" <?= $rg==='2'?'selected':'' ?>>Simples Nacional (excesso sublimite)</option>
          <option value="3" <?= $rg==='3'?'selected':'' ?>>Regime Normal</option>
        </select>
      </div>
    </div>
    <div class="fisc-grid2">
      <div class="field"><label>CSC (Código de Segurança do Contribuinte) *</label><input name="nf_csc" type="password" value="<?= e(setting_get('nf_csc')) ?>" placeholder="Código CSC"><small>Obtido no portal do SEFAZ do seu estado.</small></div>
      <div class="field"><label>ID do CSC *</label><input name="nf_csc_id" value="<?= e(setting_get('nf_csc_id','1')) ?>" placeholder="1" inputmode="numeric"><small>Geralmente é 1 (homologação) ou 2 (produção).</small></div>
    </div>
    <div class="fisc-grid3">
      <div class="field"><label>Série da NFC-e</label><input name="nf_serie" value="<?= e(setting_get('nf_serie','1')) ?>" placeholder="1" inputmode="numeric"></div>
      <div class="field"><label>Próximo número</label><input name="nf_numero" value="<?= e(setting_get('nf_numero','0')) ?>" placeholder="0" inputmode="numeric"></div>
      <div class="field"><label>Ambiente *</label>
        <select name="nf_ambiente">
          <?php $am=setting_get('nf_ambiente','2'); ?>
          <option value="2" <?= $am==='2'?'selected':'' ?>>Homologação (teste)</option>
          <option value="1" <?= $am==='1'?'selected':'' ?>>Produção</option>
        </select>
      </div>
    </div>
    <div class="field"><label>Mensagem do rodapé da nota</label><input name="nf_rodape" value="<?= e(setting_get('nf_rodape','Obrigado pela preferência')) ?>" placeholder="Mensagem de rodapé" maxlength="140"><small>Máximo 140 caracteres. Aparece no final da nota impressa.</small></div>
  </div>
  <div style="display:flex;gap:12px;margin-top:20px">
    <a href="?r=admin_fiscal&tab=empresa" class="btn" style="background:#eee;color:#333">Voltar</a>
    <button type="submit" class="btn btn-primary">Salvar dados fiscais</button>
  </div>
  </form>

  <?php elseif($tab==='automacao'): ?>
  <form method="post" action="?r=admin_fiscal_save&tab=automacao">
  <div class="cfgcard">
    <h3>Automação da emissão</h3>
    <p>Configure quando as notas fiscais serão emitidas automaticamente.</p>

    <div class="fiscal-toggle">
      <label class="toggle-row">
        <input type="checkbox" name="nf_auto" value="1" <?= setting_get('nf_auto')==='1'?'checked':'' ?>>
        <div><b>Emissão automática</b><small>Emite NFC-e automaticamente ao finalizar cada venda no PDV e no cardápio.</small></div>
      </label>
    </div>
    <div class="fiscal-toggle">
      <label class="toggle-row">
        <input type="checkbox" name="nf_auto_print" value="1" <?= setting_get('nf_auto_print')==='1'?'checked':'' ?>>
        <div><b>Impressão automática do DANFE</b><small>Imprime o DANFE NFC-e automaticamente na impressora térmica após a emissão.</small></div>
      </label>
    </div>
    <div class="fiscal-toggle">
      <label class="toggle-row">
        <input type="checkbox" name="nf_pedir_cpf" value="1" <?= setting_get('nf_pedir_cpf')==='1'?'checked':'' ?>>
        <div><b>Solicitar CPF/CNPJ na nota</b><small>Pergunta o CPF/CNPJ do consumidor antes de emitir a nota fiscal.</small></div>
      </label>
    </div>
    <div class="fiscal-toggle">
      <label class="toggle-row">
        <input type="checkbox" name="nf_incluir_frete" value="1" <?= setting_get('nf_incluir_frete')==='1'?'checked':'' ?>>
        <div><b>Incluir taxa de entrega na nota</b><small>A taxa de entrega aparecerá como "Valor frete" na NFC-e.</small></div>
      </label>
    </div>
  </div>
  <div style="display:flex;gap:12px;margin-top:20px">
    <a href="?r=admin_fiscal&tab=fiscal" class="btn" style="background:#eee;color:#333">Voltar</a>
    <button type="submit" class="btn btn-primary">Salvar automação</button>
  </div>
  </form>
  <?php endif; ?>
</div>

<style>
.fiscal-tabs{display:flex;gap:0;border-bottom:2px solid #eee;margin-bottom:24px}
.fiscal-tabs a{padding:12px 24px;font-size:14px;font-weight:600;color:#727884;text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-2px;transition:.2s}
.fiscal-tabs a.on{color:var(--brand,#e53935);border-bottom-color:var(--brand,#e53935)}
.fiscal-tabs a:hover{color:#333}
.fisc-grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.fisc-grid3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}
@media(max-width:768px){.fisc-grid2,.fisc-grid3{grid-template-columns:1fr}}
.fiscal-toggle{border:1px solid #eee;border-radius:12px;padding:16px 20px;margin-bottom:12px}
.toggle-row{display:flex;align-items:flex-start;gap:14px;cursor:pointer}
.toggle-row input[type="checkbox"]{width:44px;height:24px;-webkit-appearance:none;appearance:none;background:#ccc;border-radius:12px;position:relative;cursor:pointer;flex-shrink:0;margin-top:2px;transition:.2s}
.toggle-row input[type="checkbox"]::after{content:'';position:absolute;width:20px;height:20px;background:#fff;border-radius:50%;top:2px;left:2px;transition:.2s}
.toggle-row input[type="checkbox"]:checked{background:var(--brand,#e53935)}
.toggle-row input[type="checkbox"]:checked::after{left:22px}
.toggle-row div b{display:block;font-size:14px;color:#222;margin-bottom:4px}
.toggle-row div small{font-size:12px;color:#888;line-height:1.4}
.cfgcard{background:#fff;border:1px solid #eee;border-radius:14px;padding:24px;margin-bottom:20px}
.cfgcard h3{font-size:16px;margin:0 0 4px;color:#222}
.cfgcard p{font-size:13px;color:#888;margin:0 0 20px}
.cfgcard .field{margin-bottom:14px}
.cfgcard .field label{display:block;font-size:12px;font-weight:600;color:#555;margin-bottom:5px}
.cfgcard .field input,.cfgcard .field select{width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:10px;font-size:14px;box-sizing:border-box;background:#fff;color:#222}
.cfgcard .field small{font-size:11px;color:#aaa;margin-top:4px;display:block}
.btn{padding:12px 32px;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block}
.btn-primary{background:var(--brand,#e53935);color:#fff}
</style>
