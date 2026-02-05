# Dokumentasjons-opprydding 2026-01-03

Dette dokumentet beskriver oppryddingen av dokumentasjonen i NMMPrimus-prosjektet.

---

## Siste oppdatering: 2026-01-22

### Lagt til søkefunksjonalitet for skipsnavn

**Funksjonalitet:** Ny søkefunksjon i landingssiden (primus_main.php) for å søke etter foto basert på skipsnavn.

**Egenskaper:**
- Krever minimum 3 tegn for å søke
- Valg mellom å søke i kun valgt serie eller alle serier
- JavaScript-validering for min 3 tegn
- Søkeresultat vises med antall treff
- Paging fungerer med søkeresultater
- "Nullstill"-knapp for å gå tilbake til normal visning

**Implementasjon:**
1. Nye funksjoner i [primus_modell.php](../modules/primus/primus_modell.php):
   - `primus_sok_foto_etter_skipsnavn()` - Søk med JOIN til nmm_skip
   - `primus_sok_foto_etter_skipsnavn_antall()` - Tell søketreff
2. Oppdatert [primus_main.php](../modules/primus/primus_main.php):
   - Søkelogikk med validering
   - Søkefelt i toolbar med checkbox for "Alle serier"
   - Paging med søkeparametere
   - Søkeresultat-visning i header

**Bruk:**
1. Skriv inn minst 3 tegn av skipsnavn
2. Velg "Alle serier" for å søke på tvers av serier, eller la være for å søke kun i valgt serie
3. Klikk "Søk"
4. Klikk "Nullstill" for å gå tilbake til normal visning

---

### Fikset URL_Bane-generering

