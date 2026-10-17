<?php
/**
 * Settings > Avix blog: the article template switch and its few options.
 *
 * Option avix_blog (array). A missing key keeps its default, so code that
 * saves a partial array never switches the template off by leaving
 * "enabled" out (the form always sends it: a hidden 0 before the checkbox).
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Blog;

defined( 'ABSPATH' ) || exit;

final class Settings {

	const OPTION = 'avix_blog';
	const GROUP  = 'avix_blog';
	const PAGE   = 'avix-blog';

	/** @var array|null */
	private static $cache = null;

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'add_option_' . self::OPTION, array( __CLASS__, 'flush' ) );
		add_action( 'update_option_' . self::OPTION, array( __CLASS__, 'flush' ) );
		add_action( 'delete_option_' . self::OPTION, array( __CLASS__, 'flush' ) );
		if ( defined( 'AVIX_EW_FILE' ) ) {
			add_filter( 'plugin_action_links_' . plugin_basename( AVIX_EW_FILE ), array( __CLASS__, 'action_links' ) );
		}
	}

	/**
	 * Defaults. "proof" is the only number on the rail card and the CTA band:
	 * Fiverr, 5.0 from 118 reviews, checked 2026-10-08. Edit it here (Settings >
	 * Avix blog) when the count changes.
	 *
	 * The table of contents lists sections (H2) only: with sub-sections the
	 * articles had 16-24 entries, most of them out of view in the rail and all
	 * of them in the way of keyboard users. H2 and H3 stay available.
	 */
	public static function defaults(): array {
		return array(
			'enabled'   => 1,
			'toc_depth' => 2,
			'rail_cta'  => 1,
			'share'     => 1,
			'related'   => 3,
			'proof'     => '5.0 rating from 118 reviews on Fiverr',
			'proof_2'   => 'Five Dutch client case studies',
		);
	}

	public static function all(): array {
		if ( null === self::$cache ) {
			$saved       = get_option( self::OPTION, array() );
			self::$cache = self::sanitize( is_array( $saved ) ? $saved : array() );
		}
		return self::$cache;
	}

	/**
	 * @param string $key Setting.
	 * @return mixed
	 */
	public static function get( string $key ) {
		$all = self::all();
		return array_key_exists( $key, $all ) ? $all[ $key ] : null;
	}

	public static function flush(): void {
		self::$cache = null;
	}

	/**
	 * The register_setting() sanitize callback, also used on read.
	 *
	 * @param mixed $value Raw option.
	 */
	public static function sanitize( $value ): array {
		$value = is_array( $value ) ? $value : array();
		$d     = self::defaults();
		$get   = static function ( string $key ) use ( $value ) {
			return isset( $value[ $key ] ) && is_scalar( $value[ $key ] ) ? $value[ $key ] : null;
		};
		$out   = array();

		foreach ( array( 'enabled', 'rail_cta', 'share' ) as $flag ) {
			$raw          = $get( $flag );
			$out[ $flag ] = null === $raw ? $d[ $flag ] : ( empty( $raw ) ? 0 : 1 );
		}

		$depth            = $get( 'toc_depth' );
		$out['toc_depth'] = in_array( (int) $depth, array( 2, 3 ), true ) ? (int) $depth : $d['toc_depth'];

		$related        = $get( 'related' );
		$out['related'] = is_numeric( $related ) ? max( 0, min( 6, (int) $related ) ) : $d['related'];

		foreach ( array( 'proof', 'proof_2' ) as $text ) {
			$raw          = $get( $text );
			$out[ $text ] = null === $raw ? $d[ $text ] : self::cap( sanitize_text_field( (string) $raw ), 120 );
		}

		return $out;
	}

	private static function cap( string $text, int $max ): string {
		return function_exists( 'mb_substr' ) ? mb_substr( $text, 0, $max ) : substr( $text, 0, $max );
	}

	/* ---------- Admin ---------- */

	public static function menu(): void {
		add_options_page(
			__( 'Avix blog', 'avix-widgets' ),
			__( 'Avix blog', 'avix-widgets' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render' )
		);
	}

	public static function register(): void {
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
	 * @param mixed $links Plugin row links.
	 */
	public static function action_links( $links ) {
		if ( is_array( $links ) && current_user_can( 'manage_options' ) ) {
			$links[] = '<a href="' . esc_url( admin_url( 'options-general.php?page=' . self::PAGE ) ) . '">' . esc_html__( 'Blog template', 'avix-widgets' ) . '</a>';
		}
		return $links;
	}

	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to manage these settings.', 'avix-widgets' ) );
		}
		$o    = self::all();
		$name = self::OPTION;
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Avix blog', 'avix-widgets' ); ?></h1>
			<p><?php esc_html_e( 'Single posts use the AvixDigital article design: dark hero, table of contents, author box, a call to action that matches the topic and related posts. The header and footer stay the theme\'s own. Nothing is saved per post, so switching the template off brings the theme\'s single post back at once (purge the page cache afterwards).', 'avix-widgets' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( self::GROUP ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Article template', 'avix-widgets' ); ?></th>
						<td>
							<input type="hidden" name="<?php echo esc_attr( $name ); ?>[enabled]" value="0">
							<label>
								<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" <?php checked( 1, (int) $o['enabled'] ); ?>>
								<?php esc_html_e( 'Use the Avix article template for single posts', 'avix-widgets' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Posts built with Elementor, password-protected posts and posts with "Use the theme template for this post" ticked in their Avix article box keep the theme\'s template.', 'avix-widgets' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="avix-blog-toc"><?php esc_html_e( 'Table of contents', 'avix-widgets' ); ?></label></th>
						<td>
							<select id="avix-blog-toc" name="<?php echo esc_attr( $name ); ?>[toc_depth]">
								<option value="2" <?php selected( 2, (int) $o['toc_depth'] ); ?>><?php esc_html_e( 'Sections only (H2, recommended)', 'avix-widgets' ); ?></option>
								<option value="3" <?php selected( 3, (int) $o['toc_depth'] ); ?>><?php esc_html_e( 'Sections and sub-sections (H2 and H3)', 'avix-widgets' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Shown when an article has three or more sections. With sub-sections, an article with more than 12 entries still lists its sections only, and screens narrower than 1200px hide the sub-sections. Phones always list the sections only.', 'avix-widgets' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Side card', 'avix-widgets' ); ?></th>
						<td>
							<input type="hidden" name="<?php echo esc_attr( $name ); ?>[rail_cta]" value="0">
							<label>
								<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[rail_cta]" value="1" <?php checked( 1, (int) $o['rail_cta'] ); ?>>
								<?php esc_html_e( 'Show the service card under the table of contents', 'avix-widgets' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Share links', 'avix-widgets' ); ?></th>
						<td>
							<input type="hidden" name="<?php echo esc_attr( $name ); ?>[share]" value="0">
							<label>
								<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[share]" value="1" <?php checked( 1, (int) $o['share'] ); ?>>
								<?php esc_html_e( 'Show LinkedIn, X, email and copy-link buttons (plain links, no third-party scripts)', 'avix-widgets' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="avix-blog-related"><?php esc_html_e( 'Related posts', 'avix-widgets' ); ?></label></th>
						<td>
							<input id="avix-blog-related" type="number" class="small-text" min="0" max="6" step="1" name="<?php echo esc_attr( $name ); ?>[related]" value="<?php echo esc_attr( (string) $o['related'] ); ?>">
							<p class="description"><?php esc_html_e( 'Same category first, then the most recent posts. 0 hides the section.', 'avix-widgets' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="avix-blog-proof"><?php esc_html_e( 'Proof line', 'avix-widgets' ); ?></label></th>
						<td>
							<input id="avix-blog-proof" type="text" class="regular-text" maxlength="120" name="<?php echo esc_attr( $name ); ?>[proof]" value="<?php echo esc_attr( (string) $o['proof'] ); ?>">
							<p class="description"><?php esc_html_e( 'On the side card and the call-to-action band. Keep it true and current (the Fiverr count changes). Empty hides it.', 'avix-widgets' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="avix-blog-proof-2"><?php esc_html_e( 'Second proof line', 'avix-widgets' ); ?></label></th>
						<td>
							<input id="avix-blog-proof-2" type="text" class="regular-text" maxlength="120" name="<?php echo esc_attr( $name ); ?>[proof_2]" value="<?php echo esc_attr( (string) $o['proof_2'] ); ?>">
							<p class="description"><?php esc_html_e( 'On the call-to-action band only. Empty hides it.', 'avix-widgets' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
