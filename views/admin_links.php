<?php
$slug = current_slug();
?><div class="wrap dash"><?php
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host  = $_SERVER['HTTP_HOST'] ?? 'pederv.com.br';
$base  = $scheme.'://'.$host;

$links = [
  'cardapio' => [
    'label'   => '🛒 Cardápio',
    'desc'    => 'Link público para os clientes fazerem pedidos.',
    'url'     => $base.'/cardapio/'.$slug,
    'role'    => null,
    'usuario' => null,
  ],
  'painel' => [
    'label'   => '👨‍💼 Painel do Administrador',
    'desc'    => 'Acesso exclusivo do dono — gerencie pedidos, caixa e configurações.',
    'url'     => $base.'/painel/'.$slug,
    'role'    => 'admin',
    'usuario' => null,
  ],
  'cozinha' => [
    'label'   => '🧑‍🍳 Cozinha (KDS)',
    'desc'    => 'Display para a cozinha acompanhar e confirmar os pedidos.',
    'url'     => $base.'/cozinha/'.$slug,
    'role'    => 'cozinha',
    'usuario' => 'cozinha',
  ],
  'garcom' => [
    'label'   => '🧑‍🤝‍🧑 Garçom',
    'desc'    => 'App do garçom para anotar pedidos e acompanhar mesas.',
    'url'     => $base.'/garcom/'.$slug,
    'role'    => 'garcom',
    'usuario' => 'garcom',
  ],
  'motoboy' => [
    'label'   => '🛵 Motoboy',
    'desc'    => 'App do entregador para aceitar e confirmar entregas.',
    'url'     => $base.'/motoboy/'.$slug,
    'role'    => 'motoboy',
    'usuario' => 'motoboy',
  ],
  'caixa' => [
    'label'   => '💰 Caixa',
    'desc'    => 'Operação de caixa e PDV. Usa o mesmo link do painel.',
    'url'     => $base.'/painel/'.$slug,
    'role'    => 'caixa',
    'usuario' => 'caixa',
  ],
];

$roles_com_senha = ['cozinha','garcom','motoboy','caixa'];
$senhas = [];
foreach($roles_com_senha as $rl)
  $senhas[$rl] = setting_get('senha_visible_'.$rl, '');
?>
<div class="pg-head">
  <h1>🔗 Acessos e links</h1>
  <p>Copie e envie estes links para cada funcionário. Use os campos abaixo para definir a senha de cada função.</p>
</div>

<?php if(!empty($_GET['salvo'])): ?>
  <div class="flash ok">✅ Senha atualizada com sucesso.</div>
<?php endif; ?>
<?php if(!empty($_GET['erro'])): ?>
  <div class="flash err">❌ <?= e($_GET['erro']) ?></div>
<?php endif; ?>

<div class="links-grid">
<?php foreach($links as $key => $lk): ?>
  <?php $temSenha = $lk['role'] && in_array($lk['role'], $roles_com_senha); ?>
  <div class="link-card">
    <div class="link-card-head">
      <div>
        <strong><?= $lk['label'] ?></strong>
        <small><?= e($lk['desc']) ?></small>
      </div>
    </div>

    <div class="link-url-row">
      <input type="text" class="link-url-input" value="<?= e($lk['url']) ?>" readonly id="url-<?= $key ?>">
      <button type="button" class="btn-copy" onclick="copyLink('<?= $key ?>')">Copiar</button>
    </div>

    <?php if($temSenha): ?>
      <?php $sv = $senhas[$lk['role']]; ?>
      <div class="link-creds">
        <div class="cred-row">
          <span class="cred-label">Usuário</span>
          <code><?= e($lk['usuario']) ?></code>
        </div>
        <div class="cred-row">
          <span class="cred-label">Senha</span>
          <?php if($sv !== ''): ?>
            <span class="senha-mask" id="mask-<?= $key ?>">••••••••</span>
            <span class="senha-text" id="text-<?= $key ?>" style="display:none"><?= e($sv) ?></span>
            <button type="button" class="btn-reveal" onclick="toggleSenha('<?= $key ?>')">Mostrar</button>
          <?php else: ?>
            <em class="sem-senha">Não definida</em>
          <?php endif; ?>
        </div>
      </div>

      <details class="define-senha">
        <summary><?= $sv !== '' ? '🔑 Alterar senha' : '🔑 Definir senha' ?></summary>
        <form method="post" action="?r=admin_links_save" class="form-senha">
          <?= saas_csrf_field() ?>
          <input type="hidden" name="role" value="<?= e($lk['role']) ?>">
          <input type="hidden" name="usuario" value="<?= e($lk['usuario']) ?>">
          <div class="senha-form-row">
            <input type="text" name="nova_senha" placeholder="Nova senha" required minlength="4" autocomplete="off" class="senha-input">
            <button type="submit" class="btn-salvar">Salvar</button>
          </div>
        </form>
      </details>
    <?php elseif($key === 'cardapio'): ?>
      <div class="link-creds"><em class="sem-senha" style="color:var(--ok,#22c55e)">✓ Acesso público — sem senha necessária</em></div>
    <?php elseif($key === 'painel'): ?>
      <div class="link-creds"><em class="sem-senha">Use seu e-mail e senha de administrador</em></div>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
