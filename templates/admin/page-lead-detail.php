<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap ecalc-admin">
	<h1>Detail leadu #<?php echo (int) $lead['id']; ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=ecalc_leads' ) ); ?>" class="button">&larr; Zpět na leady</a>

	<div class="ecalc-detail-grid">
		<div class="ecalc-detail-section">
			<h2>Kontaktní údaje</h2>
			<table class="ecalc-detail-table">
				<tr><th>Jméno</th><td><?php echo esc_html( $lead['name'] ); ?></td></tr>
				<tr><th>E-mail</th><td><a href="mailto:<?php echo esc_attr( $lead['email'] ); ?>"><?php echo esc_html( $lead['email'] ); ?></a></td></tr>
				<?php if ( ! empty( $lead['phone'] ) ) : ?>
				<tr><th>Telefon</th><td><a href="tel:<?php echo esc_attr( $lead['phone'] ); ?>"><?php echo esc_html( $lead['phone'] ); ?></a></td></tr>
				<?php endif; ?>
				<tr><th>URL e-shopu</th><td><?php echo esc_html( $lead['shop_url'] ); ?></td></tr>
				<tr><th>IP adresa</th><td><?php echo esc_html( $lead['ip_address'] ); ?></td></tr>
				<tr><th>Datum odeslání</th><td><?php echo esc_html( date( 'd.m.Y H:i:s', strtotime( $lead['created_at'] ) ) ); ?></td></tr>
				<tr><th>Souhlas zpracování</th><td><?php echo $lead['consent_data'] ? 'Ano' : 'Ne'; ?></td></tr>
				<tr><th>Souhlas marketing</th><td><?php echo $lead['consent_marketing'] ? 'Ano' : 'Ne'; ?></td></tr>
			</table>
		</div>

		<div class="ecalc-detail-section">
			<h2>Zadané hodnoty</h2>
			<table class="ecalc-detail-table">
				<tr><th>Segment</th><td><?php echo esc_html( $lead['segment'] ); ?></td></tr>
				<tr><th>Spotřební zboží</th><td><?php echo (int) $lead['consumable_percentage']; ?> %</td></tr>
				<tr><th>Databáze kontaktů</th><td><?php echo esc_html( $lead['database_range'] ); ?></td></tr>
				<tr><th>Měsíční obrat</th><td><?php echo esc_html( number_format( (float) $lead['monthly_revenue'], 0, ',', ' ' ) ); ?> Kč</td></tr>
				<tr><th>Očekávané PNO</th><td><?php echo (int) $lead['expected_pno']; ?> %</td></tr>
			</table>
		</div>

		<div class="ecalc-detail-section">
			<h2>Výsledky výpočtu</h2>
			<table class="ecalc-detail-table">
				<tr><th>Consumable score</th><td><?php echo esc_html( $lead['consumable_score'] ); ?></td></tr>
				<tr><th>Database score</th><td><?php echo esc_html( $lead['database_score'] ); ?></td></tr>
				<tr><th>Segment score</th><td><?php echo esc_html( $lead['segment_score'] ); ?></td></tr>
				<tr><th>Total score</th><td><?php echo esc_html( $lead['total_score'] ); ?></td></tr>
				<tr><th>Potenciál emailingu</th><td><?php echo esc_html( number_format( (float) $lead['final_potential'], 2, ',', ' ' ) ); ?> %</td></tr>
				<tr><th>Obrat e-mail (low)</th><td><?php echo esc_html( number_format( (float) $lead['emailing_revenue_low'], 0, ',', ' ' ) ); ?> Kč</td></tr>
				<tr><th>Obrat e-mail (mid)</th><td><?php echo esc_html( number_format( (float) $lead['emailing_revenue_mid'], 0, ',', ' ' ) ); ?> Kč</td></tr>
				<tr><th>Obrat e-mail (high)</th><td><?php echo esc_html( number_format( (float) $lead['emailing_revenue_high'], 0, ',', ' ' ) ); ?> Kč</td></tr>
				<tr><th>Dostupný budget</th><td><?php echo esc_html( number_format( (float) $lead['available_budget'], 0, ',', ' ' ) ); ?> Kč</td></tr>
			</table>
		</div>

		<div class="ecalc-detail-section">
			<h2>Doporučení</h2>
			<table class="ecalc-detail-table">
				<tr><th>Typ výsledku</th><td><strong><?php echo esc_html( $result_labels[ $lead['result_type'] ] ?? $lead['result_type'] ); ?></strong></td></tr>
				<tr><th>Doporučený balíček</th><td><?php echo esc_html( $lead['recommended_package'] ); ?></td></tr>
				<tr><th>Cena balíčku</th><td><?php echo esc_html( number_format( (float) $lead['recommended_package_price'], 0, ',', ' ' ) ); ?> Kč</td></tr>
				<tr><th>Reálné PNO balíčku</th><td><?php echo esc_html( number_format( (float) $lead['recommended_package_real_pno'], 2, ',', ' ' ) ); ?> %</td></tr>
			</table>
		</div>

		<div class="ecalc-detail-section ecalc-detail-section--full">
			<h2>SmartEmailing</h2>
			<table class="ecalc-detail-table">
				<tr><th>Status</th><td>
					<span class="ecalc-se-status ecalc-se-status--<?php echo esc_attr( $lead['smartemailing_status'] ); ?>">
						<?php echo esc_html( $lead['smartemailing_status'] ); ?>
					</span>
				</td></tr>
				<tr><th>Poslední pokus</th><td><?php echo $lead['smartemailing_last_attempt_at'] ? esc_html( date( 'd.m.Y H:i:s', strtotime( $lead['smartemailing_last_attempt_at'] ) ) ) : '–'; ?></td></tr>
				<tr><th>Poslední odpověď</th><td><pre class="ecalc-pre"><?php echo esc_html( $lead['smartemailing_last_response'] ?? '' ); ?></pre></td></tr>
			</table>

			<button class="button button-secondary ecalc-resend-btn" data-lead-id="<?php echo (int) $lead['id']; ?>"
				data-nonce="<?php echo esc_attr( wp_create_nonce( 'ecalc_resend' ) ); ?>">
				Znovu odeslat do SmartEmailingu
			</button>
			<span class="ecalc-resend-status"></span>
		</div>
	</div>

	<?php if ( ! empty( $changelog ) ) : ?>
	<div class="ecalc-detail-section ecalc-detail-section--full">
		<h2>Changelog</h2>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th style="width:170px">Datum</th>
					<th style="width:150px">Typ změny</th>
					<th>Poznámka</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$alert_types = [ 'name_changed', 'url_changed' ];
				foreach ( $changelog as $entry ) :
					$is_alert = in_array( $entry['change_type'], $alert_types, true );
				?>
				<tr<?php echo $is_alert ? ' style="background:#fff5f5;"' : ''; ?>>
					<td><?php echo esc_html( date( 'd.m.Y H:i:s', strtotime( $entry['changed_at'] ) ) ); ?></td>
					<td>
						<code style="<?php echo $is_alert ? 'background:#fee2e2;color:#dc2626;font-weight:700;' : ''; ?>">
							<?php echo esc_html( $entry['change_type'] ); ?>
						</code>
					</td>
					<td style="<?php echo $is_alert ? 'color:#dc2626;font-weight:600;' : ''; ?>">
						<?php echo esc_html( $entry['note'] ); ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>

	<div class="ecalc-detail-section ecalc-delete-section">
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
			onsubmit="return confirm('Opravdu smazat tento lead?');">
			<input type="hidden" name="action" value="ecalc_delete_lead">
			<input type="hidden" name="lead_id" value="<?php echo (int) $lead['id']; ?>">
			<?php wp_nonce_field( 'ecalc_delete_lead' ); ?>
			<button type="submit" class="button button-link-delete">Smazat lead</button>
		</form>
	</div>
</div>
