<?php
/**
 * Plugin Name:       Avix Digital Elementor Widgets
 * Plugin URI:        https://avixdigital.com
 * Description:       Custom Elementor widgets for avixdigital.com: Service Benefits, About Hero, Hero Banner, Services Showcase, Selected Work (scroll stack), Impact Numbers, Testimonial Stack, Site Footer, Process Timeline, FAQ & Quote, Compare & CEO Quote, Client Logos, Intro Text, Smart Header, Team, Page Hero, Service Index, Story, Founder, Values, Journey, Careers, Post Grid, Service Tabs, Ticker, plus the Case Studies post type with Case Study Hero, Chapter, Feature Spotlight, Results, Gallery, Stack, Next and Grid.
 * Version:           1.17.4
 * Author:            Avix Digital
 * Author URI:        https://avixdigital.com
 * Text Domain:       avix-widgets
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Requires Plugins:  elementor
 * Elementor tested up to: 4.3.3
 *
 * @package AvixWidgets
 */

defined( 'ABSPATH' ) || exit;

define( 'AVIX_EW_VERSION', '1.17.4' );
define( 'AVIX_EW_FILE', __FILE__ );
define( 'AVIX_EW_PATH', plugin_dir_path( __FILE__ ) );
define( 'AVIX_EW_URL', plugin_dir_url( __FILE__ ) );

/**
 * Bootstraps the widgets once Elementor is available.
 */
final class Avix_Elementor_Widgets {

	const MIN_ELEMENTOR = '3.20.0';
	const CATEGORY      = 'avix-digital';

	/**
	 * Widget slug => [ file, class, shared assets ]. New widgets only need an
	 * entry here. Shared assets (see $shared) load before the widget's own CSS
	 * and JS.
	 *
	 * @var array<string, array{0:string,1:string,2?:string[]}>
	 */
	private static $widgets = array(
		'service-benefits'  => array( 'includes/widgets/class-service-benefits.php', '\\AvixWidgets\\Widgets\\Service_Benefits' ),
		'about-hero'        => array( 'includes/widgets/class-about-hero.php', '\AvixWidgets\Widgets\About_Hero' ),
		'hero'              => array( 'includes/widgets/class-hero.php', '\AvixWidgets\Widgets\Hero' ),
		'services'          => array( 'includes/widgets/class-services.php', '\AvixWidgets\Widgets\Services' ),
		'selected-work'     => array( 'includes/widgets/class-selected-work.php', '\AvixWidgets\Widgets\Selected_Work' ),
		'impact-numbers'    => array( 'includes/widgets/class-impact-numbers.php', '\AvixWidgets\Widgets\Impact_Numbers' ),
		'testimonial-stack' => array( 'includes/widgets/class-testimonial-stack.php', '\AvixWidgets\Widgets\Testimonial_Stack' ),
		'site-footer'       => array( 'includes/widgets/class-site-footer.php', '\AvixWidgets\Widgets\Site_Footer' ),
		'process-timeline'  => array( 'includes/widgets/class-process-timeline.php', '\AvixWidgets\Widgets\Process_Timeline' ),
		'faq'               => array( 'includes/widgets/class-faq.php', '\AvixWidgets\Widgets\Faq' ),
		'compare-quote'     => array( 'includes/widgets/class-compare-quote.php', '\AvixWidgets\Widgets\Compare_Quote' ),
		'client-logos'      => array( 'includes/widgets/class-client-logos.php', '\AvixWidgets\Widgets\Client_Logos' ),
		'intro-text'        => array( 'includes/widgets/class-intro-text.php', '\AvixWidgets\Widgets\Intro_Text' ),
		'smart-header'      => array( 'includes/widgets/class-smart-header.php', '\AvixWidgets\Widgets\Smart_Header' ),
		'team'              => array( 'includes/widgets/class-team.php', '\AvixWidgets\Widgets\Team' ),
		'page-hero'         => array( 'includes/widgets/class-page-hero.php', '\AvixWidgets\Widgets\Page_Hero', array( 'pixel-pal' ) ),
		'service-index'     => array( 'includes/widgets/class-service-index.php', '\AvixWidgets\Widgets\Service_Index', array( 'pixel-pal' ) ),
		'story'             => array( 'includes/widgets/class-story.php', '\AvixWidgets\Widgets\Story', array( 'pixel-pal' ) ),
		'founder'           => array( 'includes/widgets/class-founder.php', '\AvixWidgets\Widgets\Founder', array( 'pixel-pal' ) ),
		'values'            => array( 'includes/widgets/class-values.php', '\AvixWidgets\Widgets\Values', array( 'pixel-pal' ) ),
		'journey'           => array( 'includes/widgets/class-journey.php', '\AvixWidgets\Widgets\Journey', array( 'pixel-pal' ) ),
		'careers'           => array( 'includes/widgets/class-careers.php', '\AvixWidgets\Widgets\Careers', array( 'pixel-pal' ) ),
		'post-grid'         => array( 'includes/widgets/class-post-grid.php', '\AvixWidgets\Widgets\Post_Grid', array( 'pixel-pal' ) ),
		'service-tabs'      => array( 'includes/widgets/class-service-tabs.php', '\AvixWidgets\Widgets\Service_Tabs', array( 'pixel-pal' ) ),
		'ticker'            => array( 'includes/widgets/class-ticker.php', '\AvixWidgets\Widgets\Ticker', array( 'pixel-pal' ) ),
		'case-study-hero'      => array( 'includes/widgets/class-case-study-hero.php', '\AvixWidgets\Widgets\Case_Study_Hero', array( 'case-study-kit' ) ),
		'case-study-chapter'   => array( 'includes/widgets/class-case-study-chapter.php', '\AvixWidgets\Widgets\Case_Study_Chapter', array( 'case-study-kit' ) ),
		'case-study-spotlight' => array( 'includes/widgets/class-case-study-spotlight.php', '\AvixWidgets\Widgets\Case_Study_Spotlight', array( 'case-study-kit' ) ),
		'case-study-results'   => array( 'includes/widgets/class-case-study-results.php', '\AvixWidgets\Widgets\Case_Study_Results', array( 'case-study-kit' ) ),
		'case-study-gallery'   => array( 'includes/widgets/class-case-study-gallery.php', '\AvixWidgets\Widgets\Case_Study_Gallery', array( 'case-study-kit' ) ),
		'case-study-stack'     => array( 'includes/widgets/class-case-study-stack.php', '\AvixWidgets\Widgets\Case_Study_Stack', array( 'case-study-kit' ) ),
		'case-study-next'      => array( 'includes/widgets/class-case-study-next.php', '\AvixWidgets\Widgets\Case_Study_Next', array( 'case-study-kit', 'pixel-pal' ) ),
		'case-study-grid'      => array( 'includes/widgets/class-case-study-grid.php', '\AvixWidgets\Widgets\Case_Study_Grid', array( 'case-study-kit' ) ),
	);

