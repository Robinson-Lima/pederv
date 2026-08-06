<?php
$ems = fiscal_emissores();
$cfg = fiscal_config();
$tab = $_GET['tab'] ?? 'empresa';
?>
<div class="wrap">
  <div class="page-title-row"><div><h2>Configuração de Nota Fiscal</h2><p>Configure os dados fiscais do seu estabelecimento para emissão de NFC-e.</p></div>
    <?php $pronto=($cfg['driver']==='sped')?false:($cfg[array_key_first($ems[$cfg['driver']]['campos'])]??'')!==''; ?>
    <span style="padding:6px 16px;border-radius:20px;font-size:12px;font-weight:700;background:<?= $pronto?'#E5F7EF':'#FDE7E7' ?>;color:<?= $pronto?'#0c6b4a':'#b71c1c' ?>"><?= $pronto?'Configurada':'Não configurada' ?></span>
  </div>

  <div class="fiscal-tabs">
    <a class="<?= $tab==='empresa'?'on':'' ?>" href="?r=admin_fiscal&tab=empresa">1. Dados da empresa</a>
    <a class="<?= $tab==='fiscal'?'on':'' ?>" href="?r=admin_fiscal&tab=fiscal">2. Dados fiscais</a>
    <a class="<?= $tab==='automacao'?'on':'' ?>" href="?r=admin_fiscal&tab=automacao">3. Automação</a>
  </div>

  <form method="post" action="?r=admin_fiscal_save&tab=<?= e($tab) ?>">

  <?php if($tab==='empresa'): ?>
  <div class="cfgcard">
    <h3>Dados da empresa</h3>
    <div class="fisc-grid3">
      <div class="field"><label>CNPJ *</label><input name="nf_cnpj" value="<?= e(setting_get('nf_cnpj')) ?>" placeholder="00.000.000/0000-00" inputmode="numeric"></div>
      <div class="field"><label>Razão Social *</label><input name="nf_razao" value="<?= e(setting_get('nf_razao',cfg('restaurante'))) ?>" placeholder="Razão social da empresa"></div>
      <div class="field"><label>Nome do estabelecimento *</label><input name="nf_fantasia" value="<?= e(setting_get('nf_fantasia',cfg('restaurante'))) ?>" placeholder="Nome fantasia"></div>
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
      <div class="field"><label>Código do Município (IBGE)</label><input name="nf_cmun" value="<?= e(setting_get('nf_cmun')) ?>" placeholder="Ex: 3550308" inputmode="numeric"></div>
      <div class="field"><label>Telefone</label><input name="nf_telefone" value="<?= e(setting_get('nf_telefone')) ?>" placeholder="(00) 00000-0000"></div>
    </div>
  </div>

  <?php elseif($tab==='fiscal'): ?>
  <div class="cfgcard">
    <h3>Emissor de nota</h3>
    <p>Escolha o serviço que vai emitir suas notas fiscais.</p>
    <div class="fisc-grid2">
      <div class="field"><label>Emissor</label>
        <select name="nf_driver" id="nf_driver" onchange="showDriver()">
          <?php $dv=setting_get('nf_driver','plugnotas'); foreach($ems as $k=>$em): ?>
          <option value="<?= $k ?>" <?= $dv===$k?'selected':'' ?>><?= e($em['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <?php foreach($ems as $k=>$em): ?>
        <div class="drvbox" data-drv="<?= $k ?>" style="<?= $dv===$k?'':'display:none' ?>">
          <?php foreach($em['campos'] as $campo=>$lbl): ?>
          <div class="field"><label><?= e($lbl) ?></label>
            <input name="<?= $campo ?>" type="<?= strpos($campo,'senha')!==false||strpos($campo,'token')!==false||strpos($campo,'apikey')!==false?'password':'text' ?>"
                   value="<?= e(setting_get($campo)) ?>" placeholder="<?= e($lbl) ?>">
          </div>
          <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="cfgcard">
    <h3>Dados fiscais</h3>
    <div class="fisc-grid2">
      <div class="field"><label>Inscrição Estadual *</label><input name="nf_ie" value="<?= e(setting_get('nf_ie')) ?>" placeholder="Inscrição Estadual"></div>
      <div class="field"><label>Código de Regime Tributário (CRT) *</label>
        <select name="nf_regime">
          <?php $rg=setting_get('nf_regime','1'); ?>
          <option value="1" <?= $rg==='1'?'selected':'' ?>>Simples Nacional</option>
          <option value="2" <?= $rg==='2'?'selected':'' ?>>Simples Nacional, excesso de sublimite</option>
          <option value="3" <?= $rg==='3'?'selected':'' ?>>Regime Normal</option>
          <option value="4" <?= $rg==='4'?'selected':'' ?>>Regime Normal Especial</option>
        </select>
      </div>
    </div>
    <div class="fisc-grid2">
      <div class="field"><label>Código de Segurança do Contribuinte (CSC) *</label><input name="nf_csc" type="password" value="<?= e(setting_get('nf_csc')) ?>" placeholder="Código CSC"></div>
      <div class="field"><label>Identificador do CSC *</label><input name="nf_csc_id" value="<?= e(setting_get('nf_csc_id','1')) ?>" placeholder="ID do CSC (ex: 1)"></div>
    </div>
    <div class="fisc-grid3">
      <div class="field"><label>Série da NFCe *</label><input name="nf_serie" value="<?= e(setting_get('nf_serie','1')) ?>" placeholder="1" inputmode="numeric"></div>
      <div class="field"><label>Próximo número da NFCe *</label><input name="nf_numero" value="<?= e(setting_get('nf_numero','0')) ?>" placeholder="0" inputmode="numeric"></div>
      <div class="field"><label>Ambiente *</label>
        <select name="nf_ambiente">
          <?php $am=setting_get('nf_ambiente','2'); ?>
          <option value="2" <?= $am==='2'?'selected':'' ?>>Homologação (teste)</option>
          <option value="1" <?= $am==='1'?'selected':'' ?>>Produção (valendo)</option>
        </select>
      </div>
    </div>
    <div class="field"><label>Mensagem do rodapé da nota</label><input name="nf_rodape" value="<?= e(setting_get('nf_rodape','Obrigado pela preferência, Volte sempre')) ?>" placeholder="Mensagem de rodapé" maxlength="140"><small>Máximo de 140 caracteres.</small></div>
  </div>

  <?php elseif($tab==='automacao'): ?>
  <div class="cfgcard">
    <h3>Automação da emissão</h3>
    <p>Configure como e quando as notas fiscais serão emitidas.</p>

    <div class="fiscal-toggle">
      <label class="toggle-row">
        <input type="checkbox" name="nf_auto" value="1" <?= setting_get('nf_auto')==='1'?'checked':'' ?>>
        <div><b>Emissão automática</b><small>Permite a emissão automática de notas fiscais para os pedidos.</small></div>
      </label>
    </div>
    <div class="fiscal-toggle">
      <label class="toggle-row">
        <input type="checkbox" name="nf_auto_print" value="1" <?= setting_get('nf_auto_print')==='1'?'checked':'' ?>>
        <div><b>Impressão automática</b><small>Permite a impressão automática de notas fiscais para os seus pedidos.</small></div>
      </label>
    </div>
    <div class="fiscal-toggle">
      <label class="toggle-row">
        <input type="checkbox" name="nf_envio_whatsapp" value="1" <?= setting_get('nf_envio_whatsapp')==='1'?'checked':'' ?>>
        <div><b>Envio automático da Nota Fiscal via WhatsApp</b><small>Permite que o sistema envie a nota fiscal automaticamente para os clientes via WhatsApp.</small></div>
      </label>
    </div>
    <div class="fiscal-toggle">
      <label class="toggle-row">
        <input type="checkbox" name="nf_pedir_cpf" value="1" <?= setting_get('nf_pedir_cpf')==='1'?'checked':'' ?>>
        <div><b>Solicitação de CPF/CNPJ na nota fiscal</b><small>Permite a solicitação do CPF/CNPJ para a emissão manual da nota fiscal, caso a loja não tenha essa informação.</small></div>
      </label>
    </div>
    <div class="fiscal-toggle">
      <label class="toggle-row">
        <input type="checkbox" name="nf_taxa_servico" value="1" <?= setting_get('nf_taxa_servico')==='1'?'checked':'' ?>>
        <div><b>Taxas de salão e demais acréscimos</b><small>Permite a inclusão da taxa de serviço, couvert, consumação e demais acréscimos na nota fiscal do cliente. Ela aparecerá como "Valor acréscimos" na nota fiscal.</small></div>
      </label>
    </div>
    <div class="fiscal-toggle">
      <label class="toggle-row">
        <input type="checkbox" name="nf_incluir_frete" value="1" <?= setting_get('nf_incluir_frete')==='1'?'checked':'' ?>>
        <div><b>Incluir taxa de entrega</b><small>Permite a inclusão da taxa de entrega na nota fiscal do cliente. Ela aparecerá como "Valor frete" na nota fiscal.</small></div>
      </label>
    </div>
    <div class="fiscal-toggle">
      <label class="toggle-row">
        <input type="checkbox" name="nf_nfe_cnpj" value="1" <?= setting_get('nf_nfe_cnpj')==='1'?'checked':'' ?>>
        <div><b>Emitir NFe quando for informado um CNPJ no pedido</b><small>Permite a emissão de NFE (Nota Fiscal Eletrônica) no modelo 55 (Danfe) quando for informado um CNPJ no pedido.</small></div>
      </label>
    </div>
  </div>
  <?php endif; ?>

  <div style="display:flex;gap:12px;margin-top:20px">
    <?php if($tab!=='empresa'): ?><a href="?r=admin_fiscal&tab=<?= $tab==='automacao'?'fiscal':'empresa' ?>" class="btn" style="background:#eee;color:#333">Voltar</a><?php endif; ?>
    <button type="submit" class="btn" style="background:#e53935;color:#fff">Salvar</button>
  </div>
  </form>
</div>

<style>
.fiscal-tabs{display:flex;gap:0;border-bottom:2px solid #eee;margin-bottom:24px}
.fiscal-tabs a{padding:12px 24px;font-size:14px;font-weight:600;color:#727884;text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-2px;transition:.2s}
.fiscal-tabs a.on{color:#e53935;border-bottom-color:#e53935}
.fiscal-tabs a:hover{color:#333}
.fisc-grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.fisc-grid3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}
@media(max-width:768px){.fisc-grid2,.fisc-grid3{grid-template-columns:1fr}}
.fiscal-toggle{border:1px solid #eee;border-radius:12px;padding:16px 20px;margin-bottom:12px}
.toggle-row{display:flex;align-items:flex-start;gap:14px;cursor:pointer}
.toggle-row input[type="checkbox"]{width:44px;height:24px;-webkit-appearance:none;appearance:none;background:#ccc;border-radius:12px;position:relative;cursor:pointer;flex-shrink:0;margin-top:2px;transition:.2s}
.toggle-row input[type="checkbox"]::after{content:'';position:absolute;width:20px;height:20px;background:#fff;border-radius:50%;top:2px;left:2px;transition:.2s}
.toggle-row input[type="checkbox"]:checked{background:#e53935}
.toggle-row input[type="checkbox"]:checked::after{left:22px}
.toggle-row div b{display:block;font-size:14px;color:#222;margin-bottom:4px}
.toggle-row div small{font-size:12px;color:#888;line-height:1.4}
.cfgcard{background:#fff;border:1px solid #eee;border-radius:14px;padding:24px;margin-bottom:20px}
.cfgcard h3{font-size:16px;margin:0 0 4px;color:#222}
.cfgcard p{font-size:13px;color:#888;margin:0 0 20px}
.cfgcard .field{margin-bottom:14px}
.cfgcard .field label{display:block;font-size:12px;font-weight:600;color:#555;margin-bottom:5px}
.cfgcard .field input,.cfgcard .field select{width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:10px;font-size:14px;box-sizing:border-box}
.cfgcard .field small{font-size:11px;color:#aaa;margin-top:4px;display:block}
.btn{padding:12px 32px;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block}
</style>
<script>
function showDriver(){var v=document.getElementById('nf_driver').value;document.querySelectorAll('.drvbox').forEach(function(el){el.style.display=el.dataset.drv===v?'':'none'})}
</script>
