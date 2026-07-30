<div class="wrap bot-personalize">
  <div class="page-title-row">
    <div><h2>🤖 Robô de vendas do PedeRV</h2><p>Personalize as respostas automáticas do WhatsApp que vendem o PedeRV para novos clientes.</p></div>
  </div>
  <?php if(!empty($salvo)): ?><div class="oknote">Configurações salvas.</div><?php endif; ?>

  <!-- STATUS DA CONEXÃO + CONFIGURAÇÃO WHATSAPP -->
  <?php
  $evoOk = setting_get('saas_evo_url','')!=='' && setting_get('saas_evo_key','')!=='' && setting_get('saas_evo_instance','')!=='';
  ?>
  <details class="saas-panel" style="margin-bottom:20px" <?= !$evoOk?'open':'' ?>>
    <summary style="cursor:pointer;font-weight:700;font-size:14px;padding:14px 18px;list-style:none;display:flex;align-items:center;gap:10px">
      <span>📱 Conexão WhatsApp (Evolution API)</span>
      <?php if($evoOk): ?>
        <span style="background:#E5F7EF;color:#0c6b4a;border-radius:99px;padding:3px 10px;font-size:11px;font-weight:700">✅ Conectado</span>
      <?php else: ?>
        <span style="background:#FFF3CD;color:#856404;border-radius:99px;padding:3px 10px;font-size:11px;font-weight:700">⚠️ Não configurado</span>
      <?php endif; ?>
      <span style="margin-left:auto;opacity:.5;font-size:12px">clique para <?= $evoOk?'editar':'configurar' ?></span>
    </summary>
    <form method="post" action="?r=saas_bot_save" style="padding:0 18px 18px;border-top:1px solid var(--line)">
      <input type="hidden" name="_tab" value="config">
      <div class="grid2" style="margin-top:14px">
        <label>URL da API<input name="saas_evo_url" value="<?= e(setting_get('saas_evo_url','')) ?>" placeholder="https://api.seudominio.com"></label>
        <label>API Key<input name="saas_evo_key" type="password" value="<?= setting_get('saas_evo_key','')!==''?'••••••••':'' ?>" placeholder="sua-chave-aqui" autocomplete="off"></label>
      </div>
      <div class="grid2">
        <label>Nome da instância<input name="saas_evo_instance" value="<?= e(setting_get('saas_evo_instance','')) ?>" placeholder="pederv-vendas"></label>
        <label>Número conectado (com DDD)<input name="saas_evo_phone" value="<?= e(setting_get('saas_evo_phone','')) ?>" placeholder="5511999999999"></label>
      </div>
      <label class="wa-toggle" style="margin-top:8px">
        <input type="checkbox" name="saas_wa_bot_active" value="1" <?= setting_get('saas_wa_bot_active','0')==='1'?'checked':'' ?>>
        <span><b>Ativar robô de vendas</b><small>Responde automaticamente às mensagens recebidas nessa instância.</small></span>
      </label>
      <hr style="margin:16px 0;border:0;border-top:1px solid #eee">
      <p style="font-weight:800;font-size:13px;margin:0 0 10px">👤 Atendimento humano</p>
      <label>Números dos representantes <small style="font-weight:400;color:#888">(um por linha, com DDD sem 55)</small>
        <textarea name="saas_notify_phones" rows="3" placeholder="13997526621&#10;13997752190" style="font-family:monospace;font-size:13px"><?= e(setting_get('saas_notify_phones','')) ?></textarea>
      </label>
      <p style="font-size:11px;color:#888;margin:4px 0 10px">Quando o cliente pedir "atendente", o robô avisa esses números e pausa as respostas automáticas.</p>
      <div class="grid2">
        <label>Delay antes de responder
          <select name="saas_bot_delay" style="margin-top:5px;width:100%;border:1px solid #D6DDE6;border-radius:10px;padding:10px;font:inherit;font-size:13px">
            <option value="0" <?= setting_get('saas_bot_delay','0')==='0'?'selected':'' ?>>Sem delay</option>
            <option value="30" <?= setting_get('saas_bot_delay','0')==='30'?'selected':'' ?>>30 segundos</option>
            <option value="60" <?= setting_get('saas_bot_delay','0')==='60'?'selected':'' ?>>1 minuto</option>
          </select>
        </label>
        <label>Retomar robô automaticamente após
          <select name="saas_human_timeout" style="margin-top:5px;width:100%;border:1px solid #D6DDE6;border-radius:10px;padding:10px;font:inherit;font-size:13px">
            <option value="1" <?= setting_get('saas_human_timeout','4')==='1'?'selected':'' ?>>1 hora</option>
            <option value="2" <?= setting_get('saas_human_timeout','4')==='2'?'selected':'' ?>>2 horas</option>
            <option value="4" <?= setting_get('saas_human_timeout','4')==='4'?'selected':'' ?>>4 horas</option>
            <option value="8" <?= setting_get('saas_human_timeout','4')==='8'?'selected':'' ?>>8 horas</option>
            <option value="0" <?= setting_get('saas_human_timeout','4')==='0'?'selected':'' ?>>Nunca (retomar manual)</option>
          </select>
        </label>
      </div>
      <div style="display:flex;gap:10px;margin-top:14px;align-items:center">
        <button class="saas-btn primary">Salvar configuração</button>
        <?php if($evoOk): ?>
        <a href="?r=saas_bot_test" class="saas-btn" style="text-decoration:none">Testar conexão</a>
        <?php endif; ?>
      </div>
    </form>
  </details>

  <!-- MENSAGENS DO ROBÔ (estilo igual ao painel do cliente) -->
  <form id="botForm">
    <input type="hidden" name="_tab" value="messages">

    <h3 class="bot-section-title" style="margin-bottom:8px">💬 Respostas automáticas por palavra-chave</h3>
    <p style="color:#666;font-size:13px;margin:0 0 14px">Separe palavras-gatilho com <b>|</b>. Tokens: <code>{NOME}</code> <code>{PLANO}</code> <code>{TRIAL_DIAS}</code> <code>{LINK_SITE}</code> <code>{SAUDACAO}</code></p>
    <label class="wa-toggle bot-master" style="margin-bottom:16px">
      <input type="checkbox" id="botMasterActive" name="saas_wa_bot_active" value="1" <?= setting_get('saas_wa_bot_active','0')==='1'?'checked':'' ?>>
      <span><b>Ativar respostas automáticas</b><small>Desative para atender manualmente todas as conversas.</small></span>
    </label>

    <div class="bot-studio">
      <aside>
        <h3>Mensagens</h3>
        <p>Separe palavras semelhantes com <b>|</b>.</p>
        <div id="botCards"></div>
        <button type="button" class="cxgo" onclick="addMessage()">＋ Nova mensagem</button>
      </aside>
      <section class="bot-phone-preview" aria-label="Prévia da conversa">
        <div class="bot-phone-head">WhatsApp · prévia do cliente</div>
        <div class="bot-client-bubble" id="botPreviewTrigger">preço</div>
        <div class="bot-reply-bubble" id="botPreviewReply">...</div>
      </section>
      <section class="cfgcard bot-editor">
        <h3>Personalize a mensagem</h3>
        <label>Quando o cliente escrever</label>
        <input id="editTrigger" placeholder="preço|valor|plano" oninput="syncEditor()">
        <label>O robô responderá</label>
        <textarea id="editReply" rows="10" placeholder="Digite a resposta pronta" oninput="syncEditor()"></textarea>
        <label class="wa-toggle"><input id="editActive" type="checkbox" checked onchange="syncEditor()"> Mensagem ativa</label>
        <div class="bot-tokens">
          <button type="button" onclick="insertToken('{NOME}')">Nome do cliente</button>
          <button type="button" onclick="insertToken('{PLANO}')">Plano escolhido</button>
          <button type="button" onclick="insertToken('{TRIAL_DIAS}')">Dias de teste</button>
          <button type="button" onclick="insertToken('{LINK_SITE}')">Link do site</button>
          <button type="button" onclick="insertToken('{SAUDACAO}')">Saudação</button>
          <button type="button" onclick="compactSpaces()" style="background:#FDE8E8;border-color:#F3C4C0;color:#B4362B" title="Remove linhas em branco extras da mensagem">🧹 Compactar espaços</button>
        </div>
      </section>
    </div>

    <button type="button" class="save bot-save" style="margin-top:18px" onclick="saveMsgs(this)">Salvar mensagens</button>
  </form>
