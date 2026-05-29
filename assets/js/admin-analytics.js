/* Emailing Calculator – admin analytics dashboard */
(function () {
	'use strict';

	var cfg            = window.ecalcAnalyticsData || {};
	var chart          = null;
	var showPrediction = false;
	var currentData    = null;

	document.addEventListener('DOMContentLoaded', function () {
		if (!window.ecalcAnalyticsInitData) return;
		init(window.ecalcAnalyticsInitData);
	});

	// -------------------------------------------------------------------------
	// Init
	// -------------------------------------------------------------------------

	function init(data) {
		currentData = data;
		// Sync granularity select with server-resolved default.
		if (data.granularity) {
			var gran = document.getElementById('ecalc-filter-granularity');
			if (gran) gran.value = data.granularity;
		}
		bindFilters();
		bindPredictionToggle();
		renderChart(data.series || {}, null);
		updateCards(data.summary || {}, data.prev_sum || {});
		updateBreakdowns(data);
	}

	// -------------------------------------------------------------------------
	// Prediction toggle
	// -------------------------------------------------------------------------

	function bindPredictionToggle() {
		var btn = document.getElementById('ecalc-prediction-toggle');
		if (!btn) return;
		btn.addEventListener('click', function () {
			showPrediction = !showPrediction;
			this.classList.toggle('ecalc-btn-active', showPrediction);
			this.textContent = showPrediction ? '📈 Predikce: ZAP' : '📈 Predikce';
			if (currentData) {
				renderChart(
					currentData.series || {},
					showPrediction ? (currentData.prediction_series || null) : null
				);
			}
		});
	}

	// -------------------------------------------------------------------------
	// Chart
	// -------------------------------------------------------------------------

	function renderChart(series, prediction) {
		var wrap = document.querySelector('.ecalc-chart-wrap');
		if (!wrap) return;

		var labels     = series.labels || [];
		var hasPred    = !!(prediction && (prediction.labels || []).length && labels.length);
		var predLabels = hasPred ? prediction.labels : [];
		var allLabels  = labels.concat(predLabels);
		var currentLen = labels.length;
		var predLen    = predLabels.length;

		if (!allLabels.length) {
			if (chart) { chart.destroy(); chart = null; }
			wrap.innerHTML = '<p class="ecalc-chart-empty">Žádná data pro zvolené období.</p>';
			return;
		}

		var ctx = document.getElementById('ecalc-chart');
		if (!ctx) {
			wrap.innerHTML = '';
			ctx = document.createElement('canvas');
			ctx.id = 'ecalc-chart';
			wrap.appendChild(ctx);
		}
		if (chart) { chart.destroy(); chart = null; }

		var ptRadius = allLabels.length > 60 ? 0 : 3;

		var newLeadsArr = series.new_leads      || [];
		var ctaArr      = series.cta_clicks     || [];
		var bookArr     = series.bookings        || [];
		var inqArr      = series.inquiries       || [];
		var recalcArr   = series.recalculations  || [];

		var newLeadsActual = newLeadsArr.concat(Array(predLen).fill(null));
		var ctaActual      = ctaArr.concat(Array(predLen).fill(null));
		var bookActual     = bookArr.concat(Array(predLen).fill(null));
		var inqActual      = inqArr.concat(Array(predLen).fill(null));
		var recalcActual   = recalcArr.concat(Array(predLen).fill(null));

		var datasets = [
			{
				label: 'Nové leady',
				data: newLeadsActual,
				borderColor: '#4f46e5',
				backgroundColor: 'rgba(79,70,229,0.07)',
				fill: true,
				tension: 0.35,
				pointRadius: ptRadius,
				borderWidth: 2,
			},
			{
				label: 'Poptávky balíčků',
				data: inqActual,
				borderColor: '#0ea5e9',
				backgroundColor: 'transparent',
				fill: false,
				tension: 0.35,
				pointRadius: ptRadius,
				borderWidth: 2,
				hidden: true,
			},
			{
				label: 'CTA kliky',
				data: ctaActual,
				borderColor: '#f97316',
				backgroundColor: 'transparent',
				fill: false,
				tension: 0.35,
				pointRadius: ptRadius,
				borderWidth: 2,
				hidden: true,
			},
			{
				label: 'Rezervace',
				data: bookActual,
				borderColor: '#16a34a',
				backgroundColor: 'transparent',
				fill: false,
				tension: 0.35,
				pointRadius: ptRadius,
				borderWidth: 2,
				hidden: true,
			},
			{
				label: 'Přepočty',
				data: recalcActual,
				borderColor: '#94a3b8',
				backgroundColor: 'transparent',
				fill: false,
				tension: 0.35,
				pointRadius: allLabels.length > 60 ? 0 : 2,
				borderWidth: 1.5,
				borderDash: [6, 4],
				hidden: true,
			},
		];

		if (hasPred) {
			var nullPfx    = Array(currentLen - 1).fill(null);
			var lastLeads  = newLeadsArr[currentLen - 1];
			var lastInq    = inqArr[currentLen - 1];
			var lastCta    = ctaArr[currentLen - 1];
			var lastBook   = bookArr[currentLen - 1];
			var lastRecalc = recalcArr[currentLen - 1];
			var predPt     = allLabels.length > 60 ? 0 : 2;

			datasets.push(
				{
					label: 'Leady (předpověď)',
					data: nullPfx.concat([lastLeads]).concat(prediction.new_leads || []),
					borderColor: 'rgba(79,70,229,0.45)',
					backgroundColor: 'transparent',
					fill: false,
					tension: 0.35,
					pointRadius: predPt,
					borderWidth: 1.5,
					borderDash: [5, 5],
				},
				{
					label: 'Poptávky (předpověď)',
					data: nullPfx.concat([lastInq]).concat(prediction.inquiries || []),
					borderColor: 'rgba(14,165,233,0.45)',
					backgroundColor: 'transparent',
					fill: false,
					tension: 0.35,
					pointRadius: predPt,
					borderWidth: 1.5,
					borderDash: [5, 5],
					hidden: true,
				},
				{
					label: 'CTA (předpověď)',
					data: nullPfx.concat([lastCta]).concat(prediction.cta_clicks || []),
					borderColor: 'rgba(249,115,22,0.45)',
					backgroundColor: 'transparent',
					fill: false,
					tension: 0.35,
					pointRadius: predPt,
					borderWidth: 1.5,
					borderDash: [5, 5],
					hidden: true,
				},
				{
					label: 'Rezervace (předpověď)',
					data: nullPfx.concat([lastBook]).concat(prediction.bookings || []),
					borderColor: 'rgba(22,163,74,0.45)',
					backgroundColor: 'transparent',
					fill: false,
					tension: 0.35,
					pointRadius: predPt,
					borderWidth: 1.5,
					borderDash: [5, 5],
					hidden: true,
				},
				{
					label: 'Přepočty (předpověď)',
					data: nullPfx.concat([lastRecalc]).concat(prediction.recalculations || []),
					borderColor: 'rgba(148,163,184,0.45)',
					backgroundColor: 'transparent',
					fill: false,
					tension: 0.35,
					pointRadius: predPt,
					borderWidth: 1.5,
					borderDash: [5, 5],
					hidden: true,
				}
			);
		}

		chart = new Chart(ctx, {
			type: 'line',
			data: { labels: allLabels, datasets: datasets },
			options: {
				responsive: true,
				maintainAspectRatio: false,
				interaction: { mode: 'index', intersect: false },
				plugins: {
					legend: {
						position: 'top',
						labels: { usePointStyle: true, padding: 20, font: { size: 12 } },
					},
					tooltip: { enabled: true },
				},
				scales: {
					x: {
						grid: { color: 'rgba(0,0,0,0.04)' },
						ticks: { maxRotation: 45, font: { size: 11 } },
					},
					y: {
						beginAtZero: true,
						grid: { color: 'rgba(0,0,0,0.06)' },
						ticks: {
							stepSize: 1,
							precision: 0,
							font: { size: 11 },
							callback: function (v) { return Number.isInteger(v) ? v : null; },
						},
					},
				},
			},
		});
	}

	// -------------------------------------------------------------------------
	// Metric cards
	// -------------------------------------------------------------------------

	function calcTrend(current, prev) {
		if (prev === 0 && current === 0) return null;
		if (prev === 0) return { pct: null, dir: 'up' };
		var pct = Math.round((current - prev) / prev * 100);
		return { pct: Math.abs(pct), dir: pct >= 0 ? 'up' : 'down' };
	}

	function renderTrend(el, trend) {
		if (!el) return;
		if (!trend) { el.innerHTML = ''; return; }
		var arrow = trend.dir === 'up' ? '↑' : '↓';
		var cls   = trend.dir === 'up' ? 'ecalc-trend--up' : 'ecalc-trend--down';
		var label = trend.pct !== null ? arrow + ' ' + trend.pct + '%' : arrow;
		el.innerHTML = '<span class="ecalc-trend ' + cls + '" title="Srovnání s předchozím obdobím">' + label + '</span>';
	}

	function updateCard(id, current, prev, invertTrend) {
		var card = document.getElementById('ecalc-metric-' + id);
		if (!card) return;
		var valEl   = card.querySelector('.ecalc-metric-value');
		var trendEl = card.querySelector('.ecalc-metric-trend');
		if (valEl) valEl.textContent = current;
		if (trendEl) {
			var trend = calcTrend(current, prev);
			if (trend && invertTrend) trend.dir = trend.dir === 'up' ? 'down' : 'up';
			renderTrend(trendEl, trend);
		}
	}

	function updateCards(s, p) {
		updateCard('views',     s.total_views    || 0, p.total_views    || 0);
		updateCard('leads',     s.leads_count    || 0, p.leads_count    || 0);
		updateCard('inquiries', s.inquiries      || 0, p.inquiries      || 0);
		updateCard('bookings',  s.bookings       || 0, p.bookings       || 0);
		updateCard('cta',       s.cta_clicks     || 0, p.cta_clicks     || 0);
		updateCard('recalcs',   s.recalculations || 0, p.recalculations || 0, true);

		setText('ecalc-stat-lead-value',    fmtCurrency(s.avg_lead_value || 0));
		setText('ecalc-stat-consultations', s.consultations || 0);
		setText('ecalc-stat-potential',     fmtPct(s.avg_potential || 0));
		setText('ecalc-stat-revenue',       fmtCurrency(s.avg_revenue || 0));

		var cr = s.conversion_rate !== undefined ? s.conversion_rate : null;
		setText('ecalc-stat-conversion', (cr !== null && s.total_views > 0)
			? parseFloat(cr).toFixed(1).replace('.', ',') + ' %'
			: '—');

		setText('ecalc-stat-session-time', fmtDuration(s.avg_session_time  || 0));
		setText('ecalc-stat-submit-time',  fmtDuration(s.avg_time_to_submit || 0));
		setText('ecalc-stat-scroll',       s.avg_scroll_pct > 0 ? s.avg_scroll_pct + ' %' : '—');
	}

	function setText(id, value) {
		var el = document.getElementById(id);
		if (el) el.textContent = value;
	}

	function fmtPct(v) {
		return parseFloat(v).toFixed(1).replace('.', ',') + '%';
	}

	function fmtCurrency(v) {
		return new Intl.NumberFormat('cs-CZ', {
			style: 'currency', currency: 'CZK', maximumFractionDigits: 0,
		}).format(Math.round(v));
	}

	function fmtDuration(s) {
		var sec = Math.round(s) || 0;
		if (sec <= 0) return '—';
		if (sec < 60) return sec + ' s';
		var m = Math.floor(sec / 60), r = sec % 60;
		return r > 0 ? m + ' min ' + r + ' s' : m + ' min';
	}

	// -------------------------------------------------------------------------
	// Breakdown tables
	// -------------------------------------------------------------------------

	function updateBreakdowns(data) {
		renderBreakdown('ecalc-breakdown-status',   data.statuses       || [], 'label',   'count', data.prev_statuses   || [], 'status');
		renderBreakdown('ecalc-breakdown-results',  data.results        || [], 'label',   'count', data.prev_results    || [], 'result_type');
		renderBreakdown('ecalc-breakdown-segments', data.segments       || [], 'segment', 'count', data.prev_segments   || [], 'segment');
		renderBreakdown('ecalc-breakdown-db-ranges',data.db_ranges      || [], 'label',   'count', data.prev_db_ranges  || [], 'db_range');
		renderBreakdown('ecalc-breakdown-traffic',     data.traffic_sources   || [], 'label', 'count', [], 'source');
		renderBreakdown('ecalc-breakdown-abandonment', data.abandonment_steps || [], 'label', 'count', [], 'step');
	}

	function renderBreakdown(id, rows, labelKey, countKey, prevRows, keyField) {
		var el = document.getElementById(id);
		if (!el) return;

		if (!rows.length) {
			el.innerHTML = '<p class="ecalc-breakdown-empty">Žádná data</p>';
			return;
		}

		var prevMap = {};
		(prevRows || []).forEach(function (r) { prevMap[String(r[keyField])] = r[countKey] || 0; });

		var total = rows.reduce(function (sum, r) { return sum + (r[countKey] || 0); }, 0);
		var html  = '<table class="ecalc-breakdown-table">';
		rows.forEach(function (row) {
			var cnt  = row[countKey] || 0;
			var prev = prevMap[String(row[keyField])] || 0;
			var pct  = total > 0 ? Math.round(cnt / total * 100) : 0;
			html += '<tr>'
				+ '<td class="ecalc-bd-label">' + escHtml(String(row[labelKey] || '—')) + '</td>'
				+ '<td class="ecalc-bd-bar"><div class="ecalc-bar"><div class="ecalc-bar-fill" style="width:' + pct + '%"></div></div></td>'
				+ '<td class="ecalc-bd-count">' + cnt + '</td>'
				+ '<td class="ecalc-bd-trend">' + bdTrend(cnt, prev) + '</td>'
				+ '<td class="ecalc-bd-pct">' + pct + '%</td>'
				+ '</tr>';
		});
		html += '</table>';
		el.innerHTML = html;
	}

	function bdTrend(current, prev) {
		var diff = current - prev;
		if (diff === 0 && prev === 0) return '';
		if (diff === 0) return '<span class="ecalc-bd-neutral" title="Beze změny">→</span>';
		var sign  = diff > 0 ? '+' : '';
		var cls   = diff > 0 ? 'ecalc-bd-up' : 'ecalc-bd-down';
		var arrow = diff > 0 ? '↑' : '↓';
		return '<span class="' + cls + '" title="Oproti předchozímu období">' + arrow + sign + diff + '</span>';
	}

	function escHtml(str) {
		return str
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	// -------------------------------------------------------------------------
	// Filters & AJAX
	// -------------------------------------------------------------------------

	var PERIOD_GRANULARITY = {
		this_week:  'day',
		last_week:  'day',
		this_month: 'week',
		last_month: 'week',
		this_year:  'month',
		last_year:  'month',
		all:        'month',
	};

	function bindFilters() {
		var periodSel    = document.getElementById('ecalc-filter-period');
		var granularSel  = document.getElementById('ecalc-filter-granularity');
		var customRange  = document.getElementById('ecalc-custom-range');

		if (periodSel) {
			periodSel.addEventListener('change', function () {
				if (customRange) {
					customRange.style.display = this.value === 'custom' ? 'flex' : 'none';
				}
				// Auto-set granularity default for this period.
				var defaultGran = PERIOD_GRANULARITY[this.value];
				if (defaultGran && granularSel) {
					granularSel.value = defaultGran;
				}
				if (this.value !== 'custom') {
					loadData();
				}
			});
		}

		var applyBtn = document.getElementById('ecalc-apply-filters');
		if (applyBtn) {
			applyBtn.addEventListener('click', loadData);
		}

		['ecalc-filter-granularity', 'ecalc-filter-segment',
		 'ecalc-filter-status', 'ecalc-filter-result', 'ecalc-filter-package', 'ecalc-filter-cta-type'
		].forEach(function (id) {
			var el = document.getElementById(id);
			if (el) el.addEventListener('change', loadData);
		});
	}

	function getVal(id) {
		var el = document.getElementById(id);
		return el ? el.value : '';
	}

	function loadData() {
		var loading = document.getElementById('ecalc-loading');
		if (loading) loading.style.display = 'block';

		var params = new URLSearchParams();
		params.append('action',             'ecalc_get_analytics');
		params.append('nonce',              cfg.nonce || '');
		params.append('period',             getVal('ecalc-filter-period'));
		params.append('date_from',          getVal('ecalc-filter-date-from'));
		params.append('date_to',            getVal('ecalc-filter-date-to'));
		params.append('granularity',        getVal('ecalc-filter-granularity'));
		params.append('filter_segment',     getVal('ecalc-filter-segment'));
		params.append('filter_status',      getVal('ecalc-filter-status'));
		params.append('filter_result_type', getVal('ecalc-filter-result'));
		params.append('filter_package',     getVal('ecalc-filter-package'));
		params.append('filter_cta_type',    getVal('ecalc-filter-cta-type'));

		fetch(cfg.ajaxurl, {
			method:  'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body:    params.toString(),
		})
		.then(function (r) { return r.json(); })
		.then(function (json) {
			if (loading) loading.style.display = 'none';
			if (json.success && json.data) {
				currentData = json.data;
				renderChart(
					json.data.series || {},
					showPrediction ? (json.data.prediction_series || null) : null
				);
				updateCards(json.data.summary || {}, json.data.prev_sum || {});
				updateBreakdowns(json.data);
			}
		})
		.catch(function () {
			if (loading) loading.style.display = 'none';
		});
	}

})();
