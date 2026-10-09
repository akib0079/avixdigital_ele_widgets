<?php
/**
 * Starter layout for case studies: a new case study opens in Elementor with the
 * full page already composed (hero, three chapters, three feature spotlights,
 * results, gallery, stack and the CTA + next card), every widget reading its
 * content from the post's own meta (SPEC-CASE-STUDIES §5).
 *
 * The layout comes from the Elementor library template chosen in Settings
 * (option avix_cs_starter_template), else the bundled starter-layout.json.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Case_Studies;

defined( 'ABSPATH' ) || exit;

final class Starter {

	const POST_TYPE       = 'avix_case_study';
	const OPTION_TEMPLATE = 'avix_cs_starter_template';
	const BUNDLED_FILE    = 'starter-layout.json';

	/** Elementor caches that must go whenever _elementor_data is rewritten. */
	const CACHE_KEYS = array( '_elementor_css', '_elementor_element_cache', '_elementor_page_assets' );

	private static $booted = false;

	/** Element ids handed out in this request, so two generated ids can never collide. */
	private static $issued = array();

	/** Monotonic counter mixed into every id. */
	private static $counter = 0;

	/* ------------------------------------------------------------------ */
	/* Hooks                                                              */
	/* ------------------------------------------------------------------ */

	/**
	 * Hooks copy-on-create. Safe to call more than once.
	 */
	public static function init(): void {
		if ( self::$booted ) {
			return;
		}
		self::$booted = true;
		add_action( 'wp_insert_post', array( __CLASS__, 'on_insert' ), 10, 3 );
	}

	/**
	 * post-new.php creates an auto-draft first: that is the moment to copy the starter in,
	 * so "Edit with Elementor" opens a composed page rather than an empty canvas.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @param bool     $update  Whether this is an update.
	 */
	public static function on_insert( $post_id, $post, $update ): void {
		if ( $update || ! $post instanceof \WP_Post ) {
			return;
		}
		if ( self::POST_TYPE !== $post->post_type || 'auto-draft' !== $post->post_status ) {
			return;
		}
		if ( self::has_layout( (int) $post_id ) ) {
			return;
		}
		self::apply( (int) $post_id, false );
	}

	/* ------------------------------------------------------------------ */
	/* Public API                                                         */
	/* ------------------------------------------------------------------ */

	/**
	 * Writes the starter layout (with fresh element ids) and the theme options to a case study.
	 *
	 * Without $reset nothing happens when the post already has Elementor content
	 * ("Apply starter layout"). With $reset the current content is first kept in a
	 * revision, then replaced ("Reset to starter layout").
	 *
	 * @param int  $post_id Case study ID.
	 * @param bool $reset   Replace existing Elementor content.
	 * @return bool True when the layout was written.
	 */
	public static function apply( int $post_id, bool $reset = false ): bool {
		$post = get_post( $post_id );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return false;
		}
		if ( self::has_layout( $post_id ) ) {
			if ( ! $reset ) {
				return false;
			}
			self::save_revision( $post_id );
		}

		$layout = self::layout();
		if ( ! $layout ) {
			return false;
		}
		self::write( $post_id, $layout );
		self::write_theme_options( $post_id );
		return true;
	}

	/**
	 * The starter elements with fresh, unique 8-hex ids.
	 *
	 * @param bool $bundled_only Ignore the Settings template (the importer composes from the bundled file).
	 */
	public static function layout( bool $bundled_only = false ): array {
		$data = $bundled_only ? array() : self::template_data();
		if ( ! $data ) {
			$data = self::bundled();
		}
		return $data ? self::fresh_ids( $data ) : array();
	}

	/**
	 * The bundled starter-layout.json, decoded (ids still empty).
	 */
	public static function bundled(): array {
		static $cache = null;
		if ( null !== $cache ) {
			return $cache;
		}
		$file  = __DIR__ . '/' . self::BUNDLED_FILE;
		$json  = is_readable( $file ) ? file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local plugin file.
		$data  = $json ? json_decode( $json, true ) : null;
		$cache = is_array( $data ) ? array_values( $data ) : array();
		return $cache;
	}

	/**
	 * True when the post already has Elementor content.
	 */
	public static function has_layout( int $post_id ): bool {
		$raw = get_post_meta( $post_id, '_elementor_data', true );
		if ( is_array( $raw ) ) {
			return ! empty( $raw );
		}
		$raw = is_string( $raw ) ? trim( $raw ) : '';
		if ( '' === $raw || '[]' === $raw ) {
			return false;
		}
		$data = json_decode( $raw, true );
		return is_array( $data ) ? ! empty( $data ) : true;
	}

	/**
	 * Gives every element in the tree a new, unique, non-empty 8-hex id
	 * (two empty or duplicate ids crash the Elementor editor). Repeater rows
	 * get a fresh 7-hex _id the same way.
	 *
	 * @param array $elements Elementor elements.
	 */
	public static function fresh_ids( array $elements ): array {
		$out = array();
		foreach ( $elements as $el ) {
			if ( ! is_array( $el ) || empty( $el['elType'] ) ) {
				continue;
			}
			$el['id'] = self::new_id( 8 );
			if ( isset( $el['settings'] ) && is_array( $el['settings'] ) ) {
				foreach ( $el['settings'] as $key => $value ) {
					if ( is_array( $value ) && self::is_repeater( $value ) ) {
						foreach ( $value as $i => $row ) {
							$el['settings'][ $key ][ $i ]['_id'] = self::new_id( 7 );
						}
					}
				}
			}
			$el['elements'] = isset( $el['elements'] ) && is_array( $el['elements'] ) ? self::fresh_ids( $el['elements'] ) : array();
			$out[]          = $el;
		}
		return $out;
	}

	/**
	 * A new lowercase hex id that was not issued before in this request.
	 *
	 * @param int $length 8 for elements, 7 for repeater rows (Elementor's own lengths).
	 */
	public static function new_id( int $length = 8 ): string {
		do {
			$id = substr( md5( uniqid( '', true ) . ( ++self::$counter ) . wp_rand() ), 0, $length );
		} while ( isset( self::$issued[ $id ] ) || preg_match( '/^[0-9]+$/', $id ) );
		self::$issued[ $id ] = true;
		return $id;
	}

	/**
	 * Writes Elementor data and the builder flags to a post, then drops Elementor's caches.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $data    Elementor elements (ids already set).
	 */
	public static function write( int $post_id, array $data ): void {
		$json = wp_json_encode( self::objectify( $data ) );
		if ( ! $json ) {
			return;
		}
		update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $post_id, '_elementor_template_type', 'wp-post' );
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			update_post_meta( $post_id, '_elementor_version', ELEMENTOR_VERSION );
		}
		// Before the layout: writing it lets Case_Studies copy the theme options into these settings.
		if ( ! metadata_exists( 'post', $post_id, '_elementor_page_settings' ) ) {
			update_post_meta( $post_id, '_elementor_page_settings', array() );
		}
		update_post_meta( $post_id, '_elementor_data', wp_slash( $json ) );
		// New posts get the theme's default template (§2.11 routes it to page.php); a template the
		// owner picked later survives a reset or a re-import.
		if ( '' === (string) get_post_meta( $post_id, '_wp_page_template', true ) ) {
			update_post_meta( $post_id, '_wp_page_template', 'default' );
		}
		self::clear_cache( $post_id );
	}

	/**
	 * Drops Elementor's CSS, element and asset caches for a post.
	 */
	public static function clear_cache( int $post_id ): void {
		foreach ( self::CACHE_KEYS as $key ) {
			delete_post_meta( $post_id, $key );
		}
		if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
			try {
				\Elementor\Core\Files\CSS\Post::create( $post_id )->delete();
			} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- the meta is already gone, Elementor rebuilds the file.
			}
		}
		if ( class_exists( __NAMESPACE__ . '\Case_Study' ) && method_exists( __NAMESPACE__ . '\Case_Study', 'flush' ) ) {
			Case_Study::flush( $post_id );
		}
	}

	/* ------------------------------------------------------------------ */
	/* Theme options (Algenix / ThemeREX, §2.11)                          */
	/* ------------------------------------------------------------------ */

	/**
	 * The theme options a case study needs: full-screen body, no margins, the custom
	 * header that matches its hero (Header Home for dark, mani header for light).
	 *
	 * @param string $header 'dark' | 'light' | '' (read the post's avix_cs_header meta).
	 * @param int    $post_id Post ID, used when $header is ''.
	 */
	public static function theme_defaults( string $header = '', int $post_id = 0 ): array {
		$opts = array();
		$cls  = __NAMESPACE__ . '\Case_Studies';
		if ( class_exists( $cls ) && method_exists( $cls, 'default_theme_options' ) ) {
			try {
				// The data layer's version also merges the index page's and the post's own options.
				$opts = (array) call_user_func( array( $cls, 'default_theme_options' ), $post_id );
			} catch ( \Throwable $e ) {
				$opts = array();
			}
		}
		if ( ! $opts ) {
			$opts = array(
				'body_style'     => 'fullscreen',
				'remove_margins' => '1',
				'header_type'    => 'custom',
				'header_style'   => 'header-custom-266',
			);
		}
		if ( '' === $header && $post_id ) {
			$header = (string) get_post_meta( $post_id, 'avix_cs_header', true );
		}
		if ( 'light' === $header ) {
			$opts['header_style'] = 'header-custom-275';
		} elseif ( 'dark' === $header ) {
			$opts['header_style'] = 'header-custom-266';
		}
		return $opts;
	}

	/**
	 * Merges the case-study theme options into the post's algenix_options, never
	 * touching unrelated keys. New posts first inherit the index page's options
	 * (so site-specific header settings carry over), then get the case-study values.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $header  'dark' | 'light' | '' (from meta).
	 */
	public static function write_theme_options( int $post_id, string $header = '' ): void {
		$current = get_post_meta( $post_id, 'algenix_options', true );
		$current = is_array( $current ) ? $current : array();
		$base    = array();
		if ( ! $current ) {
			$index = self::index_page_id();
			$inh   = $index ? get_post_meta( $index, 'algenix_options', true ) : array();
			$base  = is_array( $inh ) ? $inh : array();
		}
		$merged = array_merge( $base, $current, self::theme_defaults( $header, $post_id ) );
		if ( $merged !== $current ) {
			// Slashed, because update_post_meta() unslashes and the other keys are kept byte for byte.
			update_post_meta( $post_id, 'algenix_options', wp_slash( $merged ) );
		}
	}

	/* ------------------------------------------------------------------ */
	/* Internals                                                          */
	/* ------------------------------------------------------------------ */

	/**
	 * Elements of the Elementor library template chosen in Settings, or array().
	 */
	private static function template_data(): array {
		$id = absint( get_option( self::OPTION_TEMPLATE, 0 ) );
		if ( ! $id || 'elementor_library' !== get_post_type( $id ) || 'trash' === get_post_status( $id ) ) {
			return array();
		}
		$raw  = get_post_meta( $id, '_elementor_data', true );
		$data = is_array( $raw ) ? $raw : json_decode( is_string( $raw ) ? $raw : '', true );
		return is_array( $data ) ? array_values( $data ) : array();
	}

	/**
	 * Keeps the current content in a revision before a reset. Elementor copies its
	 * meta onto the revision (_wp_put_post_revision), so the old layout can be restored.
	 */
	private static function save_revision( int $post_id ): void {
		if ( ! wp_revisions_enabled( get_post( $post_id ) ) ) {
			return;
		}
		$force = '__return_false';
		add_filter( 'wp_save_post_revision_check_for_changes', $force );
		$rev = wp_save_post_revision( $post_id );
		remove_filter( 'wp_save_post_revision_check_for_changes', $force );
		if ( ! $rev && function_exists( '_wp_put_post_revision' ) ) {
			_wp_put_post_revision( get_post( $post_id ) );
		}
	}

	/**
	 * Index page ID without requiring the data layer.
	 */
	private static function index_page_id(): int {
		$cls = __NAMESPACE__ . '\Case_Study';
		if ( class_exists( $cls ) && method_exists( $cls, 'index_page_id' ) ) {
			return (int) Case_Study::index_page_id();
		}
		$id = absint( get_option( 'avix_cs_index_page', 0 ) );
		if ( $id && 'page' === get_post_type( $id ) ) {
			return $id;
		}
		$page = get_page_by_path( 'case-studies', OBJECT, 'page' );
		return $page ? (int) $page->ID : 0;
	}

	/**
	 * True for a list of associative rows (an Elementor repeater value).
	 *
	 * @param array $value Setting value.
	 */
	private static function is_repeater( array $value ): bool {
		if ( ! $value || array_keys( $value ) !== range( 0, count( $value ) - 1 ) ) {
			return false;
		}
		foreach ( $value as $row ) {
			if ( ! is_array( $row ) || ! $row || array_keys( $row ) === range( 0, count( $row ) - 1 ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Empty settings must be saved as JSON objects ({}), not arrays ([]), or
	 * Elementor's editor treats them as a list.
	 *
	 * @param array $elements Elementor elements.
	 */
	private static function objectify( array $elements ): array {
		foreach ( $elements as $i => $el ) {
			if ( ! isset( $el['settings'] ) || ( is_array( $el['settings'] ) && ! $el['settings'] ) ) {
				$elements[ $i ]['settings'] = new \stdClass();
			}
			$elements[ $i ]['elements'] = isset( $el['elements'] ) && is_array( $el['elements'] ) ? self::objectify( $el['elements'] ) : array();
		}
		return $elements;
	}
}

// Hooks itself, so copy-on-create works whichever bootstrap loads this file.
Starter::init();
