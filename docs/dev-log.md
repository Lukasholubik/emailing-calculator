# Emailing Calculator – Vývojový deník

> **Tento soubor je první co číst.** Každá změna přibyde sem – co bylo uděláno, proč a kde v kódu.
> Nové záznamy přidávej **na začátek** (nejnovější nahoře).

---

## Workflow spolupráce

### Git & verzování
- **Repo:** `https://github.com/Lukasholubik/emailing-calculator/`
- **Push příkaz:** Napíše-li uživatel **"push"** (nebo "pošli", "pushni"), provedu bez dalšího ptaní:
  1. Bezpečnostní a penetrační audit změněného kódu (viz sekce níže)
  2. Opravím všechny nalezené problémy
  3. `git add` + `git commit` + `git push`
- **Vybízení k pushování:** Sám aktivně připomenu push po větší ucelené změně nebo sérii úprav – nikdy nenechám kód dlouho jen lokálně.
- **Commit zprávy:** Stručně popisují co a proč (česky nebo anglicky dle kontextu), ne jak.
- **Po každé změně:** Záznam do tohoto `dev-log.md` (datum, soubory, co, proč).

### Verzování a GitHub Release

Při každém bumpu verze provést **celý tento postup** (Plugin Update Checker vyžaduje GitHub Release, ne jen tag):

1. Bump `Version:` v hlavičce `emailing-calculator.php` + `define('ECALC_VERSION', ...)`
2. Aktualizovat `CHANGELOG.md`
3. `git add` + `git commit` (`chore: bump version to X.Y.Z`)
4. `git tag vX.Y.Z`
5. `git push origin main --tags`
6. Vytvořit **GitHub Release** na `https://github.com/Lukasholubik/emailing-calculator/releases/new?tag=vX.Y.Z`
   - Title: `Emailing Calculator X.Y.Z`
   - Body: changelog změn dané verze
   - Publikovat (ne Draft!)
7. Ověřit: `GET https://api.github.com/repos/Lukasholubik/emailing-calculator/releases/latest`

**Po release – vynutit kontrolu na live webu:**
`https://domena.cz/wp-admin/update-core.php?force-check=1`

### Větve (branching strategie)
- **`main`** = stabilní CORE kód – vždy funkční, vždy prošel bezpečnostním auditem.
- **Feature větve** = při každé nové funkci nebo úpravě, která by mohla narušit chod:
  - Pojmenování: `feature/nazev-funkce`, `fix/nazev-opravy`, `refactor/nazev`
  - Větev se merguje do `main` až po otestování a bezpečnostním auditu
  - Pokud se funkce nepovede → prostý `git checkout main`, větev smažeme
- **Kdy vytvořit větev:** Vždy, když přidáváme novou funkci nebo měníme existující logiku. Pro drobné opravy textu/CSS stačí přímý commit do `main`.

### Bezpečnostní audit před každým pushem
Před každým `git push` automaticky provedu kontrolu:
- **Injection:** SQL injection (přímé dotazy bez `$wpdb->prepare()`), XSS (výstup bez `esc_*`), command injection
- **Auth & capabilities:** Každý AJAX handler a REST endpoint má `check_ajax_referer()` / `verify_nonce` + `current_user_can()`
- **Citlivá data:** Žádný API klíč, heslo, secret nesmí být v kódu nebo logu v plaintextu
- **SSRF:** URL validace pro webhooku a externí požadavky
- **Sanitizace vstupů:** Všechny `$_POST`, `$_GET`, `$_REQUEST` hodnoty sanitizovány před použitím
- **Escapování výstupů:** HTML kontext `esc_html()`, atributy `esc_attr()`, URL `esc_url()`
- **Otevřené přesměrování:** `wp_safe_redirect()` místo `wp_redirect()` kde hrozí manipulace
- Pokud najdu problém → opravím **před** pushem, zapíši do dev-logu

### CSS
- **Framework: Tailwind CSS** – veškeré nové styly píšu v Tailwindu.
- Žádné vlastní CSS třídy pokud to Tailwind zvládne utility třídami.
- Inline `style=""` jen pro dynamické hodnoty (CSS custom properties z nastavení pluginu).

