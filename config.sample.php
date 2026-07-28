<?php
// ============================================================
// config.sample.php — modelo do config.php
// Copie para config.php no servidor e preencha. NÃO suba config.php ao Git.
// Mantenha as chaves que você já usa; abaixo estão só as relacionadas ao banco.
// ============================================================
return [
  // ... suas chaves atuais (restaurante, app_name, admin_senha, etc.) ...

  // ---------- Banco de dados ----------
  // 'sqlite' (padrão, arquivo local) OU 'pgsql' (Supabase / PostgreSQL)
  'db_driver' => 'sqlite',

  // Preencha quando db_driver = 'pgsql'. Pegue em Supabase → Project Settings →
  // Database → Connection info (use a porta 5432, ou 6543 para o pooler).
  'db' => [
    'host'    => 'db.SEU-PROJETO.supabase.co',
    'port'    => '5432',
    'dbname'  => 'postgres',
    'user'    => 'postgres',
    'pass'    => 'SUA_SENHA_DO_BANCO',
    'sslmode' => 'require',
  ],

  // Senha do painel SaaS (?r=admin). Também pode ser trocada dentro do painel.
  'saas_senha' => 'rvsaas',
];
