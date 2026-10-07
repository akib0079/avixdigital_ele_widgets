/*!
 * Avix Digital · Case Study Kit
 * The pixel-resolve reveal shared by the case-study widgets: a screenshot
 * marked [data-csk-pixels] arrives behind a layer of 16px squares in the
 * mat's colour that clear in a random order, once. One tiny canvas (one
 * canvas pixel per square) drawn in a single rAF loop, then removed. Skipped
 * with reduced motion, in the editor, without IntersectionObserver, and for
 * screenshots already on screen at load (the LCP image is never hidden).
 * Exposes window.AvixCsk.
 */
(function (window, document) {
	'use strict';

	var SELECTOR = '[data-csk-pixels]';
	var WIDGETS = ['hero', 'chapter', 'spotlight', 'results', 'gallery', 'stack', 'next', 'grid'];
	var SQUARE = 16; // CSS px per square
	var SPREAD = 420; // ms: the last square starts clearing by then
	var FADE = 220; // ms: each square's fade
	var WAIT_IMG = 2500; // ms: longest wait for the screenshot to load before revealing anyway
	var SETTLE = 1200; // ms: partly visible but never 25% (very tall screenshots) → reveal anyway
	var DEFAULT_COVER = '#f5f3ef';

	var mq = function (query) {
		return window.matchMedia ? window.matchMedia(query) : { matches: false };
	};
	var reduceMotion = mq('(prefers-reduced-motion: reduce)');
	var supportsCanvas = (function () {
		try {
			var c = document.createElement('canvas');
			return !!(c.getContext && c.getContext('2d'));
		} catch (error) {
			return false;
		}
	})();
	var raf = window.requestAnimationFrame || function (fn) {
		return window.setTimeout(function () {
			fn(Date.now());
		}, 16);
	};
	var now = function () {
		return window.performance && window.performance.now ? window.performance.now() : Date.now();
	};

	var observer = null;
	var pending = [];

	function isEditMode() {
		return !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
	}

	function canAnimate() {
		return supportsCanvas && 'IntersectionObserver' in window && !reduceMotion.matches && !isEditMode();
	}

	function inViewport(el) {
		var rect = el.getBoundingClientRect();
		var vh = window.innerHeight || document.documentElement.clientHeight;
		var vw = window.innerWidth || document.documentElement.clientWidth;
		return rect.bottom > 0 && rect.right > 0 && rect.top < vh && rect.left < vw;
	}

	/**
	 * The cover colour as [r, g, b]: read from --csk-cover (a mat sets it; a
	 * dark section sets #0b0b0c) and resolved by the browser, so any CSS
	 * colour works, color-mix() included.
	 */
	function coverColor(el, probe) {
		var raw = '';
		try {
			raw = (window.getComputedStyle(el).getPropertyValue('--csk-cover') || '').trim();
		} catch (error) {
			raw = '';
		}
		probe.style.color = DEFAULT_COVER;
		if (raw) {
			probe.style.color = raw;
		}
		var value = window.getComputedStyle(probe).color || '';
		var m = value.match(/rgba?\(\s*([\d.]+)[,\s]+([\d.]+)[,\s]+([\d.]+)/);
		if (m) {
			return [Math.round(+m[1]), Math.round(+m[2]), Math.round(+m[3])];
		}
		// color(srgb 0.96 0.95 0.94) from color-mix().
		m = value.match(/color\(\s*srgb\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)/);
		if (m) {
			return [Math.round(+m[1] * 255), Math.round(+m[2] * 255), Math.round(+m[3] * 255)];
		}
		return [245, 243, 239];
	}

	/* ---------- One screen ---------- */

	function Resolve(screen) {
		this.screen = screen;
		this.alive = true;
		this.started = false;
		this.timers = [];
		this.canvas = null;
		this.arm();
	}

	Resolve.prototype.arm = function () {
		var screen = this.screen;
		var width = screen.clientWidth;
		var height = screen.clientHeight;
		if (!width || !height) {
			// Hidden (a closed tab or panel): arm when it is laid out.
			this.deferred = true;
			return;
		}
		this.deferred = false;

		var cols = Math.max(1, Math.ceil(width / SQUARE));
		var rows = Math.max(1, Math.ceil(height / SQUARE));
		var canvas = document.createElement('canvas');
		canvas.className = 'avix-csk-px';
		canvas.setAttribute('aria-hidden', 'true');
		canvas.width = cols;
		canvas.height = rows;
		screen.appendChild(canvas);

		var ctx = canvas.getContext('2d');
		var color = coverColor(screen, canvas);
		canvas.style.color = '';
		var image = ctx.createImageData(cols, rows);
		var delays = new Float32Array(cols * rows);
		var data = image.data;
		var i;
		for (i = 0; i < cols * rows; i++) {
			var x = i % cols;
			var y = Math.floor(i / cols);
			// Mostly random, with a light top-left → bottom-right drift so the
			// screenshot resolves into place rather than flickering.
			var drift = (x / cols + y / rows) / 2;
			delays[i] = (Math.random() * 0.78 + drift * 0.22) * SPREAD;
			data[i * 4] = color[0];
			data[i * 4 + 1] = color[1];
			data[i * 4 + 2] = color[2];
			data[i * 4 + 3] = 255;
		}
		ctx.putImageData(image, 0, 0);

		this.canvas = canvas;
		this.ctx = ctx;
		this.image = image;
		this.delays = delays;
		this.count = cols * rows;
		screen.classList.add('is-px-armed');
	};

	/** Called by the observer while the screen is (partly) on screen. */
	Resolve.prototype.seen = function (entry) {
		var self = this;
		if (this.started || !this.alive) {
			return;
		}
		if (this.deferred) {
			// It was hidden at load (a closed panel) and has only now been laid
			// out, already on screen: covering it now would flash, so it simply
			// shows.
			this.finish();
			return;
		}
		var rootHeight = entry.rootBounds ? entry.rootBounds.height : (window.innerHeight || 0);
		var enough = entry.intersectionRatio >= 0.25 || (rootHeight && entry.intersectionRect.height >= rootHeight * 0.25);
		if (enough) {
			this.start();
			return;
		}
		if (!this.settle) {
			this.settle = window.setTimeout(function () {
				self.settle = null;
				if (self.alive && self.visible) {
					self.start();
				}
			}, SETTLE);
			this.timers.push(this.settle);
		}
	};

	/** Waits (briefly) for the screenshot itself, then clears the squares. */
	Resolve.prototype.start = function () {
		var self = this;
		if (this.started || !this.alive) {
			return;
		}
		this.started = true;
		if (observer) {
			observer.unobserve(this.screen);
		}
		var img = this.screen.querySelector('img');
		if (!img || img.complete) {
			this.play();
			return;
		}
		var gone = false;
		var go = function () {
			if (gone) {
				return;
			}
			gone = true;
			img.removeEventListener('load', go);
			img.removeEventListener('error', go);
			self.play();
		};
		img.addEventListener('load', go);
		img.addEventListener('error', go);
		this.timers.push(window.setTimeout(go, WAIT_IMG));
	};

	Resolve.prototype.play = function () {
		var self = this;
		if (this.playing || !this.alive) {
			return;
		}
		if (!this.canvas) {
			this.finish();
			return;
		}
		this.playing = true;
		var t0 = now();
		var data = this.image.data;
		var delays = this.delays;
		var count = this.count;

		function tick() {
			if (!self.alive) {
				return;
			}
			var t = now() - t0;
			var busy = false;
			for (var i = 0; i < count; i++) {
				var p = (t - delays[i]) / FADE;
				if (p <= 0) {
					busy = true;
					continue;
				}
				if (p >= 1) {
					data[i * 4 + 3] = 0;
					continue;
				}
				busy = true;
				// Ease-in: a square holds, then gives way.
				data[i * 4 + 3] = Math.round(255 * (1 - p * p));
			}
			self.ctx.putImageData(self.image, 0, 0);
			if (busy) {
				raf(tick);
			} else {
				self.finish();
			}
		}
		raf(tick);
	};

	Resolve.prototype.finish = function () {
		this.started = true;
		if (this.canvas && this.canvas.parentNode) {
			this.canvas.parentNode.removeChild(this.canvas);
		}
		this.canvas = null;
		this.ctx = null;
		this.image = null;
		this.delays = null;
		this.screen.classList.remove('is-px-armed');
		this.screen.classList.add('is-px-done');
		this.destroy(true);
	};

	Resolve.prototype.destroy = function (keepDone) {
		this.timers.forEach(function (id) {
			window.clearTimeout(id);
		});
		this.timers = [];
		if (observer) {
			observer.unobserve(this.screen);
		}
		if (!keepDone && this.canvas && this.canvas.parentNode) {
			this.canvas.parentNode.removeChild(this.canvas);
			this.screen.classList.remove('is-px-armed');
		}
		this.alive = false;
		var index = pending.indexOf(this);
		if (index > -1) {
			pending.splice(index, 1);
		}
	};

	function ensureObserver() {
		if (observer || !('IntersectionObserver' in window)) {
			return observer;
		}
		observer = new window.IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				var instance = entry.target.__avixCsk;
				if (!instance || !instance.alive) {
					return;
				}
				if (!entry.target.isConnected) {
					instance.destroy();
					return;
				}
				instance.visible = entry.isIntersecting;
				if (entry.isIntersecting) {
					instance.seen(entry);
				}
			});
		}, { threshold: [0, 0.25, 0.5] });
		return observer;
	}

	/* ---------- Public API ---------- */

	/**
	 * Arms one screen for the pixel reveal (idempotent). Screens already on
	 * screen, and every screen when motion is off, are simply marked done.
	 */
	function pixelResolve(screen) {
		if (!screen || screen.__avixCskDone || (screen.__avixCsk && screen.__avixCsk.alive)) {
			return;
		}
		// Editor re-renders replace the DOM: forget screens that are gone.
		pending.slice().forEach(function (instance) {
			if (!instance.screen.isConnected) {
				instance.destroy();
			}
		});
		if (!canAnimate() || inViewport(screen)) {
			screen.__avixCskDone = true;
			screen.classList.add('is-px-done');
			return;
		}
		var instance = new Resolve(screen);
		screen.__avixCsk = instance;
		pending.push(instance);
		ensureObserver().observe(screen);
	}

	/** Reveals one armed screen now (e.g. a gallery item opened in a lightbox). */
	function reveal(screen) {
		if (screen && screen.__avixCsk && screen.__avixCsk.alive) {
			screen.__avixCsk.start();
		}
	}

	function mount(el) {
		if (!el || !el.matches) {
			return;
		}
		if (el.matches(SELECTOR)) {
			pixelResolve(el);
		}
	}

	function mountAll(scope) {
		var root = scope || document;
		if (root.matches && root.matches(SELECTOR)) {
			pixelResolve(root);
		}
		if (root.querySelectorAll) {
			Array.prototype.forEach.call(root.querySelectorAll(SELECTOR), pixelResolve);
		}
	}

	window.AvixCsk = {
		mount: mount,
		mountAll: mountAll,
		pixelResolve: pixelResolve,
		reveal: reveal,
		isEditMode: isEditMode,
		reduceMotion: reduceMotion
	};

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
		WIDGETS.forEach(function (name) {
			window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-case-study-' + name + '.default', function ($scope) {
				var element = $scope && $scope[0] ? $scope[0] : $scope;
				if (element && element.querySelectorAll) {
					mountAll(element);
				}
			});
		});
	}

	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		hookElementor();
	} else {
		window.addEventListener('elementor/frontend/init', hookElementor);
	}
})(window, document);
