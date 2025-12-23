# NMMPrimus - Oppsettguide

Denne guiden beskriver hvordan du setter opp prosjektet for lokal utvikling og produksjon.

---

## 1. Systemkrav

- **PHP:** 8.1 eller nyere (8.3.x anbefalt)
- **MySQL:** 8.0 eller nyere (8.3.28 på webhotellet)
- **Webserver:** Apache med mod_rewrite (XAMPP anbefalt for lokal utvikling)
- **Nettleser:** Chrome (primær), Edge (støttet)

---

## 2. Lokal utvikling med XAMPP

### 2.1 Installer XAMPP

1. Last ned XAMPP fra https://www.apachefriends.org/
2. Installer med PHP 8.x
3. Start Apache og MySQL fra XAMPP Control Panel

### 2.2 Klon/kopier prosjektet

Plasser prosjektmappen i:
```
C:\xampp\htdocs\nmmprimus\
```

Filstrukturen skal være:
```
C:\xampp\htdocs\nmmprimus\
├── assets/
│   └── app.css
├── config/
│   ├── config.php          ← Lokal konfigurasjon
│   ├── configProd.php      ← Produksjonskonfigurasjon (IKKE bruk lokalt)
│   ├── constants.php       ← Lokal BASE_URL
│   └── constantsProd.php
├── doc/
├── includes/
├── modules/
├── index.php
├── login.php
├── logout.php
└── AGENTS.md
```

### 2.3 Opprett databasen

1. Åpne phpMyAdmin: http://localhost/phpmyadmin
2. Opprett ny database: `nmmprimus`
3. Velg database og importer schema:
   - Klikk "Import"
   - Velg filen `doc/Primus_Schema.md` (eller kjør SQL manuelt)

### 2.4 Opprett testbruker

Kjør følgende SQL i phpMyAdmin:

```sql
-- Opprett bruker med passord "test123"
INSERT INTO user (email, password, IsActive, role) 
VALUES (
    'test@example.com', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
    1, 
    'admin'
);
```

**Merk:** Passordet over er en bcrypt-hash av "test123". For å generere egen hash:

```php
<?php
echo password_hash('ditt_passord', PASSWORD_BCRYPT);
```

### 2.5 Konfigurer prosjektet

**config/config.php** (lokal):
```php
<?php
declare(strict_types=1);

define('DB_HOST', 'localhost');
define('DB_NAME', 'nmmprimus');
define('DB_USER', 'root');
define('DB_PASS', '');
```

**config/constants.php** (lokal):
```php
<?php
define('BASE_URL', '/nmmprimus');
```

### 2.6 Test installasjonen

1. Åpne http://localhost/nmmprimus/
2. Du skal bli omdirigert til login-siden
3. Logg inn med testbrukeren
4. Du skal se velkomstsiden og bli videresendt til primus_main.php

---

## 3. Produksjonsoppsett

### 3.1 Filoverføring

1. Last opp alle filer til webhotellet via FTP
2. Plasser i riktig mappe (avhengig av domene/undermappe)

### 3.2 Konfigurer for produksjon

**Viktig:** Ikke overfør `config/config.php` - lag en egen fil på serveren, eller:

1. Gi nytt navn til `config/configProd.php` → `config/config.php`
2. Oppdater databasekredentialene

**Anbefalt:** Bruk miljøvariabler i stedet for hardkodede passord:

```php
<?php
declare(strict_types=1);

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'nmmprimus');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
```

### 3.3 Oppdater BASE_URL

I `config/constants.php`, sett riktig base URL:

```php
// Hvis applikasjonen ligger i rot:
define('BASE_URL', '');

// Hvis applikasjonen ligger i undermappe:
define('BASE_URL', '/nmmprimus');
```

### 3.4 Sett riktige filtillatelser

```bash
chmod 755 /path/to/nmmprimus
chmod 644 /path/to/nmmprimus/*.php
chmod 755 /path/to/nmmprimus/modules
chmod 755 /path/to/nmmprimus/includes
chmod 755 /path/to/nmmprimus/config
```

---

## 4. Database

### 4.1 Tabelloversikt

| Tabell | Beskrivelse |
|--------|-------------|
| `nmmfoto` | Hovedtabell - fotoobjekter |
| `nmm_skip` | Fartøyregister |
| `bildeserie` | Bildeserier (parameter) |
| `country` | Nasjoner (parameter) |
| `farttype` | Fartøytyper (parameter) |
| `nmmxemne` | Motivemner (relasjon) |
| `nmmxtype` | Motivtyper (relasjon) |
| `nmmxou` | OU-klassifikasjoner (relasjon) |
| `nmmxudk` | UDK-klassifikasjoner (relasjon) |
| `nmmxhendelse` | Hendelser (relasjon) |
| `user` | Brukere |
| `user_preferences` | Brukerpreferanser |
| `user_remember_tokens` | "Husk meg"-tokens |
| `_zhendelsestyper` | Hendelsestyper (oppslagstabell) |

### 4.2 Viktige relasjoner

```
nmmfoto.NMM_ID → nmm_skip.NMM_ID
nmmxemne.NMM_ID → nmm_skip.NMM_ID
nmmxtype.NMM_ID → nmm_skip.NMM_ID
nmmxou.NMM_ID → nmm_skip.NMM_ID
nmmxudk.NMM_ID → nmm_skip.NMM_ID
user_preferences.user_id → user.user_id
user_remember_tokens.user_id → user.user_id
```

---

## 5. Feilsøking

### Problem: "Could not connect to database"

1. Sjekk at MySQL kjører i XAMPP
2. Verifiser at database `nmmprimus` eksisterer
3. Sjekk kredentialene i `config/config.php`

### Problem: "Class 'PDO' not found"

PHP mangler PDO-utvidelsen. I XAMPP er dette normalt aktivert.

### Problem: Hvit side / 500 error

1. Aktiver feilvisning i PHP:
   ```php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```
2. Sjekk Apache error log: `C:\xampp\apache\logs\error.log`

### Problem: CSS lastes ikke

Sjekk at `BASE_URL` i `constants.php` er korrekt satt.

### Problem: "Remember me" fungerer ikke

1. Sjekk at `user_remember_tokens`-tabellen eksisterer
2. Slett eventuelle utgåtte tokens:
   ```sql
   DELETE FROM user_remember_tokens WHERE expires_at < NOW();
   ```

---

## 6. Sikkerhetshensyn

1. **Aldri** commit `config/config.php` med produksjonspassord til Git
2. Legg til i `.gitignore`:
   ```
   config/config.php
   ```
3. Bruk sterke passord for databasebruker
4. Hold PHP og MySQL oppdatert
5. Aktiver HTTPS i produksjon

---

## 7. Kontaktinformasjon

Ved spørsmål eller problemer, kontakt:
- E-post: webman@skipsweb.no
