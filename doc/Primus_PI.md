# NMMPRIMUS – Project Instructions (Primus_PI.md)

Dette dokumentet definerer **hva** ChatGPT skal bygge og analysere i prosjektet **nmmprimus**.
Dokumentet er funksjonelt og arkitektonisk styrende, men er **underordnet AGENTS.md** når det gjelder arbeidsform og leveranser.

---

## 1. Prosjektavgrensning

* Prosjektet er **utelukkende** `nmmprimus`
* Ingen kode, struktur eller antagelser fra andre prosjekter
* Kun følgende kilder er autoritative:

  * Project Documents for `nmmprimus`
  * Filer eksplisitt vedlagt i chat
  * Eksisterende kode i repoet

Hvis informasjon mangler: **SI IFRA før arbeid starter.**

---

## 2. Prosjektets styrende dokumenter

Disse dokumentene utgjør prosjektets sannhetsgrunnlag:

* `Primus_PI.md` – dette dokumentet
* `Primus_RD.md` – kravdokument
* `Primus_Migreringsplan.md` – gjennomføringsplan
* `Primus_Schema.md` – databaseskjema
* `Primus_Filstruktur.md` – fil- og mappestruktur

Disse skal leses **før** utvikling eller endring av kode.

---

## 3. Migreringsmål

* Nettbasert PHP/MySQL-front-end
* Funksjonell paritet med følgende Access-former:

  * `frmNMMPrimusMain`
  * `frmNMMPrimus`
  * `frmNMMPrimusKand subform`
  * `frmNMMSkipsValg`

VBA-logikk skal identifiseres, forklares og oversettes til PHP / JS / SQL.

---

## 4. Arbeidsmodus

* Fokus på korrekthet og sporbarhet
* Paritet med Access er viktigere enn forenkling
* Avvik fra Access er tillatt **kun** når resultatet er funksjonelt identisk

---

## 5. UI og interaksjon

* UI skal etterligne Access der relevant
* Default-verdier og session-basert tilstand er viktige
* Umiddelbar respons der Access gjør det samme
* JS brukes kun når nødvendig

---

## 6. Forhold til AGENTS.md

* AGENTS.md definerer **hvordan** arbeidet utføres
* Dette dokumentet definerer **hva** som bygges
* Ved konflikt:

  * **AGENTS.md har alltid forrang**

---

## 7. Endringer i Project Instructions

* Endringer skal gjøres eksplisitt
* Lagres i Project Documents
* Gjelder fra **neste chat**
