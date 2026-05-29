<?php if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * @var array  $appearance
 * @var array  $notice
 */
$a = $appearance;

function ecalc_color_row( string $label, string $key, array $a, string $default = '#000000', bool $text_only = false ): void {
	$val = $a[ $key ] ?? $default;
	echo "<tr><th><label for=\"{$key}\">{$label}</label></th><td>";
	if ( $text_only ) {
		echo "<input type=\"text\" id=\"{$key}\" name=\"{$key}\" value=\"" . esc_attr( $val ) . "\" class=\"small-text\" placeholder=\"" . esc_attr( $default ) . "\">";
	} else {
		$hex = preg_match( '/^#[0-9a-fA-F]{6}$/', $val ) ? $val : $default;
		echo "<input type=\"color\" id=\"{$key}\" name=\"{$key}\" value=\"" . esc_attr( $hex ) . "\">";
	}
	echo "</td></tr>";
}

function ecalc_text_row( string $label, string $key, array $a, string $placeholder = '' ): void {
	$val = esc_attr( $a[ $key ] ?? '' );
	echo "<tr><th><label for=\"{$key}\">{$label}</label></th>";
	echo "<td><input type=\"text\" id=\"{$key}\" name=\"{$key}\" value=\"{$val}\" class=\"small-text\" placeholder=\"" . esc_attr( $placeholder ) . "\"></td></tr>";
}

