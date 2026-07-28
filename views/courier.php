<div class="mhead"><div class="mh"><div class="lg">🛵</div><div><h2>Minhas entregas</h2><div class="meta">App do motoboy</div></div><a class="courier-logout" href="?r=logout" onclick="return confirm('Sair da sua conta? Outro motoboy poderá entrar neste aparelho.')">Sair ⎋</a></div></div>

<?php $online = !empty($courier['online']); ?>
<button class="som-permission" id="somPermission" type="button">🔔 Ativar alertas de novas entregas</button>
<div class="courier-notification-setup" id="notificationSetup">
  <div><b>Alertas fora da tela</b><small id="notificationStatus">Verificando permissao do aparelho...</small></div>
  <button type="button" id="installApp" hidden>Instalar app</button>
  <button type="button" id="testAlert">Testar alerta</button>
</div>
<div class="courier-rota <?= $online?'on':'off' ?>" id="rotaBox">
  <div class="rota-info">
    <b id="rotaTitulo"><?= $online?'ROTA ATIVA':'ROTA DESATIVADA' ?></b>
    <small id="rotaSub"><?= $online?'Você está online e pode receber corridas.':'Você está offline. O restaurante não vai te enviar entregas.' ?></small>
  </div>
  <button class="rota-btn" id="rotaBtn" onclick="toggleRota()" <?= !$courier?'disabled':'' ?>><?= $online?'Desativar rota':'Ativar rota' ?></button>
</div>
<div class="som-alert" id="somAlert" style="display:none">🔔 Toque aqui para liberar o som dos avisos de corrida</div>

<div class="courier-summary"><div><small>MOTOBOY</small><b><?= e($courier['nome']??($_SESSION['user_nome']??'Sem vínculo')) ?></b></div><div><small>ENTREGAS HOJE</small><b><?= (int)($stats['hoje']??0) ?></b></div><div><small>NO MÊS</small><b><?= (int)($stats['mes']??0) ?></b></div></div>
<?php if(!$courier): ?><div class="courier-alert">Seu usuário ainda não está vinculado a um cadastro de motoboy. Peça ao administrador para editar e salvar seu usuário como Motoboy.</div><?php elseif(!$online): ?><div class="courier-alert">Ative a rota acima para começar a receber entregas.</div><?php elseif($deliveries): ?><div class="courier-alert">🔔 <?= count($deliveries) ?> entrega(s) atribuída(s) a você. Retire no balcão.</div><?php endif; ?>
<div class="cwrap courier-list">
  <?php if(!$deliveries): ?><p class="empty-dark">Nenhuma entrega no momento.</p><?php endif; ?>
  <?php foreach($deliveries as $o): ?><div class="deliv" id="d<?= $o['id'] ?>">
    <div class="oid"><?= e($o['codigo']) ?> · <span><?= e($o['status']) ?></span><?php if(!empty($o['aceito'])): ?><em> · aceita ✓</em><?php endif; ?></div>
    <div class="cli"><?= e($o['cliente_nome']) ?></div>
    <?php if(!empty($o['cliente_fone'])): ?><a class="fone" href="https://wa.me/55<?= preg_replace('/\D/','',$o['cliente_fone']) ?>" target="_blank">📞 <?= e($o['cliente_fone']) ?></a><?php endif; ?>
    <div class="adr">📍 <?= e($o['endereco'] ?: 'Endereço não informado') ?><?= !empty($o['bairro'])?' — '.e($o['bairro']):'' ?></div>
    <?php if(!empty($o['lat']) && !empty($o['lng'])): ?><a class="fone" href="https://www.google.com/maps/dir/?api=1&destination=<?= (float)$o['lat'] ?>,<?= (float)$o['lng'] ?>" target="_blank">🗺 Abrir rota no Maps</a><?php endif; ?>
    <?php if(!empty($o['referencia'])): ?><div class="adr ref">Ref: <?= e($o['referencia']) ?></div><?php endif; ?>
    <div class="val"><?= money($o['total']) ?> · <?= e(payment_label($o['pagamento_metodo'])) ?><strong class="pay-situation <?= $o['pagamento_status']==='pago'?'paid':'collect' ?>"><?= e(payment_situation_label($o)) ?></strong></div>
    <?php if(empty($o['aceito'])): ?><button class="done accept" onclick="aceitar(<?= $o['id'] ?>)">✓ Aceitar entrega</button><?php endif; ?>
    <div class="cbtns"><button class="cb ok" <?= (empty($o['aceito'])||$o['status']!=='saiu_entrega')?'disabled title="Aceite e aguarde o pedido sair para entrega"':'' ?> onclick="entregue(<?= $o['id'] ?>)">✓ Entregue</button><button class="cb warn" onclick="ocorrencia(<?= $o['id'] ?>,'não localizado')">Cliente não localizado</button><button class="cb warn" onclick="ocorrencia(<?= $o['id'] ?>,'não atende')">Cliente não atende</button></div>
    <div class="obsbox" id="obs<?= $o['id'] ?>" style="display:none"><textarea id="obst<?= $o['id'] ?>" placeholder="Observação (obrigatória)"></textarea><button class="cb warn" onclick="confirmarOco(<?= $o['id'] ?>)">Registrar ocorrência</button></div>
  </div><?php endforeach; ?>
