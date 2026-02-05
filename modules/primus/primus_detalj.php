<?php
declare(strict_types=1);

/**
 * primus_detalj.php
 *
 * Detaljvisning og redigering av foto.
 * Tilsvarer Access-form: frmNMMPrimus.
 */

// VIKTIG: Auth og validering FØR layout
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/primus_modell.php';
require_once __DIR__ . '/../foto/foto_modell.php';

require_login();

$db = db();

// Flag for å spore om vi har gjenopprettet skjemadata fra session
$restoredFromSession = false;

// Sjekk om dette er en ny rad (ikke lagret ennå)
// Støtt både GET og POST (POST fra fartoy_velg.php)
$nyRad = (isset($_GET['ny_rad']) && (int)$_GET['ny_rad'] === 1) ||
         (isset($_POST['ny_rad']) && (int)$_POST['ny_rad'] === 1);

if ($nyRad) {
    // Ny rad: hent kandidatdata fra session
    // VIKTIG: Hvis vi har lagrede skjemadata (fra validering), hent serie derfra først
    $serie = (string)($_SESSION['primus_ny_serie'] ?? '');
    $kandidatSerNr = (int)($_SESSION['primus_ny_kandidat_sernr'] ?? 0);

    // Hvis vi har lagrede skjemadata, bruk serie og SerNr derfra
    if (!empty($_SESSION['primus_form_data'])) {
        $savedData = $_SESSION['primus_form_data'];
        if (!empty($savedData['NMMSerie'])) {
            $serie = (string)$savedData['NMMSerie'];
        }
        if (!empty($savedData['SerNr'])) {
            $kandidatSerNr = (int)$savedData['SerNr'];
        }
    }

    if ($serie === '' || $kandidatSerNr === 0) {
        redirect('primus_main.php');
    }

    // Opprett et tomt foto-objekt med kandidatverdier
    $foto = [
        'Foto_ID' => null, // Null = ikke lagret ennå
        'SerNr' => $kandidatSerNr,
        'Bilde_Fil' => $serie . '-' . str_pad((string)$kandidatSerNr, 3, '0', STR_PAD_LEFT),
        'NMMSerie' => $serie,
        'Transferred' => 0,
        'UUID' => generate_uuid_v4(), // Generate UUID for new photo
    ];
    $fotoId = null;

    // Hvis det finnes lagrede skjemadata (fra feilet validering), bruk disse
    $restoredFromSession = false;
    if (!empty($_SESSION['primus_form_data'])) {
        $savedData = $_SESSION['primus_form_data'];
        $restoredFromSession = true;
        // Overstyr kun felt som ikke er kritiske for radidentitet
        foreach ($savedData as $key => $value) {
            if ($key !== 'Foto_ID' && $key !== 'action' && $key !== 'csrf_token') {
                $foto[$key] = $value;
            }
        }
        // VIKTIG: Oppdater hendelsesmodus fra lagrede data
        if (isset($savedData['iCh'])) {
            $_SESSION['primus_iCh'] = (int)$savedData['iCh'];
        }
        // Rydd opp
        unset($_SESSION['primus_form_data']);
    }
} else {
    // Eksisterende rad: hent fra database
    // Støtt både GET og POST (POST fra fartoy_velg.php)
    $fotoId = filter_input(INPUT_GET, 'Foto_ID', FILTER_VALIDATE_INT) ?:
              filter_input(INPUT_POST, 'Foto_ID', FILTER_VALIDATE_INT);
    if (!$fotoId) {
        redirect('primus_main.php');
    }

    $foto = foto_hent_en($db, $fotoId);
    if (!$foto) {
        redirect('primus_main.php');
    }

    // Hvis det finnes lagrede skjemadata (fra feilet validering), bruk disse
    if (!empty($_SESSION['primus_form_data'])) {
        $savedData = $_SESSION['primus_form_data'];
        $restoredFromSession = true;
        // Overstyr kun felt som ikke er kritiske for radidentitet
        foreach ($savedData as $key => $value) {
            if ($key !== 'Foto_ID' && $key !== 'action' && $key !== 'csrf_token') {
                $foto[$key] = $value;
            }
        }
        // VIKTIG: Oppdater hendelsesmodus fra lagrede data
        if (isset($savedData['iCh'])) {
            $_SESSION['primus_iCh'] = (int)$savedData['iCh'];
        }
        // Rydd opp
        unset($_SESSION['primus_form_data']);
    }
}

// BASE_URL for JavaScript
$baseUrlJs = base_url_js();

// --------------------------------------------------
// Helpers for form fields
// --------------------------------------------------
function txt(string $n, string $l, string $v = '', bool $readonly = false, string $width = '100%'): void
{
    $ro = $readonly ? 'readonly' : '';
    echo "<div class='form-group' style='width:$width;'>
            <label for='$n'>$l</label>
            <input type='text' name='$n' id='$n' value='" . h($v) . "' $ro>
          </div>";
}

function num(string $n, string $l, int $v = 0, bool $readonly = false, string $width = '100%'): void
{
    $ro = $readonly ? 'readonly' : '';
    echo "<div class='form-group' style='width:$width;'>
            <label for='$n'>$l</label>
            <input type='number' name='$n' id='$n' value='" . h((string)$v) . "' $ro>
          </div>";
}

function area(string $n, string $l, string $v = '', int $r = 3): void
{
    echo "<div class='form-group'>
            <label for='$n'>$l</label>
            <textarea name='$n' id='$n' rows='$r'>" . h($v) . "</textarea>
          </div>";
}

function chk(string $n, string $l, bool $v = false): void
{
    $c = $v ? 'checked' : '';
    echo "<label class='form-check' for='$n'>
            <input type='checkbox' name='$n' id='$n' value='1' $c> $l
          </label>";
}

function combo(string $n, string $l, array $opts, string $v = '', string $width = '100%'): void
{
    echo "<div class='form-group' style='width:$width;'>
            <label for='$n'>$l</label>
            <select name='$n' id='$n' style='width:100%;'>";
    foreach ($opts as $opt) {
        $o = (string)$opt;
        $sel = $o === $v ? 'selected' : '';
        echo "<option value='" . h($o) . "' $sel>" . h($o) . "</option>";
    }
    echo "    </select>
          </div>";
}

// --------------------------------------------------
// H2-modus: kandidatpanel klikkbart kun ved ny rad
// --------------------------------------------------
$h2 = (int)($_SESSION['primus_h2'] ?? 0) === 1;

