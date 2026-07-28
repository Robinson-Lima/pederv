<div class="wrap bot-personalize">
  <div class="page-title-row"><div><h2>🤖 Mensagens pré-prontas do robô</h2><p>Personalize as respostas automáticas usadas no WhatsApp. A inteligência artificial só é chamada quando nenhum gatilho combinar.</p></div><a class="cxgo" href="?r=admin_whatsapp">Conexão do WhatsApp</a></div>
  <?php if(isset($_GET['salvo'])): ?><div class="oknote">Mensagens salvas.</div><?php endif; ?>
  <form method="post" id="botForm">

    <div class="order-msgs-panel">
      <h3>📦 Acompanhamento automático do pedido</h3>
      <p>Assim que o cliente finaliza o pedido, e a cada mudança de etapa, o sistema manda essas mensagens prontas pelo WhatsApp dele. Deixe em branco para usar o texto padrão. Tokens disponíveis: <code>{NOME}</code> <code>{CODIGO}</code> <code>{ITENS}</code> <code>{ENDERECO}</code> <code>{BAIRRO}</code> <code>{TELEFONE}</code> <code>{TAXA}</code> <code>{TOTAL}</code> <code>{PAGAMENTO}</code> <code>{PAGAMENTO_STATUS}</code> <code>{TEMPO_ENTREGA}</code> <code>{LINK_ACOMPANHAR}</code></p>
      <label class="wa-toggle bot-master"><input type="checkbox" name="channel_whatsapp" value="1" <?= !empty($channelWhats)?'checked':'' ?>> <span><b>Enviar essas mensagens automaticamente</b><small>Precisa da Central WhatsApp conectada (Evolution API).</small></span></label>
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
      <aside><h3>Mensagens</h3><p>Separe palavras semelhantes com <b>|</b>.</p><div id="botCards"></div><button type="button" class="cxgo" onclick="addMessage()">＋ Nova mensagem</button></aside>
      <section class="bot-phone-preview" aria-label="Prévia da conversa"><div class="bot-phone-head">WhatsApp · prévia</div><div class="bot-client-bubble" id="botPreviewTrigger">Olá</div><div class="bot-reply-bubble" id="botPreviewReply">Olá! Como posso ajudar?</div></section>
      <section class="cfgcard bot-editor"><h3>Personalize a mensagem</h3><label>Quando o cliente escrever</label><input id="editTrigger" placeholder="olá|oi|boa tarde" oninput="syncEditor()"><label>O robô responderá</label><textarea id="editReply" rows="10" placeholder="Digite a resposta pronta" oninput="syncEditor()"></textarea><label class="wa-toggle"><input id="editActive" type="checkbox" checked onchange="syncEditor()"> Mensagem ativa</label><div class="bot-tokens"><button type="button" onclick="insertToken('{NOME_CLIENTE}')">Nome do cliente</button><button type="button" onclick="insertToken('{LINK_CARDAPIO}')">Link do cardápio</button><button type="button" onclick="insertToken('{SAUDACAO}')">Saudação</button></div></section>
    </div>
    <div id="hiddenBot"></div><button class="save bot-save">Salvar alterações</button>
  </form>
</div>
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
