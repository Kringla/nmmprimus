# NMMPrimus – Roadmap for forbedringer

Basert på omfattende kodegjennomgang 2026-01-03.

---

## Status

**Overordnet vurdering:** 7/10
- ✅ God arkitektur og sikkerhetsfundament
- ✅ Utmerket dokumentasjon
- ⚠️ Sikkerhetshull med eksponerte credentials
- ⚠️ Kodekvalitet og vedlikeholdbarhet kan forbedres

---

## Prioriterte endringer

### 🔴 KRITISK (Fikses umiddelbart)

#### 1. Fjern produksjons-credentials fra repository
**Fil:** `config/configProd.php`
**Problem:** Produksjons-database-passord commitet til repo
```php
define('DB_PASS', 'Use!Web?');  // ⚠️ EKSPONERT
```

**Løsning:**
- [ ] Opprett `.env`-fil for miljøvariabler (unntatt fra git)
- [ ] Oppdater `includes/db.php` til å lese fra miljøvariabler
- [ ] Rullere eksponert passord på webhotellet
- [ ] Fjern `config/configProd.php` fra git-historikk

**Estimat:** 2 timer
**Prioritet:** 🔴 KRITISK

---

#### 2. Fikse CSRF-sårbarhet på GET-operasjoner
**Fil:** `modules/primus/primus_detalj.php` (linjer 149-196)
**Problem:** Database-endringer via GET-parameter `add_avbildet_nmm_id`

**Løsning:**
- [ ] Konverter til POST-operasjon
- [ ] Legg til CSRF-token validering
- [ ] Oppdater JavaScript for å bruke fetch() POST

**Estimat:** 1 time
**Prioritet:** 🔴 KRITISK

---

#### 3. Fjern/sikre opprett_bruker.php
**Fil:** `opprett_bruker.php`
**Problem:** Hardkodet admin-credentials i root-fil
```php
$email    = 'gerhard@ihlen.net';
$passord = '1Gondor!';  // ⚠️ HARDKODET
```

**Løsning:**
- [ ] Flytt til `setup/`-mappe (utenfor webroot)
- [ ] Eller slett helt (bruk bruker_admin.php i stedet)
- [ ] Fjern hardkodede credentials

**Estimat:** 30 minutter
**Prioritet:** 🔴 KRITISK

---

#### 4. Implementer .gitignore
**Problem:** Ingen .gitignore-fil

**Løsning:**
Opprett `.gitignore`:
```
# Environment
.env
config/config.php
config/configProd.php

# IDE
.vscode/
.idea/
*.swp

# System
.DS_Store
Thumbs.db

# Logs
*.log

# Temporary
tmp/
temp/
```

**Estimat:** 15 minutter
**Prioritet:** 🔴 KRITISK

---

### 🟠 HØY (Fikses snart)

#### 5. Implementer miljø-deteksjon
**Fil:** `includes/db.php`
**Problem:** Laster alltid lokal config, aldri produksjon

**Løsning:**
```php
$env = getenv('APP_ENV') ?: 'development';
if ($env === 'production') {
    require_once __DIR__ . '/../config/configProd.php';
    require_once __DIR__ . '/../config/constantsProd.php';
} else {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/constants.php';
}
```

**Estimat:** 1 time
**Prioritet:** 🟠 HØY

---

#### 6. Styrk passordkrav
**Fil:** `modules/admin/bruker_admin.php` (linje 37)
**Problem:** 6 tegn minimum er for svakt

**Løsning:**
- [ ] Øk til minimum 12 tegn
- [ ] Legg til kompleksitetskrav (store/små bokstaver, tall, spesialtegn)
- [ ] Sjekk mot vanlige passord-lister (optional)

**Estimat:** 1 time
**Prioritet:** 🟠 HØY

---

#### 7. Legg til sentralisert error logging
**Problem:** Ingen systematisk error logging

**Løsning:**
- [ ] Opprett `includes/error_handler.php`
- [ ] Implementer custom error handler
- [ ] Logg til `logs/error.log` (unntatt fra git)
- [ ] Aldri vis stacktrace til bruker i produksjon

**Estimat:** 2 timer
**Prioritet:** 🟠 HØY

---

#### 8. Fjern død kode
**Filer:**
- `modules/primus/primus_modell.php` – `primus_oppdater_foto()` (linjer 307-341)
- `modules/foto/api/kandidater.php` – ubrukt fil?
- `modules/foto/api/velg_kandidat.php` – ubrukt fil?

