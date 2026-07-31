<?php
// ============================================================
// Banco de dados — SQLite (troque por MySQL no VPS mudando o DSN)
// ============================================================
function db_driver(){ $d = cfg('db_driver'); return $d==='pgsql' ? 'pgsql' : 'sqlite'; }

function _pdo_pgsql($c){
  $dsn='pgsql:host='.($c['host']??'localhost').';port='.($c['port']??'5432').';dbname='.($c['dbname']??'postgres').';sslmode='.($c['sslmode']??'require');
  $p=new PDO($dsn,$c['user']??'postgres',$c['pass']??'');
  $p->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
  $p->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
  return $p;
}
function _pdo_sqlite($file){
  $dir=dirname($file); if(!is_dir($dir)) mkdir($dir,0755,true);
  $p=new PDO('sqlite:'.$file);
  $p->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
  $p->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
  $p->exec('PRAGMA foreign_keys=ON; PRAGMA journal_mode=WAL; PRAGMA busy_timeout=5000');
  return $p;
}

// Conexão do tenant atual: SQLite por slug ou master (pgsql/sqlite) sem slug
function db(){
  static $pdo=null;
  if($pdo) return $pdo;
  if(db_driver()==='pgsql'){
    $pdo=_pdo_pgsql(cfg('db')?:[]);
    return $pdo;
  }
  $slug=current_slug();
  $file=$slug ? __DIR__.'/../data/'.$slug.'.sqlite'
              : __DIR__.'/../data/rvcardapios.sqlite';
  $pdo=_pdo_sqlite($file);
  return $pdo;
}

// Conexão direta ao banco master (SaaS billing, validação de tenant)
// Ignora o slug — sempre usa as credenciais de config.php
function master_db(){
  static $mpdo=null;
  if($mpdo) return $mpdo;
  $cfg=require __DIR__.'/../config.php';
  if(($cfg['db_driver']??'sqlite')==='pgsql'){
    $mpdo=_pdo_pgsql($cfg['db']??[]);
  } else {
    $mpdo=_pdo_sqlite(__DIR__.'/../data/rvcardapios.sqlite');
  }
  return $mpdo;
}

function db_migrate() {
  $d = db();
  // No Postgres/Supabase o schema é criado pelo arquivo supabase-schema.sql
  // (rodado uma vez no painel do Supabase). Aqui só o SQLite cria/altera tabelas.
  if (db_driver()==='sqlite') db_build_schema($d);

  // Usuário administrador inicial para instalações já existentes.
  $nu=$d->query("SELECT COUNT(*) c FROM users")->fetch()['c'];
  if($nu==0) $d->prepare("INSERT INTO users(nome,usuario,senha_hash,role) VALUES(?,?,?,?)")
    ->execute(['Administrador','admin',password_hash(cfg('admin_senha'),PASSWORD_DEFAULT),'admin']);
  $d->exec("SELECT 1");

  // Seed só na primeira vez
  $n = $d->query("SELECT COUNT(*) c FROM products")->fetch()['c'];
  if ($n == 0) db_seed($d);
}

