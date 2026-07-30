<div class="wrap dash dash-compact">
  <div class="orders-topbar">
    <div class="ot-title"><h2>Meus pedidos</h2><div class="updated">Atualizado: <b id="s-ts">--:--</b></div></div>
    <div class="orders-filters">
      <button class="order-filter on" data-filter="all">Todos</button>
      <button class="order-filter" data-filter="delivery">🛵 Entregas</button>
      <button class="order-filter" data-filter="local">▦ Mesas / balcão</button>
      <button class="order-filter" data-filter="ifood">iFood</button>
      <div class="orders-autobar" id="autoBar"></div>
    </div>
    <div class="ifood-add"><a href="?r=admin_ifood_add">＋ Adicionar iFood</a></div>
  </div>
  <div class="orders-subbar">
    <input id="orderSearch" placeholder="Buscar cliente ou número da comanda">
  </div>
  <div class="stats stats-etapas">
    <div class="stat"><div class="l">🔵 Novos</div><div class="v" id="c-novo">0</div></div>
    <div class="stat"><div class="l">🟠 Em preparo</div><div class="v" id="c-prep">0</div></div>
    <div class="stat ready-stat" id="readyStat" hidden><div class="l">🟢 Pedidos prontos</div><div class="v" id="c-ready">0</div></div>
    <div class="stat"><div class="l">🟣 Despachados</div><div class="v" id="c-saiu">0</div></div>
    <div class="stat"><div class="l">🟢 Finalizados</div><div class="v" id="c-ok">0</div></div>
    <div class="stat acerto-stat" id="acertoStat" hidden><div class="l">🔴 Acerto pendente</div><div class="v" id="c-acerto">0</div><small id="c-acerto-v"></small></div>
  </div>
  <div class="kan kan-big" id="kan"></div>
