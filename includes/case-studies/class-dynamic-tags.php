<?php
/**
 * Elementor dynamic tags for case studies (SPEC-CASE-STUDIES §2.9): any stock
 * or Pro widget can bind a text, link or image to the current case study.
 * Loaded from Case_Studies only when Elementor is loaded.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Case_Studies;

defined( 'ABSPATH' ) || exit;

final class Dynamic_Tags {

	const GROUP = 'avix-case-study';

	public static function init(): void {
		add_action( 'elementor/dynamic_tags/register', array( __CLASS__, 'register' ) );
	}

	/**
	 * @param \Elementor\Core\DynamicTags\Manager $manager Dynamic tags manager.
	 */
	public static function register( $manager ): void {
		if ( ! is_object( $manager ) || ! class_exists( __NAMESPACE__ . '\Tag_Field', false ) ) {
			return;
		}
		$manager->register_group( self::GROUP, array( 'title' => esc_html__( 'Case study', 'avix-widgets' ) ) );
		$tags = array( new Tag_Field(), new Tag_Link(), new Tag_Image() );
		foreach ( $tags as $tag ) {
			if ( method_exists( $manager, 'register' ) ) {
				$manager->register( $tag );
			} elseif ( method_exists( $manager, 'register_tag' ) ) {
				$manager->register_tag( get_class( $tag ) );
			}
		}
	}

	/**
	 * Text-like FIELDS plus title and excerpt, for the field select.
	 */
	public static function text_options(): array {
		$options = array(
			'title'   => esc_html__( 'Post title', 'avix-widgets' ),
			'excerpt' => esc_html__( 'Excerpt', 'avix-widgets' ),
		);
		$groups  = array(
			'overview' => esc_html__( 'Overview', 'avix-widgets' ),
			'facts'    => esc_html__( 'Facts', 'avix-widgets' ),
			'story'    => esc_html__( 'Story', 'avix-widgets' ),
			'results'  => esc_html__( 'Results', 'avix-widgets' ),
			'stack'    => esc_html__( 'Stack', 'avix-widgets' ),
		);
		foreach ( Case_Study::FIELDS as $key => $field ) {
			if ( 'image' === $field['type'] || 'select' === $field['type'] ) {
				continue;
			}
			$label = $field['label'];
			if ( isset( $field['row'] ) ) {
				/* translators: 1: metric number, 2: part (Value, Label, Source). */
				$label = sprintf( esc_html__( 'Metric %1$d: %2$s', 'avix-widgets' ), (int) $field['row'], $field['label'] );
			}
			$options[ $key ] = $groups[ $field['group'] ] . ' · ' . $label;
		}
		return $options;
	}

	/**
	 * Plain or kses-safe text for a field of a case study.
	 *
	 * @param array  $cs  Case_Study::get() data.
	 * @param string $key Field key.
	 */
	public static function text_value( array $cs, string $key ): string {
		if ( ! $cs || ! array_key_exists( $key, $cs ) ) {
			return '';
		}
		$value = $cs[ $key ];

		if ( in_array( $key, array( 'challenge_body', 'approach_body', 'solution_body', 'outcome_body' ), true ) ) {
			$allow = Case_Study::RICH_TAGS + array( 'br' => array() );
			$out   = '';
			foreach ( (array) $value as $para ) {
				$out .= '<p>' . wp_kses( (string) $para, $allow ) . '</p>';
			}
			return $out;
		}

		if ( is_array( $value ) ) {
			$parts = array();
			foreach ( $value as $item ) {
				if ( is_array( $item ) ) {
					$item = array_filter( array_map( 'strval', array_slice( $item, 0, 2 ) ), 'strlen' );
					$item = implode( ': ', $item );
				}
				$parts[] = (string) $item;
			}
			$parts = array_filter( $parts, 'strlen' );
			$sep   = in_array( $key, array( 'services', 'constraints' ), true ) ? ', ' : "\n";
			return esc_html( implode( $sep, $parts ) );
		}

		$value = (string) $value;
		if ( ! empty( Case_Study::FIELDS[ $key ]['accent'] ) ) {
			$value = Case_Study::strip_accent( $value );
		}
		if ( 'display_title' === $key && '' === $value ) {
			$value = $cs['title'];
		}
		return nl2br( esc_html( $value ), false );
	}
}

/*
 * The tag classes extend Elementor's bases, so they are declared only when
 * those bases exist (this file is loaded on elementor/loaded).
 */
