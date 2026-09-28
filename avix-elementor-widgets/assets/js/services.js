/*!
 * Avix Digital · Services Showcase
 * Dependency-free. The pixel avatar flies to the active service on a spring
 * (stretching with speed, jets flaring, exhaust and afterimages trailing),
 * then points at it. Hover intent ignores the fake hovers a layout shift
 * produces, so rows never cascade open. One rAF loop runs only while
 * something moves and pauses off screen.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-sv]';
	var instances = [];
	var mq = function (query) {
		return window.matchMedia ? window.matchMedia(query) : { matches: false };
	};
	var reduceMotion = mq('(prefers-reduced-motion: reduce)');
	var finePointer = mq('(hover: hover) and (pointer: fine)');

	var INTENT_MS = 110; // a hover must rest this long before a service opens
	var SETTLE_MS = 560; // panel transition (450ms) + margin: keep tracking the moving name
	var LEAVE_MS = 760; // card leave transition
	var SPRING = 170; // stiffness
	var DAMPING = 20; // a little under critical: one small, lively overshoot
	var BASE_TILT = { x: 4, y: -6 }; // resting card tilt from the CSS, in degrees

	function clamp(value, min, max) {
		return value < min ? min : value > max ? max : value;
	}

	function now() {
		return window.performance ? window.performance.now() : Date.now();
	}

	function isEditMode() {
		return !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
	}

	// Distance from `ancestor` to `el`, ignoring transforms (reveal / active shift).
	function offsetWithin(el, ancestor) {
		var y = 0;
		while (el && el !== ancestor) {
			y += el.offsetTop;
			el = el.offsetParent;
		}
		return el === ancestor ? y : null;
	}

	function Showcase(root) {
		var config = {};
		try {
			config = JSON.parse(root.getAttribute('data-avix-sv') || '{}') || {};
		} catch (error) {
			config = {};
		}

		this.root = root;
		this.alive = true;
		this.editor = isEditMode();
		this.hover = config.trigger !== 'click';
		this.tiltOn = config.tilt !== false;
		this.boots = !!config.boots;

		this.list = root.querySelector('[data-sv-list]');
		this.items = Array.prototype.slice.call(root.querySelectorAll('[data-sv-item]'));
		this.toggles = this.items.map(function (item) {
			return item.querySelector('[data-sv-toggle]');
		});
		this.panels = this.items.map(function (item) {
			return item.querySelector('.avix-sv__panel');
		});
		this.names = this.items.map(function (item) {
			return item.querySelector('.avix-sv__name');
		});
		this.cards = Array.prototype.slice.call(root.querySelectorAll('[data-sv-card]'));
		this.visual = root.querySelector('[data-sv-visual]');
		this.stage = root.querySelector('[data-sv-stage]');
		this.avatar = root.querySelector('[data-sv-avatar]');
		this.ghosts = Array.prototype.slice.call(root.querySelectorAll('[data-sv-ghost]'));
		this.dots = Array.prototype.slice.call(root.querySelectorAll('[data-sv-dot]'));
		this.fill = root.querySelector('[data-sv-fill]');
		this.spot = root.querySelector('[data-sv-spot]');
		this.canvas = root.querySelector('[data-sv-exhaust]');
		this.ctx = this.canvas && this.canvas.getContext ? this.canvas.getContext('2d') : null;

		this.active = Math.max(0, this.items.findIndex ? this.items.findIndex(function (item) {
			return item.classList.contains('is-active');
		}) : 0);
		this.centers = [];
		this.y = null;
		this.v = 0;
		this.history = [];
		this.particles = [];
		this.emitAcc = 0;
		this.pose = '';
		this.lit = -1;
		this.settleUntil = 0;
		this.seen = false;
		this.visible = !('IntersectionObserver' in window);
		this.known = this.visible;
		this.running = false;
		this.frame = 0;
		this.lastTime = 0;
		this.timers = {};
		this.last = null;
		this.tilt = { on: false, px: 0, py: 0, x: 0, y: 0, live: false };

		if (!this.list || !this.items.length) {
			this.alive = false;
			return;
		}

		this.tick = this.tick.bind(this);
		this.init();
	}

	Showcase.prototype.init = function () {
		var self = this;

		this.root.classList.add('is-ready');
		this.items.forEach(function (item, i) {
			self.setOpen(i, i === self.active);
		});

		// Clicks, taps and keyboard.
		this.onClick = function (event) {
			var toggle = event.target.closest ? event.target.closest('[data-sv-toggle]') : null;
			var index = self.toggles.indexOf(toggle);
			if (index > -1) {
				self.cancel('intent');
				self.activate(index, true);
			}
		};
		this.onKey = function (event) {
			var index = self.toggles.indexOf(event.target);
			if (index < 0) {
				return;
			}
			var last = self.toggles.length - 1;
			var next = { ArrowDown: index + 1, ArrowRight: index + 1, ArrowUp: index - 1, ArrowLeft: index - 1, Home: 0, End: last }[event.key];
			if (next === undefined) {
				return;
			}
			event.preventDefault();
			next = next > last ? 0 : next < 0 ? last : next;
			self.toggles[next].focus();
			self.activate(next, true);
		};
		this.list.addEventListener('click', this.onClick);
		this.list.addEventListener('keydown', this.onKey);

		// Hover intent: only real pointer movement counts. When a panel opens,
		// rows slide under a still cursor and the browser reports fake hovers
		// at the same coordinates; those are ignored, so nothing cascades.
		if (this.hover) {
			this.onListMove = function (event) {
				if (event.pointerType !== 'mouse') {
					return;
				}
				var moved = !self.last || self.last.x !== event.clientX || self.last.y !== event.clientY;
				self.last = { x: event.clientX, y: event.clientY };
				if (!moved) {
					return;
				}
				var item = event.target.closest ? event.target.closest('[data-sv-item]') : null;
				var index = self.items.indexOf(item);
				if (index < 0 || index === self.active) {
					self.cancel('intent');
					self.intent = -1;
					return;
				}
				if (index !== self.intent) {
					self.intent = index;
					self.later('intent', function () {
						self.intent = -1;
						self.activate(index, false);
					}, INTENT_MS);
				}
			};
			this.onListLeave = function () {
				self.cancel('intent');
				self.intent = -1;
				self.last = null;
			};
			this.list.addEventListener('pointermove', this.onListMove, { passive: true });
			this.list.addEventListener('pointerleave', this.onListLeave, { passive: true });
		}

		// Card tilt + glare.
		if (this.visual && this.stage && this.tiltOn) {
			this.onVisualMove = function (event) {
				if (event.pointerType !== 'mouse' || !finePointer.matches || reduceMotion.matches) {
					return;
				}
				self.tilt.on = true;
				self.tilt.px = event.clientX;
				self.tilt.py = event.clientY;
				self.visual.classList.add('is-tilting');
				self.wake();
			};
			this.onVisualLeave = function () {
				self.tilt.on = false;
				self.visual.classList.remove('is-tilting');
				self.wake();
			};
			this.visual.addEventListener('pointermove', this.onVisualMove, { passive: true });
			this.visual.addEventListener('pointerleave', this.onVisualLeave, { passive: true });
		}

		this.onVisibility = function () {
			self.update();
		};
		document.addEventListener('visibilitychange', this.onVisibility);

		if ('IntersectionObserver' in window) {
			if (!this.editor && !reduceMotion.matches) {
				this.root.classList.add('is-armed');
			}
			this.observer = new window.IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					self.visible = entry.isIntersecting;
				});
				self.known = true;
				self.update();
			}, { threshold: 0.12 });
			this.observer.observe(this.root);
		}

		if (window.ResizeObserver) {
			this.resizeObserver = new window.ResizeObserver(function () {
				self.later('measure', function () {
					self.measure();
					self.wake();
				}, 50);
			});
			this.resizeObserver.observe(this.list);
		}
		if (document.fonts && document.fonts.ready) {
			document.fonts.ready.then(function () {
				if (self.alive) {
					self.measure();
					self.wake();
				}
			});
		}

		this.measure();
		this.update();
	};

	/* ------------------------------------------------------------------ */
	/* State                                                               */
	/* ------------------------------------------------------------------ */

	Showcase.prototype.setOpen = function (index, open) {
		this.items[index].classList.toggle('is-active', open);
		if (this.toggles[index]) {
			this.toggles[index].setAttribute('aria-expanded', open ? 'true' : 'false');
		}
		var panel = this.panels[index];
		if (panel) {
			if ('inert' in panel) {
				panel.inert = !open;
			} else {
				Array.prototype.forEach.call(panel.querySelectorAll('a, button'), function (control) {
					control.tabIndex = open ? 0 : -1;
				});
			}
		}
	};

	Showcase.prototype.activate = function (index, fromUser) {
		if (!this.alive || index === this.active || !this.items[index]) {
			return;
		}
		if (!this.root.isConnected) {
			this.destroy();
			return;
		}
		var previous = this.active;
		this.active = index;
		this.setOpen(previous, false);
		this.setOpen(index, true);
		this.swapCards(previous, index);
		this.settleUntil = now() + SETTLE_MS;
		this.wake();

		// Phones: keep the opened service in view once the panel above has closed.
		if (fromUser && this.visual && window.getComputedStyle(this.visual).display === 'none') {
			var item = this.items[index];
			var self = this;
			this.later('scroll', function () {
				var top = item.getBoundingClientRect().top;
				if (top < 70) {
					window.scrollBy({ top: top - 90, behavior: reduceMotion.matches ? 'auto' : 'smooth' });
				}
				self.wake();
			}, SETTLE_MS);
		}
	};

	// Direction-aware swap: going down the list the new card rises from below.
	Showcase.prototype.swapCards = function (from, to) {
		var incoming = this.cards[to];
		var outgoing = this.cards[from];
		if (!incoming || !outgoing) {
			return;
		}
		var self = this;
		var shift = (to > from ? 1 : -1) * 28 + 'px';

		var glare = outgoing.querySelector('.avix-sv__glare');
		if (glare) {
			glare.style.translate = '';
		}

		if (reduceMotion.matches) {
			outgoing.classList.remove('is-active', 'is-leaving');
			incoming.classList.remove('is-leaving');
			incoming.classList.add('is-active');
			return;
		}

		incoming.style.setProperty('--sv-from', shift);
		incoming.classList.remove('is-leaving');
		incoming.classList.add('is-before');
		void incoming.offsetWidth; // commit the start pose
		incoming.classList.remove('is-before');
		incoming.classList.add('is-active');

		outgoing.style.setProperty('--sv-from', shift);
		outgoing.classList.remove('is-active');
		outgoing.classList.add('is-leaving');
		this.later('leave-' + from, function () {
			if (self.cards[from] !== self.cards[self.active]) {
				outgoing.classList.remove('is-leaving');
			}
		}, LEAVE_MS);
	};

	/* ------------------------------------------------------------------ */
	/* Measuring                                                           */
	/* ------------------------------------------------------------------ */

	// Vertical centre of each service name's first line, inside the list.
	// Runs every frame while rows slide, so line heights come from measure().
	Showcase.prototype.readCenters = function () {
		var list = this.list;
		var lines = this.lines || [];
		this.centers = this.names.map(function (name, i) {
			var top = offsetWithin(name, list);
			if (top === null) {
				var rect = name.getBoundingClientRect();
				top = rect.top - list.getBoundingClientRect().top;
			}
			return top + (lines[i] || name.offsetHeight) / 2;
		});
	};

	// Dots sit on each name; they move with the rows while panels open and close.
	Showcase.prototype.placeDots = function () {
		for (var i = 0; i < this.dots.length; i++) {
			if (this.centers[i] !== undefined) {
				this.dots[i].style.translate = '0 ' + this.centers[i].toFixed(1) + 'px';
			}
		}
	};

	Showcase.prototype.measure = function () {
		if (!this.alive) {
			return;
		}
		var list = this.list;
		this.lines = this.names.map(function (name) {
			var line = parseFloat(window.getComputedStyle(name).lineHeight);
			return isFinite(line) ? line : 0;
		});
		this.readCenters();
		this.listH = list.offsetHeight;

		// Stable height on the two-column layout: room for the tallest panel,
		// so the page never jumps while visitors hover through the services.
		var wide = this.visual && window.getComputedStyle(this.visual).display !== 'none';
		if (wide) {
			var gap = parseFloat(window.getComputedStyle(list).rowGap) || 0;
			var tallest = 0;
			var rows = 0;
			var padding = parseFloat(window.getComputedStyle(list).paddingTop) + parseFloat(window.getComputedStyle(list).paddingBottom);
			this.items.forEach(function (item, i) {
				var heading = item.firstElementChild;
				var inner = item.querySelector('.avix-sv__panel-inner');
				rows += heading ? heading.offsetHeight : 0;
				tallest = Math.max(tallest, inner ? inner.scrollHeight : 0);
				if (i) {
					rows += gap;
				}
			});
			list.style.minHeight = Math.ceil(rows + tallest + padding) + 'px';
		} else {
			list.style.minHeight = '';
		}

		this.placeDots();

		if (this.ctx) {
			var w = this.canvas.parentNode.offsetWidth;
			var h = list.offsetHeight;
			if (w && h && (w !== this.canvas.width || h !== this.canvas.height)) {
				this.canvas.width = w;
				this.canvas.height = h;
			}
			this.color = window.getComputedStyle(this.root).getPropertyValue('--sv-accent').trim() || '#fb6007';
		}

		if (this.spot) {
			var rootRect = this.root.getBoundingClientRect();
			var listRect = list.getBoundingClientRect();
			this.spotX = listRect.left - rootRect.left + list.clientWidth * 0.32;
			this.spotY = listRect.top - rootRect.top;
		}

		this.avatarH = this.avatar ? this.avatar.offsetHeight : 0;
		if (this.avatar && this.y === null) {
			// Parked above the rail until the section is first seen, then it flies in.
			this.avatar.style.translate = '0 ' + (-40 - this.avatarH / 2) + 'px';
		}
	};

	/* ------------------------------------------------------------------ */
	/* Run / pause                                                         */
	/* ------------------------------------------------------------------ */

	Showcase.prototype.update = function () {
		if (!this.alive) {
			return;
		}
		if (!this.root.isConnected) {
			this.destroy();
			return;
		}
		var running = this.visible && !document.hidden;
		this.root.classList.toggle('is-paused', this.known && !running);
		if (running === this.running) {
			return;
		}
		this.running = running;
		if (!running) {
			if (this.frame) {
				window.cancelAnimationFrame(this.frame);
				this.frame = 0;
			}
			this.lastTime = 0;
			return;
		}

		if (!this.seen) {
			// First time on screen: rows glide in, the avatar flies down to its service.
			this.seen = true;
			this.root.classList.add('is-in');
			this.readCenters();
			var target = this.centers[this.active] || 0;
			this.y = this.editor || reduceMotion.matches ? target : -40;
			this.v = 0;
			this.settleUntil = now() + SETTLE_MS;
		}
		this.wake();
	};

	Showcase.prototype.wake = function () {
		if (this.alive && this.running && !this.frame) {
			this.frame = window.requestAnimationFrame(this.tick);
		}
	};

	Showcase.prototype.later = function (name, fn, delay) {
		var self = this;
		this.cancel(name);
		this.timers[name] = window.setTimeout(function () {
			delete self.timers[name];
			if (self.alive) {
				fn();
			}
		}, delay);
	};

	Showcase.prototype.cancel = function (name) {
		if (this.timers[name]) {
			window.clearTimeout(this.timers[name]);
			delete this.timers[name];
		}
	};

	/* ------------------------------------------------------------------ */
	/* Frame loop                                                          */
	/* ------------------------------------------------------------------ */

	Showcase.prototype.tick = function (time) {
		this.frame = 0;
		if (!this.root.isConnected) {
			this.destroy();
			return;
		}
		if (!this.running) {
			return;
		}

		var dt = this.lastTime ? Math.min(0.05, (time - this.lastTime) / 1000) : 1 / 60;
		this.lastTime = time;
		var busy = false;

		// ---- Reads ----
		if (time < this.settleUntil) {
			this.readCenters(); // names slide while panels open and close
			busy = true;
		}
		var slide = time < this.settleUntil + 40;
		var visualRect = this.tilt.on || this.tilt.live ? this.visual.getBoundingClientRect() : null;

		// ---- Writes ----
		if (slide) {
			this.placeDots();
		}

		// ---- Avatar ----
		if (this.avatar && this.y !== null) {
			busy = this.fly(dt, time) || busy;
		}

		// ---- Card tilt + glare ----
		if (visualRect) {
			busy = this.tiltCard(dt, visualRect) || busy;
		}

		if (busy) {
			this.frame = window.requestAnimationFrame(this.tick);
		} else {
			this.lastTime = 0;
		}
	};

	Showcase.prototype.fly = function (dt, time) {
		var target = this.centers[this.active] || 0;
		var calm = reduceMotion.matches;

		if (calm) {
			this.y = target;
			this.v = 0;
		} else {
			var accel = SPRING * (target - this.y) - DAMPING * this.v;
			this.v += accel * dt;
			this.y += this.v * dt;
		}

		var speed = Math.abs(this.v);
		var settled = Math.abs(target - this.y) < 0.4 && speed < 6 && time >= this.settleUntil;
		if (settled) {
			if (this.pose !== 'point' && this.pose !== '' && !calm) {
				this.puff();
			}
			this.y = target;
			this.v = 0;
			speed = 0;
		}

		// Pose + squash & stretch.
		var pose = settled ? 'point' : this.v < -70 ? 'up' : this.v > 70 ? 'down' : 'hover';
		if (pose !== this.pose) {
			this.pose = pose;
			this.avatar.classList.toggle('is-pointing', pose === 'point');
			this.avatar.classList.toggle('is-up', pose === 'up');
			this.avatar.classList.toggle('is-down', pose === 'down');
		}
		var stretch = Math.min(0.2, speed / 2600);
		var h = this.avatarH || 0;
		this.avatar.style.translate = '0 ' + (this.y - h / 2).toFixed(2) + 'px';
		this.avatar.style.scale = stretch > 0.004 ? (1 - stretch * 0.55).toFixed(3) + ' ' + (1 + stretch).toFixed(3) : '';

		// Rail fills up to the avatar; the spotlight follows it.
		if (this.fill && this.listH) {
			this.fill.style.transform = 'scaleY(' + clamp(this.y / this.listH, 0, 1).toFixed(4) + ')';
		}
		if (this.spot) {
			this.spot.style.translate = this.spotX.toFixed(1) + 'px ' + (this.spotY + this.y).toFixed(1) + 'px';
		}
		var lit = settled ? this.active : -1;
		if (lit !== this.lit) {
			this.lit = lit;
			for (var d = 0; d < this.dots.length; d++) {
				this.dots[d].classList.toggle('is-lit', d === lit);
			}
		}

		var busy = !settled;
		if (this.boots && !calm) {
			busy = this.trail(dt, speed, h) || busy;
		}
		return busy;
	};

	// Afterimages + exhaust particles while flying.
	Showcase.prototype.trail = function (dt, speed, h) {
		this.history.unshift(this.y);
		if (this.history.length > 12) {
			this.history.length = 12;
		}
		var ghostsOn = false;
		for (var g = 0; g < this.ghosts.length; g++) {
			var lag = this.history[(g + 1) * 4];
			var alpha = speed > 90 && lag !== undefined ? Math.min(0.4, speed / 2200) * (1 - g * 0.45) : 0;
			this.ghosts[g].style.opacity = alpha ? alpha.toFixed(3) : '';
			if (alpha) {
				this.ghosts[g].style.translate = '0 ' + (lag - h / 2).toFixed(2) + 'px';
				ghostsOn = true;
			}
		}

		if (!this.ctx) {
			return ghostsOn;
		}

		// Exhaust comes out of the boots, more of it the faster it flies.
		var up = this.v < 0;
		this.emitAcc += dt * Math.min(70, speed * (up ? 0.07 : 0.025));
		var feet = this.y + h / 2 - 1;
		var cx = this.canvas.width / 2;
		var spread = h * 0.28;
		while (this.emitAcc >= 1) {
			this.emitAcc -= 1;
			this.spawn(cx + (Math.random() < 0.5 ? -spread : spread) * (0.6 + Math.random() * 0.4), feet, up ? 70 + Math.random() * 110 : 25 + Math.random() * 40);
		}

		var ctx = this.ctx;
		ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
		var alive = [];
		for (var i = 0; i < this.particles.length; i++) {
			var p = this.particles[i];
			p.age += dt;
			if (p.age >= p.life) {
				continue;
			}
			p.x += p.vx * dt;
			p.y += p.vy * dt;
			p.vy *= 0.96;
			ctx.globalAlpha = 1 - p.age / p.life;
			ctx.fillStyle = p.hot ? '#ffd07a' : this.color;
			ctx.fillRect(Math.round(p.x), Math.round(p.y), p.size, p.size);
			alive.push(p);
		}
		ctx.globalAlpha = 1;
		this.particles = alive;
		return ghostsOn || alive.length > 0;
	};

	Showcase.prototype.spawn = function (x, y, vy) {
		if (this.particles.length > 120) {
			return;
		}
		this.particles.push({
			x: x,
			y: y,
			vx: (Math.random() - 0.5) * 24,
			vy: vy,
			age: 0,
			life: 0.35 + Math.random() * 0.35,
			size: Math.random() < 0.55 ? 2 : 3,
			hot: Math.random() < 0.4
		});
	};

	// Little landing puff under the boots.
	Showcase.prototype.puff = function () {
		if (!this.ctx) {
			return;
		}
		var feet = this.y + (this.avatarH || 0) / 2;
		for (var i = 0; i < 8; i++) {
			var side = i % 2 ? 1 : -1;
			this.particles.push({ x: this.canvas.width / 2 + side * (2 + Math.random() * 6), y: feet, vx: side * (30 + Math.random() * 40), vy: 10 + Math.random() * 20, age: 0, life: 0.3 + Math.random() * 0.2, size: 2, hot: i < 3 });
		}
	};

	Showcase.prototype.tiltCard = function (dt, rect) {
		var tilt = this.tilt;
		var nx = 0;
		var ny = 0;
		if (tilt.on) {
			nx = clamp((tilt.px - rect.left) / rect.width * 2 - 1, -1, 1);
			ny = clamp((tilt.py - rect.top) / rect.height * 2 - 1, -1, 1);
		}
		var ease = 1 - Math.exp(-dt / 0.16);
		tilt.x += (-ny * 6 - tilt.x) * ease;
		tilt.y += (nx * 8 - tilt.y) * ease;

		var still = !tilt.on && Math.abs(tilt.x) < 0.02 && Math.abs(tilt.y) < 0.02;
		if (still) {
			tilt.x = tilt.y = 0;
			tilt.live = false;
			this.stage.style.transform = '';
			return false;
		}
		tilt.live = true;
		this.stage.style.transform = 'rotateY(' + (BASE_TILT.y + tilt.y).toFixed(2) + 'deg) rotateX(' + (BASE_TILT.x + tilt.x).toFixed(2) + 'deg) rotateZ(-1deg)';

		var card = this.cards[this.active];
		var glare = card && card.querySelector('.avix-sv__glare');
		if (glare) {
			glare.style.translate = (nx * rect.width * 0.45).toFixed(1) + 'px ' + (ny * rect.height * 0.45).toFixed(1) + 'px';
		}
		return true;
	};

	/* ------------------------------------------------------------------ */
	/* Teardown                                                            */
	/* ------------------------------------------------------------------ */

	Showcase.prototype.destroy = function () {
		if (!this.alive) {
			return;
		}
		this.alive = false;
		if (this.frame) {
			window.cancelAnimationFrame(this.frame);
		}
		Object.keys(this.timers).forEach(this.cancel, this);
		this.list.removeEventListener('click', this.onClick);
		this.list.removeEventListener('keydown', this.onKey);
		if (this.onListMove) {
			this.list.removeEventListener('pointermove', this.onListMove);
			this.list.removeEventListener('pointerleave', this.onListLeave);
		}
		if (this.onVisualMove) {
			this.visual.removeEventListener('pointermove', this.onVisualMove);
			this.visual.removeEventListener('pointerleave', this.onVisualLeave);
		}
		document.removeEventListener('visibilitychange', this.onVisibility);
		if (this.observer) {
			this.observer.disconnect();
		}
		if (this.resizeObserver) {
			this.resizeObserver.disconnect();
		}
		var index = instances.indexOf(this);
		if (index > -1) {
			instances.splice(index, 1);
		}
	};

	function mount(root) {
		if (!root || (root.__avixSv && root.__avixSv.alive)) {
			return;
		}
		// Editor re-renders replace the DOM; drop instances whose root is gone.
		instances.slice().forEach(function (instance) {
			if (!instance.root.isConnected) {
				instance.destroy();
			}
		});
		var showcase = new Showcase(root);
		if (showcase.alive) {
			root.__avixSv = showcase;
			instances.push(showcase);
		}
	}

	function mountAll(scope) {
		Array.prototype.forEach.call((scope || document).querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixServices = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-services.default', function ($scope) {
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
