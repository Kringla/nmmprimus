# NMMPRIMUS – Project Instructions for ChatGPT

Dette dokumentet definerer hvordan ChatGPT skal arbeide i prosjektet **nmmprimus**.
Instruksjonene gjelder for alle samtaler i prosjektet og har forrang foran
generelle eller implisitte antagelser.

---

## 1. Prosjektavgrensning (KRITISK)

- Prosjektet er **utelukkende** `nmmprimus`
- **Ingen** kode, arkitektur, stil eller antagelser skal hentes fra:
  - `vedl_db`
  - andre tidligere prosjekter
  - generiske “moderne” maler uten eksplisitt forankring i dokumentasjonen
- Kun følgende kilder er autoritative:
  - Project Documents for nmmprimus
  - Filer eksplisitt vedlagt i chat
  - Eksisterende kode i nmmprimus-repoet slik den er levert

Hvis nødvendig informasjon mangler: **SI IFRA før arbeid starter.**

---

## 2. Arbeidsmodus

- Bruk grundig, eksplisitt resonnering før kode eller forslag genereres
- Foretrekk korrekthet, sporbarhet og paritet med Access/VBA
- Ingen “gjetting” eller stilistisk modernisering uten krav

---

## 3. Migreringsmål

- Nettbasert PHP/MySQL-front-end
- Funksjonell paritet med:
  - `frmNMMPrimusMain`, beskrevet i `frmNMMPrimusMain.pdf` i Project Documents for nmmprimus
  - `frmNMMPrimus`, inkl. subform `frmNMMPrimusKand subform`, beskrevet i henholdsvis `frmNMMPrimus.pdf` og `frmNMMPrimusKand_subform.pdf` i Project Documents for nmmprimus
  - `frmNMMSkipsValg`, beskrevet i `frmNMMSkipsValg.pdf` i Project Documents for nmmprimus

- VBA-logikk skal:
  - identifiseres
  - forklares
  - oversettes til PHP / JS / SQL

---

## 4. Kode- og filregler

### PHP
- `declare(strict_types=1);` skal alltid stå først
- PHP 8.1+ syntaks
- PDO benyttes konsekvent
- Ingen skjulte sideeffekter

### Filnavn
- Norske, beskrivende filnavn
- `snake_case`
- Nye filer foretrekkes fremfor å “redde” gamle feilnavn

### Includes
- Kun dokumenterte includes
- `layout_start.php` / `layout_slutt.php` brukes konsistent

---

## 5. Patch- og leveranseregler (AGENTS.md)

- Følg **AGENTS.md** strengt. Den ligger i Project Documents
- Ingen linjenummer i patcher
- Enten:
  - presis erstatningsblokk med angivelse av kode som skal erstattes inkludert 3 linjer før og etter, eller
  - komplett fil
- Ikke bland forklaring og kode

---

## 6. UI og interaksjon

- Fokus på funksjon, ikke visuell modernitet
- UI skal etterligne Access der relevant:
  - default-verdier
  - husk siste valg i session
  - umiddelbar respons der Access gjør det
- JS brukes kun når nødvendig

---

## 7. Kommunikasjonsregler

- Vær eksplisitt og etterprøvbar
- Still spørsmål **kun** hvis nødvendig informasjon mangler
- Hvis noe er uklart i dokumentasjonen: stopp og avklar

---

## 8. Endringer i instruksjonen

Dette dokumentet er levende.
Endringer skal:
- gjøres eksplisitt
- lagres i Project Documents
- gjelde fra neste chat
