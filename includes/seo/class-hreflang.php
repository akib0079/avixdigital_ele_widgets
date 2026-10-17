<?php
/**
 * Language versions of a page: hreflang links, the page's lang attribute, the
 * Open Graph locale and the schema inLanguage.
 *
 * A translation is an ordinary page (for Dutch under /nl/) with two fields,
 * writable in REST through class-rest-bridge.php:
 * - _avix_lang: its language code ("nl"); empty means the site language.
 * - _avix_translation_of: the ID of the English page it translates.
 *
 * The English page and its published translations form one group. Every member
 * prints a link to every member (reciprocal, itself included) plus x-default,
 * the English page. A page without translations prints nothing.
 *
 * Filter: avix_hreflang_group (array $group lang => URL, int $post_id).
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\SEO;

defined( 'ABSPATH' ) || exit;

final class Hreflang {

	const LANG_KEY = '_avix_lang';
	const OF_KEY   = '_avix_translation_of';

	/** Language code => [ html lang, Open Graph locale ]. */
	const LOCALES = array(
		'nl' => array( 'nl-NL', 'nl_NL' ),
		'de' => array( 'de-DE', 'de_DE' ),
		'en' => array( 'en-GB', 'en_GB' ),
	);

	/** Per-request cache: post ID => group. */
	private static $groups = array();

	public static function init(): void {
		add_action( 'wp_head', array( __CLASS__, 'print_links' ), 2 );
		add_filter( 'language_attributes', array( __CLASS__, 'language_attributes' ), 20 );
		add_filter( 'wpseo_locale', array( __CLASS__, 'og_locale' ), 20 );
		add_filter( 'wpseo_schema_webpage', array( __CLASS__, 'schema_language' ), 20 );
		add_filter( 'wpseo_schema_article', array( __CLASS__, 'schema_language' ), 20 );
	}

	/** The site's own language code ("en" for en-GB). */
	public static function site_lang(): string {
		return strtolower( substr( (string) get_bloginfo( 'language' ), 0, 2 ) ) ?: 'en';
	}

	/**
	 * A post's language code: its _avix_lang, or the site language.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function lang_of( int $post_id ): string {
		$lang = $post_id ? strtolower( trim( (string) get_post_meta( $post_id, self::LANG_KEY, true ) ) ) : '';
		return '' !== $lang ? $lang : self::site_lang();
	}

	/** The queried singular post that has its own language, or 0. */
	private static function current_id(): int {
		if ( is_admin() || ! is_singular() ) {
			return 0;
		}
		return (int) get_queried_object_id();
	}

	/**
	 * The language group of a post: lang => permalink, the original first. Empty
	 * when the post has no published translation.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string,string>
	 */
	public static function group( int $post_id ): array {
		if ( isset( self::$groups[ $post_id ] ) ) {
			return self::$groups[ $post_id ];
		}
		$root = (int) get_post_meta( $post_id, self::OF_KEY, true );
		if ( $root <= 0 || 'publish' !== get_post_status( $root ) ) {
			$root = $post_id;
		}
		$members = get_posts(
			array(
				'post_type'        => array( 'page', 'post' ),
				'post_status'      => 'publish',
				'posts_per_page'   => 20,
				'fields'           => 'ids',
				'meta_key'         => self::OF_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'       => $root, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'no_found_rows'    => true,
				'suppress_filters' => true,
			)
		);
		$group = array();
		if ( $members ) {
			$group[ self::lang_of( $root ) ] = self::url( $root );
			foreach ( $members as $id ) {
				$lang = self::lang_of( (int) $id );
				if ( ! isset( $group[ $lang ] ) ) {
					$group[ $lang ] = self::url( (int) $id );
				}
			}
		}
		/**
		 * The hreflang group printed on a post: lang => URL, the original first.
		 *
		 * @param array $group   Group.
		 * @param int   $post_id Post being viewed.
		 */
		$group = (array) apply_filters( 'avix_hreflang_group', $group, $post_id );
		self::$groups[ $post_id ] = count( $group ) > 1 ? $group : array();
		return self::$groups[ $post_id ];
	}

	/**
	 * A post's public URL; the front page as the home URL.
	 *
	 * @param int $post_id Post ID.
	 */
	private static function url( int $post_id ): string {
		if ( (int) get_option( 'page_on_front' ) === $post_id ) {
			return home_url( '/' );
		}
		return (string) get_permalink( $post_id );
	}

	/** wp_head: one alternate link per language plus x-default. */
	public static function print_links(): void {
		$post_id = self::current_id();
		if ( ! $post_id ) {
			return;
		}
		$group = self::group( $post_id );
		if ( ! $group ) {
			return;
		}
		foreach ( $group as $lang => $url ) {
			printf( '<link rel="alternate" hreflang="%s" href="%s" />' . "\n", esc_attr( $lang ), esc_url( $url ) );
		}
		printf( '<link rel="alternate" hreflang="x-default" href="%s" />' . "\n", esc_url( reset( $group ) ) );
	}

	/** The lang attribute on <html> for a translated page. */
	public static function language_attributes( $output ) {
		$post_id = self::current_id();
		if ( ! $post_id ) {
			return $output;
		}
		$lang = self::lang_of( $post_id );
		if ( $lang === self::site_lang() || ! isset( self::LOCALES[ $lang ] ) ) {
			return $output;
		}
		$html = self::LOCALES[ $lang ][0];
		if ( preg_match( '/lang="[^"]*"/', (string) $output ) ) {
			return preg_replace( '/lang="[^"]*"/', 'lang="' . esc_attr( $html ) . '"', (string) $output );
		}
		return trim( $output . ' lang="' . esc_attr( $html ) . '"' );
	}

	/** Yoast's og:locale for a translated page. */
	public static function og_locale( $locale ) {
		$post_id = self::current_id();
		if ( ! $post_id ) {
			return $locale;
		}
		$lang = self::lang_of( $post_id );
		return ( $lang !== self::site_lang() && isset( self::LOCALES[ $lang ] ) ) ? self::LOCALES[ $lang ][1] : $locale;
	}

	/**
	 * Yoast schema WebPage / Article inLanguage for a translated page.
	 *
	 * @param mixed $piece Schema piece.
	 */
	public static function schema_language( $piece ) {
		$post_id = self::current_id();
		if ( ! $post_id || ! is_array( $piece ) ) {
			return $piece;
		}
		$lang = self::lang_of( $post_id );
		if ( $lang !== self::site_lang() && isset( self::LOCALES[ $lang ] ) ) {
			$piece['inLanguage'] = self::LOCALES[ $lang ][0];
		}
		return $piece;
	}
}
