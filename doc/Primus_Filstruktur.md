# FIL STRUKTUR OG LISTE

Overordnet mappe: Git-repo for Primusdatabasen.

**'¤'** betyr ikke utviklet
Midlertidige filer vises iokke.
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
 │   ├─ primus/
 │   │   ├─ primus_detalj.php
 │   │   ├─ primus_main.php
 │   │   ├─ primus_modell.php
 │   │   └─ api/ ¤ 
 │   │       ├─ ??.php  
 │   │       └─ ??.php
 │   └─ foto/
 │       ├─ foto_arbeidsflate.php
 │       ├─ foto_liste.php
 │       ├─ foto_modell.php
 │       └─ api/ 
 │           ├─ foto_state.php  
 │           ├─ kandidater.php 
 │           └─ velg_kandidat.php
 ├─ login.php 
 ├─ logout.php 
 └─ index.php