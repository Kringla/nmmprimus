# NMMPrimus – Code Review for Claude Code

**Dato:** 23. desember 2024  
**Formål:** Kontekstdokument for videre utvikling med Claude Code i VS Code

---

## 1. Prosjektoversikt

**Prosjekt:** nmmprimus  
**Formål:** Migrere Access-basert fotoarkiv-applikasjon til PHP/MySQL webløsning  
**Status:** Trinn 1–4 i migrasjonsplanen er i hovedsak gjennomført

### Styrende dokumenter (les disse først!)

1. `AGENTS.md` – Operativ kontrakt (HAR FORRANG)
2. `doc/Primus_PI.md` – Project Instructions
3. `doc/Primus_Migrasjonsplan.md` – Gjennomføringsplan
4. `doc/Primus_Schema.md` – Databaseskjema
5. `doc/Primus_Filstruktur.md` – Fil- og mappestruktur

### Access-former som skal migreres

| Access-form | Web-modul | Status |
|-------------|-----------|--------|
| frmNMMPrimusMain | `primus_main.php` | ✅ Implementert |
| frmNMMPrimus | `primus_detalj.php` | ✅ Implementert |
| frmNMMPrimusKand subform | Kandidatpanel i detalj | ✅ Implementert |
| frmNMMSkipsValg | `fartoy_velg.php` | ✅ Implementert |

---

## 2. Nåværende arkitektur

```
nmmprimus/
├── config/                    # Konfigurasjon (IKKE ENDRE)
│   ├── config.php             # DB-credentials (lokal)
│   └── constants.php          # BASE_URL
├── includes/                  # Infrastruktur
│   ├── auth.php               # Autentisering + "Remember me"
│   ├── db.php                 # PDO-tilkobling
│   ├── functions.php          # Hjelpefunksjoner + CSRF
│   ├── foto_flyt.php          # Hendelsesmodus-logikk
│   ├── layout_start.php       # HTML header
│   ├── layout_slutt.php       # HTML footer
│   └── ui.php                 # UI-komponenter
├── modules/
│   ├── primus/
│   │   ├── primus_main.php    # Hovedoversikt
│   │   ├── primus_detalj.php  # Foto-redigering
│   │   ├── primus_modell.php  # Datamodell
│   │   └── api/               # AJAX-endepunkter
│   ├── foto/
│   │   ├── foto_modell.php    # Foto CRUD
│   │   └── api/               # AJAX-endepunkter
│   └── fartoy/
│       └── fartoy_velg.php    # Fartøyvalg
├── assets/
│   └── app.css                # Samlet CSS
├── login.php
├── logout.php
└── index.php
```

---

## 3. Implementert funksjonalitet

### 3.1 Autentisering ✅
- Session-basert innlogging
- "Remember me" med token-rotasjon
- CSRF-beskyttelse via `csrf_token()`, `csrf_validate()`, `csrf_field()`
- Sikret session-API med whitelist

### 3.2 Hovedoversikt (primus_main.php) ✅
- Serievelger med session-persistens
- Fotoliste for valgt serie
- "Nytt foto" → H2-modus
- Dobbeltklikk → H1-modus (eksisterende foto)
- Sletting via POST med CSRF

### 3.3 Detaljvisning (primus_detalj.php) ✅
- Tre faner: Motiv, Bildehistorikk, Øvrige
- Kandidatpanel (H2-modus)
- Hendelsesmodus (iCh 1-6) med feltaktivering
- "Legg til i Avbildet" via fartoy_velg.php
- "Legg til Skipsportrett"
- Auto-generering av Bilde_Fil fra serie + serienr

### 3.4 Fartøyvalg (fartoy_velg.php) ✅
- Søk på fartøynavn
- Returnerer valgt NMM_ID til kallende side

---

## 4. Kjente problemer å fikse

### 4.1 Kodekvalitet

| Problem | Fil | Prioritet |
|---------|-----|-----------|
| Dupliserte funksjoner: `foto_hent_en()` og `primus_hent_foto()` | foto_modell.php, primus_modell.php | Middels |
| Ubrukt funksjon: `primus_oppdater_foto()` | primus_modell.php | Lav |
| `function_exists()`-wrapper er unødvendig med `require_once` | primus_modell.php | Lav |
| Inline CSS i primus_main.php | primus_main.php | Lav |
| Debug `error_log()`-kall bør fjernes | primus_detalj.php, fartoy_velg.php | Middels |

