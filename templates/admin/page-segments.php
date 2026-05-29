<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap ecalc-admin">
	<h1 class="wp-heading-inline">Oblasti podnikání</h1>

	<?php if ( $notice['type'] ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible"><p><?php echo esc_html( $notice['message'] ); ?></p></div>
	<?php endif; ?>

	<div class="ecalc-info-box" style="margin-top:16px;">
		Přidávejte oblasti podnikání tlačítkem níže. Pro doplnění všech přednastavených oblastí (Domácí potřeby, Petcare, Zahrada atd.) použijte tlačítko vpravo.
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;margin-left:12px;">
			<input type="hidden" name="action" value="ecalc_merge_segments">
			<?php wp_nonce_field( 'ecalc_merge_segments' ); ?>
			<button type="submit" class="button button-secondary">&#8635; Doplnit chybějící výchozí segmenty</button>
		</form>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="ecalc_save_segments">
		<?php wp_nonce_field( 'ecalc_save_segments' ); ?>

		<table class="wp-list-table widefat fixed striped ecalc-crud-table" id="ecalc-segments-table">
			<thead>
				<tr>
					<th>Pořadí</th>
					<th>Název oblasti</th>
					<th>Skóre (0–1)</th>
					<th>Aktivní</th>
					<th>Akce</th>
				</tr>
			</thead>
			<tbody id="ecalc-segments-body">
				<?php foreach ( $segments as $i => $seg ) : ?>
					<tr class="ecalc-row">
						<td><input type="number" name="segments[<?php echo (int) $i; ?>][order]" value="<?php echo (int) $seg['order']; ?>" class="small-text"></td>
						<td>
							<input type="hidden" name="segments[<?php echo (int) $i; ?>][id]" value="<?php echo (int) $seg['id']; ?>">
							<input type="text" name="segments[<?php echo (int) $i; ?>][name]" value="<?php echo esc_attr( $seg['name'] ); ?>" class="regular-text" required>
						</td>
						<td><input type="number" name="segments[<?php echo (int) $i; ?>][score]" value="<?php echo esc_attr( $seg['score'] ); ?>" step="0.01" min="0" max="1" class="small-text"></td>
						<td><input type="checkbox" name="segments[<?php echo (int) $i; ?>][active]" value="1" <?php checked( $seg['active'] ); ?>></td>
						<td><button type="button" class="button button-small ecalc-remove-row">Odebrat</button></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<div class="ecalc-table-actions">
			<button type="button" class="button" id="ecalc-add-segment">+ Přidat oblast</button>
		</div>

		<script type="text/template" id="ecalc-segment-row-tpl">
			<tr class="ecalc-row">
				<td><input type="number" name="segments[__IDX__][order]" value="__ORDER__" class="small-text"></td>
				<td>
					<input type="hidden" name="segments[__IDX__][id]" value="0">
					<input type="text" name="segments[__IDX__][name]" value="" class="regular-text" placeholder="Název oblasti" required>
				</td>
				<td><input type="number" name="segments[__IDX__][score]" value="0.50" step="0.01" min="0" max="1" class="small-text"></td>
				<td><input type="checkbox" name="segments[__IDX__][active]" value="1" checked></td>
				<td><button type="button" class="button button-small ecalc-remove-row">Odebrat</button></td>
			</tr>
		</script>

		<?php submit_button( 'Uložit segmenty' ); ?>
	</form>
</div>
