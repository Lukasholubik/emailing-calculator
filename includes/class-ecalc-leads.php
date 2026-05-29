<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ECAlc_Lead_Status {
	const CEKANI       = 'cekani';
	const POPTANO      = 'poptano';
	const SCHUZKA      = 'schuzka';
	const NEAKTIVNI    = 'neaktivni';
	const ZOBCHODOVANO = 'zobchodovano';

	public static function all(): array {
		return [
			self::CEKANI       => 'Čekání',
			self::POPTANO      => 'Poptáno',
			self::SCHUZKA      => 'Schůzka',
			self::NEAKTIVNI    => 'Neaktivní',
			self::ZOBCHODOVANO => 'Zobchodováno',
		];
	}

	public static function is_valid( string $status ): bool {
		return array_key_exists( $status, self::all() );
	}

	public static function label( string $status ): string {
		return self::all()[ $status ] ?? $status;
	}

	public static function color( string $status ): string {
		return [
			self::CEKANI       => 'gray',
			self::POPTANO      => 'blue',
			self::SCHUZKA      => 'green',
			self::NEAKTIVNI    => 'orange',
			self::ZOBCHODOVANO => 'purple',
		][ $status ] ?? 'gray';
	}
}

class ECAlc_Leads {

	private const SORTABLE_COLUMNS = [
		'id', 'created_at', 'name', 'email', 'lead_status', 'result_type',
		'recommended_package', 'recommended_package_price', 'final_potential',
		'monthly_revenue', 'cta_at', 'segment',
	];

