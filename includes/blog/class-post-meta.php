<?php
/**
 * Per-post settings for the article template, in an "Avix article" box on
 * the post screen (block and classic editor):
 *
 * - _avix_article_service: which service the side card and the CTA band point
 *   to ('' = from the categories and tags).
 * - _avix_article_template: 'off' keeps the theme's single post template for
 *   this post.
 *
 * Both are registered for the REST API, so an importer can set them.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Blog;

defined( 'ABSPATH' ) || exit;

final class Post_Meta {

	const NONCE = 'avix_article_meta';

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register' ) );
		if ( is_admin() ) {
			add_action( 'add_meta_boxes_post', array( __CLASS__, 'add_box' ) );
			add_action( 'save_post_post', array( __CLASS__, 'save' ), 10, 2 );
		}
	}

	/** Allowed values for the service meta ('' = automatic). */
	public static function service_keys(): array {
		return array( '', 'shopify', 'wordpress', 'web-apps', 'uiux', 'general' );
	}

	/**
	 * @param mixed $value Raw value.
	 */
	public static function sanitize_service( $value ): string {
		$value = sanitize_key( is_scalar( $value ) ? (string) $value : '' );
		return in_array( $value, self::service_keys(), true ) ? $value : '';
	}

	/**
	 * @param mixed $value Raw value.
	 */
	public static function sanitize_template( $value ): string {
		return 'off' === ( is_scalar( $value ) ? (string) $value : '' ) ? 'off' : '';
	}

	public static function register(): void {
		$auth = static function ( $allowed, $meta_key, $post_id ) {
			return current_user_can( 'edit_post', (int) $post_id );
		};
		register_post_meta(
			'post',
			Blog::META_SERVICE,
			array(
				'single'            => true,
				'type'              => 'string',
				'default'           => '',
				'description'       => 'Avix article: the service the calls to action point to (shopify, wordpress, web-apps, uiux, general; empty = automatic).',
				'sanitize_callback' => array( __CLASS__, 'sanitize_service' ),
				'auth_callback'     => $auth,
				'show_in_rest'      => true,
			)
		);
		register_post_meta(
			'post',
			Blog::META_TEMPLATE,
			array(
				'single'            => true,
				'type'              => 'string',
				'default'           => '',
				'description'       => 'Avix article: "off" keeps the theme single post template for this post.',
				'sanitize_callback' => array( __CLASS__, 'sanitize_template' ),
				'auth_callback'     => $auth,
				'show_in_rest'      => true,
			)
		);
	}

	public static function add_box(): void {
		add_meta_box( 'avix-article', __( 'Avix article', 'avix-widgets' ), array( __CLASS__, 'render' ), 'post', 'side', 'default' );
	}

	/**
	 * @param \WP_Post $post Post.
	 */
	public static function render( $post ): void {
		if ( ! $post instanceof \WP_Post ) {
			return;
		}
		$service  = self::sanitize_service( get_post_meta( $post->ID, Blog::META_SERVICE, true ) );
		$template = self::sanitize_template( get_post_meta( $post->ID, Blog::META_TEMPLATE, true ) );
		$labels   = array(
			''          => __( 'Automatic (from categories and tags)', 'avix-widgets' ),
			'shopify'   => __( 'Shopify development', 'avix-widgets' ),
			'wordpress' => __( 'WordPress & WooCommerce', 'avix-widgets' ),
			'web-apps'  => __( 'Web apps & Webflow', 'avix-widgets' ),
			'uiux'      => __( 'UI/UX & brand design', 'avix-widgets' ),
			'general'   => __( 'All services', 'avix-widgets' ),
		);
		wp_nonce_field( self::NONCE, self::NONCE . '_nonce' );
		?>
		<p>
			<label for="avix-article-service"><strong><?php esc_html_e( 'Call to action', 'avix-widgets' ); ?></strong></label><br>
			<select id="avix-article-service" name="avix_article_service" style="width:100%">
				<?php foreach ( $labels as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $service, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<span class="description"><?php esc_html_e( 'The service the side card and the end band point to.', 'avix-widgets' ); ?></span>
		</p>
		<p>
			<label>
				<input type="checkbox" name="avix_article_template_off" value="1" <?php checked( 'off', $template ); ?>>
				<?php esc_html_e( 'Use the theme template for this post', 'avix-widgets' ); ?>
			</label>
		</p>
		<?php
	}

	/**
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post.
	 */
	public static function save( $post_id, $post ): void {
		$post_id = (int) $post_id;
		if ( ! isset( $_POST[ self::NONCE . '_nonce' ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE . '_nonce' ] ) ), self::NONCE ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$service = isset( $_POST['avix_article_service'] ) ? self::sanitize_service( wp_unslash( $_POST['avix_article_service'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitize_service().
		if ( '' === $service ) {
			delete_post_meta( $post_id, Blog::META_SERVICE );
		} else {
			update_post_meta( $post_id, Blog::META_SERVICE, $service );
		}
		if ( ! empty( $_POST['avix_article_template_off'] ) ) {
			update_post_meta( $post_id, Blog::META_TEMPLATE, 'off' );
		} else {
			delete_post_meta( $post_id, Blog::META_TEMPLATE );
		}
	}
}
