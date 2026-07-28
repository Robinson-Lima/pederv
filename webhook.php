<?php
$_wh_body = file_get_contents('php://input');
$_wh_log  = date('Y-m-d H:i:s')."\n";
$_wh_log .= "METHOD: ".($_SERVER['REQUEST_METHOD']??'?')."\n";
$_wh_log .= "IP: ".($_SERVER['REMOTE_ADDR']??'?')."\n";
$_wh_log .= "BODY: ".substr($_wh_body,0,1200)."\n---\n";
file_put_contents(__DIR__.'/wh_bot_log.txt', $_wh_log, FILE_APPEND);

// Capturar erros PHP no processamento
set_error_handler(function($errno,$errstr,$errfile,$errline){
    file_put_contents(__DIR__.'/wh_err_log.txt', date('Y-m-d H:i:s')." ERR[$errno] $errstr in $errfile:$errline\n", FILE_APPEND);
});
register_shutdown_function(function(){
    $e=error_get_last();
    if($e&&in_array($e['type'],[E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR]))
        file_put_contents(__DIR__.'/wh_err_log.txt', date('Y-m-d H:i:s')." FATAL: {$e['message']} in {$e['file']}:{$e['line']}\n", FILE_APPEND);
});

$_GET['r']    = 'webhook_evolution';
$_GET['slug'] = '';
require __DIR__ . '/index.php';
