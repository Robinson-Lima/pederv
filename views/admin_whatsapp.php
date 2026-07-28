<?php
$uazOk = uazapi_configured();
$slug  = current_slug();
?>
<div class="wrap wa-admin">
  <div class="page-title-row">
    <div><h2>🤖 Conectar robô do WhatsApp</h2><p>Escaneie o QR Code para ativar o robô no número do restaurante.</p></div>
    <a class="cxgo" href="?r=admin_bot_messages">✏️ Mensagens do robô</a>
  </div>

<?php if(!$uazOk): ?>
  <div class="oknote" style="background:#fff3cd;color:#6d4c00;border:1.5px solid #f0c040">
    ⚠️ A conexão WhatsApp ainda não foi ativada para este painel. Entre em contato com o suporte.
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
    </div>

    <div class="wa-qr-steps">
      <h4>Como conectar em 3 passos</h4>
      <div class="wa-steps">
        <div><span>1</span> Abra o <b>WhatsApp</b> no celular do restaurante.</div>
        <div><span>2</span> Toque em <b>⋮ Menu → Dispositivos conectados → Conectar dispositivo</b>.</div>
        <div><span>3</span> Aponte a câmera para o QR Code aqui na tela.</div>
      </div>
      <p style="margin-top:16px;color:#555;font-size:13px">Após conectar, o robô responderá automaticamente às mensagens recebidas no WhatsApp desse número.</p>
      <a class="cxgo or" href="?r=admin_bot_messages" style="display:inline-block;margin-top:12px">Personalizar respostas automáticas →</a>
    </div>
  </div>

<?php endif; ?>
</div>

<script>
let _uazPolling = null;

async function uazGetQr(){
  const box = document.getElementById('uazQrBox');
  const st  = document.getElementById('uazStatus');
  box.innerHTML = '<div class="uaz-qr-placeholder">⏳ Gerando QR Code...</div>';
  st.textContent = '⏳ Gerando...'; st.className = 'wa-status-pill';
  const d = await fetch('?r=uazapi_qr', {method:'POST'}).then(x=>x.json()).catch(()=>({ok:false}));
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
    box.innerHTML = `<div class="uaz-qr-placeholder" style="color:#c00">${d.erro||'Erro ao gerar QR. Tente novamente.'}</div>`;
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
    const map = {close:'⭕ Desconectado', connecting:'⏳ Conectando...', qr:'📷 Aguardando leitura do QR'};
    st.textContent = map[d.state] || ('⭕ Desconectado');
    st.className = 'wa-status-pill';
  }
}

async function uazDisconnect(){
  if(!confirm('Desconectar o WhatsApp?')) return;
  await fetch('?r=uazapi_disconnect', {method:'POST'});
  document.getElementById('uazQrBox').innerHTML = '<div class="uaz-qr-placeholder">Desconectado. Clique em <b>Gerar QR Code</b> para reconectar.</div>';
  document.getElementById('uazStatus').textContent = '⭕ Desconectado';
  document.getElementById('uazStatus').className = 'wa-status-pill';
}

document.addEventListener('DOMContentLoaded', () => uazCheckStatus());
</script>