// --------------------------------------------------
// Defaults i Øvrige (kun ved ny rad / H2)
// Access-paritet iht Primus_Schema.md
// VIKTIG: Ikke overstyr verdier hvis vi har gjenopprettet fra session
// --------------------------------------------------
if ($h2 && !$restoredFromSession) {
    if (empty($foto['Status'])) {
        $foto['Status'] = 'Original';
    }
    if (empty($foto['Plassering'])) {
        $foto['Plassering'] = '0286/Mus/Bib';
    }
    if (empty($foto['Prosess'])) {
        $foto['Prosess'] = 'Positivkopi;300';
    }
    if (empty($foto['Tilstand'])) {
        $foto['Tilstand'] = 'God';
    }

    // For nye rader: sørg for at hendelsesmodus er 'Ingen' (1)
    // og at referansefeltene er tomme (krav fra Access-paritet/brukerhistorie)
    $_SESSION['primus_iCh'] = 1;
    $foto['ReferNeg'] = null;
    $foto['ReferFArk'] = null;
}

$svarthvittValg = ['Svart-hvit', 'Farge', 'Håndkolorert'];
if (!isset($foto['Svarthvitt']) || $foto['Svarthvitt'] === '' || $foto['Svarthvitt'] === null) {
    $foto['Svarthvitt'] = $svarthvittValg[0];
}

$samlingValg = ['C2-Johnsen, Per-Erik', 'C2-Gjersøe, Georg', 'C2-'];

// --------------------------------------------------
// Hendelsesmodus (iCh) – session-paritet
// --------------------------------------------------
// NB: Hvis dette er en ny rad (H2-modus), tvinges hendelsesmodus til 1
if (isset($_SESSION['primus_iCh'])) {
    $iCh = (int)$_SESSION['primus_iCh'];
} else {
    $iCh = (int)($foto['iCh'] ?? 1);
}
if ($iCh < 1 || $iCh > 6) {
    $iCh = 1;
}

// Dismiss UI-debug if requested
if (isset($_GET['clear_foto_debug']) && (int)$_GET['clear_foto_debug'] === 1) {
    unset($_SESSION['foto_debug_last']);
    // Redirect to the same page without the query param
    $base = 'primus_detalj.php?Foto_ID=' . (int)$fotoId;
    $ks = trim((string)($_GET['k_sok'] ?? ''));
    if ($ks !== '') {
        $base .= '&k_sok=' . rawurlencode($ks);
    }
    redirect($base);
}

// --------------------------------------------------
// Aktiv fane (primus_tab) – session-paritet
// --------------------------------------------------
$aktivTab = 'motiv';
if (isset($_SESSION['primus_tab'])) {
    $t = (string)$_SESSION['primus_tab'];
    if (in_array($t, ['motiv', 'historikk', 'ovrige'], true)) {
        $aktivTab = $t;
    }
}

// --------------------------------------------------
// Kandidatsøk (venstre panel) – alltid synlig
// --------------------------------------------------
// Arv søket fra GET hvis oppgitt, ellers fra forrige sesjon (primus_k_sok)
$kandidatSok = trim((string)($_GET['k_sok'] ?? ($_SESSION['primus_k_sok'] ?? '')));
$kandidater = primus_hent_skip_liste($kandidatSok);

// --------------------------------------------------
// POST: "Legg til i 'Avbildet'" (additiv via fartoy_velg.php)
// SIKKERHET: Konvertert fra GET til POST med CSRF-beskyttelse
// --------------------------------------------------
if (is_post() && isset($_POST['add_avbildet_nmm_id'])) {
    if (!csrf_validate()) {
        die('Ugyldig forespørsel (CSRF).');
    }

    $addAvbId = filter_var($_POST['add_avbildet_nmm_id'], FILTER_VALIDATE_INT);
    if ($addAvbId) {
        $felt = primus_hent_kandidat_felter((int)$addAvbId);
        if (!empty($felt['Avbildet']) && $felt['ok']) {
            $ny = trim((string)$felt['Avbildet']);

            $eks = (string)($foto['Avbildet'] ?? '');
            $eksTrim = trim($eks);

            $separator = ";\n";
            $liste = [];

            if ($eksTrim !== '') {
                foreach (preg_split("/;\s*\n/", $eksTrim) ?: [] as $del) {
                    $del = trim((string)$del);
                    if ($del !== '') $liste[] = $del;
                }
            }

            // Dedupe
            $finnes = false;
            foreach ($liste as $del) {
                if (mb_strtolower($del) === mb_strtolower($ny)) {
                    $finnes = true;
                    break;
                }
            }
            if (!$finnes) {
                $liste[] = $ny;
            }

            $foto['Avbildet'] = implode($separator, $liste);

            // Lagre til database KUN hvis dette er en eksisterende rad
            if (!$nyRad && $fotoId) {
                $stmt = $db->prepare("UPDATE nmmfoto SET Avbildet = :avbildet WHERE Foto_ID = :foto_id");
                $stmt->execute([
                    'avbildet' => $foto['Avbildet'],
                    'foto_id' => $fotoId
                ]);
            }
            // For nye rader: verdien er oppdatert i $foto-arrayen og vil vises i skjemaet
        }

        // Redirect til samme side uten POST-data
        if ($nyRad) {
            $base = 'primus_detalj.php?ny_rad=1';
        } else {
            $base = 'primus_detalj.php?Foto_ID=' . (int)$fotoId;
        }
        if ($kandidatSok !== '') {
            $base .= '&k_sok=' . rawurlencode($kandidatSok);
        }
        redirect($base);
    }
}

