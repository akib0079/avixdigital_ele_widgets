<?php
/**
 * Lead events for Google Analytics 4 (the Google tag Site Kit prints), so GA4
 * shows which pages produce leads, not only traffic:
 *
 * - book_site_review: a click on a Calendly link (the "Book a free site review" CTA).
 * - whatsapp_click:   a click on a WhatsApp link.
 * - email_click:      a click on a mailto: link.
 * - phone_click:      a click on a tel: link.
 * - generate_lead:    an Avix Forms enquiry sent (its success message appears).
 *
 * Each event carries link_url, link_text and page_path (generate_lead: form_id,
 * page_path, method). Mark the ones that count as leads as key events in GA4
 * (Admin > Events). Nothing is sent for logged-in editors, or when no Google tag
 * is on the page (events then go to dataLayer only).
 *
 * Filter: avix_conversions_enabled (bool).
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\SEO;

defined( 'ABSPATH' ) || exit;

final class Conversions {

	public static function init(): void {
		add_action( 'wp_footer', array( __CLASS__, 'print_script' ), 99 );
	}

	public static function print_script(): void {
		if ( is_admin() || is_customize_preview() || current_user_can( 'edit_posts' ) ) {
			return;
		}
		if ( isset( $_GET['elementor-preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only check.
			return;
		}
		/**
		 * Whether the lead events script is printed.
		 *
		 * @param bool $enabled True by default.
		 */
		if ( ! apply_filters( 'avix_conversions_enabled', true ) ) {
			return;
		}
		?>
<script id="avix-lead-events">
(function () {
	function send(name, params) {
		if (typeof window.gtag === 'function') { window.gtag('event', name, params); return; }
		(window.dataLayer = window.dataLayer || []).push(Object.assign({ event: name }, params));
	}
	document.addEventListener('click', function (e) {
		var a = e.target && e.target.closest ? e.target.closest('a[href]') : null;
		if (!a) { return; }
		var h = a.getAttribute('href') || '';
		var p = { link_url: a.href, link_text: (a.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 80), page_path: location.pathname };
		if (/calendly\.com\//i.test(h)) { send('book_site_review', p); }
		else if (/(^whatsapp:|wa\.me\/|wa\.link\/|api\.whatsapp\.com\/)/i.test(h)) { send('whatsapp_click', p); }
		else if (/^mailto:/i.test(h)) { send('email_click', p); }
		else if (/^tel:/i.test(h)) { send('phone_click', p); }
	}, true);
	if (!window.MutationObserver || !document.querySelector('.avf-message')) { return; }
	new MutationObserver(function (list) {
		list.forEach(function (m) {
			var t = m.target, was = m.oldValue || '';
			if (t.classList && t.classList.contains('avf-message--success') && was.indexOf('avf-message--success') < 0) {
				var f = t.closest('form');
				send('generate_lead', { form_id: f ? (f.id || f.getAttribute('data-avf-form') || '') : '', page_path: location.pathname, method: 'contact_form' });
			}
		});
	}).observe(document.body, { subtree: true, attributes: true, attributeFilter: ['class'], attributeOldValue: true });
})();
</script>
		<?php
	}
}
