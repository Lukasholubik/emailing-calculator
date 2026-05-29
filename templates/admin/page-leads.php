<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php
$has_advanced = (
	! empty( $filters['segment'] )
	|| ! empty( $filters['package'] )
	|| ! empty( $filters['cta_type'] )
	|| $filters['booking'] !== ''
	|| $filters['cta_clicked'] !== ''
);

$active_filter_keys = [ 'search', 'date_period', 'lead_status', 'result_type', 'segment', 'package', 'cta_type', 'booking', 'cta_clicked' ];
$has_any_filter     = (bool) array_filter(
	array_intersect_key( $filters, array_flip( $active_filter_keys ) ),
	function( $v ) { return is_array( $v ) ? ! empty( $v ) : $v !== ''; }
);

// URL builder — handles both string and array filter values
$filter_url = function( array $overrides = [] ) use ( $filters ): string {
	$p = array_merge( [ 'page' => 'ecalc_leads' ], $filters, $overrides );
	$p = array_filter( $p, function( $v ) {
		return is_array( $v ) ? ! empty( $v ) : ( $v !== '' && $v !== null );
	} );
	if ( (int) ( $p['paged'] ?? 0 ) <= 1 ) unset( $p['paged'] );
	$p['page'] = 'ecalc_leads';
	return admin_url( 'admin.php?' . http_build_query( $p ) );
};

$sort_url = function( string $col ) use ( $filters, $filter_url ): string {
	$dir = ( $filters['orderby'] === $col && $filters['order'] === 'ASC' ) ? 'DESC' : 'ASC';
	return $filter_url( [ 'orderby' => $col, 'order' => $dir, 'paged' => 1 ] );
};

$sort_icon = function( string $col ) use ( $filters ): string {
	if ( $filters['orderby'] !== $col ) return '<span class="ecalc-sort-neutral">⇅</span>';
	return $filters['order'] === 'ASC'
		? '<span class="ecalc-sort-asc">▲</span>'
		: '<span class="ecalc-sort-desc">▼</span>';
};

$period_labels   = [
	'today'      => 'Dnes',      'yesterday'  => 'Včera',
	'this_week'  => 'Tento týden', 'last_week' => 'Minulý týden',
	'this_month' => 'Tento měsíc', 'last_month'=> 'Minulý měsíc',
	'this_year'  => 'Tento rok',   'last_year' => 'Minulý rok',
	'custom'     => 'Vlastní rozsah', 'all'    => 'Vše',
];
$cta_type_labels = [ 'package' => 'Poptávka balíčku', 'consultation' => 'Konzultace' ];
$booking_labels  = [ 'yes' => 'Rezervace dokončena', 'no' => 'Bez rezervace' ];
$cta_click_lbs   = [ '1' => 'Klikl na CTA', '0' => 'Neklikl na CTA' ];

// Button label for lead_status multi-select
$ls_sel   = $filters['lead_status'];
$ls_count = count( $ls_sel );
$ls_label = $ls_count === 0 ? 'Všechny stavy'
	: ( $ls_count === 1 ? ( $all_statuses[ $ls_sel[0] ] ?? $ls_sel[0] ) : $ls_count . ' vybrané' );

// Button label for result_type multi-select
$rt_sel   = $filters['result_type'];
$rt_count = count( $rt_sel );
$rt_label = $rt_count === 0 ? 'Všechny výsledky'
	: ( $rt_count === 1 ? ( $result_labels[ $rt_sel[0] ] ?? $rt_sel[0] ) : $rt_count . ' vybrané' );
