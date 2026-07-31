<div class="wrap bot-personalize">
  <div class="page-title-row"><div><h2>🤖 Configuração Robô</h2><p>Conecte o WhatsApp e personalize as respostas automáticas.</p></div></div>

  <div class="wa-qr-wrap" style="margin-bottom:1.5rem">
    <div class="wa-qr-side">
      <div id="botWaStatus" class="wa-status-pill">⏳ Verificando...</div>
      <div id="botWaQrBox" class="uaz-qr-box">
        <div class="uaz-qr-placeholder">⏳ Aguarde...</div>
      </div>
      <div class="uaz-actions">
        <button class="cxgo" onclick="botWaGetQr()">📷 Gerar QR Code</button>
        <button class="cxgo" style="background:#f0f4ff;color:#333" onclick="botWaCheckStatus()">🔄 Verificar</button>
        <button onclick="botWaDisconnect()" style="background:#fff;color:#c00;border:1px solid #c00;padding:9px 16px;border-radius:10px;cursor:pointer;font-size:13px">Desconectar</button>
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
    </div>
  </div>

  <div class="page-title-row" style="margin-top:1rem"><div><h2>💬 Mensagens pré-prontas</h2><p>Personalize as respostas automáticas usadas no WhatsApp. A IA só é chamada quando nenhum gatilho combinar.</p></div></div>
  <?php if(isset($_GET['salvo'])): ?><div class="oknote">Mensagens salvas.</div><?php endif; ?>
  <form method="post" id="botForm">

    <div class="order-msgs-panel">
      <h3>📦 Acompanhamento automático do pedido</h3>
      <p>Assim que o cliente finaliza o pedido, e a cada mudança de etapa, o sistema manda essas mensagens prontas pelo WhatsApp dele. Deixe em branco para usar o texto padrão. Tokens disponíveis: <code>{NOME}</code> <code>{CODIGO}</code> <code>{ITENS}</code> <code>{ENDERECO}</code> <code>{BAIRRO}</code> <code>{TELEFONE}</code> <code>{TAXA}</code> <code>{TOTAL}</code> <code>{PAGAMENTO}</code> <code>{PAGAMENTO_STATUS}</code> <code>{TEMPO_ENTREGA}</code> <code>{LINK_ACOMPANHAR}</code></p>
      <label class="wa-toggle bot-master"><input type="checkbox" name="channel_whatsapp" value="1" <?= !empty($channelWhats)?'checked':'' ?>> <span><b>Enviar essas mensagens automaticamente</b><small>Precisa do WhatsApp conectado via UazAPI (página Conectar robô).</small></span></label>
      <div class="order-msgs-grid">
        <label><b>1. Pedido recebido</b><small>Enviada assim que o cliente finaliza a compra.</small><textarea name="wa_msg_novo" rows="9"><?= e($orderMsgs['novo']) ?></textarea></label>
        <label><b>2. Em preparo</b><small>Quando a cozinha inicia o pedido.</small><textarea name="wa_msg_preparo" rows="4"><?= e($orderMsgs['preparo']) ?></textarea></label>
        <label><b>3. Saiu para entrega</b><small>Quando o motoboy é despachado.</small><textarea name="wa_msg_saiu" rows="4"><?= e($orderMsgs['saiu']) ?></textarea></label>
        <label><b>4. Entregue</b><small>Quando o motoboy confirma a entrega.</small><textarea name="wa_msg_entregue" rows="4"><?= e($orderMsgs['entregue']) ?></textarea></label>
      </div>
    </div>

    <h3 class="bot-section-title">🤖 Respostas automáticas por palavra-chave</h3>
    <label class="wa-toggle bot-master"><input type="checkbox" name="wa_bot_active" value="1" <?= setting_get('wa_bot_active','0')==='1'?'checked':'' ?>> <span><b>Ativar respostas automáticas do robô</b><small>Desative quando quiser que todas as conversas sejam atendidas manualmente.</small></span></label>
    <div class="bot-studio">
      <aside><h3>Mensagens</h3><p>Separe palavras com <b>|</b>. Use <b>*</b> como resposta padrão quando nenhuma palavra bater.</p><div id="botCards"></div><button type="button" class="cxgo" onclick="addMessage()">＋ Nova mensagem</button></aside>
      <section class="bot-phone-preview" aria-label="Prévia da conversa"><div class="bot-phone-head">WhatsApp · prévia</div><div class="bot-client-bubble" id="botPreviewTrigger">Olá</div><div class="bot-reply-bubble" id="botPreviewReply">Olá! Como posso ajudar?</div></section>
      <section class="cfgcard bot-editor"><h3>Personalize a mensagem</h3><label>Quando o cliente escrever</label><input id="editTrigger" placeholder="olá|oi|boa tarde" oninput="syncEditor()"><label>O robô responderá</label><textarea id="editReply" rows="10" placeholder="Digite a resposta pronta" oninput="syncEditor()"></textarea><label class="wa-toggle"><input id="editActive" type="checkbox" checked onchange="syncEditor()"> Mensagem ativa</label><div class="bot-tokens"><button type="button" onclick="insertToken('{NOME_CLIENTE}')">Nome do cliente</button><button type="button" onclick="insertToken('{LINK_CARDAPIO}')">Link do cardápio</button><button type="button" onclick="insertToken('{SAUDACAO}')">Saudação</button></div></section>
    </div>
    <div id="hiddenBot"></div><button class="save bot-save">Salvar alterações</button>
  </form>
