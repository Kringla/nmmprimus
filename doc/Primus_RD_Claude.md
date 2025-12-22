#Prosjekt Krav Dokument (PRD)

Prosjektets navn er **nmmprimus**

##PROSJEKTMÅL:
En eksisterende Access database sin front-end skal konverteres til en funksjonsdyktig nettbasert løsning, med en MySQL database som back-end. Databasen heter **nmmprimus** (DB).
Formålet med databasen er å koble verdier fra flere parametertabeller og manuelt satte verdier inn i en resultattabell.

##OVERORDNETE KRAV:
GUI skal kunne brukes i alle vanlig forekommende browsere som Edge og Chrome.
GUI skal kunne brukes av opptil 3 samtidige brukere.
Det skal opprettes adgangskontroll med e-post/passord i egen tabell i DB.
Når logget på skal pålogging vare hele sesjonen, og avlogging skjer når en lukker nettleseren.
Enkelhet i kode og visuelt inntrykk er viktig.

##DATABASENS INNHOLD:
Databasen inneholder tabeller som er gitt i dokumentet `Primus_Schema.md`. Resultattabellen er `nmmfoto`.

##RESSURSBRUK:
Jeg er leder av utviklingen, men koder ikke selv.
Du er den som utvikler koden som er nødvendig for at prosjektets mål blir nådd.

##RESSURSRAMMEBETINGELSER:
DB ligger på et Webhotell. Webhotellet har MySQL ver 8.3.28 og støtter Cron.
Jeg har en MSc i "Design of Information Systems" fra 1980. Jeg har utviklet applikajoner i og kan lese VBA kode. Jeg har god erfaring med design av Relasjonsdatabaser.
* Jeg har tilgjengelig VB Studio Code, Notebook++, Access, phpMySQL ver 5.2.2, MySQL Workbench ver 8.0 CE, Heidi og xmapp.
Du kan få adgang til Webhotell, MySQL og min OneDrive ev. Dropbox eller Google Drive.

##ARBEIDSPROSESS:
Arbeidet skal utføres i henhold til en trinnvis migeringsplan.

### TRINN 1 – Oppsett og grunnstruktur
Mål: Etablere grunnlaget for utvikling, inkludert tilgangskontroll.
Backend: PHP 8.3.x (fordi det støttes av webhotellet)
Database: MySQL 8.3.28 (eksisterende)
Frontend: HTML, CSS (enkelt), og litt JavaScript (validering, interaktivitet)
Autentisering: users-tabell med feltene user_id, email, password, isactive, created_at, lastused
Påloggingsfunksjonalitet(`login.php`).

### TRINN VIDERE
Mål: Gjennomføre prosjektet iht. prosjektets migreringsplan i dokumentet `Primus_Migrasjonsplan.md`), som styrt av prosjektets instrukser.