**Problem:** URL_Bane-feltet manglet cURL-prefikset (`M:\NMM\Bibliotek\Foto\NSM.TUSEN-SERIE\`) som finnes i Access-versjonen.

**Løsning:**
1. Lagt til `FOTO_URL_PREFIX`-konstant i [config/constants.php](../config/constants.php)
2. Oppdatert [foto_modell.php:50](../modules/foto/foto_modell.php#L50) til å bruke konstanten
3. Oppdatert [primus_detalj.php:367](../modules/primus/primus_detalj.php#L367) til å bruke konstanten

**Resultat:** URL_Bane genereres nå som: `M:\NMM\Bibliotek\Foto\NSM.TUSEN-SERIE\NSM.2001 -001-999 Damp og Motor`

---

## Mål

1. Fjerne overlapping mellom dokumenter
2. Rendyrke CLAUDE.md og AGENTS.md
3. Forenkle doc/-strukturen
4. Klargjøre ansvarsområder

---

## Gjennomførte endringer

### 1. AGENTS.md – Rendyrket til operativt kontrakt

**Før:** Blanding av prosess, innhold og tekniske detaljer
**Nå:** Kun operative regler (hvordan arbeid utføres)

**Innhold:**
- Autoritet og dokumenthierarki
- Prosjektavgrensning
- Arbeidsprinsipper
- Koderegler (PHP, filnavn, sikkerhet)
- Leveranseregler (format, prosess)
- UI-prinsipper
- Kommunikasjon

**Slettet:** Tekniske detaljer (flyttet til CLAUDE.md)



### 2. CLAUDE.md – Konsolidert teknisk referanse

**Før:** God oversikt, men manglet detaljer fra andre dokumenter
**Nå:** Komplett teknisk referanse

**Nytt innhold (fra andre dokumenter):**
- Utvidet prosjektoversikt med formål og scope
- Detaljert database-skjema (fra Primus_Schema.md)
- Funksjonsområder (landingsside, detaljvisning, fartøyvalg, admin)
- Access-til-web mapping (skjemaer, logikk, atferd)
- Funksjonell beskrivelse (fra Primus_Funksjonalitet.md)

**Forbedret:**
- Klarere struktur
- Bedre kodeeksempler
- Utvidet feilsøking
- Oppdaterte kryssreferanser

---

### 3. doc/Primus_Funksjonalitet.md – Forenklet funksjonell beskrivelse

**Før:** 91 linjer med overlapp med CLAUDE.md og Primus_PI.md
**Nå:** 147 linjer, men mye klarere og uten duplisering

**Endringer:**
- Fjernet tekniske implementasjonsdetaljer (flyttet til CLAUDE.md)
- Fokusert på funksjonell atferd (hva systemet skal gjøre)
- Klarere struktur med Access-ekvivalenter
- Tydelig skille mellom H1/H2-modus og iCh-modus

---

### 4. doc/SETUP_GUIDE.md – Forenklet oppsettguide

**Før:** Mye "Utført!"-notater og historisk informasjon
**Nå:** Ren oppsettguide

**Endringer:**
- Fjernet "Utført!"-notater
- Lagt til konkrete kodeeksempler
- Utvidet feilsøking
- Bedre struktur for lokal vs. produksjon
- Lagt til sikkerhetsseksjon

---

### 5. Slettede dokumenter

#### doc/Primus_PI.md
**Årsak:** Overlapping med CLAUDE.md
**Innhold flyttet til:** CLAUDE.md (seksjon 1: Prosjektoversikt)

#### doc/Primus_Migrasjonsplan.md
**Årsak:** Historisk dokument, ikke lenger relevant
**Status:** Fase-planen er fullført, dokumentet er kun historisk

#### doc/Primus_RD.md
**Årsak:** Tom fil
**Innhold:** Kun overskrift og ingen substans

---

### 6. Nye dokumenter

#### README.md
**Formål:** Rask oversikt over prosjektet
**Innhold:**
- Kort beskrivelse
- Dokumentasjonshierarki
- Rask start
- Stack-oversikt

---

## Ny dokumentasjonsstruktur

### Rot-nivå (operative)
```
AGENTS.md        - Operativt kontrakt (hvordan)
CLAUDE.md        - Teknisk referanse (hva)
README.md        - Prosjektoversikt
```

### doc/ (spesialisert)
```
Primus_Funksjonalitet.md    - Funksjonell beskrivelse
Primus_Schema.md            - Database-skjema (SQL)
Primus_Filstruktur.md       - Filstruktur (detaljert)
SETUP_GUIDE.md              - Installasjons- og oppsettguide
DOCUMENTATION_CHANGELOG.md  - Dette dokumentet
```

### Beholdt uendret
```
Primus_Schema.md       - SQL-skjema (god referanse)
Primus_Filstruktur.md  - Detaljert filstruktur (god referanse)
```

---

## Dokumenthierarki

Ved konflikt gjelder denne rekkefølgen:

1. **AGENTS.md** – Operativt kontrakt (HØYESTE AUTORITET)
2. **CLAUDE.md** – Teknisk referanse
3. **doc/Primus_Funksjonalitet.md** – Funksjonell beskrivelse
4. **doc/*.md** – Spesifikk dokumentasjon

---

## Kryssreferanser

Alle dokumenter har nå oppdaterte kryssreferanser:

- AGENTS.md → CLAUDE.md
- CLAUDE.md → AGENTS.md, doc/*.md
- doc/Primus_Funksjonalitet.md → CLAUDE.md
- doc/SETUP_GUIDE.md → CLAUDE.md, Primus_Funksjonalitet.md, Primus_Schema.md
- README.md → AGENTS.md, CLAUDE.md, doc/*.md

---

## Statistikk

### Før opprydding
- 7 dokumenter i doc/
- Betydelig overlapping
- Uklar ansvarsfordeling
- Mye historisk innhold

### Etter opprydding
- 5 dokumenter i doc/ (slettet 3, opprettet 1)
- Minimal overlapping
- Klar ansvarsfordeling (AGENTS.md = hvordan, CLAUDE.md = hva)
- Kun relevant innhold

---

## Vedlikehold fremover

### Når skal hvert dokument oppdateres?

**AGENTS.md:**
- Nye arbeidsprinsipper
- Endrede leveranseregler
- Nye kodestandarder

**CLAUDE.md:**
- Nye moduler/funksjoner
- Endret arkitektur
- Nye database-tabeller
- Nye kjente problemer

**doc/Primus_Funksjonalitet.md:**
- Endret funksjonalitet
- Nye funksjonsområder
- Endret Access-mapping

**doc/Primus_Schema.md:**
- Database-skjemaendringer
- Nye tabeller
- Endrede relasjoner

**doc/SETUP_GUIDE.md:**
- Nye systemkrav
- Endret oppsettsprosess
- Nye vanlige problemer

---

**Utført av:** Claude Code
**Dato:** 2026-01-03
