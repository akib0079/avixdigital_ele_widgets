/*!
 * Avix Digital · Intro Text
 * Word reveal, keyword picture fans, and the pixel character on the logo:
 * it peeks over the top, hops on and sits; hovering the logo sets it rolling
 * while the character runs on top to keep its balance; a click or tap flings
 * it into a flip. One animation loop, only while something moves.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-it]';
	var ROLL_SPEED = 250; // degrees per second while hovered
	var reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };
	var finePointer = window.matchMedia ? window.matchMedia('(hover: hover) and (pointer: fine)') : { matches: true };

	function clamp(value, min, max) {
		return value < min ? min : value > max ? max : value;
	}

	function easeInOut(t) {
		return t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;
	}

	function isEditMode() {
		return !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
	}

	function Intro(root) {
		var config = {};
		try {
			config = JSON.parse(root.getAttribute('data-avix-it') || '{}') || {};
		} catch (error) {
			config = {};
		}

		this.root = root;
		this.alive = true;
		this.lines = config.lines || null;
		this.mark = root.querySelector('[data-it-mark]');
		this.logo = root.querySelector('[data-it-logo]');
		this.wheel = root.querySelector('[data-it-wheel]');
		this.buddy = root.querySelector('[data-it-buddy]');
		this.sprite = root.querySelector('[data-it-sprite]');
		this.bubble = root.querySelector('[data-it-bubble]');
		this.say = this.bubble ? this.bubble.querySelector('[data-it-say]') : null;
		this.keywords = Array.prototype.slice.call(root.querySelectorAll('[data-it-kw]'));
		this.still = reduceMotion.matches;

		this.pos = { x: 0, y: 0 };
		this.angle = 0;
		this.omega = 0;
		this.settleTo = 0;
		this.rolling = false;
		this.stopping = false;
		this.tween = null;
		this.pose = '';
		this.seated = false;
		this.wobble = 0;
		this.launches = 0;
		this.timers = [];
		this.frame = 0;
		this.visible = true;
		this.openKw = null;

		this.tick = this.tick.bind(this);

		if (config.reveal !== false && !this.still && !isEditMode() && 'IntersectionObserver' in window) {
			root.classList.add('is-armed');
		}
		this.bind();
		this.observe();
	}

	/* ---------- Setup ---------- */

	Intro.prototype.bind = function () {
		var self = this;

		if (this.logo && this.buddy) {
			this.logo.addEventListener('pointerenter', function (event) {
				if (event.pointerType === 'mouse') {
					self.startRoll();
				}
			});
			this.logo.addEventListener('pointerleave', function (event) {
				if (event.pointerType === 'mouse') {
					self.stopRoll();
				}
			});
			this.logo.addEventListener('click', function () {
				self.launch();
			});
		}

		this.keywords.forEach(function (kw) {
			kw.addEventListener('pointerenter', function (event) {
				if (event.pointerType === 'mouse') {
					self.openKeyword(kw);
				}
			});
			kw.addEventListener('pointerleave', function (event) {
				if (event.pointerType === 'mouse') {
					self.closeKeyword(kw);
				}
			});
			kw.addEventListener('focus', function () {
				self.openKeyword(kw);
			});
			kw.addEventListener('blur', function () {
				self.closeKeyword(kw);
			});
			// Touch: the first tap shows the pictures, a second tap follows a link.
			kw.addEventListener('click', function (event) {
				if (event.detail === 0 || self.lastPointer === 'mouse') {
					return;
				}
				// (A tap also focuses a link, which already opened it: track taps separately.)
				if (self.tapKw !== kw) {
					event.preventDefault();
					self.openKeyword(kw);
					self.tapKw = kw;
				} else if (!kw.getAttribute('href')) {
					self.closeKeyword(kw);
				}
			});
		});

		this.root.addEventListener('pointerdown', function (event) {
			self.lastPointer = event.pointerType;
		}, { passive: true });

		document.addEventListener('pointerdown', function (event) {
			if (self.openKw && event.pointerType !== 'mouse' && !self.openKw.contains(event.target)) {
				self.closeKeyword(self.openKw);
			}
		}, { passive: true });

		// The character glances toward the mouse.
		if (this.buddy) {
			this.root.addEventListener('pointermove', function (event) {
				if (event.pointerType !== 'mouse' || !self.seated || self.openKw || self.rolling || self.tween) {
					return;
				}
				var rect = self.mark.getBoundingClientRect();
				var dx = event.clientX - (rect.left + rect.width / 2);
				self.look(Math.abs(dx) < rect.width * 0.7 ? '' : dx < 0 ? 'l' : 'r');
			}, { passive: true });
			this.root.addEventListener('pointerleave', function () {
				if (!self.openKw) {
					self.look('');
				}
			});
		}

		if (window.ResizeObserver && this.mark) {
			var width = 0;
			new window.ResizeObserver(function () {
				var next = self.mark.offsetWidth;
				if (next !== width) {
					width = next;
					self.measure();
					if (self.seated && !self.tween) {
						self.pos = self.rolling || self.stopping ? self.standPos() : self.sitPos();
						self.render();
					}
				}
			}).observe(this.mark);
		}
	};

	Intro.prototype.observe = function () {
		var self = this;
		if (!('IntersectionObserver' in window)) {
			this.root.classList.add('is-in');
			this.play();
			return;
		}
		new window.IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				self.visible = entry.isIntersecting;
				self.root.classList.toggle('is-off', !entry.isIntersecting);
				if (entry.isIntersecting && entry.intersectionRatio >= 0.3 && !self.played) {
					self.root.classList.add('is-in');
					self.play();
				}
				if (self.visible) {
					self.wake();
				}
			});
		}, { threshold: [0, 0.3] }).observe(this.root);
	};

	/* ---------- Geometry (px, relative to the logo box) ---------- */

	Intro.prototype.measure = function () {
		this.size = this.mark ? this.mark.offsetWidth : 0;
		this.bw = this.buddy ? this.buddy.offsetWidth : 0;
		this.bh = this.buddy ? this.buddy.offsetHeight : 0;
	};

	Intro.prototype.cx = function () {
		return this.size / 2 - this.bw / 2;
	};

	// Seat (row 7 of 12) on the top of the logo, legs dangling in front.
	Intro.prototype.sitPos = function () {
		return { x: this.cx(), y: -this.bh * 7 / 12 + 1 };
	};

	Intro.prototype.standPos = function () {
		return { x: this.cx(), y: -this.bh + 1 };
	};

	// Head and hands showing over the top edge (rows 0–2 of 12).
	Intro.prototype.peekPos = function () {
		return { x: this.cx(), y: -Math.round(this.bh * 0.25) };
	};

	Intro.prototype.hiddenPos = function () {
		return { x: this.cx(), y: this.size * 0.24 };
	};

	/* ---------- Poses & speech ---------- */

	Intro.prototype.setPose = function (pose) {
		if (!this.buddy || pose === this.pose) {
			return;
		}
		this.buddy.classList.remove('is-' + this.pose);
		this.pose = pose;
		if (pose) {
			this.buddy.classList.add('is-' + pose);
		}
	};

	Intro.prototype.look = function (side) {
		if (!this.buddy) {
			return;
		}
		this.buddy.classList.toggle('is-look-l', side === 'l');
		this.buddy.classList.toggle('is-look-r', side === 'r');
	};

	Intro.prototype.point = function (side) {
		if (!this.buddy) {
			return;
		}
		this.buddy.classList.toggle('is-point-l', side === 'l');
		this.buddy.classList.toggle('is-point-r', side === 'r');
	};

	Intro.prototype.squash = function () {
		var buddy = this.buddy;
		var logo = this.logo;
		[buddy, logo].forEach(function (element) {
			if (element) {
				element.classList.remove(element === buddy ? 'is-land' : 'is-squash');
				void element.offsetWidth;
				element.classList.add(element === buddy ? 'is-land' : 'is-squash');
			}
		});
	};

	Intro.prototype.talk = function (text, duration, side) {
		if (!this.bubble || !text) {
			return;
		}
		var self = this;
		var reopen = !this.bubble.classList.contains('is-open');
		this.say.textContent = text;
		this.side = side || 'r';
		this.bubbleW = this.bubble.offsetWidth;
		this.bubbleH = this.bubble.offsetHeight;
		if (reopen) {
			this.bubble.style.transition = 'none';
		}
		this.placeBubble();
		if (reopen) {
			void this.bubble.offsetWidth;
			this.bubble.style.transition = '';
		}
		this.bubble.classList.add('is-open');
		window.clearTimeout(this.talkTimer);
		if (duration) {
			this.talkTimer = window.setTimeout(function () {
				self.hush();
			}, duration);
		}
		this.wake();
	};

	Intro.prototype.hush = function () {
		window.clearTimeout(this.talkTimer);
		if (this.bubble) {
			this.bubble.classList.remove('is-open');
		}
	};

	// Beside the character's head; flips sides to stay inside the section.
	Intro.prototype.placeBubble = function () {
		if (!this.bubble || !this.mark) {
			return;
		}
		var width = this.bubbleW || this.bubble.offsetWidth;
		var height = this.bubbleH || this.bubble.offsetHeight;
		var markRect = this.mark.getBoundingClientRect();
		var rootRect = this.root.getBoundingClientRect();
		var minX = Math.max(rootRect.left, 0) - markRect.left + 10;
		var maxX = Math.min(rootRect.right, document.documentElement.clientWidth) - markRect.left - 10;
		var x = this.pos.x + (this.wobbleX || 0);
		var right = x + this.bw + 10;
		var left = x - 10 - width;
		var side = this.side === 'l' ? 'l' : 'r';
		if (side === 'r' && right + width > maxX && left >= minX) {
			side = 'l';
		} else if (side === 'l' && left < minX && right + width <= maxX) {
			side = 'r';
		}
		var bx = side === 'r' ? right : left;
		bx = clamp(bx, minX, Math.max(minX, maxX - width));
		var by = this.pos.y + this.bh * 0.14 - height / 2;
		this.bubble.classList.toggle('is-left', side === 'l');
		this.bubble.style.setProperty('--it-bx', bx.toFixed(1) + 'px');
		this.bubble.style.setProperty('--it-by', by.toFixed(1) + 'px');
	};

	Intro.prototype.later = function (fn, delay) {
		var self = this;
		this.timers.push(window.setTimeout(function () {
			if (self.alive) {
				fn.call(self);
			}
		}, delay));
	};

	/* ---------- The entrance ---------- */

	Intro.prototype.play = function () {
		this.played = true;
		if (!this.buddy) {
			return;
		}
		this.measure();
		this.root.classList.add('is-buddy');
		var hint = finePointer.matches ? this.lines.hint : this.lines.hintTouch;

		if (this.still) {
			this.buddy.classList.add('is-front');
			this.pos = this.sitPos();
			this.setPose('sit');
			this.seated = true;
			this.render();
			this.later(function () {
				this.talk(hint, 4000);
			}, 600);
			return;
		}

		// Hidden behind the logo while it pops in…
		this.pos = this.hiddenPos();
		this.setPose('air');
		this.render();

		// …peeks over the top edge and looks around…
		this.later(function () {
			this.moveTo(this.peekPos(), { dur: 0.32 });
			this.setPose('peek');
		}, 700);
		this.later(function () {
			this.look('l');
		}, 1150);
		this.later(function () {
			this.look('r');
		}, 1550);
		this.later(function () {
			this.look('');
			this.buddy.classList.add('is-wave');
			this.talk(this.lines.peek, 1500);
		}, 1850);

		// …then hops on and sits down.
		this.later(function () {
			var self = this;
			this.buddy.classList.remove('is-wave');
			this.setPose('air');
			this.moveTo(this.sitPos(), {
				dur: 0.55,
				arc: this.size * 0.45,
				front: 0.4,
				done: function () {
					self.land();
					self.later(function () {
						self.talk(hint, 3400);
					}, 350);
				}
			});
		}, 3450);
	};

	Intro.prototype.land = function () {
		this.seated = true;
		this.squash();
		if (this.hovering && !this.still) {
			// Still hovered (or hovered during the flight): straight back to running.
			this.rolling = true;
			this.stopping = false;
			this.pos = this.standPos();
			this.setPose('run');
			if (!this.bubble || !this.bubble.classList.contains('is-open')) {
				this.talk(this.lines.roll, 0);
			}
			this.wake();
		} else {
			this.setPose('sit');
		}
		this.render();
	};

	/* ---------- Play: roll & flip ---------- */

	Intro.prototype.startRoll = function () {
		this.hovering = true;
		if (!this.seated || this.still) {
			return;
		}
		this.rolling = true;
		this.stopping = false;
		if (!this.tween) {
			this.look('');
			this.setPose('run');
			this.moveTo(this.standPos(), { dur: 0.16 });
			this.talk(this.lines.roll, 0);
		}
		this.wake();
	};

	Intro.prototype.stopRoll = function () {
		this.hovering = false;
		if (!this.rolling) {
			return;
		}
		this.rolling = false;
		this.stopping = true;
		// Settle upright on the next full turn ahead, never backwards.
		this.settleTo = Math.ceil((this.angle + Math.max(0, this.omega) * 0.3) / 360) * 360;
		this.hush();
		this.wake();
	};

	Intro.prototype.launch = function () {
		if (!this.buddy || !this.seated || !this.lines) {
			return;
		}
		var list = this.lines.launch || [];
		var line = list.length ? list[this.launches % list.length] : '';
		this.launches += 1;

		if (this.still) {
			this.talk(line, 2200);
			return;
		}
		if (this.tween && this.pose === 'air') {
			return;
		}

		var self = this;
		var home = this.rolling ? this.standPos() : this.sitPos();
		this.omega = Math.max(0, this.omega) + 1150;
		if (!this.rolling) {
			this.stopping = false;
			this.settleTo = Math.ceil((this.angle + 360 + this.omega * 0.3) / 360) * 360;
		}
		this.look('');
		this.setPose('air');
		this.squash();
		this.talk(line, 1800);
		this.moveTo(home, {
			dur: 0.82,
			arc: Math.max(60, this.size * 1.05),
			flip: this.launches % 4 === 0 ? -720 : -360,
			done: function () {
				self.land();
			}
		});
	};

	Intro.prototype.moveTo = function (to, options) {
		options = options || {};
		this.tween = {
			from: { x: this.pos.x, y: this.pos.y },
			to: to,
			t: 0,
			dur: options.dur || 0.4,
			arc: options.arc || 0,
			flip: options.flip || 0,
			front: options.front,
			done: options.done
		};
		this.wake();
	};

	/* ---------- Keywords ---------- */

	Intro.prototype.openKeyword = function (kw) {
		if (this.openKw && this.openKw !== kw) {
			this.closeKeyword(this.openKw);
		}
		this.openKw = kw;
		this.placeDeck(kw);
		kw.classList.add('is-open');
		var word = kw.closest('.avix-it__w');
		if (word) {
			word.classList.add('is-raised');
		}

		// The character looks at the keyword and talks about it, bubble on the far side.
		if (this.buddy && this.seated && !this.tween && !this.rolling) {
			var rect = this.mark.getBoundingClientRect();
			var kwRect = kw.getBoundingClientRect();
			var dx = kwRect.left + kwRect.width / 2 - (rect.left + rect.width / 2);
			var side = Math.abs(dx) < 24 ? '' : dx < 0 ? 'l' : 'r';
			this.look(side);
			this.point(side);
			var line = kw.getAttribute('data-line');
			if (line) {
				this.talk(line, 0, side === 'r' ? 'l' : 'r');
			}
		}
	};

	Intro.prototype.closeKeyword = function (kw) {
		kw.classList.remove('is-open');
		if (this.tapKw === kw) {
			this.tapKw = null;
		}
		var word = kw.closest('.avix-it__w');
		if (word) {
			var self = this;
			window.setTimeout(function () {
				if (self.openKw !== kw) {
					word.classList.remove('is-raised');
				}
			}, 300);
		}
		if (this.openKw === kw) {
			this.openKw = null;
			this.look('');
			this.point('');
			if (!this.rolling) {
				this.hush();
			}
		}
	};

	// Keeps the pictures inside the section (and the screen), and off the logo and character.
	Intro.prototype.placeDeck = function (kw) {
		var deck = kw.querySelector('.avix-it__deck');
		if (!deck) {
			return;
		}
		var style = window.getComputedStyle(deck);
		var rect = kw.getBoundingClientRect();
		var rootRect = this.root.getBoundingClientRect();
		var minX = Math.max(rootRect.left, 0) + 8;
		var maxX = Math.min(rootRect.right, document.documentElement.clientWidth) - 8;

		if (kw.classList.contains('avix-it__kw--round')) {
			var size = parseFloat(style.getPropertyValue('--it-round')) || 110;
			kw.classList.toggle('is-flip', rect.right + 12 + size > maxX && rect.left - 12 - size >= minX);
			return;
		}

		var cardW = parseFloat(style.getPropertyValue('--it-card-w')) || 200;
		var cardH = parseFloat(style.getPropertyValue('--it-card-h')) || 150;
		var spread = parseFloat(style.getPropertyValue('--it-spread')) || 0;
		var half = cardW / 2 + (deck.children.length < 2 ? 12 : spread + cardW * 0.14);
		var center = rect.left + rect.width / 2;
		var shift = maxX - minX > half * 2 ? clamp(center, minX + half, maxX - half) - center : 0;
		kw.style.setProperty('--it-shift', shift.toFixed(1) + 'px');

		var below = false;
		if (this.mark) {
			var mark = this.mark.getBoundingClientRect();
			var reach = rect.top - cardH - 44;
			below = center + shift + half > mark.left - 16 && center + shift - half < mark.right + 16 && reach < mark.bottom + 8;
		}
		kw.classList.toggle('is-below', below);
	};

	/* ---------- Loop ---------- */

	Intro.prototype.wake = function () {
		if (!this.alive || this.frame || !this.buddy) {
			return;
		}
		this.last = 0;
		this.frame = window.requestAnimationFrame(this.tick);
	};

	Intro.prototype.tick = function (time) {
		this.frame = 0;
		if (!this.root.isConnected) {
			this.alive = false;
			this.timers.forEach(window.clearTimeout);
			window.clearTimeout(this.talkTimer);
			return;
		}
		var dt = this.last ? Math.min(0.05, (time - this.last) / 1000) : 1 / 60;
		this.last = time;
		var active = false;

		// The logo: free roll while hovered, then a spring back to upright.
		if (this.rolling) {
			this.omega += (ROLL_SPEED - this.omega) * (1 - Math.exp(-dt / 0.5));
			this.angle += this.omega * dt;
			active = true;
		} else if (this.omega !== 0 || this.angle !== this.settleTo) {
			var k = 42;
			var accel = k * (this.settleTo - this.angle) - 2 * Math.sqrt(k) * this.omega;
			this.omega += accel * dt;
			this.angle += this.omega * dt;
			if (Math.abs(this.settleTo - this.angle) < 0.4 && Math.abs(this.omega) < 4) {
				this.angle = 0;
				this.settleTo = 0;
				this.omega = 0;
			} else {
				active = true;
			}
		}

		// Stopped rolling: sit back down once the logo has slowed.
		if (this.stopping && Math.abs(this.omega) < 70 && !this.tween) {
			this.stopping = false;
			this.setPose('sit');
			this.moveTo(this.sitPos(), { dur: 0.18 });
		}

		// Character movement.
		var flip = 0;
		if (this.tween) {
			var tw = this.tween;
			tw.t = Math.min(1, tw.t + dt / tw.dur);
			var eased = easeInOut(tw.t);
			this.pos = {
				x: tw.from.x + (tw.to.x - tw.from.x) * eased,
				y: tw.from.y + (tw.to.y - tw.from.y) * eased - tw.arc * 4 * tw.t * (1 - tw.t)
			};
			flip = tw.flip * easeInOut(tw.t);
			if (tw.front !== undefined && tw.t >= tw.front) {
				this.buddy.classList.add('is-front');
			}
			if (tw.t >= 1) {
				this.tween = null;
				flip = 0;
				if (tw.done) {
					tw.done.call(this);
				}
			}
			active = true;
		}

		// Balancing on the rolling logo.
		var tilt = 0;
		this.wobbleX = 0;
		if (this.pose === 'run' && !this.tween) {
			this.wobble += dt;
			this.wobbleX = Math.sin(this.wobble * 6.5) * 2.5;
			tilt = Math.sin(this.wobble * 6.5 + 0.9) * 8;
			active = true;
		}

		this.render(flip + tilt);

		if (active && this.visible) {
			this.frame = window.requestAnimationFrame(this.tick);
		}
	};

	Intro.prototype.render = function (rotation) {
		if (this.wheel) {
			this.wheel.style.transform = this.angle ? 'rotate(' + this.angle.toFixed(1) + 'deg)' : '';
		}
		if (!this.buddy) {
			return;
		}
		this.buddy.style.transform = 'translate3d(' + (this.pos.x + (this.wobbleX || 0)).toFixed(1) + 'px,' + this.pos.y.toFixed(1) + 'px,0)';
		this.sprite.style.transform = rotation ? 'rotate(' + rotation.toFixed(1) + 'deg)' : '';
		if (this.bubble && this.bubble.classList.contains('is-open')) {
			this.placeBubble();
		}
	};

	/* ---------- Mounting ---------- */

	function mount(root) {
		if (!root || (root.__avixIt && root.__avixIt.alive)) {
			return;
		}
		root.__avixIt = new Intro(root);
	}

	function mountAll(scope) {
		Array.prototype.forEach.call((scope || document).querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixIntroText = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-intro-text.default', function ($scope) {
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
