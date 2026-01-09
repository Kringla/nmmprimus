# Teknisk gjeld (Tasks 17-24) – Status og anbefalinger

Oversikt over LAV prioritet oppgaver fra [ROADMAP.md](ROADMAP.md).

**Status:** Ikke implementert – Dokumentert for fremtidig arbeid

---

## 📋 Task 17: Automatiserte tester

**Problem:** Ingen tester eksisterer

**Anbefaling:**
- Sett opp PHPUnit for enhetstester
- Prioriter tester for kritiske funksjoner:
  - `foto_lagre()` med iCh-hvitlisting
  - `primus_hent_kandidat_felter()`
  - Auth-funksjoner (`login()`, `require_admin()`)
  - CSRF-validering

**Eksempel teststruktur:**
```
tests/
├── Unit/
│   ├── AuthTest.php
│   ├── FotoModellTest.php
│   └── PrimusModellTest.php
├── Integration/
│   └── LoginFlowTest.php
└── bootstrap.php
```

**Estimat:** 16+ timer
**Status:** ⚠️ Ikke prioritert i denne omgangen

---

## 📋 Task 18: Implementer caching

**Problem:** Ingen caching-lag, static data hentes fra DB hver gang

**Potensielle områder:**
- `bildeserie` tabell (endres sjelden)
- User preferences (last_serie)
- Session data

**Løsning:**
```php
// Enkel PHP array cache
function get_cached_bildeserier(): array {
    static $cache = null;
    if ($cache === null) {
        $cache = db()->query("SELECT SerID, Serie FROM bildeserie ORDER BY Serie")->fetchAll();
    }
    return $cache;
}
```

**Estimat:** 8 timer
**Status:** ⚠️ Ytelse er OK uten caching p.t.

---

## 📋 Task 19: Optimaliser database-queries

**Problem:**
- N+1 queries i `primus_hent_kandidat_felter()`
- Ineffektiv `LEFT(Bilde_Fil, 8)` bruk
- Manglende indekser

**Anbefalinger:**

### 1. Kombiner nmmxou/nmmxudk queries
```php
// Før: 2 queries
foreach (['nmmxou', 'nmmxudk'] as $tab) {
    $stmt = $db->prepare("SELECT ... FROM {$tab} ...");
}

// Etter: 1 query med UNION
$stmt = $db->prepare("
    SELECT System, ID, kode, Klassifikasjon, UUID FROM nmmxou WHERE NMM_ID = :id
    UNION ALL
    SELECT System, ID, kode, Klassifikasjon, UUID FROM nmmxudk WHERE NMM_ID = :id
    ORDER BY System, ID
");
```

### 2. Erstatt LEFT() med LIKE
```sql
-- Før: Kan ikke bruke indeks
WHERE LEFT(Bilde_Fil, 8) = :serie

-- Etter: Kan bruke indeks
WHERE Bilde_Fil LIKE CONCAT(:serie, '%')
```

### 3. Nødvendige indekser
```sql
-- nmmfoto tabell
CREATE INDEX idx_bilde_fil_prefix ON nmmfoto(Bilde_Fil(8));
CREATE INDEX idx_transferred ON nmmfoto(Transferred);
CREATE INDEX idx_nmm_id ON nmmfoto(NMM_ID);

-- nmm_skip tabell
CREATE INDEX idx_fna ON nmm_skip(FNA);

-- nmmxtype, nmmxemne, nmmxou, nmmxudk
CREATE INDEX idx_nmm_id ON nmmxtype(NMM_ID);
CREATE INDEX idx_nmm_id ON nmmxemne(NMM_ID);
CREATE INDEX idx_nmm_id ON nmmxou(NMM_ID);
CREATE INDEX idx_nmm_id ON nmmxudk(NMM_ID);
```

**Estimat:** 4 timer
**Status:** ⚠️ Kan implementeres når ytelse blir problem

---

## 📋 Task 20: Forbedre tilgjengelighet

