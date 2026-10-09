<?php
/**
 * Front-end weight the site does not need.
 *
 * The Algenix theme enqueues the WordPress audio/video player
 * (wp-mediaelement: MediaElement.js and its CSS, about 36 KB of script)
 * on every page in case a player turns up. A page that shows no audio or
 * video does not need it, so it is dequeued there. Anything that does render
 * a player later in the request (the [audio]/[video] shortcodes, the media
 * widgets, an embedded media file) enqueues it again itself, so those pages
 * keep it.
 *
 * Filter: avix_perf_dequeue_mediaelement (bool $dequeue, int $post_id).
 *
 * @package AvixWidgets
 */

namespace AvixWidgets;

defined( 'ABSPATH' ) || exit;

final class Perf {

	/** Script and style handles of the WordPress media player. */
	const MEDIA_SCRIPTS = array( 'wp-mediaelement', 'mediaelement', 'mediaelement-core', 'mediaelement-migrate', 'mediaelement-vimeo', 'wp-playlist' );
	const MEDIA_STYLES  = array( 'wp-mediaelement', 'mediaelement' );

	/**
	 * Audio or video in post content or Elementor data: blocks, shortcodes,
	 * player widgets, or a media file URL.
	 */
	const MEDIA_PATTERN = '~<!--\s*wp:(?:audio|video|playlist)\b|\[(?:audio|video|playlist|embed)\b|"widgetType"\s*:\s*"[^"]*(?:audio|video|playlist|media|player)[^"]*"|\.(?:mp3|m4a|ogg|oga|wav|mp4|m4v|webm|ogv|mov|flv|wmv)(?:[?#"\'\s\\\\]|$)~i';

	public static function init(): void {
		// After the theme (Algenix enqueues its scripts at priority 1000).
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'dequeue_mediaelement' ), 9999 );
	}

	public static function dequeue_mediaelement(): void {
		if ( is_admin() || wp_doing_ajax() || is_customize_preview() || self::is_elementor_editor() ) {
			return;
		}
		// Only views whose whole content can be checked: a page, a post, a case study.
		if ( ! is_singular() ) {
			return;
		}
		$post_id = (int) get_queried_object_id();
		if ( ! $post_id ) {
			return;
		}
		/**
		 * Whether the WordPress media player is dequeued on this request.
		 *
		 * @param bool $dequeue True when the post shows no audio or video.
		 * @param int  $post_id The queried post.
		 */
		if ( ! apply_filters( 'avix_perf_dequeue_mediaelement', ! self::has_media( $post_id ), $post_id ) ) {
			return;
		}
		foreach ( self::MEDIA_SCRIPTS as $handle ) {
			wp_dequeue_script( $handle );
		}
		foreach ( self::MEDIA_STYLES as $handle ) {
			wp_dequeue_style( $handle );
		}
	}

	/**
	 * True when the post's content or its Elementor data holds audio or video.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function has_media( int $post_id ): bool {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return true;
		}
		return self::mentions_media( (string) $post->post_content ) || self::mentions_media( (string) get_post_meta( $post_id, '_elementor_data', true ) );
	}

	/**
	 * True when a post's content or Elementor JSON holds an audio/video block,
	 * shortcode, player widget or media file URL.
	 *
	 * @param string $source Post content or the _elementor_data JSON.
	 */
	public static function mentions_media( string $source ): bool {
		return '' !== $source && 1 === preg_match( self::MEDIA_PATTERN, $source );
	}

	/** True in the Elementor editor and its preview iframe. */
	private static function is_elementor_editor(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only context check.
		if ( isset( $_GET['elementor-preview'] ) || isset( $_GET['elementor_library'] ) ) {
			return true;
		}
		if ( ! did_action( 'elementor/loaded' ) || ! class_exists( '\Elementor\Plugin' ) || ! isset( \Elementor\Plugin::$instance ) ) {
			return false;
		}
		$elementor = \Elementor\Plugin::$instance;
		if ( isset( $elementor->preview ) && is_object( $elementor->preview ) && method_exists( $elementor->preview, 'is_preview_mode' ) && $elementor->preview->is_preview_mode() ) {
			return true;
		}
		return false;
	}
}
