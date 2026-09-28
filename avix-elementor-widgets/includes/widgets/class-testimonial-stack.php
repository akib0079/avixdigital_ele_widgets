<?php
/**
 * Testimonial Stack: verified client reviews dealt onto a card pile.
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

class Testimonial_Stack extends Widget_Base {

	const FIVERR_URL = 'https://www.fiverr.com/akib0079';
	const UPWORK_URL = 'https://www.upwork.com/freelancers/akibzawayed';

	/**
	 * Review platforms. Upwork mark from Simple Icons (CC0); Fiverr uses its "fi" roundel.
	 */
	const PLATFORMS = array(
		'fiverr' => array(
			'label' => 'Fiverr',
			'color' => '#1DBF73',
			'path'  => '',
		),
		'upwork' => array(
			'label' => 'Upwork',
			'color' => '#14A800',
			'path'  => 'M18.561 13.158c-1.102 0-2.135-.467-3.074-1.227l.228-1.076.008-.042c.207-1.143.849-3.06 2.839-3.06 1.492 0 2.703 1.212 2.703 2.703-.001 1.489-1.212 2.702-2.704 2.702zm0-8.14c-2.539 0-4.51 1.649-5.31 4.366-1.22-1.834-2.148-4.036-2.687-5.892H7.828v7.112c-.002 1.406-1.141 2.546-2.547 2.548-1.405-.002-2.543-1.143-2.545-2.548V3.492H0v7.112c0 2.914 2.37 5.303 5.281 5.303 2.913 0 5.283-2.389 5.283-5.303v-1.19c.529 1.107 1.182 2.229 1.974 3.221l-1.673 7.873h2.797l1.213-5.71c1.063.679 2.285 1.109 3.686 1.109 3 0 5.439-2.452 5.439-5.45 0-3-2.439-5.439-5.439-5.439z',
		),
	);

	public function get_name(): string {
		return 'avix-testimonial-stack';
	}

	public function get_title(): string {
		return esc_html__( 'Testimonial Stack', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-testimonial-carousel';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'testimonial', 'review', 'slider', 'carousel', 'stack', 'fiverr', 'upwork', 'avix' );
	}

	public function get_style_depends(): array {
		return array( 'avix-testimonial-stack' );
	}

	public function get_script_depends(): array {
		return array( 'avix-testimonial-stack' );
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/* ------------------------------------------------------------------ */
	/* Controls                                                            */
	/* ------------------------------------------------------------------ */

	protected function register_controls(): void {
		$this->controls_header();
		$this->controls_reviews();
		$this->controls_proof();
		$this->controls_behaviour();
		$this->controls_style();
	}

	private function controls_header() {
		$this->start_controls_section( 'section_header', array( 'label' => esc_html__( 'Header', 'avix-widgets' ) ) );

		$this->add_control(
			'eyebrow',
			array(
				'label'   => esc_html__( 'Eyebrow', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Client reviews', 'avix-widgets' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Title', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => esc_html__( 'Our work is judged by the people who hired us.', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'title_accent',
			array(
				'label'       => esc_html__( 'Highlighted words', 'avix-widgets' ),
				'description' => esc_html__( 'Added after the title in the accent colour.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Here’s what they said.', 'avix-widgets' ),
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
					'div' => 'div',
					'p'   => 'p',
				),
			)
		);

		$this->end_controls_section();
	}

	private function controls_reviews() {
		$this->start_controls_section( 'section_reviews', array( 'label' => esc_html__( 'Reviews', 'avix-widgets' ) ) );

		$this->add_control(
			'reviews_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Use real reviews, word for word, with the name exactly as shown on Fiverr or Upwork. Each card links to where it can be verified.', 'avix-widgets' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'quote',
			array(
				'label'   => esc_html__( 'Review text', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 5,
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'name',
			array(
				'label'       => esc_html__( 'Client name / username', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'location',
			array(
				'label'   => esc_html__( 'Country', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'role',
			array(
				'label'       => esc_html__( 'Role / company (optional)', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'Founder, PTS Marketing', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'platform',
			array(
				'label'   => esc_html__( 'Review source', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'fiverr',
				'options' => array(
					'fiverr'   => 'Fiverr',
					'upwork'   => 'Upwork',
					'verified' => esc_html__( 'Verified client (no platform badge)', 'avix-widgets' ),
				),
			)
		);

		$repeater->add_control(
			'source_url',
			array(
				'label'       => esc_html__( 'Review link', 'avix-widgets' ),
				'description' => esc_html__( 'Direct link to the review if you have it. Empty = your Fiverr / Upwork profile.', 'avix-widgets' ),
				'type'        => Controls_Manager::URL,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'rating',
			array(
				'label'   => esc_html__( 'Stars', 'avix-widgets' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 5,
				'min'     => 1,
				'max'     => 5,
				'step'    => 0.1,
			)
		);

		$repeater->add_control(
			'project',
			array(
				'label'       => esc_html__( 'Project tag (optional)', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'Shopify store', 'avix-widgets' ),
			)
		);

		$repeater->add_control(
			'logo',
			array(
				'label'       => esc_html__( 'Client logo (optional)', 'avix-widgets' ),
				'type'        => Controls_Manager::MEDIA,
				'dynamic'     => array( 'active' => true ),
				'separator'   => 'before',
			)
		);

		$repeater->add_control(
			'photo',
			array(
				'label'       => esc_html__( 'Client photo (optional)', 'avix-widgets' ),
				'description' => esc_html__( 'Only a real photo of this client, used with their permission. Best: 4:5, at least 1200×1500. Without one, the card shows a brand monogram.', 'avix-widgets' ),
				'type'        => Controls_Manager::MEDIA,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'photo_focus',
			array(
				'label'       => esc_html__( 'Photo focus (vertical)', 'avix-widgets' ),
				'description' => esc_html__( 'Keeps the face in frame when the card crops the photo (wide on phones).', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%' ),
				'range'       => array( '%' => array( 'min' => 0, 'max' => 100 ) ),
				'default'     => array( 'unit' => '%', 'size' => 28 ),
				'condition'   => array( 'photo[url]!' => '' ),
			)
		);

		$this->add_control(
			'reviews',
			array(
				'label'       => esc_html__( 'Reviews', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ name }}} · {{{ platform }}}',
				'default'     => $this->default_reviews(),
			)
		);

		$this->end_controls_section();
	}

	private function controls_proof() {
		$this->start_controls_section( 'section_proof', array( 'label' => esc_html__( 'Proof Row', 'avix-widgets' ) ) );

		$this->add_control(
			'show_proof',
			array(
				'label'   => esc_html__( 'Show proof row under the title', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'proof_rating',
			array(
				'label'     => esc_html__( 'Fiverr rating', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => '5.0',
				'condition' => array( 'show_proof' => 'yes' ),
			)
		);

		$this->add_control(
			'proof_count',
			array(
				'label'     => esc_html__( 'Review count text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( '150+ verified reviews', 'avix-widgets' ),
				'condition' => array( 'show_proof' => 'yes' ),
			)
		);

		$this->add_control(
			'proof_verified',
			array(
				'label'       => esc_html__( 'Verified text', 'avix-widgets' ),
				'description' => esc_html__( 'The words "Fiverr" and "Upwork" link to your profiles below.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Fiverr & Upwork verified', 'avix-widgets' ),
				'condition'   => array( 'show_proof' => 'yes' ),
			)
		);

		$this->add_control(
			'fiverr_url',
			array(
				'label'   => esc_html__( 'Fiverr profile', 'avix-widgets' ),
				'type'    => Controls_Manager::URL,
				'default' => array(
					'url'         => self::FIVERR_URL,
					'is_external' => 'on',
					'nofollow'    => 'on',
				),
			)
		);

		$this->add_control(
			'upwork_url',
			array(
				'label'   => esc_html__( 'Upwork profile', 'avix-widgets' ),
				'type'    => Controls_Manager::URL,
				'default' => array(
					'url'         => self::UPWORK_URL,
					'is_external' => 'on',
					'nofollow'    => 'on',
				),
			)
		);

		$this->end_controls_section();
	}

	private function controls_behaviour() {
		$this->start_controls_section( 'section_behaviour', array( 'label' => esc_html__( 'Slider', 'avix-widgets' ) ) );

		$this->add_control(
			'autoplay',
			array(
				'label'       => esc_html__( 'Autoplay', 'avix-widgets' ),
				'description' => esc_html__( 'Pauses on hover, keyboard focus, when off screen, and is off for visitors who prefer reduced motion. Paused inside the editor.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'interval',
			array(
				'label'     => esc_html__( 'Time per review (seconds)', 'avix-widgets' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 7,
				'min'       => 3,
				'max'       => 20,
				'step'      => 0.5,
				'condition' => array( 'autoplay' => 'yes' ),
			)
		);

		$this->add_control(
			'photo_style',
			array(
				'label'       => esc_html__( 'Client photo style', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'original',
				'options'     => array(
					'original' => esc_html__( 'As uploaded (already styled)', 'avix-widgets' ),
					'brand'    => esc_html__( 'Auto brand look (B&W + orange light)', 'avix-widgets' ),
				),
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'show_card_footer',
			array(
				'label'       => esc_html__( 'Stars & source badge on cards', 'avix-widgets' ),
				'description' => esc_html__( 'Off keeps each card minimal: name, line, review.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'source_label',
			array(
				'label'       => esc_html__( 'Badge text', 'avix-widgets' ),
				'description' => esc_html__( '%s is replaced by Fiverr or Upwork.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Verified on %s', 'avix-widgets' ),
				'condition'   => array( 'show_card_footer' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_style() {
		$this->start_controls_section(
			'style_colors',
			array(
				'label' => esc_html__( 'Colours', 'avix-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$vars = array(
			'rv_bg'      => array( esc_html__( 'Section background', 'avix-widgets' ), '--rv-bg' ),
			'rv_accent'  => array( esc_html__( 'Accent', 'avix-widgets' ), '--rv-accent' ),
			'rv_ink'     => array( esc_html__( 'Text', 'avix-widgets' ), '--rv-ink' ),
			'rv_muted'   => array( esc_html__( 'Secondary text', 'avix-widgets' ), '--rv-muted' ),
			'rv_shell'   => array( esc_html__( 'Card frame', 'avix-widgets' ), '--rv-shell' ),
			'rv_visual'  => array( esc_html__( 'Visual panel', 'avix-widgets' ), '--rv-visual' ),
			'rv_line'    => array( esc_html__( 'Lines', 'avix-widgets' ), '--rv-line' ),
			'rv_star'    => array( esc_html__( 'Stars', 'avix-widgets' ), '--rv-star' ),
		);
		foreach ( $vars as $key => $var ) {
			$this->add_control(
				$key,
				array(
					'label'     => $var[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-rv' => $var[1] . ': {{VALUE}};' ),
				)
			);
		}

		$this->end_controls_section();

		$this->start_controls_section(
			'style_layout',
			array(
				'label' => esc_html__( 'Layout & Type', 'avix-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'padding',
			array(
				'label'              => esc_html__( 'Padding', 'avix-widgets' ),
				'type'               => Controls_Manager::DIMENSIONS,
				'size_units'         => array( 'px', 'em', 'vh' ),
				'allowed_dimensions' => 'vertical',
				'selectors'          => array( '{{WRAPPER}} .avix-rv' => 'padding-top: {{TOP}}{{UNIT}}; padding-bottom: {{BOTTOM}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'card_width',
			array(
				'label'      => esc_html__( 'Card max width', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 560, 'max' => 1200 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-rv' => '--rv-card-max: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'label'    => esc_html__( 'Title', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-rv .avix-rv__title',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'quote_typography',
				'label'    => esc_html__( 'Review text', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-rv .avix-rv-card__quote',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'name_typography',
				'label'    => esc_html__( 'Client name', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-rv .avix-rv-card__name',
			)
		);

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ */
	/* Render                                                              */
	/* ------------------------------------------------------------------ */

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$reviews  = array_values(
			array_filter(
				(array) $settings['reviews'],
				static function ( $row ) {
					return '' !== trim( (string) ( $row['quote'] ?? '' ) );
				}
			)
		);
		$total = count( $reviews );

		if ( ! $total ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="elementor-alert elementor-alert-info">' . esc_html__( 'Testimonial Stack: add at least one review.', 'avix-widgets' ) . '</div>';
			}
			return;
		}

		$title_id = 'avix-rv-title-' . $this->get_id();
		$tag      = Utils::validate_html_tag( $settings['title_tag'] );
		$has_title = '' !== trim( $settings['title'] . $settings['title_accent'] );
		$interval = max( 3, min( 20, (float) $settings['interval'] ) ) * 1000;

		$this->add_render_attribute(
			'root',
			array(
				'class'       => array( 'avix-rv', 'brand' === $settings['photo_style'] ? 'avix-rv--photo-brand' : 'avix-rv--photo-original' ),
				'data-avix-rv' => wp_json_encode(
					array(
						'autoplay' => 'yes' === $settings['autoplay'],
						'interval' => (int) $interval,
					)
				),
				'style'       => '--rv-interval:' . (int) $interval . 'ms;',
			)
		);
		if ( $has_title ) {
			$this->add_render_attribute( 'root', 'aria-labelledby', $title_id );
		}
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="avix-rv__frame">
				<header class="avix-rv__head">
					<?php if ( '' !== trim( (string) $settings['eyebrow'] ) ) : ?>
						<p class="avix-rv__eyebrow"><?php $this->icon( 'quote' ); ?><?php echo esc_html( $settings['eyebrow'] ); ?></p>
					<?php endif; ?>
					<?php if ( $has_title ) : ?>
						<<?php echo esc_attr( $tag ); ?> class="avix-rv__title" id="<?php echo esc_attr( $title_id ); ?>">
							<?php echo esc_html( $settings['title'] ); ?>
							<?php if ( '' !== trim( (string) $settings['title_accent'] ) ) : ?>
								<span class="avix-rv__title-accent"><?php echo esc_html( $settings['title_accent'] ); ?></span>
							<?php endif; ?>
						</<?php echo esc_attr( $tag ); ?>>
					<?php endif; ?>
					<?php
					if ( 'yes' === $settings['show_proof'] ) {
						$this->render_proof( $settings );
					}
					?>
				</header>

				<div class="avix-rv__body">
					<button class="avix-rv__arrow avix-rv__arrow--prev" type="button" data-rv-prev aria-label="<?php esc_attr_e( 'Previous review', 'avix-widgets' ); ?>"><?php $this->icon( 'prev' ); ?></button>

					<div class="avix-rv__stage" data-rv-stage tabindex="0" role="region" aria-roledescription="<?php esc_attr_e( 'carousel', 'avix-widgets' ); ?>" aria-label="<?php esc_attr_e( 'Client reviews', 'avix-widgets' ); ?>">
						<?php
						foreach ( $reviews as $index => $review ) {
							$this->render_card( $settings, $review, $index, $total );
						}
						?>
					</div>

					<button class="avix-rv__arrow avix-rv__arrow--next" type="button" data-rv-next aria-label="<?php esc_attr_e( 'Next review', 'avix-widgets' ); ?>"><?php $this->icon( 'next' ); ?></button>

					<?php if ( $total > 1 ) : ?>
						<div class="avix-rv__controls">
							<div class="avix-rv__progress">
								<?php for ( $i = 0; $i < $total; $i++ ) : ?>
									<?php /* translators: %d: review number. */ ?>
									<button class="avix-rv__seg<?php echo 0 === $i ? ' is-active' : ''; ?>" type="button" data-rv-seg aria-label="<?php echo esc_attr( sprintf( __( 'Show review %d', 'avix-widgets' ), $i + 1 ) ); ?>"><i></i></button>
								<?php endfor; ?>
							</div>
							<span class="avix-rv__count" aria-hidden="true"><b data-rv-current>01</b> / <?php echo esc_html( sprintf( '%02d', $total ) ); ?></span>
							<button class="avix-rv__toggle" type="button" data-rv-toggle aria-label="<?php esc_attr_e( 'Pause autoplay', 'avix-widgets' ); ?>" data-label-pause="<?php esc_attr_e( 'Pause autoplay', 'avix-widgets' ); ?>" data-label-play="<?php esc_attr_e( 'Start autoplay', 'avix-widgets' ); ?>">
								<svg class="avix-rv__icon-pause" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 5v14M15 5v14"/></svg>
								<svg class="avix-rv__icon-play" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 5.5v13l10.5-6.5L8 5.5Z"/></svg>
							</button>
						</div>
					<?php endif; ?>
				</div>
				<?php /* translators: 1: current review number, 2: total reviews. */ ?>
				<p class="avix-rv__sr" data-rv-status aria-live="polite" data-template="<?php esc_attr_e( 'Review %1$s of %2$s', 'avix-widgets' ); ?>"></p>
			</div>
		</section>
		<?php
	}

	private function render_proof( array $settings ) {
		$items = array();

		if ( '' !== trim( (string) $settings['proof_rating'] ) ) {
			ob_start();
			$this->stars( 5 );
			$items[] = ob_get_clean() . '<span><strong>' . esc_html( $settings['proof_rating'] ) . '</strong> ' . esc_html__( 'rating on Fiverr', 'avix-widgets' ) . '</span>';
		}
		if ( '' !== trim( (string) $settings['proof_count'] ) ) {
			$items[] = '<span>' . esc_html( $settings['proof_count'] ) . '</span>';
		}
		if ( '' !== trim( (string) $settings['proof_verified'] ) ) {
			$text = esc_html( $settings['proof_verified'] );
			foreach ( array( 'fiverr' => 'Fiverr', 'upwork' => 'Upwork' ) as $platform => $word ) {
				$link = $settings[ $platform . '_url' ] ?? array();
				if ( empty( $link['url'] ) || false === strpos( $text, $word ) ) {
					continue;
				}
				$key = 'proof-' . $platform;
				$this->add_link_attributes( $key, $link );
				$text = preg_replace( '/' . $word . '/', '<a ' . $this->get_render_attribute_string( $key ) . '>' . $word . '</a>', $text, 1 );
			}
			$items[] = '<span>' . $text . '</span>';
		}

		if ( ! $items ) {
			return;
		}
		echo '<ul class="avix-rv__proof">';
		foreach ( $items as $item ) {
			echo '<li>' . $item . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts.
		}
		echo '</ul>';
	}

	private function render_card( array $settings, array $review, $index, $total ) {
		$name     = trim( (string) ( $review['name'] ?? '' ) );
		$location = trim( (string) ( $review['location'] ?? '' ) );
		$role     = trim( (string) ( $review['role'] ?? '' ) );
		$project  = trim( (string) ( $review['project'] ?? '' ) );
		$platform = in_array( $review['platform'] ?? '', array( 'fiverr', 'upwork' ), true ) ? $review['platform'] : '';
		$rating   = max( 1, min( 5, (float) ( $review['rating'] ?? 5 ) ) );
		$photo    = $review['photo']['url'] ?? '';
		$logo     = $review['logo']['url'] ?? '';
		$glow     = array( array( 18, 100 ), array( 86, 96 ), array( 50, 108 ), array( 10, 70 ), array( 92, 64 ), array( 30, 90 ) );
		$pos      = $glow[ $index % count( $glow ) ];
		$key      = 'slide-' . $index;

		/* translators: 1: review number, 2: total. */
		$label = sprintf( __( 'Review %1$d of %2$d', 'avix-widgets' ), $index + 1, $total );

		$this->add_render_attribute(
			$key,
			array(
				'class'                => 'avix-rv-card',
				'data-rv-slide'        => '',
				'data-depth'           => (string) min( 3, ( $total - $index ) % $total ),
				'role'                 => 'group',
				'aria-roledescription' => __( 'slide', 'avix-widgets' ),
				'aria-label'           => $label,
				'style'                => sprintf( '--rv-gx:%d%%;--rv-gy:%d%%;', $pos[0], $pos[1] ),
			)
		);
		if ( $index > 0 ) {
			$this->add_render_attribute( $key, 'aria-hidden', 'true' );
		}
		?>
		<article <?php $this->print_render_attribute_string( $key ); ?>>
			<div class="avix-rv-card__shell">
				<div class="avix-rv-card__visual<?php echo $photo ? ' avix-rv-card__visual--photo' : ''; ?>" aria-hidden="true">
					<?php if ( $photo ) : ?>
						<?php $focus = isset( $review['photo_focus']['size'] ) && '' !== $review['photo_focus']['size'] ? max( 0, min( 100, (float) $review['photo_focus']['size'] ) ) : 28; ?>
						<img class="avix-rv-card__photo" src="<?php echo esc_url( $photo ); ?>" alt="" loading="lazy" decoding="async" style="--rv-focus:<?php echo esc_attr( $focus ); ?>%">
					<?php else : ?>
						<span class="avix-rv-card__monogram"><?php echo esc_html( $this->initials( $name ) ); ?></span>
					<?php endif; ?>
					<?php if ( '' !== $location ) : ?>
						<span class="avix-rv-card__place"><?php $this->icon( 'pin' ); ?><?php echo esc_html( $location ); ?></span>
					<?php endif; ?>
				</div>

				<div class="avix-rv-card__content">
					<div class="avix-rv-card__top">
						<div>
							<?php if ( $logo ) : ?>
								<img class="avix-rv-card__logo" src="<?php echo esc_url( $logo ); ?>" alt="" loading="lazy" decoding="async">
							<?php endif; ?>
							<p class="avix-rv-card__name"><?php echo esc_html( $name ); ?></p>
							<p class="avix-rv-card__meta"><?php echo esc_html( $this->meta_line( $role, $platform ) ); ?></p>
						</div>
						<span class="avix-rv-card__mark" aria-hidden="true">&rdquo;</span>
					</div>

					<span class="avix-rv-card__rule" aria-hidden="true"></span>

					<blockquote class="avix-rv-card__quote is-style-plain has-background">
						<p>&ldquo;<?php echo esc_html( trim( (string) $review['quote'], " \t\n\r\"“”" ) ); ?>&rdquo;</p>
					</blockquote>

					<?php if ( 'yes' === $settings['show_card_footer'] ) : ?>
					<div class="avix-rv-card__foot">
						<span class="avix-rv-card__rating">
							<?php $this->stars( $rating ); ?>
							<?php /* translators: %s: rating. */ ?>
							<span class="avix-rv__sr"><?php echo esc_html( sprintf( __( 'Rated %s out of 5', 'avix-widgets' ), number_format( $rating, 1 ) ) ); ?></span>
							<span aria-hidden="true"><?php echo esc_html( number_format( $rating, 1 ) ); ?></span>
						</span>
						<?php if ( '' !== $project ) : ?>
							<span class="avix-rv-card__tag"><?php echo esc_html( $project ); ?></span>
						<?php endif; ?>
						<?php $this->render_source( $settings, $review, $platform, $index ); ?>
					</div>
					<?php endif; ?>
				</div>
			</div>
		</article>
		<?php
	}

	private function render_source( array $settings, array $review, $platform, $index ) {
		$key = 'source-' . $index;

		if ( ! $platform ) {
			echo '<span class="avix-rv-card__source"><span class="avix-rv-card__source-mark">';
			$this->icon( 'check' );
			echo '</span>' . esc_html__( 'Verified client', 'avix-widgets' ) . '</span>';
			return;
		}

		$data  = self::PLATFORMS[ $platform ];
		$label = sprintf( (string) $settings['source_label'], $data['label'] );
		$link  = ! empty( $review['source_url']['url'] ) ? $review['source_url'] : ( $settings[ $platform . '_url' ] ?? array() );

		$this->add_render_attribute( $key, array( 'class' => 'avix-rv-card__source', 'style' => '--rv-brand:' . $data['color'] . ';' ) );
		$tag = empty( $link['url'] ) ? 'span' : 'a';
		if ( 'a' === $tag ) {
			$this->add_link_attributes( $key, $link );
		}
		?>
		<<?php echo esc_attr( $tag ); ?> <?php $this->print_render_attribute_string( $key ); ?>>
			<span class="avix-rv-card__source-mark"><?php echo $this->platform_mark( $platform ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in platform_mark(). ?></span>
			<?php echo esc_html( $label ); ?>
			<?php if ( 'a' === $tag ) : ?>
				<svg class="avix-rv-card__source-arrow" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 17 17 7"/><path d="M8 7h9v9"/></svg>
			<?php endif; ?>
		</<?php echo esc_attr( $tag ); ?>>
		<?php
	}

	/* ------------------------------------------------------------------ */
	/* Data & helpers                                                      */
	/* ------------------------------------------------------------------ */

	/**
	 * Real reviews as published on avixdigital.com (September 2026).
	 */
	private function default_reviews() {
		return array(
			array(
				'quote'    => 'Avix Digital has done a ton of work for us and never fails to deliver. They consistently go above and beyond our expectations. 100% recommend them!',
				'name'     => 'dallinjd',
				'location' => 'Canada',
				'platform' => 'fiverr',
				'rating'   => 5,
			),
			array(
				'quote'    => 'Akib genuinely cares about your company’s success. He doesn’t just want to finish the job; he wants to ensure the delivery is flawless. Highly recommended.',
				'name'     => 'ptsmarketingja',
				'location' => 'Jamaica',
				'role'     => 'PTS Marketing',
				'platform' => 'fiverr',
				'rating'   => 5,
				'logo'     => array( 'url' => 'https://avixdigital.com/wp-content/uploads/2025/12/PTS-marketing1-scaled.png' ),
			),
			array(
				'quote'    => 'This was a crucial project for my brand, and they perfectly nailed the color palette and messaging. I am incredibly impressed with the final result.',
				'name'     => 'mayor2020',
				'location' => 'United States',
				'platform' => 'fiverr',
				'rating'   => 5,
			),
			array(
				'quote'    => 'Another great experience working with this team. They know exactly how to build a beautiful website with a sharp eye for detail. Will definitely hire again.',
				'name'     => 'Agency Partner',
				'location' => 'United Kingdom',
				'platform' => 'verified',
				'rating'   => 5,
			),
			array(
				'quote'    => 'A genuinely great development team to work with. They went well out of their way to help us, even exceeding the limits of our original agreement.',
				'name'     => 'E-Commerce Client',
				'location' => 'Australia',
				'platform' => 'verified',
				'rating'   => 5,
			),
			array(
				'quote'    => 'Trust their process and have patience—their work is incredibly thorough. It might take time, but you’ll end up with the absolute dream website you asked for.',
				'name'     => 'Business Owner',
				'location' => 'United States',
				'platform' => 'verified',
				'rating'   => 5,
			),
		);
	}

	/**
	 * Line under the name: the company when known, otherwise who they are to us.
	 */
	private function meta_line( $role, $platform ) {
		if ( '' !== $role ) {
			return $role;
		}
		if ( $platform ) {
			/* translators: %s: Fiverr or Upwork. */
			return sprintf( __( '%s client', 'avix-widgets' ), self::PLATFORMS[ $platform ]['label'] );
		}
		return __( 'Verified client', 'avix-widgets' );
	}

	private function initials( $name ) {
		$name  = trim( preg_replace( '/[^\p{L}\p{N}\s-]+/u', '', (string) $name ) );
		$words = preg_split( '/[\s-]+/', $name, -1, PREG_SPLIT_NO_EMPTY );
		if ( ! $words ) {
			return '★';
		}
		$first = function_exists( 'mb_substr' ) ? 'mb_substr' : 'substr';
		$upper = function_exists( 'mb_strtoupper' ) ? 'mb_strtoupper' : 'strtoupper';
		$out   = $first( $words[0], 0, 1 );
		if ( count( $words ) > 1 ) {
			$out .= $first( $words[1], 0, 1 );
		}
		return $upper( $out );
	}

	/**
	 * Platform mark. Fiverr's own icon is the "fi" roundel; its wordmark is unreadable at badge size.
	 */
	private function platform_mark( $platform ) {
		if ( 'fiverr' === $platform ) {
			return '<span class="avix-rv__fi" aria-hidden="true">fi</span>';
		}
		return sprintf(
			'<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="%s"/></svg>',
			esc_attr( self::PLATFORMS[ $platform ]['path'] )
		);
	}

	private function stars( $rating ) {
		$full = (int) round( $rating );
		echo '<span class="avix-rv__stars" aria-hidden="true">';
		for ( $i = 1; $i <= 5; $i++ ) {
			printf( '<svg viewBox="0 0 24 24" focusable="false"%s><path d="m12 2.7 2.82 5.72 6.31.92-4.56 4.44 1.08 6.28L12 17.09l-5.65 2.97 1.08-6.28-4.56-4.44 6.31-.92L12 2.7Z"/></svg>', $i > $full ? ' class="is-empty"' : '' );
		}
		echo '</span>';
	}

	private function icon( $name ) {
		$icons = array(
			'prev'  => '<path d="m15 18-6-6 6-6"/>',
			'next'  => '<path d="m9 18 6-6-6-6"/>',
			'pin'   => '<path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0C18.5 15.4 12 21 12 21Z"/><circle cx="12" cy="10" r="2.3"/>',
			'check' => '<path d="m6.5 12.5 3.5 3.5 7.5-8" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>',
			'quote' => '<path d="M4 17.6c0-4.9 2.2-8.4 6-10.6l1 1.6C8.7 10.1 7.6 11.8 7.4 14H10v6H4v-2.4Zm10 0c0-4.9 2.2-8.4 6-10.6l1 1.6c-2.3 1.5-3.4 3.2-3.6 5.4H20v6h-6v-2.4Z"/>',
		);
		if ( isset( $icons[ $name ] ) ) {
			echo '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $icons[ $name ] . '</svg>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup.
		}
	}
}
