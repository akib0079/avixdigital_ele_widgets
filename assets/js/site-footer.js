/*!
 * Avix Digital · Site Footer
 * Copy-to-clipboard for emails, back-to-top, and current-page link state.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-ft]';
	var reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };

	/* ---------- Pixel wordmark ---------- */

	// The brand name as a dot-matrix of square pixels. It assembles when the
	// footer comes into view; pixels near the pointer grow, lift and glow in
	// the accent colour, and the glow fades slowly so the pointer leaves a trail.
	function PixelWord(host, root) {
		this.host = host;
		this.root = root;
		this.canvas = host.querySelector('canvas');
		this.ctx = this.canvas && this.canvas.getContext ? this.canvas.getContext('2d') : null;
		if (!this.ctx) {
			return;
		}
		this.text = host.getAttribute('data-ft-word') || 'avixdigital';
		this.accentChars = host.getAttribute('data-ft-accent') || '';
		this.reduced = reduceMotion.matches;
		this.progress = this.reduced ? 1 : 0;
		this.mouse = { x: -1e4, y: -1e4, on: false };
		this.visible = false;
		this.started = this.reduced;
		this.raf = 0;
		this.n = 0;
		var self = this;
		var styles = window.getComputedStyle(host);
		this.ink = this.parse(styles.color, [255, 255, 255]);
		this.accent = this.parse(styles.getPropertyValue('--ft-accent'), [251, 96, 7]);

		if (window.ResizeObserver) {
			this.ro = new window.ResizeObserver(function (entries) {
				var box = entries[0].contentRect;
				self.size = [box.width, box.height];
				self.layout();
			});
			this.ro.observe(this.canvas);
		}
		if (window.IntersectionObserver) {
			this.io = new window.IntersectionObserver(function (entries) {
				self.visible = entries[0].isIntersecting;
				if (self.visible && !self.started && self.n) {
					self.started = true;
					self.t0 = window.performance.now();
				}
				self.request();
			}, { threshold: 0.15 });
			this.io.observe(host);
		}
		root.addEventListener('pointermove', function (event) {
			var box = self.canvas.getBoundingClientRect();
			self.mouse.x = event.clientX - box.left;
			self.mouse.y = event.clientY - box.top;
			self.mouse.on = true;
			self.request();
		}, { passive: true });
		root.addEventListener('pointerleave', function () {
			self.mouse.on = false;
			self.request();
		});
		if (document.fonts && document.fonts.load) {
			document.fonts.load('600 100px "Space Grotesk"').then(function () {
				self.fontReady = true;
				self.layout();
			}, function () {
				self.fontReady = true;
				self.layout();
			});
		} else {
			this.fontReady = true;
		}
	}

	PixelWord.prototype.parse = function (value, fallback) {
		var m = String(value || '').match(/\d+(\.\d+)?/g);
		if (m && m.length >= 3) {
			return [+m[0], +m[1], +m[2]];
		}
		var hex = String(value || '').trim().replace('#', '');
		if (/^[0-9a-f]{6}$/i.test(hex)) {
			return [parseInt(hex.slice(0, 2), 16), parseInt(hex.slice(2, 4), 16), parseInt(hex.slice(4, 6), 16)];
		}
		return fallback;
	};

	PixelWord.prototype.layout = function () {
		if (!this.size || !this.fontReady) {
			return;
		}
		var w = this.size[0];
		var h = this.size[1];
		if (!w || !h) {
			return;
		}
		var dpr = Math.min(window.devicePixelRatio || 1, 2);
		this.dpr = dpr;
		this.w = w;
		this.h = h;
		this.canvas.width = Math.round(w * dpr);
		this.canvas.height = Math.round(h * dpr);
		var gap = w < 600 ? 4 : Math.max(6, Math.round(w / 185));
		this.gap = gap;
		var cols = Math.floor(w / gap);
		var rows = Math.floor(h / gap);
		var off = document.createElement('canvas');
		off.width = cols;
		off.height = rows;
		var o = off.getContext('2d');
		var font = function (px) {
			return '600 ' + px + 'px "Space Grotesk", "Inter", Arial, sans-serif';
		};
		var size = rows * 1.3;
		o.font = font(size);
		size *= Math.min(1, (cols * 0.995) / o.measureText(this.text).width);
		o.font = font(size);
		o.textBaseline = 'alphabetic';
		var m = o.measureText(this.text);
		var asc = m.actualBoundingBoxAscent || size * 0.72;
		var desc = m.actualBoundingBoxDescent || 0;
		var x0 = (cols - m.width) / 2;
		var y0 = (rows - (asc + desc)) / 2 + asc;
		o.fillStyle = '#000';
		o.fillText(this.text, x0, y0);
		// Column ranges of the accent characters.
		var ranges = [];
		for (var c = 0; c < this.text.length; c++) {
			if (this.accentChars && this.accentChars.indexOf(this.text.charAt(c)) !== -1) {
				var a0 = x0 + o.measureText(this.text.slice(0, c)).width;
				ranges.push([a0, a0 + o.measureText(this.text.charAt(c)).width]);
			}
		}
		var data = o.getImageData(0, 0, cols, rows).data;
		var xs = [];
		var ys = [];
		var acc = [];
		var delays = [];
		var seed = 7;
		var rand = function () {
			seed ^= seed << 13;
			seed ^= seed >>> 17;
			seed ^= seed << 5;
			return (seed >>> 0) / 4294967296;
		};
		for (var y = 0; y < rows; y++) {
			for (var x = 0; x < cols; x++) {
				if (data[(y * cols + x) * 4 + 3] > 120) {
					var isAcc = false;
					for (var r = 0; r < ranges.length; r++) {
						if (x >= ranges[r][0] && x <= ranges[r][1]) {
							isAcc = true;
						}
					}
					xs.push((x + 0.5) * gap);
					ys.push((y + 0.5) * gap);
					acc.push(isAcc ? 1 : 0);
					delays.push((x / cols) * 0.5 + rand() * 0.3);
				}
			}
		}
		this.n = xs.length;
		this.xs = Float32Array.from(xs);
		this.ys = Float32Array.from(ys);
		this.acc = Uint8Array.from(acc);
		this.delay = Float32Array.from(delays);
		this.heat = new Float32Array(this.n);
		this.bucket = new Uint8Array(this.n);
		this.styles = [];
		for (var b = 0; b <= 8; b++) {
			var k = Math.min(1, (b / 8) * 1.4);
			var col = this.ink.map(function (v, i) {
				return Math.round(v + (this.accent[i] - v) * k);
			}, this);
			this.styles.push('rgb(' + col.join(',') + ')');
		}
		this.accentStyle = 'rgb(' + this.accent.join(',') + ')';
		if (this.visible && !this.started) {
			this.started = true;
			this.t0 = window.performance.now();
		}
		this.request(true);
	};

	PixelWord.prototype.request = function (force) {
		if (this.raf || !this.n || (!this.visible && !force)) {
			return;
		}
		var self = this;
		this.raf = window.requestAnimationFrame(function (t) {
			self.raf = 0;
			var busy = self.draw(t);
			if (busy && self.visible) {
				self.request();
			}
		});
	};

	PixelWord.prototype.draw = function (t) {
		var ctx = this.ctx;
		var gap = this.gap;
		if (this.started && this.progress < 1) {
			var k = Math.min(1, (t - this.t0) / 2000);
			this.progress = 1 - Math.pow(1 - k, 2);
		}
		ctx.setTransform(this.dpr, 0, 0, this.dpr, 0, 0);
		ctx.clearRect(0, 0, this.w, this.h);
		var R = gap * 13;
		var base = gap * 0.6;
		var mx = this.mouse.on ? this.mouse.x : -1e4;
		var my = this.mouse.on ? this.mouse.y : -1e4;
		var anyHeat = false;
		var i;
		for (i = 0; i < this.n; i++) {
			var dx = this.xs[i] - mx;
			var dy = this.ys[i] - my;
			var target = 0;
			if (dx > -R && dx < R && dy > -R && dy < R) {
				var dist = Math.sqrt(dx * dx + dy * dy);
				if (dist < R) {
					target = (1 - dist / R) * (1 - dist / R);
				}
			}
			var hv = target > this.heat[i] ? target : this.heat[i] * (this.reduced ? 0 : 0.94);
			this.heat[i] = hv;
			if (hv > 0.01) {
				anyHeat = true;
			}
			this.bucket[i] = this.acc[i] ? 9 : Math.min(8, Math.round(hv * 8));
		}
		for (var b = 0; b <= 9; b++) {
			ctx.fillStyle = b === 9 ? this.accentStyle : this.styles[b];
			for (i = 0; i < this.n; i++) {
				if (this.bucket[i] !== b) {
					continue;
				}
				var tp = Math.min(1, Math.max(0, (this.progress - this.delay[i] * 0.6) / 0.4));
				if (tp <= 0) {
					continue;
				}
				var h = this.heat[i];
				var s = base * tp + gap * 0.42 * h;
				ctx.globalAlpha = (this.acc[i] ? 1 : 0.86) * tp;
				ctx.fillRect(this.xs[i] - s / 2, this.ys[i] - s / 2 - h * gap * 0.7, s, s);
			}
		}
		ctx.globalAlpha = 1;
		return this.progress < 1 || anyHeat || this.mouse.on;
	};

	function normalise(url) {
		try {
			var parsed = new URL(url, window.location.href);
			if (parsed.origin !== window.location.origin) {
				return null;
			}
			return parsed.pathname.replace(/\/+$/, '') || '/';
		} catch (error) {
			return null;
		}
	}

	function copyText(text) {
		if (navigator.clipboard && window.isSecureContext) {
			return navigator.clipboard.writeText(text);
		}
		return new Promise(function (resolve, reject) {
			var field = document.createElement('textarea');
			field.value = text;
			field.setAttribute('readonly', '');
			field.style.position = 'fixed';
			field.style.opacity = '0';
			document.body.appendChild(field);
			field.select();
			var ok = false;
			try {
				ok = document.execCommand('copy');
			} catch (error) {
				ok = false;
			}
			field.remove();
			if (ok) {
				resolve();
			} else {
				reject(new Error('copy failed'));
			}
		});
	}

	// Headers used by common themes, tried after the widget's own selector.
	var HEADER_FALLBACK = '.elementor-location-header, #masthead, header.site-header, .site-header, body > header';
	var HEADER_ITEMS = 'a, button, img, svg, input, select, [role="button"]';

	function findHeaders(selector) {
		var found = [];
		[selector, HEADER_FALLBACK].forEach(function (query) {
			if (!query) {
				return;
			}
			try {
				Array.prototype.push.apply(found, document.querySelectorAll(query));
			} catch (error) {
				// Invalid selector typed in the editor: skip it.
			}
		});
		return found;
	}

	/**
	 * Left edge of the header's first item (logo) and right edge of its last
	 * (usually the CTA), counting only what is visible inside the header bar:
	 * dropdowns, off-canvas menus and hidden items are ignored.
	 */
	function headerEdges(selector, footer) {
		var headers = findHeaders(selector);
		var viewport = document.documentElement.clientWidth;
		for (var h = 0; h < headers.length; h++) {
			var header = headers[h];
			if (header.contains(footer)) {
				continue;
			}
			var bar = header.getBoundingClientRect();
			if (bar.width < 200 || bar.height < 16) {
				continue;
			}
			var left = Infinity;
			var right = -Infinity;
			var count = 0;
			var items = header.querySelectorAll(HEADER_ITEMS);
			for (var i = 0; i < items.length; i++) {
				var item = items[i];
				if (item.ownerSVGElement) {
					continue;
				}
				var rect = item.getBoundingClientRect();
				if (rect.width < 2 || rect.height < 2 || rect.top < bar.top - 1 || rect.bottom > bar.bottom + 1 || rect.left < -1 || rect.right > viewport + 1) {
					continue;
				}
				var style = window.getComputedStyle(item);
				if (style.visibility === 'hidden' || parseFloat(style.opacity) === 0) {
					continue;
				}
				left = Math.min(left, rect.left);
				right = Math.max(right, rect.right);
				count++;
			}
			if (count >= 2 && right - left > 160) {
				return { left: left, right: right };
			}
		}
		return null;
	}

	function matchHeader(root) {
		var selector = root.getAttribute('data-ft-match');
		var card = root.querySelector('.avix-ft__card');
		if (selector === null || !card) {
			return;
		}
		var frame = 0;

		function apply() {
			frame = 0;
			if (!root.isConnected) {
				return;
			}
			var edges = headerEdges(selector, root);
			var box = card.getBoundingClientRect();
			var left = edges ? Math.round(edges.left - box.left) : -1;
			var right = edges ? Math.round(box.right - edges.right) : -1;
			if (left < 0 || right < 0 || box.width - left - right < 240) {
				root.classList.remove('is-matched');
				return;
			}
			root.style.setProperty('--ft-match-l', left + 'px');
			root.style.setProperty('--ft-match-r', right + 'px');
			root.classList.add('is-matched');
		}

		function schedule() {
			if (!frame) {
				frame = window.requestAnimationFrame(apply);
			}
		}

		apply();
		window.addEventListener('resize', schedule, { passive: true });
		window.addEventListener('load', schedule);
		if (document.fonts && document.fonts.ready) {
			document.fonts.ready.then(schedule);
		}
		// Headers often shrink once scrolled: measure again as the footer comes into view.
		if ('IntersectionObserver' in window) {
			new window.IntersectionObserver(function (entries) {
				if (entries[entries.length - 1].isIntersecting) {
					schedule();
				}
			}).observe(root);
		}
		if (window.ResizeObserver) {
			var headers = findHeaders(selector);
			if (headers.length) {
				new window.ResizeObserver(schedule).observe(headers[0]);
			}
		}
	}

	function mount(root) {
		if (!root || root.__avixFt) {
			return;
		}
		root.__avixFt = true;
		matchHeader(root);
		if ('IntersectionObserver' in window) {
			new window.IntersectionObserver(function (entries) {
				root.classList.toggle('is-offscreen', !entries[entries.length - 1].isIntersecting);
			}, { rootMargin: '80px 0px' }).observe(root);
		}
		var status = root.querySelector('[data-ft-status]');

		Array.prototype.forEach.call(root.querySelectorAll('[data-ft-copy]'), function (button) {
			var timer = 0;
			button.addEventListener('click', function (event) {
				event.preventDefault();
				var value = button.getAttribute('data-ft-copy');
				copyText(value).then(function () {
					button.classList.add('is-copied');
					if (status) {
						status.textContent = (status.getAttribute('data-copied') || 'Copied') + ': ' + value;
					}
					window.clearTimeout(timer);
					timer = window.setTimeout(function () {
						button.classList.remove('is-copied');
					}, 2200);
				}).catch(function () {
					window.location.href = 'mailto:' + value;
				});
			});
		});

		var word = root.querySelector('[data-ft-word]');
		if (word) {
			new PixelWord(word, root.querySelector('.avix-ft__card') || root);
		}

		var top = root.querySelector('[data-ft-top]');
		if (top) {
			top.addEventListener('click', function () {
				window.scrollTo({ top: 0, behavior: reduceMotion.matches ? 'auto' : 'smooth' });
				var target = document.querySelector('main, [role="main"], #content, body');
				if (target && target !== document.body) {
					if (!target.hasAttribute('tabindex')) {
						target.setAttribute('tabindex', '-1');
					}
					target.focus({ preventScroll: true });
				}
			});
		}

		// The pixel buddy drops onto the headline the first time it is seen.
		var buddy = root.querySelector('.avix-ft__buddy');
		if (buddy && 'IntersectionObserver' in window && !reduceMotion.matches) {
			root.classList.add('is-armed');
			var observer = new window.IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						buddy.classList.add('is-landed');
						observer.disconnect();
					}
				});
			}, { threshold: 1, rootMargin: '0px 0px -12% 0px' });
			observer.observe(buddy.parentNode);
		}

		var here = normalise(window.location.href);
		Array.prototype.forEach.call(root.querySelectorAll('.avix-ft__col a.avix-ft__link, a.avix-ft__legal-link'), function (link) {
			if (here && normalise(link.href) === here) {
				link.setAttribute('aria-current', 'page');
			}
		});
	}

	function mountAll(scope) {
		Array.prototype.forEach.call((scope || document).querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixSiteFooter = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-site-footer.default', function ($scope) {
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
