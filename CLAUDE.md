# CLAUDE.md – NMMPrimus Referanse

**Omfattende veiledning** for Claude-agenter som jobber med NMMPrimus.

**VIKTIG:** [AGENTS.md](AGENTS.md) har absolutt forrang ved konflikt.

---

## 1. Prosjektoversikt

**NMMPrimus** er PHP/MySQL migrering av Access-basert fotoarkiv for maritime museumsbilder.

**Stack:**
```
Backend:     PHP 8.1+ (strict_types)
Database:    MySQL 8.0+ (PDO)
Frontend:    HTML5, minimal CSS, vanilla JS
Miljø:       XAMPP (dev), web hosting (prod)
```

**Prinsipp:** Funksjonalitet og korrekthet > visuell modernisering

---

## 2. Filstruktur (Kritiske filer)

```
nmmprimus/
├── config/                     # IKKE ENDRE
│   ├── config.php              # DB lokal
│   ├── configProd.php          # DB prod
│   ├── constants.php           # BASE_URL lokal
│   └── constantsProd.php       # BASE_URL prod
│
├── includes/                   # Delt infrastruktur
│   ├── auth.php                # Auth + "Remember me"
│   ├── db.php                  # PDO singleton: db()
│   ├── functions.php           # h(), csrf, redirect, etc.
│   ├── foto_flyt.php           # iCh feltlogikk
│   ├── layout_start.php        # HTML header
│   ├── layout_slutt.php        # HTML footer
│   ├── ui.php                  # UI-komponenter
│   └── user_functions.php      # User CRUD
│
├── modules/
│   ├── primus/                 # Hovedmodul
│   │   ├── primus_main.php         # Landingsside
│   │   ├── primus_detalj.php       # Detaljvisning
│   │   ├── primus_modell.php       # Datamodell
│   │   ├── export_excel.php        # CSV-eksport (admin)
│   │   ├── export_confirm.php      # Bekreftelse
│   │   └── api/
│   │       ├── sett_session.php
│   │       ├── toggle_transferred.php
│   │       ├── kandidat_data.php
│   │       └── neste_sernr.php
│   │
│   ├── foto/                   # Foto CRUD
│   │   ├── foto_modell.php
│   │   └── api/foto_state.php
│   │
│   ├── fartoy/                 # Fartøyvalg
│   │   └── fartoy_velg.php
│   │
│   └── admin/                  # User admin
│       └── bruker_admin.php
│
├── assets/app.css              # Eneste stylesheet
├── doc/                        # Dokumentasjon
├── login.php
├── logout.php
├── index.php
├── AGENTS.md                   # OPERATIVE CONTRACT
└── CLAUDE.md                   # Dette dokumentet
```

---

## 3. Kjernearkitektur

### Database Access: PDO Singleton

```php
$db = db();
$stmt = $db->prepare("SELECT * FROM table WHERE id = :id");
$stmt->execute(['id' => $id]);
$row = $stmt->fetch();
```

**Krav:**
- Prepared statements med navngitte parametere
- FETCH_ASSOC default
- Ingen rå SQL

### Include-rekkefølge (KRITISK)

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/primus_modell.php';

require_login();  // eller require_admin()

// Prosessering

$pageTitle = 'Tittel';
require_once __DIR__ . '/../../includes/layout_start.php';

// Innhold

require_once __DIR__ . '/../../includes/layout_slutt.php';
```

### Sikkerhet

```php
// Output escaping
echo h($userInput);

// CSRF
<?= csrf_field(); ?>
if (is_post() && !csrf_validate()) die('Ugyldig');

// Auth
require_login();   // Sjekk innlogget
require_admin();   // Sjekk admin-rolle
```

---

## 4. Nøkkelkonsepter

### H1 vs H2 Modes

| Modus | Bruk | Kandidatpanel | Session |
|-------|------|---------------|---------|
| H1 | Rediger eksisterende | Skjult | `primus_h2 = 0` |
| H2 | Opprett nytt | Synlig | `primus_h2 = 1` |

### Hendelsesmodus (iCh 1-6)

| iCh | Beskrivelse | Foto-felt | Samling-felt |
|-----|-------------|-----------|--------------|
| 1 | Kun hendelse | ❌ | ❌ |
| 2 | Fotohendelse | ✅ | ❌ |
| 3 | Samlingshendelse | ❌ | ✅ |
| 4 | Foto + Samling | ✅ | ✅ |
| 5 | (reservert) | ❌ | ❌ |
| 6 | Fullstendig | ✅ | ✅ |

Lagret: `$_SESSION['primus_iCh']`

Felthvitlisting i `foto_lagre()` forhindrer uautorisert redigering.

---

## 5. Database-skjema (Viktigste tabeller)

### nmmfoto (Fotoobjekter)
- `Foto_ID` (PK)
- `NMM_ID` (FK til nmm_skip)
- `SerNr` (1-999)
- `Bilde_Fil` (format: "XXXXXXXX-NNN")
- `URL_Bane` (auto-generert)
- **Motiv:** MotivBeskr, MotivType, MotivEmne, MotivKriteria
- **Foto:** Fotografi, Fotograf, FotoFirma, FotoTidFra/Til, FotoSted
- **Samling:** Aksesjon, Samling
- **Flagg:** Svarthvitt, FriKopi, Transferred

### nmm_skip (Fartøyregister)
- `NMM_ID` (PK)
- FTY, FNA, XNA, VID, VER, BNR
- BYG, RGH, NAT

### Brukertabeller
- `user` – brukere (email, password, role)
- `user_preferences` – siste serie
- `user_remember_tokens` – "Remember me"

---

## 6. Viktige Funksjoner

### Primus-modul

```php
// Serie
primus_hent_bildeserier()
primus_hent_forste_serie()
primus_lagre_sist_valgte_serie($userId, $serie)

