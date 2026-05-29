<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap ecalc-admin">
	<h1>Formulář & CTA</h1>

	<?php if ( $notice['type'] ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible"><p><?php echo esc_html( $notice['message'] ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="ecalc_save_form">
		<?php wp_nonce_field( 'ecalc_save_form' ); ?>

		<div class="ecalc-settings-grid">

			<div class="ecalc-settings-section">
				<h2>CTA tlačítko</h2>
				<p class="description">Hlavní tlačítko v sekci výsledků kalkulačky.</p>
				<table class="form-table">
					<tr>
						<th><label for="cta_text">Text tlačítka</label></th>
						<td><input type="text" id="cta_text" name="cta_text" value="<?php echo esc_attr( $cfg['cta_text'] ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th><label for="cta_url">URL tlačítka (fallback)</label></th>
						<td>
							<input type="url" id="cta_url" name="cta_url" value="<?php echo esc_attr( $cfg['cta_url'] ); ?>" class="regular-text" placeholder="https://">
							<p class="description">Použije se, pokud není vyplněna URL rezervace níže.</p>
						</td>
					</tr>
				</table>
			</div>

			<div class="ecalc-settings-section">
				<h2>Rezervace termínu (Google Calendar)</h2>
				<p class="description">Pokud je vyplněna URL rezervace, kliknutí na CTA otevře booking modal místo přesměrování.</p>
				<table class="form-table">
					<tr>
						<th><label for="booking_url">URL Google Calendar</label></th>
						<td>
							<input type="url" id="booking_url" name="booking_url" value="<?php echo esc_attr( $cfg['booking_url'] ?? '' ); ?>" class="large-text" placeholder="https://calendar.app.google/...">
							<p class="description">Prázdné = použije se URL tlačítka (fallback výše).</p>
						</td>
					</tr>
					<tr>
						<th><label for="booking_modal_title">Nadpis modalu</label></th>
						<td><input type="text" id="booking_modal_title" name="booking_modal_title" value="<?php echo esc_attr( $cfg['booking_modal_title'] ?? 'Vyberte termín konzultace' ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th><label for="booking_confirm_question">Otázka po zavření</label></th>
						<td><input type="text" id="booking_confirm_question" name="booking_confirm_question" value="<?php echo esc_attr( $cfg['booking_confirm_question'] ?? 'Provedli jste rezervaci termínu?' ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th><label for="booking_confirm_yes">Text tlačítka „Ano"</label></th>
						<td><input type="text" id="booking_confirm_yes" name="booking_confirm_yes" value="<?php echo esc_attr( $cfg['booking_confirm_yes'] ?? 'Ano, zarezervoval jsem' ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th><label for="booking_confirm_no">Text tlačítka „Ne"</label></th>
						<td><input type="text" id="booking_confirm_no" name="booking_confirm_no" value="<?php echo esc_attr( $cfg['booking_confirm_no'] ?? 'Ne, zavřít' ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th><label for="booking_yes_message">Zpráva po „Ano"</label></th>
						<td><input type="text" id="booking_yes_message" name="booking_yes_message" value="<?php echo esc_attr( $cfg['booking_yes_message'] ?? 'Skvělé! Brzy se ozveme.' ); ?>" class="large-text"></td>
					</tr>
					<tr>
						<th><label for="booking_no_message">Zpráva po „Ne"</label></th>
						<td><input type="text" id="booking_no_message" name="booking_no_message" value="<?php echo esc_attr( $cfg['booking_no_message'] ?? 'Nevadí – obraťte se na nás kdykoliv.' ); ?>" class="large-text"></td>
					</tr>
				</table>
			</div>

			<div class="ecalc-settings-section">
				<h2>Souhlas s marketingem</h2>
				<table class="form-table">
					<tr>
						<th>Marketingový souhlas povinný</th>
						<td>
							<label>
								<input type="checkbox" name="marketing_consent_required" value="1" <?php checked( $cfg['marketing_consent_required'] ); ?>>
								Vyžadovat souhlas s marketingem jako povinný
							</label>
						</td>
					</tr>
				</table>
			</div>

			<div class="ecalc-settings-section">
				<h2>Dialog – duplicitní e-mail</h2>
				<p class="description">Texty zobrazené, když uživatel zadá e-mail, který již máme v databázi. <code>{status}</code> se nahradí názvem stavu leadu.</p>
				<table class="form-table">
					<tr>
						<th><label for="duplicate_title">Nadpis dialogu</label></th>
						<td><input type="text" id="duplicate_title" name="duplicate_title" value="<?php echo esc_attr( $cfg['duplicate_title'] ?? 'Již vás máme v databázi' ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th><label for="duplicate_msg">Text zprávy</label></th>
						<td>
							<input type="text" id="duplicate_msg" name="duplicate_msg" value="<?php echo esc_attr( $cfg['duplicate_msg'] ?? 'Kontakt s tímto e-mailem již existuje v naší databázi (stav: {status}).' ); ?>" class="large-text">
							<p class="description">Použijte <code>{status}</code> pro vložení stavu leadu.</p>
						</td>
					</tr>
					<tr>
						<th><label for="duplicate_question">Otázka / výzva k akci</label></th>
						<td><input type="text" id="duplicate_question" name="duplicate_question" value="<?php echo esc_attr( $cfg['duplicate_question'] ?? 'Chcete provést nový výpočet? Aktuální záznam bude aktualizován.' ); ?>" class="large-text"></td>
					</tr>
					<tr>
						<th><label for="duplicate_confirm">Text tlačítka „Potvrdit"</label></th>
						<td><input type="text" id="duplicate_confirm" name="duplicate_confirm" value="<?php echo esc_attr( $cfg['duplicate_confirm'] ?? 'Ano, provést nový výpočet' ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th><label for="duplicate_cancel">Text tlačítka „Zrušit"</label></th>
						<td><input type="text" id="duplicate_cancel" name="duplicate_cancel" value="<?php echo esc_attr( $cfg['duplicate_cancel'] ?? 'Zrušit' ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th><label for="update_banner">Banner po aktualizaci záznamu</label></th>
						<td>
							<input type="text" id="update_banner" name="update_banner" value="<?php echo esc_attr( $cfg['update_banner'] ?? 'Váš záznam byl aktualizován na základě nového výpočtu.' ); ?>" class="large-text">
							<p class="description">Zobrazí se v sekci výsledků po přepočtu existujícího záznamu.</p>
						</td>
					</tr>
				</table>
			</div>

			<div class="ecalc-settings-section">
				<h2>Dialog – telefonní číslo</h2>
				<p class="description">Texty zobrazené v dialogu po kliknutí na CTA, kde zákazník může (volitelně) zadat telefonní číslo.</p>
				<table class="form-table">
					<tr>
						<th><label for="phone_dialog_title">Nadpis dialogu</label></th>
						<td><input type="text" id="phone_dialog_title" name="phone_dialog_title" value="<?php echo esc_attr( $cfg['phone_dialog_title'] ?? 'Zanechte nám telefonní číslo' ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th><label for="phone_dialog_desc">Popis / doprovodný text</label></th>
						<td><input type="text" id="phone_dialog_desc" name="phone_dialog_desc" value="<?php echo esc_attr( $cfg['phone_dialog_desc'] ?? 'Pro rychlejší komunikaci nám můžete zanechat telefonní číslo.' ); ?>" class="large-text"></td>
					</tr>
					<tr>
						<th><label for="phone_dialog_submit">Text tlačítka „Pokračovat"</label></th>
						<td><input type="text" id="phone_dialog_submit" name="phone_dialog_submit" value="<?php echo esc_attr( $cfg['phone_dialog_submit'] ?? 'Pokračovat' ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th><label for="phone_dialog_skip">Text tlačítka „Přeskočit"</label></th>
						<td><input type="text" id="phone_dialog_skip" name="phone_dialog_skip" value="<?php echo esc_attr( $cfg['phone_dialog_skip'] ?? 'Přeskočit' ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th><label for="phone_dialog_error">Chybová hláška (neplatný formát)</label></th>
						<td>
							<input type="text" id="phone_dialog_error" name="phone_dialog_error" value="<?php echo esc_attr( $cfg['phone_dialog_error'] ?? 'Zadejte platné telefonní číslo (7–15 číslic).' ); ?>" class="large-text">
							<p class="description">Zobrazí se, pokud zadané číslo nesplňuje formát (7–15 číslic).</p>
						</td>
					</tr>
				</table>
			</div>

			<div class="ecalc-settings-section">
				<h2>Ochrana proti spamu</h2>
				<table class="form-table">
					<tr>
						<th><label for="monthly_limit">Měsíční limit výpočtů na e-mail</label></th>
						<td>
							<input type="number" id="monthly_limit" name="monthly_limit"
								value="<?php echo esc_attr( $cfg['monthly_limit'] ?? 5 ); ?>"
								min="0" max="100" class="small-text">
							<p class="description">Maximální počet výpočtů na jeden e-mail za měsíc. 0 = bez limitu.</p>
						</td>
					</tr>
				</table>
			</div>

		</div>

		<?php submit_button( 'Uložit nastavení formuláře' ); ?>
	</form>
</div>
