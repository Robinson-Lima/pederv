<div class="placa">
  <div class="pb"><img src="assets/rv-logo.png" onerror="this.style.display='none'"><div class="w">RV<b>CARDÁPIOS</b></div></div>
  <div class="pband">CARDÁPIO DIGITAL</div>
  <div id="qr-placa"></div>
  <div class="sl">Aponte a câmera e <b>peça sozinho</b></div>
  <div style="color:#8A909C;font-size:11.5px">Sem espera. Pague pelo Pix direto do celular.</div>
  <div class="foot"><?= e(cfg('restaurante')) ?> · Mesa <?= e($mesa) ?></div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
const url=location.origin+location.pathname.replace(/index\.php$/,'')+'?r=menu&mesa=<?= e($mesa) ?>';
new QRCode(document.getElementById('qr-placa'),{text:url,width:200,height:200});
</script>
