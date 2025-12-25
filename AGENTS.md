# AGENTS.md

**Operative kontrakt** for arbeid i `nmmprimus`. Har **absolutt forrang** over alle andre dokumenter.

---

## 1. Prosjektavgrensning

* Prosjektet er **kun** `nmmprimus`
* **Ingen** kode/mønstre fra andre repoer eller generiske maler
* Autoritative kilder:
  * Dokumenter i `nmmprimus`
  * Vedlagte filer
  * Eksisterende kode

**Mangler info? Si fra før arbeid starter.**

---

## 2. Dokumenthierarki

1. **AGENTS.md** (denne filen) – hvordan arbeid utføres
2. **CLAUDE.md** – omfattende referanse
3. **Primus_PI.md** – hva systemet skal gjøre
4. **doc/*.md** – spesifikk dokumentasjon

Ved konflikt: **AGENTS.md vinner alltid**

---

## 3. Arbeidsprinsipper

* Ingen gjetting eller antagelser
* Foretrekk korrekthet over modernisering
* VBA-logikk: identifiser → forklar → oversett

---

## 4. Koderegler

**PHP:**
* `declare(strict_types=1);` først
* PHP 8.1+ syntaks
* PDO konsekvent
* `h()` for output-escaping
* Prepared statements alltid

**Filnavn:**
* Norske, beskrivende navn
* `snake_case`
* Unike i hele repoet

**Struktur:**
* Dokumenterte includes
* `layout_start.php` / `layout_slutt.php` konsistent
* `config/` skal **ikke** endres

---

## 5. Leveranseregler (KRITISK)

* **Ingen linjenummer** i kodeblokker
* Lever som:
  * Presis patch med **3 linjer kontekst**, ELLER
  * Komplett fil
* Basert på **faktisk lest kode**
* Skill forklaring fra kode

**Mangler fil? Be om nedlasting først.**

---

## 6. UI-prinsipper

* Funksjon over utseende
* Etterlign Access:
  * Standardverdier
  * Husk siste valg
  * Umiddelbar respons
* Minimal JavaScript

---

## 7. Kommunikasjon

* Eksplisitt og etterprøvbar
* Spør kun ved manglende info
* Uklar dokumentasjon? **Stopp og avklar**

---

**Endringer i AGENTS.md gjelder fra neste chat.**
