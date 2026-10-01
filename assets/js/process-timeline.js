/*!
 * Avix Digital · Process Timeline
 * Scroll draws the line; the pixel character walks it, lights up each step
 * and sits down at the end of the line instead of disappearing.
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
		this.onResize = this.measure.bind(this);
		this.tick = this.tick.bind(this);
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
				self.measure();
			});
			this.resizeObserver.observe(this.timeline);
		}
		this.measure();
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

		if (this.mode === 'path') {
			this.length = this.path.getTotalLength();
			this.path.style.strokeDasharray = this.length + ' ' + this.length;
			// Length at which the line reaches each step's band.
			var self = this;
			this.stepLengths = this.bandTops.map(function (top) {
				return self.lengthAtY(top);
			});
		} else if (this.rail) {
			this.railHeight = this.rail.offsetHeight;
			this.stepOffsets = this.steps.map(function (step) {
				return step.offsetTop;
			});
		}
		this.wake();
	};

	// First length whose point reaches y (coarse search is plenty here).
	Timeline.prototype.lengthAtY = function (y) {
		var steps = 400;
		for (var i = 0; i <= steps; i++) {
			var length = this.length * i / steps;
			if (this.path.getPointAtLength(length).y >= y - 1) {
				return length;
			}
		}
		return this.length;
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
