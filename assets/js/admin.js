/* === Emailing Calculator – Admin JS === */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		initWeightSum();
		initCrudRows();
		initPackages();
		initResendButtons();
		initBulkActions();
		initLeadStatusChange();
		initChangelogToggle();
		initConnectionTests();
	});

	// -------------------------------------------------------------------------
	// Weight sum validation
	// -------------------------------------------------------------------------
	function initWeightSum() {
		var w1 = document.getElementById('consumable_weight');
		var w2 = document.getElementById('database_weight');
		var w3 = document.getElementById('segment_weight');
		var display = document.getElementById('ecalc-weights-sum');

		if (!w1 || !w2 || !w3 || !display) return;

		function update() {
			var sum = (parseFloat(w1.value) || 0) + (parseFloat(w2.value) || 0) + (parseFloat(w3.value) || 0);
			var rounded = Math.round(sum * 100) / 100;
			display.textContent = rounded + ' %';
			display.className = Math.abs(rounded - 100) < 0.01 ? 'ecalc-weight-sum ok' : 'ecalc-weight-sum error';
		}

		[w1, w2, w3].forEach(function (el) { el.addEventListener('input', update); });
		update();
	}

	// -------------------------------------------------------------------------
	// Generic CRUD table add/remove rows
	// -------------------------------------------------------------------------
	function initCrudRows() {
		// Remove row
		document.addEventListener('click', function (e) {
			if (e.target.classList.contains('ecalc-remove-row')) {
				e.target.closest('.ecalc-row').remove();
				reindexTable(e.target);
			}
		});

		// Add segment row
		var addSeg = document.getElementById('ecalc-add-segment');
		if (addSeg) {
			addSeg.addEventListener('click', function () {
				addRowFromTemplate('ecalc-segment-row-tpl', 'ecalc-segments-body');
			});
		}

		// Generic add from data-body + data-tpl attributes
		document.querySelectorAll('[data-body][data-tpl]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				addRowFromTemplate(this.dataset.tpl, this.dataset.body);
			});
		});
	}

	function addRowFromTemplate(tplId, bodyId) {
		var tpl  = document.getElementById(tplId);
		var body = document.getElementById(bodyId);
		if (!tpl || !body) return;

		var idx   = body.querySelectorAll('.ecalc-row').length;
		var order = idx + 1;
		var html  = tpl.innerHTML
			.replace(/__IDX__/g, String(idx))
			.replace(/__ORDER__/g, String(order));

		body.insertAdjacentHTML('beforeend', html);
	}

	function reindexTable(btn) {
		var tbody = btn.closest('tbody');
		if (!tbody) return;
		tbody.querySelectorAll('.ecalc-row').forEach(function (row, i) {
			row.querySelectorAll('input, select, textarea').forEach(function (el) {
				if (el.name) {
					el.name = el.name.replace(/\[\d+\]/, '[' + i + ']');
				}
			});
		});
	}

	// -------------------------------------------------------------------------
	// Packages – add package, add item, remove item
	// -------------------------------------------------------------------------
	function initPackages() {
		var addPkgBtn = document.getElementById('ecalc-add-package');
		if (addPkgBtn) {
			addPkgBtn.addEventListener('click', function () {
				var list  = document.getElementById('ecalc-packages-list');
				var tpl   = document.getElementById('ecalc-package-tpl');
				if (!list || !tpl) return;

				var idx   = list.querySelectorAll('.ecalc-package-edit').length;
				var order = idx + 1;
				var html  = tpl.innerHTML
					.replace(/__IDX__/g, String(idx))
					.replace(/__ORDER__/g, String(order));
				list.insertAdjacentHTML('beforeend', html);
			});
		}

		// Smazat celý balíček s potvrzením
		document.addEventListener('click', function (e) {
			if (e.target.classList.contains('ecalc-delete-package')) {
				var name = e.target.dataset.name || 'tento balíček';
				if (confirm('Opravdu chcete smazat balíček „' + name + '"?\n\nTato akce je nevratná – balíček zmizí po uložení formuláře.')) {
					e.target.closest('.ecalc-package-edit').remove();
				}
			}
		});

		// Add item to package
		document.addEventListener('click', function (e) {
			if (e.target.classList.contains('ecalc-add-item')) {
				var pkgIdx   = e.target.dataset.pkg;
				var itemList = e.target.previousElementSibling;
				if (!itemList) return;

				var row = document.createElement('div');
				row.className = 'ecalc-item-row';
				row.innerHTML = '<input type="text" name="packages[' + pkgIdx + '][items][]" value="" class="regular-text" placeholder="Položka balíčku">'
					+ '<button type="button" class="button button-small ecalc-remove-item">&ndash;</button>';
				itemList.appendChild(row);
			}

			if (e.target.classList.contains('ecalc-remove-item')) {
				e.target.closest('.ecalc-item-row').remove();
			}
		});
	}

	// -------------------------------------------------------------------------
	// Bulk actions – leads table
	// -------------------------------------------------------------------------
	function initBulkActions() {
		var form        = document.getElementById('ecalc-bulk-form');
		var checkAll    = document.getElementById('ecalc-select-all');
		var checkAllTop = document.getElementById('ecalc-select-all-top');
		var countEl     = document.getElementById('ecalc-bulk-count');

		if (!form) return;

		function getChecked() {
			return form.querySelectorAll('.ecalc-row-check:checked');
		}

		function updateCount() {
			var n = getChecked().length;
			if (countEl) countEl.textContent = n > 0 ? 'Vybráno: ' + n : '';
			// Synchronizuj oba "select all" checkboxy
			var total = form.querySelectorAll('.ecalc-row-check').length;
			var allChecked = n === total && total > 0;
			if (checkAll)    checkAll.checked    = allChecked;
			if (checkAllTop) checkAllTop.checked = allChecked;
		}

		function toggleAll(checked) {
			form.querySelectorAll('.ecalc-row-check').forEach(function (cb) { cb.checked = checked; });
			updateCount();
		}

		if (checkAll)    checkAll.addEventListener('change',    function () { toggleAll(this.checked); });
		if (checkAllTop) checkAllTop.addEventListener('change', function () { toggleAll(this.checked); });

		form.querySelectorAll('.ecalc-row-check').forEach(function (cb) {
			cb.addEventListener('change', updateCount);
		});

		// Submit – ověří výběr a akci
		function submitBulk(actionSelect) {
			var action = actionSelect ? actionSelect.value : '';
			if (!action) { alert('Vyberte hromadnou akci.'); return; }
			if (getChecked().length === 0) { alert('Nejprve vyberte alespoň jeden lead.'); return; }

			if (action === 'delete') {
				if (!confirm('Opravdu smazat vybrané leady? Tato akce je nevratná.')) return;
			}

			// Nastav hidden akci pro správný handler
			var hiddenAction = form.querySelector('input[name="action"]');
			if (!hiddenAction) {
				hiddenAction = document.createElement('input');
				hiddenAction.type = 'hidden';
				hiddenAction.name = 'action';
				form.appendChild(hiddenAction);
			}
			hiddenAction.value = 'ecalc_bulk_action';

			// Sync akci do hlavního selectu
			var mainSelect = form.querySelector('select[name="bulk_action"]');
			if (mainSelect) mainSelect.value = action;

			form.submit();
		}

		var btnTop    = document.getElementById('ecalc-bulk-submit');
		var btnBottom = document.getElementById('ecalc-bulk-submit-bottom');

		if (btnTop) {
			btnTop.addEventListener('click', function (e) {
				e.preventDefault();
				submitBulk(form.querySelector('select[name="bulk_action"]'));
			});
		}

		if (btnBottom) {
			btnBottom.addEventListener('click', function (e) {
				e.preventDefault();
				submitBulk(form.querySelector('select[name="bulk_action_bottom"]'));
			});
		}

		// Rychlé smazání jednoho leadu z řádku
		document.querySelectorAll('.ecalc-quick-delete').forEach(function (link) {
			link.addEventListener('click', function (e) {
				var msg = this.dataset.confirm || 'Smazat tento lead?';
				if (!confirm(msg)) e.preventDefault();
			});
		});
	}

	// -------------------------------------------------------------------------
	// Inline změna stavu leadu
	// -------------------------------------------------------------------------
	function initLeadStatusChange() {
		var ajaxUrl = (typeof ecalcAdmin !== 'undefined' && ecalcAdmin.ajaxurl)
			? ecalcAdmin.ajaxurl
			: (typeof ajaxurl !== 'undefined' ? ajaxurl : '');

		// Toggle select visibility
		document.addEventListener('click', function (e) {
			var btn = e.target.closest('.ecalc-status-edit-btn');
			if (!btn) return;
			var wrap   = btn.closest('.ecalc-status-wrap');
			if (!wrap) return;
			var select = wrap.querySelector('.ecalc-status-select');
			if (!select) return;

			if (select.style.display !== 'none') {
				select.style.display = 'none';
				btn.textContent = '✎';
			} else {
				select.style.display = '';
				btn.textContent = '✕';
			}
		});

		// Attach change listener directly to every select
		document.querySelectorAll('.ecalc-status-select').forEach(function (sel) {
			sel.addEventListener('change', function () {
				var leadId = sel.dataset.leadId;
				var status = sel.value;
				var nonce  = (typeof ecalcAdmin !== 'undefined' && ecalcAdmin.nonceStatus)
					? ecalcAdmin.nonceStatus
					: sel.dataset.nonce;
				var wrap  = sel.closest('.ecalc-status-wrap');
				var badge = wrap ? wrap.querySelector('.ecalc-lead-status') : null;
				var btn   = wrap ? wrap.querySelector('.ecalc-status-edit-btn') : null;

				function closeSelect() {
					sel.disabled = false;
					sel.style.display = 'none';
					if (btn) btn.textContent = '✎';
				}

				if (!ajaxUrl) {
					console.error('ecalc: ajaxUrl not available');
					closeSelect();
					return;
				}

				sel.disabled = true;

				fetch(ajaxUrl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: new URLSearchParams({
						action:  'ecalc_change_lead_status',
						lead_id: leadId,
						status:  status,
						nonce:   nonce,
					}).toString(),
				})
					.then(function (r) { return r.text(); })
					.then(function (text) {
						var res = null;
						try { res = JSON.parse(text); } catch (e) {
							console.error('ecalc status: bad server response', text);
						}
						if (res && res.success) {
							if (badge) {
								badge.textContent = res.data.label;
								badge.className   = 'ecalc-lead-status ecalc-lead-status--' + res.data.color;
							}
						} else {
							var errCode = (res && res.data && res.data.code) ? res.data.code : 'err';
							console.error('ecalc status error [' + errCode + ']:', text);
							if (badge) {
								var savedText  = badge.textContent;
								var savedClass = badge.className;
								badge.textContent = '⚠ ' + errCode;
								badge.style.color = '#dc2626';
								setTimeout(function () {
									badge.textContent = savedText;
									badge.className   = savedClass;
									badge.style.color = '';
								}, 5000);
							}
						}
						closeSelect();
					})
					.catch(function (err) {
						console.error('ecalc status fetch error:', err);
						closeSelect();
					});
			});
		});
	}

	// -------------------------------------------------------------------------
	// Inline changelog toggle
	// -------------------------------------------------------------------------
	function initChangelogToggle() {
		document.querySelectorAll('.ecalc-changelog-toggle').forEach(function (btn) {
			btn.addEventListener('click', function () {
				toggleChangelogRow(this);
			});
		});

		var toggleAllBtn = document.getElementById('ecalc-logs-toggle-all');
		if (toggleAllBtn) {
			toggleAllBtn.addEventListener('click', function () {
				var state    = this.dataset.state; // 'open' = momentálně otevřené, klik = sbalit
				var collapse = state === 'open';

				document.querySelectorAll('.ecalc-changelog-toggle').forEach(function (btn) {
					var targetId = btn.dataset.target;
					var row      = document.getElementById(targetId);
					if (!row) return;
					var isOpen = row.style.display !== 'none';
					if (collapse && isOpen)  { toggleChangelogRow(btn); }
					if (!collapse && !isOpen) { toggleChangelogRow(btn); }
				});

				this.dataset.state = collapse ? 'closed' : 'open';
				this.innerHTML     = collapse
					? '&#9656; Rozbalit logy'
					: '&#9660; Sbalit logy';
			});
		}
	}

	function toggleChangelogRow(btn) {
		var targetId = btn.dataset.target;
		var row      = document.getElementById(targetId);
		if (!row) return;

		var isOpen = row.style.display !== 'none';
		row.style.display = isOpen ? 'none' : '';
		btn.setAttribute('aria-expanded', String(!isOpen));

		var countSpan = btn.querySelector('.ecalc-log-count');
		var countHtml = countSpan ? ' ' + countSpan.outerHTML : '';
		btn.innerHTML = (isOpen ? '&#9656;' : '&#9660;') + ' Log' + countHtml;
	}

	// -------------------------------------------------------------------------
	// Resend to SmartEmailing
	// -------------------------------------------------------------------------
	function initResendButtons() {
		document.querySelectorAll('.ecalc-resend-btn').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var leadId = this.dataset.leadId;
				var nonce  = this.dataset.nonce;
				var status = this.nextElementSibling;

				btn.disabled = true;
				if (status) status.textContent = 'Odesílám...';

				var data = new URLSearchParams({
					action:  'ecalc_resend_smartemailing',
					lead_id: leadId,
					nonce:   nonce,
				});

				fetch(window.ajaxurl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: data.toString(),
				})
					.then(function (r) { return r.json(); })
					.then(function (res) {
						btn.disabled = false;
						if (status) {
							status.textContent = res.success
								? 'Hotovo – status: ' + (res.data && res.data.status)
								: 'Chyba: ' + (res.data || 'neznámá');
						}
					})
					.catch(function () {
						btn.disabled = false;
						if (status) status.textContent = 'Chyba komunikace.';
					});
			});
		});
	}

	// -------------------------------------------------------------------------
	// Test připojení – SmartEmailing & Turnstile
	// -------------------------------------------------------------------------
	function initConnectionTests() {
		var cfg = (typeof ecalcAdmin !== 'undefined') ? ecalcAdmin : {};

		function runTest(btnId, resultId, action, nonce) {
			var btn    = document.getElementById(btnId);
			var result = document.getElementById(resultId);
			if (!btn || !result) return;

			btn.addEventListener('click', function () {
				btn.disabled = true;
				result.textContent = 'Testuji...';
				result.style.color = '#666';

				fetch(cfg.ajaxurl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: new URLSearchParams({ action: action, nonce: nonce }).toString(),
				})
					.then(function (r) { return r.json(); })
					.then(function (res) {
						btn.disabled = false;
						var msg = (res.data && res.data.message) ? res.data.message : (res.success ? 'OK' : 'Neznámá chyba');
						result.textContent = (res.success ? '✓ ' : '✗ ') + msg;
						result.style.color = res.success ? '#16a34a' : '#dc2626';
					})
					.catch(function () {
						btn.disabled = false;
						result.textContent = '✗ Chyba komunikace se serverem.';
						result.style.color = '#dc2626';
					});
			});
		}

		runTest('ecalc-test-se', 'ecalc-test-se-result', 'ecalc_test_smartemailing', cfg.nonceTestSE || '');
		runTest('ecalc-test-ts', 'ecalc-test-ts-result', 'ecalc_test_turnstile',    cfg.nonceTestTS || '');
	}

})();
