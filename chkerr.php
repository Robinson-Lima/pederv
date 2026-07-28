<?php
header('Content-Type: text/plain; charset=utf-8');

// Log de erros
$err = __DIR__.'/wh_err_log.txt';
echo "=== wh_err_log ===\n";
if(file_exists($err)){
    echo file_get_contents($err);
} else {
    echo "(arquivo nao existe - nenhum erro fatal capturado)\n";
}

// Ultimas 30 linhas do log principal
echo "\n=== wh_bot_log (ultimas 30 linhas) ===\n";
$log = __DIR__.'/wh_bot_log.txt';
if(file_exists($log)){
    $lines = file($log);
    echo "Total: ".count($lines)." linhas\n";
    echo implode('', array_slice($lines, -30));
}
