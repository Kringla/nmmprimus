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
        'Prosess',

        // Referanser / status
        'ReferNeg',
        'ReferFArk',
        'Plassering',
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

/**
 * Kopier foto (Access: cmdKopier)
 */
function foto_kopier(PDO $db, int $fotoId): int
{
    $foto = foto_hent_en($db, $fotoId);
    if (!$foto) {
        throw new RuntimeException('Foto ikke funnet.');
    }

    unset($foto['Foto_ID']);
    unset($foto['UUID']);

    return foto_lagre($db, $foto);
}
