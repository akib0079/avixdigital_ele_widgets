<?php
/**
 * Post cards for the Post Grid widget, shared by its first render and the
 * "avix_posts" AJAX endpoint (topic filters and "Load more"), so both always
 * print identical markup.
 *
 * Loaded at boot, before Elementor registers widgets, so nothing here may
 * depend on widget classes. The endpoint serves public post data only: no
 * nonce (cached pages would serve stale ones), every input is clamped or
 * validated instead.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( __NAMESPACE__ . '\\Post_Cards' ) ) {

	final class Post_Cards {

		const ACTION = 'avix_posts';

		/** Reading speed used for "N min read". */
		const WORDS_PER_MINUTE = 220;

		const MAX_PER_PAGE = 24;

		/** Card heading tags the widget may ask for. */
		const TAGS = array( 'h2', 'h3', 'h4', 'div' );

		/** The widget's query vars: ?topic= and ?pg=, or ?topic-<id>= / ?pg-<id>= for a second grid. */
		const VAR_PATTERN = '/^(topic|pg)(-[a-z0-9]+)?$/';

		/** Seconds browsers and caches may keep an "avix_posts" answer. */
		const CACHE_TTL = 300;

		public static function init() {
			add_action( 'wp_ajax_' . self::ACTION, array( __CLASS__, 'handle' ) );
			add_action( 'wp_ajax_nopriv_' . self::ACTION, array( __CLASS__, 'handle' ) );
			// Yoast SEO's crawl cleanup ("Remove unregistered URL parameters")
			// would 301 every topic and page link back to the bare page.
			add_filter( 'Yoast\WP\SEO\allowlist_permalink_vars', array( __CLASS__, 'allow_vars' ) );
		}

		/**
		 * Lets Yoast keep this request's topic/page vars, and post_type on a
		 * search (the blog search form limits results to posts).
		 *
		 * @param mixed $vars Allowed query vars.
		 * @return array
		 */
		public static function allow_vars( $vars ): array {
			$vars = is_array( $vars ) ? $vars : array();
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- only key names are read, to allow them.
			foreach ( array_keys( $_GET ) as $key ) {
				if ( preg_match( self::VAR_PATTERN, (string) $key ) ) {
					$vars[] = (string) $key;
				}
			}
			if ( isset( $_GET['s'], $_GET['post_type'] ) && 'post' === $_GET['post_type'] ) {
				$vars[] = 'post_type';
			}
			// phpcs:enable
			return array_values( array_unique( $vars ) );
		}

		/**
		 * Category slugs never offered as a topic or shown on a card unless a
		 * widget asks for them by name. Matched by slug, not by the default
		 * category option, which a site may point at a real topic.
		 */
		public static function hidden_topics(): array {
			$slugs = apply_filters( 'avix_post_grid_hidden_topics', array( 'uncategorized' ) );
			return array_values( array_filter( array_map( 'sanitize_title', array_map( 'strval', (array) $slugs ) ), 'strlen' ) );
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
			$cats = self::slug_list( $raw['cats'] ?? array() );

			$exclude = array_slice( array_filter( array_map( 'absint', wp_parse_id_list( $raw['exclude'] ?? array() ) ) ), 0, 20 );

			$orderby = (string) ( $raw['orderby'] ?? 'date' );
			$orderby = in_array( $orderby, array( 'date', 'modified', 'title', 'comment_count' ), true ) ? $orderby : 'date';

			$tag = strtolower( (string) ( $raw['tag'] ?? 'h3' ) );

			$label = sanitize_text_field( (string) ( $raw['label'] ?? '' ) );
			$time  = sanitize_text_field( (string) ( $raw['time_label'] ?? '' ) );

			return array(
				'page'       => max( 1, min( 500, absint( $raw['page'] ?? 1 ) ) ),
				'per_page'   => max( 1, min( self::MAX_PER_PAGE, absint( $raw['per_page'] ?? 6 ) ) ),
				'topic'      => self::topic( (string) ( $raw['topic'] ?? '' ), $cats ),
				'cats'       => $cats,
				'exclude'    => array_values( $exclude ),
				'orderby'    => $orderby,
				'order'      => 'ASC' === strtoupper( (string) ( $raw['order'] ?? 'DESC' ) ) ? 'ASC' : 'DESC',
				'slot'       => self::flag( $raw['slot'] ?? 0 ),
				'words'      => max( 5, min( 60, absint( $raw['words'] ?? 22 ) ) ),
				'excerpt'    => self::flag( $raw['excerpt'] ?? 1 ),
				'image'      => self::flag( $raw['image'] ?? 1 ),
				'cat'        => self::flag( $raw['cat'] ?? 1 ),
				'date'       => self::flag( $raw['date'] ?? 1 ),
				'time'       => self::flag( $raw['time'] ?? 1 ),
				'tag'        => in_array( $tag, self::TAGS, true ) ? $tag : 'h3',
				'label'      => '' !== $label ? self::cap( $label, 40 ) : __( 'Read article', 'avix-widgets' ),
				'time_label' => '' !== $time ? self::cap( $time, 24 ) : __( 'min read', 'avix-widgets' ),
			);
		}

		/**
		 * A topic must be an existing category slug, and one of the allowed
		 * categories when the widget limits them.
		 *
		 * @param string $slug Requested slug.
		 * @param array  $cats Allowed slugs (empty = any).
		 */
		public static function topic( $slug, array $cats = array() ): string {
			$slug = sanitize_title( $slug );
			if ( '' === $slug || ( $cats && ! in_array( $slug, $cats, true ) ) ) {
				return '';
			}
			$term = get_term_by( 'slug', $slug, 'category' );
			return $term && ! is_wp_error( $term ) ? $slug : '';
		}

		/* ------------------------------------------------------------------ */
		/* Query                                                               */
		/* ------------------------------------------------------------------ */

		/**
		 * Where a page starts and how many posts it holds. With 'slot' the
		 * project card takes one place on page 1, so every page (card included)
		 * holds per_page items and grid rows stay full.
		 *
		 * @param array $args Output of args().
		 * @return int[] array( offset, count )
		 */
		public static function window( array $args ): array {
			$per  = $args['per_page'];
			$slot = $args['slot'] && $per > 1 ? 1 : 0;
			if ( 1 === $args['page'] ) {
				return array( 0, $per - $slot );
			}
			return array( $per - $slot + ( $args['page'] - 2 ) * $per, $per );
		}

		/**
		 * Grid posts for one page. Found rows stay on: "Showing X of Y" and
		 * "Load more" need the total.
		 *
		 * @param array $args Output of args().
		 */
		public static function query( array $args ): \WP_Query {
			list( $offset, $count ) = self::window( $args );

			$query = array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => $count,
				'offset'              => $offset,
				'ignore_sticky_posts' => true,
				// ID breaks ties (equal dates, titles, 0 comments) so OFFSET paging
				// never repeats or skips a post on MySQL.
				'orderby'             => array(
					$args['orderby'] => $args['order'],
					'ID'             => $args['order'],
				),
				'has_password'        => false,
			);
			// The featured post is left out of "All" only: inside a topic every
			// matching post is listed, so a topic never looks empty because its
			// one post is featured above.
			if ( $args['exclude'] && '' === $args['topic'] ) {
				$query['post__not_in'] = $args['exclude']; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- at most 20 IDs (the featured post).
			}
			$slugs = '' !== $args['topic'] ? array( $args['topic'] ) : $args['cats'];
			if ( $slugs ) {
				$query['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => 'category',
						'field'    => 'slug',
						'terms'    => $slugs,
					),
				);
			}
			$result = new \WP_Query( $query );
			// One query for every card's image and alt text instead of one each.
			if ( $result->posts ) {
				update_post_thumbnail_cache( $result );
			}
			return $result;
		}

		/**
		 * Cards for a query, numbered from $start for the stagger.
		 *
		 * @param \WP_Query $query Grid query.
		 * @param array     $args  Output of args().
		 * @param int       $start Index of the first card.
		 */
		public static function cards( \WP_Query $query, array $args, $start = 0 ): string {
			$html = '';
			foreach ( $query->posts as $i => $post ) {
				$html .= self::card( $post, $args, $start + $i );
			}
			return $html;
		}

		/**
		 * Number of grid posts shown up to and including the query's page.
		 *
		 * @param \WP_Query $query Grid query.
		 * @param array     $args  Output of args().
		 */
		public static function shown( \WP_Query $query, array $args ): int {
			list( $offset ) = self::window( $args );
			return min( (int) $query->found_posts, $offset + count( $query->posts ) );
		}

		/* ------------------------------------------------------------------ */
		/* Markup                                                              */
		/* ------------------------------------------------------------------ */

		/**
		 * One grid card. A single link (the title) is stretched over the whole
		 * card, so there are no nested links and the card reads as one item.
		 *
		 * @param \WP_Post $post  Post.
		 * @param array    $args  Output of args().
		 * @param int      $index Position in the list (kept for filters and extensions).
		 */
		public static function card( \WP_Post $post, array $args, $index = 0 ): string {
			$title = wp_strip_all_tags( get_the_title( $post ) );
			$title = '' !== $title ? $title : __( '(Untitled)', 'avix-widgets' );
			$url   = get_permalink( $post );
			$term  = self::term( $post, $args['topic'] );
			$tag   = $args['tag'];

			$classes = 'avix-pg__card';
			$media   = '';
			if ( $args['image'] ) {
				$image   = self::image( $post, 'medium_large', '(max-width: 600px) 100vw, (max-width: 1024px) 50vw, 400px', false, 'avix-pg__img' );
				$media   = '<div class="avix-pg__media">' . ( '' !== $image ? $image : self::placeholder( $post, $term ) ) . '</div>';
				$classes .= '' !== $image ? '' : ' is-placeholder';
			} else {
				$classes .= ' is-text';
			}

			$excerpt = '';
			if ( $args['excerpt'] ) {
				$text    = self::excerpt( $post, $args['words'] );
				$excerpt = '' !== $text ? '<p class="avix-pg__excerpt">' . esc_html( $text ) . '</p>' : '';
			}

			return sprintf(
				'<article class="%1$s" data-pg-card>%2$s<div class="avix-pg__body">%3$s<%4$s class="avix-pg__card-title"><a class="avix-pg__link" href="%5$s"><span class="avix-pg__link-text">%6$s</span></a></%4$s>%7$s<span class="avix-pg__read" aria-hidden="true">%8$s<span class="avix-pg__read-icon">%9$s</span></span></div></article>',
				esc_attr( $classes ),
				$media,
				self::meta( $post, $args, $term ),
				esc_html( $tag ),
				esc_url( $url ),
				esc_html( $title ),
				$excerpt,
				esc_html( $args['label'] ),
				self::arrow()
			);
		}

		/**
		 * "Category · Jul 18, 2026 · 5 min read".
		 *
		 * @param \WP_Post      $post Post.
		 * @param array         $args Output of args().
		 * @param \WP_Term|null $term Category to show.
		 * @param bool          $chip Category as a pill (featured card) instead of inline.
		 */
		public static function meta( \WP_Post $post, array $args, $term, $chip = false ): string {
			$parts = array();
			if ( $args['cat'] && $term && ! $chip ) {
				$parts['cat'] = '<span class="avix-pg__cat">' . esc_html( $term->name ) . '</span>';
			}
			if ( $args['date'] ) {
				$parts['date'] = sprintf(
					'<time datetime="%1$s">%2$s</time>',
					esc_attr( get_the_date( 'c', $post ) ),
					esc_html( get_the_date( self::date_format(), $post ) )
				);
			}
			if ( $args['time'] ) {
				$parts['time'] = '<span>' . esc_html( self::minutes( $post ) . ' ' . $args['time_label'] ) . '</span>';
			}
			if ( ! $parts ) {
				return '';
			}
			// Every item starts with its pixel separator. The line is shifted left
			// by one separator and clipped there, so the separator of any item
			// that starts a line (the first one, or one that wrapped) is cut off
			// and no dot ever dangles at a line end or start.
			$html = '';
			foreach ( $parts as $key => $part ) {
				$html .= '<span class="avix-pg__mi avix-pg__mi--' . esc_attr( $key ) . '"><span class="avix-pg__sep" aria-hidden="true"></span>' . $part . '</span>';
			}
			return '<p class="avix-pg__meta">' . $html . '</p>';
		}

		/**
		 * Compact date ("Jul 18, 2026") so the meta line stays on one line in
		 * three columns. Filterable for sites that prefer their own format.
		 */
		public static function date_format(): string {
			return (string) apply_filters( 'avix_post_grid_date_format', 'M j, Y' );
		}

		/**
		 * Featured image with srcset, or '' when the post has none.
		 *
		 * @param \WP_Post $post  Post.
		 * @param string   $size  Image size.
		 * @param string   $sizes Sizes attribute.
		 * @param bool     $eager Load first (above the fold).
		 * @param string   $class Image class.
		 */
		public static function image( \WP_Post $post, $size, $sizes, $eager, $class ): string {
			$id = (int) get_post_thumbnail_id( $post );
			if ( ! $id ) {
				return '';
			}
			$stored = (string) get_post_meta( $id, '_wp_attachment_image_alt', true );
			$attrs  = array(
				'class'    => $class,
				'alt'      => '' !== $stored ? $stored : '',
				'loading'  => $eager ? 'eager' : 'lazy',
				'decoding' => 'async',
				'sizes'    => $sizes,
			);
			if ( $eager ) {
				$attrs['fetchpriority'] = 'high';
			}
			// GIFs keep the original file: resized GIFs stop animating.
			if ( 'image/gif' === get_post_mime_type( $id ) ) {
				$size = 'full';
			}
			return (string) wp_get_attachment_image( $id, $size, false, $attrs );
		}

		/**
		 * Branded cover for posts without an image: a dark tile with a small
		 * pixel constellation (different per post, drawn in the character's grid)
		 * and the category name.
		 *
		 * @param \WP_Post      $post Post.
		 * @param \WP_Term|null $term Category.
		 */
		public static function placeholder( \WP_Post $post, $term ): string {
			// Stable per post and portable (no integer overflow on any platform).
			$bytes = array_values( unpack( 'C*', md5( 'avix-pg-' . $post->ID, true ) . md5( 'avix-pg-x' . $post->ID, true ) ) );
			$fades = array( '1', '.7', '.45', '.25' );
			$taken = array();
			$rects = '';
			for ( $n = 0; $n < 32; $n += 2 ) {
				$x   = 9 + $bytes[ $n ] % 7;
				$y   = 1 + ( $bytes[ $n ] >> 3 ) % 8;
				$key = $x . ':' . $y;
				if ( isset( $taken[ $key ] ) ) {
					continue;
				}
				$taken[ $key ] = true;
				$rects        .= '<rect x="' . (int) $x . '" y="' . (int) $y . '" width="1" height="1" opacity="' . esc_attr( $fades[ $bytes[ $n + 1 ] % 4 ] ) . '"/>';
			}
			$label = $term ? $term->name : get_bloginfo( 'name' );
			return '<span class="avix-pg__ph"><svg class="avix-pg__ph-px" viewBox="0 0 16 10" preserveAspectRatio="xMaxYMid meet" aria-hidden="true" focusable="false">'
				. $rects . '</svg><span class="avix-pg__ph-label">' . esc_html( $label ) . '</span></span>';
		}

		/** The ↗ arrow used on cards and buttons. */
		public static function arrow(): string {
			return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 17 17 7M7 7h10v10"/></svg>';
		}

		/* ------------------------------------------------------------------ */
		/* Post data                                                           */
		/* ------------------------------------------------------------------ */

		/**
		 * Category shown on a card: the active topic when the post has it,
		 * else the first real category (never "Uncategorized" or another
		 * hidden topic).
		 *
		 * @param \WP_Post $post  Post.
		 * @param string   $topic Active topic slug.
		 * @return \WP_Term|null
		 */
		public static function term( \WP_Post $post, $topic = '' ) {
			$terms = get_the_category( $post->ID );
			if ( ! $terms ) {
				return null;
			}
			$hidden = self::hidden_topics();
			$first  = null;
			foreach ( $terms as $term ) {
				if ( '' !== $topic && $term->slug === $topic ) {
					return $term;
				}
				if ( null === $first && ! in_array( $term->slug, $hidden, true ) ) {
					$first = $term;
				}
			}
			return $first;
		}

		/**
		 * Plain-text excerpt: the hand-written one, else the trimmed content.
		 *
		 * @param \WP_Post $post  Post.
		 * @param int      $words Words to keep.
		 */
		public static function excerpt( \WP_Post $post, $words ): string {
			$text = has_excerpt( $post ) ? $post->post_excerpt : strip_shortcodes( $post->post_content );
			$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $text ) ) );
			$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
			$cut  = wp_trim_words( $text, $words, '' );
			if ( $cut === $text ) {
				return $text;
			}
			// "tasks,…" reads badly: drop trailing punctuation before the ellipsis.
			return preg_replace( '/[\s,;:.\x{2013}\x{2014}-]+$/u', '', html_entity_decode( $cut, ENT_QUOTES, 'UTF-8' ) ) . '…';
		}

		/**
		 * Reading time in whole minutes: ceil( words / 220 ), at least 1.
		 *
		 * @param \WP_Post $post Post.
		 */
		public static function minutes( \WP_Post $post ): int {
			$text  = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
			$words = count( preg_split( '/\s+/u', trim( $text ), -1, PREG_SPLIT_NO_EMPTY ) );
			return max( 1, (int) ceil( $words / self::WORDS_PER_MINUTE ) );
		}

		/* ------------------------------------------------------------------ */
		/* AJAX                                                                */
		/* ------------------------------------------------------------------ */

		/**
		 * Returns { html, hasMore, shown, total, page, topic }. Requests are GET
		 * and the answer is the same for every visitor (public posts only, no
		 * nonce), so browsers, the CDN and LiteSpeed's AJAX cache may keep it
		 * for a few minutes instead of booting WordPress on every chip click.
		 */
		public static function handle() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- public, read-only data; every value is clamped in args().
			$raw = array();
			foreach ( array( 'page', 'per_page', 'slot', 'topic', 'cats', 'exclude', 'orderby', 'order', 'words', 'excerpt', 'image', 'cat', 'date', 'time', 'tag', 'label', 'time_label' ) as $key ) {
				if ( isset( $_REQUEST[ $key ] ) && ! is_array( $_REQUEST[ $key ] ) ) {
					$raw[ $key ] = wp_unslash( $_REQUEST[ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised per key in args().
				}
			}
			// phpcs:enable

			$args          = self::args( $raw );
			$query         = self::query( $args );
			list( $start ) = self::window( $args );
			$shown         = self::shown( $query, $args );
			$total         = (int) $query->found_posts;

			if ( ! headers_sent() ) {
				// admin-ajax.php sent no-cache headers first: replace them.
				header_remove( 'Expires' );
				header_remove( 'Pragma' );
				header( 'Cache-Control: public, max-age=' . (int) apply_filters( 'avix_post_grid_ajax_ttl', self::CACHE_TTL ) );
			}

			wp_send_json_success(
				array(
					'html'    => self::cards( $query, $args, $start ),
					'count'   => count( $query->posts ),
					'hasMore' => $shown < $total,
					'shown'   => $shown,
					'total'   => $total,
					'page'    => $args['page'],
					'topic'   => $args['topic'],
				)
			);
		}

		/* ------------------------------------------------------------------ */
		/* Helpers                                                             */
		/* ------------------------------------------------------------------ */

		/**
		 * Comma string or array → unique category slugs (max 30).
		 *
		 * @param mixed $value Raw list.
		 */
		private static function slug_list( $value ): array {
			$list = is_array( $value ) ? $value : explode( ',', (string) $value );
			$list = array_filter( array_map( 'sanitize_title', array_map( 'strval', $list ) ), 'strlen' );
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

	Post_Cards::init();
}
