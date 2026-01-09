# FIL STRUKTUR OG LISTE

Overordnet mappe: Git-repo for Primusdatabasen.

**Sist oppdatert:** 2026-01-03

Midlertidige filer vises ikke.

## Rotstruktur

```
nmmprimus/
 ├─ config/                    # Konfigurasjonsfiler
 │   ├─ config.php             # Database-konfigurasjon (git ignored)
 │   ├─ config.example.php     # Database-konfigurasjon mal
 │   ├─ constants.php          # Konstanter (BASE_URL, etc.) (git ignored)
 │   ├─ constants.example.php  # Konstanter mal
 │   ├─ configProd.example.php      # ⚠️ DEPRECATED (bruk config.example.php)
 │   ├─ constantsProd.example.php   # ⚠️ DEPRECATED (bruk constants.example.php)
 │   └─ README.md              # Oppsettsinstruksjoner
 │
 ├─ includes/                  # Delte hjelpefunksjoner og layout
 │   ├─ auth.php               # Autentisering og sesjonshåndtering
 │   ├─ db.php                 # Database-tilkobling (PDO singleton)
 │   ├─ error_handler.php      # Feilhåndtering og logging
 │   ├─ foto_flyt.php          # Foto-flytkontroll (iCh feltlogikk)
 │   ├─ functions.php          # Generelle hjelpefunksjoner
 │   ├─ layout_slutt.php       # HTML footer og avslutning
 │   ├─ layout_start.php       # HTML header og navigasjon
 │   ├─ ui.php                 # UI-komponenter (card, table, etc.)
 │   └─ user_functions.php     # Brukeradministrasjon (CRUD)
 │
 ├─ assets/                    # CSS, JavaScript, bilder
 │   ├─ app.css                # Hovedstil for applikasjonen
 │   ├─ primus_detalj.js       # JavaScript for primus_detalj.php
 │   ├─ primus_main.js         # JavaScript for primus_main.php
 │   ├─ bruker_admin.js        # JavaScript for bruker_admin.php
 │   └─ index.js               # JavaScript for index.php (auto-redirect)
 │
 ├─ modules/                   # Moduler (funksjonsområder)
 │   │
 │   ├─ admin/                 # Administratormodul
 │   │   └─ bruker_admin.php   # Brukeradministrasjon (CRUD GUI)
 │   │
 │   ├─ fartoy/                # Fartøymodul
 │   │   └─ fartoy_velg.php    # Velg fartøy for kobling til foto
 │   │
 │   ├─ foto/                  # Fotomodul
 │   │   ├─ foto_modell.php    # Datamodell for foto (CRUD)
 │   │   └─ api/               # API-endepunkter
 │   │       └─ foto_state.php # Hendelsesmodus (iCh) felt-enable/disable
 │   │
 │   └─ primus/                # Primus hovedmodul
 │       ├─ primus_main.php         # Landingsside (liste over foto)
 │       ├─ primus_detalj.php       # Detaljvisning og redigering av foto
 │       ├─ primus_modell.php       # Datamodell for Primus (CRUD, kandidater, eksport)
 │       ├─ export_excel.php        # Excel-eksport (admin only)
 │       ├─ export_confirm.php      # Bekreftelsesside etter eksport
 │       └─ api/                    # API-endepunkter
 │           ├─ kandidat_data.php   # Hent kandidatdata (skip-info)
 │           ├─ neste_sernr.php     # Hent neste serienummer
 │           ├─ sett_session.php    # Sett session-variabler
 │           └─ toggle_transferred.php  # Toggle Transferred-status (admin AJAX)
 │
 ├─ setup/                     # Oppsettverktøy (CLI-only)
 │   ├─ opprett_bruker.php     # CLI-verktøy for brukeropp (kun kommandolinje)
 │   └─ README.md              # Setup-instruksjoner
 │
 ├─ logs/                      # Logger (auto-generert, git ignored)
 │   ├─ error.log              # Feillogger (auto-roteres ved 10MB)
 │   └─ README.md              # Logging-dokumentasjon
 │
 ├─ doc/                       # Dokumentasjon
 │   ├─ AccessObjects.pdf      # Access-databaseeksport (struktur)
 │   ├─ frmNMMPrimus.pdf       # Access-form VBA-kode
 │   ├─ Primus_Filstruktur.md  # Denne filen
 │   ├─ Primus_Funksjonalitet.md  # Funksjonell beskrivelse
 │   ├─ Primus_Schema.md       # Database-skjema (SQL)
 │   ├─ SETUP_GUIDE.md         # Installasjons- og oppsettguide
 │   └─ DOCUMENTATION_CHANGELOG.md  # Dokumentasjonsendringer
 │
 ├─ zzz/                       # Arkiv/deprecated filer
 │   ├─ kandidater.php.deprecated      # ⚠️ Flyttet fra modules/foto/api/
 │   ├─ velg_kandidat.php.deprecated   # ⚠️ Flyttet fra modules/foto/api/
 │   ├─ foto_arbeidsflate.php  # ⚠️ Gammel arbeidsflate
 │   ├─ ui_demo.php            # ⚠️ UI-komponent demo
 │   ├─ AGENTSGen.md           # ⚠️ Gammel agentdokumentasjon
 │   ├─ CODE_REVIEW.md         # ⚠️ Gammel kodereview
 │   ├─ Primus_RD_GPT.md       # ⚠️ Gammel requirements
 │   ├─ UM_NMMPrimus.md        # ⚠️ Brukermanual (utdatert)
 │   └─ ToDo.md                # ⚠️ Gammel TODO-liste
 │
 ├─ .claude/                   # Claude Code konfigurasjon
 │   └─ settings.local.json    # Lokale Claude-innstillinger
 │
 ├─ .git/                      # Git versjonskontroll
 ├─ .gitignore                 # Git ignore-filer
 │
 ├─ index.php                  # Forside (admin-meny / redirect)
 ├─ login.php                  # Innloggingsside
 ├─ logout.php                 # Utlogging
 │
 ├─ AGENTS.md                  # Operativt kontrakt for Claude-agenter
 ├─ CLAUDE.md                  # Teknisk referansedokument
 ├─ README.md                  # Prosjektoversikt
 ├─ ROADMAP.md                 # Planlagte forbedringer og teknisk gjeld
 ├─ SECURITY_FIXES.md          # Sikkerhetsforbedringer (Tasks 1-4)
 ├─ IMPROVEMENTS_6_7.md        # Passord og error logging (Tasks 6-7)
 ├─ IMPROVEMENTS_11_16.md      # Frontend forbedringer (Tasks 11-16)
 └─ IMPROVEMENTS_17_24.md      # Teknisk gjeld status (Tasks 17-24)
```

