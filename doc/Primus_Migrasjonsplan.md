# Primus – Migrasjonsplan

# Primus – Migrasjonsplan

## 1. Formål og omfang

Denne migrasjonsplanen beskriver en trinnvis, kontrollert overgang fra eksisterende Microsoft Access‑basert løsning til en fullverdig, nettbasert applikasjon for prosjektet **nmmprimus**. Planen er styrende for videre utvikling og skal benyttes som referanse før alle større tekniske og funksjonelle beslutninger.

Planen er utarbeidet på grunnlag av:

* Prosjekt Krav Dokument (PRD)
* Databaseskjema (`Primus_Schema.md`)
* Filstruktur (`Primus_Filstruktur.md`)
* Funksjonsbeskrivelse (`Primus_Funksjonalitet.md`)
* Access‑dokumentasjon (`AccessObjects.pdf`)
* Samarbeids‑ og leveranseregler (`AGENTS.md`)

Prosjektet **nmmprimus** utvikles i full isolasjon fra andre prosjekter.

---

## 2. Overordnede prinsipper

### 2.1 Teknologiske rammer

* **Backend:** PHP 8.3.x
* **Database:** MySQL 8.3.28 (eksisterende database `nmmprimus`)
* **Frontend:** HTML5, CSS (samlet i `assets/app.css`), begrenset JavaScript
* **Autentisering:** Sesjonsbasert innlogging med e‑post/passord

### 2.2 Arkitekturprinsipper

* Ingen endringer i `config/`‑mappen
* Klar separasjon mellom:

  * Infrastruktur (`config/`, `includes/`)
  * Presentasjon (`layout_start.php`, `layout_slutt.php`, `ui.php`)
  * Domene/funksjonelle moduler (`modules/`)
* All aktiv redigering skjer i tabellen **`nmmfoto`**
* Øvrige tabeller behandles som parametertabeller (lese‑ eller støttefunksjon)

### 2.3 Utviklingsmetodikk

* Trinnvis migrering
* Funksjon for funksjon, ikke «big‑bang»
* All kode basert på faktisk lest dokumentasjon og skjema
* Løpende validering mot AGENTS.md

---

## 3. Migreringsstrategi

Migreringen deles i logiske trinn der hvert trinn gir et fungerende, testbart delresultat.

### Trinn 1 – Grunnplattform og tilgangskontroll

**Mål:** Etablere teknisk fundament

**Innhold:**

* Ferdigstille databasekobling (`includes/db.php`)
* Grunnleggende hjelpefunksjoner (`includes/functions.php`)
* Autentisering:

  * `login.php`
  * `logout.php`
  * `includes/auth.php`
* Sesjonsstyring
* Verifisering av `user`‑tabell

**Resultat:**

* Bruker kan logge inn og forbli autentisert i sesjonen
* Beskyttede sider kan etableres

---

### Trinn 2 – Layout, UI‑rammeverk og visuell konsistens

**Mål:** Felles visuelt og strukturelt rammeverk

**Innhold:**

* Standardisere bruk av:

  * `layout_start.php`
  * `layout_slutt.php`
* Etablere/ferdigstille `includes/ui.php`
* Grunnleggende stil i `assets/app.css`
* Cards, tabeller og overskrifter med nøktern utforming

**Resultat:**

* Alle sider deler felles struktur
* Ingen inline‑CSS

---

### Trinn 3 – Lesetilgang til kjernedata

**Mål:** Trygg visning av data før redigering

**Innhold:**

* Oversiktsvisning av fotoobjekter (basert på `nmmfoto`)
* Enkle SELECT‑spørringer mot:

  * `nmmfoto`
  * `nmm_skip`
  * sentrale parametertabeller (`country`, `farttype`, m.fl.)
* Avklare hvilke felt som vises i UI (utelate `Flag`, tekniske felt)

**Resultat:**

* Bruker kan navigere i datasettet uten å endre noe

---

### Trinn 4 – Redigering av `nmmfoto`

**Mål:** Full erstatning av Access‑skjema for foto

