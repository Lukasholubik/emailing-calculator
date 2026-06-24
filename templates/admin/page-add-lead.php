<?php
/**
 * Admin stránka – Přidat lead ručně.
 * Bez notifikací klientovi. Pouze interní záznam + sync do SmartEmailingu.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap">
	<h1>➕ Přidat lead ručně</h1>
	<p style="color:#50575e;max-width:600px">
		Interní záznam bez jakýchkoliv notifikací klientovi. Lead bude uložen do databáze a synchronizován do SmartEmailingu se zvoleným stavem a štítky.
	</p>

	<div id="ecalc-add-lead-notice" style="display:none;margin-bottom:16px"></div>

	<form id="ecalc-add-lead-form" style="max-width:680px">
		<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>">

		<table class="form-table" role="presentation">

			<!-- Jméno -->
			<tr>
				<th scope="row"><label for="ecalc-ml-name">Jméno <span style="color:#d63638">*</span></label></th>
				<td>
					<input type="text" id="ecalc-ml-name" name="name" class="regular-text" required placeholder="Jan Novák">
				</td>
			</tr>

			<!-- E-mail -->
			<tr>
				<th scope="row"><label for="ecalc-ml-email">E-mail <span style="color:#d63638">*</span></label></th>
				<td>
					<input type="email" id="ecalc-ml-email" name="email" class="regular-text" required placeholder="jan@example.cz">
				</td>
			</tr>

			<!-- Telefon -->
			<tr>
				<th scope="row"><label for="ecalc-ml-phone">Telefon</label></th>
				<td>
					<input type="tel" id="ecalc-ml-phone" name="phone" class="regular-text" placeholder="+420 777 123 456">
				</td>
			</tr>

			<!-- URL e-shopu -->
			<tr>
				<th scope="row"><label for="ecalc-ml-url">URL e-shopu</label></th>
				<td>
					<input type="url" id="ecalc-ml-url" name="shop_url" class="regular-text" placeholder="https://...">
				</td>
			</tr>

			<!-- Obrat -->
			<tr>
				<th scope="row"><label for="ecalc-ml-revenue">Měsíční obrat (Kč)</label></th>
				<td>
					<input type="number" id="ecalc-ml-revenue" name="monthly_revenue" class="regular-text" min="0" step="1000" placeholder="0">
					<p class="description">Orientační hodnota pro interní přehled.</p>
				</td>
			</tr>

			<!-- Oblast podnikání -->
			<tr>
				<th scope="row"><label for="ecalc-ml-segment">Oblast podnikání</label></th>
				<td>
					<select id="ecalc-ml-segment" name="segment" style="min-width:200px">
						<option value="">— nevybráno —</option>
						<?php foreach ( $segments as $seg ) :
							$slug  = is_array( $seg ) ? ( $seg['slug']  ?? $seg['name'] ?? '' ) : $seg;
							$label = is_array( $seg ) ? ( $seg['label'] ?? $seg['name'] ?? $slug ) : $seg;
						?>
						<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>

			<!-- Stav leadu -->
			<tr>
				<th scope="row"><label for="ecalc-ml-status">Stav leadu <span style="color:#d63638">*</span></label></th>
				<td>
					<select id="ecalc-ml-status" name="lead_status" style="min-width:200px">
						<?php foreach ( $statuses as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description">Stav se pošle jako štítek do SmartEmailingu.</p>
				</td>
			</tr>

			<!-- Doporučený balíček -->
			<tr>
				<th scope="row"><label for="ecalc-ml-package">Doporučený balíček</label></th>
				<td>
					<select id="ecalc-ml-package" name="package" style="min-width:200px">
						<option value="">— nevybráno —</option>
						<?php foreach ( $packages as $pkg ) :
							$pname  = is_array( $pkg ) ? ( $pkg['name']  ?? '' ) : $pkg;
							$pprice = is_array( $pkg ) ? ( $pkg['price'] ?? 0 )  : 0;
						?>
						<option value="<?php echo esc_attr( $pname ); ?>">
							<?php echo esc_html( $pname ); ?>
							<?php if ( $pprice ) : ?>
								(<?php echo number_format( (float) $pprice, 0, ',', ' ' ); ?> Kč)
							<?php endif; ?>
						</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>

			<!-- Interní poznámka -->
			<tr>
				<th scope="row"><label for="ecalc-ml-note">Interní poznámka</label></th>
				<td>
					<textarea id="ecalc-ml-note" name="note" rows="3" class="large-text" placeholder="Kontaktoval přes LinkedIn, zájem o email automatizaci…"></textarea>
					<p class="description">Uloží se do logu leadu. Klient ji nevidí.</p>
				</td>
			</tr>

		</table>

		<p class="submit">
			<button type="submit" id="ecalc-add-lead-btn" class="button button-primary button-large">
				💾 Uložit lead a odeslat do SmartEmailingu
			</button>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ecalc_leads' ) ); ?>" class="button button-large" style="margin-left:8px">
				Zpět na leady
			</a>
		</p>
	</form>
</div>

<script>
(function () {
	'use strict';

	var form    = document.getElementById( 'ecalc-add-lead-form' );
	var btn     = document.getElementById( 'ecalc-add-lead-btn' );
	var notice  = document.getElementById( 'ecalc-add-lead-notice' );

	function showNotice( msg, type ) {
		notice.className = 'notice notice-' + type + ' is-dismissible';
		notice.innerHTML = '<p>' + msg + '</p>';
		notice.style.display = '';
		notice.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
	}

	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault();

		btn.disabled    = true;
		btn.textContent = '⏳ Ukládám…';
		notice.style.display = 'none';

		var fd = new FormData( form );
		fd.append( 'action', 'ecalc_add_manual_lead' );

		fetch( '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>', {
			method: 'POST',
			credentials: 'same-origin',
			body: fd,
		} )
		.then( function ( r ) { return r.json(); } )
		.then( function ( d ) {
			btn.disabled    = false;
			btn.textContent = '💾 Uložit lead a odeslat do SmartEmailingu';

			if ( ! d.success ) {
				showNotice( '✗ ' + ( d.data && d.data.message ? d.data.message : 'Chyba.' ), 'error' );
				return;
			}

			var seInfo = d.data.se_status ? ' · SmartEmailing: <strong>' + d.data.se_status + '</strong>' : '';
			showNotice(
				'✅ ' + d.data.message + seInfo +
				' &nbsp;<a href="' + d.data.detail_url + '">Zobrazit detail →</a>',
				'success'
			);

			// Vymaž formulář pro případný další zápis
			form.reset();
		} )
		.catch( function () {
			btn.disabled    = false;
			btn.textContent = '💾 Uložit lead a odeslat do SmartEmailingu';
			showNotice( '✗ Chyba sítě. Zkus to znovu.', 'error' );
		} );
	} );
}());
</script>
