/*!
 * Avix Digital · Pixel character (shared)
 *
 * window.AvixPal helps widgets move the character:
 *   AvixPal.play(pal, 'is-wave', 1700)     add a state class, remove it after ms
 *   AvixPal.lookAt(pal, element)           glance toward an element (null = reset)
 *   AvixPal.every(pal, fn, 5000, 9000)     run fn at random intervals while the
 *                                          character is on screen; returns stop()
 * Everything is skipped when the visitor prefers reduced motion.
 */
(function (window, document) {
	'use strict';

	if (window.AvixPal) {
		return;
	}

	var reduce = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };

	function play(pal, className, duration) {
		if (!pal || reduce.matches) {
			return;
		}
		var timers = pal.__avixPalTimers || (pal.__avixPalTimers = {});
		window.clearTimeout(timers[className]);
		pal.classList.remove(className);
		void pal.getBoundingClientRect();
		pal.classList.add(className);
		timers[className] = window.setTimeout(function () {
			pal.classList.remove(className);
		}, duration || 1700);
	}

	function lookAt(pal, target) {
		if (!pal) {
			return;
		}
		if (!target) {
			pal.classList.remove('is-look-l', 'is-look-r');
			return;
		}
		var a = target.getBoundingClientRect();
		var b = pal.getBoundingClientRect();
		var dx = a.left + a.width / 2 - (b.left + b.width / 2);
		pal.classList.toggle('is-look-l', dx < -20);
		pal.classList.toggle('is-look-r', dx > 20);
	}

	function every(pal, fn, min, max) {
		var timer = 0;
		var visible = false;
		var stopped = false;
		var observer = null;

		function schedule() {
			window.clearTimeout(timer);
			if (stopped || !visible || reduce.matches) {
				return;
			}
			timer = window.setTimeout(function () {
				if (!pal.isConnected) {
					stop();
					return;
				}
				if (!document.hidden) {
					fn();
				}
				schedule();
			}, min + Math.random() * Math.max(0, max - min));
		}

		function stop() {
			stopped = true;
			window.clearTimeout(timer);
			if (observer) {
				observer.disconnect();
			}
		}

		if (!pal) {
			return stop;
		}
		if ('IntersectionObserver' in window) {
			observer = new window.IntersectionObserver(function (entries) {
				visible = entries[0].isIntersecting;
				schedule();
			});
			observer.observe(pal);
		} else {
			visible = true;
			schedule();
		}
		return stop;
	}

	window.AvixPal = { play: play, lookAt: lookAt, every: every, reducedMotion: reduce };
})(window, document);
