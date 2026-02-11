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

function primus_hent_foto_for_serie(
    string $serie,
    int $limit = 20,
    int $offset = 0,
    ?string $dateFra = null,
    ?string $dateTil = null,
    string $dateField = 'Oppdatert_Tid'
): array
{
    $db = db();

    // Bygg WHERE-klausul dynamisk
    $where = "WHERE LEFT(Bilde_Fil, 8) = :serie";
    $params = ['serie' => $serie];

    // Legg til tidsfilter hvis angitt
    if ($dateFra !== null && $dateFra !== '') {
        $where .= " AND $dateField >= :date_fra";
        $params['date_fra'] = $dateFra . ' 00:00:00';
    }

    if ($dateTil !== null && $dateTil !== '') {
        // Til-dato: < dagen etter (inkluderer hele til-dagen)
        $where .= " AND $dateField < DATE_ADD(:date_til, INTERVAL 1 DAY)";
        $params['date_til'] = $dateTil . ' 00:00:00';
    }

    $sql = "
        SELECT Foto_ID, Bilde_Fil, MotivBeskr, Transferred, Fotografi, Aksesjon, Samling, Oppdatert_Tid
        FROM nmmfoto
        $where
        ORDER BY Bilde_Fil DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function primus_hent_totalt_antall_foto(
    string $serie,
    ?string $dateFra = null,
    ?string $dateTil = null,
    string $dateField = 'Oppdatert_Tid'
): int
{
    $db = db();

    // Bygg WHERE-klausul dynamisk
    $where = "WHERE LEFT(Bilde_Fil, 8) = :serie";
    $params = ['serie' => $serie];

    // Legg til tidsfilter hvis angitt
    if ($dateFra !== null && $dateFra !== '') {
        $where .= " AND $dateField >= :date_fra";
        $params['date_fra'] = $dateFra . ' 00:00:00';
    }

    if ($dateTil !== null && $dateTil !== '') {
        $where .= " AND $dateField < DATE_ADD(:date_til, INTERVAL 1 DAY)";
        $params['date_til'] = $dateTil . ' 00:00:00';
    }

    $sql = "
        SELECT COUNT(*) as total
        FROM nmmfoto
        $where
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return (int)($row['total'] ?? 0);
}

/**
 * Søk etter foto basert på skipsnavn (FNA fra nmm_skip)
 *
 * @param string $skipsnavn Skipsnavn å søke etter (minimum 3 tegn)
 * @param string|null $serie Valgfri serie-filtrering (8 tegn) - null for alle serier
 * @param int $limit Antall resultater per side
 * @param int $offset Offset for paging
 * @param string|null $dateFra Valgfri fra-dato (YYYY-MM-DD)
 * @param string|null $dateTil Valgfri til-dato (YYYY-MM-DD)
 * @param string $dateField Felt å filtrere på (Oppdatert_Tid eller Opprettet_Tid)
 * @return array Liste med foto
 */
function primus_sok_foto_etter_skipsnavn(
    string $skipsnavn,
    ?string $serie,
    int $limit = 20,
    int $offset = 0,
    ?string $dateFra = null,
    ?string $dateTil = null,
    string $dateField = 'Oppdatert_Tid'
): array
{
    $db = db();

    $sql = "
        SELECT f.Foto_ID, f.Bilde_Fil, f.MotivBeskr, f.Transferred, f.Fotografi, f.Aksesjon, f.Samling, f.Oppdatert_Tid
        FROM nmmfoto f
        INNER JOIN nmm_skip s ON f.NMM_ID = s.NMM_ID
        WHERE s.FNA LIKE :skipsnavn
    ";

    $params = ['skipsnavn' => '%' . $skipsnavn . '%'];

    if ($serie !== null && $serie !== '') {
        $sql .= " AND LEFT(f.Bilde_Fil, 8) = :serie ";
        $params['serie'] = $serie;
    }

    // Legg til tidsfilter hvis angitt
    if ($dateFra !== null && $dateFra !== '') {
        $sql .= " AND f.$dateField >= :date_fra ";
        $params['date_fra'] = $dateFra . ' 00:00:00';
    }

    if ($dateTil !== null && $dateTil !== '') {
        $sql .= " AND f.$dateField < DATE_ADD(:date_til, INTERVAL 1 DAY) ";
        $params['date_til'] = $dateTil . ' 00:00:00';
    }

    $sql .= "
        ORDER BY f.Bilde_Fil DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $db->prepare($sql);
    $stmt->bindValue('skipsnavn', $params['skipsnavn'], PDO::PARAM_STR);
    if (isset($params['serie'])) {
        $stmt->bindValue('serie', $params['serie'], PDO::PARAM_STR);
    }
    if (isset($params['date_fra'])) {
        $stmt->bindValue('date_fra', $params['date_fra'], PDO::PARAM_STR);
    }
    if (isset($params['date_til'])) {
        $stmt->bindValue('date_til', $params['date_til'], PDO::PARAM_STR);
    }
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

/**
 * Tell totalt antall foto som matcher skipsnavn-søk
 *
 * @param string $skipsnavn Skipsnavn å søke etter
 * @param string|null $serie Valgfri serie-filtrering - null for alle serier
 * @param string|null $dateFra Valgfri fra-dato (YYYY-MM-DD)
 * @param string|null $dateTil Valgfri til-dato (YYYY-MM-DD)
 * @param string $dateField Felt å filtrere på (Oppdatert_Tid eller Opprettet_Tid)
 * @return int Antall treff
 */
function primus_sok_foto_etter_skipsnavn_antall(
    string $skipsnavn,
    ?string $serie,
    ?string $dateFra = null,
    ?string $dateTil = null,
    string $dateField = 'Oppdatert_Tid'
): int
{
    $db = db();

    $sql = "
        SELECT COUNT(*) as total
        FROM nmmfoto f
        INNER JOIN nmm_skip s ON f.NMM_ID = s.NMM_ID
        WHERE s.FNA LIKE :skipsnavn
    ";

    $params = ['skipsnavn' => '%' . $skipsnavn . '%'];

    if ($serie !== null && $serie !== '') {
        $sql .= " AND LEFT(f.Bilde_Fil, 8) = :serie ";
        $params['serie'] = $serie;
    }

    // Legg til tidsfilter hvis angitt
    if ($dateFra !== null && $dateFra !== '') {
        $sql .= " AND f.$dateField >= :date_fra ";
        $params['date_fra'] = $dateFra . ' 00:00:00';
    }

    if ($dateTil !== null && $dateTil !== '') {
        $sql .= " AND f.$dateField < DATE_ADD(:date_til, INTERVAL 1 DAY) ";
        $params['date_til'] = $dateTil . ' 00:00:00';
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();

    return (int)($row['total'] ?? 0);
}

/* --------------------------------------------------
   SERIENUMMER
-------------------------------------------------- */

/**
 * Hent siste SerNr brukeren la inn i denne serien
 * Returnerer 0 hvis ingen tidligere registrering finnes
 */
function primus_hent_siste_sernr_for_bruker(int $userId, string $serie): int
{
    $db = db();
    $stmt = $db->prepare("
        SELECT last_sernr
        FROM user_serie_sernr
        WHERE user_id = :user_id AND serie = :serie
    ");
    $stmt->execute(['user_id' => $userId, 'serie' => $serie]);
    $row = $stmt->fetch();
    return (int)($row['last_sernr'] ?? 0);
}

/**
 * Lagre siste SerNr brukeren la inn i denne serien
 */
function primus_lagre_siste_sernr_for_bruker(int $userId, string $serie, int $serNr): void
{
    $db = db();
    $stmt = $db->prepare("
        INSERT INTO user_serie_sernr (user_id, serie, last_sernr)
        VALUES (:user_id, :serie, :sernr)
        ON DUPLICATE KEY UPDATE last_sernr = VALUES(last_sernr)
    ");
    $stmt->execute(['user_id' => $userId, 'serie' => $serie, 'sernr' => $serNr]);
}

/**
 * Finn første ledige SerNr i serien etter et gitt startpunkt
 * Sjekker databasen for eksisterende SerNr og finner første gap/hull
 *
 * @param string $serie 8-tegns serie-ID (f.eks. "NSM.9999")
 * @param int $startFra SerNr å starte søket fra
 * @return int Første ledige SerNr (1-999)
 */
function primus_finn_forste_ledige_sernr(string $serie, int $startFra): int
{
    $db = db();

    // Hent alle eksisterende SerNr i serien, sortert
    $stmt = $db->prepare("
        SELECT SerNr
        FROM nmmfoto
        WHERE LEFT(Bilde_Fil, 8) = :serie
        ORDER BY SerNr ASC
    ");
    $stmt->execute(['serie' => $serie]);
    $eksisterende = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Konverter til set for rask oppslag
    $eksisterendeSet = array_flip($eksisterende);

    // Finn første ledige nummer fra $startFra
    for ($sernr = $startFra; $sernr <= 999; $sernr++) {
        if (!isset($eksisterendeSet[$sernr])) {
            return $sernr;
        }
    }

    // Hvis ingen ledige fra startFra til 999, søk fra 1 til startFra
    for ($sernr = 1; $sernr < $startFra; $sernr++) {
        if (!isset($eksisterendeSet[$sernr])) {
            return $sernr;
        }
    }

    // Hvis alt er fullt (1-999), returner 999 (vil gi feil senere)
    return 999;
}

/**
 * Hent neste SerNr for ny rad basert på brukerens siste innleggelse
 *
 * @param int $userId Bruker-ID
 * @param string $serie 8-tegns serie-ID
 * @return int Foreslått SerNr for ny rad
 */
function primus_hent_neste_sernr_for_bruker(int $userId, string $serie): int
{
    // Hent siste SerNr brukeren la inn i denne serien
    $sisteBrukteSerNr = primus_hent_siste_sernr_for_bruker($userId, $serie);

    // Hvis brukeren aldri har lagt inn noe i denne serien, start fra 1
    if ($sisteBrukteSerNr === 0) {
        return primus_finn_forste_ledige_sernr($serie, 1);
    }

    // Finn første ledige SerNr etter siste brukte
    return primus_finn_forste_ledige_sernr($serie, $sisteBrukteSerNr + 1);
}

/**
 * DEPRECATED: Gammel funksjon - bruker MAX(SerNr) i stedet for bruker-tracking
 * Beholdt for bakoverkompatibilitet
 */
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
            'XNA'           => '',
        ];
    }

    $fty = (string)($s['FTY'] ?? '');
    $fna = (string)($s['FNA'] ?? '');
    $byg = (string)($s['BYG'] ?? '');
    $ver = (string)($s['VER'] ?? '');
    $xna = (string)($s['XNA'] ?? '');

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
            PlassFriTekst,
            Status,
            Tilstand,
            FriKopi,
            UUID AS Fart_UUID,
            Merknad
        FROM nmmfoto
        WHERE Transferred = 0
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
 * Hent foto for Fotoeksemplar-eksport (export_fotoeks.php).
 * Returnerer kun poster med Transferred = 1 (allerede overførte).
 * Oppdaterer IKKE Transferred-feltet.
 */
function primus_hent_foto_for_fotoeks_export(string $serie, int $minSerNr, int $maxSerNr): array
{
    $db = db();
    $stmt = $db->prepare("
        SELECT
            Bilde_Fil               AS ParentID,
            CONCAT(Bilde_Fil, '.1') AS BildeId,
            'Fotoeksemplar'      AS Objekttype,
            Plassering,
            PlassFriTekst,
            '1'                  As Antall
        FROM nmmfoto
        WHERE Transferred = 1
          AND LEFT(Bilde_Fil, 8) = :serie
          AND SerNr BETWEEN :min_sernr AND :max_sernr
        ORDER BY SerNr ASC
    ");
    $stmt->execute([
        'serie'     => $serie,
        'min_sernr' => $minSerNr,
        'max_sernr' => $maxSerNr,
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
        SET Transferred = 1
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
        SET Transferred = NOT Transferred
        WHERE Foto_ID = :foto_id
    ");

    return $stmt->execute(['foto_id' => $fotoId]);
}

/**
 * Finn sidenummer for et gitt foto i en serie.
 * Brukes for å redirecte til riktig side etter lagring.
 *
 * @param int $fotoId Foto_ID som skal finnes
 * @param int $perSide Antall rader per side (standard 20)
 * @return int Sidenummer (1-basert)
 */
function primus_finn_side_for_foto(int $fotoId, int $perSide = 20): int
{
    $db = db();

    // Hent Bilde_Fil for det gitte fotoet
    $stmt = $db->prepare("
        SELECT Bilde_Fil
        FROM nmmfoto
        WHERE Foto_ID = :foto_id
    ");
    $stmt->execute(['foto_id' => $fotoId]);
    $foto = $stmt->fetch();

    if (!$foto) {
        return 1; // Hvis foto ikke finnes, returner side 1
    }

    $bildeFil = (string)$foto['Bilde_Fil'];
    if (strlen($bildeFil) < 8) {
        return 1; // Ugyldig Bilde_Fil
    }

    $serie = substr($bildeFil, 0, 8);

    // Tell antall foto i serien som kommer ETTER dette fotoet
    // (siden vi sorterer DESC på Bilde_Fil)
    $stmt = $db->prepare("
        SELECT COUNT(*) as antall
        FROM nmmfoto
        WHERE LEFT(Bilde_Fil, 8) = :serie
          AND Bilde_Fil > :bilde_fil
    ");
    $stmt->execute([
        'serie' => $serie,
        'bilde_fil' => $bildeFil
    ]);
    $result = $stmt->fetch();
    $antallEtter = (int)($result['antall'] ?? 0);

    // Beregn sidenummer (1-basert)
    // Posisjon i listen = antall foto etter + 1 (0-basert = antallEtter)
    // Sidenummer = ceil((posisjon) / perSide)
    $posisjon = $antallEtter + 1;
    $side = (int)ceil($posisjon / $perSide);

    return max(1, $side);
}
