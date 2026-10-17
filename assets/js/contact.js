/*!
 * Avix Digital · Contact Studio
 * The pixel buddy rebuilt from voxels: it gathers from a cloud of pixels when
 * it comes into view beside the form, floats, waves now and then, follows the
 * pointer with its eye, ripples and warms up where the pointer passes, and
 * hops when clicked. A speech bubble cycles through short lines. Plus the
 * copy-email chip and the sentence blanks sized to their text. One rAF loop,
 * only while the buddy is on screen.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-cn]';
	var reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };
	var instances = [];

	// The brand's pixel buddy on a 10×11 grid: legs, body, arms, head.
	var RECTS = [[2, 7, 2, 4], [6, 7, 2, 4], [2, 3, 6, 4], [0, 4, 2, 3], [8, 4, 2, 3], [3, 0, 4, 3]];
	var ARM = [8, 4, 2, 3];
	var EYE_ROW = 1;
	var EYE_COL = 4;
	var SUB = 2;
	var COLS = 10 * SUB;
	var ROWS = 11 * SUB;
	var BASE = [251, 96, 7];
	var HOT = [255, 196, 140];
	var EYE = [26, 18, 13];
	var TONES = 5;

	function clamp(v, a, b) {
		return v < a ? a : v > b ? b : v;
	}

	function easeOut(t) {
		return 1 - Math.pow(1 - t, 3);
	}

	function rng(seed) {
		var s = seed >>> 0 || 1;
		return function () {
			s ^= s << 13;
			s ^= s >>> 17;
			s ^= s << 5;
			return (s >>> 0) / 4294967296;
		};
	}

	function rgb(c, k, mix) {
		var m = mix || 0;
		return 'rgb(' + [0, 1, 2].map(function (i) {
			return Math.round(clamp(c[i] * k + (255 - c[i] * k) * m, 0, 255));
		}).join(',') + ')';
	}

	function inside(r, x, y) {
		return x >= r[0] && x < r[0] + r[2] && y >= r[1] && y < r[1] + r[3];
	}

	function now() {
		return window.performance ? window.performance.now() : Date.now();
	}

	/* ---------- Voxel buddy ---------- */

	function Buddy(stage, root) {
		this.stage = stage;
		this.root = root;
		this.canvas = stage.querySelector('canvas');
		this.ctx = this.canvas && this.canvas.getContext ? this.canvas.getContext('2d') : null;
		this.bubble = stage.querySelector('[data-cn-bubble]');
		this.alive = !!this.ctx;
		if (!this.alive) {
			return;
		}
		try {
			this.lines = JSON.parse(root.getAttribute('data-cn-lines') || '[]') || [];
		} catch (error) {
			this.lines = [];
		}
		this.reduced = reduceMotion.matches;
		this.w = 0;
		this.h = 0;
		this.dpr = Math.min(window.devicePixelRatio || 1, 2);
		this.progress = this.reduced ? 1 : 0;
		this.started = this.reduced;
		this.pointer = { x: -1e4, y: -1e4, on: false };
		this.look = 0;
		this.hopAt = -1e4;
		this.waveAt = 0;
		this.blinkAt = 0;
		this.nextWave = 2600;
		this.nextBlink = 1800;
		this.visible = false;
		this.raf = 0;
		this.last = 0;
		this.lineIndex = 0;
		this.lineTimer = 0;
		this.build();
		this.bind();
	}

	Buddy.prototype.build = function () {
		var r = rng(11);
		var list = [];
		RECTS.forEach(function (rc) {
			for (var cy = 0; cy < rc[3]; cy++) {
				for (var cx = 0; cx < rc[2]; cx++) {
					var x = rc[0] + cx;
					var y = rc[1] + cy;
					for (var sy = 0; sy < SUB; sy++) {
						for (var sx = 0; sx < SUB; sx++) {
							var gy = y * SUB + sy;
							list.push({
								gx: x * SUB + sx,
								gy: gy,
								arm: inside(ARM, x, y),
								tone: Math.floor(r() * TONES),
								rx: r(),
								ry: r(),
								delay: (gy / ROWS) * -0.45 + 0.45 + r() * 0.3,
								ox: 0,
								oy: 0,
								heat: 0
							});
						}
					}
				}
			}
		});
		// Back to front for the extruded faces: bottom rows first, left to right.
		list.sort(function (a, b) {
			return b.gy - a.gy || a.gx - b.gx;
		});
		this.voxels = list;
	};

	Buddy.prototype.bind = function () {
		var self = this;

		if (window.ResizeObserver) {
			this.ro = new window.ResizeObserver(function (entries) {
				var box = entries[0].contentRect;
				self.layout(box.width, box.height);
			});
			this.ro.observe(this.stage);
		} else {
			window.addEventListener('load', function () {
				self.layout(self.stage.clientWidth, self.stage.clientHeight);
			});
		}

		if (window.IntersectionObserver) {
			this.io = new window.IntersectionObserver(function (entries) {
				self.visible = entries[0].isIntersecting;
				if (self.visible && !self.started) {
					self.started = true;
					self.t0 = now();
				}
				self.request();
			}, { rootMargin: '80px' });
			this.io.observe(this.stage);
		} else {
			this.visible = true;
			this.started = true;
			this.t0 = now();
		}

		if (this.reduced) {
			this.showLine(0);
			return;
		}

		this.onMove = function (event) {
			var box = self.canvas.getBoundingClientRect();
			self.pointer.x = event.clientX - box.left;
			self.pointer.y = event.clientY - box.top;
			self.pointer.on = true;
			self.request();
		};
		this.onLeave = function () {
			self.pointer.on = false;
			self.request();
		};
		this.onTap = function () {
			self.hopAt = now();
			self.nextLine();
			self.request();
		};
		this.root.addEventListener('pointermove', this.onMove, { passive: true });
		this.root.addEventListener('pointerleave', this.onLeave);
		this.stage.addEventListener('click', this.onTap);
		this.onVisibility = function () {
			if (!document.hidden) {
				self.request();
			}
		};
		document.addEventListener('visibilitychange', this.onVisibility);
	};

	Buddy.prototype.layout = function (w, h) {
		if (!w || !h) {
			return;
		}
		this.w = w;
		this.h = h;
		// Field dots and the floor shadow follow the section's theme (light sheet or dark).
		var tone = window.getComputedStyle(this.stage);
		this.dot = tone.getPropertyValue('--cnb-dot').trim() || '#f1ede8';
		this.floor = parseFloat(tone.getPropertyValue('--cnb-floor')) || 0.5;
		this.canvas.width = Math.round(w * this.dpr);
		this.canvas.height = Math.round(h * this.dpr);
		var v = Math.floor(Math.min((w * 0.56) / COLS, (h * 0.6) / ROWS));
		this.v = clamp(v, 7, 22);
		this.d = Math.max(2, Math.round(this.v * 0.42));
		// With a speech bubble the buddy stands left of centre, leaving room on its right.
		var bodyW = COLS * this.v;
		this.ox = Math.round(this.bubble ? clamp(w * 0.38 - bodyW / 2, 8, w - bodyW - 8) : (w - bodyW) / 2);
		this.oy = Math.round(h * 0.52 - (ROWS * this.v) / 2);
		this.stage.style.setProperty('--buddy-x', Math.round(((this.ox + bodyW / 2) / w) * 100) + '%');
		this.sprites = this.makeSprites();
		this.field = this.makeField();
		// The bubble sits just right of the head. It is anchored by its bottom edge,
		// so its tail stays at eye height however many lines the message takes.
		if (this.bubble) {
			var bx = Math.round(this.ox + 7.6 * SUB * this.v + 4);
			var tail = this.oy + 2 * this.v + 6;
			var room = w - bx - 6;
			if (room < 120) {
				// No room on the right: the bubble sits above the head instead.
				bx = Math.max(6, this.ox + 3 * SUB * this.v - 20);
				tail = this.oy - 8;
				room = w - bx - 6;
			}
			this.bubble.style.setProperty('--bubble-x', Math.round(bx) + 'px');
			this.bubble.style.setProperty('--bubble-b', Math.round(Math.max(0, h - tail)) + 'px');
			this.bubble.style.setProperty('--bubble-max', Math.round(Math.min(210, room)) + 'px');
		}
		this.request(true);
	};

	/* One sprite per tone: front face, a lighter top and a darker right side. */
	Buddy.prototype.sprite = function (color, k) {
		var v = this.v;
		var d = this.d;
		var gap = Math.max(1, Math.round(v * 0.07));
		var s = document.createElement('canvas');
		var dpr = this.dpr;
		s.width = Math.ceil((v + d) * dpr);
		s.height = Math.ceil((v + d) * dpr);
		var c = s.getContext('2d');
		c.scale(dpr, dpr);
		c.fillStyle = rgb(color, k * 1.08, 0.18);
		c.beginPath();
		c.moveTo(0, d);
		c.lineTo(d, 0);
		c.lineTo(v + d - gap, 0);
		c.lineTo(v - gap, d);
		c.closePath();
		c.fill();
		c.fillStyle = rgb(color, k * 0.58);
		c.beginPath();
		c.moveTo(v - gap, d);
		c.lineTo(v + d - gap, 0);
		c.lineTo(v + d - gap, v - gap);
		c.lineTo(v - gap, v + d - gap);
		c.closePath();
		c.fill();
		c.fillStyle = rgb(color, k);
		c.fillRect(0, d, v - gap, v - gap);
		return s;
	};

	Buddy.prototype.makeSprites = function () {
		var out = { base: [], hot: null, eye: null };
		for (var i = 0; i < TONES; i++) {
			out.base.push(this.sprite(BASE, 0.9 + (i / (TONES - 1)) * 0.16));
		}
		out.hot = this.sprite(HOT, 1);
		out.eye = this.sprite(EYE, 1);
		return out;
	};

	/* A faint dithered field of pixels around the buddy, on its own grid. */
	Buddy.prototype.makeField = function () {
		var f = document.createElement('canvas');
		var w = this.w;
		var h = this.h;
		var dpr = this.dpr;
		f.width = Math.round(w * dpr);
		f.height = Math.round(h * dpr);
		var c = f.getContext('2d');
		c.scale(dpr, dpr);
		var g = this.v;
		var r = rng(5);
		var cx = this.ox + (COLS * g) / 2;
		var cy = this.oy + (ROWS * g) / 2;
		var reach = Math.max(w, h) * 0.62;
		var bayer = [0, 8, 2, 10, 12, 4, 14, 6, 3, 11, 1, 9, 15, 7, 13, 5];
		for (var y = 0; y < h; y += g) {
			for (var x = this.ox % g - g; x < w; x += g) {
				var dist = Math.hypot(x - cx, (y - cy) * 1.15) / reach;
				var density = clamp(0.32 - dist * 0.42, 0, 0.3);
				var ix = Math.round(x / g) & 3;
				var iy = Math.round(y / g) & 3;
				if ((bayer[iy * 4 + ix] + 0.5) / 16 > density) {
					continue;
				}
				var warm = r() > 0.72;
				c.globalAlpha = 0.05 + r() * 0.09;
				c.fillStyle = warm ? '#fb6007' : this.dot;
				var s = Math.max(2, Math.round(g * 0.3));
				c.fillRect(Math.round(x + (g - s) / 2), Math.round(y + (g - s) / 2), s, s);
			}
		}
		return f;
	};

	Buddy.prototype.request = function (force) {
		if (force && !this.raf) {
			this.draw(now());
		}
		if (this.raf || !this.visible || document.hidden || this.reduced) {
			if (this.reduced && !this.raf) {
				this.draw(now());
			}
			return;
		}
		var self = this;
		this.raf = window.requestAnimationFrame(function (t) {
			self.raf = 0;
			self.tick(t);
		});
	};

	Buddy.prototype.tick = function (t) {
		if (!this.alive || !this.visible || document.hidden) {
			return;
		}
		var busy = this.progress < 1 || this.pointer.on || t - this.hopAt < 700 || this.anyHeat;
		// Idle floating runs at ~30 fps; everything interactive at full rate.
		if (busy || t - this.last > 32) {
			this.last = t;
			this.draw(t);
		}
		this.request();
	};

	Buddy.prototype.draw = function (t) {
		var ctx = this.ctx;
		if (!this.w || !this.sprites) {
			return;
		}
		var v = this.v;
		var d = this.d;
		if (this.started && this.progress < 1) {
			this.progress = clamp((t - this.t0) / 1700, 0, 1);
			if (this.progress >= 1) {
				this.showLine(0);
				this.startLines();
				this.waveAt = t;
			}
		}

		// Idle life: a soft float, a wave every few seconds, an occasional blink.
		var bob = this.reduced ? 0 : Math.round(Math.sin(t / 640) * v * 0.35);
		var hopK = clamp((t - this.hopAt) / 620, 0, 1);
		var hop = hopK < 1 ? -Math.round(Math.sin(Math.PI * hopK) * v * 3.2) : 0;
		if (!this.reduced && this.progress >= 1) {
			if (t - this.waveAt > this.nextWave + 1400) {
				this.waveAt = t;
				this.nextWave = 4200 + Math.random() * 2600;
			}
			if (t - this.blinkAt > this.nextBlink + 150) {
				this.blinkAt = t;
				this.nextBlink = 2600 + Math.random() * 2400;
			}
		}
		var waveK = t - this.waveAt;
		var armUp = !this.reduced && this.progress >= 1 && waveK < 1400 ? Math.floor(waveK / 175) % 2 === 0 : false;
		var blink = !this.reduced && t - this.blinkAt < 140;

		// The eye looks toward the pointer (one sub-voxel either way).
		var centre = this.ox + (COLS * v) / 2;
		var look = 0;
		if (this.pointer.on) {
			look = this.pointer.x < centre - v * 4 ? -1 : this.pointer.x > centre + v * 4 ? 1 : 0;
		} else if (!this.reduced) {
			look = Math.round(Math.sin(t / 2300));
		}
		var eyeX0 = EYE_COL * SUB + look;
		var eyeY0 = EYE_ROW * SUB;

		ctx.setTransform(this.dpr, 0, 0, this.dpr, 0, 0);
		ctx.clearRect(0, 0, this.w, this.h);
		ctx.globalAlpha = 0.35 + 0.65 * this.progress;
		ctx.drawImage(this.field, 0, 0, this.w, this.h);

		// Floor shadow: a dithered ellipse that shrinks while the buddy hops.
		var floorY = this.oy + ROWS * v + Math.round(v * 0.9);
		var spread = (COLS * v * 0.62) * (1 - (Math.abs(hop) / (v * 3.2)) * 0.35);
		ctx.globalAlpha = this.floor * this.progress;
		ctx.fillStyle = '#000000';
		var s = Math.max(2, Math.round(v * 0.38));
		for (var fx = -spread; fx <= spread; fx += s * 1.6) {
			var k = 1 - Math.abs(fx) / spread;
			var rows = Math.max(1, Math.round(k * 3));
			for (var fy = 0; fy < rows; fy++) {
				ctx.fillRect(Math.round(centre + fx), floorY + fy * s * 1.3 - Math.round(rows * s * 0.65), s, s);
			}
		}

		var px = this.pointer.x;
		var py = this.pointer.y;
		var R = v * 7;
		var anyHeat = false;
		var list = this.voxels;
		for (var i = 0; i < list.length; i++) {
			var vx = list[i];
			var p = clamp((this.progress - vx.delay * 0.55) / 0.42, 0, 1);
			if (p <= 0) {
				continue;
			}
			var e = easeOut(p);
			var hx = this.ox + vx.gx * v;
			var hy = this.oy + vx.gy * v + bob + hop - (vx.arm && armUp ? v * SUB : 0);
			var sx = this.w * 0.5 + (vx.rx - 0.5) * this.w * 1.05;
			var sy = this.h * 0.45 + (vx.ry - 0.5) * this.h * 1.1;
			var x = sx + (hx - sx) * e;
			var y = sy + (hy - sy) * e;

			// The pointer pushes voxels away and warms them up.
			var tx = 0;
			var ty = 0;
			var heat = 0;
			if (this.pointer.on && p >= 1) {
				var dx = x + v / 2 - px;
				var dy = y + v / 2 - py;
				var dist = Math.hypot(dx, dy);
				if (dist < R && dist > 0.001) {
					var f = (1 - dist / R) * (1 - dist / R);
					tx = (dx / dist) * f * v * 1.15;
					ty = (dy / dist) * f * v * 1.15;
					heat = f;
				}
			}
			vx.ox += (tx - vx.ox) * 0.2;
			vx.oy += (ty - vx.oy) * 0.2;
			vx.heat += (heat - vx.heat) * (heat > vx.heat ? 0.35 : 0.06);
			if (vx.heat > 0.01 || Math.abs(vx.ox) > 0.2 || Math.abs(vx.oy) > 0.2) {
				anyHeat = true;
			}

			var isEye = !blink && vx.gy >= eyeY0 && vx.gy < eyeY0 + SUB && vx.gx >= eyeX0 && vx.gx < eyeX0 + SUB;
			var dxp = Math.round(x + vx.ox);
			var dyp = Math.round(y + vx.oy) - d;
			ctx.globalAlpha = Math.min(1, p * 1.6);
			ctx.drawImage(isEye ? this.sprites.eye : this.sprites.base[vx.tone], dxp, dyp, v + d, v + d);
			if (!isEye && vx.heat > 0.04) {
				ctx.globalAlpha = Math.min(1, vx.heat * 1.4);
				ctx.drawImage(this.sprites.hot, dxp, dyp, v + d, v + d);
			}
		}
		this.anyHeat = anyHeat;
		ctx.globalAlpha = 1;
	};

	/* ---------- Speech bubble ---------- */

	Buddy.prototype.showLine = function (index) {
		if (!this.bubble || !this.lines.length) {
			return;
		}
		this.lineIndex = index % this.lines.length;
		this.bubble.textContent = this.lines[this.lineIndex];
		this.bubble.classList.add('is-on');
	};

	Buddy.prototype.nextLine = function () {
		var self = this;
		if (!this.bubble || this.lines.length < 2) {
			return;
		}
		this.bubble.classList.remove('is-on');
		window.setTimeout(function () {
			self.showLine(self.lineIndex + 1);
		}, 220);
	};

	Buddy.prototype.startLines = function () {
		var self = this;
		if (this.reduced || this.lineTimer || this.lines.length < 2) {
			return;
		}
		this.lineTimer = window.setInterval(function () {
			if (self.visible && !document.hidden) {
				self.nextLine();
			}
		}, 3600);
	};

	Buddy.prototype.destroy = function () {
		this.alive = false;
		if (this.raf) {
			window.cancelAnimationFrame(this.raf);
		}
		if (this.ro) {
			this.ro.disconnect();
		}
		if (this.io) {
			this.io.disconnect();
		}
		if (this.lineTimer) {
			window.clearInterval(this.lineTimer);
		}
		if (this.onMove) {
			this.root.removeEventListener('pointermove', this.onMove);
			this.root.removeEventListener('pointerleave', this.onLeave);
			this.stage.removeEventListener('click', this.onTap);
			document.removeEventListener('visibilitychange', this.onVisibility);
		}
	};

	/* ---------- Copy email ---------- */

	function bindCopy(root) {
		var copied = root.getAttribute('data-cn-copied') || 'Copied';
		Array.prototype.forEach.call(root.querySelectorAll('[data-cn-copy]'), function (button) {
			var tip = button.querySelector('[data-cn-tip]');
			var label = tip ? tip.textContent : '';
			button.addEventListener('click', function () {
				var text = button.getAttribute('data-cn-copy');
				var done = function () {
					button.classList.add('is-copied');
					if (tip) {
						tip.textContent = copied;
					}
					window.setTimeout(function () {
						button.classList.remove('is-copied');
						if (tip) {
							tip.textContent = label;
						}
					}, 1600);
				};
				if (navigator.clipboard && window.isSecureContext) {
					navigator.clipboard.writeText(text).then(done, function () {
						window.location.href = 'mailto:' + text;
					});
				} else {
					window.location.href = 'mailto:' + text;
				}
			});
		});
	}

	/* ---------- Sentence blanks ---------- */

	/*
	 * Each blank in the mad-lib sentence is as wide as its answer (or its
	 * placeholder), capped at the line. Avix Forms sizes them too but caps every
	 * blank at 420px, which cuts long placeholders at large type; the width is
	 * written to --cn-w, which the stylesheet applies over the inline width.
	 */
	function fitBlanks(root) {
		var sentence = root.querySelector('.avf-sentence');
		var inputs = sentence ? Array.prototype.slice.call(sentence.querySelectorAll('input.avf-input')) : [];
		if (!inputs.length) {
			return;
		}
		var ctx = document.createElement('canvas').getContext('2d');
		if (!ctx) {
			return;
		}
		var fit = function (input) {
			var cs = window.getComputedStyle(input);
			var size = parseFloat(cs.fontSize) || 16;
			var text = input.value || input.placeholder || '';
			ctx.font = cs.fontWeight + ' ' + cs.fontSize + ' ' + cs.fontFamily;
			var spacing = (parseFloat(cs.letterSpacing) || 0) * text.length;
			var pad = (parseFloat(cs.paddingLeft) || 0) + (parseFloat(cs.paddingRight) || 0);
			var w = ctx.measureText(text).width + spacing + pad + size * 0.12;
			input.style.setProperty('--cn-w', Math.ceil(Math.min(w, sentence.clientWidth)) + 'px');
			input.classList.add('is-cn-fit');
		};
		var all = function () {
			inputs.forEach(fit);
		};
		inputs.forEach(function (input) {
			input.addEventListener('input', function () {
				fit(input);
			});
		});
		var timer = 0;
		window.addEventListener('resize', function () {
			window.clearTimeout(timer);
			timer = window.setTimeout(all, 120);
		});
		all();
		if (document.fonts && document.fonts.ready) {
			document.fonts.ready.then(all);
		}
	}

	/* ---------- Mount ---------- */

	function mount(root) {
		if (root.__avixCn) {
			return;
		}
		instances.slice().forEach(function (instance) {
			if (!instance.root.isConnected) {
				instance.destroy();
				instances.splice(instances.indexOf(instance), 1);
			}
		});
		root.__avixCn = true;
		bindCopy(root);
		fitBlanks(root);
		var stage = root.querySelector('[data-cn-stage]');
		if (stage) {
			var buddy = new Buddy(stage, root);
			if (buddy.alive) {
				instances.push(buddy);
			}
		}
	}

	function mountAll(scope) {
		Array.prototype.forEach.call((scope || document).querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixContact = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-contact.default', function ($scope) {
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
