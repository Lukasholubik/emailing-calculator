<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ECAlc_Calculator {

	private ECAlc_Settings $settings;

	public function __construct( ECAlc_Settings $settings ) {
		$this->settings = $settings;
	}

	public function calculate( array $input ): array {
		$cfg = $this->settings->get_settings();

		$monthly_revenue        = (float) $input['monthly_revenue'];
		$consumable_percentage  = (float) $input['consumable_percentage'];
		$database_range_name    = (string) $input['database_range'];
		$segment_name           = (string) $input['segment'];
		$expected_pno           = (float) $input['expected_pno'];

		$consumable_weight = (float) $cfg['consumable_weight'] / 100;
		$database_weight   = (float) $cfg['database_weight'] / 100;
		$segment_weight    = (float) $cfg['segment_weight'] / 100;

		$consumable_score = ecalc_clamp( $consumable_percentage / 100, 0.0, 1.0 );
		$database_score   = $this->get_database_score( $database_range_name );
		$segment_score    = $this->get_segment_score( $segment_name );

		$total_score = ( $consumable_score * $consumable_weight )
		             + ( $database_score   * $database_weight )
		             + ( $segment_score    * $segment_weight );

		$min_potential = (float) $cfg['min_potential'];
		$max_potential = (float) $cfg['max_potential'];

		$final_potential = ecalc_clamp(
			$min_potential + ( ( $max_potential - $min_potential ) * $total_score ),
			$min_potential,
			$max_potential
		);

		$emailing_revenue_mid  = $monthly_revenue * ( $final_potential / 100 );
		$emailing_revenue_low  = $emailing_revenue_mid * (float) $cfg['conservative_multiplier'];
		$emailing_revenue_high = $emailing_revenue_mid * (float) $cfg['optimistic_multiplier'];

		$available_budget = $emailing_revenue_mid * ( $expected_pno / 100 );

		$result_type     = $this->get_result_type( $available_budget, $cfg );
		$packages        = $this->build_packages_data( $emailing_revenue_mid, $expected_pno, $result_type );
		$recommended_pkg = $this->get_recommended_package( $packages );

		$recommended_name     = $recommended_pkg ? $recommended_pkg['name']     : '';
		$recommended_price    = $recommended_pkg ? (float) $recommended_pkg['price'] : 0.0;
		$recommended_real_pno = $recommended_pkg ? (float) $recommended_pkg['real_pno'] : 0.0;

		$arguments = $this->build_arguments( $result_type, $input, $consumable_score, $database_score, $segment_score, [
			'final_potential'       => $final_potential,
			'emailing_revenue_low'  => $emailing_revenue_low,
			'emailing_revenue_mid'  => $emailing_revenue_mid,
			'emailing_revenue_high' => $emailing_revenue_high,
		] );

		return [
			'consumable_score'             => round( $consumable_score, 4 ),
			'database_score'               => round( $database_score, 4 ),
			'segment_score'                => round( $segment_score, 4 ),
			'total_score'                  => round( $total_score, 4 ),
			'final_potential'              => round( $final_potential, 4 ),
			'emailing_revenue_low'         => round( $emailing_revenue_low, 2 ),
			'emailing_revenue_mid'         => round( $emailing_revenue_mid, 2 ),
			'emailing_revenue_high'        => round( $emailing_revenue_high, 2 ),
			'available_budget'             => round( $available_budget, 2 ),
			'result_type'                  => $result_type,
			'packages'                     => $packages,
			'recommended_package'          => $recommended_pkg,
			'recommended_package_name'     => $recommended_name,
			'recommended_package_price'    => $recommended_price,
			'recommended_package_real_pno' => $recommended_real_pno,
			'arguments'                    => $arguments,
		];
	}

	private function get_database_score( string $range_name ): float {
		$ranges = $this->settings->get_database_ranges();
		foreach ( $ranges as $range ) {
			if ( (int) $range['active'] && $range['name'] === $range_name ) {
				return (float) $range['score'];
			}
		}
		return 0.0;
	}

	private function get_segment_score( string $segment_name ): float {
		$segments = $this->settings->get_segments();
		foreach ( $segments as $segment ) {
			if ( (int) $segment['active'] && $segment['name'] === $segment_name ) {
				return (float) $segment['score'];
			}
		}
		return 0.50;
	}

	private function get_result_type( float $available_budget, array $cfg ): string {
		$low        = (float) $cfg['low_potential_threshold'];
		$borderline = (float) $cfg['borderline_threshold'];

		if ( $available_budget < $low ) {
			return 'low_potential';
		}
		if ( $available_budget < $borderline ) {
			return 'borderline';
		}

		$packages = $this->settings->get_packages();
		$active   = array_filter( $packages, fn( $p ) => (int) $p['active'] );
		usort( $active, fn( $a, $b ) => (float) $a['price'] <=> (float) $b['price'] );

		$recommended = null;
		foreach ( $active as $pkg ) {
			if ( (float) $pkg['price'] <= $available_budget ) {
				$recommended = $pkg;
			}
		}

		if ( ! $recommended ) {
			return 'borderline';
		}

		usort( $active, fn( $a, $b ) => (int) $a['order'] <=> (int) $b['order'] );
		$first = reset( $active );

		if ( $recommended['id'] === $first['id'] ) {
			return 'package_1';
		}
		return 'package_n';
	}

	private function build_packages_data( float $emailing_revenue_mid, float $expected_pno, string $result_type ): array {
		$packages = $this->settings->get_packages();
		$active   = array_filter( $packages, fn( $p ) => (int) $p['active'] );
		usort( $active, fn( $a, $b ) => (int) $a['order'] <=> (int) $b['order'] );

		$recommended_id = $this->determine_recommended_id( $result_type, $active );

		$result = [];
		foreach ( $active as $pkg ) {
			$price    = (float) $pkg['price'];
			$real_pno = $emailing_revenue_mid > 0
				? round( ( $price / $emailing_revenue_mid ) * 100, 2 )
				: 0.0;

			$fits_pno = $real_pno <= $expected_pno;

			$result[] = [
				'id'             => $pkg['id'],
				'name'           => $pkg['name'],
				'price'          => $price,
				'description'    => $pkg['description'],
				'items'          => $pkg['items'] ?? [],
				'result_text'    => $pkg['result_text'] ?? '',
				'real_pno'       => $real_pno,
				'fits_pno'       => $fits_pno,
				'is_recommended' => ( $pkg['id'] == $recommended_id ),
			];
		}
		return $result;
	}

	private function determine_recommended_id( string $result_type, array $active_packages ): ?int {
		if ( in_array( $result_type, [ 'low_potential', 'borderline' ], true ) ) {
			return null;
		}

		$by_price = $active_packages;
		usort( $by_price, fn( $a, $b ) => (float) $a['price'] <=> (float) $b['price'] );

		if ( $result_type === 'package_1' ) {
			$first = reset( $by_price );
			return $first ? (int) $first['id'] : null;
		}

		$last = end( $by_price );
		return $last ? (int) $last['id'] : null;
	}

	private function get_recommended_package( array $packages ): ?array {
		foreach ( $packages as $pkg ) {
			if ( $pkg['is_recommended'] ) {
				return $pkg;
			}
		}
		return null;
	}

	private function build_arguments( string $result_type, array $input, float $consumable_score, float $database_score, float $segment_score, array $revenue_data ): array {
		if ( ! in_array( $result_type, [ 'package_1', 'package_n', 'borderline' ], true ) ) {
			return [];
		}

		$cfg = $this->settings->get_arguments();
		if ( empty( $cfg['enabled'] ) ) {
			return [];
		}

		$placeholder_data = array_merge( $input, $revenue_data );

		$consumable_band = $this->score_band( $consumable_score, $cfg );
		$database_band   = $this->score_band( $database_score, $cfg );
		$segment_band    = $this->score_band( $segment_score, $cfg );

		$items = array_filter( [
			ecalc_replace_variables( $cfg[ 'consumable_' . $consumable_band ] ?? '', $placeholder_data ),
			ecalc_replace_variables( $cfg[ 'database_' . $database_band ] ?? '', $placeholder_data ),
			ecalc_replace_variables( $cfg[ 'segment_' . $segment_band ] ?? '', $placeholder_data ),
		] );

		return [
			'title'    => $cfg['title'] ?? '',
			'subtitle' => $cfg['subtitle'] ?? '',
			'items'    => array_values( $items ),
			'summary'  => ecalc_replace_variables( $cfg['summary'] ?? '', $placeholder_data ),
		];
	}

	private function score_band( float $score, array $cfg ): string {
		if ( $score >= (float) $cfg['threshold_high'] ) {
			return 'high';
		}
		if ( $score >= (float) $cfg['threshold_medium'] ) {
			return 'medium';
		}
		return 'low';
	}
}
