# CLAUDE.md – NMMPrimus Referanse

**Teknisk referansedokument** for Claude-agenter som jobber med NMMPrimus.

**VIKTIG:** [AGENTS.md](AGENTS.md) har absolutt forrang ved konflikt.

---

## 1. Prosjektoversikt

**NMMPrimus** er en PHP/MySQL-basert webapplikasjon for forvaltning av maritim fotoarkiv. Systemet erstatter en Microsoft Access-løsning.

**Formål:** Bygge tabellen `nmmfoto` ved å koble fartøydata fra `nmm_skip`, parametertabeller og manuell input.

**Stack:**
```
Backend:     PHP 8.1+ (strict_types=1)
Database:    MySQL 	8.0.44-cll-lve (PDO)
Frontend:    HTML5, minimal CSS, vanilla JS
Miljø:       XAMPP (dev), web hosting (prod)
```

**Prinsipp:** Funksjonalitet og korrekthet > visuell modernisering

**Scope:**
- Prosjektet er **kun** `nmmprimus`
- **Ingen** kode/mønstre fra andre repoer
- Autoritative kilder: Dokumenter i `nmmprimus`, eksisterende kode

---

## 2. Filstruktur

```
nmmprimus/
├── config/                     # IKKE ENDRE
│   ├── config.php              # DB lokal
│   ├── configProd.php          # DB prod
│   ├── constants.php           # BASE_URL, FOTO_URL_PREFIX
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
│   │   ├── export_motiv.php        # CSV-eksport (admin)
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
├── manual/                     # Håndbok, manual
├── doc/                        # Dokumentasjon
├── login.php
├── logout.php
├── index.php
├── AGENTS.md                   # OPERATIVE CONTRACT
└── CLAUDE.md                   # Dette dokumentet
```

Se [doc/Primus_Filstruktur.md](doc/Primus_Filstruktur.md) for fullstendig oversikt.

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

### Konstanter

```php
// config/constants.php
define('BASE_URL', '/nmmprimus');              // URL til applikasjonen
define('FOTO_URL_PREFIX', 'M:\NMM\Bibliotek\Foto\NSM.TUSEN-SERIE\\');  // cURL fra Access
```

**FOTO_URL_PREFIX:**
- Mapper til Access VBA-konstanten `cURL`
- Brukes ved generering av `URL_Bane`-feltet
- Format: `FOTO_URL_PREFIX . $serie . ' -001-999 Damp og Motor'`
- Eksempel: `M:\NMM\Bibliotek\Foto\NSM.TUSEN-SERIE\NSM.2001 -001-999 Damp og Motor`

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
## CSS-retningslinjer

- **Unngå `!important`**: Med mindre helt nødvendig
- **Desktop-first**: 1920x1200 primær, andre PC-størrelser støttes
- **Mobildesign**: IKKE prioritert

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

**Access-mapping:**
- H1: Dobbeltaklikk på rad i landingssiden → rediger eksisterende foto
- H2: Klikk "Ny"-knapp i landingssiden → opprett nytt foto i serien

### Hendelsesmodus (iCh 1-6)

| iCh | Beskrivelse | Foto-felt | Samling-felt |
|-----|-------------|-----------|--------------|
| 1 | Kun hendelse | ❌ | ❌ |
| 2 | Fotohendelse | ✅ | ❌ |
| 3 | Samlingshendelse | ❌ | ✅ |
| 4 | Foto + Samling | ✅ | ✅ |
| 5 | (reservert) | ❌ | ❌ |
| 6 | Fullstendig | ✅ | ✅ |

**Lagret:** `$_SESSION['primus_iCh']`

**Felthvitlisting:** `foto_lagre()` validerer hvilke felt som kan redigeres basert på iCh-modus.

---

## 5. Database-skjema

Se [doc/Primus_Schema.md](doc/Primus_Schema.md) for komplett SQL-skjema.

### nmmfoto (Fotoobjekter - hovedtabell)
- `Foto_ID` (PK, auto_increment)
- `NMM_ID` (FK til nmm_skip)
- `SerNr` (smallint, 1-999)
- `Bilde_Fil` (varchar(255), format: "XXXXXXXX-NNN")
- `URL_Bane` (varchar(255), auto-generert)
- **Motiv:** MotivBeskr, MotivBeskrTillegg, MotivType, MotivEmne, MotivKriteria, Avbildet, Hendelse
- **Foto:** Fotografi (bit), Fotograf, FotoFirma, FotoTidFra, FotoTidTil, FotoSted
- **Samling:** Aksesjon (bit), Samling
- **Teknisk:** Prosess, ReferNeg, ReferFArk, Plassering, Svarthvitt, Status, Tilstand
- **Flagg:** FriKopi (bit), Transferred (bit), Flag (bit)
- **System:** UUID, Merknad

