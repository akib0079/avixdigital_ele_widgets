<?php
/**
 * Plugin Name:       Avix Digital Elementor Widgets
 * Plugin URI:        https://avixdigital.com
 * Description:       Custom Elementor widgets for avixdigital.com: Hero Banner, Services Showcase, Selected Work (scroll stack), Impact Numbers, Testimonial Stack, Site Footer, Process Timeline, FAQ & Quote and Compare & CEO Quote.
 * Version:           1.4.0
 * Author:            Avix Digital
 * Author URI:        https://avixdigital.com
 * Text Domain:       avix-widgets
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Requires Plugins:  elementor
 * Elementor tested up to: 4.3.2
 *
 * @package AvixWidgets
 */

defined( 'ABSPATH' ) || exit;

define( 'AVIX_EW_VERSION', '1.4.0' );
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
	 * Widget slug => [ file, class ]. New widgets only need an entry here.
	 *
	 * @var array<string, array{0:string,1:string}>
	 */
	private static $widgets = array(
		'hero'              => array( 'includes/widgets/class-hero.php', '\AvixWidgets\Widgets\Hero' ),
		'services'          => array( 'includes/widgets/class-services.php', '\AvixWidgets\Widgets\Services' ),
		'selected-work'     => array( 'includes/widgets/class-selected-work.php', '\AvixWidgets\Widgets\Selected_Work' ),
		'impact-numbers'    => array( 'includes/widgets/class-impact-numbers.php', '\AvixWidgets\Widgets\Impact_Numbers' ),
		'testimonial-stack' => array( 'includes/widgets/class-testimonial-stack.php', '\AvixWidgets\Widgets\Testimonial_Stack' ),
		'site-footer'       => array( 'includes/widgets/class-site-footer.php', '\AvixWidgets\Widgets\Site_Footer' ),
		'process-timeline'  => array( 'includes/widgets/class-process-timeline.php', '\AvixWidgets\Widgets\Process_Timeline' ),
		'faq'               => array( 'includes/widgets/class-faq.php', '\AvixWidgets\Widgets\Faq' ),
		'compare-quote'     => array( 'includes/widgets/class-compare-quote.php', '\AvixWidgets\Widgets\Compare_Quote' ),
	);

	public static function init() {
		add_action( 'plugins_loaded', array( __CLASS__, 'boot' ) );
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

		add_action( 'elementor/elements/categories_registered', array( __CLASS__, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( __CLASS__, 'register_widgets' ) );
		add_action( 'elementor/frontend/after_register_styles', array( __CLASS__, 'register_styles' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( __CLASS__, 'register_scripts' ) );
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
		foreach ( self::$widgets as $widget ) {
			require_once AVIX_EW_PATH . $widget[0];
			$class = $widget[1];
			$widgets_manager->register( new $class() );
		}
	}

	public static function register_styles() {
		foreach ( array_keys( self::$widgets ) as $slug ) {
			wp_register_style( 'avix-' . $slug, AVIX_EW_URL . 'assets/css/' . $slug . '.css', array(), self::asset_version( 'assets/css/' . $slug . '.css' ) );
		}
	}

	public static function register_scripts() {
		foreach ( array_keys( self::$widgets ) as $slug ) {
			wp_register_script( 'avix-' . $slug, AVIX_EW_URL . 'assets/js/' . $slug . '.js', array(), self::asset_version( 'assets/js/' . $slug . '.js' ), true );
		}
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

Avix_Elementor_Widgets::init();
