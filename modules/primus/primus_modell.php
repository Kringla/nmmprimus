<?php
declare(strict_types=1);

/**
 * primus_modell.php
 *
 * Modell-logikk for Primus main / landingsside.
 * Kun datatilgang – ingen presentasjon.
 */

require_once __DIR__ . '/../../includes/db.php';

/**
 * Hent alle bildeserier.
 *
 * @return array<int, array{SerID:int|null, Serie:string|null}>
 */
function primus_hent_bildeserier(): array
{
    $db = db();

    $sql = "
        SELECT
            SerID,
            Serie
        FROM bildeserie
        ORDER BY Serie
    ";

    $stmt = $db->query($sql);
    return $stmt->fetchAll();
}

/**
 * Hent første tilgjengelige serie (fallback).
 */
function primus_hent_forste_serie(): ?string
{
    $db = db();

    $sql = "
        SELECT Serie
        FROM bildeserie
        ORDER BY Serie
        LIMIT 1
    ";

    $stmt = $db->query($sql);
    $row = $stmt->fetch();

    return $row ? (string)$row['Serie'] : null;
}

/**
 * Hent foto for valgt serie.
 *
 * Filtrerer på de 8 første tegnene i Bilde_Fil.
 *
 * @param string $serie
 * @return array<int, array<string, mixed>>
 */
function primus_hent_foto_for_serie(string $serie): array
{
    $db = db();

    $sql = "
        SELECT
            Foto_ID,
            Bilde_Fil,
            MotivBeskr,
            Transferred
        FROM nmmfoto
        WHERE LEFT(Bilde_Fil, 8) = :serie
        ORDER BY Bilde_Fil
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        'serie' => $serie
    ]);

    return $stmt->fetchAll();
}

/**
 * Hent sist valgte serie for bruker.
 *
 * @param int $userId
 * @return string|null
 */
function primus_hent_sist_valgte_serie(int $userId): ?string
{
    $db = db();

    $sql = "
        SELECT last_serie
        FROM user_preferences
        WHERE user_id = :user_id
        LIMIT 1
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        'user_id' => $userId
    ]);

    $row = $stmt->fetch();

    return $row && $row['last_serie'] !== null
        ? (string)$row['last_serie']
        : null;
}

/**
 * Lagre sist valgte serie for bruker.
 *
 * @param int $userId
 * @param string $serie
 */
function primus_lagre_sist_valgte_serie(int $userId, string $serie): void
{
    $db = db();

    $sql = "
        INSERT INTO user_preferences (user_id, last_serie)
        VALUES (:user_id, :serie)
        ON DUPLICATE KEY UPDATE
            last_serie = VALUES(last_serie),
            updated_at = CURRENT_TIMESTAMP
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        'user_id' => $userId,
        'serie'   => $serie
    ]);
}
/**
 * Hent én foto-rad basert på Foto_ID.
 */
function primus_hent_foto(int $fotoId): ?array
{
    $db = db();

    $sql = "
        SELECT *
        FROM nmmfoto
        WHERE Foto_ID = :foto_id
        LIMIT 1
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        'foto_id' => $fotoId
    ]);

    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Hent liste over skip (tbS) med fritekstsøk på FNA.
 */
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
            c.Nasjon,
            s.KAL
        FROM nmm_skip s
        LEFT JOIN country c ON c.NID = s.NID
    ";

    $params = [];

    if ($sok !== '') {
        $sql .= " WHERE s.FNA LIKE :sok ";
        $params['sok'] = '%' . $sok . '%';
    }

    $sql .= " ORDER BY s.FNA ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}
/**
 * Oppdater ett foto (tbF).
 *
 * @param int   $fotoId
 * @param array<string, mixed> $data
 * @return bool
 */
function primus_oppdater_foto(int $fotoId, array $data): bool
{
    $db = db();

    $sql = "
        UPDATE nmmfoto SET
            MotivBeskr   = :MotivBeskr,
            Fotograf     = :Fotograf,
            FotoTidFra   = :FotoTidFra,
            Status       = :Status,
            Merknad      = :Merknad,
            Transferred  = :Transferred
        WHERE Foto_ID = :Foto_ID
    ";

    $stmt = $db->prepare($sql);

    return $stmt->execute([
        'MotivBeskr'  => $data['MotivBeskr'],
        'Fotograf'    => $data['Fotograf'],
        'FotoTidFra'  => $data['FotoTidFra'],
        'Status'      => $data['Status'],
        'Merknad'     => $data['Merknad'],
        'Transferred' => $data['Transferred'],
        'Foto_ID'     => $fotoId,
    ]);
}