</div>
<script>
let ocoAtual={}, ONLINE=<?= $online?'true':'false' ?>, IDS=<?= json_encode(array_map(fn($x)=>(int)$x['id'],$deliveries)) ?>, alarme=null, actx=null,alarmAudio=null;
const COURIER_ID=<?= (int)($courier['id']??0) ?>, SEEN_KEY='rv_courier_seen_'+COURIER_ID;
let SOUND_READY=localStorage.getItem('rv_courier_sound_ready')==='1', LAST_CHECK=Date.now(), WAS_HIDDEN=false, INSTALL_PROMPT=null;
let SEEN=JSON.parse(localStorage.getItem(SEEN_KEY)||'[]');
if('serviceWorker'in navigator)navigator.serviceWorker.register('sw.js?v=14').catch(()=>{});
async function notifyBackground(){
  if(!('Notification'in window)||Notification.permission!=='granted')return;
  const options={body:'Retire no balcão. Toque aqui para abrir o app e aceitar.',tag:'rv-nova-entrega',renotify:true,requireInteraction:true,vibrate:[250,120,250,120,500],data:{url:'?r=courier'}};
  try{const reg=await navigator.serviceWorker.ready;if(reg.active)reg.active.postMessage({type:'SHOW_DELIVERY',options});else await reg.showNotification('🛵 Nova entrega para você!',options)}catch(e){new Notification('Nova entrega para você!',options)}
}
async function notifyBackgroundV11(){
  if(!('Notification'in window)||Notification.permission!=='granted')return;
  const options={body:'Retire no balcao. Toque aqui para abrir o app e aceitar.',tag:'rv-nova-entrega-'+Date.now(),renotify:true,requireInteraction:true,silent:false,timestamp:Date.now(),icon:'assets/rv-logo.png',badge:'assets/rv-logo.png',vibrate:[250,120,250,120,500],data:{url:'?r=courier'}};
  try{const reg=await navigator.serviceWorker.ready;await reg.showNotification('Nova entrega para voce!',options)}catch(e){try{new Notification('Nova entrega para voce!',options)}catch(_){}}
}
function notificationStatus(){
  const el=document.getElementById('notificationStatus');
  if(!('Notification'in window)){el.textContent='Este navegador nao aceita alertas. Instale o app no celular.';return}
  if(Notification.permission==='granted')el.textContent=PUSH_READY?'Ativo: o aparelho avisa mesmo com o app em outra tela ou fechado.':'Ativo: o aparelho pode avisar mesmo com outra tela aberta.';
  else if(Notification.permission==='denied')el.textContent='Bloqueado. Libere Notificacoes nas configuracoes do navegador.';
  else el.textContent='Toque em Ativar alertas e permita as notificacoes.';
}

/* ---- NOTIFICAÇÃO PUSH DE VERDADE (funciona com o app fechado ou em outra tela) ---- */
let PUSH_READY=false;
function urlBase64ToUint8Array(base64String){
  const padding='='.repeat((4-base64String.length%4)%4);
  const base64=(base64String+padding).replace(/-/g,'+').replace(/_/g,'/');
  const raw=atob(base64),arr=new Uint8Array(raw.length);
  for(let i=0;i<raw.length;i++)arr[i]=raw.charCodeAt(i);
  return arr;
}
async function ensurePushSubscription(){
  try{
    if(!('serviceWorker'in navigator)||!('PushManager'in window)||Notification.permission!=='granted')return false;
    const keyRes=await fetch('?r=push_vapid_key').then(r=>r.json());
    if(!keyRes.ok||!keyRes.key)return false;
    const reg=await navigator.serviceWorker.ready;
    let sub=await reg.pushManager.getSubscription();
    if(!sub)sub=await reg.pushManager.subscribe({userVisibleOnly:true,applicationServerKey:urlBase64ToUint8Array(keyRes.key)});
    const j=sub.toJSON();
    await fetch('?r=push_subscribe',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({endpoint:j.endpoint,p256dh:j.keys.p256dh,auth:j.keys.auth})});
    PUSH_READY=true;return true;
  }catch(e){PUSH_READY=false;return false}
}
if(Notification.permission==='granted')ensurePushSubscription().then(notificationStatus);