// Cria/atualiza todo o schema SQLite. Usado pelo tenant atual e pelo
// provisionamento de novos clientes (saas_provision_tenant).
function db_build_schema($d){
  $d->exec("
    CREATE TABLE IF NOT EXISTS categories(
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      nome TEXT, ordem INTEGER DEFAULT 0
    );
    CREATE TABLE IF NOT EXISTS products(
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      category_id INTEGER, nome TEXT, descricao TEXT,
      preco REAL, emoji TEXT DEFAULT '🍔', destaque INTEGER DEFAULT 0,
      ativo INTEGER DEFAULT 1
    );
    CREATE TABLE IF NOT EXISTS orders(
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      codigo TEXT, canal TEXT DEFAULT 'cardapio', tipo TEXT DEFAULT 'entrega',
      cliente_nome TEXT, cliente_fone TEXT, endereco TEXT, mesa TEXT,
      status TEXT DEFAULT 'novo', total REAL DEFAULT 0,
      pagamento_metodo TEXT DEFAULT 'pix', pagamento_status TEXT DEFAULT 'pendente',
      courier_id INTEGER,
      criado_em TEXT DEFAULT (datetime('now','localtime')),
      atualizado_em TEXT DEFAULT (datetime('now','localtime'))
    );
    CREATE TABLE IF NOT EXISTS order_items(
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      order_id INTEGER, nome TEXT, qtd INTEGER, preco REAL
    );
    CREATE TABLE IF NOT EXISTS order_status_events(
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      order_id INTEGER, status TEXT, ator TEXT,
      criado_em TEXT DEFAULT (datetime('now','localtime'))
    );
    CREATE TABLE IF NOT EXISTS couriers(
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      nome TEXT, status TEXT DEFAULT 'livre'
    );
    CREATE TABLE IF NOT EXISTS tables_(
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      numero TEXT, status TEXT DEFAULT 'livre'
    );
    CREATE TABLE IF NOT EXISTS caixa(
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      saldo_inicial REAL DEFAULT 0, saldo_informado REAL,
      status TEXT DEFAULT 'aberto',
      aberto_em TEXT DEFAULT (datetime('now','localtime')),
      fechado_em TEXT
    );
    CREATE TABLE IF NOT EXISTS caixa_mov(
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      caixa_id INTEGER, tipo TEXT, categoria TEXT, descricao TEXT,
      valor REAL, criado_em TEXT DEFAULT (datetime('now','localtime'))
    );
    CREATE TABLE IF NOT EXISTS settings(
      chave TEXT PRIMARY KEY, valor TEXT
    );
    CREATE TABLE IF NOT EXISTS bot_replies(
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      gatilho TEXT, resposta TEXT, ativo INTEGER DEFAULT 1
    );
    CREATE TABLE IF NOT EXISTS notas(
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      order_id INTEGER, numero INTEGER, serie TEXT, modelo TEXT DEFAULT '65',
      chave TEXT, protocolo TEXT, status TEXT DEFAULT 'pendente',
      ambiente TEXT, motivo TEXT, xml_path TEXT, danfe_url TEXT,
      valor REAL, criado_em TEXT DEFAULT (datetime('now','localtime'))
    );");

  // Campos fiscais nos produtos (ALTER seguro; ignora se já existir)
  foreach ([
    "ALTER TABLE products ADD COLUMN ncm TEXT DEFAULT '21069090'",
    "ALTER TABLE products ADD COLUMN cfop TEXT DEFAULT '5102'",
    "ALTER TABLE products ADD COLUMN cst TEXT DEFAULT '102'",
    "ALTER TABLE products ADD COLUMN origem TEXT DEFAULT '0'",
    "ALTER TABLE products ADD COLUMN unidade TEXT DEFAULT 'UN'",
    "ALTER TABLE products ADD COLUMN foto TEXT DEFAULT ''",
    "ALTER TABLE products ADD COLUMN estoque INTEGER DEFAULT 0",
    "ALTER TABLE products ADD COLUMN controla_estoque INTEGER DEFAULT 0",
    "ALTER TABLE products ADD COLUMN estoque_minimo INTEGER DEFAULT 0",
    "ALTER TABLE products ADD COLUMN tipo TEXT DEFAULT 'produto'",
    "ALTER TABLE products ADD COLUMN combo_itens TEXT DEFAULT ''",
    "ALTER TABLE orders ADD COLUMN bairro TEXT DEFAULT ''",
    "ALTER TABLE orders ADD COLUMN referencia TEXT DEFAULT ''",
    "ALTER TABLE orders ADD COLUMN aceito INTEGER DEFAULT 0",
    "ALTER TABLE orders ADD COLUMN ocorrencia TEXT DEFAULT ''",
    "ALTER TABLE orders ADD COLUMN ocorrencia_obs TEXT DEFAULT ''",
    "ALTER TABLE couriers ADD COLUMN telefone TEXT DEFAULT ''",
    "ALTER TABLE couriers ADD COLUMN user_id INTEGER",
    "ALTER TABLE caixa ADD COLUMN operador TEXT DEFAULT ''",
    "ALTER TABLE caixa_mov ADD COLUMN order_id INTEGER",
    "ALTER TABLE orders ADD COLUMN atendente TEXT DEFAULT ''",
    "ALTER TABLE orders ADD COLUMN fechado_por TEXT DEFAULT ''",
    "ALTER TABLE orders ADD COLUMN recebido_por TEXT DEFAULT ''",
    "ALTER TABLE orders ADD COLUMN fechado_em TEXT",
    "ALTER TABLE orders ADD COLUMN lat REAL",
    "ALTER TABLE orders ADD COLUMN lng REAL",
    "ALTER TABLE orders ADD COLUMN zona TEXT DEFAULT ''",
    "ALTER TABLE couriers ADD COLUMN online INTEGER DEFAULT 0",
    "ALTER TABLE couriers ADD COLUMN online_em TEXT",
    "ALTER TABLE orders ADD COLUMN customer_id INTEGER",
    "ALTER TABLE orders ADD COLUMN simulacao INTEGER DEFAULT 0",
    "ALTER TABLE orders ADD COLUMN pagamento_provider_id TEXT DEFAULT ''",
    "ALTER TABLE orders ADD COLUMN pagamento_detectado_em TEXT",
    "ALTER TABLE orders ADD COLUMN pagamento_origem TEXT DEFAULT ''",
    "ALTER TABLE customers ADD COLUMN email TEXT DEFAULT ''",
    "ALTER TABLE customers ADD COLUMN senha_hash TEXT DEFAULT ''",
    "ALTER TABLE customers ADD COLUMN numero TEXT DEFAULT ''",
    "ALTER TABLE customers ADD COLUMN referencia TEXT DEFAULT ''",
    "ALTER TABLE customers ADD COLUMN cep TEXT DEFAULT ''",
    "ALTER TABLE customers ADD COLUMN cidade TEXT DEFAULT ''",
    "ALTER TABLE customers ADD COLUMN uf TEXT DEFAULT ''",
    // V15: acerto do motoboy com o caixa + PDV com cobrança real
    "ALTER TABLE orders ADD COLUMN acerto_status TEXT DEFAULT ''",   // '' | pendente | ok
    "ALTER TABLE orders ADD COLUMN acerto_em TEXT",
    "ALTER TABLE orders ADD COLUMN acerto_por TEXT DEFAULT ''",
    "ALTER TABLE orders ADD COLUMN valor_recebido REAL DEFAULT 0",
    "ALTER TABLE orders ADD COLUMN troco REAL DEFAULT 0",
  ] as $sql){ try{ $d->exec($sql); }catch(Exception $e){} }

  $d->exec("
    CREATE TABLE IF NOT EXISTS push_subscriptions(
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER, endpoint TEXT NOT NULL UNIQUE,
      p256dh TEXT NOT NULL, auth TEXT NOT NULL,
      criado_em TEXT DEFAULT (datetime('now','localtime'))
    );
    CREATE TABLE IF NOT EXISTS delivery_zones(
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      nome TEXT, tipo TEXT DEFAULT 'entrega', taxa REAL DEFAULT 0,
      poligono TEXT, ativo INTEGER DEFAULT 1,
      criado_em TEXT DEFAULT (datetime('now','localtime'))
    );
    CREATE TABLE IF NOT EXISTS delivery_areas(
      id INTEGER PRIMARY KEY AUTOINCREMENT, bairro TEXT, taxa REAL DEFAULT 0
    );
    CREATE TABLE IF NOT EXISTS operadores(
      id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT, pin TEXT DEFAULT '', ativo INTEGER DEFAULT 1
    );
    CREATE TABLE IF NOT EXISTS garcons(
      id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT, ativo INTEGER DEFAULT 1
    );
    CREATE TABLE IF NOT EXISTS users(
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      nome TEXT NOT NULL, usuario TEXT NOT NULL UNIQUE,
      senha_hash TEXT NOT NULL, role TEXT NOT NULL,
      telefone TEXT DEFAULT '', ativo INTEGER DEFAULT 1,
      criado_em TEXT DEFAULT (datetime('now','localtime'))
    );
    CREATE TABLE IF NOT EXISTS customers(
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      nome TEXT NOT NULL, telefone TEXT DEFAULT '', endereco TEXT DEFAULT '', bairro TEXT DEFAULT '',
      pedidos INTEGER DEFAULT 0, total_gasto REAL DEFAULT 0,
      ultimo_pedido TEXT DEFAULT (datetime('now','localtime'))
    );
    CREATE TABLE IF NOT EXISTS whatsapp_contacts(
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      wa_id TEXT NOT NULL UNIQUE, nome TEXT DEFAULT '',
      atualizado_em TEXT DEFAULT (datetime('now','localtime'))
    );
    CREATE TABLE IF NOT EXISTS whatsapp_messages(
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      wa_id TEXT NOT NULL, direcao TEXT NOT NULL,
      texto TEXT DEFAULT '', status TEXT DEFAULT 'recebida',
      criado_em TEXT DEFAULT (datetime('now','localtime'))
    );
    CREATE TABLE IF NOT EXISTS abandoned_carts(
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      token TEXT NOT NULL UNIQUE, customer_id INTEGER,
      nome TEXT DEFAULT '', telefone TEXT DEFAULT '', email TEXT DEFAULT '',
      itens_json TEXT DEFAULT '[]', total REAL DEFAULT 0,
      status TEXT DEFAULT 'aberto', aviso_enviado_em TEXT, convertido_order_id INTEGER,
      criado_em TEXT DEFAULT (datetime('now','localtime')),
      atualizado_em TEXT DEFAULT (datetime('now','localtime'))
    );
    CREATE TABLE IF NOT EXISTS saas_clients(
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      restaurante TEXT NOT NULL, responsavel TEXT DEFAULT '',
      email TEXT DEFAULT '', whatsapp TEXT DEFAULT '',
      dominio TEXT DEFAULT '', cidade TEXT DEFAULT '', uf TEXT DEFAULT '',
      plano TEXT DEFAULT 'pro', status TEXT DEFAULT 'trial',
      valor_mensal REAL DEFAULT 0, dia_vencimento INTEGER DEFAULT 10,
      trial_ate TEXT, proximo_venc TEXT,
      bloqueado_em TEXT, bloqueio_manual INTEGER DEFAULT 0, obs TEXT DEFAULT '',
      slug TEXT DEFAULT '',
      criado_em TEXT DEFAULT (datetime('now','localtime')),
      atualizado_em TEXT DEFAULT (datetime('now','localtime'))
    );
    CREATE TABLE IF NOT EXISTS saas_payments(
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      client_id INTEGER NOT NULL, competencia TEXT DEFAULT '',
      valor REAL DEFAULT 0, metodo TEXT DEFAULT 'pix', status TEXT DEFAULT 'pago',
      pago_em TEXT DEFAULT (datetime('now','localtime')), obs TEXT DEFAULT ''
    );
  ");
  try{$d->exec("CREATE UNIQUE INDEX IF NOT EXISTS customers_email_uq ON customers(email) WHERE email<>''");}catch(Exception $e){}
  try{$d->exec("ALTER TABLE saas_clients ADD COLUMN slug TEXT DEFAULT ''");}catch(Exception $e){}
  try{$d->exec("CREATE UNIQUE INDEX IF NOT EXISTS saas_clients_slug_uq ON saas_clients(slug) WHERE slug<>''");}catch(Exception $e){}
  try{$d->exec("CREATE INDEX IF NOT EXISTS saas_clients_status ON saas_clients(status)");}catch(Exception $e){}
  try{$d->exec("CREATE INDEX IF NOT EXISTS saas_clients_venc ON saas_clients(proximo_venc) WHERE proximo_venc IS NOT NULL");}catch(Exception $e){}
  try{$d->exec("CREATE INDEX IF NOT EXISTS saas_payments_client ON saas_payments(client_id)");}catch(Exception $e){}
  try{$d->exec("ALTER TABLE saas_clients ADD COLUMN trial_aviso_enviado INTEGER DEFAULT 0");}catch(Exception $e){}
  try{$d->exec("ALTER TABLE saas_clients ADD COLUMN dominio TEXT DEFAULT ''");}catch(Exception $e){}
  try{$d->exec("ALTER TABLE saas_clients ADD COLUMN cidade TEXT DEFAULT ''");}catch(Exception $e){}
  try{$d->exec("ALTER TABLE saas_clients ADD COLUMN uf TEXT DEFAULT ''");}catch(Exception $e){}
}

// Provisiona o banco de um novo cliente (trial): cria o SQLite, monta o schema,
// grava o admin com o login/senha escolhidos e popula o cardápio de exemplo.
function saas_provision_tenant($slug,$usuario,$senha,$nome=''){
  $slug=preg_replace('/[^a-z0-9_-]/','',strtolower((string)$slug));
  if($slug==='') return false;
  $file=__DIR__.'/../data/'.$slug.'.sqlite';
  $fresh=!file_exists($file)||filesize($file)===0;
  $d=_pdo_sqlite($file);
  db_build_schema($d);
  $hash=password_hash($senha,PASSWORD_DEFAULT);
  $q=$d->prepare("SELECT id FROM users WHERE usuario=?"); $q->execute([$usuario]);
  if($q->fetch()){
    $d->prepare("UPDATE users SET senha_hash=?,nome=?,role='admin',ativo=1 WHERE usuario=?")
      ->execute([$hash,$nome?:'Administrador',$usuario]);
  } else {
    $d->prepare("INSERT INTO users(nome,usuario,senha_hash,role) VALUES(?,?,?,'admin')")
      ->execute([$nome?:'Administrador',$usuario,$hash]);
  }
  if($fresh){
    $n=$d->query("SELECT COUNT(*) c FROM products")->fetch()['c'];
    if($n==0) db_seed($d);
    // Popula settings iniciais para o novo tenant não herdar dados de outro restaurante
    $ins=$d->prepare("INSERT OR IGNORE INTO settings(chave,valor) VALUES(?,?)");
    $ins->execute(['store_name', $nome]);
    $ins->execute(['store_open', '1']);
  }
  return true;
}

function db_seed($d=null) {
  $d = $d ?: db();
  $cats = ['Destaques','Burgers','Porções','Bebidas'];
  $catId = [];
  $st = $d->prepare("INSERT INTO categories(nome,ordem) VALUES(?,?)");
  foreach ($cats as $i=>$c){ $st->execute([$c,$i]); $catId[$c]=$d->lastInsertId(); }

  $prods = [
    ['Destaques','Smash Duplo','2 blends 90g, cheddar, cebola caramelizada e molho da casa.',28.90,'🍔',1],
    ['Burgers','RV Bacon Supreme','Blend 180g, muito bacon, cheddar duplo e maionese defumada.',34.90,'🍔',0],
    ['Porções','Batata Rústica G','Alecrim, parmesão e maionese verde.',18.90,'🍟',0],
    ['Porções','Onion Rings','8 anéis crocantes com barbecue.',16.90,'🧅',0],
    ['Bebidas','Guaraná Lata 350ml','Gelado.',6.00,'🥤',0],
    ['Bebidas','Suco Del Valle Uva','1L.',9.90,'🧃',0],
  ];
  $st = $d->prepare("INSERT INTO products(category_id,nome,descricao,preco,emoji,destaque) VALUES(?,?,?,?,?,?)");
  foreach ($prods as $p) $st->execute([$catId[$p[0]],$p[1],$p[2],$p[3],$p[4],$p[5]]);

  foreach (['Léo Martins','Rafa Souza','Diego Alves'] as $c)
    $d->prepare("INSERT INTO couriers(nome) VALUES(?)")->execute([$c]);

  for ($i=1;$i<=9;$i++)
    $d->prepare("INSERT INTO tables_(numero) VALUES(?)")->execute([str_pad($i,2,'0',STR_PAD_LEFT)]);

  // Bot híbrido: respostas prontas (IA só entra quando nada casa)
  $bot = [
    ['horario|hora|abre|fecha|funciona', 'Funcionamos todos os dias das 18h às 23h30! 🍔'],
    ['cardapio|menu|opcoes|opções', 'Nosso cardápio completo está aqui: {LINK_CARDAPIO} 📱'],
    ['entrega|taxa|frete|delivery', 'Fazemos entrega sim! Taxa a partir de R$ 5,00 conforme o bairro. 🛵'],
    ['pix|pagamento|cartao|cartão|dinheiro', 'Aceitamos Pix (sem taxa!), cartão e dinheiro. 💚'],
    ['tempo|demora|quanto tempo|previsao', 'O tempo médio de preparo + entrega é de 30 a 45 minutos. ⏱'],
    ['endereco|endereço|onde fica|local', 'Estamos na Rua Exemplo, 123 — Centro. Vem nos visitar! 📍'],
    ['promocao|promoção|desconto|cupom', 'Fique de olho! Toda terça tem combo em dobro. 🎉'],
  ];
  $st = $d->prepare("INSERT INTO bot_replies(gatilho,resposta) VALUES(?,?)");
  foreach ($bot as $b) $st->execute($b);
}
