<div class="saas-login-card">
  <img src="assets/pederv-logo.png" style="width:80px;height:80px;object-fit:contain;margin:0 auto 16px;display:block">
  <h1>PedeRV SaaS</h1>
  <p>Acesso restrito à administração da plataforma.</p>
  <?php if(!empty($erro)): ?><div class="saas-err"><?= e($erro) ?></div><?php endif; ?>
  <form method="post" action="?r=saas_login">
    <label>Senha de acesso<input type="password" name="senha" autofocus placeholder="••••••••" required></label>
    <button type="submit">Entrar</button>
  </form>
  <small class="saas-login-hint">Central SaaS · PedeRV</small>
</div>
