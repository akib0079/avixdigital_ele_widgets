/*!
 * Avix Digital · Case Study Hero
 * Reveals the hero once (copy, then the facts cell by cell, then the
 * devices as they come into view), drifts the phone or render a little on
 * scroll with a fine pointer, and fills the reading-progress line. One
 * passive scroll listener, throttled to one rAF; the glow pauses off screen
 * and in hidden tabs. Nothing moves with reduced motion or in the editor.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-csh]';
	var DRIFT = 24; // px the phone / render rises over the hero's scroll range
	var instances = [];
	var mq = function (query) {
		return window.matchMedia ? window.matchMedia(query) : { matches: false };
	};
	var reduceMotion = mq('(prefers-reduced-motion: reduce)');
	var finePointer = mq('(hover: hover) and (pointer: fine)');
	var raf = window.requestAnimationFrame || function (fn) {
		return window.setTimeout(fn, 16);
	};

	function isEditMode() {
		return !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
	}

	function clamp(value, min, max) {
		return Math.max(min, Math.min(max, value));
	}

	function Hero(root) {
		var config = {};
		try {
			config = JSON.parse(root.getAttribute('data-avix-csh') || '{}') || {};
		} catch (error) {
			config = {};
		}

		this.root = root;
		this.alive = true;
		this.editor = isEditMode();
		this.visible = true;
		this.ticking = false;
		this.row = root.querySelector('.avix-csh__devices-row');
		this.drift = config.parallax ? (root.querySelector('[data-csh-phone]') || root.querySelector('[data-csh-drift]')) : null;
		this.progress = null;
		if (config.progress) {
			// The bar is printed right after the section (a fixed element
			// cannot live inside the size-contained section).
			var next = root.nextElementSibling;
			if (next && next.hasAttribute('data-csh-progress')) {
				this.progress = next.querySelector('.avix-csh__progress-bar');
			}
		}
		this.content = root.closest ? root.closest('.elementor') : null;

		this.onScroll = this.onScroll.bind(this);
		this.onVisibility = this.onVisibility.bind(this);
		this.tick = this.tick.bind(this);

		this.init();
	}

	Hero.prototype.init = function () {
		var self = this;
		var root = this.root;
		var motion = !reduceMotion.matches && !this.editor;

		if (this.editor) {
			root.classList.add('is-editor');
		}

		// Reveal: content stays visible without IntersectionObserver, with
		// reduced motion and in the editor.
		if ('IntersectionObserver' in window && motion) {
			root.classList.add('is-armed');
		}

		if ('IntersectionObserver' in window) {
			this.observer = new window.IntersectionObserver(function (entries) {
				if (!self.alive) {
					return;
				}
				if (!root.isConnected) {
					self.destroy();
					return;
				}
				var entry = entries[0];
				self.visible = entry.isIntersecting;
				if (entry.isIntersecting && !root.classList.contains('is-in')) {
					root.classList.add('is-in');
				}
				self.pauseCheck();
			}, { threshold: [0, 0.15] });
			this.observer.observe(root);

			// The devices have their own entrance: on phones they sit below
			// the fold while the copy is already in.
			if (this.row) {
				this.rowObserver = new window.IntersectionObserver(function (entries) {
					if (self.alive && entries[0].isIntersecting) {
						self.row.classList.add('is-in');
						self.rowObserver.disconnect();
					}
				}, { threshold: 0, rootMargin: '0px 0px -8% 0px' });
				this.rowObserver.observe(this.row);
			}
		} else {
			root.classList.add('is-in');
			if (this.row) {
				this.row.classList.add('is-in');
			}
		}

		document.addEventListener('visibilitychange', this.onVisibility);

		// Scroll work: the phone / render drift (fine pointer only) and the
		// reading progress. Neither runs in the editor or with reduced motion.
		this.useDrift = !!(this.drift && motion && finePointer.matches);
		this.useProgress = !!(this.progress && motion);
		if (this.useDrift || this.useProgress) {
			window.addEventListener('scroll', this.onScroll, { passive: true });
			window.addEventListener('resize', this.onScroll, { passive: true });
			this.onScroll();
		}
	};

	Hero.prototype.pauseCheck = function () {
		this.root.classList.toggle('is-paused', !this.visible || document.hidden);
	};

	Hero.prototype.onVisibility = function () {
		if (!this.alive) {
			return;
		}
		this.pauseCheck();
	};

	Hero.prototype.onScroll = function () {
		if (!this.alive || this.ticking) {
			return;
		}
		this.ticking = true;
		raf(this.tick);
	};

	Hero.prototype.tick = function () {
		this.ticking = false;
		if (!this.alive) {
			return;
		}
		if (!this.root.isConnected) {
			this.destroy();
			return;
		}
		var rect = this.root.getBoundingClientRect();
		var vh = window.innerHeight || document.documentElement.clientHeight;

		if (this.useDrift && rect.bottom > 0 && rect.top < vh) {
			// 0 at the top of the hero, 1 when its bottom leaves the screen.
			var range = Math.max(1, rect.height);
			var p = clamp(-rect.top / range, 0, 1);
			this.drift.style.translate = '0 ' + (-DRIFT * p).toFixed(2) + 'px';
		}

		if (this.useProgress) {
			// From the top of the hero to the end of the page content.
			var start = rect.top + window.pageYOffset;
			var endEl = this.content || document.body;
			var end = endEl.getBoundingClientRect().bottom + window.pageYOffset - vh;
			var progress = end > start ? clamp((window.pageYOffset - start) / (end - start), 0, 1) : 0;
			this.progress.style.transform = 'scaleX(' + progress.toFixed(4) + ')';
		}
	};

	Hero.prototype.destroy = function () {
		if (!this.alive) {
			return;
		}
		this.alive = false;
		if (this.observer) {
			this.observer.disconnect();
		}
		if (this.rowObserver) {
			this.rowObserver.disconnect();
		}
		window.removeEventListener('scroll', this.onScroll, { passive: true });
		window.removeEventListener('resize', this.onScroll, { passive: true });
		document.removeEventListener('visibilitychange', this.onVisibility);
		var index = instances.indexOf(this);
		if (index > -1) {
			instances.splice(index, 1);
		}
	};

	function mount(root) {
		if (!root || (root.__avixCsh && root.__avixCsh.alive)) {
			return;
		}
		// Editor re-renders replace the DOM; drop instances whose root is gone.
		instances.slice().forEach(function (instance) {
			if (!instance.root.isConnected) {
				instance.destroy();
			}
		});
		var hero = new Hero(root);
		if (hero.alive) {
			root.__avixCsh = hero;
			instances.push(hero);
		}
	}

	function mountAll(scope) {
		var root = scope || document;
		if (root.matches && root.matches(ROOT_SELECTOR)) {
			mount(root);
			return;
		}
		Array.prototype.forEach.call(root.querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixCaseStudyHero = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-case-study-hero.default', function ($scope) {
			var element = $scope && $scope[0] ? $scope[0] : $scope;
			if (element && element.querySelectorAll) {
				mountAll(element);
			}
		});
	}

	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		hookElementor();
	} else {
		window.addEventListener('elementor/frontend/init', hookElementor);
	}
})(window, document);
