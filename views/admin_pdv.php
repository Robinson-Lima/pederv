<?php
$byCat=[]; foreach($prods as $p) $byCat[$p['category_id']][]=$p;
$bairroOpts=[];
if(!empty($areas)){foreach($areas as $a) $bairroOpts[]=['nome'=>$a['bairro'],'taxa'=>(float)$a['taxa'],'tipo'=>'entrega'];}
elseif(!empty($zonas)){foreach($zonas as $z) $bairroOpts[]=['nome'=>$z['nome'],'taxa'=>(float)$z['taxa'],'tipo'=>$z['tipo']];}
$__pdvSlug=current_slug();
?>
<style>
.pdv-anota{font-family:var(--body);background:#F5F6FA;min-height:calc(100vh - 60px)}
.pdv-anota .pwa-install-fab{position:static!important;display:inline-flex!important;padding:7px 12px!important;font-size:11px!important;border-radius:8px!important;box-shadow:none!important;border:1.5px solid #E2E8F2!important;background:#fff!important;color:#5A6480!important;font-weight:700!important}
.pdv-topbar{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;background:#fff;border-bottom:1px solid #E4E9F1;flex-wrap:wrap;gap:10px}
.pdv-topbar-left{display:flex;align-items:center;gap:16px}
.pdv-topbar-left h2{font-size:16px;font-weight:800;margin:0;white-space:nowrap}
.pdv-topbar-right{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.pdv-topbar-btn{padding:7px 12px;border-radius:8px;font-size:11px;font-weight:700;border:1.5px solid #E2E8F2;background:#fff;color:#5A6480;cursor:pointer;display:flex;align-items:center;gap:5px;transition:all .15s}
.pdv-topbar-btn:hover{border-color:#3B82F6;color:#3B82F6}
.pdv-topbar-btn kbd{background:#F0F3F8;border-radius:4px;padding:1px 5px;font-size:10px;font-family:var(--mono)}
.pdv-cx-badge{border-radius:8px;padding:7px 12px;font-size:11px;font-weight:800;display:flex;align-items:center;gap:6px}
.pdv-cx-badge.open{background:#E7F8EF;color:#0E7A46;border:1px solid #BFE9D3}
.pdv-cx-badge.closed{background:#FDEDE9;color:#B4362B;border:1px solid #F0A79A}
.pdv-cx-badge.closed a{color:#B4362B;font-size:10.5px;margin-left:6px}
.pdv-cx-warn{background:#FFF7E8;border:1px solid #F0D9A6;border-radius:10px;padding:12px 16px;font-size:12px;color:#7A5A12;margin:12px 20px 0}
.pdv-cx-warn a{color:#B4362B;font-weight:800}
.pdv-body{display:grid;grid-template-columns:1fr 380px;gap:0;padding:0}
.pdv-left{padding:16px 20px;overflow-y:auto;max-height:calc(100vh - 130px)}
.pdv-left-toolbar{display:flex;align-items:center;gap:10px;margin-bottom:14px}
.pdv-filter-btn{padding:8px 14px;border-radius:8px;font-size:12px;font-weight:700;border:1.5px solid #E2E8F2;background:#fff;color:#5A6480;cursor:pointer;display:flex;align-items:center;gap:5px}
.pdv-filter-btn:hover{border-color:#3B82F6;color:#3B82F6}
.pdv-filter-btn kbd{background:#F0F3F8;border-radius:4px;padding:1px 5px;font-size:10px;font-family:var(--mono)}
.pdv-search-box{flex:1;position:relative}
.pdv-search-box input{width:100%;border:1.5px solid #E2E8F2;border-radius:8px;padding:8px 12px 8px 34px;font-size:13px;background:#fff;transition:border-color .15s}
.pdv-search-box input:focus{border-color:#3B82F6;outline:none}
.pdv-search-box::before{content:"";position:absolute;left:11px;top:50%;transform:translateY(-50%);width:14px;height:14px;border:2px solid #9CA3AF;border-radius:50%;pointer-events:none}
.pdv-search-kbd{position:absolute;right:10px;top:50%;transform:translateY(-50%);display:flex;align-items:center;gap:4px;font-size:10px;font-weight:700;color:#9CA3AF}
.pdv-search-kbd kbd{background:#F0F3F8;border-radius:4px;padding:1px 5px;font-family:var(--mono)}
.pdv-nav-hint{display:flex;align-items:center;gap:12px;font-size:11px;color:#9CA3AF;font-weight:600;margin-bottom:12px}
.pdv-nav-hint span{display:flex;align-items:center;gap:4px}
.pdv-nav-hint kbd{background:#F0F3F8;border-radius:4px;padding:2px 6px;font-size:10px;font-family:var(--mono);color:#5A6480}
.pdv-cats{display:flex;gap:7px;overflow-x:auto;padding:2px 0 12px;margin-bottom:4px}
.pdv-cat{white-space:nowrap;font-size:11.5px;font-weight:700;padding:8px 14px;border-radius:999px;background:#fff;border:1.5px solid #E2E8F2;color:#5A6480;cursor:pointer;transition:all .12s}
.pdv-cat:hover{border-color:#3B82F6;color:#3B82F6}
.pdv-cat.on{background:#3B82F6;color:#fff;border-color:#3B82F6;box-shadow:0 4px 12px rgba(59,130,246,.3)}
.pdv-cat-sec{margin-bottom:8px}
.pdv-cat-sec[hidden]{display:none}
.pdv-cat-title{font-weight:800;font-size:13px;color:#374151;display:flex;align-items:center;gap:10px;margin:10px 2px 9px}
.pdv-cat-title i{flex:1;height:1px;background:linear-gradient(90deg,#DCE2EC,transparent)}
.pdv-products{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px}
.pdv-product{background:#fff;border:1.5px solid #E6EAF2;border-radius:12px;padding:12px;display:flex;flex-direction:column;text-align:left;gap:5px;cursor:pointer;transition:all .12s;min-height:110px}
.pdv-product:hover{border-color:#3B82F6;box-shadow:0 4px 14px rgba(59,130,246,.12);transform:translateY(-1px)}
.pdv-product span{font-size:28px;line-height:1}
.pdv-product b{font-size:12.5px;color:#1F2937;line-height:1.3}
.pdv-product small{color:#3B82F6;font-weight:800;font-size:12px;margin-top:auto}
.pdv-right{background:#fff;border-left:1px solid #E4E9F1;display:flex;flex-direction:column;max-height:calc(100vh - 130px);overflow-y:auto}
.pdv-right-header{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid #F0F3F8}
.pdv-right-header h3{font-size:14px;font-weight:800;margin:0;color:#1F2937}
.pdv-right-header .subtotal-label{font-size:12px;color:#9CA3AF;font-weight:600}
.pdv-empty-cart{flex:1;display:flex;align-items:center;justify-content:center;padding:40px 20px;text-align:center;color:#9CA3AF;font-size:13px;font-weight:500}
.pdv-order-items{flex:1;padding:0 16px;overflow-y:auto}
.pdv-order-item{display:grid;grid-template-columns:auto 1fr auto auto;gap:8px;padding:10px 0;border-bottom:1px solid #F5F6FA;align-items:center;font-size:13px}
.pdv-order-item .item-qty{background:#F0F3F8;border-radius:6px;padding:3px 8px;font-weight:800;font-size:12px;color:#374151;min-width:28px;text-align:center}
.pdv-order-item .item-name{color:#1F2937;font-weight:600}
.pdv-order-item .item-price{font-family:var(--mono);font-weight:700;color:#1F2937}
.pdv-order-item .item-remove{width:24px;height:24px;border-radius:6px;background:#FEE2E2;color:#DC2626;font-size:14px;display:grid;place-items:center;cursor:pointer;border:none}
.pdv-order-item .item-remove:hover{background:#DC2626;color:#fff}
.pdv-obs-link{padding:8px 16px;font-size:12px;color:#3B82F6;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:5px;border:none;background:none;text-align:left}
.pdv-obs-link:hover{color:#2563EB}
.pdv-obs-link kbd{background:#F0F3F8;border-radius:4px;padding:1px 5px;font-size:10px;font-family:var(--mono);color:#5A6480}
.pdv-obs-area{padding:0 16px;display:none}
.pdv-obs-area.show{display:block}
.pdv-obs-area textarea{width:100%;border:1.5px solid #E2E8F2;border-radius:8px;padding:8px 10px;font-size:12px;resize:vertical;min-height:50px}
.pdv-summary{padding:12px 16px;border-top:1px solid #F0F3F8}
.pdv-summary-row{display:flex;justify-content:space-between;align-items:center;padding:4px 0;font-size:13px}
.pdv-summary-row.total{font-size:16px;font-weight:800;color:#1F2937;padding:8px 0 4px}
.pdv-summary-row .label{color:#6B7280;font-weight:600}
.pdv-summary-row .value{font-family:var(--mono);font-weight:700}
.pdv-summary-row.total .value{color:#1F2937;font-size:18px}
.pdv-client-fields{padding:8px 16px;display:grid;gap:8px}
.pdv-client-fields input,.pdv-client-fields select{width:100%;border:1.5px solid #E2E8F2;border-radius:8px;padding:9px 12px;font-size:13px;background:#fff;transition:border-color .15s;box-sizing:border-box}
.pdv-client-fields input:focus,.pdv-client-fields select:focus{border-color:#3B82F6;outline:none}
.pdv-client-fields input::placeholder{color:#9CA3AF}
.pdv-client-fields label{font-size:11px;font-weight:700;color:#6B7280;margin-bottom:2px;display:block}
.pdv-actions{padding:8px 16px;display:flex;flex-wrap:wrap;gap:6px}
.pdv-action-btn{padding:7px 12px;border-radius:8px;font-size:11px;font-weight:700;border:1.5px solid #E2E8F2;background:#fff;color:#5A6480;cursor:pointer;display:flex;align-items:center;gap:5px;transition:all .15s;flex:1;justify-content:center;min-width:0}
.pdv-action-btn:hover{border-color:#3B82F6;color:#3B82F6}
.pdv-action-btn kbd{background:#F0F3F8;border-radius:4px;padding:1px 5px;font-size:10px;font-family:var(--mono)}
.pdv-action-btn.active{border-color:#3B82F6;background:#EFF6FF;color:#2563EB}
.pdv-action-btn.cpf-active{border-color:#4CAF50;background:#E8F5E9;color:#2E7D32}
.pdv-action-btn.cpf-active kbd{background:#C8E6C9;color:#2E7D32}
.pdv-cpf-area{padding:0 16px;display:none}
.pdv-cpf-area.show{display:block}
.pdv-cpf-area input{width:100%;border:1.5px solid #4CAF50;border-radius:8px;padding:9px 12px;font-size:13px;background:#F1F8E9}
.pdv-cpf-area input:focus{outline:none;box-shadow:0 0 0 3px rgba(76,175,80,.15)}
.pdv-delivery-area{padding:0 16px;display:none}
.pdv-delivery-area.show{display:block;padding-top:8px}
.pdv-delivery-area .del-grid{display:grid;gap:8px}
.pdv-delivery-area .del-grid2{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.pdv-delivery-area input,.pdv-delivery-area select{width:100%;border:1.5px solid #E2E8F2;border-radius:8px;padding:9px 12px;font-size:13px;background:#fff;box-sizing:border-box}
.pdv-delivery-area input:focus,.pdv-delivery-area select:focus{border-color:#3B82F6;outline:none}
.pdv-delivery-area label{font-size:11px;font-weight:700;color:#6B7280;display:block;margin-bottom:2px}
.pdv-delivery-area .del-taxa{font-size:11px;color:#3B82F6;font-weight:700;margin-top:4px}
.pdv-delivery-area .del-bloqueio{font-size:11px;color:#DC2626;font-weight:700;margin-top:4px}
.pdv-pay-choice{padding:8px 16px;display:none;gap:8px}
.pdv-pay-choice.show{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.pdv-pay-opt{border:1.5px solid #E2E8F2;border-radius:10px;padding:14px;cursor:pointer;text-align:center;transition:all .15s}
.pdv-pay-opt:hover{border-color:#3B82F6}
.pdv-pay-opt.selected{border-color:#3B82F6;background:#EFF6FF}
.pdv-pay-opt b{display:block;font-size:13px;color:#1F2937;margin-bottom:3px}
.pdv-pay-opt small{font-size:11px;color:#6B7280}
.pdv-generate{padding:10px 16px 16px}
.pdv-generate button{width:100%;padding:14px;border-radius:10px;background:#3B82F6;color:#fff;font-size:14px;font-weight:800;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:all .15s;box-shadow:0 4px 14px rgba(59,130,246,.3)}
.pdv-generate button:hover{background:#2563EB;box-shadow:0 6px 20px rgba(59,130,246,.4)}
.pdv-generate button:disabled{background:#D1D5DB;color:#9CA3AF;box-shadow:none;cursor:not-allowed}
.pdv-generate button kbd{background:rgba(255,255,255,.2);border-radius:4px;padding:2px 7px;font-size:11px;font-family:var(--mono)}
.pdv-generate button svg{width:18px;height:18px}
@media(max-width:900px){
  .pdv-body{grid-template-columns:1fr}
  .pdv-right{max-height:none;border-left:none;border-top:1px solid #E4E9F1}
  .pdv-left{max-height:none}
}
</style>

<div class="pdv-anota">
  <div class="pdv-topbar">
    <div class="pdv-topbar-left">
      <h2>Pedidos balcao (PDV)</h2>
    </div>
    <div class="pdv-topbar-right">
      <?php if($cx): ?>
        <div class="pdv-cx-badge open">Caixa aberto<?= $cx['operador']?' - '.e($cx['operador']):'' ?></div>
      <?php else: ?>
        <div class="pdv-cx-badge closed">Caixa fechado <a href="?r=admin_caixa">Abrir &rarr;</a></div>
      <?php endif; ?>
      <button class="pdv-topbar-btn" onclick="switchMode('mesas')"><kbd>M</kbd> Mesas</button>
      <button class="pdv-topbar-btn" onclick="openCharge()"><kbd>F10</kbd> Finalizar</button>
      <button class="pdv-topbar-btn" onclick="showLast()"><kbd>F9</kbd> Ultima venda</button>
      <button class="pdv-topbar-btn" onclick="cancelSale()"><kbd>F8</kbd> Cancelar</button>
    </div>
  </div>

  <?php if(!$cx): ?>
    <div class="pdv-cx-warn">O caixa esta fechado. Voce pode montar a comanda, mas para <b>cobrar e enviar a cozinha</b> e preciso <a href="?r=admin_caixa">abrir o caixa</a> primeiro.</div>
  <?php endif; ?>

  <div class="pdv-body">
    <div class="pdv-left">
      <div class="pdv-left-toolbar">
        <button class="pdv-filter-btn" onclick="document.getElementById('pdvCats').classList.toggle('show')"><kbd>F</kbd> Filtros</button>
        <div class="pdv-search-box">
          <input id="psearch" placeholder="Pesquisar produto..." oninput="filterProducts()">
          <div class="pdv-search-kbd"><kbd>P</kbd> Pesquisar</div>
        </div>
      </div>

      <div class="pdv-nav-hint">
        <span>Navegacao</span>
        <span><kbd>ENTER</kbd> Selecionar item</span>
      </div>

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
                <button class="pdv-product" data-name="<?= e(mb_strtolower($p['nome'])) ?>" onclick='addP(<?= json_encode(['id'=>$p['id'],'nome'=>$p['nome'],'preco'=>$p['preco']]) ?>)'>
                  <span><?= e($p['emoji']) ?></span>
                  <b><?= e($p['nome']) ?></b>
                  <small><?= money($p['preco']) ?></small>
                </button>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="pdv-right">
      <div class="pdv-right-header">
        <div>
          <h3>Itens do pedido</h3>
          <span class="subtotal-label">Subtotal</span>
        </div>
      </div>

      <div id="pitems" class="pdv-order-items">
        <div class="pdv-empty-cart">Finalize o item ao lado, ele vai aparecer aqui</div>
      </div>

      <button class="pdv-obs-link" onclick="toggleObs()"><kbd>O</kbd> Observacao do pedido</button>
      <div class="pdv-obs-area" id="obsArea">
        <textarea id="pobs" placeholder="Ex: sem cebola, bem passado..."></textarea>
      </div>

      <div class="pdv-summary">
        <div class="pdv-summary-row"><span class="label">Subtotal</span><span class="value" id="psubtotal">R$ 0,00</span></div>
        <div class="pdv-summary-row" id="rowEntrega" style="display:none"><span class="label">Entrega</span><span class="value" id="pentrega">R$ 0,00</span></div>
        <div class="pdv-summary-row total"><span class="label">Total</span><span class="value" id="ptotal">R$ 0,00</span></div>
      </div>

      <div class="pdv-client-fields">
        <input id="pphone" placeholder="(XX) X XXXX-XXXX" type="tel">
        <input id="pname" placeholder="Nome do cliente" required>
      </div>

      <div class="pdv-delivery-area" id="deliveryArea">
        <div class="del-grid">
          <div>
            <label>Rua / Avenida</label>
            <input id="pRua" placeholder="Rua / avenida">
          </div>
          <div class="del-grid2">
            <div>
              <label>Bairro</label>
              <select id="pBairro" onchange="onBairroChange()">
                <option value="">Selecione o bairro...</option>
                <?php foreach($bairroOpts as $b): ?>
                  <option value="<?= e($b['nome']) ?>" data-taxa="<?= $b['taxa'] ?>" data-tipo="<?= e($b['tipo']) ?>"><?= e($b['nome']) ?><?= $b['tipo']==='bloqueio'?' (bloqueado)':'' ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label>Numero</label>
              <input id="pNumero" placeholder="N">
            </div>
          </div>
          <div>
            <label>Ponto de referencia (opcional)</label>
            <input id="pRef" placeholder="Ex: portao azul">
          </div>
          <div id="delTaxaInfo"></div>
        </div>
      </div>

      <div class="pdv-pay-choice" id="payChoice">
        <div class="pdv-pay-opt selected" id="optPagarAgora" onclick="selectPayOpt('agora')">
          <b>Pagar agora</b>
          <small>Pix ou cartao no balcao</small>
        </div>
        <div class="pdv-pay-opt" id="optPagarEntrega" onclick="selectPayOpt('entrega')">
          <b>Pagar na entrega</b>
          <small>Acertar ao receber o pedido</small>
        </div>
      </div>

      <div class="pdv-cpf-area" id="cpfArea">
        <input id="pcpfcnpj" placeholder="CPF ou CNPJ" oninput="formatCpfCnpj(this)">
      </div>

      <div class="pdv-actions">
        <button class="pdv-action-btn" id="btnEntrega" onclick="toggleEntrega()"><kbd>E</kbd> Entrega</button>
        <button class="pdv-action-btn" id="btnCpf" onclick="toggleCpf()"><kbd>T</kbd> CPF/CNPJ</button>
        <?php if(setting_get('nf_auto','0')==='1'): ?>
        <label class="pdv-action-btn" style="cursor:pointer;display:flex;align-items:center;gap:6px">
          <input type="checkbox" id="pSemNota" style="width:16px;height:16px"> Sem nota
        </label>
        <?php endif; ?>
      </div>

      <div class="pdv-generate">
        <button id="btnCobrar" onclick="openCharge()" <?= $cx?'':'disabled' ?>>
          <kbd>F10</kbd>
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
          <?= $cx?'Finalizar venda':'CAIXA FECHADO' ?>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal de cobranca -->
<div class="pdv-modal" id="chargeModal">
  <div class="pdv-modal-in">
    <div class="pdv-modal-head"><b>Receber pagamento</b><button onclick="closeCharge()">x</button></div>
    <div class="pdv-charge-total"><span id="chargeLabelTotal">Total a cobrar</span><b id="chargeTotal">R$ 0,00</b></div>
    <div id="splitHistory" style="display:none;padding:0 16px 8px"></div>
    <div class="pdv-pay-hint">Escolha a forma de pagamento (clique ou tecle <b>1-4</b>)</div>
    <div class="pdv-pay">
      <label><input type="radio" name="pmet" value="dinheiro" checked onchange="payChanged(true)"><span><kbd>1</kbd> Dinheiro</span></label>
      <label><input type="radio" name="pmet" value="pix" onchange="payChanged(true)"><span><kbd>2</kbd> Pix</span></label>
      <label><input type="radio" name="pmet" value="debito" onchange="payChanged(true)"><span><kbd>3</kbd> Debito</span></label>
      <label><input type="radio" name="pmet" value="credito" onchange="payChanged(true)"><span><kbd>4</kbd> Credito</span></label>
    </div>
    <div id="cashFields">
      <label class="pdv-cash-label">Valor nesta forma
        <input id="pRecebido" type="number" step="0.01" min="0" inputmode="decimal" placeholder="0,00" oninput="calcTroco()" style="font-size:22px;font-weight:700;padding:12px 14px;letter-spacing:.5px">
      </label>
      <div class="pdv-cash-quick" id="quickCash"></div>
      <div class="pdv-troco" id="trocoBox"><span>Troco</span><b id="pTroco">R$ 0,00</b></div>
    </div>
    <div class="pdv-modal-err" id="chargeErr" hidden></div>
    <button class="pdv-finish confirm" id="btnConfirm" onclick="finishPdv()">CONFIRMAR PAGAMENTO - F10 / Enter</button>
  </div>
</div>

<!-- Modal ultima venda (F9) -->
<div class="pdv-modal" id="lastModal">
  <div class="pdv-modal-in">
    <div class="pdv-modal-head"><b>Ultima venda do PDV</b><button onclick="lastModal.classList.remove('on')">x</button></div>
    <div id="lastBody" class="pdv-last-body"></div>
  </div>
</div>

<!-- Modal PIX QR Code -->
<div class="pdv-modal" id="pixModal">
  <div class="pdv-modal-in">
    <div class="pdv-modal-head"><b>Pagamento via Pix</b><button onclick="pixModal.classList.remove('on')">x</button></div>
    <div class="pdv-charge-total"><span>Total a pagar</span><b id="pixTotal">R$ 0,00</b></div>
    <div style="text-align:center;padding:16px 0">
      <div id="pixQrBox" style="display:inline-block;background:#fff;padding:12px;border-radius:12px;border:2px solid #E2E8F2"></div>
    </div>
    <div style="padding:0 16px 8px;display:flex;gap:8px">
      <input id="pixCopyCode" readonly style="flex:1;border:1.5px solid #E2E8F2;border-radius:8px;padding:9px 12px;font-size:11px;background:#F5F6FA">
      <button onclick="navigator.clipboard.writeText(document.getElementById('pixCopyCode').value);this.textContent='Copiado!';setTimeout(()=>this.textContent='Copiar',2000)" style="padding:9px 16px;border-radius:8px;background:#3B82F6;color:#fff;border:none;font-weight:700;font-size:12px;cursor:pointer">Copiar</button>
    </div>
    <div style="text-align:center;padding:8px 16px;font-size:11px;color:#6B7280">Escaneie o QR Code ou copie o codigo Pix. Apos o pagamento, clique em confirmar.</div>
    <button class="pdv-finish confirm" onclick="confirmPixPaid()">CLIENTE JA PAGOU - CONFIRMAR</button>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
const pcart={};let TOTAL=0;let SUBTOTAL=0;let TAXA_ENTREGA=0;let IS_ENTREGA=false;let PAY_OPT='agora';let LAST_ORDER_ID=0;
const CAIXA_OK=<?= $cx?'true':'false' ?>;
const PDV_SLUG='<?= e($__pdvSlug) ?>';
const PRINT_CFG=<?= json_encode($printCfg??['auto'=>false,'width'=>'80','name'=>'','loja'=>'','cnpj'=>'','endereco'=>'','fone'=>'']) ?>;
const fmt=v=>'R$ '+(+v).toFixed(2).replace('.',',');
const PAYK={'1':'dinheiro','2':'pix','3':'debito','4':'credito'};
const PAYL={dinheiro:'Dinheiro',pix:'Pix',debito:'Debito',credito:'Credito',na_entrega:'Na entrega'};

function printReceipt(data){
  const w=PRINT_CFG.width==='58'?'58mm':'80mm';
  const maxW=PRINT_CFG.width==='58'?'210px':'302px';
  const now=new Date();
  const dt=now.toLocaleDateString('pt-BR')+' '+now.toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'});
  const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
  let itensHtml=data.itens.map(it=>{
    const sub=(it.qtd*it.preco);
    return `<tr><td style="text-align:left">${it.qtd}x ${esc(it.nome)}</td><td style="text-align:right">${fmt(sub)}</td></tr>`;
  }).join('');
  const tipoLabel=data.tipo==='entrega'?'ENTREGA':'BALCAO';
  const pagLabel=PAYL[data.metodo]||data.metodo;
  let enderecoHtml='';
  if(data.tipo==='entrega'&&data.endereco){
    enderecoHtml=`<tr><td colspan="2" style="text-align:left;padding-top:4px;font-size:10px">End: ${esc(data.endereco)}${data.bairro?' - '+esc(data.bairro):''}${data.referencia?'<br>Ref: '+esc(data.referencia):''}</td></tr>`;
  }
  let trocoHtml='';
  if(data.troco>0){
    trocoHtml=`<tr><td>Recebido</td><td style="text-align:right">${fmt(data.recebido)}</td></tr>
    <tr style="font-weight:700;font-size:13px"><td>TROCO</td><td style="text-align:right">${fmt(data.troco)}</td></tr>`;
  }
  let cpfHtml='';
  if(data.cpf_cnpj){cpfHtml=`<tr><td colspan="2" style="text-align:center;font-size:10px">CPF/CNPJ: ${esc(data.cpf_cnpj)}</td></tr>`}
  const html=`<!DOCTYPE html><html><head><meta charset="utf-8"><title>Cupom</title>
<style>
@page{size:${w} auto;margin:0}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Courier New',monospace;font-size:12px;width:${maxW};margin:0 auto;padding:4px 2px}
table{width:100%;border-collapse:collapse}
.sep{border-top:1px dashed #000;margin:5px 0}
.center{text-align:center}
.bold{font-weight:700}
td{padding:2px 0;vertical-align:top}
</style></head><body>
<div class="center bold" style="font-size:14px">${esc(PRINT_CFG.loja||'Restaurante')}</div>
${PRINT_CFG.cnpj?'<div class="center" style="font-size:10px">CNPJ: '+esc(PRINT_CFG.cnpj)+'</div>':''}
${PRINT_CFG.endereco?'<div class="center" style="font-size:10px">'+esc(PRINT_CFG.endereco)+'</div>':''}
${PRINT_CFG.fone?'<div class="center" style="font-size:10px">Tel: '+esc(PRINT_CFG.fone)+'</div>':''}
<div class="sep"></div>
<div class="center bold">${esc(data.codigo)} - ${tipoLabel}</div>
<div class="center" style="font-size:10px">${dt}</div>
<div style="font-size:11px;margin:3px 0">Cliente: ${esc(data.nome)}${data.fone?' - '+esc(data.fone):''}</div>
${enderecoHtml?'<table>'+enderecoHtml+'</table>':''}
<div class="sep"></div>
<table>${itensHtml}</table>
<div class="sep"></div>
<table>
<tr><td>Subtotal</td><td style="text-align:right">${fmt(data.subtotal)}</td></tr>
${data.taxa_entrega>0?'<tr><td>Entrega</td><td style="text-align:right">'+fmt(data.taxa_entrega)+'</td></tr>':''}
<tr class="bold" style="font-size:14px"><td>TOTAL</td><td style="text-align:right">${fmt(data.total)}</td></tr>
<tr><td>Pagamento</td><td style="text-align:right">${pagLabel}</td></tr>
${trocoHtml}
${cpfHtml}
</table>
<div class="sep"></div>
${data.obs?'<div style="font-size:10px">Obs: '+esc(data.obs)+'</div><div class="sep"></div>':''}
<div class="center" style="font-size:10px;margin-top:4px">Obrigado pela preferencia!</div>
<div class="center" style="font-size:9px;color:#999;margin-top:2px">PedeRV - pederv.com.br</div>
<div style="height:20px"></div>
</body></html>`;
  const iframe=document.createElement('iframe');
  iframe.style.cssText='position:fixed;top:-9999px;left:-9999px;width:0;height:0;border:none';
  document.body.appendChild(iframe);
  iframe.contentDocument.open();
  iframe.contentDocument.write(html);
  iframe.contentDocument.close();
  setTimeout(()=>{iframe.contentWindow.print();setTimeout(()=>document.body.removeChild(iframe),2000)},300);
}

// Move install app button to topbar
document.addEventListener('DOMContentLoaded',()=>{
  const fab=document.getElementById('pwaInstallBtn');
  const bar=document.querySelector('.pdv-topbar-right');
  if(fab&&bar){bar.appendChild(fab);fab.hidden=false;}
});

function addP(p){if(!pcart[p.id])pcart[p.id]={...p,qtd:0};pcart[p.id].qtd++;renderP()}
function changeP(id,n){pcart[id].qtd+=n;if(pcart[id].qtd<=0)delete pcart[id];renderP()}
function renderP(){
  let h='';SUBTOTAL=0;
  const items=Object.values(pcart);
  items.forEach(p=>{
    SUBTOTAL+=p.qtd*p.preco;
    h+=`<div class="pdv-order-item">
      <span class="item-qty">${p.qtd}x</span>
      <span class="item-name">${p.nome}</span>
      <span class="item-price">${fmt(p.qtd*p.preco)}</span>
      <button class="item-remove" onclick="changeP(${p.id},-1)">&minus;</button>
    </div>`;
  });
  const el=document.getElementById('pitems');
  if(items.length){el.innerHTML=h}
  else{el.innerHTML='<div class="pdv-empty-cart">Finalize o item ao lado, ele vai aparecer aqui</div>'}
  TOTAL=SUBTOTAL+TAXA_ENTREGA;
  if(TOTAL<0)TOTAL=0;
  document.getElementById('psubtotal').textContent=fmt(SUBTOTAL);
  document.getElementById('ptotal').textContent=fmt(TOTAL);
}

/* M - redireciona para mesas com slug */
function switchMode(mode){
  if(mode==='mesas'){
    const slug=PDV_SLUG;
    window.location.href='?r=admin_tables'+(slug?'&slug='+slug:'');
  }
}

/* Categories + search */
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

function toggleObs(){document.getElementById('obsArea').classList.toggle('show')}

/* Toggle Entrega */
function toggleEntrega(){
  IS_ENTREGA=!IS_ENTREGA;
  document.getElementById('deliveryArea').classList.toggle('show',IS_ENTREGA);
  document.getElementById('btnEntrega').classList.toggle('active',IS_ENTREGA);
  document.getElementById('rowEntrega').style.display=IS_ENTREGA?'flex':'none';
  document.getElementById('payChoice').classList.toggle('show',IS_ENTREGA);
  if(IS_ENTREGA){
    document.getElementById('pRua').focus();
    PAY_OPT='agora';selectPayOpt('agora');
  } else {
    TAXA_ENTREGA=0;
    document.getElementById('pentrega').textContent='R$ 0,00';
    document.getElementById('delTaxaInfo').innerHTML='';
    renderP();
  }
}

function onBairroChange(){
  const sel=document.getElementById('pBairro');
  const opt=sel.options[sel.selectedIndex];
  const info=document.getElementById('delTaxaInfo');
  if(!opt||!opt.value){TAXA_ENTREGA=0;info.innerHTML='';renderP();return}
  const tipo=opt.dataset.tipo||'entrega';
  const taxa=parseFloat(opt.dataset.taxa)||0;
  if(tipo==='bloqueio'){
    TAXA_ENTREGA=0;
    info.innerHTML='<div class="del-bloqueio">Regiao bloqueada para entrega</div>';
  } else {
    TAXA_ENTREGA=taxa;
    info.innerHTML=taxa>0?'<div class="del-taxa">Taxa de entrega: '+fmt(taxa)+'</div>':'<div class="del-taxa">Entrega gratis</div>';
  }
  document.getElementById('pentrega').textContent=TAXA_ENTREGA>0?fmt(TAXA_ENTREGA):'Gratis';
  document.getElementById('rowEntrega').style.display='flex';
  renderP();
}

/* Pay option: agora / entrega */
function selectPayOpt(opt){
  PAY_OPT=opt;
  document.getElementById('optPagarAgora').classList.toggle('selected',opt==='agora');
  document.getElementById('optPagarEntrega').classList.toggle('selected',opt==='entrega');
}

function toggleCpf(){
  const area=document.getElementById('cpfArea');
  const btn=document.getElementById('btnCpf');
  area.classList.toggle('show');
  if(area.classList.contains('show')){
    document.getElementById('pcpfcnpj').focus();
    btn.classList.add('cpf-active');
  } else {
    if(!document.getElementById('pcpfcnpj').value.trim()) btn.classList.remove('cpf-active');
  }
}

function formatCpfCnpj(el){
  let v=el.value.replace(/\D/g,'');
  const btn=document.getElementById('btnCpf');
  if(v.length<=11){
    v=v.replace(/(\d{3})(\d)/,'$1.$2');
    v=v.replace(/(\d{3})(\d)/,'$1.$2');
    v=v.replace(/(\d{3})(\d{1,2})$/,'$1-$2');
  } else {
    v=v.substring(0,14);
    v=v.replace(/^(\d{2})(\d)/,'$1.$2');
    v=v.replace(/^(\d{2})\.(\d{3})(\d)/,'$1.$2.$3');
    v=v.replace(/\.(\d{3})(\d)/,'.$1/$2');
    v=v.replace(/(\d{4})(\d)/,'$1-$2');
  }
  el.value=v;
  btn.classList.toggle('cpf-active',v.trim().length>0);
}

/* Charge — split/partial payment */
let splitPays=[];
let splitRemaining=0;
const METLABEL={dinheiro:'Dinheiro',pix:'Pix',debito:'Débito',credito:'Crédito'};

function openCharge(){
  if(!CAIXA_OK)return;
  if(!pname.value.trim()){pname.focus();return alert('Informe o nome do cliente.')}
  if(!Object.keys(pcart).length)return alert('Adicione produtos a comanda.');
  if(IS_ENTREGA){
    const bairro=document.getElementById('pBairro').value;
    const rua=document.getElementById('pRua').value.trim();
    if(!rua){document.getElementById('pRua').focus();return alert('Informe a rua para entrega.')}
    if(!bairro){document.getElementById('pBairro').focus();return alert('Selecione o bairro.')}
    const opt=document.getElementById('pBairro').options[document.getElementById('pBairro').selectedIndex];
    if(opt&&opt.dataset.tipo==='bloqueio')return alert('Regiao bloqueada para entrega.');
    if(PAY_OPT==='entrega'){finishDeliveryPending();return}
  }
  splitPays=[];splitRemaining=TOTAL;
  chargeTotal.textContent=fmt(TOTAL);
  document.getElementById('chargeLabelTotal').textContent='Total da comanda';
  document.getElementById('splitHistory').style.display='none';
  document.getElementById('splitHistory').innerHTML='';
  pRecebido.value='';pTroco.textContent=fmt(0);chargeErr.hidden=true;trocoBox.classList.remove('neg');
  trocoBox.querySelector('span').textContent='Troco';
  const notas=[...new Set([Math.ceil(TOTAL),Math.ceil(TOTAL/5)*5,Math.ceil(TOTAL/10)*10,Math.ceil(TOTAL/50)*50,Math.ceil(TOTAL/100)*100])].filter(v=>v>=TOTAL).slice(0,4);
  quickCash.innerHTML=notas.map(v=>`<button type="button" onclick="pRecebido.value=${v};calcTroco();pRecebido.focus()">${fmt(v)}</button>`).join('');
  chargeModal.classList.add('on');payChanged(false);
  if(document.activeElement&&document.activeElement.blur)document.activeElement.blur();
}

function renderSplitAfter(){
  const hist=document.getElementById('splitHistory');
  hist.style.display='block';
  hist.innerHTML=splitPays.map(p=>`<div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid #eee;font-size:14px"><span>${METLABEL[p.metodo]||p.metodo}</span><b>${fmt(p.valor)}</b></div>`).join('');
  pRecebido.value='';
  const notas=[...new Set([Math.ceil(splitRemaining),Math.ceil(splitRemaining/5)*5,Math.ceil(splitRemaining/10)*10,Math.ceil(splitRemaining/50)*50,Math.ceil(splitRemaining/100)*100])].filter(v=>v>=splitRemaining&&v>0).slice(0,4);
  quickCash.innerHTML=notas.map(v=>`<button type="button" onclick="pRecebido.value=${v};calcTroco();pRecebido.focus()">${fmt(v)}</button>`).join('');
  calcTroco();
}

function closeCharge(){chargeModal.classList.remove('on')}
function payChanged(focus){
  const met=document.querySelector('[name=pmet]:checked').value;
  const din=met==='dinheiro';
  if(splitPays.length>0){
    cashFields.style.display='block';
    pRecebido.value=splitRemaining.toFixed(2);
  } else if(din){
    cashFields.style.display='block';
    pRecebido.value='';
  } else {
    cashFields.style.display='none';
    pRecebido.value='';
  }
  calcTroco();
  if(focus){setTimeout(()=>(din||splitPays.length)?pRecebido.focus():btnConfirm.focus(),40)}
}
function calcTroco(){
  const r=parseFloat(pRecebido.value)||0;
  if(splitPays.length>0){
    const falta=Math.max(0,splitRemaining-r);
    pTroco.textContent=r>=splitRemaining?fmt(r-splitRemaining):fmt(falta);
    trocoBox.querySelector('span').textContent=r>=splitRemaining?'Troco':'Falta';
    trocoBox.classList.toggle('neg',r>0&&r<splitRemaining);
  } else {
    const falta=Math.max(0,TOTAL-r);
    pTroco.textContent=r>=TOTAL?fmt(r-TOTAL):fmt(falta);
    trocoBox.querySelector('span').textContent=r>=TOTAL?'Troco':'Falta';
    trocoBox.classList.toggle('neg',r>0&&r<TOTAL);
  }
}

/* Pagar na entrega — cria pedido sem cobranca */
async function finishDeliveryPending(){
  const cpf_cnpj=document.getElementById('pcpfcnpj')?document.getElementById('pcpfcnpj').value.trim():'';
  const obs=document.getElementById('pobs')?document.getElementById('pobs').value.trim():'';
  const semNota=document.getElementById('pSemNota')?document.getElementById('pSemNota').checked:false;
  const body={nome:pname.value.trim(),fone:pphone.value,itens:Object.values(pcart),metodo:'na_entrega',valor_recebido:0,cpf_cnpj:cpf_cnpj,obs:obs,pagar_entrega:true,tipo:'entrega',sem_nota:semNota};
  body.endereco=document.getElementById('pRua').value.trim()+(document.getElementById('pNumero').value.trim()?', '+document.getElementById('pNumero').value.trim():'');
  body.bairro=document.getElementById('pBairro').value;
  body.referencia=document.getElementById('pRef').value.trim();
  btnCobrar.disabled=true;btnCobrar.querySelector('svg').style.display='none';
  const oldText=btnCobrar.childNodes[btnCobrar.childNodes.length-1];
  const savedText=oldText.textContent;oldText.textContent=' Registrando...';
  const r=await fetch('?r=pdv_create',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)}).then(x=>x.json()).catch(()=>({ok:false}));
  btnCobrar.disabled=false;btnCobrar.querySelector('svg').style.display='';oldText.textContent=savedText;
  if(!r.ok){alert(r.erro||'Nao foi possivel registrar.');return}
  if(PRINT_CFG.auto){
    printReceipt({codigo:r.codigo,tipo:'entrega',nome:pname.value.trim(),fone:pphone.value,
      itens:Object.values(pcart),subtotal:SUBTOTAL,taxa_entrega:TAXA_ENTREGA,total:TOTAL,
      metodo:'na_entrega',recebido:0,troco:0,cpf_cnpj:cpf_cnpj,obs:obs,
      endereco:body.endereco,bairro:body.bairro,referencia:body.referencia});
  }
  setTimeout(()=>location.reload(),1500);
}

async function finishPdv(){
  const met=document.querySelector('[name=pmet]:checked').value;
  chargeErr.hidden=true;
  const inputVal=parseFloat(pRecebido.value)||0;

  /* --- PIX direto (sem split) --- */
  if(met==='pix'&&splitPays.length===0){closeCharge();openPixPayment();return}

  /* --- Split: valor parcial entra como pagamento parcial --- */
  if(splitPays.length>0||inputVal>0){
    if(inputVal<=0){chargeErr.textContent='Informe o valor.';chargeErr.hidden=false;pRecebido.focus();return}
    if(inputVal<splitRemaining-0.009){
      splitPays.push({metodo:met,valor:inputVal});
      splitRemaining=Math.max(0,splitRemaining-inputVal);
      renderSplitAfter();payChanged(true);return;
    }
    splitPays.push({metodo:met,valor:inputVal});
    splitRemaining=0;
  }
  /* --- Split finaliza com Pix: registra venda e abre QR para valor do pix --- */
  if(met==='pix'&&splitPays.length>1){closeCharge();openPixSplit();return}

  /* --- Montar body --- */
  btnConfirm.disabled=true;btnConfirm.textContent='Registrando...';
  const cpf_cnpj=document.getElementById('pcpfcnpj')?document.getElementById('pcpfcnpj').value.trim():'';
  const obs=document.getElementById('pobs')?document.getElementById('pobs').value.trim():'';
  const semNota=document.getElementById('pSemNota')?document.getElementById('pSemNota').checked:false;

  let body;
  if(splitPays.length>1){
    const totalRecebido=splitPays.reduce((s,p)=>s+p.valor,0);
    body={nome:pname.value.trim(),fone:pphone.value,itens:Object.values(pcart),
      metodo:splitPays[0].metodo,valor_recebido:totalRecebido,
      pagamentos:splitPays,cpf_cnpj:cpf_cnpj,obs:obs,sem_nota:semNota};
  } else {
    const recebido=met==='dinheiro'?inputVal:TOTAL;
    body={nome:pname.value.trim(),fone:pphone.value,itens:Object.values(pcart),
      metodo:met,valor_recebido:recebido,cpf_cnpj:cpf_cnpj,obs:obs,sem_nota:semNota};
  }
  if(IS_ENTREGA){
    body.tipo='entrega';
    body.endereco=document.getElementById('pRua').value.trim()+(document.getElementById('pNumero').value.trim()?', '+document.getElementById('pNumero').value.trim():'');
    body.bairro=document.getElementById('pBairro').value;
    body.referencia=document.getElementById('pRef').value.trim();
  } else {
    body.tipo='balcao';
  }
  const r=await fetch('?r=pdv_create',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)}).then(x=>x.json()).catch(()=>({ok:false}));
  btnConfirm.disabled=false;btnConfirm.textContent='CONFIRMAR PAGAMENTO - F10 / Enter';
  if(!r.ok){
    if(r.erro==='caixa_fechado')chargeErr.innerHTML='O caixa esta fechado. <a href="?r=admin_caixa">Abrir caixa &rarr;</a>';
    else chargeErr.textContent=r.erro||'Nao foi possivel registrar a venda.';
    chargeErr.hidden=false;
    if(splitPays.length){splitPays.pop();splitRemaining=TOTAL-splitPays.reduce((s,p)=>s+p.valor,0);}
    return;
  }
  closeCharge();
  const totalRecebido=splitPays.length?splitPays.reduce((s,p)=>s+p.valor,0):(met==='dinheiro'?inputVal:TOTAL);
  const troco=r.troco||0;
  const metLabel=splitPays.length>1?splitPays.map(p=>(METLABEL[p.metodo]||p.metodo)+' '+fmt(p.valor)).join(' + '):(METLABEL[met]||met);
  if(PRINT_CFG.auto){
    printReceipt({codigo:r.codigo,tipo:IS_ENTREGA?'entrega':'balcao',nome:pname.value.trim(),fone:pphone.value,
      itens:Object.values(pcart),subtotal:SUBTOTAL,taxa_entrega:TAXA_ENTREGA,total:TOTAL,
      metodo:metLabel,recebido:totalRecebido,troco:troco,cpf_cnpj:cpf_cnpj,obs:obs,
      endereco:IS_ENTREGA?(document.getElementById('pRua').value.trim()+(document.getElementById('pNumero').value.trim()?', '+document.getElementById('pNumero').value.trim():'')):'',
      bairro:IS_ENTREGA?document.getElementById('pBairro').value:'',
      referencia:IS_ENTREGA?document.getElementById('pRef').value.trim():''});
  }
  setTimeout(()=>location.reload(),1500);
}

async function openPixSplit(){
  const pixVal=splitPays.find(p=>p.metodo==='pix');
  const pixAmount=pixVal?pixVal.valor:0;
  const totalRecebido=splitPays.reduce((s,p)=>s+p.valor,0);
  const cpf_cnpj=document.getElementById('pcpfcnpj')?document.getElementById('pcpfcnpj').value.trim():'';
  const obs=document.getElementById('pobs')?document.getElementById('pobs').value.trim():'';
  const semNota=document.getElementById('pSemNota')?document.getElementById('pSemNota').checked:false;
  const body={nome:pname.value.trim(),fone:pphone.value,itens:Object.values(pcart),
    metodo:splitPays[0].metodo,valor_recebido:totalRecebido,
    pagamentos:splitPays,cpf_cnpj:cpf_cnpj,obs:obs,sem_nota:semNota};
  if(IS_ENTREGA){
    body.tipo='entrega';
    body.endereco=document.getElementById('pRua').value.trim()+(document.getElementById('pNumero').value.trim()?', '+document.getElementById('pNumero').value.trim():'');
    body.bairro=document.getElementById('pBairro').value;
    body.referencia=document.getElementById('pRef').value.trim();
  } else {body.tipo='balcao'}
  const r=await fetch('?r=pdv_create',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)}).then(x=>x.json()).catch(()=>({ok:false}));
  if(!r.ok){alert(r.erro||'Erro ao registrar.');return}
  LAST_ORDER_ID=r.id;
  document.getElementById('pixTotal').textContent=fmt(pixAmount);
  const pix=await fetch('?r=order_pix&id='+r.id+'&valor='+pixAmount).then(x=>x.json()).catch(()=>({ok:false}));
  if(!pix.ok||!pix.payload){alert('Chave PIX nao configurada.');confirmPixPaid();return}
  document.getElementById('pixCopyCode').value=pix.payload;
  const qc=document.getElementById('pixQrBox');
  qc.innerHTML='';
  new QRCode(qc,{text:pix.payload,width:200,height:200});
  pixModal.classList.add('on');
}

/* PIX - abre QR code antes de registrar a venda */
async function openPixPayment(){
  const cpf_cnpj=document.getElementById('pcpfcnpj')?document.getElementById('pcpfcnpj').value.trim():'';
  const obs=document.getElementById('pobs')?document.getElementById('pobs').value.trim():'';
  const semNota=document.getElementById('pSemNota')?document.getElementById('pSemNota').checked:false;
  const body={nome:pname.value.trim(),fone:pphone.value,itens:Object.values(pcart),metodo:'pix',valor_recebido:TOTAL,cpf_cnpj:cpf_cnpj,obs:obs,sem_nota:semNota};
  if(IS_ENTREGA){
    body.tipo='entrega';
    body.endereco=document.getElementById('pRua').value.trim()+(document.getElementById('pNumero').value.trim()?', '+document.getElementById('pNumero').value.trim():'');
    body.bairro=document.getElementById('pBairro').value;
    body.referencia=document.getElementById('pRef').value.trim();
  } else {body.tipo='balcao'}
  const r=await fetch('?r=pdv_create',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)}).then(x=>x.json()).catch(()=>({ok:false}));
  if(!r.ok){alert(r.erro||'Erro ao registrar.');return}
  LAST_ORDER_ID=r.id;
  document.getElementById('pixTotal').textContent=fmt(TOTAL);
  const pix=await fetch('?r=order_pix&id='+r.id).then(x=>x.json()).catch(()=>({ok:false}));
  if(!pix.ok||!pix.payload){alert('Chave PIX nao configurada.');return}
  document.getElementById('pixCopyCode').value=pix.payload;
  const qc=document.getElementById('pixQrBox');
  qc.innerHTML='';
  new QRCode(qc,{text:pix.payload,width:200,height:200});
  pixModal.classList.add('on');
}

async function confirmPixPaid(){
  pixModal.classList.remove('on');
  if(PRINT_CFG.auto){
    const cpf_cnpj=document.getElementById('pcpfcnpj')?document.getElementById('pcpfcnpj').value.trim():'';
    const obs=document.getElementById('pobs')?document.getElementById('pobs').value.trim():'';
    printReceipt({codigo:'#'+String(LAST_ORDER_ID).padStart(4,'0'),tipo:IS_ENTREGA?'entrega':'balcao',nome:pname.value.trim(),fone:pphone.value,
      itens:Object.values(pcart),subtotal:SUBTOTAL,taxa_entrega:TAXA_ENTREGA,total:TOTAL,
      metodo:'pix',recebido:TOTAL,troco:0,cpf_cnpj:cpf_cnpj,obs:obs,
      endereco:IS_ENTREGA?(document.getElementById('pRua').value.trim()+(document.getElementById('pNumero').value.trim()?', '+document.getElementById('pNumero').value.trim():'')):'',
      bairro:IS_ENTREGA?document.getElementById('pBairro').value:'',
      referencia:IS_ENTREGA?document.getElementById('pRef').value.trim():''});
  }
  setTimeout(()=>location.reload(),1500);
}

async function showLast(){
  const r=await fetch('?r=pdv_last').then(x=>x.json()).catch(()=>({ok:false}));
  if(!r.ok){lastBody.innerHTML=`<p class="pdv-last-empty">${r.erro||'Nao foi possivel consultar.'}</p>`}
  else{
    const v=r.venda;
    lastBody.innerHTML=`
      <div class="pdv-last-head"><b>${v.codigo}</b><span>${(v.criado_em||'').substring(11,16)} - ${v.cliente_nome||''}</span></div>
      <div class="pdv-last-items">${(r.itens||[]).map(i=>`<div><span>${i.qtd}x ${i.nome}</span><b>${fmt(i.qtd*i.preco)}</b></div>`).join('')}</div>
      <div class="pdv-last-line"><span>Total</span><b>${fmt(v.total)}</b></div>
      <div class="pdv-last-line"><span>Pagamento</span><b>${v.pagamentos_detalhe?JSON.parse(v.pagamentos_detalhe).map(p=>(METLABEL[p.metodo]||PAYL[p.metodo]||p.metodo)+' '+fmt(p.valor)).join(' + '):(PAYL[v.pagamento_metodo]||v.pagamento_metodo)}</b></div>
      ${+v.troco>0?`<div class="pdv-last-line"><span>Recebido / Troco</span><b>${fmt(v.valor_recebido)} - troco ${fmt(v.troco)}</b></div>`:''}
      ${v.recebido_por?`<div class="pdv-last-line"><span>Operador</span><b>${v.recebido_por}</b></div>`:''}`;
  }
  lastModal.classList.add('on');
}

async function cancelSale(){
  if(!Object.keys(pcart).length)return alert('Nenhuma venda em andamento para cancelar.');
  const senha=prompt('CANCELAR VENDA\nInforme a senha do administrador:');
  if(!senha)return;
  const r=await fetch('?r=pdv_admin_check',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'senha='+encodeURIComponent(senha)}).then(x=>x.json()).catch(()=>({ok:false}));
  if(!r.ok)return alert('Senha do administrador incorreta. A venda nao foi cancelada.');
  Object.keys(pcart).forEach(k=>delete pcart[k]);pname.value='';pphone.value='';TAXA_ENTREGA=0;IS_ENTREGA=false;PAY_OPT='agora';renderP();closeCharge();
  if(document.getElementById('pcpfcnpj'))document.getElementById('pcpfcnpj').value='';
  if(document.getElementById('pobs'))document.getElementById('pobs').value='';
  document.getElementById('cpfArea').classList.remove('show');
  document.getElementById('obsArea').classList.remove('show');
  document.getElementById('deliveryArea').classList.remove('show');
  document.getElementById('payChoice').classList.remove('show');
  document.getElementById('btnCpf').classList.remove('cpf-active');
  document.getElementById('btnEntrega').classList.remove('active');
  document.getElementById('rowEntrega').style.display='none';
  document.getElementById('pRua').value='';document.getElementById('pBairro').value='';
  document.getElementById('pNumero').value='';document.getElementById('pRef').value='';
  document.getElementById('delTaxaInfo').innerHTML='';
  selectPayOpt('agora');
  alert('Venda cancelada.');
}

document.addEventListener('keydown',e=>{
  const tag=document.activeElement?.tagName;
  const inInput=tag==='INPUT'||tag==='TEXTAREA'||tag==='SELECT';

  if(e.key==='F10'){e.preventDefault();chargeModal.classList.contains('on')?finishPdv():openCharge();return}
  if(e.key==='F9'){e.preventDefault();showLast();return}
  if(e.key==='F8'){e.preventDefault();cancelSale();return}
  if(e.key==='Escape'){chargeModal.classList.remove('on');lastModal.classList.remove('on');pixModal.classList.remove('on');return}

  if(chargeModal.classList.contains('on')){
    const inValor=document.activeElement===pRecebido;
    if(PAYK[e.key]&&!inValor){e.preventDefault();const el=document.querySelector(`[name=pmet][value=${PAYK[e.key]}]`);el.checked=true;payChanged(true)}
    else if(e.key==='Enter'){e.preventDefault();finishPdv()}
    return;
  }

  if(inInput)return;

  switch(e.key.toLowerCase()){
    case 'm':e.preventDefault();switchMode('mesas');break;
    case 'f':e.preventDefault();document.getElementById('pdvCats').classList.toggle('show');break;
    case 'p':e.preventDefault();document.getElementById('psearch').focus();break;
    case 'a':e.preventDefault();document.querySelector('.pdv-left').scrollBy(0,300);break;
    case 'o':e.preventDefault();toggleObs();break;
    case 'e':e.preventDefault();toggleEntrega();break;
    case 't':e.preventDefault();toggleCpf();break;
    case 'enter':e.preventDefault();openCharge();break;
  }
});
</script>
