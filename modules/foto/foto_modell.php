<?php
// modules/foto/foto_modell.php
declare(strict_types=1);

/**
 * Hent ett foto
 */
function foto_hent_en(PDO $db, int $fotoId): ?array
{
    $stmt = $db->prepare("
        SELECT *
        FROM nmmfoto
        WHERE Foto_ID = :id
    ");
    $stmt->execute(['id' => $fotoId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * Lagre (insert / update) foto
 *
 * VIKTIG:
 *  - Feltlisten under er fasit (primus_detalj feltliste.md)
 *  - SerNr og Bilde_Fil MÅ være med
 */
function foto_lagre(PDO $db, array $data): int
{
    // --------------------------------------------------
    // URL_Bane-generering (Access: UpdateURLFields)
    // --------------------------------------------------
    if (!empty($data['Bilde_Fil'])) {
        // Hent serie fra Bilde_Fil (8 første tegn)
        $bildeFil = (string)$data['Bilde_Fil'];
        if (strlen($bildeFil) >= 8) {
            $serie = substr($bildeFil, 0, 8);
            // Format: "[Serie] -001-999 Damp og Motor"
            $data['URL_Bane'] = $serie . ' -001-999 Damp og Motor';
        }
    }

    // --------------------------------------------------
    // Server-side POST-sanitering (iCh-paritet)
    // --------------------------------------------------

    $iCh = isset($_SESSION['primus_iCh']) ? (int)$_SESSION['primus_iCh'] : 1;

    // Felter som ALLTID kan lagres
    $always = [
        'Foto_ID',
        'NMM_ID',
        'SerNr',
        'Bilde_Fil',
        'MotivBeskr',
        'MotivBeskrTillegg',
        'MotivType',
        'MotivEmne',
        'MotivKriteria',
        'Avbildet',
        'Hendelse',
        'ReferNeg',
        'ReferFArk',
        'Plassering',
        'Prosess',
        'Status',
        'Tilstand',
        'Merknad',
        'Svarthvitt',
        'FriKopi'
    ];

    // Felter styrt av iCh
    $fotoFelter = [
        'Fotograf',
        'FotoFirma',
        'FotoTidFra',
        'FotoTidTil',
        'FotoSted'
    ];

    $samlingFelter = [
        'Samling'
    ];

    // Start med alltid-feltene
    $tillatt = $always;

    // Fotografi-relaterte felt
    if (in_array($iCh, [2,4,6], true)) {
        $tillatt = array_merge($tillatt, $fotoFelter);
    }

    // Samling-relaterte felt
    if (in_array($iCh, [3,4,6], true)) {
        $tillatt = array_merge($tillatt, $samlingFelter);
    }

    // Fjern alle POST-felter som ikke er tillatt i aktuell iCh
    foreach (array_keys($data) as $key) {
        if (!in_array($key, $tillatt, true)) {
            unset($data[$key]);
        }
    }

    // --------------------------------------------------
    // FELTLISTE (whitelist)
    // --------------------------------------------------
    $felter = [
        // Nøkler / system
        'Foto_ID',
        'NMM_ID',
        'UUID',
        'URL_Bane',

        // Serie / nummer
        'SerNr',
        'Bilde_Fil',

        // Hendelse / motiv
        'Hendelse',
        'MotivBeskr',
        'MotivBeskrTillegg',
        'MotivType',
        'MotivEmne',
        'MotivKriteria',
        'Avbildet',

        // Foto / samling
        'Fotografi',
        'Fotograf',
        'FotoFirma',
        'FotoTidFra',
        'FotoTidTil',
        'FotoSted',
        'Aksesjon',
        'Samling',

        // Referanser / status
        'ReferNeg',
        'ReferFArk',
        'Plassering',
        'Prosess',
        'Status',
        'Tilstand',
        'Svarthvitt',
        'FriKopi',

        // Annet
        'Merknad',
    ];

    // --------------------------------------------------
    // FILTRER DATA (kun tillatte felt)
    // --------------------------------------------------
    $filtered = [];
    foreach ($felter as $felt) {
        if (array_key_exists($felt, $data)) {
            $filtered[$felt] = $data[$felt];
        }
    }

    // --------------------------------------------------
    // UPDATE vs INSERT
    // --------------------------------------------------
    if (!empty($filtered['Foto_ID'])) {

        $fotoId = (int)$filtered['Foto_ID'];
        unset($filtered['Foto_ID']);

        $set = [];
        foreach ($filtered as $k => $v) {
            $set[] = "$k = :$k";
        }

        $sql = "
            UPDATE nmmfoto
            SET " . implode(", ", $set) . "
            WHERE Foto_ID = :Foto_ID
        ";

        $stmt = $db->prepare($sql);
        $filtered['Foto_ID'] = $fotoId;
        $stmt->execute($filtered);

        return $fotoId;
    }

    // --------------------------------------------------
    // INSERT (brukes sjelden her, men beholdt)
    // --------------------------------------------------
    unset($filtered['Foto_ID']);

    $cols = implode(", ", array_keys($filtered));
    $vals = ":" . implode(", :", array_keys($filtered));

    $sql = "
        INSERT INTO nmmfoto ($cols)
        VALUES ($vals)
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($filtered);

    return (int)$db->lastInsertId();
}

// --------------------------------------------------
// Opprett nytt foto (Primus)
// --------------------------------------------------
function foto_opprett_ny(PDO $db, array $data = []): int
{
    // Minimumsverdier – detaljer fylles i primus_detalj.php
    $data = array_merge([
        'SerNr'      => 0,
        'Bilde_Fil'  => '',
        'MotivBeskr' => ''
    ], $data);

    return foto_lagre($db, $data);
}

/**
 * Kopier foto (Access: cmdKopier)
 *
 * Kopierer Motiv-fanen, men nullstiller Bildehistorikk og Øvrige til defaultverdier.
 */
function foto_kopier(PDO $db, int $fotoId): int
{
    $foto = foto_hent_en($db, $fotoId);
    if (!$foto) {
        throw new RuntimeException('Foto ikke funnet.');
    }

    // Fjern primærnøkkel og UUID
    unset($foto['Foto_ID']);
    unset($foto['UUID']);

    // Nullstill Bildehistorikk-felter (Access: Me!fmeHendelse = 1, linjer 73-87)
    $foto['MotivBeskrTillegg'] = null;
    $foto['FotoTidFra'] = null;
    $foto['FotoTidTil'] = null;
    $foto['Aksesjon'] = 0;
    $foto['FriKopi'] = 1;
    $foto['Samling'] = null;
    $foto['Fotografi'] = 0;
    $foto['Fotograf'] = null;
    $foto['FotoFirma'] = null;

    // Nullstill Øvrige-felter (Access: linjer 83-84)
    $foto['ReferFArk'] = null;
    $foto['ReferNeg'] = null;

    // SerNr håndteres i primus_detalj.php (Access: Me!SerNr = iSer)

    return foto_lagre($db, $foto);
}
