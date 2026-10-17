/*!
 * Avix Digital · Case Study Next
 * Reveals the call-to-action card and the next case study as they come
 * into view. The page's one pixel character says hi once, the first time
 * the card is 60% visible, and waves when the main button is hovered or
 * focused. Nothing repeats on its own: no intervals, no loops, and the
 * character's moves pause while the section is off screen.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-csn]';
	var HI_MS = 2100;
	var WAVE_MS = 1700;
	var reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };
	var instances = [];

	function isEditMode() {
		return !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
	}

	function Next(root) {
		var config = {};
		try {
			config = JSON.parse(root.getAttribute('data-avix-csn') || '{}') || {};
		} catch (error) {
			config = {};
		}

		this.root = root;
		this.config = config;
		this.alive = true;
		this.editor = isEditMode();
		this.cta = root.querySelector('[data-csn-cta]');
		this.pal = root.querySelector('.avix-csn__pal');
		this.button = root.querySelector('[data-csn-button]');
		this.rises = Array.prototype.slice.call(root.querySelectorAll('.avix-csn__rise'));
		this.greeted = false;
		this.greetTimer = 0;
		this.handlers = [];
		this.observers = [];

		this.init();
	}

	Next.prototype.on = function (target, type, fn) {
		target.addEventListener(type, fn);
		this.handlers.push([target, type, fn]);
	};

	Next.prototype.init = function () {
		var self = this;
		var hasIO = 'IntersectionObserver' in window;
		var armed = hasIO && !reduceMotion.matches && !this.editor;

		if (armed) {
			this.root.classList.add('is-armed');
			var io = new window.IntersectionObserver(function (entries) {
				if (!self.check()) {
					return;
				}
				entries.forEach(function (entry) {
					var near = entry.boundingClientRect.top < (window.innerHeight || 800) * 0.88;
					if (entry.isIntersecting && (entry.intersectionRatio >= 0.15 || near)) {
						entry.target.classList.add('is-in');
						io.unobserve(entry.target);
					}
				});
			}, { threshold: [0, 0.15] });
			this.rises.forEach(function (el) {
				io.observe(el);
			});
			this.observers.push(io);
		} else {
			this.rises.forEach(function (el) {
				el.classList.add('is-in');
			});
		}

		if (!hasIO) {
			return;
		}

		// Off screen: the character's moves pause.
		var seen = new window.IntersectionObserver(function (entries) {
			if (self.check()) {
				self.root.classList.toggle('is-off', !entries[entries.length - 1].isIntersecting);
			}
		});
		seen.observe(this.root);
		this.observers.push(seen);

		if (!this.pal || !this.cta || this.editor || !window.AvixPal) {
			return;
		}

		// Hi, once: the first time the card is 60% in view (after it has risen).
		var greet = new window.IntersectionObserver(function (entries) {
			var entry = entries[entries.length - 1];
			if (!self.check() || self.greeted || !entry.isIntersecting || entry.intersectionRatio < 0.6) {
				return;
			}
			self.greeted = true;
			greet.disconnect();
			self.greetTimer = window.setTimeout(function () {
				self.greetTimer = 0;
				if (self.check()) {
					window.AvixPal.play(self.pal, 'is-hi', HI_MS);
				}
			}, armed ? 450 : 0);
		}, { threshold: [0, 0.6] });
		greet.observe(this.cta);
		this.observers.push(greet);

		if (this.button) {
			var wave = function () {
				if (self.check() && !self.pal.classList.contains('is-hi')) {
					window.AvixPal.play(self.pal, 'is-wave', WAVE_MS);
				}
			};
			this.on(this.button, 'pointerenter', wave);
			this.on(this.button, 'focus', wave);
		}
	};

	Next.prototype.check = function () {
		if (this.alive && !this.root.isConnected) {
			this.destroy();
		}
		return this.alive;
	};

	Next.prototype.destroy = function () {
		this.alive = false;
		window.clearTimeout(this.greetTimer);
		this.greetTimer = 0;
		this.observers.forEach(function (observer) {
			observer.disconnect();
		});
		this.observers = [];
		this.handlers.forEach(function (h) {
			h[0].removeEventListener(h[1], h[2]);
		});
		this.handlers = [];
	};

	/* ---------- Mounting ---------- */

	function mount(root) {
		if (!root || (root.__avixCsn && root.__avixCsn.alive)) {
			return;
		}
		instances = instances.filter(function (instance) {
			return instance.check();
		});
		root.__avixCsn = new Next(root);
		instances.push(root.__avixCsn);
	}

	function mountAll(scope) {
		var base = scope && scope.querySelectorAll ? scope : document;
		if (base.matches && base.matches(ROOT_SELECTOR)) {
			mount(base);
		}
		Array.prototype.forEach.call(base.querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixCaseStudyNext = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-case-study-next.default', function ($scope) {
			mountAll($scope && $scope[0] ? $scope[0] : $scope);
		});
	}

	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		hookElementor();
	} else {
		window.addEventListener('elementor/frontend/init', hookElementor);
	}
})(window, document);
