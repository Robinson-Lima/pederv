<div class="wrap">
  <div class="ifa-head">
    <div>
      <a class="ifa-back" href="?r=admin_orders">← Voltar aos pedidos</a>
      <h2>Adicionar pedido iFood</h2>
      <p>Registre manualmente um pedido recebido pelo app do iFood. Ele entra no quadro marcado como <b>iFood</b> e já pago.</p>
    </div>
    <span class="ifa-badge">iFood</span>
  </div>

  <?php if(!empty($erro)): ?><div class="ifa-erro"><?= e($erro) ?></div><?php endif; ?>

  <form method="post" action="?r=admin_ifood_add" class="ifa-form" id="ifaForm">
    <div class="ifa-grid">
      <section class="ifa-card">
        <h3>Cliente</h3>
        <label>Nome do cliente *<input name="cliente_nome" required placeholder="Ex.: João Silva"></label>
        <label>Telefone / WhatsApp<input name="cliente_fone" placeholder="(00) 00000-0000"></label>

        <h3 style="margin-top:16px">Tipo</h3>
        <div class="ifa-tipo">
          <label><input type="radio" name="tipo" value="entrega" checked onchange="ifaTipo()"><span>🛵 Entrega</span></label>
          <label><input type="radio" name="tipo" value="retirada" onchange="ifaTipo()"><span>🏪 Retirada</span></label>
        </div>

        <div id="ifaEndereco">
          <label>Endereço<input name="endereco" placeholder="Rua, número"></label>
          <div class="ifa-row2">
            <label>Bairro<input name="bairro" placeholder="Bairro"></label>
            <label>Referência<input name="referencia" placeholder="Ponto de referência"></label>
          </div>
          <label>Taxa de entrega (R$)<input name="taxa" value="0,00" inputmode="decimal" oninput="ifaTotal()"></label>
        </div>
      </section>

      <section class="ifa-card">
        <h3>Itens do pedido</h3>
        <div id="ifaItens"></div>
        <button type="button" class="ifa-additem" onclick="ifaAddItem()">＋ Adicionar item</button>

        <div class="ifa-total">
          <span>Total do pedido</span>
          <b id="ifaTotalView">R$ 0,00</b>
        </div>
        <div class="ifa-pay">💳 Forma de pagamento: <b>Pago no iFood</b></div>
        <button class="ifa-submit" type="submit">Registrar pedido iFood</button>
      </section>
    </div>
  </form>
</div>

<script>
const IFA_PRODUCTS=<?= json_encode(array_map(fn($p)=>['nome'=>$p['nome'],'preco'=>(float)$p['preco']],$produtos)) ?>;
function ifaOptions(){return '<option value="">— produto ou digite —</option>'+IFA_PRODUCTS.map(p=>`<option value="${p.nome.replace(/"/g,'&quot;')}" data-preco="${p.preco}">${p.nome} · R$ ${p.preco.toFixed(2).replace('.',',')}</option>`).join('')}
function ifaAddItem(nome='',qtd=1,preco=''){
  const wrap=document.getElementById('ifaItens');
  const row=document.createElement('div');row.className='ifa-item';
  row.innerHTML=`
    <select class="ifa-prod" onchange="ifaPick(this)">${ifaOptions()}</select>
    <input class="ifa-nome" name="item_nome[]" placeholder="Nome do item" value="${nome}" oninput="ifaTotal()">
    <input class="ifa-qtd" name="item_qtd[]" type="number" min="1" value="${qtd}" oninput="ifaTotal()">
    <input class="ifa-preco" name="item_preco[]" placeholder="0,00" inputmode="decimal" value="${preco}" oninput="ifaTotal()">
    <button type="button" class="ifa-del" onclick="this.closest('.ifa-item').remove();ifaTotal()">✕</button>`;
  wrap.appendChild(row);
}
function ifaPick(sel){
  const opt=sel.selectedOptions[0];const row=sel.closest('.ifa-item');
  if(opt&&opt.value){row.querySelector('.ifa-nome').value=opt.value;row.querySelector('.ifa-preco').value=parseFloat(opt.dataset.preco).toFixed(2).replace('.',',');}
  ifaTotal();
}
function parseBR(v){return parseFloat(String(v).replace(/\./g,'').replace(',','.'))||0}
function ifaTotal(){
  let t=0;
  document.querySelectorAll('.ifa-item').forEach(r=>{const q=parseInt(r.querySelector('.ifa-qtd').value)||0;const p=parseBR(r.querySelector('.ifa-preco').value);t+=q*p});
  const tipo=document.querySelector('[name=tipo]:checked').value;
  if(tipo==='entrega')t+=parseBR(document.querySelector('[name=taxa]').value);
  document.getElementById('ifaTotalView').textContent='R$ '+t.toFixed(2).replace('.',',');
}
function ifaTipo(){
  const ent=document.querySelector('[name=tipo]:checked').value==='entrega';
  document.getElementById('ifaEndereco').style.display=ent?'block':'none';
  ifaTotal();
}
ifaAddItem();ifaTipo();
</script>
