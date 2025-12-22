# AGENTS.md

Prosjektets navn er **nmmprimus**
Denne filen gir Codex veiledning for arbeid i dette repoet, **nmmprimus**.
Innholdet gjelder for alle samtaler i prosjektet og har forrang foran
generelle eller implisitte antagelser.

## 1. Prosjektavgrensning (KRITISK)

- Prosjektet er **utelukkende** `nmmprimus`
- **Ingen** kode, arkitektur, stil eller antagelser skal hentes fra andre repoer.
  - generiske “moderne” maler uten eksplisitt forankring i dokumentasjonen
- Kun følgende kilder er autoritative:
  - Dokumenter i `doc/`-mappen
  - Filer eksplisitt vedlagt i chat
  
## 2. Rammer og referanser
### A. **Prosjektets styrende filer**: 
  - Dokumenter i `doc/`-mappen
  - `Primus _RD.md` — Prosjektets kravdokument. 
  - `Primus _Migreringsplan.md` — Prosjektets gjennomføringsplan. 

  - `Primus _PI.md` — Prosjektets prosjektinstruks.
  - `Primus _Schema.md` — Prosjektets databasens Schema.
  - `Primus _Filstruktur.md` — Prosjektets fil-struktur. 
  
  **Prosjektets styrende filer** skal **alltid** leses **før** utvikling/endring av ny kode.
### B. **Prosjektets informasjonsfiler**:
Prosjektet har i tillegg flere filer til informasjon, plassert i `doc/`-mappen:
  - `Primus_Funsjonalitet.md` — Alle data om objektene i Access-databasen som skal konverteres. 
  - `frmNMMPrimusMain.pdf` — Definisjoner av felt og VBA benyttet i Access-formen "frmNMMPrimusMain". 
  - `frmNMMPrimusKand_subform.pdf` — Definisjoner av felt og VBA benyttet i Access-formen "frmNMMPrimusKand subform". 
  - `frmNMMSkipsValg.pdf` — Definisjoner av felt og VBA benyttet i Access-formen "frmNMMSkipsValg". 
  - `Primus_Feltliste.xlsx` — Definisjoner av synlighet, editerbarhet, minimumbredder, kilder, konkatinering, o.l. av felt benyttet i `primus_main.php` og `primus_detalj.php`. 
  
Disse er **kun** til informasjon/inspirasjon. De gir en indikasjon på hvordan Access-databasen som konverteres fungerte og hva den inneholdt. Nettstedet kan avvike fra den detaljerte måten Access gjør tingene på, så lenge resultatet blir det samme.
Forøvrig: 

- Vedlagte filer har **alltid prioritet** over filer i prosjektets `Project Documents`. Vedlagte filer skal alltid leses/benyttes selv om de ikke referes til som 'vedlagt' i prompten.

- Filnavn skal være **unike** innen repoet, navngitt med **norsk språk**. Filmapper kan ha engelsk-språklige navn.

- Filene i `config/` skal **ikke** endres.

- Tabellen `nmmfoto` er den aktive tabellen som endres av brukeren hele tiden. Alle øvrige tabeller såkalte **parametertabeller**, som i enkelte tilfeller kan gis tillegg.

- Prosjektets css-fil er i `assets/app.css`.

## 3. Tillatte endringer
- Det er lov å endre filer, men unngå utilsiktet å endre/bryte eksisterende funksjonalitet.
- Eksisterende CSS-klasser i `assets/` kan endres. Nye klasser kan legges til i `assets/app.css`.
- Bruk `app.css` fremfor inline `<style>`.

## 4. Generelle prinsipper
### A. **Utseende/konverteringsbakgrunn**
- En konverteringsoppgave skal ikke startes før relevante deler av filene under pkt 2.B (over) er gjennomgått og forstått.
- Hensikten med hver modul skal avklares FØR utviklingen av den starter.
- VBA-logikk skal:
  - identifiseres
  - forklares
  - oversettes til PHP / JS / SQL
### B. **Koding**:  
- Foretrekk korrekthet, sporbarhet og paritet med Access/VBA.
- Ingen “gjetting” eller stilistisk modernisering uten krav.
- Unngå unødvendig kode og duplisering av klasser. 
- Fokus på funksjon, ikke visuell modernitet
- UI skal etterligne Access der relevant:
  - default-verdier
  - husk siste valg i session
  - umiddelbar respons der Access gjør det
- JS brukes kun når nødvendig
- Databasefelt som heter `Flag` eller `Flagg` er ment for Back-End manipulering, og skal ikke være del av noe UI. De behøver ikke å tas med i SQL 'SELECT'-statements/views hvis de ikke tjener en spesiell hensikt
#### PHP spesielt
	- `declare(strict_types=1);` skal alltid stå først
	- PHP 8.1+ syntaks
	- PDO benyttes konsekvent
	- `snake_case`
	- Ingen skjulte sideeffekter 
	- Bruk `h()` for output escaping.  
	- Bruk `basename()` på filnavn hvis dynamiske bildekilder.
- Når ferdig med kodingsoppgaven skal du:
- Kontrollere at løsningen tilfredsstiller kravene i chatten og denne AGENTS.md. Hvis noen av elementene i denne AGENTS.md er til hinder for gode løsninger, kan de fravikes hvis de føles av en klar melding om hva avviket er, begrunnelse for å gjøre avviket og hvorfort avviket ikke endrer kvaliteten/konsistensen i repoet.
- Fjerne fra repoet "temp"-filer som kun var for bruk ifm. det aktuelle kodingsarbeidet.

### C. **CSS**:  
  - Ikke bruk `!important` unntatt når helt nødvendig.  
  - Scope endringer (f.eks. `.card.centered-card`).størrelser og nettbrett der det er mulig. Smartphone skjermer ikke aktuelt å ta hensyn til.

### D. **Spesielt om kommuniksjon**:

Vær eksplisitt og etterprøvbar
- Still spørsmål **kun** hvis nødvendig informasjon mangler
- Hvis noe er uklart i dokumentasjonen: **Stopp og avklar!**

## 5. **Levering av endringer**:
- **Alle** endringer av kode **SKAL** være basert på faktisk lest kode av **SISTE** versjon av filen, **ALDRI** på gjettinger eller antakelser. Du skal verifisere at du har riktig fil for endringsforslaget.
- Måten **alle** endringsforslag leveres på **SKAL**, før de fremsettes, være kontrollert mot innholdet i dette.
- Ingen linjenummer skal inluderes i patch-koden.
- **Alle** endringer av kode **SKAL** enten ha:
  - patch med klartekst **linjenummer** som referanse, 
  - patch med foregående og etterfølgende tre linjert, eller 
  - gi full oppdatert versjon av filen som har endringen i seg.
- Endringsforslag som ikke har en av disse, vil **ikke** bli utført. 
- Ved manglende muligheter for å lese faktiske kode i filer, skal det bes om nedlasting av relevante filer **før** forslag til patcher leveres.
- Begreper som " .. som ser ut som noe tilsvarende .. ", "... typisk ligger i .." og liknende vil ikke bli akseptert ifm. patching.

## 6. Testplan
- Lokalt (XAMPP):  
  - Last siden(e) direkte og verifiser layout.  
  - Hard refresh med `CTRL+F5`.

- Nettlesere: Chrome.

- Standard skjermstørrelse er 1920 x 1080, men design for andre skjermstørrelser


