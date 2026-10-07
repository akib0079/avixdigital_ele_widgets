/*!
 * Avix Digital · Page Hero
 * Reveals the hero once, lets visitors pause the orbit, and directs the pixel
 * character: it drops onto the hub, says hi now and then, and looks at the
 * platform chip or button being hovered, focused or tapped. The rings turn in
 * CSS; everything pauses off screen, and timers only run while visible.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-ph]';
	var instances = [];
	var mq = function (query) {
		return window.matchMedia ? window.matchMedia(query) : { matches: false };
	};
	var reduceMotion = mq('(prefers-reduced-motion: reduce)');
	var finePointer = mq('(hover: hover) and (pointer: fine)');

	var LAND_MS = 1750; // reveal → the character has landed (CSS: 0.85s delay + 0.9s)
	var HI_MS = 2100; // matches the pixel "hi" in pixel-pal.css
	var TAP_MS = 2200; // a tapped chip keeps its name tag up this long

	function isEditMode() {
		return !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
	}

	function PageHero(root) {
		var config = {};
		try {
			config = JSON.parse(root.getAttribute('data-avix-ph') || '{}') || {};
		} catch (error) {
			config = {};
		}

		this.root = root;
		this.alive = true;
		this.editor = isEditMode();
		this.timers = {};
		this.visible = false;
		this.greetEvery = Math.max(0, Math.min(60, parseFloat(config.greet) || 0)) * 1000;
		this.pal = config.pal ? root.querySelector('.avix-ph__pal') : null;
		this.hub = root.querySelector('[data-ph-hub]');
		this.cta = root.querySelector('[data-ph-cta]');
		this.stat = root.querySelector('[data-ph-stat]');
		this.pause = root.querySelector('[data-ph-pause]');
		this.pauseText = root.querySelector('[data-ph-pause-text]');
		this.orbit = root.querySelector('[data-ph-orbit]');
		this.visual = root.querySelector('[data-ph-visual]');
		this.stage = root.querySelector('.avix-ph__stage');
		this.chips = Array.prototype.slice.call(root.querySelectorAll('[data-ph-chip]'));
		this.active = null;
		this.stopGreeting = null;

		this.onChipEnter = this.onChipEnter.bind(this);
		this.onChipLeave = this.onChipLeave.bind(this);
		this.onChipTap = this.onChipTap.bind(this);
		this.onCtaEnter = this.onCtaEnter.bind(this);
		this.onCtaLeave = this.onCtaLeave.bind(this);
		this.onStatEnter = this.onStatEnter.bind(this);
		this.onHub = this.onHub.bind(this);
		this.onPause = this.onPause.bind(this);
		this.onOrbitDown = this.onOrbitDown.bind(this);
		this.onVisibility = this.onVisibility.bind(this);

		this.init();
	}

	PageHero.prototype.init = function () {
		var self = this;
		var root = this.root;

		if (this.editor) {
			root.classList.add('is-editor');
		}

		// Reveal: content stays visible without IntersectionObserver, with
		// reduced motion and in the editor.
		var arm = 'IntersectionObserver' in window && !reduceMotion.matches && !this.editor;
		if (arm) {
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
				self.setVisible(entries[0].isIntersecting);
			}, { threshold: 0.15 });
			this.observer.observe(root);

			// The visual has its own entrance: on phones it sits below the
			// fold while the copy is already in, so the rings, chips and the
			// character's landing wait until the visual itself is on screen.
			if (this.visual) {
				this.visualObserver = new window.IntersectionObserver(function (entries) {
					if (self.alive && entries[0].isIntersecting) {
						self.showVisual();
					}
				}, { threshold: 0.35 });
				this.visualObserver.observe(this.visual);
			}
		} else {
			this.setVisible(true);
		}

		// Editor iframes can miss the first intersection.
		if (this.editor) {
			this.later('editor', function () {
				self.setVisible(true);
				self.showVisual();
			}, 1500);
		}

		this.chips.forEach(function (chip) {
			chip.addEventListener('pointerenter', self.onChipEnter);
			chip.addEventListener('pointerleave', self.onChipLeave);
			chip.addEventListener('focus', self.onChipEnter);
			chip.addEventListener('blur', self.onChipLeave);
			chip.addEventListener('click', self.onChipTap);
		});

		if (this.cta) {
			this.cta.addEventListener('pointerenter', this.onCtaEnter);
			this.cta.addEventListener('pointerleave', this.onCtaLeave);
			this.cta.addEventListener('focus', this.onCtaEnter);
			this.cta.addEventListener('blur', this.onCtaLeave);
		}

		// The character glances at the stat chip on the image when it is hovered.
		if (this.stat && this.pal) {
			this.stat.addEventListener('pointerenter', this.onStatEnter);
			this.stat.addEventListener('pointerleave', this.onCtaLeave);
		}

		if (this.hub && this.pal) {
			this.hub.addEventListener('pointerenter', this.onHub);
			this.hub.addEventListener('click', this.onHub);
		}

		if (this.pause) {
			this.pause.addEventListener('click', this.onPause);
		}

		// Touch: hold the rings still while a finger is on the orbit, so a
		// moving link chip is easy to hit.
		if (this.orbit) {
			this.orbit.addEventListener('pointerdown', this.onOrbitDown, { passive: true });
		}

		document.addEventListener('visibilitychange', this.onVisibility);
	};

	/* ---------- Timers ---------- */

	PageHero.prototype.later = function (name, fn, ms) {
		var self = this;
		this.cancel(name);
		this.timers[name] = window.setTimeout(function () {
			delete self.timers[name];
			if (self.alive) {
				fn();
			}
		}, ms);
	};

	PageHero.prototype.cancel = function (name) {
		if (this.timers[name]) {
			window.clearTimeout(this.timers[name]);
			delete this.timers[name];
		}
	};

	/* ---------- Visibility ---------- */

	PageHero.prototype.setVisible = function (visible) {
		var root = this.root;
		this.visible = visible;
		root.classList.toggle('is-off', !visible || document.hidden);
		if (!visible) {
			return;
		}
		if (!root.classList.contains('is-in')) {
			root.classList.add('is-in');
			// No separate visual entrance without an observer (or no visual).
			if (!this.visualObserver) {
				this.showVisual();
			}
		}
	};

	PageHero.prototype.showVisual = function () {
		if (this.shown) {
			return;
		}
		this.shown = true;
		if (this.visual) {
			this.visual.classList.add('is-in');
		}
		if (this.visualObserver) {
			this.visualObserver.disconnect();
		}
		this.arrive();
	};

	PageHero.prototype.onVisibility = function () {
		if (!this.root.isConnected) {
			this.destroy();
			return;
		}
		this.root.classList.toggle('is-off', !this.visible || document.hidden);
	};

	/* ---------- Character ---------- */

	PageHero.prototype.canMove = function () {
		return !!(this.pal && window.AvixPal && !reduceMotion.matches && !this.root.classList.contains('is-paused'));
	};

	// First hi after landing, then now and then while the hero is on screen.
	// Never on a timer in the editor (hover and click reactions still work).
	PageHero.prototype.arrive = function () {
		var self = this;
		if (!this.pal || !window.AvixPal || reduceMotion.matches || this.editor) {
			return;
		}
		var armed = this.root.classList.contains('is-armed');
		this.later('hi', function () {
			self.hi();
			if (self.greetEvery > 0 && !self.stopGreeting) {
				self.stopGreeting = window.AvixPal.every(self.pal, function () {
					if (!self.active && self.canMove()) {
						self.hi();
					}
				}, self.greetEvery * 0.8, self.greetEvery * 1.25);
			}
		}, armed ? LAND_MS : 600);
	};

	PageHero.prototype.hi = function () {
		if (this.canMove()) {
			window.AvixPal.play(this.pal, 'is-hi', HI_MS);
		}
	};

	PageHero.prototype.look = function (target) {
		if (this.pal && window.AvixPal) {
			window.AvixPal.lookAt(this.pal, target || null);
		}
	};

	/* ---------- Chips ---------- */

	// The slot and the whole chip layer are raised while a chip is in use,
	// so its name tag clears the other chips, the hub and the character.
	PageHero.prototype.mark = function (chip, on) {
		var slot = chip.closest ? chip.closest('.avix-ph__slot') : null;
		var layer = chip.closest ? chip.closest('.avix-ph__chips') : null;
		chip.classList.toggle('is-active', on);
		if (slot) {
			slot.classList.toggle('is-active', on);
		}
		if (layer) {
			layer.classList.toggle('is-active', on);
		}
	};

	PageHero.prototype.syncHold = function () {
		this.root.classList.toggle('is-hold', !!(this.active || this.timers.hold));
	};

	PageHero.prototype.activate = function (chip) {
		if (this.active && this.active !== chip) {
			this.deactivate(this.active);
		}
		this.active = chip;
		this.mark(chip, true);
		this.syncHold();
		this.placeTip(chip);
		this.look(chip);
	};

	// Keeps the name tag inside the stage: shifted sideways near the left or
	// right edge, and above the chip for chips in the orbit's upper half
	// (pointing away from the hub), unless that would leave the stage. The
	// rings are held (is-hold) while a chip is active, so one read is stable.
	PageHero.prototype.placeTip = function (chip) {
		var tip = chip.querySelector('.avix-ph__tip');
		if (!tip || !this.stage || !this.orbit) {
			return;
		}
		var w = tip.offsetWidth;
		var h = tip.offsetHeight;
		if (!w || window.getComputedStyle(tip).position === 'static') {
			return; // empty, or an "always visible" name pill
		}
		var stage = this.stage.getBoundingClientRect();
		var orbit = this.orbit.getBoundingClientRect();
		var box = chip.getBoundingClientRect();
		var cx = box.left + box.width / 2;
		var cy = box.top + box.height / 2;
		var pad = 10;
		var dx = Math.max(stage.left + pad - (cx - w / 2), 0) + Math.min(stage.right - pad - (cx + w / 2), 0);
		var up = cy < orbit.top + orbit.height / 2 - 1;
		if (up && box.top - 9 - h < stage.top + pad) {
			up = false;
		} else if (!up && box.bottom + 9 + h > stage.bottom - pad) {
			up = true;
		}
		tip.style.setProperty('--tip-dx', Math.round(dx) + 'px');
		chip.classList.toggle('is-tip-up', up);
	};

	PageHero.prototype.deactivate = function (chip) {
		this.mark(chip, false);
		if (this.active === chip) {
			this.active = null;
			this.look(null);
		}
		this.syncHold();
	};

	PageHero.prototype.onOrbitDown = function (event) {
		var self = this;
		if (event.pointerType !== 'touch') {
			return;
		}
		this.later('hold', function () {
			self.syncHold();
		}, TAP_MS);
		this.syncHold();
	};

	PageHero.prototype.onChipEnter = function (event) {
		this.cancel('tap');
		this.activate(event.currentTarget);
	};

	PageHero.prototype.onChipLeave = function (event) {
		// A tap fires pointerleave right away on touch; keep the tag up a moment.
		if (event.pointerType === 'touch' || this.timers.tap) {
			return;
		}
		this.deactivate(event.currentTarget);
	};

	PageHero.prototype.onChipTap = function (event) {
		var self = this;
		var chip = event.currentTarget;
		if (finePointer.matches && event.pointerType !== 'touch') {
			return;
		}
		this.activate(chip);
		if (this.canMove()) {
			window.AvixPal.play(this.pal, 'is-wave', 1700);
		}
		this.later('tap', function () {
			self.deactivate(chip);
		}, TAP_MS);
	};

	/* ---------- Button & hub ---------- */

	PageHero.prototype.onCtaEnter = function () {
		this.look(this.cta);
		if (this.canMove()) {
			window.AvixPal.play(this.pal, 'is-wave', 1700);
		}
	};

	PageHero.prototype.onCtaLeave = function () {
		if (!this.active) {
			this.look(null);
		}
	};

	PageHero.prototype.onStatEnter = function () {
		this.look(this.stat);
	};

	PageHero.prototype.onHub = function () {
		if (!this.canMove() || this.timers.hub) {
			return;
		}
		window.AvixPal.play(this.pal, 'is-jump', 650);
		this.later('hub', function () {}, 900);
	};

	/* ---------- Pause ---------- */

	PageHero.prototype.onPause = function () {
		var paused = !this.root.classList.contains('is-paused');
		this.root.classList.toggle('is-paused', paused);
		// The label says what the button will do next (no aria-pressed).
		var label = this.pause.getAttribute(paused ? 'data-play' : 'data-pause') || '';
		this.pause.setAttribute('aria-label', label);
		if (this.pauseText) {
			this.pauseText.textContent = label;
		}
	};

	/* ---------- Teardown ---------- */

	PageHero.prototype.destroy = function () {
		var self = this;
		if (!this.alive) {
			return;
		}
		this.alive = false;
		Object.keys(this.timers).forEach(this.cancel, this);
		if (this.stopGreeting) {
			this.stopGreeting();
		}
		if (this.observer) {
			this.observer.disconnect();
		}
		if (this.visualObserver) {
			this.visualObserver.disconnect();
		}
		this.chips.forEach(function (chip) {
			chip.removeEventListener('pointerenter', self.onChipEnter);
			chip.removeEventListener('pointerleave', self.onChipLeave);
			chip.removeEventListener('focus', self.onChipEnter);
			chip.removeEventListener('blur', self.onChipLeave);
			chip.removeEventListener('click', self.onChipTap);
		});
		if (this.cta) {
			this.cta.removeEventListener('pointerenter', this.onCtaEnter);
			this.cta.removeEventListener('pointerleave', this.onCtaLeave);
			this.cta.removeEventListener('focus', this.onCtaEnter);
			this.cta.removeEventListener('blur', this.onCtaLeave);
		}
		if (this.stat && this.pal) {
			this.stat.removeEventListener('pointerenter', this.onStatEnter);
			this.stat.removeEventListener('pointerleave', this.onCtaLeave);
		}
		if (this.hub && this.pal) {
			this.hub.removeEventListener('pointerenter', this.onHub);
			this.hub.removeEventListener('click', this.onHub);
		}
		if (this.pause) {
			this.pause.removeEventListener('click', this.onPause);
		}
		if (this.orbit) {
			this.orbit.removeEventListener('pointerdown', this.onOrbitDown, { passive: true });
		}
		document.removeEventListener('visibilitychange', this.onVisibility);
		var index = instances.indexOf(this);
		if (index > -1) {
			instances.splice(index, 1);
		}
	};

	function mount(root) {
		if (!root || (root.__avixPh && root.__avixPh.alive)) {
			return;
		}
		// Editor re-renders replace the DOM; drop instances whose root is gone.
		instances.slice().forEach(function (instance) {
			if (!instance.root.isConnected) {
				instance.destroy();
			}
		});
		var hero = new PageHero(root);
		if (hero.alive) {
			root.__avixPh = hero;
			instances.push(hero);
		}
	}

	function mountAll(scope) {
		Array.prototype.forEach.call((scope || document).querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixPageHero = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-page-hero.default', function ($scope) {
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
