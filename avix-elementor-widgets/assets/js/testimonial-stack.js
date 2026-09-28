/*!
 * Avix Digital · Testimonial Stack
 * Dependency-free. Autoplay is driven by the CSS progress animation
 * (animationend advances the slide), so pausing is just
 * animation-play-state and the bar never drifts from the timer.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-rv]';
	var reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };
	var SWIPE_DISTANCE = 70;

	function isEditMode() {
		return !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
	}

	function Slider(root) {
		var config = {};
		try {
			config = JSON.parse(root.getAttribute('data-avix-rv') || '{}') || {};
		} catch (error) {
			config = {};
		}

		this.root = root;
		this.stage = root.querySelector('[data-rv-stage]');
		this.slides = Array.prototype.slice.call(root.querySelectorAll('[data-rv-slide]'));
		this.segments = Array.prototype.slice.call(root.querySelectorAll('[data-rv-seg]'));
		this.status = root.querySelector('[data-rv-status]');
		this.toggle = root.querySelector('[data-rv-toggle]');
		this.count = this.slides.length;
		this.index = 0;
		this.pauseReasons = {};
		this.autoplay = config.autoplay !== false && !reduceMotion.matches && this.count > 1;
		this.pauseOnHover = config.pauseOnHover !== false;
		this.leaveTimers = [];
		this.alive = true;

		if (!this.stage || this.count === 0) {
			this.alive = false;
			return;
		}

		if (config.interval) {
			root.style.setProperty('--rv-interval', Math.max(2500, parseInt(config.interval, 10)) + 'ms');
		}

		this.bind();
		this.render(false);
		root.classList.add('is-ready');
		root.classList.toggle('is-autoplay', this.autoplay);
		if (!this.autoplay) {
			root.classList.add('is-user-paused');
		}
	}

	Slider.prototype.bind = function () {
		var self = this;
		var prev = this.root.querySelector('[data-rv-prev]');
		var next = this.root.querySelector('[data-rv-next]');

		if (this.count < 2) {
			[prev, next, this.toggle].forEach(function (el) {
				if (el) {
					el.hidden = true;
				}
			});
			return;
		}

		if (prev) {
			prev.addEventListener('click', function () {
				self.go(self.index - 1, -1, true);
			});
		}
		if (next) {
			next.addEventListener('click', function () {
				self.go(self.index + 1, 1, true);
			});
		}

		this.segments.forEach(function (segment, i) {
			segment.addEventListener('click', function () {
				if (i !== self.index) {
					self.go(i, i > self.index ? 1 : -1, true);
				}
			});
		});

		// Autoplay tick: the active segment finished filling.
		this.root.addEventListener('animationend', function (event) {
			if (event.animationName !== 'avix-rv-fill' || !self.autoplay) {
				return;
			}
			var active = self.segments[self.index];
			if (active && active.contains(event.target)) {
				self.go(self.index + 1, 1, false);
			}
		});

		if (this.toggle) {
			this.toggle.addEventListener('click', function () {
				var paused = !self.root.classList.contains('is-user-paused');
				self.root.classList.toggle('is-user-paused', paused);
				if (!paused && !self.autoplay) {
					self.autoplay = true;
					self.root.classList.add('is-autoplay');
					self.restartSegment();
				}
				self.setPause('user', paused);
				self.toggle.setAttribute('aria-label', paused ? self.toggle.getAttribute('data-label-play') : self.toggle.getAttribute('data-label-pause'));
			});
		}

		this.stage.addEventListener('keydown', function (event) {
			if (event.key === 'ArrowRight') {
				event.preventDefault();
				self.go(self.index + 1, 1, true);
			} else if (event.key === 'ArrowLeft') {
				event.preventDefault();
				self.go(self.index - 1, -1, true);
			}
		});

		if (this.pauseOnHover) {
			this.stage.addEventListener('pointerenter', function (event) {
				if (event.pointerType === 'mouse') {
					self.setPause('hover', true);
				}
			});
			this.stage.addEventListener('pointerleave', function () {
				self.setPause('hover', false);
			});
		}

		this.root.addEventListener('focusin', function () {
			self.setPause('focus', true);
		});
		this.root.addEventListener('focusout', function (event) {
			if (!self.root.contains(event.relatedTarget)) {
				self.setPause('focus', false);
			}
		});

		this.onVisibility = function () {
			self.setPause('hidden', document.hidden);
		};
		document.addEventListener('visibilitychange', this.onVisibility);

		// Only run while on screen so visitors never miss the first reviews.
		if ('IntersectionObserver' in window) {
			this.observer = new window.IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					self.setPause('offscreen', !entry.isIntersecting);
				});
			}, { threshold: 0.35 });
			this.observer.observe(this.stage);
		}

		this.bindDrag();
	};

	Slider.prototype.bindDrag = function () {
		var self = this;
		var state = null;

		this.stage.addEventListener('pointerdown', function (event) {
			if (event.button !== 0 || (event.target.closest && event.target.closest('a, button'))) {
				return;
			}
			var card = self.slides[self.index];
			state = { x: event.clientX, y: event.clientY, dx: 0, card: card, active: false, id: event.pointerId };
		});

		this.stage.addEventListener('pointermove', function (event) {
			if (!state || event.pointerId !== state.id) {
				return;
			}
			var dx = event.clientX - state.x;
			var dy = event.clientY - state.y;
			if (!state.active) {
				if (Math.abs(dx) < 8 || Math.abs(dx) < Math.abs(dy)) {
					if (Math.abs(dy) > 10) {
						state = null; // vertical scroll wins
					}
					return;
				}
				state.active = true;
				state.card.classList.add('is-dragging');
				try {
					self.stage.setPointerCapture(event.pointerId);
				} catch (error) {
					// Not supported: dragging still works while over the stage.
				}
				self.setPause('drag', true);
			}
			state.dx = dx;
			state.card.style.transform = 'translate3d(' + dx.toFixed(1) + 'px,' + (Math.abs(dx) * -0.05).toFixed(1) + 'px,0) rotate(' + (dx * 0.025).toFixed(2) + 'deg)';
		});

		var end = function () {
			if (!state) {
				return;
			}
			var drag = state;
			state = null;
			if (!drag.active) {
				return;
			}
			drag.card.classList.remove('is-dragging');
			drag.card.style.transform = '';
			self.setPause('drag', false);
			self.suppressClick = true;
			window.setTimeout(function () {
				self.suppressClick = false;
			}, 50);
			if (drag.dx <= -SWIPE_DISTANCE) {
				self.go(self.index + 1, 1, true);
			} else if (drag.dx >= SWIPE_DISTANCE) {
				self.go(self.index - 1, -1, true);
			}
		};

		this.stage.addEventListener('pointerup', end);
		this.stage.addEventListener('pointercancel', end);
		this.stage.addEventListener('click', function (event) {
			if (self.suppressClick) {
				event.preventDefault();
				event.stopPropagation();
			}
		}, true);
	};

	Slider.prototype.setPause = function (reason, on) {
		if (on) {
			this.pauseReasons[reason] = true;
		} else {
			delete this.pauseReasons[reason];
		}
		this.root.classList.toggle('is-paused', Object.keys(this.pauseReasons).length > 0);
	};

	Slider.prototype.go = function (target, direction, manual) {
		if (!this.alive || this.count < 2) {
			return;
		}
		if (!this.root.isConnected) {
			this.destroy();
			return;
		}

		var from = this.index;
		var to = ((target % this.count) + this.count) % this.count;
		if (to === from) {
			return;
		}
		this.index = to;

		var incoming = this.slides[to];
		var outgoing = this.slides[from];
		var motion = !reduceMotion.matches;

		if (motion && direction > 0) {
			// Park the new card above the pile, then let it land.
			incoming.classList.remove('is-leaving', 'is-landing');
			incoming.classList.add('is-entering');
			void incoming.offsetWidth; // commit the start pose
			incoming.classList.remove('is-entering');
			incoming.classList.add('is-landing');
			this.later(function () {
				incoming.classList.remove('is-landing');
			}, 1050);
		} else if (motion && direction < 0) {
			outgoing.classList.remove('is-landing');
			outgoing.classList.add('is-leaving');
			this.later(function () {
				outgoing.classList.remove('is-leaving');
			}, 900);
		}

		this.render(manual);
	};

	Slider.prototype.later = function (fn, delay) {
		this.leaveTimers.push(window.setTimeout(fn, delay));
	};

	Slider.prototype.render = function (announce) {
		var self = this;

		this.slides.forEach(function (slide, i) {
			var depth = (self.index - i + self.count) % self.count;
			var isActive = depth === 0;
			slide.setAttribute('data-depth', String(Math.min(depth, 3)));
			slide.setAttribute('aria-hidden', String(!isActive));
			if ('inert' in slide) {
				slide.inert = !isActive;
			} else {
				Array.prototype.forEach.call(slide.querySelectorAll('a, button'), function (control) {
					control.tabIndex = isActive ? 0 : -1;
				});
			}
		});

		this.segments.forEach(function (segment, i) {
			segment.classList.toggle('is-done', i < self.index);
			segment.classList.remove('is-active');
			segment.setAttribute('aria-current', i === self.index ? 'true' : 'false');
		});
		this.restartSegment();

		if (announce && this.status) {
			this.status.textContent = this.status.getAttribute('data-template').replace('%1$s', this.index + 1).replace('%2$s', this.count);
		}
	};

	Slider.prototype.restartSegment = function () {
		var active = this.segments[this.index];
		if (!active) {
			return;
		}
		active.classList.remove('is-active');
		void active.offsetWidth; // restart the fill animation
		active.classList.add('is-active');
	};

	Slider.prototype.destroy = function () {
		if (!this.alive) {
			return;
		}
		this.alive = false;
		this.leaveTimers.forEach(window.clearTimeout);
		document.removeEventListener('visibilitychange', this.onVisibility);
		if (this.observer) {
			this.observer.disconnect();
		}
	};

	function mount(root) {
		if (!root || (root.__avixRv && root.__avixRv.alive)) {
			return;
		}
		var slider = new Slider(root);
		if (slider.alive) {
			root.__avixRv = slider;
			if (isEditMode()) {
				// Keep the editor calm: no autoplay while designing.
				slider.setPause('editor', true);
			}
		}
	}

	function mountAll(scope) {
		Array.prototype.forEach.call((scope || document).querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixTestimonialStack = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-testimonial-stack.default', function ($scope) {
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
