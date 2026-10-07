<?php
/**
 * Case Study Gallery: "05 · Across devices". Real desktop and phone
 * screenshots of the live site (and dark studio renders that already show the
 * devices) laid out as a bento mosaic of equal-height tiles: a full-width
 * showcase render, a wide desktop shot beside a phone, a staggered row of
 * phones and justified pairs of desktop shots. In narrow spaces (and with the
 * Carousel layout) it becomes a swipeable carousel with buttons and a counter.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Widgets;

use AvixWidgets\Case_Studies\Kit;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;

defined( 'ABSPATH' ) || exit;

// The shared source controls load with the widgets. Without them this file
// declares nothing, so the plugin's loader skips the widget instead of failing.
if ( ! trait_exists( '\AvixWidgets\Case_Studies\Source' ) ) {
	return;
}

class Case_Study_Gallery extends Widget_Base {

	use Media;
	use \AvixWidgets\Case_Studies\Source;

	/** Item types: framed screenshots, or a render that already shows devices. */
	const DEVICES = array( 'browser', 'phone', 'render' );

	const LAYOUTS = array( 'mosaic', 'phones', 'carousel' );

	/** Phones per row in the mosaic (rows are balanced: 5 → 3 + 2). */
	const PHONES_PER_ROW = 4;

	public function get_name(): string {
		return 'avix-case-study-gallery';
	}

	public function get_title(): string {
		return esc_html__( 'Case Study Gallery', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-gallery-masonry';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'avix', 'case study', 'portfolio', 'gallery', 'screenshots', 'devices', 'mobile', 'carousel', 'mosaic' );
	}

	public function get_style_depends(): array {
		return array( 'avix-case-study-gallery' );
	}

	public function get_script_depends(): array {
		return array( 'avix-case-study-gallery' );
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/** Reads the case study's accent and site label: never cache a stale copy. */
	protected function is_dynamic_content(): bool {
		return true;
	}

	/* ------------------------------------------------------------------ */
	/* Controls                                                            */
	/* ------------------------------------------------------------------ */

	protected function register_controls(): void {
		$this->controls_source();
		$this->controls_header();
		$this->controls_items();
		$this->controls_layout();
		$this->controls_style();
		$this->controls_type();
	}

	private function controls_header() {
		$this->start_controls_section( 'section_header', array( 'label' => esc_html__( 'Header', 'avix-widgets' ) ) );

		$this->add_control(
			'show_number',
			array(
				'label'   => esc_html__( 'Number', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'number',
			array(
				'label'     => esc_html__( 'Number text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => '05',
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_number' => 'yes' ),
			)
		);

		$this->add_control(
			'show_label',
			array(
				'label'     => esc_html__( 'Label', 'avix-widgets' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'label',
			array(
				'label'       => esc_html__( 'Label text', 'avix-widgets' ),
				'description' => esc_html__( 'Shown after the number in the small eyebrow: "05 · Across devices".', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Across devices', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_label' => 'yes' ),
			)
		);

		$this->add_control(
			'show_title',
			array(
				'label'     => esc_html__( 'Title', 'avix-widgets' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Title text', 'avix-widgets' ),
				'description' => esc_html__( 'Wrap words in [brackets] to highlight them in orange. Press Enter for a new line.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => esc_html__( 'Built for every screen', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_title' => 'yes' ),
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'     => esc_html__( 'Title tag', 'avix-widgets' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'h2',
				'options'   => array(
					'h2'  => 'H2',
					'h3'  => 'H3',
					'div' => 'div',
					'p'   => 'p',
				),
				'condition' => array( 'show_title' => 'yes' ),
			)
		);

		$this->add_control(
			'show_text',
			array(
				'label'     => esc_html__( 'Text', 'avix-widgets' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'text',
			array(
				'label'       => esc_html__( 'Text', 'avix-widgets' ),
				'description' => esc_html__( 'Optional. One or two sentences beside the title.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 3,
				'default'     => '',
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_text' => 'yes' ),
			)
		);

		$this->add_control(
			'anchor',
			array(
				'label'       => esc_html__( 'Section anchor', 'avix-widgets' ),
				'description' => esc_html__( 'Link to this section with #devices. Letters, numbers and dashes only.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'devices',
				'separator'   => 'before',
			)
		);

		$this->end_controls_section();
	}

	private function controls_items() {
		$this->start_controls_section( 'section_items', array( 'label' => esc_html__( 'Screenshots', 'avix-widgets' ) ) );

		$repeater = new Repeater();

		$repeater->add_control(
			'image',
			array(
				'label'   => esc_html__( 'Image', 'avix-widgets' ),
				'type'    => Controls_Manager::MEDIA,
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'device',
			array(
				'label'       => esc_html__( 'Show as', 'avix-widgets' ),
				'description' => esc_html__( 'Render: a studio image that already shows the devices (no frame is added). A render placed first fills the whole width as a showcase.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'browser',
				'options'     => array(
					'browser' => esc_html__( 'Desktop (browser frame)', 'avix-widgets' ),
					'phone'   => esc_html__( 'Phone (phone frame)', 'avix-widgets' ),
					'render'  => esc_html__( 'Render (no frame)', 'avix-widgets' ),
				),
			)
		);

		$repeater->add_control(
			'caption',
			array(
				'label'       => esc_html__( 'Caption', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'url_label',
			array(
				'label'       => esc_html__( 'Address bar text', 'avix-widgets' ),
				'description' => esc_html__( 'Leave empty to use the case study\'s website label.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__( 'From case study', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'device' => 'browser' ),
			)
		);

		$repeater->add_control(
			'link',
			array(
				'label'       => esc_html__( 'Link', 'avix-widgets' ),
				'description' => esc_html__( 'Optional. Visitors click the screenshot or its caption to open the page it shows.', 'avix-widgets' ),
				'type'        => Controls_Manager::URL,
				'default'     => array( 'url' => '' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'alt',
			array(
				'label'       => esc_html__( 'Alt text', 'avix-widgets' ),
				'description' => esc_html__( 'Leave empty to use the image\'s alt text from the Media Library.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'items',
			array(
				'label'       => esc_html__( 'Screenshots', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(),
				'title_field' => '{{{ caption || device }}}',
			)
		);

		$this->add_control(
			'items_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Mosaic order: a render placed first becomes a full-width showcase. Then the first desktop shot sits beside the first phone, the other phones follow in a staggered row, and the remaining desktop shots pair up. Narrow screens get a swipeable carousel.', 'avix-widgets' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->end_controls_section();
	}

	private function controls_layout() {
		$this->start_controls_section( 'section_layout', array( 'label' => esc_html__( 'Layout', 'avix-widgets' ) ) );

		$this->add_control(
			'layout',
			array(
				'label'       => esc_html__( 'Layout', 'avix-widgets' ),
				'description' => esc_html__( 'Below 768px wide every layout becomes a swipeable carousel.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'mosaic',
				'options'     => array(
					'mosaic'   => esc_html__( 'Mosaic', 'avix-widgets' ),
					'phones'   => esc_html__( 'Phones only (staggered rows)', 'avix-widgets' ),
					'carousel' => esc_html__( 'Carousel', 'avix-widgets' ),
				),
			)
		);

		$this->add_control(
			'stagger',
			array(
				'label'       => esc_html__( 'Phone stagger', 'avix-widgets' ),
				'description' => esc_html__( 'How far every second phone sits lower in a phone row.', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array(
					'px' => array(
						'min' => 0,
						'max' => 120,
					),
				),
				'default'     => array(
					'unit' => 'px',
					'size' => 48,
				),
				'selectors'   => array( '{{WRAPPER}} .avix-csg' => '--csg-stagger: {{SIZE}}{{UNIT}};' ),
				'condition'   => array( 'layout!' => 'carousel' ),
			)
		);

		$this->add_control(
			'mat',
			array(
				'label'       => esc_html__( 'Tiles', 'avix-widgets' ),
				'description' => esc_html__( 'The panel each screenshot sits on. Tint mixes the case study\'s brand colour into the paper tone.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'paper',
				'options'     => array(
					'paper' => esc_html__( 'Paper', 'avix-widgets' ),
					'tint'  => esc_html__( 'Tint (brand colour)', 'avix-widgets' ),
					'none'  => esc_html__( 'None (frames on the section)', 'avix-widgets' ),
				),
			)
		);

		$this->add_control(
			'pixels',
			array(
				'label'       => esc_html__( 'Pixel reveal', 'avix-widgets' ),
				'description' => esc_html__( 'Screenshots resolve from small squares the first time they scroll into view.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'parallax',
			array(
				'label'       => esc_html__( 'Phone depth on scroll', 'avix-widgets' ),
				'description' => esc_html__( 'On desktop the phones drift a few pixels as the section scrolls. Off for visitors who prefer less motion.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'condition'   => array( 'layout!' => 'carousel' ),
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
				'description' => esc_html__( 'Paper follows the dark results section on case-study pages.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'paper',
				'options'     => array(
					'paper' => esc_html__( 'Paper', 'avix-widgets' ),
					'white' => esc_html__( 'White', 'avix-widgets' ),
					'dark'  => esc_html__( 'Dark', 'avix-widgets' ),
				),
			)
		);

		$colours = array(
			'bg'     => array( esc_html__( 'Background', 'avix-widgets' ), '--csg-bg' ),
			'ink'    => array( esc_html__( 'Title', 'avix-widgets' ), '--csg-ink' ),
			'muted'  => array( esc_html__( 'Text & captions', 'avix-widgets' ), '--csg-muted' ),
			'line'   => array( esc_html__( 'Lines & buttons', 'avix-widgets' ), '--csg-line' ),
			'accent' => array( esc_html__( 'Accent', 'avix-widgets' ), '--csg-accent' ),
			'tile'   => array( esc_html__( 'Tiles', 'avix-widgets' ), '--csg-tile' ),
		);
		foreach ( $colours as $key => $colour ) {
			$this->add_control(
				'color_' . $key,
				array(
					'label'     => $colour[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-csg' => $colour[1] . ': {{VALUE}};' ),
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
				'selectors'          => array( '{{WRAPPER}} .avix-csg' => '--csg-pad-top: {{TOP}}{{UNIT}}; --csg-pad-bottom: {{BOTTOM}}{{UNIT}};' ),
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
				'selectors'  => array( '{{WRAPPER}} .avix-csg' => '--csg-max: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'gap',
			array(
				'label'      => esc_html__( 'Tile gap', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 8,
						'max' => 64,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .avix-csg' => '--csg-gap: {{SIZE}}{{UNIT}};' ),
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
			'type_title'   => array( esc_html__( 'Title', 'avix-widgets' ), '.avix-csg__title' ),
			'type_text'    => array( esc_html__( 'Text', 'avix-widgets' ), '.avix-csg__text' ),
			'type_caption' => array( esc_html__( 'Captions', 'avix-widgets' ), '.avix-csg__cap' ),
		);
		foreach ( $groups as $key => $group ) {
			$this->add_group_control(
				Group_Control_Typography::get_type(),
				array(
					'name'     => $key,
					'label'    => $group[0],
					'selector' => '{{WRAPPER}} .avix-csg ' . $group[1],
				)
			);
		}

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ */
	/* Render                                                              */
	/* ------------------------------------------------------------------ */

	protected function render(): void {
		$s      = $this->get_settings_for_display();
		$cs     = $this->cs();
		$layout = in_array( (string) ( $s['layout'] ?? '' ), self::LAYOUTS, true ) ? (string) $s['layout'] : 'mosaic';
		$items  = $this->items( $s, $cs );

		if ( 'phones' === $layout ) {
			$phones = array_values(
				array_filter(
					$items,
					static function ( $item ) {
						return 'phone' === $item['device'];
					}
				)
			);
			if ( count( $phones ) < count( $items ) ) {
				$this->cs_alert( esc_html__( 'Case Study Gallery: the "Phones only" layout shows phone screenshots only. Switch to Mosaic to show desktop shots and renders too.', 'avix-widgets' ) );
			}
			$items = $phones;
		}

		if ( ! $items ) {
			$this->cs_alert( esc_html__( 'Case Study Gallery: add desktop and phone screenshots.', 'avix-widgets' ) );
			return;
		}

		$theme  = in_array( (string) ( $s['theme'] ?? '' ), array( 'paper', 'white', 'dark' ), true ) ? (string) $s['theme'] : 'paper';
		$mat    = in_array( (string) ( $s['mat'] ?? '' ), array( 'paper', 'tint', 'none' ), true ) ? (string) $s['mat'] : 'paper';
		$uid    = $this->get_id();
		$client = trim( (string) ( $cs['client'] ?? '' ) );
		$accent = (string) ( $cs['accent'] ?? '' );
		$rows   = $this->plan( $items, $layout );
		$count  = count( $items );
		$pixels = 'yes' === ( $s['pixels'] ?? '' );

		$classes = array( 'avix-csg', 'avix-csg--' . $theme, 'avix-csg--' . $layout, 'avix-csg--mat-' . $mat );
		if ( 'dark' === $theme ) {
			$classes[] = 'avix-csk-on-dark';
		}
		if ( 1 === $count ) {
			$classes[] = 'avix-csg--single';
		}

		$label = '' !== $client
			/* translators: %s: client name. */
			? sprintf( __( '%s screenshots', 'avix-widgets' ), $client )
			: __( 'Screenshots', 'avix-widgets' );

		$config = array(
			'parallax' => 'carousel' !== $layout && 'yes' === ( $s['parallax'] ?? '' ),
			'label'    => $label,
			/* translators: 1: position, 2: number of screenshots. */
			'slide'    => __( '%1$s of %2$s', 'avix-widgets' ),
			/* translators: 1: position, 2: number of screenshots, 3: caption. */
			'announce' => __( 'Screenshot %1$s of %2$s', 'avix-widgets' ),
		);

		$root = array(
			'class'         => $classes,
			'data-avix-csg' => wp_json_encode( $config ),
		);
		$anchor = $this->unique_anchor( sanitize_html_class( (string) ( $s['anchor'] ?? '' ) ) );
		if ( '' !== $anchor ) {
			$root['id'] = $anchor;
		}
		if ( preg_match( '/^#[0-9a-f]{6}$/i', $accent ) ) {
			$root['style'] = '--csk-accent: ' . $accent . ';';
		}
		$this->add_render_attribute( 'root', $root );

		$title_id = 'avix-csg-title-' . $uid;
		$has_head = $this->has_head( $s );
		if ( $has_head && 'yes' === ( $s['show_title'] ?? '' ) && '' !== trim( (string) ( $s['title'] ?? '' ) ) ) {
			$this->add_render_attribute( 'root', 'aria-labelledby', $title_id );
		} else {
			$this->add_render_attribute( 'root', 'aria-label', $label );
		}

		$index = 0;
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="avix-csg__inner">
				<?php
				if ( $has_head ) {
					$this->render_head( $s, $title_id );
				}
				?>
				<div class="avix-csg__stage" data-csg-stage>
					<div class="avix-csg__track" data-csg-track>
						<?php
						foreach ( $rows as $row ) {
							echo '<div class="avix-csg__row avix-csg__row--' . esc_attr( $row['type'] ) . ' avix-csg__row--n' . (int) count( $row['items'] ) . '" data-csg-row>';
							foreach ( $row['items'] as $slot => $item ) {
								echo $this->figure( $item, $row['type'], $index, $count, $slot, $client, $pixels ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in figure().
								++$index;
							}
							echo '</div>';
						}
						?>
					</div>
					<?php if ( $count > 1 ) : ?>
						<div class="avix-csg__nav" data-csg-nav hidden>
							<button type="button" class="avix-csg__btn avix-csg__btn--prev" data-csg-prev aria-label="<?php echo esc_attr__( 'Previous screenshot', 'avix-widgets' ); ?>">
								<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
							</button>
							<p class="avix-csg__count" aria-hidden="true"><span class="avix-csg__count-now" data-csg-now>01</span><span class="avix-csg__count-sep"></span><span class="avix-csg__count-all"><?php echo esc_html( str_pad( (string) $count, 2, '0', STR_PAD_LEFT ) ); ?></span></p>
							<button type="button" class="avix-csg__btn avix-csg__btn--next" data-csg-next aria-label="<?php echo esc_attr__( 'Next screenshot', 'avix-widgets' ); ?>">
								<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
							</button>
						</div>
						<p class="avix-csg__sr" aria-live="polite" aria-atomic="true" data-csg-live></p>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * The section anchor, made unique when a page holds several galleries
	 * (#devices, then #devices-2…), so every id on the page stays unique.
	 *
	 * @param string $anchor Sanitised anchor.
	 */
	private function unique_anchor( $anchor ) {
		static $used = array();
		if ( '' === $anchor ) {
			return '';
		}
		$id = $anchor;
		$n  = 1;
		while ( isset( $used[ $id ] ) ) {
			$id = $anchor . '-' . ( ++$n );
		}
		$used[ $id ] = true;
		return $id;
	}

	/**
	 * Whether any header part is switched on and filled in.
	 *
	 * @param array $s Settings.
	 */
	private function has_head( array $s ) {
		foreach ( array( 'number', 'label', 'title', 'text' ) as $part ) {
			if ( 'yes' === ( $s[ 'show_' . $part ] ?? '' ) && '' !== trim( (string) ( $s[ $part ] ?? '' ) ) ) {
				return true;
			}
		}
		return false;
	}

	private function render_head( array $s, $title_id ) {
		$number = 'yes' === ( $s['show_number'] ?? '' ) ? trim( (string) ( $s['number'] ?? '' ) ) : '';
		$label  = 'yes' === ( $s['show_label'] ?? '' ) ? trim( (string) ( $s['label'] ?? '' ) ) : '';
		$title  = 'yes' === ( $s['show_title'] ?? '' ) ? trim( (string) ( $s['title'] ?? '' ) ) : '';
		$text   = 'yes' === ( $s['show_text'] ?? '' ) ? trim( (string) ( $s['text'] ?? '' ) ) : '';
		$tag    = Utils::validate_html_tag( (string) ( $s['title_tag'] ?? 'h2' ) );
		$brow   = implode( ' · ', array_filter( array( $number, $label ), 'strlen' ) );
		?>
		<header class="avix-csg__head<?php echo '' !== $text ? ' avix-csg__head--split' : ''; ?>" data-csg-rv>
			<div class="avix-csg__intro">
				<?php
				if ( '' !== $brow ) {
					echo $this->eyebrow( $brow ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in eyebrow().
				}
				if ( '' !== $title ) {
					printf(
						'<%1$s class="avix-csg__title" id="%2$s">%3$s</%1$s>',
						esc_html( $tag ),
						esc_attr( $title_id ),
						$this->accent_html( $title, 'avix-csg__accent' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in accent_html().
					);
				}
				?>
			</div>
			<?php if ( '' !== $text ) : ?>
				<p class="avix-csg__text"><?php echo esc_html( $text ); ?></p>
			<?php endif; ?>
		</header>
		<?php
	}

	/* ------------------------------------------------------------------ */
	/* Items and the mosaic plan                                           */
	/* ------------------------------------------------------------------ */

	/**
	 * Repeater rows with an image, normalised.
	 *
	 * @param array $s  Settings.
	 * @param array $cs Case study data.
	 */
	private function items( array $s, array $cs ) {
		$items = array();
		foreach ( (array) ( $s['items'] ?? array() ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$media = (array) ( $row['image'] ?? array() );
			$id    = $this->media_id( $media );
			$url   = trim( (string) ( $media['url'] ?? '' ) );
			if ( ! $id && '' === $url ) {
				continue;
			}
			$device = in_array( (string) ( $row['device'] ?? '' ), self::DEVICES, true ) ? (string) $row['device'] : 'browser';
			$ratio  = $id && class_exists( '\AvixWidgets\Case_Studies\Kit' ) ? Kit::ratio( $id ) : $this->ratio( $id );
			$parts  = '' !== $ratio ? array_map( 'floatval', explode( '/', $ratio ) ) : array();
			$ar     = 2 === count( $parts ) && $parts[0] > 0 && $parts[1] > 0 ? $parts[0] / $parts[1] : ( 'phone' === $device ? 0.4621 : 1.6 );

			$items[] = array(
				'id'        => $id,
				'url'       => $url,
				'device'    => $device,
				'caption'   => trim( (string) ( $row['caption'] ?? '' ) ),
				'url_label' => $this->pick( $row, 'url_label', (string) ( $cs['live_label'] ?? '' ) ),
				'link'      => is_array( $row['link'] ?? null ) ? $row['link'] : array(),
				'alt'       => trim( (string) ( $row['alt'] ?? '' ) ),
				'key'       => (string) ( $row['_id'] ?? '' ),
				'ratio'     => $ratio,
				'ar'        => $ar,
			);
		}
		return $items;
	}

	/**
	 * Rows of the layout, in reading order. Mosaic (D = desktop shots and
	 * renders, P = phones, both in repeater order):
	 *   a render placed first → a full-width showcase row;
	 *   row "a": D[0] (8 cols) beside P[0] (4 cols);
	 *   "phones": the other phones, staggered, balanced rows of up to 4;
	 *   "pair": the other desktop shots two by two, "solo": a last odd one.
	 * Carousel: one flat row. Rows without items are skipped.
	 *
	 * @param array  $items  Normalised items.
	 * @param string $layout mosaic | phones | carousel.
	 */
	private function plan( array $items, $layout ) {
		if ( 'carousel' === $layout ) {
			return array(
				array(
					'type'  => 'flat',
					'items' => $items,
				),
			);
		}

		$rows = array();
		if ( 'mosaic' === $layout && 'render' === $items[0]['device'] ) {
			$rows[] = array(
				'type'  => 'show',
				'items' => array( array_shift( $items ) ),
			);
		}

		$wide   = array();
		$phones = array();
		foreach ( $items as $item ) {
			if ( 'phone' === $item['device'] ) {
				$phones[] = $item;
			} else {
				$wide[] = $item;
			}
		}

		if ( $wide && $phones ) {
			$rows[] = array(
				'type'  => 'a',
				'items' => array( array_shift( $wide ), array_shift( $phones ) ),
			);
		}

		foreach ( $this->balanced( $phones, self::PHONES_PER_ROW ) as $chunk ) {
			$rows[] = array(
				'type'  => 'phones',
				'items' => $chunk,
			);
		}

		foreach ( array_chunk( $wide, 2 ) as $chunk ) {
			$rows[] = array(
				'type'  => 2 === count( $chunk ) ? 'pair' : 'solo',
				'items' => $chunk,
			);
		}

		return $rows;
	}

	/**
	 * Splits a list into rows of at most $max with sizes as even as
	 * possible: 5 → 3 + 2, 6 → 3 + 3, 7 → 4 + 3.
	 *
	 * @param array $list List.
	 * @param int   $max  Most per row.
	 */
	private function balanced( array $list, $max ) {
		$n = count( $list );
		if ( ! $n ) {
			return array();
		}
		$rows  = (int) ceil( $n / $max );
		$base  = intdiv( $n, $rows );
		$extra = $n % $rows;
		$out   = array();
		$at    = 0;
		for ( $r = 0; $r < $rows; $r++ ) {
			$size  = $base + ( $r < $extra ? 1 : 0 );
			$out[] = array_slice( $list, $at, $size );
			$at   += $size;
		}
		return $out;
	}

	/* ------------------------------------------------------------------ */
	/* Markup                                                              */
	/* ------------------------------------------------------------------ */

	/**
	 * One item: a <figure> with its tile (a framed screenshot, or the render
	 * itself) and an optional caption.
	 *
	 * @param array  $item   Normalised item.
	 * @param string $row    Row type.
	 * @param int    $index  Position (0-based).
	 * @param int    $count  Number of items.
	 * @param int    $slot   Position in its row.
	 * @param string $client Client name.
	 * @param bool   $pixels Pixel reveal.
	 */
	private function figure( array $item, $row, $index, $count, $slot, $client, $pixels ) {
		$device  = $item['device'];
		$alt     = $this->alt( $item, $client );
		$img     = $this->image( $item, $this->sizes( $device, $row ), $alt );
		$caption = $item['caption'];
		$link    = $item['link'];
		$url     = '' !== esc_url( (string) ( $link['url'] ?? '' ) ) ? (string) $link['url'] : '';

		$classes = array( 'avix-csg__item', 'avix-csg__item--' . $device, 'avix-csk-figure' );
		if ( '' !== $item['key'] ) {
			$classes[] = 'elementor-repeater-item-' . sanitize_html_class( $item['key'] );
		}
		if ( '' !== $url ) {
			$classes[] = 'is-linked';
		}

		// Pairs share one height: each item grows with its own shape.
		$grow  = max( 1, min( 2.4, (float) $item['ar'] ) );
		$style = sprintf( '--csg-i:%d;--csg-ar:%s;--csg-grow:%s;', (int) $index, $this->num( $item['ar'] ), $this->num( $grow ) );

		if ( 'render' === $device ) {
			$tile_style = '' !== $item['ratio'] ? ' style="' . esc_attr( '--csg-ratio: ' . $item['ratio'] ) . '"' : '';
			$tile       = '<div class="avix-csg__tile avix-csg__tile--render"' . $tile_style . ( $pixels ? ' data-csk-pixels' : '' ) . '>' . $img . '</div>';
		} else {
			$frame = $this->frame(
				$img,
				$device,
				array(
					'url_label' => 'browser' === $device ? $item['url_label'] : '',
					'ratio'     => $item['ratio'],
					'pixels'    => $pixels,
				)
			);
			$tile  = '<div class="avix-csg__tile"><div class="avix-csg__device"' . ( 'phone' === $device ? ' data-csg-depth' : '' ) . '>' . $frame . '</div></div>';
		}

		$cap = '';
		if ( '' !== $url ) {
			$key = 'csg_link_' . $index;
			$this->add_link_attributes( $key, $link );
			$this->add_render_attribute( $key, 'class', 'avix-csg__cap-link' );
			$text = '' !== $caption ? esc_html( $caption ) : '<span class="avix-csg__sr">' . esc_html( $alt ) . '</span>';
			$cap  = '<figcaption class="avix-csg__cap' . ( '' === $caption ? ' avix-csg__cap--hidden' : '' ) . '">'
				. ( '' !== $caption ? '<span class="avix-csg__px" aria-hidden="true"></span>' : '' )
				. '<a ' . $this->get_render_attribute_string( $key ) . '>' . $text
				. ( '' !== $caption ? '<svg class="avix-csg__cap-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 17 17 7M7 7h10v10"/></svg>' : '' )
				. '</a></figcaption>';
		} elseif ( '' !== $caption ) {
			$cap = '<figcaption class="avix-csg__cap"><span class="avix-csg__px" aria-hidden="true"></span><span class="avix-csg__cap-text">' . esc_html( $caption ) . '</span></figcaption>';
		}

		return '<figure class="' . esc_attr( implode( ' ', $classes ) ) . '" data-csg-item data-csg-slot="' . (int) $slot . '" style="' . esc_attr( $style ) . '">'
			. $tile
			. $cap
			. '</figure>';
	}

	/**
	 * Device frame from the shared kit (the same frames as every case-study
	 * widget), with a plain fallback if the kit failed to load.
	 *
	 * @param string $img    Image markup.
	 * @param string $device browser | phone.
	 * @param array  $o      Kit::frame() options.
	 */
	private function frame( $img, $device, array $o ) {
		if ( class_exists( '\AvixWidgets\Case_Studies\Kit' ) ) {
			return Kit::frame( $img, $device, $o );
		}
		$style = '' !== $o['ratio'] ? ' style="' . esc_attr( '--csk-ratio: ' . $o['ratio'] ) . '"' : '';
		$bar   = 'browser' === $device
			? '<div class="avix-csk-frame__bar" aria-hidden="true"><span class="avix-csk-frame__dots"><i></i><i></i><i></i></span>' . ( '' !== $o['url_label'] ? '<span class="avix-csk-frame__url">' . esc_html( $o['url_label'] ) . '</span>' : '' ) . '</div>'
			: '';
		return '<div class="avix-csk-frame avix-csk-frame--' . esc_attr( $device ) . '"' . $style . '>' . $bar . '<div class="avix-csk-frame__screen">' . $img . '</div></div>';
	}

	/**
	 * Responsive image, lazy (the gallery is never the first screen).
	 *
	 * @param array  $item  Normalised item.
	 * @param string $sizes Sizes attribute.
	 * @param string $alt   Alt text.
	 */
	private function image( array $item, $sizes, $alt ) {
		$attr = array(
			'class'    => 'avix-csg__img',
			'alt'      => $alt,
			'loading'  => 'lazy',
			'decoding' => 'async',
		);
		if ( $item['id'] ) {
			// GIFs keep the original file: resized GIFs stop animating.
			$size = 'image/gif' === get_post_mime_type( $item['id'] ) ? 'full' : 'large';
			$html = class_exists( '\AvixWidgets\Case_Studies\Kit' )
				? Kit::img( (int) $item['id'], $size, $sizes, $attr )
				: (string) wp_get_attachment_image( $item['id'], $size, false, array_merge( $attr, array( 'sizes' => $sizes ) ) );
			if ( '' !== $html ) {
				return $html;
			}
		}
		return sprintf(
			'<img class="avix-csg__img" src="%1$s" alt="%2$s" loading="lazy" decoding="async">',
			esc_url( $item['url'] ),
			esc_attr( $alt )
		);
	}

	/**
	 * Sizes attribute by slot, so the browser picks a file close to the
	 * rendered width (the carousel shows each item at about 82% of a phone).
	 *
	 * @param string $device Device.
	 * @param string $row    Row type.
	 */
	private function sizes( $device, $row ) {
		if ( 'phone' === $device ) {
			return '(max-width: 767px) 62vw, 300px';
		}
		$wide = array(
			'show'  => '(max-width: 767px) 84vw, (max-width: 1320px) 94vw, 1240px',
			'flat'  => '(max-width: 767px) 84vw, (max-width: 1320px) 72vw, 960px',
			'solo'  => '(max-width: 767px) 84vw, (max-width: 1320px) 80vw, 1040px',
			'a'     => '(max-width: 767px) 84vw, (max-width: 1320px) 62vw, 820px',
			'pair'  => '(max-width: 767px) 84vw, (max-width: 1320px) 56vw, 740px',
		);
		return $wide[ $row ] ?? $wide['pair'];
	}

	/**
	 * Alt text: the override, else the Media Library's, else the caption,
	 * else a description from the client and device.
	 *
	 * @param array  $item   Normalised item.
	 * @param string $client Client name.
	 */
	private function alt( array $item, $client ) {
		if ( '' !== $item['alt'] ) {
			return $item['alt'];
		}
		$stored = $item['id'] ? trim( (string) get_post_meta( $item['id'], '_wp_attachment_image_alt', true ) ) : '';
		if ( '' !== $stored ) {
			return $stored;
		}
		if ( '' !== $item['caption'] ) {
			/* translators: 1: client name, 2: caption. */
			return '' !== $client ? sprintf( __( '%1$s: %2$s', 'avix-widgets' ), $client, $item['caption'] ) : $item['caption'];
		}
		$who = '' !== $client ? $client : __( 'The website', 'avix-widgets' );
		$what = array(
			/* translators: %s: client name. */
			'browser' => __( '%s website on desktop', 'avix-widgets' ),
			/* translators: %s: client name. */
			'phone'   => __( '%s website on a phone', 'avix-widgets' ),
			/* translators: %s: client name. */
			'render'  => __( '%s website on desktop and mobile devices', 'avix-widgets' ),
		);
		return sprintf( $what[ $item['device'] ], $who );
	}

	/**
	 * Eyebrow with the brand pixel (the kit's markup).
	 *
	 * @param string $text Plain text.
	 */
	private function eyebrow( $text ) {
		if ( class_exists( '\AvixWidgets\Case_Studies\Kit' ) ) {
			return Kit::eyebrow( $text, 'avix-csg__eyebrow' );
		}
		return '<p class="avix-csg__eyebrow avix-csk-eyebrow"><span class="avix-csk-eyebrow__px" aria-hidden="true"></span><span class="avix-csk-eyebrow__text">' . esc_html( $text ) . '</span></p>';
	}

	/* ------------------------------------------------------------------ */
	/* Helpers                                                             */
	/* ------------------------------------------------------------------ */

	/**
	 * "W / H" from attachment metadata (used only when the kit is missing).
	 *
	 * @param int $id Attachment ID.
	 */
	private function ratio( $id ) {
		$meta = $id ? wp_get_attachment_metadata( $id ) : array();
		$w    = absint( is_array( $meta ) ? ( $meta['width'] ?? 0 ) : 0 );
		$h    = absint( is_array( $meta ) ? ( $meta['height'] ?? 0 ) : 0 );
		return $w && $h ? $w . ' / ' . $h : '';
	}

	/**
	 * Locale-proof number for inline CSS.
	 *
	 * @param float $value Value.
	 */
	private function num( $value ) {
		return rtrim( rtrim( number_format( (float) $value, 4, '.', '' ), '0' ), '.' );
	}

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
}
