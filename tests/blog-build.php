<?php
/**
 * Builds assets/css/blog-article.min.css from assets/css/blog-article.css.
 *
 *   php tests/blog-build.php          build, then print the sizes
 *   php tests/blog-build.php --check  exit 1 when the minified file is missing or stale
 *
 * The first line of the minified file carries the md5 of its source;
 * Blog::css_file() serves it only while that matches, so an edit to
 * blog-article.css without a rebuild falls back to the readable file
 * instead of shipping stale styles. Run this after every CSS change.
 *
 * The minifier is deliberately conservative: comments go, whitespace
 * collapses, and spaces next to { } ; , > and after : and ( and before )
 * go. Strings and url() are never touched, nor are spaces around + and -
 * (calc()) or before ":" (a descendant combinator such as ".a :is(b)").
 *
 * @package AvixWidgets
 */

$root = dirname( __DIR__ );
$src  = $root . '/assets/css/blog-article.css';
$dest = $root . '/assets/css/blog-article.min.css';

if ( ! is_readable( $src ) ) {
	fwrite( STDERR, "Missing {$src}\n" );
	exit( 1 );
}

$hash = md5_file( $src );

if ( in_array( '--check', $argv, true ) ) {
	$line = is_readable( $dest ) ? (string) fgets( fopen( $dest, 'r' ) ) : '';
	$ok   = false !== strpos( $line, 'source:' . $hash );
	echo $ok ? "blog-article.min.css is current\n" : "blog-article.min.css is missing or stale: run php tests/blog-build.php\n";
	exit( $ok ? 0 : 1 );
}

/**
 * @param string $css Stylesheet.
 */
function avix_blog_min_css( string $css ): string {
	$keep = array();
	// Left to right: a comment that starts first wins over a quote inside it, and the reverse.
	$css = preg_replace_callback(
		'~/\*.*?\*/|"(?:[^"\\\\\n]|\\\\.)*"|\'(?:[^\'\\\\\n]|\\\\.)*\'|url\(\s*(?:"[^"]*"|\'[^\']*\'|[^)]*)\s*\)~s',
		static function ( $m ) use ( &$keep ) {
			if ( 0 === strpos( $m[0], '/*' ) ) {
				return ' ';
			}
			$keep[] = $m[0];
			return "\x01" . ( count( $keep ) - 1 ) . "\x02";
		},
		$css
	);
	$css = preg_replace( '~\s+~', ' ', $css );
	$css = preg_replace( '~\s*([{};,>])\s*~', '$1', $css );
	$css = preg_replace( '~:\s+~', ':', $css );
	$css = preg_replace( '~\(\s+~', '(', $css );
	$css = preg_replace( '~\s+\)~', ')', $css );
	$css = str_replace( ';}', '}', $css );
	$css = trim( $css );
	return preg_replace_callback(
		"~\x01(\d+)\x02~",
		static function ( $m ) use ( $keep ) {
			return $keep[ (int) $m[1] ];
		},
		$css
	);
}

$css = (string) file_get_contents( $src );
$min = avix_blog_min_css( $css );

// Sanity: the same number of rules and declarations survive.
foreach ( array( '{', '}', '!important' ) as $token ) {
	$before = substr_count( preg_replace( '~/\*.*?\*/~s', '', $css ), $token );
	$after  = substr_count( $min, $token );
	if ( $before !== $after ) {
		fwrite( STDERR, "Token count changed for {$token}: {$before} -> {$after}\n" );
		exit( 1 );
	}
}

$header = "/*! Avix Digital · Article page. Built from blog-article.css by tests/blog-build.php; source:{$hash} */\n";
file_put_contents( $dest, $header . $min . "\n" );

printf(
	"blog-article.css      %6.1f KB raw  %5.1f KB gzip\nblog-article.min.css  %6.1f KB raw  %5.1f KB gzip\n",
	strlen( $css ) / 1024,
	strlen( gzencode( $css, 9 ) ) / 1024,
	filesize( $dest ) / 1024,
	strlen( gzencode( (string) file_get_contents( $dest ), 9 ) ) / 1024
);
