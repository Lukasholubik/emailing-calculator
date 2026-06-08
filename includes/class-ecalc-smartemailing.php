<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SmartEmailing adapter – API v3
 * Endpoint: POST https://app.smartemailing.cz/api/v3/import
 * Auth: Basic base64(username:api_key)
 * Docs: https://app.smartemailing.cz/docs/api/v3/index.html
 */
class ECAlc_SmartEmailing {

	private ECAlc_Settings $settings;
	private const API_BASE = 'https://app.smartemailing.cz/api/v3';

	public function __construct( ECAlc_Settings $settings ) {
		$this->settings = $settings;
		add_action( 'ecalc_lead_status_changed', [ $this, 'on_status_changed' ], 10, 2 );
	}

	// -------------------------------------------------------------------------
	// Odeslání leadu (první import po vyplnění formuláře)
	// -------------------------------------------------------------------------
	public function send_lead( array $lead_data, bool $has_marketing_consent ): array {
		$cfg = $this->settings->get_smartemailing();

		if ( ! $cfg['enabled'] ) {
			return [ 'status' => 'disabled', 'response' => '' ];
		}
		if ( $cfg['require_marketing_consent'] && ! $has_marketing_consent ) {
			return [ 'status' => 'skipped_no_consent', 'response' => '' ];
		}
		if ( empty( $cfg['username'] ) || empty( $cfg['api_key'] ) ) {
			return [ 'status' => 'failed', 'response' => 'Chybí přihlašovací údaje.' ];
		}

		return $this->import_contact( $lead_data, $cfg, ECAlc_Lead_Status::CEKANI );
	}

