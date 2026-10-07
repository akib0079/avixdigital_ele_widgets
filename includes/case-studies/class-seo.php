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
