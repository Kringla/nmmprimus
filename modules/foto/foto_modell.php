<?php
// modules/foto/foto_modell.php
declare(strict_types=1);

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

function foto_lagre(PDO $db, array $data): int
{
    $felter = [
        'NMM_ID',
        'URL_Bane',
        'SerNr',
        'Bilde_Fil',
        'MotivBeskr',
        'MotivBeskrTillegg',
        'MotivType',
        'MotivEmne',
        'MotivKriteria',
        'Avbildet',
        'Hendelse',
        'Aksesjon',
        'Samling',
        'Fotografi',
        'Fotograf',
        'FotoFirma',
        'FotoTidFra',
        'FotoTidTil',
        'FotoSted',
        'Prosess',
        'ReferNeg',
        'ReferFArk',
        'Plassering',
        'Svarthvitt',
        'Status',
        'Tilstand',
        'FriKopi',
        'Transferred',
        'Merknad'
    ];

    $insertFelter = [];
    $params = [];

    foreach ($felter as $felt) {
        if (array_key_exists($felt, $data)) {
            $insertFelter[] = $felt;
            $params[$felt] = $data[$felt];
        }
    }

    if (!empty($data['Foto_ID'])) {
        // UPDATE
        $set = [];
        foreach ($insertFelter as $felt) {
            $set[] = "$felt = :$felt";
        }

        $sql = "
            UPDATE nmmfoto
            SET " . implode(', ', $set) . "
            WHERE Foto_ID = :Foto_ID
        ";

        $params['Foto_ID'] = (int)$data['Foto_ID'];

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return (int)$data['Foto_ID'];
    }

    // INSERT
    $sql = "
        INSERT INTO nmmfoto
        (" . implode(', ', $insertFelter) . ")
        VALUES
        (:" . implode(', :', $insertFelter) . ")
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    return (int)$db->lastInsertId();
}