## Nøkkelfunksjoner per modul

### Admin-modul (`modules/admin/`)
- **bruker_admin.php**: Komplett brukeradministrasjon
  - Opprett nye brukere (admin/bruker)
  - Rediger eksisterende brukere (e-post, rolle)
  - Endre passord (min. 8 tegn, kompleksitet)
  - Aktivere/deaktivere brukere
  - Slette brukere (med sikkerhet)
  - JavaScript: `assets/bruker_admin.js` (modal-funksjoner)

### Primus-modul (`modules/primus/`)
- **primus_main.php**: Landingsside
  - Velg bildeserie fra dropdown
  - Liste over foto (20 per side, paging)
  - Opprett nytt foto
  - Dobbeltklikk for redigering (H1-modus)
  - Slett foto
  - **Admin-funksjoner:**
    - Toggle Transferred-status (checkbox i liste, AJAX)
    - Eksporter til Excel (modal dialog med SerNr-valg)
  - JavaScript: `assets/primus_main.js`

- **primus_detalj.php**: Detaljvisning
  - 3 faner: Motiv, Bildehistorikk, Øvrige
  - Kandidatpanel (venstre) for fartøyvalg (kun H2-modus)
  - Hendelsesmodus (iCh 1-6) med felt-enable/disable
  - "Legg til i Avbildet" via fartøy-søk
  - "Kopier foto"-funksjon
  - Auto-generering av URL_Bane
  - JavaScript: `assets/primus_detalj.js`

