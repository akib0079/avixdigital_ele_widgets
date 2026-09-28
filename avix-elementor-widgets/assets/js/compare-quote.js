/*!
 * Avix Digital · Compare & CEO Quote
 * Pixel Akib on rocket boots: a 2D spring carries it between the VS badge,
 * the rows (where it zaps the old habit and cheers the Avix one) and the CEO
 * photo. One animation loop that sleeps whenever it has landed.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-cq]';
	var SPRING = 170;
	var DAMPING = 19;
	var POSES = ['is-sit', 'is-hover', 'is-zap', 'is-cheer'];
	var reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };
	var finePointer = window.matchMedia ? window.matchMedia('(hover: hover) and (pointer: fine)') : { matches: true };

	function slice(list) {
		return Array.prototype.slice.call(list || []);
	}

	function clamp(value, min, max) {
		return value < min ? min : value > max ? max : value;
	}

	// Position inside `ancestor` from layout offsets, so reveal transforms
	// (the rows slide up as they appear) don't skew the measurement.
	function offsetIn(el, ancestor) {
		var x = 0;
		var y = 0;
		while (el && el !== ancestor) {
			x += el.offsetLeft;
			y += el.offsetTop;
			el = el.offsetParent;
			if (el && el !== ancestor) {
				x += el.clientLeft;
				y += el.clientTop;
			}
		}
		return { x: x, y: y };
	}

	function isEditMode() {
		return !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
	}

	function Showdown(root) {
		this.root = root;
		this.alive = true;
		this.timers = {};
		this.frame = root.querySelector('[data-cq-frame]');
		this.compare = root.querySelector('[data-cq-compare]');
		this.quote = root.querySelector('[data-cq-quote]');
		this.buddy = root.querySelector('[data-cq-buddy]');
		this.rows = slice(root.querySelectorAll('[data-cq-row]'));

		root.classList.add('is-live');
		this.observeReveal();
		this.observeVideo();
		if (this.buddy && this.frame) {
			this.initBuddy();
		}
	}

	/* ---------------------------------------------------------------- */
	/* Reveal, video, visibility                                         */
	/* ---------------------------------------------------------------- */

	Showdown.prototype.observeReveal = function () {
		var items = slice(this.root.querySelectorAll('[data-cq-reveal]'));
		if (!('IntersectionObserver' in window) || reduceMotion.matches || isEditMode()) {
			items.forEach(function (item) {
				item.classList.add('is-shown');
			});
			return;
		}
		this.revealObserver = new window.IntersectionObserver(function (entries, observer) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					entry.target.classList.add('is-shown');
					observer.unobserve(entry.target);
				}
			});
		}, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
		items.forEach(function (item) {
			this.revealObserver.observe(item);
		}, this);
	};

	// The looping video only downloads and plays while it is on screen.
	Showdown.prototype.observeVideo = function () {
		var video = this.root.querySelector('[data-cq-video]');
		if (!video || !('IntersectionObserver' in window)) {
			if (video && !reduceMotion.matches) {
				video.autoplay = true;
			}
			return;
		}
		this.videoObserver = new window.IntersectionObserver(function (entries) {
			var visible = entries[entries.length - 1].isIntersecting;
			if (visible && !reduceMotion.matches) {
				var playing = video.play();
				if (playing && playing.catch) {
					playing.catch(function () {});
				}
			} else if (!visible) {
				video.pause();
			}
		}, { rootMargin: '100px 0px' });
		this.videoObserver.observe(video);
	};

	/* ---------------------------------------------------------------- */
	/* Buddy setup                                                       */
	/* ---------------------------------------------------------------- */

	Showdown.prototype.initBuddy = function () {
		var self = this;
		var root = this.root;
		this.hit = this.buddy.querySelector('[data-cq-hit]');
		this.bob = this.buddy.querySelector('[data-cq-bob]');
		this.bubble = this.buddy.querySelector('[data-cq-bubble]');
		this.seatEl = root.querySelector('[data-cq-seat]');
		this.rowsEl = root.querySelector('[data-cq-rows]');
		this.perchEl = root.querySelector('[data-cq-perch]');
		try {
			this.say = JSON.parse(root.getAttribute('data-cq-say') || '{}') || {};
		} catch (error) {
			this.say = {};
		}
		this.say.lines = this.say.lines || [];

		this.x = 0;
		this.y = 0;
		this.vx = 0;
		this.vy = 0;
		this.tx = 0;
		this.ty = 0;
		this.kind = '';
		this.active = -1;
		this.home = this.seatEl ? 'seat' : 'perch';
		this.zapped = [];
		this.celebrated = false;
		this.clicks = 0;
		this.line = 0;
		this.seq = 0;
		this.loop = 0;
		this.last = 0;
		this.flying = false;
		this.inView = false;
		this.touched = false;
		this.perched = false;
		this.face = 'right';
		this.pointerX = null;
		this.sparks = [];
		this.sparkIndex = 0;
		this.lastSpark = 0;

		this.traveling = false;
		this.pendingZap = false;
		this.tick = this.tick.bind(this);
		this.onScroll = this.onScroll.bind(this);
		this.onPointer = this.onPointer.bind(this);
		this.onResize = function () {
			self.measure();
			self.retarget(!self.loop);
		};

		this.makeSparks();
		this.measure();
		this.retarget(true);
		root.classList.add('is-ready');

		// Rows: hover with a mouse, tap on touch.
		this.rows.forEach(function (row, index) {
			row.addEventListener('pointerenter', function (event) {
				if (event.pointerType === 'mouse') {
					self.activate(index);
				}
			});
			row.addEventListener('click', function (event) {
				if (event.pointerType === 'mouse' || (!event.pointerType && finePointer.matches)) {
					return;
				}
				self.activate(index);
				self.later('touch-home', 2600, function () {
					self.release();
				});
			});
		});
		if (this.rowsEl) {
			this.rowsEl.addEventListener('pointerleave', function (event) {
				if (event.pointerType === 'mouse') {
					self.later('leave', 420, function () {
						self.release();
					});
				}
			});
		}
		if (this.hit) {
			this.hit.addEventListener('click', function () {
				self.poke();
			});
		}

		window.addEventListener('scroll', this.onScroll, { passive: true });
		window.addEventListener('resize', this.onResize, { passive: true });
		window.addEventListener('pointermove', this.onPointer, { passive: true });
		if (window.ResizeObserver) {
			this.resizeObserver = new window.ResizeObserver(this.onResize);
			this.resizeObserver.observe(this.frame);
		}
		if ('IntersectionObserver' in window) {
			this.viewObserver = new window.IntersectionObserver(function (entries) {
				if (!root.isConnected) {
					self.destroy();
					return;
				}
				self.inView = entries[entries.length - 1].isIntersecting;
				root.classList.toggle('is-offscreen', !self.inView);
				if (self.inView) {
					self.onScroll();
					self.fidget();
				}
			}, { rootMargin: '80px 0px' });
			this.viewObserver.observe(root);

			if (this.compare && this.rows.length) {
				this.tourObserver = new window.IntersectionObserver(function (entries, observer) {
					if (entries[entries.length - 1].isIntersecting) {
						observer.disconnect();
						self.later('tour', 900, function () {
							self.tour();
						});
					}
				}, { threshold: 0.5 });
				this.tourObserver.observe(this.compare);
			}
		} else {
			this.inView = true;
		}
	};

	/* ---------------------------------------------------------------- */
	/* Geometry                                                          */
	/* ---------------------------------------------------------------- */

	Showdown.prototype.measure = function () {
		if (!this.alive) {
			return;
		}
		var frame = this.frame;
		this.frameLeft = frame.getBoundingClientRect().left;
		this.frameWidth = frame.offsetWidth;
		this.bw = this.buddy.offsetWidth || 30;
		this.bh = this.buddy.offsetHeight || 36;
		this.wide = !this.rows.length || window.getComputedStyle(this.rows[0]).getPropertyValue('--cq-rail').trim() !== 'narrow';

		this.seat = null;
		if (this.seatEl && this.seatEl.offsetWidth) {
			var vs = offsetIn(this.seatEl, frame);
			this.seat = { x: vs.x + this.seatEl.offsetWidth / 2, edge: vs.y };
		}
		this.perch = null;
		if (this.perchEl && this.perchEl.offsetWidth) {
			var photo = offsetIn(this.perchEl, frame);
			var width = this.perchEl.offsetWidth;
			this.perch = { x: photo.x + width - Math.min(36, width * 0.24), edge: photo.y };
		}
		this.points = [];
		if (this.rowsEl) {
			var list = offsetIn(this.rowsEl, frame);
			var railX = parseFloat(window.getComputedStyle(this.rowsEl).getPropertyValue('--cq-rail-x')) || 16;
			var x = this.wide ? list.x + this.rowsEl.offsetWidth / 2 : list.x + railX;
			this.points = this.rows.map(function (row) {
				var at = offsetIn(row, frame);
				var them = row.querySelector('.avix-cq__them');
				// Narrow: hover level with the struck line, which comes first.
				var y = this.wide || !them ? at.y + row.offsetHeight / 2 : offsetIn(them, frame).y + Math.min(11, them.offsetHeight / 2);
				return { x: x, y: y };
			}, this);
		}
	};

	// Sitting targets: the seat row (7 of 12) goes on the edge.
	Showdown.prototype.anchor = function (kind) {
		if (kind === 'seat' && this.seat) {
			return { x: this.seat.x, y: this.seat.edge - this.bh / 12, sit: true };
		}
		if (kind === 'perch' && this.perch) {
			return { x: this.perch.x, y: this.perch.edge - this.bh / 12, sit: true };
		}
		if (kind === 'row' && this.points[this.active]) {
			return { x: this.points[this.active].x, y: this.points[this.active].y, sit: false };
		}
		return this.seat ? this.anchor('seat') : this.perch ? this.anchor('perch') : null;
	};

	Showdown.prototype.retarget = function (snap) {
		var kind = this.active >= 0 ? 'row' : this.home;
		var point = this.anchor(kind);
		if (!point) {
			return;
		}
		this.kind = point.sit ? kind : 'row';
		this.tx = point.x;
		this.ty = point.y;
		// A resize re-aims at the same spot: that must not re-fire the zap.
		var key = this.kind + (this.kind === 'row' ? this.active : '');
		if (key !== this.targetKey) {
			this.targetKey = key;
			this.pendingZap = this.kind === 'row';
		}
		this.traveling = Math.abs(this.tx - this.x) + Math.abs(this.ty - this.y) > 4;
		if (snap || reduceMotion.matches) {
			this.x = this.tx;
			this.y = this.ty;
			this.vx = 0;
			this.vy = 0;
			this.render(0);
			this.arrive(false);
			return;
		}
		this.setSide();
		this.wake();
	};

	/* ---------------------------------------------------------------- */
	/* Motion                                                            */
	/* ---------------------------------------------------------------- */

	Showdown.prototype.wake = function () {
		if (!this.loop && this.alive) {
			this.last = 0;
			this.loop = window.requestAnimationFrame(this.tick);
		}
	};

	Showdown.prototype.tick = function (time) {
		this.loop = 0;
		if (!this.root.isConnected) {
			this.destroy();
			return;
		}
		var dt = this.last ? Math.min(1 / 30, (time - this.last) / 1000) : 1 / 60;
		this.last = time;

		this.vx += (SPRING * (this.tx - this.x) - DAMPING * this.vx) * dt;
		this.vy += (SPRING * (this.ty - this.y) - DAMPING * this.vy) * dt;
		this.x += this.vx * dt;
		this.y += this.vy * dt;

		var distance = Math.abs(this.tx - this.x) + Math.abs(this.ty - this.y);
		var speed = Math.abs(this.vx) + Math.abs(this.vy);
		if (distance < 0.4 && speed < 6) {
			this.x = this.tx;
			this.y = this.ty;
			this.vx = 0;
			this.vy = 0;
			var moved = this.traveling || this.hopped;
			this.hopped = false;
			this.render(0);
			this.arrive(moved);
			return;
		}
		this.render(speed);
		if (this.traveling && speed > 140 && time - this.lastSpark > 42) {
			this.lastSpark = time;
			this.spark(this.x + (Math.random() * 6 - 3), this.y + this.bh * 0.55, -this.vx * 0.05, 14 + Math.random() * 10, '#ffb347');
		}
		this.loop = window.requestAnimationFrame(this.tick);
	};

	Showdown.prototype.render = function (speed) {
		this.buddy.style.transform = 'translate3d(' + this.x.toFixed(1) + 'px,' + this.y.toFixed(1) + 'px,0)';
		var flying = this.traveling && speed > 60;
		if (flying) {
			var stretch = Math.min(speed / 2600, 0.2);
			var tilt = clamp(this.vx * 0.022, -16, 16);
			this.bob.style.transform = 'rotate(' + tilt.toFixed(1) + 'deg) scale(' + (1 - stretch / 2).toFixed(3) + ',' + (1 + stretch).toFixed(3) + ')';
			if (Math.abs(this.vx) > 60) {
				this.setFace(this.vx < 0 ? 'left' : 'right');
			}
		} else if (this.bob.style.transform) {
			this.bob.style.transform = '';
		}
		if (flying !== this.flying) {
			this.flying = flying;
			this.buddy.classList.toggle('is-fly', flying);
			if (flying) {
				this.setPose('is-hover');
			}
		}
		if (flying) {
			this.buddy.classList.toggle('is-rise', this.vy < -40);
			this.buddy.classList.toggle('is-fall', this.vy > 40);
		}
	};

	Showdown.prototype.arrive = function (moved) {
		var traveled = this.traveling;
		this.traveling = false;
		this.flying = false;
		this.buddy.classList.remove('is-fly', 'is-rise', 'is-fall');
		if (this.kind === 'row') {
			// A hop at the row just lands; only a trip to a row fires the zap.
			if (this.pendingZap) {
				this.pendingZap = false;
				this.setPose('is-hover');
				this.zap(this.active);
			}
			return;
		}
		if (traveled || !this.buddy.classList.contains('is-sit')) {
			this.setPose('is-sit');
			this.setFace(this.kind === 'perch' ? 'right' : this.pointerFace());
		}
		this.fidget();
		if (moved) {
			this.flash('is-land', 420);
		}
		if (this.kind === 'perch' && !this.perched && this.inView) {
			this.perched = true;
			this.talk(this.say.perch, 3600);
		} else if (this.kind === 'seat' && this.pendingHint) {
			this.pendingHint = false;
			this.talk(finePointer.matches ? this.say.hover : this.say.touch, 3800);
		}
	};

	/* ---------------------------------------------------------------- */
	/* Behaviour                                                         */
	/* ---------------------------------------------------------------- */

	Showdown.prototype.activate = function (index, fromTour) {
		if (!fromTour) {
			this.touched = true;
			this.cancel('tour');
			this.cancel('tour-home');
			if (this.pendingHint || this.talking === 'hint') {
				this.pendingHint = false;
				this.hush();
			}
		}
		this.cancel('leave');
		this.cancel('touch-home');
		if (index === this.active && this.kind === 'row') {
			return;
		}
		this.endZap();
		if (this.rows[this.active]) {
			this.rows[this.active].classList.remove('is-active');
		}
		this.targetKey = '';
		this.active = index;
		this.rows[index].classList.add('is-active');
		this.retarget(false);
	};

	Showdown.prototype.release = function () {
		if (this.active < 0) {
			return;
		}
		this.endZap();
		this.rows[this.active].classList.remove('is-active');
		this.active = -1;
		this.retarget(false);
	};

	Showdown.prototype.zap = function (index) {
		var row = this.rows[index];
		if (!row) {
			return;
		}
		var self = this;
		var token = ++this.seq;
		var alreadyFixed = this.zapped.indexOf(index) >= 0;
		this.setPose('is-zap');
		this.setFace(this.wide ? 'left' : 'right');
		row.classList.remove('is-zapping', 'is-won');
		void row.offsetWidth; // restart the CSS animations
		row.classList.add('is-zapping');

		this.later('zap-hit', 240, function () {
			row.classList.add('is-zapped');
			if (!alreadyFixed) {
				self.zapped.push(index);
			}
		});
		this.later('zap-cheer', 600, function () {
			if (token !== self.seq) {
				return;
			}
			self.setPose('is-cheer');
			self.setFace('right');
			row.classList.add('is-won');
			self.hop(160);
		});
		this.later('zap-done', 1250, function () {
			if (token !== self.seq) {
				return;
			}
			row.classList.remove('is-zapping');
			self.setPose('is-hover');
			self.setFace(self.pointerFace());
			if (!self.celebrated && self.zapped.length === self.rows.length && self.touched) {
				self.celebrate();
			}
		});
	};

	Showdown.prototype.endZap = function () {
		this.seq++;
		this.cancel('zap-cheer');
		this.cancel('zap-done');
		var row = this.rows[this.active];
		if (row) {
			row.classList.remove('is-zapping');
		}
	};

	// First view: fly to the first row, zap it, come back and explain.
	Showdown.prototype.tour = function () {
		if (this.touched || reduceMotion.matches || isEditMode() || !this.inView) {
			this.pendingHint = !this.touched;
			if (this.pendingHint && this.kind === 'seat' && !this.flying) {
				this.arrive(false);
			}
			return;
		}
		var self = this;
		this.activate(0, true);
		this.later('tour-home', 2100, function () {
			if (self.touched) {
				return;
			}
			self.pendingHint = true;
			self.release();
		});
	};

	Showdown.prototype.celebrate = function () {
		this.celebrated = true;
		this.setPose('is-cheer');
		this.flash('is-flip', 720);
		this.hop(420);
		this.talk(this.say.done, 3600);
		var colors = ['#fb6007', '#ffffff', '#ffb347'];
		for (var i = 0; i < 14; i++) {
			var angle = (Math.PI * 2 * i) / 14;
			this.spark(this.x, this.y - this.bh * 0.2, Math.cos(angle) * 46, Math.sin(angle) * 46 - 10, colors[i % 3], 700);
		}
	};

	Showdown.prototype.poke = function () {
		this.touched = true;
		this.clicks++;
		this.hop(this.clicks % 4 === 0 ? 520 : 360);
		if (this.clicks % 4 === 0) {
			this.flash('is-flip', 720);
		}
		this.flash('is-wave', 1300);
		var lines = this.say.lines;
		if (lines.length) {
			this.talk(lines[this.line % lines.length], 3200);
			this.line++;
		}
	};

	// Upward kick; the spring brings it back down to where it belongs.
	Showdown.prototype.hop = function (strength) {
		if (reduceMotion.matches) {
			return;
		}
		this.vy -= strength;
		this.hopped = true;
		this.wake();
	};

	// Idle life while sitting: swap legs every 750 ms, glance now and then.
	// Stops itself when it flies off, the section leaves the screen or the
	// visitor prefers less motion; arrive() and coming back on screen restart it.
	Showdown.prototype.fidget = function () {
		if (this.timers.fidget || reduceMotion.matches) {
			return;
		}
		var self = this;
		var beat = 0;
		function step() {
			var sitting = self.buddy.classList.contains('is-sit') && !self.loop;
			if (!self.alive || !self.inView || !sitting) {
				self.buddy.classList.remove('is-step', 'is-glance');
				return;
			}
			beat++;
			self.buddy.classList.toggle('is-step');
			// Without a mouse to watch, glance around once in a while.
			self.buddy.classList.toggle('is-glance', self.pointerX === null && beat % 10 >= 8);
			self.later('fidget', 750, step);
		}
		this.later('fidget', 750, step);
	};

	/* ---------------------------------------------------------------- */
	/* Scroll focus, pointer                                             */
	/* ---------------------------------------------------------------- */

	Showdown.prototype.onScroll = function () {
		if (!this.inView || this.scrollQueued || !this.quote || !this.perch || !this.seat) {
			return;
		}
		var self = this;
		this.scrollQueued = true;
		window.requestAnimationFrame(function () {
			self.scrollQueued = false;
			if (!self.alive) {
				return;
			}
			var top = self.quote.getBoundingClientRect().top;
			var vh = window.innerHeight;
			var home = self.home;
			if (top < vh * 0.55) {
				home = 'perch';
			} else if (top > vh * 0.68) {
				home = 'seat';
			}
			if (home !== self.home) {
				self.home = home;
				if (self.active < 0) {
					self.retarget(false);
				}
			}
		});
	};

	Showdown.prototype.onPointer = function (event) {
		if (!this.inView || event.pointerType !== 'mouse') {
			return;
		}
		this.pointerX = event.clientX;
		if (!this.flying && (this.kind === 'seat' || (this.kind === 'row' && this.buddy.classList.contains('is-hover')))) {
			this.setFace(this.pointerFace());
		}
	};

	Showdown.prototype.pointerFace = function () {
		if (this.pointerX === null) {
			return this.face;
		}
		return this.pointerX < this.frameLeft + this.x - 4 ? 'left' : 'right';
	};

	/* ---------------------------------------------------------------- */
	/* Presentation helpers                                              */
	/* ---------------------------------------------------------------- */

	Showdown.prototype.setPose = function (pose) {
		POSES.forEach(function (name) {
			this.buddy.classList.toggle(name, name === pose);
		}, this);
	};

	Showdown.prototype.setFace = function (face) {
		if (face !== this.face) {
			this.face = face;
			this.buddy.classList.toggle('is-left', face === 'left');
		}
	};

	// Bubble opens toward the roomier side.
	Showdown.prototype.setSide = function () {
		var ratio = this.frameWidth ? this.tx / this.frameWidth : 0.5;
		this.buddy.setAttribute('data-side', ratio < 0.3 ? 'right' : ratio > 0.7 ? 'left' : 'center');
	};

	Showdown.prototype.talk = function (text, duration) {
		if (!text || !this.bubble) {
			return;
		}
		var self = this;
		this.setSide();
		this.bubble.textContent = text;
		this.talking = text === this.say.hover || text === this.say.touch ? 'hint' : 'line';
		this.buddy.classList.add('is-talking');
		this.later('talk', duration, function () {
			self.hush();
		});
	};

	Showdown.prototype.hush = function () {
		this.cancel('talk');
		this.talking = '';
		this.buddy.classList.remove('is-talking');
	};

	Showdown.prototype.flash = function (name, duration) {
		if (reduceMotion.matches && name !== 'is-wave') {
			return;
		}
		var buddy = this.buddy;
		buddy.classList.remove(name);
		void buddy.offsetWidth;
		buddy.classList.add(name);
		this.later(name, duration, function () {
			buddy.classList.remove(name);
		});
	};

	Showdown.prototype.makeSparks = function () {
		if (reduceMotion.matches || typeof document.body.animate !== 'function') {
			return;
		}
		for (var i = 0; i < 18; i++) {
			var spark = document.createElement('i');
			spark.className = 'avix-cq__spark';
			spark.setAttribute('aria-hidden', 'true');
			this.frame.appendChild(spark);
			this.sparks.push(spark);
		}
	};

	Showdown.prototype.spark = function (x, y, dx, dy, color, duration) {
		if (!this.sparks.length) {
			return;
		}
		var spark = this.sparks[this.sparkIndex++ % this.sparks.length];
		spark.style.translate = x.toFixed(1) + 'px ' + y.toFixed(1) + 'px';
		spark.style.background = color;
		spark.animate(
			[
				{ opacity: 0.95, transform: 'translate(0, 0) scale(1)' },
				{ opacity: 0, transform: 'translate(' + dx.toFixed(1) + 'px,' + dy.toFixed(1) + 'px) scale(0.3)' }
			],
			{ duration: duration || 420, easing: 'cubic-bezier(0.22, 1, 0.36, 1)' }
		);
	};

	Showdown.prototype.later = function (key, delay, fn) {
		var timers = this.timers;
		window.clearTimeout(timers[key]);
		timers[key] = window.setTimeout(function () {
			delete timers[key];
			fn();
		}, delay);
	};

	Showdown.prototype.cancel = function (key) {
		window.clearTimeout(this.timers[key]);
		delete this.timers[key];
	};

	Showdown.prototype.destroy = function () {
		if (!this.alive) {
			return;
		}
		this.alive = false;
		Object.keys(this.timers).forEach(this.cancel, this);
		window.removeEventListener('scroll', this.onScroll);
		window.removeEventListener('resize', this.onResize);
		window.removeEventListener('pointermove', this.onPointer);
		['revealObserver', 'videoObserver', 'viewObserver', 'tourObserver', 'resizeObserver'].forEach(function (key) {
			if (this[key]) {
				this[key].disconnect();
			}
		}, this);
		if (this.loop) {
			window.cancelAnimationFrame(this.loop);
		}
	};

	function mount(root) {
		if (!root || (root.__avixCq && root.__avixCq.alive)) {
			return;
		}
		root.__avixCq = new Showdown(root);
	}

	function mountAll(scope) {
		Array.prototype.forEach.call((scope || document).querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixCompareQuote = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-compare-quote.default', function ($scope) {
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
