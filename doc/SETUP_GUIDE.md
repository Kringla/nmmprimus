# NMMPrimus – Oppsettguide

Veiledning for oppsett av NMMPrimus for lokal utvikling og produksjon.

---

## Systemkrav

- **PHP:** 8.1+ (8.3.x anbefalt)
- **MySQL:** 8.0+ (8.3.28 i produksjon)
- **Webserver:** Apache med mod_rewrite
- **XAMPP:** Anbefalt for lokal utvikling
- **Nettleser:** Chrome (primær), Edge (støttet)

---

## Lokal utvikling (XAMPP)

### 1. Installer XAMPP

Last ned og installer XAMPP med PHP 8.1+ og MySQL 8.0+.

### 2. Klon repository

```bash
cd C:\xampp\htdocs
git clone [repository-url] nmmprimus
```

### 3. Opprett database

```sql
CREATE DATABASE nmmprimus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Importer schema fra `doc/Primus_Schema.md`.

### 4. Konfigurer database

Rediger `config/config.php`:

```php
return [
    'host' => 'localhost',
    'dbname' => 'nmmprimus',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
];
```

### 5. Konfigurer BASE_URL

Rediger `config/constants.php`:

```php
define('BASE_URL', 'http://localhost/nmmprimus/');
```

### 6. Test oppsettet

Åpne i nettleser:
```
http://localhost/nmmprimus/login.php
```

---

## Produksjonsoppsett

### 1. Overføring

Last opp alle filer til webhotellet, unntatt:
- `.git/`
- `config/config.php` (bruk `configProd.php`)
- `config/constants.php` (bruk `constantsProd.php`)

### 2. Database

Opprett database via cPanel/phpMyAdmin og importer schema.

### 3. Konfigurer database

Opprett `config/config.php` på server:

```php
<?php
return [
    'host' => 'din-database-server',
    'dbname' => 'din_database_navn',
    'username' => 'din_database_bruker',
    'password' => 'ditt_passord',
    'charset' => 'utf8mb4'
];
```

### 4. Konfigurer BASE_URL

Opprett `config/constants.php` på server:

```php
<?php
define('BASE_URL', 'https://ditt-domene.no/nmmprimus/');
```

### 5. Filtillatelser

```bash
chmod 755 /path/to/nmmprimus
chmod 644 /path/to/nmmprimus/*.php
chmod 755 /path/to/nmmprimus/modules
chmod 755 /path/to/nmmprimus/includes
chmod 755 /path/to/nmmprimus/config
```

---

## Database-tabeller

Se [Primus_Schema.md](Primus_Schema.md) for komplett skjema.

### Hovedtabeller

| Tabell | Beskrivelse |
|--------|-------------|
| `nmmfoto` | Fotoobjekter (hovedtabell) |
| `nmm_skip` | Fartøyregister |
| `bildeserie` | Bildeserier |
| `country` | Nasjoner |
| `farttype` | Fartøytyper |

### Relasjonstabeller

| Tabell | Beskrivelse |
|--------|-------------|
| `nmmxemne` | Motivemner |
| `nmmxtype` | Motivtyper |
| `nmmxou` | OU-klassifikasjoner |
| `nmmxudk` | UDK-klassifikasjoner |
| `nmmxhendelse` | Hendelser |

### Brukertabeller

| Tabell | Beskrivelse |
|--------|-------------|
| `user` | Brukere |
| `user_preferences` | Brukerpreferanser |
| `user_remember_tokens` | "Remember me"-tokens |

### Viktige relasjoner

```
nmmfoto.NMM_ID → nmm_skip.NMM_ID
nmmx*.NMM_ID → nmm_skip.NMM_ID
user_preferences.user_id → user.user_id
user_remember_tokens.user_id → user.user_id
```

---

## Feilsøking

### "Could not connect to database"

1. Sjekk at MySQL kjører (XAMPP Control Panel)
2. Verifiser at database `nmmprimus` eksisterer
3. Sjekk kredentialer i `config/config.php`

### "Class 'PDO' not found"

PHP mangler PDO-utvidelsen. I XAMPP er dette normalt aktivert.

### Hvit side / 500 error

1. Aktiver feilvisning i PHP:
   ```php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```
2. Sjekk Apache error log: `C:\xampp\apache\logs\error.log`

### CSS lastes ikke

Sjekk at `BASE_URL` i `constants.php` er korrekt satt.

### "Remember me" fungerer ikke

1. Sjekk at `user_remember_tokens`-tabellen eksisterer
2. Slett utgåtte tokens:
   ```sql
   DELETE FROM user_remember_tokens WHERE expires_at < NOW();
   ```

### Session-problemer

1. Sjekk at session-katalogen er skrivbar
2. Verifiser session-innstillinger i php.ini
3. Test med hard refresh: CTRL+F5

---

## Sikkerhet

### Passord
- Alle passord lagres med `password_hash()` (bcrypt)
- Aldri lagre passord i klartekst

### CSRF-beskyttelse
- Alle POST-operasjoner krever CSRF-token
- Token genereres via `csrf_field()`
- Valideres via `csrf_validate()`

### SQL Injection
- Kun prepared statements brukes
- Ingen rå SQL-strenger

### Session
- HTTPOnly cookies
- Secure cookies i produksjon (HTTPS)
- "Remember me"-tokens kryptert

---

## Kontakt

Ved spørsmål eller problemer:
- E-post: webman@skipsweb.no

---

**Se også:**
- [CLAUDE.md](../CLAUDE.md) – Teknisk referanse
- [Primus_Funksjonalitet.md](Primus_Funksjonalitet.md) – Funksjonell beskrivelse
- [Primus_Schema.md](Primus_Schema.md) – Database-skjema
