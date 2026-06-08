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

### 2026-06-08 – SmartEmailing: sync stavu při manuální změně + DB tracking

**Soubory:** `includes/class-ecalc-smartemailing.php`  
**Co bylo uděláno:** Mechanismus `on_status_changed` existoval, ale neaktualizoval `smartemailing_status` v DB po provedené synchronizaci. Opraveno – po úspěšném (i neúspěšném) volání SE API se aktualizují sloupce `smartemailing_status`, `smartemailing_last_response`, `smartemailing_last_attempt_at`.  
**Jak to funguje:** Admin změní stav leadu → `update_lead_status()` → `do_action('ecalc_lead_status_changed')` → `on_status_changed()` → SE import s novým statusem tagem + custom field stavu.  
**Poznámka k tagům:** Staré status tagy se v SE nesmažou (SE API to neumožňuje v rámci importu). Pro čisté sledování stavu doporučit použití `status_customfield_id` – ten se vždy přepíše.

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
