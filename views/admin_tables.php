<div class="wrap dash">
  <h2><?= e(cfg('restaurante')) ?></h2>
  <?= admin_subnav('admin_tables') ?>

  <p style="color:#727884;font-size:13px;margin:6px 2px 14px">
    Mesas <b style="color:#22A06B">livres</b> são abertas pelo garçom. Clique numa mesa <b style="color:#EE7430">ocupada</b> para ver o consumo, quem atendeu e fechar.</p>

  <div class="mesas-adm">
    <?php foreach($tables as $t): $c=$comandas[$t['numero']]??null; $occupied=$c||$t['status']==='ocupada'; ?>
      <div class="mesa-adm <?= $occupied?'ocupada':'livre' ?>" onclick="<?= $c?"abrir('".e($t['numero'])."')":'' ?>" style="<?= $c?'cursor:pointer':'' ?>">
        <div class="n">Mesa <?= e($t['numero']) ?></div>
        <?php if($c): ?>
          <div class="s"><?= money($c['total']) ?></div>
          <div style="font-size:10px;opacity:.85;margin-top:2px">👤 <?= e($c['atendente']?:'—') ?></div>
        <?php elseif($occupied): ?>
          <div class="s">Ocupada</div><div style="font-size:10px;opacity:.85">QR Code aberto · aguardando pedido</div><button class="release-table" onclick="event.stopPropagation();liberar(<?= (int)$t['id'] ?>)">Liberar mesa</button>
        <?php else: ?>
          <div class="s">Livre</div>
        <?php endif; ?>
        <a class="qrlink" href="?r=placa&mesa=<?= e($t['numero']) ?>" target="_blank" onclick="event.stopPropagation()">QR Code da mesa</a>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- modal consumo -->
<div id="mesaModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:60;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;max-width:420px;width:92%;max-height:88vh;overflow-y:auto;padding:20px">
    <div style="display:flex;justify-content:space-between;align-items:center">
      <h3 id="m-title" style="font-size:18px">Mesa</h3>
      <button onclick="fechar()" style="background:none;border:0;font-size:22px;cursor:pointer">×</button>
    </div>
    <div id="m-atend" style="font-size:12.5px;color:#727884;margin:2px 0 12px"></div>
    <div id="m-itens"></div>
    <div style="display:flex;justify-content:space-between;border-top:1px solid #E7E3DC;margin-top:10px;padding-top:10px;font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:18px">
      <span>Total</span><span id="m-total"></span></div>
    <form method="post" action="?r=waiter_fechar" onsubmit="return confirm('Fechar esta mesa?')" style="margin-top:14px">
      <input type="hidden" name="mesa" id="m-mesa"><input type="hidden" name="origem" value="admin">
      <label class="table-pay-label">Forma de pagamento</label><select name="metodo" required class="table-pay-input"><option value="">Selecione…</option><option value="dinheiro">Dinheiro</option><option value="pix">Pix</option><option value="debito">Cartão de débito</option><option value="credito">Cartão de crédito</option></select>
      <label class="table-pay-label">Valor recebido</label><input name="valor_pago" id="m-pago" required class="table-pay-input">
      <button type="button" class="save table-print" onclick="imprimirConferencia()">🧾 Imprimir conferência</button>
      <button type="button" class="save table-transfer" onclick="transferirMesa()">⇄ Transferir para outra mesa</button>
      <button class="save table-close">💳 Confirmar pagamento e fechar mesa</button>
    </form>
  </div>
</div>

