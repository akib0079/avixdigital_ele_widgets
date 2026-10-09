/*!
 * Avix Digital · Case Study admin
 * Tabs, character counters, media pickers, colour picker and soft validation
 * for the "Case study details" box, plus the starter and importer buttons.
 * Works without it: every panel is visible and the server sanitises anyway.
 */
(function (window, document, $) {
	'use strict';

	var cfg = window.avixCsAdmin || { postId: 0, i18n: {} };
	var t = cfg.i18n || {};
	var STORE_KEY = 'avixCsTab:' + (cfg.postId || 'new');

	function store(key, value) {
		try {
			if (value === undefined) {
				return window.sessionStorage.getItem(key);
			}
			window.sessionStorage.setItem(key, value);
		} catch (e) {
			return null;
		}
		return null;
	}

	function forEach(list, fn) {
		Array.prototype.forEach.call(list || [], fn);
	}

	function strLen(value) {
		// Count code points like PHP's mb_strlen, so emoji do not count double.
		var n = 0;
		for (var i = 0; i < value.length; i++) {
			var c = value.charCodeAt(i);
			if (c < 0xd800 || c > 0xdbff) {
				n++;
			}
		}
		return n;
	}

	/* ---------- Tabs ---------- */

	function Tabs(box) {
		this.box = box;
		this.tabs = box.querySelectorAll('[role="tab"]');
		this.panels = box.querySelectorAll('[role="tabpanel"]');
		this.box.classList.add('is-js');
		var self = this;

		forEach(this.tabs, function (tab, i) {
			tab.addEventListener('click', function () {
				self.select(i, true);
			});
			tab.addEventListener('keydown', function (e) {
				var n = self.tabs.length;
				var next = null;
				if (e.key === 'ArrowRight') {
					next = (i + 1) % n;
				} else if (e.key === 'ArrowLeft') {
					next = (i - 1 + n) % n;
				} else if (e.key === 'Home') {
					next = 0;
				} else if (e.key === 'End') {
					next = n - 1;
				}
				if (next !== null) {
					e.preventDefault();
					self.select(next, true);
				}
			});
		});

		var saved = store(STORE_KEY);
		var start = 0;
		forEach(this.tabs, function (tab, i) {
			if (saved && tab.getAttribute('data-tab') === saved) {
				start = i;
			}
		});
		this.select(start, false);
	}

	Tabs.prototype.select = function (index, focus) {
		var self = this;
		forEach(this.tabs, function (tab, i) {
			var on = i === index;
			tab.classList.toggle('is-active', on);
			tab.setAttribute('aria-selected', on ? 'true' : 'false');
			tab.setAttribute('tabindex', on ? '0' : '-1');
			if (on) {
				store(STORE_KEY, tab.getAttribute('data-tab'));
				if (focus) {
					tab.focus();
				}
			}
		});
		forEach(this.panels, function (panel, i) {
			var on = i === index;
			panel.classList.toggle('is-active', on);
			panel.hidden = !on;
		});
		// A colour picker or textarea in a newly shown panel may need a layout pass.
		if (self.onSelect) {
			self.onSelect(index);
		}
	};

	/* ---------- Counters + progress ---------- */

	function updateCounter(input) {
		var counter = document.querySelector('.avix-cs-count[data-for="' + input.id + '"]');
		if (!counter) {
			return;
		}
		var max = parseInt(input.getAttribute('data-max'), 10) || 0;
		var len = strLen(input.value || '');
		counter.textContent = len + ' / ' + max;
		counter.classList.toggle('is-near', max > 0 && len >= max * 0.9 && len <= max);
		counter.classList.toggle('is-over', max > 0 && len > max);
	}

	function itemFilled(item) {
		var inputs = item.querySelectorAll('input:not([type="checkbox"]):not([type="radio"]), textarea');
		if (item.hasAttribute('data-metric')) {
			var all = inputs.length > 0;
			forEach(inputs, function (input) {
				if (!String(input.value || '').trim()) {
					all = false;
				}
			});
			return all;
		}
		var any = false;
		forEach(inputs, function (input) {
			if (String(input.value || '').trim()) {
				any = true;
			}
		});
		return any;
	}

	function updateProgress(box) {
		forEach(box.querySelectorAll('[role="tabpanel"]'), function (panel) {
			var tab = panel.getAttribute('data-panel');
			var items = panel.querySelectorAll('[data-count]');
			var filled = 0;
			forEach(items, function (item) {
				if (itemFilled(item)) {
					filled++;
				}
			});
			var badge = box.querySelector('[data-tab-count="' + tab + '"]');
			if (badge) {
				badge.textContent = filled + '/' + items.length;
				badge.classList.toggle('is-done', items.length > 0 && filled === items.length);
			}
		});
		forEach(box.querySelectorAll('[data-metric]'), function (row) {
			var inputs = row.querySelectorAll('input');
			var some = false;
			forEach(inputs, function (input) {
				if (String(input.value || '').trim()) {
					some = true;
				}
			});
			var state = row.querySelector('[data-metric-state]');
			var incomplete = some && !itemFilled(row);
			row.classList.toggle('is-incomplete', incomplete);
			if (state) {
				state.hidden = !incomplete;
			}
		});
	}

	/* ---------- Soft validation (the server is the real gate) ---------- */

	var RULES = {
		year: function (v) {
			return /^\d{4}$/.test(v);
		},
		url: function (v) {
			return /^https?:\/\/[^\s/$.?#][^\s]*$/i.test(v);
		},
		hex: function (v) {
			return /^#?([0-9a-f]{3}|[0-9a-f]{6})$/i.test(v);
		}
	};

	function validate(input) {
		var rule = input.getAttribute('data-validate');
		if (!rule || !RULES[rule]) {
			return;
		}
		var value = String(input.value || '').trim();
		var ok = value === '' || RULES[rule](value);
		var field = input.closest('.avix-cs-field');
		var error = document.getElementById(input.id + '-error');
		if (field) {
			field.classList.toggle('has-error', !ok);
		}
		input.setAttribute('aria-invalid', ok ? 'false' : 'true');
		if (error) {
			error.textContent = ok ? '' : (t[rule] || '');
			error.hidden = ok;
			var described = (input.getAttribute('aria-describedby') || '').split(' ').filter(function (id) {
				return id && id !== error.id;
			});
			if (!ok) {
				described.unshift(error.id);
			}
			input.setAttribute('aria-describedby', described.join(' '));
		}
	}

	/* ---------- Media pickers ---------- */

	function Media(card, onChange) {
		this.card = card;
		this.input = card.querySelector('[data-media-input]');
		this.preview = card.querySelector('.avix-cs-media__preview');
		this.file = card.querySelector('[data-media-file]');
		this.remove = card.querySelector('[data-media-remove]');
		this.openers = card.querySelectorAll('[data-media-open]');
		this.onChange = onChange;
		this.frame = null;
		var self = this;

		forEach(this.openers, function (btn) {
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				self.open();
			});
		});
		if (this.remove) {
			this.remove.addEventListener('click', function (e) {
				e.preventDefault();
				self.set(null);
			});
		}
	}

	Media.prototype.open = function () {
		if (!window.wp || !window.wp.media) {
			return;
		}
		var self = this;
		if (!this.frame) {
			var label = this.card.querySelector('.avix-cs-field__label');
			this.frame = window.wp.media({
				title: label ? label.textContent : (t.choose || 'Choose image'),
				library: { type: 'image' },
				button: { text: t.use || 'Use this image' },
				multiple: false
			});
			this.frame.on('open', function () {
				var id = parseInt(self.input.value, 10);
				var selection = self.frame.state().get('selection');
				selection.reset();
				if (id) {
					var att = window.wp.media.attachment(id);
					att.fetch();
					selection.add(att);
				}
			});
			this.frame.on('select', function () {
				var att = self.frame.state().get('selection').first();
				if (att) {
					self.set(att.toJSON());
				}
			});
		}
		this.frame.open();
	};

	Media.prototype.set = function (att) {
		var img = this.preview.querySelector('img');
		if (!att) {
			this.input.value = '';
			if (img) {
				img.parentNode.removeChild(img);
			}
			if (this.file) {
				this.file.textContent = '';
			}
			this.card.classList.remove('has-image');
			if (this.remove) {
				this.remove.hidden = true;
			}
			this.openers[this.openers.length - 1].textContent = t.choose || 'Choose image';
		} else {
			var sizes = att.sizes || {};
			var src = (sizes.medium_large || sizes.large || sizes.medium || sizes.full || {}).url || att.url;
			if (!img) {
				img = document.createElement('img');
				img.alt = '';
				img.decoding = 'async';
				this.preview.insertBefore(img, this.preview.firstChild);
			}
			img.src = src;
			this.input.value = String(att.id);
			if (this.file) {
				this.file.textContent = (att.filename || '') + (att.width ? ' · ' + att.width + ' × ' + att.height : '');
			}
			this.card.classList.add('has-image');
			if (this.remove) {
				this.remove.hidden = false;
			}
			this.openers[this.openers.length - 1].textContent = t.replace || 'Replace';
		}
		if (this.onChange) {
			this.onChange();
		}
	};

	/* ---------- Details box ---------- */

	function mountBox(box) {
		if (!box || box.__avixCs) {
			return;
		}
		box.__avixCs = true;

		var refresh = function () {
			updateProgress(box);
		};

		var tabs = new Tabs(box);

		forEach(box.querySelectorAll('[data-max]'), function (input) {
			updateCounter(input);
			input.addEventListener('input', function () {
				updateCounter(input);
			});
		});

		forEach(box.querySelectorAll('[data-validate]'), function (input) {
			validate(input);
			input.addEventListener('blur', function () {
				validate(input);
			});
			input.addEventListener('input', function () {
				if (input.getAttribute('aria-invalid') === 'true') {
					validate(input);
				}
			});
		});

		box.addEventListener('input', refresh);
		box.addEventListener('change', refresh);

		forEach(box.querySelectorAll('[data-media]'), function (card) {
			card.__avixMedia = new Media(card, refresh);
		});

		if ($ && $.fn && $.fn.wpColorPicker) {
			$(box).find('.avix-cs-color').each(function () {
				var input = this;
				$(input).wpColorPicker({
					change: function (e, ui) {
						// The picker updates the field after this event: read the colour it reports.
						input.value = ui && ui.color ? ui.color.toString() : input.value;
						validate(input);
						refresh();
					},
					clear: function () {
						input.value = '';
						validate(input);
						refresh();
					}
				});
			});
		}

		tabs.onSelect = refresh;
		refresh();

		// The excerpt box sits outside: give it the same counter.
		var excerpt = document.getElementById('excerpt');
		if (excerpt && excerpt.hasAttribute('data-max')) {
			updateCounter(excerpt);
			excerpt.addEventListener('input', function () {
				updateCounter(excerpt);
			});
		}
	}

	/* ---------- Starter buttons ---------- */

	function bindStarter() {
		forEach(document.querySelectorAll('[data-starter]'), function (link) {
			link.addEventListener('click', function (e) {
				if (link.getAttribute('data-starter') === 'reset' && !window.confirm(t.confirmReset || 'Replace the layout?')) {
					e.preventDefault();
					return;
				}
				// WordPress warns about unsaved edits on its own (beforeunload), which is what we want here.
				link.classList.add('is-busy');
				link.setAttribute('aria-busy', 'true');
			});
		});
	}

	/* ---------- Importer ---------- */

	function bindImporter() {
		var form = document.querySelector('[data-import-form]');
		if (!form) {
			return;
		}
		var all = form.querySelector('[data-import-all]');
		var boxes = form.querySelectorAll('input[name="slugs[]"]');
		var run = form.querySelector('[data-import-run]');
		var spinner = form.querySelector('[data-import-spinner]');

		function sync() {
			var on = 0;
			forEach(boxes, function (b) {
				if (b.checked) {
					on++;
				}
			});
			if (all) {
				all.checked = on === boxes.length;
				all.indeterminate = on > 0 && on < boxes.length;
			}
			if (run) {
				run.disabled = on === 0;
			}
		}

		if (all) {
			all.addEventListener('change', function () {
				forEach(boxes, function (b) {
					b.checked = all.checked;
				});
				sync();
			});
		}
		forEach(boxes, function (b) {
			b.addEventListener('change', sync);
		});
		form.addEventListener('submit', function () {
			if (run) {
				run.disabled = true;
				run.textContent = t.running || 'Importing…';
			}
			if (spinner) {
				spinner.classList.add('is-active');
			}
		});
		sync();
	}

	function boot() {
		forEach(document.querySelectorAll('[data-avix-cs-box]'), mountBox);
		bindStarter();
		bindImporter();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})(window, document, window.jQuery);
