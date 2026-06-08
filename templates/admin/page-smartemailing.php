<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap ecalc-admin">
	<h1>SmartEmailing integrace</h1>

	<?php if ( $notice['type'] ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible"><p><?php echo esc_html( $notice['message'] ); ?></p></div>
	<?php endif; ?>

	<!-- ── Průvodce nastavením ──────────────────────────────────────── -->
	<div class="ecalc-se-guide" style="background:#fff;border:1px solid #2271b1;border-left:4px solid #2271b1;border-radius:6px;padding:20px 24px;margin-bottom:24px;">
		<div style="display:flex;align-items:center;justify-content:space-between;cursor:pointer;" id="ecalc-se-guide-toggle">
			<h2 style="margin:0;font-size:1rem;">📖 Průvodce nastavením SmartEmailing integrace</h2>
			<span id="ecalc-se-guide-arrow" style="font-size:1.1rem;color:#2271b1;">▼</span>
		</div>

		<div id="ecalc-se-guide-body" style="margin-top:18px;">

			<h3 style="margin:0 0 8px;color:#1d2327;">① Jak napojit SmartEmailing</h3>
			<ol style="margin:0 0 20px 18px;line-height:2;">
				<li>Přihlaste se do SmartEmailingu → <strong>Nastavení → Přístupy a API → API</strong> → zkopírujte API klíč a login (e-mail).</li>
				<li>V SmartEmailingu vytvořte seznam kontaktů (nebo použijte existující) a poznamenejte si jeho <strong>ID</strong> (vidíte ho v URL při editaci).</li>
				<li>Vytvořte custom pole (viz sekce níže) a zadejte jejich ID do formuláře níže.</li>
				<li>Uložte nastavení a klikněte na <strong>Testovat připojení</strong>.</li>
			</ol>

			<h3 style="margin:0 0 10px;color:#1d2327;">② Jaká custom pole vytvořit v SmartEmailingu</h3>
			<p style="margin:0 0 10px;color:#555;font-size:0.875rem;">Přejděte do SmartEmailing → <strong>Nastavení → Vlastní pole kontaktů → Přidat pole</strong>.</p>
			<table style="width:100%;border-collapse:collapse;font-size:0.875rem;margin-bottom:20px;">
				<thead>
					<tr style="background:#f6f7f7;">
						<th style="padding:8px 10px;text-align:left;border:1px solid #e0e0e0;white-space:nowrap;">Pole v nastavení</th>
						<th style="padding:8px 10px;text-align:left;border:1px solid #e0e0e0;">Název pole v SE</th>
						<th style="padding:8px 10px;text-align:left;border:1px solid #e0e0e0;">Typ</th>
						<th style="padding:8px 10px;text-align:left;border:1px solid #e0e0e0;">Co se uloží</th>
						<th style="padding:8px 10px;text-align:left;border:1px solid #e0e0e0;">Příklad hodnoty</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;font-weight:600;">Stav leadu</td>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;">Stav leadu kalkulačka</td>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;"><code>Text</code></td>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;">Aktuální stav (Čekání, Aktivní, Poptáno, Schůzka, Uzavřeno, Neaktivní)</td>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;color:#2271b1;"><code>Čekání</code></td>
					</tr>
					<tr style="background:#f9f9f9;">
						<td style="padding:8px 10px;border:1px solid #e0e0e0;font-weight:600;">Segment e-shopu</td>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;">Segment kalkulačka</td>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;"><code>Text</code></td>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;">Slug segmentu e-shopu z kalkulačky</td>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;color:#2271b1;"><code>fashion</code>, <code>food</code>, <code>electronics</code></td>
					</tr>
					<tr>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;font-weight:600;">Měsíční obrat</td>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;">Obrat kalkulačka (Kč)</td>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;"><code>Číslo</code></td>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;">Měsíční obrat e-shopu v Kč</td>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;color:#2271b1;"><code>850000</code></td>
					</tr>
					<tr style="background:#f9f9f9;">
						<td style="padding:8px 10px;border:1px solid #e0e0e0;font-weight:600;">Potenciál emailingu</td>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;">Potenciál emailingu (%)</td>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;"><code>Číslo</code></td>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;">Vypočítaný potenciál 15–45 % (celé číslo)</td>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;color:#2271b1;"><code>32</code></td>
					</tr>
					<tr>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;font-weight:600;">Odhadovaný obrat z emailingu</td>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;">Obrat z emailingu (Kč)</td>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;"><code>Číslo</code></td>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;">Střední odhad měsíčního výnosu z emailingu v Kč</td>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;color:#2271b1;"><code>272000</code></td>
					</tr>
					<tr style="background:#f9f9f9;">
						<td style="padding:8px 10px;border:1px solid #e0e0e0;font-weight:600;">Doporučený budget</td>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;">Budget emailing (Kč)</td>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;"><code>Číslo</code></td>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;">Doporučený měsíční budget pro emailing v Kč</td>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;color:#2271b1;"><code>54400</code></td>
					</tr>
					<tr>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;font-weight:600;">Doporučený balíček</td>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;">Balíček kalkulačka</td>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;"><code>Text</code></td>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;">Název doporučeného balíčku (jak je zadaný v sekci Balíčky)</td>
						<td style="padding:8px 10px;border:1px solid #e0e0e0;color:#2271b1;"><code>Start</code>, <code>Business</code>, <code>Premium</code></td>
					</tr>
				</tbody>
			</table>

			<h3 style="margin:0 0 10px;color:#1d2327;">③ Jak fungují tagy</h3>
			<p style="margin:0 0 8px;font-size:0.875rem;color:#555;">Každý kontakt dostane automaticky tyto tagy (prefix lze nastavit níže, výchozí je <code>lead-</code>):</p>
			<table style="width:100%;border-collapse:collapse;font-size:0.875rem;margin-bottom:20px;">
				<thead>
					<tr style="background:#f6f7f7;">
						<th style="padding:7px 10px;text-align:left;border:1px solid #e0e0e0;">Tag</th>
						<th style="padding:7px 10px;text-align:left;border:1px solid #e0e0e0;">Kdy se přidá</th>
						<th style="padding:7px 10px;text-align:left;border:1px solid #e0e0e0;">Příklad (prefix lead-)</th>
					</tr>
				</thead>
				<tbody>
					<tr><td style="padding:7px 10px;border:1px solid #e0e0e0;">Stavový tag</td><td style="padding:7px 10px;border:1px solid #e0e0e0;">Při každém importu/aktualizaci stavu</td><td style="padding:7px 10px;border:1px solid #e0e0e0;"><code>lead-cekani</code>, <code>lead-poptano</code></td></tr>
					<tr style="background:#f9f9f9;"><td style="padding:7px 10px;border:1px solid #e0e0e0;">Tag výsledku</td><td style="padding:7px 10px;border:1px solid #e0e0e0;">Dle výsledku kalkulačky</td><td style="padding:7px 10px;border:1px solid #e0e0e0;"><code>lead-nizky-potencial</code>, <code>lead-balicek-1</code>, <code>lead-balicek-n</code></td></tr>
					<tr><td style="padding:7px 10px;border:1px solid #e0e0e0;">Výchozí tag</td><td style="padding:7px 10px;border:1px solid #e0e0e0;">Vždy (identifikace zdroje)</td><td style="padding:7px 10px;border:1px solid #e0e0e0;"><code>emailing-kalkulacka</code></td></tr>
				</tbody>
			</table>

			<h3 style="margin:0 0 8px;color:#1d2327;">④ Kde v SmartEmailingu najdu ID custom pole</h3>
			<p style="margin:0;font-size:0.875rem;color:#555;">SmartEmailing → <strong>Nastavení → Vlastní pole kontaktů</strong> → klikněte na pole → v URL nebo v detailu pole najdete jeho číselné ID. Toto číslo zadejte do polí níže.</p>
		</div>
	</div>

	<!-- ── Test připojení ──────────────────────────────────────────── -->
	<div style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
		<strong style="min-width:160px;">Test připojení</strong>
		<button type="button" id="ecalc-test-se" class="button button-secondary">
			Testovat připojení k SmartEmailing
		</button>
		<span id="ecalc-test-se-result" style="font-size:13px;"></span>
	</div>

	<!-- ── Nastavení ───────────────────────────────────────────────── -->
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="ecalc_save_smartemailing">
		<?php wp_nonce_field( 'ecalc_save_smartemailing' ); ?>

		<div style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:20px 24px;margin-bottom:20px;">
			<h2 style="margin:0 0 16px;">Připojení</h2>
			<table class="form-table">
				<tr>
					<th>Zapnout integraci</th>
					<td><label><input type="checkbox" name="enabled" value="1" <?php checked( $se['enabled'] ); ?>> Aktivovat odesílání do SmartEmailingu</label></td>
				</tr>
				<tr>
					<th><label for="username">Username / login</label></th>
					<td>
						<input type="text" id="username" name="username" value="<?php echo esc_attr( $se['username'] ); ?>" class="regular-text" autocomplete="off">
						<p class="description">Přihlašovací e-mail do SmartEmailingu.</p>
					</td>
				</tr>
				<tr>
					<th><label for="api_key">API klíč</label></th>
					<td>
						<input type="password" id="api_key" name="api_key" value="<?php echo esc_attr( $se['api_key'] ); ?>" class="regular-text" autocomplete="off">
						<p class="description">Najdete v SmartEmailing → Nastavení → Přístupy a API → API.</p>
					</td>
				</tr>
				<tr>
					<th><label for="list_id">ID seznamu kontaktů</label></th>
					<td>
						<input type="text" id="list_id" name="list_id" value="<?php echo esc_attr( $se['list_id'] ); ?>" class="regular-text">
						<p class="description">Číselné ID cílového seznamu. Najdete v SmartEmailing → Kontakty → Seznamy → klikněte na seznam → ID v URL.</p>
					</td>
				</tr>
				<tr>
					<th>Vyžadovat marketingový souhlas</th>
					<td>
						<label>
							<input type="checkbox" name="require_marketing_consent" value="1" <?php checked( $se['require_marketing_consent'] ); ?>>
							Odesílat do SmartEmailingu pouze kontakty, které zaškrtly souhlas s marketingem
						</label>
						<p class="description">Doporučeno. Pokud vypnete, budou se odesílat i kontakty bez marketingového souhlasu.</p>
					</td>
				</tr>
			</table>
		</div>

		<div style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:20px 24px;margin-bottom:20px;">
			<h2 style="margin:0 0 4px;">Tagy a stavy</h2>
			<p class="description" style="margin:0 0 16px;">Každý importovaný kontakt dostane tagy automaticky.</p>
			<table class="form-table">
				<tr>
					<th><label for="default_tag">Výchozí tag</label></th>
					<td>
						<input type="text" id="default_tag" name="default_tag" value="<?php echo esc_attr( $se['default_tag'] ); ?>" class="regular-text">
						<p class="description">Přidá se ke všem kontaktům z kalkulačky. Slouží k identifikaci zdroje. Výchozí: <code>emailing-kalkulacka</code></p>
					</td>
				</tr>
				<tr>
					<th><label for="status_tag_prefix">Prefix stavových tagů</label></th>
					<td>
						<input type="text" id="status_tag_prefix" name="status_tag_prefix" value="<?php echo esc_attr( $se['status_tag_prefix'] ?? 'lead-' ); ?>" class="regular-text">
						<p class="description">
							Prefix před každým stavovým tagem. Výsledné tagy budou:<br>
							<code><?php echo esc_html( $se['status_tag_prefix'] ?? 'lead-' ); ?>cekani</code> &nbsp;
							<code><?php echo esc_html( $se['status_tag_prefix'] ?? 'lead-' ); ?>poptano</code> &nbsp;
							<code><?php echo esc_html( $se['status_tag_prefix'] ?? 'lead-' ); ?>schuzkа</code> &nbsp;
							<code><?php echo esc_html( $se['status_tag_prefix'] ?? 'lead-' ); ?>uzavreno</code> &nbsp;
							<code><?php echo esc_html( $se['status_tag_prefix'] ?? 'lead-' ); ?>nizky-potencial</code> &nbsp;
							<code><?php echo esc_html( $se['status_tag_prefix'] ?? 'lead-' ); ?>balicek-1</code> &nbsp;
							<code><?php echo esc_html( $se['status_tag_prefix'] ?? 'lead-' ); ?>balicek-n</code>
						</p>
					</td>
				</tr>
			</table>
		</div>

		<div style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:20px 24px;margin-bottom:20px;">
			<h2 style="margin:0 0 4px;">Mapování custom polí</h2>
			<p class="description" style="margin:0 0 16px;">
				Zadejte ID custom polí z vašeho SmartEmailingu. Nechejte <code>0</code> pokud pole nechcete mapovat.
				<a href="#ecalc-se-guide-body" style="margin-left:8px;">Jak vytvořit pole a zjistit jejich ID ↑</a>
			</p>
			<table class="form-table">
				<tr>
					<th><label for="status_customfield_id">Stav leadu</label></th>
					<td>
						<input type="number" id="status_customfield_id" name="status_customfield_id" value="<?php echo esc_attr( $se['status_customfield_id'] ?? 0 ); ?>" class="small-text" min="0">
						<p class="description">Textové pole. Ukládá: <code>Čekání</code>, <code>Aktivní</code>, <code>Poptáno</code>, <code>Schůzka</code>, <code>Uzavřeno</code>, <code>Neaktivní</code>.<br>Aktualizuje se automaticky při každé změně stavu leadu.</p>
					</td>
				</tr>
				<?php
				$cf_fields = [
					'cf_segment'          => [
						'label' => 'Segment e-shopu',
						'desc'  => 'Textové pole. Ukládá slug segmentu z kalkulačky. Příklad: <code>fashion</code>, <code>food</code>, <code>electronics</code>.',
					],
					'cf_monthly_revenue'  => [
						'label' => 'Měsíční obrat (Kč)',
						'desc'  => 'Číselné pole. Ukládá měsíční obrat e-shopu v Kč. Příklad: <code>850000</code>.',
					],
					'cf_final_potential'  => [
						'label' => 'Potenciál emailingu (%)',
						'desc'  => 'Číselné pole. Vypočítaný potenciál 15–45 (celé číslo bez %). Příklad: <code>32</code>.',
					],
					'cf_emailing_mid'     => [
						'label' => 'Odhadovaný obrat z emailingu (Kč)',
						'desc'  => 'Číselné pole. Střední odhad měsíčního výnosu z emailingu v Kč. Příklad: <code>272000</code>.',
					],
					'cf_available_budget' => [
						'label' => 'Doporučený budget (Kč)',
						'desc'  => 'Číselné pole. Doporučený měsíční budget pro emailing v Kč. Příklad: <code>54400</code>.',
					],
					'cf_package'          => [
						'label' => 'Doporučený balíček',
						'desc'  => 'Textové pole. Ukládá <strong>název balíčku</strong> přesně jak je zadán v sekci Balíčky. Příklad: <code>Start</code>, <code>Business</code>, <code>Premium</code>.',
					],
				];
				foreach ( $cf_fields as $key => $info ) : ?>
				<tr>
					<th><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $info['label'] ); ?></label></th>
					<td>
						<input type="number" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $se[ $key ] ?? 0 ); ?>" class="small-text" min="0">
						<p class="description"><?php echo wp_kses_post( $info['desc'] ); ?></p>
					</td>
				</tr>
				<?php endforeach; ?>
			</table>
		</div>

		<?php submit_button( 'Uložit nastavení SmartEmailing' ); ?>
	</form>

	<!-- ── Hromadný export historických leadů ─────────────────────── -->
	<div style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:20px 24px;margin-top:8px;">
		<h2 style="margin:0 0 8px;">Hromadný export do SmartEmailingu</h2>
		<p class="description" style="margin:0 0 16px;">
			Exportujte historické leady do SmartEmailingu. Exportují se pouze leady s marketingovým souhlasem (pokud je tato podmínka aktivní).
			Stávající kontakty v SE budou aktualizovány, nové vytvořeny.
		</p>

		<table class="form-table" style="margin-bottom:16px;">
			<tr>
				<th style="width:200px;">Rozsah exportu</th>
				<td>
					<label style="margin-right:20px;">
						<input type="radio" name="ecalc_export_range" value="all" checked> Všechny leady
					</label>
					<label>
						<input type="radio" name="ecalc_export_range" value="date"> Vlastní rozsah
					</label>
				</td>
			</tr>
			<tr id="ecalc-export-date-row" style="display:none;">
				<th>Datum od – do</th>
				<td style="display:flex;gap:12px;align-items:center;">
					<input type="date" id="ecalc-export-date-from" class="regular-text" style="width:160px;">
					<span>–</span>
					<input type="date" id="ecalc-export-date-to" class="regular-text" style="width:160px;" value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>">
				</td>
			</tr>
		</table>

		<div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
			<button type="button" id="ecalc-bulk-export-se" class="button button-primary">
				Spustit export do SmartEmailingu
			</button>
			<span id="ecalc-bulk-export-result" style="font-size:13px;"></span>
		</div>

		<div id="ecalc-bulk-export-progress" style="display:none;margin-top:16px;">
			<div style="background:#f0f6fc;border:1px solid #2271b1;border-radius:4px;padding:12px 16px;">
				<strong>Export probíhá…</strong>
				<div style="margin-top:8px;background:#e0e0e0;border-radius:3px;height:8px;">
					<div id="ecalc-export-bar" style="background:#2271b1;height:8px;border-radius:3px;width:0%;transition:width 0.3s;"></div>
				</div>
				<p id="ecalc-export-status" style="margin:8px 0 0;font-size:0.875rem;color:#555;"></p>
			</div>
		</div>
	</div>

