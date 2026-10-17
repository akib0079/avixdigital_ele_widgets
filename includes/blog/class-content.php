<?php
/**
 * Accessibility and layout hooks the content contract leaves to the template
 * (blog/spec/CONTRACT.md section 3). Runs on the rendered article body only,
 * so block and classic posts get the same treatment and nothing changes in
 * feeds, excerpts or the REST API.
 *
 * - Key takeaways and callouts: role="note" (takeaways also get a label).
 * - Tables: the figure becomes focusable (named by its caption, or a labelled
 *   region without one); body cells get data-label from the header row, so
 *   phones can show each row as a stacked card with the column names; the
 *   empty corner header cell becomes a <td>; tables outside a figure get a
 *   labelled scroll wrapper.
 * - Blockquotes: class is-style-plain, which the theme's quote painting skips.
 * - Case-study proof card: the decorative "Read the case study" line is
 *   aria-hidden (the title link is the card's link).
 * - Code blocks: a language label (data-lang) from their language-* class,
 *   and tabindex/role/aria-label so they can be scrolled from the keyboard.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Blog;

defined( 'ABSPATH' ) || exit;

final class Content {

	/** Attribute list of a tag (quoted values may contain ">"). */
	const ATTRS = '((?:[^>"\']+|"[^"]*"|\'[^\']*\')*)';

	/** language-* class → label. */
	const LANGUAGES = array(
		'liquid'     => 'Liquid',
		'php'        => 'PHP',
		'js'         => 'JavaScript',
		'javascript' => 'JavaScript',
		'ts'         => 'TypeScript',
		'typescript' => 'TypeScript',
		'css'        => 'CSS',
		'scss'       => 'SCSS',
		'json'       => 'JSON',
		'html'       => 'HTML',
		'xml'        => 'XML',
		'bash'       => 'Bash',
		'shell'      => 'Shell',
		'sql'        => 'SQL',
		'jsx'        => 'JSX',
		'tsx'        => 'TSX',
		'yaml'       => 'YAML',
	);

	public static function enhance( string $html ): string {
		if ( '' === trim( $html ) ) {
			return $html;
		}
		$html = self::notes( $html );
		$html = self::tables( $html );
		$html = self::quotes( $html );
		$html = self::proof( $html );
		$html = self::code( $html );
		return $html;
	}

	/* ------------------------------------------------------------------ */

	private static function notes( string $html ): string {
		$html = self::add_attrs(
			$html,
			'div',
			'avix-takeaways',
			array(
				'role'       => 'note',
				'aria-label' => __( 'Key takeaways', 'avix-widgets' ),
			)
		);
		return self::add_attrs( $html, 'div', 'avix-callout', array( 'role' => 'note' ) );
	}

	private static function proof( string $html ): string {
		return self::add_attrs( $html, 'p', 'avix-proof__more', array( 'aria-hidden' => 'true' ) );
	}

	private static function quotes( string $html ): string {
		return (string) preg_replace_callback(
			'~<blockquote\b' . self::ATTRS . '>~i',
			static function ( $m ) {
				return '<blockquote' . self::with_class( $m[1], 'is-style-plain' ) . '>';
			},
			$html
		);
	}

	private static function code( string $html ): string {
		return (string) preg_replace_callback(
			'~<pre\b' . self::ATTRS . '>(\s*<code\b' . self::ATTRS . '>)?~i',
			static function ( $m ) {
				$classes = array_merge( Toc::classes( $m[1] ), isset( $m[3] ) ? Toc::classes( $m[3] ) : array() );
				$lang    = '';
				foreach ( $classes as $class ) {
					if ( 0 === strpos( $class, 'language-' ) ) {
						$lang = strtolower( substr( $class, 9 ) );
						break;
					}
				}
				$label = '';
				if ( '' !== $lang ) {
					$label = isset( self::LANGUAGES[ $lang ] ) ? self::LANGUAGES[ $lang ] : ucfirst( (string) preg_replace( '/[^a-z0-9+#-]/', '', $lang ) );
				}
				$add = array();
				if ( '' !== $label ) {
					$add['data-lang'] = $label;
				}
				// A code block scrolls sideways: focusable and named, so keyboards can scroll it.
				$add['tabindex']   = '0';
				$add['role']       = 'region';
				$add['aria-label'] = '' !== $label
					/* translators: %s: language name, e.g. Liquid. */
					? sprintf( __( '%s code', 'avix-widgets' ), $label )
					: __( 'Code', 'avix-widgets' );
				return '<pre' . self::merge_attrs( $m[1], $add ) . '>' . ( isset( $m[2] ) ? $m[2] : '' );
			},
			$html
		);
	}

	/* ------------------------------------------------------------------ */
	/* Tables                                                             */
	/* ------------------------------------------------------------------ */

	private static function tables( string $html ): string {
		if ( false === stripos( $html, '<table' ) ) {
			return $html;
		}
		// Table blocks: <figure class="wp-block-table">…</figure>.
		$html = (string) preg_replace_callback(
			'~<figure\b' . self::ATTRS . '>(.*?)</figure>~is',
			static function ( $m ) {
				if ( ! in_array( 'wp-block-table', Toc::classes( $m[1] ), true ) || false === stripos( $m[2], '<table' ) ) {
					return $m[0];
				}
				$stack = false;
				$inner = (string) preg_replace_callback(
					'~<table\b' . self::ATTRS . '>.*?</table>~is',
					static function ( $t ) use ( &$stack ) {
						$out   = self::table( $t[0] );
						$stack = $stack || $out['stack'];
						return $out['html'];
					},
					$m[2]
				);
				$label = '';
				if ( preg_match( '~<figcaption\b[^>]*>(.*?)</figcaption>~is', $inner, $c ) ) {
					$label = wp_trim_words( Toc::text( $c[1] ), 16, '…' );
				}
				$attrs = $m[1];
				if ( $stack ) {
					$attrs = self::with_class( $attrs, 'is-stackable' );
				}
				// Focusable, so the table can be scrolled from the keyboard. With a
				// caption the figure is named by it (a <figure> with a <figcaption>
				// may not take another role); without one it becomes a labelled region.
				$attrs = self::merge_attrs(
					$attrs,
					'' !== $label
						? array( 'tabindex' => '0' )
						: array(
							'tabindex'   => '0',
							'role'       => 'region',
							'aria-label' => __( 'Table', 'avix-widgets' ),
						)
				);
				return '<figure' . $attrs . '>' . $inner . '</figure>';
			},
			$html
		);
		// Any other table (classic posts): the same treatment in a scroll wrapper.
		return (string) preg_replace_callback(
			'~<table\b' . self::ATTRS . '>.*?</table>~is',
			static function ( $t ) {
				if ( '' !== Toc::attr( $t[1], 'data-art-table' ) ) {
					return $t[0];
				}
				$out = self::table( $t[0] );
				return '<div class="avix-art-table' . ( $out['stack'] ? ' is-stackable' : '' ) . '" tabindex="0" role="region" aria-label="' . esc_attr__( 'Table', 'avix-widgets' ) . '">' . $out['html'] . '</div>';
			},
			$html
		);
	}

	/**
	 * data-label on body cells from the header row. A table can stack on phones
	 * when it has a header row of two or more cells.
	 *
	 * @param string $table One <table>…</table>.
	 * @return array{html:string,stack:bool}
	 */
	public static function table( string $table ): array {
		// The contract's empty corner cell ("<th></th>") is a header without
		// text: make it a plain cell.
		$table  = (string) preg_replace_callback(
			'~<thead\b[^>]*>.*?</thead>~is',
			static function ( $head ) {
				return (string) preg_replace( '~<th\b(' . self::ATTRS . ')>(\s|&nbsp;)*</th>~i', '<td$1></td>', $head[0] );
			},
			$table,
			1
		);
		$labels = array();
		if ( preg_match( '~<thead\b[^>]*>(.*?)</thead>~is', $table, $head ) && preg_match( '~<tr\b[^>]*>(.*?)</tr>~is', $head[1], $row ) ) {
			if ( preg_match_all( '~<(th|td)\b' . self::ATTRS . '>(.*?)</\1>~is', $row[1], $cells, PREG_SET_ORDER ) ) {
				foreach ( $cells as $cell ) {
					$span = max( 1, (int) Toc::attr( $cell[2], 'colspan' ) );
					for ( $i = 0; $i < $span; $i++ ) {
						$labels[] = Toc::text( $cell[3] );
					}
				}
			}
		}
		$stack = count( $labels ) >= 2;
		if ( $stack ) {
			$table = (string) preg_replace_callback(
				'~(<tbody\b[^>]*>)(.*?)(</tbody>)~is',
				static function ( $body ) use ( $labels ) {
					$rows = (string) preg_replace_callback(
						'~<tr\b[^>]*>.*?</tr>~is',
						static function ( $tr ) use ( $labels ) {
							$col = 0;
							return (string) preg_replace_callback(
								'~<(td|th)\b' . self::ATTRS . '>~i',
								static function ( $cell ) use ( $labels, &$col ) {
									$at   = $col;
									$col += max( 1, (int) Toc::attr( $cell[2], 'colspan' ) );
									$name = strtolower( $cell[1] );
									if ( 'td' !== $name || ! isset( $labels[ $at ] ) || '' === $labels[ $at ] || '' !== Toc::attr( $cell[2], 'data-label' ) ) {
										return $cell[0];
									}
									return '<td' . $cell[2] . ' data-label="' . esc_attr( $labels[ $at ] ) . '">';
								},
								$tr[0]
							);
						},
						$body[2]
					);
					return $body[1] . $rows . $body[3];
				},
				$table
			);
		}
		// Mark it done, so the second pass (and a second run) leaves it alone.
		if ( ! preg_match( '~^<table\b[^>]*\sdata-art-table=~i', $table ) ) {
			$table = (string) preg_replace( '~^<table\b~i', '<table data-art-table="1"', $table, 1 );
		}
		return array(
			'html'  => $table,
			'stack' => $stack,
		);
	}

	/* ------------------------------------------------------------------ */
	/* Attribute helpers                                                  */
	/* ------------------------------------------------------------------ */

	/**
	 * Adds attributes to every <$tag> that has $class (attributes the author
	 * already set are kept).
	 *
	 * @param string $html  HTML.
	 * @param string $tag   Tag name.
	 * @param string $class Class token.
	 * @param array  $attrs Name => value.
	 */
	public static function add_attrs( string $html, string $tag, string $class, array $attrs ): string {
		if ( false === strpos( $html, $class ) ) {
			return $html;
		}
		return (string) preg_replace_callback(
			'~<' . preg_quote( $tag, '~' ) . '\b' . self::ATTRS . '>~i',
			static function ( $m ) use ( $tag, $class, $attrs ) {
				if ( ! in_array( $class, Toc::classes( $m[1] ), true ) ) {
					return $m[0];
				}
				return '<' . $tag . self::merge_attrs( $m[1], $attrs ) . '>';
			},
			$html
		);
	}

	/**
	 * @param string $attrs Attribute string.
	 * @param array  $add   Name => value (skipped when the attribute exists).
	 */
	public static function merge_attrs( string $attrs, array $add ): string {
		$self_closing = '/' === substr( rtrim( $attrs ), -1 );
		if ( $self_closing ) {
			$attrs = rtrim( substr( rtrim( $attrs ), 0, -1 ) );
		}
		foreach ( $add as $name => $value ) {
			if ( preg_match( '~(?:^|\s)' . preg_quote( $name, '~' ) . '(?:\s*=|\s|$)~i', $attrs ) ) {
				continue;
			}
			$attrs .= ' ' . $name . '="' . esc_attr( (string) $value ) . '"';
		}
		return $self_closing ? $attrs . ' /' : $attrs;
	}

	/**
	 * Adds a class token to an attribute string.
	 *
	 * @param string $attrs Attribute string.
	 * @param string $class Class token.
	 */
	public static function with_class( string $attrs, string $class ): string {
		$classes = Toc::classes( $attrs );
		if ( in_array( $class, $classes, true ) ) {
			return $attrs;
		}
		if ( ! $classes && '' === Toc::attr( $attrs, 'class' ) && ! preg_match( '~(?:^|\s)class\s*=~i', $attrs ) ) {
			return $attrs . ' class="' . esc_attr( $class ) . '"';
		}
		return (string) preg_replace_callback(
			'~(^|\s)class\s*=\s*(["\'])(.*?)\2~i',
			static function ( $m ) use ( $class ) {
				$value = trim( $m[3] );
				return $m[1] . 'class=' . $m[2] . ( '' !== $value ? $value . ' ' : '' ) . esc_attr( $class ) . $m[2];
			},
			$attrs,
			1
		);
	}
}
