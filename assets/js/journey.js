/*!
 * Avix Digital · Journey
 * Scroll walks the pixel character along the milestone track: it pauses on
 * each milestone, lights the ones it has passed and plants a flag at the end.
 * One rAF loop runs only while something moves, and scrolling elsewhere on
 * the page never wakes it while the journey is off screen.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-jr]';
	var reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };
	var finePointer = window.matchMedia ? window.matchMedia('(hover: hover) and (pointer: fine)') : { matches: false };
	var instances = [];

	// Share of each stretch between two milestones spent standing still at
	// either end, so the character visibly stops at every milestone.
	var DWELL = 0.22;

	// The character counts as walking above this speed (px/s), and is set
	// down exactly on its target once closer than SNAP px. SNAP stays far
	// below what one frame at walking speed covers, so arriving never reads
	// as a fresh step (which would cut the cheer short and twitch the legs).
	var WALK_SPEED = 15;
	var SNAP = 0.2;

	function clamp(value, min, max) {
		return value < min ? min : value > max ? max : value;
	}

	function isEditMode() {
		return !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
	}

	function Journey(root) {
		var config = {};
		try {
			config = JSON.parse(root.getAttribute('data-avix-jr') || '{}') || {};
		} catch (error) {
			config = {};
		}

		this.root = root;
		this.alive = true;
		this.config = config;
		this.track = root.querySelector('[data-jr-track]');
		this.items = Array.prototype.slice.call(root.querySelectorAll('[data-jr-item]'));
		this.nodes = this.items.map(function (item) {
			return item.querySelector('[data-jr-node]');
		});
		this.fill = root.querySelector('[data-jr-fill]');
		this.rider = root.querySelector('[data-jr-rider]');
		this.pal = this.rider ? this.rider.querySelector('.avix-pal') : null;
		this.end = root.querySelector('[data-jr-end]');
		this.editor = isEditMode();

		this.points = [];
		this.pos = null;
		this.frame = 0;
		this.lastTime = 0;
		this.activeUntil = 0;
		this.walkUntil = 0;
		this.walking = false;
		this.facingLeft = false;
		this.up = null;
		this.reached = -2;
		this.done = null;
		this.visible = false;
		this.greeted = false;
		this.inAt = 0;
		this.timers = [];
		this.measureFrame = 0;

		if (!this.track || !this.items.length) {
			this.alive = false;
			return;
		}

		var self = this;
		// Scrolling only matters while the journey is (nearly) on screen; the
		// view observer wakes the loop again as it comes back into view.
		this.onScroll = function () {
			if (self.visible || self.pos === null) {
				self.wake();
			}
		};
		// The ResizeObserver re-measures layout changes; a viewport resize alone
		// (e.g. a phone's toolbar) only changes the scroll progress.
		this.onResize = window.ResizeObserver ? this.onScroll : this.measure.bind(this);
		this.onVisibility = this.syncPause.bind(this);
		this.tick = this.tick.bind(this);
		this.init();
	}

	Journey.prototype.init = function () {
		var self = this;
		this.root.classList.add('is-live');
		if (this.editor) {
			this.root.classList.add('is-editor');
		}

		this.observeReveal();
		this.observeView();
		this.bindLook();

		window.addEventListener('scroll', this.onScroll, { passive: true });
		window.addEventListener('resize', this.onResize, { passive: true });
		document.addEventListener('visibilitychange', this.onVisibility);
		if (window.ResizeObserver) {
			// Card heights change as images load and fonts swap: re-measure. The
			// border box and the character are watched too, because the Character
			// size control only moves the track's top padding and the sprite.
			// Measured on the next frame: measure() can resize the track (title
			// heights), which inside the callback would be a resize loop.
			this.resizeObserver = new window.ResizeObserver(function () {
				if (!self.measureFrame) {
					self.measureFrame = window.requestAnimationFrame(function () {
						self.measureFrame = 0;
						self.measure();
					});
				}
			});
			this.resizeObserver.observe(this.track, { box: 'border-box' });
			if (this.pal) {
				this.resizeObserver.observe(this.pal);
			}
		}
		if (document.fonts && document.fonts.ready && typeof document.fonts.ready.then === 'function') {
			// A font swap can make titles shorter without resizing the track.
			document.fonts.ready.then(function () {
				self.measure();
			});
		}
		this.measure();
	};

	// Static journeys (walk off, reduced motion, the editor) show the end state.
	Journey.prototype.isStatic = function () {
		return !this.config.walk || reduceMotion.matches || this.editor;
	};

	Journey.prototype.later = function (fn, ms) {
		var self = this;
		var id = window.setTimeout(function () {
			self.timers.splice(self.timers.indexOf(id), 1);
			if (self.alive) {
				fn();
			}
		}, ms);
		this.timers.push(id);
		return id;
	};

	/*
	 * Reveal and greeting key on an edge crossing a line, never on an area
	 * ratio: a long journey can be taller than the screen divided by the
	 * threshold, and would then never count as visible.
	 */
	Journey.prototype.observeReveal = function () {
		var root = this.root;
		var self = this;
		if (!('IntersectionObserver' in window) || reduceMotion.matches || this.editor) {
			return;
		}
		root.classList.add('is-armed');
		this.revealObserver = new window.IntersectionObserver(function (entries, observer) {
			if (!entries[entries.length - 1].isIntersecting) {
				return;
			}
			root.classList.add('is-in');
			self.inAt = window.performance.now();
			observer.disconnect();
			self.later(function () {
				root.classList.add('is-settled');
			}, 1500 + self.items.length * 90);
		}, { threshold: 0, rootMargin: '0px 0px -15% 0px' });
		this.revealObserver.observe(root);
	};

	/*
	 * The character's loops pause off screen. It says hi the first time the
	 * track is well into view (past the lower third), before the walk starts.
	 * Editor re-renders build a new instance on every change: no replays there.
	 */
	Journey.prototype.observeView = function () {
		var self = this;
		if (!('IntersectionObserver' in window)) {
			this.visible = true;
			return;
		}
		this.viewObserver = new window.IntersectionObserver(function (entries) {
			self.visible = entries[entries.length - 1].isIntersecting;
			self.syncPause();
			if (self.visible) {
				self.wake();
			}
		}, { rootMargin: '80px 0px' });
		this.viewObserver.observe(this.root);

		if (this.pal && this.config.hi && !this.editor) {
			this.greetObserver = new window.IntersectionObserver(function (entries, observer) {
				if (!entries[entries.length - 1].isIntersecting) {
					return;
				}
				observer.disconnect();
				// Wait for the character to fade in with the entrance, if it is still running.
				var wait = 150;
				if (self.root.classList.contains('is-armed')) {
					wait = self.inAt ? Math.max(150, self.inAt + 1000 - window.performance.now()) : 1100;
				}
				self.later(function () {
					self.greet();
				}, wait);
			}, { threshold: 0, rootMargin: '0px 0px -35% 0px' });
			this.greetObserver.observe(this.track);
		}
	};

	Journey.prototype.syncPause = function () {
		this.root.classList.toggle('is-off', !this.visible || document.hidden);
	};

	Journey.prototype.greet = function () {
		if (!this.pal || !window.AvixPal || this.greeted || this.editor) {
			return;
		}
		this.greeted = true;
		// A character standing at the flag from the start (walk off, or a
		// single milestone) waves without the pixel "hi", which would run into
		// the flag.
		var still = this.isStatic() || this.points.length < 2;
		// The greeting belongs at the start. If a fast scroll has already
		// carried the character past the first milestone, it skips the hi.
		if (!still && (this.reached > 0 || this.done)) {
			return;
		}
		window.AvixPal.play(this.pal, still ? 'is-wave' : 'is-hi', 1300);
	};

	// While it stands still, the character glances at the card being looked at.
	Journey.prototype.bindLook = function () {
		var self = this;
		if (!this.pal || !this.config.look || !window.AvixPal) {
			return;
		}
		this.onLook = function (event) {
			if (self.walking || (event.type === 'mouseenter' && !finePointer.matches)) {
				return;
			}
			window.AvixPal.lookAt(self.pal, event.currentTarget);
		};
		this.onLookEnd = function (event) {
			if (event.type === 'focusout' && event.currentTarget.contains(event.relatedTarget)) {
				return;
			}
			window.AvixPal.lookAt(self.pal, null);
		};
		this.items.forEach(function (item) {
			item.addEventListener('mouseenter', self.onLook);
			item.addEventListener('mouseleave', self.onLookEnd);
			item.addEventListener('focusin', self.onLook);
			item.addEventListener('focusout', self.onLookEnd);
		});
	};

	/*
	 * Node centres along the track axis, measured with offsets so the cards'
	 * entrance transforms never skew them. Items are positioned; the list is
	 * not, so every item's offsetParent is the track.
	 */
	Journey.prototype.measure = function () {
		if (!this.alive) {
			return;
		}
		if (!this.root.isConnected) {
			this.destroy();
			return;
		}
		var axis = (window.getComputedStyle(this.track).getPropertyValue('--jr-axis') || 'y').trim();
		var horizontal = axis === 'x';
		var points = [];

		this.levelNames(horizontal);
		this.alignNodes(horizontal);

		var cross = 0;
		var nodeSize = 0;

		for (var i = 0; i < this.items.length; i++) {
			var item = this.items[i];
			var node = this.nodes[i];
			if (!node) {
				continue;
			}
			var x = item.offsetLeft + node.offsetLeft + node.offsetWidth / 2;
			var y = item.offsetTop + node.offsetTop + node.offsetHeight / 2;
			points.push(horizontal ? x : y);
			if (!i) {
				cross = horizontal ? y : x;
				nodeSize = node.offsetWidth;
			}
		}
		if (!points.length || !nodeSize) {
			return;
		}

		this.horizontal = horizontal;
		this.points = points;
		this.cross = cross;
		this.nodeSize = nodeSize;
		this.palW = this.pal ? this.pal.offsetWidth : 0;
		this.palH = this.pal ? this.pal.offsetHeight : 0;

		var first = points[0];
		var last = points[points.length - 1];
		var tailEnd = '';
		if (this.end) {
			var endNode = this.end.querySelector('.avix-jr__end-node');
			if (endNode) {
				tailEnd = horizontal
					? this.end.offsetLeft + endNode.offsetLeft + endNode.offsetWidth / 2
					: this.end.offsetTop + endNode.offsetTop + endNode.offsetHeight / 2;
			}
		}
		this.track.style.setProperty('--jr-a', first.toFixed(1) + 'px');
		this.track.style.setProperty('--jr-b', last.toFixed(1) + 'px');
		if (tailEnd !== '') {
			this.track.style.setProperty('--jr-c', tailEnd.toFixed(1) + 'px');
		}

		// A fresh layout re-places the character without a walk across the page.
		this.pos = null;
		this.wake();
	};

	/*
	 * In the row, every milestone title reserves the height of the tallest one,
	 * so the stories start level without a dead band when no title wraps.
	 * One write, one read pass, one write.
	 */
	Journey.prototype.levelNames = function (horizontal) {
		var names = horizontal ? this.root.querySelectorAll('.avix-jr__name') : [];
		if (!names.length) {
			this.track.style.removeProperty('--jr-name-h');
			return;
		}
		this.track.style.setProperty('--jr-name-h', '0px');
		var tallest = 0;
		for (var i = 0; i < names.length; i++) {
			tallest = Math.max(tallest, names[i].offsetHeight);
		}
		this.track.style.setProperty('--jr-name-h', tallest + 'px');
	};

	/*
	 * On the vertical track each pixel sits level with its card's label (or,
	 * without a label, the first line of the card). CSS places it from the
	 * label size token; this also follows a custom line height or a centred
	 * card. The row layout clears it. All reads come first, then all writes.
	 */
	Journey.prototype.alignNodes = function (horizontal) {
		var tops = [];
		var i;
		for (i = 0; i < this.items.length; i++) {
			tops[i] = null;
			var node = this.nodes[i];
			if (!node || horizontal) {
				continue;
			}
			var card = this.items[i].querySelector('.avix-jr__card');
			var anchor = this.items[i].querySelector('.avix-jr__label') || this.items[i].querySelector('.avix-jr__body > *');
			if (!card || !anchor || anchor.offsetParent !== card) {
				continue;
			}
			var height = anchor.offsetHeight;
			if (!anchor.classList.contains('avix-jr__label')) {
				// Level with the first line of a title or text.
				var lineHeight = parseFloat(window.getComputedStyle(anchor).lineHeight);
				if (lineHeight > 0) {
					height = Math.min(height, lineHeight);
				}
			}
			tops[i] = card.offsetTop + card.clientTop + anchor.offsetTop + height / 2 - node.offsetHeight / 2;
		}
		for (i = 0; i < this.items.length; i++) {
			if (!this.nodes[i]) {
				continue;
			}
			if (tops[i] === null) {
				this.nodes[i].style.removeProperty('top');
			} else {
				this.nodes[i].style.top = tops[i].toFixed(1) + 'px';
			}
		}
	};

	// Scroll position → 0..1 progress through the journey.
	Journey.prototype.progress = function () {
		if (this.isStatic() || this.points.length < 2) {
			return 1;
		}
		var rect = this.track.getBoundingClientRect();
		var vh = window.innerHeight || document.documentElement.clientHeight;
		var scroller = document.scrollingElement || document.documentElement;
		var room = Math.max(0, scroller.scrollHeight - vh - (window.pageYOffset || scroller.scrollTop || 0));
		var self = this;
		function at(top) {
			if (self.horizontal) {
				// Starts once the track is past the lower third, after the hi,
				// and ends while the line is still well below a fixed header.
				return (vh * 0.62 - top) / Math.max(1, vh * 0.44);
			}
			// Vertical: the character follows a reading line just below centre.
			var first = self.points[0];
			var last = self.points[self.points.length - 1];
			return (vh * 0.62 - (top + first)) / Math.max(1, last - first);
		}
		// Near the bottom of a page the scroll may run out before the end:
		// stretch the walk so it always finishes at the furthest scroll.
		var reachable = at(rect.top - room);
		var progress = at(rect.top);
		if (reachable < 1 && reachable > 0.05) {
			progress = progress / reachable;
		}
		return clamp(progress, 0, 1);
	};

	// Progress → position, holding still around every milestone.
	Journey.prototype.placeFor = function (progress) {
		var points = this.points;
		if (points.length < 2) {
			return points[0] || 0;
		}
		var span = progress * (points.length - 1);
		var index = Math.min(points.length - 2, Math.floor(span));
		var t = clamp((span - index - DWELL) / (1 - 2 * DWELL), 0, 1);
		t = t * t * (3 - 2 * t);
		return points[index] + (points[index + 1] - points[index]) * t;
	};

	Journey.prototype.wake = function () {
		if (!this.alive) {
			return;
		}
		this.activeUntil = window.performance.now() + 500;
		if (!this.frame) {
			this.lastTime = 0;
			this.frame = window.requestAnimationFrame(this.tick);
		}
	};

	Journey.prototype.tick = function (time) {
		this.frame = 0;
		if (!this.root.isConnected) {
			this.destroy();
			return;
		}
		if (!this.points.length) {
			return;
		}

		var dt = this.lastTime ? Math.min(0.1, (time - this.lastTime) / 1000) : 1 / 60;
		this.lastTime = time;

		// Read first, then write.
		var progress = this.progress();
		var target = this.placeFor(progress);
		var previous = this.pos;

		var step = 0;
		if (previous === null || this.isStatic()) {
			this.pos = target;
		} else {
			// dt-based easing keeps 120 Hz screens matching 60 Hz ones. Walking
			// is decided from the eased step, before the final snap.
			step = (target - this.pos) * (1 - Math.exp(-dt / 0.14));
			this.pos += step;
			if (Math.abs(target - this.pos) < SNAP) {
				this.pos = target;
			}
		}

		if (Math.abs(step) > WALK_SPEED * dt) {
			this.walkUntil = time + 140;
			if (this.horizontal) {
				this.facingLeft = step < 0;
			}
		}

		this.render(progress, time);

		if (this.pos !== target || time < this.activeUntil || time < this.walkUntil) {
			this.frame = window.requestAnimationFrame(this.tick);
		}
	};

	Journey.prototype.render = function (progress, time) {
		var points = this.points;
		var first = points[0];
		var last = points[points.length - 1];
		var pos = this.pos;
		var half = this.nodeSize / 2;
		var ratio = last > first ? clamp((pos - first) / (last - first), 0, 1) : 1;

		var fill = (this.horizontal ? 'scaleX(' : 'scaleY(') + ratio.toFixed(4) + ')';
		if (this.fill && fill !== this.lastFill) {
			this.lastFill = fill;
			this.fill.style.transform = fill;
		}

		if (this.rider) {
			var x;
			var y;
			if (this.horizontal) {
				// Feet on the line; JS lifts it onto each pixel it stands on.
				x = pos - this.palW / 2;
				y = this.cross - 1 - this.palH;
			} else {
				x = this.cross - this.palW / 2;
				y = pos - half - this.palH;
			}
			var place = 'translate3d(' + x.toFixed(1) + 'px,' + y.toFixed(1) + 'px,0)';
			if (place !== this.lastPlace) {
				this.lastPlace = place;
				this.rider.style.transform = place;
			}

			var nearest = Infinity;
			for (var n = 0; n < points.length; n++) {
				nearest = Math.min(nearest, Math.abs(points[n] - pos));
			}
			var up = this.horizontal && nearest < half + this.palW * 0.2;
			if (up !== this.up) {
				this.up = up;
				this.rider.classList.toggle('is-up', up);
			}
		}

		// Milestones reached so far.
		var reached = -1;
		for (var i = 0; i < points.length; i++) {
			if (pos >= points[i] - 1) {
				reached = i;
			}
		}
		if (reached !== this.reached) {
			this.reached = reached;
			// The hi belongs to the start: it never rides on to the flag.
			if (reached > 0 && this.pal && !this.isStatic()) {
				this.pal.classList.remove('is-hi');
			}
			for (var j = 0; j < this.items.length; j++) {
				this.items[j].classList.toggle('is-reached', j <= reached);
				this.items[j].classList.toggle('is-current', j === reached);
			}
		}

		// Arrival first, so leaving the flag again also ends the cheer.
		var done = Math.abs(pos - last) < 0.75 && (progress >= 0.995 || (this.done && progress > 0.96));
		if (done !== this.done) {
			var arrived = done && this.done === false;
			this.done = done;
			this.root.classList.toggle('is-done', done);
			if (!done && this.pal) {
				this.pal.classList.remove('is-cheer');
			}
			if (arrived && this.pal && this.config.cheer && window.AvixPal) {
				this.pal.classList.remove('is-hi');
				window.AvixPal.play(this.pal, 'is-cheer', 1100);
			}
		}

		this.setWalking(time < this.walkUntil);
	};

	Journey.prototype.setWalking = function (walking) {
		if (!this.pal) {
			return;
		}
		var face = this.horizontal && this.facingLeft;
		if (walking !== this.walking) {
			this.walking = walking;
			if (walking) {
				this.pal.classList.remove('is-look-l', 'is-look-r');
				// The cheer at the flag plays out in full.
				if (!this.done) {
					this.pal.classList.remove('is-cheer');
				}
			}
			this.pal.classList.toggle(this.horizontal ? 'is-walk' : 'is-climb', walking);
			this.pal.classList.remove(this.horizontal ? 'is-climb' : 'is-walk');
		}
		this.pal.classList.toggle('is-face-l', !!face);
	};

	Journey.prototype.destroy = function () {
		if (!this.alive) {
			return;
		}
		var self = this;
		this.alive = false;
		window.removeEventListener('scroll', this.onScroll);
		window.removeEventListener('resize', this.onResize);
		document.removeEventListener('visibilitychange', this.onVisibility);
		[this.resizeObserver, this.revealObserver, this.viewObserver, this.greetObserver].forEach(function (observer) {
			if (observer) {
				observer.disconnect();
			}
		});
		if (this.onLook) {
			this.items.forEach(function (item) {
				item.removeEventListener('mouseenter', self.onLook);
				item.removeEventListener('mouseleave', self.onLookEnd);
				item.removeEventListener('focusin', self.onLook);
				item.removeEventListener('focusout', self.onLookEnd);
			});
		}
		this.timers.forEach(function (id) {
			window.clearTimeout(id);
		});
		this.timers = [];
		if (this.frame) {
			window.cancelAnimationFrame(this.frame);
			this.frame = 0;
		}
		if (this.measureFrame) {
			window.cancelAnimationFrame(this.measureFrame);
			this.measureFrame = 0;
		}
	};

	function mount(root) {
		if (!root || (root.__avixJr && root.__avixJr.alive)) {
			return;
		}
		// Editor re-renders replace the DOM: drop instances whose root is gone.
		instances = instances.filter(function (instance) {
			if (!instance.root.isConnected) {
				instance.destroy();
				return false;
			}
			return instance.alive;
		});
		var journey = new Journey(root);
		if (journey.alive) {
			root.__avixJr = journey;
			instances.push(journey);
		}
	}

	function mountAll(scope) {
		var base = scope || document;
		if (base.matches && base.matches(ROOT_SELECTOR)) {
			mount(base);
		}
		Array.prototype.forEach.call(base.querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixJourney = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-journey.default', function ($scope) {
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
