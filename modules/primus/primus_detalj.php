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
    // Simple select dropdown (visuell indikasjon via JavaScript for readonly-tilstand)
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

$samlingValg = ['', 'C2-Johnsen, Per-Erik', 'C2-Gjersøe, Georg', 'C2-'];

// Split Samling-verdi: hvis ikke en av de faste valgene, ekstrahér suffiks
$samlingVerdi = (string)($foto['Samling'] ?? '');
$samlingSuffiks = '';

if (!in_array($samlingVerdi, $samlingValg, true) && str_starts_with($samlingVerdi, 'C2-')) {
    // Custom verdi som starter med "C2-" - ekstrahér suffiks
    $samlingSuffiks = substr($samlingVerdi, 3); // Hopp over "C2-"
    $samlingVerdi = 'C2-'; // Velg "C2-" i dropdown
}

// --------------------------------------------------
// Hendelsesmodus (iCh) – session-paritet
// --------------------------------------------------
// NB: Hvis dette er en ny rad (H2-modus), tvinges hendelsesmodus til 1
if ($h2) {
    // Ny rad: iCh=1 (allerede satt på linje 195)
    $iCh = 1;
} else {
    // VIKTIG: Hvis dette er POST (bruker lagrer), bruk iCh fra POST
    // Hvis ikke POST (bruker åpner rad), beregn iCh fra Aksesjon/Fotografi
    if (is_post() && isset($_POST['iCh'])) {
        $iCh = (int)$_POST['iCh'];
        $_SESSION['primus_iCh'] = $iCh;
    } else {
        // Eksisterende rad: Beregn iCh fra faktiske feltverdier
        // (Aksesjon/Fotografi-flagg kan være utdaterte i eldre rader)
        $harFoto = (
            trim((string)($foto['Fotograf'] ?? '')) !== '' ||
            trim((string)($foto['FotoFirma'] ?? '')) !== '' ||
            trim((string)($foto['FotoTidFra'] ?? '')) !== '' ||
            trim((string)($foto['FotoTidTil'] ?? '')) !== '' ||
            trim((string)($foto['FotoSted'] ?? '')) !== ''
        );
        $harSamling = trim((string)($foto['Samling'] ?? '')) !== '';

        if ($harSamling && $harFoto) {
            $iCh = 4;  // Foto + Samling
        } elseif ($harSamling) {
            $iCh = 3;  // Samlingshendelse
        } elseif ($harFoto) {
            $iCh = 2;  // Fotohendelse
        } else {
            $iCh = 1;  // Kun hendelse
        }

        // Synkroniser session med beregnet verdi
        $_SESSION['primus_iCh'] = $iCh;
    }
}

