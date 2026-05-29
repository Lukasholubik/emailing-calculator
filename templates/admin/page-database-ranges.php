<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap ecalc-admin">
	<h1>Rozsahy databáze kontaktů</h1>

	<?php if ( $notice['type'] ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible"><p><?php echo esc_html( $notice['message'] ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="ecalc_save_database_ranges">
		<?php wp_nonce_field( 'ecalc_save_database_ranges' ); ?>

		<table class="wp-list-table widefat fixed striped ecalc-crud-table">
			<thead>
				<tr>
					<th>Pořadí</th>
					<th>Název rozsahu</th>
					<th>Min</th>
					<th>Max (prázdné = otevřený)</th>
					<th>Skóre (0–1)</th>
					<th>Aktivní</th>
					<th>Akce</th>
				</tr>
			</thead>
			<tbody id="ecalc-db-ranges-body">
				<?php foreach ( $ranges as $i => $r ) : ?>
					<tr class="ecalc-row">
						<td><input type="number" name="ranges[<?php echo (int) $i; ?>][order]" value="<?php echo (int) $r['order']; ?>" class="small-text"></td>
						<td>
							<input type="hidden" name="ranges[<?php echo (int) $i; ?>][id]" value="<?php echo (int) $r['id']; ?>">
							<input type="text" name="ranges[<?php echo (int) $i; ?>][name]" value="<?php echo esc_attr( $r['name'] ); ?>" class="regular-text">
						</td>
						<td><input type="number" name="ranges[<?php echo (int) $i; ?>][min]" value="<?php echo esc_attr( $r['min'] ); ?>" class="small-text" min="0"></td>
						<td><input type="number" name="ranges[<?php echo (int) $i; ?>][max]" value="<?php echo esc_attr( $r['max'] ?? '' ); ?>" class="small-text" min="0" placeholder="∞"></td>
						<td><input type="number" name="ranges[<?php echo (int) $i; ?>][score]" value="<?php echo esc_attr( $r['score'] ); ?>" step="0.01" min="0" max="1" class="small-text"></td>
						<td><input type="checkbox" name="ranges[<?php echo (int) $i; ?>][active]" value="1" <?php checked( $r['active'] ); ?>></td>
						<td><button type="button" class="button button-small ecalc-remove-row">Odebrat</button></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<div class="ecalc-table-actions">
			<button type="button" class="button" id="ecalc-add-db-range"
				data-body="ecalc-db-ranges-body"
				data-tpl="ecalc-db-range-row-tpl">+ Přidat rozsah</button>
		</div>

		<script type="text/template" id="ecalc-db-range-row-tpl">
			<tr class="ecalc-row">
				<td><input type="number" name="ranges[__IDX__][order]" value="__ORDER__" class="small-text"></td>
				<td>
					<input type="hidden" name="ranges[__IDX__][id]" value="0">
					<input type="text" name="ranges[__IDX__][name]" value="" class="regular-text" placeholder="Název rozsahu">
				</td>
				<td><input type="number" name="ranges[__IDX__][min]" value="0" class="small-text" min="0"></td>
				<td><input type="number" name="ranges[__IDX__][max]" value="" class="small-text" min="0" placeholder="∞"></td>
				<td><input type="number" name="ranges[__IDX__][score]" value="0.00" step="0.01" min="0" max="1" class="small-text"></td>
				<td><input type="checkbox" name="ranges[__IDX__][active]" value="1" checked></td>
				<td><button type="button" class="button button-small ecalc-remove-row">Odebrat</button></td>
			</tr>
		</script>

		<?php submit_button( 'Uložit rozsahy' ); ?>
	</form>
</div>
