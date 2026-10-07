<?php
/**
 * Case Study Feature Spotlight: one feature of a client site, explained. A
 * text column (index, title, the problem, what was built, a numbered legend)
 * sits beside a real screenshot in a device frame with numbered pixel pins on
 * the exact UI. Pins open a callout, the legend highlights its pin, phones get
 * numbered cards, and the full screenshot opens in a lightbox.
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

// The case-study data layer (WI-02) provides the shared "Case study" source
// controls. Without it the widget is simply not registered (the plugin's
// widget loader skips a missing class) instead of taking the site down.
if ( ! trait_exists( '\AvixWidgets\Case_Studies\Source' ) ) {
	return;
}

class Case_Study_Spotlight extends Widget_Base {

	use Media;
	use \AvixWidgets\Case_Studies\Source;

	/**
	 * Tags (and their attributes) "What we built" may use.
	 */
	const BUILT_TAGS = array(
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

	const DEVICES    = array( 'browser', 'phone', 'plain' );
	const MATS       = array( 'paper', 'tint', 'white', 'dark' );
	const PLACEMENTS = array( 'auto', 'top', 'right', 'bottom', 'left' );

	public function get_name(): string {
		return 'avix-case-study-spotlight';
	}

	public function get_title(): string {
		return esc_html__( 'Case Study Feature Spotlight', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-image-hotspot';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'avix', 'case study', 'portfolio', 'spotlight', 'feature', 'hotspot', 'screenshot', 'annotated', 'pins' );
	}

	public function get_style_depends(): array {
		return array( 'avix-case-study-spotlight' );
	}

	public function get_script_depends(): array {
		return array( 'avix-case-study-spotlight' );
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
		$this->controls_feature();
		$this->controls_media();
		$this->controls_hotspots();
		$this->controls_style();
	}

	private function controls_feature() {
		$this->start_controls_section( 'section_feature', array( 'label' => esc_html__( 'Feature', 'avix-widgets' ) ) );

		$this->add_control(
			'index',
			array(
				'label'       => esc_html__( 'Number', 'avix-widgets' ),
				'description' => esc_html__( 'Empty counts the spotlights on this page for you, e.g. “01 / 06”. Save the page to update the count.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => '01 / 06',
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'tag',
			array(
				'label'       => esc_html__( 'Tag', 'avix-widgets' ),
				'description' => esc_html__( 'A one-word label beside the number, e.g. Navigation, Search or Checkout.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__( 'Navigation', 'avix-widgets' ),
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
				'default'     => esc_html__( 'Describe the feature in one line', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'   => esc_html__( 'Title HTML tag', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h3',
				'options' => array(
					'h2'  => 'H2',
					'h3'  => 'H3',
					'h4'  => 'H4',
					'div' => 'div',
				),
			)
		);

		$this->add_control(
			'problem_label',
			array(
				'label'     => esc_html__( 'Problem label', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'The problem', 'avix-widgets' ),
				'separator' => 'before',
				'dynamic'   => array( 'active' => true ),
			)
		);

		$this->add_control(
			'problem',
			array(
				'label'       => esc_html__( 'The problem', 'avix-widgets' ),
				'description' => esc_html__( 'What got in the visitor’s way before. Leave an empty line between paragraphs.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 4,
				'default'     => '',
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'built_label',
			array(
				'label'     => esc_html__( '“What we built” label', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'What we built', 'avix-widgets' ),
				'separator' => 'before',
				'dynamic'   => array( 'active' => true ),
			)
		);

		$this->add_control(
			'built',
			array(
				'label'       => esc_html__( 'What we built', 'avix-widgets' ),
				'description' => esc_html__( 'What the visitor can now do. Simple links (<a href="…">), <strong> and <em> are allowed. Leave an empty line between paragraphs.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 6,
				'default'     => '',
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'attribution',
			array(
				'label'       => esc_html__( 'Attribution', 'avix-widgets' ),
				'description' => esc_html__( 'Stay honest: “Built” only when the project record says we built it. “Observed” is what visitors can see on the live site today; it may postdate our work.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'built',
				'separator'   => 'before',
				'options'     => array(
					'built'    => esc_html__( 'Built by AvixDigital', 'avix-widgets' ),
					'observed' => esc_html__( 'Observed on the live site', 'avix-widgets' ),
					'none'     => esc_html__( 'None', 'avix-widgets' ),
				),
			)
		);

		$this->add_control(
			'attribution_text',
			array(
				'label'       => esc_html__( 'Attribution text', 'avix-widgets' ),
				'description' => esc_html__( 'Empty: “Built by AvixDigital”, or “Live on rehall.com today” for observed features.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'condition'   => array( 'attribution!' => 'none' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'source_link',
			array(
				'label'       => esc_html__( 'See it live (link)', 'avix-widgets' ),
				'description' => esc_html__( 'The page on the client site where visitors can try this. Empty uses the case study’s live site.', 'avix-widgets' ),
				'type'        => Controls_Manager::URL,
				'default'     => array( 'url' => '' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'source_text',
			array(
				'label'       => esc_html__( 'Link text', 'avix-widgets' ),
				'description' => esc_html__( 'Empty: “See this on rehall.com”.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_media() {
		$this->start_controls_section( 'section_media', array( 'label' => esc_html__( 'Screenshot', 'avix-widgets' ) ) );

		$this->add_control(
			'media_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Use a real, uncropped screenshot of the live site: the pins are its proof. Never put pins on a mock-up or a render. Without a screenshot the text uses the full width.', 'avix-widgets' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->add_control(
			'image',
			array(
				'label'   => esc_html__( 'Screenshot', 'avix-widgets' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => array( 'url' => '' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'image_alt',
			array(
				'label'       => esc_html__( 'Alt text', 'avix-widgets' ),
				'description' => esc_html__( 'Describe what the screenshot shows. Empty uses the Media Library alt text, then the title. It is also the lightbox caption.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => '',
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'device',
			array(
				'label'   => esc_html__( 'Frame', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'browser',
				'options' => array(
					'browser' => esc_html__( 'Browser window (desktop shots)', 'avix-widgets' ),
					'phone'   => esc_html__( 'Phone (mobile shots)', 'avix-widgets' ),
					'plain'   => esc_html__( 'Plain card (a drawer, form or panel)', 'avix-widgets' ),
				),
			)
		);

		$this->add_control(
			'url_label',
			array(
				'label'       => esc_html__( 'Address bar text', 'avix-widgets' ),
				'description' => esc_html__( 'Empty shows the link’s address, e.g. rehall.com/collections/mens-ski-jackets.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'condition'   => array( 'device' => 'browser' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'mat',
			array(
				'label'       => esc_html__( 'Mat', 'avix-widgets' ),
				'description' => esc_html__( 'The panel the frame sits on. Tint mixes in the client’s brand colour from the case study.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'paper',
				'options'     => array(
					'paper' => esc_html__( 'Paper', 'avix-widgets' ),
					'tint'  => esc_html__( 'Brand tint', 'avix-widgets' ),
					'white' => esc_html__( 'White', 'avix-widgets' ),
					'dark'  => esc_html__( 'Dark', 'avix-widgets' ),
				),
			)
		);

		$this->add_control(
			'side',
			array(
				'label'       => esc_html__( 'Screenshot side', 'avix-widgets' ),
				'description' => esc_html__( 'Automatic alternates down the page: the 1st, 3rd and 5th spotlight show the screenshot on the right.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'auto',
				'options'     => array(
					'auto'  => esc_html__( 'Automatic', 'avix-widgets' ),
					'left'  => esc_html__( 'Left', 'avix-widgets' ),
					'right' => esc_html__( 'Right', 'avix-widgets' ),
				),
			)
		);

		$this->add_responsive_control(
			'visual_max',
			array(
				'label'       => esc_html__( 'Frame max width', 'avix-widgets' ),
				'description' => esc_html__( 'Empty: browser frames fill the column, phones 340px, plain cards 460px.', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array(
					'px' => array(
						'min' => 240,
						'max' => 1200,
					),
				),
				'selectors'   => array( '{{WRAPPER}} .avix-csf' => '--csf-visual-max: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'full_image',
			array(
				'label'       => esc_html__( 'Full screenshot (lightbox)', 'avix-widgets' ),
				'description' => esc_html__( 'Opens from “View full screenshot”. Empty uses the screenshot above.', 'avix-widgets' ),
				'type'        => Controls_Manager::MEDIA,
				'default'     => array( 'url' => '' ),
				'separator'   => 'before',
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'full_label',
			array(
				'label'       => esc_html__( 'Lightbox link text', 'avix-widgets' ),
				'description' => esc_html__( 'Empty hides the link.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'View full screenshot', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'pixel_reveal',
			array(
				'label'       => esc_html__( 'Pixel reveal', 'avix-widgets' ),
				'description' => esc_html__( 'The screenshot resolves from pixel squares as it scrolls into view, then the pins pop in.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->end_controls_section();
	}

	private function controls_hotspots() {
		$this->start_controls_section( 'section_hotspots', array( 'label' => esc_html__( 'Hotspots', 'avix-widgets' ) ) );

		$this->add_control(
			'hotspots_tip',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Drag the X/Y sliders and watch the pin move in the preview. Positions are percentages of the full screenshot, so they survive resizing.', 'avix-widgets' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'x',
			array(
				'label'      => esc_html__( 'Across (X)', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array(
					'%' => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 0.1,
					),
				),
				'default'    => array(
					'unit' => '%',
					'size' => 50,
				),
				// Moves the pin instantly while dragging; the render prints the same value inline.
				'selectors'  => array( '{{WRAPPER}} {{CURRENT_ITEM}}.avix-csf__pin' => '--csf-ex: {{SIZE}}%;' ),
			)
		);

		$repeater->add_control(
			'y',
			array(
				'label'      => esc_html__( 'Down (Y)', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array(
					'%' => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 0.1,
					),
				),
				'default'    => array(
					'unit' => '%',
					'size' => 50,
				),
				'selectors'  => array( '{{WRAPPER}} {{CURRENT_ITEM}}.avix-csf__pin' => '--csf-ey: {{SIZE}}%;' ),
			)
		);

		$repeater->add_control(
			'label',
			array(
				'label'       => esc_html__( 'Label', 'avix-widgets' ),
				'description' => esc_html__( 'What this part of the page does, in a few words. Only describe what visitors can see on the live site.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'detail',
			array(
				'label'   => esc_html__( 'Detail (optional)', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 2,
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'placement',
			array(
				'label'       => esc_html__( 'Callout side', 'avix-widgets' ),
				'description' => esc_html__( 'Automatic picks the side with the most room. A chosen side is used when it fits.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'auto',
				'options'     => array(
					'auto'   => esc_html__( 'Automatic', 'avix-widgets' ),
					'top'    => esc_html__( 'Above', 'avix-widgets' ),
					'right'  => esc_html__( 'Right', 'avix-widgets' ),
					'bottom' => esc_html__( 'Below', 'avix-widgets' ),
					'left'   => esc_html__( 'Left', 'avix-widgets' ),
				),
			)
		);

		$this->add_control(
			'hotspots',
			array(
				'label'       => esc_html__( 'Hotspots', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ label }}}',
				'default'     => array(
					array(
						'x'     => array(
							'unit' => '%',
							'size' => 50,
						),
						'y'     => array(
							'unit' => '%',
							'size' => 50,
						),
						'label' => esc_html__( 'What this part of the page does', 'avix-widgets' ),
					),
				),
			)
		);

		$this->add_control(
			'pin_style',
			array(
				'label'       => esc_html__( 'Pins', 'avix-widgets' ),
				'description' => esc_html__( 'Squares are quieter pixel markers; the legend keeps its numbers.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'numbers',
				'separator'   => 'before',
				'options'     => array(
					'numbers' => esc_html__( 'Numbered', 'avix-widgets' ),
					'squares' => esc_html__( 'Pixel squares', 'avix-widgets' ),
				),
			)
		);

		$this->add_control(
			'show_legend',
			array(
				'label'       => esc_html__( 'Show legend', 'avix-widgets' ),
				'description' => esc_html__( 'The numbered list under the text. Hidden, it stays readable for search engines and screen readers.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'pulse',
			array(
				'label'       => esc_html__( 'Pulse', 'avix-widgets' ),
				'description' => esc_html__( 'A soft ring breathes around each pin so visitors know they can open it.', 'avix-widgets' ),
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
				'description' => esc_html__( 'Sets every colour below; any colour you pick still wins.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'white',
				'options'     => array(
					'white' => esc_html__( 'White', 'avix-widgets' ),
					'paper' => esc_html__( 'Paper', 'avix-widgets' ),
					'dark'  => esc_html__( 'Dark', 'avix-widgets' ),
				),
			)
		);

		$colors = array(
			'bg'          => array( esc_html__( 'Background', 'avix-widgets' ), '--csf-bg' ),
			'ink'         => array( esc_html__( 'Headings', 'avix-widgets' ), '--csf-ink' ),
			'muted'       => array( esc_html__( 'Text', 'avix-widgets' ), '--csf-muted' ),
			'line'        => array( esc_html__( 'Lines', 'avix-widgets' ), '--csf-line' ),
			'accent'      => array( esc_html__( 'Accent', 'avix-widgets' ), '--csf-accent' ),
			'accent_text' => array( esc_html__( 'Highlighted words and links', 'avix-widgets' ), '--csf-accent-text' ),
			'pin_bg'      => array( esc_html__( 'Pins', 'avix-widgets' ), '--csf-pin' ),
			'pin_ink'     => array( esc_html__( 'Pin numbers', 'avix-widgets' ), '--csf-pin-ink' ),
			'callout_bg'  => array( esc_html__( 'Callout', 'avix-widgets' ), '--csf-callout' ),
			'callout_ink' => array( esc_html__( 'Callout text', 'avix-widgets' ), '--csf-callout-ink' ),
		);
		foreach ( $colors as $key => $color ) {
			$this->add_control(
				$key,
				array(
					'label'     => $color[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-csf' => $color[1] . ': {{VALUE}};' ),
				)
			);
		}

		$this->add_responsive_control(
			'padding',
			array(
				'label'              => esc_html__( 'Padding', 'avix-widgets' ),
				'description'        => esc_html__( 'Spotlights that follow each other read best with 0 at the top of the 2nd and later ones.', 'avix-widgets' ),
				'type'               => Controls_Manager::DIMENSIONS,
				'size_units'         => array( 'px', 'vh' ),
				'allowed_dimensions' => 'vertical',
				'separator'          => 'before',
				'selectors'          => array( '{{WRAPPER}} .avix-csf' => 'padding-top: {{TOP}}{{UNIT}}; padding-bottom: {{BOTTOM}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'max_width',
			array(
				'label'      => esc_html__( 'Content width', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 720,
						'max' => 1600,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .avix-csf' => '--csf-max: {{SIZE}}{{UNIT}};' ),
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
			'title_typography'  => array( esc_html__( 'Title', 'avix-widgets' ), '{{WRAPPER}} .avix-csf .avix-csf__title' ),
			'label_typography'  => array( esc_html__( 'Labels', 'avix-widgets' ), '{{WRAPPER}} .avix-csf .avix-csf__qa dt' ),
			'text_typography'   => array( esc_html__( 'Text', 'avix-widgets' ), '{{WRAPPER}} .avix-csf .avix-csf__qa dd' ),
			'legend_typography' => array( esc_html__( 'Legend', 'avix-widgets' ), '{{WRAPPER}} .avix-csf .avix-csf__legend-text' ),
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
		$cs   = $this->cs();
		$edit = $this->in_editor();
		$id   = $this->get_id();

		$title    = trim( (string) ( $s['title'] ?? '' ) );
		$problem  = $this->paras_html( (string) ( $s['problem'] ?? '' ), false );
		$built    = $this->paras_html( (string) ( $s['built'] ?? '' ), true );
		$hotspots = $this->hotspots( (array) ( $s['hotspots'] ?? array() ) );
		$shot     = $this->shot( $s, $title );

		// An empty starter spotlight (only the default title) is a builder-only
		// shell: visitors never see a placeholder.
		if ( ! $shot && '' === $problem && '' === $built ) {
			if ( ! $edit ) {
				return;
			}
			if ( '' === $title ) {
				echo '<div class="elementor-alert elementor-alert-info">' . esc_html__( 'Feature Spotlight: add a title, the problem and what was built, and a screenshot of the live site.', 'avix-widgets' ) . '</div>';
				return;
			}
		}

		// Links and labels: this widget's fields first, then the case study.
		$live_url   = (string) ( $cs['live_url'] ?? '' );
		$link       = (array) ( $s['source_link'] ?? array() );
		$link_url   = trim( (string) ( $link['url'] ?? '' ) );
		$link_url   = '' !== $link_url ? $link_url : $live_url;
		$link_url   = $this->safe_url( $link_url );
		$link_host  = $this->domain( $link_url );
		$live_label = trim( (string) ( $cs['live_label'] ?? '' ) );
		$live_label = '' !== $live_label ? $live_label : $this->domain( $live_url );
		$live_label = '' !== $live_label ? $live_label : $link_host;

		// Position among the page's spotlights: the "01 / 06" counter and the auto side.
		$place = $this->position();
		$index = trim( (string) ( $s['index'] ?? '' ) );
		if ( '' === $index && $place['count'] > 1 && $place['pos'] > 0 ) {
			$index = sprintf( '%02d / %02d', $place['pos'], $place['count'] );
		}
		$side = (string) ( $s['side'] ?? 'auto' );
		if ( ! in_array( $side, array( 'left', 'right' ), true ) ) {
			$side = ( $place['pos'] > 0 && 0 === $place['pos'] % 2 ) ? 'left' : 'right';
		}

		$device  = in_array( (string) ( $s['device'] ?? '' ), self::DEVICES, true ) ? (string) $s['device'] : 'browser';
		$theme   = in_array( (string) ( $s['theme'] ?? '' ), array( 'white', 'paper', 'dark' ), true ) ? (string) $s['theme'] : 'white';
		$legend  = 'yes' === ( $s['show_legend'] ?? '' );
		$squares = 'squares' === ( $s['pin_style'] ?? '' );
		$pulse   = 'yes' === ( $s['pulse'] ?? '' ) && $hotspots && $shot;
		$media   = $shot || $edit;

		$classes = array(
			'avix-csf',
			'avix-csf--media-' . $side,
			'avix-csf--' . $device,
			'avix-csf--theme-' . $theme,
			$media ? 'has-media' : 'no-media',
		);
		if ( 'dark' === $theme ) {
			$classes[] = 'avix-csk-on-dark';
		}
		if ( $squares ) {
			$classes[] = 'avix-csf--squares';
		}
		if ( $pulse ) {
			$classes[] = 'avix-csf--pulse';
		}
		if ( ! $legend ) {
			$classes[] = 'avix-csf--legend-off';
		}

		$title_id = 'avix-csf-title-' . $id;
		$root     = array(
			'class'         => $classes,
			'id'            => 'avix-csf-' . $id,
			'data-avix-csf' => wp_json_encode(
				array(
					'pulse'  => $pulse,
					'legend' => $legend,
					'close'  => __( 'Close', 'avix-widgets' ),
				)
			),
		);
		if ( '' !== $title ) {
			$root['aria-labelledby'] = $title_id;
		} else {
			$root['aria-label'] = esc_html__( 'Feature spotlight', 'avix-widgets' );
		}
		$accent = sanitize_hex_color( (string) ( $cs['accent'] ?? '' ) );
		if ( $accent ) {
			$root['style'] = '--csk-accent:' . $accent . ';';
		}
		$this->add_render_attribute( 'root', $root );

		$tag       = trim( (string) ( $s['tag'] ?? '' ) );
		$title_tag = Utils::validate_html_tag( $s['title_tag'] ?? 'h3' );
		$meta      = $this->meta_html( $s, $live_label, $link_url, $link_host );
		$i         = 0;
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="avix-csf__inner">
				<div class="avix-csf__text">
					<?php if ( '' !== $index || '' !== $tag ) : ?>
						<p class="avix-csf__index" data-csf-reveal style="--i:<?php echo (int) $i++; ?>;">
							<?php if ( '' !== $index ) : ?>
								<span class="avix-csf__count"><?php echo $this->index_html( $index ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in index_html(). ?></span>
							<?php endif; ?>
							<?php if ( '' !== $tag ) : ?>
								<span class="avix-csf__tag"><?php echo esc_html( $tag ); ?></span>
							<?php endif; ?>
						</p>
					<?php endif; ?>

					<?php if ( '' !== $title ) : ?>
						<<?php echo esc_attr( $title_tag ); ?> class="avix-csf__title" id="<?php echo esc_attr( $title_id ); ?>" data-csf-reveal style="--i:<?php echo (int) $i++; ?>;"><?php echo $this->accent_html( $title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in accent_html(). ?></<?php echo esc_attr( $title_tag ); ?>>
					<?php endif; ?>

					<?php if ( '' !== $problem || '' !== $built ) : ?>
						<dl class="avix-csf__qa" data-csf-reveal style="--i:<?php echo (int) $i++; ?>;">
							<?php if ( '' !== $problem ) : ?>
								<div class="avix-csf__qa-row avix-csf__qa-row--problem">
									<dt><?php echo esc_html( $this->label( $s, 'problem_label', __( 'The problem', 'avix-widgets' ) ) ); ?></dt>
									<dd><?php echo $problem; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in paras_html(). ?></dd>
								</div>
							<?php endif; ?>
							<?php if ( '' !== $built ) : ?>
								<div class="avix-csf__qa-row avix-csf__qa-row--built">
									<dt><?php echo esc_html( $this->label( $s, 'built_label', __( 'What we built', 'avix-widgets' ) ) ); ?></dt>
									<dd><?php echo $built; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- filtered with wp_kses() in paras_html(). ?></dd>
								</div>
							<?php endif; ?>
						</dl>
					<?php endif; ?>

					<?php if ( $hotspots ) : ?>
						<ol class="avix-csf__legend<?php echo $legend ? '' : ' screen-reader-text'; ?>" id="<?php echo esc_attr( 'avix-csf-legend-' . $id ); ?>" role="list" data-csf-reveal style="--i:<?php echo (int) $i++; ?>;">
							<?php foreach ( $hotspots as $n => $spot ) : ?>
								<li class="avix-csf__legend-item" id="<?php echo esc_attr( 'avix-csf-' . $id . '-h' . ( $n + 1 ) ); ?>" data-csf-item="<?php echo (int) $n; ?>">
									<span class="avix-csf__num" aria-hidden="true"><?php echo (int) ( $n + 1 ); ?></span>
									<span class="avix-csf__legend-text">
										<strong class="avix-csf__legend-label"><?php echo esc_html( $spot['label'] ); ?></strong>
										<?php if ( '' !== $spot['detail'] ) : ?>
											<span class="avix-csf__legend-detail"><?php echo esc_html( $spot['detail'] ); ?></span>
										<?php endif; ?>
									</span>
								</li>
							<?php endforeach; ?>
						</ol>
					<?php endif; ?>

					<?php if ( '' !== $meta ) : ?>
						<p class="avix-csf__meta" data-csf-reveal style="--i:<?php echo (int) $i++; ?>;"><?php echo $meta; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in meta_html(). ?></p>
					<?php endif; ?>
				</div>

				<?php if ( $media ) : ?>
					<figure class="avix-csf__media" data-csf-media>
						<?php
						echo $shot // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in frame_html() / placeholder_html().
							? $this->frame_html( $s, $shot, $hotspots, $device, $link_url, $squares )
							: $this->placeholder_html( $device );
						?>
						<?php if ( $shot && '' !== $shot['alt'] ) : ?>
							<figcaption class="screen-reader-text"><?php echo esc_html( $shot['alt'] ); ?></figcaption>
						<?php endif; ?>
						<?php echo $shot ? $this->full_link_html( $s, $shot ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in full_link_html(). ?>
					</figure>
				<?php endif; ?>
			</div>
			<?php if ( $hotspots && $shot ) : ?>
				<div class="avix-csf__callout" id="<?php echo esc_attr( 'avix-csf-callout-' . $id ); ?>" role="tooltip" hidden data-csf-callout></div>
			<?php endif; ?>
		</section>
		<?php
	}

	/**
	 * The screenshot in its device frame (on its mat) with the pins layered on
	 * the screen.
	 *
	 * @param array  $s        Settings.
	 * @param array  $shot     From shot().
	 * @param array  $hotspots From hotspots().
	 * @param string $device   browser | phone | plain.
	 * @param string $link_url Resolved "see it live" URL.
	 * @param bool   $squares  Pixel-square pins without numerals.
	 */
	private function frame_html( array $s, array $shot, array $hotspots, $device, $link_url, $squares ) {
		$id   = $this->get_id();
		$pins = '';
		foreach ( $hotspots as $n => $spot ) {
			$style = '--x:' . $this->num( $spot['x'] ) . '%;--y:' . $this->num( $spot['y'] ) . '%;--n:' . (int) $n . ';';
			$pins .= '<button type="button"'
				. ' class="avix-csf__pin elementor-repeater-item-' . esc_attr( sanitize_html_class( $spot['_id'] ) ) . '"'
				. ' style="' . esc_attr( $style ) . '"'
				. ' aria-describedby="' . esc_attr( 'avix-csf-' . $id . '-h' . ( $n + 1 ) ) . '"'
				. ' aria-expanded="false"'
				. ' aria-controls="' . esc_attr( 'avix-csf-callout-' . $id ) . '"'
				. ' data-csf-pin="' . (int) $n . '"'
				. ' data-csf-place="' . esc_attr( $spot['placement'] ) . '">'
				. '<span class="avix-csf__pin-num" aria-hidden="true">' . ( $squares ? '' : (int) ( $n + 1 ) ) . '</span>'
				/* translators: 1: hotspot number, 2: hotspot label. */
				. '<span class="screen-reader-text">' . esc_html( sprintf( __( 'Hotspot %1$d: %2$s', 'avix-widgets' ), $n + 1, $spot['label'] ) ) . '</span>'
				. '</button>';
		}

		$screen = $shot['html'];
		if ( '' !== $pins ) {
			$screen .= '<div class="avix-csf__pins" role="group" aria-label="' . esc_attr__( 'Hotspots on the screenshot', 'avix-widgets' ) . '">' . $pins . '</div>';
		}

		$url_label = trim( (string) ( $s['url_label'] ?? '' ) );
		if ( '' === $url_label && 'browser' === $device ) {
			$url_label = $this->address( $link_url );
		}
		$mat = in_array( (string) ( $s['mat'] ?? '' ), self::MATS, true ) ? (string) $s['mat'] : 'paper';
		$opt = array(
			'url_label' => $url_label,
			'ratio'     => $shot['ratio'],
			'class'     => 'avix-csf__mat',
			'pixels'    => 'yes' === ( $s['pixel_reveal'] ?? '' ),
			'mat'       => $mat,
		);

		if ( class_exists( '\AvixWidgets\Case_Studies\Kit' ) ) {
			return \AvixWidgets\Case_Studies\Kit::frame( $screen, $device, $opt );
		}

		// Same markup as Kit::frame(), in case the kit helper is missing.
		$frame = '<div class="avix-csk-frame avix-csk-frame--' . esc_attr( $device ) . '"' . ( '' !== $opt['ratio'] ? ' style="' . esc_attr( '--csk-ratio: ' . $opt['ratio'] . ';' ) . '"' : '' ) . '>';
		if ( 'browser' === $device ) {
			$frame .= '<div class="avix-csk-frame__bar" aria-hidden="true"><span class="avix-csk-frame__dots"><i></i><i></i><i></i></span>'
				. ( '' !== $url_label ? '<span class="avix-csk-frame__url">' . esc_html( $url_label ) . '</span>' : '' ) . '</div>';
		}
		$frame .= '<div class="avix-csk-frame__screen"' . ( $opt['pixels'] ? ' data-csk-pixels' : '' ) . '>' . $screen . '</div></div>';
		return '<div class="' . esc_attr( 'avix-csk-mat avix-csk-mat--' . $mat . ' avix-csk-mat--' . $device . ' avix-csf__mat' ) . '">' . $frame . '</div>';
	}

	/**
	 * Builder-only placeholder where the screenshot goes.
	 *
	 * @param string $device Frame type.
	 */
	private function placeholder_html( $device ) {
		return '<div class="avix-csf__placeholder avix-csf__placeholder--' . esc_attr( $device ) . '">'
			. '<span class="avix-csf__placeholder-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><rect x="3" y="4" width="18" height="14" rx="2"/><path d="M3 8h18M7 6h.01M10 6h.01"/></svg></span>'
			. '<span class="avix-csf__placeholder-text">' . esc_html__( 'Add a screenshot of the live site', 'avix-widgets' ) . '</span>'
			. '<span class="avix-csf__placeholder-hint">' . esc_html__( 'Pick one under Screenshot. Until then visitors only see the text.', 'avix-widgets' ) . '</span>'
			. '</div>';
	}

	/**
	 * "View full screenshot": a plain link to the image file that the script
	 * turns into a lightbox.
	 *
	 * @param array $s    Settings.
	 * @param array $shot From shot().
	 */
	private function full_link_html( array $s, array $shot ) {
		$label = trim( (string) ( $s['full_label'] ?? '' ) );
		if ( '' === $label ) {
			return '';
		}
		$media = $s['full_image'] ?? array();
		$media = is_string( $media ) ? array( 'url' => $media ) : (array) $media;
		$fid   = $this->media_id( $media );
		$url   = $fid ? (string) wp_get_attachment_url( $fid ) : trim( (string) ( $media['url'] ?? '' ) );
		$w     = 0;
		$h     = 0;
		if ( $fid ) {
			$meta = wp_get_attachment_metadata( $fid );
			$w    = absint( is_array( $meta ) ? ( $meta['width'] ?? 0 ) : 0 );
			$h    = absint( is_array( $meta ) ? ( $meta['height'] ?? 0 ) : 0 );
		} elseif ( '' === $url ) {
			$url = $shot['full'];
			$w   = $shot['w'];
			$h   = $shot['h'];
		}
		$url = esc_url( $url );
		if ( '' === $url ) {
			return '';
		}
		return '<a class="avix-csf__full" href="' . $url . '" data-csf-full'
			. ( $w && $h ? ' data-csf-w="' . (int) $w . '" data-csf-h="' . (int) $h . '"' : '' )
			. ' data-csf-alt="' . esc_attr( $shot['alt'] ) . '">'
			. '<span class="avix-csf__full-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M15 4h5v5M9 20H4v-5M20 4l-6 6M4 20l6-6"/></svg></span>'
			. '<span class="avix-csf__full-text">' . esc_html( $label ) . '</span>'
			. '</a>';
	}

	/**
	 * Attribution and the "see it live" link.
	 *
	 * @param array  $s          Settings.
	 * @param string $live_label The client's site, e.g. "rehall.com".
	 * @param string $link_url   Resolved link.
	 * @param string $link_host  Domain of the link.
	 */
	private function meta_html( array $s, $live_label, $link_url, $link_host ) {
		$parts = array();
		$mode  = (string) ( $s['attribution'] ?? 'built' );
		if ( 'none' !== $mode ) {
			$mode = 'observed' === $mode ? 'observed' : 'built';
			$text = trim( (string) ( $s['attribution_text'] ?? '' ) );
			if ( '' === $text ) {
				if ( 'observed' === $mode ) {
					/* translators: %s: the client's site, e.g. rehall.com. */
					$text = '' !== $live_label ? sprintf( __( 'Live on %s today', 'avix-widgets' ), $live_label ) : __( 'Live on the client’s site today', 'avix-widgets' );
				} else {
					$text = __( 'Built by AvixDigital', 'avix-widgets' );
				}
			}
			$parts[] = '<span class="avix-csf__attr avix-csf__attr--' . esc_attr( $mode ) . '"><span class="avix-csf__attr-dot" aria-hidden="true"></span>' . esc_html( $text ) . '</span>';
		}

		if ( '' !== $link_url ) {
			$text = trim( (string) ( $s['source_text'] ?? '' ) );
			if ( '' === $text ) {
				/* translators: %s: a domain, e.g. rehall.com. */
				$text = '' !== $link_host ? sprintf( __( 'See this on %s', 'avix-widgets' ), $link_host ) : __( 'See this live', 'avix-widgets' );
			}
			$link = (array) ( $s['source_link'] ?? array() );
			$this->add_render_attribute( 'source', 'class', 'avix-csf__link' );
			if ( '' !== trim( (string) ( $link['url'] ?? '' ) ) ) {
				$this->add_link_attributes( 'source', $link );
			} else {
				$this->add_render_attribute( 'source', 'href', $link_url );
			}
			// The client's site always opens beside this page.
			$this->set_render_attribute( 'source', 'target', '_blank' );
			$rel = $this->get_render_attributes( 'source', 'rel' );
			$rel = implode( ' ', is_array( $rel ) ? $rel : array( (string) $rel ) );
			$rel = implode( ' ', array_unique( array_filter( array_merge( preg_split( '/\s+/', trim( $rel ) ), array( 'noopener' ) ) ) ) );
			$this->set_render_attribute( 'source', 'rel', $rel );

			$parts[] = '<a ' . $this->get_render_attribute_string( 'source' ) . '>'
				. '<span class="avix-csf__link-text">' . esc_html( $text ) . '</span>'
				. $this->arrow()
				. '<span class="screen-reader-text"> ' . esc_html__( '(opens in a new tab)', 'avix-widgets' ) . '</span>'
				. '</a>';
		}

		return implode( '', $parts );
	}

	/* ------------------------------------------------------------------ */
	/* Data                                                                */
	/* ------------------------------------------------------------------ */

	/**
	 * The screenshot: image markup, ratio, alt and full-size file, or array()
	 * when there is none.
	 *
	 * @param array  $s     Settings.
	 * @param string $title Title (alt fallback).
	 */
	private function shot( array $s, $title ) {
		$media = $s['image'] ?? array();
		$media = is_string( $media ) ? array( 'url' => $media ) : (array) $media;
		$url   = trim( (string) ( $media['url'] ?? '' ) );
		$id    = $this->media_id( $media );
		if ( ! $id && '' === $url ) {
			return array();
		}
		if ( $id && ! wp_attachment_is_image( $id ) ) {
			return array();
		}

		$alt = trim( (string) ( $s['image_alt'] ?? '' ) );
		if ( '' === $alt && $id ) {
			$alt = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
		}
		if ( '' === $alt ) {
			$alt = trim( wp_strip_all_tags( str_replace( array( '[', ']' ), '', $title ) ) );
		}

		$device = (string) ( $s['device'] ?? 'browser' );
		$sizes  = array(
			'browser' => '(max-width: 900px) calc(100vw - 40px), (max-width: 1300px) 64vw, 820px',
			'phone'   => '(max-width: 600px) 86vw, 340px',
			'plain'   => '(max-width: 600px) 90vw, 460px',
		);
		$sizes  = $sizes[ $device ] ?? $sizes['browser'];
		$attr   = array(
			'class'    => 'avix-csf__img',
			'alt'      => $alt,
			'loading'  => 'lazy',
			'decoding' => 'async',
		);

		$html  = '';
		$ratio = '';
		$w     = 0;
		$h     = 0;
		$full  = '';
		if ( $id ) {
			$meta  = wp_get_attachment_metadata( $id );
			$w     = absint( is_array( $meta ) ? ( $meta['width'] ?? 0 ) : 0 );
			$h     = absint( is_array( $meta ) ? ( $meta['height'] ?? 0 ) : 0 );
			$full  = (string) wp_get_attachment_url( $id );
			$ratio = class_exists( '\AvixWidgets\Case_Studies\Kit' ) ? \AvixWidgets\Case_Studies\Kit::ratio( $id ) : ( $w && $h ? $w . ' / ' . $h : '' );
			// GIFs keep the original file: resized GIFs stop animating.
			if ( 'image/gif' !== get_post_mime_type( $id ) ) {
				$html = class_exists( '\AvixWidgets\Case_Studies\Kit' )
					? \AvixWidgets\Case_Studies\Kit::img( $id, 'full', $sizes, $attr )
					: (string) wp_get_attachment_image( $id, 'full', false, array_merge( $attr, array( 'sizes' => $sizes ) ) );
				$html = $this->lazy_img( $html );
			}
			if ( '' === $url ) {
				$url = $full;
			}
		}
		if ( '' === $html ) {
			$src = esc_url( $url );
			if ( '' === $src ) {
				return array();
			}
			$html = sprintf(
				'<img class="avix-csf__img" src="%s" alt="%s"%s loading="lazy" decoding="async">',
				$src,
				esc_attr( $alt ),
				$w && $h ? ' width="' . (int) $w . '" height="' . (int) $h . '"' : ''
			);
		}

		return array(
			'html'  => $html,
			'ratio' => $ratio,
			'alt'   => $alt,
			'full'  => '' !== $full ? $full : $url,
			'w'     => $w,
			'h'     => $h,
		);
	}

	/**
	 * Hotspot rows with a label, normalised.
	 *
	 * @param array $rows Repeater rows.
	 */
	private function hotspots( array $rows ) {
		$out = array();
		foreach ( $rows as $n => $row ) {
			$row   = (array) $row;
			$label = trim( wp_strip_all_tags( (string) ( $row['label'] ?? '' ) ) );
			if ( '' === $label ) {
				continue;
			}
			$place = (string) ( $row['placement'] ?? 'auto' );
			$out[] = array(
				'_id'       => '' !== (string) ( $row['_id'] ?? '' ) ? (string) $row['_id'] : 'h' . $n,
				'x'         => $this->pct( $row['x'] ?? 50 ),
				'y'         => $this->pct( $row['y'] ?? 50 ),
				'label'     => $label,
				'detail'    => trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) ( $row['detail'] ?? '' ) ) ) ),
				'placement' => in_array( $place, self::PLACEMENTS, true ) ? $place : 'auto',
			);
		}
		return $out;
	}

	/**
	 * This widget's place among the spotlights of the document it lives in:
	 * array( 'pos' => 1-based or 0 when unknown, 'count' => n ). Empty
	 * spotlights (starter shells that visitors never see) are not counted, so
	 * the numbers on the page always run 01, 02, 03 …
	 */
	private function position() {
		$post = $this->document_id();
		$list = array();
		$live = $post ? self::spotlights_in( $post ) : array();
		if ( $post ) {
			if ( class_exists( '\AvixWidgets\Case_Studies\Case_Study' ) && method_exists( '\AvixWidgets\Case_Studies\Case_Study', 'spotlights' ) ) {
				foreach ( (array) \AvixWidgets\Case_Studies\Case_Study::spotlights( $post ) as $item ) {
					$list[] = (string) ( is_array( $item ) ? ( $item['id'] ?? '' ) : $item );
				}
			} else {
				$list = array_keys( $live );
			}
		}
		$list = array_values(
			array_filter(
				$list,
				static function ( $id ) use ( $live ) {
					return '' !== $id && ( ! isset( $live[ $id ] ) || $live[ $id ] );
				}
			)
		);
		$pos  = array_search( (string) $this->get_id(), $list, true );
		return array(
			'pos'   => false === $pos ? 0 : $pos + 1,
			'count' => count( $list ),
		);
	}

	/**
	 * The post whose Elementor data holds this widget: the document being
	 * rendered (a library template counts as its own), else the current post.
	 */
	private function document_id() {
		$doc = null;
		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->documents ) ) {
			$doc = \Elementor\Plugin::$instance->documents->get_current();
		}
		if ( $doc && method_exists( $doc, 'get_main_id' ) ) {
			return (int) $doc->get_main_id();
		}
		return (int) get_the_ID();
	}

	/**
	 * Spotlight widget ids in document order => whether visitors see it (it
	 * has a screenshot or text, typed or dynamic), read from the saved
	 * Elementor data. Also the fallback for Case_Study::spotlights(). Cached
	 * per request.
	 *
	 * @param int $post_id Post ID.
	 */
	private static function spotlights_in( $post_id ) {
		static $cache = array();
		$post_id = (int) $post_id;
		if ( isset( $cache[ $post_id ] ) ) {
			return $cache[ $post_id ];
		}
		$raw  = get_post_meta( $post_id, '_elementor_data', true );
		$data = is_string( $raw ) ? json_decode( $raw, true ) : ( is_array( $raw ) ? $raw : array() );
		$list = array();
		$walk = static function ( $elements ) use ( &$walk, &$list ) {
			foreach ( (array) $elements as $el ) {
				if ( ! is_array( $el ) ) {
					continue;
				}
				if ( 'widget' === ( $el['elType'] ?? '' ) && 'avix-case-study-spotlight' === ( $el['widgetType'] ?? '' ) && ! empty( $el['id'] ) ) {
					$set  = isset( $el['settings'] ) && is_array( $el['settings'] ) ? $el['settings'] : array();
					$dyn  = isset( $set['__dynamic__'] ) && is_array( $set['__dynamic__'] ) ? $set['__dynamic__'] : array();
					$img  = $set['image'] ?? '';
					$shot = is_array( $img ) ? ( ! empty( $img['id'] ) || '' !== trim( (string) ( $img['url'] ?? '' ) ) ) : '' !== trim( (string) $img );
					$text = '' !== trim( (string) ( $set['problem'] ?? '' ) ) || '' !== trim( (string) ( $set['built'] ?? '' ) );
					$list[ (string) $el['id'] ] = $shot || $text || ! empty( $dyn['image'] ) || ! empty( $dyn['problem'] ) || ! empty( $dyn['built'] );
				}
				if ( ! empty( $el['elements'] ) ) {
					$walk( $el['elements'] );
				}
			}
		};
		$walk( is_array( $data ) ? $data : array() );
		$cache[ $post_id ] = $list;
		return $list;
	}

	/* ------------------------------------------------------------------ */
	/* Helpers                                                             */
	/* ------------------------------------------------------------------ */

	private function in_editor() {
		return class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->editor ) && \Elementor\Plugin::$instance->editor->is_edit_mode();
	}

	/**
	 * A label control, or its default when cleared.
	 *
	 * @param array  $s        Settings.
	 * @param string $key      Control.
	 * @param string $fallback Default.
	 */
	private function label( array $s, $key, $fallback ) {
		$value = trim( (string) ( $s[ $key ] ?? '' ) );
		return '' !== $value ? $value : $fallback;
	}

	/**
	 * Slider value (array or number) → 0–100 with at most two decimals.
	 *
	 * @param mixed $value Control value.
	 */
	private function pct( $value ) {
		if ( is_array( $value ) ) {
			$value = $value['size'] ?? 50;
		}
		if ( '' === $value || null === $value || ! is_numeric( $value ) ) {
			$value = 50;
		}
		return round( max( 0, min( 100, (float) $value ) ), 2 );
	}

	/**
	 * Locale-proof number for CSS ("4.2", never "4,2").
	 *
	 * @param float $value Number.
	 */
	private function num( $value ) {
		$out = rtrim( rtrim( number_format( (float) $value, 2, '.', '' ), '0' ), '.' );
		return '' === $out ? '0' : $out;
	}

	/**
	 * Only http(s) links.
	 *
	 * @param string $url URL.
	 */
	private function safe_url( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url || ! preg_match( '#^https?://#i', $url ) ) {
			return '';
		}
		return esc_url_raw( $url );
	}

	/**
	 * "https://www.flowstorage.nl/x" → "flowstorage.nl".
	 *
	 * @param string $url URL.
	 */
	private function domain( $url ) {
		if ( '' === (string) $url ) {
			return '';
		}
		if ( class_exists( '\AvixWidgets\Case_Studies\Case_Study' ) && method_exists( '\AvixWidgets\Case_Studies\Case_Study', 'domain' ) ) {
			return (string) \AvixWidgets\Case_Studies\Case_Study::domain( (string) $url );
		}
		$host = (string) wp_parse_url( (string) $url, PHP_URL_HOST );
		return strtolower( (string) preg_replace( '/^www\./i', '', $host ) );
	}

	/**
	 * Browser bar text: domain + path, e.g. "rehall.com/collections/mens-ski-jackets".
	 *
	 * @param string $url URL.
	 */
	private function address( $url ) {
		$host = $this->domain( $url );
		if ( '' === $host ) {
			return '';
		}
		$path = trim( (string) wp_parse_url( (string) $url, PHP_URL_PATH ), '/' );
		return '' !== $path ? $host . '/' . rawurldecode( $path ) : $host;
	}

	/**
	 * "01 / 06": the position in ink, the count muted.
	 *
	 * @param string $index Index text.
	 */
	private function index_html( $index ) {
		if ( preg_match( '#^\s*(\d+)\s*/\s*(\d+)\s*$#', $index, $m ) ) {
			return '<span class="avix-csf__count-now">' . esc_html( $m[1] ) . '</span><span class="avix-csf__count-of">/ ' . esc_html( $m[2] ) . '</span>';
		}
		return '<span class="avix-csf__count-now">' . esc_html( $index ) . '</span>';
	}

	/**
	 * Paragraphs from a textarea: plain text is escaped; "built" keeps a tight
	 * allowlist of tags, and new-tab links get rel="noopener".
	 *
	 * @param string $text  Raw text.
	 * @param bool   $rich  Allow a/strong/em.
	 */
	private function paras_html( $text, $rich ) {
		$text = trim( (string) $text );
		if ( '' === $text ) {
			return '';
		}
		$html = '';
		foreach ( preg_split( '/(?:\r\n|\r|\n)\s*(?:\r\n|\r|\n)/', $text ) as $para ) {
			$para = trim( (string) $para );
			if ( '' === $para ) {
				continue;
			}
			if ( $rich ) {
				$para = wp_kses( nl2br( $para, false ), self::BUILT_TAGS );
				$para = (string) preg_replace_callback(
					'/<a\s[^>]*>/i',
					static function ( $m ) {
						$tag = $m[0];
						if ( ! preg_match( '/\starget=("|\')_blank\1/i', $tag ) ) {
							return $tag;
						}
						if ( preg_match( '/\srel=("|\')([^"\']*)\1/i', $tag, $rel ) ) {
							return false !== stripos( $rel[2], 'noopener' ) ? $tag : str_replace( $rel[0], ' rel="' . esc_attr( trim( $rel[2] . ' noopener' ) ) . '"', $tag );
						}
						return substr( $tag, 0, -1 ) . ' rel="noopener">';
					},
					$para
				);
			} else {
				$para = nl2br( esc_html( $para ), false );
			}
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
			$out    = preg_replace( '/\[([^\[\]]+)\]/u', '<span class="avix-csf__accent">$1</span>', esc_html( $line ) );
			$html[] = null === $out ? esc_html( $line ) : $out;
		}
		return implode( ' <br>', $html );
	}

	private function arrow() {
		if ( class_exists( '\AvixWidgets\Case_Studies\Kit' ) ) {
			return \AvixWidgets\Case_Studies\Kit::arrow( 'ne' );
		}
		return '<svg class="avix-csk-arrow avix-csk-arrow--ne" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M7 17 17 7M7 7h10v10"/></svg>';
	}

	/**
	 * Keeps the screenshot lazy. Some sites filter attachment images to
	 * loading="eager", which made below-the-fold shots compete with the hero.
	 *
	 * @param string $html Image markup.
	 */
	private function lazy_img( $html ) {
		$html = (string) $html;
		if ( '' === $html || ! class_exists( '\WP_HTML_Tag_Processor' ) ) {
			return $html;
		}
		$tags = new \WP_HTML_Tag_Processor( $html );
		if ( ! $tags->next_tag( 'img' ) ) {
			return $html;
		}
		// Only touch what a filter changed, so nothing is written twice.
		$changed = false;
		if ( 'lazy' !== $tags->get_attribute( 'loading' ) ) {
			$tags->set_attribute( 'loading', 'lazy' );
			$changed = true;
		}
		if ( null !== $tags->get_attribute( 'fetchpriority' ) ) {
			$tags->remove_attribute( 'fetchpriority' );
			$changed = true;
		}
		return $changed ? $tags->get_updated_html() : $html;
	}
}
