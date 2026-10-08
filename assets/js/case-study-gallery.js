/*!
 * Avix Digital · Case Study Gallery
 * Rows rise in as they scroll into view, phones get a few pixels of depth on
 * desktop, and in narrow spaces (or with the Carousel layout) the strip
 * becomes an accessible carousel: previous / next buttons, a "02 — 07"
 * counter and a polite live region. Swiping is native scroll-snap; there is
 * no autoplay. Nothing runs off screen; reduced motion skips every animation.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-csg]';
	var DEPTH = 16; // px: how far a phone drifts over the section's scroll range
	var reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };
	var finePointer = window.matchMedia ? window.matchMedia('(hover: hover) and (pointer: fine)') : { matches: false };
	var instances = [];

	function isEditMode() {
		return !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
	}

	function toArray(list) {
		return Array.prototype.slice.call(list || []);
	}

	function pad(n) {
		return (n < 10 ? '0' : '') + n;
	}

	function format(template, values) {
		return String(template || '').replace(/%(\d)\$s/g, function (match, n) {
			var value = values[parseInt(n, 10) - 1];
			return value === undefined ? '' : String(value);
		});
	}

	function Gallery(root) {
		this.root = root;
		this.alive = true;
		this.timers = {};
		this.cfg = {};
		try {
			this.cfg = JSON.parse(root.getAttribute('data-avix-csg') || '{}') || {};
		} catch (error) {
			this.cfg = {};
		}

		this.stage = root.querySelector('[data-csg-stage]');
		this.track = root.querySelector('[data-csg-track]');
		this.items = toArray(root.querySelectorAll('[data-csg-item]'));
		this.rows = toArray(root.querySelectorAll('[data-csg-row]'));
		this.head = root.querySelector('[data-csg-rv]');
		this.nav = root.querySelector('[data-csg-nav]');
		this.prev = root.querySelector('[data-csg-prev]');
		this.next = root.querySelector('[data-csg-next]');
		this.now = root.querySelector('[data-csg-now]');
		this.live = root.querySelector('[data-csg-live]');
		this.depth = toArray(root.querySelectorAll('[data-csg-depth]'));

		this.mode = '';
		this.current = -1;
		this.spoken = -1;
		this.visible = false;

		this.onScroll = this.scrolled.bind(this);
		this.onTrackScroll = this.trackScrolled.bind(this);
		this.onPrev = this.step.bind(this, -1);
		this.onNext = this.step.bind(this, 1);
		this.onKey = this.key.bind(this);
		this.onResize = this.resized.bind(this);

		this.init();
	}

	Gallery.prototype.init = function () {
		var self = this;
		var root = this.root;
		var editor = isEditMode();

		if (!this.track || !this.items.length) {
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
			}, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
			if (this.head) {
				this.revealer.observe(this.head);
			}
		}

		// Depth only runs while the section is on screen.
		this.depthOn = !!this.cfg.parallax && this.depth.length > 0 && finePointer.matches && !reduceMotion.matches && !editor && 'IntersectionObserver' in window;
		if ('IntersectionObserver' in window) {
			this.watcher = new window.IntersectionObserver(function (entries) {
				if (!root.isConnected) {
					self.destroy();
					return;
				}
				self.visible = entries[entries.length - 1].isIntersecting;
				if (self.visible) {
					self.scrolled();
					self.preload();
				}
			}, { rootMargin: '120px 0px' });
			this.watcher.observe(root);
		}
		if (this.depthOn) {
			window.addEventListener('scroll', this.onScroll, { passive: true });
		}

		this.track.addEventListener('scroll', this.onTrackScroll, { passive: true });
		this.track.addEventListener('keydown', this.onKey);
		if (this.prev) {
			this.prev.addEventListener('click', this.onPrev);
		}
		if (this.next) {
			this.next.addEventListener('click', this.onNext);
		}

		if ('ResizeObserver' in window) {
			this.sizer = new window.ResizeObserver(this.onResize);
			this.sizer.observe(root);
		} else {
			window.addEventListener('resize', this.onResize);
		}

		this.layout();

		// Editor iframes can miss the first intersection: show everything.
		if (editor) {
			this.revealAll();
		}
	};

	/* ---------- Mode ---------- */

	/* The CSS decides (container query or the Carousel layout): a strip that
	   runs in a row is a carousel. */
	Gallery.prototype.isCarousel = function () {
		var style = window.getComputedStyle(this.track);
		return style.flexDirection === 'row' && style.display !== 'none';
	};

	Gallery.prototype.resized = function () {
		var self = this;
		if (this.resizeFrame) {
			return;
		}
		this.resizeFrame = window.requestAnimationFrame(function () {
			self.resizeFrame = 0;
			if (self.alive) {
				self.layout();
			}
		});
	};

	Gallery.prototype.layout = function () {
		if (!this.root.isConnected) {
			this.destroy();
			return;
		}
		var mode = this.isCarousel() ? 'carousel' : 'mosaic';
		if (mode === this.mode) {
			if (mode === 'carousel') {
				this.sync(true);
			} else {
				this.scrolled();
			}
			return;
		}
		var first = !this.mode;
		this.mode = mode;

		if (this.armed) {
			if (first) {
				this.watchReveal();
			} else {
				// Switching layouts mid-way: never leave items hidden.
				this.revealAll();
			}
		}

		if (mode === 'carousel') {
			this.setCarousel(true);
			this.resetDepth();
			this.sync(true);
		} else {
			this.setCarousel(false);
			this.scrolled();
		}
	};

	/* ---------- Reveal ---------- */

	/* Mosaic rows rise as a group; a carousel strip rises at once, so the
	   peeking tile is never an empty gap. */
	Gallery.prototype.watchReveal = function () {
		var revealer = this.revealer;
		if (!revealer) {
			return;
		}
		if (this.mode === 'carousel') {
			revealer.observe(this.track);
		} else {
			this.rows.forEach(function (row) {
				revealer.observe(row);
			});
		}
	};

	Gallery.prototype.revealed = function (entries) {
		var self = this;
		entries.forEach(function (entry) {
			if (!entry.isIntersecting) {
				return;
			}
			var target = entry.target;
			self.revealer.unobserve(target);
			if (target.hasAttribute('data-csg-rv')) {
				target.classList.add('is-in');
				return;
			}
			var step = 0;
			toArray(target.querySelectorAll('[data-csg-item]')).forEach(function (item) {
				if (item.classList.contains('is-in')) {
					return;
				}
				item.style.setProperty('--csg-d', Math.min(step, 6) * 90 + 'ms');
				item.classList.add('is-in');
				step++;
			});
		});
	};

	Gallery.prototype.revealAll = function () {
		if (this.head) {
			this.head.classList.add('is-in');
		}
		this.items.forEach(function (item) {
			item.classList.add('is-in');
		});
		if (this.revealer) {
			this.revealer.disconnect();
		}
	};

	/* ---------- Carousel ---------- */

	Gallery.prototype.setCarousel = function (on) {
		var cfg = this.cfg;
		var total = this.items.length;
		var stage = this.stage;
		var track = this.track;
		if (on) {
			if (stage) {
				stage.setAttribute('role', 'region');
				stage.setAttribute('aria-roledescription', 'carousel');
				stage.setAttribute('aria-label', cfg.label || 'Screenshots');
			}
			// The strip scrolls with the arrow keys once focused.
			track.setAttribute('tabindex', '0');
			this.items.forEach(function (item, i) {
				item.setAttribute('role', 'group');
				item.setAttribute('aria-roledescription', 'slide');
				item.setAttribute('aria-label', format(cfg.slide || '%1$s of %2$s', [i + 1, total]));
			});
			if (this.nav) {
				this.nav.hidden = total < 2;
			}
		} else {
			if (stage) {
				stage.removeAttribute('role');
				stage.removeAttribute('aria-roledescription');
				stage.removeAttribute('aria-label');
			}
			track.removeAttribute('tabindex');
			this.items.forEach(function (item) {
				item.removeAttribute('role');
				item.removeAttribute('aria-roledescription');
				item.removeAttribute('aria-label');
			});
			if (this.nav) {
				this.nav.hidden = true;
			}
			this.current = -1;
		}
	};

	Gallery.prototype.trackScrolled = function () {
		var self = this;
		if (this.mode !== 'carousel' || this.trackFrame) {
			return;
		}
		this.trackFrame = window.requestAnimationFrame(function () {
			self.trackFrame = 0;
			if (self.alive) {
				self.sync(false);
			}
		});
	};

	/* Left edge of an item inside the strip's scrolled content. */
	Gallery.prototype.offset = function (item) {
		return item.getBoundingClientRect().left - this.track.getBoundingClientRect().left + this.track.scrollLeft;
	};

	/* The counter, the buttons and (after a pause) the live region follow the
	   tile nearest the strip's centre. */
	Gallery.prototype.sync = function (quiet) {
		var track = this.track;
		var centre = track.scrollLeft + track.clientWidth / 2;
		var best = 0;
		var bestGap = Infinity;
		var self = this;
		this.items.forEach(function (item, i) {
			var gap = Math.abs(self.offset(item) + item.offsetWidth / 2 - centre);
			if (gap < bestGap) {
				bestGap = gap;
				best = i;
			}
		});
		var max = track.scrollWidth - track.clientWidth;
		this.setButton(this.prev, track.scrollLeft > 2 && best > 0);
		this.setButton(this.next, track.scrollLeft < max - 2 && best < this.items.length - 1);

		if (best !== this.current) {
			this.current = best;
			if (this.now) {
				this.now.textContent = pad(best + 1);
			}
		}
		this.preload();
		if (quiet) {
			this.spoken = best;
			return;
		}
		this.later('announce', function () {
			self.announce();
		}, 450);
	};

	/* Lazy images load as they near the viewport, but a slide off to the side
	   of the strip can be swiped in faster than that: once the strip is on
	   screen, the slides around the current one load ahead (one back, two on),
	   so a swipe never lands on an empty card. */
	Gallery.prototype.preload = function () {
		if (this.mode !== 'carousel' || (this.watcher && !this.visible)) {
			return;
		}
		var from = Math.max(0, this.current - 1);
		var to = Math.min(this.items.length - 1, Math.max(this.current, 0) + 2);
		for (var i = from; i <= to; i++) {
			toArray(this.items[i].querySelectorAll('img[loading="lazy"]')).forEach(function (img) {
				if (img.complete) {
					return;
				}
				// "sizes=auto" only applies to lazy images: keep the list.
				var sizes = img.getAttribute('sizes') || '';
				if (/^\s*auto\s*,/i.test(sizes)) {
					img.setAttribute('sizes', sizes.replace(/^\s*auto\s*,\s*/i, ''));
				}
				img.setAttribute('loading', 'eager');
			});
		}
	};

	Gallery.prototype.setButton = function (button, on) {
		if (!button) {
			return;
		}
		var wasOn = !button.classList.contains('is-off');
		button.classList.toggle('is-off', !on);
		button.setAttribute('aria-disabled', on ? 'false' : 'true');
		// A hidden button cannot keep focus: hand it to the other one.
		if (wasOn && !on && document.activeElement === button) {
			var other = button === this.prev ? this.next : this.prev;
			if (other && !other.classList.contains('is-off')) {
				other.focus();
			} else {
				this.track.focus();
			}
		}
	};

	Gallery.prototype.announce = function () {
		if (!this.live || this.current === this.spoken || this.current < 0) {
			return;
		}
		this.spoken = this.current;
		var item = this.items[this.current];
		var caption = item ? item.querySelector('.avix-csg__cap') : null;
		var text = format(this.cfg.announce || 'Screenshot %1$s of %2$s', [this.current + 1, this.items.length]);
		var words = caption ? caption.textContent.replace(/\s+/g, ' ').trim() : '';
		this.live.textContent = words ? text + ': ' + words : text;
	};

	Gallery.prototype.go = function (index) {
		var track = this.track;
		var item = this.items[Math.max(0, Math.min(this.items.length - 1, index))];
		if (!item) {
			return;
		}
		var left = this.offset(item) - (track.clientWidth - item.offsetWidth) / 2;
		var behavior = reduceMotion.matches ? 'auto' : 'smooth';
		if (track.scrollTo) {
			try {
				track.scrollTo({ left: left, behavior: behavior });
				return;
			} catch (error) {
				// Old engines: plain assignment below.
			}
		}
		track.scrollLeft = left;
	};

	Gallery.prototype.step = function (dir) {
		if (this.mode !== 'carousel') {
			return;
		}
		this.go((this.current < 0 ? 0 : this.current) + dir);
	};

	Gallery.prototype.key = function (event) {
		if (this.mode !== 'carousel' || event.target !== this.track) {
			return;
		}
		var key = event.key;
		if (key === 'ArrowRight' || key === 'Right') {
			event.preventDefault();
			this.step(1);
		} else if (key === 'ArrowLeft' || key === 'Left') {
			event.preventDefault();
			this.step(-1);
		} else if (key === 'Home') {
			event.preventDefault();
			this.go(0);
		} else if (key === 'End') {
			event.preventDefault();
			this.go(this.items.length - 1);
		}
	};

	/* ---------- Depth ---------- */

	Gallery.prototype.scrolled = function () {
		var self = this;
		if (!this.depthOn || this.mode !== 'mosaic' || !this.visible || this.depthFrame) {
			return;
		}
		this.depthFrame = window.requestAnimationFrame(function () {
			self.depthFrame = 0;
			if (self.alive && self.mode === 'mosaic') {
				self.drift();
			}
		});
	};

	/* Phones drift from +16px to -16px (every second one the other way) as
	   the section crosses the viewport: transform only. */
	Gallery.prototype.drift = function () {
		var rect = this.root.getBoundingClientRect();
		var vh = window.innerHeight || document.documentElement.clientHeight;
		var progress = (vh - rect.top) / (vh + rect.height);
		progress = Math.max(0, Math.min(1, progress));
		var base = (0.5 - progress) * 2;
		this.depth.forEach(function (node, i) {
			var y = base * DEPTH * (i % 2 ? -1 : 1);
			node.style.setProperty('--csg-depth', y.toFixed(2) + 'px');
		});
	};

	Gallery.prototype.resetDepth = function () {
		this.depth.forEach(function (node) {
			node.style.removeProperty('--csg-depth');
		});
	};

	/* ---------- Timers & teardown ---------- */

	Gallery.prototype.later = function (name, fn, ms) {
		this.cancel(name);
		this.timers[name] = window.setTimeout(fn, ms);
	};

	Gallery.prototype.cancel = function (name) {
		window.clearTimeout(this.timers[name]);
		delete this.timers[name];
	};

	Gallery.prototype.destroy = function () {
		if (!this.alive) {
			return;
		}
		this.alive = false;
		Object.keys(this.timers).forEach(this.cancel, this);
		[this.trackFrame, this.depthFrame, this.resizeFrame].forEach(function (frame) {
			if (frame) {
				window.cancelAnimationFrame(frame);
			}
		});
		if (this.revealer) {
			this.revealer.disconnect();
		}
		if (this.watcher) {
			this.watcher.disconnect();
		}
		if (this.sizer) {
			this.sizer.disconnect();
		} else {
			window.removeEventListener('resize', this.onResize);
		}
		window.removeEventListener('scroll', this.onScroll);
		if (this.track) {
			this.track.removeEventListener('scroll', this.onTrackScroll);
			this.track.removeEventListener('keydown', this.onKey);
		}
		if (this.prev) {
			this.prev.removeEventListener('click', this.onPrev);
		}
		if (this.next) {
			this.next.removeEventListener('click', this.onNext);
		}
	};

	/* ---------- Mounting ---------- */

	function mount(root) {
		if (!root || (root.__avixCsg && root.__avixCsg.alive)) {
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
		root.__avixCsg = new Gallery(root);
		instances.push(root.__avixCsg);
	}

	function mountAll(scope) {
		var base = scope && scope.querySelectorAll ? scope : document;
		if (base.matches && base.matches(ROOT_SELECTOR)) {
			mount(base);
		}
		Array.prototype.forEach.call(base.querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixCsGallery = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-case-study-gallery.default', function ($scope) {
			mountAll($scope && $scope[0] ? $scope[0] : $scope);
		});
	}

	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		hookElementor();
	} else {
		window.addEventListener('elementor/frontend/init', hookElementor);
	}
})(window, document);