**Problem:** Mangler ARIA-labels, skip links, keyboard navigation

**Anbefalinger:**

### 1. Skip to content link
```html
<!-- layout_start.php -->
<a href="#main-content" class="skip-link">Hopp til hovedinnhold</a>
```

### 2. ARIA labels
```html
<!-- primus_main.php -->
<button onclick="openExportDialog()" aria-label="Åpne eksport-dialog">
    Eksporter
</button>

<!-- primus_detalj.php -->
<div class="primus-tabbar" role="tablist">
    <div class="primus-tab" role="tab" aria-selected="true">Hendelse</div>
</div>
```

### 3. Fargeblind-vennlig iCh indikatorer
```css
/* Legg til ikoner ved siden av farger */
input[data-foto-state="aktiv"]::before {
    content: "✓ ";
}
input[data-foto-state="inaktiv"]::before {
    content: "✗ ";
}
```

**Estimat:** 6 timer
**Status:** ⚠️ Grunnleggende tilgjengelighet OK, forbedringer kan vente

---

## 📋 Task 21: Ekstraher repetert kode

**Problem:** String concatenation repeteres i loops

**Eksempel på duplisering:**
```php
// primus_modell.php
$motivType = $rows
    ? implode("\n", array_map(
        fn($r) => "{$r['ID']};{$r['MotivType']};{$r['UUID']}",
        $rows
    ))
    : '-';

$motivEmne = $rows
    ? implode("\n", array_map(
        fn($r) => "{$r['Id_nr']};{$r['MotivOrd']};{$r['UUID']}",
        $rows
    ))
    : '-';
```

**Løsning:**
```php
// includes/functions.php
function format_lookup_rows(array $rows, array $keys): string {
    if (empty($rows)) {
        return '-';
    }
    return implode("\n", array_map(
        fn($r) => implode(';', array_map(fn($k) => $r[$k] ?? '', $keys)),
        $rows
    ));
}

// Bruk:
$motivType = format_lookup_rows($rows, ['ID', 'MotivType', 'UUID']);
$motivEmne = format_lookup_rows($rows, ['Id_nr', 'MotivOrd', 'UUID']);
```

**Estimat:** 2 timer
**Status:** ⚠️ Lav prioritet, eksisterende kode fungerer

---

## 📋 Task 22: Legg til PHPDoc-kommentarer

**Problem:** Manglende function-level dokumentasjon

**Eksempel:**
```php
/**
 * Henter alle foto for en gitt serie med paginering.
 *
 * @param string $serie Bildeserie (8 tegn, f.eks. "XXXXXXXX")
 * @param int $limit Antall resultater per side (default: 20)
 * @param int $offset Start-offset for paginering (default: 0)
 * @return array Array med foto-objekter (Foto_ID, Bilde_Fil, MotivBeskr, Transferred)
 * @throws PDOException Hvis database-query feiler
 */
function primus_hent_foto_for_serie(string $serie, int $limit = 20, int $offset = 0): array
{
    // ...
}
```

**Estimat:** 8 timer
**Status:** ⚠️ Type hints dekker mye, PHPDoc kan legges til gradvis

---

## 📋 Task 23: Implementer transaksjons-håndtering

**Problem:**
- Uklare transaksjons-grenser
- Mystisk `if ($db->inTransaction())` check i primus_detalj.php:272

**Anbefalinger:**

### 1. Fjern transaction check
```php
// primus_detalj.php:272 - FJERN DETTE
if ($db->inTransaction()) {
    $db->rollBack();
}
```

### 2. Definer klare transaction boundaries
```php
// foto_kopier() - Multi-step operasjon trenger transaction
function foto_kopier(PDO $db, int $fotoId): int {
    try {
        $db->beginTransaction();

        $foto = foto_hent_en($db, $fotoId);
        // ... kopier logikk ...
        $stmt->execute($values);
        $nyId = (int)$db->lastInsertId();

        $db->commit();
        return $nyId;
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}
```

