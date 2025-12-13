<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/constants.php';

// Avslutt sesjon og send bruker tilbake til login
logout();
redirect(BASE_URL . '/login.php');
