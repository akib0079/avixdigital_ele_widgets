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
			'icon',
			array(
				'label'   => esc_html__( 'Icon', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => array(
					'auto'     => esc_html__( 'Auto (matches the label)', 'avix-widgets' ),
					'users'    => esc_html__( 'Clients', 'avix-widgets' ),
					'rocket'   => esc_html__( 'Launches / projects', 'avix-widgets' ),
					'review'   => esc_html__( 'Reviews', 'avix-widgets' ),
					'globe'    => esc_html__( 'Countries / globe', 'avix-widgets' ),
					'star'     => esc_html__( 'Rating star', 'avix-widgets' ),
					'trophy'   => esc_html__( 'Awards', 'avix-widgets' ),
					'calendar' => esc_html__( 'Years / calendar', 'avix-widgets' ),
					'code'     => esc_html__( 'Code / web apps', 'avix-widgets' ),
					'bag'      => esc_html__( 'Stores / sales', 'avix-widgets' ),
					'chart'    => esc_html__( 'Growth', 'avix-widgets' ),
					'heart'    => esc_html__( 'Happy clients', 'avix-widgets' ),
					'shield'   => esc_html__( 'Verified / trust', 'avix-widgets' ),
					'clock'    => esc_html__( 'Time / support', 'avix-widgets' ),
					'custom'   => esc_html__( 'Custom icon…', 'avix-widgets' ),
					'none'     => esc_html__( 'No icon', 'avix-widgets' ),
				),
			)
		);

		$repeater->add_control(
			'icon_custom',
			array(
				'label'     => esc_html__( 'Custom icon', 'avix-widgets' ),
				'type'      => Controls_Manager::ICONS,
				'default'   => array(
					'value'   => 'fas fa-star',
					'library' => 'fa-solid',
				),
				'condition' => array( 'icon' => 'custom' ),
			)
		);

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
			'show_icons',
			array(
				'label'     => esc_html__( 'Show icons', 'avix-widgets' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'icon_style',
			array(
				'label'     => esc_html__( 'Icon style', 'avix-widgets' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'tile',
				'options'   => array(
					'tile'  => esc_html__( 'Soft tile', 'avix-widgets' ),
					'plain' => esc_html__( 'Plain', 'avix-widgets' ),
				),
				'condition' => array( 'show_icons' => 'yes' ),
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
			'icon_color',
			array(
				'label'     => esc_html__( 'Icon lines', 'avix-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .avix-impact' => '--ai-icon-ink: {{VALUE}};' ),
				'condition' => array( 'show_icons' => 'yes' ),
			)
		);

		$this->add_control(
			'icon_bg',
			array(
				'label'     => esc_html__( 'Icon tile', 'avix-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .avix-impact' => '--ai-icon-bg: {{VALUE}};' ),
				'condition' => array(
					'show_icons' => 'yes',
					'icon_style' => 'tile',
				),
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
		if ( 'yes' === $settings['show_icons'] ) {
			$classes[] = 'avix-impact--icon-' . ( 'plain' === $settings['icon_style'] ? 'plain' : 'tile' );
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
			<?php $this->render_icon( $settings, $metric, $index, $label ); ?>
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

	private function render_icon( array $settings, array $metric, $index, $label ) {
		if ( 'yes' !== $settings['show_icons'] ) {
			return;
		}
		$choice = (string) ( $metric['icon'] ?? 'auto' );
		if ( 'none' === $choice ) {
			return;
		}
		if ( 'custom' === $choice ) {
			if ( empty( $metric['icon_custom']['value'] ) ) {
				return;
			}
			echo '<span class="avix-impact__icon avix-impact__icon--custom" aria-hidden="true">';
			\Elementor\Icons_Manager::render_icon( $metric['icon_custom'], array( 'aria-hidden' => 'true' ) );
			echo '</span>';
			return;
		}

		$icons = self::icons();
		$name  = isset( $icons[ $choice ] ) ? $choice : $this->guess_icon( $label, $index );
		// Every stroke gets pathLength="1" so it can draw itself in on reveal.
		$markup = preg_replace( '/<(path|circle|rect)(?![^>]*class="af)/', '<$1 pathLength="1"', $icons[ $name ] );
		?>
		<span class="avix-impact__icon avix-impact__icon--<?php echo esc_attr( $name ); ?>" aria-hidden="true">
			<svg viewBox="0 0 24 24" focusable="false"><?php echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup from self::icons(). ?></svg>
		</span>
		<?php
	}

	/**
	 * Picks an icon from the label's wording, so existing numbers get a
	 * fitting icon without being edited.
	 */
	private function guess_icon( $label, $index ) {
		$rules = array(
			'review'   => 'review|rating|testimonial|feedback',
			'users'    => 'client|customer|brand|partner|people|user',
			'globe'    => 'countr|world|global|nation|continent|cit(y|ies)',
			'rocket'   => 'project|deliver|launch|website|site|store|build|ship',
			'calendar' => 'year|experience|since|decade',
			'trophy'   => 'award|winner|trophy',
			'bag'      => 'sale|revenue|order|shop|commerce',
			'chart'    => 'growth|conversion|increase|roi|traffic',
			'heart'    => 'happy|love|satisf|smile',
			'code'     => 'code|app|develop',
			'shield'   => 'verified|secure|guarantee|trust',
			'clock'    => 'hour|support|time|response',
		);
		foreach ( $rules as $icon => $pattern ) {
			if ( preg_match( '/' . $pattern . '/i', $label ) ) {
				return $icon;
			}
		}
		$cycle = array( 'users', 'rocket', 'review', 'globe' );
		return $cycle[ $index % count( $cycle ) ];
	}

	/**
	 * Avix duotone line icons on a 24px grid: ink strokes, orange details
	 * (class "a" = orange stroke, "af" = orange fill).
	 */
	private static function icons() {
		return array(
			'users'    => '<circle cx="9" cy="8" r="3.25"/><path d="M3.25 19.25c0-3.3 2.6-5.75 5.75-5.75s5.75 2.45 5.75 5.75"/><circle class="a" cx="16.75" cy="8.75" r="2.5"/><path class="a" d="M16.25 13.6c.17-.02.33-.03.5-.03 2.3 0 4 1.9 4 4.43"/>',
			'rocket'   => '<path d="M12 2.75c2.9 2.05 4.4 5.1 4.4 8.85v4.65H7.6V11.6c0-3.75 1.5-6.8 4.4-8.85Z"/><circle cx="12" cy="9.75" r="1.75"/><path d="M7.6 12.6 5 14.9v3.35l2.6-1.35M16.4 12.6l2.6 2.3v3.35l-2.6-1.35"/><path class="a" d="M10.25 18.75c.35 1.2.95 2 1.75 2.5.8-.5 1.4-1.3 1.75-2.5"/>',
			'review'   => '<path d="M20 13.5a2.5 2.5 0 0 1-2.5 2.5H10l-4.25 3.5c-.33.27-.75.04-.75-.38V6.5A2.5 2.5 0 0 1 7.5 4h10A2.5 2.5 0 0 1 20 6.5Z"/><path class="af" d="m12.5 6.8.85 2.23 2.38.12-1.85 1.5.62 2.3-2-1.3-2 1.3.62-2.3-1.85-1.5 2.38-.12Z"/>',
			'globe'    => '<circle cx="12" cy="12" r="8.75"/><path d="M3.5 9.5h17M3.5 14.5h17"/><path d="M12 3.25c-2.35 2.4-3.5 5.3-3.5 8.75s1.15 6.35 3.5 8.75M12 3.25c2.35 2.4 3.5 5.3 3.5 8.75s-1.15 6.35-3.5 8.75"/><circle class="af pin" cx="17.25" cy="7.25" r="2.4"/>',
			'star'     => '<path d="M12 3.4 14.38 9.12 20.56 9.62 15.85 13.65 17.29 19.68 12 16.45 6.71 19.68 8.15 13.65 3.44 9.62 9.62 9.12Z"/><path class="a" d="M20 2.5v3M18.5 4h3"/>',
			'trophy'   => '<path d="M7.5 4h9v5.25a4.5 4.5 0 0 1-9 0Z"/><path d="M7.5 5.75H5.25a.75.75 0 0 0-.75.75c0 2.1 1.35 3.75 3.25 4M16.5 5.75h2.25a.75.75 0 0 1 .75.75c0 2.1-1.35 3.75-3.25 4"/><path d="M12 13.75V17M8 20.25h8M9.25 20.25c.2-1.8 1.3-3.25 2.75-3.25s2.55 1.45 2.75 3.25"/><path class="af" d="m12 5.9.62 1.26 1.38.2-1 .98.24 1.38L12 9.07l-1.24.65.24-1.38-1-.98 1.38-.2Z"/>',
			'calendar' => '<rect x="3.75" y="5" width="16.5" height="15.25" rx="2.5"/><path d="M3.75 9.75h16.5M8 3v3.5M16 3v3.5"/><path class="a" d="m9 14.75 2 2 4-4"/>',
			'code'     => '<rect x="3" y="4.5" width="18" height="15" rx="2.75"/><path class="a" d="m9.25 9.75-2.5 2.5 2.5 2.5M14.75 9.75l2.5 2.5-2.5 2.5"/><path d="m12.75 9.25-1.5 6"/>',
			'bag'      => '<path d="M5.5 8.25h13l-1 11.05a1.5 1.5 0 0 1-1.5 1.35H8a1.5 1.5 0 0 1-1.5-1.35Z"/><path d="M9 10.5V7a3 3 0 0 1 6 0v3.5"/><path class="a" d="M9.5 15c.6.9 1.5 1.35 2.5 1.35s1.9-.45 2.5-1.35"/>',
			'chart'    => '<path d="M4 4v14.5A1.5 1.5 0 0 0 5.5 20H20"/><path class="a" d="m7.5 15 3.5-3.75 3 2.5 5-5.75"/><path class="a" d="M15.75 8H19v3.25"/>',
			'heart'    => '<path d="M12 20s-7.5-4.35-7.5-10.1A4.15 4.15 0 0 1 8.65 5.75c1.45 0 2.65.7 3.35 1.85.7-1.15 1.9-1.85 3.35-1.85a4.15 4.15 0 0 1 4.15 4.15C19.5 15.65 12 20 12 20Z"/><path class="a" d="M7.75 9.5a1.9 1.9 0 0 1 1.5-1.5"/>',
			'shield'   => '<path d="M12 3 5 5.75v5.5c0 4.4 2.95 8.15 7 9.75 4.05-1.6 7-5.35 7-9.75v-5.5Z"/><path class="a" d="m8.75 12.25 2.25 2.25 4.25-4.5"/>',
			'clock'    => '<circle cx="12" cy="12" r="8.75"/><path class="a" d="M12 7.25V12l3.25 2"/>',
		);
	}

	private function format( $value, $decimals, $separator ) {
		return number_format( $value, $decimals, '.', $separator );
	}
}