</div>

<script>
let messages=<?= json_encode(array_map(fn($b)=>['gatilho'=>$b['gatilho'],'resposta'=>$b['resposta'],'ativo'=>(int)$b['ativo']],$bot),JSON_UNESCAPED_UNICODE) ?>;
if(!messages.length) messages=[
  {gatilho:'preço|valor|plano|planos|quanto custa|mensalidade|assinatura',resposta:'Olá, {SAUDACAO}! 😊\nNossos planos são:\n*🟢 Pró — R$ 149/mês*\nCardápio digital, pedidos online, WhatsApp bot, KDS cozinha, motoboy e muito mais.\n*⭐ Premium — R$ 199/mês*\nTudo do Pró + relatórios avançados, NF-e fiscal, suporte prioritário.\n✅ Todos os planos têm *{TRIAL_DIAS} dias grátis* para testar!\nQuer começar agora? Acesse: {LINK_SITE}',ativo:1},
  {gatilho:'trial|teste|grátis|gratis|testar|experimentar|demo',resposta:'{SAUDACAO}! 🎉\nSim, você pode testar o PedeRV *completamente grátis por {TRIAL_DIAS} dias* — sem precisar de cartão de crédito!\nÉ só cadastrar seu restaurante em: {LINK_SITE}\nSe tiver dúvidas durante o teste, é só chamar aqui! 😊',ativo:1},
  {gatilho:'como funciona|funcionalidades|recursos|o que inclui|o que tem',resposta:'O *PedeRV* é um sistema completo para restaurantes e deliveries! 🍕\n✅ Cardápio digital com QR Code\n✅ Pedidos online pelo WhatsApp e link\n✅ Robô de atendimento automático\n✅ Gestão de pedidos em tempo real\n✅ KDS para cozinha\n✅ Controle de motoboys\n✅ Relatórios e caixa\n✅ Nota fiscal (plano Premium)\nQuer ver uma demo? Acesse: {LINK_SITE}',ativo:1},
  {gatilho:'suporte|ajuda|duvida|dúvida|problema|não funciona|nao funciona',resposta:'Olá! 😊 Estou aqui para ajudar!\nMe conta o que está acontecendo e resolvo o mais rápido possível.\nNosso suporte funciona de seg a sáb, das 8h às 20h.\nSe preferir, acesse nossa documentação em: {LINK_SITE}',ativo:1},
  {gatilho:'cancelar|cancelamento|desistir|encerrar|sair',resposta:'Que pena! 😢 Antes de cancelar, posso te ajudar a resolver qualquer problema?\nSe ainda quiser cancelar, entre em contato e cuidamos de tudo sem burocracia.\nO cancelamento é imediato e sem multa.',ativo:1},
  {gatilho:'olá|ola|oi|bom dia|boa tarde|boa noite|hello|hey',resposta:'{SAUDACAO}, {NOME}! 👋\nSou o assistente virtual do *PedeRV* — sistema de pedidos e cardápio digital para restaurantes.\nComo posso ajudar?\n1️⃣ Conhecer os planos e preços\n2️⃣ Como funciona o sistema\n3️⃣ Começar o teste grátis\n4️⃣ Falar com um consultor',ativo:1},
];
let current=0;
const botCardsEl=document.getElementById('botCards'),botJsonEl=document.getElementById('botJsonInput'),editTriggerEl=document.getElementById('editTrigger'),editReplyEl=document.getElementById('editReply'),editActiveEl=document.getElementById('editActive'),previewTriggerEl=document.getElementById('botPreviewTrigger'),previewReplyEl=document.getElementById('botPreviewReply');
function cards(){botCardsEl.innerHTML=messages.map((m,i)=>`<button type="button" class="bot-card ${i===current?'on':''}" onclick="selectMessage(${i})"><b>${escapeHtml(m.gatilho||'Nova mensagem')}</b><small>${escapeHtml((m.resposta||'').slice(0,80))}</small></button>`).join('')}
function preview(){const m=messages[current];const saudacao=new Date().getHours()<12?'Bom dia':new Date().getHours()<18?'Boa tarde':'Boa noite';previewTriggerEl.textContent=(m.gatilho||'olá').split('|')[0];previewReplyEl.textContent=(m.resposta||'A resposta aparecerá aqui.').replace('{NOME}','João').replace('{SAUDACAO}',saudacao).replace('{PLANO}','Pró').replace('{TRIAL_DIAS}','7').replace('{LINK_SITE}','pederv.com.br')}
function draw(){cards();const m=messages[current];editTriggerEl.value=m.gatilho;editReplyEl.value=m.resposta;editActiveEl.checked=!!m.ativo;preview()}
function selectMessage(i){current=i;draw()}
function addMessage(){messages.push({gatilho:'',resposta:'',ativo:1});current=messages.length-1;draw();editTriggerEl.focus()}
let _syncT=null;
function syncEditor(){
  messages[current]={gatilho:editTriggerEl.value,resposta:editReplyEl.value,ativo:editActiveEl.checked?1:0};
  preview();
  clearTimeout(_syncT);
  _syncT=setTimeout(function(){cards();},250);
}
function insertToken(t){editReplyEl.setRangeText(t,editReplyEl.selectionStart,editReplyEl.selectionEnd,'end');syncEditor()}
function compactSpaces(){editReplyEl.value=editReplyEl.value.replace(/\n{3,}/g,'\n\n').replace(/\n{2,}/g,'\n').trim();syncEditor()}
function escapeHtml(s){return String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]))}
async function saveMsgs(btn){
  messages[current]={gatilho:editTriggerEl.value,resposta:editReplyEl.value,ativo:editActiveEl.checked?1:0};
  var json=JSON.stringify(messages);
  if(!json||json==='[]'){alert('Nenhuma mensagem para salvar.');return;}
  btn.textContent='Salvando...';btn.disabled=true;
  try{
    var fd=new FormData();
    fd.append('_tab','messages');
    fd.append('bot_json',json);
    fd.append('saas_wa_bot_active',document.getElementById('botMasterActive').checked?'1':'0');
    var r=await fetch('?r=saas_bot_save',{method:'POST',body:fd});
    if(r.redirected){window.location.href=r.url;return;}
    var data=null;try{data=await r.json();}catch(e){}
    if(data&&data.ok){window.location.href='?r=saas_bot&salvo=1';}
    else{btn.textContent='Salvar mensagens';btn.disabled=false;alert('Erro ao salvar: '+(data&&data.erro?data.erro:'resposta inválida. JSON enviado: '+json.substring(0,200)));}
  }catch(e){btn.textContent='Salvar mensagens';btn.disabled=false;alert('Erro de rede: '+e.message);}
}
draw();
</script>
