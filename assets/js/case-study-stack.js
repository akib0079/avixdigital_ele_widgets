/*!
 * Avix Digital · Case Study Stack
 * Reveals the header, then the tool list in a short stagger when it comes
 * into view, and keeps the side label sticky by swapping overflow:hidden
 * for clip on this widget's own Elementor wrappers. No loops or timers
 * beyond a debounced resize check.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-cst]';
	var ELEMENTOR_WRAPPERS = '.elementor-element, .e-con, .e-con-inner, .elementor-container, .elementor-column, .elementor-widget-wrap, .elementor-widget-container, .elementor-section';
	var reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };
	var instances = [];

	function isEditMode() {
		return !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
	}

	function Stack(root) {
		var config = {};
		try {
			config = JSON.parse(root.getAttribute('data-avix-cst') || '{}') || {};
		} catch (error) {
			config = {};
		}

		this.root = root;
		this.config = config;
		this.alive = true;
		this.editor = isEditMode();
		this.aside = root.querySelector('[data-cst-aside]');
		this.list = root.querySelector('.avix-cst__list');
		this.observers = [];
		this.clipped = [];
		this.resizeTimer = 0;
		this.onResize = this.onResize.bind(this);

		this.init();
	}

	Stack.prototype.init = function () {
		var self = this;
		var armed = 'IntersectionObserver' in window && !reduceMotion.matches && !this.editor;

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
			io.observe(this.root);
			if (this.list) {
				io.observe(this.list);
			}
			this.observers.push(io);
		} else {
			this.root.classList.add('is-in');
			if (this.list) {
				this.list.classList.add('is-in');
			}
		}

		if (this.config.sticky && this.aside) {
			this.releaseOverflow();
			window.addEventListener('resize', this.onResize, { passive: true });
		}
	};

	Stack.prototype.check = function () {
		if (this.alive && !this.root.isConnected) {
			this.destroy();
		}
		return this.alive;
	};

	// Sticky breaks under overflow:hidden ancestors; clip keeps the clipping
	// without creating a scroll container. Only this widget's ancestors are
	// touched, and only while the label is sticky; theme wrappers are reported.
	Stack.prototype.releaseOverflow = function () {
		if (this.editor || window.getComputedStyle(this.aside).position !== 'sticky') {
			return;
		}
		for (var node = this.root.parentElement; node && node !== document.body && node !== document.documentElement; node = node.parentElement) {
			var style = window.getComputedStyle(node);
			var overflow = style.overflowX + ' ' + style.overflowY;
			if (!/(hidden|auto|scroll)/.test(overflow)) {
				continue;
			}
			if (!/(auto|scroll)/.test(overflow) && node.matches && node.matches(ELEMENTOR_WRAPPERS)) {
				if (this.clipped.indexOf(node) === -1) {
					node.style.overflow = 'clip';
					this.clipped.push(node);
				}
			} else if (window.console && !this.warned) {
				this.warned = true;
				window.console.warn('[Avix Case Study Stack] An ancestor has overflow "' + overflow + '", which stops the sticky label. Change it to "clip" or "visible".', node);
			}
		}
	};

	Stack.prototype.onResize = function () {
		var self = this;
		window.clearTimeout(this.resizeTimer);
		this.resizeTimer = window.setTimeout(function () {
			if (self.check()) {
				self.releaseOverflow();
			}
		}, 150);
	};

	Stack.prototype.destroy = function () {
		this.alive = false;
		window.clearTimeout(this.resizeTimer);
		window.removeEventListener('resize', this.onResize);
		this.observers.forEach(function (observer) {
			observer.disconnect();
		});
		this.observers = [];
	};

	/* ---------- Mounting ---------- */

	function mount(root) {
		if (!root || (root.__avixCst && root.__avixCst.alive)) {
			return;
		}
		instances = instances.filter(function (instance) {
			return instance.check();
		});
		root.__avixCst = new Stack(root);
		instances.push(root.__avixCst);
	}

	function mountAll(scope) {
		var base = scope && scope.querySelectorAll ? scope : document;
		if (base.matches && base.matches(ROOT_SELECTOR)) {
			mount(base);
		}
		Array.prototype.forEach.call(base.querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixCaseStudyStack = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-case-study-stack.default', function ($scope) {
			mountAll($scope && $scope[0] ? $scope[0] : $scope);
		});
	}

	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		hookElementor();
	} else {
		window.addEventListener('elementor/frontend/init', hookElementor);
	}
})(window, document);