window.addEventListener('beforeinstallprompt',e=>{e.preventDefault();INSTALL_PROMPT=e;document.getElementById('installApp').hidden=false});
window.addEventListener('appinstalled',()=>{INSTALL_PROMPT=null;document.getElementById('installApp').hidden=true;notificationStatus()});
if(!window.matchMedia('(display-mode: standalone)').matches)document.getElementById('installApp').hidden=false;
document.getElementById('installApp').addEventListener('click',async()=>{if(!INSTALL_PROMPT){alert('Abra o menu do navegador e escolha “Adicionar à tela inicial” ou “Instalar app”.');return}INSTALL_PROMPT.prompt();await INSTALL_PROMPT.userChoice;INSTALL_PROMPT=null;document.getElementById('installApp').hidden=true});
document.getElementById('testAlert').addEventListener('click',async()=>{
  liberarSom();
  if('Notification'in window&&Notification.permission==='default')await Notification.requestPermission();
  await ensurePushSubscription();
  notificationStatus();await notifyBackgroundV11();toqueCorrida();
  // também testa a notificação real, chegando pelo servidor — é o que confirma se funciona de longe.
  const r=await fetch('?r=push_test',{method:'POST'}).then(x=>x.json()).catch(()=>null);
  if(r&&!r.ok&&r.erro)setTimeout(()=>{document.getElementById('notificationStatus').textContent+=' · '+r.erro},600);
});
document.addEventListener('visibilitychange',()=>{if(document.hidden)WAS_HIDDEN=true;else checar()});
document.getElementById('somPermission').style.display=SOUND_READY?'none':'block';
notificationStatus();

/* ---- SOM ESTILO CORRIDA DE APP: 3 bipes que se repetem até o motoboy aceitar ---- */
function ctx(){if(!actx){try{actx=new (window.AudioContext||window.webkitAudioContext)()}catch(e){}}return actx}
function buildAlarm(){if(alarmAudio)return alarmAudio;const rate=22050,dur=1.15,n=Math.floor(rate*dur),buf=new ArrayBuffer(44+n*2),v=new DataView(buf);const w=(o,s)=>{for(let i=0;i<s.length;i++)v.setUint8(o+i,s.charCodeAt(i))};w(0,'RIFF');v.setUint32(4,36+n*2,true);w(8,'WAVEfmt ');v.setUint32(16,16,true);v.setUint16(20,1,true);v.setUint16(22,1,true);v.setUint32(24,rate,true);v.setUint32(28,rate*2,true);v.setUint16(32,2,true);v.setUint16(34,16,true);w(36,'data');v.setUint32(40,n*2,true);for(let i=0;i<n;i++){const t=i/rate,on=(t<.23||(t>.34&&t<.57)||(t>.68&&t<1.08));const f=t>.68?1380:1040;v.setInt16(44+i*2,on?Math.sin(2*Math.PI*f*t)*18000:0,true)}alarmAudio=new Audio(URL.createObjectURL(new Blob([buf],{type:'audio/wav'})));alarmAudio.preload='auto';return alarmAudio}
function bip(freq,ini,dur){const a=ctx();if(!a)return;const o=a.createOscillator(),g=a.createGain();o.type='sine';o.frequency.value=freq;o.connect(g);g.connect(a.destination);g.gain.setValueAtTime(0.001,a.currentTime+ini);g.gain.exponentialRampToValueAtTime(0.6,a.currentTime+ini+0.02);g.gain.exponentialRampToValueAtTime(0.001,a.currentTime+ini+dur);o.start(a.currentTime+ini);o.stop(a.currentTime+ini+dur+0.02)}
function toqueCorrida(){const media=buildAlarm();media.currentTime=0;media.play().catch(()=>{const a=ctx();if(a&&a.state==='suspended'){document.getElementById('somAlert').style.display='block';a.resume().catch(()=>{})}bip(1050,0,.18);bip(1050,.26,.18);bip(1400,.52,.30)});if(navigator.vibrate)navigator.vibrate([220,120,220,120,380])}
function iniciarAlarme(){if(!SOUND_READY||alarme)return;toqueCorrida();alarme=setInterval(toqueCorrida,4200)}
function pararAlarme(){if(alarme){clearInterval(alarme);alarme=null}}
function liberarSom(){const a=ctx();if(a&&a.state==='suspended')a.resume();buildAlarm();SOUND_READY=true;localStorage.setItem('rv_courier_sound_ready','1');document.getElementById('somPermission').style.display='none';document.getElementById('somAlert').style.display='none';bip(880,0,.1)}
document.addEventListener('click',()=>{const a=ctx();if(a&&a.state==='suspended')a.resume()},{once:true});
document.getElementById('somAlert').addEventListener('click',liberarSom);
document.getElementById('somPermission').addEventListener('click',async()=>{
  const a=ctx();if(a&&a.state==='suspended')await a.resume().catch(()=>{});
  buildAlarm();SOUND_READY=true;localStorage.setItem('rv_courier_sound_ready','1');
  document.getElementById('somPermission').style.display='none';
  document.getElementById('somAlert').style.display='none';bip(880,0,.12);
  if('Notification'in window&&Notification.permission==='default')await Notification.requestPermission();
  await ensurePushSubscription();
  notificationStatus();
});

