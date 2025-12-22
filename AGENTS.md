# AGENTS.md

Prosjektets navn er **nmmprimus**.
Denne filen er den **operative kontrakten** for hvordan ChatGPT skal arbeide i repoet `nmmprimus`.
Instruksjonene gjelder for **alle samtaler** i prosjektet og har **absolutt forrang** foran alle andre dokumenter, inkludert Project Instructions.

---

## 1. Prosjektavgrensning (KRITISK)

* Prosjektet er **utelukkende** `nmmprimus`
* **Ingen** kode, arkitektur, stil, mønstre eller antagelser skal hentes fra:

  * andre repoer (inkl. `vedl_db`)
  * generiske “moderne” maler
* Kun følgende kilder er autoritative:

  * Project Documents for `nmmprimus`
  * Filer eksplisitt vedlagt i chat
  * Eksisterende kode i `nmmprimus`-repoet slik den faktisk foreligger

Hvis nødvendig informasjon mangler: **SI IFRA før arbeid starter.**

---

## 2. Forhold til Project Instructions (Primus_PI.md)

* `Primus_PI.md` definerer **hva** systemet er og skal gjøre
* **Denne filen (AGENTS.md)** definerer **hvordan** arbeid og leveranser skal utføres
* Ved konflikt mellom dokumentene:

  * **AGENTS.md vinner alltid**

---

## 3. Arbeidsprinsipper

* Ingen gjetting, antagelser eller implisitt utfylling
* Foretrekk korrekthet, sporbarhet og paritet med Access/VBA
* VBA-logikk skal:

  * identifiseres
  * forklares
  * oversettes til PHP / JS / SQL

---

## 4. Kode- og filregler

### PHP

* `declare(strict_types=1);` skal alltid stå først
* PHP 8.1+ syntaks
* PDO benyttes konsekvent
* Ingen skjulte sideeffekter
* Bruk `h()` for output-escaping

### Filnavn

* Norske, beskrivende filnavn
* `snake_case`
* Filnavn skal være **unike** i repoet

### Struktur

* Kun dokumenterte includes
* `layout_start.php` / `layout_slutt.php` brukes konsistent
* Filene i `config/` skal **ikke** endres

---

## 5. Patch- og leveranseregler (KRITISK)

* **Ingen linjenummer** i kodeblokker
* Alle endringer **SKAL** leveres som enten:

  * presis patch med **3 linjer før og etter**, eller
  * komplett oppdatert fil
* Ikke bland forklaring og kode
* Alle patcher skal være basert på **faktisk lest kode av siste versjon**

Hvis korrekt fil ikke er tilgjengelig: **be om nedlasting før patch foreslås**

---

## 6. UI og interaksjon

* Fokus på funksjon, ikke visuell modernisering
* UI skal etterligne Access der relevant:

  * default-verdier
  * husk siste valg i session
  * umiddelbar respons der Access gjør det
* JS brukes kun når nødvendig

---

## 7. Kommunikasjon

* Vær eksplisitt og etterprøvbar
* Still spørsmål **kun** hvis nødvendig informasjon mangler
* Hvis dokumentasjon er uklar: **stopp og avklar**

---

## 8. Endringer i AGENTS.md

* Endringer skal gjøres eksplisitt
* Gjelder først fra **neste chat**
