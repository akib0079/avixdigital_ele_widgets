/*!
 * Avix Digital · Pixel Reveal (site-wide)
 * The case-study pixel resolve for every content image: an image arrives
 * behind a layer of 16px squares in the colour of the section it sits on,
 * and the squares clear in a random order, once. One tiny canvas per image
 * (one canvas pixel per square, scaled up without smoothing), all driven by
 * one IntersectionObserver and one rAF loop, and removed when it has
 * cleared.
 *
 * The canvas is an absolutely positioned sibling of the image, placed over
 * the image's visible box by measuring (so it follows transformed, sticky
 * and scrolling containers, and is clipped like the image). Insertions that
 * would move anything (a sibling selector, :last-child) are undone before
 * the next frame and that image is left alone.
 *
 * Most widgets bring their cards and rows in with their own fade. The canvas
 * sits inside that card, so it fades in with it as a solid placeholder, and
 * the squares only start clearing once the card is (nearly) fully shown.
 *
 * Never hides an image without JS, never touches images already on screen
 * when it runs, and does nothing with reduced motion or in the editor. The
 * two CSS rules it needs (hidden for reduced motion and print) are added by
 * the script itself, so the page loads no stylesheet for it. Loaded async;
 * the first look waits until the page has loaded and the browser is idle,
 * and images that come near together are armed in one read pass and one
 * write pass (no layout per image).
 * Config: window.avixPixelRevealConfig (printed by includes/pixel-reveal.php).
 * API: window.AvixPixelReveal.{ scan(root), reveal(img), destroy() }: call
 * scan(container) after swapping content in (AJAX filters, "Load more"): it
 * forgets images that left the page and picks up the new ones.
 */
