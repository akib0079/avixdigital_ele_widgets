<?php
/**
 * Case Study Hero: the top of every case study, full width with the header's
 * side margins, compact so the copy and the facts share the first screen. A
 * breadcrumb, then the client's logo, an eyebrow and the headline as one
 * lock-up, a lead with the two buttons under it, a facts bar (client,
 * services, platform, year, role, website) and the live site itself: the
 * homepage in a browser frame with the phone overlapping it, or a studio
 * render of the site on devices. Behind it, an animated orange shader that
 * follows the pointer: a mosaic of the brand's pixel squares or a smooth flow
 * (WebGL, with the CSS glow and grid as the fallback).
 * Dark by default; the Light theme re-themes all of it. The lower part of the
 * devices can sit on a white band, so the next section starts under them.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Widgets;

use AvixWidgets\Case_Studies\Case_Study;
use AvixWidgets\Case_Studies\Kit;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;

defined( 'ABSPATH' ) || exit;

class Case_Study_Hero extends Widget_Base {

	use Media;
	use \AvixWidgets\Case_Studies\Source;

	/** Visual modes. `auto` = `image` when a render is set, else `devices`. */
	const VISUALS = array( 'auto', 'devices', 'browser', 'image', 'none' );

	/** Built-in facts in display order: key => default label. */
	const FACTS = array(
		'client'   => 'Client',
		'services' => 'Services',
		'platform' => 'Platform',
		'year'     => 'Year',
		'role'     => 'Role',
		'website'  => 'Website',
		'market'   => 'Market',
		'credits'  => 'Credits',
	);

	/** The LCP desktop screenshot: browser frame at most 1120px wide. */
	const SIZES_DESKTOP = '(max-width: 600px) 92vw, (max-width: 1240px) 90vw, 1120px';

	/** The phone overlapping the browser (22–26% of it). */
	const SIZES_PHONE = '(max-width: 1024px) 26vw, 250px';

	/** The studio render, at most 1240px wide (edge to edge on phones). */
	const SIZES_RENDER = '(max-width: 600px) 100vw, (max-width: 1370px) 92vw, 1240px';

	public function get_name(): string {
		return 'avix-case-study-hero';
	}

	public function get_title(): string {
		return esc_html__( 'Case Study Hero', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-device-desktop';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'avix', 'case study', 'portfolio', 'hero', 'devices', 'facts', 'client', 'breadcrumb', 'shader', 'webgl' );
	}

	public function get_style_depends(): array {
		return array( 'avix-case-study-hero' );
	}

	public function get_script_depends(): array {
		return array( 'avix-case-study-hero' );
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
		$this->controls_visual();
		$this->controls_facts();
		$this->controls_extras();
		$this->controls_style();
		$this->controls_type();
	}

	private function controls_content() {
		$this->start_controls_section( 'section_content', array( 'label' => esc_html__( 'Hero content', 'avix-widgets' ) ) );

		$this->add_control(
			'show_breadcrumb',
			array(
				'label'       => esc_html__( 'Breadcrumb', 'avix-widgets' ),
				'description' => esc_html__( 'Home › Case studies › client. Phones show a short "‹ Case studies" link.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'crumb_parent',
			array(
				'label'       => esc_html__( 'Middle step', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__( 'Case studies', 'avix-widgets' ),
				'description' => esc_html__( 'Empty: the title of the case studies page.', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_breadcrumb' => 'yes' ),
			)
		);

		$this->add_control(
			'crumb_parent_link',
			array(
				'label'       => esc_html__( 'Middle step link', 'avix-widgets' ),
				'type'        => Controls_Manager::URL,
				'default'     => array( 'url' => '' ),
				'placeholder' => esc_html__( 'Empty: the case studies page', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_breadcrumb' => 'yes' ),
			)
		);

		$this->add_control(
			'show_logo',
			array(
				'label'       => esc_html__( 'Client logo', 'avix-widgets' ),
				'description' => esc_html__( 'Above the eyebrow, lined up with the headline. Use a white logo: on the Light theme it is shown dark (Style › Section).', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'logo',
			array(
				'label'       => esc_html__( 'Logo', 'avix-widgets' ),
				'type'        => Controls_Manager::MEDIA,
				'default'     => array(
					'url' => '',
					'id'  => '',
				),
				'description' => esc_html__( 'Empty: the logo saved on the case study.', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_logo' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'logo_height',
			array(
				'label'          => esc_html__( 'Logo height', 'avix-widgets' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => array( 'px' ),
				'range'          => array( 'px' => array( 'min' => 16, 'max' => 64 ) ),
				'description'    => esc_html__( 'Wide wordmarks stop at 240px wide (200px on phones).', 'avix-widgets' ),
				'default'        => array(
					'size' => 28,
					'unit' => 'px',
				),
				'tablet_default' => array(
					'size' => 26,
					'unit' => 'px',
				),
				'mobile_default' => array(
					'size' => 24,
					'unit' => 'px',
				),
				'selectors'      => array( '{{WRAPPER}} .avix-csh' => '--csh-logo-h: {{SIZE}}{{UNIT}};' ),
				'condition'      => array( 'show_logo' => 'yes' ),
			)
		);

		$this->add_control(
			'eyebrow',
			array(
				'label'       => esc_html__( 'Eyebrow', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__( 'Case study · Shopify · Netherlands', 'avix-widgets' ),
				'description' => esc_html__( 'Empty: "Case study", the first service and the market.', 'avix-widgets' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Headline', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => '',
				'placeholder' => esc_html__( 'From the case study', 'avix-widgets' ),
				'description' => esc_html__( 'Wrap words in [brackets] to highlight them in orange. Press Enter for a new line. Empty: the case study\'s display title.', 'avix-widgets' ),
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
					'div' => 'div',
					'p'   => 'p',
				),
			)
		);

		$this->add_control(
			'text',
			array(
				'label'       => esc_html__( 'Lead', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 4,
				'default'     => '',
				'placeholder' => esc_html__( 'From the case study summary', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'button_text',
			array(
				'label'       => esc_html__( 'Button text', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Visit live site', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'button_link',
			array(
				'label'       => esc_html__( 'Button link', 'avix-widgets' ),
				'type'        => Controls_Manager::URL,
				'default'     => array(
					'url'         => '',
					'is_external' => 'on',
				),
				'placeholder' => esc_html__( 'Empty: the live site', 'avix-widgets' ),
				'description' => esc_html__( 'Opens in a new tab.', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'show_button_2',
			array(
				'label'   => esc_html__( 'Second button', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'button_2_text',
			array(
				'label'     => esc_html__( 'Second button text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'See what we built', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_button_2' => 'yes' ),
			)
		);

		$this->add_control(
			'button_2_link',
			array(
				'label'       => esc_html__( 'Second button link', 'avix-widgets' ),
				'type'        => Controls_Manager::URL,
				'default'     => array( 'url' => '#features' ),
				'description' => esc_html__( 'A #anchor scrolls down the page (the "What we built" chapter is #features).', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_button_2' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_visual() {
		$this->start_controls_section( 'section_visual', array( 'label' => esc_html__( 'Devices', 'avix-widgets' ) ) );

		$this->add_control(
			'visual',
			array(
				'label'       => esc_html__( 'Visual', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'auto',
				'options'     => array(
					'auto'    => esc_html__( 'Automatic (render, else devices)', 'avix-widgets' ),
					'devices' => esc_html__( 'Browser + phone screenshots', 'avix-widgets' ),
					'browser' => esc_html__( 'Browser screenshot only', 'avix-widgets' ),
					'image'   => esc_html__( 'Studio render (image)', 'avix-widgets' ),
					'none'    => esc_html__( 'None', 'avix-widgets' ),
				),
				'description' => esc_html__( 'Automatic shows the studio render when the case study has one, otherwise the live homepage in a browser frame with the phone beside it.', 'avix-widgets' ),
			)
		);

		$render_modes = array(
			'name'     => 'visual',
			'operator' => 'in',
			'value'    => array( 'auto', 'image' ),
		);
		$shot_modes   = array(
			'name'     => 'visual',
			'operator' => 'in',
			'value'    => array( 'auto', 'devices', 'browser' ),
		);

		$this->add_control(
			'image_render',
			array(
				'label'       => esc_html__( 'Studio render', 'avix-widgets' ),
				'type'        => Controls_Manager::MEDIA,
				'default'     => array(
					'url' => '',
					'id'  => '',
				),
				'description' => esc_html__( 'A dark render of the live site on devices (3:2). Empty: the render saved on the case study.', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
				'conditions'  => array( 'terms' => array( $render_modes ) ),
			)
		);

		$this->add_control(
			'image_blend',
			array(
				'label'       => esc_html__( 'Blend into the hero', 'avix-widgets' ),
				'description' => esc_html__( 'On: no frame, the render\'s edges fade into the dark background so the devices float. Off: a rounded frame.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'conditions'  => array( 'terms' => array( $render_modes ) ),
			)
		);

		$this->add_control(
			'image_desktop',
			array(
				'label'       => esc_html__( 'Desktop screenshot', 'avix-widgets' ),
				'type'        => Controls_Manager::MEDIA,
				'default'     => array(
					'url' => '',
					'id'  => '',
				),
				'description' => esc_html__( 'Empty: the hero desktop screenshot saved on the case study (16:10).', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
				'separator'   => 'before',
				'conditions'  => array( 'terms' => array( $shot_modes ) ),
			)
		);

		$this->add_control(
			'image_mobile',
			array(
				'label'       => esc_html__( 'Phone screenshot', 'avix-widgets' ),
				'type'        => Controls_Manager::MEDIA,
				'default'     => array(
					'url' => '',
					'id'  => '',
				),
				'description' => esc_html__( 'Empty: the hero phone screenshot saved on the case study. Hidden on phones.', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
				'conditions'  => array(
					'terms' => array(
						array(
							'name'     => 'visual',
							'operator' => 'in',
							'value'    => array( 'auto', 'devices' ),
						),
					),
				),
			)
		);

		$this->add_control(
			'url_label',
			array(
				'label'       => esc_html__( 'Address bar text', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__( 'rehall.com', 'avix-widgets' ),
				'description' => esc_html__( 'Empty: the live site\'s domain.', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
				'conditions'  => array( 'terms' => array( $shot_modes ) ),
			)
		);

		$this->add_control(
			'image_eager',
			array(
				'label'       => esc_html__( 'Load image first', 'avix-widgets' ),
				'description' => esc_html__( 'The hero image is usually the largest thing on screen: loading it first makes the page feel faster. Turn off if this hero is not at the top.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'condition'   => array( 'visual!' => 'none' ),
			)
		);

		$this->add_control(
			'bleed',
			array(
				'label'       => esc_html__( 'Band under the screenshots', 'avix-widgets' ),
				'description' => esc_html__( 'The lower part of the devices sits on a band in the next section\'s colour, so the page flows on under them.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
				'conditions'  => array( 'terms' => array( $shot_modes ) ),
			)
		);

		$this->add_control(
			'image_bleed',
			array(
				'label'       => esc_html__( 'Band under the render', 'avix-widgets' ),
				'description' => esc_html__( 'Off by default: the render ends inside the dark hero. Only with "Blend into the hero" off.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
				'conditions'  => array(
					'terms' => array(
						$render_modes,
						array(
							'name'     => 'image_blend',
							'operator' => '!==',
							'value'    => 'yes',
						),
					),
				),
			)
		);

		$band_on = array(
			'relation' => 'or',
			'terms'    => array(
				array(
					'relation' => 'and',
					'terms'    => array(
						$shot_modes,
						array(
							'name'  => 'bleed',
							'value' => 'yes',
						),
					),
				),
				array(
					'relation' => 'and',
					'terms'    => array(
						$render_modes,
						array(
							'name'  => 'image_bleed',
							'value' => 'yes',
						),
					),
				),
			),
		);

		$this->add_control(
			'bleed_amount',
			array(
				'label'       => esc_html__( 'Band height', 'avix-widgets' ),
				'description' => esc_html__( 'How much of the screenshot sits on the band (15% less on phones).', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%' ),
				'range'       => array( '%' => array( 'min' => 0, 'max' => 30 ) ),
				'default'     => array(
					'size' => 18,
					'unit' => '%',
				),
				'selectors'   => array( '{{WRAPPER}} .avix-csh' => '--csh-bleed: {{SIZE}};' ),
				'conditions'  => $band_on,
			)
		);

		$this->add_control(
			'after_color',
			array(
				'label'       => esc_html__( 'Band colour', 'avix-widgets' ),
				'description' => esc_html__( 'Match the next section\'s background.', 'avix-widgets' ),
				'type'        => Controls_Manager::COLOR,
				'default'     => '#ffffff',
				'selectors'   => array( '{{WRAPPER}} .avix-csh' => '--csh-after: {{VALUE}};' ),
				'conditions'  => $band_on,
			)
		);

		$this->end_controls_section();
	}

	private function controls_facts() {
		$this->start_controls_section( 'section_facts', array( 'label' => esc_html__( 'Facts', 'avix-widgets' ) ) );

		$this->add_control(
			'show_facts',
			array(
				'label'       => esc_html__( 'Facts bar', 'avix-widgets' ),
				'description' => esc_html__( 'Filled from the case study; empty facts are left out.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$labels = array(
			'client'   => esc_html__( 'Client', 'avix-widgets' ),
			'services' => esc_html__( 'Services', 'avix-widgets' ),
			'platform' => esc_html__( 'Platform', 'avix-widgets' ),
			'year'     => esc_html__( 'Year', 'avix-widgets' ),
			'role'     => esc_html__( 'Role', 'avix-widgets' ),
			'website'  => esc_html__( 'Website', 'avix-widgets' ),
			'market'   => esc_html__( 'Market', 'avix-widgets' ),
			'credits'  => esc_html__( 'Credits', 'avix-widgets' ),
		);
		foreach ( $labels as $key => $label ) {
			$this->add_control(
				'fact_' . $key,
				array(
					'label'     => $label,
					'type'      => Controls_Manager::SWITCHER,
					'default'   => in_array( $key, array( 'market', 'credits' ), true ) ? '' : 'yes',
					'separator' => 'client' === $key ? 'before' : '',
					'condition' => array( 'show_facts' => 'yes' ),
				)
			);
			$this->add_control(
				'fact_' . $key . '_label',
				array(
					'label'       => esc_html__( 'Label', 'avix-widgets' ),
					'type'        => Controls_Manager::TEXT,
					'default'     => $label,
					'dynamic'     => array( 'active' => true ),
					'condition'   => array(
						'show_facts'    => 'yes',
						'fact_' . $key  => 'yes',
					),
				)
			);
		}

		$repeater = new Repeater();
		$repeater->add_control(
			'label',
			array(
				'label'   => esc_html__( 'Label', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Team', 'avix-widgets' ),
				'dynamic' => array( 'active' => true ),
			)
		);
		$repeater->add_control(
			'value',
			array(
				'label'   => esc_html__( 'Value', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
				'dynamic' => array( 'active' => true ),
			)
		);
		$repeater->add_control(
			'link',
			array(
				'label'   => esc_html__( 'Link', 'avix-widgets' ),
				'type'    => Controls_Manager::URL,
				'default' => array( 'url' => '' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'facts_extra',
			array(
				'label'       => esc_html__( 'More facts', 'avix-widgets' ),
				'description' => esc_html__( 'Added at the end. Rows without a value are left out.', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(),
				'title_field' => '{{{ label }}}',
				'separator'   => 'before',
				'condition'   => array( 'show_facts' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_extras() {
		$this->start_controls_section( 'section_extras', array( 'label' => esc_html__( 'Extras', 'avix-widgets' ) ) );

		$this->add_control(
			'clear_header',
			array(
				'label'       => esc_html__( 'Clear the fixed header', 'avix-widgets' ),
				'description' => esc_html__( 'Adds the Smart Header\'s height to the top spacing, so the hero starts below the menu. Turn off if this hero is not at the top of the page.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'reading_progress',
			array(
				'label'       => esc_html__( 'Reading progress bar', 'avix-widgets' ),
				'description' => esc_html__( 'A thin orange line at the very top of the screen that fills as visitors read down the case study. Hidden while editing.', 'avix-widgets' ),
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
				'description' => esc_html__( 'Dark: the orange shader on near-black, for the dark header. Light: paper background, ink text and a soft orange shader; pair it with the light header.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'dark',
				'options'     => array(
					'dark'  => esc_html__( 'Dark', 'avix-widgets' ),
					'light' => esc_html__( 'Light (paper)', 'avix-widgets' ),
				),
			)
		);

		$this->add_control(
			'logo_light',
			array(
				'label'       => esc_html__( 'Logo on Light', 'avix-widgets' ),
				'description' => esc_html__( 'Client logos are white for the dark hero. "Dark" shows a white logo in ink on paper; "Original" keeps the file\'s own colours.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'dark',
				'options'     => array(
					'dark'     => esc_html__( 'Dark', 'avix-widgets' ),
					'original' => esc_html__( 'Original', 'avix-widgets' ),
				),
				'condition'   => array(
					'theme'     => 'light',
					'show_logo' => 'yes',
				),
			)
		);

		// Empty colours use the theme's own; one picked here overrides only
		// that token. The background comes with headline and text colours so
		// text never disappears on it.
		$colors = array(
			'bg'     => array( esc_html__( 'Background', 'avix-widgets' ), '--csh-bg', esc_html__( 'Empty: #0b0b0c (Dark) or paper (Light). The text turns light or dark to suit the colour picked; the Headline and Text colours below override that.', 'avix-widgets' ) ),
			'ink'    => array( esc_html__( 'Headline', 'avix-widgets' ), '--csh-ink', esc_html__( 'Also the facts and the second button.', 'avix-widgets' ) ),
			'muted'  => array( esc_html__( 'Text', 'avix-widgets' ), '--csh-muted', '' ),
			'line'   => array( esc_html__( 'Lines', 'avix-widgets' ), '--csh-line', '' ),
			'accent' => array( esc_html__( 'Accent', 'avix-widgets' ), '--csh-accent', esc_html__( 'Empty: #fb6007. The button keeps white text.', 'avix-widgets' ) ),
		);
		foreach ( $colors as $key => $color ) {
			$this->add_control(
				'color_' . $key,
				array(
					'label'       => $color[0],
					'description' => $color[2],
					'type'        => Controls_Manager::COLOR,
					'selectors'   => array( '{{WRAPPER}} .avix-csh' => $color[1] . ': {{VALUE}};' ),
					// The shader reads the background and accent when it starts.
					'render_type' => in_array( $key, array( 'bg', 'accent' ), true ) ? 'template' : 'ui',
					'separator'   => 'bg' === $key ? 'before' : '',
				)
			);
		}

		$this->add_responsive_control(
			'padding',
			array(
				'label'              => esc_html__( 'Padding', 'avix-widgets' ),
				'description'        => esc_html__( 'Top: the space under the header. Bottom: used when there is no band under the devices.', 'avix-widgets' ),
				'type'               => Controls_Manager::DIMENSIONS,
				'size_units'         => array( 'px', 'vh' ),
				'allowed_dimensions' => 'vertical',
				'selectors'          => array( '{{WRAPPER}} .avix-csh' => '--csh-pad-top: {{TOP}}{{UNIT}}; --csh-pad-bottom: {{BOTTOM}}{{UNIT}};' ),
				'separator'          => 'before',
			)
		);

		$this->add_responsive_control(
			'max_width',
			array(
				'label'       => esc_html__( 'Content width', 'avix-widgets' ),
				'description' => esc_html__( 'Empty: the full width, with the same side margins as the header.', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 760, 'max' => 1920 ) ),
				'selectors'   => array( '{{WRAPPER}} .avix-csh' => '--csh-max: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'devices_width',
			array(
				'label'       => esc_html__( 'Devices width', 'avix-widgets' ),
				'description' => esc_html__( 'Empty: 1120px for the screenshots, 1240px for a studio render.', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 600, 'max' => 1600 ) ),
				'selectors'   => array( '{{WRAPPER}} .avix-csh' => '--csh-devices-max: {{SIZE}}{{UNIT}};' ),
				'condition'   => array( 'visual!' => 'none' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_shader',
			array(
				'label' => esc_html__( 'Shader background', 'avix-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'shader',
			array(
				'label'       => esc_html__( 'Animated shader', 'avix-widgets' ),
				'description' => esc_html__( 'A slow, flowing orange field (WebGL) with a light that follows the pointer. It stays dark behind the text, pauses off screen and shows one still frame with reduced motion. Without WebGL the glow and grid below show instead.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'shader_style',
			array(
				'label'       => esc_html__( 'Shader style', 'avix-widgets' ),
				'description' => esc_html__( 'Pixel mosaic: the flow drawn as glowing orange squares (the brand pixel) that lift under the pointer, with a quiet square grid and the odd sparkle. Smooth flow: soft orange smoke.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'pixel',
				'options'     => array(
					'pixel'  => esc_html__( 'Pixel mosaic', 'avix-widgets' ),
					'smooth' => esc_html__( 'Smooth flow', 'avix-widgets' ),
				),
				'condition'   => array( 'shader' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'shader_pixel',
			array(
				'label'          => esc_html__( 'Pixel size', 'avix-widgets' ),
				'description'    => esc_html__( 'One square with its gap. Empty: 18px, 16px on tablets, 12px on phones.', 'avix-widgets' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => array( 'px' ),
				'range'          => array( 'px' => array( 'min' => 8, 'max' => 40 ) ),
				'default'        => array(
					'size' => 18,
					'unit' => 'px',
				),
				'tablet_default' => array(
					'size' => 16,
					'unit' => 'px',
				),
				'mobile_default' => array(
					'size' => 12,
					'unit' => 'px',
				),
				'condition'      => array(
					'shader'       => 'yes',
					'shader_style' => 'pixel',
				),
			)
		);

		$this->add_control(
			'shader_sparkles',
			array(
				'label'       => esc_html__( 'Sparkles', 'avix-widgets' ),
				'description' => esc_html__( 'Now and then a single square lights up fully orange and fades. Sparse, never behind the text.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'condition'   => array(
					'shader'       => 'yes',
					'shader_style' => 'pixel',
				),
			)
		);

		$this->add_control(
			'shader_intensity',
			array(
				'label'      => esc_html__( 'Intensity', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array( '%' => array( 'min' => 0, 'max' => 200, 'step' => 5 ) ),
				'default'    => array(
					'size' => 100,
					'unit' => '%',
				),
				'condition'  => array( 'shader' => 'yes' ),
			)
		);

		$this->add_control(
			'shader_speed',
			array(
				'label'       => esc_html__( 'Speed', 'avix-widgets' ),
				'description' => esc_html__( '0: the field stands still (the pointer light still moves).', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%' ),
				'range'       => array( '%' => array( 'min' => 0, 'max' => 300, 'step' => 5 ) ),
				'default'     => array(
					'size' => 100,
					'unit' => '%',
				),
				'condition'   => array( 'shader' => 'yes' ),
			)
		);

		$this->add_control(
			'shader_pointer',
			array(
				'label'       => esc_html__( 'Follow the pointer', 'avix-widgets' ),
				'description' => esc_html__( 'With a mouse or trackpad. On touch screens the light drifts on its own.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'condition'   => array( 'shader' => 'yes' ),
			)
		);

		$this->add_control(
			'shader_accent',
			array(
				'label'       => esc_html__( 'Shader colour', 'avix-widgets' ),
				'description' => esc_html__( 'Empty: the accent (#fb6007).', 'avix-widgets' ),
				'type'        => Controls_Manager::COLOR,
				'selectors'   => array( '{{WRAPPER}} .avix-csh' => '--csh-shader-accent: {{VALUE}};' ),
				'render_type' => 'template',
				'condition'   => array( 'shader' => 'yes' ),
			)
		);

		$this->add_control(
			'shader_base',
			array(
				'label'       => esc_html__( 'Shader base', 'avix-widgets' ),
				'description' => esc_html__( 'Empty: the section background (#0b0b0c on Dark, paper on Light).', 'avix-widgets' ),
				'type'        => Controls_Manager::COLOR,
				'selectors'   => array( '{{WRAPPER}} .avix-csh' => '--csh-shader-base: {{VALUE}};' ),
				'render_type' => 'template',
				'condition'   => array( 'shader' => 'yes' ),
			)
		);

		$this->add_control(
			'show_glow',
			array(
				'label'       => esc_html__( 'Orange glow', 'avix-widgets' ),
				'description' => esc_html__( 'Shown when the shader is off or WebGL is not available.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'show_grid',
			array(
				'label'   => esc_html__( 'Grid pattern', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
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

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'label'    => esc_html__( 'Headline', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-csh .avix-csh__title',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'text_typography',
				'label'    => esc_html__( 'Lead', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-csh .avix-csh__lead',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'fact_typography',
				'label'    => esc_html__( 'Fact values', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-csh .avix-csh__fact-value',
			)
		);

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ */
	/* Render                                                              */
	/* ------------------------------------------------------------------ */

	protected function render(): void {
		$s      = $this->get_settings_for_display();
		$cs     = $this->cs();
		$editor = Case_Study::is_editor();

		$client = '' !== (string) ( $cs['client'] ?? '' ) ? (string) $cs['client'] : (string) ( $cs['title'] ?? '' );
		$title  = $this->pick( $s, 'title', '' !== (string) ( $cs['display_title'] ?? '' ) ? $cs['display_title'] : ( $cs['title'] ?? '' ) );

		// Nothing to show: no case study and no headline typed in. The live
		// page prints nothing; the editor explains why.
		if ( ! $cs && '' === $title ) {
			$this->cs_alert( esc_html__( 'Case Study Hero: there is no case study to show yet. Publish one, or pick one under "Case study".', 'avix-widgets' ) );
			return;
		}

		$theme    = 'light' === ( $s['theme'] ?? '' ) ? 'light' : 'dark';
		// A background picked under Style sets the text tone (auto contrast),
		// so a dark colour on the Light theme never leaves ink on ink.
		$tone     = $this->bg_tone( $s );
		$theme    = '' !== $tone ? $tone : $theme;
		$tag      = Utils::validate_html_tag( $s['title_tag'] ?? 'h1' );
		$title_id = 'avix-csh-title-' . $this->get_id();
		$eager    = 'yes' === ( $s['image_eager'] ?? '' );
		$visual   = $this->visual_html( $s, $cs, $client, $eager );
		$mode     = $visual['mode'];
		$bleed    = $visual['bleed'];

		$classes = array(
			'avix-csh',
			'avix-csh--' . $theme,
			'avix-csh--visual-' . $mode,
		);
		$classes[] = 'dark' === $theme ? 'avix-csk-on-dark' : 'avix-csk-on-light';
		if ( 'light' === $theme && 'original' !== ( $s['logo_light'] ?? 'dark' ) ) {
			$classes[] = 'avix-csh--logo-ink';
		}
		if ( $bleed ) {
			$classes[] = 'avix-csh--bleed';
		}
		if ( 'yes' === ( $s['clear_header'] ?? '' ) ) {
			$classes[] = 'avix-csh--clear';
		}
		if ( 'yes' !== ( $s['show_glow'] ?? '' ) ) {
			$classes[] = 'avix-csh--no-glow';
		}
		if ( 'yes' !== ( $s['show_grid'] ?? '' ) ) {
			$classes[] = 'avix-csh--no-grid';
		}

		$style = '';
		if ( '' !== (string) ( $cs['accent'] ?? '' ) ) {
			$style .= '--csk-accent: ' . $cs['accent'] . ';';
		}
		if ( '' !== $visual['ratio'] ) {
			$style .= ' --csh-shot-ratio: ' . $visual['ratio'] . ';';
		}

		$progress = 'yes' === ( $s['reading_progress'] ?? '' );
		$config   = array(
			'progress' => $progress,
			'parallax' => in_array( $mode, array( 'devices', 'image' ), true ),
			'shader'   => $this->shader_config( $s ),
		);

		$this->add_render_attribute(
			'root',
			array(
				'class'         => $classes,
				'data-avix-csh' => wp_json_encode( $config ),
			)
		);
		if ( '' !== trim( $style ) ) {
			$this->add_render_attribute( 'root', 'style', trim( $style ) );
		}
		if ( '' !== $title ) {
			$this->add_render_attribute( 'root', 'aria-labelledby', $title_id );
		} else {
			$this->add_render_attribute( 'root', 'aria-label', '' !== $client ? $client : esc_html__( 'Case study', 'avix-widgets' ) );
		}

		$eyebrow = $this->pick( $s, 'eyebrow', $this->auto_eyebrow( $cs ) );
		$lead    = $this->pick( $s, 'text', '' !== (string) ( $cs['summary'] ?? '' ) ? $cs['summary'] : ( $cs['excerpt'] ?? '' ) );
		$logo    = 'yes' === ( $s['show_logo'] ?? '' ) ? $this->logo_html( $s, $cs, $client ) : '';
		$facts   = 'yes' === ( $s['show_facts'] ?? '' ) ? $this->facts( $s, $cs ) : array();
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<?php // The script adds the shader canvas here; the grid and glow are its fallback. ?>
			<div class="avix-csh__bg" aria-hidden="true">
				<span class="avix-csh__grid"></span>
				<span class="avix-csh__glow avix-csh__glow--a"></span>
				<span class="avix-csh__glow avix-csh__glow--b"></span>
			</div>
			<div class="avix-csh__inner">
				<div class="avix-csh__frame">
					<?php $this->render_crumbs( $s, $cs, $client ); ?>
					<div class="avix-csh__top">
						<div class="avix-csh__copy">
							<?php
							// The headline and the lead are visible from the first paint (the
							// headline is the LCP element); only the parts around them rise.
							if ( '' !== $logo ) :
								?>
								<div class="avix-csh__logo avix-csh__rise"><?php echo $logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in logo_html(). ?></div>
							<?php endif; ?>
							<?php
							echo Kit::eyebrow( $eyebrow, 'avix-csh__eyebrow avix-csh__rise' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Kit::eyebrow().
							if ( '' !== $title ) :
								?>
								<<?php echo esc_html( $tag ); ?> id="<?php echo esc_attr( $title_id ); ?>" class="avix-csh__title"><?php echo $this->title_html( $title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in title_html(). ?></<?php echo esc_html( $tag ); ?>>
							<?php endif; ?>
							<?php if ( '' !== $lead ) : ?>
								<p class="avix-csh__lead"><?php echo Case_Study::accent_html( $lead, 'avix-csh__accent' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in accent_html(). ?></p>
							<?php endif; ?>
							<?php $this->render_buttons( $s, $cs, $editor ); ?>
						</div>
					</div>
					<?php $this->render_facts( $facts ); ?>
				</div>
				<?php
				if ( '' !== $visual['html'] ) {
					echo $visual['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts in visual_html().
				}
				?>
			</div>
		</section>
		<?php
		if ( $progress ) {
			echo '<div class="avix-csh__progress" data-csh-progress aria-hidden="true"><span class="avix-csh__progress-bar"></span></div>';
		}
	}

	/**
	 * "Case study · Shopify · Netherlands": the first service term and the
	 * market, skipping empty parts.
	 *
	 * @param array $cs Case study.
	 */
	private function auto_eyebrow( array $cs ) {
		$parts = array( __( 'Case study', 'avix-widgets' ) );
		$terms = (array) ( $cs['terms']['service'] ?? array() );
		if ( ! empty( $terms[0][0] ) ) {
			$parts[] = (string) $terms[0][0];
		}
		if ( '' !== (string) ( $cs['market'] ?? '' ) ) {
			$parts[] = (string) $cs['market'];
		}
		return implode( ' · ', $parts );
	}

	/**
	 * "dark" or "light" for a background colour picked under Style (plain
	 * hex or rgb), or '' when none is set or it is a global colour.
	 *
	 * @param array $s Settings.
	 */
	private function bg_tone( array $s ) {
		if ( ! empty( $s['__globals__']['color_bg'] ) ) {
			return '';
		}
		$color = strtolower( trim( (string) ( $s['color_bg'] ?? '' ) ) );
		$rgb   = null;
		if ( preg_match( '/^#([0-9a-f]{3,8})$/', $color, $m ) ) {
			$hex = $m[1];
			if ( strlen( $hex ) < 6 ) {
				$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
			}
			$rgb = array( hexdec( substr( $hex, 0, 2 ) ), hexdec( substr( $hex, 2, 2 ) ), hexdec( substr( $hex, 4, 2 ) ) );
		} elseif ( preg_match( '/^rgba?\(\s*([\d.]+)[\s,]+([\d.]+)[\s,]+([\d.]+)/', $color, $m ) ) {
			$rgb = array( (float) $m[1], (float) $m[2], (float) $m[3] );
		}
		if ( null === $rgb ) {
			return '';
		}
		$luma = 0.0;
		foreach ( array( 0.2126, 0.7152, 0.0722 ) as $i => $weight ) {
			$v     = max( 0.0, min( 255.0, (float) $rgb[ $i ] ) ) / 255;
			$luma += $weight * ( $v <= 0.04045 ? $v / 12.92 : pow( ( $v + 0.055 ) / 1.055, 2.4 ) );
		}
		// Where white and ink text have the same contrast.
		return $luma < 0.179 ? 'dark' : 'light';
	}

	/**
	 * The shader settings for the script, or false when it is off.
	 *
	 * @param array $s Settings.
	 * @return array|false
	 */
	private function shader_config( array $s ) {
		if ( 'yes' !== ( $s['shader'] ?? 'yes' ) ) {
			return false;
		}
		$size  = function ( $key ) use ( $s ) {
			$value = $s[ $key ]['size'] ?? '';
			return '' === $value || null === $value ? 100.0 : max( 0.0, min( 300.0, (float) $value ) );
		};
		$pixel = function ( $key, $fallback ) use ( $s ) {
			$value = $s[ $key ]['size'] ?? '';
			return '' === $value || null === $value ? $fallback : (int) max( 8, min( 40, (float) $value ) );
		};
		return array(
			'style'     => 'smooth' === ( $s['shader_style'] ?? 'pixel' ) ? 'smooth' : 'pixel',
			'cell'      => array(
				'd' => $pixel( 'shader_pixel', 18 ),
				't' => $pixel( 'shader_pixel_tablet', 16 ),
				'm' => $pixel( 'shader_pixel_mobile', 12 ),
			),
			'sparkles'  => 'yes' === ( $s['shader_sparkles'] ?? 'yes' ),
			'intensity' => round( $size( 'shader_intensity' ) / 100, 3 ),
			'speed'     => round( $size( 'shader_speed' ) / 100, 3 ),
			'pointer'   => 'yes' === ( $s['shader_pointer'] ?? 'yes' ),
		);
	}

	/**
	 * Headline: escaped, [accent] spans, Enter = line break.
	 *
	 * @param string $title Raw headline.
	 */
	private function title_html( $title ) {
		$lines = preg_split( '/\R/u', trim( (string) $title ) );
		$lines = array_filter( array_map( 'trim', is_array( $lines ) ? $lines : array( $title ) ), 'strlen' );
		$out   = array();
		foreach ( $lines as $line ) {
			$out[] = $this->keep_compounds( Case_Study::accent_html( $line, 'avix-csh__accent' ) );
		}
		return implode( ' <br>', $out );
	}

	/**
	 * Short hyphenated words ("E-Commerce", "High-Converting") never break at
	 * the hyphen: they move to the next line whole. Text outside tags only.
	 *
	 * @param string $html Escaped headline HTML.
	 */
	private function keep_compounds( $html ) {
		$parts = preg_split( '/(<[^>]*>)/u', (string) $html, -1, PREG_SPLIT_DELIM_CAPTURE );
		if ( ! is_array( $parts ) ) {
			return (string) $html;
		}
		foreach ( $parts as $i => $part ) {
			if ( '' === $part || '<' === $part[0] ) {
				continue;
			}
			$done = preg_replace_callback(
				'/(?<![\p{L}\p{N}-])[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)+(?![\p{L}\p{N}-])/u',
				function ( $m ) {
					$len = function_exists( 'mb_strlen' ) ? mb_strlen( $m[0], 'UTF-8' ) : strlen( $m[0] );
					return $len <= 18 ? '<span class="avix-csh__nowrap">' . $m[0] . '</span>' : $m[0];
				},
				$part
			);
			$parts[ $i ] = null === $done ? $part : $done;
		}
		return implode( '', $parts );
	}

	/**
	 * @param array  $s      Settings.
	 * @param array  $cs     Case study.
	 * @param string $client Client name.
	 */
	private function render_crumbs( array $s, array $cs, $client ) {
		if ( 'yes' !== ( $s['show_breadcrumb'] ?? '' ) ) {
			return;
		}
		$parent      = $this->pick( $s, 'crumb_parent', Case_Study::index_title() );
		$link        = (array) ( $s['crumb_parent_link'] ?? array() );
		$custom_link = '' !== esc_url( (string) ( $link['url'] ?? '' ) );
		$current     = '' !== $client ? $client : Case_Study::plain( (string) ( $cs['title'] ?? '' ) );
		$sep         = '<svg class="avix-csh__crumb-sep" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m9 6 6 6-6 6"/></svg>';

		if ( $custom_link ) {
			$this->add_link_attributes( 'crumb_parent', $link );
		} else {
			$this->add_render_attribute( 'crumb_parent', 'href', esc_url( Case_Study::index_url() ) );
		}
		$this->add_render_attribute( 'crumb_parent', 'class', 'avix-csh__crumb-link' );
		?>
		<nav class="avix-csh__crumbs avix-csh__rise" aria-label="<?php echo esc_attr__( 'Breadcrumb', 'avix-widgets' ); ?>">
			<ol class="avix-csh__crumb-list">
				<li class="avix-csh__crumb avix-csh__crumb--home"><a class="avix-csh__crumb-link" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'avix-widgets' ); ?></a></li>
				<li class="avix-csh__crumb avix-csh__crumb--parent">
					<?php echo $sep; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
					<a <?php $this->print_render_attribute_string( 'crumb_parent' ); ?>><svg class="avix-csh__crumb-back" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m15 6-6 6 6 6"/></svg><span class="avix-csh__crumb-text"><?php echo esc_html( $parent ); ?></span></a>
				</li>
				<?php if ( '' !== $current ) : ?>
					<li class="avix-csh__crumb avix-csh__crumb--current" aria-current="page">
						<?php echo $sep; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
						<span class="avix-csh__crumb-text" title="<?php echo esc_attr( $current ); ?>"><?php echo esc_html( $current ); ?></span>
					</li>
				<?php endif; ?>
			</ol>
		</nav>
		<?php
	}

	/**
	 * Client logo <img>, or ''.
	 *
	 * @param array  $s      Settings.
	 * @param array  $cs     Case study.
	 * @param string $client Client name.
	 */
	private function logo_html( array $s, array $cs, $client ) {
		$media = (array) ( $s['logo'] ?? array() );
		$id    = $this->media_id( $media );
		$id    = $id ? $id : (int) ( $cs['logo'] ?? 0 );
		/* translators: %s: client name. */
		$alt = '' !== $client ? sprintf( __( '%s logo', 'avix-widgets' ), $client ) : '';
		if ( $id ) {
			// Above the fold but tiny: eager, yet never competing with the
			// hero image for priority (core would mark it "high").
			return Kit::img(
				$id,
				'medium_large',
				'(max-width: 600px) 200px, 240px',
				array(
					'class'         => 'avix-csh__logo-img',
					'alt'           => $alt,
					'loading'       => 'eager',
					'fetchpriority' => 'auto',
				)
			);
		}
		if ( ! empty( $media['url'] ) ) {
			return sprintf( '<img class="avix-csh__logo-img" src="%1$s" alt="%2$s" decoding="async">', esc_url( $media['url'] ), esc_attr( $alt ) );
		}
		return '';
	}

	/**
	 * @param array $s      Settings.
	 * @param array $cs     Case study.
	 * @param bool  $editor Editing in Elementor.
	 */
	private function render_buttons( array $s, array $cs, $editor ) {
		$primary   = trim( (string) ( $s['button_text'] ?? '' ) );
		$secondary = 'yes' === ( $s['show_button_2'] ?? '' ) ? trim( (string) ( $s['button_2_text'] ?? '' ) ) : '';

		$link_1 = (array) ( $s['button_link'] ?? array() );
		$own_1  = '' !== esc_url( (string) ( $link_1['url'] ?? '' ) );
		$url_1  = $own_1 ? esc_url( (string) $link_1['url'] ) : esc_url( (string) ( $cs['live_url'] ?? '' ) );
		$url_2  = esc_url( (string) ( $s['button_2_link']['url'] ?? '' ) );

		// A button needs a usable link; the editor still shows it.
		if ( '' === $url_1 && ! $editor ) {
			$primary = '';
		}
		if ( '' === $url_2 && ! $editor ) {
			$secondary = '';
		}
		if ( '' === $primary && '' === $secondary ) {
			return;
		}

		echo '<div class="avix-csh__actions avix-csh__rise">';
		if ( '' !== $primary ) {
			$this->add_render_attribute( 'btn', 'class', 'avix-csh__btn avix-csh__btn--primary' );
			$new_tab = true;
			if ( $own_1 ) {
				$this->add_link_attributes( 'btn', $link_1 );
				$new_tab = ! empty( $link_1['is_external'] );
			} elseif ( '' !== $url_1 ) {
				$this->add_render_attribute(
					'btn',
					array(
						'href'   => $url_1,
						'target' => '_blank',
					)
				);
			}
			if ( $new_tab && '' !== $url_1 ) {
				// Our own client work: noopener, but no nofollow.
				$this->add_render_attribute( 'btn', 'rel', 'noopener' );
			}
			printf(
				'<a %1$s><span class="avix-csh__btn-text">%2$s</span>%3$s<span class="avix-csh__btn-icon" aria-hidden="true">%4$s</span></a>',
				$this->get_render_attribute_string( 'btn' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by Elementor.
				esc_html( $primary ),
				$new_tab && '' !== $url_1 ? '<span class="avix-csh__sr">' . esc_html__( '(opens in a new tab)', 'avix-widgets' ) . '</span>' : '',
				Kit::arrow( 'ne' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
			);
		}
		if ( '' !== $secondary ) {
			$this->add_render_attribute( 'btn2', 'class', 'avix-csh__btn avix-csh__btn--ghost' );
			$raw = (string) ( $s['button_2_link']['url'] ?? '' );
			if ( '' !== $url_2 ) {
				$this->add_link_attributes( 'btn2', $s['button_2_link'] );
			}
			printf(
				'<a %1$s><span class="avix-csh__btn-text">%2$s</span><span class="avix-csh__btn-arrow" aria-hidden="true">%3$s</span></a>',
				$this->get_render_attribute_string( 'btn2' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by Elementor.
				esc_html( $secondary ),
				Kit::arrow( 0 === strpos( $raw, '#' ) ? 's' : 'e' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
			);
		}
		echo '</div>';
	}

	/**
	 * Facts as rows of array( key, label, value_html ), in display order.
	 *
	 * @param array $s  Settings.
	 * @param array $cs Case study.
	 */
	private function facts( array $s, array $cs ) {
		$rows = array();
		foreach ( self::FACTS as $key => $default ) {
			if ( 'yes' !== ( $s[ 'fact_' . $key ] ?? '' ) ) {
				continue;
			}
			$label = trim( (string) ( $s[ 'fact_' . $key . '_label' ] ?? '' ) );
			$label = '' !== $label ? $label : $default;
			$value = '';
			switch ( $key ) {
				case 'services':
					$value = $this->services_html( $cs );
					break;
				case 'website':
					$url  = esc_url( (string) ( $cs['live_url'] ?? '' ) );
					$text = (string) ( $cs['live_label'] ?? '' );
					if ( '' !== $url && '' !== $text ) {
						$value = sprintf(
							'<a class="avix-csh__fact-link" href="%1$s" target="_blank" rel="noopener"><span class="avix-csh__fact-link-text">%2$s</span>%3$s<span class="avix-csh__sr">%4$s</span></a>',
							$url,
							esc_html( $text ),
							Kit::arrow( 'ne' ),
							esc_html__( '(opens in a new tab)', 'avix-widgets' )
						);
					}
					break;
				default:
					$text  = trim( (string) ( $cs[ $key ] ?? '' ) );
					$value = '' !== $text ? esc_html( $text ) : '';
			}
			if ( '' !== $value ) {
				$rows[] = array( $key, $label, $value );
			}
		}

		foreach ( (array) ( $s['facts_extra'] ?? array() ) as $row ) {
			$label = trim( (string) ( $row['label'] ?? '' ) );
			$text  = trim( (string) ( $row['value'] ?? '' ) );
			if ( '' === $text ) {
				continue;
			}
			$url   = esc_url( (string) ( $row['link']['url'] ?? '' ) );
			$value = esc_html( $text );
			if ( '' !== $url ) {
				$key = 'fact_extra_' . sanitize_html_class( (string) ( $row['_id'] ?? count( $rows ) ) );
				$this->add_render_attribute( $key, 'class', 'avix-csh__fact-link' );
				$this->add_link_attributes( $key, (array) $row['link'] );
				$value = '<a ' . $this->get_render_attribute_string( $key ) . '><span class="avix-csh__fact-link-text">' . $value . '</span>' . Kit::arrow( 'ne' ) . '</a>';
			}
			$rows[] = array( 'extra', $label, $value );
		}

		return $rows;
	}

	/**
	 * Services as chips, linked to our service page when a term has one. The
	 * case study's own services text wins; the term names are the fallback.
	 *
	 * @param array $cs Case study.
	 */
	private function services_html( array $cs ) {
		$terms  = (array) ( $cs['terms']['service'] ?? array() );
		$labels = array_values( array_filter( array_map( 'strval', (array) ( $cs['services'] ?? array() ) ), 'strlen' ) );
		if ( ! $labels ) {
			foreach ( $terms as $term ) {
				if ( ! empty( $term[0] ) ) {
					$labels[] = (string) $term[0];
				}
			}
		}
		if ( ! $labels ) {
			return '';
		}

		$links = array();
		foreach ( $labels as $i => $label ) {
			$slug = sanitize_title( $label );
			foreach ( $terms as $term ) {
				if ( empty( $term[2] ) ) {
					continue;
				}
				$name = (string) ( $term[0] ?? '' );
				if ( $slug === (string) ( $term[1] ?? '' ) || $slug === sanitize_title( $name ) || ( '' !== $name && 0 === stripos( $label, $name . ' ' ) ) ) {
					$links[ $i ] = (string) $term[2];
					break;
				}
			}
		}
		return Kit::chips( $labels, 'avix-csh__chips', $links );
	}

	/**
	 * The facts bar. Hairlines and spans per layout (6 / 3 / 2 columns) are
	 * worked out here, so every row starts flush left and the last cell of a
	 * short row fills it.
	 *
	 * @param array $facts From facts().
	 */
	private function render_facts( array $facts ) {
		$n = count( $facts );
		if ( ! $n ) {
			return;
		}
		$cols_d = $n <= 6 ? $n : (int) ceil( $n / ceil( $n / 6 ) );
		$cols_t = $n <= 3 ? $n : (int) ceil( $n / ceil( $n / 3 ) );

		// Phones: two columns of short facts, then the services (chips need
		// the width) and the website, each on its own full-width row.
		$mobile = array();
		$wide   = array();
		foreach ( $facts as $i => $fact ) {
			if ( in_array( $fact[0], array( 'services', 'website' ), true ) ) {
				$wide[ $fact[0] ] = $i;
				continue;
			}
			$mobile[] = $i;
		}
		$m_pos = array_flip( $mobile );
		$m_n   = count( $mobile );
		$first = $m_n ? -1 : ( isset( $wide['services'] ) ? $wide['services'] : ( isset( $wide['website'] ) ? $wide['website'] : -1 ) );

		printf(
			'<div class="avix-csh__facts-wrap"><dl class="avix-csh__facts" style="--csh-cols-d: %1$d; --csh-cols-t: %2$d;">',
			(int) $cols_d,
			(int) $cols_t
		);
		foreach ( $facts as $i => $fact ) {
			$cls   = array( 'avix-csh__fact', 'avix-csh__fact--' . sanitize_html_class( $fact[0] ) );
			$style = '--i: ' . (int) $i . ';';

			foreach ( array( 'd' => $cols_d, 't' => $cols_t ) as $bp => $cols ) {
				if ( 0 === $i % $cols ) {
					$cls[] = 'is-' . $bp . '-start';
				}
				if ( $i < $cols ) {
					$cls[] = 'is-' . $bp . '-top';
				}
				if ( $i === $n - 1 ) {
					$span = (int) ( ceil( $n / $cols ) * $cols - $n + 1 );
					if ( $span > 1 ) {
						$style .= ' --' . $bp . '-span: ' . $span . ';';
					}
				}
			}

			if ( in_array( $i, $wide, true ) ) {
				$cls[] = 'is-m-start';
				if ( $i === $first ) {
					$cls[] = 'is-m-top';
				}
			} else {
				$j = $m_pos[ $i ];
				if ( 0 === $j % 2 ) {
					$cls[] = 'is-m-start';
				}
				if ( $j < 2 ) {
					$cls[] = 'is-m-top';
				}
				if ( $j === $m_n - 1 && 1 === $m_n % 2 ) {
					$style .= ' --m-span: 2;';
				}
			}

			printf(
				'<div class="%1$s" style="%2$s"><dt class="avix-csh__fact-label">%3$s</dt><dd class="avix-csh__fact-value">%4$s</dd></div>',
				esc_attr( implode( ' ', $cls ) ),
				esc_attr( $style ),
				esc_html( $fact[1] ),
				$fact[2] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in facts().
			);
		}
		echo '</dl></div>';
	}

	/**
	 * The visual under the facts.
	 *
	 * @param array  $s      Settings.
	 * @param array  $cs     Case study.
	 * @param string $client Client name.
	 * @param bool   $eager  Load the main image first (LCP).
	 * @return array{mode:string,html:string,bleed:bool,ratio:string}
	 */
	private function visual_html( array $s, array $cs, $client, $eager ) {
		$mode = in_array( $s['visual'] ?? 'auto', self::VISUALS, true ) ? (string) $s['visual'] : 'auto';
		$out  = array(
			'mode'  => 'none',
			'html'  => '',
			'bleed' => false,
			'ratio' => '',
		);
		if ( 'none' === $mode ) {
			return $out;
		}

		$render = 0;
		if ( in_array( $mode, array( 'auto', 'image' ), true ) ) {
			$render = $this->media_id( (array) ( $s['image_render'] ?? array() ) );
			$render = $render ? $render : (int) ( $cs['hero_render'] ?? 0 );
		}
		if ( 'auto' === $mode ) {
			$mode = $render ? 'image' : 'devices';
		}

		if ( 'image' === $mode ) {
			if ( ! $render ) {
				// Image mode without a render falls back to the screenshots.
				$mode = 'devices';
			} else {
				return $this->render_html( $s, $render, $client, $eager );
			}
		}

		$desktop = $this->media_id( (array) ( $s['image_desktop'] ?? array() ) );
		$desktop = $desktop ? $desktop : (int) ( $cs['hero_desktop'] ?? 0 );
		if ( ! $desktop ) {
			return $out;
		}
		$mobile = 0;
		if ( 'devices' === $mode ) {
			$mobile = $this->media_id( (array) ( $s['image_mobile'] ?? array() ) );
			$mobile = $mobile ? $mobile : (int) ( $cs['hero_mobile'] ?? 0 );
		}
		$mode  = $mobile ? 'devices' : 'browser';
		$label = $this->pick( $s, 'url_label', (string) ( $cs['live_label'] ?? '' ) );
		$ratio = Kit::ratio( $desktop );

		$attr = array(
			'class' => 'avix-csh__shot',
			/* translators: %s: client name. */
			'alt'   => $this->alt( $desktop, '' !== $client ? sprintf( __( '%s homepage on desktop', 'avix-widgets' ), $client ) : '' ),
		);
		if ( $eager ) {
			$attr['loading']       = 'eager';
			$attr['fetchpriority'] = 'high';
		}
		$html  = '<div class="avix-csh__devices-row"><figure class="avix-csh__devices avix-csk-figure">';
		$html .= '<div class="avix-csh__browser">' . Kit::frame(
			Kit::img( $desktop, 'full', self::SIZES_DESKTOP, $attr ),
			'browser',
			array(
				'url_label' => $label,
				'ratio'     => $ratio,
			)
		) . '</div>';
		if ( $mobile ) {
			$html .= '<div class="avix-csh__phone" data-csh-phone>' . Kit::frame(
				Kit::img(
					$mobile,
					'large',
					self::SIZES_PHONE,
					array(
						'class' => 'avix-csh__shot',
						/* translators: %s: client name. */
						'alt'   => $this->alt( $mobile, '' !== $client ? sprintf( __( '%s homepage on mobile', 'avix-widgets' ), $client ) : '' ),
					)
				),
				'phone',
				array( 'ratio' => Kit::ratio( $mobile ) )
			) . '</div>';
		}
		if ( '' !== $client ) {
			$caption = $mobile
				/* translators: %s: client name. */
				? sprintf( __( '%s homepage on desktop and mobile, as it is live today.', 'avix-widgets' ), $client )
				/* translators: %s: client name. */
				: sprintf( __( '%s homepage, as it is live today.', 'avix-widgets' ), $client );
			$html .= '<figcaption class="avix-csh__sr">' . esc_html( $caption ) . '</figcaption>';
		}
		$html .= '</figure></div>';

		$out['mode']  = $mode;
		$out['html']  = $html;
		$out['bleed'] = 'yes' === ( $s['bleed'] ?? '' );
		$out['ratio'] = $this->ratio_factor( $ratio );
		return $out;
	}

	/**
	 * Image mode: the studio render, centred on the glow.
	 *
	 * @param array  $s      Settings.
	 * @param int    $id     Render attachment.
	 * @param string $client Client name.
	 * @param bool   $eager  Load it first (LCP).
	 */
	private function render_html( array $s, $id, $client, $eager ) {
		$blend = 'yes' === ( $s['image_blend'] ?? '' );
		$attr  = array(
			'class' => 'avix-csh__render-img',
			/* translators: %s: client name. */
			'alt'   => $this->alt( $id, '' !== $client ? sprintf( __( '%s website on a laptop and a phone', 'avix-widgets' ), $client ) : '' ),
		);
		if ( $eager ) {
			$attr['loading']       = 'eager';
			$attr['fetchpriority'] = 'high';
		}
		$img = Kit::img( $id, 'full', self::SIZES_RENDER, $attr );
		if ( '' === $img ) {
			return array(
				'mode'  => 'none',
				'html'  => '',
				'bleed' => false,
				'ratio' => '',
			);
		}
		$ratio = Kit::ratio( $id );
		$html  = '<div class="avix-csh__devices-row avix-csh__devices-row--render">'
			. '<figure class="avix-csh__render avix-csk-figure' . ( $blend ? ' is-blend' : ' is-framed' ) . '"' . ( '' !== $ratio ? ' style="--csh-render-ratio: ' . esc_attr( $ratio ) . ';"' : '' ) . '>'
			. '<div class="avix-csh__render-frame" data-csh-drift>' . $img . '</div>';
		if ( '' !== $client ) {
			/* translators: %s: client name. */
			$html .= '<figcaption class="avix-csh__sr">' . esc_html( sprintf( __( '%s website on desktop and mobile, as it is live today.', 'avix-widgets' ), $client ) ) . '</figcaption>';
		}
		$html .= '</figure></div>';

		return array(
			'mode'  => 'image',
			'html'  => $html,
			// A blended render fades out at the bottom: a band under it would show through.
			'bleed' => ! $blend && 'yes' === ( $s['image_bleed'] ?? '' ),
			'ratio' => $this->ratio_factor( $ratio ),
		);
	}

	/**
	 * "2400 / 1500" → "0.625" (height per width), for the band maths.
	 *
	 * @param string $ratio Kit::ratio().
	 */
	private function ratio_factor( $ratio ) {
		if ( preg_match( '#^(\d+(?:\.\d+)?)\s*/\s*(\d+(?:\.\d+)?)$#', (string) $ratio, $m ) && (float) $m[1] > 0 ) {
			return (string) round( (float) $m[2] / (float) $m[1], 4 );
		}
		return '';
	}

	/**
	 * Stored alt text, else the fallback.
	 *
	 * @param int    $id       Attachment.
	 * @param string $fallback Fallback text.
	 */
	private function alt( $id, $fallback ) {
		$alt = trim( (string) get_post_meta( (int) $id, '_wp_attachment_image_alt', true ) );
		return '' !== $alt ? $alt : (string) $fallback;
	}
}
