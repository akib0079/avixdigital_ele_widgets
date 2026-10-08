/*!
 * Avix Digital · Case Study Feature Spotlight
 * Numbered pins on a real screenshot. A pin opens one shared callout on the
 * side with the most room (never over the pin, always inside the viewport);
 * pins and legend items light each other up; on phones a tap scrolls to the
 * pin's legend card instead. "View full screenshot" opens a native <dialog>.
 * Pins that would touch on a narrow screenshot are nudged apart (at most
 * 12px each) and their touch targets trimmed, so every tap lands on the pin
 * it meant. No loops: the pulse is CSS (paused off screen) and the only rAF
 * is a one-frame callout re-placement while one is open.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-csf]';
	var PHONE_MAX = 600;
	var GAP = 18; // px between the pin's edge and the callout (8px tail + room for the pin's ring)
	var EDGE = 12; // px the callout keeps from the viewport and section edges
	var CLOSE_DELAY = 140;
	var FLASH_MS = 1600;
	var PINS_WAIT = 450;
	var PIN_GAP = 10; // px: least room between two pins' edges
	var PIN_SHIFT = 12; // px: most a pin moves off its spot to make that room
	var PIN_HIT = 22; // px: half the 44px touch target
	var reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };
	var instances = [];
	var openInstance = null;

	function isEditMode() {
		return !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
	}

	// Bottom edge of whatever is fixed at the top of the window: the Smart
	// Header (only while it is shown; it slides away on scroll) and the admin bar.
	function headerOffset() {
		var value = 0;
		var ids = ['wpadminbar', 'avix-smart-header'];
		for (var i = 0; i < ids.length; i++) {
			var el = document.getElementById(ids[i]);
			if (el && window.getComputedStyle(el).position === 'fixed') {
				value = Math.max(value, el.getBoundingClientRect().bottom);
			}
		}
		return Math.max(0, value);
	}

	function clamp(value, min, max) {
		return Math.max(min, Math.min(max, value));
	}

	function Spotlight(root) {
		var config = {};
		try {
			config = JSON.parse(root.getAttribute('data-avix-csf') || '{}') || {};
		} catch (error) {
			config = {};
		}

		this.root = root;
		this.config = config;
		this.alive = true;
		this.editor = isEditMode();
		this.motion = !reduceMotion.matches;
		this.pins = Array.prototype.slice.call(root.querySelectorAll('[data-csf-pin]'));
		this.items = Array.prototype.slice.call(root.querySelectorAll('[data-csf-item]'));
		this.callout = root.querySelector('[data-csf-callout]');
		this.screen = root.querySelector('.avix-csk-frame__screen');
		this.pinBox = root.querySelector('.avix-csf__pins');
		this.full = root.querySelector('[data-csf-full]');
		this.dialog = null;
		this.open = -1;
		this.mode = '';
		this.hoverItem = -1;
		this.phone = false;
		this.handlers = [];
		this.observers = [];
		this.timers = {};
		this.frame = 0;
		this.lockedOverflow = null;

		this.onDocPointer = this.onDocPointer.bind(this);
		this.onKey = this.onKey.bind(this);
		this.onScroll = this.onScroll.bind(this);
		this.onResize = this.onResize.bind(this);
		this.onVisibility = this.onVisibility.bind(this);

		this.init();
	}

	Spotlight.prototype.on = function (el, type, fn, opts) {
		el.addEventListener(type, fn, opts || false);
		this.handlers.push([el, type, fn, opts || false]);
	};

	Spotlight.prototype.later = function (name, ms, fn) {
		var self = this;
		this.cancel(name);
		this.timers[name] = window.setTimeout(function () {
			delete self.timers[name];
			if (self.check()) {
				fn();
			}
		}, ms);
	};

	Spotlight.prototype.cancel = function (name) {
		if (this.timers[name]) {
			window.clearTimeout(this.timers[name]);
			delete this.timers[name];
		}
	};

	Spotlight.prototype.check = function () {
		if (this.alive && !this.root.isConnected) {
			this.destroy();
		}
		return this.alive;
	};

	Spotlight.prototype.init = function () {
		var self = this;
		var root = this.root;
		var armed = 'IntersectionObserver' in window && this.motion && !this.editor;

		root.classList.add('is-ready');
		if (this.editor) {
			root.classList.add('is-editor', 'is-in');
		}
		if (armed) {
			root.classList.add('is-armed');
		}

		if ('IntersectionObserver' in window) {
			var io = new window.IntersectionObserver(function (entries) {
				if (!self.check()) {
					return;
				}
				var entry = entries[entries.length - 1];
				root.classList.toggle('is-off', !entry.isIntersecting);
				if (!entry.isIntersecting && self.open > -1) {
					self.close(false);
				}
				if (entry.isIntersecting && entry.intersectionRatio >= 0.15 && !root.classList.contains('is-in')) {
					root.classList.add('is-in');
				}
			}, { threshold: [0, 0.15] });
			io.observe(root);
			this.observers.push(io);

			// Pins pop once their screenshot is on screen and resolved.
			if (armed && this.screen && this.pins.length) {
				var media = new window.IntersectionObserver(function (entries) {
					var entry = entries[entries.length - 1];
					if (entry.isIntersecting && entry.intersectionRatio >= 0.2) {
						media.disconnect();
						self.popPins();
					}
				}, { threshold: [0, 0.2, 0.5] });
				media.observe(this.screen);
				this.observers.push(media);
			}
		} else {
			root.classList.add('is-in');
		}
		document.addEventListener('visibilitychange', this.onVisibility);

		// Legend items join the tab order only now that focusing one does
		// something, and never while the legend is visually hidden.
		this.items.forEach(function (item, i) {
			if (self.config.legend !== false) {
				item.setAttribute('tabindex', '0');
			}
			self.on(item, 'pointerenter', function () {
				self.hoverItem = i;
				self.highlight();
			});
			self.on(item, 'pointerleave', function () {
				if (self.hoverItem === i) {
					self.hoverItem = -1;
				}
				self.highlight();
			});
			self.on(item, 'focus', function () {
				self.hoverItem = i;
				self.highlight();
			});
			self.on(item, 'blur', function () {
				if (self.hoverItem === i) {
					self.hoverItem = -1;
				}
				self.highlight();
			});
		});

		this.pins.forEach(function (pin, i) {
			self.on(pin, 'pointerenter', function (event) {
				if (event.pointerType !== 'mouse' || self.phone) {
					return;
				}
				self.cancel('close');
				if (self.open !== i) {
					self.show(i, 'hover');
				}
			});
			self.on(pin, 'pointerleave', function (event) {
				if (event.pointerType !== 'mouse' || self.mode !== 'hover' || self.open !== i) {
					return;
				}
				self.later('close', CLOSE_DELAY, function () {
					self.close(false);
				});
			});
			self.on(pin, 'focus', function () {
				if (self.phone) {
					self.hoverItem = i;
					self.highlight();
					return;
				}
				self.cancel('close');
				if (self.open !== i) {
					self.show(i, 'focus');
				} else if (self.mode === 'hover') {
					self.mode = 'focus';
				}
			});
			self.on(pin, 'blur', function () {
				if (self.phone) {
					if (self.hoverItem === i) {
						self.hoverItem = -1;
					}
					self.highlight();
					return;
				}
				// Focus moving to another pin opens that one; anything else closes.
				self.later('close', 0, function () {
					var active = document.activeElement;
					if (self.open === i && self.pins.indexOf(active) < 0 && self.mode !== 'hover') {
						self.close(false);
					}
				});
			});
			self.on(pin, 'click', function (event) {
				event.preventDefault();
				if (self.phone) {
					self.toLegend(i);
					return;
				}
				self.cancel('close');
				if (self.open === i && self.mode === 'click') {
					self.close(false);
				} else {
					self.show(i, 'click');
				}
			});
		});

		document.addEventListener('keydown', this.onKey);
		document.addEventListener('pointerdown', this.onDocPointer, true);
		window.addEventListener('resize', this.onResize);
		window.addEventListener('scroll', this.onScroll, { passive: true });

		if ('ResizeObserver' in window) {
			var ro = new window.ResizeObserver(function () {
				if (self.check()) {
					self.onResize();
				}
			});
			ro.observe(root);
			this.observers.push(ro);
		}
		this.layout();
		this.spread();

		// Without a reserved ratio the screen only gets its height on load.
		var img = this.screen ? this.screen.querySelector('img') : null;
		if (img && !img.complete && this.pins.length > 1) {
			this.on(img, 'load', function () {
				if (self.check()) {
					self.spread();
				}
			});
		}

		// Editor: a slider moved a pin (left/top transition), re-check its neighbours.
		if (this.editor && this.pinBox) {
			this.on(this.pinBox, 'transitionend', function (event) {
				if ((event.propertyName === 'left' || event.propertyName === 'top') && self.check()) {
					self.spread();
				}
			});
		}

		if (this.full && typeof window.HTMLDialogElement === 'function') {
			this.on(this.full, 'click', function (event) {
				if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button > 0) {
					return;
				}
				event.preventDefault();
				self.openDialog();
			});
		}
	};

	/* ---------- Reveal ---------- */

	Spotlight.prototype.popPins = function () {
		var self = this;
		var root = this.root;
		var screen = this.screen;
		var go = function (wait) {
			if (!self.check() || root.classList.contains('is-pins')) {
				return;
			}
			root.style.setProperty('--csf-pins-wait', wait + 'ms');
			root.classList.add('is-pins');
		};
		// The kit's pixel reveal is running: pop as its last squares clear.
		if (screen.classList.contains('is-px-armed') && 'MutationObserver' in window) {
			var mo = new window.MutationObserver(function () {
				if (!screen.classList.contains('is-px-armed')) {
					mo.disconnect();
					go(0);
				}
			});
			mo.observe(screen, { attributes: true, attributeFilter: ['class'] });
			this.observers.push(mo);
			this.later('pins', 3200, function () {
				go(0);
			});
			return;
		}
		go(PINS_WAIT);
	};

	Spotlight.prototype.onVisibility = function () {
		if (!this.check()) {
			return;
		}
		if (document.hidden) {
			this.root.classList.add('is-off');
			return;
		}
		var rect = this.root.getBoundingClientRect();
		var vh = window.innerHeight || document.documentElement.clientHeight;
		this.root.classList.toggle('is-off', rect.bottom <= 0 || rect.top >= vh);
	};

	/* ---------- Layout ---------- */

	Spotlight.prototype.layout = function () {
		var phone = this.root.clientWidth <= PHONE_MAX;
		var self = this;
		if (phone === this.phone && this.laidOut) {
			return;
		}
		this.laidOut = true;
		this.phone = phone;
		// On phones a pin does not expand anything: it points to its legend card.
		this.pins.forEach(function (pin) {
			if (phone) {
				pin.removeAttribute('aria-expanded');
				pin.removeAttribute('aria-controls');
			} else {
				pin.setAttribute('aria-expanded', self.open > -1 && self.pins[self.open] === pin ? 'true' : 'false');
				if (self.callout) {
					pin.setAttribute('aria-controls', self.callout.id);
				}
			}
		});
		if (phone && this.open > -1) {
			this.close(false);
		}
	};

	Spotlight.prototype.onResize = function () {
		if (!this.check()) {
			return;
		}
		this.layout();
		this.spread();
		if (this.open > -1) {
			this.schedulePlace();
		}
	};

	Spotlight.prototype.onScroll = function () {
		if (this.open > -1 && this.check()) {
			this.schedulePlace();
		}
	};

	Spotlight.prototype.schedulePlace = function () {
		var self = this;
		if (this.frame) {
			return;
		}
		this.frame = window.requestAnimationFrame(function () {
			self.frame = 0;
			if (self.check() && self.open > -1) {
				self.place();
			}
		});
	};

	/* ---------- Close pins ---------- */

	/**
	 * Keeps pins apart on narrow screenshots. Two centres closer than a pin
	 * plus PIN_GAP are pushed apart along the axis where they are already
	 * furthest apart, each by at most PIN_SHIFT and never out of the screen.
	 * Then every pin's touch target is trimmed to stop short of its nearest
	 * neighbour's centre, and earlier pins sit on top (CSS), so a tap on any
	 * pin's centre lands on that pin. Positions are re-read from CSS (left/
	 * top without the nudge) every time, so this is safe to run again.
	 */
	Spotlight.prototype.spread = function () {
		var pins = this.pins;
		var box = this.pinBox;
		if (!box || pins.length < 2) {
			return;
		}
		var w = box.clientWidth;
		var h = box.clientHeight;
		if (!w || !h) {
			return;
		}
		var size = pins[0].offsetWidth || 28;
		var edge = size / 2 + 4;
		var room = size + PIN_GAP;
		var pts = pins.map(function (pin) {
			var style = window.getComputedStyle(pin);
			return { x: parseFloat(style.left) || 0, y: parseFloat(style.top) || 0, dx: 0, dy: 0 };
		});

		// Moves a point by up to `amount` along an axis; returns how far it went.
		function nudge(p, axis, amount) {
			var key = axis === 'x' ? 'dx' : 'dy';
			var base = axis === 'x' ? p.x : p.y;
			var max = axis === 'x' ? w : h;
			var next = clamp(p[key] + amount, -PIN_SHIFT, PIN_SHIFT);
			next = clamp(base + next, edge, Math.max(edge, max - edge)) - base;
			var moved = next - p[key];
			p[key] = next;
			return moved;
		}

		for (var pass = 0; pass < 6; pass++) {
			var changed = false;
			for (var i = 0; i < pts.length; i++) {
				for (var j = i + 1; j < pts.length; j++) {
					var a = pts[i];
					var b = pts[j];
					var ddx = b.x + b.dx - (a.x + a.dx);
					var ddy = b.y + b.dy - (a.y + a.dy);
					if (Math.sqrt(ddx * ddx + ddy * ddy) >= room - 0.5) {
						continue;
					}
					var axis = Math.abs(ddx) >= Math.abs(ddy) ? 'x' : 'y';
					var d = axis === 'x' ? ddx : ddy;
					var dir = d < 0 ? -1 : 1;
					var need = room - Math.abs(d);
					// Half each; whatever one pin cannot take, the other tries.
					var gotA = Math.abs(nudge(a, axis, -dir * need / 2));
					var gotB = Math.abs(nudge(b, axis, dir * (need - gotA)));
					if (gotA + gotB < need - 0.5) {
						gotA += Math.abs(nudge(a, axis, -dir * (need - gotA - gotB)));
					}
					if (gotA + gotB > 0.25) {
						changed = true;
					}
				}
			}
			if (!changed) {
				break;
			}
		}

		pts.forEach(function (p, n) {
			var gap = Infinity;
			pts.forEach(function (q, m) {
				if (m !== n) {
					gap = Math.min(gap, Math.max(Math.abs(q.x + q.dx - p.x - p.dx), Math.abs(q.y + q.dy - p.y - p.dy)));
				}
			});
			// An open pin grows by 12%: its target must still stop short.
			var hit = Math.max(size / 2, Math.min(PIN_HIT, gap / 1.15 - 1));
			var pin = pins[n];
			pin.style.setProperty('--csf-dx', Math.round(p.dx * 10) / 10 + 'px');
			pin.style.setProperty('--csf-dy', Math.round(p.dy * 10) / 10 + 'px');
			pin.style.setProperty('--csf-hit', Math.round(hit * 10) / 10 + 'px');
		});
	};

	/* ---------- Highlight ---------- */

	Spotlight.prototype.highlight = function () {
		var active = this.open > -1 ? this.open : this.hoverItem;
		if (this.hoverItem > -1) {
			active = this.hoverItem;
		}
		this.pins.forEach(function (pin, i) {
			pin.classList.toggle('is-active', i === active);
		});
		this.items.forEach(function (item, i) {
			item.classList.toggle('is-active', i === active);
		});
	};

	/* ---------- Callout ---------- */

	Spotlight.prototype.fill = function (i) {
		var callout = this.callout;
		var item = this.items[i];
		var label = item ? item.querySelector('.avix-csf__legend-label') : null;
		var detail = item ? item.querySelector('.avix-csf__legend-detail') : null;
		var text = label ? label.textContent : '';
		if (!text) {
			var sr = this.pins[i].querySelector('.screen-reader-text');
			text = sr ? sr.textContent.replace(/^[^:]*:\s*/, '') : '';
		}

		while (callout.firstChild) {
			callout.removeChild(callout.firstChild);
		}
		var num = document.createElement('span');
		num.className = 'avix-csf__callout-num';
		num.setAttribute('aria-hidden', 'true');
		num.textContent = String(i + 1);
		var body = document.createElement('span');
		body.className = 'avix-csf__callout-body';
		var strong = document.createElement('strong');
		strong.className = 'avix-csf__callout-label';
		strong.textContent = text;
		body.appendChild(strong);
		if (detail && detail.textContent) {
			var span = document.createElement('span');
			span.className = 'avix-csf__callout-detail';
			span.textContent = detail.textContent;
			body.appendChild(span);
		}
		callout.appendChild(num);
		callout.appendChild(body);
	};

	Spotlight.prototype.show = function (i, mode) {
		var self = this;
		if (!this.callout || !this.pins[i]) {
			return;
		}
		if (openInstance && openInstance !== this) {
			openInstance.close(false);
		}
		openInstance = this;
		this.cancel('close');
		this.cancel('hide');

		var changed = this.open !== i;
		this.open = i;
		this.mode = mode;
		this.pins.forEach(function (pin, n) {
			if (!self.phone) {
				pin.setAttribute('aria-expanded', n === i ? 'true' : 'false');
			}
		});
		this.highlight();

		var callout = this.callout;
		if (changed || callout.hidden) {
			this.fill(i);
			callout.classList.remove('is-open');
			callout.hidden = false;
			this.place();
			// Next frame: start the pop from the side of the pin.
			window.requestAnimationFrame(function () {
				if (self.open === i && self.check()) {
					callout.classList.add('is-open');
				}
			});
		} else {
			callout.classList.add('is-open');
		}
	};

	Spotlight.prototype.close = function (refocus) {
		var self = this;
		var i = this.open;
		if (i < 0) {
			return;
		}
		this.open = -1;
		this.mode = '';
		this.cancel('close');
		if (!this.phone) {
			this.pins.forEach(function (pin) {
				pin.setAttribute('aria-expanded', 'false');
			});
		}
		this.highlight();
		if (openInstance === this) {
			openInstance = null;
		}
		if (this.callout) {
			this.callout.classList.remove('is-open');
			this.later('hide', 200, function () {
				if (self.open < 0) {
					self.callout.hidden = true;
				}
			});
		}
		if (refocus && this.pins[i]) {
			this.pins[i].focus({ preventScroll: true });
		}
	};

	/**
	 * Puts the callout beside the open pin. It tries the screenshot's own
	 * area first (so it never covers the copy), then the whole section; both
	 * are cut to the viewport below a fixed header. Within a box, the editor's
	 * chosen side wins when it fits, then a side that keeps the other pins
	 * visible, then the side with the most room. Only the cross axis is
	 * clamped, so the callout slides along its pin but never over it.
	 */
	Spotlight.prototype.place = function () {
		var callout = this.callout;
		var pin = this.pins[this.open];
		if (!callout || !pin) {
			return;
		}
		var rootRect = this.root.getBoundingClientRect();
		var pinRect = pin.getBoundingClientRect();
		var vw = document.documentElement.clientWidth || window.innerWidth;
		var vh = window.innerHeight || document.documentElement.clientHeight;
		var top = headerOffset();
		var half = (pin.offsetWidth || 28) / 2;
		var cx = pinRect.left + pinRect.width / 2;
		var cy = pinRect.top + pinRect.height / 2;
		var w = callout.offsetWidth;
		var h = callout.offsetHeight;
		var wanted = pin.getAttribute('data-csf-place') || 'auto';
		var others = this.pins.filter(function (other) {
			return other !== pin;
		}).map(function (other) {
			var r = other.getBoundingClientRect();
			return { left: r.left - 6, right: r.right + 6, top: r.top - 6, bottom: r.bottom + 6 };
		});

		function cut(rect) {
			return {
				minX: Math.max(rect.left, 0) + EDGE,
				maxX: Math.min(rect.right, vw) - EDGE,
				minY: Math.max(rect.top, top) + EDGE,
				maxY: Math.min(rect.bottom, vh) - EDGE
			};
		}

		function candidate(side, b) {
			var c = { side: side };
			if (side === 'top' || side === 'bottom') {
				c.x = clamp(cx - w / 2, b.minX, Math.max(b.minX, b.maxX - w));
				c.y = side === 'top' ? cy - half - GAP - h : cy + half + GAP;
				c.room = side === 'top' ? c.y - b.minY : b.maxY - (c.y + h);
				c.tail = clamp(cx - c.x, 16, w - 16);
			} else {
				c.y = clamp(cy - h / 2, b.minY, Math.max(b.minY, b.maxY - h));
				c.x = side === 'left' ? cx - half - GAP - w : cx + half + GAP;
				c.room = side === 'left' ? c.x - b.minX : b.maxX - (c.x + w);
				c.tail = clamp(cy - c.y, 16, h - 16);
			}
			c.fits = c.room >= 0;
			c.clear = !others.some(function (o) {
				return !(c.x + w <= o.left || c.x >= o.right || c.y + h <= o.top || c.y >= o.bottom);
			});
			return c;
		}

		function best(b) {
			var list = ['right', 'left', 'bottom', 'top'].map(function (side) {
				return candidate(side, b);
			});
			var fit = list.filter(function (c) {
				return c.fits;
			});
			if (!fit.length) {
				return null;
			}
			var chosen = null;
			fit.forEach(function (c) {
				if (c.side === wanted) {
					chosen = c;
				}
			});
			if (chosen) {
				return chosen;
			}
			var clear = fit.filter(function (c) {
				return c.clear;
			});
			return (clear.length ? clear : fit).reduce(function (a, c) {
				return c.room > a.room + 0.5 ? c : a;
			});
		}

		var media = this.root.querySelector('[data-csf-media]');
		var pick = (media ? best(cut(media.getBoundingClientRect())) : null) || best(cut(rootRect));
		if (!pick) {
			// Nowhere fits (a tiny window): the side with the most room in the section.
			var b = cut(rootRect);
			pick = ['right', 'left', 'bottom', 'top'].map(function (side) {
				return candidate(side, b);
			}).reduce(function (a, c) {
				return c.room > a.room ? c : a;
			});
		}

		callout.setAttribute('data-place', pick.side);
		callout.style.left = Math.round(pick.x - rootRect.left) + 'px';
		callout.style.top = Math.round(pick.y - rootRect.top) + 'px';
		callout.style.setProperty('--csf-tail', Math.round(pick.tail) + 'px');
	};

	/* ---------- Phones: tap a pin, see its card ---------- */

	Spotlight.prototype.toLegend = function (i) {
		var self = this;
		var item = this.items[i];
		var pin = this.pins[i];
		if (!item || !this.config.legend) {
			// Legend hidden on this spotlight: the callout is the only place the label lives.
			this.show(i, 'click');
			return;
		}
		try {
			item.scrollIntoView({ block: 'nearest', behavior: this.motion ? 'smooth' : 'auto' });
		} catch (error) {
			item.scrollIntoView(false);
		}
		this.items.forEach(function (other) {
			other.classList.remove('is-flash');
		});
		// Restart the flash on a repeat tap.
		void item.offsetWidth;
		item.classList.add('is-flash', 'is-active');
		pin.classList.add('is-active');
		this.later('flash', FLASH_MS, function () {
			item.classList.remove('is-flash');
			self.highlight();
		});
	};

	/* ---------- Keyboard and outside clicks ---------- */

	Spotlight.prototype.onKey = function (event) {
		if ((event.key === 'Escape' || event.key === 'Esc') && this.open > -1 && this.check()) {
			var refocus = this.mode !== 'hover' || this.root.contains(document.activeElement);
			event.stopPropagation();
			this.close(refocus);
		}
	};

	Spotlight.prototype.onDocPointer = function (event) {
		if (!this.check() || this.open < 0) {
			return;
		}
		var target = event.target;
		for (var i = 0; i < this.pins.length; i++) {
			if (this.pins[i] === target || this.pins[i].contains(target)) {
				return;
			}
		}
		this.close(false);
	};

	/* ---------- Lightbox ---------- */

	Spotlight.prototype.buildDialog = function () {
		var self = this;
		var link = this.full;
		var dialog = document.createElement('dialog');
		var alt = link.getAttribute('data-csf-alt') || '';
		dialog.className = 'avix-csf__dialog';
		dialog.setAttribute('aria-label', alt || link.textContent.trim());

		var bar = document.createElement('div');
		bar.className = 'avix-csf__dialog-bar';
		var cap = document.createElement('p');
		cap.className = 'avix-csf__dialog-cap';
		cap.textContent = alt;
		var close = document.createElement('button');
		close.type = 'button';
		close.className = 'avix-csf__dialog-close';
		close.setAttribute('aria-label', this.config.close || 'Close');
		close.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6 6 18"/></svg>';
		bar.appendChild(cap);
		bar.appendChild(close);

		var body = document.createElement('div');
		body.className = 'avix-csf__dialog-body';
		body.setAttribute('tabindex', '0');
		var img = document.createElement('img');
		img.className = 'avix-csf__dialog-img';
		img.alt = alt;
		img.decoding = 'async';
		var w = parseInt(link.getAttribute('data-csf-w'), 10) || 0;
		var h = parseInt(link.getAttribute('data-csf-h'), 10) || 0;
		if (w && h) {
			img.width = w;
			img.height = h;
			// Screenshots are 2× (desktop) or 3× (phone): show them at their real CSS size.
			var css = h > w ? Math.min(w / 2, 430) : Math.min(w / 2, 1600);
			dialog.style.setProperty('--csf-dialog-w', Math.round(Math.max(css, 320)) + 'px');
		}
		body.appendChild(img);
		dialog.appendChild(bar);
		dialog.appendChild(body);

		close.addEventListener('click', function () {
			dialog.close();
		});
		// A click on the backdrop lands on the dialog itself.
		dialog.addEventListener('click', function (event) {
			if (event.target === dialog) {
				dialog.close();
			}
		});
		dialog.addEventListener('close', function () {
			self.unlock();
			if (self.full && self.alive) {
				self.full.focus({ preventScroll: true });
			}
		});

		this.root.appendChild(dialog);
		this.dialog = dialog;
		this.dialogImg = img;
		this.dialogBody = body;
		this.dialogClose = close;
		return dialog;
	};

	Spotlight.prototype.openDialog = function () {
		var dialog = this.dialog || this.buildDialog();
		if (dialog.open) {
			return;
		}
		this.close(false);
		if (!this.dialogImg.getAttribute('src')) {
			this.dialogImg.src = this.full.href;
		}
		this.lock();
		try {
			dialog.showModal();
		} catch (error) {
			this.unlock();
			window.location.href = this.full.href;
			return;
		}
		this.dialogBody.scrollTop = 0;
		this.dialogClose.focus({ preventScroll: true });
	};

	Spotlight.prototype.lock = function () {
		var html = document.documentElement;
		if (this.lockedOverflow === null) {
			this.lockedOverflow = html.style.overflow;
		}
		html.style.overflow = 'hidden';
		html.style.overflow = 'clip';
	};

	Spotlight.prototype.unlock = function () {
		if (this.lockedOverflow !== null) {
			document.documentElement.style.overflow = this.lockedOverflow;
			this.lockedOverflow = null;
		}
	};

	/* ---------- Teardown ---------- */

	Spotlight.prototype.destroy = function () {
		var self = this;
		this.alive = false;
		window.cancelAnimationFrame(this.frame);
		Object.keys(this.timers).forEach(function (name) {
			window.clearTimeout(self.timers[name]);
		});
		this.timers = {};
		this.observers.forEach(function (observer) {
			observer.disconnect();
		});
		this.observers = [];
		this.handlers.forEach(function (h) {
			h[0].removeEventListener(h[1], h[2], h[3]);
		});
		this.handlers = [];
		document.removeEventListener('pointerdown', this.onDocPointer, true);
		document.removeEventListener('keydown', this.onKey);
		document.removeEventListener('visibilitychange', this.onVisibility);
		window.removeEventListener('resize', this.onResize);
		window.removeEventListener('scroll', this.onScroll, { passive: true });
		if (this.dialog && this.dialog.open) {
			this.dialog.close();
		}
		this.unlock();
		if (openInstance === this) {
			openInstance = null;
		}
	};

	/* ---------- Mounting ---------- */

	function mount(root) {
		if (!root || (root.__avixCsf && root.__avixCsf.alive)) {
			return;
		}
		instances = instances.filter(function (instance) {
			return instance.check();
		});
		root.__avixCsf = new Spotlight(root);
		instances.push(root.__avixCsf);
	}

	function mountAll(scope) {
		var base = scope && scope.querySelectorAll ? scope : document;
		if (base.matches && base.matches(ROOT_SELECTOR)) {
			mount(base);
		}
		Array.prototype.forEach.call(base.querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixCaseStudySpotlight = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-case-study-spotlight.default', function ($scope) {
			mountAll($scope && $scope[0] ? $scope[0] : $scope);
		});
	}

	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		hookElementor();
	} else {
		window.addEventListener('elementor/frontend/init', hookElementor);
	}
})(window, document);
