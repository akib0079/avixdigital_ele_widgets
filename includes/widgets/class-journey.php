<?php
/**
 * Journey: the agency's milestones on one track. As the visitor scrolls, the
 * Avix pixel character walks the track node by node, lighting each milestone
 * it passes, and plants a flag at the last one. On narrow screens the track
 * turns vertical and the character climbs down it beside the cards.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Widgets;

use AvixWidgets\Pixel_Pal;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;

defined( 'ABSPATH' ) || exit;

class Journey extends Widget_Base {

	use Media;

	const UPLOADS = 'https://avixdigital.com/wp-content/uploads/';

	/**
	 * Up to this many milestones sit side by side on a horizontal track once
	 * the widget is 1180px wide; longer journeys always use the vertical log.
	 */
	const ROW_MAX = 4;

	public function get_name(): string {
		return 'avix-journey';
	}

	public function get_title(): string {
		return esc_html__( 'Journey', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-time-line';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'journey', 'timeline', 'milestones', 'history', 'story', 'about', 'stepper', 'avix' );
	}

	public function get_style_depends(): array {
		return array( 'avix-journey' );
	}

	public function get_script_depends(): array {
		return array( 'avix-journey' );
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/* ------------------------------------------------------------------ */
	/* Controls                                                            */
	/* ------------------------------------------------------------------ */

	protected function register_controls(): void {
		$this->controls_header();
		$this->controls_items();
		$this->controls_track();
		$this->controls_pal();
		$this->controls_style();
	}

	private function controls_header() {
		$this->start_controls_section( 'section_header', array( 'label' => esc_html__( 'Header', 'avix-widgets' ) ) );

		$this->add_control(
			'eyebrow',
			array(
				'label'       => esc_html__( 'Eyebrow', 'avix-widgets' ),
				'description' => esc_html__( 'Small label above the title, with a little orange pixel in front.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__( 'Our journey', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Title', 'avix-widgets' ),
				'description' => esc_html__( 'Wrap words in [brackets] to highlight them in orange. Press Enter for a new line.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => 'The experience behind [your next project]',
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'   => esc_html__( 'Title HTML tag', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h2',
				'options' => array(
					'h1'  => 'H1',
					'h2'  => 'H2',
					'h3'  => 'H3',
					'div' => 'div',
					'p'   => 'p',
				),
			)
		);

		$this->add_control(
			'text',
			array(
				'label'   => esc_html__( 'Text', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 3,
				'default' => esc_html__( 'From our first design and development projects in 2020 to Shopify stores and custom web applications, every project adds to the experience we bring to yours.', 'avix-widgets' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_items() {
		$this->start_controls_section( 'section_items', array( 'label' => esc_html__( 'Milestones', 'avix-widgets' ) ) );

		$repeater = new Repeater();

		$repeater->add_control(
			'label',
			array(
				'label'       => esc_html__( 'Label', 'avix-widgets' ),
				'description' => esc_html__( 'A year or a short tag, shown big at the top of the card.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Title', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'text',
			array(
				'label'   => esc_html__( 'Text', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 4,
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'image',
			array(
				'label'       => esc_html__( 'Image (optional)', 'avix-widgets' ),
				'description' => esc_html__( 'Optional picture for the card. Leave empty for a text-only card.', 'avix-widgets' ),
				'type'        => Controls_Manager::MEDIA,
				'separator'   => 'before',
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'image_focus',
			array(
				'label'   => esc_html__( 'Image focus', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'center',
				'options' => array(
					'top'    => esc_html__( 'Top', 'avix-widgets' ),
					'center' => esc_html__( 'Centre', 'avix-widgets' ),
					'bottom' => esc_html__( 'Bottom', 'avix-widgets' ),
				),
			)
		);

		$repeater->add_control(
			'link',
			array(
				'label'       => esc_html__( 'Link (optional)', 'avix-widgets' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => 'https://avixdigital.com/service/',
				'separator'   => 'before',
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'link_text',
			array(
				'label'       => esc_html__( 'Link text', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__( 'Learn more', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'items',
			array(
				'label'       => esc_html__( 'Milestones', 'avix-widgets' ),
				'description' => esc_html__( 'Up to 4 milestones sit side by side on a horizontal track on wide screens (about 1180px and up); 5 or more use a vertical track everywhere.', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ label }}} · {{{ title }}}',
				'default'     => array(
					array(
						'label' => '2020',
						'title' => esc_html__( 'Design & development', 'avix-widgets' ),
						'text'  => esc_html__( 'Design and development work together, so your website reflects your brand and gives customers a clear next step. We agree the scope, content and integrations before building.', 'avix-widgets' ),
						'image' => array( 'url' => self::UPLOADS . '2025/12/FullSizeRender-3.jpg' ),
					),
					array(
						'label' => 'Shopify',
						'title' => esc_html__( 'Shopify store development', 'avix-widgets' ),
						'text'  => esc_html__( 'Our Shopify work includes custom Liquid themes, product discovery, subscriptions and third-party integrations, planned around your catalogue, customer journeys and store operations.', 'avix-widgets' ),
						'image' => array( 'url' => self::UPLOADS . '2026/06/Untitled-design235.png' ),
					),
					array(
						'label' => esc_html__( 'Web apps', 'avix-widgets' ),
						'title' => esc_html__( 'Custom web applications', 'avix-widgets' ),
						'text'  => esc_html__( 'React, Next.js and full-stack builds for customer portals, dashboards and business tools, including the client portal we use with our own clients.', 'avix-widgets' ),
						'image' => array( 'url' => self::UPLOADS . '2026/09/ChatGPT-Image-29-Sept-2026-02_09_56.png' ),
					),
					array(
						'label' => esc_html__( 'Today', 'avix-widgets' ),
						'title' => esc_html__( 'One team, start to finish', 'avix-widgets' ),
						'text'  => esc_html__( 'Design, development and support from one team, for brands around the world.', 'avix-widgets' ),
						'image' => array( 'url' => self::UPLOADS . '2026/03/Untitled-design227.webp' ),
					),
				),
			)
		);

		$this->add_control(
			'item_tag',
			array(
				'label'     => esc_html__( 'Milestone title HTML tag', 'avix-widgets' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'h3',
				'separator' => 'before',
				'options'   => array(
					'h3'  => 'H3',
					'h4'  => 'H4',
					'div' => 'div',
					'p'   => 'p',
				),
			)
		);

		$this->add_control(
			'show_images',
			array(
				'label'       => esc_html__( 'Show images', 'avix-widgets' ),
				'description' => esc_html__( 'Turn off for compact, text-only milestone cards.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->end_controls_section();
	}

	private function controls_track() {
		$this->start_controls_section( 'section_track', array( 'label' => esc_html__( 'Track', 'avix-widgets' ) ) );

		$this->add_control(
			'walk',
			array(
				'label'       => esc_html__( 'Walk with scroll', 'avix-widgets' ),
				'description' => esc_html__( 'Visitors scroll and the track fills in, milestone by milestone. Turn off to show the whole journey lit up from the start.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'show_end',
			array(
				'label'       => esc_html__( 'Show end label', 'avix-widgets' ),
				'description' => esc_html__( 'A small label where the track runs out, e.g. "Today" when your milestones are years.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'end_label',
			array(
				'label'       => esc_html__( 'End label', 'avix-widgets' ),
				'description' => esc_html__( 'Keep it short (about 18 characters): on the horizontal track it floats above the end of the line, and longer text wraps.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Today', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_end' => 'yes' ),
			)
		);

		$this->add_control(
			'end_link',
			array(
				'label'       => esc_html__( 'End label link (optional)', 'avix-widgets' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => 'https://avixdigital.com/contact/',
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_end' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_pal() {
		$this->start_controls_section( 'section_pal', array( 'label' => esc_html__( 'Pixel Character', 'avix-widgets' ) ) );

		$this->add_control(
			'show_pal',
			array(
				'label'       => esc_html__( 'Show pixel character', 'avix-widgets' ),
				'description' => esc_html__( 'The Avix pixel character walks the track as visitors scroll, stops at each milestone, and plants a flag at the last one.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'pal_hi',
			array(
				'label'       => esc_html__( 'Say hi on arrival', 'avix-widgets' ),
				'description' => esc_html__( 'It waves and says "hi" in pixel letters when the section first comes into view.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'condition'   => array( 'show_pal' => 'yes' ),
			)
		);

		$this->add_control(
			'pal_cheer',
			array(
				'label'       => esc_html__( 'Cheer at the end', 'avix-widgets' ),
				'description' => esc_html__( 'Arms up and a little hop when it reaches the last milestone.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'condition'   => array( 'show_pal' => 'yes' ),
			)
		);

		$this->add_control(
			'pal_look',
			array(
				'label'       => esc_html__( 'Look at hovered cards', 'avix-widgets' ),
				'description' => esc_html__( 'While it stands still, it glances toward the card under the mouse or keyboard focus.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'condition'   => array( 'show_pal' => 'yes' ),
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
				'description' => esc_html__( 'Warm paper (default) or dark sets the section apart from white neighbours; choose Light next to warm or dark sections.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'warm',
				'options'     => array(
					'light' => esc_html__( 'Light', 'avix-widgets' ),
					'warm'  => esc_html__( 'Warm paper', 'avix-widgets' ),
					'dark'  => esc_html__( 'Dark', 'avix-widgets' ),
				),
			)
		);

		$colors = array(
			'bg'          => array( esc_html__( 'Background', 'avix-widgets' ), '--jr-bg' ),
			'ink'         => array( esc_html__( 'Headings', 'avix-widgets' ), '--jr-ink' ),
			'muted'       => array( esc_html__( 'Text', 'avix-widgets' ), '--jr-muted' ),
			'accent'      => array( esc_html__( 'Accent (track, nodes, character)', 'avix-widgets' ), '--jr-accent' ),
			'accent_text' => array( esc_html__( 'Highlighted words and labels', 'avix-widgets' ), '--jr-accent-text' ),
			'rail'        => array( esc_html__( 'Track', 'avix-widgets' ), '--jr-rail' ),
			'line'        => array( esc_html__( 'Card borders', 'avix-widgets' ), '--jr-line' ),
			'card'        => array( esc_html__( 'Cards', 'avix-widgets' ), '--jr-card' ),
			'card_on'     => array( esc_html__( 'Reached cards', 'avix-widgets' ), '--jr-card-on' ),
		);
		foreach ( $colors as $key => $color ) {
			$this->add_control(
				$key,
				array(
					'label'     => $color[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-jr' => $color[1] . ': {{VALUE}};' ),
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
				'separator'          => 'before',
				'selectors'          => array( '{{WRAPPER}} .avix-jr' => 'padding-top: {{TOP}}{{UNIT}}; padding-bottom: {{BOTTOM}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'max_width',
			array(
				'label'      => esc_html__( 'Content width', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 760, 'max' => 1600 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-jr' => '--jr-max: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'pal_size',
			array(
				'label'       => esc_html__( 'Character size', 'avix-widgets' ),
				'description' => esc_html__( 'Default: 40px on wide screens, 30px on phones. Multiples of 10 keep the pixels crisp.', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array(
					'px' => array(
						'min'  => 20,
						'max'  => 60,
						'step' => 10,
					),
				),
				'selectors'   => array( '{{WRAPPER}} .avix-jr' => '--jr-pal-w: {{SIZE}}{{UNIT}};' ),
				'condition'   => array( 'show_pal' => 'yes' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_cards',
			array(
				'label' => esc_html__( 'Cards', 'avix-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'card_radius',
			array(
				'label'      => esc_html__( 'Corner radius', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-jr' => '--jr-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'image_ratio',
			array(
				'label'                => esc_html__( 'Image shape', 'avix-widgets' ),
				'description'          => esc_html__( 'Auto picks a shape for each layout: 16:10 in the row, flatter in the long log and on phones.', 'avix-widgets' ),
				'type'                 => Controls_Manager::SELECT,
				'default'              => '',
				'options'              => array(
					''      => esc_html__( 'Auto', 'avix-widgets' ),
					'16-10' => '16:10',
					'16-9'  => '16:9',
					'2-1'   => '2:1',
					'4-3'   => '4:3',
					'1-1'   => '1:1',
				),
				'selectors_dictionary' => array(
					'16-10' => '16 / 10',
					'16-9'  => '16 / 9',
					'2-1'   => '2 / 1',
					'4-3'   => '4 / 3',
					'1-1'   => '1 / 1',
				),
				'selectors'            => array( '{{WRAPPER}} .avix-jr' => '--jr-ratio: {{VALUE}};' ),
				'condition'            => array( 'show_images' => 'yes' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_type',
			array(
				'label' => esc_html__( 'Typography', 'avix-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$type = array(
			'title_typography'      => array( esc_html__( 'Title', 'avix-widgets' ), '{{WRAPPER}} .avix-jr .avix-jr__title' ),
			'text_typography'       => array( esc_html__( 'Intro text', 'avix-widgets' ), '{{WRAPPER}} .avix-jr .avix-jr__intro' ),
			'label_typography'      => array( esc_html__( 'Milestone labels', 'avix-widgets' ), '{{WRAPPER}} .avix-jr .avix-jr__label' ),
			'item_title_typography' => array( esc_html__( 'Milestone titles', 'avix-widgets' ), '{{WRAPPER}} .avix-jr .avix-jr__name' ),
			'item_text_typography'  => array( esc_html__( 'Milestone text', 'avix-widgets' ), '{{WRAPPER}} .avix-jr .avix-jr__text' ),
		);
		foreach ( $type as $name => $group ) {
			$args = array(
				'name'     => $name,
				'label'    => $group[0],
				'selector' => $group[1],
			);
			if ( 'label_typography' === $name ) {
				// The label size also places the pixels on the vertical track.
				$args['fields_options'] = array(
					'font_size' => array(
						'selectors' => array( '{{WRAPPER}} .avix-jr__inner' => '--jr-label-fs: {{SIZE}}{{UNIT}};' ),
					),
				);
			}
			$this->add_group_control( Group_Control_Typography::get_type(), $args );
		}

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ */
	/* Render                                                              */
	/* ------------------------------------------------------------------ */

	protected function render(): void {
		$s     = $this->get_settings_for_display();
		$items = array_values(
			array_filter(
				(array) ( $s['items'] ?? array() ),
				static function ( $row ) {
					return '' !== trim( (string) ( $row['label'] ?? '' ) . (string) ( $row['title'] ?? '' ) . (string) ( $row['text'] ?? '' ) );
				}
			)
		);

		if ( ! $items ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="elementor-alert elementor-alert-info">' . esc_html__( 'Journey: add at least one milestone with a label, title or text.', 'avix-widgets' ) . '</div>';
			}
			return;
		}

		$count    = count( $items );
		$pal      = 'yes' === ( $s['show_pal'] ?? '' );
		$images   = 'yes' === ( $s['show_images'] ?? '' );
		$theme    = in_array( $s['theme'] ?? '', array( 'light', 'warm', 'dark' ), true ) ? $s['theme'] : 'warm';
		$tag      = Utils::validate_html_tag( $s['title_tag'] ?? 'h2' );
		$item_tag = Utils::validate_html_tag( $s['item_tag'] ?? 'h3' );
		$title    = trim( (string) ( $s['title'] ?? '' ) );
		$eyebrow  = trim( (string) ( $s['eyebrow'] ?? '' ) );
		$intro    = trim( (string) ( $s['text'] ?? '' ) );
		$end      = 'yes' === ( $s['show_end'] ?? '' ) ? trim( (string) ( $s['end_label'] ?? '' ) ) : '';

		// Pictures are resolved up front: a missing one gives a text-only card,
		// and the share of cards with a picture sets the log's rhythm.
		$pictures = 0;
		foreach ( $items as $i => $item ) {
			$name                 = trim( (string) ( $item['title'] ?? '' ) );
			$items[ $i ]['_html'] = $images ? $this->image_html( (array) ( $item['image'] ?? array() ), '' !== $name ? $name : trim( (string) ( $item['label'] ?? '' ) ) ) : '';
			$pictures            += '' !== $items[ $i ]['_html'] ? 1 : 0;
		}

		$classes = array( 'avix-jr', 'avix-jr--' . $theme, 'avix-jr--n' . min( $count, 9 ) );
		if ( $pictures && $pictures * 2 >= $count ) {
			// Mostly picture cards: text-only cards match their height in the log.
			$classes[] = 'has-pictures';
		}
		if ( $count <= self::ROW_MAX ) {
			// Horizontal track on wide screens. Fewer than 3 milestones still use
			// 3 columns, so a single card never stretches across the page.
			$classes[] = 'avix-jr--row';
		}
		if ( $pal ) {
			$classes[] = 'has-pal';
		}
		if ( '' !== $end ) {
			$classes[] = 'has-end';
		}

		$config = array(
			'walk'  => 'yes' === ( $s['walk'] ?? '' ),
			'hi'    => $pal && 'yes' === ( $s['pal_hi'] ?? '' ),
			'cheer' => $pal && 'yes' === ( $s['pal_cheer'] ?? '' ),
			'look'  => $pal && 'yes' === ( $s['pal_look'] ?? '' ),
		);

		$this->add_render_attribute(
			'root',
			array(
				'class'        => $classes,
				'data-avix-jr' => wp_json_encode( $config ),
				'style'        => '--jr-cols:' . max( 3, $count ) . ';--jr-count:' . $count . ';',
			)
		);
		if ( '' !== $title ) {
			$this->add_render_attribute( 'root', 'aria-labelledby', 'avix-jr-title-' . $this->get_id() );
		} else {
			$this->add_render_attribute( 'root', 'aria-label', '' !== $eyebrow ? $eyebrow : esc_html__( 'Our journey', 'avix-widgets' ) );
		}
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="avix-jr__inner">
				<?php if ( '' !== $eyebrow || '' !== $title || '' !== $intro ) : ?>
					<header class="avix-jr__head">
						<div class="avix-jr__head-main">
							<?php if ( '' !== $eyebrow ) : ?>
								<p class="avix-jr__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
							<?php endif; ?>
							<?php if ( '' !== $title ) : ?>
								<<?php echo esc_attr( $tag ); ?> class="avix-jr__title" id="avix-jr-title-<?php echo esc_attr( $this->get_id() ); ?>"><?php echo $this->accent_html( $title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in accent_html(). ?></<?php echo esc_attr( $tag ); ?>>
							<?php endif; ?>
						</div>
						<?php if ( '' !== $intro ) : ?>
							<p class="avix-jr__intro"><?php echo esc_html( $intro ); ?></p>
						<?php endif; ?>
					</header>
				<?php endif; ?>

				<div class="avix-jr__track" data-jr-track>
					<div class="avix-jr__rail" aria-hidden="true">
						<span class="avix-jr__line"></span>
						<span class="avix-jr__tail"></span>
						<span class="avix-jr__fill" data-jr-fill></span>
					</div>

					<?php if ( $pal ) : ?>
						<span class="avix-jr__rider" data-jr-rider aria-hidden="true">
							<span class="avix-jr__lift">
								<?php
								echo Pixel_Pal::render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup built and escaped in Pixel_Pal::render().
									array(
										'class' => 'avix-jr__pal',
										'hi'    => true,
										'prop'  => 'flag',
									)
								);
								?>
							</span>
						</span>
					<?php endif; ?>

					<ol class="avix-jr__list" role="list">
						<?php foreach ( $items as $i => $item ) : ?>
							<?php $this->render_item( $item, (int) $i, $item_tag ); ?>
						<?php endforeach; ?>
					</ol>

					<?php if ( '' !== $end ) : ?>
						<?php $this->render_end( $end, (array) ( $s['end_link'] ?? array() ) ); ?>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<?php
	}

	private function render_item( array $item, $index, $item_tag ) {
		$label    = trim( (string) ( $item['label'] ?? '' ) );
		$name     = trim( (string) ( $item['title'] ?? '' ) );
		$text     = trim( (string) ( $item['text'] ?? '' ) );
		$link     = (array) ( $item['link'] ?? array() );
		$class    = 'avix-jr__item elementor-repeater-item-' . sanitize_html_class( (string) ( $item['_id'] ?? '' ) );
		$img_html = (string) ( $item['_html'] ?? '' );
		if ( '' !== $img_html ) {
			$class .= ' has-image';
		}
		?>
		<li class="<?php echo esc_attr( $class ); ?>" style="--i:<?php echo (int) $index; ?>" data-jr-item>
			<span class="avix-jr__node" data-jr-node aria-hidden="true"></span>
			<div class="avix-jr__card">
				<div class="avix-jr__body">
					<?php if ( '' !== $label ) : ?>
						<p class="avix-jr__label"><?php echo esc_html( $label ); ?></p>
					<?php endif; ?>
					<?php if ( '' !== $name ) : ?>
						<<?php echo esc_attr( $item_tag ); ?> class="avix-jr__name"><?php echo esc_html( $name ); ?></<?php echo esc_attr( $item_tag ); ?>>
					<?php endif; ?>
					<?php if ( '' !== $text ) : ?>
						<p class="avix-jr__text"><?php echo esc_html( $text ); ?></p>
					<?php endif; ?>
					<?php
					if ( ! empty( $link['url'] ) ) {
						$key = 'link-' . $index;
						$this->add_render_attribute( $key, 'class', 'avix-jr__link' );
						$this->add_link_attributes( $key, $link );
						$link_text = trim( (string) ( $item['link_text'] ?? '' ) );
						$link_text = '' !== $link_text ? $link_text : esc_html__( 'Learn more', 'avix-widgets' );
						?>
						<a <?php $this->print_render_attribute_string( $key ); ?>>
							<span><?php echo esc_html( $link_text ); ?></span>
							<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
						</a>
					<?php } ?>
				</div>
				<?php if ( '' !== $img_html ) : ?>
					<div class="avix-jr__media avix-jr__media--<?php echo esc_attr( in_array( $item['image_focus'] ?? '', array( 'top', 'bottom' ), true ) ? $item['image_focus'] : 'center' ); ?>">
						<?php echo $img_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built and escaped in image_html(). ?>
					</div>
				<?php endif; ?>
			</div>
		</li>
		<?php
	}

	/**
	 * The card picture's <img>, or '' when there is nothing usable: no image,
	 * a deleted or non-image attachment without a URL, or a URL that does not
	 * survive esc_url() (e.g. javascript:).
	 */
	private function image_html( array $image, $fallback_alt ) {
		if ( empty( $image['id'] ) && empty( $image['url'] ) ) {
			return '';
		}
		$id  = $this->media_id( $image );
		$alt = '';
		if ( $id ) {
			$alt = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
		}
		$alt = '' !== $alt ? $alt : $fallback_alt;

		if ( $id ) {
			$html = wp_get_attachment_image(
				$id,
				'medium_large',
				false,
				array(
					'class'    => 'avix-jr__img',
					'alt'      => $alt,
					'loading'  => 'lazy',
					'decoding' => 'async',
					'sizes'    => '(max-width: 600px) 86vw, (max-width: 999px) 36vw, 300px',
				)
			);
			if ( '' !== $html ) {
				return $html;
			}
		}
		$url = esc_url( (string) ( $image['url'] ?? '' ) );
		if ( '' === $url ) {
			return '';
		}
		return sprintf(
			'<img class="avix-jr__img" src="%s" alt="%s" loading="lazy" decoding="async">',
			$url,
			esc_attr( $alt )
		);
	}

	private function render_end( $label, array $link ) {
		$tag = empty( $link['url'] ) ? 'span' : 'a';
		$this->add_render_attribute( 'end', 'class', 'avix-jr__end-label' );
		if ( 'a' === $tag ) {
			$this->add_link_attributes( 'end', $link );
		}
		?>
		<div class="avix-jr__end" data-jr-end>
			<span class="avix-jr__end-node" aria-hidden="true"></span>
			<<?php echo esc_attr( $tag ); ?> <?php $this->print_render_attribute_string( 'end' ); ?>><?php echo esc_html( $label ); ?></<?php echo esc_attr( $tag ); ?>>
		</div>
		<?php
	}

	/**
	 * Escapes the text, turns [words] into highlighted spans and new lines into breaks.
	 */
	private function accent_html( $text ) {
		$lines = preg_split( '/\r\n|\r|\n/', (string) $text );
		$lines = array_map(
			static function ( $line ) {
				return preg_replace( '/\[([^\[\]]+)\]/u', '<span class="avix-jr__accent">$1</span>', esc_html( trim( $line ) ) );
			},
			array_filter( (array) $lines, static function ( $line ) {
				return '' !== trim( $line );
			} )
		);
		return implode( ' <br>', $lines );
	}
}
