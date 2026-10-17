<?php
/**
 * Blog breadcrumb (SEO plan A7): Yoast's crumbs for a single post become Home › Blog › Post,
 * which fixes both the visible breadcrumb and the BreadcrumbList in Yoast's graph. A sibling of
 * Case_Studies\SEO::insert_index_crumb().
 *
 * The blog page is WordPress's posts page when one is set, else the published page with the
 * slug "blog" (avixdigital.com: page 6252); filter avix_seo_blog_page_id to choose another.
 * Only a Yoast filter is hooked, so this file is safe without Yoast.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\SEO;

defined( 'ABSPATH' ) || exit;

final class Breadcrumbs {

	public static function init(): void {
		add_filter( 'wpseo_breadcrumb_links', array( __CLASS__, 'filter' ), 20 );
	}

	/**
	 * Inserts the blog crumb after Home on single posts.
	 *
	 * @param mixed $links Yoast crumbs: array of array( 'url', 'text' ) (or array( 'id' )).
	 * @return mixed
	 */
	public static function filter( $links ) {
		if ( ! is_array( $links ) || ! is_singular( 'post' ) ) {
			return $links;
		}
		$blog = self::blog_page_id();
		if ( ! $blog || (int) get_queried_object_id() === $blog ) {
			return $links;
		}
		$url  = (string) get_permalink( $blog );
		$text = self::crumb_title( $blog );
		if ( '' === $url || '' === $text ) {
			return $links;
		}
		return self::insert_crumb( $links, $url, $text, $blog );
	}

	/**
	 * The blog page ID (0 when there is none or it is not published).
	 */
	public static function blog_page_id(): int {
		$id = (int) get_option( 'page_for_posts' );
		if ( ! $id ) {
			$page = get_page_by_path( 'blog', OBJECT, 'page' );
			$id   = $page ? (int) $page->ID : 0;
		}
		/**
		 * Filters the page shown as "Blog" between Home and a single post in the breadcrumbs.
		 *
		 * @param int $id Page ID (0 = no blog crumb).
		 */
		$id = (int) apply_filters( 'avix_seo_blog_page_id', $id );
		return ( $id > 0 && 'publish' === get_post_status( $id ) ) ? $id : 0;
	}

	/**
	 * The crumb text: Yoast's "Breadcrumbs title" for the page when set, else its title, as plain
	 * text (no HTML entities such as &#038;).
	 *
	 * @param int $id Page ID.
	 */
	public static function crumb_title( int $id ): string {
		$title = trim( (string) get_post_meta( $id, '_yoast_wpseo_bctitle', true ) );
		if ( '' === $title ) {
			$title = (string) get_the_title( $id );
		}
		return trim( (string) preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( $title ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
	}

	/**
	 * Pure helper (unit-testable): adds the crumb after the first one (Home) unless a crumb with
	 * the same URL or page ID is already there.
	 *
	 * @param array  $links Crumbs.
	 * @param string $url   Crumb URL.
	 * @param string $text  Crumb text.
	 * @param int    $id    Page ID (0 = compare by URL only).
	 */
	public static function insert_crumb( array $links, string $url, string $text, int $id = 0 ): array {
		$norm = untrailingslashit( $url );
		foreach ( $links as $link ) {
			if ( ! is_array( $link ) ) {
				continue;
			}
			if ( ( isset( $link['url'] ) && untrailingslashit( (string) $link['url'] ) === $norm ) || ( $id && isset( $link['id'] ) && (int) $link['id'] === $id ) ) {
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
}
