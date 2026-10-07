<?php
/**
 * Case Study Results: the dark "04 · The impact" chapter. A large outcome
 * statement with an orange highlight, the outcome story, short pillars,
 * the build in numbers (each figure printed with its source, counting up
 * from zero as it comes into view), links visitors can use to check the
 * work on the live site, and a client quote when a real one exists.
 * Incomplete figures and empty quotes never render.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;

defined( 'ABSPATH' ) || exit;

class Case_Study_Results extends Widget_Base {

	use \AvixWidgets\Case_Studies\Source;

	const FOOTNOTE = 'These figures describe the build and were observed on the live site on the date shown. We only publish client revenue or conversion figures with the client\'s permission.';

	public function get_name(): string {
		return 'avix-case-study-results';
	}

	public function get_title(): string {
		return esc_html__( 'Case Study Results', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-counter';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'case study', 'portfolio', 'results', 'impact', 'outcome', 'metrics', 'numbers', 'proof', 'quote', 'avix' );
	}

	public function get_style_depends(): array {
		return array( 'avix-case-study-results' );
	}

	public function get_script_depends(): array {
		return array( 'avix-case-study-results' );
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	protected function is_dynamic_content(): bool {
		return true;
	}

	/* ------------------------------------------------------------------ */
	/* Controls                                                            */
	/* ------------------------------------------------------------------ */

	protected function register_controls(): void {
		$this->controls_source();
		$this->controls_content();
		$this->controls_metrics();
		$this->controls_proof();
		$this->controls_quote();
		$this->controls_style();
		$this->controls_type();
	}

	private function controls_content() {
		$this->start_controls_section( 'section_content', array( 'label' => esc_html__( 'Impact', 'avix-widgets' ) ) );

		$this->add_control(
			'number',
			array(
				'label'   => esc_html__( 'Number', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '04',
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'label',
			array(
				'label'       => esc_html__( 'Label', 'avix-widgets' ),
				'description' => esc_html__( 'Next to the number. When it matches the headline, only the number shows, so the words are not repeated.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'The impact', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'eyebrow_tag',
			array(
				'label'       => esc_html__( 'Tag', 'avix-widgets' ),
				'description' => esc_html__( 'A small pill under the headline. Leave empty for the case study’s card label and value, e.g. “Designed for: Faster discovery”.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__( 'From case study: card label: card value', 'avix-widgets' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Headline', 'avix-widgets' ),
				'description' => esc_html__( 'Leave empty for “The impact”. Wrap words in [brackets] to highlight them in orange.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => '',
				'placeholder' => esc_html__( 'The impact', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'   => esc_html__( 'Headline tag', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h2',
				'options' => array(
					'h2'  => 'H2',
					'h3'  => 'H3',
					'div' => 'div',
					'p'   => 'p',
				),
			)
		);

		$this->add_control(
			'statement',
			array(
				'label'       => esc_html__( 'Statement', 'avix-widgets' ),
				'description' => esc_html__( 'The big line. Wrap words in [brackets] to highlight them in orange. Leave empty to use the case study’s outcome statement.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 3,
				'default'     => '',
				'placeholder' => esc_html__( 'From case study: outcome statement', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'body',
			array(
				'label'       => esc_html__( 'Text', 'avix-widgets' ),
				'description' => esc_html__( 'Paragraphs separated by a blank line. Links, <strong> and <em> are kept. Leave empty to use the case study.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 6,
				'default'     => '',
				'placeholder' => esc_html__( 'From case study: outcome text', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'pillars',
			array(
				'label'       => esc_html__( 'Pillars', 'avix-widgets' ),
				'description' => esc_html__( 'One short line each, up to 4. Leave empty to use the case study.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 4,
				'default'     => '',
				'placeholder' => esc_html__( 'From case study: outcome pillars', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'anchor',
			array(
				'label'       => esc_html__( 'Anchor', 'avix-widgets' ),
				'description' => esc_html__( 'The section’s #id. Leave empty for #impact.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => 'impact',
				'separator'   => 'before',
			)
		);

		$this->end_controls_section();
	}

	private function controls_metrics() {
		$this->start_controls_section( 'section_metrics', array( 'label' => esc_html__( 'Numbers', 'avix-widgets' ) ) );

		$this->add_control(
			'honesty_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'A figure shows only with its value, label and source. Never publish client revenue or conversion figures without the client’s written permission.', 'avix-widgets' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->add_control(
			'show_metrics',
			array(
				'label'   => esc_html__( 'Show numbers', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'metrics_title',
			array(
				'label'     => esc_html__( 'Heading', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'The build in numbers', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_metrics' => 'yes' ),
			)
		);

		$this->add_control(
			'metrics_source',
			array(
				'label'       => esc_html__( 'Numbers from', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'auto',
				'options'     => array(
					'auto'   => esc_html__( 'The case study', 'avix-widgets' ),
					'manual' => esc_html__( 'The list below', 'avix-widgets' ),
				),
				'condition'   => array( 'show_metrics' => 'yes' ),
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'value',
			array(
				'label'       => esc_html__( 'Value', 'avix-widgets' ),
				'description' => esc_html__( 'A whole number (e.g. 43) counts up from zero. “Up to 20%” or “3x” show as typed, with the % or x in orange.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'label',
			array(
				'label'       => esc_html__( 'Label', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'source',
			array(
				'label'       => esc_html__( 'Source (required)', 'avix-widgets' ),
				'description' => esc_html__( 'Where the number comes from and when, e.g. “observed on rehall.com, 8 Oct 2026”. Without a source the number is not shown.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'metrics',
			array(
				'label'       => esc_html__( 'Numbers', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ value }}} · {{{ label }}}',
				'default'     => array(),
				'condition'   => array(
					'show_metrics'   => 'yes',
					'metrics_source' => 'manual',
				),
			)
		);

		$this->add_control(
			'footnote',
			array(
				'label'     => esc_html__( 'Footnote', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXTAREA,
				'rows'      => 3,
				'default'   => self::FOOTNOTE,
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_metrics' => 'yes' ),
			)
		);

		$this->add_control(
			'count_up',
			array(
				'label'       => esc_html__( 'Count up', 'avix-widgets' ),
				'description' => esc_html__( 'Whole numbers count up from zero when they come into view. The final number is always in the page, for search engines and screen readers.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'condition'   => array( 'show_metrics' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_proof() {
		$this->start_controls_section( 'section_proof', array( 'label' => esc_html__( 'Proof links', 'avix-widgets' ) ) );

		$this->add_control(
			'show_proof',
			array(
				'label'       => esc_html__( 'Show', 'avix-widgets' ),
				'description' => esc_html__( 'Links to the live site, so visitors can check the work themselves. They open in a new tab.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'proof_title',
			array(
				'label'     => esc_html__( 'Heading', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Proof you can check', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_proof' => 'yes' ),
			)
		);

		$this->add_control(
			'proof',
			array(
				'label'       => esc_html__( 'Links', 'avix-widgets' ),
				'description' => esc_html__( 'One per line: Label | https://url. Up to 5. Leave empty to use the case study (or one “Visit the live site” link).', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 4,
				'default'     => '',
				'placeholder' => 'Browse the mega menu | https://rehall.com/',
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_proof' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_quote() {
		$this->start_controls_section( 'section_quote', array( 'label' => esc_html__( 'Client quote', 'avix-widgets' ) ) );

		$this->add_control(
			'quote_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Real quotes only. The quote shows when it has both text and a name; otherwise nothing is printed.', 'avix-widgets' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->add_control(
			'show_quote',
			array(
				'label'   => esc_html__( 'Show', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'quote_text',
			array(
				'label'       => esc_html__( 'Quote', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 4,
				'default'     => '',
				'placeholder' => esc_html__( 'From case study: quote', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_quote' => 'yes' ),
			)
		);

		$this->add_control(
			'quote_name',
			array(
				'label'       => esc_html__( 'Name', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__( 'From case study', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_quote' => 'yes' ),
			)
		);

		$this->add_control(
			'quote_role',
			array(
				'label'       => esc_html__( 'Role', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__( 'From case study', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_quote' => 'yes' ),
			)
		);

		$this->add_control(
			'quote_source_url',
			array(
				'label'       => esc_html__( 'Original review', 'avix-widgets' ),
				'description' => esc_html__( 'Link to the review on its platform. Leave empty to use the case study.', 'avix-widgets' ),
				'type'        => Controls_Manager::URL,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_quote' => 'yes' ),
			)
		);

		$this->add_control(
			'quote_link_text',
			array(
				'label'     => esc_html__( 'Review link text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Read the review', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_quote' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_style() {
		$this->start_controls_section(
			'style_section',
			array(
				'label' => esc_html__( 'Section', 'avix-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'theme',
			array(
				'label'   => esc_html__( 'Theme', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'dark',
				'options' => array(
					'dark'  => esc_html__( 'Dark', 'avix-widgets' ),
					'light' => esc_html__( 'Light (warm paper)', 'avix-widgets' ),
				),
			)
		);

		$this->add_control(
			'show_glow',
			array(
				'label'       => esc_html__( 'Orange glow', 'avix-widgets' ),
				'description' => esc_html__( 'A soft glow that drifts slowly behind the section (still for visitors who prefer less motion).', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$colors = array(
			'bg'          => array( esc_html__( 'Background', 'avix-widgets' ), '--csr-bg' ),
			'ink'         => array( esc_html__( 'Headline', 'avix-widgets' ), '--csr-ink' ),
			'muted'       => array( esc_html__( 'Text', 'avix-widgets' ), '--csr-muted' ),
			'line'        => array( esc_html__( 'Lines', 'avix-widgets' ), '--csr-line' ),
			'accent'      => array( esc_html__( 'Accent', 'avix-widgets' ), '--csr-accent' ),
			'accent_text' => array( esc_html__( 'Highlight text', 'avix-widgets' ), '--csr-accent-text' ),
		);
		foreach ( $colors as $key => $color ) {
			$this->add_control(
				'color_' . $key,
				array(
					'label'     => $color[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-csr' => $color[1] . ': {{VALUE}};' ),
					'separator' => 'bg' === $key ? 'before' : '',
				)
			);
		}

		$this->add_responsive_control(
			'padding',
			array(
				'label'              => esc_html__( 'Padding', 'avix-widgets' ),
				'type'               => Controls_Manager::DIMENSIONS,
				'size_units'         => array( 'px', 'vh' ),
				'allowed_dimensions' => 'vertical',
				'selectors'          => array( '{{WRAPPER}} .avix-csr' => '--csr-pad-top: {{TOP}}{{UNIT}}; --csr-pad-bottom: {{BOTTOM}}{{UNIT}};' ),
				'separator'          => 'before',
			)
		);

		$this->add_responsive_control(
			'max_width',
			array(
				'label'      => esc_html__( 'Content width', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 720, 'max' => 1600 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-csr' => '--csr-max: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_type() {
		$this->start_controls_section(
			'style_type',
			array(
				'label' => esc_html__( 'Typography', 'avix-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$groups = array(
			'title'        => array( esc_html__( 'Headline', 'avix-widgets' ), '.avix-csr__title' ),
			'statement'    => array( esc_html__( 'Statement', 'avix-widgets' ), '.avix-csr__statement' ),
			'body'         => array( esc_html__( 'Text', 'avix-widgets' ), '.avix-csr__body p' ),
			'metric_value' => array( esc_html__( 'Number value', 'avix-widgets' ), '.avix-csr__value' ),
			'metric_label' => array( esc_html__( 'Number label', 'avix-widgets' ), '.avix-csr__metric-label' ),
		);
		foreach ( $groups as $key => $group ) {
			$this->add_group_control(
				Group_Control_Typography::get_type(),
				array(
					'name'     => $key . '_typography',
					'label'    => $group[0],
					'selector' => '{{WRAPPER}} .avix-csr ' . $group[1],
				)
			);
		}

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ */
	/* Helpers                                                             */
	/* ------------------------------------------------------------------ */

	/**
	 * Escaped text with [accent] spans and line breaks.
	 *
	 * @param string $text Raw text.
	 */
	private function accent_html( $text ) {
		$html  = esc_html( trim( (string) $text ) );
		$html  = preg_replace( '/\[([^\[\]]+)\]/u', '<span class="avix-csr__accent">$1</span>', $html );
		$lines = preg_split( '/\r\n|\r|\n/', (string) $html );
		return implode( ' <br class="avix-csr__break">', array_map( 'trim', $lines ) );
	}

	private function kses_tags() {
		return array(
			'a'      => array(
				'href'   => true,
				'title'  => true,
				'target' => true,
				'rel'    => true,
			),
			'strong' => array(),
			'em'     => array(),
			'br'     => array(),
		);
	}

	/**
	 * Safe paragraphs from text (blank-line separated) or a data-layer list.
	 *
	 * @param mixed $value String or array.
	 * @return string[]
	 */
	private function paragraphs( $value ) {
		// Data-layer paragraphs already carry <br> for single line breaks.
		$is_list = is_array( $value );
		$list    = $is_list ? $value : preg_split( '/\R\s*\R/u', trim( (string) $value ) );
		$out     = array();
		foreach ( (array) $list as $para ) {
			$para = trim( (string) $para );
			if ( '' !== $para ) {
				$safe  = wp_kses( $para, $this->kses_tags() );
				$out[] = $is_list ? $safe : nl2br( $safe, false );
			}
		}
		return $out;
	}

	/**
	 * Non-empty lines from text or a list.
	 *
	 * @param mixed $value String or array.
	 * @param int   $max   Limit.
	 */
	private function lines_of( $value, $max ) {
		$list = is_array( $value ) ? $value : preg_split( '/\R/u', (string) $value );
		$list = array_values( array_filter( array_map( 'trim', array_map( 'strval', (array) $list ) ), 'strlen' ) );
		return array_slice( $list, 0, $max );
	}

	/**
	 * An http(s) URL, or ''.
	 *
	 * @param string $url Raw URL.
	 */
	private function web_url( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url || ! preg_match( '#^https?://#i', $url ) ) {
			return '';
		}
		return esc_url( $url, array( 'http', 'https' ) );
	}

	/**
	 * "rehall.com/collections/mens-ski-jackets" for display.
	 *
	 * @param string $url URL.
	 */
	private function short_url( $url ) {
		$host = (string) wp_parse_url( $url, PHP_URL_HOST );
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		$host = preg_replace( '/^www\./i', '', $host );
		$path = rtrim( $path, '/' );
		return $host . $path;
	}

	/**
	 * Complete figures only: value, label and source, at most 4.
	 *
	 * @param array $s  Settings.
	 * @param array $cs Case study data.
	 * @return array List of array( value, label, source ).
	 */
	private function metrics( array $s, array $cs ) {
		$rows = array();
		if ( 'manual' === ( $s['metrics_source'] ?? 'auto' ) ) {
			foreach ( (array) ( $s['metrics'] ?? array() ) as $row ) {
				$rows[] = array( $row['value'] ?? '', $row['label'] ?? '', $row['source'] ?? '' );
			}
		} else {
			foreach ( (array) ( $cs['metrics'] ?? array() ) as $row ) {
				$row    = is_array( $row ) ? $row : array();
				$rows[] = array(
					$row['value'] ?? ( $row[0] ?? '' ),
					$row['label'] ?? ( $row[1] ?? '' ),
					$row['source'] ?? ( $row[2] ?? '' ),
				);
			}
		}
		$complete = array();
		foreach ( $rows as $row ) {
			$row = array_map( 'trim', array_map( 'strval', $row ) );
			if ( '' !== $row[0] && '' !== $row[1] && '' !== $row[2] ) {
				$complete[] = $row;
			}
		}
		return array_slice( $complete, 0, 4 );
	}

	/**
	 * A figure's markup: an optional prefix, the number (counting up when it
	 * is a whole number), and a short symbol suffix in orange.
	 *
	 * @param string $value Raw value.
	 * @param bool   $count Count-up is on.
	 */
	private function value_html( $value, $count ) {
		if ( preg_match( '/^\d{1,9}$/', $value ) ) {
			$attr = $count ? ' data-csr-count="' . esc_attr( (string) (int) $value ) . '"' : '';
			return '<span class="avix-csr__number"' . $attr . '>' . esc_html( $value ) . '</span>';
		}
		if ( preg_match( '/^(.*?)(\d[\d.,]*)([%+×x★]{1,2})?$/u', $value, $m ) && ( '' === $m[1] || preg_match( '/^[^\d]+$/u', $m[1] ) ) ) {
			$html = '';
			if ( '' !== trim( $m[1] ) ) {
				// The trailing space keeps "Up to 20%" readable as text; flex layout ignores it.
				$html .= '<span class="avix-csr__prefix">' . esc_html( trim( $m[1] ) ) . ' </span>';
			}
			$html .= '<span class="avix-csr__number">' . esc_html( $m[2] ) . '</span>';
			if ( ! empty( $m[3] ) ) {
				$html .= '<span class="avix-csr__suffix">' . esc_html( $m[3] ) . '</span>';
			}
			return $html;
		}
		// Short values ("1:1", "A+") keep the display size; longer text steps down.
		$long = ( function_exists( 'mb_strlen' ) ? mb_strlen( $value ) : strlen( $value ) ) > 6;
		return '<span class="avix-csr__number avix-csr__number--text' . ( $long ? ' avix-csr__number--long' : '' ) . '">' . esc_html( $value ) . '</span>';
	}

	private function arrow( $dir = 'ne' ) {
		$paths = array(
			'ne' => 'M7 17 17 7M7 7h10v10',
			'e'  => 'M5 12h14M12 5l7 7-7 7',
		);
		return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="' . esc_attr( $paths[ $dir ] ?? $paths['ne'] ) . '"/></svg>';
	}

	private function is_editor() {
		if ( class_exists( '\AvixWidgets\Case_Studies\Case_Study' ) ) {
			return \AvixWidgets\Case_Studies\Case_Study::is_editor();
		}
		return class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor && \Elementor\Plugin::$instance->editor->is_edit_mode();
	}

	/* ------------------------------------------------------------------ */
	/* Render                                                              */
	/* ------------------------------------------------------------------ */

	protected function render(): void {
		$s  = $this->get_settings_for_display();
		$cs = (array) $this->cs();

		$statement = trim( (string) ( $s['statement'] ?? '' ) );
		$statement = '' !== $statement ? $statement : trim( (string) ( $cs['outcome_statement'] ?? '' ) );

		$body_raw = trim( (string) ( $s['body'] ?? '' ) );
		$body     = $this->paragraphs( '' !== $body_raw ? $body_raw : ( $cs['outcome_body'] ?? array() ) );

		$pillars_raw = trim( (string) ( $s['pillars'] ?? '' ) );
		$pillars     = $this->lines_of( '' !== $pillars_raw ? $pillars_raw : ( $cs['outcome_pillars'] ?? array() ), 4 );

		$metrics = 'yes' === ( $s['show_metrics'] ?? '' ) ? $this->metrics( $s, $cs ) : array();

		// Proof links: the override, the case study's list, else the live site.
		$proof      = array();
		$proof_real = false;
		if ( 'yes' === ( $s['show_proof'] ?? '' ) ) {
			$raw  = trim( (string) ( $s['proof'] ?? '' ) );
			$rows = array();
			if ( '' !== $raw ) {
				foreach ( preg_split( '/\R/u', $raw ) as $line ) {
					$parts  = explode( '|', $line, 2 );
					$rows[] = array( $parts[0], $parts[1] ?? '' );
				}
				$proof_real = true;
			} else {
				foreach ( (array) ( $cs['proof'] ?? array() ) as $row ) {
					$row    = array_values( (array) $row );
					$rows[] = array( $row[0] ?? '', $row[1] ?? '' );
				}
				$proof_real = (bool) $rows;
				$live       = $this->web_url( $cs['live_url'] ?? '' );
				if ( ! $rows && '' !== $live ) {
					$name   = trim( (string) ( $cs['live_label'] ?? '' ) );
					$name   = '' !== $name ? $name : $this->short_url( $live );
					$rows[] = array( sprintf( /* translators: %s: website, e.g. rehall.com. */ __( 'Visit %s', 'avix-widgets' ), $name ), $live );
				}
			}
			foreach ( $rows as $row ) {
				$label = trim( wp_strip_all_tags( (string) $row[0] ) );
				$url   = $this->web_url( $row[1] );
				if ( '' !== $label && '' !== $url ) {
					$proof[] = array( $label, $url );
				}
			}
			$proof = array_slice( $proof, 0, 5 );
		}

		// A quote needs real text and a name, never one without the other.
		$quote = array();
		if ( 'yes' === ( $s['show_quote'] ?? '' ) ) {
			$meta  = (array) ( $cs['quote'] ?? array() );
			$text  = trim( (string) ( $s['quote_text'] ?? '' ) );
			$text  = '' !== $text ? $text : trim( (string) ( $meta['text'] ?? '' ) );
			$name  = trim( (string) ( $s['quote_name'] ?? '' ) );
			$name  = '' !== $name ? $name : trim( (string) ( $meta['name'] ?? '' ) );
			$role  = trim( (string) ( $s['quote_role'] ?? '' ) );
			$role  = '' !== $role ? $role : trim( (string) ( $meta['role'] ?? '' ) );
			$link  = $this->web_url( $s['quote_source_url']['url'] ?? '' );
			$link  = '' !== $link ? $link : $this->web_url( $meta['source_url'] ?? '' );
			if ( '' !== $text && '' !== $name ) {
				$quote = array(
					'text' => $text,
					'name' => $name,
					'role' => $role,
					'link' => $link,
				);
			}
		}

		// The "Visit the live site" fallback alone is not enough for a section.
		if ( '' === $statement && ! $body && ! $pillars && ! $metrics && ! $quote && ! ( $proof && $proof_real ) ) {
			if ( $this->is_editor() ) {
				$this->cs_alert( esc_html__( 'Case Study Results: add the outcome to the case study (Results tab), or type a statement here.', 'avix-widgets' ) );
			}
			return;
		}

		$theme    = 'light' === ( $s['theme'] ?? 'dark' ) ? 'light' : 'dark';
		$number   = trim( (string) ( $s['number'] ?? '' ) );
		$label    = trim( (string) ( $s['label'] ?? '' ) );
		$title    = trim( (string) ( $s['title'] ?? '' ) );
		$title    = '' !== $title ? $title : __( 'The impact', 'avix-widgets' );
		$tag      = Utils::validate_html_tag( $s['title_tag'] ?? 'h2' );
		$title_id = 'avix-csr-title-' . $this->get_id();
		$anchor   = sanitize_html_class( trim( (string) ( $s['anchor'] ?? '' ) ) );
		$anchor   = '' !== $anchor ? $anchor : 'impact';
		$count    = 'yes' === ( $s['count_up'] ?? '' );

		// "04 · The impact" above an H2 that also says "The impact" reads twice.
		$plain_title = trim( str_replace( array( '[', ']' ), '', $title ) );
		if ( '' !== $label && 0 === strcasecmp( $label, $plain_title ) ) {
			$label = '';
		}

		$tag_text = trim( (string) ( $s['eyebrow_tag'] ?? '' ) );
		$tag_key  = '';
		$tag_val  = '';
		if ( '' === $tag_text ) {
			$tag_key = trim( (string) ( $cs['card_label'] ?? '' ) );
			$tag_val = trim( (string) ( $cs['card_value'] ?? '' ) );
			if ( '' === $tag_key || '' === $tag_val ) {
				$tag_key = '';
				$tag_val = '';
			}
		} else {
			$parts   = explode( ':', $tag_text, 2 );
			$tag_key = isset( $parts[1] ) ? trim( $parts[0] ) : '';
			$tag_val = isset( $parts[1] ) ? trim( $parts[1] ) : $tag_text;
		}

		$classes = array( 'avix-csr', 'avix-csr--' . $theme );
		if ( 'dark' === $theme ) {
			$classes[] = 'avix-csk-on-dark';
		}
		if ( 'yes' !== ( $s['show_glow'] ?? '' ) ) {
			$classes[] = 'avix-csr--no-glow';
		}
		$this->add_render_attribute(
			'root',
			array(
				'class'           => $classes,
				'id'              => $anchor,
				'aria-labelledby' => $title_id,
				'data-avix-csr'   => wp_json_encode( array( 'count' => $count ) ),
			)
		);
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="avix-csr__fx" aria-hidden="true"><span class="avix-csr__glow avix-csr__glow--a"></span><span class="avix-csr__glow avix-csr__glow--b"></span></div>
			<div class="avix-csr__inner">
				<header class="avix-csr__head">
					<div class="avix-csr__head-side">
						<?php if ( '' !== $number || '' !== $label ) : ?>
							<p class="avix-csr__eyebrow avix-csk-eyebrow avix-csr__rise" style="--i:0;"><span class="avix-csk-eyebrow__px avix-csr__px" aria-hidden="true"></span><?php
							if ( '' !== $number ) {
								echo '<span class="avix-csr__num">' . esc_html( $number ) . '</span>';
							}
							if ( '' !== $number && '' !== $label ) {
								echo '<span class="avix-csr__dot" aria-hidden="true">·</span>';
							}
							if ( '' !== $label ) {
								echo '<span>' . esc_html( $label ) . '</span>';
							}
							?></p>
						<?php endif; ?>
						<<?php echo esc_html( $tag ); ?> id="<?php echo esc_attr( $title_id ); ?>" class="avix-csr__title avix-csr__rise" style="--i:1;"><?php echo $this->accent_html( $title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in accent_html(). ?></<?php echo esc_html( $tag ); ?>>
						<?php if ( '' !== $tag_val ) : ?>
							<p class="avix-csr__tag avix-csr__rise" style="--i:2;">
								<?php if ( '' !== $tag_key ) : ?>
									<span class="avix-csr__tag-key"><?php echo esc_html( $tag_key ); ?></span>
								<?php endif; ?>
								<span class="avix-csr__tag-val"><?php echo esc_html( $tag_val ); ?></span>
							</p>
						<?php endif; ?>
					</div>
					<?php if ( '' !== $statement || $body ) : ?>
						<div class="avix-csr__head-main">
							<?php if ( '' !== $statement ) : ?>
								<p class="avix-csr__statement avix-csr__rise" style="--i:2;"><?php echo $this->accent_html( $statement ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in accent_html(). ?></p>
							<?php endif; ?>
							<?php if ( $body ) : ?>
								<div class="avix-csr__body avix-csr__rise<?php echo count( $body ) > 1 ? ' avix-csr__body--cols' : ''; ?>" style="--i:3;">
									<?php foreach ( $body as $para ) : ?>
										<p><?php echo $para; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses() in paragraphs(). ?></p>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</header>

				<?php if ( $pillars ) : ?>
					<ul class="avix-csr__pillars avix-csr__pillars--<?php echo (int) count( $pillars ); ?>">
						<?php foreach ( $pillars as $i => $pillar ) : ?>
							<li class="avix-csr__pillar" style="--i:<?php echo (int) $i; ?>;"><span class="avix-csr__pillar-px" aria-hidden="true"></span><span class="avix-csr__pillar-text"><?php echo esc_html( $pillar ); ?></span></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if ( $metrics ) : ?>
					<?php $metrics_title = trim( (string) ( $s['metrics_title'] ?? '' ) ); ?>
					<div class="avix-csr__numbers">
						<?php if ( '' !== $metrics_title ) : ?>
							<h3 class="avix-csr__subtitle avix-csr__rise" style="--i:0;"><?php echo esc_html( $metrics_title ); ?></h3>
						<?php endif; ?>
						<ul class="avix-csr__metrics avix-csr__metrics--<?php echo (int) count( $metrics ); ?>">
							<?php foreach ( $metrics as $i => $metric ) : ?>
								<?php $is_count = $count && preg_match( '/^\d{1,9}$/', $metric[0] ); ?>
								<li class="avix-csr__metric" style="--i:<?php echo (int) $i; ?>;">
									<p class="avix-csr__value"<?php echo $is_count ? ' aria-hidden="true"' : ''; ?>><?php echo $this->value_html( $metric[0], $count ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in value_html(). ?></p>
									<?php if ( $is_count ) : ?>
										<p class="avix-csr__sr"><?php echo esc_html( $metric[0] ); ?></p>
									<?php endif; ?>
									<p class="avix-csr__metric-label"><?php echo esc_html( $metric[1] ); ?></p>
									<p class="avix-csr__source"><span class="avix-csr__source-key"><?php esc_html_e( 'Source:', 'avix-widgets' ); ?></span> <?php echo esc_html( $metric[2] ); ?></p>
								</li>
							<?php endforeach; ?>
						</ul>
						<?php $footnote = trim( (string) ( $s['footnote'] ?? '' ) ); ?>
						<?php if ( '' !== $footnote ) : ?>
							<p class="avix-csr__footnote avix-csr__rise" style="--i:1;"><?php echo esc_html( $footnote ); ?></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( $proof ) : ?>
					<?php $proof_title = trim( (string) ( $s['proof_title'] ?? '' ) ); ?>
					<div class="avix-csr__proof">
						<?php if ( '' !== $proof_title ) : ?>
							<h3 class="avix-csr__subtitle avix-csr__proof-title avix-csr__rise" style="--i:0;"><?php echo esc_html( $proof_title ); ?></h3>
						<?php endif; ?>
						<ul class="avix-csr__links">
							<?php foreach ( $proof as $i => $row ) : ?>
								<li class="avix-csr__link-item" style="--i:<?php echo (int) $i; ?>;">
									<a class="avix-csr__link" href="<?php echo esc_url( $row[1] ); ?>" target="_blank" rel="noopener">
										<span class="avix-csr__link-label"><?php echo esc_html( $row[0] ); ?></span>
										<span class="avix-csr__link-url"><?php echo esc_html( $this->short_url( $row[1] ) ); ?></span>
										<span class="avix-csr__link-icon"><?php echo $this->arrow( 'ne' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span>
										<span class="screen-reader-text avix-csr__sr"><?php esc_html_e( '(opens in a new tab)', 'avix-widgets' ); ?></span>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<?php if ( $quote ) : ?>
					<?php // Spacing lives on a div: Elementor resets <figure> margins inside widgets. ?>
					<div class="avix-csr__quote-block avix-csr__rise" style="--i:0;">
					<figure class="avix-csr__quote-wrap">
						<span class="avix-csr__glyph" aria-hidden="true"><i></i><i></i></span>
						<blockquote class="avix-csr__quote is-style-plain has-background">
							<p><?php echo esc_html( $quote['text'] ); ?></p>
						</blockquote>
						<figcaption class="avix-csr__cite">
							<cite class="avix-csr__cite-name"><?php echo esc_html( $quote['name'] ); ?></cite>
							<?php if ( '' !== $quote['role'] ) : ?>
								<span class="avix-csr__cite-role"><?php echo esc_html( $quote['role'] ); ?></span>
							<?php endif; ?>
							<?php if ( '' !== $quote['link'] && '' !== trim( (string) ( $s['quote_link_text'] ?? '' ) ) ) : ?>
								<a class="avix-csr__cite-link" href="<?php echo esc_url( $quote['link'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( trim( (string) $s['quote_link_text'] ) ); ?><?php echo $this->arrow( 'ne' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></a>
							<?php endif; ?>
						</figcaption>
					</figure>
					</div>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}
}
