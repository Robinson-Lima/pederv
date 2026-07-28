<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f4f6f8;padding:24px">
<div style="width:min(420px,100%);background:#fff;border-radius:18px;padding:36px 32px;box-shadow:0 20px 60px rgba(0,0,0,.1);text-align:center">
  <div style="width:60px;height:60px;background:#e7f8ef;border-radius:50%;display:grid;place-items:center;font-size:28px;margin:0 auto 18px">✓</div>
  <h2 style="font-size:20px;font-weight:800;margin:0 0 10px">Verifique seu e-mail</h2>
  <p style="font-size:14px;color:#6b7280;line-height:1.6;margin:0 0 22px">
    Se este e-mail estiver cadastrado, você receberá um link para redefinir sua senha nos próximos minutos.<br><br>
    Verifique também sua caixa de spam.
  </p>
  <?php if(!empty($link)): ?>
  <div style="background:#fef9e7;border:1px solid #f59e0b;border-radius:10px;padding:14px 16px;margin-bottom:20px;text-align:left">
    <p style="font-size:12px;font-weight:700;color:#92400e;margin:0 0 8px">Não recebeu o e-mail? Use este link direto:</p>
    <a href="<?= htmlspecialchars($link) ?>" style="font-size:12px;color:#0797e5;word-break:break-all;line-height:1.5"><?= htmlspecialchars($link) ?></a>
    <p style="font-size:11px;color:#78716c;margin:8px 0 0">Válido por 1 hora.</p>
  </div>
  <?php endif; ?>
  <a href="javascript:history.back()" style="color:#0797e5;font-size:13px;text-decoration:none">← Voltar ao login</a>
</div>
</div>
