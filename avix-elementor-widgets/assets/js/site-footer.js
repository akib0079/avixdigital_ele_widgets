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

	function mount(root) {
		if (!root || root.__avixFt) {
			return;
		}
		root.__avixFt = true;
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
