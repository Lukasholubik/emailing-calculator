/* === Emailing Calculator – Frontend JS === */
(function () {
	'use strict';

	var cfg = window.ecalcData || {};
	var strings = cfg.strings || {};

	// -------------------------------------------------------------------------
	// Engagement tracking state
	// -------------------------------------------------------------------------
	var pageLoadTime    = Date.now();
	var furthestStep    = 0;
	var formConverted   = false;
	var exitBeaconFired = false;
	var maxScrollPct    = 0;
	var ABANDON_STEPS   = ['initial', 'name', 'email', 'shop_url', 'segment', 'database', 'revenue', 'consumable', 'pno', 'consent'];

	// -------------------------------------------------------------------------
	// GTM dataLayer helper
	// -------------------------------------------------------------------------
	function gtm(event, data) {
		window.dataLayer = window.dataLayer || [];
		var payload = { event: event };
		if (data) {
			Object.keys(data).forEach(function (k) { payload[k] = data[k]; });
		}
		window.dataLayer.push(payload);
	}

	// -------------------------------------------------------------------------
	// Init
	// -------------------------------------------------------------------------
	document.addEventListener('DOMContentLoaded', function () {
		captureUtm();
		trackView();
		initStepTracking();
		initSlider();
		initRevenueToggle();
		initRevenueExactFormat();
		initForm();
	});

	function captureUtm() {
		try {
			var params = new URLSearchParams(window.location.search);
			['utm_source', 'utm_medium', 'utm_campaign'].forEach(function (k) {
				var v = params.get(k);
				if (v) sessionStorage.setItem('ecalc_' + k, v);
			});
		} catch (e) {}
	}

	function trackView() {
		if (!cfg.trackViewUrl) return;
		try {
			fetch(cfg.trackViewUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
				keepalive: true,
			});
		} catch (e) {}
	}

	function getUtm(key) {
		try { return sessionStorage.getItem('ecalc_' + key) || ''; } catch (e) { return ''; }
	}

	function reachStep(step) {
		var idx = ABANDON_STEPS.indexOf(step);
		if (idx > furthestStep) furthestStep = idx;
		updateProgressUI();
	}

	function updateProgressUI() {
		var fillEl = document.getElementById('ecalc-progress-fill');
		var textEl = document.getElementById('ecalc-progress-text');
		if (!fillEl || !textEl) return;

		var totalSteps = ABANDON_STEPS.length - 1; // 'initial' není krok navíc
		var pct = Math.round((furthestStep / totalSteps) * 100);
		fillEl.style.width = pct + '%';
		textEl.textContent = pct >= 100 ? 'Formulář je kompletní' : 'Vyplněno ' + pct + ' %';
	}

	function initStepTracking() {
		if (!document.getElementById('ecalc-form')) return;

		var fieldSteps = [
			['ecalc-name',              'focus',  'name'],
			['ecalc-email',             'focus',  'email'],
			['ecalc-shop-url',          'focus',  'shop_url'],
			['ecalc-segment',           'change', 'segment'],
			['ecalc-database-range',    'change', 'database'],
			['ecalc-revenue-range',     'change', 'revenue'],
			['ecalc-revenue-exact',     'focus',  'revenue'],
			['ecalc-consumable-slider', 'input',  'consumable'],
			['ecalc-pno',               'focus',  'pno'],
			['ecalc-consent-data',      'change', 'consent'],
		];
		fieldSteps.forEach(function (triple) {
			var el = document.getElementById(triple[0]);
			if (el) el.addEventListener(triple[1], function () { reachStep(triple[2]); }, { once: false, passive: true });
		});

		// Scroll depth
		window.addEventListener('scroll', function () {
			var pct = Math.round((window.scrollY + window.innerHeight) / Math.max(1, document.documentElement.scrollHeight) * 100);
			if (pct > maxScrollPct) maxScrollPct = Math.min(100, pct);
		}, { passive: true });

		// Exit beacon
		var fireExit = function () {
			if (exitBeaconFired || !cfg.trackExitUrl) return;
			exitBeaconFired = true;
			var payload = JSON.stringify({
				last_step:        ABANDON_STEPS[furthestStep],
				time_spent_s:     Math.round((Date.now() - pageLoadTime) / 1000),
				scroll_depth_pct: maxScrollPct,
				converted:        formConverted,
			});
			try {
				if (navigator.sendBeacon) {
					navigator.sendBeacon(cfg.trackExitUrl, new Blob([payload], { type: 'application/json' }));
				} else {
					fetch(cfg.trackExitUrl, {
						method: 'POST',
						headers: { 'Content-Type': 'application/json' },
						body: payload,
						keepalive: true,
					});
				}
			} catch (e) {}
		};

		document.addEventListener('visibilitychange', function () {
			if (document.visibilityState === 'hidden') fireExit();
		});
		window.addEventListener('pagehide', fireExit);
	}

	// -------------------------------------------------------------------------
	// Slider – consumable percentage (bidirectional: slider ↔ both inputs)
	// -------------------------------------------------------------------------
	var _sliderEl, _sliderHidden, _sliderConsEl, _sliderNonEl;

	function applySliderValue(v, skipCons, skipNon) {
		v = Math.max(0, Math.min(100, isNaN(parseInt(v, 10)) ? 0 : parseInt(v, 10)));
		if (!_sliderEl) return;
		_sliderEl.value     = v;
		_sliderHidden.value = v;
		_sliderEl.style.setProperty('--ecalc-fill-pct', v + '%');
		if (!skipCons) _sliderConsEl.value = v;
		if (!skipNon)  _sliderNonEl.value  = 100 - v;
	}

	function initSlider() {
		_sliderEl      = document.getElementById('ecalc-consumable-slider');
		_sliderHidden  = document.getElementById('ecalc-consumable-percentage');
		_sliderConsEl  = document.getElementById('ecalc-consumable-pct');
		_sliderNonEl   = document.getElementById('ecalc-non-consumable-pct');

		if (!_sliderEl) return;

		applySliderValue(_sliderEl.value);

		_sliderEl.addEventListener('input', function () {
			applySliderValue(this.value);
		});

		// Consumable input typed – update other side and slider, keep cursor in this field
		_sliderConsEl.addEventListener('input', function () {
			var v = parseInt(this.value, 10);
			if (!isNaN(v)) applySliderValue(v, true, false);
		});
		_sliderConsEl.addEventListener('blur', function () {
			applySliderValue(parseInt(this.value, 10) || 0);
		});

		// Non-consumable input typed – invert and apply
		_sliderNonEl.addEventListener('input', function () {
			var v = parseInt(this.value, 10);
			if (!isNaN(v)) applySliderValue(100 - Math.max(0, Math.min(100, v)), false, true);
		});
		_sliderNonEl.addEventListener('blur', function () {
			var v = Math.max(0, Math.min(100, parseInt(this.value, 10) || 0));
			applySliderValue(100 - v);
		});
	}

	// -------------------------------------------------------------------------
	// Revenue exact – thousands formatting (e.g. 2 000 000)
	// -------------------------------------------------------------------------
	function fmtRevenue(raw) {
		var digits = String(raw).replace(/\D/g, '');
		if (!digits) return '';
		return parseInt(digits, 10).toLocaleString('cs-CZ', { maximumFractionDigits: 0 });
	}

	function parseRevenue(str) {
		return parseInt(String(str).replace(/\s/g, '').replace(/,/g, ''), 10) || 0;
	}

	function initRevenueExactFormat() {
		var input = document.getElementById('ecalc-revenue-exact');
		if (!input) return;

		input.addEventListener('input', function () {
			var cursor    = this.selectionStart;
			var oldVal    = this.value;
			var spacesBefore = (oldVal.substring(0, cursor).match(/\s/g) || []).length;

			var formatted = fmtRevenue(oldVal);
			this.value    = formatted;

			// Adjust cursor for added/removed spaces
			var spacesAfter = (formatted.substring(0, cursor).match(/\s/g) || []).length;
			var newCursor   = Math.max(0, cursor + (spacesAfter - spacesBefore));
			this.setSelectionRange(newCursor, newCursor);
		});
	}

	// -------------------------------------------------------------------------
	// Revenue type toggle
	// -------------------------------------------------------------------------
	function initRevenueToggle() {
		var radios    = document.querySelectorAll('input[name="revenue_type"]');
		var rangeWrap = document.getElementById('ecalc-revenue-range-wrap');
		var exactWrap = document.getElementById('ecalc-revenue-exact-wrap');

		if (!radios.length) return;

		radios.forEach(function (r) {
			r.addEventListener('change', function () {
				if (this.value === 'exact') {
					rangeWrap.style.display = 'none';
					exactWrap.style.display = '';
				} else {
					rangeWrap.style.display = '';
					exactWrap.style.display = 'none';
				}
			});
		});
	}

	// -------------------------------------------------------------------------
	// Form submit
	// -------------------------------------------------------------------------
	function initForm() {
		var form    = document.getElementById('ecalc-form');

		if (!form) return;

		// Jednou při první interakci s formulářem
		var formStartFired = false;
		form.addEventListener('focus', function () {
			if (!formStartFired) {
				formStartFired = true;
				gtm('ecalc_form_start');
			}
		}, true);

		form.addEventListener('submit', function (e) {
			e.preventDefault();

			clearErrors();

			if (!validate()) {
				scrollToFirstError();
				return;
			}

			formConverted = true;
			var data = collectData();
			lastFormData = data;

			gtm('ecalc_form_submit', {
				ecalc_segment:            data.segment,
				ecalc_database_range:     data.database_range,
				ecalc_revenue_type:       data.revenue_type,
				ecalc_revenue_range:      data.revenue_range || '',
				ecalc_monthly_revenue:    data.revenue_exact || 0,
				ecalc_consumable_pct:     data.consumable_percentage,
				ecalc_expected_pno:       data.expected_pno,
				ecalc_consent_marketing:  data.consent_marketing,
			});

			submitCalculation(data);
		});
	}

	function submitCalculation(data) {
		var btnText = document.getElementById('ecalc-btn-text');
		var btnLoad = document.getElementById('ecalc-btn-loader');
		var btnEl   = document.getElementById('ecalc-submit');
		var errBox  = document.getElementById('ecalc-form-error');

		btnEl.disabled        = true;
		btnText.style.display = 'none';
		btnLoad.style.display = '';
		errBox.style.display  = 'none';

		fetch(cfg.restUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': cfg.nonce,
			},
			body: JSON.stringify(data),
		})
			.then(function (res) {
				return res.json().then(function (json) {
					return { status: res.status, json: json };
				});
			})
			.then(function (result) {
				btnEl.disabled        = false;
				btnText.style.display = '';
				btnLoad.style.display = 'none';

				// Backend našel existující email – zobraz potvrzovací dialog
				if (result.status === 409 && result.json.code === 'duplicate_email') {
					gtm('ecalc_duplicate_dialog', {
						ecalc_duplicate_status: result.json.status_label || '',
						ecalc_is_active_lead:   result.json.is_active || false,
					});
					showDuplicateConfirm(result.json, function (confirmed) {
						if (confirmed) {
							gtm('ecalc_duplicate_confirmed');
							var confirmedData = JSON.parse(JSON.stringify(data));
							confirmedData.confirmed = true;
							submitCalculation(confirmedData);
						}
					});
					return;
				}

				if (result.json.success) {
					var r    = result.json;
					var calc = r.calculation || {};
					var res  = r.result     || {};
					var lead = r.lead       || {};
					gtm('ecalc_calculation_success', {
						ecalc_lead_id:                    r.lead_id || null,
						ecalc_result_type:                res.type  || '',
						ecalc_segment:                    lead.segment || data.segment || '',
						ecalc_database_range:             lead.database_range || data.database_range || '',
						ecalc_monthly_revenue:            parseFloat(lead.monthly_revenue) || 0,
						ecalc_expected_pno:               parseFloat(lead.expected_pno)    || 0,
						ecalc_consumable_pct:             parseFloat(lead.consumable_percentage) || 0,
						ecalc_final_potential:            parseFloat(calc.final_potential) || 0,
						ecalc_emailing_revenue_low:       parseFloat(calc.emailing_revenue_low)  || 0,
						ecalc_emailing_revenue_mid:       parseFloat(calc.emailing_revenue_mid)  || 0,
						ecalc_emailing_revenue_high:      parseFloat(calc.emailing_revenue_high) || 0,
						ecalc_available_budget:           parseFloat(calc.available_budget) || 0,
						ecalc_recommended_package:        res.recommended_package ? res.recommended_package.name  : '',
						ecalc_recommended_package_price:  res.recommended_package ? res.recommended_package.price : 0,
						ecalc_is_updated:                 r.updated || false,
					});
					showResult(r);
				} else {
					gtm('ecalc_calculation_error', {
						ecalc_error_code:    result.json.code    || '',
						ecalc_error_message: result.json.message || '',
					});
					showFormError(result.json.message || strings.error_generic);
					resetTurnstile();
				}
			})
			.catch(function () {
				btnEl.disabled        = false;
				btnText.style.display = '';
				btnLoad.style.display = 'none';
				showFormError(strings.error_generic);
				resetTurnstile();
			});
	}

	function resetTurnstile() {
		if (cfg.turnstile && cfg.turnstile.enabled && window.turnstile) {
			var widget = document.querySelector('#ecalc-turnstile-wrap .cf-turnstile');
			if (widget) {
				window.turnstile.reset(widget);
			}
		}
	}

	function checkEmail(email, callback) {
		if (!cfg.checkEmailUrl || !email) { callback(null); return; }

		var url = cfg.checkEmailUrl + '?email=' + encodeURIComponent(email);

		fetch(url, { method: 'GET' })
			.then(function (res) {
				if (!res.ok) { callback(null); return null; }
				return res.json();
			})
			.then(function (json) { if (json !== null && json !== undefined) callback(json); })
			.catch(function () { callback(null); });
	}

	function showDuplicateConfirm(emailResult, callback) {
		var s           = strings;
		var statusLabel = esc(emailResult.status_label || '');
		var isActive    = emailResult.is_active;
		var ctaClicked  = emailResult.cta_clicked;

		var warning = '';
		if (isActive && ctaClicked) {
			var ctaTypeLabel = emailResult.cta_type === 'package' ? 'poptávku balíčku' : 'rezervaci konzultace';
			warning = '<p class="ecalc-dup-warning">&#9888; Tento kontakt má stav <strong>' + statusLabel + '</strong> a odeslal ' + ctaTypeLabel + '. Nový výpočet tuto aktivitu přepíše.</p>';
		} else if (isActive) {
			warning = '<p class="ecalc-dup-warning">&#9888; Tento kontakt má aktivní stav <strong>' + statusLabel + '</strong>. Stav bude zachován.</p>';
		}

		var msgText = (s.duplicate_msg || 'Kontakt s tímto e-mailem již existuje v naší databázi (stav: {status}).').replace('{status}', statusLabel);

		var overlay = document.createElement('div');
		overlay.className = 'ecalc-bm-overlay ecalc-dup-overlay';
		overlay.innerHTML =
			'<div class="ecalc-dup-box">' +
				'<h3 class="ecalc-dup-title">' + esc(s.duplicate_title || 'Již vás máme v databázi') + '</h3>' +
				'<p class="ecalc-dup-msg">' + msgText + '</p>' +
				warning +
				'<p class="ecalc-dup-question">' + esc(s.duplicate_question || 'Chcete provést nový výpočet? Aktuální záznam bude aktualizován.') + '</p>' +
				'<div class="ecalc-dup-btns">' +
					'<button class="ecalc-dup-confirm"><span>' + esc(s.duplicate_confirm || 'Ano, provést nový výpočet') + '</span></button>' +
					'<button class="ecalc-dup-cancel"><span>' + esc(s.duplicate_cancel || 'Zrušit') + '</span></button>' +
				'</div>' +
			'</div>';

		document.body.appendChild(overlay);
		document.body.style.overflow = 'hidden';
		overlay.addEventListener('touchmove', function (e) { e.preventDefault(); }, { passive: false });

		var dupOpenedAt = Date.now();

		function closeOverlay() {
			overlay.remove();
			document.body.style.overflow = '';
		}

		overlay.querySelector('.ecalc-dup-confirm').addEventListener('click', function () {
			closeOverlay();
			callback(true);
		});

		overlay.querySelector('.ecalc-dup-cancel').addEventListener('click', function () {
			closeOverlay();
			callback(false);
		});

		overlay.addEventListener('click', function (e) {
			if (Date.now() - dupOpenedAt < 350) return;
			if (e.target === overlay) { closeOverlay(); callback(false); }
		});

		document.addEventListener('keydown', function onEsc(e) {
			if (e.key === 'Escape') { closeOverlay(); callback(false); document.removeEventListener('keydown', onEsc); }
		});
	}

	// -------------------------------------------------------------------------
	// Validation
	// -------------------------------------------------------------------------
	function validate() {
		var ok = true;

		var name  = val('ecalc-name');
		var email = val('ecalc-email');
		var url   = val('ecalc-shop-url');
		var seg   = val('ecalc-segment');
		var db    = val('ecalc-database-range');
		var pno   = val('ecalc-pno');
		var consentData = document.getElementById('ecalc-consent-data');
		var consentMkt  = document.getElementById('ecalc-consent-marketing');
		var mktRequired = consentMkt && consentMkt.hasAttribute('required');

		if (!name) { setError('ecalc-error-name', strings.required); ok = false; }
		if (!email || !isEmail(email)) { setError('ecalc-error-email', strings.invalid_email || 'Zadejte platný e-mail.'); ok = false; }
		if (!url || !isValidShopUrl(url)) { setError('ecalc-error-shop-url', 'Zadejte platnou adresu e-shopu (např. mujshop.cz).'); ok = false; }
		if (!seg)   { setError('ecalc-error-segment', 'Vyberte segment e-shopu.'); ok = false; }
		if (!db)    { setError('ecalc-error-database', 'Vyberte velikost databáze.'); ok = false; }

		var revType  = document.querySelector('input[name="revenue_type"]:checked');
		var revRange = document.getElementById('ecalc-revenue-range');
		var revExact = document.getElementById('ecalc-revenue-exact');

		if (revType && revType.value === 'range' && revRange && !revRange.value) {
			setError('ecalc-error-revenue', 'Vyberte rozsah obratu.'); ok = false;
		}
		if (revType && revType.value === 'exact' && revExact && (!revExact.value || parseRevenue(revExact.value) <= 0)) {
			setError('ecalc-error-revenue', 'Zadejte měsíční obrat.'); ok = false;
		}

		if (!pno || parseFloat(pno) < 1 || parseFloat(pno) > 100) {
			setError('ecalc-error-pno', 'Zadejte PNO v rozsahu 1–100 %.'); ok = false;
		}

		if (consentData && !consentData.checked) {
			setError('ecalc-error-consent-data', strings.consent_required || 'Souhlas je povinný.'); ok = false;
		}
		if (mktRequired && !consentMkt.checked) {
			setError('ecalc-error-consent-marketing', 'Souhlas s marketingem je povinný.'); ok = false;
		}

		// honeypot
		var hp = document.getElementById('_hp_field');
		if (hp && hp.value) return false;

		return ok;
	}

	// -------------------------------------------------------------------------
	// Collect form data
	// -------------------------------------------------------------------------
	function collectData() {
		var revType  = document.querySelector('input[name="revenue_type"]:checked');
		var revRange = document.getElementById('ecalc-revenue-range');
		var revExact = document.getElementById('ecalc-revenue-exact');
		var consentData = document.getElementById('ecalc-consent-data');
		var consentMkt  = document.getElementById('ecalc-consent-marketing');
		var hp = document.getElementById('_hp_field');

		var tsToken = '';
		if (cfg.turnstile && cfg.turnstile.enabled) {
			var tsInput = document.querySelector('[name="cf-turnstile-response"]');
			tsToken = tsInput ? tsInput.value : '';
		}

		return {
			name:                 val('ecalc-name'),
			email:                val('ecalc-email'),
			shop_url:             val('ecalc-shop-url'),
			segment:              val('ecalc-segment'),
			consumable_percentage:parseInt(document.getElementById('ecalc-consumable-percentage').value, 10),
			database_range:       val('ecalc-database-range'),
			revenue_type:         revType ? revType.value : 'range',
			revenue_range:        revRange ? revRange.value : '',
			revenue_exact:        revExact ? parseRevenue(revExact.value) : 0,
			expected_pno:         parseFloat(val('ecalc-pno')),
			consent_data:         consentData && consentData.checked ? 1 : 0,
			consent_marketing:    consentMkt && consentMkt.checked ? 1 : 0,
			_hp_field:            hp ? hp.value : '',
			nonce:                cfg.nonce,
			turnstile_token:      tsToken,
			utm_source:           getUtm('utm_source'),
			utm_medium:           getUtm('utm_medium'),
			utm_campaign:         getUtm('utm_campaign'),
			referrer:             document.referrer || '',
			time_to_submit:       Math.round((Date.now() - pageLoadTime) / 1000),
		};
	}

	// -------------------------------------------------------------------------
	// Show result
	// -------------------------------------------------------------------------
	var lastFormData      = null;
	var currentLeadId    = null;
	var currentLeadToken = null;

	function showResult(response) {
		var app      = document.getElementById('ecalc-app');
		var formCol  = app.querySelector('.ecalc-form-col');
		var infoCol  = document.getElementById('ecalc-info-panel');
		var resultEl = document.getElementById('ecalc-result');

		formCol.style.display = 'none';
		if (infoCol) infoCol.style.display = 'none';

		currentLeadId    = response.lead_id    || null;
		currentLeadToken = response.lead_token || null;

		var lead    = response.lead;
		var calc    = response.calculation;
		var res     = response.result;
		var updated = response.updated || false;

		var html = buildResultHTML(lead, calc, res, updated);
		document.getElementById('ecalc-result-inner').innerHTML = html;
		resultEl.style.display = '';
		resultEl.scrollIntoView({ behavior: 'smooth', block: 'start' });

		// Toolbar buttons
		var btnEdit = document.getElementById('ecalc-btn-edit');
		var btnNew  = document.getElementById('ecalc-btn-new');
		if (btnEdit) btnEdit.addEventListener('click', function () { resetToForm(true); });
		if (btnNew)  btnNew.addEventListener('click',  function () { resetToForm(false); });

		// CTA interceptors
		bindCtaClicks(resultEl);
	}

	function bindCtaClicks(container) {
		// Hlavní CTA – konzultace (booking modal nebo přímý odkaz)
		container.querySelectorAll('.ecalc-cta-btn').forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				var bookingUrl  = cfg.bookingUrl || '';
				// getAttribute vrací surovou hodnotu atributu ('#'), this.href by vrátila absolutní URL
				var fallbackUrl = this.getAttribute('href') || '';

				gtm('ecalc_cta_click', {
					ecalc_cta_type:   'consultation',
					ecalc_cta_label:  this.textContent.trim(),
					ecalc_lead_id:    currentLeadId,
				});

				// CTA tracking + notifikace
				if (cfg.ctaClickUrl && currentLeadId) {
					fetch(cfg.ctaClickUrl, {
						method: 'POST',
						headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
						body: JSON.stringify({ lead_id: currentLeadId, lead_token: currentLeadToken, type: 'consultation', package_name: '', phone: '' }),
						keepalive: true,
					});
				}
				// Akce
				if (bookingUrl) {
					openBookingModal(bookingUrl, fallbackUrl);
				} else if (fallbackUrl && fallbackUrl !== '#' && fallbackUrl !== 'javascript:void(0)') {
					window.location.href = fallbackUrl;
				}
			});
		});

		// "Poptat balíček" – konkrétní balíček
		container.querySelectorAll('.ecalc-package-cta').forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				var card    = this.closest('.ecalc-package-card');
				var pkgName = card ? ((card.querySelector('.ecalc-package-name') || {}).textContent || '').trim() : '';
				var ctaUrl  = this.getAttribute('href') || '';

				gtm('ecalc_package_cta_click', {
					ecalc_cta_type:    'package',
					ecalc_package_name: pkgName,
					ecalc_lead_id:     currentLeadId,
				});

				// 1. Telefon dialog – získáme číslo před odesláním notifikace
				showPhoneDialog(function (phone) {
					// 2. CTA tracking + notifikace (s telefonem v payloadu)
					if (cfg.ctaClickUrl && currentLeadId) {
						fetch(cfg.ctaClickUrl, {
							method: 'POST',
							headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
							body: JSON.stringify({ lead_id: currentLeadId, lead_token: currentLeadToken, type: 'package', package_name: pkgName, phone: phone }),
							keepalive: true,
						});
					}
					// 3. Děkovačka
					showInquiryThankYou(pkgName, ctaUrl);
				});
			});
		});
	}

	function recordCtaClick(type, packageName, redirectUrl) {
		if (!cfg.ctaClickUrl) {
			if (redirectUrl) window.location.href = redirectUrl;
			return;
		}

		var payload = {
			lead_id:      currentLeadId,
			lead_token:   currentLeadToken,
			type:         type,
			package_name: packageName,
		};

		fetch(cfg.ctaClickUrl, {
			method:  'POST',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
			body:    JSON.stringify(payload),
			keepalive: true,
		}).finally(function () {
			if (redirectUrl && redirectUrl !== '#') window.location.href = redirectUrl;
		});
	}

	function resetToForm(prefill) {
		var app      = document.getElementById('ecalc-app');
		var formCol  = app.querySelector('.ecalc-form-col');
		var infoCol  = document.getElementById('ecalc-info-panel');
		var resultEl = document.getElementById('ecalc-result');

		resultEl.style.display = 'none';
		formCol.style.display  = '';
		if (infoCol) infoCol.style.display = '';

		if (prefill && lastFormData) {
			prefillForm(lastFormData);
		} else {
			var form = document.getElementById('ecalc-form');
			if (form) form.reset();
			initSlider();
		}
		formCol.scrollIntoView({ behavior: 'smooth', block: 'start' });
	}

	function prefillForm(data) {
		var set = function (id, val) { var el = document.getElementById(id); if (el) el.value = val; };
		set('ecalc-name',            data.name || '');
		set('ecalc-email',           data.email || '');
		set('ecalc-shop-url',        data.shop_url || '');
		set('ecalc-segment',         data.segment || '');
		set('ecalc-database-range',  data.database_range || '');
		set('ecalc-pno',             data.expected_pno || '');

		applySliderValue(data.consumable_percentage || 50);

		var radios = document.querySelectorAll('input[name="revenue_type"]');
		radios.forEach(function (r) { r.checked = (r.value === (data.revenue_type || 'range')); });
		set('ecalc-revenue-range', data.revenue_range || '');
		var reEl = document.getElementById('ecalc-revenue-exact');
		if (reEl) reEl.value = data.revenue_exact ? fmtRevenue(data.revenue_exact) : '';

		var rangeWrap = document.getElementById('ecalc-revenue-range-wrap');
		var exactWrap = document.getElementById('ecalc-revenue-exact-wrap');
		if (data.revenue_type === 'exact') {
			if (rangeWrap) rangeWrap.style.display = 'none';
			if (exactWrap) exactWrap.style.display = '';
		} else {
			if (rangeWrap) rangeWrap.style.display = '';
			if (exactWrap) exactWrap.style.display = 'none';
		}
	}

	function buildResultHTML(lead, calc, res, updated) {
		var html = '<div class="ecalc-result-wrap">';

		// Banner pro aktualizaci existujícího záznamu
		if (updated) {
			html += '<div class="ecalc-update-banner">'
				+ '<span class="ecalc-update-banner-icon">&#8635;</span>'
				+ '<span>' + esc(strings.update_banner || 'Váš záznam byl aktualizován na základě nového výpočtu.') + '</span>'
				+ '</div>';
		}

		// Toolbar
		html += '<div class="ecalc-result-toolbar">';
		html += '<h2 class="ecalc-result-title">' + esc(res.title) + '</h2>';
		html += '<div class="ecalc-toolbar-btns">';
		html += '<button class="ecalc-toolbar-btn ecalc-toolbar-btn--accent" id="ecalc-btn-edit"><span>&#9998; Upravit hodnoty</span></button>';
		html += '<button class="ecalc-toolbar-btn" id="ecalc-btn-new"><span>&#8635; Nový výpočet</span></button>';
		html += '</div></div>';

		// Summary – čistý grid místo tabulky
		html += '<div class="ecalc-summary">';
		html += '<p class="ecalc-summary-title">Shrnutí zadaných údajů</p>';
		html += '<div class="ecalc-summary-grid">';
		html += summaryItem('Měsíční obrat',     fmt(lead.monthly_revenue));
		html += summaryItem('Segment',           lead.segment);
		html += summaryItem('Spotřební zboží',   lead.consumable_percentage + ' %');
		html += summaryItem('Databáze kontaktů', lead.database_range);
		html += summaryItem('Očekávané PNO',     lead.expected_pno + ' %');
		html += '</div></div>';

		// Stats grid
		var noMeetsPno = (res.type === 'low_potential' || res.type === 'borderline');
		var textClass  = res.type === 'low_potential' ? ' ecalc-result-text--danger'
		               : res.type === 'borderline'    ? ' ecalc-result-text--warning'
		               : '';

		html += '<div class="ecalc-result-grid">';
		html += statCard('Potenciál emailingu', fmtPct(calc.final_potential), 'orientační odhad');
		html += statCard('Odhadovaný obrat z emailingu', fmt(calc.emailing_revenue_mid), fmt(calc.emailing_revenue_low) + ' – ' + fmt(calc.emailing_revenue_high));
		if (!noMeetsPno && res.recommended_package) {
			var pkgSavings = calc.available_budget - res.recommended_package.price;
			html += budgetStatCard(calc.available_budget, lead.expected_pno, res.recommended_package.real_pno, pkgSavings);
			html += packageStatCard(res.recommended_package.name, res.recommended_package.price, res.recommended_package.items || []);
		} else {
			var cheapestForBudget = findCheapestPackage(res.packages);
			if (cheapestForBudget) {
				html += budgetStatCardAlert(calc.available_budget, lead.expected_pno, cheapestForBudget.real_pno);
			} else {
				html += statCard('Doporučený budget', fmt(calc.available_budget), 'při PNO ' + lead.expected_pno + ' %');
			}
			html += consultationStatCard(res.cta_text, res.cta_url);
		}
		html += '</div>';

		// Result text
		if (res.text) {
			html += '<div class="ecalc-result-text' + textClass + '">' + res.text + '</div>';
		}

		// ---- ARGUMENTY ("proč máte tento potenciál") ----
		if (res.arguments && res.arguments.items && res.arguments.items.length > 0) {
			html += '<div class="ecalc-arguments">';
			if (res.arguments.title) {
				html += '<p class="ecalc-arguments-title">' + esc(res.arguments.title) + '</p>';
			}
			if (res.arguments.subtitle) {
				html += '<p class="ecalc-arguments-subtitle">' + esc(res.arguments.subtitle) + '</p>';
			}
			html += '<ul class="ecalc-arguments-list">';
			res.arguments.items.forEach(function (item) {
				html += '<li>' + esc(item) + '</li>';
			});
			html += '</ul>';
			if (res.arguments.summary) {
				html += '<p class="ecalc-arguments-summary">' + esc(res.arguments.summary) + '</p>';
			}
			if (res.cta_url || res.cta_text) {
				html += '<a href="' + esc(res.cta_url || '#') + '" class="ecalc-arguments-cta ecalc-cta-btn ecalc-cta-btn--outline"'
					+ ' data-gtm-id="ecalc-cta-consultation"'
					+ ' rel="noopener"><span>' + esc(res.cta_text) + '</span></a>';
			}
			html += '</div>';
		}

		// ---- PACKAGES SECTION ----
		if (res.packages && res.packages.length > 0) {
			if (noMeetsPno) {
				// Nesplněné PNO: zobrazit jen nejlevnější balíček + konzultace CTA
				var cheapest = findCheapestPackage(res.packages);
				if (cheapest) {
					html += '<p class="ecalc-packages-title">Nejbližší varianta pro orientaci</p>';
					html += '<div class="ecalc-packages-grid ecalc-packages-grid--single">';
					html += buildPackageCard(cheapest, lead.expected_pno, false, null);
					html += '</div>';
				}
				if (res.cta_url || res.cta_text) {
					html += '<div class="ecalc-cta-wrap">';
					html += '<a href="' + esc(res.cta_url || '#') + '" class="ecalc-cta-btn"'
					+ ' data-gtm-id="ecalc-cta-consultation"'
					+ ' rel="noopener"><span>' + esc(res.cta_text) + '</span></a>';
					html += '</div>';
				}
			} else {
				// Splněné PNO: všechny balíčky s "Poptat balíček" u každého
				html += '<p class="ecalc-packages-title">Porovnání balíčků</p>';
				html += '<div class="ecalc-packages-grid">';
				res.packages.forEach(function (pkg) {
					html += buildPackageCard(pkg, lead.expected_pno, true, res.cta_url || '#');
				});
				html += '</div>';
			}
		}

		html += '</div>';
		return html;
	}

	function findCheapestPackage(packages) {
		if (!packages || !packages.length) return null;
		return packages.reduce(function (min, pkg) {
			return parseFloat(pkg.price) < parseFloat(min.price) ? pkg : min;
		});
	}

	function buildPackageCard(pkg, expected_pno, showCta, ctaUrl) {
		var isRec   = pkg.is_recommended;
		var fitsCls = pkg.fits_pno ? 'ecalc-package-pno--ok' : 'ecalc-package-pno--over';
		var fitsMsg = pkg.fits_pno
			? 'Vejde se do vašeho PNO (' + fmtPct(pkg.real_pno) + ')'
			: (cfg.pnoOverLabel || 'Nad vaším zadaným PNO') + ' (' + fmtPct(pkg.real_pno) + ')';

		var html = '<div class="ecalc-package-card' + (isRec ? ' ecalc-package-card--recommended' : '') + '">';

		if (isRec) {
			html += '<div class="ecalc-package-badge">Doporučený</div>';
		}

		html += '<p class="ecalc-package-name">' + esc(pkg.name) + '</p>';
		html += '<p class="ecalc-package-price">' + fmt(pkg.price) + '</p>';
		html += '<p class="ecalc-package-price-sub">/ měsíc</p>';
		html += '<span class="ecalc-package-pno ' + fitsCls + '">' + fitsMsg + '</span>';

		html += '<p class="ecalc-pno-note">Orientační reálné PNO: <strong>' + fmtPct(pkg.real_pno) + '</strong> (vámi zvolené: ' + expected_pno + ' %)</p>';

		if (pkg.description) {
			html += '<p class="ecalc-package-desc">' + esc(pkg.description) + '</p>';
		}

		if (pkg.items && pkg.items.length > 0) {
			html += '<ul class="ecalc-package-items">';
			pkg.items.forEach(function (it) {
				html += '<li>' + esc(it) + '</li>';
			});
			html += '</ul>';
		}

		// CTA tlačítko "Poptat balíček" (jen ve splněném scénáři)
		if (showCta && ctaUrl) {
			var btnCls = isRec
				? 'ecalc-package-cta ecalc-package-cta--primary'
				: 'ecalc-package-cta ecalc-package-cta--secondary';
			html += '<a href="' + esc(ctaUrl) + '" class="' + btnCls + '"'
				+ ' data-gtm-id="ecalc-package-cta"'
				+ ' data-gtm-package="' + esc(pkg.name) + '"'
				+ ' data-gtm-recommended="' + (isRec ? '1' : '0') + '"'
				+ ' rel="noopener"><span>Poptat balíček</span></a>';
		}

		html += '</div>';
		return html;
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------
	function summaryItem(label, value) {
		return '<div class="ecalc-si">'
			+ '<span class="ecalc-si-label">' + esc(label) + '</span>'
			+ '<span class="ecalc-si-value">' + esc(value) + '</span>'
			+ '</div>';
	}

	function statCard(label, value, sub) {
		var s = sub ? '<p class="ecalc-stat-sub">' + esc(sub) + '</p>' : '';
		return '<div class="ecalc-stat-card">'
			+ '<p class="ecalc-stat-label">' + esc(label) + '</p>'
			+ '<p class="ecalc-stat-value">' + value + '</p>'
			+ s + '</div>';
	}

	function budgetStatCard(budget, expectedPno, realPno, savings) {
		var html = '<div class="ecalc-stat-card">'
			+ '<p class="ecalc-stat-label">Doporučený budget</p>'
			+ '<p class="ecalc-stat-value">' + fmt(budget) + '</p>'
			+ '<p class="ecalc-stat-sub">při PNO ' + expectedPno + ' %</p>'
			+ '<p class="ecalc-stat-real-pno">Reálné PNO doporuč. balíčku: <strong>' + fmtPct(realPno) + '</strong></p>';
		if (savings > 0) {
			html += '<div class="ecalc-budget-saving">'
				+ '<span class="ecalc-budget-saving-label">&#10003; Ušetříte oproti svému budgetu</span>'
				+ '<strong class="ecalc-budget-saving-amount">' + fmt(savings) + '</strong>'
				+ '</div>';
		}
		html += '</div>';
		return html;
	}

	function consultationStatCard(ctaText, ctaUrl) {
		var btnHtml = '';
		if (ctaUrl || ctaText) {
			var href = ctaUrl ? esc(ctaUrl) : '#';
			btnHtml = '<a href="' + href + '" class="ecalc-stat-cta ecalc-cta-btn"'
				+ ' data-gtm-id="ecalc-cta-consultation"'
				+ ' rel="noopener">'
				+ '<span>' + esc(ctaText || 'Konzultace zdarma') + '</span></a>';
		}
		var noteHtml = cfg.ctaConsultationNote
			? '<p class="ecalc-stat-cta-note">' + esc(cfg.ctaConsultationNote) + '</p>'
			: '';
		return '<div class="ecalc-stat-card">'
			+ '<p class="ecalc-stat-label">Doporučení</p>'
			+ '<p class="ecalc-stat-value">Konzultace zdarma</p>'
			+ '<p class="ecalc-stat-sub">pro detailní analýzu</p>'
			+ btnHtml
			+ noteHtml
			+ '</div>';
	}

	function budgetStatCardAlert(budget, expectedPno, realPno) {
		return '<div class="ecalc-stat-card">'
			+ '<p class="ecalc-stat-label">Doporučený budget</p>'
			+ '<p class="ecalc-stat-value">' + fmt(budget) + '</p>'
			+ '<p class="ecalc-stat-sub">při PNO ' + expectedPno + ' %</p>'
			+ '<div class="ecalc-budget-pno-alert">'
			+ '<span class="ecalc-budget-pno-alert-label">&#9888; Reálné PNO nejbližší varianty</span>'
			+ '<strong class="ecalc-budget-pno-alert-value">' + fmtPct(realPno) + '</strong>'
			+ '</div>'
			+ '</div>';
	}

	function packageStatCard(name, price, items) {
		var itemsHtml = '';
		if (items && items.length > 0) {
			itemsHtml = '<ul class="ecalc-stat-items">';
			items.forEach(function (item) {
				itemsHtml += '<li>' + esc(item) + '</li>';
			});
			itemsHtml += '</ul>';
		}
		return '<div class="ecalc-stat-card">'
			+ '<p class="ecalc-stat-label">Doporučený balíček</p>'
			+ '<p class="ecalc-stat-value">' + esc(name) + '</p>'
			+ '<p class="ecalc-stat-sub">' + fmt(price) + ' / měsíc</p>'
			+ itemsHtml
			+ '<p class="ecalc-stat-sw-note">&#42;&nbsp;K ceně připočtěte licenci emailing SW:'
			+ ' <strong>200–4 000 Kč/měs.</strong> dle velikosti seznamu kontaktů.</p>'
			+ '</div>';
	}

	function row(label, value) {
		return '<tr><td>' + esc(label) + '</td><td>' + esc(value) + '</td></tr>';
	}

	function fmt(num) {
		var n = parseFloat(num) || 0;
		return n.toLocaleString('cs-CZ', { maximumFractionDigits: 0 }) + ' Kč';
	}

	function fmtPct(num) {
		var n = parseFloat(num) || 0;
		return n.toLocaleString('cs-CZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' %';
	}

	function esc(str) {
		if (str === null || str === undefined) return '';
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#39;');
	}

	function val(id) {
		var el = document.getElementById(id);
		return el ? el.value.trim() : '';
	}

	function isEmail(str) {
		return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(str);
	}

	function isValidShopUrl(str) {
		// Povolí: mujshop.cz, shop.co.uk, https://eshop.online/cesta
		// Vyžaduje TLD min. 2 písmena, bez horního limitu (.cz, .com, .online, .consulting...)
		return /^(https?:\/\/)?(www\.)?[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9\-]+)*\.[a-zA-Z]{2,}(\/.*)?$/.test(str.trim());
	}

	function scrollToFirstError() {
		// Najde první chybné pole a posune k němu stránku
		var firstInput = document.querySelector('#ecalc-form .ecalc-input--error');
		if (!firstInput) {
			firstInput = document.querySelector('#ecalc-form .ecalc-error-msg:not(:empty)');
		}
		if (!firstInput) return;

		firstInput.scrollIntoView({ behavior: 'smooth', block: 'center' });

		// Shake animace na chybném poli
		firstInput.classList.add('ecalc-input--shake');
		setTimeout(function () {
			firstInput.classList.remove('ecalc-input--shake');
		}, 600);

		// Focus na první chybný input (přístupnost)
		if ( firstInput.tagName === 'INPUT' || firstInput.tagName === 'SELECT' || firstInput.tagName === 'TEXTAREA' ) {
			firstInput.focus();
		}
	}

	function setError(id, msg) {
		var el = document.getElementById(id);
		if (el) el.textContent = msg;

		var inputId = id.replace('ecalc-error-', 'ecalc-');
		var input   = document.getElementById(inputId);
		if (input) input.classList.add('ecalc-input--error');
	}

	function clearErrors() {
		document.querySelectorAll('.ecalc-error-msg').forEach(function (el) {
			el.textContent = '';
		});
		document.querySelectorAll('.ecalc-input--error').forEach(function (el) {
			el.classList.remove('ecalc-input--error');
		});
	}

	function showFormError(msg) {
		var errBox = document.getElementById('ecalc-form-error');
		if (errBox) {
			errBox.textContent  = msg;
			errBox.style.display = '';
		}
	}


	// -------------------------------------------------------------------------
	// Phone dialog – volitelné zadání telefonního čísla po CTA kliknutí
	// -------------------------------------------------------------------------
	function showPhoneDialog(callback) {
		var s = cfg.strings || {};
		var overlay = document.createElement('div');
		overlay.className = 'ecalc-bm-overlay ecalc-phone-overlay';
		overlay.innerHTML =
			'<div class="ecalc-phone-box">' +
				'<div class="ecalc-ty-icon">&#128222;</div>' +
				'<h3 class="ecalc-phone-title">' + esc(s.phone_dialog_title  || 'Zanechte nám telefonní číslo') + '</h3>' +
				'<p class="ecalc-phone-desc">'  + esc(s.phone_dialog_desc   || 'Pro rychlejší komunikaci nám můžete zanechat telefonní číslo.') + '</p>' +
				'<input class="ecalc-input ecalc-phone-input" type="tel" placeholder="např. +420 777 123 456" autocomplete="tel">' +
				'<span class="ecalc-phone-error ecalc-error-msg"></span>' +
				'<div class="ecalc-phone-btns">' +
					'<button class="ecalc-phone-submit"><span>' + esc(s.phone_dialog_submit || 'Pokračovat') + '</span></button>' +
					'<button class="ecalc-phone-skip"><span>'   + esc(s.phone_dialog_skip   || 'Přeskočit')  + '</span></button>' +
				'</div>' +
			'</div>';

		document.body.appendChild(overlay);
		document.body.style.overflow = 'hidden';
		overlay.addEventListener('touchmove', function (e) { e.preventDefault(); }, { passive: false });

		var phoneInput = overlay.querySelector('.ecalc-phone-input');
		var phoneError = overlay.querySelector('.ecalc-phone-error');
		var phoneOpenedAt = Date.now();

		function isValidPhone(val) {
			if (!val) return true;
			var digits = val.replace(/[\s\-().+]/g, '');
			return /^\d{7,15}$/.test(digits);
		}

		function closePhone(phone) {
			overlay.remove();
			document.body.style.overflow = '';
			document.removeEventListener('keydown', onEscPhone);
			callback(phone || '');
		}

		function onEscPhone(e) { if (e.key === 'Escape') closePhone(''); }

		phoneInput.addEventListener('input', function () {
			if (phoneError) phoneError.textContent = '';
			phoneInput.classList.remove('ecalc-input--error');
		});

		overlay.querySelector('.ecalc-phone-submit').addEventListener('click', function () {
			var phone = phoneInput ? phoneInput.value.trim() : '';
			if (!isValidPhone(phone)) {
				if (phoneError) phoneError.textContent = s.phone_dialog_error || 'Zadejte platné telefonní číslo (7–15 číslic).';
				phoneInput.classList.add('ecalc-input--error');
				phoneInput.focus();
				return;
			}
			closePhone(phone);
		});

		overlay.querySelector('.ecalc-phone-skip').addEventListener('click', function () {
			closePhone('');
		});

		overlay.addEventListener('click', function (e) {
			if (Date.now() - phoneOpenedAt < 350) return;
			if (e.target === overlay) closePhone('');
		});

		document.addEventListener('keydown', onEscPhone);

		if (phoneInput) {
			phoneInput.focus();
			phoneInput.addEventListener('keydown', function (e) {
				if (e.key === 'Enter') {
					e.preventDefault();
					var phone = phoneInput.value.trim();
					if (!isValidPhone(phone)) {
						if (phoneError) phoneError.textContent = s.phone_dialog_error || 'Zadejte platné telefonní číslo (7–15 číslic).';
						phoneInput.classList.add('ecalc-input--error');
						return;
					}
					closePhone(phone);
				}
			});
		}
	}

	function savePhone(phone) {
		if (!cfg.savePhoneUrl || !currentLeadId || !phone) return;
		fetch(cfg.savePhoneUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
			body: JSON.stringify({ lead_id: currentLeadId, lead_token: currentLeadToken, phone: phone }),
			keepalive: true,
		});
	}

	// -------------------------------------------------------------------------
	// Inquiry thank-you popup (Poptat balíček)
	// -------------------------------------------------------------------------
	function showInquiryThankYou(pkgName, ctaUrl) {
		var s = cfg.strings || {};

		var visitBtn = ctaUrl
			? '<a href="' + esc(ctaUrl) + '" class="ecalc-ty-visit" rel="noopener"><span>' + esc(s.inquiry_visit || 'Přejít na web') + '</span></a>'
			: '';

		var overlay = document.createElement('div');
		overlay.className = 'ecalc-bm-overlay ecalc-ty-overlay';
		overlay.innerHTML =
			'<div class="ecalc-ty-box">' +
				'<div class="ecalc-ty-icon">✓</div>' +
				'<h3 class="ecalc-ty-title">' + esc(s.inquiry_title || 'Děkujeme za zájem!') + '</h3>' +
				(pkgName
					? '<p class="ecalc-ty-pkg">' + esc(s.inquiry_pkg_label || 'Poptáváte balíček:') + ' <strong>' + esc(pkgName) + '</strong></p>'
					: '') +
				'<p class="ecalc-ty-msg">' + esc(s.inquiry_msg || 'Vaše poptávka byla odeslána. Ozveme se vám v nejbližší možné době.') + '</p>' +
				'<div class="ecalc-ty-btns">' +
					visitBtn +
					'<button class="ecalc-ty-close"><span>' + esc(s.inquiry_close || 'Zavřít') + '</span></button>' +
				'</div>' +
			'</div>';

		document.body.appendChild(overlay);
		document.body.style.overflow = 'hidden';
		overlay.addEventListener('touchmove', function (e) { e.preventDefault(); }, { passive: false });

		var tyOpenedAt = Date.now();

		function closeIt() {
			overlay.remove();
			document.body.style.overflow = '';
			document.removeEventListener('keydown', onEscTy);
		}

		function onEscTy(e) { if (e.key === 'Escape') closeIt(); }

		overlay.querySelector('.ecalc-ty-close').addEventListener('click', closeIt);
		overlay.addEventListener('click', function (e) {
			if (Date.now() - tyOpenedAt < 350) return;
			if (e.target === overlay) closeIt();
		});
		document.addEventListener('keydown', onEscTy);
	}

	// -------------------------------------------------------------------------
	// Booking modal
	// -------------------------------------------------------------------------
	var bookingModal     = null;
	var bookingLeadId    = null;
	var bookingFallbackUrl = '';

	function openBookingModal(bookingUrl, fallbackUrl) {
		bookingLeadId    = currentLeadId;
		bookingFallbackUrl = fallbackUrl || bookingUrl;
		var t = cfg.bookingTexts || {};

		gtm('ecalc_booking_modal_open', {
			ecalc_cta_type: 'consultation',
			ecalc_lead_id:  currentLeadId,
		});

		// Zapsat "otevřeno"
		postBookingStatus('opened');

		var modal = document.createElement('div');
		modal.id  = 'ecalc-booking-modal';
		modal.className = 'ecalc-bm-overlay';
		modal.innerHTML =
			'<div class="ecalc-bm-box">' +
				'<div class="ecalc-bm-header">' +
					'<h3 class="ecalc-bm-title">' + esc(t.title || 'Vyberte termín konzultace') + '</h3>' +
					'<button class="ecalc-bm-close" aria-label="Zavřít">&#x2715;</button>' +
				'</div>' +
				'<div class="ecalc-bm-body">' +
					'<div class="ecalc-bm-fallback">' +
						esc(t.fallbackLink || 'Pokud se kalendář nenačítá,') + ' ' +
						'<a href="' + esc(bookingUrl) + '" target="_blank" rel="noopener">' + esc(t.openNewWindow || 'otevřete ho v novém okně') + '</a>.' +
					'</div>' +
					'<iframe src="' + esc(bookingUrl) + '" class="ecalc-bm-iframe" frameborder="0" allowfullscreen></iframe>' +
				'</div>' +
			'</div>';

		document.body.appendChild(modal);
		document.body.style.overflow = 'hidden';
		bookingModal = modal;

		// iOS: zabráníme průchodu scrollu skrz overlay
		modal.addEventListener('touchmove', function (e) { e.preventDefault(); }, { passive: false });

		// Zavření – ghost click guard (touch event na mobilu může dopadnout na backdrop ihned po otevření)
		var openedAt = Date.now();
		modal.querySelector('.ecalc-bm-close').addEventListener('click', closeBookingModal);
		modal.addEventListener('click', function (e) {
			if (Date.now() - openedAt < 350) return;
			if (e.target === modal) closeBookingModal();
		});
		document.addEventListener('keydown', onEscKey);
	}

	function closeBookingModal() {
		if (!bookingModal) return;
		bookingModal.remove();
		bookingModal = null;
		document.body.style.overflow = '';
		document.removeEventListener('keydown', onEscKey);
		showBookingConfirm();
	}

	function onEscKey(e) {
		if (e.key === 'Escape') closeBookingModal();
	}

	function showBookingConfirm() {
		var t = cfg.bookingTexts || {};

		var overlay = document.createElement('div');
		overlay.className = 'ecalc-bm-overlay ecalc-bc-overlay';
		overlay.innerHTML =
			'<div class="ecalc-bc-box">' +
				'<p class="ecalc-bc-question">' + esc(t.confirmQuestion || 'Proběhla rezervace bez problémů?') + '</p>' +
				'<div class="ecalc-bc-btns">' +
					'<button class="ecalc-bc-yes"><span>' + esc(t.confirmYes || '✓ Ano, zarezervoval jsem') + '</span></button>' +
					'<button class="ecalc-bc-no"><span>' + esc(t.confirmNo || 'Ne, zavřít') + '</span></button>' +
				'</div>' +
			'</div>';

		document.body.appendChild(overlay);
		document.body.style.overflow = 'hidden';

		function closeOverlay() {
			overlay.remove();
			document.body.style.overflow = '';
		}

		overlay.querySelector('.ecalc-bc-yes').addEventListener('click', function () {
			postBookingStatus('completed');
			gtm('ecalc_booking_confirmed', {
				ecalc_cta_type: 'consultation',
				ecalc_lead_id:  bookingLeadId,
			});
			overlay.querySelector('.ecalc-bc-box').innerHTML =
				'<div class="ecalc-ty-icon">✓</div>' +
				'<p class="ecalc-bc-thanks ecalc-bc-thanks--yes">' + esc(t.yesMessage || 'Skvělé! Brzy se vám ozveme s potvrzením.') + '</p>' +
				'<button class="ecalc-bc-close-final"><span>Zavřít</span></button>';
			overlay.querySelector('.ecalc-bc-close-final').addEventListener('click', closeOverlay);
		});

		overlay.querySelector('.ecalc-bc-no').addEventListener('click', function () {
			postBookingStatus('declined');
			gtm('ecalc_booking_declined', {
				ecalc_cta_type: 'consultation',
				ecalc_lead_id:  bookingLeadId,
			});
			closeOverlay();
		});

		overlay.addEventListener('click', function (e) { if (e.target === overlay) closeOverlay(); });
	}

	function postBookingStatus(status) {
		if (!cfg.bookingStatusUrl || !bookingLeadId) return;
		fetch(cfg.bookingStatusUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
			body: JSON.stringify({ lead_id: bookingLeadId, lead_token: currentLeadToken, status: status }),
			keepalive: true,
		});
	}

})();
