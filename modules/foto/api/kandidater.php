<?php
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/nmmprimus/includes/db.php';

session_start();

header('Content-Type: application/json; charset=utf-8');

/* -------------------------------------------------------------
 * 1. Input / state
 * ------------------------------------------------------------- */

/*
 * startFraSiste:
 *  - true  → filtrer FNA >= siste valgte
 *  - false → vis alle
 */
$startFraSiste = filter_input(INPUT_GET, 'startFraSiste', FILTER_VALIDATE_BOOLEAN);
$sisteFna = $_SESSION['siste_fna'] ?? null;

$db = db();

/* -------------------------------------------------------------
 * 2. SQL – speiler frmNMMPrimusKand
 * ------------------------------------------------------------- */

$sql = "
    SELECT
        NMM_ID,
        FTY,
        FNA,
        XNA,
        BYG,
        VER
    FROM nmm_skip
    WHERE NMM_ID > 0
";

$params = [];

if ($startFraSiste && $sisteFna !== null) {
    $sql .= " WHERE FNA >= :sisteFna ";
    $params['sisteFna'] = $sisteFna;
}

/*
 * Access sorterer kandidater primært på FNA,
 * deretter stabil rekkefølge innen samme navn.
 */
$sql .= "
    ORDER BY
        FNA ASC,
        NMM_ID ASC
";

/* -------------------------------------------------------------
 * 3. Utfør
 * ------------------------------------------------------------- */

$stmt = $db->prepare($sql);
$stmt->execute($params);

$kandidater = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* -------------------------------------------------------------
 * 4. Returner
 * ------------------------------------------------------------- */

echo json_encode([
    'ok' => true,
    'data' => $kandidater
]);