**Løsning:**
- [ ] Verifiser at funksjoner/filer ikke er i bruk
- [ ] Slett eller flytt til `zzz/`-mappe

**Estimat:** 1 time
**Prioritet:** 🟠 HØY

---

#### 9. Konsolider foto_hent_en()
**Filer:**
- `modules/foto/foto_modell.php` (linjer 8-19)
- `modules/primus/primus_modell.php` (potensielt duplisert)

**Løsning:**
- [ ] Behold kun én versjon i `foto_modell.php`
- [ ] Sørg for at alle filer inkluderer riktig modell-fil
- [ ] Fjern `function_exists()`-wrappere

**Estimat:** 1 time
**Prioritet:** 🟠 HØY

---

#### 10. Fjern function_exists()-wrappere
**Fil:** `modules/primus/primus_modell.php` (14 forekomster)
**Problem:** Unødvendig når `require_once` brukes

**Løsning:**
- [ ] Fjern alle `if (!function_exists(...))` wrapper
- [ ] Verifiser at `require_once` brukes konsekvent

**Estimat:** 30 minutter
**Prioritet:** 🟠 HØY

---

### 🟡 MEDIUM (Neste sprint)

#### 11. Flytt inline CSS til app.css
**Fil:** `modules/primus/primus_main.php` (linjer 277-415, 138 linjer CSS)
**Problem:** Inline CSS reduserer vedlikeholdbarhet

**Løsning:**
- [ ] Flytt all CSS til `assets/app.css`
- [ ] Bruk unike klassenavn (`.primus-main-*`)
- [ ] Fjern inline `<style>`-tags

**Estimat:** 2 timer
**Prioritet:** 🟡 MEDIUM

---

#### 12. Ekstraher JavaScript til egne filer
**Fil:** `modules/primus/primus_detalj.php` (linjer 572-836)
**Problem:** 264 linjer JavaScript inline

**Løsning:**
- [ ] Opprett `assets/primus_detalj.js`
- [ ] Flytt all JavaScript
- [ ] Inkluder via `<script src="...">`

**Estimat:** 2 timer
**Prioritet:** 🟡 MEDIUM

---

#### 13. Legg til API-autentisering
**Filer:** Alle `modules/*/api/*.php`
**Problem:** Inkonsistent autentisering

**Løsning:**
- [ ] Standardiser på `require_login()` i alle API-endepunkter
- [ ] Legg til rate limiting (optional)
- [ ] Konsistent error-respons format

**Estimat:** 3 timer
**Prioritet:** 🟡 MEDIUM

---

#### 14. Forbedre input-validering
**Problem:** Minimal server-side validering

**Løsning:**
- [ ] Opprett `includes/validation.php`
- [ ] Sentraliserte valideringsfunksjoner
- [ ] Valider alle bruker-input
- [ ] Whitelist-tilnærming for alle felt

**Estimat:** 4 timer
**Prioritet:** 🟡 MEDIUM

---

#### 15. Legg til bruker-feedback
**Fil:** `modules/primus/primus_detalj.php` JavaScript
**Problem:** Stille feil, ingen loading-indikatorer

**Løsning:**
- [ ] Vis loading-spinner ved AJAX-kall
- [ ] Toast-notifikasjoner for suksess/feil
- [ ] Bedre error-meldinger

**Estimat:** 3 timer
**Prioritet:** 🟡 MEDIUM

---

#### 16. Fikse hardkodet BASE_URL
**Fil:** `modules/fartoy/fartoy_velg.php` (linje 15)
```php
redirect('/nmmprimus/modules/primus/primus_main.php');
```

**Løsning:**
```php
redirect(BASE_URL . '/modules/primus/primus_main.php');
```

**Estimat:** 15 minutter
**Prioritet:** 🟡 MEDIUM

---

### 🟢 LAV (Teknisk gjeld)

#### 17. Legg til automatiserte tester
**Problem:** Ingen tester

**Løsning:**
- [ ] Sett opp PHPUnit
- [ ] Skriv enhetstester for modell-funksjoner
- [ ] Integrasjonstester for kritiske flows
- [ ] CI/CD pipeline (optional)

**Estimat:** 16+ timer
**Prioritet:** 🟢 LAV (men viktig langsiktig)

---

#### 18. Implementer caching
**Problem:** Ingen caching-lag

**Løsning:**
- [ ] Cache static lookup-tabeller (bildeserie, country, farttype)
- [ ] Session-caching for brukerdata
- [ ] Vurder Redis/Memcached

**Estimat:** 8 timer
**Prioritet:** 🟢 LAV

---

