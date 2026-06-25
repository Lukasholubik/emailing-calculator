<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ECAlc_Admin {

	private ECAlc_Settings      $settings;
	private ECAlc_Leads         $leads;
	private ECAlc_SmartEmailing $smartemailing;

	public function __construct( ECAlc_Settings $settings, ECAlc_Leads $leads, ECAlc_SmartEmailing $smartemailing ) {
		$this->settings      = $settings;
		$this->leads         = $leads;
		$this->smartemailing = $smartemailing;
	}

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_menu', [ $this, 'add_group_separator' ], 999 );
		add_action( 'admin_head', [ $this, 'output_group_separator_css' ] );
		add_action( 'admin_head', [ $this, 'output_submenu_group_css' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_post_ecalc_save_settings', [ $this, 'save_settings' ] );
		add_action( 'admin_post_ecalc_save_form',     [ $this, 'save_form_settings' ] );
		add_action( 'admin_post_ecalc_save_segments', [ $this, 'save_segments' ] );
		add_action( 'admin_post_ecalc_merge_segments', [ $this, 'merge_segments' ] );
		add_action( 'admin_post_ecalc_save_database_ranges', [ $this, 'save_database_ranges' ] );
		add_action( 'admin_post_ecalc_save_revenue_ranges', [ $this, 'save_revenue_ranges' ] );
		add_action( 'admin_post_ecalc_save_packages', [ $this, 'save_packages' ] );
		add_action( 'admin_post_ecalc_save_result_texts', [ $this, 'save_result_texts' ] );
		add_action( 'admin_post_ecalc_save_smartemailing', [ $this, 'save_smartemailing' ] );
		add_action( 'admin_post_ecalc_save_appearance', [ $this, 'save_appearance' ] );
		add_action( 'admin_post_ecalc_save_notifications', [ $this, 'save_notifications' ] );
		add_action( 'admin_post_ecalc_save_info_panel', [ $this, 'save_info_panel' ] );
		add_action( 'admin_post_ecalc_save_security', [ $this, 'save_security' ] );
		add_action( 'admin_post_ecalc_delete_lead', [ $this, 'delete_lead' ] );
		add_action( 'admin_post_ecalc_export_csv', [ $this, 'export_csv' ] );
		add_action( 'admin_post_ecalc_bulk_action', [ $this, 'bulk_action' ] );
		add_action( 'wp_ajax_ecalc_resend_smartemailing',      [ $this, 'ajax_resend_smartemailing' ] );
		add_action( 'wp_ajax_ecalc_change_lead_status',       [ $this, 'ajax_change_lead_status' ] );
		add_action( 'wp_ajax_ecalc_test_smartemailing',       [ $this, 'ajax_test_smartemailing' ] );
		add_action( 'wp_ajax_ecalc_test_turnstile',           [ $this, 'ajax_test_turnstile' ] );
		add_action( 'wp_ajax_ecalc_get_analytics',            [ $this, 'ajax_get_analytics' ] );
		add_action( 'wp_ajax_ecalc_bulk_export_smartemailing',[ $this, 'ajax_bulk_export_smartemailing' ] );
		add_action( 'wp_ajax_ecalc_add_manual_lead',          [ $this, 'ajax_add_manual_lead' ] );
	}

	public function add_menu(): void {
		add_menu_page(
			'Emailing kalkulačka',
			'Emailing kalk.',
			'manage_options',
			'ecalc_overview',
			[ $this, 'page_overview' ],
			'dashicons-chart-line',
			30
		);

		$pages = [
			[ 'ecalc_overview',        'Přehledy',          [ $this, 'page_overview' ] ],
			[ 'ecalc_leads',           'Leady',             [ $this, 'page_leads' ] ],
			[ 'ecalc_add_lead',        '+ Přidat lead',     [ $this, 'page_add_lead' ] ],
			// Skupina: Nastavení výpočtů
			[ 'ecalc_group_calc',      'Nastavení výpočtů', [ $this, 'page_group_calc' ] ],
			[ 'ecalc_settings',        'Výpočet',           [ $this, 'page_settings' ] ],
			[ 'ecalc_segments',        'Oblasti podnikání', [ $this, 'page_segments' ] ],
			[ 'ecalc_database_ranges', 'Databáze kontaktů', [ $this, 'page_database_ranges' ] ],
			[ 'ecalc_revenue_ranges',  'Rozsahy obratu',    [ $this, 'page_revenue_ranges' ] ],
			// Skupina: Nastavení obsahu
			[ 'ecalc_group_content',   'Nastavení obsahu',  [ $this, 'page_group_content' ] ],
			[ 'ecalc_packages',        'Balíčky',           [ $this, 'page_packages' ] ],
			[ 'ecalc_result_texts',    'Texty výsledků',    [ $this, 'page_result_texts' ] ],
			[ 'ecalc_form',            'Formulář & CTA',    [ $this, 'page_form' ] ],
			[ 'ecalc_info_panel',      'Info panel',        [ $this, 'page_info_panel' ] ],
			// Samostatné záložky
			[ 'ecalc_appearance',      'Vzhled',            [ $this, 'page_appearance' ] ],
			[ 'ecalc_notifications',   'Notifikace',        [ $this, 'page_notifications' ] ],
			[ 'ecalc_smartemailing',   'SmartEmailing',     [ $this, 'page_smartemailing' ] ],
			[ 'ecalc_security',        'Zabezpečení',       [ $this, 'page_security' ] ],
			[ 'ecalc_gtm',             'GTM / Měření',      [ $this, 'page_gtm' ] ],
		];

		foreach ( $pages as [ $slug, $title, $callback ] ) {
			add_submenu_page( 'ecalc_overview', $title, $title, 'manage_options', $slug, $callback );
		}
	}

	public function enqueue_assets( string $hook ): void {
		$our_pages = [
			'ecalc_overview', 'ecalc_leads', 'ecalc_settings', 'ecalc_segments',
			'ecalc_database_ranges', 'ecalc_revenue_ranges', 'ecalc_packages',
			'ecalc_result_texts', 'ecalc_form', 'ecalc_notifications', 'ecalc_smartemailing',
			'ecalc_appearance', 'ecalc_info_panel', 'ecalc_security', 'ecalc_gtm',
		];

		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
		if ( ! in_array( $page, $our_pages, true ) ) {
			return;
		}

		wp_enqueue_style( 'ecalc-admin', ECALC_PLUGIN_URL . 'assets/css/admin.css', [], ECALC_VERSION );
		wp_enqueue_script( 'ecalc-admin', ECALC_PLUGIN_URL . 'assets/js/admin.js', [], ECALC_VERSION, true );
		wp_localize_script( 'ecalc-admin', 'ecalcAdmin', [
			'ajaxurl'         => admin_url( 'admin-ajax.php' ),
			'nonceResend'     => wp_create_nonce( 'ecalc_resend' ),
			'nonceStatus'     => wp_create_nonce( 'ecalc_change_status' ),
			'nonceTestSE'     => wp_create_nonce( 'ecalc_test_se' ),
			'nonceTestTS'     => wp_create_nonce( 'ecalc_test_ts' ),
			'statusLabels'    => ECAlc_Lead_Status::all(),
			'statusColors'    => array_map( [ 'ECAlc_Lead_Status', 'color' ], array_keys( ECAlc_Lead_Status::all() ) ),
		] );

		if ( $page === 'ecalc_overview' ) {
			wp_enqueue_script(
				'chartjs',
				'https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js',
				[],
				'4.4.7',
				true
			);
			wp_enqueue_script(
				'ecalc-analytics',
				ECALC_PLUGIN_URL . 'assets/js/admin-analytics.js',
				[ 'chartjs' ],
				ECALC_VERSION,
				true
			);
			wp_localize_script( 'ecalc-analytics', 'ecalcAnalyticsData', [
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ecalc_analytics' ),
			] );
		}
	}

	// -------------------------------------------------------------------------
	// PŘEHLEDY
	// -------------------------------------------------------------------------

	public function page_overview(): void {
		$this->capability_check();

		$analytics = new ECAlc_Analytics();
		$data      = $analytics->get_data( [
			'period'             => 'this_month',
			'date_from'          => '',
			'date_to'            => '',
			'granularity'        => '',
			'filter_segment'     => '',
			'filter_status'      => '',
			'filter_result_type' => '',
			'filter_package'     => '',
		] );

		$this->render_template( 'admin/page-overview.php', compact( 'data' ) );
	}

	public function ajax_get_analytics(): void {
		$this->capability_check();
		check_ajax_referer( 'ecalc_analytics', 'nonce' );

		$analytics = new ECAlc_Analytics();
		$data      = $analytics->get_data( [
			'period'             => sanitize_text_field( $_POST['period']             ?? 'this_month' ),
			'date_from'          => sanitize_text_field( $_POST['date_from']          ?? '' ),
			'date_to'            => sanitize_text_field( $_POST['date_to']            ?? '' ),
			'granularity'        => sanitize_text_field( $_POST['granularity']        ?? '' ),
			'filter_segment'     => sanitize_text_field( $_POST['filter_segment']     ?? '' ),
			'filter_status'      => sanitize_text_field( $_POST['filter_status']      ?? '' ),
			'filter_result_type' => sanitize_text_field( $_POST['filter_result_type'] ?? '' ),
			'filter_package'     => sanitize_text_field( $_POST['filter_package']     ?? '' ),
			'filter_cta_type'    => sanitize_text_field( $_POST['filter_cta_type']    ?? '' ),
		] );

		wp_send_json_success( $data );
	}

	// -------------------------------------------------------------------------
	// LEADY
	// -------------------------------------------------------------------------

	public function page_add_lead(): void {
		$this->capability_check();
		$packages  = $this->settings->get_packages();
		$segments  = $this->settings->get_segments();
		$statuses  = ECAlc_Lead_Status::all();
		$nonce     = wp_create_nonce( 'ecalc_add_manual_lead' );
		$ajax_url  = admin_url( 'admin-ajax.php' );
		require ECALC_PLUGIN_DIR . 'templates/admin/page-add-lead.php';
	}

	public function ajax_add_manual_lead(): void {
		check_ajax_referer( 'ecalc_add_manual_lead', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Nedostatečná oprávnění.' ], 403 );
		}

		$name    = sanitize_text_field( wp_unslash( $_POST['name']    ?? '' ) );
		$email   = sanitize_email( wp_unslash( $_POST['email']  ?? '' ) );
		$phone   = sanitize_text_field( wp_unslash( $_POST['phone']   ?? '' ) );
		$status  = sanitize_key( $_POST['lead_status'] ?? ECAlc_Lead_Status::CEKANI );
		$package = sanitize_text_field( wp_unslash( $_POST['package']  ?? '' ) );
		$segment = sanitize_text_field( wp_unslash( $_POST['segment']  ?? '' ) );
		$revenue = (float) ( $_POST['monthly_revenue'] ?? 0 );
		$url     = esc_url_raw( wp_unslash( $_POST['shop_url'] ?? '' ) );
		$note    = sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) );

		if ( ! $name || ! is_email( $email ) ) {
			wp_send_json_error( [ 'message' => 'Jméno a platný e-mail jsou povinné.' ] );
			return;
		}
		if ( ! ECAlc_Lead_Status::is_valid( $status ) ) {
			$status = ECAlc_Lead_Status::CEKANI;
		}

		// Najdi existující balíček pro cenu
		$packages_cfg    = $this->settings->get_packages();
		$package_price   = 0.0;
		foreach ( $packages_cfg as $pkg ) {
			if ( ( $pkg['name'] ?? '' ) === $package ) {
				$package_price = (float) ( $pkg['price'] ?? 0 );
				break;
			}
		}

		$data = [
			'name'                         => $name,
			'email'                        => $email,
			'shop_url'                     => $url,
			'segment'                      => $segment,
			'consumable_percentage'        => 0,
			'database_range'               => '',
			'monthly_revenue'              => $revenue,
			'expected_pno'                 => 0,
			'consumable_score'             => 0,
			'database_score'               => 0,
			'segment_score'                => 0,
			'total_score'                  => 0,
			'final_potential'              => 0,
			'emailing_revenue_low'         => 0,
			'emailing_revenue_mid'         => 0,
			'emailing_revenue_high'        => 0,
			'available_budget'             => 0,
			'recommended_package'          => $package,
			'recommended_package_price'    => $package_price,
			'recommended_package_real_pno' => 0,
			'result_type'                  => 'manual',
			'consent_data'                 => 1,
			'consent_marketing'            => 1,
			'ip_address'                   => '',
			'user_agent'                   => '',
			'smartemailing_status'         => 'pending',
			'utm_source'                   => 'manual',
			'utm_medium'                   => 'admin',
			'utm_campaign'                 => '',
			'referrer'                     => '',
			'time_to_submit'               => null,
		];

		$lead_id = $this->leads->insert( $data );

		if ( ! $lead_id ) {
			wp_send_json_error( [ 'message' => 'Chyba při ukládání leadu do databáze.' ] );
			return;
		}

		// Uložit telefon pokud byl zadán
		if ( $phone ) {
			$this->leads->save_phone( $lead_id, $phone );
		}

		// Log manuálního přidání
		$log_note = 'Manuálně přidáno adminem.';
		if ( $note ) {
			$log_note .= ' Poznámka: ' . $note;
		}
		$this->leads->log_change( $lead_id, 'manual_add', $log_note );

		// Nastavit stav (spustí hook ecalc_lead_status_changed → SmartEmailing sync)
		// Jen pokud je stav jiný než výchozí CEKANI (insert ho nastavil na CEKANI)
		if ( $status !== ECAlc_Lead_Status::CEKANI ) {
			$this->leads->update_lead_status( $lead_id, $status );
		} else {
			// Pro CEKANI spustíme sync ručně
			do_action( 'ecalc_lead_status_changed', $lead_id, ECAlc_Lead_Status::CEKANI );
		}

		$se_result = $this->smartemailing->send_lead_by_id( $lead_id );

		wp_send_json_success( [
			'lead_id'     => $lead_id,
			'detail_url'  => admin_url( 'admin.php?page=ecalc_leads&action=view&id=' . $lead_id ),
			'se_status'   => $se_result['status'] ?? '',
			'message'     => 'Lead úspěšně přidán (ID ' . $lead_id . ').',
		] );
	}

	public function page_leads(): void {
		$this->capability_check();

		$action = $_GET['action'] ?? '';

		if ( $action === 'view' && ! empty( $_GET['id'] ) ) {
			$this->render_lead_detail( (int) $_GET['id'] );
			return;
		}

		$filters = [
			'search'      => sanitize_text_field( $_GET['search']      ?? '' ),
			'date_period' => sanitize_text_field( $_GET['date_period'] ?? '' ),
			'date_from'   => sanitize_text_field( $_GET['date_from']   ?? '' ),
			'date_to'     => sanitize_text_field( $_GET['date_to']     ?? '' ),
			'lead_status' => array_values( array_filter( array_map( 'sanitize_text_field', (array) ( $_GET['lead_status'] ?? [] ) ) ) ),
			'result_type' => array_values( array_filter( array_map( 'sanitize_text_field', (array) ( $_GET['result_type'] ?? [] ) ) ) ),
			'segment'     => sanitize_text_field( $_GET['segment']     ?? '' ),
			'package'     => sanitize_text_field( $_GET['package']     ?? '' ),
			'cta_type'    => sanitize_text_field( $_GET['cta_type']    ?? '' ),
			'booking'     => sanitize_text_field( $_GET['booking']     ?? '' ),
			'cta_clicked' => sanitize_text_field( $_GET['cta_clicked'] ?? '' ),
			'orderby'     => sanitize_text_field( $_GET['orderby']     ?? 'created_at' ),
			'order'       => sanitize_text_field( $_GET['order']       ?? 'DESC' ),
		];

		$per_page    = 20;
		$page        = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
		$total       = $this->leads->count( $filters );
		$leads_list  = $this->leads->get_list( array_merge( $filters, [ 'per_page' => $per_page, 'page' => $page ] ) );
		$total_pages = (int) ceil( $total / $per_page );

		$result_types  = $this->leads->get_distinct_result_types();
		$result_labels = $this->get_result_type_labels();
		$segments      = $this->leads->get_distinct_segments();
		$packages      = $this->leads->get_distinct_packages();
		$all_statuses  = ECAlc_Lead_Status::all();
		$lead_ids      = array_column( $leads_list, 'id' );
		$changelogs    = $this->leads->get_changelogs_by_ids( $lead_ids );
		$notice        = $this->get_notice();

		$this->render_template( 'admin/page-leads.php', compact(
			'leads_list', 'filters', 'result_types', 'result_labels',
			'segments', 'packages', 'all_statuses',
			'total', 'page', 'total_pages', 'per_page', 'changelogs', 'notice'
		) );
	}

	private function render_lead_detail( int $id ): void {
		$lead = $this->leads->get( $id );
		if ( ! $lead ) {
			echo '<div class="wrap"><div class="notice notice-error"><p>Lead nenalezen.</p></div></div>';
			return;
		}
		$result_labels = $this->get_result_type_labels();
		$changelog     = $this->leads->get_changelog( $id );
		$this->render_template( 'admin/page-lead-detail.php', compact( 'lead', 'result_labels', 'changelog' ) );
	}

	public function delete_lead(): void {
		$this->capability_check();
		check_admin_referer( 'ecalc_delete_lead' );
		$id = (int) ( $_POST['lead_id'] ?? 0 );
		if ( $id ) {
			$this->leads->delete( $id );
		}
		wp_redirect( admin_url( 'admin.php?page=ecalc_leads&deleted=1' ) );
		exit;
	}

	public function export_csv(): void {
		$this->capability_check();
		check_admin_referer( 'ecalc_export_csv' );

		$filters = [
			'search'      => sanitize_text_field( $_POST['search']      ?? '' ),
			'date_period' => sanitize_text_field( $_POST['date_period'] ?? '' ),
			'date_from'   => sanitize_text_field( $_POST['date_from']   ?? '' ),
			'date_to'     => sanitize_text_field( $_POST['date_to']     ?? '' ),
			'lead_status' => array_values( array_filter( array_map( 'sanitize_text_field', (array) ( $_POST['lead_status'] ?? [] ) ) ) ),
			'result_type' => array_values( array_filter( array_map( 'sanitize_text_field', (array) ( $_POST['result_type'] ?? [] ) ) ) ),
			'segment'     => sanitize_text_field( $_POST['segment']     ?? '' ),
			'package'     => sanitize_text_field( $_POST['package']     ?? '' ),
			'cta_type'    => sanitize_text_field( $_POST['cta_type']    ?? '' ),
			'booking'     => sanitize_text_field( $_POST['booking']     ?? '' ),
			'cta_clicked' => sanitize_text_field( $_POST['cta_clicked'] ?? '' ),
			'orderby'     => sanitize_text_field( $_POST['orderby']     ?? 'created_at' ),
			'order'       => sanitize_text_field( $_POST['order']       ?? 'DESC' ),
		];

		$rows = $this->leads->get_all_for_export( $filters );
		$this->output_csv( $rows, 'emailing-calculator-leads-' . date( 'Y-m-d' ) . '.csv' );
		exit;
	}

	public function bulk_action(): void {
		$this->capability_check();
		check_admin_referer( 'ecalc_bulk_action' );

		$action = sanitize_text_field( $_POST['bulk_action'] ?? $_POST['bulk_action_bottom'] ?? '' );
		$ids    = array_map( 'intval', (array) ( $_POST['lead_ids'] ?? [] ) );

		$filter_params = [
			'page'        => 'ecalc_leads',
			'search'      => sanitize_text_field( $_POST['filter_search']      ?? '' ),
			'date_period' => sanitize_text_field( $_POST['filter_date_period'] ?? '' ),
			'date_from'   => sanitize_text_field( $_POST['filter_date_from']   ?? '' ),
			'date_to'     => sanitize_text_field( $_POST['filter_date_to']     ?? '' ),
			'lead_status' => array_values( array_filter( array_map( 'sanitize_text_field', (array) ( $_POST['filter_lead_status'] ?? [] ) ) ) ),
			'result_type' => array_values( array_filter( array_map( 'sanitize_text_field', (array) ( $_POST['filter_result_type'] ?? [] ) ) ) ),
			'segment'     => sanitize_text_field( $_POST['filter_segment']     ?? '' ),
			'package'     => sanitize_text_field( $_POST['filter_package']     ?? '' ),
			'cta_type'    => sanitize_text_field( $_POST['filter_cta_type']    ?? '' ),
			'booking'     => sanitize_text_field( $_POST['filter_booking']     ?? '' ),
			'cta_clicked' => sanitize_text_field( $_POST['filter_cta_clicked'] ?? '' ),
			'orderby'     => sanitize_text_field( $_POST['filter_orderby']     ?? '' ),
			'order'       => sanitize_text_field( $_POST['filter_order']       ?? '' ),
		];
		$filter_params = array_filter( $filter_params, function( $v ) {
			return is_array( $v ) ? ! empty( $v ) : $v !== '';
		} );
		$filter_params['page'] = 'ecalc_leads';

		$redirect = admin_url( 'admin.php?' . http_build_query( $filter_params ) );

		if ( empty( $ids ) ) {
			wp_redirect( $redirect . '&bulk_error=no_selection' );
			exit;
		}

		if ( $action === 'delete' ) {
			$count = $this->leads->delete_multiple( $ids );
			wp_redirect( $redirect . '&bulk_deleted=' . $count );
			exit;
		}

		if ( $action === 'export' ) {
			$rows = $this->leads->get_by_ids( $ids );
			$this->output_csv( $rows, 'emailing-calculator-export-' . date( 'Y-m-d' ) . '.csv' );
			exit;
		}

		wp_redirect( $redirect );
		exit;
	}

	private function output_csv( array $rows, string $filename ): void {
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );

		$output = fopen( 'php://output', 'w' );
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		fputcsv( $output, [
			'ID', 'Datum', 'Jméno', 'E-mail', 'Telefon', 'URL e-shopu', 'Segment',
			'Spotřební zboží %', 'Databáze', 'Měsíční obrat', 'Očekávané PNO %',
			'Total score', 'Potenciál %', 'Obrat e-low', 'Obrat e-mid', 'Obrat e-high',
			'Dostupný budget', 'Doporučený balíček', 'Cena balíčku', 'Reálné PNO %',
			'Typ výsledku', 'Souhlas zprac.', 'Souhlas marketing', 'SmartEmailing status',
		], ';' );

		foreach ( $rows as $row ) {
			fputcsv( $output, [
				$row['id'], $row['created_at'],
				$this->csv_escape( $row['name'] ),
				$this->csv_escape( $row['email'] ),
				$this->csv_escape( $row['phone'] ?? '' ),
				$this->csv_escape( $row['shop_url'] ),
				$this->csv_escape( $row['segment'] ),
				$row['consumable_percentage'],
				$this->csv_escape( $row['database_range'] ),
				$row['monthly_revenue'], $row['expected_pno'],
				$row['total_score'], $row['final_potential'],
				$row['emailing_revenue_low'], $row['emailing_revenue_mid'], $row['emailing_revenue_high'],
				$row['available_budget'],
				$this->csv_escape( $row['recommended_package'] ),
				$row['recommended_package_price'], $row['recommended_package_real_pno'],
				$this->csv_escape( $row['result_type'] ),
				$row['consent_data'] ? 'Ano' : 'Ne',
				$row['consent_marketing'] ? 'Ano' : 'Ne',
				$this->csv_escape( $row['smartemailing_status'] ),
			], ';' );
		}
		fclose( $output );
	}

	private function csv_escape( string $val ): string {
		if ( $val !== '' && in_array( $val[0], [ '=', '+', '-', '@', "\t", "\r" ], true ) ) {
			return "\t" . $val;
		}
		return $val;
	}

	public function ajax_change_lead_status(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'code' => 'forbidden' ] );
		}

		$nonce = sanitize_text_field( $_POST['nonce'] ?? '' );
		if ( ! wp_verify_nonce( $nonce, 'ecalc_change_status' ) ) {
			wp_send_json_error( [ 'code' => 'bad_nonce' ] );
		}

		$lead_id = (int) ( $_POST['lead_id'] ?? 0 );
		$status  = sanitize_text_field( $_POST['status'] ?? '' );

		if ( ! $lead_id || ! ECAlc_Lead_Status::is_valid( $status ) ) {
			wp_send_json_error( [ 'code' => 'bad_params' ] );
		}

		$result = $this->leads->update_lead_status( $lead_id, $status );

		if ( $result ) {
			wp_send_json_success( [
				'status' => $status,
				'label'  => ECAlc_Lead_Status::label( $status ),
				'color'  => ECAlc_Lead_Status::color( $status ),
			] );
		} else {
			wp_send_json_error( [ 'code' => 'db_error' ] );
		}
	}

	public function ajax_resend_smartemailing(): void {
		$this->capability_check();
		check_ajax_referer( 'ecalc_resend', 'nonce' );

		$id   = (int) ( $_POST['lead_id'] ?? 0 );
		$lead = $this->leads->get( $id );

		if ( ! $lead ) {
			wp_send_json_error( 'Lead nenalezen.' );
		}

		$result = $this->smartemailing->send_lead( $lead, (bool) $lead['consent_marketing'] );
		$this->leads->update_smartemailing_status( $id, $result['status'], $result['response'] );

		wp_send_json_success( [ 'status' => $result['status'] ] );
	}

	public function ajax_test_smartemailing(): void {
		$this->capability_check();
		check_ajax_referer( 'ecalc_test_se', 'nonce' );

		$result = $this->smartemailing->test_connection();
		if ( $result['success'] ) {
			wp_send_json_success( [ 'message' => $result['message'] ] );
		} else {
			wp_send_json_error( [ 'message' => $result['message'] ] );
		}
	}

	public function ajax_bulk_export_smartemailing(): void {
		$this->capability_check();
		check_ajax_referer( 'ecalc_bulk_export', 'nonce' );

		$date_from = sanitize_text_field( $_POST['date_from'] ?? '' );
		$date_to   = sanitize_text_field( $_POST['date_to']   ?? '' );

		// Validace formátu data
		if ( $date_from && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) ) {
			wp_send_json_error( [ 'message' => 'Neplatný formát data od.' ] );
		}
		if ( $date_to && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ) {
			wp_send_json_error( [ 'message' => 'Neplatný formát data do.' ] );
		}

		$result = $this->smartemailing->bulk_export( $date_from, $date_to );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	public function ajax_test_turnstile(): void {
		$this->capability_check();
		check_ajax_referer( 'ecalc_test_ts', 'nonce' );

		$ts = $this->settings->get_turnstile();

		if ( empty( $ts['secret_key'] ) ) {
			wp_send_json_error( [ 'message' => 'Tajný klíč není vyplněn.' ] );
		}

		// Odeslání dummy tokenu – pokud klíč existuje, Cloudflare vrátí 'invalid-input-response',
		// nikoli 'invalid-input-secret'. To nám potvrdí, že klíč je platný.
		$response = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', [
			'timeout' => 10,
			'body'    => [
				'secret'   => $ts['secret_key'],
				'response' => 'test-token-ecalc-admin-check',
			],
		] );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( [ 'message' => 'Chyba připojení k Cloudflare: ' . $response->get_error_message() ] );
		}

		$body   = json_decode( wp_remote_retrieve_body( $response ), true );
		$errors = $body['error-codes'] ?? [];

		if ( in_array( 'invalid-input-secret', $errors, true ) ) {
			wp_send_json_error( [ 'message' => 'Tajný klíč je neplatný.' ] );
		}

		// Klíč je OK – 'invalid-input-response' je očekávané chování pro náš dummy token
		wp_send_json_success( [ 'message' => 'Tajný klíč je platný. Cloudflare API je dostupné.' ] );
	}

	// -------------------------------------------------------------------------
	// NASTAVENÍ VÝPOČTU
	// -------------------------------------------------------------------------

	public function page_settings(): void {
		$this->capability_check();
		$cfg     = $this->settings->get_settings();
		$notice  = $this->get_notice();
		$this->render_template( 'admin/page-settings.php', compact( 'cfg', 'notice' ) );
	}

	public function save_settings(): void {
		$this->capability_check();
		check_admin_referer( 'ecalc_save_settings' );

		$cw = (float) ( $_POST['consumable_weight'] ?? 70 );
		$dw = (float) ( $_POST['database_weight']   ?? 20 );
		$sw = (float) ( $_POST['segment_weight']    ?? 10 );

		$sum = $cw + $dw + $sw;
		if ( abs( $sum - 100 ) > 0.01 ) {
			wp_redirect( admin_url( 'admin.php?page=ecalc_settings&error=weights&sum=' . round( $sum, 2 ) ) );
			exit;
		}

		$current = $this->settings->get_settings();
		$current = array_merge( $current, [
			'min_potential'           => (float) ( $_POST['min_potential'] ?? 15 ),
			'max_potential'           => (float) ( $_POST['max_potential'] ?? 45 ),
			'consumable_weight'       => $cw,
			'database_weight'         => $dw,
			'segment_weight'          => $sw,
			'conservative_multiplier' => (float) ( $_POST['conservative_multiplier'] ?? 0.85 ),
			'optimistic_multiplier'   => (float) ( $_POST['optimistic_multiplier'] ?? 1.15 ),
			'low_potential_threshold' => (float) ( $_POST['low_potential_threshold'] ?? 10000 ),
			'borderline_threshold'    => (float) ( $_POST['borderline_threshold'] ?? 15000 ),
		] );

		$this->settings->save_settings( $current );
		wp_redirect( admin_url( 'admin.php?page=ecalc_settings&saved=1' ) );
		exit;
	}

	// -------------------------------------------------------------------------
	// FORMULÁŘ & CTA
	// -------------------------------------------------------------------------

	public function page_form(): void {
		$this->capability_check();
		$cfg    = $this->settings->get_settings();
		$notice = $this->get_notice();
		$this->render_template( 'admin/page-form.php', compact( 'cfg', 'notice' ) );
	}

	public function save_form_settings(): void {
		$this->capability_check();
		check_admin_referer( 'ecalc_save_form' );

		$current = $this->settings->get_settings();
		$current = array_merge( $current, [
			'cta_text'                   => sanitize_text_field( $_POST['cta_text'] ?? 'Chci konzultaci zdarma' ),
			'cta_url'                    => esc_url_raw( $_POST['cta_url'] ?? '' ),
			'marketing_consent_required' => isset( $_POST['marketing_consent_required'] ) ? 1 : 0,
			'monthly_limit'              => max( 1, (int) ( $_POST['monthly_limit'] ?? 5 ) ),
			'booking_url'                => esc_url_raw( $_POST['booking_url'] ?? '' ),
			'booking_modal_title'        => sanitize_text_field( $_POST['booking_modal_title'] ?? '' ),
			'booking_confirm_question'   => sanitize_text_field( $_POST['booking_confirm_question'] ?? '' ),
			'booking_confirm_yes'        => sanitize_text_field( $_POST['booking_confirm_yes'] ?? '' ),
			'booking_confirm_no'         => sanitize_text_field( $_POST['booking_confirm_no'] ?? '' ),
			'booking_yes_message'        => sanitize_text_field( $_POST['booking_yes_message'] ?? '' ),
			'booking_no_message'         => sanitize_text_field( $_POST['booking_no_message'] ?? '' ),
			'duplicate_title'            => sanitize_text_field( $_POST['duplicate_title']    ?? 'Již vás máme v databázi' ),
			'duplicate_msg'              => sanitize_text_field( $_POST['duplicate_msg']      ?? 'Kontakt s tímto e-mailem již existuje v naší databázi (stav: {status}).' ),
			'duplicate_question'         => sanitize_text_field( $_POST['duplicate_question'] ?? 'Chcete provést nový výpočet? Aktuální záznam bude aktualizován.' ),
			'duplicate_confirm'          => sanitize_text_field( $_POST['duplicate_confirm']  ?? 'Ano, provést nový výpočet' ),
			'duplicate_cancel'           => sanitize_text_field( $_POST['duplicate_cancel']   ?? 'Zrušit' ),
			'update_banner'              => sanitize_text_field( $_POST['update_banner']      ?? 'Váš záznam byl aktualizován na základě nového výpočtu.' ),
			'phone_dialog_title'         => sanitize_text_field( $_POST['phone_dialog_title']  ?? 'Zanechte nám telefonní číslo' ),
			'phone_dialog_desc'          => sanitize_text_field( $_POST['phone_dialog_desc']   ?? 'Pro rychlejší komunikaci nám můžete zanechat telefonní číslo.' ),
			'phone_dialog_submit'        => sanitize_text_field( $_POST['phone_dialog_submit'] ?? 'Pokračovat' ),
			'phone_dialog_skip'          => sanitize_text_field( $_POST['phone_dialog_skip']   ?? 'Přeskočit' ),
			'phone_dialog_error'         => sanitize_text_field( $_POST['phone_dialog_error']  ?? 'Zadejte platné telefonní číslo (7–15 číslic).' ),
		] );

		$this->settings->save_settings( $current );
		wp_redirect( admin_url( 'admin.php?page=ecalc_form&saved=1' ) );
		exit;
	}

	// -------------------------------------------------------------------------
	// SEGMENTY
	// -------------------------------------------------------------------------

	public function page_segments(): void {
		$this->capability_check();
		$segments = $this->settings->get_segments();
		$notice   = $this->get_notice();
		$this->render_template( 'admin/page-segments.php', compact( 'segments', 'notice' ) );
	}

	public function merge_segments(): void {
		$this->capability_check();
		check_admin_referer( 'ecalc_merge_segments' );
		$added = $this->settings->merge_default_segments();
		wp_redirect( admin_url( 'admin.php?page=ecalc_segments&merged=' . $added ) );
		exit;
	}

	public function save_segments(): void {
		$this->capability_check();
		check_admin_referer( 'ecalc_save_segments' );

		$raw      = $_POST['segments'] ?? [];
		$segments = [];
		$i        = 1;

		foreach ( $raw as $item ) {
			$segments[] = [
				'id'     => (int) ( $item['id'] ?? $i ),
				'name'   => sanitize_text_field( $item['name'] ?? '' ),
				'score'  => (float) ( $item['score'] ?? 0.5 ),
				'active' => isset( $item['active'] ) ? 1 : 0,
				'order'  => (int) ( $item['order'] ?? $i ),
			];
			$i++;
		}

		$this->settings->save_segments( $segments );
		wp_redirect( admin_url( 'admin.php?page=ecalc_segments&saved=1' ) );
		exit;
	}

	// -------------------------------------------------------------------------
	// DATABÁZE KONTAKTŮ
	// -------------------------------------------------------------------------

	public function page_database_ranges(): void {
		$this->capability_check();
		$ranges = $this->settings->get_database_ranges();
		$notice = $this->get_notice();
		$this->render_template( 'admin/page-database-ranges.php', compact( 'ranges', 'notice' ) );
	}

	public function save_database_ranges(): void {
		$this->capability_check();
		check_admin_referer( 'ecalc_save_database_ranges' );

		$raw    = $_POST['ranges'] ?? [];
		$ranges = [];
		$i      = 1;

		foreach ( $raw as $item ) {
			$ranges[] = [
				'id'     => (int) ( $item['id'] ?? $i ),
				'name'   => sanitize_text_field( $item['name'] ?? '' ),
				'min'    => (int) ( $item['min'] ?? 0 ),
				'max'    => $item['max'] !== '' && $item['max'] !== null ? (int) $item['max'] : null,
				'score'  => (float) ( $item['score'] ?? 0 ),
				'active' => isset( $item['active'] ) ? 1 : 0,
				'order'  => (int) ( $item['order'] ?? $i ),
			];
			$i++;
		}

		$this->settings->save_database_ranges( $ranges );
		wp_redirect( admin_url( 'admin.php?page=ecalc_database_ranges&saved=1' ) );
		exit;
	}

	// -------------------------------------------------------------------------
	// ROZSAHY OBRATU
	// -------------------------------------------------------------------------

	public function page_revenue_ranges(): void {
		$this->capability_check();
		$ranges = $this->settings->get_revenue_ranges();
		$notice = $this->get_notice();
		$this->render_template( 'admin/page-revenue-ranges.php', compact( 'ranges', 'notice' ) );
	}

	public function save_revenue_ranges(): void {
		$this->capability_check();
		check_admin_referer( 'ecalc_save_revenue_ranges' );

		$raw    = $_POST['ranges'] ?? [];
		$ranges = [];
		$i      = 1;

		foreach ( $raw as $item ) {
			$ranges[] = [
				'id'         => (int) ( $item['id'] ?? $i ),
				'name'       => sanitize_text_field( $item['name'] ?? '' ),
				'min'        => (int) ( $item['min'] ?? 0 ),
				'max'        => $item['max'] !== '' && $item['max'] !== null ? (int) $item['max'] : null,
				'calc_value' => (float) ( $item['calc_value'] ?? 0 ),
				'active'     => isset( $item['active'] ) ? 1 : 0,
				'order'      => (int) ( $item['order'] ?? $i ),
			];
			$i++;
		}

		$this->settings->save_revenue_ranges( $ranges );
		wp_redirect( admin_url( 'admin.php?page=ecalc_revenue_ranges&saved=1' ) );
		exit;
	}

	// -------------------------------------------------------------------------
	// BALÍČKY
	// -------------------------------------------------------------------------

	public function page_packages(): void {
		$this->capability_check();
		$packages = $this->settings->get_packages();
		$notice   = $this->get_notice();
		$se       = $this->settings->get_smartemailing();
		$this->render_template( 'admin/page-packages.php', compact( 'packages', 'notice', 'se' ) );
	}

	public function save_packages(): void {
		$this->capability_check();
		check_admin_referer( 'ecalc_save_packages' );

		$raw      = $_POST['packages'] ?? [];
		$packages = [];
		$i        = 1;

		foreach ( $raw as $item ) {
			$items_raw = $item['items'] ?? [];
			$items     = [];
			foreach ( $items_raw as $it ) {
				$it = sanitize_text_field( $it );
				if ( $it !== '' ) {
					$items[] = $it;
				}
			}

			$packages[] = [
				'id'          => (int) ( $item['id'] ?? $i ),
				'name'        => sanitize_text_field( $item['name'] ?? '' ),
				'price'       => (float) ( $item['price'] ?? 0 ),
				'description' => sanitize_textarea_field( $item['description'] ?? '' ),
				'items'       => $items,
				'result_text' => sanitize_textarea_field( $item['result_text'] ?? '' ),
				'active'      => isset( $item['active'] ) ? 1 : 0,
				'order'       => (int) ( $item['order'] ?? $i ),
				'se_value'    => sanitize_text_field( $item['se_value'] ?? '' ),
			];
			$i++;
		}

		$this->settings->save_packages( $packages );
		wp_redirect( admin_url( 'admin.php?page=ecalc_packages&saved=1' ) );
		exit;
	}

	// -------------------------------------------------------------------------
	// TEXTY VÝSLEDKŮ
	// -------------------------------------------------------------------------

	public function page_result_texts(): void {
		$this->capability_check();
		$texts  = $this->settings->get_result_texts();
		$notice = $this->get_notice();
		$this->render_template( 'admin/page-result-texts.php', compact( 'texts', 'notice' ) );
	}

	public function save_result_texts(): void {
		$this->capability_check();
		check_admin_referer( 'ecalc_save_result_texts' );

		$data = [
			'result_title'  => sanitize_text_field( $_POST['result_title'] ?? 'Výsledek vaší kalkulačky' ),
			'low_potential' => sanitize_textarea_field( $_POST['low_potential'] ?? '' ),
			'borderline'    => sanitize_textarea_field( $_POST['borderline'] ?? '' ),
		];

		$this->settings->save_result_texts( $data );
		wp_redirect( admin_url( 'admin.php?page=ecalc_result_texts&saved=1' ) );
		exit;
	}

	// -------------------------------------------------------------------------
	// E-MAILOVÉ NOTIFIKACE
	// -------------------------------------------------------------------------

	public function page_notifications(): void {
		$this->capability_check();
		$notif  = $this->settings->get_notifications();
		$notice = $this->get_notice();
		$this->render_template( 'admin/page-notifications.php', compact( 'notif', 'notice' ) );
	}

	public function save_notifications(): void {
		$this->capability_check();
		check_admin_referer( 'ecalc_save_notifications' );

		$data = [
			'admin_enabled'  => isset( $_POST['admin_enabled'] ) ? 1 : 0,
			'admin_email'    => sanitize_email( $_POST['admin_email'] ?? '' ),
			'admin_subject'  => sanitize_text_field( $_POST['admin_subject'] ?? '' ),
			'admin_body'     => sanitize_textarea_field( $_POST['admin_body'] ?? '' ),
			'client_enabled' => isset( $_POST['client_enabled'] ) ? 1 : 0,
			'client_subject' => sanitize_text_field( $_POST['client_subject'] ?? '' ),
			'client_body'    => sanitize_textarea_field( $_POST['client_body'] ?? '' ),
			// Trigger: follow-up
			'trigger_followup_enabled'       => isset( $_POST['trigger_followup_enabled'] ) ? 1 : 0,
			'trigger_followup_delay_hours'   => max( 1, (int) ( $_POST['trigger_followup_delay_hours'] ?? 24 ) ),
			'trigger_followup_subject'       => sanitize_text_field( $_POST['trigger_followup_subject'] ?? '' ),
			'trigger_followup_body'          => sanitize_textarea_field( $_POST['trigger_followup_body'] ?? '' ),
			// Trigger: poptávka balíčku
			'trigger_inquiry_enabled'         => isset( $_POST['trigger_inquiry_enabled'] ) ? 1 : 0,
			'trigger_inquiry_admin_subject'   => sanitize_text_field( $_POST['trigger_inquiry_admin_subject'] ?? '' ),
			'trigger_inquiry_admin_body'      => sanitize_textarea_field( $_POST['trigger_inquiry_admin_body'] ?? '' ),
			'trigger_inquiry_client_subject'  => sanitize_text_field( $_POST['trigger_inquiry_client_subject'] ?? '' ),
			'trigger_inquiry_client_body'     => sanitize_textarea_field( $_POST['trigger_inquiry_client_body'] ?? '' ),
			// Trigger: poptávka konzultace
			'trigger_consultation_enabled'        => isset( $_POST['trigger_consultation_enabled'] ) ? 1 : 0,
			'trigger_consultation_admin_subject'  => sanitize_text_field( $_POST['trigger_consultation_admin_subject'] ?? '' ),
			'trigger_consultation_admin_body'     => sanitize_textarea_field( $_POST['trigger_consultation_admin_body'] ?? '' ),
			'trigger_consultation_client_subject' => sanitize_text_field( $_POST['trigger_consultation_client_subject'] ?? '' ),
			'trigger_consultation_client_body'    => sanitize_textarea_field( $_POST['trigger_consultation_client_body'] ?? '' ),
		];

		$this->settings->save_notifications( $data );
		wp_redirect( admin_url( 'admin.php?page=ecalc_notifications&saved=1' ) );
		exit;
	}

	// -------------------------------------------------------------------------
	// SMARTEMAILING
	// -------------------------------------------------------------------------

	public function page_smartemailing(): void {
		$this->capability_check();
		$se     = $this->settings->get_smartemailing();
		$notice = $this->get_notice();
		$this->render_template( 'admin/page-smartemailing.php', compact( 'se', 'notice' ) );
	}

	public function save_smartemailing(): void {
		$this->capability_check();
		check_admin_referer( 'ecalc_save_smartemailing' );

		$data = [
			'enabled'                            => isset( $_POST['enabled'] ) ? 1 : 0,
			'username'                           => sanitize_text_field( $_POST['username'] ?? '' ),
			'api_key'                            => sanitize_text_field( $_POST['api_key'] ?? '' ),
			'list_id'                            => sanitize_text_field( $_POST['list_id'] ?? '' ),
			'default_tag'                        => sanitize_text_field( $_POST['default_tag'] ?? '' ),
			'status_tag_prefix'                  => sanitize_text_field( $_POST['status_tag_prefix'] ?? 'lead-' ),
			'status_customfield_id'              => (int) ( $_POST['status_customfield_id'] ?? 0 ),
			'require_marketing_consent'          => isset( $_POST['require_marketing_consent'] ) ? 1 : 0,
			'send_low_potential_without_consent' => isset( $_POST['send_low_potential_without_consent'] ) ? 1 : 0,
			'cf_segment'                         => (int) ( $_POST['cf_segment']          ?? 0 ),
			'cf_monthly_revenue'                 => (int) ( $_POST['cf_monthly_revenue']  ?? 0 ),
			'cf_final_potential'                 => (int) ( $_POST['cf_final_potential']  ?? 0 ),
			'cf_emailing_mid'                    => (int) ( $_POST['cf_emailing_mid']     ?? 0 ),
			'cf_available_budget'                => (int) ( $_POST['cf_available_budget'] ?? 0 ),
			'cf_package'                         => (int) ( $_POST['cf_package']          ?? 0 ),
			'low_potential_se_value'             => sanitize_text_field( $_POST['low_potential_se_value'] ?? 'Nízký potenciál' ),
		];

		$this->settings->save_smartemailing( $data );
		wp_redirect( admin_url( 'admin.php?page=ecalc_smartemailing&saved=1' ) );
		exit;
	}

	// -------------------------------------------------------------------------
	// VZHLED
	// -------------------------------------------------------------------------

	public function page_appearance(): void {
		$this->capability_check();
		$appearance = $this->settings->get_appearance();
		$notice     = $this->get_notice();
		$this->render_template( 'admin/page-appearance.php', compact( 'appearance', 'notice' ) );
	}

	public function save_appearance(): void {
		$this->capability_check();
		check_admin_referer( 'ecalc_save_appearance' );

		$color  = function ( string $key, string $default ): string {
			$raw = $_POST[ $key ] ?? $default;
			$hex = sanitize_hex_color( $raw );
			// Povolíme také 'transparent' pro btn2_bg
			return $hex ?: sanitize_text_field( $raw );
		};
		$text   = fn( string $key, string $default ) => sanitize_text_field( $_POST[ $key ] ?? $default );
		$weight = function ( string $key ): string {
			$allowed = [ '400', '600', '700', '800' ];
			$val     = sanitize_text_field( $_POST[ $key ] ?? '700' );
			return in_array( $val, $allowed, true ) ? $val : '700';
		};

		$data = [
			// Základní
			'base_font_size'    => $text( 'base_font_size',   '15px' ),
			'base_text_color'   => $color( 'base_text_color',  '#1e293b' ),
			'muted_text_color'  => $color( 'muted_text_color', '#475569' ),
			'subtle_text_color' => $color( 'subtle_text_color','#94a3b8' ),
			'primary_accent'    => $color( 'primary_accent',   '#4de5c4' ),
			// Nadpisy
			'h1_size'   => $text( 'h1_size',   '1.6rem' ),   'h1_color' => $color( 'h1_color', '#1e293b' ), 'h1_weight' => $weight( 'h1_weight' ),
			'h2_size'   => $text( 'h2_size',   '1.45rem' ),  'h2_color' => $color( 'h2_color', '#1e293b' ), 'h2_weight' => $weight( 'h2_weight' ),
			'h3_size'   => $text( 'h3_size',   '1.1rem' ),   'h3_color' => $color( 'h3_color', '#1e293b' ), 'h3_weight' => $weight( 'h3_weight' ),
			// Primární tlačítko
			'btn_bg'           => $color( 'btn_bg',           '#4de5c4' ),
			'btn_bg_hover'     => $color( 'btn_bg_hover',     '#ffffff' ),
			'btn_color'        => $color( 'btn_color',        '#352830' ),
			'btn_color_hover'  => $color( 'btn_color_hover',  '#4de5c4' ),
			'btn_border'       => $color( 'btn_border',       '#4de5c4' ),
			'btn_border_hover' => $color( 'btn_border_hover', '#4de5c4' ),
			'btn_border_width' => $text( 'btn_border_width',  '2px' ),
			'btn_font_size'    => $text( 'btn_font_size',     '1rem' ),
			'btn_font_weight'  => $weight( 'btn_font_weight' ),
			'btn_pad_y'        => $text( 'btn_pad_y',         '14px' ),
			'btn_pad_x'        => $text( 'btn_pad_x',         '32px' ),
			// Sekundární tlačítko
			'btn2_bg'           => $color( 'btn2_bg',           'transparent' ),
			'btn2_bg_hover'     => $color( 'btn2_bg_hover',     '#4de5c4' ),
			'btn2_color'        => $color( 'btn2_color',        '#4de5c4' ),
			'btn2_color_hover'  => $color( 'btn2_color_hover',  '#352830' ),
			'btn2_border'       => $color( 'btn2_border',       '#4de5c4' ),
			'btn2_border_hover' => $color( 'btn2_border_hover', '#4de5c4' ),
			// Formulář
			'input_border'       => $color( 'input_border',       '#e2e8f0' ),
			'input_border_focus' => $color( 'input_border_focus', '#4de5c4' ),
			'input_bg'           => $color( 'input_bg',           '#f8fafc' ),
			'label_color'        => $color( 'label_color',        '#1e293b' ),
			// Layout
			'dark_panel_bg'   => $color( 'dark_panel_bg',   '#352830' ),
			'dark_panel_text' => $color( 'dark_panel_text', '#ffffff' ),
			'border_radius'   => $text( 'border_radius',    '12px' ),
			'card_bg'         => $color( 'card_bg',         '#ffffff' ),
			'card_border'     => $color( 'card_border',     '#e2e8f0' ),
		];

		$this->settings->save_appearance( $data );
		wp_redirect( admin_url( 'admin.php?page=ecalc_appearance&saved=1' ) );
		exit;
	}

	// -------------------------------------------------------------------------
	// INFO PANEL
	// -------------------------------------------------------------------------

	public function page_info_panel(): void {
		$this->capability_check();
		$panel  = $this->settings->get_info_panel();
		$notice = $this->get_notice();
		$this->render_template( 'admin/page-info-panel.php', compact( 'panel', 'notice' ) );
	}

	public function save_info_panel(): void {
		$this->capability_check();
		check_admin_referer( 'ecalc_save_info_panel' );

		$raw_items = (array) ( $_POST['items'] ?? [] );
		$items     = [];
		foreach ( $raw_items as $item ) {
			$item = sanitize_text_field( $item );
			if ( $item !== '' ) {
				$items[] = $item;
			}
		}

		$data = [
			'title' => sanitize_text_field( $_POST['title'] ?? '' ),
			'items' => $items,
			'note'  => sanitize_textarea_field( $_POST['note'] ?? '' ),
		];

		$this->settings->save_info_panel( $data );
		wp_redirect( admin_url( 'admin.php?page=ecalc_info_panel&saved=1' ) );
		exit;
	}

	// -------------------------------------------------------------------------
	// ZABEZPEČENÍ (Turnstile)
	// -------------------------------------------------------------------------

	public function page_security(): void {
		$this->capability_check();
		$turnstile = $this->settings->get_turnstile();
		$notice    = $this->get_notice();
		$this->render_template( 'admin/page-security.php', compact( 'turnstile', 'notice' ) );
	}

	public function page_gtm(): void {
		$this->capability_check();
		$this->render_template( 'admin/page-gtm.php', [] );
	}

	public function save_security(): void {
		$this->capability_check();
		check_admin_referer( 'ecalc_save_security' );

		$data = [
			'enabled'    => isset( $_POST['turnstile_enabled'] ) ? 1 : 0,
			'site_key'   => sanitize_text_field( $_POST['turnstile_site_key']   ?? '' ),
			'secret_key' => sanitize_text_field( $_POST['turnstile_secret_key'] ?? '' ),
		];

		$this->settings->save_turnstile( $data );
		wp_redirect( admin_url( 'admin.php?page=ecalc_security&saved=1' ) );
		exit;
	}

	// -------------------------------------------------------------------------
	// SEKCE "Grou.cz" V ADMIN MENU
	// -------------------------------------------------------------------------

	public function add_group_separator(): void {
		grou_register_admin_menu_group( 30 );
	}

	public function output_group_separator_css(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		grou_output_admin_group_css();
	}

	// -------------------------------------------------------------------------
	// SKUPINY V SUBMENU
	// -------------------------------------------------------------------------

	public function page_group_calc(): void {
		wp_safe_redirect( admin_url( 'admin.php?page=ecalc_settings' ) );
		exit;
	}

	public function page_group_content(): void {
		wp_safe_redirect( admin_url( 'admin.php?page=ecalc_packages' ) );
		exit;
	}

	public function output_submenu_group_css(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<style>
/* === Skupinové záhlaví v submenu === */
#adminmenu li a[href$="page=ecalc_group_calc"],
#adminmenu li a[href$="page=ecalc_group_content"] {
	pointer-events: none !important;
	cursor: default !important;
	color: #72aee6 !important;
	font-size: 10px !important;
	font-weight: 700 !important;
	letter-spacing: 0.7px !important;
	text-transform: uppercase !important;
	padding-top: 14px !important;
	padding-bottom: 3px !important;
	opacity: 1 !important;
}
#adminmenu li a[href$="page=ecalc_group_calc"]:hover,
#adminmenu li a[href$="page=ecalc_group_content"]:hover {
	background: none !important;
	color: #72aee6 !important;
}
/* === Odsazení pod-položek === */
#adminmenu li a[href$="page=ecalc_settings"],
#adminmenu li a[href$="page=ecalc_segments"],
#adminmenu li a[href$="page=ecalc_database_ranges"],
#adminmenu li a[href$="page=ecalc_revenue_ranges"],
#adminmenu li a[href$="page=ecalc_packages"],
#adminmenu li a[href$="page=ecalc_result_texts"],
#adminmenu li a[href$="page=ecalc_form"],
#adminmenu li a[href$="page=ecalc_info_panel"] {
	padding-left: 22px !important;
}
</style>';
	}

	// -------------------------------------------------------------------------
	// HELPERS
	// -------------------------------------------------------------------------

	private function capability_check(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Nemáte oprávnění k přístupu na tuto stránku.' );
		}
	}

	private function get_notice(): array {
		$notice = [ 'type' => '', 'message' => '' ];

		if ( isset( $_GET['merged'] ) ) {
			$n = (int) $_GET['merged'];
			$notice = $n > 0
				? [ 'type' => 'success', 'message' => "Přidáno {$n} nových segmentů." ]
				: [ 'type' => 'info',    'message' => 'Žádné nové segmenty k přidání – vše je již aktuální.' ];
		} elseif ( isset( $_GET['saved'] ) ) {
			$notice = [ 'type' => 'success', 'message' => 'Nastavení bylo uloženo.' ];
		} elseif ( isset( $_GET['deleted'] ) ) {
			$notice = [ 'type' => 'success', 'message' => 'Lead byl smazán.' ];
		} elseif ( isset( $_GET['bulk_deleted'] ) ) {
			$count = (int) $_GET['bulk_deleted'];
			$notice = [ 'type' => 'success', 'message' => "Smazáno leadů: {$count}." ];
		} elseif ( isset( $_GET['bulk_error'] ) && $_GET['bulk_error'] === 'no_selection' ) {
			$notice = [ 'type' => 'warning', 'message' => 'Nejprve vyberte alespoň jeden lead.' ];
		} elseif ( isset( $_GET['error'] ) ) {
			if ( $_GET['error'] === 'weights' ) {
				$sum = isset( $_GET['sum'] ) ? (float) $_GET['sum'] : 0;
				$notice = [
					'type'    => 'error',
					'message' => sprintf(
						'Součet vah musí být přesně 100 %%. Aktuální součet: %s %%.',
						esc_html( (string) $sum )
					),
				];
			}
		}

		return $notice;
	}

	private function get_result_type_labels(): array {
		return [
			'low_potential' => 'Nízký potenciál',
			'borderline'    => 'Hraniční potenciál',
			'package_1'     => 'Doporučen Balíček 1',
			'package_n'     => 'Doporučen vyšší balíček',
		];
	}

	private function render_template( string $template, array $vars = [] ): void {
		$file = ECALC_PLUGIN_DIR . 'templates/' . $template;
		if ( ! file_exists( $file ) ) {
			echo '<div class="notice notice-error"><p>Šablona nenalezena: ' . esc_html( $template ) . '</p></div>';
			return;
		}
		extract( $vars, EXTR_SKIP );
		include $file;
	}
}
