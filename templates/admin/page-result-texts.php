<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap ecalc-admin">
	<h1>Texty výsledků</h1>

	<?php if ( $notice['type'] ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible"><p><?php echo esc_html( $notice['message'] ); ?></p></div>
	<?php endif; ?>

	<div class="ecalc-info-box ecalc-info-box--info">
		<strong>Návštěvník balíčky ani ceny nevidí.</strong> Výsledek ukazuje odhad (potenciál, obrat, budget, verdikt) a jedinou výzvu: rezervaci konzultace.
		Doporučený balíček se dál ukládá k leadu, posílá v e-mailu adminovi a do SmartEmailingu. Z e-mailů klientovi se řádky s proměnnou balíčku vynechávají automaticky.
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="ecalc_save_result_texts">
		<?php wp_nonce_field( 'ecalc_save_result_texts' ); ?>

		<table class="form-table">
			<tr>
				<th><label for="result_title">Nadpis výsledku</label></th>
				<td><input type="text" id="result_title" name="result_title" value="<?php echo esc_attr( $texts['result_title'] ?? 'Výsledek vaší kalkulačky' ); ?>" class="large-text"></td>
			</tr>
			<tr>
				<th><label for="good">Text – Vyplatí se</label></th>
				<td>
					<textarea id="good" name="good" rows="5" class="large-text"><?php echo esc_textarea( $texts['good'] ?? '' ); ?></textarea>
					<p class="description">Zobrazí se, když se do budgetu při zadaném PNO vejde profesionální správa.</p>
				</td>
			</tr>
			<tr>
				<th><label for="borderline">Text – Hraniční potenciál</label></th>
				<td>
					<textarea id="borderline" name="borderline" rows="5" class="large-text"><?php echo esc_textarea( $texts['borderline'] ?? '' ); ?></textarea>
					<p class="description">Zobrazí se, když je budget hraniční.</p>
				</td>
			</tr>
			<tr>
				<th><label for="low_potential">Text – Nízký potenciál</label></th>
				<td>
					<textarea id="low_potential" name="low_potential" rows="5" class="large-text"><?php echo esc_textarea( $texts['low_potential'] ?? '' ); ?></textarea>
					<p class="description">Zobrazí se, když je budget pod hranicí nízkého potenciálu.</p>
				</td>
			</tr>
		</table>

		<h2>Verdikt „Vyplatí se?“</h2>
		<table class="form-table">
			<tr>
				<th><label for="verdict_label">Popisek karty</label></th>
				<td><input type="text" id="verdict_label" name="verdict_label" value="<?php echo esc_attr( $texts['verdict_label'] ?? '' ); ?>" class="regular-text"></td>
			</tr>
			<?php
			$verdicts = [
				'good'       => 'Vyplatí se',
				'borderline' => 'Hraniční',
				'low'        => 'Nízký potenciál',
			];
			foreach ( $verdicts as $key => $label ) :
				?>
				<tr>
					<th><?php echo esc_html( $label ); ?></th>
					<td>
						<input type="text" name="verdict_<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $texts[ 'verdict_' . $key ] ?? '' ); ?>" class="regular-text" placeholder="Hlavní text">
						<input type="text" name="verdict_<?php echo esc_attr( $key ); ?>_sub" value="<?php echo esc_attr( $texts[ 'verdict_' . $key . '_sub' ] ?? '' ); ?>" class="regular-text" placeholder="Doplňující řádek">
					</td>
				</tr>
			<?php endforeach; ?>
		</table>

		<h2>Výzva k rezervaci konzultace</h2>
		<table class="form-table">
			<tr>
				<th><label for="cta_title">Nadpis</label></th>
				<td><input type="text" id="cta_title" name="cta_title" value="<?php echo esc_attr( $texts['cta_title'] ?? '' ); ?>" class="large-text"></td>
			</tr>
			<tr>
				<th><label for="cta_subtitle">Podnadpis</label></th>
				<td><textarea id="cta_subtitle" name="cta_subtitle" rows="3" class="large-text"><?php echo esc_textarea( $texts['cta_subtitle'] ?? '' ); ?></textarea></td>
			</tr>
		</table>
		<p class="description">Text tlačítka, poznámka pod ním a odkaz na kalendář se nastavují v <a href="<?php echo esc_url( admin_url( 'admin.php?page=ecalc_form' ) ); ?>">Formulář a CTA</a>.</p>

		<?php submit_button( 'Uložit texty' ); ?>
	</form>
</div>
