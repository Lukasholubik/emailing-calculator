<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap ecalc-admin">
	<h1>Balíčky služeb</h1>

	<?php if ( $notice['type'] ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible"><p><?php echo esc_html( $notice['message'] ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="ecalc_save_packages">
		<?php wp_nonce_field( 'ecalc_save_packages' ); ?>

		<div id="ecalc-packages-list">
			<?php foreach ( $packages as $i => $pkg ) : ?>
				<div class="ecalc-package-edit" data-index="<?php echo (int) $i; ?>">
					<div class="ecalc-package-edit-header">
						<h3>Balíček: <?php echo esc_html( $pkg['name'] ); ?></h3>
						<div style="display:flex;align-items:center;gap:16px;">
							<label>
								<input type="checkbox" name="packages[<?php echo (int) $i; ?>][active]" value="1" <?php checked( $pkg['active'] ); ?>>
								Aktivní
							</label>
							<button type="button"
								class="button button-link-delete ecalc-delete-package"
								data-name="<?php echo esc_attr( $pkg['name'] ); ?>">
								Smazat balíček
							</button>
						</div>
					</div>
					<input type="hidden" name="packages[<?php echo (int) $i; ?>][id]" value="<?php echo (int) $pkg['id']; ?>">
					<table class="form-table">
						<tr>
							<th><label>Název</label></th>
							<td><input type="text" name="packages[<?php echo (int) $i; ?>][name]" value="<?php echo esc_attr( $pkg['name'] ); ?>" class="regular-text" required></td>
						</tr>
						<tr>
							<th><label>Cena (Kč/měsíc)</label></th>
							<td><input type="number" name="packages[<?php echo (int) $i; ?>][price]" value="<?php echo esc_attr( $pkg['price'] ); ?>" class="medium-text" min="0" step="100"></td>
						</tr>
						<tr>
							<th><label>Pořadí</label></th>
							<td><input type="number" name="packages[<?php echo (int) $i; ?>][order]" value="<?php echo esc_attr( $pkg['order'] ); ?>" class="small-text" min="1"></td>
						</tr>
						<tr>
							<th><label>Popis balíčku</label></th>
							<td><textarea name="packages[<?php echo (int) $i; ?>][description]" rows="3" class="large-text"><?php echo esc_textarea( $pkg['description'] ); ?></textarea></td>
						</tr>
						<tr>
							<th><label>Text výsledku</label></th>
							<td>
								<textarea name="packages[<?php echo (int) $i; ?>][result_text]" rows="4" class="large-text"><?php echo esc_textarea( $pkg['result_text'] ?? '' ); ?></textarea>
								<p class="description">Text zobrazený v kalkulačce, když je tento balíček doporučen.</p>
							</td>
						</tr>
						<tr>
							<th>
								<label>Položky balíčku</label>
								<p class="description" style="font-weight:400;">Zobrazují se jako ✓ body ve výsledkové kartě doporučeného balíčku.</p>
							</th>
							<td>
								<div class="ecalc-items-list" data-index="<?php echo (int) $i; ?>">
									<?php foreach ( ( $pkg['items'] ?? [] ) as $j => $item ) : ?>
										<div class="ecalc-item-row">
											<input type="text" name="packages[<?php echo (int) $i; ?>][items][]" value="<?php echo esc_attr( $item ); ?>" class="regular-text" placeholder="např. 4x newsletter, VIP podpora...">
											<button type="button" class="button button-small ecalc-remove-item">–</button>
										</div>
									<?php endforeach; ?>
								</div>
								<button type="button" class="button ecalc-add-item" data-pkg="<?php echo (int) $i; ?>">+ Přidat položku</button>
							</td>
						</tr>
					</table>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="ecalc-add-package-wrap">
			<button type="button" class="button button-secondary" id="ecalc-add-package">+ Přidat nový balíček</button>
		</div>

		<?php submit_button( 'Uložit balíčky' ); ?>
	</form>

	<script type="text/template" id="ecalc-package-tpl">
		<div class="ecalc-package-edit" data-index="__IDX__">
			<div class="ecalc-package-edit-header">
				<h3>Nový balíček</h3>
				<div style="display:flex;align-items:center;gap:16px;">
					<label>
						<input type="checkbox" name="packages[__IDX__][active]" value="1" checked>
						Aktivní
					</label>
					<button type="button"
						class="button button-link-delete ecalc-delete-package"
						data-name="Nový balíček">
						Smazat balíček
					</button>
				</div>
			</div>
			<input type="hidden" name="packages[__IDX__][id]" value="0">
			<table class="form-table">
				<tr>
					<th><label>Název</label></th>
					<td><input type="text" name="packages[__IDX__][name]" value="" class="regular-text" required></td>
				</tr>
				<tr>
					<th><label>Cena (Kč/měsíc)</label></th>
					<td><input type="number" name="packages[__IDX__][price]" value="0" class="medium-text" min="0" step="100"></td>
				</tr>
				<tr>
					<th><label>Pořadí</label></th>
					<td><input type="number" name="packages[__IDX__][order]" value="__ORDER__" class="small-text" min="1"></td>
				</tr>
				<tr>
					<th><label>Popis balíčku</label></th>
					<td><textarea name="packages[__IDX__][description]" rows="3" class="large-text"></textarea></td>
				</tr>
				<tr>
					<th><label>Text výsledku</label></th>
					<td><textarea name="packages[__IDX__][result_text]" rows="4" class="large-text"></textarea></td>
				</tr>
				<tr>
					<th><label>Položky balíčku</label></th>
					<td>
						<div class="ecalc-items-list" data-index="__IDX__"></div>
						<button type="button" class="button ecalc-add-item" data-pkg="__IDX__">+ Přidat položku</button>
					</td>
				</tr>
			</table>
		</div>
	</script>
</div>
