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
    // Validering: SerNr må være mellom 1 og 999
    // --------------------------------------------------
    if (isset($data['SerNr'])) {
        $serNr = (int)$data['SerNr'];
        if ($serNr < 1 || $serNr > 999) {
            throw new InvalidArgumentException('SerNr må være mellom 1 og 999');
        }
    }

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

    // Normaliser numeriske/nullable felt
    // Hvis NMM_ID er en tom streng, send NULL til DB (unngå MySQL strict-feil)
    if (array_key_exists('NMM_ID', $filtered)) {
        if ($filtered['NMM_ID'] === '' || $filtered['NMM_ID'] === null) {
            $filtered['NMM_ID'] = null;
        } else {
            $filtered['NMM_ID'] = (int)$filtered['NMM_ID'];
        }
    }

    // Sørg for at SerNr er integer om angitt
    if (array_key_exists('SerNr', $filtered)) {
        $filtered['SerNr'] = (int)$filtered['SerNr'];
    }

    // Normaliser bit-felter (Aksesjon, Fotografi, FriKopi, Transferred, Flag)
    $bitFelter = ['Aksesjon', 'Fotografi', 'FriKopi', 'Transferred', 'Flag'];
    foreach ($bitFelter as $bf) {
        if (!array_key_exists($bf, $filtered)) {
            continue;
        }

        $v = $filtered[$bf];

        // Empty values -> NULL (DB tillater NULL)
        if ($v === '' || $v === null) {
            $filtered[$bf] = null;
            continue;
        }

        // Booleans and ints -> normalize to 0/1
        if (is_bool($v) || is_int($v)) {
            $filtered[$bf] = (int)$v ? 1 : 0;
            continue;
        }

        // Strings: try several strategies
        if (is_string($v)) {
            $trim = trim($v);

            // 1-byte binary from PDO (e.g. "\x00" / "\x01")
            if (strlen($trim) === 1) {
                $ord = ord($trim);
                if ($ord === 0 || $ord === 1) {
                    $filtered[$bf] = $ord;
                    continue;
                }
            }

            // Numeric strings ("0", "1", "0\x00", "1\n" etc)
            if (is_numeric($trim)) {
                $filtered[$bf] = ((int)$trim) ? 1 : 0;
                continue;
            }

            // If string contains explicit '0' or '1' anywhere, use first occurrence
            if (preg_match('/([01])/', $trim, $m)) {
                $filtered[$bf] = ((int)$m[1]) ? 1 : 0;
                continue;
            }
        }

        // Arrays or other unexpected types -> set NULL and log for debugging
        error_log(sprintf('foto_lagre: could not normalize bit field %s (value type=%s)', $bf, gettype($v)));
        $filtered[$bf] = null;

        // Final safeguard: ensure value is either NULL or 0/1
        if (!is_null($filtered[$bf])) {
            $filtered[$bf] = ((int)$filtered[$bf]) ? 1 : 0;
        }
    }

    // --------------------------------------------------
    // UPDATE vs INSERT
    // --------------------------------------------------
    // Defensive check: ensure FriKopi is safe for DB (must be NULL, 0 or 1)
    if (array_key_exists('FriKopi', $filtered)) {
        $fp = $filtered['FriKopi'];
        if (!is_null($fp) && !in_array($fp, [0, 1], true)) {
            // If string contains 0 or 1, use first occurrence; else set NULL and log
            if (is_string($fp) && preg_match('/([01])/', $fp, $m)) {
                $filtered['FriKopi'] = (int)$m[1];
                error_log(sprintf('foto_lagre: trimmed FriKopi string to %d', $filtered['FriKopi']));
            } else {
                error_log(sprintf('foto_lagre: invalid FriKopi value; setting NULL (was: %s)', var_export($fp, true)));
                $filtered['FriKopi'] = null;
            }
        }
    }

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

        // Bind bit-felter eksplisitt som integers for MySQL bit(1) kompatibilitet
        $bitFelterIUpdate = ['Aksesjon', 'Fotografi', 'FriKopi', 'Transferred', 'Flag'];
        foreach ($bitFelterIUpdate as $bf) {
            if (array_key_exists($bf, $filtered)) {
                $val = $filtered[$bf];
                if (is_null($val)) {
                    $stmt->bindValue(":$bf", null, PDO::PARAM_NULL);
                } else {
                    $stmt->bindValue(":$bf", (int)$val, PDO::PARAM_INT);
                }
            }
        }

        try {
            $stmt->execute($filtered);
        } catch (PDOException $e) {
            // Log the exact problematic FriKopi value (type, export, raw hex) for debugging
            $fk = $filtered['FriKopi'] ?? null;
            $hex = is_string($fk) ? bin2hex($fk) : '';
            $msg = sprintf("foto_lagre UPDATE failed: %s; Foto_ID=%d; FriKopi_export=%s; FriKopi_type=%s; FriKopi_hex=%s", $e->getMessage(), $fotoId, var_export($fk, true), gettype($fk), $hex);
            error_log($msg);

            // UI-debug: store details in session for display in the UI (cleared by user)
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $_SESSION['foto_debug_last'] = [
                'when' => date('c'),
                'context' => 'UPDATE',
                'message' => $e->getMessage(),
                'foto_id' => $fotoId,
                'FriKopi' => $fk,
                'FriKopi_type' => gettype($fk),
                'FriKopi_hex' => $hex,
                'sql_error' => $e->getMessage(),
            ];

            throw $e;
        }

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

    // Bind bit-felter eksplisitt som integers for MySQL bit(1) kompatibilitet
    $bitFelterIInsert = ['Aksesjon', 'Fotografi', 'FriKopi', 'Transferred', 'Flag'];
    foreach ($bitFelterIInsert as $bf) {
        if (array_key_exists($bf, $filtered)) {
            $val = $filtered[$bf];
            if (is_null($val)) {
                $stmt->bindValue(":$bf", null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(":$bf", (int)$val, PDO::PARAM_INT);
            }
        }
    }

    try {
        $stmt->execute($filtered);
    } catch (PDOException $e) {
        $fk = $filtered['FriKopi'] ?? null;
        $hex = is_string($fk) ? bin2hex($fk) : '';
        $msg = sprintf("foto_lagre INSERT failed: %s; FriKopi_export=%s; FriKopi_type=%s; FriKopi_hex=%s", $e->getMessage(), var_export($fk, true), gettype($fk), $hex);
        error_log($msg);

        // UI-debug: store details in session for display in the UI (cleared by user)
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['foto_debug_last'] = [
            'when' => date('c'),
            'context' => 'INSERT',
            'message' => $e->getMessage(),
            'FriKopi' => $fk,
            'FriKopi_type' => gettype($fk),
            'FriKopi_hex' => $hex,
            'sql_error' => $e->getMessage(),
        ];

        throw $e;
    }

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

    // Nullstill Bildehistorikk-felter til database defaults
    // (fanen "Bildehistorikk" i primus_detalj.php)
    $foto['FotoTidFra'] = null;
    $foto['FotoTidTil'] = null;
    $foto['FotoSted'] = null;
    $foto['Aksesjon'] = 0;
    $foto['FriKopi'] = 1;
    $foto['Samling'] = null;
    $foto['Fotografi'] = 0;
    $foto['Fotograf'] = null;
    $foto['FotoFirma'] = null;

    // Nullstill referanse-felter i Øvrige-fanen til tomme
    $foto['ReferFArk'] = null;
    $foto['ReferNeg'] = null;

    // SerNr håndteres i primus_detalj.php (Access: Me!SerNr = iSer)

    // Try saving; if DB error occurs, persist debug info in session and redirect
    try {
        return foto_lagre($db, $foto);
    } catch (PDOException $e) {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $fk = $foto['FriKopi'] ?? null;
        $_SESSION['foto_debug_last'] = [
            'when' => date('c'),
            'context' => 'foto_kopier->foto_lagre',
            'message' => $e->getMessage(),
            'foto_id' => $fotoId,
            'FriKopi' => $fk,
            'FriKopi_type' => gettype($fk),
            'FriKopi_hex' => is_string($fk) ? bin2hex($fk) : '',
            'sql_error' => $e->getMessage(),
        ];

        // Redirect back to the detail page so the debug panel is shown
        // Use redirect helper if available
        if (function_exists('redirect')) {
            redirect('/modules/primus/primus_detalj.php?Foto_ID=' . (int)$fotoId);
        }
        header('Location: ' . '/modules/primus/primus_detalj.php?Foto_ID=' . (int)$fotoId);
        exit;
    }
}
