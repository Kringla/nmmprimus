<?php
declare(strict_types=1);
session_start();

foreach ($_POST as $key => $value) {
    $_SESSION[$key] = $value;
}

header('Content-Type: application/json');
echo json_encode(['ok' => true]);