	/**
	 * Assets several widgets share: slug => has a script.
	 *
	 * @var array<string, bool>
	 */
	private static $shared = array(
		'pixel-pal'      => true,
		'case-study-kit' => true,
	);

	public static function init() {
		// The Case Studies post type does not need Elementor, so the posts never
		// disappear when Elementor is switched off.
		if ( file_exists( AVIX_EW_PATH . 'includes/case-studies/class-case-studies.php' ) ) {
			require_once AVIX_EW_PATH . 'includes/case-studies/class-case-studies.php';
			\AvixWidgets\Case_Studies\Case_Studies::init();
		}
		// Site-wide pixel reveal for content images (front end only; see includes/pixel-reveal.php).
		if ( file_exists( AVIX_EW_PATH . 'includes/pixel-reveal.php' ) ) {
			require_once AVIX_EW_PATH . 'includes/pixel-reveal.php';
			if ( class_exists( '\\AvixWidgets\\Pixel_Reveal' ) ) {
				\AvixWidgets\Pixel_Reveal::init();
			}
		}
		// Blog article template: the single post design (see includes/blog/class-blog.php).
		if ( file_exists( AVIX_EW_PATH . 'includes/blog/class-blog.php' ) ) {
			require_once AVIX_EW_PATH . 'includes/blog/class-blog.php';
			if ( class_exists( '\\AvixWidgets\\Blog\\Blog' ) ) {
				\AvixWidgets\Blog\Blog::init();
			}
		}
		// SEO module: entity schema, founder Person, Service graph piece, redirects, SEO data importer (see includes/seo/load.php).
		if ( file_exists( AVIX_EW_PATH . 'includes/seo/load.php' ) ) {
			require_once AVIX_EW_PATH . 'includes/seo/load.php';
		}
		add_action( 'plugins_loaded', array( __CLASS__, 'boot' ) );
	}

