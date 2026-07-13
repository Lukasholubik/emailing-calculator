# Emailing Calculator – Přehled pluginu

> **Rychlý průvodce pro navigaci v kódu.** Při každém novém úkolu nejprve nahlédni do `dev-log.md`, pak sem.

---

## Základní info

| Položka | Hodnota |
|---|---|
| Verze | 1.6.0 |
| Prefix option klíčů | `ecalc_` |
| Prefix tříd | `ECAlc_` |
| DB tabulky | `{prefix}emailing_calculator_leads`, `{prefix}emailing_calculator_log` |
| REST namespace | `emailing-calculator/v1` |
| Shortcode | `[emailing_calculator]` |
| GitHub repo | `https://github.com/Lukasholubik/emailing-calculator/` |
| Auto-updater | Plugin Update Checker v5.5 (vendor/) |

---

## Co plugin dělá

ROI kalkulačka pro e-shopy – zjistí potenciál e-mailingu:
1. Uživatel vyplní formulář (segment, databáze, obrat, PNO, spotřební %)
2. Plugin spočítá skóre a doporučí balíček nebo konzultaci
3. Uloží lead do DB, pošle e-maily, synchronizuje do SmartEmailing
4. Admin spravuje leady, mění statusy, vidí analytiku

---

## Struktura složek

```
emailing-calculator/
├── emailing-calculator.php           ← bootstrap, konstanty (ECALC_VERSION, ECALC_DIR…)
├── docs/                             ← TATO SLOŽKA – vývojová dokumentace
│   ├── overview.md
│   ├── settings-reference.md
│   └── dev-log.md
│
├── includes/
│   ├── class-ecalc-plugin.php        ← orchestrátor, init, enqueueing, CSS vars
│   ├── class-ecalc-activator.php     ← tvorba/migrace DB tabulek, db_version
│   ├── class-ecalc-settings.php      ← get/save všech options
│   ├── class-ecalc-calculator.php    ← výpočetní engine (skóre → doporučení)
│   ├── class-ecalc-leads.php         ← CRUD leadů, filtrování, export
│   ├── class-ecalc-email.php         ← e-mailové notifikace (admin, klient, follow-up)
│   ├── class-ecalc-smartemailing.php ← sync kontaktů do SE API v3
│   ├── class-ecalc-rest.php          ← REST API endpointy
│   ├── class-ecalc-shortcode.php     ← render formuláře [emailing_calculator]
│   ├── class-ecalc-admin.php         ← admin stránky, AJAX, CSV export
│   ├── class-ecalc-cron.php          ← naplánované follow-up e-maily
│   ├── class-ecalc-analytics.php     ← analytická data, time series, statistiky
│   ├── helpers.php                   ← utility funkce
│   └── grou-admin-group.php          ← seskupení Grou.cz pluginů v menu
│
├── templates/
│   ├── admin/                        ← šablony admin stránek (15+)
│   └── frontend/                     ← HTML šablona kalkulačky
│
└── assets/
    ├── css/
    │   ├── frontend.css              ← kompletní styling kalkulačky, CSS custom props
    │   └── admin.css                 ← admin UI styling
    └── js/
        ├── frontend.js               ← formulář, validace, výsledky, GTM eventy
        ├── admin.js                  ← admin interakce
        └── admin-analytics.js        ← analytický dashboard
```

---

## Třídy a jejich zodpovědnost

| Třída | Soubor | Co dělá |
|---|---|---|
| `ECAlc_Plugin` | class-ecalc-plugin.php | Orchestrátor – init, shortcode, REST, cron, assets, CSS vars |
| `ECAlc_Activator` | class-ecalc-activator.php | Tvorba/migrace tabulek na aktivaci/db_version bump |
| `ECAlc_Settings` | class-ecalc-settings.php | Jediný přístupový bod pro get/save options |
| `ECAlc_Calculator` | class-ecalc-calculator.php | `calculate($data)` → vrací kompletní výsledek s doporučením |
| `ECAlc_Leads` | class-ecalc-leads.php | `insert()`, `update()`, `get_leads()`, `export_csv()` |
| `ECAlc_Email` | class-ecalc-email.php | Admin notif, klientský e-mail, follow-up, inquiry, konzultace |
| `ECAlc_SmartEmailing` | class-ecalc-smartemailing.php | `sync_contact($lead)`, `test_connection()` |
| `ECAlc_REST` | class-ecalc-rest.php | 8 REST endpointů (viz níže) |
| `ECAlc_Shortcode` | class-ecalc-shortcode.php | Render HTML kalkulačky |
| `ECAlc_Admin` | class-ecalc-admin.php | 15+ admin stránek, AJAX, CSV export |
| `ECAlc_Cron` | class-ecalc-cron.php | Follow-up cron event `ecalc_send_followup` |
| `ECAlc_Analytics` | class-ecalc-analytics.php | Time series, konverze, segmenty, abandonment |

---

## REST API endpointy (`emailing-calculator/v1`)

