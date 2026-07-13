<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var array $segments */
/** @var array $database_ranges */
/** @var array $revenue_ranges */
/** @var string $rest_url */
/** @var string $nonce */
/** @var int $marketing_required */
?>
<div class="ecalc-wrapper" id="ecalc-app"
	data-rest-url="<?php echo esc_url( $rest_url ); ?>"
	data-nonce="<?php echo esc_attr( $nonce ); ?>">

	<div class="ecalc-layout">
		<!-- ===================== FORMULÁŘ ===================== -->
		<div class="ecalc-form-col">
			<div class="ecalc-card ecalc-form-card">
				<h2 class="ecalc-form-title">Zjistěte potenciál emailingu pro váš e-shop</h2>
				<p class="ecalc-form-subtitle">Orientační kalkulačka vám ukáže, jaký výkon může emailing pro váš e-shop dosahovat a jaký balíček dává podle zadaných údajů smysl.</p>

				<div class="ecalc-progress-wrap" aria-hidden="true">
					<div class="ecalc-progress-bar"><div class="ecalc-progress-fill" id="ecalc-progress-fill"></div></div>
					<p class="ecalc-progress-text" id="ecalc-progress-text">Vyplněno 0 %</p>
				</div>

				<form id="ecalc-form" novalidate>
					<!-- Honeypot ochrana proti spamu -->
					<div class="ecalc-honeypot" aria-hidden="true">
						<label for="_hp_field">Nevyplňujte toto pole</label>
						<input type="text" name="_hp_field" id="_hp_field" tabindex="-1" autocomplete="off">
					</div>

					<div class="ecalc-field">
						<label class="ecalc-label" for="ecalc-segment">Oblast podnikání <span class="ecalc-required">*</span></label>
						<select class="ecalc-input ecalc-select" id="ecalc-segment" name="segment" required>
							<option value="">— Vyberte oblast —</option>
							<?php foreach ( $segments as $seg ) : ?>
								<option value="<?php echo esc_attr( $seg['name'] ); ?>"><?php echo esc_html( $seg['name'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<span class="ecalc-error-msg" id="ecalc-error-segment"></span>
					</div>

					<div class="ecalc-field">
						<label class="ecalc-label">
							Podíl spotřebního / opakovaně nakupovaného zboží <span class="ecalc-required">*</span>
						</label>
						<div class="ecalc-slider-wrap">
							<input class="ecalc-slider" type="range" id="ecalc-consumable-slider"
								min="0" max="100" step="1" value="50">
							<div class="ecalc-slider-labels">
								<div class="ecalc-slider-label">
									<span class="ecalc-label-key">Spotřební zboží:</span>
									<div class="ecalc-pct-wrap">
										<input class="ecalc-pct-input" type="number" id="ecalc-consumable-pct" min="0" max="100" step="1" value="50">
										<span class="ecalc-pct-sign">%</span>
									</div>
								</div>
								<div class="ecalc-slider-label ecalc-slider-label--right">
									<span class="ecalc-label-key">Jednorázové / klasické:</span>
									<div class="ecalc-pct-wrap ecalc-pct-wrap--right">
										<input class="ecalc-pct-input" type="number" id="ecalc-non-consumable-pct" min="0" max="100" step="1" value="50">
										<span class="ecalc-pct-sign">%</span>
									</div>
								</div>
							</div>
						</div>
						<input type="hidden" id="ecalc-consumable-percentage" name="consumable_percentage" value="50">
						<p class="ecalc-hint">Např. 0 % = nábytek, elektronika · 50 % = smíšený sortiment · 100 % = drogerie, doplňky stravy, krmivo pro zvířata.</p>
						<span class="ecalc-error-msg" id="ecalc-error-consumable"></span>
					</div>

					<div class="ecalc-field">
						<label class="ecalc-label" for="ecalc-database-range">Velikost databáze kontaktů <span class="ecalc-required">*</span></label>
						<select class="ecalc-input ecalc-select" id="ecalc-database-range" name="database_range" required>
							<option value="">— Vyberte rozsah —</option>
							<?php foreach ( $database_ranges as $dr ) : ?>
								<option value="<?php echo esc_attr( $dr['name'] ); ?>"><?php echo esc_html( $dr['name'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<span class="ecalc-error-msg" id="ecalc-error-database"></span>
					</div>

					<div class="ecalc-field">
						<label class="ecalc-label">Měsíční obrat e-shopu <span class="ecalc-required">*</span></label>
						<div class="ecalc-toggle-row">
							<label class="ecalc-toggle-opt">
								<input type="radio" name="revenue_type" value="range" checked> Chci vybrat rozsah
							</label>
							<label class="ecalc-toggle-opt">
								<input type="radio" name="revenue_type" value="exact"> Znám přesný měsíční obrat
							</label>
						</div>

						<div id="ecalc-revenue-range-wrap">
							<select class="ecalc-input ecalc-select" id="ecalc-revenue-range" name="revenue_range">
								<option value="">— Vyberte rozsah —</option>
								<?php foreach ( $revenue_ranges as $rr ) : ?>
									<option value="<?php echo esc_attr( $rr['name'] ); ?>"><?php echo esc_html( $rr['name'] ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div id="ecalc-revenue-exact-wrap" style="display:none;">
							<input class="ecalc-input" type="text" id="ecalc-revenue-exact" name="revenue_exact"
								placeholder="Zadejte měsíční obrat v Kč" inputmode="numeric" autocomplete="off">
						</div>
						<span class="ecalc-error-msg" id="ecalc-error-revenue"></span>
					</div>

					<div class="ecalc-field">
						<label class="ecalc-label" for="ecalc-pno">Kolik % z tržeb chcete investovat do emailingu? (PNO) <span class="ecalc-required">*</span></label>
						<input class="ecalc-input ecalc-input--short" type="number" id="ecalc-pno" name="expected_pno"
							placeholder="např. 10" min="1" max="100" step="1" required>
						<p class="ecalc-hint">Např. 10 % = cena za emailing má tvořit max. 10 % z tržeb, které emailing vygeneruje.</p>
						<span class="ecalc-error-msg" id="ecalc-error-pno"></span>
					</div>

					<p class="ecalc-contact-divider">Skvělé, teď už jen kontakt pro zaslání výsledků:</p>

					<div class="ecalc-field">
						<label class="ecalc-label" for="ecalc-shop-url">URL e-shopu <span class="ecalc-required">*</span></label>
						<input class="ecalc-input" type="text" id="ecalc-shop-url" name="shop_url" placeholder="mujshop.cz" required>
						<span class="ecalc-error-msg" id="ecalc-error-shop-url"></span>
					</div>

					<div class="ecalc-field">
						<label class="ecalc-label" for="ecalc-name">Jméno a příjmení <span class="ecalc-required">*</span></label>
						<input class="ecalc-input" type="text" id="ecalc-name" name="name" placeholder="Jan Novák" required>
						<span class="ecalc-error-msg" id="ecalc-error-name"></span>
					</div>

					<div class="ecalc-field">
						<label class="ecalc-label" for="ecalc-email">E-mail <span class="ecalc-required">*</span></label>
						<input class="ecalc-input" type="email" id="ecalc-email" name="email" placeholder="jan@mujshop.cz" required>
						<span class="ecalc-error-msg" id="ecalc-error-email"></span>
					</div>

					<div class="ecalc-field">
						<label class="ecalc-label" for="ecalc-phone">Telefon <span class="ecalc-required">*</span></label>
						<input class="ecalc-input" type="tel" id="ecalc-phone" name="phone" placeholder="+420 777 123 456" autocomplete="tel" required>
						<span class="ecalc-error-msg" id="ecalc-error-phone"></span>
					</div>

					<div class="ecalc-field ecalc-field--checkbox">
						<label class="ecalc-checkbox-label">
							<input type="checkbox" id="ecalc-consent-data" name="consent_data" value="1" required>
							<span>Souhlasím se <a href="<?php echo esc_url( get_privacy_policy_url() ?: home_url( '/ochrana-osobnich-udaju' ) ); ?>" target="_blank" rel="noopener noreferrer">zpracováním osobních údajů</a> pro účely vyhodnocení kalkulačky. <span class="ecalc-required">*</span></span>
						</label>
						<span class="ecalc-error-msg" id="ecalc-error-consent-data"></span>
					</div>

					<div class="ecalc-field ecalc-field--checkbox">
						<label class="ecalc-checkbox-label">
							<input type="checkbox" id="ecalc-consent-marketing" name="consent_marketing" value="1"
								<?php echo $marketing_required ? 'required' : ''; ?>>
							<span>Souhlasím s odesíláním marketingové komunikace e-mailem.<span class="ecalc-required"><?php echo $marketing_required ? ' *' : ''; ?></span></span>
						</label>
						<span class="ecalc-error-msg" id="ecalc-error-consent-marketing"></span>
					</div>

					<div id="ecalc-form-error" class="ecalc-notice ecalc-notice--error" style="display:none;"></div>

					<?php if ( ! empty( $turnstile_enabled ) && ! empty( $turnstile_site_key ) ) : ?>
					<div id="ecalc-turnstile-wrap" style="margin-bottom:16px;">
						<div class="cf-turnstile" data-sitekey="<?php echo esc_attr( $turnstile_site_key ); ?>" data-theme="light"></div>
					</div>
					<?php endif; ?>

					<?php if ( ! empty( $cfg['social_proof_enabled_form'] ) && ! empty( $cfg['social_proof_shortcode'] ) ) : ?>
						<div class="ecalc-social-proof-inline">
							<?php echo do_shortcode( $cfg['social_proof_shortcode'] ); ?>
						</div>
					<?php endif; ?>

					<button type="submit" class="ecalc-button" id="ecalc-submit"
						data-gtm-id="ecalc-form-submit">
						<span id="ecalc-btn-text">Vypočítat potenciál emailingu</span>
						<span id="ecalc-btn-loader" style="display:none;">Počítám...</span>
					</button>
					<?php if ( ! empty( $cfg['form_submit_note'] ) ) : ?>
						<p class="ecalc-submit-note"><?php echo esc_html( $cfg['form_submit_note'] ); ?></p>
					<?php endif; ?>
				</form>
			</div>
		</div>

		<!-- ===================== INFORMAČNÍ PANEL ===================== -->
		<div class="ecalc-info-col" id="ecalc-info-panel">
			<div class="ecalc-card ecalc-dark-card">
				<h3 class="ecalc-dark-title"><?php echo esc_html( $info_panel['title'] ); ?></h3>
				<ul class="ecalc-info-list">
					<?php foreach ( $info_panel['items'] as $item ) : ?>
						<li><?php echo esc_html( $item ); ?></li>
					<?php endforeach; ?>
				</ul>
				<?php if ( ! empty( $info_panel['note'] ) ) : ?>
					<p class="ecalc-dark-note"><?php echo esc_html( $info_panel['note'] ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<!-- ===================== VÝSLEDEK ===================== -->
		<div class="ecalc-result-col" id="ecalc-result" style="display:none;">
			<div id="ecalc-result-inner"></div>
			<?php if ( ! empty( $cfg['social_proof_enabled_result'] ) && ! empty( $cfg['social_proof_shortcode'] ) ) : ?>
				<div class="ecalc-card ecalc-social-proof-card ecalc-social-proof-result">
					<?php echo do_shortcode( $cfg['social_proof_shortcode'] ); ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
