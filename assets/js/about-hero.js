/* Avix About Hero: measured, instance-safe loops with Elementor editor lifecycle. */
(() => {
  'use strict';
  const instances = new Map();
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  let hooked = null;

  function mount(root) {
    if (instances.has(root)) return;
    const rail = root.querySelector('.avix-about__expertise');
    if (!rail) return;
    let config;
    try { config = JSON.parse(root.dataset.avixAbout || '{}'); } catch (_) { return; }
    const tracks = [...rail.querySelectorAll('[data-track]')];
    const groups = tracks.map(track => track.firstElementChild);
    const button = rail.querySelector('.avix-about__pause');
    const marquee = rail.querySelector('.avix-about__marquee');
    let manual = false, hovered = false, focused = false, offscreen = false, frame = 0, destroyed = false;
    const cleanups = [];
    const listen = (target, name, callback) => {
      target.addEventListener(name, callback);
      cleanups.push(() => target.removeEventListener(name, callback));
    };
    function updatePause() {
      root.classList.toggle('is-paused', manual || document.hidden || offscreen || (config.pauseHover && (hovered || focused)));
      if (button) {
        const label = manual ? config.resume : config.pause;
        button.setAttribute('aria-pressed', String(manual));
        button.setAttribute('aria-label', label || (manual ? 'Resume animation' : 'Pause animation'));
        button.querySelector('span').textContent = label || (manual ? 'Resume animation' : 'Pause animation');
      }
    }
    function measure() {
      frame = 0;
      if (destroyed || !root.isConnected || root.classList.contains('is-still')) return;
      // Every read first, then the writes: one layout per measure.
      const period = groups[0].getBoundingClientRect().width;
      if (!period) return;
      const copies = Math.ceil(marquee.clientWidth / period) + 2;
      // Larger editor typography or longer labels may need additional vertical room.
      const tileHeight = rail.querySelector('.avix-about__hub').getBoundingClientRect().height;
      const groupHeight = Math.max(...groups.map(group => group.getBoundingClientRect().height));
      const height = Math.max(tileHeight, groupHeight) + 28;
      const current = marquee.getBoundingClientRect().height;
      rail.style.setProperty('--aa-period', period + 'px');
      tracks.forEach((track, index) => {
        while (track.children.length > copies) track.lastElementChild.remove();
        while (track.children.length < copies) {
          const copy = groups[index].cloneNode(true);
          copy.querySelectorAll('[id]').forEach(node => node.removeAttribute('id'));
          track.append(copy);
        }
      });
      // The CSS already reserves the usual height: only a real difference is written (no shift).
      if (Math.abs(height - current) > 2) marquee.style.height = height + 'px';
    }
    function schedule() { if (!frame && !destroyed) frame = requestAnimationFrame(measure); }
    function motionChanged() {
      const still = config.animate === false || reduced.matches;
      root.classList.toggle('is-still', still);
      if (button) button.hidden = still;
      schedule(); updatePause();
    }
    root.classList.add('is-enhanced');
    listen(button, 'click', () => { manual = !manual; updatePause(); });
    listen(rail, 'mouseenter', () => { hovered = true; updatePause(); });
    listen(rail, 'mouseleave', () => { hovered = false; updatePause(); });
    listen(rail, 'focusin', () => { focused = true; updatePause(); });
    listen(rail, 'focusout', event => { focused = rail.contains(event.relatedTarget); updatePause(); });
    listen(document, 'visibilitychange', updatePause);
    if (reduced.addEventListener) listen(reduced, 'change', motionChanged);
    else { reduced.addListener(motionChanged); cleanups.push(() => reduced.removeListener(motionChanged)); }
    if (window.ResizeObserver) {
      const resize = new ResizeObserver(schedule);
      [rail, ...groups, rail.querySelector('.avix-about__hub')].forEach(node => resize.observe(node));
      cleanups.push(() => resize.disconnect());
    } else listen(window, 'resize', schedule);
    if (window.IntersectionObserver) {
      const visible = new IntersectionObserver(entries => { offscreen = !entries[0].isIntersecting; updatePause(); });
      visible.observe(root); cleanups.push(() => visible.disconnect());
    }
    if (document.fonts) document.fonts.ready.then(schedule);
    instances.set(root, () => {
      destroyed = true; cancelAnimationFrame(frame); cleanups.forEach(cleanup => cleanup());
      instances.delete(root);
    });
    motionChanged();
  }
  function mountAll(scope = document) {
    const node = scope.jquery ? scope[0] : scope;
    if (!node) return;
    if (node.matches && node.matches('[data-avix-about]')) mount(node);
    node.querySelectorAll('[data-avix-about]').forEach(mount);
  }
  function hookElementor() {
    const frontend = window.elementorFrontend;
    if (!frontend || !frontend.hooks || hooked === frontend.hooks) return;
    hooked = frontend.hooks;
    hooked.addAction('frontend/element_ready/avix-about-hero.default', mountAll);
  }
  function start() {
    mountAll(); hookElementor();
    // Elementor replaces PHP-rendered markup in the preview when content changes.
    const observer = new MutationObserver(records => {
      for (const record of records) record.addedNodes.forEach(node => { if (node.nodeType === 1) mountAll(node); });
      instances.forEach((destroy, root) => { if (!root.isConnected) destroy(); });
      hookElementor();
    });
    observer.observe(document.body, { childList: true, subtree: true });
  }
  window.addEventListener('elementor/frontend/init', hookElementor);
  if (window.jQuery) window.jQuery(window).on('elementor/frontend/init.avixAboutHero', hookElementor);
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
  else start();
})();
