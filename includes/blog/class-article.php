<?php
/**
 * The data an article page prints (includes/blog/templates/article.php): breadcrumb,
 * category, dek, hero image and mosaic, author, dates, reading time, the
 * service the calls to action point to, share links, the TOC with the
 * processed content, and related posts.
 *
 * Nothing here prints schema or meta tags: Yoast SEO owns Article,
 * BreadcrumbList, canonical and Open Graph. The visible breadcrumb comes from
 * Yoast's own crumbs, so it always equals the BreadcrumbList.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Blog;

defined( 'ABSPATH' ) || exit;

final class Article {

	/** Reading speed, the same as the blog index (Post_Cards::WORDS_PER_MINUTE). */
	const WORDS_PER_MINUTE = 220;

	/** The founder photo on avixdigital.com (media library ID), used for user 1 only. */
	const FOUNDER_IMAGE_ID = 8651;

	/** Brand name used in the author's role line. */
	const BRAND = 'AvixDigital';

	/** Square author photo (hard crop from the top centre): 104px author box at 2x. */
	const AUTHOR_SIZE = 'avix-author';

	/** With sub-sections on, a TOC longer than this lists the sections only. */
	const TOC_MAX_ENTRIES = 12;

	public static function init(): void {
		add_action( 'after_setup_theme', array( __CLASS__, 'image_sizes' ) );
	}

	/**
	 * A square head-and-shoulders crop for author photos, cut from the top
	 * centre so portraits keep the head. Generated for new uploads; an older
	 * photo without it falls back to its medium size (Media > Regenerate, or
	 * a re-upload, adds it).
	 */
	public static function image_sizes(): void {
		add_image_size( self::AUTHOR_SIZE, 208, 208, array( 'center', 'top' ) );
	}

	/**
	 * Everything includes/blog/templates/article.php needs. Call inside the loop.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function view( \WP_Post $post ): array {
		$settings = class_exists( __NAMESPACE__ . '\Settings', false ) ? Settings::all() : array();
		$settings = array_merge(
			array(
				'toc_depth' => 2,
				'rail_cta'  => 1,
				'share'     => 1,
				'related'   => 3,
				'proof'     => '',
				'proof_2'   => '',
			),
			$settings
		);

		// The hero image first: its fetchpriority="high" tells WordPress the
		// page's priority image is taken, so the_content does not hand
		// "high" to the first figure (see also Blog::omit_threshold()).
		$image   = self::hero_image( $post );
		$content = self::content( (int) $settings['toc_depth'] );
		$items   = $content['items'];
		$has_toc = $content['count'] >= 3;
		$term    = self::primary_term( $post );

		return array(
			'id'        => (int) $post->ID,
			'title'     => self::title( $post ),
			'crumbs'    => self::crumbs( $post ),
			'chip'      => $term ? self::plain( $term->name ) : '',
			'dek'       => self::dek( $post ),
			'image'     => $image,
			'mosaic'    => self::mosaic( (int) $post->ID ),
			'author'    => self::author( $post ),
			'dates'     => self::dates( $post ),
			'minutes'   => self::minutes( $post ),
			'service'   => self::service( $post ),
			'contact'   => self::contact_url(),
			'share'     => ! empty( $settings['share'] ) ? self::share( $post ) : array(),
			'toc'       => $has_toc ? Toc::desktop( $items ) : '',
			'toc_m'     => $has_toc ? Toc::mobile( $items ) : '',
			'content'   => $content['html'],
			'pages'     => self::page_links(),
			'related'   => self::related( $post, (int) $settings['related'], $term ),
			'blog_url'  => self::blog_url(),
			'settings'  => $settings,
		);
	}

	/* ------------------------------------------------------------------ */
	/* Content                                                            */
	/* ------------------------------------------------------------------ */

	/**
	 * the_content for the current post, with heading ids, the TOC and the
	 * component attributes (Content::enhance()).
	 *
	 * With sub-sections on (depth 3), a TOC of more than TOC_MAX_ENTRIES
	 * entries lists the sections only: a long list does not fit the rail and
	 * puts every entry between keyboard users and the article.
	 *
	 * @param int $depth TOC depth (2 or 3).
	 * @return array{html:string,items:array,count:int}
	 */
	public static function content( int $depth = 2 ): array {
		add_filter( 'wp_content_img_tag', array( __CLASS__, 'lazy_image' ), 11 );
		add_filter( 'render_block_data', array( __CLASS__, 'image_lightbox' ), 10, 1 );
		$html = (string) apply_filters( 'the_content', get_the_content() );
		remove_filter( 'render_block_data', array( __CLASS__, 'image_lightbox' ), 10 );
		remove_filter( 'wp_content_img_tag', array( __CLASS__, 'lazy_image' ), 11 );
		$html = str_replace( ']]>', ']]&gt;', $html );
		$toc  = Toc::process( $html, $depth );
		if ( $depth > 2 && $toc['count'] > self::TOC_MAX_ENTRIES ) {
			$toc = Toc::process( $html, 2 );
		}
		$toc['html'] = class_exists( __NAMESPACE__ . '\Content', false ) ? Content::enhance( $toc['html'] ) : $toc['html'];
		return $toc;
	}

	/**
	 * Article images open in WordPress's own lightbox (core/image "Expand on
	 * click"): the light infographics carry small interface text that phones
	 * cannot read at column width. Only unlinked images, only in the article
	 * body, and an author's explicit lightbox choice on a block is kept.
	 *
	 * @param mixed $block Parsed block (render_block_data).
	 * @return mixed
	 */
	public static function image_lightbox( $block ) {
		if ( ! is_array( $block ) || ! isset( $block['blockName'] ) || 'core/image' !== $block['blockName'] ) {
			return $block;
		}
		$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
		if ( isset( $attrs['lightbox'] ) || ( isset( $attrs['linkDestination'] ) && 'none' !== $attrs['linkDestination'] ) ) {
			return $block;
		}
		$attrs['lightbox'] = array( 'enabled' => true );
		$block['attrs']    = $attrs;
		return $block;
	}

	/**
	 * Content images all sit below the hero (the page's LCP image), so each
	 * one is lazy and none is "high" priority. Runs after WordPress's own
	 * loading attributes and after Elementor's Optimized Image Loading
	 * (wp_content_img_tag, priority 10), which would otherwise treat the
	 * first content images as above the fold, because the hero is printed
	 * after the_content has run.
	 *
	 * @param mixed $image One <img> tag.
	 */
	public static function lazy_image( $image ) {
		if ( ! is_string( $image ) || 0 !== stripos( ltrim( $image ), '<img' ) ) {
			return $image;
		}
		if ( class_exists( '\WP_HTML_Tag_Processor' ) ) {
			$tag = new \WP_HTML_Tag_Processor( $image );
			if ( $tag->next_tag( 'img' ) ) {
				$tag->remove_attribute( 'fetchpriority' );
				$tag->remove_attribute( 'loading' );
				$tag->set_attribute( 'loading', 'lazy' );
				return $tag->get_updated_html();
			}
			return $image;
		}
		// Older WordPress: drop whole loading/fetchpriority attributes (quoted values are skipped whole).
		$attrs = (string) preg_replace_callback(
			'~\s+([^\s=>/"\']+)(\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s"\'>]+))?~',
			static function ( $m ) {
				return in_array( strtolower( $m[1] ), array( 'loading', 'fetchpriority' ), true ) ? '' : $m[0];
			},
			(string) substr( ltrim( $image ), 4 )
		);
		return '<img loading="lazy"' . $attrs;
	}

	private static function page_links(): string {
		return (string) wp_link_pages(
			array(
				'before' => '<nav class="avix-art-pages" aria-label="' . esc_attr__( 'Pages', 'avix-widgets' ) . '">',
				'after'  => '</nav>',
				'echo'   => 0,
			)
		);
	}

	/* ------------------------------------------------------------------ */
	/* Hero                                                               */
	/* ------------------------------------------------------------------ */

	/**
	 * The title as escaped HTML (texturised, like the_title()).
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function title( \WP_Post $post ): string {
		$title = get_the_title( $post );
		return '' !== trim( (string) $title ) ? esc_html( wp_strip_all_tags( $title ) ) : esc_html__( '(Untitled)', 'avix-widgets' );
	}

	/**
	 * Plain text (entities decoded, tags stripped, whitespace collapsed).
	 *
	 * @param mixed $text Text.
	 */
	public static function plain( $text ): string {
		$text = html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
	}

	/**
	 * Breadcrumb trail: Yoast's crumbs (Home › Blog › Post with the SEO
	 * module's blog crumb), else the same trail built here. Each item is
	 * array( 'text', 'url' ); the last one is the current page (no url).
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function crumbs( \WP_Post $post ): array {
		$links = array();
		if ( function_exists( 'YoastSEO' ) ) {
			try {
				$surface = YoastSEO();
				$meta    = is_object( $surface ) && isset( $surface->meta ) ? $surface->meta->for_current_page() : null;
				$crumbs  = is_object( $meta ) ? $meta->breadcrumbs : null;
				if ( is_array( $crumbs ) ) {
					foreach ( $crumbs as $crumb ) {
						if ( is_array( $crumb ) && isset( $crumb['text'] ) && '' !== self::plain( $crumb['text'] ) ) {
							$links[] = array(
								'text' => self::plain( $crumb['text'] ),
								'url'  => isset( $crumb['url'] ) ? (string) $crumb['url'] : '',
							);
						}
					}
				}
			} catch ( \Throwable $error ) {
				$links = array();
			}
		}
		if ( count( $links ) < 2 ) {
			$links = array(
				array(
					'text' => __( 'Home', 'avix-widgets' ),
					'url'  => home_url( '/' ),
				),
			);
			$blog = self::blog_page_id();
			if ( $blog ) {
				$links[] = array(
					'text' => class_exists( '\AvixWidgets\SEO\Breadcrumbs' ) ? \AvixWidgets\SEO\Breadcrumbs::crumb_title( $blog ) : self::plain( get_the_title( $blog ) ),
					'url'  => (string) get_permalink( $blog ),
				);
			}
			$links[] = array(
				'text' => self::plain( get_the_title( $post ) ),
				'url'  => '',
			);
		}
		$links[ count( $links ) - 1 ]['url'] = '';
		return $links;
	}

	private static function blog_page_id(): int {
		if ( class_exists( '\AvixWidgets\SEO\Breadcrumbs' ) ) {
			return \AvixWidgets\SEO\Breadcrumbs::blog_page_id();
		}
		$id = (int) get_option( 'page_for_posts' );
		if ( ! $id ) {
			$page = get_page_by_path( 'blog', OBJECT, 'page' );
			$id   = $page ? (int) $page->ID : 0;
		}
		return ( $id > 0 && 'publish' === get_post_status( $id ) ) ? $id : 0;
	}

	public static function blog_url(): string {
		$id = self::blog_page_id();
		return $id ? (string) get_permalink( $id ) : home_url( '/blog/' );
	}

	/**
	 * Yoast's primary category, else the first real category (never Uncategorized).
	 *
	 * @param \WP_Post $post Post.
	 * @return \WP_Term|null
	 */
	public static function primary_term( \WP_Post $post ) {
		$terms = get_the_category( $post->ID );
		if ( ! $terms ) {
			return null;
		}
		$primary = (int) get_post_meta( $post->ID, '_yoast_wpseo_primary_category', true );
		if ( $primary ) {
			foreach ( $terms as $term ) {
				if ( (int) $term->term_id === $primary ) {
					return $term;
				}
			}
		}
		$hidden = class_exists( '\AvixWidgets\Post_Cards' ) ? \AvixWidgets\Post_Cards::hidden_topics() : array( 'uncategorized' );
		foreach ( $terms as $term ) {
			if ( ! in_array( $term->slug, $hidden, true ) ) {
				return $term;
			}
		}
		return null;
	}

	/**
	 * The dek: the hand-written excerpt, else Yoast's meta description, else
	 * nothing (never a trimmed first paragraph, which repeats the intro).
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function dek( \WP_Post $post ): string {
		$text = self::plain( $post->post_excerpt );
		if ( '' === $text ) {
			$desc = (string) get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true );
			if ( false !== strpos( $desc, '%%' ) ) {
				$desc = function_exists( 'wpseo_replace_vars' ) ? (string) wpseo_replace_vars( $desc, $post ) : '';
			}
			$text = false === strpos( $desc, '%%' ) ? self::plain( $desc ) : '';
		}
		return $text;
	}

	/**
	 * Featured image for the hero frame: the LCP image, so no lazy loading,
	 * high fetch priority and no pixel reveal.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function hero_image( \WP_Post $post ): string {
		$id = (int) get_post_thumbnail_id( $post );
		if ( ! $id || ! wp_attachment_is_image( $id ) ) {
			return '';
		}
		$size = 'image/gif' === get_post_mime_type( $id ) ? 'full' : 'large';
		return (string) wp_get_attachment_image(
			$id,
			$size,
			false,
			array(
				'class'         => 'avix-art-hero__img no-pixel-reveal',
				'alt'           => trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ),
				'loading'       => 'eager',
				'fetchpriority' => 'high',
				'decoding'      => 'async',
				'sizes'         => '(max-width: 600px) calc(100vw - 40px), (max-width: 1023px) 560px, (max-width: 1199px) 42vw, 540px',
			)
		);
	}

	/**
	 * The pixel-mosaic band over the hero frame: 5 rows of 34 squares, denser
	 * towards the bottom and the centre, different per post and stable.
	 *
	 * The squares come in three groups (<g>) that twinkle out of step, so the
	 * animation runs on three elements instead of every square.
	 *
	 * @param int $seed Post ID.
	 */
	public static function mosaic( int $seed ): string {
		$rows   = 5;
		$cols   = 34;
		$bytes  = '';
		for ( $i = 0; strlen( $bytes ) < $rows * $cols * 2; $i++ ) {
			$bytes .= md5( 'avix-art-' . $seed . '-' . $i, true );
		}
		$b      = array_values( unpack( 'C*', $bytes ) );
		$groups = array( '', '', '' );
		$k      = 0;
		for ( $r = 0; $r < $rows; $r++ ) {
			$depth = ( $r + 1 ) / $rows;
			for ( $c = 0; $c < $cols; $c++ ) {
				$n      = ( $r * $cols + $c ) * 2;
				$centre = 1 - abs( $c - ( $cols - 1 ) / 2 ) / ( $cols / 2 );
				$chance = ( 0.06 + 0.58 * pow( $depth, 1.3 ) ) * ( 0.3 + 0.95 * $centre );
				if ( $b[ $n ] / 255 >= min( 0.9, $chance ) ) {
					continue;
				}
				$opacity = 0.24 + 0.76 * $depth * ( 0.45 + 0.55 * $centre ) * ( 0.6 + 0.4 * $b[ $n + 1 ] / 255 );
				$rect    = '<rect x="' . ( 4 + $c * 16 ) . '" y="' . ( 4 + $r * 16 ) . '" width="8" height="8" opacity="' . esc_attr( rtrim( rtrim( number_format( min( 1, $opacity ), 2, '.', '' ), '0' ), '.' ) ) . '"/>';
				// Every 5th square in the third group, every 3rd in the second (the earlier per-square rhythm).
				++$k;
				$groups[ 0 === $k % 5 ? 2 : ( 0 === $k % 3 ? 1 : 0 ) ] .= $rect;
			}
		}
		return '<svg class="avix-art-hero__mosaic" viewBox="0 0 544 80" width="544" height="80" aria-hidden="true" focusable="false"><g>' . implode( '</g><g>', $groups ) . '</g></svg>';
	}

	/* ------------------------------------------------------------------ */
	/* Author, dates, reading time                                        */
	/* ------------------------------------------------------------------ */

	/**
	 * The post author: name, role, link, photo, bio and profile links.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function author( \WP_Post $post ): array {
		$user = get_userdata( (int) $post->post_author );
		if ( ! $user instanceof \WP_User ) {
			return array();
		}
		$id      = (int) $user->ID;
		$entity  = class_exists( '\AvixWidgets\SEO\Entity' ) ? \AvixWidgets\SEO\Entity::get() : array();
		$founder = class_exists( '\AvixWidgets\SEO\Person' ) && method_exists( '\AvixWidgets\SEO\Person', 'is_founder_user' )
			? (bool) \AvixWidgets\SEO\Person::is_founder_user( $id )
			: 1 === $id;

		$name  = self::plain( $user->display_name );
		$first = self::plain( get_user_meta( $id, 'first_name', true ) );
		if ( '' === $first ) {
			$parts = explode( ' ', $name );
			$first = (string) $parts[0];
		}

		$role = '';
		if ( $founder ) {
			$title = isset( $entity['founder_job_title'] ) && '' !== $entity['founder_job_title'] ? $entity['founder_job_title'] : 'Founder & CEO';
			$role  = $title . ', ' . self::BRAND;
		}

		// Link: the user's website (Users > Profile), else the founder's About page.
		$url = esc_url_raw( trim( (string) $user->user_url ) );
		if ( '' !== $url && untrailingslashit( $url ) === untrailingslashit( home_url() ) ) {
			$url = '';
		}
		$about = '';
		if ( $founder && ! empty( $entity['founder_url'] ) ) {
			$about = self::absolute( (string) $entity['founder_url'] );
		}
		if ( '' === $url ) {
			$url = $about;
		}

		$links = array();
		if ( '' !== $url ) {
			$internal = self::is_internal( $url );
			$links[]  = array(
				'label'    => $internal
					/* translators: %s: first name. */
					? sprintf( $founder ? __( 'About %s and the team', 'avix-widgets' ) : __( 'More about %s', 'avix-widgets' ), $first )
					/* translators: %s: first name. */
					: sprintf( __( '%s’s website', 'avix-widgets' ), $first ),
				'url'      => $url,
				'external' => ! $internal,
				'icon'     => '',
			);
		}
		if ( $founder && ! empty( $entity['founder_same_as'] ) && is_array( $entity['founder_same_as'] ) ) {
			foreach ( $entity['founder_same_as'] as $profile ) {
				if ( false !== stripos( (string) $profile, 'linkedin.com/' ) ) {
					$links[] = array(
						'label'    => 'LinkedIn',
						'url'      => (string) $profile,
						'external' => true,
						'icon'     => 'linkedin',
					);
					break;
				}
			}
		}
		$links[] = array(
			'label'    => __( 'Case studies', 'avix-widgets' ),
			'url'      => self::case_studies_url(),
			'external' => false,
			'icon'     => '',
		);
		// No link twice.
		$seen  = array();
		$links = array_values(
			array_filter(
				$links,
				static function ( $link ) use ( &$seen ) {
					$key = untrailingslashit( strtolower( $link['url'] ) );
					if ( isset( $seen[ $key ] ) ) {
						return false;
					}
					$seen[ $key ] = true;
					return true;
				}
			)
		);

		$bio = trim( (string) get_the_author_meta( 'description', $id ) );
		$bio = '' !== $bio ? wp_kses(
			$bio,
			array(
				'a'      => array( 'href' => true ),
				'strong' => array(),
				'em'     => array(),
			)
		) : '';

		$image_id = 1 === $id ? self::FOUNDER_IMAGE_ID : 0;
		/**
		 * The author's photo (attachment ID; 0 = initials).
		 *
		 * @param int $image_id Media 8651 (the founder photo) for user 1, else 0.
		 * @param int $user_id
		 */
		$image_id = (int) apply_filters( 'avix_blog_author_image_id', $image_id, $id );
		if ( $image_id && ! wp_attachment_is_image( $image_id ) ) {
			$image_id = 0;
		}

		$author = array(
			'id'       => $id,
			'name'     => $name,
			'first'    => $first,
			'role'     => $role,
			'url'      => $url,
			'external' => '' !== $url && ! self::is_internal( $url ),
			'bio'      => $bio,
			'links'    => $links,
			'image_id' => $image_id,
			'initials' => self::initials( $name ),
		);
		/**
		 * The author data the hero and the author box print.
		 *
		 * @param array    $author
		 * @param \WP_User $user
		 * @param \WP_Post $post
		 */
		$author = apply_filters( 'avix_blog_author', $author, $user, $post );
		return is_array( $author ) ? $author : array();
	}

	/**
	 * The author photo as an <img> (or initials). $big: the author box, else
	 * the hero avatar. The square "avix-author" crop when the photo has one,
	 * else its medium size, which the CSS covers from the top (no per-photo
	 * offsets).
	 *
	 * @param array $author Output of author().
	 * @param bool  $big    Author box size.
	 */
	public static function face( array $author, bool $big ): string {
		$id = isset( $author['image_id'] ) ? (int) $author['image_id'] : 0;
		if ( $id ) {
			$size = image_get_intermediate_size( $id, self::AUTHOR_SIZE ) ? self::AUTHOR_SIZE : 'medium';
			$src  = wp_get_attachment_image_src( $id, $size );
			if ( $src ) {
				$srcset = (string) wp_get_attachment_image_srcset( $id, $size );
				return sprintf(
					'<img class="avix-art-face__img" src="%1$s"%2$s width="%3$d" height="%4$d" alt="%5$s" decoding="async"%6$s>',
					esc_url( $src[0] ),
					'' !== $srcset ? ' srcset="' . esc_attr( $srcset ) . '" sizes="' . esc_attr( $big ? '104px' : '46px' ) . '"' : '',
					(int) $src[1],
					(int) $src[2],
					esc_attr( $big ? $author['name'] : '' ),
					$big ? ' loading="lazy"' : ''
				);
			}
		}
		return '<span class="avix-art-face__mono" aria-hidden="true">' . esc_html( isset( $author['initials'] ) ? $author['initials'] : '' ) . '</span>';
	}

	private static function initials( string $name ): string {
		$out = '';
		foreach ( preg_split( '/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY ) as $word ) {
			$out .= function_exists( 'mb_substr' ) ? mb_strtoupper( mb_substr( $word, 0, 1 ) ) : strtoupper( substr( $word, 0, 1 ) );
			if ( strlen( $out ) >= 2 ) {
				break;
			}
		}
		return $out;
	}

	/**
	 * Published and modified dates; "updated" when modified a day or more after publishing.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function dates( \WP_Post $post ): array {
		$published = (int) get_post_time( 'U', true, $post );
		$modified  = (int) get_post_modified_time( 'U', true, $post );
		return array(
			'published' => array(
				'iso'   => (string) get_the_date( 'c', $post ),
				'short' => (string) get_the_date( 'j M Y', $post ),
				'long'  => (string) get_the_date( 'j F Y', $post ),
			),
			'modified'  => array(
				'iso'   => (string) get_the_modified_date( 'c', $post ),
				'short' => (string) get_the_modified_date( 'j M Y', $post ),
				'long'  => (string) get_the_modified_date( 'j F Y', $post ),
			),
			'updated'   => $modified - $published >= DAY_IN_SECONDS,
		);
	}

	/**
	 * Whole minutes, the same number the blog index shows.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function minutes( \WP_Post $post ): int {
		self::load_post_cards();
		if ( class_exists( '\AvixWidgets\Post_Cards' ) ) {
			return (int) \AvixWidgets\Post_Cards::minutes( $post );
		}
		$text  = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
		$words = count( preg_split( '/\s+/u', trim( $text ), -1, PREG_SPLIT_NO_EMPTY ) );
		return max( 1, (int) ceil( $words / self::WORDS_PER_MINUTE ) );
	}

	/* ------------------------------------------------------------------ */
	/* Service and calls to action                                        */
	/* ------------------------------------------------------------------ */

	/**
	 * The services the calls to action can point to.
	 */
	public static function services(): array {
		$services = array(
			'shopify'   => array(
				'match'       => array( 'shopify', 'shopify-plus', 'liquid', 'ecommerce', 'e-commerce' ),
				'eyebrow'     => __( 'Shopify development', 'avix-widgets' ),
				'title'       => __( 'Planning a Shopify store?', 'avix-widgets' ),
				'link'        => __( 'How we build Shopify stores', 'avix-widgets' ),
				'url'         => home_url( '/service/shopify-plus/' ),
				'band_lead'   => __( 'Planning a', 'avix-widgets' ),
				'band_accent' => __( 'Shopify store?', 'avix-widgets' ),
			),
			'wordpress' => array(
				'match'       => array( 'wordpress', 'woocommerce', 'elementor', 'bricks' ),
				'eyebrow'     => __( 'WordPress & WooCommerce', 'avix-widgets' ),
				'title'       => __( 'Planning a WordPress or WooCommerce build?', 'avix-widgets' ),
				'link'        => __( 'WordPress development', 'avix-widgets' ),
				'url'         => home_url( '/service/wordpress-development/' ),
				'band_lead'   => __( 'Planning a', 'avix-widgets' ),
				'band_accent' => __( 'WordPress or WooCommerce build?', 'avix-widgets' ),
			),
			'web-apps'  => array(
				'match'       => array( 'webflow', 'web-app', 'web-apps', 'web-application', 'portal', 'react', 'nextjs', 'next-js', 'saas', 'web-development' ),
				'eyebrow'     => __( 'Web apps & Webflow', 'avix-widgets' ),
				'title'       => __( 'Planning a web app or customer portal?', 'avix-widgets' ),
				'link'        => __( 'Custom web app development', 'avix-widgets' ),
				'url'         => home_url( '/service/web-development/' ),
				'band_lead'   => __( 'Planning a', 'avix-widgets' ),
				'band_accent' => __( 'web app or customer portal?', 'avix-widgets' ),
			),
			'uiux'      => array(
				'match'       => array( 'ui-ux', 'uiux', 'ux', 'design', 'brand', 'branding', 'redesign' ),
				'eyebrow'     => __( 'UI/UX & brand design', 'avix-widgets' ),
				'title'       => __( 'Planning a redesign?', 'avix-widgets' ),
				'link'        => __( 'UI/UX and brand design', 'avix-widgets' ),
				'url'         => home_url( '/service/uiux-and-brand-design/' ),
				'band_lead'   => __( 'Planning a', 'avix-widgets' ),
				'band_accent' => __( 'redesign?', 'avix-widgets' ),
			),
			'general'   => array(
				'match'       => array(),
				'eyebrow'     => __( 'Work with us', 'avix-widgets' ),
				'title'       => __( 'Planning a website or web app?', 'avix-widgets' ),
				'link'        => __( 'Our services', 'avix-widgets' ),
				'url'         => home_url( '/service/' ),
				'band_lead'   => __( 'Planning a website or', 'avix-widgets' ),
				'band_accent' => __( 'web application?', 'avix-widgets' ),
			),
		);
		/**
		 * The services the rail card and the CTA band point to: key => array(
		 * match (slug words), eyebrow, title, link, url, band_lead, band_accent ).
		 * Keep a "general" entry: it is the fallback.
		 *
		 * @param array $services
		 */
		$filtered = apply_filters( 'avix_blog_services', $services );
		if ( is_array( $filtered ) && isset( $filtered['general'] ) && is_array( $filtered['general'] ) ) {
			$services = $filtered;
		}
		return $services;
	}

	/**
	 * Pure helper (unit-testable): the service key for a set of slugs.
	 * Categories decide before tags; services are tried in their order. A
	 * match word must be a whole slug part: "ux" matches "ui-ux", not "linux".
	 *
	 * @param string[][] $slug_groups Lists of slugs, strongest first (categories, tags).
	 * @param array      $services    Output of services().
	 */
	public static function match_service( array $slug_groups, array $services ): string {
		foreach ( $slug_groups as $slugs ) {
			foreach ( $services as $key => $service ) {
				if ( empty( $service['match'] ) || ! is_array( $service['match'] ) ) {
					continue;
				}
				foreach ( (array) $slugs as $slug ) {
					foreach ( $service['match'] as $word ) {
						if ( '' !== (string) $word && preg_match( '/(^|-)' . preg_quote( (string) $word, '/' ) . '(-|$)/', (string) $slug ) ) {
							return (string) $key;
						}
					}
				}
			}
		}
		return 'general';
	}

	/**
	 * The service for this post: the meta _avix_article_service, else a
	 * category or tag slug match, else "general". Returns the service array
	 * with its key.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function service( \WP_Post $post ): array {
		$services = self::services();
		$key      = sanitize_key( (string) get_post_meta( $post->ID, Blog::META_SERVICE, true ) );
		if ( '' === $key || ! isset( $services[ $key ] ) ) {
			$cats = wp_list_pluck( (array) get_the_category( $post->ID ), 'slug' );
			$tags = get_the_tags( $post->ID );
			$tags = is_array( $tags ) ? wp_list_pluck( $tags, 'slug' ) : array();
			$key  = self::match_service( array( $cats, $tags ), $services );
		}
		/**
		 * The service key the calls to action use for this post.
		 *
		 * @param string   $key
		 * @param \WP_Post $post
		 */
		$key = (string) apply_filters( 'avix_blog_service_key', $key, $post );
		if ( ! isset( $services[ $key ] ) ) {
			$key = 'general';
		}
		return array_merge(
			array(
				'eyebrow'     => '',
				'title'       => '',
				'link'        => '',
				'url'         => '',
				'band_lead'   => '',
				'band_accent' => '',
			),
			$services[ $key ],
			array( 'key' => $key )
		);
	}

	public static function contact_url(): string {
		/**
		 * Where "Talk to the team" and "Share your project brief" lead.
		 *
		 * @param string $url
		 */
		return (string) apply_filters( 'avix_blog_contact_url', home_url( '/contact/' ) );
	}

	public static function case_studies_url(): string {
		if ( class_exists( '\AvixWidgets\Case_Studies\Case_Study' ) && method_exists( '\AvixWidgets\Case_Studies\Case_Study', 'index_url' ) ) {
			$url = (string) \AvixWidgets\Case_Studies\Case_Study::index_url();
			if ( '' !== $url ) {
				return $url;
			}
		}
		return home_url( '/case-studies/' );
	}

	/* ------------------------------------------------------------------ */
	/* Share                                                              */
	/* ------------------------------------------------------------------ */

	/**
	 * Plain share links: no third-party scripts, counters or tracking parameters.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function share( \WP_Post $post ): array {
		$url   = (string) get_permalink( $post );
		$title = self::plain( get_the_title( $post ) );
		return array(
			'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode( $url ),
			'x'        => 'https://x.com/intent/post?url=' . rawurlencode( $url ) . '&text=' . rawurlencode( $title ),
			'email'    => 'mailto:?subject=' . rawurlencode( $title ) . '&body=' . rawurlencode( $url ),
			'copy'     => $url,
		);
	}

	/* ------------------------------------------------------------------ */
	/* Related posts                                                      */
	/* ------------------------------------------------------------------ */

	/**
	 * Up to $count published posts: the same category first, then the most
	 * recent. IDs are cached per post until any post changes.
	 *
	 * @param \WP_Post      $post  Post.
	 * @param int           $count How many.
	 * @param \WP_Term|null $term  Primary category.
	 * @return int[]
	 */
	public static function related_ids( \WP_Post $post, int $count, $term = null ): array {
		if ( $count < 1 ) {
			return array();
		}
		$key = 'avix_blog_rel_' . md5( $post->ID . '|' . $count . '|' . ( $term ? $term->term_id : 0 ) . '|' . Blog::salt() );
		$ids = get_transient( $key );
		if ( ! is_array( $ids ) ) {
			$base = array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => $count,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'has_password'        => false,
				'fields'              => 'ids',
				'orderby'             => array(
					'date' => 'DESC',
					'ID'   => 'DESC',
				),
				'post__not_in'        => array( (int) $post->ID ), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- one ID.
			);
			$ids  = array();
			if ( $term ) {
				$query = new \WP_Query( array_merge( $base, array( 'cat' => (int) $term->term_id ) ) );
				$ids   = array_map( 'intval', $query->posts );
			}
			if ( count( $ids ) < $count ) {
				$query = new \WP_Query(
					array_merge(
						$base,
						array(
							'posts_per_page' => $count - count( $ids ),
							'post__not_in'   => array_merge( array( (int) $post->ID ), $ids ), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- at most 7 IDs.
						)
					)
				);
				$ids   = array_merge( $ids, array_map( 'intval', $query->posts ) );
			}
			set_transient( $key, $ids, DAY_IN_SECONDS );
		}
		/**
		 * The related post IDs, in order.
		 *
		 * @param int[]    $ids
		 * @param \WP_Post $post
		 * @param int      $count
		 */
		$ids = apply_filters( 'avix_blog_related_ids', $ids, $post, $count );
		$ids = array_values( array_unique( array_filter( array_map( 'intval', is_array( $ids ) ? $ids : array() ) ) ) );
		return array_slice( array_diff( $ids, array( (int) $post->ID ) ), 0, $count );
	}

	/**
	 * Related cards, printed with the blog index's own card markup
	 * (Post_Cards::card()), so they match it exactly.
	 *
	 * @param \WP_Post      $post  Post.
	 * @param int           $count How many.
	 * @param \WP_Term|null $term  Primary category.
	 */
	public static function related( \WP_Post $post, int $count, $term = null ): string {
		$ids = self::related_ids( $post, $count, $term );
		self::load_post_cards();
		if ( ! $ids || ! class_exists( '\AvixWidgets\Post_Cards' ) ) {
			return '';
		}
		$query = new \WP_Query(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'post__in'            => $ids,
				'orderby'             => 'post__in',
				'posts_per_page'      => count( $ids ),
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'has_password'        => false,
			)
		);
		if ( ! $query->posts ) {
			return '';
		}
		update_post_thumbnail_cache( $query );
		$args = \AvixWidgets\Post_Cards::args(
			array(
				'per_page' => count( $ids ),
				'tag'      => 'h3',
				'words'    => 22,
			)
		);
		// The article's own date style ("2 Oct 2026", British), as in the hero.
		$date = static function () {
			return 'j M Y';
		};
		add_filter( 'avix_post_grid_date_format', $date, 20 );
		$html = '';
		foreach ( $query->posts as $i => $related ) {
			$html .= \AvixWidgets\Post_Cards::card( $related, $args, $i );
		}
		remove_filter( 'avix_post_grid_date_format', $date, 20 );
		return $html;
	}

	/**
	 * Post_Cards lives in includes/ajax.php, which the bootstrap loads only
	 * when Elementor is active.
	 */
	private static function load_post_cards(): void {
		if ( class_exists( '\AvixWidgets\Post_Cards' ) ) {
			return;
		}
		$file = Blog::path( 'includes/ajax.php' );
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}

	/* ------------------------------------------------------------------ */
	/* URL helpers                                                        */
	/* ------------------------------------------------------------------ */

	private static function absolute( string $url ): string {
		$url = trim( $url );
		if ( '' === $url ) {
			return '';
		}
		if ( 0 === strpos( $url, '/' ) && 0 !== strpos( $url, '//' ) ) {
			return home_url( $url );
		}
		return esc_url_raw( $url );
	}

	private static function is_internal( string $url ): bool {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		$home = wp_parse_url( home_url(), PHP_URL_HOST );
		return ! $host || ( is_string( $home ) && strtolower( (string) $host ) === strtolower( $home ) );
	}
}
