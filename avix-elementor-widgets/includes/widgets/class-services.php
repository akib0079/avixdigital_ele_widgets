<?php
/**
 * Services Showcase: a list of services that expand on hover / tap, a 3D
 * preview card that swaps per service, and the Avix pixel character flying
 * on rocket boots to whichever service is active and pointing at it.
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

class Services extends Widget_Base {

	public function get_name(): string {
		return 'avix-services';
	}

	public function get_title(): string {
		return esc_html__( 'Services Showcase', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-accordion';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'services', 'list', 'accordion', 'tabs', 'showcase', 'avatar', 'pixel', 'avix' );
	}

	public function get_style_depends(): array {
		return array( 'avix-services' );
	}

	public function get_script_depends(): array {
		return array( 'avix-services' );
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/* ------------------------------------------------------------------ */
	/* Controls                                                            */
	/* ------------------------------------------------------------------ */

	protected function register_controls(): void {
		$this->controls_header();
		$this->controls_services();
		$this->controls_card();
		$this->controls_avatar();
		$this->controls_style();
	}

	private function controls_header() {
		$this->start_controls_section( 'section_header', array( 'label' => esc_html__( 'Header', 'avix-widgets' ) ) );

		$this->add_control(
			'tag',
			array(
				'label'       => esc_html__( 'Tag', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Empowering Your Brand Journey', 'avix-widgets' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Title', 'avix-widgets' ),
				'description' => esc_html__( 'Wrap words in [square brackets] to colour them orange. Press Enter for a new line.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => "Comprehensive Digital\nSolutions by Avix Digital.",
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
				),
			)
		);

		$this->add_control(
			'intro',
			array(
				'label'   => esc_html__( 'Intro text (optional)', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 3,
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'head_align',
			array(
				'label'   => esc_html__( 'Alignment', 'avix-widgets' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'center',
				'toggle'  => false,
				'options' => array(
					'left'   => array(
						'title' => esc_html__( 'Left', 'avix-widgets' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => esc_html__( 'Center', 'avix-widgets' ),
						'icon'  => 'eicon-text-align-center',
					),
				),
			)
		);

		$this->end_controls_section();
	}

	private function controls_services() {
		$this->start_controls_section( 'section_services', array( 'label' => esc_html__( 'Services', 'avix-widgets' ) ) );

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
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);
		$repeater->add_control(
			'description',
			array(
				'label'       => esc_html__( 'Description', 'avix-widgets' ),
				'description' => esc_html__( '[Words in brackets] are highlighted.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 4,
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
			'link_text',
			array(
				'label'   => esc_html__( 'Link text', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Learn more', 'avix-widgets' ),
				'dynamic' => array( 'active' => true ),
			)
		);
		$repeater->add_control(
			'image',
			array(
				'label'   => esc_html__( 'Preview image', 'avix-widgets' ),
				'type'    => Controls_Manager::MEDIA,
				'dynamic' => array( 'active' => true ),
			)
		);
		$repeater->add_control(
			'label',
			array(
				'label'       => esc_html__( 'Card label (optional)', 'avix-widgets' ),
				'description' => esc_html__( 'Small chip on the preview, e.g. “Shopify Plus Partner”.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
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

		$this->add_control(
			'query_words',
			array(
				'label'       => esc_html__( 'Description length (words)', 'avix-widgets' ),
				'description' => esc_html__( 'Taken from the excerpt. The featured image becomes the preview.', 'avix-widgets' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 24,
				'min'         => 8,
				'max'         => 60,
				'condition'   => array( 'source' => 'query' ),
			)
		);

		$this->add_control(
			'query_link_text',
			array(
				'label'     => esc_html__( 'Link text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Learn more', 'avix-widgets' ),
				'condition' => array( 'source' => 'query' ),
			)
		);

		$this->add_control(
			'active_index',
			array(
				'label'     => esc_html__( 'Open first', 'avix-widgets' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 1,
				'min'       => 1,
				'max'       => 12,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'trigger',
			array(
				'label'       => esc_html__( 'Open a service on', 'avix-widgets' ),
				'description' => esc_html__( 'Touch screens always use tap.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'hover',
				'options'     => array(
					'hover' => esc_html__( 'Hover', 'avix-widgets' ),
					'click' => esc_html__( 'Click', 'avix-widgets' ),
				),
			)
		);

		$this->add_control(
			'show_index',
			array(
				'label'   => esc_html__( 'Number the services (01, 02…)', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'item_tag',
			array(
				'label'   => esc_html__( 'Service name tag', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h3',
				'options' => array(
					'h3'  => 'H3',
					'h4'  => 'H4',
					'div' => 'div',
				),
			)
		);

		$this->end_controls_section();
	}

	private function controls_card() {
		$this->start_controls_section( 'section_card', array( 'label' => esc_html__( 'Preview Card', 'avix-widgets' ) ) );

		$this->add_control(
			'frame',
			array(
				'label'   => esc_html__( 'Frame', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'browser',
				'options' => array(
					'browser' => esc_html__( 'Browser window (shows the page address)', 'avix-widgets' ),
					'border'  => esc_html__( 'White border', 'avix-widgets' ),
					'none'    => esc_html__( 'None', 'avix-widgets' ),
				),
			)
		);

		$this->add_control(
			'ratio',
			array(
				'label'   => esc_html__( 'Image shape', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '16 / 10',
				'options' => array(
					'16 / 10' => '16:10',
					'16 / 9'  => '16:9',
					'4 / 3'   => '4:3',
					'1 / 1'   => '1:1',
				),
			)
		);

		$this->add_control(
			'tilt',
			array(
				'label'       => esc_html__( 'Tilts toward the mouse', 'avix-widgets' ),
				'description' => esc_html__( 'With a light glare that follows the cursor. Desktop only.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->end_controls_section();
	}

	private function controls_avatar() {
		$this->start_controls_section( 'section_avatar', array( 'label' => esc_html__( 'Pixel Avatar', 'avix-widgets' ) ) );

		$this->add_control(
			'show_avatar',
			array(
				'label'       => esc_html__( 'Show the guide', 'avix-widgets' ),
				'description' => esc_html__( 'The Avix pixel character flies to the service being viewed and points at it; the rail lights up behind it.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'boots',
			array(
				'label'       => esc_html__( 'Rocket boots', 'avix-widgets' ),
				'description' => esc_html__( 'Flickering jets, an exhaust trail and afterimages while it flies.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'condition'   => array( 'show_avatar' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'avatar_size',
			array(
				'label'      => esc_html__( 'Size', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 18, 'max' => 60 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-sv' => '--sv-avatar-w: {{SIZE}}{{UNIT}};' ),
				'condition'  => array( 'show_avatar' => 'yes' ),
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
			'sv_bg'     => array( esc_html__( 'Background', 'avix-widgets' ), '--sv-bg' ),
			'sv_accent' => array( esc_html__( 'Accent', 'avix-widgets' ), '--sv-accent' ),
			'sv_ink'    => array( esc_html__( 'Headings', 'avix-widgets' ), '--sv-ink' ),
			'sv_muted'  => array( esc_html__( 'Body text', 'avix-widgets' ), '--sv-muted' ),
			'sv_line'   => array( esc_html__( 'Outlines & rail', 'avix-widgets' ), '--sv-line' ),
		);
		foreach ( $colors as $key => $color ) {
			$this->add_control(
				$key,
				array(
					'label'     => $color[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-sv' => $color[1] . ': {{VALUE}};' ),
				)
			);
		}

		$this->add_control(
			'idle_style',
			array(
				'label'     => esc_html__( 'Closed services', 'avix-widgets' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'outline',
				'options'   => array(
					'outline' => esc_html__( 'Outlined text', 'avix-widgets' ),
					'muted'   => esc_html__( 'Dimmed text', 'avix-widgets' ),
				),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'show_glow',
			array(
				'label'   => esc_html__( 'Background glow', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'show_grid',
			array(
				'label'   => esc_html__( 'Background grid', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_responsive_control(
			'padding',
			array(
				'label'              => esc_html__( 'Padding', 'avix-widgets' ),
				'type'               => Controls_Manager::DIMENSIONS,
				'size_units'         => array( 'px', 'vh' ),
				'allowed_dimensions' => 'vertical',
				'separator'          => 'before',
				'selectors'          => array( '{{WRAPPER}} .avix-sv' => 'padding-top: {{TOP}}{{UNIT}}; padding-bottom: {{BOTTOM}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'max_width',
			array(
				'label'      => esc_html__( 'Content width', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 800, 'max' => 1800 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-sv' => '--sv-max: {{SIZE}}{{UNIT}};' ),
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

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'headline_typography',
				'label'    => esc_html__( 'Title', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-sv .avix-sv__headline',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'name_typography',
				'label'    => esc_html__( 'Service names', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-sv .avix-sv__name',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'desc_typography',
				'label'    => esc_html__( 'Descriptions', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-sv .avix-sv__desc',
			)
		);

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ */
	/* Render                                                              */
	/* ------------------------------------------------------------------ */

	protected function render(): void {
		$s     = $this->get_settings_for_display();
		$items = 'query' === $s['source'] ? $this->query_items( $s ) : $this->manual_items( $s );

		if ( ! $items ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="elementor-alert elementor-alert-info">' . esc_html__( 'Services Showcase: add at least one service (or pick a post type that has published posts).', 'avix-widgets' ) . '</div>';
			}
			return;
		}

		$uid      = $this->get_id();
		$count    = count( $items );
		$active   = max( 0, min( $count - 1, (int) $s['active_index'] - 1 ) );
		$avatar   = 'yes' === $s['show_avatar'];
		$boots    = $avatar && 'yes' === $s['boots'];
		$title_id = 'avix-sv-title-' . $uid;
		$title    = $this->accent_html( (string) $s['title'], true );
		$tag      = Utils::validate_html_tag( $s['title_tag'] );
		$item_tag = Utils::validate_html_tag( $s['item_tag'] );
		$ratio    = in_array( $s['ratio'], array( '16 / 10', '16 / 9', '4 / 3', '1 / 1' ), true ) ? $s['ratio'] : '16 / 10';

		$classes = array(
			'avix-sv',
			'avix-sv--frame-' . ( in_array( $s['frame'], array( 'browser', 'border', 'none' ), true ) ? $s['frame'] : 'browser' ),
			'avix-sv--' . ( 'muted' === $s['idle_style'] ? 'muted' : 'outline' ),
			'avix-sv--head-' . ( 'left' === $s['head_align'] ? 'left' : 'center' ),
		);
		if ( $avatar ) {
			$classes[] = 'avix-sv--guide';
		}
		if ( 'yes' === $s['show_index'] ) {
			$classes[] = 'avix-sv--indexed';
		}
		if ( 'yes' !== $s['show_glow'] ) {
			$classes[] = 'avix-sv--no-glow';
		}
		if ( 'yes' !== $s['show_grid'] ) {
			$classes[] = 'avix-sv--no-grid';
		}

		$this->add_render_attribute(
			'root',
			array(
				'class'       => $classes,
				'style'       => '--sv-ratio: ' . $ratio . ';',
				'data-avix-sv' => wp_json_encode(
					array(
						'trigger' => 'click' === $s['trigger'] ? 'click' : 'hover',
						'tilt'    => 'yes' === $s['tilt'],
						'boots'   => $boots,
					)
				),
			)
		);
		if ( '' !== $title ) {
			$this->add_render_attribute( 'root', 'aria-labelledby', $title_id );
		}
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="avix-sv__bg" aria-hidden="true">
				<span class="avix-sv__glow avix-sv__glow--1"></span>
				<span class="avix-sv__glow avix-sv__glow--2"></span>
				<span class="avix-sv__spot" data-sv-spot></span>
				<span class="avix-sv__grid"></span>
			</div>

			<div class="avix-sv__frame">
				<?php if ( '' !== trim( (string) $s['tag'] ) || '' !== $title || '' !== trim( (string) $s['intro'] ) ) : ?>
					<header class="avix-sv__head">
						<?php if ( '' !== trim( (string) $s['tag'] ) ) : ?>
							<p class="avix-sv__tag"><b aria-hidden="true">/</b> <?php echo esc_html( $s['tag'] ); ?></p>
						<?php endif; ?>
						<?php if ( '' !== $title ) : ?>
							<<?php echo esc_attr( $tag ); ?> class="avix-sv__headline" id="<?php echo esc_attr( $title_id ); ?>"><?php echo $title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in accent_html(). ?></<?php echo esc_attr( $tag ); ?>>
						<?php endif; ?>
						<?php if ( '' !== trim( (string) $s['intro'] ) ) : ?>
							<p class="avix-sv__intro"><?php echo esc_html( $s['intro'] ); ?></p>
						<?php endif; ?>
					</header>
				<?php endif; ?>

				<div class="avix-sv__inner">
					<div class="avix-sv__list" data-sv-list>
						<?php if ( $avatar ) : ?>
							<?php $this->render_guide( $boots, $count ); ?>
						<?php endif; ?>

						<?php foreach ( $items as $i => $item ) : ?>
							<?php $this->render_item( $s, $item, $i, $active === $i, $item_tag, $uid ); ?>
						<?php endforeach; ?>
					</div>

					<div class="avix-sv__visual" data-sv-visual>
						<div class="avix-sv__stage" data-sv-stage>
							<?php foreach ( $items as $i => $item ) : ?>
								<?php $this->render_card( $item, $i, $active === $i ); ?>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			</div>
		</section>
		<?php
	}

	private function render_item( array $s, array $item, $i, $is_active, $item_tag, $uid ) {
		$tab   = 'avix-sv-tab-' . $uid . '-' . $i;
		$panel = 'avix-sv-panel-' . $uid . '-' . $i;
		?>
		<div class="avix-sv__item<?php echo $is_active ? ' is-active' : ''; ?>" data-sv-item style="--sv-i: <?php echo (int) $i; ?>;">
			<<?php echo esc_attr( $item_tag ); ?> class="avix-sv__heading">
				<button class="avix-sv__toggle" type="button" id="<?php echo esc_attr( $tab ); ?>" aria-expanded="<?php echo $is_active ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $panel ); ?>" data-sv-toggle>
					<?php if ( 'yes' === $s['show_index'] ) : ?>
						<span class="avix-sv__index" aria-hidden="true"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span>
					<?php endif; ?>
					<span class="avix-sv__name"><?php echo $this->name_html( $item['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in name_html(). ?></span>
				</button>
			</<?php echo esc_attr( $item_tag ); ?>>
			<div class="avix-sv__panel" id="<?php echo esc_attr( $panel ); ?>" role="region" aria-labelledby="<?php echo esc_attr( $tab ); ?>">
				<div class="avix-sv__panel-inner">
					<?php
					$image = $this->image_html( $item, '(min-width: 1025px) 600px, 92vw', 'avix-sv__img' );
					if ( '' !== $image ) :
						?>
						<div class="avix-sv__mobile-shot"><?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in image_html(). ?></div>
					<?php endif; ?>
					<?php if ( '' !== $item['desc'] ) : ?>
						<p class="avix-sv__desc"><?php echo $item['desc']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped when the item was built. ?></p>
					<?php endif; ?>
					<?php
					if ( $item['link'] && '' !== $item['link_text'] ) :
						$key = 'more-' . $i;
						$this->add_link_attributes( $key, $item['link'] );
						$this->add_render_attribute( $key, 'class', 'avix-sv__more' );
						?>
						<a <?php $this->print_render_attribute_string( $key ); ?>>
							<?php echo esc_html( $item['link_text'] ); ?>
							<span class="screen-reader-text avix-sv__sr"><?php echo esc_html( ': ' . $item['title'] ); ?></span>
							<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	private function render_card( array $item, $i, $is_active ) {
		$image = $this->image_html( $item, '(min-width: 1440px) 660px, (min-width: 1025px) 46vw, 92vw', 'avix-sv__img' );
		$url   = $item['link'] ? $this->url_label( $item['link']['url'] ) : '';
		?>
		<figure class="avix-sv__card<?php echo $is_active ? ' is-active' : ''; ?>" data-sv-card>
			<div class="avix-sv__chrome" aria-hidden="true">
				<i></i><i></i><i></i>
				<?php if ( '' !== $url ) : ?>
					<span class="avix-sv__url"><?php echo esc_html( $url ); ?></span>
				<?php endif; ?>
			</div>
			<div class="avix-sv__shot">
				<?php if ( '' !== $image ) : ?>
					<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in image_html(). ?>
				<?php else : ?>
					<div class="avix-sv__placeholder" aria-hidden="true">
						<span><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span>
						<b><?php echo esc_html( $item['title'] ); ?></b>
					</div>
				<?php endif; ?>
				<span class="avix-sv__glare" aria-hidden="true"></span>
			</div>
			<?php if ( '' !== $item['label'] ) : ?>
				<figcaption class="avix-sv__label"><?php echo esc_html( $item['label'] ); ?></figcaption>
			<?php endif; ?>
		</figure>
		<?php
	}

	/**
	 * Rail, exhaust canvas, afterimages and the avatar itself.
	 */
	private function render_guide( $boots, $count ) {
		?>
		<div class="avix-sv__guide" aria-hidden="true">
			<span class="avix-sv__rail"><span class="avix-sv__rail-fill" data-sv-fill></span></span>
			<?php echo str_repeat( '<span class="avix-sv__dot" data-sv-dot></span>', (int) $count ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup. ?>
			<?php if ( $boots ) : ?>
				<canvas class="avix-sv__exhaust" data-sv-exhaust></canvas>
				<span class="avix-sv__ghost" data-sv-ghost><?php echo $this->sprite( false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup. ?></span>
				<span class="avix-sv__ghost" data-sv-ghost><?php echo $this->sprite( false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup. ?></span>
			<?php endif; ?>
			<span class="avix-sv__avatar" data-sv-avatar>
				<span class="avix-sv__bob"><?php echo $this->sprite( $boots ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup. ?></span>
			</span>
		</div>
		<?php
	}

	/**
	 * The Avix pixel character (same 10-unit grid as the site), standing,
	 * with a pointing arm and optional rocket-boot jets under the feet.
	 */
	private function sprite( $boots ) {
		$jets = $boots
			? '<rect class="avix-sv__jet avix-sv__jet--l" x="2" y="12" width="2" height="2.5"/>'
				. '<rect class="avix-sv__jet avix-sv__jet--r" x="6" y="12" width="2" height="2.5"/>'
				. '<rect class="avix-sv__core avix-sv__core--l" x="2.5" y="12" width="1" height="1.3"/>'
				. '<rect class="avix-sv__core avix-sv__core--r" x="6.5" y="12" width="1" height="1.3"/>'
			: '';
		return '<svg viewBox="0 0 10 12" focusable="false">' . $jets
			. '<rect class="avix-sv__b-leg avix-sv__b-leg--l" x="2" y="7" width="2" height="5"/>'
			. '<rect class="avix-sv__b-leg avix-sv__b-leg--r" x="6" y="7" width="2" height="5"/>'
			. '<rect class="avix-sv__b-body" x="2" y="3" width="6" height="4"/>'
			. '<rect class="avix-sv__b-head" x="3" y="0" width="4" height="3"/>'
			. '<rect class="avix-sv__b-arm avix-sv__b-arm--l" x="0" y="3" width="2" height="3"/>'
			. '<rect class="avix-sv__b-arm avix-sv__b-arm--r" x="8" y="3" width="2" height="3"/>'
			. '<rect class="avix-sv__b-point" x="8" y="3" width="4" height="2"/>'
			. '</svg>';
	}

	/* ------------------------------------------------------------------ */
	/* Data                                                                */
	/* ------------------------------------------------------------------ */

	/**
	 * @return array<int, array{title:string,desc:string,link:?array,link_text:string,image_id:int,image_url:string,label:string}>
	 */
	private function manual_items( array $s ) {
		$items = array();
		foreach ( (array) $s['items'] as $row ) {
			$title = trim( (string) ( $row['title'] ?? '' ) );
			if ( '' === $title ) {
				continue;
			}
			$image   = (array) ( $row['image'] ?? array() );
			$items[] = array(
				'title'     => $title,
				'desc'      => $this->accent_html( (string) ( $row['description'] ?? '' ), false ),
				'link'      => ! empty( $row['link']['url'] ) ? $row['link'] : null,
				'link_text' => trim( (string) ( $row['link_text'] ?? '' ) ),
				'image_id'  => absint( $image['id'] ?? 0 ) ? absint( $image['id'] ) : $this->attachment_id( $image['url'] ?? '' ),
				'image_url' => (string) ( $image['url'] ?? '' ),
				'label'     => trim( (string) ( $row['label'] ?? '' ) ),
			);
		}
		return $items;
	}

	private function query_items( array $s ) {
		$post_type = sanitize_key( (string) $s['query_post_type'] );
		if ( '' === $post_type || ! post_type_exists( $post_type ) ) {
			return array();
		}

		$orderby = in_array( $s['query_orderby'], array( 'menu_order', 'date', 'title' ), true ) ? $s['query_orderby'] : 'menu_order';
		$order   = 'DESC' === $s['query_order'] ? 'DESC' : 'ASC';
		$query   = new \WP_Query(
			array(
				'post_type'           => $post_type,
				'post_status'         => 'publish',
				'posts_per_page'      => max( 1, min( 12, (int) $s['query_count'] ) ),
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'orderby'             => 'menu_order' === $orderby ? array( 'menu_order' => $order, 'date' => 'DESC' ) : $orderby,
				'order'               => $order,
			)
		);

		$words = max( 8, min( 60, (int) $s['query_words'] ) );
		$items = array();
		foreach ( $query->posts as $post ) {
			$items[] = array(
				'title'     => wp_strip_all_tags( get_the_title( $post ) ),
				'desc'      => esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post ) ), $words ) ),
				'link'      => array( 'url' => get_permalink( $post ) ),
				'link_text' => trim( (string) $s['query_link_text'] ),
				'image_id'  => (int) get_post_thumbnail_id( $post ),
				'image_url' => '',
				'label'     => '',
			);
		}
		return $items;
	}

	/**
	 * Service name with the arrow glued to its last word, so it follows the
	 * text on any line and never wraps onto a line of its own.
	 */
	private function name_html( $title ) {
		$arrow = '<svg class="avix-sv__arrow" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 17 17 7M7 7h10v10"/></svg>';
		$words = preg_split( '/\s+/u', trim( $title ) );
		$last  = array_pop( $words );
		$head  = $words ? esc_html( implode( ' ', $words ) ) . ' ' : '';
		return $head . '<span class="avix-sv__tail">' . esc_html( (string) $last ) . $arrow . '</span>';
	}

	/**
	 * Escaped text; [words] become the accent span, new lines <br> when $breaks.
	 */
	private function accent_html( $text, $breaks ) {
		$lines = array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $text ) ), 'strlen' ) );
		$lines = array_map(
			static function ( $line ) {
				$html = preg_replace( '/\[([^\[\]]+)\]/u', '<span class="avix-sv__accent">$1</span>', esc_html( $line ) );
				return null === $html ? esc_html( $line ) : $html;
			},
			$lines
		);
		return implode( $breaks ? ' <br>' : ' ', $lines );
	}

	private function image_html( array $item, $sizes, $class ) {
		if ( $item['image_id'] ) {
			$stored = get_post_meta( $item['image_id'], '_wp_attachment_image_alt', true );
			$html   = wp_get_attachment_image(
				$item['image_id'],
				'full',
				false,
				array(
					'class'    => $class,
					'alt'      => $stored ? $stored : $item['title'],
					'loading'  => 'lazy',
					'decoding' => 'async',
					'sizes'    => $sizes,
				)
			);
			if ( $html ) {
				return $html;
			}
		}
		if ( '' !== $item['image_url'] ) {
			return sprintf(
				'<img class="%s" src="%s" alt="%s" loading="lazy" decoding="async">',
				esc_attr( $class ),
				esc_url( $item['image_url'] ),
				esc_attr( $item['title'] )
			);
		}
		return '';
	}

	/**
	 * Media-library ID for an image given only by URL (e.g. the defaults), so it
	 * still gets responsive srcset sizes. Also matches resized names like
	 * "photo-2048x1143.webp". Cached per request.
	 */
	private function attachment_id( $url ) {
		static $cache = array();
		$url = (string) $url;
		if ( '' === $url || ! function_exists( 'attachment_url_to_postid' ) ) {
			return 0;
		}
		if ( ! isset( $cache[ $url ] ) ) {
			$id = attachment_url_to_postid( $url );
			if ( ! $id ) {
				$full = preg_replace( '/-\d+x\d+(\.[a-z0-9]+)$/i', '$1', $url );
				$id   = $full !== $url ? attachment_url_to_postid( $full ) : 0;
			}
			$cache[ $url ] = (int) $id;
		}
		return $cache[ $url ];
	}

	/**
	 * "avixdigital.com/service/web-development" for the browser frame.
	 */
	private function url_label( $url ) {
		$parts = wp_parse_url( (string) $url );
		if ( ! is_array( $parts ) ) {
			return '';
		}
		$host = isset( $parts['host'] ) ? $parts['host'] : (string) wp_parse_url( home_url(), PHP_URL_HOST );
		$path = isset( $parts['path'] ) ? untrailingslashit( $parts['path'] ) : '';
		return preg_replace( '/^www\./', '', $host ) . $path;
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

	/** Prefer a public services post type (themes often register one), picked from the same list the control offers. */
	private function default_post_type() {
		$options = $this->post_type_options();
		foreach ( array( 'cpt_services', 'services', 'service' ) as $candidate ) {
			if ( isset( $options[ $candidate ] ) ) {
				return $candidate;
			}
		}
		return isset( $options['post'] ) ? 'post' : (string) key( $options );
	}

	private function default_items() {
		return array(
			array(
				'title'       => 'Web Development',
				'description' => 'We build high-performance websites using [WordPress, Shopify, and Webflow] tailored to your specific business goals.',
				'link'        => array( 'url' => 'https://avixdigital.com/service/web-development/' ),
				'image'       => array( 'url' => 'https://avixdigital.com/wp-content/uploads/2026/03/Untitled-design227-2048x1143.webp' ),
			),
			array(
				'title'       => 'Shopify Plus',
				'description' => 'Enterprise e-commerce architecture utilizing [custom Liquid coding] and automated Shopify Flow ecosystems to drive explosive revenue growth.',
				'link'        => array( 'url' => 'https://avixdigital.com/service/shopify-plus/' ),
				'image'       => array( 'url' => 'https://avixdigital.com/wp-content/uploads/2026/06/Untitled-design235.png' ),
			),
			array(
				'title'       => 'UI/UX & Brand Design',
				'description' => 'High-converting, bespoke interfaces engineered for modern brands. We transform complex data into [pixel-perfect, intuitive user journeys].',
				'link'        => array( 'url' => 'https://avixdigital.com/service/uiux-and-brand-design/' ),
				'image'       => array( 'url' => 'https://avixdigital.com/wp-content/uploads/2026/06/Untitled-design247.webp' ),
			),
			array(
				'title'       => 'Growth ROI',
				'description' => 'Continuous technical optimization, server-side scaling, and rigorous [conversion rate optimization (CRO)] to dominate your digital market.',
				'link'        => array( 'url' => 'https://avixdigital.com/contact/' ),
				'image'       => array( 'url' => 'https://avixdigital.com/wp-content/uploads/2025/12/FullSizeRender-3-scaled.jpg' ),
			),
		);
	}
}
