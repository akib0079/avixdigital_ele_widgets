<?php
/**
 * Compare & CEO Quote: a compact "typical agency vs Avix" face-off and the
 * CEO quote card. Pixel Akib flies between them on rocket boots: it zaps the
 * typical-agency rows, cheers the Avix side and perches on the real photo.
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

class Compare_Quote extends Widget_Base {

	use Media;

	const PHOTO = 'https://avixdigital.com/wp-content/uploads/2026/06/Untitled-design2-1024x1024.gif';

	/** The Avix mark (60×60), shown in the Avix column heading. */
	const MARK = 'M0.000609729 33.4786V24.609H15.5435C15.9776 24.609 16.3249 24.522 16.6724 24.2613L0.608473 8.17411L1.91078 6.78291L8.16259 0.522042L24.3131 16.6963C24.4868 16.3485 24.6603 15.9135 24.6603 15.5658V15.4788V0H26.5705H33.4301H35.3403V15.4785V15.5655C35.3403 15.9132 35.5141 16.3479 35.6875 16.696L51.838 0.521739L58.0898 6.78261L59.3921 8.17381L43.3282 24.261C43.6754 24.5217 44.023 24.6087 44.4571 24.6087H60V33.4783V35.3913H44.4571C41.0705 35.3913 38.0315 34.0871 35.6872 31.9131L34.819 32.7826L32.7351 34.8695L31.8669 35.739C34.0377 37.9999 35.34 41.1304 35.34 44.5215V60H33.4298H24.66V44.5221C24.66 44.0874 24.4862 43.6527 24.3128 43.3046L8.16228 59.4789L6.85967 58.0877L0.607873 51.8268L16.6718 35.7396C16.3246 35.5656 15.977 35.3919 15.5429 35.3919H0V33.4786H0.000609729Z';

	public function get_name(): string {
		return 'avix-compare-quote';
	}

	public function get_title(): string {
		return esc_html__( 'Compare & CEO Quote', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-blockquote';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'compare', 'comparison', 'versus', 'vs', 'quote', 'ceo', 'founder', 'testimonial', 'avatar', 'avix' );
	}

	public function get_style_depends(): array {
		return array( 'avix-compare-quote' );
	}

	public function get_script_depends(): array {
		return array( 'avix-compare-quote' );
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/* ------------------------------------------------------------------ */
	/* Controls                                                            */
	/* ------------------------------------------------------------------ */

	protected function register_controls(): void {
		$this->controls_header();
		$this->controls_compare();
		$this->controls_quote();
		$this->controls_buddy();
		$this->controls_style();
	}

	private function controls_header() {
		$this->start_controls_section( 'section_header', array( 'label' => esc_html__( 'Header', 'avix-widgets' ) ) );

		$this->add_control(
			'tag',
			array(
				'label'   => esc_html__( 'Tag', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Why Avix', 'avix-widgets' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Title', 'avix-widgets' ),
				'description' => esc_html__( 'Wrap words in [square brackets] to colour them with the accent. Enter starts a new line.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => "Same budget.\n[Better outcome.]",
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
					'h2'  => 'H2',
					'h3'  => 'H3',
					'div' => 'div',
				),
			)
		);

		$this->end_controls_section();
	}

	private function controls_compare() {
		$this->start_controls_section( 'section_compare', array( 'label' => esc_html__( 'Comparison', 'avix-widgets' ) ) );

		$this->add_control(
			'show_compare',
			array(
				'label'   => esc_html__( 'Show the comparison', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'them_label',
			array(
				'label'     => esc_html__( 'Left column', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'The typical agency', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_compare' => 'yes' ),
			)
		);

		$this->add_control(
			'us_label',
			array(
				'label'     => esc_html__( 'Right column', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => 'Avix Digital',
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_compare' => 'yes' ),
			)
		);

		$this->add_control(
			'us_mark',
			array(
				'label'     => esc_html__( 'Avix mark in the heading', 'avix-widgets' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'show_compare' => 'yes' ),
			)
		);

		$rows = new Repeater();
		$rows->add_control(
			'them',
			array(
				'label'       => esc_html__( 'Typical agency', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);
		$rows->add_control(
			'us',
			array(
				'label'       => esc_html__( 'Avix', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'rows',
			array(
				'label'       => esc_html__( 'Rows', 'avix-widgets' ),
				'description' => esc_html__( 'Each row faces off one habit against yours. Short lines (under ~45 characters) keep it compact; 3–5 rows read best.', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $rows->get_controls(),
				'title_field' => '{{{ us }}}',
				'default'     => array(
					array(
						'them' => 'Juniors take over after the sales call',
						'us'   => 'The people you meet build your project',
					),
					array(
						'them' => 'Templates dressed up as custom',
						'us'   => 'Custom design built around your brand',
					),
					array(
						'them' => 'Slow, plugin-heavy builds',
						'us'   => 'Fast, clean code built to convert',
					),
					array(
						'them' => 'Gone quiet after launch',
						'us'   => 'Support and optimisation after launch',
					),
				),
				'condition'   => array( 'show_compare' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_quote() {
		$this->start_controls_section( 'section_quote', array( 'label' => esc_html__( 'CEO Quote', 'avix-widgets' ) ) );

		$this->add_control(
			'show_quote',
			array(
				'label'   => esc_html__( 'Show the quote', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'photo',
			array(
				'label'       => esc_html__( 'Photo', 'avix-widgets' ),
				'description' => esc_html__( 'Square works best. Animated GIFs keep playing (they’re never swapped for a resized, still copy).', 'avix-widgets' ),
				'type'        => Controls_Manager::MEDIA,
				'dynamic'     => array( 'active' => true ),
				'default'     => array( 'url' => self::PHOTO ),
				'condition'   => array( 'show_quote' => 'yes' ),
			)
		);

		$this->add_control(
			'video',
			array(
				'label'       => esc_html__( 'Looping video (optional)', 'avix-widgets' ),
				'description' => esc_html__( 'An MP4/WebM of the same loop is usually 5–10× lighter than a GIF. It plays only while on screen; the photo is its poster.', 'avix-widgets' ),
				'type'        => Controls_Manager::URL,
				'dynamic'     => array( 'active' => true ),
				'options'     => false,
				'condition'   => array( 'show_quote' => 'yes' ),
			)
		);

		$this->add_control(
			'quote',
			array(
				'label'       => esc_html__( 'Quote', 'avix-widgets' ),
				'description' => esc_html__( '[Square brackets] colour words with the accent.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 4,
				'default'     => 'The gap between a good idea and a market-leading business is execution. At Avix Digital, we bridge that gap with [pixel-perfect design] and future-ready engineering.',
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_quote' => 'yes' ),
			)
		);

		$this->add_control(
			'name',
			array(
				'label'     => esc_html__( 'Name', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => 'Akib Zawayed',
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_quote' => 'yes' ),
			)
		);

		$this->add_control(
			'role',
			array(
				'label'     => esc_html__( 'Role', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => 'CEO, Avix Digital',
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_quote' => 'yes' ),
			)
		);

		$this->add_control(
			'profile',
			array(
				'label'       => esc_html__( 'Profile link', 'avix-widgets' ),
				'description' => esc_html__( 'LinkedIn or About page. Makes the name a link and is added to the structured data.', 'avix-widgets' ),
				'type'        => Controls_Manager::URL,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_quote' => 'yes' ),
			)
		);

		$this->add_control(
			'schema',
			array(
				'label'       => esc_html__( 'Person structured data', 'avix-widgets' ),
				'description' => esc_html__( 'JSON-LD for the CEO: name, role, photo, profile, employer. Shares its id with the Process Timeline team row, so search engines see one person.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
				'condition'   => array( 'show_quote' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_buddy() {
		$this->start_controls_section( 'section_buddy', array( 'label' => esc_html__( 'Pixel Akib', 'avix-widgets' ) ) );

		$this->add_control(
			'show_buddy',
			array(
				'label'       => esc_html__( 'Show the pixel character', 'avix-widgets' ),
				'description' => esc_html__( 'Sits on the VS badge. Hover a row and it rockets over, zaps the typical-agency habit and cheers the Avix side; scroll down and it lands on the photo. Click it for a line.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'hint_hover',
			array(
				'label'     => esc_html__( 'Hint (mouse)', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Hover a row. I’ll fix it.', 'avix-widgets' ),
				'condition' => array( 'show_buddy' => 'yes' ),
			)
		);

		$this->add_control(
			'hint_touch',
			array(
				'label'     => esc_html__( 'Hint (touch)', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Tap a row. I’ll fix it.', 'avix-widgets' ),
				'condition' => array( 'show_buddy' => 'yes' ),
			)
		);

		$this->add_control(
			'done_line',
			array(
				'label'     => esc_html__( 'When every row is fixed', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'All fixed. Same budget, zero compromises.', 'avix-widgets' ),
				'condition' => array( 'show_buddy' => 'yes' ),
			)
		);

		$this->add_control(
			'perch_line',
			array(
				'label'     => esc_html__( 'On the photo', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'That’s me! The high-res version.', 'avix-widgets' ),
				'condition' => array( 'show_buddy' => 'yes' ),
			)
		);

		$this->add_control(
			'lines',
			array(
				'label'       => esc_html__( 'Lines when clicked', 'avix-widgets' ),
				'description' => esc_html__( 'One per line, used in order. Every fourth click it does a flip.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 5,
				'default'     => "Hi again! Pixel Akib here 👋\nFewer pixels, same ideas.\nRocket boots: 10/10, would fly again.\nWhee! Okay, one more.\nWant the real me? The audit’s on us.",
				'condition'   => array( 'show_buddy' => 'yes' ),
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
			'cq_bg'     => array( esc_html__( 'Background', 'avix-widgets' ), '--cq-bg' ),
			'cq_accent' => array( esc_html__( 'Accent (Avix column, character)', 'avix-widgets' ), '--cq-accent' ),
			'cq_ink'    => array( esc_html__( 'Headings', 'avix-widgets' ), '--cq-ink' ),
			'cq_muted'  => array( esc_html__( 'Muted text', 'avix-widgets' ), '--cq-muted' ),
			'cq_line'   => array( esc_html__( 'Lines', 'avix-widgets' ), '--cq-line' ),
		);
		foreach ( $colors as $key => $color ) {
			$this->add_control(
				$key,
				array(
					'label'     => $color[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-cq' => $color[1] . ': {{VALUE}};' ),
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
			'warm_card',
			array(
				'label'   => esc_html__( 'Warm glow on the quote card', 'avix-widgets' ),
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
				'selectors'          => array( '{{WRAPPER}} .avix-cq' => 'padding-top: {{TOP}}{{UNIT}}; padding-bottom: {{BOTTOM}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'max_width',
			array(
				'label'      => esc_html__( 'Content width', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 640, 'max' => 1400 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-cq' => '--cq-max: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'buddy_size',
			array(
				'label'      => esc_html__( 'Character size', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 18, 'max' => 56 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-cq' => '--cq-buddy-w: {{SIZE}}{{UNIT}};' ),
				'condition'  => array( 'show_buddy' => 'yes' ),
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

		$type = array(
			'title_typography' => array( esc_html__( 'Title', 'avix-widgets' ), '.avix-cq__title' ),
			'row_typography'   => array( esc_html__( 'Rows', 'avix-widgets' ), '.avix-cq__row' ),
			'quote_typography' => array( esc_html__( 'Quote', 'avix-widgets' ), '.avix-cq__words' ),
		);
		foreach ( $type as $name => $group ) {
			$this->add_group_control(
				Group_Control_Typography::get_type(),
				array(
					'name'     => $name,
					'label'    => $group[0],
					'selector' => '{{WRAPPER}} .avix-cq ' . $group[1],
				)
			);
		}

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ */
	/* Render                                                              */
	/* ------------------------------------------------------------------ */

	protected function render(): void {
		$s    = $this->get_settings_for_display();
		$rows = array();
		if ( 'yes' === $s['show_compare'] ) {
			foreach ( (array) $s['rows'] as $row ) {
				$them = trim( (string) ( $row['them'] ?? '' ) );
				$us   = trim( (string) ( $row['us'] ?? '' ) );
				if ( '' !== $them || '' !== $us ) {
					$rows[] = array(
						'them' => $them,
						'us'   => $us,
					);
				}
			}
		}
		$quote = 'yes' === $s['show_quote'] && '' !== trim( (string) $s['quote'] );

		if ( ! $rows && ! $quote ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="elementor-alert elementor-alert-info">' . esc_html__( 'Compare & CEO Quote: add a comparison row or a quote.', 'avix-widgets' ) . '</div>';
			}
			return;
		}

		$id       = $this->get_id();
		$title    = trim( (string) $s['title'] );
		$title_id = 'avix-cq-title-' . $id;
		$buddy    = 'yes' === $s['show_buddy'];

		$classes = array( 'avix-cq' );
		if ( 'yes' !== $s['show_grid'] ) {
			$classes[] = 'avix-cq--no-grid';
		}
		if ( 'yes' !== $s['warm_card'] ) {
			$classes[] = 'avix-cq--cool';
		}
		$this->add_render_attribute(
			'root',
			array(
				'class'        => $classes,
				'data-avix-cq' => '',
			)
		);
		if ( '' !== $title ) {
			$this->add_render_attribute( 'root', 'aria-labelledby', $title_id );
		}
		if ( $buddy ) {
			$this->add_render_attribute(
				'root',
				'data-cq-say',
				wp_json_encode(
					array(
						'hover' => trim( (string) $s['hint_hover'] ),
						'touch' => trim( (string) $s['hint_touch'] ),
						'done'  => trim( (string) $s['done_line'] ),
						'perch' => trim( (string) $s['perch_line'] ),
						'lines' => $this->lines( (string) $s['lines'] ),
					)
				)
			);
		}
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="avix-cq__frame" data-cq-frame>
				<?php if ( '' !== trim( (string) $s['tag'] ) || '' !== $title ) : ?>
					<header class="avix-cq__head" data-cq-reveal>
						<?php if ( '' !== trim( (string) $s['tag'] ) ) : ?>
							<p class="avix-cq__tag"><b>/</b> <?php echo esc_html( $s['tag'] ); ?></p>
						<?php endif; ?>
						<?php if ( '' !== $title ) : ?>
							<<?php echo esc_attr( Utils::validate_html_tag( $s['title_tag'] ) ); ?> class="avix-cq__title" id="<?php echo esc_attr( $title_id ); ?>"><?php echo $this->accent_html( $title, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in accent_html(). ?></<?php echo esc_attr( Utils::validate_html_tag( $s['title_tag'] ) ); ?>>
						<?php endif; ?>
					</header>
				<?php endif; ?>

				<?php
				if ( $rows ) {
					$this->render_compare( $s, $rows );
				}
				if ( $quote ) {
					$this->render_quote( $s );
				}
				if ( $buddy ) {
					$this->render_buddy();
				}
				?>
			</div>
		</section>
		<?php
		if ( $quote && 'yes' === $s['schema'] ) {
			$this->print_schema( $s );
		}
	}

	private function render_compare( array $s, array $rows ) {
		$them = trim( (string) $s['them_label'] );
		$us   = trim( (string) $s['us_label'] );
		?>
		<div class="avix-cq__compare" data-cq-compare data-cq-reveal style="--cq-delay: 80ms;">
			<div class="avix-cq__heads" aria-hidden="true">
				<span class="avix-cq__pill avix-cq__pill--them"><?php echo esc_html( $them ); ?></span>
				<span class="avix-cq__vs" data-cq-seat><span>VS</span></span>
				<span class="avix-cq__pill avix-cq__pill--us">
					<?php if ( 'yes' === $s['us_mark'] ) : ?>
						<svg viewBox="0 0 60 60" focusable="false"><path d="<?php echo esc_attr( self::MARK ); ?>"/></svg>
					<?php endif; ?>
					<?php echo esc_html( $us ); ?>
				</span>
			</div>
			<ul class="avix-cq__rows" data-cq-rows>
				<?php foreach ( $rows as $i => $row ) : ?>
					<li class="avix-cq__row" data-cq-row style="--cq-i: <?php echo (int) $i; ?>;">
						<p class="avix-cq__them">
							<span class="avix-cq__sr"><?php echo esc_html( $them ); ?>: </span>
							<span class="avix-cq__txt"><?php echo esc_html( $row['them'] ); ?></span>
							<span class="avix-cq__icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M6 6l12 12M18 6 6 18"/></svg></span>
						</p>
						<p class="avix-cq__us">
							<span class="avix-cq__icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg></span>
							<span class="avix-cq__sr"><?php echo esc_html( $us ); ?>: </span>
							<span class="avix-cq__txt"><?php echo esc_html( $row['us'] ); ?></span>
						</p>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	private function render_quote( array $s ) {
		$name    = trim( (string) $s['name'] );
		$role    = trim( (string) $s['role'] );
		$profile = (array) $s['profile'];
		$linked  = '' !== $name && '' !== esc_url( $profile['url'] ?? '' );
		if ( $linked ) {
			$this->add_link_attributes( 'profile', $profile );
			$this->add_render_attribute( 'profile', 'class', 'avix-cq__name-link' );
		}
		$media = $this->media_html( $s, implode( ', ', array_filter( array( $name, $role ), 'strlen' ) ) );
		?>
		<div class="avix-cq__quote<?php echo '' === $media ? ' avix-cq__quote--text' : ''; ?>" data-cq-quote data-cq-reveal style="--cq-delay: 120ms;">
			<?php if ( '' !== $media ) : ?>
				<div class="avix-cq__photo" data-cq-perch>
					<?php echo $media; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in media_html(). ?>
				</div>
			<?php endif; ?>
			<div class="avix-cq__body">
				<blockquote class="avix-cq__words is-style-plain has-background">
					<p><?php echo $this->accent_html( (string) $s['quote'], false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in accent_html(). ?></p>
				</blockquote>
				<?php if ( '' !== $name || '' !== $role ) : ?>
					<div class="avix-cq__by">
						<?php if ( '' !== $name ) : ?>
							<cite class="avix-cq__name">
								<?php if ( $linked ) : ?>
									<a <?php $this->print_render_attribute_string( 'profile' ); ?>><?php echo esc_html( $name ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $name ); ?>
								<?php endif; ?>
							</cite>
						<?php endif; ?>
						<?php if ( '' !== $name && '' !== $role ) : ?>
							<span class="avix-cq__dot" aria-hidden="true"></span>
						<?php endif; ?>
						<?php if ( '' !== $role ) : ?>
							<span class="avix-cq__role"><?php echo esc_html( $role ); ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Photo (GIFs untouched so they keep animating), or a looping video that
	 * uses the photo as its poster.
	 */
	private function media_html( array $s, $alt ) {
		$photo = (array) $s['photo'];
		$url   = (string) ( $photo['url'] ?? '' );
		$video = esc_url( $s['video']['url'] ?? '' );
		$id    = $this->media_id( $photo );
		$gif   = (bool) preg_match( '/\.gif(\?.*)?$/i', $url );

		if ( '' !== $video ) {
			$poster = '' !== $url ? $url : ( $id ? (string) wp_get_attachment_image_url( $id, 'medium_large' ) : '' );
			return sprintf(
				'<video class="avix-cq__media" data-cq-video muted loop playsinline preload="none"%s aria-label="%s"><source src="%s"></video>',
				'' !== $poster ? ' poster="' . esc_url( $poster ) . '"' : '',
				esc_attr( $alt ),
				$video
			);
		}
		if ( $id && ! $gif ) {
			$html = wp_get_attachment_image(
				$id,
				'medium_large',
				false,
				array(
					'class'    => 'avix-cq__media',
					'alt'      => $alt,
					'loading'  => 'lazy',
					'decoding' => 'async',
					'sizes'    => '(max-width: 720px) 132px, 220px',
				)
			);
			if ( $html ) {
				return $html;
			}
		}
		if ( '' === $url ) {
			return '';
		}
		return sprintf( '<img class="avix-cq__media" src="%s" alt="%s" width="440" height="440" loading="lazy" decoding="async">', esc_url( $url ), esc_attr( $alt ) );
	}

	private function render_buddy() {
		?>
		<div class="avix-cq__buddy" data-cq-buddy>
			<span class="avix-cq__puffs" aria-hidden="true"><i></i><i></i><i></i></span>
			<button type="button" class="avix-cq__hit" data-cq-hit aria-label="<?php esc_attr_e( 'Say hi to Pixel Akib', 'avix-widgets' ); ?>">
				<span class="avix-cq__bob" data-cq-bob>
					<svg class="avix-cq__sprite" viewBox="0 0 10 12" focusable="false" aria-hidden="true">
						<rect class="avix-cq__jet avix-cq__jet--l" x="2" y="12" width="2" height="3"/>
						<rect class="avix-cq__jet avix-cq__jet--r" x="6" y="12" width="2" height="3"/>
						<rect class="avix-cq__core avix-cq__core--l" x="2.5" y="12" width="1" height="1.6"/>
						<rect class="avix-cq__core avix-cq__core--r" x="6.5" y="12" width="1" height="1.6"/>
						<g class="avix-cq__b-leg avix-cq__b-leg--l"><rect x="2" y="7" width="2" height="4"/><rect class="avix-cq__boot" x="1.5" y="10.5" width="3" height="1.5"/></g>
						<g class="avix-cq__b-leg avix-cq__b-leg--r"><rect x="6" y="7" width="2" height="4"/><rect class="avix-cq__boot" x="5.5" y="10.5" width="3" height="1.5"/></g>
						<rect class="avix-cq__b-body" x="2" y="3" width="6" height="4"/>
						<rect class="avix-cq__b-head" x="3" y="0" width="4" height="3"/>
						<rect class="avix-cq__b-eye" x="5" y="1" width="2" height="1"/>
						<rect class="avix-cq__b-arm avix-cq__b-arm--l" x="0" y="3" width="2" height="3"/>
						<rect class="avix-cq__b-arm avix-cq__b-arm--r" x="8" y="3" width="2" height="3"/>
						<rect class="avix-cq__b-point" x="8" y="3" width="4" height="2"/>
					</svg>
				</span>
			</button>
			<span class="avix-cq__bubble" data-cq-bubble role="status" aria-live="polite"></span>
		</div>
		<?php
	}

	/* ------------------------------------------------------------------ */
	/* Helpers                                                             */
	/* ------------------------------------------------------------------ */

	/**
	 * Escaped text with [words] in the accent span; optional line breaks.
	 */
	private function accent_html( $text, $breaks ) {
		$lines = preg_split( '/\R/', trim( (string) $text ) );
		$out   = array();
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' !== $line ) {
				$out[] = preg_replace( '/\[([^\[\]]+)\]/', '<span class="avix-cq__accent">$1</span>', esc_html( $line ) );
			}
		}
		return implode( $breaks ? '<br>' : ' ', $out );
	}

	private function lines( $text ) {
		$lines = array();
		foreach ( preg_split( '/\R/', $text ) as $line ) {
			$line = trim( wp_strip_all_tags( $line ) );
			if ( '' !== $line ) {
				$lines[] = $line;
			}
		}
		return array_slice( $lines, 0, 20 );
	}

	/**
	 * schema.org Person for the CEO. The @id matches the Process Timeline
	 * team row, so both describe one entity.
	 */
	private function print_schema( array $s ) {
		$name = wp_strip_all_tags( trim( (string) $s['name'] ) );
		if ( '' === $name ) {
			return;
		}
		$slug   = sanitize_title( $name );
		$person = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Person',
			'name'     => $name,
			'worksFor' => array(
				'@type' => 'Organization',
				'name'  => wp_strip_all_tags( get_bloginfo( 'name' ) ),
				'url'   => home_url( '/' ),
			),
		);
		if ( '' !== $slug ) {
			$person['@id'] = home_url( '/#person-' . $slug );
		}
		$role = wp_strip_all_tags( trim( (string) $s['role'] ) );
		if ( '' !== $role ) {
			$person['jobTitle'] = $role;
		}
		$photo = (array) $s['photo'];
		$image = ! empty( $photo['url'] ) ? $photo['url'] : ( $this->media_id( $photo ) ? wp_get_attachment_image_url( $this->media_id( $photo ), 'medium_large' ) : '' );
		if ( $image ) {
			$person['image'] = esc_url_raw( $image );
		}
		$link = trim( (string) ( $s['profile']['url'] ?? '' ) );
		if ( '' !== $link && '/' === $link[0] && ( ! isset( $link[1] ) || '/' !== $link[1] ) ) {
			$link = home_url( $link );
		}
		$link = esc_url_raw( $link, array( 'http', 'https' ) );
		if ( '' !== $link ) {
			$person[ wp_parse_url( $link, PHP_URL_HOST ) === wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ? 'url' : 'sameAs' ] = $link;
		}
		$json = wp_json_encode( $person, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP );
		if ( $json ) {
			echo '<script type="application/ld+json">' . $json . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON with < > & hex-escaped.
		}
	}
}
