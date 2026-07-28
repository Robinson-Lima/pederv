<?php
// Gera o bookmarklet que extrai Pinia do Anota AI e copia pro clipboard
$bm = "(function(){try{var p=document.querySelector('#app').__vue_app__.config.globalProperties.\$pinia.state.value;var m=p.menu&&p.menu.menu&&p.menu.menu.menu;if(!m||!m.length){alert('Cardápio não encontrado. Aguarde a página carregar completamente e tente de novo.');return;}var e=p.establishment&&p.establishment.establishment;var d=JSON.stringify({source:'pinia',name:e&&e.page?e.page.name:'',menu:m});var ta=document.createElement('textarea');ta.value=d;ta.style.cssText='position:fixed;top:-9999px';document.body.appendChild(ta);ta.select();document.execCommand('copy');ta.remove();alert('✅ Dados copiados! Volte ao painel do PedeRV → Importar cardápio → cole na área de texto e clique Importar.');}catch(ex){alert('Erro: '+ex.message+'\\n\\nCertifique-se de estar na página do cardápio do Anota AI com tudo carregado.');}})();";
$bmHref = 'javascript:'.rawurlencode($bm);
?>
<div class="wrap">
<div class="page-title-row">
  <div><h2>📥 Importar cardápio</h2><p>Anota AI e outros sistemas.</p></div>
  <a class="cxgo" href="?r=admin_produtos">← Voltar ao cardápio</a>
</div>

<?php if(!empty($erroImport)): ?>
<div class="oknote" style="background:#fff3cd;color:#6d4c00;border:1.5px solid #f0c040;margin-bottom:18px"><?= e($erroImport) ?></div>
<?php endif; ?>

<?php if(empty($preview)): ?>

<div class="imp-card">

  <!-- Anota AI: bookmarklet + colar -->
  <div class="imp-section">
    <div class="imp-badge">Anota AI</div>
    <h3 style="margin:0 0 4px">Importar do Anota AI</h3>
    <p style="color:#555;font-size:14px;margin:0 0 16px">Instale o botão abaixo <b>uma vez</b>. Depois, abra qualquer restaurante no Anota AI e clique nele.</p>

    <div style="background:#F0F4FF;border-radius:14px;padding:18px 20px;margin-bottom:16px">
      <b style="font-size:13px;display:block;margin-bottom:10px">① Instale o botão (só uma vez)</b>
      <p style="font-size:13px;color:#555;margin:0 0 12px">Arraste o botão abaixo até a barra de favoritos do Chrome:</p>
      <a class="imp-bm-bigbtn" href="<?= htmlspecialchars($bmHref) ?>" id="bmBtn">📥 Importar Anota AI</a>
      <p style="font-size:12px;color:#888;margin:10px 0 0">Ou pressione <kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>B</kbd> para mostrar a barra de favoritos primeiro.</p>
    </div>

    <div style="background:#F0FFF4;border-radius:14px;padding:18px 20px">
      <b style="font-size:13px;display:block;margin-bottom:10px">② Para cada restaurante que quiser importar</b>
      <div style="display:flex;align-items:flex-start;gap:12px;flex-wrap:wrap">
        <div class="imp-use-step" style="flex:1;min-width:130px">
          <div class="imp-use-icon">🌐</div>
          <b style="font-size:12px">Abra o restaurante</b><br>
          <small>pedido.anota.ai/loja/...</small>
        </div>
        <div class="imp-use-sep">→</div>
        <div class="imp-use-step" style="flex:1;min-width:130px">
          <div class="imp-use-icon">📥</div>
          <b style="font-size:12px">Clique no botão</b><br>
          <small>nos favoritos do Chrome</small>
        </div>
        <div class="imp-use-sep">→</div>
        <div class="imp-use-step" style="flex:1;min-width:130px">
          <div class="imp-use-icon">📋</div>
          <b style="font-size:12px">Dados copiados!</b><br>
          <small>Cole aqui embaixo</small>
        </div>
      </div>
    </div>

    <form method="post" action="?r=admin_import" id="pasteForm" style="margin-top:16px">
      <textarea name="manual_json" id="pasteArea" rows="3"
        placeholder="Cole aqui os dados copiados pelo botão (Ctrl+V)..."
        style="width:100%;box-sizing:border-box;font-family:monospace;font-size:12px;border:2px dashed #c8d0d8;border-radius:10px;padding:12px;resize:vertical;background:#f8fafc"></textarea>
      <button class="cxgo" id="pasteBtn" style="margin-top:8px;width:100%;font-size:15px;padding:12px">✅ Importar cardápio</button>
    </form>
  </div>

  <div class="imp-divider"><span>ou para outros sistemas</span></div>

  <!-- Outros sistemas: URL automática -->
  <div class="imp-section" style="padding-bottom:0">
    <div class="imp-badge">Goomer · Yooga · outros</div>
    <h3 style="margin:0 0 8px">Cole o link do restaurante</h3>
    <form method="post" action="?r=admin_import" id="importForm" style="display:flex;gap:8px;flex-wrap:wrap">
      <input name="url" type="url" id="urlInput"
        placeholder="https://goomer.app/r/seu-restaurante"
        value="<?= e($_POST['url']??'') ?>"
        style="flex:1;min-width:220px;padding:10px 14px;border:2px solid #E0E8FF;border-radius:10px">
      <button class="cxgo" id="importBtn">🔍 Buscar</button>
    </form>
  </div>

