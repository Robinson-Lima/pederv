<main class="customer-auth-page">
  <a class="customer-back" href="?r=menu">← Voltar ao cardápio</a>
  <section class="customer-auth-card">
    <div class="customer-brand"><?= e(mb_substr(cfg('restaurante'),0,1)) ?></div>
    <?php if(($mode??'login')==='register'): ?>
      <h1>Criar minha conta</h1><p>Seus endereços e pedidos ficam salvos para as próximas compras.</p>
      <?php if(!empty($erro)): ?><div class="auth-error"><?= e($erro) ?></div><?php endif; ?>
      <form method="post" action="?r=customer_register" class="customer-form">
        <label>Nome completo<input name="nome" required autocomplete="name"></label>
        <label>WhatsApp<input name="telefone" inputmode="tel" autocomplete="tel"></label>
        <label>E-mail<input name="email" required type="email" autocomplete="email"></label>
        <label>Senha<input name="senha" required type="password" minlength="6" autocomplete="new-password"></label>
        <div class="address-grid"><label>CEP<input name="cep" autocomplete="postal-code"></label><label>Rua / avenida<input name="endereco" autocomplete="address-line1"></label><label>Número<input name="numero"></label><label>Bairro<input name="bairro"></label><label>Cidade<input name="cidade"></label><label>UF<input name="uf" maxlength="2"></label></div>
        <label>Referência<input name="referencia" placeholder="Ex.: portão azul"></label>
        <button>Criar conta</button>
      </form>
      <p class="auth-switch">Já tem conta? <a href="?r=customer_login">Entrar</a></p>
    <?php else: ?>
      <h1>Entrar</h1><p>Acesse seus pedidos anteriores e use seu endereço salvo.</p>
      <?php if(!empty($erro)): ?><div class="auth-error"><?= e($erro) ?></div><?php endif; ?>
      <form method="post" action="?r=customer_login" class="customer-form"><label>E-mail<input name="email" required type="email" autocomplete="email"></label><label>Senha<input name="senha" required type="password" autocomplete="current-password"></label><button>Entrar</button></form>
      <p class="auth-switch">Primeira compra? <a href="?r=customer_register">Criar conta</a></p>
    <?php endif; ?>
  </section>
</main>
