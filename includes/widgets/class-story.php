<?php
/**
 * Story: the About page manifesto. A large statement fills from grey to ink
 * as the visitor scrolls through it, with small rounded photos set inline
 * between the words. Below it, a wide photo card carries a dark stats panel
 * that counts up, and the pixel character sits on the panel's top edge.
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

class Story extends Widget_Base {

	use Media;

	const UPLOADS = 'https://avixdigital.com/wp-content/uploads/';

	public function get_name(): string {
		return 'avix-story';
	}

	public function get_title(): string {
		return esc_html__( 'Story', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-text';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'story', 'about', 'manifesto', 'statement', 'stats', 'numbers', 'mission', 'avix' );
	}

	public function get_style_depends(): array {
		return array( 'avix-story' );
	}

	public function get_script_depends(): array {
		return array( 'avix-story' );
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/* ------------------------------------------------------------------ */
	/* Controls                                                            */
	/* ------------------------------------------------------------------ */

	protected function register_controls(): void {
		$this->controls_header();
		$this->controls_pills();
		$this->controls_media();
		$this->controls_buddy();
		$this->controls_motion();
		$this->controls_style();
	}

	private function controls_header() {
		$this->start_controls_section( 'section_header', array( 'label' => esc_html__( 'Statement', 'avix-widgets' ) ) );

		$this->add_control(
			'eyebrow',
			array(
				'label'       => esc_html__( 'Eyebrow', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Our story', 'avix-widgets' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'sticky_eyebrow',
			array(
				'label'       => esc_html__( 'Keep the eyebrow in view', 'avix-widgets' ),
				'description' => esc_html__( 'On wide screens the eyebrow stays beside the statement while visitors read it.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'statement',
			array(
				'label'       => esc_html__( 'Statement', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 5,
				'default'     => esc_html__( 'We bring design and development together {1} to build websites, Shopify stores {2} and custom web applications {3} around [your goals], so customers find what they need and your team can keep growing.', 'avix-widgets' ),
				'description' => esc_html__( 'Wrap words in [brackets] to highlight them in orange. Type {1}, {2}, {3}… to place the matching picture from "Inline pictures" between the words. Press Enter for a new line.', 'avix-widgets' ),
				'separator'   => 'before',
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'statement_tag',
			array(
				'label'   => esc_html__( 'Statement HTML tag', 'avix-widgets' ),
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
				'label'     => esc_html__( 'Supporting paragraph', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXTAREA,
				'rows'      => 4,
				'default'   => esc_html__( 'AvixDigital started in 2020 with a simple idea: a useful website should make your offer clearer, help customers take the next step and fit the way your team works.', 'avix-widgets' ),
				'separator' => 'before',
				'dynamic'   => array( 'active' => true ),
			)
		);

		$this->add_control(
			'link_text',
			array(
				'label'       => esc_html__( 'Link text', 'avix-widgets' ),
				'description' => esc_html__( 'Optional. Shows a small arrow link beside the paragraph.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'link',
			array(
				'label'     => esc_html__( 'Link', 'avix-widgets' ),
				'type'      => Controls_Manager::URL,
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'link_text!' => '' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_pills() {
		$this->start_controls_section( 'section_pills', array( 'label' => esc_html__( 'Inline Pictures', 'avix-widgets' ) ) );

		$this->add_control(
			'pills_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Picture 1 replaces {1} in the statement, picture 2 replaces {2}, and so on. They show as small rounded capsules between the words.', 'avix-widgets' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'image',
			array(
				'label'   => esc_html__( 'Picture', 'avix-widgets' ),
				'type'    => Controls_Manager::MEDIA,
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'label',
			array(
				'label'       => esc_html__( 'Name in the list', 'avix-widgets' ),
				'description' => esc_html__( 'Only helps you find it here; visitors do not see it.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Picture', 'avix-widgets' ),
				'label_block' => true,
			)
		);

		$repeater->add_control(
			'position',
			array(
				'label'   => esc_html__( 'Crop focus', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'center',
				'options' => array(
					'center' => esc_html__( 'Centre', 'avix-widgets' ),
					'top'    => esc_html__( 'Top', 'avix-widgets' ),
					'bottom' => esc_html__( 'Bottom', 'avix-widgets' ),
					'left'   => esc_html__( 'Left', 'avix-widgets' ),
					'right'  => esc_html__( 'Right', 'avix-widgets' ),
				),
			)
		);

		$repeater->add_control(
			'zoom',
			array(
				'label'       => esc_html__( 'Zoom', 'avix-widgets' ),
				'description' => esc_html__( 'Zoom into the crop focus so a small detail (a screen, a face) fills the capsule.', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%' ),
				'range'       => array( '%' => array( 'min' => 100, 'max' => 250 ) ),
				'default'     => array(
					'unit' => '%',
					'size' => 100,
				),
			)
		);

		$this->add_control(
			'pills',
			array(
				'label'       => esc_html__( 'Pictures', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => "{{{ label || 'Picture' }}}",
				'default'     => array(
					array(
						'label' => esc_html__( '{1} Founder at work', 'avix-widgets' ),
						'image' => array( 'url' => self::UPLOADS . '2025/12/FullSizeRender-3.jpg' ),
					),
					array(
						'label'    => esc_html__( '{2} Shopify store on a laptop', 'avix-widgets' ),
						'image'    => array( 'url' => self::UPLOADS . '2026/06/Untitled-design235.png' ),
						'position' => 'center',
						'zoom'     => array(
							'unit' => '%',
							'size' => 170,
						),
					),
					array(
						'label'    => esc_html__( '{3} Client portal', 'avix-widgets' ),
						'image'    => array( 'url' => self::UPLOADS . '2026/09/ChatGPT-Image-29-Sept-2026-02_09_56.png' ),
						'position' => 'right',
						'zoom'     => array(
							'unit' => '%',
							'size' => 160,
						),
					),
				),
			)
		);

		$this->end_controls_section();
	}

	private function controls_media() {
		$this->start_controls_section( 'section_media', array( 'label' => esc_html__( 'Photo & Numbers', 'avix-widgets' ) ) );

		$this->add_control(
			'show_media',
			array(
				'label'   => esc_html__( 'Show photo card', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'media_image',
			array(
				'label'     => esc_html__( 'Photo', 'avix-widgets' ),
				'type'      => Controls_Manager::MEDIA,
				'default'   => array( 'url' => self::UPLOADS . '2026/03/Untitled-design227.webp' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_media' => 'yes' ),
			)
		);

		$this->add_control(
			'media_alt',
			array(
				'label'       => esc_html__( 'Photo description (alt text)', 'avix-widgets' ),
				'description' => esc_html__( 'Leave empty to use the description saved in the Media Library.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_media' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'media_focus_x',
			array(
				'label'          => esc_html__( 'Photo focus (left ↔ right)', 'avix-widgets' ),
				'description'    => esc_html__( 'Phones show a taller crop of the photo: move the focus so the people stay in the picture.', 'avix-widgets' ),
				'type'           => Controls_Manager::SLIDER,
				'mobile_default' => array(
					'unit' => '%',
					'size' => 36,
				),
				'size_units'     => array( '%' ),
				'range'          => array( '%' => array( 'min' => 0, 'max' => 100 ) ),
				'selectors'      => array( '{{WRAPPER}} .avix-st' => '--st-focus-x: {{SIZE}}%;' ),
				'condition'      => array( 'show_media' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'media_focus_y',
			array(
				'label'      => esc_html__( 'Photo focus (top ↕ bottom)', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array( '%' => array( 'min' => 0, 'max' => 100 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-st' => '--st-focus-y: {{SIZE}}%;' ),
				'condition'  => array( 'show_media' => 'yes' ),
			)
		);

		$this->add_control(
			'panel_layout',
			array(
				'label'       => esc_html__( 'Numbers panel', 'avix-widgets' ),
				'description' => esc_html__( 'The bar keeps the faces in the default team photo clear. The cards suit photos with a free corner. On tablets and phones the panel always sits under the photo.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'bar',
				'options'     => array(
					'bar'   => esc_html__( 'Bar along the bottom', 'avix-widgets' ),
					'left'  => esc_html__( 'Card, bottom left', 'avix-widgets' ),
					'right' => esc_html__( 'Card, bottom right', 'avix-widgets' ),
				),
				'condition'   => array( 'show_media' => 'yes' ),
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'prefix',
			array(
				'label'   => esc_html__( 'Prefix', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'value',
			array(
				'label'       => esc_html__( 'Number', 'avix-widgets' ),
				'description' => esc_html__( 'Digits only, decimals allowed (e.g. 250 or 5.0).', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '100',
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'suffix',
			array(
				'label'   => esc_html__( 'Suffix', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '+',
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'label',
			array(
				'label'       => esc_html__( 'Label', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Label', 'avix-widgets' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'count',
			array(
				'label'       => esc_html__( 'Count up', 'avix-widgets' ),
				'description' => esc_html__( 'Turn off for years, so 2020 never ticks up from 0. Decimal ratings such as 4.9 count up from one below, never from 0.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'stats',
			array(
				'label'       => esc_html__( 'Numbers', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ prefix }}}{{{ value }}}{{{ suffix }}} · {{{ label }}}',
				'separator'   => 'before',
				'default'     => array(
					array(
						'value'  => '250',
						'suffix' => '+',
						'label'  => esc_html__( 'Websites built', 'avix-widgets' ),
					),
					array(
						'value'  => '200',
						'suffix' => '+',
						'label'  => esc_html__( 'Clients supported worldwide', 'avix-widgets' ),
					),
					array(
						'value'  => '2020',
						'suffix' => '',
						'label'  => esc_html__( 'Building since', 'avix-widgets' ),
						'count'  => '',
					),
				),
				'condition'   => array( 'show_media' => 'yes' ),
			)
		);

		$this->add_control(
			'thousands',
			array(
				'label'     => esc_html__( 'Thousands separator', 'avix-widgets' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => ',',
				'options'   => array(
					','  => esc_html__( 'Comma (1,000)', 'avix-widgets' ),
					'.'  => esc_html__( 'Dot (1.000)', 'avix-widgets' ),
					' '  => esc_html__( 'Space (1 000)', 'avix-widgets' ),
					''   => esc_html__( 'None (1000)', 'avix-widgets' ),
				),
				'condition' => array( 'show_media' => 'yes' ),
			)
		);

		$this->add_control(
			'show_rating',
			array(
				'label'     => esc_html__( 'Show rating line', 'avix-widgets' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'separator' => 'before',
				'condition' => array( 'show_media' => 'yes' ),
			)
		);

		$this->add_control(
			'rating_stars',
			array(
				'label'     => esc_html__( 'Stars', 'avix-widgets' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 5,
				'min'       => 0,
				'max'       => 5,
				'step'      => 1,
				'condition' => array(
					'show_media'  => 'yes',
					'show_rating' => 'yes',
				),
			)
		);

		$this->add_control(
			'rating_text',
			array(
				'label'       => esc_html__( 'Rating text', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( '5.0 Fiverr Pro rating', 'avix-widgets' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array(
					'show_media'  => 'yes',
					'show_rating' => 'yes',
				),
			)
		);

		$this->add_control(
			'rating_link',
			array(
				'label'     => esc_html__( 'Rating link', 'avix-widgets' ),
				'type'      => Controls_Manager::URL,
				'dynamic'   => array( 'active' => true ),
				'condition' => array(
					'show_media'  => 'yes',
					'show_rating' => 'yes',
				),
			)
		);

		$this->end_controls_section();
	}

	private function controls_buddy() {
		$this->start_controls_section( 'section_buddy', array( 'label' => esc_html__( 'Pixel Character', 'avix-widgets' ) ) );

		$this->add_control(
			'show_buddy',
			array(
				'label'       => esc_html__( 'Show pixel character', 'avix-widgets' ),
				'description' => esc_html__( 'It sits on the numbers panel with its legs dangling, waves now and then, looks at the number you point at and cheers when the counting is done.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_responsive_control(
			'buddy_size',
			array(
				'label'      => esc_html__( 'Character size', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 20, 'max' => 80 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-st .avix-st__panel' => '--st-pal-w: {{SIZE}}{{UNIT}};' ),
				'condition'  => array( 'show_buddy' => 'yes' ),
			)
		);

		$this->add_control(
			'buddy_side',
			array(
				'label'       => esc_html__( 'Seat on the panel', 'avix-widgets' ),
				'description' => esc_html__( 'Automatic: the right end of the bar, the outer end of a card (towards the photo edge), or over the stars when the panel only holds the rating.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				// "auto" seats it at the right end of the bar, exactly where it has always sat on the default layout.
				'default'     => 'auto',
				'options'     => array(
					'auto'  => esc_html__( 'Automatic', 'avix-widgets' ),
					'right' => esc_html__( 'Right end', 'avix-widgets' ),
					'left'  => esc_html__( 'Left end', 'avix-widgets' ),
				),
				'condition'   => array( 'show_buddy' => 'yes' ),
			)
		);

		$this->add_control(
			'wave_every',
			array(
				'label'       => esc_html__( 'Wave about every (seconds)', 'avix-widgets' ),
				'description' => esc_html__( '0 turns the waving off.', 'avix-widgets' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 8,
				'min'         => 0,
				'max'         => 60,
				'condition'   => array( 'show_buddy' => 'yes' ),
			)
		);

		$this->add_control(
			'cheer',
			array(
				'label'     => esc_html__( 'Cheer when the numbers finish', 'avix-widgets' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'show_buddy' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_motion() {
		$this->start_controls_section( 'section_motion', array( 'label' => esc_html__( 'Motion', 'avix-widgets' ) ) );

		$this->add_control(
			'motion_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'All motion is skipped for visitors who prefer reduced motion, and while you edit in Elementor the text is shown fully inked.', 'avix-widgets' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->add_control(
			'fill',
			array(
				'label'       => esc_html__( 'Ink the words on scroll', 'avix-widgets' ),
				'description' => esc_html__( 'Words start light grey and fill with ink as visitors scroll through the statement.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'fill_start',
			array(
				'label'       => esc_html__( 'Start inking at (% of screen height)', 'avix-widgets' ),
				'description' => esc_html__( 'Where the top of the statement is when the first word fills. 85 = near the bottom of the screen.', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%' ),
				'range'       => array( '%' => array( 'min' => 50, 'max' => 100 ) ),
				'default'     => array(
					'unit' => '%',
					'size' => 85,
				),
				'condition'   => array( 'fill' => 'yes' ),
			)
		);

		$this->add_control(
			'fill_end',
			array(
				'label'       => esc_html__( 'Finish inking at (% of screen height)', 'avix-widgets' ),
				'description' => esc_html__( 'Where the bottom of the statement is when the last word fills. 50 = the middle of the screen. Keep it at least 5 below the start value. Near the end of a page the text always finishes inking before the visitor reaches the bottom.', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%' ),
				'range'       => array( '%' => array( 'min' => 10, 'max' => 90 ) ),
				'default'     => array(
					'unit' => '%',
					'size' => 62,
				),
				'condition'   => array( 'fill' => 'yes' ),
			)
		);

		$this->add_control(
			'reveal',
			array(
				'label'     => esc_html__( 'Reveal on scroll', 'avix-widgets' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'zoom',
			array(
				'label'       => esc_html__( 'Slow photo zoom on scroll', 'avix-widgets' ),
				'description' => esc_html__( 'The photo settles from a slight zoom as it scrolls into view.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'count_up',
			array(
				'label'   => esc_html__( 'Count up numbers', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'duration',
			array(
				'label'     => esc_html__( 'Count duration (ms)', 'avix-widgets' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 1800,
				'min'       => 300,
				'max'       => 6000,
				'step'      => 100,
				'condition' => array( 'count_up' => 'yes' ),
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
				'default' => 'light',
				'options' => array(
					'light' => esc_html__( 'Light', 'avix-widgets' ),
					'warm'  => esc_html__( 'Warm paper', 'avix-widgets' ),
					'dark'  => esc_html__( 'Dark', 'avix-widgets' ),
				),
			)
		);

		$colors = array(
			'bg'         => array( esc_html__( 'Background', 'avix-widgets' ), '--st-bg' ),
			'ink'        => array( esc_html__( 'Inked words & eyebrow', 'avix-widgets' ), '--st-ink' ),
			'dim'        => array( esc_html__( 'Words before inking', 'avix-widgets' ), '--st-dim' ),
			'muted'      => array( esc_html__( 'Paragraph', 'avix-widgets' ), '--st-muted' ),
			'accent'     => array( esc_html__( 'Accent (highlight, square, stars)', 'avix-widgets' ), '--st-accent' ),
			'panel_bg'   => array( esc_html__( 'Numbers panel', 'avix-widgets' ), '--st-panel-bg' ),
			'panel_ink'  => array( esc_html__( 'Numbers panel text', 'avix-widgets' ), '--st-panel-ink' ),
			'card_bg'    => array( esc_html__( 'Photo card frame (tablets & phones)', 'avix-widgets' ), '--st-card-bg' ),
			'buddy'      => array( esc_html__( 'Pixel character', 'avix-widgets' ), '--st-buddy' ),
		);
		foreach ( $colors as $key => $color ) {
			$this->add_control(
				'color_' . $key,
				array(
					'label'     => $color[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-st' => $color[1] . ': {{VALUE}};' ),
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
				'selectors'          => array( '{{WRAPPER}} .avix-st' => 'padding-top: {{TOP}}{{UNIT}}; padding-bottom: {{BOTTOM}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'content_width',
			array(
				'label'      => esc_html__( 'Content width', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array( 'min' => 600, 'max' => 1800 ),
					'%'  => array( 'min' => 50, 'max' => 100 ),
				),
				'selectors'  => array( '{{WRAPPER}} .avix-st' => '--st-max: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'gutter',
			array(
				'label'      => esc_html__( 'Side spacing', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-st .avix-st__inner' => '--st-gutter: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'media_height',
			array(
				'label'       => esc_html__( 'Photo height', 'avix-widgets' ),
				'description' => esc_html__( 'Leave empty for the built-in shape: wide on desktop, taller on phones.', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', 'vh' ),
				'range'       => array(
					'px' => array( 'min' => 240, 'max' => 900 ),
					'vh' => array( 'min' => 20, 'max' => 100 ),
				),
				'selectors'   => array( '{{WRAPPER}} .avix-st .avix-st__media' => '--st-shot-h: {{SIZE}}{{UNIT}};' ),
				'separator'   => 'before',
			)
		);

		$this->add_responsive_control(
			'media_radius',
			array(
				'label'      => esc_html__( 'Photo corner radius', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-st .avix-st__media' => '--st-radius: {{SIZE}}{{UNIT}};' ),
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

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'statement_typography',
				'label'    => esc_html__( 'Statement', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-st .avix-st__statement',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'text_typography',
				'label'    => esc_html__( 'Paragraph', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-st .avix-st__text',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'eyebrow_typography',
				'label'    => esc_html__( 'Eyebrow', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-st .avix-st__eyebrow',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'value_typography',
				'label'    => esc_html__( 'Numbers', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-st .avix-st__value',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'label_typography',
				'label'    => esc_html__( 'Number labels & rating', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-st .avix-st__label, {{WRAPPER}} .avix-st .avix-st__rating',
			)
		);

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ */
	/* Render                                                              */
	/* ------------------------------------------------------------------ */

	protected function render(): void {
		$s         = $this->get_settings_for_display();
		$statement = trim( (string) $s['statement'] );
		$text      = trim( (string) $s['text'] );
		$eyebrow   = trim( (string) $s['eyebrow'] );
		$stats     = array_values(
			array_filter(
				(array) $s['stats'],
				static function ( $row ) {
					return '' !== trim( (string) ( $row['value'] ?? '' ) ) || '' !== trim( (string) ( $row['label'] ?? '' ) );
				}
			)
		);
		$media     = 'yes' === $s['show_media'];
		$image     = (array) $s['media_image'];
		$rating    = 'yes' === $s['show_rating'] && ( '' !== trim( (string) $s['rating_text'] ) || (int) $s['rating_stars'] > 0 );
		$has_panel = $media && ( $stats || $rating );
		$has_media = $media && ( ! empty( $image['url'] ) || $has_panel );

		if ( '' === $statement && '' === $text && ! $has_media ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="elementor-alert elementor-alert-info">' . esc_html__( 'Story: add a statement, a paragraph or the photo card.', 'avix-widgets' ) . '</div>';
			}
			return;
		}

		$theme    = in_array( $s['theme'], array( 'light', 'warm', 'dark' ), true ) ? $s['theme'] : 'light';
		$title_id = 'avix-st-title-' . $this->get_id();
		$label_id = 'avix-st-eyebrow-' . $this->get_id();
		$buddy    = $has_panel && 'yes' === $s['show_buddy'];
		$classes  = array( 'avix-st', 'avix-st--' . $theme );
		if ( 'yes' === $s['sticky_eyebrow'] ) {
			$classes[] = 'avix-st--sticky';
		}
		if ( '' === $eyebrow ) {
			$classes[] = 'avix-st--no-eyebrow';
		}
		if ( '' === $statement ) {
			$classes[] = 'avix-st--no-statement';
		}

		$start = isset( $s['fill_start']['size'] ) && '' !== $s['fill_start']['size'] ? (float) $s['fill_start']['size'] : 85;
		$end   = isset( $s['fill_end']['size'] ) && '' !== $s['fill_end']['size'] ? (float) $s['fill_end']['size'] : 62;
		$start = max( 0.5, min( 1, $start / 100 ) );
		// The finish line must stay above the start line, or every word would ink at once.
		$end   = max( 0.1, min( 0.9, $end / 100, $start - 0.05 ) );
		// An emptied number field means "use the default", not the 300 ms minimum.
		$count = '' === trim( (string) ( $s['duration'] ?? '' ) ) ? 1800 : max( 300, min( 6000, (int) $s['duration'] ) );

		$this->add_render_attribute(
			'root',
			array(
				'class'        => $classes,
				'data-avix-st' => wp_json_encode(
					array(
						'fill'     => 'yes' === $s['fill'],
						'start'    => round( $start, 3 ),
						'end'      => round( $end, 3 ),
						'reveal'   => 'yes' === $s['reveal'],
						'zoom'     => 'yes' === $s['zoom'],
						'countUp'  => 'yes' === $s['count_up'],
						'duration' => $count,
						'wave'     => $buddy ? max( 0, min( 60, (int) $s['wave_every'] ) ) : 0,
						'cheer'    => $buddy && 'yes' === $s['cheer'],
					)
				),
			)
		);
		// The short eyebrow ("Our story") names the region: the whole manifesto would otherwise be read out on every landmark jump.
		if ( '' !== $eyebrow ) {
			$this->add_render_attribute( 'root', 'aria-labelledby', $label_id );
		} elseif ( '' !== $statement ) {
			$this->add_render_attribute( 'root', 'aria-labelledby', $title_id );
		}

		$tag = Utils::validate_html_tag( $s['statement_tag'] );
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="avix-st__inner">
				<?php if ( '' !== $statement || '' !== $text || '' !== $eyebrow ) : ?>
					<div class="avix-st__grid">
						<?php if ( '' !== $eyebrow ) : ?>
							<div class="avix-st__aside">
								<p class="avix-st__eyebrow" id="<?php echo esc_attr( $label_id ); ?>"><?php echo esc_html( $eyebrow ); ?></p>
							</div>
						<?php endif; ?>
						<div class="avix-st__main">
							<?php if ( '' !== $statement ) : ?>
								<<?php echo esc_attr( $tag ); ?> class="avix-st__statement" id="<?php echo esc_attr( $title_id ); ?>" data-st-statement><?php echo $this->statement_html( $statement, (array) $s['pills'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in statement_html(). ?></<?php echo esc_attr( $tag ); ?>>
							<?php endif; ?>
							<?php $this->render_foot( $s, $text ); ?>
						</div>
					</div>
				<?php endif; ?>

				<?php
				if ( $has_media ) {
					$this->render_media( $s, $image, $stats, $rating, $buddy );
				}
				?>
			</div>
		</section>
		<?php
	}

	private function render_foot( array $s, $text ) {
		$link_text = trim( (string) $s['link_text'] );
		// Decide on the escaped URL: esc_url() empties unsafe schemes, which would otherwise leave href="".
		$has_link  = '' !== $link_text && '' !== esc_url( (string) ( $s['link']['url'] ?? '' ) );
		if ( '' === $text && ! $has_link ) {
			return;
		}
		?>
		<div class="avix-st__foot" data-st-reveal>
			<?php if ( '' !== $text ) : ?>
				<p class="avix-st__text"><?php echo nl2br( esc_html( $text ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped before nl2br(). ?></p>
			<?php endif; ?>
			<?php
			if ( $has_link ) :
				$this->add_link_attributes( 'link', $s['link'] );
				$this->add_render_attribute( 'link', 'class', 'avix-st__link' );
				?>
				<a <?php $this->print_render_attribute_string( 'link' ); ?>>
					<span><?php echo esc_html( $link_text ); ?></span>
					<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
				</a>
			<?php endif; ?>
		</div>
		<?php
	}

	private function render_media( array $s, array $image, array $stats, $rating, $buddy ) {
		$separator = in_array( (string) $s['thousands'], array( ',', '.', ' ', '' ), true ) ? (string) $s['thousands'] : ',';
		$layout    = in_array( $s['panel_layout'], array( 'bar', 'left', 'right' ), true ) ? $s['panel_layout'] : 'bar';
		if ( 'bar' === $layout && ! $stats ) {
			// A rating on its own looks lost in a full-width bar: use the compact card.
			$layout = 'left';
		}
		$classes   = array( 'avix-st__media', 'avix-st__media--' . $layout );
		if ( count( $stats ) > 4 ) {
			// A bar with five or more numbers moves under the photo below 1200px instead of covering the faces.
			$classes[] = 'avix-st__media--many';
		}
		if ( empty( $image['url'] ) ) {
			$classes[] = 'avix-st__media--blank';
		}
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-st-media data-st-reveal>
			<div class="avix-st__shot">
				<?php if ( ! empty( $image['url'] ) ) : ?>
					<div class="avix-st__zoom" data-st-zoom><?php echo $this->media_img( $image, (string) $s['media_alt'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in media_img(). ?></div>
				<?php endif; ?>
			</div>
			<?php if ( $stats || $rating ) : ?>
				<?php
				$total         = count( $stats );
				$panel_classes = array( 'avix-st__panel' );
				if ( ! $stats ) {
					// Rating only: the card shrinks to its content.
					$panel_classes[] = 'avix-st__panel--solo';
				} elseif ( 3 === $total && $rating ) {
					// Phones lay three numbers and the rating out as a 2 x 2 grid.
					$panel_classes[] = 'avix-st__panel--quad';
				} elseif ( $total > 4 ) {
					$panel_classes[] = 'avix-st__panel--many';
				}
				if ( $buddy ) {
					$panel_classes[] = 'avix-st__panel--has-pal';
					$side            = in_array( $s['buddy_side'] ?? 'auto', array( 'left', 'right' ), true ) ? $s['buddy_side'] : 'auto';
					if ( 'auto' === $side ) {
						// Away from the people in the photo: over the stars when there are no numbers, else the
						// card's outer end (towards the photo edge), else the right end of the bar.
						$side = ! $stats || 'left' === $layout ? 'left' : 'right';
					}
					if ( 'left' === $side ) {
						$panel_classes[] = 'avix-st__panel--pal-left';
					}
				}
				?>
				<div class="<?php echo esc_attr( implode( ' ', $panel_classes ) ); ?>" data-st-panel>
					<?php
					if ( $buddy ) {
						echo \AvixWidgets\Pixel_Pal::render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pixel_Pal::render() escapes its attributes; the rest is static markup.
							array(
								'class' => 'avix-st__pal',
								'attrs' => array( 'data-st-pal' => '' ),
							)
						);
					}
					?>
					<?php if ( $stats ) : ?>
						<?php
						$stat_classes = array( 'avix-st__stats' );
						if ( 1 === $total ) {
							$stat_classes[] = 'avix-st__stats--one';
						} elseif ( 3 === $total ) {
							$stat_classes[] = 'avix-st__stats--three';
						} elseif ( 5 === $total || 6 === $total ) {
							// Tablets set five or six numbers out as rows of three (3 + 2, 3 + 3) rather than 4 + 1.
							$stat_classes[] = 'avix-st__stats--rows3';
						}
						// --st-n: columns in the wide bar (one row up to six); --st-n-tab: columns on tablets.
						$cols_tab = $total <= 4 ? $total : ( $total <= 6 ? 3 : 4 );
						?>
						<ul class="<?php echo esc_attr( implode( ' ', $stat_classes ) ); ?>" role="list" style="--st-n:<?php echo (int) min( 6, $total ); ?>;--st-n-tab:<?php echo (int) $cols_tab; ?>;">
							<?php foreach ( $stats as $index => $stat ) : ?>
								<?php $this->render_stat( $stat, $index, $separator ); ?>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<?php
					if ( $rating ) {
						$this->render_rating( $s );
					}
					?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	private function render_stat( array $stat, $index, $separator ) {
		$raw      = trim( (string) ( $stat['value'] ?? '' ) );
		$number   = str_replace( array( ',', ' ' ), '', $raw );
		$count    = 'yes' === ( $stat['count'] ?? 'yes' );
		$numeric  = $count && '' !== $number && is_numeric( $number );
		$decimals = $numeric && false !== strpos( $number, '.' ) ? strlen( substr( strrchr( $number, '.' ), 1 ) ) : 0;
		// With a dot for thousands, the decimal mark becomes a comma (1.250,75), as readers of that style expect.
		$decimal  = '.' === $separator ? ',' : '.';
		$display  = $numeric ? number_format( (float) $number, $decimals, $decimal, $separator ) : $raw;
		$prefix   = (string) ( $stat['prefix'] ?? '' );
		$suffix   = (string) ( $stat['suffix'] ?? '' );
		$label    = trim( (string) ( $stat['label'] ?? '' ) );
		$spoken   = trim( $prefix . $display . $suffix );
		?>
		<li class="avix-st__stat" style="--i:<?php echo (int) $index; ?>;" data-st-stat>
			<?php if ( '' !== $spoken ) : ?>
				<?php // Long values (1.250,75k) step down a size so they stay on one line in their column. ?>
				<p class="avix-st__value<?php echo ( function_exists( 'mb_strlen' ) ? mb_strlen( $spoken ) : strlen( $spoken ) ) > 7 ? ' avix-st__value--long' : ''; ?>">
					<span class="avix-st__sr"><?php echo esc_html( $spoken ); ?></span>
					<?php if ( '' !== $prefix ) : ?>
						<span class="avix-st__affix" aria-hidden="true"><?php echo esc_html( $prefix ); ?></span>
					<?php endif; ?>
					<?php if ( $numeric ) : ?>
						<span class="avix-st__num" aria-hidden="true" data-to="<?php echo esc_attr( $number ); ?>" data-decimals="<?php echo (int) $decimals; ?>" data-separator="<?php echo esc_attr( $separator ); ?>" data-decimal="<?php echo esc_attr( $decimal ); ?>"><?php echo esc_html( $display ); ?></span>
					<?php else : ?>
						<span class="avix-st__num" aria-hidden="true"><?php echo esc_html( $raw ); ?></span>
					<?php endif; ?>
					<?php if ( '' !== $suffix ) : ?>
						<span class="avix-st__affix avix-st__affix--suffix" aria-hidden="true"><?php echo esc_html( $suffix ); ?></span>
					<?php endif; ?>
				</p>
			<?php endif; ?>
			<?php if ( '' !== $label ) : ?>
				<p class="avix-st__label"><?php echo esc_html( $label ); ?></p>
			<?php endif; ?>
		</li>
		<?php
	}

	private function render_rating( array $s ) {
		$stars  = max( 0, min( 5, (int) $s['rating_stars'] ) );
		$text   = trim( (string) $s['rating_text'] );
		$tag    = '' === esc_url( (string) ( $s['rating_link']['url'] ?? '' ) ) ? 'p' : 'a';
		/* translators: %d: number of stars out of five. */
		$spoken = sprintf( _n( '%d star out of 5', '%d stars out of 5', $stars, 'avix-widgets' ), $stars );
		$this->add_render_attribute( 'rating', 'class', 'avix-st__rating' );
		if ( 'a' === $tag ) {
			$this->add_link_attributes( 'rating', $s['rating_link'] );
		}
		// A pixel star on a 7×7 grid: the same blocky language as the character.
		$star = '<svg viewBox="0 0 7 7" focusable="false"><rect x="3" y="0" width="1" height="1"/><rect x="2" y="1" width="3" height="1"/><rect x="0" y="2" width="7" height="1"/><rect x="1" y="3" width="5" height="1"/><rect x="2" y="4" width="3" height="1"/><rect x="1" y="5" width="2" height="1"/><rect x="4" y="5" width="2" height="1"/><rect x="1" y="6" width="1" height="1"/><rect x="5" y="6" width="1" height="1"/></svg>';
		?>
		<<?php echo esc_attr( $tag ); ?> <?php $this->print_render_attribute_string( 'rating' ); ?>>
			<?php if ( $stars > 0 ) : ?>
				<span class="avix-st__stars" role="img" aria-label="<?php echo esc_attr( $spoken ); ?>">
					<?php for ( $i = 0; $i < 5; $i++ ) : ?>
						<span class="avix-st__star<?php echo $i >= $stars ? ' is-empty' : ''; ?>" style="--i:<?php echo (int) $i; ?>;"><?php echo $star; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG markup. ?></span>
					<?php endfor; ?>
				</span>
			<?php endif; ?>
			<?php if ( '' !== $text ) : ?>
				<span class="avix-st__rating-text"><?php echo esc_html( $text ); ?></span>
			<?php endif; ?>
			<?php if ( 'a' === $tag ) : ?>
				<svg class="avix-st__rating-arrow" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 17 17 7M7 7h10v10"/></svg>
			<?php endif; ?>
		</<?php echo esc_attr( $tag ); ?>>
		<?php
	}

	/**
	 * Splits the statement into word spans (the scroll fill inks them one by
	 * one), turns [brackets] into orange highlights and {n} into inline
	 * picture capsules. A capsule never starts a line on its own: it stays
	 * glued to the word before it, and punctuation right after it ("{2},")
	 * stays attached too. Everything is escaped here.
	 */
	private function statement_html( $text, array $pills ) {
		$accent = false;
		$lines  = array();
		foreach ( preg_split( '/\R/u', $text ) as $line ) {
			$items = array();
			foreach ( preg_split( '/\s+/u', trim( $line ), -1, PREG_SPLIT_NO_EMPTY ) as $token ) {
				$html       = '';
				$has_pill   = false;
				$pill_first = false;
				// "{2}." whose picture is missing: the full stop must still hug the word before it.
				$lost_pill  = false;
				$after_lost = '';
				foreach ( preg_split( '/(\{\d+\}|\[|\])/u', $token, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY ) as $part ) {
					if ( '[' === $part || ']' === $part ) {
						$accent = '[' === $part;
						continue;
					}
					if ( preg_match( '/^\{(\d+)\}$/', $part, $match ) ) {
						$pill = $this->pill_html( $pills, (int) $match[1] - 1 );
						if ( '' !== $pill ) {
							$pill_first = $pill_first || '' === $html;
							$has_pill   = true;
							$html      .= $pill;
						} elseif ( '' === $html ) {
							$lost_pill = true;
						}
						continue;
					}
					if ( $lost_pill && '' === $html ) {
						$after_lost = $part;
					}
					$html .= '<span class="avix-st__w' . ( $accent ? ' avix-st__w--accent' : '' ) . '" data-st-w>' . esc_html( $part ) . '</span>';
				}
				if ( '' === $html ) {
					continue;
				}
				// Only punctuation hugs the word before ("here {2}." reads "here."); a word ("{9}text") keeps its space.
				if ( $lost_pill && ! $has_pill && $items && preg_match( '/^\p{P}/u', $after_lost ) ) {
					$items[] = array_pop( $items ) . $html;
				} elseif ( $pill_first && $items ) {
					// "together {1}": keep the capsule on the same line as "together".
					$items[] = '<span class="avix-st__glue">' . array_pop( $items ) . ' ' . $html . '</span>';
				} elseif ( $has_pill ) {
					$items[] = '<span class="avix-st__glue">' . $html . '</span>';
				} else {
					$items[] = $html;
				}
			}
			$lines[] = implode( ' ', $items );
		}
		return implode( '<br>', $lines );
	}

	private function pill_html( array $pills, $index ) {
		$row   = $pills[ $index ] ?? null;
		$media = is_array( $row ) ? (array) ( $row['image'] ?? array() ) : array();
		if ( empty( $media['url'] ) ) {
			return '';
		}
		$focus = (string) ( $row['position'] ?? 'center' );
		if ( ! in_array( $focus, array( 'center', 'top', 'bottom', 'left', 'right' ), true ) ) {
			$focus = 'center';
		}
		$zoom  = isset( $row['zoom']['size'] ) && '' !== $row['zoom']['size'] ? (float) $row['zoom']['size'] : 100;
		$zoom  = max( 100, min( 250, $zoom ) ) / 100;
		// Zoom grows from the crop focus, so "left" + 190% keeps the left part of the picture in the capsule.
		$style = 'object-position:' . $focus . ';';
		if ( $zoom > 1 ) {
			$style .= 'transform:scale(' . round( $zoom, 2 ) . ');transform-origin:' . $focus . ';';
		}
		$id    = $this->media_id( $media );
		$attrs = array(
			'class'    => 'avix-st__pill-img',
			'alt'      => '',
			'loading'  => 'lazy',
			'decoding' => 'async',
			'sizes'    => (int) ceil( 120 * $zoom ) . 'px',
			'style'    => $style,
		);
		$img   = '';
		if ( $id && ! preg_match( '/\.gif(\?|$)/i', (string) $media['url'] ) ) {
			$img = wp_get_attachment_image( $id, $zoom > 1.2 ? 'medium_large' : 'medium', false, $attrs );
			// Same as the photo: no width, so WordPress keeps our zoom-aware sizes instead of "auto".
			$img = str_replace( 'sizes="auto, ', 'sizes="', preg_replace( '/\s(?:width|height)="\d*"/', '', $img, 2 ) );
		}
		if ( '' === $img ) {
			$img = sprintf(
				'<img class="avix-st__pill-img" src="%s" alt="" loading="lazy" decoding="async" style="%s">',
				esc_url( $media['url'] ),
				esc_attr( $style )
			);
		}
		$class = isset( $row['_id'] ) ? ' elementor-repeater-item-' . sanitize_html_class( $row['_id'] ) : '';
		// Not aria-hidden: Chrome drops the spaces beside an aria-hidden inline-block, so screen readers heard
		// "togetherto build". The empty alt already keeps the decorative picture out of the accessibility tree.
		return '<span class="avix-st__pill' . esc_attr( $class ) . '" data-st-w>' . $img . '</span>';
	}

	private function media_img( array $media, $alt ) {
		$id  = $this->media_id( $media );
		$alt = trim( $alt );
		if ( '' === $alt && $id ) {
			$alt = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
		}
		if ( '' === $alt ) {
			if ( false !== strpos( (string) $media['url'], 'Untitled-design227' ) ) {
				// Describes the default team photo only; any other picture falls back to its title.
				$alt = __( 'The AvixDigital team working together on a project', 'avix-widgets' );
			} elseif ( $id ) {
				$alt = trim( (string) get_the_title( $id ) );
			}
		}
		if ( $id && ! preg_match( '/\.gif(\?|$)/i', (string) $media['url'] ) ) {
			$img = wp_get_attachment_image(
				$id,
				'full',
				false,
				array(
					'class'    => 'avix-st__img',
					'alt'      => $alt,
					'loading'  => 'lazy',
					'decoding' => 'async',
					// Phones crop the wide photo to 4:5, so the file must be about 2.3x the screen width to stay sharp.
					'sizes'    => '(max-width: 600px) 230vw, (max-width: 1320px) 100vw, 1320px',
				)
			);
			if ( $img ) {
				// WordPress prefixes lazy images that carry a width with sizes="auto, …" (here and again when
				// the_content is filtered). Browsers then size from the cropped layout box and pick a soft file
				// on phones. The photo fills an aspect-ratio box, so width/height are not needed against
				// layout shift: dropping them keeps our exact sizes list.
				$img = preg_replace( '/\s(?:width|height)="\d*"/', '', $img, 2 );
				return str_replace( 'sizes="auto, ', 'sizes="', $img );
			}
		}
		return sprintf(
			'<img class="avix-st__img" src="%s" alt="%s" loading="lazy" decoding="async">',
			esc_url( $media['url'] ),
			esc_attr( $alt )
		);
	}
}
