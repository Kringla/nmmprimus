# FIL STRUKTUR OG LISTE

Overordnet mappe: Git-repo for Primusdatabasen.

**'¤'** betyr ikke utviklet
Midlertidige filer vises ikke.
Dokument-filer vises ikke.

Rot:
nmmprimus/
 ├─ config/
 │   ├─ constants.php 
 │   └─ config.php
 ├─ includes/
 │   ├─ auth.php
 │   ├─ db.php
 │   ├─ foto_flyt.php
 │   ├─ functions.php
 │   ├─ layout_slutt.php
 │   ├─ layout_start.php
 │   └─ ui.php
 ├─ assets/
 │   └─ app.css
 ├─ modules/
 │   ├─ fartoy/
 │   │   ├─ ??.php  ¤
 │   │   └─ fartoy_velg.php  
 │   ├─ foto/
 │   │   ├─ foto_modell.php
 │   │   └─ api/ 
 │   │       ├─ foto_state.php  
 │   │       ├─ kandidater.php 
 │   │        └─ velg_kandidat.php
 │   └─ primus/
 │       ├─ primus_detalj.php
 │       ├─ primus_main.php
 │       ├─ primus_modell.php
 │       └─ api/  
 │           ├─ neste_sernr.php  
 │           └─ sett_session.php
 ├─ login.php 
 ├─ logout.php 
 └─ index.php