# FIL STRUKTUR OG LISTE

Overordnet mappe: Git-repo for Primusdatabasen.

**Sist oppdatert:** 2025-12-24

Midlertidige filer vises ikke.
Dokument-filer vises ikke.

## Rotstruktur

```
nmmprimus/
 ├─ config/                    # Konfigurasjonsfiler
 │   ├─ constants.php          # Konstanter (BASE_URL, etc.)
 │   ├─ constantsProd.php      # Produksjonskonstanter
 │   ├─ config.php             # Database-konfigurasjon (utvikling)
 │   └─ configProd.php         # Database-konfigurasjon (produksjon)
 │
 ├─ includes/                  # Delte hjelpefunksjoner og layout
 │   ├─ auth.php               # Autentisering og sesjonshåndtering
 │   ├─ db.php                 # Database-tilkobling
 │   ├─ foto_flyt.php          # Foto-flytkontroll (¤ ikke i bruk)
 │   ├─ functions.php          # Generelle hjelpefunksjoner
 │   ├─ layout_slutt.php       # HTML footer og avslutning
 │   ├─ layout_start.php       # HTML header og navigasjon
 │   ├─ ui.php                 # UI-komponenter (card, table, etc.)
 │   └─ user_functions.php     # Brukeradministrasjon (CRUD)
 │
 ├─ assets/                    # CSS, JavaScript, bilder
 │   └─ app.css                # Hovedstil for applikasjonen
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
 │   │       ├─ foto_state.php      # Hendelsesmodus (iCh) felt-enable/disable
 │   │       ├─ kandidater.php      # ¤ (ikke i bruk)
 │   │       └─ velg_kandidat.php   # ¤ (ikke i bruk)
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
 ├─ doc/                       # Dokumentasjon
 │   ├─ AccessObjects.pdf      # Access-databaseeksport (struktur)
 │   ├─ CODE_REVIEW_2.md       # Kodereview og oppryddingsplan
 │   ├─ frmNMMPrimus.pdf       # Access-form VBA-kode
 │   ├─ Primus_Filstruktur.md  # Denne filen
 │   ├─ Primus_Funksjonalitet.md
 │   ├─ Primus_Migrasjonsplan.md
 │   ├─ Primus_RD_Claude.md
 │   ├─ Primus_Schema.md
 │   └─ SETUP_GUIDE.md
 │
 ├─ zzz/                       # Arkiv/testfiler
 │   ├─ foto_arbeidsflate.php  # ¤ Gammel arbeidsflate
 │   ├─ ui_demo.php            # ¤ UI-komponent demo
 │   ├─ AGENTSGen.md
 │   ├─ CODE_REVIEW.md
 │   ├─ Primus_RD_GPT.md
 │   └─ ToDo.md
 │
 ├─ .claude/                   # Claude Code konfigurasjon
 ├─ .git/                      # Git versjonskontroll
 ├─ .gitignore                 # Git ignore-filer
 │
 ├─ index.php                  # Forside (admin-meny / redirect)
 ├─ login.php                  # Innloggingsside
 ├─ logout.php                 # Utlogging
 └─ opprett_bruker.php         # ¤ CLI-verktøy for brukeropp (erstattet av admin-modul)
```

## Nøkkelfunksjoner per modul

### Admin-modul (`modules/admin/`)
- **bruker_admin.php**: Komplett brukeradministrasjon
  - Opprett nye brukere (admin/bruker)
  - Rediger eksisterende brukere (e-post, rolle)
  - Endre passord
  - Aktivere/deaktivere brukere
  - Slette brukere (med sikkerhet)

### Primus-modul (`modules/primus/`)
- **primus_main.php**: Landingsside
  - Velg bildeserie fra dropdown
  - Liste over foto (20 per side, paging)
  - Opprett nytt foto
  - Dobbeltklikk for redigering
  - Slett foto
  - **Admin-funksjoner:**
    - Toggle Transferred-status (checkbox i liste)
    - Eksporter til Excel (modal dialog med SerNr-valg)

- **primus_detalj.php**: Detaljvisning
  - 3 faner: Motiv, Bildehistorikk, Øvrige
  - Kandidatpanel (venstre) for fartøyvalg
  - Hendelsesmodus (iCh 1-6) med felt-enable/disable
  - "Legg til i Avbildet" via fartøy-søk
  - "Kopier foto"-funksjon
  - Auto-generering av URL_Bane

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
  - Velg fartøy → koble til foto

## Viktige implementerte funksjoner

### Fase A (Opprydding) ✅
- Include-rekkefølge korrigert
- Debug-logging fjernet
- Dupliserte funksjoner konsolidert

### Fase B (Søk og filtrering) ✅
- Paging (LIMIT/OFFSET, 20 per side)
- Totalt antall treff visning
- Navigasjon (Forrige/Neste)

### Fase C (Access-paritet) ✅
- URL_Bane-generering (auto, ved lagring)
- Kopier foto-funksjon (med nullstilling)

### Brukeradministrasjon ✅
- Admin-meny på index.php
- Komplett CRUD for brukere
- Rollebasert tilgangskontroll

### Admin Excel-eksport ✅
- Toggle Transferred-status (checkbox, AJAX)
- Excel-eksport med SerNr-filtrering
- Modal dialog med auto-fylling (høy = lav + 1)
- CSV-format med UTF-8 BOM
- Bekreftelsesside med bulk-update