<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ECAlc_Shortcode {

	private ECAlc_Settings $settings;

	public function __construct( ECAlc_Settings $settings ) {
		$this->settings = $settings;
	}

	public function register(): void {
		add_shortcode( 'emailing_calculator', [ $this, 'render' ] );
	}

	public function render( $atts ): string {
		$segments       = array_filter( $this->settings->get_segments(), fn( $s ) => (int) $s['active'] );
		$database_ranges = array_filter( $this->settings->get_database_ranges(), fn( $r ) => (int) $r['active'] );
		$revenue_ranges  = array_filter( $this->settings->get_revenue_ranges(), fn( $r ) => (int) $r['active'] );
		$appearance      = $this->settings->get_appearance();
		$cfg             = $this->settings->get_settings();

		usort( $segments, fn( $a, $b ) => (int) $a['order'] <=> (int) $b['order'] );
		usort( $database_ranges, fn( $a, $b ) => (int) $a['order'] <=> (int) $b['order'] );
		usort( $revenue_ranges, fn( $a, $b ) => (int) $a['order'] <=> (int) $b['order'] );

		$rest_url   = rest_url( 'emailing-calculator/v1/calculate' );
		$nonce      = wp_create_nonce( 'wp_rest' );
		$marketing_required = (int) $cfg['marketing_consent_required'];
		$info_panel = $this->settings->get_info_panel();

		$ts                = $this->settings->get_turnstile();
		$turnstile_enabled  = ! empty( $ts['enabled'] ) && ! empty( $ts['site_key'] );
		$turnstile_site_key = $turnstile_enabled ? $ts['site_key'] : '';

		ob_start();
		include ECALC_PLUGIN_DIR . 'templates/frontend-form.php';
		return ob_get_clean();
	}
}
