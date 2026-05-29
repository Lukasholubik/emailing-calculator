<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var array $turnstile */
/** @var array $notice */
?>
<div class="wrap ecalc-admin">
	<h1>Zabezpečení – Cloudflare Turnstile</h1>

	<?php if ( $notice['type'] ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible">
			<p><?php echo esc_html( $notice['message'] ); ?></p>
		</div>
	<?php endif; ?>

	<div class="ecalc-admin-notice" style="background:#fff3cd;border-left:4px solid #ffc107;padding:12px 16px;margin:16px 0;border-radius:4px;">
		<strong>Jak nastavit Cloudflare Turnstile:</strong>
		<ol style="margin:8px 0 0 20px;">
			<li>Přihlaste se na <a href="https://dash.cloudflare.com/" target="_blank" rel="noopener">dash.cloudflare.com</a></li>
			<li>V levém menu klikněte na <strong>Turnstile</strong> a přidejte nový web (Add site)</li>
			<li>Zadejte doménu vašeho e-shopu a zvolte typ widgetu (doporučujeme <em>Managed</em>)</li>
			<li>Zkopírujte <strong>Klíč webu (Site Key)</strong> a <strong>Tajný klíč (Secret Key)</strong> níže</li>
		</ol>
	</div>

	<div class="ecalc-connection-test" style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
		<strong style="min-width:160px;">Test klíče</strong>
		<button type="button" id="ecalc-test-ts" class="button button-secondary">
			Ověřit Turnstile tajný klíč
		</button>
		<span id="ecalc-test-ts-result" style="font-size:13px;"></span>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'ecalc_save_security' ); ?>
		<input type="hidden" name="action" value="ecalc_save_security">

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">Aktivovat ochranu</th>
				<td>
					<label>
						<input type="checkbox" name="turnstile_enabled" value="1"
							<?php checked( 1, (int) ( $turnstile['enabled'] ?? 0 ) ); ?>>
						Zapnout Cloudflare Turnstile ochranu proti botům
					</label>
					<p class="description">Pokud je aktivní, uživatel musí projít Turnstile výzvou před odesláním formuláře.</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="turnstile_site_key">Klíč webu (Site Key)</label></th>
				<td>
					<input type="text" id="turnstile_site_key" name="turnstile_site_key"
						value="<?php echo esc_attr( $turnstile['site_key'] ?? '' ); ?>"
						class="regular-text" placeholder="0x4AAAAAAA...">
					<p class="description">Veřejný klíč – vkládá se do HTML stránky.</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="turnstile_secret_key">Tajný klíč (Secret Key)</label></th>
				<td>
					<input type="password" id="turnstile_secret_key" name="turnstile_secret_key"
						value="<?php echo esc_attr( $turnstile['secret_key'] ?? '' ); ?>"
						class="regular-text" placeholder="0x4AAAAAAA...">
					<p class="description">Soukromý klíč – používá se pouze na serveru pro ověření. Nikdy ho nesdílejte.</p>
				</td>
			</tr>
		</table>

		<?php submit_button( 'Uložit nastavení' ); ?>
	</form>
</div>
