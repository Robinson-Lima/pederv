-- ============================================================
-- RV CARDÁPIOS — Schema para Supabase / PostgreSQL
-- Rode este arquivo UMA VEZ no Supabase: SQL Editor → New query → cole tudo → Run.
-- Depois configure o config.php do app (db_driver = 'pgsql'). Veja GUIA-SUPABASE.md.
-- ============================================================

-- Fuso de referência (ajuste se necessário)
-- Todas as datas são gravadas como TEXTO no formato 'YYYY-MM-DD HH24:MI:SS'
-- para manter compatibilidade com o código que já existe.

-- ------------------------------------------------------------
-- Funções de compatibilidade com o SQLite
-- (permitem que o código do app rode sem reescrever as consultas)
-- ------------------------------------------------------------
CREATE OR REPLACE FUNCTION datetime(a text, b text DEFAULT '') RETURNS text AS $$
  SELECT to_char(now() AT TIME ZONE 'America/Sao_Paulo','YYYY-MM-DD HH24:MI:SS');
$$ LANGUAGE sql STABLE;

CREATE OR REPLACE FUNCTION date(a text, b text) RETURNS text AS $$
  SELECT to_char(now() AT TIME ZONE 'America/Sao_Paulo','YYYY-MM-DD');
$$ LANGUAGE sql STABLE;

CREATE OR REPLACE FUNCTION date(a text) RETURNS text AS $$
  SELECT CASE
    WHEN a = 'now' THEN to_char(now() AT TIME ZONE 'America/Sao_Paulo','YYYY-MM-DD')
    WHEN a IS NULL OR a = '' THEN NULL
    ELSE to_char((a)::timestamp,'YYYY-MM-DD')
  END;
$$ LANGUAGE sql STABLE;

-- strftime('%Y-%m', coluna)  e  strftime('%Y-%m','now','localtime')
CREATE OR REPLACE FUNCTION strftime(fmt text, a text) RETURNS text AS $$
  SELECT CASE
    WHEN a = 'now' THEN to_char(now() AT TIME ZONE 'America/Sao_Paulo', replace(replace(fmt,'%Y','YYYY'),'%m','MM'))
    WHEN a IS NULL OR a = '' THEN NULL
    ELSE to_char((a)::timestamp, replace(replace(fmt,'%Y','YYYY'),'%m','MM'))
  END;
$$ LANGUAGE sql STABLE;

CREATE OR REPLACE FUNCTION strftime(fmt text, a text, b text) RETURNS text AS $$
  SELECT to_char(now() AT TIME ZONE 'America/Sao_Paulo', replace(replace(fmt,'%Y','YYYY'),'%m','MM'));
$$ LANGUAGE sql STABLE;

-- Valor padrão de data/hora usado nas colunas
CREATE OR REPLACE FUNCTION rv_now() RETURNS text AS $$
  SELECT to_char(now() AT TIME ZONE 'America/Sao_Paulo','YYYY-MM-DD HH24:MI:SS');
$$ LANGUAGE sql STABLE;

-- ------------------------------------------------------------
-- Tabelas
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories(
  id SERIAL PRIMARY KEY, nome text, ordem integer DEFAULT 0
);

CREATE TABLE IF NOT EXISTS products(
  id SERIAL PRIMARY KEY,
  category_id integer, nome text, descricao text,
  preco real, emoji text DEFAULT '🍔', destaque integer DEFAULT 0, ativo integer DEFAULT 1,
  ncm text DEFAULT '21069090', cfop text DEFAULT '5102', cst text DEFAULT '102',
  origem text DEFAULT '0', unidade text DEFAULT 'UN', foto text DEFAULT '',
  estoque integer DEFAULT 0, controla_estoque integer DEFAULT 0, estoque_minimo integer DEFAULT 0,
  tipo text DEFAULT 'produto', combo_itens text DEFAULT ''
);

