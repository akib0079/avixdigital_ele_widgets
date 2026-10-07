/*!
 * Avix Digital · Values
 * Each card rises as it scrolls into view and its pixel icon builds itself.
 * The pixel character drops onto the first card once that card is well in
 * view, then moves to whichever card is hovered or focused and goes home
 * afterwards (phones: it stays on the first card and waves on a real tap).
 * Within a row it hops in a short arc that stays below the text above; to
 * another row it pops out and back in on the new card, so it never passes in
 * front of text. Transform and opacity only, one rAF loop that runs only
 * during a move, and nothing ticks while off screen or in a hidden tab.
 * Side layout: the cards are rows beside a sticky text column; the character
 * hops between rows while the columns sit side by side, and Elementor wrappers
 * whose overflow would break the sticky column are switched to clip.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-vl]';
	var reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };
	var BUILD_MS = 900;
	var HOME_AFTER_MS = 1100;
	var TAP_SLOP = 10;
	var TAP_MS = 500;
	var HOP_CLEAR = 8;
	var DROP_MS = 420;
	var LAND_MS = 300;
	var POP_OUT_MS = 150;
	var POP_GAP_MS = 60;
	var POP_IN_MS = 240;
	var STICK_ROOM = 40;
	var ELEMENTOR_WRAPPERS = '.elementor-element, .e-con, .e-con-inner, .elementor-container, .elementor-column, .elementor-widget-wrap, .elementor-widget-container, .elementor-section';
	var instances = [];

	function isEditMode() {
		return !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
	}

	function easeInOut(t) {
		return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
	}

	function Values(root) {
		var config = {};
		try {
			config = JSON.parse(root.getAttribute('data-avix-vl') || '{}') || {};
		} catch (error) {
			config = {};
		}

		this.root = root;
		this.config = config;
		this.alive = true;
		this.board = root.querySelector('[data-vl-board]');
		this.head = root.querySelector('.avix-vl__head');
		this.cards = Array.prototype.slice.call(root.querySelectorAll('[data-vl-card]'));
		this.items = this.cards.map(function (card) {
			return card.parentNode;
		});
		this.revealed = [];
		this.dropped = false;
		this.inAt = 0;
		this.handlers = [];
		this.mover = root.querySelector('[data-vl-mover]');
		this.pal = this.mover ? this.mover.querySelector('[data-avix-pal]') : null;
		this.editor = isEditMode();
		this.motion = !reduceMotion.matches;
		this.current = 0;
		this.hovered = -1;
		this.pos = null;
		this.frame = 0;
		this.timers = {};
		this.observers = [];
		this.stopWave = null;
		this.visible = true;
		this.press = null;
		this.side = !!config.side;
		this.sticky = this.side && !!config.sticky;
		this.clipped = [];

		this.onResize = this.onResize.bind(this);
		this.onStickResize = this.onStickResize.bind(this);
		this.onVisibility = this.onVisibility.bind(this);
		this.onLeave = this.onLeave.bind(this);
		this.onFocusOut = this.onFocusOut.bind(this);

		this.init();
	}

	Values.prototype.init = function () {
		var self = this;
		var armed = 'IntersectionObserver' in window && this.motion && !this.editor;

		if (this.pal) {
			this.root.classList.add('is-js');
			this.place();
		}

		if (armed) {
			this.root.classList.add('is-armed');
			if (this.config.build) {
				this.cards.forEach(function (card) {
					card.classList.add('is-pending');
				});
			}
			if (this.mover) {
				this.mover.classList.add('is-wait');
			}
		}

		if ('IntersectionObserver' in window) {
			// The section as a whole only pauses the character's loops.
			var io = new window.IntersectionObserver(function (entries) {
				if (!self.check()) {
					return;
				}
				// Batched entries: the newest one is the current state.
				self.visible = entries[entries.length - 1].isIntersecting;
				self.onVisibility();
			}, { threshold: 0 });
			io.observe(this.root);
			this.observers.push(io);
		}
		if (armed) {
			this.observeItems();
		}
		document.addEventListener('visibilitychange', this.onVisibility);

		if (this.sticky && !this.editor) {
			this.releaseOverflowAncestors();
			this.updateStick();
			window.addEventListener('resize', this.onStickResize, { passive: true });
			window.addEventListener('load', this.onStickResize);
		}

		if (!this.pal) {
			this.bindCards();
			return;
		}

		if ('ResizeObserver' in window && this.board) {
			// The mover too: a new Character size changes its box, not the board's.
			var ro = new window.ResizeObserver(this.onResize);
			ro.observe(this.board);
			ro.observe(this.mover);
			this.observers.push(ro);
		}
		window.addEventListener('resize', this.onResize, { passive: true });
		window.addEventListener('load', this.onResize);

		if (this.config.wave && this.motion && !this.editor && window.AvixPal) {
			this.stopWave = window.AvixPal.every(this.pal, function () {
				if (!self.frame && self.hovered < 0 && self.current === 0 && self.check()) {
					window.AvixPal.play(self.pal, 'is-wave', 1700);
				}
			}, 6000, 11000);
		}

		this.bindCards();
	};

	/* ---------- Helpers ---------- */

	// Off screen or in a hidden tab: the character's CSS loops pause.
	Values.prototype.onVisibility = function () {
		if (this.check()) {
			this.root.classList.toggle('is-off', document.hidden || !this.visible);
		}
	};

	// Listeners that destroy() removes again.
	Values.prototype.listen = function (el, type, fn, options) {
		el.addEventListener(type, fn, options);
		this.handlers.push([el, type, fn, options]);
	};

	Values.prototype.later = function (name, ms, fn) {
		var self = this;
		this.cancel(name);
		this.timers[name] = window.setTimeout(function () {
			delete self.timers[name];
			if (self.check()) {
				fn();
			}
		}, ms);
	};

	Values.prototype.cancel = function (name) {
		if (this.timers[name]) {
			window.clearTimeout(this.timers[name]);
			delete this.timers[name];
		}
	};

	// Restartable state class: remove, force a reflow, add, remove after ms.
	Values.prototype.play = function (el, className, ms) {
		var name = className + ':' + (el === this.mover ? 'm' : this.cards.indexOf(el));
		el.classList.remove(className);
		void el.offsetWidth;
		el.classList.add(className);
		this.later(name, ms, function () {
			el.classList.remove(className);
			if (className === 'is-build') {
				el.__vlBuilding = false;
			}
		});
	};

	// Editor re-renders replace the DOM: an instance whose root is gone cleans up.
	Values.prototype.check = function () {
		if (this.alive && !this.root.isConnected) {
			this.destroy();
		}
		return this.alive;
	};

	/* ---------- Side layout: sticky text column ---------- */

	// Sticky breaks under overflow:hidden ancestors. Elementor wrappers are
	// flex/grid boxes, so swapping hidden for clip keeps the clipping without
	// creating a scroll container. Theme wrappers are only reported.
	Values.prototype.releaseOverflowAncestors = function () {
		for (var node = this.root.parentElement; node && node !== document.body && node !== document.documentElement; node = node.parentElement) {
			var style = window.getComputedStyle(node);
			if (!/(hidden|auto|scroll)/.test(style.overflowX + style.overflowY)) {
				continue;
			}
			if (!/(auto|scroll)/.test(style.overflowX + style.overflowY) && node.matches && node.matches(ELEMENTOR_WRAPPERS)) {
				node.style.overflow = 'clip';
				this.clipped.push(node);
			} else if (window.console && !this.warned) {
				this.warned = true;
				window.console.warn('[Avix Values] An ancestor has overflow "' + style.overflowX + '/' + style.overflowY + '", which stops the sticky text column. Change it to "clip" or "visible".', node);
			}
		}
	};

	// A text column taller than the screen would hide its end: it scrolls
	// normally instead. Measured with the sticky offset applied (resize only).
	Values.prototype.updateStick = function () {
		if (!this.head) {
			return;
		}
		this.root.classList.remove('is-unstick');
		// Measured against the offset used while the Smart Header shows (the
		// larger one), even if it is hidden right now.
		var shown = (parseFloat(window.getComputedStyle(document.documentElement).getPropertyValue('--avix-header-h')) || 0) + (document.body.classList.contains('admin-bar') ? 72 : 40);
		var top = Math.max(parseFloat(window.getComputedStyle(this.head).top) || 0, shown);
		var room = window.innerHeight - Math.max(top, STICK_ROOM) - STICK_ROOM;
		this.root.classList.toggle('is-unstick', this.head.offsetHeight > room);
	};

	Values.prototype.onStickResize = function () {
		var self = this;
		if (this.stickFrame || !this.check()) {
			return;
		}
		this.stickFrame = window.requestAnimationFrame(function () {
			self.stickFrame = 0;
			if (self.check()) {
				self.updateStick();
			}
		});
	};

	// Side layout with the text column beside the cards (not stacked on top).
	Values.prototype.sideBySide = function () {
		return !!(this.head && this.board && this.board.offsetLeft >= this.head.offsetLeft + this.head.offsetWidth - 1);
	};

	/* ---------- Reveal ---------- */

	// Every card is observed on its own, so each one rises (and builds its
	// icon) as it actually enters the screen. Cards that enter together
	// stagger by 90ms.
	Values.prototype.observeItems = function () {
		var self = this;
		var io = new window.IntersectionObserver(function (entries) {
			if (!self.check()) {
				return;
			}
			var batch = [];
			var drop = false;
			entries.forEach(function (entry) {
				var i = self.items.indexOf(entry.target);
				if (i < 0 || !entry.isIntersecting) {
					return;
				}
				var view = entry.rootBounds ? entry.rootBounds.height : window.innerHeight;
				var seen = entry.intersectionRect.height;
				if (!self.revealed[i] && (entry.intersectionRatio >= 0.35 || seen >= view * 0.4)) {
					batch.push(i);
				}
				// The character drops in once the first card is well in view.
				if (i === 0 && (entry.intersectionRatio >= 0.6 || seen >= view * 0.5)) {
					drop = true;
				}
			});
			batch.sort(function (a, b) {
				return a - b;
			});
			batch.forEach(function (i, k) {
				self.revealItem(i, k);
			});
			if (drop) {
				self.drop();
			}
			if (self.dropped && self.revealed.filter(Boolean).length === self.items.length) {
				io.disconnect();
			}
		}, { threshold: [0, 0.1, 0.2, 0.35, 0.5, 0.6, 0.8, 1] });
		this.items.forEach(function (item) {
			io.observe(item);
		});
		this.observers.push(io);
		if (!this.mover) {
			this.dropped = true;
		}
	};

	Values.prototype.revealItem = function (i, k) {
		var self = this;
		var item = this.items[i];
		this.revealed[i] = true;
		if (i === 0) {
			this.inAt = Date.now();
		}
		item.style.setProperty('--k', String(k));
		item.classList.add('is-in');
		if (this.config.build) {
			this.later('reveal-' + i, k * 90 + 260, function () {
				self.build(i);
			});
		}
	};

	Values.prototype.drop = function () {
		var self = this;
		if (this.dropped || !this.revealed[0]) {
			return;
		}
		this.dropped = true;
		// Let the first card rise most of the way first.
		var wait = Math.max(0, this.inAt + 480 - Date.now());
		this.later('drop', wait, function () {
			var mover = self.mover;
			if (!mover.classList.contains('is-wait')) {
				return;
			}
			self.place();
			// Fall a few whole sprite units, never further than the free space
			// above the seat, so it never passes in front of the text above.
			var unit = Math.max(1, mover.offsetWidth / 10);
			var units = Math.max(2, Math.min(6, Math.floor(self.headroom(self.current, self.pos.y) / unit)));
			mover.style.setProperty('--vl-drop', units * unit + 'px');
			mover.style.setProperty('--vl-drop-steps', String(units));
			mover.classList.remove('is-wait');
			self.play(mover, 'is-drop', DROP_MS);
			self.later('drop-land', DROP_MS, function () {
				self.play(mover, 'is-land', LAND_MS);
			});
			if (self.config.hi && window.AvixPal) {
				self.later('hi', DROP_MS + 260, function () {
					if (!self.frame && self.current === 0) {
						window.AvixPal.play(self.pal, 'is-hi', 2100);
					}
				});
			}
		});
	};

	Values.prototype.build = function (i) {
		var card = this.cards[i];
		if (!card || !this.config.build) {
			return;
		}
		card.classList.remove('is-pending');
		if (!this.motion || card.__vlBuilding) {
			return;
		}
		card.__vlBuilding = true;
		this.play(card, 'is-build', BUILD_MS);
	};

	/* ---------- Cards ---------- */

	Values.prototype.bindCards = function () {
		var self = this;
		this.cards.forEach(function (card, i) {
			self.listen(card, 'pointerenter', function (event) {
				if (event.pointerType !== 'touch') {
					self.enter(i);
				}
			});
			// Touch: only a real tap counts. A swipe that scrolls the page moves
			// past the slop or ends in pointercancel, so it never reaches tap().
			self.listen(card, 'pointerdown', function (event) {
				self.press = event.pointerType === 'touch' ? { i: i, x: event.clientX, y: event.clientY, t: Date.now() } : null;
			}, { passive: true });
			self.listen(card, 'pointerup', function (event) {
				var press = self.press;
				self.press = null;
				if (event.pointerType !== 'touch' || !press || press.i !== i) {
					return;
				}
				if (Math.abs(event.clientX - press.x) > TAP_SLOP || Math.abs(event.clientY - press.y) > TAP_SLOP || Date.now() - press.t > TAP_MS) {
					return;
				}
				self.tap(i);
			});
			self.listen(card, 'pointercancel', function () {
				self.press = null;
			});
			self.listen(card, 'focusin', function () {
				self.enter(i);
			});
		});
		if (this.board) {
			this.listen(this.board, 'pointerleave', this.onLeave);
			this.listen(this.board, 'focusout', this.onFocusOut);
		}
	};

	// Layout box of a card inside the board, ignoring transforms. A card whose
	// item is still rising has that item as its offsetParent (a transformed
	// element is one), so the offsets are summed up to the board.
	Values.prototype.box = function (card) {
		var x = 0;
		var y = 0;
		var el = card;
		while (el && el !== this.board) {
			x += el.offsetLeft;
			y += el.offsetTop;
			el = el.offsetParent;
			if (el && el !== this.board) {
				x += el.clientLeft;
				y += el.clientTop;
			}
		}
		return { x: x, y: y, w: card.offsetWidth, h: card.offsetHeight };
	};

	Values.prototype.canHop = function () {
		if (!this.pal || !this.config.hop || this.cards.length < 2) {
			return false;
		}
		// Side layout: the rows sit under each other, so it hops between them
		// while the text column is beside them (desktop), never when stacked.
		if (this.side) {
			return this.sideBySide();
		}
		// Single column (phones or a narrow column): it stays put.
		var left = this.box(this.cards[0]).x;
		for (var i = 1; i < this.cards.length; i++) {
			if (this.box(this.cards[i]).x !== left) {
				return true;
			}
		}
		return false;
	};

	Values.prototype.enter = function (i) {
		var self = this;
		if (!this.check()) {
			return;
		}
		this.hovered = i;
		this.cancel('home');
		if (this.canHop() && i !== this.current) {
			this.flyTo(i, function () {
				if (self.hovered === i) {
					self.build(i);
				}
			});
			return;
		}
		if (this.pal && !this.config.hop && window.AvixPal) {
			window.AvixPal.lookAt(this.pal, this.cards[i]);
		}
		if (!this.frame) {
			this.build(i);
		}
	};

	Values.prototype.tap = function (i) {
		if (!this.check()) {
			return;
		}
		// Multi-column touch layout: hop over, build, then go home again. There
		// is no pointerleave on touch, so nothing holds the hover afterwards.
		if (this.canHop()) {
			var self = this;
			this.cancel('home');
			this.hovered = -1;
			if (i !== this.current) {
				this.flyTo(i, function () {
					self.build(i);
				});
			} else if (!this.frame) {
				this.build(i);
			}
			this.scheduleHome(2600);
			return;
		}
		this.build(i);
		if (this.pal && window.AvixPal) {
			window.AvixPal.play(this.pal, 'is-wave', 1700);
		}
	};

	Values.prototype.onLeave = function (event) {
		if (event.pointerType === 'touch') {
			return;
		}
		this.hovered = -1;
		if (this.pal && window.AvixPal && !this.config.hop) {
			window.AvixPal.lookAt(this.pal, null);
		}
		this.scheduleHome(HOME_AFTER_MS);
	};

	Values.prototype.onFocusOut = function (event) {
		if (this.board && event.relatedTarget && this.board.contains(event.relatedTarget)) {
			return;
		}
		this.hovered = -1;
		if (this.pal && window.AvixPal && !this.config.hop) {
			window.AvixPal.lookAt(this.pal, null);
		}
		this.scheduleHome(HOME_AFTER_MS);
	};

	Values.prototype.scheduleHome = function (ms) {
		var self = this;
		if (!this.pal || this.current === 0) {
			return;
		}
		this.later('home', ms, function () {
			if (self.hovered < 0) {
				self.flyTo(0);
			}
		});
	};

	/* ---------- The character ---------- */

	// Seat on card i: centred over the icon tile, seat row (7 of 12) on the edge.
	Values.prototype.perch = function (i) {
		var card = this.cards[i];
		if (!card || !this.mover) {
			return null;
		}
		var perch = card.querySelector('[data-vl-perch]');
		var box = this.box(card);
		var w = this.mover.offsetWidth;
		var h = this.mover.offsetHeight;
		var px = perch ? perch.offsetLeft + perch.offsetWidth / 2 : 40;
		// The box is the border box, the perch is measured from the padding box.
		return {
			x: Math.round(box.x + card.clientLeft + px - w / 2),
			y: Math.round(box.y - h * 7 / 12)
		};
	};

	// Highest the character's head may rise above card i's perch: below the
	// text of the row above (or the header, or the section edge) with a margin.
	Values.prototype.headroom = function (i, y) {
		var self = this;
		var card = this.cards[i];
		var boardTop = this.board.getBoundingClientRect().top;
		var limit = this.root.getBoundingClientRect().top - boardTop;
		var top = this.box(card).y;
		var above = false;
		this.cards.forEach(function (other) {
			var box = self.box(other);
			var bottom = box.y + box.h;
			if (other !== card && bottom <= top) {
				var pad = parseFloat(window.getComputedStyle(other).paddingBottom) || 0;
				limit = Math.max(limit, bottom - pad);
				above = true;
			}
		});
		// The header only limits it when it sits above the cards (not beside).
		if (!above && this.head && !(this.side && this.sideBySide())) {
			limit = Math.max(limit, this.head.getBoundingClientRect().bottom - boardTop);
		}
		return Math.max(0, y - limit - HOP_CLEAR);
	};

	Values.prototype.setPos = function (p) {
		this.pos = p;
		this.mover.style.transform = 'translate3d(' + Math.round(p.x) + 'px,' + Math.round(p.y) + 'px,0)';
	};

	Values.prototype.place = function () {
		var target = this.perch(this.current);
		if (target) {
			this.setPos(target);
		}
	};

	Values.prototype.onResize = function () {
		var self = this;
		if (this.resizeFrame || !this.check()) {
			return;
		}
		this.resizeFrame = window.requestAnimationFrame(function () {
			self.resizeFrame = 0;
			if (!self.frame && self.check()) {
				self.place();
			}
		});
	};

	Values.prototype.flyTo = function (i, done) {
		var self = this;
		var target = this.perch(i);
		if (!target) {
			return;
		}
		var fromIndex = this.current;
		this.current = i;
		// Not shown yet: just move the seat, the drop lands it there.
		if (!this.motion || !this.pos || this.mover.classList.contains('is-wait')) {
			this.setPos(target);
			if (done) {
				done();
			}
			return;
		}

		// A hover during the drop-in ends the drop (and its "hi") first.
		this.cancel('is-drop:m');
		this.cancel('drop-land');
		this.cancel('hi');
		this.cancel('is-land:m');
		this.mover.classList.remove('is-drop', 'is-land');
		window.cancelAnimationFrame(this.frame);
		this.frame = 0;
		this.mover.style.opacity = '';

		var from = { x: this.pos.x, y: this.pos.y };
		var dx = target.x - from.x;
		var dy = target.y - from.y;
		var dist = Math.sqrt(dx * dx + dy * dy);
		if (dist < 2) {
			this.land(done);
			return;
		}

		this.pal.classList.remove('is-hi', 'is-wave', 'is-look-l', 'is-look-r');
		this.pal.classList.add('is-fly');
		this.pal.classList.toggle('is-face-l', dx < -2);

		if (Math.abs(dy) >= 2) {
			this.pop(fromIndex, i, from, target, done);
			return;
		}

		// Same row: a short arc that never lifts the head into the text above.
		var duration = Math.max(380, Math.min(760, 360 + dist * 0.35));
		var arc = Math.min(26 + Math.min(dist * 0.16, 64), this.headroom(i, target.y));
		var start = 0;

		function step(now) {
			if (!self.check()) {
				return;
			}
			if (!start) {
				start = now;
			}
			var t = Math.min(1, (now - start) / duration);
			var e = easeInOut(t);
			self.setPos({
				x: from.x + dx * e,
				y: from.y + dy * e - arc * Math.sin(Math.PI * t)
			});
			if (t < 1) {
				self.frame = window.requestAnimationFrame(step);
				return;
			}
			self.land(done);
		}
		this.frame = window.requestAnimationFrame(step);
	};

	// Another row: hop up out of sight on this card, then drop in a few whole
	// sprite units onto the target. It never travels across a card, so it
	// never passes in front of text, and each step is one sprite unit.
	Values.prototype.pop = function (fromIndex, i, from, target, done) {
		var self = this;
		var mover = this.mover;
		var unit = Math.max(1, mover.offsetWidth / 10);
		var upUnits = Math.max(1, Math.min(3, Math.floor(this.headroom(fromIndex, from.y) / unit)));
		var inUnits = Math.max(1, Math.min(4, Math.floor(this.headroom(i, target.y) / unit)));
		var start = 0;

		function step(now) {
			if (!self.check()) {
				return;
			}
			if (!start) {
				start = now;
			}
			var t = now - start;
			if (t < POP_OUT_MS) {
				var k = Math.min(3, Math.floor(t / (POP_OUT_MS / 3)) + 1);
				self.setPos({ x: from.x, y: from.y - Math.round(upUnits * k / 3) * unit });
				mover.style.opacity = String(Math.max(0, 1 - k / 3));
			} else if (t < POP_OUT_MS + POP_GAP_MS) {
				mover.style.opacity = '0';
			} else if (t < POP_OUT_MS + POP_GAP_MS + POP_IN_MS) {
				var n = Math.min(inUnits, Math.floor((t - POP_OUT_MS - POP_GAP_MS) / (POP_IN_MS / (inUnits + 1))));
				self.setPos({ x: target.x, y: target.y - (inUnits - n) * unit });
				mover.style.opacity = n ? '1' : '0.5';
			} else {
				mover.style.opacity = '';
				self.land(done);
				return;
			}
			self.frame = window.requestAnimationFrame(step);
		}
		this.frame = window.requestAnimationFrame(step);
	};

	Values.prototype.land = function (done) {
		this.frame = 0;
		this.mover.style.opacity = '';
		this.place();
		this.pal.classList.remove('is-fly', 'is-face-l');
		this.play(this.mover, 'is-land', LAND_MS);
		if (done) {
			done();
		}
	};

	Values.prototype.destroy = function () {
		var self = this;
		this.alive = false;
		window.cancelAnimationFrame(this.frame);
		window.cancelAnimationFrame(this.resizeFrame);
		Object.keys(this.timers).forEach(function (name) {
			self.cancel(name);
		});
		this.observers.forEach(function (observer) {
			observer.disconnect();
		});
		this.observers = [];
		if (this.stopWave) {
			this.stopWave();
		}
		window.cancelAnimationFrame(this.stickFrame);
		window.removeEventListener('resize', this.onResize);
		window.removeEventListener('load', this.onResize);
		window.removeEventListener('resize', this.onStickResize);
		window.removeEventListener('load', this.onStickResize);
		document.removeEventListener('visibilitychange', this.onVisibility);
		this.handlers.forEach(function (h) {
			h[0].removeEventListener(h[1], h[2], h[3]);
		});
		this.handlers = [];
	};

	/* ---------- Mounting ---------- */

	function mount(root) {
		if (!root || (root.__avixVl && root.__avixVl.alive)) {
			return;
		}
		instances = instances.filter(function (instance) {
			return instance.check();
		});
		root.__avixVl = new Values(root);
		instances.push(root.__avixVl);
	}

	function mountAll(scope) {
		var base = scope && scope.querySelectorAll ? scope : document;
		if (base.matches && base.matches(ROOT_SELECTOR)) {
			mount(base);
		}
		Array.prototype.forEach.call(base.querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixValues = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-values.default', function ($scope) {
			mountAll($scope && $scope[0] ? $scope[0] : $scope);
		});
	}

	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		hookElementor();
	} else {
		window.addEventListener('elementor/frontend/init', hookElementor);
	}
})(window, document);