	private function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'emailing_calculator_leads';
	}

	public function insert( array $data ): int {
		global $wpdb;

		$row = [
			'created_at'                   => current_time( 'mysql' ),
			'name'                         => sanitize_text_field( $data['name'] ),
			'email'                        => sanitize_email( $data['email'] ),
			'shop_url'                     => esc_url_raw( $data['shop_url'] ),
			'segment'                      => sanitize_text_field( $data['segment'] ),
			'consumable_percentage'        => (int) $data['consumable_percentage'],
			'database_range'               => sanitize_text_field( $data['database_range'] ),
			'monthly_revenue'              => (float) $data['monthly_revenue'],
			'expected_pno'                 => (int) $data['expected_pno'],
			'consumable_score'             => (float) $data['consumable_score'],
			'database_score'               => (float) $data['database_score'],
			'segment_score'                => (float) $data['segment_score'],
			'total_score'                  => (float) $data['total_score'],
			'final_potential'              => (float) $data['final_potential'],
			'emailing_revenue_low'         => (float) $data['emailing_revenue_low'],
			'emailing_revenue_mid'         => (float) $data['emailing_revenue_mid'],
			'emailing_revenue_high'        => (float) $data['emailing_revenue_high'],
			'available_budget'             => (float) $data['available_budget'],
			'recommended_package'          => sanitize_text_field( $data['recommended_package'] ?? '' ),
			'recommended_package_price'    => (float) ( $data['recommended_package_price'] ?? 0 ),
			'recommended_package_real_pno' => (float) ( $data['recommended_package_real_pno'] ?? 0 ),
			'result_type'                  => sanitize_text_field( $data['result_type'] ),
			'consent_data'                 => (int) $data['consent_data'],
			'consent_marketing'            => (int) $data['consent_marketing'],
			'ip_address'                   => sanitize_text_field( $data['ip_address'] ?? '' ),
			'user_agent'                   => sanitize_text_field( $data['user_agent'] ?? '' ),
			'smartemailing_status'         => sanitize_text_field( $data['smartemailing_status'] ?? 'pending' ),
			'lead_status'                  => ECAlc_Lead_Status::CEKANI,
			'utm_source'                   => sanitize_text_field( $data['utm_source']   ?? '' ),
			'utm_medium'                   => sanitize_text_field( $data['utm_medium']   ?? '' ),
			'utm_campaign'                 => sanitize_text_field( $data['utm_campaign'] ?? '' ),
			'referrer'                     => sanitize_text_field( $data['referrer']     ?? '' ),
			'time_to_submit'               => isset( $data['time_to_submit'] ) ? (int) $data['time_to_submit'] : null,
		];

		$formats = [
			'%s', '%s', '%s', '%s', '%s', '%d', '%s', '%f', '%d',
			'%f', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%f',
			'%s', '%f', '%f', '%s', '%d', '%d', '%s', '%s', '%s', '%s',
			'%s', '%s', '%s', '%s', '%d',
		];

		$wpdb->insert( $this->table(), $row, $formats );

		if ( $wpdb->insert_id ) {
			return (int) $wpdb->insert_id;
		}

		// Race condition: duplicate email vložen souběžně – vrátíme ID existujícího záznamu
		$existing_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$this->table()} WHERE email = %s LIMIT 1",
			$row['email']
		) );
		return (int) $existing_id;
	}

	public function update_smartemailing_status( int $id, string $status, string $response = '' ): void {
		global $wpdb;
		$wpdb->update(
			$this->table(),
			[
				'smartemailing_status'         => $status,
				'smartemailing_last_response'  => $response,
				'smartemailing_last_attempt_at'=> current_time( 'mysql' ),
			],
			[ 'id' => $id ],
			[ '%s', '%s', '%s' ],
			[ '%d' ]
		);
	}

	public function get( int $id ): ?array {
		global $wpdb;
		$table = $this->table();
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
		return $row ?: null;
	}

	private function resolve_date_range( string $period, string $date_from = '', string $date_to = '' ): array {
		$now = new DateTime( 'now' );
		switch ( $period ) {
			case 'today':
				return [ 'start' => $now->format( 'Y-m-d 00:00:00' ), 'end' => $now->format( 'Y-m-d 23:59:59' ) ];
			case 'yesterday':
				$d = ( clone $now )->modify( '-1 day' );
				return [ 'start' => $d->format( 'Y-m-d 00:00:00' ), 'end' => $d->format( 'Y-m-d 23:59:59' ) ];
			case 'this_week':
				$s = ( clone $now )->modify( 'Monday this week' );
				$e = ( clone $now )->modify( 'Sunday this week' );
				return [ 'start' => $s->format( 'Y-m-d 00:00:00' ), 'end' => $e->format( 'Y-m-d 23:59:59' ) ];
			case 'last_week':
				$s = ( clone $now )->modify( 'Monday last week' );
				$e = ( clone $now )->modify( 'Sunday last week' );
				return [ 'start' => $s->format( 'Y-m-d 00:00:00' ), 'end' => $e->format( 'Y-m-d 23:59:59' ) ];
			case 'this_month':
				return [ 'start' => $now->format( 'Y-m-01 00:00:00' ), 'end' => $now->format( 'Y-m-t 23:59:59' ) ];
			case 'last_month':
				$d = ( clone $now )->modify( 'first day of last month' );
				return [ 'start' => $d->format( 'Y-m-01 00:00:00' ), 'end' => $d->format( 'Y-m-t 23:59:59' ) ];
			case 'this_year':
				return [ 'start' => $now->format( 'Y-01-01 00:00:00' ), 'end' => $now->format( 'Y-12-31 23:59:59' ) ];
			case 'last_year':
				$y = (int) $now->format( 'Y' ) - 1;
				return [ 'start' => "{$y}-01-01 00:00:00", 'end' => "{$y}-12-31 23:59:59" ];
			case 'custom':
				return [
					'start' => $date_from ? $date_from . ' 00:00:00' : null,
					'end'   => $date_to   ? $date_to   . ' 23:59:59' : null,
				];
			default:
				return [ 'start' => null, 'end' => null ];
		}
	}

	private function build_leads_where( array $args ): string {
		global $wpdb;
		$parts = [];

		$period = $args['date_period'] ?? '';
		if ( $period && $period !== 'all' ) {
			$range = $this->resolve_date_range( $period, $args['date_from'] ?? '', $args['date_to'] ?? '' );
			if ( ! empty( $range['start'] ) ) {
				$parts[] = $wpdb->prepare( 'created_at >= %s', $range['start'] );
			}
			if ( ! empty( $range['end'] ) ) {
				$parts[] = $wpdb->prepare( 'created_at <= %s', $range['end'] );
			}
		}

		if ( ! empty( $args['search'] ) ) {
			$raw = sanitize_text_field( $args['search'] );
			$q   = '%' . $wpdb->esc_like( $raw ) . '%';
			if ( is_numeric( $raw ) && (int) $raw > 0 ) {
				$parts[] = $wpdb->prepare(
					'(id = %d OR name LIKE %s OR email LIKE %s OR phone LIKE %s)',
					(int) $raw, $q, $q, $q
				);
			} else {
				$parts[] = $wpdb->prepare( '(name LIKE %s OR email LIKE %s OR phone LIKE %s)', $q, $q, $q );
			}
		}

		$lead_statuses = array_values( array_filter( array_map( 'sanitize_text_field', (array) ( $args['lead_status'] ?? [] ) ) ) );
		if ( ! empty( $lead_statuses ) ) {
			if ( count( $lead_statuses ) === 1 ) {
				$parts[] = $wpdb->prepare( 'lead_status = %s', $lead_statuses[0] );
			} else {
				$ph      = implode( ',', array_fill( 0, count( $lead_statuses ), '%s' ) );
				$parts[] = $wpdb->prepare( "lead_status IN ({$ph})", ...$lead_statuses );
			}
		}

		$rt_values = array_values( array_filter( array_map( 'sanitize_text_field', (array) ( $args['result_type'] ?? [] ) ) ) );
		if ( ! empty( $rt_values ) ) {
			if ( count( $rt_values ) === 1 ) {
				$parts[] = $wpdb->prepare( 'result_type = %s', $rt_values[0] );
			} else {
				$ph      = implode( ',', array_fill( 0, count( $rt_values ), '%s' ) );
				$parts[] = $wpdb->prepare( "result_type IN ({$ph})", ...$rt_values );
			}
		}

		if ( ! empty( $args['segment'] ) ) {
			$parts[] = $wpdb->prepare( 'segment = %s', sanitize_text_field( $args['segment'] ) );
		}

		if ( ! empty( $args['package'] ) ) {
			$parts[] = $wpdb->prepare( 'recommended_package = %s', sanitize_text_field( $args['package'] ) );
		}

		if ( ! empty( $args['cta_type'] ) ) {
			$parts[] = $wpdb->prepare( 'cta_type = %s', sanitize_text_field( $args['cta_type'] ) );
		}

		$booking = $args['booking'] ?? '';
		if ( $booking === 'yes' ) {
			$parts[] = "booking_status = 'completed'";
		} elseif ( $booking === 'no' ) {
			$parts[] = "(booking_status IS NULL OR booking_status != 'completed')";
		}

		$cta_clicked = isset( $args['cta_clicked'] ) ? (string) $args['cta_clicked'] : '';
		if ( $cta_clicked !== '' ) {
			$parts[] = $wpdb->prepare( 'cta_clicked = %d', (int) $cta_clicked );
		}

		return empty( $parts ) ? 'WHERE 1=1' : 'WHERE ' . implode( ' AND ', $parts );
	}

	public function get_list( array $args = [] ): array {
		global $wpdb;
		$table = $this->table();

		$where   = $this->build_leads_where( $args );
		$orderby = in_array( $args['orderby'] ?? '', self::SORTABLE_COLUMNS, true ) ? $args['orderby'] : 'created_at';
		$order   = strtoupper( $args['order'] ?? 'DESC' ) === 'ASC' ? 'ASC' : 'DESC';

		$limit = '';
		if ( ! empty( $args['per_page'] ) ) {
			$page   = max( 1, (int) ( $args['page'] ?? 1 ) );
			$offset = ( $page - 1 ) * (int) $args['per_page'];
			$limit  = $wpdb->prepare( 'LIMIT %d OFFSET %d', (int) $args['per_page'], $offset );
		}

		return $wpdb->get_results(
			"SELECT * FROM {$table} {$where} ORDER BY {$orderby} {$order} {$limit}",
			ARRAY_A
		) ?: [];
	}

	public function count( array $args = [] ): int {
		global $wpdb;
		$table = $this->table();
		$where = $this->build_leads_where( $args );
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where}" );
	}

	public function delete( int $id ): void {
		global $wpdb;
		$wpdb->delete( $this->table(), [ 'id' => $id ], [ '%d' ] );
	}

	public function delete_multiple( array $ids ): int {
		global $wpdb;
		$ids = array_map( 'intval', $ids );
		if ( empty( $ids ) ) return 0;
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		return (int) $wpdb->query(
			$wpdb->prepare( "DELETE FROM {$this->table()} WHERE id IN ({$placeholders})", ...$ids )
		);
	}

	public function get_by_ids( array $ids ): array {
		global $wpdb;
		$ids = array_map( 'intval', $ids );
		if ( empty( $ids ) ) return [];
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$this->table()} WHERE id IN ({$placeholders}) ORDER BY created_at DESC", ...$ids ),
			ARRAY_A
		) ?: [];
	}

	public function get_all_for_export( array $args = [] ): array {
		return $this->get_list( $args );
	}

	public function update_lead_status( int $id, string $status ): bool {
		if ( ! ECAlc_Lead_Status::is_valid( $status ) ) {
			return false;
		}
		global $wpdb;
		$rows = $wpdb->update(
			$this->table(),
			[ 'lead_status' => $status ],
			[ 'id' => $id ],
			[ '%s' ],
			[ '%d' ]
		);
		if ( $rows === false ) {
			return false;
		}
		if ( $rows > 0 ) {
			do_action( 'ecalc_lead_status_changed', $id, $status );
		}
		return true;
	}

	public function update_booking_status( int $id, string $status ): void {
		global $wpdb;
		$allowed = [ 'opened', 'completed', 'declined' ];
		if ( ! in_array( $status, $allowed, true ) ) return;
		$wpdb->update(
			$this->table(),
			[ 'booking_status' => $status ],
			[ 'id' => $id ],
			[ '%s' ],
			[ '%d' ]
		);
	}

	public function record_cta_click( int $id, string $type, string $package_name = '' ): void {
		global $wpdb;
		$wpdb->update(
			$this->table(),
			[
				'cta_clicked'     => 1,
				'cta_type'        => $type,
				'cta_package_name'=> $package_name,
				'cta_at'          => current_time( 'mysql' ),
			],
			[ 'id' => $id ],
			[ '%d', '%s', '%s', '%s' ],
			[ '%d' ]
		);
	}

	public function save_phone( int $id, string $phone ): void {
		global $wpdb;
		$phone = substr( preg_replace( '/[^+\d\s\-()\.]/', '', sanitize_text_field( $phone ) ), 0, 30 );
		// Musí obsahovat 7–15 číslic, jinak nejde o platné telefonní číslo
		$digit_count = strlen( preg_replace( '/\D/', '', $phone ) );
		if ( $digit_count < 7 || $digit_count > 15 ) {
			return;
		}
		$wpdb->update(
			$this->table(),
			[ 'phone' => $phone ],
			[ 'id' => $id ],
			[ '%s' ],
			[ '%d' ]
		);
	}

	public function mark_followup_sent( int $id ): void {
		global $wpdb;
		$wpdb->update(
			$this->table(),
			[ 'followup_sent' => 1 ],
			[ 'id' => $id ],
			[ '%d' ],
			[ '%d' ]
		);
	}

	public function count_by_email_this_month( string $email ): int {
		global $wpdb;
		$table      = $this->table();
		$first_day  = date( 'Y-m-01 00:00:00' );
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE email = %s AND created_at >= %s",
				$email,
				$first_day
			)
		);
	}

	public function get_distinct_result_types(): array {
		global $wpdb;
		$table = $this->table();
		return $wpdb->get_col( "SELECT DISTINCT result_type FROM {$table} ORDER BY result_type" ) ?: [];
	}

	public function get_distinct_segments(): array {
		global $wpdb;
		$table = $this->table();
		return $wpdb->get_col( "SELECT DISTINCT segment FROM {$table} WHERE segment != '' ORDER BY segment" ) ?: [];
	}

	public function get_distinct_packages(): array {
		global $wpdb;
		$table = $this->table();
		return $wpdb->get_col( "SELECT DISTINCT recommended_package FROM {$table} WHERE recommended_package != '' ORDER BY recommended_package" ) ?: [];
	}

	public function get_by_email( string $email ): ?array {
		global $wpdb;
		$table = $this->table();
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE email = %s ORDER BY created_at DESC LIMIT 1",
				sanitize_email( $email )
			),
			ARRAY_A
		);
		return $row ?: null;
	}

	public function update_lead( int $id, array $data ): bool {
		global $wpdb;

		$current = $this->get( $id );
		if ( ! $current ) return false;

		$active_statuses = [ ECAlc_Lead_Status::POPTANO, ECAlc_Lead_Status::SCHUZKA, ECAlc_Lead_Status::ZOBCHODOVANO ];
		$current_status  = isset( $current['lead_status'] ) && $current['lead_status'] !== '' ? $current['lead_status'] : ECAlc_Lead_Status::CEKANI;
		$new_status      = in_array( $current_status, $active_statuses, true ) ? $current_status : ECAlc_Lead_Status::CEKANI;

		$wpdb->update(
			$this->table(),
			[
				'name'                         => sanitize_text_field( $data['name'] ),
				'shop_url'                     => esc_url_raw( $data['shop_url'] ),
				'segment'                      => sanitize_text_field( $data['segment'] ),
				'consumable_percentage'        => (int) $data['consumable_percentage'],
				'database_range'               => sanitize_text_field( $data['database_range'] ),
				'monthly_revenue'              => (float) $data['monthly_revenue'],
				'expected_pno'                 => (int) $data['expected_pno'],
				'consumable_score'             => (float) $data['consumable_score'],
				'database_score'               => (float) $data['database_score'],
				'segment_score'                => (float) $data['segment_score'],
				'total_score'                  => (float) $data['total_score'],
				'final_potential'              => (float) $data['final_potential'],
				'emailing_revenue_low'         => (float) $data['emailing_revenue_low'],
				'emailing_revenue_mid'         => (float) $data['emailing_revenue_mid'],
				'emailing_revenue_high'        => (float) $data['emailing_revenue_high'],
				'available_budget'             => (float) $data['available_budget'],
				'recommended_package'          => sanitize_text_field( $data['recommended_package'] ?? '' ),
				'recommended_package_price'    => (float) ( $data['recommended_package_price'] ?? 0 ),
				'recommended_package_real_pno' => (float) ( $data['recommended_package_real_pno'] ?? 0 ),
				'result_type'                  => sanitize_text_field( $data['result_type'] ),
				'consent_data'                 => (int) $data['consent_data'],
				'consent_marketing'            => (int) $data['consent_marketing'],
				'ip_address'                   => sanitize_text_field( $data['ip_address'] ?? '' ),
				'user_agent'                   => sanitize_text_field( $data['user_agent'] ?? '' ),
				'lead_status'                  => $new_status,
			],
			[ 'id' => $id ],
			[
				'%s', '%s', '%s', '%d', '%s', '%f', '%d',
				'%f', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%f',
				'%s', '%f', '%f', '%s', '%d', '%d', '%s', '%s',
				'%s',
			],
			[ '%d' ]
		);
		return true;
	}

	private function log_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'emailing_calculator_log';
	}

	public function log_change( int $lead_id, string $change_type, string $note ): void {
		global $wpdb;
		$wpdb->insert(
			$this->log_table(),
			[
				'lead_id'     => $lead_id,
				'changed_at'  => current_time( 'mysql' ),
				'change_type' => $change_type,
				'note'        => $note,
			],
			[ '%d', '%s', '%s', '%s' ]
		);
	}

	public function get_changelog( int $lead_id ): array {
		global $wpdb;
		$table = $this->log_table();
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE lead_id = %d ORDER BY changed_at DESC", $lead_id ),
			ARRAY_A
		) ?: [];
	}

	public function get_changelogs_by_ids( array $ids ): array {
		global $wpdb;
		$ids = array_map( 'intval', $ids );
		if ( empty( $ids ) ) return [];
		$table        = $this->log_table();
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$rows         = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE lead_id IN ({$placeholders}) ORDER BY changed_at ASC", ...$ids ),
			ARRAY_A
		) ?: [];
		$by_lead = [];
		foreach ( $rows as $row ) {
			$by_lead[ (int) $row['lead_id'] ][] = $row;
		}
		return $by_lead;
	}
}
