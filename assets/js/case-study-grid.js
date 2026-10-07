/*!
 * Avix Digital · Case Study Grid
 * Service chips swap the cards and "Load more" adds the next ones through
 * admin-ajax (action avix_case_studies), keeping ?service= in the address.
 * Without this file the chips and the button are plain links that still
 * work (?service= / ?pg=). Also: one-shot reveals with a stagger. Nothing
 * runs off screen; reduced motion skips every animation.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-csi]';
	var reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };
	var instances = [];

	function isEditMode() {
		return !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
	}

	function closest(node, selector, stop) {
		while (node && node !== stop && node.nodeType === 1) {
			if (node.matches ? node.matches(selector) : node.msMatchesSelector(selector)) {
				return node;
			}
			node = node.parentNode;
		}
		return null;
	}

	function format(template, values) {
		return String(template || '').replace(/%(\d)\$s/g, function (match, n) {
			var value = values[parseInt(n, 10) - 1];
			return value === undefined ? '' : String(value);
		});
	}

	function Grid(root) {
		this.root = root;
		this.alive = true;
		this.timers = {};
		this.cfg = {};
		try {
			this.cfg = JSON.parse(root.getAttribute('data-avix-csi') || '{}') || {};
		} catch (error) {
			this.cfg = {};
		}

		this.grid = root.querySelector('[data-csi-grid]');
		this.more = root.querySelector('[data-csi-more]');
		this.moreText = root.querySelector('[data-csi-more-text]');
		this.status = root.querySelector('[data-csi-status]');
		this.bar = root.querySelector('[data-csi-bar]');
		this.foot = root.querySelector('[data-csi-foot]');
		this.empty = root.querySelector('[data-csi-empty]');
		this.announcer = root.querySelector('[data-csi-announce]');
		this.rail = root.querySelector('.avix-csi__rail');
		this.row = root.querySelector('[data-csi-row]');

		this.page = parseInt(this.cfg.page, 10) || 1;
		this.service = this.cfg.service || '';
		this.total = parseInt(this.cfg.total, 10) || 0;
		this.shown = parseInt(this.cfg.shown, 10) || 0;
		this.moreLabel = this.moreText ? this.moreText.textContent : '';
		this.xhr = null;
		// The request in flight: { kind: 'filter' | 'more', service, page }.
		this.pending = null;

		this.onClick = this.click.bind(this);
		this.onKey = this.key.bind(this);
		this.onRowScroll = this.rowScroll.bind(this);

		this.init();
	}

	Grid.prototype.init = function () {
		var self = this;
		var root = this.root;
		var editor = isEditMode();

		if (!this.grid) {
			return;
		}
		root.classList.add('is-ready');
		if (editor) {
			root.classList.add('is-editor');
		}

		this.armed = 'IntersectionObserver' in window && !reduceMotion.matches && !editor;
		if (this.armed) {
			root.classList.add('is-armed');
			this.revealer = new window.IntersectionObserver(function (entries) {
				self.revealed(entries);
			}, { threshold: 0.1, rootMargin: '0px 0px -6% 0px' });
			this.reveal(root.querySelectorAll('[data-csi-rv], [data-csi-card]'));
		}

		// With the script, "Load more" acts in place: announce it as a button.
		if (this.more) {
			this.more.setAttribute('role', 'button');
			root.addEventListener('keydown', this.onKey);
		}
		if (this.row) {
			this.row.addEventListener('scroll', this.onRowScroll, { passive: true });
			this.rowScroll();
		}
		root.addEventListener('click', this.onClick);
	};

	/* ---------- Reveal ---------- */

	/* now: cards swapped in by a filter or "Load more" rise at once when any
	   part shows, one after another from the first new card. */
	Grid.prototype.reveal = function (nodes, now) {
		if (!this.revealer) {
			return;
		}
		var step = 0;
		var limit = window.innerHeight || document.documentElement.clientHeight;
		var list = Array.prototype.filter.call(nodes, function (node) {
			return !node.classList.contains('is-in');
		});
		// All reads first, then all writes: one layout pass, not one per card.
		var tops = now ? list.map(function (node) {
			return node.getBoundingClientRect().top;
		}) : [];
		list.forEach(function (node, i) {
			if (now && tops[i] < limit) {
				node.style.setProperty('--csi-d', Math.min(step++, 5) * 90 + 'ms');
				node.classList.add('is-in');
				return;
			}
			this.revealer.observe(node);
		}, this);
	};

	Grid.prototype.revealed = function (entries) {
		var step = 0;
		var revealer = this.revealer;
		entries.forEach(function (entry) {
			if (!entry.isIntersecting) {
				return;
			}
			// Items entering together rise one after another.
			entry.target.style.setProperty('--csi-d', Math.min(step, 5) * 90 + 'ms');
			entry.target.classList.add('is-in');
			revealer.unobserve(entry.target);
			step++;
		});
	};

	/* ---------- Clicks ---------- */

	Grid.prototype.click = function (event) {
		if (!this.alive) {
			return;
		}
		if (!this.root.isConnected) {
			this.destroy();
			return;
		}
		if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
			return;
		}
		var chip = closest(event.target, '[data-csi-service]', this.root);
		if (chip) {
			event.preventDefault();
			this.select(chip.getAttribute('data-csi-service') || '', chip);
			return;
		}
		var more = closest(event.target, '[data-csi-more]', this.root);
		if (more) {
			event.preventDefault();
			this.loadMore();
		}
	};

	/* Space presses a role=button link, as it would a real button. */
	Grid.prototype.key = function (event) {
		if ((event.key === ' ' || event.key === 'Spacebar') && closest(event.target, '[data-csi-more]', this.root)) {
			event.preventDefault();
			this.loadMore();
		}
	};

	Grid.prototype.select = function (service, chip) {
		var self = this;
		// Compare with the filter on its way, so "All" right after "Shopify"
		// still runs (and replaces the Shopify request).
		var current = this.pending && this.pending.kind === 'filter' ? this.pending.service : this.service;
		if (service === current && !this.failed) {
			return;
		}
		this.abort();
		this.markChips(service);
		this.setBusy(true);
		this.request({ kind: 'filter', page: 1, service: service }, function (data) {
			self.replace(data);
		}, function () {
			window.location.href = chip.href;
		});
	};

	Grid.prototype.markChips = function (service) {
		var row = this.row;
		Array.prototype.forEach.call(this.root.querySelectorAll('.avix-csi__chip[data-csi-service]'), function (chip) {
			var on = (chip.getAttribute('data-csi-service') || '') === service;
			chip.classList.toggle('is-active', on);
			if (on) {
				chip.setAttribute('aria-current', 'true');
				// Keep the picked chip in view in the swipeable row.
				if (row && row.scrollWidth > row.clientWidth && row.scrollTo) {
					var left = chip.getBoundingClientRect().left - row.getBoundingClientRect().left + row.scrollLeft - 24;
					row.scrollTo({ left: Math.max(0, left), behavior: reduceMotion.matches ? 'auto' : 'smooth' });
				}
			} else {
				chip.removeAttribute('aria-current');
			}
		});
	};

	/* Soft edges on the chip row: left once scrolled, right until the end. */
	Grid.prototype.rowScroll = function () {
		var self = this;
		if (this.rowFrame) {
			return;
		}
		this.rowFrame = window.requestAnimationFrame(function () {
			self.rowFrame = 0;
			var row = self.row;
			if (!row || !self.alive) {
				return;
			}
			var left = row.scrollLeft;
			row.classList.toggle('is-scrolled', left > 2);
			row.classList.toggle('is-end', left + row.clientWidth >= row.scrollWidth - 2);
		});
	};

	Grid.prototype.loadMore = function () {
		var self = this;
		// One "more" at a time, and never on top of a filter that is loading.
		if (this.loading || !this.more || this.pending) {
			return;
		}
		this.loading = true;
		this.more.classList.add('is-loading');
		this.more.setAttribute('aria-busy', 'true');
		if (this.moreText && this.cfg.loading) {
			this.moreText.textContent = this.cfg.loading;
		}
		this.request({ kind: 'more', page: this.page + 1, service: this.service }, function (data) {
			self.append(data);
		}, function () {
			self.settle('more');
			window.location.href = self.more.href;
		});
	};

	/* Undo what a request set up: the busy grid or the loading button. */
	Grid.prototype.settle = function (kind) {
		if (kind === 'more') {
			this.loading = false;
			if (this.more) {
				this.more.classList.remove('is-loading');
				this.more.removeAttribute('aria-busy');
			}
			if (this.moreText) {
				this.moreText.textContent = this.moreLabel;
			}
		} else if (kind === 'filter') {
			this.setBusy(false);
		}
	};

	/* Cancel the request in flight and reset the state it owned. */
	Grid.prototype.abort = function () {
		var pending = this.pending;
		if (this.xhr) {
			this.xhr.onload = this.xhr.onerror = this.xhr.ontimeout = null;
			this.xhr.abort();
			this.xhr = null;
		}
		this.pending = null;
		if (pending) {
			this.settle(pending.kind);
			if (pending.kind === 'filter') {
				this.markChips(this.service);
			}
		}
	};

	/* ---------- AJAX ---------- */

	Grid.prototype.request = function (params, done, fail) {
		var self = this;
		var cfg = this.cfg;
		if (!cfg.ajax) {
			fail();
			return;
		}
		this.abort();
		var pending = { kind: params.kind, service: params.service, page: params.page };
		this.pending = pending;
		var fields = {
			action: cfg.action || 'avix_case_studies',
			page: params.page,
			per_page: cfg.perPage,
			service: params.service,
			services: cfg.services,
			exclude: cfg.exclude,
			orderby: cfg.orderby,
			featured: cfg.featured,
			style: cfg.style,
			metric: cfg.metric,
			tag: cfg.tag,
			label: cfg.label,
			uid: cfg.uid
		};
		var body = Object.keys(fields).map(function (key) {
			var value = fields[key];
			return encodeURIComponent(key) + '=' + encodeURIComponent(value === undefined || value === null ? '' : value);
		}).join('&');

		// GET: the answer is public and the same for everyone, so the browser,
		// the CDN and the page cache's AJAX cache may keep it (POST never is).
		var xhr = new window.XMLHttpRequest();
		this.xhr = xhr;
		xhr.open('GET', cfg.ajax + (cfg.ajax.indexOf('?') < 0 ? '?' : '&') + body, true);
		xhr.timeout = 15000;
		xhr.onload = function () {
			var data = null;
			// Superseded meanwhile: its state was already reset by abort().
			if (!self.alive || self.xhr !== xhr || self.pending !== pending) {
				return;
			}
			self.xhr = null;
			self.pending = null;
			try {
				data = JSON.parse(xhr.responseText);
			} catch (error) {
				data = null;
			}
			if (xhr.status >= 200 && xhr.status < 300 && data && typeof data.html === 'string' && (data.service || '') === (pending.service || '') && (parseInt(data.page, 10) || 1) === pending.page) {
				self.failed = false;
				done(data);
			} else {
				self.failed = true;
				fail();
			}
		};
		xhr.onerror = xhr.ontimeout = function () {
			if (!self.alive || self.xhr !== xhr) {
				return;
			}
			self.xhr = null;
			self.pending = null;
			self.failed = true;
			fail();
		};
		xhr.send();
	};

	Grid.prototype.parse = function (html) {
		var holder = document.createElement('div');
		holder.innerHTML = html || '';
		return Array.prototype.slice.call(holder.children);
	};

	/* A new filter: swap the cards. The rail stays where it was clicked. */
	Grid.prototype.replace = function (data) {
		var grid = this.grid;
		var cards = this.parse(data.html);
		var anchor = this.rail || grid;
		var before = anchor.getBoundingClientRect().top;

		while (grid.firstChild) {
			grid.removeChild(grid.firstChild);
		}
		cards.forEach(function (card) {
			grid.appendChild(card);
		});

		this.service = data.service || '';
		this.page = 1;
		this.total = parseInt(data.total, 10) || 0;
		this.shown = parseInt(data.shown, 10) || 0;
		this.update(!!data.hasMore);
		this.setBusy(false);
		this.updateUrl();

		var shift = anchor.getBoundingClientRect().top - before;
		if (Math.abs(shift) > 1 && before < (window.innerHeight || 0)) {
			// Instant, even where the theme sets smooth scrolling on <html>.
			try {
				window.scrollBy({ top: shift, left: 0, behavior: 'instant' });
			} catch (error) {
				window.scrollBy(0, shift);
			}
		}
		this.reveal(cards, true);
		if (this.empty) {
			this.empty.hidden = this.total > 0;
		}
		this.announceFilter();
	};

	Grid.prototype.append = function (data) {
		var grid = this.grid;
		var cards = this.parse(data.html);
		cards.forEach(function (card) {
			grid.appendChild(card);
		});
		this.settle('more');
		this.page = parseInt(data.page, 10) || this.page + 1;
		this.total = parseInt(data.total, 10) || this.total;
		this.shown = parseInt(data.shown, 10) || this.shown;
		this.update(!!data.hasMore);
		this.reveal(cards, true);
		if (this.status && this.cfg.countText) {
			this.announce(this.status.textContent);
		}
		// Keyboard and screen-reader users continue at the first new card.
		var link = cards.length ? cards[0].querySelector('.avix-csi__link') : null;
		if (link) {
			try {
				link.focus({ preventScroll: true });
			} catch (error) {
				link.focus();
			}
		}
	};

	/* Status line, progress bar and the next-page link. */
	Grid.prototype.update = function (hasMore) {
		var total = this.total;
		var shown = Math.min(this.shown, total);
		if (this.foot) {
			// Nothing to load and nothing loaded yet: "Showing 3 of 3" adds nothing.
			this.foot.hidden = !hasMore && this.page === 1;
		}
		if (this.status && this.cfg.countText) {
			var status = this.status;
			while (status.firstChild) {
				status.removeChild(status.firstChild);
			}
			String(this.cfg.countText).split(/(\{shown\}|\{total\})/).forEach(function (part) {
				if (part === '{shown}' || part === '{total}') {
					var strong = document.createElement('strong');
					strong.textContent = (part === '{shown}' ? shown : total).toLocaleString();
					status.appendChild(strong);
				} else if (part) {
					status.appendChild(document.createTextNode(part));
				}
			});
		}
		if (this.bar) {
			this.bar.style.setProperty('--csi-progress', total ? Math.min(1, shown / total).toFixed(4) : '0');
		}
		if (this.more) {
			this.more.hidden = !hasMore;
			this.more.href = this.url({ page: this.page + 1 });
		}
		// The no-JS "Previous page" link belongs to the page it was printed on.
		var prev = this.root.querySelector('.avix-csi__prev');
		if (prev && this.page === 1) {
			prev.hidden = true;
		}
	};

	Grid.prototype.setBusy = function (busy) {
		this.grid.classList.toggle('is-busy', busy);
		this.grid.setAttribute('aria-busy', busy ? 'true' : 'false');
	};

	/* ---------- Screen-reader updates ---------- */

	Grid.prototype.announce = function (text) {
		var node = this.announcer;
		if (!node || !text) {
			return;
		}
		node.textContent = '';
		// A fresh text node a moment later is read even when it repeats.
		this.later('announce', function () {
			node.textContent = text;
		}, 120);
	};

	Grid.prototype.announceFilter = function () {
		var service = this.service;
		var chip = null;
		Array.prototype.forEach.call(this.root.querySelectorAll('.avix-csi__chip[data-csi-service]'), function (node) {
			if (!chip && (node.getAttribute('data-csi-service') || '') === service) {
				chip = node;
			}
		});
		var name = chip ? chip.getAttribute('data-csi-name') || chip.textContent.trim() : '';
		var template = this.total === 1 && this.cfg.announce1 ? this.cfg.announce1 : this.cfg.announce;
		if (template) {
			this.announce(format(template, [name, this.total.toLocaleString()]));
		}
	};

	/* ---------- Address ---------- */

	/* The page's address with this view's filter (and an optional page). */
	Grid.prototype.url = function (opts) {
		var cfg = this.cfg;
		if (!window.URL) {
			return this.more ? this.more.href : window.location.href;
		}
		var url = new window.URL(window.location.href);
		url.searchParams.delete(cfg.pageVar || 'pg');
		url.searchParams.delete(cfg.serviceVar || 'service');
		if (this.service) {
			url.searchParams.set(cfg.serviceVar || 'service', this.service);
		}
		if (opts && opts.page > 1) {
			url.searchParams.set(cfg.pageVar || 'pg', opts.page);
		}
		url.hash = '';
		return url.toString();
	};

	Grid.prototype.updateUrl = function () {
		if (!this.cfg.urlState || isEditMode() || !window.history || !window.history.replaceState || !window.URL) {
			return;
		}
		try {
			window.history.replaceState(window.history.state, '', this.url());
		} catch (error) {
			// Some sandboxed frames refuse; the view still works.
		}
	};

	/* ---------- Timers & teardown ---------- */

	Grid.prototype.later = function (name, fn, ms) {
		this.cancel(name);
		this.timers[name] = window.setTimeout(fn, ms);
	};

	Grid.prototype.cancel = function (name) {
		window.clearTimeout(this.timers[name]);
		delete this.timers[name];
	};

	Grid.prototype.destroy = function () {
		if (!this.alive) {
			return;
		}
		this.alive = false;
		Object.keys(this.timers).forEach(this.cancel, this);
		if (this.xhr) {
			this.xhr.onload = this.xhr.onerror = this.xhr.ontimeout = null;
			this.xhr.abort();
			this.xhr = null;
		}
		this.pending = null;
		if (this.rowFrame) {
			window.cancelAnimationFrame(this.rowFrame);
		}
		if (this.row) {
			this.row.removeEventListener('scroll', this.onRowScroll);
		}
		if (this.revealer) {
			this.revealer.disconnect();
		}
		this.root.removeEventListener('click', this.onClick);
		this.root.removeEventListener('keydown', this.onKey);
	};

	/* ---------- Mounting ---------- */

	function mount(root) {
		if (!root || (root.__avixCsi && root.__avixCsi.alive)) {
			return;
		}
		// Editor re-renders replace the DOM: drop instances whose root is gone.
		instances = instances.filter(function (instance) {
			if (!instance.root.isConnected) {
				instance.destroy();
				return false;
			}
			return true;
		});
		root.__avixCsi = new Grid(root);
		instances.push(root.__avixCsi);
	}

	function mountAll(scope) {
		var base = scope && scope.querySelectorAll ? scope : document;
		if (base.matches && base.matches(ROOT_SELECTOR)) {
			mount(base);
		}
		Array.prototype.forEach.call(base.querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixCsGrid = { mount: mount, mountAll: mountAll };

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			mountAll();
		});
	} else {
		mountAll();
	}

	function hookElementor() {
		if (!window.elementorFrontend || !window.elementorFrontend.hooks) {
			return;
		}
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-case-study-grid.default', function ($scope) {
			mountAll($scope && $scope[0] ? $scope[0] : $scope);
		});
	}

	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		hookElementor();
	} else {
		window.addEventListener('elementor/frontend/init', hookElementor);
	}
})(window, document);
