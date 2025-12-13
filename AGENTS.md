# AGENTS.md

Prosjektets navn er **nmmprimus**
Denne filen gir Codex veiledning for arbeid i dette repoet, **nmmprimus**.

## Rammer og referanser

- Prosjektets styrende filer: 
  - `Primus _Migreringsplan.md` — Prosjektets gjennomføringsplan. Når laget, ligger i prosjektets `Project Documents`
  - `Primus _Views.md` — Prosjektets Views. Når laget, ligger i prosjektets `Project Documents` (kan være mangelfull)
  - `Primus _Schema.md` — Prosjektets databasens Schema. Ligger i prosjektets `Project Documents`
  - `Primus _Filstruktur.md` — Prosjektets fil-struktur. Når laget, ligger i prosjektets `Project Documents`
  
  Disse skal **alltid** leses **før** utvikling/endring av ny kode.

- Prosjektet har i tillegg to filer til informasjon:
  - `AccessObjects.pdf` — Alle data om komponentene i Access-databasen som skal konverteres. Ligger i prosjektets `Project Documents`
  - `Primus _Forms_Map.md` — Mapping av forms til html. Når laget, ligger i prosjektets `Project Documents`

  Disse er **kun** til informasjon/inspirasjon. De gir en indikasjon på hvordan Access-databasen som konverteres fungerte og hva den inneholdt.

- I tillegg finnes en fil-dump av Access-databasens tables, queries, forms, reports og modules som det kan gis adgang til ved behov.

- Bruk alltid **nyeste versjon** dersom flere versjoner finnes (`v*` = høyest nummer).

- Vedlagte filer har **alltid prioritet** over filer i prosjektets `Project Documents`.

- Filnavn skal være **unike** innen repoet, navngitt med **norsk språk**. Filmapper kan ha engelsk-språklige navn.

- Vedlagte filer skal alltid leses/benyttes selv om de ikke referes til som 'vedlagt' i prompten.
- Filene i `config/` skal **ikke** endres.
- Tabellen `nmmfoto` er den aktive tabellen som endres av brukeren hele tiden. Alle øvrige tabeller såkalte **parametertabeller**, som i enkelte tilfeller kan gis tillegg.
- Prosjektets css-fil er, når laget, i `assets/app.css`.

## Tillatte endringer
- Det er lov å endre filer, men unngå utilsiktet å endre/bryte eksisterende funksjonalitet.
- Eksisterende CSS-klasser i `assets/` kan endres. Nye klasser kan legges til i `assets/app.css`.
- Bruk `app.css` fremfor inline `<style>`.

## Generelle prinsipper

- **Utseende/konverteringsbakgrunn**
  - En konverteringsoppgave skal ikke startes før relevante deler av  `AccessObjects.pdf` er gjennomgått og forstått.
  - Hensikten med hver modul skal avklares FØR utviklingen av den starter.
  
- **Kodekvalitet**:  
  - Fjern unødvendig kode og duplisering av klasser.  
  - Bruk `h()` for output escaping.  
  - Bruk `basename()` på filnavn hvis dynamiske bildekilder.
  - Når ferdig med kodingsoppgaven skal du:
	-	Kontrollere at løsningen tilfredsstiller kravene i chatten og denne AGENTS.md. Hvis noen av elementene i denne AGENTS.md er til hinder for gode løsninger, kan de fravikes hvis de føles av en klar melding om hva avviket er, begrunnelse for å gjøre avviket og hvorfort avviket ikke endrer kvaliteten/konsistensen i repoet.
	- Fjerne fra repoet "temp"-filer som kun var for bruk ifm. det aktuelle kodingsarbeidet.

- **CSS**:  
  - Ikke bruk `!important` unntatt når helt nødvendig.  
  - Scope endringer (f.eks. `.card.centered-card`).størrelser og nettbrett der det er mulig. Smartphone skjermer ikke aktuelt å ta hensyn til.

- **Spesielle forhold**:
  - Databasefelt som heter `Flag` eller `Flagg` er ment for Back-End manipulering, og skal ikke være del av noe UI. De behøver ikke å tas med i SQL 'SELECT'-statements/views hvis de ikke tjener en spesiell hensikt.
- All visning av `KompNr` i UI skal bruke `komp_visning()` for å fjerne leading "<root>." Dette gjelder `komponenttre.php`, `komponent-liste.php`, jobb-lister, maling-lister og alle dropdowns som viser komponentnumre.

- **Levering av endringer**:
- **Alle** endringer av kode **SKAL** være basert på faktisk lest kode, **ALDRI** på gjettinger eller antakelser.
- **Alle** endringer av kode **SKAL** enten ha:
  - patch med klartekst **linjenummer** som referanse, 
  - patch med foregående og etterfølgende tre linjert, eller 
  - gi full oppdatert versjon av filen som har endringen i seg.
- Ved manglende muligheter for å lese faktiske kode i filer, skal det bes om nedlasting av relevante filer **før** forslag til patcher leveres.
- Begreper som " .. som ser ut som noe tilsvarende .. ", "... typisk ligger i .." og liknende vil ikke bli akseptert ifm. patching.

## Testplan

- Lokalt (XAMPP):  
  - Last siden(e) direkte og verifiser layout.  
  - Hard refresh med `CTRL+F5`.

- Nettlesere: Chrome.

- Standard skjermstørrelse er 1920 x 1080, men design for andre PC 


## Instruksjoner for samarbeid med ChatGPT
- Last opp denne filen i starten av en økt. 
- Be ChatGPT validere leveranser mot reglene i denne filen.
- ChatGPT skal kun foreslå endringer som er i tråd med disse reglene.

