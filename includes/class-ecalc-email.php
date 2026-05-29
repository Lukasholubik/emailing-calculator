<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ECAlc_Email {

	private ECAlc_Settings $settings;

	public function __construct( ECAlc_Settings $settings ) {
		$this->settings = $settings;
	}

	public function send_admin_notification( array $lead_data, int $lead_id, bool $is_recalculation = false ): void {
		$cfg = $this->settings->get_notifications();

		if ( ! $cfg['admin_enabled'] ) {
			return;
		}

		$to      = sanitize_email( $cfg['admin_email'] );
		$subject = ecalc_replace_variables( $cfg['admin_subject'], $lead_data );
		$body    = ecalc_replace_variables( $cfg['admin_body'], $lead_data );

		if ( $is_recalculation ) {
			$subject = '[Přepočet] ' . $subject;
			$body    = "⚠️ Tento kontakt již v databázi existuje a provedl nový výpočet. Klientský e-mail nebyl odeslán.\n\n" . $body;
		}

		$detail_url = admin_url( 'admin.php?page=ecalc_leads&action=view&id=' . $lead_id );
		$body      .= "\n\nDetail leadu: " . $detail_url;

		$headers = [ 'Content-Type: text/plain; charset=UTF-8' ];

		wp_mail( $to, $subject, $body, $headers );
	}

	private function prepend_phone_block( string $body, string $phone ): string {
		if ( $phone === '' ) {
			return $body;
		}
		$line  = str_repeat( '=', 42 );
		$block = $line . "\n"
			. '📞  TELEFON: ' . $phone . "\n"
			. $line . "\n\n";
		return $block . $body;
	}

	private function build_email_data( array $lead ): array {
		return array_merge( $lead, [
			'inquiry_package' => $lead['cta_package_name'] ?? '',
		] );
	}

	private function send( string $to, string $subject, string $body ): void {
		wp_mail( sanitize_email( $to ), $subject, $body, [ 'Content-Type: text/plain; charset=UTF-8' ] );
	}

	public function send_followup_email( array $lead, array $cfg ): void {
		$data    = $this->build_email_data( $lead );
		$subject = ecalc_replace_variables( $cfg['trigger_followup_subject'] ?? '', $data );
		$body    = ecalc_replace_variables( $cfg['trigger_followup_body'] ?? '', $data );
		$this->send( $lead['email'], $subject, $body );
	}

	public function send_inquiry_admin( array $lead, array $cfg ): void {
		$data    = $this->build_email_data( $lead );
		$to      = $cfg['admin_email'] ?? get_option( 'admin_email' );
		$subject = ecalc_replace_variables( $cfg['trigger_inquiry_admin_subject'] ?? '', $data );
		$body    = ecalc_replace_variables( $cfg['trigger_inquiry_admin_body'] ?? '', $data );
		$body    = $this->prepend_phone_block( $body, $lead['phone'] ?? '' );
		$this->send( $to, $subject, $body );
	}

	public function send_inquiry_client( array $lead, array $cfg ): void {
		$data    = $this->build_email_data( $lead );
		$subject = ecalc_replace_variables( $cfg['trigger_inquiry_client_subject'] ?? '', $data );
		$body    = ecalc_replace_variables( $cfg['trigger_inquiry_client_body'] ?? '', $data );
		$this->send( $lead['email'], $subject, $body );
	}

	public function send_consultation_admin( array $lead, array $cfg ): void {
		$data    = $this->build_email_data( $lead );
		$to      = $cfg['admin_email'] ?? get_option( 'admin_email' );
		$subject = ecalc_replace_variables( $cfg['trigger_consultation_admin_subject'] ?? '', $data );
		$body    = ecalc_replace_variables( $cfg['trigger_consultation_admin_body'] ?? '', $data );
		$body    = $this->prepend_phone_block( $body, $lead['phone'] ?? '' );
		$this->send( $to, $subject, $body );
	}

	public function send_consultation_client( array $lead, array $cfg ): void {
		$data    = $this->build_email_data( $lead );
		$subject = ecalc_replace_variables( $cfg['trigger_consultation_client_subject'] ?? '', $data );
		$body    = ecalc_replace_variables( $cfg['trigger_consultation_client_body'] ?? '', $data );
		$this->send( $lead['email'], $subject, $body );
	}

	public function send_client_email( array $lead_data ): void {
		$cfg = $this->settings->get_notifications();

		if ( ! $cfg['client_enabled'] ) {
			return;
		}

		$to      = sanitize_email( $lead_data['email'] );
		$subject = ecalc_replace_variables( $cfg['client_subject'], $lead_data );
		$body    = ecalc_replace_variables( $cfg['client_body'], $lead_data );
		$headers = [ 'Content-Type: text/plain; charset=UTF-8' ];

		wp_mail( $to, $subject, $body, $headers );
	}
}
