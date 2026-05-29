<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap ecalc-admin">
	<h1>Texty výsledků</h1>

	<?php if ( $notice['type'] ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible"><p><?php echo esc_html( $notice['message'] ); ?></p></div>
	<?php endif; ?>

	<div class="ecalc-info-box">
		<strong>Dostupné proměnné v textech:</strong>
		<code>{name}</code> <code>{email}</code> <code>{shop_url}</code> <code>{segment}</code>
		<code>{monthly_revenue}</code> <code>{consumable_percentage}</code> <code>{database_range}</code>
		<code>{expected_pno}</code> <code>{final_potential}</code> <code>{emailing_revenue_low}</code>
		<code>{emailing_revenue_mid}</code> <code>{emailing_revenue_high}</code> <code>{available_budget}</code>
		<code>{recommended_package}</code> <code>{recommended_package_price}</code> <code>{recommended_package_real_pno}</code>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="ecalc_save_result_texts">
		<?php wp_nonce_field( 'ecalc_save_result_texts' ); ?>

		<table class="form-table">
			<tr>
				<th><label for="result_title">Nadpis výsledku</label></th>
				<td><input type="text" id="result_title" name="result_title" value="<?php echo esc_attr( $texts['result_title'] ?? 'Výsledek vaší kalkulačky' ); ?>" class="large-text"></td>
			</tr>
			<tr>
				<th><label for="low_potential">Text – Nízký potenciál</label></th>
				<td>
					<textarea id="low_potential" name="low_potential" rows="6" class="large-text"><?php echo esc_textarea( $texts['low_potential'] ?? '' ); ?></textarea>
					<p class="description">Zobrazí se, když dostupný budget je nízký (pod hranicí nízkého potenciálu).</p>
				</td>
			</tr>
			<tr>
				<th><label for="borderline">Text – Hraniční potenciál</label></th>
				<td>
					<textarea id="borderline" name="borderline" rows="6" class="large-text"><?php echo esc_textarea( $texts['borderline'] ?? '' ); ?></textarea>
					<p class="description">Zobrazí se, když budget je hraniční (mezi nízkým a prvním balíčkem).</p>
				</td>
			</tr>
		</table>

		<div class="ecalc-info-box ecalc-info-box--info">
			<strong>Texty pro doporučené balíčky</strong> se nastavují přímo v sekci <a href="<?php echo esc_url( admin_url( 'admin.php?page=ecalc_packages' ) ); ?>">Balíčky</a> → pole „Text výsledku" u každého balíčku.
		</div>

		<?php submit_button( 'Uložit texty' ); ?>
	</form>
</div>
