<?php
// Agrupa produtos por categoria para exibição organizada
$byCat=[]; foreach($prods as $p) $byCat[$p['category_id']][]=$p;
?>
<div class="wrap pdv-page">
  <div class="page-title-row"><div><h2>Frente de Caixa · Balcão (PDV)</h2><p>Monte a comanda, cobre o cliente e a venda é lançada no caixa e enviada à cozinha.</p></div>
    <?php if($cx): ?><div class="pdv-cx-badge open">▣ Caixa aberto<?= $cx['operador']?' · '.e($cx['operador']):'' ?><small>desde <?= e(substr($cx['aberto_em'],11,5)) ?></small></div>
    <?php else: ?><div class="pdv-cx-badge closed">⚠ Caixa fechado<a href="?r=admin_caixa">Abrir caixa →</a></div><?php endif; ?>
  </div>
  <?php if(!$cx): ?><div class="pdv-cx-warn">O caixa está fechado. Você pode montar a comanda, mas para <b>cobrar e enviar à cozinha</b> é preciso <a href="?r=admin_caixa">abrir o caixa</a> primeiro.</div><?php endif; ?>

  <div class="pdv-hotkeys">
    <span><b>F10</b> Finalizar venda</span>
    <span><b>F9</b> Última venda</span>
    <span><b>F8</b> Cancelar venda <small>(senha do admin)</small></span>
    <span class="sep"></span>
    <span>No pagamento: <b>1</b> Dinheiro · <b>2</b> Pix · <b>3</b> Débito · <b>4</b> Crédito · <b>Enter</b> confirma</span>
  </div>

  <div class="pdv-layout">
    <section>
      <div class="pdv-search"><input id="psearch" placeholder="⌕ Buscar produto" oninput="filterProducts()"></div>
      <div class="pdv-cats" id="pdvCats">
        <button class="pdv-cat on" data-cat="all" onclick="pickCat('all',this)">Todos</button>
        <?php foreach($cats as $c): if(empty($byCat[$c['id']])) continue; ?>
          <button class="pdv-cat" data-cat="c<?= $c['id'] ?>" onclick="pickCat('c<?= $c['id'] ?>',this)"><?= e($c['nome']) ?></button>
        <?php endforeach; ?>
      </div>
      <div class="pdv-catalog">
        <?php foreach($cats as $c): if(empty($byCat[$c['id']])) continue; ?>
          <div class="pdv-cat-sec" data-sec="c<?= $c['id'] ?>">
            <div class="pdv-cat-title"><?= e($c['nome']) ?><i></i></div>
            <div class="pdv-products">
              <?php foreach($byCat[$c['id']] as $p): ?>
                <button class="pdv-product" data-name="<?= e(mb_strtolower($p['nome'])) ?>" onclick='addP(<?= json_encode(['id'=>$p['id'],'nome'=>$p['nome'],'preco'=>$p['preco']]) ?>)'><span><?= e($p['emoji']) ?></span><b><?= e($p['nome']) ?></b><small><?= money($p['preco']) ?></small></button>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
    <aside class="pdv-cart">
      <h3>Nova comanda</h3>
      <input id="pname" placeholder="Nome do cliente" required>
      <input id="pphone" placeholder="Telefone (opcional)">
      <div id="pitems" class="pdv-items"><p>Nenhum item adicionado.</p></div>
      <div class="pdv-total"><span>Total</span><b id="ptotal">R$ 0,00</b></div>
      <button class="pdv-finish" id="btnCobrar" onclick="openCharge()" <?= $cx?'':'disabled' ?>><?= $cx?'💳 COBRAR CLIENTE — F10':'CAIXA FECHADO — ABRA O CAIXA' ?></button>
    </aside>
  </div>
</div>

<!-- Modal de cobrança -->
<div class="pdv-modal" id="chargeModal">
  <div class="pdv-modal-in">
    <div class="pdv-modal-head"><b>Receber pagamento</b><button onclick="closeCharge()">×</button></div>
    <div class="pdv-charge-total"><span>Total a cobrar</span><b id="chargeTotal">R$ 0,00</b></div>
    <div class="pdv-pay-hint">1️⃣ Escolha a forma de pagamento (clique ou tecle <b>1–4</b>)</div>
    <div class="pdv-pay">
      <label><input type="radio" name="pmet" value="dinheiro" checked onchange="payChanged(true)"><span><kbd>1</kbd> 💵 Dinheiro</span></label>
      <label><input type="radio" name="pmet" value="pix" onchange="payChanged(true)"><span><kbd>2</kbd> ◆ Pix</span></label>
      <label><input type="radio" name="pmet" value="debito" onchange="payChanged(true)"><span><kbd>3</kbd> ▣ Débito</span></label>
      <label><input type="radio" name="pmet" value="credito" onchange="payChanged(true)"><span><kbd>4</kbd> ▣ Crédito</span></label>
    </div>
    <div id="cashFields">
      <label class="pdv-cash-label">Valor recebido do cliente
        <input id="pRecebido" type="number" step="0.01" min="0" inputmode="decimal" placeholder="0,00" oninput="calcTroco()">
      </label>
      <div class="pdv-cash-quick" id="quickCash"></div>
      <div class="pdv-troco" id="trocoBox"><span>Troco</span><b id="pTroco">R$ 0,00</b></div>
    </div>
    <div class="pdv-modal-err" id="chargeErr" hidden></div>
    <button class="pdv-finish confirm" id="btnConfirm" onclick="finishPdv()">✔ CONFIRMAR PAGAMENTO — F10 / Enter</button>
  </div>