**Innhold:**

* Redigeringsskjema for `nmmfoto`
* Feltgruppering etter faglig mening (motiv, foto, metadata)
* Håndtering av:

  * Ja/Nei‑felt
  * fritekst
  * relaterte x‑tabeller (`nmmxemne`, `nmmxhendelse`, `nmmxou`, `nmmxtype`, `nmmxudk`)
* Server‑side validering

**Resultat:**

* Full CRUD‑støtte for `nmmfoto`

---

### Trinn 5 – Relasjoner og støttetabeller

**Mål:** Gjenskape Access‑logikk rundt koblingstabeller

**Innhold:**

* Redigering/vedlikehold av:

  * Emner
  * Hendelser
  * Klassifikasjoner
* Kontrollert håndtering av Ser_ID / Foto_ID
* Unngå duplisering av data

**Resultat:**

* Samme informasjonsrikdom som i Access

---

### Trinn 6 – Søk, filtrering og arbeidsflyt

**Mål:** Effektiv bruk i daglig arbeid

**Innhold:**

* Søk på sentrale felter
* Filtrering (status, samling, tidsrom)
* Sortering
* Enkle hurtigvalg

**Resultat:**

* Brukervennlig og effektiv løsning

---

### Trinn 7 – Kvalitet, sikkerhet og ferdigstillelse

**Mål:** Produksjonsklar løsning

**Innhold:**

* Rolle‑ og tilgangsvurdering (evt. utvidelse av `user`)
* SQL‑herding og input‑sikring
* Gjennomgang av kodekvalitet
* Opprydding i midlertidige filer

**Resultat:**

* Stabil, vedlikeholdbar applikasjon

---

## 4. Teststrategi

* Lokal testing (XAMPP)
* Hard refresh (`CTRL+F5`)
* Chrome som primær nettleser
* Skjermoppløsning: 1920×1080 (PC)

---

## 5. Videre dokumentasjon

Følgende dokumenter oppdateres fortløpende i takt med utviklingen:

* `Primus_Views.md`
* `Primus_Forms_Map.md`
* Eventuelle tekniske notater

---

## 6. Endringskontroll

Denne migrasjonsplanen er et levende dokument. Endringer skal:

* Begrunnes
* Dokumenteres
* Avstemmes mot prosjektets mål og AGENTS.md

---

**Status:** Første versjon – styrende for videre arbeid


Fase 1 — Navigasjon + “Main”-oversikt (Access: frmNMMPrimusMain / qryNSMPrimusMain)

Mål: Lage web-hjem som tilsvarer “hovedoversikt”.

Leveranser:

Ny modul: modules/primus/primus_oversikt.php som erstatter/utfyller index.

Underliggende query/view som tilsvarer qryNSMPrimusMain 

AccessObjects

Listevisning med:

søkefelt

sortering (typisk på ID/registreringsdato)

paging

Akseptansekriterier:

Bruker kan finne poster fra main-oversikten og klikke seg til detalj.

Fase 2 — Fartøyvalg og kandidatflyt (Access: frmNMMSkipsValg + frmNMMPrimusKand subform)

Mål: Reprodusere søk/valg-flyten som Access har for fartøy.

Indikasjoner i Access:

Skipsvalg-form bruker qryNavnScr og sorterer på nmm_skip.NMM_ID DESC 

AccessObjects

Kandidat-subform har filtrering og “dobbeltklikk for å velge” 

AccessObjects

Leveranser:

modules/fartoy/fartoy_liste.php (søk på del av fartøynavn, flaggstat, etc.)

modules/fartoy/fartoy_velg.php (returnerer valgt NMM_ID til kallende skjema)

Støtte for filter og “tilbake til main”.

Akseptansekriterier:

Bruker kan søke, filtrere, velge fartøy og komme tilbake til primus-registrering med valgt fartøy referanse.

Fase 3 — Foto-søk og detalj (Access: qryNMMfotoScr)

Mål: Implementere foto-søk og detaljvisning.