### nmm_skip (Fartøyregister)
- `NMM_ID` (PK)
- `FTY` (fartøytype)
- `FNA` (fartøynavn)
- `XNA` (tidligere navn)
- `VID` (verft ID)
- `VER` (verft navn)
- `BNR` (byggenummer)
- `BYG` (byggeår)
- `RGH` (registerhavendehavn)
- `NAT` (nasjonalitet)
- `NID` (nasjons-ID, FK til country)

### Relasjonstabeller (x-tabeller)
- `nmmxemne` – Motivemner (NMM_ID → nmm_skip)
- `nmmxtype` – Motivtyper (NMM_ID → nmm_skip)
- `nmmxou` – OU-klassifikasjoner (NMM_ID → nmm_skip)
- `nmmxudk` – UDK-klassifikasjoner (NMM_ID → nmm_skip)
- `nmmxhendelse` – Hendelser (Foto_ID → nmmfoto)

### Parametertabeller
- `bildeserie` – Bildeserier (SerID, Serie)
- `country` – Nasjoner (Nasjon_ID, Nasjon)
- `farttype` – Fartøytyper (FTY, FartType)
- `_zhendelsestyper` – Hendelsestyper (Kode, Hendelsestype)

### Brukertabeller
- `user` – Brukere (user_id, email, password, role, IsActive)
- `user_preferences` – Siste serie (user_id, last_serie)
- `user_remember_tokens` – "Remember me"-tokens (token_id, user_id, selector, validator_hash, expires_at)

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

### Skjemaer

| Access | Web | Fil |
|--------|-----|-----|
| frmNMMPrimusMain | Landingsside | primus_main.php |
| frmNMMPrimus | Detaljvisning | primus_detalj.php |
| frmNMMPrimusKand | Kandidatpanel | Venstre sidebar i detalj |
| frmNMMSkipsValg | Fartøyvalg | fartoy_velg.php |

### Logikk

| Access | Web | Fil |
|--------|-----|-----|
| H1/H2 modus | Session-flagg | `$_SESSION['primus_h2']` |
| iCh (1-6) | Hendelsesmodus | `$_SESSION['primus_iCh']` |
| SummaryFields() | primus_hent_kandidat_felter() | primus_modell.php |
| UpdateURLFields() | Auto i foto_lagre() | foto_modell.php |

### Atferd

| Access | Web |
|--------|-----|
| Standardverdier ved ny post | Session + database defaults |
| Husk siste valg | user_preferences-tabell |
| Umiddelbar respons | AJAX + session |
| NotInList | (ikke implementert) |

---

## 8. Funksjonsområder

### Landingsside (primus_main.php)

**Funksjoner:**
- Velg bildeserie (combobox, husker siste valg)
- Liste over foto i serien (20 per side)
- Paging (forrige/neste)
- Dobbeltaklikk → H1-modus (rediger)
- Ny-knapp → H2-modus (opprett)
- Slett foto
- **Admin:** Toggle Transferred, eksport til Excel

**Access-ekvivalent:** frmNMMPrimusMain

### Detaljvisning (primus_detalj.php)

**Faner:**
- **Motiv** – MotivBeskr, MotivType, MotivEmne, etc.
- **Bildehistorikk** – Fotograf, FotoFirma, FotoTid, FotoSted, Aksesjon, Samling
- **Øvrige** – Prosess, Referanser, Plassering, Status

**Venstre panel (kun H2-modus):**
- Søk etter fartøy (min 3 tegn)
- Velg fartøy → fyller felter automatisk

**Hendelsesmodus (iCh):**
- Styrer hvilke felt som er redigerbare
- Visuell markering (grønn/rød ramme)
- Huskes i session

**Funksjoner:**
- Lagre endringer (Oppdater-knapp)
- Kopier foto (samme serie, nytt SerNr)
- Endre SerNr (validering mot ledige numre)
- Auto-generering av Bilde_Fil og URL_Bane

**Access-ekvivalent:** frmNMMPrimus + frmNMMPrimusKand subform

### Fartøyvalg (fartoy_velg.php)

**Funksjoner:**
- Søk på fartøynavn (FNA)
- Liste med 25 rader (scrollbar)
- Velg fartøy → returner til detalj

