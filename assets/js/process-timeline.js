/*!
 * Avix Digital · Process Timeline
 * Scroll draws the line; the pixel character walks it, lights up each step
 * and sits down at the end of the line instead of disappearing. A faint pixel
 * picture beside each step fills in as the character reaches it.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-pt]';
	var reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };

	function clamp(value, min, max) {
		return value < min ? min : value > max ? max : value;
	}

	function isEditMode() {
		return !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
	}

	/* ---------- Pixel pictures ---------- */

	// Each picture is a 60 x 40 pixel drawing: the same browser window at four
	// stages (sitemap, wireframe, code, live site). Only the window frame shows
	// until the character reaches the step; then the rest fills in pixel by
	// pixel in a random order.
	var ART_W = 60;
	var ART_H = 40;
	// White at these alphas; 'a' is the accent colour.
	var TONES = { d: 0.045, o: 0.1, A: 0.14, w: 0.22, W: 0.32, a: 0.7 };
	var BAYER = [0, 8, 2, 10, 12, 4, 14, 6, 3, 11, 1, 9, 15, 7, 13, 5];
	var pictures = {};

	function bayer(x, y) {
		return (BAYER[(y & 3) * 4 + (x & 3)] + 0.5) / 16;
	}

	function hash(x, y) {
		var h = Math.sin(x * 127.1 + y * 311.7) * 43758.5453;
		return h - Math.floor(h);
	}

	function Pixels() {
		this.kind = [];
		this.under = [];
		this.frame = false;
	}

	Pixels.prototype.dot = function (x, y, k) {
		if (x < 0 || y < 0 || x >= ART_W || y >= ART_H) {
			return;
		}
		this.kind[y * ART_W + x] = k;
		if (this.frame) {
			this.under[y * ART_W + x] = k;
		}
	};

	Pixels.prototype.rect = function (x, y, w, h, k) {
		for (var j = y; j < y + h; j++) {
			for (var i = x; i < x + w; i++) {
				this.dot(i, j, k);
			}
		}
	};

	Pixels.prototype.box = function (x, y, w, h, k) {
		this.rect(x, y, w, 1, k);
		this.rect(x, y + h - 1, w, 1, k);
		this.rect(x, y, 1, h, k);
		this.rect(x + w - 1, y, 1, h, k);
	};

	Pixels.prototype.line = function (x0, y0, x1, y1, k) {
		var dx = Math.abs(x1 - x0);
		var dy = -Math.abs(y1 - y0);
		var sx = x0 < x1 ? 1 : -1;
		var sy = y0 < y1 ? 1 : -1;
		var err = dx + dy;
		for (;;) {
			this.dot(x0, y0, k);
			if (x0 === x1 && y0 === y1) {
				return;
			}
			var e2 = 2 * err;
			if (e2 >= dy) {
				err += dy;
				x0 += sx;
			}
			if (e2 <= dx) {
				err += dx;
				y0 += sy;
			}
		}
	};

	// Rows of characters; each character maps to a tone ('.' is empty).
	Pixels.prototype.sprite = function (x, y, rows, map) {
		for (var j = 0; j < rows.length; j++) {
			for (var i = 0; i < rows[j].length; i++) {
				if (map[rows[j].charAt(i)]) {
					this.dot(x + i, y + j, map[rows[j].charAt(i)]);
				}
			}
		}
	};

	// The browser window all four pictures share: 56 x 34 at (2, 4).
	function windowFrame(px, live) {
		px.frame = true;
		px.box(2, 4, 56, 34, live ? 'w' : 'o');
		px.rect(2, 8, 56, 1, 'o');
		px.dot(4, 6, 'a');
		px.dot(6, 6, live ? 'w' : 'o');
		px.dot(8, 6, live ? 'w' : 'o');
		px.box(16, 5, 28, 3, 'o');
		px.frame = false;
	}

	var DRAWINGS = {
		// Planning: a sitemap and a short scope list.
		brief: function (px) {
			windowFrame(px, false);
			px.box(22, 11, 16, 5, 'w');
			px.rect(25, 13, 4, 1, 'a');
			px.line(30, 16, 30, 19, 'o');
			px.line(10, 19, 50, 19, 'o');
			[10, 30, 50].forEach(function (x) {
				px.line(x, 19, x, 21, 'o');
				px.box(x - 6, 22, 13, 5, 'o');
			});
			[7, 13, 47, 53].forEach(function (x) {
				px.line(x, 27, x, 29, 'o');
				px.box(x - 2, 30, 5, 3, 'o');
			});
			[0, 1, 2].forEach(function (n) {
				px.dot(20, 30 + n * 2, n < 2 ? 'a' : 'o');
				px.rect(22, 30 + n * 2, 10 - n * 2, 1, 'o');
			});
		},
		// Design: a wireframe with a cursor.
		wireframe: function (px) {
			windowFrame(px, false);
			px.rect(5, 11, 6, 2, 'w');
			[32, 38, 44].forEach(function (x) {
				px.rect(x, 11, 4, 1, 'o');
			});
			px.box(50, 10, 6, 3, 'a');
			px.box(5, 15, 50, 12, 'o');
			px.rect(8, 18, 18, 2, 'w');
			px.rect(8, 21, 12, 1, 'o');
			px.rect(8, 23, 7, 2, 'w');
			[[5, 15], [22, 16], [40, 15]].forEach(function (card) {
				px.box(card[0], 29, card[1], 7, 'o');
				px.rect(card[0] + 2, 33, card[1] - 6, 1, 'o');
			});
			px.sprite(42, 20, ['W', 'WW', 'WAW', 'WAAW', 'WAAAW', 'WAAAAW', 'WAAWWW', 'WW.WA', 'W..WAW', '....WW'], { W: 'W', A: 'A' });
		},
		// Build: lines of code and a </> mark.
		code: function (px) {
			windowFrame(px, false);
			var lines = [[0, [[3, 'w'], [8, 'w'], [2, 'o']]], [2, [[5, 'A'], [9, 'o']]], [4, [[4, 'w'], [12, 'o'], [3, 'o']]], [4, [[7, 'o'], [5, 'A']]], [2, [[2, 'o']]], [0, [[6, 'w'], [10, 'o']]], [2, [[8, 'o'], [4, 'A'], [6, 'o']]], [2, [[5, 'o'], [3, 'w']]], [0, [[2, 'o']]], [0, [[4, 'w'], [7, 'o']]], [2, [[10, 'o'], [1, 'o']]]];
			lines.forEach(function (line, n) {
				var x = 7 + line[0];
				px.dot(4, 11 + n * 2, n % 5 === 0 ? 'o' : 'd');
				line[1].forEach(function (token) {
					px.rect(x, 11 + n * 2, token[0], 1, token[1]);
					x += token[0] + 1;
				});
			});
			px.rect(20, 31, 1, 2, 'a');
			[[5, 1, 1, 5], [1, 5, 5, 9], [11, 1, 15, 5], [15, 5, 11, 9], [10, 0, 6, 10]].forEach(function (l) {
				px.line(38 + l[0], 15 + l[1], 38 + l[2], 15 + l[3], 'A');
				px.line(39 + l[0], 15 + l[1], 39 + l[2], 15 + l[3], 'A');
			});
		},
		// Launch: the finished page, and a small rocket leaving the window.
		launch: function (px) {
			windowFrame(px, true);
			for (var j = 0; j < 17; j++) {
				for (var i = 0; i < 24; i++) {
					if (bayer(32 + i, 10 + j) < (i / 24) * (j / 17) * 0.6) {
						px.dot(32 + i, 10 + j, 'd');
					}
				}
			}
			px.rect(6, 13, 22, 2, 'W');
			px.rect(6, 16, 15, 2, 'W');
			px.rect(6, 20, 9, 3, 'a');
			[[5, 15], [22, 16], [40, 15]].forEach(function (card) {
				px.box(card[0], 29, card[1], 7, 'o');
				px.rect(card[0] + 2, 31, 4, 2, 'w');
				px.rect(card[0] + 2, 34, card[1] - 6, 1, 'o');
			});
			px.sprite(44, 0, ['...W...', '..WWW..', '..WWW..', '.WWaWW.', '.WWWWW.', '.WWWWW.', 'wWWWWWw', 'ww.W.ww', '..aaa..', '...a...', '..A.A..', '...A...', '.A...A.'], { W: 'W', w: 'w', a: 'a', A: 'A' });
		}
	};

	// Flat list of the picture's pixels: [x, y, tone, tone before the step is
	// reached (the frame, or '' for nothing), fill order 0-1].
	function picture(name) {
		if (!pictures[name]) {
			var px = new Pixels();
			(DRAWINGS[name] || DRAWINGS.brief)(px);
			var list = [];
			px.kind.forEach(function (k, i) {
				if (k) {
					var x = i % ART_W;
					var y = Math.floor(i / ART_W);
					list.push([x, y, k, px.under[i] || '', hash(x, y)]);
				}
			});
			pictures[name] = list;
		}
		return pictures[name];
	}

	function Art(el, accent) {
		this.el = el;
		this.canvas = el.querySelector('canvas');
		this.ctx = this.canvas && this.canvas.getContext ? this.canvas.getContext('2d') : null;
		this.pixels = picture(el.getAttribute('data-pt-art'));
		this.step = parseInt(el.getAttribute('data-pt-art-step'), 10) || 0;
		this.accent = accent;
		this.shown = 0;
		this.target = 0;
		this.frame = 0;
		this.size = '';
		this.animate = this.animate.bind(this);
	}

	Art.prototype.resize = function () {
		if (!this.ctx) {
			return;
		}
		var width = this.canvas.clientWidth;
		var height = this.canvas.clientHeight;
		var ratio = Math.min(2, window.devicePixelRatio || 1);
		var size = Math.round(width * ratio) + 'x' + Math.round(height * ratio);
		if (!width || size === this.size) {
			return;
		}
		this.size = size;
		this.canvas.width = Math.round(width * ratio);
		this.canvas.height = Math.round(height * ratio);
		this.draw();
	};

	Art.prototype.setReached = function (reached) {
		var target = reached ? 1 : 0;
		if (target === this.target) {
			return;
		}
		this.target = target;
		this.el.classList.toggle('is-reached', reached);
		if (reduceMotion.matches) {
			this.shown = target;
			this.draw();
		} else if (!this.frame) {
			this.last = 0;
			this.frame = window.requestAnimationFrame(this.animate);
		}
	};

	// Fills in over ~0.9 s and clears a little faster.
	Art.prototype.animate = function (time) {
		this.frame = 0;
		var dt = this.last ? Math.min(0.05, (time - this.last) / 1000) : 1 / 60;
		this.last = time;
		this.shown = this.target > this.shown ? Math.min(this.target, this.shown + dt / 0.9) : Math.max(this.target, this.shown - dt / 0.5);
		this.draw();
		if (this.shown !== this.target) {
			this.frame = window.requestAnimationFrame(this.animate);
		}
	};

	Art.prototype.draw = function () {
		var c = this.ctx;
		if (!c || !this.canvas.width) {
			return;
		}
		var unit = this.canvas.width / ART_W;
		var gap = Math.max(1, Math.round(unit * 0.16));
		var shown = this.shown;
		c.clearRect(0, 0, this.canvas.width, this.canvas.height);
		for (var i = 0; i < this.pixels.length; i++) {
			var p = this.pixels[i];
			var tone = p[4] < shown ? p[2] : p[3];
			if (!tone) {
				continue;
			}
			var x = Math.round(p[0] * unit);
			var y = Math.round(p[1] * unit);
			// Pixels that just arrived glow a little brighter.
			var fresh = tone !== p[3] && shown < 1 && p[4] > shown - 0.08 ? 1.8 : 1;
			c.globalAlpha = Math.min(1, TONES[tone] * fresh);
			c.fillStyle = 'a' === tone ? this.accent : '#ffffff';
			c.fillRect(x, y, Math.round((p[0] + 1) * unit) - x - gap, Math.round((p[1] + 1) * unit) - y - gap);
		}
		c.globalAlpha = 1;
	};

	Art.prototype.stop = function () {
		if (this.frame) {
			window.cancelAnimationFrame(this.frame);
			this.frame = 0;
		}
	};

	function Timeline(root) {
		var config = {};
		try {
			config = JSON.parse(root.getAttribute('data-avix-pt') || '{}') || {};
		} catch (error) {
			config = {};
		}

		this.root = root;
		this.alive = true;
		this.timeline = root.querySelector('[data-pt-timeline]');
		this.path = root.querySelector('[data-pt-draw]');
		this.rail = root.querySelector('[data-pt-rail]');
		this.railDraw = root.querySelector('[data-pt-rail-draw]');
		this.buddy = root.querySelector('[data-pt-buddy]');
		this.steps = Array.prototype.slice.call(root.querySelectorAll('[data-pt-step]'));
		var accent = (window.getComputedStyle(root).getPropertyValue('--pt-accent') || '').trim() || '#fb6007';
		this.arts = [];
		Array.prototype.forEach.call(root.querySelectorAll('[data-pt-art]'), function (el) {
			var art = new Art(el, accent);
			if (art.ctx) {
				this.arts[art.step] = art;
			}
		}, this);
		this.viewW = parseFloat(config.w) || 1400;
		this.viewH = parseFloat(config.h) || 2100;
		this.bandTops = (config.bands || []).map(parseFloat);
		this.progress = 0;
		this.target = 0;
		this.frame = 0;
		this.lastTime = 0;
		this.activeUntil = 0;
		this.walkUntil = 0;
		this.state = '';

		if (!this.timeline || !this.steps.length) {
			this.alive = false;
			return;
		}

		this.onScroll = this.wake.bind(this);
		this.onResize = this.requestMeasure.bind(this);
		this.tick = this.tick.bind(this);
		this.measureFrame = 0;
		this.init();
	}

	Timeline.prototype.init = function () {
		var self = this;
		this.root.classList.add('is-live');
		this.splitTitle();
		this.observeReveals();
		this.observeView();

		window.addEventListener('scroll', this.onScroll, { passive: true });
		window.addEventListener('resize', this.onResize, { passive: true });
		if (window.ResizeObserver) {
			this.resizeObserver = new window.ResizeObserver(function () {
				self.requestMeasure();
			});
			this.resizeObserver.observe(this.timeline);
		}
		this.measure();
	};

	// Resize bursts (a window drag, a font swap, late images) measure once, on the next frame.
	Timeline.prototype.requestMeasure = function () {
		var self = this;
		if (!this.alive || this.measureFrame) {
			return;
		}
		this.measureFrame = window.requestAnimationFrame(function () {
			self.measureFrame = 0;
			self.measure();
		});
	};

	// Word-by-word title reveal. The text stays in the HTML for SEO / no-JS.
	// Words are split on whitespace only, so "[highlight]." stays one word;
	// highlighted runs keep their span (cloned per word).
	Timeline.prototype.splitTitle = function () {
		var title = this.root.querySelector('[data-pt-words]');
		if (!title || title.getAttribute('data-pt-split')) {
			return;
		}
		var words = [];
		var current = null;
		Array.prototype.forEach.call(title.childNodes, function (node) {
			var wrapper = node.nodeType === 1 ? node : null;
			(node.textContent || '').split(/(\s+)/).forEach(function (part) {
				if (!part) {
					return;
				}
				if (/^\s+$/.test(part)) {
					current = null;
					return;
				}
				if (!current) {
					current = [];
					words.push(current);
				}
				current.push({ text: part, wrapper: wrapper });
			});
		});
		if (!words.length) {
			return;
		}

		title.setAttribute('aria-label', title.textContent.trim().replace(/\s+/g, ' '));
		var fragment = document.createDocumentFragment();
		words.forEach(function (runs, index) {
			var span = document.createElement('span');
			span.className = 'avix-pt__word';
			span.setAttribute('aria-hidden', 'true');
			span.style.setProperty('--pt-delay', index * 40 + 'ms');
			runs.forEach(function (run) {
				var text = document.createTextNode(run.text);
				if (run.wrapper) {
					var clone = run.wrapper.cloneNode(false);
					clone.appendChild(text);
					span.appendChild(clone);
				} else {
					span.appendChild(text);
				}
			});
			if (index) {
				fragment.appendChild(document.createTextNode(' '));
			}
			fragment.appendChild(span);
		});
		title.textContent = '';
		title.appendChild(fragment);
		title.setAttribute('data-pt-split', '1');
		title.classList.add('is-split');
	};

	// Loops (glow, the waving tile) pause while the section is off screen.
	Timeline.prototype.observeView = function () {
		if (!('IntersectionObserver' in window)) {
			return;
		}
		var root = this.root;
		this.viewObserver = new window.IntersectionObserver(function (entries) {
			root.classList.toggle('is-offscreen', !entries[entries.length - 1].isIntersecting);
		}, { rootMargin: '100px 0px' });
		this.viewObserver.observe(root);
	};

	Timeline.prototype.observeReveals = function () {
		var items = Array.prototype.slice.call(this.root.querySelectorAll('[data-pt-reveal]'));
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
		}, { threshold: 0.12, rootMargin: '0px 0px -50px 0px' });
		items.forEach(function (item) {
			this.revealObserver.observe(item);
		}, this);
	};

	Timeline.prototype.measure = function () {
		if (!this.alive) {
			return;
		}
		var styles = window.getComputedStyle(this.timeline);
		this.mode = styles.getPropertyValue('aspect-ratio') === 'auto' || !this.path || this.path.getBoundingClientRect().width === 0 ? 'rail' : 'path';
		this.arts.forEach(function (art) {
			art.resize();
		});

		if (this.mode === 'path') {
			var length = this.path.getTotalLength();
			// The path lives in the SVG's own units, so its length and the step
			// lengths only change with the path itself, not with the layout.
			if (length !== this.length || !this.stepLengths) {
				this.length = length;
				this.path.style.strokeDasharray = this.length + ' ' + this.length;
				// Length at which the line reaches each step's band.
				var self = this;
				this.stepLengths = this.bandTops.map(function (top) {
					return self.lengthAtY(top);
				});
			}
		} else if (this.rail) {
			this.railHeight = this.rail.offsetHeight;
			this.stepOffsets = this.steps.map(function (step) {
				return step.offsetTop;
			});
		}
		this.wake();
	};

	// First length (on a 400-step grid) whose point reaches y. The serpentine
	// only ever runs across or down (y never decreases along it), so a binary
	// search finds the same grid step as walking all 400: about 9 point
	// lookups per step instead of up to 400.
	Timeline.prototype.lengthAtY = function (y) {
		var steps = 400;
		var path = this.path;
		var total = this.length;
		var reaches = function (i) {
			return path.getPointAtLength(total * i / steps).y >= y - 1;
		};
		if (!reaches(steps)) {
			return total;
		}
		var lo = 0;
		var hi = steps;
		while (lo < hi) {
			var mid = (lo + hi) >> 1;
			if (reaches(mid)) {
				hi = mid;
			} else {
				lo = mid + 1;
			}
		}
		return total * lo / steps;
	};

	Timeline.prototype.wake = function () {
		if (!this.alive) {
			return;
		}
		this.activeUntil = window.performance.now() + 600;
		if (!this.frame) {
			this.lastTime = 0;
			this.frame = window.requestAnimationFrame(this.tick);
		}
	};

	Timeline.prototype.tick = function (time) {
		this.frame = 0;
		if (!this.root.isConnected) {
			this.destroy();
			return;
		}

		var dt = this.lastTime ? Math.min(0.1, (time - this.lastTime) / 1000) : 1 / 60;
		this.lastTime = time;

		var rect = this.timeline.getBoundingClientRect();
		var vh = window.innerHeight;
		var anchor = vh * 0.58;
		var span = this.mode === 'path' ? rect.height * 0.94 : (this.railHeight || rect.height);
		this.target = clamp((anchor - rect.top) / Math.max(1, span), 0, 1);

		var previous = this.progress;
		if (reduceMotion.matches) {
			this.progress = this.target;
		} else {
			this.progress += (this.target - this.progress) * (1 - Math.exp(-dt / 0.12));
			if (Math.abs(this.target - this.progress) < 0.0004) {
				this.progress = this.target;
			}
		}

		var p = this.progress;
		var moving = Math.abs(p - previous) > 0.00035;
		var now = time;
		if (moving) {
			this.walkUntil = now + 160;
		}

		var chill = this.target >= 0.999 && p >= 0.996;
		var on = p > 0.004;

		if (this.mode === 'path') {
			this.renderPath(rect, p);
		} else {
			this.renderRail(p, chill, now);
		}

		this.setState(on, chill, !chill && now < this.walkUntil);

		if (this.target !== this.progress || now < this.activeUntil || now < this.walkUntil || now < (this.glideUntil || 0)) {
			this.frame = window.requestAnimationFrame(this.tick);
		}
	};

	Timeline.prototype.renderPath = function (rect, p) {
		var length = this.length * p;
		var point = this.path.getPointAtLength(length);
		var scaleX = rect.width / this.viewW;
		var scaleY = rect.height / this.viewH;

		this.path.style.strokeDashoffset = String(this.length - length);
		if (this.buddy) {
			this.buddy.style.transform = 'translate3d(' + (point.x * scaleX).toFixed(1) + 'px,' + (point.y * scaleY).toFixed(1) + 'px,0)';
		}

		var current = -1;
		for (var i = 0; i < this.steps.length; i++) {
			var reached = p > 0.004 && length >= (this.stepLengths[i] || 0) - 1;
			this.steps[i].classList.toggle('is-reached', reached);
			if (this.arts[i]) {
				this.arts[i].setReached(reached);
			}
			if (reached) {
				current = i;
			}
		}
		this.markCurrent(current);
	};

	Timeline.prototype.renderRail = function (p, chill, now) {
		var height = this.railHeight || 0;
		var y = height * p;
		if (this.railDraw) {
			this.railDraw.style.height = (p * 100).toFixed(2) + '%';
		}
		if (this.buddy) {
			// At the end it steps onto the ledge and sits near its edge.
			var ledge = parseFloat(window.getComputedStyle(this.rail.parentNode).getPropertyValue('--pt-ledge')) || 72;
			var x = chill ? ledge - 12 : 1;
			if (chill !== this.railChill) {
				this.railChill = chill;
				this.glideUntil = now + 480;
			}
			this.buddy.style.transition = now < (this.glideUntil || 0) ? 'transform 450ms cubic-bezier(0.22, 1, 0.36, 1)' : '';
			this.buddy.style.transform = 'translate3d(' + x + 'px,' + y.toFixed(1) + 'px,0)';
		}

		var current = -1;
		for (var i = 0; i < this.steps.length; i++) {
			var reached = p > 0.004 && y + 24 >= (this.stepOffsets[i] || 0);
			this.steps[i].classList.toggle('is-reached', reached);
			if (reached) {
				current = i;
			}
		}
		this.markCurrent(current);
	};

	Timeline.prototype.markCurrent = function (current) {
		for (var i = 0; i < this.steps.length; i++) {
			this.steps[i].classList.toggle('is-current', i === current);
		}
	};

	Timeline.prototype.setState = function (on, chill, walking) {
		var state = (on ? 'on' : '') + (chill ? ' chill' : '') + (walking ? ' walk' : '');
		if (state === this.state) {
			return;
		}
		this.state = state;
		if (this.buddy) {
			this.buddy.classList.toggle('is-on', on);
			this.buddy.classList.toggle('is-chill', chill);
			this.buddy.classList.toggle('is-walking', walking);
		}
		this.root.classList.toggle('is-chill', chill);
	};

	Timeline.prototype.destroy = function () {
		if (!this.alive) {
			return;
		}
		this.alive = false;
		window.removeEventListener('scroll', this.onScroll);
		window.removeEventListener('resize', this.onResize);
		if (this.resizeObserver) {
			this.resizeObserver.disconnect();
		}
		if (this.revealObserver) {
			this.revealObserver.disconnect();
		}
		if (this.viewObserver) {
			this.viewObserver.disconnect();
		}
		if (this.frame) {
			window.cancelAnimationFrame(this.frame);
		}
		if (this.measureFrame) {
			window.cancelAnimationFrame(this.measureFrame);
			this.measureFrame = 0;
		}
		this.arts.forEach(function (art) {
			art.stop();
		});
	};

	function mount(root) {
		if (!root || (root.__avixPt && root.__avixPt.alive)) {
			return;
		}
		var timeline = new Timeline(root);
		if (timeline.alive) {
			root.__avixPt = timeline;
		}
	}

	function mountAll(scope) {
		Array.prototype.forEach.call((scope || document).querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixProcessTimeline = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-process-timeline.default', function ($scope) {
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
