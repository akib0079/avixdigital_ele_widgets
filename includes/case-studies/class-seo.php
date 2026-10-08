<?php
/**
 * Case Studies SEO: Yoast breadcrumbs (Home › Case studies › Client), the
 * CreativeWork schema for each case study and an ItemList on the index page.
 * With Yoast the entities join Yoast's graph; without it one self-contained
 * JSON-LD block is printed in wp_head (SPEC-CASE-STUDIES §6). Never both.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Case_Studies;

defined( 'ABSPATH' ) || exit;

final class SEO {

	/** Static guard: the fallback JSON-LD prints once per request. */
	private static $printed = false;

	public static function init(): void {
		add_filter( 'wpseo_breadcrumb_links', array( __CLASS__, 'breadcrumbs' ) );
		add_filter( 'wpseo_schema_graph_pieces', array( __CLASS__, 'graph_pieces' ), 11, 2 );
		add_filter( 'wpseo_schema_webpage', array( __CLASS__, 'webpage' ) );
		add_action( 'wp_head', array( __CLASS__, 'print_fallback' ), 30 );
		// One Open Graph block: unhook the theme's copy once the query is known, and
		// again first thing in wp_head for anything hooked after template_redirect.
		add_action( 'template_redirect', array( __CLASS__, 'drop_theme_og' ), PHP_INT_MAX );
		add_action( 'wp_head', array( __CLASS__, 'drop_theme_og' ), -9999 );
	}

	/* ------------------------------------------------------------------ */
	/* One Open Graph block                                               */
	/* ------------------------------------------------------------------ */

	/**
	 * ThemeREX Addons callbacks known to print og:* tags in wp_head.
	 */
	const THEME_OG_CALLBACKS = array( 'trx_addons_add_og_tags', 'trx_addons_add_og_meta', 'trx_addons_og_tags', 'trx_addons_add_open_graph_tags' );

	/**
	 * With Yoast active, Yoast prints the page's Open Graph tags (on a case study
	 * the designed 1200 × 630 avix-cs-<slug>-og.jpg). ThemeREX Addons ("Add Open
	 * Graph tags") prints a second og:type/url/title/description/image block after
	 * it: on case studies the featured render as og:image and a cut description,
	 * on the index an empty og:description. Social sites may take either, so on
	 * singular pages (the index included) the theme's block is unhooked. Without
	 * Yoast, or with Yoast's Open Graph switched off, nothing changes: the theme's
	 * block is then the only one. Matching by name keeps working if the theme
	 * moves the priority. Head-only, nothing is logged.
	 */
	public static function drop_theme_og(): void {
		if ( is_admin() || is_feed() || ! self::yoast_prints_og() ) {
			return;
		}
		$scope = is_singular() || self::is_index();
		/**
		 * Filters whether the ThemeREX Addons Open Graph block is removed on this request.
		 *
		 * @param bool $scope True on singular pages and the case-studies index while Yoast prints Open Graph.
		 */
		if ( ! apply_filters( 'avix_cs_drop_theme_og', $scope ) ) {
			return;
		}
		global $wp_filter;
		if ( ! isset( $wp_filter['wp_head'] ) || ! is_object( $wp_filter['wp_head'] ) || ! isset( $wp_filter['wp_head']->callbacks ) || ! is_array( $wp_filter['wp_head']->callbacks ) ) {
			return;
		}
		$found = array();
		foreach ( $wp_filter['wp_head']->callbacks as $priority => $callbacks ) {
			if ( ! is_array( $callbacks ) ) {
				continue;
			}
			foreach ( $callbacks as $callback ) {
				$fn = isset( $callback['function'] ) ? $callback['function'] : null;
				if ( self::is_theme_og_callback( $fn ) ) {
					$found[] = array( $fn, $priority );
				}
			}
		}
		// Removed after the walk, so the array being read is never changed underneath it.
		foreach ( $found as $hook ) {
			remove_action( 'wp_head', $hook[0], $hook[1] );
		}
	}

	/**
	 * True when Yoast is active and its Open Graph output is on (Yoast SEO >
	 * Settings > Social; on by default). Without Yoast's class the setting is
	 * assumed on, as Yoast ships it.
	 */
	public static function yoast_prints_og(): bool {
		if ( ! self::has_yoast() ) {
			return false;
		}
		if ( class_exists( '\WPSEO_Options' ) && method_exists( '\WPSEO_Options', 'get' ) ) {
			try {
				return (bool) \WPSEO_Options::get( 'opengraph', true );
			} catch ( \Throwable $error ) {
				return true;
			}
		}
		return true;
	}

	/**
	 * Pure helper (unit-testable): true for a ThemeREX Addons Open Graph callback.
	 *
	 * @param mixed $fn A wp_head callback.
	 */
	public static function is_theme_og_callback( $fn ): bool {
		$name = '';
		if ( is_string( $fn ) ) {
			$name = $fn;
		} elseif ( is_array( $fn ) && 2 === count( $fn ) && is_string( $fn[1] ) ) {
			$owner = is_object( $fn[0] ) ? get_class( $fn[0] ) : ( is_string( $fn[0] ) ? $fn[0] : '' );
			$name  = $owner . '::' . $fn[1];
		}
		if ( '' === $name ) {
			return false;
		}
		if ( in_array( $name, self::THEME_OG_CALLBACKS, true ) ) {
			return true;
		}
		return 0 === stripos( $name, 'trx_addons' ) && (bool) preg_match( '/(^|_|::)(og|open_?graph)(_|$)/i', $name );
	}

	/**
	 * Yoast is active (checked late: Yoast loads after this plugin).
	 */
	public static function has_yoast(): bool {
		return defined( 'WPSEO_VERSION' );
	}

	/* ------------------------------------------------------------------ */
	/* Breadcrumbs                                                        */
	/* ------------------------------------------------------------------ */

	/**
	 * Inserts the index page between Home and the case study, which fixes both
	 * Yoast's visible crumbs and its BreadcrumbList.
	 *
	 * @param mixed $links Yoast crumbs: array of array( 'url', 'text' ) or array( 'id' ).
	 * @return mixed
	 */
	public static function breadcrumbs( $links ) {
		if ( ! is_array( $links ) || ! is_singular( Case_Study::POST_TYPE ) ) {
			return $links;
		}
		return self::insert_index_crumb( $links, Case_Study::index_url(), Case_Study::index_title(), Case_Study::index_page_id() );
	}

	/**
	 * Pure helper (unit-testable): adds the index crumb after Home unless it is already there.
	 */
	public static function insert_index_crumb( array $links, string $url, string $text, int $index_id = 0 ): array {
		$norm = untrailingslashit( $url );
		foreach ( $links as $link ) {
			if ( ! is_array( $link ) ) {
				continue;
			}
			if ( ( isset( $link['url'] ) && untrailingslashit( (string) $link['url'] ) === $norm ) || ( $index_id && isset( $link['id'] ) && (int) $link['id'] === $index_id ) ) {
				return $links;
			}
		}
		$crumb = array(
			'url'  => $url,
			'text' => $text,
		);
		if ( count( $links ) <= 1 ) {
			$links[] = $crumb;
			return $links;
		}
		array_splice( $links, 1, 0, array( $crumb ) );
		return array_values( $links );
	}

	/* ------------------------------------------------------------------ */
	/* Schema data                                                        */
	/* ------------------------------------------------------------------ */

	/**
	 * The CreativeWork entity for a case study.
	 *
	 * @param int    $id        Case study ID.
	 * @param string $canonical Page URL used for @ids (Yoast's canonical, else the permalink).
	 * @param bool   $yoast     True inside Yoast's graph (references), false for the standalone block (inline objects).
	 */
	public static function creative_work( int $id, string $canonical = '', bool $yoast = true ): array {
		$cs = Case_Study::get( $id );
		if ( ! $cs ) {
			return array();
		}
		$url = '' !== $canonical ? $canonical : $cs['permalink'];
		$org = trailingslashit( home_url() ) . '#organization';

		$keywords = array();
		foreach ( $cs['terms']['service'] as $term ) {
			$keywords[] = $term[0];
		}
		foreach ( $cs['stack'] as $row ) {
			$keywords[] = $row[0];
		}
		$keywords = array_values( array_unique( array_filter( $keywords ) ) );

		$name        = '' !== $cs['display_title_plain'] ? $cs['display_title_plain'] : $cs['title'];
		$description = '' !== $cs['excerpt'] ? $cs['excerpt'] : $cs['summary'];

		$work = array(
			'@type'    => 'CreativeWork',
			'@id'      => $url . '#case-study',
			'name'     => $name,
			'headline' => $cs['title'],
		);
		if ( '' !== $description ) {
			$work['description'] = $description;
		}
		$work['url'] = $url;

		$image_id = $cs['thumbnail_id'] ? $cs['thumbnail_id'] : $cs['card_image'];
		if ( $yoast && $image_id ) {
			$work['image'] = array( '@id' => $url . '#primaryimage' );
		} elseif ( $image_id ) {
			$src = wp_get_attachment_image_src( $image_id, 'full' );
			if ( $src ) {
				$work['image'] = array(
					'@type'  => 'ImageObject',
					'url'    => $src[0],
					'width'  => (int) $src[1],
					'height' => (int) $src[2],
				);
			}
		}

		if ( '' !== $cs['date_published'] ) {
			$work['datePublished'] = $cs['date_published'];
		}
		if ( '' !== $cs['date_modified'] ) {
			$work['dateModified'] = $cs['date_modified'];
		}
		if ( '' !== $cs['year'] ) {
			$work['dateCreated'] = $cs['year'];
		}

		if ( $yoast ) {
			$work['creator']   = array( '@id' => $org );
			$work['publisher'] = array( '@id' => $org );
		} else {
			$organization      = array(
				'@type' => 'Organization',
				'@id'   => $org,
				'name'  => Case_Study::plain( get_bloginfo( 'name' ) ),
				'url'   => trailingslashit( home_url() ),
			);
			$work['creator']   = $organization;
			$work['publisher'] = array( '@id' => $org );
		}

		if ( '' !== $cs['client'] ) {
			$about = array(
				'@type' => 'Organization',
				'name'  => $cs['client'],
			);
			if ( '' !== $cs['live_url'] ) {
				$about['url'] = $cs['live_url'];
			}
			$work['about'] = $about;
		}
		if ( '' !== $cs['live_url'] ) {
			$work['mentions'] = array(
				'@type' => 'WebSite',
				'url'   => $cs['live_url'],
			);
		}
		if ( $keywords ) {
			$work['keywords'] = implode( ', ', $keywords );
		}
		$work['inLanguage'] = 'en-GB';

		if ( $yoast ) {
			$work['isPartOf']         = array( '@id' => $url );
			$work['mainEntityOfPage'] = array( '@id' => $url );
		} else {
			$work['mainEntityOfPage'] = $url;
		}

		return $work;
	}

	/**
	 * The ItemList entity for the index page, or array() when there are no case studies.
	 *
	 * @param string $canonical Index page URL.
	 */
	public static function item_list( string $canonical = '' ): array {
		$ids = Case_Study::ordered_ids();
		if ( ! $ids ) {
			return array();
		}
		$url   = '' !== $canonical ? $canonical : Case_Study::index_url();
		$items = array();
		foreach ( array_values( $ids ) as $i => $id ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'url'      => (string) get_permalink( $id ),
				'name'     => Case_Study::plain( get_the_title( $id ) ),
			);
		}
		return array(
			'@type'           => 'ItemList',
			'@id'             => $url . '#case-studies',
			'name'            => Case_Study::index_title(),
			'numberOfItems'   => count( $items ),
			'itemListElement' => $items,
		);
	}

	/* ------------------------------------------------------------------ */
	/* Yoast                                                              */
	/* ------------------------------------------------------------------ */

	/**
	 * Adds our pieces to Yoast's graph. The piece classes extend Yoast's
	 * abstract, so they are loaded only here, when Yoast is running.
	 *
	 * @param mixed $pieces  Graph pieces.
	 * @param mixed $context Yoast Meta_Tags_Context.
	 * @return mixed
	 */
	public static function graph_pieces( $pieces, $context = null ) {
		if ( ! is_array( $pieces ) || ! self::has_yoast() ) {
			return $pieces;
		}
		if ( ! is_singular( Case_Study::POST_TYPE ) && ! self::is_index() ) {
			return $pieces;
		}
		$file = __DIR__ . '/class-schema-piece.php';
		if ( ! file_exists( $file ) ) {
			return $pieces;
		}
		require_once $file;
		if ( ! class_exists( __NAMESPACE__ . '\Schema_Piece', false ) ) {
			return $pieces;
		}
		$pieces[] = new Schema_Piece( $context );
		$pieces[] = new Schema_Index_Piece( $context );
		return $pieces;
	}

	/**
	 * Yoast's WebPage gets mainEntity → our CreativeWork (or the ItemList on the index).
	 *
	 * @param mixed $data WebPage piece.
	 * @return mixed
	 */
	public static function webpage( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}
		$url = isset( $data['@id'] ) && is_string( $data['@id'] ) ? $data['@id'] : '';
		if ( is_singular( Case_Study::POST_TYPE ) ) {
			$id = (int) get_queried_object_id();
			if ( Case_Study::get( $id ) ) {
				$data['mainEntity'] = array( '@id' => ( '' !== $url ? $url : (string) get_permalink( $id ) ) . '#case-study' );
			}
		} elseif ( self::is_index() && Case_Study::ordered_ids() ) {
			$data['mainEntity'] = array( '@id' => ( '' !== $url ? $url : Case_Study::index_url() ) . '#case-studies' );
		}
		return $data;
	}

	/* ------------------------------------------------------------------ */
	/* Without Yoast                                                      */
	/* ------------------------------------------------------------------ */

	public static function print_fallback(): void {
		if ( self::$printed || self::has_yoast() || is_admin() || is_feed() ) {
			return;
		}
		$data = array();
		if ( is_singular( Case_Study::POST_TYPE ) ) {
			$data = self::creative_work( (int) get_queried_object_id(), '', false );
		} elseif ( self::is_index() ) {
			$data = self::item_list( (string) get_permalink( Case_Study::index_page_id() ) );
		}
		if ( ! $data ) {
			return;
		}
		self::$printed = true;
		$data          = array( '@context' => 'https://schema.org' ) + $data;
		echo "\n" . '<script type="application/ld+json" class="avix-cs-schema">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON encoded with HEX_TAG/HEX_AMP.
	}

	/**
	 * True on the /case-studies/ index page.
	 */
	public static function is_index(): bool {
		$index = Case_Study::index_page_id();
		return $index && is_page( $index );
	}
}