/* ---- LIGAR / DESLIGAR A ROTA ---- */
async function toggleRota(){
  liberarSom();
  const novo=!ONLINE;
  const r=await fetch('?r=courier_toggle_online',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'online='+(novo?1:0)}).then(x=>x.json());
  if(!r.ok){alert('Não foi possível mudar o status da rota.');return}
  ONLINE=novo;pintarRota()
}
function pintarRota(){
  const box=document.getElementById('rotaBox');
  box.classList.toggle('on',ONLINE);box.classList.toggle('off',!ONLINE);
  document.getElementById('rotaTitulo').textContent=ONLINE?'ROTA ATIVA':'ROTA DESATIVADA';
  document.getElementById('rotaSub').textContent=ONLINE?'Você está online e pode receber corridas.':'Você está offline. O restaurante não vai te enviar entregas.';
  document.getElementById('rotaBtn').textContent=ONLINE?'Desativar rota':'Ativar rota';
  if(!ONLINE)pararAlarme();
}

/* ---- POLLING: corrida nova cai aqui ---- */
async function checar(){
  try{
    const agora=Date.now(), pausado=agora-LAST_CHECK>7000, estavaFora=WAS_HIDDEN||pausado||document.hidden;LAST_CHECK=agora;
    const d=await fetch('?r=courier_feed&_='+(Date.now()),{cache:'no-store'}).then(r=>r.json());
    if(!d.ok)return;
    if(d.online!==(ONLINE?1:0)){ONLINE=d.online===1;pintarRota()}
    const novos=(d.pendentes||[]).filter(i=>!SEEN.includes(i));
    if((d.aceitas||0)>0)pararAlarme();
    if((d.aceitas||0)===0&&novos.length){
      await notifyBackgroundV11();
      if(estavaFora&&SOUND_READY)toqueCorrida();
      SEEN=[...new Set([...SEEN,...novos])];localStorage.setItem(SEEN_KEY,JSON.stringify(SEEN));
      WAS_HIDDEN=false;
      IDS=d.ids;setTimeout(()=>location.reload(),700);
      return;
    }
    if(d.ids.join(',')!==IDS.join(',')){IDS=d.ids;location.reload()}
  }catch(e){}
}
pintarRota();
setInterval(checar,3000);

async function aceitar(id){pararAlarme();SEEN=[...new Set([...SEEN,id])];localStorage.setItem(SEEN_KEY,JSON.stringify(SEEN));const r=await fetch('?r=courier_aceitar',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+id}).then(r=>r.json());if(r.ok)location.reload()}
async function entregue(id){if(!confirm('Confirmar que o pedido foi realmente entregue ao cliente?'))return;const r=await fetch('?r=courier_delivered',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+id}).then(r=>r.json());if(!r.ok){alert(r.erro||'Não foi possível concluir a entrega.');return}const el=document.getElementById('d'+id);el.classList.add('ok');el.querySelectorAll('button,textarea').forEach(b=>b.disabled=true);el.querySelector('.cbtns').innerHTML='<div class="delivered-msg">✓ Entrega confirmada</div>';setTimeout(()=>location.reload(),1500)}
function ocorrencia(id,motivo){ocoAtual[id]=motivo;document.getElementById('obs'+id).style.display='flex'}
async function confirmarOco(id){const obs=document.getElementById('obst'+id).value.trim();if(!obs){alert('Descreva o que aconteceu.');return}await fetch('?r=courier_ocorrencia',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+id+'&motivo='+encodeURIComponent(ocoAtual[id]||'ocorrência')+'&obs='+encodeURIComponent(obs)});const el=document.getElementById('d'+id);el.style.opacity=.5;el.querySelector('.cbtns').innerHTML='<div class="occurred">⚠ '+(ocoAtual[id]||'ocorrência')+'</div>';document.getElementById('obs'+id).style.display='none'}
</script>