(function (window, document) {
	'use strict';

	if (window.AvixPixelReveal) {
		return;
	}

	var cfg = window.avixPixelRevealConfig || {};

	function num(value, fallback, min, max) {
		var n = parseFloat(value);
		if (!isFinite(n)) {
			n = fallback;
		}
		return Math.min(max, Math.max(min, n));
	}

	var SQUARE = num(cfg.square, 16, 4, 64); // CSS px per square
	var SPREAD = num(cfg.spread, 420, 0, 3000); // ms: the last square starts clearing by then
	var FADE = num(cfg.fade, 220, 16, 3000); // ms: each square's fade
	var WAIT_IMG = num(cfg.wait, 2500, 0, 10000); // ms: longest wait for the image to load
	var SETTLE = num(cfg.settle, 1200, 0, 10000); // ms: partly visible but never 25% → reveal anyway
	var MAX_ACTIVE = Math.round(num(cfg.maxActive, 6, 1, 50)); // more at once reveal instantly
	var MIN_W = num(cfg.minWidth, 140, 0, 5000);
	var MIN_H = num(cfg.minHeight, 100, 0, 5000);
	var MARGIN = typeof cfg.margin === 'string' && /^\d{1,3}(px|%)$/.test(cfg.margin) ? cfg.margin : '50%';
	// Waiting for the card or row around the image to finish its own entrance:
	var SHOWN = 0.8; // start once the image shows at this opacity through its ancestors
	var FAINT = 0.3; // after HOLD, still below this: keep waiting (nothing to see yet)
	var HOLD = num(cfg.hold, 2000, 0, 10000); // ms: longest look-again burst (twice that while a fade is running)
	var HOLD_POLL = 100; // ms between looks while waiting
	var DEFAULT_SCOPE = 'main, .elementor, .page_content_wrap, .post_content, .content';
	// Site header / footer landmarks: a <header> or <footer> inside content
	// (a card, an article) does not count.
	var CHROME = 'header, footer, [role="banner"], [role="contentinfo"]';
	var CONTENT = 'main, article, [role="main"], .elementor-widget, .elementor-element';
	var OVERLAY = 'elementor-background-overlay';
	var MEDIA_LAYERS = /(^|\s)(elementor-background-video-container|elementor-background-slideshow|elementor-motion-effects-container)(\s|$)/;

	var api = {
		scan: function () {},
		reveal: function () {},
		destroy: function () {}
	};
	window.AvixPixelReveal = api;

	/* ---------- Support ---------- */

	var mq = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };
	var supportsCanvas = (function () {
		try {
			var c = document.createElement('canvas');
			return !!(c.getContext && c.getContext('2d'));
		} catch (error) {
			return false;
		}
	})();

	function isEditMode() {
		if (/[?&]elementor-preview=/.test(window.location.search || '')) {
			return true;
		}
		if (document.body && document.body.classList.contains('elementor-editor-active')) {
			return true;
		}
		var ef = window.elementorFrontend;
		try {
			return !!(ef && typeof ef.isEditMode === 'function' && ef.isEditMode());
		} catch (error) {
			return false;
		}
	}

	function canAnimate() {
		return supportsCanvas && 'IntersectionObserver' in window && !!Element.prototype.closest && !mq.matches && !isEditMode();
	}

	var raf = window.requestAnimationFrame
		? function (fn) {
			return window.requestAnimationFrame(fn);
		}
		: function (fn) {
			return window.setTimeout(fn, 16);
		};
	var now = function () {
		return window.performance && window.performance.now ? window.performance.now() : Date.now();
	};

	function toArray(list) {
		return Array.prototype.slice.call(list || []);
	}

	function remove(list, item) {
		var i = list.indexOf(item);
		if (i > -1) {
			list.splice(i, 1);
		}
	}

	function connected(el) {
		return !!el && document.documentElement.contains(el);
	}

	/**
	 * True when the element is in the middle of a one-off opacity animation
	 * or transition (a card fading in). Endless loops are not entrances.
	 */
	function fading(el) {
		if (typeof el.getAnimations !== 'function') {
			return false;
		}
		var list;
		try {
			list = el.getAnimations();
		} catch (error) {
			return false;
		}
		for (var i = 0; i < list.length; i++) {
			var anim = list[i];
			var effect = anim.effect;
			if (anim.playState !== 'running' || !effect || typeof effect.getComputedTiming !== 'function') {
				continue;
			}
			if (!isFinite(effect.getComputedTiming().endTime)) {
				continue;
			}
			if (typeof anim.transitionProperty === 'string') {
				if (anim.transitionProperty === 'opacity') {
					return true;
				}
				continue;
			}
			if (typeof effect.getKeyframes === 'function') {
				var frames = effect.getKeyframes();
				for (var k = 0; k < frames.length; k++) {
					if (frames[k] && frames[k].opacity !== undefined) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/** Keeps only the selectors the browser accepts, joined into one list. */
	function compile(list) {
		var probe = document.createDocumentFragment();
		var out = [];
		(Array.isArray(list) ? list : (typeof list === 'string' ? [list] : [])).forEach(function (sel) {
			sel = typeof sel === 'string' ? sel.trim() : '';
			if (!sel) {
				return;
			}
			try {
				probe.querySelector(sel);
				out.push(sel);
			} catch (error) {
				// Invalid selector: skip it.
			}
		});
		return out.join(', ');
	}

	var SCOPE = compile(cfg.scope) || DEFAULT_SCOPE;
	var EXCLUDE = compile(cfg.exclude);
	// Class-name hints, matched per class token (BEM modifiers such as
	// "avix-work--logos-hover" and the classes of <body>/<html>, e.g.
	// "wp-custom-logo", say nothing about one image): logos, avatars and
	// icons on the image or its three nearest ancestors, marquees and tickers
	// at any depth (they move continuously).
	var HINT_NEAR = /logo|avatar|icon/i;
	var HINT_ANY = /marquee|ticker/i;
	// Elementor's lazy-loaded container backgrounds: its inline script adds
	// .e-lazyloaded about 200px before a container shows, and until then
	// background images inside are switched off, so the colour behind an image
	// is provisional and is read again once the class is there. Only when that
	// script is on the page (a global const, so typeof is the safe way to look).
	// Looked up at the first arming, not when this file runs: loaded async, it
	// may run before Elementor's footer script has been parsed.
	var LAZY_BG = null;

	function lazyBg() {
		if (null === LAZY_BG) {
			try {
				/* global lazyloadRunObserver */
				if (typeof lazyloadRunObserver === 'function') {
					LAZY_BG = compile(['.e-con.e-parent:not(.e-lazyloaded):not(.e-no-lazyload)']);
				} else if (document.readyState === 'complete') {
					LAZY_BG = ''; // the page has loaded without it: stop looking
				}
			} catch (error) {
				LAZY_BG = '';
			}
		}
		return LAZY_BG || '';
	}

	/* ---------- Colour ---------- */

	function parseColor(value) {
		if (!value) {
			return null;
		}
		var m = value.match(/rgba?\(\s*([\d.]+)[,\s]+([\d.]+)[,\s]+([\d.]+)(?:[,\s/]+([\d.]+%?))?/);
		if (m) {
			var a = m[4] === undefined ? 1 : (m[4].indexOf('%') > -1 ? parseFloat(m[4]) / 100 : parseFloat(m[4]));
			return [+m[1], +m[2], +m[3], a];
		}
		m = value.match(/color\(\s*srgb\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)(?:\s*\/\s*([\d.]+%?))?/);
		if (m) {
			var alpha = m[4] === undefined ? 1 : (m[4].indexOf('%') > -1 ? parseFloat(m[4]) / 100 : parseFloat(m[4]));
			return [m[1] * 255, m[2] * 255, m[3] * 255, alpha];
		}
		return null;
	}

	/** The average of a gradient's colour stops, or null. */
	function gradientColor(value) {
		var stops = value.match(/rgba?\([^)]*\)|color\(srgb[^)]*\)/g);
		if (!stops || !stops.length) {
			return null;
		}
		var sum = [0, 0, 0, 0];
		var count = 0;
		stops.forEach(function (stop) {
			var c = parseColor(stop);
			if (c) {
				sum[0] += c[0] * c[3];
				sum[1] += c[1] * c[3];
				sum[2] += c[2] * c[3];
				sum[3] += c[3];
				count++;
			}
		});
		if (!count || sum[3] <= 0) {
			return null;
		}
		return [sum[0] / sum[3], sum[1] / sum[3], sum[2] / sum[3], sum[3] / count];
	}

	/**
	 * The colour the image sits on, as [r, g, b]: the nearest opaque
	 * background behind it (semi-transparent layers composited on top), or
	 * --avix-pxr-cover when a section sets it. Null when a photo or video is
	 * behind the image (no colour would blend in, so it is left alone).
	 */
	function coverColor(start, probe) {
		var raw = '';
		try {
			raw = (window.getComputedStyle(start).getPropertyValue('--avix-pxr-cover') || '').trim();
		} catch (error) {
			raw = '';
		}
		if (raw) {
			probe.style.color = '';
			probe.style.color = raw;
			var forced = parseColor(window.getComputedStyle(probe).color);
			probe.style.color = '';
			if (forced) {
				return [forced[0], forced[1], forced[2]];
			}
		}

		var layers = []; // top-most first
		var el = start;
		var opaque = false;
		var depth = 0;
		while (el && el.nodeType === 1 && depth++ < 60) {
			var top = el !== document.body && el !== document.documentElement;
			// Elementor paints a section's overlay, video, slideshow and
			// parallax image in child layers behind the content.
			if (top && el.children) {
				for (var k = 0; k < el.children.length; k++) {
					var child = el.children[k];
					var cls = typeof child.className === 'string' ? child.className : '';
					if (!cls) {
						continue;
					}
					if (MEDIA_LAYERS.test(cls)) {
						return null;
					}
					if ((' ' + cls + ' ').indexOf(' ' + OVERLAY + ' ') > -1) {
						var ocs = window.getComputedStyle(child);
						if (ocs.backgroundImage && ocs.backgroundImage !== 'none' && /url\(/.test(ocs.backgroundImage)) {
							return null;
						}
						var oc = parseColor(ocs.backgroundColor);
						var oo = parseFloat(ocs.opacity);
						if (oc && oc[3] > 0 && ocs.display !== 'none') {
							layers.push([oc[0], oc[1], oc[2], oc[3] * (isFinite(oo) ? oo : 1)]);
						}
					}
				}
			}
			var cs = window.getComputedStyle(el);
			var image = cs.backgroundImage;
			if (top && image && image !== 'none') {
				if (/url\(/.test(image)) {
					return null;
				}
				var g = gradientColor(image);
				if (g && g[3] > 0) {
					layers.push(g);
				}
			}
			var c = parseColor(cs.backgroundColor);
			if (c && c[3] > 0) {
				layers.push(c);
				if (c[3] >= 0.995) {
					opaque = true;
					break;
				}
			}
			el = el.parentElement;
		}
		var base = opaque ? null : [255, 255, 255];
		for (var i = layers.length - 1; i >= 0; i--) {
			var layer = layers[i];
			if (!base) {
				base = [layer[0], layer[1], layer[2]];
				continue;
			}
			var a = Math.min(1, Math.max(0, layer[3]));
			base = [
				layer[0] * a + base[0] * (1 - a),
				layer[1] * a + base[1] * (1 - a),
				layer[2] * a + base[2] * (1 - a)
			];
		}
		base = base || [255, 255, 255];
		return [Math.round(base[0]), Math.round(base[1]), Math.round(base[2])];
	}

	/* ---------- Which images ---------- */

	function hostOf(img) {
		var parent = img.parentElement;
		return parent && parent.tagName === 'PICTURE' ? parent : img;
	}

	function excluded(img) {
		if (EXCLUDE && img.closest(EXCLUDE)) {
			return true;
		}
		var el = img;
		for (var depth = 0; el && el !== document.body && el !== document.documentElement; depth++) {
			var cls = el.getAttribute('class');
			if (cls) {
				var tokens = cls.split(/\s+/);
				for (var i = 0; i < tokens.length; i++) {
					var token = tokens[i];
					if (token && token.indexOf('--') === -1 && (HINT_ANY.test(token) || (depth <= 3 && HINT_NEAR.test(token)))) {
						return true;
					}
				}
			}
			el = el.parentElement;
		}
		var chrome = img.closest(CHROME);
		if (chrome && !(chrome.parentElement && chrome.parentElement.closest(CONTENT))) {
			return true;
		}
		// Lazy loaders keep the real file in data-*; their placeholder is often an SVG.
		var src = img.getAttribute('data-lazy-src') || img.getAttribute('data-src') || img.currentSrc || img.getAttribute('src') || '';
		return /\.svg(?:[?#]|$)/i.test(src) || (/^data:image\/svg/i.test(src) && !img.getAttribute('data-srcset') && !img.getAttribute('data-lazy-srcset'));
	}

	function loaded(img) {
		return img.complete && img.naturalWidth > 0;
	}

	function inViewport(rect) {
		var vh = window.innerHeight || document.documentElement.clientHeight;
		var vw = window.innerWidth || document.documentElement.clientWidth;
		return rect.bottom > 0 && rect.right > 0 && rect.top < vh && rect.left < vw && rect.width > 0 && rect.height > 0;
	}

	function radiusOf(cs) {
		var corners = [cs.borderTopLeftRadius, cs.borderTopRightRadius, cs.borderBottomRightRadius, cs.borderBottomLeftRadius];
		for (var i = 0; i < 4; i++) {
			if (corners[i] && parseFloat(corners[i]) > 0) {
				return corners;
			}
		}
		return null;
	}

	/* ---------- State ---------- */

	var observer = null; // arm margin: puts the canvas on before the image is reached
	var viewer = null; // the viewport itself: in view, 25% in view
	var resizer = null; // the page changing height (an accordion, a late font)
	var all = [];
	var armed = []; // canvas on, waiting to be scrolled to
	var active = []; // started: waiting for the image or clearing
	var playing = [];
	var looping = false;
	var needCheck = false;
	var DONE = { state: 'done' };

	var BASE_STYLE = {
		position: 'absolute',
		left: '0px',
		top: '0px',
		right: 'auto',
		bottom: 'auto',
		width: '100px',
		height: '100px',
		'min-width': '0',
		'min-height': '0',
		'max-width': 'none',
		'max-height': 'none',
		margin: '0',
		padding: '0',
		border: '0',
		float: 'none',
		opacity: '1',
		visibility: 'inherit',
		transform: 'none',
		filter: 'none',
		background: 'transparent',
		'box-shadow': 'none',
		'pointer-events': 'none',
		'image-rendering': 'pixelated'
	};

	function css(el, prop, value) {
		el.style.setProperty(prop, value, 'important');
	}

	function Reveal(img) {
		this.img = img;
		this.host = hostOf(img);
		this.state = 'idle';
		this.timers = [];
		this.canvas = null;
		this.inView = false;
		this.holdSince = 0;
		this.dormant = false;
	}

	/** A timeout that is cleared with the instance (and forgotten once it ran). */
	Reveal.prototype.later = function (fn, ms) {
		var self = this;
		var id = window.setTimeout(function () {
			remove(self.timers, id);
			fn();
		}, ms);
		this.timers.push(id);
		return id;
	};

	/** The image's visible box in viewport px: its rect cut by clipping ancestors. */
	Reveal.prototype.box = function () {
		var r = this.img.getBoundingClientRect();
		var box = { left: r.left, top: r.top, right: r.right, bottom: r.bottom, full: r };
		for (var i = 0; i < this.clips.length; i++) {
			var c = this.clips[i].getBoundingClientRect();
			box.left = Math.max(box.left, c.left);
			box.top = Math.max(box.top, c.top);
			box.right = Math.min(box.right, c.right);
			box.bottom = Math.min(box.bottom, c.bottom);
		}
		box.width = Math.max(0, box.right - box.left);
		box.height = Math.max(0, box.bottom - box.top);
		return box;
	};

	/** Reads the geometry (no writes): call for every canvas, then place(). */
	Reveal.prototype.measure = function () {
		if (!this.canvas) {
			return null;
		}
		return { box: this.box(), canvas: this.canvas.getBoundingClientRect() };
	};

	/**
	 * Moves the canvas over the measured box (in its own layout px). The first
	 * placement (before it is ever painted) sets left/top; later corrections
	 * (the image moving by its own transform, a parallax, a re-layout) use a
	 * translate, so they never count as a layout shift.
	 */
	Reveal.prototype.place = function (geo) {
		if (!geo || !this.canvas) {
			return;
		}
		var cr = geo.canvas;
		var sx = cr.width > 0 && this.w > 0 ? cr.width / this.w : 1;
		var sy = cr.height > 0 && this.h > 0 ? cr.height / this.h : 1;
		var x = this.x + (geo.box.left - cr.left) / sx;
		var y = this.y + (geo.box.top - cr.top) / sy;
		var w = Math.max(0, geo.box.width / sx);
		var h = Math.max(0, geo.box.height / sy);
		if (Math.abs(w - this.w) > 0.2 || Math.abs(h - this.h) > 0.2) {
			this.w = w;
			this.h = h;
			css(this.canvas, 'width', w.toFixed(2) + 'px');
			css(this.canvas, 'height', h.toFixed(2) + 'px');
			if (this.ctx && (this.state === 'armed' || this.state === 'starting')) {
				// Resized while still solid (a rotated phone, a narrower
				// window): new columns and rows, so the squares stay square.
				this.grid();
			}
		}
		if (Math.abs(x - this.x) > 0.2 || Math.abs(y - this.y) > 0.2) {
			this.x = x;
			this.y = y;
			if (!this.placed) {
				css(this.canvas, 'left', x.toFixed(2) + 'px');
				css(this.canvas, 'top', y.toFixed(2) + 'px');
			} else {
				css(this.canvas, 'transform', 'translate(' + (x - this.left).toFixed(2) + 'px, ' + (y - this.top).toFixed(2) + 'px)');
			}
		}
		if (!this.placed) {
			this.placed = true;
			this.left = this.x;
			this.top = this.y;
		}
	};

	function snapshot(host) {
		var parent = host.parentElement;
		var next = host.nextElementSibling;
		if (next && next.className === 'avix-pxr' && next.tagName === 'CANVAS') {
			next = next.nextElementSibling;
		}
		var list = [host.getBoundingClientRect(), parent ? parent.getBoundingClientRect() : null, next ? next.getBoundingClientRect() : null];
		return list.map(function (r) {
			return r ? [r.left, r.top, r.width, r.height] : null;
		});
	}

	function same(a, b) {
		for (var i = 0; i < a.length; i++) {
			if (!a[i] || !b[i]) {
				if (a[i] !== b[i]) {
					return false;
				}
				continue;
			}
			for (var k = 0; k < 4; k++) {
				if (Math.abs(a[i][k] - b[i][k]) > 0.5) {
					return false;
				}
			}
		}
		return true;
	}

	/** Called when the image comes within the arm margin of the viewport. */
	Reveal.prototype.near = function () {
		armAll([this]);
	};

	/**
	 * Arming, step 1 (reads only): whether this image gets a cover, and the
	 * geometry and styles the cover needs. Null when it is left alone.
	 */
	Reveal.prototype.prepare = function () {
		var self = this;
		var img = this.img;
		if (this.state !== 'idle') {
			return null;
		}
		if (!connected(img) || mq.matches) {
			this.done(mq.matches ? 'motion' : 'gone');
			return null;
		}
		var rect = img.getBoundingClientRect();
		if (inViewport(rect)) {
			// Already showing (at load, or reached in one jump): never cover it.
			this.done('in-view');
			return null;
		}
		if (rect.width < MIN_W || rect.height < MIN_H) {
			if (loaded(img)) {
				this.done('small');
			} else if (!this.waitSize) {
				// A lazy image without reserved space may grow when it loads:
				// look again then (re-observing replays the intersection).
				this.waitSize = function () {
					img.removeEventListener('load', self.waitSize);
					img.removeEventListener('error', self.waitSize);
					self.waitSize = null;
					if (self.state === 'idle' && observer) {
						observer.unobserve(img);
						observer.observe(img);
					}
				};
				img.addEventListener('load', this.waitSize);
				img.addEventListener('error', this.waitSize);
			}
			return null;
		}

		var host = this.host;
		var parent = host.parentElement;
		if (!parent) {
			this.done('no-parent');
			return null;
		}
		var ics = window.getComputedStyle(img);
		// Elementor's entrance animations hide the element until it is
		// reached: the canvas waits inside it and clears once it has shown.
		var entrance = ics.visibility !== 'visible' && !!img.closest('.elementor-invisible');
		if ((ics.visibility !== 'visible' && !entrance) || parseFloat(ics.opacity) < 0.5 || ics.display === 'none') {
			// Hidden by its own widget (a crossfade, its own reveal): leave it.
			this.done('hidden');
			return null;
		}

		// Clipping ancestors: an absolutely positioned canvas can escape the
		// ones below its containing block, so it is cut to them by hand.
		var clips = [];
		var el = parent;
		var depth = 0;
		while (el && el !== document.body && el !== document.documentElement && depth++ < 40) {
			var cs = window.getComputedStyle(el);
			if (cs.overflowX !== 'visible' || cs.overflowY !== 'visible') {
				clips.push(el);
			}
			el = el.parentElement;
		}
		this.clips = clips;

		var box = this.box();
		if (box.width * box.height < 0.5 * rect.width * rect.height || box.width < MIN_W * 0.5 || box.height < MIN_H * 0.5) {
			// Mostly clipped away (a scroller, a crop): not worth a reveal.
			this.done('clipped');
			return null;
		}

		// Rounded corners: the image's own, or a clipping frame's when the
		// visible box is that frame.
		var radius = null;
		for (var i = 0; i < clips.length && !radius; i++) {
			var cr = clips[i].getBoundingClientRect();
			if (Math.abs(cr.left - box.left) < 1.5 && Math.abs(cr.top - box.top) < 1.5 && Math.abs(cr.right - box.right) < 1.5 && Math.abs(cr.bottom - box.bottom) < 1.5) {
				radius = radiusOf(window.getComputedStyle(clips[i]));
			}
		}
		if (!radius && Math.abs(box.width - rect.width) < 1.5 && Math.abs(box.height - rect.height) < 1.5) {
			radius = radiusOf(ics);
		}

		return {
			parent: parent,
			radius: radius,
			z: ics.position !== 'static' && /^-?\d+$/.test(ics.zIndex) ? ics.zIndex : 'auto',
			before: snapshot(host)
		};
	};

	/** Arming, step 2 (writes only): the cover goes in, not placed yet. */
	Reveal.prototype.insert = function (plan) {
		var canvas = document.createElement('canvas');
		canvas.className = 'avix-pxr';
		canvas.setAttribute('aria-hidden', 'true');
		Object.keys(BASE_STYLE).forEach(function (prop) {
			css(canvas, prop, BASE_STYLE[prop]);
		});
		css(canvas, 'image-rendering', 'crisp-edges');
		css(canvas, 'image-rendering', 'pixelated');
		css(canvas, 'z-index', plan.z);
		if (plan.radius) {
			css(canvas, 'border-top-left-radius', plan.radius[0]);
			css(canvas, 'border-top-right-radius', plan.radius[1]);
			css(canvas, 'border-bottom-right-radius', plan.radius[2]);
			css(canvas, 'border-bottom-left-radius', plan.radius[3]);
		}
		this.x = 0;
		this.y = 0;
		this.w = 100;
		this.h = 100;
		this.placed = false;
		this.canvas = canvas;
		plan.parent.insertBefore(canvas, this.host.nextSibling);
	};

	/**
	 * Arming, step 5 (reads): the insertion must not have moved anything (a
	 * sibling selector, :last-child…) and a colour must blend in. Returns the
	 * cover colour, or null to undo it.
	 */
	Reveal.prototype.verify = function (plan) {
		plan.moved = !same(plan.before, snapshot(this.host)) || this.w < 1 || this.h < 1;
		return plan.moved ? null : coverColor(plan.parent, this.canvas);
	};

	/** Arming, step 6 (writes): the squares are drawn, or the cover is undone before it is painted. */
	Reveal.prototype.commit = function (plan, color) {
		if (!color) {
			this.removeCanvas();
			this.done(plan.moved ? 'shift' : 'cover');
			return;
		}
		var ctx = this.canvas.getContext('2d');
		if (!ctx) {
			this.removeCanvas();
			this.done('no-ctx');
			return;
		}
		var bg = lazyBg();
		this.ctx = ctx;
		this.color = color;
		this.cols = 0;
		this.rows = 0;
		this.grid();
		this.lazy = bg ? this.img.closest(bg) : null;
		this.state = 'armed';
		this.settled = false;
		this.inView = false;
		this.holdSince = 0;
		this.dormant = false;
		armed.push(this);
		if (viewer) {
			// Reports reaching the viewport and 25% of it however the image
			// gets there: a scroll, or content above it collapsing.
			viewer.observe(this.img);
		}
	};

	/**
	 * Arms every image that came near in one go, reads and writes in
	 * separate passes, so a batch costs two layouts instead of two per image
	 * (the first look after the page has loaded can cover a dozen at once).
	 */
	function armAll(list) {
		var jobs = [];
		var i;
		for (i = 0; i < list.length; i++) {
			var plan = list[i].prepare();
			if (plan) {
				jobs.push([list[i], plan]);
			}
		}
		if (!jobs.length) {
			return;
		}
		for (i = 0; i < jobs.length; i++) {
			jobs[i][0].insert(jobs[i][1]);
		}
		var geos = jobs.map(function (job) {
			return job[0].measure();
		});
		for (i = 0; i < jobs.length; i++) {
			jobs[i][0].place(geos[i]);
		}
		var colors = jobs.map(function (job) {
			return job[0].verify(job[1]);
		});
		for (i = 0; i < jobs.length; i++) {
			jobs[i][0].commit(jobs[i][1], colors[i]);
		}
		requestCheck();
	}

	/**
	 * One canvas pixel per square of the current size, each with its own
	 * start time, all in the cover colour. Only while nothing has cleared.
	 */
	Reveal.prototype.grid = function () {
		var cols = Math.max(1, Math.ceil(this.w / SQUARE));
		var rows = Math.max(1, Math.ceil(this.h / SQUARE));
		if (cols === this.cols && rows === this.rows && this.image) {
			return;
		}
		this.cols = cols;
		this.rows = rows;
		this.canvas.width = cols;
		this.canvas.height = rows;
		var delays = new Float32Array(cols * rows);
		for (var n = 0; n < cols * rows; n++) {
			var x = n % cols;
			var y = Math.floor(n / cols);
			// Mostly random, with a light top-left → bottom-right drift so the
			// image resolves into place rather than flickering.
			var drift = (x / cols + y / rows) / 2;
			delays[n] = (Math.random() * 0.78 + drift * 0.22) * SPREAD;
		}
		this.image = this.ctx.createImageData(cols, rows);
		this.delays = delays;
		this.count = cols * rows;
		this.paint(this.color);
	};

	/** Fills every square with the cover colour. */
	Reveal.prototype.paint = function (color) {
		this.color = color;
		var data = this.image.data;
		for (var n = 0; n < this.count; n++) {
			data[n * 4] = color[0];
			data[n * 4 + 1] = color[1];
			data[n * 4 + 2] = color[2];
			data[n * 4 + 3] = 255;
		}
		this.ctx.putImageData(this.image, 0, 0);
	};

	/**
	 * Elementor has now painted the section's lazy background: read the
	 * colour again (false when a photo turned out to be behind the image).
	 */
	Reveal.prototype.recolor = function () {
		if (!this.lazy || !this.canvas || !this.lazy.classList.contains('e-lazyloaded')) {
			return true;
		}
		this.lazy = null;
		var color = coverColor(this.host.parentElement, this.canvas);
		if (!color) {
			return false;
		}
		this.paint(color);
		return true;
	};

	/** Out of the arm margin again before it was reached: drop the canvas. */
	Reveal.prototype.disarm = function () {
		if (this.state !== 'armed') {
			return;
		}
		this.clearTimers();
		this.removeCanvas();
		remove(armed, this);
		if (viewer) {
			viewer.unobserve(this.img);
		}
		this.inView = false;
		this.state = 'idle';
	};

	/** Check for an armed image: start at 25% visible (or 25% of the screen). */
	Reveal.prototype.check = function (geo) {
		var self = this;
		if (this.state !== 'armed' || !geo) {
			return;
		}
		if (!this.recolor()) {
			this.finish('cover');
			return;
		}
		var box = geo.box;
		var vh = window.innerHeight || document.documentElement.clientHeight;
		var vw = window.innerWidth || document.documentElement.clientWidth;
		var visH = Math.min(box.bottom, vh) - Math.max(box.top, 0);
		var visW = Math.min(box.right, vw) - Math.max(box.left, 0);
		if (visH <= 0 || visW <= 0 || !box.height) {
			this.holdSince = 0;
			return;
		}
		if (visH >= box.height * 0.25 || visH >= vh * 0.25 || this.settled) {
			var veil = this.veil();
			if (veil && this.hold(veil)) {
				return;
			}
			this.start();
			return;
		}
		this.holdSince = 0;
		if (!this.settleTimer) {
			this.settleTimer = this.later(function () {
				self.settleTimer = null;
				self.settled = true;
				requestCheck();
			}, SETTLE);
		}
	};

	/**
	 * How far the image is from being seen through its ancestors: null when
	 * it shows, else { opacity, moving }. The canvas sits in the same card or
	 * row, so it fades with them: clearing it while they are still (nearly)
	 * transparent would play the whole dissolve unseen.
	 */
	Reveal.prototype.veil = function () {
		var opacity = 1;
		var chain = [];
		var el = this.img;
		var cs = window.getComputedStyle(el);
		if (cs.visibility !== 'visible') {
			opacity = 0;
		}
		for (var depth = 0; el && el.nodeType === 1 && depth < 60; depth++) {
			if (depth) {
				cs = window.getComputedStyle(el);
			}
			var o = parseFloat(cs.opacity);
			if (isFinite(o)) {
				opacity *= Math.max(0, Math.min(1, o));
			}
			chain.push(el);
			el = el.parentElement;
		}
		if (opacity >= SHOWN) {
			return null;
		}
		var moving = false;
		for (var i = 0; i < chain.length && !moving; i++) {
			moving = fading(chain[i]);
		}
		return { opacity: opacity, moving: moving };
	};

	/**
	 * The image is in view but its card is still hidden or fading in (most
	 * widgets bring their items in with their own observer): keep the cover,
	 * a solid placeholder that fades in with the card, and look again
	 * shortly. False when it is time to clear it anyway.
	 */
	Reveal.prototype.hold = function (veil) {
		var self = this;
		var t = now();
		if (!this.holdSince) {
			this.holdSince = t;
		}
		this.dormant = false;
		if (t - this.holdSince >= (veil.moving ? HOLD * 2 : HOLD)) {
			if (veil.opacity >= FAINT) {
				// Stays half shown (a dimmed card): clear it as it is.
				return false;
			}
			// Still (all but) invisible: its widget's own trigger has not
			// fired yet. Stop looking until something happens: a scroll, a
			// layout change or an animation starting look again.
			this.holdSince = 0;
			this.dormant = true;
			return true;
		}
		if (!this.holdTimer) {
			this.holdTimer = this.later(function () {
				self.holdTimer = null;
				requestCheck();
			}, HOLD_POLL);
		}
		return true;
	};

	/** Waits (briefly) for the image itself, then clears the squares. */
	Reveal.prototype.start = function () {
		var self = this;
		var img = this.img;
		if (this.state !== 'armed') {
			return;
		}
		remove(armed, this);
		this.clearTimers();
		if (observer) {
			observer.unobserve(img);
		}
		if (viewer) {
			viewer.unobserve(img);
		}
		if (active.length >= MAX_ACTIVE) {
			this.finish();
			return;
		}
		this.state = 'starting';
		active.push(this);
		if (loaded(img)) {
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
		this.onImage = go;
		img.addEventListener('load', go);
		img.addEventListener('error', go);
		this.later(go, WAIT_IMG);
	};

	Reveal.prototype.play = function () {
		if (this.state !== 'starting') {
			return;
		}
		if (!this.canvas || !connected(this.img)) {
			this.finish();
			return;
		}
		this.state = 'playing';
		this.t0 = 0;
		playing.push(this);
		loop();
	};

	/** One frame of the dissolve; true when every square has cleared. */
	Reveal.prototype.step = function (time) {
		if (!this.t0) {
			this.t0 = time;
		}
		var t = time - this.t0;
		var data = this.image.data;
		var delays = this.delays;
		var busy = false;
		for (var i = 0; i < this.count; i++) {
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
		this.ctx.putImageData(this.image, 0, 0);
		return !busy;
	};

	Reveal.prototype.clearTimers = function () {
		this.timers.forEach(function (id) {
			window.clearTimeout(id);
		});
		this.timers = [];
		this.settleTimer = null;
		this.holdTimer = null;
		this.holdSince = 0;
		this.dormant = false;
	};

	Reveal.prototype.removeCanvas = function () {
		if (this.canvas && this.canvas.parentNode) {
			this.canvas.parentNode.removeChild(this.canvas);
		}
		this.canvas = null;
		this.ctx = null;
		this.image = null;
		this.delays = null;
	};

	/** Shows the image (removes the canvas) and forgets it. */
	Reveal.prototype.finish = function (why) {
		this.removeCanvas();
		this.done(why || 'shown');
	};

	Reveal.prototype.done = function (why) {
		this.img.__avixPxrWhy = why || 'done';
		this.clearTimers();
		if (this.onImage) {
			this.img.removeEventListener('load', this.onImage);
			this.img.removeEventListener('error', this.onImage);
			this.onImage = null;
		}
		if (this.waitSize) {
			this.img.removeEventListener('load', this.waitSize);
			this.img.removeEventListener('error', this.waitSize);
			this.waitSize = null;
		}
		if (this.canvas) {
			this.removeCanvas();
		}
		if (observer) {
			observer.unobserve(this.img);
		}
		if (viewer) {
			viewer.unobserve(this.img);
		}
		remove(armed, this);
		remove(active, this);
		remove(playing, this);
		remove(all, this);
		this.state = 'done';
		this.img.__avixPxr = DONE;
		if (!all.length) {
			// Nothing left to reveal: stop listening until scan() finds more.
			sleep();
		}
	};

	/* ---------- The one loop ---------- */

	function requestCheck() {
		if (!armed.length && !active.length) {
			return;
		}
		needCheck = true;
		loop();
	}

	function loop() {
		if (looping) {
			return;
		}
		looping = true;
		raf(frame);
	}

	function frame(stamp) {
		looping = false;
		var time = typeof stamp === 'number' && stamp > 1e3 ? stamp : now();
		var checking = needCheck;
		needCheck = false;

		// Every armed canvas is measured, not only those whose image is in
		// view: one in a sticky or transformed container can drift from its
		// image while waiting just outside the viewport, and would then show
		// before the image does. (Armed means within half a screen.)
		var tracked = playing.slice();
		if (checking) {
			armed.forEach(function (inst) {
				tracked.push(inst);
			});
			active.forEach(function (inst) {
				if (inst.state === 'starting') {
					tracked.push(inst);
				}
			});
		}

		// Gone from the page (re-rendered markup): forget them.
		var i;
		for (i = tracked.length - 1; i >= 0; i--) {
			if (!connected(tracked[i].img) || !tracked[i].canvas || !connected(tracked[i].canvas)) {
				tracked[i].finish('gone');
				tracked.splice(i, 1);
			}
		}

		// Read everything, then write everything.
		var geos = tracked.map(function (inst) {
			return inst.measure();
		});
		for (i = 0; i < tracked.length; i++) {
			tracked[i].place(geos[i]);
		}
		for (i = 0; i < tracked.length; i++) {
			var inst = tracked[i];
			if (inst.state === 'armed') {
				inst.check(geos[i]);
			}
		}
		playing.slice().forEach(function (inst) {
			if (inst.state === 'playing' && inst.step(time)) {
				inst.finish();
			}
		});

		if (playing.length || needCheck) {
			loop();
		}
	}

	/* ---------- Observers and listeners ---------- */

	/** The arm margin: canvas on before the image is reached, off when it is far again. */
	function onIntersect(entries) {
		var near = [];
		entries.forEach(function (entry) {
			var inst = entry.target.__avixPxr;
			if (!inst || inst === DONE || inst.state === 'done') {
				return;
			}
			if (!entry.isIntersecting) {
				if (!connected(entry.target)) {
					// Removed from the page: let it go.
					inst.done('gone');
				} else if (inst.state === 'armed') {
					inst.disarm();
				}
				return;
			}
			if (inst.state === 'idle') {
				near.push(inst);
			} else if (inst.state === 'armed') {
				requestCheck();
			}
		});
		if (near.length) {
			armAll(near);
		}
	}

	/**
	 * The viewport (no margin) for armed images: fires when one comes into
	 * view and at 25%, whatever moved it (a scroll, an accordion closing
	 * above it, a tab switch, a late font), so a cover never waits for the
	 * next scroll over an image that is already on screen.
	 */
	function onView(entries) {
		entries.forEach(function (entry) {
			var inst = entry.target.__avixPxr;
			if (!inst || inst === DONE || inst.state !== 'armed') {
				return;
			}
			inst.inView = entry.isIntersecting;
			if (!inst.inView) {
				inst.holdSince = 0;
				inst.dormant = false;
			}
		});
		requestCheck();
	}

	function ensureObserver() {
		if (!observer) {
			observer = new window.IntersectionObserver(onIntersect, {
				rootMargin: MARGIN + ' 0px ' + MARGIN + ' 0px',
				threshold: [0]
			});
		}
		if (!viewer) {
			viewer = new window.IntersectionObserver(onView, {
				threshold: [0, 0.25]
			});
			armed.forEach(function (inst) {
				viewer.observe(inst.img);
			});
		}
		return observer;
	}

	function onScroll() {
		requestCheck();
	}

	/** A resize or the page changing height: re-place the armed canvases and look again. */
	function onLayout() {
		requestCheck();
	}

	/** A card's own entrance (a transition or animation) started or ended. */
	function onEntrance() {
		for (var i = 0; i < armed.length; i++) {
			if (armed[i].inView && (armed[i].dormant || armed[i].holdSince)) {
				requestCheck();
				return;
			}
		}
	}

	var PASSIVE = { passive: true };
	var PASSIVE_CAPTURE = { passive: true, capture: true };
	var listening = false;

	function listen(on) {
		if (on === listening) {
			return;
		}
		listening = on;
		var method = on ? 'addEventListener' : 'removeEventListener';
		window[method]('scroll', onScroll, PASSIVE);
		// Scrolling panels and carousels inside the page.
		document[method]('scroll', onScroll, PASSIVE_CAPTURE);
		window[method]('resize', onLayout, PASSIVE);
		document[method]('transitionrun', onEntrance, true);
		document[method]('animationstart', onEntrance, true);
		document[method]('transitionend', onEntrance, true);
		document[method]('animationend', onEntrance, true);
		if (on && !resizer && 'ResizeObserver' in window && document.body) {
			resizer = new window.ResizeObserver(onLayout);
			resizer.observe(document.body);
		} else if (!on && resizer) {
			resizer.disconnect();
			resizer = null;
		}
	}

	/** Something to reveal: observers and listeners on. */
	function wake() {
		ensureObserver();
		listen(true);
	}

	/** Nothing (left) to reveal, or the page is going away: all of it off. */
	function sleep() {
		if (observer) {
			observer.disconnect();
			observer = null;
		}
		if (viewer) {
			viewer.disconnect();
			viewer = null;
		}
		listen(false);
	}

	function add(img) {
		if (img.__avixPxr) {
			return;
		}
		if (excluded(img)) {
			img.__avixPxr = DONE;
			return;
		}
		var inst = new Reveal(img);
		img.__avixPxr = inst;
		all.push(inst);
		wake();
		observer.observe(img);
	}

	function scan(root) {
		if (!enabled) {
			return;
		}
		// Images that left the page (an AJAX filter, a re-rendered widget):
		// let them go, or they would be kept in memory for the page's life.
		all.slice().forEach(function (inst) {
			if (!connected(inst.img)) {
				inst.done('gone');
			}
		});
		var scope = root && root.nodeType === 1 ? root : document;
		var roots;
		if (scope !== document && (scope.matches(SCOPE) || scope.closest(SCOPE))) {
			roots = [scope];
		} else {
			roots = toArray(scope.querySelectorAll(SCOPE));
		}
		var kept = [];
		roots.forEach(function (r) {
			for (var i = 0; i < kept.length; i++) {
				if (kept[i].contains(r)) {
					return;
				}
			}
			kept.push(r);
		});
		kept.forEach(function (r) {
			if (r.tagName === 'IMG') {
				add(r);
				return;
			}
			toArray(r.getElementsByTagName('img')).forEach(add);
		});
	}

	/**
	 * Shows every covered image now (reduced motion turned on, printing, the
	 * page going away). Images not reached yet keep their reveal. A hidden
	 * tab needs nothing: the browser pauses the loop and the observers, and
	 * a dissolve that was running completes on the first frame back.
	 */
	function settleAll() {
		all.slice().forEach(function (inst) {
			if (inst.state !== 'idle') {
				inst.finish();
			}
		});
	}

	function teardown() {
		settleAll();
		sleep();
	}

	/** Never shown with reduced motion or in print, even before the script reacts. */
	function addStyle() {
		if (document.getElementById('avix-pxr-style')) {
			return;
		}
		var style = document.createElement('style');
		style.id = 'avix-pxr-style';
		style.textContent = '@media (prefers-reduced-motion: reduce), print { canvas.avix-pxr { display: none !important; } }';
		(document.head || document.documentElement).appendChild(style);
	}

	var enabled = false;

	/** Runs fn once the browser is idle (at the latest after `timeout` ms). */
	function whenIdle(fn, timeout) {
		if (window.requestIdleCallback) {
			window.requestIdleCallback(fn, { timeout: timeout });
		} else {
			window.setTimeout(fn, 200);
		}
	}

	function init() {
		if (enabled || !canAnimate()) {
			return;
		}
		enabled = true;
		addStyle();

		// The first look waits for the page to finish loading and the browser
		// to be idle: measuring every image while the page is still settling
		// forced layouts on the main thread right when it was busiest. Images
		// are never hidden before that (they are only covered once found), and
		// the ones on screen by then are left alone as before.
		var first = function () {
			whenIdle(function () {
				scan();
			}, 1500);
		};
		if (document.readyState === 'complete') {
			first();
		} else {
			window.addEventListener('load', first);
		}
		window.addEventListener('beforeprint', settleAll);
		window.addEventListener('pagehide', teardown);
		window.addEventListener('pageshow', function (event) {
			if (event.persisted && enabled) {
				// Back/forward cache: watch the images that were never reached.
				all.forEach(function (inst) {
					if (inst.state === 'idle') {
						wake();
						observer.observe(inst.img);
					}
				});
			}
		});
		var onMotion = function () {
			if (mq.matches) {
				settleAll();
			}
		};
		if (mq.addEventListener) {
			mq.addEventListener('change', onMotion);
		} else if (mq.addListener) {
			mq.addListener(onMotion);
		}

		api.scan = scan;
		api.reveal = function (img) {
			var inst = img && img.__avixPxr;
			if (inst && inst !== DONE && inst.state !== 'done') {
				inst.finish();
			}
		};
		api.destroy = function () {
			teardown();
			enabled = false;
		};
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})(window, document);
