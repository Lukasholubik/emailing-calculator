<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap ecalc-admin">
	<h1>Výpočet</h1>

	<?php if ( $notice['type'] ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible"><p><?php echo esc_html( $notice['message'] ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="ecalc_save_settings">
		<?php wp_nonce_field( 'ecalc_save_settings' ); ?>

		<div class="ecalc-settings-grid">
			<div class="ecalc-settings-section">
				<h2>Potenciál emailingu</h2>
				<table class="form-table">
					<tr>
						<th><label for="min_potential">Minimální potenciál (%)</label></th>
						<td><input type="number" id="min_potential" name="min_potential" value="<?php echo esc_attr( $cfg['min_potential'] ); ?>" step="0.1" min="0" max="100" class="small-text"></td>
					</tr>
					<tr>
						<th><label for="max_potential">Maximální potenciál (%)</label></th>
						<td><input type="number" id="max_potential" name="max_potential" value="<?php echo esc_attr( $cfg['max_potential'] ); ?>" step="0.1" min="0" max="100" class="small-text"></td>
					</tr>
				</table>
			</div>

			<div class="ecalc-settings-section">
				<h2>Váhy skóre <span class="ecalc-hint-inline">(součet musí být 100 %)</span></h2>
				<table class="form-table">
					<tr>
						<th><label for="consumable_weight">Váha spotřebního zboží (%)</label></th>
						<td><input type="number" id="consumable_weight" name="consumable_weight" value="<?php echo esc_attr( $cfg['consumable_weight'] ); ?>" step="1" min="0" max="100" class="small-text" id="ecalc-w1"></td>
					</tr>
					<tr>
						<th><label for="database_weight">Váha databáze (%)</label></th>
						<td><input type="number" id="database_weight" name="database_weight" value="<?php echo esc_attr( $cfg['database_weight'] ); ?>" step="1" min="0" max="100" class="small-text" id="ecalc-w2"></td>
					</tr>
					<tr>
						<th><label for="segment_weight">Váha segmentu (%)</label></th>
						<td><input type="number" id="segment_weight" name="segment_weight" value="<?php echo esc_attr( $cfg['segment_weight'] ); ?>" step="1" min="0" max="100" class="small-text" id="ecalc-w3"></td>
					</tr>
					<tr>
						<th>Součet vah</th>
						<td><span id="ecalc-weights-sum" class="ecalc-weight-sum">
							<?php echo esc_html( $cfg['consumable_weight'] + $cfg['database_weight'] + $cfg['segment_weight'] ); ?> %
						</span></td>
					</tr>
				</table>
			</div>

			<div class="ecalc-settings-section">
				<h2>Multiplikátory scénářů</h2>
				<table class="form-table">
					<tr>
						<th><label for="conservative_multiplier">Konzervativní multiplikátor</label></th>
						<td><input type="number" id="conservative_multiplier" name="conservative_multiplier" value="<?php echo esc_attr( $cfg['conservative_multiplier'] ); ?>" step="0.01" min="0" max="2" class="small-text">
						<p class="description">Výchozí: 0.85 (nízký scénář = mid × 0.85)</p></td>
					</tr>
					<tr>
						<th><label for="optimistic_multiplier">Optimistický multiplikátor</label></th>
						<td><input type="number" id="optimistic_multiplier" name="optimistic_multiplier" value="<?php echo esc_attr( $cfg['optimistic_multiplier'] ); ?>" step="0.01" min="0" max="2" class="small-text">
						<p class="description">Výchozí: 1.15 (vysoký scénář = mid × 1.15)</p></td>
					</tr>
				</table>
			</div>

			<div class="ecalc-settings-section">
				<h2>Hranice doporučení</h2>
				<table class="form-table">
					<tr>
						<th><label for="low_potential_threshold">Hranice nízkého potenciálu (Kč)</label></th>
						<td><input type="number" id="low_potential_threshold" name="low_potential_threshold" value="<?php echo esc_attr( $cfg['low_potential_threshold'] ); ?>" step="100" min="0" class="medium-text">
						<p class="description">Budget pod touto hodnotou = nízký potenciál. Výchozí: 10 000 Kč</p></td>
					</tr>
					<tr>
						<th><label for="borderline_threshold">Hranice hraničního potenciálu (Kč)</label></th>
						<td><input type="number" id="borderline_threshold" name="borderline_threshold" value="<?php echo esc_attr( $cfg['borderline_threshold'] ); ?>" step="100" min="0" class="medium-text">
						<p class="description">Budget pod touto hodnotou (ale nad nízkým) = hraniční. Výchozí: 15 000 Kč</p></td>
					</tr>
				</table>
			</div>
		</div>

		<?php submit_button( 'Uložit nastavení výpočtu' ); ?>
	</form>
</div>
