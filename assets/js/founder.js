/*!
 * Avix Digital · Founder
 * Reveals the card once, lets the pixel character peek over the photo and pop
 * up to say hi when the visitor reaches for play, and opens the introduction
 * video in an accessible pop-up (focus trap, Esc, backdrop, focus return).
 * The video is only requested after a click; timers run only on screen.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-fn]';
	var reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };
	var instances = [];
	// Periodic pop-up-and-wave runs at most this often per page view, so it never nags while someone reads.
	var MAX_WAVES = 2;

	function isEditMode() {
		return !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
	}

	function pal() {
		return window.AvixPal || null;
	}

	function Founder(root) {
		var config = {};
		try {
			config = JSON.parse(root.getAttribute('data-avix-fn') || '{}') || {};
		} catch (e) {
			config = {};
		}
		this.root = root;
		this.config = config;
		this.alive = true;
		this.timers = {};
		this.visible = false;
		this.greeted = false;
		this.pointerIn = false;
		this.focusIn = false;
		this.returning = false;
		this.waves = 0;
		this.lastHi = 0;
		this.modal = null;
		this.perch = root.querySelector('[data-fn-perch]');
		this.pal = root.querySelector('[data-fn-pal]');
		this.play = root.querySelector('[data-fn-play]');
		this.stopEvery = null;

		this.onPointerIn = this.onPointerIn.bind(this);
		this.onPointerOut = this.onPointerOut.bind(this);
		this.onFocusIn = this.onFocusIn.bind(this);
		this.onFocusOut = this.onFocusOut.bind(this);
		this.onPlay = this.onPlay.bind(this);
		this.onPlayKey = this.onPlayKey.bind(this);
		this.onKey = this.onKey.bind(this);
		this.onFocus = this.onFocus.bind(this);
		this.close = this.close.bind(this);

		this.init();
	}

	Founder.prototype.later = function (name, fn, ms) {
		var self = this;
		this.cancel(name);
		this.timers[name] = window.setTimeout(function () {
			delete self.timers[name];
			if (self.alive) {
				fn();
			}
		}, ms);
	};

	// Only a real pointer hover or keyboard focus counts, never the focus put back after the pop-up closes.
	Founder.prototype.isEngaged = function () {
		return this.pointerIn || this.focusIn;
	};

	Founder.prototype.cancel = function (name) {
		if (this.timers[name]) {
			window.clearTimeout(this.timers[name]);
			delete this.timers[name];
		}
	};

	Founder.prototype.init = function () {
		var self = this;
		var root = this.root;
		var edit = isEditMode();

		if (edit) {
			root.classList.add('is-editor');
		}

		if ('IntersectionObserver' in window) {
			if (!reduceMotion.matches && !edit) {
				root.classList.add('is-armed');
			}
			this.io = new window.IntersectionObserver(function (entries) {
				// A fast scroll can queue enter and exit together: the newest entry wins.
				self.visible = entries[entries.length - 1].isIntersecting;
				root.classList.toggle('is-off', !self.visible);
				if (self.visible) {
					self.reveal();
				}
			}, { threshold: 0.2 });
			this.io.observe(root);
			// Editor iframes can miss the first intersection.
			if (edit) {
				this.later('force', function () {
					self.reveal();
				}, 1500);
			}
		} else {
			this.reveal();
		}

		if (this.play && this.config.video) {
			// Announced as a button, so it must answer Space as well as Enter.
			this.play.setAttribute('role', 'button');
			this.play.setAttribute('aria-haspopup', 'dialog');
			this.play.addEventListener('click', this.onPlay);
			this.play.addEventListener('keydown', this.onPlayKey);
		}

		if (this.play && this.pal) {
			this.play.addEventListener('pointerenter', this.onPointerIn);
			this.play.addEventListener('pointerleave', this.onPointerOut);
			this.play.addEventListener('focus', this.onFocusIn);
			this.play.addEventListener('blur', this.onFocusOut);
		}

		if (this.pal && pal() && this.config.every > 0 && !edit && !reduceMotion.matches) {
			var ms = this.config.every * 1000;
			this.stopEvery = pal().every(this.pal, function () {
				self.wave();
			}, ms * 0.8, ms * 1.25);
		}
	};

	Founder.prototype.reveal = function () {
		var self = this;
		if (this.root.classList.contains('is-in')) {
			return;
		}
		this.root.classList.add('is-in');
		// The first rise into the peek waits for the card; after that it ducks without delay.
		this.later('settle', function () {
			self.root.classList.add('is-settled');
		}, 1300);
		if (!this.pal || this.greeted || reduceMotion.matches || isEditMode()) {
			return;
		}
		this.greeted = true;
		// The character rises to a peek with the card, then pops up once to say hi.
		this.later('greet', function () {
			if (!self.isEngaged()) {
				self.hi();
				self.later('down', function () {
					self.up(false);
				}, 2300);
			}
		}, 1900);
	};

	Founder.prototype.up = function (on) {
		if (this.perch) {
			this.perch.classList.toggle('is-up', !!on);
		}
	};

	Founder.prototype.hi = function () {
		var now = Date.now();
		if (!pal() || now - this.lastHi < 1800) {
			return;
		}
		this.lastHi = now;
		this.up(true);
		pal().play(this.pal, 'is-hi', 1900);
	};

	Founder.prototype.wave = function () {
		var self = this;
		if (this.isEngaged() || this.modal || !pal()) {
			return;
		}
		this.waves += 1;
		if (this.waves >= MAX_WAVES && this.stopEvery) {
			this.stopEvery();
			this.stopEvery = null;
		}
		this.up(true);
		pal().play(this.pal, 'is-wave', 1700);
		this.later('down', function () {
			self.up(false);
		}, 2000);
	};

	Founder.prototype.onPointerIn = function (event) {
		if (event && 'touch' === event.pointerType) {
			return;
		}
		this.pointerIn = true;
		this.engage();
	};

	Founder.prototype.onPointerOut = function () {
		if (!this.pointerIn) {
			return;
		}
		this.pointerIn = false;
		this.disengage();
	};

	Founder.prototype.onFocusIn = function () {
		// Focus handed back by close() is not the visitor reaching for play.
		if (this.returning) {
			this.returning = false;
			return;
		}
		var keyboard = true;
		try {
			keyboard = this.play.matches(':focus-visible');
		} catch (e) {
			keyboard = true;
		}
		if (!keyboard) {
			return;
		}
		this.focusIn = true;
		this.engage();
	};

	Founder.prototype.onFocusOut = function () {
		this.returning = false;
		if (!this.focusIn) {
			return;
		}
		this.focusIn = false;
		this.disengage();
	};

	Founder.prototype.engage = function () {
		if (!pal() || !this.pal || this.modal) {
			return;
		}
		this.cancel('down');
		this.cancel('greet');
		this.up(true);
		pal().lookAt(this.pal, this.play);
		if (reduceMotion.matches) {
			// No hop or wave: it stands up and shows a still "hi" (pixel-pal.css keeps it visible).
			this.pal.classList.add('is-hi');
			return;
		}
		this.hi();
	};

	Founder.prototype.disengage = function () {
		var self = this;
		if (this.isEngaged()) {
			return;
		}
		if (reduceMotion.matches) {
			if (this.pal) {
				this.pal.classList.remove('is-hi');
			}
			if (pal()) {
				pal().lookAt(this.pal, null);
			}
			this.up(false);
			return;
		}
		this.later('down', function () {
			if (pal()) {
				pal().lookAt(self.pal, null);
			}
			self.up(false);
		}, 900);
	};

	/* ---------- Video pop-up ---------- */

	Founder.prototype.onPlay = function (event) {
		var video = this.config.video;
		if (!video || !video.src || isEditMode()) {
			if (isEditMode()) {
				event.preventDefault();
			}
			return;
		}
		// Cmd/Ctrl/Shift-click keeps the link's own behaviour (new tab or window).
		if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || (event.button && 0 !== event.button)) {
			return;
		}
		event.preventDefault();
		this.open();
	};

	Founder.prototype.onPlayKey = function (event) {
		if (' ' === event.key || 'Spacebar' === event.key) {
			event.preventDefault();
			if (!isEditMode()) {
				this.open();
			}
		}
	};

	Founder.prototype.open = function () {
		var self = this;
		var video = this.config.video;
		if (this.modal) {
			return;
		}
		// PHP always passes translated labels; these only cover a broken config.
		var label = this.config.label || 'Introduction video';
		var modal = document.createElement('div');
		modal.className = 'avix-fn-modal';
		modal.setAttribute('role', 'dialog');
		modal.setAttribute('aria-modal', 'true');
		modal.setAttribute('aria-label', label);

		var backdrop = document.createElement('div');
		backdrop.className = 'avix-fn-modal__backdrop';
		backdrop.addEventListener('click', this.close);

		var box = document.createElement('div');
		box.className = 'avix-fn-modal__box';

		var media;
		if ('file' === video.type) {
			media = document.createElement('video');
			media.setAttribute('controls', '');
			media.setAttribute('autoplay', '');
			media.setAttribute('playsinline', '');
			media.setAttribute('preload', 'auto');
			media.setAttribute('aria-label', label);
			media.src = video.src;
		} else {
			media = document.createElement('iframe');
			media.setAttribute('title', label);
			media.setAttribute('allow', 'autoplay; encrypted-media; picture-in-picture; fullscreen');
			media.setAttribute('allowfullscreen', '');
			media.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
			media.src = video.src;
		}
		box.appendChild(media);

		var closeBtn = document.createElement('button');
		closeBtn.type = 'button';
		closeBtn.className = 'avix-fn-modal__close';
		closeBtn.setAttribute('aria-label', this.config.close || 'Close video');
		closeBtn.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6 6 18"/></svg>';
		closeBtn.addEventListener('click', this.close);

		// Focus guards: the YouTube/Vimeo frame is cross-origin, so Tab presses inside it
		// can't be read. Tabbing out of either end lands on a guard that wraps focus.
		var guardStart = this.guard(function () {
			closeBtn.focus();
		});
		var guardEnd = this.guard(function () {
			media.focus();
		});

		modal.appendChild(guardStart);
		modal.appendChild(backdrop);
		modal.appendChild(box);
		modal.appendChild(closeBtn);
		modal.appendChild(guardEnd);

		// Portal to <body>: the widget is a size container, which would trap position: fixed.
		document.body.appendChild(modal);
		// A classic scrollbar takes up width: keep its gutter while locked so nothing shifts.
		if (window.innerWidth - document.documentElement.clientWidth > 0) {
			document.documentElement.classList.add('avix-fn-gutter');
		}
		document.documentElement.classList.add('avix-fn-lock');
		this.modal = { el: modal, close: closeBtn, media: media };
		void modal.offsetWidth;
		modal.classList.add('is-open');
		closeBtn.focus({ preventScroll: true });
		document.addEventListener('keydown', this.onKey, true);
		document.addEventListener('focusin', this.onFocus, true);
		this.cancel('down');
		this.up(false);
		if (pal()) {
			pal().lookAt(self.pal, null);
		}
		if (this.pal) {
			this.pal.classList.remove('is-hi');
		}
	};

	Founder.prototype.close = function () {
		var self = this;
		var m = this.modal;
		if (!m) {
			return;
		}
		this.modal = null;
		document.removeEventListener('keydown', this.onKey, true);
		document.removeEventListener('focusin', this.onFocus, true);
		// Stop playback right away; the element fades out after.
		if (m.media.tagName === 'VIDEO') {
			m.media.pause();
			m.media.removeAttribute('src');
			m.media.load();
		} else {
			m.media.src = 'about:blank';
		}
		m.el.classList.remove('is-open');
		document.documentElement.classList.remove('avix-fn-lock', 'avix-fn-gutter');
		window.setTimeout(function () {
			if (m.el.parentNode) {
				m.el.parentNode.removeChild(m.el);
			}
		}, reduceMotion.matches ? 0 : 380);
		this.pointerIn = false;
		this.focusIn = false;
		if (this.play && this.alive) {
			this.returning = true;
			this.play.focus({ preventScroll: true });
			this.returning = false;
		}
		if (this.alive && this.pal && pal() && !reduceMotion.matches) {
			this.later('thanks', function () {
				self.up(true);
				// Face forward: a turned head merges with the raised arm into a blob.
				pal().lookAt(self.pal, null);
				pal().play(self.pal, 'is-cheer', 1100);
				self.later('down', function () {
					self.up(false);
				}, 1500);
			}, 300);
		}
	};

	Founder.prototype.onKey = function (event) {
		var m = this.modal;
		if (!m) {
			return;
		}
		if ('Escape' === event.key || 'Esc' === event.key) {
			event.preventDefault();
			this.close();
			return;
		}
	};

	Founder.prototype.guard = function (onFocus) {
		var el = document.createElement('span');
		el.className = 'avix-fn-modal__guard';
		el.setAttribute('tabindex', '0');
		el.addEventListener('focus', onFocus);
		return el;
	};

	// Anything that tries to focus the page behind the pop-up is sent back to it.
	Founder.prototype.onFocus = function (event) {
		if (this.modal && !this.modal.el.contains(event.target)) {
			this.modal.close.focus({ preventScroll: true });
		}
	};

	Founder.prototype.destroy = function () {
		var name;
		this.alive = false;
		for (name in this.timers) {
			if (Object.prototype.hasOwnProperty.call(this.timers, name)) {
				window.clearTimeout(this.timers[name]);
			}
		}
		this.timers = {};
		if (this.io) {
			this.io.disconnect();
		}
		if (this.stopEvery) {
			this.stopEvery();
		}
		if (this.modal) {
			this.close();
		}
		if (this.play) {
			this.play.removeEventListener('click', this.onPlay);
			this.play.removeEventListener('keydown', this.onPlayKey);
			this.play.removeEventListener('pointerenter', this.onPointerIn);
			this.play.removeEventListener('pointerleave', this.onPointerOut);
			this.play.removeEventListener('focus', this.onFocusIn);
			this.play.removeEventListener('blur', this.onFocusOut);
		}
		this.root.__avixFn = null;
	};

	/* ---------- Mounting ---------- */

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
		if (!root || (root.__avixFn && root.__avixFn.alive)) {
			return;
		}
		prune();
		root.__avixFn = new Founder(root);
		instances.push(root.__avixFn);
	}

	function mountAll(scope) {
		var base = scope && scope.querySelectorAll ? scope : document;
		if (base.matches && base.matches(ROOT_SELECTOR)) {
			mount(base);
		}
		Array.prototype.forEach.call(base.querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixFounder = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-founder.default', function ($scope) {
			mountAll($scope && $scope[0] ? $scope[0] : $scope);
		});
	}

	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		hookElementor();
	} else {
		window.addEventListener('elementor/frontend/init', hookElementor);
	}
})(window, document);
