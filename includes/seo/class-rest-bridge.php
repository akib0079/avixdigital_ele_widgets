<?php
/**
 * REST bridge: lets an editor (logged in, edit_post on the item) read and write
 * the fields a page edit needs through the WordPress REST API, so page copy,
 * Yoast fields, language links and header/footer layouts can be changed in one
 * request each instead of through several admin screens.
 *
 * - Yoast SEO post fields (title, description, focus keyphrase, breadcrumb title,
 *   Open Graph title/description/image, schema page type) on pages and posts.
 * - The plugin's language fields (_avix_lang, _avix_translation_of; see
 *   class-hreflang.php) on pages and posts.
 * - Elementor data and page settings on ThemeREX layouts (cpt_layouts: headers
 *   and footers), which Elementor only exposes for pages and posts.
 *
 * After a REST write that touches Elementor data or page settings, the page is
 * left in the state an Elementor save would leave it in:
 * - algenix_options (the Algenix theme's own copy of the Theme Options) is
 *   rebuilt from the algenix_options_override_/field_ keys in the page settings,
 *   the same way the theme does on an Elementor save. Without this a page created
 *   through REST renders with the site-wide defaults (boxed body, 150px top gap,
 *   light header) until someone saves it in Elementor.
 * - Elementor's generated CSS and element cache for the post are cleared so they
 *   are rebuilt from the new data on the next view, and the LiteSpeed page cache
 *   for the post is purged.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\SEO;

defined( 'ABSPATH' ) || exit;

final class Rest_Bridge {

	/** Yoast post meta keys exposed to REST (all single strings). */
	const YOAST_KEYS = array(
		'_yoast_wpseo_title',
		'_yoast_wpseo_metadesc',
		'_yoast_wpseo_focuskw',
		'_yoast_wpseo_bctitle',
		'_yoast_wpseo_opengraph-title',
		'_yoast_wpseo_opengraph-description',
		'_yoast_wpseo_opengraph-image',
		'_yoast_wpseo_opengraph-image-id',
		'_yoast_wpseo_twitter-title',
		'_yoast_wpseo_twitter-description',
		'_yoast_wpseo_schema_page_type',
		'_yoast_wpseo_meta-robots-noindex',
	);

	/** Post types that get the Yoast and language fields. */
	const CONTENT_TYPES = array( 'page', 'post' );

	/** ThemeREX layouts (headers, footers): Elementor fields only. */
	const LAYOUT_TYPE = 'cpt_layouts';

	/** Theme option prefixes in Elementor's page settings. */
	const OVERRIDE = 'algenix_options_override_';
	const FIELD    = 'algenix_options_field_';

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register' ), 99 );
		foreach ( array_merge( self::CONTENT_TYPES, array( self::LAYOUT_TYPE ) ) as $type ) {
			add_action( "rest_after_insert_{$type}", array( __CLASS__, 'after_rest_write' ), 10, 3 );
		}
	}

	/** Meta registration (init, late: after the theme and Yoast registered theirs). */
	public static function register(): void {
		$auth = static function ( $allowed, $meta_key, $object_id ) {
			return current_user_can( 'edit_post', (int) $object_id );
		};

		foreach ( self::CONTENT_TYPES as $type ) {
			foreach ( self::YOAST_KEYS as $key ) {
				register_post_meta(
					$type,
					$key,
					array(
						'type'              => 'string',
						'single'            => true,
						'show_in_rest'      => true,
						'auth_callback'     => $auth,
						'sanitize_callback' => array( __CLASS__, 'sanitize_text' ),
					)
				);
			}
			register_post_meta(
				$type,
				'_avix_lang',
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'auth_callback'     => $auth,
					'sanitize_callback' => array( __CLASS__, 'sanitize_lang' ),
				)
			);
			register_post_meta(
				$type,
				'_avix_translation_of',
				array(
					'type'              => 'integer',
					'single'            => true,
					'show_in_rest'      => true,
					'auth_callback'     => $auth,
					'sanitize_callback' => 'absint',
				)
			);
		}

		if ( post_type_exists( self::LAYOUT_TYPE ) ) {
			// The REST controller only adds the meta field for types that support custom fields.
			add_post_type_support( self::LAYOUT_TYPE, 'custom-fields' );
			register_post_meta(
				self::LAYOUT_TYPE,
				'_elementor_data',
				array(
					'type'          => 'string',
					'single'        => true,
					'show_in_rest'  => true,
					'auth_callback' => $auth,
				)
			);
		}
	}

	/**
	 * Plain text, one line (titles, descriptions), keeping Yoast's %%variables%%.
	 *
	 * @param mixed $value Value.
	 */
	public static function sanitize_text( $value ): string {
		return sanitize_text_field( (string) $value );
	}

	/**
	 * A language code such as "nl" or "en-gb", lower case; anything else is ''.
	 *
	 * @param mixed $value Value.
	 */
	public static function sanitize_lang( $value ): string {
		$value = strtolower( trim( (string) $value ) );
		return preg_match( '/^[a-z]{2}(?:-[a-z]{2})?$/', $value ) ? $value : '';
	}

	/**
	 * rest_after_insert_{type}: when the request wrote Elementor data or page
	 * settings, rebuild the theme options and clear the Elementor caches.
	 *
	 * @param \WP_Post         $post    Post.
	 * @param \WP_REST_Request $request Request.
	 * @param bool             $creating Whether the post was created.
	 */
	public static function after_rest_write( $post, $request, $creating ): void {
		if ( ! $post instanceof \WP_Post ) {
			return;
		}
		$meta = $request->get_param( 'meta' );
		if ( ! is_array( $meta ) ) {
			return;
		}
		$post_id = (int) $post->ID;

		// Yoast's head reads its indexable, built on save_post: before the REST
		// controller wrote the meta. Rebuild it from the new values.
		if ( array_intersect( array_keys( $meta ), array_merge( self::YOAST_KEYS, array( '_avix_lang', '_avix_translation_of' ) ) ) ) {
			self::rebuild_yoast( $post_id );
		}

		$touched = array_key_exists( '_elementor_data', $meta ) || array_key_exists( '_elementor_page_settings', $meta );
		if ( ! $touched ) {
			return;
		}

		if ( array_key_exists( '_elementor_page_settings', $meta ) ) {
			$settings = get_post_meta( $post_id, '_elementor_page_settings', true );
			if ( is_array( $settings ) ) {
				$current = get_post_meta( $post_id, 'algenix_options', true );
				$next    = self::theme_options( is_array( $current ) ? $current : array(), $settings );
				if ( $next !== $current ) {
					update_post_meta( $post_id, 'algenix_options', wp_slash( $next ) );
				}
			}
		}

		if ( array_key_exists( '_elementor_data', $meta ) ) {
			if ( '' === (string) get_post_meta( $post_id, '_elementor_edit_mode', true ) ) {
				update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
			}
			if ( '' === (string) get_post_meta( $post_id, '_elementor_version', true ) && defined( 'ELEMENTOR_VERSION' ) ) {
				update_post_meta( $post_id, '_elementor_version', ELEMENTOR_VERSION );
			}
		}

		self::clear_caches( $post_id );
	}

	/**
	 * The theme options a page's Elementor settings stand for: every key whose
	 * override switch is on takes the picked value; a key whose switch is off
	 * (inherit) is removed, as the theme does on an Elementor save; keys the
	 * settings do not mention stay as they are.
	 *
	 * @param array $current  Current algenix_options.
	 * @param array $settings Elementor page settings.
	 */
	public static function theme_options( array $current, array $settings ): array {
		foreach ( $settings as $name => $on ) {
			$name = (string) $name;
			if ( 0 !== strpos( $name, self::OVERRIDE ) ) {
				continue;
			}
			$key = substr( $name, strlen( self::OVERRIDE ) );
			if ( '' === $key ) {
				continue;
			}
			$field = self::FIELD . $key;
			if ( ! empty( $on ) && '0' !== (string) $on && array_key_exists( $field, $settings ) && is_scalar( $settings[ $field ] ) && '' !== (string) $settings[ $field ] ) {
				$current[ $key ] = (string) $settings[ $field ];
			} elseif ( empty( $on ) || '0' === (string) $on ) {
				unset( $current[ $key ] );
			}
		}
		return $current;
	}

	/**
	 * Yoast's indexable for a post, rebuilt from its current meta (as the SEO
	 * importer does). Without Yoast there is nothing to rebuild.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function rebuild_yoast( int $post_id ): void {
		if ( ! defined( 'WPSEO_VERSION' ) || ! function_exists( 'YoastSEO' ) ) {
			return;
		}
		clean_post_cache( $post_id );
		try {
			$watcher = YoastSEO()->classes->get( 'Yoast\WP\SEO\Integrations\Watchers\Indexable_Post_Watcher' );
			if ( is_object( $watcher ) && method_exists( $watcher, 'build_indexable' ) ) {
				$watcher->build_indexable( $post_id );
			}
		} catch ( \Throwable $error ) {
			return;
		}
	}

	/**
	 * Elementor's generated CSS, element cache and page assets for a post, and its
	 * LiteSpeed page cache.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function clear_caches( int $post_id ): void {
		if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
			try {
				\Elementor\Core\Files\CSS\Post::create( $post_id )->delete();
			} catch ( \Throwable $e ) {
				delete_post_meta( $post_id, '_elementor_css' );
			}
		} else {
			delete_post_meta( $post_id, '_elementor_css' );
		}
		delete_post_meta( $post_id, '_elementor_element_cache' );
		delete_post_meta( $post_id, '_elementor_page_assets' );
		clean_post_cache( $post_id );
		do_action( 'litespeed_purge_post', $post_id );
	}
}
