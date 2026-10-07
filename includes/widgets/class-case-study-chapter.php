<?php
/**
 * Case Study Chapter: one widget for the text chapters of a case study. The
 * challenge reads as a large statement beside a sticky "01 · The challenge"
 * label; the approach stacks a headline, a lead and numbered build cards
 * joined by an orange line; "What we built" introduces the feature
 * spotlights with one anchor chip per feature. Every field fills itself from
 * the case study and can be overridden on the page.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Widgets;

use AvixWidgets\Case_Studies\Case_Study;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Utils;
use Elementor\Widget_Base;

defined( 'ABSPATH' ) || exit;

class Case_Study_Chapter extends Widget_Base {

	use \AvixWidgets\Case_Studies\Source;

	/** Chapter modes and their fixed defaults. */
	const MODES = array(
		'challenge' => array(
			'number' => '01',
			'label'  => 'The challenge',
			'anchor' => 'challenge',
			'layout' => 'split',
			'theme'  => 'white',
		),
		'approach'  => array(
			'number' => '02',
			'label'  => 'Approach',
			'anchor' => 'approach',
			'layout' => 'stacked',
			'theme'  => 'paper',
		),
		'features'  => array(
			'number' => '03',
			'label'  => 'What we built',
			'anchor' => 'features',
			'layout' => 'stacked',
			'theme'  => 'white',
		),
		'custom'    => array(
			'number' => '',
			'label'  => '',
			'anchor' => '',
			'layout' => 'split',
			'theme'  => 'white',
		),
	);

	/** Spoken numbers for the features headline ("Six features …"). */
	const SPOKEN = array( 1 => 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve' );

	public function get_name(): string {
		return 'avix-case-study-chapter';
	}

	public function get_title(): string {
		return esc_html__( 'Case Study Chapter', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-post-content';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'case study', 'portfolio', 'chapter', 'challenge', 'approach', 'features', 'story', 'avix' );
	}

	public function get_style_depends(): array {
		return array( 'avix-case-study-chapter' );
	}

	public function get_script_depends(): array {
		return array( 'avix-case-study-chapter' );
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
		$this->controls_extras();
		$this->controls_style();
		$this->controls_type();
	}

	private function controls_content() {
		$this->start_controls_section( 'section_content', array( 'label' => esc_html__( 'Chapter', 'avix-widgets' ) ) );

		$this->add_control(
			'chapter',
			array(
				'label'       => esc_html__( 'Chapter', 'avix-widgets' ),
				'description' => esc_html__( 'Picks the layout and which case-study fields fill the empty boxes below. The challenge is a statement beside a sticky label; the approach shows numbered build cards; What we built lists one anchor chip per feature spotlight on the page.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'challenge',
				'options'     => array(
					'challenge' => esc_html__( '01 · The challenge', 'avix-widgets' ),
					'approach'  => esc_html__( '02 · Approach', 'avix-widgets' ),
					'features'  => esc_html__( '03 · What we built', 'avix-widgets' ),
					'custom'    => esc_html__( 'Custom chapter', 'avix-widgets' ),
				),
			)
		);

		$this->add_control(
			'number',
			array(
				'label'       => esc_html__( 'Number', 'avix-widgets' ),
				'description' => esc_html__( 'Leave empty for 01, 02 or 03 by chapter.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => '01',
				'dynamic'     => array( 'active' => true ),
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'label',
			array(
				'label'       => esc_html__( 'Label', 'avix-widgets' ),
				'description' => esc_html__( 'The small label next to the number. Leave empty for the chapter’s own (The challenge, Approach, What we built).', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__( 'The challenge', 'avix-widgets' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Headline', 'avix-widgets' ),
				'description' => esc_html__( 'Wrap words in [brackets] to highlight them in orange. Press Enter for a new line. Leave empty to use the case study: the challenge statement, “The solution & execution”, or “{count} features that do the selling” ({count} becomes Three, Four… from the spotlights on this page).', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 3,
				'default'     => '',
				'placeholder' => esc_html__( 'From case study: challenge statement', 'avix-widgets' ),
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
			'caption',
			array(
				'label'       => esc_html__( 'Caption', 'avix-widgets' ),
				'description' => esc_html__( 'A short muted line under the label. Leave empty for “The problem we were hired to solve” on the challenge (none on the others).', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__( 'The problem we were hired to solve', 'avix-widgets' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'lead',
			array(
				'label'       => esc_html__( 'Lead', 'avix-widgets' ),
				'description' => esc_html__( 'The larger opening paragraph. Leave empty to use the approach intro (Approach), or the first paragraph of the text below.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 3,
				'default'     => '',
				'placeholder' => esc_html__( 'From case study: approach intro', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'body',
			array(
				'label'       => esc_html__( 'Text', 'avix-widgets' ),
				'description' => esc_html__( 'Paragraphs separated by a blank line. Links, <strong> and <em> are kept. Leave empty to use the case study’s challenge, approach or solution text.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 8,
				'default'     => '',
				'placeholder' => esc_html__( 'From case study: chapter text', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'anchor',
			array(
				'label'       => esc_html__( 'Anchor', 'avix-widgets' ),
				'description' => esc_html__( 'The section’s #id for links such as “See what we built”. Leave empty for #challenge, #approach or #features.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => 'challenge',
				'separator'   => 'before',
			)
		);

		$this->end_controls_section();
	}

	private function controls_extras() {
		$this->start_controls_section( 'section_extras', array( 'label' => esc_html__( 'Chips & cards', 'avix-widgets' ) ) );

		$this->add_control(
			'show_chips',
			array(
				'label'       => esc_html__( 'Chips', 'avix-widgets' ),
				'description' => esc_html__( 'The challenge shows the project’s constraints; What we built shows one chip per feature spotlight on this page, each jumping to it.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'chips_label',
			array(
				'label'       => esc_html__( 'Chips label', 'avix-widgets' ),
				'description' => esc_html__( 'Leave empty for “Constraints” (challenge) or “Jump to a feature” (What we built).', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__( 'Constraints', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_chips' => 'yes' ),
			)
		);

		$this->add_control(
			'chips',
			array(
				'label'       => esc_html__( 'Chips', 'avix-widgets' ),
				'description' => esc_html__( 'Comma-separated. Leave empty to use the case study.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__( 'From case study: constraints', 'avix-widgets' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_chips' => 'yes' ),
			)
		);

		$this->add_control(
			'show_cards',
			array(
				'label'       => esc_html__( 'Cards', 'avix-widgets' ),
				'description' => esc_html__( 'Numbered cards, e.g. what was implemented. The approach fills them from the case study.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'cards',
			array(
				'label'       => esc_html__( 'Cards', 'avix-widgets' ),
				'description' => esc_html__( 'One card per line: Title: text (split on the first colon). Up to 6. Leave empty to use the case study’s implementations.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 6,
				'default'     => '',
				'placeholder' => esc_html__( 'Pixel-perfect build: What we did and why it mattered.', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_cards' => 'yes' ),
			)
		);

		$this->add_control(
			'card_numbers',
			array(
				'label'     => esc_html__( 'Card numbers', 'avix-widgets' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'show_cards' => 'yes' ),
			)
		);

		$this->add_control(
			'connector',
			array(
				'label'       => esc_html__( 'Connecting line', 'avix-widgets' ),
				'description' => esc_html__( 'An orange line that draws through the card numbers when they come into view (three across on wide screens, down the left on phones).', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'condition'   => array(
					'show_cards'   => 'yes',
					'card_numbers' => 'yes',
				),
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'       => esc_html__( 'Layout', 'avix-widgets' ),
				'description' => esc_html__( 'Automatic: side by side for the challenge and custom chapters, stacked for the approach and What we built.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'auto',
				'options'     => array(
					'auto'    => esc_html__( 'Automatic', 'avix-widgets' ),
					'split'   => esc_html__( 'Label beside the text', 'avix-widgets' ),
					'stacked' => esc_html__( 'Stacked', 'avix-widgets' ),
				),
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'sticky_label',
			array(
				'label'       => esc_html__( 'Sticky label', 'avix-widgets' ),
				'description' => esc_html__( 'Side-by-side layout on wide screens: the label stays in view while the text scrolls past.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
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
				'label'       => esc_html__( 'Theme', 'avix-widgets' ),
				'description' => esc_html__( 'Automatic: white for the challenge and What we built, warm paper for the approach, so the chapters alternate.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'auto',
				'options'     => array(
					'auto'  => esc_html__( 'Automatic', 'avix-widgets' ),
					'white' => esc_html__( 'White', 'avix-widgets' ),
					'paper' => esc_html__( 'Warm paper', 'avix-widgets' ),
					'dark'  => esc_html__( 'Dark', 'avix-widgets' ),
				),
			)
		);

		$colors = array(
			'bg'          => array( esc_html__( 'Background', 'avix-widgets' ), '--csc-bg' ),
			'ink'         => array( esc_html__( 'Headline', 'avix-widgets' ), '--csc-ink' ),
			'muted'       => array( esc_html__( 'Text', 'avix-widgets' ), '--csc-muted' ),
			'line'        => array( esc_html__( 'Lines', 'avix-widgets' ), '--csc-line' ),
			'accent'      => array( esc_html__( 'Accent', 'avix-widgets' ), '--csc-accent' ),
			'accent_text' => array( esc_html__( 'Small orange text', 'avix-widgets' ), '--csc-accent-text' ),
			'card_bg'     => array( esc_html__( 'Cards', 'avix-widgets' ), '--csc-card-bg' ),
		);
		foreach ( $colors as $key => $color ) {
			$this->add_control(
				'color_' . $key,
				array(
					'label'     => $color[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-csc' => $color[1] . ': {{VALUE}};' ),
					'separator' => 'bg' === $key ? 'before' : '',
				)
			);
		}

		$this->add_responsive_control(
			'padding',
			array(
				'label'              => esc_html__( 'Padding', 'avix-widgets' ),
				'description'        => esc_html__( 'Leave empty for the chapter’s own rhythm (a little tighter above the challenge, which follows the hero’s white band, and under What we built, which leads into the spotlights).', 'avix-widgets' ),
				'type'               => Controls_Manager::DIMENSIONS,
				'size_units'         => array( 'px', 'vh' ),
				'allowed_dimensions' => 'vertical',
				'selectors'          => array( '{{WRAPPER}} .avix-csc' => '--csc-pad-top: {{TOP}}{{UNIT}}; --csc-pad-bottom: {{BOTTOM}}{{UNIT}};' ),
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
				'selectors'  => array( '{{WRAPPER}} .avix-csc' => '--csc-max: {{SIZE}}{{UNIT}};' ),
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
			'title'      => array( esc_html__( 'Headline', 'avix-widgets' ), '.avix-csc__title' ),
			'lead'       => array( esc_html__( 'Lead', 'avix-widgets' ), '.avix-csc__lead' ),
			'body'       => array( esc_html__( 'Text', 'avix-widgets' ), '.avix-csc__body p' ),
			'eyebrow'    => array( esc_html__( 'Label', 'avix-widgets' ), '.avix-csc__eyebrow' ),
			'card_title' => array( esc_html__( 'Card title', 'avix-widgets' ), '.avix-csc__card-title' ),
			'card_text'  => array( esc_html__( 'Card text', 'avix-widgets' ), '.avix-csc__card-text' ),
		);
		foreach ( $groups as $key => $group ) {
			$this->add_group_control(
				Group_Control_Typography::get_type(),
				array(
					'name'     => $key . '_typography',
					'label'    => $group[0],
					'selector' => '{{WRAPPER}} .avix-csc ' . $group[1],
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
		$html  = preg_replace( '/\[([^\[\]]+)\]/u', '<span class="avix-csc__accent">$1</span>', $html );
		$lines = preg_split( '/\r\n|\r|\n/', (string) $html );
		return implode( ' <br class="avix-csc__break">', array_map( 'trim', $lines ) );
	}

	/**
	 * Inline tags a chapter paragraph may keep.
	 */
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
	 * Paragraphs from a textarea: split on blank lines, single line breaks kept.
	 *
	 * @param string $text Raw text.
	 * @return string[] Safe HTML per paragraph.
	 */
	private function paragraphs( $text ) {
		$out = array();
		foreach ( preg_split( '/\R\s*\R/u', trim( (string) $text ) ) as $para ) {
			$para = trim( $para );
			if ( '' !== $para ) {
				$out[] = nl2br( wp_kses( $para, $this->kses_tags() ), false );
			}
		}
		return $out;
	}

	/**
	 * A list of paragraphs from the data layer, made safe again on output.
	 *
	 * @param mixed $list Paragraphs (array) or text (string).
	 * @return string[]
	 */
	private function meta_paragraphs( $list ) {
		if ( is_string( $list ) ) {
			return $this->paragraphs( $list );
		}
		// The data layer already turned single line breaks into <br>.
		$out = array();
		foreach ( (array) $list as $para ) {
			$para = trim( (string) $para );
			if ( '' !== $para ) {
				$out[] = wp_kses( $para, $this->kses_tags() );
			}
		}
		return $out;
	}

	/**
	 * Comma list → trimmed labels, at most $max.
	 *
	 * @param mixed $value String or array.
	 * @param int   $max   Limit.
	 */
	private function list_of( $value, $max = 12 ) {
		$items = is_array( $value ) ? $value : explode( ',', (string) $value );
		$items = array_values( array_filter( array_map( 'trim', array_map( 'strval', $items ) ), 'strlen' ) );
		return array_slice( $items, 0, $max );
	}

	/**
	 * Card rows from "Title: text" lines, at most 6.
	 *
	 * @param string $text Raw textarea.
	 * @return array List of array( title, text ).
	 */
	private function card_rows( $text ) {
		$rows = array();
		foreach ( preg_split( '/\R/u', (string) $text ) as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			$parts  = explode( ':', $line, 2 );
			$rows[] = array( trim( $parts[0] ), isset( $parts[1] ) ? trim( $parts[1] ) : '' );
		}
		return array_slice( $rows, 0, 6 );
	}

	/**
	 * Cards from the data layer (array of [title, text]) or a raw string.
	 *
	 * @param mixed $value Meta value.
	 */
	private function meta_cards( $value ) {
		if ( is_string( $value ) ) {
			return $this->card_rows( $value );
		}
		$rows = array();
		foreach ( (array) $value as $row ) {
			$row   = array_values( (array) $row );
			$title = trim( (string) ( $row[0] ?? '' ) );
			$text  = trim( (string) ( $row[1] ?? '' ) );
			if ( '' !== $title || '' !== $text ) {
				$rows[] = array( $title, $text );
			}
		}
		return array_slice( $rows, 0, 6 );
	}

	/**
	 * Feature spotlights on the case study's page, in document order.
	 *
	 * @param array $cs Case study data.
	 */
	private function spotlights( array $cs ) {
		$id = (int) ( $cs['id'] ?? 0 );
		if ( ! $id || ! class_exists( '\AvixWidgets\Case_Studies\Case_Study' ) || ! method_exists( '\AvixWidgets\Case_Studies\Case_Study', 'spotlights' ) ) {
			return array();
		}
		$list = array();
		foreach ( (array) Case_Study::spotlights( $id ) as $spot ) {
			$spot = (array) $spot;
			$eid  = sanitize_html_class( (string) ( $spot['id'] ?? '' ) );
			if ( '' === $eid ) {
				continue;
			}
			$name = trim( wp_strip_all_tags( str_replace( array( '[', ']' ), '', (string) ( $spot['tag'] ?? '' ) ) ) );
			if ( '' === $name ) {
				$name = trim( wp_strip_all_tags( str_replace( array( '[', ']' ), '', (string) ( $spot['title'] ?? '' ) ) ) );
			}
			$list[] = array(
				'id'   => $eid,
				'name' => $name,
			);
		}
		return $list;
	}

	/**
	 * "Six", or digits past twelve.
	 *
	 * @param int $count Number.
	 */
	private function spoken( $count ) {
		return self::SPOKEN[ $count ] ?? (string) $count;
	}

	/**
	 * The eyebrow: "01 · The challenge" with the shared pixel marker.
	 *
	 * @param string $number Number.
	 * @param string $label  Label.
	 */
	private function eyebrow_html( $number, $label ) {
		$inner = '';
		if ( '' !== $number ) {
			$inner .= '<span class="avix-csc__num">' . esc_html( $number ) . '</span>';
		}
		if ( '' !== $number && '' !== $label ) {
			$inner .= '<span class="avix-csc__dot" aria-hidden="true">·</span>';
		}
		if ( '' !== $label ) {
			$inner .= '<span class="avix-csc__label">' . esc_html( $label ) . '</span>';
		}
		if ( '' === $inner ) {
			return '';
		}
		return '<p class="avix-csc__eyebrow avix-csk-eyebrow avix-csc__rise" style="--i:0;"><span class="avix-csk-eyebrow__px avix-csc__px" aria-hidden="true"></span>' . $inner . '</p>';
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
		$s    = $this->get_settings_for_display();
		$mode = (string) ( $s['chapter'] ?? 'challenge' );
		$mode = isset( self::MODES[ $mode ] ) ? $mode : 'challenge';
		$def  = self::MODES[ $mode ];
		$cs   = 'custom' === $mode ? array() : (array) $this->cs();
		$data = ! empty( $cs );

		$spots = 'features' === $mode ? $this->spotlights( $cs ) : array();

		$number = trim( (string) ( $s['number'] ?? '' ) );
		$number = '' !== $number ? $number : $def['number'];
		$label  = trim( (string) ( $s['label'] ?? '' ) );
		$label  = '' !== $label ? $label : $def['label'];

		// Headline: the override, else the chapter's own. Only a title that
		// comes from the case study (or the editor) counts as content: a
		// generic default alone never makes a section render.
		$title       = trim( (string) ( $s['title'] ?? '' ) );
		$title_is_ok = '' !== $title;
		if ( '' === $title ) {
			if ( 'challenge' === $mode ) {
				$title       = trim( (string) ( $cs['challenge_statement'] ?? '' ) );
				$title_is_ok = '' !== $title;
			} elseif ( 'approach' === $mode ) {
				$title = __( 'The solution & execution', 'avix-widgets' );
			} elseif ( 'features' === $mode ) {
				$count = count( $spots );
				if ( 1 === $count ) {
					$title = __( 'One feature that does the selling', 'avix-widgets' );
				} elseif ( $count > 1 ) {
					$title = __( '{count} features that do the selling', 'avix-widgets' );
				} else {
					$title = __( 'The features that do the selling', 'avix-widgets' );
				}
			}
		}
		$title = str_replace( '{count}', $this->spoken( count( $spots ) ), $title );

		$caption = trim( (string) ( $s['caption'] ?? '' ) );
		if ( '' === $caption && 'challenge' === $mode && $data ) {
			$caption = __( 'The problem we were hired to solve', 'avix-widgets' );
		}

		// Lead and paragraphs.
		$lead = trim( (string) ( $s['lead'] ?? '' ) );
		$lead = '' !== $lead ? nl2br( wp_kses( $lead, $this->kses_tags() ), false ) : '';
		if ( '' === $lead && 'approach' === $mode && '' === trim( (string) ( $s['body'] ?? '' ) ) ) {
			$intro = trim( (string) ( $cs['approach_intro'] ?? '' ) );
			$lead  = '' !== $intro ? nl2br( wp_kses( $intro, $this->kses_tags() ), false ) : '';
		}
		$body_raw = trim( (string) ( $s['body'] ?? '' ) );
		if ( '' !== $body_raw ) {
			$body = $this->paragraphs( $body_raw );
		} else {
			$keys = array(
				'challenge' => 'challenge_body',
				'approach'  => 'approach_body',
				'features'  => 'solution_body',
			);
			$body = isset( $keys[ $mode ] ) ? $this->meta_paragraphs( $cs[ $keys[ $mode ] ] ?? array() ) : array();
		}

		$layout = (string) ( $s['layout'] ?? 'auto' );
		$layout = in_array( $layout, array( 'split', 'stacked' ), true ) ? $layout : $def['layout'];

		// Stacked chapters open with a lead: without one, the first paragraph takes the role.
		if ( 'stacked' === $layout && '' === $lead && $body ) {
			$lead = array_shift( $body );
		}

		// Chips.
		$chips       = array();
		$chip_anchor = false;
		if ( 'yes' === ( $s['show_chips'] ?? '' ) ) {
			$chips_raw = trim( (string) ( $s['chips'] ?? '' ) );
			if ( '' !== $chips_raw ) {
				$chips = $this->list_of( $chips_raw, 12 );
			} elseif ( 'challenge' === $mode ) {
				$chips = $this->list_of( $cs['constraints'] ?? array(), 6 );
			} elseif ( 'features' === $mode && $spots ) {
				$chips       = $spots;
				$chip_anchor = true;
			}
		}
		$chips_label = trim( (string) ( $s['chips_label'] ?? '' ) );
		if ( '' === $chips_label && $chips ) {
			if ( $chip_anchor ) {
				$chips_label = __( 'Jump to a feature', 'avix-widgets' );
			} elseif ( 'challenge' === $mode && '' === trim( (string) ( $s['chips'] ?? '' ) ) ) {
				$chips_label = __( 'Constraints', 'avix-widgets' );
			}
		}

		// Cards.
		$cards = array();
		if ( 'yes' === ( $s['show_cards'] ?? '' ) ) {
			$cards_raw = trim( (string) ( $s['cards'] ?? '' ) );
			if ( '' !== $cards_raw ) {
				$cards = $this->card_rows( $cards_raw );
			} elseif ( 'approach' === $mode ) {
				$cards = $this->meta_cards( $cs['implementations'] ?? array() );
			}
		}

		$has_content = $title_is_ok || '' !== $lead || $body || $chips || $cards;
		if ( 'custom' !== $mode && ! $data && '' === trim( (string) ( $s['title'] ?? '' ) ) && '' === $body_raw && '' === trim( (string) ( $s['lead'] ?? '' ) ) && ! $cards ) {
			$has_content = false;
		}
		if ( ! $has_content ) {
			if ( $this->is_editor() ) {
				$this->cs_alert(
					'custom' === $mode
						? esc_html__( 'Case Study Chapter: add a headline or some text.', 'avix-widgets' )
						: esc_html__( 'Case Study Chapter: this chapter is empty. Fill the case study’s fields (Story tab), or type a headline and text here.', 'avix-widgets' )
				);
			}
			return;
		}

		$theme = (string) ( $s['theme'] ?? 'auto' );
		$theme = in_array( $theme, array( 'white', 'paper', 'dark' ), true ) ? $theme : $def['theme'];

		$anchor = sanitize_html_class( trim( (string) ( $s['anchor'] ?? '' ) ) );
		$anchor = '' !== $anchor ? $anchor : $def['anchor'];

		$tag      = Utils::validate_html_tag( $s['title_tag'] ?? 'h2' );
		$title_id = 'avix-csc-title-' . $this->get_id();
		$sticky   = 'split' === $layout && 'yes' === ( $s['sticky_label'] ?? '' );
		$numbers  = 'yes' === ( $s['card_numbers'] ?? '' );

		$classes = array(
			'avix-csc',
			'avix-csc--' . $mode,
			'avix-csc--' . $theme,
			'avix-csc--' . $layout,
		);
		if ( 'dark' === $theme ) {
			$classes[] = 'avix-csk-on-dark';
		}
		if ( $sticky ) {
			$classes[] = 'avix-csc--sticky';
		}
		if ( $cards ) {
			// Card grid on wide screens: three across when the count fills
			// rows of three, two across for even counts, one column for the
			// rest. The side-by-side layout has less room: two at most.
			$n = count( $cards );
			if ( 'stacked' === $layout && in_array( $n, array( 3, 5, 6 ), true ) ) {
				$grid = 'three';
			} elseif ( 0 === $n % 2 ) {
				$grid = 'two';
			} else {
				$grid = 'one';
			}
			$classes[] = 'avix-csc--grid-' . $grid;
			$classes[] = 'avix-csc--cards-' . ( 0 === $n % 2 ? 'even' : 'odd' );
			if ( $numbers && 'yes' === ( $s['connector'] ?? '' ) && $n > 1 ) {
				$classes[] = 'avix-csc--connector';
			}
		}

		$this->add_render_attribute(
			'root',
			array(
				'class'         => $classes,
				'data-avix-csc' => wp_json_encode( array( 'sticky' => $sticky ) ),
			)
		);
		if ( '' !== $anchor ) {
			$this->add_render_attribute( 'root', 'id', $anchor );
		}
		if ( '' !== $title ) {
			$this->add_render_attribute( 'root', 'aria-labelledby', $title_id );
		} elseif ( '' !== $label ) {
			$this->add_render_attribute( 'root', 'aria-label', $label );
		}

		$eyebrow = $this->eyebrow_html( $number, $label );
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="avix-csc__inner">
				<?php if ( 'split' === $layout ) : ?>
					<div class="avix-csc__grid">
						<?php if ( '' !== $eyebrow || '' !== $caption ) : ?>
							<div class="avix-csc__aside" data-csc-aside>
								<?php echo $eyebrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in eyebrow_html(). ?>
								<?php if ( '' !== $caption ) : ?>
									<p class="avix-csc__caption avix-csc__rise" style="--i:1;"><?php echo esc_html( $caption ); ?></p>
								<?php endif; ?>
							</div>
						<?php endif; ?>
						<div class="avix-csc__main">
							<?php if ( '' !== $title ) : ?>
								<<?php echo esc_html( $tag ); ?> id="<?php echo esc_attr( $title_id ); ?>" class="avix-csc__title avix-csc__title--statement avix-csc__rise" style="--i:1;"><?php echo $this->accent_html( $title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in accent_html(). ?></<?php echo esc_html( $tag ); ?>>
							<?php endif; ?>
							<?php if ( '' !== $lead ) : ?>
								<p class="avix-csc__lead avix-csc__rise" style="--i:2;"><?php echo $lead; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses() in render(). ?></p>
							<?php endif; ?>
							<?php $this->render_body( $body, 'split', '' === $lead ); ?>
							<?php $this->render_chips( $chips, $chips_label, $chip_anchor ); ?>
							<?php $this->render_cards( $cards, $numbers ); ?>
						</div>
					</div>
				<?php else : ?>
					<?php
					// What we built: the feature chips sit under the headline, beside
					// the (long) lead, so the header reads as one balanced block.
					$chips_in_head = $chip_anchor && $chips && ( '' !== $lead || '' !== $caption );
					$row_classes   = array( 'avix-csc__head-row' );
					if ( '' === $lead && '' === $caption ) {
						$row_classes[] = 'avix-csc__head-row--solo';
					}
					if ( $chips_in_head ) {
						$row_classes[] = 'avix-csc__head-row--chips';
					}
					?>
					<header class="avix-csc__head">
						<?php echo $eyebrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in eyebrow_html(). ?>
						<div class="<?php echo esc_attr( implode( ' ', $row_classes ) ); ?>">
							<?php if ( '' !== $title ) : ?>
								<<?php echo esc_html( $tag ); ?> id="<?php echo esc_attr( $title_id ); ?>" class="avix-csc__title avix-csc__title--display avix-csc__rise" style="--i:1;"><?php echo $this->accent_html( $title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in accent_html(). ?></<?php echo esc_html( $tag ); ?>>
							<?php endif; ?>
							<?php
							if ( $chips_in_head ) {
								$this->render_chips( $chips, $chips_label, $chip_anchor );
							}
							?>
							<?php if ( '' !== $lead || '' !== $caption ) : ?>
								<div class="avix-csc__head-side">
									<?php if ( '' !== $caption ) : ?>
										<p class="avix-csc__caption avix-csc__rise" style="--i:2;"><?php echo esc_html( $caption ); ?></p>
									<?php endif; ?>
									<?php if ( '' !== $lead ) : ?>
										<p class="avix-csc__lead avix-csc__rise" style="--i:2;"><?php echo $lead; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses() in render(). ?></p>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>
					</header>
					<?php $this->render_body( $body, 'stacked', false ); ?>
					<?php
					if ( ! $chips_in_head ) {
						$this->render_chips( $chips, $chips_label, $chip_anchor );
					}
					?>
					<?php $this->render_cards( $cards, $numbers ); ?>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}

	/**
	 * Body paragraphs. In the side-by-side layout the first one is larger
	 * (unless a lead already opens the text).
	 *
	 * @param string[] $body    Safe paragraphs.
	 * @param string   $layout  split|stacked.
	 * @param bool     $opening First paragraph gets the opening size.
	 */
	private function render_body( array $body, $layout, $opening ) {
		if ( ! $body ) {
			return;
		}
		$classes = array( 'avix-csc__body', 'avix-csc__rise' );
		if ( 'stacked' === $layout && count( $body ) > 1 ) {
			$classes[] = 'avix-csc__body--cols';
		}
		if ( $opening ) {
			$classes[] = 'avix-csc__body--opening';
		}
		echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" style="--i:3;">';
		foreach ( $body as $para ) {
			echo '<p>' . $para . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses() in paragraphs().
		}
		echo '</div>';
	}

	/**
	 * Chips: plain labels, or anchor links to the feature spotlights.
	 *
	 * @param array  $chips  Labels, or spotlight rows (id, name) when $anchor.
	 * @param string $label  Small label above.
	 * @param bool   $anchor Link each chip to its spotlight.
	 */
	private function render_chips( array $chips, $label, $anchor ) {
		if ( ! $chips ) {
			return;
		}
		$list_id = 'avix-csc-chips-' . $this->get_id();
		echo '<div class="avix-csc__chipbar avix-csc__rise' . ( $anchor ? ' avix-csc__chipbar--nav' : '' ) . '" style="--i:4;">';
		if ( '' !== $label ) {
			echo '<p class="avix-csc__chips-label" id="' . esc_attr( $list_id ) . '">' . esc_html( $label ) . '</p>';
		}
		if ( $anchor ) {
			echo '<nav class="avix-csc__scroller" data-csc-scroller' . ( '' !== $label ? ' aria-labelledby="' . esc_attr( $list_id ) . '"' : ' aria-label="' . esc_attr__( 'Features', 'avix-widgets' ) . '"' ) . '>';
		} else {
			echo '<div class="avix-csc__scroller">';
		}
		echo '<ul class="avix-csc__chips"' . ( ! $anchor && '' !== $label ? ' aria-labelledby="' . esc_attr( $list_id ) . '"' : '' ) . '>';
		foreach ( array_values( $chips ) as $i => $chip ) {
			echo '<li class="avix-csc__chip-item" style="--c:' . (int) $i . ';">';
			if ( $anchor ) {
				printf(
					'<a class="avix-csc__chip avix-csc__chip--link" href="#avix-csf-%1$s" data-csc-jump><span class="avix-csc__chip-num">%2$s</span><span class="avix-csc__chip-text">%3$s</span><svg class="avix-csc__chip-arrow" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 5v14M5 12l7 7 7-7"/></svg></a>',
					esc_attr( $chip['id'] ),
					esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ),
					esc_html( '' !== $chip['name'] ? $chip['name'] : sprintf( /* translators: %d: feature number. */ __( 'Feature %d', 'avix-widgets' ), $i + 1 ) )
				);
			} else {
				echo '<span class="avix-csc__chip">' . esc_html( $chip ) . '</span>';
			}
			echo '</li>';
		}
		echo '</ul>';
		echo $anchor ? '</nav>' : '</div>';
		echo '</div>';
	}

	/**
	 * Numbered cards, joined by the connector line.
	 *
	 * @param array $cards   Rows of array( title, text ).
	 * @param bool  $numbers Show 01, 02 …
	 */
	private function render_cards( array $cards, $numbers ) {
		if ( ! $cards ) {
			return;
		}
		echo '<ol class="avix-csc__cards' . ( $numbers ? '' : ' avix-csc__cards--plain' ) . '">';
		foreach ( $cards as $i => $card ) {
			echo '<li class="avix-csc__card" style="--i:' . (int) $i . ';">';
			if ( $numbers ) {
				echo '<span class="avix-csc__card-index"><span class="avix-csc__card-num">' . esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ) . '</span></span>';
				echo '<span class="avix-csc__card-line" aria-hidden="true"></span>';
			}
			if ( '' !== $card[0] ) {
				echo '<h3 class="avix-csc__card-title">' . esc_html( $card[0] ) . '</h3>';
			}
			if ( '' !== $card[1] ) {
				echo '<p class="avix-csc__card-text">' . esc_html( $card[1] ) . '</p>';
			}
			echo '</li>';
		}
		echo '</ol>';
	}
}
