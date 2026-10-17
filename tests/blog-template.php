<?php
/**
 * CLI tests for the blog article template's pure helpers (no WordPress needed):
 * heading ids and the TOC, the content hooks (notes, tables, quotes, code),
 * the theme option merge, the service mapping, settings and post meta
 * sanitising, the hero mosaic, the image lightbox switch and whether the
 * minified stylesheet is current (php tests/blog-build.php).
 *
 *   php tests/blog-template.php
 *
 * The page itself (routing, theme options at runtime, header and footer,
 * scroll-spy) is checked in the local Playground: see the build notes.
 *
 * @package AvixWidgets
 */

define( 'ABSPATH', __DIR__ . '/' );

/* ---------------- minimal WordPress stand-ins ---------------- */

function __( $text, $domain = '' ) {
	return $text;
}
function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}
function esc_attr( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}
function esc_html__( $text, $domain = '' ) {
	return esc_html( $text );
}
function esc_attr__( $text, $domain = '' ) {
	return esc_attr( $text );
}
function _n( $single, $plural, $number, $domain = '' ) {
	return 1 === (int) $number ? $single : $plural;
}
function apply_filters( $hook, $value ) {
	return $value;
}
function wp_strip_all_tags( $text ) {
	$text = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', (string) $text );
	return trim( strip_tags( $text ) );
}
function wp_trim_words( $text, $num = 55, $more = '…' ) {
	$words = preg_split( '/\s+/', trim( (string) $text ), -1, PREG_SPLIT_NO_EMPTY );
	return count( $words ) > $num ? implode( ' ', array_slice( $words, 0, $num ) ) . $more : implode( ' ', $words );
}
function sanitize_key( $key ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
}
function sanitize_text_field( $text ) {
	return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $text ) ) );
}
/** WordPress's sanitize_title_with_dashes (accents removed, other UTF-8 percent-encoded). */
function sanitize_title( $title ) {
	$title = strip_tags( (string) $title );
	if ( function_exists( 'iconv' ) ) {
		$ascii = @iconv( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', $title ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		$title = false !== $ascii ? $ascii : $title;
	}
	$title = preg_replace_callback(
		'/[\x80-\xff]/',
		static function ( $m ) {
			return '%' . strtolower( dechex( ord( $m[0] ) ) );
		},
		$title
	);
	$title = strtolower( $title );
	$title = preg_replace( '/&.+?;/', '', $title );
	$title = str_replace( '.', '-', $title );
	$title = preg_replace( '/[^%a-z0-9 _-]/', '', $title );
	$title = preg_replace( '/\s+/', '-', $title );
	$title = preg_replace( '|-+|', '-', $title );
	return trim( $title, '-' );
}

$root = dirname( __DIR__ );
require $root . '/includes/blog/class-blog.php';
require $root . '/includes/blog/class-toc.php';
require $root . '/includes/blog/class-content.php';
require $root . '/includes/blog/class-settings.php';
require $root . '/includes/blog/class-post-meta.php';
require $root . '/includes/blog/class-article.php';

use AvixWidgets\Blog\Article;
use AvixWidgets\Blog\Blog;
use AvixWidgets\Blog\Content;
use AvixWidgets\Blog\Post_Meta;
use AvixWidgets\Blog\Settings;
use AvixWidgets\Blog\Toc;

$failures = 0;
$checks   = 0;

/**
 * @param bool   $ok   Condition.
 * @param string $name Check name.
 * @param mixed  $info Shown on failure.
 */
function check( $ok, $name, $info = null ) {
	global $failures, $checks;
	++$checks;
	if ( $ok ) {
		echo "ok   - {$name}\n";
		return;
	}
	++$failures;
	echo "FAIL - {$name}\n";
	if ( null !== $info ) {
		echo '       ' . str_replace( "\n", "\n       ", var_export( $info, true ) ) . "\n";
	}
}

/* ---------------- heading ids and the TOC ---------------- */

$html = '<div class="wp-block-group avix-takeaways"><p class="avix-takeaways__title">Key takeaways</p></div>'
	. '<p>Intro &amp; more.</p>'
	. '<h2 class="wp-block-heading">Compare the scope before comparing quotes</h2>'
	. '<p>Text</p>'
	. '<h3 class="wp-block-heading">Cost &amp; speed</h3>'
	. '<h3 class="wp-block-heading" data-note="a > b">Café menus in <em>three</em> languages</h3>'
	. '<!-- <h2>Commented out</h2> -->'
	. '<pre class="wp-block-code"><code>&lt;h2&gt;not a heading&lt;/h2&gt;' . "\n" . '<h2>raw in pre</h2></code></pre>'
	. '<h2 id="kept-id" class="wp-block-heading">Has an id</h2>'
	. '<h2 class="wp-block-heading">Compare the scope before comparing quotes</h2>'
	. '<h2 class="wp-block-heading avix-toc-skip">Skipped heading</h2>'
	. '<h2 class="wp-block-heading">   </h2>'
	. '<h4>Not in the TOC</h4>'
	. '<div class="wp-block-group avix-faq"><h2 class="wp-block-heading">Frequently asked questions</h2>'
	. '<h3 class="wp-block-heading">What does it cost?</h3><p>Answer.</p>'
	. '<div class="inner"><h3 class="wp-block-heading">Nested question?</h3></div></div>'
	. '<h3 class="wp-block-heading">After the FAQ</h3>';

$out = Toc::process( $html, 3 );
$ids = array();
preg_match_all( '~<h([23])\b[^>]*\sid="([^"]+)"~', $out['html'], $m, PREG_SET_ORDER );
foreach ( $m as $hit ) {
	$ids[] = $hit[2];
}
check( 'compare-the-scope-before-comparing-quotes' === $ids[0], 'H2 id is the slug of its text', $ids );
check( 'cost-speed' === $ids[1], '&amp; in a heading does not leak into the id', $ids );
check( 'cafe-menus-in-three-languages' === $ids[2], 'accents become ASCII, inline tags are dropped', $ids );
check( in_array( 'kept-id', $ids, true ) && 1 === substr_count( $out['html'], 'id="kept-id"' ), 'an existing id is kept and not duplicated' );
check( in_array( 'compare-the-scope-before-comparing-quotes-2', $ids, true ), 'a repeated heading gets -2' );
check( count( $ids ) === count( array_unique( $ids ) ), 'ids are unique on the page', $ids );
check( false !== strpos( $out['html'], '<!-- <h2>Commented out</h2> -->' ), 'headings inside comments are left alone' );
check( false !== strpos( $out['html'], '<h2>raw in pre</h2>' ), 'headings inside <pre> are left alone' );
check( false !== strpos( $out['html'], 'data-note="a > b"' ), 'a ">" inside a quoted attribute does not break the tag' );
check( false === strpos( $out['html'], '<h4 id=' ), 'H4 gets no id' );
check( preg_replace( '~ id="[^"]*"~', '', $out['html'] ) === preg_replace( '~ id="[^"]*"~', '', $html ), 'apart from the ids, the content is byte for byte the same' );

$texts = array();
foreach ( $out['items'] as $item ) {
	$texts[] = $item['text'];
	foreach ( $item['children'] as $child ) {
		$texts[] = '- ' . $child['text'];
	}
}
check(
	array(
		'Compare the scope before comparing quotes',
		'- Cost & speed',
		'- Café menus in three languages',
		'Has an id',
		'Compare the scope before comparing quotes',
		'Frequently asked questions',
		'- After the FAQ',
	) === $texts,
	'TOC: H2s with their H3s; skip class, empty headings and FAQ questions are left out',
	$texts
);
check( 7 === $out['count'], 'TOC count', $out['count'] );

$shallow = Toc::process( $html, 2 );
check( 4 === $shallow['count'] && ! $shallow['items'][0]['children'], 'depth 2 lists H2s only', $shallow['count'] );
check( $shallow['html'] === $out['html'], 'depth does not change the heading ids' );
check( array( 'html' => '<p>No headings</p>', 'items' => array(), 'count' => 0 ) === Toc::process( '<p>No headings</p>' ), 'content without headings is returned untouched' );

$again = Toc::process( $out['html'], 3 );
check( $again['html'] === $out['html'], 'running the pass twice changes nothing' );

$desk = Toc::desktop( $out['items'] );
check( 1 === substr_count( $desk, '<nav class="avix-art-toc"' ) && false !== strpos( $desk, 'aria-labelledby="avix-art-toc-title"' ), 'desktop TOC: one labelled nav' );
check( false !== strpos( $desk, '<a href="#cost-speed" data-t="Cost &amp; speed"><span>Cost &amp; speed</span></a>' ), 'desktop TOC: escaped text, plain anchors, the text reserved in data-t', $desk );
check( 7 === substr_count( $desk, '<a href="#' ), 'desktop TOC: one link per entry' );
$mob = Toc::mobile( $out['items'] );
check( 0 === strpos( $mob, '<details class="avix-art-toc-m">' ) && false === strpos( $mob, ' open' ), 'phone TOC: a closed <details>' );
check( 4 === substr_count( $mob, '<a href="#' ) && false !== strpos( $mob, '4 sections' ), 'phone TOC: sections only, with the count', $mob );
check( false !== strpos( $mob, '<a href="#kept-id">Has an id</a>' ), 'phone TOC: plain links' );
check( '' === Toc::desktop( array() ) && '' === Toc::mobile( array() ), 'no items, no TOC markup' );
$xss = Toc::process( '<h2>&lt;script&gt;alert(1)&lt;/script&gt; tips</h2><h2>B</h2><h2>C</h2>' );
check( false === strpos( Toc::desktop( $xss['items'] ), '<script>' ), 'TOC text is escaped' );
check( 'section' === Toc::slug( '!!!' ) && 'section' === Toc::slug( '日本語' ), 'a heading without ASCII letters gets "section"' );

/* ---------------- content hooks ---------------- */

$content = '<div class="wp-block-group avix-callout avix-callout--tip"><p class="avix-callout__title">Tip</p><p>Text</p></div>'
	. '<div class="wp-block-group avix-takeaways"><p class="avix-takeaways__title">Key takeaways</p></div>'
	. '<figure class="wp-block-table alignwide avix-compare"><table class="has-fixed-layout"><thead><tr><th></th><th>Shopify</th><th>WooCommerce</th></tr></thead>'
	. '<tbody><tr><th scope="row">Hosting</th><td>Included</td><td>Your own</td></tr><tr><th scope="row">Apps</th><td colspan="2">Both</td></tr></tbody></table>'
	. '<figcaption class="wp-element-caption">Platforms compared &amp; sourced from their docs.</figcaption></figure>'
	. '<table><tr><td>No header row</td></tr></table>'
	. '<blockquote><p>Quote</p></blockquote>'
	. '<figure class="wp-block-pullquote avix-pullquote"><blockquote class="x"><p>Pull</p></blockquote></figure>'
	. '<p class="avix-proof__more">Read the case study</p>'
	. '<pre class="wp-block-code language-liquid"><code>{{ x }}</code></pre>'
	. '<pre class="wp-block-code"><code class="language-js">a()</code></pre>'
	. '<pre>plain</pre>';
$enh = Content::enhance( $content );
check( false !== strpos( $enh, '<div class="wp-block-group avix-callout avix-callout--tip" role="note">' ), 'callout: role="note"' );
check( false !== strpos( $enh, 'avix-takeaways" role="note" aria-label="Key takeaways"' ), 'takeaways: role="note" with a label' );
check( false !== strpos( $enh, '<figure class="wp-block-table alignwide avix-compare is-stackable" tabindex="0">' ), 'table figure with a caption: stackable and focusable, named by its figcaption (no extra role)', $enh );
check( false !== strpos( $enh, '<thead><tr><td></td><th>Shopify</th>' ), 'the empty corner header cell becomes a <td>' );
$nocap = Content::enhance( '<figure class="wp-block-table"><table><thead><tr><th>A</th><th>B</th></tr></thead><tbody><tr><td>1</td><td>2</td></tr></tbody></table></figure>' );
check( false !== strpos( $nocap, 'tabindex="0" role="region" aria-label="Table"' ), 'table figure without a caption: a labelled region', $nocap );
check( false !== strpos( $enh, '<td data-label="Shopify">Included</td>' ) && false !== strpos( $enh, '<td data-label="WooCommerce">Your own</td>' ), 'body cells get the column name' );
check( false !== strpos( $enh, '<td colspan="2" data-label="Shopify">Both</td>' ), 'colspan cells take the first spanned column' );
check( false === strpos( $enh, '<th scope="row" data-label' ), 'row headers get no data-label' );
check( false !== strpos( $enh, '<div class="avix-art-table" tabindex="0" role="region" aria-label="Table"><table data-art-table="1"><tr><td>No header row</td>' ), 'a bare table gets the scroll wrapper and is not stackable', $enh );
check( 2 === substr_count( $enh, 'data-art-table="1"' ) && 1 === substr_count( $enh, 'class="avix-art-table' ), 'each table is processed exactly once' );
check( false !== strpos( $enh, '<blockquote class="is-style-plain"><p>Quote</p>' ) && false !== strpos( $enh, '<blockquote class="x is-style-plain">' ), 'blockquotes get is-style-plain (class added or appended)' );
check( false !== strpos( $enh, '<p class="avix-proof__more" aria-hidden="true">' ), 'proof card: the decorative line is hidden from screen readers' );
check( false !== strpos( $enh, '<pre class="wp-block-code language-liquid" data-lang="Liquid" tabindex="0" role="region" aria-label="Liquid code">' ), 'code: language label from the pre class, keyboard-scrollable region', $enh );
check( false !== strpos( $enh, '<pre class="wp-block-code" data-lang="JavaScript" tabindex="0" role="region" aria-label="JavaScript code"><code class="language-js">' ), 'code: language label from the code class' );
check( false !== strpos( $enh, '<pre tabindex="0" role="region" aria-label="Code">plain</pre>' ), 'code without a language: no label, still keyboard-scrollable' );
check( Content::enhance( $enh ) === $enh, 'enhancing twice changes nothing' );
check( '' === Content::enhance( '' ), 'empty content stays empty' );
check( ' class="a" role="note"' === Content::merge_attrs( ' class="a"', array( 'role' => 'note' ) ) && ' role="x"' === Content::merge_attrs( ' role="x"', array( 'role' => 'note' ) ), 'merge_attrs adds missing attributes and keeps the author\'s' );
check( ' src="a" alt="b" /' === Content::merge_attrs( ' src="a" /', array( 'alt' => 'b' ) ), 'merge_attrs keeps a self-closing slash last' );

/* ---------------- theme options ---------------- */

$merged = Blog::merge_theme_values( 'not-an-array', array( 'body_style' => 'fullscreen', 'header_style' => 'header-custom-266', '' => 'x', 5 => 'y' ) );
check(
	array(
		'body_style'          => 'fullscreen',
		'body_style_single'   => 'fullscreen',
		'body_style_mobile'   => 'fullscreen',
		'header_style'        => 'header-custom-266',
		'header_style_single' => 'header-custom-266',
		'header_style_mobile' => 'header-custom-266',
	) === $merged,
	'each value is written as <key>, <key>_single and <key>_mobile; bad keys are skipped',
	$merged
);
$kept = Blog::merge_theme_values( array( 'logo' => 'x', 'body_style_mobile' => 'boxed' ), array( 'body_style' => 'fullscreen' ) );
check( 'x' === $kept['logo'] && 'fullscreen' === $kept['body_style_mobile'], 'other per-post options are kept; a per-post mobile value is overridden' );
$theme = Blog::theme_values();
foreach ( array( 'body_style' => 'fullscreen', 'remove_margins' => '1', 'header_type' => 'custom', 'header_style' => 'header-custom-266', 'sidebar_position' => 'hide' ) as $key => $value ) {
	check( isset( $theme[ $key ] ) && $value === $theme[ $key ], "theme option {$key} = {$value}" );
}

/* ---------------- service mapping ---------------- */

$services = array(
	'shopify'   => array( 'match' => array( 'shopify', 'liquid', 'ecommerce' ) ),
	'wordpress' => array( 'match' => array( 'wordpress', 'woocommerce' ) ),
	'web-apps'  => array( 'match' => array( 'webflow', 'web-development' ) ),
	'uiux'      => array( 'match' => array( 'ux', 'design' ) ),
	'general'   => array( 'match' => array() ),
);
check( 'shopify' === Article::match_service( array( array( 'shopify-plus' ), array() ), $services ), 'category slug part matches (shopify-plus -> shopify)' );
check( 'wordpress' === Article::match_service( array( array( 'agency-services' ), array( 'woocommerce' ) ), $services ), 'tags decide when no category matches' );
check( 'shopify' === Article::match_service( array( array( 'ecommerce' ), array( 'woocommerce' ) ), $services ), 'categories beat tags' );
check( 'uiux' === Article::match_service( array( array( 'ui-ux' ), array() ), $services ), '"ux" matches the slug part in ui-ux' );
check( 'general' === Article::match_service( array( array( 'linux-tips', 'tech-trends' ), array() ), $services ), '"ux" does not match inside "linux"; no match falls back to general' );
check( 'web-apps' === Article::match_service( array( array( 'web-development' ), array() ), $services ), 'multi-word match words (web-development)' );

/* ---------------- settings and post meta ---------------- */

$d        = Settings::sanitize( array() );
$defaults = Settings::defaults();
ksort( $d );
ksort( $defaults );
check( $defaults === $d, 'an empty option gives the defaults (template on)', $d );
check( 1 === $d['enabled'] && 2 === $d['toc_depth'] && 3 === $d['related'], 'defaults: on, sections only (H2), three related posts' );
check( 3 === Settings::sanitize( array( 'toc_depth' => '3' ) )['toc_depth'], 'sub-sections (H2 and H3) stay available' );
$s = Settings::sanitize( array( 'enabled' => '0', 'toc_depth' => '9', 'related' => '42', 'share' => '', 'proof' => '<b>5.0</b> rating', 'proof_2' => str_repeat( 'x', 300 ) ) );
check( 0 === $s['enabled'] && 0 === $s['share'], 'a sent 0 switches a flag off' );
check( 2 === $s['toc_depth'], 'an invalid TOC depth falls back to the default (2)' );
check( 6 === $s['related'] && 0 === Settings::sanitize( array( 'related' => '-3' ) )['related'], 'related posts are clamped to 0..6' );
check( '5.0 rating' === $s['proof'] && 120 === strlen( $s['proof_2'] ), 'proof lines are plain text, at most 120 characters' );
check( 1 === Settings::sanitize( array( 'enabled' => array( 'x' ) ) )['enabled'], 'a non-scalar value keeps the default' );
check( 'wordpress' === Post_Meta::sanitize_service( 'WordPress' ) && '' === Post_Meta::sanitize_service( 'drupal' ) && '' === Post_Meta::sanitize_service( array() ), 'service meta: known keys only' );
check( 'off' === Post_Meta::sanitize_template( 'off' ) && '' === Post_Meta::sanitize_template( 'yes' ), 'template meta: "off" or nothing' );

/* ---------------- hero mosaic ---------------- */

$a = Article::mosaic( 8855 );
$b = Article::mosaic( 8855 );
$c = Article::mosaic( 8856 );
check( $a === $b && $a !== $c, 'the mosaic is stable per post and differs between posts' );
check( 0 === strpos( $a, '<svg class="avix-art-hero__mosaic"' ) && false !== strpos( $a, 'aria-hidden="true"' ) && false !== strpos( $a, 'focusable="false"' ), 'the mosaic is decorative SVG' );
check( strlen( $a ) < 5100 && substr_count( $a, '<rect' ) > 20, 'the mosaic stays small', strlen( $a ) );
check( 1 === preg_match( '~^<svg[^>]*>(<g>(<rect[^>]*/>)+</g>){3}</svg>$~', $a ), 'the squares twinkle in three groups: the SVG holds three non-empty <g>, nothing else' );

/* ---------------- image lightbox ---------------- */

$img = array( 'blockName' => 'core/image', 'attrs' => array( 'id' => 7, 'linkDestination' => 'none' ) );
$on  = Article::image_lightbox( $img );
check( isset( $on['attrs']['lightbox']['enabled'] ) && true === $on['attrs']['lightbox']['enabled'] && 7 === $on['attrs']['id'], 'an unlinked article image opens in the lightbox' );
check( true === Article::image_lightbox( array( 'blockName' => 'core/image', 'attrs' => array() ) )['attrs']['lightbox']['enabled'], 'no link setting counts as unlinked' );
$linked = array( 'blockName' => 'core/image', 'attrs' => array( 'linkDestination' => 'media' ) );
check( $linked === Article::image_lightbox( $linked ), 'a linked image keeps its link (no lightbox)' );
$chosen = array( 'blockName' => 'core/image', 'attrs' => array( 'lightbox' => array( 'enabled' => false ) ) );
check( $chosen === Article::image_lightbox( $chosen ), 'an author\'s lightbox choice on the block is kept' );
$para = array( 'blockName' => 'core/paragraph', 'attrs' => array() );
check( $para === Article::image_lightbox( $para ) && 'x' === Article::image_lightbox( 'x' ), 'other blocks and odd input pass through' );

/* ---------------- minified stylesheet ---------------- */

if ( ! defined( 'AVIX_EW_PATH' ) ) {
	define( 'AVIX_EW_PATH', $root . '/' );
}
check( Blog::MIN_CSS === Blog::css_file(), 'blog-article.min.css matches blog-article.css (else run php tests/blog-build.php)' );

echo "\n{$checks} checks, {$failures} failed\n";
exit( $failures ? 1 : 0 );
