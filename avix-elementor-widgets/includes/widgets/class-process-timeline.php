<?php
/**
 * Process Timeline: scroll-drawn path walked by the Avix pixel character.
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

class Process_Timeline extends Widget_Base {

	/* Path geometry, in SVG units. Each step adds one 500-unit loop. */
	const VIEW_W = 1400;
	const TOP    = 50;
	const BAND   = 500;
	const RADIUS = 150;
	const X_LEFT = 50;
	const X_RIGHT = 1350;

	public function get_name(): string {
		return 'avix-process-timeline';
	}

	public function get_title(): string {
		return esc_html__( 'Process Timeline', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-time-line';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'process', 'timeline', 'steps', 'methodology', 'scroll', 'avatar', 'avix' );
	}

	public function get_style_depends(): array {
		return array( 'avix-process-timeline' );
	}

	public function get_script_depends(): array {
		return array( 'avix-process-timeline' );
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/* ------------------------------------------------------------------ */
	/* Controls                                                            */
	/* ------------------------------------------------------------------ */

	protected function register_controls(): void {
		$this->start_controls_section( 'section_header', array( 'label' => esc_html__( 'Header', 'avix-widgets' ) ) );

		$this->add_control(
			'tag',
			array(
				'label'   => esc_html__( 'Tag', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Our Methodology', 'avix-widgets' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'   => esc_html__( 'Title', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 3,
				'default' => 'How we transform bold ideas into high-converting e-commerce and web experiences.',
				'dynamic' => array( 'active' => true ),
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
			'word_reveal',
			array(
				'label'   => esc_html__( 'Word-by-word title reveal', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section( 'section_steps', array( 'label' => esc_html__( 'Steps', 'avix-widgets' ) ) );

		$repeater = new Repeater();
		$repeater->add_control(
			'tag',
			array(
				'label'   => esc_html__( 'Tag', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Step', 'avix-widgets' ),
				'dynamic' => array( 'active' => true ),
			)
		);
		$repeater->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Title', 'avix-widgets' ),
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
				'rows'    => 5,
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'steps',
			array(
				'label'       => esc_html__( 'Steps', 'avix-widgets' ),
				'description' => esc_html__( 'The line grows one loop per step. 3–5 steps read best.', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ tag }}} · {{{ title }}}',
				'default'     => $this->default_steps(),
			)
		);

		$this->add_control(
			'show_numbers',
			array(
				'label'   => esc_html__( 'Number the tags (01 / Strategy)', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section( 'section_buddy', array( 'label' => esc_html__( 'Pixel Character', 'avix-widgets' ) ) );

		$this->add_control(
			'show_buddy',
			array(
				'label'       => esc_html__( 'Show the character', 'avix-widgets' ),
				'description' => esc_html__( 'Walks the line while it draws, then sits at the end of the line and chills.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'show_bubble',
			array(
				'label'     => esc_html__( 'Speech bubble when it sits', 'avix-widgets' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'show_buddy' => 'yes' ),
			)
		);

		$this->add_control(
			'bubble_text',
			array(
				'label'     => esc_html__( 'Bubble text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Let’s start yours', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array(
					'show_buddy'  => 'yes',
					'show_bubble' => 'yes',
				),
			)
		);

		$this->add_control(
			'bubble_link',
			array(
				'label'     => esc_html__( 'Bubble link', 'avix-widgets' ),
				'type'      => Controls_Manager::URL,
				'dynamic'   => array( 'active' => true ),
				'default'   => array( 'url' => 'https://avixdigital.com/contact/' ),
				'condition' => array(
					'show_buddy'  => 'yes',
					'show_bubble' => 'yes',
				),
			)
		);

		$this->end_controls_section();

		/* ---------- Style ---------- */
		$this->start_controls_section(
			'style_section',
			array(
				'label' => esc_html__( 'Section', 'avix-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$colors = array(
			'pt_bg'     => array( esc_html__( 'Background', 'avix-widgets' ), '--pt-bg' ),
			'pt_accent' => array( esc_html__( 'Accent (line & character)', 'avix-widgets' ), '--pt-accent' ),
			'pt_ink'    => array( esc_html__( 'Headings', 'avix-widgets' ), '--pt-ink' ),
			'pt_muted'  => array( esc_html__( 'Body text', 'avix-widgets' ), '--pt-muted' ),
			'pt_line'   => array( esc_html__( 'Undrawn line', 'avix-widgets' ), '--pt-line' ),
		);
		foreach ( $colors as $key => $color ) {
			$this->add_control(
				$key,
				array(
					'label'     => $color[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-pt' => $color[1] . ': {{VALUE}};' ),
				)
			);
		}

		$this->add_control(
			'show_grid',
			array(
				'label'     => esc_html__( 'Background grid', 'avix-widgets' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'show_glow',
			array(
				'label'   => esc_html__( 'Corner glows', 'avix-widgets' ),
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
				'selectors'          => array( '{{WRAPPER}} .avix-pt' => 'padding-top: {{TOP}}{{UNIT}}; padding-bottom: {{BOTTOM}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'max_width',
			array(
				'label'      => esc_html__( 'Content width', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 800, 'max' => 1800 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-pt' => '--pt-max: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'buddy_size',
			array(
				'label'      => esc_html__( 'Character size', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 18, 'max' => 64 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-pt' => '--pt-buddy-w: {{SIZE}}{{UNIT}};' ),
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
				'name'     => 'title_typography',
				'label'    => esc_html__( 'Title', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-pt .avix-pt__title',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'step_title_typography',
				'label'    => esc_html__( 'Step title', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-pt .avix-pt__step-title',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'desc_typography',
				'label'    => esc_html__( 'Step text', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-pt .avix-pt__desc',
			)
		);

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ */
	/* Render                                                              */
	/* ------------------------------------------------------------------ */

	protected function render(): void {
		$s     = $this->get_settings_for_display();
		$steps = array_values(
			array_filter(
				(array) $s['steps'],
				static function ( $row ) {
					return '' !== trim( (string) ( $row['title'] ?? '' ) ) || '' !== trim( (string) ( $row['description'] ?? '' ) );
				}
			)
		);
		$count = count( $steps );

		if ( ! $count ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="elementor-alert elementor-alert-info">' . esc_html__( 'Process Timeline: add at least one step.', 'avix-widgets' ) . '</div>';
			}
			return;
		}

		$geo      = $this->geometry( $count );
		$buddy    = 'yes' === $s['show_buddy'];
		$title_id = 'avix-pt-title-' . $this->get_id();
		$tag      = Utils::validate_html_tag( $s['title_tag'] );

		$classes = array( 'avix-pt' );
		if ( 'yes' !== $s['show_grid'] ) {
			$classes[] = 'avix-pt--no-grid';
		}
		if ( 'yes' !== $s['show_glow'] ) {
			$classes[] = 'avix-pt--no-glow';
		}

		$this->add_render_attribute(
			'root',
			array(
				'class'       => $classes,
				'data-avix-pt' => wp_json_encode(
					array(
						'w'     => self::VIEW_W,
						'h'     => $geo['h'],
						'bands' => $geo['bands'],
					)
				),
			)
		);
		if ( '' !== trim( (string) $s['title'] ) ) {
			$this->add_render_attribute( 'root', 'aria-labelledby', $title_id );
		}
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="avix-pt__frame">
				<header class="avix-pt__head">
					<?php if ( '' !== trim( (string) $s['tag'] ) ) : ?>
						<p class="avix-pt__tag" data-pt-reveal><b>/</b> <?php echo esc_html( $s['tag'] ); ?></p>
					<?php endif; ?>
					<?php if ( '' !== trim( (string) $s['title'] ) ) : ?>
						<<?php echo esc_attr( $tag ); ?> class="avix-pt__title" id="<?php echo esc_attr( $title_id ); ?>" data-pt-reveal<?php echo 'yes' === $s['word_reveal'] ? ' data-pt-words' : ''; ?>><?php echo esc_html( $s['title'] ); ?></<?php echo esc_attr( $tag ); ?>>
					<?php endif; ?>
				</header>

				<div class="avix-pt__timeline" data-pt-timeline style="--pt-ratio: <?php echo (int) self::VIEW_W; ?> / <?php echo (int) $geo['h']; ?>;">
					<svg class="avix-pt__svg" viewBox="0 0 <?php echo (int) self::VIEW_W; ?> <?php echo (int) $geo['h']; ?>" preserveAspectRatio="none" aria-hidden="true" focusable="false">
						<path class="avix-pt__track" d="<?php echo esc_attr( $geo['d'] ); ?>"/>
						<path class="avix-pt__draw" d="<?php echo esc_attr( $geo['d'] ); ?>" data-pt-draw/>
					</svg>

					<div class="avix-pt__rail" data-pt-rail aria-hidden="true">
						<span class="avix-pt__rail-draw" data-pt-rail-draw></span>
						<span class="avix-pt__ledge"></span>
					</div>

					<ol class="avix-pt__steps">
						<?php foreach ( $steps as $i => $step ) : ?>
							<li class="avix-pt__step avix-pt__step--<?php echo 0 === $i % 2 ? 'left' : 'right'; ?>" data-pt-step style="--pt-top: <?php echo esc_attr( round( $geo['centers'][ $i ] / $geo['h'] * 100, 3 ) ); ?>%;">
								<?php if ( '' !== trim( (string) $step['tag'] ) ) : ?>
									<p class="avix-pt__tag" data-pt-reveal><b><?php echo 'yes' === $s['show_numbers'] ? esc_html( sprintf( '%02d /', $i + 1 ) ) : '/'; ?></b> <?php echo esc_html( $step['tag'] ); ?></p>
								<?php endif; ?>
								<?php if ( '' !== trim( (string) $step['title'] ) ) : ?>
									<h3 class="avix-pt__step-title" data-pt-reveal style="--pt-delay: 100ms;"><?php echo esc_html( $step['title'] ); ?></h3>
								<?php endif; ?>
								<?php if ( '' !== trim( (string) $step['description'] ) ) : ?>
									<p class="avix-pt__desc" data-pt-reveal style="--pt-delay: 200ms;"><?php echo esc_html( $step['description'] ); ?></p>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ol>

					<?php if ( $buddy ) : ?>
						<div class="avix-pt__buddy" data-pt-buddy aria-hidden="true">
							<span class="avix-pt__sprite">
								<svg viewBox="0 0 10 12" focusable="false">
									<rect class="avix-pt__b-leg avix-pt__b-leg--l" x="2" y="7" width="2" height="5"/>
									<rect class="avix-pt__b-leg avix-pt__b-leg--r" x="6" y="7" width="2" height="5"/>
									<rect class="avix-pt__b-body" x="2" y="3" width="6" height="4"/>
									<rect class="avix-pt__b-head" x="3" y="0" width="4" height="3"/>
									<rect class="avix-pt__b-arm avix-pt__b-arm--l" x="0" y="3" width="2" height="3"/>
									<rect class="avix-pt__b-arm avix-pt__b-arm--r" x="8" y="3" width="2" height="3"/>
								</svg>
							</span>
						</div>
						<?php
						if ( 'yes' === $s['show_bubble'] && '' !== trim( (string) $s['bubble_text'] ) && ! empty( $s['bubble_link']['url'] ) ) :
							$this->add_link_attributes( 'bubble', $s['bubble_link'] );
							$this->add_render_attribute(
								'bubble',
								array(
									'class' => 'avix-pt__bubble' . ( $geo['heading_right'] ? '' : ' avix-pt__bubble--right' ),
									'style' => sprintf( '--pt-end-x:%s%%;--pt-end-y:%s%%;', round( $geo['end'][0] / self::VIEW_W * 100, 3 ), round( $geo['end'][1] / $geo['h'] * 100, 3 ) ),
								)
							);
							?>
							<a <?php $this->print_render_attribute_string( 'bubble' ); ?>>
								<?php echo esc_html( $s['bubble_text'] ); ?>
								<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
							</a>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * Builds the serpentine path for $n steps.
	 *
	 * Step i sits in band i (500 units tall). The line runs along the top of
	 * the band, turns down the far side and back along the bottom, so steps
	 * alternate left / right. It ends short of the edge: that's where the
	 * character sits.
	 *
	 * @return array{d:string,h:int,bands:int[],centers:int[],end:int[],heading_right:bool}
	 */
	private function geometry( $n ) {
		$top = self::TOP;
		$b   = self::BAND;
		$r   = self::RADIUS;
		$xl  = self::X_LEFT;
		$xr  = self::X_RIGHT;
		$d   = sprintf( 'M 0 %d', $top );

		$bands   = array();
		$centers = array();
		for ( $i = 0; $i < $n; $i++ ) {
			$y         = $top + $b * $i;
			$bands[]   = $y;
			$centers[] = $y + (int) ( $b / 2 );
			if ( 0 === $i % 2 ) {
				$d .= sprintf( ' L %1$d %2$d Q %3$d %2$d, %3$d %4$d L %3$d %5$d Q %3$d %6$d, %1$d %6$d', $xr - $r, $y, $xr, $y + $r, $y + $b - $r, $y + $b );
			} else {
				$d .= sprintf( ' L %1$d %2$d Q %3$d %2$d, %3$d %4$d L %3$d %5$d Q %3$d %6$d, %1$d %6$d', $xl + $r, $y, $xl, $y + $r, $y + $b - $r, $y + $b );
			}
		}

		$end_y         = $top + $b * $n;
		$heading_right = 0 === $n % 2;
		$end_x         = $heading_right ? 1000 : 400;
		$d            .= sprintf( ' L %d %d', $end_x, $end_y );

		return array(
			'd'             => $d,
			'h'             => $end_y + 60,
			'bands'         => $bands,
			'centers'       => $centers,
			'end'           => array( $end_x, $end_y ),
			'heading_right' => $heading_right,
		);
	}

	private function default_steps() {
		return array(
			array(
				'tag'         => 'Strategy',
				'title'       => 'Digital Audit & Strategic Planning',
				'description' => 'We begin by analyzing your brand, target audience, and current digital footprint. Our data-driven approach uncovers critical growth opportunities, laying a scalable foundation for your custom Shopify Plus or Webflow project.',
			),
			array(
				'tag'         => 'UI/UX',
				'title'       => 'Conversion-Focused UI/UX Design',
				'description' => 'We craft pixel-perfect, bespoke interfaces designed to engage users and maximize conversions. Every wireframe and prototype is tailored to reflect your premium brand identity while ensuring a frictionless customer journey.',
			),
			array(
				'tag'         => 'Development',
				'title'       => 'Advanced Web & Shopify Development',
				'description' => 'Our engineers bring your design to life using clean, scalable code. From bespoke Shopify Liquid architecture to advanced Webflow and modern JS frameworks, we build lightning-fast, robust digital platforms.',
			),
			array(
				'tag'         => 'Growth',
				'title'       => 'Seamless Launch & Measurable ROI',
				'description' => 'Before going live, we conduct rigorous QA testing to ensure peak performance across all devices. Post-launch, we provide ongoing support and technical optimization to drive sustainable e-commerce scaling and lasting business impact.',
			),
		);
	}
}
