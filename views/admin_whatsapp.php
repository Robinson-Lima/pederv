<?php
$uazUrl      = setting_get('uaz_url','');
$uazAdmToken = setting_get('uaz_admintoken','');
$uazToken    = setting_get('uaz_token','');
$uazConfigured = ($uazUrl !== '' && $uazAdmToken !== '');
?>
<div class="wrap wa-admin">
  <div class="page-title-row">
    <div><h2>🤖 Conectar robô do WhatsApp</h2><p>Conecte o WhatsApp do restaurante via UazAPI para ativar as respostas automáticas.</p></div>
    <a class="cxgo" href="?r=admin_bot_messages">✏️ Mensagens do robô</a>
  </div>

  <?php if(isset($_GET['saved'])): ?><div class="oknote">✓ Configurações salvas.</div><?php endif; ?>

  <div class="cfgcard" style="margin-bottom:20px">
    <h3>⚙️ Configuração UazAPI</h3>
    <form method="post" action="?r=uazapi_settings_save">
      <label>URL do servidor</label>
      <input name="uaz_url" value="<?= e($uazUrl) ?>" placeholder="https://rvautomacao.uazapi.com">
      <label>Admin Token</label>
      <input name="uaz_admintoken" type="password" value="<?= $uazAdmToken!==''?'••••••••':'' ?>" placeholder="Token de administrador" autocomplete="off">
      <?php if($uazToken!==''): ?>
        <p style="font-size:12px;color:#2d6a30;margin:8px 0 0">✅ Instância criada e token ativo.</p>
      <?php endif; ?>
      <div class="warnmini" style="margin-top:8px">Ficam salvos no banco do servidor. Não vão ao Git.</div>
      <button class="cxgo" style="margin-top:12px">Salvar configuração</button>
    </form>
  </div>

<?php if(!$uazConfigured): ?>
  <div class="oknote" style="background:#fff3cd;color:#6d4c00;border:1.5px solid #f0c040">
    ⚠️ Preencha a URL e o Admin Token acima para ativar a conexão.
  </div>
<?php else: ?>

  <div class="wa-qr-wrap">
    <div class="wa-qr-side">
      <div id="uazStatus" class="wa-status-pill">⏳ Verificando...</div>
      <div id="uazQrBox" class="uaz-qr-box">
        <div class="uaz-qr-placeholder">Clique em <b>Gerar QR Code</b> para começar</div>
      </div>
      <div class="uaz-actions">
        <button class="cxgo" onclick="uazGetQr()">📷 Gerar QR Code</button>
        <button class="cxgo" style="background:#f0f4ff;color:#333" onclick="uazCheckStatus()">🔄 Verificar</button>
        <button onclick="uazDisconnect()" style="background:#fff;color:#c00;border:1px solid #c00;padding:9px 16px;border-radius:10px;cursor:pointer;font-size:13px">Desconectar</button>
      </div>
      <p class="uaz-hint">O QR Code expira em ~60 segundos. Clique em <b>Gerar QR Code</b> novamente se expirar.</p>
      <div id="uazWebhookInfo" style="display:none;margin-top:10px;font-size:12px;padding:8px 12px;border-radius:8px"></div>
    </div>

    <div class="wa-qr-steps">
      <h4>Como conectar em 3 passos</h4>
      <div class="wa-steps">
        <div><span>1</span> Abra o <b>WhatsApp</b> no celular do restaurante.</div>
        <div><span>2</span> Toque em <b>⋮ Menu → Dispositivos conectados → Conectar dispositivo</b>.</div>
        <div><span>3</span> Aponte a câmera para o QR Code aqui na tela.</div>
      </div>
      <p style="margin-top:16px;color:#555;font-size:13px">
        Após conectar, clique em <b>Registrar Webhook</b> para o robô começar a receber e responder mensagens automaticamente.
      </p>
      <a class="cxgo or" href="?r=admin_bot_messages" style="display:inline-block;margin-top:12px">Personalizar respostas automáticas →</a>
    </div>
  </div>

