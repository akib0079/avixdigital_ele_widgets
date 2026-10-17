/*!
 * Avix Digital · Case Study Results
 * Reveals each group (header, pillars, numbers, proof links, quote) as it
 * comes into view and counts whole numbers up from zero (easeOutExpo,
 * 1.6s). The final figure is always in the HTML: the count only runs once
 * the section is armed, and a screen-reader copy keeps the real value.
 * The glow's drift pauses off screen and in hidden tabs; the one rAF loop
 * runs only while numbers are counting.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-csr]';
	var COUNT_MS = 1600;
	var reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };
	var instances = [];

	function isEditMode() {
		return !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
	}

	function easeOutExpo(t) {
		return t >= 1 ? 1 : 1 - Math.pow(2, -10 * t);
	}

	function Results(root) {
		var config = {};
		try {
			config = JSON.parse(root.getAttribute('data-avix-csr') || '{}') || {};
		} catch (error) {
			config = {};
		}

		this.root = root;
		this.config = config;
		this.alive = true;
		this.editor = isEditMode();
		this.groups = Array.prototype.slice.call(root.querySelectorAll('.avix-csr__head, .avix-csr__pillars, .avix-csr__numbers, .avix-csr__proof, .avix-csr__quote-block'));
		this.numbers = root.querySelector('.avix-csr__numbers');
		this.counters = [];
		this.observers = [];
		this.frame = 0;
		this.visible = true;

		this.onVisibility = this.onVisibility.bind(this);
		this.tick = this.tick.bind(this);

		this.init();
	}

	Results.prototype.init = function () {
		var self = this;
		var armed = 'IntersectionObserver' in window && !reduceMotion.matches && !this.editor;

		if (armed) {
			this.root.classList.add('is-armed');
			if (this.config.count) {
				Array.prototype.forEach.call(this.root.querySelectorAll('[data-csr-count]'), function (el) {
					var target = parseInt(el.getAttribute('data-csr-count'), 10);
					if (!isNaN(target) && target > 0) {
						self.counters.push({ el: el, target: target, final: el.textContent, start: 0 });
						el.textContent = '0';
					}
				});
			}
			var io = new window.IntersectionObserver(function (entries) {
				if (!self.check()) {
					return;
				}
				entries.forEach(function (entry) {
					var near = entry.boundingClientRect.top < (window.innerHeight || 800) * 0.88;
					if (entry.isIntersecting && (entry.intersectionRatio >= 0.15 || near)) {
						entry.target.classList.add('is-in');
						io.unobserve(entry.target);
						if (entry.target === self.numbers) {
							self.count();
						}
					}
				});
			}, { threshold: [0, 0.15] });
			this.groups.forEach(function (group) {
				io.observe(group);
			});
			this.observers.push(io);
		} else {
			this.groups.forEach(function (group) {
				group.classList.add('is-in');
			});
		}

		// The glow drifts only while the section is on screen.
		if ('IntersectionObserver' in window) {
			var seen = new window.IntersectionObserver(function (entries) {
				if (!self.check()) {
					return;
				}
				self.visible = entries[entries.length - 1].isIntersecting;
				self.onVisibility();
			});
			seen.observe(this.root);
			this.observers.push(seen);
		}
		document.addEventListener('visibilitychange', this.onVisibility);
	};

	Results.prototype.check = function () {
		if (this.alive && !this.root.isConnected) {
			this.destroy();
		}
		return this.alive;
	};

	Results.prototype.onVisibility = function () {
		this.root.classList.toggle('is-paused', !this.visible || document.hidden);
	};

	Results.prototype.count = function () {
		if (!this.counters.length) {
			return;
		}
		var now = window.performance && window.performance.now ? window.performance.now() : Date.now();
		this.counters.forEach(function (counter, i) {
			// Follows the cards' 110ms stagger.
			counter.start = now + 120 + i * 110;
		});
		if (!this.frame) {
			this.frame = window.requestAnimationFrame(this.tick);
		}
	};

	Results.prototype.tick = function (now) {
		this.frame = 0;
		if (!this.check()) {
			return;
		}
		var busy = false;
		this.counters.forEach(function (counter) {
			if (counter.done) {
				return;
			}
			var t = Math.max(0, Math.min(1, (now - counter.start) / COUNT_MS));
			if (t >= 1) {
				counter.el.textContent = counter.final;
				counter.done = true;
				return;
			}
			counter.el.textContent = String(Math.round(counter.target * easeOutExpo(t)));
			busy = true;
		});
		if (busy) {
			this.frame = window.requestAnimationFrame(this.tick);
		}
	};

	Results.prototype.destroy = function () {
		this.alive = false;
		window.cancelAnimationFrame(this.frame);
		this.frame = 0;
		this.observers.forEach(function (observer) {
			observer.disconnect();
		});
		this.observers = [];
		document.removeEventListener('visibilitychange', this.onVisibility);
	};

	/* ---------- Mounting ---------- */

	function mount(root) {
		if (!root || (root.__avixCsr && root.__avixCsr.alive)) {
			return;
		}
		instances = instances.filter(function (instance) {
			return instance.check();
		});
		root.__avixCsr = new Results(root);
		instances.push(root.__avixCsr);
	}

	function mountAll(scope) {
		var base = scope && scope.querySelectorAll ? scope : document;
		if (base.matches && base.matches(ROOT_SELECTOR)) {
			mount(base);
		}
		Array.prototype.forEach.call(base.querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixCaseStudyResults = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-case-study-results.default', function ($scope) {
			mountAll($scope && $scope[0] ? $scope[0] : $scope);
		});
	}

	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		hookElementor();
	} else {
		window.addEventListener('elementor/frontend/init', hookElementor);
	}
})(window, document);