CREATE TABLE IF NOT EXISTS orders(
  id SERIAL PRIMARY KEY,
  codigo text, canal text DEFAULT 'cardapio', tipo text DEFAULT 'entrega',
  cliente_nome text, cliente_fone text, endereco text, mesa text,
  status text DEFAULT 'novo', total real DEFAULT 0,
  pagamento_metodo text DEFAULT 'pix', pagamento_status text DEFAULT 'pendente',
  courier_id integer,
  criado_em text DEFAULT rv_now(), atualizado_em text DEFAULT rv_now(),
  bairro text DEFAULT '', referencia text DEFAULT '', aceito integer DEFAULT 0,
  ocorrencia text DEFAULT '', ocorrencia_obs text DEFAULT '',
  atendente text DEFAULT '', fechado_por text DEFAULT '', recebido_por text DEFAULT '',
  fechado_em text, lat real, lng real, zona text DEFAULT '',
  customer_id integer, simulacao integer DEFAULT 0,
  pagamento_provider_id text DEFAULT '', pagamento_detectado_em text, pagamento_origem text DEFAULT '',
  acerto_status text DEFAULT '', acerto_em text, acerto_por text DEFAULT '',
  valor_recebido real DEFAULT 0, troco real DEFAULT 0
);

CREATE TABLE IF NOT EXISTS order_items(
  id SERIAL PRIMARY KEY, order_id integer, nome text, qtd integer, preco real
);

CREATE TABLE IF NOT EXISTS order_status_events(
  id SERIAL PRIMARY KEY, order_id integer, status text, ator text, criado_em text DEFAULT rv_now()
);

CREATE TABLE IF NOT EXISTS couriers(
  id SERIAL PRIMARY KEY, nome text, status text DEFAULT 'livre',
  telefone text DEFAULT '', user_id integer, online integer DEFAULT 0, online_em text
);

CREATE TABLE IF NOT EXISTS tables_(
  id SERIAL PRIMARY KEY, numero text, status text DEFAULT 'livre'
);

CREATE TABLE IF NOT EXISTS caixa(
  id SERIAL PRIMARY KEY, saldo_inicial real DEFAULT 0, saldo_informado real,
  status text DEFAULT 'aberto', aberto_em text DEFAULT rv_now(), fechado_em text,
  operador text DEFAULT ''
);

CREATE TABLE IF NOT EXISTS caixa_mov(
  id SERIAL PRIMARY KEY, caixa_id integer, tipo text, categoria text, descricao text,
  valor real, criado_em text DEFAULT rv_now(), order_id integer
);

CREATE TABLE IF NOT EXISTS settings(
  chave text PRIMARY KEY, valor text
);

CREATE TABLE IF NOT EXISTS bot_replies(
  id SERIAL PRIMARY KEY, gatilho text, resposta text, ativo integer DEFAULT 1
);

CREATE TABLE IF NOT EXISTS notas(
  id SERIAL PRIMARY KEY, order_id integer, numero integer, serie text, modelo text DEFAULT '65',
  chave text, protocolo text, status text DEFAULT 'pendente',
  ambiente text, motivo text, xml_path text, danfe_url text,
  valor real, criado_em text DEFAULT rv_now()
);

CREATE TABLE IF NOT EXISTS push_subscriptions(
  id SERIAL PRIMARY KEY, user_id integer, endpoint text NOT NULL UNIQUE,
  p256dh text NOT NULL, auth text NOT NULL, criado_em text DEFAULT rv_now()
);

CREATE TABLE IF NOT EXISTS delivery_zones(
  id SERIAL PRIMARY KEY, nome text, tipo text DEFAULT 'entrega', taxa real DEFAULT 0,
  poligono text, ativo integer DEFAULT 1, criado_em text DEFAULT rv_now()
);

CREATE TABLE IF NOT EXISTS delivery_areas(
  id SERIAL PRIMARY KEY, bairro text, taxa real DEFAULT 0
);

