<?php
declare(strict_types=1);

/**
 * toggle_transferred.php
 *
 * AJAX endpoint for admin to toggle Transferred status on a photo.
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../primus_modell.php';

header('Content-Type: application/json; charset=utf-8');

// Only admins can toggle Transferred status
require_login();
$user = current_user();
if ($user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Kun administratorer kan endre overføringsstatus']);
    exit;
}

if (!is_post()) {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Kun POST er tillatt']);
    exit;
}

$fotoId = filter_input(INPUT_POST, 'foto_id', FILTER_VALIDATE_INT);
if (!$fotoId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Ugyldig foto_id']);
    exit;
}

// Toggle status
$success = primus_toggle_transferred($fotoId);

if (!$success) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Kunne ikke oppdatere database']);
    exit;
}

// Get new status
$db = db();
$stmt = $db->prepare("SELECT Transferred FROM nmmfoto WHERE Foto_ID = :id");
$stmt->execute(['id' => $fotoId]);
$row = $stmt->fetch();

$transferred = !empty($row['Transferred']);

echo json_encode([
    'success' => true,
    'transferred' => $transferred
]);
