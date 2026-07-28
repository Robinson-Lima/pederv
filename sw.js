self.addEventListener('install',event=>{self.skipWaiting()});
self.addEventListener('activate',event=>{event.waitUntil(self.clients.claim())});
self.addEventListener('message',event=>{
  if(!event.data||event.data.type!=='SHOW_DELIVERY')return;
  event.waitUntil(self.registration.showNotification('🛵 Nova entrega para você!',event.data.options||{}));
});
self.addEventListener('push',event=>{
  let data={};try{data=event.data?event.data.json():{}}catch(e){}
  event.waitUntil(self.registration.showNotification(data.title||'🛵 Nova entrega para você!',{
    body:data.body||'Abra o app do motoboy para consultar.',
    tag:data.tag||'rv-nova-entrega-'+Date.now(),
    renotify:true,requireInteraction:true,silent:false,
    icon:'assets/rv-logo.png',badge:'assets/rv-logo.png',
    vibrate:[300,120,300,120,650],
    data:{url:data.url||'?r=courier'}
  }));
});
self.addEventListener('notificationclick',event=>{
  event.notification.close();
  const target=(event.notification.data&&event.notification.data.url)||'?r=courier';
  event.waitUntil(clients.matchAll({type:'window',includeUncontrolled:true}).then(list=>{
    for(const client of list){if('focus'in client){client.navigate(target);return client.focus();}}
    return clients.openWindow(target);
  }));
});
