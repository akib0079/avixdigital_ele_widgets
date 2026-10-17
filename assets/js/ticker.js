/*!
 * Avix Digital · Ticker
 * The words move on one CSS animation (transform only, on the compositor).
 * The script copies just enough word groups to cover the band twice, eases
 * the band to a stop on hover, focus or the pause button, pauses it off
 * screen, and drives the pixel character: it walks on the spot, hops over a
 * passing separator now and then, and says hi when the band is hovered.
 * No work runs per frame unless the band is easing between speeds.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-tk]';
	var reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };

	function clamp(value, min, max) {
		return value < min ? min : value > max ? max : value;
	}

	// Keyboard focus holds the band; a click or tap that focuses the pause
	// button must not, or Play would leave the words frozen.
	function isKeyboardFocus(el) {
		try {
			return el.matches(':focus-visible');
		} catch (error) {
			return true;
		}
	}

	function isEditMode() {
		return !!(window.elementorFrontend && window.elementorFrontend.isEditMode && window.elementorFrontend.isEditMode());
	}

	function Ticker(root) {
		var config = {};
		try {
			config = JSON.parse(root.getAttribute('data-avix-tk') || '{}') || {};
		} catch (error) {
			config = {};
		}

		this.root = root;
		this.alive = true;
		this.stage = root.querySelector('[data-tk-stage]');
		this.pal = root.querySelector('[data-tk-pal]');
		this.toggle = root.querySelector('[data-tk-toggle]');
		this.speed = clamp(isFinite(parseFloat(config.speed)) ? parseFloat(config.speed) : 30, 6, 120);
		this.dir = config.dir === 'right' ? 'right' : 'left';
		this.hop = config.hop !== false;
		this.bands = Array.prototype.map.call(root.querySelectorAll('[data-tk-band]'), function (band) {
			return {
				el: band,
				track: band.querySelector('[data-tk-track]'),
				viewport: band.querySelector('.avix-tk__viewport'),
				sets: Math.max(1, parseInt((band.querySelector('[data-tk-sets]') || band).getAttribute('data-tk-sets'), 10) || 1),
				halves: Array.prototype.slice.call(band.querySelectorAll('[data-tk-half]')),
				main: band.classList.contains('avix-tk__band--main'),
				groupW: 0,
				copies: 0,
				dur: 0
			};
		}).filter(function (band) {
			return band.track && band.halves.length === 2;
		});

		this.holds = {};
		this.rate = 1;
		this.target = 1;
		this.frame = 0;
		this.lastTime = 0;
		this.inView = false;
		this.width = 0;
		this.timers = [];
		this.observers = [];
		this.listeners = [];
		this.stopHops = null;
		this.lastHi = 0;

		if (!this.stage || !this.bands.length) {
			this.alive = false;
			return;
		}

		this.tick = this.tick.bind(this);
		this.init();
	}

	Ticker.prototype.on = function (target, type, fn, options) {
		target.addEventListener(type, fn, options || false);
		this.listeners.push([target, type, fn, options || false]);
	};

	Ticker.prototype.later = function (fn, delay) {
		var self = this;
		var id = window.setTimeout(function () {
			self.timers = self.timers.filter(function (t) {
				return t !== id;
			});
			if (self.alive) {
				fn();
			}
		}, delay);
		this.timers.push(id);
		return id;
	};

	Ticker.prototype.init = function () {
		var self = this;
		var root = this.root;
		var editor = isEditMode();

		if (editor) {
			root.classList.add('is-editor');
		}

		this.build();

		// Hover (mouse) and keyboard focus hold the band; the character stops to say hi.
		this.on(this.stage, 'pointerenter', function (event) {
			if (event.pointerType === 'mouse') {
				self.setHold('hover', true);
				self.greet();
			}
		});
		this.on(this.stage, 'pointerleave', function (event) {
			if (event.pointerType === 'mouse') {
				self.setHold('hover', false);
			}
		});
		// Touch: a tap on the band gets a wave, the words keep moving.
		this.on(this.stage, 'pointerup', function (event) {
			if (event.pointerType !== 'mouse' && !(event.target.closest && event.target.closest('[data-tk-toggle]'))) {
				self.greet();
			}
		});
		this.on(root, 'focusin', function (event) {
			if (isKeyboardFocus(event.target)) {
				self.setHold('focus', true);
			}
		});
		this.on(root, 'focusout', function (event) {
			if (!root.contains(event.relatedTarget)) {
				self.setHold('focus', false);
			}
		});

		if (this.toggle) {
			this.on(this.toggle, 'click', function () {
				var paused = !root.classList.contains('is-user-paused');
				root.classList.toggle('is-user-paused', paused);
				self.toggle.setAttribute('aria-label', self.toggle.getAttribute(paused ? 'data-label-play' : 'data-label-pause'));
				// Play means play, even while the button keeps keyboard focus.
				if (!paused) {
					delete self.holds.focus;
				}
				self.setHold('user', paused);
			});
		}

		this.on(document, 'visibilitychange', function () {
			self.syncOff();
		});

		// Reveal once, pause while off screen.
		var armed = 'IntersectionObserver' in window && !reduceMotion.matches && !editor;
		if (armed) {
			root.classList.add('is-armed');
		}
		if ('IntersectionObserver' in window) {
			var io = new window.IntersectionObserver(function (entries) {
				var entry = entries[entries.length - 1];
				self.inView = entry.isIntersecting;
				if (entry.isIntersecting && entry.intersectionRatio >= 0.15 && !root.classList.contains('is-in')) {
					root.classList.add('is-in');
					self.later(function () {
						self.greet(true);
					}, armed ? 1300 : 600);
				}
				self.syncOff();
			}, { threshold: [0, 0.15] });
			io.observe(root);
			this.observers.push(io);
		} else {
			this.inView = true;
			root.classList.add('is-in');
		}
		if (editor) {
			// Editor iframes can miss the first intersection.
			this.later(function () {
				root.classList.add('is-in');
			}, 1500);
		}

		// Re-count the copies when the width or the font changes.
		if (window.ResizeObserver) {
			var ro = new window.ResizeObserver(function () {
				if (!root.isConnected) {
					self.destroy();
					return;
				}
				var width = root.clientWidth;
				var groupW = self.bands[0].halves[0].firstElementChild ? self.bands[0].halves[0].firstElementChild.offsetWidth : 0;
				if (Math.abs(width - self.width) > 1 || Math.abs(groupW - self.bands[0].groupW) > 1) {
					self.build();
				}
			});
			ro.observe(root);
			if (this.bands[0].halves[0].firstElementChild) {
				ro.observe(this.bands[0].halves[0].firstElementChild);
			}
			this.observers.push(ro);
		}
		if (document.fonts && document.fonts.ready) {
			document.fonts.ready.then(function () {
				if (self.alive) {
					self.build();
				}
			});
		}

		if (this.pal && this.hop && window.AvixPal) {
			this.stopHops = window.AvixPal.every(this.pal, function () {
				self.hopSoon();
			}, 4200, 7800);
		}

		root.classList.add('is-ready');
		this.syncWalk();
	};

	// Copy the word group until each half of the track is at least as wide as
	// the band, so sliding by one half always keeps the band covered.
	Ticker.prototype.build = function () {
		var self = this;
		this.width = this.root.clientWidth;
		this.bands.forEach(function (band) {
			var first = band.halves[0].firstElementChild;
			if (!first) {
				return;
			}
			var groupW = first.offsetWidth;
			var viewW = band.viewport ? band.viewport.offsetWidth : self.width;
			if (!groupW || !viewW) {
				return;
			}
			var copies = reduceMotion.matches ? 1 : Math.max(1, Math.ceil(viewW / groupW));
			band.groupW = groupW;
			if (copies !== band.copies) {
				band.copies = copies;
				band.halves.forEach(function (half) {
					while (half.children.length > copies) {
						half.removeChild(half.lastElementChild);
					}
					while (half.children.length < copies) {
						half.appendChild(first.cloneNode(true));
					}
				});
			}
			// "Seconds per loop" is the time one set of words (or, for a short
			// set, one band width) takes to pass, so short lists don't crawl.
			// A group may hold the set twice (alternating patterns, odd count).
			band.dur = (copies * groupW * self.speed) / Math.max(groupW / band.sets, viewW);
			band.el.style.setProperty('--tk-dur', band.dur.toFixed(2) + 's');
		});
	};

	Ticker.prototype.setHold = function (reason, on) {
		if (on) {
			this.holds[reason] = true;
		} else {
			delete this.holds[reason];
		}
		this.target = Object.keys(this.holds).length ? 0 : 1;
		this.wake();
	};

	Ticker.prototype.syncOff = function () {
		var off = !this.inView || document.hidden;
		this.root.classList.toggle('is-off', off);
		if (!off) {
			this.wake();
		}
	};

	Ticker.prototype.animations = function () {
		var list = [];
		this.bands.forEach(function (band) {
			if (band.track.getAnimations) {
				band.track.getAnimations().forEach(function (animation) {
					list.push(animation);
				});
			}
		});
		return list;
	};

	Ticker.prototype.applyRate = function () {
		var rate = this.rate;
		var animations = this.animations();
		if (!animations.length || !animations[0].updatePlaybackRate && !('playbackRate' in animations[0])) {
			// No Web Animations API: fall back to a hard CSS pause.
			this.root.classList.toggle('is-held', this.target === 0);
			return;
		}
		animations.forEach(function (animation) {
			if (animation.updatePlaybackRate) {
				animation.updatePlaybackRate(rate);
			} else {
				animation.playbackRate = rate;
			}
		});
	};

	Ticker.prototype.wake = function () {
		if (!this.alive || this.frame) {
			return;
		}
		this.lastTime = 0;
		this.frame = window.requestAnimationFrame(this.tick);
	};

	// Eases the playback rate toward its target; sleeps once it gets there.
	Ticker.prototype.tick = function (time) {
		this.frame = 0;
		if (!this.root.isConnected) {
			this.destroy();
			return;
		}
		var dt = this.lastTime ? clamp((time - this.lastTime) / 1000, 0.001, 0.1) : 1 / 60;
		this.lastTime = time;

		if (reduceMotion.matches) {
			this.rate = this.target;
		} else {
			this.rate += (this.target - this.rate) * (1 - Math.exp(-dt / 0.2));
			if (Math.abs(this.rate - this.target) < 0.01) {
				this.rate = this.target;
			}
		}
		this.applyRate();
		this.syncWalk();

		if (this.rate !== this.target && !document.hidden) {
			this.frame = window.requestAnimationFrame(this.tick);
		}
	};

	Ticker.prototype.syncWalk = function () {
		if (this.pal) {
			this.pal.classList.toggle('is-walk', !reduceMotion.matches && this.rate > 0.35);
		}
	};

	Ticker.prototype.greet = function (first) {
		if (!this.pal || !window.AvixPal) {
			return;
		}
		var now = Date.now();
		if (now - this.lastHi < 2400 || (first && this.lastHi)) {
			return;
		}
		this.lastHi = now;
		window.AvixPal.play(this.pal, 'is-hi', 2100);
	};

	// Time a jump so its peak lands right above the next separator passing
	// under the character's feet. One layout read per hop, never per frame.
	Ticker.prototype.hopSoon = function () {
		if (!this.pal || this.rate < 0.95 || this.target !== 1 || this.pal.classList.contains('is-hi')) {
			return;
		}
		var band = this.bands.filter(function (b) {
			return b.main;
		})[0];
		if (!band || !band.groupW || !band.dur) {
			return;
		}
		var palBox = this.pal.getBoundingClientRect();
		var palX = palBox.left + palBox.width / 2;
		var toLeft = this.dir === 'left';
		var best = Infinity;
		Array.prototype.forEach.call(band.track.querySelectorAll('.avix-tk__sep'), function (sep) {
			var box = sep.getBoundingClientRect();
			var d = toLeft ? box.left + box.width / 2 - palX : palX - (box.left + box.width / 2);
			if (d > 30 && d < best) {
				best = d;
			}
		});
		if (!isFinite(best)) {
			return;
		}
		var velocity = (band.groupW * band.copies) / band.dur;
		var wait = best / velocity - 0.3;
		if (wait < 0 || wait > 3.5) {
			return;
		}
		var self = this;
		this.later(function () {
			if (self.rate > 0.95 && !self.root.classList.contains('is-off') && !self.pal.classList.contains('is-hi')) {
				window.AvixPal.play(self.pal, 'is-jump', 650);
			}
		}, wait * 1000);
	};

	Ticker.prototype.destroy = function () {
		this.alive = false;
		if (this.frame) {
			window.cancelAnimationFrame(this.frame);
			this.frame = 0;
		}
		this.timers.forEach(window.clearTimeout);
		this.timers = [];
		this.observers.forEach(function (observer) {
			observer.disconnect();
		});
		this.observers = [];
		this.listeners.forEach(function (l) {
			l[0].removeEventListener(l[1], l[2], l[3]);
		});
		this.listeners = [];
		if (this.stopHops) {
			this.stopHops();
			this.stopHops = null;
		}
	};

	var instances = [];

	function mount(root) {
		if (!root || (root.__avixTk && root.__avixTk.alive)) {
			return;
		}
		// Prune instances whose DOM the editor replaced.
		instances = instances.filter(function (instance) {
			if (!instance.root.isConnected) {
				instance.destroy();
				return false;
			}
			return instance.alive;
		});
		var ticker = new Ticker(root);
		if (ticker.alive) {
			root.__avixTk = ticker;
			instances.push(ticker);
		}
	}

	function mountAll(scope) {
		var base = scope || document;
		if (base.matches && base.matches(ROOT_SELECTOR)) {
			mount(base);
		}
		Array.prototype.forEach.call(base.querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixTicker = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-ticker.default', function ($scope) {
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
