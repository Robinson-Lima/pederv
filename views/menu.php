<?php $capa=setting_get('menu_capa','');$promo=setting_get('menu_banner','');$brandLogo=setting_get('brand_logo','');$loja=setting_get('store_name','')?: cfg('restaurante');$storeSt=store_status(); ?>
<?php if($storeSt['closed']): ?>
<div class="menu-closed-banner">🔒 <b>Loja fechada no momento</b><span><?= e(store_closed_message()) ?></span></div>
<?php endif; ?>
<div class="mhero <?= $capa?'has-capa':'' ?>">
  <?php if($capa): ?><div class="mhero-capa"><img src="<?= e($capa) ?>" alt="<?= e((setting_get('store_name','')?: cfg('restaurante'))) ?>"><div class="mhero-fade"></div></div><?php endif; ?>
  <div class="mhead">
    <div class="mh">
      <div class="lg"><?php if($brandLogo): ?><img src="<?= e($brandLogo) ?>" alt="<?= e((setting_get('store_name','')?: cfg('restaurante'))) ?>"><?php else: ?><?= e(mb_substr((setting_get('store_name','')?: cfg('restaurante')),0,1)) ?><?php endif; ?></div>
      <div><h2><?= e((setting_get('store_name','')?: cfg('restaurante'))) ?></h2><div class="meta"><span class="op <?= $storeSt['closed']?'closed':'' ?>">● <?= $storeSt['closed']?'Fechada':'Aberto' ?></span> · 30–45 min · ⭐ 4,8</div></div>
      <div class="menu-account"><?php if(!empty($customer)): ?><a class="track-top" href="?r=customer_account">👤 <?= e(explode(' ',$customer['nome'])[0]) ?></a><?php else: ?><a class="track-top" href="?r=customer_login">Entrar</a><?php endif; ?><a class="track-top" href="#" onclick="acompanharPedido();return false">Pedidos</a></div>
    </div>
  </div>
  <?php
    $promoLinhas=array_values(array_filter(array_map('trim',preg_split('/\r\n|\r|\n/',$promo))));
    if($promoLinhas):
      $promoTxt=implode('   •   ',$promoLinhas);
  ?>
  <div class="mpromo"><div class="mpromo-track"><span><?= e($promoTxt) ?></span><span aria-hidden="true"><?= e($promoTxt) ?></span></div></div>
  <?php endif; ?>
</div>
<?php if(!empty($mesa)): ?><div class="table-order-banner">🍽 Pedido vinculado à <b>Mesa <?= e($mesa) ?></b></div><?php endif; ?>
<div class="chips">
  <?php foreach($cats as $i=>$c): ?><button class="chip <?= $i===0?'on':'' ?>" onclick="goCat('cat<?= $c['id'] ?>',this)"><?= e($c['nome']) ?></button><?php endforeach; ?>
</div>
<div class="mlist">
  <?php foreach($cats as $c): if(empty($byCat[$c['id']])) continue; ?>
    <div class="msec" id="cat<?= $c['id'] ?>"><?= e($c['nome']) ?></div>
    <?php foreach($byCat[$c['id']] as $p): ?>
      <div class="prod">
        <div class="pic"><?php if(!empty($p['foto'])): ?><img src="<?= e($p['foto']) ?>" alt=""><?php else: ?><?= e($p['emoji']) ?><?php endif; ?></div>
        <div class="info"><h4><?= e($p['nome']) ?><?php if(($p['tipo']??'produto')==='combo'): ?> <span class="menu-combo">COMBO</span><?php endif; ?></h4><p><?= e($p['descricao']) ?></p>
          <?php if(($p['tipo']??'produto')==='combo'&&!empty($p['combo_itens'])): $ids=json_decode($p['combo_itens'],true)?:[];$names=[];foreach($byCat as $ps)foreach($ps as $item)if(in_array((int)$item['id'],array_map('intval',$ids),true))$names[]=$item['nome'];if($names): ?><small class="combo-contains">Inclui: <?= e(implode(' + ',$names)) ?></small><?php endif; endif; ?>
          <div class="prow"><span class="price"><?= money($p['preco']) ?></span><button class="add" aria-label="Adicionar <?= e($p['nome']) ?>" onclick="addItem(<?= $p['id'] ?>,'<?= e(addslashes($p['nome'])) ?>',<?= $p['preco'] ?>)">+</button></div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endforeach; ?>
