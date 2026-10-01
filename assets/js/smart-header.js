/*!
 * Avix Digital · Smart Header
 * Scroll states (see-through → frosted bar, hides while scrolling down),
 * light/dark matching with logo swap, services dropdown, full-screen mobile
 * menu, and the pixel character in the notch that says hi now and then.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-sh]';
	var reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };
	var mobileQuery = window.matchMedia ? window.matchMedia('(max-width: 900px)') : { matches: false };

	function isEditMode() {
		return !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
	}

	function parseColor(value) {
		var match = /rgba?\(\s*([\d.]+)[,\s]+([\d.]+)[,\s]+([\d.]+)(?:[,\s/]+([\d.]+%?))?\s*\)/.exec(value || '');
		if (!match) {
			return null;
		}
		var alpha = match[4] === undefined ? 1 : match[4].indexOf('%') > -1 ? parseFloat(match[4]) / 100 : parseFloat(match[4]);
		return { r: +match[1], g: +match[2], b: +match[3], a: alpha };
	}

	function isDark(color) {
		return (0.2126 * color.r + 0.7152 * color.g + 0.0722 * color.b) / 255 < 0.55;
	}

	// Same page? Compares origin + path, ignoring a trailing slash, query and hash.
	function pageKey(href) {
		try {
			var url = new window.URL(href, window.location.href);
			return (url.origin + url.pathname.replace(/\/+$/, '')).toLowerCase();
		} catch (error) {
			return '';
		}
	}

	function Header(root) {
		var config = {};
		try {
			config = JSON.parse(root.getAttribute('data-avix-sh') || '{}') || {};
		} catch (error) {
			config = {};
		}

		this.root = root;
		this.config = config;
		this.alive = true;
		this.edit = isEditMode();
		this.bar = root.querySelector('.avix-sh__bar');
		this.toggle = root.querySelector('[data-sh-toggle]');
		this.menu = this.toggle ? document.getElementById(this.toggle.getAttribute('aria-controls')) : null;
		this.notch = root.querySelector('[data-sh-notch]');
		this.pal = root.querySelector('[data-sh-pal]');
		this.drops = Array.prototype.slice.call(root.querySelectorAll('[data-sh-drop]'));
		this.topTone = config.top === 'light' ? 'light' : 'dark';
		this.lastY = window.pageYOffset || 0;
		this.hidden = false;
		this.open = false;

		this.onScroll = this.onScroll.bind(this);
		this.init();
	}

	Header.prototype.init = function () {
		var self = this;
		window.addEventListener('scroll', this.onScroll, { passive: true });
		window.addEventListener('resize', this.onScroll, { passive: true });
		window.addEventListener('load', function () {
			self.update(true);
		});
		// Late content (sliders, lazy images) can change what's behind the header.
		window.setTimeout(function () {
			self.update(true);
		}, 700);

		this.bindDrops();
		this.bindMenu();
		this.bindPal();
		if (this.config.current !== false) {
			this.markCurrent();
		}

		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape') {
				if (self.open) {
					self.closeMenu(true);
				}
				self.drops.forEach(function (drop) {
					if (drop.classList.contains('is-open')) {
						self.closeDrop(drop);
						var button = drop.querySelector('[data-sh-drop-btn]');
						if (button && drop.contains(document.activeElement)) {
							button.focus();
						}
					}
				});
			}
		});

		this.update(true);
	};

	/* ---------- Scroll & tone ---------- */

	Header.prototype.onScroll = function () {
		if (this.ticking) {
			return;
		}
		this.ticking = true;
		var self = this;
		window.requestAnimationFrame(function () {
			self.ticking = false;
			self.update(false);
		});
	};

	Header.prototype.update = function (force) {
		if (!this.root.isConnected) {
			this.destroy();
			return;
		}
		var y = window.pageYOffset || document.documentElement.scrollTop || 0;
		if (this.open) {
			this.lastY = y;
			return;
		}
		var scrolled = y > 50 && !this.edit;
		this.root.classList.toggle('is-scrolled', scrolled);

		if (this.config.hide && !this.edit) {
			if (y <= 10) {
				this.hidden = false;
			} else if (y > this.lastY + 3 && y > 200) {
				this.hidden = true;
			} else if (y < this.lastY - 3) {
				this.hidden = false;
			}
			this.root.classList.toggle('is-hidden', this.hidden);
			if (this.hidden) {
				this.drops.forEach(this.closeDrop, this);
			}
		}
		this.lastY = y;

		// See-through at the top: match what's behind it.
		if (!scrolled && this.config.top === 'auto' && (force || Math.abs(y - (this.detectedAt || 0)) > 24)) {
			this.detectedAt = y;
			this.topTone = this.detect();
		}
		this.scrolled = scrolled;
		this.applyTone();
	};

	Header.prototype.applyTone = function () {
		var tone = this.open ? this.config.mobile : this.scrolled ? this.config.scrolled : this.topTone;
		tone = tone === 'light' ? 'light' : 'dark';
		this.root.classList.toggle('avix-sh--on-dark', tone === 'dark');
		this.root.classList.toggle('avix-sh--on-light', tone === 'light');
	};

	// Samples three points behind the bar and takes the majority.
	Header.prototype.detect = function () {
		if (!document.elementsFromPoint || !this.bar) {
			return this.topTone;
		}
		var rect = this.bar.getBoundingClientRect();
		var y = Math.max(1, Math.min(window.innerHeight - 2, rect.top + rect.height * 0.55));
		var width = document.documentElement.clientWidth;
		var dark = 0;
		var self = this;
		[0.12, 0.5, 0.88].forEach(function (fraction) {
			if (self.toneAt(width * fraction, y) === 'dark') {
				dark++;
			}
		});
		return dark >= 2 ? 'dark' : 'light';
	};

	Header.prototype.toneAt = function (x, y) {
		var stack = document.elementsFromPoint(x, y);
		for (var i = 0; i < stack.length; i++) {
			var element = stack[i];
			if (this.root.contains(element) || (this.menu && this.menu.contains(element))) {
				continue;
			}
			var forced = element.closest && element.closest('[data-avix-header]');
			if (forced) {
				var value = forced.getAttribute('data-avix-header');
				if (value === 'dark' || value === 'light') {
					return value;
				}
			}
			var tag = element.tagName;
			if (tag === 'IMG' || tag === 'VIDEO' || tag === 'CANVAS' || tag === 'IFRAME' || tag === 'PICTURE') {
				return 'dark';
			}
			var style = window.getComputedStyle(element);
			if (style.backgroundImage && style.backgroundImage.indexOf('url(') > -1) {
				return 'dark';
			}
			var color = parseColor(style.backgroundColor);
			if (color && color.a >= 0.5) {
				return isDark(color) ? 'dark' : 'light';
			}
		}
		return 'light';
	};

	/* ---------- Dropdown ---------- */

	Header.prototype.bindDrops = function () {
		var self = this;
		this.drops.forEach(function (drop) {
			var button = drop.querySelector('[data-sh-drop-btn]');
			var isLink = button && button.tagName === 'A';
			var timer = 0;

			drop.addEventListener('pointerenter', function (event) {
				if (event.pointerType === 'mouse') {
					window.clearTimeout(timer);
					self.openDrop(drop);
				}
			});
			drop.addEventListener('pointerleave', function (event) {
				if (event.pointerType === 'mouse') {
					timer = window.setTimeout(function () {
						self.closeDrop(drop);
					}, 160);
				}
			});
			drop.addEventListener('focusout', function (event) {
				if (!drop.contains(event.relatedTarget)) {
					self.closeDrop(drop);
				}
			});
			if (!button) {
				return;
			}
			if (isLink) {
				button.addEventListener('focus', function () {
					self.openDrop(drop);
				});
			}
			button.addEventListener('pointerdown', function (event) {
				self.lastPointer = event.pointerType;
			}, { passive: true });
			button.addEventListener('click', function (event) {
				var touch = event.detail > 0 && self.lastPointer && self.lastPointer !== 'mouse';
				if (!isLink) {
					// A mouse already opened it by hovering: a click keeps it open.
					if (touch || event.detail === 0 || !drop.classList.contains('is-open')) {
						self.toggleDrop(drop);
					}
				} else if (touch && !drop.classList.contains('is-open')) {
					event.preventDefault();
					self.openDrop(drop);
				}
			});
			button.addEventListener('keydown', function (event) {
				if (event.key === 'ArrowDown') {
					event.preventDefault();
					self.openDrop(drop);
					var first = drop.querySelector('.avix-sh__item');
					if (first) {
						first.focus();
					}
				}
			});
		});

		document.addEventListener('pointerdown', function (event) {
			self.drops.forEach(function (drop) {
				if (!drop.contains(event.target)) {
					self.closeDrop(drop);
				}
			});
		}, { passive: true });
	};

	Header.prototype.openDrop = function (drop) {
		drop.classList.add('is-open');
		var button = drop.querySelector('[data-sh-drop-btn]');
		if (button) {
			button.setAttribute('aria-expanded', 'true');
		}
	};

	Header.prototype.closeDrop = function (drop) {
		drop.classList.remove('is-open');
		var button = drop.querySelector('[data-sh-drop-btn]');
		if (button) {
			button.setAttribute('aria-expanded', 'false');
		}
	};

	Header.prototype.toggleDrop = function (drop) {
		if (drop.classList.contains('is-open')) {
			this.closeDrop(drop);
		} else {
			this.openDrop(drop);
		}
	};

	/* ---------- Mobile menu ---------- */

	Header.prototype.bindMenu = function () {
		var self = this;
		if (!this.toggle || !this.menu) {
			return;
		}
		this.toggle.addEventListener('click', function () {
			if (self.open) {
				self.closeMenu(false);
			} else {
				self.openMenu();
			}
		});

		Array.prototype.forEach.call(this.menu.querySelectorAll('[data-sh-acc]'), function (button) {
			var sub = document.getElementById(button.getAttribute('aria-controls'));
			button.addEventListener('click', function () {
				var expanded = button.getAttribute('aria-expanded') !== 'true';
				button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
				if (sub) {
					sub.classList.toggle('is-open', expanded);
				}
			});
		});

		// Following a link closes the menu (after the tap colour shows).
		this.menu.addEventListener('click', function (event) {
			var link = event.target.closest && event.target.closest('a[href]');
			if (link) {
				window.setTimeout(function () {
					self.closeMenu(false);
				}, 150);
			}
		});

		var onChange = function () {
			if (!mobileQuery.matches && self.open) {
				self.closeMenu(false);
			}
		};
		if (mobileQuery.addEventListener) {
			mobileQuery.addEventListener('change', onChange);
		} else if (mobileQuery.addListener) {
			mobileQuery.addListener(onChange);
		}
	};

	Header.prototype.openMenu = function () {
		var rect = this.toggle.getBoundingClientRect();
		this.menu.style.setProperty('--sh-mx', (rect.left + rect.width / 2).toFixed(0) + 'px');
		this.menu.style.setProperty('--sh-my', (rect.top + rect.height / 2).toFixed(0) + 'px');
		this.open = true;
		this.menu.classList.add('is-open');
		this.menu.setAttribute('aria-hidden', 'false');
		this.root.classList.add('is-menu-open');
		this.root.classList.remove('is-hidden');
		this.toggle.setAttribute('aria-expanded', 'true');
		this.toggle.setAttribute('aria-label', this.toggle.getAttribute('data-label-close') || 'Close menu');
		document.documentElement.classList.add('avix-sh-lock');
		this.applyTone();
		var first = this.menu.querySelector('.avix-sh-menu__link');
		if (first) {
			window.setTimeout(function () {
				first.focus({ preventScroll: true });
			}, reduceMotion.matches ? 0 : 380);
		}
	};

	Header.prototype.closeMenu = function (returnFocus) {
		if (!this.open) {
			return;
		}
		this.open = false;
		this.menu.classList.remove('is-open');
		this.menu.setAttribute('aria-hidden', 'true');
		this.root.classList.remove('is-menu-open');
		this.toggle.setAttribute('aria-expanded', 'false');
		this.toggle.setAttribute('aria-label', this.toggle.getAttribute('data-label-open') || 'Open menu');
		document.documentElement.classList.remove('avix-sh-lock');
		this.update(true);
		if (returnFocus) {
			this.toggle.focus();
		}
	};

	/* ---------- Current page ---------- */

	Header.prototype.markCurrent = function () {
		var here = pageKey(window.location.href);
		var links = Array.prototype.slice.call(this.root.querySelectorAll('.avix-sh__link[href], .avix-sh__item[href]'));
		if (this.menu) {
			links = links.concat(Array.prototype.slice.call(this.menu.querySelectorAll('.avix-sh-menu__link[href], .avix-sh-menu__sublink[href]')));
		}
		links.forEach(function (link) {
			var href = link.getAttribute('href');
			if (!href || href.charAt(0) === '#' || pageKey(link.href) !== here) {
				return;
			}
			link.classList.add('is-current');
			link.setAttribute('aria-current', 'page');
			var drop = link.closest('[data-sh-drop]');
			var button = drop && drop.querySelector('[data-sh-drop-btn]');
			if (button) {
				button.classList.add('is-current');
			}
		});
	};

	/* ---------- The pixel character ---------- */

	Header.prototype.bindPal = function () {
		if (!this.pal || !this.notch) {
			return;
		}
		var self = this;
		this.notch.addEventListener('pointerenter', function () {
			self.sayHi();
		});
		this.notch.addEventListener('click', function () {
			self.sayHi();
		});
		this.root.addEventListener('pointermove', function (event) {
			if (event.pointerType !== 'mouse' || self.saying) {
				return;
			}
			var rect = self.pal.getBoundingClientRect();
			var dx = event.clientX - (rect.left + rect.width / 2);
			self.pal.classList.toggle('is-look-l', dx < -40);
			self.pal.classList.toggle('is-look-r', dx > 40);
		}, { passive: true });
		this.root.addEventListener('pointerleave', function () {
			self.pal.classList.remove('is-look-l', 'is-look-r');
		});
		if ('IntersectionObserver' in window) {
			new window.IntersectionObserver(function (entries) {
				self.root.classList.toggle('is-off', !entries[0].isIntersecting);
			}).observe(this.notch);
		}
		this.scheduleHi(1400);
	};

	Header.prototype.sayHi = function () {
		if (!this.alive || this.saying || this.scrolled || this.hidden || this.open || document.hidden) {
			return;
		}
		var self = this;
		this.saying = true;
		this.pal.classList.remove('is-look-l', 'is-look-r', 'is-hi');
		void this.pal.offsetWidth;
		this.pal.classList.add('is-hi');
		window.setTimeout(function () {
			self.pal.classList.remove('is-hi');
			self.saying = false;
		}, 2100);
	};

	Header.prototype.scheduleHi = function (delay) {
		var every = parseFloat(this.config.hiEvery);
		if (!(every > 0) && delay === undefined) {
			return;
		}
		var self = this;
		window.clearTimeout(this.hiTimer);
		this.hiTimer = window.setTimeout(function () {
			if (!self.root.isConnected) {
				self.destroy();
				return;
			}
			self.sayHi();
			self.scheduleHi();
		}, delay !== undefined ? delay : every * 1000 * (0.75 + Math.random() * 0.5));
	};

	Header.prototype.destroy = function () {
		this.alive = false;
		window.clearTimeout(this.hiTimer);
		window.removeEventListener('scroll', this.onScroll);
		window.removeEventListener('resize', this.onScroll);
		if (this.open) {
			document.documentElement.classList.remove('avix-sh-lock');
		}
	};

	/* ---------- Mounting ---------- */

	function mount(root) {
		if (!root || (root.__avixSh && root.__avixSh.alive)) {
			return;
		}
		root.__avixSh = new Header(root);
	}

	function mountAll(scope) {
		Array.prototype.forEach.call((scope || document).querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixSmartHeader = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-smart-header.default', function ($scope) {
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
