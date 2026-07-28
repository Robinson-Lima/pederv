<div class="wrap">
  <div class="cfgcard address-complete">
    <h3>Endereço completo do restaurante</h3>
    <p>Informe o CEP para preencher bairro, cidade e UF automaticamente. Depois confirme o número e localize no mapa.</p>
    <div class="rest-address-grid">
      <div><label>CEP</label><input id="restCep" value="<?= e($restCep) ?>" placeholder="00000-000" inputmode="numeric" maxlength="9" onblur="buscarCep()"></div>
      <div class="wide"><label>Rua / avenida</label><input id="restLogradouro" value="<?= e($restLogradouro) ?>" placeholder="Logradouro"></div>
      <div><label>Número</label><input id="restNumero" value="<?= e($restNumero) ?>" placeholder="Número"></div>
      <div><label>Bairro</label><input id="restBairro" value="<?= e($restBairro) ?>" placeholder="Bairro"></div>
      <div><label>Cidade</label><input id="restCidade" value="<?= e($restCidade) ?>" placeholder="Cidade"></div>
      <div><label>UF</label><input id="restUf" value="<?= e($restUf) ?>" placeholder="UF" maxlength="2"></div>
    </div>
    <button class="cxgo or locate-rest" onclick="localizarCompleto()">Preencher endereço e localizar no mapa</button>
    <small id="cepMsg" class="zone-msg"></small>
    <input type="hidden" id="restEnd" value="<?= e($restEndereco) ?>">
    <small id="restMsg" class="zone-msg"><?= $restLat? 'Local salvo: '.e($restLat).', '.e($restLng) : 'Ainda não localizado.' ?></small>
  </div>
  <div class="page-title-row"><div><h2>Taxas e área de entrega</h2><p>Igual ao iFood: você desenha no mapa até onde entrega, define a taxa de cada faixa e marca as regiões bloqueadas. O bairro continua funcionando como plano B.</p></div></div>

  <div class="cfgcard">
    <h3>🗺 Áreas no mapa</h3>
    <p>Defina a área atendida desenhando livremente ou criando um alcance em quilômetros, como no painel do iFood.</p>
    <div class="delivery-designer" style="display:grid;grid-template-columns:280px minmax(0,1fr);min-height:540px;border:1px solid #ddd;border-radius:14px;overflow:hidden;background:#fff">
      <aside class="delivery-toolbox" style="padding:20px;background:#fafafa;border-right:1px solid #ddd;display:flex;flex-direction:column;gap:12px">
        <b>Minha área de entrega</b>
        <p>Localize o restaurante e escolha como deseja configurar a região atendida.</p>
        <button type="button" class="map-primary" style="display:block;width:100%;padding:12px;border:0;border-radius:9px;background:#e51f3f;color:#fff;font-weight:900;cursor:pointer" onclick="startDrawing()">✎ Desenhar no mapa</button>
        <label>Ou crie uma área por distância</label>
        <select id="radiusKm" style="display:block;width:100%;padding:11px;border:1px solid #ddd;border-radius:9px;background:#fff"><option value="1">Até 1 km</option><option value="3">Até 3 km</option><option value="5" selected>Até 5 km</option><option value="8">Até 8 km</option><option value="10">Até 10 km</option><option value="15">Até 15 km</option></select>
        <button type="button" class="map-secondary" style="display:block;width:100%;padding:12px;border:1px solid #6d35b4;border-radius:9px;background:#fff;color:#6d35b4;font-weight:900;cursor:pointer" onclick="createRadius()">Criar área circular</button>
        <small id="mapStatus" style="display:block;min-height:34px;color:#6d35b4;font-weight:800;line-height:1.4">Carregando o mapa…</small>
        <div class="map-help" style="display:grid;gap:6px;margin-top:auto;padding:12px;border-radius:10px;background:#f1eafb;color:#51317e"><b>Como desenhar</b><span>1. Clique em Desenhar no mapa.</span><span>2. Marque os pontos ao redor dos bairros.</span><span>3. Clique novamente no primeiro ponto.</span><span>4. Informe nome, taxa e salve.</span></div>
      </aside>
      <div class="map-stage" style="position:relative;display:block;min-width:0;min-height:540px;background:#ecebe5"><div id="map" class="zone-map" style="display:block;width:100%;height:540px;min-height:540px;margin:0;border:0;border-radius:0;position:relative;z-index:1;background:#e9e8e2"><div id="mapLoading" style="height:100%;display:grid;place-items:center;padding:30px;color:#6d35b4;font-weight:900;text-align:center">Carregando mapa…</div></div><div id="mapNoTiles" class="map-no-tiles-note" hidden>O fundo do mapa não carregou, mas o desenho continua funcionando. Confira se o navegador permite acesso ao OpenStreetMap.</div></div>
    </div>
    <div class="zone-editor" id="zoneEditor" style="display:none">
      <input id="zNome" placeholder="Nome da área (ex: Centro / até 3km)">
      <select id="zTipo" onchange="document.getElementById('zTaxa').disabled=this.value==='bloqueio'">
        <option value="entrega">✅ Entrego aqui — cobrar taxa</option>
        <option value="bloqueio">⛔ Não entrego aqui</option>
      </select>
      <input id="zTaxa" placeholder="Taxa (ex: 7,00)">
      <button class="cxgo or" onclick="salvarZona()">Salvar área</button>
      <button class="zcancel" onclick="cancelarZona()">Descartar desenho</button>
    </div>
  </div>

  <div class="cfgcard area-page">
    <h3>Áreas cadastradas</h3>
    <?php foreach($zones as $z): ?>
      <div class="area-row zone-row <?= $z['ativo']?'':'off' ?>">
        <div><b><?= $z['tipo']==='bloqueio'?'⛔':'✅' ?> <?= e($z['nome']) ?></b><small><?= $z['tipo']==='bloqueio'?'Região bloqueada — o cardápio recusa o pedido':'Taxa aplicada automaticamente no checkout' ?><?= $z['ativo']?'':' · DESATIVADA' ?></small></div>
        <strong><?= $z['tipo']==='bloqueio'?'—':money($z['taxa']) ?></strong>
        <button onclick="toggleZona(<?= $z['id'] ?>)"><?= $z['ativo']?'Desativar':'Ativar' ?></button>
        <button onclick="excluirZona(<?= $z['id'] ?>)">Excluir</button>
      </div>
    <?php endforeach; ?>
    <?php if(!$zones): ?><p>Nenhuma área desenhada ainda — o sistema vai usar as taxas por bairro abaixo.</p><?php endif; ?>
  </div>

  <div class="cfgcard area-page">
    <h3>Taxas por bairro (plano B)</h3>
    <p>Usadas quando o cliente não tem localização no mapa ou quando não há área desenhada.</p>
    <form method="post" action="?r=area_salvar" class="area-form">
      <input name="bairro" placeholder="Nome do bairro" required>
      <input name="taxa" placeholder="Taxa (ex: 7,00)" required>
      <button class="cxgo or">+ Cadastrar bairro</button>
    </form>
    <?php foreach($areas as $a): ?>
      <div class="area-row"><div><b><?= e($a['bairro']) ?></b><small>Taxa calculada no checkout</small></div><strong><?= money($a['taxa']) ?></strong>
      <form method="post" action="?r=area_excluir" onsubmit="return confirm('Excluir este bairro?')"><input type="hidden" name="id" value="<?= $a['id'] ?>"><button>Excluir</button></form></div>
    <?php endforeach; ?>
    <?php if(!$areas): ?><p>Nenhum bairro cadastrado.</p><?php endif; ?>
  </div>
