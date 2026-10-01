/*!
 * Avix Digital · Site Footer
 * Copy-to-clipboard for emails, back-to-top, and current-page link state.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-ft]';
	var reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };

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
		Array.prototype.forEach.call(root.querySelectorAll('.avix-ft__col a.avix-ft__link'), function (link) {
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
