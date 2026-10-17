<?php
/**
 * CLI tests for the Case Study Stack brand marks (no WordPress needed): which
 * logo every tool in the bundled case studies gets, tricky names that must
 * not match, pairs, typefaces, the logo registry against the files in
 * assets/images/brands/, and the markup of one tile, two marks and the
 * pixel square.
 *
 *   php tests/case-study-stack-marks.php
 *
 * @package AvixWidgets
 */

namespace Elementor {
	// Just enough of Elementor to load the widget class.
	class Widget_Base {}
	class Controls_Manager {}
	class Group_Control_Typography {}
	class Utils {}
}

namespace AvixWidgets\Case_Studies {
	trait Source {}
}

namespace {
	use AvixWidgets\Widgets\Brand_Icons;
	use AvixWidgets\Widgets\Case_Study_Stack as Stack;

	define( 'ABSPATH', __DIR__ . '/' );
	define( 'AVIX_EW_URL', 'https://example.test/wp-content/plugins/avix-elementor-widgets/' );

	/* ---------------- minimal WordPress stand-ins ---------------- */

	function esc_attr( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
	function esc_url( $url ) {
		return htmlspecialchars( (string) $url, ENT_QUOTES, 'UTF-8' );
	}

	$root = dirname( __DIR__ );
	require $root . '/includes/brand-icons.php';
	require $root . '/includes/widgets/class-case-study-stack.php';

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

	/**
	 * @param string   $name     Tool name.
	 * @param string[] $expected Keys.
	 * @param string   $text     Tool description.
	 */
	function marks_are( $name, array $expected, $text = '' ) {
		$got = Stack::marks_for( $name, $text );
		check( $expected === $got, '"' . $name . '"' . ( '' !== $text ? ' ("' . $text . '")' : '' ) . ' => ' . ( $expected ? implode( ' + ', $expected ) : 'pixel square' ), $got );
	}

	/* ---------------- every tool in the bundled case studies ---------------- */

	$expected = array(
		// Rehall.
		'Shopify (Online Store 2.0)'                  => array( 'shopify' ),
		'Liquid'                                      => array( 'liquid' ),
		'Rebuy'                                       => array( 'rebuy' ),
		'Globo Smart Product Filters'                 => array( 'globo-filters' ),
		'Klaviyo'                                     => array( 'klaviyo' ),
		'Gorgias'                                     => array( 'gorgias' ),
		'Judge.me & Trustpilot'                       => array( 'judgeme', 'trustpilot' ),
		'Returnista'                                  => array( 'returnista' ),
		'GSAP'                                        => array( 'gsap' ),
		// Ovabalance.
		'Custom JavaScript'                           => array( 'javascript' ),
		'Juo Subscriptions'                           => array( 'juo' ),
		'GSAP & ScrollTrigger'                        => array( 'gsap' ),
		'Swiper'                                      => array( 'swiper' ),
		// Kampeerwinkel Roermond.
		'JavaScript web components'                   => array( 'javascript' ),
		'Shop Pay & Google Pay'                       => array( 'shoppay', 'googlepay' ),
		'Shopify Checkout Blocks'                     => array( 'checkout-blocks' ),
		'Judge.me'                                    => array( 'judgeme' ),
		'Smile.io'                                    => array( 'smile' ),
		'CookieYes'                                   => array( 'cookieyes' ),
		// Flow Storage.
		'Webflow Designer & Interactions'             => array( 'webflow' ),
		'Webflow CMS'                                 => array( 'webflow' ),
		'Figma'                                       => array( 'figma' ),
		'Elfsight Form Builder'                       => array( 'elfsight' ),
		'Space Grotesk'                               => array( Stack::TYPEFACE ),
		// Products for Home.
		'WordPress & WooCommerce'                     => array( 'wordpress', 'woocommerce' ),
		'Bricks Builder'                              => array( 'bricks' ),
		'Custom Bricks elements (pfh-bricks-widgets)' => array( 'bricks' ),
		'FunnelKit Funnel Builder Pro'                => array( 'funnelkit' ),
		'FunnelKit Cart'                              => array( 'funnelkit' ),
		'Woo Discount Rules'                          => array( 'woo-discount-rules' ),
		'WPLoyalty'                                   => array( 'wployalty' ),
		'bol.checkout gateway'                        => array( 'bol' ),
		'WebwinkelKeur'                               => array( 'webwinkelkeur' ),
		'LiteSpeed & FlyingPress'                     => array( 'litespeed', 'flyingpress' ),
	);
	foreach ( $expected as $name => $keys ) {
		marks_are( $name, $keys );
	}

	// The data files themselves, parsed like Case_Study::prepare(): no tool falls back to the pixel square.
	$tools = array();
	foreach ( glob( $root . '/includes/case-studies/data/*.json' ) as $file ) {
		$json = json_decode( (string) file_get_contents( $file ), true );
		foreach ( (array) ( $json['meta']['stack'] ?? array() ) as $line ) {
			$line = trim( (string) preg_replace( '/\|\s*observed\s*$/i', '', (string) $line ) );
			$at   = strpos( $line, ':' );
			$name = trim( false === $at ? $line : substr( $line, 0, $at ) );
			$text = false === $at ? '' : trim( substr( $line, $at + 1 ) );
			if ( '' !== $name ) {
				$tools[ basename( $file, '.json' ) . ': ' . $name ] = array( $name, $text );
			}
		}
	}
	check( count( $tools ) >= 40, 'the five case studies list their tools', count( $tools ) );
	foreach ( $tools as $label => $tool ) {
		$got = Stack::marks_for( $tool[0], $tool[1] );
		check( isset( $expected[ $tool[0] ] ) && $expected[ $tool[0] ] === $got, "{$label}: matches the expected marks (add new tools to this test)", $got );
	}

	/* ---------------- specific before generic, pairs, qualifiers ---------------- */

	marks_are( 'Checkout Blocks', array() );
	marks_are( 'WooCommerce Checkout Blocks', array( 'woocommerce' ) );
	marks_are( 'Checkout Blocks for WooCommerce', array( 'woocommerce' ) );
	marks_are( 'GSAP ScrollTrigger', array( 'gsap' ) );
	marks_are( 'Webflow Interactions (scroll trigger)', array( 'webflow' ) );
	marks_are( 'Shopify Plus', array( 'shopify' ) );
	marks_are( 'SHOPIFY', array( 'shopify' ) );
	marks_are( 'Klaviyo for Shopify', array( 'klaviyo' ) );
	marks_are( 'Custom theme for Shopify', array( 'shopify' ) );
	marks_are( 'Discount Rules for WooCommerce', array( 'woo-discount-rules' ) );
	marks_are( 'FunnelKit Cart for WooCommerce', array( 'funnelkit' ) );
	marks_are( 'WooCommerce', array( 'woocommerce' ) );
	marks_are( 'WooCommerce & WordPress', array( 'woocommerce', 'wordpress' ) );
	marks_are( 'Klaviyo, Gorgias and Rebuy', array( 'klaviyo', 'gorgias' ) );
	marks_are( 'GreenSock', array( 'gsap' ) );
	marks_are( 'ScrollTrigger', array( 'gsap' ) );
	marks_are( 'Judgeme', array( 'judgeme' ) );
	marks_are( 'Trustpilot', array( 'trustpilot' ) );
	marks_are( 'Smile', array( 'smile' ) );
	marks_are( 'Smile Loyalty', array( 'smile' ) );
	marks_are( 'Shop Pay', array( 'shoppay' ) );
	marks_are( 'GPay', array( 'googlepay' ) );
	marks_are( 'bol.com', array( 'bol' ) );
	marks_are( 'Bol', array( 'bol' ) );
	marks_are( 'Vanilla JS', array( 'javascript' ) );
	marks_are( 'LiteSpeed Cache', array( 'litespeed' ) );
	marks_are( 'Swiper.js', array( 'swiper' ) );
	marks_are( 'Elementor Pro', array( 'elementor' ) );
	marks_are( 'React', array( 'react' ) );
	marks_are( 'Next.js', array( 'nextjs' ) );
	marks_are( 'NextJS', array( 'nextjs' ) );
	marks_are( 'Next', array( 'nextjs' ) );
	marks_are( 'Node.js', array( 'nodejs' ) );
	marks_are( 'Next.js & Node.js', array( 'nextjs', 'nodejs' ) );
	marks_are( 'Next 14', array( 'nextjs' ) );
	marks_are( 'Next (App Router)', array( 'nextjs' ) );
	marks_are( 'Node 20', array( 'nodejs' ) );
	marks_are( 'Node / Express', array( 'nodejs' ) );
	marks_are( 'WooCommerce Discount Rules', array( 'woo-discount-rules' ) );
	marks_are( 'Shopify discount rules', array( 'shopify' ) );
	marks_are( 'Smart Product Filter for WooCommerce', array( 'woocommerce' ) );
	marks_are( 'Shopify Liquid', array( 'shopify', 'liquid' ) );
	marks_are( 'Bricks', array( 'bricks' ) );

	/* ---------------- names that must not match ---------------- */

	foreach ( array(
		'Liquidity planning',
		'Smiley support widget',
		'Customer smile survey',
		'Symbol library',
		'Bolt payments',
		'Bricklayer grid',
		'Java backend',
		'Reactive forms',
		'Next-day delivery app',
		'Nodemailer',
		'Shopping feed',
		'GPS tracker',
		'Big Pay',
		'Custom checkout',
		'Cart & Checkout Blocks',
		'Scroll trigger animations',
		'Discount codes',
		'Discount rules',
		'Custom discount rules',
		'Smart product filters',
		'Liquid Web',
		'Bricks and mortar POS',
		'Bricks-and-mortar stores',
		'Inter-store stock sync',
		'Next day',
		'Trust badges',
		'Interactions',
		'Font Awesome icons',
		'Hotjar',
		'',
	) as $name ) {
		marks_are( $name, array() );
	}

	/* ---------------- typefaces ---------------- */

	marks_are( 'Inter', array( Stack::TYPEFACE ) );
	marks_are( 'Inter Tight', array( Stack::TYPEFACE ) );
	marks_are( 'Google Fonts', array( Stack::TYPEFACE ) );
	marks_are( 'Brand serif', array( Stack::TYPEFACE ), 'the brand typeface' );
	marks_are( 'Custom preload', array(), 'font and image preloading' );
	marks_are( 'Perfmatters', array(), 'delays scripts and preloads the brand fonts' );
	marks_are( 'Cloudflare', array(), 'serves the body font from the edge' );
	marks_are( 'Shopify', array( 'shopify' ), 'the brand typeface' );
	check( 'Space Grotesk' === Stack::typeface_family( 'Space Grotesk' ), 'typeface_family: a family name is kept' );
	check( 'Space Grotesk' === Stack::typeface_family( 'Space Grotesk (variable)' ), 'typeface_family: a note in brackets is dropped' );
	check( '' === Stack::typeface_family( 'Google Fonts' ), 'typeface_family: a font service is not a family' );
	check( '' === Stack::typeface_family( "Evil'; } body { color: red" ), 'typeface_family: anything but letters, digits, spaces and hyphens is refused' );

	/* ---------------- the logo registry and its files ---------------- */

	$dir   = $root . '/assets/images/brands/';
	$files = array();
	foreach ( Stack::LOGOS as $key => $logo ) {
		list( $file, $mode, $width, $height ) = $logo;
		$files[] = $file;
		check( is_file( $dir . $file ) && filesize( $dir . $file ) > 0, "logo {$key}: {$file} exists" );
		check( in_array( $mode, array( 'glyph', 'pill', 'tile' ), true ), "logo {$key}: mode is glyph, pill or tile", $mode );
		check( 'tile' === $mode ? ( 36 === $width && 36 === $height ) : ( $width >= 16 && $width <= 32 && $height >= 12 && $height <= 28 ), "logo {$key}: size fits the 36px tile", array( $width, $height ) );
		check( isset( Stack::MARKS[ $key ] ), "logo {$key}: has a matching rule" );
		check( (bool) preg_match( '/^[a-z0-9-]+\.(svg|webp)$/', $file ) && 0 === strpos( $file, $key . '.' ), "logo {$key}: file named after its key" );
		if ( 'tile' === $mode && '.webp' === substr( $file, -5 ) ) {
			$size = @getimagesize( $dir . $file );
			check( is_array( $size ) && $size[0] >= 3 * $width && $size[1] >= 3 * $height, "logo {$key}: raster is sharp on a 3x screen (at least 108 px)", $size ? array( $size[0], $size[1] ) : $size );
		}
	}
	$on_disk = array_map( 'basename', array_merge( glob( $dir . '*.svg' ), glob( $dir . '*.webp' ) ) );
	sort( $on_disk );
	sort( $files );
	check( $on_disk === $files, 'every logo file in assets/images/brands is in LOGOS, and the other way round', array_values( array_diff( $on_disk, $files ) + array_diff( $files, $on_disk ) ) );
	check( is_file( $dir . 'README.md' ), 'assets/images/brands/README.md lists the sources' );

	foreach ( glob( $dir . '*.svg' ) as $svg ) {
		$xml = (string) file_get_contents( $svg );
		$ok  = 0 === strpos( ltrim( $xml ), '<svg' )
			&& ! preg_match( '/<script|<foreignObject|<style|<image|\son[a-z]+\s*=|href\s*=|url\(\s*[\'"]?(?!#)/i', $xml );
		check( $ok, basename( $svg ) . ': plain shapes only (no script, styles, events or outside references)' );
	}
	foreach ( glob( $dir . '*.webp' ) as $webp ) {
		$head = (string) file_get_contents( $webp, false, null, 0, 16 );
		check( 'RIFF' === substr( $head, 0, 4 ) && 'WEBP' === substr( $head, 8, 4 ) && filesize( $webp ) < 8192, basename( $webp ) . ': a small WebP' );
	}

	// Every key the matcher can return is drawable: a logo file, a Brand_Icons mark or the typeface tile.
	foreach ( array_keys( Stack::MARKS ) as $key ) {
		check( isset( Stack::LOGOS[ $key ] ) || '' !== Brand_Icons::svg( $key ), "rule {$key}: has a logo file or an inline Brand_Icons mark" );
	}
	check( ! isset( Stack::MARKS[ Stack::TYPEFACE ] ) && ! isset( Stack::LOGOS[ Stack::TYPEFACE ] ), 'the typeface tile is CSS, not a logo' );
	check( 0 === strpos( Brand_Icons::svg( 'shopify' ), '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="#95BF47"' ), 'Brand_Icons output is unchanged' );

	/* ---------------- markup ---------------- */

	$widget = new Stack();
	$icon   = new ReflectionMethod( Stack::class, 'icon' );
	if ( PHP_VERSION_ID < 80100 ) {
		$icon->setAccessible( true );
	}

	$one = $icon->invoke( $widget, 'Klaviyo', 'Newsletter sign-up' );
	check( '' === $one[0], 'a glyph logo keeps the plain tile', $one[0] );
	check( (bool) preg_match( '#^<img class="avix-cst__logo no-pixel-reveal" src="https://example\.test/wp-content/plugins/avix-elementor-widgets/assets/images/brands/klaviyo\.svg" alt="" width="19" height="19" loading="lazy" decoding="async" style="--cst-logo-w:19px;--cst-logo-h:19px;">$#', $one[1] ), 'a logo is a decorative, lazy <img> outside the pixel reveal', $one[1] );

	$tile = $icon->invoke( $widget, 'Bricks Builder', '' );
	check( ' avix-cst__icon--tile' === $tile[0] && false !== strpos( $tile[1], 'width="36" height="36"' ) && false === strpos( $tile[1], 'style=' ), 'an app icon fills the tile', $tile );

	$judge = $icon->invoke( $widget, 'Judge.me', '' );
	check( ' avix-cst__icon--tile' === $judge[0] && false !== strpos( $judge[1], 'judgeme.svg' ), 'the Judge.me square fills the tile, like an app icon', $judge );
	$reviews = $icon->invoke( $widget, 'Judge.me & Trustpilot', '' );
	check( 0 === strpos( $reviews[1], '<span class="avix-cst__mark avix-cst__mark--a avix-cst__mark--tile"><img ' ), 'pair: Judge.me is a small square of its own', $reviews[1] );

	$pair = $icon->invoke( $widget, 'WordPress & WooCommerce', '' );
	check( ' avix-cst__icon--pair' === $pair[0], 'two marks share the tile', $pair[0] );
	check( (bool) preg_match( '#^<span class="avix-cst__mark avix-cst__mark--a"><svg [^>]*>.*</svg></span><span class="avix-cst__mark avix-cst__mark--b"><img [^>]*woocommerce\.webp[^>]*></span>$#s', $pair[1] ), 'pair: inline WordPress first, the WooCommerce logo on the badge', $pair[1] );

	$pill = $icon->invoke( $widget, 'Shop Pay & Google Pay', '' );
	check( ' avix-cst__icon--pair avix-cst__icon--pill' === $pill[0], 'a pair with a pill sizes the first mark to it', $pill[0] );
	check( false !== strpos( $pill[1], 'avix-cst__mark avix-cst__mark--b avix-cst__mark--pill"><img' ), 'Google Pay is its own badge', $pill[1] );
	$pill_first = $icon->invoke( $widget, 'Google Pay & Shop Pay', '' );
	check( Stack::marks_for( 'Google Pay & Shop Pay' ) === array( 'googlepay', 'shoppay' ) && $pill === $pill_first, 'a pill named first still goes on the badge', $pill_first );
	$tiles = $icon->invoke( $widget, 'Rebuy & Returnista', '' );
	check( ' avix-cst__icon--pair' === $tiles[0] && false !== strpos( $tiles[1], 'avix-cst__mark avix-cst__mark--b avix-cst__mark--tile"><img' ), 'an app icon second fills the badge, no pill sizing', $tiles );

	$type = $icon->invoke( $widget, 'Space Grotesk', 'the brand typeface' );
	check( ' avix-cst__icon--type' === $type[0] && '<span class="avix-cst__type" style="font-family:&#039;Space Grotesk&#039;,var(--cst-font-display);">Aa</span>' === $type[1], 'a typeface is "Aa" set in it', $type );

	$none = $icon->invoke( $widget, 'Hotjar', 'heatmaps' );
	check( array( ' avix-cst__icon--px', '<i></i>' ) === $none, 'no mark keeps the pixel square', $none );

	/* ---------------- stylesheet ---------------- */

	$css = (string) file_get_contents( $root . '/assets/css/case-study-stack.css' );
	foreach ( array( '.avix-cst__icon--tile', '.avix-cst__icon--pair', '.avix-cst__icon--pill', '.avix-cst__mark--b', '.avix-cst__mark--pill', '.avix-cst__type', 'img.avix-cst__logo', '--cst-logo-w' ) as $selector ) {
		check( false !== strpos( $css, $selector ), "case-study-stack.css styles {$selector}" );
	}

	echo "\n{$checks} checks, {$failures} failed\n";
	exit( $failures ? 1 : 0 );
}
