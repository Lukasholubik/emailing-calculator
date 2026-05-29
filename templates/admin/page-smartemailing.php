<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap ecalc-admin">
	<h1>SmartEmailing integrace</h1>

	<?php if ( $notice['type'] ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible"><p><?php echo esc_html( $notice['message'] ); ?></p></div>
	<?php endif; ?>

	<div class="ecalc-connection-test" style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
		<strong style="min-width:160px;">Test připojení</strong>
		<button type="button" id="ecalc-test-se" class="button button-secondary">
			Testovat připojení k SmartEmailing
		</button>
		<span id="ecalc-test-se-result" style="font-size:13px;"></span>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="ecalc_save_smartemailing">
		<?php wp_nonce_field( 'ecalc_save_smartemailing' ); ?>

		<table class="form-table">
			<tr>
				<th>Zapnout integraci</th>
				<td><label><input type="checkbox" name="enabled" value="1" <?php checked( $se['enabled'] ); ?>> Aktivovat odesílání do SmartEmailingu</label></td>
			</tr>
			<tr>
				<th><label for="username">Username / login</label></th>
				<td><input type="text" id="username" name="username" value="<?php echo esc_attr( $se['username'] ); ?>" class="regular-text" autocomplete="off"></td>
			</tr>
			<tr>
				<th><label for="api_key">API klíč</label></th>
				<td><input type="password" id="api_key" name="api_key" value="<?php echo esc_attr( $se['api_key'] ); ?>" class="regular-text" autocomplete="off"></td>
			</tr>
			<tr>
				<th><label for="list_id">ID seznamu kontaktů</label></th>
				<td><input type="text" id="list_id" name="list_id" value="<?php echo esc_attr( $se['list_id'] ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="default_tag">Výchozí tag</label></th>
				<td><input type="text" id="default_tag" name="default_tag" value="<?php echo esc_attr( $se['default_tag'] ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th>Vyžadovat marketingový souhlas</th>
				<td>
					<label>
						<input type="checkbox" name="require_marketing_consent" value="1" <?php checked( $se['require_marketing_consent'] ); ?>>
						Odesílat do SmartEmailingu pouze kontakty, které zaškrtly souhlas s marketingem
					</label>
				</td>
			</tr>
		</table>

		<h2>Tagy a stavy</h2>
		<table class="form-table">
			<tr>
				<th><label for="status_tag_prefix">Prefix stavových tagů</label></th>
				<td>
					<input type="text" id="status_tag_prefix" name="status_tag_prefix" value="<?php echo esc_attr( $se['status_tag_prefix'] ?? 'lead-' ); ?>" class="regular-text">
					<p class="description">Tagy budou: <code>lead-cekani</code>, <code>lead-poptano</code>, <code>lead-schuzkа</code>, atd.</p>
				</td>
			</tr>
			<tr>
				<th><label for="status_customfield_id">Custom field ID pro stav leadu</label></th>
				<td>
					<input type="number" id="status_customfield_id" name="status_customfield_id" value="<?php echo esc_attr( $se['status_customfield_id'] ?? 0 ); ?>" class="small-text" min="0">
					<p class="description">ID custom pole v SmartEmailingu (vytvořte v nastavení SmartEmailingu → Custom fields). Pokud 0, použijí se pouze tagy.</p>
				</td>
			</tr>
		</table>

		<h2>Mapování custom fields (volitelné)</h2>
		<p class="description">Zadejte ID custom polí z vašeho SmartEmailingu pro automatické plnění dat z kalkulačky. Nechejte 0 pokud nechcete mapovat.</p>
		<table class="form-table">
			<?php
			$cf_fields = [
				'cf_segment'          => 'Segment e-shopu',
				'cf_monthly_revenue'  => 'Měsíční obrat (Kč)',
				'cf_final_potential'  => 'Potenciál emailingu (%)',
				'cf_emailing_mid'     => 'Odhadovaný obrat z emailingu (Kč)',
				'cf_available_budget' => 'Doporučený budget (Kč)',
				'cf_package'          => 'Doporučený balíček',
			];
			foreach ( $cf_fields as $key => $label ) :
			?>
			<tr>
				<th><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
				<td><input type="number" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $se[ $key ] ?? 0 ); ?>" class="small-text" min="0"></td>
			</tr>
			<?php endforeach; ?>
		</table>

		<?php submit_button( 'Uložit nastavení SmartEmailing' ); ?>
	</form>
</div>