- **export_excel.php**: Excel-eksport (CSV-format)
  - Kun for admin
  - Eksporterer foto med Transferred = False
  - Filtrering på Serie og SerNr-område
  - Maks 1000 poster per eksport
  - 23 felter per rad (BildeId, URL_Bane, MotivBeskr, ...)
  - Filnavn: ExportToPrimus_YYYYMMDD_HHMMSS.csv

- **export_confirm.php**: Bekreftelsesside
  - Viser eksportinformasjon (Serie, SerNr-område, antall)
  - Bekreft → marker alle eksporterte foto som Transferred = True
  - Avbryt → ingen endringer

### Foto-modul (`modules/foto/`)
- **foto_modell.php**: Datamodell
  - `foto_hent_en()`: Hent ett foto
  - `foto_lagre()`: Lagre/oppdater foto (med iCh-sanitering)
  - `foto_kopier()`: Kopier foto (nullstill Bildehistorikk/Øvrige)
  - `foto_opprett_ny()`: Opprett nytt foto

### Fartøy-modul (`modules/fartoy/`)
- **fartoy_velg.php**: Søk og velg fartøy
  - Søk etter fartøynavn (FNA)
  - Liste med 25 rader (scrollbar)
  - Velg fartøy → koble til foto (POST med CSRF)

## Sikkerhetsimplementeringer

### ✅ Fullført (Tasks 1-7, 11-16)

#### Task 1-4: Kritiske sikkerhetsforbedringer
- ✅ Utvidet `.gitignore` (config.php, logs/, .env)
- ✅ Fjernet hardkodede credentials
- ✅ Opprettet `.example.php` filer for config
- ✅ CSRF-beskyttelse på alle state-endringer (POST)
- ✅ `opprett_bruker.php` flyttet til `setup/` (CLI-only)

#### Task 5: Miljø-deteksjon
- ✅ Forenklet til å alltid bruke `config.php` og `constants.php`
- ✅ Miljøspesifikke verdier settes i selve config-filene

#### Task 6: Passordkrav
- ✅ Min. 8 tegn (opp fra 6)
- ✅ Kompleksitetskrav (store/små bokstaver, tall, spesialtegn)
- ✅ `validate_password_strength()` funksjon

#### Task 7: Error logging
- ✅ `includes/error_handler.php` opprettet
- ✅ Miljø-avhengig `display_errors` (dev: ON, prod: OFF)
- ✅ Auto-roterende logfiler (10MB limit)
- ✅ Custom error/exception/shutdown handlers

#### Task 8-10: Kodeopprydding
- ✅ Fjernet `primus_oppdater_foto()` (død kode)
- ✅ Flyttet ubrukte API-filer til `zzz/`
- ✅ Verifisert `foto_hent_en()` ikke duplisert
- ✅ Fjernet 12 `function_exists()` wrappere

#### Task 11: Inline CSS → app.css
- ✅ 290+ linjer utility-klasser lagt til
- ✅ ~40 inline `style=""` fjernet fra 6 filer

#### Task 12: Inline JavaScript → dedikerte filer
- ✅ 4 JavaScript-filer opprettet (~300 linjer)
- ✅ Separasjon av bekymringer (HTML vs JS)

#### Task 13-14: API sikkerhet
- ✅ Alle API-endepunkter har `require_login()` eller `require_admin()`
- ✅ Input-validering med prepared statements og `FILTER_VALIDATE_INT`

#### Task 16: Hardkodede BASE_URL
- ✅ Fjernet siste hardkodede path i `fartoy_velg.php`
- ✅ Konsistent bruk av `BASE_URL` konstant

### ⚠️ Delvis (Task 15)
- ⚠️ Loading-indikatorer ikke implementert
- ⚠️ Bruker fortsatt `alert()` for feilmeldinger

