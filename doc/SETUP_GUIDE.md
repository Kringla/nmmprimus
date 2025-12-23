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

Er utført!

---

## 3. Produksjonsoppsett

### 3.1 Filoverføring

Utført!

### 3.2 Konfigurer for produksjon

Utført!

### 3.3 Oppdater BASE_URL

Utført!

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

`Primus_Schema.md` gir schema!

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
Er notert!
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
Utført.

---

## 7. Kontaktinformasjon

Ved spørsmål eller problemer, kontakt:
- E-post: webman@skipsweb.no
