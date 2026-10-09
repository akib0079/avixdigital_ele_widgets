/*!
 * Avix Digital · Service Index
 * Reveals the header and each row as it scrolls in, and on desktops shows one
 * preview card inside the hovered row, docked in the column the visitor is not
 * reading (eased in requestAnimationFrame, only while it is moving). The card
 * is sized once per list in layout, never scaled, so its label stays readable.
 * The pixel character rides on top: it glances where the mouse goes and walks
 * to keep its balance when the card swings fast. Sleeps off screen and in
 * hidden tabs.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-si]';
	var instances = [];
	var mq = function (query) {
		return window.matchMedia ? window.matchMedia(query) : { matches: false };
	};
	var reduceMotion = mq('(prefers-reduced-motion: reduce)');
	var finePointer = mq('(hover: hover) and (pointer: fine)');

	// Tuning: how fast the card catches the cursor, how far it leans, and the
	// speed (px/s) at which the character starts walking to keep up.
	var FOLLOW = 11;
	var LEAN_PER_PX = 1 / 70;
	var MAX_LEAN = 6;
	var WALK_SPEED = 260;
	var SLIDE = 0.08;
	var REVEAL_STEP = 80;
	// The card never gets shorter than this; on a shorter row it overhangs the
	// row's bottom a little (it ignores the pointer) instead of shrinking.
	var MIN_H = 190;

	function clamp(value, min, max) {
		return value < min ? min : value > max ? max : value;
	}

	function now() {
		return window.performance && window.performance.now ? window.performance.now() : Date.now();
	}

	function isEditMode() {
		return !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
	}

	function Index(root) {
		var config = {};
		try {
			config = JSON.parse(root.getAttribute('data-avix-si') || '{}') || {};
		} catch (error) {
			config = {};
		}

		this.root = root;
		this.alive = true;
		this.config = {
			preview: config.preview !== false,
			tilt: config.tilt !== false,
			pal: config.pal !== false,
			greet: config.greet !== false
		};
		this.editor = isEditMode();
		this.list = root.querySelector('[data-si-list]');
		this.rows = Array.prototype.slice.call(root.querySelectorAll('[data-si-row]'));
		this.preview = root.querySelector('[data-si-preview]');
		this.swing = root.querySelector('[data-si-swing]');
		this.inner = root.querySelector('.avix-si__inner');
		this.head = root.querySelector('.avix-si__head');
		this.rider = root.querySelector('[data-si-rider]');
		this.pal = root.querySelector('[data-si-pal]');
		this.shots = Array.prototype.slice.call(root.querySelectorAll('[data-si-shot]'));
		this.foot = root.querySelector('[data-si-foot]');
		this.footPal = root.querySelector('[data-si-foot-pal]');

		this.observers = [];
		this.timers = [];
		this.stops = [];
		this.layoutOk = null;
		this.frame = 0;
		this.p = {
			cx: 0, cy: 0, x: 0, y: 0, tx: 0, ty: 0, vx: 0, tilt: 0,
			palX: 0, palV: 0, w: 0, h: 0, ox: 0, oy: 0, last: 0, lookAt: 0,
			rowTop: 0, rowH: 0, padTop: 0, nameL: 0, descL: 0, descR: 0, gap: 0, colMax: 0, needS: 0, needH: 0, floor: 0,
			zone: -1, scrolled: false, shown: false, index: -1, greeted: false
		};
		this.palState = { walk: false, faceL: false, look: '' };

		this.tick = this.tick.bind(this);
		this.onMove = this.onMove.bind(this);
		this.onLeave = this.onLeave.bind(this);
		this.onScroll = this.onScroll.bind(this);
		this.onResize = this.onResize.bind(this);
		this.onVisibility = this.onVisibility.bind(this);
		this.onFootHello = this.onFootHello.bind(this);

		this.init();
	}

	Index.prototype.later = function (fn, ms) {
		var self = this;
		var id = window.setTimeout(function () {
			var at = self.timers.indexOf(id);
			if (at > -1) {
				self.timers.splice(at, 1);
			}
			if (self.alive) {
				fn();
			}
		}, ms);
		this.timers.push(id);
	};

	Index.prototype.observe = function (target, options, callback) {
		if (!target || !('IntersectionObserver' in window)) {
			return null;
		}
		var observer = new window.IntersectionObserver(callback, options);
		observer.observe(target);
		this.observers.push(observer);
		return observer;
	};

	Index.prototype.init = function () {
		var self = this;
		var root = this.root;

		if (this.editor) {
			root.classList.add('is-editor');
		}

		this.setupReveal();

		// Pause the character's loops while off screen or in a hidden tab.
		this.onScreen = true;
		this.observe(root, { threshold: 0 }, function (entries) {
			self.onScreen = entries[0].isIntersecting;
			self.onVisibility();
		});
		document.addEventListener('visibilitychange', this.onVisibility);

		this.setupFoot();

		if (this.config.preview && this.preview && this.list && this.rows.length && !this.editor) {
			this.list.addEventListener('pointermove', this.onMove, { passive: true });
			this.list.addEventListener('pointerleave', this.onLeave);
			window.addEventListener('scroll', this.onScroll, { passive: true });
			window.addEventListener('resize', this.onResize);
			window.addEventListener('blur', this.onLeave);
		}
	};

	/*
	 * Reveal: content stays visible unless everything needed to show it again
	 * is here. Each header part and row is watched on its own (a long list can
	 * be many screens tall, so a ratio of the whole section might never be
	 * reached) and fades in as it enters; items entering together stagger.
	 */
	Index.prototype.setupReveal = function () {
		var self = this;
		if (!('IntersectionObserver' in window) || reduceMotion.matches || this.editor) {
			return;
		}
		var items = Array.prototype.slice.call(this.root.querySelectorAll('[data-si-reveal], [data-si-row]'));
		if (!items.length) {
			return;
		}
		var fired = false;
		var show = function (el, delay) {
			el.style.setProperty('--si-d', delay + 'ms');
			el.classList.add('is-in');
		};
		var reveal = new window.IntersectionObserver(function (entries) {
			fired = true;
			var n = 0;
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					reveal.unobserve(entry.target);
					show(entry.target, n++ * REVEAL_STEP);
				}
			});
		}, { threshold: 0, rootMargin: '0px 0px -10% 0px' });
		this.root.classList.add('is-armed');
		items.forEach(function (el) {
			reveal.observe(el);
		});
		this.observers.push(reveal);
		// Safety net: never leave the list hidden if the observer stays silent.
		this.later(function () {
			if (!fired) {
				reveal.disconnect();
				items.forEach(function (el) {
					show(el, 0);
				});
			}
		}, 2500);
	};

	// Editor deletes leave no re-mount behind: clean up on the next event.
	Index.prototype.gone = function () {
		if (this.root.isConnected) {
			return false;
		}
		this.destroy();
		return true;
	};

	Index.prototype.onVisibility = function () {
		if (this.gone()) {
			return;
		}
		var off = !this.onScreen || document.hidden;
		this.root.classList.toggle('is-off', off);
		if (off) {
			this.hide();
		}
	};

	/* ---------- Footer character ---------- */

	Index.prototype.setupFoot = function () {
		var self = this;
		var AvixPal = window.AvixPal;
		if (!this.foot || !this.footPal || !AvixPal) {
			return;
		}
		this.foot.addEventListener('pointerenter', this.onFootHello);
		this.foot.addEventListener('focusin', this.onFootHello);
		if (this.editor) {
			return;
		}
		// Says hi once when the note comes into view, then waves now and then.
		var hello = this.observe(this.foot, { threshold: 0.6 }, function (entries) {
			if (entries[0].isIntersecting) {
				hello.disconnect();
				self.later(self.onFootHello, 350);
			}
		});
		this.stops.push(AvixPal.every(this.footPal, function () {
			if (!self.root.classList.contains('is-riding')) {
				AvixPal.play(self.footPal, 'is-wave', 1700);
			}
		}, 8000, 14000));
	};

	Index.prototype.onFootHello = function () {
		if (window.AvixPal && this.footPal && !this.footPal.classList.contains('is-hi') && !this.root.classList.contains('is-riding')) {
			window.AvixPal.play(this.footPal, 'is-hi', 1900);
		}
	};

	/* ---------- Preview card ---------- */

	// The card only exists in the row layout (CSS hides it for cards).
	Index.prototype.usable = function () {
		if (this.layoutOk === null) {
			this.layoutOk = finePointer.matches && window.getComputedStyle(this.preview).display !== 'none';
		}
		return this.layoutOk;
	};

	Index.prototype.onMove = function (event) {
		if (event.pointerType && event.pointerType !== 'mouse' && event.pointerType !== 'pen') {
			return;
		}
		this.p.cx = event.clientX;
		this.p.cy = event.clientY;
		if (this.pick(event.target)) {
			this.wake();
		}
	};

	Index.prototype.onLeave = function () {
		this.hide();
	};

	// Page scrolls under a still mouse: the next frame finds the row now under the cursor.
	Index.prototype.onScroll = function () {
		if (this.gone() || !this.p.shown) {
			return;
		}
		this.p.scrolled = true;
		this.wake();
	};

	Index.prototype.onResize = function () {
		if (this.gone()) {
			return;
		}
		this.layoutOk = null;
		this.hide();
	};

	// Shows the preview for the row under the pointer; rows without a link get
	// none (nothing to click, so nothing should suggest it). Returns true while shown.
	Index.prototype.pick = function (target) {
		var row = target && target.closest ? target.closest('[data-si-row]') : null;
		if (!row || !row.classList.contains('has-link') || !this.list.contains(row) || !this.usable() || this.root.classList.contains('is-off')) {
			this.hide();
			return false;
		}
		var index = this.rows.indexOf(row);
		if (!this.p.shown) {
			this.measure();
		}
		if (index !== this.p.index || !this.p.shown) {
			this.setActive(index);
		}
		if (!this.p.shown) {
			this.show();
		}
		return true;
	};

	Index.prototype.setActive = function (index) {
		var p = this.p;
		var row = this.rows[index];
		p.index = index;
		// The card is filed inside its row: remember where the row sits in the list.
		p.rowTop = row.offsetTop;
		p.rowH = row.offsetHeight;
		for (var i = 0; i < this.shots.length; i++) {
			this.shots[i].classList.toggle('is-active', i === index);
		}
	};

	// Layout reads and the card size, once per appearance: the column edges,
	// how far up the character (and its "hi") may reach without leaving the
	// section, and one card size for every row of the list.
	Index.prototype.measure = function () {
		var p = this.p;
		var row = this.rows[0];
		var style = window.getComputedStyle(row);
		var vars = window.getComputedStyle(this.preview);
		var body = row.querySelector('.avix-si__body');
		var name = row.querySelector('.avix-si__name');
		var width = this.list.clientWidth;
		var listTop = this.list.offsetTop + (this.inner ? this.inner.offsetTop : 0);
		var floor = -listTop;
		var maxW = parseFloat(vars.getPropertyValue('--si-preview-w')) || 360;
		var ratio = String(vars.getPropertyValue('--si-preview-ratio') || '18 / 13').split('/');
		ratio = (parseFloat(ratio[0]) || 18) / (parseFloat(ratio[1]) || 13);
		var at = this.list.getBoundingClientRect();
		p.padTop = parseFloat(style.paddingTop) || 0;
		// The list may sit on a fractional pixel: paint() rounds against it.
		p.ox = at.left - Math.floor(at.left);
		p.oy = at.top - Math.floor(at.top);
		// Column edges: [number] [name] | [text] [arrow]. The card docks in the
		// text column or the name column, never over the round arrow.
		p.gap = parseFloat(style.columnGap) || 0;
		p.nameL = name ? name.offsetLeft : 0;
		p.descL = body ? body.offsetLeft : width / 2;
		p.descR = width - 64 - p.gap;
		p.colMax = Math.max(120, Math.min(p.descR - p.descL, p.descL - p.gap - p.nameL));
		// Height above the card taken by the rider alone, and with its pixel "hi".
		p.needS = this.pal ? this.pal.offsetWidth * 1.2 + 4 : 0;
		p.needH = this.pal ? this.pal.offsetWidth * 1.85 + 6 : 0;
		if (this.head) {
			floor = Math.max(floor, this.head.offsetTop + this.head.offsetHeight - this.list.offsetTop);
		}
		p.floor = floor + 1;

		// Width: the Card width setting, capped by the column it docks in (even,
		// so the centred rider lands on whole pixels). Height: the card shape,
		// cropped to the shortest row so it is the same size on every row.
		p.w = Math.max(160, Math.round(Math.min(maxW, p.colMax) / 2) * 2);
		var room = Infinity;
		for (var i = 0; i < this.rows.length; i++) {
			var top = this.rows[i].offsetTop;
			room = Math.min(room, top + this.rows[i].offsetHeight - 8 - this.cardTop(top));
		}
		p.h = Math.round(Math.max(MIN_H, Math.min(p.w / ratio, room)));
		this.preview.style.setProperty('--si-card-w', p.w + 'px');
		this.preview.style.setProperty('--si-card-h', p.h + 'px');
		this.preview.classList.add('is-sized');
	};

	// The card's top edge for a row: just under the hairline, with the rider
	// (and its "hi") clear of the row above but its feet never below this row's
	// text top, and never above the header or the section top.
	Index.prototype.cardTop = function (rowTop) {
		var p = this.p;
		return Math.round(Math.max(rowTop + 8, Math.min(rowTop - p.padTop + p.needH, rowTop + p.padTop - 2), p.floor + p.needS));
	};

	Index.prototype.show = function () {
		var p = this.p;
		var rect = this.list.getBoundingClientRect();
		p.zone = -1;
		this.target(rect);
		// Appear right beside the cursor instead of flying in from a corner.
		p.x = p.tx;
		p.y = p.ty;
		p.vx = 0;
		p.tilt = 0;
		p.palX = 0;
		p.palV = 0;
		p.shown = true;
		this.paint();
		this.root.classList.add('is-previewing');
		if (this.pal) {
			this.root.classList.add('is-riding');
			// Greet only where the "hi" has room; otherwise wait for a roomier row.
			var hiOk = p.ty - p.needH >= Math.max(p.floor, p.rowTop - p.padTop) - 1;
			if (this.config.greet && !p.greeted && hiOk && window.AvixPal) {
				p.greeted = true;
				var pal = this.pal;
				this.later(function () {
					window.AvixPal.play(pal, 'is-hi', 1900);
				}, 280);
			}
		}
	};

	Index.prototype.hide = function () {
		if (!this.p.shown) {
			return;
		}
		this.p.shown = false;
		this.root.classList.remove('is-previewing', 'is-riding');
		this.setPal(false, false, '');
		if (this.frame) {
			window.cancelAnimationFrame(this.frame);
			this.frame = 0;
		}
	};

	/*
	 * Where the card goes. Vertically it is locked to the hovered row (top edge
	 * just under the hairline, the rider standing in the row's empty top
	 * padding). Horizontally it docks in the column the visitor is NOT reading:
	 * over the text column while the cursor is on the number or name (sliding
	 * in step with it), over the name column while it is on the text, and at
	 * the right end of the text column, just left of the round arrow, while it
	 * is on the arrow, so the name of what is about to be clicked stays in
	 * view. It never sits under the cursor or over the arrow.
	 */
	Index.prototype.target = function (rect) {
		var p = this.p;
		var cx = p.cx - rect.left;
		var w = p.w;
		var split = p.descL - p.gap / 2;
		var edge = p.descR + p.gap / 2;
		// Zones: 0 name side, 1 text, 2 arrow. Hysteresis (about 40px in all)
		// around each edge keeps the card from flickering between sides.
		var slack = Math.max(4, p.gap / 2 - 4);
		var raw = cx < split ? 0 : cx < edge ? 1 : 2;
		var zone = p.zone;
		if (zone < 0 || Math.abs(raw - zone) > 1) {
			zone = raw;
		} else if (raw !== zone) {
			if (Math.max(raw, zone) === 1 ? Math.abs(cx - split) > slack : (raw === 2 ? cx > edge + 12 : cx < p.descR + 4)) {
				zone = raw;
			}
		}
		p.zone = zone;
		p.ty = this.cardTop(p.rowTop);

		var lo;
		var hi;
		var t;
		if (zone === 1) {
			lo = p.nameL;
			hi = p.descL - p.gap - w;
			t = (cx - p.descL) / Math.max(1, p.descR - p.descL);
		} else {
			lo = p.descL;
			hi = p.descR - w;
			t = zone === 2 ? 1 : (cx - p.nameL) / Math.max(1, split - p.nameL);
		}
		p.tx = hi > lo ? lo + (hi - lo) * clamp(t, 0, 1) : Math.max(0, hi);
	};

	Index.prototype.wake = function () {
		if (!this.frame && this.alive) {
			this.p.last = now();
			this.frame = window.requestAnimationFrame(this.tick);
		}
	};

	Index.prototype.tick = function (time) {
		this.frame = 0;
		var p = this.p;
		if (!this.alive || !p.shown) {
			return;
		}
		var dt = clamp((time - p.last) / 1000, 0.001, 0.05);
		p.last = time;
		if (p.scrolled) {
			p.scrolled = false;
			if (!this.pick(document.elementFromPoint(p.cx, p.cy))) {
				return;
			}
		}
		this.target(this.list.getBoundingClientRect());

		var still = reduceMotion.matches;
		if (still) {
			p.x = p.tx;
			p.y = p.ty;
			p.vx = 0;
		} else {
			// dt-based easing keeps 120 Hz screens in step with 60 Hz ones.
			var k = 1 - Math.exp(-dt * FOLLOW);
			var nextX = p.x + (p.tx - p.x) * k;
			p.vx += ((nextX - p.x) / dt - p.vx) * (1 - Math.exp(-dt * 14));
			p.x = nextX;
			p.y += (p.ty - p.y) * k;
		}

		var lean = this.config.tilt && !still ? clamp(p.vx * LEAN_PER_PX, -MAX_LEAN, MAX_LEAN) : 0;
		p.tilt += (lean - p.tilt) * (1 - Math.exp(-dt * 10));

		// The character slides back when the card speeds off, then walks to the middle.
		var range = p.w * 0.34;
		var slideTo = still ? 0 : clamp(-p.vx * SLIDE, -range, range);
		var before = p.palX;
		p.palX += (slideTo - p.palX) * (1 - Math.exp(-dt * 3.2));
		p.palV = (p.palX - before) / dt;

		this.paint();

		var fast = Math.abs(p.vx) > WALK_SPEED;
		var walking = !still && (fast || Math.abs(p.palV) > 30);
		// Walking shows a profile; standing still it faces the visitor again.
		var faceL = walking ? (fast ? p.vx < 0 : p.palV < 0) : false;
		var look = this.palState.look;
		if (walking) {
			look = '';
		} else if (Math.abs(p.vx) > 30) {
			look = p.vx < 0 ? 'l' : 'r';
			p.lookAt = time;
		} else if (time - p.lookAt > 900) {
			look = '';
		}
		this.setPal(walking, faceL, look);

		var busy = Math.abs(p.tx - p.x) > 0.3 || Math.abs(p.ty - p.y) > 0.3 || Math.abs(p.tilt) > 0.03 ||
			Math.abs(p.palX) > 0.4 || walking || look !== '';
		if (busy) {
			this.frame = window.requestAnimationFrame(this.tick);
		} else if (p.palX !== 0) {
			p.palX = 0;
			this.paint();
		}
	};

	Index.prototype.paint = function () {
		var p = this.p;
		// Whole pixels, so the card edge and the sprite on it stay sharp at rest.
		this.preview.style.transform = 'translate3d(' + (Math.round(p.x + p.ox) - p.ox).toFixed(2) + 'px,' + (Math.round(p.y + p.oy) - p.oy).toFixed(2) + 'px,0)';
		var tilted = Math.abs(p.tilt) > 0.01;
		this.swing.style.transform = tilted ? 'rotate(' + p.tilt.toFixed(2) + 'deg)' : '';
		if (this.rider) {
			// Whole pixels, and counter-rotated so the sprite stays upright and
			// crisp while it balances on the leaning card.
			this.rider.style.transform = 'translate3d(' + Math.round(p.palX) + 'px,0,0)' +
				(tilted ? ' rotate(' + (-p.tilt).toFixed(2) + 'deg)' : '');
		}
	};

	Index.prototype.setPal = function (walk, faceL, look) {
		var pal = this.pal;
		var state = this.palState;
		if (!pal) {
			return;
		}
		if (state.walk !== walk) {
			state.walk = walk;
			pal.classList.toggle('is-walk', walk);
		}
		if (state.faceL !== faceL) {
			state.faceL = faceL;
			pal.classList.toggle('is-face-l', faceL);
		}
		if (state.look !== look) {
			state.look = look;
			pal.classList.toggle('is-look-l', look === 'l');
			pal.classList.toggle('is-look-r', look === 'r');
		}
	};

	Index.prototype.destroy = function () {
		if (!this.alive) {
			return;
		}
		this.alive = false;
		if (this.frame) {
			window.cancelAnimationFrame(this.frame);
		}
		this.timers.forEach(function (id) {
			window.clearTimeout(id);
		});
		this.observers.forEach(function (observer) {
			observer.disconnect();
		});
		this.stops.forEach(function (stop) {
			stop();
		});
		document.removeEventListener('visibilitychange', this.onVisibility);
		if (this.list) {
			this.list.removeEventListener('pointermove', this.onMove);
			this.list.removeEventListener('pointerleave', this.onLeave);
		}
		if (this.foot) {
			this.foot.removeEventListener('pointerenter', this.onFootHello);
			this.foot.removeEventListener('focusin', this.onFootHello);
		}
		window.removeEventListener('scroll', this.onScroll);
		window.removeEventListener('resize', this.onResize);
		window.removeEventListener('blur', this.onLeave);
		var index = instances.indexOf(this);
		if (index > -1) {
			instances.splice(index, 1);
		}
	};

	function mount(root) {
		if (!root || (root.__avixSi && root.__avixSi.alive)) {
			return;
		}
		// Editor re-renders replace the DOM; drop instances whose root is gone.
		instances.slice().forEach(function (instance) {
			if (!instance.root.isConnected) {
				instance.destroy();
			}
		});
		var instance = new Index(root);
		root.__avixSi = instance;
		instances.push(instance);
	}

	function mountAll(scope) {
		var base = scope && scope.querySelectorAll ? scope : document;
		if (base.matches && base.matches(ROOT_SELECTOR)) {
			mount(base);
		}
		Array.prototype.forEach.call(base.querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixServiceIndex = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-service-index.default', function ($scope) {
			mountAll($scope && $scope[0] ? $scope[0] : $scope);
		});
	}

	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		hookElementor();
	} else {
		window.addEventListener('elementor/frontend/init', hookElementor);
	}
})(window, document);
