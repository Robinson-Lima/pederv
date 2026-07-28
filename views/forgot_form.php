<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f4f6f8;padding:24px">
<div style="width:min(420px,100%);background:#fff;border-radius:18px;padding:36px 32px;box-shadow:0 20px 60px rgba(0,0,0,.1)">
  <h2 style="font-size:20px;font-weight:800;margin:0 0 6px">Recuperar senha</h2>
  <p style="font-size:13.5px;color:#6b7280;margin:0 0 22px">Digite o e-mail cadastrado. Enviaremos o link de recuperação.</p>
  <form method="post" action="?r=saas_forgot">
    <input type="email" name="email" placeholder="Seu e-mail de acesso" required autocomplete="email"
      style="width:100%;border:1px solid #ddd;border-radius:11px;padding:13px 14px;font-size:14px;font-family:inherit;margin-bottom:12px;box-sizing:border-box">
    <button type="submit"
      style="width:100%;background:#0797e5;color:#fff;border:none;border-radius:11px;padding:14px;font-size:15px;font-weight:700;cursor:pointer">
      Enviar link de recuperação
    </button>
  </form>
  <p style="text-align:center;margin-top:18px;font-size:13px">
    <a href="javascript:history.back()" style="color:#0797e5;text-decoration:none">← Voltar ao login</a>
  </p>
</div>
</div>
