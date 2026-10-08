/*!
 * Avix Digital · Case Study Hero
 * Reveals the hero once (copy, then the facts cell by cell, then the
 * devices as they come into view), drifts the phone or render a little on
 * scroll with a fine pointer, and fills the reading-progress line. One
 * passive scroll listener, throttled to one rAF; the glow pauses off screen
 * and in hidden tabs. Nothing moves with reduced motion or in the editor.
 * Starts the orange shader background (AvixCsk.shader, case-study-kit.js:
 * the pixel mosaic or the smooth flow) after the page has loaded and the
 * browser is idle, keeping it dark behind every line of text (the headline
 * and lead are never hidden: LCP), and
 * lands "#anchor" buttons below the fixed header like the chapter chips.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-csh]';
	var DRIFT = 24; // px the phone / render rises over the hero's scroll range
	var instances = [];
	var mq = function (query) {
		return window.matchMedia ? window.matchMedia(query) : { matches: false };
	};
	var reduceMotion = mq('(prefers-reduced-motion: reduce)');
	var finePointer = mq('(hover: hover) and (pointer: fine)');
	var raf = window.requestAnimationFrame || function (fn) {
		return window.setTimeout(fn, 16);
	};

	function isEditMode() {
		return !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
	}

	function clamp(value, min, max) {
		return Math.max(min, Math.min(max, value));
	}

	function cssPx(name) {
		var value = parseFloat(window.getComputedStyle(document.documentElement).getPropertyValue(name));
		return isNaN(value) ? 0 : value;
	}

	/**
	 * One box per line of an element's text (a line's inline pieces merged),
	 * at most `max`: the last box takes the remaining lines.
	 */
	function lineRects(el, max) {
		var out = [];
		if (!el || !document.createRange) {
			return out;
		}
		var range = document.createRange();
		range.selectNodeContents(el);
		Array.prototype.forEach.call(range.getClientRects(), function (r) {
			if (!r.width || !r.height) {
				return;
			}
			var last = out[out.length - 1];
			if (last && (r.top < last.bottom - r.height * 0.5 || out.length >= max)) {
				last.left = Math.min(last.left, r.left);
				last.right = Math.max(last.right, r.right);
				last.top = Math.min(last.top, r.top);
				last.bottom = Math.max(last.bottom, r.bottom);
			} else {
				out.push({ left: r.left, right: r.right, top: r.top, bottom: r.bottom });
			}
		});
		out.forEach(function (r) {
			r.width = r.right - r.left;
			r.height = r.bottom - r.top;
		});
		return out;
	}

	/** The tight box around an element's text (its lines, not its column). */
	function textRect(el) {
		if (!el) {
			return null;
		}
		var rect = null;
		if (document.createRange) {
			var range = document.createRange();
			range.selectNodeContents(el);
			rect = range.getBoundingClientRect();
		}
		return rect && rect.width && rect.height ? rect : el.getBoundingClientRect();
	}

	function Hero(root) {
		var config = {};
		try {
			config = JSON.parse(root.getAttribute('data-avix-csh') || '{}') || {};
		} catch (error) {
			config = {};
		}

		this.root = root;
		this.config = config;
		this.bg = root.querySelector('.avix-csh__bg');
		this.alive = true;
		this.editor = isEditMode();
		this.visible = true;
		this.ticking = false;
		this.row = root.querySelector('.avix-csh__devices-row');
		this.drift = config.parallax ? (root.querySelector('[data-csh-phone]') || root.querySelector('[data-csh-drift]')) : null;
		this.progress = null;
		if (config.progress) {
			// The bar is printed right after the section (a fixed element
			// cannot live inside the size-contained section).
			var next = root.nextElementSibling;
			if (next && next.hasAttribute('data-csh-progress')) {
				this.progress = next.querySelector('.avix-csh__progress-bar');
			}
		}
		this.content = root.closest ? root.closest('.elementor') : null;

		this.onScroll = this.onScroll.bind(this);
		this.onVisibility = this.onVisibility.bind(this);
		this.onClick = this.onClick.bind(this);
		this.startShader = this.startShader.bind(this);
		this.tick = this.tick.bind(this);

		this.init();
	}

	Hero.prototype.init = function () {
		var self = this;
		var root = this.root;
		var motion = !reduceMotion.matches && !this.editor;

		if (this.editor) {
			root.classList.add('is-editor');
		}

		// Reveal: content stays visible without IntersectionObserver, with
		// reduced motion and in the editor.
		if ('IntersectionObserver' in window && motion) {
			root.classList.add('is-armed');
		}

		if ('IntersectionObserver' in window) {
			this.observer = new window.IntersectionObserver(function (entries) {
				if (!self.alive) {
					return;
				}
				if (!root.isConnected) {
					self.destroy();
					return;
				}
				var entry = entries[0];
				self.visible = entry.isIntersecting;
				if (entry.isIntersecting && !root.classList.contains('is-in')) {
					root.classList.add('is-in');
				}
				self.pauseCheck();
			}, { threshold: [0, 0.15] });
			this.observer.observe(root);

			// The devices have their own entrance: on phones they sit below
			// the fold while the copy is already in.
			if (this.row) {
				this.rowObserver = new window.IntersectionObserver(function (entries) {
					if (self.alive && entries[0].isIntersecting) {
						self.row.classList.add('is-in');
						self.rowObserver.disconnect();
					}
				}, { threshold: 0, rootMargin: '0px 0px -8% 0px' });
				this.rowObserver.observe(this.row);
			}
		} else {
			root.classList.add('is-in');
			if (this.row) {
				this.row.classList.add('is-in');
			}
		}

		document.addEventListener('visibilitychange', this.onVisibility);

		// Scroll work: the phone / render drift (fine pointer only) and the
		// reading progress. Neither runs in the editor or with reduced motion.
		this.useDrift = !!(this.drift && motion && finePointer.matches);
		this.useProgress = !!(this.progress && motion);
		if (this.useDrift || this.useProgress) {
			window.addEventListener('scroll', this.onScroll, { passive: true });
			window.addEventListener('resize', this.onScroll, { passive: true });
			this.onScroll();
		}

		root.addEventListener('click', this.onClick);

		// The shader never competes with the first paint (the headline is
		// the LCP) or the hero image: it starts once the page has loaded and
		// the browser is idle; in the editor right away. The CSS glow shows
		// until then.
		if (this.config.shader && this.bg && window.AvixCsk && window.AvixCsk.shader) {
			if (this.editor) {
				this.idle = window.setTimeout(this.startShader, 0);
			} else if (document.readyState === 'complete') {
				this.queueShader();
			} else {
				this.onLoad = function () {
					self.queueShader();
				};
				window.addEventListener('load', this.onLoad);
			}
		}
	};

	Hero.prototype.queueShader = function () {
		if (this.onLoad) {
			window.removeEventListener('load', this.onLoad);
			this.onLoad = null;
		}
		if (!this.alive) {
			return;
		}
		if (window.requestIdleCallback) {
			this.idleId = window.requestIdleCallback(this.startShader, { timeout: 1200 });
		} else {
			this.idle = window.setTimeout(this.startShader, 200);
		}
	};

	Hero.prototype.startShader = function () {
		var self = this;
		var root = this.root;
		var cfg = this.config.shader || {};
		this.idleId = 0;
		if (!this.alive || this.shader || !root.isConnected) {
			return;
		}
		var cell = cfg.cell || {};
		this.shader = window.AvixCsk.shader(this.bg, {
			className: 'avix-csh__shader',
			root: root,
			style: cfg.style === 'smooth' ? 'smooth' : 'pixel',
			// The pixel size for the Elementor breakpoint the hero's width
			// falls in (the hero spans the page).
			cell: function (width) {
				var pick = width <= 767 ? cell.m : (width <= 1024 ? cell.t : cell.d);
				return pick || (width <= 767 ? 12 : (width <= 1024 ? 16 : 18));
			},
			sparkles: cfg.sparkles !== false,
			// No assembly replay on every change while editing.
			assemble: !this.editor,
			intensity: typeof cfg.intensity === 'number' ? cfg.intensity : 1,
			speed: typeof cfg.speed === 'number' ? cfg.speed : 1,
			pointer: cfg.pointer !== false,
			colors: function () {
				var style = window.getComputedStyle(root);
				var get = function (name) {
					return (style.getPropertyValue(name) || '').trim();
				};
				return {
					base: get('--csh-shader-base') || get('--csh-bg'),
					accent: get('--csh-shader-accent') || get('--csh-accent')
				};
			},
			layout: function (box) {
				return self.shaderLayout(box);
			},
			onState: function (on) {
				window.clearTimeout(self.swap);
				if (!on) {
					root.classList.remove('has-shader');
					return;
				}
				// The glow and grid go once the canvas has faded in over them.
				self.swap = window.setTimeout(function () {
					if (self.alive) {
						root.classList.add('has-shader');
					}
				}, reduceMotion.matches ? 0 : 950);
			}
		});
	};

	/**
	 * Where the shader may glow, in CSS px from the background's top left:
	 * calm (capped dark) behind every line of text, the buttons, the facts
	 * and the header's links; brightest right of the headline (above it on
	 * narrow screens); gone by the top of the render, whose own dark
	 * backdrop would otherwise show as a box.
	 */
	Hero.prototype.shaderLayout = function (box) {
		var root = this.root;
		var calm = [];
		var add = function (rect, pad) {
			pad = pad || 0;
			if (rect && rect.width && rect.height) {
				calm.push([rect.left - box.left - pad, rect.top - box.top - pad, rect.right - box.left + pad, rect.bottom - box.top + pad]);
			}
		};
		var el = function (selector) {
			return root.querySelector(selector);
		};
		var logo = el('.avix-csh__logo-img');
		var actions = el('.avix-csh__actions');
		var facts = el('.avix-csh__facts-wrap');
		var title = textRect(el('.avix-csh__title'));
		var lines = lineRects(el('.avix-csh__title'), 3);
		add(textRect(el('.avix-csh__crumb-list')));
		add(logo && logo.getBoundingClientRect());
		add(textRect(el('.avix-csh__eyebrow')));
		if (lines.length) {
			lines.forEach(function (line) {
				add(line, 6);
			});
		} else {
			add(title, 6);
		}
		add(textRect(el('.avix-csh__lead')));
		// Wider around the buttons: the orange one keeps a dark edge.
		add(actions && actions.getBoundingClientRect(), 24);
		add(facts && facts.getBoundingClientRect());
		// The fixed header sits over the top of the hero.
		var header = root.classList.contains('avix-csh--clear') ? cssPx('--avix-header-h') : 0;
		if (header > 0) {
			calm.push([0, 0, box.width, header - 12]);
		}

		var w = box.width;
		var focus = [w * 0.8, Math.min(box.height * 0.3, 380), Math.max(280, w * 0.3)];
		if (title) {
			var right = title.right - box.left;
			focus = w > 1024
				? [right + (w - right) * 0.62, title.top - box.top + title.height * 0.3, clamp(w * 0.28, 300, 440)]
				: [w * 0.86, Math.max(0, title.top - box.top - 40), Math.max(220, w * 0.62)];
		}

		var height = box.height;
		var fade = box.height * 0.3;
		// The figures, not the frames: the frames are moved by the entrance.
		var render = el('.avix-csh__render');
		var devices = el('.avix-csh__devices');
		if (render) {
			var r = render.getBoundingClientRect();
			height = r.top - box.top + r.height * 0.05;
			fade = clamp(height * 0.3, 180, 340);
		} else if (devices) {
			var d = devices.getBoundingClientRect();
			height = d.top - box.top + d.height * 0.55;
			fade = clamp(height * 0.35, 200, 420);
		}
		return { height: height, fade: fade, focus: focus, calm: calm };
	};

	// "#anchor" buttons: land the target below the fixed header (as the
	// chapter chips do) and move focus there.
	Hero.prototype.onClick = function (event) {
		if (this.editor || event.defaultPrevented || event.button > 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
			return;
		}
		var link = event.target && event.target.closest ? event.target.closest('a[href^="#"]') : null;
		if (!link || !this.root.contains(link)) {
			return;
		}
		var id = (link.getAttribute('href') || '').slice(1);
		var target = null;
		try {
			target = id ? document.getElementById(decodeURIComponent(id)) : null;
		} catch (error) {
			target = null;
		}
		if (!target) {
			return;
		}
		event.preventDefault();
		var offset = cssPx('--wp-admin--admin-bar--height') + cssPx('--avix-header-h') + 16;
		var top = target.getBoundingClientRect().top + (window.pageYOffset || document.documentElement.scrollTop || 0) - offset;
		try {
			window.scrollTo({ top: Math.max(0, top), behavior: reduceMotion.matches ? 'auto' : 'smooth' });
		} catch (error) {
			window.scrollTo(0, Math.max(0, top));
		}
		if (window.history && window.history.pushState) {
			window.history.pushState(null, '', '#' + id);
		}
		if (!target.hasAttribute('tabindex')) {
			target.setAttribute('tabindex', '-1');
		}
		try {
			target.focus({ preventScroll: true });
		} catch (error) {
			// Older browsers without focus options: skip moving focus.
		}
	};

	Hero.prototype.pauseCheck = function () {
		this.root.classList.toggle('is-paused', !this.visible || document.hidden);
	};

	Hero.prototype.onVisibility = function () {
		if (!this.alive) {
			return;
		}
		this.pauseCheck();
	};

	Hero.prototype.onScroll = function () {
		if (!this.alive || this.ticking) {
			return;
		}
		this.ticking = true;
		raf(this.tick);
	};

	Hero.prototype.tick = function () {
		this.ticking = false;
		if (!this.alive) {
			return;
		}
		if (!this.root.isConnected) {
			this.destroy();
			return;
		}
		var rect = this.root.getBoundingClientRect();
		var vh = window.innerHeight || document.documentElement.clientHeight;

		if (this.useDrift && rect.bottom > 0 && rect.top < vh) {
			// 0 at the top of the hero, 1 when its bottom leaves the screen.
			var range = Math.max(1, rect.height);
			var p = clamp(-rect.top / range, 0, 1);
			this.drift.style.translate = '0 ' + (-DRIFT * p).toFixed(2) + 'px';
		}

		if (this.useProgress) {
			// From the top of the hero to the end of the page content.
			var start = rect.top + window.pageYOffset;
			var endEl = this.content || document.body;
			var end = endEl.getBoundingClientRect().bottom + window.pageYOffset - vh;
			var progress = end > start ? clamp((window.pageYOffset - start) / (end - start), 0, 1) : 0;
			this.progress.style.transform = 'scaleX(' + progress.toFixed(4) + ')';
		}
	};

	Hero.prototype.destroy = function () {
		if (!this.alive) {
			return;
		}
		this.alive = false;
		if (this.observer) {
			this.observer.disconnect();
		}
		if (this.rowObserver) {
			this.rowObserver.disconnect();
		}
		window.removeEventListener('scroll', this.onScroll, { passive: true });
		window.removeEventListener('resize', this.onScroll, { passive: true });
		document.removeEventListener('visibilitychange', this.onVisibility);
		this.root.removeEventListener('click', this.onClick);
		if (this.onLoad) {
			window.removeEventListener('load', this.onLoad);
			this.onLoad = null;
		}
		window.clearTimeout(this.idle);
		window.clearTimeout(this.swap);
		if (this.idleId && window.cancelIdleCallback) {
			window.cancelIdleCallback(this.idleId);
		}
		if (this.shader) {
			this.shader.destroy();
			this.shader = null;
		}
		var index = instances.indexOf(this);
		if (index > -1) {
			instances.splice(index, 1);
		}
	};

	function mount(root) {
		if (!root || (root.__avixCsh && root.__avixCsh.alive)) {
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
			root.__avixCsh = hero;
			instances.push(hero);
		}
	}

	function mountAll(scope) {
		var root = scope || document;
		if (root.matches && root.matches(ROOT_SELECTOR)) {
			mount(root);
			return;
		}
		Array.prototype.forEach.call(root.querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixCaseStudyHero = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-case-study-hero.default', function ($scope) {
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
