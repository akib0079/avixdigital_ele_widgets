<?php
/**
 * Shared "Case study" source controls for the case-study widgets: pick a case
 * study (or follow the page automatically), read its data once per widget, and
 * let any filled-in control override the meta on this page only (SPEC §1.5).
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Case_Studies;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

trait Source {

	/** Memoised case-study data for this widget instance. */
	private $avix_cs_data = null;

	/** Key the memo was built for, so a changed cs_post re-reads. */
	private $avix_cs_key = null;

	/**
	 * Content section "Case study": the source picker plus a short note.
	 */
	protected function controls_source(): void {
		$this->start_controls_section(
			'section_source',
			array(
				'label' => esc_html__( 'Case study', 'avix-widgets' ),
			)
		);

		$this->add_control(
			'cs_post',
			array(
				'label'       => esc_html__( 'Show', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => self::cs_source_options(),
				'label_block' => true,
				'description' => esc_html__( 'Automatic follows the case study this page belongs to. Pick one to show it anywhere, for example on a landing page.', 'avix-widgets' ),
			)
		);

		$this->add_control(
			'cs_source_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Every field below is empty by default and fills itself from the case study. Type in a field to override it on this page only.', 'avix-widgets' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The case study this widget shows (Case_Study::get() array), memoised per instance.
	 */
	protected function cs(): array {
		$s      = $this->get_settings_for_display();
		$picked = isset( $s['cs_post'] ) && is_scalar( $s['cs_post'] ) ? (string) $s['cs_post'] : '';
		if ( null === $this->avix_cs_data || $this->avix_cs_key !== $picked ) {
			$this->avix_cs_key  = $picked;
			$this->avix_cs_data = Case_Study::get( Case_Study::current_id( $picked ) );
		}
		return $this->avix_cs_data;
	}

	/**
	 * The trimmed control value when it is filled in, else $fallback.
	 *
	 * @param array  $s        Widget settings.
	 * @param string $control  Control name.
	 * @param mixed  $fallback Value from the case study.
	 */
	protected function pick( array $s, string $control, $fallback ): string {
		$value = isset( $s[ $control ] ) && is_scalar( $s[ $control ] ) ? trim( (string) $s[ $control ] ) : '';
		if ( '' !== $value ) {
			return $value;
		}
		return is_scalar( $fallback ) ? (string) $fallback : '';
	}

	/**
	 * An info box, only while editing in Elementor.
	 */
	protected function cs_alert( string $msg ): void {
		if ( ! Case_Study::is_editor() ) {
			return;
		}
		echo '<div class="elementor-alert elementor-alert-info">' . esc_html( $msg ) . '</div>';
	}

	/**
	 * Picker options: automatic plus up to 100 published case studies.
	 */
	private static function cs_source_options(): array {
		static $options = null;
		if ( null !== $options ) {
			return $options;
		}
		$options = array( '' => esc_html__( 'This page\'s case study (automatic)', 'avix-widgets' ) );
		$ids     = get_posts(
			array(
				'post_type'        => Case_Study::POST_TYPE,
				'post_status'      => 'publish',
				'posts_per_page'   => 100,
				'orderby'          => array(
					'menu_order' => 'ASC',
					'date'       => 'DESC',
				),
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => true,
			)
		);
		foreach ( $ids as $id ) {
			$title                    = Case_Study::plain( get_the_title( $id ) );
			$options[ (string) $id ] = '' !== $title ? $title : sprintf( '#%d', $id );
		}
		return $options;
	}
}
