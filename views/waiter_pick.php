<div class="mhead"><div class="mh"><div class="lg">🧑‍🍳</div>
  <div><h2>Quem é você?</h2><div class="meta">Selecione o atendente</div></div></div></div>
<div style="max-width:420px;margin:0 auto;padding:16px">
  <?php if($garcons): foreach($garcons as $g): ?>
    <form method="post" action="?r=waiter_nome" style="margin-bottom:9px">
      <input type="hidden" name="nome" value="<?= e($g['nome']) ?>">
      <button class="gbtn" style="width:100%;background:#171C25;color:#fff"><?= e($g['nome']) ?></button>
    </form>
  <?php endforeach; endif; ?>
  <form method="post" action="?r=waiter_nome" style="display:flex;gap:8px;margin-top:10px">
    <input name="nome" placeholder="Ou digite seu nome" required
      style="flex:1;background:#171C25;border:1px solid #252B35;border-radius:10px;padding:12px;color:#fff">
    <button class="gbtn" style="background:#EE7430;color:#111;margin:0;width:auto;padding:0 18px">Entrar</button>
  </form>
  <p style="color:#8A909C;font-size:11px;margin-top:10px">Cadastre os garçons em ⚙ Config para aparecerem na lista.</p>
</div>
