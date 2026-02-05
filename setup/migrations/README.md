# Database Migrations

Denne mappen inneholder SQL-migrasjonsfiler for NMMPrimus-databasen.

## Kjøre Migrasjoner

### På Produksjonsserver

1. **Ta backup FØRST:**
   ```bash
   mysqldump -u [bruker] -p nmmprimus > backup_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **Kjør migrasjon:**
   ```bash
   mysql -u [bruker] -p nmmprimus < migrations/001_change_bit_to_tinyint.sql
   ```

3. **Verifiser:**
   ```bash
   mysql -u [bruker] -p nmmprimus -e "DESCRIBE nmmfoto;"
   ```

### På Lokal Utviklingsserver (XAMPP)

1. **Via phpMyAdmin:**
   - Åpne phpMyAdmin
   - Velg `nmmprimus` database
   - Gå til "SQL"-fanen
   - Kopier innholdet fra migrasjonsfilen
   - Kjør SQL

2. **Via MySQL Command Line:**
   ```bash
   mysql -u root nmmprimus < migrations/001_change_bit_to_tinyint.sql
   ```

## Migrasjonsoversikt

| Fil | Beskrivelse | Dato | Status |
|-----|-------------|------|--------|
| `001_change_bit_to_tinyint.sql` | Endre BIT(1) til TINYINT(1) for Aksesjon, Fotografi, FriKopi, Transferred, Flag | 2026-01-08 | Klar for kjøring |
| `002_add_last_sernr_tracking.sql` | Legg til user_serie_sernr-tabell for smart SerNr-forslag per bruker per serie | 2026-01-14 | Klar for kjøring |

## Rollback

Hvis noe går galt, kan du rulle tilbake med backup:

```bash
mysql -u [bruker] -p nmmprimus < backup_YYYYMMDD_HHMMSS.sql
```

## Viktig

- **ALLTID ta backup før migrering!**
- Test på lokal database først
- Verifiser at applikasjonen fungerer etter migrering
- Dokumenter eventuelle problemer i denne filen
