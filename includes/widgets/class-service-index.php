<?php
/**
 * Service Index: the services as an editorial index of numbered rows. On a
 * desktop, hovering a row tints it and shows its image on a preview card, docked
 * in the column the visitor is not reading, with the Avix pixel character
 * riding on top; on touch screens and in narrow
 * columns every service becomes a card with its image on top.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Widgets;

use AvixWidgets\Pixel_Pal;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;

defined( 'ABSPATH' ) || exit;

class Service_Index extends Widget_Base {

	use Media;

	/**
	 * Live uploads used by the default rows (the Media trait resolves them to
	 * attachments when they exist in the library).
	 */
	const UPLOADS = 'https://avixdigital.com/wp-content/uploads/';

	public function get_name(): string {
		return 'avix-service-index';
	}

	public function get_title(): string {
		return esc_html__( 'Service Index', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-bullet-list';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'services', 'index', 'list', 'rows', 'hover', 'preview', 'pixel', 'avix' );
	}

	public function get_style_depends(): array {
		return array( 'avix-service-index' );
	}

	public function get_script_depends(): array {
		return array( 'avix-service-index' );
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/**
	 * The list can come from a post query, so Elementor's element cache must
	 * never freeze it.
	 */
	protected function is_dynamic_content(): bool {
		return true;
	}

	/* ------------------------------------------------------------------ */
	/* Controls                                                            */
	/* ------------------------------------------------------------------ */

	protected function register_controls(): void {
		$this->controls_header();
		$this->controls_items();
		$this->controls_preview();
		$this->controls_footer();
		$this->controls_buddy();
		$this->controls_style();
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
				'description' => esc_html__( 'Small label above the title, with an orange pixel in front.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'What we do', 'avix-widgets' ),
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
				'default'     => 'What we build, [and how we plan it.]',
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
				'options'   => array(
					'h1'  => 'H1',
					'h2'  => 'H2',
					'h3'  => 'H3',
					'div' => 'div',
					'p'   => 'p',
				),
				'condition' => array( 'show_header' => 'yes' ),
			)
		);

		$this->add_control(
			'text',
			array(
				'label'     => esc_html__( 'Text (optional)', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXTAREA,
				'rows'      => 3,
				'default'   => esc_html__( 'Every project starts with discovery. We look at your goals, content and integrations first, then recommend the platform that fits.', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_header' => 'yes' ),
			)
		);

		$this->add_control(
			'head_link_text',
			array(
				'label'       => esc_html__( 'Header link text (optional)', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Book a discovery call', 'avix-widgets' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_header' => 'yes' ),
			)
		);

		$this->add_control(
			'head_link',
			array(
				'label'     => esc_html__( 'Header link', 'avix-widgets' ),
				'type'      => Controls_Manager::URL,
				'default'   => array( 'url' => 'https://calendly.com/akibzawayed0079/meeting-for-quote' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_header' => 'yes' ),
			)
		);

		$this->add_control(
			'head_layout',
			array(
				'label'       => esc_html__( 'Header layout', 'avix-widgets' ),
				'description' => esc_html__( 'Split puts the text beside the title on wide screens. It always stacks on phones.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'split',
				'options'     => array(
					'split'   => esc_html__( 'Split (title left, text right)', 'avix-widgets' ),
					'stacked' => esc_html__( 'Stacked', 'avix-widgets' ),
				),
				'condition'   => array( 'show_header' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_items() {
		$this->start_controls_section( 'section_items', array( 'label' => esc_html__( 'Services', 'avix-widgets' ) ) );

		$this->add_control(
			'source',
			array(
				'label'   => esc_html__( 'Services from', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'manual',
				'options' => array(
					'manual' => esc_html__( 'Manual list', 'avix-widgets' ),
					'query'  => esc_html__( 'Posts (e.g. your Services pages)', 'avix-widgets' ),
				),
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Service', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'New service', 'avix-widgets' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'category',
			array(
				'label'       => esc_html__( 'Category label', 'avix-widgets' ),
				'description' => esc_html__( 'Small orange label above the name, e.g. “Ecommerce”.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Category', 'avix-widgets' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'number',
			array(
				'label'       => esc_html__( 'Number (optional)', 'avix-widgets' ),
				'description' => esc_html__( 'Leave empty to count automatically: 01, 02, 03…', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'text',
			array(
				'label'   => esc_html__( 'Text', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 4,
				'default' => esc_html__( 'A sentence or two on what this service covers and who it is for.', 'avix-widgets' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'tags',
			array(
				'label'       => esc_html__( 'Tags', 'avix-widgets' ),
				'description' => esc_html__( 'Separate with commas. Each one becomes a small chip.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'icons',
			array(
				'label'       => esc_html__( 'Platform logos', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => Brand_Icons::options(),
			)
		);

		$repeater->add_control(
			'image',
			array(
				'label'       => esc_html__( 'Image', 'avix-widgets' ),
				'description' => esc_html__( 'Shown on the hover preview on desktop; sits on top of the card on phones and tablets.', 'avix-widgets' ),
				'type'        => Controls_Manager::MEDIA,
				'dynamic'     => array( 'active' => true ),
			)
		);

		foreach ( array(
			'image_focus_x' => esc_html__( 'Image focus X', 'avix-widgets' ),
			'image_focus_y' => esc_html__( 'Image focus Y', 'avix-widgets' ),
		) as $key => $label ) {
			$repeater->add_control(
				$key,
				array(
					'label'       => $label,
					'description' => 'image_focus_y' === $key ? esc_html__( 'Which part of the image stays in view when the card crops it.', 'avix-widgets' ) : '',
					'type'        => Controls_Manager::SLIDER,
					'size_units'  => array( '%' ),
					'range'       => array( '%' => array( 'min' => 0, 'max' => 100 ) ),
					'default'     => array( 'unit' => '%', 'size' => 50 ),
				)
			);
		}

		$repeater->add_control(
			'image_alt',
			array(
				'label'       => esc_html__( 'Image description (alt)', 'avix-widgets' ),
				'description' => esc_html__( 'Leave empty to use the image’s own alt text or the service name.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'link',
			array(
				'label'   => esc_html__( 'Link', 'avix-widgets' ),
				'type'    => Controls_Manager::URL,
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'link_label',
			array(
				'label'       => esc_html__( 'Link label', 'avix-widgets' ),
				'description' => esc_html__( 'Shown on the preview card and on phone cards; read out by screen readers.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'items',
			array(
				'label'       => esc_html__( 'Services', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => $this->default_items(),
				'condition'   => array( 'source' => 'manual' ),
			)
		);

		$this->add_control(
			'query_post_type',
			array(
				'label'     => esc_html__( 'Post type', 'avix-widgets' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => $this->default_post_type(),
				'options'   => $this->post_type_options(),
				'condition' => array( 'source' => 'query' ),
			)
		);

		$this->add_control(
			'query_count',
			array(
				'label'     => esc_html__( 'How many', 'avix-widgets' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 4,
				'min'       => 1,
				'max'       => 12,
				'condition' => array( 'source' => 'query' ),
			)
		);

		$this->add_control(
			'query_ids',
			array(
				'label'       => esc_html__( 'Only these IDs (optional)', 'avix-widgets' ),
				'description' => esc_html__( 'Comma-separated post IDs, shown in this order.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'source' => 'query' ),
			)
		);

		$this->add_control(
			'query_orderby',
			array(
				'label'     => esc_html__( 'Order by', 'avix-widgets' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'menu_order',
				'options'   => array(
					'menu_order' => esc_html__( 'Page order', 'avix-widgets' ),
					'date'       => esc_html__( 'Date', 'avix-widgets' ),
					'title'      => esc_html__( 'Title', 'avix-widgets' ),
				),
				'condition' => array( 'source' => 'query' ),
			)
		);

		$this->add_control(
			'query_order',
			array(
				'label'     => esc_html__( 'Order', 'avix-widgets' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'ASC',
				'options'   => array(
					'ASC'  => esc_html__( 'Ascending', 'avix-widgets' ),
					'DESC' => esc_html__( 'Descending', 'avix-widgets' ),
				),
				'condition' => array( 'source' => 'query' ),
			)
		);

		$taxonomies = array( '' => esc_html__( 'None', 'avix-widgets' ) ) + $this->taxonomy_options();

		$this->add_control(
			'query_cat_tax',
			array(
				'label'       => esc_html__( 'Category label from', 'avix-widgets' ),
				'description' => esc_html__( 'The first term becomes the small orange label.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => $taxonomies,
				'condition'   => array( 'source' => 'query' ),
			)
		);

		$this->add_control(
			'query_terms',
			array(
				'label'       => esc_html__( 'Only these categories (optional)', 'avix-widgets' ),
				'description' => esc_html__( 'Comma-separated term slugs from the taxonomy above, e.g. development, ecommerce.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array(
					'source'         => 'query',
					'query_cat_tax!' => '',
				),
			)
		);

		$this->add_control(
			'query_tag_tax',
			array(
				'label'       => esc_html__( 'Tags from', 'avix-widgets' ),
				'description' => esc_html__( 'Up to four terms become chips.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => $taxonomies,
				'condition'   => array( 'source' => 'query' ),
			)
		);

		$this->add_control(
			'query_words',
			array(
				'label'       => esc_html__( 'Text length (words)', 'avix-widgets' ),
				'description' => esc_html__( 'Taken from the excerpt. The featured image becomes the preview.', 'avix-widgets' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 28,
				'min'         => 8,
				'max'         => 60,
				'condition'   => array( 'source' => 'query' ),
			)
		);

		$this->add_control(
			'query_link_label',
			array(
				'label'       => esc_html__( 'Link label', 'avix-widgets' ),
				/* translators: %s stays as typed: it is the placeholder editors write for the post title. */
				'description' => esc_html__( 'Short is best, e.g. “Explore” or “Read more”. Write %s where the post title should go. Screen readers always hear the title.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Explore', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'source' => 'query' ),
			)
		);

		$this->add_control(
			'item_tag',
			array(
				'label'     => esc_html__( 'Service name tag', 'avix-widgets' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'h3',
				'options'   => array(
					'h2'  => 'H2',
					'h3'  => 'H3',
					'h4'  => 'H4',
					'div' => 'div',
				),
				'separator' => 'before',
			)
		);

		$toggles = array(
			'show_number'   => esc_html__( 'Numbers (01, 02…)', 'avix-widgets' ),
			'show_category' => esc_html__( 'Category labels', 'avix-widgets' ),
			'show_text'     => esc_html__( 'Text', 'avix-widgets' ),
			'show_icons'    => esc_html__( 'Platform logos', 'avix-widgets' ),
			'show_tags'     => esc_html__( 'Tags', 'avix-widgets' ),
		);
		foreach ( $toggles as $key => $label ) {
			$this->add_control(
				$key,
				array(
					'label'   => $label,
					'type'    => Controls_Manager::SWITCHER,
					'default' => 'yes',
				)
			);
		}

		$this->add_control(
			'card_ratio',
			array(
				'label'       => esc_html__( 'Card image shape (phones & tablets)', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '16 / 10',
				'options'     => array(
					'16 / 10' => '16:10',
					'16 / 9'  => '16:9',
					'4 / 3'   => '4:3',
					'1 / 1'   => '1:1',
				),
				'separator'   => 'before',
			)
		);

		$this->end_controls_section();
	}

	private function controls_preview() {
		$this->start_controls_section( 'section_preview', array( 'label' => esc_html__( 'Hover Preview', 'avix-widgets' ) ) );

		$this->add_control(
			'preview_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'On desktops, hovering a linked row shows its image on a card in the column the visitor is not reading, so the text stays clear. Every card in the list has the same size: the image is cropped to fit the rows, while the link label and the character keep their real size. Phones, tablets and narrow columns show the image on top of each card instead. The preview is off while you edit in Elementor.', 'avix-widgets' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->add_control(
			'show_preview',
			array(
				'label'   => esc_html__( 'Show image preview on hover', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'preview_tilt',
			array(
				'label'       => esc_html__( 'Swing with the mouse', 'avix-widgets' ),
				'description' => esc_html__( 'The card leans a little in the direction the mouse moves.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'condition'   => array( 'show_preview' => 'yes' ),
			)
		);

		$this->add_control(
			'show_chip',
			array(
				'label'     => esc_html__( 'Show the link label on the card', 'avix-widgets' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'show_preview' => 'yes' ),
			)
		);

		$this->add_control(
			'preview_ratio',
			array(
				'label'       => esc_html__( 'Card shape', 'avix-widgets' ),
				'description' => esc_html__( 'The tallest the card gets. When the rows are shorter, the image is cropped to the row height.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '18 / 13',
				'options'     => array(
					'18 / 13' => esc_html__( 'Landscape (360×260)', 'avix-widgets' ),
					'16 / 10' => '16:10',
					'4 / 3'   => '4:3',
					'1 / 1'   => '1:1',
					'4 / 5'   => esc_html__( 'Portrait 4:5', 'avix-widgets' ),
				),
				'condition'   => array( 'show_preview' => 'yes' ),
			)
		);

		$this->add_control(
			'preview_width',
			array(
				'label'       => esc_html__( 'Card width', 'avix-widgets' ),
				'description' => esc_html__( 'The widest the card gets. It never grows past the column it docks in.', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 220, 'max' => 520 ) ),
				'selectors'   => array( '{{WRAPPER}} .avix-si' => '--si-preview-w: {{SIZE}}{{UNIT}};' ),
				'condition'   => array( 'show_preview' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_footer() {
		$this->start_controls_section( 'section_footer', array( 'label' => esc_html__( 'Footer Note', 'avix-widgets' ) ) );

		$this->add_control(
			'show_footer',
			array(
				'label'       => esc_html__( 'Show footer note', 'avix-widgets' ),
				'description' => esc_html__( 'A help bar under the list. The pixel character stands on it.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'footer_title',
			array(
				'label'       => esc_html__( 'Question', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Not sure which platform fits?', 'avix-widgets' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_footer' => 'yes' ),
			)
		);

		$this->add_control(
			'footer_text',
			array(
				'label'     => esc_html__( 'Text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXTAREA,
				'rows'      => 2,
				'default'   => esc_html__( 'We can discuss the options during discovery.', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_footer' => 'yes' ),
			)
		);

		$this->add_control(
			'footer_link_text',
			array(
				'label'     => esc_html__( 'Button text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Talk to our team', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_footer' => 'yes' ),
			)
		);

		$this->add_control(
			'footer_link',
			array(
				'label'     => esc_html__( 'Button link', 'avix-widgets' ),
				'type'      => Controls_Manager::URL,
				'default'   => array( 'url' => 'https://avixdigital.com/contact/' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_footer' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_buddy() {
		$this->start_controls_section( 'section_buddy', array( 'label' => esc_html__( 'Pixel Character', 'avix-widgets' ) ) );

		$this->add_control(
			'show_pal',
			array(
				'label'       => esc_html__( 'Show pixel character', 'avix-widgets' ),
				'description' => esc_html__( 'It rides on top of the hover preview, glances where the mouse goes and walks to keep its balance when the card moves fast. It also stands on the footer note, waves now and then, and types on its laptop on cards without an image.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'pal_greet',
			array(
				'label'       => esc_html__( 'Say hi on the first hover', 'avix-widgets' ),
				'description' => esc_html__( 'A hop, a wave and a pixel “hi” the first time the preview appears.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'condition'   => array( 'show_pal' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'pal_size',
			array(
				'label'       => esc_html__( 'Character size', 'avix-widgets' ),
				'description' => esc_html__( 'Steps of 10px keep every pixel of the character sharp.', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array(
					'px' => array(
						'min'  => 20,
						'max'  => 60,
						'step' => 10,
					),
				),
				'selectors'   => array( '{{WRAPPER}} .avix-si' => '--si-pal-w: {{SIZE}}{{UNIT}};' ),
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
				'label'   => esc_html__( 'Theme', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'light',
				'options' => array(
					'light' => esc_html__( 'Light', 'avix-widgets' ),
					'dark'  => esc_html__( 'Dark', 'avix-widgets' ),
				),
			)
		);

		$colors = array(
			'si_bg'          => array( esc_html__( 'Background', 'avix-widgets' ), '--si-bg' ),
			'si_ink'         => array( esc_html__( 'Headings', 'avix-widgets' ), '--si-ink' ),
			'si_muted'       => array( esc_html__( 'Body text', 'avix-widgets' ), '--si-muted' ),
			'si_accent'      => array( esc_html__( 'Accent', 'avix-widgets' ), '--si-accent' ),
			'si_accent_text' => array( esc_html__( 'Small orange text', 'avix-widgets' ), '--si-accent-text' ),
			'si_hl'          => array( esc_html__( 'Title [highlight]', 'avix-widgets' ), '--si-hl' ),
			'si_line'        => array( esc_html__( 'Lines', 'avix-widgets' ), '--si-line' ),
			'si_tint'        => array( esc_html__( 'Row hover tint', 'avix-widgets' ), '--si-tint' ),
			'si_card'        => array( esc_html__( 'Cards (phones & tablets)', 'avix-widgets' ), '--si-card' ),
			'si_foot'        => array( esc_html__( 'Footer note background', 'avix-widgets' ), '--si-foot' ),
		);
		foreach ( $colors as $key => $color ) {
			$this->add_control(
				$key,
				array(
					'label'     => $color[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-si' => $color[1] . ': {{VALUE}};' ),
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
				'selectors'          => array( '{{WRAPPER}} .avix-si' => 'padding-top: {{TOP}}{{UNIT}}; padding-bottom: {{BOTTOM}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'side_gutter',
			array(
				'label'       => esc_html__( 'Side margins', 'avix-widgets' ),
				'description' => esc_html__( 'Line up with the header: the content starts on the header logo\'s line (5% of the width) at every width, like the other sections set the same way.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => array(
					''       => esc_html__( 'Standard (24px)', 'avix-widgets' ),
					'header' => esc_html__( 'Line up with the header', 'avix-widgets' ),
				),
			)
		);

		$this->add_responsive_control(
			'max_width',
			array(
				'label'      => esc_html__( 'Content width', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 760, 'max' => 1600 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-si' => '--si-max: {{SIZE}}{{UNIT}};' ),
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

		$groups = array(
			'title_typography' => array( esc_html__( 'Title', 'avix-widgets' ), '{{WRAPPER}} .avix-si .avix-si__title' ),
			'text_typography'  => array( esc_html__( 'Header text', 'avix-widgets' ), '{{WRAPPER}} .avix-si .avix-si__text' ),
			'name_typography'  => array( esc_html__( 'Service names', 'avix-widgets' ), '{{WRAPPER}} .avix-si .avix-si__name' ),
			'cat_typography'   => array( esc_html__( 'Category labels', 'avix-widgets' ), '{{WRAPPER}} .avix-si .avix-si__cat' ),
			'desc_typography'  => array( esc_html__( 'Service text', 'avix-widgets' ), '{{WRAPPER}} .avix-si .avix-si__desc' ),
			'foot_typography'  => array( esc_html__( 'Footer note', 'avix-widgets' ), '{{WRAPPER}} .avix-si .avix-si__foot-text' ),
		);
		foreach ( $groups as $name => $group ) {
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
		$items = 'query' === ( $s['source'] ?? 'manual' ) ? $this->query_items( $s ) : $this->manual_items( $s );

		if ( ! $items ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="elementor-alert elementor-alert-info">' . esc_html__( 'Service Index: add at least one service with a name (or pick a post type that has published posts).', 'avix-widgets' ) . '</div>';
			}
			return;
		}

		$uid      = $this->get_id();
		$header   = 'yes' === ( $s['show_header'] ?? 'yes' );
		$title    = $header ? $this->accent_html( (string) ( $s['title'] ?? '' ), true ) : '';
		$title_id = 'avix-si-title-' . $uid;
		$preview  = 'yes' === ( $s['show_preview'] ?? 'yes' );
		$pal      = 'yes' === ( $s['show_pal'] ?? 'yes' );
		$footer   = 'yes' === ( $s['show_footer'] ?? 'yes' ) && (
			'' !== trim( (string) ( $s['footer_title'] ?? '' ) ) ||
			'' !== trim( (string) ( $s['footer_text'] ?? '' ) ) ||
			( '' !== trim( (string) ( $s['footer_link_text'] ?? '' ) ) && $this->has_url( $s['footer_link'] ?? null ) )
		);
		$card     = in_array( $s['card_ratio'] ?? '', array( '16 / 10', '16 / 9', '4 / 3', '1 / 1' ), true ) ? $s['card_ratio'] : '16 / 10';
		$ratio    = in_array( $s['preview_ratio'] ?? '', array( '18 / 13', '16 / 10', '4 / 3', '1 / 1', '4 / 5' ), true ) ? $s['preview_ratio'] : '18 / 13';

		$classes = array(
			'avix-si',
			'avix-si--' . ( 'dark' === ( $s['theme'] ?? 'light' ) ? 'dark' : 'light' ),
			'avix-si--head-' . ( 'stacked' === ( $s['head_layout'] ?? 'split' ) ? 'stacked' : 'split' ),
		);
		if ( 'header' === ( $s['side_gutter'] ?? '' ) ) {
			$classes[] = 'avix-si--edge-header';
		}
		if ( $preview ) {
			$classes[] = 'avix-si--preview';
		}
		if ( 'yes' !== ( $s['show_number'] ?? 'yes' ) ) {
			$classes[] = 'avix-si--no-number';
		}
		// Post titles run longer than service names: a calmer size keeps rows from turning into walls of display type.
		$long = 'query' === ( $s['source'] ?? 'manual' );
		foreach ( $items as $item ) {
			$long = $long || ( function_exists( 'mb_strlen' ) ? mb_strlen( $item['title'] ) : strlen( $item['title'] ) ) > 40;
		}
		if ( $long ) {
			$classes[] = 'avix-si--long-names';
		}

		$this->add_render_attribute(
			'root',
			array(
				'class'        => $classes,
				'style'        => '--si-card-ratio: ' . $card . '; --si-preview-ratio: ' . $ratio . ';',
				'data-avix-si' => wp_json_encode(
					array(
						'preview' => $preview,
						'tilt'    => $preview && 'yes' === ( $s['preview_tilt'] ?? 'yes' ),
						'pal'     => $pal,
						'greet'   => $pal && 'yes' === ( $s['pal_greet'] ?? 'yes' ),
					)
				),
			)
		);
		if ( '' !== $title ) {
			$this->add_render_attribute( 'root', 'aria-labelledby', $title_id );
		} else {
			$this->add_render_attribute( 'root', 'aria-label', esc_html__( 'Services', 'avix-widgets' ) );
		}
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="avix-si__inner">
				<?php
				if ( $header ) {
					$this->render_header( $s, $title, $title_id );
				}
				?>
				<div class="avix-si__list" data-si-list>
					<ol class="avix-si__rows">
						<?php foreach ( $items as $i => $item ) : ?>
							<?php $this->render_row( $s, $item, $i, $pal ); ?>
						<?php endforeach; ?>
					</ol>
					<?php
					if ( $preview ) {
						$this->render_preview( $s, $items, $pal );
					}
					?>
				</div>
				<?php
				if ( $footer ) {
					$this->render_footer( $s, $pal );
				}
				?>
			</div>
		</section>
		<?php
	}

	private function render_header( array $s, $title, $title_id ) {
		$eyebrow   = trim( (string) ( $s['eyebrow'] ?? '' ) );
		$text      = trim( (string) ( $s['text'] ?? '' ) );
		$link_text = trim( (string) ( $s['head_link_text'] ?? '' ) );
		$has_link  = '' !== $link_text && $this->has_url( $s['head_link'] ?? null );
		if ( '' === $eyebrow && '' === $title && '' === $text && ! $has_link ) {
			return;
		}
		$tag = Utils::validate_html_tag( $s['title_tag'] ?? 'h2' );
		?>
		<header class="avix-si__head">
			<div class="avix-si__head-main">
				<?php if ( '' !== $eyebrow ) : ?>
					<p class="avix-si__eyebrow" data-si-reveal><?php echo esc_html( $eyebrow ); ?></p>
				<?php endif; ?>
				<?php if ( '' !== $title ) : ?>
					<<?php echo esc_attr( $tag ); ?> class="avix-si__title" id="<?php echo esc_attr( $title_id ); ?>" data-si-reveal><?php echo $title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in accent_html(). ?></<?php echo esc_attr( $tag ); ?>>
				<?php endif; ?>
			</div>
			<?php if ( '' !== $text || $has_link ) : ?>
				<div class="avix-si__head-side" data-si-reveal>
					<?php if ( '' !== $text ) : ?>
						<p class="avix-si__text"><?php echo esc_html( $text ); ?></p>
					<?php endif; ?>
					<?php
					if ( $has_link ) :
						$this->add_link_attributes( 'head_link', $s['head_link'] );
						$this->add_render_attribute( 'head_link', 'class', 'avix-si__head-link' );
						?>
						<a <?php $this->print_render_attribute_string( 'head_link' ); ?>>
							<span class="avix-si__head-link-text"><?php echo esc_html( $link_text ); ?></span>
							<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 17 17 7M7 7h10v10"/></svg>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</header>
		<?php
	}

	private function render_row( array $s, array $item, $i, $pal ) {
		$item_tag   = Utils::validate_html_tag( $s['item_tag'] ?? 'h3' );
		$show_cat   = 'yes' === ( $s['show_category'] ?? 'yes' ) && '' !== $item['category'];
		$show_text  = 'yes' === ( $s['show_text'] ?? 'yes' ) && '' !== $item['text'];
		$icons      = 'yes' === ( $s['show_icons'] ?? 'yes' ) ? $item['icons'] : array();
		$tags       = 'yes' === ( $s['show_tags'] ?? 'yes' ) ? $item['tags'] : array();
		$number     = '' !== $item['number'] ? $item['number'] : sprintf( '%02d', $i + 1 );
		$classes    = 'avix-si__row' . ( '' !== $item['repeater_id'] ? ' elementor-repeater-item-' . sanitize_html_class( $item['repeater_id'] ) : '' );
		$classes   .= $item['link'] ? ' has-link' : '';
		$image      = $this->image_html( $item, '(min-width: 601px) 46vw, 92vw', 'avix-si__img' );
		?>
		<li class="<?php echo esc_attr( $classes ); ?>" data-si-row<?php echo '' !== $item['focus'] ? ' style="' . esc_attr( $item['focus'] ) . '"' : ''; ?>>
			<div class="avix-si__media" aria-hidden="<?php echo '' === $image ? 'true' : 'false'; ?>">
				<?php
				if ( '' !== $image ) {
					echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in image_html().
				} else {
					$this->placeholder_html( $item, true, $pal );
				}
				?>
			</div>
			<span class="avix-si__num" aria-hidden="true"><?php echo esc_html( $number ); ?></span>
			<?php if ( $show_cat ) : ?>
				<p class="avix-si__cat"><?php echo esc_html( $item['category'] ); ?></p>
			<?php endif; ?>
			<<?php echo esc_attr( $item_tag ); ?> class="avix-si__name"><span class="avix-si__name-in"><?php echo esc_html( $item['title'] ); ?></span></<?php echo esc_attr( $item_tag ); ?>>
			<?php if ( $icons ) : ?>
				<ul class="avix-si__icons" aria-label="<?php esc_attr_e( 'Platforms', 'avix-widgets' ); ?>">
					<?php foreach ( $icons as $key => $label ) : ?>
						<li class="avix-si__icon avix-si__icon--<?php echo esc_attr( $key ); ?>" title="<?php echo esc_attr( $label ); ?>">
							<?php echo Brand_Icons::svg( $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static, escaped in Brand_Icons::svg(). ?>
							<span class="avix-si__sr"><?php echo esc_html( $label ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<div class="avix-si__body">
				<?php if ( $show_text ) : ?>
					<p class="avix-si__desc"><?php echo $item['text']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in accent_html() when the item was built. ?></p>
				<?php endif; ?>
				<?php if ( $tags ) : ?>
					<ul class="avix-si__tags">
						<?php foreach ( $tags as $tag ) : ?>
							<li class="avix-si__tag"><?php echo esc_html( $tag ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<?php
				if ( $item['link'] ) :
					$key = 'row-link-' . $i;
					$this->add_link_attributes( $key, $item['link'] );
					$this->add_render_attribute( $key, 'class', 'avix-si__link' );
					$this->add_render_attribute( $key, 'data-si-link', '' );
					?>
					<a <?php $this->print_render_attribute_string( $key ); ?>>
						<span class="avix-si__link-text">
							<?php
							$label = '' !== $item['link_label'] ? $item['link_label'] : __( 'Explore', 'avix-widgets' );
							echo esc_html( $label );
							// A label that does not name the service ("Read more") gets the name for screen readers.
							if ( false === stripos( $label, $item['title'] ) ) :
								?>
								<span class="avix-si__sr"> – <?php echo esc_html( $item['title'] ); ?></span>
							<?php endif; ?>
						</span>
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
					</a>
				<?php endif; ?>
			</div>
			<?php if ( $item['link'] ) : ?>
				<span class="avix-si__go" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M7 17 17 7M7 7h10v10"/></svg></span>
			<?php endif; ?>
		</li>
		<?php
	}

	/**
	 * One card that docks inside the hovered row; it holds every row's image
	 * and shows the hovered one. Decorative: the same images sit in the rows for touch.
	 */
	private function render_preview( array $s, array $items, $pal ) {
		$chip = 'yes' === ( $s['show_chip'] ?? 'yes' );
		?>
		<div class="avix-si__preview" data-si-preview aria-hidden="true">
			<div class="avix-si__swing" data-si-swing>
				<div class="avix-si__pop">
					<?php if ( $pal ) : ?>
						<span class="avix-si__rider" data-si-rider>
							<?php
							echo Pixel_Pal::render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup escaped in Pixel_Pal::render().
								array(
									'class' => 'avix-si__pal is-still',
									'hi'    => true,
									'attrs' => array( 'data-si-pal' => '' ),
								)
							);
							?>
						</span>
					<?php endif; ?>
					<div class="avix-si__card">
						<?php foreach ( $items as $i => $item ) : ?>
							<?php
							$image = $this->image_html( $item, '480px', 'avix-si__shot-img', true );
							$label = '' !== $item['link_label'] ? $item['link_label'] : esc_html__( 'Explore', 'avix-widgets' );
							?>
							<div class="avix-si__shot" data-si-shot<?php echo '' !== $item['focus'] ? ' style="' . esc_attr( $item['focus'] ) . '"' : ''; ?>>
								<?php
								if ( '' !== $image ) {
									echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in image_html().
								} else {
									$this->placeholder_html( $item, false, false );
								}
								?>
								<?php if ( $chip && $item['link'] ) : ?>
									<span class="avix-si__chip"><?php echo esc_html( $label ); ?><svg viewBox="0 0 24 24" focusable="false"><path d="M7 17 17 7M7 7h10v10"/></svg></span>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	private function render_footer( array $s, $pal ) {
		$title     = trim( (string) ( $s['footer_title'] ?? '' ) );
		$text      = trim( (string) ( $s['footer_text'] ?? '' ) );
		$link_text = trim( (string) ( $s['footer_link_text'] ?? '' ) );
		$has_link  = '' !== $link_text && $this->has_url( $s['footer_link'] ?? null );
		?>
		<div class="avix-si__foot<?php echo $pal ? ' has-pal' : ''; ?><?php echo '' === $title && '' === $text ? ' avix-si__foot--bare' : ''; ?>" data-si-foot data-si-reveal>
			<?php if ( $pal ) : ?>
				<?php
				echo Pixel_Pal::render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup escaped in Pixel_Pal::render().
					array(
						'class' => 'avix-si__foot-pal',
						'hi'    => true,
						'attrs' => array( 'data-si-foot-pal' => '' ),
					)
				);
				?>
			<?php endif; ?>
			<?php if ( '' !== $title || '' !== $text ) : ?>
				<p class="avix-si__foot-text">
					<?php if ( '' !== $title ) : ?>
						<strong class="avix-si__foot-title"><?php echo esc_html( $title ); ?></strong>
					<?php endif; ?>
					<?php echo esc_html( $text ); ?>
				</p>
			<?php endif; ?>
			<?php
			if ( $has_link ) :
				$this->add_link_attributes( 'foot_link', $s['footer_link'] );
				$this->add_render_attribute( 'foot_link', 'class', 'avix-si__foot-link' );
				?>
				<a <?php $this->print_render_attribute_string( 'foot_link' ); ?>>
					<span><?php echo esc_html( $link_text ); ?></span>
					<span class="avix-si__foot-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M7 17 17 7M7 7h10v10"/></svg></span>
				</a>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Branded stand-in when a service has no image: a dark tile with an orange
	 * pixel glow and the category (or the service name) at the top. On cards,
	 * the pixel character sits on the tile's floor typing on its laptop, unless
	 * the character is switched off. The hover preview already carries the
	 * riding character, so it shows the label only, clear of the link chip.
	 */
	private function placeholder_html( array $item, $card, $pal ) {
		$label = '' !== $item['category'] ? $item['category'] : $item['title'];
		?>
		<span class="avix-si__ph<?php echo $card ? ' avix-si__ph--card' : ''; ?>">
			<span class="avix-si__ph-label"><?php echo esc_html( $label ); ?></span>
			<?php if ( $card ) : ?>
				<span class="avix-si__ph-floor">
					<?php
					if ( $pal ) {
						echo Pixel_Pal::render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup escaped in Pixel_Pal::render().
							array(
								'class' => 'avix-si__ph-pal is-still is-type',
								'prop'  => 'laptop',
							)
						);
					}
					?>
				</span>
			<?php endif; ?>
		</span>
		<?php
	}

	/**
	 * True when a URL control holds a link that survives esc_url() (a
	 * javascript: URL or a broken dynamic tag would otherwise print href="").
	 */
	private function has_url( $link ) {
		return is_array( $link ) && '' !== esc_url( (string) ( $link['url'] ?? '' ) );
	}

	/**
	 * Inline object-position for an item's image, '' when it is centred.
	 */
	private function focus_style( array $row ) {
		$x = isset( $row['image_focus_x']['size'] ) && '' !== $row['image_focus_x']['size'] ? max( 0, min( 100, (float) $row['image_focus_x']['size'] ) ) : 50;
		$y = isset( $row['image_focus_y']['size'] ) && '' !== $row['image_focus_y']['size'] ? max( 0, min( 100, (float) $row['image_focus_y']['size'] ) ) : 50;
		if ( 50.0 === (float) $x && 50.0 === (float) $y ) {
			return '';
		}
		return '--si-focus: ' . $x . '% ' . $y . '%;';
	}

	/* ------------------------------------------------------------------ */
	/* Data                                                                */
	/* ------------------------------------------------------------------ */

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function manual_items( array $s ) {
		$rows  = array_values(
			array_filter(
				(array) ( $s['items'] ?? array() ),
				static function ( $row ) {
					return '' !== trim( (string) ( $row['title'] ?? '' ) );
				}
			)
		);
		$brand = Brand_Icons::options();
		$items = array();
		foreach ( $rows as $row ) {
			$image = (array) ( $row['image'] ?? array() );
			$icons = array();
			foreach ( (array) ( $row['icons'] ?? array() ) as $key ) {
				$key = sanitize_key( (string) $key );
				if ( isset( $brand[ $key ] ) ) {
					$icons[ $key ] = $brand[ $key ];
				}
			}
			$items[] = array(
				'repeater_id' => (string) ( $row['_id'] ?? '' ),
				'title'       => trim( (string) $row['title'] ),
				'category'    => trim( (string) ( $row['category'] ?? '' ) ),
				'number'      => trim( (string) ( $row['number'] ?? '' ) ),
				'text'        => $this->accent_html( (string) ( $row['text'] ?? '' ), false ),
				'tags'        => $this->split_tags( (string) ( $row['tags'] ?? '' ) ),
				'icons'       => $icons,
				'image_id'    => $this->media_id( $image ),
				'image_url'   => (string) ( $image['url'] ?? '' ),
				'image_alt'   => trim( (string) ( $row['image_alt'] ?? '' ) ),
				'focus'       => $this->focus_style( $row ),
				'link'        => $this->has_url( $row['link'] ?? null ) ? $row['link'] : null,
				'link_label'  => trim( (string) ( $row['link_label'] ?? '' ) ),
			);
		}
		return $items;
	}

	private function query_items( array $s ) {
		$post_type = sanitize_key( (string) ( $s['query_post_type'] ?? '' ) );
		// Only the types the panel offers: internal ones (templates, kits) never list.
		if ( '' === $post_type || ! isset( $this->post_type_options()[ $post_type ] ) ) {
			return array();
		}
		$taxonomies = $this->taxonomy_options();

		$orderby = in_array( $s['query_orderby'] ?? '', array( 'menu_order', 'date', 'title' ), true ) ? $s['query_orderby'] : 'menu_order';
		$order   = 'DESC' === ( $s['query_order'] ?? 'ASC' ) ? 'DESC' : 'ASC';
		$ids     = wp_parse_id_list( (string) ( $s['query_ids'] ?? '' ) );
		$args    = array(
			'post_type'           => $post_type,
			'post_status'         => 'publish',
			'posts_per_page'      => max( 1, min( 12, (int) ( $s['query_count'] ?? 4 ) ) ),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'menu_order' === $orderby ? array( 'menu_order' => $order, 'date' => 'DESC' ) : $orderby,
			'order'               => $order,
		);
		if ( $ids ) {
			$args['post__in'] = $ids;
			$args['orderby']  = 'post__in';
		} elseif ( get_queried_object_id() ) {
			// A "Pages" list should not include the page it sits on.
			$args['post__not_in'] = array( get_queried_object_id() );
		}

		$cat_tax = sanitize_key( (string) ( $s['query_cat_tax'] ?? '' ) );
		$cat_tax = isset( $taxonomies[ $cat_tax ] ) ? $cat_tax : '';
		$tag_tax = sanitize_key( (string) ( $s['query_tag_tax'] ?? '' ) );
		$tag_tax = isset( $taxonomies[ $tag_tax ] ) ? $tag_tax : '';
		$terms   = array_values( array_filter( array_map( 'sanitize_title', explode( ',', (string) ( $s['query_terms'] ?? '' ) ) ) ) );
		if ( '' !== $cat_tax && $terms ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => $cat_tax,
					'field'    => 'slug',
					'terms'    => $terms,
				),
			);
		}
		$query = new \WP_Query( $args );

		$words    = max( 8, min( 60, (int) ( $s['query_words'] ?? 28 ) ) );
		$template = trim( (string) ( $s['query_link_label'] ?? '' ) );
		$items    = array();
		foreach ( $query->posts as $post ) {
			$title    = wp_strip_all_tags( get_the_title( $post ) );
			$category = '';
			if ( '' !== $cat_tax ) {
				$terms    = get_the_terms( $post, $cat_tax );
				$category = is_array( $terms ) && $terms ? $terms[0]->name : '';
			}
			$tags = array();
			if ( '' !== $tag_tax ) {
				$terms = get_the_terms( $post, $tag_tax );
				$tags  = is_array( $terms ) ? array_slice( wp_list_pluck( $terms, 'name' ), 0, 4 ) : array();
			}
			$items[] = array(
				'repeater_id' => '',
				'title'       => $title,
				'category'    => $category,
				'number'      => '',
				'text'        => esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post ) ), $words ) ),
				'tags'        => $tags,
				'icons'       => array(),
				'image_id'    => (int) get_post_thumbnail_id( $post ),
				'image_url'   => '',
				'image_alt'   => '',
				'focus'       => '',
				'link'        => array( 'url' => get_permalink( $post ) ),
				'link_label'  => '' !== $template ? str_replace( '%s', $title, $template ) : '',
			);
		}
		return $items;
	}

	/**
	 * "Liquid themes, Shopify Plus, Integrations" → three chips.
	 */
	private function split_tags( $text ) {
		return array_values( array_filter( array_map( 'trim', explode( ',', $text ) ), 'strlen' ) );
	}

	/**
	 * Escaped text; [words] become the accent span, new lines <br> when $breaks.
	 */
	private function accent_html( $text, $breaks ) {
		$lines = array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $text ) ), 'strlen' ) );
		$lines = array_map(
			static function ( $line ) {
				$html = preg_replace( '/\[([^\[\]]+)\]/u', '<span class="avix-si__accent">$1</span>', esc_html( $line ) );
				return null === $html ? esc_html( $line ) : $html;
			},
			$lines
		);
		return implode( $breaks ? ' <br>' : ' ', $lines );
	}

	/**
	 * Responsive image for an item, or '' when it has none. Preview images are
	 * decorative (empty alt): the row already names the service.
	 */
	private function image_html( array $item, $sizes, $class, $decorative = false ) {
		$alt = '';
		if ( ! $decorative ) {
			$alt = $item['image_alt'];
			if ( '' === $alt && $item['image_id'] ) {
				$alt = trim( (string) get_post_meta( $item['image_id'], '_wp_attachment_image_alt', true ) );
			}
			if ( '' === $alt ) {
				$alt = $item['title'];
			}
		}
		if ( $item['image_id'] ) {
			$is_gif = 'image/gif' === get_post_mime_type( $item['image_id'] );
			$html   = $is_gif ? '' : wp_get_attachment_image(
				$item['image_id'],
				'large',
				false,
				array(
					'class'    => $class,
					'alt'      => $alt,
					'loading'  => 'lazy',
					'decoding' => 'async',
					'sizes'    => $sizes,
				)
			);
			if ( $html ) {
				return $this->lazy_img( $html );
			}
			$url = (string) wp_get_attachment_url( $item['image_id'] );
		} else {
			$url = $item['image_url'];
		}
		$src = esc_url( $url );
		if ( '' === $src ) {
			return '';
		}
		return sprintf(
			'<img class="%s" src="%s" alt="%s" loading="lazy" decoding="async">',
			esc_attr( $class ),
			$src,
			esc_attr( $alt )
		);
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
		$options = array();
		foreach ( get_taxonomies( array( 'public' => true ), 'objects' ) as $taxonomy ) {
			if ( in_array( $taxonomy->name, array( 'post_format', 'elementor_library_type', 'elementor_library_category' ), true ) ) {
				continue;
			}
			$options[ $taxonomy->name ] = $taxonomy->labels->singular_name;
		}
		return $options;
	}

	/** Prefer a public services post type (themes often register one). */
	private function default_post_type() {
		$options = $this->post_type_options();
		foreach ( array( 'cpt_services', 'services', 'service' ) as $candidate ) {
			if ( isset( $options[ $candidate ] ) ) {
				return $candidate;
			}
		}
		return isset( $options['page'] ) ? 'page' : (string) key( $options );
	}

	private function default_items() {
		return array(
			array(
				'category'   => 'Development',
				'title'      => 'Custom websites & web applications',
				'text'       => 'React, Next.js and full-stack development for customer portals, dashboards and business tools. We define users, workflows, data and integrations before deciding how to build.',
				'tags'       => 'Customer portals, Dashboards, APIs',
				'icons'      => array( 'react', 'nextjs', 'nodejs' ),
				'image'      => array( 'url' => self::UPLOADS . '2026/09/ChatGPT-Image-29-Sept-2026-02_09_56.png' ),
				'image_alt'  => 'Client portal dashboard on a laptop and phone',
				'link'       => array( 'url' => 'https://avixdigital.com/service/web-development/' ),
				'link_label' => 'Explore web development',
			),
			array(
				'category'   => 'Ecommerce',
				'title'      => 'Shopify & Shopify Plus',
				'text'       => 'Custom Liquid themes, storefront improvements and ecommerce integrations. A development approach shaped around your catalogue, customer journey and store operations.',
				'tags'       => 'Liquid themes, Shopify Plus, Apps',
				'icons'      => array( 'shopify' ),
				'image'      => array( 'url' => self::UPLOADS . '2026/06/Untitled-design235.png' ),
				'image_alt'  => 'Kampeerwinkel Shopify store on a laptop',
				'link'       => array( 'url' => 'https://avixdigital.com/service/shopify-plus/' ),
				'link_label' => 'Explore Shopify development',
			),
			array(
				'category'   => 'Content & commerce',
				'title'      => 'WordPress development',
				'text'       => 'Editable business websites, custom Elementor components and WooCommerce functionality. Give your team a site they can manage, with the flexibility your project needs.',
				'tags'       => 'Elementor, WooCommerce, Speed',
				'icons'      => array( 'wordpress', 'elementor' ),
				'image'      => array( 'url' => self::UPLOADS . '2026/09/ChatGPT-Image-29-Sept-2026-02_12_10.png' ),
				'image_alt'  => 'WordPress, Shopify and Webflow around a website dashboard',
				'link'       => array( 'url' => 'https://avixdigital.com/service/wordpress-development/' ),
				'link_label' => 'Explore WordPress development',
			),
			array(
				'category'      => 'Design',
				'title'         => 'UI/UX & brand design',
				'text'          => 'User journeys, Figma prototypes and visual identity for websites and digital products. Clear navigation and thoughtful interfaces that guide people to their next step.',
				'tags'          => 'User journeys, Prototypes, Identity',
				'icons'         => array( 'figma' ),
				'image'         => array( 'url' => self::UPLOADS . '2026/06/Untitled-design247.webp' ),
				// A faint wordmark runs across the top: anchor the crop to the top edge so the
				// wordmark stays whole above the tablet (only the lower hands are cropped).
				'image_focus_y' => array(
					'unit' => '%',
					'size' => 0,
				),
				'image_alt'     => 'Dashboard interface design on a tablet',
				'link'          => array( 'url' => 'https://avixdigital.com/service/uiux-and-brand-design/' ),
				'link_label'    => 'Explore UI/UX and branding',
			),
		);
	}

	/**
	 * Keeps a below-the-fold image lazy. Some sites filter attachment images
	 * to loading="eager" (avixdigital.com does), which made these images
	 * compete with the hero image on first load.
	 */
	private function lazy_img( $html ) {
		$html = (string) $html;
		if ( '' === $html ) {
			return $html;
		}
		if ( class_exists( '\WP_HTML_Tag_Processor' ) ) {
			$tags = new \WP_HTML_Tag_Processor( $html );
			if ( $tags->next_tag( 'img' ) ) {
				$tags->set_attribute( 'loading', 'lazy' );
				$tags->set_attribute( 'decoding', 'async' );
				$tags->remove_attribute( 'fetchpriority' );
				return $tags->get_updated_html();
			}
			return $html;
		}
		return (string) preg_replace( '/\sloading=(["\'])[^"\']*\1/i', ' loading="lazy"', $html );
	}
}
