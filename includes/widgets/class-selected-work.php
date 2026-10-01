<?php
/**
 * Selected Work: sticky 3D scroll stack of case studies.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;

defined( 'ABSPATH' ) || exit;

class Selected_Work extends Widget_Base {

	const DEFAULT_OFFSET_SELECTORS = '#wpadminbar, #avix-smart-header';

	/**
	 * Platform marks (shared with the Smart Header), see Brand_Icons.
	 */
	const BRANDS = Brand_Icons::BRANDS;

	public function get_name(): string {
		return 'avix-selected-work';
	}

	public function get_title(): string {
		return esc_html__( 'Selected Work Stack', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-gallery-grid';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'portfolio', 'work', 'case study', 'projects', 'stack', 'scroll', 'sticky', 'avix' );
	}

	public function get_style_depends(): array {
		return array( 'avix-selected-work' );
	}

	public function get_script_depends(): array {
		return array( 'avix-selected-work' );
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/* ------------------------------------------------------------------ */
	/* Controls                                                            */
	/* ------------------------------------------------------------------ */

	protected function register_controls(): void {
		$this->controls_header();
		$this->controls_projects();
		$this->controls_query();
		$this->controls_card_content();
		$this->controls_platforms();
		$this->controls_behaviour();

		$this->style_section();
		$this->style_header();
		$this->style_cards();
		$this->style_panel();
		$this->style_platforms();
	}

	private function controls_header() {
		$this->start_controls_section(
			'section_header',
			array( 'label' => esc_html__( 'Header', 'avix-widgets' ) )
		);

		$this->add_control(
			'show_header',
			array(
				'label'   => esc_html__( 'Show header', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'kicker',
			array(
				'label'       => esc_html__( 'Kicker', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Portfolio / 2026', 'avix-widgets' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_header' => 'yes' ),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Title', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Selected work', 'avix-widgets' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_header' => 'yes' ),
			)
		);

		$this->add_control(
			'title_accent',
			array(
				'label'       => esc_html__( 'Highlighted words', 'avix-widgets' ),
				'description' => esc_html__( 'Added after the title in the accent colour.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_header' => 'yes' ),
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'     => esc_html__( 'Title HTML tag', 'avix-widgets' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'h2',
				'options'   => $this->heading_tags(),
				'condition' => array( 'show_header' => 'yes' ),
			)
		);

		$this->add_control(
			'show_count',
			array(
				'label'     => esc_html__( 'Project count after title', 'avix-widgets' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'show_header' => 'yes' ),
			)
		);

		$this->add_control(
			'sticky_header',
			array(
				'label'       => esc_html__( 'Sticky header', 'avix-widgets' ),
				'description' => esc_html__( 'Header stays pinned above the stack and leaves with the last card.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'condition'   => array( 'show_header' => 'yes' ),
			)
		);

		$this->add_control(
			'show_progress',
			array(
				'label'     => esc_html__( 'Progress readout (01 — 03)', 'avix-widgets' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array(
					'show_header'   => 'yes',
					'sticky_header' => 'yes',
				),
			)
		);

		$this->add_control(
			'cta_text',
			array(
				'label'     => esc_html__( 'Button text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'All case studies', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'separator' => 'before',
				'condition' => array( 'show_header' => 'yes' ),
			)
		);

		$this->add_control(
			'cta_link',
			array(
				'label'     => esc_html__( 'Button link', 'avix-widgets' ),
				'type'      => Controls_Manager::URL,
				'dynamic'   => array( 'active' => true ),
				'default'   => array(
					'url'         => 'https://akib.avixdigital.com/',
					'is_external' => '',
					'nofollow'    => '',
				),
				'condition' => array( 'show_header' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_projects() {
		$this->start_controls_section(
			'section_projects',
			array( 'label' => esc_html__( 'Projects', 'avix-widgets' ) )
		);

		$this->add_control(
			'source',
			array(
				'label'   => esc_html__( 'Source', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'manual',
				'options' => array(
					'manual' => esc_html__( 'Manual list', 'avix-widgets' ),
					'query'  => esc_html__( 'Posts / custom post type', 'avix-widgets' ),
				),
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'name',
			array(
				'label'       => esc_html__( 'Project name', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Project name', 'avix-widgets' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'image',
			array(
				'label'   => esc_html__( 'Image', 'avix-widgets' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => array( 'url' => Utils::get_placeholder_image_src() ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'focus_x',
			array(
				'label'      => esc_html__( 'Image focus X', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array( '%' => array( 'min' => 0, 'max' => 100 ) ),
				'default'    => array( 'unit' => '%', 'size' => 50 ),
			)
		);

		$repeater->add_control(
			'focus_y',
			array(
				'label'      => esc_html__( 'Image focus Y', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array( '%' => array( 'min' => 0, 'max' => 100 ) ),
				'default'    => array( 'unit' => '%', 'size' => 50 ),
			)
		);

		$repeater->add_control(
			'tags',
			array(
				'label'       => esc_html__( 'Services / tags', 'avix-widgets' ),
				'description' => esc_html__( 'Comma separated.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'description',
			array(
				'label'   => esc_html__( 'Description', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 4,
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'outcome_label',
			array(
				'label'       => esc_html__( 'Outcome label', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'Designed for', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'outcome_value',
			array(
				'label'       => esc_html__( 'Outcome', 'avix-widgets' ),
				'description' => esc_html__( 'A result or goal, e.g. "Repeat purchase" or "3.1x ROAS".', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'year',
			array(
				'label'       => esc_html__( 'Meta (year, domain…)', 'avix-widgets' ),
				'description' => esc_html__( 'Small text in the top corner of the panel, e.g. "2025" or "rehall.com".', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'link',
			array(
				'label'   => esc_html__( 'Case study link', 'avix-widgets' ),
				'type'    => Controls_Manager::URL,
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'cta_text',
			array(
				'label'       => esc_html__( 'Button text override', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'View case study', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'projects',
			array(
				'label'       => esc_html__( 'Projects', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ name }}}',
				'default'     => $this->default_projects(),
				'condition'   => array( 'source' => 'manual' ),
			)
		);

		$this->add_control(
			'image_size',
			array(
				'label'     => esc_html__( 'Image size', 'avix-widgets' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'full',
				'options'   => $this->image_size_options(),
				'separator' => 'before',
			)
		);

		$this->end_controls_section();
	}

	private function controls_query() {
		$this->start_controls_section(
			'section_query',
			array(
				'label'     => esc_html__( 'Query', 'avix-widgets' ),
				'condition' => array( 'source' => 'query' ),
			)
		);

		$this->add_control(
			'query_help',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Title, featured image, excerpt and permalink come from each post. Map extra fields (ACF or post meta) below.', 'avix-widgets' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->add_control(
			'query_post_type',
			array(
				'label'   => esc_html__( 'Post type', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'post',
				'options' => $this->post_type_options(),
			)
		);

		$this->add_control(
			'query_count',
			array(
				'label'   => esc_html__( 'Number of projects', 'avix-widgets' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 3,
				'min'     => 1,
				'max'     => 12,
			)
		);

		$taxonomies = $this->taxonomy_options();

		$this->add_control(
			'query_taxonomy',
			array(
				'label'   => esc_html__( 'Filter by taxonomy', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => $taxonomies,
			)
		);

		$this->add_control(
			'query_terms',
			array(
				'label'       => esc_html__( 'Term slugs', 'avix-widgets' ),
				'description' => esc_html__( 'Comma separated, e.g. shopify, featured', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'condition'   => array( 'query_taxonomy!' => '' ),
			)
		);

		$this->add_control(
			'query_ids',
			array(
				'label'       => esc_html__( 'Only these post IDs', 'avix-widgets' ),
				'description' => esc_html__( 'Comma separated. Leave empty for the latest posts.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
			)
		);

		$this->add_control(
			'query_orderby',
			array(
				'label'   => esc_html__( 'Order by', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'date',
				'options' => array(
					'date'       => esc_html__( 'Date', 'avix-widgets' ),
					'menu_order' => esc_html__( 'Menu order', 'avix-widgets' ),
					'title'      => esc_html__( 'Title', 'avix-widgets' ),
					'post__in'   => esc_html__( 'Order of IDs above', 'avix-widgets' ),
					'rand'       => esc_html__( 'Random', 'avix-widgets' ),
				),
			)
		);

		$this->add_control(
			'query_order',
			array(
				'label'   => esc_html__( 'Order', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'DESC',
				'options' => array(
					'DESC' => esc_html__( 'Descending', 'avix-widgets' ),
					'ASC'  => esc_html__( 'Ascending', 'avix-widgets' ),
				),
			)
		);

		$this->add_control(
			'query_tags_taxonomy',
			array(
				'label'     => esc_html__( 'Tags come from', 'avix-widgets' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => $taxonomies,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'query_desc_words',
			array(
				'label'   => esc_html__( 'Excerpt length (words)', 'avix-widgets' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 22,
				'min'     => 5,
				'max'     => 60,
			)
		);

		$this->add_control(
			'query_outcome_label_key',
			array(
				'label'       => esc_html__( 'Outcome label field', 'avix-widgets' ),
				'description' => esc_html__( 'Meta key / ACF field name.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'outcome_label',
			)
		);

		$this->add_control(
			'query_outcome_value_key',
			array(
				'label'       => esc_html__( 'Outcome field', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'outcome',
			)
		);

		$this->add_control(
			'query_link_key',
			array(
				'label'       => esc_html__( 'External link field', 'avix-widgets' ),
				'description' => esc_html__( 'Optional. When empty or missing, the post permalink is used.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'case_study_url',
			)
		);

		$this->add_control(
			'query_link_new_tab',
			array(
				'label'     => esc_html__( 'Open external links in new tab', 'avix-widgets' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => '',
				'condition' => array( 'query_link_key!' => '' ),
			)
		);

		$this->add_control(
			'query_year_key',
			array(
				'label'       => esc_html__( 'Year / meta field', 'avix-widgets' ),
				'description' => esc_html__( 'Leave empty to use the publish year.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'project_year',
			)
		);

		$this->end_controls_section();
	}

	private function controls_card_content() {
		$this->start_controls_section(
			'section_card_content',
			array( 'label' => esc_html__( 'Card Content', 'avix-widgets' ) )
		);

		$this->add_control(
			'name_tag',
			array(
				'label'   => esc_html__( 'Project name tag', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h3',
				'options' => $this->heading_tags(),
			)
		);

		$this->add_control(
			'show_index',
			array(
				'label'   => esc_html__( 'Number (01, 02…)', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'show_year',
			array(
				'label'   => esc_html__( 'Meta text', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'show_tags',
			array(
				'label'   => esc_html__( 'Tags', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'show_description',
			array(
				'label'   => esc_html__( 'Description', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'show_outcome',
			array(
				'label'   => esc_html__( 'Outcome row', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'outcome_default_label',
			array(
				'label'     => esc_html__( 'Default outcome label', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Designed for', 'avix-widgets' ),
				'condition' => array( 'show_outcome' => 'yes' ),
			)
		);

		$this->add_control(
			'show_card_cta',
			array(
				'label'   => esc_html__( 'Card button', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'card_cta_text',
			array(
				'label'     => esc_html__( 'Card button text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'View case study', 'avix-widgets' ),
				'condition' => array( 'show_card_cta' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_platforms() {
		$this->start_controls_section(
			'section_platforms',
			array( 'label' => esc_html__( 'Platforms Strip', 'avix-widgets' ) )
		);

		$this->add_control(
			'show_platforms',
			array(
				'label'       => esc_html__( 'Show platforms under the stack', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'platforms_label',
			array(
				'label'     => esc_html__( 'Label', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'We build with', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_platforms' => 'yes' ),
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'brand',
			array(
				'label'   => esc_html__( 'Logo', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'shopify',
				'options' => array_merge( wp_list_pluck( self::BRANDS, 'label' ), array( 'custom' => esc_html__( 'Upload my own', 'avix-widgets' ) ) ),
			)
		);

		$repeater->add_control(
			'logo',
			array(
				'label'       => esc_html__( 'Logo image', 'avix-widgets' ),
				'description' => esc_html__( 'A small square mark works best (SVG or PNG).', 'avix-widgets' ),
				'type'        => Controls_Manager::MEDIA,
				'condition'   => array( 'brand' => 'custom' ),
			)
		);

		$repeater->add_control(
			'name',
			array(
				'label'   => esc_html__( 'Name', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => 'Shopify',
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
			'platforms',
			array(
				'label'       => esc_html__( 'Platforms', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ name }}}',
				'default'     => array(
					array(
						'brand' => 'shopify',
						'name'  => 'Shopify',
						'link'  => array( 'url' => 'https://avixdigital.com/service/shopify-plus/' ),
					),
					array(
						'brand' => 'wordpress',
						'name'  => 'WordPress',
						'link'  => array( 'url' => 'https://avixdigital.com/service/wordpress-development/' ),
					),
					array(
						'brand' => 'webflow',
						'name'  => 'Webflow',
					),
					array(
						'brand' => 'react',
						'name'  => 'React',
						'link'  => array( 'url' => 'https://avixdigital.com/service/web-development/' ),
					),
					array(
						'brand' => 'nextjs',
						'name'  => 'Next.js',
						'link'  => array( 'url' => 'https://avixdigital.com/service/web-development/' ),
					),
					array(
						'brand' => 'nodejs',
						'name'  => 'Node.js',
						'link'  => array( 'url' => 'https://avixdigital.com/service/web-development/' ),
					),
				),
				'condition'   => array( 'show_platforms' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_behaviour() {
		$this->start_controls_section(
			'section_behaviour',
			array( 'label' => esc_html__( 'Motion & Behaviour', 'avix-widgets' ) )
		);

		$this->add_control(
			'motion',
			array(
				'label'       => esc_html__( '3D stack motion', 'avix-widgets' ),
				'description' => esc_html__( 'Cards tilt back and shrink as the next one slides over. Visitors who prefer reduced motion always get a still stack.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'motion_intensity',
			array(
				'label'     => esc_html__( 'Motion intensity', 'avix-widgets' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 20, 'max' => 150, 'step' => 5 ) ),
				'default'   => array( 'size' => 100 ),
				'condition' => array( 'motion' => 'yes' ),
			)
		);

		$this->add_control(
			'parallax',
			array(
				'label'     => esc_html__( 'Image parallax', 'avix-widgets' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'motion' => 'yes' ),
			)
		);

		$this->add_control(
			'dim',
			array(
				'label'       => esc_html__( 'Dim covered cards', 'avix-widgets' ),
				'description' => esc_html__( 'Darkens a card as the next one covers it (0 = off).', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 80, 'step' => 2 ) ),
				'default'     => array( 'size' => 40 ),
			)
		);

		$this->add_control(
			'panel_position',
			array(
				'label'     => esc_html__( 'Info panel position', 'avix-widgets' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'right',
				'options'   => array(
					'right'     => esc_html__( 'Right', 'avix-widgets' ),
					'left'      => esc_html__( 'Left', 'avix-widgets' ),
					'alternate' => esc_html__( 'Alternate', 'avix-widgets' ),
				),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'cursor',
			array(
				'label'       => esc_html__( 'Cursor bubble on images', 'avix-widgets' ),
				'description' => esc_html__( 'Desktop mouse only. Hidden inside the editor.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'cursor_text',
			array(
				'label'     => esc_html__( 'Cursor text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'View case', 'avix-widgets' ),
				'condition' => array( 'cursor' => 'yes' ),
			)
		);

		$this->add_control(
			'heading_offsets',
			array(
				'label'     => esc_html__( 'Fixed header clearance', 'avix-widgets' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'offset_selectors',
			array(
				'label'       => esc_html__( 'Fixed elements to clear', 'avix-widgets' ),
				'description' => esc_html__( 'CSS selectors of fixed bars at the top. The stack moves down while they are visible and back up when they hide.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => self::DEFAULT_OFFSET_SELECTORS,
				'label_block' => true,
			)
		);

		$this->add_control(
			'offset_extra',
			array(
				'label'   => esc_html__( 'Extra top offset (px)', 'avix-widgets' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 0,
				'min'     => 0,
				'max'     => 300,
			)
		);

		$this->add_control(
			'fix_overflow',
			array(
				'label'       => esc_html__( 'Auto-fix parent overflow', 'avix-widgets' ),
				'description' => esc_html__( 'Sticky scrolling stops working inside containers set to Overflow: Hidden. This switches those Elementor containers to "clip", which looks the same.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->end_controls_section();
	}

	/* ---------- Style ---------- */

	private function style_section() {
		$this->start_controls_section(
			'style_section',
			array(
				'label' => esc_html__( 'Section', 'avix-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'section_bg',
			array(
				'label'     => esc_html__( 'Background', 'avix-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .avix-work' => '--aw-bg: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'accent_color',
			array(
				'label'     => esc_html__( 'Accent', 'avix-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .avix-work' => '--aw-accent: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'accent_text_color',
			array(
				'label'       => esc_html__( 'Accent (small text)', 'avix-widgets' ),
				'description' => esc_html__( 'A darker orange keeps small text readable on white.', 'avix-widgets' ),
				'type'        => Controls_Manager::COLOR,
				'selectors'   => array( '{{WRAPPER}} .avix-work' => '--aw-accent-text: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'section_padding',
			array(
				'label'      => esc_html__( 'Padding', 'avix-widgets' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'vh' ),
				'allowed_dimensions' => 'vertical',
				'selectors'  => array( '{{WRAPPER}} .avix-work' => 'padding-top: {{TOP}}{{UNIT}}; padding-bottom: {{BOTTOM}}{{UNIT}};' ),
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
				'selectors'  => array( '{{WRAPPER}} .avix-work' => '--aw-max: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'side_gutter',
			array(
				'label'      => esc_html__( 'Side spacing', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-work' => '--aw-gutter: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	private function style_header() {
		$this->start_controls_section(
			'style_header',
			array(
				'label'     => esc_html__( 'Header', 'avix-widgets' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_header' => 'yes' ),
			)
		);

		$this->add_control(
			'kicker_color',
			array(
				'label'     => esc_html__( 'Kicker colour', 'avix-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .avix-work__kicker' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'kicker_typography',
				'label'    => esc_html__( 'Kicker typography', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-work__kicker',
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => esc_html__( 'Title colour', 'avix-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .avix-work' => '--aw-ink: {{VALUE}};' ),
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'label'    => esc_html__( 'Title typography', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-work__title',
			)
		);

		$this->add_responsive_control(
			'head_height',
			array(
				'label'      => esc_html__( 'Header height', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 60, 'max' => 240 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-work' => '--aw-head-h: {{SIZE}}{{UNIT}};' ),
				'separator'  => 'before',
			)
		);

		$this->add_responsive_control(
			'head_gap',
			array(
				'label'      => esc_html__( 'Space below header', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 160 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-work' => '--aw-head-gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'heading_cta_style',
			array(
				'label'     => esc_html__( 'Button', 'avix-widgets' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'cta_bg',
			array(
				'label'     => esc_html__( 'Background', 'avix-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .avix-work' => '--aw-cta-bg: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'cta_color',
			array(
				'label'     => esc_html__( 'Text', 'avix-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .avix-work' => '--aw-cta-ink: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'cta_typography',
				'selector' => '{{WRAPPER}} .avix-work__cta',
			)
		);

		$this->end_controls_section();
	}

	private function style_cards() {
		$this->start_controls_section(
			'style_cards',
			array(
				'label' => esc_html__( 'Cards', 'avix-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'card_height',
			array(
				'label'       => esc_html__( 'Card height (wide layout)', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', 'vh' ),
				'range'       => array(
					'px' => array( 'min' => 360, 'max' => 900 ),
					'vh' => array( 'min' => 40, 'max' => 90 ),
				),
				'selectors'   => array( '{{WRAPPER}} .avix-work' => '--aw-card-h: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'card_height_stacked',
			array(
				'label'       => esc_html__( 'Card height (stacked layout)', 'avix-widgets' ),
				'description' => esc_html__( 'Used when the widget is narrower than 900px. Always capped to the screen height.', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 420, 'max' => 900 ) ),
				'selectors'   => array( '{{WRAPPER}} .avix-work' => '--aw-card-h-stacked: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'card_gap',
			array(
				'label'       => esc_html__( 'Scroll distance between cards', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', 'vh' ),
				'range'       => array(
					'px' => array( 'min' => 24, 'max' => 400 ),
					'vh' => array( 'min' => 5, 'max' => 60 ),
				),
				'selectors'   => array( '{{WRAPPER}} .avix-work' => '--aw-gap: {{SIZE}}{{UNIT}}; --aw-gap-stacked: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'release_space',
			array(
				'label'       => esc_html__( 'Hold after last card', 'avix-widgets' ),
				'description' => esc_html__( 'Extra scroll before the stack leaves the screen.', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', 'vh' ),
				'range'       => array(
					'px' => array( 'min' => 0, 'max' => 600 ),
					'vh' => array( 'min' => 0, 'max' => 80 ),
				),
				'selectors'   => array( '{{WRAPPER}} .avix-work' => '--aw-release: {{SIZE}}{{UNIT}}; --aw-release-stacked: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'card_radius',
			array(
				'label'      => esc_html__( 'Corner radius', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-work' => '--aw-radius: {{SIZE}}{{UNIT}};' ),
				'separator'  => 'before',
			)
		);

		$this->add_control(
			'card_border',
			array(
				'label'     => esc_html__( 'Border colour', 'avix-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .avix-work' => '--aw-line: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'card_bg',
			array(
				'label'     => esc_html__( 'Image backdrop', 'avix-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .avix-work' => '--aw-card-bg: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_shadow',
				'selector' => '{{WRAPPER}} .avix-work-card',
			)
		);

		$this->end_controls_section();
	}

	private function style_panel() {
		$this->start_controls_section(
			'style_panel',
			array(
				'label' => esc_html__( 'Info Panel', 'avix-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'panel_bg',
			array(
				'label'     => esc_html__( 'Background', 'avix-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .avix-work' => '--aw-panel-bg: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'panel_glow',
			array(
				'label'     => esc_html__( 'Corner glow', 'avix-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .avix-work' => '--aw-panel-glow: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'panel_width',
			array(
				'label'      => esc_html__( 'Max width', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 340, 'max' => 640 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-work' => '--aw-panel-w: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'panel_inset',
			array(
				'label'      => esc_html__( 'Inset from card edge', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 48 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-work' => '--aw-panel-inset: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'panel_radius',
			array(
				'label'      => esc_html__( 'Corner radius', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 48 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-work' => '--aw-panel-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'heading_panel_name',
			array(
				'label'     => esc_html__( 'Project name', 'avix-widgets' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'name_color',
			array(
				'label'     => esc_html__( 'Colour', 'avix-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .avix-work' => '--aw-panel-ink: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'name_typography',
				'selector' => '{{WRAPPER}} .avix-work .avix-work-card__name',
			)
		);

		$this->add_control(
			'heading_panel_text',
			array(
				'label'     => esc_html__( 'Description & tags', 'avix-widgets' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'desc_color',
			array(
				'label'     => esc_html__( 'Description colour', 'avix-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .avix-work' => '--aw-panel-muted: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'desc_typography',
				'selector' => '{{WRAPPER}} .avix-work .avix-work-card__desc',
			)
		);

		$this->add_control(
			'tag_bg',
			array(
				'label'     => esc_html__( 'Tag background', 'avix-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .avix-work' => '--aw-panel-soft: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'tag_color',
			array(
				'label'     => esc_html__( 'Tag text', 'avix-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .avix-work .avix-work-card__tags li' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'heading_panel_outcome',
			array(
				'label'     => esc_html__( 'Outcome', 'avix-widgets' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'outcome_color',
			array(
				'label'     => esc_html__( 'Outcome colour', 'avix-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .avix-work' => '--aw-panel-accent: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'outcome_typography',
				'selector' => '{{WRAPPER}} .avix-work-card__outcome-value',
			)
		);

		$this->add_control(
			'panel_line',
			array(
				'label'     => esc_html__( 'Divider colour', 'avix-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .avix-work' => '--aw-panel-line: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	private function style_platforms() {
		$this->start_controls_section(
			'style_platforms',
			array(
				'label'     => esc_html__( 'Platforms Strip', 'avix-widgets' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_platforms' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'platforms_spacing',
			array(
				'label'      => esc_html__( 'Space above', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 200 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-work__platforms' => 'margin-top: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'platforms_color',
			array(
				'label'     => esc_html__( 'Colour', 'avix-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .avix-work__platforms' => '--aw-platform-ink: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'platforms_logo_colour',
			array(
				'label'   => esc_html__( 'Logo colours', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'brand',
				'options' => array(
					'brand' => esc_html__( 'Brand colours', 'avix-widgets' ),
					'hover' => esc_html__( 'Grey, brand colour on hover', 'avix-widgets' ),
					'mono'  => esc_html__( 'Grey', 'avix-widgets' ),
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'platforms_typography',
				'label'    => esc_html__( 'Names', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-work__platform',
			)
		);

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ */
	/* Render                                                              */
	/* ------------------------------------------------------------------ */

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$items    = 'query' === $settings['source'] ? $this->query_items( $settings ) : $this->manual_items( $settings );
		$total    = count( $items );

		if ( ! $total ) {
			if ( $this->is_editor() ) {
				echo '<div class="elementor-alert elementor-alert-info">' . esc_html__( 'Selected Work: no projects to show yet. Add projects or adjust the query.', 'avix-widgets' ) . '</div>';
			}
			return;
		}

		$show_header = 'yes' === $settings['show_header'];
		$sticky_head = $show_header && 'yes' === $settings['sticky_header'];
		$panel       = in_array( $settings['panel_position'], array( 'right', 'left', 'alternate' ), true ) ? $settings['panel_position'] : 'right';
		$title_id    = 'avix-work-title-' . $this->get_id();

		$config = array(
			'motion'          => 'yes' === $settings['motion'],
			'intensity'       => round( $this->slider_size( $settings, 'motion_intensity', 100 ) / 100, 2 ),
			'parallax'        => 'yes' === $settings['parallax'],
			'dim'             => round( $this->slider_size( $settings, 'dim', 40 ) / 100, 2 ),
			'releaseHead'     => $sticky_head,
			'offsetSelectors' => sanitize_text_field( (string) $settings['offset_selectors'] ),
			'offsetExtra'     => max( 0, (int) $settings['offset_extra'] ),
			'fixOverflow'     => 'yes' === $settings['fix_overflow'],
			'cursor'          => 'yes' === $settings['cursor'] && ! $this->is_editor(),
			'cursorText'      => sanitize_text_field( (string) $settings['cursor_text'] ),
		);

		$classes = array( 'avix-work', 'avix-work--panel-' . $panel );
		if ( ! $sticky_head ) {
			$classes[] = 'avix-work--static-head';
		}
		$classes[] = 'avix-work--logos-' . ( in_array( $settings['platforms_logo_colour'], array( 'brand', 'hover', 'mono' ), true ) ? $settings['platforms_logo_colour'] : 'brand' );

		$this->add_render_attribute(
			'root',
			array(
				'class'          => $classes,
				'data-avix-work' => wp_json_encode( $config ),
			)
		);
		if ( $show_header && '' !== trim( (string) $settings['title'] ) ) {
			$this->add_render_attribute( 'root', 'aria-labelledby', $title_id );
		}
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="avix-work__frame">
				<?php
				if ( $show_header ) {
					$this->render_header( $settings, $total, $title_id, $sticky_head );
				}
				?>
				<div class="avix-work__stack" data-aw-stack>
					<?php
					foreach ( $items as $index => $item ) {
						$this->render_card( $settings, $item, $index, $total );
					}
					?>
				</div>
				<?php
				if ( 'yes' === $settings['show_platforms'] ) {
					$this->render_platforms( $settings );
				}
				?>
			</div>
		</section>
		<?php
	}

	private function render_header( array $settings, $total, $title_id, $sticky_head ) {
		$tag     = Utils::validate_html_tag( $settings['title_tag'] );
		$has_cta = '' !== trim( (string) $settings['cta_text'] ) && ! empty( $settings['cta_link']['url'] );

		if ( $has_cta ) {
			$this->add_link_attributes( 'cta', $settings['cta_link'] );
			$this->add_render_attribute( 'cta', 'class', 'avix-work__cta' );
		}
		?>
		<header class="avix-work__head" data-aw-head>
			<div class="avix-work__heading">
				<?php if ( '' !== trim( (string) $settings['kicker'] ) ) : ?>
					<p class="avix-work__kicker"><?php echo esc_html( $settings['kicker'] ); ?></p>
				<?php endif; ?>
				<<?php echo esc_attr( $tag ); ?> class="avix-work__title" id="<?php echo esc_attr( $title_id ); ?>">
					<?php echo esc_html( $settings['title'] ); ?>
					<?php if ( '' !== trim( (string) $settings['title_accent'] ) ) : ?>
						<span class="avix-work__title-accent"><?php echo esc_html( $settings['title_accent'] ); ?></span>
					<?php endif; ?>
					<?php if ( 'yes' === $settings['show_count'] ) : ?>
						<sup class="avix-work__count" aria-hidden="true">(<?php echo esc_html( sprintf( '%02d', $total ) ); ?>)</sup>
					<?php endif; ?>
				</<?php echo esc_attr( $tag ); ?>>
			</div>
			<?php if ( $has_cta || ( $sticky_head && 'yes' === $settings['show_progress'] && $total > 1 ) ) : ?>
				<div class="avix-work__aside">
					<?php if ( $sticky_head && 'yes' === $settings['show_progress'] && $total > 1 ) : ?>
						<div class="avix-work__progress" aria-hidden="true">
							<span class="avix-work__progress-current" data-aw-current>01</span>
							<span class="avix-work__progress-track"><span class="avix-work__progress-bar" data-aw-bar></span></span>
							<span class="avix-work__progress-total"><?php echo esc_html( sprintf( '%02d', $total ) ); ?></span>
						</div>
					<?php endif; ?>
					<?php if ( $has_cta ) : ?>
						<a <?php $this->print_render_attribute_string( 'cta' ); ?>>
							<?php echo esc_html( $settings['cta_text'] ); ?>
							<span class="avix-work__cta-icon" aria-hidden="true"><?php $this->arrow_icon(); ?></span>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</header>
		<?php
	}

	private function render_card( array $settings, array $item, $index, $total ) {
		$number   = sprintf( '%02d', $index + 1 );
		$card_key = 'card-' . $index;
		$link_key = 'surface-' . $index;
		$has_link = ! empty( $item['link']['url'] );
		$name_tag = Utils::validate_html_tag( $settings['name_tag'] );
		$cta_text = '' !== $item['cta'] ? $item['cta'] : (string) $settings['card_cta_text'];

		$this->add_render_attribute(
			$card_key,
			array(
				'class'        => 'avix-work-card',
				'data-aw-card' => '',
				'style'        => sprintf( '--aw-i:%d;--aw-pos:%s;', $index, $item['focus'] ),
			)
		);

		if ( $has_link ) {
			$this->add_link_attributes( $link_key, $item['link'] );
			$this->add_render_attribute(
				$link_key,
				array(
					'class'          => 'avix-work-card__surface',
					'data-aw-surface' => '',
					/* translators: %s: project name. */
					'aria-label'     => sprintf( __( 'View case study: %s', 'avix-widgets' ), $item['name'] ),
				)
			);
		} else {
			$this->add_render_attribute( $link_key, 'class', 'avix-work-card__surface' );
		}

		$surface_tag = $has_link ? 'a' : 'div';
		$tags        = 'yes' === $settings['show_tags'] ? $item['tags'] : array();
		$show_year   = 'yes' === $settings['show_year'] && '' !== $item['year'];
		$show_index  = 'yes' === $settings['show_index'];
		$outcome     = 'yes' === $settings['show_outcome'] && '' !== $item['outcome_value'];
		$show_cta    = 'yes' === $settings['show_card_cta'] && $has_link && '' !== trim( $cta_text );
		?>
		<article <?php $this->print_render_attribute_string( $card_key ); ?>>
			<<?php echo esc_attr( $surface_tag ); ?> <?php $this->print_render_attribute_string( $link_key ); ?>>
				<div class="avix-work-card__media" data-aw-media>
					<?php
					$image = $this->image_html( $item, $settings['image_size'] );
					if ( $image ) {
						echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in image_html().
					} else {
						echo '<span class="avix-work-card__placeholder" aria-hidden="true">' . esc_html( $this->initials( $item['name'] ) ) . '</span>';
					}
					?>
				</div>
				<div class="avix-work-card__panel">
					<?php if ( $show_index || $show_year ) : ?>
						<div class="avix-work-card__meta">
							<?php if ( $show_index ) : ?>
								<span class="avix-work-card__index"><?php echo esc_html( $number ); ?><span> / <?php echo esc_html( sprintf( '%02d', $total ) ); ?></span></span>
							<?php endif; ?>
							<?php if ( $show_year ) : ?>
								<span class="avix-work-card__year"><?php echo esc_html( $item['year'] ); ?></span>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<<?php echo esc_attr( $name_tag ); ?> class="avix-work-card__name"><?php echo esc_html( $item['name'] ); ?></<?php echo esc_attr( $name_tag ); ?>>

					<?php if ( $tags ) : ?>
						<ul class="avix-work-card__tags">
							<?php foreach ( $tags as $tag ) : ?>
								<li><?php echo esc_html( $tag ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<?php if ( 'yes' === $settings['show_description'] && '' !== $item['description'] ) : ?>
						<p class="avix-work-card__desc"><?php echo wp_kses( $item['description'], $this->inline_tags() ); ?></p>
					<?php endif; ?>

					<?php if ( $outcome || $show_cta ) : ?>
						<div class="avix-work-card__foot">
							<?php if ( $outcome ) : ?>
								<div class="avix-work-card__outcome">
									<span class="avix-work-card__outcome-label"><?php echo esc_html( '' !== $item['outcome_label'] ? $item['outcome_label'] : $settings['outcome_default_label'] ); ?></span>
									<strong class="avix-work-card__outcome-value"><?php echo esc_html( $item['outcome_value'] ); ?></strong>
								</div>
							<?php endif; ?>
							<?php if ( $show_cta ) : ?>
								<span class="avix-work-card__cta">
									<?php echo esc_html( $cta_text ); ?>
									<span class="avix-work-card__cta-icon" aria-hidden="true"><?php $this->arrow_icon(); ?></span>
								</span>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</<?php echo esc_attr( $surface_tag ); ?>>
		</article>
		<?php
	}

	private function render_platforms( array $settings ) {
		$rows = array_filter(
			(array) $settings['platforms'],
			static function ( $row ) {
				return '' !== trim( (string) ( $row['name'] ?? '' ) ) || 'custom' !== ( $row['brand'] ?? '' );
			}
		);
		if ( ! $rows ) {
			return;
		}
		$label = trim( (string) $settings['platforms_label'] );
		?>
		<div class="avix-work__platforms">
			<?php if ( '' !== $label ) : ?>
				<p class="avix-work__platforms-label"><?php echo esc_html( $label ); ?></p>
			<?php endif; ?>
			<ul class="avix-work__platforms-list">
				<?php
				foreach ( array_values( $rows ) as $index => $row ) {
					$brand = isset( self::BRANDS[ $row['brand'] ?? '' ] ) ? $row['brand'] : 'custom';
					$name  = trim( (string) ( $row['name'] ?? '' ) );
					$key   = 'platform-' . $index;
					$tag   = ! empty( $row['link']['url'] ) ? 'a' : 'span';

					$this->add_render_attribute( $key, 'class', 'avix-work__platform' );
					if ( 'custom' !== $brand ) {
						$this->add_render_attribute( $key, 'style', '--aw-brand:' . self::BRANDS[ $brand ]['color'] . ';' );
					}
					if ( 'a' === $tag ) {
						$this->add_link_attributes( $key, $row['link'] );
					}
					?>
					<li>
						<<?php echo esc_attr( $tag ); ?> <?php $this->print_render_attribute_string( $key ); ?>>
							<?php
							if ( 'custom' !== $brand ) {
								printf( '<svg class="avix-work__platform-mark" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="%s"/></svg>', esc_attr( self::BRANDS[ $brand ]['path'] ) );
							} elseif ( ! empty( $row['logo']['url'] ) ) {
								printf( '<img class="avix-work__platform-mark" src="%s" alt="" loading="lazy" decoding="async">', esc_url( $row['logo']['url'] ) );
							}
							echo esc_html( $name );
							?>
						</<?php echo esc_attr( $tag ); ?>>
					</li>
					<?php
				}
				?>
			</ul>
		</div>
		<?php
	}

	/* ------------------------------------------------------------------ */
	/* Data                                                                */
	/* ------------------------------------------------------------------ */

	/**
	 * Normalises repeater rows into the shape render_card() expects.
	 */
	private function manual_items( array $settings ) {
		$items = array();
		foreach ( (array) $settings['projects'] as $row ) {
			$name = trim( (string) ( $row['name'] ?? '' ) );
			if ( '' === $name ) {
				continue;
			}
			$image = is_array( $row['image'] ?? null ) ? $row['image'] : array();
			$items[] = array(
				'name'          => $name,
				'image_id'      => absint( $image['id'] ?? 0 ),
				'image_url'     => (string) ( $image['url'] ?? '' ),
				'focus'         => $this->focus( $row ),
				'tags'          => $this->split_tags( (string) ( $row['tags'] ?? '' ) ),
				'description'   => trim( (string) ( $row['description'] ?? '' ) ),
				'outcome_label' => trim( (string) ( $row['outcome_label'] ?? '' ) ),
				'outcome_value' => trim( (string) ( $row['outcome_value'] ?? '' ) ),
				'year'          => trim( (string) ( $row['year'] ?? '' ) ),
				'link'          => is_array( $row['link'] ?? null ) ? $row['link'] : array(),
				'cta'           => trim( (string) ( $row['cta_text'] ?? '' ) ),
			);
		}
		return $items;
	}

	private function query_items( array $settings ) {
		$post_type = sanitize_key( $settings['query_post_type'] ? $settings['query_post_type'] : 'post' );
		if ( ! post_type_exists( $post_type ) ) {
			return array();
		}

		$orderby = in_array( $settings['query_orderby'], array( 'date', 'menu_order', 'title', 'post__in', 'rand' ), true ) ? $settings['query_orderby'] : 'date';
		$args    = array(
			'post_type'           => $post_type,
			'post_status'         => 'publish',
			'posts_per_page'      => max( 1, min( 12, (int) $settings['query_count'] ) ),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => $orderby,
			'order'               => 'ASC' === $settings['query_order'] ? 'ASC' : 'DESC',
		);

		$ids = wp_parse_id_list( (string) $settings['query_ids'] );
		if ( $ids ) {
			$args['post__in'] = $ids;
		} elseif ( 'post__in' === $orderby ) {
			$args['orderby'] = 'date';
		}

		$taxonomy = sanitize_key( (string) $settings['query_taxonomy'] );
		$terms    = array_filter( array_map( 'sanitize_title', explode( ',', (string) $settings['query_terms'] ) ) );
		if ( $taxonomy && $terms && taxonomy_exists( $taxonomy ) ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => $terms,
				),
			);
		}

		$query     = new \WP_Query( $args );
		$tag_tax   = sanitize_key( (string) $settings['query_tags_taxonomy'] );
		$words     = max( 5, min( 60, (int) $settings['query_desc_words'] ) );
		$new_tab   = 'yes' === $settings['query_link_new_tab'];
		$items     = array();

		foreach ( $query->posts as $post ) {
			$meta = static function ( $key ) use ( $post ) {
				$key = trim( (string) $key );
				if ( '' === $key ) {
					return '';
				}
				$value = get_post_meta( $post->ID, $key, true );
				return is_scalar( $value ) ? trim( (string) $value ) : '';
			};

			$tags = array();
			if ( $tag_tax && taxonomy_exists( $tag_tax ) ) {
				$terms = get_the_terms( $post, $tag_tax );
				if ( $terms && ! is_wp_error( $terms ) ) {
					$tags = array_slice( wp_list_pluck( $terms, 'name' ), 0, 4 );
				}
			}

			$external = $meta( $settings['query_link_key'] );
			$link     = array(
				'url'         => $external ? $external : get_permalink( $post ),
				'is_external' => $external && $new_tab ? 'on' : '',
				'nofollow'    => '',
			);

			$year = $meta( $settings['query_year_key'] );
			if ( '' === $year ) {
				$year = get_the_date( 'Y', $post );
			}

			$items[] = array(
				'name'          => get_the_title( $post ),
				'image_id'      => (int) get_post_thumbnail_id( $post ),
				'image_url'     => '',
				'focus'         => '50% 50%',
				'tags'          => $tags,
				'description'   => esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post ) ), $words ) ),
				'outcome_label' => $meta( $settings['query_outcome_label_key'] ),
				'outcome_value' => $meta( $settings['query_outcome_value_key'] ),
				'year'          => (string) $year,
				'link'          => $link,
				'cta'           => '',
			);
		}

		return $items;
	}

	private function default_projects() {
		return array(
			array(
				'name'          => 'OvaBalance',
				'image'         => array( 'url' => 'https://avixdigital.com/wp-content/uploads/2025/12/Untitled-design-7.webp' ),
				'tags'          => 'Shopify, Liquid, Subscription',
				'description'   => 'A custom Shopify subscription experience with dynamic pricing, recurring order integration and a modular Liquid theme.',
				'outcome_label' => 'Designed for',
				'outcome_value' => 'Repeat purchase',
				'link'          => array( 'url' => 'https://akib.avixdigital.com/ovabalance-eu/' ),
			),
			array(
				'name'          => 'Rehall',
				'image'         => array( 'url' => 'https://avixdigital.com/wp-content/uploads/2025/12/rehall.webp' ),
				'focus_y'       => array( 'unit' => '%', 'size' => 35 ),
				'tags'          => 'Shopify, Theme engineering',
				'description'   => 'Custom theme engineering and third party integrations shape a responsive storefront built for confident product discovery.',
				'outcome_label' => 'Designed for',
				'outcome_value' => 'Faster discovery',
				'link'          => array( 'url' => 'https://akib.avixdigital.com/rehall-com/' ),
			),
			array(
				'name'          => 'Flow Storage',
				'image'         => array( 'url' => 'https://avixdigital.com/wp-content/uploads/2025/12/Untitled-design-6.webp' ),
				'tags'          => 'Webflow, CMS, Lead generation',
				'description'   => 'A precise Figma to Webflow build with custom CMS architecture and lead forms tuned for a clear customer journey.',
				'outcome_label' => 'Designed for',
				'outcome_value' => 'Qualified leads',
				'link'          => array( 'url' => 'https://akib.avixdigital.com/flow-storage/' ),
			),
		);
	}

	/* ------------------------------------------------------------------ */
	/* Helpers                                                             */
	/* ------------------------------------------------------------------ */

	private function image_html( array $item, $size ) {
		$alt = $item['name'];

		if ( $item['image_id'] ) {
			$stored = get_post_meta( $item['image_id'], '_wp_attachment_image_alt', true );
			$html   = wp_get_attachment_image(
				$item['image_id'],
				$size ? $size : 'full',
				false,
				array(
					'class'       => 'avix-work-card__img',
					'alt'         => $stored ? $stored : $alt,
					'loading'     => 'lazy',
					'decoding'    => 'async',
					'sizes'       => '(min-width: 1248px) 1200px, calc(100vw - 32px)',
					'data-aw-img' => '',
				)
			);
			if ( $html ) {
				return $html;
			}
		}

		if ( '' !== $item['image_url'] ) {
			return sprintf(
				'<img class="avix-work-card__img" src="%s" alt="%s" loading="lazy" decoding="async" data-aw-img>',
				esc_url( $item['image_url'] ),
				esc_attr( $alt )
			);
		}

		return '';
	}

	private function focus( array $row ) {
		$x = isset( $row['focus_x']['size'] ) && '' !== $row['focus_x']['size'] ? (float) $row['focus_x']['size'] : 50;
		$y = isset( $row['focus_y']['size'] ) && '' !== $row['focus_y']['size'] ? (float) $row['focus_y']['size'] : 50;
		return sprintf( '%s%% %s%%', max( 0, min( 100, $x ) ), max( 0, min( 100, $y ) ) );
	}

	private function split_tags( $raw ) {
		$tags = array_map( 'trim', explode( ',', $raw ) );
		return array_values( array_filter( $tags, 'strlen' ) );
	}

	private function initials( $name ) {
		$words = preg_split( '/\s+/', trim( $name ) );
		$out   = '';
		foreach ( array_slice( (array) $words, 0, 2 ) as $word ) {
			$out .= function_exists( 'mb_substr' ) ? mb_substr( $word, 0, 1 ) : substr( $word, 0, 1 );
		}
		return $out;
	}

	private function slider_size( array $settings, $key, $fallback ) {
		return isset( $settings[ $key ]['size'] ) && '' !== $settings[ $key ]['size'] ? (float) $settings[ $key ]['size'] : $fallback;
	}

	private function is_editor() {
		return \Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode();
	}

	private function arrow_icon() {
		echo '<svg class="avix-work__icon" viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M7 17 17 7"/><path d="M8 7h9v9"/></svg>';
	}

	private function inline_tags() {
		return array(
			'br'     => array(),
			'strong' => array(),
			'b'      => array(),
			'em'     => array(),
			'i'      => array(),
			'span'   => array( 'class' => array() ),
		);
	}

	private function heading_tags() {
		return array(
			'h1'  => 'H1',
			'h2'  => 'H2',
			'h3'  => 'H3',
			'h4'  => 'H4',
			'h5'  => 'H5',
			'h6'  => 'H6',
			'div' => 'div',
			'p'   => 'p',
		);
	}

	private function image_size_options() {
		$options = array( 'full' => esc_html__( 'Full', 'avix-widgets' ) );
		if ( function_exists( 'wp_get_registered_image_subsizes' ) ) {
			foreach ( wp_get_registered_image_subsizes() as $name => $size ) {
				$options[ $name ] = sprintf( '%s (%d×%d)', ucwords( str_replace( array( '_', '-' ), ' ', $name ) ), $size['width'], $size['height'] );
			}
		}
		return $options;
	}

	private function post_type_options() {
		$options = array();
		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $type ) {
			if ( in_array( $type->name, array( 'attachment', 'elementor_library', 'e-landing-page', 'e-floating-buttons' ), true ) ) {
				continue;
			}
			$options[ $type->name ] = $type->labels->singular_name;
		}
		return $options;
	}

	private function taxonomy_options() {
		$options = array( '' => esc_html__( 'None', 'avix-widgets' ) );
		foreach ( get_taxonomies( array( 'public' => true ), 'objects' ) as $taxonomy ) {
			$options[ $taxonomy->name ] = $taxonomy->labels->singular_name . ' (' . $taxonomy->name . ')';
		}
		return $options;
	}
}
