/*!
 * Avix Digital · Team
 * Cards rise in when the section is reached. The pixel character waves now
 * and then, looks toward whoever is hovered, and jumps when its own card is
 * hovered or tapped. Timers only run while the section is on screen.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-tm]';
	var reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };

	function isEditMode() {
		return !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
	}

	function mount(root) {
		if (!root || root.__avixTm) {
			return;
		}
		root.__avixTm = true;

		var pal = root.querySelector('[data-tm-pal]');
		var palCard = root.querySelector('[data-tm-pal-card]');
		var waveTimer = 0;
		var visible = false;

		function play(className, duration) {
			if (!pal) {
				return;
			}
			pal.classList.remove(className);
			void pal.offsetWidth;
			pal.classList.add(className);
			window.setTimeout(function () {
				pal.classList.remove(className);
			}, duration);
		}

		function scheduleWave() {
			window.clearTimeout(waveTimer);
			if (!pal || !visible || reduceMotion.matches) {
				return;
			}
			waveTimer = window.setTimeout(function () {
				if (!root.isConnected) {
					return;
				}
				play('is-wave', 1700);
				scheduleWave();
			}, 5000 + Math.random() * 4000);
		}

		if ('IntersectionObserver' in window) {
			var reveal = !reduceMotion.matches && !isEditMode();
			if (reveal) {
				root.classList.add('is-armed');
			}
			new window.IntersectionObserver(function (entries) {
				visible = entries[0].isIntersecting;
				root.classList.toggle('is-off', !visible);
				if (visible) {
					root.classList.add('is-in');
					scheduleWave();
				} else {
					window.clearTimeout(waveTimer);
				}
			}, { threshold: 0.15 }).observe(root);
		} else {
			root.classList.add('is-in');
		}

		if (!pal) {
			return;
		}

		// Looks (and raises an arm) toward the hovered person.
		Array.prototype.forEach.call(root.querySelectorAll('[data-tm-card]'), function (card) {
			card.addEventListener('pointerenter', function () {
				var a = card.getBoundingClientRect();
				var b = pal.getBoundingClientRect();
				var dx = a.left + a.width / 2 - (b.left + b.width / 2);
				pal.classList.toggle('is-look-l', dx < -20);
				pal.classList.toggle('is-look-r', dx > 20);
			});
			card.addEventListener('pointerleave', function () {
				pal.classList.remove('is-look-l', 'is-look-r');
			});
		});

		if (palCard) {
			palCard.addEventListener('pointerenter', function () {
				play('is-jump', 650);
				play('is-wave', 1700);
			});
			palCard.addEventListener('click', function () {
				play('is-jump', 650);
			});
		}
	}

	function mountAll(scope) {
		Array.prototype.forEach.call((scope || document).querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixTeam = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-team.default', function ($scope) {
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
