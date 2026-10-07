<?php
/**
 * Case Study Grid: the /case-studies/ index. A breadcrumb and an editorial
 * header, service filter chips, then the case studies as cards in the home
 * page's Selected Work language: the image with a dark info panel over its
 * foot (client, tags, summary, the "Designed for" line, one checked metric
 * and a "View case study" pill). The first card is wide. Filters and "Load
 * more" work without a reload, and as plain ?service= / ?pg= links without
 * JavaScript.
 *
 * Cards come from \AvixWidgets\Case_Studies\Cards (includes/case-studies/
 * cards.php), which also answers the AJAX requests, so filtered and loaded
 * cards match exactly.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Widgets;

use AvixWidgets\Case_Studies\Cards;
use AvixWidgets\Case_Studies\Case_Study;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Utils;
use Elementor\Widget_Base;

defined( 'ABSPATH' ) || exit;

class Case_Study_Grid extends Widget_Base {

	public function get_name(): string {
		return 'avix-case-study-grid';
	}

	public function get_title(): string {
		return esc_html__( 'Case Study Grid', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-gallery-grid';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'avix', 'case study', 'portfolio', 'case studies', 'projects', 'work', 'grid', 'filter', 'load more' );
	}

	public function get_style_depends(): array {
		return array( 'avix-case-study-grid' );
	}

	public function get_script_depends(): array {
		return array( 'avix-case-study-grid' );
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/** Case studies change: Elementor's element cache must never freeze this list. */
	protected function is_dynamic_content(): bool {
		return true;
	}

	/* ------------------------------------------------------------------ */
	/* Controls                                                            */
	/* ------------------------------------------------------------------ */

	protected function register_controls(): void {
		$this->controls_header();
		$this->controls_query();
		$this->controls_layout();
		$this->controls_style();
		$this->controls_type();
	}

	private function controls_header() {
		$this->start_controls_section( 'section_header', array( 'label' => esc_html__( 'Header', 'avix-widgets' ) ) );

		$this->add_control(
			'show_header',
			array(
				'label'   => esc_html__( 'Show header', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'show_breadcrumb',
			array(
				'label'       => esc_html__( 'Breadcrumb', 'avix-widgets' ),
				'description' => esc_html__( 'Home › this page, above the eyebrow.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'condition'   => array( 'show_header' => 'yes' ),
			)
		);

		$this->add_control(
			'eyebrow',
			array(
				'label'       => esc_html__( 'Eyebrow', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Case studies', 'avix-widgets' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_header' => 'yes' ),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Title', 'avix-widgets' ),
				'description' => esc_html__( 'Wrap words in [brackets] to highlight them in orange. Press Enter for a new line.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => 'Selected Website & [Ecommerce Projects]',
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_header' => 'yes' ),
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'       => esc_html__( 'Title tag', 'avix-widgets' ),
				'description' => esc_html__( 'H1 when this widget opens the page. The client names on the cards follow one level below.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'h1',
				'options'     => array(
					'h1'  => 'H1',
					'h2'  => 'H2',
					'h3'  => 'H3',
					'div' => 'div',
					'p'   => 'p',
				),
				'condition'   => array( 'show_header' => 'yes' ),
			)
		);

		$this->add_control(
			'text',
			array(
				'label'     => esc_html__( 'Text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXTAREA,
				'rows'      => 4,
				'default'   => 'Live projects for brands in the Netherlands: a Shopify store for technical skiwear, a subscription-first supplement brand and a Webflow site that turns local searches into enquiries. Every screenshot comes from the live site, with the features that solved each problem marked.',
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_header' => 'yes' ),
			)
		);

		$this->add_control(
			'clear_header',
			array(
				'label'       => esc_html__( 'Clear the fixed header', 'avix-widgets' ),
				'description' => esc_html__( 'Starts the section below the Smart Header when this widget is at the top of the page.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
			)
		);

		$this->end_controls_section();
	}

	private function controls_query() {
		$this->start_controls_section( 'section_query', array( 'label' => esc_html__( 'Case studies', 'avix-widgets' ) ) );

		$this->add_control(
			'count',
			array(
				'label'       => esc_html__( 'Case studies per page', 'avix-widgets' ),
				'description' => esc_html__( 'Cards shown at first, and how many each "Load more" adds.', 'avix-widgets' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 1,
				'max'         => 24,
				'default'     => 9,
			)
		);

		$this->add_control(
			'services',
			array(
				'label'       => esc_html__( 'Only these services', 'avix-widgets' ),
				'description' => esc_html__( 'Leave empty to show every case study.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $this->service_options(),
				'default'     => array(),
			)
		);

		$this->add_control(
			'exclude_current',
			array(
				'label'       => esc_html__( 'Leave out the current case study', 'avix-widgets' ),
				'description' => esc_html__( 'For a "More case studies" section on a case study page.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'orderby',
			array(
				'label'       => esc_html__( 'Order', 'avix-widgets' ),
				'description' => esc_html__( 'Menu order is the "Order" number on each case study (lowest first).', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'menu_order',
				'options'     => array(
					'menu_order' => esc_html__( 'Menu order', 'avix-widgets' ),
					'date'       => esc_html__( 'Newest first', 'avix-widgets' ),
				),
			)
		);

		$this->end_controls_section();
	}

	private function controls_layout() {
		$this->start_controls_section( 'section_layout', array( 'label' => esc_html__( 'Layout', 'avix-widgets' ) ) );

		$this->add_control(
			'show_featured',
			array(
				'label'       => esc_html__( 'Wide first card', 'avix-widgets' ),
				'description' => esc_html__( 'The first case study spans the grid, image and panel side by side. Only on the first page with no filter picked.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'columns',
			array(
				'label'       => esc_html__( 'Columns', 'avix-widgets' ),
				'description' => esc_html__( 'On wide screens. Phones always show one column.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '2',
				'options'     => array(
					'2' => '2',
					'3' => '3',
				),
			)
		);

		$this->add_control(
			'card_style',
			array(
				'label'   => esc_html__( 'Card style', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'panel',
				'options' => array(
					'panel'   => esc_html__( 'Dark panel (as on the home page)', 'avix-widgets' ),
					'minimal' => esc_html__( 'Minimal', 'avix-widgets' ),
				),
			)
		);

		$this->add_control(
			'show_metric',
			array(
				'label'       => esc_html__( 'Key figure on cards', 'avix-widgets' ),
				'description' => esc_html__( 'The case study\'s first metric (only metrics with a source exist).', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'card_button',
			array(
				'label'   => esc_html__( 'Card button text', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'View case study', 'avix-widgets' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'show_filters',
			array(
				'label'       => esc_html__( 'Service filters', 'avix-widgets' ),
				'description' => esc_html__( '"All" plus each service in use. They appear once there are at least 4 case studies across 2 or more services. A pick swaps the cards without a reload and adds ?service= to the address.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'filters_label',
			array(
				'label'     => esc_html__( 'Filters label', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Filter by service', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_filters' => 'yes' ),
			)
		);

		$this->add_control(
			'all_label',
			array(
				'label'     => esc_html__( '"All" chip text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'All', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_filters' => 'yes' ),
			)
		);

		$this->add_control(
			'show_load_more',
			array(
				'label'       => esc_html__( 'Load more', 'avix-widgets' ),
				'description' => esc_html__( 'Adds the next case studies without a reload. Without JavaScript it is a plain link to the next page (?pg=2).', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'more_text',
			array(
				'label'     => esc_html__( 'Button text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Load more case studies', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_load_more' => 'yes' ),
			)
		);

		$this->add_control(
			'count_text',
			array(
				'label'       => esc_html__( 'Progress text', 'avix-widgets' ),
				'description' => esc_html__( '{shown} and {total} become numbers. Leave empty to hide the line.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Showing {shown} of {total} case studies', 'avix-widgets' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_load_more' => 'yes' ),
			)
		);

		$this->add_control(
			'empty_text',
			array(
				'label'     => esc_html__( 'When there are no case studies', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'New case studies are on their way.', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'seo_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Filter and page links use ?service= and ?pg=. They are allowed automatically in Yoast SEO\'s "Remove unregistered URL parameters". Purge the page cache after publishing a case study.', 'avix-widgets' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
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
				'default' => 'paper',
				'options' => array(
					'paper' => esc_html__( 'Paper', 'avix-widgets' ),
					'white' => esc_html__( 'White', 'avix-widgets' ),
					'dark'  => esc_html__( 'Dark', 'avix-widgets' ),
				),
			)
		);

		$colours = array(
			'bg'       => array( esc_html__( 'Background', 'avix-widgets' ), '--csi-bg' ),
			'ink'      => array( esc_html__( 'Headings', 'avix-widgets' ), '--csi-ink' ),
			'muted'    => array( esc_html__( 'Body text', 'avix-widgets' ), '--csi-muted' ),
			'line'     => array( esc_html__( 'Lines', 'avix-widgets' ), '--csi-line' ),
			'accent'   => array( esc_html__( 'Accent', 'avix-widgets' ), '--csi-accent' ),
			'panel'    => array( esc_html__( 'Card panel', 'avix-widgets' ), '--csi-panel' ),
			'panel_hi' => array( esc_html__( 'Card highlight (on the panel)', 'avix-widgets' ), '--csi-panel-accent' ),
		);
		foreach ( $colours as $key => $colour ) {
			$this->add_control(
				'color_' . $key,
				array(
					'label'     => $colour[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-csi' => $colour[1] . ': {{VALUE}};' ),
				)
			);
		}

		$this->add_responsive_control(
			'padding',
			array(
				'label'              => esc_html__( 'Vertical padding', 'avix-widgets' ),
				'type'               => Controls_Manager::DIMENSIONS,
				'size_units'         => array( 'px', 'vh' ),
				'allowed_dimensions' => 'vertical',
				'separator'          => 'before',
				'selectors'          => array( '{{WRAPPER}} .avix-csi' => '--csi-pad-top: {{TOP}}{{UNIT}}; --csi-pad-bottom: {{BOTTOM}}{{UNIT}};' ),
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
				'selectors'  => array( '{{WRAPPER}} .avix-csi' => '--csi-max: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'gap',
			array(
				'label'      => esc_html__( 'Card gap', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 12,
						'max' => 72,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .avix-csi' => '--csi-gap: {{SIZE}}{{UNIT}};' ),
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
			'type_title'   => array( esc_html__( 'Title', 'avix-widgets' ), '.avix-csi__title' ),
			'type_text'    => array( esc_html__( 'Header text', 'avix-widgets' ), '.avix-csi__text' ),
			'type_name'    => array( esc_html__( 'Client names', 'avix-widgets' ), '.avix-csi__name' ),
			'type_excerpt' => array( esc_html__( 'Card summaries', 'avix-widgets' ), '.avix-csi__excerpt' ),
			'type_chips'   => array( esc_html__( 'Filter chips', 'avix-widgets' ), '.avix-csi__chip' ),
		);
		foreach ( $groups as $key => $group ) {
			$this->add_group_control(
				Group_Control_Typography::get_type(),
				array(
					'name'     => $key,
					'label'    => $group[0],
					'selector' => '{{WRAPPER}} .avix-csi ' . $group[1],
				)
			);
		}

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ */
	/* Render                                                              */
	/* ------------------------------------------------------------------ */

	protected function render(): void {
		if ( ! class_exists( '\AvixWidgets\Case_Studies\Cards' ) && defined( 'AVIX_EW_PATH' ) && file_exists( AVIX_EW_PATH . 'includes/case-studies/cards.php' ) ) {
			require_once AVIX_EW_PATH . 'includes/case-studies/cards.php';
		}
		if ( ! class_exists( '\AvixWidgets\Case_Studies\Cards' ) ) {
			return;
		}

		$s         = $this->get_settings_for_display();
		$uid       = $this->get_id();
		$editor    = $this->is_editor();
		$title_id  = 'avix-csi-title-' . $uid;
		$grid_id   = 'avix-csi-grid-' . $uid;
		$show_more = 'yes' === ( $s['show_load_more'] ?? '' );
		$title_tag = Utils::validate_html_tag( (string) ( $s['title_tag'] ?? 'h1' ) );
		$has_head  = 'yes' === ( $s['show_header'] ?? '' ) && '' !== trim( (string) ( $s['title'] ?? '' ) . (string) ( $s['eyebrow'] ?? '' ) . (string) ( $s['text'] ?? '' ) );
		$levels    = array(
			'h1' => 'h2',
			'h2' => 'h3',
			'h3' => 'h4',
		);
		$card_tag  = $has_head && '' !== trim( (string) ( $s['title'] ?? '' ) ) ? ( $levels[ $title_tag ] ?? 'h3' ) : 'h2';
		$services  = array_values( array_filter( array_map( 'sanitize_title', array_map( 'strval', (array) ( $s['services'] ?? array() ) ) ), 'strlen' ) );
		$exclude   = 'yes' === ( $s['exclude_current'] ?? '' ) ? $this->current_study() : 0;

		$base_raw = array(
			'per_page' => $s['count'] ?? 9,
			'services' => $services,
			'exclude'  => $exclude ? array( $exclude ) : array(),
			'orderby'  => $s['orderby'] ?? 'menu_order',
			'featured' => 'yes' === ( $s['show_featured'] ?? '' ),
			'style'    => $s['card_style'] ?? 'panel',
			'metric'   => 'yes' === ( $s['show_metric'] ?? '' ),
			'tag'      => $card_tag,
			'label'    => $s['card_button'] ?? '',
			'uid'      => $uid,
		);

		// Filters: shown with at least 4 case studies across 2+ services.
		$terms      = 'yes' === ( $s['show_filters'] ?? '' ) ? Cards::terms_in_use( $services ) : array();
		$base_total = $this->count_all( $base_raw );
		$terms      = count( $terms ) >= 2 && $base_total >= 4 ? $terms : array();

		// The URL picks the filter (one of the chips) and the page (with "Load
		// more"), never while designing.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- public, read-only view state; both values are validated.
		$req_service = '';
		if ( ! $editor && $terms && isset( $_GET[ Cards::SERVICE_VAR ] ) && is_scalar( $_GET[ Cards::SERVICE_VAR ] ) ) {
			$req_service = sanitize_title( wp_unslash( (string) $_GET[ Cards::SERVICE_VAR ] ) );
			$req_service = in_array( $req_service, wp_list_pluck( $terms, 'slug' ), true ) ? $req_service : '';
		}
		$req_page = 1;
		if ( ! $editor && $show_more && isset( $_GET[ Cards::PAGE_VAR ] ) && is_scalar( $_GET[ Cards::PAGE_VAR ] ) ) {
			$req_page = max( 1, absint( $_GET[ Cards::PAGE_VAR ] ) );
		}
		// phpcs:enable

		$raw            = $base_raw;
		$raw['page']    = $req_page;
		$raw['eager']   = 1;
		$raw['service'] = $req_service;
		$args           = Cards::args( $raw );
		$query          = Cards::query( $args );

		// A page past the end (an old link) falls back to page 1.
		if ( $args['page'] > 1 && ! $query->posts ) {
			$raw['page'] = 1;
			$args        = Cards::args( $raw );
			$query       = Cards::query( $args );
		}

		$total    = (int) $query->found_posts;
		$shown    = Cards::shown( $query, $args );
		$has_more = $shown < $total;

		$classes = array(
			'avix-csi',
			'avix-csi--' . ( in_array( (string) ( $s['theme'] ?? '' ), array( 'paper', 'white', 'dark' ), true ) ? $s['theme'] : 'paper' ),
			'avix-csi--cols-' . ( '3' === (string) ( $s['columns'] ?? '2' ) ? '3' : '2' ),
			'avix-csi--' . $args['style'],
		);
		if ( 'yes' === ( $s['clear_header'] ?? '' ) && $has_head ) {
			$classes[] = 'avix-csi--clear';
		}
		if ( ! $has_head ) {
			$classes[] = 'avix-csi--headless';
		}

		$count_text = (string) ( $s['count_text'] ?? '' );
		$config     = array(
			'ajax'       => admin_url( 'admin-ajax.php' ),
			'action'     => Cards::ACTION,
			'page'       => $args['page'],
			'perPage'    => $args['per_page'],
			'service'    => $args['service'],
			'services'   => implode( ',', $args['services'] ),
			'exclude'    => implode( ',', $args['exclude'] ),
			'orderby'    => $args['orderby'],
			'featured'   => 'yes' === ( $s['show_featured'] ?? '' ) ? 1 : 0,
			'style'      => $args['style'],
			'metric'     => $args['metric'] ? 1 : 0,
			'tag'        => $args['tag'],
			'label'      => $args['label'],
			'uid'        => $args['uid'],
			'total'      => $total,
			'shown'      => $shown,
			'countText'  => $count_text,
			'moreText'   => (string) ( $s['more_text'] ?? '' ),
			'loading'    => __( 'Loading case studies…', 'avix-widgets' ),
			/* translators: 1: service name, 2: number of case studies. */
			'announce'   => __( '%1$s: %2$s case studies', 'avix-widgets' ),
			/* translators: 1: service name. */
			'announce1'  => __( '%1$s: 1 case study', 'avix-widgets' ),
			'serviceVar' => Cards::SERVICE_VAR,
			'pageVar'    => Cards::PAGE_VAR,
			'urlState'   => $terms || $show_more ? 1 : 0,
		);

		$this->add_render_attribute(
			'root',
			array(
				'class'         => $classes,
				'data-avix-csi' => wp_json_encode( $config ),
			)
		);
		if ( $has_head && '' !== trim( (string) ( $s['title'] ?? '' ) ) ) {
			$this->add_render_attribute( 'root', 'aria-labelledby', $title_id );
		} else {
			$this->add_render_attribute( 'root', 'aria-label', esc_html__( 'Case studies', 'avix-widgets' ) );
		}

		if ( 0 === $base_total && $editor ) {
			echo '<div class="elementor-alert elementor-alert-info">' . esc_html__( 'Case Study Grid: publish a case study to fill this grid. Visitors see the "no case studies" line until then.', 'avix-widgets' ) . '</div>';
		}

		$base = $this->base_url();
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="avix-csi__inner">
				<?php
				if ( $has_head ) {
					$this->render_header( $s, $title_tag, $title_id );
				}
				if ( $terms ) {
					$this->render_filters( $s, $terms, $args, $base, $grid_id, $base_total );
				}
				?>
				<div class="avix-csi__grid" id="<?php echo esc_attr( $grid_id ); ?>" data-csi-grid aria-busy="false">
					<?php echo Cards::cards( $query, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Cards::card(). ?>
				</div>
				<p class="avix-csi__sr" role="status" aria-live="polite" data-csi-announce></p>
				<?php
				$this->render_empty( $s, 0 === $total );
				if ( $show_more ) {
					$this->render_more( $s, $args, $base, $total, $shown, $has_more );
				}
				?>
			</div>
		</section>
		<?php
	}

	private function render_header( array $s, $tag, $title_id ) {
		$eyebrow = trim( (string) ( $s['eyebrow'] ?? '' ) );
		$title   = trim( (string) ( $s['title'] ?? '' ) );
		$text    = trim( (string) ( $s['text'] ?? '' ) );
		?>
		<header class="avix-csi__head<?php echo '' !== $text ? ' avix-csi__head--split' : ''; ?>">
			<div class="avix-csi__intro" data-csi-rv>
				<?php
				if ( 'yes' === ( $s['show_breadcrumb'] ?? '' ) ) {
					$this->render_crumbs();
				}
				if ( '' !== $eyebrow ) {
					echo '<p class="avix-csi__eyebrow"><span class="avix-csi__px" aria-hidden="true"></span>' . esc_html( $eyebrow ) . '</p>';
				}
				if ( '' !== $title ) {
					printf(
						'<%1$s class="avix-csi__title" id="%2$s">%3$s</%1$s>',
						esc_html( $tag ),
						esc_attr( $title_id ),
						$this->accent_html( $title, 'avix-csi__accent' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in accent_html().
					);
				}
				?>
			</div>
			<?php if ( '' !== $text ) : ?>
				<p class="avix-csi__text" data-csi-rv><?php echo esc_html( $text ); ?></p>
			<?php endif; ?>
		</header>
		<?php
	}

	/** Home › this page. Visible only: Yoast prints the BreadcrumbList. */
	private function render_crumbs() {
		$id      = get_queried_object_id();
		$current = $id && ! is_singular( Cards::POST_TYPE ) ? wp_strip_all_tags( get_the_title( $id ) ) : '';
		if ( '' === $current ) {
			$current = class_exists( '\AvixWidgets\Case_Studies\Case_Study' ) ? Case_Study::index_title() : __( 'Case studies', 'avix-widgets' );
		}
		?>
		<nav class="avix-csi__crumbs" aria-label="<?php echo esc_attr__( 'Breadcrumb', 'avix-widgets' ); ?>">
			<ol class="avix-csi__crumb-list">
				<li class="avix-csi__crumb"><a class="avix-csi__crumb-link" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'avix-widgets' ); ?></a></li>
				<li class="avix-csi__crumb" aria-current="page">
					<svg class="avix-csi__crumb-sep" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m9 6 6 6-6 6"/></svg>
					<span class="avix-csi__crumb-text"><?php echo esc_html( $current ); ?></span>
				</li>
			</ol>
		</nav>
		<?php
	}

	/**
	 * "All" plus each service in use. Plain links (?service=) that the
	 * script turns into in-place filters.
	 *
	 * @param array  $s          Settings.
	 * @param array  $terms      Service terms.
	 * @param array  $args       Output of Cards::args().
	 * @param string $base       Base address.
	 * @param string $grid_id    Grid element ID.
	 * @param int    $base_total Case studies in the unfiltered view.
	 */
	private function render_filters( array $s, array $terms, array $args, $base, $grid_id, $base_total ) {
		$label  = trim( (string) ( $s['filters_label'] ?? '' ) );
		$all    = trim( (string) ( $s['all_label'] ?? '' ) );
		$nav_id = 'avix-csi-filters-' . $this->get_id();
		// Term counts include the left-out current study: no counts then.
		$counts = ! $args['exclude'];
		$chips  = array(
			array(
				'slug'  => '',
				'name'  => '' !== $all ? $all : __( 'All', 'avix-widgets' ),
				'count' => $base_total,
				'url'   => $base,
			),
		);
		foreach ( $terms as $term ) {
			$chips[] = array(
				'slug'  => $term->slug,
				'name'  => $term->name,
				'count' => (int) $term->count,
				'url'   => add_query_arg( Cards::SERVICE_VAR, $term->slug, $base ),
			);
		}
		?>
		<div class="avix-csi__rail" data-csi-rv>
			<?php if ( '' !== $label ) : ?>
				<p class="avix-csi__rail-label" id="<?php echo esc_attr( $nav_id ); ?>"><span class="avix-csi__px" aria-hidden="true"></span><?php echo esc_html( $label ); ?></p>
			<?php endif; ?>
			<nav class="avix-csi__filters" <?php echo '' !== $label ? 'aria-labelledby="' . esc_attr( $nav_id ) . '"' : 'aria-label="' . esc_attr__( 'Filter by service', 'avix-widgets' ) . '"'; ?> data-csi-row>
				<ul class="avix-csi__chips">
					<?php foreach ( $chips as $chip ) : ?>
						<?php $active = $chip['slug'] === $args['service']; ?>
						<li class="avix-csi__chip-item">
							<a class="avix-csi__chip<?php echo $active ? ' is-active' : ''; ?>" href="<?php echo esc_url( $chip['url'] ); ?>" data-csi-service="<?php echo esc_attr( $chip['slug'] ); ?>" data-csi-name="<?php echo esc_attr( $chip['name'] ); ?>" aria-controls="<?php echo esc_attr( $grid_id ); ?>"<?php echo $active ? ' aria-current="true"' : ''; ?>>
								<?php echo esc_html( $chip['name'] ); ?>
								<?php if ( $counts ) : ?>
									<span class="avix-csi__chip-n" aria-hidden="true"><?php echo esc_html( number_format_i18n( $chip['count'] ) ); ?></span>
								<?php endif; ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>
		</div>
		<?php
	}

	/**
	 * Shown when nothing is listed (no case studies yet).
	 *
	 * @param array $s       Settings.
	 * @param bool  $visible Whether nothing is listed.
	 */
	private function render_empty( array $s, $visible ) {
		$text = trim( (string) ( $s['empty_text'] ?? '' ) );
		if ( '' === $text ) {
			return;
		}
		?>
		<div class="avix-csi__empty" data-csi-empty<?php echo $visible ? '' : ' hidden'; ?>>
			<span class="avix-csi__empty-px" aria-hidden="true"><i></i><i></i><i></i></span>
			<p class="avix-csi__empty-text"><?php echo esc_html( $text ); ?></p>
		</div>
		<?php
	}

	private function render_more( array $s, array $args, $base, $total, $shown, $has_more ) {
		$count = trim( (string) ( $s['count_text'] ?? '' ) );
		$text  = trim( (string) ( $s['more_text'] ?? '' ) );
		$text  = '' !== $text ? $text : __( 'Load more case studies', 'avix-widgets' );
		$next  = $this->page_url( $args, $base, $args['page'] + 1 );
		$ratio = $total > 0 ? min( 1, $shown / $total ) : 0;
		// Nothing to load and nothing loaded yet: "Showing 3 of 3" adds nothing.
		$idle = ! $has_more && 1 === $args['page'];
		?>
		<div class="avix-csi__foot" data-csi-foot data-csi-rv<?php echo $idle ? ' hidden' : ''; ?>>
			<?php if ( '' !== $count ) : ?>
				<p class="avix-csi__status" data-csi-status><?php echo $this->count_html( $count, $shown, $total ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in count_html(). ?></p>
				<span class="avix-csi__bar" aria-hidden="true"><span class="avix-csi__bar-fill" data-csi-bar style="--csi-progress:<?php echo esc_attr( (string) round( $ratio, 4 ) ); ?>"></span></span>
			<?php endif; ?>
			<div class="avix-csi__actions">
				<?php if ( $args['page'] > 1 ) : ?>
					<a class="avix-csi__prev" href="<?php echo esc_url( $this->page_url( $args, $base, $args['page'] - 1 ) ); ?>"><?php esc_html_e( 'Previous page', 'avix-widgets' ); ?></a>
				<?php endif; ?>
				<a class="avix-csi__more" href="<?php echo esc_url( $next ); ?>" data-csi-more<?php echo $has_more ? '' : ' hidden'; ?>>
					<span class="avix-csi__more-text" data-csi-more-text><?php echo esc_html( $text ); ?></span>
					<span class="avix-csi__more-icon" aria-hidden="true">
						<svg class="avix-csi__plus" viewBox="0 0 24 24" focusable="false"><path d="M12 5v14M5 12h14"/></svg>
						<svg class="avix-csi__dots" viewBox="0 0 7 3" focusable="false"><rect x="0" y="1" width="1" height="1"/><rect x="3" y="1" width="1" height="1"/><rect x="6" y="1" width="1" height="1"/></svg>
					</span>
				</a>
			</div>
		</div>
		<?php
	}

	/* ------------------------------------------------------------------ */
	/* Data                                                                */
	/* ------------------------------------------------------------------ */

	/**
	 * Published case studies in the unfiltered view (the "All" chip and the
	 * rule that shows the filters).
	 *
	 * @param array $raw Base arguments.
	 */
	private function count_all( array $raw ) {
		$args             = Cards::args( array_merge( $raw, array( 'page' => 1 ) ) );
		$args['per_page'] = 1;
		return (int) Cards::query( $args )->found_posts;
	}

	/** The case study this page belongs to (to leave it out), else 0. */
	private function current_study() {
		$id = (int) get_queried_object_id();
		if ( $this->is_editor() && class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor ) {
			$id = (int) get_the_ID();
		}
		return $id && Cards::POST_TYPE === get_post_type( $id ) ? $id : 0;
	}

	/** Service slug => name, for the "Only these services" control. */
	private function service_options() {
		$options = array();
		if ( ! taxonomy_exists( 'avix_cs_service' ) ) {
			return $options;
		}
		$terms = get_terms(
			array(
				'taxonomy'   => 'avix_cs_service',
				'hide_empty' => false,
				'number'     => 100,
			)
		);
		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$options[ $term->slug ] = $term->name;
			}
		}
		return $options;
	}

	/**
	 * Base address for the chips and page links (so they work without
	 * JavaScript): the page's path only. Never the visitor's own query
	 * string: page caches leave utm_*, gclid and fbclid out of the cache key,
	 * so one visitor's tracking IDs would end up in every cached copy.
	 */
	private function base_url() {
		if ( $this->is_editor() || wp_doing_ajax() ) {
			$id = get_queried_object_id();
			return $id ? (string) get_permalink( $id ) : home_url( '/' );
		}
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised by esc_url_raw.
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		return '' !== $path ? $path : '/';
	}

	/**
	 * Address of one page of the current view (filter kept).
	 *
	 * @param array  $args Output of Cards::args().
	 * @param string $base Base address.
	 * @param int    $page Page.
	 */
	private function page_url( array $args, $base, $page ) {
		$url = '' !== $args['service'] ? add_query_arg( Cards::SERVICE_VAR, $args['service'], $base ) : $base;
		return $page > 1 ? add_query_arg( Cards::PAGE_VAR, $page, $url ) : $url;
	}

	/* ------------------------------------------------------------------ */
	/* Helpers                                                             */
	/* ------------------------------------------------------------------ */

	/**
	 * Escaped text; [words] become the accent span, new lines become <br>.
	 *
	 * @param string $text  Text.
	 * @param string $class Accent class.
	 */
	private function accent_html( $text, $class ) {
		$lines = array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $text ) ), 'strlen' ) );
		$lines = array_map(
			static function ( $line ) use ( $class ) {
				$html = preg_replace( '/\[([^\[\]]+)\]/u', '<span class="' . esc_attr( $class ) . '">$1</span>', esc_html( $line ) );
				return null === $html ? esc_html( $line ) : $html;
			},
			$lines
		);
		return implode( ' <br>', $lines );
	}

	/**
	 * "Showing {shown} of {total} case studies" with the numbers in <strong>.
	 *
	 * @param string $template Text with tokens.
	 * @param int    $shown    Shown.
	 * @param int    $total    Total.
	 */
	private function count_html( $template, $shown, $total ) {
		return strtr(
			esc_html( $template ),
			array(
				'{shown}' => '<strong data-csi-shown>' . esc_html( number_format_i18n( $shown ) ) . '</strong>',
				'{total}' => '<strong data-csi-total>' . esc_html( number_format_i18n( $total ) ) . '</strong>',
			)
		);
	}

	private function is_editor() {
		return class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor && \Elementor\Plugin::$instance->editor->is_edit_mode();
	}
}
