<?php
/**
 * Impact Numbers: animated proof metrics ("Numbers behind the work").
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

class Impact_Numbers extends Widget_Base {

	public function get_name(): string {
		return 'avix-impact-numbers';
	}

	public function get_title(): string {
		return esc_html__( 'Impact Numbers', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-counter';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'numbers', 'stats', 'counter', 'metrics', 'impact', 'proof', 'avix' );
	}

	public function get_style_depends(): array {
		return array( 'avix-impact-numbers' );
	}

	public function get_script_depends(): array {
		return array( 'avix-impact-numbers' );
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	protected function register_controls(): void {
		/* ---------- Content: header ---------- */
		$this->start_controls_section(
			'section_header',
			array( 'label' => esc_html__( 'Header', 'avix-widgets' ) )
		);

		$this->add_control(
			'layout',
			array(
				'label'   => esc_html__( 'Layout', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'minimal',
				'options' => array(
					'minimal'   => esc_html__( 'Minimal (centered, dividers)', 'avix-widgets' ),
					'editorial' => esc_html__( 'Editorial (heading + rules)', 'avix-widgets' ),
				),
			)
		);

		$this->add_control(
			'eyebrow',
			array(
				'label'       => esc_html__( 'Eyebrow', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Numbers behind the work', 'avix-widgets' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Title', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'Optional. Leave empty for the minimal look.', 'avix-widgets' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'title_accent',
			array(
				'label'       => esc_html__( 'Highlighted words', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
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
					'h4'  => 'H4',
					'div' => 'div',
					'p'   => 'p',
				),
			)
		);

		$this->add_control(
			'text',
			array(
				'label'   => esc_html__( 'Text', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 3,
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'head_layout',
			array(
				'label'   => esc_html__( 'Header layout', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default'   => 'split',
				'options'   => array(
					'split'  => esc_html__( 'Title left, text right', 'avix-widgets' ),
					'center' => esc_html__( 'Centered', 'avix-widgets' ),
				),
				'condition' => array( 'layout' => 'editorial' ),
			)
		);

		$this->end_controls_section();

		/* ---------- Content: metrics ---------- */
		$this->start_controls_section(
			'section_metrics',
			array( 'label' => esc_html__( 'Numbers', 'avix-widgets' ) )
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'value',
			array(
				'label'       => esc_html__( 'Number', 'avix-widgets' ),
				'description' => esc_html__( 'Digits only, decimals allowed (e.g. 250 or 5.0).', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '100',
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'prefix',
			array(
				'label' => esc_html__( 'Prefix', 'avix-widgets' ),
				'type'  => Controls_Manager::TEXT,
			)
		);

		$repeater->add_control(
			'suffix',
			array(
				'label'   => esc_html__( 'Suffix', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '+',
			)
		);

		$repeater->add_control(
			'suffix_size',
			array(
				'label'   => esc_html__( 'Suffix size', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'full',
				'options' => array(
					'full'  => esc_html__( 'Same as number', 'avix-widgets' ),
					'small' => esc_html__( 'Small', 'avix-widgets' ),
				),
			)
		);

		$repeater->add_control(
			'label',
			array(
				'label'       => esc_html__( 'Label', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Label', 'avix-widgets' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'description',
			array(
				'label'   => esc_html__( 'Small print', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 2,
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'metrics',
			array(
				'label'       => esc_html__( 'Numbers', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ prefix }}}{{{ value }}}{{{ suffix }}} · {{{ label }}}',
				'default'     => array(
					array(
						'value'  => '200',
						'suffix' => '+',
						'label'  => 'Clients supported worldwide',
					),
					array(
						'value'  => '250',
						'suffix' => '+',
						'label'  => 'Projects delivered',
					),
					array(
						'value'  => '150',
						'suffix' => '+',
						'label'  => 'Verified client reviews',
					),
				),
			)
		);

		$this->add_control(
			'show_index',
			array(
				'label'     => esc_html__( 'Show 01, 02… numbering', 'avix-widgets' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => '',
				'separator' => 'before',
				'condition' => array( 'layout' => 'editorial' ),
			)
		);

		$this->add_control(
			'thousands',
			array(
				'label'   => esc_html__( 'Thousands separator', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => ',',
				'options' => array(
					','  => esc_html__( 'Comma (1,000)', 'avix-widgets' ),
					'.'  => esc_html__( 'Dot (1.000)', 'avix-widgets' ),
					' '  => esc_html__( 'Space (1 000)', 'avix-widgets' ),
					''   => esc_html__( 'None (1000)', 'avix-widgets' ),
				),
			)
		);

		$this->end_controls_section();

		/* ---------- Content: motion ---------- */
		$this->start_controls_section(
			'section_motion',
			array( 'label' => esc_html__( 'Motion', 'avix-widgets' ) )
		);

		$this->add_control(
			'animate',
			array(
				'label'       => esc_html__( 'Reveal on scroll', 'avix-widgets' ),
				'description' => esc_html__( 'Skipped automatically for visitors who prefer reduced motion.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'count_up',
			array(
				'label'     => esc_html__( 'Count up numbers', 'avix-widgets' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'animate' => 'yes' ),
			)
		);

		$this->add_control(
			'duration',
			array(
				'label'     => esc_html__( 'Count duration (ms)', 'avix-widgets' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 1800,
				'min'       => 300,
				'max'       => 6000,
				'step'      => 100,
				'condition' => array(
					'animate'  => 'yes',
					'count_up' => 'yes',
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

		$this->add_control(
			'theme',
			array(
				'label'   => esc_html__( 'Theme', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'light',
				'options' => array(
					'light' => esc_html__( 'Light', 'avix-widgets' ),
					'soft'  => esc_html__( 'Warm grey', 'avix-widgets' ),
					'dark'  => esc_html__( 'Dark', 'avix-widgets' ),
				),
			)
		);

		$this->add_control(
			'divider',
			array(
				'label'   => esc_html__( 'Top divider line', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'bg',
			array(
				'label'     => esc_html__( 'Background', 'avix-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .avix-impact' => '--ai-bg: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'accent',
			array(
				'label'     => esc_html__( 'Accent', 'avix-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .avix-impact' => '--ai-accent: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'line',
			array(
				'label'     => esc_html__( 'Lines', 'avix-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .avix-impact' => '--ai-line: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'padding',
			array(
				'label'              => esc_html__( 'Padding', 'avix-widgets' ),
				'type'               => Controls_Manager::DIMENSIONS,
				'size_units'         => array( 'px', 'em', 'vh' ),
				'allowed_dimensions' => 'vertical',
				'selectors'          => array( '{{WRAPPER}} .avix-impact' => 'padding-top: {{TOP}}{{UNIT}}; padding-bottom: {{BOTTOM}}{{UNIT}};' ),
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
				'selectors'  => array( '{{WRAPPER}} .avix-impact' => '--ai-max: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'side_gutter',
			array(
				'label'      => esc_html__( 'Side spacing', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-impact' => '--ai-gutter: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_text',
			array(
				'label' => esc_html__( 'Typography', 'avix-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => esc_html__( 'Title & numbers', 'avix-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .avix-impact' => '--ai-ink: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'muted_color',
			array(
				'label'     => esc_html__( 'Secondary text', 'avix-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .avix-impact' => '--ai-muted: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'label'    => esc_html__( 'Title', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-impact .avix-impact__title',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'value_typography',
				'label'    => esc_html__( 'Numbers', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-impact .avix-impact__value',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'label_typography',
				'label'    => esc_html__( 'Labels', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-impact .avix-impact__label',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'text_typography',
				'label'    => esc_html__( 'Body text', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-impact .avix-impact__text, {{WRAPPER}} .avix-impact .avix-impact__desc',
			)
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$metrics  = array_values(
			array_filter(
				(array) $settings['metrics'],
				static function ( $row ) {
					return '' !== trim( (string) ( $row['value'] ?? '' ) ) || '' !== trim( (string) ( $row['label'] ?? '' ) );
				}
			)
		);

		$theme    = in_array( $settings['theme'], array( 'light', 'soft', 'dark' ), true ) ? $settings['theme'] : 'light';
		$title_id = 'avix-impact-title-' . $this->get_id();
		$has_head = '' !== trim( $settings['eyebrow'] . $settings['title'] . $settings['title_accent'] . $settings['text'] );
		$has_title = '' !== trim( $settings['title'] . $settings['title_accent'] );

		$minimal = 'editorial' !== $settings['layout'];
		$classes = array( 'avix-impact', 'avix-impact--' . $theme, $minimal ? 'avix-impact--minimal' : 'avix-impact--editorial', 'avix-impact--head-' . ( $minimal || 'center' === $settings['head_layout'] ? 'center' : 'split' ) );
		if ( ! $has_title && '' === trim( (string) $settings['text'] ) ) {
			$classes[] = 'avix-impact--eyebrow-only';
		}
		if ( 'yes' === $settings['divider'] ) {
			$classes[] = 'avix-impact--divider';
		}

		$this->add_render_attribute(
			'root',
			array(
				'class'            => $classes,
				'style'            => '--ai-cols:' . max( 1, min( 4, count( $metrics ) ) ) . ';',
				'data-avix-impact' => wp_json_encode(
					array(
						'animate'  => 'yes' === $settings['animate'],
						'countUp'  => 'yes' === $settings['count_up'],
						'duration' => max( 300, min( 6000, (int) $settings['duration'] ) ),
					)
				),
			)
		);
		if ( $has_title ) {
			$this->add_render_attribute( 'root', 'aria-labelledby', $title_id );
		}

		$tag = Utils::validate_html_tag( $settings['title_tag'] );
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="avix-impact__frame">
				<?php if ( $has_head ) : ?>
					<header class="avix-impact__head">
						<div>
							<?php if ( '' !== trim( (string) $settings['eyebrow'] ) ) : ?>
								<p class="avix-impact__eyebrow"><?php echo esc_html( $settings['eyebrow'] ); ?></p>
							<?php endif; ?>
							<?php if ( $has_title ) : ?>
								<<?php echo esc_attr( $tag ); ?> class="avix-impact__title" id="<?php echo esc_attr( $title_id ); ?>">
									<?php echo esc_html( $settings['title'] ); ?>
									<?php if ( '' !== trim( (string) $settings['title_accent'] ) ) : ?>
										<span class="avix-impact__title-accent"><?php echo esc_html( $settings['title_accent'] ); ?></span>
									<?php endif; ?>
								</<?php echo esc_attr( $tag ); ?>>
							<?php endif; ?>
						</div>
						<?php if ( '' !== trim( (string) $settings['text'] ) ) : ?>
							<p class="avix-impact__text"><?php echo esc_html( $settings['text'] ); ?></p>
						<?php endif; ?>
					</header>
				<?php endif; ?>

				<?php if ( $metrics ) : ?>
					<ul class="avix-impact__grid" role="list">
						<?php foreach ( $metrics as $index => $metric ) : ?>
							<?php $this->render_metric( $settings, $metric, $index ); ?>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}

	private function render_metric( array $settings, array $metric, $index ) {
		$raw       = trim( (string) ( $metric['value'] ?? '' ) );
		$number    = str_replace( array( ',', ' ' ), '', $raw );
		$numeric   = is_numeric( $number );
		$decimals  = $numeric && false !== strpos( $number, '.' ) ? strlen( substr( strrchr( $number, '.' ), 1 ) ) : 0;
		$separator = (string) $settings['thousands'];
		$display   = $numeric ? $this->format( (float) $number, $decimals, $separator ) : $raw;
		$prefix    = (string) ( $metric['prefix'] ?? '' );
		$suffix    = (string) ( $metric['suffix'] ?? '' );
		$small     = 'small' === ( $metric['suffix_size'] ?? '' ) ? ' avix-impact__affix--small' : '';
		$label     = trim( (string) ( $metric['label'] ?? '' ) );
		$spoken    = trim( $prefix . $display . $suffix );
		?>
		<li class="avix-impact__item" style="--ai-i:<?php echo (int) $index; ?>;">
			<?php if ( 'yes' === $settings['show_index'] && 'editorial' === $settings['layout'] ) : ?>
				<span class="avix-impact__index" aria-hidden="true"><?php echo esc_html( sprintf( '%02d', $index + 1 ) ); ?></span>
			<?php endif; ?>
			<p class="avix-impact__value" aria-label="<?php echo esc_attr( $spoken ); ?>">
				<?php if ( '' !== $prefix ) : ?>
					<span class="avix-impact__affix avix-impact__affix--prefix" aria-hidden="true"><?php echo esc_html( $prefix ); ?></span>
				<?php endif; ?>
				<?php if ( $numeric ) : ?>
					<span class="avix-impact__num" aria-hidden="true" data-to="<?php echo esc_attr( $number ); ?>" data-decimals="<?php echo (int) $decimals; ?>" data-separator="<?php echo esc_attr( $separator ); ?>"><?php echo esc_html( $display ); ?></span>
				<?php else : ?>
					<span class="avix-impact__num" aria-hidden="true"><?php echo esc_html( $raw ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== $suffix ) : ?>
					<span class="avix-impact__affix avix-impact__affix--suffix<?php echo esc_attr( $small ); ?>" aria-hidden="true"><?php echo esc_html( $suffix ); ?></span>
				<?php endif; ?>
			</p>
			<?php if ( '' !== $label ) : ?>
				<p class="avix-impact__label"><?php echo esc_html( $label ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== trim( (string) ( $metric['description'] ?? '' ) ) ) : ?>
				<p class="avix-impact__desc"><?php echo esc_html( $metric['description'] ); ?></p>
			<?php endif; ?>
		</li>
		<?php
	}

	private function format( $value, $decimals, $separator ) {
		return number_format( $value, $decimals, '.', $separator );
	}
}