</div>

<?php else: ?>
<!-- PREVIEW -->
<div class="import-preview">
  <div class="import-preview-header">
    <div>
      <h3>📋 <?= e($previewMeta['nome']??'Cardápio importado') ?></h3>
      <p><?= count($preview) ?> categorias · <?= array_sum(array_column($preview,'count')) ?> produtos</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a href="?r=admin_import" class="cxgo" style="background:#fff;color:#333;border:1px solid #ccc">← Importar outro</a>
      <form method="post" action="?r=admin_import_do" style="margin:0">
        <input type="hidden" name="data" value="<?= htmlspecialchars(json_encode($preview,JSON_UNESCAPED_UNICODE),ENT_QUOTES) ?>">
        <button class="cxgo" onclick="return confirm('Importar <?= array_sum(array_column($preview,'count')) ?> produtos para o cardápio?')">✅ Confirmar importação</button>
      </form>
    </div>
  </div>
  <?php foreach($preview as $cat): ?>
  <details class="import-cat" open>
    <summary><b><?= e($cat['nome']) ?></b><span>(<?= count($cat['itens']) ?> itens)</span></summary>
    <table class="import-table">
      <thead><tr><th>Produto</th><th>Descrição</th><th>Preço</th></tr></thead>
      <tbody>
        <?php foreach($cat['itens'] as $it): ?>
        <tr>
          <td><b><?= e($it['nome']) ?></b></td>
          <td style="color:#666;font-size:12px"><?= e(mb_substr($it['descricao']??'',0,120)) ?></td>
          <td style="white-space:nowrap">R$ <?= number_format($it['preco']??0,2,',','.') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </details>
  <?php endforeach; ?>
</div>
<?php endif; ?>
</div>
<script>
document.getElementById('importForm')?.addEventListener('submit',function(){
  document.getElementById('importBtn').disabled=true;
  document.getElementById('importBtn').textContent='⏳ Buscando...';
});
document.getElementById('pasteForm')?.addEventListener('submit',function(e){
  if(!document.getElementById('pasteArea').value.trim()){
    e.preventDefault();
    document.getElementById('pasteArea').focus();
    document.getElementById('pasteArea').style.borderColor='#E2483D';
    return;
  }
  document.getElementById('pasteBtn').disabled=true;
  document.getElementById('pasteBtn').textContent='⏳ Processando...';
});
// Bloqueia clique esquerdo no bookmarklet
document.getElementById('bmBtn')?.addEventListener('click',function(e){
  e.preventDefault();
  alert('Arraste este botão até a barra de favoritos do Chrome (não clique — arraste para cima).');
});
</script>
