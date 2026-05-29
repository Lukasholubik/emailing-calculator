<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ECAlc_Cron {

	private ECAlc_Settings $settings;
	private ECAlc_Leads    $leads;
	private ECAlc_Email    $email;

	public function __construct( ECAlc_Settings $settings, ECAlc_Leads $leads, ECAlc_Email $email ) {
		$this->settings = $settings;
		$this->leads    = $leads;
		$this->email    = $email;
	}

	public function register(): void {
		add_action( 'ecalc_lead_saved',   [ $this, 'maybe_schedule_followup' ] );
		add_action( 'ecalc_send_followup', [ $this, 'send_followup' ] );
	}

	public function maybe_schedule_followup( int $lead_id ): void {
		$cfg = $this->settings->get_notifications();
		if ( empty( $cfg['trigger_followup_enabled'] ) ) {
			return;
		}
		$hours = max( 1, (int) ( $cfg['trigger_followup_delay_hours'] ?? 24 ) );
		wp_schedule_single_event( time() + $hours * 3600, 'ecalc_send_followup', [ $lead_id ] );
	}

	public function send_followup( int $lead_id ): void {
		$lead = $this->leads->get( $lead_id );
		if ( ! $lead ) return;
		if ( (int) $lead['cta_clicked'] || (int) $lead['followup_sent'] ) return;

		$cfg = $this->settings->get_notifications();
		if ( empty( $cfg['trigger_followup_enabled'] ) ) return;

		$this->email->send_followup_email( $lead, $cfg );
		$this->leads->mark_followup_sent( $lead_id );

		// Přechod do stavu Neaktivní (pouze pokud stále v Čekání)
		if ( ( $lead['lead_status'] ?? '' ) === ECAlc_Lead_Status::CEKANI ) {
			$this->leads->update_lead_status( $lead_id, ECAlc_Lead_Status::NEAKTIVNI );
		}
	}
}
