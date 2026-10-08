/*!
 * Avix Digital · Case Study Kit
 * The pixel-resolve reveal shared by the case-study widgets: a screenshot
 * marked [data-csk-pixels] arrives behind a layer of 16px squares in the
 * mat's colour that clear in a random order, once. One tiny canvas (one
 * canvas pixel per square) drawn in a single rAF loop, then removed. Skipped
 * with reduced motion, in the editor, without IntersectionObserver, and for
 * screenshots already on screen at load (the LCP image is never hidden).
 * Exposes window.AvixCsk.
 */
(function (window, document) {
	'use strict';

	var SELECTOR = '[data-csk-pixels]';
	var WIDGETS = ['hero', 'chapter', 'spotlight', 'results', 'gallery', 'stack', 'next', 'grid'];
	var SQUARE = 16; // CSS px per square
	var SPREAD = 420; // ms: the last square starts clearing by then
	var FADE = 220; // ms: each square's fade
	var WAIT_IMG = 2500; // ms: longest wait for the screenshot to load before revealing anyway
	var SETTLE = 1200; // ms: partly visible but never 25% (very tall screenshots) → reveal anyway
	var DEFAULT_COVER = '#f5f3ef';

	var mq = function (query) {
		return window.matchMedia ? window.matchMedia(query) : { matches: false };
	};
	var reduceMotion = mq('(prefers-reduced-motion: reduce)');
	var supportsCanvas = (function () {
		try {
			var c = document.createElement('canvas');
			return !!(c.getContext && c.getContext('2d'));
		} catch (error) {
			return false;
		}
	})();
	var raf = window.requestAnimationFrame || function (fn) {
		return window.setTimeout(function () {
			fn(Date.now());
		}, 16);
	};
	var now = function () {
		return window.performance && window.performance.now ? window.performance.now() : Date.now();
	};

	var observer = null;
	var pending = [];

	function isEditMode() {
		return !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
	}

	function canAnimate() {
		return supportsCanvas && 'IntersectionObserver' in window && !reduceMotion.matches && !isEditMode();
	}

	function inViewport(el) {
		var rect = el.getBoundingClientRect();
		var vh = window.innerHeight || document.documentElement.clientHeight;
		var vw = window.innerWidth || document.documentElement.clientWidth;
		return rect.bottom > 0 && rect.right > 0 && rect.top < vh && rect.left < vw;
	}

	/**
	 * The cover colour as [r, g, b]: read from --csk-cover (a mat sets it; a
	 * dark section sets #0b0b0c) and resolved by the browser, so any CSS
	 * colour works, color-mix() included.
	 */
	function coverColor(el, probe) {
		var raw = '';
		try {
			raw = (window.getComputedStyle(el).getPropertyValue('--csk-cover') || '').trim();
		} catch (error) {
			raw = '';
		}
		probe.style.color = DEFAULT_COVER;
		if (raw) {
			probe.style.color = raw;
		}
		var value = window.getComputedStyle(probe).color || '';
		var m = value.match(/rgba?\(\s*([\d.]+)[,\s]+([\d.]+)[,\s]+([\d.]+)/);
		if (m) {
			return [Math.round(+m[1]), Math.round(+m[2]), Math.round(+m[3])];
		}
		// color(srgb 0.96 0.95 0.94) from color-mix().
		m = value.match(/color\(\s*srgb\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)/);
		if (m) {
			return [Math.round(+m[1] * 255), Math.round(+m[2] * 255), Math.round(+m[3] * 255)];
		}
		return [245, 243, 239];
	}

	/* ---------- One screen ---------- */

	function Resolve(screen) {
		this.screen = screen;
		this.alive = true;
		this.started = false;
		this.timers = [];
		this.canvas = null;
		this.arm();
	}

	Resolve.prototype.arm = function () {
		var screen = this.screen;
		var width = screen.clientWidth;
		var height = screen.clientHeight;
		if (!width || !height) {
			// Hidden (a closed tab or panel): arm when it is laid out.
			this.deferred = true;
			return;
		}
		this.deferred = false;

		var cols = Math.max(1, Math.ceil(width / SQUARE));
		var rows = Math.max(1, Math.ceil(height / SQUARE));
		var canvas = document.createElement('canvas');
		canvas.className = 'avix-csk-px';
		canvas.setAttribute('aria-hidden', 'true');
		canvas.width = cols;
		canvas.height = rows;
		screen.appendChild(canvas);

		var ctx = canvas.getContext('2d');
		var color = coverColor(screen, canvas);
		canvas.style.color = '';
		var image = ctx.createImageData(cols, rows);
		var delays = new Float32Array(cols * rows);
		var data = image.data;
		var i;
		for (i = 0; i < cols * rows; i++) {
			var x = i % cols;
			var y = Math.floor(i / cols);
			// Mostly random, with a light top-left → bottom-right drift so the
			// screenshot resolves into place rather than flickering.
			var drift = (x / cols + y / rows) / 2;
			delays[i] = (Math.random() * 0.78 + drift * 0.22) * SPREAD;
			data[i * 4] = color[0];
			data[i * 4 + 1] = color[1];
			data[i * 4 + 2] = color[2];
			data[i * 4 + 3] = 255;
		}
		ctx.putImageData(image, 0, 0);

		this.canvas = canvas;
		this.ctx = ctx;
		this.image = image;
		this.delays = delays;
		this.count = cols * rows;
		screen.classList.add('is-px-armed');
	};

	/** Called by the observer while the screen is (partly) on screen. */
	Resolve.prototype.seen = function (entry) {
		var self = this;
		if (this.started || !this.alive) {
			return;
		}
		if (this.deferred) {
			// It was hidden at load (a closed panel) and has only now been laid
			// out, already on screen: covering it now would flash, so it simply
			// shows.
			this.finish();
			return;
		}
		var rootHeight = entry.rootBounds ? entry.rootBounds.height : (window.innerHeight || 0);
		var enough = entry.intersectionRatio >= 0.25 || (rootHeight && entry.intersectionRect.height >= rootHeight * 0.25);
		if (enough) {
			this.start();
			return;
		}
		if (!this.settle) {
			this.settle = window.setTimeout(function () {
				self.settle = null;
				if (self.alive && self.visible) {
					self.start();
				}
			}, SETTLE);
			this.timers.push(this.settle);
		}
	};

	/** Waits (briefly) for the screenshot itself, then clears the squares. */
	Resolve.prototype.start = function () {
		var self = this;
		if (this.started || !this.alive) {
			return;
		}
		this.started = true;
		if (observer) {
			observer.unobserve(this.screen);
		}
		var img = this.screen.querySelector('img');
		if (!img || img.complete) {
			this.play();
			return;
		}
		var gone = false;
		var go = function () {
			if (gone) {
				return;
			}
			gone = true;
			img.removeEventListener('load', go);
			img.removeEventListener('error', go);
			self.play();
		};
		img.addEventListener('load', go);
		img.addEventListener('error', go);
		this.timers.push(window.setTimeout(go, WAIT_IMG));
	};

	Resolve.prototype.play = function () {
		var self = this;
		if (this.playing || !this.alive) {
			return;
		}
		if (!this.canvas) {
			this.finish();
			return;
		}
		this.playing = true;
		var t0 = now();
		var data = this.image.data;
		var delays = this.delays;
		var count = this.count;

		function tick() {
			if (!self.alive) {
				return;
			}
			var t = now() - t0;
			var busy = false;
			for (var i = 0; i < count; i++) {
				var p = (t - delays[i]) / FADE;
				if (p <= 0) {
					busy = true;
					continue;
				}
				if (p >= 1) {
					data[i * 4 + 3] = 0;
					continue;
				}
				busy = true;
				// Ease-in: a square holds, then gives way.
				data[i * 4 + 3] = Math.round(255 * (1 - p * p));
			}
			self.ctx.putImageData(self.image, 0, 0);
			if (busy) {
				raf(tick);
			} else {
				self.finish();
			}
		}
		raf(tick);
	};

	Resolve.prototype.finish = function () {
		this.started = true;
		if (this.canvas && this.canvas.parentNode) {
			this.canvas.parentNode.removeChild(this.canvas);
		}
		this.canvas = null;
		this.ctx = null;
		this.image = null;
		this.delays = null;
		this.screen.classList.remove('is-px-armed');
		this.screen.classList.add('is-px-done');
		this.destroy(true);
	};

	Resolve.prototype.destroy = function (keepDone) {
		this.timers.forEach(function (id) {
			window.clearTimeout(id);
		});
		this.timers = [];
		if (observer) {
			observer.unobserve(this.screen);
		}
		if (!keepDone && this.canvas && this.canvas.parentNode) {
			this.canvas.parentNode.removeChild(this.canvas);
			this.screen.classList.remove('is-px-armed');
		}
		this.alive = false;
		var index = pending.indexOf(this);
		if (index > -1) {
			pending.splice(index, 1);
		}
	};

	function ensureObserver() {
		if (observer || !('IntersectionObserver' in window)) {
			return observer;
		}
		observer = new window.IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				var instance = entry.target.__avixCsk;
				if (!instance || !instance.alive) {
					return;
				}
				if (!entry.target.isConnected) {
					instance.destroy();
					return;
				}
				instance.visible = entry.isIntersecting;
				if (entry.isIntersecting) {
					instance.seen(entry);
				}
			});
		}, { threshold: [0, 0.25, 0.5] });
		return observer;
	}

	/* ---------- Shader background ----------
	   A slow, domain-warped noise field in the accent colour on the section's
	   background, with a soft light that follows the pointer (or wanders on
	   touch screens). Plain WebGL 1, one full-screen triangle pair. Rendered
	   at half the CSS size (three quarters on dense screens), paused off
	   screen and in hidden tabs, one still frame with reduced motion. Text
	   stays readable: inside the "calm" rectangles the field is capped at a
	   dark tone (dark themes) or a faint tint (light themes). */

	var SHADER_VS = 'attribute vec2 a;void main(){gl_Position=vec4(a,0.,1.);}';
	var SHADER_FS = [
		'#ifdef GL_FRAGMENT_PRECISION_HIGH',
		'precision highp float;',
		'#else',
		'precision mediump float;',
		'#endif',
		'uniform vec2 R;uniform float S,T,O,A,K;uniform vec3 F,C0,C1,C2,C3;uniform vec4 P,Q[10];',
		'float h(vec2 p){return fract(sin(dot(p,vec2(127.1,311.7)))*43758.5453);}',
		'float n(vec2 p){vec2 i=floor(p),f=fract(p);f=f*f*(3.-2.*f);',
		'return mix(mix(h(i),h(i+vec2(1.,0.)),f.x),mix(h(i+vec2(0.,1.)),h(i+vec2(1.,1.)),f.x),f.y);}',
		'float fbm(vec2 p){float v=0.,a=.5;for(int k=0;k<4;k++){v+=a*n(p);p=mat2(1.6,1.2,-1.2,1.6)*p;a*=.5;}return v;}',
		'void main(){',
		// CSS px from the top left of the canvas.
		'vec2 c=vec2(gl_FragCoord.x,R.y-gl_FragCoord.y)*S;',
		'vec2 d=c-P.xy;float pl=P.z*exp(-dot(d,d)/(P.w*P.w));',
		'vec2 p=c/560.+vec2(0.,O);',
		// The pointer gently pushes the smoke aside.
		'p-=d/(length(d)+80.)*pl*.28;',
		// Stretched sideways, so the smoke flows in bands.
		'float t=T*.045;vec2 s=p*vec2(.7,1.25);',
		'vec2 q=vec2(fbm(s+vec2(0.,t)),fbm(s+vec2(5.2,1.3)-t*.8));',
		'float f=fbm(s+2.4*q+vec2(t*.6,-t*.35));',
		'float v=smoothstep(.35,.95,f+.2*q.x);',
		// Thin aurora filaments where the field crosses its middle.
		'float g=pow(1.-abs(2.*f-1.),9.);',
		'vec2 e=(c-F.xy)/F.z;float fo=exp(-dot(e,e)*1.4);',
		'float a=(.14*v+.06*g+fo*(.12+.6*v*v+.75*g)+pl*(.14+.42*v+.3*g))*A;',
		// Behind text: capped, with a wide edge the smoke itself frays, so
		// it reads as smoke parting around the words, never as a box.
		'float m=0.;',
		'for(int i=0;i<10;i++){vec2 o=max(max(Q[i].xy-c,c-Q[i].zw),0.);m=max(m,1.-smoothstep(0.,150.,length(o)+(f-.5)*180.));}',
		'a=mix(a,min(a,K),m);',
		// base → ember → deep → accent → warm highlight.
		'vec3 col=mix(C0,mix(C0,C1,.38),smoothstep(0.,.4,a));',
		'col=mix(col,C1,smoothstep(.3,.75,a));',
		'col=mix(col,C2,smoothstep(.65,1.05,a));',
		'col=mix(col,C3,smoothstep(.95,1.45,a));',
		// Film grain, also against banding.
		'col+=(h(gl_FragCoord.xy+fract(T*.37)*97.)-.5)*.025;',
		'gl_FragColor=vec4(col,1.);}'
	].join('\n');
	var CALM = 10;
	var FAR = [-1e5, -1e5, -1e5 + 1, -1e5 + 1];

	var finePointer = mq('(hover: hover) and (pointer: fine)');
	var caf = window.cancelAnimationFrame || window.clearTimeout;

	function parseColor(el, value, fallback) {
		var raw = (value || '').trim();
		if (!raw) {
			return fallback;
		}
		el.style.color = '';
		el.style.color = raw;
		var out = window.getComputedStyle(el).color || '';
		el.style.color = '';
		var m = out.match(/rgba?\(\s*([\d.]+)[,\s]+([\d.]+)[,\s]+([\d.]+)/);
		if (m) {
			return [+m[1] / 255, +m[2] / 255, +m[3] / 255];
		}
		m = out.match(/color\(\s*srgb\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)/);
		return m ? [+m[1], +m[2], +m[3]] : fallback;
	}

	function mixRgb(a, b, t) {
		return [a[0] + (b[0] - a[0]) * t, a[1] + (b[1] - a[1]) * t, a[2] + (b[2] - a[2]) * t];
	}

	/** base → deep → accent → warm highlight; softer tints on a light base. */
	function shaderPalette(base, accent) {
		var hot = mixRgb(accent, [1, 0.86, 0.62], 0.35);
		var luma = 0.2126 * base[0] + 0.7152 * base[1] + 0.0722 * base[2];
		if (luma > 0.5) {
			return { light: true, c: [base, mixRgb(base, accent, 0.14), mixRgb(base, accent, 0.34), mixRgb(base, hot, 0.62)] };
		}
		return { light: false, c: [base, [accent[0] * 0.78, accent[1] * 0.78, accent[2] * 0.78], accent, hot] };
	}

	/**
	 * @param {Element} host  Positioned element the canvas fills (top left).
	 * @param {Object}  opts {
	 *   className: extra canvas class;
	 *   intensity: 1 = designed look; speed: 1 = designed pace (0 = still);
	 *   pointer: follow a fine pointer (true);
	 *   colors(): { base, accent } as CSS colours (read on every refresh);
	 *   layout(hostRect): { height, fade, focus: [x, y, r], calm: [[x0, y0, x1, y1] …] }
	 *     in CSS px from the host's top left;
	 *   onState(on): the canvas is showing (true) or the caller's fallback should (false).
	 * }
	 */
	function Shader(host, opts) {
		this.host = host;
		this.opts = opts || {};
		this.alive = true;
		this.visible = true;
		this.time = 7 + Math.random() * 40;
		this.quality = 1;
		this.slow = 0;
		this.raf = 0;
		this.timers = [];
		this.ptr = { x: 0, y: 0, s: 0, tx: 0, ty: 0, ts: 0, inside: false, set: false };
		this.animate = !reduceMotion.matches && (this.opts.speed > 0 || this.opts.pointer !== false);
		this.frame = this.frame.bind(this);
		this.refresh = this.refresh.bind(this);
		this.onVisibility = this.onVisibility.bind(this);
		this.onMove = this.onMove.bind(this);
		this.onLeave = this.onLeave.bind(this);
		this.onLost = this.onLost.bind(this);
		this.onRestored = this.onRestored.bind(this);
		this.ok = this.init();
	}

	Shader.prototype.init = function () {
		var self = this;
		var canvas = document.createElement('canvas');
		canvas.className = ('avix-csk-shader ' + (this.opts.className || '')).trim();
		canvas.setAttribute('aria-hidden', 'true');
		this.canvas = canvas;
		var gl = null;
		var attrs = { alpha: false, antialias: false, depth: false, stencil: false, premultipliedAlpha: false, preserveDrawingBuffer: false, powerPreference: 'low-power' };
		try {
			gl = canvas.getContext('webgl', attrs) || canvas.getContext('experimental-webgl', attrs);
		} catch (error) {
			gl = null;
		}
		if (!gl) {
			return false;
		}
		this.gl = gl;
		this.host.appendChild(canvas);
		if (!this.build()) {
			this.destroy();
			return false;
		}
		canvas.addEventListener('webglcontextlost', this.onLost, false);
		canvas.addEventListener('webglcontextrestored', this.onRestored, false);

		if ('IntersectionObserver' in window) {
			this.io = new window.IntersectionObserver(function (entries) {
				self.visible = entries[entries.length - 1].isIntersecting;
				self.loop();
			}, { rootMargin: '80px 0px' });
			this.io.observe(this.host);
		}
		if ('ResizeObserver' in window) {
			this.ro = new window.ResizeObserver(function () {
				self.later(self.refresh, 120, 'resize');
			});
			this.ro.observe(this.host);
		} else {
			this.onResize = function () {
				self.later(self.refresh, 150, 'resize');
			};
			window.addEventListener('resize', this.onResize, { passive: true });
		}
		document.addEventListener('visibilitychange', this.onVisibility);
		this.pointer = this.animate && this.opts.pointer !== false && finePointer.matches;
		if (this.pointer) {
			this.target = this.opts.root || this.host;
			this.target.addEventListener('pointermove', this.onMove, { passive: true });
			this.target.addEventListener('pointerleave', this.onLeave, { passive: true });
		}
		// Web fonts and the entrance move the text a little: measure again.
		if (document.fonts && document.fonts.ready) {
			document.fonts.ready.then(function () {
				if (self.alive) {
					self.refresh();
				}
			});
		}
		this.timers.push(window.setTimeout(this.refresh, 1300));

		this.refresh();
		this.draw();
		// Fade in once the first frame is on the canvas.
		raf(function () {
			if (!self.alive) {
				return;
			}
			canvas.classList.add('is-on');
			if (self.opts.onState) {
				self.opts.onState(true);
			}
			self.loop();
		});
		return true;
	};

	Shader.prototype.build = function () {
		var gl = this.gl;
		var compile = function (type, src) {
			var s = gl.createShader(type);
			gl.shaderSource(s, src);
			gl.compileShader(s);
			return gl.getShaderParameter(s, gl.COMPILE_STATUS) ? s : null;
		};
		var vs = compile(gl.VERTEX_SHADER, SHADER_VS);
		var fs = compile(gl.FRAGMENT_SHADER, SHADER_FS);
		if (!vs || !fs) {
			return false;
		}
		var prog = gl.createProgram();
		gl.attachShader(prog, vs);
		gl.attachShader(prog, fs);
		gl.linkProgram(prog);
		if (!gl.getProgramParameter(prog, gl.LINK_STATUS)) {
			return false;
		}
		gl.useProgram(prog);
		var buf = gl.createBuffer();
		gl.bindBuffer(gl.ARRAY_BUFFER, buf);
		gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 1, -1, -1, 1, 1, 1]), gl.STATIC_DRAW);
		var loc = gl.getAttribLocation(prog, 'a');
		gl.enableVertexAttribArray(loc);
		gl.vertexAttribPointer(loc, 2, gl.FLOAT, false, 0, 0);
		var u = {};
		['R', 'S', 'T', 'O', 'A', 'K', 'F', 'C0', 'C1', 'C2', 'C3', 'P', 'Q'].forEach(function (name) {
			u[name] = gl.getUniformLocation(prog, name);
		});
		this.u = u;
		this.dirty = true;
		return true;
	};

	Shader.prototype.later = function (fn, ms, key) {
		var self = this;
		window.clearTimeout(this[key + 'Timer']);
		this[key + 'Timer'] = window.setTimeout(function () {
			if (self.alive) {
				fn();
			}
		}, ms);
	};

	/** Re-reads the colours and the layout, resizes the canvas, redraws. */
	Shader.prototype.refresh = function () {
		if (!this.alive || !this.gl) {
			return;
		}
		if (!this.host.isConnected) {
			this.destroy();
			return;
		}
		var rect = this.host.getBoundingClientRect();
		var w = Math.max(1, Math.round(rect.width));
		var lay = (this.opts.layout && this.opts.layout(rect)) || {};
		var h = Math.max(1, Math.round(Math.min(lay.height || rect.height, rect.height || 1)));
		var scale = Math.min(window.devicePixelRatio || 1, 1.5) * 0.5 * this.quality;
		var bw = Math.max(1, Math.round(w * scale));
		var bh = Math.max(1, Math.round(h * scale));
		var canvas = this.canvas;
		canvas.style.height = h + 'px';
		canvas.style.setProperty('--csk-shader-fade', Math.round(lay.fade || h * 0.3) + 'px');
		if (canvas.width !== bw || canvas.height !== bh) {
			canvas.width = bw;
			canvas.height = bh;
		}
		this.w = w;
		this.h = h;
		this.css = w / bw;

		var colors = (this.opts.colors && this.opts.colors()) || {};
		var base = parseColor(canvas, colors.base, [0.043, 0.043, 0.047]);
		var accent = parseColor(canvas, colors.accent, [0.984, 0.376, 0.027]);
		this.pal = shaderPalette(base, accent);

		var focus = lay.focus || [w * 0.8, Math.min(h * 0.3, 360), Math.max(260, w * 0.36)];
		this.focus = focus;
		var calm = [];
		var list = lay.calm || [];
		for (var i = 0; i < CALM; i++) {
			var r = list[i];
			calm.push.apply(calm, r && r[2] > r[0] && r[3] > r[1] ? r : FAR);
		}
		this.calm = new Float32Array(calm);
		this.dirty = true;
		if (!this.ptr.set) {
			// Animated, the light fades in; a still frame keeps it in place.
			this.ptr.x = this.ptr.tx = focus[0] - focus[2] * 0.25;
			this.ptr.y = this.ptr.ty = focus[1] + focus[2] * 0.15;
			this.ptr.s = this.animate ? 0 : 0.5;
			this.ptr.set = true;
		}
		if (!this.raf) {
			this.draw();
		}
	};

	Shader.prototype.draw = function () {
		var gl = this.gl;
		var u = this.u;
		if (!gl || this.lost || !u || !this.pal) {
			return;
		}
		var o = this.opts;
		var c = this.pal.c;
		gl.viewport(0, 0, this.canvas.width, this.canvas.height);
		if (this.dirty) {
			gl.uniform2f(u.R, this.canvas.width, this.canvas.height);
			gl.uniform1f(u.S, this.css);
			gl.uniform1f(u.A, (o.intensity >= 0 ? o.intensity : 1) * (this.pal.light ? 1.05 : 1));
			gl.uniform1f(u.K, this.pal.light ? 0.3 : 0.2);
			gl.uniform3f(u.F, this.focus[0], this.focus[1], this.focus[2]);
			gl.uniform3f(u.C0, c[0][0], c[0][1], c[0][2]);
			gl.uniform3f(u.C1, c[1][0], c[1][1], c[1][2]);
			gl.uniform3f(u.C2, c[2][0], c[2][1], c[2][2]);
			gl.uniform3f(u.C3, c[3][0], c[3][1], c[3][2]);
			gl.uniform4fv(u.Q, this.calm);
			this.dirty = false;
		}
		var radius = Math.max(220, Math.min(520, this.w * 0.35));
		gl.uniform1f(u.T, this.time);
		gl.uniform1f(u.O, this.animate ? (window.pageYOffset || 0) / 2400 : 0);
		gl.uniform4f(u.P, this.ptr.x, this.ptr.y, this.ptr.s, radius);
		gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
	};

	Shader.prototype.running = function () {
		return this.alive && this.animate && this.visible && !this.lost && !document.hidden;
	};

	Shader.prototype.loop = function () {
		if (this.raf || !this.running()) {
			return;
		}
		this.last = 0;
		this.raf = raf(this.frame);
	};

	Shader.prototype.frame = function (now) {
		this.raf = 0;
		if (!this.running()) {
			return;
		}
		if (!this.host.isConnected) {
			this.destroy();
			return;
		}
		now = now || Date.now();
		var dt = this.last ? Math.min(0.1, (now - this.last) / 1000) : 1 / 60;
		this.last = now;
		this.time += dt * (this.opts.speed >= 0 ? this.opts.speed : 1);

		// The light: eased towards the pointer, or wandering around the focus.
		var p = this.ptr;
		if (!p.inside) {
			var f = this.focus;
			p.tx = f[0] + Math.sin(this.time * 0.21) * f[2] * 0.55;
			p.ty = f[1] + Math.sin(this.time * 0.17 + 1.3) * f[2] * 0.35;
			p.ts = 0.55;
		}
		var k = 1 - Math.pow(0.94, dt * 60);
		p.x += (p.tx - p.x) * k;
		p.y += (p.ty - p.y) * k;
		p.s += (p.ts - p.s) * k;

		// A slow GPU: drop the resolution once or twice (never below 0.5).
		this.avg = this.avg ? this.avg * 0.95 + dt * 0.05 : dt;
		if (++this.slow > 90 && this.avg > 0.03 && this.quality > 0.5) {
			this.quality = Math.max(0.5, this.quality * 0.7);
			this.slow = 0;
			this.refresh();
		}

		this.draw();
		this.raf = raf(this.frame);
	};

	Shader.prototype.onMove = function (event) {
		if (event.pointerType === 'touch') {
			return;
		}
		var rect = this.canvas.getBoundingClientRect();
		this.ptr.tx = event.clientX - rect.left;
		this.ptr.ty = event.clientY - rect.top;
		this.ptr.ts = 1;
		this.ptr.inside = true;
	};

	Shader.prototype.onLeave = function () {
		this.ptr.inside = false;
	};

	Shader.prototype.onVisibility = function () {
		this.loop();
	};

	Shader.prototype.onLost = function (event) {
		event.preventDefault();
		this.lost = true;
		caf(this.raf);
		this.raf = 0;
		this.canvas.classList.remove('is-on');
		if (this.opts.onState) {
			this.opts.onState(false);
		}
	};

	Shader.prototype.onRestored = function () {
		if (!this.alive || !this.build()) {
			return;
		}
		this.lost = false;
		this.refresh();
		this.draw();
		this.canvas.classList.add('is-on');
		if (this.opts.onState) {
			this.opts.onState(true);
		}
		this.loop();
	};

	Shader.prototype.destroy = function () {
		if (!this.alive) {
			return;
		}
		this.alive = false;
		caf(this.raf);
		this.raf = 0;
		this.timers.forEach(function (id) {
			window.clearTimeout(id);
		});
		window.clearTimeout(this.resizeTimer);
		if (this.io) {
			this.io.disconnect();
		}
		if (this.ro) {
			this.ro.disconnect();
		}
		if (this.onResize) {
			window.removeEventListener('resize', this.onResize, { passive: true });
		}
		document.removeEventListener('visibilitychange', this.onVisibility);
		if (this.target) {
			this.target.removeEventListener('pointermove', this.onMove, { passive: true });
			this.target.removeEventListener('pointerleave', this.onLeave, { passive: true });
		}
		if (this.canvas) {
			this.canvas.removeEventListener('webglcontextlost', this.onLost, false);
			this.canvas.removeEventListener('webglcontextrestored', this.onRestored, false);
		}
		// Free the GPU context now: browsers cap the live contexts per page.
		if (this.gl) {
			var ext = this.gl.getExtension('WEBGL_lose_context');
			if (ext) {
				ext.loseContext();
			}
		}
		if (this.canvas && this.canvas.parentNode) {
			this.canvas.parentNode.removeChild(this.canvas);
		}
		this.gl = null;
	};

	/**
	 * Starts a shader background in `host`. Returns the controller
	 * ({ refresh, destroy }), or null without WebGL (keep the CSS fallback).
	 */
	function shader(host, opts) {
		if (!host || !window.WebGLRenderingContext) {
			return null;
		}
		var instance = new Shader(host, opts);
		return instance.ok ? instance : null;
	}

	/* ---------- Public API ---------- */

	/**
	 * Arms one screen for the pixel reveal (idempotent). Screens already on
	 * screen, and every screen when motion is off, are simply marked done.
	 */
	function pixelResolve(screen) {
		if (!screen || screen.__avixCskDone || (screen.__avixCsk && screen.__avixCsk.alive)) {
			return;
		}
		// Editor re-renders replace the DOM: forget screens that are gone.
		pending.slice().forEach(function (instance) {
			if (!instance.screen.isConnected) {
				instance.destroy();
			}
		});
		if (!canAnimate() || inViewport(screen)) {
			screen.__avixCskDone = true;
			screen.classList.add('is-px-done');
			return;
		}
		var instance = new Resolve(screen);
		screen.__avixCsk = instance;
		pending.push(instance);
		ensureObserver().observe(screen);
	}

	/** Reveals one armed screen now (e.g. a gallery item opened in a lightbox). */
	function reveal(screen) {
		if (screen && screen.__avixCsk && screen.__avixCsk.alive) {
			screen.__avixCsk.start();
		}
	}

	function mount(el) {
		if (!el || !el.matches) {
			return;
		}
		if (el.matches(SELECTOR)) {
			pixelResolve(el);
		}
	}

	function mountAll(scope) {
		var root = scope || document;
		if (root.matches && root.matches(SELECTOR)) {
			pixelResolve(root);
		}
		if (root.querySelectorAll) {
			Array.prototype.forEach.call(root.querySelectorAll(SELECTOR), pixelResolve);
		}
	}

	window.AvixCsk = {
		mount: mount,
		mountAll: mountAll,
		pixelResolve: pixelResolve,
		reveal: reveal,
		shader: shader,
		isEditMode: isEditMode,
		reduceMotion: reduceMotion
	};

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
		WIDGETS.forEach(function (name) {
			window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-case-study-' + name + '.default', function ($scope) {
				var element = $scope && $scope[0] ? $scope[0] : $scope;
				if (element && element.querySelectorAll) {
					mountAll(element);
				}
			});
		});
	}

	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		hookElementor();
	} else {
		window.addEventListener('elementor/frontend/init', hookElementor);
	}
})(window, document);
