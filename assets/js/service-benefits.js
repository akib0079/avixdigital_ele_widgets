/* Finite greeting, idempotent editor mounting and cleanup on widget removal. */
(() => {
  'use strict';
  const instances = new Map();
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  let hooked;
  function mount(root) {
    if (instances.has(root)) return;
    const help = root.querySelector('[data-benefits-help]');
    const arm = help && help.querySelector('.avix-benefits__arm');
    let config = {};
    try { config = JSON.parse(root.getAttribute('data-avix-benefits') || '{}'); } catch (_) { /* Static content remains usable. */ }
    if (!arm || config.avatarMotion === false) return;
    let observer;
    const clear = () => help.classList.remove('is-waving');
    const wave = () => { if (!reduced.matches && !help.classList.contains('is-waving')) help.classList.add('is-waving'); };
    const changed = () => { if (reduced.matches) clear(); };
    arm.addEventListener('animationend', clear);
    help.addEventListener('pointerenter', wave);
    help.addEventListener('focusin', wave);
    if (reduced.addEventListener) reduced.addEventListener('change', changed);
    else reduced.addListener(changed);
    if (window.IntersectionObserver) {
      observer = new IntersectionObserver(entries => {
        if (entries.some(entry => entry.isIntersecting)) { wave(); observer.disconnect(); }
      }, { threshold: .6 });
      observer.observe(help);
    }
    instances.set(root, () => {
      if (observer) observer.disconnect();
      arm.removeEventListener('animationend', clear);
      help.removeEventListener('pointerenter', wave);
      help.removeEventListener('focusin', wave);
      if (reduced.removeEventListener) reduced.removeEventListener('change', changed);
      else reduced.removeListener(changed);
      clear(); instances.delete(root);
    });
  }
  function mountAll(scope = document) {
    const node = scope.jquery ? scope[0] : scope;
    if (!node) return;
    if (node.matches && node.matches('[data-avix-benefits]')) mount(node);
    node.querySelectorAll('[data-avix-benefits]').forEach(mount);
  }
  function hookElementor() {
    const hooks = window.elementorFrontend && window.elementorFrontend.hooks;
    if (!hooks || hooks === hooked) return;
    hooked = hooks;
    hooks.addAction('frontend/element_ready/avix-service-benefits.default', mountAll);
  }
  function start() {
    mountAll(); hookElementor();
    new MutationObserver(records => {
      records.forEach(record => record.addedNodes.forEach(node => { if (node.nodeType === 1) mountAll(node); }));
      instances.forEach((destroy, root) => { if (!root.isConnected) destroy(); });
      hookElementor();
    }).observe(document.body, { childList: true, subtree: true });
  }
  window.addEventListener('elementor/frontend/init', hookElementor);
  if (window.jQuery) window.jQuery(window).on('elementor/frontend/init.avixBenefits', hookElementor);
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
  else start();
})();