if ( class_exists( '\Elementor\Core\DynamicTags\Tag' ) && class_exists( '\Elementor\Core\DynamicTags\Data_Tag' ) ) {

	/**
	 * Text tag "Case study field".
	 */
	class Tag_Field extends \Elementor\Core\DynamicTags\Tag {

		public function get_name() {
			return 'avix-cs-field';
		}

		public function get_title() {
			return esc_html__( 'Case study field', 'avix-widgets' );
		}

		public function get_group() {
			return Dynamic_Tags::GROUP;
		}

		public function get_categories() {
			return array( 'text' );
		}

		protected function register_controls() {
			$this->add_control(
				'field',
				array(
					'label'   => esc_html__( 'Field', 'avix-widgets' ),
					'type'    => \Elementor\Controls_Manager::SELECT,
					'default' => 'client',
					'options' => Dynamic_Tags::text_options(),
				)
			);
		}

		public function render() {
			$key = (string) $this->get_settings( 'field' );
			$cs  = Case_Study::get( Case_Study::current_id() );
			echo Dynamic_Tags::text_value( $cs, $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in text_value().
		}
	}

	/**
	 * URL tag "Case study link".
	 */
	class Tag_Link extends \Elementor\Core\DynamicTags\Data_Tag {

		public function get_name() {
			return 'avix-cs-link';
		}

		public function get_title() {
			return esc_html__( 'Case study link', 'avix-widgets' );
		}

		public function get_group() {
			return Dynamic_Tags::GROUP;
		}

		public function get_categories() {
			return array( 'url' );
		}

		protected function register_controls() {
			$this->add_control(
				'link',
				array(
					'label'   => esc_html__( 'Link', 'avix-widgets' ),
					'type'    => \Elementor\Controls_Manager::SELECT,
					'default' => 'live_url',
					'options' => array(
						'live_url'  => esc_html__( 'Live site', 'avix-widgets' ),
						'permalink' => esc_html__( 'Case study page', 'avix-widgets' ),
						'index'     => esc_html__( 'All case studies', 'avix-widgets' ),
					),
				)
			);
		}

		public function get_value( array $options = array() ) {
			$which = (string) $this->get_settings( 'link' );
			if ( 'index' === $which ) {
				return Case_Study::index_url();
			}
			$cs = Case_Study::get( Case_Study::current_id() );
			if ( ! $cs ) {
				return '';
			}
			return 'permalink' === $which ? $cs['permalink'] : $cs['live_url'];
		}
	}

	/**
	 * Image tag "Case study image".
	 */
	class Tag_Image extends \Elementor\Core\DynamicTags\Data_Tag {

		public function get_name() {
			return 'avix-cs-image';
		}

		public function get_title() {
			return esc_html__( 'Case study image', 'avix-widgets' );
		}

		public function get_group() {
			return Dynamic_Tags::GROUP;
		}

		public function get_categories() {
			return array( 'image' );
		}

		protected function register_controls() {
			$this->add_control(
				'image',
				array(
					'label'   => esc_html__( 'Image', 'avix-widgets' ),
					'type'    => \Elementor\Controls_Manager::SELECT,
					'default' => 'hero_desktop',
					'options' => array(
						'hero_desktop' => esc_html__( 'Desktop screenshot', 'avix-widgets' ),
						'hero_mobile'  => esc_html__( 'Phone screenshot', 'avix-widgets' ),
						'hero_render'  => esc_html__( 'Studio render', 'avix-widgets' ),
						'card_image'   => esc_html__( 'Card image (render, featured, desktop)', 'avix-widgets' ),
						'logo'         => esc_html__( 'Client logo', 'avix-widgets' ),
						'featured'     => esc_html__( 'Card & social image (featured)', 'avix-widgets' ),
					),
				)
			);
		}

		public function get_value( array $options = array() ) {
			$which = (string) $this->get_settings( 'image' );
			$cs    = Case_Study::get( Case_Study::current_id() );
			if ( ! $cs ) {
				return array(
					'id'  => '',
					'url' => '',
				);
			}
			$key = 'featured' === $which ? 'thumbnail_id' : $which;
			$id  = isset( $cs[ $key ] ) ? (int) $cs[ $key ] : 0;
			$src = $id ? wp_get_attachment_image_src( $id, 'full' ) : false;
			return array(
				'id'  => $src ? $id : '',
				'url' => $src ? $src[0] : '',
			);
		}
	}
}
