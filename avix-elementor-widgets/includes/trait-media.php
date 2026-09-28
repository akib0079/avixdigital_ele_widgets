<?php
/**
 * Media helpers shared by the widgets.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Widgets;

defined( 'ABSPATH' ) || exit;

trait Media {

	/**
	 * Media-library ID for an image given only by URL (e.g. the defaults), so it
	 * still gets responsive srcset sizes. Also matches resized names like
	 * "photo-2048x1143.webp". Cached per request.
	 */
	private function attachment_id( $url ) {
		static $cache = array();
		$url = (string) $url;
		if ( '' === $url || ! function_exists( 'attachment_url_to_postid' ) ) {
			return 0;
		}
		if ( ! isset( $cache[ $url ] ) ) {
			$id = attachment_url_to_postid( $url );
			if ( ! $id ) {
				$full = preg_replace( '/-\d+x\d+(\.[a-z0-9]+)$/i', '$1', $url );
				$id   = $full !== $url ? attachment_url_to_postid( $full ) : 0;
			}
			$cache[ $url ] = (int) $id;
		}
		return $cache[ $url ];
	}

	/**
	 * Media-control value → attachment ID, resolving URL-only values.
	 */
	private function media_id( $media ) {
		$media = (array) $media;
		$id    = absint( $media['id'] ?? 0 );
		return $id ? $id : $this->attachment_id( $media['url'] ?? '' );
	}
}