### 🔴 Gjenstående teknisk gjeld (Tasks 17-24)
Se [IMPROVEMENTS_17_24.md](../IMPROVEMENTS_17_24.md) for detaljer:
- Task 17: Automatiserte tester (PHPUnit)
- Task 18: Caching (Redis/Memcached)
- Task 19: Database-optimalisering (indekser, UNION queries)
- Task 20: Tilgjengelighet (ARIA, skip links)
- Task 21: Ekstraher repetert kode
- Task 22: PHPDoc-kommentarer
- Task 23: Transaksjons-håndtering
- Task 24: Manglende Access-funksjoner (NotInList, batch ops, audit trail)

## Viktige implementerte funksjoner

### Fase A (Opprydding) ✅
- Include-rekkefølge korrigert
- Debug-logging fjernet
- Dupliserte funksjoner konsolidert
- Død kode fjernet (Task 8)

### Fase B (Søk og filtrering) ✅
- Paging (LIMIT/OFFSET, 20 per side)
- Totalt antall treff visning
- Navigasjon (Forrige/Neste)

### Fase C (Access-paritet) ✅
- URL_Bane-generering (auto, ved lagring)
- Kopier foto-funksjon (med nullstilling)
- H1/H2 modus (rediger vs opprett)
- iCh 1-6 hendelsesmodus

### Brukeradministrasjon ✅
- Admin-meny på index.php
- Komplett CRUD for brukere
- Rollebasert tilgangskontroll
- "Remember me"-funksjonalitet
- Passordkompleksitet (Task 6)

### Admin Excel-eksport ✅
- Toggle Transferred-status (checkbox, AJAX)
- Excel-eksport med SerNr-filtrering
- Modal dialog med auto-fylling (høy = lav + 1)
- CSV-format med UTF-8 BOM
- Bekreftelsesside med bulk-update

### Sikkerhetsforbedringer ✅
- CSRF-beskyttelse på alle POST-operasjoner
- Prepared statements (SQL injection-beskyttelse)
- Output escaping med `h()` (XSS-beskyttelse)
- Password hashing med bcrypt
- Environment-aware error handling
- Comprehensive logging system

## Teknologier og mønstre

### Backend
- PHP 8.1+ (`declare(strict_types=1)`)
- PDO for database-tilgang
- Session-basert autentisering
- CSRF-tokens
- Prepared statements

### Frontend
- Vanilla JavaScript (ingen rammeverk)
- Fetch API for AJAX
- CSS utility-klasser
- Responsive design (flexbox)

### Database
- MySQL 8.0+
- Normalisert skjema
- Relasjonelle koblinger via x-tabeller
- Auto-increment primary keys

### Sikkerhet
- Input-validering
- Output-escaping
- CSRF-beskyttelse
- XSS-beskyttelse
- SQL injection-beskyttelse
- Passordkompleksitet
- Error logging (uten sensitive data)

## Filnavn-konvensjoner

- **PHP:** `snake_case.php` (primus_main.php, bruker_admin.php)
- **JavaScript:** `snake_case.js` (primus_main.js)
- **CSS:** `snake_case.css` (app.css)
- **Config:** `camelCase.php` eller `snake_case.php` (config.php, constants.php)
- **Dokumentasjon:** `PascalCase.md` eller `UPPERCASE.md` (ROADMAP.md, Primus_Schema.md)

## Viktighetsrekkefølge for dokumenter

1. **AGENTS.md** - Operativt kontrakt (HØYESTE AUTORITET)
2. **CLAUDE.md** - Teknisk referansedokument
3. **Primus_Funksjonalitet.md** - Funksjonell beskrivelse
4. **Primus_Schema.md** - Database-skjema
5. **Primus_Filstruktur.md** - Denne filen
6. **ROADMAP.md** - Planlagte forbedringer
7. **README.md** - Prosjektoversikt

---

**Sist oppdatert:** 2026-01-03
**Versjon:** 2.0
**Av:** Claude Code
