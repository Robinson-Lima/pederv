<?php
$tabs=[
  'status'=>['Canais e status','Defina por onde a loja recebe pedidos.'],
  'sequence'=>['Sequência do pedido','Automação, numeração e aceite.'],
  'sounds'=>['Som dos pedidos','Escolha os alertas de cada operação.'],
  'printing'=>['Impressão','Configure a impressão automática.'],
  'cancel'=>['Cancelar pedido','Defina a segurança do cancelamento.'],
  'establishment'=>['Estabelecimento','Dados, contato e funcionamento da loja.'],
  'delivery'=>['Entrega','Prazos, retirada e operação delivery.'],
  'cardapio'=>['Cardápio digital','Conta do cliente e experiência de compra.'],
  'integrations'=>['Integrações','Evolution, iFood e automações externas.'],
  'security'=>['Segurança','Sessões, acessos e proteção da operação.'],
];
$ck=function($key,$def='0'){return setting_get($key,$def)==='1'?'checked':'';};
$toggle=function($key,$label,$help,$def='0')use($ck){ ?>
  <label class="setting-row"><span><b><?= e($label) ?></b><small><?= e($help) ?></small></span><span class="switch-ui"><input type="hidden" name="s[<?= e($key) ?>]" value="0"><input type="checkbox" name="s[<?= e($key) ?>]" value="1" <?= $ck($key,$def) ?>><i></i></span></label>
<?php };
?>
<div class="wrap settings-page">
  <div class="page-title-row"><div><h2>Configurações gerais</h2><p>Centralize o funcionamento dos pedidos, alertas, impressão e cancelamentos.</p></div></div>
  <?php if(isset($_GET['salvo'])): ?><div class="oknote">Configurações salvas com sucesso.</div><?php endif; ?>
  <div class="settings-shell">
    <nav class="settings-nav"><?php $n=1;foreach($tabs as $key=>$tab): ?><a class="<?= $sec===$key?'on':'' ?>" href="?r=admin_general&sec=<?= $key ?>"><b><?= $n++ ?>.</b><span><?= e($tab[0]) ?><small><?= e($tab[1]) ?></small></span></a><?php endforeach; ?></nav>
    <section class="settings-panel"><form method="post" action="?r=admin_general&sec=<?= e($sec) ?>" enctype="multipart/form-data">
      <?php if($sec==='status'): ?>
        <h3>1. Canais e status do sistema</h3><p>Ative somente os canais que sua loja realmente utiliza.</p>
        <?php $toggle('channel_cardapio','Cardápio digital','Receber pedidos feitos no cardápio on-line.','1'); ?>
        <?php $toggle('channel_whatsapp','WhatsApp / robô','Receber pedidos e mensagens pela integração Evolution.','1'); ?>
        <?php $toggle('channel_ifood','iFood','Receber pedidos integrados ou usar o simulador enquanto conecta.','1'); ?>
        <?php $toggle('channel_salon','Salão, mesas e balcão','Habilitar pedidos locais, comandas e PDV.','1'); ?>
      <?php elseif($sec==='sequence'): ?>
        <h3>2. Sequência do pedido</h3><p>Controle o aceite e a identificação dos novos pedidos.</p>
        <?php $toggle('orders_auto_accept','Aceitar pedidos automaticamente','Quando ativo, pedidos do cardápio avançam direto para Em preparo.','0'); ?>
        <?php $toggle('ifood_auto_accept','Aceitar iFood automaticamente','Use somente depois de validar a integração oficial.','0'); ?>
        <?php $toggle('skip_kds','Pular cozinha (sem KDS) — Plano PRÓ','Permite despachar o motoboy direto do status "Aceito", sem precisar que a cozinha marque como pronto. Ideal para adega, disk-água e deliverys sem preparo.','0'); ?>
        <div class="settings-grid"><label class="setting-field">Número inicial dos próximos pedidos<input type="number" min="1" name="s[order_initial]" value="<?= e(setting_get('order_initial','1')) ?>"></label><label class="setting-field">Prefixo visual<input name="s[order_prefix]" maxlength="8" value="<?= e(setting_get('order_prefix','#')) ?>"></label></div>
      <?php elseif($sec==='sounds'): ?>
        <h3>3. Som dos pedidos</h3><p>Escolha onde a campainha deve tocar.</p>
        <?php $toggle('sound_delivery','Pedidos para entrega','Tocar na central quando chegar um pedido delivery.','1'); ?>
        <?php $toggle('sound_local','Pedidos do salão','Tocar para pedidos de mesa, garçom e balcão.','1'); ?>
        <?php $toggle('sound_attendant','Chamado para atendente','Tocar quando o robô solicitar atendimento humano.','1'); ?>
        <label class="setting-field">Duração do alerta<select name="s[order_sound_style]"><option value="long" <?= setting_get('order_sound_style','long')==='long'?'selected':'' ?>>Campainha longa</option><option value="short" <?= setting_get('order_sound_style')==='short'?'selected':'' ?>>Campainha curta</option><option value="off" <?= setting_get('order_sound_style')==='off'?'selected':'' ?>>Sem som</option></select></label>
      <?php elseif($sec==='printing'): ?>
        <h3>4. Impressão</h3><p>Configure quando os pedidos devem ser enviados à impressão.</p>
        <?php $toggle('print_auto','Habilitar impressão automática','Imprime o pedido assim que ele for aceito.','0'); ?>
        <?php $toggle('print_kds','Imprimir também com o KDS aberto','Gera uma via mesmo quando a cozinha usa o display.','0'); ?>
        <?php $toggle('print_weight','Imprimir itens vendidos por peso','Inclui produtos conectados à balança.','0'); ?>
      <?php elseif($sec==='cancel'): ?>
        <h3>5. Cancelar pedido</h3><p>Proteja o cancelamento para evitar alterações acidentais.</p>
        <label class="radio-setting"><input type="radio" name="s[cancel_security]" value="none" <?= setting_get('cancel_security','none')==='none'?'checked':'' ?>><span><b>Não solicitar senha</b><small>Qualquer usuário autorizado consegue cancelar.</small></span></label>
        <label class="radio-setting"><input type="radio" name="s[cancel_security]" value="master" <?= setting_get('cancel_security')==='master'?'checked':'' ?>><span><b>Solicitar senha padrão</b><small>A senha será exigida antes de cancelar qualquer pedido.</small></span></label>
        <label class="setting-field">Senha padrão para cancelamento<input type="password" inputmode="numeric" name="s[cancel_pin]" value="<?= e(setting_get('cancel_pin','1234')) ?>"></label>
      <?php elseif($sec==='establishment'): ?>
        <h3>6. Estabelecimento</h3><p>Informações exibidas no cardápio, impressões e mensagens automáticas.</p>
        <div class="settings-grid"><label>Nome da loja<input name="s[store_name]" value="<?= e(setting_get('store_name',cfg('restaurante'))) ?>"></label><label>WhatsApp da loja<input name="s[store_phone]" inputmode="tel" value="<?= e(setting_get('store_phone','')) ?>" placeholder="(13) 99999-9999"></label></div>
        <div class="settings-grid"><label>Segmento<input name="s[store_segment]" value="<?= e(setting_get('store_segment','Restaurante / lanchonete')) ?>"></label><label>Instagram<input name="s[store_instagram]" value="<?= e(setting_get('store_instagram','')) ?>" placeholder="@sualoja"></label></div>
        <label class="setting-field">Descrição da loja<textarea name="s[store_description]" rows="3"><?= e(setting_get('store_description','Peça on-line com rapidez e segurança.')) ?></textarea></label>
        <?php $toggle('store_open','Loja aberta para pedidos','Quando desativado, o cardápio continua visível mas não aceita novos pedidos.','1'); ?>
      <?php elseif($sec==='delivery'): ?>
        <h3>7. Entrega</h3><p>Configure as formas e os prazos apresentados ao cliente.</p>
        <?php $toggle('delivery_enabled','Aceitar pedidos para entrega','Habilita endereço, taxa e despacho para motoboy.','1'); ?>
        <?php $toggle('pickup_enabled','Aceitar retirada no local','O cliente pode buscar o pedido no balcão.','1'); ?>
        <div class="settings-grid"><label>Prazo mínimo (minutos)<input type="number" min="0" name="s[delivery_min]" value="<?= e(setting_get('delivery_min','30')) ?>"></label><label>Prazo máximo (minutos)<input type="number" min="0" name="s[delivery_max]" value="<?= e(setting_get('delivery_max','50')) ?>"></label></div>
        <label class="setting-field">Pedido mínimo para entrega<input type="number" min="0" step="0.01" name="s[delivery_min_order]" value="<?= e(setting_get('delivery_min_order','0')) ?>"></label>
        <a class="save inline-link" href="?r=admin_areas">Configurar endereço, bairros e áreas no mapa</a>
      <?php elseif($sec==='cardapio'): ?>
        <h3>8. Marca e cores</h3><p>Dividido em dois blocos: a aparencia do seu <b>painel</b> (o que voce e sua equipe veem) e a aparencia do <b>cardapio</b> (o que seus clientes veem).</p>
        <style>
          .cfg-section{border:1px solid #e5eaef;border-radius:14px;padding:16px 18px;margin:0 0 20px;background:#fbfcfd}
          .cfg-section-title{margin:0 0 6px;font-size:15px;font-weight:800;color:#1f2b3a;display:flex;align-items:center;gap:8px;padding-bottom:11px;border-bottom:2px solid #eef2f6}
          .cfg-section-title small{font-weight:600;font-size:11px;color:#7a8794}
        </style>

        <?php
          // ---- Helpers de cor (definidos uma vez, usados nos dois blocos abaixo) ----
          $paletaCores=['#EE7430'=>'Laranja','#E23B3B'=>'Vermelho','#1B9E58'=>'Verde','#2F6BE0'=>'Azul','#8B5CF6'=>'Roxo','#E0447E'=>'Rosa','#0EA5A5'=>'Turquesa','#111827'=>'Grafite'];
          $paletaLateral=['#0D1320'=>'Azul-noite','#111827'=>'Grafite','#0B0B0D'=>'Preto','#10241B'=>'Verde-escuro','#22111A'=>'Vinho','#152036'=>'Marinho'];
          $renderPalette=function($campo,$atual,$lista){ ?>
            <div class="palette-picker">
              <?php foreach($lista as $hex=>$nome): ?>
                <button type="button" class="palette-swatch <?= strtolower($atual)===strtolower($hex)?'sel':'' ?>" style="background:<?= $hex ?>" title="<?= $nome ?>" onclick="setPal('<?= $campo ?>','<?= $hex ?>')"></button>
              <?php endforeach; ?>
              <label class="palette-custom">Personalizada<input type="color" id="pal_<?= $campo ?>" name="s[<?= $campo ?>]" value="<?= e($atual) ?>" oninput="markPal('<?= $campo ?>',this.value)"></label>
            </div>
          <?php };
          $txtOpts=function($campo,$label,$hint){
            $val=setting_get($campo,''); $auto=($val==='');
            ?>
            <div class="setting-field">
              <b style="font-size:13px"><?= $label ?></b>
              <small><?= e($hint) ?></small>
              <div class="txtcolor-row">
                <label class="txtcolor-opt"><input type="radio" name="txtmode_<?= $campo ?>" value="auto" <?= $auto?'checked':'' ?> onchange="txtMode('<?= $campo ?>','auto')"> Automático (contraste)</label>
                <label class="txtcolor-opt"><input type="radio" name="txtmode_<?= $campo ?>" value="branco" <?= strtolower($val)==='#ffffff'?'checked':'' ?> onchange="txtMode('<?= $campo ?>','#FFFFFF')"> Branca</label>
                <label class="txtcolor-opt"><input type="radio" name="txtmode_<?= $campo ?>" value="preto" <?= strtolower($val)==='#111111'?'checked':'' ?> onchange="txtMode('<?= $campo ?>','#111111')"> Preta</label>
                <label class="txtcolor-opt custom"><input type="radio" name="txtmode_<?= $campo ?>" value="custom" <?= (!$auto&&!in_array(strtolower($val),['#ffffff','#111111']))?'checked':'' ?> onchange="txtMode('<?= $campo ?>',document.getElementById('tc_<?= $campo ?>').value)"> Personalizada
                  <input type="color" id="tc_<?= $campo ?>" value="<?= e($val?:'#ffffff') ?>" oninput="document.querySelector('[name=txtmode_<?= $campo ?>][value=custom]').checked=true;txtMode('<?= $campo ?>',this.value)">
                </label>
              </div>
              <input type="hidden" name="s[<?= $campo ?>]" id="hid_<?= $campo ?>" value="<?= e($val) ?>">
            </div>
          <?php };
        ?>

        <!-- ============ BLOCO 1 · PAINEL (sistema) ============ -->
        <div class="cfg-section">
          <h4 class="cfg-section-title">&#128421; Aparencia do painel <small>· o que voce e sua equipe veem</small></h4>

          <div class="setting-field">
            <b style="font-size:13px">&#127991; Logo do restaurante</b>
            <small>Aparece no painel (canto superior esquerdo) e no topo do cardapio, no lugar da inicial. PNG com fundo transparente, quadrado (recomendado 256x256px).</small>
            <?php $logoAtual=setting_get('brand_logo',''); if($logoAtual): ?>
              <div class="logo-preview"><img src="<?= e($logoAtual) ?>" alt="Logo atual"><span>Logo atual</span></div>
              <label style="display:flex;align-items:center;gap:8px;font-weight:600;font-size:11px;margin-top:6px"><input type="checkbox" name="remover_logo" value="1" style="width:auto"> Remover a logo atual</label>
            <?php endif; ?>
            <input type="file" name="logo" accept=".jpg,.jpeg,.png,.webp,.svg">
          </div>

          <div class="setting-field">
            <b style="font-size:13px">&#128421; Cor do painel (sistema)</b>
            <small>Cor de destaque do painel administrativo: item ativo do menu, botoes principais.</small>
            <?php $renderPalette('panel_color', setting_get('panel_color','')?:'#EE7430', $paletaCores); ?>
          </div>

          <div class="setting-field">
            <b style="font-size:13px">&#127761; Cor da barra lateral</b>
            <small>Fundo do menu lateral do painel.</small>
            <?php $renderPalette('panel_side', setting_get('panel_side','')?:'#0D1320', $paletaLateral); ?>
          </div>

          <?php $txtOpts('panel_btn_text','&#9997; Cor do texto dos botões (painel)','Cor das letras dos botões e do item ativo do menu do painel.'); ?>
        </div>

        <!-- ============ BLOCO 2 · CARDAPIO (cliente) ============ -->
        <div class="cfg-section">
          <h4 class="cfg-section-title">&#127912; Aparencia do cardapio <small>· o que seus clientes veem</small></h4>

          <div class="setting-field">
            <b style="font-size:13px">&#127912; Cor do cardapio (cliente)</b>
            <small>Cor de destaque do cardapio do cliente: botoes, precos e realces.</small>
            <?php $renderPalette('brand_color', setting_get('brand_color','')?:'#EE7430', $paletaCores); ?>
          </div>

          <?php $txtOpts('menu_btn_text','&#9997; Cor das letras (cardápio)','Cor das letras nos botões, faixa de anúncios e realces do cardápio do cliente.'); ?>

          <div class="setting-field">
            <b style="font-size:13px">&#128444; Capa do cardapio (vitrine do comercio)</b>
            <small>Imagem de destaque no topo do cardapio, estilo pagina de apresentacao. Recomendado 1200x400px (JPG/PNG/WebP).</small>
            <?php $capaAtual=setting_get('menu_capa',''); if($capaAtual): ?>
              <div class="capa-preview"><img src="<?= e($capaAtual) ?>" alt="Capa atual"></div>
              <label style="display:flex;align-items:center;gap:8px;font-weight:600;font-size:11px;margin-top:6px"><input type="checkbox" name="remover_capa" value="1" style="width:auto"> Remover a capa atual</label>
            <?php endif; ?>
            <input type="file" name="capa" accept=".jpg,.jpeg,.png,.webp">
          </div>

          <div class="setting-field">
            <b style="font-size:13px">&#128247; Fundo do cardapio (imagem propria)</b>
            <small>Envie uma imagem para ser o fundo do cardapio inteiro. Ela cobre a pagina toda com um leve escurecimento para manter a leitura. Substitui o tema abaixo enquanto estiver ativa.</small>
            <?php $bgAtual=setting_get('menu_bg_image',''); if($bgAtual): ?>
              <div class="capa-preview"><img src="<?= e($bgAtual) ?>" alt="Fundo atual"></div>
              <label style="display:flex;align-items:center;gap:8px;font-weight:600;font-size:11px;margin-top:6px"><input type="checkbox" name="remover_menubg" value="1" style="width:auto"> Remover o fundo enviado (voltar ao tema)</label>
            <?php endif; ?>
            <input type="file" name="menubg" accept=".jpg,.jpeg,.png,.webp">
          </div>

          <div class="setting-field">
            <b style="font-size:13px">&#127761; Tema de fundo (se nao usar imagem propria)</b>
            <small>Clique no fundo que quiser. "Preto liso" e "Branco liso" definem a cor base. A previa grande mostra como fica.</small>
            <?php
              $tema=setting_get('menu_theme','gourmet');
              $temas=[
                'gourmet'=>['Gourmet','radial-gradient(60% 70% at 50% 0,rgba(238,116,48,.18),transparent 62%),#12161F','escuro'],
                'carvao'=>['Carvao','radial-gradient(60% 75% at 50% 0,rgba(238,116,48,.15),transparent 60%),#0C0E12','escuro'],
                'noite'=>['Noite Ambar','radial-gradient(45% 55% at 50% 0,rgba(247,160,90,.34),transparent 60%),#08090B','escuro'],
                'madeira'=>['Madeira','radial-gradient(60% 70% at 50% 0,rgba(238,116,48,.16),transparent 62%),#1A130A','escuro'],
                'bistro'=>['Bistro','radial-gradient(60% 70% at 50% 0,rgba(238,116,48,.12),transparent 62%),#0D1712','escuro'],
                'vinho'=>['Vinho','radial-gradient(65% 72% at 50% 0,rgba(214,80,84,.22),transparent 60%),#160A0E','escuro'],
                'escuro'=>['Preto liso','#0B0F14','preto'],
                'claro'=>['Marfim','linear-gradient(180deg,#F8F3EB,#F1E9DD)','claro'],
                'branco'=>['Branco liso','#FFFFFF','branco'],
              ];
              $temasJson=[]; foreach($temas as $k=>$t)$temasJson[$k]=['bg'=>$t[1],'base'=>$t[2],'nome'=>$t[0]];
            ?>
            <div class="theme-live">
              <div class="theme-live-prev" id="themeLivePrev">
                <div class="tl-top"><span class="tl-logo"></span><b class="tl-nm">Seu Restaurante</b></div>
                <div class="tl-chips"><span class="tl-chip on">Destaques</span><span class="tl-chip">Burgers</span></div>
                <div class="tl-card"><div><b>Smash Duplo</b><small>2 blends, cheddar e molho da casa</small></div><span class="tl-price">R$ 28,90</span></div>
              </div>
              <div class="theme-live-name" id="themeLiveName"></div>
            </div>
            <div class="theme-picker">
              <?php foreach($temas as $tv=>$t): $baseLight=in_array($t[2],['claro','branco']); ?>
                <label class="theme-opt <?= $tema===$tv?'sel':'' ?>" data-theme="<?= $tv ?>">
                  <input type="radio" name="s[menu_theme]" value="<?= $tv ?>" <?= $tema===$tv?'checked':'' ?> onchange="pickTheme('<?= $tv ?>')">
                  <span class="theme-prev" style="background:<?= $t[1] ?>"><span class="tp-card" style="<?= $baseLight?'background:#fff':'background:rgba(255,255,255,.10)' ?>"></span></span>
                  <span class="theme-name"><?= e($t[0]) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <label class="setting-field">&#128226; Anuncios que passam no topo do cardapio<textarea name="s[menu_banner]" rows="3" placeholder="Um anuncio por linha. Ex.:&#10;Entrega gratis acima de R$ 50&#10;Combo do dia com 20% OFF"><?= e(setting_get('menu_banner','')) ?></textarea><small>Escreva um anuncio por linha. Eles ficam passando automaticamente numa faixa no topo do cardapio. Deixe vazio para ocultar.</small></label>

          <label class="setting-field">Link do cardápio (para o robô do WhatsApp)<input name="s[cardapio_url]" value="<?= e(setting_get('cardapio_url','')) ?>" placeholder="Ex: https://pederv.com.br/cardapio/barbaburguer"><small style="color:#6b7280;font-size:11px">Usado pelo token <code>{LINK_CARDAPIO}</code> nas mensagens automáticas do robô.</small></label>

          <?php $toggle('customer_accounts','Permitir conta do cliente','Ativa cadastro com e-mail, senha, enderecos salvos e historico.','1'); ?>
          <?php $toggle('cart_recovery_capture','Registrar carrinhos abandonados','Prepara as compras esquecidas para o Recuperador de vendas.','1'); ?>
          <?php $toggle('menu_show_featured','Exibir destaques primeiro','Prioriza produtos e combos marcados como destaque.','1'); ?>
          <?php $toggle('menu_show_order_tracking','Mostrar Acompanhar pedido','Permite consultar a situacao sem entrar na conta.','1'); ?>
        </div>

        <script>
        function txtMode(campo,val){document.getElementById('hid_'+campo).value=(val==='auto')?'':val}
        const THEMES=<?= json_encode($temasJson,JSON_UNESCAPED_UNICODE) ?>;
        function pickTheme(k){
          document.querySelectorAll('.theme-opt').forEach(o=>o.classList.toggle('sel',o.dataset.theme===k));
          const t=THEMES[k];if(!t)return;const p=document.getElementById('themeLivePrev');
          p.style.background=t.bg;p.classList.toggle('light',['claro','branco'].includes(t.base));
          document.getElementById('themeLiveName').textContent='Fundo selecionado: '+t.nome;
        }
        function setPal(campo,hex){const i=document.getElementById('pal_'+campo);i.value=hex;markPal(campo,hex)}
        function markPal(campo,hex){const box=document.getElementById('pal_'+campo).closest('.palette-picker');box.querySelectorAll('.palette-swatch').forEach(s=>s.classList.toggle('sel',rgbToHex(s.style.background).toLowerCase()===hex.toLowerCase()))}
        function rgbToHex(rgb){if(!rgb)return '';if(rgb.charAt(0)==='#')return rgb;const m=rgb.match(/\d+/g);if(!m)return rgb;return '#'+m.slice(0,3).map(x=>(+x).toString(16).padStart(2,'0')).join('')}
        document.addEventListener('DOMContentLoaded',()=>pickTheme(<?= json_encode($tema) ?>));
        </script>
      <?php elseif($sec==='integrations'): ?>
        <h3>9. Integrações</h3><p>Acompanhe os canais externos sem misturar as configurações da operação.</p>
        <div class="integration-setting"><span class="integration-icon">W</span><div><b>WhatsApp · Evolution API</b><small><?= evolution_configured()?'Configuração informada. Use o teste para confirmar a conexão.':'Aguardando URL, instância e chave.' ?></small></div><a href="?r=admin_whatsapp">Configurar</a></div>
        <div class="integration-setting"><span class="integration-icon ifood">iF</span><div><b>iFood</b><small>Use o simulador até concluir o credenciamento e a integração oficial.</small></div><a href="?r=admin_ifood">Abrir</a></div>
        <div class="integration-setting"><span class="integration-icon">N</span><div><b>Webhook / n8n</b><small>Envie eventos de pedidos e entregas para suas automações.</small></div><a href="?r=admin_settings">Ajustes</a></div>
      <?php else: ?>
        <h3>10. Segurança e acessos</h3><p>Cada funcionário deve usar seu próprio login e enxergar somente sua função.</p>
        <?php $toggle('security_individual_login','Exigir acesso individual','Mantém o responsável registrado em pedidos, caixa e cancelamentos.','1'); ?>
        <?php $toggle('security_logout_shared','Mostrar botão Sair nos aplicativos','Facilita a troca de funcionário em celulares e tablets compartilhados.','1'); ?>
        <?php $toggle('security_audit','Registrar histórico de operações','Mantém mudanças de status e responsáveis para conferência.','1'); ?>
        <label class="setting-field">Tempo da sessão compartilhada (horas)<input type="number" min="1" max="168" name="s[security_session_hours]" value="<?= e(setting_get('security_session_hours','12')) ?>"></label>
        <a class="save inline-link" href="?r=admin_settings#usuarios">Gerenciar usuários e senhas</a>
      <?php endif; ?>
      <button class="save settings-save">Salvar alterações</button>
    </form></section>
  </div>
</div>
