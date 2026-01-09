# Setup Scripts

Dette er setup-scripts for initial oppsett av NMMPrimus. Disse er kun ment for engangsbruk ved oppsett.

## opprett_bruker.php

CLI-verktøy for å opprette nye brukere direkte i databasen.

### Bruk

```bash
php setup/opprett_bruker.php <email> <passord> <rolle>
```

### Eksempel

```bash
php setup/opprett_bruker.php admin@example.com MySecurePassword123 admin
```

### Parametere

- `email`: Gyldig e-postadresse
- `passord`: Minimum 12 tegn
- `rolle`: `admin` eller `user`

### Sikkerhet

⚠️ **VIKTIG:**
- Dette scriptet kan **kun** kjøres fra kommandolinjen (CLI)
- Bruk **aldri** hardkodede passord i scripts
- For vanlig brukeradministrasjon, bruk `modules/admin/bruker_admin.php` via web-grensesnittet

### Etter oppsett

Når første admin-bruker er opprettet:
1. Logg inn via web-grensesnittet
2. Bruk brukeradministrasjons-modulen for videre brukeradministrasjon
3. Vurder å slette eller sikre `setup/`-mappen i produksjon