<?php endif; ?>
</div>

<script>
let _uazPolling = null;
const stateMap = {
  disconnected:   '⭕ Desconectado',
  connecting:     '⏳ Conectando...',
  hibernated:     '💤 Hibernando',
  not_configured: '⚙️ Não configurado'
};

async function uazGetQr(){
  const box = document.getElementById('uazQrBox');
  const st  = document.getElementById('uazStatus');
  box.innerHTML = '<div class="uaz-qr-placeholder">⏳ Gerando QR Code...</div>';
  st.textContent = '⏳ Gerando...'; st.className = 'wa-status-pill';
  const d = await fetch('?r=uazapi_qr',{method:'POST'}).then(x=>x.json()).catch(()=>({ok:false}));
  if(d.ok && d.base64){
    box.innerHTML = `<img src="${d.base64}" alt="QR Code" style="width:240px;height:240px;border-radius:8px;display:block">`;
    st.textContent = '📷 Escaneie com o WhatsApp'; st.className = 'wa-status-pill scanning';
    clearInterval(_uazPolling);
    _uazPolling = setInterval(uazCheckStatus, 3000);
    setTimeout(()=>{
      clearInterval(_uazPolling);
      if(document.getElementById('uazStatus').textContent.includes('Escaneie')){
        box.innerHTML = '<div class="uaz-qr-placeholder">QR expirou. Clique em <b>Gerar QR Code</b> novamente.</div>';
        st.textContent = '⚠️ QR expirado'; st.className = 'wa-status-pill';
      }
    }, 65000);
  } else {
    box.innerHTML = `<div class="uaz-qr-placeholder" style="color:#c00">${d.erro||'Erro ao gerar QR. Verifique URL e Admin Token.'}</div>`;
    st.textContent = '❌ Erro'; st.className = 'wa-status-pill error';
  }
}

async function uazCheckStatus(){
  const st  = document.getElementById('uazStatus');
  const box = document.getElementById('uazQrBox');
  const d = await fetch('?r=uazapi_status').then(x=>x.json()).catch(()=>({ok:false}));
  if(d.connected){
    clearInterval(_uazPolling); _uazPolling = null;
    box.innerHTML = '<div class="uaz-connected">✅ WhatsApp conectado!<br><small>O robô está ativo e respondendo às mensagens.</small></div>';
    st.textContent = '✅ Conectado'; st.className = 'wa-status-pill connected';
  } else {
    st.textContent = stateMap[d.state] || '⭕ Desconectado';
    st.className = 'wa-status-pill';
    // QR atualizado via status
    if(d.qrcode && box.querySelector('img')){
      box.querySelector('img').src = d.qrcode;
    }
  }
}

async function uazRegisterWebhook(){
  const info = document.getElementById('uazWebhookInfo');
  info.textContent = '⏳ Registrando...';
  info.style.display = 'block';
  info.style.background = '#f0f4ff'; info.style.color = '#333';
  const d = await fetch('?r=uazapi_webhook_config',{method:'POST'}).then(x=>x.json()).catch(()=>({ok:false}));
  if(d.ok){
    info.style.background = '#f0fff4'; info.style.color = '#1a5c2a';
    info.textContent = '✅ Webhook registrado com sucesso: ' + d.webhook_url;
  } else {
    info.style.background = '#fff0f0'; info.style.color = '#c00';
    info.textContent = '❌ Erro: ' + (d.erro || JSON.stringify(d));
  }
}

async function uazDisconnect(){
  if(!confirm('Desconectar o WhatsApp?')) return;
  await fetch('?r=uazapi_disconnect',{method:'POST'});
  document.getElementById('uazQrBox').innerHTML = '<div class="uaz-qr-placeholder">Desconectado. Clique em <b>Gerar QR Code</b> para reconectar.</div>';
  document.getElementById('uazStatus').textContent = '⭕ Desconectado';
  document.getElementById('uazStatus').className = 'wa-status-pill';
}

document.addEventListener('DOMContentLoaded', ()=>uazCheckStatus());
</script>
