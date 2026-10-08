/*!
 * Avix Digital · Case Study Kit
 * The pixel-resolve reveal shared by the case-study widgets: a screenshot
 * marked [data-csk-pixels] arrives behind a layer of 16px squares in the
 * mat's colour that clear in a random order, once. One tiny canvas (one
 * canvas pixel per square) drawn in a single rAF loop, then removed. Skipped
 * with reduced motion, in the editor, without IntersectionObserver, and for
 * screenshots already on screen at load (the LCP image is never hidden).
 * Also the WebGL shader background (AvixCsk.shader): the brand's pixel
 * squares glowing in an orange flow, or the smooth flow on its own.
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
	   touch screens). Plain WebGL 1. Two styles:

	   - "pixel" (default): the brand's pixel square. The field is sampled once
	     per square cell into a tiny texture (one texel per cell), then drawn
	     as glowing squares with hairline gaps whose size and brightness follow
	     the flow, over a soft haze of the same field. The pointer light lifts
	     and brightens the squares near it, a fast pointer sends a ripple of
	     squares outwards, a few squares now and then light up fully orange and
	     fade (sparkles), a quiet square grid shows in the dark parts, and on
	     first load the squares assemble from the focus outwards. Rendered at
	     the screen's density (at most 1.5x) so the squares stay crisp.
	   - "smooth": the flowing smoke, rendered at half the CSS size.

	   Both pause off screen and in hidden tabs and draw one still frame with
	   reduced motion. Text stays readable: inside the "calm" rectangles the
	   field is capped at a dark tone (dark themes) or a faint tint (light
	   themes), and the squares dissolve cell by cell. */

	var SHADER_VS = 'attribute vec2 a;void main(){gl_Position=vec4(a,0.,1.);}';
	var GLSL_HEAD = [
		'#ifdef GL_FRAGMENT_PRECISION_HIGH',
		'precision highp float;',
		'#else',
		'precision mediump float;',
		'#endif'
	].join('\n');
	var GLSL_NOISE = [
		'float h(vec2 p){return fract(sin(dot(p,vec2(127.1,311.7)))*43758.5453);}',
		'float n(vec2 p){vec2 i=floor(p),f=fract(p);f=f*f*(3.-2.*f);',
		'return mix(mix(h(i),h(i+vec2(1.,0.)),f.x),mix(h(i+vec2(0.,1.)),h(i+vec2(1.,1.)),f.x),f.y);}',
		'float fbm(vec2 p){float v=0.,a=.5;for(int k=0;k<4;k++){v+=a*n(p);p=mat2(1.6,1.2,-1.2,1.6)*p;a*=.5;}return v;}'
	].join('\n');
	// The flow at CSS position c: f (field), q (warp), v (bands), g (filaments).
	var GLSL_FLOW = [
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
		'vec2 e=(c-F.xy)/F.z;float fo=exp(-dot(e,e)*1.4);'
	].join('\n');
	// Behind text: a mask with a wide edge the smoke itself frays, so it
	// reads as smoke parting around the words, never as a box.
	var GLSL_CALM = 'float m=0.;for(int i=0;i<10;i++){vec2 o=max(max(Q[i].xy-c,c-Q[i].zw),0.);m=max(m,1.-smoothstep(0.,150.,length(o)+(f-.5)*180.));}';
	// base → ember → deep → accent → warm highlight.
	var GLSL_PALETTE = [
		'vec3 pal(float a){vec3 col=mix(C0,mix(C0,C1,.38),smoothstep(0.,.4,a));',
		'col=mix(col,C1,smoothstep(.3,.75,a));',
		'col=mix(col,C2,smoothstep(.65,1.05,a));',
		'return mix(col,C3,smoothstep(.95,1.45,a));}'
	].join('\n');

	/* Smooth flow: one pass at half the CSS size. */
	var SMOOTH_FS = [
		GLSL_HEAD,
		'uniform vec2 R;uniform float S,T,O,A,K;uniform vec3 F,C0,C1,C2,C3;uniform vec4 P,Q[10];',
		GLSL_NOISE,
		GLSL_PALETTE,
		'void main(){',
		// CSS px from the top left of the canvas.
		'vec2 c=vec2(gl_FragCoord.x,R.y-gl_FragCoord.y)*S;',
		'vec2 d=c-P.xy;float pl=P.z*exp(-dot(d,d)/(P.w*P.w));',
		GLSL_FLOW,
		'float a=(.14*v+.06*g+fo*(.12+.6*v*v+.75*g)+pl*(.14+.42*v+.3*g))*A;',
		GLSL_CALM,
		'a=mix(a,min(a,K),m);',
		'vec3 col=pal(a);',
		// Film grain, also against banding.
		'col+=(h(gl_FragCoord.xy+fract(T*.37)*97.)-.5)*.025;',
		'gl_FragColor=vec4(col,1.);}'
	].join('\n');

	/* Pixel mosaic, pass 1: one fragment per square cell (G = columns, rows;
	   Z = the grid's origin and cell size in CSS px). Writes the field
	   (r, halved), the calm mask (g) and the filaments (b). */
	var FIELD_FS = [
		GLSL_HEAD,
		'uniform vec2 G;uniform vec3 Z,F;uniform float T,O,A;uniform vec4 P,Q[10];',
		GLSL_NOISE,
		'void main(){',
		'vec2 id=vec2(floor(gl_FragCoord.x),G.y-1.-floor(gl_FragCoord.y));',
		'vec2 c=Z.xy+(id+.5)*Z.z;',
		'vec2 d=c-P.xy;float pl=P.z*exp(-dot(d,d)/(P.w*P.w));',
		GLSL_FLOW,
		'float a=(.14*v+.06*g+fo*(.12+.6*v*v+.75*g))*A;',
		GLSL_CALM,
		'gl_FragColor=vec4(clamp(a*.5,0.,1.),m,g,1.);}'
	].join('\n');

	/* Pixel mosaic, pass 2: the squares. W = seconds since start (sparkles,
	   ripples), B = seconds of the first-load assembly (large = done),
	   SP = sparkle density (0 = off), HZ = haze strength, L = hairline
	   colour and alpha, RP = up to four ripples (x, y, start, strength). */
	var MOSAIC_FS = [
		GLSL_HEAD,
		'uniform sampler2D X;uniform vec2 R,G;uniform vec3 Z,F,C0,C1,C2,C3,AC;uniform float S,T,W,B,K,SP,HZ,SQ;uniform vec4 P,L,RP[4];',
		'float h(vec2 p){return fract(sin(dot(p,vec2(127.1,311.7)))*43758.5453);}',
		GLSL_PALETTE,
		'vec4 tx(vec2 u){return texture2D(X,vec2(u.x/G.x,1.-u.y/G.y));}',
		'void main(){',
		'vec2 c=vec2(gl_FragCoord.x,R.y-gl_FragCoord.y)*S;',
		'vec2 u=(c-Z.xy)/Z.z;vec2 id=floor(u);vec2 l=fract(u)-.5;',
		'vec2 cc=Z.xy+(id+.5)*Z.z;',
		'vec4 fx=tx(id+.5);float a=fx.r*2.,m=fx.g,g=fx.b;',
		// The haze: the same field, blurred over five taps.
		'vec4 hz=(tx(u)*2.+tx(u+vec2(1.6,0.))+tx(u-vec2(1.6,0.))+tx(u+vec2(0.,1.6))+tx(u-vec2(0.,1.6)))/6.;',
		'float ha=hz.r*2.;ha=mix(ha,min(ha,K),hz.g);',
		// First load: the squares assemble from the focus outwards, each with
		// a brief flash as it lands; the haze fades in under them.
		'float bd=clamp(length(cc-F.xy)/(F.z*3.2),0.,1.)*.62+h(id*.913+vec2(1.3,7.7))*.38;',
		'float bk=smoothstep(bd*1.15,bd*1.15+.32,B);float fl=bk*(1.-bk)*2.4;',
		'ha*=smoothstep(0.,1.4,B);',
		// The pointer light (per cell, so a whole square lifts) and ripples.
		'vec2 d=cc-P.xy;float pl=P.z*exp(-dot(d,d)/(P.w*P.w));',
		'float rp=0.;',
		'for(int i=0;i<4;i++){vec4 r=RP[i];float age=W-r.z;',
		'if(age>0.&&age<1.8){float dd=length(cc-r.xy)-age*380.;float k=1.-age/1.8;rp+=r.w*exp(-dd*dd/2200.)*k*k;}}',
		// Each square keeps a little of its own brightness, like a real
		// LED wall; the pointer lifts the squares under it (more light than
		// size, so the gaps stay open).
		'float pt=pl*(.08+.34*a);',
		'float lv=a*(.84+.32*h(id*1.71+vec2(4.2,8.6)))+pt+rp*(.8+.3*a)+fl*.5;',
		// Behind text the squares dissolve, cell by cell.
		'float keep=smoothstep(m-.18,m+.18,h(id*1.31+vec2(7.1,2.3))*.9+.05);',
		'lv*=keep*(1.-m*.6);float lz=lv-pt*.45*keep;',
		// Sparkles: now and then a square glows up and slowly fades.
		'float sp=0.;',
		'if(SP>0.){float per=6.+h(id+vec2(3.7,9.1))*8.;float ph=W/per+h(id+vec2(11.3,2.9));',
		'float k=floor(ph);float ta=fract(ph)*per;',
		'float luck=step(h(vec2(id.x*1.7+mod(k,97.)*.131,id.y*2.3-mod(k,89.)*.077)),SP*(.4+.6*smoothstep(.04,.5,a)));',
		'sp=luck*smoothstep(0.,.4,ta)*exp(-ta*1.1)*(1.-smoothstep(.05,.35,m))*bk;}',
		// Square size follows the light (area ~ brightness), tiny gaps at full.
		'float s=smoothstep(.14,1.2,lz);',
		'float side=.84*pow(s,.85)*bk;side=max(side,.9*sqrt(sp));side=min(side,.9);',
		'float aa=S/Z.z;float db=max(abs(l.x),abs(l.y));',
		'float inq=1.-smoothstep(side*.5-aa*.5,side*.5+aa*.5,db);',
		'vec3 bg=pal(ha*HZ);',
		// The quiet grid: a hairline on the cell edges, only where it is dark.
		'float ln=1.-smoothstep(0.,aa,.5-db);',
		'bg=mix(bg,L.rgb,ln*L.a*(1.-smoothstep(.02,.45,ha))*(1.-hz.g*.75)*smoothstep(0.,1.,B));',
		'bg+=(h(gl_FragCoord.xy+fract(T*.37)*97.)-.5)*.02;',
		'vec3 sq=pal(lv*.95+SQ);',
		// A soft light in the middle of every square: glowing pixels.
		'sq*=1.+.06*(1.-db*2.);',
		'sq=mix(sq,AC,smoothstep(0.,.8,sp));sq=mix(sq,C3,smoothstep(.75,1.,sp)*.5);',
		'gl_FragColor=vec4(mix(bg,sq,inq),1.);}'
	].join('\n');

	var CALM = 10;
	var RIPPLES = 4;
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

	/**
	 * base → deep → accent → warm highlight; softer tints on a light base.
	 * On paper the pixel squares run all the way to the brand orange (the
	 * haze stays a soft tint), so the light mosaic is as rich as the dark.
	 */
	function shaderPalette(base, accent, pixel) {
		var hot = mixRgb(accent, [1, 0.86, 0.62], 0.35);
		var luma = 0.2126 * base[0] + 0.7152 * base[1] + 0.0722 * base[2];
		if (luma > 0.5 && pixel) {
			return { light: true, accent: accent, c: [base, mixRgb(base, accent, 0.16), mixRgb(base, accent, 0.62), mixRgb(accent, hot, 0.3)] };
		}
		if (luma > 0.5) {
			return { light: true, accent: accent, c: [base, mixRgb(base, accent, 0.14), mixRgb(base, accent, 0.34), mixRgb(base, hot, 0.62)] };
		}
		return { light: false, accent: accent, c: [base, [accent[0] * 0.78, accent[1] * 0.78, accent[2] * 0.78], accent, hot] };
	}

	/**
	 * @param {Element} host  Positioned element the canvas fills (top left).
	 * @param {Object}  opts {
	 *   className: extra canvas class;
	 *   style: 'pixel' (the default) or 'smooth';
	 *   cell(width): the pixel size in CSS px for a host this wide (or a number);
	 *   sparkles: squares that light up now and then (true; a number scales how many);
	 *   assemble: the squares assemble on first load (true);
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
		this.style = this.opts.style === 'smooth' ? 'smooth' : 'pixel';
		this.time = 7 + Math.random() * 40;
		this.wall = 0;
		this.quality = 1;
		this.slow = 0;
		this.raf = 0;
		this.timers = [];
		this.ptr = { x: 0, y: 0, s: 0, tx: 0, ty: 0, ts: 0, inside: false, set: false };
		this.ripples = new Float32Array(RIPPLES * 4);
		this.rippleAt = -1;
		this.rippleNext = 0;
		this.speed = 0;
		this.animate = !reduceMotion.matches && (this.opts.speed > 0 || this.opts.pointer !== false);
		this.build_t = this.animate && this.opts.assemble !== false ? 0 : 99;
		this.frame = this.frame.bind(this);
		this.refresh = this.refresh.bind(this);
		this.onVisibility = this.onVisibility.bind(this);
		this.onMove = this.onMove.bind(this);
		this.onLeave = this.onLeave.bind(this);
		this.onLost = this.onLost.bind(this);
		this.onRestored = this.onRestored.bind(this);
		for (var i = 0; i < RIPPLES; i++) {
			this.ripples[i * 4] = -1e5;
			this.ripples[i * 4 + 2] = -100;
		}
		this.ok = this.init();
	}

	Shader.prototype.init = function () {
		var self = this;
		var canvas = document.createElement('canvas');
		canvas.className = ('avix-csk-shader avix-csk-shader--' + this.style + ' ' + (this.opts.className || '')).trim();
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

	/** Compiles the programs (and the pixel style's cell texture). */
	Shader.prototype.build = function () {
		var gl = this.gl;
		var compile = function (type, src) {
			var s = gl.createShader(type);
			gl.shaderSource(s, src);
			gl.compileShader(s);
			return gl.getShaderParameter(s, gl.COMPILE_STATUS) ? s : null;
		};
		var program = function (src, names) {
			var vs = compile(gl.VERTEX_SHADER, SHADER_VS);
			var fs = compile(gl.FRAGMENT_SHADER, src);
			if (!vs || !fs) {
				return null;
			}
			var prog = gl.createProgram();
			gl.attachShader(prog, vs);
			gl.attachShader(prog, fs);
			gl.bindAttribLocation(prog, 0, 'a');
			gl.linkProgram(prog);
			if (!gl.getProgramParameter(prog, gl.LINK_STATUS)) {
				return null;
			}
			var u = {};
			names.forEach(function (name) {
				u[name] = gl.getUniformLocation(prog, name);
			});
			return { prog: prog, u: u };
		};
		var buf = gl.createBuffer();
		gl.bindBuffer(gl.ARRAY_BUFFER, buf);
		gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 1, -1, -1, 1, 1, 1]), gl.STATIC_DRAW);
		gl.enableVertexAttribArray(0);
		gl.vertexAttribPointer(0, 2, gl.FLOAT, false, 0, 0);

		this.field = null;
		this.mosaic = null;
		this.smooth = null;
		this.cols = 0;
		this.rows = 0;
		if (this.style === 'pixel') {
			this.field = program(FIELD_FS, ['G', 'Z', 'F', 'T', 'O', 'A', 'P', 'Q']);
			this.mosaic = program(MOSAIC_FS, ['X', 'R', 'G', 'Z', 'F', 'C0', 'C1', 'C2', 'C3', 'AC', 'S', 'T', 'W', 'B', 'K', 'SP', 'HZ', 'SQ', 'P', 'L', 'RP']);
			if (!this.field || !this.mosaic || !this.cellTarget()) {
				// A GPU that cannot run the mosaic still gets the smooth flow.
				this.style = 'smooth';
				this.field = this.mosaic = null;
				this.canvas.classList.remove('avix-csk-shader--pixel');
				this.canvas.classList.add('avix-csk-shader--smooth');
			}
		}
		if (this.style === 'smooth') {
			this.smooth = program(SMOOTH_FS, ['R', 'S', 'T', 'O', 'A', 'K', 'F', 'C0', 'C1', 'C2', 'C3', 'P', 'Q']);
			if (!this.smooth) {
				return false;
			}
			gl.useProgram(this.smooth.prog);
		}
		this.dirty = true;
		return true;
	};

	/** The cell texture and its framebuffer (pixel style). */
	Shader.prototype.cellTarget = function () {
		var gl = this.gl;
		var tex = gl.createTexture();
		gl.bindTexture(gl.TEXTURE_2D, tex);
		gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.LINEAR);
		gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.LINEAR);
		gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE);
		gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE);
		gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, 1, 1, 0, gl.RGBA, gl.UNSIGNED_BYTE, null);
		var fbo = gl.createFramebuffer();
		gl.bindFramebuffer(gl.FRAMEBUFFER, fbo);
		gl.framebufferTexture2D(gl.FRAMEBUFFER, gl.COLOR_ATTACHMENT0, gl.TEXTURE_2D, tex, 0);
		var ok = gl.checkFramebufferStatus(gl.FRAMEBUFFER) === gl.FRAMEBUFFER_COMPLETE;
		gl.bindFramebuffer(gl.FRAMEBUFFER, null);
		this.tex = tex;
		this.fbo = fbo;
		return ok;
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

	/** The pixel size in CSS px for the current width (8–64). */
	Shader.prototype.cellSize = function (width) {
		var cell = this.opts.cell;
		var size = typeof cell === 'function' ? cell(width) : cell;
		size = +size;
		if (!size || size !== size) {
			size = width <= 767 ? 12 : (width <= 1024 ? 16 : 18);
		}
		return Math.max(8, Math.min(64, size));
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
		var pixel = this.style === 'pixel';
		// Pixel squares need the screen's density for crisp edges (capped at
		// 1.5x); the smooth flow is soft anyway and renders at half that.
		var scale = Math.min(window.devicePixelRatio || 1, 1.5) * (pixel ? 1 : 0.5) * this.quality;
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

		if (pixel) {
			// A grid centred on the section, one texel per cell.
			var size = this.cellSize(w);
			var cols = Math.ceil(w / size) + 1;
			var rows = Math.ceil(h / size) + 1;
			this.grid = [(w - cols * size) / 2, 0, size];
			if (cols !== this.cols || rows !== this.rows) {
				var gl = this.gl;
				gl.bindTexture(gl.TEXTURE_2D, this.tex);
				gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, cols, rows, 0, gl.RGBA, gl.UNSIGNED_BYTE, null);
				this.cols = cols;
				this.rows = rows;
			}
		}

		var colors = (this.opts.colors && this.opts.colors()) || {};
		var base = parseColor(canvas, colors.base, [0.043, 0.043, 0.047]);
		var accent = parseColor(canvas, colors.accent, [0.984, 0.376, 0.027]);
		this.pal = shaderPalette(base, accent, pixel);

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
		if (!gl || this.lost || !this.pal) {
			return;
		}
		if (this.style === 'pixel') {
			this.drawPixel();
			return;
		}
		var p = this.smooth;
		if (!p) {
			return;
		}
		var u = p.u;
		var o = this.opts;
		var c = this.pal.c;
		gl.useProgram(p.prog);
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

	/** Pass 1 fills the cell texture, pass 2 draws the squares. */
	Shader.prototype.drawPixel = function () {
		var gl = this.gl;
		var f = this.field;
		var m = this.mosaic;
		if (!f || !m || !this.cols) {
			return;
		}
		var o = this.opts;
		var c = this.pal.c;
		var light = this.pal.light;
		var dirty = this.dirty;
		var radius = Math.max(150, Math.min(260, this.w * 0.18));
		var offset = this.animate ? (window.pageYOffset || 0) / 2400 : 0;
		var grid = this.grid;

		gl.bindFramebuffer(gl.FRAMEBUFFER, this.fbo);
		gl.viewport(0, 0, this.cols, this.rows);
		gl.useProgram(f.prog);
		if (dirty) {
			gl.uniform2f(f.u.G, this.cols, this.rows);
			gl.uniform3f(f.u.Z, grid[0], grid[1], grid[2]);
			gl.uniform3f(f.u.F, this.focus[0], this.focus[1], this.focus[2]);
			gl.uniform1f(f.u.A, (o.intensity >= 0 ? o.intensity : 1) * (light ? 1.1 : 1));
			gl.uniform4fv(f.u.Q, this.calm);
		}
		gl.uniform1f(f.u.T, this.time);
		gl.uniform1f(f.u.O, offset);
		gl.uniform4f(f.u.P, this.ptr.x, this.ptr.y, this.ptr.s, radius * 1.6);
		gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);

		gl.bindFramebuffer(gl.FRAMEBUFFER, null);
		gl.viewport(0, 0, this.canvas.width, this.canvas.height);
		gl.useProgram(m.prog);
		gl.activeTexture(gl.TEXTURE0);
		gl.bindTexture(gl.TEXTURE_2D, this.tex);
		if (dirty) {
			var a = this.pal.accent;
			gl.uniform1i(m.u.X, 0);
			gl.uniform2f(m.u.R, this.canvas.width, this.canvas.height);
			gl.uniform2f(m.u.G, this.cols, this.rows);
			gl.uniform3f(m.u.Z, grid[0], grid[1], grid[2]);
			gl.uniform3f(m.u.F, this.focus[0], this.focus[1], this.focus[2]);
			gl.uniform3f(m.u.C0, c[0][0], c[0][1], c[0][2]);
			gl.uniform3f(m.u.C1, c[1][0], c[1][1], c[1][2]);
			gl.uniform3f(m.u.C2, c[2][0], c[2][1], c[2][2]);
			gl.uniform3f(m.u.C3, c[3][0], c[3][1], c[3][2]);
			gl.uniform3f(m.u.AC, a[0], a[1], a[2]);
			gl.uniform1f(m.u.S, this.css);
			gl.uniform1f(m.u.K, light ? 0.3 : 0.2);
			gl.uniform1f(m.u.HZ, light ? 0.9 : 0.6);
			// On paper even the smallest squares carry a clear tint.
			gl.uniform1f(m.u.SQ, light ? 0.26 : 0.04);
			// Sparse: about one square in 160 per cycle lights up (a number
			// scales that: 0.4 = fewer).
			gl.uniform1f(m.u.SP, o.sparkles === false ? 0 : 0.0065 * (typeof o.sparkles === 'number' ? Math.max(0, o.sparkles) : 1));
			// The hairline grid: white on dark, ink on paper.
			if (light) {
				gl.uniform4f(m.u.L, 0.1, 0.1, 0.1, 0.05);
			} else {
				gl.uniform4f(m.u.L, 1, 1, 1, 0.045);
			}
			this.dirty = false;
		}
		gl.uniform1f(m.u.T, this.time);
		gl.uniform1f(m.u.W, this.wall);
		gl.uniform1f(m.u.B, this.build_t);
		gl.uniform4f(m.u.P, this.ptr.x, this.ptr.y, this.ptr.s, radius);
		gl.uniform4fv(m.u.RP, this.ripples);
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
		// Wrapped, so the sparkle hashes keep their precision on long visits.
		this.wall = (this.wall + dt) % 3600;
		if (this.build_t < 99) {
			this.build_t = Math.min(99, this.build_t + dt);
		}

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
		this.speed *= Math.pow(0.9, dt * 60);

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
		var x = event.clientX - rect.left;
		var y = event.clientY - rect.top;
		var t = now();
		// A quick flick of the pointer sends a ripple of squares outwards
		// (at most one every 280ms, four at a time).
		if (this.style === 'pixel' && this.moved) {
			var gap = t - this.moved.t;
			if (gap > 0 && gap < 120) {
				var v = Math.sqrt((x - this.moved.x) * (x - this.moved.x) + (y - this.moved.y) * (y - this.moved.y)) / gap;
				this.speed = this.speed * 0.6 + v * 0.4;
				if (this.speed > 1 && t - this.rippleAt > 280 && y < this.h) {
					var i = this.rippleNext;
					this.ripples[i * 4] = x;
					this.ripples[i * 4 + 1] = y;
					this.ripples[i * 4 + 2] = this.wall;
					this.ripples[i * 4 + 3] = Math.min(1, 0.5 + (this.speed - 1) * 0.25);
					this.rippleNext = (i + 1) % RIPPLES;
					this.rippleAt = t;
				}
			}
		}
		this.moved = { x: x, y: y, t: t };
		this.ptr.tx = x;
		this.ptr.ty = y;
		this.ptr.ts = 1;
		this.ptr.inside = true;
	};

	Shader.prototype.onLeave = function () {
		this.ptr.inside = false;
		this.moved = null;
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