</div>

<button class="cartbar hidden" id="cartbar" onclick="openCart()"><span>🛒 Ver sacola</span><span class="mono" id="cartsum">0 itens</span></button>

<div class="sheet" id="sheet" onclick="if(event.target.id==='sheet')closeCart()">
  <div class="sheet-in"><div class="grab"></div>
    <div id="co-view">
      <div style="display:flex;justify-content:space-between;align-items:center"><h3><?= !empty($mesa)?'Pedido da Mesa '.e($mesa):'Sua sacola' ?></h3><button onclick="closeCart()" style="background:none;border:none;font-size:22px;cursor:pointer;padding:8px;color:var(--fg,#333)">&times;</button></div>
      <div id="co-items"></div>
      <div class="field" <?= !empty($mesa)?'style="display:none"':'' ?>><label>Seu nome</label><input id="f-nome" value="<?= !empty($mesa)?'Mesa '.e($mesa):e($customer['nome']??'') ?>" placeholder="Nome" autocomplete="name"></div>
      <div class="field" <?= !empty($mesa)?'style="display:none"':'' ?>><label>Telefone / WhatsApp</label><input id="f-fone" value="<?= e($customer['telefone']??'') ?>" placeholder="(00) 00000-0000" inputmode="tel" autocomplete="tel"></div>
      <div class="field" <?= !empty($mesa)?'style="display:none"':'' ?>><label>E-mail <small>(salva seu histórico)</small></label><input id="f-email" value="<?= e($customer['email']??'') ?>" placeholder="voce@email.com" type="email" autocomplete="email"></div>
      <div class="field" <?= !empty($mesa)?'style="display:none"':'' ?>><label>Tipo</label><select id="f-tipo" onchange="toggleEnd()"><?php if(!empty($mesa)): ?><option value="mesa">Consumir na mesa</option><?php else: ?><option value="entrega">Entrega</option><option value="retirada">Retirada</option><?php endif; ?></select></div>
      <div id="end-wrap" <?= !empty($mesa)?'style="display:none"':'' ?>>
        <div class="field"><label>Endereço (rua e número)</label><input id="f-end" value="<?= e(trim(($customer['endereco']??'').' '.($customer['numero']??''))) ?>" placeholder="Rua, número" autocomplete="street-address"></div>
        <div class="field"><label>Bairro</label><?php if(!empty($areas)): ?><select id="f-bairro" onchange="renderCart()"><option value="">Selecione o bairro…</option><?php foreach($areas as $a): ?><option value="<?= e($a['bairro']) ?>" data-taxa="<?= (float)$a['taxa'] ?>"><?= e($a['bairro']) ?> — <?= money($a['taxa']) ?></option><?php endforeach; ?></select><?php else: ?><input id="f-bairro" placeholder="Bairro"><?php endif; ?></div>
        <div class="field"><label>Ponto de referência (opcional)</label><input id="f-ref" value="<?= e($customer['referencia']??'') ?>" placeholder="Ex: portão azul"></div>
        <?php if(!empty($temZonas)): ?><div class="field frete-check"><button type="button" class="frete-btn" onclick="conferirFrete()">📍 Conferir taxa do meu endereço</button><small id="frete-msg">Informe rua, número e bairro e confira antes de finalizar.</small></div><?php endif; ?>
      </div>
      <?php
        $ipayOn = setting_get('gw_nome','')==='InfinitePay'
               && preg_replace('/[^a-zA-Z0-9_.-]/','',(string)setting_get('gw_infinitepay_handle',''))!=='';
      ?>
      <?php if(empty($mesa)): ?><fieldset class="paychoices"><legend>Quando deseja pagar?</legend>
        <label><input type="radio" name="paywhen" value="agora" onchange="payWhen()"><span><b>Pagar agora</b><small>Pix<?= $ipayOn?' ou cartão':'' ?> na hora</small></span></label>
        <label><input type="radio" name="paywhen" value="entrega" onchange="payWhen()"><span><b>Pagar na entrega</b><small>Acerte ao receber o pedido</small></span></label>

        <div id="pay-now-opts" style="display:none;grid-column:1/-1">
          <small class="paysub">Como você quer pagar agora?</small>
          <div class="payopts">
            <label><input type="radio" name="paynow" value="pix" onchange="payNow()"><span><b>Pix</b><small>QR Code na tela</small></span></label>
            <?php if($ipayOn): ?><label><input type="radio" name="paynow" value="online" onchange="payNow()"><span><b>Cartão</b><small>Débito ou crédito à vista</small></span></label><?php endif; ?>
          </div>
          <div id="paynow-pix-note" style="display:none"><small class="paysub" style="font-weight:400">💠 O QR Code do Pix aparece na tela após confirmar. Pague ou copie o código.</small></div>
          <div id="paynow-card-box" style="display:none">
            <small class="paysub">Débito ou crédito? (à vista, 1x sem juros)</small>
            <div class="payopts">
              <label class="paymini"><input type="radio" name="cartaotipo_online" value="credito" checked><span>Crédito</span></label>
              <label class="paymini"><input type="radio" name="cartaotipo_online" value="debito"><span>Débito</span></label>
            </div>
            <small class="paysub" style="font-weight:400">🔒 Você vai para a página segura da InfinitePay para inserir os dados do cartão. O pedido é liberado assim que o pagamento aprovar.</small>
          </div>
        </div>

        <div id="pay-later-opts" style="display:none;grid-column:1/-1">
          <small class="paysub">Só para o restaurante se preparar — nada é cobrado agora.</small>
          <div class="payopts">
            <label><input type="radio" name="paylater" value="cartao_entrega" onchange="payLater()"><span><b>Cartão</b><small>Maquininha com o entregador</small></span></label>
            <label><input type="radio" name="paylater" value="dinheiro" onchange="payLater()"><span><b>Dinheiro</b><small>Pague ao receber</small></span></label>
          </div>
          <div id="card-tipo" style="display:none">
            <small class="paysub">Débito ou crédito?</small>
            <div class="payopts">
              <label class="paymini"><input type="radio" name="cartaotipo" value="credito" checked><span>Crédito</span></label>
              <label class="paymini"><input type="radio" name="cartaotipo" value="debito"><span>Débito</span></label>
            </div>
          </div>
          <div id="troco-box" style="display:none">
            <label class="paysub" for="f-troco">Se precisar de troco, para quanto? <span style="font-weight:400">(opcional)</span></label>
            <input id="f-troco" inputmode="numeric" oninput="fmtTroco(this)" placeholder="R$ 0,00">
          </div>
        </div>
      </fieldset><?php endif; ?>
      <div class="cototal"><span>Total</span><b id="co-total">R$ 0,00</b></div>
      <button class="btn finish" onclick="finalizar()"><?= !empty($mesa)?'Confirmar pedido da mesa':'Confirmar pedido' ?></button>
    </div>
    <div id="success-view" style="display:none"><div class="success-icon" id="success-icon">✓</div><h3 id="success-title">Pedido confirmado!</h3><p id="success-copy"></p><div id="pix-area" style="display:none" class="qrbox"><div class="amt" id="pix-amt"></div><div id="qrcode"></div><div class="cp"><input id="pix-code" readonly><button onclick="copyPix()">Copiar</button></div><small style="color:#64605c;margin-top:8px;display:block">Pague o Pix acima para confirmar seu pedido. O restaurante será avisado automaticamente.</small></div><a class="btn track" id="track-link">Acompanhar meu pedido</a><button class="btn neworder" onclick="location.reload()">Fazer novo pedido</button></div>
  </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