	// -------------------------------------------------------------------------
	// Sync při změně stavu leadu
	// -------------------------------------------------------------------------
	public function on_status_changed( int $lead_id, string $status ): void {
		$cfg = $this->settings->get_smartemailing();
		if ( ! $cfg['enabled'] ) return;
		if ( empty( $cfg['username'] ) || empty( $cfg['api_key'] ) ) return;

		global $wpdb;
		$table = $wpdb->prefix . 'emailing_calculator_leads';
		$lead  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $lead_id ), ARRAY_A );
		if ( ! $lead ) return;

		if ( $cfg['require_marketing_consent'] && ! (int) $lead['consent_marketing'] ) {
			return;
		}

		$result = $this->import_contact( $lead, $cfg, $status );

		// Aktualizovat SE status v DB, aby byl viditelný v adminu
		$wpdb->update(
			$table,
			[
				'smartemailing_status'          => $result['status'],
				'smartemailing_last_response'   => mb_substr( $result['response'] ?? '', 0, 1000 ),
				'smartemailing_last_attempt_at' => current_time( 'mysql', true ),
			],
			[ 'id' => $lead_id ],
			[ '%s', '%s', '%s' ],
			[ '%d' ]
		);
	}

	// -------------------------------------------------------------------------
	// Ruční resend
	// -------------------------------------------------------------------------
	public function send_lead_by_id( int $lead_id ): array {
		$cfg = $this->settings->get_smartemailing();
		if ( ! $cfg['enabled'] ) return [ 'status' => 'disabled', 'response' => '' ];

		global $wpdb;
		$table = $wpdb->prefix . 'emailing_calculator_leads';
		$lead  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $lead_id ), ARRAY_A );
		if ( ! $lead ) return [ 'status' => 'failed', 'response' => 'Lead nenalezen.' ];

		return $this->import_contact( $lead, $cfg, $lead['lead_status'] ?? ECAlc_Lead_Status::CEKANI );
	}

	// -------------------------------------------------------------------------
	// Hlavní import – vytvoří nebo aktualizuje kontakt (nikdy neduplikuje)
	// -------------------------------------------------------------------------
	private function import_contact( array $lead, array $cfg, string $lead_status ): array {
		$name_parts = explode( ' ', trim( $lead['name'] ?? '' ), 2 );
		$first_name = $name_parts[0] ?? '';
		$last_name  = $name_parts[1] ?? '';

		$contact = [
			'emailaddress' => $lead['email'],
			'name'         => $first_name,
			'surname'      => $last_name,
		];

		if ( ! empty( $lead['phone'] ) ) {
			$contact['cellphone'] = sanitize_text_field( $lead['phone'] );
		}

		// Přiřadit do seznamu
		if ( ! empty( $cfg['list_id'] ) ) {
			$contact['contactlists'] = [
				[ 'id' => (int) $cfg['list_id'], 'status' => 'confirmed' ],
			];
		}

		// Status custom field (pokud je nakonfigurovaný)
		$customfields = [];
		if ( ! empty( $cfg['status_customfield_id'] ) ) {
			$customfields[] = [
				'id'    => (int) $cfg['status_customfield_id'],
				'value' => ECAlc_Lead_Status::label( $lead_status ),
			];
		}

		// Vlastní custom fields z lead dat
		$extra_fields = $this->map_custom_fields( $lead, $cfg );
		$customfields = array_merge( $customfields, $extra_fields );

		if ( $customfields ) {
			$contact['customfields'] = $customfields;
		}

		// Tagy: status tag + výchozí tag
		$tags = [];
		$prefix = sanitize_text_field( $cfg['status_tag_prefix'] ?? 'lead-' );
		$tags[] = $prefix . $lead_status;

		if ( ! empty( $cfg['default_tag'] ) ) {
			$tags[] = sanitize_text_field( $cfg['default_tag'] );
		}

		// Tagy výsledku
		$result_tags = [
			'low_potential' => $prefix . 'nizky-potencial',
			'borderline'    => $prefix . 'hranicni-potencial',
			'package_1'     => $prefix . 'balicek-1',
			'package_n'     => $prefix . 'balicek-n',
		];
		$rt = $lead['result_type'] ?? '';
		if ( isset( $result_tags[ $rt ] ) ) {
			$tags[] = $result_tags[ $rt ];
		}

		$contact['tags'] = array_values( array_unique( $tags ) );

		$body = [
			'settings' => [
				'update_existing_contacts' => true,
				'skip_invalid_orders'      => true,
			],
			'data' => [ $contact ],
		];

		return $this->request( 'POST', '/import', $body, $cfg );
	}

	// -------------------------------------------------------------------------
	// Mapování lead dat na custom fields dle nastavení
	// -------------------------------------------------------------------------
	private function map_custom_fields( array $lead, array $cfg ): array {
		$fields  = [];
		$mapping = [
			'cf_segment'         => $lead['segment']              ?? '',
			'cf_monthly_revenue' => $lead['monthly_revenue']      ?? '',
			'cf_final_potential' => $lead['final_potential']      ?? '',
			'cf_emailing_mid'    => $lead['emailing_revenue_mid'] ?? '',
			'cf_available_budget'=> $lead['available_budget']     ?? '',
			// Hodnota balíčku pro SE: preferuje se_value z nastavení balíčku, fallback na název balíčku
			// recommended_package může být string (z DB) nebo array (z kalkulátoru) – normalizujeme na string
			'cf_package'         => $this->get_package_se_value(
				$this->extract_package_id( $lead['recommended_package'] ?? '' ),
				$lead['recommended_package_name'] ?? ''
			),
		];

		foreach ( $mapping as $setting_key => $value ) {
			$field_id = (int) ( $cfg[ $setting_key ] ?? 0 );
			if ( $field_id > 0 && $value !== '' ) {
				$fields[] = [ 'id' => $field_id, 'value' => (string) $value ];
			}
		}

		return $fields;
	}

	// -------------------------------------------------------------------------
	// Aktualizace telefonního čísla existujícího kontaktu
	// -------------------------------------------------------------------------
	public function update_phone( array $lead ): void {
		$cfg = $this->settings->get_smartemailing();
		if ( ! $cfg['enabled'] ) return;
		if ( empty( $cfg['username'] ) || empty( $cfg['api_key'] ) ) return;
		if ( $cfg['require_marketing_consent'] && ! (int) ( $lead['consent_marketing'] ?? 0 ) ) return;
		if ( empty( $lead['phone'] ) ) return;

		$body = [
			'settings' => [
				'update_existing_contacts' => true,
				'skip_invalid_orders'      => true,
			],
			'data' => [ [
				'emailaddress' => $lead['email'],
				'cellphone'    => sanitize_text_field( $lead['phone'] ),
			] ],
		];

		$this->request( 'POST', '/import', $body, $cfg );
	}

	// -------------------------------------------------------------------------
	// Test připojení
	// -------------------------------------------------------------------------
	public function test_connection(): array {
		$cfg = $this->settings->get_smartemailing();
		if ( empty( $cfg['username'] ) || empty( $cfg['api_key'] ) ) {
			return [ 'success' => false, 'message' => 'Vyplňte username a API klíč.' ];
		}

		$result = $this->request( 'GET', '/ping', [], $cfg );

		if ( $result['status'] === 'sent' ) {
			return [ 'success' => true, 'message' => 'Připojení úspěšné.' ];
		}
		return [ 'success' => false, 'message' => 'Chyba: ' . $result['response'] ];
	}

	// -------------------------------------------------------------------------
	// HTTP request
	// -------------------------------------------------------------------------
	private function request( string $method, string $endpoint, array $body, array $cfg ): array {
		$url  = self::API_BASE . $endpoint;
		$auth = base64_encode( $cfg['username'] . ':' . $cfg['api_key'] );

		$args = [
			'method'  => strtoupper( $method ),
			'headers' => [
				'Authorization' => 'Basic ' . $auth,
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
			],
			'timeout' => 15,
		];

		if ( ! empty( $body ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return [ 'status' => 'failed', 'response' => $response->get_error_message() ];
		}

		$code        = wp_remote_retrieve_response_code( $response );
		$body_string = wp_remote_retrieve_body( $response );

		if ( $code >= 200 && $code < 300 ) {
			return [ 'status' => 'sent', 'response' => $body_string ];
		}

		return [ 'status' => 'failed', 'response' => "HTTP {$code}: {$body_string}" ];
	}

	// -------------------------------------------------------------------------
	// Hromadný export historických leadů
	// -------------------------------------------------------------------------
	public function bulk_export( string $date_from = '', string $date_to = '' ): array {
		$cfg = $this->get_validated_cfg();
		if ( ! $cfg ) {
			return [ 'success' => false, 'message' => 'Integrace není nakonfigurována nebo aktivní.' ];
		}

		global $wpdb;
		$table  = $wpdb->prefix . 'emailing_calculator_leads';
		$where  = '1=1';
		$values = [];

		if ( $date_from ) {
			$where   .= ' AND DATE(created_at) >= %s';
			$values[] = sanitize_text_field( $date_from );
		}
		if ( $date_to ) {
			$where   .= ' AND DATE(created_at) <= %s';
			$values[] = sanitize_text_field( $date_to );
		}
		if ( $cfg['require_marketing_consent'] ) {
			$where .= ' AND consent_marketing = 1';
		}

		$sql   = "SELECT * FROM {$table} WHERE {$where} ORDER BY id ASC";
		$leads = $values
			? $wpdb->get_results( $wpdb->prepare( $sql, ...$values ), ARRAY_A )
			: $wpdb->get_results( $sql, ARRAY_A );

		if ( empty( $leads ) ) {
			return [ 'success' => true, 'message' => 'Žádné leady ke exportu (nebo žádné s marketingovým souhlasem).', 'count' => 0 ];
		}

		$sent  = 0;
		$errors = 0;
		foreach ( $leads as $lead ) {
			$result = $this->import_contact( $lead, $cfg, $lead['lead_status'] ?? 'cekani' );
			if ( $result['status'] === 'sent' ) {
				$sent++;
				$wpdb->update( $table, [
					'smartemailing_status'       => 'synced',
					'smartemailing_last_attempt_at' => current_time( 'mysql', true ),
				], [ 'id' => $lead['id'] ], [ '%s', '%s' ], [ '%d' ] );
			} else {
				$errors++;
			}
		}

		$msg = "Export dokončen: {$sent} odesláno";
		if ( $errors ) $msg .= ", {$errors} chyb";
		$msg .= " (celkem " . count( $leads ) . " leadů).";

		return [ 'success' => true, 'message' => $msg, 'count' => $sent, 'errors' => $errors ];
	}

	private function extract_package_id( mixed $package ): string {
		if ( is_array( $package ) ) {
			return (string) ( $package['id'] ?? $package['name'] ?? '' );
		}
		return (string) $package;
	}

	private function get_package_se_value( string $package_id, string $package_name ): string {
		$packages = $this->settings->get_packages();
		foreach ( $packages as $pkg ) {
			if ( (string) ( $pkg['id'] ?? '' ) === $package_id || ( $pkg['name'] ?? '' ) === $package_name ) {
				$se_value = trim( $pkg['se_value'] ?? '' );
				// Pokud je se_value vyplněno, použij ho; jinak název balíčku
				return $se_value !== '' ? $se_value : ( $pkg['name'] ?? $package_name );
			}
		}
		// Fallback – název balíčku z leadu
		return $package_name;
	}

	private function get_validated_cfg(): ?array {
		$cfg = $this->settings->get_smartemailing();
		if ( ! $cfg['enabled'] ) return null;
		if ( empty( $cfg['username'] ) || empty( $cfg['api_key'] ) ) return null;
		return $cfg;
	}

	// Zpětná kompatibilita
	public function send_lead_obj( array $lead_data, bool $consent ): array {
		return $this->send_lead( $lead_data, $consent );
	}
}
