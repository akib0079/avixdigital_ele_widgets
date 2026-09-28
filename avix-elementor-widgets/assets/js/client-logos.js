/*!
 * Avix Digital · Client Logos
 * The logo belt moves by script (not CSS) so it can ease to a stop and so
 * the pixel character can ride a real tile: it walks against the belt, hops
 * forward to stay in view, and jumps to any logo you hover, focus or tap to
 * tell its story in a speech bubble. Runs only while on screen.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-cl]';
	var reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };
	var touchOnly = window.matchMedia ? window.matchMedia('(hover: none)') : { matches: false };

	function clamp(value, min, max) {
		return value < min ? min : value > max ? max : value;
	}

	function easeInOut(t) {
		return t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;
	}

	function Belt(root) {
		var config = {};
		try {
			config = JSON.parse(root.getAttribute('data-avix-cl') || '{}') || {};
		} catch (error) {
			config = {};
		}

		this.root = root;
		this.alive = true;
		this.stage = root.querySelector('[data-cl-stage]');
		this.viewport = root.querySelector('[data-cl-viewport]');
		this.track = root.querySelector('[data-cl-track]');
		this.set = root.querySelector('[data-cl-set]');
		this.beltLine = root.querySelector('[data-cl-belt]');
		this.buddy = root.querySelector('[data-cl-buddy]');
		this.bubble = root.querySelector('[data-cl-bubble]');
		this.toggle = root.querySelector('[data-cl-toggle]');
		this.story = config.story !== false;
		this.speed = reduceMotion.matches ? 0 : clamp(isFinite(parseFloat(config.speed)) ? parseFloat(config.speed) : 36, 0, 200);
		this.intro = touchOnly.matches ? (config.introTouch || '') : (config.intro || '');
		this.linkText = config.linkText || 'See the case study';

		this.x = 0;
		this.v = 0;
		this.pauses = {};
		this.inView = false;
		this.frame = 0;
		this.lastTime = 0;
		this.rider = { state: 'ride', idx: 0, to: 0, t: 0, fromX: 0, dur: 0.5, arc: 60 };
		this.focusIdx = -1;
		this.tapIdx = -1;
		this.bubbleMode = '';
		this.timers = [];

		if (!this.viewport || !this.track || !this.set) {
			this.alive = false;
			return;
		}

		this.tick = this.tick.bind(this);
		this.init();
	}

	Belt.prototype.init = function () {
		var self = this;
		this.build();

		// Hover / focus / tap tell a tile's story.
		this.viewport.addEventListener('pointerover', function (event) {
			if (event.pointerType === 'mouse') {
				var tile = event.target.closest && event.target.closest('[data-cl-tile]');
				if (tile) {
					self.present(self.tiles.indexOf(tile));
				}
			}
		});
		this.stage.addEventListener('pointerleave', function (event) {
			if (event.pointerType === 'mouse') {
				self.dismiss();
			}
		});
		this.viewport.addEventListener('focusin', function (event) {
			var tile = event.target.closest && event.target.closest('[data-cl-tile]');
			if (tile) {
				self.present(self.tiles.indexOf(tile));
			}
		});
		this.stage.addEventListener('focusout', function (event) {
			if (!self.stage.contains(event.relatedTarget)) {
				self.dismiss();
			}
		});
		// Touch: first tap tells the story, a second tap on the same logo follows its link.
		// Mouse clicks and keyboard Enter follow links straight away.
		this.viewport.addEventListener('pointerdown', function (event) {
			self.lastPointer = event.pointerType;
		}, { passive: true });
		this.viewport.addEventListener('click', function (event) {
			var tile = event.target.closest && event.target.closest('[data-cl-tile]');
			// The minimal marquee has no story to tell: links just open.
			if (!tile || !self.story) {
				return;
			}
			var index = self.tiles.indexOf(tile);
			var touch = event.detail > 0 && !!self.lastPointer && self.lastPointer !== 'mouse';
			if (tile.getAttribute('href') && !(touch && index !== self.tapIdx)) {
				return;
			}
			event.preventDefault();
			self.present(index);
			if (touch) {
				self.tapIdx = index;
				window.clearTimeout(self.tapTimer);
				self.tapTimer = window.setTimeout(function () {
					if (self.focusIdx === index) {
						self.dismiss();
					}
				}, 6000);
			}
		});
		document.addEventListener('pointerdown', function (event) {
			if (event.pointerType !== 'mouse' && self.focusIdx > -1 && !self.stage.contains(event.target)) {
				self.dismiss();
			}
		}, { passive: true });

		if (this.toggle) {
			this.toggle.addEventListener('click', function () {
				var paused = !self.root.classList.contains('is-user-paused');
				self.root.classList.toggle('is-user-paused', paused);
				self.setPause('user', paused);
				self.toggle.setAttribute('aria-label', self.toggle.getAttribute(paused ? 'data-label-play' : 'data-label-pause'));
			});
		}

		document.addEventListener('visibilitychange', function () {
			self.setPause('hidden', document.hidden);
			self.wake();
		});

		if ('IntersectionObserver' in window) {
			new window.IntersectionObserver(function (entries) {
				var visible = entries[entries.length - 1].isIntersecting;
				self.inView = visible;
				if (visible) {
					self.showIntro();
					self.wake();
				}
			}, { threshold: 0.25 }).observe(this.stage);
		} else {
			this.inView = true;
		}

		if (window.ResizeObserver) {
			var width = this.viewport.clientWidth;
			new window.ResizeObserver(function () {
				if (Math.abs(self.viewport.clientWidth - width) > 2) {
					width = self.viewport.clientWidth;
					self.build();
				}
			}).observe(this.viewport);
		}

		Array.prototype.forEach.call(this.set.querySelectorAll('img'), function (image) {
			if (!image.complete) {
				image.addEventListener('load', function () {
					self.build();
				}, { once: true });
			}
		});

		this.v = this.speed;
		this.root.classList.add('is-ready');
		this.wake();
	};

	// Clone the logo set until the belt covers the viewport twice, so it loops seamlessly.
	Belt.prototype.build = function () {
		Array.prototype.forEach.call(this.track.querySelectorAll('[data-cl-clone]'), function (clone) {
			clone.remove();
		});

		var gap = parseFloat(window.getComputedStyle(this.track).columnGap) || 0;
		this.vw = this.viewport.clientWidth;
		this.setW = this.set.getBoundingClientRect().width + gap;
		this.perSet = this.set.querySelectorAll('[data-cl-tile]').length;
		var copies = this.speed > 0 ? Math.max(2, Math.ceil((this.vw * 2) / Math.max(1, this.setW)) + 1) : 1;

		for (var i = 1; i < copies; i++) {
			var clone = this.set.cloneNode(true);
			clone.removeAttribute('data-cl-set');
			clone.setAttribute('data-cl-clone', '');
			clone.setAttribute('aria-hidden', 'true');
			Array.prototype.forEach.call(clone.querySelectorAll('a, button'), function (control) {
				control.tabIndex = -1;
			});
			this.track.appendChild(clone);
		}

		this.tiles = Array.prototype.slice.call(this.track.querySelectorAll('[data-cl-tile]'));
		this.centers = this.tiles.map(function (tile) {
			return tile.offsetLeft + tile.offsetWidth / 2;
		});
		this.tileTop = this.tiles.length ? this.tiles[0].offsetTop : 0;

		// Static belt (reduced motion or speed 0): centre the row instead.
		if (this.speed === 0) {
			this.x = Math.max(0, (this.vw - this.setW + gap) / 2);
		}

		this.rider.state = 'ride';
		this.rider.idx = this.nearest(this.vw * 0.55);
		this.focusIdx = -1;
		this.tapIdx = -1;
		this.wake();
	};

	Belt.prototype.center = function (index) {
		return (this.centers[index] || 0) + this.x;
	};

	Belt.prototype.nearest = function (x) {
		var best = 0;
		var bestDistance = Infinity;
		for (var i = 0; i < this.tiles.length; i++) {
			var distance = Math.abs(this.center(i) - x);
			if (distance < bestDistance) {
				best = i;
				bestDistance = distance;
			}
		}
		return best;
	};

	Belt.prototype.later = function (fn, delay) {
		this.timers.push(window.setTimeout(fn, delay));
	};

	Belt.prototype.setPause = function (reason, on) {
		if (on) {
			this.pauses[reason] = true;
		} else {
			delete this.pauses[reason];
		}
		this.wake();
	};

	Belt.prototype.hopTo = function (index) {
		if (!this.buddy) {
			return;
		}
		var r = this.rider;
		var fromX = r.state === 'hop' ? this.buddyX : this.center(r.idx);
		var distance = Math.abs(this.center(index) - fromX);
		r.state = 'hop';
		r.to = index;
		r.t = 0;
		r.fromX = fromX;
		r.dur = reduceMotion.matches ? 0.001 : clamp(0.42 + distance / 1500, 0.42, 0.95);
		r.arc = clamp(38 + distance * 0.12, 38, 120);
		if (this.buddy) {
			this.buddy.classList.add('is-hop');
			this.buddy.classList.remove('is-land');
		}
		this.wake();
	};

	// Next tile just past the middle of the belt.
	Belt.prototype.nextIdx = function () {
		for (var i = this.rider.idx + 1; i < this.tiles.length; i++) {
			if (this.center(i) > this.vw * 0.5) {
				return i;
			}
		}
		return Math.min(this.tiles.length - 1, this.rider.idx + 1);
	};

	Belt.prototype.present = function (index) {
		if (index < 0 || index === this.focusIdx) {
			return;
		}
		if (this.focusIdx > -1 && this.tiles[this.focusIdx]) {
			this.tiles[this.focusIdx].classList.remove('is-active');
		}
		this.focusIdx = index;
		this.tiles[index].classList.add('is-active');
		this.setPause('present', true);
		this.bubbleMode = 'client';
		this.fillBubble(this.tiles[index]);

		if (!this.buddy) {
			// No character: the bubble opens straight above the logo.
			if (this.bubble) {
				this.openBubble();
			}
			return;
		}
		if (this.rider.state === 'ride' && this.rider.idx === index) {
			this.openBubble();
		} else {
			this.closeBubble();
			this.hopTo(index);
		}
		this.buddy.classList.add('is-talk');
	};

	Belt.prototype.dismiss = function () {
		if (this.focusIdx > -1 && this.tiles[this.focusIdx]) {
			this.tiles[this.focusIdx].classList.remove('is-active');
		}
		this.focusIdx = -1;
		this.tapIdx = -1;
		if (this.bubbleMode === 'client') {
			this.closeBubble();
		}
		if (this.buddy) {
			this.buddy.classList.remove('is-talk');
		}
		this.setPause('present', false);
	};

	Belt.prototype.fillBubble = function (tile) {
		if (!this.bubble) {
			return;
		}
		var name = this.bubble.querySelector('[data-cl-bname]');
		var meta = this.bubble.querySelector('[data-cl-bmeta]');
		var link = this.bubble.querySelector('[data-cl-blink]');
		var project = tile.getAttribute('data-project') || '';
		var country = tile.getAttribute('data-country') || '';
		var href = tile.getAttribute('href') || '';
		name.textContent = tile.getAttribute('data-name') || '';
		meta.textContent = [project, country].filter(Boolean).join(' · ');
		meta.hidden = !meta.textContent;
		if (link) {
			link.hidden = !href;
			if (href) {
				link.setAttribute('href', href);
				link.setAttribute('target', tile.getAttribute('target') || '_self');
				link.querySelector('span').textContent = this.linkText;
			}
		}
	};

	Belt.prototype.showIntro = function () {
		if (this.introShown || !this.intro || !this.bubble || !this.buddy) {
			return;
		}
		this.introShown = true;
		var self = this;
		this.later(function () {
			if (self.focusIdx > -1) {
				return;
			}
			var name = self.bubble.querySelector('[data-cl-bname]');
			var meta = self.bubble.querySelector('[data-cl-bmeta]');
			var link = self.bubble.querySelector('[data-cl-blink]');
			name.textContent = self.intro;
			meta.hidden = true;
			if (link) {
				link.hidden = true;
			}
			self.bubbleMode = 'intro';
			self.openBubble();
			self.later(function () {
				if (self.bubbleMode === 'intro') {
					self.closeBubble();
				}
			}, 4200);
		}, 900);
	};

	Belt.prototype.openBubble = function () {
		if (this.bubble) {
			// Jump to the new spot while hidden, so the bubble pops in there instead of sliding over.
			if (!this.bubble.classList.contains('is-open')) {
				this.bubble.style.transition = 'none';
				if (this.buddy) {
					this.placeBubble(this.buddyX || 0, this.rider.state === 'ride' && this.rider.idx === this.focusIdx ? -4 : 0);
				} else if (this.focusIdx > -1) {
					this.placeBubble(this.center(this.focusIdx), -4);
				}
				void this.bubble.offsetWidth;
				this.bubble.style.transition = '';
			}
			this.bubble.classList.add('is-open');
			this.bubble.setAttribute('aria-hidden', 'false');
			this.wake();
		}
	};

	Belt.prototype.closeBubble = function () {
		if (this.bubble) {
			this.bubble.classList.remove('is-open');
			this.bubble.setAttribute('aria-hidden', 'true');
		}
		if (this.bubbleMode === 'intro') {
			this.bubbleMode = '';
		}
	};

	Belt.prototype.wake = function () {
		if (!this.alive || this.frame) {
			return;
		}
		this.lastTime = 0;
		this.frame = window.requestAnimationFrame(this.tick);
	};

	Belt.prototype.tick = function (time) {
		this.frame = 0;
		if (!this.root.isConnected) {
			this.alive = false;
			this.timers.forEach(window.clearTimeout);
			window.clearTimeout(this.tapTimer);
			return;
		}
		if (!this.tiles.length) {
			return;
		}

		var dt = this.lastTime ? Math.min(0.05, (time - this.lastTime) / 1000) : 1 / 60;
		this.lastTime = time;

		// Belt speed eases toward its target (0 while paused or presenting).
		var paused = Object.keys(this.pauses).length > 0;
		var target = paused ? 0 : this.speed;
		this.v += (target - this.v) * (1 - Math.exp(-dt / 0.28));
		if (Math.abs(this.v - target) < 0.2) {
			this.v = target;
		}

		if (this.speed > 0) {
			this.x -= this.v * dt;
			if (this.x <= -this.setW) {
				this.x += this.setW;
				this.shiftIndices(-this.perSet);
			}
			this.track.style.transform = 'translate3d(' + this.x.toFixed(2) + 'px,0,0)';
			if (this.beltLine) {
				this.beltLine.style.setProperty('--cl-x', this.x.toFixed(1) + 'px');
			}
		} else {
			this.track.style.transform = 'translate3d(' + this.x.toFixed(2) + 'px,0,0)';
		}

		this.moveRider(dt);

		var moving = this.v > 0.2 || this.rider.state === 'hop';
		if (this.inView && !document.hidden && (moving || this.v !== target)) {
			this.frame = window.requestAnimationFrame(this.tick);
		}
	};

	Belt.prototype.shiftIndices = function (delta) {
		var shift = function (index) {
			return index >= -delta ? index + delta : index;
		};
		this.rider.idx = shift(this.rider.idx);
		this.rider.to = shift(this.rider.to);
		if (this.focusIdx > -1) {
			this.focusIdx = shift(this.focusIdx);
		}
		if (this.tapIdx > -1) {
			this.tapIdx = shift(this.tapIdx);
		}
	};

	Belt.prototype.moveRider = function (dt) {
		if (!this.buddy) {
			if (this.bubble && this.focusIdx > -1 && this.bubble.classList.contains('is-open')) {
				this.placeBubble(this.center(this.focusIdx), -4);
			}
			return;
		}
		var r = this.rider;
		var x = 0;
		var y = 0;

		if (r.state === 'ride') {
			x = this.center(r.idx);
			// The belt carried it too far left: hop forward (not while telling a story).
			if (this.focusIdx < 0 && this.speed > 0 && x < this.vw * 0.26) {
				this.hopTo(this.nextIdx());
				x = this.center(r.idx);
			}
		} else {
			r.t = Math.min(1, r.t + dt / r.dur);
			var eased = easeInOut(r.t);
			x = r.fromX + (this.center(r.to) - r.fromX) * eased;
			y = -r.arc * 4 * r.t * (1 - r.t);
			if (r.t >= 1) {
				r.state = 'ride';
				r.idx = r.to;
				this.land();
			}
		}

		// Stand on the lifted tile while it is presented.
		var lift = r.state === 'ride' && r.idx === this.focusIdx ? -4 : 0;
		this.buddyX = x;
		this.buddy.style.transform = 'translate3d(' + x.toFixed(1) + 'px,' + (y + lift).toFixed(1) + 'px,0)';
		this.buddy.classList.toggle('is-walk', r.state === 'ride' && this.v > 4);
		this.buddy.classList.toggle('is-idle', r.state === 'ride' && this.v <= 4);

		if (this.bubble && this.bubble.classList.contains('is-open')) {
			this.placeBubble(x, lift);
		}
	};

	Belt.prototype.land = function () {
		var self = this;
		this.buddy.classList.remove('is-hop');
		this.buddy.classList.add('is-land');
		this.later(function () {
			self.buddy.classList.remove('is-land');
		}, 180);
		if (this.focusIdx > -1 && this.rider.idx === this.focusIdx) {
			this.openBubble();
		}
	};

	Belt.prototype.placeBubble = function (x, lift) {
		var width = this.bubble.offsetWidth;
		var height = this.bubble.offsetHeight;
		var buddyH = this.buddy ? this.buddy.offsetHeight : 0;
		var left = clamp(x - width / 2, 12, Math.max(12, this.vw - width - 12));
		var top = this.viewport.offsetTop + this.tileTop - buddyH - 14 - height + lift;
		this.bubble.style.setProperty('--cl-bx', left.toFixed(1) + 'px');
		this.bubble.style.setProperty('--cl-by', top.toFixed(1) + 'px');
		this.bubble.style.setProperty('--cl-tail', clamp(x - left, 18, width - 18).toFixed(1) + 'px');
	};

	function mount(root) {
		if (!root || (root.__avixCl && root.__avixCl.alive)) {
			return;
		}
		var belt = new Belt(root);
		if (belt.alive) {
			root.__avixCl = belt;
		}
	}

	function mountAll(scope) {
		Array.prototype.forEach.call((scope || document).querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixClientLogos = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-client-logos.default', function ($scope) {
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
