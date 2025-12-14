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

        // Steg A: hindre overskriving – kun aktiv Foto_ID kan oppdateres
        if (
            !isset($_SESSION['aktiv_foto_id']) ||
            (int)$_SESSION['aktiv_foto_id'] !== $params['Foto_ID']
        ) {
            // Stille avvisning (Access-ekvivalent)
            return (int)$data['Foto_ID'];
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return (int)$data['Foto_ID'];
    }


    // INSERT
        // --------------------------------------------------
    // Access-ekvivalent: UpdateURLFields()
    // --------------------------------------------------

    // Serie må være tilgjengelig (valgt i primus_main)
    if (empty($data['Serie'])) {
        throw new RuntimeException('Serie mangler ved opprettelse av nytt foto');
    }

    $serie = $data['Serie'];

    // Finn neste ledige SerNr innen samme serie
    $stmt = $db->prepare("
        SELECT MAX(SerNr) AS max_sernr
        FROM nmmfoto
        WHERE Bilde_Fil LIKE :serie
    ");
    $stmt->execute([
        'serie' => $serie . '-%'
    ]);

    $max = (int)($stmt->fetchColumn() ?? 0);
    $serNr = $max + 1;

    // Format: 3 sifre, ledende nuller
    $serNrFmt = str_pad((string)$serNr, 3, '0', STR_PAD_LEFT);

    // Bilde_Fil: SERIE-001
    $data['SerNr'] = $serNr;
    $data['Bilde_Fil'] = $serie . '-' . $serNrFmt;

    // URL_Bane: cURL + Serie + "-001-999 Damp og Motor"
    // Access: Public Const cURL
    $cURL = 'M:\\NMM\\Bibliotek\\Foto\\NSM.TUSEN-SERIE\\';

    $data['URL_Bane'] =
        $cURL .
        $serie .
        '-001-999 Damp og Motor';

        
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
