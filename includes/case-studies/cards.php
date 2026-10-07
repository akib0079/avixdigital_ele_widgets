<?php
/**
 * Case study cards for the /case-studies/ index (Case Study Grid widget),
 * shared by the widget's first render and the "avix_case_studies" AJAX
 * endpoint (service filters and "Load more"), so both always print
 * identical markup.
 *
 * Loaded at boot, before Elementor registers widgets, so nothing here may
 * depend on widget classes or on the shared kit (which only loads with the
 * widgets). The endpoint serves public, published data only: no nonce
 * (cached pages would serve stale ones); every input is clamped or validated
 * instead.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Case_Studies;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( __NAMESPACE__ . '\\Cards' ) ) {

	final class Cards {

		const ACTION = 'avix_case_studies';

		const POST_TYPE = 'avix_case_study';

		const TAX_SERVICE = 'avix_cs_service';

		/** Query vars of the no-JS fallback: ?service=<term slug> and ?pg=<page>. */
		const SERVICE_VAR = 'service';
		const PAGE_VAR    = 'pg';

		const MAX_PER_PAGE = 24;

		/** Seconds browsers and caches may keep an "avix_case_studies" answer. */
		const CACHE_TTL = 300;

		/** Card name tags the widget may ask for. */
		const TAGS = array( 'h2', 'h3', 'h4', 'div' );

		public static function init() {
			add_action( 'wp_ajax_' . self::ACTION, array( __CLASS__, 'handle' ) );
			add_action( 'wp_ajax_nopriv_' . self::ACTION, array( __CLASS__, 'handle' ) );
			// Yoast SEO's crawl cleanup ("Remove unregistered URL parameters")
			// would 301 every filter and page link back to the bare page.
			add_filter( 'Yoast\WP\SEO\allowlist_permalink_vars', array( __CLASS__, 'allow_vars' ) );
		}

		/**
		 * Lets Yoast keep ?service= and ?pg= (the index's filter and page).
		 *
		 * @param mixed $vars Allowed query vars.
		 * @return array
		 */
		public static function allow_vars( $vars ): array {
			$vars   = is_array( $vars ) ? $vars : array();
			$vars[] = self::SERVICE_VAR;
			$vars[] = self::PAGE_VAR;
			return array_values( array_unique( array_map( 'strval', $vars ) ) );
		}

		/* ------------------------------------------------------------------ */
		/* Arguments                                                           */
		/* ------------------------------------------------------------------ */

		/**
		 * Clamp and validate everything a card list depends on. Used for the
		 * widget's own settings and for untrusted AJAX input alike.
		 *
		 * @param array $raw Raw values.
		 */
		public static function args( array $raw ): array {
			$services = self::slug_list( $raw['services'] ?? array() );
			$exclude  = array_slice( array_values( array_filter( array_map( 'absint', wp_parse_id_list( $raw['exclude'] ?? array() ) ) ) ), 0, 50 );
			$orderby  = 'date' === (string) ( $raw['orderby'] ?? '' ) ? 'date' : 'menu_order';
			$tag      = strtolower( (string) ( $raw['tag'] ?? 'h2' ) );
			$label    = sanitize_text_field( (string) ( $raw['label'] ?? '' ) );
			$page     = max( 1, min( 500, (int) ( is_numeric( $raw['page'] ?? '' ) ? $raw['page'] : 1 ) ) );
			$per_page = (int) ( is_numeric( $raw['per_page'] ?? '' ) ? $raw['per_page'] : 9 );
			$service  = self::service( (string) ( $raw['service'] ?? '' ), $services );
			$uid      = preg_replace( '/[^a-z0-9]/', '', strtolower( (string) ( $raw['uid'] ?? '' ) ) );

			return array(
				'page'     => $page,
				'per_page' => max( 1, min( self::MAX_PER_PAGE, $per_page ) ),
				'service'  => $service,
				'services' => $services,
				'exclude'  => $exclude,
				'orderby'  => $orderby,
				// The wide first card belongs to the unfiltered first page only.
				'featured' => self::flag( $raw['featured'] ?? 0 ) && 1 === $page && '' === $service,
				'style'    => 'minimal' === (string) ( $raw['style'] ?? '' ) ? 'minimal' : 'panel',
				'metric'   => self::flag( $raw['metric'] ?? 1 ),
				'tag'      => in_array( $tag, self::TAGS, true ) ? $tag : 'h2',
				'label'    => '' !== $label ? self::cap( $label, 40 ) : __( 'View case study', 'avix-widgets' ),
				'uid'      => substr( (string) $uid, 0, 12 ),
				// Load the first image early: only the widget's own first render asks.
				'eager'    => self::flag( $raw['eager'] ?? 0 ),
			);
		}

		/**
		 * A service filter must be an existing service term slug, and one of
		 * the widget's chosen services when it limits them. Anything else
		 * means "all".
		 *
		 * @param string $slug     Requested slug.
		 * @param array  $services Allowed slugs (empty = any).
		 */
		public static function service( $slug, array $services = array() ): string {
			$slug = sanitize_title( $slug );
			if ( '' === $slug || ( $services && ! in_array( $slug, $services, true ) ) || ! taxonomy_exists( self::TAX_SERVICE ) ) {
				return '';
			}
			$term = get_term_by( 'slug', $slug, self::TAX_SERVICE );
			return $term && ! is_wp_error( $term ) ? $slug : '';
		}

		/* ------------------------------------------------------------------ */
		/* Query                                                               */
		/* ------------------------------------------------------------------ */

		/**
		 * Published case studies for one page. Found rows stay on: "Showing X
		 * of Y" and "Load more" need the total.
		 *
		 * @param array $args Output of args().
		 */
		public static function query( array $args ): \WP_Query {
			$order = 'date' === $args['orderby']
				? array(
					'date' => 'DESC',
					'ID'   => 'DESC',
				)
				: array(
					'menu_order' => 'ASC',
					'date'       => 'DESC',
					'ID'         => 'DESC',
				);

			$query = array(
				'post_type'           => self::POST_TYPE,
				'post_status'         => 'publish',
				'posts_per_page'      => $args['per_page'],
				'offset'              => ( $args['page'] - 1 ) * $args['per_page'],
				'ignore_sticky_posts' => true,
				'has_password'        => false,
				// ID breaks ties so OFFSET paging never repeats or skips a study.
				'orderby'             => $order,
			);
			if ( $args['exclude'] ) {
				$query['post__not_in'] = $args['exclude']; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- at most 50 IDs (usually the current study).
			}
			$slugs = '' !== $args['service'] ? array( $args['service'] ) : $args['services'];
			if ( $slugs && taxonomy_exists( self::TAX_SERVICE ) ) {
				$query['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => self::TAX_SERVICE,
						'field'    => 'slug',
						'terms'    => $slugs,
					),
				);
			}
			$result = new \WP_Query( $query );
			if ( $result->posts ) {
				// One query for every card's meta and image instead of one each.
				update_postmeta_cache( wp_list_pluck( $result->posts, 'ID' ) );
				$images = array();
				foreach ( $result->posts as $post ) {
					$image = self::image_id( (int) $post->ID, self::data( (int) $post->ID ) );
					if ( $image ) {
						$images[] = $image;
					}
				}
				if ( $images && function_exists( '_prime_post_caches' ) ) {
					_prime_post_caches( array_unique( $images ), false, true );
				}
			}
			return $result;
		}

		/**
		 * Number of studies shown up to and including the query's page.
		 *
		 * @param \WP_Query $query Grid query.
		 * @param array     $args  Output of args().
		 */
		public static function shown( \WP_Query $query, array $args ): int {
			return min( (int) $query->found_posts, ( $args['page'] - 1 ) * $args['per_page'] + count( $query->posts ) );
		}

		/**
		 * Cards for a query, numbered from the page's first position.
		 *
		 * @param \WP_Query $query Grid query.
		 * @param array     $args  Output of args().
		 */
		public static function cards( \WP_Query $query, array $args ): string {
			$html  = '';
			$start = ( $args['page'] - 1 ) * $args['per_page'];
			$total = (int) $query->found_posts;
			foreach ( $query->posts as $i => $post ) {
				$html .= self::card( (int) $post->ID, $args, $start + $i, $total );
			}
			return $html;
		}

		/**
		 * Service terms that have published case studies (the filter chips),
		 * limited to the chosen services.
		 *
		 * @param array $services Allowed slugs (empty = any).
		 * @return \WP_Term[]
		 */
		public static function terms_in_use( array $services = array() ): array {
			if ( ! taxonomy_exists( self::TAX_SERVICE ) ) {
				return array();
			}
			$query = array(
				'taxonomy'   => self::TAX_SERVICE,
				'hide_empty' => true,
				'orderby'    => 'count',
				'order'      => 'DESC',
				'number'     => 30,
			);
			if ( $services ) {
				$query['slug'] = $services;
			}
			$terms = get_terms( $query );
			return is_array( $terms ) ? array_values( $terms ) : array();
		}

		/* ------------------------------------------------------------------ */
		/* Markup                                                              */
		/* ------------------------------------------------------------------ */

		/**
		 * One card. A single link holds the whole card (no nested links); it
		 * is named by the client and described by the summary, so a screen
		 * reader hears "Rehall, link" plus the summary, not the whole panel.
		 *
		 * @param int   $id    Case study ID.
		 * @param array $args  Output of args().
		 * @param int   $index Position in the current view (0-based).
		 * @param int   $total Studies in the current view.
		 */
		public static function card( int $id, array $args, int $index = 0, int $total = 0 ): string {
			$cs = self::data( $id );
			if ( ! $cs ) {
				return '';
			}

			$featured = $args['featured'] && 0 === $index;
			$minimal  = 'minimal' === $args['style'];
			$uid      = 'avix-csi-' . ( '' !== $args['uid'] ? $args['uid'] . '-' : '' ) . $id;
			$name     = self::text( $cs['client'] ?? '' );
			$name     = '' !== $name ? $name : self::text( $cs['title'] ?? '' );
			$name     = '' !== $name ? $name : __( '(Untitled)', 'avix-widgets' );
			$excerpt  = self::excerpt( $cs );
			$year     = self::text( $cs['year'] ?? '' );
			$tag      = $args['tag'];

			$classes = array( 'avix-csi__card' );
			if ( $featured ) {
				$classes[] = 'avix-csi__card--featured';
			}
			if ( $minimal ) {
				$classes[] = 'avix-csi__card--minimal';
			}

			// Image: a studio render, the card image or the desktop shot.
			$image_id = self::image_id( $id, $cs );
			$render   = $image_id && (int) ( $cs['hero_render'] ?? 0 ) === $image_id;
			$sizes    = $featured
				? '(max-width: 1024px) 94vw, 720px'
				: '(max-width: 767px) 94vw, (max-width: 1240px) 47vw, 600px';
			$eager    = $args['eager'] && 0 === $index;
			$image    = $image_id ? self::img( $image_id, $sizes, $eager, $name ) : '';
			$media    = '<div class="avix-csi__media' . ( $render ? ' is-render' : '' ) . ( '' === $image ? ' is-placeholder' : '' ) . '">'
				. ( '' !== $image ? $image : self::placeholder( $name ) )
				. '</div>';

			// Panel.
			$count = max( $total, $index + 1 );
			$meta  = '<p class="avix-csi__meta"><span class="avix-csi__index" aria-hidden="true">' . esc_html( self::pad( $index + 1 ) ) . '<span class="avix-csi__of"> / ' . esc_html( self::pad( $count ) ) . '</span></span>';
			if ( '' !== $year ) {
				$meta .= '<span class="avix-csi__year">' . esc_html( $year ) . '</span>';
			}
			$meta .= '</p>';

			$tags = self::tags( $cs );
			$chip = '';
			if ( $tags ) {
				$chip = '<ul class="avix-csi__tags">';
				foreach ( $tags as $label ) {
					$chip .= '<li class="avix-csi__tag">' . esc_html( $label ) . '</li>';
				}
				$chip .= '</ul>';
			}

			$desc = '' !== $excerpt ? '<p class="avix-csi__excerpt" id="' . esc_attr( $uid . '-d' ) . '">' . esc_html( $excerpt ) . '</p>' : '';

			$outcome = '';
			$o_label = self::text( $cs['card_label'] ?? '' );
			$o_value = self::text( $cs['card_value'] ?? '' );
			if ( '' !== $o_value ) {
				$outcome = '<p class="avix-csi__stat avix-csi__outcome">'
					. ( '' !== $o_label ? '<span class="avix-csi__stat-label">' . esc_html( $o_label ) . '<span class="avix-csi__sr">: </span></span>' : '' )
					. '<span class="avix-csi__stat-value">' . esc_html( $o_value ) . '</span></p>';
			}

			$metric = '';
			if ( $args['metric'] ) {
				$row = self::metric( $cs );
				if ( $row ) {
					$metric = '<p class="avix-csi__stat avix-csi__metric"><span class="avix-csi__stat-label">' . esc_html( $row[1] ) . '<span class="avix-csi__sr">: </span></span><span class="avix-csi__stat-value">' . esc_html( $row[0] ) . '</span></p>';
				}
			}

			$cta = '<span class="avix-csi__cta" aria-hidden="true"><span class="avix-csi__cta-text">' . esc_html( $args['label'] ) . '</span><span class="avix-csi__cta-icon">' . self::arrow() . '</span></span>';

			$link_attrs = 'class="avix-csi__link" href="' . esc_url( (string) ( $cs['permalink'] ?? get_permalink( $id ) ) ) . '" aria-labelledby="' . esc_attr( $uid . '-n' ) . '"';
			if ( '' !== $desc ) {
				$link_attrs .= ' aria-describedby="' . esc_attr( $uid . '-d' ) . '"';
			}

			return '<article class="' . esc_attr( implode( ' ', $classes ) ) . '" data-csi-card data-csi-id="' . esc_attr( (string) $id ) . '">'
				. '<a ' . $link_attrs . '>'
				. $media
				. '<div class="avix-csi__panel">'
				. '<div class="avix-csi__top">'
				. $meta
				. '<' . $tag . ' class="avix-csi__name" id="' . esc_attr( $uid . '-n' ) . '">' . esc_html( $name ) . '</' . $tag . '>'
				. $chip
				. $desc
				. '</div>'
				. '<div class="avix-csi__bottom">'
				. ( '' !== $outcome . $metric ? '<div class="avix-csi__stats' . ( '' !== $outcome && '' !== $metric ? ' avix-csi__stats--two' : '' ) . '">' . $outcome . $metric . '</div>' : '' )
				. $cta
				. '</div>'
				. '</div>'
				. '</a>'
				. '</article>';
		}

		/**
		 * Responsive card image (lazy unless it is the page's first card).
		 *
		 * @param int    $id    Attachment ID.
		 * @param string $sizes Sizes attribute.
		 * @param bool   $eager Load first.
		 * @param string $name  Client name (alt fallback).
		 */
		private static function img( $id, $sizes, $eager, $name ): string {
			$alt   = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
			$attrs = array(
				'class'    => 'avix-csi__img',
				/* translators: %s: client name. */
				'alt'      => '' !== $alt ? $alt : sprintf( __( '%s website on desktop and mobile', 'avix-widgets' ), $name ),
				'loading'  => $eager ? 'eager' : 'lazy',
				'decoding' => 'async',
				'sizes'    => $sizes,
			);
			if ( $eager ) {
				$attrs['fetchpriority'] = 'high';
			}
			// GIFs keep the original file: resized GIFs stop animating.
			$size = 'image/gif' === get_post_mime_type( $id ) ? 'full' : 'large';
			return (string) wp_get_attachment_image( $id, $size, false, $attrs );
		}

		/**
		 * A dark tile with the client's name, for a study without any image.
		 *
		 * @param string $name Client name.
		 */
		private static function placeholder( $name ): string {
			return '<span class="avix-csi__ph" aria-hidden="true"><span class="avix-csi__ph-px"></span><span class="avix-csi__ph-name">' . esc_html( $name ) . '</span></span>';
		}

		/** The ↗ arrow used on cards and buttons. */
		public static function arrow(): string {
			return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 17 17 7M7 7h10v10"/></svg>';
		}

		/* ------------------------------------------------------------------ */
		/* Case study data                                                     */
		/* ------------------------------------------------------------------ */

		/**
		 * Case_Study::get() (the single source of truth). A small fallback
		 * reads the few card fields straight from post meta while the data
		 * layer is not loaded, so a card never fatals.
		 *
		 * @param int $id Post ID.
		 */
		public static function data( int $id ): array {
			static $cache = array();
			if ( isset( $cache[ $id ] ) ) {
				return $cache[ $id ];
			}
			if ( class_exists( __NAMESPACE__ . '\\Case_Study' ) && method_exists( __NAMESPACE__ . '\\Case_Study', 'get' ) ) {
				$data = Case_Study::get( $id );
				return $cache[ $id ] = is_array( $data ) ? $data : array();
			}
			$post = get_post( $id );
			if ( ! $post || self::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
				return $cache[ $id ] = array();
			}
			$meta = static function ( $key ) use ( $id ) {
				return (string) get_post_meta( $id, 'avix_cs_' . $key, true );
			};
			$metrics = array();
			if ( '' !== $meta( 'metric_1_value' ) && '' !== $meta( 'metric_1_source' ) ) {
				$metrics[] = array( $meta( 'metric_1_value' ), $meta( 'metric_1_label' ), $meta( 'metric_1_source' ) );
			}
			return $cache[ $id ] = array(
				'id'           => $id,
				'title'        => get_the_title( $post ),
				'excerpt'      => has_excerpt( $post ) ? $post->post_excerpt : '',
				'permalink'    => (string) get_permalink( $post ),
				'thumbnail_id' => (int) get_post_thumbnail_id( $post ),
				'client'       => $meta( 'client' ),
				'year'         => $meta( 'year' ),
				'card_tags'    => $meta( 'card_tags' ),
				'card_label'   => $meta( 'card_label' ),
				'card_value'   => $meta( 'card_value' ),
				'hero_render'  => absint( $meta( 'hero_render' ) ),
				'hero_desktop' => absint( $meta( 'hero_desktop' ) ),
				'metrics'      => $metrics,
				'terms'        => array( 'service' => array() ),
			);
		}

		/**
		 * Card image: `card_image` from the data layer (render → featured
		 * image → desktop shot), worked out here when it is not there yet.
		 *
		 * @param int   $id Post ID.
		 * @param array $cs Case study data.
		 */
		public static function image_id( int $id, array $cs ): int {
			$candidates = array( $cs['card_image'] ?? 0, $cs['hero_render'] ?? 0, $cs['thumbnail_id'] ?? get_post_thumbnail_id( $id ), $cs['hero_desktop'] ?? 0 );
			foreach ( $candidates as $candidate ) {
				$candidate = absint( $candidate );
				if ( $candidate && wp_attachment_is_image( $candidate ) ) {
					return $candidate;
				}
			}
			return 0;
		}

		/**
		 * Card chips: `card_tags`, else the service term names. At most 4.
		 *
		 * @param array $cs Case study data.
		 */
		private static function tags( array $cs ): array {
			$tags = $cs['card_tags'] ?? '';
			$tags = is_array( $tags ) ? $tags : explode( ',', (string) $tags );
			$tags = array_values( array_filter( array_map( array( __CLASS__, 'text' ), $tags ), 'strlen' ) );
			if ( ! $tags && ! empty( $cs['terms']['service'] ) && is_array( $cs['terms']['service'] ) ) {
				foreach ( $cs['terms']['service'] as $term ) {
					$name = self::text( is_array( $term ) ? ( $term[0] ?? ( $term['name'] ?? '' ) ) : '' );
					if ( '' !== $name ) {
						$tags[] = $name;
					}
				}
			}
			return array_slice( array_values( array_unique( $tags ) ), 0, 4 );
		}

		/**
		 * First complete metric (value, short label): only rows with a source exist.
		 *
		 * @param array $cs Case study data.
		 */
		private static function metric( array $cs ): array {
			$rows = $cs['metrics'] ?? array();
			if ( ! is_array( $rows ) || ! $rows ) {
				return array();
			}
			$row   = array_values( (array) reset( $rows ) );
			$value = self::text( $row[0] ?? '' );
			$label = self::text( $row[1] ?? '' );
			// "Storefront languages: English, Dutch and German" → the short
			// part fits a card; the full line is on the case study.
			$short = trim( (string) strtok( $label, ':' ) );
			$label = '' !== $short ? $short : $label;
			return '' !== $value && '' !== $label ? array( $value, $label ) : array();
		}

		/**
		 * Plain summary (the excerpt), at most about 200 characters.
		 *
		 * @param array $cs Case study data.
		 */
		private static function excerpt( array $cs ): string {
			$text = self::text( $cs['excerpt'] ?? '' );
			if ( '' === $text && '' !== self::text( $cs['summary'] ?? '' ) ) {
				$text = self::text( $cs['summary'] );
			}
			if ( function_exists( 'mb_strlen' ) && mb_strlen( $text ) > 220 ) {
				$text = rtrim( mb_substr( $text, 0, 210 ), " \t\n\r\0\x0B,;:.-" ) . '…';
			}
			return $text;
		}

		/* ------------------------------------------------------------------ */
		/* AJAX                                                                */
		/* ------------------------------------------------------------------ */

		/**
		 * Returns { html, hasMore, shown, total, page, service, count }.
		 * Requests are GET and the answer is the same for every visitor
		 * (published studies only, no nonce), so browsers, the CDN and
		 * LiteSpeed's AJAX cache may keep it for a few minutes.
		 */
		public static function handle() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- public, read-only data; every value is clamped in args().
			$raw = array();
			foreach ( array( 'page', 'per_page', 'service', 'services', 'exclude', 'orderby', 'featured', 'style', 'metric', 'tag', 'label', 'uid' ) as $key ) {
				if ( isset( $_REQUEST[ $key ] ) && ! is_array( $_REQUEST[ $key ] ) ) {
					$raw[ $key ] = wp_unslash( $_REQUEST[ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised per key in args().
				}
			}
			// phpcs:enable

			$args  = self::args( $raw );
			$query = self::query( $args );
			$total = (int) $query->found_posts;
			$shown = self::shown( $query, $args );

			if ( ! headers_sent() ) {
				// admin-ajax.php sent no-cache headers first: replace them.
				header_remove( 'Expires' );
				header_remove( 'Pragma' );
				header( 'Cache-Control: public, max-age=' . (int) apply_filters( 'avix_case_studies_ajax_ttl', self::CACHE_TTL ) );
			}

			wp_send_json(
				array(
					'html'    => self::cards( $query, $args ),
					'hasMore' => $shown < $total,
					'shown'   => $shown,
					'total'   => $total,
					'page'    => $args['page'],
					'service' => $args['service'],
					'count'   => count( $query->posts ),
				)
			);
		}

		/* ------------------------------------------------------------------ */
		/* Helpers                                                             */
		/* ------------------------------------------------------------------ */

		/**
		 * "1" → "01".
		 *
		 * @param int $n Number.
		 */
		private static function pad( $n ): string {
			return str_pad( (string) (int) $n, 2, '0', STR_PAD_LEFT );
		}

		/**
		 * Plain, trimmed single-line text.
		 *
		 * @param mixed $value Value.
		 */
		public static function text( $value ): string {
			if ( ! is_scalar( $value ) ) {
				return '';
			}
			$text = html_entity_decode( wp_strip_all_tags( (string) $value ), ENT_QUOTES, 'UTF-8' );
			return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
		}

		/**
		 * Comma string or array → unique term slugs (max 30).
		 *
		 * @param mixed $value Raw list.
		 */
		private static function slug_list( $value ): array {
			$list = is_array( $value ) ? $value : explode( ',', (string) $value );
			$list = array_filter( array_map( 'sanitize_title', array_map( 'strval', array_filter( $list, 'is_scalar' ) ) ), 'strlen' );
			return array_slice( array_values( array_unique( $list ) ), 0, 30 );
		}

		/**
		 * @param mixed $value '1', 'yes', true…
		 */
		private static function flag( $value ): bool {
			return in_array( $value, array( true, 1, '1', 'yes', 'true' ), true );
		}

		/**
		 * @param string $text Text.
		 * @param int    $max  Characters.
		 */
		private static function cap( $text, $max ): string {
			return function_exists( 'mb_substr' ) ? mb_substr( $text, 0, $max ) : substr( $text, 0, $max );
		}
	}

	Cards::init();
}
