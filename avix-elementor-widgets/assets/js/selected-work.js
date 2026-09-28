/*!
 * Avix Digital · Selected Work (scroll stack)
 * Dependency-free. Cards are CSS sticky; this script adds the 3D "buried card"
 * motion, image parallax, the live progress readout, the header release and
 * an offset that follows fixed headers (admin bar, hide-on-scroll site header).
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-work]';
	var instances = [];
	var mq = function (query) {
		return window.matchMedia ? window.matchMedia(query) : { matches: false, addEventListener: function () {} };
	};
	var reduceMotion = mq('(prefers-reduced-motion: reduce)');
	var finePointer = mq('(hover: hover) and (pointer: fine)');

	// Motion recipe per layout, tuned to the original GSAP timeline.
	var PRESETS = {
		overlay: { perspective: 1400, scale: 0.86, scaleStep: 0.035, rotX: -7, rotXStep: 1.5, rotZ: 2.2, y: -18, z: -160, start: 0.08, parallax: 2.4 },
		stacked: { perspective: 1100, scale: 0.925, scaleStep: 0.015, rotX: -4.5, rotXStep: 1, rotZ: 1.15, y: -10, z: -110, start: 0.07, parallax: 0 }
	};

	var ELEMENTOR_WRAPPERS = '.elementor-element, .e-con, .e-con-inner, .elementor-container, .elementor-column, .elementor-widget-wrap, .elementor-widget-container, .elementor-section';

	function clamp(value, min, max) {
		return value < min ? min : value > max ? max : value;
	}

	function toNumber(value, fallback) {
		var parsed = parseFloat(value);
		return isFinite(parsed) ? parsed : fallback;
	}

	function pad(value) {
		return value < 10 ? '0' + value : String(value);
	}

	function isEditMode() {
		return !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
	}

	function Stack(root) {
		var config = {};
		try {
			config = JSON.parse(root.getAttribute('data-avix-work') || '{}') || {};
		} catch (error) {
			config = {};
		}

		this.root = root;
		this.alive = true;
		this.config = {
			motion: config.motion !== false,
			intensity: clamp(toNumber(config.intensity, 1), 0, 2),
			parallax: config.parallax !== false,
			dim: clamp(toNumber(config.dim, 0.42), 0, 0.9),
			releaseHead: config.releaseHead !== false,
			offsetSelectors: typeof config.offsetSelectors === 'string' ? config.offsetSelectors : '#wpadminbar',
			offsetExtra: toNumber(config.offsetExtra, 0),
			fixOverflow: config.fixOverflow !== false,
			cursor: !!config.cursor,
			cursorText: config.cursorText || 'View case'
		};

		this.stack = root.querySelector('[data-aw-stack]');
		this.head = root.querySelector('[data-aw-head]');
		this.cards = Array.prototype.slice.call(root.querySelectorAll('[data-aw-card]'));
		this.images = this.cards.map(function (card) {
			return card.querySelector('[data-aw-img]');
		});
		this.currentLabel = root.querySelector('[data-aw-current]');
		this.progressBar = root.querySelector('[data-aw-bar]');
		this.count = this.cards.length;

		this.progress = this.cards.map(function () { return 0; });
		this.naturalTops = [];
		this.stackBottom = 0;
		this.lastHeight = 0;
		this.baseTop = 0;
		this.offset = 0;
		this.offsetElements = [];
		this.layout = 'overlay';
		this.current = -1;
		this.frame = 0;
		this.lastTime = 0;
		this.activeUntil = 0;
		this.cursor = null;

		this.onScroll = this.wake.bind(this);
		this.onResize = this.refresh.bind(this);
		this.onMotionChange = this.refresh.bind(this);
		this.tick = this.tick.bind(this);

		if (!this.stack || !this.count) {
			this.alive = false;
			return;
		}

		this.init();
	}

	Stack.prototype.init = function () {
		var self = this;

		if (this.config.fixOverflow) {
			this.releaseOverflowAncestors();
		}

		window.addEventListener('scroll', this.onScroll, { passive: true });
		window.addEventListener('resize', this.onResize, { passive: true });
		window.addEventListener('load', this.onResize);
		if (reduceMotion.addEventListener) {
			reduceMotion.addEventListener('change', this.onMotionChange);
		}

		if (window.ResizeObserver) {
			this.resizeObserver = new window.ResizeObserver(function () {
				self.refresh();
			});
			this.resizeObserver.observe(this.stack);
		}

		this.images.forEach(function (image) {
			if (image && !image.complete) {
				image.addEventListener('load', self.onResize, { once: true });
			}
		});

		if (document.fonts && document.fonts.ready) {
			document.fonts.ready.then(function () {
				self.refresh();
			});
		}

		if (this.config.cursor && finePointer.matches && !isEditMode()) {
			this.setupCursor();
		}

		this.root.classList.add('is-ready');
		this.refresh();
	};

	// Sticky breaks under overflow:hidden ancestors. Elementor wrappers are
	// flex/grid boxes, so swapping hidden for clip keeps the clipping without
	// creating a scroll container. Theme wrappers are only reported.
	Stack.prototype.releaseOverflowAncestors = function () {
		for (var node = this.root.parentElement; node && node !== document.body && node !== document.documentElement; node = node.parentElement) {
			var style = window.getComputedStyle(node);
			var blocking = /(hidden|auto|scroll)/.test(style.overflowX + style.overflowY);
			if (!blocking) {
				continue;
			}
			var onlyHidden = !/(auto|scroll)/.test(style.overflowX + style.overflowY);
			if (onlyHidden && node.matches && node.matches(ELEMENTOR_WRAPPERS)) {
				node.style.overflow = 'clip';
				(this.clipped = this.clipped || []).push(node);
			} else if (window.console && !this.warned) {
				this.warned = true;
				window.console.warn('[Avix Selected Work] An ancestor has overflow "' + style.overflowX + '/' + style.overflowY + '", which stops the sticky stack. Change it to "clip" or "visible".', node);
			}
		}
	};

	Stack.prototype.refresh = function () {
		if (!this.alive) {
			return;
		}
		this.measure();
		this.wake();
	};

	Stack.prototype.measure = function () {
		var styles = window.getComputedStyle(this.stack);
		var scrollY = window.pageYOffset || document.documentElement.scrollTop || 0;
		var stackRect = this.stack.getBoundingClientRect();
		var top = stackRect.top + scrollY;
		var self = this;

		this.layout = styles.getPropertyValue('--aw-layout').trim() === 'stacked' ? 'stacked' : 'overlay';
		this.naturalTops = [];

		this.cards.forEach(function (card) {
			self.naturalTops.push(top);
			top += card.offsetHeight + toNumber(window.getComputedStyle(card).marginBottom, 0);
		});

		this.lastHeight = this.cards[this.count - 1].offsetHeight;
		this.stackBottom = stackRect.bottom + scrollY - toNumber(styles.paddingBottom, 0);
		this.baseTop = toNumber(window.getComputedStyle(this.cards[0]).top, 0) - this.offset;

		this.offsetElements = [];
		if (this.config.offsetSelectors) {
			try {
				Array.prototype.forEach.call(document.querySelectorAll(this.config.offsetSelectors), function (element) {
					var position = window.getComputedStyle(element).position;
					if ((position === 'fixed' || position === 'sticky') && !self.root.contains(element)) {
						self.offsetElements.push(element);
					}
				});
			} catch (error) {
				// Invalid selector typed in the editor: ignore.
			}
		}
	};

	Stack.prototype.readOffset = function (viewportHeight) {
		var offset = 0;
		for (var i = 0; i < this.offsetElements.length; i++) {
			var rect = this.offsetElements[i].getBoundingClientRect();
			if (rect.height > 0 && rect.bottom > 0 && rect.top < viewportHeight * 0.35) {
				offset = Math.max(offset, rect.bottom);
			}
		}
		return clamp(offset, 0, viewportHeight * 0.35) + this.config.offsetExtra;
	};

	Stack.prototype.wake = function () {
		if (!this.alive) {
			return;
		}
		// Keep ticking briefly after the last scroll so smoothing and header
		// slide-in animations settle.
		this.activeUntil = (window.performance ? window.performance.now() : Date.now()) + 750;
		if (!this.frame) {
			this.lastTime = 0;
			this.frame = window.requestAnimationFrame(this.tick);
		}
	};

	Stack.prototype.tick = function (time) {
		this.frame = 0;

		if (!this.root.isConnected) {
			this.destroy();
			return;
		}

		var dt = this.lastTime ? Math.min(0.1, (time - this.lastTime) / 1000) : 1 / 60;
		this.lastTime = time;

		// ---- Reads ----
		var scrollY = window.pageYOffset || document.documentElement.scrollTop || 0;
		var viewportHeight = window.innerHeight;
		var offset = this.readOffset(viewportHeight);
		var stickyTop = this.baseTop + offset;
		var preset = PRESETS[this.layout];
		var motion = this.config.motion && !reduceMotion.matches;
		var intensity = this.config.intensity;
		var start = viewportHeight * (1 - preset.start);
		var span = Math.max(1, start - stickyTop);
		var smoothing = 1 - Math.exp(-dt / 0.13);
		var settled = true;
		var current = 0;
		var i;

		for (i = 0; i < this.count; i++) {
			if (this.naturalTops[i] - scrollY <= stickyTop + 4) {
				current = i;
			}
		}

		for (i = 0; i < this.count - 1; i++) {
			var target = clamp((start - (this.naturalTops[i + 1] - scrollY)) / span, 0, 1);
			var value = this.progress[i];
			value += (target - value) * smoothing;
			if (Math.abs(target - value) < 0.0006) {
				value = target;
			} else {
				settled = false;
			}
			this.progress[i] = value;
		}

		// ---- Writes ----
		if (Math.abs(offset - this.offset) > 0.25) {
			this.offset = offset;
			this.root.style.setProperty('--aw-offset', offset.toFixed(2) + 'px');
		}

		for (i = 0; i < this.count; i++) {
			var card = this.cards[i];
			var p = i < this.count - 1 ? this.progress[i] : 0;

			if (motion && intensity > 0 && p > 0.0001) {
				var direction = i % 2 === 0 ? -1 : 1;
				var endScale = Math.min(0.97, preset.scale + i * preset.scaleStep);
				var scale = 1 - (1 - endScale) * p * intensity;
				var rotX = Math.min(-1, preset.rotX + i * preset.rotXStep) * p * intensity;
				var rotZ = direction * preset.rotZ * p * intensity;
				card.style.transform = 'perspective(' + preset.perspective + 'px) translate3d(0,' + (preset.y * p * intensity).toFixed(2) + 'px,' + (preset.z * p * intensity).toFixed(2) + 'px) rotateX(' + rotX.toFixed(3) + 'deg) rotate(' + rotZ.toFixed(3) + 'deg) scale(' + scale.toFixed(4) + ')';
			} else if (card.style.transform) {
				card.style.transform = '';
			}

			card.style.setProperty('--aw-dim', (p * this.config.dim).toFixed(3));
			card.classList.toggle('is-past', i < current);
			card.classList.toggle('is-current', i === current);

			var image = this.images[i];
			if (image) {
				if (motion && this.config.parallax && preset.parallax) {
					var naturalTop = this.naturalTops[i] - scrollY;
					var enter = clamp((viewportHeight - naturalTop) / Math.max(1, viewportHeight - stickyTop), 0, 1);
					var shift = p > 0 ? p * preset.parallax : (enter - 1) * preset.parallax;
					image.style.setProperty('--aw-img-y', shift.toFixed(3) + '%');
				} else {
					image.style.removeProperty('--aw-img-y');
				}
			}
		}

		// The readout flips once the incoming card covers most of the current one.
		var shown = current < this.count - 1 && this.progress[current] > 0.55 ? current + 1 : current;
		if (shown !== this.current) {
			this.current = shown;
			if (this.currentLabel) {
				this.currentLabel.textContent = pad(shown + 1);
			}
		}

		if (this.progressBar) {
			var fraction = current < this.count - 1 ? this.progress[current] : 0;
			var overall = this.count > 1 ? clamp((current + fraction) / (this.count - 1), 0, 1) : 1;
			this.progressBar.style.transform = 'scaleX(' + overall.toFixed(4) + ')';
		}

		// The sticky header leaves together with the last card instead of
		// letting the stack slide underneath it.
		if (this.head) {
			var shiftHead = 0;
			if (this.config.releaseHead && this.layout === 'overlay') {
				var lastTop = this.stackBottom - scrollY - this.lastHeight;
				shiftHead = Math.min(0, lastTop - stickyTop);
			}
			this.head.style.transform = shiftHead < -0.5 ? 'translate3d(0,' + shiftHead.toFixed(2) + 'px,0)' : '';
		}

		var now = window.performance ? window.performance.now() : Date.now();
		if (!settled || now < this.activeUntil) {
			this.frame = window.requestAnimationFrame(this.tick);
		}
	};

	Stack.prototype.setupCursor = function () {
		var self = this;
		var cursor = document.createElement('div');
		var bubble = document.createElement('span');
		var styles = window.getComputedStyle(this.root);

		cursor.className = 'avix-work-cursor';
		cursor.setAttribute('aria-hidden', 'true');
		bubble.className = 'avix-work-cursor__bubble';
		bubble.textContent = this.config.cursorText;
		cursor.appendChild(bubble);
		cursor.style.setProperty('--awc-bg', styles.getPropertyValue('--aw-accent').trim() || '#fb6007');
		cursor.style.setProperty('--awc-font', styles.getPropertyValue('--aw-font-body').trim() || 'Inter, Arial, sans-serif');
		document.body.appendChild(cursor);

		var state = { x: 0, y: 0, tx: 0, ty: 0, frame: 0, visible: false };
		var render = function () {
			state.frame = 0;
			state.x += (state.tx - state.x) * 0.22;
			state.y += (state.ty - state.y) * 0.22;
			cursor.style.transform = 'translate3d(' + state.x.toFixed(1) + 'px,' + state.y.toFixed(1) + 'px,0)';
			if (Math.abs(state.tx - state.x) > 0.2 || Math.abs(state.ty - state.y) > 0.2) {
				state.frame = window.requestAnimationFrame(render);
			}
		};
		var show = function (visible) {
			if (visible !== state.visible) {
				state.visible = visible;
				cursor.classList.toggle('is-visible', visible);
			}
		};

		this.onPointerMove = function (event) {
			var target = event.target;
			var media = target && target.closest ? target.closest('[data-aw-media]') : null;
			var card = media ? media.closest('[data-aw-card]') : null;
			var hasLink = card && card.querySelector('a[data-aw-surface]');
			if (!state.visible) {
				state.x = event.clientX;
				state.y = event.clientY;
			}
			state.tx = event.clientX;
			state.ty = event.clientY;
			show(!!(media && hasLink && !card.classList.contains('is-past')));
			if (!state.frame) {
				state.frame = window.requestAnimationFrame(render);
			}
		};
		this.onPointerLeave = function () {
			show(false);
		};
		this.onScrollCursor = function () {
			show(false);
		};

		this.stack.addEventListener('pointermove', this.onPointerMove, { passive: true });
		this.stack.addEventListener('pointerleave', this.onPointerLeave);
		window.addEventListener('scroll', this.onScrollCursor, { passive: true });
		this.cursor = cursor;
	};

	Stack.prototype.destroy = function () {
		if (!this.alive) {
			return;
		}
		this.alive = false;
		if (this.frame) {
			window.cancelAnimationFrame(this.frame);
		}
		window.removeEventListener('scroll', this.onScroll);
		window.removeEventListener('resize', this.onResize);
		window.removeEventListener('load', this.onResize);
		if (reduceMotion.removeEventListener) {
			reduceMotion.removeEventListener('change', this.onMotionChange);
		}
		if (this.resizeObserver) {
			this.resizeObserver.disconnect();
		}
		if (this.cursor) {
			this.stack.removeEventListener('pointermove', this.onPointerMove);
			this.stack.removeEventListener('pointerleave', this.onPointerLeave);
			window.removeEventListener('scroll', this.onScrollCursor);
			this.cursor.remove();
			this.cursor = null;
		}
		var index = instances.indexOf(this);
		if (index > -1) {
			instances.splice(index, 1);
		}
	};

	function mount(root) {
		if (!root || (root.__avixWork && root.__avixWork.alive)) {
			return;
		}
		// Editor re-renders replace the DOM; drop instances whose root is gone.
		instances.slice().forEach(function (instance) {
			if (!instance.root.isConnected) {
				instance.destroy();
			}
		});
		var instance = new Stack(root);
		if (instance.alive) {
			root.__avixWork = instance;
			instances.push(instance);
		}
	}

	function mountAll(scope) {
		Array.prototype.forEach.call((scope || document).querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixSelectedWork = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-selected-work.default', function ($scope) {
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
