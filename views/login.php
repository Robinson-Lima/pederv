<?php $formAction = strtok($_SERVER['REQUEST_URI']??'/', '?').'?r=login_do'; ?>
<form class="login" method="post" action="<?= htmlspecialchars($formAction) ?>">
  <h2><?= e($titulo ?? 'Entrar') ?></h2>
  <p>RVCARDÁPIOS · acesso restrito</p>
  <?php if(!empty($erro)): ?><div class="err"><?= e($erro) ?></div><?php endif; ?>
  <input type="hidden" name="role" value="<?= e($role) ?>">
  <input name="usuario" placeholder="<?= $role==='admin'?'E-mail ou usuário (deixe em branco para senha mestre)':'Usuário' ?>" autocomplete="username" <?= $role!=='admin'?'required':'' ?> autofocus>
  <input type="password" name="senha" placeholder="Senha" autocomplete="current-password" required>
  <button class="go">Entrar</button>
</form>
<?php if($role==='admin'): ?>
<p style="text-align:center;margin-top:14px;font-size:13px">
  <a href="?r=saas_forgot" style="color:#0797e5;text-decoration:none">Esqueci minha senha</a>
</p>
<?php endif; ?>
