<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap ecalc-admin">
	<h1>Argumenty – proč máte tento potenciál</h1>

	<?php if ( $notice['type'] ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible"><p><?php echo esc_html( $notice['message'] ); ?></p></div>
	<?php endif; ?>

	<div class="ecalc-info-box">
		<strong>K čemu tato sekce slouží:</strong> U pozitivního výsledku (doporučený balíček) a u hraničního výsledku
		se pod textem výsledku zobrazí 3 argumentační body – proč má e-shop právě tento potenciál – vysvětlující
		vliv opakovaného nákupu, velikosti databáze a oboru podnikání. Text pro každý faktor se vybere podle toho,
		do jakého pásma skóre (nízké / střední / vysoké) e-shop v daném faktoru spadá.
	</div>

	<div class="ecalc-info-box">
		<strong>Dostupné proměnné v textech:</strong>
		<code>{segment}</code> <code>{consumable_percentage}</code> <code>{database_range}</code>
		<code>{final_potential}</code> <code>{emailing_revenue_low}</code> <code>{emailing_revenue_mid}</code>
		<code>{emailing_revenue_high}</code> <code>{monthly_revenue}</code> <code>{expected_pno}</code>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="ecalc_save_arguments">
		<?php wp_nonce_field( 'ecalc_save_arguments' ); ?>

		<table class="form-table">
			<tr>
				<th><label for="enabled">Zobrazovat argumenty</label></th>
				<td>
					<label>
						<input type="checkbox" id="enabled" name="enabled" value="1" <?php checked( ! empty( $args['enabled'] ) ); ?>>
						Zapnuto
					</label>
				</td>
			</tr>
			<tr>
				<th><label for="title">Nadpis sekce</label></th>
				<td><input type="text" id="title" name="title" value="<?php echo esc_attr( $args['title'] ?? '' ); ?>" class="large-text"></td>
			</tr>
			<tr>
				<th><label for="subtitle">Podnadpis / vysvětlivka</label></th>
				<td>
					<input type="text" id="subtitle" name="subtitle" value="<?php echo esc_attr( $args['subtitle'] ?? '' ); ?>" class="large-text">
					<p class="description">Zobrazí se pod nadpisem – dej najevo, že jde o výběr hlavních (top 3) důvodů, ne o kompletní výčet.</p>
				</td>
			</tr>
			<tr>
				<th><label for="threshold_medium">Práh – střední pásmo</label></th>
				<td>
					<input type="text" id="threshold_medium" name="threshold_medium" value="<?php echo esc_attr( $args['threshold_medium'] ?? '0.34' ); ?>" class="small-text">
					<p class="description">Skóre faktoru (0–1) od kterého se použije text „střední pásmo". Pod touto hodnotou se použije „nízké pásmo".</p>
				</td>
			</tr>
			<tr>
				<th><label for="threshold_high">Práh – vysoké pásmo</label></th>
				<td>
					<input type="text" id="threshold_high" name="threshold_high" value="<?php echo esc_attr( $args['threshold_high'] ?? '0.67' ); ?>" class="small-text">
					<p class="description">Skóre faktoru (0–1) od kterého se použije text „vysoké pásmo".</p>
				</td>
			</tr>
		</table>

		<h2>Spotřební zboží / opakovaný nákup <small>(váha 70 % výpočtu)</small></h2>
		<table class="form-table">
			<tr>
				<th><label for="consumable_low">Nízké pásmo</label></th>
				<td><textarea id="consumable_low" name="consumable_low" rows="3" class="large-text"><?php echo esc_textarea( $args['consumable_low'] ?? '' ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="consumable_medium">Střední pásmo</label></th>
				<td><textarea id="consumable_medium" name="consumable_medium" rows="3" class="large-text"><?php echo esc_textarea( $args['consumable_medium'] ?? '' ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="consumable_high">Vysoké pásmo</label></th>
				<td><textarea id="consumable_high" name="consumable_high" rows="3" class="large-text"><?php echo esc_textarea( $args['consumable_high'] ?? '' ); ?></textarea></td>
			</tr>
		</table>

		<h2>Databáze kontaktů <small>(váha 20 % výpočtu)</small></h2>
		<table class="form-table">
			<tr>
				<th><label for="database_low">Nízké pásmo</label></th>
				<td><textarea id="database_low" name="database_low" rows="3" class="large-text"><?php echo esc_textarea( $args['database_low'] ?? '' ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="database_medium">Střední pásmo</label></th>
				<td><textarea id="database_medium" name="database_medium" rows="3" class="large-text"><?php echo esc_textarea( $args['database_medium'] ?? '' ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="database_high">Vysoké pásmo</label></th>
				<td><textarea id="database_high" name="database_high" rows="3" class="large-text"><?php echo esc_textarea( $args['database_high'] ?? '' ); ?></textarea></td>
			</tr>
		</table>

		<h2>Obor podnikání <small>(váha 10 % výpočtu)</small></h2>
		<table class="form-table">
			<tr>
				<th><label for="segment_low">Nízké pásmo</label></th>
				<td><textarea id="segment_low" name="segment_low" rows="3" class="large-text"><?php echo esc_textarea( $args['segment_low'] ?? '' ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="segment_medium">Střední pásmo</label></th>
				<td><textarea id="segment_medium" name="segment_medium" rows="3" class="large-text"><?php echo esc_textarea( $args['segment_medium'] ?? '' ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="segment_high">Vysoké pásmo</label></th>
				<td><textarea id="segment_high" name="segment_high" rows="3" class="large-text"><?php echo esc_textarea( $args['segment_high'] ?? '' ); ?></textarea></td>
			</tr>
		</table>

		<h2>Shrnutí</h2>
		<table class="form-table">
			<tr>
				<th><label for="summary">Závěrečná věta</label></th>
				<td>
					<textarea id="summary" name="summary" rows="3" class="large-text"><?php echo esc_textarea( $args['summary'] ?? '' ); ?></textarea>
					<p class="description">Zobrazí se pod třemi argumentačními body jako shrnutí s konkrétní částkou.</p>
				</td>
			</tr>
		</table>

		<?php submit_button( 'Uložit argumenty' ); ?>
	</form>
</div>
