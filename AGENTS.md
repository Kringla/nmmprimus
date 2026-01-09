# AGENTS.md

**Operativt kontrakt** for arbeid i `nmmprimus`. Har **absolutt forrang** over alle andre dokumenter.

---

## 1. Autoritet

* **AGENTS.md vinner alltid** ved konflikt med andre dokumenter
* Endringer i AGENTS.md gjelder fra neste chat
* Dette dokumentet definerer **hvordan** arbeid utføres
* [CLAUDE.md](CLAUDE.md) definerer **hva** som bygges

---

## 2. Prosjektavgrensning

* Prosjektet er **kun** `nmmprimus`
* **Ingen** kode/mønstre fra andre repoer eller generiske maler
* Autoritative kilder:
  * Eksisterende kode i `nmmprimus`
  * Dokumenter i `nmmprimus` (CLAUDE.md, doc/*.md)
  * Filer eksplisitt vedlagt i chat

**Mangler info? Si fra før arbeid starter.**

---

## 3. Arbeidsprinsipper

* **Ingen gjetting eller antagelser**
* Foretrekk **korrekthet over modernisering**
* VBA-logikk: identifiser → forklar → oversett
* Funksjonalitet og korrekthet > visuell modernisering
* Access-paritet er viktigere enn forenkling

---

## 4. Koderegler

### PHP

* `declare(strict_types=1);` først i alle filer
* PHP 8.1+ syntaks
* PDO konsekvent (prepared statements, navngitte parametere)
* `h()` for output-escaping
* CSRF-validering på alle POST-operasjoner
* `require_login()` eller `require_admin()` tidlig

### Filnavn og struktur

* Norske, beskrivende navn
* `snake_case`
* Unike i hele repoet
* Dokumenterte includes (kommentarer)
* `layout_start.php` / `layout_slutt.php` konsistent
* **`config/` skal ALDRI endres**

### Sikkerhet

* Prepared statements alltid
* Output escaping med `h()`
* CSRF på POST
* Ingen rå SQL-strenger
* Valider input ved system-grenser

---

## 5. Leveranseregler (KRITISK)

### Format

* **Ingen linjenummer** i kodeblokker
* Lever som:
  * **Presis patch** med 3 linjer kontekst, ELLER
  * **Komplett fil**
* Basert på **faktisk lest kode**
* Skill forklaring fra kode

### Prosess

* Les fil før endring (alltid)
* Verifiser eksisterende mønstre
* **Mangler fil? Be om nedlasting først**
* Aldri gjett filinnhold

---

## 6. UI-prinsipper

* Funksjon over utseende
* Etterlign Access:
  * Standardverdier
  * Husk siste valg (session/database)
  * Umiddelbar respons
* Minimal JavaScript (kun når nødvendig)
* Ingen inline CSS (bruk assets/app.css)

---

## 7. Kommunikasjon

* Eksplisitt og etterprøvbar
* Spør kun ved manglende info
* Uklar dokumentasjon? **Stopp og avklar**
* Vis hva du gjør, ikke bare resultatet
* Skill analyse fra implementering

---

## 8. Dokumenthierarki

Ved konflikt gjelder denne rekkefølgen:

1. **AGENTS.md** (denne filen) – hvordan arbeid utføres
2. **CLAUDE.md** – teknisk referanse
3. **doc/Primus_Funksjonalitet.md** – funksjonell beskrivelse
4. **doc/*.md** – spesifikk dokumentasjon

---

**Sist oppdatert:** 2026-01-03
