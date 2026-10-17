<?php
/**
 * Table of contents: stable heading ids and the TOC model, built on the
 * server from the rendered post content, so links like
 * /post/#compare-the-scope work without JavaScript and nothing moves when
 * the page loads.
 *
 * - Every H2 and H3 gets an id (an existing id is kept): the slug of its text
 *   (sanitize_title, ASCII only), made unique on the page with -2, -3...
 * - The TOC lists H2s and, at depth 3, the H3s under them. Left out: H3s inside
 *   the FAQ group (.avix-faq; its H2 stays), headings with the class
 *   avix-toc-skip, and empty headings.
 *
 * No DOM parser: a small tag scanner keeps the content byte for byte, apart
 * from the inserted id attributes. Comments, <script>, <style>, <pre>,
 * <textarea> and inline <svg> are skipped whole.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Blog;

defined( 'ABSPATH' ) || exit;

final class Toc {

	/** Class that keeps a heading out of the TOC. */
	const SKIP_CLASS = 'avix-toc-skip';

	/** Group whose H3s (the questions) stay out of the TOC. */
	const FAQ_CLASS = 'avix-faq';

	/** Elements without a closing tag. */
	const VOID = array( 'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr' );

	/** Attribute list of a tag (quoted values may contain ">"). */
	const ATTRS = '((?:[^>"\']+|"[^"]*"|\'[^\']*\')*)';

	/**
	 * Adds heading ids and returns the TOC.
	 *
	 * @param string $html  Rendered content.
	 * @param int    $depth 2 = H2 only, 3 = H2 and H3.
	 * @return array{html:string,items:array,count:int} items: list of
	 *               array( 'id', 'text', 'level', 'children' => list ).
	 */
	public static function process( string $html, int $depth = 3 ): array {
		$empty = array(
			'html'  => $html,
			'items' => array(),
			'count' => 0,
		);
		if ( '' === $html || ! preg_match( '/<h[23]\b/i', $html ) ) {
			return $empty;
		}

		$headings = self::scan( $html );
		if ( ! $headings ) {
			return $empty;
		}

		$used  = self::existing_ids( $html );
		$flat  = array();
		$edits = array();
		foreach ( $headings as $h ) {
			$text = self::text( $h['inner'] );
			$id   = self::attr( $h['attrs'], 'id' );
			if ( '' === $id ) {
				$id           = self::unique( self::slug( $text ), $used );
				$used[ $id ]  = true;
				$edits[]      = array(
					'at'  => $h['start'],
					'len' => $h['open_len'],
					'tag' => '<h' . $h['level'] . ' id="' . esc_attr( $id ) . '"' . $h['attrs'] . '>',
				);
			}
			$skip = '' === $text
				|| in_array( self::SKIP_CLASS, self::classes( $h['attrs'] ), true )
				|| ( 3 === $h['level'] && $h['in_faq'] )
				|| ( 3 === $h['level'] && $depth < 3 );
			if ( ! $skip ) {
				$flat[] = array(
					'id'    => $id,
					'text'  => $text,
					'level' => $h['level'],
				);
			}
		}

		// Apply the id edits from the end, so earlier offsets stay valid.
		for ( $i = count( $edits ) - 1; $i >= 0; $i-- ) {
			$html = substr_replace( $html, $edits[ $i ]['tag'], $edits[ $i ]['at'], $edits[ $i ]['len'] );
		}

		return array(
			'html'  => $html,
			'items' => self::nest( $flat ),
			'count' => count( $flat ),
		);
	}

	/**
	 * Every H2/H3 opening tag with its attributes, inner HTML and whether it
	 * sits inside the FAQ group.
	 *
	 * @param string $html Content.
	 * @return array<int,array{start:int,open_len:int,level:int,attrs:string,inner:string,in_faq:bool}>
	 */
	public static function scan( string $html ): array {
		$re = '~<!--.*?-->|<(script|style|pre|textarea|svg)\b[^>]*>.*?</\1\s*>|<(/?)([a-zA-Z][a-zA-Z0-9-]*)' . self::ATTRS . '>~si';
		if ( ! preg_match_all( $re, $html, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
			return array();
		}
		$stack = array();
		$found = array();
		foreach ( $matches as $m ) {
			if ( ! isset( $m[3] ) || '' === $m[3][0] ) {
				continue; // A comment or a skipped block.
			}
			$name    = strtolower( $m[3][0] );
			$closing = '/' === $m[2][0];
			$attrs   = isset( $m[4] ) ? $m[4][0] : '';
			if ( $closing ) {
				for ( $i = count( $stack ) - 1; $i >= 0; $i-- ) {
					if ( $stack[ $i ]['name'] === $name ) {
						array_splice( $stack, $i );
						break;
					}
				}
				continue;
			}
			if ( in_array( $name, self::VOID, true ) || '/' === substr( rtrim( $attrs ), -1 ) ) {
				continue;
			}
			$is_faq = in_array( self::FAQ_CLASS, self::classes( $attrs ), true );
			if ( 'h2' === $name || 'h3' === $name ) {
				$start    = $m[0][1];
				$open_len = strlen( $m[0][0] );
				$close    = stripos( $html, '</' . $name, $start + $open_len );
				if ( false !== $close ) {
					$in_faq = false;
					foreach ( $stack as $entry ) {
						if ( $entry['faq'] ) {
							$in_faq = true;
							break;
						}
					}
					$found[] = array(
						'start'    => $start,
						'open_len' => $open_len,
						'level'    => (int) substr( $name, 1 ),
						'attrs'    => $attrs,
						'inner'    => substr( $html, $start + $open_len, $close - $start - $open_len ),
						'in_faq'   => $in_faq,
					);
				}
			}
			$stack[] = array(
				'name' => $name,
				'faq'  => $is_faq,
			);
		}
		return $found;
	}

	/**
	 * Plain text of a heading's inner HTML.
	 *
	 * @param string $inner Inner HTML.
	 */
	public static function text( string $inner ): string {
		$text = html_entity_decode( wp_strip_all_tags( $inner ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
	}

	/**
	 * ASCII slug of a heading ('' for text without letters or digits).
	 *
	 * @param string $text Heading text.
	 */
	public static function slug( string $text ): string {
		$slug = sanitize_title( $text );
		$slug = (string) preg_replace( '/%[0-9a-f]{2}/i', '', $slug );
		$slug = (string) preg_replace( '/[^a-z0-9-]+/', '', strtolower( $slug ) );
		$slug = trim( (string) preg_replace( '/-+/', '-', $slug ), '-' );
		return '' !== $slug ? $slug : 'section';
	}

	/**
	 * @param string $slug Base slug.
	 * @param array  $used Ids taken (id => true).
	 */
	public static function unique( string $slug, array $used ): string {
		if ( ! isset( $used[ $slug ] ) ) {
			return $slug;
		}
		$n = 2;
		while ( isset( $used[ $slug . '-' . $n ] ) ) {
			$n++;
		}
		return $slug . '-' . $n;
	}

	/**
	 * Ids already in the content (id => true).
	 *
	 * @param string $html Content.
	 */
	public static function existing_ids( string $html ): array {
		$ids = array();
		if ( preg_match_all( '~\sid\s*=\s*(["\'])(.*?)\1~i', $html, $m ) ) {
			foreach ( $m[2] as $id ) {
				$ids[ html_entity_decode( $id, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ] = true;
			}
		}
		return $ids;
	}

	/**
	 * One attribute's value from an attribute string ('' when absent).
	 *
	 * @param string $attrs Attribute string.
	 * @param string $name  Attribute.
	 */
	public static function attr( string $attrs, string $name ): string {
		if ( preg_match( '~(?:^|\s)' . preg_quote( $name, '~' ) . '\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'>]+))~i', $attrs, $m ) ) {
			$value = isset( $m[3] ) && '' !== $m[3] ? $m[3] : ( isset( $m[2] ) && '' !== $m[2] ? $m[2] : $m[1] );
			return trim( html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		}
		return '';
	}

	/**
	 * @param string $attrs Attribute string.
	 * @return string[]
	 */
	public static function classes( string $attrs ): array {
		$class = self::attr( $attrs, 'class' );
		return '' === $class ? array() : preg_split( '/\s+/', $class, -1, PREG_SPLIT_NO_EMPTY );
	}

	/**
	 * Flat list → H2 items with their H3s as children (an H3 before any H2
	 * becomes a top-level item).
	 *
	 * @param array $flat Items in document order.
	 */
	public static function nest( array $flat ): array {
		$items = array();
		$last  = -1;
		foreach ( $flat as $item ) {
			$item['children'] = array();
			if ( 3 === $item['level'] && $last >= 0 ) {
				$items[ $last ]['children'][] = $item;
				continue;
			}
			$items[] = $item;
			if ( 2 === $item['level'] ) {
				$last = count( $items ) - 1;
			}
		}
		return $items;
	}

	/* ------------------------------------------------------------------ */
	/* Markup                                                             */
	/* ------------------------------------------------------------------ */

	/**
	 * The desktop TOC (rail): H2s numbered by CSS, H3s nested.
	 *
	 * @param array $items Output of process()['items'].
	 */
	public static function desktop( array $items ): string {
		if ( ! $items ) {
			return '';
		}
		$html = '';
		foreach ( $items as $item ) {
			$html .= '<li>' . self::link( $item, true );
			if ( ! empty( $item['children'] ) ) {
				$html .= '<ol>';
				foreach ( $item['children'] as $child ) {
					$html .= '<li>' . self::link( $child, true ) . '</li>';
				}
				$html .= '</ol>';
			}
			$html .= '</li>';
		}
		return '<nav class="avix-art-toc" aria-labelledby="avix-art-toc-title">'
			. '<p class="avix-art-toc__title" id="avix-art-toc-title">' . esc_html__( 'In this article', 'avix-widgets' ) . '</p>'
			. '<ol class="avix-art-toc__list" data-art-toc>' . $html . '</ol></nav>';
	}

	/**
	 * The phone and tablet TOC: a closed <details> with the sections only
	 * (all entries when there are fewer than three sections).
	 *
	 * @param array $items Output of process()['items'].
	 */
	public static function mobile( array $items ): string {
		if ( ! $items ) {
			return '';
		}
		$list = $items;
		if ( count( $items ) < 3 ) {
			$list = array();
			foreach ( $items as $item ) {
				$list[] = $item;
				foreach ( $item['children'] as $child ) {
					$list[] = $child;
				}
			}
		}
		$html = '';
		foreach ( $list as $item ) {
			$html .= '<li>' . self::link( $item ) . '</li>';
		}
		$count = count( $list );
		/* translators: %d: number of sections. */
		$label = sprintf( _n( '%d section', '%d sections', $count, 'avix-widgets' ), $count );
		return '<details class="avix-art-toc-m">'
			. '<summary><span class="avix-art-toc-m__label">' . esc_html__( 'In this article', 'avix-widgets' ) . '</span>'
			. '<span class="avix-art-toc-m__count">' . esc_html( $label ) . '</span>'
			. '<span class="avix-art-toc-m__chev" aria-hidden="true"></span></summary>'
			. '<nav aria-label="' . esc_attr__( 'In this article', 'avix-widgets' ) . '"><ol data-art-toc-m>' . $html . '</ol></nav></details>';
	}

	/**
	 * @param array $item TOC item.
	 */
	/**
	 * @param array $item    TOC item.
	 * @param bool  $reserve Desktop: the text again in data-t, which the CSS lays
	 *                       (bold, invisible) under the text, so the semibold
	 *                       active entry never re-wraps and moves the card below.
	 */
	private static function link( array $item, bool $reserve = false ): string {
		$href = '#' . rawurlencode( $item['id'] );
		if ( $reserve ) {
			return '<a href="' . esc_attr( $href ) . '" data-t="' . esc_attr( $item['text'] ) . '"><span>' . esc_html( $item['text'] ) . '</span></a>';
		}
		return '<a href="' . esc_attr( $href ) . '">' . esc_html( $item['text'] ) . '</a>';
	}
}
