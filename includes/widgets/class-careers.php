<?php
/**
 * Careers: a big rounded card that invites designers and developers to join.
 * The pixel character stands on the card's top edge holding a flag next to a
 * "We're hiring" sign; perks and optional open roles sit on the right.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Widgets;

use AvixWidgets\Pixel_Pal;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;

defined( 'ABSPATH' ) || exit;

class Careers extends Widget_Base {

	/**
	 * Pixel icons on an 8×8 grid: '#' is an ink pixel, 'o' an orange pixel.
	 * Same drawing style as the Values widget, so the About page reads as one set.
	 */
	const ICONS = array(
		'home'   => array(
			'...##...',
			'..#..#..',
			'.#....#.',
			'########',
			'.#....#.',
			'.#.oo.#.',
			'.#.oo.#.',
			'.######.',
		),
		'laptop' => array(
			'........',
			'.######.',
			'.#....#.',
			'.#.oo.#.',
			'.#....#.',
			'.######.',
			'########',
			'........',
		),
		'growth' => array(
			'......oo',
			'......##',
			'...oo.##',
			'...##.##',
			'oo.##.##',
			'##.##.##',
			'##.##.##',
			'########',
		),
		'heart'  => array(
			'.##..##.',
			'#oo##oo#',
			'#oooooo#',
			'#oooooo#',
			'.#oooo#.',
			'..#oo#..',
			'...##...',
			'........',
		),
		'chat'   => array(
			'.######.',
			'#......#',
			'#.o.o.o#',
			'#......#',
			'.######.',
			'..##....',
			'..#.....',
			'........',
		),
		'rocket' => array(
			'...##...',
			'..####..',
			'..#oo#..',
			'..####..',
			'..####..',
			'.######.',
			'##.oo.##',
			'...o.o..',
		),
		'globe'  => array(
			'..####..',
			'.#.oo.#.',
			'#.o..o.#',
			'#oooooo#',
			'#.o..o.#',
			'#.o..o.#',
			'.#.oo.#.',
			'..####..',
		),
		'clock'  => array(
			'..####..',
			'.#....#.',
			'#...o..#',
			'#...o..#',
			'#...ooo#',
			'#......#',
			'.#....#.',
			'..####..',
		),
		'book'   => array(
			'........',
			'###..###',
			'#..##..#',
			'#.o##o.#',
			'#..##..#',
			'#.o##o.#',
			'########',
			'........',
		),
		'star'   => array(
			'...oo...',
			'...oo...',
			'oooooooo',
			'.oooooo.',
			'..oooo..',
			'.oo..oo.',
			'oo....oo',
			'........',
		),
	);

	public function get_name(): string {
		return 'avix-careers';
	}

	public function get_title(): string {
		return esc_html__( 'Careers', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-call-to-action';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'careers', 'jobs', 'hiring', 'join', 'team', 'perks', 'roles', 'about', 'avix' );
	}

	public function get_style_depends(): array {
		return array( 'avix-careers' );
	}

	public function get_script_depends(): array {
		return array( 'avix-careers' );
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/* ------------------------------------------------------------------ */
	/* Controls                                                            */
	/* ------------------------------------------------------------------ */

	protected function register_controls(): void {
		$this->controls_header();
		$this->controls_perks();
		$this->controls_roles();
		$this->controls_buddy();
		$this->controls_style();
	}

	private function controls_header() {
		$this->start_controls_section( 'section_header', array( 'label' => esc_html__( 'Header & Button', 'avix-widgets' ) ) );

		$this->add_control(
			'eyebrow',
			array(
				'label'   => esc_html__( 'Eyebrow', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Join our journey', 'avix-widgets' ),
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
				'default'     => "Our team is\n[growing]",
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
				'default' => esc_html__( 'We’re always happy to meet designers and developers who care about clear, useful work.', 'avix-widgets' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'button_heading',
			array(
				'label'     => esc_html__( 'Button', 'avix-widgets' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'button_text',
			array(
				'label'       => esc_html__( 'Button text', 'avix-widgets' ),
				'description' => esc_html__( 'Keep it to 2-4 words. Leave empty to hide the button. Hovering it makes the pixel character cheer.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Explore positions', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'button_link',
			array(
				'label'       => esc_html__( 'Button link', 'avix-widgets' ),
				'description' => esc_html__( 'The button only shows when it has a link.', 'avix-widgets' ),
				'type'        => Controls_Manager::URL,
				'default'     => array( 'url' => 'https://avixdigital.com/contact/' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'contact_heading',
			array(
				'label'     => esc_html__( 'Contact line', 'avix-widgets' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'contact_prefix',
			array(
				'label'   => esc_html__( 'Text before the email', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Or send your portfolio to', 'avix-widgets' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'email',
			array(
				'label'       => esc_html__( 'Email', 'avix-widgets' ),
				'description' => esc_html__( 'Visitors can click it to write to you. Leave empty to hide the line.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'info@avixdigital.com',
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_perks() {
		$this->start_controls_section( 'section_perks', array( 'label' => esc_html__( 'Perks', 'avix-widgets' ) ) );

		$icons = array(
			'home'   => esc_html__( 'Pixel: home', 'avix-widgets' ),
			'laptop' => esc_html__( 'Pixel: laptop', 'avix-widgets' ),
			'growth' => esc_html__( 'Pixel: growth', 'avix-widgets' ),
			'heart'  => esc_html__( 'Pixel: heart', 'avix-widgets' ),
			'chat'   => esc_html__( 'Pixel: chat', 'avix-widgets' ),
			'rocket' => esc_html__( 'Pixel: rocket', 'avix-widgets' ),
			'globe'  => esc_html__( 'Pixel: globe', 'avix-widgets' ),
			'clock'  => esc_html__( 'Pixel: clock', 'avix-widgets' ),
			'book'   => esc_html__( 'Pixel: book', 'avix-widgets' ),
			'star'   => esc_html__( 'Pixel: star', 'avix-widgets' ),
			'custom' => esc_html__( 'Custom icon', 'avix-widgets' ),
			'none'   => esc_html__( 'None', 'avix-widgets' ),
		);

		$repeater = new Repeater();
		$repeater->add_control(
			'icon',
			array(
				'label'       => esc_html__( 'Icon', 'avix-widgets' ),
				'description' => esc_html__( 'Pixel icons build themselves pixel by pixel when the card comes into view.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'star',
				'options'     => $icons,
			)
		);
		$repeater->add_control(
			'icon_custom',
			array(
				'label'     => esc_html__( 'Custom icon', 'avix-widgets' ),
				'type'      => Controls_Manager::ICONS,
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
				'rows'    => 4,
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'perks',
			array(
				'label'       => esc_html__( 'Perks', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => array(
					array(
						'icon'  => 'home',
						'title' => esc_html__( 'Flexible work culture', 'avix-widgets' ),
						'text'  => esc_html__( 'We believe great work happens when you have the freedom to balance your life. Whether you prefer a remote setup or a hybrid environment, we provide the tools and trust you need to thrive on your own terms.', 'avix-widgets' ),
					),
					array(
						'icon'  => 'growth',
						'title' => esc_html__( 'Accelerated career growth', 'avix-widgets' ),
						'text'  => esc_html__( 'Your professional evolution is our priority. At Avix Digital, we offer continuous learning opportunities, mentorship programs, and a clear path for advancement to ensure you’re always leveling up your skillset.', 'avix-widgets' ),
					),
					array(
						'icon'  => 'heart',
						'title' => esc_html__( 'Comprehensive wellness benefits', 'avix-widgets' ),
						'text'  => esc_html__( 'We invest in the person, not just the employee. From mental health support and fitness stipends to comprehensive healthcare, our benefits package is designed to keep you feeling energized and supported.', 'avix-widgets' ),
					),
				),
			)
		);

		$this->end_controls_section();
	}

	private function controls_roles() {
		$this->start_controls_section( 'section_roles', array( 'label' => esc_html__( 'Open Roles', 'avix-widgets' ) ) );

		$this->add_control(
			'roles_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Add a role whenever you are hiring. With no roles, visitors see only the perks and the button.', 'avix-widgets' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->add_control(
			'roles_title',
			array(
				'label'   => esc_html__( 'List heading', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Open roles', 'avix-widgets' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater = new Repeater();
		$repeater->add_control(
			'role',
			array(
				'label'       => esc_html__( 'Role', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);
		$repeater->add_control(
			'type',
			array(
				'label'       => esc_html__( 'Details', 'avix-widgets' ),
				'description' => esc_html__( 'For example: Remote · Full-time', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);
		$repeater->add_control(
			'link',
			array(
				'label'   => esc_html__( 'Link (optional)', 'avix-widgets' ),
				'type'    => Controls_Manager::URL,
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'roles',
			array(
				'label'       => esc_html__( 'Roles', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ role }}}',
				'default'     => array(),
			)
		);

		$this->end_controls_section();
	}

	private function controls_buddy() {
		$this->start_controls_section( 'section_buddy', array( 'label' => esc_html__( 'Pixel Character & Sign', 'avix-widgets' ) ) );

		$this->add_control(
			'show_pal',
			array(
				'label'       => esc_html__( 'Show pixel character', 'avix-widgets' ),
				'description' => esc_html__( 'It stands on the card’s top edge, says hi when the card comes into view, waves its flag now and then, jumps when the sign is hovered and cheers while the button is hovered.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'show_flag',
			array(
				'label'     => esc_html__( 'Hold a flag', 'avix-widgets' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'show_pal' => 'yes' ),
			)
		);

		$this->add_control(
			'wave_every',
			array(
				'label'       => esc_html__( 'Wave every (seconds)', 'avix-widgets' ),
				'description' => esc_html__( 'Roughly; it only waves while the card is on screen.', 'avix-widgets' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 3,
				'max'         => 60,
				'default'     => 8,
				'condition'   => array( 'show_pal' => 'yes' ),
			)
		);

		$this->add_control(
			'show_sign',
			array(
				'label'     => esc_html__( 'Show sign', 'avix-widgets' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'sign_text',
			array(
				'label'     => esc_html__( 'Sign text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'We’re hiring', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_sign' => 'yes' ),
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
				'label'   => esc_html__( 'Card style', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'light',
				'options' => array(
					'light' => esc_html__( 'Light (warm paper)', 'avix-widgets' ),
					'dark'  => esc_html__( 'Dark', 'avix-widgets' ),
				),
			)
		);

		$this->add_control(
			'tone',
			array(
				'label'       => esc_html__( 'Section tone', 'avix-widgets' ),
				'description' => esc_html__( 'The character and its sign stand on the section, not on the card. Dark gives the section a near-black background and turns the sign and flag pole white so they stay visible. Pick Dark whenever you set a dark section background.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'light',
				'options'     => array(
					'light' => esc_html__( 'Light', 'avix-widgets' ),
					'dark'  => esc_html__( 'Dark', 'avix-widgets' ),
				),
			)
		);

		$colors = array(
			'bg'          => array( esc_html__( 'Section background', 'avix-widgets' ), '--cr-bg' ),
			'card'        => array( esc_html__( 'Card', 'avix-widgets' ), '--cr-card' ),
			'card_line'   => array( esc_html__( 'Card border', 'avix-widgets' ), '--cr-card-line' ),
			'ink'         => array( esc_html__( 'Headings', 'avix-widgets' ), '--cr-ink' ),
			'muted'       => array( esc_html__( 'Text', 'avix-widgets' ), '--cr-muted' ),
			'accent'      => array( esc_html__( 'Accent', 'avix-widgets' ), '--cr-accent' ),
			'accent_text' => array( esc_html__( 'Highlighted words and email hover (defaults to a shade of Accent)', 'avix-widgets' ), '--cr-accent-text' ),
			'btn_ink'     => array( esc_html__( 'Button text', 'avix-widgets' ), '--cr-btn-ink' ),
			'btn_hover'   => array( esc_html__( 'Button hover fill', 'avix-widgets' ), '--cr-btn-hover' ),
			'line'        => array( esc_html__( 'Lines', 'avix-widgets' ), '--cr-line' ),
			'tile'        => array( esc_html__( 'Icon tiles and role rows', 'avix-widgets' ), '--cr-tile' ),
			'tile_line'   => array( esc_html__( 'Tile and role borders', 'avix-widgets' ), '--cr-tile-line' ),
			'icon_ink'    => array( esc_html__( 'Pixel icon ink', 'avix-widgets' ), '--cr-px-ink' ),
			'sign_bg'     => array( esc_html__( 'Sign and flag pole', 'avix-widgets' ), '--cr-sign-bg' ),
			'sign_ink'    => array( esc_html__( 'Sign text', 'avix-widgets' ), '--cr-sign-ink' ),
			'pal_color'   => array( esc_html__( 'Pixel character (defaults to Accent)', 'avix-widgets' ), '--cr-pal' ),
		);
		foreach ( $colors as $key => $color ) {
			$this->add_control(
				$key,
				array(
					'label'     => $color[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-cr' => $color[1] . ': {{VALUE}};' ),
				)
			);
		}

		$this->add_responsive_control(
			'padding',
			array(
				'label'              => esc_html__( 'Padding', 'avix-widgets' ),
				'description'        => esc_html__( 'Space above and below the section. The character and its sign make their own room above the card, so this space grows with them automatically.', 'avix-widgets' ),
				'type'               => Controls_Manager::DIMENSIONS,
				'size_units'         => array( 'px', 'vh' ),
				'allowed_dimensions' => 'vertical',
				'separator'          => 'before',
				'selectors'          => array( '{{WRAPPER}} .avix-cr' => 'padding-top: {{TOP}}{{UNIT}}; padding-bottom: {{BOTTOM}}{{UNIT}};' ),
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
				'selectors'  => array( '{{WRAPPER}} .avix-cr' => '--cr-max: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'card_radius',
			array(
				'label'      => esc_html__( 'Card corner radius', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 60,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .avix-cr' => '--cr-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'pal_size',
			array(
				'label'       => esc_html__( 'Character size', 'avix-widgets' ),
				'description' => esc_html__( 'Width of the pixel character. The sign and the space above the card grow with it. It moves in steps of 10px so every pixel stays sharp, and narrow phones cap it so it always fits.', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array(
					'px' => array(
						'min'  => 30,
						'max'  => 90,
						'step' => 10,
					),
				),
				'selectors'   => array( '{{WRAPPER}} .avix-cr' => '--cr-pal-w: {{SIZE}}{{UNIT}};' ),
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
			'title_typography'      => array( esc_html__( 'Title', 'avix-widgets' ), '{{WRAPPER}} .avix-cr .avix-cr__title' ),
			'text_typography'       => array( esc_html__( 'Text', 'avix-widgets' ), '{{WRAPPER}} .avix-cr .avix-cr__text' ),
			'button_typography'     => array( esc_html__( 'Button', 'avix-widgets' ), '{{WRAPPER}} .avix-cr .avix-cr__btn' ),
			'perk_title_typography' => array( esc_html__( 'Perk titles', 'avix-widgets' ), '{{WRAPPER}} .avix-cr .avix-cr__perk-title' ),
			'perk_text_typography'  => array( esc_html__( 'Perk text', 'avix-widgets' ), '{{WRAPPER}} .avix-cr .avix-cr__perk-text' ),
			'role_typography'       => array( esc_html__( 'Role names', 'avix-widgets' ), '{{WRAPPER}} .avix-cr .avix-cr__role-name' ),
		);
		foreach ( $type as $name => $group ) {
			$this->add_group_control(
				Group_Control_Typography::get_type(),
				array(
					'name'     => $name,
					'label'    => $group[0],
					'selector' => $group[1],
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
		$perks = array_values(
			array_filter(
				(array) ( $s['perks'] ?? array() ),
				static function ( $row ) {
					return '' !== trim( (string) ( $row['title'] ?? '' ) ) || '' !== trim( (string) ( $row['text'] ?? '' ) );
				}
			)
		);
		$roles = array_values(
			array_filter(
				(array) ( $s['roles'] ?? array() ),
				static function ( $row ) {
					return '' !== trim( (string) ( $row['role'] ?? '' ) );
				}
			)
		);

		$title    = trim( (string) ( $s['title'] ?? '' ) );
		$tag      = Utils::validate_html_tag( $s['title_tag'] ?? 'h2' );
		$title_id = 'avix-cr-title-' . $this->get_id();
		$pal      = 'yes' === ( $s['show_pal'] ?? '' );
		$sign     = 'yes' === ( $s['show_sign'] ?? '' ) && '' !== trim( (string) ( $s['sign_text'] ?? '' ) );
		$email    = sanitize_email( (string) ( $s['email'] ?? '' ) );
		$button   = trim( (string) ( $s['button_text'] ?? '' ) );
		$link     = (array) ( $s['button_link'] ?? array() );
		$edit     = \Elementor\Plugin::$instance->editor->is_edit_mode();
		// A button that goes nowhere is not printed: the editor gets a hint instead.
		$no_link  = '' !== $button && '' === self::safe_url( $link );
		if ( $no_link ) {
			$button = '';
		}
		$eyebrow  = trim( (string) ( $s['eyebrow'] ?? '' ) );
		$text     = trim( (string) ( $s['text'] ?? '' ) );
		$aside    = ! empty( $perks ) || ! empty( $roles );
		$head     = '' !== $eyebrow || '' !== $title || '' !== $text;
		$main     = $head || '' !== $button || '' !== $email || ( $no_link && $edit );

		// Nothing to read: no empty card (the character alone is not content).
		if ( '' === $eyebrow && '' === $title && '' === $text && '' === $button && '' === $email && ! $aside ) {
			if ( $edit ) {
				echo '<div class="elementor-alert elementor-alert-info">' . esc_html__( 'Careers: add a title, a perk or a role.', 'avix-widgets' ) . '</div>';
			}
			return;
		}
		// Perk titles and the roles heading sit one level below the section title.
		$sub_tags = array(
			'h1' => 'h2',
			'h2' => 'h3',
			'h3' => 'h4',
		);
		$sub_tag  = '' !== $title && isset( $sub_tags[ $tag ] ) ? $sub_tags[ $tag ] : 'p';
		$roles_id = 'avix-cr-roles-' . $this->get_id();
		// The sign is decorative markup, so screen readers get its message as text.
		$sign_sr = $sign ? trim( (string) $s['sign_text'] ) : '';
		if ( '' !== $sign_sr && ! preg_match( '/[.!?…]$/u', $sign_sr ) ) {
			$sign_sr .= '.';
		}

		$classes = array( 'avix-cr', 'avix-cr--' . ( 'dark' === ( $s['theme'] ?? '' ) ? 'dark' : 'light' ) );
		if ( 'dark' === ( $s['tone'] ?? '' ) ) {
			$classes[] = 'avix-cr--on-dark';
		}
		// One column when either side is empty.
		if ( ! $aside || ! $main ) {
			$classes[] = 'avix-cr--solo';
		}
		if ( $pal || $sign ) {
			$classes[] = 'has-scene';
		}

		// An empty or invalid field falls back to the default 8s, not the 3s minimum.
		$every  = (int) ( $s['wave_every'] ?? 0 );
		$config = array(
			'pal'   => $pal,
			'every' => $every > 0 ? max( 3, min( 60, $every ) ) : 8,
		);

		$this->add_render_attribute(
			'root',
			array(
				'class'        => $classes,
				'data-avix-cr' => wp_json_encode( $config ),
			)
		);
		if ( '' !== $title ) {
			$this->add_render_attribute( 'root', 'aria-labelledby', $title_id );
		} else {
			$this->add_render_attribute( 'root', 'aria-label', '' !== $eyebrow ? $eyebrow : esc_html__( 'Careers', 'avix-widgets' ) );
		}
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="avix-cr__frame">
				<?php if ( $pal || $sign ) : ?>
					<div class="avix-cr__scene" data-cr-scene aria-hidden="true">
						<?php
						if ( $pal ) {
							echo Pixel_Pal::render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pixel_Pal::render() escapes its attributes; the rest is static markup.
								array(
									'class' => 'avix-cr__pal',
									'hi'    => true,
									'prop'  => 'yes' === ( $s['show_flag'] ?? '' ) ? 'flag' : '',
									'attrs' => array( 'data-cr-pal' => '' ),
								)
							);
						}
						?>
						<?php if ( $sign ) : ?>
							<span class="avix-cr__sign">
								<span class="avix-cr__board">
									<span class="avix-cr__blink"></span>
									<span class="avix-cr__sign-text"><?php echo esc_html( $s['sign_text'] ); ?></span>
								</span>
								<span class="avix-cr__posts"></span>
							</span>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<div class="avix-cr__card">
					<span class="avix-cr__deco" aria-hidden="true"></span>

					<div class="avix-cr__grid">
						<?php if ( $sign ) : ?>
							<p class="avix-cr__sr"><?php echo esc_html( $sign_sr ); ?></p>
						<?php endif; ?>
						<?php if ( $main ) : ?>
							<div class="avix-cr__main">
								<?php if ( $head ) : ?>
									<header class="avix-cr__head">
										<?php if ( '' !== $eyebrow ) : ?>
											<p class="avix-cr__eyebrow avix-cr__rise" style="--i:0"><?php echo esc_html( $eyebrow ); ?></p>
										<?php endif; ?>
										<?php if ( '' !== $title ) : ?>
											<<?php echo esc_attr( $tag ); ?> class="avix-cr__title avix-cr__rise" id="<?php echo esc_attr( $title_id ); ?>" style="--i:1"><?php echo $this->accent_html( $title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in accent_html(). ?></<?php echo esc_attr( $tag ); ?>>
										<?php endif; ?>
										<?php if ( '' !== $text ) : ?>
											<p class="avix-cr__text avix-cr__rise" style="--i:2"><?php echo esc_html( $text ); ?></p>
										<?php endif; ?>
									</header>
								<?php endif; ?>

								<?php if ( $no_link && $edit ) : ?>
									<div class="elementor-alert elementor-alert-info"><?php esc_html_e( 'Careers: add a link to show the button.', 'avix-widgets' ); ?></div>
								<?php endif; ?>

								<?php if ( '' !== $button || '' !== $email ) : ?>
									<div class="avix-cr__actions avix-cr__rise" style="--i:3">
										<?php
										if ( '' !== $button ) {
											$this->add_render_attribute(
												'button',
												array(
													'class'       => 'avix-cr__btn',
													'data-cr-cta' => '',
												)
											);
											$this->add_link_attributes( 'button', $link );
											?>
											<a <?php $this->print_render_attribute_string( 'button' ); ?>>
												<span class="avix-cr__btn-label"><?php echo esc_html( $button ); ?></span>
												<span class="avix-cr__btn-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M7 17 17 7M7 7h10v10"/></svg></span>
											</a>
											<?php
										}
										if ( '' !== $email ) :
											?>
											<p class="avix-cr__contact">
												<?php if ( '' !== trim( (string) $s['contact_prefix'] ) ) : ?>
													<span class="avix-cr__contact-pre"><?php echo esc_html( $s['contact_prefix'] ); ?></span>
												<?php endif; ?>
												<a class="avix-cr__mail" href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a>
											</p>
										<?php endif; ?>
									</div>
								<?php endif; ?>
							</div>
						<?php endif; ?>

						<?php if ( $aside ) : ?>
							<div class="avix-cr__aside">
								<?php if ( ! empty( $perks ) ) : ?>
									<ul class="avix-cr__perks" role="list">
										<?php foreach ( $perks as $i => $perk ) : ?>
											<?php $this->render_perk( $perk, $i, $sub_tag ); ?>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>

								<?php if ( ! empty( $roles ) ) : ?>
									<div class="avix-cr__roles avix-cr__rise" style="--i:<?php echo (int) count( $perks ) + 1; ?>">
										<?php if ( '' !== trim( (string) $s['roles_title'] ) ) : ?>
											<<?php echo esc_attr( $sub_tag ); ?> class="avix-cr__roles-title" id="<?php echo esc_attr( $roles_id ); ?>">
												<span><?php echo esc_html( $s['roles_title'] ); ?></span>
												<span class="avix-cr__count"><?php echo (int) count( $roles ); ?></span>
											</<?php echo esc_attr( $sub_tag ); ?>>
										<?php endif; ?>
										<ul class="avix-cr__role-list" role="list"<?php echo '' !== trim( (string) $s['roles_title'] ) ? ' aria-labelledby="' . esc_attr( $roles_id ) . '"' : ''; ?>>
											<?php foreach ( $roles as $i => $role ) : ?>
												<?php $this->render_role( $role, $i ); ?>
											<?php endforeach; ?>
										</ul>
									</div>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</section>
		<?php
	}

	private function render_perk( array $perk, $index, $tag = 'h3' ) {
		$title = trim( (string) ( $perk['title'] ?? '' ) );
		$text  = trim( (string) ( $perk['text'] ?? '' ) );
		$class = 'avix-cr__perk avix-cr__rise elementor-repeater-item-' . sanitize_html_class( (string) ( $perk['_id'] ?? '' ) );
		if ( ! self::has_icon( $perk ) ) {
			$class .= ' is-plain';
		}
		?>
		<li class="<?php echo esc_attr( $class ); ?>" style="--i:<?php echo (int) $index + 1; ?>" data-cr-look>
			<?php $this->render_icon( $perk ); ?>
			<div class="avix-cr__perk-body">
				<?php if ( '' !== $title ) : ?>
					<<?php echo esc_attr( $tag ); ?> class="avix-cr__perk-title"><?php echo esc_html( $title ); ?></<?php echo esc_attr( $tag ); ?>>
				<?php endif; ?>
				<?php if ( '' !== $text ) : ?>
					<p class="avix-cr__perk-text"><?php echo esc_html( $text ); ?></p>
				<?php endif; ?>
			</div>
		</li>
		<?php
	}

	/**
	 * Whether render_icon() prints a tile for this perk.
	 *
	 * @param array $perk Repeater row.
	 */
	private static function has_icon( array $perk ) {
		$icon = (string) ( $perk['icon'] ?? 'none' );
		return ( 'custom' === $icon && ! empty( $perk['icon_custom']['value'] ) ) || isset( self::ICONS[ $icon ] );
	}

	private function render_icon( array $perk ) {
		$icon = (string) ( $perk['icon'] ?? 'none' );
		if ( 'custom' === $icon && ! empty( $perk['icon_custom']['value'] ) ) {
			echo '<span class="avix-cr__tile avix-cr__tile--custom" aria-hidden="true">';
			Icons_Manager::render_icon( $perk['icon_custom'], array( 'aria-hidden' => 'true' ) );
			echo '</span>';
			return;
		}
		if ( ! isset( self::ICONS[ $icon ] ) ) {
			return;
		}
		echo '<span class="avix-cr__tile" aria-hidden="true">' . self::pixel_svg( $icon ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup built from self::ICONS.
	}

	private function render_role( array $role, $index ) {
		$name = trim( (string) ( $role['role'] ?? '' ) );
		$type = trim( (string) ( $role['type'] ?? '' ) );
		$link = (array) ( $role['link'] ?? array() );
		$tag  = '' === self::safe_url( $link ) ? 'div' : 'a';
		$key  = 'role-' . $index;

		$this->add_render_attribute(
			$key,
			array(
				'class'        => 'avix-cr__role' . ( 'a' === $tag ? ' is-link' : '' ),
				'data-cr-look' => '',
			)
		);
		if ( 'a' === $tag ) {
			$this->add_link_attributes( $key, $link );
		}
		?>
		<li class="avix-cr__role-item elementor-repeater-item-<?php echo esc_attr( sanitize_html_class( (string) ( $role['_id'] ?? '' ) ) ); ?>">
			<<?php echo esc_attr( $tag ); ?> <?php $this->print_render_attribute_string( $key ); ?>>
				<span class="avix-cr__role-main">
					<span class="avix-cr__role-name"><?php echo esc_html( $name ); ?></span>
					<?php if ( '' !== $type ) : ?>
						<span class="avix-cr__role-type"><?php echo esc_html( $type ); ?></span>
					<?php endif; ?>
				</span>
				<?php if ( 'a' === $tag ) : ?>
					<span class="avix-cr__role-go" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
				<?php endif; ?>
			</<?php echo esc_attr( $tag ); ?>>
		</li>
		<?php
	}

	/**
	 * The link URL as it will be printed, or '' when it is empty or uses a
	 * protocol esc_url() drops (then the item renders without a link instead
	 * of an href="" that reloads the page). Same protocols as add_link_attributes().
	 *
	 * @param array $link URL control value.
	 */
	private static function safe_url( array $link ) {
		$url = trim( (string) ( $link['url'] ?? '' ) );
		if ( '' === $url ) {
			return '';
		}
		return esc_url( $url, array_merge( wp_allowed_protocols(), array( 'skype', 'viber' ) ) );
	}

	/**
	 * Builds an 8×8 pixel icon. Each pixel carries its build order (--d) so CSS
	 * can assemble the icon row by row from the bottom when the card is reached.
	 *
	 * @param string $name Icon key.
	 */
	private static function pixel_svg( $name ) {
		$rects = '';
		foreach ( self::ICONS[ $name ] as $y => $row ) {
			$len = strlen( $row );
			for ( $x = 0; $x < $len; $x++ ) {
				$char = $row[ $x ];
				if ( '#' !== $char && 'o' !== $char ) {
					continue;
				}
				$rects .= sprintf(
					'<rect class="%1$s" x="%2$d" y="%3$d" width="1" height="1" style="--d:%4$d"/>',
					'o' === $char ? 'a' : 'i',
					$x,
					$y,
					( 7 - $y ) * 2 + ( ( $x * 3 + $y ) % 3 )
				);
			}
		}
		return '<svg class="avix-cr__px" viewBox="0 0 8 8" focusable="false" aria-hidden="true">' . $rects . '</svg>';
	}

	/**
	 * Escapes the text, turns [words] into highlighted spans and line breaks into <br>.
	 *
	 * @param string $text Raw title.
	 */
	private function accent_html( $text ) {
		$lines = preg_split( '/\r\n|\r|\n/', (string) $text );
		$lines = array_map(
			static function ( $line ) {
				return preg_replace( '/\[([^\[\]]+)\]/u', '<span class="avix-cr__accent">$1</span>', esc_html( trim( $line ) ) );
			},
			array_filter(
				(array) $lines,
				static function ( $line ) {
					return '' !== trim( $line );
				}
			)
		);
		return implode( ' <br>', $lines );
	}
}
