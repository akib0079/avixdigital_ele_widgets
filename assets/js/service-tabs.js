/*!
 * Avix Digital · Service Tabs
 * WAI-ARIA tabs (roving tabindex, arrow keys, Home and End, automatic
 * activation) with a thumb that slides to the open tab and a cross-fade
 * between panels that share one grid cell. The pixel character walks along
 * the tab bar to the chosen tab and says hi; it looks at hovered tabs. On a
 * scrolling tab bar the chosen tab slides into view and the edges fade.
 * Transform and opacity only; the one rAF loop runs only while it walks, and
 * the character's loops pause off screen or in a hidden tab.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-stb]';
	var reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };
	var WALK_SPEED = 320;
	var WALK_MIN = 320;
	var WALK_MAX = 1100;
	var HOP_MS = 300;
	var instances = [];
	// Editor only: the tab you were looking at, per widget, so a re-render
	// after editing the third tab doesn't jump back to the first.
	var editorTabs = {};

	function isEditMode() {
		return !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
	}

	function easeInOut(t) {
		return -(Math.cos(Math.PI * t) - 1) / 2;
	}

	function ServiceTabs(root) {
		var config = {};
		try {
			config = JSON.parse(root.getAttribute('data-avix-stb') || '{}') || {};
		} catch (error) {
			config = {};
		}

		this.root = root;
		this.config = config;
		this.alive = true;
		this.editor = isEditMode();
		this.motion = !reduceMotion.matches;
		this.scroller = root.querySelector('[data-stb-scroller]');
		this.track = root.querySelector('[data-stb-track]');
		this.thumb = root.querySelector('[data-stb-thumb]');
		this.tabs = Array.prototype.slice.call(root.querySelectorAll('[data-stb-tab]'));
		this.panels = Array.prototype.slice.call(root.querySelectorAll('[data-stb-panel]'));
		this.box = root.querySelector('[data-stb-panels]');
		this.walker = root.querySelector('[data-stb-walker]');
		this.pal = this.walker ? this.walker.querySelector('[data-avix-pal]') : null;
		this.current = 0;
		this.walkX = null;
		this.frame = 0;
		this.layoutFrame = 0;
		this.scrollFrame = 0;
		this.walking = false;
		this.visible = true;
		this.greeted = false;
		this.handlers = [];
		this.observers = [];
		this.timers = {};
		this.stopWave = null;

		this.onLayout = this.onLayout.bind(this);
		this.onScroll = this.onScroll.bind(this);
		this.onVisibility = this.onVisibility.bind(this);
		this.onKey = this.onKey.bind(this);
		this.onHash = this.onHash.bind(this);

		this.init();
	}

	ServiceTabs.prototype.init = function () {
		var self = this;
		var armed = 'IntersectionObserver' in window && this.motion && !this.editor;

		if (armed) {
			this.root.classList.add('is-armed');
		}

		if ('IntersectionObserver' in window) {
			var io = new window.IntersectionObserver(function (entries) {
				if (!self.check()) {
					return;
				}
				var entry = entries[entries.length - 1];
				self.visible = entry.isIntersecting;
				self.onVisibility();
				if (entry.isIntersecting && entry.intersectionRatio >= 0.15 && !self.root.classList.contains('is-in')) {
					self.root.classList.add('is-in');
				}
				if (entry.isIntersecting && entry.intersectionRatio >= 0.35) {
					self.greet();
				}
			}, { threshold: [0, 0.15, 0.35] });
			io.observe(this.root);
			this.observers.push(io);
		} else {
			this.root.classList.add('is-in');
		}
		// Editor iframes can miss the first intersection.
		if (this.editor) {
			this.root.classList.add('is-in');
		}
		document.addEventListener('visibilitychange', this.onVisibility);

		if (!this.tabs.length || !this.track) {
			return;
		}

		this.current = 0;
		this.tabs.forEach(function (tab, i) {
			if (tab.getAttribute('aria-selected') === 'true') {
				self.current = i;
			}
		});

		var host = this.root.closest ? this.root.closest('[data-id]') : null;
		this.editorKey = this.editor && host ? host.getAttribute('data-id') : '';
		if (this.editorKey && editorTabs[this.editorKey] !== undefined && editorTabs[this.editorKey] < this.tabs.length) {
			this.current = editorTabs[this.editorKey];
		}

		// A shared link to a tab opens it. No element carries that id, so the
		// browser can't scroll there itself: bring the section into view.
		var linked = this.config.hash ? this.fromHash() : -1;
		if (linked > -1) {
			this.current = linked;
			if (!this.editor) {
				this.later('hash', 0, function () {
					self.root.scrollIntoView({ block: 'start' });
				});
			}
		}

		this.root.classList.add('is-instant');
		this.apply(this.current);
		this.root.classList.add('is-ready');
		this.layout();
		this.later('instant', 60, function () {
			self.root.classList.remove('is-instant');
		});

		this.tabs.forEach(function (tab, i) {
			self.listen(tab, 'click', function () {
				self.select(i, false);
			});
			self.listen(tab, 'pointerenter', function (event) {
				if (event.pointerType === 'mouse' && self.config.look && !self.walking && self.pal && window.AvixPal && i !== self.current) {
					window.AvixPal.lookAt(self.pal, tab);
				}
			});
		});
		this.listen(this.track, 'keydown', this.onKey);
		this.listen(this.track, 'pointerleave', function () {
			if (self.pal && window.AvixPal && !self.walking) {
				window.AvixPal.lookAt(self.pal, null);
			}
		});
		if (this.scroller) {
			this.listen(this.scroller, 'scroll', this.onScroll, { passive: true });
		}
		if (this.config.hash) {
			this.listen(window, 'hashchange', this.onHash);
		}

		if ('ResizeObserver' in window) {
			var ro = new window.ResizeObserver(this.onLayout);
			ro.observe(this.track);
			if (this.scroller) {
				ro.observe(this.scroller);
			}
			this.observers.push(ro);
		}
		window.addEventListener('resize', this.onLayout, { passive: true });
		window.addEventListener('load', this.onLayout);
		// Web fonts change the tab widths once they arrive.
		if (document.fonts && document.fonts.ready) {
			document.fonts.ready.then(function () {
				self.onLayout();
			});
		}

		if (this.pal && this.config.wave && this.motion && !this.editor && window.AvixPal) {
			this.stopWave = window.AvixPal.every(this.pal, function () {
				if (!self.walking && self.check()) {
					window.AvixPal.play(self.pal, 'is-wave', 1700);
				}
			}, 7000, 12000);
		}
	};

	/* ---------- Helpers ---------- */

	ServiceTabs.prototype.listen = function (el, type, fn, options) {
		el.addEventListener(type, fn, options);
		this.handlers.push([el, type, fn, options]);
	};

	ServiceTabs.prototype.later = function (name, ms, fn) {
		var self = this;
		this.cancel(name);
		this.timers[name] = window.setTimeout(function () {
			delete self.timers[name];
			if (self.check()) {
				fn();
			}
		}, ms);
	};

	ServiceTabs.prototype.cancel = function (name) {
		if (this.timers[name]) {
			window.clearTimeout(this.timers[name]);
			delete this.timers[name];
		}
	};

	// Editor re-renders replace the DOM: an instance whose root is gone cleans up.
	ServiceTabs.prototype.check = function () {
		if (this.alive && !this.root.isConnected) {
			this.destroy();
		}
		return this.alive;
	};

	// Off screen or in a hidden tab: the character's CSS loops pause.
	ServiceTabs.prototype.onVisibility = function () {
		if (this.check()) {
			this.root.classList.toggle('is-off', document.hidden || !this.visible);
		}
	};

	/* ---------- Tabs ---------- */

	// Index of the tab named in the address (#slug), or -1.
	ServiceTabs.prototype.fromHash = function () {
		var wanted = '';
		var found = -1;
		try {
			wanted = decodeURIComponent(window.location.hash.slice(1));
		} catch (error) {
			return -1;
		}
		this.tabs.forEach(function (tab, i) {
			if (wanted && tab.getAttribute('data-stb-slug') === wanted) {
				found = i;
			}
		});
		return found;
	};

	// An in-page link to #slug opens that tab.
	ServiceTabs.prototype.onHash = function () {
		var i = this.check() ? this.fromHash() : -1;
		if (i > -1) {
			this.select(i, false);
		}
	};

	ServiceTabs.prototype.onKey = function (event) {
		var i = this.tabs.indexOf(document.activeElement);
		if (i < 0) {
			return;
		}
		var last = this.tabs.length - 1;
		var next = -1;
		var rtl = window.getComputedStyle(this.track).direction === 'rtl';
		switch (event.key) {
			case 'ArrowRight':
				next = rtl ? (i ? i - 1 : last) : (i < last ? i + 1 : 0);
				break;
			case 'ArrowLeft':
				next = rtl ? (i < last ? i + 1 : 0) : (i ? i - 1 : last);
				break;
			case 'Home':
				next = 0;
				break;
			case 'End':
				next = last;
				break;
			default:
				return;
		}
		event.preventDefault();
		this.select(next, true);
	};

	ServiceTabs.prototype.select = function (i, focus) {
		if (!this.check() || i < 0 || i >= this.tabs.length) {
			return;
		}
		if (focus) {
			try {
				this.tabs[i].focus({ preventScroll: true });
			} catch (error) {
				this.tabs[i].focus();
			}
		}
		if (i === this.current) {
			this.reveal(i);
			return;
		}
		this.current = i;
		if (this.editorKey) {
			editorTabs[this.editorKey] = i;
		}
		this.resize(function () {
			this.apply(i);
		});
		this.moveThumb(i);
		this.reveal(i);
		this.walkTo(i);
		if (this.config.hash && window.history && window.history.replaceState) {
			try {
				window.history.replaceState(window.history.state, '', '#' + this.tabs[i].getAttribute('data-stb-slug'));
			} catch (error) {
				// Sandboxed previews may refuse; the tab still opens.
			}
		}
	};

	// Selected state on tabs and panels (roving tabindex). The outgoing panel
	// stays visible while it fades out, so it is made inert right away: Tab
	// after an arrow key must land in the new panel, not the old one.
	ServiceTabs.prototype.apply = function (i) {
		this.tabs.forEach(function (tab, k) {
			var on = k === i;
			tab.classList.toggle('is-active', on);
			tab.setAttribute('aria-selected', on ? 'true' : 'false');
			tab.setAttribute('tabindex', on ? '0' : '-1');
		});
		if (this.tabs.length < 2) {
			return;
		}
		this.panels.forEach(function (panel, k) {
			var on = k === i;
			panel.classList.toggle('is-active', on);
			panel.setAttribute('tabindex', on ? '0' : '-1');
			if (on) {
				panel.removeAttribute('inert');
			} else {
				panel.setAttribute('inert', '');
			}
		});
	};

	// Swaps panels while the card glides from the old height to the new one.
	// Two layout reads per switch; the glide itself is a CSS transition.
	ServiceTabs.prototype.resize = function (change) {
		var box = this.box;
		if (!box || !this.motion || this.root.classList.contains('is-instant')) {
			change.call(this);
			return;
		}
		var from = box.getBoundingClientRect().height;
		box.classList.remove('is-sizing');
		box.style.height = '';
		change.call(this);
		var to = box.getBoundingClientRect().height;
		if (Math.abs(to - from) < 2) {
			return;
		}
		box.style.height = from + 'px';
		void box.offsetHeight;
		box.classList.add('is-sizing');
		box.style.height = to + 'px';
		this.later('size', 560, function () {
			box.classList.remove('is-sizing');
			box.style.height = '';
		});
	};

	ServiceTabs.prototype.moveThumb = function (i) {
		var tab = this.tabs[i];
		if (!this.thumb || !tab) {
			return;
		}
		this.thumb.style.setProperty('--stb-thumb-x', tab.offsetLeft + 'px');
		this.thumb.style.setProperty('--stb-thumb-w', tab.offsetWidth + 'px');
	};

	// A scrolling tab bar brings the chosen tab to the middle. Only the bar
	// scrolls, never the page.
	ServiceTabs.prototype.reveal = function (i) {
		var scroller = this.scroller;
		var tab = this.tabs[i];
		if (!scroller || !tab || scroller.scrollWidth <= scroller.clientWidth + 2) {
			return;
		}
		var left = this.track.offsetLeft + tab.offsetLeft + tab.offsetWidth / 2 - scroller.clientWidth / 2;
		left = Math.max(0, Math.min(left, scroller.scrollWidth - scroller.clientWidth));
		if (Math.abs(left - scroller.scrollLeft) < 2) {
			return;
		}
		if (scroller.scrollTo) {
			scroller.scrollTo({ left: left, behavior: this.motion && !this.root.classList.contains('is-instant') ? 'smooth' : 'auto' });
		} else {
			scroller.scrollLeft = left;
		}
	};

	ServiceTabs.prototype.onScroll = function () {
		var self = this;
		if (this.scrollFrame) {
			return;
		}
		this.scrollFrame = window.requestAnimationFrame(function () {
			self.scrollFrame = 0;
			if (self.check()) {
				self.fades();
			}
		});
	};

	// Soft edges only where there is more to scroll to.
	ServiceTabs.prototype.fades = function () {
		var scroller = this.scroller;
		if (!scroller) {
			return;
		}
		var max = scroller.scrollWidth - scroller.clientWidth;
		var left = Math.abs(scroller.scrollLeft);
		scroller.classList.toggle('is-fade-l', max > 2 && left > 2);
		scroller.classList.toggle('is-fade-r', max > 2 && left < max - 2);
	};

	ServiceTabs.prototype.onLayout = function () {
		var self = this;
		if (this.layoutFrame || !this.check()) {
			return;
		}
		this.layoutFrame = window.requestAnimationFrame(function () {
			self.layoutFrame = 0;
			if (self.check()) {
				self.layout();
			}
		});
	};

	// Re-measure after a resize or font swap: no animation for this jump.
	ServiceTabs.prototype.layout = function () {
		var thumb = this.thumb;
		if (thumb) {
			thumb.style.transition = 'none';
		}
		this.moveThumb(this.current);
		if (!this.walking) {
			this.placeWalker(this.seat(this.current));
		}
		if (thumb) {
			void thumb.offsetWidth;
			thumb.style.transition = '';
		}
		this.fades();
		if (this.root.classList.contains('is-instant')) {
			this.reveal(this.current);
		}
	};

	/* ---------- The character ---------- */

	// Where it stands over tab i: centred on the tab, in track coordinates.
	ServiceTabs.prototype.seat = function (i) {
		var tab = this.tabs[i];
		if (!tab || !this.walker) {
			return 0;
		}
		return Math.round(tab.offsetLeft + tab.offsetWidth / 2 - this.walker.offsetWidth / 2);
	};

	ServiceTabs.prototype.placeWalker = function (x) {
		if (!this.walker) {
			return;
		}
		this.walkX = x;
		this.walker.style.transform = 'translate3d(' + Math.round(x) + 'px,0,0)';
	};

	ServiceTabs.prototype.walkTo = function (i) {
		var self = this;
		var pal = this.pal;
		if (!this.walker || !pal) {
			return;
		}
		var target = this.seat(i);
		var from = this.walkX === null ? target : this.walkX;
		var dx = target - from;
		window.cancelAnimationFrame(this.frame);
		this.frame = 0;
		this.cancel('arrive');
		pal.classList.remove('is-hi', 'is-wave', 'is-look-l', 'is-look-r');

		if (!this.motion || Math.abs(dx) < 2) {
			this.placeWalker(target);
			this.arrive();
			return;
		}

		// Walking off: a quick hop straight over.
		if (!this.config.walk) {
			this.walking = true;
			pal.classList.toggle('is-face-l', dx < 0);
			pal.classList.add('is-jump');
			this.placeWalker(target);
			this.walker.style.transition = 'transform ' + HOP_MS + 'ms cubic-bezier(0.22, 1, 0.36, 1)';
			this.later('arrive', HOP_MS + 40, function () {
				self.walker.style.transition = '';
				pal.classList.remove('is-jump', 'is-face-l');
				self.walking = false;
				self.land();
				self.arrive();
			});
			return;
		}

		var duration = Math.max(WALK_MIN, Math.min(WALK_MAX, Math.abs(dx) / WALK_SPEED * 1000));
		var start = 0;
		this.walking = true;
		pal.classList.add('is-walk');
		pal.classList.toggle('is-face-l', dx < 0);

		function step(now) {
			if (!self.check()) {
				return;
			}
			if (!start) {
				start = now;
			}
			var t = Math.min(1, (now - start) / duration);
			// The target can move while it walks (a resize): follow it.
			var goal = self.seat(i);
			self.placeWalker(from + (goal - from) * easeInOut(t));
			if (t < 1) {
				self.frame = window.requestAnimationFrame(step);
				return;
			}
			self.frame = 0;
			self.walking = false;
			pal.classList.remove('is-walk', 'is-face-l');
			self.placeWalker(goal);
			self.arrive();
		}
		this.frame = window.requestAnimationFrame(step);
	};

	ServiceTabs.prototype.land = function () {
		var walker = this.walker;
		walker.classList.remove('is-land');
		void walker.offsetWidth;
		walker.classList.add('is-land');
		this.later('land', 320, function () {
			walker.classList.remove('is-land');
		});
	};

	// Arrived on the new tab: say hi (or wave).
	ServiceTabs.prototype.arrive = function () {
		if (!this.pal || !window.AvixPal || this.editor) {
			return;
		}
		window.AvixPal.play(this.pal, this.config.hi ? 'is-hi' : 'is-wave', this.config.hi ? 2100 : 1700);
	};

	// First time the section is well in view: a hello once the bar has risen.
	ServiceTabs.prototype.greet = function () {
		var self = this;
		if (this.greeted || !this.pal || !this.config.hi || this.editor || !window.AvixPal) {
			return;
		}
		this.greeted = true;
		this.later('greet', this.root.classList.contains('is-armed') ? 900 : 300, function () {
			if (!self.walking) {
				window.AvixPal.play(self.pal, 'is-hi', 2100);
			}
		});
	};

	ServiceTabs.prototype.destroy = function () {
		var self = this;
		this.alive = false;
		window.cancelAnimationFrame(this.frame);
		window.cancelAnimationFrame(this.layoutFrame);
		window.cancelAnimationFrame(this.scrollFrame);
		Object.keys(this.timers).forEach(function (name) {
			self.cancel(name);
		});
		this.observers.forEach(function (observer) {
			observer.disconnect();
		});
		this.observers = [];
		if (this.stopWave) {
			this.stopWave();
		}
		window.removeEventListener('resize', this.onLayout);
		window.removeEventListener('load', this.onLayout);
		document.removeEventListener('visibilitychange', this.onVisibility);
		this.handlers.forEach(function (h) {
			h[0].removeEventListener(h[1], h[2], h[3]);
		});
		this.handlers = [];
	};

	/* ---------- Mounting ---------- */

	function mount(root) {
		if (!root || (root.__avixStb && root.__avixStb.alive)) {
			return;
		}
		instances = instances.filter(function (instance) {
			return instance.check();
		});
		root.__avixStb = new ServiceTabs(root);
		instances.push(root.__avixStb);
	}

	function mountAll(scope) {
		var base = scope && scope.querySelectorAll ? scope : document;
		if (base.matches && base.matches(ROOT_SELECTOR)) {
			mount(base);
		}
		Array.prototype.forEach.call(base.querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixServiceTabs = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-service-tabs.default', function ($scope) {
			mountAll($scope && $scope[0] ? $scope[0] : $scope);
		});
	}

	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		hookElementor();
	} else {
		window.addEventListener('elementor/frontend/init', hookElementor);
	}
})(window, document);
