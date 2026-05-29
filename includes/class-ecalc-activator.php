<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ECAlc_Activator {

	public static function activate(): void {
		self::create_leads_table();
		flush_rewrite_rules();
	}

	public static function deactivate(): void {
		flush_rewrite_rules();
	}

	public static function create_leads_table(): void {
		global $wpdb;

		$table  = $wpdb->prefix . 'emailing_calculator_leads';
		$collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			name varchar(255) NOT NULL DEFAULT '',
			email varchar(255) NOT NULL DEFAULT '',
			shop_url varchar(500) NOT NULL DEFAULT '',
			segment varchar(255) NOT NULL DEFAULT '',
			consumable_percentage tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
			database_range varchar(100) NOT NULL DEFAULT '',
			monthly_revenue decimal(15,2) NOT NULL DEFAULT 0.00,
			expected_pno tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
			consumable_score decimal(6,4) NOT NULL DEFAULT 0.0000,
			database_score decimal(6,4) NOT NULL DEFAULT 0.0000,
			segment_score decimal(6,4) NOT NULL DEFAULT 0.0000,
			total_score decimal(6,4) NOT NULL DEFAULT 0.0000,
			final_potential decimal(8,4) NOT NULL DEFAULT 0.0000,
			emailing_revenue_low decimal(15,2) NOT NULL DEFAULT 0.00,
			emailing_revenue_mid decimal(15,2) NOT NULL DEFAULT 0.00,
			emailing_revenue_high decimal(15,2) NOT NULL DEFAULT 0.00,
			available_budget decimal(15,2) NOT NULL DEFAULT 0.00,
			recommended_package varchar(255) NOT NULL DEFAULT '',
			recommended_package_price decimal(15,2) NOT NULL DEFAULT 0.00,
			recommended_package_real_pno decimal(8,4) NOT NULL DEFAULT 0.0000,
			result_type varchar(50) NOT NULL DEFAULT '',
			consent_data tinyint(1) NOT NULL DEFAULT 0,
			consent_marketing tinyint(1) NOT NULL DEFAULT 0,
			ip_address varchar(45) NOT NULL DEFAULT '',
			user_agent varchar(500) NOT NULL DEFAULT '',
			smartemailing_status varchar(50) NOT NULL DEFAULT 'pending',
			smartemailing_last_response text DEFAULT NULL,
			smartemailing_last_attempt_at datetime DEFAULT NULL,
			cta_clicked tinyint(1) NOT NULL DEFAULT 0,
			cta_type varchar(50) NOT NULL DEFAULT '',
			cta_package_name varchar(255) NOT NULL DEFAULT '',
			cta_at datetime DEFAULT NULL,
			followup_sent tinyint(1) NOT NULL DEFAULT 0,
			booking_status varchar(30) NOT NULL DEFAULT '',
			lead_status varchar(30) NOT NULL DEFAULT 'cekani',
			phone varchar(30) DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY email (email(100)),
			KEY result_type (result_type),
			KEY created_at (created_at)
		) {$collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Migrace: převod regular KEY email → UNIQUE KEY (ochrana proti race condition)
		$index_info = $wpdb->get_row( $wpdb->prepare(
			"SELECT Non_unique FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND INDEX_NAME = 'email' LIMIT 1",
			$table
		) );
		if ( $index_info && (int) $index_info->Non_unique === 1 ) {
			// Smazat případné duplicity (ponechat nejnovější záznam)
			$wpdb->query( "DELETE a FROM {$table} a INNER JOIN {$table} b ON a.email = b.email AND a.id < b.id" );
			// Převést na UNIQUE
			$wpdb->query( "ALTER TABLE {$table} DROP INDEX email, ADD UNIQUE KEY email (email(100))" );
		}

		// Explicit column migration – dbDelta sometimes misses columns on existing tables
		$existing = $wpdb->get_col( "SHOW COLUMNS FROM {$table}", 0 );
		$add_columns = [
			'cta_clicked'      => "ALTER TABLE {$table} ADD COLUMN cta_clicked tinyint(1) NOT NULL DEFAULT 0",
			'cta_type'         => "ALTER TABLE {$table} ADD COLUMN cta_type varchar(50) NOT NULL DEFAULT ''",
			'cta_package_name' => "ALTER TABLE {$table} ADD COLUMN cta_package_name varchar(255) NOT NULL DEFAULT ''",
			'cta_at'           => "ALTER TABLE {$table} ADD COLUMN cta_at datetime DEFAULT NULL",
			'followup_sent'    => "ALTER TABLE {$table} ADD COLUMN followup_sent tinyint(1) NOT NULL DEFAULT 0",
			'booking_status'   => "ALTER TABLE {$table} ADD COLUMN booking_status varchar(30) NOT NULL DEFAULT ''",
			'lead_status'      => "ALTER TABLE {$table} ADD COLUMN lead_status varchar(30) NOT NULL DEFAULT 'cekani'",
			'phone'            => "ALTER TABLE {$table} ADD COLUMN phone varchar(30) DEFAULT NULL",
			'time_to_submit'   => "ALTER TABLE {$table} ADD COLUMN time_to_submit int(11) UNSIGNED DEFAULT NULL",
			'utm_source'       => "ALTER TABLE {$table} ADD COLUMN utm_source varchar(100) NOT NULL DEFAULT ''",
			'utm_medium'       => "ALTER TABLE {$table} ADD COLUMN utm_medium varchar(100) NOT NULL DEFAULT ''",
			'utm_campaign'     => "ALTER TABLE {$table} ADD COLUMN utm_campaign varchar(100) NOT NULL DEFAULT ''",
			'referrer'         => "ALTER TABLE {$table} ADD COLUMN referrer varchar(500) NOT NULL DEFAULT ''",
		];
		foreach ( $add_columns as $col => $alter_sql ) {
			if ( ! in_array( $col, $existing, true ) ) {
				$wpdb->query( $alter_sql );
			}
		}

		// Migrace: oprava Cyrilického 'а' (U+0430) → latinské 'a' v lead_status
		$cyrillic_schuzka = "schuzk\xD0\xB0";
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET lead_status = 'schuzka' WHERE lead_status = %s",
				$cyrillic_schuzka
			)
		);

		$log_table = $wpdb->prefix . 'emailing_calculator_log';
		$log_sql   = "CREATE TABLE IF NOT EXISTS {$log_table} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			lead_id bigint(20) UNSIGNED NOT NULL,
			changed_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			change_type varchar(50) NOT NULL DEFAULT '',
			note text DEFAULT NULL,
			PRIMARY KEY (id),
			KEY lead_id (lead_id),
			KEY changed_at (changed_at)
		) {$collate};";

		dbDelta( $log_sql );

		update_option( 'ecalc_db_version', ECALC_VERSION );
	}
}
