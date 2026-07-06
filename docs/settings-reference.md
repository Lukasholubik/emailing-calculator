# Emailing Calculator – Reference nastavení

Všechna nastavení pluginu jsou uložena jako WordPress options s prefixem `ecalc_`.  
Přístup vždy přes `ECAlc_Settings` (nikdy `get_option` napřímo v business logice).

---

## ecalc_settings

Hlavní nastavení – váhy, prahy, CTA, limity.

```php
[
    // Váhy výpočtu (součet = 100)
    'weight_consumable' => 70,
    'weight_database'   => 20,
    'weight_segment'    => 10,

    // Prahy klasifikace (Kč)
    'threshold_low'        => 10000,   // pod tímto = low_potential
    'threshold_borderline' => 15000,   // pod tímto = borderline

    // Potenciál (procenta výnosů z emailingu)
    'potential_min' => 15,
    'potential_max' => 45,

    // Limity
    'monthly_submit_limit' => 5,   // max odeslání/měsíc na e-mail
    'hourly_recalc_limit'  => 10,  // max recalculations/hodinu

    // Follow-up
    'followup_hours' => 24,   // za kolik hodin odeslat follow-up

    // CTA tlačítka
    'cta_consultation_label' => 'Domluvit konzultaci',
    'cta_consultation_url'   => 'https://...',
    'cta_package_label'      => 'Poptat balíček',

    // Formulář
    'form_title'       => 'Zjistěte potenciál vašeho e-mailingu',
    'marketing_consent_required' => 0,   // 0/1 – je marketing consent povinný?
]
```

---

## ecalc_segments

Pole kategorií e-shopů – každá má `slug`, `label` a `score` (0.0–1.0):

```php
[
    ['slug' => 'food',     'label' => 'Potraviny',     'score' => 0.9],
    ['slug' => 'fashion',  'label' => 'Móda',          'score' => 0.85],
    // ... celkem 34 kategorií
]
```

Score odpovídá potenciálu e-mailingu pro daný segment.

---

## ecalc_database_ranges

Rozsahy velikosti databáze kontaktů:

```php
[
    ['range' => '0-500',       'label' => 'Do 500',       'score' => 0.1],
    ['range' => '501-2000',    'label' => '501–2 000',    'score' => 0.3],
    ['range' => '2001-10000',  'label' => '2 001–10 000', 'score' => 0.6],
    ['range' => '10001-50000', 'label' => '10 001–50 000','score' => 0.85],
    ['range' => '50001+',      'label' => '50 001+',      'score' => 1.0],
]
```

---

## ecalc_revenue_ranges

Rozsahy měsíčního obratu (pro výběr z předvoleb):

```php
[
    ['range' => '0-100000',     'label' => 'Do 100 000 Kč',   'calc_value' => 75000],
    ['range' => '100001-300000','label' => '100–300 tis. Kč', 'calc_value' => 200000],
    // ... celkem 6 rozsahů
    // calc_value = střední hodnota pro výpočet
]
```

Uživatel může místo rozsahu zadat přesnou částku.

---

## ecalc_packages

Pole servisních balíčků (řazeno od nejlevnějšího):

```php
[
    [
        'id'          => 'package_1',
        'name'        => 'Start',
        'price'       => 15000,     // Kč/měsíc
        'description' => 'Popis balíčku...',
        'items'       => [          // seznam položek
            'Plánování & strategie',
            'Správa kampaní',
            // ...
        ],
        'highlighted' => 0,         // 1 = zvýrazněný (doporučený)
    ],
    // ...
]
```

---

## ecalc_result_texts

Texty pro každý typ výsledku:

```php
[
    'low_potential' => [
        'title'   => 'Potenciál je zatím nižší',
        'message' => 'Text vysvětlující situaci...',
        'cta'     => 'Domluvit konzultaci zdarma',
    ],
    'borderline' => [
        'title'   => 'Jste na hraně',
        'message' => '...',
        'cta'     => '...',
    ],
    'package_1' => [
        'title'   => 'Skvělý potenciál!',
        'message' => '...',
        'cta'     => '...',
    ],
    'package_n' => [
        'title'   => 'Výjimečný potenciál!',
        'message' => '...',
        'cta'     => '...',
    ],
]
```

---

## ecalc_arguments

Texty argumentů „Proč máte tento potenciál" – zobrazují se u výsledku `package_1` / `package_n` / `borderline`, jeden text na faktor podle pásma jeho skóre (nízké/střední/vysoké):

```php
[
    'enabled'          => 1,
    'title'            => 'Proč máte tento potenciál',
    'threshold_medium' => 0.34,   // skóre faktoru (0–1) od kterého se použije "medium" text
    'threshold_high'   => 0.67,   // skóre faktoru od kterého se použije "high" text

    'consumable_low'    => '...', 'consumable_medium' => '...', 'consumable_high' => '...',
    'database_low'      => '...', 'database_medium'   => '...', 'database_high'   => '...',
    'segment_low'       => '...', 'segment_medium'    => '...', 'segment_high'    => '...',

    'summary' => 'Na základě těchto faktorů odhadujeme... {emailing_revenue_low} – {emailing_revenue_high}...',
]
```