#### 19. Optimaliser database-queries
**Problem:** N+1 queries, ineffektiv LEFT()-bruk

**Løsning:**
- [ ] Kombiner nmmxou/nmmxudk-queries til én UNION
- [ ] Erstatt `LEFT(Bilde_Fil, 8)` med `LIKE 'serie%'`
- [ ] Legg til database-indekser
- [ ] Dokumenter nødvendige indekser

**Estimat:** 4 timer
**Prioritet:** 🟢 LAV

---

#### 20. Forbedre tilgjengelighet
**Problem:** Mangler ARIA-labels, skip links, fargeblind-støtte

**Løsning:**
- [ ] Legg til aria-labels på interaktive elementer
- [ ] Implementer "skip to content"-link
- [ ] Legg til ikoner/tekst ved siden av farge-indikatorer
- [ ] Tast through flows

**Estimat:** 6 timer
**Prioritet:** 🟢 LAV

---

#### 21. Ekstraher repetert kode
**Filer:** Flere forekomster av string concatenation i loops

**Løsning:**
- [ ] Opprett hjelpefunksjoner i `includes/functions.php`
- [ ] Reduser code duplication

**Estimat:** 2 timer
**Prioritet:** 🟢 LAV

---

#### 22. Legg til PHPDoc-kommentarer
**Problem:** Manglende function-level dokumentasjon

**Løsning:**
- [ ] Legg til PHPDoc for alle public functions
- [ ] Dokumenter parametere, return-verdier, exceptions

**Estimat:** 8 timer
**Prioritet:** 🟢 LAV

---

#### 23. Implementer transaksjons-håndtering
**Problem:** Uklare transaksjons-grenser

**Løsning:**
- [ ] Definer klare transaction boundaries
- [ ] Bruk transactions for multi-step operasjoner
- [ ] Fjern mystisk `if ($db->inTransaction())` i primus_detalj.php

**Estimat:** 3 timer
**Prioritet:** 🟢 LAV

---

#### 24. Legg til manglende Access-funksjoner
**Mangler:**
- NotInList-håndtering (opprett ny vessel on-the-fly)
- Avansert søk
- Batch-operasjoner (bulk edit/delete)
- Audit trail (hvem endret hva når)

**Estimat:** 24+ timer (avhenger av scope)
**Prioritet:** 🟢 LAV (vurder behov først)

---

## Implementeringsplan

### Sprint 1: Sikkerhet (1 uke)
- Task 1-4: KRITISKE sikkerhetsforbedringer
- Task 5: Miljø-deteksjon
- Task 6: Passordkrav

### Sprint 2: Kodekvalitet (1 uke)
- Task 7: Error logging
- Task 8-10: Fjern død kode, konsolider funksjoner

### Sprint 3: Frontend (1 uke)
- Task 11-12: Ekstraher CSS/JS
- Task 15: Bruker-feedback
- Task 16: Fikse hardkoding

### Sprint 4: API & Validering (1 uke)
- Task 13: API-autentisering
- Task 14: Input-validering

### Sprint 5+: Teknisk gjeld
- Task 17-24: Vurder prioritering basert på faktisk behov

---

## Estimat totalt

| Prioritet | Antall tasks | Estimert tid |
|-----------|--------------|--------------|
| 🔴 KRITISK | 4 | 4 timer |
| 🟠 HØY | 6 | 8 timer |
| 🟡 MEDIUM | 6 | 16 timer |
| 🟢 LAV | 8 | 65+ timer |
| **TOTALT** | **24** | **93+ timer** |

---

## Vedlikehold fremover

### Etter implementering
- [ ] Oppdater CLAUDE.md seksjon 11 (Kjente problemer)
- [ ] Oppdater dokumentasjon med nye patterns
- [ ] Legg til CHANGELOG.md
- [ ] Versjonering (semantic versioning)

### Kontinuerlig
- Code reviews før commit
- Kjør tester før deploy (når implementert)
- Overvåk error logs
- Regelmessig sikkerhetsoversikt

---

## Notater

- **KRITISKE endringer må gjøres først** (sikkerhet)
- **HØY-prioritet bør gjøres innen 2 uker**
- **MEDIUM og LAV** kan planlegges basert på faktisk behov og tid
- Vurder å opprette GitHub Issues for hver task
- Bruk feature branches for større endringer

---

**Opprettet:** 2026-01-03
**Basert på:** Omfattende kodegjennomgang av Claude Code
**Se også:** [CLAUDE.md](CLAUDE.md) seksjon 11 for kjente problemer
