<?php
declare(strict_types=1);

/**
 * primus_modell.php
 *
 * Samlet modell for Primus.
 * Alle funksjoner som brukes av primus_main.php og primus_detalj.php
 * SKAL defineres her.
 */

require_once __DIR__ . '/../../includes/db.php';

/* --------------------------------------------------
   SERIER
-------------------------------------------------- */

function primus_hent_bildeserier(): array
{
    $db = db();
    return $db->query("
        SELECT SerID, Serie
        FROM bildeserie
        ORDER BY Serie
    ")->fetchAll();
}

function primus_hent_forste_serie(): ?string
{
    $db = db();
    $row = $db->query("
        SELECT Serie
        FROM bildeserie
        ORDER BY Serie
        LIMIT 1
    ")->fetch();
    return $row ? (string)$row['Serie'] : null;
}

/* --------------------------------------------------
   BRUKERPREFERANSER (sist valgt serie)
-------------------------------------------------- */

function primus_hent_sist_valgte_serie(int $userId): ?string
{
    $db = db();
    $stmt = $db->prepare("
        SELECT last_serie
        FROM user_preferences
        WHERE user_id = :uid
        LIMIT 1
    ");
    $stmt->execute(['uid' => $userId]);
    $row = $stmt->fetch();

    return ($row && $row['last_serie'] !== null)
        ? (string)$row['last_serie']
        : null;
}

function primus_lagre_sist_valgte_serie(int $userId, string $serie): void
{
    $db = db();
    $stmt = $db->prepare("
        INSERT INTO user_preferences (user_id, last_serie)
        VALUES (:uid, :serie)
        ON DUPLICATE KEY UPDATE
            last_serie = VALUES(last_serie),
            updated_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute([
        'uid'   => $userId,
        'serie' => $serie
    ]);
}

/* --------------------------------------------------
   FOTO-LISTER
-------------------------------------------------- */

function primus_hent_foto_for_serie(string $serie, int $limit = 20, int $offset = 0): array
{
    $db = db();
    $stmt = $db->prepare("
        SELECT Foto_ID, Bilde_Fil, MotivBeskr, Transferred
        FROM nmmfoto
        WHERE LEFT(Bilde_Fil, 8) = :serie
        ORDER BY Bilde_Fil DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue('serie', $serie, PDO::PARAM_STR);
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function primus_hent_totalt_antall_foto(string $serie): int
{
    $db = db();
    $stmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM nmmfoto
        WHERE LEFT(Bilde_Fil, 8) = :serie
    ");
    $stmt->execute(['serie' => $serie]);
    $row = $stmt->fetch();
    return (int)($row['total'] ?? 0);
}

/* --------------------------------------------------
   SERIENUMMER
-------------------------------------------------- */

function primus_hent_neste_sernr(string $serie): int
{
    $db = db();
    $stmt = $db->prepare("
        SELECT MAX(SerNr) AS maxnr
        FROM nmmfoto
        WHERE LEFT(Bilde_Fil, 8) = :serie
    ");
    $stmt->execute(['serie' => $serie]);
    $row = $stmt->fetch();
    return ((int)($row['maxnr'] ?? 0)) + 1;
}

/* --------------------------------------------------
   KANDIDATER (SKIP)
-------------------------------------------------- */

function primus_hent_skip_liste(string $sok = ''): array
{
    $db = db();

    $sql = "
        SELECT
            s.NMM_ID,
            s.FTY,
            s.FNA,
            s.BYG,
            s.RGH,
            s.KAL,
            s.FTO,
            s.UUID
        FROM nmm_skip s
    ";

    $params = [];
    if ($sok !== '') {
        $sql .= " WHERE s.FNA LIKE :sok ";
        $params['sok'] = '%' . $sok . '%';
    }

    $sql .= " ORDER BY s.FNA LIMIT 25 ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/* --------------------------------------------------
   ACCESS: SummaryFields()
-------------------------------------------------- */

function primus_hent_kandidat_felter(int $nmmId): array
{
    $db = db();
     $valgtFartoy = '';
    $avbildet = '';
    // MotivType
    $stmt = $db->prepare("
        SELECT `ID`, MotivType, UUID
        FROM nmmxtype
        WHERE NMM_ID = :id
        ORDER BY `ID`
    ");
    $stmt->execute(['id' => $nmmId]);
    $rows = $stmt->fetchAll();

    $motivType = $rows
        ? implode("\n", array_map(
            fn($r) => "{$r['ID']};{$r['MotivType']};{$r['UUID']}",
            $rows
        ))
        : '-';

    // MotivEmne
    $stmt = $db->prepare("
        SELECT Id_nr, MotivOrd, UUID
        FROM nmmxemne
        WHERE NMM_ID = :id
        ORDER BY Id_nr
    ");
    $stmt->execute(['id' => $nmmId]);
    $rows = $stmt->fetchAll();

    $motivEmne = $rows
        ? implode("\n", array_map(
            fn($r) => "{$r['Id_nr']};{$r['MotivOrd']};{$r['UUID']}",
            $rows
        ))
        : '-';

    // MotivKriteria (OU + UDK)
    $linjer = [];

    foreach (['nmmxou', 'nmmxudk'] as $tab) {
        $stmt = $db->prepare("
            SELECT `System`, `ID`, `kode`, `Klassifikasjon`, `UUID`
            FROM {$tab}
            WHERE `NMM_ID` = :id
            ORDER BY `System`, `ID`
        ");
        $stmt->execute(['id' => $nmmId]);
        foreach ($stmt->fetchAll() as $r) {
            $linjer[] = "{$r['System']};{$r['ID']};{$r['kode']};{$r['Klassifikasjon']};{$r['UUID']}";
        }
    }

    $motivKriteria = $linjer ? implode("\n", $linjer) : '-';

    // Skip
    $stmt = $db->prepare("
        SELECT FTY, FNA, BYG, VER, XNA, KAL, FTO, UUID
        FROM nmm_skip
        WHERE NMM_ID = :id
        LIMIT 1
    ");
    $stmt->execute(['id' => $nmmId]);
    $s = $stmt->fetch();

    if (!$s) {
        return [
            'ok'            => false,

            // eksisterende felt
            'ValgtFartoy'   => '',
            'FTO'           => '',
            'Avbildet'      => '',
            'MotivType'     => $motivType,
            'MotivEmne'     => $motivEmne,
            'MotivKriteria' => $motivKriteria,

            // 🔴 PRIMITIVE FELT – nødvendig for Access-lik MotivBeskr
            'FTY'           => '',
            'FNA'           => '',
            'BYG'           => '',
            'VER'           => '',
            'XNA'           => 0,
        ];
    }

    $fty = (string)($s['FTY'] ?? '');
    $fna = (string)($s['FNA'] ?? '');
    $byg = (string)($s['BYG'] ?? '');
    $ver = (string)($s['VER'] ?? '');
    $xna = (int)($s['XNA'] ?? 0);

    $valgtFartoy = trim($fty . ' ' . $fna);
    $avbildet = $valgtFartoy . ', ' . $byg;
    if (($s['KAL'] ?? '') !== '') {
        $avbildet .= ' (' . $s['KAL'] . ')';
    }
    if (($s['UUID'] ?? '') !== '') {
        $avbildet .= ' ' . $s['UUID'];
    }

    return [
        'ok'            => true,
        'ValgtFartoy'   => $valgtFartoy,
        'FTO'           => (string)($s['FTO'] ?? ''),
        'Avbildet'      => $avbildet,
        'MotivType'     => $motivType,
        'MotivEmne'     => $motivEmne,
        'MotivKriteria' => $motivKriteria,
        'MotivBeskr'    => $valgtFartoy,
        'FTY'           => $fty,
        'FNA'           => $fna,
        'BYG'           => $byg,
        'VER'           => $ver,
        'XNA'           => $xna,
    ];
}

/* --------------------------------------------------
   EXPORT FUNCTIONALITY (Admin only)
-------------------------------------------------- */

/**
 * Hent foto for eksport med alle nødvendige felt.
 * Brukes av export_excel.php for admin-eksport.
 */
function primus_hent_foto_for_export(string $serie, int $minSerNr, int $maxSerNr): array
{
    $db = db();

    $stmt = $db->prepare("
        SELECT
            Foto_ID,
            Bilde_Fil AS BildeId,
            URL_Bane,
            MotivBeskr,
            MotivType,
            MotivEmne,
            MotivKriteria,
            Svarthvitt,
            Aksesjon,
            Samling,
            Fotografi,
            FotoFirma,
            FotoTidFra AS Foto_Fra,
            FotoTidTil AS Foto_Til,
            FotoSted,
            Prosess,
            ReferNeg AS Referansenr,
            ReferFArk AS FotografsRefNr,
            Plassering,
            Status,
            Tilstand,
            FriKopi,
            UUID AS Fart_UUID,
            Merknad
        FROM nmmfoto
        WHERE Transferred = b'0'
          AND LEFT(Bilde_Fil, 8) = :serie
          AND SerNr BETWEEN :min_sernr AND :max_sernr
        ORDER BY SerNr
    ");

    $stmt->execute([
        'serie'      => $serie,
        'min_sernr'  => $minSerNr,
        'max_sernr'  => $maxSerNr
    ]);

    return $stmt->fetchAll();
}

/**
 * Marker flere foto som overført (Transferred = True).
 * Brukes etter eksport er bekreftet.
 */
function primus_marker_som_transferred(array $fotoIds): bool
{
    if (empty($fotoIds)) {
        return true;
    }

    $db = db();

    $placeholders = implode(',', array_fill(0, count($fotoIds), '?'));
    $stmt = $db->prepare("
        UPDATE nmmfoto
        SET Transferred = b'1'
        WHERE Foto_ID IN ($placeholders)
    ");

    return $stmt->execute($fotoIds);
}

/**
 * Toggle Transferred-status for ett foto.
 * Brukes av AJAX-endepunkt for admin-checkboks.
 */
function primus_toggle_transferred(int $fotoId): bool
{
    $db = db();

    $stmt = $db->prepare("
        UPDATE nmmfoto
        SET Transferred = IF(Transferred = b'1', b'0', b'1')
        WHERE Foto_ID = :foto_id
    ");

    return $stmt->execute(['foto_id' => $fotoId]);
}