const cart={}, TAXA=<?= json_encode((float)cfg('taxa_entrega')) ?>, TEM_AREAS=<?= json_encode(!empty($areas)) ?>, TEM_ZONAS=<?= json_encode(!empty($temZonas)) ?>, CIDADE=<?= json_encode(cfg('cidade')) ?>, MESA=<?= json_encode($mesa??'') ?>, IPAY=<?= json_encode(!empty($ipayOn)) ?>, PAY_LABELS={pix:'Pix (QR Code)',pix_entrega:'Pix na entrega',cartao_online:'Cartão online',cartao_entrega:'Cartão na entrega',dinheiro:'Dinheiro na entrega',online:'Cartão online'};
function payWhen(){const v=document.querySelector('input[name="paywhen"]:checked')?.value;const n=document.getElementById('pay-now-opts'),l=document.getElementById('pay-later-opts');if(n)n.style.display=v==='agora'?'block':'none';if(l)l.style.display=v==='entrega'?'block':'none'}
function payNow(){const v=document.querySelector('input[name="paynow"]:checked')?.value;const pn=document.getElementById('paynow-pix-note'),cb=document.getElementById('paynow-card-box');if(pn)pn.style.display=v==='pix'?'block':'none';if(cb)cb.style.display=v==='online'?'block':'none'}
function payLater(){const v=document.querySelector('input[name="paylater"]:checked')?.value;const ct=document.getElementById('card-tipo'),tb=document.getElementById('troco-box');if(ct)ct.style.display=v==='cartao_entrega'?'block':'none';if(tb)tb.style.display=v==='dinheiro'?'block':'none'}
function fmtTroco(el){let d=el.value.replace(/\D/g,'');if(!d){el.value='';return}el.value='R$ '+(parseInt(d,10)/100).toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2})}
const PRODUCT_DATA=<?= json_encode(array_values(array_merge(...array_values($byCat?:[[]]))),JSON_UNESCAPED_UNICODE) ?>;
const RESTORE_CART=<?= json_encode(!empty($restoreCart)?json_decode($restoreCart['itens_json'],true):[],JSON_UNESCAPED_UNICODE) ?>;
const CART_TOKEN=(()=>{let t=localStorage.getItem('rv_cart_token');if(!t){t='c'+Date.now().toString(36)+Math.random().toString(36).slice(2);localStorage.setItem('rv_cart_token',t)}return t})();
let snapshotTimer=null;
let GEO={lat:0,lng:0,taxa:null,zona:'',ok:null};
function freteAtual(){if(document.getElementById('f-tipo').value!=='entrega')return 0;if(GEO.taxa!==null&&GEO.ok)return GEO.taxa;const b=document.getElementById('f-bairro');if(TEM_AREAS&&b?.tagName==='SELECT'){const op=b.options[b.selectedIndex];return op?.dataset.taxa?parseFloat(op.dataset.taxa):0}return TAXA}
function enderecoCompleto(){const end=document.getElementById('f-end').value.trim(),b=document.getElementById('f-bairro');return [end,b?b.value:'',CIDADE].filter(Boolean).join(', ')}
async function geocodificar(){const q=enderecoCompleto();if(!q)return null;try{const r=await fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=br&q='+encodeURIComponent(q)).then(x=>x.json());if(!r.length)return null;return{lat:parseFloat(r[0].lat),lng:parseFloat(r[0].lon)}}catch(e){return null}}
async function conferirFrete(silencioso){
  const msg=document.getElementById('frete-msg');
  if(!document.getElementById('f-end').value.trim()){if(msg)msg.textContent='Digite o endereço primeiro.';return null}
  if(msg)msg.textContent='Consultando a área de entrega…';
  const pt=await geocodificar();
  if(!pt){if(msg)msg.textContent='Não localizamos seu endereço no mapa — a taxa será confirmada pelo restaurante.';GEO={lat:0,lng:0,taxa:null,zona:'',ok:null};return null}
  const b=document.getElementById('f-bairro');
  const r=await fetch(`?r=calc_frete&lat=${pt.lat}&lng=${pt.lng}&bairro=`+encodeURIComponent(b?b.value:'')).then(x=>x.json());
  GEO={lat:pt.lat,lng:pt.lng,taxa:r.taxa,zona:r.zona,ok:r.ok};
  if(msg)msg.textContent=r.ok?`✓ Entregamos aí! Taxa: R$ ${parseFloat(r.taxa).toFixed(2).replace('.',',')}${r.zona?' ('+r.zona+')':''}`:('⛔ '+(r.motivo||'Fora da área de entrega.'));
  renderCart();
  return r;
}
function addItem(id,nome,preco){if(!cart[id])cart[id]={id,nome,preco,qtd:0};cart[id].qtd++;renderCart();scheduleSnapshot()}
function changeQtd(id,delta){if(!cart[id])return;cart[id].qtd+=delta;if(cart[id].qtd<=0)delete cart[id];renderCart();scheduleSnapshot()}
function renderCart(){let n=0,tot=0,html='';Object.values(cart).forEach(c=>{n+=c.qtd;tot+=c.qtd*c.preco;html+=`<div class="coitem"><div style="display:flex;align-items:center;gap:8px"><button onclick="changeQtd(${c.id},-1)" style="width:28px;height:28px;border-radius:50%;border:1px solid #ccc;background:#fff;font-size:16px;cursor:pointer">−</button><span>${c.qtd}</span><button onclick="changeQtd(${c.id},1)" style="width:28px;height:28px;border-radius:50%;border:1px solid #ccc;background:#fff;font-size:16px;cursor:pointer">+</button></div><span style="flex:1;margin-left:8px">${c.nome}</span><b>R$ ${(c.qtd*c.preco).toFixed(2).replace('.',',')}</b></div>`});cartbar.classList.toggle('hidden',n===0);cartsum.textContent=n+' '+(n===1?'item':'itens')+' · R$ '+tot.toFixed(2).replace('.',',');document.getElementById('co-items').innerHTML=html;document.getElementById('co-total').textContent='R$ '+(tot+freteAtual()).toFixed(2).replace('.',',')}
function scheduleSnapshot(){if(MESA)return;clearTimeout(snapshotTimer);snapshotTimer=setTimeout(saveSnapshot,1200)}
async function saveSnapshot(){const itens=Object.values(cart).map(c=>({id:c.id,qtd:c.qtd}));if(!itens.length)return;const total=Object.values(cart).reduce((s,c)=>s+c.qtd*c.preco,0);fetch('?r=cart_snapshot',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({token:CART_TOKEN,itens,total,nome:document.getElementById('f-nome')?.value||'',telefone:document.getElementById('f-fone')?.value||'',email:document.getElementById('f-email')?.value||''})}).catch(()=>{})}
function toggleEnd(){document.getElementById('end-wrap').style.display=document.getElementById('f-tipo').value==='entrega'?'block':'none';renderCart()}
function openCart(){renderCart();document.getElementById('sheet').classList.add('on')}
function closeCart(){document.getElementById('sheet').classList.remove('on')}
async function finalizar(){const itens=Object.values(cart).map(c=>({id:c.id,qtd:c.qtd}));if(!itens.length)return;const tipo=document.getElementById('f-tipo').value,nome=document.getElementById('f-nome').value.trim(),fone=document.getElementById('f-fone').value.trim(),end=document.getElementById('f-end').value.trim(),b=document.getElementById('f-bairro');const paywhen=document.querySelector('input[name="paywhen"]:checked')?.value;if(!paywhen){alert('Escolha quando deseja pagar: agora ou na entrega.');return}let metodo='',cartao_tipo='',troco_para=0;if(paywhen==='agora'){metodo=document.querySelector('input[name="paynow"]:checked')?.value;if(!metodo){alert('Escolha como pagar agora: Pix ou Cartão.');return}if(metodo==='online')cartao_tipo=document.querySelector('input[name="cartaotipo_online"]:checked')?.value||'';}else{metodo=document.querySelector('input[name="paylater"]:checked')?.value;if(!metodo){alert('Escolha a forma de pagamento na entrega.');return}if(metodo==='cartao_entrega')cartao_tipo=document.querySelector('input[name="cartaotipo"]:checked')?.value||'';if(metodo==='dinheiro')troco_para=(parseInt((document.getElementById('f-troco')?.value||'').replace(/\D/g,''),10)||0)/100;}if(!nome||!fone||(tipo==='entrega'&&!end)){alert('Preencha nome, telefone e endereço para continuar.');return}const btn=document.querySelector('.finish');btn.disabled=true;btn.textContent='Enviando…';try{if(tipo==='entrega'&&TEM_ZONAS&&GEO.ok===null){const chk=await conferirFrete(true);if(chk&&!chk.ok){alert(chk.motivo||'Não entregamos nesse endereço.');btn.disabled=false;btn.textContent='Confirmar pedido';return}}
    if(tipo==='entrega'&&GEO.ok===false){alert('Infelizmente não entregamos nesse endereço.');btn.disabled=false;btn.textContent='Confirmar pedido';return}
    const body={itens,nome,fone,email:document.getElementById('f-email')?.value||'',cart_token:CART_TOKEN,tipo,endereco:end,bairro:b?b.value:'',referencia:document.getElementById('f-ref')?.value||'',metodo,cartao_tipo,troco_para,lat:GEO.lat,lng:GEO.lng};const res=await fetch('?r=order_create',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)}).then(r=>r.json());if(!res.ok){alert(res.erro||'Não foi possível enviar o pedido.');btn.disabled=false;btn.textContent='Confirmar pedido';return}localStorage.removeItem('rv_cart_token');localStorage.setItem('rv_last_order',res.order_id);
    if(metodo==='online'){btn.textContent='Abrindo pagamento seguro…';const pay=await fetch('?r=order_checkout_link&id='+res.order_id).then(r=>r.json()).catch(()=>({ok:false}));if(pay.ok&&pay.checkout_url){location.href=pay.checkout_url;return}alert(pay.erro||'Não foi possível abrir o pagamento online. Acompanhe o pedido e combine o pagamento com o restaurante.')}
    document.getElementById('co-view').style.display='none';document.getElementById('success-view').style.display='block';document.querySelector('.sheet-in').scrollTop=0;document.getElementById('track-link').href=res.status_url;
    if(metodo==='pix'){document.getElementById('success-icon').textContent='💠';document.getElementById('success-title').textContent='Pague o Pix para confirmar';document.getElementById('success-copy').textContent=`Pedido ${res.codigo} registrado. Escaneie o QR Code ou copie o código Pix abaixo.`;const pix=await fetch('?r=order_pix&id='+res.order_id).then(r=>r.json());document.getElementById('pix-area').style.display='block';document.getElementById('pix-amt').textContent='R$ '+pix.valor.toFixed(2).replace('.',',');document.getElementById('pix-code').value=pix.payload;const qc=document.getElementById('qrcode');qc.innerHTML='';new QRCode(qc,{text:pix.payload,width:190,height:190})}else{document.getElementById('success-icon').textContent='✓';document.getElementById('success-title').textContent='Pedido confirmado!';document.getElementById('success-copy').textContent=`Pedido ${res.codigo} recebido. Forma de pagamento: ${PAY_LABELS[metodo]}.`}}catch(e){alert('Não foi possível enviar o pedido. Tente novamente.');btn.disabled=false;btn.textContent='Confirmar pedido'}}