---

## Šablona záznamu

```
### RRRR-MM-DD – Stručný popis změny

**Soubory:** `includes/class-ecalc-xyz.php`, `templates/admin/page-xyz.php`
**Co bylo uděláno:** ...
**Proč:** ...
**Pozor na:** ... (volitelné – upozornění, side-effecty, TODO)
```

---

## Záznamy

### 2026-07-06 – Formulář: přesun kontaktních polí (URL, jméno, e-mail) na konec

**Soubory:** `templates/frontend-form.php`, `assets/js/frontend.js`, `assets/css/frontend.css`

**Co bylo uděláno:** Pole URL e-shopu, Jméno a E-mail byla přesunuta z horní části formuláře až za PNO, těsně před souhlasy – uživatel nejdřív vyplní byznysová data (obor, spotřební %, databáze, obrat, PNO) a teprve na konci kontakt. Přidána přechodová věta „Skvělé, teď už jen kontakt pro zaslání výsledků:" (`.ecalc-contact-divider`) mezi byznysovými poli a kontaktem – zdůvodňuje, proč kontakt potřebujeme, a dává pocit blízkosti dokončení.

**Beze změny zůstalo:** Moment uložení leadu (až po odeslání celého formuláře včetně kontaktu), výpočet a zobrazení výsledku (až po odeslání) – jde čistě o přeuspořádání polí, ne o změnu flow ukládání/výpočtu. Rozhodnuto s uživatelem: jednodušší a bezpečnější varianta oproti dřívějšímu nápadu ukazovat orientační číslo ještě před kontaktem.

**Musela se upravit i analytika:** `ABANDON_STEPS` pole a `fieldSteps` v `initStepTracking()` (frontend.js) měly pevné pořadí `['name','email','shop_url','segment','database','revenue','consumable','pno','consent']` – použité i pro progress bar (`furthestStep / totalSteps`). Přeuspořádáno na `['segment','consumable','database','revenue','pno','shop_url','name','email','consent']`, aby odpovídalo novému vizuálnímu pořadí – jinak by uživatel vyplňující formulář v novém pořadí dostal nesmyslné/skákající hodnoty progress baru (protože `reachStep()` porovnává jen index v poli, ne skutečné pořadí na stránce). Názvy kroků (stringy) zůstaly stejné, mění se jen pořadí v poli – nemá to dopad na PHP stranu (`ecalc_abandonment_steps` je počítadlo klíčované stejnými stringy bez ohledu na pořadí).

**Ověřeno:** živě na `/kalkulacka/` po vyčištění cache – pole se renderují ve správném pořadí, PHP/JS syntax OK.

---

### 2026-07-06 – Copywriter/SEO/UX review: konverzní vylepšení formuláře i výsledků

**Soubory:** `includes/class-ecalc-settings.php`, `includes/class-ecalc-plugin.php`, `includes/class-ecalc-admin.php`, `includes/class-ecalc-shortcode.php`, `templates/frontend-form.php`, `templates/admin/page-form.php`, `assets/js/frontend.js`, `assets/css/frontend.css`

**Co bylo uděláno (fáze 1 – vyplnění formuláře):**
1. Progress indikátor vyplnění (`.ecalc-progress-wrap`) – navázán na existující `ABANDON_STEPS`/`furthestStep` step-tracking (`reachStep()` teď volá i `updateProgressUI()`), žádná nová logika sběru dat.
2. Label PNO přejmenován na „Kolik % z tržeb chcete investovat do emailingu? (PNO)" – srozumitelnější pro laika.
3. Slider spotřebního zboží – přidán hint s konkrétními příklady (0 % = nábytek/elektronika, 50 % = smíšený sortiment, 100 % = drogerie/doplňky stravy/krmivo).
4. Mikrocopy pod tlačítkem „Vypočítat" – nové nastavení `form_submit_note` (výchozí „Zdarma · Bez závazků · Výsledek za pár vteřin").
5. Sociální důkaz – nová nastavení `social_proof_shortcode`/`social_proof_enabled_form`/`social_proof_enabled_result` v `ecalc_settings`. Shortcode (Trustindex `[trustindex no-registration=google]`) se renderuje přes `do_shortcode()` staticky v `frontend-form.php` – jednou v info panelu (`.ecalc-info-col`), jednou jako trvalý sourozenec `#ecalc-result-inner` uvnitř `#ecalc-result` (zobrazí/skryje se spolu s celým výsledkovým sloupcem, JS jen manipuluje `display` rodiče).