?>
<div class="wrap ecalc-admin ecalc-leads-page">
	<h1 class="wp-heading-inline">Emailing kalkulačka – Leady</h1>

	<?php if ( $notice['type'] ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible">
			<p><?php echo esc_html( $notice['message'] ); ?></p>
		</div>
	<?php endif; ?>

	<!-- ====== FILTRY ====== -->
	<div class="ecalc-leads-filters">
		<form method="get" action="" id="ecalc-leads-filter-form">
			<input type="hidden" name="page" value="ecalc_leads">

			<div class="ecalc-leads-filter-row">
				<input type="search" name="search" class="ecalc-leads-search"
					value="<?php echo esc_attr( $filters['search'] ); ?>"
					placeholder="Jméno, e-mail, telefon, ID…">

				<select name="date_period" id="ecalc-leads-period">
					<option value="">Všechna období</option>
					<?php foreach ( $period_labels as $val => $lbl ) : ?>
						<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $filters['date_period'], $val ); ?>>
							<?php echo esc_html( $lbl ); ?>
						</option>
					<?php endforeach; ?>
				</select>

				<!-- Multi-select: Stav leadu -->
				<div class="ecalc-ms" data-placeholder="Všechny stavy">
					<button type="button" class="ecalc-ms-btn <?php echo ! empty( $ls_sel ) ? 'ecalc-ms-active' : ''; ?>" aria-expanded="false">
						<span class="ecalc-ms-label"><?php echo esc_html( $ls_label ); ?></span>
						<span class="ecalc-ms-caret">▾</span>
					</button>
					<div class="ecalc-ms-panel" hidden>
						<?php foreach ( $all_statuses as $val => $lbl ) : ?>
							<label class="ecalc-ms-option">
								<input type="checkbox" name="lead_status[]" value="<?php echo esc_attr( $val ); ?>"
									<?php echo in_array( $val, $ls_sel, true ) ? 'checked' : ''; ?>>
								<?php echo esc_html( $lbl ); ?>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Multi-select: Výsledek -->
				<div class="ecalc-ms" data-placeholder="Všechny výsledky">
					<button type="button" class="ecalc-ms-btn <?php echo ! empty( $rt_sel ) ? 'ecalc-ms-active' : ''; ?>" aria-expanded="false">
						<span class="ecalc-ms-label"><?php echo esc_html( $rt_label ); ?></span>
						<span class="ecalc-ms-caret">▾</span>
					</button>
					<div class="ecalc-ms-panel" hidden>
						<?php foreach ( $result_types as $rt ) : ?>
							<label class="ecalc-ms-option">
								<input type="checkbox" name="result_type[]" value="<?php echo esc_attr( $rt ); ?>"
									<?php echo in_array( $rt, $rt_sel, true ) ? 'checked' : ''; ?>>
								<?php echo esc_html( $result_labels[ $rt ] ?? $rt ); ?>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<button type="submit" class="button button-primary">Filtrovat</button>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ecalc_leads' ) ); ?>" class="button">Zrušit filtry</a>
			</div>

			<!-- Vlastní rozsah dat -->
			<div id="ecalc-leads-custom-range" class="ecalc-leads-custom-range"
				style="display:<?php echo $filters['date_period'] === 'custom' ? 'flex' : 'none'; ?>;">
				<label>Od <input type="date" name="date_from" value="<?php echo esc_attr( $filters['date_from'] ); ?>"></label>
				<label>Do <input type="date" name="date_to" value="<?php echo esc_attr( $filters['date_to'] ); ?>"></label>
			</div>

			<!-- Pokročilé filtry -->
			<details id="ecalc-leads-advanced" <?php echo $has_advanced ? 'open' : ''; ?>>
				<summary class="ecalc-leads-adv-toggle">Pokročilé filtry</summary>
				<div class="ecalc-leads-adv-body">
					<?php if ( ! empty( $segments ) ) : ?>
					<label class="ecalc-filter-label">Oblast podnikání
						<select name="segment">
							<option value="">Vše</option>
							<?php foreach ( $segments as $seg ) : ?>
								<option value="<?php echo esc_attr( $seg ); ?>" <?php selected( $filters['segment'], $seg ); ?>>
									<?php echo esc_html( $seg ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>
					<?php endif; ?>

					<?php if ( ! empty( $packages ) ) : ?>
					<label class="ecalc-filter-label">Balíček
						<select name="package">
							<option value="">Vše</option>
							<?php foreach ( $packages as $pkg ) : ?>
								<option value="<?php echo esc_attr( $pkg ); ?>" <?php selected( $filters['package'], $pkg ); ?>>
									<?php echo esc_html( $pkg ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>
					<?php endif; ?>

					<label class="ecalc-filter-label">Typ konverze
						<select name="cta_type">
							<option value="">Vše</option>
							<option value="package" <?php selected( $filters['cta_type'], 'package' ); ?>>Poptávka balíčku</option>
							<option value="consultation" <?php selected( $filters['cta_type'], 'consultation' ); ?>>Konzultace</option>
						</select>
					</label>

					<label class="ecalc-filter-label">Rezervace
						<select name="booking">
							<option value="">Vše</option>
							<option value="yes" <?php selected( $filters['booking'], 'yes' ); ?>>Dokončena</option>
							<option value="no" <?php selected( $filters['booking'], 'no' ); ?>>Bez rezervace</option>
						</select>
					</label>

					<label class="ecalc-filter-label">CTA klik
						<select name="cta_clicked">
							<option value="">Vše</option>
							<option value="1" <?php selected( $filters['cta_clicked'], '1' ); ?>>Klikl</option>
							<option value="0" <?php selected( $filters['cta_clicked'], '0' ); ?>>Neklikl</option>
						</select>
					</label>
				</div>
			</details>

			<!-- Zachování řazení při filtrování -->
			<input type="hidden" name="orderby" value="<?php echo esc_attr( $filters['orderby'] ); ?>">
			<input type="hidden" name="order" value="<?php echo esc_attr( $filters['order'] ); ?>">
		</form>
	</div>

	<!-- ====== AKTIVNÍ FILTRY ====== -->
	<?php
	$active_badges = [];
	if ( ! empty( $filters['search'] ) ) {
		$active_badges[] = [ 'label' => 'Hledání: ' . $filters['search'], 'clear' => [ 'search' => '' ] ];
	}
	if ( ! empty( $filters['date_period'] ) ) {
		$dl = $period_labels[ $filters['date_period'] ] ?? $filters['date_period'];
		if ( $filters['date_period'] === 'custom' && ( $filters['date_from'] || $filters['date_to'] ) ) {
			$dl .= ': ' . $filters['date_from'] . ' – ' . $filters['date_to'];
		}
		$active_badges[] = [ 'label' => 'Období: ' . $dl, 'clear' => [ 'date_period' => '', 'date_from' => '', 'date_to' => '' ] ];
	}
	if ( ! empty( $ls_sel ) ) {
		$ls_names = array_map( fn( $v ) => $all_statuses[ $v ] ?? $v, $ls_sel );
		$active_badges[] = [ 'label' => 'Stav: ' . implode( ', ', $ls_names ), 'clear' => [ 'lead_status' => [] ] ];
	}
	if ( ! empty( $rt_sel ) ) {
		$rt_names = array_map( fn( $v ) => $result_labels[ $v ] ?? $v, $rt_sel );
		$active_badges[] = [ 'label' => 'Výsledek: ' . implode( ', ', $rt_names ), 'clear' => [ 'result_type' => [] ] ];
	}
	if ( ! empty( $filters['segment'] ) ) {
		$active_badges[] = [ 'label' => 'Oblast: ' . $filters['segment'], 'clear' => [ 'segment' => '' ] ];
	}
	if ( ! empty( $filters['package'] ) ) {
		$active_badges[] = [ 'label' => 'Balíček: ' . $filters['package'], 'clear' => [ 'package' => '' ] ];
	}
	if ( ! empty( $filters['cta_type'] ) ) {
		$active_badges[] = [ 'label' => 'Konverze: ' . ( $cta_type_labels[ $filters['cta_type'] ] ?? $filters['cta_type'] ), 'clear' => [ 'cta_type' => '' ] ];
	}
	if ( $filters['booking'] !== '' ) {
		$active_badges[] = [ 'label' => 'Rezervace: ' . ( $booking_labels[ $filters['booking'] ] ?? $filters['booking'] ), 'clear' => [ 'booking' => '' ] ];
	}
	if ( $filters['cta_clicked'] !== '' ) {
		$active_badges[] = [ 'label' => 'CTA klik: ' . ( $cta_click_lbs[ $filters['cta_clicked'] ] ?? $filters['cta_clicked'] ), 'clear' => [ 'cta_clicked' => '' ] ];
	}
	?>
	<?php if ( ! empty( $active_badges ) ) : ?>
	<div class="ecalc-active-badges">
		<?php foreach ( $active_badges as $badge ) :
			$badge_url = $filter_url( array_merge( $badge['clear'], [ 'paged' => 1 ] ) );
		?>
			<span class="ecalc-active-badge">
				<?php echo esc_html( $badge['label'] ); ?>
				<a href="<?php echo esc_url( $badge_url ); ?>" class="ecalc-badge-remove" title="Odebrat filtr">×</a>
			</span>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<!-- Počet leadů -->
	<p class="ecalc-total">
		<?php if ( $has_any_filter ) : ?>
			<strong><?php echo (int) $total; ?></strong> leadů odpovídá aktivním filtrům
		<?php else : ?>
			Celkem leadů: <strong><?php echo (int) $total; ?></strong>
		<?php endif; ?>
	</p>

	<?php if ( empty( $leads_list ) ) : ?>
		<div class="ecalc-leads-empty">
			<p><?php echo $has_any_filter ? 'Žádné leady neodpovídají nastaveným filtrům.' : 'Zatím žádné leady.'; ?></p>
			<?php if ( $has_any_filter ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ecalc_leads' ) ); ?>" class="button">Zrušit filtry</a>
			<?php endif; ?>
		</div>
	<?php else : ?>

	<!-- ====== HROMADNÉ AKCE + TABULKA ====== -->
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="ecalc-bulk-form">
		<?php wp_nonce_field( 'ecalc_bulk_action' ); ?>
		<input type="hidden" name="action" value="ecalc_bulk_action">

		<!-- Předání filtrů pro redirect zpět (pole se posílají jako filter_lead_status[], atd.) -->
		<?php foreach ( $filters as $fkey => $fval ) :
			if ( is_array( $fval ) ) :
				foreach ( $fval as $fitem ) : ?>
					<input type="hidden" name="filter_<?php echo esc_attr( $fkey ); ?>[]" value="<?php echo esc_attr( $fitem ); ?>">
				<?php endforeach;
			else : ?>
				<input type="hidden" name="filter_<?php echo esc_attr( $fkey ); ?>" value="<?php echo esc_attr( $fval ); ?>">
			<?php endif;
		endforeach; ?>

		<div class="ecalc-bulk-bar">
			<label class="ecalc-bulk-select-all-label">
				<input type="checkbox" id="ecalc-select-all"> Vybrat vše
			</label>
			<select name="bulk_action" id="ecalc-bulk-action">
				<option value="">— Hromadná akce —</option>
				<option value="delete">Smazat vybrané</option>
				<option value="export">Exportovat vybrané (CSV)</option>
			</select>
			<button type="submit" class="button" id="ecalc-bulk-submit">Provést</button>
			<span class="ecalc-bulk-count" id="ecalc-bulk-count"></span>
			<span class="ecalc-bulk-sep">|</span>
			<button type="button" class="button" id="ecalc-logs-toggle-all" data-state="open" title="Sbalit / rozbalit všechny logy">&#9660; Sbalit logy</button>
		</div>

		<table class="wp-list-table widefat fixed striped ecalc-leads-table">
			<colgroup>
				<col style="width:32px">
				<col style="width:38px">
				<col style="width:115px">
				<col style="width:75px">
				<col style="width:120px">
				<col style="width:100px">
				<col style="width:95px">
				<col style="width:82px">
				<col style="width:82px">
				<col style="width:108px">
				<col style="width:115px">
				<col style="width:78px">
				<col style="width:85px">
				<col style="width:152px">
			</colgroup>
			<thead>
				<tr>
					<th class="ecalc-col-check"><input type="checkbox" id="ecalc-select-all-top"></th>
					<th><a href="<?php echo esc_url( $sort_url( 'id' ) ); ?>" class="ecalc-sortable">ID <?php echo $sort_icon( 'id' ); // phpcs:ignore ?></a></th>
					<th><a href="<?php echo esc_url( $sort_url( 'created_at' ) ); ?>" class="ecalc-sortable">Datum <?php echo $sort_icon( 'created_at' ); // phpcs:ignore ?></a></th>
					<th><a href="<?php echo esc_url( $sort_url( 'name' ) ); ?>" class="ecalc-sortable">Jméno <?php echo $sort_icon( 'name' ); // phpcs:ignore ?></a></th>
					<th><a href="<?php echo esc_url( $sort_url( 'email' ) ); ?>" class="ecalc-sortable">E-mail <?php echo $sort_icon( 'email' ); // phpcs:ignore ?></a></th>
					<th>Web</th>
					<th><a href="<?php echo esc_url( $sort_url( 'segment' ) ); ?>" class="ecalc-sortable">Segment <?php echo $sort_icon( 'segment' ); // phpcs:ignore ?></a></th>
					<th><a href="<?php echo esc_url( $sort_url( 'monthly_revenue' ) ); ?>" class="ecalc-sortable">Obrat <?php echo $sort_icon( 'monthly_revenue' ); // phpcs:ignore ?></a></th>
					<th>Budget</th>
					<th><a href="<?php echo esc_url( $sort_url( 'result_type' ) ); ?>" class="ecalc-sortable">Výsledek <?php echo $sort_icon( 'result_type' ); // phpcs:ignore ?></a></th>
					<th><a href="<?php echo esc_url( $sort_url( 'lead_status' ) ); ?>" class="ecalc-sortable">Stav leadu <?php echo $sort_icon( 'lead_status' ); // phpcs:ignore ?></a></th>
					<th>SmartEmailing</th>
					<th>Rezervace</th>
					<th>Akce</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $leads_list as $lead ) :
					$lead_id    = (int) $lead['id'];
					$lead_log   = $changelogs[ $lead_id ] ?? [];
					$log_count  = count( $lead_log );
					$log_row_id = 'ecalc-changelog-' . $lead_id;
				?>
					<tr>
						<td><input type="checkbox" name="lead_ids[]" value="<?php echo $lead_id; ?>" class="ecalc-row-check"></td>
						<td><?php echo $lead_id; ?></td>
						<td><?php echo esc_html( date( 'd.m.Y H:i', strtotime( $lead['created_at'] ) ) ); ?></td>
						<td><?php echo esc_html( $lead['name'] ); ?></td>
						<td>
							<a href="mailto:<?php echo esc_attr( $lead['email'] ); ?>"><?php echo esc_html( $lead['email'] ); ?></a>
							<?php if ( ! empty( $lead['phone'] ) ) : ?>
								<br><a href="tel:<?php echo esc_attr( $lead['phone'] ); ?>" style="color:#666;font-size:11px;white-space:nowrap;">📞 <?php echo esc_html( $lead['phone'] ); ?></a>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $lead['shop_url'] ); ?></td>
						<td><?php echo esc_html( $lead['segment'] ); ?></td>
						<td><?php echo esc_html( number_format( (float) $lead['monthly_revenue'], 0, ',', ' ' ) ); ?> Kč</td>
						<td><?php echo esc_html( number_format( (float) $lead['available_budget'], 0, ',', ' ' ) ); ?> Kč</td>
						<td>
							<span class="ecalc-badge ecalc-badge--<?php echo esc_attr( $lead['result_type'] ); ?>">
								<?php echo esc_html( $result_labels[ $lead['result_type'] ] ?? $lead['result_type'] ); ?>
							</span>
						</td>
						<td>
							<?php $ls = $lead['lead_status'] ?? ECAlc_Lead_Status::CEKANI; ?>
							<div class="ecalc-status-wrap">
								<span class="ecalc-lead-status ecalc-lead-status--<?php echo esc_attr( ECAlc_Lead_Status::color( $ls ) ); ?>">
									<?php echo esc_html( ECAlc_Lead_Status::label( $ls ) ); ?>
								</span>
								<select class="ecalc-status-select" style="display:none;"
									data-lead-id="<?php echo $lead_id; ?>"
									data-nonce="<?php echo esc_attr( wp_create_nonce( 'ecalc_change_status' ) ); ?>">
									<?php foreach ( ECAlc_Lead_Status::all() as $val => $lbl ) : ?>
										<?php if ( $val === ECAlc_Lead_Status::ZOBCHODOVANO ) continue; ?>
										<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $ls, $val ); ?>>
											<?php echo esc_html( $lbl ); ?>
										</option>
									<?php endforeach; ?>
									<option value="zobchodovano" <?php selected( $ls, ECAlc_Lead_Status::ZOBCHODOVANO ); ?>>Zobchodováno</option>
								</select>
								<button type="button" class="button button-small ecalc-status-edit-btn" title="Změnit stav">✎</button>
							</div>
						</td>
						<td>
							<span class="ecalc-se-status ecalc-se-status--<?php echo esc_attr( $lead['smartemailing_status'] ); ?>">
								<?php echo esc_html( $lead['smartemailing_status'] ); ?>
							</span>
						</td>
						<td>
							<?php
							$bs_labels = [ 'opened' => '📅 Otevřel', 'completed' => '✅ Zarezervoval', 'declined' => '❌ Nezarezervoval', '' => '–' ];
							$bs = $lead['booking_status'] ?? '';
							echo esc_html( $bs_labels[ $bs ] ?? $bs );
							?>
						</td>
						<td class="ecalc-row-actions">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=ecalc_leads&action=view&id=' . $lead_id ) ); ?>" class="button button-small">Detail</a>
							<button type="button"
								class="button button-small ecalc-changelog-toggle"
								data-target="<?php echo esc_attr( $log_row_id ); ?>"
								title="Zobrazit changelog"
								aria-expanded="true">
								&#9660; Log<?php if ( $log_count ) : ?> <span class="ecalc-log-count">(<?php echo $log_count; ?>)</span><?php endif; ?>
							</button>
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ecalc_delete_lead&lead_id=' . $lead_id ), 'ecalc_delete_lead' ) ); ?>"
								class="button button-small button-link-delete ecalc-quick-delete"
								data-confirm="Smazat lead #<?php echo $lead_id; ?>?">✕</a>
						</td>
					</tr>
					<tr id="<?php echo esc_attr( $log_row_id ); ?>" class="ecalc-changelog-row">
						<td colspan="14" class="ecalc-changelog-cell">
							<?php if ( empty( $lead_log ) ) : ?>
								<span class="ecalc-changelog-empty">Zatím žádné záznamy v changelogu.</span>
							<?php else :
								$alert_types = [ 'name_changed', 'url_changed' ];
								$grouped     = [];
								foreach ( $lead_log as $entry ) {
									$key = date( 'Y-m-d H:i', strtotime( $entry['changed_at'] ) );
									$grouped[ $key ][] = $entry;
								}
							?>
								<div class="ecalc-changelog-list">
									<?php foreach ( $grouped as $entries ) :
										$display_time = date( 'd.m H:i', strtotime( $entries[0]['changed_at'] ) );
										$full_date    = date( 'd.m.Y H:i', strtotime( $entries[0]['changed_at'] ) );
									?>
										<div class="ecalc-changelog-group">
											<span class="ecalc-changelog-time"><?php echo esc_html( $display_time ); ?></span>
											<?php foreach ( $entries as $entry ) :
												$is_alert = in_array( $entry['change_type'], $alert_types, true );
												$tooltip  = esc_attr( $full_date . ' · ' . $entry['change_type'] . ': ' . $entry['note'] );
											?>
												<span class="ecalc-changelog-entry<?php echo $is_alert ? ' ecalc-changelog-entry--alert' : ''; ?>" title="<?php echo $tooltip; ?>">
													<span class="ecalc-changelog-type"><?php echo esc_html( $entry['change_type'] ); ?></span>
													<span class="ecalc-changelog-note"><?php echo esc_html( $entry['note'] ); ?></span>
												</span>
											<?php endforeach; ?>
										</div>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<!-- Bulk bar dole -->
		<div class="ecalc-bulk-bar ecalc-bulk-bar--bottom">
			<select name="bulk_action_bottom">
				<option value="">— Hromadná akce —</option>
				<option value="delete">Smazat vybrané</option>
				<option value="export">Exportovat vybrané (CSV)</option>
			</select>
			<button type="button" class="button" id="ecalc-bulk-submit-bottom">Provést</button>
		</div>

	</form>

	<!-- ====== STRÁNKOVÁNÍ ====== -->
	<?php if ( $total_pages > 1 ) : ?>
		<div class="ecalc-pagination">
			<?php for ( $p = 1; $p <= $total_pages; $p++ ) : ?>
				<a href="<?php echo esc_url( $filter_url( [ 'paged' => $p ] ) ); ?>"
					class="button <?php echo $p === $page ? 'button-primary' : ''; ?>">
					<?php echo (int) $p; ?>
				</a>
			<?php endfor; ?>
		</div>
	<?php endif; ?>

	<!-- ====== EXPORT ====== -->
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ecalc-export-form">
		<input type="hidden" name="action" value="ecalc_export_csv">
		<?php wp_nonce_field( 'ecalc_export_csv' ); ?>
		<?php foreach ( $filters as $fkey => $fval ) :
			if ( is_array( $fval ) ) :
				foreach ( $fval as $fitem ) : ?>
					<input type="hidden" name="<?php echo esc_attr( $fkey ); ?>[]" value="<?php echo esc_attr( $fitem ); ?>">
				<?php endforeach;
			else : ?>
				<input type="hidden" name="<?php echo esc_attr( $fkey ); ?>" value="<?php echo esc_attr( $fval ); ?>">
			<?php endif;
		endforeach; ?>
		<button type="submit" class="button">
			<?php echo $has_any_filter ? 'Exportovat filtrované leady (CSV)' : 'Exportovat všechny leady (CSV)'; ?>
		</button>
	</form>

	<?php endif; ?>
