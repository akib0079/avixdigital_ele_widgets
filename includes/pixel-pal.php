<?php
/**
 * The Avix pixel character, shared by the widgets that feature it.
 *
 * Markup only; the look lives in assets/css/pixel-pal.css and the moves in
 * assets/js/pixel-pal.js (window.AvixPal). Widgets that use it list
 * 'pixel-pal' as a shared asset in the plugin's widget map, so both files
 * load before the widget's own CSS and JS.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets;

defined( 'ABSPATH' ) || exit;

final class Pixel_Pal {

	/**
	 * Small props drawn in the same 10×12 grid. They use --pal-prop, so they
	 * stand out against the orange body.
	 */
	const PROPS = array(
		'book'   => '<rect class="avix-pal__prop" x="1" y="4" width="3.5" height="3"/><rect class="avix-pal__prop" x="5.5" y="4" width="3.5" height="3"/>',
		'laptop' => '<rect class="avix-pal__prop" x="1.5" y="3.5" width="7" height="3.5"/><rect class="avix-pal__prop" x="0" y="7" width="10" height="1"/>',
		'flag'   => '<rect class="avix-pal__prop avix-pal__prop--pole" x="9" y="-5" width="1" height="9"/><rect class="avix-pal__prop avix-pal__prop--cloth" x="10" y="-5" width="4" height="3"/>',
	);

	/**
	 * @param array $args {
	 *     @type string $class Extra classes for the root, e.g. "avix-st__pal".
	 *     @type bool   $hi    Include the pixel "hi" that pops up with .is-hi.
	 *     @type string $prop  '', 'book', 'laptop' or 'flag'.
	 *     @type array  $attrs Extra attributes for the root (name => value).
	 * }
	 */
	public static function render( array $args = array() ): string {
		$class = trim( 'avix-pal ' . ( $args['class'] ?? '' ) );
		$prop  = (string) ( $args['prop'] ?? '' );
		$prop  = isset( self::PROPS[ $prop ] ) ? $prop : '';
		if ( '' !== $prop ) {
			$class .= ' has-prop-' . $prop;
		}

		$attrs = '';
		foreach ( (array) ( $args['attrs'] ?? array() ) as $name => $value ) {
			$attrs .= ' ' . esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
		}

		$html  = '<span class="' . esc_attr( $class ) . '" data-avix-pal aria-hidden="true"' . $attrs . '>';
		if ( ! empty( $args['hi'] ) ) {
			$html .= '<svg class="avix-pal__hi" viewBox="0 0 5 5" focusable="false">'
				. '<g class="avix-pal__hi-h"><rect x="0" y="0" width="1" height="5"/><rect x="1" y="2" width="2" height="1"/><rect x="2" y="3" width="1" height="2"/></g>'
				. '<g class="avix-pal__hi-i"><rect x="4" y="0" width="1" height="1"/><rect x="4" y="2" width="1" height="3"/></g>'
				. '</svg>';
		}
		$html .= '<span class="avix-pal__sprite"><svg viewBox="0 0 10 12" focusable="false">'
			. '<rect class="avix-pal__leg avix-pal__leg--l" x="2" y="7" width="2" height="5"/>'
			. '<rect class="avix-pal__leg avix-pal__leg--r" x="6" y="7" width="2" height="5"/>'
			. '<rect class="avix-pal__body" x="2" y="3" width="6" height="4"/>'
			. '<rect class="avix-pal__head" x="3" y="0" width="4" height="3"/>'
			. '<rect class="avix-pal__arm avix-pal__arm--l" x="0" y="3" width="2" height="3"/>'
			. '<rect class="avix-pal__arm avix-pal__arm--r" x="8" y="3" width="2" height="3"/>'
			. ( '' !== $prop ? self::PROPS[ $prop ] : '' )
			. '</svg></span></span>';

		return $html;
	}
}