</div>

<style>
.delivery-designer{display:grid!important;grid-template-columns:280px minmax(0,1fr)!important;min-height:540px!important;border:1px solid #ddd;border-radius:14px;overflow:hidden;background:#fff}.delivery-toolbox{padding:20px!important;background:#fafafa;border-right:1px solid #ddd;display:flex!important;flex-direction:column;gap:12px}.delivery-toolbox>b{font-size:19px}.delivery-toolbox p{font-size:12px;line-height:1.5;color:#6f7780}.delivery-toolbox label{font-size:11px;font-weight:900;margin-top:6px}.delivery-toolbox select{width:100%;padding:11px;border:1px solid #ddd;border-radius:9px;background:#fff}.map-primary,.map-secondary{display:block!important;width:100%!important;padding:12px!important;border-radius:9px!important;font-weight:900!important}.map-primary{background:#e51f3f!important;color:#fff!important}.map-secondary{background:#fff!important;color:#6d35b4!important;border:1px solid #6d35b4!important}.delivery-toolbox>small{min-height:34px;color:#6d35b4;font-weight:800;line-height:1.4}.map-help{display:grid;gap:6px;margin-top:auto;padding:12px;border-radius:10px;background:#f1eafb;color:#51317e}.map-help b{font-size:11px}.map-help span{display:block;font-size:10px}.map-stage{position:relative!important;min-width:0;background:#ecebe5}.map-stage #map,.zone-map{display:block!important;width:100%!important;height:540px!important;min-height:540px!important;margin:0!important;border:0!important;border-radius:0!important;position:relative!important;z-index:1}.map-no-tiles-note{position:absolute;left:16px;right:16px;bottom:16px;z-index:500;background:#fff7dc;border:1px solid #e4bd45;border-radius:9px;padding:9px 12px;font-size:11px;color:#745b0a}.zone-editor{margin-top:12px!important}.leaflet-control-draw{display:none!important}@media(max-width:900px){.delivery-designer{grid-template-columns:1fr!important}.delivery-toolbox{border-right:0;border-bottom:1px solid #ddd}.map-stage #map,.zone-map{height:440px!important;min-height:440px!important}}
</style>
<link rel="stylesheet" href="assets/vendor/leaflet/leaflet.css?v=9">
<link rel="stylesheet" href="assets/vendor/leaflet-draw/leaflet.draw.css?v=9">
<script src="assets/vendor/leaflet/leaflet.js?v=9"></script>
<script src="assets/vendor/leaflet-draw/leaflet.draw.js?v=9"></script>
<script>
const ZONES=<?= json_encode($zones,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE) ?: '[]' ?>;
const LAT0=parseFloat('<?= e($restLat?:'-23.9') ?>')||-23.9, LNG0=parseFloat('<?= e($restLng?:'-46.33') ?>')||-46.33;
let map,restMarker,desenho=null,drawn;
if(typeof L==='undefined'){document.getElementById('map').innerHTML='<div style="height:100%;display:grid;place-items:center;padding:30px;text-align:center;background:#fff0e8;color:#b23a21;font-weight:900">O mapa não iniciou. Reenvie a pasta assets completa ao servidor.</div>';throw new Error('Leaflet não carregado')}
document.getElementById('map').innerHTML='';

map=L.map('map',{zoomControl:true}).setView([LAT0,LNG0],13);
let tilesLoaded=false,tileErrors=0;
const baseTiles=L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'© OpenStreetMap',crossOrigin:true}).addTo(map);
baseTiles.on('load',()=>{tilesLoaded=true;document.getElementById('mapStatus').textContent='Mapa carregado. Desenhe a área ou crie um raio.';document.getElementById('mapNoTiles').hidden=true});
baseTiles.on('tileerror',()=>{tileErrors++;if(tileErrors===3){document.getElementById('mapStatus').textContent='Tentando uma segunda fonte de mapa…';const alt=L.tileLayer('https://a.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',{maxZoom:20,attribution:'© OpenStreetMap © CARTO'}).addTo(map);alt.on('load',()=>{tilesLoaded=true;document.getElementById('mapStatus').textContent='Mapa carregado. Desenhe a área ou crie um raio.'})}});
restMarker=L.marker([LAT0,LNG0],{draggable:true}).addTo(map).bindPopup('Restaurante — arraste para ajustar');
restMarker.on('dragend',()=>salvarRest(restMarker.getLatLng().lat,restMarker.getLatLng().lng,document.getElementById('restEnd').value));
setTimeout(()=>map.invalidateSize(),250);
setTimeout(()=>{map.invalidateSize();if(!tilesLoaded){document.getElementById('map').classList.add('map-grid-fallback');document.getElementById('mapNoTiles').hidden=false;document.getElementById('mapStatus').textContent='O desenho está liberado. Se as ruas não aparecerem, verifique o bloqueio do navegador.'}},6000);

