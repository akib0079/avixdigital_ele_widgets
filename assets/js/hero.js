/*!
 * Avix Digital · Hero Banner
 * Dependency-free. The pixel trail and the mouse parallax share one rAF loop
 * that only runs while something is moving, and stops off screen or in a
 * hidden tab. The pixel avatar drops onto the headline, waves hello, then
 * chills: it watches the cursor and says hi again on hover, when the button
 * is hovered, or every few seconds until it has said everything.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-hero]';
	var instances = [];
	var mq = function (query) {
		return window.matchMedia ? window.matchMedia(query) : { matches: false };
	};
	var reduceMotion = mq('(prefers-reduced-motion: reduce)');
	var finePointer = mq('(hover: hover) and (pointer: fine)');
	var wideScreen = mq('(min-width: 1025px)');

	var LAND_AT = 650; // ms after mount: the headline has mostly arrived
	var LAND_MS = 900; // matches avix-hero-land in the CSS
	var SAY_MS = 3400; // how long a greeting stays up
	var LINGER_MS = 1200; // bubble stays this long after the pointer leaves
	var RIPPLE_MS = 650;
	var EDGE = 12; // px kept free between the bubble / tags and the banner edge

	function clamp(value, min, max) {
		return value < min ? min : value > max ? max : value;
	}

	function now() {
		return window.performance ? window.performance.now() : Date.now();
	}

	function isEditMode() {
		return !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
	}

	function Hero(root) {
		var config = {};
		try {
			config = JSON.parse(root.getAttribute('data-avix-hero') || '{}') || {};
		} catch (error) {
			config = {};
		}

		var greet = config.greet || {};
		var trail = config.trail || null;

		this.root = root;
		this.alive = true;
		this.editor = isEditMode();
		this.mountedAt = now();
		this.parallax = Math.max(0, parseFloat(config.parallax) || 0);
		this.trail = trail ? {
			size: clamp(parseInt(trail.size, 10) || 40, 16, 96),
			radius: clamp(parseInt(trail.radius, 10) || 2, 1, 5),
			strength: clamp(parseFloat(trail.strength) || 0.3, 0.05, 0.8)
		} : null;

		this.canvas = root.querySelector('[data-hero-trail]');
		this.buddy = root.querySelector('[data-hero-buddy]');
		this.sprite = this.buddy ? this.buddy.querySelector('.avix-hero__sprite') : null;
		this.say = root.querySelector('[data-hero-say]');
		this.cta = root.querySelector('[data-hero-cta]');
		this.title = root.querySelector('.avix-hero__title');
		this.tags = Array.prototype.slice.call(root.querySelectorAll('.avix-hero__tag'));
		this.layers = Array.prototype.map.call(root.querySelectorAll('[data-hero-depth]'), function (element) {
			var depth = (element.getAttribute('data-hero-depth') || '').split(/\s+/);
			return { el: element, x: parseFloat(depth[0]) || 0, y: parseFloat(depth[1]) || 0 };
		});

		this.lines = (Array.isArray(greet.lines) ? greet.lines : []).filter(function (line) {
			return typeof line === 'string' && line.trim();
		});
		this.buttonLine = typeof greet.button === 'string' ? greet.button : '';
		this.every = Math.max(0, parseFloat(greet.every) || 0) * 1000;
		this.nextLine = 0;

		this.visible = !('IntersectionObserver' in window);
		this.known = this.visible;
		this.running = false;
		this.state = this.buddy ? 'armed' : 'none';
		this.pointer = null;
		this.local = null;
		this.moved = false;
		this.norm = { tx: 0, ty: 0, x: 0, y: 0 };
		this.parallaxLive = false;
		this.ripples = [];
		this.cells = null;
		this.lit = false;
		this.dirty = false;
		this.frame = 0;
		this.lastTime = 0;
		this.timers = {};
		this.look = '';
		this.hovering = false;
		this.held = false;

		this.tick = this.tick.bind(this);
		this.init();
	}

	Hero.prototype.init = function () {
		var self = this;

		if (this.editor) {
			this.root.classList.add('is-editor');
		}

		if (this.canvas && this.trail && this.canvas.getContext) {
			this.ctx = this.canvas.getContext('2d');
		}

		this.onPointerMove = function (event) {
			if (event.pointerType === 'touch') {
				return;
			}
			self.pointer = { x: event.clientX, y: event.clientY };
			self.moved = true;
			self.wake();
		};
		this.onPointerLeave = function () {
			self.pointer = null;
			self.local = null;
			self.unwatch();
			self.wake();
		};
		this.onPointerDown = function (event) {
			if (self.ctx && !reduceMotion.matches) {
				var rect = self.root.getBoundingClientRect();
				self.ripples.push({ x: event.clientX - rect.left, y: event.clientY - rect.top, t: now() });
				self.wake();
			}
		};
		this.root.addEventListener('pointermove', this.onPointerMove, { passive: true });
		this.root.addEventListener('pointerleave', this.onPointerLeave, { passive: true });
		this.root.addEventListener('pointerdown', this.onPointerDown, { passive: true });

		this.onVisibility = function () {
			self.update();
		};
		document.addEventListener('visibilitychange', this.onVisibility);

		if ('IntersectionObserver' in window) {
			this.observer = new window.IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					self.visible = entry.isIntersecting;
				});
				self.known = true;
				self.update();
			}, { threshold: 0.15 });
			this.observer.observe(this.root);
		}

		if (window.ResizeObserver) {
			this.resizeObserver = new window.ResizeObserver(function () {
				self.later('resize', function () {
					self.resize();
				}, 60);
			});
			this.resizeObserver.observe(this.root);
		}
		if (document.fonts && document.fonts.ready) {
			document.fonts.ready.then(function () {
				if (self.alive) {
					self.seat();
					self.placeTags();
				}
			});
		}

		this.initBuddy();
		this.resize();
		this.update();
	};

	/* ------------------------------------------------------------------ */
	/* Run / pause                                                         */
	/* ------------------------------------------------------------------ */

	Hero.prototype.update = function () {
		if (!this.alive) {
			return;
		}
		if (!this.root.isConnected) {
			this.destroy();
			return;
		}
		var running = this.visible && !document.hidden;
		// Off screen, CSS animations (zoom, floats, the avatar) pause too. Only once
		// visibility is known: never hold the headline's entrance (LCP) on this script.
		this.root.classList.toggle('is-paused', this.known && !running);
		if (running === this.running) {
			return;
		}
		this.running = running;

		if (running) {
			this.wake();
			this.resume();
			return;
		}

		if (this.frame) {
			window.cancelAnimationFrame(this.frame);
			this.frame = 0;
		}
		this.lastTime = 0;
		this.pauseBuddy();
	};

	Hero.prototype.wake = function () {
		if (this.alive && this.running && !this.frame) {
			this.frame = window.requestAnimationFrame(this.tick);
		}
	};

	Hero.prototype.later = function (name, fn, delay) {
		var self = this;
		this.cancel(name);
		this.timers[name] = window.setTimeout(function () {
			delete self.timers[name];
			if (self.alive) {
				fn();
			}
		}, delay);
	};

	Hero.prototype.cancel = function (name) {
		if (this.timers[name]) {
			window.clearTimeout(this.timers[name]);
			delete this.timers[name];
		}
	};

	/* ------------------------------------------------------------------ */
	/* Frame loop: parallax, trail, cursor watching                        */
	/* ------------------------------------------------------------------ */

	Hero.prototype.tick = function (time) {
		this.frame = 0;
		if (!this.root.isConnected) {
			this.destroy();
			return;
		}
		if (!this.running) {
			return;
		}

		var dt = this.lastTime ? Math.min(0.1, (time - this.lastTime) / 1000) : 1 / 60;
		this.lastTime = time;

		// Reads first, writes after: no forced style recalcs mid-frame.
		var rect = this.root.getBoundingClientRect();
		var pointer = this.pointer;
		var watch = pointer && this.moved && this.sprite && this.state === 'ready' ? this.sprite.getBoundingClientRect() : null;
		var busy = false;

		// ---- Parallax ----
		var parallax = this.parallax > 0 && finePointer.matches && wideScreen.matches && !reduceMotion.matches;
		if (parallax) {
			var norm = this.norm;
			norm.tx = pointer ? clamp((pointer.x - rect.left) / (rect.width / 2) - 1, -1, 1) : 0;
			norm.ty = pointer ? clamp((pointer.y - rect.top) / (rect.height / 2) - 1, -1, 1) : 0;
			var ease = 1 - Math.exp(-dt / 0.33);
			norm.x += (norm.tx - norm.x) * ease;
			norm.y += (norm.ty - norm.y) * ease;
			if (Math.abs(norm.tx - norm.x) < 0.0005 && Math.abs(norm.ty - norm.y) < 0.0005) {
				norm.x = norm.tx;
				norm.y = norm.ty;
			} else {
				busy = true;
			}
			this.applyParallax(rect.width);
		} else if (this.parallaxLive) {
			this.norm.x = this.norm.y = 0;
			this.applyParallax(rect.width);
		}

		// ---- Pixel trail ----
		if (this.ctx && this.cells && !reduceMotion.matches) {
			if (pointer && this.moved) {
				this.trace(pointer.x - rect.left, pointer.y - rect.top);
			}
			if (this.ripples.length) {
				this.spread(time);
				busy = true;
			}
			busy = this.draw(dt) || busy;
		}

		// ---- The avatar watches the cursor ----
		if (watch) {
			this.watch(pointer, watch);
		}

		this.moved = false;
		if (busy) {
			this.frame = window.requestAnimationFrame(this.tick);
		} else {
			this.lastTime = 0;
		}
	};

	Hero.prototype.applyParallax = function (width) {
		var scale = this.parallax * width / 100;
		var x = this.norm.x;
		var y = this.norm.y;
		var still = Math.abs(x) < 0.0005 && Math.abs(y) < 0.0005;
		for (var i = 0; i < this.layers.length; i++) {
			var layer = this.layers[i];
			layer.el.style.translate = still ? '' : (x * layer.x * scale).toFixed(2) + 'px ' + (y * layer.y * scale).toFixed(2) + 'px';
		}
		this.parallaxLive = !still;
	};

	// Lights the squares between the last and current pointer position, so fast moves leave no gaps.
	Hero.prototype.trace = function (x, y) {
		var radius = this.trail.radius;
		var last = this.local;
		if (last) {
			var dx = x - last.x;
			var dy = y - last.y;
			var steps = Math.min(24, Math.floor(Math.sqrt(dx * dx + dy * dy) / (this.trail.size * 0.75)));
			for (var s = 1; s <= steps; s++) {
				this.light(last.x + dx * s / (steps + 1), last.y + dy * s / (steps + 1), radius);
			}
		}
		this.light(x, y, radius);
		this.local = { x: x, y: y };
	};

	Hero.prototype.light = function (x, y, radius) {
		var size = this.trail.size;
		var col = Math.floor(x / size);
		var row = Math.floor(y / size);
		for (var i = -radius; i <= radius; i++) {
			for (var j = -radius; j <= radius; j++) {
				var c = col + i;
				var r = row + j;
				var distance = Math.sqrt(i * i + j * j);
				if (c < 0 || r < 0 || c >= this.cols || r >= this.rows || distance > radius) {
					continue;
				}
				var value = 1 - distance / (radius + 1);
				var index = c + r * this.cols;
				if (this.cells[index] < value) {
					this.cells[index] = value;
				}
			}
		}
		this.lit = true;
	};

	// Tap / click ripple: a ring of squares that grows and fades.
	Hero.prototype.spread = function (time) {
		var size = this.trail.size;
		var keep = [];
		for (var k = 0; k < this.ripples.length; k++) {
			var ripple = this.ripples[k];
			var age = (time - ripple.t) / RIPPLE_MS;
			if (age >= 1) {
				continue;
			}
			keep.push(ripple);
			if (age < 0) {
				continue;
			}
			var ring = age * size * 5;
			var power = 1 - age;
			var span = Math.ceil(ring / size) + 1;
			var col = Math.floor(ripple.x / size);
			var row = Math.floor(ripple.y / size);
			for (var i = -span; i <= span; i++) {
				for (var j = -span; j <= span; j++) {
					var c = col + i;
					var r = row + j;
					if (c < 0 || r < 0 || c >= this.cols || r >= this.rows) {
						continue;
					}
					var cx = (c + 0.5) * size - ripple.x;
					var cy = (r + 0.5) * size - ripple.y;
					if (Math.abs(Math.sqrt(cx * cx + cy * cy) - ring) < size * 0.6) {
						var index = c + r * this.cols;
						if (this.cells[index] < power) {
							this.cells[index] = power;
						}
					}
				}
			}
			this.lit = true;
		}
		this.ripples = keep;
	};

	// Draws lit squares and fades them (time-based, so 120 Hz screens match 60 Hz).
	Hero.prototype.draw = function (dt) {
		var ctx = this.ctx;
		if (this.dirty) {
			ctx.clearRect(0, 0, this.w, this.h);
			this.dirty = false;
		}
		if (!this.lit) {
			return false;
		}

		var cells = this.cells;
		var cols = this.cols;
		var size = this.trail.size;
		var decay = Math.pow(0.93, dt * 60);
		var any = false;

		ctx.fillStyle = this.color;
		for (var i = 0; i < cells.length; i++) {
			var value = cells[i];
			if (value > 0.01) {
				ctx.globalAlpha = value * this.trail.strength;
				ctx.fillRect((i % cols) * size, Math.floor(i / cols) * size, size - 1, size - 1);
				cells[i] = value * decay;
				any = true;
			} else if (value) {
				cells[i] = 0;
			}
		}
		ctx.globalAlpha = 1;
		this.dirty = any;
		this.lit = any;
		return any;
	};

	Hero.prototype.resize = function () {
		if (!this.alive) {
			return;
		}
		var w = this.root.clientWidth;
		var h = this.root.clientHeight;

		if (this.ctx && w && h && (w !== this.w || h !== this.h)) {
			this.w = w;
			this.h = h;
			this.canvas.width = w;
			this.canvas.height = h;
			this.cols = Math.ceil(w / this.trail.size);
			this.rows = Math.ceil(h / this.trail.size);
			this.cells = new Float32Array(this.cols * this.rows);
			this.lit = false;
			this.dirty = false;
			this.local = null;
		}
		if (this.ctx) {
			this.color = window.getComputedStyle(this.root).getPropertyValue('--hero-trail').trim() || '#fb6007';
		}
		this.seat();
		this.placeTags();
	};

	/* ------------------------------------------------------------------ */
	/* Seat: over a lowercase letter the avatar sits on the x-height       */
	/* ------------------------------------------------------------------ */

	Hero.prototype.seat = function () {
		if (!this.buddy) {
			return;
		}
		var perch = this.buddy.parentNode;
		var box = this.buddy.getBoundingClientRect();
		var x = box.left + box.width / 2;
		var letter = '';
		var range = document.createRange();
		var walker = document.createTreeWalker(perch, window.NodeFilter.SHOW_TEXT, null);

		while (!letter && walker.nextNode()) {
			var node = walker.currentNode;
			if (this.buddy.contains(node)) {
				continue;
			}
			for (var i = 0; i < node.nodeValue.length; i++) {
				range.setStart(node, i);
				range.setEnd(node, i + 1);
				var rect = range.getBoundingClientRect();
				if (x >= rect.left && x < rect.right) {
					letter = node.nodeValue.charAt(i);
					break;
				}
			}
		}

		var drop = 0;
		if (/[acegijmnopqrsuvwxyz]/.test(letter)) {
			var style = window.getComputedStyle(perch);
			var ruler = Hero.ruler || (Hero.ruler = document.createElement('canvas').getContext('2d'));
			if (ruler) {
				ruler.font = style.fontStyle + ' ' + style.fontWeight + ' ' + style.fontSize + ' ' + style.fontFamily;
				drop = ruler.measureText('H').actualBoundingBoxAscent - ruler.measureText('x').actualBoundingBoxAscent;
			}
		}
		if (drop > 0.5) {
			this.buddy.style.setProperty('--hero-seat-drop', drop.toFixed(1) + 'px');
		} else {
			drop = 0;
			this.buddy.style.removeProperty('--hero-seat-drop');
		}
		// Head centre (row 1.5 of 11) measured up from the bubble's bottom edge.
		this.buddy.style.setProperty('--hero-say-tail', (box.height * 0.364 - drop).toFixed(1) + 'px');
	};

	/* ------------------------------------------------------------------ */
	/* Floating tags: just outside the first and last line of the headline */
	/* ------------------------------------------------------------------ */

	Hero.prototype.placeTags = function () {
		if (!this.tags.length || !this.title || window.getComputedStyle(this.tags[0]).display === 'none') {
			return;
		}
		var lines = this.lineBoxes();
		if (!lines.length) {
			return;
		}
		var head = this.title.parentNode.getBoundingClientRect();
		var bounds = this.root.getBoundingClientRect();
		var gap = 28;

		this.tags.forEach(function (tag) {
			var first = tag.classList.contains('avix-hero__tag--1');
			var width = tag.offsetWidth;
			var x = first ? lines[0].left - gap - width : lines[lines.length - 1].right + gap;
			var fits = first ? x >= bounds.left + EDGE : x + width <= bounds.right - EDGE;
			tag.style.setProperty('--hero-tag-x', (x - head.left).toFixed(1) + 'px');
			tag.classList.add('is-placed');
			tag.classList.toggle('is-cramped', !fits);
		});
	};

	// Visual line boxes of the headline text (and inline icons), top to bottom.
	Hero.prototype.lineBoxes = function () {
		var lines = [];
		var add = function (rect) {
			if (rect.width < 1 || rect.height < 1) {
				return;
			}
			for (var i = 0; i < lines.length; i++) {
				var line = lines[i];
				if (rect.top < line.bottom - rect.height / 2 && rect.bottom > line.top + rect.height / 2) {
					line.left = Math.min(line.left, rect.left);
					line.right = Math.max(line.right, rect.right);
					return;
				}
			}
			lines.push({ top: rect.top, bottom: rect.bottom, left: rect.left, right: rect.right });
		};

		var range = document.createRange();
		var walker = document.createTreeWalker(this.title, window.NodeFilter.SHOW_TEXT, null);
		while (walker.nextNode()) {
			if (!/\S/.test(walker.currentNode.nodeValue)) {
				continue;
			}
			range.selectNodeContents(walker.currentNode);
			Array.prototype.forEach.call(range.getClientRects(), add);
		}
		Array.prototype.forEach.call(this.title.querySelectorAll('.avix-hero__icon, .avix-hero__mark'), function (element) {
			add(element.getBoundingClientRect());
		});

		return lines.sort(function (a, b) {
			return a.top - b.top;
		});
	};

	/* ------------------------------------------------------------------ */
	/* The pixel avatar                                                    */
	/* ------------------------------------------------------------------ */

	Hero.prototype.initBuddy = function () {
		if (!this.buddy) {
			return;
		}
		var self = this;

		this.onBuddyEnter = function (event) {
			if (event.pointerType === 'mouse') {
				self.hovering = true;
				self.hello(self.takeLine(), true);
			}
		};
		this.onBuddyLeave = function (event) {
			if (event.pointerType === 'mouse') {
				self.hovering = false;
				self.release();
			}
		};
		// Taps, and clicks for the curious: the next greeting.
		this.onBuddyClick = function () {
			self.hello(self.takeLine(), self.hovering);
		};
		this.buddy.addEventListener('pointerenter', this.onBuddyEnter);
		this.buddy.addEventListener('pointerleave', this.onBuddyLeave);
		this.buddy.addEventListener('click', this.onBuddyClick);

		if (this.cta) {
			this.onCtaEnter = function (event) {
				if (!event.pointerType || event.pointerType === 'mouse') {
					self.hello(self.buttonLine || self.takeLine(), true);
				}
			};
			this.onCtaLeave = function (event) {
				if (!event.pointerType || event.pointerType === 'mouse') {
					self.release();
				}
			};
			this.cta.addEventListener('pointerenter', this.onCtaEnter);
			this.cta.addEventListener('pointerleave', this.onCtaLeave);
			this.cta.addEventListener('focus', this.onCtaEnter);
			this.cta.addEventListener('blur', this.onCtaLeave);
		}

		// No drop-in for reduced motion, inside the editor, or without IntersectionObserver.
		if (reduceMotion.matches || this.editor || !('IntersectionObserver' in window)) {
			this.state = 'arriving';
		} else {
			this.root.classList.add('is-armed');
		}
	};

	// Called whenever the banner is on screen and the tab visible.
	Hero.prototype.resume = function () {
		var self = this;
		if (this.state === 'armed') {
			this.state = 'landing';
			this.later('land', function () {
				self.buddy.classList.add('is-landed');
				self.later('arrive', function () {
					self.arrive();
				}, LAND_MS - 150);
			}, Math.max(150, this.mountedAt + LAND_AT - now()));
		} else if (this.state === 'arriving') {
			this.later('arrive', function () {
				self.arrive();
			}, this.editor ? 300 : 800);
		} else if (this.state === 'ready') {
			this.scheduleNext();
		}
	};

	Hero.prototype.pauseBuddy = function () {
		['land', 'arrive', 'next', 'quiet'].forEach(this.cancel, this);
		if (this.state === 'landing') {
			// Mid-drop when it left the screen: finish quietly and say hi on return.
			this.buddy.classList.add('is-landed');
			this.state = 'arriving';
		}
		if (this.buddy && !this.held) {
			this.buddy.classList.remove('is-waving', 'is-saying');
		}
	};

	Hero.prototype.arrive = function () {
		this.state = 'ready';
		this.hello(this.takeLine(), false);
	};

	Hero.prototype.takeLine = function () {
		if (!this.lines.length) {
			return '';
		}
		var line = this.lines[this.nextLine % this.lines.length];
		this.nextLine++;
		return line;
	};

	/**
	 * Wave, and show text in the bubble if there is one. hold = keep it up
	 * until release() (hover / focus); otherwise it quiets down on its own.
	 */
	Hero.prototype.hello = function (text, hold) {
		if (!this.buddy || this.state !== 'ready') {
			return;
		}
		var self = this;
		this.held = !!hold;
		this.cancel('next');
		this.cancel('quiet');
		this.buddy.classList.add('is-waving');

		if (this.say && text) {
			this.say.setAttribute('data-say', text);
			this.fitBubble();
			this.buddy.classList.add('is-saying');
		} else if (this.say) {
			this.buddy.classList.remove('is-saying');
		}

		if (!hold) {
			this.later('quiet', function () {
				self.quiet();
			}, SAY_MS);
		}
	};

	Hero.prototype.release = function () {
		var self = this;
		if (!this.held) {
			return;
		}
		this.held = false;
		this.later('quiet', function () {
			self.quiet();
		}, LINGER_MS);
	};

	Hero.prototype.quiet = function () {
		this.buddy.classList.remove('is-waving', 'is-saying');
		this.scheduleNext();
	};

	// Each greeting gets said once on its own; after that it just chills.
	Hero.prototype.scheduleNext = function () {
		var self = this;
		if (!this.every || this.editor || reduceMotion.matches || this.held || !this.running || this.nextLine >= this.lines.length) {
			return;
		}
		this.later('next', function () {
			self.hello(self.takeLine(), false);
		}, this.every);
	};

	// Right of the avatar when it fits, else left; wraps the text if neither side has room.
	Hero.prototype.fitBubble = function () {
		var say = this.say;
		var gap = 10;
		say.style.maxWidth = '';
		this.buddy.classList.remove('is-say-left');
		var width = say.offsetWidth;
		var buddy = this.buddy.getBoundingClientRect();
		var bounds = this.root.getBoundingClientRect();
		var right = bounds.right - EDGE - buddy.right - gap;
		var left = buddy.left - gap - bounds.left - EDGE;
		var flip = width > right && left > right;
		var room = flip ? left : right;
		this.buddy.classList.toggle('is-say-left', flip);
		if (width > room) {
			say.style.maxWidth = Math.max(96, Math.floor(room)) + 'px';
		}
	};

	Hero.prototype.watch = function (pointer, rect) {
		var dx = pointer.x - (rect.left + rect.width / 2);
		var look = dx < -rect.width ? 'l' : dx > rect.width ? 'r' : '';
		if (!this.watching) {
			this.watching = true;
			this.buddy.classList.add('is-watching');
		}
		if (look !== this.look) {
			this.look = look;
			this.buddy.classList.toggle('is-look-l', look === 'l');
			this.buddy.classList.toggle('is-look-r', look === 'r');
		}
	};

	Hero.prototype.unwatch = function () {
		if (!this.buddy || !this.watching) {
			return;
		}
		this.watching = false;
		this.look = '';
		this.buddy.classList.remove('is-watching', 'is-look-l', 'is-look-r');
	};

	/* ------------------------------------------------------------------ */
	/* Teardown                                                            */
	/* ------------------------------------------------------------------ */

	Hero.prototype.destroy = function () {
		if (!this.alive) {
			return;
		}
		this.alive = false;
		if (this.frame) {
			window.cancelAnimationFrame(this.frame);
		}
		Object.keys(this.timers).forEach(this.cancel, this);
		this.root.removeEventListener('pointermove', this.onPointerMove);
		this.root.removeEventListener('pointerleave', this.onPointerLeave);
		this.root.removeEventListener('pointerdown', this.onPointerDown);
		document.removeEventListener('visibilitychange', this.onVisibility);
		if (this.observer) {
			this.observer.disconnect();
		}
		if (this.resizeObserver) {
			this.resizeObserver.disconnect();
		}
		if (this.buddy) {
			this.buddy.removeEventListener('pointerenter', this.onBuddyEnter);
			this.buddy.removeEventListener('pointerleave', this.onBuddyLeave);
			this.buddy.removeEventListener('click', this.onBuddyClick);
		}
		if (this.cta && this.onCtaEnter) {
			this.cta.removeEventListener('pointerenter', this.onCtaEnter);
			this.cta.removeEventListener('pointerleave', this.onCtaLeave);
			this.cta.removeEventListener('focus', this.onCtaEnter);
			this.cta.removeEventListener('blur', this.onCtaLeave);
		}
		var index = instances.indexOf(this);
		if (index > -1) {
			instances.splice(index, 1);
		}
	};

	function mount(root) {
		if (!root || (root.__avixHero && root.__avixHero.alive)) {
			return;
		}
		// Editor re-renders replace the DOM; drop instances whose root is gone.
		instances.slice().forEach(function (instance) {
			if (!instance.root.isConnected) {
				instance.destroy();
			}
		});
		var hero = new Hero(root);
		if (hero.alive) {
			root.__avixHero = hero;
			instances.push(hero);
		}
	}

	function mountAll(scope) {
		Array.prototype.forEach.call((scope || document).querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixHero = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-hero.default', function ($scope) {
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
