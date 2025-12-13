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
        'Prosess'    => false,
        'FriKopi'    => true,
        'Hendelse'   => false,
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
        $felter['Prosess']    = true;
    }

    if ($iCh === 3) {
        $felter['FriKopi'] = false;
    }

    return $felter;
}

// --------------------------------------------------
// Avledede verdier
// --------------------------------------------------
function foto_avledede_verdier(int $iCh): array
{
    return [
        'Hendelse' => '',
    ];
}