</div>
<script>
// WhatsApp QR connection
const _botSlug = '<?= htmlspecialchars(current_slug(), ENT_QUOTES) ?>';
function _botWaUrl(r){ return _botSlug ? '?r='+r+'&slug='+encodeURIComponent(_botSlug) : '?r='+r; }
let _botWaPolling = null;
const _botStateMap = {disconnected:'⭕ Desconectado',connecting:'⏳ Conectando...',hibernated:'💤 Hibernando',not_configured:'⚙️ Não configurado'};

async function botWaGetQr(){
  const box=document.getElementById('botWaQrBox'), st=document.getElementById('botWaStatus');
  box.innerHTML='<div class="uaz-qr-placeholder">⏳ Gerando QR Code...</div>';
  st.textContent='⏳ Gerando...'; st.className='wa-status-pill';
  const d=await fetch(_botWaUrl('uazapi_qr'),{method:'POST'}).then(x=>x.json()).catch(()=>({ok:false}));
  if(d.ok && d.base64){
    box.innerHTML=`<img src="${d.base64}" alt="QR Code" style="width:240px;height:240px;border-radius:8px;display:block">`;
    st.textContent='📷 Escaneie com o WhatsApp'; st.className='wa-status-pill scanning';
    clearInterval(_botWaPolling);
    _botWaPolling=setInterval(botWaCheckStatus,3000);
    setTimeout(()=>{
      clearInterval(_botWaPolling);
      if(document.getElementById('botWaStatus').textContent.includes('Escaneie')){
        box.innerHTML='<div class="uaz-qr-placeholder">QR expirou. Clique em <b>Gerar QR Code</b> novamente.</div>';
        st.textContent='⚠️ QR expirado'; st.className='wa-status-pill';
      }
    },65000);
  } else {
    box.innerHTML=`<div class="uaz-qr-placeholder" style="color:#c00">${d.erro||'Erro ao gerar QR Code.'}</div>`;
    st.textContent='❌ Erro'; st.className='wa-status-pill error';
  }
}

async function botWaCheckStatus(){
  const st=document.getElementById('botWaStatus'), box=document.getElementById('botWaQrBox');
  const d=await fetch(_botWaUrl('uazapi_status')).then(x=>x.json()).catch(()=>({ok:false}));
  if(d.connected){
    clearInterval(_botWaPolling); _botWaPolling=null;
    box.innerHTML='<div class="uaz-connected">✅ WhatsApp conectado!<br><small>O robô está ativo e respondendo às mensagens.</small></div>';
    st.textContent='✅ Conectado'; st.className='wa-status-pill connected';
  } else {
    st.textContent=_botStateMap[d.state]||'⭕ Desconectado'; st.className='wa-status-pill';
    if(d.qrcode && box.querySelector('img')) box.querySelector('img').src=d.qrcode;
  }
}

