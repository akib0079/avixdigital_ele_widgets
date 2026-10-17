<?php
/**
 * CLI tests for the REST bridge's pure helpers (no WordPress needed): the theme
 * options rebuilt from Elementor page settings, and the language code check.
 *
 *   php tests/seo-rest-bridge.php
 *
 * @package AvixWidgets
 */

define( 'ABSPATH', __DIR__ . '/' );
require dirname( __DIR__ ) . '/includes/seo/class-rest-bridge.php';

use AvixWidgets\SEO\Rest_Bridge;

$failures = 0;
$checks   = 0;

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
		echo '       ' . var_export( $info, true ) . "\n";
	}
}

$settings = array(
	'algenix_options_override_body_style'    => '1',
	'algenix_options_field_body_style'       => 'fullscreen',
	'algenix_options_override_remove_margins' => '1',
	'algenix_options_field_remove_margins'   => '1',
	'algenix_options_override_header_style'  => '1',
	'algenix_options_field_header_style'     => 'header-custom-266',
	'algenix_options_override_sidebar_position' => '',
	'custom_css'                             => '.x{}',
);
$out = Rest_Bridge::theme_options( array(), $settings );
check( array( 'body_style' => 'fullscreen', 'remove_margins' => '1', 'header_style' => 'header-custom-266' ) === $out, 'switches on: picked values copied', $out );

$out = Rest_Bridge::theme_options( array( 'sidebar_position' => 'left', 'footer_style' => 'footer-custom-2695' ), $settings );
check( ! isset( $out['sidebar_position'] ), 'switch off: key removed (inherit)', $out );
check( 'footer-custom-2695' === $out['footer_style'], 'key not in the settings: kept', $out );

$out = Rest_Bridge::theme_options( array( 'body_style' => 'boxed' ), array( 'algenix_options_override_body_style' => '1', 'algenix_options_field_body_style' => '' ) );
check( 'boxed' === $out['body_style'], 'switch on without a pick: earlier value kept', $out );

$out = Rest_Bridge::theme_options( array( 'body_style' => 'boxed' ), array( 'algenix_options_override_body_style' => '0' ) );
check( ! isset( $out['body_style'] ), 'switch "0": removed', $out );

check( 'nl' === Rest_Bridge::sanitize_lang( ' NL ' ), 'lang: nl' );
check( 'en-gb' === Rest_Bridge::sanitize_lang( 'en-GB' ), 'lang: en-gb' );
check( '' === Rest_Bridge::sanitize_lang( 'dutch' ), 'lang: rejects words' );
check( '' === Rest_Bridge::sanitize_lang( '<nl>' ), 'lang: rejects markup' );

echo "\n{$checks} checks, {$failures} failed\n";
exit( $failures ? 1 : 0 );
