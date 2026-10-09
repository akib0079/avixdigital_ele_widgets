<?php
/**
 * 404-only redirects (plan task A8): old URLs that Google and visitors still
 * request are sent to the page that replaced them with one 301, and a removed
 * page answers 410 Gone. Only a request that WordPress has already resolved to
 * a 404 is touched, so an existing page, post or file is never redirected.
 *
 * The map is filterable (avix_seo_redirects): path => target path on this site
 * (301), or the integer 410 (Gone). The query string is kept on a redirect.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\SEO;

defined( 'ABSPATH' ) || exit;

final class Redirects {

	/** Old path => new path (301), or 410. Paths are relative to the site root. */
	const MAP = array(
		'/services/'                               => '/service/',
		'/about-us/'                               => '/about-avixdigital/',
		'/about-us-1/'                             => '/about-avixdigital/',
		'/contact-us-2/'                           => '/contact/',
		'/avix-digital-client-portal-seamless-project-management/' => '/why-your-digital-agency-needs-a-custom-client-portal-and-how-we-built-ours/',
		'/how-to-choose-the-right-digital-agency/' => '/web-development-agency-vs-freelancer/',
		'/ai-features/'                            => 410,
	);

	public static function init(): void {
		// Before redirect_canonical() (priority 10), which would otherwise guess a
		// different target for some of these 404s and add a hop.
		add_action( 'template_redirect', array( __CLASS__, 'maybe_redirect' ), 0 );
	}

	/**
	 * The map after the avix_seo_redirects filter, with normalised keys.
	 *
	 * @return array path => string target | int 410
	 */
	public static function map(): array {
		$map = function_exists( 'apply_filters' ) ? apply_filters( 'avix_seo_redirects', self::MAP ) : self::MAP;
		return self::clean_map( is_array( $map ) ? $map : array() );
	}

	/**
	 * Normalised map: keys and targets as "/path/", entries that are neither a
	 * root-relative path nor 410 dropped, and no entry pointing at itself.
	 *
	 * @param array $map Raw map.
	 */
	public static function clean_map( array $map ): array {
		$out = array();
		foreach ( $map as $from => $to ) {
			$from = self::normalize( (string) $from );
			if ( '/' === $from ) {
				continue; // Never the home page.
			}
			if ( 410 === $to || '410' === $to ) {
				$out[ $from ] = 410;
				continue;
			}
			if ( ! is_string( $to ) || '' === trim( $to ) || '/' !== substr( trim( $to ), 0, 1 ) || '//' === substr( trim( $to ), 0, 2 ) ) {
				continue; // Only paths on this site.
			}
			$target = self::normalize( $to );
			if ( $target !== $from ) {
				$out[ $from ] = $target;
			}
		}
		return $out;
	}

	/**
	 * What a request URI should get, without WordPress: array( 'code' => 301,
	 * 'path' => '/service/', 'query' => 'utm=x', 'location' => '/service/?utm=x' )
	 * (path relative to the site root, location from the server root), or
	 * array( 'code' => 410 ), or array() for no match.
	 *
	 * @param string $request_uri $_SERVER['REQUEST_URI'] (path and query).
	 * @param array  $map         clean_map() result.
	 * @param string $home_path   Path of home_url( '/' ), e.g. "/" or "/sub/".
	 */
	public static function match( string $request_uri, array $map, string $home_path = '/' ): array {
		$query = '';
		$at    = strpos( $request_uri, '?' );
		if ( false !== $at ) {
			$query       = (string) substr( $request_uri, $at + 1 );
			$request_uri = substr( $request_uri, 0, $at );
		}
		$path = $request_uri;
		$base = rtrim( $home_path, '/' );
		if ( '' !== $base ) {
			if ( 0 !== stripos( $path, $base . '/' ) && 0 !== strcasecmp( rtrim( $path, '/' ), $base ) ) {
				return array();
			}
			$path = (string) substr( $path, strlen( $base ) );
		}
		$path = self::normalize( $path );
		if ( ! isset( $map[ $path ] ) ) {
			return array();
		}
		if ( 410 === $map[ $path ] ) {
			return array( 'code' => 410 );
		}
		return array(
			'code'     => 301,
			'path'     => $map[ $path ],
			'query'    => $query,
			'location' => $base . $map[ $path ] . ( '' !== $query ? '?' . $query : '' ),
		);
	}

	/**
	 * "/Services" → "/services/". Drops the query and fragment and repeated slashes.
	 *
	 * @param string $path Path.
	 */
	public static function normalize( string $path ): string {
		foreach ( array( '#', '?' ) as $cut ) {
			$at = strpos( $path, $cut );
			if ( false !== $at ) {
				$path = substr( $path, 0, $at );
			}
		}
		$path = strtolower( rawurldecode( trim( $path ) ) );
		$path = (string) preg_replace( '#/+#', '/', '/' . trim( $path, '/' ) . '/' );
		return $path;
	}

	/**
	 * template_redirect: only for a front-end GET or HEAD that is a 404.
	 */
	public static function maybe_redirect(): void {
		if ( ! is_404() || is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_key( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';
		if ( ! in_array( $method, array( 'GET', 'HEAD' ), true ) ) {
			return;
		}
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- only compared against the map; the redirect target comes from the map, the query is escaped by wp_safe_redirect().
		if ( '' === $uri ) {
			return;
		}
		$home = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$hit  = self::match( $uri, self::map(), '' !== $home ? $home : '/' );
		if ( ! $hit ) {
			return;
		}
		if ( 410 === $hit['code'] ) {
			// Gone: the theme's 404 template still renders, with a 410 status.
			status_header( 410 );
			return;
		}
		$location = home_url( $hit['path'] ) . ( '' !== $hit['query'] ? '?' . $hit['query'] : '' );
		wp_safe_redirect( $location, 301, 'AvixDigital' );
		exit;
	}
}
