<?php
/**
 * Images printed without any alt attribute in Elementor content (for example the
 * logo the ThemeREX "background text" marquee repeats between its words) get an
 * empty alt, which marks them as decorative. Screen readers then skip them instead
 * of reading the file name, and accessibility audits stop flagging them. Images
 * that have an alt, empty or not, are left alone.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\SEO;

defined( 'ABSPATH' ) || exit;

final class Image_Alt {

	public static function init(): void {
		add_filter( 'elementor/frontend/the_content', array( __CLASS__, 'add_missing_alt' ), 20 );
		add_action( 'wp_footer', array( __CLASS__, 'print_script' ), 98 );
	}

	/**
	 * The ThemeREX "background text" marquee builds its words and logo images in
	 * the browser, after the HTML filter has run: give those images alt="" as they
	 * are added.
	 */
	public static function print_script(): void {
		if ( is_admin() ) {
			return;
		}
		?>
<script id="avix-img-alt">
(function () {
	var sel = '.trx_addons_bg_text img:not([alt])';
	function fix(root) { (root.querySelectorAll ? root : document).querySelectorAll(sel).forEach(function (i) { i.setAttribute('alt', ''); }); }
	if (!document.querySelector('.trx_addons_bg_text')) { return; }
	fix(document);
	if (window.MutationObserver) {
		var mo = new MutationObserver(function (list) { list.forEach(function (m) { m.addedNodes.forEach(function (n) { if (n.nodeType === 1) { if (n.matches && n.matches(sel)) { n.setAttribute('alt', ''); } fix(n); } }); }); });
		document.querySelectorAll('.trx_addons_bg_text').forEach(function (el) { mo.observe(el, { childList: true, subtree: true }); });
	}
})();
</script>
		<?php
	}

	/**
	 * alt="" on every <img> that has no alt attribute.
	 *
	 * @param mixed $html Rendered builder content.
	 * @return mixed
	 */
	public static function add_missing_alt( $html ) {
		if ( ! is_string( $html ) || false === stripos( $html, '<img' ) ) {
			return $html;
		}
		return (string) preg_replace( '/<img\b(?![^>]*\balt\s*=)/i', '<img alt=""', $html );
	}
}
