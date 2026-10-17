/*!
 * Avix Digital · Article page (includes/blog/templates/article.php)
 * Reading progress, table-of-contents scroll-spy, in-page jumps that close
 * the phone TOC first, copy link, and the hero mosaic paused off screen.
 * No dependencies. Everything works without it: the TOC links are plain
 * anchors, the phone TOC is a native <details> and the share buttons are
 * plain links.
 */
(function () {
	'use strict';

	var root = document.querySelector('.avix-art');
	var content = root && root.querySelector('[data-art-content]');
	if (!content) {
		return;
	}

	var win = window;
	var bar = root.querySelector('[data-art-progress]');
	var toc = root.querySelector('[data-art-toc]');
	var live = root.querySelector('[data-art-live]');
	var reduce = win.matchMedia ? win.matchMedia('(prefers-reduced-motion: reduce)') : { matches: true };

	function byHash(hash) {
		var id = '';
		try {
			id = decodeURIComponent((hash || '').slice(1));
		} catch (e) {
			id = (hash || '').slice(1);
		}
		return id ? document.getElementById(id) : null;
	}

	/* ---------- Progress and scroll-spy ---------- */

	var links = toc ? Array.prototype.slice.call(toc.querySelectorAll('a[href^="#"]')) : [];
	var heads = links.map(function (a) { return byHash(a.hash); });
	// For a sub-section link: the link of its section (null for sections).
	var parents = links.map(function (a) {
		var sub = a.parentNode && a.parentNode.parentNode;
		var li = sub && sub !== toc && sub.parentNode;
		return li && li.tagName === 'LI' ? li.querySelector('a[href^="#"]') : null;
	});
	var tops = [];
	var start = 0;
	var end = 1;
	var active = -2;
	var ticking = false;

	function measure() {
		var y = win.pageYOffset;
		var vh = win.innerHeight;
		var r = content.getBoundingClientRect();
		start = r.top + y - vh * 0.2;
		end = r.bottom + y - vh * 0.8;
		tops = heads.map(function (h) { return h ? h.getBoundingClientRect().top + y : Infinity; });
		update();
	}

	function keepInView(link) {
		if (!toc || toc.scrollHeight <= toc.clientHeight + 1) {
			return;
		}
		if (!link) {
			toc.scrollTop = 0;
			return;
		}
		// A hidden sub-section (narrow rails list the sections only): keep its section in view.
		if (!link.offsetParent) {
			link = parents[links.indexOf(link)] || link;
		}
		var top = link.offsetTop - toc.offsetTop;
		if (top < toc.scrollTop + 8 || top > toc.scrollTop + toc.clientHeight - 56) {
			toc.scrollTop = Math.max(0, top - toc.clientHeight / 3);
		}
	}

	function update() {
		ticking = false;
		var y = win.pageYOffset;
		if (bar) {
			var p = Math.max(0, Math.min(1, (y - start) / Math.max(1, end - start)));
			bar.style.transform = 'scaleX(' + p.toFixed(4) + ')';
		}
		if (!links.length) {
			return;
		}
		var line = y + win.innerHeight * 0.28;
		var idx = -1;
		for (var i = 0; i < tops.length; i++) {
			if (tops[i] <= line) {
				idx = i;
			}
		}
		if (idx !== active) {
			if (links[active]) {
				links[active].removeAttribute('aria-current');
			}
			if (parents[active]) {
				parents[active].removeAttribute('data-parent');
			}
			if (links[idx]) {
				links[idx].setAttribute('aria-current', 'location');
			}
			if (parents[idx]) {
				parents[idx].setAttribute('data-parent', '');
			}
			keepInView(links[idx]);
			active = idx;
		}
	}

	function onScroll() {
		if (!ticking) {
			ticking = true;
			win.requestAnimationFrame(update);
		}
	}

	win.addEventListener('scroll', onScroll, { passive: true });
	win.addEventListener('resize', measure, { passive: true });
	win.addEventListener('load', measure);
	if ('ResizeObserver' in win) {
		new win.ResizeObserver(function () { measure(); }).observe(content);
	}
	measure();

	/* ---------- Hero mosaic: no twinkle while the hero is off screen ---------- */

	var hero = root.querySelector('.avix-art-hero');
	if (hero && 'IntersectionObserver' in win) {
		new win.IntersectionObserver(function (entries) {
			hero.classList.toggle('is-still', !entries[entries.length - 1].isIntersecting);
		}).observe(hero);
	}

	/* ---------- In-page links ---------- */

	root.addEventListener('click', function (e) {
		var a = e.target && e.target.closest ? e.target.closest('a[href^="#"]') : null;
		if (!a || e.defaultPrevented || e.button || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
			return;
		}
		var target = byHash(a.hash);
		if (!target) {
			return;
		}
		e.preventDefault();
		// The phone TOC closes first, so the target does not move after the jump.
		var details = a.closest('details');
		if (details) {
			details.open = false;
		}
		target.scrollIntoView({ behavior: reduce.matches ? 'auto' : 'smooth', block: 'start' });
		if (win.history && win.history.replaceState) {
			win.history.replaceState(null, '', a.hash);
		}
		if (!target.hasAttribute('tabindex')) {
			target.setAttribute('tabindex', '-1');
		}
		target.focus({ preventScroll: true });
	});

	/* ---------- Copy link ---------- */

	Array.prototype.forEach.call(root.querySelectorAll('[data-art-copy]'), function (btn) {
		var timer = 0;
		btn.addEventListener('click', function () {
			var url = btn.getAttribute('data-art-copy');
			function done() {
				btn.classList.add('is-copied');
				if (live) {
					live.textContent = live.getAttribute('data-art-copied') || 'Link copied';
				}
				win.clearTimeout(timer);
				timer = win.setTimeout(function () {
					btn.classList.remove('is-copied');
					if (live) {
						live.textContent = '';
					}
				}, 2200);
			}
			function ask() {
				win.prompt(btn.textContent.trim() || 'Copy link', url);
			}
			if (navigator.clipboard && win.isSecureContext) {
				navigator.clipboard.writeText(url).then(done, ask);
			} else {
				ask();
			}
		});
	});
})();
