<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ecalc_format_currency( float $amount ): string {
	return number_format( $amount, 0, ',', ' ' ) . ' Kč';
}

function ecalc_format_pct( float $value, int $decimals = 2 ): string {
	return number_format( $value, $decimals, ',', ' ' ) . ' %';
}

function ecalc_clamp( float $value, float $min, float $max ): float {
	return max( $min, min( $max, $value ) );
}

function ecalc_get_client_ip(): string {
	// Používáme pouze REMOTE_ADDR – HTTP_CLIENT_IP a HTTP_X_FORWARDED_FOR jsou
	// uživatelsky ovlivnitelné hlavičky a nesmí se používat pro bezpečnostní rozhodnutí.
	return sanitize_text_field( trim( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) ) );
}

function ecalc_get_user_agent(): string {
	return sanitize_text_field( (string) ( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );
}

function ecalc_replace_variables( string $text, array $data ): string {
	// Pro plain-text emaily používáme wp_strip_all_tags() místo esc_html(),
	// aby se v textu nezobrazovaly HTML entity (např. &lt; místo <).
	$s = fn( $v ) => wp_strip_all_tags( (string) $v );
	$map = [
		'{name}'                          => $s( $data['name'] ?? '' ),
		'{email}'                         => $s( $data['email'] ?? '' ),
		'{shop_url}'                      => $s( $data['shop_url'] ?? '' ),
		'{segment}'                       => $s( $data['segment'] ?? '' ),
		'{monthly_revenue}'               => ecalc_format_currency( (float) ( $data['monthly_revenue'] ?? 0 ) ),
		'{consumable_percentage}'         => ( (int) ( $data['consumable_percentage'] ?? 0 ) ) . ' %',
		'{database_range}'                => $s( $data['database_range'] ?? '' ),
		'{expected_pno}'                  => ( (int) ( $data['expected_pno'] ?? 0 ) ) . ' %',
		'{final_potential}'               => ecalc_format_pct( (float) ( $data['final_potential'] ?? 0 ) ),
		'{emailing_revenue_low}'          => ecalc_format_currency( (float) ( $data['emailing_revenue_low'] ?? 0 ) ),
		'{emailing_revenue_mid}'          => ecalc_format_currency( (float) ( $data['emailing_revenue_mid'] ?? 0 ) ),
		'{emailing_revenue_high}'         => ecalc_format_currency( (float) ( $data['emailing_revenue_high'] ?? 0 ) ),
		'{available_budget}'              => ecalc_format_currency( (float) ( $data['available_budget'] ?? 0 ) ),
		'{recommended_package}'           => $s( $data['recommended_package'] ?? '' ),
		'{recommended_package_price}'     => ecalc_format_currency( (float) ( $data['recommended_package_price'] ?? 0 ) ),
		'{recommended_package_real_pno}'  => ecalc_format_pct( (float) ( $data['recommended_package_real_pno'] ?? 0 ) ),
		'{inquiry_package}'               => $s( $data['inquiry_package'] ?? '' ),
		'{phone}'                         => $s( $data['phone'] ?? '' ),
	];
	return str_replace( array_keys( $map ), array_values( $map ), $text );
}

function ecalc_sanitize_phone( string $phone ): string {
	return substr( preg_replace( '/[^+\d\s\-()\.]/', '', sanitize_text_field( $phone ) ), 0, 30 );
}

function ecalc_is_valid_phone( string $phone ): bool {
	$digit_count = strlen( preg_replace( '/\D/', '', $phone ) );
	return $digit_count >= 7 && $digit_count <= 15;
}

function ecalc_sanitize_css_value( string $value ): string {
	// Odstraní znaky umožňující vyskočení z CSS property value kontextu.
	$value = trim( $value );
	$value = preg_replace( '/[;{}"\'\\\\]/', '', $value );
	return sanitize_text_field( $value );
}
