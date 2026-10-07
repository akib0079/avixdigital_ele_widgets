<?php
/**
 * Service Tabs: a service page's capabilities as tabs. Visitors pick a tab in
 * a segmented pill bar and the panel below cross-fades to its title, text, a
 * checklist and an image, while the Avix pixel character walks along the top
 * of the tab bar to the tab they chose and says hi.
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

class Service_Tabs extends Widget_Base {

	use Media;

	const UPLOADS = 'https://avixdigital.com/wp-content/uploads/';

	/**
	 * Tags (and their attributes) the intro text may use.
	 */
	const INTRO_TAGS = array(
		'a'      => array(
			'href'   => true,
			'target' => true,
			'rel'    => true,
			'title'  => true,
		),
		'strong' => array(),
		'em'     => array(),
		'br'     => array(),
	);

	public function get_name(): string {
		return 'avix-service-tabs';
	}

	public function get_title(): string {
		return esc_html__( 'Service Tabs', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-tabs';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'tabs', 'capabilities', 'service', 'features', 'checklist', 'shopify', 'avix' );
	}

	public function get_style_depends(): array {
		return array( 'avix-service-tabs' );
	}

	public function get_script_depends(): array {
		return array( 'avix-service-tabs' );
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/* ------------------------------------------------------------------ */
	/* Controls                                                            */
	/* ------------------------------------------------------------------ */

	protected function register_controls(): void {
		$this->controls_header();
		$this->controls_tabs();
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
				'default' => esc_html__( 'What we build', 'avix-widgets' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Title', 'avix-widgets' ),
				'description' => esc_html__( 'Wrap words in [brackets] to highlight them in orange. Press Enter for a new line.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => 'Shopify Plus [Capabilities]',
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
			'intro',
			array(
				'label'       => esc_html__( 'Intro text', 'avix-widgets' ),
				'description' => esc_html__( 'Leave an empty line between paragraphs. Simple links (<a href="…">), <strong>, <em> and <br> are allowed; links that open in a new tab get rel="noopener" automatically.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 6,
				'default'     => "Start with the right scope: improve an existing theme, build a custom storefront or connect the tools your business depends on. We recommend the approach that fits your requirements and maintenance budget.\n\nSee our Shopify work: <a href=\"https://akib.avixdigital.com/rehall-com/\" target=\"_blank\" rel=\"noopener nofollow\">Rehall</a> and <a href=\"https://akib.avixdigital.com/ovabalance-eu/\" target=\"_blank\" rel=\"noopener nofollow\">OvaBalance</a>.",
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_responsive_control(
			'align',
			array(
				'label'                => esc_html__( 'Alignment', 'avix-widgets' ),
				'description'          => esc_html__( 'Lines up the header and the tab bar.', 'avix-widgets' ),
				'type'                 => Controls_Manager::CHOOSE,
				'default'              => 'center',
				'options'              => array(
					'left'   => array(
						'title' => esc_html__( 'Left', 'avix-widgets' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => esc_html__( 'Centred', 'avix-widgets' ),
						'icon'  => 'eicon-text-align-center',
					),
				),
				'selectors_dictionary' => array(
					'left'   => '--stb-head-align: left; --stb-head-ml: 0; --stb-head-items: flex-start; --stb-nav-ml: 0;',
					'center' => '--stb-head-align: center; --stb-head-ml: auto; --stb-head-items: center; --stb-nav-ml: auto;',
				),
				'selectors'            => array( '{{WRAPPER}} .avix-stb' => '{{VALUE}}' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_tabs() {
		$this->start_controls_section( 'section_tabs', array( 'label' => esc_html__( 'Tabs', 'avix-widgets' ) ) );

		$repeater = new Repeater();

		$repeater->add_control(
			'tab_label',
			array(
				'label'       => esc_html__( 'Tab label', 'avix-widgets' ),
				'description' => esc_html__( 'Short is best: two or three words.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Panel title', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'separator'   => 'before',
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'text',
			array(
				'label'   => esc_html__( 'Panel text', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 4,
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'features',
			array(
				'label'       => esc_html__( 'Checklist', 'avix-widgets' ),
				'description' => esc_html__( 'One item per line. Visitors see each line with an orange pixel check.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 5,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'button_text',
			array(
				'label'     => esc_html__( 'Button text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'separator' => 'before',
				'dynamic'   => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'button_link',
			array(
				'label'   => esc_html__( 'Button link', 'avix-widgets' ),
				'type'    => Controls_Manager::URL,
				'default' => array( 'url' => 'https://avixdigital.com/contact/' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'image',
			array(
				'label'       => esc_html__( 'Image', 'avix-widgets' ),
				'description' => esc_html__( 'Shown beside the text, cropped to the image ratio in the Style tab. Without an image the text uses the full card.', 'avix-widgets' ),
				'type'        => Controls_Manager::MEDIA,
				'separator'   => 'before',
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'image_alt',
			array(
				'label'       => esc_html__( 'Image alt text', 'avix-widgets' ),
				'description' => esc_html__( 'Describe the image for people who can’t see it. Empty uses the alt text from the Media Library, then the panel title.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'tabs',
			array(
				'label'       => esc_html__( 'Tabs', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ tab_label || title }}}',
				'default'     => $this->default_tabs(),
			)
		);

		$this->add_control(
			'tablist_label',
			array(
				'label'       => esc_html__( 'Tab bar name (screen readers)', 'avix-widgets' ),
				'description' => esc_html__( 'Read out when someone reaches the tabs with a screen reader. Empty uses the title.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Shopify Plus capabilities', 'avix-widgets' ),
				'separator'   => 'before',
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'active_tab',
			array(
				'label'       => esc_html__( 'Open tab on load', 'avix-widgets' ),
				'description' => esc_html__( '1 is the first tab.', 'avix-widgets' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 1,
				'max'         => 12,
				'step'        => 1,
				'default'     => 1,
			)
		);

		$this->add_control(
			'panel_title_tag',
			array(
				'label'   => esc_html__( 'Panel title HTML tag', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h3',
				'options' => array(
					'h2'  => 'H2',
					'h3'  => 'H3',
					'h4'  => 'H4',
					'div' => 'div',
					'p'   => 'p',
				),
			)
		);

		$this->add_control(
			'show_count',
			array(
				'label'       => esc_html__( 'Show panel number', 'avix-widgets' ),
				'description' => esc_html__( 'A small “01 / 03” above each panel title.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'url_hash',
			array(
				'label'       => esc_html__( 'Remember the tab in the address', 'avix-widgets' ),
				'description' => esc_html__( 'Adds #tab-name to the address when a tab is chosen, so a shared link opens that tab.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
			)
		);

		$this->add_control(
			'tabs_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Arrow keys, Home and End move between tabs. On phones the tab bar scrolls sideways and the chosen tab slides into view. If JavaScript is off, every panel is shown one under the other.', 'avix-widgets' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
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
				'description' => esc_html__( 'The Avix pixel character stands on top of the tab bar, above the open tab.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'pal_walk',
			array(
				'label'       => esc_html__( 'Walk to the chosen tab', 'avix-widgets' ),
				'description' => esc_html__( 'Pick another tab and it walks along the bar to it. Off: it hops straight there.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'condition'   => array( 'show_pal' => 'yes' ),
			)
		);

		$this->add_control(
			'pal_hi',
			array(
				'label'       => esc_html__( 'Say hi', 'avix-widgets' ),
				'description' => esc_html__( 'It says “hi” in pixel letters when the section first comes into view and when it reaches a new tab.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'condition'   => array( 'show_pal' => 'yes' ),
			)
		);

		$this->add_control(
			'pal_look',
			array(
				'label'       => esc_html__( 'Look at hovered tabs', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'condition'   => array( 'show_pal' => 'yes' ),
			)
		);

		$this->add_control(
			'pal_wave',
			array(
				'label'     => esc_html__( 'Wave now and then', 'avix-widgets' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'show_pal' => 'yes' ),
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
			'bg'          => array( esc_html__( 'Background', 'avix-widgets' ), '--stb-bg' ),
			'ink'         => array( esc_html__( 'Headings', 'avix-widgets' ), '--stb-ink' ),
			'muted'       => array( esc_html__( 'Text', 'avix-widgets' ), '--stb-muted' ),
			'accent'      => array( esc_html__( 'Accent (pixels, checks and character)', 'avix-widgets' ), '--stb-accent' ),
			'accent_text' => array( esc_html__( 'Highlighted words and links', 'avix-widgets' ), '--stb-accent-text' ),
			'track'       => array( esc_html__( 'Tab bar', 'avix-widgets' ), '--stb-track' ),
			'tab_ink'     => array( esc_html__( 'Tab labels', 'avix-widgets' ), '--stb-tab-ink' ),
			'thumb'       => array( esc_html__( 'Open tab', 'avix-widgets' ), '--stb-thumb' ),
			'thumb_ink'   => array( esc_html__( 'Open tab label', 'avix-widgets' ), '--stb-thumb-ink' ),
			'card'        => array( esc_html__( 'Panel card', 'avix-widgets' ), '--stb-card' ),
			'card_line'   => array( esc_html__( 'Panel card border', 'avix-widgets' ), '--stb-card-line' ),
			'btn'         => array( esc_html__( 'Button', 'avix-widgets' ), '--stb-btn' ),
			'btn_ink'     => array( esc_html__( 'Button text', 'avix-widgets' ), '--stb-btn-ink' ),
		);
		foreach ( $colors as $key => $color ) {
			$this->add_control(
				$key,
				array(
					'label'     => $color[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-stb' => $color[1] . ': {{VALUE}};' ),
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
				'selectors'          => array( '{{WRAPPER}} .avix-stb' => 'padding-top: {{TOP}}{{UNIT}}; padding-bottom: {{BOTTOM}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'max_width',
			array(
				'label'      => esc_html__( 'Content width', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 640, 'max' => 1600 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-stb' => '--stb-max: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'radius',
			array(
				'label'      => esc_html__( 'Panel corner radius', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 48 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-stb' => '--stb-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'image_ratio',
			array(
				'label'       => esc_html__( 'Image ratio', 'avix-widgets' ),
				'description' => esc_html__( 'While the image sits beside the text.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '4 / 3',
				'options'     => array(
					'4 / 3'   => '4:3',
					'3 / 2'   => '3:2',
					'16 / 10' => '16:10',
					'1 / 1'   => '1:1',
					'4 / 5'   => '4:5',
				),
				'selectors'   => array( '{{WRAPPER}} .avix-stb' => '--stb-ratio: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'image_ratio_stacked',
			array(
				'label'       => esc_html__( 'Image ratio when stacked', 'avix-widgets' ),
				'description' => esc_html__( 'In narrow spaces (tablets, phones) the image sits above the text at this ratio.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '16 / 10',
				'options'     => array(
					'16 / 10' => '16:10',
					'3 / 2'   => '3:2',
					'4 / 3'   => '4:3',
					'1 / 1'   => '1:1',
				),
				'selectors'   => array( '{{WRAPPER}} .avix-stb' => '--stb-ratio-stack: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'image_side',
			array(
				'label'   => esc_html__( 'Image side', 'avix-widgets' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'right',
				'options' => array(
					'left'  => array(
						'title' => esc_html__( 'Left', 'avix-widgets' ),
						'icon'  => 'eicon-h-align-left',
					),
					'right' => array(
						'title' => esc_html__( 'Right', 'avix-widgets' ),
						'icon'  => 'eicon-h-align-right',
					),
				),
			)
		);

		$this->add_control(
			'features_columns',
			array(
				'label'       => esc_html__( 'Checklist columns', 'avix-widgets' ),
				'description' => esc_html__( 'On wide screens. Narrow screens always use one column.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '2',
				'options'     => array(
					'1' => '1',
					'2' => '2',
				),
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
						'max'  => 50,
						'step' => 10,
					),
				),
				'selectors'   => array( '{{WRAPPER}} .avix-stb' => '--stb-pal-w: {{SIZE}}{{UNIT}};' ),
				'condition'   => array( 'show_pal' => 'yes' ),
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
			'eyebrow_typography'  => array( esc_html__( 'Eyebrow', 'avix-widgets' ), '{{WRAPPER}} .avix-stb .avix-stb__eyebrow' ),
			'title_typography'    => array( esc_html__( 'Title', 'avix-widgets' ), '{{WRAPPER}} .avix-stb .avix-stb__title' ),
			'intro_typography'    => array( esc_html__( 'Intro text', 'avix-widgets' ), '{{WRAPPER}} .avix-stb .avix-stb__intro' ),
			'tab_typography'      => array( esc_html__( 'Tab labels', 'avix-widgets' ), '{{WRAPPER}} .avix-stb .avix-stb__tab' ),
			'name_typography'     => array( esc_html__( 'Panel titles', 'avix-widgets' ), '{{WRAPPER}} .avix-stb .avix-stb__name' ),
			'text_typography'     => array( esc_html__( 'Panel text', 'avix-widgets' ), '{{WRAPPER}} .avix-stb .avix-stb__text' ),
			'features_typography' => array( esc_html__( 'Checklist', 'avix-widgets' ), '{{WRAPPER}} .avix-stb .avix-stb__feature' ),
			'button_typography'   => array( esc_html__( 'Button', 'avix-widgets' ), '{{WRAPPER}} .avix-stb .avix-stb__btn' ),
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
		$s    = $this->get_settings_for_display();
		$tabs = array_values(
			array_filter(
				(array) ( $s['tabs'] ?? array() ),
				static function ( $row ) {
					return '' !== trim( (string) ( $row['tab_label'] ?? '' ) ) || '' !== trim( (string) ( $row['title'] ?? '' ) );
				}
			)
		);

		if ( ! $tabs ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="elementor-alert elementor-alert-info">' . esc_html__( 'Service Tabs: add at least one tab with a label or a panel title.', 'avix-widgets' ) . '</div>';
			}
			return;
		}

		$id       = $this->get_id();
		$pal      = 'yes' === ( $s['show_pal'] ?? '' );
		$eyebrow  = trim( (string) ( $s['eyebrow'] ?? '' ) );
		$title    = trim( (string) ( $s['title'] ?? '' ) );
		$intro    = $this->intro_html( (string) ( $s['intro'] ?? '' ) );
		$tag      = Utils::validate_html_tag( $s['title_tag'] ?? 'h2' );
		$title_id = 'avix-stb-title-' . $id;
		$count    = count( $tabs );
		$active   = max( 0, min( $count - 1, absint( $s['active_tab'] ?? 1 ) - 1 ) );
		$label    = trim( (string) ( $s['tablist_label'] ?? '' ) );
		$label    = '' !== $label ? $label : wp_strip_all_tags( str_replace( array( '[', ']' ), '', $title ) );

		$classes = array( 'avix-stb', 'avix-stb--img-' . ( 'left' === ( $s['image_side'] ?? '' ) ? 'left' : 'right' ) );
		if ( 'dark' === ( $s['theme'] ?? '' ) ) {
			$classes[] = 'avix-stb--dark';
		}
		// The character stands on the tab bar, so a single tab (no bar) has none.
		if ( $pal && $count > 1 ) {
			$classes[] = 'avix-stb--pal';
		}
		if ( '1' === (string) ( $s['features_columns'] ?? '2' ) ) {
			$classes[] = 'avix-stb--list-1';
		}
		if ( 1 === $count ) {
			$classes[] = 'avix-stb--single';
		}

		$this->add_render_attribute(
			'root',
			array(
				'class'         => $classes,
				'data-avix-stb' => wp_json_encode(
					array(
						'pal'  => $pal,
						'walk' => $pal && 'yes' === ( $s['pal_walk'] ?? '' ),
						'hi'   => $pal && 'yes' === ( $s['pal_hi'] ?? '' ),
						'look' => $pal && 'yes' === ( $s['pal_look'] ?? '' ),
						'wave' => $pal && 'yes' === ( $s['pal_wave'] ?? '' ),
						'hash' => 'yes' === ( $s['url_hash'] ?? '' ),
					)
				),
			)
		);
		if ( '' !== $title ) {
			$this->add_render_attribute( 'root', 'aria-labelledby', $title_id );
		} elseif ( '' !== $label ) {
			$this->add_render_attribute( 'root', 'aria-label', $label );
		}

		$slugs = array();
		foreach ( $tabs as $i => $tab ) {
			$slug          = sanitize_title( $this->tab_label( $tab ) );
			$slug          = '' !== $slug ? $slug : 'tab-' . ( $i + 1 );
			$slugs[ $i ]   = in_array( $slug, $slugs, true ) ? $slug . '-' . ( $i + 1 ) : $slug;
		}
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="avix-stb__inner">
				<?php if ( '' !== $eyebrow || '' !== $title || '' !== $intro ) : ?>
					<header class="avix-stb__head" data-stb-reveal style="--i:0;">
						<?php if ( '' !== $eyebrow ) : ?>
							<p class="avix-stb__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
						<?php endif; ?>
						<?php if ( '' !== $title ) : ?>
							<<?php echo esc_attr( $tag ); ?> class="avix-stb__title" id="<?php echo esc_attr( $title_id ); ?>"><?php echo $this->accent_html( $title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in accent_html(). ?></<?php echo esc_attr( $tag ); ?>>
						<?php endif; ?>
						<?php if ( '' !== $intro ) : ?>
							<div class="avix-stb__intro"><?php echo $intro; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- filtered with wp_kses() in intro_html(). ?></div>
						<?php endif; ?>
					</header>
				<?php endif; ?>

				<?php if ( $count > 1 ) : ?>
					<div class="avix-stb__nav" data-stb-nav data-stb-reveal style="--i:1;">
						<div class="avix-stb__scroller" data-stb-scroller>
							<div class="avix-stb__track" role="tablist"<?php echo '' !== $label ? ' aria-label="' . esc_attr( $label ) . '"' : ''; ?> data-stb-track>
								<span class="avix-stb__thumb" data-stb-thumb aria-hidden="true"></span>
								<?php foreach ( $tabs as $i => $tab ) : ?>
									<button type="button" class="avix-stb__tab<?php echo $i === $active ? ' is-active' : ''; ?>" role="tab" id="<?php echo esc_attr( 'avix-stb-tab-' . $id . '-' . ( $i + 1 ) ); ?>" aria-controls="<?php echo esc_attr( 'avix-stb-panel-' . $id . '-' . ( $i + 1 ) ); ?>" aria-selected="<?php echo $i === $active ? 'true' : 'false'; ?>" tabindex="<?php echo $i === $active ? '0' : '-1'; ?>" data-stb-tab data-stb-slug="<?php echo esc_attr( $slugs[ $i ] ); ?>">
										<span class="avix-stb__tab-px" aria-hidden="true"></span>
										<span class="avix-stb__tab-text"><?php echo esc_html( $this->tab_label( $tab ) ); ?></span>
									</button>
								<?php endforeach; ?>
								<?php if ( $pal ) : ?>
									<span class="avix-stb__walker" data-stb-walker aria-hidden="true">
										<?php
										echo \AvixWidgets\Pixel_Pal::render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Pixel_Pal::render().
											array(
												'class' => 'avix-stb__pal',
												'hi'    => true,
											)
										);
										?>
									</span>
								<?php endif; ?>
							</div>
						</div>
					</div>
				<?php endif; ?>

				<div class="avix-stb__panels" data-stb-panels data-stb-reveal style="--i:2;">
					<?php
					foreach ( $tabs as $i => $tab ) {
						$this->render_panel( $s, $tab, $i, $count, $active );
					}
					?>
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * One tab panel: text column (number, title, text, checklist, button) and
	 * the image.
	 *
	 * @param array $s      Widget settings.
	 * @param array $tab    Repeater row.
	 * @param int   $i      Position.
	 * @param int   $count  Number of tabs.
	 * @param int   $active Index of the open tab.
	 */
	private function render_panel( array $s, array $tab, $i, $count, $active ) {
		$id       = $this->get_id();
		$name     = trim( (string) ( $tab['title'] ?? '' ) );
		$text     = trim( (string) ( $tab['text'] ?? '' ) );
		$features = array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) ( $tab['features'] ?? '' ) ) ), 'strlen' ) );
		$btn_text = trim( (string) ( $tab['button_text'] ?? '' ) );
		$link     = (array) ( $tab['button_link'] ?? array() );
		$has_btn  = '' !== $btn_text && ! empty( $link['url'] );
		$name_tag = Utils::validate_html_tag( $s['panel_title_tag'] ?? 'h3' );
		$image    = $this->image_html( $tab, '' !== $name ? $name : $this->tab_label( $tab ) );
		$multi    = $count > 1;

		$classes = array(
			'avix-stb__panel',
			'elementor-repeater-item-' . sanitize_html_class( (string) ( $tab['_id'] ?? '' ) ),
			'' !== $image ? 'has-media' : 'no-media',
		);
		if ( $i === $active ) {
			$classes[] = 'is-active';
		}

		$attrs = array(
			'class' => implode( ' ', $classes ),
			'id'    => 'avix-stb-panel-' . $id . '-' . ( $i + 1 ),
		);
		if ( $multi ) {
			$attrs['role']            = 'tabpanel';
			$attrs['aria-labelledby'] = 'avix-stb-tab-' . $id . '-' . ( $i + 1 );
			$attrs['tabindex']        = '0';
		}
		$attr_html = '';
		foreach ( $attrs as $key => $value ) {
			$attr_html .= ' ' . $key . '="' . esc_attr( $value ) . '"';
		}
		?>
		<div<?php echo $attr_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- every value escaped above. ?> data-stb-panel>
			<div class="avix-stb__copy">
				<?php if ( $multi && 'yes' === ( $s['show_count'] ?? '' ) ) : ?>
					<p class="avix-stb__count" aria-hidden="true" style="--j:0;"><span class="avix-stb__count-now"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span><span class="avix-stb__count-of"><?php echo esc_html( sprintf( '/ %02d', $count ) ); ?></span><span class="avix-stb__count-label"><?php echo esc_html( $this->tab_label( $tab ) ); ?></span></p>
				<?php endif; ?>
				<?php if ( '' !== $name ) : ?>
					<<?php echo esc_attr( $name_tag ); ?> class="avix-stb__name" style="--j:1;"><?php echo esc_html( $name ); ?></<?php echo esc_attr( $name_tag ); ?>>
				<?php endif; ?>
				<?php if ( '' !== $text ) : ?>
					<p class="avix-stb__text" style="--j:2;"><?php echo esc_html( $text ); ?></p>
				<?php endif; ?>
				<?php if ( $features ) : ?>
					<ul class="avix-stb__features" role="list" style="--j:3;">
						<?php foreach ( $features as $feature ) : ?>
							<li class="avix-stb__feature"><span class="avix-stb__check" aria-hidden="true"><?php echo self::check_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup. ?></span><span class="avix-stb__feature-text"><?php echo esc_html( $feature ); ?></span></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<?php
				if ( $has_btn ) :
					$key = 'btn-' . $i;
					$this->add_render_attribute( $key, 'class', 'avix-stb__btn' );
					$this->add_link_attributes( $key, $link );
					?>
					<div class="avix-stb__cta" style="--j:4;">
						<a <?php $this->print_render_attribute_string( $key ); ?>>
							<span class="avix-stb__btn-text"><?php echo esc_html( $btn_text ); ?></span>
							<span class="avix-stb__btn-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M7 17 17 7M7 7h10v10"/></svg></span>
						</a>
					</div>
				<?php endif; ?>
			</div>
			<?php if ( '' !== $image ) : ?>
				<figure class="avix-stb__media">
					<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built by wp_get_attachment_image() or escaped in image_html(). ?>
				</figure>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * The tab's label, or its panel title when the label is empty.
	 *
	 * @param array $tab Repeater row.
	 */
	private function tab_label( array $tab ) {
		$label = trim( (string) ( $tab['tab_label'] ?? '' ) );
		return '' !== $label ? $label : trim( (string) ( $tab['title'] ?? '' ) );
	}

	/**
	 * Responsive image for a panel, or '' when it has none.
	 *
	 * @param array  $tab      Repeater row.
	 * @param string $fallback Alt text when nothing better is set.
	 */
	private function image_html( array $tab, $fallback ) {
		$media = (array) ( $tab['image'] ?? array() );
		$url   = trim( (string) ( $media['url'] ?? '' ) );
		$id    = $this->media_id( $media );
		if ( ! $id && '' === $url ) {
			return '';
		}

		$alt = trim( (string) ( $tab['image_alt'] ?? '' ) );
		if ( '' === $alt && $id ) {
			$alt = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
		}
		if ( '' === $alt ) {
			$alt = $fallback;
		}

		if ( $id ) {
			$is_gif = 'image/gif' === get_post_mime_type( $id );
			$html   = $is_gif ? '' : wp_get_attachment_image(
				$id,
				'large',
				false,
				array(
					'class'    => 'avix-stb__img',
					'alt'      => $alt,
					'loading'  => 'lazy',
					'decoding' => 'async',
					'sizes'    => '(max-width: 900px) calc(100vw - 32px), 600px',
				)
			);
			if ( $html ) {
				return $html;
			}
			$url = (string) wp_get_attachment_url( $id );
		}

		$src = esc_url( $url );
		if ( '' === $src ) {
			return '';
		}
		return sprintf(
			'<img class="avix-stb__img" src="%s" alt="%s" loading="lazy" decoding="async">',
			$src,
			esc_attr( $alt )
		);
	}

	/**
	 * Intro: paragraphs split on empty lines, filtered to a tight allowlist.
	 * Links that open a new tab get rel="noopener".
	 *
	 * @param string $text Raw intro.
	 */
	private function intro_html( $text ) {
		$text = trim( $text );
		if ( '' === $text ) {
			return '';
		}
		$paras = preg_split( '/(?:\r\n|\r|\n)\s*(?:\r\n|\r|\n)/', $text );
		$html  = '';
		foreach ( $paras as $para ) {
			$para = trim( (string) $para );
			if ( '' === $para ) {
				continue;
			}
			$para = wp_kses( nl2br( $para, false ), self::INTRO_TAGS );
			$para = preg_replace_callback(
				'/<a\s[^>]*>/i',
				static function ( $m ) {
					$tag = $m[0];
					if ( ! preg_match( '/\starget=("|\')_blank\1/i', $tag ) ) {
						return $tag;
					}
					if ( preg_match( '/\srel=("|\')([^"\']*)\1/i', $tag, $rel ) ) {
						if ( false !== stripos( $rel[2], 'noopener' ) ) {
							return $tag;
						}
						return str_replace( $rel[0], ' rel="' . esc_attr( trim( $rel[2] . ' noopener' ) ) . '"', $tag );
					}
					return substr( $tag, 0, -1 ) . ' rel="noopener">';
				},
				$para
			);
			$html .= '<p>' . $para . '</p>';
		}
		return $html;
	}

	/**
	 * Escapes the title, turns [words] into highlighted spans and line breaks
	 * into <br>.
	 *
	 * @param string $text Title.
	 */
	private function accent_html( $text ) {
		$lines = array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $text ) ), 'strlen' ) );
		$html  = array();
		foreach ( $lines as $line ) {
			$out    = preg_replace( '/\[([^\[\]]+)\]/u', '<span class="avix-stb__accent">$1</span>', esc_html( $line ) );
			$html[] = null === $out ? esc_html( $line ) : $out;
		}
		return implode( ' <br>', $html );
	}

	/**
	 * A check drawn on the character's pixel grid (7×6, whole units only).
	 */
	private static function check_svg() {
		$rows  = array( '......#', '.....##', '#...##.', '##.##..', '.###...', '..#....' );
		$rects = '';
		foreach ( $rows as $y => $row ) {
			for ( $x = 0; $x < 7; $x++ ) {
				if ( '#' === $row[ $x ] ) {
					$rects .= '<rect x="' . $x . '" y="' . $y . '" width="1" height="1"/>';
				}
			}
		}
		return '<svg viewBox="0 0 7 6" focusable="false">' . $rects . '</svg>';
	}

	/**
	 * Shopify Plus capabilities, from the live service page.
	 */
	private function default_tabs() {
		$contact = array( 'url' => 'https://avixdigital.com/contact/' );
		return array(
			array(
				'tab_label'   => esc_html__( 'Headless Commerce', 'avix-widgets' ),
				'title'       => esc_html__( 'Headless Shopify for a defined business need', 'avix-widgets' ),
				'text'        => esc_html__( 'A separate React or Next.js storefront can support custom journeys and content requirements. We assess the benefits, integration work and ongoing maintenance against a standard Shopify theme before recommending headless.', 'avix-widgets' ),
				'features'    => "Next.js & React Frontend\nPerformance planning and testing\nOmnichannel Selling\nSanity/Contentful CMS Integration",
				'button_text' => esc_html__( 'Explore Headless Solutions', 'avix-widgets' ),
				'button_link' => $contact,
				'image'       => array( 'url' => self::UPLOADS . '2026/09/ChatGPT-Image-29-Sept-2026-02_12_10.png' ),
				'image_alt'   => esc_html__( 'A storefront on a laptop and phone, surrounded by the platforms it connects to', 'avix-widgets' ),
			),
			array(
				'tab_label'   => esc_html__( 'Custom Theme Dev', 'avix-widgets' ),
				'title'       => esc_html__( 'Custom Liquid themes your team can manage', 'avix-widgets' ),
				'text'        => esc_html__( 'We build or extend Shopify themes with Liquid, CSS and JavaScript, using reusable sections, metafields and metaobjects. The goal is a storefront that fits your brand and lets your team update content without rebuilding pages.', 'avix-widgets' ),
				'features'    => "Native Shopify 2.0 Themes\nFigma designs implemented in Shopify\nCustom Metaobjects & Metafields\nKeyboard and accessibility checks",
				'button_text' => esc_html__( 'Start your Custom Theme', 'avix-widgets' ),
				'button_link' => $contact,
				'image'       => array( 'url' => self::UPLOADS . '2026/06/Untitled-design235.png' ),
				'image_alt'   => esc_html__( 'The Kampeerwinkel Shopify storefront on a laptop', 'avix-widgets' ),
			),
			array(
				'tab_label'   => esc_html__( 'Complex Integrations', 'avix-widgets' ),
				'title'       => esc_html__( 'Enterprise ERP & API Integrations', 'avix-widgets' ),
				'text'        => esc_html__( 'Connect product, inventory and order data with your existing systems. We review API access, data ownership, sync rules and failure handling before quoting an integration.', 'avix-widgets' ),
				'features'    => "ERP and inventory integration scoping\nCustom Shopify apps and API integrations\nAdvanced Shopify Flow Automation\nB2B Wholesale Portal Architecture",
				'button_text' => esc_html__( 'Automate your operations', 'avix-widgets' ),
				'button_link' => $contact,
				'image'       => array( 'url' => self::UPLOADS . '2026/06/Untitled-design247.webp' ),
				'image_alt'   => esc_html__( 'An operations dashboard on a tablet', 'avix-widgets' ),
			),
		);
	}
}
