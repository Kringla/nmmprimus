# NMMPrimus - Funksjonalitetsbeskrivelse.md

## Mål
Målet er å skape riktig funksjonalitet i nettstedet **nmmprimus**, basert på eksisterende Access-løsning.

## Utgangspunkt
Utgangspunktet er beskrevet i `AccessObjects.pdf` som er vedlagt som en del av Prosjektdokumentasjonen.

## Overordnet oppførsel
Å bygge tabellen`nmmfoto` er hovedfokus for nettstedet. Den kalles i dette dokumentet for 'tbF'.
Byggingen består i hovedsak i lage en rad med kobling mellom en eller flere rader i tabellen `nmm_skip` (kalt 'tbS'), parametertabeller og manuelt input til tbF.

## Detaljer vedrørende oppførsel
Jeg ønsker at **nmmprimus** skal ha funksjonalitet og layout som følger: 

## Landingssiden
1. Landingssiden skal ha en overskrift og vise feltet `Serie` fra tabellen `bildeserie` (kalt 'xB') som innholder verdien av den raden i tabellen som brukeren benyttet siste gang brukeren var inne. Første gang brukeren er inne vises første forekomst i xB. Feltet er en combobox.
Verdien som finnes i feltet `Serie`s første 8 tegn brukes som et filter i noen VBA funksjoner/prosedyrer.
2. Under comboboksen vises en tabell med alle radene i tbF der de 8 første karakterene i feltet `Bilde_fil`er lik combofeltet `Serie`, i avtagende `Bilde_Fil`rekkefølge. Tabellen skal som minimum vise hele feltet `Bilde_Fil`, `Motivbeskr` og `Transferred`. En side skal max vise 25 rader, med mulighet til navigering mellom sidene.
3. I `AccessObjects.pdf` er landingssiden beskrevet under navnet `frmNMMPrimusMain`.
### Mulige hendelser
-  H1: Ved å dobbel-klikke (eller på annen måre) i en rad, skal detaljene av raden det er klikket på vises i et detaljskjema.  Verdien av radene felt `Foto_ID` er entydig nøkkel til å åpne detaljskjemaet.
-  H2: Det skal være mulig å lage en ny rad i tbF i samme `Serie` som angitt i feltet, med `SerNr` som neste ledige. Det skal gjøres ved å trykke på en knapp lokalisert ved siden av `Serie`-feltet.

## Side med detaljer
1. I venstre del skal det være et panel med en søkbar tabell som viser utdrag fra tabellen`nmm_skip`('tbS'). Tabellen skal, som et minimum, vise kolonnene `NMM_ID`,`FTY`, `FNA`, `BYG`, `RGH`, `Nasjon` hentet fra tabellen `country` basert på feltet `NID` og `KAL`. 
- Søket skal være fritekst innen feltet `FNA`, ikke case sensitivt. Søketeksten settes inn i et eget felt over panelet. Minst 3 karakterer kreves for å iverksette søket.
- Resultat tabellen fra søskal max vise 20 rader,  med muligheter for scrolling. Antall rader som tilfredsstiller søketeksten skal angis i eget felt over  tabellen..
I `AccessObjects.pdf` er venstre panel i formen beskrevet under navnet `frmNMMPrimusKand subform`
2. Høyre panel inneholder alle feltene i tbF; med unntak av `Flag` og `Transferred`. Feltene  `Foto_ID` og `NMM_ID` ikke synlige.
-  I `AccessObjects.pdf it` er resten av formen beskrevet under navnet `frmNMMPrimus` (som også viser `frmNMMPrimusKand subform`)..
### Mulige hendelser, konsekvenser av valg i landingssiden
1. Ved hendelse H1 i landingssiden :
- Venstre panel vises ikke. 
- Alle felt er fylt med alle detaljverdiene fra den raden i tabellen `nmmfoto`som raden i landingssiden er basert på, dvs raden med landingssidens `NMM_ID`.
- Det er kun disse verdiene av feltene fra raden i `nmmfoto` som er editerbare.
2. Ved hendelse H2 i landingssiden:
-  Venstre panel vises. Alle felt er tomme i høyre panel, bortsett fra `SerNr` og `Bilde_Fil`. `SerNr` er generert i landingssiden. `Bilde_Fil` er konkatinering av `Serie` på landingssiden, "-" og `SerNr`.
-  Ved å klikke på en av radene i tabellen i venstre panel, vil noen av feltene i høyre del,  som skal Inneholde verdiene av en rad i tbF, bli fylt.  Feltene `MotivBeskr` og `FTO` får sammen med fartøy navn og type; verdier fra den valgte raden i det venstre panelet. Flere får sitt innhold fra tabeller basert på verdien av `NMM_ID` som kommer fra den valgte raden i venstre panel. Disse er:
	-	`nmmxemne`
	-	`nmmxtype`
	-	`nmmxou`
	-	`nmmxudk`
	-	`nmmxhendelse`
	-	`nmmtema`
	-	`nmm_skip`
-   Det er kun verdien av feltene som lagres i `nmmfoto` som er editerbare. 
-	Det skal være en radiobølger som styrer aksesjon- og fotografi detaljer.  Logikken er beskrevet i VBA  delen av `frmNMMPrimus.pdf`. Virkningen av valg skal være synliggjort, f.eks. med røde og grønne rammer.
-	Sidens layout kan avvike fra `frmNMMPrimus`, og kan benytte 'tabs'.
3. På/fra denne detaljsiden skal det i hovedsak være mulig å:
    -   Rette eksisterende verdier.
	-	Det skal være mulig å endre feltet `SerNr` til et hvilket som helst tall mellom 1 og 999 som er ledig i serien. Feltet `Bilde!_Fil` skal oppdateres automatisk.
    -   Lage helt ny forekomst/rad i `nmmfoto` i samme `Bilde_Fil`-serie (de første 8 karakterene) med `SerNr`-verdi som neste ledige i serien.
    -   Kopiere raden til ny rad `nmmfoto` med samme feltverdier, kun med endret `SerNr` som neste tall mellom 1 og 999 som er ledig i serien. Feltet `Bilde!_Fil` skal oppdateres automatisk.
    
   