function ecalc_select_row( string $label, string $key, array $a, array $options ): void {
	echo "<tr><th><label for=\"{$key}\">{$label}</label></th><td><select id=\"{$key}\" name=\"{$key}\">";
	foreach ( $options as $val => $txt ) {
		$sel = selected( $a[ $key ] ?? '', $val, false );
		echo "<option value=\"" . esc_attr( $val ) . "\" {$sel}>" . esc_html( $txt ) . "</option>";
	}
	echo "</select></td></tr>";
}
?>
<div class="wrap ecalc-admin">
	<h1>Vzhled kalkulačky</h1>

	<?php if ( $notice['type'] ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible"><p><?php echo esc_html( $notice['message'] ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="ecalc_save_appearance">
		<?php wp_nonce_field( 'ecalc_save_appearance' ); ?>

		<!-- === ZÁKLADNÍ === -->
		<div class="ecalc-appear-section">
			<h2>🎨 Základní barvy a text</h2>
			<table class="form-table">
				<?php ecalc_text_row( 'Základní velikost písma', 'base_font_size', $a, '15px' ); ?>
				<?php ecalc_color_row( 'Barva hlavního textu', 'base_text_color', $a, '#1e293b' ); ?>
				<?php ecalc_color_row( 'Barva doplňkového textu (popisky, nápovědy)', 'muted_text_color', $a, '#475569' ); ?>
				<?php ecalc_color_row( 'Barva jemného textu (placeholder, hint)', 'subtle_text_color', $a, '#94a3b8' ); ?>
				<?php ecalc_color_row( 'Primární akcent (čísla výsledků, linky)', 'primary_accent', $a, '#4de5c4' ); ?>
			</table>
		</div>

		<!-- === NADPISY === -->
		<div class="ecalc-appear-section">
			<h2>🔤 Nadpisy</h2>
			<h3 class="ecalc-appear-subheading">H1 – Název výsledku</h3>
			<table class="form-table">
				<?php ecalc_text_row( 'Velikost', 'h1_size', $a, '1.6rem' ); ?>
				<?php ecalc_color_row( 'Barva', 'h1_color', $a, '#1e293b' ); ?>
				<?php ecalc_select_row( 'Tloušťka', 'h1_weight', $a, ['400'=>'Normální (400)','600'=>'Semibold (600)','700'=>'Tučné (700)','800'=>'Extra tučné (800)'] ); ?>
			</table>

			<h3 class="ecalc-appear-subheading">H2 – Název formuláře / sekce</h3>
			<table class="form-table">
				<?php ecalc_text_row( 'Velikost', 'h2_size', $a, '1.45rem' ); ?>
				<?php ecalc_color_row( 'Barva', 'h2_color', $a, '#1e293b' ); ?>
				<?php ecalc_select_row( 'Tloušťka', 'h2_weight', $a, ['400'=>'Normální (400)','600'=>'Semibold (600)','700'=>'Tučné (700)','800'=>'Extra tučné (800)'] ); ?>
			</table>

			<h3 class="ecalc-appear-subheading">H3 – Názvy balíčků a karet</h3>
			<table class="form-table">
				<?php ecalc_text_row( 'Velikost', 'h3_size', $a, '1.1rem' ); ?>
				<?php ecalc_color_row( 'Barva', 'h3_color', $a, '#1e293b' ); ?>
				<?php ecalc_select_row( 'Tloušťka', 'h3_weight', $a, ['400'=>'Normální (400)','600'=>'Semibold (600)','700'=>'Tučné (700)','800'=>'Extra tučné (800)'] ); ?>
			</table>
		</div>

		<!-- === PRIMÁRNÍ TLAČÍTKO === -->
		<div class="ecalc-appear-section">
			<h2>🔵 Primární tlačítko <span class="ecalc-appear-hint">(Vypočítat, Chci konzultaci zdarma)</span></h2>

			<div class="ecalc-appear-cols">
				<div>
					<h3 class="ecalc-appear-subheading">Normální stav</h3>
					<table class="form-table">
						<?php ecalc_color_row( 'Pozadí', 'btn_bg', $a, '#4de5c4' ); ?>
						<?php ecalc_color_row( 'Barva textu', 'btn_color', $a, '#352830' ); ?>
						<?php ecalc_color_row( 'Barva rámečku', 'btn_border', $a, '#4de5c4' ); ?>
					</table>
				</div>
				<div>
					<h3 class="ecalc-appear-subheading">Hover stav</h3>
					<table class="form-table">
						<?php ecalc_color_row( 'Pozadí při hoveru', 'btn_bg_hover', $a, '#ffffff' ); ?>
						<?php ecalc_color_row( 'Barva textu při hoveru', 'btn_color_hover', $a, '#4de5c4' ); ?>
						<?php ecalc_color_row( 'Barva rámečku při hoveru', 'btn_border_hover', $a, '#4de5c4' ); ?>
					</table>
				</div>
			</div>

			<h3 class="ecalc-appear-subheading">Rozměry a typografie</h3>
			<table class="form-table">
				<?php ecalc_text_row( 'Šířka rámečku', 'btn_border_width', $a, '2px' ); ?>
				<?php ecalc_text_row( 'Velikost písma', 'btn_font_size', $a, '1rem' ); ?>
				<?php ecalc_select_row( 'Tloušťka písma', 'btn_font_weight', $a, ['400'=>'Normální (400)','600'=>'Semibold (600)','700'=>'Tučné (700)','800'=>'Extra tučné (800)'] ); ?>
				<?php ecalc_text_row( 'Vnitřní odsazení – výška (padding-top/bottom)', 'btn_pad_y', $a, '14px' ); ?>
				<?php ecalc_text_row( 'Vnitřní odsazení – šířka (padding-left/right)', 'btn_pad_x', $a, '32px' ); ?>
			</table>
		</div>

		<!-- === SEKUNDÁRNÍ TLAČÍTKO === -->
		<div class="ecalc-appear-section">
			<h2>⚪ Sekundární tlačítko <span class="ecalc-appear-hint">(Poptat balíček – nedoporučená varianta, outline)</span></h2>

			<div class="ecalc-appear-cols">
				<div>
					<h3 class="ecalc-appear-subheading">Normální stav</h3>
					<table class="form-table">
						<?php ecalc_color_row( 'Pozadí (hex nebo "transparent")', 'btn2_bg', $a, 'transparent', true ); ?>
						<?php ecalc_color_row( 'Barva textu', 'btn2_color', $a, '#4de5c4' ); ?>
						<?php ecalc_color_row( 'Barva rámečku', 'btn2_border', $a, '#4de5c4' ); ?>
					</table>
				</div>
				<div>
					<h3 class="ecalc-appear-subheading">Hover stav</h3>
					<table class="form-table">
						<?php ecalc_color_row( 'Pozadí při hoveru', 'btn2_bg_hover', $a, '#4de5c4' ); ?>
						<?php ecalc_color_row( 'Barva textu při hoveru', 'btn2_color_hover', $a, '#352830' ); ?>
						<?php ecalc_color_row( 'Barva rámečku při hoveru', 'btn2_border_hover', $a, '#4de5c4' ); ?>
					</table>
				</div>
			</div>
		</div>

		<!-- === FORMULÁŘ === -->
		<div class="ecalc-appear-section">
			<h2>📝 Formulář – vstupní pole</h2>
			<table class="form-table">
				<?php ecalc_color_row( 'Barva rámečku inputu (normální)', 'input_border', $a, '#e2e8f0' ); ?>
				<?php ecalc_color_row( 'Barva rámečku inputu (focus/aktivní)', 'input_border_focus', $a, '#4de5c4' ); ?>
				<?php ecalc_color_row( 'Pozadí inputu', 'input_bg', $a, '#f8fafc' ); ?>
				<?php ecalc_color_row( 'Barva popisků (label)', 'label_color', $a, '#1e293b' ); ?>
			</table>
		</div>

		<!-- === LAYOUT === -->
		<div class="ecalc-appear-section">
			<h2>🏗️ Layout a karty</h2>
			<table class="form-table">
				<tr>
					<th><label for="border_radius">Zaoblení rohů (border-radius)</label></th>
					<td><input type="text" id="border_radius" name="border_radius" value="<?php echo esc_attr( $a['border_radius'] ?? '12px' ); ?>" class="small-text" placeholder="12px">
					<p class="description">Platí pro karty, výsledkové boxy. Tlačítka jsou vždy rovná (skew efekt).</p></td>
				</tr>
				<?php ecalc_color_row( 'Pozadí karet', 'card_bg', $a, '#ffffff' ); ?>
				<?php ecalc_color_row( 'Rámeček karet', 'card_border', $a, '#e2e8f0' ); ?>
			</table>

			<h3 class="ecalc-appear-subheading">Tmavý informační panel (vpravo od formuláře)</h3>
			<table class="form-table">
				<?php ecalc_color_row( 'Pozadí tmavého panelu', 'dark_panel_bg', $a, '#352830' ); ?>
				<?php ecalc_color_row( 'Barva textu v tmavém panelu', 'dark_panel_text', $a, '#ffffff' ); ?>
			</table>
		</div>

		<?php submit_button( 'Uložit vzhled' ); ?>
	</form>
</div>

<style>
.ecalc-appear-section {
	background: #fff;
	border: 1px solid #e2e8f0;
	border-radius: 8px;
	padding: 20px 24px;
	margin-bottom: 20px;
}
.ecalc-appear-section h2 {
	margin: 0 0 16px;
	font-size: 1rem;
	font-weight: 700;
	padding-bottom: 10px;
	border-bottom: 1px solid #f1f5f9;
}
.ecalc-appear-hint {
	font-size: 0.8rem;
	font-weight: 400;
	color: #94a3b8;
}
.ecalc-appear-subheading {
	font-size: 0.88rem;
	font-weight: 600;
	color: #475569;
	margin: 16px 0 8px;
	text-transform: uppercase;
	letter-spacing: 0.05em;
}
.ecalc-appear-cols {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 20px;
}
@media (max-width: 900px) {
	.ecalc-appear-cols { grid-template-columns: 1fr; }
}
</style>
