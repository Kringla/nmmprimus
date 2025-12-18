<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/auth.php';

require_login();
$db = db();

$fotoId = $_SESSION['aktiv_foto_id'] ?? null;
$nmmId  = filter_input(INPUT_POST, 'NMM_ID', FILTER_VALIDATE_INT);

if (!$fotoId || !$nmmId) {
    http_response_code(400);
    echo json_encode(['ok' => false]);
    exit;
}

$stmt = $db->prepare("
    UPDATE nmmfoto
    SET NMM_ID = :nmm
    WHERE Foto_ID = :foto
");
$stmt->execute([
    'nmm'  => $nmmId,
    'foto' => $fotoId,
]);

$stmtSkip = $db->prepare("
    SELECT
        s.NMM_ID,
        s.FNA,
        s.FTO,
        s.FTY,
        s.KAL,
        s.BYG,
        s.UUID,
        s.XNA,
        s.VER
    FROM nmm_skip s
    WHERE s.NMM_ID = :nmm
    LIMIT 1
");
$stmtSkip->execute(['nmm' => $nmmId]);
$skip = $stmtSkip->fetch(PDO::FETCH_ASSOC) ?: [];

/**
 * Sørg for at motivbeskrivelse inneholder "b. <år>" dersom fødselsår finnes.
 */
function ensure_b_marker(string $tekst, string $byg, string $ver): string
{
    $tekst = trim($tekst);
    $byg = trim($byg);
    $ver = trim($ver);

    if ($tekst === '' || $byg === '') {
        return $tekst;
    }

    // Hvis "b." allerede finnes, behold teksten som den er.
    if (stripos($tekst, '(b.') !== false) {
        return $tekst;
    }

    // Hvis årstallet finnes i teksten, prefiks med "b."
    $needle = '(' . $byg;
    if (strpos($tekst, $needle) !== false) {
        return str_replace($needle, '(b. ' . $byg, $tekst);
    }

    // Hvis det finnes en parentes, sett inn "b." ved første parentes
    if (strpos($tekst, '(') !== false) {
        return preg_replace('/\\(/', '(b. ', $tekst, 1);
    }

    // Fallback: legg til på slutten
    $tail = $ver !== '' ? ', ' . $ver : '';
    return rtrim($tekst) . ' (b. ' . $byg . $tail . ')';
}

// Bygg default-fylling for felter i foto-skjemaet (Access: SummaryFields/funcAvbildet)
$feltdata = [];

if ($skip) {
    $feltdata['Avbildet'] = trim(
        sprintf(
            '%s %s, %s (%s) %s',
            (string)($skip['FTY'] ?? ''),
            (string)($skip['FNA'] ?? ''),
            (string)($skip['BYG'] ?? ''),
            (string)($skip['KAL'] ?? ''),
            (string)($skip['UUID'] ?? '')
        )
    );

    // FTO (fritekst) er nærmest "MotivBeskr" i dagens skjema; fall tilbake på navn
    $fty = trim((string)($skip['FTY'] ?? ''));
    $fna = trim((string)($skip['FNA'] ?? ''));
    $xna = trim((string)($skip['XNA'] ?? ''));
    $byg = trim((string)($skip['BYG'] ?? ''));
    $ver = trim((string)($skip['VER'] ?? ''));

    if (!empty($skip['FTO'])) {
        $feltdata['MotivBeskr'] = ensure_b_marker((string)$skip['FTO'], $byg, $ver);
        $feltdata['FTO'] = (string)$skip['FTO'];
    } else {
        $exPart = ($xna !== '') ? " (Ex. {$xna}) " : ' ';

        $feltdata['MotivBeskr'] = ensure_b_marker(trim(sprintf(
            '%s %s%s(b. %s, %s)',
            $fty,
            $fna,
            $exPart,
            $byg,
            $ver
        )), $byg, $ver);
        $feltdata['FTO'] = '';
    }
}

// MotivType: nmmxtype (ID;MotivType;UUID pr linje)
$stmtType = $db->prepare("
    SELECT Id, MotivType, UUID
    FROM nmmxtype
    WHERE NMM_ID = :nmm
    ORDER BY Id
");
$stmtType->execute(['nmm' => $nmmId]);
$linjerType = [];
foreach ($stmtType->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $linje = implode(';', [
        (string)($row['Id'] ?? ''),
        (string)($row['MotivType'] ?? ''),
        (string)($row['UUID'] ?? ''),
    ]);
    $linje = trim($linje, "; \t\n\r\0\x0B");
    if ($linje !== '') {
        $linjerType[] = $linje;
    }
}
if ($linjerType) {
    $feltdata['MotivType'] = implode("\n", $linjerType);
}

// MotivEmne: nmmxemne (Id_nr;MotivOrd;UUID pr linje)
$stmtEmne = $db->prepare("
    SELECT Id_nr, Motivord, UUID
    FROM nmmxemne
    WHERE NMM_ID = :nmm
    ORDER BY Id_nr
");
$stmtEmne->execute(['nmm' => $nmmId]);
$linjerEmne = [];
foreach ($stmtEmne->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $linje = implode(';', [
        (string)($row['Id_nr'] ?? ''),
        (string)($row['Motivord'] ?? ''),
        (string)($row['UUID'] ?? ''),
    ]);
    $linje = trim($linje, "; \t\n\r\0\x0B");
    if ($linje !== '') {
        $linjerEmne[] = $linje;
    }
}
if ($linjerEmne) {
    $feltdata['MotivEmne'] = implode("\n", $linjerEmne);
}

// MotivKriteria: nmmxou + nmmxudk (System;Id;Kode;Klassifikasjon;UUID pr linje)
$linjerKrit = [];

$stmtOu = $db->prepare("
    SELECT System, Id, Kode, Klassifikasjon, UUID
    FROM nmmxou
    WHERE NMM_ID = :nmm
    ORDER BY Id
");
$stmtOu->execute(['nmm' => $nmmId]);
foreach ($stmtOu->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $linje = implode(';', [
        (string)($row['System'] ?? ''),
        (string)($row['Id'] ?? ''),
        (string)($row['Kode'] ?? ''),
        (string)($row['Klassifikasjon'] ?? ''),
        (string)($row['UUID'] ?? ''),
    ]);
    $linje = trim($linje, "; \t\n\r\0\x0B");
    if ($linje !== '') {
        $linjerKrit[] = $linje;
    }
}

$stmtUdk = $db->prepare("
    SELECT System, Id, Kode, Klassifikasjon, UUID
    FROM nmmxudk
    WHERE NMM_ID = :nmm
    ORDER BY Id
");
$stmtUdk->execute(['nmm' => $nmmId]);
foreach ($stmtUdk->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $linje = implode(';', [
        (string)($row['System'] ?? ''),
        (string)($row['Id'] ?? ''),
        (string)($row['Kode'] ?? ''),
        (string)($row['Klassifikasjon'] ?? ''),
        (string)($row['UUID'] ?? ''),
    ]);
    $linje = trim($linje, "; \t\n\r\0\x0B");
    if ($linje !== '') {
        $linjerKrit[] = $linje;
    }
}

if ($linjerKrit) {
    $feltdata['MotivKriteria'] = implode("\n", $linjerKrit);
}

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'ok' => true,
    'data' => [
        'NMM_ID' => (int)$nmmId,
        'felt'   => $feltdata,
    ]
]);
