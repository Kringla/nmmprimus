# Dokumentasjons-opprydding 2026-01-03

Dette dokumentet beskriver oppryddingen av dokumentasjonen i NMMPrimus-prosjektet.

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

---

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
