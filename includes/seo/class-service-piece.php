<?php
/**
 * Service schema in the Yoast graph (SEO plan A5).
 *
 * A service page's Page Hero widget (Structured data > Service schema) describes the page as a
 * schema.org Service provided by the site's Organization. With Yoast SEO the Service is a graph
 * piece (@id <page>#service) and Yoast's WebPage gets mainEntity → #service; the widget then
 * prints nothing. Without Yoast (or when the graph did not carry it) the widget prints the same
 * Service as one free-standing JSON-LD block, built by build() here.
 *
 * The head runs before the widget renders, so the piece reads the Page Hero's settings from the
 * page's Elementor data (first Page Hero with the switch on, also inside an embedded template).
 *
 * Every front-end hook is a Yoast filter; the Yoast piece class loads lazily inside
 * wpseo_schema_graph_pieces, so this file is safe without Yoast.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\SEO;

defined( 'ABSPATH' ) || exit;

final class Service_Piece {

	/** The Page Hero widget's name. */
	const WIDGET = 'avix-page-hero';

	/** @var array|null The current page's Service node (array() = none), per request. */
	private static $current = null;

	/** @var bool True once the Yoast graph printed the Service on this request. */
	private static $in_graph = false;

	public static function init(): void {
		add_filter( 'wpseo_schema_graph_pieces', array( __CLASS__, 'graph_pieces' ), 12, 2 );
		add_filter( 'wpseo_schema_webpage', array( __CLASS__, 'webpage' ), 20, 2 );
	}

	/* ------------------------------------------------------------------ */
	/* Yoast                                                              */
	/* ------------------------------------------------------------------ */

	/**
	 * Adds the Service piece when the page has one (piece class loaded only here).
	 *
	 * @param mixed $pieces  Graph pieces.
	 * @param mixed $context Yoast Meta_Tags_Context.
	 * @return mixed
	 */
	public static function graph_pieces( $pieces, $context = null ) {
		if ( ! is_array( $pieces ) || ! defined( 'WPSEO_VERSION' ) || ! self::current() ) {
			return $pieces;
		}
		$file = __DIR__ . '/schema/class-service-graph-piece.php';
		if ( ! file_exists( $file ) ) {
			return $pieces;
		}
		require_once $file;
		if ( class_exists( __NAMESPACE__ . '\Service_Graph_Piece', false ) ) {
			$pieces[] = new Service_Graph_Piece( $context );
		}
		return $pieces;
	}

	/**
	 * Yoast's WebPage gets mainEntity → the Service (unless something already set one).
	 *
	 * @param mixed $data    WebPage piece.
	 * @param mixed $context Yoast Meta_Tags_Context.
	 * @return mixed
	 */
	public static function webpage( $data, $context = null ) {
		if ( ! is_array( $data ) || ! empty( $data['mainEntity'] ) ) {
			return $data;
		}
		$service = self::current();
		if ( $service ) {
			$data['mainEntity'] = array( '@id' => $service['@id'] );
		}
		return $data;
	}

	/**
	 * Called by the graph piece once it printed the Service.
	 */
	public static function mark_in_graph(): void {
		self::$in_graph = true;
	}

	/**
	 * True when Yoast's graph carries the Service on this request (the widget then prints nothing).
	 */
	public static function in_graph(): bool {
		return self::$in_graph;
	}

	/**
	 * Forgets the per-request state (tests).
	 */
	public static function reset(): void {
		self::$current  = null;
		self::$in_graph = false;
	}

	/* ------------------------------------------------------------------ */
	/* The current page                                                   */
	/* ------------------------------------------------------------------ */

	/**
	 * The Service node of the queried page (graph form), or array().
	 */
	public static function current(): array {
		if ( null !== self::$current ) {
			return self::$current;
		}
		self::$current = array();
		// A Service describes one page: on archives the queried object is a term or user.
		if ( ! is_singular() ) {
			return self::$current;
		}
		$post_id = (int) get_queried_object_id();
		$url     = $post_id ? (string) get_permalink( $post_id ) : '';
		// Nothing about a password-protected page's content goes into the head.
		if ( '' === $url || post_password_required( $post_id ) ) {
			return self::$current;
		}
		$settings = self::hero_settings( $post_id );
		if ( $settings ) {
			self::$current = self::build( $settings, $url, true );
		}
		return self::$current;
	}

	/**
	 * Display settings of the page's first Page Hero with Service schema on, or array().
	 *
	 * @param int $post_id Page ID.
	 */
	private static function hero_settings( int $post_id ): array {
		if ( ! did_action( 'elementor/loaded' ) || ! class_exists( '\Elementor\Plugin' ) ) {
			return array();
		}
		try {
			$plugin = \Elementor\Plugin::$instance;
			if ( ! $plugin || empty( $plugin->documents ) || empty( $plugin->elements_manager ) ) {
				return array();
			}
			$document = $plugin->documents->get_doc_for_frontend( $post_id );
			if ( ! $document || ! $document->is_built_with_elementor() ) {
				return array();
			}
			$element = self::find_element(
				(array) $document->get_elements_data(),
				static function ( $template_id ) use ( $plugin ) {
					$template = $plugin->documents->get( (int) $template_id );
					return $template ? (array) $template->get_elements_data() : array();
				}
			);
			if ( ! $element ) {
				return array();
			}
			$widget = $plugin->elements_manager->create_element_instance( $element );
			if ( ! $widget || ! method_exists( $widget, 'get_settings_for_display' ) ) {
				return array();
			}
			$settings = $widget->get_settings_for_display();
			return is_array( $settings ) && 'yes' === ( $settings['service_schema'] ?? '' ) ? $settings : array();
		} catch ( \Throwable $error ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Avix SEO Service piece: ' . $error->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
			return array();
		}
	}

	/**
	 * Pure helper (unit-testable): the first Page Hero element with Service schema on, walking
	 * nested elements and (up to three levels of) embedded Elementor templates.
	 *
	 * @param array         $elements      Elementor element tree.
	 * @param callable|null $load_template template_id → element tree.
	 * @param int           $depth         Template nesting depth.
	 */
	public static function find_element( array $elements, $load_template = null, int $depth = 0 ): array {
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}
			$settings = isset( $element['settings'] ) && is_array( $element['settings'] ) ? $element['settings'] : array();
			$type     = isset( $element['widgetType'] ) ? (string) $element['widgetType'] : '';
			if ( self::WIDGET === $type && 'yes' === ( $settings['service_schema'] ?? '' ) ) {
				return $element;
			}
			if ( 'template' === $type && ! empty( $settings['template_id'] ) && is_callable( $load_template ) && $depth < 3 ) {
				$found = self::find_element( (array) call_user_func( $load_template, $settings['template_id'] ), $load_template, $depth + 1 );
				if ( $found ) {
					return $found;
				}
			}
			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$found = self::find_element( $element['elements'], $load_template, $depth );
				if ( $found ) {
					return $found;
				}
			}
		}
		return array();
	}

	/* ------------------------------------------------------------------ */
	/* The Service node                                                   */
	/* ------------------------------------------------------------------ */

	/**
	 * The Service node (no @context) from Page Hero display settings: name (the Service name,
	 * else the headline without [brackets]), serviceType, description (else the lead text), url,
	 * image (the hero image, else the page's featured image), areaServed (one line each: Country
	 * for a country name, Place otherwise), provider → #organization and mainEntityOfPage.
	 *
	 * @param array  $s     Page Hero settings (title = headline).
	 * @param string $url   Page URL.
	 * @param bool   $graph True for the Yoast graph (references), false for a free-standing block.
	 */
	public static function build( array $s, string $url, bool $graph = true ): array {
		$name = self::plain( $s['service_name'] ?? '' );
		$name = '' !== $name ? $name : self::plain( $s['title'] ?? '' );
		if ( '' === $url || '' === $name ) {
			return array();
		}
		$service = array(
			'@type' => 'Service',
			'@id'   => $url . '#service',
			'name'  => $name,
		);
		$type    = self::plain( $s['service_type'] ?? '' );
		if ( '' !== $type ) {
			$service['serviceType'] = $type;
		}
		$text = self::plain( $s['service_description'] ?? '' );
		$text = '' !== $text ? $text : self::plain( $s['text'] ?? '' );
		if ( '' !== $text ) {
			$service['description'] = $text;
		}
		$service['url'] = $url;
		$image          = self::image_url( $s );
		if ( '' !== $image ) {
			$service['image'] = $image;
		}
		$areas = array();
		foreach ( preg_split( '/\r\n|\r|\n/', (string) ( $s['area_served'] ?? '' ) ) as $line ) {
			$line = self::plain( $line );
			if ( '' === $line ) {
				continue;
			}
			$areas[] = class_exists( __NAMESPACE__ . '\Entity' ) ? Entity::area_node( $line ) : array(
				'@type' => 'Place',
				'name'  => $line,
			);
		}
		if ( $areas ) {
			$service['areaServed'] = $areas;
		}
		$service['provider']         = array( '@id' => trailingslashit( home_url() ) . '#organization' );
		$service['mainEntityOfPage'] = $graph ? array( '@id' => $url ) : $url;
		return $service;
	}

	/**
	 * The hero image URL (Image visual only), else the queried page's featured image, else ''.
	 *
	 * @param array $s Page Hero settings.
	 */
	private static function image_url( array $s ): string {
		$url = '';
		if ( 'image' === ( $s['visual'] ?? '' ) ) {
			$media = isset( $s['image'] ) && is_array( $s['image'] ) ? $s['image'] : array();
			$id    = absint( $media['id'] ?? 0 );
			$url   = $id ? (string) wp_get_attachment_image_url( $id, 'full' ) : '';
			if ( '' === $url && ! empty( $media['url'] ) && is_string( $media['url'] ) ) {
				$url = $media['url'];
			}
		}
		if ( '' === $url && function_exists( 'get_the_post_thumbnail_url' ) && is_singular() ) {
			$url = (string) get_the_post_thumbnail_url( get_queried_object_id(), 'full' );
		}
		$url = '' !== $url ? esc_url_raw( $url, array( 'http', 'https' ) ) : '';
		return preg_match( '#^https?://[^/\s]+#i', $url ) ? $url : '';
	}

	/**
	 * Plain text for JSON-LD: no tags or [accent] brackets, entities decoded, whitespace folded.
	 *
	 * @param mixed $text Raw text.
	 */
	public static function plain( $text ): string {
		$text = html_entity_decode( wp_strip_all_tags( str_replace( array( '[', ']' ), '', is_scalar( $text ) ? (string) $text : '' ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
	}
}
