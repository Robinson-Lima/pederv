<?php $catNome=[]; foreach($cats as $c) $catNome[$c['id']]=$c['nome']; ?>
<div class="wrap dash">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:4px">
    <h2><?= e(cfg('restaurante')) ?></h2>
    <a href="?r=admin_import" class="cxgo" style="font-size:13px;padding:7px 14px">📥 Importar de outra plataforma</a>
  </div>
  <?php if(!empty($_GET['importado'])): ?><div class="oknote"><?= (int)$_GET['importado'] ?> produtos importados com sucesso!</div><?php endif; ?>
  <?= admin_subnav('admin_produtos') ?>

  <div class="pgrid">
    <!-- FORM -->
    <div class="cfgcard">
      <h3><?= $edit?'✏️ Editar produto':'➕ Novo produto' ?></h3>
      <form method="post" action="?r=produto_salvar" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= $edit['id']??'' ?>">
        <input type="hidden" name="foto_atual" value="<?= e($edit['foto']??'') ?>">

        <label>Nome</label>
        <input name="nome" value="<?= e($edit['nome']??'') ?>" required>

        <label>Descrição</label>
        <textarea name="descricao" rows="2"><?= e($edit['descricao']??'') ?></textarea>

        <label>Tipo de cadastro</label>
        <select name="tipo" id="productType" onchange="toggleCombo()"><option value="produto">Produto individual</option><option value="combo" <?= ($edit['tipo']??'')==='combo'?'selected':'' ?>>Combo promocional</option></select>
        <div id="comboBox" class="combo-builder">
          <b>Itens que fazem parte do combo</b><small>O cliente compra o conjunto por um único preço.</small>
          <?php $comboIds=json_decode($edit['combo_itens']??'[]',true)?:[]; foreach($prods as $cp): if($edit&&$cp['id']==$edit['id'])continue; ?>
            <label><input type="checkbox" name="combo_itens[]" value="<?= $cp['id'] ?>" <?= in_array((int)$cp['id'],array_map('intval',$comboIds),true)?'checked':'' ?>> <?= e($cp['nome']) ?> · <?= money($cp['preco']) ?></label>
          <?php endforeach; ?>
        </div>

        <div class="fisc2">
          <div><label>Preço (R$)</label><input name="preco" value="<?= isset($edit['preco'])?number_format($edit['preco'],2,',',''):'' ?>" placeholder="0,00" required></div>
          <div><label>Categoria</label>
            <select name="category_id">
              <?php foreach($cats as $c): ?>
                <option value="<?= $c['id'] ?>" <?= (isset($edit['category_id'])&&$edit['category_id']==$c['id'])?'selected':'' ?>><?= e($c['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <label>Foto do produto</label>
        <?php if(!empty($edit['foto'])): ?><img src="<?= e($edit['foto']) ?>" style="width:70px;height:70px;object-fit:cover;border-radius:10px;margin-bottom:6px"><?php endif; ?>
        <input type="file" name="foto" accept="image/*">
        <div class="fisc2" style="margin-top:8px">
          <div><label>Emoji (se não tiver foto)</label><input name="emoji" value="<?= e($edit['emoji']??'🍔') ?>"></div>
          <div><label>&nbsp;</label>
            <label style="display:flex;align-items:center;gap:8px;font-weight:600">
              <input type="checkbox" name="destaque" value="1" <?= !empty($edit['destaque'])?'checked':'' ?> style="width:auto"> Destaque 🔥</label>
          </div>
        </div>

        <div style="border-top:1px solid var(--line);margin:14px 0;padding-top:12px">
          <b style="font-size:12px">📦 Estoque</b>
          <label style="display:flex;align-items:center;gap:8px;font-weight:600;margin:8px 0">
            <input type="checkbox" name="controla_estoque" value="1" <?= !empty($edit['controla_estoque'])?'checked':'' ?> style="width:auto"> Controlar estoque deste produto</label>
          <div class="fisc2">
            <div><label>Qtd em estoque</label><input name="estoque" type="number" value="<?= $edit['estoque']??0 ?>"></div>
            <div><label>Estoque mínimo (alerta)</label><input name="estoque_minimo" type="number" value="<?= $edit['estoque_minimo']??0 ?>"></div>
          </div>
        </div>

        <details>
          <summary style="cursor:pointer;font-size:12px;font-weight:700;color:var(--muted)">Dados fiscais (NCM/CFOP/CST)</summary>
          <div class="fisc2" style="margin-top:8px">
            <div><label>NCM</label><input name="ncm" value="<?= e($edit['ncm']??'21069090') ?>"></div>
            <div><label>CFOP</label><input name="cfop" value="<?= e($edit['cfop']??'5102') ?>"></div>
          </div>
          <div class="fisc2">
            <div><label>CST/CSOSN</label><input name="cst" value="<?= e($edit['cst']??'102') ?>"></div>
            <div><label>Unidade</label><input name="unidade" value="<?= e($edit['unidade']??'UN') ?>"></div>
          </div>
        </details>

        <label style="display:flex;align-items:center;gap:8px;font-weight:600;margin-top:10px">
          <input type="checkbox" name="ativo" value="1" <?= (!isset($edit)||!empty($edit['ativo']))?'checked':'' ?> style="width:auto"> Produto ativo (aparece no cardápio)</label>

        <button class="save" style="margin-top:12px"><?= $edit?'Salvar alterações':'Adicionar produto' ?></button>
        <?php if($edit): ?><a href="?r=admin_produtos" style="display:inline-block;margin-left:10px;font-size:13px;color:var(--muted)">cancelar</a><?php endif; ?>
      </form>

      <div style="border-top:1px solid var(--line);margin-top:16px;padding-top:14px">
        <b style="font-size:12px">Nova categoria</b>
        <form method="post" action="?r=categoria_salvar" style="display:flex;gap:8px;margin-top:8px">
          <input name="nome" placeholder="Ex: Sobremesas" style="flex:1;border:1px solid var(--line);border-radius:9px;padding:9px">
          <button class="cxgo or">+ Add</button>
        </form>
      </div>
    </div>

    <!-- LISTA -->
    <div>
      <div class="paysec">🍔 Produtos cadastrados (<?= count($prods) ?>)</div>
      <div class="chips" style="position:static;background:transparent;padding:0 0 10px">
        <div class="chip on" onclick="filtProd('all',this)">Todos</div>
        <?php foreach($cats as $c): ?>
          <div class="chip" onclick="filtProd('pc<?= $c['id'] ?>',this)"><?= e($c['nome']) ?></div>
        <?php endforeach; ?>
      </div>
      <?php foreach($prods as $p): $low = $p['controla_estoque'] && $p['estoque']<=$p['estoque_minimo']; ?>
        <div class="prodrow pc<?= $p['category_id'] ?> <?= $p['ativo']?'':'off' ?>">
          <div class="thumb"><?php if(!empty($p['foto'])): ?><img src="<?= e($p['foto']) ?>"><?php else: ?><span><?= e($p['emoji']) ?></span><?php endif; ?></div>
          <div class="pw">
            <div class="pn"><?= e($p['nome']) ?> <?= ($p['tipo']??'produto')==='combo'?'<span class="combo-label">COMBO</span>':'' ?> <?= !empty($p['destaque'])?'🔥':'' ?></div>
            <div class="pm"><?= e($catNome[$p['category_id']]??'—') ?> · <?= money($p['preco']) ?>
              <?php if($p['controla_estoque']): ?> · <span style="color:<?= $low?'#c8342b':'#22A06B' ?>">estoque: <?= (int)$p['estoque'] ?><?= $low?' ⚠':'' ?></span><?php endif; ?>
            </div>
          </div>
          <div class="pa">
            <button class="tgl <?= $p['ativo']?'on':'' ?>" onclick="toggleP(<?= $p['id'] ?>,this)"><?= $p['ativo']?'ativo':'off' ?></button>
            <a href="?r=admin_produtos&edit=<?= $p['id'] ?>" class="ed">editar</a>
            <form method="post" action="?r=produto_excluir" style="display:inline" onsubmit="return confirm('Excluir <?= e(addslashes($p['nome'])) ?>?')">
              <input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="del">🗑 Excluir</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<script>
async function toggleP(id,el){
  const r=await fetch('?r=produto_toggle',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+id}).then(x=>x.json());
  el.classList.toggle('on',r.ativo==1); el.textContent=r.ativo==1?'ativo':'off';
  el.closest('.prodrow').classList.toggle('off',r.ativo!=1);
}
function filtProd(cls,el){
  document.querySelectorAll('.pgrid .chip').forEach(c=>c.classList.remove('on')); el.classList.add('on');
  document.querySelectorAll('.prodrow').forEach(p=>{
    p.style.display=(cls==='all'||p.classList.contains(cls))?'flex':'none';
  });
}
function toggleCombo(){document.getElementById('comboBox').style.display=document.getElementById('productType').value==='combo'?'grid':'none'}toggleCombo();
</script>