| Endpoint | Metoda | Auth | Funkce |
|---|---|---|---|
| `/calculate` | POST | Nonce | Odeslání formuláře, výpočet, uložení leadu |
| `/check-email` | GET | Public | Kontrola duplicitního e-mailu |
| `/booking-status` | POST | Nonce + Token | Aktualizace stavu rezervace kalendáře |
| `/cta-click` | POST | Nonce + Token | Zaznamenání kliknutí na CTA |
| `/save-phone` | POST | Nonce + Token | Uložení telefonního čísla (legacy – od v.1.6.0 se telefon sbírá rovnou v `/calculate`, frontend endpoint už nevolá) |
| `/resend/{id}` | POST | manage_options | Ruční resync do SmartEmailing |
| `/track-view` | POST | Public | Analytika – zobrazení stránky |
| `/track-exit` | POST | Public | Analytika – opuštění formuláře |

**Token:** `wp_hash($lead_id . ':' . $email)` – brání cizím úpravám leadu.

---

## WordPress hooky

### Actions
| Hook | Kdo | Co |
|---|---|---|
| `admin_post_ecalc_*` (13×) | Admin.php | Uložení nastavení jednotlivých stránek |
| `wp_ajax_ecalc_*` (5×) | Admin.php | AJAX operace v adminu |
| `ecalc_send_followup` (cron) | Cron.php | Odeslání follow-up e-mailu |
| `ecalc_lead_saved` | custom | Spuštěno po vložení nového leadu |
| `ecalc_lead_status_changed` | custom | Spuštěno při změně statusu leadu |
| `ecalc_cta_clicked` | custom | Spuštěno při kliknutí na CTA |

---

## Výpočetní logika (stručně)

```
consumable_score = consumable_percentage / 100               (0.0–1.0)
database_score   = lookup z ecalc_database_ranges            (0.0–1.0)
segment_score    = lookup z ecalc_segments                   (0.0–1.0)

total_score = (consumable × 0.70) + (database × 0.20) + (segment × 0.10)

final_potential = clamp(15 + (45-15) × total_score, 15, 45)  (procenta)

emailing_revenue_mid  = monthly_revenue × (final_potential / 100)
emailing_revenue_low  = mid × 0.85
emailing_revenue_high = mid × 1.15

available_budget = mid × (expected_pno / 100)
```

**Klasifikace výsledku:**
- `available_budget < 10 000` → `low_potential`
- `< 15 000` → `borderline`
- `≥ cena nejlevnějšího balíčku` → `package_1` nebo `package_n`

Výchozí váhy (70/20/10) lze měnit v admin → Nastavení výpočtů → Výpočet.

---

## Formulářová pole (vstup kalkulačky)

| Pole | Typ | Poznámka |
|---|---|---|
| name | text | povinné, max 255 znaků |
| email | email | povinné, UNIQUE v DB |
| phone | tel | povinné, 7–15 číslic |
| shop_url | url | povinné, validovaná doména |
| segment | select | 34 kategorií e-shopů |
| consumable_percentage | 0–100 % | slider + textový input |
| database_range | select | 5 rozsahů velikosti DB |
| monthly_revenue | number/select | rozsah nebo přesná částka |
| expected_pno | 1–100 % | náklady jako % z obratu |
| consent_data | checkbox | povinné – zpracování dat |
| consent_marketing | checkbox | podmíněné – sync do SE |

Honeypot: skryté pole `_hp_field` – bot protection.

---

## Statusy leadů

Leady procházejí životním cyklem:
- `Nový` → výchozí po odeslání
- `Aktivní` → po CTA kliknutí / manuálně adminem
- `Neaktivní` → cron po X hodinách bez CTA kliknutí
- `Uzavřený` → manuálně adminem

Každá změna statusu se loguje do `emailing_calculator_log`.

---

## Bezpečnost (přehled)

- Nonce: `wp_create_nonce('wp_rest')` na frontendu, ověřeno v každém REST endpointu
- Token: `wp_hash(lead_id . ':' . email)` pro akce po odeslání (CTA, telefon, booking)
- Rate limiting: tranzienty – limity na e-mail, IP, endpoint
- Honeypot: `_hp_field` v HTML formuláři
- Cloudflare Turnstile: volitelné, token ověřen server-side
- GDPR: dvě consent checkboxy, marketing consent vyžadován pro SE sync
- Sanitizace: `sanitize_text_field`, `sanitize_email`, `esc_url_raw`, `intval`
- Výstup: `esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`

---

## Integrace SmartEmailing

- **Endpoint:** `https://app.smartemailing.cz/api/v3`
- **Auth:** HTTP Basic (username:api_key)
- **Trigger:** Vložení leadu (pokud `consent_marketing = 1`)
- **Synchronizuje:** jméno, e-mail, telefon, segment, obrat, potenciál, výsledek, balíček
- **Tagy:** status tag + result tag
- Ruční resync přes admin nebo REST `/resend/{id}`

---

## Cloudflare Turnstile

- Konfigurace: Admin → Zabezpečení
- Option klíč: `ecalc_turnstile` (`site_key`, `secret_key`, `enabled`)
- Ověření při každém odeslání formuláře (server-side `siteverify`)
- Přeskočeno u recalculation (token je single-use)

---

## GTM eventy (frontend.js)

| Event | Kdy |
|---|---|
| `form_start` | První interakce s formulářem |
| `form_submit` | Odeslání formuláře |
| `calculation_success` | Výsledky zobrazeny |
| `duplicate_dialog` | Dialog duplicitního e-mailu |
| `cta_click` | Kliknutí na CTA tlačítko |

---

*Tento soubor popisuje stav pluginu ke dni **2026-07-13**. Aktuální změny viz `dev-log.md`.*