drawn=new L.FeatureGroup();map.addLayer(drawn);
ZONES.forEach(z=>{try{const pts=JSON.parse(z.poligono);L.polygon(pts,{color:z.tipo==='bloqueio'?'#d94040':'#22A06B',fillOpacity:z.ativo==1?.25:.06,dashArray:z.ativo==1?null:'5,6'}).addTo(map).bindPopup(`<b>${z.nome}</b><br>${z.tipo==='bloqueio'?'Não entrega':'Taxa R$ '+parseFloat(z.taxa).toFixed(2).replace('.',',')}`)}catch(e){}});

map.addControl(new L.Control.Draw({
  edit:{featureGroup:drawn,edit:false,remove:false},
  draw:{polygon:{allowIntersection:false,shapeOptions:{color:'#EE7430'}},polyline:false,rectangle:{shapeOptions:{color:'#EE7430'}},circle:false,marker:false,circlemarker:false}
}));
map.on(L.Draw.Event.CREATED,e=>{drawn.clearLayers();desenho=e.layer;drawn.addLayer(desenho);document.getElementById('zoneEditor').style.display='flex';document.getElementById('zNome').focus()});

function abrirEditor(){document.getElementById('zoneEditor').style.display='flex';document.getElementById('zNome').focus();document.getElementById('mapStatus').textContent='Área selecionada. Informe o nome e a taxa abaixo.'}
function startDrawing(){
  if(!map||!L.Draw)return alert('O desenho ainda está carregando. Aguarde alguns segundos.');
  drawn.clearLayers();desenho=null;new L.Draw.Polygon(map,{allowIntersection:false,showArea:true,shapeOptions:{color:'#6d35b4',fillColor:'#8a55c5',fillOpacity:.3,weight:3}}).enable();
  document.getElementById('mapStatus').textContent='Clique ponto a ponto no mapa e feche no primeiro ponto.';
}
function createRadius(){
  const km=parseFloat(document.getElementById('radiusKm').value)||5,center=restMarker.getLatLng(),pts=[];
  for(let i=0;i<64;i++){const a=2*Math.PI*i/64,lat=center.lat+(km/111.32)*Math.sin(a),lng=center.lng+(km/(111.32*Math.cos(center.lat*Math.PI/180)))*Math.cos(a);pts.push([lat,lng])}
  drawn.clearLayers();desenho=L.polygon(pts,{color:'#6d35b4',fillColor:'#8a55c5',fillOpacity:.3,weight:3});drawn.addLayer(desenho);map.fitBounds(desenho.getBounds(),{padding:[20,20]});document.getElementById('zNome').value='Até '+km+' km';abrirEditor();
}

