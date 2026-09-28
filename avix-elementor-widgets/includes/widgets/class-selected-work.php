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
	 * Platform marks from Simple Icons (CC0) with each brand's own colour.
	 */
	const BRANDS = array(
		'shopify' => array(
			'label' => 'Shopify',
			'color' => '#95BF47',
			'path'  => 'M15.337 23.979l7.216-1.561s-2.604-17.613-2.625-17.73c-.018-.116-.114-.192-.211-.192s-1.929-.136-1.929-.136-1.275-1.274-1.439-1.411c-.045-.037-.075-.057-.121-.074l-.914 21.104h.023zM11.71 11.305s-.81-.424-1.774-.424c-1.447 0-1.504.906-1.504 1.141 0 1.232 3.24 1.715 3.24 4.629 0 2.295-1.44 3.76-3.406 3.76-2.354 0-3.54-1.465-3.54-1.465l.646-2.086s1.245 1.066 2.28 1.066c.675 0 .975-.545.975-.932 0-1.619-2.654-1.694-2.654-4.359-.034-2.237 1.571-4.416 4.827-4.416 1.257 0 1.875.361 1.875.361l-.945 2.715-.02.01zM11.17.83c.136 0 .271.038.405.135-.984.465-2.064 1.639-2.508 3.992-.656.213-1.293.405-1.889.578C7.697 3.75 8.951.84 11.17.84V.83zm1.235 2.949v.135c-.754.232-1.583.484-2.394.736.466-1.777 1.333-2.645 2.085-2.971.193.501.309 1.176.309 2.1zm.539-2.234c.694.074 1.141.867 1.429 1.755-.349.114-.735.231-1.158.366v-.252c0-.752-.096-1.371-.271-1.871v.002zm2.992 1.289c-.02 0-.06.021-.078.021s-.289.075-.714.21c-.423-1.233-1.176-2.37-2.508-2.37h-.115C12.135.209 11.669 0 11.265 0 8.159 0 6.675 3.877 6.21 5.846c-1.194.365-2.063.636-2.16.674-.675.213-.694.232-.772.87-.075.462-1.83 14.063-1.83 14.063L15.009 24l.927-21.166z',
		),
		'wordpress' => array(
			'label' => 'WordPress',
			'color' => '#21759B',
			'path'  => 'M21.469 6.825c.84 1.537 1.318 3.3 1.318 5.175 0 3.979-2.156 7.456-5.363 9.325l3.295-9.527c.615-1.54.82-2.771.82-3.864 0-.405-.026-.78-.07-1.11m-7.981.105c.647-.03 1.232-.105 1.232-.105.582-.075.514-.93-.067-.899 0 0-1.755.135-2.88.135-1.064 0-2.85-.15-2.85-.15-.585-.03-.661.855-.075.885 0 0 .54.061 1.125.09l1.68 4.605-2.37 7.08L5.354 6.9c.649-.03 1.234-.1 1.234-.1.585-.075.516-.93-.065-.896 0 0-1.746.138-2.874.138-.2 0-.438-.008-.69-.015C4.911 3.15 8.235 1.215 12 1.215c2.809 0 5.365 1.072 7.286 2.833-.046-.003-.091-.009-.141-.009-1.06 0-1.812.923-1.812 1.914 0 .89.513 1.643 1.06 2.531.411.72.89 1.643.89 2.977 0 .915-.354 1.994-.821 3.479l-1.075 3.585-3.9-11.61.001.014zM12 22.784c-1.059 0-2.081-.153-3.048-.437l3.237-9.406 3.315 9.087c.024.053.05.101.078.149-1.12.393-2.325.609-3.582.609M1.211 12c0-1.564.336-3.05.935-4.39L7.29 21.709C3.694 19.96 1.212 16.271 1.211 12M12 0C5.385 0 0 5.385 0 12s5.385 12 12 12 12-5.385 12-12S18.615 0 12 0',
		),
		'webflow' => array(
			'label' => 'Webflow',
			'color' => '#146EF5',
			'path'  => 'm24 4.515-7.658 14.97H9.149l3.205-6.204h-.144C9.566 16.713 5.621 18.973 0 19.485v-6.118s3.596-.213 5.71-2.435H0V4.515h6.417v5.278l.144-.001 2.622-5.277h4.854v5.244h.144l2.72-5.244H24Z',
		),
		'react' => array(
			'label' => 'React',
			'color' => '#61DAFB',
			'path'  => 'M14.23 12.004a2.236 2.236 0 0 1-2.235 2.236 2.236 2.236 0 0 1-2.236-2.236 2.236 2.236 0 0 1 2.235-2.236 2.236 2.236 0 0 1 2.236 2.236zm2.648-10.69c-1.346 0-3.107.96-4.888 2.622-1.78-1.653-3.542-2.602-4.887-2.602-.41 0-.783.093-1.106.278-1.375.793-1.683 3.264-.973 6.365C1.98 8.917 0 10.42 0 12.004c0 1.59 1.99 3.097 5.043 4.03-.704 3.113-.39 5.588.988 6.38.32.187.69.275 1.102.275 1.345 0 3.107-.96 4.888-2.624 1.78 1.654 3.542 2.603 4.887 2.603.41 0 .783-.09 1.106-.275 1.374-.792 1.683-3.263.973-6.365C22.02 15.096 24 13.59 24 12.004c0-1.59-1.99-3.097-5.043-4.032.704-3.11.39-5.587-.988-6.38-.318-.184-.688-.277-1.092-.278zm-.005 1.09v.006c.225 0 .406.044.558.127.666.382.955 1.835.73 3.704-.054.46-.142.945-.25 1.44-.96-.236-2.006-.417-3.107-.534-.66-.905-1.345-1.727-2.035-2.447 1.592-1.48 3.087-2.292 4.105-2.295zm-9.77.02c1.012 0 2.514.808 4.11 2.28-.686.72-1.37 1.537-2.02 2.442-1.107.117-2.154.298-3.113.538-.112-.49-.195-.964-.254-1.42-.23-1.868.054-3.32.714-3.707.19-.09.4-.127.563-.132zm4.882 3.05c.455.468.91.992 1.36 1.564-.44-.02-.89-.034-1.345-.034-.46 0-.915.01-1.36.034.44-.572.895-1.096 1.345-1.565zM12 8.1c.74 0 1.477.034 2.202.093.406.582.802 1.203 1.183 1.86.372.64.71 1.29 1.018 1.946-.308.655-.646 1.31-1.013 1.95-.38.66-.773 1.288-1.18 1.87-.728.063-1.466.098-2.21.098-.74 0-1.477-.035-2.202-.093-.406-.582-.802-1.204-1.183-1.86-.372-.64-.71-1.29-1.018-1.946.303-.657.646-1.313 1.013-1.954.38-.66.773-1.286 1.18-1.868.728-.064 1.466-.098 2.21-.098zm-3.635.254c-.24.377-.48.763-.704 1.16-.225.39-.435.782-.635 1.174-.265-.656-.49-1.31-.676-1.947.64-.15 1.315-.283 2.015-.386zm7.26 0c.695.103 1.365.23 2.006.387-.18.632-.405 1.282-.66 1.933-.2-.39-.41-.783-.64-1.174-.225-.392-.465-.774-.705-1.146zm3.063.675c.484.15.944.317 1.375.498 1.732.74 2.852 1.708 2.852 2.476-.005.768-1.125 1.74-2.857 2.475-.42.18-.88.342-1.355.493-.28-.958-.646-1.956-1.1-2.98.45-1.017.81-2.01 1.085-2.964zm-13.395.004c.278.96.645 1.957 1.1 2.98-.45 1.017-.812 2.01-1.086 2.964-.484-.15-.944-.318-1.37-.5-1.732-.737-2.852-1.706-2.852-2.474 0-.768 1.12-1.742 2.852-2.476.42-.18.88-.342 1.356-.494zm11.678 4.28c.265.657.49 1.312.676 1.948-.64.157-1.316.29-2.016.39.24-.375.48-.762.705-1.158.225-.39.435-.788.636-1.18zm-9.945.02c.2.392.41.783.64 1.175.23.39.465.772.705 1.143-.695-.102-1.365-.23-2.006-.386.18-.63.406-1.282.66-1.933zM17.92 16.32c.112.493.2.968.254 1.423.23 1.868-.054 3.32-.714 3.708-.147.09-.338.128-.563.128-1.012 0-2.514-.807-4.11-2.28.686-.72 1.37-1.536 2.02-2.44 1.107-.118 2.154-.3 3.113-.54zm-11.83.01c.96.234 2.006.415 3.107.532.66.905 1.345 1.727 2.035 2.446-1.595 1.483-3.092 2.295-4.11 2.295-.22-.005-.406-.05-.553-.132-.666-.38-.955-1.834-.73-3.703.054-.46.142-.944.25-1.438zm4.56.64c.44.02.89.034 1.345.034.46 0 .915-.01 1.36-.034-.44.572-.895 1.095-1.345 1.565-.455-.47-.91-.993-1.36-1.565z',
		),
		'nextjs' => array(
			'label' => 'Next.js',
			'color' => '#000000',
			'path'  => 'M18.665 21.978C16.758 23.255 14.465 24 12 24 5.377 24 0 18.623 0 12S5.377 0 12 0s12 5.377 12 12c0 3.583-1.574 6.801-4.067 9.001L9.219 7.2H7.2v9.596h1.615V9.251l9.85 12.727Zm-3.332-8.533 1.6 2.061V7.2h-1.6v6.245Z',
		),
		'nodejs' => array(
			'label' => 'Node.js',
			'color' => '#5FA04E',
			'path'  => 'M11.998,24c-0.321,0-0.641-0.084-0.922-0.247l-2.936-1.737c-0.438-0.245-0.224-0.332-0.08-0.383 c0.585-0.203,0.703-0.25,1.328-0.604c0.065-0.037,0.151-0.023,0.218,0.017l2.256,1.339c0.082,0.045,0.197,0.045,0.272,0l8.795-5.076 c0.082-0.047,0.134-0.141,0.134-0.238V6.921c0-0.099-0.053-0.192-0.137-0.242l-8.791-5.072c-0.081-0.047-0.189-0.047-0.271,0 L3.075,6.68C2.99,6.729,2.936,6.825,2.936,6.921v10.15c0,0.097,0.054,0.189,0.139,0.235l2.409,1.392 c1.307,0.654,2.108-0.116,2.108-0.89V7.787c0-0.142,0.114-0.253,0.256-0.253h1.115c0.139,0,0.255,0.112,0.255,0.253v10.021 c0,1.745-0.95,2.745-2.604,2.745c-0.508,0-0.909,0-2.026-0.551L2.28,18.675c-0.57-0.329-0.922-0.945-0.922-1.604V6.921 c0-0.659,0.353-1.275,0.922-1.603l8.795-5.082c0.557-0.315,1.296-0.315,1.848,0l8.794,5.082c0.57,0.329,0.924,0.944,0.924,1.603 v10.15c0,0.659-0.354,1.273-0.924,1.604l-8.794,5.078C12.643,23.916,12.324,24,11.998,24z M19.099,13.993 c0-1.9-1.284-2.406-3.987-2.763c-2.731-0.361-3.009-0.548-3.009-1.187c0-0.528,0.235-1.233,2.258-1.233 c1.807,0,2.473,0.389,2.747,1.607c0.024,0.115,0.129,0.199,0.247,0.199h1.141c0.071,0,0.138-0.031,0.186-0.081 c0.048-0.054,0.074-0.123,0.067-0.196c-0.177-2.098-1.571-3.076-4.388-3.076c-2.508,0-4.004,1.058-4.004,2.833 c0,1.925,1.488,2.457,3.895,2.695c2.88,0.282,3.103,0.703,3.103,1.269c0,0.983-0.789,1.402-2.642,1.402 c-2.327,0-2.839-0.584-3.011-1.742c-0.02-0.124-0.126-0.215-0.253-0.215h-1.137c-0.141,0-0.254,0.112-0.254,0.253 c0,1.482,0.806,3.248,4.655,3.248C17.501,17.007,19.099,15.91,19.099,13.993z',
		),
	);

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
				<figure class="avix-work-card__media" data-aw-media>
					<?php
					$image = $this->image_html( $item, $settings['image_size'] );
					if ( $image ) {
						echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in image_html().
					} else {
						echo '<span class="avix-work-card__placeholder" aria-hidden="true">' . esc_html( $this->initials( $item['name'] ) ) . '</span>';
					}
					?>
				</figure>
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
