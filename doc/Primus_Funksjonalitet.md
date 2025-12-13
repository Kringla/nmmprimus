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
2. Under comboboksen vises en tabell med alle radene i tbF der de 8 første karakterene i feltet `Bilde_fil`er lik combofeltet `Serie`, i avtagende `Bilde_Fil`rekkefølge. Tabellen skal som minimum vise hele feltet `Bilde_Fil`, `Motivbeskr` og `Transferred`.
3. Ved å dobbel-klikke (eller på annen måte) i en rad, skal detaljene av raden vises i et detaljskjema.
4. Det skal være mulig å lage en ny rad i tbF i samme `Serie` som angitt i feltet, med `SerNr` som neste ledige. 
5. I `AccessObjects.pdf` er formen beskrevet under navnet `frmNMMPrimusMain`.

## Side med detaljer
1. I venstre del skal det være en søkbar tabell som viser utdrag fra tabellen`nmm_skip`('tbS'). Tabellen skal, som et minimum, vise kolonnene `NMM_ID`,`FTY`, `FNA`, `BYG`, `RGH`, `Nasjon` hentet fra tabellen `country` basert på feltet `NID` og `KAL`. Søket skal være fritekst innen feltet `FNA`, ikke case sensitivt. I `AccessObjects.pdf` er formen beskrevet under navnet `frmNMMPrimusKand subform`.
2. Klikking på radene i denne tabellen oppdaterer innholdet i høyre del, som skal inneholde verdiene av en rad i tbF. I `AccessObjects.pdf` er formen beskrevet under navnet `frmNMMPrimus`.
3. I/fra denne siden skal det i hovedsak være mulig å:
    -   Rette eksisterende verdier.
    -   Lage helt ny rad i samme `Bilde_Fil`-serie (de første 8 karakterene)
    -   Kopiere raden til ny rad med samme valgt rad i `nmm_skip`, kun med endret `SerNr`