### 4.2 Arkitektur

| Problem | Beskrivelse | Prioritet |
|---------|-------------|-----------|
| Include-rekkefølge i fartoy_velg.php | `layout_start.php` inkluderes FØR `require_login()` | Høy |
| Inkonsistent bruk av BASE_URL | Noen steder hardkodet `/nmmprimus` | Middels |

### 4.3 Manglende funksjonalitet

| Funksjon | Beskrivelse | Prioritet |
|----------|-------------|-----------|
| URL_Bane-generering | Access-logikk for filbaner | Middels |
| Kopier foto | Access: cmdKopier (funksjon finnes, ikke knapp) | Lav |

---

## 5. Neste utviklingsoppgaver

### Fase A: Opprydding (umiddelbart)

1. **Fiks include-rekkefølge i fartoy_velg.php**
   - Flytt `require_login()` før `layout_start.php`

2. **Fjern debug-logging**
   - Slett `error_log()`-kall i primus_detalj.php og fartoy_velg.php

3. **Konsolider dupliserte funksjoner**
   - Velg én: `foto_hent_en()` eller `primus_hent_foto()`
   - Oppdater alle kallsteder

### Fase B: Søk og filtrering (Trinn 6 i migrasjonsplan)

1. **Implementer paging**
   - LIMIT/OFFSET eller keyset-pagination
   - Vis totalt antall treff

### Fase C: Access-paritet (Trinn 5)

1. **URL_Bane-generering**
   - Implementer `UpdateURLFields()` fra Access VBA
   - Server-side ved lagring

2. **Kopier foto-knapp**
   - Bruk eksisterende `foto_kopier()` funksjon
   - Legg til knapp i primus_detalj.php mellom "oppdater" og "Tilbake"-knapper

---

## 6. Kodestandarder (fra AGENTS.md)

### PHP
```php
<?php
declare(strict_types=1);  // ALLTID først

// PDO med prepared statements
$stmt = $db->prepare("SELECT * FROM table WHERE id = :id");
$stmt->execute(['id' => $id]);

// Output-escaping
echo h($userInput);

// CSRF på alle POST-skjemaer
<?= csrf_field(); ?>
```

### Filer
- `snake_case` for filnavn
- Norske, beskrivende navn
- `layout_start.php` / `layout_slutt.php` på alle sider

### Leveranser
- **Ingen linjenummer** i kodeblokker
- Patch med 3 linjer kontekst, eller komplett fil
- Basert på **faktisk lest kode**

---

## 7. Database-referanse

### Hovedtabeller
- `nmmfoto` – Fotoobjekter (hovedtabell)
- `nmm_skip` – Fartøyregister

### Relasjonstabeller (x-tabeller)
- `nmmxemne` – Motivemner
- `nmmxtype` – Motivtyper
- `nmmxou` – OU-klassifikasjoner
- `nmmxudk` – UDK-klassifikasjoner
- `nmmxhendelse` – Hendelser

### Parametertabeller
- `bildeserie` – Serier
- `farttype` – Fartøytyper
- `country` – Nasjoner
- `_zhendelsestyper` – Hendelsestyper

### Brukertabeller
- `user` – Brukere
- `user_preferences` – Sist valgt serie etc.
- `user_remember_tokens` – "Husk meg"-tokens

---

## 8. Testing

- **Miljø:** XAMPP lokalt, Chrome primær
- **Hard refresh:** Ctrl+F5 etter CSS/JS-endringer
- **Skjerm:** 1920×1080

### Testsekvens for ny funksjonalitet
1. Logg inn
2. Test funksjon
3. Sjekk at data lagres korrekt i DB
4. Test med flere brukere (sessions)
5. Test "Remember me" på tvers av browser-restart

---

## 9. Viktige Access-begreper

| Access-term | Web-ekvivalent |
|-------------|----------------|
| H1-modus | Eksisterende foto (kandidatpanel inaktivt) |
| H2-modus | Nytt foto (kandidatpanel aktivt) |
| iCh | Hendelsesmodus (1-6) |
| SummaryFields() | `primus_hent_kandidat_felter()` |
| cmdNytt_Click | "Nytt foto"-knapp |
| NotInList | Ikke implementert ennå |

---

## 10. Kontakt

Ved spørsmål eller uklarheter: **Stopp og avklar** (jf. AGENTS.md §7)
