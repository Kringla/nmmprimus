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

if (!function_exists('primus_hent_bildeserier')) {
    function primus_hent_bildeserier(): array
    {
        $db = db();
        return $db->query("
            SELECT SerID, Serie
            FROM bildeserie
            ORDER BY Serie
        ")->fetchAll();
    }
}

if (!function_exists('primus_hent_forste_serie')) {
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
}

/* --------------------------------------------------
   BRUKERPREFERANSER (sist valgt serie)
-------------------------------------------------- */

if (!function_exists('primus_hent_sist_valgte_serie')) {
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
}

if (!function_exists('primus_lagre_sist_valgte_serie')) {
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
}

/* --------------------------------------------------
   FOTO-LISTER
-------------------------------------------------- */

if (!function_exists('primus_hent_foto_for_serie')) {
    function primus_hent_foto_for_serie(string $serie): array
    {
        $db = db();
        $stmt = $db->prepare("
            SELECT Foto_ID, Bilde_Fil, MotivBeskr, Transferred
            FROM nmmfoto
            WHERE LEFT(Bilde_Fil, 8) = :serie
            ORDER BY Bilde_Fil DESC
            LIMIT 25
        ");
        $stmt->execute(['serie' => $serie]);
        return $stmt->fetchAll();
    }
}

/* --------------------------------------------------
   SERIENUMMER
-------------------------------------------------- */

if (!function_exists('primus_hent_neste_sernr')) {
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
}

/* --------------------------------------------------
   KANDIDATER (SKIP)
-------------------------------------------------- */

if (!function_exists('primus_hent_skip_liste')) {
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
}

/* --------------------------------------------------
   ACCESS: SummaryFields()
-------------------------------------------------- */

if (!function_exists('primus_hent_kandidat_felter')) {
    function primus_hent_kandidat_felter(int $nmmId): array
    {
        $db = db();
         $valgtFartoy = '';
        $avbildet = '';
        // MotivType
        $stmt = $db->prepare("
            SELECT ID, MotivType, UUID
            FROM nmmxtype
            WHERE NMM_ID = :id
            ORDER BY ID
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
                SELECT System, ID, kode, Klassifikasjon, UUID
                FROM {$tab}
                WHERE NMM_ID = :id
                ORDER BY System, ID
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
                'ok' => true,

                // eksisterende felt
                'ValgtFartoy'   => $valgtFartoy,
                'FTO'           => (string)$s['FTO'],
                'Avbildet'      => $avbildet,
                'MotivType'     => $motivType,
                'MotivEmne'     => $motivEmne,
                'MotivKriteria' => $motivKriteria,

                // 🔴 PRIMITIVE FELT – nødvendig for Access-lik MotivBeskr
                'FTY' => (string)$s['FTY'],
                'FNA' => (string)$s['FNA'],
                'BYG' => (string)$s['BYG'],
                'VER' => (string)($s['VER'] ?? ''),
                'XNA' => (int)($s['XNA'] ?? 0),
            ];
        }

        $valgtFartoy = trim($s['FTY'] . ' ' . $s['FNA']);
        $avbildet = $valgtFartoy . ', ' . $s['BYG'];
        if ($s['KAL'] !== '') {
            $avbildet .= ' (' . $s['KAL'] . ')';
        }
        if ($s['UUID'] !== '') {
            $avbildet .= ' ' . $s['UUID'];
        }

        return [
            'ok' => true,
            'ValgtFartoy' => $valgtFartoy,
            'FTO' => (string)$s['FTO'],
            'Avbildet' => $avbildet,
            'MotivType' => $motivType,
            'MotivEmne' => $motivEmne,
            'MotivKriteria' => $motivKriteria,
            'MotivBeskr' => $valgtFartoy
        ];
    }
}
/* --------------------------------------------------
   FOTO – hent én rad
-------------------------------------------------- */
if (!function_exists('primus_hent_foto')) {
    function primus_hent_foto(int $fotoId): ?array
    {
        $db = db();

        $stmt = $db->prepare("
            SELECT *
            FROM nmmfoto
            WHERE Foto_ID = :id
        ");
        $stmt->execute([
            'id' => $fotoId
        ]);

        $row = $stmt->fetch();
        return $row ?: null;
    }
}

/* --------------------------------------------------
   FOTO – oppdater (lagring fra detalj)
-------------------------------------------------- */
if (!function_exists('primus_oppdater_foto')) {
    function primus_oppdater_foto(int $fotoId, array $data): bool
    {
        $db = db();

        $stmt = $db->prepare("
            UPDATE nmmfoto SET
                NMMSerie   = :NMMSerie,
                SerNr      = :SerNr,
                Bilde_Fil  = :Bilde_Fil,
                MotivBeskr = :MotivBeskr,
                MotivType  = :MotivType,
                MotivEmne  = :MotivEmne,
                MotivKriteria = :MotivKriteria,
                Avbildet   = :Avbildet,
                Status     = :Status,
                Merknad    = :Merknad
            WHERE Foto_ID = :Foto_ID
        ");

        return $stmt->execute([
            'NMMSerie'       => $data['NMMSerie'] ?? null,
            'SerNr'          => $data['SerNr'] ?? null,
            'Bilde_Fil'      => $data['Bilde_Fil'] ?? null,
            'MotivBeskr'     => $data['MotivBeskr'] ?? null,
            'MotivType'      => $data['MotivType'] ?? null,
            'MotivEmne'      => $data['MotivEmne'] ?? null,
            'MotivKriteria'  => $data['MotivKriteria'] ?? null,
            'Avbildet'       => $data['Avbildet'] ?? null,
            'Status'         => $data['Status'] ?? null,
            'Merknad'        => $data['Merknad'] ?? null,
            'Foto_ID'        => $fotoId,
        ]);
    }
}