// --------------------------------------------------
// POST: kopier foto (Access: cmdKopier)
// --------------------------------------------------
if (is_post() && ($_POST['action'] ?? '') === 'kopier_foto') {
    if (!csrf_validate()) {
        die('Ugyldig forespørsel (CSRF).');
    }

    try {
        // HVIS DETTE ER EN NY RAD: Lagre den først, deretter kopier
        if ($nyRad || $fotoId === null) {
            // Lagre den nye raden først (samme logikk som Oppdater-knappen)
            $data = $_POST;

            // Validering: NMM_ID må være satt
            if (empty($data['NMM_ID']) || trim((string)$data['NMM_ID']) === '' || (int)$data['NMM_ID'] === 0) {
                $_SESSION['primus_form_data'] = $data;
                $redirectUrl = 'primus_detalj.php?ny_rad=1';
                echo '<script>alert("FEIL: Du må velge et fartøy fra kandidatlisten før kopiering."); window.location.href = "' . $redirectUrl . '";</script>';
                exit;
            }

            // Validering: SerNr må være mellom 1 og 999
            if (isset($data['SerNr'])) {
                $serNr = (int)$data['SerNr'];
                if ($serNr < 1 || $serNr > 999) {
                    $_SESSION['primus_form_data'] = $data;
                    $redirectUrl = 'primus_detalj.php?ny_rad=1';
                    echo '<script>alert("FEIL: SerNr må være mellom 1 og 999"); window.location.href = "' . $redirectUrl . '";</script>';
                    exit;
                }
            }

            // KORRIGERT: Bilde_Fil = NMMSerie-SerNr (3 siffer med leading zeros)
            if (!empty($data['NMMSerie']) && isset($data['SerNr'])) {
                $data['Bilde_Fil'] = $data['NMMSerie'] . '-' . str_pad((string)(int)$data['SerNr'], 3, '0', STR_PAD_LEFT);
            }

            // FotoTidTil automatisk lik FotoTidFra hvis ikke angitt
            if (isset($data['FotoTidFra'])) {
                $fotoTidFra = trim((string)$data['FotoTidFra']);
                $fotoTidTil = isset($data['FotoTidTil']) ? trim((string)$data['FotoTidTil']) : '';

                if ($fotoTidFra !== '' && $fotoTidTil === '') {
                    $data['FotoTidTil'] = $fotoTidFra;
                }
            }

            // Checkbox-håndtering: hvis ikke i POST, sett til 0 (ikke krysset av)
            $checkboxFelter = ['Aksesjon', 'Fotografi', 'FriKopi'];
            foreach ($checkboxFelter as $chk) {
                if (!isset($data[$chk])) {
                    $data[$chk] = 0;
                }
            }

            // Opprett i database (INSERT)
            $data['Transferred'] = 0;
            unset($data['Foto_ID']); // Sikre at vi ikke sender Foto_ID for INSERT
            $lagredesFotoId = foto_lagre($db, $data);

            // Lagre siste SerNr for bruker-tracking
            if (isset($data['SerNr']) && !empty($data['Bilde_Fil'])) {
                $userId = (int)($_SESSION['user_id'] ?? 0);
                $bildeFil = (string)$data['Bilde_Fil'];
                if ($userId > 0 && strlen($bildeFil) >= 8) {
                    $serie = substr($bildeFil, 0, 8);
                    $serNr = (int)$data['SerNr'];
                    primus_lagre_siste_sernr_for_bruker($userId, $serie, $serNr);
                }
            }

            // Rydd opp session
            unset($_SESSION['primus_ny_serie']);
            unset($_SESSION['primus_ny_kandidat_sernr']);

            // NÅ har vi et lagret foto med $lagredesFotoId - fortsett med kopiering
        } else {
            // EKSISTERENDE RAD: Bruk eksisterende $fotoId
            $lagredesFotoId = $fotoId;
        }

        // Hent det lagrede fotoet som skal kopieres
        $eksisterende = foto_hent_en($db, $lagredesFotoId);
        if (!$eksisterende) {
            echo '<script>alert("FEIL: Finner ikke foto."); window.history.back();</script>';
            exit;
        }

        // Validering: NMM_ID må være satt i det lagrede fotoet
        if (empty($eksisterende['NMM_ID']) || (int)$eksisterende['NMM_ID'] === 0) {
            echo '<script>alert("FEIL: Fotoet mangler fartøy (NMM_ID). Dette bør ikke skje."); window.history.back();</script>';
            exit;
        }
        $bildeFil = (string)($eksisterende['Bilde_Fil'] ?? '');
        $serie = '';

        if (strlen($bildeFil) >= 8) {
            $serie = substr($bildeFil, 0, 8);
        }

        // Kopier foto (nullstiller Bildehistorikk og Øvrige)
        $nyFotoId = foto_kopier($db, $lagredesFotoId);

        // Oppdater SerNr og Bilde_Fil for den nye kopien
        if ($serie !== '') {
            $userId = (int)($_SESSION['user_id'] ?? 0);
            // Bruk brukerens siste SerNr som utgangspunkt (slik Access gjør)
            $nesteSerNr = primus_hent_neste_sernr_for_bruker($userId, $serie);

            // Validering: SerNr må være mellom 1 og 999
            if ($nesteSerNr < 1 || $nesteSerNr > 999) {
                die('FEIL: SerNr må være mellom 1 og 999. Neste tilgjengelige SerNr (' . $nesteSerNr . ') er utenfor tillatt område.');
            }

            $stmt = $db->prepare("
                UPDATE nmmfoto
                SET SerNr = :sernr,
                    Bilde_Fil = :bilde_fil,
                    URL_Bane = :url_bane
                WHERE Foto_ID = :foto_id
            ");
            $stmt->execute([
                'sernr' => $nesteSerNr,
                'bilde_fil' => $serie . '-' . str_pad((string)$nesteSerNr, 3, '0', STR_PAD_LEFT),
                'url_bane' => FOTO_URL_PREFIX . $serie . ' -001-999 Damp og Motor',
                'foto_id' => $nyFotoId
            ]);

            // Lagre siste SerNr for ny kopi
            if ($userId > 0) {
                primus_lagre_siste_sernr_for_bruker($userId, $serie, $nesteSerNr);
            }
        }

        // Hent fartøynavn fra det kopierte fotoet for å vise det i kandidatlisten
        $nmmIdKopi = (int)($eksisterende['NMM_ID'] ?? 0);
        $kandidatSokKopi = '';
        if ($nmmIdKopi > 0) {
            $stmtSkip = $db->prepare("SELECT FNA FROM nmm_skip WHERE NMM_ID = :id LIMIT 1");
            $stmtSkip->execute(['id' => $nmmIdKopi]);
            $skipRow = $stmtSkip->fetch();
            if ($skipRow) {
                $kandidatSokKopi = (string)($skipRow['FNA'] ?? '');
            }
        }

        // H2-modus = 1 (venstre panel SYNLIG/klikkbart - kopien kan tilknyttes nytt fartøy)
        $_SESSION['primus_h2'] = 1;
        $_SESSION['primus_iCh'] = 1; // Hendelsesmodus = Ingen

        // Sett søkestreng i session slik at det kopierte fartøyet vises i kandidatlisten
        if ($kandidatSokKopi !== '') {
            $_SESSION['primus_k_sok'] = $kandidatSokKopi;
        }

        redirect('primus_detalj.php?Foto_ID=' . $nyFotoId);

    } catch (Throwable $e) {
        // Ensure debug info available in session for UI panel
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (empty($_SESSION['foto_debug_last'])) {
            $_SESSION['foto_debug_last'] = [
                'when' => date('c'),
                'context' => 'kopier_foto',
                'message' => $e->getMessage(),
                'foto_id' => $fotoId,
            ];
        }

        // Redirect back to the same detail page so the debug panel can be displayed
        redirect('primus_detalj.php?Foto_ID=' . $fotoId);
    }
}

// --------------------------------------------------
// POST: lagre foto (først her skjer DB-lagring)
// --------------------------------------------------
if (is_post() && ($_POST['action'] ?? '') !== 'kopier_foto') {
    // DEBUG: Se hva som sendes
file_put_contents('c:/xampp/htdocs/debug_post.txt', print_r($_POST, true));
    if (!csrf_validate()) {
        die('Ugyldig forespørsel (CSRF).');
    }

    $data = $_POST;

    // Validering: NMM_ID må være satt (i stedet for ValgtFartøy som er readonly display)
    if (empty($data['NMM_ID']) || trim((string)$data['NMM_ID']) === '' || (int)$data['NMM_ID'] === 0) {
        // Lagre POST-data i session slik at skjemaet kan fylles ut igjen
        $_SESSION['primus_form_data'] = $data;
        // Bygg redirect URL basert på om dette er ny rad eller eksisterende
        if ($nyRad || empty($fotoId)) {
            $redirectUrl = 'primus_detalj.php?ny_rad=1';
        } else {
            $redirectUrl = 'primus_detalj.php?Foto_ID=' . (int)$fotoId;
        }
        echo '<script>alert("FEIL: Du må velge et fartøy fra kandidatlisten før lagring."); window.location.href = "' . $redirectUrl . '";</script>';
        exit;
    }

    // Validering: SerNr må være mellom 1 og 999
    if (isset($data['SerNr'])) {
        $serNr = (int)$data['SerNr'];
        if ($serNr < 1 || $serNr > 999) {
            // Lagre POST-data i session slik at skjemaet kan fylles ut igjen
            $_SESSION['primus_form_data'] = $data;
            // Bygg redirect URL basert på om dette er ny rad eller eksisterende
            if ($nyRad || empty($fotoId)) {
                $redirectUrl = 'primus_detalj.php?ny_rad=1';
            } else {
                $redirectUrl = 'primus_detalj.php?Foto_ID=' . (int)$fotoId;
            }
            echo '<script>alert("FEIL: SerNr må være mellom 1 og 999"); window.location.href = "' . $redirectUrl . '";</script>';
            exit;
        }
    }

    // KORRIGERT: Bilde_Fil = NMMSerie-SerNr (3 siffer med leading zeros)
    if (!empty($data['NMMSerie']) && isset($data['SerNr'])) {
        $data['Bilde_Fil'] = $data['NMMSerie'] . '-' . str_pad((string)(int)$data['SerNr'], 3, '0', STR_PAD_LEFT);
    }

    // FotoTidTil automatisk lik FotoTidFra hvis ikke angitt
    if (isset($data['FotoTidFra'])) {
        $fotoTidFra = trim((string)$data['FotoTidFra']);
        $fotoTidTil = isset($data['FotoTidTil']) ? trim((string)$data['FotoTidTil']) : '';

        if ($fotoTidFra !== '' && $fotoTidTil === '') {
            $data['FotoTidTil'] = $fotoTidFra;
        }
    }

    // Checkbox-håndtering: hvis ikke i POST, sett til 0 (ikke krysset av)
    $checkboxFelter = ['Aksesjon', 'Fotografi', 'FriKopi'];
    foreach ($checkboxFelter as $chk) {
        if (!isset($data[$chk])) {
            $data[$chk] = 0;
        }
    }

    // Sjekk om dette er en ny rad eller eksisterende
    if ($nyRad || $fotoId === null) {
        // NY RAD: Opprett i database (INSERT)
        $data['Transferred'] = 0;
        unset($data['Foto_ID']); // Sikre at vi ikke sender Foto_ID for INSERT
        $nyFotoId = foto_lagre($db, $data);

        // Lagre siste SerNr for bruker-tracking
        if (isset($data['SerNr']) && !empty($data['Bilde_Fil'])) {
            $userId = (int)($_SESSION['user_id'] ?? 0);
            $bildeFil = (string)$data['Bilde_Fil'];
            if ($userId > 0 && strlen($bildeFil) >= 8) {
                $serie = substr($bildeFil, 0, 8);
                $serNr = (int)$data['SerNr'];
                primus_lagre_siste_sernr_for_bruker($userId, $serie, $serNr);
            }
        }

        // Rydd opp session
        unset($_SESSION['primus_ny_serie']);
        unset($_SESSION['primus_ny_kandidat_sernr']);
        $_SESSION['primus_h2'] = 0; // Deaktiver H2-modus

        // Sjekk om vi skal gå til fartøyvalg etter lagring
        $gotoFartoyvalg = isset($_POST['save_and_goto_fartoyvalg']) && (int)$_POST['save_and_goto_fartoyvalg'] === 1;

        if ($gotoFartoyvalg) {
            // Gå til fartøyvalg for å legge til i Avbildet
            $retUrl = 'primus_detalj.php?Foto_ID=' . (int)$nyFotoId;
            if ($kandidatSok !== '') {
                $retUrl .= '&k_sok=' . rawurlencode($kandidatSok);
            }
            redirect('../fartoy/fartoy_velg.php?Foto_ID=' . (int)$nyFotoId . '&ret=' . rawurlencode($retUrl) . '&mode=add_avbildet');
        } else {
            // Normal flyt: gå tilbake til hovedsiden
            $side = primus_finn_side_for_foto($nyFotoId);
            redirect('primus_main.php?side=' . $side);
        }
    } else {
        // EKSISTERENDE RAD: Oppdater i database (UPDATE)
        $data['Foto_ID'] = $fotoId;
        foto_lagre($db, $data);

        // Lagre siste SerNr for bruker-tracking
        if (isset($data['SerNr']) && !empty($data['Bilde_Fil'])) {
            $userId = (int)($_SESSION['user_id'] ?? 0);
            $bildeFil = (string)$data['Bilde_Fil'];
            if ($userId > 0 && strlen($bildeFil) >= 8) {
                $serie = substr($bildeFil, 0, 8);
                $serNr = (int)$data['SerNr'];
                primus_lagre_siste_sernr_for_bruker($userId, $serie, $serNr);
            }
        }

        // Finn sidenummer og redirect til riktig side
        $side = primus_finn_side_for_foto($fotoId);
        redirect('primus_main.php?side=' . $side);
    }
}

// --------------------------------------------------
// Toppfelt: NMMSerie + SerNr + Bilde_Fil
// SerNr avledes av Bilde_Fil og NMMSerie (8 første tegn = NMMSerie)
// VIKTIG: Hvis vi gjenopprettet fra session, behold alle verdier som de er
// --------------------------------------------------
$sernr = (int)($foto['SerNr'] ?? 0);
$bild = (string)($foto['Bilde_Fil'] ?? '');
$serieFraBild = (strlen($bild) >= 8) ? substr($bild, 0, 8) : '';

$nmmSerie = (string)($foto['NMMSerie'] ?? $serieFraBild);
if ($nmmSerie === '' && $serieFraBild !== '') {
    $nmmSerie = $serieFraBild;
}

// Kun gjør beregninger hvis vi IKKE har gjenopprettet fra session
if (!$restoredFromSession) {
    if ($nmmSerie !== '' && str_starts_with($bild, $nmmSerie)) {
        // plukk ut alle siffer etter serien (tåler både "NSM.2113-652" og "NSM.2113652")
        $rest = substr($bild, 8);
        if (preg_match('/^-?(\d+)/', $rest, $m)) {
            $sernr = (int)$m[1];
        }
    }

    // H2-modus: Foreslå SerNr basert på brukerens siste innleggelse i denne serien
    // VIKTIG: Kun for NYE rader (ikke eksisterende rader som kopieres)
    if ($h2 && $nmmSerie !== '' && $nyRad) {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId > 0) {
            $sernr = primus_hent_neste_sernr_for_bruker($userId, $nmmSerie);
        }
    }
}