**Access-ekvivalent:** frmNMMSkipsValg

### Admin (bruker_admin.php)

**Funksjoner:**
- Opprett brukere (admin/user)
- Rediger brukere (e-post, rolle)
- Endre passord
- Aktivere/deaktivere brukere
- Slette brukere

**Kun admin-rolle.**

---

## 9. Vanlige oppgaver

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

## 10. Sjekkliste for ny kode

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
- [ ] **Angi alltid hvilke filer som må overføres til produksjon etter endringer**

---

## 11. Deployment til produksjon

**VIKTIG:** Etter enhver kodeendring skal du alltid angi hvilke filer som må overføres til produksjon.

### Format for deployment-liste:

```markdown
## 📦 Filer å overføre til produksjon:

1. ✅ `modules/primus/primus_main.php`
2. ✅ `modules/primus/primus_modell.php`
3. ✅ `modules/primus/primus_detalj.php`

**Nye filer:**
- ✅ `modules/primus/api/ny_fil.php`

**Filer å slette:**
- 🗑️ `modules/primus/gammel_fil.php`
```

### Retningslinjer:

1. **Liste alle endrede filer** - Bruk relative paths fra prosjektrot
2. **Marker nye filer** - Angi tydelig hvilke filer som er nye
3. **Angi filer som skal slettes** - Hvis noen filer skal fjernes i produksjon
4. **Kort forklaring** - Hvis nødvendig, forklar hva hver fil gjør
5. **Alltid på slutten** - Legg deployment-listen på slutten av svaret/oppsummeringen

### Eksempel fra praksis:

```markdown
## ✅ Ferdig! Tre read-only kolonner lagt til

[... beskrivelse av endringer ...]

---

## 📦 Filer å overføre til produksjon:

1. ✅ `modules/primus/primus_modell.php` (henter nye felt)
2. ✅ `modules/primus/primus_main.php` (viser nye kolonner)

**Test etter deployment:** Refresh siden og verifiser at nye kolonner vises.
```

---

## 12. Kjente problemer

**Se [ROADMAP.md](ROADMAP.md) for fullstendig liste og implementeringsplan.**

### Code Quality
1. ~~Duplisert `foto_hent_en()`~~ ✅ Aldri duplisert - kun i foto_modell.php
2. ~~Ubrukt `primus_oppdater_foto()`~~ ✅ Fjernet (Task 8)
3. Unødvendige `function_exists()` wrapper med `require_once`
4. Noe inline CSS i primus_main.php (bør flyttes til app.css)

### Sikkerhet
1. 🔴 **KRITISK:** Produksjons-credentials eksponert i configProd.php
2. 🔴 **KRITISK:** CSRF-sårbarhet på GET-operasjoner (primus_detalj.php)
3. 🔴 **KRITISK:** Hardkodede admin-credentials i opprett_bruker.php
4. 🟠 Svake passordkrav (6 tegn minimum)
5. 🟠 Ingen environment detection i db.php

### Mangler
1. NotInList-håndterer (Access-funksjon)
2. Automatiserte tester
3. Error logging system
4. API-autentisering standardisering

---

## 13. Feilsøking

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

## 14. Når du står fast

1. Sjekk [AGENTS.md](AGENTS.md)
2. Sjekk [doc/Primus_Funksjonalitet.md](doc/Primus_Funksjonalitet.md)
3. Sjekk kodkommentarer
4. **Stopp og spør** – aldri gjett

**Kontakt:** webman@skipsweb.no

---

## 15. Viktige dokumenter

| Fil | Formål |
|-----|--------|
| [AGENTS.md](AGENTS.md) | Operativt kontrakt (HØYESTE AUTORITET) |
| [CLAUDE.md](CLAUDE.md) | Dette dokumentet (teknisk referanse) |
| [ROADMAP.md](ROADMAP.md) | Planlagte forbedringer og teknisk gjeld |
| [README.md](README.md) | Prosjektoversikt |
| [doc/Primus_Funksjonalitet.md](doc/Primus_Funksjonalitet.md) | Funksjonell beskrivelse |
| [doc/Primus_Schema.md](doc/Primus_Schema.md) | Database-skjema (SQL) |
| [doc/Primus_Filstruktur.md](doc/Primus_Filstruktur.md) | Filstruktur (detaljert) |
| [doc/SETUP_GUIDE.md](doc/SETUP_GUIDE.md) | Installasjons- og oppsettguide |

---

**Versjon:** 2.0
**Sist oppdatert:** 2026-01-03
**Forfatter:** Claude Code