</div>

<script>
(function () {
	// Show/hide custom date range
	var periodSel = document.getElementById('ecalc-leads-period');
	var customRange = document.getElementById('ecalc-leads-custom-range');
	if (periodSel && customRange) {
		periodSel.addEventListener('change', function () {
			customRange.style.display = this.value === 'custom' ? 'flex' : 'none';
		});
	}

	// Multi-select widgets
	var allMs = document.querySelectorAll('.ecalc-ms');

	function closeAll() {
		allMs.forEach(function (ms) {
			ms.querySelector('.ecalc-ms-btn').setAttribute('aria-expanded', 'false');
			ms.querySelector('.ecalc-ms-panel').hidden = true;
		});
	}

	allMs.forEach(function (ms) {
		var btn         = ms.querySelector('.ecalc-ms-btn');
		var panel       = ms.querySelector('.ecalc-ms-panel');
		var labelEl     = ms.querySelector('.ecalc-ms-label');
		var placeholder = ms.dataset.placeholder;

		function updateLabel() {
			var checked = panel.querySelectorAll('input:checked');
			if (checked.length === 0) {
				labelEl.textContent = placeholder;
				btn.classList.remove('ecalc-ms-active');
			} else if (checked.length === 1) {
				labelEl.textContent = checked[0].closest('label').textContent.trim();
				btn.classList.add('ecalc-ms-active');
			} else {
				labelEl.textContent = checked.length + ' vybrané';
				btn.classList.add('ecalc-ms-active');
			}
		}

		btn.addEventListener('click', function (e) {
			e.stopPropagation();
			var isOpen = btn.getAttribute('aria-expanded') === 'true';
			closeAll();
			if (!isOpen) {
				btn.setAttribute('aria-expanded', 'true');
				panel.hidden = false;
			}
		});

		panel.addEventListener('click', function (e) {
			e.stopPropagation();
		});

		panel.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
			cb.addEventListener('change', updateLabel);
		});
	});

	document.addEventListener('click', closeAll);
})();
</script>