// --------------------------------------------------
// ValgtFartøy + FTO: alltid basert på valgt NMM_ID
// (hvis NMM_ID finnes)
// --------------------------------------------------
$valgtFartoyVis = (string)($foto['ValgtFartøy'] ?? '');
$ftoVis = (string)($foto['FTO'] ?? '');

$aktNmmId = (int)($foto['NMM_ID'] ?? 0);
if ($aktNmmId > 0) {
    $felt = primus_hent_kandidat_felter($aktNmmId);
    if (!empty($felt['ok'])) {
        $valgtFartoyVis = (string)$felt['ValgtFartoy'];
        $ftoVis = (string)$felt['FTO'];
    }
}

// NÅ kan vi inkludere layout (etter all logikk)
$pageTitle = 'Primus – foto';
require_once __DIR__ . '/../../includes/layout_start.php';
?>

<div class="container-fluid">

    <div class="flex-row-space-between">
        <h1 class="m-0">Primus – foto</h1>
        <div class="flex-row">
            <button type="submit" form="foto-form" class="btn btn-primary">Oppdater</button>
            <form method="post" class="inline-form">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="kopier_foto">
                <button type="submit" class="btn btn-info" onclick="return confirm('Lagre og kopiere dette fotoet?');">Kopier foto</button>
            </form>
            <a href="<?= $nyRad ? 'primus_main.php?avbryt_ny=1' : 'primus_main.php' ?>" class="btn btn-secondary">Tilbake</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">

            <form method="post" id="foto-form">
                <?= csrf_field(); ?>

                <!-- Hidden field for å indikere at vi skal gå til fartøyvalg etter lagring -->
                <input type="hidden" name="save_and_goto_fartoyvalg" id="save_and_goto_fartoyvalg" value="0">

                <!-- Kandidatstyrt NMM_ID (H2). I H1 beholder vi DB-verdi. -->
                <input type="hidden" name="NMM_ID" id="NMM_ID" value="<?= h((string)($foto['NMM_ID'] ?? '')) ?>">

                <!-- UUID (system field, preserved on update, generated on insert) -->
                <input type="hidden" name="UUID" id="UUID" value="<?= h((string)($foto['UUID'] ?? '')) ?>">

                <!-- TOPP: ValgtFartøy -->
                <div class="flex-wrap">
                    <div class="form-group w-420px-form">
                        <label for="ValgtFartoy_vis" class="text-center-label">Valgt fartøy</label>
                        <input type="text" name="ValgtFartoy_vis" id="ValgtFartoy_vis" value="<?= h($valgtFartoyVis) ?>" readonly class="text-large-bold">
                    </div>
                </div>

                <hr>

                <!-- Serie / fil -->
                <div class="flex-wrap-end" style="align-items: flex-start;">
                    <div class="form-group w-15ch">
                        <label for="NMMSerie">NSM serie</label>
                        <select name="NMMSerie" id="NMMSerie">
                            <?php
                            // Enkel serie-liste fra bildeserie-tabellen (scroll i select)
                            $serier = primus_hent_bildeserier();
                            foreach ($serier as $s) {
                                $sv = (string)($s['Serie'] ?? '');
                                if ($sv === '') continue;
                                $sel = ($sv === $nmmSerie) ? 'selected' : '';
                                echo '<option value="' . h($sv) . '" ' . $sel . '>' . h($sv) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <?php
                    $bildeFilVis = (string)($foto['Bilde_Fil'] ?? '');
                    if ($nmmSerie !== '' && $sernr > 0) {
                        $bildeFilVis = $nmmSerie . '-' . str_pad((string)$sernr, 3, '0', STR_PAD_LEFT);
                    }
                    ?>
                    <div class="form-group w-7ch">
                        <label for="SerNr">Serienr</label>
                        <input type="number" name="SerNr" id="SerNr" value="<?= h((string)$sernr) ?>" min="1" max="999" required>
                    </div>
                    <div class="form-group w-15ch">
                        <label for="Bilde_Fil">Bildefil</label>
                        <input type="text" name="Bilde_Fil" id="Bilde_Fil" value="<?= h($bildeFilVis) ?>" readonly>
                    </div>
                    <div class="form-group w-10ch">
                        <label for="ReferNeg_top">Negativref</label>
                        <input type="text" id="ReferNeg_top" value="<?= h((string)($foto['ReferNeg'] ?? '')) ?>">
                    </div>
                    <div class="form-group flex-auto">
                        <label for="FTO_vis">Bilde kommentarer</label>
                        <textarea name="FTO_vis" id="FTO_vis" rows="2" readonly style="resize: vertical; overflow-y: auto;"><?= h($ftoVis) ?></textarea>
                    </div>
                </div>

                <hr>

                <div class="flex-row-start">

                    <!-- VENSTRE: kandidater -->
                    <div class="flex-fixed-420">
                        <div class="card">
                            <div class="card-header">
                                <strong>Kandidater</strong>
                                <?php if (!$h2): ?>
                                    <div class="text-hint">
                                        (Klikk for å endre fartøy - krever bekreftelse)
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">

                                <div class="mb-3">
                                    <input type="hidden" id="k_sok_foto_id" value="<?= h((string)$fotoId) ?>">
                                    <div class="form-group">
                                        <label for="k_sok">Søk i fartøynavn</label>
                                        <input type="text" id="k_sok" value="<?= h($kandidatSok) ?>">
                                    </div>
                                    <button class="btn btn-secondary" type="button" id="btn-kandidat-sok">Søk</button>
                                </div>

                                <div class="table-scroll-520">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Fartøy</th>
                                                <th class="nowrap">BYG</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($kandidater as $k): ?>
                                            <?php
                                            $kid = (int)$k['NMM_ID'];
                                            $navn = trim((string)$k['FTY'] . ' ' . (string)$k['FNA']);
                                            $byg = (string)($k['BYG'] ?? '');
                                            ?>
                                            <tr class="kandidat-rad" data-nmm-id="<?= h((string)$kid) ?>" data-navn="<?= h($navn) ?>">
                                                <td><?= h($navn) ?></td>
                                                <td class="nowrap"><?= h($byg) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- HØYRE: faner -->
                    <div class="flex-min-520">

                        <div class="primus-tabs">

                            <div class="primus-tabbar">
                                <button type="button"
                                        class="primus-tab <?= $aktivTab === 'motiv' ? 'is-active' : '' ?>"
                                        data-tab="motiv">Motiv</button>

                                <button type="button"
                                        class="primus-tab <?= $aktivTab === 'historikk' ? 'is-active' : '' ?>"
                                        data-tab="historikk">Bildehistorikk</button>

                                <button type="button"
                                        class="primus-tab <?= $aktivTab === 'ovrige' ? 'is-active' : '' ?>"
                                        data-tab="ovrige">Øvrige</button>
                            </div>

                            <div class="primus-pane <?= $aktivTab === 'motiv' ? 'is-active' : '' ?>" id="motiv">
                                <?php
                                area('MotivBeskr', 'Motivbeskrivelse', (string)($foto['MotivBeskr'] ?? ''), 4);
                                area('MotivBeskrTillegg', 'Tillegg, Motivbeskrivelse', (string)($foto['MotivBeskrTillegg'] ?? ''), 3);

                                area('Avbildet', 'Avbildet', (string)($foto['Avbildet'] ?? ''), 3);
                                ?>
                                <div class="mb-2">
                                    <?php if ($nyRad): ?>
                                        <!-- Ny rad: lagre automatisk og gå til fartøyvalg -->
                                        <button type="button" class="btn btn-secondary" onclick="lagreOgGaaTilFartoyvalg()">
                                            Legg til i 'Avbildet'
                                        </button>
                                    <?php else: ?>
                                        <!-- Eksisterende rad: kan legge til i Avbildet -->
                                        <?php
                                        $retUrl = 'primus_detalj.php?Foto_ID=' . (int)$fotoId;
                                        $fotoIdParam = 'Foto_ID=' . (int)$fotoId;
                                        if ($kandidatSok !== '') {
                                            $retUrl .= '&k_sok=' . rawurlencode($kandidatSok);
                                        }
                                        ?>
                                        <a class="btn btn-secondary"
                                           href="../fartoy/fartoy_velg.php?<?= $fotoIdParam ?>&ret=<?= rawurlencode($retUrl) ?>&mode=add_avbildet">
                                            Legg til i 'Avbildet'
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <hr>

                                <?php
                                area('MotivType', 'Motivtype', (string)($foto['MotivType'] ?? ''), 3);
                                ?>
                                <div class="mb-2">
                                    <button type="button" class="btn btn-secondary" id="btn-leggtil-skipsportrett">
                                        Legg til 'Skipsportrett'
                                    </button>
                                </div>

                                <?php
                                area('MotivEmne', 'Motivemne', (string)($foto['MotivEmne'] ?? ''), 3);
                                area('MotivKriteria', 'Søkekriteria', (string)($foto['MotivKriteria'] ?? ''), 3);
                                ?>
                            </div>

                            <div class="primus-pane <?= $aktivTab === 'historikk' ? 'is-active' : '' ?>" id="historikk">
                                <strong>Hendelsesmodus</strong>
                                <div class="hendelser-rad">
                                    <?php
                                    $lbl = [1 => 'Ingen', 2 => 'Fotografi', 3 => 'Samling', 4 => 'Foto+Samling', 5 => 'Annet', 6 => 'Alle'];
                                    foreach ($lbl as $v => $t):
                                    ?>
                                        <label class="form-check">
                                            <input type="radio" name="iCh" value="<?= $v ?>" <?= $iCh === $v ? 'checked' : '' ?>>
                                            <?= $v ?> – <?= h($t) ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>

                                <?php
                                // Hendelse: Auto-filled based on iCh, readonly but enabled (must submit in POST)
                                echo "<div class='form-group'>
                                        <label for='Hendelse'>Hendelse</label>
                                        <textarea name='Hendelse' id='Hendelse' rows='2' readonly style='background-color: #ffe6e6; border: 2px solid #cc0000;'>" . h((string)($foto['Hendelse'] ?? '')) . "</textarea>
                                      </div>";
                                combo('Samling', 'Samling', $samlingValg, (string)($foto['Samling'] ?? ''));
                                chk('FriKopi', 'Fri kopi', (bool)($foto['FriKopi'] ?? false));
                                txt('Fotograf', 'Fotograf', (string)($foto['Fotograf'] ?? ''));
                                txt('FotoFirma', 'Fotofirma', (string)($foto['FotoFirma'] ?? ''));
                                txt('FotoTidFra', 'Tid (Fra)', (string)($foto['FotoTidFra'] ?? ''));
                                txt('FotoTidTil', 'Tid (Til)', (string)($foto['FotoTidTil'] ?? ''));
                                txt('FotoSted', 'Sted tatt', (string)($foto['FotoSted'] ?? ''));
                                ?>
                            </div>

                            <div class="primus-pane <?= $aktivTab === 'ovrige' ? 'is-active' : '' ?>" id="ovrige">
                                <?php
                                txt('ReferNeg', 'Referanse, NMM', (string)($foto['ReferNeg'] ?? ''));
                                txt('ReferFArk', 'Referanse, fotograf', (string)($foto['ReferFArk'] ?? ''));
                                ?>
                                <hr>
                                <?php
                                txt('Plassering', 'Plassering', (string)($foto['Plassering'] ?? ''));
                                txt('Prosess', 'Prosess', (string)($foto['Prosess'] ?? ''));
                                ?>
                                <hr>
                                <?php
                                $tilstandValg = ['God', 'Dårlig'];
                                $tilstandVerdi = (string)($foto['Tilstand'] ?? 'God');
                                if ($tilstandVerdi === '' || $tilstandVerdi === null) {
                                    $tilstandVerdi = 'God';
                                }

                                combo('Svarthvitt', 'Svarthvitt', $svarthvittValg, (string)($foto['Svarthvitt'] ?? ''));
                                txt('Status', 'Status', (string)($foto['Status'] ?? ''));
                                combo('Tilstand', 'Tilstand', $tilstandValg, $tilstandVerdi);
                                ?>
                                <hr>
                                <?php area('Merknad', 'Merknad', (string)($foto['Merknad'] ?? ''), 4); ?>
                            </div>

                        </div>

                    </div>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
(function(){
    var baseUrl = <?= $baseUrlJs ?>;

    // ---------------- Tabs ----------------
    document.querySelectorAll('.primus-tab').forEach(function(tab) {
        tab.addEventListener('click', function () {
            var valgt = this.dataset.tab;
            if (!valgt) return;

            document.querySelectorAll('.primus-tab')
                .forEach(function(t) { t.classList.toggle('is-active', t === tab); });

            document.querySelectorAll('.primus-pane')
                .forEach(function(p) { p.classList.toggle('is-active', p.id === valgt); });

            fetch(baseUrl + '/modules/primus/api/sett_session.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: 'primus_tab=' + encodeURIComponent(valgt)
            }).catch(function(){});
        });
    });

    // ---------------- Kandidatsøk (GET uten nested form) ----------------
    var sokBtn = document.getElementById('btn-kandidat-sok');
    if (sokBtn) {
        sokBtn.addEventListener('click', function () {
            var sok = document.getElementById('k_sok');
            if (!sok) return;

            <?php if ($nyRad): ?>
            var url = 'primus_detalj.php?ny_rad=1';
            <?php else: ?>
            var url = 'primus_detalj.php?Foto_ID=<?= (int)$fotoId ?>';
            <?php endif; ?>
            if (sok.value.trim() !== '') {
                url += '&k_sok=' + encodeURIComponent(sok.value.trim());
            }

            // Persist søket i session før navigasjon
            fetch(baseUrl + '/modules/primus/api/sett_session.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: 'primus_k_sok=' + encodeURIComponent(sok.value.trim())
            }).catch(function(){}).then(function(){ window.location.href = url; });
        });

        // Prevent Enter key in the candidate search field from submitting the main form.
        // This avoids accidental saves/redirects — Enter will perform the candidate search instead.
        var sokInput = document.getElementById('k_sok');
        if (sokInput) {
            sokInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();

                    // Trigger the same search behaviour as the button click,
                    // but only if there are characters in the field.
                    var val = sokInput.value.trim();
                    if (val !== '') {
                        <?php if ($nyRad): ?>
                        var url = 'primus_detalj.php?ny_rad=1';
                        <?php else: ?>
                        var url = 'primus_detalj.php?Foto_ID=<?= (int)$fotoId ?>';
                        <?php endif; ?>
                        url += '&k_sok=' + encodeURIComponent(val);

                        fetch(baseUrl + '/modules/primus/api/sett_session.php', {
                            method: 'POST',
                            headers: {'Content-Type':'application/x-www-form-urlencoded'},
                            body: 'primus_k_sok=' + encodeURIComponent(val)
                        }).catch(function(){}).then(function(){ window.location.href = url; });
                    }
                    // If empty, ignore the Enter key (do not submit form)
                }
            });
        }
    }

    // ---------------- iCh → foto_state ----------------
    function oppdaterFotoState(){
        var valgt = document.querySelector('input[name="iCh"]:checked');
        if(!valgt) return;

        fetch(baseUrl + '/modules/primus/api/sett_session.php', {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'primus_iCh=' + encodeURIComponent(valgt.value)
        }).catch(function(){});

        fetch(baseUrl + '/modules/foto/api/foto_state.php', {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'fmeHendelse=' + encodeURIComponent(valgt.value)
        })
        .then(function(r) { return r.ok ? r.json() : null; })
        .then(function(json) {
            if(!json || !json.ok || !json.data || !json.data.felter) return;

            Object.keys(json.data.felter).forEach(function(id) {
                var el = document.getElementById(id);
                if(!el) return;
                el.disabled = false;
                el.removeAttribute('data-foto-state');
            });

            Object.keys(json.data.felter).forEach(function(id) {
                var enabled = json.data.felter[id];
                var el = document.getElementById(id);
                if(!el) return;

                el.disabled = !enabled;
                el.dataset.fotoState = enabled ? 'aktiv' : 'inaktiv';

                if(!enabled){
                    if(el.type === 'checkbox' || el.type === 'radio') {
                        el.checked = false;
                    } else {
                        el.value = '';
                    }
                } else if(id === 'Fotograf' && !el.value.trim()) {
                    el.value = '10F:';
                }
            });

            // Apply verdier (field values like Hendelse)
            if(json.data.verdier) {
                Object.keys(json.data.verdier).forEach(function(id) {
                    var el = document.getElementById(id);
                    if(!el) return;
                    el.value = json.data.verdier[id] || '';
                });
            }
        })
        .catch(function(){});
    }

    document.querySelectorAll('input[name="iCh"]')
        .forEach(function(r) { r.addEventListener('change', oppdaterFotoState); });

    oppdaterFotoState();

    // ---------------- Kandidatklikk (H1 og H2) ----------------
    var h2 = <?= $h2 ? 'true' : 'false' ?>;

    function settFelt(id, val){
        var el = document.getElementById(id);
        if(!el) return;
        el.value = val || '';
    }

    function settVis(id, val){
        var el = document.getElementById(id);
        if(!el) return;
        el.value = val || '';
    }

    function oppdaterFartoyFelter(nmmId) {
        fetch(baseUrl + '/modules/primus/api/kandidat_data.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: 'NMM_ID=' + encodeURIComponent(nmmId)
        })
        .then(function(r) { return r.ok ? r.json() : null; })
        .then(function(json) {
            if(!json || !json.ok || !json.data) return;

            var d = json.data;

            // Kandidatstyrt kontekst
            settFelt('NMM_ID', nmmId);

            // Overskriv felt i skjema (Access: SummaryFields)
            settVis('ValgtFartoy_vis', d.ValgtFartoy || '');
            settVis('FTO_vis', d.FTO || '');

            settFelt('Avbildet', d.Avbildet || '');
            settFelt('MotivType', d.MotivType || '-');
            settFelt('MotivEmne', d.MotivEmne || '-');
            settFelt('MotivKriteria', d.MotivKriteria || '-');

            // Access-paritet: bygg MotivBeskr fra kandidatfelter
            var fty = (d.FTY || '').trim();
            var fna = (d.FNA || '').trim();
            var byg = (d.BYG || '').trim();
            var ver = (d.VER || '').trim();
            var xna = (d.XNA || '').trim();

            if (fty === '' || fna === '') {
                var fto = (d.FTO || '').trim();
                settFelt('MotivBeskr', fto !== '' ? fto : '');
            } else {
                var mb = '';
                var bygInfo = 'b. ' + byg;
                if (ver !== '') {
                    bygInfo += ', ' + ver;
                }

                if (xna !== '') {
                    mb = fty + ' ' + fna + ' (ex. ' + xna + ') (' + bygInfo + ')';
                } else {
                    mb = fty + ' ' + fna + ' (' + bygInfo + ')';
                }
                settFelt('MotivBeskr', mb);
            }
        })
        .catch(function(){});
    }

    document.querySelectorAll('.kandidat-rad').forEach(function(row) {
        row.addEventListener('click', function(){
            var nmmId = this.dataset.nmmId;
            var navn = this.dataset.navn || '';
            if (!nmmId) return;

            if (h2) {
                // H2-modus: Direkte oppdatering (som før)
                oppdaterFartoyFelter(nmmId);
            } else {
                // H1-modus: Bekreftelse først
                if (confirm('Vil du endre fartøy til "' + navn + '"?\n\nDette vil overskrive nåværende fartøyinformasjon.')) {
                    oppdaterFartoyFelter(nmmId);
                }
            }
        });
    });
})();
</script>