**Co bylo uděláno (fáze 2 – konverze po výsledku):**
6. `pno_over_label` – nahrazuje alarmující „Překračuje vaše PNO" neutrálním „Nad vaším zadaným PNO" u `buildPackageCard()`; barva badge změněna z červené na jantarovou (`.ecalc-package-pno--over`).
7. `inquiry_msg` – konkrétní slib „Ozveme se vám do 24 hodin" místo vágního „v nejbližší možné době". Tyto texty (`inquiry_title/pkg_label/msg/close/visit`) byly dřív **hardcoded** v `class-ecalc-plugin.php` – nyní čtou z `ecalc_settings` (editovatelné v adminu).
8. `cta_consultation_note` – doplňkový text u CTA konzultace („30 min – projdeme vaše čísla a návrh strategie"), zobrazí se v `consultationStatCard()`.
9. Sekce „Proč máte tento potenciál" (viz předchozí záznam) dostala vlastní sekundární CTA tlačítko (`.ecalc-arguments-cta`, outline varianta) – dřív bylo CTA jen na konci stránky.
10. Benefit věta doplněna do popisu obou balíčků (`ecalc_packages`) – **patchnuto přímo v DB** (ne jen v defaultech), protože option už byl na tomto webu uložený a `get_packages()` needoplňuje `description` z defaultů.

**Nové admin UI:** Formulář & CTA → sekce „Mikrocopy formuláře", „Reference / sociální důkaz", „Poděkování po poptání balíčku" + nové pole u CTA konzultace.

**Ověřeno:** živé vykreslení stránky `/kalkulacka/` (do_shortcode Trustindexu skutečně vykreslil recenze), PHP/JS syntax checky.

**Pozor na:** Trustindex shortcode se renderuje 2× na stránce (info panel + výsledek), oba instance generují stejný `<template id="trustindex-google-widget-html">`. HTML duplicitní ID není ideální, ale template obsah není live DOM (inertní), a `data-src` loader mechanismus Trustindexu je stavěný na více instancí na stránce – funkčně by to mělo být v pořádku, ale stálo by za vizuální ověření po nasazení na produkci.

**Odloženo (na příště):** Velká restrukturalizace formuláře – přesun jména/e-mailu až na konec (nejdřív byznysová data, kontakt až před zobrazením detailního rozboru). Uživatel potvrdil, že tohle chce řešit jako další krok, ne v rámci téhle session – je to zásah do ukládání leadu, abandonment trackingu i analytiky, chce vlastní projekt.

**Dodatečná úprava – mobile first (web má 70 % provozu z mobilu):** Reference u formuláře byly původně navrženy do `.ecalc-info-col` (postranní tmavá karta). Layout se ale pod `800px` řadí do jednoho sloupce v pořadí formulář (`grid-row: 1`) → info panel (`row: 2`) → výsledek (`row: 3`) – protože tlačítko „Vypočítat" je součástí formuláře, na mobilu by se reference zobrazily **až za tlačítkem**, tedy v momentě, kdy už nemají na rozhodnutí žádný vliv. Přesunuto: sociální důkaz (`.ecalc-social-proof-inline`) je nyní přímo uvnitř `<form>`, těsně nad tlačítkem – funguje shodně na mobilu i desktopu, žádná duplicita widgetu na pre-submission straně (jen `.ecalc-social-proof-result` zůstává u výsledku). Soubory: `templates/frontend-form.php`, `assets/css/frontend.css`, `templates/admin/page-form.php` (upravený popisek nastavení).

---

### 2026-07-06 – Sekce "Proč máte tento potenciál" (argumenty u pozitivního/hraničního výsledku)

**Soubory:** `includes/class-ecalc-settings.php`, `includes/class-ecalc-calculator.php`, `includes/class-ecalc-rest.php`, `includes/class-ecalc-admin.php`, `templates/admin/page-arguments.php`, `assets/js/frontend.js`, `assets/css/frontend.css`

**Co bylo uděláno:**
1. Nová option `ecalc_arguments` (`ECAlc_Settings::get_arguments()/save_arguments()`) – texty pro 3 faktory výpočtu (spotřební zboží, databáze, segment) × 3 pásma skóre (nízké/střední/vysoké) + shrnující věta + 2 prahy pásem (`threshold_medium` 0,34, `threshold_high` 0,67) + zapnuto/vypnuto.
2. `ECAlc_Calculator::build_arguments()` – po výpočtu vybere pásmo pro `consumable_score`, `database_score`, `segment_score` (metoda `score_band()`), dosadí placeholdery přes existující `ecalc_replace_variables()` a vrátí `['title', 'items' => [...], 'summary']`. Počítá se jen pro `result_type` = `package_1` / `package_n` / `borderline` (u `low_potential` se nezobrazuje).
3. REST `/calculate` – přidán klíč `result.arguments` (escapováno přes novou privátní metodu `esc_arguments()` v `class-ecalc-rest.php`).
4. Nová admin stránka **Argumenty** (`ecalc_arguments`, submenu vedle „Texty výsledků") – textarea na každý ze 3×3 textů + shrnutí, konfigurace prahů pásem.
5. `frontend.js` `buildResultHTML()` – nová sekce `.ecalc-arguments` mezi `res.text` a sekcí balíčků, renderuje se jen když `res.arguments.items` není prázdné.

**Proč:** Uživatel chtěl u doporučeného balíčku vysvětlit *proč* má e-shop daný potenciál – konkrétní argumenty k opakovanému nákupu, velikosti databáze a oboru, ne jen číslo.

**Pozor na:** Placeholder `{consumable_percentage}` (i `{final_potential}` apod.) v `ecalc_replace_variables()` už sám přidává „ %" – v textech se nesmí psát `{consumable_percentage} %` (vzniklo by „80 % %"). Ověřeno přímým voláním `ECAlc_Calculator::calculate()` s testovacími daty (package_n i low_potential scénář).

**Doplněno:** Přidáno pole `subtitle` (`ecalc_arguments`) – vysvětlivka pod nadpisem sekce, výchozí text jasně říká, že jde o „3 hlavní důvody – nejde o kompletní výčet", aby bylo zřejmé, že existují i další faktory mimo tyto tři.

---

### 2026-06-25 – SmartEmailing: Nízký potenciál – hodnota balíčku + odeslání bez souhlasu

**Soubory:** `includes/class-ecalc-settings.php`, `includes/class-ecalc-smartemailing.php`, `includes/class-ecalc-admin.php`, `templates/admin/page-smartemailing.php`

**Co bylo uděláno:**
1. Přidána konfigurovatelná hodnota `low_potential_se_value` (výchozí `Nízký potenciál`) – odesílá se do SE custom pole „Doporučený balíček" pro leady s výsledkem Nízký potenciál. Dříve bylo pole prázdné (recommended_package_name = '').
2. Přidána nová metoda `get_cf_package_value()` v `ECAlc_SmartEmailing` – centralizuje logiku hodnoty balíčku pro SE (low_potential → konfigurovaný text, ostatní → původní logika přes `get_package_se_value()`).
3. Přidáno nastavení `send_low_potential_without_consent` (výchozí vypnuto) – umožňuje odeslat Nízký potenciál do SE i bez marketingového souhlasu (přepíše podmínku `require_marketing_consent` jen pro tento výsledkový typ).
4. Admin šablona: nová pole v sekci Připojení a Mapování custom polí.

**Proč:** Leady s nízkým potenciálem nedostávali žádnou hodnotu v SE poli „Doporučený balíček" – pole bylo prázdné, což znemožňovalo filtrování/segmentaci v SE automacích.

### 2026-06-08 – SmartEmailing: sync stavu při manuální změně + DB tracking

**Soubory:** `includes/class-ecalc-smartemailing.php`  
**Co bylo uděláno:** Mechanismus `on_status_changed` existoval, ale neaktualizoval `smartemailing_status` v DB po provedené synchronizaci. Opraveno – po úspěšném (i neúspěšném) volání SE API se aktualizují sloupce `smartemailing_status`, `smartemailing_last_response`, `smartemailing_last_attempt_at`.  
**Jak to funguje:** Admin změní stav leadu → `update_lead_status()` → `do_action('ecalc_lead_status_changed')` → `on_status_changed()` → SE import s novým statusem tagem + custom field stavu.  
**Poznámka k tagům:** Staré status tagy se v SE nesmažou (SE API to neumožňuje v rámci importu). Pro čisté sledování stavu doporučit použití `status_customfield_id` – ten se vždy přepíše.

---

### 2026-06-08 – Odstraněna IP-based spam ochrana (nefunkční za proxy/Cloudflare)

**Soubory:** `includes/class-ecalc-rest.php`  
**Co bylo uděláno:** Odstraněna metoda `send_to_se_with_spam_check` a IP-based spam ochrana. Na live webu za Cloudflare/reverse proxy vrací `REMOTE_ADDR` IP proxy serveru → všichni návštěvníci sdíleli stejný čítač → po 4 různých e-mailech se blokoval SE import pro všechny. Kontakty se neodesílaly nebo se chybně přeskakoval. Vráceno přímé volání `$this->smartemailing->send_lead()`.  
**Dostatečná ochrana:** existující per-email rate limit (max 10 přepočtů/hodinu) v `handle_calculate()`.  
**Poznámka k blacklistu:** SE samo označuje kontakty jako blacklisted pokud byli dříve odhlášeni – to je SE chování, ne chyba pluginu.

---

### 2026-06-08 – SmartEmailing: ochrana proti spamu při přepočtu s jiným e-mailem

**Soubory:** `includes/class-ecalc-rest.php`  
**Co bylo uděláno:** Přidána metoda `send_to_se_with_spam_check()`. Sleduje počet různých e-mailových adres odeslaných z jedné IP za hodinu (transient `ecalc_ip_emails_{hash}`). Pokud stejná IP pošle 4 a více různých e-mailů, SE import se přeskočí (status `skipped_spam_protection`). 1–3 různé e-maily = normální chování, import proběhne.  
**Proč:** Klient mohl kliknout na špatný e-mail a přepočítat → to je OK a má se propisovat. Ale 4+ různých e-mailů = pravděpodobný spam nebo abuse.  
**Pozor na:** Transient se resetuje po hodině. Pokud reálný klient legitimně testuje více adres, může se mu zastavit sync po 4. adrese – to je akceptovatelné.

---

### 2026-06-08 – Balíčky: vlastní název pro SmartEmailing (se_value)

**Soubory:** `templates/admin/page-packages.php`, `includes/class-ecalc-admin.php`, `includes/class-ecalc-smartemailing.php`  
**Co bylo uděláno:** Každý balíček má nové pole „Název v SmartEmailingu" (`se_value`). Tato hodnota se pošle do SE custom pole `cf_package` místo automatického názvu balíčku. Pokud je prázdné, použije se název balíčku jako fallback. Funguje i pro nově přidané balíčky.  
**Datová struktura:** `$pkg['se_value'] = 'VIP'` → do SE jde `VIP` místo `Premium`.

---

**Soubory:** `templates/admin/page-packages.php`, `includes/class-ecalc-admin.php`, `includes/class-ecalc-smartemailing.php`  
**Co bylo uděláno:** Každý balíček má novou sekci „SmartEmailing – custom pole" kde lze přidat libovolný počet párů (ID pole → Hodnota). Stránka zobrazuje která SE custom pole jsou nakonfigurována globálně (pro orientaci). Data se ukládají do `custom_fields` klíče každého balíčku v `ecalc_packages`. Při importu leadu se per-balíčková custom pole automaticky přidají k ostatním mapovaným polím.  
**Datová struktura:** `$pkg['custom_fields'] = [['field_id' => 15, 'value' => 'Business'], ...]`

---

### 2026-06-08 – SmartEmailing: průvodce, oprava balíčku, bulk export

**Soubory:** `templates/admin/page-smartemailing.php`, `includes/class-ecalc-smartemailing.php`, `includes/class-ecalc-admin.php`  
**Co bylo uděláno:**
1. **Průvodce nastavením** – rozbalovací sekce na stránce SmartEmailing vysvětluje: jak napojit SE, jaká custom pole vytvořit v SE (typ, co se ukládá, příklady hodnot), jak fungují tagy, kde najít ID custom pole.
2. **Oprava `cf_package`** – místo ID balíčku (`package_1`) se nyní odesílá **název balíčku** (`recommended_package_name`) přesně jak je zadán v sekci Balíčky.
3. **Bulk export** – nová sekce na stránce SE umožňuje exportovat historické leady do SmartEmailingu. Výběr: všechny leady nebo vlastní rozsah dat (od–do). AJAX handler `ecalc_bulk_export_smartemailing` + metoda `bulk_export()` v SE třídě. Nonce: `ecalc_bulk_export`.  
**Pozor na:** Bulk export může trvat déle u velkých databází – PHP timeout. Pro tisíce leadů zvážit batch processing.

---

### 2026-06-08 – Vytvoření dokumentační složky

**Soubory:** `docs/overview.md`, `docs/settings-reference.md`, `docs/dev-log.md`  
**Co bylo uděláno:** Zřízena složka `docs/` s přehledem architektury pluginu, referencí všech option klíčů a tímto vývojovým deníkem.  
**Proč:** Zajistit kontinuitu mezi session – rychlé zorientování bez nutnosti znovu procházet zdrojový kód.

---

### (dříve) – Cloudflare Turnstile integrace

**Soubory:** `includes/class-ecalc-rest.php`, `includes/class-ecalc-plugin.php`, `templates/admin/page-security.php`  
**Co bylo uděláno:** Přidána bot-ochrana formuláře přes Cloudflare Turnstile. Server-side ověření tokenu při `/calculate`. Admin stránka Zabezpečení pro správu klíčů.  
**Proč:** Ochrana před boty a spam submissiony.  
**Pozor na:** Token je single-use – přeskakuje se při recalculation (uživatel přepočítává bez nového challange).  
**Klíče:** Uloženy v DB option `ecalc_turnstile` a v interní paměti projektu. Do gitu se klíče nikdy nezapisují.

---

### (dříve) – Verze 1.0.0 → 1.4.0 – Vytvoření a rozvoj pluginu

**Co bylo vytvořeno:**
- ROI kalkulačka s výpočetním enginem (váhy 70/20/10, potenciál 15–45 %)
- 34 segmentů e-shopů s individuálními skóre
- 5 rozsahů databáze, 6 rozsahů obratu
- Správa balíčků (ceny, položky, popis)
- 4 typy výsledků: low_potential, borderline, package_1, package_n
- DB tabulky: leads (UNIQUE email) + log
- REST API: 8 endpointů
- Shortcode `[emailing_calculator]`
- Admin: 15 stránek (přehledy, leady, nastavení, vzhled, notifikace, SmartEmailing, GTM)
- E-mailové notifikace: admin, klient, follow-up, inquiry, konzultace
- Follow-up cron (configurable hours)
- SmartEmailing sync (kontakty, custom fields, tagy)
- Analytika: time series, konverze, segmenty, abandonment funnel
- Rate limiting, honeypot, token ochrana, nonce
- CTA tracking (konzultace + poptávka balíčku)
- Booking calendar status tracking
- Phone capture (volitelné, po výsledcích)
- GTM eventy (form_start, form_submit, calculation_success, cta_click…)
- CSS custom properties pro kompletní přebarvení
- Plugin Update Checker (GitHub releases)
- Grou.cz admin skupina

---
