<?php
$master = new PDO('sqlite:'.dirname(__DIR__).'/data/rvcardapios.sqlite');
$master->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Listar últimos cadastros
$clientes = $master->query("SELECT id,restaurante,email,whatsapp,plano,status,slug,criado_em FROM saas_clients ORDER BY id DESC LIMIT 5")->fetchAll();
echo "=== CLIENTES ===\n";
foreach($clientes as $c) { echo "{$c['id']} | {$c['restaurante']} | {$c['email']} | {$c['slug']} | {$c['status']}\n"; }

// Para cada slug, verificar se o sqlite existe e tem user
echo "\n=== TENANTS ===\n";
foreach($clientes as $c){
  if(!$c['slug']) continue;
  $f=dirname(__DIR__).'/data/'.$c['slug'].'.sqlite';
  if(!file_exists($f)){ echo "{$c['slug']}: ARQUIVO NAO EXISTE\n"; continue; }
  $d=new PDO('sqlite:'.$f);$d->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
  $users=$d->query("SELECT id,nome,usuario,role,ativo FROM users ORDER BY id")->fetchAll();
  echo "{$c['slug']}:\n";
  foreach($users as $u) echo "  [{$u['id']}] {$u['usuario']} | role={$u['role']} | ativo={$u['ativo']}\n";
}

// Teste de mail
echo "\n=== MAIL TEST ===\n";
$ok = @mail('roh.icl2013@gmail.com','Teste PedeRV','Teste de envio de e-mail via PHP mail()','From: noreply@pederv.com.br');
echo $ok ? "mail() retornou TRUE\n" : "mail() retornou FALSE\n";