</div>

<!-- Modal última venda (F9) -->
<div class="pdv-modal" id="lastModal">
  <div class="pdv-modal-in">
    <div class="pdv-modal-head"><b>Última venda do PDV</b><button onclick="lastModal.classList.remove('on')">×</button></div>
    <div id="lastBody" class="pdv-last-body"></div>
  </div>
</div>

<!-- Modal de sucesso -->
<div class="pdv-modal" id="doneModal">
  <div class="pdv-modal-in done">
    <div class="pdv-done-icon">✓</div>
    <h3>Venda registrada no caixa</h3>
    <p id="doneCopy"></p>
    <div class="pdv-troco big" id="doneTroco" hidden><span>TROCO A DEVOLVER</span><b id="doneTrocoV"></b></div>
    <button class="pdv-finish" onclick="location.reload()">＋ Nova venda</button>
  </div>
</div>

<script>
const pcart={};let TOTAL=0;
const CAIXA_OK=<?= $cx?'true':'false' ?>;
const fmt=v=>'R$ '+(+v).toFixed(2).replace('.',',');
const PAYK={'1':'dinheiro','2':'pix','3':'debito','4':'credito'};
const PAYL={dinheiro:'Dinheiro',pix:'Pix',debito:'Débito',credito:'Crédito'};

function addP(p){if(!pcart[p.id])pcart[p.id]={...p,qtd:0};pcart[p.id].qtd++;renderP()}
function changeP(id,n){pcart[id].qtd+=n;if(pcart[id].qtd<=0)delete pcart[id];renderP()}
function renderP(){let h='';TOTAL=0;Object.values(pcart).forEach(p=>{TOTAL+=p.qtd*p.preco;h+=`<div><span>${p.qtd}× ${p.nome}</span><b>${fmt(p.qtd*p.preco)}</b><button onclick="changeP(${p.id},-1)">−</button></div>`});pitems.innerHTML=h||'<p>Nenhum item adicionado.</p>';ptotal.textContent=fmt(TOTAL)}

/* Categorias + busca */
let CAT='all';
function pickCat(c,btn){CAT=c;document.querySelectorAll('.pdv-cat').forEach(x=>x.classList.remove('on'));btn.classList.add('on');applyFilter()}
function filterProducts(){applyFilter()}
function applyFilter(){
  const s=psearch.value.toLowerCase();
  document.querySelectorAll('.pdv-cat-sec').forEach(sec=>{
    const catOk=CAT==='all'||sec.dataset.sec===CAT;let any=false;
    sec.querySelectorAll('.pdv-product').forEach(x=>{const ok=catOk&&x.dataset.name.includes(s);x.hidden=!ok;if(ok)any=true});
    sec.hidden=!any;
  });
}