Indikasjon i Access:

qryNMMfotoScr eksisterer som egen søke-query 

AccessObjects

VBA peker på felter for URL/filbane og seriehåndtering (generering av Bilde_Fil, URL_Bane, URL_Bane_Bilde) 

AccessObjects

Leveranser:

modules/foto/foto_liste.php (søk på relevante metadata)

modules/foto/foto_detalj.php (inkl. visning av filbane/URL-felt)

Evt. modules/foto/foto_rediger.php (hvis Access tillater edits)

Server-side logikk for å generere/oppdatere felt som Access gjorde i UpdateURLFields() 

AccessObjects

Akseptansekriterier:

Bruker kan søke opp foto, åpne detalj, se (og ev. endre) metadata uten datatap.

Fase 4 — Registrering/redigering (Access: frmNMMPrimus)

Mål: Full CRUD-funksjonalitet for primus-kjerneobjekt.

Indikasjon i Access:

Form har filter Foto_ID = 66 eksempel, og er “Single Form” 

AccessObjects

Inneholder “serie-endring”, NotInList-ny kategori og eksplisitt feilhåndtering

Leveranser:

modules/primus/primus_detalj.php

modules/primus/primus_rediger.php

Validering og oppslag mot hjelpetabeller (kategorier, serier, osv.)

NotInList-funksjon: tilby å opprette manglende kategori (web-variant av Access NotInList) 

AccessObjects

Akseptansekriterier:

Redigering følger samme regler som Access (feltlåsing, obligatoriske felt, autogenererte felter).

Fase 5 — Rapporter, eksport, og kvalitet

Mål: Erstatte Access-rapporter og manuelle rutiner med web-rapporter og eksport.

Leveranser:

CSV/XLSX eksport for sentrale lister (main, foto, fartøy)

“Rapport”-sider som erstatter Access reports (start med de mest brukte)

Revisjonslogg (hvem endret hva, når)

7. Datamigrasjon og datakvalitet

Selv om tabeller allerede finnes i MySQL, standardiserer vi migreringsregime:

Kontrakt pr tabell: PK, FK, nullability, default, indekser.

Query-paritet: For hver Access query vi migrerer til view/SQL:

“Before/After” kontroll: antall rader, nøkkelfelt, og noen stikkprøver.

Ytelse: Indekser på søkefelt, paging via LIMIT/OFFSET eller keyset-pagination der nødvendig.

8. Sikkerhet og compliance (minimum)

Passord lagres kun som moderne hash (bcrypt/argon2).

CSRF på alle skriveoperasjoner.

Filbaner/URL-felt valideres og normaliseres.

Rollebasert tilgang: les/skriv/admin.

Audit logging for endringer i kjerneobjekter.

9. Milepæler (foreslått rekkefølge)

M0: “Remember me” + RBAC-hardening + CSRF (plattform klar)

M1: Primus oversikt (main) + detaljnavigasjon

M2: Fartøy-søk/velg (skipsvalg/kandidater)

M3: Foto-søk + detalj + URL/serie-regler

M4: Full redigering/CRUD for primus

M5: Rapporter/eksport + revisjonslogg

10. Risiko og avbøtende tiltak

Ufullstendig mapping av Access-regler (VBA):
Tiltak: Identifiser “kritiske” event-prosedyrer tidlig (AfterUpdate/NotInList/OnCurrent) og implementer dem server-side.

Query-kompleksitet:
Tiltak: Start med views for stable, gjenbrukte queries (f.eks. main-søk), ellers modulspesifikke spørringer.

Filhåndtering/paths:
Tiltak: Sentraliser path/URL-generering (web-variant av UpdateURLFields()) 

AccessObjects

11. Neste konkrete steg (foreslått “Trinn 1”)

Implementere “Remember me” i innloggingen (uttrykkelig ønsket) og ferdigstille plattformlaget før første domenemodul.

Dette berører eksisterende login-flyt direkte  og skal ende i en stabil, gjenbrukbar require_login()-mekanisme for alle moduler 

