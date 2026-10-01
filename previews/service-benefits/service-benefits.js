/* A short greeting on entry or interaction; no continual motion or timers. */
(() => {
  'use strict';
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  document.querySelectorAll('[data-avix-benefits]').forEach(root => {
    const help = root.querySelector('[data-benefits-help]');
    if (!help) return;
    const arm = help.querySelector('.avix-benefits__arm');
    function wave() {
      if (reduced.matches || help.classList.contains('is-waving')) return;
      help.classList.add('is-waving');
    }
    arm.addEventListener('animationend', () => help.classList.remove('is-waving'));
    help.addEventListener('pointerenter', wave);
    help.addEventListener('focusin', wave);
    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver(entries => {
        if (entries.some(entry => entry.isIntersecting)) { wave(); observer.disconnect(); }
      }, { threshold: .6 });
      observer.observe(help);
    }
  });
})();
