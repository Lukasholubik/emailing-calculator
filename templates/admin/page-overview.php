<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap ecalc-admin ecalc-overview">
	<h1>Přehledy</h1>

	<!-- Filters -->
	<div class="ecalc-overview-filters">
		<div class="ecalc-filter-row">
			<label class="ecalc-filter-label">Období
				<select id="ecalc-filter-period">
					<option value="this_week">Tento týden</option>
					<option value="last_week">Minulý týden</option>
					<option value="this_month" selected>Tento měsíc</option>
					<option value="last_month">Minulý měsíc</option>
					<option value="this_year">Tento rok</option>
					<option value="last_year">Minulý rok</option>
					<option value="custom">Vlastní rozsah</option>
					<option value="all">Vše</option>
				</select>
			</label>

			<div id="ecalc-custom-range" class="ecalc-custom-range" style="display:none;">
				<label class="ecalc-filter-label">Od
					<input type="date" id="ecalc-filter-date-from" value="">
				</label>
				<label class="ecalc-filter-label">Do
					<input type="date" id="ecalc-filter-date-to" value="">
				</label>
			</div>

			<label class="ecalc-filter-label">Granularita
				<select id="ecalc-filter-granularity">
					<option value="day">Den</option>
					<option value="week" selected>Týden</option>
					<option value="month">Měsíc</option>
					<option value="year">Rok</option>
				</select>
			</label>

			<label class="ecalc-filter-label">Oblast
				<select id="ecalc-filter-segment">
					<option value="">Vše</option>
					<?php foreach ( $data['filter_opts']['segments'] as $seg ) : ?>
						<option value="<?php echo esc_attr( $seg ); ?>"><?php echo esc_html( $seg ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>

			<label class="ecalc-filter-label">Stav leadu
				<select id="ecalc-filter-status">
					<option value="">Vše</option>
					<?php foreach ( $data['filter_opts']['statuses'] as $val => $lbl ) : ?>
						<option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $lbl ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>

			<label class="ecalc-filter-label">Výsledek
				<select id="ecalc-filter-result">
					<option value="">Vše</option>
					<?php foreach ( $data['filter_opts']['results'] as $val => $lbl ) : ?>
						<option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $lbl ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>

			<label class="ecalc-filter-label">Typ konverze
				<select id="ecalc-filter-cta-type">
					<option value="">Vše</option>
					<option value="package">Poptávka balíčku</option>
					<option value="consultation">Konzultace</option>
				</select>
			</label>

			<?php if ( ! empty( $data['filter_opts']['packages'] ) ) : ?>
			<label class="ecalc-filter-label">Balíček
				<select id="ecalc-filter-package">
					<option value="">Vše</option>
					<?php foreach ( $data['filter_opts']['packages'] as $pkg ) : ?>
						<option value="<?php echo esc_attr( $pkg ); ?>"><?php echo esc_html( $pkg ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<?php else : ?>
			<input type="hidden" id="ecalc-filter-package" value="">
			<?php endif; ?>

			<button type="button" class="button button-primary" id="ecalc-apply-filters">Použít filtry</button>
			<button type="button" class="button ecalc-prediction-btn" id="ecalc-prediction-toggle">📈 Predikce</button>
		</div>
		<div id="ecalc-loading" class="ecalc-overview-loading" style="display:none;">
			<span class="spinner is-active"></span> Načítám data&hellip;
		</div>
	</div>

	<!-- Metric cards -->
	<?php
	$total_views     = (int) ( $data['summary']['total_views']    ?? 0 );
	$leads_count     = (int) ( $data['summary']['leads_count']    ?? 0 );
	$conversion_rate = $data['summary']['conversion_rate'] ?? 0;
	$conv_fmt        = $total_views > 0
		? number_format( (float) $conversion_rate, 1, ',', ' ' ) . ' %'
		: '—';
	?>
	<div class="ecalc-metric-cards">
		<div class="ecalc-metric-card" id="ecalc-metric-views">
			<div class="ecalc-metric-icon">&#128065;</div>
			<div class="ecalc-metric-body">
				<div class="ecalc-metric-label">Zobrazení kalkulačky</div>
				<div class="ecalc-metric-value"><?php echo $total_views; ?></div>
			</div>
			<div class="ecalc-metric-trend"></div>
		</div>
		<div class="ecalc-metric-card" id="ecalc-metric-leads">
			<div class="ecalc-metric-icon">&#128100;</div>
			<div class="ecalc-metric-body">
				<div class="ecalc-metric-label">Nové leady</div>
				<div class="ecalc-metric-value"><?php echo $leads_count; ?></div>
			</div>
			<div class="ecalc-metric-trend"></div>
		</div>
		<div class="ecalc-metric-card ecalc-metric-card--highlight" id="ecalc-metric-inquiries">
			<div class="ecalc-metric-icon">&#128230;</div>
			<div class="ecalc-metric-body">
				<div class="ecalc-metric-label">Poptávky balíčků</div>
				<div class="ecalc-metric-value"><?php echo (int) ( $data['summary']['inquiries'] ?? 0 ); ?></div>
			</div>
			<div class="ecalc-metric-trend"></div>
		</div>
		<div class="ecalc-metric-card ecalc-metric-card--highlight" id="ecalc-metric-bookings">
			<div class="ecalc-metric-icon">&#128197;</div>
			<div class="ecalc-metric-body">
				<div class="ecalc-metric-label">Rezervace schůzky</div>
				<div class="ecalc-metric-value"><?php echo (int) ( $data['summary']['bookings'] ?? 0 ); ?></div>
			</div>
			<div class="ecalc-metric-trend"></div>
		</div>
		<div class="ecalc-metric-card" id="ecalc-metric-cta">
			<div class="ecalc-metric-icon">&#128432;</div>
			<div class="ecalc-metric-body">
				<div class="ecalc-metric-label">CTA kliky</div>
				<div class="ecalc-metric-value"><?php echo (int) ( $data['summary']['cta_clicks'] ?? 0 ); ?></div>
			</div>
			<div class="ecalc-metric-trend"></div>
		</div>
		<div class="ecalc-metric-card" id="ecalc-metric-recalcs">
			<div class="ecalc-metric-icon">&#128260;</div>
			<div class="ecalc-metric-body">
				<div class="ecalc-metric-label">Přepočty</div>
				<div class="ecalc-metric-value"><?php echo (int) ( $data['summary']['recalculations'] ?? 0 ); ?></div>
			</div>
			<div class="ecalc-metric-trend"></div>
		</div>
	</div>

	<!-- Secondary stats bar -->
	<?php
	$fmt_dur = fn( $s ) => ( $s = (int) round( $s ) ) <= 0 ? '—'
		: ( $s < 60 ? "{$s} s" : ( (int) floor( $s / 60 ) . ' min' . ( $s % 60 > 0 ? ' ' . ( $s % 60 ) . ' s' : '' ) ) );
	$avg_session    = (int) ( $data['summary']['avg_session_time']   ?? 0 );
	$avg_submit     = (int) ( $data['summary']['avg_time_to_submit'] ?? 0 );
	$avg_scroll     = (int) ( $data['summary']['avg_scroll_pct']     ?? 0 );
	?>
	<div class="ecalc-secondary-stats">
		<span>Konverzní poměr:
			<strong id="ecalc-stat-conversion"><?php echo esc_html( $conv_fmt ); ?></strong>
			<span class="ecalc-stat-hint" title="Podíl leadů k celkovému počtu zobrazení kalkulačky v daném období.">ⓘ</span>
		</span>
		<span class="ecalc-stat-sep">·</span>
		<span>Prům. čas na stránce:
			<strong id="ecalc-stat-session-time"><?php echo esc_html( $fmt_dur( $avg_session ) ); ?></strong>
			<span class="ecalc-stat-hint" title="Průměrná doba od načtení kalkulačky do zavření/odchodu ze stránky (přibližné).">ⓘ</span>
		</span>
		<span class="ecalc-stat-sep">·</span>
		<span>Prům. čas do odeslání:
			<strong id="ecalc-stat-submit-time"><?php echo esc_html( $fmt_dur( $avg_submit ) ); ?></strong>
			<span class="ecalc-stat-hint" title="Průměrná doba od načtení kalkulačky do kliknutí na odeslat (pouze konvertující leady).">ⓘ</span>
		</span>
		<span class="ecalc-stat-sep">·</span>
		<span>Prům. hloubka scrollu:
			<strong id="ecalc-stat-scroll"><?php echo $avg_scroll > 0 ? $avg_scroll . ' %' : '—'; ?></strong>
		</span>
		<span class="ecalc-stat-sep">·</span>
		<span>Prům. hodnota leadu:
			<strong id="ecalc-stat-lead-value"><?php echo number_format( (float) ( $data['summary']['avg_lead_value'] ?? 0 ), 0, ',', ' ' ); ?> Kč</strong>
			<span class="ecalc-stat-hint" title="Průměr cen poptaných balíčků + 2 000 Kč za každou rezervaci konzultace. Zahrnuje pouze leady s konverzí.">ⓘ</span>
		</span>
		<span class="ecalc-stat-sep">·</span>
		<span>CTA konzultace: <strong id="ecalc-stat-consultations"><?php echo (int) ( $data['summary']['consultations'] ?? 0 ); ?></strong></span>
		<span class="ecalc-stat-sep">·</span>
		<span>Průměrný potenciál: <strong id="ecalc-stat-potential"><?php echo number_format( (float) ( $data['summary']['avg_potential'] ?? 0 ), 1, ',', ' ' ); ?>%</strong></span>
		<span class="ecalc-stat-sep">·</span>
		<span>Průměrný obrat: <strong id="ecalc-stat-revenue"><?php echo number_format( (float) ( $data['summary']['avg_revenue'] ?? 0 ), 0, ',', ' ' ); ?> Kč</strong></span>
	</div>

	<!-- Chart -->
	<div class="ecalc-chart-wrap">
		<canvas id="ecalc-chart" height="300"></canvas>
	</div>

	<!-- Breakdowns -->
	<div class="ecalc-breakdowns">
		<div class="ecalc-breakdown-section">
			<h3>Stav leadů</h3>
			<div id="ecalc-breakdown-status" class="ecalc-breakdown-body"></div>
		</div>
		<div class="ecalc-breakdown-section">
			<h3>Typ výsledku</h3>
			<div id="ecalc-breakdown-results" class="ecalc-breakdown-body"></div>
		</div>
		<div class="ecalc-breakdown-section">
			<h3>Oblasti podnikání <small>(top 10)</small></h3>
			<div id="ecalc-breakdown-segments" class="ecalc-breakdown-body"></div>
		</div>
		<div class="ecalc-breakdown-section">
			<h3>Velikost databáze <small>(top 10)</small></h3>
			<div id="ecalc-breakdown-db-ranges" class="ecalc-breakdown-body"></div>
		</div>
		<div class="ecalc-breakdown-section">
			<h3>Zdroj návštěvnosti <small>(dle UTM / referreru)</small></h3>
			<div id="ecalc-breakdown-traffic" class="ecalc-breakdown-body"></div>
		</div>
		<div class="ecalc-breakdown-section">
			<h3>Fáze opuštění <small>(bez odeslání formuláře)</small></h3>
			<div id="ecalc-breakdown-abandonment" class="ecalc-breakdown-body"></div>
		</div>
	</div>
</div>

<script>
window.ecalcAnalyticsInitData = <?php echo wp_json_encode( $data, JSON_HEX_TAG ); ?>;
</script>