function pontos(){return desenho.getLatLngs()[0].map(p=>[p.lat,p.lng])}
function cancelarZona(){drawn.clearLayers();desenho=null;document.getElementById('zoneEditor').style.display='none'}
async function salvarZona(){
  if(!desenho)return;
  const nome=document.getElementById('zNome').value.trim();
  if(!nome)return alert('Dê um nome para a área.');
  const tipo=document.getElementById('zTipo').value;
  const taxa=document.getElementById('zTaxa').value.replace(',','.');
  if(tipo==='entrega'&&taxa==='')return alert('Informe a taxa desta área.');
  const r=await fetch('?r=zone_salvar',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({nome,tipo,taxa,poligono:pontos()})}).then(x=>x.json());
  if(!r.ok)return alert(r.erro||'Não foi possível salvar.');
  location.reload();
}
async function excluirZona(id){if(!confirm('Excluir esta área do mapa?'))return;await fetch('?r=zone_excluir',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+id});location.reload()}
async function toggleZona(id){await fetch('?r=zone_toggle',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+id});location.reload()}

async function salvarRest(lat,lng,end){
  const params=new URLSearchParams({endereco:end||'',lat,lng,cep:val('restCep'),logradouro:val('restLogradouro'),numero:val('restNumero'),bairro:val('restBairro'),cidade:val('restCidade'),uf:val('restUf')});
  await fetch('?r=resto_endereco_salvar',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:params});
  document.getElementById('restMsg').textContent='Local salvo: '+lat.toFixed(6)+', '+lng.toFixed(6);
}
function val(id){return document.getElementById(id)?.value.trim()||''}
function enderecoCompletoRest(){return [val('restLogradouro'),val('restNumero'),val('restBairro'),val('restCidade'),val('restUf'),'Brasil'].filter(Boolean).join(', ')}
async function buscarCep(){
  const cep=val('restCep').replace(/\D/g,'');if(cep.length!==8)return;
  const msg=document.getElementById('cepMsg');msg.textContent='Consultando CEP…';
  try{
    const d=await fetch('https://viacep.com.br/ws/'+cep+'/json/',{cache:'no-store'}).then(r=>r.json());if(d.erro)throw new Error();
    document.getElementById('restLogradouro').value=d.logradouro||'';
    document.getElementById('restBairro').value=d.bairro||'';
    document.getElementById('restCidade').value=d.localidade||'';
    document.getElementById('restUf').value=d.uf||'';
    document.getElementById('restNumero').focus();msg.textContent='CEP localizado. Informe o número.';
  }catch(e){msg.textContent='CEP não encontrado. Preencha manualmente.'}
}
function localizarCompleto(){
  const end=enderecoCompletoRest();if(!val('restLogradouro')||!val('restCidade'))return alert('Informe CEP ou rua e cidade.');
  document.getElementById('restEnd').value=end;localizarRest();
}
async function localizarRest(){
  const end=document.getElementById('restEnd').value.trim();
  if(!end)return alert('Digite o endereço do restaurante.');
  document.getElementById('restMsg').textContent='Procurando endereço…';
  try{
    const r=await fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=br&q='+encodeURIComponent(end),{headers:{'Accept':'application/json'}}).then(x=>x.json());
    if(!r.length){document.getElementById('restMsg').textContent='Endereço não encontrado. Tente com cidade e UF, ou arraste o pino no mapa.';return}
    const lat=parseFloat(r[0].lat),lng=parseFloat(r[0].lon);
    if(map&&restMarker){map.setView([lat,lng],15);restMarker.setLatLng([lat,lng]);}
    salvarRest(lat,lng,end);
  }catch(e){document.getElementById('restMsg').textContent='Falha ao consultar o mapa. Arraste o pino manualmente.'}
}
</script>
