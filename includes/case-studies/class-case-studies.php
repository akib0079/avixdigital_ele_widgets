<?php
/**
 * Case Studies bootstrap: the avix_case_study post type, its two taxonomies
 * and meta, Elementor support, theme (Algenix) integration, the one-character
 * rule on case-study pages, cache purges and a plain fallback page when no
 * Elementor layout exists (SPEC-CASE-STUDIES §1, §2.10, §2.11).
 *
 * Loaded from Avix_Elementor_Widgets::init() before the Elementor gate, so the
 * posts never disappear when Elementor is switched off.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Case_Studies;

defined( 'ABSPATH' ) || exit;

final class Case_Studies {

	const OPTION_INDEX      = 'avix_cs_index_page';
	const OPTION_STARTER    = 'avix_cs_starter_template';
	const OPTION_CHARACTERS = 'avix_cs_characters';
	const OPTION_REWRITE    = 'avix_cs_rewrite_version';

	const STYLE_HANDLE = 'avix-case-studies-global';
	const STYLE_FILE   = 'assets/css/case-studies-global.css';

	/** Theme header templates (live IDs): Header Home (dark) and "mani header" (light). */
	const HEADER_DARK  = 'header-custom-266';
	const HEADER_LIGHT = 'header-custom-275';

	/** Siblings, loaded with file_exists guards so a half-written file never fatals the site. */
	const SIBLINGS = array(
		'class-case-study.php' => '',
		'class-admin.php'      => 'Admin',
		'class-seo.php'        => 'SEO',
		'class-starter.php'    => 'Starter',
		'class-importer.php'   => 'Importer',
	);

	private static $booted = false;

	/** Post IDs already purged in this request. */
	private static $purged = array();

	/** Static guard for the fallback content (printed once per request). */
	private static $fallback_done = false;

	/* ------------------------------------------------------------------ */
	/* Boot                                                               */
	/* ------------------------------------------------------------------ */

	public static function init(): void {
		if ( self::$booted ) {
			return;
		}
		self::$booted = true;

		self::load_siblings();

		add_action( 'init', array( __CLASS__, 'register' ), 5 );
		add_action( 'init', array( __CLASS__, 'add_elementor_support' ), 20 );
		add_action( 'init', array( __CLASS__, 'maybe_flush_rewrites' ), 99 );
		add_filter( 'option_elementor_cpt_support', array( __CLASS__, 'filter_cpt_support' ) );
		add_filter( 'default_option_elementor_cpt_support', array( __CLASS__, 'filter_cpt_support' ) );

		// Front end: the avatar rule, theme routing and the fallback page.
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_filter( 'style_loader_src', array( __CLASS__, 'bust_cache' ), PHP_INT_MAX, 2 );
		add_filter( 'single_template_hierarchy', array( __CLASS__, 'template_hierarchy' ) );
		add_filter( 'template_include', array( __CLASS__, 'template_include' ), 20 );
		add_filter( 'the_content', array( __CLASS__, 'fallback_content' ), 20 );

		// Algenix / ThemeREX.
		add_filter( 'algenix_filter_detect_blog_mode', array( __CLASS__, 'theme_blog_mode' ) );
		add_filter( 'algenix_filter_allow_override_options', array( __CLASS__, 'theme_allow_override' ), 10, 2 );
		add_action( 'load-post.php', array( __CLASS__, 'theme_override_modes' ) );
		add_action( 'load-post-new.php', array( __CLASS__, 'theme_override_modes' ) );

		// Block editor off: content is built in Elementor, meta lives in our box.
		add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'disable_block_editor' ), 20, 2 );

		// Meta: empty means deleted, so defaults apply.
		add_filter( 'update_post_metadata', array( __CLASS__, 'drop_empty_meta' ), 10, 4 );

		// Freshness: element cache, LiteSpeed, theme options.
		add_action( 'save_post_' . Case_Study::POST_TYPE, array( __CLASS__, 'on_save' ), 30, 3 );
		add_action( 'added_post_meta', array( __CLASS__, 'on_meta_change' ), 10, 4 );
		add_action( 'updated_post_meta', array( __CLASS__, 'on_meta_change' ), 10, 4 );
		add_action( 'deleted_post_meta', array( __CLASS__, 'on_meta_change' ), 10, 4 );
		add_action( 'set_object_terms', array( __CLASS__, 'on_terms_change' ), 10, 4 );
		add_action( 'transition_post_status', array( __CLASS__, 'on_status_change' ), 10, 3 );
		add_action( 'updated_term_meta', array( __CLASS__, 'on_term_meta_change' ), 10, 3 );
		add_action( 'added_term_meta', array( __CLASS__, 'on_term_meta_change' ), 10, 3 );
		add_action( 'update_option_' . self::OPTION_INDEX, array( __CLASS__, 'on_index_option_change' ), 10, 2 );

		// Elementor dynamic tags, only when Elementor is there.
		if ( did_action( 'elementor/loaded' ) ) {
			self::load_dynamic_tags();
		} else {
			add_action( 'elementor/loaded', array( __CLASS__, 'load_dynamic_tags' ) );
		}
	}

	/**
	 * Requires the sibling files and starts the ones that have an init().
	 */
	private static function load_siblings(): void {
		$dir = __DIR__ . '/';
		foreach ( self::SIBLINGS as $file => $class ) {
			if ( ! file_exists( $dir . $file ) ) {
				continue;
			}
			try {
				require_once $dir . $file;
				$fqcn = __NAMESPACE__ . '\\' . $class;
				if ( '' !== $class && class_exists( $fqcn, false ) && method_exists( $fqcn, 'init' ) ) {
					if ( 'Admin' === $class && ! is_admin() ) {
						continue;
					}
					$fqcn::init();
				}
			} catch ( \Throwable $error ) {
				error_log( 'Avix case studies: "' . $file . '" failed to load: ' . $error->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		}
	}

	/**
	 * Elementor dynamic tags (§2.9).
	 */
	public static function load_dynamic_tags(): void {
		$file = __DIR__ . '/class-dynamic-tags.php';
		if ( ! file_exists( $file ) ) {
			return;
		}
		try {
			require_once $file;
			if ( class_exists( __NAMESPACE__ . '\Dynamic_Tags', false ) ) {
				Dynamic_Tags::init();
			}
		} catch ( \Throwable $error ) {
			error_log( 'Avix case studies: dynamic tags failed to load: ' . $error->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	/* ------------------------------------------------------------------ */
	/* Activation                                                         */
	/* ------------------------------------------------------------------ */

	public static function activate(): void {
		if ( ! class_exists( __NAMESPACE__ . '\Case_Study', false ) && file_exists( __DIR__ . '/class-case-study.php' ) ) {
			require_once __DIR__ . '/class-case-study.php';
		}
		self::register();
		flush_rewrite_rules( false );
		update_option( self::OPTION_REWRITE, self::version(), true );
	}

	public static function deactivate(): void {
		if ( post_type_exists( Case_Study::POST_TYPE ) ) {
			unregister_post_type( Case_Study::POST_TYPE );
		}
		flush_rewrite_rules( false );
		delete_option( self::OPTION_REWRITE );
	}

	/**
	 * Flushes rewrite rules once per plugin version, so /case-studies/<slug>/
	 * resolves after an update without visiting Settings → Permalinks.
	 */
	public static function maybe_flush_rewrites(): void {
		if ( get_option( self::OPTION_REWRITE ) === self::version() ) {
			return;
		}
		flush_rewrite_rules( false );
		update_option( self::OPTION_REWRITE, self::version(), true );
	}

	private static function version(): string {
		return defined( 'AVIX_EW_VERSION' ) ? (string) AVIX_EW_VERSION : '0';
	}

	/* ------------------------------------------------------------------ */
	/* Registration                                                       */
	/* ------------------------------------------------------------------ */

	public static function register(): void {
		if ( post_type_exists( Case_Study::POST_TYPE ) ) {
			return;
		}

		register_post_type(
			Case_Study::POST_TYPE,
			array(
				'labels'              => array(
					'name'                  => __( 'Case Studies', 'avix-widgets' ),
					'singular_name'         => __( 'Case Study', 'avix-widgets' ),
					'menu_name'             => __( 'Case Studies', 'avix-widgets' ),
					'add_new'               => __( 'Add New', 'avix-widgets' ),
					'add_new_item'          => __( 'Add New Case Study', 'avix-widgets' ),
					'new_item'              => __( 'New Case Study', 'avix-widgets' ),
					'edit_item'             => __( 'Edit Case Study', 'avix-widgets' ),
					'view_item'             => __( 'View Case Study', 'avix-widgets' ),
					'view_items'            => __( 'View Case Studies', 'avix-widgets' ),
					'all_items'             => __( 'All Case Studies', 'avix-widgets' ),
					'search_items'          => __( 'Search Case Studies', 'avix-widgets' ),
					'not_found'             => __( 'No case studies found', 'avix-widgets' ),
					'not_found_in_trash'    => __( 'No case studies found in Trash', 'avix-widgets' ),
					'featured_image'        => __( 'Card & social image', 'avix-widgets' ),
					'set_featured_image'    => __( 'Set card & social image', 'avix-widgets' ),
					'remove_featured_image' => __( 'Remove card & social image', 'avix-widgets' ),
					'use_featured_image'    => __( 'Use as card & social image', 'avix-widgets' ),
					'item_published'        => __( 'Case study published.', 'avix-widgets' ),
					'item_updated'          => __( 'Case study updated.', 'avix-widgets' ),
					'item_link'             => __( 'Case Study Link', 'avix-widgets' ),
					'filter_items_list'     => __( 'Filter case studies list', 'avix-widgets' ),
					'items_list'            => __( 'Case studies list', 'avix-widgets' ),
				),
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => true,
				'show_in_admin_bar'   => true,
				'exclude_from_search' => false,
				'menu_icon'           => 'dashicons-portfolio',
				'menu_position'       => 21,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'hierarchical'        => false,
				'has_archive'         => false,
				'rewrite'             => array(
					'slug'       => 'case-studies',
					'with_front' => false,
					'feeds'      => false,
					'pages'      => false,
				),
				'query_var'           => Case_Study::POST_TYPE,
				'show_in_rest'        => true,
				'rest_base'           => 'case-studies',
				'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields', 'page-attributes', 'elementor' ),
			)
		);

		$tax_common = array(
			'public'             => false,
			'publicly_queryable' => false,
			'rewrite'            => false,
			'query_var'          => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => false,
			'show_tagcloud'      => false,
			'show_admin_column'  => true,
			'show_in_rest'       => true,
			'hierarchical'       => true,
		);

		register_taxonomy(
			Case_Study::TAX_SERVICE,
			Case_Study::POST_TYPE,
			array_merge(
				$tax_common,
				array(
					'labels' => array(
						'name'          => __( 'Services', 'avix-widgets' ),
						'singular_name' => __( 'Service', 'avix-widgets' ),
						'menu_name'     => __( 'Services', 'avix-widgets' ),
						'all_items'     => __( 'All services', 'avix-widgets' ),
						'edit_item'     => __( 'Edit service', 'avix-widgets' ),
						'add_new_item'  => __( 'Add new service', 'avix-widgets' ),
						'search_items'  => __( 'Search services', 'avix-widgets' ),
						'not_found'     => __( 'No services found', 'avix-widgets' ),
						'back_to_items' => __( '← Back to services', 'avix-widgets' ),
					),
				)
			)
		);

		register_taxonomy(
			Case_Study::TAX_INDUSTRY,
			Case_Study::POST_TYPE,
			array_merge(
				$tax_common,
				array(
					'labels' => array(
						'name'          => __( 'Industries', 'avix-widgets' ),
						'singular_name' => __( 'Industry', 'avix-widgets' ),
						'menu_name'     => __( 'Industries', 'avix-widgets' ),
						'all_items'     => __( 'All industries', 'avix-widgets' ),
						'edit_item'     => __( 'Edit industry', 'avix-widgets' ),
						'add_new_item'  => __( 'Add new industry', 'avix-widgets' ),
						'search_items'  => __( 'Search industries', 'avix-widgets' ),
						'not_found'     => __( 'No industries found', 'avix-widgets' ),
						'back_to_items' => __( '← Back to industries', 'avix-widgets' ),
					),
				)
			)
		);

		self::register_meta();
	}

	/**
	 * register_post_meta for every FIELDS key, plus the service-page term meta.
	 */
	private static function register_meta(): void {
		$revisions = version_compare( get_bloginfo( 'version' ), '6.4', '>=' );
		$auth      = static function ( $allowed, $meta_key, $post_id ) {
			return current_user_can( 'edit_post', (int) $post_id );
		};

		foreach ( Case_Study::FIELDS as $key => $field ) {
			$is_image = 'image' === $field['type'];
			$args     = array(
				'single'            => true,
				'type'              => $is_image ? 'integer' : 'string',
				'default'           => $is_image ? 0 : '',
				'description'       => $field['label'],
				'sanitize_callback' => static function ( $value ) use ( $key ) {
					return Case_Study::sanitize( $key, $value );
				},
				'auth_callback'     => $auth,
				'show_in_rest'      => true,
			);
			if ( $revisions ) {
				$args['revisions_enabled'] = true;
			}
			register_post_meta( Case_Study::POST_TYPE, Case_Study::meta_key( $key ), $args );
		}

		register_term_meta(
			Case_Study::TAX_SERVICE,
			Case_Study::SERVICE_PAGE_META,
			array(
				'single'            => true,
				'type'              => 'integer',
				'default'           => 0,
				'description'       => 'Service page ID for service chip links',
				'sanitize_callback' => 'absint',
				'auth_callback'     => static function () {
					return current_user_can( 'manage_categories' );
				},
				'show_in_rest'      => true,
			)
		);
	}

	/**
	 * Elementor support for the CPT (init 20, after Elementor's own CPT pass).
	 */
	public static function add_elementor_support(): void {
		add_post_type_support( Case_Study::POST_TYPE, 'elementor' );
	}

	/**
	 * Appends the CPT to Elementor's "post types" option on read. The option is never written.
	 *
	 * @param mixed $value Option value.
	 * @return mixed
	 */
	public static function filter_cpt_support( $value ) {
		if ( is_array( $value ) && ! in_array( Case_Study::POST_TYPE, $value, true ) ) {
			$value[] = Case_Study::POST_TYPE;
		}
		return $value;
	}

	/**
	 * The classic screen for our CPT: content is built in Elementor.
	 *
	 * @param bool   $use       Whether to use the block editor.
	 * @param string $post_type Post type.
	 */
	public static function disable_block_editor( $use, $post_type ) {
		return Case_Study::POST_TYPE === $post_type ? false : $use;
	}

	/* ------------------------------------------------------------------ */
	/* Front end: body classes, stylesheet, the avatar rule              */
	/* ------------------------------------------------------------------ */

	/**
	 * one (default) | none | site.
	 */
	public static function characters_mode(): string {
		$mode = (string) get_option( self::OPTION_CHARACTERS, 'one' );
		return in_array( $mode, array( 'one', 'none', 'site' ), true ) ? $mode : 'one';
	}

	/**
	 * True on a case-study single.
	 */
	public static function is_single_page(): bool {
		return is_singular( Case_Study::POST_TYPE );
	}

	/**
	 * True on the /case-studies/ index page.
	 */
	public static function is_index_page(): bool {
		$index = Case_Study::index_page_id();
		return $index && is_page( $index );
	}

	/**
	 * @param string[] $classes Body classes.
	 */
	public static function body_class( $classes ) {
		$classes = (array) $classes;
		$single  = self::is_single_page();
		$index   = ! $single && self::is_index_page();
		if ( $single ) {
			$classes[] = 'avix-is-case-study';
		}
		if ( $index ) {
			$classes[] = 'avix-is-case-index';
		}
		if ( $single || $index ) {
			$classes[] = 'avix-cs-chars-' . self::characters_mode();
		}
		return $classes;
	}

	public static function enqueue(): void {
		if ( ! self::is_single_page() && ! self::is_index_page() ) {
			return;
		}
		wp_enqueue_style( self::STYLE_HANDLE, self::asset_url(), array(), self::asset_version() );
	}

	/**
	 * Adds "?avixv=" like the bootstrap does, for optimisers that strip "?ver=".
	 *
	 * @param string $src    Asset URL.
	 * @param string $handle Handle.
	 */
	public static function bust_cache( $src, $handle ) {
		if ( self::STYLE_HANDLE !== $handle || ! is_string( $src ) || '' === $src ) {
			return $src;
		}
		return add_query_arg( 'avixv', self::asset_version(), $src );
	}

	private static function asset_url(): string {
		$base = defined( 'AVIX_EW_URL' ) ? AVIX_EW_URL : plugin_dir_url( dirname( __DIR__ ) );
		return $base . self::STYLE_FILE;
	}

	private static function asset_version(): string {
		$path  = dirname( __DIR__, 2 ) . '/' . self::STYLE_FILE;
		$mtime = file_exists( $path ) ? (int) filemtime( $path ) : 0;
		return $mtime ? self::version() . '.' . $mtime : self::version();
	}

	/* ------------------------------------------------------------------ */
	/* Theme integration (§2.11)                                          */
	/* ------------------------------------------------------------------ */

	/**
	 * Page markup for case-study singles: no related posts, post nav, meta or comments.
	 *
	 * @param string $template Chosen template.
	 */
	public static function template_include( $template ) {
		if ( ! is_string( $template ) || ! self::is_single_page() ) {
			return $template;
		}
		if ( in_array( basename( $template ), array( 'single.php', 'singular.php', 'index.php' ), true ) ) {
			$page = locate_template( 'page.php' );
			if ( $page ) {
				return $page;
			}
		}
		return $template;
	}

	/**
	 * Same rule, earlier: page.php comes before single.php in the template
	 * hierarchy for case studies. This also covers block themes, which resolve
	 * templates from the hierarchy and never reach template_include with single.php.
	 * A theme's own single-avix_case_study.php still wins.
	 *
	 * @param string[] $templates Candidate templates.
	 */
	public static function template_hierarchy( $templates ) {
		if ( ! is_array( $templates ) || ! self::is_single_page() || in_array( 'page.php', $templates, true ) ) {
			return $templates;
		}
		$at = array_search( 'single.php', $templates, true );
		if ( false === $at ) {
			$templates[] = 'page.php';
			return $templates;
		}
		array_splice( $templates, (int) $at, 0, array( 'page.php' ) );
		return $templates;
	}

	/**
	 * @param mixed $mode Blog mode.
	 */
	public static function theme_blog_mode( $mode ) {
		return self::is_single_page() ? 'page' : $mode;
	}

	/**
	 * @param mixed  $allow Whether the Theme Options box is allowed.
	 * @param string $type  Post type, when the theme passes it.
	 */
	public static function theme_allow_override( $allow, $type = '' ) {
		if ( '' === $type || ! is_string( $type ) ) {
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
			$type   = $screen && isset( $screen->post_type ) ? $screen->post_type : get_post_type();
		}
		return Case_Study::POST_TYPE === $type ? true : $allow;
	}

	/**
	 * Shows the native Theme Options fields (body style, margins, header) on our screen.
	 */
	public static function theme_override_modes(): void {
		if ( ! function_exists( 'algenix_storage_get' ) ) {
			return;
		}
		$type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- screen detection only.
		if ( '' === $type && isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$type = (string) get_post_type( absint( $_GET['post'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		if ( Case_Study::POST_TYPE !== $type ) {
			return;
		}
		global $ALGENIX_STORAGE; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
		if ( ! isset( $ALGENIX_STORAGE['options'] ) || ! is_array( $ALGENIX_STORAGE['options'] ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
			return;
		}
		foreach ( array( 'body_style', 'remove_margins', 'header_type', 'header_style' ) as $key ) {
			if ( isset( $ALGENIX_STORAGE['options'][ $key ]['override']['mode'] ) && is_string( $ALGENIX_STORAGE['options'][ $key ]['override']['mode'] ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
				$mode = $ALGENIX_STORAGE['options'][ $key ]['override']['mode']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
				if ( false === strpos( $mode, Case_Study::POST_TYPE ) ) {
					$ALGENIX_STORAGE['options'][ $key ]['override']['mode'] = $mode . ',' . Case_Study::POST_TYPE; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
				}
			}
		}
	}

	/**
	 * The algenix_options a case study should have: the index page's options
	 * (when it exists), then the post's own, then the four keys the dark or
	 * light hero needs. Unrelated keys are never dropped. Used by the Starter.
	 */
	public static function default_theme_options( int $post_id ): array {
		$index   = Case_Study::index_page_id();
		$base    = $index ? get_post_meta( $index, 'algenix_options', true ) : array();
		$own     = $post_id ? get_post_meta( $post_id, 'algenix_options', true ) : array();
		$header  = $post_id ? (string) get_post_meta( $post_id, Case_Study::meta_key( 'header' ), true ) : '';
		$options = array_merge( is_array( $base ) ? $base : array(), is_array( $own ) ? $own : array() );

		$options['body_style']     = 'fullscreen';
		$options['remove_margins'] = '1';
		$options['header_type']    = 'custom';
		$options['header_style']   = 'light' === $header ? self::HEADER_LIGHT : self::HEADER_DARK;

		return $options;
	}

	/**
	 * Writes default_theme_options() to the post. Returns true when it changed.
	 */
	public static function apply_theme_options( int $post_id ): bool {
		if ( ! $post_id || Case_Study::POST_TYPE !== get_post_type( $post_id ) ) {
			return false;
		}
		$current = get_post_meta( $post_id, 'algenix_options', true );
		$next    = self::default_theme_options( $post_id );
		if ( is_array( $current ) && $current == $next ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- order-insensitive array compare.
			return false;
		}
		return (bool) update_post_meta( $post_id, 'algenix_options', $next );
	}

	/* ------------------------------------------------------------------ */
	/* Fallback content (no Elementor layout, or Elementor off)           */
	/* ------------------------------------------------------------------ */

	/**
	 * @param string $content Post content.
	 */
	public static function fallback_content( $content ) {
		if ( self::$fallback_done || ! self::is_single_page() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$id = (int) get_the_ID();
		if ( ! $id || Case_Study::POST_TYPE !== get_post_type( $id ) || (int) get_queried_object_id() !== $id || post_password_required( $id ) ) {
			return $content;
		}

		$elementor_on = did_action( 'elementor/loaded' ) > 0;
		$has_layout   = self::has_elementor_layout( $id );
		if ( $elementor_on && $has_layout ) {
			return $content;
		}

		$html = self::fallback_html( $id );
		if ( '' === $html ) {
			return $content;
		}
		self::$fallback_done = true;

		// Elementor off: post_content only holds Elementor's plain copy, so the fallback replaces it.
		if ( $has_layout ) {
			return $html;
		}
		return $html . ( '' !== trim( wp_strip_all_tags( (string) $content ) ) ? '<div class="avix-cs-fallback__more">' . $content . '</div>' : '' );
	}

	/**
	 * True when the post has a non-empty Elementor layout.
	 */
	public static function has_elementor_layout( int $id ): bool {
		$data = get_post_meta( $id, '_elementor_data', true );
		if ( is_array( $data ) ) {
			return ! empty( $data );
		}
		$data = is_string( $data ) ? trim( $data ) : '';
		return '' !== $data && '[]' !== $data;
	}

	/**
	 * A semantic page built from meta: h1, summary, facts, the three chapters and the live link.
	 */
	public static function fallback_html( int $id ): string {
		$cs = Case_Study::get( $id );
		if ( ! $cs ) {
			return '';
		}

		$title = '' !== $cs['display_title'] ? $cs['display_title'] : $cs['title'];
		$lead  = '' !== $cs['summary'] ? $cs['summary'] : $cs['excerpt'];
		$image = $cs['hero_render'] ? $cs['hero_render'] : $cs['hero_desktop'];
		$allow = Case_Study::RICH_TAGS + array( 'br' => array() );

		$out  = '<article class="avix-cs-fallback">';
		$out .= '<header class="avix-cs-fallback__head">';
		$out .= '<p class="avix-cs-fallback__crumbs"><a href="' . esc_url( Case_Study::index_url() ) . '">' . esc_html( Case_Study::index_title() ) . '</a></p>';
		$out .= '<h1 class="avix-cs-fallback__title">' . Case_Study::accent_html( $title, 'avix-cs-fallback__accent' ) . '</h1>';
		if ( '' !== $lead ) {
			$out .= '<p class="avix-cs-fallback__lead">' . esc_html( $lead ) . '</p>';
		}
		if ( '' !== $cs['live_url'] ) {
			$out .= '<p class="avix-cs-fallback__cta"><a class="avix-cs-fallback__button" href="' . esc_url( $cs['live_url'] ) . '" target="_blank" rel="noopener">' . esc_html__( 'Visit live site', 'avix-widgets' ) . ' <span aria-hidden="true">↗</span></a></p>';
		}
		$out .= '</header>';

		if ( $image ) {
			$img = wp_get_attachment_image(
				$image,
				'large',
				false,
				array(
					'class'         => 'avix-cs-fallback__img',
					'loading'       => 'eager',
					'decoding'      => 'async',
					'sizes'         => '(max-width: 1000px) 92vw, 960px',
				)
			);
			if ( $img ) {
				$out .= '<figure class="avix-cs-fallback__figure">' . $img . '</figure>';
			}
		}

		$facts = array(
			__( 'Client', 'avix-widgets' )   => esc_html( $cs['client'] ),
			__( 'Services', 'avix-widgets' ) => esc_html( implode( ', ', $cs['services'] ) ),
			__( 'Platform', 'avix-widgets' ) => esc_html( $cs['platform'] ),
			__( 'Year', 'avix-widgets' )     => esc_html( $cs['year'] ),
			__( 'Role', 'avix-widgets' )     => esc_html( $cs['role'] ),
			__( 'Website', 'avix-widgets' )  => '' !== $cs['live_url'] ? '<a href="' . esc_url( $cs['live_url'] ) . '" target="_blank" rel="noopener">' . esc_html( $cs['live_label'] ) . '</a>' : '',
		);
		$facts = array_filter( $facts, 'strlen' );
		if ( $facts ) {
			$out .= '<dl class="avix-cs-fallback__facts">';
			foreach ( $facts as $label => $value ) {
				$out .= '<div class="avix-cs-fallback__fact"><dt>' . esc_html( $label ) . '</dt><dd>' . $value . '</dd></div>';
			}
			$out .= '</dl>';
		}

		// The challenge.
		if ( '' !== $cs['challenge_statement'] || $cs['challenge_body'] ) {
			$out .= '<section class="avix-cs-fallback__section" id="challenge"><h2>' . esc_html__( 'The challenge', 'avix-widgets' ) . '</h2>';
			if ( '' !== $cs['challenge_statement'] ) {
				$out .= '<p class="avix-cs-fallback__statement">' . Case_Study::accent_html( $cs['challenge_statement'], 'avix-cs-fallback__accent' ) . '</p>';
			}
			foreach ( $cs['challenge_body'] as $para ) {
				$out .= '<p>' . wp_kses( $para, $allow ) . '</p>';
			}
			$out .= '</section>';
		}

		// Approach.
		if ( '' !== $cs['approach_intro'] || $cs['approach_body'] || $cs['implementations'] ) {
			$out .= '<section class="avix-cs-fallback__section" id="approach"><h2>' . esc_html__( 'The solution & execution', 'avix-widgets' ) . '</h2>';
			if ( '' !== $cs['approach_intro'] ) {
				$out .= '<p class="avix-cs-fallback__lead">' . esc_html( $cs['approach_intro'] ) . '</p>';
			}
			foreach ( $cs['approach_body'] as $para ) {
				$out .= '<p>' . wp_kses( $para, $allow ) . '</p>';
			}
			if ( $cs['implementations'] ) {
				$out .= '<ul class="avix-cs-fallback__list">';
				foreach ( $cs['implementations'] as $row ) {
					$out .= '<li><strong>' . esc_html( $row[0] ) . '</strong>' . ( '' !== $row[1] ? ' ' . esc_html( $row[1] ) : '' ) . '</li>';
				}
				$out .= '</ul>';
			}
			foreach ( $cs['solution_body'] as $para ) {
				$out .= '<p>' . wp_kses( $para, $allow ) . '</p>';
			}
			$out .= '</section>';
		}

		// Outcome.
		if ( '' !== $cs['outcome_statement'] || $cs['outcome_body'] || $cs['outcome_pillars'] || $cs['metrics'] ) {
			$out .= '<section class="avix-cs-fallback__section" id="impact"><h2>' . esc_html__( 'The impact', 'avix-widgets' ) . '</h2>';
			if ( '' !== $cs['outcome_statement'] ) {
				$out .= '<p class="avix-cs-fallback__statement">' . Case_Study::accent_html( $cs['outcome_statement'], 'avix-cs-fallback__accent' ) . '</p>';
			}
			foreach ( $cs['outcome_body'] as $para ) {
				$out .= '<p>' . wp_kses( $para, $allow ) . '</p>';
			}
			if ( $cs['outcome_pillars'] ) {
				$out .= '<ul class="avix-cs-fallback__list">';
				foreach ( $cs['outcome_pillars'] as $pillar ) {
					$out .= '<li>' . esc_html( $pillar ) . '</li>';
				}
				$out .= '</ul>';
			}
			if ( $cs['metrics'] ) {
				$out .= '<dl class="avix-cs-fallback__metrics">';
				foreach ( $cs['metrics'] as $metric ) {
					$out .= '<div><dt>' . esc_html( $metric[1] ) . '</dt><dd>' . esc_html( $metric[0] ) . ' <small>' . esc_html( $metric[2] ) . '</small></dd></div>';
				}
				$out .= '</dl>';
			}
			$out .= '</section>';
		}

		$out .= '<footer class="avix-cs-fallback__foot">';
		if ( '' !== $cs['live_url'] ) {
			$out .= '<a class="avix-cs-fallback__button" href="' . esc_url( $cs['live_url'] ) . '" target="_blank" rel="noopener">' . esc_html__( 'Visit live site', 'avix-widgets' ) . ' <span aria-hidden="true">↗</span></a>';
		}
		$out .= '<a class="avix-cs-fallback__back" href="' . esc_url( Case_Study::index_url() ) . '">' . esc_html__( 'All case studies', 'avix-widgets' ) . '</a>';
		$out .= '</footer></article>';

		return $out;
	}

	/* ------------------------------------------------------------------ */
	/* Meta hygiene                                                       */
	/* ------------------------------------------------------------------ */

	/**
	 * Saving an empty value deletes the key instead, so get() defaults apply.
	 * Runs after the registered sanitiser.
	 *
	 * @param mixed  $check     Short-circuit value.
	 * @param int    $object_id Post ID.
	 * @param string $meta_key  Meta key.
	 * @param mixed  $value     Sanitised value.
	 */
	public static function drop_empty_meta( $check, $object_id, $meta_key, $value ) {
		if ( null !== $check || ! is_string( $meta_key ) || 0 !== strpos( $meta_key, Case_Study::PREFIX ) ) {
			return $check;
		}
		if ( '' === Case_Study::field_key( $meta_key ) || ! Case_Study::is_empty_value( $value ) ) {
			return $check;
		}
		if ( Case_Study::POST_TYPE !== get_post_type( (int) $object_id ) ) {
			return $check;
		}
		delete_post_meta( (int) $object_id, $meta_key );
		return true;
	}

	/* ------------------------------------------------------------------ */
	/* Freshness: element cache, LiteSpeed, neighbours, index             */
	/* ------------------------------------------------------------------ */

	/**
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post.
	 * @param bool     $update  Update flag.
	 */
	public static function on_save( $post_id, $post, $update ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ( $post && 'auto-draft' === $post->post_status ) ) {
			return;
		}
		Case_Study::flush( (int) $post_id );
		self::purge( (int) $post_id, true );

		// Theme options for posts that came in without the Starter (importer, REST, duplicates).
		if ( ! is_array( get_post_meta( $post_id, 'algenix_options', true ) ) ) {
			self::apply_theme_options( (int) $post_id );
		}
	}

	/**
	 * @param int    $meta_id   Meta ID (or IDs on delete).
	 * @param int    $object_id Post ID.
	 * @param string $meta_key  Meta key.
	 * @param mixed  $value     Value.
	 */
	public static function on_meta_change( $meta_id, $object_id, $meta_key, $value ) {
		if ( ! is_string( $meta_key ) ) {
			return;
		}
		$ours = '' !== Case_Study::field_key( $meta_key );
		if ( ! $ours && ! in_array( $meta_key, array( '_thumbnail_id', '_elementor_data' ), true ) ) {
			return;
		}
		$object_id = (int) $object_id;
		if ( Case_Study::POST_TYPE !== get_post_type( $object_id ) ) {
			return;
		}
		Case_Study::flush( $object_id );
		self::purge( $object_id );

		if ( Case_Study::meta_key( 'header' ) === $meta_key ) {
			self::apply_theme_options( $object_id );
		}
	}

	/**
	 * @param int    $object_id Post ID.
	 * @param array  $terms     Terms.
	 * @param array  $tt_ids    Term taxonomy IDs.
	 * @param string $taxonomy  Taxonomy.
	 */
	public static function on_terms_change( $object_id, $terms, $tt_ids, $taxonomy ) {
		if ( in_array( $taxonomy, array( Case_Study::TAX_SERVICE, Case_Study::TAX_INDUSTRY ), true ) ) {
			Case_Study::flush( (int) $object_id );
			self::purge( (int) $object_id );
		}
	}

	/**
	 * Publishing, unpublishing or trashing changes the order, so neighbours and the index refresh too.
	 *
	 * @param string   $new  New status.
	 * @param string   $old  Old status.
	 * @param \WP_Post $post Post.
	 */
	public static function on_status_change( $new, $old, $post ) {
		if ( ! $post instanceof \WP_Post || Case_Study::POST_TYPE !== $post->post_type || $new === $old ) {
			return;
		}
		if ( 'publish' !== $new && 'publish' !== $old ) {
			return;
		}
		$id = (int) $post->ID;

		// Neighbours under the old order, then under the new one.
		$before = Case_Study::neighbours( $id );
		Case_Study::flush();
		$after = Case_Study::neighbours( $id );

		$ids = array_unique( array_filter( array( $id, Case_Study::index_page_id(), $before['prev'], $before['next'], $after['prev'], $after['next'] ) ) );
		foreach ( $ids as $target ) {
			self::clear_element_cache( (int) $target );
			do_action( 'litespeed_purge_post', (int) $target ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- LiteSpeed Cache hook.
		}
	}

	/**
	 * A service term now links to another page: every case study's chips change.
	 *
	 * @param int    $meta_id  Meta ID.
	 * @param int    $term_id  Term ID.
	 * @param string $meta_key Meta key.
	 */
	public static function on_term_meta_change( $meta_id, $term_id, $meta_key ) {
		if ( Case_Study::SERVICE_PAGE_META !== $meta_key ) {
			return;
		}
		Case_Study::flush();
		foreach ( Case_Study::ordered_ids() as $id ) {
			self::clear_element_cache( $id );
			do_action( 'litespeed_purge_post', $id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- LiteSpeed Cache hook.
		}
	}

	/**
	 * @param mixed $old Old value.
	 * @param mixed $new New value.
	 */
	public static function on_index_option_change( $old, $new ) {
		Case_Study::flush();
		foreach ( array( absint( $old ), absint( $new ) ) as $id ) {
			if ( $id ) {
				self::clear_element_cache( $id );
			}
		}
	}

	/**
	 * Clears Elementor's element cache for a case study, the index page and its
	 * neighbours (their Next cards show it), and asks LiteSpeed to purge them
	 * when the post is public. Runs once per post per request unless forced.
	 */
	public static function purge( int $id, bool $force = false ): void {
		if ( ! $id ) {
			return;
		}
		if ( ! $force && isset( self::$purged[ $id ] ) ) {
			return;
		}
		self::$purged[ $id ] = true;

		$neighbours = Case_Study::neighbours( $id );
		$targets    = array_unique( array_filter( array( $id, Case_Study::index_page_id(), $neighbours['prev'], $neighbours['next'] ) ) );
		foreach ( $targets as $target ) {
			self::clear_element_cache( (int) $target );
		}

		if ( 'publish' === get_post_status( $id ) ) {
			foreach ( $targets as $target ) {
				do_action( 'litespeed_purge_post', (int) $target ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- LiteSpeed Cache hook.
			}
		}
	}

	/**
	 * Elementor's per-post element cache (Elementor ≥ 3.22).
	 */
	public static function clear_element_cache( int $id ): void {
		if ( $id > 0 ) {
			delete_post_meta( $id, '_elementor_element_cache' );
		}
	}
}