CREATE TABLE IF NOT EXISTS operadores(
  id SERIAL PRIMARY KEY, nome text, pin text DEFAULT '', ativo integer DEFAULT 1
);

CREATE TABLE IF NOT EXISTS garcons(
  id SERIAL PRIMARY KEY, nome text, ativo integer DEFAULT 1
);

CREATE TABLE IF NOT EXISTS users(
  id SERIAL PRIMARY KEY,
  nome text NOT NULL, usuario text NOT NULL UNIQUE, senha_hash text NOT NULL, role text NOT NULL,
  telefone text DEFAULT '', ativo integer DEFAULT 1, criado_em text DEFAULT rv_now()
);

CREATE TABLE IF NOT EXISTS customers(
  id SERIAL PRIMARY KEY,
  nome text NOT NULL, telefone text DEFAULT '', endereco text DEFAULT '', bairro text DEFAULT '',
  pedidos integer DEFAULT 0, total_gasto real DEFAULT 0, ultimo_pedido text DEFAULT rv_now(),
  email text DEFAULT '', senha_hash text DEFAULT '', numero text DEFAULT '', referencia text DEFAULT '',
  cep text DEFAULT '', cidade text DEFAULT '', uf text DEFAULT ''
);
CREATE UNIQUE INDEX IF NOT EXISTS customers_email_uq ON customers(email) WHERE email <> '';

CREATE TABLE IF NOT EXISTS whatsapp_contacts(
  id SERIAL PRIMARY KEY, wa_id text NOT NULL UNIQUE, nome text DEFAULT '', atualizado_em text DEFAULT rv_now()
);

CREATE TABLE IF NOT EXISTS whatsapp_messages(
  id SERIAL PRIMARY KEY, wa_id text NOT NULL, direcao text NOT NULL,
  texto text DEFAULT '', status text DEFAULT 'recebida', criado_em text DEFAULT rv_now()
);

CREATE TABLE IF NOT EXISTS abandoned_carts(
  id SERIAL PRIMARY KEY, token text NOT NULL UNIQUE, customer_id integer,
  nome text DEFAULT '', telefone text DEFAULT '', email text DEFAULT '',
  itens_json text DEFAULT '[]', total real DEFAULT 0,
  status text DEFAULT 'aberto', aviso_enviado_em text, convertido_order_id integer,
  criado_em text DEFAULT rv_now(), atualizado_em text DEFAULT rv_now()
);

-- SaaS (painel do dono da plataforma)
CREATE TABLE IF NOT EXISTS saas_clients(
  id SERIAL PRIMARY KEY,
  restaurante text NOT NULL, responsavel text DEFAULT '',
  email text DEFAULT '', whatsapp text DEFAULT '',
  dominio text DEFAULT '', cidade text DEFAULT '', uf text DEFAULT '',
  plano text DEFAULT 'pro', status text DEFAULT 'trial',
  valor_mensal real DEFAULT 0, dia_vencimento integer DEFAULT 10,
  trial_ate text, proximo_venc text,
  bloqueado_em text, bloqueio_manual integer DEFAULT 0, obs text DEFAULT '',
  criado_em text DEFAULT rv_now(), atualizado_em text DEFAULT rv_now()
);

CREATE TABLE IF NOT EXISTS saas_payments(
  id SERIAL PRIMARY KEY, client_id integer NOT NULL, competencia text DEFAULT '',
  valor real DEFAULT 0, metodo text DEFAULT 'pix', status text DEFAULT 'pago',
  pago_em text DEFAULT rv_now(), obs text DEFAULT ''
);

-- ------------------------------------------------------------
-- Dados iniciais: NÃO é preciso inserir nada aqui.
-- No primeiro acesso, o app cria o usuário admin (senha do config.php) e
-- popula categorias/produtos de exemplo automaticamente.
-- ------------------------------------------------------------