// Validering (sikkerhet)
if ($iCh < 1 || $iCh > 4) {
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
        // Kopier foto: Kun tillatt for eksisterende (lagrede) rader MED fartøynavn
        if ($nyRad || $fotoId === null) {
            echo '<script>alert("FEIL: Fotoet må lagres før det kan kopieres.\n\nKlikk Oppdater-knappen først, og deretter kan du kopiere."); window.history.back();</script>';
            exit;
        }

        // Bruk eksisterende lagret foto
        $lagredesFotoId = $fotoId;

        // Hent det lagrede fotoet som skal kopieres
        $eksisterende = foto_hent_en($db, $lagredesFotoId);
        if (!$eksisterende) {
            echo '<script>alert("FEIL: Finner ikke foto."); window.history.back();</script>';
            exit;
        }

        // Sjekk at fotoet har et fartøynavn (NMM_ID)
        if (empty($eksisterende['NMM_ID'])) {
            echo '<script>alert("FEIL: Fotoet må ha et fartøynavn før det kan kopieres.\n\nVelg et fartøy fra kandidatlisten, klikk Oppdater, og deretter kan du kopiere."); window.history.back();</script>';
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
// POST: marker som kontrollert (oppdater Oppdatert_Tid til NOW())
// --------------------------------------------------
if (is_post() && ($_POST['action'] ?? '') === 'marker_kontrollert') {
    if (!csrf_validate()) {
        die('Ugyldig forespørsel (CSRF).');
    }

    // Kun tillatt for eksisterende (lagrede) rader
    if ($nyRad || $fotoId === null) {
        echo '<script>alert("FEIL: Kan ikke markere som kontrollert før fotoet er lagret."); window.history.back();</script>';
        exit;
    }

    // Oppdater Oppdatert_Tid til NOW()
    $stmt = $db->prepare("UPDATE nmmfoto SET Oppdatert_Tid = NOW() WHERE Foto_ID = :foto_id");
    $stmt->execute(['foto_id' => $fotoId]);

    // Redirect tilbake til hovedsiden (samme logikk som Tilbake-knappen)
    $side = primus_finn_side_for_foto($fotoId, 20, $_SESSION['primus_sort_order'] ?? 'DESC');
    $tilbakeUrl = 'primus_main.php?side=' . $side . '#foto-' . $fotoId;
    redirect($tilbakeUrl);
}

// --------------------------------------------------
// POST: lagre foto (først her skjer DB-lagring)
// --------------------------------------------------
if (is_post() && ($_POST['action'] ?? '') !== 'kopier_foto') {
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

    // Aksesjon: Sett automatisk basert på iCh (ikke bruker-redigerbart)
    // iCh 3, 4 → Aksesjon = 1, ellers 0
    $data['Aksesjon'] = in_array($iCh, [3, 4], true) ? 1 : 0;

    // Fotografi: Sett automatisk basert på iCh (ikke bruker-redigerbart)
    // iCh 2, 4 → Fotografi = 1, ellers 0
    $data['Fotografi'] = in_array($iCh, [2, 4], true) ? 1 : 0;

    // FriKopi: Sett automatisk basert på iCh (ikke bruker-redigerbart)
    // iCh 3, 4 → FriKopi = 0, ellers 1
    $data['FriKopi'] = in_array($iCh, [3, 4], true) ? 0 : 1;

    // Samling-suffiks: Kombiner "C2-" + suffiks hvis nødvendig
    if (isset($data['Samling']) && $data['Samling'] === 'C2-') {
        $suffix = isset($data['Samling_suffix']) ? trim((string)$data['Samling_suffix']) : '';
        if ($suffix !== '') {
            $data['Samling'] = 'C2-' . $suffix;
        }
    }
    // Fjern Samling_suffix fra data (skal ikke lagres som eget felt i database)
    unset($data['Samling_suffix']);

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
            $side = primus_finn_side_for_foto($nyFotoId, 20, $_SESSION['primus_sort_order'] ?? 'DESC');
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

        // Finn sidenummer og redirect til riktig side med anchor til raden
        $side = primus_finn_side_for_foto($fotoId, 20, $_SESSION['primus_sort_order'] ?? 'DESC');
        redirect('primus_main.php?side=' . $side . '#foto-' . $fotoId);
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

// Beregn Tilbake-URL (samme logikk som Oppdater-knappen)
if ($nyRad) {
    $tilbakeUrl = 'primus_main.php?avbryt_ny=1';
} else {
    $side = primus_finn_side_for_foto($fotoId, 20, $_SESSION['primus_sort_order'] ?? 'DESC');
    $tilbakeUrl = 'primus_main.php?side=' . $side . '#foto-' . $fotoId;
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
                <?php if ($fotoId): ?>
                    <input type="hidden" name="Foto_ID" value="<?= (int)$fotoId ?>">
                <?php endif; ?>
                <?php
                // Kopier-knapp: kun enabled når raden er lagret MED fartøynavn
                $nmmId = $foto['NMM_ID'] ?? null;
                $kanKopiere = !$nyRad && $fotoId !== null && !empty($nmmId);
                ?>
                <button type="submit" class="btn btn-info" <?= $kanKopiere ? '' : 'disabled title="Må lagres med fartøynavn først"' ?> onclick="return confirm('Lagre og kopiere dette fotoet?');">Kopier foto</button>
            </form>
            <form method="post" class="inline-form">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="marker_kontrollert">
                <?php if ($fotoId): ?>
                    <input type="hidden" name="Foto_ID" value="<?= (int)$fotoId ?>">
                <?php endif; ?>
                <?php
                // Kontrollert-knapp: kun enabled når raden er lagret
                $kanMarkereKontrollert = !$nyRad && $fotoId !== null;
                ?>
                <button type="submit" class="btn btn-success" <?= $kanMarkereKontrollert ? '' : 'disabled title="Må lagres først"' ?>>Kontrollert</button>
            </form>
            <a href="<?= h($tilbakeUrl) ?>" class="btn btn-secondary">Tilbake</a>
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
                                    $lbl = [1 => 'Ingen', 2 => 'Fotografi', 3 => 'Samling', 4 => 'Foto+Samling'];
                                    foreach ($lbl as $v => $t):
                                    ?>
                                        <label class="form-check">
                                            <input type="radio" name="iCh" value="<?= $v ?>" <?= $iCh === $v ? 'checked' : '' ?>>
                                            <?= $v ?> – <?= h($t) ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>

                                <?php
                                // Hendelse: Redigerbart ved iCh 1-2, readonly ved iCh 3-6
                                // JavaScript vil oppdatere readonly-status dynamisk
                                area('Hendelse', 'Hendelse', (string)($foto['Hendelse'] ?? ''), 2);

                                combo('Samling', 'Samling', $samlingValg, $samlingVerdi);
                                ?>
                                <div class="form-group" id="samling-suffix-container" style="display:none;">
                                    <label for="Samling_suffix" class="text-hint">Legg til Samlingseiers navn her!</label>
                                    <input type="text" name="Samling_suffix" id="Samling_suffix" value="<?= h($samlingSuffiks) ?>">
                                </div>
                                <?php
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

<!-- Alt håndteres av primus_detalj.js (initTabs, initIChState, initKandidatSok, initKandidatRadKlikk) -->

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
   Samling-suffiks: Vis/skjul basert på dropdown-valg
   --------------------------------------------- */
(function() {
    var samlingSelect = document.getElementById('Samling');
    var samlingSuffixContainer = document.getElementById('samling-suffix-container');

    if (!samlingSelect || !samlingSuffixContainer) return;

    function toggleSamlingSuffix() {
        if (samlingSelect.value === 'C2-') {
            samlingSuffixContainer.style.display = 'block';
        } else {
            samlingSuffixContainer.style.display = 'none';
        }
    }

    // Lytter på endringer i dropdown
    samlingSelect.addEventListener('change', toggleSamlingSuffix);

    // Kjør ved page load (for å vise suffix hvis "C2-" er valgt)
    toggleSamlingSuffix();
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

<!-- Load and initialize primus_detalj.js -->
<script src="<?= BASE_URL; ?>/assets/primus_detalj.js?v=<?= filemtime(__DIR__ . '/../../assets/primus_detalj.js'); ?>"></script>
<script>
(function() {
    if (typeof window.initPrimusDetalj === 'function') {
        window.initPrimusDetalj({
            baseUrl: '<?= BASE_URL; ?>',
            fotoId: <?= $fotoId ? $fotoId : 'null'; ?>,
            nyRad: <?= $nyRad ? 'true' : 'false'; ?>,
            h2: <?= $h2 ? 'true' : 'false'; ?>
        });
    }
})();
</script>

<?php
require_once __DIR__ . '/../../includes/layout_slutt.php';
