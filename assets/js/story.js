/*!
 * Avix Digital · Story
 * Inks the statement word by word as the visitor scrolls through it, settles
 * the photo from a slight zoom, reveals the card and counts the numbers up
 * once. The pixel character on the panel waves now and then, looks at the
 * number you point at and cheers when the counting is done.
 * Scroll work is rAF-throttled, reads before writes, and only runs while the
 * section is near the screen; nothing loops while it is off screen.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-st]';
	var reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };
	// Visitors who ask for more contrast get the statement fully inked: the grey words sit near 1.4:1.
	var moreContrast = window.matchMedia ? window.matchMedia('(prefers-contrast: more)') : { matches: false };
	var instances = [];

	function clamp(value, min, max) {
		return value < min ? min : value > max ? max : value;
	}

	function easeOutExpo(t) {
		return t >= 1 ? 1 : 1 - Math.pow(2, -10 * t);
	}

	// Mirrors the PHP number_format(): same thousands separator and decimal mark (a comma when thousands use a dot).
	function format(value, decimals, separator, decimal) {
		var parts = value.toFixed(decimals).split('.');
		if (separator) {
			parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, separator);
		}
		return parts.join(decimal || '.');
	}

	function readNum(el) {
		var to = parseFloat(el.getAttribute('data-to'));
		var decimals = parseInt(el.getAttribute('data-decimals'), 10) || 0;
		return {
			to: to,
			decimals: decimals,
			// A rating like 4.9 ticks up from 3.9, so it never flashes a poor score on the way.
			from: decimals > 0 && to <= 10 ? Math.max(0, to - 1) : 0,
			separator: el.getAttribute('data-separator') || '',
			decimal: el.getAttribute('data-decimal') || '.'
		};
	}

	function isEditMode() {
		return !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
	}

	function Story(root) {
		var config = {};
		try {
			config = JSON.parse(root.getAttribute('data-avix-st') || '{}') || {};
		} catch (error) {
			config = {};
		}

		this.root = root;
		this.alive = true;
		this.config = config;
		this.statement = root.querySelector('[data-st-statement]');
		this.words = Array.prototype.slice.call(root.querySelectorAll('[data-st-w]'));
		this.media = root.querySelector('[data-st-media]');
		this.zoomEl = root.querySelector('[data-st-zoom]');
		this.panel = root.querySelector('[data-st-panel]');
		this.pal = root.querySelector('[data-st-pal]');
		this.stats = Array.prototype.slice.call(root.querySelectorAll('[data-st-stat]'));
		this.counters = Array.prototype.slice.call(root.querySelectorAll('.avix-st__num[data-to]'));
		this.reveals = Array.prototype.slice.call(root.querySelectorAll('[data-st-reveal]'));

		this.editor = isEditMode();
		this.motion = !reduceMotion.matches && !this.editor && 'IntersectionObserver' in window;
		this.fill = this.motion && config.fill !== false && !moreContrast.matches && !!this.statement && this.words.length > 0;
		this.zoom = this.motion && config.zoom !== false && !!this.zoomEl;
		this.lit = -1;
		this.zoomScale = -1;
		this.frame = 0;
		this.near = false;
		this.counted = false;
		this.observers = [];
		this.timers = [];
		this.stopWave = null;

		this.update = this.update.bind(this);
		this.request = this.request.bind(this);

		this.init();
	}

	Story.prototype.init = function () {
		var self = this;
		var root = this.root;

		if (this.motion && this.config.reveal !== false) {
			root.classList.add('is-armed');
		}
		if (this.fill) {
			// No colour transition on the first pass: a statement already on screen (reload or back mid-page)
			// must open at its scroll state, not fade the unread words from ink to grey.
			root.classList.add('is-fill', 'is-fill-init');
		}

		if (!('IntersectionObserver' in window)) {
			this.reveals.forEach(function (el) {
				el.classList.add('is-in');
			});
			return;
		}

		// Near the screen: listen to scroll. Off screen: pause the character.
		this.watch(new window.IntersectionObserver(function (entries) {
			var entry = entries[entries.length - 1];
			self.setNear(entry.isIntersecting);
		}, { rootMargin: '15% 0px 15% 0px' }), root);

		// Reveal each part (statement foot, photo card) as it arrives.
		if (root.classList.contains('is-armed')) {
			var revealer = this.watch(new window.IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						entry.target.classList.add('is-in');
						revealer.unobserve(entry.target);
					}
				});
			}, { threshold: 0.15, rootMargin: '0px 0px -6% 0px' }));
			this.reveals.forEach(function (el) {
				revealer.observe(el);
			});
			// Keyboard users tabbing ahead of the scroll must never land on something still hidden.
			this.onFocusIn = function () {
				self.reveals.forEach(function (el) {
					el.classList.add('is-in');
				});
			};
			root.addEventListener('focusin', this.onFocusIn);
		} else {
			this.reveals.forEach(function (el) {
				el.classList.add('is-in');
			});
		}

		// Count the numbers once the panel is in view.
		if (this.panel) {
			var counter = this.watch(new window.IntersectionObserver(function (entries) {
				if (entries[entries.length - 1].isIntersecting) {
					counter.disconnect();
					self.count();
				}
			}, { threshold: 0.4 }), this.panel);
		}

		if (this.motion && this.config.countUp !== false) {
			this.counters.forEach(function (el) {
				var n = readNum(el);
				if (isFinite(n.to)) {
					el.textContent = format(n.from, n.decimals, n.separator, n.decimal);
				}
			});
		}

		this.bindPal();

		if (this.editor) {
			// Editor iframes can miss the first intersection after a re-render.
			this.later(function () {
				self.reveals.forEach(function (el) {
					el.classList.add('is-in');
				});
			}, 1500);
		}

		if (this.fill || this.zoom) {
			// First pass synchronously (reads, then writes), so the right words are inked before the first paint.
			this.update();
		}
		if (this.fill) {
			// Commit that state without transitions, then hand the colours back to CSS on the next frame.
			void this.statement.offsetHeight;
			window.requestAnimationFrame(function () {
				root.classList.remove('is-fill-init');
			});
		}
	};

	Story.prototype.watch = function (observer, target) {
		this.observers.push(observer);
		if (target) {
			observer.observe(target);
		}
		return observer;
	};

	Story.prototype.later = function (fn, ms) {
		var self = this;
		var id = window.setTimeout(function () {
			self.timers = self.timers.filter(function (t) {
				return t !== id;
			});
			if (self.alive) {
				fn();
			}
		}, ms);
		this.timers.push(id);
		return id;
	};

	Story.prototype.setNear = function (near) {
		this.root.classList.toggle('is-off', !near);
		if (near === this.near) {
			return;
		}
		this.near = near;
		if (!this.fill && !this.zoom) {
			return;
		}
		if (near) {
			window.addEventListener('scroll', this.request, { passive: true });
			window.addEventListener('resize', this.request, { passive: true });
			this.request();
		} else {
			window.removeEventListener('scroll', this.request);
			window.removeEventListener('resize', this.request);
		}
	};

	/* ---------- Scroll fill + photo zoom ---------- */

	Story.prototype.request = function () {
		if (!this.frame && this.alive) {
			this.frame = window.requestAnimationFrame(this.update);
		}
	};

	Story.prototype.update = function () {
		this.frame = 0;
		if (!this.root.isConnected) {
			this.destroy();
			return;
		}

		var doc = document.documentElement;
		var vh = window.innerHeight || doc.clientHeight;
		var lit = -1;
		var scale = -1;

		// Reads first.
		if (this.fill) {
			var r = this.statement.getBoundingClientRect();
			var start = vh * clamp(+this.config.start || 0.85, 0.5, 1);
			var end = vh * clamp(+this.config.end || 0.62, 0.1, 0.9);
			// Near the end of a page the statement may never rise to the end line: finish where it stops instead.
			var remaining = Math.max(0, Math.max(doc.scrollHeight, document.body ? document.body.scrollHeight : 0) - (window.pageYOffset || doc.scrollTop || 0) - vh);
			end = Math.max(end, r.bottom - remaining - 2);
			// 0 when the top reaches the start line, 1 when the bottom reaches the end line.
			var span = Math.max(1, r.height + start - end);
			var progress = clamp((start - r.top) / span, 0, 1);
			lit = Math.round(progress * this.words.length);
		}
		if (this.zoom && this.media) {
			var m = this.media.getBoundingClientRect();
			// 0 as the card's top enters the screen, 1 once it reaches the upper third.
			var t = clamp((vh - m.top) / Math.max(1, vh * 0.7), 0, 1);
			scale = Math.round((1.1 - 0.1 * easeOutExpo(t)) * 1000) / 1000;
		}

		// Then writes, only for what changed.
		if (lit !== -1 && lit !== this.lit) {
			var from = this.lit < 0 ? 0 : Math.min(this.lit, lit);
			var to = this.lit < 0 ? this.words.length : Math.max(this.lit, lit);
			for (var i = from; i < to; i++) {
				this.words[i].classList.toggle('is-lit', i < lit);
			}
			this.lit = lit;
		}
		if (scale !== -1 && scale !== this.zoomScale) {
			this.zoomEl.style.transform = scale > 1 ? 'scale(' + scale + ')' : '';
			// A compositor layer only while it scales; once settled at 1 the photo is a plain image again.
			this.zoomEl.style.willChange = scale > 1 ? 'transform' : '';
			this.zoomScale = scale;
		}
	};

	/* ---------- Count-up ---------- */

	Story.prototype.count = function () {
		var self = this;
		if (this.counted) {
			return;
		}
		this.counted = true;

		if (!this.motion || this.config.countUp === false || !this.counters.length) {
			// Nothing to count: still celebrate the numbers once they're in view.
			this.later(function () {
				self.cheer();
			}, this.motion ? 1400 : 0);
			return;
		}

		var duration = clamp(parseInt(this.config.duration, 10) || 1800, 300, 6000);
		var remaining = this.counters.length;
		this.counters.forEach(function (el, index) {
			self.later(function () {
				self.countOne(el, duration, function () {
					remaining -= 1;
					if (!remaining) {
						self.cheer();
					}
				});
			}, 450 + index * 110);
		});
	};

	Story.prototype.countOne = function (el, duration, done) {
		var self = this;
		var n = readNum(el);
		var start = 0;
		if (!isFinite(n.to)) {
			done();
			return;
		}
		var step = function (time) {
			if (!self.alive) {
				return;
			}
			if (!start) {
				start = time;
			}
			var t = Math.min(1, (time - start) / duration);
			el.textContent = format(n.from + (n.to - n.from) * easeOutExpo(t), n.decimals, n.separator, n.decimal);
			if (t < 1) {
				window.requestAnimationFrame(step);
			} else {
				done();
			}
		};
		window.requestAnimationFrame(step);
	};

	/* ---------- Pixel character ---------- */

	Story.prototype.bindPal = function () {
		var self = this;
		var pal = this.pal;
		var Pal = window.AvixPal;
		if (!pal || !Pal) {
			return;
		}

		var every = parseInt(this.config.wave, 10) || 0;
		// No waving on a timer while editing: the canvas should hold still.
		if (every > 0 && !reduceMotion.matches && !this.editor) {
			var ms = every * 1000;
			this.stopWave = Pal.every(pal, function () {
				Pal.play(pal, 'is-wave', 1700);
			}, ms * 0.75, ms * 1.25);
		}

		// Looks at the number under the pointer (mouse only; taps would leave it staring).
		this.onStatEnter = function (event) {
			if (event.pointerType === 'mouse') {
				Pal.lookAt(pal, event.currentTarget);
			}
		};
		this.onStatLeave = function () {
			Pal.lookAt(pal, null);
		};
		this.stats.forEach(function (stat) {
			stat.addEventListener('pointerenter', self.onStatEnter);
			stat.addEventListener('pointerleave', self.onStatLeave);
		});
	};

	Story.prototype.cheer = function () {
		if (this.pal && window.AvixPal && this.config.cheer && !this.editor) {
			window.AvixPal.play(this.pal, 'is-cheer', 1300);
		}
	};

	/* ---------- Teardown (Elementor re-renders replace the DOM) ---------- */

	Story.prototype.destroy = function () {
		var self = this;
		if (!this.alive) {
			return;
		}
		this.alive = false;
		window.cancelAnimationFrame(this.frame);
		window.removeEventListener('scroll', this.request);
		window.removeEventListener('resize', this.request);
		this.observers.forEach(function (observer) {
			observer.disconnect();
		});
		this.timers.forEach(function (id) {
			window.clearTimeout(id);
		});
		if (this.stopWave) {
			this.stopWave();
		}
		if (this.onFocusIn) {
			this.root.removeEventListener('focusin', this.onFocusIn);
		}
		if (this.onStatEnter) {
			this.stats.forEach(function (stat) {
				stat.removeEventListener('pointerenter', self.onStatEnter);
				stat.removeEventListener('pointerleave', self.onStatLeave);
			});
		}
	};

	/* ---------- Mounting ---------- */

	function prune() {
		instances = instances.filter(function (instance) {
			if (!instance.root.isConnected) {
				instance.destroy();
				return false;
			}
			return instance.alive;
		});
	}

	function mount(root) {
		if (!root || (root.__avixSt && root.__avixSt.alive)) {
			return;
		}
		prune();
		root.__avixSt = new Story(root);
		instances.push(root.__avixSt);
	}

	function mountAll(scope) {
		Array.prototype.forEach.call((scope || document).querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixStory = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-story.default', function ($scope) {
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
