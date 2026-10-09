/*!
 * Avix Digital · Case Study Chapter
 * Reveals the chapter in groups as they come into view (header, chips,
 * cards and their connecting line), keeps the side label sticky by
 * swapping overflow:hidden for clip on this widget's own Elementor
 * wrappers, fades the edges of the scrolling feature chips and lands
 * anchor jumps below the fixed header. No loops: one observer and a
 * rAF-throttled scroll listener on the chip row only.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-csc]';
	var ELEMENTOR_WRAPPERS = '.elementor-element, .e-con, .e-con-inner, .elementor-container, .elementor-column, .elementor-widget-wrap, .elementor-widget-container, .elementor-section';
	var reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };
	var instances = [];

	function isEditMode() {
		return !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
	}

	function cssPx(name) {
		var value = parseFloat(window.getComputedStyle(document.documentElement).getPropertyValue(name));
		return isNaN(value) ? 0 : value;
	}

	function Chapter(root) {
		var config = {};
		try {
			config = JSON.parse(root.getAttribute('data-avix-csc') || '{}') || {};
		} catch (error) {
			config = {};
		}

		this.root = root;
		this.config = config;
		this.alive = true;
		this.editor = isEditMode();
		this.aside = root.querySelector('[data-csc-aside]');
		this.scroller = root.querySelector('[data-csc-scroller]');
		this.groups = Array.prototype.slice.call(root.querySelectorAll('.avix-csc__cards, .avix-csc__chipbar'));
		this.handlers = [];
		this.observers = [];
		this.clipped = [];
		this.frame = 0;
		this.resizeTimer = 0;

		this.onResize = this.onResize.bind(this);
		this.updateFades = this.updateFades.bind(this);

		this.init();
	}

	Chapter.prototype.on = function (target, type, fn, options) {
		target.addEventListener(type, fn, options || false);
		this.handlers.push([target, type, fn, options || false]);
	};

	Chapter.prototype.init = function () {
		var self = this;
		var armed = 'IntersectionObserver' in window && !reduceMotion.matches && !this.editor;

		if (armed) {
			this.root.classList.add('is-armed');
			// Each group (the header, the chips, the cards) reveals on its own,
			// so a tall chapter never animates out of sight.
			var targets = [this.root].concat(this.groups);
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
			targets.forEach(function (target) {
				io.observe(target);
			});
			this.observers.push(io);
		} else {
			this.root.classList.add('is-in');
			this.groups.forEach(function (group) {
				group.classList.add('is-in');
			});
		}

		if (this.config.sticky && this.aside) {
			this.releaseOverflow();
			this.on(window, 'resize', this.onResize, { passive: true });
		}

		if (this.scroller) {
			this.on(this.scroller, 'scroll', function () {
				if (!self.frame) {
					self.frame = window.requestAnimationFrame(self.updateFades);
				}
			}, { passive: true });
			if (!this.config.sticky) {
				this.on(window, 'resize', this.onResize, { passive: true });
			}
			this.updateFades();
		}

		Array.prototype.forEach.call(this.root.querySelectorAll('[data-csc-jump]'), function (link) {
			self.on(link, 'click', function (event) {
				self.jump(event, link);
			});
		});
	};

	Chapter.prototype.check = function () {
		if (this.alive && !this.root.isConnected) {
			this.destroy();
		}
		return this.alive;
	};

	// Sticky breaks under overflow:hidden ancestors. Elementor wrappers are
	// flex/grid boxes, so swapping hidden for clip keeps the clipping without
	// creating a scroll container. Only this widget's ancestors are touched,
	// and only while the label is actually sticky; theme wrappers are reported.
	Chapter.prototype.releaseOverflow = function () {
		if (this.editor || !this.aside || window.getComputedStyle(this.aside).position !== 'sticky') {
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
				window.console.warn('[Avix Case Study Chapter] An ancestor has overflow "' + overflow + '", which stops the sticky label. Change it to "clip" or "visible".', node);
			}
		}
	};

	Chapter.prototype.onResize = function () {
		var self = this;
		window.clearTimeout(this.resizeTimer);
		this.resizeTimer = window.setTimeout(function () {
			if (!self.check()) {
				return;
			}
			if (self.config.sticky) {
				self.releaseOverflow();
			}
			self.updateFades();
		}, 150);
	};

	// Edge fades only where the chip row can still scroll.
	Chapter.prototype.updateFades = function () {
		this.frame = 0;
		var el = this.scroller;
		if (!el || !this.check()) {
			return;
		}
		var max = el.scrollWidth - el.clientWidth;
		el.classList.toggle('is-fade-l', max > 2 && el.scrollLeft > 2);
		el.classList.toggle('is-fade-r', max > 2 && el.scrollLeft < max - 2);
	};

	// Anchor chips: land the feature below the fixed header and move focus
	// there, so keyboard and screen-reader users continue from the feature.
	Chapter.prototype.jump = function (event, link) {
		var id = (link.getAttribute('href') || '').replace(/^#/, '');
		var target = id ? document.getElementById(id) : null;
		if (!target || this.editor) {
			return;
		}
		event.preventDefault();
		var offset = cssPx('--wp-admin--admin-bar--height') + cssPx('--avix-header-h') + 16;
		var top = target.getBoundingClientRect().top + (window.pageYOffset || document.documentElement.scrollTop || 0) - offset;
		try {
			window.scrollTo({ top: Math.max(0, top), behavior: reduceMotion.matches ? 'auto' : 'smooth' });
		} catch (error) {
			window.scrollTo(0, Math.max(0, top));
		}
		if (window.history && window.history.pushState) {
			window.history.pushState(null, '', '#' + id);
		}
		if (!target.hasAttribute('tabindex')) {
			target.setAttribute('tabindex', '-1');
		}
		try {
			target.focus({ preventScroll: true });
		} catch (error) {
			// Older browsers without focus options: skip moving focus.
		}
	};

	Chapter.prototype.destroy = function () {
		this.alive = false;
		window.cancelAnimationFrame(this.frame);
		window.clearTimeout(this.resizeTimer);
		this.observers.forEach(function (observer) {
			observer.disconnect();
		});
		this.observers = [];
		this.handlers.forEach(function (h) {
			h[0].removeEventListener(h[1], h[2], h[3]);
		});
		this.handlers = [];
	};

	/* ---------- Mounting ---------- */

	function mount(root) {
		if (!root || (root.__avixCsc && root.__avixCsc.alive)) {
			return;
		}
		instances = instances.filter(function (instance) {
			return instance.check();
		});
		root.__avixCsc = new Chapter(root);
		instances.push(root.__avixCsc);
	}

	function mountAll(scope) {
		var base = scope && scope.querySelectorAll ? scope : document;
		if (base.matches && base.matches(ROOT_SELECTOR)) {
			mount(base);
		}
		Array.prototype.forEach.call(base.querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixCaseStudyChapter = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-case-study-chapter.default', function ($scope) {
			mountAll($scope && $scope[0] ? $scope[0] : $scope);
		});
	}

	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		hookElementor();
	} else {
		window.addEventListener('elementor/frontend/init', hookElementor);
	}
})(window, document);
