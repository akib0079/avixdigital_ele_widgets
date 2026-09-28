<?php
/**
 * Hero Banner: the homepage welcome. Interactive pixel-matrix background,
 * a headline with an orange highlight and inline icon, and the Avix pixel
 * character sitting on the headline, chilling and saying hi to visitors.
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

class Hero extends Widget_Base {

	use Media;

	const BG_IMAGE = 'https://avixdigital.com/wp-content/uploads/2025/11/techai-red-bg.webp';

	/** The Avix mark (60×60), used on the welcome badge. */
	const MARK = 'M0.000609729 33.4786V24.609H15.5435C15.9776 24.609 16.3249 24.522 16.6724 24.2613L0.608473 8.17411L1.91078 6.78291L8.16259 0.522042L24.3131 16.6963C24.4868 16.3485 24.6603 15.9135 24.6603 15.5658V15.4788V0H26.5705H33.4301H35.3403V15.4785V15.5655C35.3403 15.9132 35.5141 16.3479 35.6875 16.696L51.838 0.521739L58.0898 6.78261L59.3921 8.17381L43.3282 24.261C43.6754 24.5217 44.023 24.6087 44.4571 24.6087H60V33.4783V35.3913H44.4571C41.0705 35.3913 38.0315 34.0871 35.6872 31.9131L34.819 32.7826L32.7351 34.8695L31.8669 35.739C34.0377 37.9999 35.34 41.1304 35.34 44.5215V60H33.4298H24.66V44.5221C24.66 44.0874 24.4862 43.6527 24.3128 43.3046L8.16228 59.4789L6.85967 58.0877L0.607873 51.8268L16.6718 35.7396C16.3246 35.5656 15.977 35.3919 15.5429 35.3919H0V33.4786H0.000609729Z';

	/**
	 * Line icons (24×24 stroked, Feather Icons, MIT) for the headline icon and the trust dock.
	 */
	const ICONS = array(
		'layout'  => array( 'Layout', '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>' ),
		'pen'     => array( 'Pen tool', '<path d="m12 19 7-7 3 3-7 7-3-3z"/><path d="m18 13-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/><path d="m2 2 7.59 7.59"/><circle cx="11" cy="11" r="2"/>' ),
		'code'    => array( 'Code', '<path d="m16 18 6-6-6-6M8 6l-6 6 6 6"/>' ),
		'monitor' => array( 'Monitor', '<rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/>' ),
		'cursor'  => array( 'Cursor', '<path d="m3 3 7.07 16.97 2.51-7.39 7.39-2.51L3 3zM13 13l6 6"/>' ),
		'zap'     => array( 'Lightning', '<path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/>' ),
		'bag'     => array( 'Shopping bag', '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4zM3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>' ),
		'shield'  => array( 'Shield check', '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>' ),
		'trend'   => array( 'Trending up', '<path d="m23 6-9.5 9.5-5-5L1 18"/><path d="M17 6h6v6"/>' ),
		'star'    => array( 'Star', '<path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>' ),
		'award'   => array( 'Award', '<circle cx="12" cy="8" r="7"/><path d="M8.21 13.89 7 23l5-3 5 3-1.21-9.12"/>' ),
		'check'   => array( 'Check circle', '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4 12 14.01l-3-3"/>' ),
		'users'   => array( 'Users', '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>' ),
		'globe'   => array( 'Globe', '<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>' ),
		'clock'   => array( 'Clock', '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>' ),
		'heart'   => array( 'Heart', '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78z"/>' ),
		'smile'   => array( 'Smile', '<circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2M9 9h.01M15 9h.01"/>' ),
	);

	public function get_name(): string {
		return 'avix-hero';
	}

	public function get_title(): string {
		return esc_html__( 'Hero Banner', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-header';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'hero', 'banner', 'header', 'welcome', 'headline', 'avatar', 'pixel', 'avix' );
	}

	public function get_style_depends(): array {
		return array( 'avix-hero' );
	}

	public function get_script_depends(): array {
		return array( 'avix-hero' );
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/* ------------------------------------------------------------------ */
	/* Controls                                                            */
	/* ------------------------------------------------------------------ */

	protected function register_controls(): void {
		$this->controls_badge();
		$this->controls_headline();
		$this->controls_buttons();
		$this->controls_buddy();
		$this->controls_tags();
		$this->controls_dock();
		$this->controls_background();
		$this->controls_style();
	}

	/**
	 * Options for the built-in line icons, plus custom / none where needed.
	 */
	private function icon_options( $custom = true, $none = false ) {
		$options = array();
		foreach ( self::ICONS as $key => $icon ) {
			$options[ $key ] = $icon[0];
		}
		if ( $custom ) {
			$options['custom'] = esc_html__( 'Custom icon…', 'avix-widgets' );
		}
		if ( $none ) {
			$options['none'] = esc_html__( 'None', 'avix-widgets' );
		}
		return $options;
	}

	private function controls_badge() {
		$this->start_controls_section( 'section_badge', array( 'label' => esc_html__( 'Welcome Badge', 'avix-widgets' ) ) );

		$this->add_control(
			'show_badge',
			array(
				'label'   => esc_html__( 'Show', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'badge_text',
			array(
				'label'       => esc_html__( 'Text', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Welcome to AvixDigital', 'avix-widgets' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_badge' => 'yes' ),
			)
		);

		$this->add_control(
			'badge_icon_type',
			array(
				'label'     => esc_html__( 'Icon', 'avix-widgets' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'mark',
				'options'   => array(
					'mark'   => esc_html__( 'Avix mark', 'avix-widgets' ),
					'dot'    => esc_html__( 'Pulsing dot (e.g. “Available for projects”)', 'avix-widgets' ),
					'custom' => esc_html__( 'Custom icon…', 'avix-widgets' ),
					'none'   => esc_html__( 'None', 'avix-widgets' ),
				),
				'condition' => array( 'show_badge' => 'yes' ),
			)
		);

		$this->add_control(
			'badge_icon',
			array(
				'label'     => esc_html__( 'Custom icon', 'avix-widgets' ),
				'type'      => Controls_Manager::ICONS,
				'condition' => array(
					'show_badge'      => 'yes',
					'badge_icon_type' => 'custom',
				),
			)
		);

		$this->add_control(
			'badge_link',
			array(
				'label'       => esc_html__( 'Link (optional)', 'avix-widgets' ),
				'description' => esc_html__( 'Turns the badge into a link, e.g. to an announcement.', 'avix-widgets' ),
				'type'        => Controls_Manager::URL,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_badge' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_headline() {
		$this->start_controls_section( 'section_headline', array( 'label' => esc_html__( 'Headline', 'avix-widgets' ) ) );

		$this->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Headline', 'avix-widgets' ),
				'description' => esc_html__( 'Wrap words in [square brackets] for the orange highlight. Type {icon} for the round icon. Press Enter for a new line.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 3,
				'default'     => "Leading [Creative]\nWeb Design {icon} Agency.",
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'   => esc_html__( 'Headline tag', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h1',
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
			'title_icon',
			array(
				'label'       => esc_html__( 'Round icon ({icon})', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'layout',
				'options'     => $this->icon_options(),
			)
		);

		$this->add_control(
			'title_icon_custom',
			array(
				'label'     => esc_html__( 'Custom icon', 'avix-widgets' ),
				'type'      => Controls_Manager::ICONS,
				'condition' => array( 'title_icon' => 'custom' ),
			)
		);

		$this->add_control(
			'subtitle',
			array(
				'label'     => esc_html__( 'Supporting text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXTAREA,
				'rows'      => 3,
				'default'   => 'We specialize in crafting innovative web designs, sustainable digital solutions, and exceptional user experiences to bring your brand to life.',
				'dynamic'   => array( 'active' => true ),
				'separator' => 'before',
			)
		);

		$this->end_controls_section();
	}

	private function controls_buttons() {
		$this->start_controls_section( 'section_buttons', array( 'label' => esc_html__( 'Buttons', 'avix-widgets' ) ) );

		$this->add_control(
			'button_text',
			array(
				'label'   => esc_html__( 'Button text', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Book a free audit', 'avix-widgets' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'button_link',
			array(
				'label'   => esc_html__( 'Button link', 'avix-widgets' ),
				'type'    => Controls_Manager::URL,
				'dynamic' => array( 'active' => true ),
				'default' => array( 'url' => 'https://avixdigital.com/contact/' ),
			)
		);

		$this->add_control(
			'show_button_2',
			array(
				'label'       => esc_html__( 'Second link', 'avix-widgets' ),
				'description' => esc_html__( 'A quieter option for visitors who aren’t ready to book yet.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'button_2_text',
			array(
				'label'     => esc_html__( 'Text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'See our work', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_button_2' => 'yes' ),
			)
		);

		$this->add_control(
			'button_2_link',
			array(
				'label'       => esc_html__( 'Link', 'avix-widgets' ),
				'description' => esc_html__( 'A page, or #id of a section on this page (set the ID under the section’s Advanced tab).', 'avix-widgets' ),
				'type'        => Controls_Manager::URL,
				'dynamic'     => array( 'active' => true ),
				'default'     => array( 'url' => '#work' ),
				'condition'   => array( 'show_button_2' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_buddy() {
		$this->start_controls_section( 'section_buddy', array( 'label' => esc_html__( 'Pixel Avatar', 'avix-widgets' ) ) );

		$this->add_control(
			'show_buddy',
			array(
				'label'       => esc_html__( 'Avatar on the headline', 'avix-widgets' ),
				'description' => esc_html__( 'The Avix pixel character drops onto the headline, waves hello, then chills: legs swinging, watching the cursor. Hover it (or the button) and it says hi again.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'buddy_word',
			array(
				'label'       => esc_html__( 'Sits on the word', 'avix-widgets' ),
				'description' => esc_html__( 'A word from the first line keeps it on top at every screen size. Falls back to the first word.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Leading',
				'condition'   => array( 'show_buddy' => 'yes' ),
			)
		);

		$this->add_control(
			'buddy_x',
			array(
				'label'      => esc_html__( 'Position on the word', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array( '%' => array( 'min' => 0, 'max' => 100 ) ),
				'default'    => array( 'unit' => '%', 'size' => 30 ),
				'selectors'  => array( '{{WRAPPER}} .avix-hero' => '--hero-buddy-x: {{SIZE}}%;' ),
				'condition'  => array( 'show_buddy' => 'yes' ),
			)
		);

		$this->add_control(
			'buddy_nudge',
			array(
				'label'       => esc_html__( 'Height adjustment', 'avix-widgets' ),
				'description' => esc_html__( 'Fine-tune if it floats or sinks with another font.', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'em' ),
				'range'       => array( 'em' => array( 'min' => -0.3, 'max' => 0.3, 'step' => 0.01 ) ),
				'default'     => array( 'unit' => 'em', 'size' => 0 ),
				'selectors'   => array( '{{WRAPPER}} .avix-hero' => '--hero-buddy-nudge: {{SIZE}}em;' ),
				'condition'   => array( 'show_buddy' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'buddy_size',
			array(
				'label'       => esc_html__( 'Size', 'avix-widgets' ),
				'description' => esc_html__( 'Leave empty to scale with the headline.', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 16, 'max' => 90 ) ),
				'selectors'   => array( '{{WRAPPER}} .avix-hero' => '--hero-buddy-w: {{SIZE}}{{UNIT}};' ),
				'condition'   => array( 'show_buddy' => 'yes' ),
			)
		);

		$this->add_control(
			'show_bubble',
			array(
				'label'     => esc_html__( 'Speech bubble', 'avix-widgets' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'separator' => 'before',
				'condition' => array( 'show_buddy' => 'yes' ),
			)
		);

		$this->add_control(
			'greetings',
			array(
				'label'       => esc_html__( 'Greetings', 'avix-widgets' ),
				'description' => esc_html__( 'One per line. The first plays when the page opens; the others follow one by one, and again whenever the avatar is hovered or tapped.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 4,
				'default'     => "Hi, I’m Akib 👋\nWelcome! Make yourself at home.\nPsst… the audit’s on us.",
				'condition'   => array(
					'show_buddy'  => 'yes',
					'show_bubble' => 'yes',
				),
			)
		);

		$this->add_control(
			'button_line',
			array(
				'label'       => esc_html__( 'When the button is hovered', 'avix-widgets' ),
				'description' => esc_html__( 'It waves and says this. Leave empty to use the next greeting.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Good choice! 🙌',
				'label_block' => true,
				'condition'   => array(
					'show_buddy'  => 'yes',
					'show_bubble' => 'yes',
				),
			)
		);

		$this->add_control(
			'greet_every',
			array(
				'label'       => esc_html__( 'Next greeting after (seconds)', 'avix-widgets' ),
				'description' => esc_html__( 'Each greeting is said once, then it just chills. 0 = only on arrival and hover. Pauses while off screen.', 'avix-widgets' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 12,
				'min'         => 0,
				'max'         => 120,
				'step'        => 1,
				'condition'   => array(
					'show_buddy'  => 'yes',
					'show_bubble' => 'yes',
				),
			)
		);

		$this->end_controls_section();
	}

	private function controls_tags() {
		$this->start_controls_section( 'section_tags', array( 'label' => esc_html__( 'Floating Cursor Tags', 'avix-widgets' ) ) );

		$this->add_control(
			'show_tags',
			array(
				'label'       => esc_html__( 'Show', 'avix-widgets' ),
				'description' => esc_html__( 'Multiplayer-style cursor labels beside the headline. Wide screens only.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'tag_1',
			array(
				'label'     => esc_html__( 'Top left (orange)', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Design Agency', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_tags' => 'yes' ),
			)
		);

		$this->add_control(
			'tag_2',
			array(
				'label'     => esc_html__( 'Bottom right (white)', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Shopify Expert', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_tags' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_dock() {
		$this->start_controls_section( 'section_dock', array( 'label' => esc_html__( 'Trust Dock', 'avix-widgets' ) ) );

		$this->add_control(
			'show_dock',
			array(
				'label'   => esc_html__( 'Show', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$repeater = new Repeater();
		$repeater->add_control(
			'icon',
			array(
				'label'   => esc_html__( 'Icon', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'check',
				'options' => $this->icon_options( true, true ),
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
			'text',
			array(
				'label'       => esc_html__( 'Text', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);
		$repeater->add_control(
			'link',
			array(
				'label'       => esc_html__( 'Proof link (optional)', 'avix-widgets' ),
				'description' => esc_html__( 'e.g. your Fiverr or partner profile, so visitors can verify it.', 'avix-widgets' ),
				'type'        => Controls_Manager::URL,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'dock_items',
			array(
				'label'       => esc_html__( 'Items', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ text }}}',
				'default'     => array(
					array(
						'icon' => 'bag',
						'text' => 'Shopify Plus Agency',
					),
					array(
						'icon' => 'shield',
						'text' => 'Fiverr Verified',
					),
					array(
						'icon' => 'trend',
						'text' => 'Involved in Proven business',
					),
				),
				'condition'   => array( 'show_dock' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_background() {
		$this->start_controls_section( 'section_background', array( 'label' => esc_html__( 'Background & Effects', 'avix-widgets' ) ) );

		$this->add_control(
			'bg_image',
			array(
				'label'   => esc_html__( 'Background image', 'avix-widgets' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => array( 'url' => self::BG_IMAGE ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'bg_focus_x',
			array(
				'label'      => esc_html__( 'Image focus X', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array( '%' => array( 'min' => 0, 'max' => 100 ) ),
				'default'    => array( 'unit' => '%', 'size' => 50 ),
			)
		);

		$this->add_control(
			'bg_focus_y',
			array(
				'label'      => esc_html__( 'Image focus Y', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array( '%' => array( 'min' => 0, 'max' => 100 ) ),
				'default'    => array( 'unit' => '%', 'size' => 50 ),
			)
		);

		$this->add_control(
			'bg_priority',
			array(
				'label'       => esc_html__( 'Load image first', 'avix-widgets' ),
				'description' => esc_html__( 'Speeds up the first screen. Turn off if this banner isn’t at the top of the page.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'ken_burns',
			array(
				'label'   => esc_html__( 'Slow zoom', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'show_glass',
			array(
				'label'     => esc_html__( 'Glass pixels', 'avix-widgets' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'show_trail',
			array(
				'label'       => esc_html__( 'Pixel trail under the cursor', 'avix-widgets' ),
				'description' => esc_html__( 'Squares light up where the mouse moves; a tap on phones sends a small ripple.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'trail_size',
			array(
				'label'      => esc_html__( 'Square size', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 16, 'max' => 96 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 40 ),
				'condition'  => array( 'show_trail' => 'yes' ),
			)
		);

		$this->add_control(
			'trail_radius',
			array(
				'label'     => esc_html__( 'Trail radius (squares)', 'avix-widgets' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 1, 'max' => 5, 'step' => 1 ) ),
				'default'   => array( 'size' => 2 ),
				'condition' => array( 'show_trail' => 'yes' ),
			)
		);

		$this->add_control(
			'trail_strength',
			array(
				'label'     => esc_html__( 'Trail strength', 'avix-widgets' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 5, 'max' => 80, 'step' => 5 ) ),
				'default'   => array( 'size' => 30 ),
				'condition' => array( 'show_trail' => 'yes' ),
			)
		);

		$this->add_control(
			'parallax',
			array(
				'label'       => esc_html__( 'Mouse parallax', 'avix-widgets' ),
				'description' => esc_html__( 'Background, glass pixels and tags drift with the mouse. Desktop only.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'parallax_strength',
			array(
				'label'     => esc_html__( 'Parallax strength', 'avix-widgets' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 20, 'max' => 200, 'step' => 10 ) ),
				'default'   => array( 'size' => 100 ),
				'condition' => array( 'parallax' => 'yes' ),
			)
		);

		$this->add_control(
			'entrance',
			array(
				'label'       => esc_html__( 'Entrance animation', 'avix-widgets' ),
				'description' => esc_html__( 'Visitors who prefer reduced motion get a still banner either way.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
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

		$colors = array(
			'hero_bg'     => array( esc_html__( 'Background', 'avix-widgets' ), '--hero-bg' ),
			'hero_accent' => array( esc_html__( 'Accent', 'avix-widgets' ), '--hero-accent' ),
			'hero_ink'    => array( esc_html__( 'Headline', 'avix-widgets' ), '--hero-ink' ),
			'hero_text'   => array( esc_html__( 'Supporting text', 'avix-widgets' ), '--hero-text' ),
			'hero_muted'  => array( esc_html__( 'Trust dock text', 'avix-widgets' ), '--hero-muted' ),
		);
		foreach ( $colors as $key => $color ) {
			$this->add_control(
				$key,
				array(
					'label'     => $color[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-hero' => $color[1] . ': {{VALUE}};' ),
				)
			);
		}

		$this->add_control(
			'overlay_heading',
			array(
				'label'     => esc_html__( 'Image overlay', 'avix-widgets' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$overlay = array(
			'overlay_top'    => array( esc_html__( 'Top', 'avix-widgets' ), '--hero-overlay-top' ),
			'overlay_bottom' => array( esc_html__( 'Bottom', 'avix-widgets' ), '--hero-overlay-bottom' ),
		);
		foreach ( $overlay as $key => $color ) {
			$this->add_control(
				$key,
				array(
					'label'     => $color[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-hero' => $color[1] . ': {{VALUE}};' ),
				)
			);
		}

		$this->add_control(
			'top_fade',
			array(
				'label'       => esc_html__( 'Fade into the header', 'avix-widgets' ),
				'description' => esc_html__( 'Darkens the top edge so a transparent header stays readable.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'effects_heading',
			array(
				'label'     => esc_html__( 'Effects', 'avix-widgets' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$effects = array(
			'glass_1' => array( esc_html__( 'Glass pixel 1', 'avix-widgets' ), '--hero-glass-1' ),
			'glass_2' => array( esc_html__( 'Glass pixel 2', 'avix-widgets' ), '--hero-glass-2' ),
			'glass_3' => array( esc_html__( 'Glass pixel 3', 'avix-widgets' ), '--hero-glass-3' ),
			'trail'   => array( esc_html__( 'Pixel trail', 'avix-widgets' ), '--hero-trail' ),
		);
		foreach ( $effects as $key => $color ) {
			$this->add_control(
				$key . '_color',
				array(
					'label'     => $color[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-hero' => $color[1] . ': {{VALUE}};' ),
				)
			);
		}

		$this->add_responsive_control(
			'min_height',
			array(
				'label'      => esc_html__( 'Minimum height', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'vh', 'px' ),
				'range'      => array(
					'vh' => array( 'min' => 40, 'max' => 100 ),
					'px' => array( 'min' => 400, 'max' => 1200 ),
				),
				'separator'  => 'before',
				'selectors'  => array( '{{WRAPPER}} .avix-hero' => '--hero-min-h: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'padding',
			array(
				'label'              => esc_html__( 'Padding', 'avix-widgets' ),
				'description'        => esc_html__( 'Top padding keeps the content clear of a transparent header.', 'avix-widgets' ),
				'type'               => Controls_Manager::DIMENSIONS,
				'size_units'         => array( 'px', 'vh' ),
				'allowed_dimensions' => 'vertical',
				'selectors'          => array( '{{WRAPPER}} .avix-hero' => '--hero-pad-top: {{TOP}}{{UNIT}}; --hero-pad-bottom: {{BOTTOM}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'max_width',
			array(
				'label'      => esc_html__( 'Content width', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 600, 'max' => 1600 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-hero' => '--hero-max: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_headline',
			array(
				'label' => esc_html__( 'Headline', 'avix-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'mark_color',
			array(
				'label'     => esc_html__( 'Highlight colour', 'avix-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .avix-hero' => '--hero-mark: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'mark_tilt',
			array(
				'label'      => esc_html__( 'Highlight tilt', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'deg' ),
				'range'      => array( 'deg' => array( 'min' => -10, 'max' => 10, 'step' => 0.5 ) ),
				'default'    => array( 'unit' => 'deg', 'size' => -3 ),
				'selectors'  => array( '{{WRAPPER}} .avix-hero' => '--hero-mark-tilt: {{SIZE}}deg;' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'label'    => esc_html__( 'Headline', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-hero .avix-hero__title',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'subtitle_typography',
				'label'    => esc_html__( 'Supporting text', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-hero .avix-hero__subtitle',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_parts',
			array(
				'label' => esc_html__( 'Badge, Button & Dock', 'avix-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'button_bg',
			array(
				'label'     => esc_html__( 'Button background', 'avix-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .avix-hero' => '--hero-btn-bg: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'button_ink',
			array(
				'label'     => esc_html__( 'Button text', 'avix-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .avix-hero' => '--hero-btn-ink: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'button_typography',
				'label'    => esc_html__( 'Button', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-hero .avix-hero__btn, {{WRAPPER}} .avix-hero .avix-hero__link',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'badge_typography',
				'label'    => esc_html__( 'Badge', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-hero .avix-hero__badge',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'dock_typography',
				'label'    => esc_html__( 'Trust dock', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-hero .avix-hero__dock-item',
			)
		);

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ */
	/* Render                                                              */
	/* ------------------------------------------------------------------ */

	protected function render(): void {
		$s        = $this->get_settings_for_display();
		$title_id = 'avix-hero-title-' . $this->get_id();
		$buddy    = 'yes' === $s['show_buddy'];
		$bubble   = $buddy && 'yes' === $s['show_bubble'];
		$trail    = 'yes' === $s['show_trail'];
		$title    = $this->title_html(
			(string) $s['title'],
			$buddy ? (string) $s['buddy_word'] : null,
			$this->headline_icon( $s ),
			$this->buddy_html( $bubble )
		);

		$classes = array( 'avix-hero' );
		if ( 'yes' === $s['entrance'] ) {
			$classes[] = 'avix-hero--enter';
		}
		if ( 'yes' === $s['ken_burns'] ) {
			$classes[] = 'avix-hero--zoom';
		}
		if ( 'yes' !== $s['top_fade'] ) {
			$classes[] = 'avix-hero--no-fade';
		}
		if ( $bubble && '' !== $title ) {
			$classes[] = 'avix-hero--say';
		}

		$config = array(
			'parallax' => 'yes' === $s['parallax'] ? round( $this->slider( $s, 'parallax_strength', 100, 20, 200 ) / 100, 2 ) : 0,
			'trail'    => $trail ? array(
				'size'     => (int) $this->slider( $s, 'trail_size', 40, 16, 96 ),
				'radius'   => (int) $this->slider( $s, 'trail_radius', 2, 1, 5 ),
				'strength' => round( $this->slider( $s, 'trail_strength', 30, 5, 80 ) / 100, 2 ),
			) : false,
		);
		if ( $bubble && '' !== $title ) {
			$config['greet'] = array(
				'lines'  => $this->lines( (string) $s['greetings'] ),
				'button' => trim( (string) $s['button_line'] ),
				'every'  => max( 0, min( 120, (int) $s['greet_every'] ) ),
			);
		}

		$this->add_render_attribute(
			'root',
			array(
				'class'          => $classes,
				'data-avix-hero' => wp_json_encode( $config ),
			)
		);
		if ( '' !== $title ) {
			$this->add_render_attribute( 'root', 'aria-labelledby', $title_id );
		}

		$tag = Utils::validate_html_tag( $s['title_tag'] );
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="avix-hero__bg" aria-hidden="true">
				<div class="avix-hero__media" data-hero-depth="-1.7 -1.7">
					<?php echo $this->image_html( $s ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in image_html(). ?>
				</div>
				<div class="avix-hero__overlay"></div>
				<?php if ( 'yes' === $s['show_glass'] ) : ?>
					<span class="avix-hero__glass avix-hero__glass--1" data-hero-depth="3 3"></span>
					<span class="avix-hero__glass avix-hero__glass--2" data-hero-depth="-4 -4"></span>
					<span class="avix-hero__glass avix-hero__glass--3" data-hero-depth="2 -2"></span>
				<?php endif; ?>
				<?php if ( $trail ) : ?>
					<canvas class="avix-hero__trail" data-hero-trail></canvas>
				<?php endif; ?>
				<div class="avix-hero__fade"></div>
			</div>

			<div class="avix-hero__body">
				<div class="avix-hero__content">
					<?php $this->render_badge( $s ); ?>

					<?php if ( '' !== $title ) : ?>
						<div class="avix-hero__head">
							<?php $this->render_tags( $s ); ?>
							<<?php echo esc_attr( $tag ); ?> class="avix-hero__title" id="<?php echo esc_attr( $title_id ); ?>"><?php echo $title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in title_html(). ?></<?php echo esc_attr( $tag ); ?>>
						</div>
					<?php endif; ?>

					<?php if ( '' !== trim( (string) $s['subtitle'] ) ) : ?>
						<p class="avix-hero__subtitle"><?php echo esc_html( $s['subtitle'] ); ?></p>
					<?php endif; ?>

					<?php $this->render_actions( $s ); ?>
				</div>

				<?php $this->render_dock( $s ); ?>
			</div>
		</section>
		<?php
	}

	private function render_badge( array $s ) {
		$text = trim( (string) $s['badge_text'] );
		if ( 'yes' !== $s['show_badge'] || '' === $text ) {
			return;
		}

		$icon = '';
		switch ( $s['badge_icon_type'] ) {
			case 'mark':
				$icon = '<svg class="avix-hero__badge-icon" viewBox="0 0 60 60" aria-hidden="true" focusable="false"><path d="' . esc_attr( self::MARK ) . '"/></svg>';
				break;
			case 'dot':
				$icon = '<span class="avix-hero__dot" aria-hidden="true"></span>';
				break;
			case 'custom':
				$icon = $this->elementor_icon( $s['badge_icon'] ?? array(), 'avix-hero__badge-icon' );
				break;
		}

		$tag = empty( $s['badge_link']['url'] ) ? 'p' : 'a';
		if ( 'a' === $tag ) {
			$this->add_link_attributes( 'badge', $s['badge_link'] );
		}
		$this->add_render_attribute( 'badge', 'class', 'avix-hero__badge' );
		?>
		<<?php echo esc_attr( $tag ); ?> <?php $this->print_render_attribute_string( 'badge' ); ?>><?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup or Elementor icon. ?><span><?php echo esc_html( $text ); ?></span></<?php echo esc_attr( $tag ); ?>>
		<?php
	}

	private function render_tags( array $s ) {
		if ( 'yes' !== $s['show_tags'] ) {
			return;
		}
		$tags = array(
			1 => array( trim( (string) $s['tag_1'] ), '-0.6 -0.6' ),
			2 => array( trim( (string) $s['tag_2'] ), '0.6 0.6' ),
		);
		foreach ( $tags as $n => $tag ) {
			if ( '' === $tag[0] ) {
				continue;
			}
			printf(
				'<div class="avix-hero__tag avix-hero__tag--%1$d" data-hero-depth="%2$s" aria-hidden="true"><svg class="avix-hero__cursor" viewBox="0 0 24 24" focusable="false"><path d="M5.5 3.2 20.8 11.5l-7.3 2-2 7.3z"/></svg><span class="avix-hero__pill">%3$s</span></div>',
				(int) $n,
				esc_attr( $tag[1] ),
				esc_html( $tag[0] )
			);
		}
	}

	private function render_actions( array $s ) {
		$primary   = '' !== trim( (string) $s['button_text'] ) && ! empty( $s['button_link']['url'] );
		$secondary = 'yes' === $s['show_button_2'] && '' !== trim( (string) $s['button_2_text'] ) && ! empty( $s['button_2_link']['url'] );
		if ( ! $primary && ! $secondary ) {
			return;
		}
		?>
		<div class="avix-hero__actions">
			<?php
			if ( $primary ) :
				$this->add_link_attributes( 'button', $s['button_link'] );
				$this->add_render_attribute(
					'button',
					array(
						'class'         => 'avix-hero__btn',
						'data-hero-cta' => '',
					)
				);
				?>
				<a <?php $this->print_render_attribute_string( 'button' ); ?>>
					<span class="avix-hero__btn-text"><?php echo esc_html( $s['button_text'] ); ?></span>
					<span class="avix-hero__btn-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M7 17 17 7M7 7h10v10"/></svg></span>
				</a>
			<?php endif; ?>

			<?php
			if ( $secondary ) :
				$this->add_link_attributes( 'button-2', $s['button_2_link'] );
				$this->add_render_attribute( 'button-2', 'class', 'avix-hero__link' );
				?>
				<a <?php $this->print_render_attribute_string( 'button-2' ); ?>>
					<?php echo esc_html( $s['button_2_text'] ); ?>
					<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
				</a>
			<?php endif; ?>
		</div>
		<?php
	}

	private function render_dock( array $s ) {
		if ( 'yes' !== $s['show_dock'] ) {
			return;
		}
		$items = array_values(
			array_filter(
				(array) $s['dock_items'],
				static function ( $row ) {
					return '' !== trim( (string) ( $row['text'] ?? '' ) );
				}
			)
		);
		if ( ! $items ) {
			return;
		}
		?>
		<div class="avix-hero__dock-wrap">
			<ul class="avix-hero__dock" role="list">
				<?php
				foreach ( $items as $i => $row ) :
					$icon  = $this->dock_icon( $row );
					$inner = $icon . '<span>' . esc_html( $row['text'] ) . '</span>';
					?>
					<li class="avix-hero__dock-item">
						<?php
						if ( ! empty( $row['link']['url'] ) ) {
							$key = 'dock-' . $i;
							$this->add_link_attributes( $key, $row['link'] );
							$this->add_render_attribute( $key, 'class', 'avix-hero__dock-link' );
							echo '<a ' . $this->get_render_attribute_string( $key ) . '>' . $inner . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above; attributes escaped by Elementor.
						} else {
							echo $inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
						}
						?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	/* ------------------------------------------------------------------ */
	/* Markup helpers                                                      */
	/* ------------------------------------------------------------------ */

	/**
	 * Headline markup from the plain-text field: [words] become the orange
	 * highlight, {icon} the round icon, new lines <br>. When $perch is given,
	 * the first whole-word match (or the first word) gets the avatar.
	 *
	 * Text is escaped before any markup is added. The perch is marked with
	 * control characters (stripped from the input first) so the token pass
	 * can never match inside generated tags.
	 *
	 * @param string      $raw   Headline field.
	 * @param string|null $perch Word the avatar sits on, or null for no avatar.
	 * @param string      $icon  Markup for {icon}.
	 * @param string      $buddy Avatar markup.
	 */
	private function title_html( $raw, $perch, $icon, $buddy ) {
		$raw   = (string) preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $raw );
		$lines = array_map( 'esc_html', $this->lines( $raw ) );
		$lines = array_values( array_filter( $lines, 'strlen' ) );
		if ( ! $lines ) {
			return '';
		}

		if ( null !== $perch ) {
			$lines = $this->mark_perch( $lines, trim( $perch ) );
		}

		foreach ( $lines as $i => $line ) {
			$marked      = preg_replace( '/\[([^\[\]]+)\]/u', '<span class="avix-hero__mark">$1</span>', $line );
			$lines[ $i ] = str_replace( '{icon}', $icon, null === $marked ? $line : $marked );
		}

		// The space before <br> is invisible but keeps words apart in the text (search, screen readers).
		return str_replace(
			array( "\x01", "\x02" ),
			array( '<span class="avix-hero__perch">', $buddy . '</span>' ),
			implode( ' <br>', $lines )
		);
	}

	/**
	 * Wraps the perch word in \x01…\x02: the first whole-word, case-insensitive
	 * match, else the first word of the first line. Never matches inside {icon}
	 * or an HTML entity.
	 *
	 * @param string[] $lines Escaped headline lines.
	 * @param string   $perch Word the avatar sits on.
	 * @return string[]
	 */
	private function mark_perch( array $lines, $perch ) {
		if ( '' !== $perch ) {
			$pattern = '/(?<![\p{L}\p{N}{&#])' . preg_quote( esc_html( $perch ), '/' ) . '(?![\p{L}\p{N}}])/iu';
			foreach ( $lines as $i => $line ) {
				$count  = 0;
				$marked = preg_replace( $pattern, "\x01\$0\x02", $line, 1, $count );
				if ( $count && null !== $marked ) {
					$lines[ $i ] = $marked;
					return $lines;
				}
			}
		}

		$done   = false;
		$marked = preg_replace_callback(
			'/\{icon\}|(?<![&#\p{L}\p{N}])[\p{L}\p{N}][^\s\[\]{}]*/u',
			static function ( $match ) use ( &$done ) {
				if ( $done || '{icon}' === $match[0] ) {
					return $match[0];
				}
				$done = true;
				return "\x01" . $match[0] . "\x02";
			},
			$lines[0]
		);
		if ( null !== $marked ) {
			$lines[0] = $marked;
		}
		return $lines;
	}

	/**
	 * The Avix pixel character (same 10-unit grid as the site), sitting.
	 * The speech bubble reads its text from data-say via CSS, so greetings
	 * never become part of the headline's text.
	 */
	private function buddy_html( $bubble ) {
		return '<span class="avix-hero__buddy" data-hero-buddy aria-hidden="true">'
			. ( $bubble ? '<span class="avix-hero__say" data-hero-say data-say=""></span>' : '' )
			. '<span class="avix-hero__sprite"><svg viewBox="0 0 10 11" focusable="false">'
			. '<rect class="avix-hero__b-leg avix-hero__b-leg--l" x="2" y="7" width="2" height="4"/>'
			. '<rect class="avix-hero__b-leg avix-hero__b-leg--r" x="6" y="7" width="2" height="4"/>'
			. '<rect class="avix-hero__b-body" x="2" y="3" width="6" height="4"/>'
			. '<rect class="avix-hero__b-head" x="3" y="0" width="4" height="3"/>'
			. '<rect class="avix-hero__b-arm avix-hero__b-arm--l" x="0" y="4" width="2" height="3"/>'
			. '<rect class="avix-hero__b-arm avix-hero__b-arm--r" x="8" y="4" width="2" height="3"/>'
			. '</svg></span></span>';
	}

	private function headline_icon( array $s ) {
		$key = (string) $s['title_icon'];
		if ( 'custom' === $key ) {
			$inner = $this->elementor_icon( $s['title_icon_custom'] ?? array() );
		} else {
			$inner = $this->line_icon( isset( self::ICONS[ $key ] ) ? $key : 'layout' );
		}
		return '' === $inner ? '' : '<span class="avix-hero__icon" aria-hidden="true">' . $inner . '</span>';
	}

	private function dock_icon( array $row ) {
		$key = (string) ( $row['icon'] ?? '' );
		if ( 'custom' === $key ) {
			return $this->elementor_icon( $row['icon_custom'] ?? array(), 'avix-hero__dock-icon' );
		}
		return isset( self::ICONS[ $key ] ) ? $this->line_icon( $key, 'avix-hero__dock-icon' ) : '';
	}

	private function line_icon( $key, $class = '' ) {
		return sprintf(
			'<svg class="%s" viewBox="0 0 24 24" aria-hidden="true" focusable="false">%s</svg>',
			esc_attr( trim( 'avix-hero__line ' . $class ) ),
			self::ICONS[ $key ][1]
		);
	}

	/**
	 * Captures an Elementor icon (font icon or uploaded SVG) as a string.
	 */
	private function elementor_icon( $icon, $class = '' ) {
		if ( empty( $icon['value'] ) ) {
			return '';
		}
		ob_start();
		Icons_Manager::render_icon(
			$icon,
			array(
				'aria-hidden' => 'true',
				'class'       => $class,
			)
		);
		return trim( (string) ob_get_clean() );
	}

	/**
	 * Background as a real <img>: responsive srcset from the media library,
	 * and fetched first when it's the top of the page (it is the LCP image).
	 */
	private function image_html( array $s ) {
		$image    = (array) $s['bg_image'];
		$url      = (string) ( $image['url'] ?? '' );
		$id       = $this->media_id( $image );
		$priority = 'yes' === $s['bg_priority'];
		$focus    = sprintf(
			'object-position:%s%% %s%%;',
			$this->slider( $s, 'bg_focus_x', 50, 0, 100 ),
			$this->slider( $s, 'bg_focus_y', 50, 0, 100 )
		);
		$attrs    = array(
			'class'    => 'avix-hero__img',
			'alt'      => '',
			'sizes'    => '100vw',
			'style'    => $focus,
			'loading'  => $priority ? 'eager' : 'lazy',
			'decoding' => $priority ? 'auto' : 'async',
		);
		if ( $priority ) {
			$attrs['fetchpriority'] = 'high';
		}

		if ( $id ) {
			$html = wp_get_attachment_image( $id, 'full', false, $attrs );
			if ( $html ) {
				return $html;
			}
		}
		if ( '' === $url ) {
			return '';
		}

		$out = '<img src="' . esc_url( $url ) . '"';
		unset( $attrs['sizes'] );
		foreach ( $attrs as $name => $value ) {
			$out .= ' ' . $name . '="' . esc_attr( $value ) . '"';
		}
		return $out . '>';
	}

	/**
	 * Non-empty, trimmed lines of a textarea.
	 *
	 * @return string[]
	 */
	private function lines( $text ) {
		return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $text ) ), 'strlen' ) );
	}

	private function slider( array $s, $key, $fallback, $min, $max ) {
		$value = isset( $s[ $key ]['size'] ) && '' !== $s[ $key ]['size'] ? (float) $s[ $key ]['size'] : $fallback;
		return max( $min, min( $max, $value ) );
	}
}