// Foto
primus_hent_foto_for_serie($serie, $offset, $limit)
primus_hent_totalt_antall_foto($serie)

// Kandidater
primus_hent_skip_liste($sokeTekst)
primus_hent_kandidat_felter($nmmId)

// Export (admin)
primus_hent_foto_for_export($serie, $minSerNr, $maxSerNr)
primus_marker_som_transferred($fotoIds)
primus_toggle_transferred($fotoId)
```

### Foto-modul

```php
foto_hent_en($db, $fotoId)
foto_lagre($db, $data)           // Med iCh-hvitlisting
foto_kopier($db, $fotoId)         // Nullstill historikk
foto_opprett_ny($db, $bildeFil)
```

---

## 7. Access-til-web Mapping

| Access | Web | Fil |
|--------|-----|-----|
| frmNMMPrimusMain | Landingsside | primus_main.php |
| frmNMMPrimus | Detaljvisning | primus_detalj.php |
| frmNMMPrimusKand | Kandidatpanel | Venstre sidebar i detalj |
| frmNMMSkipsValg | Fartøyvalg | fartoy_velg.php |
| H1/H2 modus | Session-flagg | `$_SESSION['primus_h2']` |
| iCh (1-6) | Hendelsesmodus | `$_SESSION['primus_iCh']` |
| SummaryFields() | primus_hent_kandidat_felter() | primus_modell.php |
| UpdateURLFields() | Auto i foto_lagre() | foto_modell.php |

---

## 8. Vanlige oppgaver

### Ny modul

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/din_modell.php';

require_login();

// Prosessering

$pageTitle = 'Tittel';
require_once __DIR__ . '/../../includes/layout_start.php';
?>

<!-- HTML -->

<?php require_once __DIR__ . '/../../includes/layout_slutt.php'; ?>
```

### Database-funksjon

```php
function din_funksjon(string $param): array
{
    $db = db();
    $stmt = $db->prepare("SELECT * FROM table WHERE field = :param");
    $stmt->execute(['param' => $param]);
    return $stmt->fetchAll();
}
```

### API-endepunkt

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/auth.php';
header('Content-Type: application/json; charset=utf-8');

require_login();

if (!is_post()) {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Kun POST']);
    exit;
}

$result = din_funksjon();
echo json_encode(['success' => true, 'data' => $result], JSON_UNESCAPED_UNICODE);
```

---

## 9. Sjekkliste for ny kode

- [ ] `declare(strict_types=1);` først
- [ ] Prepared statements
- [ ] Output escaped med `h()`
- [ ] CSRF på POST
- [ ] Include-rekkefølge riktig
- [ ] `layout_start.php` før output
- [ ] `layout_slutt.php` på slutten
- [ ] Ingen duplisering av eksisterende funksjoner
- [ ] `require_login()` eller `require_admin()`
- [ ] Bruk BASE_URL konstant
- [ ] Norske, beskrivende navn
- [ ] Dokumentert med kommentar

---

## 10. Kjente problemer

### Code Quality
1. Duplisert `foto_hent_en()` i foto_modell.php og primus_modell.php
2. Ubrukt `primus_oppdater_foto()` i primus_modell.php
3. Unødvendige `function_exists()` wrapper med `require_once`
4. Noe inline CSS i primus_main.php (bør flyttes til app.css)

### Mangler
1. NotInList-håndterer (Access-funksjon)
2. Kopier foto-knapp i UI (funksjon finnes)

---

## 11. Feilsøking

### Vanlige problemer

| Feil | Årsak | Løsning |
|------|-------|---------|
| "Could not connect" | MySQL ikke startet | Start XAMPP MySQL |
| Hvit side / 500 | Syntaksfeil | Sjekk apache/logs/error.log |
| CSS lastes ikke | Feil BASE_URL | Sjekk constants.php |
| Sesjon tapt | Cookie-problem | Sjekk remember tokens |

### Nyttige SQL-queries

```sql
-- Sjekk brukere
SELECT user_id, email, role, IsActive FROM user;

-- Foto for serie
SELECT Foto_ID, Bilde_Fil, MotivBeskr
FROM nmmfoto
WHERE LEFT(Bilde_Fil, 8) = 'XXXXXXXX'
ORDER BY Bilde_Fil DESC;

-- Remember tokens
SELECT user_id, expires_at
FROM user_remember_tokens
WHERE expires_at > NOW();
```

---

## 12. Når du står fast

1. Sjekk [AGENTS.md](AGENTS.md)
2. Sjekk [doc/Primus_PI.md](doc/Primus_PI.md)
3. Sjekk kodkommentarer
4. **Stopp og spør** – aldri gjett

**Kontakt:** webman@skipsweb.no

---

## 13. Viktige dokumenter

| Fil | Formål |
|-----|--------|
| [AGENTS.md](AGENTS.md) | Operative contract (HØYESTE AUTORITET) |
| [CLAUDE.md](CLAUDE.md) | Dette dokumentet |
| [doc/Primus_PI.md](doc/Primus_PI.md) | Hva systemet skal gjøre |
| [doc/Primus_Schema.md](doc/Primus_Schema.md) | Database-skjema |
| [doc/Primus_Filstruktur.md](doc/Primus_Filstruktur.md) | Filstruktur |
| [doc/CODE_REVIEW_2.md](doc/CODE_REVIEW_2.md) | Kjente problemer |

---

**Versjon:** 1.0
**Opprettet:** 2025-12-24
**Forfatter:** Claude Code
