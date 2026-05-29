<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap ecalc-admin">
	<h1>Info panel formuláře</h1>
	<p class="description" style="margin-bottom:20px;">Texty v tmavém panelu vpravo od formuláře kalkulačky (nadpis, odrážky, poznámka).</p>

	<?php if ( $notice['type'] ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible"><p><?php echo esc_html( $notice['message'] ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="ecalc_save_info_panel">
		<?php wp_nonce_field( 'ecalc_save_info_panel' ); ?>

		<div class="ecalc-settings-section" style="max-width:700px;">
			<table class="form-table">
				<tr>
					<th><label for="panel-title">Nadpis panelu</label></th>
					<td><input type="text" id="panel-title" name="title" value="<?php echo esc_attr( $panel['title'] ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th><label>Odrážky</label></th>
					<td>
						<div id="ecalc-info-items-list">
							<?php foreach ( $panel['items'] as $item ) : ?>
								<div class="ecalc-item-row">
									<input type="text" name="items[]" value="<?php echo esc_attr( $item ); ?>" class="regular-text" placeholder="Text odrážky">
									<button type="button" class="button button-small ecalc-remove-item">–</button>
								</div>
							<?php endforeach; ?>
						</div>
						<div class="ecalc-table-actions">
							<button type="button" class="button ecalc-add-info-item">+ Přidat odrážku</button>
						</div>
					</td>
				</tr>
				<tr>
					<th><label for="panel-note">Poznámka pod odrážkami</label></th>
					<td>
						<textarea id="panel-note" name="note" rows="3" class="large-text"><?php echo esc_textarea( $panel['note'] ); ?></textarea>
					</td>
				</tr>
			</table>
		</div>

		<?php submit_button( 'Uložit info panel' ); ?>
	</form>
</div>

<script>
(function () {
	function addItemRow(value) {
		var list = document.getElementById('ecalc-info-items-list');
		var row  = document.createElement('div');
		row.className = 'ecalc-item-row';
		row.innerHTML =
			'<input type="text" name="items[]" value="' + (value || '') + '" class="regular-text" placeholder="Text odrážky">' +
			'<button type="button" class="button button-small ecalc-remove-item">–</button>';
		list.appendChild(row);
	}

	document.querySelector('.ecalc-add-info-item').addEventListener('click', function () {
		addItemRow('');
	});

	document.getElementById('ecalc-info-items-list').addEventListener('click', function (e) {
		if (e.target.classList.contains('ecalc-remove-item')) {
			e.target.closest('.ecalc-item-row').remove();
		}
	});
})();
</script>