async function botWaDisconnect(){
  if(!confirm('Desconectar o WhatsApp?')) return;
  await fetch(_botWaUrl('uazapi_disconnect'),{method:'POST'});
  document.getElementById('botWaQrBox').innerHTML='<div class="uaz-qr-placeholder">Desconectado. Clique em <b>Gerar QR Code</b> para reconectar.</div>';
  document.getElementById('botWaStatus').textContent='⭕ Desconectado';
  document.getElementById('botWaStatus').className='wa-status-pill';
}

document.addEventListener('DOMContentLoaded',()=>{
  botWaCheckStatus().then(()=>{
    // Auto-gera QR se não estiver conectado
    const st=document.getElementById('botWaStatus');
    if(st && !st.textContent.includes('Conectado')) botWaGetQr();
  });
});
</script>
<script>
let messages=<?= json_encode(array_map(fn($b)=>['gatilho'=>$b['gatilho'],'resposta'=>$b['resposta'],'ativo'=>(int)$b['ativo']],$bot),JSON_UNESCAPED_UNICODE) ?>,current=0;
const botCardsEl=document.getElementById('botCards'),hiddenBotEl=document.getElementById('hiddenBot'),editTriggerEl=document.getElementById('editTrigger'),editReplyEl=document.getElementById('editReply'),editActiveEl=document.getElementById('editActive'),previewTriggerEl=document.getElementById('botPreviewTrigger'),previewReplyEl=document.getElementById('botPreviewReply');
if(!messages.length)messages=[{gatilho:'olá|oi',resposta:'Olá! Como posso ajudar? Veja nosso cardápio: {LINK_CARDAPIO}',ativo:1}];
function hidden(){hiddenBotEl.innerHTML=messages.map((m,i)=>`<input type="hidden" name="gatilho[]" value="${attr(m.gatilho)}"><input type="hidden" name="resposta[]" value="${attr(m.resposta)}">${m.ativo?`<input type="hidden" name="ativo[${i}]" value="1">`:''}`).join('')}
function cards(){botCardsEl.innerHTML=messages.map((m,i)=>`<button type="button" class="bot-card ${i===current?'on':''}" onclick="selectMessage(${i})"><b>${escapeHtml(m.gatilho||'Nova mensagem')}</b><small>${escapeHtml((m.resposta||'').slice(0,80))}</small></button>`).join('')}
function preview(){const m=messages[current];previewTriggerEl.textContent=(m.gatilho||'Olá').split('|')[0];previewReplyEl.textContent=(m.resposta||'A resposta aparecerá aqui.').replace('{NOME_CLIENTE}','Robinson').replace('{SAUDACAO}','Olá').replace('{LINK_CARDAPIO}','rvautomacao.com.br/cardapio')}
function draw(){cards();const m=messages[current];editTriggerEl.value=m.gatilho;editReplyEl.value=m.resposta;editActiveEl.checked=!!m.ativo;hidden();preview()}
function selectMessage(i){current=i;draw()}function addMessage(){messages.push({gatilho:'',resposta:'',ativo:1});current=messages.length-1;draw();editTriggerEl.focus()}function syncEditor(){messages[current]={gatilho:editTriggerEl.value,resposta:editReplyEl.value,ativo:editActiveEl.checked?1:0};hidden();cards();preview()}function insertToken(t){editReplyEl.setRangeText(t,editReplyEl.selectionStart,editReplyEl.selectionEnd,'end');syncEditor()}function escapeHtml(s){return String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]))}function attr(s){return escapeHtml(s).replace(/\n/g,'&#10;')}draw();
</script>