/* Cobrança */
function openCharge(){
  if(!CAIXA_OK)return;
  if(!pname.value.trim()){pname.focus();return alert('Informe o nome do cliente.')}
  if(!Object.keys(pcart).length)return alert('Adicione produtos à comanda.');
  chargeTotal.textContent=fmt(TOTAL);pRecebido.value='';pTroco.textContent=fmt(0);chargeErr.hidden=true;trocoBox.classList.remove('neg');
  const notas=[...new Set([Math.ceil(TOTAL),Math.ceil(TOTAL/5)*5,Math.ceil(TOTAL/10)*10,Math.ceil(TOTAL/50)*50,Math.ceil(TOTAL/100)*100])].filter(v=>v>=TOTAL).slice(0,4);
  quickCash.innerHTML=notas.map(v=>`<button type="button" onclick="pRecebido.value=${v};calcTroco();pRecebido.focus()">${fmt(v)}</button>`).join('');
  chargeModal.classList.add('on');payChanged(false);
  if(document.activeElement&&document.activeElement.blur)document.activeElement.blur();
}
function closeCharge(){chargeModal.classList.remove('on')}
function payChanged(focus){
  const din=document.querySelector('[name=pmet]:checked').value==='dinheiro';
  cashFields.style.display=din?'block':'none';calcTroco();
  // Só move o foco para o valor DEPOIS que a forma de pagamento foi escolhida
  if(focus){if(din){setTimeout(()=>pRecebido.focus(),40)}else{setTimeout(()=>btnConfirm.focus(),40)}}
}
function calcTroco(){const r=parseFloat(pRecebido.value)||0;pTroco.textContent=fmt(Math.max(0,r-TOTAL));trocoBox.classList.toggle('neg',r>0&&r<TOTAL)}
async function finishPdv(){
  const met=document.querySelector('[name=pmet]:checked').value;
  const recebido=met==='dinheiro'?(parseFloat(pRecebido.value)||0):TOTAL;
  if(met==='dinheiro'&&recebido<TOTAL){chargeErr.textContent='Valor recebido menor que o total.';chargeErr.hidden=false;pRecebido.focus();return}
  btnConfirm.disabled=true;btnConfirm.textContent='Registrando…';
  const r=await fetch('?r=pdv_create',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({nome:pname.value.trim(),fone:pphone.value,itens:Object.values(pcart),metodo:met,valor_recebido:recebido})}).then(x=>x.json()).catch(()=>({ok:false}));
  btnConfirm.disabled=false;btnConfirm.textContent='✔ CONFIRMAR PAGAMENTO — F10 / Enter';
  if(!r.ok){
    if(r.erro==='caixa_fechado')chargeErr.innerHTML='O caixa está fechado. <a href="?r=admin_caixa">Abrir caixa →</a>';
    else chargeErr.textContent=r.erro||'Não foi possível registrar a venda.';
    chargeErr.hidden=false;return;
  }
  closeCharge();
  doneCopy.textContent=`Comanda ${r.codigo||''} paga (${PAYL[met]||met}) e enviada para a cozinha. Venda lançada no caixa.`;
  const t=r.troco||0;doneTroco.hidden=t<=0;doneTrocoV.textContent=fmt(t);
  doneModal.classList.add('on');
}

/* F9 — última venda */
async function showLast(){
  const r=await fetch('?r=pdv_last').then(x=>x.json()).catch(()=>({ok:false}));
  if(!r.ok){lastBody.innerHTML=`<p class="pdv-last-empty">${r.erro||'Não foi possível consultar.'}</p>`}
  else{
    const v=r.venda;
    lastBody.innerHTML=`
      <div class="pdv-last-head"><b>${v.codigo}</b><span>${(v.criado_em||'').substring(11,16)} · ${v.cliente_nome||''}</span></div>
      <div class="pdv-last-items">${(r.itens||[]).map(i=>`<div><span>${i.qtd}× ${i.nome}</span><b>${fmt(i.qtd*i.preco)}</b></div>`).join('')}</div>
      <div class="pdv-last-line"><span>Total</span><b>${fmt(v.total)}</b></div>
      <div class="pdv-last-line"><span>Pagamento</span><b>${PAYL[v.pagamento_metodo]||v.pagamento_metodo}</b></div>
      ${+v.troco>0?`<div class="pdv-last-line"><span>Recebido / Troco</span><b>${fmt(v.valor_recebido)} · troco ${fmt(v.troco)}</b></div>`:''}
      ${v.recebido_por?`<div class="pdv-last-line"><span>Operador</span><b>${v.recebido_por}</b></div>`:''}`;
  }
  lastModal.classList.add('on');
}

/* F8 — cancelar venda em andamento (exige senha do administrador) */
async function cancelSale(){
  if(!Object.keys(pcart).length)return alert('Nenhuma venda em andamento para cancelar.');
  const senha=prompt('CANCELAR VENDA\nInforme a senha do administrador:');
  if(!senha)return;
  const r=await fetch('?r=pdv_admin_check',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'senha='+encodeURIComponent(senha)}).then(x=>x.json()).catch(()=>({ok:false}));
  if(!r.ok)return alert('Senha do administrador incorreta. A venda não foi cancelada.');
  Object.keys(pcart).forEach(k=>delete pcart[k]);pname.value='';pphone.value='';renderP();closeCharge();
  alert('Venda cancelada.');
}

/* Teclas de atalho */
document.addEventListener('keydown',e=>{
  if(e.key==='F10'){e.preventDefault();if(doneModal.classList.contains('on'))return location.reload();chargeModal.classList.contains('on')?finishPdv():openCharge();return}
  if(e.key==='F9'){e.preventDefault();showLast();return}
  if(e.key==='F8'){e.preventDefault();cancelSale();return}
  if(e.key==='Escape'){chargeModal.classList.remove('on');lastModal.classList.remove('on');return}
  if(chargeModal.classList.contains('on')){
    const inValor=document.activeElement===pRecebido;
    if(PAYK[e.key]&&!inValor){
      e.preventDefault();
      const el=document.querySelector(`[name=pmet][value=${PAYK[e.key]}]`);el.checked=true;payChanged(true);
    } else if(e.key==='Enter'){e.preventDefault();finishPdv()}
  }
});
</script>
