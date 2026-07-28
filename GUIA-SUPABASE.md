# Guia — Migrar o banco para o Supabase (PostgreSQL)

O app já vem preparado para rodar em **SQLite** (padrão) **ou** **Supabase/PostgreSQL**,
escolhido por uma chave no `config.php`. O código das telas não muda: o schema do
Supabase inclui funções de compatibilidade (`datetime`, `date`, `strftime`) que
fazem as consultas existentes funcionarem no Postgres.

## Passo a passo

1. **Crie o projeto no Supabase** (supabase.com) e anote a senha do banco.

2. **Rode o schema:** no Supabase, vá em **SQL Editor → New query**, cole todo o
   conteúdo de `supabase-schema.sql` e clique em **Run**. Isso cria as tabelas e
   as funções de compatibilidade.

3. **Configure o `config.php`** (no servidor) com:
   ```php
   'db_driver' => 'pgsql',
   'db' => [
     'host'    => 'db.SEU-PROJETO.supabase.co',
     'port'    => '5432',        // ou 6543 (pooler)
     'dbname'  => 'postgres',
     'user'    => 'postgres',
     'pass'    => 'SUA_SENHA',
     'sslmode' => 'require',
   ],
   ```
   (Pegue os dados em Supabase → Project Settings → Database → Connection info.)

4. **Confirme a extensão pdo_pgsql no PHP** da hospedagem (na HostGator costuma
   estar disponível; se não, ative em "Selecionar versão do PHP → extensões").

5. **Abra o app.** No primeiro acesso ele cria o usuário admin (com a senha do
   `config.php`) e popula os produtos de exemplo. Pronto.

## Voltar para SQLite
Basta mudar `'db_driver' => 'sqlite'` no `config.php`.

## Migrar os dados que já existem (opcional)
Se quiser levar os dados atuais do SQLite para o Supabase, exporte cada tabela
para CSV e importe pelo Supabase (Table editor → Import), ou me peça um script de
migração dedicado. Como as datas são texto no mesmo formato nos dois bancos, os
dados são compatíveis.

## Observações técnicas
- As datas continuam gravadas como texto `YYYY-MM-DD HH:MM:SS` (fuso
  America/Sao_Paulo) para manter compatibilidade — ajuste o fuso no topo do
  `supabase-schema.sql` se precisar.
- `INSERT ... ON CONFLICT`, `lastInsertId` (via sequences SERIAL) e `substr`
  funcionam igual no Postgres.
- Recomendado testar os fluxos principais após migrar: criar pedido no cardápio,
  PDV com pagamento, abrir/fechar caixa, e o painel SaaS.