	public static function activate() {
		if ( class_exists( '\AvixWidgets\Case_Studies\Case_Studies' ) ) {
			\AvixWidgets\Case_Studies\Case_Studies::activate();
		}
	}

	public static function deactivate() {
		if ( class_exists( '\AvixWidgets\Case_Studies\Case_Studies' ) ) {
			\AvixWidgets\Case_Studies\Case_Studies::deactivate();
		}
	}

	public static function boot() {
		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'notice_missing_elementor' ) );
			return;
		}

		if ( ! defined( 'ELEMENTOR_VERSION' ) || version_compare( ELEMENTOR_VERSION, self::MIN_ELEMENTOR, '<' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'notice_old_elementor' ) );
			return;
		}

		// Front-end AJAX endpoints (e.g. loading more posts) run without Elementor's widget registry.
		if ( file_exists( AVIX_EW_PATH . 'includes/ajax.php' ) ) {
			require_once AVIX_EW_PATH . 'includes/ajax.php';
		}
		if ( file_exists( AVIX_EW_PATH . 'includes/case-studies/cards.php' ) ) {
			require_once AVIX_EW_PATH . 'includes/case-studies/cards.php';
		}
		// Front-end weight the pages do not need (the WordPress media player where nothing plays; see includes/perf.php).
		if ( file_exists( AVIX_EW_PATH . 'includes/perf.php' ) ) {
			require_once AVIX_EW_PATH . 'includes/perf.php';
			if ( class_exists( '\\AvixWidgets\\Perf' ) ) {
				\AvixWidgets\Perf::init();
			}
		}

		add_action( 'elementor/elements/categories_registered', array( __CLASS__, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( __CLASS__, 'register_widgets' ) );
		add_action( 'elementor/frontend/after_register_styles', array( __CLASS__, 'register_styles' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( __CLASS__, 'register_scripts' ) );

		// Runs last, after any theme/optimiser that strips "?ver=" from asset URLs.
		add_filter( 'style_loader_src', array( __CLASS__, 'bust_cache' ), PHP_INT_MAX, 2 );
		add_filter( 'script_loader_src', array( __CLASS__, 'bust_cache' ), PHP_INT_MAX, 2 );
	}

	/**
	 * @param \Elementor\Elements_Manager $elements_manager Elements manager.
	 */
	public static function register_category( $elements_manager ) {
		$elements_manager->add_category(
			self::CATEGORY,
			array(
				'title' => esc_html__( 'Avix Digital', 'avix-widgets' ),
				'icon'  => 'eicon-star',
			)
		);
	}

	/**
	 * @param \Elementor\Widgets_Manager $widgets_manager Widgets manager.
	 */
	public static function register_widgets( $widgets_manager ) {
		require_once AVIX_EW_PATH . 'includes/trait-media.php';
		require_once AVIX_EW_PATH . 'includes/brand-icons.php';
		require_once AVIX_EW_PATH . 'includes/pixel-pal.php';
		foreach ( array( 'class-kit.php', 'trait-case-study-source.php' ) as $file ) {
			try {
				if ( file_exists( AVIX_EW_PATH . 'includes/case-studies/' . $file ) ) {
					require_once AVIX_EW_PATH . 'includes/case-studies/' . $file;
				}
			} catch ( \Throwable $error ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Avix case-study helper "' . $file . '" failed to load: ' . $error->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
			}
		}
		foreach ( self::$widgets as $slug => $widget ) {
			// One broken or missing widget file must not take the whole site down.
			try {
				if ( ! file_exists( AVIX_EW_PATH . $widget[0] ) ) {
					continue;
				}
				require_once AVIX_EW_PATH . $widget[0];
				$class = $widget[1];
				$widgets_manager->register( new $class() );
			} catch ( \Throwable $error ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Avix widget "' . $slug . '" failed to load: ' . $error->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
			}
		}
	}

	/**
	 * Handle => asset path (relative to the plugin root), for cache busting.
	 *
	 * @var array<string, string>
	 */
	private static $assets = array();

	public static function register_styles() {
		self::register_style( 'avix-widgets-base', 'assets/css/base.css' );
		foreach ( array_keys( self::$shared ) as $slug ) {
			self::register_style( 'avix-' . $slug, 'assets/css/' . $slug . '.css', array( 'avix-widgets-base' ) );
		}
		foreach ( self::$widgets as $slug => $widget ) {
			self::register_style( 'avix-' . $slug, 'assets/css/' . $slug . '.css', array_merge( array( 'avix-widgets-base' ), self::shared_handles( $widget ) ) );
		}
	}

	public static function register_scripts() {
		foreach ( self::$shared as $slug => $has_script ) {
			if ( $has_script ) {
				self::register_script( 'avix-' . $slug, 'assets/js/' . $slug . '.js' );
			}
		}
		foreach ( self::$widgets as $slug => $widget ) {
			$deps = array_values(
				array_filter(
					self::shared_handles( $widget ),
					static function ( $handle ) {
						return ! empty( self::$shared[ substr( $handle, 5 ) ] );
					}
				)
			);
			self::register_script( 'avix-' . $slug, 'assets/js/' . $slug . '.js', $deps );
		}
	}

	/**
	 * Handles of the shared assets a widget uses, e.g. [ 'avix-pixel-pal' ].
	 *
	 * @param array $widget Widget map entry.
	 */
	private static function shared_handles( array $widget ) {
		$handles = array();
		foreach ( (array) ( $widget[2] ?? array() ) as $slug ) {
			if ( isset( self::$shared[ $slug ] ) ) {
				$handles[] = 'avix-' . $slug;
			}
		}
		return $handles;
	}

	/**
	 * Footer scripts, deferred: they mount on DOMContentLoaded (or right away
	 * once the document is parsed) and hook Elementor through the native
	 * "elementor/frontend/init" event, so running after parsing changes
	 * nothing for them, and they no longer hold up the parser.
	 */
	private static function register_script( $handle, $relative, array $deps = array() ) {
		self::$assets[ $handle ] = $relative;
		wp_register_script(
			$handle,
			AVIX_EW_URL . $relative,
			$deps,
			self::asset_version( $relative ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	private static function register_style( $handle, $relative, array $deps = array() ) {
		self::$assets[ $handle ] = $relative;
		wp_register_style( $handle, AVIX_EW_URL . $relative, $deps, self::asset_version( $relative ) );
	}

	/**
	 * Some themes and optimisers remove "?ver=" from asset URLs. With a CDN in
	 * front, visitors then keep getting old CSS/JS after a plugin update (and
	 * phones and desktops can even get different old copies). A separate
	 * parameter that changes whenever a file changes keeps our assets fresh.
	 *
	 * @param string $src    Asset URL.
	 * @param string $handle Registered handle.
	 */
	public static function bust_cache( $src, $handle ) {
		if ( ! is_string( $src ) || '' === $src || ! isset( self::$assets[ $handle ] ) ) {
			return $src;
		}
		return add_query_arg( 'avixv', self::asset_version( self::$assets[ $handle ] ), $src );
	}

	/**
	 * Cache-busts on file change so edits show up without bumping the plugin version.
	 *
	 * @param string $relative Path relative to the plugin root.
	 */
	private static function asset_version( $relative ) {
		$mtime = @filemtime( AVIX_EW_PATH . $relative ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		return $mtime ? AVIX_EW_VERSION . '.' . $mtime : AVIX_EW_VERSION;
	}

	public static function notice_missing_elementor() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="notice notice-warning"><p>' . esc_html__( 'Avix Digital Elementor Widgets needs Elementor installed and active.', 'avix-widgets' ) . '</p></div>';
	}

	public static function notice_old_elementor() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		/* translators: %s: minimum Elementor version. */
		echo '<div class="notice notice-warning"><p>' . esc_html( sprintf( __( 'Avix Digital Elementor Widgets needs Elementor %s or newer.', 'avix-widgets' ), self::MIN_ELEMENTOR ) ) . '</p></div>';
	}
}

register_activation_hook( __FILE__, array( 'Avix_Elementor_Widgets', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Avix_Elementor_Widgets', 'deactivate' ) );

Avix_Elementor_Widgets::init();
