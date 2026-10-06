<?php
/**
 * Values: "How we work" principles as a bento grid of cards. Each card has a
 * pixel icon that builds itself from falling pixels, and the Avix pixel
 * character hops onto the top edge of whichever card is hovered or focused.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;

defined( 'ABSPATH' ) || exit;

class Values extends Widget_Base {

	/**
	 * Saved values of icons that were renamed, mapped to their current key.
	 */
	const ICON_ALIASES = array( 'compass' => 'pin' );

	/**
	 * Pixel icons on an 8×8 grid: '#' is an ink pixel, 'o' an orange pixel.
	 */
	const ICONS = array(
		'target'  => array(
			'..####..',
			'.#....#.',
			'#......#',
			'#..oo..#',
			'#..oo..#',
			'#......#',
			'.#....#.',
			'..####..',
		),
		'chat'    => array(
			'.######.',
			'#......#',
			'#.o.o.o#',
			'#......#',
			'.######.',
			'..##....',
			'..#.....',
			'........',
		),
		'blocks'  => array(
			'..oooo..',
			'..oooo..',
			'..oooo..',
			'........',
			'###..###',
			'###..###',
			'###..###',
			'........',
		),
		'shield'  => array(
			'########',
			'#......#',
			'#.....o#',
			'#o...o.#',
			'#.o.o..#',
			'.#.o..#.',
			'..#..#..',
			'...##...',
		),
		'rocket'  => array(
			'...##...',
			'..####..',
			'..#oo#..',
			'..####..',
			'.######.',
			'#.####.#',
			'#..##..#',
			'...oo...',
		),
		'pin'     => array(
			'..oooo..',
			'.oooooo.',
			'.oo..oo.',
			'.oo..oo.',
			'.oooooo.',
			'..oooo..',
			'...oo...',
			'.######.',
		),
		'bolt'    => array(
			'....oo..',
			'...oo...',
			'..oo....',
			'.oooooo.',
			'....oo..',
			'...oo...',
			'..oo....',
			'.o......',
		),
		'heart'   => array(
			'........',
			'.oo..oo.',
			'oooooooo',
			'oooooooo',
			'.oooooo.',
			'..oooo..',
			'...oo...',
			'........',
		),
		'eye'     => array(
			'........',
			'..####..',
			'.#....#.',
			'#..oo..#',
			'#..oo..#',
			'.#....#.',
			'..####..',
			'........',
		),
		'gear'    => array(
			'...##...',
			'.#.##.#.',
			'..####..',
			'###..###',
			'###..###',
			'..####..',
			'.#.##.#.',
			'...##...',
		),
	);

	public function get_name(): string {
		return 'avix-values';
	}

	public function get_title(): string {
		return esc_html__( 'Values', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-gallery-grid';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'values', 'principles', 'how we work', 'bento', 'features', 'about', 'avix' );
	}

	public function get_style_depends(): array {
		return array( 'avix-values' );
	}

	public function get_script_depends(): array {
		return array( 'avix-values' );
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
		$this->controls_buddy();
		$this->controls_style();
	}

	private function controls_header() {
		$this->start_controls_section( 'section_header', array( 'label' => esc_html__( 'Header', 'avix-widgets' ) ) );

		$this->add_control(
			'eyebrow',
			array(
				'label'   => esc_html__( 'Eyebrow', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'How we work', 'avix-widgets' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Title', 'avix-widgets' ),
				'description' => esc_html__( 'Wrap words in [brackets] to highlight them in orange. Press Enter for a new line.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 3,
				'default'     => "Clear plans.\n[Honest updates.]\nWork that lasts.",
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
				'default' => esc_html__( 'The way we run every project, whether it’s a Shopify store, a WordPress or Webflow site, or a custom web app.', 'avix-widgets' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'head_layout',
			array(
				'label'   => esc_html__( 'Header layout', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'split',
				'options' => array(
					'split'   => esc_html__( 'Title left, text right', 'avix-widgets' ),
					'stacked' => esc_html__( 'Text under the title', 'avix-widgets' ),
				),
			)
		);

		$this->end_controls_section();
	}

	private function controls_items() {
		$this->start_controls_section( 'section_values', array( 'label' => esc_html__( 'Values', 'avix-widgets' ) ) );

		$repeater = new Repeater();

		$repeater->add_control(
			'icon',
			array(
				'label'   => esc_html__( 'Pixel icon', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'target',
				'options' => array(
					'target'  => esc_html__( 'Target', 'avix-widgets' ),
					'chat'    => esc_html__( 'Chat', 'avix-widgets' ),
					'blocks'  => esc_html__( 'Building blocks', 'avix-widgets' ),
					'shield'  => esc_html__( 'Shield', 'avix-widgets' ),
					'rocket'  => esc_html__( 'Rocket', 'avix-widgets' ),
					'pin'     => esc_html__( 'Map pin', 'avix-widgets' ),
					'bolt'    => esc_html__( 'Lightning bolt', 'avix-widgets' ),
					'heart'   => esc_html__( 'Heart', 'avix-widgets' ),
					'eye'     => esc_html__( 'Eye', 'avix-widgets' ),
					'gear'    => esc_html__( 'Gear', 'avix-widgets' ),
					'custom'  => esc_html__( 'Custom icon…', 'avix-widgets' ),
					'none'    => esc_html__( 'No icon', 'avix-widgets' ),
				),
			)
		);

		$repeater->add_control(
			'icon_custom',
			array(
				'label'     => esc_html__( 'Custom icon', 'avix-widgets' ),
				'type'      => Controls_Manager::ICONS,
				'default'   => array(
					'value'   => 'fas fa-star',
					'library' => 'fa-solid',
				),
				'condition' => array( 'icon' => 'custom' ),
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
				'rows'    => 3,
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'wide',
			array(
				'label'       => esc_html__( 'Wide card', 'avix-widgets' ),
				'description' => esc_html__( 'Takes two thirds of the row on desktop and shows the icon large on graph paper. Rows always fill up, whatever you pick.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
				'separator'   => 'before',
			)
		);

		$repeater->add_control(
			'tone',
			array(
				'label'   => esc_html__( 'Card style', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'soft',
				'options' => array(
					'soft'  => esc_html__( 'Warm paper', 'avix-widgets' ),
					'white' => esc_html__( 'White with a border', 'avix-widgets' ),
					'dark'  => esc_html__( 'Dark', 'avix-widgets' ),
				),
			)
		);

		$repeater->add_control(
			'link',
			array(
				'label'       => esc_html__( 'Link (optional)', 'avix-widgets' ),
				'description' => esc_html__( 'Makes the whole card clickable.', 'avix-widgets' ),
				'type'        => Controls_Manager::URL,
				'separator'   => 'before',
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'link_text',
			array(
				'label'   => esc_html__( 'Link text', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Learn more', 'avix-widgets' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'items',
			array(
				'label'       => esc_html__( 'Cards', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => array(
					array(
						'icon'  => 'target',
						'title' => esc_html__( 'Goals before pixels', 'avix-widgets' ),
						'text'  => esc_html__( 'We start with your goals, then plan the design, development and integrations needed to get there.', 'avix-widgets' ),
						'wide'  => 'yes',
						'tone'  => 'dark',
					),
					array(
						'icon'  => 'chat',
						'title' => esc_html__( 'Talk to the people building it', 'avix-widgets' ),
						'text'  => esc_html__( 'Talk directly with the team planning and building your project, from the first brief through design, development and launch.', 'avix-widgets' ),
						'tone'  => 'soft',
					),
					array(
						'icon'  => 'blocks',
						'title' => esc_html__( 'Scope agreed up front', 'avix-widgets' ),
						'text'  => esc_html__( 'We agree the scope, content and integrations before building, so the project has a clear plan from day one.', 'avix-widgets' ),
						'tone'  => 'soft',
					),
					array(
						'icon'  => 'shield',
						'title' => esc_html__( 'Built to be managed', 'avix-widgets' ),
						'text'  => esc_html__( 'Fast pages, clear content and a solid technical SEO foundation, on a site your team can update with confidence.', 'avix-widgets' ),
						'wide'  => 'yes',
						'tone'  => 'soft',
					),
				),
			)
		);

		$this->add_control(
			'card_title_tag',
			array(
				'label'     => esc_html__( 'Card title HTML tag', 'avix-widgets' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'h3',
				'options'   => array(
					'h3'  => 'H3',
					'h4'  => 'H4',
					'div' => 'div',
					'p'   => 'p',
				),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'show_numbers',
			array(
				'label'   => esc_html__( 'Show numbers', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'show_build',
			array(
				'label'       => esc_html__( 'Build the pixel icons', 'avix-widgets' ),
				'description' => esc_html__( 'Visitors see each icon drop into place pixel by pixel when the cards appear and again when a card is hovered.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->end_controls_section();
	}

	private function controls_buddy() {
		$this->start_controls_section( 'section_pal', array( 'label' => esc_html__( 'Pixel Character', 'avix-widgets' ) ) );

		$this->add_control(
			'show_pal',
			array(
				'label'       => esc_html__( 'Show pixel character', 'avix-widgets' ),
				'description' => esc_html__( 'The Avix pixel character sits on the top edge of the first card with its legs dangling.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'pal_hop',
			array(
				'label'       => esc_html__( 'Hop to the hovered card', 'avix-widgets' ),
				'description' => esc_html__( 'Hover or tab to a card and it hops over to sit on that card; it goes back to the first card when the pointer leaves. On phones it stays on the first card and waves.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'condition'   => array( 'show_pal' => 'yes' ),
			)
		);

		$this->add_control(
			'pal_hi',
			array(
				'label'       => esc_html__( 'Say hi when it arrives', 'avix-widgets' ),
				'description' => esc_html__( 'It drops onto the first card and says “hi” in pixel letters the first time the section is seen.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'condition'   => array( 'show_pal' => 'yes' ),
			)
		);

		$this->add_control(
			'pal_wave',
			array(
				'label'       => esc_html__( 'Wave now and then', 'avix-widgets' ),
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
				'description' => esc_html__( 'Dark sets every colour below for a dark section; any colour you pick still wins.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'light',
				'options'     => array(
					'light' => esc_html__( 'Light', 'avix-widgets' ),
					'dark'  => esc_html__( 'Dark', 'avix-widgets' ),
				),
			)
		);

		$colors = array(
			'bg'          => array( esc_html__( 'Background', 'avix-widgets' ), '--vl-bg' ),
			'ink'         => array( esc_html__( 'Headings', 'avix-widgets' ), '--vl-ink' ),
			'muted'       => array( esc_html__( 'Text', 'avix-widgets' ), '--vl-muted' ),
			'accent'      => array( esc_html__( 'Accent (pixels and character)', 'avix-widgets' ), '--vl-accent' ),
			'accent_text' => array( esc_html__( 'Highlighted words and card links', 'avix-widgets' ), '--vl-accent-text', '--vl-link' ),
			'card'        => array( esc_html__( 'Warm paper cards', 'avix-widgets' ), '--vl-card' ),
			'card_line'   => array( esc_html__( 'Warm paper card borders', 'avix-widgets' ), '--vl-card-line' ),
			'card_ink'    => array( esc_html__( 'Warm paper card titles and icons', 'avix-widgets' ), '--vl-card-ink' ),
			'card_muted'  => array( esc_html__( 'Warm paper card text', 'avix-widgets' ), '--vl-card-muted' ),
			'white'       => array( esc_html__( 'White cards', 'avix-widgets' ), '--vl-white' ),
			'white_ink'   => array( esc_html__( 'White card titles and icons', 'avix-widgets' ), '--vl-white-ink' ),
			'white_muted' => array( esc_html__( 'White card text', 'avix-widgets' ), '--vl-white-muted' ),
			'dark'        => array( esc_html__( 'Dark cards', 'avix-widgets' ), '--vl-dark' ),
			'tile'        => array( esc_html__( 'Icon tiles (warm paper cards)', 'avix-widgets' ), '--vl-tile-bg' ),
		);
		foreach ( $colors as $key => $color ) {
			$css = '';
			foreach ( array_slice( $color, 1 ) as $var ) {
				$css .= $var . ': {{VALUE}};';
			}
			$this->add_control(
				$key,
				array(
					'label'     => $color[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-vl' => $css ),
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
				'selectors'          => array( '{{WRAPPER}} .avix-vl' => 'padding-top: {{TOP}}{{UNIT}}; padding-bottom: {{BOTTOM}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'max_width',
			array(
				'label'      => esc_html__( 'Content width', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 640, 'max' => 1600 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-vl' => '--vl-max: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'gap',
			array(
				'label'       => esc_html__( 'Gap between cards', 'avix-widgets' ),
				'description' => esc_html__( 'With the pixel character shown, the gap on desktop and tablet never drops below the room it needs to sit between rows (38px at the default size).', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 48 ) ),
				'selectors'   => array( '{{WRAPPER}} .avix-vl' => '--vl-gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'card_height',
			array(
				'label'       => esc_html__( 'Card minimum height', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 160, 'max' => 520 ) ),
				'description' => esc_html__( 'Phones always fit the content.', 'avix-widgets' ),
				'selectors'   => array( '{{WRAPPER}} .avix-vl' => '--vl-card-h: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'radius',
			array(
				'label'      => esc_html__( 'Card corner radius', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-vl' => '--vl-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'pal_size',
			array(
				'label'       => esc_html__( 'Character size', 'avix-widgets' ),
				'description' => esc_html__( 'Steps of 10 keep every pixel perfectly sharp.', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array(
					'px' => array(
						'min'  => 20,
						'max'  => 60,
						'step' => 10,
					),
				),
				'selectors'   => array( '{{WRAPPER}} .avix-vl' => '--vl-pal-w: {{SIZE}}{{UNIT}};' ),
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

		$fonts = array(
			'eyebrow_typography'    => array( esc_html__( 'Eyebrow', 'avix-widgets' ), '{{WRAPPER}} .avix-vl .avix-vl__eyebrow' ),
			'title_typography'      => array( esc_html__( 'Title', 'avix-widgets' ), '{{WRAPPER}} .avix-vl .avix-vl__title' ),
			'text_typography'       => array( esc_html__( 'Text', 'avix-widgets' ), '{{WRAPPER}} .avix-vl .avix-vl__text' ),
			'card_title_typography' => array( esc_html__( 'Card titles', 'avix-widgets' ), '{{WRAPPER}} .avix-vl .avix-vl__name' ),
			'card_text_typography'  => array( esc_html__( 'Card text', 'avix-widgets' ), '{{WRAPPER}} .avix-vl .avix-vl__desc' ),
		);
		foreach ( $fonts as $name => $font ) {
			$this->add_group_control(
				Group_Control_Typography::get_type(),
				array(
					'name'     => $name,
					'label'    => $font[0],
					'selector' => $font[1],
				)
			);
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
					return '' !== trim( (string) ( $row['title'] ?? '' ) ) || '' !== trim( (string) ( $row['text'] ?? '' ) );
				}
			)
		);

		if ( ! $items ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="elementor-alert elementor-alert-info">' . esc_html__( 'Values: add at least one card with a title or text.', 'avix-widgets' ) . '</div>';
			}
			return;
		}

		$pal      = 'yes' === ( $s['show_pal'] ?? '' );
		$title    = trim( (string) ( $s['title'] ?? '' ) );
		$eyebrow  = trim( (string) ( $s['eyebrow'] ?? '' ) );
		$text     = trim( (string) ( $s['text'] ?? '' ) );
		$tag      = Utils::validate_html_tag( $s['title_tag'] ?? 'h2' );
		$title_id = 'avix-vl-title-' . $this->get_id();
		$layout   = 'stacked' === ( $s['head_layout'] ?? '' ) ? 'stacked' : 'split';
		$spans    = self::spans( $items );

		$classes = array( 'avix-vl', 'avix-vl--head-' . $layout );
		if ( 'dark' === ( $s['theme'] ?? '' ) ) {
			$classes[] = 'avix-vl--dark';
		}
		if ( $pal ) {
			$classes[] = 'avix-vl--pal';
		}

		$this->add_render_attribute(
			'root',
			array(
				'class'        => $classes,
				'data-avix-vl' => wp_json_encode(
					array(
						'pal'   => $pal,
						'hop'   => $pal && 'yes' === ( $s['pal_hop'] ?? '' ),
						'hi'    => $pal && 'yes' === ( $s['pal_hi'] ?? '' ),
						'wave'  => $pal && 'yes' === ( $s['pal_wave'] ?? '' ),
						'build' => 'yes' === ( $s['show_build'] ?? '' ),
					)
				),
			)
		);
		if ( '' !== $title ) {
			$this->add_render_attribute( 'root', 'aria-labelledby', $title_id );
		} elseif ( '' !== $eyebrow ) {
			$this->add_render_attribute( 'root', 'aria-label', $eyebrow );
		}
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="avix-vl__inner">
				<?php if ( '' !== $eyebrow || '' !== $title || '' !== $text ) : ?>
					<header class="avix-vl__head">
						<?php if ( '' !== $eyebrow || '' !== $title ) : ?>
							<div class="avix-vl__head-main">
								<?php if ( '' !== $eyebrow ) : ?>
									<p class="avix-vl__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
								<?php endif; ?>
								<?php if ( '' !== $title ) : ?>
									<<?php echo esc_attr( $tag ); ?> class="avix-vl__title" id="<?php echo esc_attr( $title_id ); ?>"><?php echo $this->title_html( $title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in title_html(). ?></<?php echo esc_attr( $tag ); ?>>
								<?php endif; ?>
							</div>
						<?php endif; ?>
						<?php if ( '' !== $text ) : ?>
							<p class="avix-vl__text"><?php echo esc_html( $text ); ?></p>
						<?php endif; ?>
					</header>
				<?php endif; ?>

				<div class="avix-vl__board" data-vl-board>
					<ul class="avix-vl__grid" role="list">
						<?php foreach ( $items as $i => $item ) : ?>
							<li class="avix-vl__item" style="<?php echo esc_attr( '--i:' . (int) $i . ';--vl-span:' . (int) $spans[ $i ][0] . ';--vl-span-md:' . (int) $spans[ $i ][1] . ';' ); ?>">
								<?php $this->render_card( $s, $item, $i, count( $items ), $spans[ $i ] ); ?>
							</li>
						<?php endforeach; ?>
					</ul>
					<?php if ( $pal ) : ?>
						<span class="avix-vl__mover" data-vl-mover aria-hidden="true">
							<?php
							echo \AvixWidgets\Pixel_Pal::render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Pixel_Pal::render().
								array(
									'class' => 'avix-vl__pal',
									'hi'    => true,
								)
							);
							?>
						</span>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * One value card.
	 *
	 * @param array $s     Widget settings.
	 * @param array $item  Repeater row.
	 * @param int   $index Position.
	 * @param int   $total Number of cards.
	 * @param array $span  Desktop and tablet column spans.
	 */
	private function render_card( array $s, array $item, $index, $total, array $span ) {
		$name     = trim( (string) ( $item['title'] ?? '' ) );
		$desc     = trim( (string) ( $item['text'] ?? '' ) );
		$tone     = in_array( $item['tone'] ?? '', array( 'soft', 'white', 'dark' ), true ) ? $item['tone'] : 'soft';
		$icon     = self::icon_key( $item );
		$link     = (array) ( $item['link'] ?? array() );
		$has_link = ! empty( $link['url'] );
		$key      = 'card-link-' . $index;
		$name_tag = Utils::validate_html_tag( $s['card_title_tag'] ?? 'h3' );
		$has_icon = isset( self::ICONS[ $icon ] ) || ( 'custom' === $icon && ! empty( $item['icon_custom']['value'] ) );
		$has_num  = 'yes' === ( $s['show_numbers'] ?? '' );

		$classes = array(
			'avix-vl__card',
			'avix-vl__card--' . $tone,
			'elementor-repeater-item-' . sanitize_html_class( (string) ( $item['_id'] ?? '' ) ),
		);
		if ( $span[0] >= 4 ) {
			$classes[] = 'avix-vl__card--wide';
		}
		if ( $span[1] >= 2 ) {
			$classes[] = 'avix-vl__card--wide-md';
		}
		if ( $has_icon ) {
			$classes[] = 'has-icon';
		}
		if ( $has_link ) {
			$classes[] = 'has-link';
		}
		?>
		<article class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-vl-card>
			<span class="avix-vl__perch" data-vl-perch aria-hidden="true"></span>
			<div class="avix-vl__main">
				<?php if ( $has_icon || $has_num ) : ?>
					<div class="avix-vl__top">
						<?php if ( $has_icon ) : ?>
							<span class="avix-vl__tile" aria-hidden="true"><?php $this->icon_html( $item, 'avix-vl__px' ); ?></span>
						<?php endif; ?>
						<?php if ( $has_num ) : ?>
							<span class="avix-vl__num" aria-hidden="true"><?php echo esc_html( sprintf( '%02d', $index + 1 ) ); ?><span class="avix-vl__of"><?php echo esc_html( sprintf( '/%02d', $total ) ); ?></span></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<div class="avix-vl__body">
					<?php if ( '' !== $name ) : ?>
						<<?php echo esc_attr( $name_tag ); ?> class="avix-vl__name"><?php echo esc_html( $name ); ?></<?php echo esc_attr( $name_tag ); ?>>
					<?php endif; ?>
					<?php if ( '' !== $desc ) : ?>
						<p class="avix-vl__desc"><?php echo esc_html( $desc ); ?></p>
					<?php endif; ?>
					<?php
					if ( $has_link ) :
						$this->add_render_attribute( $key, 'class', 'avix-vl__link' );
						$this->add_link_attributes( $key, $link );
						$label = trim( (string) ( $item['link_text'] ?? '' ) );
						$label = '' !== $label ? $label : esc_html__( 'Learn more', 'avix-widgets' );
						?>
						<a <?php $this->print_render_attribute_string( $key ); ?>>
							<span class="avix-vl__link-text"><?php echo esc_html( $label ); ?></span>
							<?php if ( '' !== $name ) : ?>
								<span class="avix-vl__sr"><?php echo esc_html( ': ' . $name ); ?></span>
							<?php endif; ?>
							<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
						</a>
					<?php endif; ?>
				</div>
			</div>
			<?php if ( $has_icon ) : ?>
				<div class="avix-vl__canvas" aria-hidden="true"><?php $this->icon_html( $item, 'avix-vl__px avix-vl__px--big' ); ?></div>
			<?php endif; ?>
		</article>
		<?php
	}

	/**
	 * Prints a pixel icon (or the custom icon).
	 *
	 * @param array  $item  Repeater row.
	 * @param string $class SVG classes.
	 */
	private function icon_html( array $item, $class ) {
		$icon = self::icon_key( $item );
		if ( 'custom' === $icon ) {
			echo '<span class="avix-vl__custom">';
			Icons_Manager::render_icon( $item['icon_custom'], array( 'aria-hidden' => 'true' ) );
			echo '</span>';
			return;
		}
		echo self::pixel_svg( $icon, $class ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup built from self::ICONS.
	}

	/**
	 * The card's icon key, with renamed icons mapped to their current key.
	 *
	 * @param array $item Repeater row.
	 */
	private static function icon_key( array $item ) {
		$icon = (string) ( $item['icon'] ?? 'target' );
		return self::ICON_ALIASES[ $icon ] ?? $icon;
	}

	/**
	 * Builds an 8×8 pixel icon. Every pixel carries its build order (--d) and
	 * how many whole grid units it falls (--s, also the animation's step
	 * count), so CSS drops the pixels into place row by row from the bottom up
	 * without ever leaving the grid.
	 *
	 * @param string $name  Icon key.
	 * @param string $class SVG classes.
	 */
	private static function pixel_svg( $name, $class ) {
		if ( ! isset( self::ICONS[ $name ] ) ) {
			return '';
		}
		$rects = '';
		foreach ( self::ICONS[ $name ] as $y => $row ) {
			$len = strlen( $row );
			for ( $x = 0; $x < $len; $x++ ) {
				$char = $row[ $x ];
				if ( '#' !== $char && 'o' !== $char ) {
					continue;
				}
				$order  = ( 7 - $y ) * 2 + ( ( $x * 3 + $y ) % 4 );
				$fall   = 2 + ( ( $x * 7 + $y * 3 ) % 3 );
				$rects .= sprintf(
					'<rect class="%1$s" x="%2$d" y="%3$d" width="1" height="1" style="--d:%4$d;--s:%5$d"/>',
					'o' === $char ? 'a' : 'i',
					$x,
					$y,
					$order,
					$fall
				);
			}
		}
		return '<svg class="' . esc_attr( $class ) . '" viewBox="0 0 8 8" focusable="false" aria-hidden="true">' . $rects . '</svg>';
	}

	/**
	 * Column spans for the bento grid: [ desktop span of 6, tablet span of 2 ].
	 * Wide cards take 4 of 6 columns, normal cards 2. A row that can't be
	 * filled is stretched so the grid never leaves holes, and a lone card after
	 * a row of three becomes two pairs instead.
	 *
	 * @param array $items Repeater rows.
	 */
	private static function spans( array $items ) {
		$wide = array();
		foreach ( $items as $item ) {
			$wide[] = 'yes' === ( $item['wide'] ?? '' );
		}

		// Desktop: pack into rows of 6.
		$rows = array();
		$row  = array();
		$used = 0;
		foreach ( $wide as $i => $is_wide ) {
			$w = $is_wide ? 4 : 2;
			if ( $used + $w > 6 ) {
				$rows[] = $row;
				$row    = array();
				$used   = 0;
			}
			$row[ $i ] = $w;
			$used     += $w;
		}
		if ( $row ) {
			$rows[] = $row;
		}

		$count = count( $rows );
		if ( $count > 1 ) {
			$last = $rows[ $count - 1 ];
			$prev = $rows[ $count - 2 ];
			if ( 1 === count( $last ) && 2 === reset( $last ) && 3 === count( $prev ) && 6 === array_sum( $prev ) ) {
				$keys                 = array_merge( array_keys( $prev ), array_keys( $last ) );
				$rows[ $count - 2 ]   = array(
					$keys[0] => 3,
					$keys[1] => 3,
				);
				$rows[ $count - 1 ]   = array(
					$keys[2] => 3,
					$keys[3] => 3,
				);
			}
		}

		$desktop = array();
		foreach ( $rows as $row ) {
			$sum = array_sum( $row );
			if ( $sum < 6 ) {
				if ( 1 === count( $row ) ) {
					$row[ key( $row ) ] = 6;
				} else {
					$share = (int) floor( 6 / count( $row ) );
					foreach ( $row as $i => $w ) {
						$row[ $i ] = $share;
					}
				}
			}
			foreach ( $row as $i => $w ) {
				$desktop[ $i ] = $w;
			}
		}

		// Tablet: two columns, wide cards take the full row.
		$tablet = array();
		$open   = null;
		foreach ( $wide as $i => $is_wide ) {
			if ( $is_wide ) {
				if ( null !== $open ) {
					$tablet[ $open ] = 2;
					$open            = null;
				}
				$tablet[ $i ] = 2;
			} elseif ( null === $open ) {
				$open         = $i;
				$tablet[ $i ] = 1;
			} else {
				$tablet[ $i ] = 1;
				$open         = null;
			}
		}
		if ( null !== $open ) {
			$tablet[ $open ] = 2;
		}

		$out = array();
		foreach ( array_keys( $items ) as $i ) {
			$out[ $i ] = array( $desktop[ $i ] ?? 2, $tablet[ $i ] ?? 1 );
		}
		return $out;
	}

	/**
	 * Escapes the title, turns [words] into highlighted spans and line breaks
	 * into <br>.
	 *
	 * @param string $text Title.
	 */
	private function title_html( $text ) {
		$lines = preg_split( '/\r\n|\r|\n/', $text );
		$lines = array_filter( array_map( 'trim', $lines ), 'strlen' );
		$html  = array();
		foreach ( $lines as $line ) {
			$html[] = preg_replace( '/\[([^\[\]]+)\]/u', '<span class="avix-vl__accent">$1</span>', esc_html( $line ) );
		}
		return implode( ' <br>', $html );
	}
}
