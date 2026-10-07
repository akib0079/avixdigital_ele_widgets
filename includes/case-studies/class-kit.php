<?php
/**
 * Case-study kit: the shared device frames, mats, eyebrow, chips and arrows
 * every case-study widget prints, so the screenshots look like one system.
 *
 * Markup only; the look lives in assets/css/case-study-kit.css and the pixel
 * reveal in assets/js/case-study-kit.js (window.AvixCsk). Widgets list
 * 'case-study-kit' as a shared asset in the plugin's widget map, so both files
 * load before the widget's own CSS and JS.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Case_Studies;

defined( 'ABSPATH' ) || exit;

final class Kit {

	/** Device frames. */
	const DEVICES = array( 'browser', 'phone', 'plain' );

	/** Mats (the coloured panel a frame can sit on). */
	const MATS = array( 'paper', 'tint', 'white', 'dark' );

	/**
	 * Arrow paths on a 24px grid (CONVENTIONS "SVG/ICON MARKUP").
	 */
	const ARROWS = array(
		'ne' => 'M7 17 17 7M7 7h10v10',
		'e'  => 'M5 12h14M13 6l6 6-6 6',
		'w'  => 'M19 12H5M11 6l-6 6 6 6',
		's'  => 'M12 5v14M6 13l6 6 6-6',
		'n'  => 'M12 19V5M6 11l6-6 6 6',
	);

	/**
	 * A screenshot inside a device frame.
	 *
	 * Without a mat the frame is the outermost element:
	 *   <div class="avix-csk-frame avix-csk-frame--browser {class}" style="--csk-ratio: 2880 / 1800">
	 *     <div class="avix-csk-frame__bar" aria-hidden="true">…dots…<span class="avix-csk-frame__url">rehall.com</span></div>
	 *     <div class="avix-csk-frame__screen" data-csk-pixels>{img}</div>
	 *   </div>
	 * With a mat, the frame sits inside the mat (a phone's bezel and a mat's
	 * padding cannot share one element):
	 *   <div class="avix-csk-mat avix-csk-mat--paper {class}"><div class="avix-csk-frame …">…</div></div>
	 *
	 * The screen is position: relative, so absolutely positioned children
	 * passed in $img_html (hotspots) are placed over the screenshot.
	 *
	 * @param string $img_html Image markup (already escaped, e.g. from Kit::img()).
	 * @param string $device   browser | phone | plain.
	 * @param array  $o {
	 *     @type string $url_label Browser bar text, e.g. "rehall.com".
	 *     @type string $ratio     "W / H" from Kit::ratio(); reserves the space so nothing shifts.
	 *     @type string $class     Extra classes for the outermost element.
	 *     @type bool   $pixels    Add data-csk-pixels for the pixel-resolve reveal.
	 *     @type string $mat       '' | paper | tint | white | dark.
	 *     @type string $style     Extra inline CSS for the outermost element (e.g. "--csk-accent: #d4002a").
	 *     @type array  $attrs     Extra attributes for the outermost element (name => value).
	 * }
	 */
	public static function frame( string $img_html, string $device, array $o = array() ): string {
		$device = in_array( $device, self::DEVICES, true ) ? $device : 'browser';
		$mat    = (string) ( $o['mat'] ?? '' );
		$mat    = in_array( $mat, self::MATS, true ) ? $mat : '';
		$extra  = trim( (string) ( $o['class'] ?? '' ) );
		$ratio  = self::clean_ratio( (string) ( $o['ratio'] ?? '' ) );
		$label  = trim( (string) ( $o['url_label'] ?? '' ) );

		$frame_class = 'avix-csk-frame avix-csk-frame--' . $device;
		if ( '' === $mat && '' !== $extra ) {
			$frame_class .= ' ' . $extra;
		}

		$extra_css   = trim( (string) ( $o['style'] ?? '' ) );
		$frame_style = '' !== $ratio ? '--csk-ratio: ' . $ratio . ';' : '';
		if ( '' === $mat && '' !== $extra_css ) {
			$frame_style .= ' ' . $extra_css;
		}
		$frame_style = trim( $frame_style );
		$attrs       = '' === $mat ? self::attrs( (array) ( $o['attrs'] ?? array() ) ) : '';

		$html = '<div class="' . esc_attr( $frame_class ) . '"' . ( '' !== $frame_style ? ' style="' . esc_attr( $frame_style ) . '"' : '' ) . $attrs . '>';
		if ( 'browser' === $device ) {
			$html .= '<div class="avix-csk-frame__bar" aria-hidden="true">'
				. '<span class="avix-csk-frame__dots"><i></i><i></i><i></i></span>'
				. ( '' !== $label ? '<span class="avix-csk-frame__url">' . esc_html( $label ) . '</span>' : '' )
				. '</div>';
		}
		$html .= '<div class="avix-csk-frame__screen"' . ( ! empty( $o['pixels'] ) ? ' data-csk-pixels' : '' ) . '>'
			. $img_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the caller (Kit::img / wp_get_attachment_image).
			. '</div></div>';

		if ( '' !== $mat ) {
			$mat_class = trim( 'avix-csk-mat avix-csk-mat--' . $mat . ' avix-csk-mat--' . $device . ' ' . $extra );
			$mat_style = '' !== $extra_css ? ' style="' . esc_attr( $extra_css ) . '"' : '';
			$html      = '<div class="' . esc_attr( $mat_class ) . '"' . $mat_style . self::attrs( (array) ( $o['attrs'] ?? array() ) ) . '>' . $html . '</div>';
		}

		return $html;
	}

	/**
	 * Attachment image with case-study defaults: lazy, async decoding, the
	 * attachment's own alt text and explicit width/height (no layout shift).
	 * Pass array( 'loading' => 'eager', 'fetchpriority' => 'high' ) for an LCP image.
	 *
	 * @param int    $id    Attachment ID.
	 * @param string $size  Registered size, e.g. 'large' or 'full'.
	 * @param string $sizes The sizes attribute, e.g. "(max-width: 600px) 92vw, 1120px".
	 * @param array  $attr  Extra or overriding attributes (class, alt, loading …).
	 */
	public static function img( int $id, string $size, string $sizes, array $attr = array() ): string {
		if ( $id <= 0 || ! function_exists( 'wp_get_attachment_image' ) ) {
			return '';
		}
		$defaults = array(
			'class'    => 'avix-csk-img',
			'loading'  => 'lazy',
			'decoding' => 'async',
		);
		if ( '' !== $sizes ) {
			$defaults['sizes'] = $sizes;
		}
		if ( isset( $attr['class'] ) ) {
			$attr['class'] = trim( 'avix-csk-img ' . $attr['class'] );
		}
		$attr = array_merge( $defaults, $attr );

		// fetchpriority="high" only makes sense on an eager image (core warns otherwise).
		if ( 'eager' !== $attr['loading'] ) {
			unset( $attr['fetchpriority'] );
		}

		return (string) wp_get_attachment_image( $id, '' !== $size ? $size : 'large', false, $attr );
	}

	/**
	 * "2880 / 1800" from the attachment metadata, '' when unknown.
	 *
	 * @param int $id Attachment ID.
	 */
	public static function ratio( int $id ): string {
		if ( $id <= 0 || ! function_exists( 'wp_get_attachment_metadata' ) ) {
			return '';
		}
		$meta = wp_get_attachment_metadata( $id );
		$w    = absint( is_array( $meta ) ? ( $meta['width'] ?? 0 ) : 0 );
		$h    = absint( is_array( $meta ) ? ( $meta['height'] ?? 0 ) : 0 );
		if ( ! $w || ! $h ) {
			$src = wp_get_attachment_image_src( $id, 'full' );
			$w   = absint( is_array( $src ) ? ( $src[1] ?? 0 ) : 0 );
			$h   = absint( is_array( $src ) ? ( $src[2] ?? 0 ) : 0 );
		}
		return ( $w && $h ) ? $w . ' / ' . $h : '';
	}

	/**
	 * Eyebrow with the small accent pixel.
	 *
	 * @param string $text  Plain text (escaped here).
	 * @param string $class Widget class, e.g. "avix-csh__eyebrow".
	 */
	public static function eyebrow( string $text, string $class ): string {
		$text = trim( $text );
		if ( '' === $text ) {
			return '';
		}
		return '<p class="' . esc_attr( trim( $class . ' avix-csk-eyebrow' ) ) . '">'
			. '<span class="avix-csk-eyebrow__px" aria-hidden="true"></span>'
			. '<span class="avix-csk-eyebrow__text">' . esc_html( $text ) . '</span></p>';
	}

	/**
	 * Inline arrow icon: ne ↗, e →, s ↓ (plus w ←, n ↑).
	 *
	 * @param string $dir ne | e | s | w | n.
	 */
	public static function arrow( string $dir ): string {
		$dir = isset( self::ARROWS[ $dir ] ) ? $dir : 'ne';
		return '<svg class="avix-csk-arrow avix-csk-arrow--' . $dir . '" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="' . self::ARROWS[ $dir ] . '"/></svg>';
	}

	/**
	 * Chip list. Links are matched by index or by label; '' means plain chip.
	 *
	 * @param array  $labels Chip labels (plain text).
	 * @param string $class  Widget class for the list, e.g. "avix-csc__chips".
	 * @param array  $links  Optional URLs, keyed like $labels or by label.
	 */
	public static function chips( array $labels, string $class, array $links = array() ): string {
		$items = '';
		foreach ( $labels as $key => $label ) {
			$label = trim( wp_strip_all_tags( (string) $label ) );
			if ( '' === $label ) {
				continue;
			}
			$url = '';
			if ( isset( $links[ $key ] ) && is_string( $links[ $key ] ) ) {
				$url = $links[ $key ];
			} elseif ( isset( $links[ $label ] ) && is_string( $links[ $label ] ) ) {
				$url = $links[ $label ];
			}
			$url = '' !== trim( $url ) ? esc_url( $url ) : '';

			$items .= '<li class="avix-csk-chips__item">';
			$items .= '' !== $url
				? '<a class="avix-csk-chip avix-csk-chip--link" href="' . $url . '">' . esc_html( $label ) . '</a>'
				: '<span class="avix-csk-chip">' . esc_html( $label ) . '</span>';
			$items .= '</li>';
		}
		if ( '' === $items ) {
			return '';
		}
		return '<ul class="' . esc_attr( trim( $class . ' avix-csk-chips' ) ) . '" role="list">' . $items . '</ul>';
	}

	/**
	 * "2880 / 1800" (or "16/10", "1.6") → safe CSS aspect-ratio value.
	 *
	 * @param string $ratio Raw ratio.
	 */
	private static function clean_ratio( string $ratio ): string {
		$ratio = trim( $ratio );
		if ( preg_match( '#^(\d+(?:\.\d+)?)\s*/\s*(\d+(?:\.\d+)?)$#', $ratio, $m ) && (float) $m[1] > 0 && (float) $m[2] > 0 ) {
			return $m[1] . ' / ' . $m[2];
		}
		if ( preg_match( '#^\d+(?:\.\d+)?$#', $ratio ) && (float) $ratio > 0 ) {
			return $ratio;
		}
		return '';
	}

	/**
	 * name => value pairs → escaped attribute string.
	 *
	 * @param array $attrs Attributes.
	 */
	private static function attrs( array $attrs ): string {
		$out = '';
		foreach ( $attrs as $name => $value ) {
			$name = preg_replace( '/[^a-zA-Z0-9_:-]/', '', (string) $name );
			if ( '' === $name || 'class' === $name || 'style' === $name ) {
				continue;
			}
			$out .= true === $value ? ' ' . $name : ' ' . $name . '="' . esc_attr( (string) $value ) . '"';
		}
		return $out;
	}
}
