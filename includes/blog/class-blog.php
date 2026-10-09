<?php
/**
 * Blog article template: the single post design (blog/spec/SPEC.md).
 *
 * Single posts are printed by includes/blog/templates/single-post.php between the
 * theme's own get_header() and get_footer(), so the ThemeREX header and footer
 * layouts render exactly as on every other page. (The templates live under
 * includes/ because the release package ships assets/ and includes/ only.)
 * Nothing is saved per post:
 * the theme options the design needs (full-width body, no margins, the dark
 * "Header Home" layout, no sidebar) are merged into the theme's per-request
 * option store, the same values a case study stores in its algenix_options.
 * Switching the template off (Settings > Avix blog) restores the theme's own
 * single post at once.
 *
 * The template steps aside for: password-protected posts, posts built with
 * Elementor, an Elementor Pro Theme Builder single template, AMP, embeds,
 * feeds, posts with the meta _avix_article_template = off, and anything the
 * avix_article_template_enabled filter turns off.
 *
 * Filters (all documented where they are applied):
 * - avix_article_template_enabled( bool $enabled, WP_Post $post )
 * - avix_blog_theme_options( array $options, WP_Post $post )
 * - avix_blog_services( array $services )
 * - avix_blog_service_key( string $key, WP_Post $post )
 * - avix_blog_contact_url( string $url )
 * - avix_blog_author( array $author, WP_User $user, WP_Post $post )
 * - avix_blog_author_image_id( int $id, int $user_id )
 * - avix_blog_related_ids( int[] $ids, WP_Post $post, int $count )
 *
 * Loaded from Avix_Elementor_Widgets::init() before the Elementor gate: the
 * template does not need Elementor.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Blog;

defined( 'ABSPATH' ) || exit;

final class Blog {

	const STYLE  = 'avix-blog-article';
	const SCRIPT = 'avix-blog-article';
	const CSS    = 'assets/css/blog-article.css';
	const JS     = 'assets/js/blog-article.js';

	/** Minified build of CSS (php tests/blog-build.php); served while it matches its source. */
	const MIN_CSS = 'assets/css/blog-article.min.css';

	/**
	 * Self-hosted fonts the stylesheet declares and the page preloads (Inter
	 * variable, Space Grotesk variable; latin). Space Grotesk is not taken
	 * from Elementor: on the live site that copy is five static TTFs from
	 * fonts.gstatic.com, discovered late through CSS, and swapping the H1 to
	 * it after first paint re-wrapped the headline (CLS 0.013-0.031 measured
	 * locally without the preload). The preloaded 22 KB woff2 keeps CLS at 0.
	 */
	const FONTS = array( 'assets/fonts/inter.woff2', 'assets/fonts/space-grotesk.woff2' );

	/** Per-post switch: 'off' keeps the theme's single post template for that post. */
	const META_TEMPLATE = '_avix_article_template';

	/** Per-post CTA service: shopify | wordpress | web-apps | uiux | general ('' = automatic). */
	const META_SERVICE = '_avix_article_service';

	/** Bumped whenever a post changes, so cached related-post lists go stale together. */
	const SALT_OPTION = 'avix_blog_cache_salt';

	/** Theme header layout: "Header Home" (dark), the one service pages and case studies use. */
	const HEADER_STYLE = 'header-custom-266';

	/** Siblings, loaded with file_exists guards so a half-written file never fatals the site. */
	const SIBLINGS = array(
		'class-settings.php'  => 'Settings',
		'class-toc.php'       => 'Toc',
		'class-content.php'   => 'Content',
		'class-article.php'   => 'Article',
		'class-post-meta.php' => 'Post_Meta',
	);

	/** @var bool */
	private static $booted = false;

	/** @var bool|null Per-request decision, once the main query is known. */
	private static $active = null;

	public static function init(): void {
		if ( self::$booted ) {
			return;
		}
		self::$booted = true;

		foreach ( self::SIBLINGS as $file => $class ) {
			$path = __DIR__ . '/' . $file;
			if ( ! file_exists( $path ) ) {
				continue;
			}
			try {
				require_once $path;
				$fqcn = __NAMESPACE__ . '\\' . $class;
				if ( class_exists( $fqcn, false ) && method_exists( $fqcn, 'init' ) ) {
					$fqcn::init();
				}
			} catch ( \Throwable $error ) {
				error_log( 'Avix blog: "' . $file . '" failed to load: ' . $error->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		}

		// Theme integration (Algenix / ThemeREX). Both hooks run on 'wp' (priority 1 in the theme).
		add_action( 'algenix_action_override_theme_options', array( __CLASS__, 'theme_options' ), 20 );
		add_filter( 'algenix_filter_single_post_header', array( __CLASS__, 'single_post_header' ), 20 );

		// Routing: after Elementor Pro's theme builder (priority 12) and the case studies (20).
		add_filter( 'template_include', array( __CLASS__, 'template_include' ), 30 );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );

		// Every content image sits below the hero: lazy-load them all.
		add_filter( 'wp_omit_loading_attr_threshold', array( __CLASS__, 'omit_threshold' ), 20 );

		// Assets.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 20 );
		add_filter( 'wp_preload_resources', array( __CLASS__, 'preload' ) );
		add_filter( 'style_loader_src', array( __CLASS__, 'bust_cache' ), PHP_INT_MAX, 2 );
		add_filter( 'script_loader_src', array( __CLASS__, 'bust_cache' ), PHP_INT_MAX, 2 );

		// Cached related posts go stale with any post or category change.
		add_action( 'clean_post_cache', array( __CLASS__, 'bump_salt_for_post' ), 10, 2 );
		add_action( 'edited_category', array( __CLASS__, 'bump_salt' ) );
		add_action( 'delete_category', array( __CLASS__, 'bump_salt' ) );
	}

	/* ------------------------------------------------------------------ */
	/* The decision                                                       */
	/* ------------------------------------------------------------------ */

	/**
	 * True when this request is a single post that gets the article template.
	 * Before the main query has run (before 'wp') the answer is false and not kept.
	 */
	public static function is_active(): bool {
		if ( null !== self::$active ) {
			return self::$active;
		}
		if ( ! did_action( 'wp' ) && ! doing_action( 'wp' ) ) {
			return false;
		}
		self::$active = self::decide();
		return self::$active;
	}

	/**
	 * Forgets the per-request decision (tests, or code that switches the main query).
	 */
	public static function reset(): void {
		self::$active = null;
	}

	private static function decide(): bool {
		if ( is_admin() || wp_doing_ajax() || is_feed() || is_embed() || ! is_singular( 'post' ) ) {
			return false;
		}
		if ( function_exists( 'amp_is_request' ) && amp_is_request() ) {
			return false;
		}
		if ( self::is_elementor_preview() ) {
			return false;
		}
		$post = get_queried_object();
		if ( ! $post instanceof \WP_Post || 'post' !== $post->post_type ) {
			return false;
		}
		$enabled = class_exists( __NAMESPACE__ . '\Settings', false ) ? (bool) Settings::get( 'enabled' ) : true;
		$enabled = $enabled && self::post_allows( $post ) && self::theme_supported();
		if ( $enabled && self::elementor_pro_single() ) {
			$enabled = false;
		}
		/**
		 * Whether this single post is printed with the Avix article template.
		 *
		 * @param bool     $enabled
		 * @param \WP_Post $post
		 */
		return (bool) apply_filters( 'avix_article_template_enabled', $enabled, $post );
	}

	/**
	 * Per-post reasons to keep the theme template (pure enough to unit test).
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function post_allows( \WP_Post $post ): bool {
		if ( post_password_required( $post ) ) {
			return false;
		}
		if ( 'off' === (string) get_post_meta( $post->ID, self::META_TEMPLATE, true ) ) {
			return false;
		}
		// Built with Elementor: the owner chose that layout.
		if ( 'builder' === (string) get_post_meta( $post->ID, '_elementor_edit_mode', true ) ) {
			return false;
		}
		return true;
	}

	/**
	 * A classic theme with a header.php (get_header()/get_footer() print its
	 * layouts), or a block theme (header and footer template parts).
	 */
	private static function theme_supported(): bool {
		if ( self::is_block_theme() ) {
			return true;
		}
		return '' !== locate_template( array( 'header.php' ) ) && '' !== locate_template( array( 'footer.php' ) );
	}

	public static function is_block_theme(): bool {
		return function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();
	}

	/** The Elementor editor's preview iframe of this post. */
	private static function is_elementor_preview(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only context check.
		return isset( $_GET['elementor-preview'] );
	}

	/**
	 * An Elementor Pro Theme Builder "Single" template applies to this post:
	 * respect the owner's explicit choice.
	 */
	private static function elementor_pro_single(): bool {
		if ( ! class_exists( '\ElementorPro\Modules\ThemeBuilder\Module' ) ) {
			return false;
		}
		try {
			$module = \ElementorPro\Modules\ThemeBuilder\Module::instance();
			if ( ! is_object( $module ) || ! method_exists( $module, 'get_conditions_manager' ) ) {
				return false;
			}
			$manager = $module->get_conditions_manager();
			if ( ! is_object( $manager ) || ! method_exists( $manager, 'get_documents_for_location' ) ) {
				return false;
			}
			return ! empty( $manager->get_documents_for_location( 'single' ) );
		} catch ( \Throwable $error ) {
			return false;
		}
	}

	/* ------------------------------------------------------------------ */
	/* Theme integration                                                  */
	/* ------------------------------------------------------------------ */

	/**
	 * The theme options an article page runs with. Each one is written as
	 * <key>, <key>_single and <key>_mobile, because algenix_get_theme_option()
	 * reads options_meta[<key>_mobile] first on phones, then the global mobile
	 * options, then <key>_single, then <key>.
	 *
	 * @param \WP_Post|null $post Post.
	 */
	public static function theme_values( $post = null ): array {
		$values = array(
			'body_style'            => 'fullscreen',
			'remove_margins'        => '1',
			'header_type'           => 'custom',
			'header_style'          => self::HEADER_STYLE,
			'sidebar_position'      => 'hide',
			'sidebar_widgets'       => 'hide',
			'widgets_above_page'    => 'hide',
			'widgets_above_content' => 'hide',
			'widgets_below_content' => 'hide',
			'widgets_below_page'    => 'hide',
			'show_related_posts'    => '0',
			'posts_navigation'      => 'none',
		);
		/**
		 * Theme options merged into the theme's per-request store on article pages.
		 *
		 * @param array         $values Option => value.
		 * @param \WP_Post|null $post
		 */
		$values = apply_filters( 'avix_blog_theme_options', $values, $post );
		return is_array( $values ) ? $values : array();
	}

	/**
	 * Pure helper (unit-testable): $meta with every value written as <key>,
	 * <key>_single and <key>_mobile.
	 *
	 * @param mixed $meta   Current options_meta (anything; non-arrays become array()).
	 * @param array $values Option => value.
	 */
	public static function merge_theme_values( $meta, array $values ): array {
		$meta = is_array( $meta ) ? $meta : array();
		foreach ( $values as $key => $value ) {
			if ( ! is_string( $key ) || '' === $key ) {
				continue;
			}
			foreach ( array( $key, $key . '_single', $key . '_mobile' ) as $name ) {
				$meta[ $name ] = $value;
			}
		}
		return $meta;
	}

	/**
	 * algenix_action_override_theme_options (runs on 'wp' priority 1, right
	 * after the theme copied the post's algenix_options into options_meta).
	 */
	public static function theme_options(): void {
		if ( ! function_exists( 'algenix_storage_get' ) || ! function_exists( 'algenix_storage_set' ) || ! self::is_active() ) {
			return;
		}
		$post = get_queried_object();
		algenix_storage_set( 'options_meta', self::merge_theme_values( algenix_storage_get( 'options_meta' ), self::theme_values( $post instanceof \WP_Post ? $post : null ) ) );
	}

	/**
	 * No theme single-post banner (title/featured image block in header.php)
	 * and no single_style_* body class on article pages.
	 *
	 * @param mixed $show Theme decision.
	 */
	public static function single_post_header( $show ) {
		return self::is_active() ? false : $show;
	}

	/**
	 * @param mixed $template Chosen template.
	 */
	public static function template_include( $template ) {
		if ( ! self::is_active() ) {
			return $template;
		}
		$file = self::path( 'includes/blog/templates/single-post.php' );
		return file_exists( $file ) ? $file : $template;
	}

	/**
	 * @param mixed $classes Body classes.
	 */
	public static function body_class( $classes ) {
		$classes = is_array( $classes ) ? $classes : array();
		if ( self::is_active() ) {
			$classes[] = 'avix-is-article';
		}
		return $classes;
	}

	/**
	 * WordPress leaves the first images of the_content eager (and gives the
	 * first one fetchpriority="high"), assuming they may be above the fold.
	 * On an article page the hero holds the only image above the fold, so
	 * every content image can be lazy.
	 *
	 * @param mixed $threshold Number of content images that are not lazy-loaded.
	 */
	public static function omit_threshold( $threshold ) {
		return self::is_active() ? 0 : $threshold;
	}

	/* ------------------------------------------------------------------ */
	/* Assets                                                             */
	/* ------------------------------------------------------------------ */

	public static function enqueue(): void {
		if ( ! self::is_active() ) {
			return;
		}
		$css = self::css_file();
		wp_enqueue_style( self::STYLE, self::url( $css ), array(), self::version( $css ) );
		wp_enqueue_script(
			self::SCRIPT,
			self::url( self::JS ),
			array(),
			self::version( self::JS ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/**
	 * The stylesheet to serve: the minified build while its first line names
	 * the md5 of the current blog-article.css (so a CSS edit without a
	 * rebuild falls back to the readable file), else blog-article.css.
	 * SCRIPT_DEBUG always serves the readable file.
	 */
	public static function css_file(): string {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			return self::CSS;
		}
		$min = self::path( self::MIN_CSS );
		$src = self::path( self::CSS );
		if ( ! is_readable( $min ) || ! is_readable( $src ) ) {
			return self::CSS;
		}
		$handle = fopen( $min, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- one line of a local file.
		$line   = $handle ? (string) fgets( $handle, 512 ) : '';
		if ( $handle ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		}
		$hash = md5_file( $src );
		return ( $hash && false !== strpos( $line, 'source:' . $hash ) ) ? self::MIN_CSS : self::CSS;
	}

	/**
	 * Preloads the two self-hosted fonts on article pages (H1 and dek are above the fold).
	 *
	 * @param mixed $resources Preload resources.
	 */
	public static function preload( $resources ) {
		$resources = is_array( $resources ) ? $resources : array();
		if ( ! self::is_active() ) {
			return $resources;
		}
		foreach ( self::FONTS as $font ) {
			if ( file_exists( self::path( $font ) ) ) {
				$resources[] = array(
					'href'        => self::url( $font ),
					'as'          => 'font',
					'type'        => 'font/woff2',
					'crossorigin' => 'anonymous',
				);
			}
		}
		return $resources;
	}

	/**
	 * "?avixv=" like the widgets' assets, for optimisers that strip "?ver=".
	 *
	 * @param mixed  $src    Asset URL.
	 * @param string $handle Handle.
	 */
	public static function bust_cache( $src, $handle ) {
		if ( ! is_string( $src ) || '' === $src ) {
			return $src;
		}
		if ( self::STYLE === $handle && false !== strpos( $src, self::MIN_CSS ) ) {
			return add_query_arg( 'avixv', self::version( self::MIN_CSS ), $src );
		}
		if ( self::STYLE === $handle && false !== strpos( $src, self::CSS ) ) {
			return add_query_arg( 'avixv', self::version( self::CSS ), $src );
		}
		if ( self::SCRIPT === $handle && false !== strpos( $src, self::JS ) ) {
			return add_query_arg( 'avixv', self::version( self::JS ), $src );
		}
		return $src;
	}

	/* ------------------------------------------------------------------ */
	/* Cache salt                                                         */
	/* ------------------------------------------------------------------ */

	public static function salt(): string {
		return (string) get_option( self::SALT_OPTION, '0' );
	}

	public static function bump_salt(): void {
		update_option( self::SALT_OPTION, (string) microtime( true ), true );
	}

	/**
	 * @param mixed $post_id Post ID.
	 * @param mixed $post    Post.
	 */
	public static function bump_salt_for_post( $post_id, $post = null ): void {
		if ( $post instanceof \WP_Post && 'post' === $post->post_type ) {
			self::bump_salt();
		}
	}

	/* ------------------------------------------------------------------ */
	/* Rendering                                                          */
	/* ------------------------------------------------------------------ */

	/**
	 * Prints the article for the current post (inside the loop).
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function render( \WP_Post $post ): void {
		if ( ! class_exists( __NAMESPACE__ . '\Article', false ) ) {
			the_content();
			return;
		}
		$file = self::path( 'includes/blog/templates/article.php' );
		if ( ! file_exists( $file ) ) {
			the_content();
			return;
		}
		load_template( $file, false, Article::view( $post ) );
	}

	/**
	 * Block themes: the document head and the header template part. The part
	 * is rendered before wp_head() so the styles its blocks need are enqueued.
	 */
	public static function block_theme_open(): void {
		ob_start();
		if ( function_exists( 'block_header_area' ) ) {
			block_header_area();
		}
		$header = (string) ob_get_clean();
		?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="wp-site-blocks">
<a class="skip-link screen-reader-text" href="#avix-article"><?php esc_html_e( 'Skip to content', 'avix-widgets' ); ?></a>
<?php
		if ( '' !== trim( $header ) ) {
			echo '<header class="wp-block-template-part">' . $header . '</header>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered template part.
		}
	}

	public static function block_theme_close(): void {
		if ( function_exists( 'block_footer_area' ) ) {
			ob_start();
			block_footer_area();
			$footer = (string) ob_get_clean();
			if ( '' !== trim( $footer ) ) {
				echo '<footer class="wp-block-template-part">' . $footer . '</footer>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered template part.
			}
		}
		echo "</div>\n";
		wp_footer();
		echo "</body>\n</html>\n";
	}

	/* ------------------------------------------------------------------ */
	/* Paths                                                              */
	/* ------------------------------------------------------------------ */

	public static function path( string $relative ): string {
		$base = defined( 'AVIX_EW_PATH' ) ? AVIX_EW_PATH : trailingslashit( dirname( __DIR__, 2 ) );
		return $base . ltrim( $relative, '/' );
	}

	public static function url( string $relative ): string {
		$base = defined( 'AVIX_EW_URL' ) ? AVIX_EW_URL : plugin_dir_url( dirname( __DIR__ ) );
		return $base . ltrim( $relative, '/' );
	}

	public static function version( string $relative ): string {
		$base  = defined( 'AVIX_EW_VERSION' ) ? (string) AVIX_EW_VERSION : '1';
		$path  = self::path( $relative );
		$mtime = file_exists( $path ) ? (int) filemtime( $path ) : 0;
		return $mtime ? $base . '.' . $mtime : $base;
	}
}
