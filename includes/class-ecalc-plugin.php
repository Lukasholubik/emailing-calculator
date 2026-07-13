<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ECAlc_Plugin {

	private ECAlc_Settings      $settings;
	private ECAlc_Calculator    $calculator;
	private ECAlc_Leads         $leads;
	private ECAlc_Email         $email;
	private ECAlc_SmartEmailing $smartemailing;
	private ECAlc_Shortcode     $shortcode;
	private ECAlc_REST          $rest;

	public function __construct() {
		$this->settings      = new ECAlc_Settings();
		$this->calculator    = new ECAlc_Calculator( $this->settings );
		$this->leads         = new ECAlc_Leads();
		$this->email         = new ECAlc_Email( $this->settings );
		$this->smartemailing = new ECAlc_SmartEmailing( $this->settings );
		$this->shortcode     = new ECAlc_Shortcode( $this->settings );
		$this->rest          = new ECAlc_REST(
			$this->settings,
			$this->calculator,
			$this->leads,
			$this->email,
			$this->smartemailing
		);
	}

	public function run(): void {
		$this->shortcode->register();
		$this->rest->register();

		$cron = new ECAlc_Cron( $this->settings, $this->leads, $this->email );
		$cron->register();

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );

		if ( is_admin() ) {
			$admin = new ECAlc_Admin( $this->settings, $this->leads, $this->smartemailing );
			$admin->register();
		}

		add_action( 'admin_init', function () {
			$db_ver = get_option( 'ecalc_db_version', '' );
			if ( version_compare( $db_ver, ECALC_VERSION, '<' ) ) {
				ECAlc_Activator::create_leads_table();
			}
		} );
	}

	public function enqueue_frontend_assets(): void {
		global $post;

		if ( ! is_a( $post, 'WP_Post' ) ) {
			return;
		}

		$has_shortcode = has_shortcode( $post->post_content, 'emailing_calculator' );

		// Elementor ukládá obsah do meta pole, ne do post_content
		if ( ! $has_shortcode ) {
			$elementor_data = get_post_meta( $post->ID, '_elementor_data', true );
			if ( $elementor_data && strpos( $elementor_data, 'emailing_calculator' ) !== false ) {
				$has_shortcode = true;
			}
		}

		if ( ! $has_shortcode ) {
			return;
		}

		wp_enqueue_style(
			'ecalc-frontend',
			ECALC_PLUGIN_URL . 'assets/css/frontend.css',
			[],
			ECALC_VERSION
		);

		wp_enqueue_script(
			'ecalc-frontend',
			ECALC_PLUGIN_URL . 'assets/js/frontend.js',
			[],
			ECALC_VERSION,
			true
		);

		$ts = $this->settings->get_turnstile();
		if ( ! empty( $ts['enabled'] ) && ! empty( $ts['site_key'] ) ) {
			wp_enqueue_script(
				'cf-turnstile',
				'https://challenges.cloudflare.com/turnstile/v0/api.js',
				[],
				null,
				true
			);
		}

		$a = $this->settings->get_appearance();
		$e = function( string $k ) use ( $a ): string {
			return ecalc_sanitize_css_value( (string) ( $a[ $k ] ?? '' ) );
		};

		$css_vars = ':root {' .
			// Základní
			'--ecalc-base-size:'       . $e('base_font_size')       . ';' .
			'--ecalc-base-color:'      . $e('base_text_color')       . ';' .
			'--ecalc-muted-color:'     . $e('muted_text_color')      . ';' .
			'--ecalc-subtle-color:'    . $e('subtle_text_color')     . ';' .
			'--ecalc-primary:'         . $e('primary_accent')        . ';' .
			// Nadpisy
			'--ecalc-h1-size:'    . $e('h1_size')    . ';' .
			'--ecalc-h1-color:'   . $e('h1_color')   . ';' .
			'--ecalc-h1-weight:'  . $e('h1_weight')  . ';' .
			'--ecalc-h2-size:'    . $e('h2_size')    . ';' .
			'--ecalc-h2-color:'   . $e('h2_color')   . ';' .
			'--ecalc-h2-weight:'  . $e('h2_weight')  . ';' .
			'--ecalc-h3-size:'    . $e('h3_size')    . ';' .
			'--ecalc-h3-color:'   . $e('h3_color')   . ';' .
			'--ecalc-h3-weight:'  . $e('h3_weight')  . ';' .
			// Primární tlačítko
			'--ecalc-btn-bg:'           . $e('btn_bg')           . ';' .
			'--ecalc-btn-bg-hover:'     . $e('btn_bg_hover')     . ';' .
			'--ecalc-btn-color:'        . $e('btn_color')        . ';' .
			'--ecalc-btn-color-hover:'  . $e('btn_color_hover')  . ';' .
			'--ecalc-btn-border:'       . $e('btn_border')       . ';' .
			'--ecalc-btn-border-hover:' . $e('btn_border_hover') . ';' .
			'--ecalc-btn-bw:'           . $e('btn_border_width') . ';' .
			'--ecalc-btn-size:'         . $e('btn_font_size')    . ';' .
			'--ecalc-btn-weight:'       . $e('btn_font_weight')  . ';' .
			'--ecalc-btn-pad-y:'        . $e('btn_pad_y')        . ';' .
			'--ecalc-btn-pad-x:'        . $e('btn_pad_x')        . ';' .
			// Sekundární tlačítko
			'--ecalc-btn2-bg:'           . $e('btn2_bg')           . ';' .
			'--ecalc-btn2-bg-hover:'     . $e('btn2_bg_hover')     . ';' .
			'--ecalc-btn2-color:'        . $e('btn2_color')        . ';' .
			'--ecalc-btn2-color-hover:'  . $e('btn2_color_hover')  . ';' .
			'--ecalc-btn2-border:'       . $e('btn2_border')       . ';' .
			'--ecalc-btn2-border-hover:' . $e('btn2_border_hover') . ';' .
			// Formulář
			'--ecalc-input-border:'       . $e('input_border')       . ';' .
			'--ecalc-input-border-focus:' . $e('input_border_focus') . ';' .
			'--ecalc-input-bg:'           . $e('input_bg')           . ';' .
			'--ecalc-label-color:'        . $e('label_color')        . ';' .
			// Layout
			'--ecalc-dark:'        . $e('dark_panel_bg')   . ';' .
			'--ecalc-dark-text:'   . $e('dark_panel_text') . ';' .
			'--ecalc-radius:'      . $e('border_radius')   . ';' .
			'--ecalc-card-bg:'     . $e('card_bg')         . ';' .
			'--ecalc-card-border:' . $e('card_border')     . ';' .
		'}';

		wp_add_inline_style( 'ecalc-frontend', $css_vars );

		$cfg = $this->settings->get_settings();
		wp_localize_script( 'ecalc-frontend', 'ecalcData', [
			'turnstile' => [
				'enabled'  => ! empty( $ts['enabled'] ) && ! empty( $ts['site_key'] ),
				'site_key' => ! empty( $ts['enabled'] ) ? esc_attr( $ts['site_key'] ) : '',
			],
			'restUrl'          => esc_url( rest_url( 'emailing-calculator/v1/calculate' ) ),
			'trackViewUrl'     => esc_url( rest_url( 'emailing-calculator/v1/track-view' ) ),
			'trackExitUrl'     => esc_url( rest_url( 'emailing-calculator/v1/track-exit' ) ),
			'checkEmailUrl'    => esc_url( rest_url( 'emailing-calculator/v1/check-email' ) ),
			'ctaClickUrl'      => esc_url( rest_url( 'emailing-calculator/v1/cta-click' ) ),
			'bookingStatusUrl' => esc_url( rest_url( 'emailing-calculator/v1/booking-status' ) ),
			'bookingUrl'       => esc_url( $cfg['booking_url'] ?? '' ),
			'bookingTexts'     => [
				'title'            => esc_html( $cfg['booking_modal_title']      ?? 'Vyberte termín konzultace' ),
				'confirmQuestion'  => esc_html( $cfg['booking_confirm_question'] ?? 'Provedli jste rezervaci termínu?' ),
				'confirmYes'       => esc_html( $cfg['booking_confirm_yes']      ?? 'Ano, zarezervoval jsem' ),
				'confirmNo'        => esc_html( $cfg['booking_confirm_no']       ?? 'Ne, zavřít' ),
				'yesMessage'       => esc_html( $cfg['booking_yes_message']      ?? 'Skvělé! Brzy se ozveme.' ),
				'noMessage'        => esc_html( $cfg['booking_no_message']       ?? 'Nevadí – obraťte se na nás kdykoliv.' ),
				'fallbackLink'     => 'Pokud se kalendář nenačítá, otevřete ho v novém okně',
				'openNewWindow'    => 'Otevřít v novém okně',
			],
			'nonce'        => wp_create_nonce( 'wp_rest' ),
			'ctaConsultationNote' => esc_html( $cfg['cta_consultation_note'] ?? '' ),
			'pnoOverLabel'        => esc_html( $cfg['pno_over_label'] ?? 'Nad vaším zadaným PNO' ),
			'strings' => [
				'required'             => 'Toto pole je povinné.',
				'invalid_email'        => 'Zadejte platný e-mail.',
				'invalid_url'          => 'Zadejte platnou URL adresu e-shopu.',
				'loading'              => 'Počítám výsledky...',
				'error_generic'        => 'Došlo k chybě. Zkuste to prosím znovu.',
				'consent_required'     => 'Pro pokračování je nutné souhlasit se zpracováním údajů.',
				'inquiry_title'        => esc_html( $cfg['inquiry_title']     ?? 'Děkujeme za zájem!' ),
				'inquiry_pkg_label'    => esc_html( $cfg['inquiry_pkg_label'] ?? 'Poptáváte balíček:' ),
				'inquiry_msg'          => esc_html( $cfg['inquiry_msg']       ?? 'Vaše poptávka byla odeslána. Ozveme se vám do 24 hodin a probereme detaily spolupráce.' ),
				'inquiry_close'        => esc_html( $cfg['inquiry_close']     ?? 'Zavřít' ),
				'inquiry_visit'        => esc_html( $cfg['inquiry_visit']     ?? 'Přejít na web' ),
				// Duplicitní email
				'duplicate_title'    => esc_html( $cfg['duplicate_title']    ?? 'Již vás máme v databázi' ),
				'duplicate_msg'      => esc_html( $cfg['duplicate_msg']      ?? 'Kontakt s tímto e-mailem již existuje v naší databázi (stav: {status}).' ),
				'duplicate_question' => esc_html( $cfg['duplicate_question'] ?? 'Chcete provést nový výpočet? Aktuální záznam bude aktualizován.' ),
				'duplicate_confirm'  => esc_html( $cfg['duplicate_confirm']  ?? 'Ano, provést nový výpočet' ),
				'duplicate_cancel'   => esc_html( $cfg['duplicate_cancel']   ?? 'Zrušit' ),
				'update_banner'      => esc_html( $cfg['update_banner']      ?? 'Váš záznam byl aktualizován na základě nového výpočtu.' ),
			],
		] );
	}
}
