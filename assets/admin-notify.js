document.addEventListener('DOMContentLoaded',()=>{
  const dock=document.querySelector('.wa-head-button');if(!dock)return;
  const badge=dock.querySelector('em');
  const key='rv_wa_last_seen';
  async function check(){try{const d=await fetch('?r=whatsapp_feed&_='+(Date.now()),{cache:'no-store'}).then(r=>r.json());if(!d.ok)return;if(Number.isFinite(+d.today))badge.textContent=d.today;if(!d.latest)return;const seen=parseInt(localStorage.getItem(key)||'0',10);if(d.latest.id>seen){dock.classList.add('has-new');if(document.visibilityState==='visible'&&'Notification'in window&&Notification.permission==='granted')new Notification('Nova mensagem no WhatsApp',{body:'Clique no robô do WhatsApp para atender.'});}}catch(e){}}
  dock.addEventListener('click',()=>{fetch('?r=whatsapp_feed').then(r=>r.json()).then(d=>{if(d.latest)localStorage.setItem(key,d.latest.id);dock.classList.remove('has-new')})});
  check();setInterval(check,5000);
});