<script>
/* ---------------------------------------------
   Lagre og gå til fartøyvalg (for nye rader)
--------------------------------------------- */
function lagreOgGaaTilFartoyvalg() {
    // Bekreft at brukeren vil lagre
    if (!confirm('Fotoet må lagres før du kan legge til flere fartøy i Avbildet.\n\nLagre og fortsett til fartøyvalg?')) {
        return;
    }

    // Sett hidden field
    document.getElementById('save_and_goto_fartoyvalg').value = '1';

    // Submit skjemaet
    document.getElementById('foto-form').submit();
}
</script>

<script type="text/javascript">
/* ---------------------------------------------
   OPPDATER Bilde_Fil klient-side (visuelt)
   --------------------------------------------- */
function oppdaterBildeFil() {
    var serie = document.getElementById('NMMSerie');
    var serNr = document.getElementById('SerNr');
    var bildeFil = document.getElementById('Bilde_Fil');

    if (serie && serNr && bildeFil) {
        // SerNr må ha 3 siffer med leading zeros (f.eks. 9 -> 009)
        var serNrPadded = String(serNr.value).padStart(3, '0');
        bildeFil.value = String(serie.value) + '-' + serNrPadded;
    }
}

var serieEl = document.getElementById('NMMSerie');
if (serieEl) {
    serieEl.addEventListener('change', oppdaterBildeFil);
}

