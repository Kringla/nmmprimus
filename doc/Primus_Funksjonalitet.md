# Primus – Funksjonell beskrivelse

Dette dokumentet beskriver **hva** NMMPrimus skal gjøre funksjonelt, basert på Access-løsningen.

---

## Formål

Bygge tabellen `nmmfoto` ved å koble:
- Fartøydata fra `nmm_skip`
- Parametertabeller (emner, typer, klassifikasjoner)
- Manuelt input

---

## Landingsside (primus_main.php)

**Access-ekvivalent:** frmNMMPrimusMain

### Visning

1. **Serie-velger** (combobox)
   - Viser `Serie` fra `bildeserie`-tabellen
   - Husker siste valgte serie (per bruker)
   - Første gang: vis første serie i tabellen

2. **Fotoliste**
   - Viser alle foto der `Bilde_Fil` starter med valgt serie (8 første tegn)
   - Kolonner: `Bilde_Fil`, `MotivBeskr`, `Transferred`
   - Sortering: `Bilde_Fil` DESC
   - Paging: 20 rader per side

### Hendelser

- **H1 (Rediger):** Dobbeltklikk på rad → åpne detaljvisning
  - `Foto_ID` brukes som nøkkel
  - Alle felt forhåndsutfylt

- **H2 (Ny):** Klikk "Ny"-knapp
  - Opprett nytt foto i serien
  - `SerNr` = neste ledige nummer (1-999)
  - Kun `SerNr` og `Bilde_Fil` forhåndsutfylt

---

## Detaljvisning (primus_detalj.php)

**Access-ekvivalent:** frmNMMPrimus + frmNMMPrimusKand subform

### Layout

**Venstre panel:**
- Søkbar tabell fra `nmm_skip`
- Kolonner: `NMM_ID`, `FTY`, `FNA`, `BYG`, `RGH`, `Nasjon`, `KAL`
- Søk: Fritekst i `FNA`. Søkestreng i felt med tittel "Fartøynavn" (min 3 tegn, ikke case-sensitivt), default verdi for felt med tittel "Fartøynavn" = siste benyttete søkestreng.
- Resultat: Max 20 rader med scrolling
- Antall treff vises over tabellen

**Høyre panel:**
- Alle felt fra `nmmfoto` (unntatt `Flag`, `Transferred`, `Foto_ID`)
- Felt gruppert i faner:
  - **Motiv** – MotivBeskr, MotivType, MotivEmne, MotivKriteria
  - **Bildehistorikk** – Fotograf, FotoFirma, FotoTid, FotoSted, Aksesjon, Samling
  - **Øvrige** – Prosess, Referanser, Plassering, Status

### Hendelsesmodus (iCh)

Radioknapper (1-4) som styrer:
- Hvilke felt som er redigerbare
- Visuell markering (grønn/rød ramme)
- Lagres i session

**iCh-modus:**
- **1:** Ingen hendelse (ingen foto- og samling-felt aktive, ingen default verdier vises, feltet `Frikopi` verdi = 1, feltet `Aksesjon` verdi = 0, feltet `Fotografi` verdi = 0)
- **2:** Fotohendelse (foto-felt aktive, feltet `Frikopi` verdi = 1, feltet `Aksesjon` verdi = 0, feltet `Fotografi` verdi = 1)
  - Default verdi `Fotograf` = "10F:" vises. Skal ikke lagres hvis ikke etterfulgt av minst 2 karakterer. Eksempel: Verdi "10F: BK" lagres, verdi "10F:" alene lagres ikke.
- **3:** Samlingshendelse (`Samling`-felt aktivt, feltet `Frikopi` verdi = 0, feltet `Aksesjon` verdi = 1, feltet `Fotografi` verdi = 0)
  - `Samling`-felt er rullefelt med tre verdier (i sorteringsrekkefølge) "C2-Johnsen, Per-Erik", "C2-Gjersøe, Georg" og "C2-".
  - Default verdi `Samling` = "C2-Johnsen, Per-Erik" som vises. Verdien "C2-" skal ikke lagres hvis ikke etterfulgt av minst 2 karakterer. Eksempel: Verdi "C2-BK" lagres, verdi "C2-" alene lagres ikke.
- **4:** Foto + Samling (både foto-felt og samling-felt aktive, feltet `Frikopi` verdi = 0, feltet `Aksesjon` verdi = 1, feltet `Fotografi` verdi = 1)
  - Defaultverdier og lagringsregler som for både iCh-modus 2 og 3.


### Kandidatvalg 

Søk i venstre panel etter `FNA`-verdier som inneholder søkestrengen i felt med tittel "Fartøynavn". Søk aktiveres når mer enn 2 karakterer i søkestrengen ved at bruker trykker <Enter> eller egen knapp "Søk".

Ved klikk på rad i venstre panel:

- `MotivBeskr` fylles med fartøynavn og type
- `FTO` fylles med fartøydata
- Felt fylles fra x-tabeller basert på `NMM_ID`:
  - `nmmxemne` → MotivEmne
  - `nmmxtype` → MotivType
  - `nmmxou` → MotivKriteria
  - `nmmxudk` → (klassifikasjon)
  - `nmmxhendelse` → Hendelse
I H1-modus skal bruker bekrefte valget før feltet oppdateres.

### Valg av  `SerNr` 

**Automatisk valg**: Neste ledige nummer i den "NSM Serie"-og den "Serienr" - verdien som brukeren sist benyttet.
**Manuelt valg**: Begge kan endres manuelt
"Bildefil"-verdien oppdateres automatisk

### Automatiske oppdateringer

- **SerNr → Bilde_Fil:** Konkatinering av `Serie` + "-" + `SerNr`
- **FotoTidFra → FotoTidTil:** Automatisk kopi ved endring
- **Bilde_Fil → URL_Bane:** Auto-generert ved lagring

### Funksjoner

- **Lagre:** Oppdater eksisterende eller opprett ny post
- **Kopier:** Dupliser foto med nytt `SerNr` (nullstill feltene i fanen "Bildehistorikk" til databasens default verdier, og referanse-feltene under fanen "Øvrige" til tomme)
- **Ny:** Opprett helt nytt foto i samme serie
- **SerNr-endring:** Validering mot ledige numre (1-999)

---

## Fartøyvalg (fartoy_velg.php)

**Access-ekvivalent:** frmNMMSkipsValg

### Funksjon

- Søk etter fartøy (fritekst i `FNA`)
- Liste med 25 rader (scrollbar)
- Velg fartøy → returner `NMM_ID` til detaljvisning

---

## Viktig atferd

### Standardverdier
- Session husker siste valg (serie, hendelsesmodus, fane)
- Database defaults brukes for nye poster

### Umiddelbar respons
- AJAX for kandidatvalg
- Ingen sideoppdatering ved hendelsesmodus-endring

### Validering
- SerNr: 1-999, unikt i serien
- Bilde_Fil: Auto-generert, ikke manuelt redigerbar
- URL_Bane: Auto-generert basert på Bilde_Fil

---

**Se [CLAUDE.md](../CLAUDE.md) for teknisk implementering.**