Texty prochází `ecalc_replace_variables()` (stejné placeholdery jako `ecalc_notifications`/`ecalc_result_texts`) – pozor, `{consumable_percentage}` a `{final_potential}` už samy obsahují „ %", nepsat je znovu.

Výsledek se počítá v `ECAlc_Calculator::build_arguments()` a vrací se z REST `/calculate` jako `result.arguments` (`title`, `items[]`, `summary`).

---

## ecalc_appearance

CSS custom properties pro kompletní přebarvení:

```php
[
    '--ecalc-primary'        => '#2563eb',
    '--ecalc-primary-hover'  => '#1d4ed8',
    '--ecalc-bg'             => '#ffffff',
    '--ecalc-border'         => '#e2e8f0',
    '--ecalc-text'           => '#1e293b',
    '--ecalc-font-family'    => 'Archivo Narrow, sans-serif',
    '--ecalc-border-radius'  => '8px',
    // ... kompletní sada CSS vars
]
```

Hodnoty se injektují jako `<style>:root { --ecalc-...: ...; }</style>` ve wp_head.

---

## ecalc_notifications

Šablony e-mailů:

```php
[
    // Admin notifikace při novém leadu
    'admin_email'          => 'admin@example.com',
    'admin_subject'        => 'Nový lead: {name}',
    'admin_body'           => 'HTML šablona s {placeholders}...',

    // Klientský e-mail s výsledky
    'client_subject'       => 'Váš výsledek kalkulačky',
    'client_body'          => 'HTML šablona...',

    // Follow-up (po X hodinách bez CTA)
    'followup_subject'     => 'Ještě jedna věc...',
    'followup_body'        => '...',
    'followup_enabled'     => 1,

    // Poptávka balíčku (admin + klient)
    'inquiry_admin_subject'  => 'Poptávka balíčku od {name}',
    'inquiry_admin_body'     => '...',
    'inquiry_client_subject' => 'Potvrzení poptávky',
    'inquiry_client_body'    => '...',

    // Konzultace (admin + klient)
    'consult_admin_subject'  => 'Nová žádost o konzultaci',
    'consult_admin_body'     => '...',
    'consult_client_subject' => 'Potvrzení konzultace',
    'consult_client_body'    => '...',
]
```

**Dostupné placeholdery v šablonách:**
`{name}`, `{email}`, `{shop_url}`, `{segment}`, `{database_range}`, `{monthly_revenue}`, `{final_potential}`, `{emailing_revenue_mid}`, `{available_budget}`, `{recommended_package}`, `{result_type}`, `{date}`

---

## ecalc_smartemailing

```php
[
    'enabled'     => 0|1,
    'username'    => 'user@example.com',
    'api_key'     => 'xxxx',
    'list_id'     => 5,       // ID listu v SmartEmailing
    'field_segment'   => 15,  // ID custom field pro segment
    'field_revenue'   => 16,  // ID custom field pro obrat
    'field_potential' => 17,  // ID custom field pro potenciál
    'field_package'   => 18,  // ID custom field pro balíček
    'tag_status'      => 'ecalc_lead',      // tag pro všechny leady
    'tag_result'      => 'ecalc_{result}',  // dynamický tag dle výsledku
]
```

---

## ecalc_turnstile

```php
[
    'enabled'    => 0|1,
    'site_key'   => '0x4AAA...',   // nikdy neukládat do gitu – viz admin Zabezpečení
    'secret_key' => '0x4AAA...',   // nikdy neukládat do gitu – viz admin Zabezpečení
]
```

Aktivní klíče viz paměť projektu (`project-turnstile.md`).

---

## ecalc_info_panel

Obsah informačního panelu vedle formuláře:

```php
[
    'title'   => 'Jak kalkulačka funguje?',
    'content' => 'HTML obsah...',
    'enabled' => 1,
]
```

---

## ecalc_db_version

String – verze DB schématu, slouží pro detekci potřebných migrací.

---

## Analytické options (rolling data)

Tyto options se automaticky aktualizují při každém zobrazení/odeslání:

| Klíč | Obsah |
|---|---|
| `ecalc_views_daily` | Pole `['YYYY-MM-DD' => count]` – denní počty zobrazení (365 dní) |
| `ecalc_session_times_daily` | Pole `['YYYY-MM-DD' => avg_seconds]` – průměrná délka session |
| `ecalc_abandonment_steps` | Pole `['field_name' => count]` – na kterém poli uživatel odešel |

---

## DB tabulky

### `{prefix}emailing_calculator_leads`

