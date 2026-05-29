<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap ecalc-admin">
	<h1>E-mailové notifikace</h1>

	<?php if ( $notice['type'] ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible"><p><?php echo esc_html( $notice['message'] ); ?></p></div>
	<?php endif; ?>

	<div class="ecalc-info-box">
		<strong>Dostupné proměnné:</strong><br>
		<code>{name}</code> <code>{email}</code> <code>{phone}</code> <code>{shop_url}</code> <code>{segment}</code>
		<code>{monthly_revenue}</code> <code>{consumable_percentage}</code> <code>{database_range}</code>
		<code>{expected_pno}</code> <code>{final_potential}</code> <code>{emailing_revenue_low}</code>
		<code>{emailing_revenue_mid}</code> <code>{emailing_revenue_high}</code> <code>{available_budget}</code>
		<code>{recommended_package}</code> <code>{recommended_package_price}</code> <code>{recommended_package_real_pno}</code>
		<code>{inquiry_package}</code>
		<br><em style="font-size:12px;color:#666;">
			<code>{phone}</code> je dostupný v triggerových notifikacích (poptávka, konzultace) – je automaticky zvýrazněn v admin e-mailu pokud zákazník číslo zadal.
			<code>{inquiry_package}</code> obsahuje název poptávaného balíčku.
		</em>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="ecalc_save_notifications">
		<?php wp_nonce_field( 'ecalc_save_notifications' ); ?>

		<h2>Notifikace administrátorovi</h2>
		<table class="form-table">
			<tr>
				<th>Zapnout notifikaci</th>
				<td><label><input type="checkbox" name="admin_enabled" value="1" <?php checked( $notif['admin_enabled'] ); ?>> Odesílat e-mail po každém leadu</label></td>
			</tr>
			<tr>
				<th><label for="admin_email">E-mail příjemce</label></th>
				<td><input type="email" id="admin_email" name="admin_email" value="<?php echo esc_attr( $notif['admin_email'] ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="admin_subject">Předmět e-mailu</label></th>
				<td><input type="text" id="admin_subject" name="admin_subject" value="<?php echo esc_attr( $notif['admin_subject'] ); ?>" class="large-text"></td>
			</tr>
			<tr>
				<th><label for="admin_body">Text e-mailu</label></th>
				<td><textarea id="admin_body" name="admin_body" rows="12" class="large-text"><?php echo esc_textarea( $notif['admin_body'] ); ?></textarea></td>
			</tr>
		</table>

		<h2>E-mail klientovi</h2>
		<table class="form-table">
			<tr>
				<th>Zapnout e-mail klientovi</th>
				<td><label><input type="checkbox" name="client_enabled" value="1" <?php checked( $notif['client_enabled'] ); ?>> Odesílat potvrzovací e-mail zákazníkovi</label></td>
			</tr>
			<tr>
				<th><label for="client_subject">Předmět e-mailu</label></th>
				<td><input type="text" id="client_subject" name="client_subject" value="<?php echo esc_attr( $notif['client_subject'] ); ?>" class="large-text"></td>
			</tr>
			<tr>
				<th><label for="client_body">Text e-mailu</label></th>
				<td><textarea id="client_body" name="client_body" rows="10" class="large-text"><?php echo esc_textarea( $notif['client_body'] ); ?></textarea></td>
			</tr>
		</table>

	<hr>
	<h2>Triggery – automatické e-maily</h2>

	<div class="ecalc-info-box">
		<strong>Dostupná proměnná navíc:</strong> <code>{inquiry_package}</code> – název poptávaného balíčku (jen u Triggeru 2).
	</div>

	<!-- Trigger 1: Follow-up -->
	<div class="ecalc-trigger-section">
		<h3>⏱ Trigger 1 – Follow-up (neklikl na žádné CTA)</h3>
		<p class="description">Odešle se automaticky po zvoleném čase, pokud uživatel nevyplnil poptávku ani neklikl na konzultaci.</p>
		<table class="form-table">
			<tr>
				<th>Zapnout trigger</th>
				<td><label><input type="checkbox" name="trigger_followup_enabled" value="1" <?php checked( $notif['trigger_followup_enabled'] ?? 0 ); ?>> Aktivovat follow-up e-mail</label></td>
			</tr>
			<tr>
				<th><label for="trigger_followup_delay_hours">Zpoždění (hodiny)</label></th>
				<td><input type="number" id="trigger_followup_delay_hours" name="trigger_followup_delay_hours" value="<?php echo esc_attr( $notif['trigger_followup_delay_hours'] ?? 24 ); ?>" min="1" max="720" class="small-text"> hodin po odeslání formuláře</td>
			</tr>
			<tr>
				<th><label for="trigger_followup_subject">Předmět e-mailu</label></th>
				<td><input type="text" id="trigger_followup_subject" name="trigger_followup_subject" value="<?php echo esc_attr( $notif['trigger_followup_subject'] ?? '' ); ?>" class="large-text"></td>
			</tr>
			<tr>
				<th><label for="trigger_followup_body">Text e-mailu</label></th>
				<td><textarea id="trigger_followup_body" name="trigger_followup_body" rows="8" class="large-text"><?php echo esc_textarea( $notif['trigger_followup_body'] ?? '' ); ?></textarea></td>
			</tr>
		</table>
	</div>

	<!-- Trigger 2: Poptávka balíčku -->
	<div class="ecalc-trigger-section">
		<h3>📦 Trigger 2 – Poptávka balíčku (klik na „Poptat balíček")</h3>
		<p class="description">Spustí se okamžitě po kliknutí na tlačítko „Poptat balíček" u konkrétního balíčku.</p>
		<table class="form-table">
			<tr>
				<th>Zapnout trigger</th>
				<td><label><input type="checkbox" name="trigger_inquiry_enabled" value="1" <?php checked( $notif['trigger_inquiry_enabled'] ?? 1 ); ?>> Aktivovat e-maily při poptávce balíčku</label></td>
			</tr>
			<tr>
				<th><label>Notifikace admina – předmět</label></th>
				<td><input type="text" name="trigger_inquiry_admin_subject" value="<?php echo esc_attr( $notif['trigger_inquiry_admin_subject'] ?? '' ); ?>" class="large-text"></td>
			</tr>
			<tr>
				<th><label>Notifikace admina – text</label></th>
				<td><textarea name="trigger_inquiry_admin_body" rows="8" class="large-text"><?php echo esc_textarea( $notif['trigger_inquiry_admin_body'] ?? '' ); ?></textarea></td>
			</tr>
			<tr>
				<th><label>Potvrzení klientovi – předmět</label></th>
				<td><input type="text" name="trigger_inquiry_client_subject" value="<?php echo esc_attr( $notif['trigger_inquiry_client_subject'] ?? '' ); ?>" class="large-text"></td>
			</tr>
			<tr>
				<th><label>Potvrzení klientovi – text</label></th>
				<td><textarea name="trigger_inquiry_client_body" rows="8" class="large-text"><?php echo esc_textarea( $notif['trigger_inquiry_client_body'] ?? '' ); ?></textarea></td>
			</tr>
		</table>
	</div>

	<!-- Trigger 3: Poptávka konzultace -->
	<div class="ecalc-trigger-section">
		<h3>📅 Trigger 3 – Poptávka konzultace (klik na hlavní CTA)</h3>
		<p class="description">Spustí se okamžitě po kliknutí na hlavní CTA tlačítko „Chci konzultaci zdarma".</p>
		<table class="form-table">
			<tr>
				<th>Zapnout trigger</th>
				<td><label><input type="checkbox" name="trigger_consultation_enabled" value="1" <?php checked( $notif['trigger_consultation_enabled'] ?? 1 ); ?>> Aktivovat e-maily při poptávce konzultace</label></td>
			</tr>
			<tr>
				<th><label>Notifikace admina – předmět</label></th>
				<td><input type="text" name="trigger_consultation_admin_subject" value="<?php echo esc_attr( $notif['trigger_consultation_admin_subject'] ?? '' ); ?>" class="large-text"></td>
			</tr>
			<tr>
				<th><label>Notifikace admina – text</label></th>
				<td><textarea name="trigger_consultation_admin_body" rows="8" class="large-text"><?php echo esc_textarea( $notif['trigger_consultation_admin_body'] ?? '' ); ?></textarea></td>
			</tr>
			<tr>
				<th><label>Potvrzení klientovi – předmět</label></th>
				<td><input type="text" name="trigger_consultation_client_subject" value="<?php echo esc_attr( $notif['trigger_consultation_client_subject'] ?? '' ); ?>" class="large-text"></td>
			</tr>
			<tr>
				<th><label>Potvrzení klientovi – text</label></th>
				<td><textarea name="trigger_consultation_client_body" rows="8" class="large-text"><?php echo esc_textarea( $notif['trigger_consultation_client_body'] ?? '' ); ?></textarea></td>
			</tr>
		</table>
	</div>

		<?php submit_button( 'Uložit nastavení notifikací' ); ?>
	</form>
</div>
