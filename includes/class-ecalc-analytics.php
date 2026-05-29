<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ECAlc_Analytics {

	private string $leads_table;
	private string $log_table;

	public function __construct() {
		global $wpdb;
		$this->leads_table = $wpdb->prefix . 'emailing_calculator_leads';
		$this->log_table   = $wpdb->prefix . 'emailing_calculator_log';
	}

	// -------------------------------------------------------------------------
	// Public API
	// -------------------------------------------------------------------------

	public function get_data( array $params ): array {
		$period      = sanitize_text_field( $params['period']             ?? 'this_month' );
		$date_from   = sanitize_text_field( $params['date_from']          ?? '' );
		$date_to     = sanitize_text_field( $params['date_to']            ?? '' );
		$granularity = sanitize_text_field( $params['granularity']        ?? '' );
		$filters     = [
			'f_segment'  => sanitize_text_field( $params['filter_segment']     ?? '' ),
			'f_status'   => sanitize_text_field( $params['filter_status']      ?? '' ),
			'f_result'   => sanitize_text_field( $params['filter_result_type'] ?? '' ),
			'f_package'  => sanitize_text_field( $params['filter_package']     ?? '' ),
			'f_cta_type' => sanitize_text_field( $params['filter_cta_type']    ?? '' ),
		];

		if ( ! $granularity ) {
			$granularity = self::default_granularity( $period );
		}

		[ 'start' => $start, 'end' => $end, 'end_display' => $end_display ] = $this->resolve_period( $period, $date_from, $date_to );
		[ 'start' => $prev_start, 'end' => $prev_end ] = $this->get_comparison_period( $start, $end );

		$series = $this->get_time_series( $start, $end, $end_display, $granularity, $filters );

		$summary  = $this->get_summary( $start, $end, $filters );
		$prev_sum = $this->get_summary( $prev_start, $prev_end, $filters );

		$total_views = $this->get_views_total( $start, $end );
		$prev_views  = $this->get_views_total( $prev_start, $prev_end );
		$session     = $this->get_avg_session_time( $start, $end );

		$summary['total_views']       = $total_views;
		$summary['conversion_rate']   = $total_views > 0 ? round( $summary['leads_count'] / $total_views * 100, 1 ) : 0;
		$summary['avg_session_time']  = $session['avg_s'];
		$summary['avg_scroll_pct']    = $session['avg_scroll_pct'];
		$summary['avg_time_to_submit']= $this->get_avg_time_to_submit( $start, $end, $filters );
		$prev_sum['total_views']      = $prev_views;

		return [
			'summary'            => $summary,
			'prev_sum'           => $prev_sum,
			'series'             => $series,
			'prediction_series'  => $this->get_prediction_series( $end, $granularity, $series ),
			'statuses'           => $this->get_status_breakdown( $start, $end, $filters ),
			'prev_statuses'      => $this->get_status_breakdown( $prev_start, $prev_end, $filters ),
			'results'            => $this->get_result_breakdown( $start, $end, $filters ),
			'prev_results'       => $this->get_result_breakdown( $prev_start, $prev_end, $filters ),
			'segments'           => $this->get_segment_breakdown( $start, $end, $filters ),
			'prev_segments'      => $this->get_segment_breakdown( $prev_start, $prev_end, $filters ),
			'db_ranges'          => $this->get_database_range_breakdown( $start, $end, $filters ),
			'prev_db_ranges'     => $this->get_database_range_breakdown( $prev_start, $prev_end, $filters ),
			'traffic_sources'    => $this->get_traffic_source_breakdown( $start, $end, $filters ),
			'abandonment_steps'  => $this->get_abandonment_breakdown( $start, $end ),
			'filter_opts'        => $this->get_filter_options(),
			'granularity'        => $granularity,
		];
	}

	// -------------------------------------------------------------------------
	// Period resolution
	// -------------------------------------------------------------------------

	private function resolve_period( string $period, string $date_from, string $date_to ): array {
		$tz  = wp_timezone();
		$now = new DateTime( 'now', $tz );

		$end_display = null; // full period end including future days (for label generation)

		switch ( $period ) {
			case 'this_week':
				$dow         = (int) $now->format( 'N' ); // 1=Mon … 7=Sun
				$start       = ( clone $now )->modify( "-{$dow} days +1 day" )->setTime( 0, 0, 0 );
				$end         = ( clone $now )->setTime( 23, 59, 59 );
				$end_display = ( clone $start )->modify( '+6 days' )->setTime( 23, 59, 59 );
				break;
			case 'last_week':
				$dow   = (int) $now->format( 'N' );
				$end   = ( clone $now )->modify( "-{$dow} days" )->setTime( 23, 59, 59 );
				$start = ( clone $end )->modify( '-6 days' )->setTime( 0, 0, 0 );
				break;
			case 'this_month':
				$start       = new DateTime( $now->format( 'Y-m-01' ), $tz );
				$start->setTime( 0, 0, 0 );
				$end         = ( clone $now )->setTime( 23, 59, 59 );
				$end_display = new DateTime( $now->format( 'Y-m-t' ), $tz ); // 't' = last day of month
				$end_display->setTime( 23, 59, 59 );
				break;
			case 'last_month':
				$first = new DateTime( $now->format( 'Y-m-01' ), $tz );
				$end   = ( clone $first )->modify( '-1 day' )->setTime( 23, 59, 59 );
				$start = new DateTime( $end->format( 'Y-m-01' ), $tz );
				$start->setTime( 0, 0, 0 );
				break;
			case 'this_year':
				$start       = new DateTime( $now->format( 'Y' ) . '-01-01', $tz );
				$start->setTime( 0, 0, 0 );
				$end         = ( clone $now )->setTime( 23, 59, 59 );
				$end_display = new DateTime( $now->format( 'Y' ) . '-12-31', $tz );
				$end_display->setTime( 23, 59, 59 );
				break;
			case 'last_year':
				$y     = (int) $now->format( 'Y' ) - 1;
				$start = ( new DateTime( "{$y}-01-01", $tz ) )->setTime( 0, 0, 0 );
				$end   = ( new DateTime( "{$y}-12-31", $tz ) )->setTime( 23, 59, 59 );
				break;
			case 'custom':
				if ( $date_from && $date_to ) {
					$start = ( new DateTime( $date_from, $tz ) )->setTime( 0, 0, 0 );
					$end   = ( new DateTime( $date_to,   $tz ) )->setTime( 23, 59, 59 );
				} else {
					$start = new DateTime( $now->format( 'Y-m-01' ), $tz );
					$start->setTime( 0, 0, 0 );
					$end   = ( clone $now )->setTime( 23, 59, 59 );
				}
				break;
			case 'all':
			default:
				return [ 'start' => null, 'end' => null, 'end_display' => null ];
		}

		return [ 'start' => $start, 'end' => $end, 'end_display' => $end_display ?? $end ];
	}

	private function get_comparison_period( ?DateTime $start, ?DateTime $end ): array {
		if ( $start === null || $end === null ) {
			return [ 'start' => null, 'end' => null ];
		}

		$interval   = $start->diff( $end );
		$prev_end   = ( clone $start )->modify( '-1 second' );
		$prev_start = ( clone $prev_end )->sub( $interval );

		return [ 'start' => $prev_start, 'end' => $prev_end ];
	}

	// -------------------------------------------------------------------------
	// WHERE clause builders
	// -------------------------------------------------------------------------

	private function lead_where( ?DateTime $start, ?DateTime $end, array $filters, string $alias = '' ): array {
		$p          = $alias ? $alias . '.' : '';
		$conditions = [];
		$values     = [];

		if ( $start ) {
			$conditions[] = "{$p}created_at >= %s";
			$values[]     = $start->format( 'Y-m-d H:i:s' );
		}
		if ( $end ) {
			$conditions[] = "{$p}created_at <= %s";
			$values[]     = $end->format( 'Y-m-d H:i:s' );
		}
		if ( ! empty( $filters['f_segment'] ) ) {
			$conditions[] = "{$p}segment = %s";
			$values[]     = $filters['f_segment'];
		}
		if ( ! empty( $filters['f_status'] ) ) {
			$conditions[] = "{$p}lead_status = %s";
			$values[]     = $filters['f_status'];
		}
		if ( ! empty( $filters['f_result'] ) ) {
			$conditions[] = "{$p}result_type = %s";
			$values[]     = $filters['f_result'];
		}
		if ( ! empty( $filters['f_package'] ) ) {
			$conditions[] = "{$p}recommended_package = %s";
			$values[]     = $filters['f_package'];
		}
		if ( ! empty( $filters['f_cta_type'] ) ) {
			$conditions[] = "{$p}cta_type = %s";
			$values[]     = $filters['f_cta_type'];
		}

		$sql = $conditions ? 'WHERE ' . implode( ' AND ', $conditions ) : '';
		return [ 'sql' => $sql, 'values' => $values ];
	}

	private function log_where( ?DateTime $start, ?DateTime $end, array $filters ): array {
		$conditions = [ "lg.change_type = 'recalculation'" ];
		$values     = [];

		if ( $start ) {
			$conditions[] = 'lg.changed_at >= %s';
			$values[]     = $start->format( 'Y-m-d H:i:s' );
		}
		if ( $end ) {
			$conditions[] = 'lg.changed_at <= %s';
			$values[]     = $end->format( 'Y-m-d H:i:s' );
		}

		$need_join = ! empty( $filters['f_segment'] ) || ! empty( $filters['f_status'] )
		          || ! empty( $filters['f_result'] )  || ! empty( $filters['f_package'] )
		          || ! empty( $filters['f_cta_type'] );
		$join      = '';

		if ( $need_join ) {
			$join = "INNER JOIN {$this->leads_table} l ON lg.lead_id = l.id";
			if ( ! empty( $filters['f_segment'] ) )  { $conditions[] = 'l.segment = %s';             $values[] = $filters['f_segment']; }
			if ( ! empty( $filters['f_status'] ) )   { $conditions[] = 'l.lead_status = %s';         $values[] = $filters['f_status']; }
			if ( ! empty( $filters['f_result'] ) )   { $conditions[] = 'l.result_type = %s';         $values[] = $filters['f_result']; }
			if ( ! empty( $filters['f_package'] ) )  { $conditions[] = 'l.recommended_package = %s'; $values[] = $filters['f_package']; }
			if ( ! empty( $filters['f_cta_type'] ) ) { $conditions[] = 'l.cta_type = %s';            $values[] = $filters['f_cta_type']; }
		}

		$sql = 'WHERE ' . implode( ' AND ', $conditions );
		return [ 'sql' => $sql, 'values' => $values, 'join' => $join ];
	}

	// -------------------------------------------------------------------------
	// Safe WHERE builders (no full-query prepare – avoids DATE_FORMAT conflict)
	// -------------------------------------------------------------------------

	private function build_safe_where( ?DateTime $start, ?DateTime $end, array $filters, string $alias = '' ): string {
		global $wpdb;
		$p     = $alias ? $alias . '.' : '';
		$parts = [];

		if ( $start ) $parts[] = $wpdb->prepare( "{$p}created_at >= %s", $start->format( 'Y-m-d H:i:s' ) );
		if ( $end )   $parts[] = $wpdb->prepare( "{$p}created_at <= %s", $end->format( 'Y-m-d H:i:s' ) );
		if ( ! empty( $filters['f_segment'] ) ) $parts[] = $wpdb->prepare( "{$p}segment = %s",             $filters['f_segment'] );
		if ( ! empty( $filters['f_status']  ) ) $parts[] = $wpdb->prepare( "{$p}lead_status = %s",         $filters['f_status'] );
		if ( ! empty( $filters['f_result']  ) ) $parts[] = $wpdb->prepare( "{$p}result_type = %s",         $filters['f_result'] );
		if ( ! empty( $filters['f_package']   ) ) $parts[] = $wpdb->prepare( "{$p}recommended_package = %s", $filters['f_package'] );
		if ( ! empty( $filters['f_cta_type'] ) ) $parts[] = $wpdb->prepare( "{$p}cta_type = %s",             $filters['f_cta_type'] );

		return $parts ? 'WHERE ' . implode( ' AND ', $parts ) : '';
	}

	private function build_safe_log_where( ?DateTime $start, ?DateTime $end, array $filters ): array {
		global $wpdb;
		$parts = [ "lg.change_type = 'recalculation'" ];

		if ( $start ) $parts[] = $wpdb->prepare( 'lg.changed_at >= %s', $start->format( 'Y-m-d H:i:s' ) );
		if ( $end )   $parts[] = $wpdb->prepare( 'lg.changed_at <= %s', $end->format( 'Y-m-d H:i:s' ) );

		$need_join = ! empty( $filters['f_segment'] ) || ! empty( $filters['f_status'] )
		          || ! empty( $filters['f_result'] )  || ! empty( $filters['f_package'] )
		          || ! empty( $filters['f_cta_type'] );
		$join      = '';

		if ( $need_join ) {
			$join = "INNER JOIN {$this->leads_table} l ON lg.lead_id = l.id";
			if ( ! empty( $filters['f_segment'] )  ) $parts[] = $wpdb->prepare( 'l.segment = %s',             $filters['f_segment'] );
			if ( ! empty( $filters['f_status']   ) ) $parts[] = $wpdb->prepare( 'l.lead_status = %s',         $filters['f_status'] );
			if ( ! empty( $filters['f_result']   ) ) $parts[] = $wpdb->prepare( 'l.result_type = %s',         $filters['f_result'] );
			if ( ! empty( $filters['f_package']  ) ) $parts[] = $wpdb->prepare( 'l.recommended_package = %s', $filters['f_package'] );
			if ( ! empty( $filters['f_cta_type'] ) ) $parts[] = $wpdb->prepare( 'l.cta_type = %s',            $filters['f_cta_type'] );
		}

		return [ 'where' => 'WHERE ' . implode( ' AND ', $parts ), 'join' => $join ];
	}

	// -------------------------------------------------------------------------
	// Queries
	// -------------------------------------------------------------------------

	private function get_summary( ?DateTime $start, ?DateTime $end, array $filters ): array {
		global $wpdb;

		$w = $this->lead_where( $start, $end, $filters, 'l' );

		$sql = "SELECT
			COUNT(*) AS leads_count,
			SUM(l.cta_clicked) AS cta_clicks,
			SUM(CASE WHEN l.booking_status = 'completed' THEN 1 ELSE 0 END) AS bookings,
			SUM(CASE WHEN l.cta_type = 'consultation' THEN 1 ELSE 0 END) AS consultations,
			SUM(CASE WHEN l.cta_type = 'package' THEN 1 ELSE 0 END) AS inquiries,
			AVG(NULLIF(l.final_potential, 0)) AS avg_potential,
			AVG(NULLIF(l.monthly_revenue, 0)) AS avg_revenue,
			AVG(CASE
				WHEN l.cta_type = 'package' AND l.recommended_package_price > 0 THEN l.recommended_package_price
				WHEN l.cta_type = 'consultation' OR l.booking_status = 'completed' THEN 2000
				ELSE NULL
			END) AS avg_lead_value
			FROM {$this->leads_table} l {$w['sql']}";

		$row = $w['values']
			? $wpdb->get_row( $wpdb->prepare( $sql, $w['values'] ), ARRAY_A )
			: $wpdb->get_row( $sql, ARRAY_A );

		$lw = $this->log_where( $start, $end, $filters );
		$log_sql = "SELECT COUNT(*) FROM {$this->log_table} lg {$lw['join']} {$lw['sql']}";
		$recalculations = (int) ( $lw['values']
			? $wpdb->get_var( $wpdb->prepare( $log_sql, $lw['values'] ) )
			: $wpdb->get_var( $log_sql ) );

		return [
			'leads_count'    => (int) ( $row['leads_count']    ?? 0 ),
			'cta_clicks'     => (int) ( $row['cta_clicks']     ?? 0 ),
			'bookings'       => (int) ( $row['bookings']       ?? 0 ),
			'consultations'  => (int) ( $row['consultations']  ?? 0 ),
			'inquiries'      => (int) ( $row['inquiries']      ?? 0 ),
			'recalculations' => $recalculations,
			'avg_potential'  => round( (float) ( $row['avg_potential']  ?? 0 ), 1 ),
			'avg_revenue'    => round( (float) ( $row['avg_revenue']    ?? 0 ) ),
			'avg_lead_value' => round( (float) ( $row['avg_lead_value'] ?? 0 ) ),
		];
	}

	private function get_time_series( ?DateTime $start, ?DateTime $end, ?DateTime $end_display, string $granularity, array $filters ): array {
		global $wpdb;

		switch ( $granularity ) {
			case 'week':
				$grp_expr = 'YEARWEEK(l.created_at, 1)';
				$log_grp  = 'YEARWEEK(lg.changed_at, 1)';
				break;
			case 'month':
				$grp_expr = "DATE_FORMAT(l.created_at, '%Y-%m')";
				$log_grp  = "DATE_FORMAT(lg.changed_at, '%Y-%m')";
				break;
			case 'year':
				$grp_expr = 'YEAR(l.created_at)';
				$log_grp  = 'YEAR(lg.changed_at)';
				break;
			default: // day
				$grp_expr = 'DATE(l.created_at)';
				$log_grp  = 'DATE(lg.changed_at)';
				break;
		}

		// Pre-escaped WHERE – no full-query prepare(), so DATE_FORMAT % chars are safe.
		$where = $this->build_safe_where( $start, $end, $filters, 'l' );
		$log_w = $this->build_safe_log_where( $start, $end, $filters );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results(
			"SELECT {$grp_expr} AS grp,
				COUNT(*) AS leads_count,
				SUM(l.cta_clicked) AS cta_clicks,
				SUM(CASE WHEN l.booking_status = 'completed' THEN 1 ELSE 0 END) AS bookings,
				SUM(CASE WHEN l.cta_type = 'package' THEN 1 ELSE 0 END) AS inquiries
			FROM {$this->leads_table} l {$where}
			GROUP BY {$grp_expr} ORDER BY {$grp_expr} ASC",
			ARRAY_A
		);

		$log_rows = $wpdb->get_results(
			"SELECT {$log_grp} AS grp, COUNT(*) AS cnt
			FROM {$this->log_table} lg {$log_w['join']} {$log_w['where']}
			GROUP BY {$log_grp} ORDER BY {$log_grp} ASC",
			ARRAY_A
		);
		// phpcs:enable

		// Index actual query results by grp key.
		$data_map   = [];
		foreach ( $rows as $row ) {
			$data_map[ (string) $row['grp'] ] = $row;
		}
		$recalc_map = [];
		foreach ( $log_rows as $lr ) {
			$recalc_map[ (string) $lr['grp'] ] = (int) $lr['cnt'];
		}

		// Build a complete date range including future days within the period.
		$range = $this->generate_period_labels( $start, $end_display ?? $end, $granularity );

		$labels = $new_leads = $cta_clicks = $bookings = $inquiries = $recalculations = [];
		foreach ( $range as $bucket ) {
			$key              = (string) $bucket['key'];
			$row              = $data_map[ $key ] ?? null;
			$labels[]         = $bucket['label'];
			$new_leads[]      = $row ? (int) $row['leads_count'] : 0;
			$cta_clicks[]     = $row ? (int) $row['cta_clicks']  : 0;
			$bookings[]       = $row ? (int) $row['bookings']     : 0;
			$inquiries[]      = $row ? (int) $row['inquiries']    : 0;
			$recalculations[] = $recalc_map[ $key ] ?? 0;
		}

		return compact( 'labels', 'new_leads', 'cta_clicks', 'bookings', 'inquiries', 'recalculations' );
	}

	private function generate_period_labels( ?DateTime $start, ?DateTime $end, string $granularity ): array {
		if ( ! $start || ! $end ) {
			return [];
		}

		$tz      = wp_timezone();
		$buckets = [];

		switch ( $granularity ) {
			case 'week':
				$cur = ( clone $start )->setTimezone( $tz );
				// Snap to Monday of the week containing $start.
				$dow = (int) $cur->format( 'N' ); // 1=Mon
				if ( $dow > 1 ) {
					$cur->modify( '-' . ( $dow - 1 ) . ' days' );
				}
				$cur->setTime( 0, 0, 0 );
				while ( $cur <= $end ) {
					$key      = (int) ( $cur->format( 'o' ) . str_pad( $cur->format( 'W' ), 2, '0', STR_PAD_LEFT ) );
					$buckets[] = [ 'key' => $key, 'label' => $cur->format( 'd.m' ) ];
					$cur->modify( '+7 days' );
				}
				break;

			case 'month':
				$cur = new DateTime( $start->format( 'Y-m-01' ), $tz );
				$cur->setTime( 0, 0, 0 );
				while ( $cur <= $end ) {
					$buckets[] = [ 'key' => $cur->format( 'Y-m' ), 'label' => $cur->format( 'm/Y' ) ];
					$cur->modify( '+1 month' );
				}
				break;

			case 'year':
				$y_start = (int) $start->format( 'Y' );
				$y_end   = (int) $end->format( 'Y' );
				for ( $y = $y_start; $y <= $y_end; $y++ ) {
					$buckets[] = [ 'key' => $y, 'label' => (string) $y ];
				}
				break;

			default: // day
				$cur = ( clone $start )->setTimezone( $tz )->setTime( 0, 0, 0 );
				while ( $cur <= $end ) {
					$buckets[] = [ 'key' => $cur->format( 'Y-m-d' ), 'label' => $cur->format( 'd.m' ) ];
					$cur->modify( '+1 day' );
				}
				break;
		}

		return $buckets;
	}

	private function get_status_breakdown( ?DateTime $start, ?DateTime $end, array $filters ): array {
		global $wpdb;

		$w   = $this->lead_where( $start, $end, $filters );
		$sql = "SELECT lead_status, COUNT(*) AS cnt FROM {$this->leads_table} {$w['sql']} GROUP BY lead_status ORDER BY cnt DESC";

		$rows = $w['values']
			? $wpdb->get_results( $wpdb->prepare( $sql, $w['values'] ), ARRAY_A )
			: $wpdb->get_results( $sql, ARRAY_A );

		return array_map( function ( $row ) {
			return [
				'status' => $row['lead_status'],
				'label'  => ECAlc_Lead_Status::label( $row['lead_status'] ),
				'color'  => ECAlc_Lead_Status::color( $row['lead_status'] ),
				'count'  => (int) $row['cnt'],
			];
		}, $rows );
	}

	private function get_result_breakdown( ?DateTime $start, ?DateTime $end, array $filters ): array {
		global $wpdb;

		$labels = [
			'low_potential' => 'Nízký potenciál',
			'borderline'    => 'Hraniční potenciál',
			'package_1'     => 'Balíček 1',
			'package_n'     => 'Vyšší balíček',
		];

		$w   = $this->lead_where( $start, $end, $filters );
		$sql = "SELECT result_type, COUNT(*) AS cnt FROM {$this->leads_table} {$w['sql']} GROUP BY result_type ORDER BY cnt DESC";

		$rows = $w['values']
			? $wpdb->get_results( $wpdb->prepare( $sql, $w['values'] ), ARRAY_A )
			: $wpdb->get_results( $sql, ARRAY_A );

		return array_map( function ( $row ) use ( $labels ) {
			return [
				'result_type' => $row['result_type'],
				'label'       => $labels[ $row['result_type'] ] ?? $row['result_type'],
				'count'       => (int) $row['cnt'],
			];
		}, $rows );
	}

	private function get_segment_breakdown( ?DateTime $start, ?DateTime $end, array $filters ): array {
		global $wpdb;

		$w   = $this->lead_where( $start, $end, $filters );
		$sql = "SELECT segment, COUNT(*) AS cnt FROM {$this->leads_table} {$w['sql']} GROUP BY segment ORDER BY cnt DESC LIMIT 10";

		$rows = $w['values']
			? $wpdb->get_results( $wpdb->prepare( $sql, $w['values'] ), ARRAY_A )
			: $wpdb->get_results( $sql, ARRAY_A );

		return array_map( function ( $row ) {
			return [ 'segment' => $row['segment'], 'count' => (int) $row['cnt'] ];
		}, $rows );
	}

	private function extrapolate( array $values, int $n ): array {
		$count = count( $values );
		if ( $count === 0 ) return array_fill( 0, $n, 0 );
		if ( $count === 1 ) return array_fill( 0, $n, $values[0] );

		$sum_x = 0; $sum_y = 0.0; $sum_xy = 0.0; $sum_x2 = 0;
		for ( $i = 0; $i < $count; $i++ ) {
			$sum_x  += $i;
			$sum_y  += (float) $values[ $i ];
			$sum_xy += $i * (float) $values[ $i ];
			$sum_x2 += $i * $i;
		}

		$denom = $count * $sum_x2 - $sum_x * $sum_x;
		if ( $denom == 0 ) {
			return array_fill( 0, $n, round( $sum_y / $count, 1 ) );
		}

		$slope     = ( $count * $sum_xy - $sum_x * $sum_y ) / $denom;
		$intercept = ( $sum_y - $slope * $sum_x ) / $count;

		$result = [];
		for ( $i = $count; $i < $count + $n; $i++ ) {
			$result[] = round( max( 0.0, $intercept + $slope * $i ), 1 );
		}
		return $result;
	}

	private function get_prediction_series( ?DateTime $end, string $granularity, array $curr_series ): array {
		if ( ! $end || empty( $curr_series['labels'] ) ) {
			return [];
		}

		$n   = count( $curr_series['labels'] );
		$tz  = wp_timezone();

		switch ( $granularity ) {
			case 'week':  $step = '+7 days';  $fmt = 'd.m'; break;
			case 'month': $step = '+1 month'; $fmt = 'm/Y'; break;
			case 'year':  $step = '+1 year';  $fmt = 'Y';   break;
			default:      $step = '+1 day';   $fmt = 'd.m'; break;
		}

		$cur    = ( clone $end )->setTimezone( $tz );
		$cur->modify( $step );
		$labels = [];
		for ( $i = 0; $i < $n; $i++ ) {
			$labels[] = $cur->format( $fmt );
			$cur->modify( $step );
		}

		return [
			'labels'         => $labels,
			'new_leads'      => $this->extrapolate( $curr_series['new_leads'],      $n ),
			'cta_clicks'     => $this->extrapolate( $curr_series['cta_clicks'],     $n ),
			'bookings'       => $this->extrapolate( $curr_series['bookings'],       $n ),
			'inquiries'      => $this->extrapolate( $curr_series['inquiries'],      $n ),
			'recalculations' => $this->extrapolate( $curr_series['recalculations'], $n ),
		];
	}

	public static function default_granularity( string $period ): string {
		switch ( $period ) {
			case 'this_week':
			case 'last_week':
				return 'day';
			case 'this_year':
			case 'last_year':
			case 'all':
				return 'month';
			default: // this_month, last_month, custom
				return 'week';
		}
	}

	private function get_database_range_breakdown( ?DateTime $start, ?DateTime $end, array $filters ): array {
		global $wpdb;

		$where      = $this->build_safe_where( $start, $end, $filters );
		$extra      = "database_range IS NOT NULL AND database_range != ''";
		$full_where = $where ? $where . ' AND ' . $extra : 'WHERE ' . $extra;

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results(
			"SELECT database_range AS db_range, COUNT(*) AS cnt
			FROM {$this->leads_table} {$full_where}
			GROUP BY database_range ORDER BY cnt DESC LIMIT 10",
			ARRAY_A
		);
		// phpcs:enable

		return array_map( function ( $row ) {
			return [
				'db_range' => $row['db_range'],
				'label'    => $row['db_range'],
				'count'    => (int) $row['cnt'],
			];
		}, $rows );
	}

	private function get_avg_time_to_submit( ?DateTime $start, ?DateTime $end, array $filters ): int {
		global $wpdb;
		$where = $this->build_safe_where( $start, $end, $filters );
		$extra = 'time_to_submit IS NOT NULL AND time_to_submit > 0';
		$full  = $where ? $where . ' AND ' . $extra : 'WHERE ' . $extra;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$avg = $wpdb->get_var( "SELECT AVG(time_to_submit) FROM {$this->leads_table} {$full}" );
		// phpcs:enable
		return (int) round( (float) $avg );
	}

	private function get_avg_session_time( ?DateTime $start, ?DateTime $end ): array {
		$raw = get_option( 'ecalc_session_times_daily', [] );
		if ( ! is_array( $raw ) ) return [ 'count' => 0, 'avg_s' => 0, 'avg_scroll_pct' => 0 ];
		$total_count = 0; $total_s = 0; $total_scroll = 0; $scroll_count = 0;
		foreach ( $raw as $date => $entry ) {
			$dt = DateTime::createFromFormat( 'Y-m-d', $date );
			if ( ! $dt ) continue;
			if ( $start && $dt < $start ) continue;
			if ( $end   && $dt > $end   ) continue;
			$total_count  += (int) ( $entry['count']   ?? 0 );
			$total_s      += (int) ( $entry['total_s'] ?? 0 );
			if ( ! empty( $entry['scroll_count'] ) ) {
				$scroll_count += (int) $entry['scroll_count'];
				$total_scroll += (int) ( $entry['total_scroll'] ?? 0 );
			}
		}
		return [
			'count'          => $total_count,
			'avg_s'          => $total_count > 0 ? (int) round( $total_s / $total_count ) : 0,
			'avg_scroll_pct' => $scroll_count > 0 ? (int) round( $total_scroll / $scroll_count ) : 0,
		];
	}

	private function get_abandonment_breakdown( ?DateTime $start, ?DateTime $end ): array {
		$raw = get_option( 'ecalc_abandonment_steps', [] );
		if ( ! is_array( $raw ) ) return [];
		$totals = [];
		foreach ( $raw as $date => $steps ) {
			$dt = DateTime::createFromFormat( 'Y-m-d', $date );
			if ( ! $dt ) continue;
			if ( $start && $dt < $start ) continue;
			if ( $end   && $dt > $end   ) continue;
			foreach ( $steps as $step => $count ) {
				$totals[ $step ] = ( $totals[ $step ] ?? 0 ) + (int) $count;
			}
		}
		$labels = [
			'initial'    => 'Jen prohlédl (bez interakce)',
			'name'       => 'Jméno',
			'email'      => 'E-mail',
			'shop_url'   => 'URL e-shopu',
			'segment'    => 'Segment',
			'database'   => 'Velikost databáze',
			'revenue'    => 'Obrat',
			'consumable' => 'Spotřební zboží',
			'pno'        => 'PNO',
			'consent'    => 'Souhlas',
		];
		$result = [];
		foreach ( array_keys( $labels ) as $step ) {
			if ( ! empty( $totals[ $step ] ) ) {
				$result[] = [ 'step' => $step, 'label' => $labels[ $step ], 'count' => $totals[ $step ] ];
			}
		}
		return $result;
	}

	private function get_views_total( ?DateTime $start, ?DateTime $end ): int {
		$raw = get_option( 'ecalc_views_daily', [] );
		if ( ! is_array( $raw ) ) return 0;
		$total = 0;
		foreach ( $raw as $date => $count ) {
			$dt = DateTime::createFromFormat( 'Y-m-d', $date );
			if ( ! $dt ) continue;
			if ( $start && $dt < $start ) continue;
			if ( $end   && $dt > $end   ) continue;
			$total += (int) $count;
		}
		return $total;
	}

	private function get_traffic_source_breakdown( ?DateTime $start, ?DateTime $end, array $filters ): array {
		global $wpdb;

		$where = $this->build_safe_where( $start, $end, $filters );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results(
			"SELECT utm_medium, utm_source, referrer, COUNT(*) AS cnt
			FROM {$this->leads_table} {$where}
			GROUP BY utm_medium, utm_source, referrer",
			ARRAY_A
		);
		// phpcs:enable

		if ( ! $rows ) {
			return [];
		}

		$paid_mediums    = [ 'cpc', 'ppc', 'paid', 'cpm', 'paid_social', 'paidsearch' ];
		$search_engines  = [ 'google', 'bing', 'yahoo', 'seznam', 'duckduckgo', 'ecosia', 'yandex' ];
		$paid = $organic = $direct = $other = 0;

		foreach ( $rows as $row ) {
			$count  = (int) $row['cnt'];
			$medium = strtolower( trim( $row['utm_medium'] ?? '' ) );
			$source = strtolower( trim( $row['utm_source'] ?? '' ) );
			$ref    = strtolower( trim( $row['referrer']   ?? '' ) );

			if ( $medium !== '' && in_array( $medium, $paid_mediums, true ) ) {
				$paid += $count;
			} elseif ( $medium === 'organic' ) {
				$organic += $count;
			} elseif ( $medium === '' && $source === '' && $ref === '' ) {
				$direct += $count;
			} elseif ( $ref !== '' && array_reduce( $search_engines, fn( $carry, $e ) => $carry || strpos( $ref, $e ) !== false, false ) ) {
				$organic += $count;
			} else {
				$other += $count;
			}
		}

		return [
			[ 'source' => 'paid',    'label' => 'Placená reklama', 'count' => $paid ],
			[ 'source' => 'organic', 'label' => 'Organické',       'count' => $organic ],
			[ 'source' => 'direct',  'label' => 'Přímá návštěva',  'count' => $direct ],
			[ 'source' => 'other',   'label' => 'Ostatní',         'count' => $other ],
		];
	}

	private function get_filter_options(): array {
		global $wpdb;

		$segments = $wpdb->get_col( "SELECT DISTINCT segment FROM {$this->leads_table} WHERE segment != '' ORDER BY segment ASC" );
		$packages = $wpdb->get_col( "SELECT DISTINCT recommended_package FROM {$this->leads_table} WHERE recommended_package != '' ORDER BY recommended_package ASC" );

		return [
			'segments' => array_values( $segments ),
			'packages' => array_values( $packages ),
			'statuses' => ECAlc_Lead_Status::all(),
			'results'  => [
				'low_potential' => 'Nízký potenciál',
				'borderline'    => 'Hraniční potenciál',
				'package_1'     => 'Balíček 1',
				'package_n'     => 'Vyšší balíček',
			],
		];
	}
}
