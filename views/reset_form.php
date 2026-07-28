<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f4f6f8;padding:24px">
<div style="width:min(420px,100%);background:#fff;border-radius:18px;padding:36px 32px;box-shadow:0 20px 60px rgba(0,0,0,.1)">
  <h2 style="font-size:20px;font-weight:800;margin:0 0 6px">Nova senha</h2>
  <p style="font-size:13.5px;color:#6b7280;margin:0 0 22px">Crie uma nova senha de acesso ao painel.</p>
  <?php if(!empty($erro)): ?>
  <div style="background:#fdecea;border:1px solid #f5c2b8;color:#b3341f;border-radius:10px;padding:11px 13px;font-size:13px;margin-bottom:14px"><?= htmlspecialchars($erro) ?></div>
  <?php endif; ?>
  <form method="post" action="?r=saas_reset">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
    <input type="password" name="senha" placeholder="Nova senha (mín. 4 caracteres)" required minlength="4"
      style="width:100%;border:1px solid #ddd;border-radius:11px;padding:13px 14px;font-size:14px;font-family:inherit;margin-bottom:12px;box-sizing:border-box">
    <button type="submit"
      style="width:100%;background:#0797e5;color:#fff;border:none;border-radius:11px;padding:14px;font-size:15px;font-weight:700;cursor:pointer">
      Salvar nova senha
    </button>
  </form>
</div>
</div>
