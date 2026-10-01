/*!
 * Avix Digital · Impact Numbers
 * Reveal on scroll + count-up. The final values are rendered server-side, so
 * without JS (or with reduced motion) the real numbers are always shown.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-impact]';
	var reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };

	function easeOutExpo(t) {
		return t >= 1 ? 1 : 1 - Math.pow(2, -10 * t);
	}

	function format(value, decimals, separator) {
		var fixed = value.toFixed(decimals);
		if (!separator) {
			return fixed;
		}
		var parts = fixed.split('.');
		parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, separator);
		return parts.join('.');
	}

	function isEditMode() {
		return !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
	}

	function countUp(element, duration) {
		var to = parseFloat(element.getAttribute('data-to'));
		var decimals = parseInt(element.getAttribute('data-decimals'), 10) || 0;
		var separator = element.getAttribute('data-separator') || '';
		var from = parseFloat(element.getAttribute('data-from')) || 0;
		if (!isFinite(to)) {
			return;
		}
		var start = 0;
		var step = function (time) {
			if (!start) {
				start = time;
			}
			var t = Math.min(1, (time - start) / duration);
			element.textContent = format(from + (to - from) * easeOutExpo(t), decimals, separator);
			if (t < 1 && element.isConnected) {
				window.requestAnimationFrame(step);
			}
		};
		window.requestAnimationFrame(step);
	}

	function mount(root) {
		if (!root || root.__avixImpact) {
			return;
		}
		root.__avixImpact = true;

		var config = {};
		try {
			config = JSON.parse(root.getAttribute('data-avix-impact') || '{}') || {};
		} catch (error) {
			config = {};
		}

		var animate = config.animate !== false && !reduceMotion.matches && 'IntersectionObserver' in window;
		var counters = Array.prototype.slice.call(root.querySelectorAll('[data-to]'));
		var duration = Math.max(300, parseInt(config.duration, 10) || 1800);

		if (!animate) {
			return;
		}

		root.classList.add('is-armed');
		if (config.countUp !== false) {
			counters.forEach(function (counter) {
				counter.textContent = format(parseFloat(counter.getAttribute('data-from')) || 0, parseInt(counter.getAttribute('data-decimals'), 10) || 0, counter.getAttribute('data-separator') || '');
			});
		}

		var reveal = function () {
			root.classList.add('is-visible');
			if (config.countUp !== false) {
				counters.forEach(function (counter, index) {
					window.setTimeout(function () {
						countUp(counter, duration);
					}, 180 + index * 110);
				});
			}
		};

		var observer = new window.IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					observer.disconnect();
					reveal();
				}
			});
		}, { threshold: 0.2, rootMargin: '0px 0px -8% 0px' });

		observer.observe(root.querySelector('.avix-impact__grid') || root);

		if (isEditMode()) {
			// Editor iframes can miss the first intersection after a re-render.
			window.setTimeout(function () {
				if (!root.classList.contains('is-visible')) {
					observer.disconnect();
					reveal();
				}
			}, 1500);
		}
	}

	function mountAll(scope) {
		Array.prototype.forEach.call((scope || document).querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixImpactNumbers = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-impact-numbers.default', function ($scope) {
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
