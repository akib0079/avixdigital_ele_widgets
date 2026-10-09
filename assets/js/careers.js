/*!
 * Avix Digital · Careers
 * Reveals the card when it is reached; the pixel character pops up on the
 * card's edge and says hi, waves its flag now and then, looks at the hovered
 * perk, jumps when the sign is hovered or tapped and cheers while the button
 * is hovered or has keyboard focus. Timers and loops only run while the card is on screen.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-cr]';
	var reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };
	var instances = [];

	function isEditMode() {
		return !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
	}

	function Careers(root) {
		var config = {};
		try {
			config = JSON.parse(root.getAttribute('data-avix-cr') || '{}') || {};
		} catch (e) {
			config = {};
		}

		this.root = root;
		this.alive = true;
		this.editor = isEditMode();
		this.every = Math.max(3, Math.min(60, parseInt(config.every, 10) || 8)) * 1000;
		this.pal = root.querySelector('[data-cr-pal]');
		this.scene = root.querySelector('[data-cr-scene]');
		this.cta = root.querySelector('[data-cr-cta]');
		this.looks = Array.prototype.slice.call(root.querySelectorAll('[data-cr-look]'));
		this.timers = {};
		this.visible = false;
		this.revealed = false;
		this.cheering = false;
		this.stopEvery = null;
		this.observer = null;
		this.revealer = null;
		this.unbinders = [];

		this.onVisibility = this.onVisibility.bind(this);
		this.init();
	}

	Careers.prototype.init = function () {
		var self = this;
		var root = this.root;
		var canArm = 'IntersectionObserver' in window && !reduceMotion.matches && !this.editor;

		if (this.editor) {
			root.classList.add('is-editor');
		}
		if (canArm) {
			root.classList.add('is-armed');
		}

		if ('IntersectionObserver' in window) {
			// Loops pause whenever no part of the card is on screen.
			this.observer = new window.IntersectionObserver(function (entries) {
				if (!self.root.isConnected) {
					self.destroy();
					return;
				}
				self.visible = entries[entries.length - 1].isIntersecting;
				self.syncPause();
			}, { threshold: 0 });
			this.observer.observe(root);

			/*
			 * The reveal waits until the card's top passes 85% of the viewport.
			 * A margin, not a ratio, so a card taller than the screen reveals too.
			 */
			this.revealer = new window.IntersectionObserver(function (entries) {
				if (!self.root.isConnected) {
					self.destroy();
					return;
				}
				if (entries[entries.length - 1].isIntersecting && !self.revealed) {
					self.revealer.disconnect();
					self.revealer = null;
					self.reveal();
				}
			}, { threshold: 0, rootMargin: '0px 0px -15% 0px' });
			this.revealer.observe(root);
		} else {
			this.visible = true;
			this.reveal();
		}

		// Editor iframes can miss the first intersection: never leave content hidden.
		if (this.editor) {
			this.later('force', function () {
				root.classList.add('is-in');
			}, 1500);
		}

		document.addEventListener('visibilitychange', this.onVisibility);
		this.bindCharacter();
	};

	Careers.prototype.later = function (name, fn, ms) {
		var self = this;
		this.cancel(name);
		this.timers[name] = window.setTimeout(function () {
			delete self.timers[name];
			if (self.alive) {
				fn();
			}
		}, ms);
	};

	Careers.prototype.cancel = function (name) {
		if (this.timers[name]) {
			window.clearTimeout(this.timers[name]);
			delete this.timers[name];
		}
	};

	Careers.prototype.on = function (el, type, fn, opts) {
		if (!el) {
			return;
		}
		el.addEventListener(type, fn, opts || false);
		this.unbinders.push(function () {
			el.removeEventListener(type, fn, opts || false);
		});
	};

	Careers.prototype.onVisibility = function () {
		if (!this.root.isConnected) {
			this.destroy();
			return;
		}
		this.syncPause();
	};

	Careers.prototype.syncPause = function () {
		this.root.classList.toggle('is-off', !this.visible || document.hidden);
	};

	Careers.prototype.reveal = function () {
		var self = this;
		this.revealed = true;
		this.root.classList.add('is-in');

		if (!this.pal || reduceMotion.matches || !window.AvixPal) {
			return;
		}
		// Says hi once it has popped up out of the card, then waves its flag now and then.
		if (!this.editor) {
			this.later('hi', function () {
				self.play('is-hi', 2100);
			}, 1300);
		}
		this.stopEvery = window.AvixPal.every(this.pal, function () {
			if (self.alive && !self.cheering) {
				self.play('is-wave', 1700);
			}
		}, this.every * 0.75, this.every * 1.25);
	};

	Careers.prototype.play = function (cls, ms) {
		if (window.AvixPal && this.pal) {
			window.AvixPal.play(this.pal, cls, ms);
		}
	};

	Careers.prototype.jump = function () {
		if (!this.pal && !this.scene) {
			return;
		}
		this.play('is-jump', 650);
		if (this.scene && !reduceMotion.matches) {
			var scene = this.scene;
			scene.classList.remove('is-bump');
			void scene.offsetWidth;
			scene.classList.add('is-bump');
			this.later('bump', function () {
				scene.classList.remove('is-bump');
			}, 650);
		}
	};

	Careers.prototype.bindCharacter = function () {
		var self = this;
		var pal = this.pal;

		if (this.scene) {
			this.on(this.scene, 'pointerenter', function (e) {
				if (e.pointerType === 'mouse') {
					self.jump();
					self.later('hiAfterJump', function () {
						self.play('is-hi', 2100);
					}, 620);
				}
			});
			// Tap (or click) the sign or the character: a jump.
			this.on(this.scene, 'click', function () {
				self.jump();
			});
		}

		if (!pal) {
			return;
		}

		function cheerOn() {
			self.cheering = true;
			if (window.AvixPal) {
				window.AvixPal.lookAt(pal, null);
			}
			self.play('is-jump', 650);
			pal.classList.add('is-cheer');
		}

		function cheerOff() {
			self.cheering = false;
			pal.classList.remove('is-cheer');
		}

		if (this.cta) {
			this.on(this.cta, 'pointerenter', function (e) {
				if (e.pointerType === 'mouse') {
					cheerOn();
				}
			});
			this.on(this.cta, 'pointerleave', cheerOff);
			// Keyboard focus only: a tap or click also focuses the link, and the
			// cheer would then stick (and stop the waving) until it blurs.
			this.on(this.cta, 'focus', function () {
				var keyboard = true;
				try {
					keyboard = self.cta.matches(':focus-visible');
				} catch (e) {
					keyboard = true;
				}
				if (keyboard) {
					cheerOn();
				}
			});
			this.on(this.cta, 'blur', cheerOff);
		}

		// Glances toward the perk or role being read.
		this.looks.forEach(function (el) {
			self.on(el, 'pointerenter', function (e) {
				if (e.pointerType === 'mouse' && window.AvixPal && !self.cheering) {
					window.AvixPal.lookAt(pal, el);
				}
			});
			self.on(el, 'pointerleave', function () {
				if (window.AvixPal) {
					window.AvixPal.lookAt(pal, null);
				}
			});
			self.on(el, 'focusin', function () {
				if (window.AvixPal && !self.cheering) {
					window.AvixPal.lookAt(pal, el);
				}
			});
			self.on(el, 'focusout', function () {
				if (window.AvixPal) {
					window.AvixPal.lookAt(pal, null);
				}
			});
		});
	};

	Careers.prototype.destroy = function () {
		var name;
		if (!this.alive) {
			return;
		}
		this.alive = false;
		for (name in this.timers) {
			if (Object.prototype.hasOwnProperty.call(this.timers, name)) {
				window.clearTimeout(this.timers[name]);
			}
		}
		this.timers = {};
		if (this.observer) {
			this.observer.disconnect();
		}
		if (this.revealer) {
			this.revealer.disconnect();
			this.revealer = null;
		}
		if (this.stopEvery) {
			this.stopEvery();
		}
		this.unbinders.forEach(function (off) {
			off();
		});
		this.unbinders = [];
		document.removeEventListener('visibilitychange', this.onVisibility);
		if (this.root.__avixCr === this) {
			this.root.__avixCr = null;
		}
	};

	function prune() {
		instances = instances.filter(function (instance) {
			if (instance.root.isConnected) {
				return true;
			}
			instance.destroy();
			return false;
		});
	}

	function mount(root) {
		if (!root || (root.__avixCr && root.__avixCr.alive)) {
			return;
		}
		prune();
		root.__avixCr = new Careers(root);
		instances.push(root.__avixCr);
	}

	function mountAll(scope) {
		var base = scope && scope.querySelectorAll ? scope : document;
		if (base.matches && base.matches(ROOT_SELECTOR)) {
			mount(base);
		}
		Array.prototype.forEach.call(base.querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixCareers = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-careers.default', function ($scope) {
			mountAll($scope && $scope[0] ? $scope[0] : $scope);
		});
	}

	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		hookElementor();
	} else {
		window.addEventListener('elementor/frontend/init', hookElementor);
	}
})(window, document);