</div>
<script>
const COURIERS=<?= json_encode($couriers) ?>,
COLS=[['novo','🔵 Novos'],['em_preparo','🟠 Em preparo'],['saiu_entrega','🟣 Despachados'],['concluido','🟢 Finalizados']],
PAYMENTS={pix:'Pix online',pix_entrega:'Pix na entrega',cartao_online:'Cartão online',cartao_entrega:'Cartão na entrega',dinheiro:'Dinheiro',ifood:'Pago no iFood',debito:'Débito',credito:'Crédito',mesa:'Pagar no caixa'};
let AUTO=<?= !empty($autoAccept)?'true':'false' ?>,AUTO_IFOOD=<?= !empty($autoAcceptIfood)?'true':'false' ?>,FILTER='<?= ($_GET['origem']??'')==='ifood'?'ifood':'all' ?>',QUERY='',CANCEL_MODE=<?= json_encode($cancelMode??'none') ?>,SKIP_KDS=<?= !empty($skipKds)?'true':'false' ?>;
let readySeen=JSON.parse(localStorage.getItem('rv_ready_seen')||'[]'),firstFeed=true;
function colOf(s){return ['entregue','concluido','cancelado'].includes(s)?'concluido':(['aceito','em_preparo','pronto'].includes(s)?'em_preparo':(s==='ocorrencia'?'saiu_entrega':s))}
function origin(o){if(o.tipo==='mesa')return 'Mesa '+(o.mesa||'');if(o.canal==='ifood')return 'iFood · '+(o.tipo==='retirada'?'retirada':'entrega');if(o.tipo==='balcao')return 'Balcão';return o.tipo==='retirada'?'Retirada':'Entrega'}
function source(o){if(o.canal==='ifood')return '<span class="source-badge ifood">iFood</span>'+(parseInt(o.simulacao)?'<span class="source-badge simulation">SIMULAÇÃO</span>':'');if(o.tipo==='mesa')return '<span class="source-badge mesa">SALÃO</span>';if(o.tipo==='balcao')return '<span class="source-badge">PDV</span>';return '<span class="source-badge">CARDÁPIO</span>'}
function kitchenStatus(o){return o.status==='aceito'?'<span class="kstatus pending">PENDENTE · aguardando cozinha</span>':o.status==='em_preparo'?'<span class="kstatus cooking">EM PREPARO</span>':o.status==='pronto'?'<span class="kstatus ready">PRONTO 🔔 · despache agora</span>':''}
function rank(o){if(o.acerto_status==='pendente')return -1;return o.status==='pronto'?0:o.status==='em_preparo'?1:o.status==='aceito'?2:3}
function sortCol(list){return list.sort((a,b)=>rank(a)-rank(b)||b.id-a.id)}
function matches(o){const local=['mesa','balcao','retirada'].includes(o.tipo);if(FILTER==='delivery'&&local)return false;if(FILTER==='local'&&!local)return false;if(FILTER==='ifood'&&o.canal!=='ifood')return false;if(!QUERY)return true;return ((o.cliente_nome||'')+' '+(o.codigo||'')+' '+(o.itens||'')).toLowerCase().includes(QUERY)}
function courierOptions(o){const coleta=o.canal==='ifood'?'Pronto para coleta (entregador iFood)':'Pronto para coleta (cliente/parceiro)';return '<option value="">Escolher motoboy…</option>'+COURIERS.map(c=>`<option value="${c.id}" ${c.online?'':'disabled'}>${c.nome}${c.online?' · online':' (offline)'}</option>`).join('')+`<option value="coleta">${coleta}</option>`}
function motoTag(o){const fim=['entregue','concluido'].includes(o.status);if(!o.cnome)return o.status==='saiu_entrega'?'<div class="moto-tag coleta">📦 Pronto para coleta</div>':'';return `<div class="moto-tag ${fim?'done':(o.aceito?'accepted':'pending')}">🛵 ${o.cnome} · ${fim?'ENTREGA CONCLUÍDA ✓':(o.aceito?'A CAMINHO':'ACEITE PENDENTE')}</div>`}
function beepReady(ids){const fresh=ids.filter(id=>!readySeen.includes(id));if(!firstFeed&&fresh.length){try{const a=new AudioContext(),o=a.createOscillator();o.frequency.value=880;o.connect(a.destination);o.start();o.stop(a.currentTime+.35)}catch(e){}if('Notification'in window&&Notification.permission==='granted')new Notification('Pedido pronto',{body:'A cozinha finalizou um pedido. Faça o despacho.'})}readySeen=[...new Set([...readySeen,...ids])];localStorage.setItem('rv_ready_seen',JSON.stringify(readySeen));firstFeed=false}
function autoBox(){return `<div class="auto-box"><label class="auto-accept"><input type="checkbox" ${AUTO?'checked':''} onchange="toggleAuto('orders',this.checked)"><span></span><b>Aceitar pedidos automaticamente</b></label><label class="auto-accept"><input type="checkbox" ${AUTO_IFOOD?'checked':''} onchange="toggleAuto('ifood',this.checked)"><span></span><b>Aceitar automaticamente os do iFood</b></label><small>Desmarcado = o pedido aguarda a decisão do operador.</small></div>`}
document.getElementById('autoBar').innerHTML=autoBox();
function contact(o){if(o.tipo!=='entrega')return'';const linhas=[];if(o.cliente_fone)linhas.push(`📞 ${o.cliente_fone}`);const end=[o.endereco,o.bairro].filter(Boolean).join(' — ');if(end)linhas.push(`📍 ${end}`);return linhas.length?`<div class="ord-contact">${linhas.join('<br>')}</div>`:''}
function canEditPayment(o){return ['novo','aceito','em_preparo','pronto'].includes(o.status)}
function onlinePending(o){return ['pix','cartao_online'].includes(o.pagamento_metodo)&&o.pagamento_status!=='pago'}
function paymentBadge(o){
  const label=PAYMENTS[o.pagamento_metodo]||o.pagamento_metodo||'Pagamento';
  const text=o.pagamento_status==='pago'?`PAGO · ${label}`:(onlinePending(o)?`${label} · AGUARDANDO`:`${label} · RECEBER`);
  const click=canEditPayment(o)?` onclick="togglePayment(${o.id})" title="Editar pagamento ou marcar como pago"`:'';
  return `<span class="chp ${o.pagamento_status} ${onlinePending(o)?'online-wait':''} ${canEditPayment(o)?'clickable':''}"${click}>${text}</span>`;
}
function paymentEditor(o){
  if(!canEditPayment(o))return'';
  return `<div class="payedit" id="pe${o.id}"><select id="pm${o.id}">${Object.entries(PAYMENTS).map(([v,l])=>`<option value="${v}" ${o.pagamento_metodo===v?'selected':''}>${l}</option>`).join('')}</select><select id="ps${o.id}"><option value="pendente">Pendente / receber do cliente</option><option value="pago" ${o.pagamento_status==='pago'?'selected':''}>Marcar como PAGO</option></select><button onclick="savePayment(${o.id})">Salvar pagamento</button></div>`;
}
function togglePayment(id){document.getElementById('pe'+id)?.classList.toggle('open')}
function renderOrder(o){
  const locked=onlinePending(o);let action='';
  const podeDespachar=o.status==='pronto'||(SKIP_KDS&&o.status==='aceito');
  if(o.status==='novo') action=`<div class="new-actions"><button class="adv" onclick="setStatus(${o.id},'aceito')">Aceitar pedido</button><button class="reject" onclick="rejectOrder(${o.id})">Recusar</button></div>`;
  else if(podeDespachar&&o.tipo==='entrega') action=`<div class="desp"><select id="cs${o.id}">${courierOptions(o)}</select><button class="${locked?'payment-locked':''}" onclick="despachar(${o.id},${locked?'true':'false'})">${locked?'🔒 Aguardando pagamento':'Despachar →'}</button></div>${locked?'<div class="payment-help">Confirme PAGO na caneta antes de despachar.</div>':''}`;
  else if(podeDespachar) action=`<button class="adv" onclick="setStatus(${o.id},'concluido')">${o.tipo==='mesa'?'Entregue na mesa':'Entregar / concluir'} →</button>`;
  const edit=canEditPayment(o)?`<button class="edit-card" onclick="togglePayment(${o.id})" title="Editar forma de pagamento">✎</button>`:'';
  let acerto='';
  if(o.acerto_status==='pendente'){
    const met=PAYMENTS[o.pagamento_metodo]||o.pagamento_metodo;
    acerto=`<div class="acerto-pend"><b>⚠ AGUARDANDO ACERTO NO CAIXA</b><small>${o.pagamento_metodo==='dinheiro'?'O motoboy precisa entregar o dinheiro no caixa':'O motoboy precisa entregar o comprovante da maquininha'} · ${met}${o.cnome?' · 🛵 '+o.cnome:''}</small><button onclick="confirmAcerto(${o.id})">✔ Confirmar acerto · marcar PAGO</button></div>`;
  } else if(o.acerto_status==='ok'&&['entregue','concluido'].includes(o.status)&&['dinheiro','cartao_entrega','pix_entrega'].includes(o.pagamento_metodo)){
    acerto=`<div class="acerto-ok">✔ Acerto conferido no caixa</div>`;
  }
  return `<div class="tk ${o.status} ${o.acerto_status==='pendente'?'tk-acerto':''}" draggable="true" data-oid="${o.id}" data-status="${o.status}">${edit}${acerto}<div class="id">Comanda ${o.codigo}${source(o)}<span>${origin(o)}</span></div>${kitchenStatus(o)}<div class="nm">${o.cliente_nome}</div><div class="it">${o.itens||''}</div>${contact(o)}${motoTag(o)}<div class="ft">${paymentBadge(o)}<span class="val">R$ ${parseFloat(o.total).toFixed(2).replace('.',',')}</span></div>${paymentEditor(o)}${action}${!['entregue','concluido','cancelado'].includes(o.status)?`<button class="cancel-order" onclick="cancelOrder(${o.id})">Cancelar pedido</button>`:''}</div>`;
}
function isInteracting(){
  const kan=document.getElementById('kan');if(!kan)return false;
  const a=document.activeElement;
  if(a&&kan.contains(a)&&['SELECT','INPUT','BUTTON','OPTION','TEXTAREA'].includes(a.tagName))return true;
  if(kan.querySelector('.payedit.open'))return true;
  return false;
}
async function feed(force){
  if(!force&&isInteracting())return; // não reconstrói o quadro enquanto o operador escolhe motoboy ou edita pagamento
  const d=await fetch('?r=admin_feed&_='+(Date.now()),{cache:'no-store'}).then(r=>r.json());if(!d.ok)return;
  if(!force&&isInteracting())return; // checa de novo após o await (interação pode ter começado durante o fetch)
  const all=d.orders,visible=all.filter(matches),g={novo:[],em_preparo:[],saiu_entrega:[],concluido:[]};visible.forEach(o=>g[colOf(o.status)]?.push(o));Object.keys(g).forEach(k=>sortCol(g[k]));
  const readyIds=all.filter(o=>o.status==='pronto').map(o=>o.id),readyTotal=readyIds.length;beepReady(readyIds);
  document.getElementById('readyStat').hidden=readyTotal===0;document.getElementById('c-ready').textContent=readyTotal;
  const ac=d.acertos||{qtd:0,valor:0};document.getElementById('acertoStat').hidden=ac.qtd===0;document.getElementById('c-acerto').textContent=ac.qtd;document.getElementById('c-acerto-v').textContent=ac.qtd?('R$ '+ac.valor.toFixed(2).replace('.',',')+' a receber dos motoboys'):'';
  ['novo','prep','saiu','ok'].forEach((x,i)=>document.getElementById('c-'+x).textContent=g[COLS[i][0]].length);document.getElementById('s-ts').textContent=d.ts;
  let html='';
  COLS.forEach(([key,label])=>{
    const ready=key==='em_preparo'&&readyTotal?`<span class="ready-inline hot">🟢 ${readyTotal} pronto${readyTotal>1?'s':''}</span>`:'';
    html+=`<div class="kcol" data-col="${key}"><div class="kh"><span>${label}${ready}</span><span class="c">${g[key].length}</span></div>`;
    g[key].forEach(o=>html+=renderOrder(o));
    html+='</div>';
  });
  document.getElementById('kan').innerHTML=html;
}
document.querySelectorAll('.order-filter').forEach(b=>b.onclick=()=>{document.querySelectorAll('.order-filter').forEach(x=>x.classList.remove('on'));b.classList.add('on');FILTER=b.dataset.filter;feed()});
document.querySelector(`.order-filter[data-filter="${FILTER}"]`)?.classList.add('on');if(FILTER!=='all')document.querySelector('.order-filter[data-filter="all"]')?.classList.remove('on');
document.getElementById('orderSearch').oninput=e=>{QUERY=e.target.value.trim().toLowerCase();feed()};
async function toggleAuto(chave,v){if(chave==='ifood')AUTO_IFOOD=v;else AUTO=v;await fetch('?r=admin_toggle_auto_accept',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'chave='+chave+'&enabled='+(v?1:0)});feed(true)}
async function setStatus(id,status){await fetch('?r=admin_set_status',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`id=${id}&status=${status}`});feed(true)}
async function rejectOrder(id){if(confirm('Recusar este pedido?')){await fetch('?r=admin_reject_order',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+id});feed(true)}}
async function cancelOrder(id){if(!confirm('Cancelar este pedido?'))return;let senha='';if(CANCEL_MODE==='master'){senha=prompt('Informe a senha de cancelamento:')||'';if(!senha)return}const r=await fetch('?r=admin_cancel_order',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+id+'&senha='+encodeURIComponent(senha)}).then(x=>x.json());if(!r.ok)alert(r.erro||'Não foi possível cancelar.');feed(true)}
async function confirmAcerto(id){if(!confirm('Confirmar que o motoboy entregou o valor / comprovante no caixa?'))return;const r=await fetch('?r=admin_acerto_pago',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','Accept':'application/json'},body:'id='+id}).then(x=>x.json());if(!r.ok)alert(r.erro||'Não foi possível confirmar.');feed(true)}
async function savePayment(id){const r=await fetch('?r=admin_set_payment',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`id=${id}&metodo=${document.getElementById('pm'+id).value}&pagamento_status=${document.getElementById('ps'+id).value}`}).then(x=>x.json());if(!r.ok)alert(r.erro||'Não foi possível alterar o pagamento.');feed(true)}
async function despachar(id,locked=false){if(locked)return alert('Pagamento online ainda não confirmado. Clique na caneta, marque PAGO e salve antes de despachar.');const cid=document.getElementById('cs'+id).value;if(!cid)return alert('Escolha o motoboy ou marque Pronto para coleta.');const r=await fetch('?r=admin_assign_courier',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','Accept':'application/json'},body:`order_id=${id}&courier_id=${cid}`}).then(x=>x.json()).catch(()=>({ok:true}));if(r&&r.ok===false)alert(r.erro||'Não foi possível despachar.');feed(true)}
if('Notification'in window&&Notification.permission==='default')Notification.requestPermission();feed();setInterval(feed,4000);
// Kanban drag & drop via event delegation (funciona após rebuilds do innerHTML)
(function(){
  const kan=document.getElementById('kan');
  let dragOid=null,dragStatus=null;
  const colStatus={em_preparo:'aceito',concluido_from_saiu:'entregue',concluido_from_prep:'concluido'};
  kan.addEventListener('dragstart',e=>{
    const tk=e.target.closest('[data-oid]');
    if(!tk)return;
    dragOid=tk.dataset.oid; dragStatus=tk.dataset.status;
    e.dataTransfer.effectAllowed='move';
    setTimeout(()=>tk.classList.add('dragging'),0);
  });
  kan.addEventListener('dragend',e=>{
    document.querySelectorAll('.tk.dragging').forEach(el=>el.classList.remove('dragging'));
    document.querySelectorAll('.kcol.drag-over').forEach(el=>el.classList.remove('drag-over'));
  });
  kan.addEventListener('dragover',e=>{
    const col=e.target.closest('[data-col]');
    if(!col||!dragOid)return;
    e.preventDefault(); e.dataTransfer.dropEffect='move';
    document.querySelectorAll('.kcol.drag-over').forEach(el=>el.classList.remove('drag-over'));
    col.classList.add('drag-over');
  });
  kan.addEventListener('dragleave',e=>{
    const col=e.target.closest('[data-col]');
    if(col&&!col.contains(e.relatedTarget))col.classList.remove('drag-over');
  });
  kan.addEventListener('drop',e=>{
    e.preventDefault();
    const col=e.target.closest('[data-col]');
    if(!col||!dragOid)return;
    col.classList.remove('drag-over');
    const target=col.dataset.col;
    let newStatus=null;
    if(target==='em_preparo'&&dragStatus==='novo') newStatus='aceito';
    else if(target==='concluido'&&dragStatus==='saiu_entrega') newStatus='entregue';
    else if(target==='concluido'&&['pronto'].includes(dragStatus)) newStatus='concluido';
    if(newStatus) setStatus(parseInt(dragOid),newStatus);
    dragOid=null; dragStatus=null;
  });
})();
</script>
