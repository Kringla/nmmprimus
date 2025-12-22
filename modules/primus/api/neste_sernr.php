<?php
declare(strict_types=1);
require_once __DIR__ . '/../primus_modell.php';
header('Content-Type:application/json');

$serie=$_POST['serie']??'';
if($serie===''){echo json_encode(['ok'=>false]);exit;}

echo json_encode([
 'ok'=>true,
 'sernr'=>primus_hent_neste_sernr($serie)
]);