</div>

<script>
(function($){
	// Toggle průvodce
	$('#ecalc-se-guide-toggle').on('click', function(){
		var $body  = $('#ecalc-se-guide-body');
		var $arrow = $('#ecalc-se-guide-arrow');
		$body.toggle();
		$arrow.text($body.is(':visible') ? '▼' : '▶');
	});

	// Přepínač rozsahu exportu
	$('input[name="ecalc_export_range"]').on('change', function(){
		$('#ecalc-export-date-row').toggle($(this).val() === 'date');
	});

	// Hromadný export
	$('#ecalc-bulk-export-se').on('click', function(){
		var range    = $('input[name="ecalc_export_range"]:checked').val();
		var dateFrom = range === 'date' ? $('#ecalc-export-date-from').val() : '';
		var dateTo   = range === 'date' ? $('#ecalc-export-date-to').val()   : '';

		if(range === 'date' && !dateFrom){
			$('#ecalc-bulk-export-result').css('color','#d63638').text('Zadejte datum od.');
			return;
		}

		$(this).prop('disabled', true).text('Exportuji…');
		$('#ecalc-bulk-export-result').text('');
		$('#ecalc-bulk-export-progress').show();
		$('#ecalc-export-bar').css('width','5%').css('background','#2271b1');
		$('#ecalc-export-status').text('Spouštím export…');

		$.post((typeof ecalcAdmin !== 'undefined' ? ecalcAdmin.ajaxurl : ajaxurl), {
			action:    'ecalc_bulk_export_smartemailing',
			nonce:     '<?php echo esc_js( wp_create_nonce( 'ecalc_bulk_export' ) ); ?>',
			date_from: dateFrom,
			date_to:   dateTo,
		})
		.done(function(r){
			$('#ecalc-bulk-export-se').prop('disabled', false).text('Spustit export do SmartEmailingu');
			if(r.success){
				$('#ecalc-export-bar').css('width','100%').css('background','#007017');
				$('#ecalc-export-status').text(r.data.message);
				$('#ecalc-bulk-export-result').css('color','#007017').text('✓ ' + r.data.message);
			} else {
				$('#ecalc-export-bar').css('background','#d63638').css('width','100%');
				$('#ecalc-export-status').text('Chyba: ' + (r.data && r.data.message ? r.data.message : 'Neznámá chyba'));
				$('#ecalc-bulk-export-result').css('color','#d63638').text('✗ Export selhal.');
			}
		})
		.fail(function(){
			$('#ecalc-bulk-export-se').prop('disabled', false).text('Spustit export do SmartEmailingu');
			$('#ecalc-bulk-export-result').css('color','#d63638').text('Chyba komunikace.');
		});
	});
})(jQuery);
</script>