| Sloupec | Typ | Poznámka |
|---|---|---|
| `id` | BIGINT | PK, auto-increment |
| `created_at` | DATETIME | Timestamp odeslání |
| `name` | VARCHAR 255 | Jméno |
| `email` | VARCHAR 255 | **UNIQUE** – zabraňuje duplicitám |
| `shop_url` | VARCHAR 500 | URL e-shopu |
| `segment` | VARCHAR 100 | Slug segmentu |
| `database_range` | VARCHAR 50 | Slug rozsahu databáze |
| `monthly_revenue` | DECIMAL | Měsíční obrat (Kč) |
| `expected_pno` | DECIMAL | PNO (%) |
| `consumable_percentage` | DECIMAL | % spotřebního zboží |
| `consumable_score` | DECIMAL | Vypočítané skóre |
| `database_score` | DECIMAL | Vypočítané skóre |
| `segment_score` | DECIMAL | Vypočítané skóre |
| `total_score` | DECIMAL | Celkové skóre (0–1) |
| `final_potential` | DECIMAL | Potenciál (15–45 %) |
| `emailing_revenue_low` | DECIMAL | Odhad výnosů – pesimistický |
| `emailing_revenue_mid` | DECIMAL | Odhad výnosů – střední |
| `emailing_revenue_high` | DECIMAL | Odhad výnosů – optimistický |
| `available_budget` | DECIMAL | Doporučený budget |
| `recommended_package` | VARCHAR 100 | ID doporučeného balíčku |
| `recommended_package_name` | VARCHAR 255 | Název balíčku |
| `recommended_package_price` | DECIMAL | Cena balíčku |
| `result_type` | VARCHAR 50 | low_potential / borderline / package_1 / package_n |
| `consent_data` | TINYINT | 1 = souhlas se zpracováním |
| `consent_marketing` | TINYINT | 1 = souhlas s marketingem |
| `ip_address` | VARCHAR 45 | IPv4 nebo IPv6 |
| `user_agent` | TEXT | Browser UA |
| `smartemailing_status` | VARCHAR 50 | pending / synced / failed / skipped |
| `smartemailing_last_response` | TEXT | Poslední odpověď API |
| `smartemailing_last_attempt_at` | DATETIME | Poslední pokus o sync |
| `cta_clicked` | TINYINT | 1 = kliknuto na CTA |
| `cta_type` | VARCHAR 50 | consultation / package |
| `cta_package_name` | VARCHAR 255 | Název poptávaného balíčku |
| `cta_at` | DATETIME | Čas kliknutí |
| `followup_sent` | TINYINT | 1 = follow-up odeslán |
| `booking_status` | VARCHAR 50 | Stav rezervace kalendáře |
| `lead_status` | VARCHAR 50 | Nový / Aktivní / Neaktivní / Uzavřený |
| `phone` | VARCHAR 20 | Telefonní číslo (volitelné) |
| `time_to_submit` | INT | Sekund od načtení do odeslání |
| `utm_source` | VARCHAR 255 | UTM parametry |
| `utm_medium` | VARCHAR 255 | |
| `utm_campaign` | VARCHAR 255 | |
| `referrer` | VARCHAR 500 | HTTP referrer |

### `{prefix}emailing_calculator_log`

| Sloupec | Typ | Poznámka |
|---|---|---|
| `id` | BIGINT | PK |
| `lead_id` | BIGINT | FK na leads |
| `changed_at` | DATETIME | Timestamp |
| `change_type` | VARCHAR 50 | Typ události |
| `note` | TEXT | Popis |

---

## Admin stránky

| Stránka | Ukládá do | AJAX/POST slug |
|---|---|---|
| Přehledy | – (analytika) | `wp_ajax_ecalc_analytics` |
| Leady | – (CRUD) | `wp_ajax_ecalc_leads`, `wp_ajax_ecalc_status` |
| Výpočet | `ecalc_settings` | `admin_post_ecalc_settings` |
| Oblasti podnikání | `ecalc_segments` | `admin_post_ecalc_segments` |
| Databáze kontaktů | `ecalc_database_ranges` | `admin_post_ecalc_database_ranges` |
| Rozsahy obratu | `ecalc_revenue_ranges` | `admin_post_ecalc_revenue_ranges` |
| Balíčky | `ecalc_packages` | `admin_post_ecalc_packages` |
| Texty výsledků | `ecalc_result_texts` | `admin_post_ecalc_result_texts` |
| Argumenty | `ecalc_arguments` | `admin_post_ecalc_save_arguments` |
| Formulář & CTA | `ecalc_settings` | `admin_post_ecalc_form_cta` |
| Info panel | `ecalc_info_panel` | `admin_post_ecalc_info_panel` |
| Vzhled | `ecalc_appearance` | `admin_post_ecalc_appearance` |
| Notifikace | `ecalc_notifications` | `admin_post_ecalc_notifications` |
| SmartEmailing | `ecalc_smartemailing` | `admin_post_ecalc_smartemailing` |
| Zabezpečení | `ecalc_turnstile` | `admin_post_ecalc_security` |
| GTM / Měření | `ecalc_settings` | `admin_post_ecalc_gtm` |

---

*Poslední aktualizace: 2026-06-08*
