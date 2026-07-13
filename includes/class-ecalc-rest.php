<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ECAlc_REST {

	private ECAlc_Settings     $settings;
	private ECAlc_Calculator   $calculator;
	private ECAlc_Leads        $leads;
	private ECAlc_Email        $email;
	private ECAlc_SmartEmailing $smartemailing;

	public function __construct(
		ECAlc_Settings $settings,
		ECAlc_Calculator $calculator,
		ECAlc_Leads $leads,
		ECAlc_Email $email,
		ECAlc_SmartEmailing $smartemailing
	) {
		$this->settings      = $settings;
		$this->calculator    = $calculator;
		$this->leads         = $leads;
		$this->email         = $email;
		$this->smartemailing = $smartemailing;
	}

	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes(): void {
		register_rest_route( 'emailing-calculator/v1', '/calculate', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'handle_calculate' ],
			'permission_callback' => '__return_true',
		] );

		register_rest_route( 'emailing-calculator/v1', '/check-email', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'handle_check_email' ],
			'permission_callback' => '__return_true',
		] );

		register_rest_route( 'emailing-calculator/v1', '/booking-status', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'handle_booking_status' ],
			'permission_callback' => '__return_true',
		] );

		register_rest_route( 'emailing-calculator/v1', '/cta-click', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'handle_cta_click' ],
			'permission_callback' => '__return_true',
		] );

		register_rest_route( 'emailing-calculator/v1', '/save-phone', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'handle_save_phone' ],
			'permission_callback' => '__return_true',
		] );

		register_rest_route( 'emailing-calculator/v1', '/resend/(?P<id>\d+)', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'handle_resend' ],
			'permission_callback' => fn() => current_user_can( 'manage_options' ),
		] );

		register_rest_route( 'emailing-calculator/v1', '/track-view', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'handle_track_view' ],
			'permission_callback' => '__return_true',
		] );

		register_rest_route( 'emailing-calculator/v1', '/track-exit', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'handle_track_exit' ],
			'permission_callback' => '__return_true',
		] );
	}

	public function handle_calculate( WP_REST_Request $request ): WP_REST_Response {
		if ( ! $this->verify_nonce( $request ) ) {
			return $this->error( 'invalid_nonce', 'Neplatný bezpečnostní token.', 403 );
		}

		if ( ! empty( $request->get_param( '_hp_field' ) ) ) {
			return $this->error( 'spam', 'Formulář nebyl odeslán správně.', 400 );
		}

		$params = $request->get_json_params();
		if ( empty( $params ) ) {
			$params = $request->get_body_params();
		}

		// Turnstile – přeskočit při potvrzeném přepočtu (token byl již spotřebován prvním voláním)
		$confirmed_early = ! empty( $params['confirmed'] );
		$ts = $this->settings->get_turnstile();
		if ( ! empty( $ts['enabled'] ) && ! empty( $ts['secret_key'] ) && ! $confirmed_early ) {
			$ts_token = sanitize_text_field( $params['turnstile_token'] ?? '' );
			if ( ! $this->verify_turnstile( $ts_token, $ts['secret_key'] ) ) {
				return $this->error( 'turnstile_failed', 'Ověření ochrany proti botům selhalo. Zkuste to prosím znovu.', 400 );
			}
		}

		$validation = $this->validate( $params );
		if ( is_wp_error( $validation ) ) {
			return new WP_REST_Response(
				[ 'success' => false, 'message' => $validation->get_error_message(), 'code' => $validation->get_error_code() ],
				422
			);
		}

		$cfg         = $this->settings->get_settings();
		$email_clean = sanitize_email( $params['email'] );

		// Email je primární klíč – vždy hledáme existující záznam, nikdy nevytváříme duplicitu
		$existing_lead = $this->leads->get_by_email( $email_clean );
		$confirmed     = ! empty( $params['confirmed'] );

		// Pokud email existuje a uživatel ještě nepotvrdil → vrátíme 409 pro zobrazení dialogu
		if ( $existing_lead && ! $confirmed ) {
			$active_statuses  = [ ECAlc_Lead_Status::POPTANO, ECAlc_Lead_Status::SCHUZKA, ECAlc_Lead_Status::ZOBCHODOVANO ];
			$existing_status  = $existing_lead['lead_status'] ?? ECAlc_Lead_Status::CEKANI;
			$is_active        = in_array( $existing_status, $active_statuses, true );

			return new WP_REST_Response( [
				'success'      => false,
				'code'         => 'duplicate_email',
				'status_label' => ECAlc_Lead_Status::label( $existing_status ),
				'is_active'    => $is_active,
				'cta_clicked'  => (bool) ( $existing_lead['cta_clicked'] ?? 0 ),
				'cta_type'     => $existing_lead['cta_type'] ?? '',
			], 409 );
		}

		// Rate limiting – nové emaily: měsíční limit; existující: max 10 přepočtů za hodinu
		if ( ! $existing_lead ) {
			$limit = (int) ( $cfg['monthly_limit'] ?? 5 );
			if ( $limit > 0 && $this->leads->count_by_email_this_month( $email_clean ) >= $limit ) {
				return $this->error(
					'rate_limit',
					sprintf( 'Pro tento e-mail byl překročen měsíční limit %d výpočtů. Kontaktujte nás přímo.', $limit ),
					429
				);
			}
		} else {
			$recalc_key   = 'ecalc_recalc_' . md5( $email_clean );
			$recalc_count = (int) get_transient( $recalc_key );
			if ( $recalc_count >= 10 ) {
				return $this->error( 'rate_limit', 'Příliš mnoho přepočtů. Zkuste to prosím za hodinu.', 429 );
			}
			set_transient( $recalc_key, $recalc_count + 1, 3600 );
		}

		$monthly_revenue = $this->resolve_monthly_revenue( $params );
		if ( $monthly_revenue === null ) {
			return $this->error( 'invalid_revenue', 'Zadejte nebo vyberte měsíční obrat.', 422 );
		}

		$input = [
			'monthly_revenue'       => $monthly_revenue,
			'consumable_percentage' => (float) $params['consumable_percentage'],
			'database_range'        => sanitize_text_field( $params['database_range'] ),
			'segment'               => sanitize_text_field( $params['segment'] ),
			'expected_pno'          => (float) $params['expected_pno'],
		];

		$calc = $this->calculator->calculate( $input );

		$result_texts = $this->settings->get_result_texts();

		$result_text = $this->get_result_text( $calc['result_type'], $calc, $result_texts );

		$lead_data = array_merge( [
			'name'              => sanitize_text_field( $params['name'] ),
			'email'             => sanitize_email( $params['email'] ),
			'phone'             => ecalc_sanitize_phone( (string) $params['phone'] ),
			'shop_url'          => esc_url_raw( $params['shop_url'] ),
			'segment'           => $input['segment'],
			'consumable_percentage' => (int) $input['consumable_percentage'],
			'database_range'    => $input['database_range'],
			'monthly_revenue'   => $monthly_revenue,
			'expected_pno'      => (int) $input['expected_pno'],
			'consent_data'      => ! empty( $params['consent_data'] ) ? 1 : 0,
			'consent_marketing' => ! empty( $params['consent_marketing'] ) ? 1 : 0,
			'ip_address'        => ecalc_get_client_ip(),
			'user_agent'        => ecalc_get_user_agent(),
			'recommended_package'          => $calc['recommended_package_name'],
			'recommended_package_price'    => $calc['recommended_package_price'],
			'recommended_package_real_pno' => $calc['recommended_package_real_pno'],
			'utm_source'        => sanitize_text_field( $params['utm_source']   ?? '' ),
			'utm_medium'        => sanitize_text_field( $params['utm_medium']   ?? '' ),
			'utm_campaign'      => sanitize_text_field( $params['utm_campaign'] ?? '' ),
			'referrer'          => sanitize_text_field( $params['referrer']     ?? '' ),
			'time_to_submit'    => isset( $params['time_to_submit'] ) ? max( 0, min( 7200, (int) $params['time_to_submit'] ) ) : null,
		], $calc );

		$se_result = $this->smartemailing->send_lead( $lead_data, (bool) $lead_data['consent_marketing'] );
		$lead_data['smartemailing_status'] = $se_result['status'];

		if ( $existing_lead ) {
			$lead_id = (int) $existing_lead['id'];

			$old_name = trim( $existing_lead['name'] );
			$new_name = trim( $lead_data['name'] );
			if ( $old_name !== $new_name ) {
				$this->leads->log_change( $lead_id, 'name_changed',
					sprintf( '"%s" → "%s"', $old_name, $new_name ) );
			}

			$old_url = rtrim( trim( $existing_lead['shop_url'] ), '/' );
			$new_url = rtrim( trim( $lead_data['shop_url'] ), '/' );
			if ( $old_url !== $new_url ) {
				$this->leads->log_change( $lead_id, 'url_changed',
					sprintf( '"%s" → "%s"', $old_url, $new_url ) );
			}

			$old_phone = trim( (string) ( $existing_lead['phone'] ?? '' ) );
			$new_phone = trim( $lead_data['phone'] );
			if ( $old_phone !== $new_phone ) {
				$this->leads->log_change( $lead_id, 'phone_changed',
					sprintf( '"%s" → "%s"', $old_phone, $new_phone ) );
			}

			$this->leads->update_lead( $lead_id, $lead_data );

			$note = sprintf(
				'Nový výpočet z frontendu. Segment: %s, Obrat: %s Kč, Potenciál: %s %%, Výsledek: %s.',
				$lead_data['segment'],
				number_format( $monthly_revenue, 0, ',', ' ' ),
				number_format( (float) $calc['final_potential'], 2, ',', ' ' ),
				$lead_data['result_type']
			);
			$this->leads->log_change( $lead_id, 'recalculation', $note );
		} else {
			$lead_id = $this->leads->insert( $lead_data );
		}

		// Vždy uložit SE status – i bez response – aby bylo v adminu vidět důvod
		$this->leads->update_smartemailing_status( $lead_id, $se_result['status'], $se_result['response'] ?? '' );

		$email_data = array_merge( $lead_data, [
			'final_potential'    => $calc['final_potential'],
			'emailing_revenue_low'  => $calc['emailing_revenue_low'],
			'emailing_revenue_mid'  => $calc['emailing_revenue_mid'],
			'emailing_revenue_high' => $calc['emailing_revenue_high'],
			'available_budget'      => $calc['available_budget'],
		] );

		$this->email->send_admin_notification( $email_data, $lead_id, (bool) $existing_lead );

		if ( ! $existing_lead ) {
			$this->email->send_client_email( $email_data );
			do_action( 'ecalc_lead_saved', $lead_id );
		}

		$lead_token = wp_hash( $lead_id . ':' . $email_clean );

		return new WP_REST_Response( [
			'success'    => true,
			'updated'    => (bool) $existing_lead,
			'lead_id'    => $lead_id,
			'lead_token' => $lead_token,
			'lead'       => [
				'name'                  => esc_html( $lead_data['name'] ),
				'email'                 => esc_html( $lead_data['email'] ),
				'phone'                 => esc_html( $lead_data['phone'] ),
				'shop_url'              => esc_html( $lead_data['shop_url'] ),
				'segment'               => esc_html( $input['segment'] ),
				'consumable_percentage' => (int) $input['consumable_percentage'],
				'database_range'        => esc_html( $input['database_range'] ),
				'monthly_revenue'       => $monthly_revenue,
				'expected_pno'          => (int) $input['expected_pno'],
			],
			'calculation' => [
				'consumable_score'    => $calc['consumable_score'],
				'database_score'      => $calc['database_score'],
				'segment_score'       => $calc['segment_score'],
				'total_score'         => $calc['total_score'],
				'final_potential'     => $calc['final_potential'],
				'emailing_revenue_low'  => $calc['emailing_revenue_low'],
				'emailing_revenue_mid'  => $calc['emailing_revenue_mid'],
				'emailing_revenue_high' => $calc['emailing_revenue_high'],
				'available_budget'    => $calc['available_budget'],
			],
			'result' => [
				'type'                => $calc['result_type'],
				'title'               => esc_html( $result_texts['result_title'] ?? 'Výsledek vaší kalkulačky' ),
				'text'                => wp_kses_post( $result_text ),
				'recommended_package' => $calc['recommended_package'],
				'packages'            => $calc['packages'],
				'cta_text'            => esc_html( $cfg['cta_text'] ),
				'cta_url'             => esc_url( $cfg['cta_url'] ),
				'arguments'           => $this->esc_arguments( $calc['arguments'] ?? [] ),
			],
		], 200 );
	}

	public function handle_check_email( WP_REST_Request $request ): WP_REST_Response {
		// Rate limiting per IP – max 15 požadavků za 60 sekund
		$ip_key = 'ecalc_ce_' . md5( $_SERVER['REMOTE_ADDR'] ?? '' );
		$hits   = (int) get_transient( $ip_key );
		if ( $hits >= 15 ) {
			return new WP_REST_Response( [ 'found' => false ], 429 );
		}
		set_transient( $ip_key, $hits + 1, 60 );

		$email = sanitize_email( $request->get_param( 'email' ) ?? '' );
		if ( ! is_email( $email ) ) {
			return new WP_REST_Response( [ 'found' => false ], 200 );
		}

		$lead = $this->leads->get_by_email( $email );
		if ( ! $lead ) {
			return new WP_REST_Response( [ 'found' => false ], 200 );
		}

		$active_statuses = [ ECAlc_Lead_Status::POPTANO, ECAlc_Lead_Status::SCHUZKA, ECAlc_Lead_Status::ZOBCHODOVANO ];
		$lead_status     = $lead['lead_status'] ?? ECAlc_Lead_Status::CEKANI;
		$is_active       = in_array( $lead_status, $active_statuses, true );

		// Vracíme jen minimum – žádný interní status ani label, aby nebylo možné
		// enumerovat databázi leadů nebo zjišťovat jejich stav bez autentizace.
		return new WP_REST_Response( [
			'found'     => true,
			'is_active' => $is_active,
		], 200 );
	}

	public function handle_booking_status( WP_REST_Request $request ): WP_REST_Response {
		if ( ! $this->verify_nonce( $request ) ) {
			return $this->error( 'invalid_nonce', 'Neplatný token.', 403 );
		}

		$params  = $request->get_json_params() ?: $request->get_body_params();
		$lead_id = (int) ( $params['lead_id'] ?? 0 );
		$status  = sanitize_text_field( $params['status'] ?? '' );
		$token   = sanitize_text_field( $params['lead_token'] ?? '' );

		if ( ! $lead_id || ! in_array( $status, [ 'opened', 'completed', 'declined' ], true ) ) {
			return $this->error( 'invalid_params', 'Neplatné parametry.', 422 );
		}

		$lead = $this->leads->get( $lead_id );
		if ( ! $lead ) {
			return $this->error( 'not_found', 'Lead nenalezen.', 404 );
		}

		$expected_token = wp_hash( $lead_id . ':' . $lead['email'] );
		if ( ! hash_equals( $expected_token, $token ) ) {
			return $this->error( 'forbidden', 'Přístup odepřen.', 403 );
		}

		$this->leads->update_booking_status( $lead_id, $status );

		// Booking completed → přechod stavu + zrušit follow-up
		if ( $status === 'completed' ) {
			$this->leads->record_cta_click( $lead_id, 'consultation', '' );
			$this->leads->update_lead_status( $lead_id, ECAlc_Lead_Status::SCHUZKA );
		}

		return new WP_REST_Response( [ 'success' => true, 'status' => $status ], 200 );
	}

	public function handle_cta_click( WP_REST_Request $request ): WP_REST_Response {
		if ( ! $this->verify_nonce( $request ) ) {
			return $this->error( 'invalid_nonce', 'Neplatný token.', 403 );
		}

		$params       = $request->get_json_params() ?: $request->get_body_params();
		$lead_id      = (int) ( $params['lead_id'] ?? 0 );
		$type         = sanitize_text_field( $params['type'] ?? '' );
		$package_name = sanitize_text_field( $params['package_name'] ?? '' );
		$token        = sanitize_text_field( $params['lead_token'] ?? '' );
		$phone        = sanitize_text_field( $params['phone'] ?? '' );

		if ( ! $lead_id || ! in_array( $type, [ 'consultation', 'package' ], true ) ) {
			return $this->error( 'invalid_params', 'Neplatné parametry.', 422 );
		}

		$lead = $this->leads->get( $lead_id );
		if ( ! $lead ) {
			return $this->error( 'not_found', 'Lead nenalezen.', 404 );
		}

		$expected_token = wp_hash( $lead_id . ':' . $lead['email'] );
		if ( ! hash_equals( $expected_token, $token ) ) {
			return $this->error( 'forbidden', 'Přístup odepřen.', 403 );
		}

		// Uložit telefon a syncovat do SmartEmailingu (pokud přišel s CTA klikem)
		if ( $phone !== '' ) {
			$this->leads->save_phone( $lead_id, $phone );
			$lead['phone'] = $phone;
			$this->smartemailing->update_phone( $lead );
		}

		$this->leads->record_cta_click( $lead_id, $type, $package_name );

		// Automatický přechod stavu
		if ( $type === 'package' ) {
			$this->leads->update_lead_status( $lead_id, ECAlc_Lead_Status::POPTANO );
		} elseif ( $type === 'consultation' ) {
			$current = $lead['lead_status'] ?? '';
			if ( $current !== ECAlc_Lead_Status::POPTANO && $current !== ECAlc_Lead_Status::ZOBCHODOVANO ) {
				$this->leads->update_lead_status( $lead_id, ECAlc_Lead_Status::SCHUZKA );
			}
		}

		$cfg = $this->settings->get_notifications();
		$lead['cta_type']         = $type;
		$lead['cta_package_name'] = $package_name;

		if ( $type === 'package' && ! empty( $cfg['trigger_inquiry_enabled'] ) ) {
			$this->email->send_inquiry_admin( $lead, $cfg );
			$this->email->send_inquiry_client( $lead, $cfg );
		}

		if ( $type === 'consultation' && ! empty( $cfg['trigger_consultation_enabled'] ) ) {
			$this->email->send_consultation_admin( $lead, $cfg );
			$this->email->send_consultation_client( $lead, $cfg );
		}

		do_action( 'ecalc_cta_clicked', $lead_id, $type, $package_name );

		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	public function handle_save_phone( WP_REST_Request $request ): WP_REST_Response {
		if ( ! $this->verify_nonce( $request ) ) {
			return $this->error( 'invalid_nonce', 'Neplatný token.', 403 );
		}

		$params  = $request->get_json_params() ?: $request->get_body_params();
		$lead_id = (int) ( $params['lead_id'] ?? 0 );
		$phone   = sanitize_text_field( $params['phone'] ?? '' );
		$token   = sanitize_text_field( $params['lead_token'] ?? '' );

		if ( ! $lead_id ) {
			return $this->error( 'invalid_params', 'Neplatné parametry.', 422 );
		}

		$lead = $this->leads->get( $lead_id );
		if ( ! $lead ) {
			return $this->error( 'not_found', 'Lead nenalezen.', 404 );
		}

		$expected_token = wp_hash( $lead_id . ':' . $lead['email'] );
		if ( ! hash_equals( $expected_token, $token ) ) {
			return $this->error( 'forbidden', 'Přístup odepřen.', 403 );
		}

		if ( $phone !== '' ) {
			$this->leads->save_phone( $lead_id, $phone );
		}

		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	public function handle_track_exit( WP_REST_Request $request ): WP_REST_Response {
		$ip_key = 'ecalc_te_' . md5( $_SERVER['REMOTE_ADDR'] ?? '' );
		$hits   = (int) get_transient( $ip_key );
		if ( $hits >= 30 ) {
			return new WP_REST_Response( [ 'success' => true ], 200 );
		}
		set_transient( $ip_key, $hits + 1, 3600 );

		$valid_steps = [ 'initial', 'name', 'email', 'phone', 'shop_url', 'segment', 'database', 'revenue', 'consumable', 'pno', 'consent' ];
		$params       = $request->get_json_params() ?: [];
		$last_step    = sanitize_key( $params['last_step']        ?? '' );
		$time_spent   = max( 0, min( 7200, (int) ( $params['time_spent_s']    ?? 0 ) ) );
		$scroll_pct   = max( 0, min( 100,  (int) ( $params['scroll_depth_pct'] ?? 0 ) ) );
		$converted    = ! empty( $params['converted'] );
		$today        = current_time( 'Y-m-d' );

		if ( $time_spent > 0 ) {
			$times = get_option( 'ecalc_session_times_daily', [] );
			if ( ! is_array( $times ) ) $times = [];
			if ( ! isset( $times[ $today ] ) ) $times[ $today ] = [ 'count' => 0, 'total_s' => 0, 'scroll_count' => 0, 'total_scroll' => 0 ];
			$times[ $today ]['count']++;
			$times[ $today ]['total_s'] += $time_spent;
			if ( $scroll_pct > 0 ) {
				$times[ $today ]['scroll_count'] = ( $times[ $today ]['scroll_count'] ?? 0 ) + 1;
				$times[ $today ]['total_scroll'] = ( $times[ $today ]['total_scroll'] ?? 0 ) + $scroll_pct;
			}
			if ( count( $times ) > 365 ) { ksort( $times ); $times = array_slice( $times, -365, 365, true ); }
			update_option( 'ecalc_session_times_daily', $times, false );
		}

		if ( ! $converted && $last_step && in_array( $last_step, $valid_steps, true ) ) {
			$steps = get_option( 'ecalc_abandonment_steps', [] );
			if ( ! is_array( $steps ) ) $steps = [];
			if ( ! isset( $steps[ $today ] ) ) $steps[ $today ] = [];
			$steps[ $today ][ $last_step ] = ( $steps[ $today ][ $last_step ] ?? 0 ) + 1;
			if ( count( $steps ) > 365 ) { ksort( $steps ); $steps = array_slice( $steps, -365, 365, true ); }
			update_option( 'ecalc_abandonment_steps', $steps, false );
		}

		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	public function handle_track_view( WP_REST_Request $request ): WP_REST_Response {
		$ip_key = 'ecalc_tv_' . md5( $_SERVER['REMOTE_ADDR'] ?? '' );
		$hits   = (int) get_transient( $ip_key );
		if ( $hits >= 30 ) {
			return new WP_REST_Response( [ 'success' => true ], 200 );
		}
		set_transient( $ip_key, $hits + 1, 3600 );

		$today = current_time( 'Y-m-d' );
		$views = get_option( 'ecalc_views_daily', [] );
		if ( ! is_array( $views ) ) {
			$views = [];
		}
		$views[ $today ] = ( $views[ $today ] ?? 0 ) + 1;
		if ( count( $views ) > 365 ) {
			ksort( $views );
			$views = array_slice( $views, -365, 365, true );
		}
		update_option( 'ecalc_views_daily', $views, false );
		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	public function handle_resend( WP_REST_Request $request ): WP_REST_Response {
		$id   = (int) $request->get_param( 'id' );
		$lead = $this->leads->get( $id );

		if ( ! $lead ) {
			return $this->error( 'not_found', 'Lead nebyl nalezen.', 404 );
		}

		$result = $this->smartemailing->send_lead( $lead, (bool) $lead['consent_marketing'] );
		$this->leads->update_smartemailing_status( $id, $result['status'], $result['response'] );

		return new WP_REST_Response( [ 'success' => true, 'status' => $result['status'] ], 200 );
	}

	private function verify_nonce( WP_REST_Request $request ): bool {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce ) {
			$params = $request->get_json_params() ?: $request->get_body_params();
			$nonce  = $params['nonce'] ?? '';
		}
		if ( ! $nonce ) {
			$nonce = sanitize_text_field( $request->get_param( 'nonce' ) ?? '' );
		}
		return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
	}

	private function validate( array $params ): true|WP_Error {
		if ( empty( $params['name'] ) ) {
			return new WP_Error( 'missing_name', 'Vyplňte prosím jméno.' );
		}
		if ( mb_strlen( (string) $params['name'], 'UTF-8' ) > 255 ) {
			return new WP_Error( 'invalid_name', 'Jméno je příliš dlouhé.' );
		}
		if ( empty( $params['email'] ) || ! is_email( $params['email'] ) ) {
			return new WP_Error( 'invalid_email', 'Zadejte platný e-mail.' );
		}
		if ( empty( $params['phone'] ) || ! ecalc_is_valid_phone( ecalc_sanitize_phone( (string) $params['phone'] ) ) ) {
			return new WP_Error( 'invalid_phone', 'Zadejte platné telefonní číslo (7–15 číslic).' );
		}
		if ( empty( $params['shop_url'] ) || ! $this->is_valid_shop_url( (string) $params['shop_url'] ) ) {
			return new WP_Error( 'invalid_url', 'Zadejte platnou adresu e-shopu (např. mujshop.cz).' );
		}
		if ( mb_strlen( (string) $params['shop_url'], 'UTF-8' ) > 500 ) {
			return new WP_Error( 'invalid_url', 'URL e-shopu je příliš dlouhá.' );
		}
		if ( empty( $params['segment'] ) ) {
			return new WP_Error( 'missing_segment', 'Vyberte segment e-shopu.' );
		}
		if ( ! isset( $params['consumable_percentage'] ) || $params['consumable_percentage'] === '' ) {
			return new WP_Error( 'missing_consumable', 'Zadejte podíl spotřebního zboží.' );
		}
		$consumable = (float) $params['consumable_percentage'];
		if ( $consumable < 0 || $consumable > 100 ) {
			return new WP_Error( 'invalid_consumable', 'Podíl spotřebního zboží musí být 0–100 %.' );
		}
		if ( empty( $params['database_range'] ) ) {
			return new WP_Error( 'missing_database', 'Vyberte velikost databáze kontaktů.' );
		}
		if ( ! isset( $params['expected_pno'] ) || $params['expected_pno'] === '' ) {
			return new WP_Error( 'missing_pno', 'Zadejte očekávané PNO.' );
		}
		$pno = (float) $params['expected_pno'];
		if ( $pno < 1 || $pno > 100 ) {
			return new WP_Error( 'invalid_pno', 'Očekávané PNO musí být v rozsahu 1–100 %.' );
		}
		if ( empty( $params['consent_data'] ) ) {
			return new WP_Error( 'missing_consent', 'Pro pokračování je nutné souhlasit se zpracováním údajů.' );
		}
		return true;
	}

	private function resolve_monthly_revenue( array $params ): ?float {
		$type = $params['revenue_type'] ?? 'range';

		if ( $type === 'exact' ) {
			$val = isset( $params['revenue_exact'] ) ? (float) $params['revenue_exact'] : 0;
			if ( $val <= 0 ) return null;
			if ( $val > 999_999_999 ) return null; // horní limit 999 mil. Kč
			return $val;
		}

		$range_name = $params['revenue_range'] ?? '';
		if ( empty( $range_name ) ) {
			return null;
		}

		foreach ( $this->settings->get_revenue_ranges() as $range ) {
			if ( (int) $range['active'] && $range['name'] === $range_name ) {
				return (float) $range['calc_value'];
			}
		}
		return null;
	}

	private function get_result_text( string $result_type, array $calc, array $texts ): string {
		if ( $result_type === 'low_potential' ) {
			return $texts['low_potential'] ?? '';
		}
		if ( $result_type === 'borderline' ) {
			return $texts['borderline'] ?? '';
		}

		$recommended = $calc['recommended_package'];
		if ( $recommended && ! empty( $recommended['result_text'] ) ) {
			return $recommended['result_text'];
		}
		return '';
	}

	private function esc_arguments( array $arguments ): array {
		if ( empty( $arguments ) ) {
			return [];
		}
		return [
			'title'    => esc_html( $arguments['title'] ?? '' ),
			'subtitle' => esc_html( $arguments['subtitle'] ?? '' ),
			'items'    => array_map( 'esc_html', $arguments['items'] ?? [] ),
			'summary'  => esc_html( $arguments['summary'] ?? '' ),
		];
	}

	private function is_valid_shop_url( string $url ): bool {
		$url = trim( $url );
		// Přidáme protokol pokud chybí, aby filter_var fungoval
		if ( ! preg_match( '#^https?://#i', $url ) ) {
			$url = 'https://' . $url;
		}
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}
		// Musí mít TLD: aspoň tečka + 2–6 písmen na konci hostu
		$host = parse_url( $url, PHP_URL_HOST );
		return (bool) preg_match( '/\.[a-zA-Z]{2,}$/', (string) $host );
	}

	private function verify_turnstile( string $token, string $secret_key ): bool {
		if ( $token === '' ) {
			return false;
		}

		$response = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', [
			'timeout' => 10,
			'body'    => [
				'secret'   => $secret_key,
				'response' => $token,
				'remoteip' => sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ),
			],
		] );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		return ! empty( $body['success'] );
	}

	private function error( string $code, string $message, int $status ): WP_REST_Response {
		return new WP_REST_Response( [ 'success' => false, 'code' => $code, 'message' => $message ], $status );
	}

}
