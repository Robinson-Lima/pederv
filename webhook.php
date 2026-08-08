<?php
$_wh_body = file_get_contents('php://input');
$_GET['r']    = 'webhook_evolution';
$_GET['slug'] = '';
require __DIR__ . '/index.php';
