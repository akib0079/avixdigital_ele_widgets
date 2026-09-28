/*!
 * Avix Digital · FAQ & Quote
 * Progressive extras only; the accordion works without this file:
 * #anchor deep links open their answer, one-open-at-a-time for browsers
 * without <details name>, a one-shot reveal and pausing loops off screen.
 */
(function (window, document) {
	'use strict';

	var ROOT_SELECTOR = '[data-avix-faq]';
	var details = window.HTMLDetailsElement ? window.HTMLDetailsElement.prototype : {};
	var nativeExclusive = 'name' in details;

	function isEditMode() {
		return !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
	}

	function Faq(root) {
		this.root = root;
		this.alive = true;
		this.items = Array.prototype.slice.call(root.querySelectorAll('[data-faq-item]'));
		this.onHash = this.openFromHash.bind(this);
		this.onToggle = this.toggle.bind(this);

		root.classList.add('is-live');
		this.observe();
		if (root.hasAttribute('data-faq-exclusive') && !nativeExclusive) {
			// toggle doesn't bubble; capture catches it on the way down.
			root.addEventListener('toggle', this.onToggle, true);
		}
		window.addEventListener('hashchange', this.onHash);
		this.openFromHash();
	}

	Faq.prototype.observe = function () {
		var root = this.root;
		var self = this;
		if (!('IntersectionObserver' in window) || isEditMode()) {
			root.classList.add('is-in');
			return;
		}
		this.observer = new window.IntersectionObserver(function (entries) {
			if (!root.isConnected) {
				self.destroy();
				return;
			}
			var visible = entries[entries.length - 1].isIntersecting;
			if (visible) {
				root.classList.add('is-in');
			}
			root.classList.toggle('is-offscreen', !visible);
		}, { rootMargin: '0px 0px -8% 0px' });
		this.observer.observe(root);
	};

	// "#faq-pricing" (or any id inside an answer) opens that answer.
	Faq.prototype.openFromHash = function () {
		if (!this.root.isConnected) {
			this.destroy();
			return;
		}
		var id = window.location.hash.slice(1);
		if (!id) {
			return;
		}
		var target = document.getElementById(id);
		if (!target) {
			try {
				target = document.getElementById(decodeURIComponent(id));
			} catch (error) {
				return;
			}
		}
		var item = target && this.root.contains(target) && target.closest ? target.closest('[data-faq-item]') : null;
		if (!item) {
			return;
		}
		if (item.open) {
			return;
		}
		// Jump without animation so the scroll lands exactly on the answer.
		var root = this.root;
		root.classList.add('is-in', 'is-jump');
		item.open = true;
		target.scrollIntoView({ block: 'start' });
		window.requestAnimationFrame(function () {
			window.requestAnimationFrame(function () {
				root.classList.remove('is-jump');
			});
		});
	};

	Faq.prototype.toggle = function (event) {
		var item = event.target;
		if (!item.open || this.items.indexOf(item) < 0) {
			return;
		}
		this.items.forEach(function (other) {
			if (other !== item && other.open) {
				other.open = false;
			}
		});
	};

	Faq.prototype.destroy = function () {
		if (!this.alive) {
			return;
		}
		this.alive = false;
		window.removeEventListener('hashchange', this.onHash);
		this.root.removeEventListener('toggle', this.onToggle, true);
		if (this.observer) {
			this.observer.disconnect();
		}
	};

	function mount(root) {
		if (!root || (root.__avixFaq && root.__avixFaq.alive)) {
			return;
		}
		root.__avixFaq = new Faq(root);
	}

	function mountAll(scope) {
		Array.prototype.forEach.call((scope || document).querySelectorAll(ROOT_SELECTOR), mount);
	}

	window.AvixFaq = { mount: mount, mountAll: mountAll };

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
		window.elementorFrontend.hooks.addAction('frontend/element_ready/avix-faq.default', function ($scope) {
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
