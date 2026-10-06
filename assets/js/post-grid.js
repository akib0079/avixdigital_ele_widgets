/*!
 * Avix Digital · Post Grid
 * Topic chips swap the grid and "Load more" adds posts through admin-ajax
 * (action avix_posts), keeping ?topic= in the address. Without this file the
 * chips and the button are plain links that still work. Also: one-shot
 * reveals, and the reading pixel character that looks up while visitors
 * browse. Nothing runs off screen; reduced motion skips every animation.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-pg]';
	var reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };
	var finePointer = window.matchMedia ? window.matchMedia('(hover: hover) and (pointer: fine)') : { matches: false };
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

	function pal() {
		return window.AvixPal || null;
	}

	function PostGrid(root) {
		this.root = root;
		this.alive = true;
		this.timers = {};
		this.cfg = {};
		try {
			this.cfg = JSON.parse(root.getAttribute('data-avix-pg') || '{}') || {};
		} catch (error) {
			this.cfg = {};
		}

		this.grid = root.querySelector('[data-pg-grid]');
		this.more = root.querySelector('[data-pg-more]');
		this.moreText = root.querySelector('[data-pg-more-text]');
		this.status = root.querySelector('[data-pg-status]');
		this.bar = root.querySelector('[data-pg-bar]');
		this.foot = root.querySelector('[data-pg-foot]');
		this.empty = root.querySelector('[data-pg-empty]');
		this.announcer = root.querySelector('[data-pg-announce]');
		this.cta = root.querySelector('[data-pg-cta]');
		this.feature = root.querySelector('[data-pg-feature]');
		this.rail = root.querySelector('.avix-pg__rail');
		this.row = root.querySelector('.avix-pg__topics');
		this.blink = root.querySelector('[data-pg-blink]');
		this.pal = root.querySelector('[data-pg-pal]');
		this.ctaPal = this.cta ? this.cta.querySelector('.avix-pal') : null;

		this.page = parseInt(this.cfg.page, 10) || 1;
		this.topic = this.cfg.topic || '';
		this.total = parseInt(this.cfg.total, 10) || 0;
		this.shown = parseInt(this.cfg.shown, 10) || 0;
		// 1 when a featured post exists: it counts on the "All" view only.
		this.extra = parseInt(this.cfg.extra, 10) ? 1 : 0;
		this.moreLabel = this.moreText ? this.moreText.textContent : '';
		this.xhr = null;
		// The request in flight: { kind: 'topic' | 'more', topic, page }.
		this.pending = null;
		this.palSeen = false;
		this.awake = false;
		this.lastWave = 0;

		this.onClick = this.click.bind(this);
		this.onOver = this.over.bind(this);
		this.onLeave = this.leave.bind(this);
		this.onCtaEnter = this.ctaEnter.bind(this);
		this.onVisibility = this.visibility.bind(this);
		this.onRowScroll = this.rowScroll.bind(this);
		this.onKey = this.key.bind(this);

		this.init();
	}

	PostGrid.prototype.init = function () {
		var root = this.root;
		var self = this;
		var editor = isEditMode();

		root.classList.add('is-ready');
		if (editor) {
			root.classList.add('is-editor');
		}

		this.armed = 'IntersectionObserver' in window && !reduceMotion.matches && !editor;
		if (this.armed) {
			root.classList.add('is-armed');
			this.revealer = new window.IntersectionObserver(function (entries) {
				self.revealed(entries);
			}, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
			this.reveal(root.querySelectorAll('[data-pg-rv], [data-pg-card], [data-pg-cta]'));
		}

		// Pause the character's loops off screen and in hidden tabs.
		if ('IntersectionObserver' in window) {
			this.pauser = new window.IntersectionObserver(function (entries) {
				if (!root.isConnected) {
					self.destroy();
					return;
				}
				self.visible = entries[entries.length - 1].isIntersecting;
				self.visibility();
			});
			this.pauser.observe(root);

			// The character reacts to browsing only while it can be seen.
			if (this.pal) {
				this.palWatcher = new window.IntersectionObserver(function (entries) {
					self.palSeen = entries[entries.length - 1].isIntersecting;
					if (!self.palSeen && self.awake) {
						self.leave();
					}
				});
				this.palWatcher.observe(this.pal);
			}
		} else {
			this.palSeen = true;
		}
		document.addEventListener('visibilitychange', this.onVisibility);

		// The project card's typing loop pauses while the card is out of view.
		if (this.cta && this.ctaPal && 'IntersectionObserver' in window) {
			this.ctaWatcher = new window.IntersectionObserver(function (entries) {
				var cta = self.cta;
				cta.classList.toggle('is-off', !entries[entries.length - 1].isIntersecting);
			});
			this.ctaWatcher.observe(this.cta);
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
		if (this.pal && finePointer.matches && !editor) {
			root.addEventListener('pointerover', this.onOver);
			root.addEventListener('pointerleave', this.onLeave);
		}
		if (this.cta && this.ctaPal) {
			this.cta.addEventListener('pointerenter', this.onCtaEnter);
		}

		if (this.pal && pal() && !editor) {
			// A hello once it is first seen, then the odd glance up from the book.
			var glance = function () {
				if (!self.awake && !self.root.classList.contains('is-off')) {
					self.pal.classList.add(Math.random() < 0.5 ? 'is-look-r' : 'is-look-l');
					self.lookUp(1100);
				}
			};
			this.stopIdle = pal().every(this.pal, function () {
				self.stopIdle();
				self.lookUp(2100);
				pal().play(self.pal, 'is-hi', 2100);
				self.stopIdle = pal().every(self.pal, glance, 8000, 14000);
			}, 900, 1300);
		}
	};

	/* ---------- Reveal ---------- */

	/* now: cards swapped in by a filter or "Load more" rise at once when any
	   part shows, so the grid never sits half empty next to the project card. */
	PostGrid.prototype.reveal = function (nodes, now) {
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
				node.style.setProperty('--pg-d', Math.min(step++, 5) * 70 + 'ms');
				node.classList.add('is-in');
				return;
			}
			this.revealer.observe(node);
		}, this);
	};

	PostGrid.prototype.revealed = function (entries) {
		var step = 0;
		var revealer = this.revealer;
		entries.forEach(function (entry) {
			if (!entry.isIntersecting) {
				return;
			}
			// Items entering together rise one after another.
			entry.target.style.setProperty('--pg-d', Math.min(step, 5) * 90 + 'ms');
			entry.target.classList.add('is-in');
			revealer.unobserve(entry.target);
			step++;
		});
	};

	/* ---------- Clicks ---------- */

	PostGrid.prototype.click = function (event) {
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
		var chip = closest(event.target, '[data-pg-topic]', this.root);
		if (chip) {
			event.preventDefault();
			this.select(chip.getAttribute('data-pg-topic') || '', chip);
			return;
		}
		var more = closest(event.target, '[data-pg-more]', this.root);
		if (more) {
			event.preventDefault();
			this.loadMore();
		}
	};

	/* Space presses a role=button link, as it would a real button. */
	PostGrid.prototype.key = function (event) {
		if ((event.key === ' ' || event.key === 'Spacebar') && closest(event.target, '[data-pg-more]', this.root)) {
			event.preventDefault();
			this.loadMore();
		}
	};

	PostGrid.prototype.select = function (topic, chip) {
		var self = this;
		var fromEmpty = !!(this.empty && chip && this.empty.contains(chip));
		// Compare with the topic on its way, so "All" right after "Shopify"
		// still runs (and replaces the Shopify request).
		var current = this.pending && this.pending.kind === 'topic' ? this.pending.topic : this.topic;
		if (topic === current && !this.failed) {
			return;
		}
		this.abort();
		this.markChips(topic);
		this.cheer(chip);
		this.setBusy(true);
		this.request({ kind: 'topic', page: 1, topic: topic }, function (data) {
			self.replace(data);
			// The empty state's button just hid itself: keep focus nearby.
			if (fromEmpty) {
				self.focusAfterEmpty();
			}
		}, function () {
			window.location.href = chip.href;
		});
	};

	PostGrid.prototype.focusAfterEmpty = function () {
		var target = this.root.querySelector('.avix-pg__chip[data-pg-topic=""]') || this.grid.querySelector('.avix-pg__link');
		if (target) {
			try {
				target.focus({ preventScroll: true });
			} catch (error) {
				target.focus();
			}
		}
	};

	/* The rail's own small moment (always in view while choosing), plus the
	   character's glance and hop when it is on screen too. */
	PostGrid.prototype.cheer = function (chip) {
		var blink = this.blink;
		if (blink && !reduceMotion.matches) {
			blink.classList.remove('is-blink');
			void blink.offsetWidth;
			blink.classList.add('is-blink');
			this.later('blink', function () {
				blink.classList.remove('is-blink');
			}, 1000);
		}
		if (this.pal && pal() && this.palSeen) {
			pal().lookAt(this.pal, chip);
			this.lookUp(1600);
			pal().play(this.pal, 'is-jump', 650);
		}
	};

	PostGrid.prototype.markChips = function (topic) {
		var row = this.row;
		Array.prototype.forEach.call(this.root.querySelectorAll('.avix-pg__chip[data-pg-topic]'), function (chip) {
			var on = (chip.getAttribute('data-pg-topic') || '') === topic;
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

	/* Fades on the chip row: left once scrolled, right until the end. */
	PostGrid.prototype.rowScroll = function () {
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

	PostGrid.prototype.loadMore = function () {
		var self = this;
		// One "more" at a time, and never on top of a topic that is loading:
		// the grid is about to change under it.
		if (this.loading || !this.more || this.pending) {
			return;
		}
		this.loading = true;
		this.more.classList.add('is-loading');
		this.more.setAttribute('aria-busy', 'true');
		if (this.moreText && this.cfg.loading) {
			this.moreText.textContent = this.cfg.loading;
		}
		this.request({ kind: 'more', page: this.page + 1, topic: this.topic }, function (data) {
			self.append(data);
		}, function () {
			self.settle('more');
			window.location.href = self.more.href;
		});
	};

	/* Undo what a request set up: the busy grid or the loading button. */
	PostGrid.prototype.settle = function (kind) {
		if (kind === 'more') {
			this.loading = false;
			if (this.more) {
				this.more.classList.remove('is-loading');
				this.more.removeAttribute('aria-busy');
			}
			if (this.moreText) {
				this.moreText.textContent = this.moreLabel;
			}
		} else if (kind === 'topic') {
			this.setBusy(false);
		}
	};

	/* Cancel the request in flight and reset the state it owned. */
	PostGrid.prototype.abort = function () {
		var pending = this.pending;
		if (this.xhr) {
			this.xhr.onload = this.xhr.onerror = this.xhr.ontimeout = null;
			this.xhr.abort();
			this.xhr = null;
		}
		this.pending = null;
		if (pending) {
			this.settle(pending.kind);
			if (pending.kind === 'topic') {
				this.markChips(this.topic);
			}
		}
	};

	/* ---------- AJAX ---------- */

	PostGrid.prototype.request = function (params, done, fail) {
		var self = this;
		var cfg = this.cfg;
		if (!cfg.ajax) {
			fail();
			return;
		}
		this.abort();
		var pending = { kind: params.kind, topic: params.topic, page: params.page };
		this.pending = pending;
		var fields = {
			action: cfg.action || 'avix_posts',
			page: params.page,
			per_page: cfg.perPage,
			slot: cfg.slot,
			topic: params.topic,
			cats: cfg.cats,
			exclude: cfg.exclude,
			orderby: cfg.orderby,
			order: cfg.order,
			words: cfg.words,
			excerpt: cfg.excerpt,
			image: cfg.image,
			cat: cfg.cat,
			date: cfg.date,
			time: cfg.time,
			tag: cfg.tag,
			label: cfg.label,
			time_label: cfg.timeLabel
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
			var json = null;
			// Superseded meanwhile: its state was already reset by abort().
			if (!self.alive || self.xhr !== xhr || self.pending !== pending) {
				return;
			}
			self.xhr = null;
			self.pending = null;
			try {
				json = JSON.parse(xhr.responseText);
			} catch (error) {
				json = null;
			}
			var data = json && json.success && json.data;
			if (xhr.status >= 200 && xhr.status < 300 && data && (data.topic || '') === (pending.topic || '') && (parseInt(data.page, 10) || 1) === pending.page) {
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

	PostGrid.prototype.parse = function (html) {
		var holder = document.createElement('div');
		holder.innerHTML = html || '';
		return Array.prototype.slice.call(holder.children);
	};

	/* A new topic: swap the cards, keep the project card at its slot. */
	PostGrid.prototype.replace = function (data) {
		var grid = this.grid;
		var cards = this.parse(data.html);
		var self = this;
		var topic = data.topic || '';
		// The rail stays where the visitor clicked, even when the featured
		// card above it appears or steps aside.
		var anchor = this.rail || grid;
		var before = anchor.getBoundingClientRect().top;

		Array.prototype.forEach.call(grid.querySelectorAll('[data-pg-card]'), function (card) {
			grid.removeChild(card);
		});
		cards.forEach(function (card) {
			grid.appendChild(card);
		});
		this.placeCta(cards, data.hasMore);

		// The featured card belongs to "All"; a topic lists every post.
		var feature = this.feature;
		var showFeature = !!feature && !topic;
		if (feature && feature.hidden === showFeature) {
			feature.hidden = !showFeature;
		}

		this.topic = topic;
		this.page = 1;
		this.total = data.total || 0;
		this.shown = data.shown || 0;
		this.update(data.hasMore);
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
		if (showFeature) {
			this.reveal([feature], true);
		}
		this.reveal(cards, true);

		if (this.empty) {
			this.empty.hidden = this.total + (showFeature ? 1 : 0) > 0;
			// Inside a topic: "browse every article"; with no posts at all the
			// neutral line and no button (it would lead nowhere).
			Array.prototype.forEach.call(this.empty.querySelectorAll('[data-pg-empty-topic]'), function (node) {
				node.hidden = !topic;
			});
			Array.prototype.forEach.call(this.empty.querySelectorAll('[data-pg-empty-all]'), function (node) {
				node.hidden = !!topic;
			});
		}
		this.announceTopic();
		this.later('rest', function () {
			self.rest();
		}, 1600);
	};

	/* Same rule as the server: at its slot while more posts follow, else
	   last, where the CSS widens it over the row's empty cells. */
	PostGrid.prototype.placeCta = function (cards, hasMore) {
		var cta = this.cta;
		if (!cta) {
			return;
		}
		var slot = (parseInt(this.cfg.ctaPos, 10) || 4) - 1;
		cta.hidden = cards.length === 0;
		if (hasMore && cards.length > slot) {
			this.grid.insertBefore(cta, cards[slot]);
		} else {
			this.grid.appendChild(cta);
		}
	};

	/* ---------- Screen-reader updates ---------- */

	/* One live region outside the foot (which hides when everything fits),
	   so a picked topic is always announced. */
	PostGrid.prototype.announce = function (text) {
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

	PostGrid.prototype.announceTopic = function () {
		var template = this.cfg.announce;
		if (!template) {
			return;
		}
		var chip = null;
		var topic = this.topic;
		Array.prototype.forEach.call(this.root.querySelectorAll('.avix-pg__chip[data-pg-topic]'), function (node) {
			if (!chip && (node.getAttribute('data-pg-topic') || '') === topic) {
				chip = node;
			}
		});
		var name = chip ? chip.getAttribute('data-pg-name') || chip.textContent.trim() : '';
		var total = this.total + (topic ? 0 : this.extra);
		if (total === 1 && this.cfg.announce1) {
			template = this.cfg.announce1;
		}
		this.announce(String(template).split('{topic}').join(name).split('{total}').join(total.toLocaleString()).replace(/^[\s:]+/, ''));
	};

	PostGrid.prototype.append = function (data) {
		var grid = this.grid;
		var cards = this.parse(data.html);
		cards.forEach(function (card) {
			grid.appendChild(card);
		});
		this.settle('more');
		this.page = data.page || this.page + 1;
		this.total = data.total || this.total;
		this.shown = data.shown || this.shown;
		this.update(data.hasMore);
		this.reveal(cards, true);
		if (this.status && this.cfg.countText) {
			this.announce(this.status.textContent);
		}

		// Keyboard and screen-reader users continue at the first new article.
		var link = cards.length ? cards[0].querySelector('.avix-pg__link') : null;
		if (link) {
			try {
				link.focus({ preventScroll: true });
			} catch (error) {
				link.focus();
			}
		}
		if (this.pal && pal() && this.palSeen) {
			pal().play(this.pal, 'is-wave', 1700);
		}
	};

	/* Status line, progress bar and the next-page link. */
	PostGrid.prototype.update = function (hasMore) {
		// On "All" the featured card is one of the articles on show.
		var extra = this.topic ? 0 : this.extra;
		var total = this.total + extra;
		var shown = Math.min(this.shown + extra, total);
		if (this.foot) {
			// Nothing to load and nothing loaded yet: "Showing 2 of 2" adds nothing.
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
			this.bar.style.setProperty('--pg-progress', total ? Math.min(1, shown / total).toFixed(4) : '0');
		}
		if (this.more) {
			this.more.hidden = !hasMore;
			this.more.href = this.url({ page: this.page + 1 });
		}
	};

	PostGrid.prototype.setBusy = function (busy) {
		this.grid.classList.toggle('is-busy', busy);
		this.grid.setAttribute('aria-busy', busy ? 'true' : 'false');
	};

	/* The page's address with this view's topic (and an optional page). */
	PostGrid.prototype.url = function (opts) {
		var cfg = this.cfg;
		if (!window.URL) {
			return this.more ? this.more.href : window.location.href;
		}
		var url = new window.URL(window.location.href);
		url.searchParams.delete(cfg.pageVar || 'pg');
		url.searchParams.delete(cfg.topicVar || 'topic');
		if (this.topic) {
			url.searchParams.set(cfg.topicVar || 'topic', this.topic);
		}
		if (opts && opts.page > 1) {
			url.searchParams.set(cfg.pageVar || 'pg', opts.page);
		}
		url.hash = '';
		return url.toString();
	};

	PostGrid.prototype.updateUrl = function () {
		if (!this.cfg.urlState || isEditMode() || !window.history || !window.history.replaceState || !window.URL) {
			return;
		}
		try {
			window.history.replaceState(window.history.state, '', this.url());
		} catch (error) {
			// Some sandboxed frames refuse; the view still works.
		}
	};

	/* ---------- The reading character ---------- */

	/* Book down, head up for a moment (or until rest()). */
	PostGrid.prototype.lookUp = function (ms) {
		var self = this;
		if (!this.pal) {
			return;
		}
		this.pal.classList.remove('is-read');
		this.pal.classList.add('is-up');
		if (ms) {
			this.later('rest', function () {
				if (!self.awake) {
					self.rest();
				}
			}, ms);
		}
	};

	PostGrid.prototype.rest = function () {
		if (!this.pal) {
			return;
		}
		this.pal.classList.remove('is-up', 'is-look-l', 'is-look-r');
		this.pal.classList.add('is-read');
	};

	/* Browsing the topics or cards: it looks up, waves now and then, and
	   watches the card under the pointer. */
	PostGrid.prototype.over = function (event) {
		var zone = closest(event.target, '[data-pg-zone]', this.root);
		var self = this;
		if (!zone || !this.palSeen) {
			if (this.awake) {
				this.leave();
			}
			return;
		}
		this.cancel('rest');
		if (!this.awake) {
			this.awake = true;
			this.lookUp(0);
			var now = Date.now();
			if (now - this.lastWave > 6000 && pal()) {
				this.lastWave = now;
				pal().play(this.pal, 'is-wave', 1700);
			}
		}
		var target = closest(event.target, '[data-pg-card], [data-pg-cta], .avix-pg__chip', this.root) || zone;
		if (target !== this.watching) {
			this.watching = target;
			if (pal()) {
				window.requestAnimationFrame(function () {
					if (self.alive && self.watching === target) {
						pal().lookAt(self.pal, target);
					}
				});
			}
		}
	};

	PostGrid.prototype.leave = function () {
		var self = this;
		if (!this.awake) {
			return;
		}
		this.awake = false;
		this.watching = null;
		this.later('rest', function () {
			self.rest();
		}, 1200);
	};

	PostGrid.prototype.ctaEnter = function () {
		if (pal() && finePointer.matches) {
			pal().play(this.ctaPal, 'is-jump', 650);
		}
	};

	PostGrid.prototype.visibility = function () {
		this.root.classList.toggle('is-off', this.visible === false || document.hidden);
	};

	/* ---------- Timers & teardown ---------- */

	PostGrid.prototype.later = function (name, fn, ms) {
		this.cancel(name);
		this.timers[name] = window.setTimeout(fn, ms);
	};

	PostGrid.prototype.cancel = function (name) {
		window.clearTimeout(this.timers[name]);
		delete this.timers[name];
	};

	PostGrid.prototype.destroy = function () {
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
		if (this.palWatcher) {
			this.palWatcher.disconnect();
		}
		if (this.ctaWatcher) {
			this.ctaWatcher.disconnect();
		}
		if (this.revealer) {
			this.revealer.disconnect();
		}
		if (this.pauser) {
			this.pauser.disconnect();
		}
		if (this.stopIdle) {
			this.stopIdle();
		}
		document.removeEventListener('visibilitychange', this.onVisibility);
		this.root.removeEventListener('click', this.onClick);
		this.root.removeEventListener('keydown', this.onKey);
		this.root.removeEventListener('pointerover', this.onOver);
		this.root.removeEventListener('pointerleave', this.onLeave);
		if (this.cta) {
			this.cta.removeEventListener('pointerenter', this.onCtaEnter);
		}
	};

	/* ---------- Mounting ---------- */

	function mount(root) {
		if (!root || (root.__avixPg && root.__avixPg.alive)) {
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
		root.__avixPg = new PostGrid(root);
		instances.push(root.__avixPg);
	}

	function mountAll(scope) {
		var base = scope && scope.querySelectorAll ? scope : document;
		if (base.matches && base.matches(ROOT_SELECTOR)) {
			mount(base);
		}
		Array.prototype.forEach.call(base.querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixPostGrid = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-post-grid.default', function ($scope) {
			mountAll($scope && $scope[0] ? $scope[0] : $scope);
		});
	}

	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		hookElementor();
	} else {
		window.addEventListener('elementor/frontend/init', hookElementor);
	}
})(window, document);