var serNrEl = document.getElementById('SerNr');
if (serNrEl) {
    serNrEl.addEventListener('input', oppdaterBildeFil);
}
</script>

<script>
/* ---------------------------------------------
   Tillegg, Motivbeskrivelse -> Motivbeskrivelse
   --------------------------------------------- */
(function() {
    var tillegg = document.getElementById('MotivBeskrTillegg');
    var motiv = document.getElementById('MotivBeskr');

    if (!tillegg || !motiv) return;

    function appendTillegg() {
        var tilleggVal = tillegg.value.trim();
        if (tilleggVal === '') return;

        var motivVal = motiv.value.trim();
        if (motivVal === '') {
            motiv.value = tilleggVal;
        } else {
            motiv.value = motivVal + ' ' + tilleggVal;
        }

        // Keep value in Tillegg field for storage
    }

    // On blur (when field loses focus)
    tillegg.addEventListener('blur', appendTillegg);

    // On Enter key
    tillegg.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            appendTillegg();
        }
    });
})();
</script>

<script>
/* ---------------------------------------------
   Legg til 'Skipsportrett' button
   --------------------------------------------- */
(function() {
    var btn = document.getElementById('btn-leggtil-skipsportrett');
    if (!btn) return;

    btn.addEventListener('click', function() {
        var motivType = document.getElementById('MotivType');
        if (!motivType) return;

        var current = motivType.value.trim();
        // Format from Access: ID;MotivType;UUID
        var toAdd = '1060;Skipsportrett;4D9A6929-3BE1-42E4-B5F4-A2782C75A054';

        // Check if 'Skipsportrett' already exists
        if (current.toLowerCase().includes('skipsportrett')) {
            return; // Already present
        }

        // Append with newline separator if there's existing content
        if (current === '' || current === '-') {
            motivType.value = toAdd;
        } else {
            motivType.value = current + '\n' + toAdd;
        }
    });
})();
</script>