**Estimat:** 3 timer
**Status:** ⚠️ Eksisterende kode fungerer, lav risiko

---

## 📋 Task 24: Manglende Access-funksjoner

**Funksjoner som ikke er portert:**

### 1. NotInList-håndtering
**Access:** Når bruker skriver nytt fartøynavn i dropdown, kan de opprette det on-the-fly
**Status:** ⚠️ Ikke implementert i web-versjon

**Løsning:**
- Legg til "Opprett nytt fartøy"-knapp i fartoy_velg.php
- Modal-skjema for quick-create
- Redirect tilbake med nytt NMM_ID

### 2. Avansert søk
**Access:** Kombinert søk på flere felt
**Status:** ⚠️ Kun enkel FNA-søk implementert

**Løsning:**
- Utvid søkeskjema med flere felt (FTY, BYG, KAL)
- Kombiner filters med AND/OR logikk

### 3. Batch-operasjoner
**Access:** Bulk edit/delete
**Status:** ⚠️ Ikke implementert

**Løsning:**
- Checkboxes for multi-select
- "Marker alle som Transferred"-knapp
- "Slett valgte"-knapp

### 4. Audit trail
**Access:** Logg hvem endret hva når
**Status:** ⚠️ Ikke implementert

**Løsning:**
```sql
CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(50),
    record_id INT,
    action ENUM('INSERT', 'UPDATE', 'DELETE'),
    user_id INT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    old_values JSON,
    new_values JSON
);
```

**Estimat:** 24+ timer (avhenger av scope)
**Status:** ⚠️ Vurder behov med bruker før implementering

---

## Oppsummering

| Oppgave | Estimat | Prioritet | Status |
|---------|---------|-----------|--------|
| 17. Automatiserte tester | 16+ timer | 🟢 LAV | ⚠️ Ikke startet |
| 18. Caching | 8 timer | 🟢 LAV | ⚠️ Ikke nødvendig p.t. |
| 19. Database-optimalisering | 4 timer | 🟢 LAV | ⚠️ Ytelse OK |
| 20. Tilgjengelighet | 6 timer | 🟢 LAV | ⚠️ Grunnleggende OK |
| 21. Ekstraher repetert kode | 2 timer | 🟢 LAV | ⚠️ Fungerer som det er |
| 22. PHPDoc | 8 timer | 🟢 LAV | ⚠️ Type hints OK |
| 23. Transaksjoner | 3 timer | 🟢 LAV | ⚠️ Lav risiko |
| 24. Manglende Access-features | 24+ timer | 🟢 LAV | ⚠️ Vurder behov |
| **TOTALT** | **71+ timer** | - | **Dokumentert, ikke implementert** |

---

## Anbefalinger for fremtidig arbeid

### Kort sikt (3-6 måneder):
1. **Task 19:** Legg til database-indekser (1 time) hvis ytelse blir problem
2. **Task 23:** Fjern mystisk transaction check (15 min)
3. **Task 21:** Ekstraher repeated code (2 timer) ved neste refactoring

### Mellomlang sikt (6-12 måneder):
1. **Task 17:** Sett opp PHPUnit for kritiske funksjoner (4 timer for basis-setup)
2. **Task 20:** Legg til skip-link og ARIA labels (2 timer)
3. **Task 24:** Vurder NotInList-funksjon med bruker (8 timer hvis ønsket)

### Lang sikt (12+ måneder):
1. **Task 18:** Vurder Redis/Memcached hvis applikasjonen skalerer
2. **Task 24:** Audit trail hvis compliance krever det
3. **Task 17:** Full test coverage (16+ timer)

---

**Konklusjon:**
Disse oppgavene er teknisk gjeld og nice-to-have forbedringer. Eksisterende system fungerer godt uten dem. Implementer kun når konkrete behov oppstår eller når det er ledig kapasitet.

---

**Dokumentert:** 2026-01-03
**Av:** Claude Code
**Status:** Ikke implementert – Anbefalt for fremtidig arbeid
