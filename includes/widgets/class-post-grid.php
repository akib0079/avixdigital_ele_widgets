<?php
/**
 * Post Grid: the Blog page. A header with search (the pixel character reads a
 * book on top of it), the newest post as a large featured card, topic chips
 * that filter without a reload, a 3/2/1 column grid of article cards with an
 * orange "Share your brief" card in the flow, and "Load more articles".
 *
 * Cards come from \AvixWidgets\Post_Cards (includes/ajax.php), which also
 * answers the AJAX requests, so filtered and loaded cards match exactly.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Widgets;

use AvixWidgets\Pixel_Pal;
use AvixWidgets\Post_Cards;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Utils;
use Elementor\Widget_Base;

defined( 'ABSPATH' ) || exit;

class Post_Grid extends Widget_Base {

	/** Query vars: ?topic=<category slug> and ?pg=<page> (the no-JS fallback). */
	const TOPIC_VAR = 'topic';
	const PAGE_VAR  = 'pg';

	/** This instance's query vars (see url_vars()). */
	private $topic_var = self::TOPIC_VAR;
	private $page_var  = self::PAGE_VAR;

	public function get_name(): string {
		return 'avix-post-grid';
	}

	public function get_title(): string {
		return esc_html__( 'Post Grid', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-posts-grid';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'blog', 'posts', 'grid', 'articles', 'insights', 'filter', 'load more', 'pixel', 'avix' );
	}

	public function get_style_depends(): array {
		return array( 'avix-post-grid' );
	}

	public function get_script_depends(): array {
		return array( 'avix-post-grid' );
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/** Posts change all the time: Elementor's element cache must never freeze this list. */
	protected function is_dynamic_content(): bool {
		return true;
	}

	/* ------------------------------------------------------------------ */
	/* Controls                                                            */
	/* ------------------------------------------------------------------ */

	protected function register_controls(): void {
		$this->controls_header();
		$this->controls_query();
		$this->controls_featured();
		$this->controls_filters();
		$this->controls_cards();
		$this->controls_cta();
		$this->controls_more();
		$this->controls_buddy();
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
			'eyebrow',
			array(
				'label'       => esc_html__( 'Eyebrow', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'AvixDigital insights', 'avix-widgets' ),
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
				'rows'        => 3,
				'default'     => 'Plan a better website or [web application.]',
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_header' => 'yes' ),
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'       => esc_html__( 'Title tag', 'avix-widgets' ),
				'description' => esc_html__( 'H1 when this widget opens the Blog page. Card titles follow one level below.', 'avix-widgets' ),
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
				'default'   => 'Practical guidance on custom websites, ecommerce and web applications, from choosing a platform to defining a project your business can maintain.',
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_header' => 'yes' ),
			)
		);

		$this->add_control(
			'show_search',
			array(
				'label'       => esc_html__( 'Search field', 'avix-widgets' ),
				'description' => esc_html__( 'Visitors search the blog; results open on your site\'s search page.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
				'condition'   => array( 'show_header' => 'yes' ),
			)
		);

		$this->add_control(
			'search_placeholder',
			array(
				'label'     => esc_html__( 'Search placeholder', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Search articles', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array(
					'show_header' => 'yes',
					'show_search' => 'yes',
				),
			)
		);

		$this->add_control(
			'search_button',
			array(
				'label'       => esc_html__( 'Search button label', 'avix-widgets' ),
				'description' => esc_html__( 'Read out by screen readers; the button itself shows an icon.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Search', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
				'condition'   => array(
					'show_header' => 'yes',
					'show_search' => 'yes',
				),
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
		$this->start_controls_section( 'section_query', array( 'label' => esc_html__( 'Posts', 'avix-widgets' ) ) );

		$this->add_control(
			'per_page',
			array(
				'label'       => esc_html__( 'Posts per page', 'avix-widgets' ),
				'description' => esc_html__( 'Cards in the grid at first (the project card counts as one, so rows stay full), and how many each "Load more" adds.', 'avix-widgets' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 1,
				'max'         => 24,
				'default'     => 6,
			)
		);

		$this->add_control(
			'include_cats',
			array(
				'label'       => esc_html__( 'Only these categories', 'avix-widgets' ),
				'description' => esc_html__( 'Leave empty to show every category.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $this->category_options(),
				'default'     => array(),
			)
		);

		$this->add_control(
			'orderby',
			array(
				'label'   => esc_html__( 'Order by', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'date',
				'options' => array(
					'date'          => esc_html__( 'Publish date', 'avix-widgets' ),
					'modified'      => esc_html__( 'Last updated', 'avix-widgets' ),
					'title'         => esc_html__( 'Title', 'avix-widgets' ),
					'comment_count' => esc_html__( 'Most comments', 'avix-widgets' ),
				),
			)
		);

		$this->add_control(
			'order',
			array(
				'label'   => esc_html__( 'Order', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'DESC',
				'options' => array(
					'DESC' => esc_html__( 'Newest / Z–A first', 'avix-widgets' ),
					'ASC'  => esc_html__( 'Oldest / A–Z first', 'avix-widgets' ),
				),
			)
		);

		$this->add_control(
			'show_schema',
			array(
				'label'       => esc_html__( 'Structured data', 'avix-widgets' ),
				'description' => esc_html__( 'Lists the articles for search engines (ItemList JSON-LD). Printed once per page.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
			)
		);

		$this->end_controls_section();
	}

	private function controls_featured() {
		$this->start_controls_section( 'section_featured', array( 'label' => esc_html__( 'Featured post', 'avix-widgets' ) ) );

		$this->add_control(
			'show_featured',
			array(
				'label'       => esc_html__( 'Show featured post', 'avix-widgets' ),
				'description' => esc_html__( 'A large card above the topics, shown with "All" only. It is left out of the "All" grid below; when a visitor picks a topic it steps aside and the grid lists every post in that topic.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'featured_source',
			array(
				'label'     => esc_html__( 'Which post', 'avix-widgets' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'latest',
				'options'   => array(
					'latest' => esc_html__( 'Newest post', 'avix-widgets' ),
					'sticky' => esc_html__( 'Sticky post (else newest)', 'avix-widgets' ),
					'id'     => esc_html__( 'A chosen post ID', 'avix-widgets' ),
				),
				'condition' => array( 'show_featured' => 'yes' ),
			)
		);

		$this->add_control(
			'featured_id',
			array(
				'label'       => esc_html__( 'Post ID', 'avix-widgets' ),
				'description' => esc_html__( 'Find it in Posts → hover a post → "ID". Falls back to the newest post.', 'avix-widgets' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 1,
				'condition'   => array(
					'show_featured'   => 'yes',
					'featured_source' => 'id',
				),
			)
		);

		$this->add_control(
			'featured_label',
			array(
				'label'       => esc_html__( 'Image label', 'avix-widgets' ),
				'description' => esc_html__( 'Small tag on the image. "Latest guide" suits the newest post; for a sticky or chosen post try "Featured guide". Leave empty to hide it.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Latest guide', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_featured' => 'yes' ),
			)
		);

		$this->add_control(
			'featured_button',
			array(
				'label'     => esc_html__( 'Button text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Read the guide', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_featured' => 'yes' ),
			)
		);

		$this->add_control(
			'featured_excerpt',
			array(
				'label'       => esc_html__( 'Excerpt', 'avix-widgets' ),
				'description' => esc_html__( 'Set apart from the card excerpts, so the big card can keep its summary while the grid stays compact.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'condition'   => array( 'show_featured' => 'yes' ),
			)
		);

		$this->add_control(
			'featured_words',
			array(
				'label'     => esc_html__( 'Excerpt length (words)', 'avix-widgets' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 5,
				'max'       => 60,
				'default'   => 32,
				'condition' => array(
					'show_featured'    => 'yes',
					'featured_excerpt' => 'yes',
				),
			)
		);

		$this->add_control(
			'featured_eager',
			array(
				'label'       => esc_html__( 'Load image first', 'avix-widgets' ),
				'description' => esc_html__( 'On when the featured image is in the first screen (faster Largest Contentful Paint).', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'condition'   => array( 'show_featured' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_filters() {
		$this->start_controls_section( 'section_filters', array( 'label' => esc_html__( 'Topic filters', 'avix-widgets' ) ) );

		$this->add_control(
			'show_filters',
			array(
				'label'       => esc_html__( 'Show topic filters', 'avix-widgets' ),
				'description' => esc_html__( '"All" plus each category that has posts (shown when there are at least two). Choosing one swaps the grid without reloading and adds ?topic= to the address, so the view can be shared.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'filters_label',
			array(
				'label'     => esc_html__( 'Label', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Browse by topic', 'avix-widgets' ),
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
			'filters_order',
			array(
				'label'     => esc_html__( 'Topic order', 'avix-widgets' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'count',
				'options'   => array(
					'count' => esc_html__( 'Most posts first', 'avix-widgets' ),
					'name'  => esc_html__( 'A–Z', 'avix-widgets' ),
				),
				'condition' => array( 'show_filters' => 'yes' ),
			)
		);

		$this->add_control(
			'show_counts',
			array(
				'label'     => esc_html__( 'Post counts on chips', 'avix-widgets' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => '',
				'condition' => array( 'show_filters' => 'yes' ),
			)
		);

		$this->add_control(
			'announce_text',
			array(
				'label'       => esc_html__( 'Screen-reader update', 'avix-widgets' ),
				'description' => esc_html__( 'Read out (not shown) after a topic is picked. {topic} and {total} become the topic and its number of articles.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( '{topic}: {total} articles', 'avix-widgets' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_filters' => 'yes' ),
			)
		);

		$this->add_control(
			'filters_seo_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Topic and page links use ?topic= and ?pg=. They are allowed automatically in Yoast SEO\'s "Remove unregistered URL parameters". With another SEO plugin\'s clean-URL option, allow topic and pg there. Purge the page cache after changing these settings.', 'avix-widgets' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
				'condition'       => array( 'show_filters' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_cards() {
		$this->start_controls_section( 'section_cards', array( 'label' => esc_html__( 'Cards', 'avix-widgets' ) ) );

		$this->add_control(
			'columns',
			array(
				'label'       => esc_html__( 'Columns', 'avix-widgets' ),
				'description' => esc_html__( 'On wide screens. Narrower spaces drop to 2 columns, then 1.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '3',
				'options'     => array(
					'2' => '2',
					'3' => '3',
					'4' => '4',
				),
			)
		);

		$this->add_control(
			'show_image',
			array(
				'label'       => esc_html__( 'Images', 'avix-widgets' ),
				'description' => esc_html__( 'Posts without a featured image get a branded pixel cover.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'ratio',
			array(
				'label'     => esc_html__( 'Image shape', 'avix-widgets' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '16 / 10',
				'options'   => array(
					'16 / 10' => '16:10',
					'3 / 2'   => '3:2',
					'4 / 3'   => '4:3',
					'1 / 1'   => '1:1',
				),
				'selectors' => array( '{{WRAPPER}} .avix-pg' => '--pg-ratio: {{VALUE}};' ),
				'condition' => array( 'show_image' => 'yes' ),
			)
		);

		$this->add_control(
			'show_cat',
			array(
				'label'     => esc_html__( 'Category', 'avix-widgets' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'show_date',
			array(
				'label'   => esc_html__( 'Date', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'show_time',
			array(
				'label'       => esc_html__( 'Reading time', 'avix-widgets' ),
				'description' => esc_html__( 'Worked out from the word count (220 words a minute).', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'time_label',
			array(
				'label'     => esc_html__( 'Reading time text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'min read', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_time' => 'yes' ),
			)
		);

		$this->add_control(
			'show_excerpt',
			array(
				'label'     => esc_html__( 'Excerpt', 'avix-widgets' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'excerpt_words',
			array(
				'label'     => esc_html__( 'Excerpt length (words)', 'avix-widgets' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 5,
				'max'       => 60,
				'default'   => 22,
				'condition' => array( 'show_excerpt' => 'yes' ),
			)
		);

		$this->add_control(
			'read_label',
			array(
				'label'     => esc_html__( 'Card link text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Read article', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'separator' => 'before',
			)
		);

		$this->end_controls_section();
	}

	private function controls_cta() {
		$this->start_controls_section( 'section_cta', array( 'label' => esc_html__( 'Project card', 'avix-widgets' ) ) );

		$this->add_control(
			'show_cta',
			array(
				'label'       => esc_html__( 'Show project card', 'avix-widgets' ),
				'description' => esc_html__( 'An orange card in the grid, where the pixel character types on a laptop.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'cta_position',
			array(
				'label'       => esc_html__( 'Position in the grid', 'avix-widgets' ),
				'description' => esc_html__( '4 = the fourth card. When every post fits on the page it goes last and widens to fill the last row, so the grid never ends on a gap.', 'avix-widgets' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 1,
				'max'         => 24,
				'default'     => 4,
				'condition'   => array( 'show_cta' => 'yes' ),
			)
		);

		$this->add_control(
			'cta_eyebrow',
			array(
				'label'     => esc_html__( 'Eyebrow', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Work with us', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_cta' => 'yes' ),
			)
		);

		$this->add_control(
			'cta_title',
			array(
				'label'       => esc_html__( 'Title', 'avix-widgets' ),
				'description' => esc_html__( 'Wrap words in [brackets] to set them in dark ink. Press Enter for a new line.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => "Planning a project?\n[Share your brief.]",
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_cta' => 'yes' ),
			)
		);

		$this->add_control(
			'cta_text',
			array(
				'label'     => esc_html__( 'Text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXTAREA,
				'rows'      => 3,
				'default'   => 'Tell us what you want to build and we\'ll help you choose the right platform and next steps.',
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_cta' => 'yes' ),
			)
		);

		$this->add_control(
			'cta_button',
			array(
				'label'     => esc_html__( 'Button text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Talk to our team', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_cta' => 'yes' ),
			)
		);

		$this->add_control(
			'cta_link',
			array(
				'label'       => esc_html__( 'Link', 'avix-widgets' ),
				'description' => esc_html__( 'The whole card is clickable.', 'avix-widgets' ),
				'type'        => Controls_Manager::URL,
				'dynamic'     => array( 'active' => true ),
				'default'     => array( 'url' => 'https://avixdigital.com/contact/' ),
				'condition'   => array( 'show_cta' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_more() {
		$this->start_controls_section( 'section_more', array( 'label' => esc_html__( 'Load more & empty state', 'avix-widgets' ) ) );

		$this->add_control(
			'show_more',
			array(
				'label'       => esc_html__( 'Load more button', 'avix-widgets' ),
				'description' => esc_html__( 'Adds the next posts without a reload. Without JavaScript it is a plain link to the next page (?pg=2).', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'more_text',
			array(
				'label'     => esc_html__( 'Button text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Load more articles', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_more' => 'yes' ),
			)
		);

		$this->add_control(
			'loading_text',
			array(
				'label'     => esc_html__( 'Loading text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Loading articles…', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_more' => 'yes' ),
			)
		);

		$this->add_control(
			'count_text',
			array(
				'label'       => esc_html__( 'Progress text', 'avix-widgets' ),
				'description' => esc_html__( '{shown} and {total} become numbers (the featured post counts on "All"). Shown while there is more to load, and after "Load more". Leave empty to hide the line.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Showing {shown} of {total} articles', 'avix-widgets' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_more' => 'yes' ),
			)
		);

		$this->add_control(
			'newer_text',
			array(
				'label'       => esc_html__( '"Newer articles" link', 'avix-widgets' ),
				'description' => esc_html__( 'Only on ?pg=2 and later pages (visitors without JavaScript).', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Newer articles', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_more' => 'yes' ),
			)
		);

		$this->add_control(
			'empty_heading',
			array(
				'label'     => esc_html__( 'When there are no posts', 'avix-widgets' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'empty_title',
			array(
				'label'   => esc_html__( 'Title', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Nothing here yet.', 'avix-widgets' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'empty_text',
			array(
				'label'       => esc_html__( 'Text (a topic)', 'avix-widgets' ),
				'description' => esc_html__( 'When the chosen topic has no posts. The button below leads back to all articles.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => 'We haven\'t written about this topic yet. Try another one, or browse every article.',
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'empty_all_text',
			array(
				'label'       => esc_html__( 'Text (no posts at all)', 'avix-widgets' ),
				'description' => esc_html__( 'When the blog (or the chosen categories) has no posts yet. No button is shown.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => esc_html__( 'New articles are on the way. Check back soon.', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'empty_button',
			array(
				'label'   => esc_html__( 'Button text', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'See all articles', 'avix-widgets' ),
				'dynamic' => array( 'active' => true ),
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
				'description' => esc_html__( 'It reads a book on the search bar and looks up and waves when visitors browse the topics or cards while it is in view. The topic label\'s pixel blinks when a topic is picked.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_responsive_control(
			'pal_size',
			array(
				'label'       => esc_html__( 'Character size', 'avix-widgets' ),
				'description' => esc_html__( 'Steps of 10px keep every pixel of the character crisp.', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array(
					'px' => array(
						'min'  => 30,
						'max'  => 80,
						'step' => 10,
					),
				),
				'selectors'   => array( '{{WRAPPER}} .avix-pg' => '--pg-pal-w: {{SIZE}}{{UNIT}};' ),
				'condition'   => array( 'show_pal' => 'yes' ),
			)
		);

		$this->add_control(
			'show_cta_pal',
			array(
				'label'       => esc_html__( 'On the project card', 'avix-widgets' ),
				'description' => esc_html__( 'Typing on a laptop.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'condition'   => array( 'show_pal' => 'yes' ),
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
				'description' => esc_html__( 'Dark sets every colour for a near-black section (text, lines, buttons, search field). The colours below fine-tune either theme.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'light',
				'options'     => array(
					'light' => esc_html__( 'Light', 'avix-widgets' ),
					'dark'  => esc_html__( 'Dark', 'avix-widgets' ),
				),
			)
		);

		$colours = array(
			'bg'          => array( esc_html__( 'Background', 'avix-widgets' ), '--pg-bg' ),
			'ink'         => array( esc_html__( 'Headings', 'avix-widgets' ), '--pg-ink' ),
			'muted'       => array( esc_html__( 'Body text', 'avix-widgets' ), '--pg-muted' ),
			'line'        => array( esc_html__( 'Lines', 'avix-widgets' ), '--pg-line' ),
			'accent'      => array( esc_html__( 'Accent', 'avix-widgets' ), '--pg-accent' ),
			'accent_text' => array( esc_html__( 'Small orange text', 'avix-widgets' ), '--pg-accent-text' ),
			'chip_bg'     => array( esc_html__( 'Soft panels (image, empty state)', 'avix-widgets' ), '--pg-chip-bg' ),
			'btn_bg'      => array( esc_html__( 'Buttons', 'avix-widgets' ), '--pg-btn-bg' ),
			'btn_ink'     => array( esc_html__( 'Button text', 'avix-widgets' ), '--pg-btn-ink' ),
			'surface'     => array( esc_html__( 'Search field & image label', 'avix-widgets' ), '--pg-surface' ),
			'surface_ink' => array( esc_html__( 'Search field text', 'avix-widgets' ), '--pg-surface-ink' ),
			'cover_bg'    => array( esc_html__( 'Pixel cover (no image)', 'avix-widgets' ), '--pg-cover-bg' ),
			'cta_bg'      => array( esc_html__( 'Project card', 'avix-widgets' ), '--pg-cta-bg' ),
			'cta_ink'     => array( esc_html__( 'Project card text', 'avix-widgets' ), '--pg-cta-ink' ),
			'pal'         => array( esc_html__( 'Pixel character', 'avix-widgets' ), '--pg-pal' ),
		);
		foreach ( $colours as $key => $colour ) {
			$this->add_control(
				'color_' . $key,
				array(
					'label'     => $colour[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-pg' => $colour[1] . ': {{VALUE}};' ),
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
				'selectors'          => array( '{{WRAPPER}} .avix-pg' => '--pg-pad-top: {{TOP}}{{UNIT}}; --pg-pad-bottom: {{BOTTOM}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'max_width',
			array(
				'label'      => esc_html__( 'Content width', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 720, 'max' => 1600 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-pg' => '--pg-max: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'gap',
			array(
				'label'      => esc_html__( 'Card gap', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 8, 'max' => 64 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-pg' => '--pg-gap: {{SIZE}}{{UNIT}};' ),
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
			'type_title'   => array( esc_html__( 'Title', 'avix-widgets' ), '.avix-pg__title' ),
			'type_text'    => array( esc_html__( 'Header text', 'avix-widgets' ), '.avix-pg__text' ),
			'type_eyebrow' => array( esc_html__( 'Eyebrow', 'avix-widgets' ), '.avix-pg__eyebrow' ),
			'type_feature' => array( esc_html__( 'Featured title', 'avix-widgets' ), '.avix-pg__feature-title' ),
			'type_card'    => array( esc_html__( 'Card titles', 'avix-widgets' ), '.avix-pg__card-title' ),
			'type_excerpt' => array( esc_html__( 'Excerpts', 'avix-widgets' ), '.avix-pg__excerpt' ),
			'type_meta'    => array( esc_html__( 'Meta line', 'avix-widgets' ), '.avix-pg__meta' ),
			'type_chips'   => array( esc_html__( 'Topic chips', 'avix-widgets' ), '.avix-pg__chip' ),
			'type_cta'     => array( esc_html__( 'Project card title', 'avix-widgets' ), '.avix-pg__cta-title' ),
		);
		foreach ( $groups as $key => $group ) {
			$this->add_group_control(
				Group_Control_Typography::get_type(),
				array(
					'name'     => $key,
					'label'    => $group[0],
					'selector' => '{{WRAPPER}} .avix-pg ' . $group[1],
				)
			);
		}

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ */
	/* Render                                                              */
	/* ------------------------------------------------------------------ */

	protected function render(): void {
		if ( ! class_exists( '\AvixWidgets\Post_Cards' ) && defined( 'AVIX_EW_PATH' ) && file_exists( AVIX_EW_PATH . 'includes/ajax.php' ) ) {
			require_once AVIX_EW_PATH . 'includes/ajax.php';
		}
		if ( ! class_exists( '\AvixWidgets\Post_Cards' ) ) {
			return;
		}

		$s            = $this->get_settings_for_display();
		$uid          = $this->get_id();
		$editor       = $this->is_editor();
		$title_id     = 'avix-pg-title-' . $uid;
		$show_pal     = 'yes' === ( $s['show_pal'] ?? '' );
		$show_filters = 'yes' === ( $s['show_filters'] ?? '' );
		$show_more    = 'yes' === ( $s['show_more'] ?? '' );
		$cats         = array_values( array_filter( array_map( 'sanitize_title', (array) ( $s['include_cats'] ?? array() ) ), 'strlen' ) );
		$title_tag    = Utils::validate_html_tag( (string) ( $s['title_tag'] ?? 'h1' ) );
		$levels       = array( 'h1' => 2, 'h2' => 3, 'h3' => 4 );
		$level        = $levels[ $title_tag ] ?? 2;
		$has_head     = 'yes' === ( $s['show_header'] ?? '' ) && '' !== trim( (string) ( $s['title'] ?? '' ) );

		// Chips only make sense with at least two topics.
		$terms = $show_filters ? $this->topic_terms( $s, $cats ) : array();
		$terms = count( $terms ) >= 2 ? $terms : array();

		$this->url_vars( $uid, $terms || $show_more );

		// The URL picks the topic (only one of this widget's chips) and the
		// page (only with "Load more"), never while designing.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- public, read-only view state; both values are validated.
		$req_topic = '';
		if ( ! $editor && $terms && isset( $_GET[ $this->topic_var ] ) && ! is_array( $_GET[ $this->topic_var ] ) ) {
			$req_topic = sanitize_title( wp_unslash( (string) $_GET[ $this->topic_var ] ) );
			$req_topic = in_array( $req_topic, wp_list_pluck( $terms, 'slug' ), true ) ? $req_topic : '';
		}
		$req_page = 1;
		if ( ! $editor && $show_more && isset( $_GET[ $this->page_var ] ) && ! is_array( $_GET[ $this->page_var ] ) ) {
			$req_page = max( 1, absint( $_GET[ $this->page_var ] ) );
		}
		// phpcs:enable

		$featured = 'yes' === ( $s['show_featured'] ?? '' ) ? $this->featured_post( $s, $cats ) : null;

		// The project card takes a grid slot only when it has something to show.
		$cta = 'yes' === ( $s['show_cta'] ?? '' ) ? $this->cta_html( $s, $show_pal ) : '';

		// Heading outline: with a featured story (h2 under an h1 title) the
		// topic label becomes an h2 too, so the h3 cards sit under "Browse by
		// topic" rather than under the featured article. Without that label the
		// cards share the featured title's level.
		$rail_label   = $terms ? trim( (string) ( $s['filters_label'] ?? '' ) ) : '';
		$rail_heading = $featured && '' !== $rail_label;
		$card_level   = min( 4, $level + ( $rail_heading ? 1 : 0 ) );

		$raw  = array(
			'page'       => $req_page,
			'per_page'   => $s['per_page'] ?? 6,
			'topic'      => $req_topic,
			'cats'       => $cats,
			'exclude'    => $featured ? array( $featured->ID ) : array(),
			'slot'       => '' !== $cta,
			'orderby'    => $s['orderby'] ?? 'date',
			'order'      => $s['order'] ?? 'DESC',
			'words'      => $s['excerpt_words'] ?? 22,
			'excerpt'    => 'yes' === ( $s['show_excerpt'] ?? '' ),
			'image'      => 'yes' === ( $s['show_image'] ?? '' ),
			'cat'        => 'yes' === ( $s['show_cat'] ?? '' ),
			'date'       => 'yes' === ( $s['show_date'] ?? '' ),
			'time'       => 'yes' === ( $s['show_time'] ?? '' ),
			'tag'        => 'h' . $card_level,
			'label'      => $s['read_label'] ?? '',
			'time_label' => $s['time_label'] ?? '',
		);
		$args  = Post_Cards::args( $raw );
		$query = Post_Cards::query( $args );

		// A page number past the end (an old link) falls back to page 1.
		if ( $args['page'] > 1 && ! $query->posts ) {
			$raw['page'] = 1;
			$args        = Post_Cards::args( $raw );
			$query       = Post_Cards::query( $args );
		}

		$first    = 1 === $args['page'];
		$all      = '' === $args['topic'];
		$total    = (int) $query->found_posts;
		$shown    = Post_Cards::shown( $query, $args );
		$has_more = $shown < $total;
		// The featured card belongs to the "All" view: it is visible there (on
		// the first page) and counted in "Showing X of Y", since the grid
		// leaves it out. A topic lists every post, so it steps aside.
		$extra        = $featured && $all ? 1 : 0;
		$feature_seen = $featured && $all && $first;

		if ( ! $featured && 0 === $total && $all && $editor ) {
			echo '<div class="elementor-alert elementor-alert-info">' . esc_html__( 'Post Grid: publish a few posts to fill this grid.', 'avix-widgets' ) . '</div>';
		}

		$classes = array(
			'avix-pg',
			'avix-pg--cols-' . (int) ( in_array( (string) ( $s['columns'] ?? '3' ), array( '2', '3', '4' ), true ) ? $s['columns'] : 3 ),
			'avix-pg--' . ( 'dark' === ( $s['theme'] ?? '' ) ? 'dark' : 'light' ),
		);
		if ( 'yes' === ( $s['clear_header'] ?? '' ) ) {
			$classes[] = 'avix-pg--clear';
		}
		if ( ! $has_head ) {
			$classes[] = 'avix-pg--headless';
		}

		$config = array(
			'ajax'      => admin_url( 'admin-ajax.php' ),
			'action'    => Post_Cards::ACTION,
			'page'      => $args['page'],
			'perPage'   => $args['per_page'],
			'slot'      => $args['slot'] ? 1 : 0,
			'topic'     => $args['topic'],
			'cats'      => implode( ',', $args['cats'] ),
			'exclude'   => implode( ',', $args['exclude'] ),
			'orderby'   => $args['orderby'],
			'order'     => $args['order'],
			'words'     => $args['words'],
			'excerpt'   => $args['excerpt'] ? 1 : 0,
			'image'     => $args['image'] ? 1 : 0,
			'cat'       => $args['cat'] ? 1 : 0,
			'date'      => $args['date'] ? 1 : 0,
			'time'      => $args['time'] ? 1 : 0,
			'tag'       => $args['tag'],
			'label'     => $args['label'],
			'timeLabel' => $args['time_label'],
			'total'     => $total,
			'shown'     => $shown,
			'extra'     => $featured ? 1 : 0,
			'ctaPos'    => max( 1, min( 24, absint( $s['cta_position'] ?? 4 ) ) ),
			'countText' => (string) ( $s['count_text'] ?? '' ),
			'announce'  => (string) ( $s['announce_text'] ?? '' ),
			// The default line has a singular form ("1 article"); a custom line is used as written.
			'announce1' => __( '{topic}: {total} articles', 'avix-widgets' ) === (string) ( $s['announce_text'] ?? '' ) ? __( '{topic}: {total} article', 'avix-widgets' ) : '',
			'moreText'  => (string) ( $s['more_text'] ?? '' ),
			'loading'   => (string) ( $s['loading_text'] ?? '' ),
			'topicVar'  => $this->topic_var,
			'pageVar'   => $this->page_var,
			'urlState'  => $terms || $show_more ? 1 : 0,
		);

		$this->add_render_attribute(
			'root',
			array(
				'class'        => $classes,
				'data-avix-pg' => wp_json_encode( $config ),
			)
		);
		if ( $has_head ) {
			$this->add_render_attribute( 'root', 'aria-labelledby', $title_id );
		} else {
			$this->add_render_attribute( 'root', 'aria-label', esc_html__( 'Articles', 'avix-widgets' ) );
		}

		$base = $this->base_url();
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="avix-pg__inner">
				<?php
				if ( $has_head ) {
					$this->render_header( $s, $title_tag, $title_id, $show_pal );
				}
				if ( $featured ) {
					// Always printed so the script can bring it back on "All".
					$this->render_featured( $s, $featured, $args, $level, $feature_seen );
				}
				if ( $terms ) {
					$this->render_filters( $s, $terms, $args, $base, $uid, $rail_heading ? 'h' . min( 6, $level ) : 'p' );
				}
				?>
				<div class="avix-pg__grid" id="avix-pg-grid-<?php echo esc_attr( $uid ); ?>" data-pg-grid data-pg-zone>
					<?php
					$this->render_cards( $s, $query, $args, $cta, $has_more );
					?>
				</div>
				<?php // Filled by the script after a topic or "Load more", whether or not the foot is visible. ?>
				<p class="avix-pg__sr" role="status" aria-live="polite" data-pg-announce></p>
				<?php
				$this->render_empty( $s, $base, 0 === $total + ( $feature_seen ? 1 : 0 ), $args['topic'] );
				if ( $show_more ) {
					$this->render_more( $s, $args, $base, $total + $extra, $shown + $extra, $has_more );
				}
				?>
			</div>
		</section>
		<?php
		if ( 'yes' === ( $s['show_schema'] ?? '' ) && ! $editor ) {
			$this->render_schema( $feature_seen ? $featured : null, $query );
		}
	}

	/**
	 * Query vars for this instance. The first Post Grid on a page with chips
	 * or "Load more" uses the plain ?topic= and ?pg=; any other one gets its
	 * own (?topic-<id>=), so two grids never filter or page each other.
	 *
	 * @param string $uid      Element ID.
	 * @param bool   $stateful Whether this instance keeps state in the URL.
	 */
	private function url_vars( $uid, $stateful ) {
		static $owner = null;
		if ( null === $owner && $stateful ) {
			$owner = $uid;
		}
		$plain           = null === $owner || $owner === $uid;
		$this->topic_var = $plain ? self::TOPIC_VAR : self::TOPIC_VAR . '-' . $uid;
		$this->page_var  = $plain ? self::PAGE_VAR : self::PAGE_VAR . '-' . $uid;
	}

	private function render_header( array $s, $tag, $title_id, $show_pal ) {
		$eyebrow = trim( (string) ( $s['eyebrow'] ?? '' ) );
		$text    = trim( (string) ( $s['text'] ?? '' ) );
		$search  = 'yes' === ( $s['show_search'] ?? '' );
		?>
		<header class="avix-pg__head">
			<div class="avix-pg__intro" data-pg-rv>
				<?php if ( '' !== $eyebrow ) : ?>
					<p class="avix-pg__eyebrow"><span class="avix-pg__px" aria-hidden="true"></span><?php echo esc_html( $eyebrow ); ?></p>
				<?php endif; ?>
				<<?php echo esc_html( $tag ); ?> class="avix-pg__title" id="<?php echo esc_attr( $title_id ); ?>"><?php echo $this->accent_html( (string) $s['title'], 'avix-pg__accent' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in accent_html(). ?></<?php echo esc_html( $tag ); ?>>
			</div>
			<?php if ( '' !== $text || $search || $show_pal ) : ?>
				<div class="avix-pg__aside" data-pg-rv>
					<?php if ( '' !== $text ) : ?>
						<p class="avix-pg__text"><?php echo esc_html( $text ); ?></p>
					<?php endif; ?>
					<?php if ( $search || $show_pal ) : ?>
						<div class="avix-pg__perch<?php echo $search ? '' : ' avix-pg__perch--ledge'; ?>">
							<?php
							if ( $show_pal ) {
								// A book seen from the front while it is read: an ink cover
								// with the cream page edge on top, in whole grid units.
								echo $this->pal_with( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup built and escaped in Pixel_Pal::render(); the extra rects are static.
									Pixel_Pal::render(
										array(
											'class' => 'avix-pg__pal has-prop-book is-read',
											'hi'    => true,
											'attrs' => array( 'data-pg-pal' => '' ),
										)
									),
									'<rect class="avix-pal__prop avix-pg__book" x="1" y="4" width="8" height="3"/><rect class="avix-pal__prop avix-pg__book-pages" x="2" y="4" width="6" height="1"/>'
								);
							}
							if ( $search ) {
								$this->render_search( $s );
							} else {
								echo '<span class="avix-pg__ledge" aria-hidden="true"></span>';
							}
							?>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</header>
		<?php
	}

	private function render_search( array $s ) {
		$field_id    = 'avix-pg-s-' . $this->get_id();
		$placeholder = trim( (string) ( $s['search_placeholder'] ?? '' ) );
		$button      = trim( (string) ( $s['search_button'] ?? '' ) );
		$button      = '' !== $button ? $button : __( 'Search', 'avix-widgets' );
		?>
		<form class="avix-pg__search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label class="avix-pg__sr" for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( '' !== $placeholder ? $placeholder : $button ); ?></label>
			<svg class="avix-pg__search-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/></svg>
			<input class="avix-pg__input" type="search" id="<?php echo esc_attr( $field_id ); ?>" name="s" placeholder="<?php echo esc_attr( $placeholder ); ?>" autocomplete="off" data-pg-search>
			<input type="hidden" name="post_type" value="post">
			<button class="avix-pg__search-btn" type="submit" aria-label="<?php echo esc_attr( $button ); ?>">
				<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
			</button>
		</form>
		<?php
	}

	private function render_featured( array $s, \WP_Post $post, array $args, $level, $visible ) {
		$term   = Post_Cards::term( $post );
		$title  = wp_strip_all_tags( get_the_title( $post ) );
		$tag    = 'h' . min( 6, $level );
		$label  = trim( (string) ( $s['featured_label'] ?? '' ) );
		$button = trim( (string) ( $s['featured_button'] ?? '' ) );
		$eager  = $visible && 'yes' === ( $s['featured_eager'] ?? '' );
		$image  = Post_Cards::image( $post, 'large', '(max-width: 900px) 100vw, 760px', $eager, 'avix-pg__img' );
		$words  = max( 5, min( 60, absint( $s['featured_words'] ?? 32 ) ) );
		$text   = 'yes' === ( $s['featured_excerpt'] ?? 'yes' ) ? Post_Cards::excerpt( $post, $words ) : '';
		$meta   = Post_Cards::meta( $post, $args, $term, true );
		?>
		<article class="avix-pg__feature<?php echo '' !== $image ? '' : ' is-placeholder'; ?>" data-pg-feature data-pg-rv<?php echo $visible ? '' : ' hidden'; ?>>
			<div class="avix-pg__feature-media">
				<?php
				echo '' !== $image ? $image : Post_Cards::placeholder( $post, $term ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with wp_get_attachment_image() / escaped in placeholder().
				if ( '' !== $label ) {
					echo '<span class="avix-pg__badge"><span class="avix-pg__px" aria-hidden="true"></span>' . esc_html( $label ) . '</span>';
				}
				?>
			</div>
			<div class="avix-pg__feature-body">
				<?php if ( ( $args['cat'] && $term ) || '' !== $meta ) : ?>
					<div class="avix-pg__feature-top">
						<?php if ( $args['cat'] && $term ) : ?>
							<span class="avix-pg__chip-tag"><?php echo esc_html( $term->name ); ?></span>
						<?php endif; ?>
						<?php echo $meta; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Post_Cards::meta(). ?>
					</div>
				<?php endif; ?>
				<<?php echo esc_html( $tag ); ?> class="avix-pg__feature-title"><a class="avix-pg__link" href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( '' !== $title ? $title : __( '(Untitled)', 'avix-widgets' ) ); ?></a></<?php echo esc_html( $tag ); ?>>
				<?php if ( '' !== $text ) : ?>
					<p class="avix-pg__feature-excerpt"><?php echo esc_html( $text ); ?></p>
				<?php endif; ?>
				<?php if ( '' !== $button ) : ?>
					<span class="avix-pg__btn" aria-hidden="true"><?php echo esc_html( $button ); ?><span class="avix-pg__btn-icon"><?php echo Post_Cards::arrow(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span></span>
				<?php endif; ?>
			</div>
		</article>
		<?php
	}

	private function render_filters( array $s, array $terms, array $args, $base, $uid, $label_tag = 'p' ) {
		$label  = trim( (string) ( $s['filters_label'] ?? '' ) );
		$all    = trim( (string) ( $s['all_label'] ?? '' ) );
		$counts = 'yes' === ( $s['show_counts'] ?? '' );
		$nav_id = 'avix-pg-topics-' . $uid;
		$chips  = array(
			array(
				'slug'  => '',
				'name'  => '' !== $all ? $all : __( 'All', 'avix-widgets' ),
				'count' => null,
				'url'   => remove_query_arg( array( $this->topic_var, $this->page_var ), $base ),
			),
		);
		foreach ( $terms as $term ) {
			$chips[] = array(
				'slug'  => $term->slug,
				'name'  => $term->name,
				'count' => (int) $term->count,
				'url'   => add_query_arg( $this->topic_var, $term->slug, remove_query_arg( array( $this->topic_var, $this->page_var ), $base ) ),
			);
		}
		?>
		<div class="avix-pg__rail" data-pg-zone data-pg-rv>
			<?php if ( '' !== $label ) : ?>
				<<?php echo esc_html( $label_tag ); ?> class="avix-pg__rail-label" id="<?php echo esc_attr( $nav_id ); ?>"><span class="avix-pg__px" aria-hidden="true" data-pg-blink></span><?php echo esc_html( $label ); ?></<?php echo esc_html( $label_tag ); ?>>
			<?php endif; ?>
			<nav class="avix-pg__topics" <?php echo '' !== $label ? 'aria-labelledby="' . esc_attr( $nav_id ) . '"' : 'aria-label="' . esc_attr__( 'Topics', 'avix-widgets' ) . '"'; ?>>
				<ul class="avix-pg__chips" data-pg-chips>
					<?php foreach ( $chips as $chip ) : ?>
						<?php $active = $chip['slug'] === $args['topic']; ?>
						<li class="avix-pg__chip-item">
							<a class="avix-pg__chip<?php echo $active ? ' is-active' : ''; ?>" href="<?php echo esc_url( $chip['url'] ); ?>" data-pg-topic="<?php echo esc_attr( $chip['slug'] ); ?>" data-pg-name="<?php echo esc_attr( $chip['name'] ); ?>" aria-controls="avix-pg-grid-<?php echo esc_attr( $uid ); ?>"<?php echo $active ? ' aria-current="true"' : ''; ?>>
								<?php echo esc_html( $chip['name'] ); ?>
								<?php if ( $counts && null !== $chip['count'] ) : ?>
									<span class="avix-pg__chip-n"><?php echo esc_html( number_format_i18n( $chip['count'] ) ); ?></span>
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
	 * Cards with the project card slotted in at its position. When every post
	 * fits on this page it goes last instead, where the CSS widens it over the
	 * cells the posts leave empty: the grid never ends on a hole. The script
	 * keeps the same rule after filtering.
	 *
	 * @param array     $s        Settings.
	 * @param \WP_Query $query    Grid query.
	 * @param array     $args     Output of Post_Cards::args().
	 * @param string    $cta      Project card markup.
	 * @param bool      $has_more Whether more posts follow this page.
	 */
	private function render_cards( array $s, \WP_Query $query, array $args, $cta, $has_more ) {
		list( $start ) = Post_Cards::window( $args );
		$cta   = 1 === $args['page'] ? (string) $cta : '';
		$pos   = $has_more ? max( 1, min( 24, absint( $s['cta_position'] ?? 4 ) ) ) - 1 : PHP_INT_MAX;
		$count = count( $query->posts );

		foreach ( $query->posts as $i => $post ) {
			if ( '' !== $cta && $i === $pos ) {
				echo $cta; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in cta_html().
				$cta = '';
			}
			echo Post_Cards::card( $post, $args, $start + $i ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Post_Cards::card().
		}
		if ( '' !== $cta ) {
			// Fewer posts than the position: last. With no posts it stays, hidden, for the script.
			echo 0 === $count ? str_replace( 'data-pg-cta', 'data-pg-cta hidden', $cta ) : $cta; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in cta_html().
		}
	}

	private function cta_html( array $s, $show_pal ) {
		$title = trim( (string) ( $s['cta_title'] ?? '' ) );
		$link  = (array) ( $s['cta_link'] ?? array() );
		$url   = (string) ( $link['url'] ?? '' );
		if ( '' === $title && '' === $url ) {
			return '';
		}
		$eyebrow = trim( (string) ( $s['cta_eyebrow'] ?? '' ) );
		$text    = trim( (string) ( $s['cta_text'] ?? '' ) );
		$button  = trim( (string) ( $s['cta_button'] ?? '' ) );

		$key = 'cta_link_' . $this->get_id();
		if ( '' !== $url ) {
			$this->add_link_attributes( $key, $link );
			$this->add_render_attribute( $key, 'class', 'avix-pg__cta-btn' );
		}

		$html  = '<aside class="avix-pg__cta" data-pg-cta aria-label="' . esc_attr( wp_strip_all_tags( str_replace( array( '[', ']' ), '', $title ) ) ) . '">';
		$html .= '<div class="avix-pg__cta-in"><div class="avix-pg__cta-top">';
		if ( '' !== $eyebrow ) {
			$html .= '<p class="avix-pg__cta-eyebrow"><span class="avix-pg__px" aria-hidden="true"></span>' . esc_html( $eyebrow ) . '</p>';
		}
		if ( '' !== $title ) {
			$html .= '<p class="avix-pg__cta-title">' . $this->accent_html( $title, 'avix-pg__cta-accent' ) . '</p>';
		}
		if ( '' !== $text ) {
			$html .= '<p class="avix-pg__cta-text">' . esc_html( $text ) . '</p>';
		}
		$html .= '</div><div class="avix-pg__cta-foot">';
		if ( '' !== $url ) {
			$html .= '<a ' . $this->get_render_attribute_string( $key ) . '><span>' . esc_html( '' !== $button ? $button : __( 'Get in touch', 'avix-widgets' ) ) . '</span><span class="avix-pg__cta-icon">' . Post_Cards::arrow() . '</span></a>';
		}
		if ( $show_pal && 'yes' === ( $s['show_cta_pal'] ?? '' ) ) {
			// A logo pixel on the lid tells the laptop from a plain dark block.
			$html .= '<span class="avix-pg__desk">' . $this->pal_with(
				Pixel_Pal::render(
					array(
						'class' => 'avix-pg__cta-pal is-type is-still',
						'prop'  => 'laptop',
					)
				),
				'<rect class="avix-pg__logo" x="4.5" y="4.5" width="1" height="1"/>'
			) . '</span>';
		}
		$html .= '</div></div></aside>';
		return $html;
	}

	/**
	 * Empty state. Inside a topic it offers the way back to all articles; with
	 * no posts at all it only says more are coming (a button would lead
	 * nowhere). The script switches between the two.
	 *
	 * @param array  $s       Settings.
	 * @param string $base    Base address.
	 * @param bool   $visible Whether nothing is listed.
	 * @param string $topic   Active topic.
	 */
	private function render_empty( array $s, $base, $visible, $topic ) {
		$title    = trim( (string) ( $s['empty_title'] ?? '' ) );
		$text     = trim( (string) ( $s['empty_text'] ?? '' ) );
		$all_text = trim( (string) ( $s['empty_all_text'] ?? '' ) );
		$button   = trim( (string) ( $s['empty_button'] ?? '' ) );
		$in_topic = '' !== $topic;
		?>
		<div class="avix-pg__empty" data-pg-empty<?php echo $visible ? '' : ' hidden'; ?>>
			<?php
			if ( 'yes' === ( $s['show_pal'] ?? '' ) ) {
				echo '<span class="avix-pg__empty-stage" aria-hidden="true">' . Pixel_Pal::render( array( 'class' => 'avix-pg__empty-pal' ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Pixel_Pal::render().
			}
			?>
			<?php if ( '' !== $title ) : ?>
				<p class="avix-pg__empty-title"><?php echo esc_html( $title ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $text ) : ?>
				<p class="avix-pg__empty-text" data-pg-empty-topic<?php echo $in_topic ? '' : ' hidden'; ?>><?php echo esc_html( $text ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $all_text ) : ?>
				<p class="avix-pg__empty-text" data-pg-empty-all<?php echo $in_topic ? ' hidden' : ''; ?>><?php echo esc_html( $all_text ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $button ) : ?>
				<a class="avix-pg__empty-btn" href="<?php echo esc_url( remove_query_arg( array( $this->topic_var, $this->page_var ), $base ) ); ?>" data-pg-topic="" data-pg-empty-topic<?php echo $in_topic ? '' : ' hidden'; ?>><?php echo esc_html( $button ); ?></a>
			<?php endif; ?>
		</div>
		<?php
	}

	private function render_more( array $s, array $args, $base, $total, $shown, $has_more ) {
		$count = trim( (string) ( $s['count_text'] ?? '' ) );
		$text  = trim( (string) ( $s['more_text'] ?? '' ) );
		$text  = '' !== $text ? $text : __( 'Load more articles', 'avix-widgets' );
		$newer = trim( (string) ( $s['newer_text'] ?? '' ) );
		$next  = add_query_arg( $this->page_var, $args['page'] + 1, $base );
		if ( '' !== $args['topic'] ) {
			$next = add_query_arg( $this->topic_var, $args['topic'], $next );
		}
		$ratio = $total > 0 ? min( 1, $shown / $total ) : 0;
		// Nothing to load and nothing loaded yet: "Showing 2 of 2" adds nothing.
		$idle = ! $has_more && 1 === $args['page'];
		?>
		<div class="avix-pg__foot" data-pg-foot data-pg-rv<?php echo $idle ? ' hidden' : ''; ?>>
			<?php if ( '' !== $count ) : ?>
				<p class="avix-pg__status" data-pg-status><?php echo $this->count_html( $count, $shown, $total ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in count_html(). ?></p>
				<span class="avix-pg__bar" aria-hidden="true"><span class="avix-pg__bar-fill" data-pg-bar style="--pg-progress:<?php echo esc_attr( round( $ratio, 4 ) ); ?>"></span></span>
			<?php endif; ?>
			<div class="avix-pg__actions">
				<?php if ( $args['page'] > 1 && '' !== $newer ) : ?>
					<a class="avix-pg__newer" href="<?php echo esc_url( $this->page_url( $args, $base, $args['page'] - 1 ) ); ?>"><?php echo esc_html( $newer ); ?></a>
				<?php endif; ?>
				<a class="avix-pg__more" href="<?php echo esc_url( $next ); ?>" data-pg-more<?php echo $has_more ? '' : ' hidden'; ?>>
					<span class="avix-pg__more-text" data-pg-more-text><?php echo esc_html( $text ); ?></span>
					<span class="avix-pg__more-icon" aria-hidden="true">
						<svg class="avix-pg__plus" viewBox="0 0 24 24" focusable="false"><path d="M12 5v14M5 12h14"/></svg>
						<svg class="avix-pg__dots" viewBox="0 0 7 3" focusable="false"><rect x="0" y="1" width="1" height="1"/><rect x="3" y="1" width="1" height="1"/><rect x="6" y="1" width="1" height="1"/></svg>
					</span>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * ItemList of the articles in the first view: once per page, as search
	 * engines read the first one.
	 *
	 * @param \WP_Post|null $featured Featured post.
	 * @param \WP_Query     $query    Grid query.
	 */
	private function render_schema( $featured, \WP_Query $query ) {
		static $printed = false;
		$posts          = array_merge( $featured ? array( $featured ) : array(), $query->posts );
		if ( $printed || ! $posts ) {
			return;
		}
		$printed = true;
		$items   = array();
		foreach ( array_values( $posts ) as $i => $post ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'url'      => get_permalink( $post ),
				'name'     => wp_strip_all_tags( get_the_title( $post ) ),
			);
		}
		$data = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'ItemList',
			'itemListElement' => $items,
		);
		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP ) . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON with HEX_TAG/HEX_AMP.
	}

	/* ------------------------------------------------------------------ */
	/* Data                                                                */
	/* ------------------------------------------------------------------ */

	/**
	 * Newest post, a sticky one, or a chosen ID; always within the allowed
	 * categories, never password protected.
	 *
	 * @param array $s    Settings.
	 * @param array $cats Allowed category slugs.
	 * @return \WP_Post|null
	 */
	private function featured_post( array $s, array $cats ) {
		$source = (string) ( $s['featured_source'] ?? 'latest' );
		$in     = array();
		if ( 'id' === $source ) {
			$in = array( absint( $s['featured_id'] ?? 0 ) );
		} elseif ( 'sticky' === $source ) {
			$in = array_slice( array_filter( array_map( 'absint', (array) get_option( 'sticky_posts', array() ) ) ), 0, 20 );
		}
		$in = array_values( array_filter( $in ) );

		$base = array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => 1,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'has_password'        => false,
			'orderby'             => array(
				'date' => 'DESC',
				'ID'   => 'DESC',
			),
		);
		if ( $cats ) {
			$base['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'category',
					'field'    => 'slug',
					'terms'    => $cats,
				),
			);
		}
		if ( $in ) {
			$posts = get_posts( array_merge( $base, array( 'post__in' => $in ) ) );
			if ( $posts ) {
				return $posts[0];
			}
		}
		$posts = get_posts( $base );
		return $posts ? $posts[0] : null;
	}

	/**
	 * Categories that have posts, limited to the chosen ones. "Uncategorized"
	 * (and any slug added through the avix_post_grid_hidden_topics filter) only
	 * shows when it was chosen on purpose. Matched by slug, so a site whose
	 * default category is a real topic keeps that topic's chip.
	 *
	 * @param array $s    Settings.
	 * @param array $cats Allowed slugs.
	 */
	private function topic_terms( array $s, array $cats ) {
		$query = array(
			'taxonomy'   => 'category',
			'hide_empty' => true,
			'orderby'    => 'name' === ( $s['filters_order'] ?? 'count' ) ? 'name' : 'count',
			'order'      => 'name' === ( $s['filters_order'] ?? 'count' ) ? 'ASC' : 'DESC',
			'number'     => 40,
		);
		if ( $cats ) {
			$query['slug'] = $cats;
		}
		$terms = get_terms( $query );
		if ( ! is_array( $terms ) ) {
			return array();
		}
		if ( ! $cats ) {
			$hidden = Post_Cards::hidden_topics();
			$terms  = array_filter(
				$terms,
				static function ( $term ) use ( $hidden ) {
					return ! in_array( $term->slug, $hidden, true );
				}
			);
		}
		return array_slice( array_values( $terms ), 0, 30 );
	}

	/** Category slug => name, for the "Only these categories" control. */
	private function category_options() {
		$options = array();
		$terms   = get_terms(
			array(
				'taxonomy'   => 'category',
				'hide_empty' => false,
				'number'     => 200,
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
	 * Base address for the topic chips and the next-page link (so they work
	 * without JavaScript): the page's path plus only the state of OTHER Post
	 * Grids on it. Never the visitor's own query string: page caches such as
	 * LiteSpeed leave utm_*, gclid and fbclid out of the cache key, so one
	 * visitor's tracking IDs would end up in every cached copy's links.
	 */
	private function base_url() {
		if ( $this->is_editor() || wp_doing_ajax() ) {
			$id = get_queried_object_id();
			return $id ? (string) get_permalink( $id ) : home_url( '/' );
		}
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised by esc_url_raw.
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		$base = '' !== $path ? $path : '/';
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- public view state; keys are matched and values sanitised below.
		foreach ( $_GET as $key => $value ) {
			$key = (string) $key;
			if ( is_array( $value ) || $key === $this->topic_var || $key === $this->page_var || ! preg_match( Post_Cards::VAR_PATTERN, $key ) ) {
				continue;
			}
			$value = 0 === strpos( $key, self::PAGE_VAR ) ? (string) max( 1, absint( $value ) ) : sanitize_title( wp_unslash( (string) $value ) );
			if ( '' !== $value ) {
				$base = add_query_arg( $key, rawurlencode( $value ), $base );
			}
		}
		// phpcs:enable
		return $base;
	}

	/**
	 * Address of one page of the current view (topic kept).
	 *
	 * @param array  $args Output of Post_Cards::args().
	 * @param string $base Base address.
	 * @param int    $page Page.
	 */
	private function page_url( array $args, $base, $page ) {
		$url = $page > 1 ? add_query_arg( $this->page_var, $page, $base ) : $base;
		return '' !== $args['topic'] ? add_query_arg( $this->topic_var, $args['topic'], $url ) : $url;
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
	 * "Showing {shown} of {total} articles" with the numbers in <strong>.
	 *
	 * @param string $template Text with tokens.
	 * @param int    $shown    Shown.
	 * @param int    $total    Total.
	 */
	private function count_html( $template, $shown, $total ) {
		return strtr(
			esc_html( $template ),
			array(
				'{shown}' => '<strong data-pg-shown>' . esc_html( number_format_i18n( $shown ) ) . '</strong>',
				'{total}' => '<strong data-pg-total>' . esc_html( number_format_i18n( $total ) ) . '</strong>',
			)
		);
	}

	/**
	 * Adds widget-only pixels (a book cover, a laptop logo) to the shared
	 * character's sprite, drawn on top of its props.
	 *
	 * @param string $html  Pixel_Pal::render() output.
	 * @param string $rects Static <rect> markup.
	 */
	private function pal_with( $html, $rects ) {
		$at = strrpos( $html, '</svg>' );
		return false === $at ? $html : substr_replace( $html, $rects, $at, 0 );
	}

	private function is_editor() {
		return class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor && \Elementor\Plugin::$instance->editor->is_edit_mode();
	}
}
