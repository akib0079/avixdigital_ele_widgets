<?php
/**
 * Page Hero: the top of an inner page (Services first). A rounded stage holds
 * a breadcrumb, eyebrow, headline, lead, two buttons and a proof row on the
 * left, and on the right an "orbit" of platform chips circling the Avix hub,
 * where the pixel character stands, says hi and looks at whatever you hover.
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

class Page_Hero extends Widget_Base {

	use Media;

	/** Default image for the "Image" visual: platforms and the client portal. */
	const IMAGE = 'https://avixdigital.com/wp-content/uploads/2026/09/ChatGPT-Image-29-Sept-2026-02_12_10.png';

	/** The Avix mark (60×60), shown on the centre hub of the orbit. */
	const MARK = 'M0.000609729 33.4786V24.609H15.5435C15.9776 24.609 16.3249 24.522 16.6724 24.2613L0.608473 8.17411L1.91078 6.78291L8.16259 0.522042L24.3131 16.6963C24.4868 16.3485 24.6603 15.9135 24.6603 15.5658V15.4788V0H26.5705H33.4301H35.3403V15.4785V15.5655C35.3403 15.9132 35.5141 16.3479 35.6875 16.696L51.838 0.521739L58.0898 6.78261L59.3921 8.17381L43.3282 24.261C43.6754 24.5217 44.023 24.6087 44.4571 24.6087H60V33.4783V35.3913H44.4571C41.0705 35.3913 38.0315 34.0871 35.6872 31.9131L34.819 32.7826L32.7351 34.8695L31.8669 35.739C34.0377 37.9999 35.34 41.1304 35.34 44.5215V60H33.4298H24.66V44.5221C24.66 44.0874 24.4862 43.6527 24.3128 43.3046L8.16228 59.4789L6.85967 58.0877L0.607873 51.8268L16.6718 35.7396C16.3246 35.5656 15.977 35.3919 15.5429 35.3919H0V33.4786H0.000609729Z';

	public function get_name(): string {
		return 'avix-page-hero';
	}

	public function get_title(): string {
		return esc_html__( 'Page Hero', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-banner';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'hero', 'page', 'services', 'banner', 'breadcrumb', 'orbit', 'platforms', 'pixel', 'avix' );
	}

	public function get_style_depends(): array {
		return array( 'avix-page-hero' );
	}

	public function get_script_depends(): array {
		return array( 'avix-page-hero' );
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/* ------------------------------------------------------------------ */
	/* Controls                                                            */
	/* ------------------------------------------------------------------ */

	protected function register_controls(): void {
		$this->controls_header();
		$this->controls_buttons();
		$this->controls_proof();
		$this->controls_visual();
		$this->controls_buddy();
		$this->controls_schema();
		$this->controls_style();
		$this->controls_type();
		$this->controls_orbit_style();
	}

	private function controls_header() {
		$this->start_controls_section( 'section_header', array( 'label' => esc_html__( 'Hero content', 'avix-widgets' ) ) );

		$this->add_control(
			'show_breadcrumb',
			array(
				'label'       => esc_html__( 'Breadcrumb', 'avix-widgets' ),
				'description' => esc_html__( 'A small “Home › This page” trail above the eyebrow.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'breadcrumb_home',
			array(
				'label'     => esc_html__( 'Home label', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Home', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_breadcrumb' => 'yes' ),
			)
		);

		$this->add_control(
			'breadcrumb_parent',
			array(
				'label'       => esc_html__( 'Parent page label', 'avix-widgets' ),
				'description' => esc_html__( 'Optional middle step, e.g. “Services” on a service page: the trail reads Home › Services › This page. Leave empty for Home › This page.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_breadcrumb' => 'yes' ),
			)
		);

		$this->add_control(
			'breadcrumb_parent_link',
			array(
				'label'       => esc_html__( 'Parent page link', 'avix-widgets' ),
				'description' => esc_html__( 'Where the middle step goes, e.g. /service/. Without a link it shows as plain text.', 'avix-widgets' ),
				'type'        => Controls_Manager::URL,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array(
					'show_breadcrumb'    => 'yes',
					'breadcrumb_parent!' => '',
				),
			)
		);

		$this->add_control(
			'breadcrumb_current',
			array(
				'label'       => esc_html__( 'Current page label', 'avix-widgets' ),
				'description' => esc_html__( 'Leave empty to use the page title.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_breadcrumb' => 'yes' ),
			)
		);

		$this->add_control(
			'eyebrow',
			array(
				'label'       => esc_html__( 'Eyebrow', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'AvixDigital services', 'avix-widgets' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Headline', 'avix-widgets' ),
				'description' => esc_html__( 'Wrap words in [brackets] to highlight them in orange. Press Enter for a new line.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 3,
				'default'     => 'Websites and web applications [built for your business.]',
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
			'text',
			array(
				'label'   => esc_html__( 'Lead text', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 4,
				'default' => 'From a brand website to an ecommerce store or a custom business application, we help you turn a clear brief into a practical digital product.',
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_buttons() {
		$this->start_controls_section( 'section_buttons', array( 'label' => esc_html__( 'Buttons', 'avix-widgets' ) ) );

		$this->add_control(
			'button_text',
			array(
				'label'       => esc_html__( 'Main button text', 'avix-widgets' ),
				'description' => esc_html__( 'Leave empty to hide the button.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Discuss your project', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'button_link',
			array(
				'label'       => esc_html__( 'Main button link', 'avix-widgets' ),
				'description' => esc_html__( 'Required: without a link the button is left out of the live page.', 'avix-widgets' ),
				'type'        => Controls_Manager::URL,
				'dynamic'     => array( 'active' => true ),
				'default'     => array( 'url' => 'https://avixdigital.com/contact/' ),
			)
		);

		$this->add_control(
			'show_button_2',
			array(
				'label'       => esc_html__( 'Second button', 'avix-widgets' ),
				'description' => esc_html__( 'A quieter outlined option, e.g. a jump to the process section.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'button_2_text',
			array(
				'label'     => esc_html__( 'Text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'See how we work', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_button_2' => 'yes' ),
			)
		);

		$this->add_control(
			'button_2_link',
			array(
				'label'       => esc_html__( 'Link', 'avix-widgets' ),
				'description' => esc_html__( 'A page, or #id of a section on this page (set the ID under the section’s Advanced tab). Without a link the button is left out of the live page.', 'avix-widgets' ),
				'type'        => Controls_Manager::URL,
				'dynamic'     => array( 'active' => true ),
				'default'     => array( 'url' => '#process' ),
				'condition'   => array( 'show_button_2' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_proof() {
		$this->start_controls_section( 'section_proof', array( 'label' => esc_html__( 'Proof row', 'avix-widgets' ) ) );

		$this->add_control(
			'show_proof',
			array(
				'label'   => esc_html__( 'Show', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'value',
			array(
				'label'   => esc_html__( 'Value', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '250+',
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'label',
			array(
				'label'       => esc_html__( 'Label', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Websites built', 'avix-widgets' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'proof',
			array(
				'label'       => esc_html__( 'Items', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ value }}} · {{{ label }}}',
				'default'     => array(
					array(
						'value' => '250+',
						'label' => 'Websites built',
					),
					array(
						'value' => '5.0',
						'label' => 'Fiverr Pro rating',
					),
					array(
						'value' => 'Since 2020',
						'label' => 'Building for brands worldwide',
					),
				),
				'condition'   => array( 'show_proof' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_visual() {
		$this->start_controls_section( 'section_visual', array( 'label' => esc_html__( 'Visual', 'avix-widgets' ) ) );

		$this->add_control(
			'visual',
			array(
				'label'       => esc_html__( 'Right side', 'avix-widgets' ),
				'description' => esc_html__( 'Orbit: the platforms you build with circle the Avix hub, where the pixel character stands.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'orbit',
				'options'     => array(
					'orbit' => esc_html__( 'Platform orbit', 'avix-widgets' ),
					'image' => esc_html__( 'Image', 'avix-widgets' ),
					'none'  => esc_html__( 'None (text only)', 'avix-widgets' ),
				),
			)
		);

		$chips = new Repeater();

		$chips->add_control(
			'label',
			array(
				'label'   => esc_html__( 'Name', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => 'Shopify',
				'dynamic' => array( 'active' => true ),
			)
		);

		$chips->add_control(
			'icon',
			array(
				'label'   => esc_html__( 'Logo', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'shopify',
				'options' => array_merge( Brand_Icons::options(), array( 'upload' => esc_html__( 'Upload a logo…', 'avix-widgets' ) ) ),
			)
		);

		$chips->add_control(
			'logo',
			array(
				'label'     => esc_html__( 'Logo image', 'avix-widgets' ),
				'type'      => Controls_Manager::MEDIA,
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'icon' => 'upload' ),
			)
		);

		$chips->add_control(
			'link',
			array(
				'label'       => esc_html__( 'Link (optional)', 'avix-widgets' ),
				'description' => esc_html__( 'Turns the chip into a link, e.g. to that service page.', 'avix-widgets' ),
				'type'        => Controls_Manager::URL,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$chips->add_control(
			'ring',
			array(
				'label'   => esc_html__( 'Ring', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => array(
					'auto'  => esc_html__( 'Automatic', 'avix-widgets' ),
					'inner' => esc_html__( 'Inner ring', 'avix-widgets' ),
					'outer' => esc_html__( 'Outer ring', 'avix-widgets' ),
				),
			)
		);

		$this->add_control(
			'chips',
			array(
				'label'       => esc_html__( 'Platform chips', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $chips->get_controls(),
				'title_field' => '{{{ label }}}',
				'default'     => array(
					array(
						'label' => 'Shopify',
						'icon'  => 'shopify',
						'link'  => array( 'url' => 'https://avixdigital.com/service/shopify-plus/' ),
					),
					array(
						'label' => 'WordPress',
						'icon'  => 'wordpress',
						'link'  => array( 'url' => 'https://avixdigital.com/service/wordpress-development/' ),
					),
					array(
						'label' => 'Webflow',
						'icon'  => 'webflow',
					),
					array(
						'label' => 'React',
						'icon'  => 'react',
						'link'  => array( 'url' => 'https://avixdigital.com/service/web-development/' ),
					),
					array(
						'label' => 'Next.js',
						'icon'  => 'nextjs',
						'link'  => array( 'url' => 'https://avixdigital.com/service/web-development/' ),
					),
					array(
						'label' => 'Figma',
						'icon'  => 'figma',
						'link'  => array( 'url' => 'https://avixdigital.com/service/uiux-and-brand-design/' ),
					),
					array(
						'label' => 'Custom code',
						'icon'  => 'code',
					),
				),
				'condition'   => array( 'visual' => 'orbit' ),
			)
		);

		$this->add_control(
			'chip_labels',
			array(
				'label'       => esc_html__( 'Chip names', 'avix-widgets' ),
				'description' => esc_html__( 'On hover: logos only, the name appears when a chip is hovered, focused or tapped. Always visible turns chips into name pills spread around the outer ring, for up to 8 platforms; orbits narrower than 400px (phones) fall back to logos.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'hover',
				'options'     => array(
					'hover'  => esc_html__( 'On hover', 'avix-widgets' ),
					'always' => esc_html__( 'Always visible', 'avix-widgets' ),
				),
				'condition'   => array( 'visual' => 'orbit' ),
			)
		);

		$this->add_control(
			'orbit_label',
			array(
				'label'       => esc_html__( 'Screen-reader label', 'avix-widgets' ),
				'description' => esc_html__( 'Read out before the list of platforms.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Platforms we build with', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'visual' => 'orbit' ),
			)
		);

		$this->add_control(
			'centre',
			array(
				'label'     => esc_html__( 'Centre hub', 'avix-widgets' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'mark',
				'options'   => array(
					'mark'  => esc_html__( 'Avix mark', 'avix-widgets' ),
					'image' => esc_html__( 'Image / logo', 'avix-widgets' ),
					'none'  => esc_html__( 'Empty tile', 'avix-widgets' ),
				),
				'separator' => 'before',
				'condition' => array( 'visual' => 'orbit' ),
			)
		);

		$this->add_control(
			'centre_image',
			array(
				'label'     => esc_html__( 'Hub image', 'avix-widgets' ),
				'type'      => Controls_Manager::MEDIA,
				'dynamic'   => array( 'active' => true ),
				'condition' => array(
					'visual' => 'orbit',
					'centre' => 'image',
				),
			)
		);

		$this->add_control(
			'rotate',
			array(
				'label'       => esc_html__( 'Rotate the rings', 'avix-widgets' ),
				'description' => esc_html__( 'The rings turn slowly while the chips stay upright. It pauses on hover, off screen and in the editor.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
				'condition'   => array( 'visual' => 'orbit' ),
			)
		);

		$this->add_control(
			'speed',
			array(
				'label'      => esc_html__( 'Seconds per turn', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 's' ),
				'range'      => array( 's' => array( 'min' => 20, 'max' => 240 ) ),
				'default'    => array( 'unit' => 's', 'size' => 90 ),
				'selectors'  => array( '{{WRAPPER}} .avix-ph' => '--ph-speed: {{SIZE}}s;' ),
				'condition'  => array(
					'visual' => 'orbit',
					'rotate' => 'yes',
				),
			)
		);

		$this->add_control(
			'show_pause',
			array(
				'label'       => esc_html__( 'Pause button', 'avix-widgets' ),
				'description' => esc_html__( 'A small button that stops the motion (turning rings, the character\'s greetings), for visitors who prefer it still.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
				'condition'   => array( 'visual!' => 'none' ),
			)
		);

		$this->add_control(
			'pause_text',
			array(
				'label'     => esc_html__( 'Pause label', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Pause animation', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array(
					'visual!'    => 'none',
					'show_pause' => 'yes',
				),
			)
		);

		$this->add_control(
			'play_text',
			array(
				'label'     => esc_html__( 'Play label', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Play animation', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array(
					'visual!'    => 'none',
					'show_pause' => 'yes',
				),
			)
		);

		$this->add_control(
			'image',
			array(
				'label'     => esc_html__( 'Image', 'avix-widgets' ),
				'type'      => Controls_Manager::MEDIA,
				'dynamic'   => array( 'active' => true ),
				'default'   => array( 'url' => self::IMAGE ),
				'condition' => array( 'visual' => 'image' ),
			)
		);

		$this->add_control(
			'image_alt',
			array(
				'label'       => esc_html__( 'Alt text', 'avix-widgets' ),
				'description' => esc_html__( 'Leave empty to use the alt text from the Media Library.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'visual' => 'image' ),
			)
		);

		$this->add_responsive_control(
			'image_ratio',
			array(
				'label'     => esc_html__( 'Image shape', 'avix-widgets' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'auto',
				'options'   => array(
					'auto'    => esc_html__( 'Original (whole image)', 'avix-widgets' ),
					'1 / 1'   => esc_html__( 'Square', 'avix-widgets' ),
					'4 / 5'   => esc_html__( 'Portrait 4:5', 'avix-widgets' ),
					'4 / 3'   => esc_html__( 'Landscape 4:3', 'avix-widgets' ),
					'16 / 10' => esc_html__( 'Wide 16:10', 'avix-widgets' ),
				),
				'selectors' => array( '{{WRAPPER}} .avix-ph' => '--ph-ratio: {{VALUE}};' ),
				'condition' => array( 'visual' => 'image' ),
			)
		);

		$this->add_responsive_control(
			'image_focus',
			array(
				'label'       => esc_html__( 'Image focus', 'avix-widgets' ),
				'description' => esc_html__( 'Which part of the image stays in view when the shape crops it.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '50% 50%',
				'options'     => array(
					'50% 50%'  => esc_html__( 'Centre', 'avix-widgets' ),
					'50% 0%'   => esc_html__( 'Top', 'avix-widgets' ),
					'50% 100%' => esc_html__( 'Bottom', 'avix-widgets' ),
					'0% 50%'   => esc_html__( 'Left', 'avix-widgets' ),
					'100% 50%' => esc_html__( 'Right', 'avix-widgets' ),
				),
				'selectors'   => array( '{{WRAPPER}} .avix-ph' => '--ph-img-pos: {{VALUE}};' ),
				'condition'   => array(
					'visual'       => 'image',
					'image_ratio!' => 'auto',
				),
			)
		);

		$this->add_control(
			'image_frame',
			array(
				'label'       => esc_html__( 'Image frame', 'avix-widgets' ),
				'description' => esc_html__( 'Product render: a warm orange shadow, a hairline edge and a round icon-only pause button, made for square device renders on a white background. Dark: for dark device renders on a dark stage: an orange rim glow and a glass pause button.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => array(
					''       => esc_html__( 'Classic', 'avix-widgets' ),
					'render' => esc_html__( 'Product render', 'avix-widgets' ),
					'glow'   => esc_html__( 'Product render, dark (orange glow)', 'avix-widgets' ),
				),
				'condition'   => array( 'visual' => 'image' ),
			)
		);

		$this->add_control(
			'image_eager',
			array(
				'label'       => esc_html__( 'Load image first', 'avix-widgets' ),
				'description' => esc_html__( 'Keep on when this hero is at the top of the page, so the image appears straight away.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'condition'   => array( 'visual' => 'image' ),
			)
		);

		$this->add_control(
			'show_image_chip',
			array(
				'label'       => esc_html__( 'Stat chip on the image', 'avix-widgets' ),
				'description' => esc_html__( 'A small white card on the image’s bottom-left corner with a big value and a short label, e.g. “$50M+ · Client revenue scaled”. On phones it sits under the image so it never covers it.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
				'separator'   => 'before',
				'condition'   => array( 'visual' => 'image' ),
			)
		);

		$this->add_control(
			'image_chip_value',
			array(
				'label'       => esc_html__( 'Value', 'avix-widgets' ),
				'description' => esc_html__( 'A number or a few words: “$50M+”, “UI/UX”, “Built for people”.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '$50M+',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array(
					'visual'          => 'image',
					'show_image_chip' => 'yes',
				),
			)
		);

		$this->add_control(
			'image_chip_label',
			array(
				'label'       => esc_html__( 'Label', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Client revenue scaled', 'avix-widgets' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array(
					'visual'          => 'image',
					'show_image_chip' => 'yes',
				),
			)
		);

		$this->add_control(
			'image_chip_badge',
			array(
				'label'       => esc_html__( 'Check seal', 'avix-widgets' ),
				'description' => esc_html__( 'A scalloped seal with an orange check in front of the value.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'condition'   => array(
					'visual'          => 'image',
					'show_image_chip' => 'yes',
				),
			)
		);

		$this->end_controls_section();
	}

	private function controls_buddy() {
		$this->start_controls_section( 'section_buddy', array( 'label' => esc_html__( 'Pixel character', 'avix-widgets' ) ) );

		$this->add_control(
			'show_pal',
			array(
				'label'       => esc_html__( 'Show pixel character', 'avix-widgets' ),
				'description' => esc_html__( 'It drops onto the hub (or the image), says hi now and then, and looks at whichever platform or button the visitor hovers.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'greet_every',
			array(
				'label'       => esc_html__( 'Says hi every (seconds)', 'avix-widgets' ),
				'description' => esc_html__( 'Roughly; it varies a little so it feels alive. 0 = only once, when the hero appears.', 'avix-widgets' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 60,
				'step'        => 1,
				'default'     => 7,
				'condition'   => array( 'show_pal' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'pal_size',
			array(
				'label'       => esc_html__( 'Character size', 'avix-widgets' ),
				'description' => esc_html__( 'Leave empty to scale with the visual.', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 20, 'max' => 90 ) ),
				'selectors'   => array( '{{WRAPPER}} .avix-ph' => '--ph-pal-w: {{SIZE}}{{UNIT}};' ),
				'condition'   => array( 'show_pal' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_schema() {
		$this->start_controls_section( 'section_schema', array( 'label' => esc_html__( 'Structured data', 'avix-widgets' ) ) );

		$this->add_control(
			'service_schema',
			array(
				'label'       => esc_html__( 'Service schema', 'avix-widgets' ),
				'description' => esc_html__( 'Describes this page as a service offered by your organisation (the site\'s #organization, as Yoast outputs it). With Yoast SEO it becomes part of Yoast\'s schema graph, as the page\'s main entity; without Yoast it prints as its own JSON-LD block. Turn it on for service pages only (once per page, not in the editor). Country names in "Areas served" are marked as countries.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
			)
		);

		$this->add_control(
			'service_name',
			array(
				'label'       => esc_html__( 'Service name', 'avix-widgets' ),
				'description' => esc_html__( 'Leave empty to use the headline without the [brackets].', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'service_schema' => 'yes' ),
			)
		);

		$this->add_control(
			'service_type',
			array(
				'label'       => esc_html__( 'Service type', 'avix-widgets' ),
				'description' => esc_html__( 'The kind of service in a few words, e.g. “Web development”.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'service_schema' => 'yes' ),
			)
		);

		$this->add_control(
			'service_description',
			array(
				'label'       => esc_html__( 'Description', 'avix-widgets' ),
				'description' => esc_html__( 'Leave empty to use the lead text.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 3,
				'default'     => '',
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'service_schema' => 'yes' ),
			)
		);

		$this->add_control(
			'area_served',
			array(
				'label'       => esc_html__( 'Areas served', 'avix-widgets' ),
				'description' => esc_html__( 'One place per line.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 3,
				'default'     => "European Union\nUnited Kingdom\nUnited States",
				'condition'   => array( 'service_schema' => 'yes' ),
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
				'description' => esc_html__( 'Dark on an inset stage: pair it with the Light Smart Header (the page around the card stays white). Dark with Full width stage: use the Dark header.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'light',
				'options'     => array(
					'light' => esc_html__( 'Light (warm white)', 'avix-widgets' ),
					'dark'  => esc_html__( 'Dark', 'avix-widgets' ),
				),
			)
		);

		$this->add_control(
			'align',
			array(
				'label'       => esc_html__( 'Text alignment', 'avix-widgets' ),
				'description' => esc_html__( 'For the text-only layout (Right side: None).', 'avix-widgets' ),
				'type'        => Controls_Manager::CHOOSE,
				'default'     => 'left',
				'toggle'      => false,
				'options'     => array(
					'left'   => array(
						'title' => esc_html__( 'Left', 'avix-widgets' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => esc_html__( 'Centre', 'avix-widgets' ),
						'icon'  => 'eicon-text-align-center',
					),
				),
				'condition'   => array( 'visual' => 'none' ),
			)
		);

		$this->add_control(
			'clear_header',
			array(
				'label'       => esc_html__( 'Clear the fixed header', 'avix-widgets' ),
				'description' => esc_html__( 'Adds the Smart Header’s height to the top spacing, so the hero starts below the menu. Turn off if this hero is not at the top of the page.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_responsive_control(
			'header_gap',
			array(
				'label'       => esc_html__( 'Gap under the header', 'avix-widgets' ),
				'description' => esc_html__( 'Space between the menu and the top of the stage. Leave empty for 24px (12px on phones).', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
				'selectors'   => array( '{{WRAPPER}} .avix-ph' => '--ph-header-gap: {{SIZE}}{{UNIT}};' ),
				'condition'   => array(
					'clear_header' => 'yes',
					'full_bleed!'  => 'yes',
				),
			)
		);

		$this->add_control(
			'full_bleed',
			array(
				'label'       => esc_html__( 'Full width stage', 'avix-widgets' ),
				'description' => esc_html__( 'Off: a rounded card inset from the screen edges. On: edge to edge, square corners.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
			)
		);

		$this->add_control(
			'show_grid',
			array(
				'label'   => esc_html__( 'Pixel grid pattern', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'show_glow',
			array(
				'label'   => esc_html__( 'Orange glow', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$colors = array(
			'bg'           => array( esc_html__( 'Page background (around the stage)', 'avix-widgets' ), '--ph-bg' ),
			'stage'        => array( esc_html__( 'Stage background', 'avix-widgets' ), '--ph-stage' ),
			'ink'          => array( esc_html__( 'Headline', 'avix-widgets' ), '--ph-ink' ),
			'muted'        => array( esc_html__( 'Text', 'avix-widgets' ), '--ph-muted' ),
			'accent'       => array( esc_html__( 'Accent', 'avix-widgets' ), '--ph-accent' ),
			'accent_text'  => array( esc_html__( 'Small orange text', 'avix-widgets' ), '--ph-accent-text' ),
			'accent_title' => array( esc_html__( 'Headline [highlight]', 'avix-widgets' ), '--ph-accent-title' ),
			'line'         => array( esc_html__( 'Lines', 'avix-widgets' ), '--ph-line' ),
			'grid_color'   => array( esc_html__( 'Grid pattern', 'avix-widgets' ), '--ph-grid-color' ),
		);
		foreach ( $colors as $key => $color ) {
			$this->add_control(
				'color_' . $key,
				array(
					'label'     => $color[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-ph' => $color[1] . ': {{VALUE}};' ),
					'separator' => 'bg' === $key ? 'before' : '',
				)
			);
		}

		$this->add_responsive_control(
			'grid_size',
			array(
				'label'       => esc_html__( 'Grid pixel size', 'avix-widgets' ),
				'description' => esc_html__( 'The small squares; every fourth line is a little stronger. Leave empty for 8px.', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 4, 'max' => 40 ) ),
				'selectors'   => array( '{{WRAPPER}} .avix-ph' => '--ph-grid: {{SIZE}}{{UNIT}};' ),
				'condition'   => array( 'show_grid' => 'yes' ),
				'separator'   => 'before',
			)
		);

		$this->add_responsive_control(
			'padding',
			array(
				'label'              => esc_html__( 'Stage padding', 'avix-widgets' ),
				'type'               => Controls_Manager::DIMENSIONS,
				'size_units'         => array( 'px', 'vh' ),
				'allowed_dimensions' => 'vertical',
				'selectors'          => array( '{{WRAPPER}} .avix-ph' => '--ph-pad-top: {{TOP}}{{UNIT}}; --ph-pad-bottom: {{BOTTOM}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'side_gutter',
			array(
				'label'       => esc_html__( 'Side margins', 'avix-widgets' ),
				'description' => esc_html__( 'Line up with the header: the headline starts on the header logo\'s line at every width, like the other sections set the same way.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => array(
					''       => esc_html__( 'Standard', 'avix-widgets' ),
					'header' => esc_html__( 'Line up with the header', 'avix-widgets' ),
				),
				'separator'   => 'before',
			)
		);

		$this->add_responsive_control(
			'inset',
			array(
				'label'      => esc_html__( 'Space around the stage', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 64 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-ph' => '--ph-inset: {{SIZE}}{{UNIT}};' ),
				'condition'  => array( 'full_bleed!' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'radius',
			array(
				'label'      => esc_html__( 'Stage corner radius', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 64 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-ph' => '--ph-radius: {{SIZE}}{{UNIT}};' ),
				'condition'  => array( 'full_bleed!' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'max_width',
			array(
				'label'      => esc_html__( 'Content width', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 760, 'max' => 1600 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-ph' => '--ph-max: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'min_height',
			array(
				'label'      => esc_html__( 'Minimum stage height', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vh' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 1200 ),
					'vh' => array( 'min' => 0, 'max' => 100 ),
				),
				'selectors'  => array( '{{WRAPPER}} .avix-ph' => '--ph-min-h: {{SIZE}}{{UNIT}};' ),
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
			'title'       => array( esc_html__( 'Headline', 'avix-widgets' ), '.avix-ph__title' ),
			'text'        => array( esc_html__( 'Lead text', 'avix-widgets' ), '.avix-ph__lead' ),
			'eyebrow'     => array( esc_html__( 'Eyebrow', 'avix-widgets' ), '.avix-ph__eyebrow' ),
			'crumbs'      => array( esc_html__( 'Breadcrumb', 'avix-widgets' ), '.avix-ph__crumbs' ),
			'button'      => array( esc_html__( 'Buttons', 'avix-widgets' ), '.avix-ph__btn' ),
			'proof_value' => array( esc_html__( 'Proof values', 'avix-widgets' ), '.avix-ph__proof-value' ),
			'proof_label' => array( esc_html__( 'Proof labels', 'avix-widgets' ), '.avix-ph__proof-label' ),
		);
		foreach ( $groups as $key => $group ) {
			$this->add_group_control(
				Group_Control_Typography::get_type(),
				array(
					'name'     => $key . '_typography',
					'label'    => $group[0],
					'selector' => '{{WRAPPER}} .avix-ph ' . $group[1],
				)
			);
		}

		$this->add_control(
			'buttons_heading',
			array(
				'label'     => esc_html__( 'Button colours', 'avix-widgets' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$buttons = array(
			'btn_bg'        => array( esc_html__( 'Main button', 'avix-widgets' ), '--ph-btn-bg' ),
			'btn_ink'       => array( esc_html__( 'Main button text', 'avix-widgets' ), '--ph-btn-ink' ),
			'btn_hover'     => array( esc_html__( 'Main button hover', 'avix-widgets' ), '--ph-btn-hover' ),
			'btn2_ink'      => array( esc_html__( 'Second button text', 'avix-widgets' ), '--ph-btn2-ink' ),
			'btn2_line'     => array( esc_html__( 'Second button border', 'avix-widgets' ), '--ph-btn2-line' ),
		);
		foreach ( $buttons as $key => $color ) {
			$this->add_control(
				$key,
				array(
					'label'     => $color[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-ph' => $color[1] . ': {{VALUE}};' ),
				)
			);
		}

		$this->add_control(
			'stat_heading',
			array(
				'label'     => esc_html__( 'Stat chip on the image', 'avix-widgets' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array(
					'visual'          => 'image',
					'show_image_chip' => 'yes',
				),
			)
		);

		$stat_type = array(
			'stat_value' => array( esc_html__( 'Chip value', 'avix-widgets' ), '.avix-ph__stat-value' ),
			'stat_label' => array( esc_html__( 'Chip label', 'avix-widgets' ), '.avix-ph__stat-label' ),
		);
		foreach ( $stat_type as $key => $group ) {
			$this->add_group_control(
				Group_Control_Typography::get_type(),
				array(
					'name'      => $key . '_typography',
					'label'     => $group[0],
					'selector'  => '{{WRAPPER}} .avix-ph ' . $group[1],
					'condition' => array(
						'visual'          => 'image',
						'show_image_chip' => 'yes',
					),
				)
			);
		}

		$stat_colors = array(
			'stat_bg'    => array( esc_html__( 'Chip background', 'avix-widgets' ), '--ph-stat-bg' ),
			'stat_ink'   => array( esc_html__( 'Chip value', 'avix-widgets' ), '--ph-stat-ink' ),
			'stat_muted' => array( esc_html__( 'Chip label', 'avix-widgets' ), '--ph-stat-muted' ),
			'stat_seal'  => array( esc_html__( 'Seal', 'avix-widgets' ), '--ph-stat-seal' ),
			'stat_check' => array( esc_html__( 'Seal check', 'avix-widgets' ), '--ph-stat-check' ),
		);
		foreach ( $stat_colors as $key => $color ) {
			$this->add_control(
				$key,
				array(
					'label'     => $color[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-ph' => $color[1] . ': {{VALUE}};' ),
					'condition' => array(
						'visual'          => 'image',
						'show_image_chip' => 'yes',
					),
				)
			);
		}

		$this->end_controls_section();
	}

	private function controls_orbit_style() {
		$this->start_controls_section(
			'style_orbit',
			array(
				'label'     => esc_html__( 'Orbit', 'avix-widgets' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'visual' => 'orbit' ),
			)
		);

		$this->add_responsive_control(
			'orbit_size',
			array(
				'label'      => esc_html__( 'Orbit size', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 240, 'max' => 720 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-ph' => '--ph-orbit-max: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'chip_size',
			array(
				'label'      => esc_html__( 'Chip size', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 32, 'max' => 88 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-ph' => '--ph-chip: {{SIZE}}{{UNIT}};' ),
			)
		);

		$colors = array(
			'chip_bg'   => array( esc_html__( 'Chips', 'avix-widgets' ), '--ph-chip-bg' ),
			'chip_line' => array( esc_html__( 'Chip border', 'avix-widgets' ), '--ph-chip-line' ),
			'ring'      => array( esc_html__( 'Rings', 'avix-widgets' ), '--ph-ring' ),
			'hub_bg'    => array( esc_html__( 'Centre hub', 'avix-widgets' ), '--ph-hub-bg' ),
			'tip_bg'    => array( esc_html__( 'Name tag', 'avix-widgets' ), '--ph-tip-bg' ),
			'tip_ink'   => array( esc_html__( 'Name tag text', 'avix-widgets' ), '--ph-tip-ink' ),
		);
		foreach ( $colors as $key => $color ) {
			$this->add_control(
				$key,
				array(
					'label'     => $color[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-ph' => $color[1] . ': {{VALUE}};' ),
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
		$html  = preg_replace( '/\[([^\[\]]+)\]/u', '<span class="avix-ph__accent">$1</span>', $html );
		$lines = preg_split( '/\r\n|\r|\n/', (string) $html );
		return implode( ' <br class="avix-ph__break">', array_map( 'trim', $lines ) );
	}

	/**
	 * Arrow icons: 'up' = ↗, 'right' = →, 'down' = ↓.
	 *
	 * @param string $dir Direction.
	 */
	private function arrow( $dir = 'up' ) {
		$paths = array(
			'up'    => 'M7 17 17 7M7 7h10v10',
			'right' => 'M5 12h14M12 5l7 7-7 7',
			'down'  => 'M12 5v14M5 12l7 7 7-7',
		);
		return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="' . esc_attr( $paths[ $dir ] ?? $paths['up'] ) . '"/></svg>';
	}

	/**
	 * The current page's name for the breadcrumb.
	 *
	 * @param array $s Settings.
	 */
	private function current_label( array $s ) {
		$label = trim( (string) ( $s['breadcrumb_current'] ?? '' ) );
		if ( '' !== $label ) {
			return $label;
		}
		// The queried object's own name: a post, the blog page, a term or an archive.
		if ( is_singular() ) {
			return wp_strip_all_tags( get_the_title( get_queried_object_id() ) );
		}
		if ( is_home() && get_option( 'page_for_posts' ) ) {
			return wp_strip_all_tags( get_the_title( (int) get_option( 'page_for_posts' ) ) );
		}
		if ( is_category() || is_tag() || is_tax() ) {
			return wp_strip_all_tags( (string) single_term_title( '', false ) );
		}
		if ( is_search() ) {
			return esc_html__( 'Search results', 'avix-widgets' );
		}
		if ( is_archive() ) {
			return wp_strip_all_tags( (string) get_the_archive_title() );
		}
		// Editor preview and template contexts: the document being edited.
		$id = get_the_ID();
		return $id && ! is_404() ? wp_strip_all_tags( get_the_title( $id ) ) : '';
	}

	/**
	 * A chip's accessible name: its Name, else the brand's name, else the
	 * uploaded logo's alt text or title.
	 *
	 * @param array $chip Repeater row.
	 */
	private function chip_name( array $chip ) {
		$label = trim( (string) ( $chip['label'] ?? '' ) );
		if ( '' !== $label ) {
			return $label;
		}
		$icon = (string) ( $chip['icon'] ?? '' );
		if ( 'upload' === $icon ) {
			$id = $this->media_id( (array) ( $chip['logo'] ?? array() ) );
			if ( $id ) {
				$alt = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
				return '' !== $alt ? $alt : trim( wp_strip_all_tags( get_the_title( $id ) ) );
			}
			return '';
		}
		$names = Brand_Icons::options();
		return isset( $names[ $icon ] ) ? wp_strip_all_tags( (string) $names[ $icon ] ) : '';
	}

	/**
	 * Logo inside a chip: a brand mark, or an uploaded image.
	 *
	 * @param array $chip Repeater row.
	 */
	private function chip_logo( array $chip ) {
		$icon = (string) ( $chip['icon'] ?? 'code' );
		if ( 'upload' === $icon ) {
			$media = (array) ( $chip['logo'] ?? array() );
			$id    = $this->media_id( $media );
			if ( $id ) {
				// A proportional size (never the hard-cropped thumbnail), so a
				// wordmark shows whole; SVGs have no generated sizes.
				return wp_get_attachment_image(
					$id,
					'image/svg+xml' === get_post_mime_type( $id ) ? 'full' : 'medium',
					false,
					array(
						'class'    => 'avix-ph__chip-img',
						'alt'      => '',
						'loading'  => 'lazy',
						'decoding' => 'async',
						'sizes'    => '64px',
					)
				);
			}
			if ( ! empty( $media['url'] ) ) {
				return sprintf( '<img class="avix-ph__chip-img" src="%s" alt="" loading="lazy" decoding="async">', esc_url( $media['url'] ) );
			}
			$icon = 'code';
		}
		$svg = Brand_Icons::svg( $icon );
		return '' !== $svg ? $svg : Brand_Icons::svg( 'code' );
	}

	/**
	 * Places the chips, keeping repeater order. When every row is "Auto", all
	 * chips share one even spacing and alternate outer / inner. When a ring
	 * is forced, each ring spreads its chips evenly and the inner ring's start
	 * angle is chosen to sit as far as possible from the outer chips. Both
	 * rings turn together, so these gaps never change.
	 *
	 * Each slot also gets a one-ring angle (all chips evenly on the outer
	 * ring), used by the "Always visible" name pills on wide orbits.
	 *
	 * @param array $chips Filtered repeater rows.
	 * @return array List of array( 'chip', 'ring', 'angle', 'single' ).
	 */
	private function distribute( array $chips ) {
		$total = count( $chips );

		// All automatic (the default): one even spacing for every chip in
		// repeater order, alternating outer / inner, so the orbit never clumps
		// and never leaves a half empty.
		$forced = false;
		foreach ( $chips as $chip ) {
			if ( in_array( (string) ( $chip['ring'] ?? 'auto' ), array( 'outer', 'inner' ), true ) ) {
				$forced = true;
				break;
			}
		}
		if ( ! $forced ) {
			$slots = array();
			foreach ( $chips as $k => $chip ) {
				$angle   = -90 + $k * 360 / max( 1, $total );
				$slots[] = array(
					'chip'   => $chip,
					'ring'   => ( 0 === $k % 2 || 1 === $total ) ? 'outer' : 'inner',
					'angle'  => $angle,
					'single' => $angle,
				);
			}
			return $slots;
		}

		$rings = array();
		$count = array(
			'outer' => 0,
			'inner' => 0,
		);
		$auto  = 0;
		foreach ( $chips as $i => $chip ) {
			$ring = (string) ( $chip['ring'] ?? 'auto' );
			if ( ! isset( $count[ $ring ] ) ) {
				$ring = 0 === $auto % 2 ? 'outer' : 'inner';
				++$auto;
			}
			$rings[ $i ] = $ring;
			++$count[ $ring ];
		}

		$outer_start = -126;
		$outer       = array();
		for ( $k = 0; $k < $count['outer']; $k++ ) {
			$outer[] = $outer_start + $k * 360 / $count['outer'];
		}

		// Inner start: the angle whose chips keep the widest gap to the outer ones.
		$inner_start = -18;
		if ( $count['inner'] && $outer ) {
			$best = -INF;
			$pick = $inner_start;
			for ( $s = -180; $s < 180; $s += 1 ) {
				$min = 360;
				for ( $k = 0; $k < $count['inner']; $k++ ) {
					$a = $s + $k * 360 / $count['inner'];
					foreach ( $outer as $o ) {
						$d   = fmod( abs( $a - $o ), 360 );
						$min = min( $min, $d > 180 ? 360 - $d : $d );
					}
				}
				$score = $min * 1000 - abs( $s - $inner_start );
				if ( $score > $best ) {
					$best = $score;
					$pick = $s;
				}
			}
			$inner_start = $pick;
		}

		$seen = array(
			'outer' => 0,
			'inner' => 0,
		);
		$slots = array();
		foreach ( $chips as $i => $chip ) {
			$ring    = $rings[ $i ];
			$k       = $seen[ $ring ]++;
			$start   = 'outer' === $ring ? $outer_start : $inner_start;
			$slots[] = array(
				'chip'   => $chip,
				'ring'   => $ring,
				'angle'  => $start + $k * 360 / $count[ $ring ],
				'single' => -90 + count( $slots ) * 360 / max( 1, $total ),
			);
		}
		return $slots;
	}

	/**
	 * One chip on a ring.
	 *
	 * @param array $slot  From distribute().
	 * @param int   $index Repeater index (stagger).
	 */
	private function render_chip( array $slot, $index ) {
		$chip  = $slot['chip'];
		$key   = 'chip_' . $index;
		$label = $this->chip_name( $chip );
		$this->add_render_attribute(
			$key,
			array(
				'class'        => 'avix-ph__chip',
				'data-ph-chip' => '',
			)
		);
		// Decided on the escaped URL: a stripped scheme (javascript:) must not
		// leave an <a href=""> that reloads the page.
		if ( '' !== esc_url( (string) ( $chip['link']['url'] ?? '' ) ) ) {
			$tag = 'a';
			$this->add_link_attributes( $key, $chip['link'] );
		} elseif ( '' !== $label ) {
			// No link: still a focusable control, so keyboard users can show its name.
			$tag = 'button';
			$this->add_render_attribute( $key, 'type', 'button' );
		} else {
			$tag = 'span';
		}

		printf(
			'<li class="avix-ph__slot avix-ph__slot--%1$s elementor-repeater-item-%2$s" style="--a2:%3$sdeg;--a1:%4$sdeg;--i:%5$d;"><span class="avix-ph__spin">',
			esc_attr( $slot['ring'] ),
			esc_attr( sanitize_html_class( (string) ( $chip['_id'] ?? '' ) ) ),
			esc_attr( (string) round( $slot['angle'], 2 ) ),
			esc_attr( (string) round( $slot['single'], 2 ) ),
			(int) $index
		);
		echo '<' . $tag . ' ' . $this->get_render_attribute_string( $key ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed tag, attributes escaped by Elementor.
		echo '<span class="avix-ph__chip-logo" aria-hidden="true">' . $this->chip_logo( $chip ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted brand SVG or wp_get_attachment_image().
		echo '<span class="avix-ph__tip">' . esc_html( $label ) . '</span>';
		echo '</' . $tag . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed tag.
		echo '</span></li>';
	}

	/**
	 * The pixel character, wrapped so it can drop in without fighting the
	 * component's own transforms.
	 */
	private function pal_html() {
		return '<span class="avix-ph__perch" aria-hidden="true">'
			. \AvixWidgets\Pixel_Pal::render(
				array(
					'class' => 'avix-ph__pal',
					'hi'    => true,
				)
			)
			. '</span>';
	}

	/* ------------------------------------------------------------------ */
	/* Render                                                              */
	/* ------------------------------------------------------------------ */

	protected function render(): void {
		$s        = $this->get_settings_for_display();
		$visual   = in_array( $s['visual'] ?? 'orbit', array( 'orbit', 'image', 'none' ), true ) ? $s['visual'] : 'orbit';
		$show_pal = 'yes' === ( $s['show_pal'] ?? '' ) && 'none' !== $visual;
		$rotate   = 'orbit' === $visual && 'yes' === ( $s['rotate'] ?? '' );
		$title    = trim( (string) ( $s['title'] ?? '' ) );
		$tag      = Utils::validate_html_tag( $s['title_tag'] ?? 'h1' );
		$title_id = 'avix-ph-title-' . $this->get_id();

		$proof = array();
		if ( 'yes' === ( $s['show_proof'] ?? '' ) ) {
			$proof = array_values(
				array_filter(
					(array) ( $s['proof'] ?? array() ),
					static function ( $row ) {
						return '' !== trim( (string) ( $row['value'] ?? '' ) ) || '' !== trim( (string) ( $row['label'] ?? '' ) );
					}
				)
			);
		}

		// A row shows when it has a name, a brand logo, or an uploaded logo.
		$chips = array_values(
			array_filter(
				(array) ( $s['chips'] ?? array() ),
				function ( $row ) {
					if ( '' !== trim( (string) ( $row['label'] ?? '' ) ) || 'upload' !== ( $row['icon'] ?? '' ) ) {
						return true;
					}
					$logo = (array) ( $row['logo'] ?? array() );
					return $this->media_id( $logo ) || ! empty( $logo['url'] );
				}
			)
		);

		// Image visual without an image: the live page falls back to text
		// only; the editor keeps a placeholder frame to show where it goes.
		$image_html = '';
		if ( 'image' === $visual ) {
			$image_html = $this->image_html( $s );
			if ( '' === $image_html && ! $this->is_editor() ) {
				$visual   = 'none';
				$show_pal = false;
			}
		}

		$classes = array(
			'avix-ph',
			'avix-ph--' . ( 'dark' === ( $s['theme'] ?? '' ) ? 'dark' : 'light' ),
			'avix-ph--visual-' . $visual,
		);
		if ( 'yes' === ( $s['clear_header'] ?? '' ) ) {
			$classes[] = 'avix-ph--clear';
		}
		if ( 'yes' === ( $s['full_bleed'] ?? '' ) ) {
			$classes[] = 'avix-ph--bleed';
		}
		if ( 'header' === ( $s['side_gutter'] ?? '' ) ) {
			$classes[] = 'avix-ph--edge-header';
		}
		if ( 'yes' !== ( $s['show_grid'] ?? '' ) ) {
			$classes[] = 'avix-ph--no-grid';
		}
		if ( 'yes' !== ( $s['show_glow'] ?? '' ) ) {
			$classes[] = 'avix-ph--no-glow';
		}
		if ( $rotate ) {
			$classes[] = 'avix-ph--rotate';
		}
		// Name pills need room on the ring: past 8 chips they would touch, so
		// the orbit keeps logo chips with name tags instead.
		if ( 'always' === ( $s['chip_labels'] ?? '' ) && count( $chips ) <= 8 ) {
			$classes[] = 'avix-ph--labels';
		}
		if ( ! $show_pal ) {
			$classes[] = 'avix-ph--no-pal';
		}
		if ( 'none' === $visual && 'center' === ( $s['align'] ?? '' ) ) {
			$classes[] = 'avix-ph--center';
		}
		$has_copy = '' !== $title
			|| '' !== trim( (string) ( $s['eyebrow'] ?? '' ) )
			|| '' !== trim( (string) ( $s['text'] ?? '' ) )
			|| '' !== trim( (string) ( $s['button_text'] ?? '' ) )
			|| ( 'yes' === ( $s['show_button_2'] ?? '' ) && '' !== trim( (string) ( $s['button_2_text'] ?? '' ) ) )
			|| ( 'yes' === ( $s['show_breadcrumb'] ?? '' ) )
			|| $proof;
		if ( ! $has_copy ) {
			$classes[] = 'avix-ph--no-copy';
		}

		$greet  = max( 0, min( 60, (int) ( $s['greet_every'] ?? 7 ) ) );
		$config = array(
			'pal'   => $show_pal,
			'greet' => $greet,
		);

		$this->add_render_attribute(
			'root',
			array(
				'class'        => $classes,
				'data-avix-ph' => wp_json_encode( $config ),
			)
		);
		if ( '' !== $title ) {
			$this->add_render_attribute( 'root', 'aria-labelledby', $title_id );
		} else {
			$this->add_render_attribute( 'root', 'aria-label', '' !== trim( (string) ( $s['eyebrow'] ?? '' ) ) ? trim( (string) $s['eyebrow'] ) : esc_html__( 'Introduction', 'avix-widgets' ) );
		}
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="avix-ph__stage">
				<div class="avix-ph__inner">
					<?php if ( $has_copy ) : ?>
					<div class="avix-ph__copy">
						<?php $this->render_crumbs( $s ); ?>
						<?php if ( '' !== trim( (string) ( $s['eyebrow'] ?? '' ) ) ) : ?>
							<p class="avix-ph__eyebrow avix-ph__rise" style="--i:1;"><span class="avix-ph__px" aria-hidden="true"></span><?php echo esc_html( $s['eyebrow'] ); ?></p>
						<?php endif; ?>
						<?php if ( '' !== $title ) : ?>
							<<?php echo esc_html( $tag ); ?> id="<?php echo esc_attr( $title_id ); ?>" class="avix-ph__title avix-ph__rise" style="--i:2;"><?php echo $this->accent_html( $title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in accent_html(). ?></<?php echo esc_html( $tag ); ?>>
						<?php endif; ?>
						<?php if ( '' !== trim( (string) ( $s['text'] ?? '' ) ) ) : ?>
							<p class="avix-ph__lead avix-ph__rise" style="--i:3;"><?php echo $this->accent_html( $s['text'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in accent_html(). ?></p>
						<?php endif; ?>
						<?php $this->render_buttons( $s ); ?>
						<?php $this->render_proof( $proof ); ?>
					</div>
					<?php endif; ?>
					<?php
					if ( 'orbit' === $visual ) {
						$this->render_orbit( $s, $chips, $show_pal );
					} elseif ( 'image' === $visual ) {
						// The pause button sits on the image's corner, not under it.
						// "Product render, dark" is the render frame plus a glow class.
						$frame = (string) ( $s['image_frame'] ?? '' );
						ob_start();
						$this->render_pause( $s, $show_pal && $greet > 0 );
						$this->render_image( $image_html, $show_pal, (string) ob_get_clean(), $this->stat_html( $s ), in_array( $frame, array( 'render', 'glow' ), true ), 'glow' === $frame );
					}
					?>
				</div>
				<?php
				if ( 'orbit' === $visual ) {
					$this->render_pause( $s, $rotate || ( $show_pal && $greet > 0 ) );
				}
				?>
			</div>
		</section>
		<?php
		if ( 'yes' === ( $s['service_schema'] ?? '' ) && ! $this->is_editor() ) {
			$this->print_schema( $s, $title );
		}
	}

	/**
	 * schema.org Service for this page, provided by the site's Organization
	 * (the @id Yoast gives it), so search engines tie the two together.
	 * With Yoast SEO the Service is a node of Yoast's graph, printed in the
	 * head with WebPage.mainEntity → #service (includes/seo/class-service-piece.php),
	 * and nothing is printed here. Otherwise the same Service prints as one
	 * free-standing block. Once per page: a second hero adds nothing.
	 *
	 * @param array  $s     Settings.
	 * @param string $title Headline.
	 */
	private function print_schema( array $s, $title ) {
		static $printed = false;
		// A Service describes one page: on archives the queried object is a
		// term or user, whose ID would give the wrong permalink.
		if ( $printed || ! is_singular() || ! class_exists( '\AvixWidgets\SEO\Service_Piece' ) ) {
			return;
		}
		if ( \AvixWidgets\SEO\Service_Piece::in_graph() ) {
			$printed = true;
			return;
		}
		$url = get_permalink( get_queried_object_id() );
		if ( ! $url ) {
			return;
		}
		$service = \AvixWidgets\SEO\Service_Piece::build( array_merge( $s, array( 'title' => $title ) ), (string) $url, false );
		if ( ! $service ) {
			return;
		}
		$printed = true;
		$json    = wp_json_encode( array( '@context' => 'https://schema.org' ) + $service, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP );
		if ( $json ) {
			echo '<script type="application/ld+json">' . $json . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON with < > & hex-escaped.
		}
	}

	/**
	 * The pause button sits in the stage's bottom corner, clear of the rings.
	 * It is a plain button whose label says what it will do (no aria-pressed,
	 * so the name and the state never contradict each other).
	 *
	 * @param array $s      Settings.
	 * @param bool  $moving Something loops: the rings turn or the character greets.
	 */
	private function render_pause( array $s, $moving ) {
		if ( ! $moving || 'yes' !== ( $s['show_pause'] ?? '' ) ) {
			return;
		}
		$pause = trim( (string) ( $s['pause_text'] ?? '' ) );
		$play  = trim( (string) ( $s['play_text'] ?? '' ) );
		$pause = '' !== $pause ? $pause : esc_html__( 'Pause animation', 'avix-widgets' );
		$play  = '' !== $play ? $play : esc_html__( 'Play animation', 'avix-widgets' );
		?>
		<button type="button" class="avix-ph__pause" data-ph-pause aria-label="<?php echo esc_attr( $pause ); ?>" data-pause="<?php echo esc_attr( $pause ); ?>" data-play="<?php echo esc_attr( $play ); ?>">
			<svg class="avix-ph__pause-i" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="7" y="6" width="3.2" height="12" rx="1"/><rect x="13.8" y="6" width="3.2" height="12" rx="1"/></svg>
			<svg class="avix-ph__play-i" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 5.8v12.4a.8.8 0 0 0 1.2.7l9.7-6.2a.8.8 0 0 0 0-1.4L9.2 5.1a.8.8 0 0 0-1.2.7z"/></svg>
			<span class="avix-ph__pause-text" aria-hidden="true" data-ph-pause-text><?php echo esc_html( $pause ); ?></span>
		</button>
		<?php
	}

	/**
	 * @param array $s Settings.
	 */
	private function render_crumbs( array $s ) {
		if ( 'yes' !== ( $s['show_breadcrumb'] ?? '' ) ) {
			return;
		}
		$home    = trim( (string) ( $s['breadcrumb_home'] ?? '' ) );
		$parent  = trim( (string) ( $s['breadcrumb_parent'] ?? '' ) );
		$current = $this->current_label( $s );
		if ( '' === $home && '' === $parent && '' === $current ) {
			return;
		}
		?>
		<nav class="avix-ph__crumbs avix-ph__rise" style="--i:0;" aria-label="<?php echo esc_attr__( 'Breadcrumb', 'avix-widgets' ); ?>">
			<ol class="avix-ph__crumb-list">
				<?php if ( '' !== $home ) : ?>
					<li class="avix-ph__crumb"><a class="avix-ph__crumb-link" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( $home ); ?></a></li>
				<?php endif; ?>
				<?php
				// The optional middle step prints on this line, so a trail
				// without one keeps exactly the markup it had before.
				$this->render_crumb_parent( $s, $parent, '' !== $home );
				if ( '' !== $current ) :
					?>
					<li class="avix-ph__crumb" aria-current="page">
						<?php if ( '' !== $home || '' !== $parent ) : ?>
							<svg class="avix-ph__crumb-sep" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m9 6 6 6-6 6"/></svg>
						<?php endif; ?>
						<span class="avix-ph__crumb-text" title="<?php echo esc_attr( $current ); ?>"><?php echo esc_html( $current ); ?></span>
					</li>
				<?php endif; ?>
			</ol>
		</nav>
		<?php
	}

	/**
	 * The middle step of a three-level trail (Home › Parent › Current): a
	 * link when it has a usable URL, plain text otherwise.
	 *
	 * @param array  $s      Settings.
	 * @param string $parent Trimmed label ('' prints nothing).
	 * @param bool   $sep    A step comes before it, so it starts with a separator.
	 */
	private function render_crumb_parent( array $s, $parent, $sep ) {
		if ( '' === $parent ) {
			return;
		}
		$link = (array) ( $s['breadcrumb_parent_link'] ?? array() );
		echo '<li class="avix-ph__crumb avix-ph__crumb--parent">';
		if ( $sep ) {
			echo '<svg class="avix-ph__crumb-sep" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m9 6 6 6-6 6"/></svg>';
		}
		// Decided on the escaped URL, so a stripped scheme leaves plain text.
		if ( '' !== esc_url( (string) ( $link['url'] ?? '' ) ) ) {
			$this->add_render_attribute( 'crumb_parent', 'class', 'avix-ph__crumb-link' );
			$this->add_link_attributes( 'crumb_parent', $link );
			printf(
				'<a %1$s><span class="avix-ph__crumb-text" title="%2$s">%3$s</span></a>',
				$this->get_render_attribute_string( 'crumb_parent' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by Elementor.
				esc_attr( $parent ),
				esc_html( $parent )
			);
		} else {
			printf( '<span class="avix-ph__crumb-text" title="%1$s">%2$s</span>', esc_attr( $parent ), esc_html( $parent ) );
		}
		echo '</li>';
	}

	/**
	 * @param array $s Settings.
	 */
	private function render_buttons( array $s ) {
		$primary   = trim( (string) ( $s['button_text'] ?? '' ) );
		$secondary = 'yes' === ( $s['show_button_2'] ?? '' ) ? trim( (string) ( $s['button_2_text'] ?? '' ) ) : '';
		if ( '' === $primary && '' === $secondary ) {
			return;
		}
		// A button needs a usable link. Without one (or with a stripped
		// scheme) it is left out of the live page; the editor still shows it.
		$editor = $this->is_editor();
		$url_1  = esc_url( (string) ( $s['button_link']['url'] ?? '' ) );
		$url_2  = esc_url( (string) ( $s['button_2_link']['url'] ?? '' ) );
		if ( '' === $url_1 && ! $editor ) {
			$primary = '';
		}
		if ( '' === $url_2 && ! $editor ) {
			$secondary = '';
		}
		if ( '' === $primary && '' === $secondary ) {
			return;
		}
		echo '<div class="avix-ph__actions avix-ph__rise" style="--i:4;">';
		if ( '' !== $primary ) {
			$this->add_render_attribute(
				'btn',
				array(
					'class'      => 'avix-ph__btn avix-ph__btn--primary',
					'data-ph-cta' => '',
				)
			);
			if ( '' !== $url_1 ) {
				$this->add_link_attributes( 'btn', $s['button_link'] );
			}
			printf(
				'<a %1$s><span class="avix-ph__btn-text">%2$s</span><span class="avix-ph__btn-icon" aria-hidden="true">%3$s</span></a>',
				$this->get_render_attribute_string( 'btn' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by Elementor.
				esc_html( $primary ),
				$this->arrow( 'up' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
			);
		}
		if ( '' !== $secondary ) {
			$this->add_render_attribute( 'btn2', 'class', 'avix-ph__btn avix-ph__btn--ghost' );
			$url = (string) ( $s['button_2_link']['url'] ?? '' );
			if ( '' !== $url_2 ) {
				$this->add_link_attributes( 'btn2', $s['button_2_link'] );
			}
			printf(
				'<a %1$s><span class="avix-ph__btn-text">%2$s</span><span class="avix-ph__btn-arrow" aria-hidden="true">%3$s</span></a>',
				$this->get_render_attribute_string( 'btn2' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by Elementor.
				esc_html( $secondary ),
				$this->arrow( 0 === strpos( $url, '#' ) ? 'down' : 'right' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
			);
		}
		echo '</div>';
	}

	/**
	 * @param array $proof Filtered rows.
	 */
	private function render_proof( array $proof ) {
		if ( ! $proof ) {
			return;
		}
		// Four or more stats share a two-up grid on narrower stages.
		printf( '<ul class="avix-ph__proof%s avix-ph__rise" style="--i:5;">', count( $proof ) >= 4 ? ' avix-ph__proof--many' : '' );
		foreach ( $proof as $row ) {
			printf(
				'<li class="avix-ph__proof-item elementor-repeater-item-%1$s"><span class="avix-ph__proof-value">%2$s</span><span class="avix-ph__proof-label">%3$s</span></li>',
				esc_attr( sanitize_html_class( (string) ( $row['_id'] ?? '' ) ) ),
				esc_html( trim( (string) ( $row['value'] ?? '' ) ) ),
				esc_html( trim( (string) ( $row['label'] ?? '' ) ) )
			);
		}
		echo '</ul>';
	}

	/**
	 * @param array $s        Settings.
	 * @param array $chips    Filtered chips.
	 * @param bool  $show_pal Character on the hub.
	 */
	private function render_orbit( array $s, array $chips, $show_pal ) {
		$slots = $this->distribute( $chips );
		$label = trim( (string) ( $s['orbit_label'] ?? '' ) );
		?>
		<div class="avix-ph__visual" data-ph-visual>
			<?php if ( ! $slots && $this->is_editor() ) : ?>
				<div class="elementor-alert elementor-alert-info avix-ph__alert"><?php echo esc_html__( 'Page Hero: add at least one platform chip, or set Right side to Image or None.', 'avix-widgets' ); ?></div>
			<?php endif; ?>
			<div class="avix-ph__orbit" data-ph-orbit role="group"<?php echo '' !== $label ? ' aria-label="' . esc_attr( $label ) . '"' : ''; ?>>
				<span class="avix-ph__halo" aria-hidden="true"></span>
				<span class="avix-ph__ring avix-ph__ring--core" aria-hidden="true"><span class="avix-ph__comet-track"><span class="avix-ph__comet"></span></span></span>
				<span class="avix-ph__ring avix-ph__ring--inner" aria-hidden="true"></span>
				<span class="avix-ph__ring avix-ph__ring--outer" aria-hidden="true"></span>
				<?php
				if ( $slots ) {
					// One list in editor order, turning as a single layer.
					echo '<ul class="avix-ph__chips" data-ph-chips>';
					foreach ( $slots as $index => $slot ) {
						$this->render_chip( $slot, $index );
					}
					echo '</ul>';
				}
				?>
				<div class="avix-ph__hub" data-ph-hub>
					<span class="avix-ph__hub-tile" aria-hidden="true">
						<?php $this->render_hub_logo( $s ); ?>
					</span>
					<?php
					if ( $show_pal ) {
						echo $this->pal_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts in Pixel_Pal::render().
					}
					?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * @param array $s Settings.
	 */
	private function render_hub_logo( array $s ) {
		$centre = (string) ( $s['centre'] ?? 'mark' );
		if ( 'image' === $centre ) {
			$media = (array) ( $s['centre_image'] ?? array() );
			$id    = $this->media_id( $media );
			if ( $id ) {
				echo wp_get_attachment_image(
					$id,
					'medium',
					false,
					array(
						'class'    => 'avix-ph__hub-img',
						'alt'      => '',
						'decoding' => 'async',
						'sizes'    => '100px',
					)
				);
				return;
			}
			if ( ! empty( $media['url'] ) ) {
				printf( '<img class="avix-ph__hub-img" src="%s" alt="" decoding="async">', esc_url( $media['url'] ) );
				return;
			}
			$centre = 'mark';
		}
		if ( 'mark' === $centre ) {
			echo '<svg class="avix-ph__mark" viewBox="0 0 60 60" focusable="false"><path d="' . esc_attr( self::MARK ) . '"/></svg>';
		}
	}

	/**
	 * True inside the Elementor editor preview.
	 */
	private function is_editor() {
		return class_exists( '\\Elementor\\Plugin' ) && \Elementor\Plugin::$instance->editor && \Elementor\Plugin::$instance->editor->is_edit_mode();
	}

	/**
	 * The image for the "Image" visual, or '' when none is set.
	 *
	 * @param array $s Settings.
	 */
	private function image_html( array $s ) {
		$media = (array) ( $s['image'] ?? array() );
		$id    = $this->media_id( $media );
		$eager = 'yes' === ( $s['image_eager'] ?? '' );
		$alt   = trim( (string) ( $s['image_alt'] ?? '' ) );
		if ( '' === $alt && $id ) {
			$alt = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
		}
		if ( '' === $alt ) {
			// Fall back to the headline (or eyebrow), without the [accent] brackets.
			$fallback = trim( (string) ( $s['title'] ?? '' ) );
			$fallback = '' !== $fallback ? $fallback : trim( (string) ( $s['eyebrow'] ?? '' ) );
			$alt      = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( str_replace( array( '[', ']' ), '', $fallback ) ) ) );
		}
		$img = '';
		if ( $id ) {
			$attrs = array(
				'class'    => 'avix-ph__img',
				'alt'      => $alt,
				'decoding' => 'async',
				'loading'  => $eager ? 'eager' : 'lazy',
				'sizes'    => '(max-width: 900px) 92vw, 520px',
			);
			if ( $eager ) {
				$attrs['fetchpriority'] = 'high';
			}
			$img = wp_get_attachment_image( $id, 'large', false, $attrs );
		} elseif ( ! empty( $media['url'] ) ) {
			$img = sprintf(
				'<img class="avix-ph__img" src="%1$s" alt="%2$s" loading="%3$s" decoding="async"%4$s>',
				esc_url( $media['url'] ),
				esc_attr( $alt ),
				$eager ? 'eager' : 'lazy',
				$eager ? ' fetchpriority="high"' : ''
			);
		}
		return $img;
	}

	/**
	 * The stat chip on the image ("$50M+ · Client revenue scaled"), or ''
	 * when it is off or has no text.
	 *
	 * @param array $s Settings.
	 */
	private function stat_html( array $s ) {
		if ( 'yes' !== ( $s['show_image_chip'] ?? '' ) ) {
			return '';
		}
		$value = trim( (string) ( $s['image_chip_value'] ?? '' ) );
		$label = trim( (string) ( $s['image_chip_label'] ?? '' ) );
		if ( '' === $value && '' === $label ) {
			return '';
		}
		$seal = 'yes' === ( $s['image_chip_badge'] ?? '' ) ? $this->seal_svg() : '';
		$html = '<p class="avix-ph__stat' . ( '' !== $seal ? ' has-seal' : '' ) . '" data-ph-stat>';
		if ( '' !== $seal ) {
			$html .= '<span class="avix-ph__stat-seal" aria-hidden="true">' . $seal . '</span>';
		}
		$html .= '<span class="avix-ph__stat-copy">';
		if ( '' !== $value ) {
			$html .= '<span class="avix-ph__stat-value">' . esc_html( $value ) . '</span>';
		}
		if ( '' !== $label ) {
			// "Responsive design · Clear user journeys" sets as two lines, so
			// the dot never hangs at a line end; screen readers still hear it.
			$parts = preg_split( '/\s+·\s+/u', $label );
			$parts = is_array( $parts ) ? $parts : array( $label );
			$html .= '<span class="avix-ph__stat-label">' . implode( '<span class="avix-ph__stat-sep"> · </span>', array_map( 'esc_html', $parts ) ) . '</span>';
		}
		return $html . '</span></p>';
	}

	/**
	 * The approved scalloped seal (assets/images/check-badge.svg, shared with
	 * About Hero) with an orange check laid into its cut-out. Trusted bundled
	 * file only, never user-supplied markup.
	 */
	private function seal_svg() {
		static $svg = null;
		if ( null === $svg ) {
			$file = AVIX_EW_PATH . 'assets/images/check-badge.svg';
			$raw  = is_readable( $file ) ? (string) file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local bundled file.
			$svg  = '';
			if ( false !== strpos( $raw, '</svg>' ) ) {
				$raw = preg_replace( '/\s(class|width|height|data-name)="[^"]*"/', '', $raw, 4 );
				$raw = preg_replace( '/^<svg\b/', '<svg class="avix-ph__seal"', trim( $raw ) );
				$svg = str_replace( '</svg>', '<path class="avix-ph__seal-check" d="M9 12l2 2 4-4"/></svg>', $raw );
			}
		}
		return $svg;
	}

	/**
	 * @param string $img      From image_html(); '' only reaches here in the editor (placeholder frame).
	 * @param bool   $show_pal Character on the image.
	 * @param string $pause    Pause button markup from render_pause(), or ''.
	 * @param string $stat     Stat chip markup from stat_html(), or ''.
	 * @param bool   $render   "Product render" frame (opt-in; Classic adds no class).
	 * @param bool   $glow     "Product render, dark" frame: the render frame plus an orange rim glow.
	 */
	private function render_image( $img, $show_pal, $pause = '', $stat = '', $render = false, $glow = false ) {
		?>
		<div class="avix-ph__visual" data-ph-visual>
			<figure class="avix-ph__media<?php echo $render ? ' avix-ph__media--render' : ''; ?><?php echo $glow ? ' avix-ph__media--glow' : ''; ?><?php echo '' === $img ? ' is-empty' : ''; ?><?php echo $show_pal ? ' has-pal' : ''; ?><?php echo '' !== $stat ? ' has-stat' : ''; ?>" data-ph-hub>
				<span class="avix-ph__media-frame">
					<?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() or escaped above. ?>
				</span>
				<?php
				echo $stat; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in stat_html().
				if ( $show_pal ) {
					echo $this->pal_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts in Pixel_Pal::render().
				}
				echo $pause; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in render_pause().
				?>
			</figure>
		</div>
		<?php
	}
}
