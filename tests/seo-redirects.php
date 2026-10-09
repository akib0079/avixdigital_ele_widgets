<?php
/**
 * CLI tests for the 404-only redirect map (no WordPress needed).
 *
 *   php tests/seo-redirects.php
 *
 * @package AvixWidgets
 */

define( 'ABSPATH', __DIR__ . '/' );
require dirname( __DIR__ ) . '/includes/seo/class-redirects.php';

use AvixWidgets\SEO\Redirects;

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

$map = Redirects::map(); // No WordPress: the bundled map, cleaned.

/* ---------------- the plan's map (A8) ---------------- */

$expect = array(
	'/services/'                               => '/service/',
	'/about-us/'                               => '/about-avixdigital/',
	'/about-us-1/'                             => '/about-avixdigital/',
	'/contact-us-2/'                           => '/contact/',
	'/avix-digital-client-portal-seamless-project-management/' => '/why-your-digital-agency-needs-a-custom-client-portal-and-how-we-built-ours/',
	'/how-to-choose-the-right-digital-agency/' => '/web-development-agency-vs-freelancer/',
);
foreach ( $expect as $from => $to ) {
	$hit = Redirects::match( $from, $map );
	check( 301 === ( $hit['code'] ?? 0 ) && $to === ( $hit['location'] ?? '' ), "301 {$from} -> {$to}", $hit );
}
$hit = Redirects::match( '/ai-features/', $map );
check( array( 'code' => 410 ) === $hit, '410 for /ai-features/', $hit );
check( 7 === count( $map ), 'the map has exactly the seven plan entries', array_keys( $map ) );

/* ---------------- one hop: no target is itself a source ---------------- */

foreach ( $map as $from => $to ) {
	if ( is_string( $to ) ) {
		check( ! isset( $map[ $to ] ), "target {$to} is not redirected again (one hop)" );
	}
}

/* ---------------- matching rules ---------------- */

check( '/service/' === ( Redirects::match( '/services', $map )['location'] ?? '' ), 'missing trailing slash still matches' );
check( '/service/' === ( Redirects::match( '/Services/', $map )['location'] ?? '' ), 'case-insensitive' );
check( '/service/?utm_source=x&a=1' === ( Redirects::match( '/services/?utm_source=x&a=1', $map )['location'] ?? '' ), 'query string is kept' );
check( array() === Redirects::match( '/service/', $map ), 'the real /service/ page is not in the map' );
check( array() === Redirects::match( '/', $map ), 'home is never matched' );
check( array() === Redirects::match( '/services/web/', $map ), 'deeper paths are not matched' );
check( array() === Redirects::match( '/about-us-2/', $map ), 'unknown paths are not matched' );

/* ---------------- sub-folder installs ---------------- */

$hit = Redirects::match( '/wp/services/?x=1', $map, '/wp/' );
check( 301 === ( $hit['code'] ?? 0 ) && '/service/' === $hit['path'] && 'x=1' === $hit['query'] && '/wp/service/?x=1' === $hit['location'], 'sub-folder: path relative to the site, location from the server root', $hit );
check( array() === Redirects::match( '/services/', $map, '/wp/' ), 'sub-folder: a path outside the site is ignored' );

/* ---------------- clean_map (filter input) ---------------- */

$clean = Redirects::clean_map(
	array(
		'Old-Page'          => '/new-page',
		'/loop/'            => '/loop/',
		'/'                 => '/anything/',
		'/external/'        => 'https://example.com/',
		'/protocol-rel/'    => '//example.com/x/',
		'/gone/'            => '410',
		'/relative-target/' => 'new-page/',
		'/array/'           => array( 301, '/x/' ),
	)
);
check(
	array(
		'/old-page/' => '/new-page/',
		'/gone/'     => 410,
	) === $clean,
	'clean_map: normalises, keeps 410, drops loops, home, external, protocol-relative, relative and malformed targets',
	$clean
);

echo "\n{$checks} checks, {$failures} failed\n";
exit( $failures ? 1 : 0 );