function acompanharPedido(){const id=localStorage.getItem('rv_last_order');if(id)location.href='?r=order_status&id='+id;else alert('Você ainda não fez um pedido neste aparelho.')}
// Fluxo exclusivo do QR da mesa: sem cadastro, endereço ou pagamento.
if(MESA){
  freteAtual=()=>0;
  finalizar=async function(){
    const itens=Object.values(cart).map(c=>({id:c.id,qtd:c.qtd}));
    if(!itens.length)return;
    const btn=document.querySelector('.finish');
    btn.disabled=true;btn.textContent='Enviando para a cozinha…';
    try{
      const res=await fetch('?r=order_create',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({
        itens,nome:'Mesa '+MESA,fone:'',tipo:'mesa',mesa:MESA,endereco:'',bairro:'',referencia:'',metodo:'mesa',lat:0,lng:0
      })}).then(r=>r.json());
      if(!res.ok)throw new Error(res.erro||'Não foi possível enviar.');
      localStorage.setItem('rv_last_order',res.order_id);
      document.getElementById('co-view').style.display='none';
      document.getElementById('success-view').style.display='block';
      document.getElementById('track-link').href=res.status_url;
      document.getElementById('success-copy').textContent=`Pedido ${res.codigo} da Mesa ${MESA} enviado para a cozinha.`;
    }catch(e){
      alert(e.message||'Não foi possível enviar o pedido. Tente novamente.');
      btn.disabled=false;btn.textContent='Confirmar pedido da mesa';
    }
  };
}
function goCat(id,el){document.querySelectorAll('.chip').forEach(c=>c.classList.remove('on'));el.classList.add('on');document.getElementById(id)?.scrollIntoView({behavior:'smooth',block:'start'})}
function copyPix(){navigator.clipboard?navigator.clipboard.writeText(document.getElementById('pix-code').value):(document.getElementById('pix-code').select(),document.execCommand('copy'))}
['f-nome','f-fone','f-email'].forEach(id=>document.getElementById(id)?.addEventListener('input',scheduleSnapshot));
if(Array.isArray(RESTORE_CART)&&RESTORE_CART.length){RESTORE_CART.forEach(i=>{const p=PRODUCT_DATA.find(x=>Number(x.id)===Number(i.id));if(p)cart[p.id]={id:Number(p.id),nome:p.nome,preco:Number(p.preco),qtd:Math.max(1,Number(i.qtd)||1)}});renderCart()}
</script>