<script>
const COM = <?= json_encode($comandas,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE) ?: '{}' ?>;
const ITENS = <?= json_encode($itensMesa,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE) ?: '{}' ?>;
const TABLES = <?= json_encode(array_column($tables,'numero')) ?>;
const RESTAURANTE = <?= json_encode(cfg('restaurante'),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE) ?>;
function abrir(mesa){
  const c=COM[mesa]; if(!c) return;
  document.getElementById('m-title').textContent='Mesa '+mesa+' · Comanda '+c.codigo;
  document.getElementById('m-atend').textContent='👤 Atendido por: '+(c.atendente||'—');
  document.getElementById('m-mesa').value=mesa;
  let html=''; (ITENS[mesa]||[]).forEach(it=>{
    html+=`<div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #E7E3DC;font-size:13.5px"><span><b style="color:#EE7430">${it.qtd}×</b> ${it.nome}</span><b>R$ ${(it.qtd*it.preco).toFixed(2).replace('.',',')}</b></div>`;
  });
  document.getElementById('m-itens').innerHTML=html||'<p style="color:#727884">Sem itens.</p>';
  document.getElementById('m-total').textContent='R$ '+parseFloat(c.total).toFixed(2).replace('.',',');
  document.getElementById('m-pago').value=parseFloat(c.total).toFixed(2).replace('.',',');
  document.getElementById('mesaModal').style.display='flex';
}
function escPrint(v){return String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]))}
function imprimirConferencia(){
  const mesa=document.getElementById('m-mesa').value,c=COM[mesa],lista=ITENS[mesa]||[];if(!c)return alert('Comanda não encontrada.');
  const linhas=lista.map(it=>{const qtd=parseFloat(it.qtd)||0,preco=parseFloat(it.preco)||0;return `<tr><td>${qtd}x ${escPrint(it.nome)}</td><td>R$ ${preco.toFixed(2).replace('.',',')}</td><td>R$ ${(qtd*preco).toFixed(2).replace('.',',')}</td></tr>`}).join('');
  const data=new Date().toLocaleString('pt-BR'),total=parseFloat(c.total||0).toFixed(2).replace('.',',');
  const html=`<!doctype html><html><head><meta charset="utf-8"><title>Conferência ${escPrint(c.codigo)}</title><style>@page{size:80mm auto;margin:5mm}*{box-sizing:border-box}body{font-family:Arial,sans-serif;color:#111;margin:0;font-size:12px}.receipt{width:100%;max-width:76mm;margin:auto}h1{font-size:16px;text-align:center;margin:0 0 4px}.meta{text-align:center;font-size:10px;margin-bottom:12px}.dash{border-top:1px dashed #333;margin:9px 0}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:6px 2px;border-bottom:1px dashed #aaa}th:nth-child(2),th:nth-child(3),td:nth-child(2),td:nth-child(3){text-align:right;white-space:nowrap}.total{display:flex;justify-content:space-between;font-size:16px;font-weight:bold;margin-top:12px}.footer{text-align:center;font-size:10px;margin-top:18px}@media print{button{display:none}}</style></head><body><div class="receipt"><h1>${escPrint(RESTAURANTE)}</h1><div class="meta">CONFERÊNCIA DE CONSUMO<br>Mesa ${escPrint(mesa)} · Comanda ${escPrint(c.codigo)}<br>${escPrint(data)}<br>Atendente: ${escPrint(c.atendente||'—')}</div><div class="dash"></div><table><thead><tr><th>Produto</th><th>Unit.</th><th>Total</th></tr></thead><tbody>${linhas||'<tr><td colspan="3">Sem itens lançados</td></tr>'}</tbody></table><div class="total"><span>TOTAL</span><span>R$ ${total}</span></div><div class="dash"></div><div class="footer">Documento para conferência · Não é documento fiscal</div></div></body></html>`;
  const w=window.open('','_blank','width=440,height=720');if(!w)return alert('Permita a abertura da janela de impressão no navegador.');w.document.open();w.document.write(html);w.document.close();w.focus();setTimeout(()=>w.print(),350);
}
function fechar(){document.getElementById('mesaModal').style.display='none';}
async function liberar(id){if(!confirm('Liberar esta mesa sem comanda?'))return;await fetch('?r=admin_table_toggle',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+id});location.reload()}
async function transferirMesa(){const from=document.getElementById('m-mesa').value,to=prompt('Transferir a comanda da Mesa '+from+' para qual mesa?\nDisponíveis: '+TABLES.filter(x=>x!==from).join(', '));if(!to)return;const r=await fetch('?r=admin_transfer_table',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'from='+encodeURIComponent(from)+'&to='+encodeURIComponent(to)}).then(x=>x.json());if(!r.ok)return alert(r.erro||'Não foi possível transferir.');alert('Comanda transferida para a Mesa '+to);location.reload()}
</script>
