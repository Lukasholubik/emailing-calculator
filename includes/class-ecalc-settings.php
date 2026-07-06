<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ECAlc_Settings {

	public function get_settings(): array {
		$defaults = [
			'min_potential'              => 15,
			'max_potential'              => 45,
			'consumable_weight'          => 70,
			'database_weight'            => 20,
			'segment_weight'             => 10,
			'conservative_multiplier'    => 0.85,
			'optimistic_multiplier'      => 1.15,
			'low_potential_threshold'    => 10000,
			'borderline_threshold'       => 15000,
			'cta_text'                   => 'Chci konzultaci zdarma',
			'cta_url'                    => '',
			'marketing_consent_required' => 1,
			'monthly_limit'              => 5,
			'booking_url'                => '',
			'booking_modal_title'        => 'Vyberte termín konzultace',
			'booking_confirm_question'   => 'Provedli jste rezervaci termínu?',
			'booking_confirm_yes'        => '✓ Ano, zarezervoval jsem',
			'booking_confirm_no'         => 'Ne, zavřít',
			'booking_yes_message'        => 'Skvělé! Brzy se vám ozveme s potvrzením. Těšíme se na setkání.',
			'booking_no_message'         => 'Nevadí – pro rezervaci termínu se na nás kdykoliv obraťte.',
			// Duplicitní email – dialog
			'duplicate_title'    => 'Již vás máme v databázi',
			'duplicate_msg'      => 'Kontakt s tímto e-mailem již existuje v naší databázi (stav: {status}).',
			'duplicate_question' => 'Chcete provést nový výpočet? Aktuální záznam bude aktualizován.',
			'duplicate_confirm'  => 'Ano, provést nový výpočet',
			'duplicate_cancel'   => 'Zrušit',
			// Banner o aktualizaci záznamu
			'update_banner'      => 'Váš záznam byl aktualizován na základě nového výpočtu.',
			// Dialog – telefonní číslo
			'phone_dialog_title'  => 'Zanechte nám telefonní číslo',
			'phone_dialog_desc'   => 'Pro rychlejší komunikaci nám můžete zanechat telefonní číslo.',
			'phone_dialog_submit' => 'Pokračovat',
			'phone_dialog_skip'   => 'Přeskočit',
			'phone_dialog_error'  => 'Zadejte platné telefonní číslo (7–15 číslic).',
			// Mikrocopy pod tlačítkem formuláře
			'form_submit_note'     => 'Zdarma · Bez závazků · Výsledek za pár vteřin',
			// Doplňkový text u CTA "Konzultace zdarma"
			'cta_consultation_note' => '30 min – projdeme vaše čísla a návrh strategie',
			// Poděkování po poptání balíčku
			'inquiry_title'     => 'Děkujeme za zájem!',
			'inquiry_pkg_label' => 'Poptáváte balíček:',
			'inquiry_msg'       => 'Vaše poptávka byla odeslána. Ozveme se vám do 24 hodin a probereme detaily spolupráce.',
			'inquiry_close'     => 'Zavřít',
			'inquiry_visit'     => 'Přejít na web',
			// Reference / sociální důkaz (např. Trustindex shortcode)
			'social_proof_shortcode'      => '[trustindex no-registration=google]',
			// Výchozí vypnuto – plný recenzní slider je nad tlačítkem formuláře příliš rušivý,
			// zapnout jen pokud je k dispozici kompaktní verze widgetu (jen hvězdičky/počet).
			'social_proof_enabled_form'   => 0,
			'social_proof_enabled_result' => 1,
			// Reálné PNO balíčku nad rámec zadaného PNO – neutrální formulace
			'pno_over_label' => 'Nad vaším zadaným PNO',
		];
		$saved = get_option( 'ecalc_settings', [] );
		return wp_parse_args( $saved, $defaults );
	}

	public function save_settings( array $data ): void {
		update_option( 'ecalc_settings', $data );
	}

	public function get_segments(): array {
		$saved = get_option( 'ecalc_segments', null );
		if ( $saved === null ) {
			return $this->default_segments();
		}
		return $saved;
	}

	public function save_segments( array $data ): void {
		update_option( 'ecalc_segments', $data );
	}

	public function get_database_ranges(): array {
		$saved = get_option( 'ecalc_database_ranges', null );
		if ( $saved === null ) {
			return $this->default_database_ranges();
		}
		return $saved;
	}

	public function save_database_ranges( array $data ): void {
		update_option( 'ecalc_database_ranges', $data );
	}

	public function get_revenue_ranges(): array {
		$saved = get_option( 'ecalc_revenue_ranges', null );
		if ( $saved === null ) {
			return $this->default_revenue_ranges();
		}
		return $saved;
	}

	public function save_revenue_ranges( array $data ): void {
		update_option( 'ecalc_revenue_ranges', $data );
	}

	public function get_packages(): array {
		$saved = get_option( 'ecalc_packages', null );
		if ( $saved === null ) {
			return $this->default_packages();
		}
		// Back-fill items on existing packages that have none (one-time migration)
		$defaults_by_id = [];
		foreach ( $this->default_packages() as $d ) {
			$defaults_by_id[ $d['id'] ] = $d;
		}
		$changed = false;
		foreach ( $saved as &$pkg ) {
			if ( empty( $pkg['items'] ) && isset( $defaults_by_id[ $pkg['id'] ] ) ) {
				$pkg['items'] = $defaults_by_id[ $pkg['id'] ]['items'];
				$changed = true;
			}
		}
		unset( $pkg );
		if ( $changed ) {
			update_option( 'ecalc_packages', $saved );
		}
		return $saved;
	}

	public function save_packages( array $data ): void {
		update_option( 'ecalc_packages', $data );
	}

	public function get_result_texts(): array {
		$defaults = $this->default_result_texts();
		$saved    = get_option( 'ecalc_result_texts', [] );
		return wp_parse_args( $saved, $defaults );
	}

	public function save_result_texts( array $data ): void {
		update_option( 'ecalc_result_texts', $data );
	}

	public function get_arguments(): array {
		$defaults = $this->default_arguments();
		$saved    = get_option( 'ecalc_arguments', [] );
		return wp_parse_args( $saved, $defaults );
	}

	public function save_arguments( array $data ): void {
		update_option( 'ecalc_arguments', $data );
	}

	public function get_smartemailing(): array {
		$defaults = [
			'enabled'                            => 0,
			'username'                           => '',
			'api_key'                            => '',
			'list_id'                            => '',
			'default_tag'                        => 'emailing-kalkulacka',
			'status_tag_prefix'                  => 'lead-',
			'status_customfield_id'              => 0,
			'require_marketing_consent'          => 1,
			'send_low_potential_without_consent' => 0,
			// Mapování custom fields (ID v SmartEmailingu)
			'cf_segment'                         => 0,
			'cf_monthly_revenue'                 => 0,
			'cf_final_potential'                 => 0,
			'cf_emailing_mid'                    => 0,
			'cf_available_budget'                => 0,
			'cf_package'                         => 0,
			// Hodnoty pro výsledkové typy bez doporučeného balíčku
			'low_potential_se_value'             => 'Nízký potenciál',
		];
		$saved = get_option( 'ecalc_smartemailing', [] );
		return wp_parse_args( $saved, $defaults );
	}

	public function save_smartemailing( array $data ): void {
		update_option( 'ecalc_smartemailing', $data );
	}

	public function get_appearance(): array {
		$defaults = [
			// Základní
			'base_font_size'       => '15px',
			'base_text_color'      => '#1e293b',
			'muted_text_color'     => '#475569',
			'subtle_text_color'    => '#94a3b8',
			'primary_accent'       => '#4de5c4',

			// Nadpisy
			'h1_size'    => '1.6rem',
			'h1_color'   => '#1e293b',
			'h1_weight'  => '700',
			'h2_size'    => '1.45rem',
			'h2_color'   => '#1e293b',
			'h2_weight'  => '700',
			'h3_size'    => '1.1rem',
			'h3_color'   => '#1e293b',
			'h3_weight'  => '700',

			// Primární tlačítko (Vypočítat, Chci konzultaci)
			'btn_bg'           => '#4de5c4',
			'btn_bg_hover'     => '#ffffff',
			'btn_color'        => '#352830',
			'btn_color_hover'  => '#4de5c4',
			'btn_border'       => '#4de5c4',
			'btn_border_hover' => '#4de5c4',
			'btn_border_width' => '2px',
			'btn_font_size'    => '1rem',
			'btn_font_weight'  => '700',
			'btn_pad_y'        => '14px',
			'btn_pad_x'        => '32px',

			// Sekundární tlačítko (Poptat balíček – outline)
			'btn2_bg'           => 'transparent',
			'btn2_bg_hover'     => '#4de5c4',
			'btn2_color'        => '#4de5c4',
			'btn2_color_hover'  => '#352830',
			'btn2_border'       => '#4de5c4',
			'btn2_border_hover' => '#4de5c4',

			// Formulář
			'input_border'       => '#e2e8f0',
			'input_border_focus' => '#4de5c4',
			'input_bg'           => '#f8fafc',
			'label_color'        => '#1e293b',

			// Layout
			'dark_panel_bg'   => '#352830',
			'dark_panel_text' => '#ffffff',
			'border_radius'   => '12px',
			'card_bg'         => '#ffffff',
			'card_border'     => '#e2e8f0',
		];
		$saved = get_option( 'ecalc_appearance', [] );
		return wp_parse_args( $saved, $defaults );
	}

	public function save_appearance( array $data ): void {
		update_option( 'ecalc_appearance', $data );
	}

	public function get_info_panel(): array {
		$defaults = [
			'title' => 'Co kalkulačka spočítá?',
			'items' => [
				'Orientační potenciál emailingu pro váš e-shop',
				'Odhadovaný měsíční obrat z emailingu',
				'Doporučený rozpočet podle vámi zvoleného PNO',
				'Vhodný balíček služeb',
				'Reálné PNO pro každý balíček',
			],
			'note'  => 'Výsledky jsou orientační a závisí na zadaných údajích. Vždy doporučujeme detailní konzultaci.',
		];
		$saved = get_option( 'ecalc_info_panel', null );
		if ( $saved === null ) {
			return $defaults;
		}
		return wp_parse_args( $saved, $defaults );
	}

	public function save_info_panel( array $data ): void {
		update_option( 'ecalc_info_panel', $data );
	}

	public function get_notifications(): array {
		$defaults = [
			// Základní notifikace (po odeslání formuláře)
			'admin_enabled'  => 1,
			'admin_email'    => get_option( 'admin_email', '' ),
			'admin_subject'  => 'Nový lead z emailing kalkulačky – {name}',
			'admin_body'     => "Nový lead z emailing kalkulačky.\n\nJméno: {name}\nE-mail: {email}\nWeb: {shop_url}\nSegment: {segment}\nMěsíční obrat: {monthly_revenue}\nSpotřební zboží: {consumable_percentage}\nDatabáze: {database_range}\nOčekávané PNO: {expected_pno}\n\nPotenciál emailingu: {final_potential}\nOdhadovaný obrat z emailingu: {emailing_revenue_low} – {emailing_revenue_high}\nStřední scénář: {emailing_revenue_mid}\nDoporučený budget: {available_budget}\n\nDoporučený balíček: {recommended_package} ({recommended_package_price})\nReálné PNO balíčku: {recommended_package_real_pno}",
			'client_enabled' => 0,
			'client_subject' => 'Výsledek vaší emailing kalkulačky',
			'client_body'    => "Dobrý den, {name},\n\nděkujeme za vyplnění kalkulačky. Zde jsou vaše výsledky:\n\nOdhadovaný potenciál emailingu: {final_potential}\nStřední scénář obratu z emailingu: {emailing_revenue_mid}\nDoporučený balíček: {recommended_package}\n\nChcete se dozvědět více? Kontaktujte nás.",

			// Trigger 1: Follow-up – neklikl na žádné CTA
			'trigger_followup_enabled'       => 0,
			'trigger_followup_delay_hours'   => 24,
			'trigger_followup_subject'       => 'Vaše výsledky z emailing kalkulačky stále čekají',
			'trigger_followup_body'          => "Dobrý den, {name},\n\nspočítali jste si potenciál emailingu pro {shop_url} a výsledky jsou zajímavé.\n\nVaše výsledky:\n- Odhadovaný potenciál: {final_potential}\n- Odhadovaný obrat z emailingu: {emailing_revenue_mid}\n- Doporučený balíček: {recommended_package}\n\nJeště jste si nenaplánovali konzultaci. Rádi vám poradíme – stačí odpovědět na tento e-mail nebo se ozvat přes náš web.",

			// Trigger 2: Poptávka balíčku (Poptat balíček)
			'trigger_inquiry_enabled'         => 1,
			'trigger_inquiry_admin_subject'   => 'Nová poptávka balíčku – {name} ({shop_url})',
			'trigger_inquiry_admin_body'      => "Zákazník poptává konkrétní balíček.\n\nJméno: {name}\nE-mail: {email}\nWeb: {shop_url}\nSegment: {segment}\nMěsíční obrat: {monthly_revenue}\n\nPoptávaný balíček: {inquiry_package}\n\nVýsledky kalkulačky:\n- Potenciál emailingu: {final_potential}\n- Odhadovaný obrat: {emailing_revenue_mid}\n- Doporučený budget: {available_budget}",
			'trigger_inquiry_client_subject'  => 'Děkujeme za zájem, {name} – brzy se ozveme',
			'trigger_inquiry_client_body'     => "Dobrý den, {name},\n\ndíky za zájem o balíček {inquiry_package}.\n\nOzveme se vám v nejbližší možné době a probereme detaily a možnosti spolupráce.\n\nVaše výsledky z kalkulačky:\n- Odhadovaný potenciál emailingu: {final_potential}\n- Odhadovaný obrat z emailingu: {emailing_revenue_mid}\n\nTěšíme se na spolupráci!",

			// Trigger 3: Poptávka konzultace (hlavní CTA)
			'trigger_consultation_enabled'        => 1,
			'trigger_consultation_admin_subject'  => 'Nová poptávka konzultace – {name} ({shop_url})',
			'trigger_consultation_admin_body'     => "Zákazník poptává konzultaci emailingové strategie.\n\nJméno: {name}\nE-mail: {email}\nWeb: {shop_url}\nSegment: {segment}\nMěsíční obrat: {monthly_revenue}\n\nVýsledky kalkulačky:\n- Potenciál emailingu: {final_potential}\n- Odhadovaný obrat: {emailing_revenue_mid}\n- Doporučený balíček: {recommended_package} ({recommended_package_price})",
			'trigger_consultation_client_subject' => 'Potvrzení poptávky konzultace – brzy se ozveme',
			'trigger_consultation_client_body'    => "Dobrý den, {name},\n\nděkujeme za zájem o konzultaci emailingové strategie pro {shop_url}.\n\nOzveme se vám v nejbližší možné době.\n\nVaše výsledky z kalkulačky:\n- Odhadovaný potenciál emailingu: {final_potential}\n- Odhadovaný obrat z emailingu: {emailing_revenue_mid}\n- Doporučený balíček: {recommended_package}\n\nTěšíme se na spolupráci!",
		];
		$saved = get_option( 'ecalc_notifications', [] );
		return wp_parse_args( $saved, $defaults );
	}

	public function save_notifications( array $data ): void {
		update_option( 'ecalc_notifications', $data );
	}

	public function get_turnstile(): array {
		$defaults = [
			'enabled'    => 0,
			'site_key'   => '',
			'secret_key' => '',
		];
		$saved = get_option( 'ecalc_turnstile', [] );
		return wp_parse_args( $saved, $defaults );
	}

	public function save_turnstile( array $data ): void {
		update_option( 'ecalc_turnstile', $data );
	}

	private function default_segments(): array {
		return [
			// Potraviny a nápoje
			[ 'id' => 1,  'name' => 'Potraviny',                        'score' => 1.00, 'active' => 1, 'order' => 1 ],
			[ 'id' => 2,  'name' => 'Káva a čaj',                       'score' => 1.00, 'active' => 1, 'order' => 2 ],
			[ 'id' => 3,  'name' => 'Alkohol a nápoje',                 'score' => 0.95, 'active' => 1, 'order' => 3 ],
			// Zdraví, krása, péče
			[ 'id' => 4,  'name' => 'Drogerie a čistící prostředky',    'score' => 0.92, 'active' => 1, 'order' => 4 ],
			[ 'id' => 5,  'name' => 'Kosmetika a péče o tělo',          'score' => 0.90, 'active' => 1, 'order' => 5 ],
			[ 'id' => 6,  'name' => 'Doplňky stravy a vitamíny',        'score' => 0.90, 'active' => 1, 'order' => 6 ],
			[ 'id' => 7,  'name' => 'Zdraví a lékárna',                 'score' => 0.85, 'active' => 1, 'order' => 7 ],
			// Zvířata
			[ 'id' => 8,  'name' => 'Petcare – krmivo a péče',          'score' => 0.95, 'active' => 1, 'order' => 8 ],
			[ 'id' => 9,  'name' => 'Granule a potřeby pro zvířata',    'score' => 1.00, 'active' => 1, 'order' => 9 ],
			// Domácnost
			[ 'id' => 10, 'name' => 'Domácí potřeby a kuchyně',         'score' => 0.70, 'active' => 1, 'order' => 10 ],
			[ 'id' => 11, 'name' => 'Dům a bytové doplňky',             'score' => 0.45, 'active' => 1, 'order' => 11 ],
			[ 'id' => 12, 'name' => 'Svíčky a interiérové dekorace',    'score' => 0.55, 'active' => 1, 'order' => 12 ],
			[ 'id' => 13, 'name' => 'Nábytek',                          'score' => 0.25, 'active' => 1, 'order' => 13 ],
			[ 'id' => 14, 'name' => 'Bytový textil (ložní, ručníky)',   'score' => 0.40, 'active' => 1, 'order' => 14 ],
			// Zahrada
			[ 'id' => 15, 'name' => 'Dům a zahrada',                    'score' => 0.55, 'active' => 1, 'order' => 15 ],
			[ 'id' => 16, 'name' => 'Zahradnictví a rostliny',          'score' => 0.60, 'active' => 1, 'order' => 16 ],
			[ 'id' => 17, 'name' => 'Zahradní nábytek a vybavení',      'score' => 0.30, 'active' => 1, 'order' => 17 ],
			// Móda a sport
			[ 'id' => 18, 'name' => 'Móda a oblečení',                  'score' => 0.75, 'active' => 1, 'order' => 18 ],
			[ 'id' => 19, 'name' => 'Dětské zboží a oblečení',          'score' => 0.75, 'active' => 1, 'order' => 19 ],
			[ 'id' => 20, 'name' => 'Sportovní vybavení a oblečení',    'score' => 0.60, 'active' => 1, 'order' => 20 ],
			[ 'id' => 21, 'name' => 'Outdoor, turistika a kempování',   'score' => 0.55, 'active' => 1, 'order' => 21 ],
			// Volný čas
			[ 'id' => 22, 'name' => 'Hračky a hry',                     'score' => 0.65, 'active' => 1, 'order' => 22 ],
			[ 'id' => 23, 'name' => 'Hobby a kutilství',                 'score' => 0.50, 'active' => 1, 'order' => 23 ],
			[ 'id' => 24, 'name' => 'Knihy, hudba a média',             'score' => 0.45, 'active' => 1, 'order' => 24 ],
			[ 'id' => 25, 'name' => 'Výtvarné a kreativní potřeby',     'score' => 0.40, 'active' => 1, 'order' => 25 ],
			// Technika
			[ 'id' => 26, 'name' => 'Malá elektronika a gadgety',       'score' => 0.35, 'active' => 1, 'order' => 26 ],
			[ 'id' => 27, 'name' => 'Bílé zboží a velké spotřebiče',    'score' => 0.20, 'active' => 1, 'order' => 27 ],
			[ 'id' => 28, 'name' => 'Počítače a příslušenství',         'score' => 0.30, 'active' => 1, 'order' => 28 ],
			// Kancelář a nástroje
			[ 'id' => 29, 'name' => 'Kancelářské potřeby',              'score' => 0.35, 'active' => 1, 'order' => 29 ],
			[ 'id' => 30, 'name' => 'Nástroje a nářadí',                'score' => 0.25, 'active' => 1, 'order' => 30 ],
			// Auto a luxus
			[ 'id' => 31, 'name' => 'Automotive a autodoplňky',         'score' => 0.25, 'active' => 1, 'order' => 31 ],
			[ 'id' => 32, 'name' => 'Šperky a zlatnictví',              'score' => 0.20, 'active' => 1, 'order' => 32 ],
			// Ostatní
			[ 'id' => 33, 'name' => 'Cestování a dovolená',             'score' => 0.30, 'active' => 1, 'order' => 33 ],
			[ 'id' => 34, 'name' => 'Ostatní',                          'score' => 0.50, 'active' => 1, 'order' => 34 ],
		];
	}

	public function merge_default_segments(): int {
		$saved    = $this->get_segments();
		$defaults = $this->default_segments();
		$existing = array_column( $saved, 'name' );
		$added    = 0;
		$max_id   = $saved ? max( array_column( $saved, 'id' ) ) : 0;
		$max_ord  = $saved ? max( array_column( $saved, 'order' ) ) : 0;

		foreach ( $defaults as $def ) {
			if ( ! in_array( $def['name'], $existing, true ) ) {
				$max_id++;
				$max_ord++;
				$def['id']    = $max_id;
				$def['order'] = $max_ord;
				$saved[]      = $def;
				$added++;
			}
		}

		if ( $added > 0 ) {
			$this->save_segments( $saved );
		}
		return $added;
	}

	private function default_database_ranges(): array {
		return [
			[ 'id' => 1, 'name' => '0–500',        'min' => 0,     'max' => 500,   'score' => 0.00, 'active' => 1, 'order' => 1 ],
			[ 'id' => 2, 'name' => '501–2 000',    'min' => 501,   'max' => 2000,  'score' => 0.25, 'active' => 1, 'order' => 2 ],
			[ 'id' => 3, 'name' => '2 001–5 000',  'min' => 2001,  'max' => 5000,  'score' => 0.55, 'active' => 1, 'order' => 3 ],
			[ 'id' => 4, 'name' => '5 001–10 000', 'min' => 5001,  'max' => 10000, 'score' => 0.85, 'active' => 1, 'order' => 4 ],
			[ 'id' => 5, 'name' => '10 001+',      'min' => 10001, 'max' => null,  'score' => 1.00, 'active' => 1, 'order' => 5 ],
		];
	}

	private function default_revenue_ranges(): array {
		return [
			[ 'id' => 1, 'name' => 'do 250 000 Kč',            'min' => 0,       'max' => 250000,  'calc_value' => 200000,  'active' => 1, 'order' => 1 ],
			[ 'id' => 2, 'name' => '250 001–500 000 Kč',       'min' => 250001,  'max' => 500000,  'calc_value' => 375000,  'active' => 1, 'order' => 2 ],
			[ 'id' => 3, 'name' => '500 001–1 000 000 Kč',     'min' => 500001,  'max' => 1000000, 'calc_value' => 750000,  'active' => 1, 'order' => 3 ],
			[ 'id' => 4, 'name' => '1 000 001–2 000 000 Kč',   'min' => 1000001, 'max' => 2000000, 'calc_value' => 1500000, 'active' => 1, 'order' => 4 ],
			[ 'id' => 5, 'name' => '2 000 001–5 000 000 Kč',   'min' => 2000001, 'max' => 5000000, 'calc_value' => 3500000, 'active' => 1, 'order' => 5 ],
			[ 'id' => 6, 'name' => '5 000 001+ Kč',            'min' => 5000001, 'max' => null,    'calc_value' => 5000000, 'active' => 1, 'order' => 6 ],
		];
	}

	private function default_packages(): array {
		return [
			[
				'id'          => 1,
				'name'        => 'Startovací emailing',
				'price'       => 15000,
				'description' => 'Pro e-shopy, kde dává smysl začít se základní správou emailingu a postupně rozvíjet databázi i automatizace. První měřitelné výsledky obvykle do 60 dní.',
				'items'       => [
					'2x newsletter měsíčně',
					'Až 3 automatizace',
					'Základní segmentace',
					'Měsíční reporting',
					'E-mailová podpora',
				],
				'result_text' => 'Podle zadaných údajů pro vás dává smysl začít se základním balíčkem. Ten umožní rozumně nastavit emailing, začít pracovat s databází a postupně ověřit skutečný výkon kampaní i automatizací.',
				'active'      => 1,
				'order'       => 1,
			],
			[
				'id'          => 2,
				'name'        => 'Výkonnostní emailing',
				'price'       => 22000,
				'description' => 'Pro e-shopy s vyšším potenciálem, kde emailing může tvořit významnější část obratu a dává smysl aktivnější práce s kampaněmi, automatizacemi a segmentací. Cílem je dlouhodobě růst obrat z emailingu, ne jen udržet status quo.',
				'items'       => [
					'4x newsletter měsíčně',
					'Až 8 automatizací',
					'Pokročilá segmentace',
					'A/B testování',
					'VIP podpora',
					'Měsíční strategie a reporting',
				],
				'result_text' => 'Podle zadaných údajů má váš e-shop zajímavý potenciál pro výkonnostní emailing. Doporučujeme pokročilejší balíček, který dává prostor pro aktivnější práci s kampaněmi, segmentací, automatizacemi a dlouhodobou optimalizací výkonu.',
				'active'      => 1,
				'order'       => 2,
			],
		];
	}

	private function default_result_texts(): array {
		return [
			'result_title' => 'Výsledek vaší kalkulačky',
			'low_potential' => 'Podle zadaných údajů má váš e-shop v emailingu určitý potenciál, ale při vámi zvoleném PNO by pravděpodobně nebylo možné pokrýt minimální náklady na smysluplnou správu emailingu. Doporučujeme bezplatnou konzultaci, kde společně ověříme, zda existuje cesta, jak potenciál navýšit – například růstem databáze, lepší prací s opakovaným nákupem nebo úpravou očekávání.',
			'borderline'    => 'Výsledek je hraniční. Emailing může dávat smysl, ale při aktuálním obratu, velikosti databáze a vámi zvoleném PNO je potřeba návratnost detailněji ověřit. Nejbližší variantou je základní balíček, ale reálné PNO může být vyšší než vámi zadané. Doporučujeme konzultaci zdarma.',
		];
	}

	private function default_arguments(): array {
		return [
			'enabled'  => 1,
			'title'    => 'Proč máte tento potenciál',
			'subtitle' => 'Toto jsou 3 hlavní důvody – nejde o kompletní výčet všech faktorů, které se do výpočtu promítají.',

			'threshold_medium' => 0.34,
			'threshold_high'   => 0.67,

			'consumable_low'    => 'Váš sortiment ({consumable_percentage} opakovaného nákupu) je spíše jednorázový, přesto i zde funguje cross-sell a doporučené produkty na základě předchozích nákupů.',
			'consumable_medium' => 'Přibližně {consumable_percentage} vašeho sortimentu se nakupuje opakovaně. I částečná automatizace (win-back kampaně, připomínky doplnění) dokáže zvednout obrat z e-mailingu.',
			'consumable_high'   => 'Až {consumable_percentage} vašeho sortimentu tvoří zboží s opakovaným nákupem. To je ideální pro automatizované reorder sekvence a personalizovaná doporučení – tento faktor má na výpočet největší váhu (70 %) a právě u vás táhne potenciál nahoru.',

			'database_low'    => 'Databáze {database_range} kontaktů je zatím menší, ale i tak lze stavět na uvítacích a nákupních sekvencích – s růstem databáze poroste i potenciál.',
			'database_medium' => 'Databáze {database_range} kontaktů už umožňuje základní segmentaci podle chování a nákupní historie.',
			'database_high'   => 'S databází {database_range} kontaktů máte dostatek dat pro jemnou segmentaci a personalizaci – větší databáze znamená přesnější cílení a vyšší návratnost.',

			'segment_low'    => 'V oboru {segment} je e-mailing spíše doplňkový kanál, přesto dokáže zvýšit retenci a opakované nákupy.',
			'segment_medium' => 'Obor {segment} má solidní potenciál pro e-mailing, zejména v kombinaci s personalizovanými nabídkami a sezónními kampaněmi.',
			'segment_high'   => 'Ve vašem oboru ({segment}) je e-mailing dlouhodobě jeden z nejsilnějších prodejních kanálů – zákazníci nakupují v pravidelných cyklech, ideální pro replenishment a win-back automatizace.',

			'summary' => 'Na základě těchto faktorů odhadujeme, že e-mailing může pro váš e-shop generovat {emailing_revenue_low} – {emailing_revenue_high} měsíčně, tedy {final_potential} vašeho obratu.',
		];
	}
}
