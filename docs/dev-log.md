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
