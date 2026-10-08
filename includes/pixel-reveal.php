<?php
/**
 * Site-wide pixel reveal: the case-study pixel resolve for content images.
 *
 * Each content image arrives behind a layer of 16px squares in the colour of
 * the section it sits on, which clear in a random order once the image is
 * scrolled to (assets/js/pixel-reveal.js). Front end only: never in wp-admin,
 * the Elementor editor or its preview. Images are never hidden by CSS, so
 * without JS (or with reduced motion) they simply show. One deferred script
 * and no stylesheet: the two CSS rules it needs are added by the script.
 *
 * Settings > Avix pixel reveal. Filters:
 * - avix_pixel_reveal_enabled (bool $enabled, array $options): load it on this request?
 * - avix_pixel_reveal_config (array $config, array $options): the JS config.
 *
 * Opt an image (or a whole section) out with the class "no-pixel-reveal".
 * A section whose background is a photo or a video is skipped; set
 * --avix-pxr-cover on it to give the squares a colour instead.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets;

defined( 'ABSPATH' ) || exit;

final class Pixel_Reveal {

	const OPTION = 'avix_pixel_reveal';
	const GROUP  = 'avix_pixel_reveal';
	const PAGE   = 'avix-pixel-reveal';
	const HANDLE = 'avix-pixel-reveal';
	const JS     = 'assets/js/pixel-reveal.js';

	/** Where content images are looked for ("Content images on every page"). */
	const SCOPE_CONTENT = 'main, .elementor, .page_content_wrap, .post_content, .content';

	/** "Avix widgets only": inside the plugin's own widgets. */
	const SCOPE_WIDGETS = '[class*="elementor-widget-avix-"]';

	/**
	 * Never revealed (an image matching, or inside, one of these).
	 * Site <header>/<footer> landmarks, [class*="logo"], [class*="marquee"],
	 * [class*="ticker"], [class*="avatar"] and SVG files are excluded in JS.
	 */
	const EXCLUDE = array(
		// Site header and footer.
		'.top_panel',
		'.footer_wrap',
		'[data-elementor-type="header"]',
		'[data-elementor-type="footer"]',
		'.elementor-location-header',
		'.elementor-location-footer',
		'.sc_layouts_row',
		'.menu_mobile',
		'#wpadminbar',
		'.avix-sh',
		'.avix-ft',
		// Logos and icons.
		'.custom-logo',
		'.custom-logo-link',
		'.avix-cl',
		'.avix-work__platform',
		'.wp-smiley',
		'.emoji',
		// Avatars.
		'.avatar',
		// The pixel character.
		'.avix-pal',
		// Sliders and marquees that move continuously.
		'.swiper',
		'.swiper-container',
		'.slick-slider',
		'.owl-carousel',
		'.flickity-enabled',
		'.splide',
		'.avix-tk',
		// Popups and lightboxes.
		'.elementor-lightbox',
		'.dialog-widget',
		'.elementor-popup-modal',
		// Already revealed by the case-study kit; the case-study hero render
		// is the LCP image and must never be covered.
		'[data-csk-pixels]',
		'.avix-csh',
		'.avix-csh-strip',
		// Opt-out.
		'.no-pixel-reveal',
	);

	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 20 );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_filter( 'script_loader_src', array( __CLASS__, 'bust_cache' ), PHP_INT_MAX, 2 );
		if ( defined( 'AVIX_EW_FILE' ) ) {
			add_filter( 'plugin_action_links_' . plugin_basename( AVIX_EW_FILE ), array( __CLASS__, 'action_links' ) );
		}
	}

	/* ---------- Options ---------- */

	public static function defaults(): array {
		return array(
			'enabled'    => 1,
			'scope'      => 'content',
			'min_width'  => 140,
			'min_height' => 100,
			'exclude'    => '',
			'square'     => 16,
		);
	}

	public static function options(): array {
		$saved = get_option( self::OPTION, array() );
		return self::sanitize( is_array( $saved ) ? $saved : array() );
	}

	/**
	 * The register_setting() sanitize callback, so it runs for the settings
	 * form and for every update_option() call alike (WordPress passes only
	 * the value). A missing key keeps its default: the form always sends
	 * "enabled" (a hidden 0 before the checkbox), so code that saves a
	 * partial array never switches the reveal off by leaving it out.
	 * Anything that is not a plain value (a crafted avix_pixel_reveal[x][])
	 * is ignored.
	 *
	 * @param mixed $value Raw option (the settings form, update_option() or the database).
	 */
	public static function sanitize( $value ): array {
		$value = is_array( $value ) ? $value : array();
		$d     = self::defaults();
		$out   = array();
		$get   = static function ( string $key ) use ( $value ) {
			return isset( $value[ $key ] ) && is_scalar( $value[ $key ] ) ? $value[ $key ] : null;
		};

		$enabled        = $get( 'enabled' );
		$out['enabled'] = null === $enabled ? $d['enabled'] : ( empty( $enabled ) ? 0 : 1 );

		$scope        = null === $get( 'scope' ) ? $d['scope'] : sanitize_key( (string) $get( 'scope' ) );
		$out['scope'] = in_array( $scope, array( 'content', 'widgets', 'case_studies' ), true ) ? $scope : $d['scope'];

		$out['min_width']  = is_numeric( $get( 'min_width' ) ) ? max( 0, min( 2000, (int) $get( 'min_width' ) ) ) : $d['min_width'];
		$out['min_height'] = is_numeric( $get( 'min_height' ) ) ? max( 0, min( 2000, (int) $get( 'min_height' ) ) ) : $d['min_height'];
		$out['square']     = is_numeric( $get( 'square' ) ) ? max( 8, min( 48, (int) $get( 'square' ) ) ) : $d['square'];
		$out['exclude']    = implode( "\n", self::selectors( (string) $get( 'exclude' ) ) );

		return $out;
	}

	/**
	 * Extra exclusion selectors: one per line, CSS selector characters only.
	 * The browser drops any that are still invalid.
	 *
	 * @param string $raw Textarea value.
	 * @return string[]
	 */
	public static function selectors( string $raw ): array {
		$out = array();
		foreach ( preg_split( '/\r\n|\r|\n/', wp_strip_all_tags( $raw ) ) as $line ) {
			$line = preg_replace( '/[^A-Za-z0-9 _\-\.#\[\]="\'\*\^\$\|~:\(\)>\+,]/', '', (string) $line );
			$line = trim( preg_replace( '/\s+/', ' ', (string) $line ) );
			if ( '' !== $line && strlen( $line ) <= 200 ) {
				$out[] = $line;
			}
			if ( count( $out ) >= 40 ) {
				break;
			}
		}
		return array_values( array_unique( $out ) );
	}

	/* ---------- Front end ---------- */

	/** True in the Elementor editor and its preview iframe. */
	private static function is_elementor_editor(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only context check.
		if ( isset( $_GET['elementor-preview'] ) || ( isset( $_GET['elementor_library'] ) && isset( $_GET['preview'] ) ) ) {
			return true;
		}
		if ( ! did_action( 'elementor/loaded' ) || ! class_exists( '\Elementor\Plugin' ) || ! isset( \Elementor\Plugin::$instance ) ) {
			return false;
		}
		$elementor = \Elementor\Plugin::$instance;
		if ( isset( $elementor->preview ) && is_object( $elementor->preview ) && method_exists( $elementor->preview, 'is_preview_mode' ) && $elementor->preview->is_preview_mode() ) {
			return true;
		}
		if ( isset( $elementor->editor ) && is_object( $elementor->editor ) && method_exists( $elementor->editor, 'is_edit_mode' ) && $elementor->editor->is_edit_mode() ) {
			return true;
		}
		return false;
	}

	/** A case study, the /case-studies/ index page or a case-study taxonomy. */
	private static function is_case_study_view(): bool {
		$type = 'avix_case_study';
		if ( class_exists( '\AvixWidgets\Case_Studies\Case_Study' ) ) {
			$type  = \AvixWidgets\Case_Studies\Case_Study::POST_TYPE;
			$index = \AvixWidgets\Case_Studies\Case_Study::index_page_id();
			if ( $index && is_page( $index ) ) {
				return true;
			}
		}
		return is_singular( $type ) || is_post_type_archive( $type ) || is_tax( array( 'avix_cs_service', 'avix_cs_industry' ) );
	}

	public static function enqueue() {
		if ( is_admin() || wp_doing_ajax() || is_feed() || is_embed() || is_customize_preview() || self::is_elementor_editor() ) {
			return;
		}
		if ( function_exists( 'amp_is_request' ) && amp_is_request() ) {
			return;
		}

		$o       = self::options();
		$enabled = ! empty( $o['enabled'] );
		if ( $enabled && 'case_studies' === $o['scope'] && ! self::is_case_study_view() ) {
			$enabled = false;
		}
		/**
		 * Whether the pixel reveal loads on this request.
		 *
		 * @param bool  $enabled
		 * @param array $options Saved settings.
		 */
		if ( ! apply_filters( 'avix_pixel_reveal_enabled', $enabled, $o ) ) {
			return;
		}

		$config = array(
			'scope'      => 'widgets' === $o['scope'] ? self::SCOPE_WIDGETS : self::SCOPE_CONTENT,
			'exclude'    => array_values( array_merge( self::EXCLUDE, self::selectors( $o['exclude'] ) ) ),
			'minWidth'   => (int) $o['min_width'],
			'minHeight'  => (int) $o['min_height'],
			'square'     => (int) $o['square'],
			'spread'     => 420,
			'fade'       => 220,
			'wait'       => 2500,
			'settle'     => 1200,
			'hold'       => 2000,
			'maxActive'  => 6,
			'margin'     => '50%',
		);
		/**
		 * The pixel reveal's JS config (window.avixPixelRevealConfig).
		 *
		 * @param array $config  scope, exclude[], minWidth, minHeight, square, spread, fade, wait, settle, hold, maxActive, margin.
		 * @param array $options Saved settings.
		 */
		$config = apply_filters( 'avix_pixel_reveal_config', $config, $o );
		if ( ! is_array( $config ) ) {
			return;
		}

		wp_enqueue_script(
			self::HANDLE,
			AVIX_EW_URL . self::JS,
			array(),
			self::version( self::JS ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_add_inline_script(
			self::HANDLE,
			'window.avixPixelRevealConfig=' . wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES ) . ';',
			'before'
		);
		// No stylesheet: every style the canvas needs is set inline by the
		// script, which also adds the reduced-motion and print rules. (A
		// stylesheet printed with the footer scripts would hold them back,
		// Elementor's frontend and DOMContentLoaded included.)
	}

	private static function version( string $relative ): string {
		$base  = defined( 'AVIX_EW_VERSION' ) ? AVIX_EW_VERSION : '1';
		$mtime = @filemtime( AVIX_EW_PATH . $relative ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		return $mtime ? $base . '.' . $mtime : $base;
	}

	/**
	 * Keeps the script fresh behind themes/optimisers that strip "?ver=" (the
	 * same "avixv" parameter the widgets' assets get).
	 *
	 * @param string $src    Asset URL.
	 * @param string $handle Handle.
	 */
	public static function bust_cache( $src, $handle ) {
		if ( self::HANDLE !== $handle || ! is_string( $src ) || '' === $src ) {
			return $src;
		}
		return add_query_arg( 'avixv', self::version( self::JS ), $src );
	}

	/* ---------- Settings ---------- */

	public static function menu() {
		add_options_page(
			__( 'Avix pixel reveal', 'avix-widgets' ),
			__( 'Avix pixel reveal', 'avix-widgets' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render_settings' )
		);
	}

	public static function register_settings() {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'default'           => self::defaults(),
				'show_in_rest'      => false,
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
			)
		);
	}

	/**
	 * @param string[] $links Plugin row links.
	 */
	public static function action_links( $links ) {
		if ( current_user_can( 'manage_options' ) && is_array( $links ) ) {
			$links[] = '<a href="' . esc_url( admin_url( 'options-general.php?page=' . self::PAGE ) ) . '">' . esc_html__( 'Pixel reveal', 'avix-widgets' ) . '</a>';
		}
		return $links;
	}

	public static function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to manage these settings.', 'avix-widgets' ) );
		}
		$o    = self::options();
		$name = self::OPTION;
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Avix pixel reveal', 'avix-widgets' ); ?></h1>
			<p><?php esc_html_e( 'Images arrive behind a layer of small squares in the colour of the section they sit on, which clear in a random order when the image is scrolled to (the case-study reveal). Images already on screen when the page opens are never covered, and visitors who prefer reduced motion see the images straight away.', 'avix-widgets' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( self::GROUP ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Pixel reveal', 'avix-widgets' ); ?></th>
						<td>
							<input type="hidden" name="<?php echo esc_attr( $name ); ?>[enabled]" value="0">
							<label>
								<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" <?php checked( 1, (int) $o['enabled'] ); ?>>
								<?php esc_html_e( 'Reveal images with the pixel squares', 'avix-widgets' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="avix-pxr-scope"><?php esc_html_e( 'Where', 'avix-widgets' ); ?></label></th>
						<td>
							<select id="avix-pxr-scope" name="<?php echo esc_attr( $name ); ?>[scope]">
								<option value="content" <?php selected( 'content', $o['scope'] ); ?>><?php esc_html_e( 'Content images on every page', 'avix-widgets' ); ?></option>
								<option value="widgets" <?php selected( 'widgets', $o['scope'] ); ?>><?php esc_html_e( 'Avix widgets only', 'avix-widgets' ); ?></option>
								<option value="case_studies" <?php selected( 'case_studies', $o['scope'] ); ?>><?php esc_html_e( 'Case studies only', 'avix-widgets' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'The header, footer, logos, avatars, sliders and marquees are always left alone, as are the case-study screenshots that already have their own reveal.', 'avix-widgets' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Minimum size', 'avix-widgets' ); ?></th>
						<td>
							<label>
								<input type="number" class="small-text" min="0" max="2000" step="1" name="<?php echo esc_attr( $name ); ?>[min_width]" value="<?php echo esc_attr( (string) $o['min_width'] ); ?>">
								<?php esc_html_e( 'px wide', 'avix-widgets' ); ?>
							</label>
							&nbsp;
							<label>
								<input type="number" class="small-text" min="0" max="2000" step="1" name="<?php echo esc_attr( $name ); ?>[min_height]" value="<?php echo esc_attr( (string) $o['min_height'] ); ?>">
								<?php esc_html_e( 'px tall', 'avix-widgets' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Smaller images (icons, small portraits) show without the reveal. Measured as the image appears on screen.', 'avix-widgets' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="avix-pxr-square"><?php esc_html_e( 'Square size', 'avix-widgets' ); ?></label></th>
						<td>
							<input id="avix-pxr-square" type="number" class="small-text" min="8" max="48" step="1" name="<?php echo esc_attr( $name ); ?>[square]" value="<?php echo esc_attr( (string) $o['square'] ); ?>"> px
							<p class="description"><?php esc_html_e( 'The case studies use 16.', 'avix-widgets' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="avix-pxr-exclude"><?php esc_html_e( 'Also leave out', 'avix-widgets' ); ?></label></th>
						<td>
							<textarea id="avix-pxr-exclude" class="large-text code" rows="5" name="<?php echo esc_attr( $name ); ?>[exclude]" placeholder=".my-gallery&#10;#hero img"><?php echo esc_textarea( $o['exclude'] ); ?></textarea>
							<p class="description">
								<?php esc_html_e( 'CSS selectors, one per line: matching images, and images inside matching elements, show without the reveal.', 'avix-widgets' ); ?>
								<?php
								printf(
									/* translators: 1: class name, 2: CSS custom property. */
									esc_html__( 'In Elementor, add the class %1$s (Advanced > CSS Classes) to any widget or section to leave it out. A section with a photo or video background is skipped unless it sets %2$s to a colour.', 'avix-widgets' ),
									'<code>no-pixel-reveal</code>',
									'<code>--avix-pxr-cover</code>'
								);
								?>
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
