<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ecalc-admin">
<h1>GTM / Měření – dokumentace dataLayer</h1>

<p style="color:#475569;margin-bottom:24px;max-width:780px;">
	Kalkulačka automaticky odesílá události do <code>window.dataLayer</code> při každé klíčové interakci uživatele.
	Stránka slouží jako referenční přehled pro nastavení Google Tag Manageru.
</p>

<style>
.ecalc-gtm-section { margin-bottom: 36px; }
.ecalc-gtm-section h2 { font-size: 15px; margin: 0 0 12px; padding: 10px 16px; background: #1e293b; color: #fff; border-radius: 6px; font-family: monospace; font-weight: 600; letter-spacing: .3px; }
.ecalc-gtm-section h2 .ecalc-gtm-badge { display:inline-block; font-size:10px; font-family:sans-serif; font-weight:700; padding:2px 8px; border-radius:20px; margin-left:12px; vertical-align:middle; letter-spacing:.5px; text-transform:uppercase; }
.ecalc-gtm-badge--conv  { background:#4ade80; color:#14532d; }
.ecalc-gtm-badge--micro { background:#fbbf24; color:#78350f; }
.ecalc-gtm-badge--info  { background:#94a3b8; color:#1e293b; }
.ecalc-gtm-table { width:100%; border-collapse:collapse; font-size:13px; background:#fff; border:1px solid #e2e8f0; border-radius:6px; overflow:hidden; }
.ecalc-gtm-table th { background:#f8fafc; padding:8px 14px; text-align:left; font-weight:600; color:#475569; border-bottom:1px solid #e2e8f0; font-size:11px; text-transform:uppercase; letter-spacing:.5px; }
.ecalc-gtm-table td { padding:8px 14px; border-bottom:1px solid #f1f5f9; vertical-align:top; }
.ecalc-gtm-table tr:last-child td { border-bottom:none; }
.ecalc-gtm-table code { background:#f1f5f9; padding:2px 6px; border-radius:4px; font-size:12px; color:#0f172a; }
.ecalc-gtm-table td:first-child code { color:#7c3aed; }
.ecalc-gtm-note { font-size:12px; color:#64748b; margin-top:8px; padding: 8px 12px; background:#f8fafc; border-left:3px solid #cbd5e1; border-radius:0 4px 4px 0; }
.ecalc-gtm-attr-table { width:100%; border-collapse:collapse; font-size:13px; background:#fff; border:1px solid #e2e8f0; border-radius:6px; overflow:hidden; margin-bottom:8px; }
.ecalc-gtm-attr-table th { background:#f8fafc; padding:8px 14px; text-align:left; font-weight:600; color:#475569; border-bottom:1px solid #e2e8f0; font-size:11px; text-transform:uppercase; letter-spacing:.5px; }
.ecalc-gtm-attr-table td { padding:8px 14px; border-bottom:1px solid #f1f5f9; vertical-align:top; }
.ecalc-gtm-attr-table tr:last-child td { border-bottom:none; }
.ecalc-gtm-attr-table code { background:#f1f5f9; padding:2px 6px; border-radius:4px; font-size:12px; color:#0f172a; }
.ecalc-gtm-how { background:#fff; border:1px solid #e2e8f0; border-radius:6px; padding:20px 24px; }
.ecalc-gtm-how ol { margin:8px 0 0 20px; line-height:1.9; font-size:13px; color:#334155; }
.ecalc-gtm-how code { background:#f1f5f9; padding:2px 6px; border-radius:4px; font-size:12px; color:#0f172a; }
.ecalc-gtm-cols { display:grid; grid-template-columns:1fr 1fr; gap:24px; }
@media (max-width:900px) { .ecalc-gtm-cols { grid-template-columns:1fr; } }
.ecalc-gtm-legend { display:flex; gap:20px; margin-bottom:20px; flex-wrap:wrap; }
.ecalc-gtm-legend span { font-size:12px; display:flex; align-items:center; gap:6px; }
</style>

<div class="ecalc-gtm-legend">
	<span><span class="ecalc-gtm-badge ecalc-gtm-badge--conv">Konverze</span> Primární cíle – používej v Google Ads / GA4 jako konverze</span>
	<span><span class="ecalc-gtm-badge ecalc-gtm-badge--micro">Mikro</span> Mikrokonverze – zapojení uživatele</span>
	<span><span class="ecalc-gtm-badge ecalc-gtm-badge--info">Info</span> Informační – pro analýzu chování</span>
</div>

<!-- ================================================================ -->
<!-- FORM EVENTS -->
<!-- ================================================================ -->
<div class="ecalc-gtm-section">
	<h2>ecalc_form_start <span class="ecalc-gtm-badge ecalc-gtm-badge--micro">Mikro</span></h2>
	<p class="ecalc-gtm-note">Odeslána jednou při první interakci uživatele s formulářem (focus na libovolné pole). Slouží k měření míry zapojení.</p>
	<table class="ecalc-gtm-table"><thead><tr><th>Proměnná</th><th>Popis</th><th>Příklad</th></tr></thead><tbody>
		<tr><td colspan="3" style="color:#94a3b8;font-style:italic;text-align:center;">Žádné extra proměnné – pouze název eventu</td></tr>
	</tbody></table>
</div>

<div class="ecalc-gtm-section">
	<h2>ecalc_form_submit <span class="ecalc-gtm-badge ecalc-gtm-badge--micro">Mikro</span></h2>
	<p class="ecalc-gtm-note">Odeslána po kliknutí na "Vypočítat" a úspěšném prošlení frontendové validace – těsně před odesláním na server.</p>
	<table class="ecalc-gtm-table"><thead><tr><th>Proměnná dataLayer</th><th>Popis</th><th>Příklad hodnoty</th></tr></thead><tbody>
		<tr><td><code>ecalc_segment</code></td><td>Zvolený segment e-shopu</td><td><code>Kosmetika a péče o tělo</code></td></tr>
		<tr><td><code>ecalc_database_range</code></td><td>Velikost databáze kontaktů</td><td><code>2 001–5 000</code></td></tr>
		<tr><td><code>ecalc_revenue_type</code></td><td>Způsob zadání obratu</td><td><code>range</code> nebo <code>exact</code></td></tr>
		<tr><td><code>ecalc_revenue_range</code></td><td>Zvolený rozsah obratu (pokud range)</td><td><code>500 001–1 000 000 Kč</code></td></tr>
		<tr><td><code>ecalc_monthly_revenue</code></td><td>Přesný obrat v Kč (pokud exact)</td><td><code>750000</code></td></tr>
		<tr><td><code>ecalc_consumable_pct</code></td><td>Podíl spotřebního zboží v %</td><td><code>70</code></td></tr>
		<tr><td><code>ecalc_expected_pno</code></td><td>Očekávané PNO v %</td><td><code>10</code></td></tr>
		<tr><td><code>ecalc_consent_marketing</code></td><td>Souhlas s marketingem (0/1)</td><td><code>1</code></td></tr>
	</tbody></table>
</div>

<!-- ================================================================ -->
<!-- RESULT EVENTS -->
<!-- ================================================================ -->
<div class="ecalc-gtm-section">
	<h2>ecalc_calculation_success <span class="ecalc-gtm-badge ecalc-gtm-badge--micro">Mikro</span></h2>
	<p class="ecalc-gtm-note">Odeslána po úspěšném výpočtu a zobrazení výsledku. Obsahuje kompletní data výsledku – ideal pro segmentaci v GA4 nebo Looker Studio.</p>
	<table class="ecalc-gtm-table"><thead><tr><th>Proměnná dataLayer</th><th>Popis</th><th>Příklad hodnoty</th></tr></thead><tbody>
		<tr><td><code>ecalc_lead_id</code></td><td>ID leadu v databázi pluginu</td><td><code>42</code></td></tr>
		<tr><td><code>ecalc_result_type</code></td><td>Typ výsledku výpočtu</td><td><code>low_potential</code> / <code>borderline</code> / <code>package_1</code> / <code>package_n</code></td></tr>
		<tr><td><code>ecalc_segment</code></td><td>Segment e-shopu</td><td><code>Kosmetika a péče o tělo</code></td></tr>
		<tr><td><code>ecalc_database_range</code></td><td>Velikost databáze</td><td><code>2 001–5 000</code></td></tr>
		<tr><td><code>ecalc_monthly_revenue</code></td><td>Měsíční obrat v Kč</td><td><code>750000</code></td></tr>
		<tr><td><code>ecalc_expected_pno</code></td><td>Očekávané PNO v %</td><td><code>10</code></td></tr>
		<tr><td><code>ecalc_consumable_pct</code></td><td>Podíl spotřebního zboží v %</td><td><code>70</code></td></tr>
		<tr><td><code>ecalc_final_potential</code></td><td>Vypočtený potenciál emailingu v %</td><td><code>28.5</code></td></tr>
		<tr><td><code>ecalc_emailing_revenue_low</code></td><td>Konzervativní odhad obratu z emailingu (Kč)</td><td><code>178500</code></td></tr>
		<tr><td><code>ecalc_emailing_revenue_mid</code></td><td>Střední odhad obratu z emailingu (Kč)</td><td><code>210000</code></td></tr>
		<tr><td><code>ecalc_emailing_revenue_high</code></td><td>Optimistický odhad obratu z emailingu (Kč)</td><td><code>241500</code></td></tr>
		<tr><td><code>ecalc_available_budget</code></td><td>Doporučený budget (Kč) při zadaném PNO</td><td><code>75000</code></td></tr>
		<tr><td><code>ecalc_recommended_package</code></td><td>Název doporučeného balíčku</td><td><code>Výkonnostní emailing</code></td></tr>
		<tr><td><code>ecalc_recommended_package_price</code></td><td>Cena doporučeného balíčku (Kč/měs.)</td><td><code>22000</code></td></tr>
		<tr><td><code>ecalc_is_updated</code></td><td>Přepočet vracejícího se uživatele</td><td><code>true</code> / <code>false</code></td></tr>
	</tbody></table>
</div>

<div class="ecalc-gtm-section">
	<h2>ecalc_calculation_error <span class="ecalc-gtm-badge ecalc-gtm-badge--info">Info</span></h2>
	<p class="ecalc-gtm-note">Odeslána když server vrátí chybu (rate limit, validace, Turnstile…). Pomáhá odhalit problémy v produkci.</p>
	<table class="ecalc-gtm-table"><thead><tr><th>Proměnná dataLayer</th><th>Popis</th><th>Příklad hodnoty</th></tr></thead><tbody>
		<tr><td><code>ecalc_error_code</code></td><td>Strojový kód chyby</td><td><code>rate_limit</code> / <code>turnstile_failed</code> / <code>invalid_input</code></td></tr>
		<tr><td><code>ecalc_error_message</code></td><td>Textový popis chyby</td><td><code>Příliš mnoho přepočtů. Zkuste to prosím za hodinu.</code></td></tr>
	</tbody></table>
</div>

<!-- ================================================================ -->
<!-- DUPLICATE EVENTS -->
<!-- ================================================================ -->
<div class="ecalc-gtm-section">
	<h2>ecalc_duplicate_dialog <span class="ecalc-gtm-badge ecalc-gtm-badge--info">Info</span></h2>
	<p class="ecalc-gtm-note">Dialog "Již vás máme v databázi" byl zobrazen vracejícímu se uživateli.</p>
	<table class="ecalc-gtm-table"><thead><tr><th>Proměnná dataLayer</th><th>Popis</th><th>Příklad hodnoty</th></tr></thead><tbody>
		<tr><td><code>ecalc_duplicate_status</code></td><td>Aktuální stav existujícího leadu</td><td><code>Čekání</code> / <code>Poptáno</code> / <code>Schůzka</code></td></tr>
		<tr><td><code>ecalc_is_active_lead</code></td><td>Zda má lead aktivní obchodní stav</td><td><code>true</code> / <code>false</code></td></tr>
	</tbody></table>
</div>

<div class="ecalc-gtm-section">
	<h2>ecalc_duplicate_confirmed <span class="ecalc-gtm-badge ecalc-gtm-badge--info">Info</span></h2>
	<p class="ecalc-gtm-note">Uživatel potvrdil přepočet v dialogu duplicitního emailu. Žádné extra proměnné.</p>
</div>

<!-- ================================================================ -->
<!-- CTA EVENTS -->
<!-- ================================================================ -->
<div class="ecalc-gtm-section">
	<h2>ecalc_cta_click <span class="ecalc-gtm-badge ecalc-gtm-badge--conv">Konverze</span></h2>
	<p class="ecalc-gtm-note">Klik na hlavní CTA tlačítko "Chci konzultaci zdarma". Spustí booking modal nebo přesměruje na URL dle nastavení. Toto je <strong>primární konverze</strong>.</p>
	<table class="ecalc-gtm-table"><thead><tr><th>Proměnná dataLayer</th><th>Popis</th><th>Příklad hodnoty</th></tr></thead><tbody>
		<tr><td><code>ecalc_cta_type</code></td><td>Typ CTA akce</td><td><code>consultation</code></td></tr>
		<tr><td><code>ecalc_cta_label</code></td><td>Text tlačítka</td><td><code>Chci konzultaci zdarma</code></td></tr>
		<tr><td><code>ecalc_lead_id</code></td><td>ID leadu</td><td><code>42</code></td></tr>
	</tbody></table>
</div>

<div class="ecalc-gtm-section">
	<h2>ecalc_package_cta_click <span class="ecalc-gtm-badge ecalc-gtm-badge--conv">Konverze</span></h2>
	<p class="ecalc-gtm-note">Klik na tlačítko "Poptat balíček" u konkrétního balíčku. Zobrazí děkovací popup. Toto je <strong>sekundární konverze</strong>.</p>
	<table class="ecalc-gtm-table"><thead><tr><th>Proměnná dataLayer</th><th>Popis</th><th>Příklad hodnoty</th></tr></thead><tbody>
		<tr><td><code>ecalc_cta_type</code></td><td>Typ CTA akce</td><td><code>package</code></td></tr>
		<tr><td><code>ecalc_package_name</code></td><td>Název poptávaného balíčku</td><td><code>Výkonnostní emailing</code></td></tr>
		<tr><td><code>ecalc_lead_id</code></td><td>ID leadu</td><td><code>42</code></td></tr>
	</tbody></table>
</div>

<!-- ================================================================ -->
<!-- BOOKING EVENTS -->
<!-- ================================================================ -->
<div class="ecalc-gtm-section">
	<h2>ecalc_booking_modal_open <span class="ecalc-gtm-badge ecalc-gtm-badge--micro">Mikro</span></h2>
	<p class="ecalc-gtm-note">Booking modal byl otevřen (iframe s kalendářem). Funguje pouze pokud je v nastavení vyplněna Booking URL.</p>
	<table class="ecalc-gtm-table"><thead><tr><th>Proměnná dataLayer</th><th>Popis</th><th>Příklad hodnoty</th></tr></thead><tbody>
		<tr><td><code>ecalc_cta_type</code></td><td>Typ akce</td><td><code>consultation</code></td></tr>
		<tr><td><code>ecalc_lead_id</code></td><td>ID leadu</td><td><code>42</code></td></tr>
	</tbody></table>
</div>

<div class="ecalc-gtm-section">
	<h2>ecalc_booking_confirmed <span class="ecalc-gtm-badge ecalc-gtm-badge--conv">Konverze</span></h2>
	<p class="ecalc-gtm-note">Uživatel potvrdil, že provedl rezervaci termínu. Nejsilnější signál záměru – doporučujeme použít jako primární konverzi v Google Ads.</p>
	<table class="ecalc-gtm-table"><thead><tr><th>Proměnná dataLayer</th><th>Popis</th><th>Příklad hodnoty</th></tr></thead><tbody>
		<tr><td><code>ecalc_cta_type</code></td><td>Typ akce</td><td><code>consultation</code></td></tr>
		<tr><td><code>ecalc_lead_id</code></td><td>ID leadu</td><td><code>42</code></td></tr>
	</tbody></table>
</div>

<div class="ecalc-gtm-section">
	<h2>ecalc_booking_declined <span class="ecalc-gtm-badge ecalc-gtm-badge--info">Info</span></h2>
	<p class="ecalc-gtm-note">Uživatel zavřel booking modal a odmítl rezervaci. Užitečné pro analýzu drop-off v konverzním trychtýři.</p>
	<table class="ecalc-gtm-table"><thead><tr><th>Proměnná dataLayer</th><th>Popis</th><th>Příklad hodnoty</th></tr></thead><tbody>
		<tr><td><code>ecalc_cta_type</code></td><td>Typ akce</td><td><code>consultation</code></td></tr>
		<tr><td><code>ecalc_lead_id</code></td><td>ID leadu</td><td><code>42</code></td></tr>
	</tbody></table>
</div>

<!-- ================================================================ -->
<!-- DATA ATTRIBUTES -->
<!-- ================================================================ -->
<div class="ecalc-gtm-section">
	<h2>data-gtm-id atributy (Click trigger bez JS)</h2>
	<p class="ecalc-gtm-note">Klíčové prvky mají atribut <code>data-gtm-id</code> pro snadné nastavení Click triggerů v GTM bez nutnosti psát vlastní JS. Stačí použít trigger "All Elements" nebo "Just Links" s podmínkou <strong>Click Element matches CSS selector</strong>.</p>
	<table class="ecalc-gtm-attr-table"><thead><tr><th>Selektor</th><th>Prvek</th><th>Doplňující atributy</th></tr></thead><tbody>
		<tr>
			<td><code>[data-gtm-id="ecalc-form-submit"]</code></td>
			<td>Tlačítko "Vypočítat potenciál emailingu"</td>
			<td>—</td>
		</tr>
		<tr>
			<td><code>[data-gtm-id="ecalc-cta-consultation"]</code></td>
			<td>Hlavní CTA "Chci konzultaci zdarma"</td>
			<td>—</td>
		</tr>
		<tr>
			<td><code>[data-gtm-id="ecalc-package-cta"]</code></td>
			<td>Tlačítko "Poptat balíček"</td>
			<td><code>data-gtm-package</code> = název balíčku<br><code>data-gtm-recommended</code> = <code>1</code> / <code>0</code></td>
		</tr>
	</tbody></table>
	<p class="ecalc-gtm-note">Hodnotu <code>data-gtm-package</code> přečteš v GTM jako Built-in variable <strong>Click Element</strong> → <code>getAttribute('data-gtm-package')</code> nebo přes <strong>DOM Element</strong> proměnnou.</p>
</div>

<!-- ================================================================ -->
<!-- HOW TO SETUP -->
<!-- ================================================================ -->
<div class="ecalc-gtm-section">
	<h2>Jak nastavit v GTM – rychlý průvodce</h2>
	<div class="ecalc-gtm-cols">
		<div class="ecalc-gtm-how">
			<strong>1. Proměnné dataLayer</strong>
			<ol>
				<li>GTM → Proměnné → Nová → <strong>Proměnná datové vrstvy</strong></li>
				<li>Název proměnné vrstvy dat: <code>ecalc_result_type</code></li>
				<li>Opakuj pro každou proměnnou kterou chceš použít</li>
				<li>Pojmenuj proměnnou stejně, např. <code>dlv_ecalc_result_type</code></li>
			</ol>
		</div>
		<div class="ecalc-gtm-how">
			<strong>2. Triggery (Custom Event)</strong>
			<ol>
				<li>GTM → Triggery → Nový → <strong>Vlastní událost</strong></li>
				<li>Název události: přesný název eventu, např. <code>ecalc_booking_confirmed</code></li>
				<li>Pro všechny ecalc eventy najednou: <code>ecalc_.*</code> (regex)</li>
				<li>Trigger přiřaď ke konkrétní značce (GA4 event, Google Ads konverze…)</li>
			</ol>
		</div>
		<div class="ecalc-gtm-how">
			<strong>3. GA4 – doporučené nastavení</strong>
			<ol>
				<li>Značka GA4 Event → trigger <code>ecalc_booking_confirmed</code> → event <code>generate_lead</code></li>
				<li>Parametry: <code>ecalc_lead_id</code>, <code>ecalc_result_type</code>, <code>ecalc_recommended_package</code></li>
				<li>Mikro: <code>ecalc_calculation_success</code> → event <code>ecalc_result</code> + všechny parametry výsledku</li>
				<li>Veškeré ecalc_* parametry zaregistruj v GA4 jako vlastní dimenze/metriky</li>
			</ol>
		</div>
		<div class="ecalc-gtm-how">
			<strong>4. Konverzní trychtýř</strong>
			<ol>
				<li><code>ecalc_form_start</code> → Povědomí / Zapojení</li>
				<li><code>ecalc_form_submit</code> → Záměr</li>
				<li><code>ecalc_calculation_success</code> → Kvalifikace</li>
				<li><code>ecalc_cta_click</code> nebo <code>ecalc_package_cta_click</code> → Lead</li>
				<li><code>ecalc_booking_confirmed</code> → Kvalifikovaný lead (SQL)</li>
			</ol>
		</div>
	</div>
</div>

</div>