<script>
/* ---------------------------------------------
   FotoTidTil arver FotoTidFra visuelt
   --------------------------------------------- */
(function() {
    var fotoTidFra = document.getElementById('FotoTidFra');
    var fotoTidTil = document.getElementById('FotoTidTil');

    if (!fotoTidFra || !fotoTidTil) return;

    function kopierTid() {
        var fraVerdi = fotoTidFra.value.trim();
        var tilVerdi = fotoTidTil.value.trim();

        // Hvis FotoTidFra har verdi OG FotoTidTil er tomt: kopier verdien
        if (fraVerdi !== '' && tilVerdi === '') {
            fotoTidTil.value = fraVerdi;
        }
    }

    // Kopier når brukeren forlater FotoTidFra-feltet
    fotoTidFra.addEventListener('blur', kopierTid);

    // Kopier også ved Enter-tastetrykk
    fotoTidFra.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            kopierTid();
        }
    });
})();
</script>

<script>
/* ---------------------------------------------
   Synkroniser ReferNeg-felt
   --------------------------------------------- */
(function() {
    var referNegTop = document.getElementById('ReferNeg_top');
    var referNegMain = document.getElementById('ReferNeg');

    if (!referNegTop || !referNegMain) return;

    // Når top-feltet endres, oppdater hovedfeltet (som har name og sendes til server)
    referNegTop.addEventListener('input', function() {
        referNegMain.value = this.value;
    });

    // Når hovedfeltet endres, oppdater top-feltet
    referNegMain.addEventListener('input', function() {
        referNegTop.value = this.value;
    });
})();
</script>

<?php
require_once __DIR__ . '/../../includes/layout_slutt.php';
