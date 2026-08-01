<div class="wrap dash">
  <h2><?= e(cfg('restaurante')) ?></h2>
  <?= admin_subnav('admin_settings') ?>

  <?php if(isset($_GET['ok'])): ?><div class="oknote">✓ Configurações salvas</div><?php endif; ?>

  <form method="post" action="?r=admin_settings">

    <div class="mtgrid">
      <div>
        <div class="cfgcard">
          <h3>🔴 iFood</h3>
          <p>Preencha quando tiver o cadastro de parceiro. Os pedidos entram na aba iFood.</p>
          <label>Client ID</label>
          <input name="s[ifood_client_id]" value="<?= e(setting_get('ifood_client_id')) ?>" placeholder="Client ID do portal iFood">
          <label>Client Secret</label>
          <input name="s[ifood_client_secret]" type="password" value="<?= e(setting_get('ifood_client_secret')) ?>" placeholder="Client Secret">
          <label>Merchant ID</label>
          <input name="s[ifood_merchant_id]" value="<?= e(setting_get('ifood_merchant_id')) ?>" placeholder="ID da loja">
        </div>

        <div class="cfgcard">
          <h3>💳 Gateway de pagamento</h3>
          <p>Receba cartão online dos seus clientes (o Pix direto continua sem taxa).</p>
          <label>Gateway</label>
          <select name="s[gw_nome]" id="gw_nome" onchange="showGwFields()">
            <?php $gw=setting_get('gw_nome','Mercado Pago');
              foreach(['Mercado Pago','Pagar.me','Stripe','PagSeguro','InfinitePay'] as $g): ?>
              <option <?= $gw===$g?'selected':'' ?>><?= $g ?></option>
            <?php endforeach; ?>
          </select>
          <div id="gw-infinitepay" style="<?= $gw==='InfinitePay'?'':'display:none' ?>">
            <label>Sua InfiniteTag (sem o $)</label>
            <input name="s[gw_infinitepay_handle]" value="<?= e(setting_get('gw_infinitepay_handle','')) ?>" placeholder="ex: meunegocio">
            <div class="warnmini">É o nome que aparece no canto superior do app InfinitePay. O cliente será redirecionado para a página segura da InfinitePay para pagar.</div>
          </div>
          <div id="gw-apikeys" style="<?= $gw==='InfinitePay'?'display:none':'' ?>">
            <label>Public Key</label>
            <input name="s[gw_public_key]" value="<?= e(setting_get('gw_public_key')) ?>" placeholder="APP_USR-...">
            <label>Access Token (chave secreta)</label>
            <input name="s[gw_access_token]" type="password" value="<?= e(setting_get('gw_access_token')) ?>" placeholder="token secreto">
            <div class="warnmini">⚠ Nunca compartilhe suas chaves. Ficam salvas no banco do servidor.</div>
          </div>
        </div>

        <div class="cfgcard">
          <h3>🧾 Nota Fiscal (NFC-e)</h3>
          <p>Escolha o emissor e cole a API dele. Você pode trocar quando quiser.</p>

          <label>Emissor de nota</label>
          <select name="s[nf_driver]" id="nf_driver" onchange="showDriver()">
            <?php $dv=setting_get('nf_driver','plugnotas');
              foreach(fiscal_emissores() as $k=>$em): ?>
              <option value="<?= $k ?>" <?= $dv===$k?'selected':'' ?>><?= e($em['label']) ?></option>
            <?php endforeach; ?>
          </select>

          <?php foreach(fiscal_emissores() as $k=>$em): ?>
            <div class="drvbox" data-drv="<?= $k ?>" style="<?= $dv===$k?'':'display:none' ?>">
              <?php foreach($em['campos'] as $campo=>$lbl): ?>
                <label><?= e($lbl) ?></label>
                <input name="s[<?= $campo ?>]" type="<?= strpos($campo,'senha')!==false||strpos($campo,'token')!==false||strpos($campo,'apikey')!==false?'password':'text' ?>"
                       value="<?= e(setting_get($campo)) ?>" placeholder="<?= e($lbl) ?>">
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>

          <div style="height:10px"></div>
          <label>Dados fiscais (comuns a todos)</label>
          <div class="fisc2">
            <input name="s[nf_cnpj]" value="<?= e(setting_get('nf_cnpj')) ?>" placeholder="CNPJ">
            <input name="s[nf_ie]" value="<?= e(setting_get('nf_ie')) ?>" placeholder="Inscrição Estadual">
          </div>
          <div class="fisc2">
            <input name="s[nf_uf]" value="<?= e(setting_get('nf_uf','SP')) ?>" placeholder="UF (ex: SP)">
            <input name="s[nf_serie]" value="<?= e(setting_get('nf_serie','1')) ?>" placeholder="Série (ex: 1)">
          </div>
          <div class="fisc2">
            <select name="s[nf_regime]">
              <?php $rg=setting_get('nf_regime','1'); ?>
              <option value="1" <?= $rg==='1'?'selected':'' ?>>Simples Nacional</option>
              <option value="3" <?= $rg==='3'?'selected':'' ?>>Regime Normal</option>
            </select>
            <select name="s[nf_ambiente]">
              <?php $am=setting_get('nf_ambiente','2'); ?>
              <option value="2" <?= $am==='2'?'selected':'' ?>>Homologação (teste)</option>
              <option value="1" <?= $am==='1'?'selected':'' ?>>Produção (valendo)</option>
            </select>
          </div>
          <label>CSC / ID Token (para o QR Code da NFC-e)</label>
          <div class="fisc2">
            <input name="s[nf_csc]" type="password" value="<?= e(setting_get('nf_csc')) ?>" placeholder="CSC">
            <input name="s[nf_csc_id]" value="<?= e(setting_get('nf_csc_id','1')) ?>" placeholder="ID CSC (ex: 1)">
          </div>
          <label style="display:flex;align-items:center;gap:9px;margin-top:12px">
            <input type="checkbox" name="s[nf_auto]" value="1" <?= setting_get('nf_auto')==='1'?'checked':'' ?> style="width:auto">
            Emitir NFC-e automaticamente a cada venda paga
          </label>
          <div class="warnmini">A emissão real exige o emissor configurado + credenciamento na SEFAZ. Certificado A1 (se usar Direto SEFAZ) vai por FTP em <code>data/certificado.pfx</code>.</div>
        </div>
      </div>

      <div>
        <div class="cfgcard">
          <h3>💠 PIX</h3>
          <p>Chave PIX exibida no checkout para pagamento manual. O QR Code é gerado automaticamente.</p>
          <label>Chave PIX (CPF, CNPJ, e-mail, telefone ou aleatória)</label>
          <input name="s[pix_key]" value="<?= e(setting_get('pix_key','')) ?>" placeholder="Ex: 11999999999 ou chave-aleatoria@banco">
          <label>Nome do favorecido (aparece no comprovante)</label>
          <input name="s[pix_favorecido]" value="<?= e(setting_get('pix_favorecido','')) ?>" placeholder="Ex: Restaurante Sabor & Arte">
        </div>

        <div class="cfgcard">
          <h3>⚡ n8n (automação)</h3>
          <label>Webhook do n8n (mensagens automáticas)</label>
          <input name="s[n8n_webhook]" value="<?= e(setting_get('n8n_webhook')) ?>" placeholder="https://seu-n8n/webhook/rvcardapios">
          <p>Recebe os eventos: pedido criado, pago, saiu p/ entrega, entregue.</p>
        </div>
      </div>
    </div>

    <button class="save">💾 Salvar todas as configurações</button>
  </form>

  <div class="two" style="margin-top:16px">
    <div class="cfgcard" id="areas">
      <h3>🛵 Áreas de entrega & frete</h3>
      <p>Cadastre os bairros que você atende e o valor do frete de cada um. No checkout o cliente escolhe o bairro e o frete é calculado sozinho.</p>
      <form method="post" action="?r=area_salvar" style="display:flex;gap:8px;margin-bottom:10px">
        <input name="bairro" placeholder="Bairro" style="flex:2">
        <input name="taxa" placeholder="Frete (ex: 5,00)" style="flex:1">
        <button class="cxgo or" style="padding:0 14px">+ Add</button>
      </form>
      <?php if(empty($areas)): ?><p style="font-size:12px;color:var(--muted)">Nenhuma área cadastrada — usando o frete padrão do config.php.</p><?php endif; ?>
      <?php foreach($areas as $a): ?>
        <div class="pcol" style="padding:8px 11px">
          <div class="w"><div class="nm"><?= e($a['bairro']) ?></div></div>
          <div class="amt"><?= money($a['taxa']) ?></div>
          <form method="post" action="?r=area_excluir" style="margin:0">
            <input type="hidden" name="id" value="<?= $a['id'] ?>"><button class="del">×</button></form>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="cfgcard" id="operadores">
      <h3>👤 Operadores de caixa</h3>
      <p>Quem pode abrir e operar o caixa. O nome aparece no caixa e no fechamento — assim dá pra saber quem operou se der quebra.</p>
      <form method="post" action="?r=operador_salvar" style="display:flex;gap:8px;margin-bottom:10px">
        <input name="nome" placeholder="Nome do operador" style="flex:2">
        <input name="pin" placeholder="PIN (opcional)" style="flex:1">
        <button class="cxgo or" style="padding:0 14px">+ Add</button>
      </form>
      <?php if(empty($operadores)): ?><p style="font-size:12px;color:var(--muted)">Nenhum operador cadastrado ainda.</p><?php endif; ?>
      <?php foreach($operadores as $op): ?>
        <div class="pcol" style="padding:8px 11px">
          <div class="w"><div class="nm"><?= e($op['nome']) ?></div></div>
          <form method="post" action="?r=operador_excluir" style="margin:0">
            <input type="hidden" name="id" value="<?= $op['id'] ?>"><button class="del">×</button></form>
        </div>
      <?php endforeach; ?>

      <div style="border-top:1px solid var(--line);margin-top:14px;padding-top:12px" id="permissoes">
        <h3>🔐 Quem pode fechar comanda</h3>
        <p>Escolha se o garçom pode receber e fechar a mesa no app dele. Fechando por lá, fica registrado o nome de quem fechou e recebeu.</p>
        <input type="hidden" name="s[garcom_fecha_comanda]" value="0">
        <label class="auto-accept perm-toggle">
          <input type="checkbox" name="s[garcom_fecha_comanda]" value="1" <?= setting_get('garcom_fecha_comanda','1')==='1'?'checked':'' ?>><span></span>
          <b>Garçom pode fechar comanda e receber</b>
        </label>
        <small style="color:var(--muted);font-size:12px">Desmarcado = só o caixa fecha; o app do garçom mostra o aviso pra levar ao caixa.</small>
      </div>

      <div style="border-top:1px solid var(--line);margin-top:14px;padding-top:12px" id="garcons">
        <h3>🧑‍🍳 Garçons</h3>
        <p>Aparecem na lista quando o garçom entra no app, pra registrar quem atendeu cada mesa.</p>
        <form method="post" action="?r=garcom_salvar" style="display:flex;gap:8px;margin-bottom:10px">
          <input name="nome" placeholder="Nome do garçom" style="flex:1">
          <button class="cxgo or" style="padding:0 14px">+ Add</button>
        </form>
        <?php if(empty($garcons)): ?><p style="font-size:12px;color:var(--muted)">Nenhum garçom cadastrado ainda.</p><?php endif; ?>
        <?php foreach($garcons as $g): ?>
          <div class="pcol" style="padding:8px 11px">
            <div class="w"><div class="nm"><?= e($g['nome']) ?></div></div>
            <form method="post" action="?r=garcom_excluir" style="margin:0">
              <input type="hidden" name="id" value="<?= $g['id'] ?>"><button class="del">×</button></form>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<div class="cfgcard users-card" id="usuarios">
  <h3>🔐 Usuários e permissões</h3>
  <p>Crie um acesso individual para cada funcionário. Cada pessoa verá apenas a área correspondente à sua função.</p>
  <form method="post" action="?r=user_salvar" class="user-form">
    <input type="hidden" name="slug" value="<?= e(current_slug()) ?>">
    <input name="nome" placeholder="Nome completo" required>
    <input name="usuario" placeholder="Usuário para entrar" required>
    <input name="senha" type="password" minlength="4" placeholder="Senha (mínimo 4 caracteres)" required>
    <input name="telefone" placeholder="WhatsApp com DDD">
    <select name="role" required><option value="garcom">Garçom</option><option value="motoboy">Motoboy</option><option value="cozinha">Cozinha</option><option value="caixa">Caixa</option><option value="admin">Administrador</option></select>
    <button class="cxgo or">+ Criar usuário</button>
  </form>
  <div class="user-list"><?php foreach($users??[] as $u): ?><details class="user-edit-row"><summary><span class="user-avatar"><?= e(mb_substr($u['nome'],0,1)) ?></span><div><b><?= e($u['nome']) ?></b><small>@<?= e($u['usuario']) ?> · <?= e(ucfirst($u['role'])) ?><?= $u['telefone']?' · '.e($u['telefone']):'' ?></small></div><span class="edit-user">Editar</span></summary><form method="post" action="?r=user_salvar" class="user-form edit-user-form"><input type="hidden" name="slug" value="<?= e(current_slug()) ?>"><input type="hidden" name="id" value="<?= $u['id'] ?>"><input name="nome" value="<?= e($u['nome']) ?>" required><input name="usuario" value="<?= e($u['usuario']) ?>" required><input name="senha" type="password" minlength="4" placeholder="Nova senha (vazio mantém a atual)"><input name="telefone" value="<?= e($u['telefone']) ?>" placeholder="WhatsApp"><select name="role"><?php foreach(['garcom'=>'Garçom','motoboy'=>'Motoboy','cozinha'=>'Cozinha','caixa'=>'Caixa','admin'=>'Administrador'] as $rv=>$rl): ?><option value="<?= $rv ?>" <?= $u['role']===$rv?'selected':'' ?>><?= $rl ?></option><?php endforeach; ?></select><button class="cxgo or">Salvar alterações</button></form><form method="post" action="?r=user_excluir" class="delete-user-form" onsubmit="return confirm('Excluir definitivamente este acesso?')"><input type="hidden" name="slug" value="<?= e(current_slug()) ?>"><input type="hidden" name="id" value="<?= $u['id'] ?>"><button type="submit">Excluir usuário</button></form></details><?php endforeach; ?></div>
</div>

<script>
function addBot(){
  const d=document.createElement('div'); d.className='botrow';
  d.innerHTML='<input name="bot_gatilho[]" placeholder="palavra1|palavra2"><textarea name="bot_resposta[]" rows="2" placeholder="Resposta pronta"></textarea>';
  document.getElementById('botlist').appendChild(d);
}
function showGwFields(){
  const v=document.getElementById('gw_nome').value;
  const ip=document.getElementById('gw-infinitepay'), ak=document.getElementById('gw-apikeys');
  if(ip) ip.style.display=v==='InfinitePay'?'block':'none';
  if(ak) ak.style.display=v==='InfinitePay'?'none':'block';
}
function showDriver(){
  const v=document.getElementById('nf_driver').value;
  document.querySelectorAll('.drvbox').forEach(b=>b.style.display = b.dataset.drv===v?'block':'none');
}
</script>
