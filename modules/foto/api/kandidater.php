<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/auth.php';

require_login();
$db = db();

header('Content-Type: application/json; charset=utf-8');

/* -------------------------------------------------------------
 * 1. Input / state
 * ------------------------------------------------------------- */

$startFraSiste = filter_input(INPUT_GET, 'startFraSiste', FILTER_VALIDATE_BOOLEAN);
$sisteFna = $_SESSION['siste_fna'] ?? null;

// Fritekstsøk på FNA (ikke case sensitivt – ivaretas normalt av *_ci-collation)
$q = trim((string)filter_input(INPUT_GET, 'q', FILTER_UNSAFE_RAW));
$q = mb_substr($q, 0, 100); // enkel begrensning

// Aktivt foto i session (settes av primus_detalj.php)
$fotoId = $_SESSION['aktiv_foto_id'] ?? null;

// Finn valgt NMM_ID for aktivt foto (Access: current record sync)
$aktivNmmId = null;
if ($fotoId) {
    $stmtAktiv = $db->prepare("
        SELECT NMM_ID
        FROM nmmfoto
        WHERE Foto_ID = :foto
        LIMIT 1
    ");
    $stmtAktiv->execute(['foto' => (int)$fotoId]);
    $aktivNmmId = $stmtAktiv->fetchColumn();
    $aktivNmmId = ($aktivNmmId !== false && $aktivNmmId !== null) ? (int)$aktivNmmId : null;
}

/* -------------------------------------------------------------
 * 2. SQL – speiler frmNMMPrimusKand (med Nasjon fra country)
 * ------------------------------------------------------------- */

$sql = "
    SELECT
        s.NMM_ID,
        s.FTY,
        s.FNA,
        s.BYG,
        s.RGH,
        c.Nasjon,
        s.KAL,
        s.NID
    FROM nmm_skip s
    LEFT JOIN country c ON c.NID = s.NID
    WHERE s.NMM_ID > 0
";

$params = [];

// Access: fmeSorter=2 → filter FNA >= siste valgte
if ($startFraSiste && $sisteFna !== null && $sisteFna !== '') {
    $sql .= " AND s.FNA >= :sisteFna ";
    $params['sisteFna'] = (string)$sisteFna;
}

// Access: søk på del av fartøynavn (FNA)
if ($q !== '') {
    $sql .= " AND s.FNA LIKE :q ";
    $params['q'] = '%' . $q . '%';
}

$sql .= "
    ORDER BY
        s.FNA ASC,
        s.NMM_ID ASC
    LIMIT 500
";

/* -------------------------------------------------------------
 * 3. Utfør
 * ------------------------------------------------------------- */

$stmt = $db->prepare($sql);
$stmt->execute($params);

$kandidater = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* -------------------------------------------------------------
 * 4. Annoter valgt kandidat (for UI-forhåndsmarkering)
 * ------------------------------------------------------------- */

if ($aktivNmmId !== null) {
    foreach ($kandidater as &$k) {
        $k['valgt'] = ((int)($k['NMM_ID'] ?? 0) === $aktivNmmId);
    }
    unset($k);
} else {
    foreach ($kandidater as &$k) {
        $k['valgt'] = false;
    }
    unset($k);
}

/* -------------------------------------------------------------
 * 5. Returner
 * ------------------------------------------------------------- */

echo json_encode([
    'ok' => true,
    'aktiv_nmm_id' => $aktivNmmId,
    'data' => $kandidater,
]);
