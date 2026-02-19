<?php
declare(strict_types=1);

/**
 * Foto – regelmotor (1:1 Access)
 * Ingen UI, ingen SQL
 */

// --------------------------------------------------
// iCh fra fmeHendelse
// --------------------------------------------------
function foto_hendelsesmodus_fra_fme(int $fme): int
{
    if ($fme < 1 || $fme > 6) {
        return 1;
    }
    return $fme;
}

// --------------------------------------------------
// Felt-tilstand
// --------------------------------------------------
function foto_felt_tilstand(int $iCh): array
{
    $felter = [
        'Samling'    => false,
        'Fotograf'   => false,
        'FotoFirma'  => false,
        'FotoTidFra' => false,
        'FotoTidTil' => false,
        'FotoSted'   => false,
        'FriKopi'    => true,
        'Hendelse'   => true,    // ✅ Hendelse er ALLTID redigerbart (i $always)
    ];

    if (in_array($iCh, [3, 4, 6], true)) {
        $felter['Samling'] = true;
    }

    if (in_array($iCh, [2, 4, 6], true)) {
        $felter['Fotograf']   = true;
        $felter['FotoFirma'] = true;
        $felter['FotoTidFra'] = true;
        $felter['FotoTidTil'] = true;
        $felter['FotoSted']   = true;
    }

    if (in_array($iCh, [3, 4], true)) {
        $felter['FriKopi'] = false;
    }

    return $felter;
}

// --------------------------------------------------
// Avledede verdier
// --------------------------------------------------
function foto_avledede_verdier(int $iCh): array
{
    $verdier = [];

    // ---------------------------------------------
    // Access: Fotografi settes eksplisitt
    // iCh = 2,4,6 → True
    // ---------------------------------------------
    $verdier['Fotografi'] = in_array($iCh, [2, 4, 6], true) ? 1 : 0;

    // ---------------------------------------------
    // Access: Aksesjon / FriKopi / Samling (auto-verdier basert på iCh)
    // iCh = 3,4,6
    // ---------------------------------------------
    if (in_array($iCh, [3, 4, 6], true)) {
        $verdier['Aksesjon'] = 1;
        $verdier['Samling']  = 'C2-Johnsen, Per-Erik';  // Default (kun hvis tom)
        $verdier['FriKopi']  = 0;
    } else {
        $verdier['Aksesjon'] = 0;
        $verdier['Samling']  = '';  // Tøm ved iCh 1,2
        $verdier['FriKopi']  = 1;
    }

    // Hendelse-tekst settes fortsatt i foto_state.php
    // (bevisst: DB-avhengig logikk holdes der)
    return $verdier;
}