</div>

<style>
.pg-head{margin-bottom:1.5rem}
.pg-head h1{font-size:1.4rem;margin:0 0 .3rem}
.pg-head p{color:var(--muted);margin:0;font-size:.9rem}
.flash{padding:.7rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:.9rem}
.flash.ok{background:#dcfce7;color:#166534;border:1px solid #bbf7d0}
.flash.err{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
.links-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:1rem}
.link-card{background:var(--card,#fff);border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:1.1rem 1.2rem;display:flex;flex-direction:column;gap:.75rem}
.link-card-head{display:flex;align-items:flex-start;justify-content:space-between}
.link-card-head strong{display:block;font-size:1rem;margin-bottom:.15rem}
.link-card-head small{color:var(--muted);font-size:.8rem;line-height:1.4}
.link-url-row{display:flex;gap:.5rem}
.link-url-input{flex:1;font-size:.75rem;padding:.4rem .6rem;border:1px solid var(--border,#e5e7eb);border-radius:7px;background:var(--bg2,#f9fafb);color:var(--text);font-family:monospace;min-width:0}
.btn-copy{white-space:nowrap;padding:.4rem .85rem;background:var(--brand,#EE7430);color:#fff;border:none;border-radius:7px;cursor:pointer;font-size:.82rem;font-weight:600}
.btn-copy:hover{opacity:.87}
.link-creds{display:flex;flex-direction:column;gap:.35rem;padding:.55rem .7rem;background:var(--bg2,#f9fafb);border-radius:8px;border:1px solid var(--border,#e5e7eb)}
.cred-row{display:flex;align-items:center;gap:.6rem;font-size:.85rem}
.cred-label{color:var(--muted);width:50px;flex-shrink:0;font-size:.78rem}
.cred-row code{font-family:monospace;background:var(--border,#e5e7eb);padding:.1rem .4rem;border-radius:4px;font-size:.82rem}
.senha-mask,.senha-text{font-family:monospace;font-size:.87rem;letter-spacing:.05em}
.btn-reveal{margin-left:auto;font-size:.75rem;padding:.2rem .55rem;border:1px solid var(--border,#e5e7eb);background:transparent;border-radius:5px;cursor:pointer;color:var(--text)}
.btn-reveal:hover{background:var(--bg2)}
.sem-senha{font-size:.82rem;color:var(--muted);font-style:italic}
.define-senha{margin-top:-.1rem}
.define-senha summary{font-size:.82rem;color:var(--brand,#EE7430);cursor:pointer;list-style:none;padding:.15rem 0}
.define-senha summary::-webkit-details-marker{display:none}
.form-senha{margin-top:.5rem}
.senha-form-row{display:flex;gap:.5rem}
.senha-input{flex:1;padding:.42rem .65rem;border:1px solid var(--border,#e5e7eb);border-radius:7px;font-size:.85rem;min-width:0}
.btn-salvar{padding:.42rem .9rem;background:var(--brand,#EE7430);color:#fff;border:none;border-radius:7px;cursor:pointer;font-size:.85rem;font-weight:600}
.btn-salvar:hover{opacity:.87}
@media(max-width:600px){.links-grid{grid-template-columns:1fr}}
</style>
<script>
function copyLink(key){
  var el=document.getElementById('url-'+key);
  navigator.clipboard.writeText(el.value).then(function(){
    var btn=el.parentElement.querySelector('.btn-copy');
    var orig=btn.textContent;
    btn.textContent='✓ Copiado';
    setTimeout(function(){btn.textContent=orig;},1800);
  });
}
function toggleSenha(key){
  var mask=document.getElementById('mask-'+key);
  var txt=document.getElementById('text-'+key);
  var btn=mask.parentElement.querySelector('.btn-reveal');
  if(txt.style.display==='none'){
    mask.style.display='none'; txt.style.display=''; btn.textContent='Ocultar';
  } else {
    mask.style.display=''; txt.style.display='none'; btn.textContent='Mostrar';
  }
}
</script>
</div>
