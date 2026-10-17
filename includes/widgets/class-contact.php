<?php
/**
 * Contact Studio: the contact page in one widget. A short dark banner with the
 * title, the booking button and a copy-email chip. Below it, on a light sheet,
 * the enquiry form (any shortcode, styled as a big mad-lib sentence for Avix
 * Forms) beside a small voxel pixel buddy that assembles from scattered
 * pixels, waves, follows the pointer with its eye and ripples where the
 * pointer passes, with the direct contact details under it; then a row with
 * what happens next.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

defined( 'ABSPATH' ) || exit;

class Contact extends Widget_Base {

	const CALENDLY = 'https://calendly.com/akibzawayed0079/meeting-for-quote';

	public function get_name(): string {
		return 'avix-contact';
	}

	public function get_title(): string {
		return esc_html__( 'Contact Studio', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-mail';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'contact', 'form', 'enquiry', 'booking', 'pixel', 'avatar', 'voxel', 'avix' );
	}

	public function get_style_depends(): array {
		return array( 'avix-contact' );
	}

	public function get_script_depends(): array {
		return array( 'avix-contact' );
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'section_hero', array( 'label' => esc_html__( 'Hero', 'avix-widgets' ) ) );

		$this->add_control(
			'eyebrow',
			array(
				'label'   => esc_html__( 'Eyebrow', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Contact', 'avix-widgets' ),
			)
		);
		$this->add_control(
			'title_1',
			array(
				'label'   => esc_html__( 'Title, line 1 (bold)', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Let’s', 'avix-widgets' ),
			)
		);
		$this->add_control(
			'title_2',
			array(
				'label'   => esc_html__( 'Title, line 2 (light)', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Build It.', 'avix-widgets' ),
			)
		);
		$this->add_control(
			'title_tag',
			array(
				'label'   => esc_html__( 'Title tag', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h1',
				'options' => array(
					'h1' => 'H1',
					'h2' => 'H2',
				),
			)
		);
		$this->add_control(
			'lede',
			array(
				'label'   => esc_html__( 'Intro', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 3,
				'default' => esc_html__( 'Planning a new webshop or website? Book a free 30-minute site review, or tell us about it below. We reply within 24 hours.', 'avix-widgets' ),
			)
		);
		$this->add_control(
			'button_text',
			array(
				'label'     => esc_html__( 'Button text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Book a free site review', 'avix-widgets' ),
				'separator' => 'before',
			)
		);
		$this->add_control(
			'button_link',
			array(
				'label'   => esc_html__( 'Button link', 'avix-widgets' ),
				'type'    => Controls_Manager::URL,
				'default' => array(
					'url'         => self::CALENDLY,
					'is_external' => 'on',
				),
			)
		);
		$this->add_control(
			'email',
			array(
				'label'   => esc_html__( 'Email (copy chip)', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => 'info@avixdigital.com',
			)
		);
		$this->add_control(
			'copy_label',
			array(
				'label'   => esc_html__( 'Copy tooltip', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Copy', 'avix-widgets' ),
			)
		);
		$this->add_control(
			'copied_label',
			array(
				'label'   => esc_html__( 'Copied tooltip', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Copied', 'avix-widgets' ),
			)
		);
		$this->add_control(
			'meta',
			array(
				'label'       => esc_html__( 'Facts under the buttons', 'avix-widgets' ),
				'description' => esc_html__( 'One per line. The first line gets a green "live" dot.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 3,
				'default'     => "Available for new projects\nReplies within 24 hours\n5.0 ★ from 150+ reviews",
				'separator'   => 'before',
			)
		);
		$this->add_control(
			'bubble',
			array(
				'label'       => esc_html__( 'Pixel buddy lines', 'avix-widgets' ),
				'description' => esc_html__( 'One per line; the speech bubble cycles through them. Clicking the buddy makes it hop.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 4,
				'default'     => "Hi! I’m Pixel 👋\nThe site review is free\nTell me about your store\nWe reply within 24 hours",
			)
		);

		$this->end_controls_section();

		$this->start_controls_section( 'section_form', array( 'label' => esc_html__( 'Form', 'avix-widgets' ) ) );

		$this->add_control(
			'form_eyebrow',
			array(
				'label'   => esc_html__( 'Label', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Project enquiry', 'avix-widgets' ),
			)
		);
		$this->add_control(
			'shortcode',
			array(
				'label'       => esc_html__( 'Form shortcode', 'avix-widgets' ),
				'description' => esc_html__( 'Avix Forms (conversational layout) gets the dark mad-lib style.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '[avix_form id="8364"]',
				'label_block' => true,
			)
		);
		$this->add_control(
			'form_note',
			array(
				'label'   => esc_html__( 'Note under the form', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 2,
				'default' => 'Please avoid sharing passwords or confidential customer data. <a href="https://avixdigital.com/privacy-policy/">Privacy policy</a>.',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section( 'section_aside', array( 'label' => esc_html__( 'Aside', 'avix-widgets' ) ) );

		$this->add_control(
			'steps_title',
			array(
				'label'   => esc_html__( 'Steps title', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'What happens next', 'avix-widgets' ),
			)
		);
		$this->add_control(
			'steps',
			array(
				'label'       => esc_html__( 'Steps', 'avix-widgets' ),
				'description' => esc_html__( 'One per line: "Title | text".', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 4,
				'default'     => "We reply within 24 hours | With first thoughts and a time for a call.\nA free 30-minute site review | We look at your site, goals and integrations together.\nA written scope and a fixed price | In euros, before any build starts.",
			)
		);
		$this->add_control(
			'direct_title',
			array(
				'label'     => esc_html__( 'Direct title', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Direct', 'avix-widgets' ),
				'separator' => 'before',
			)
		);
		$this->add_control(
			'phone',
			array(
				'label'       => esc_html__( 'Phone (optional)', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => '+31 20 000 0000',
			)
		);
		$this->add_control(
			'whatsapp_text',
			array(
				'label'   => esc_html__( 'WhatsApp link text', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Chat with us on WhatsApp', 'avix-widgets' ),
			)
		);
		$this->add_control(
			'whatsapp_link',
			array(
				'label'   => esc_html__( 'WhatsApp link', 'avix-widgets' ),
				'type'    => Controls_Manager::URL,
				'default' => array(
					'url'         => 'https://wa.link/moetob',
					'is_external' => 'on',
				),
			)
		);
		$this->add_control(
			'studio_title',
			array(
				'label'     => esc_html__( 'Studio title', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Studio', 'avix-widgets' ),
				'separator' => 'before',
			)
		);
		$this->add_control(
			'studio_text',
			array(
				'label'   => esc_html__( 'Studio text', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 4,
				'default' => "187 G RK Mission Road\nMymensingh, Dhaka 2200, Bangladesh\nA remote team working with brands in the Netherlands, Belgium, Germany and the UK.",
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			array(
				'label' => esc_html__( 'Colours', 'avix-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'accent',
			array(
				'label'     => esc_html__( 'Accent', 'avix-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#fb6007',
				'selectors' => array( '{{WRAPPER}} .avix-cn' => '--cn-accent: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'background',
			array(
				'label'     => esc_html__( 'Background', 'avix-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#0a0908',
				'selectors' => array( '{{WRAPPER}} .avix-cn' => '--cn-bg: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'body_theme',
			array(
				'label'   => esc_html__( 'Form section', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'light',
				'options' => array(
					'light' => esc_html__( 'Light', 'avix-widgets' ),
					'dark'  => esc_html__( 'Dark', 'avix-widgets' ),
				),
			)
		);
		$this->add_control(
			'body_background',
			array(
				'label'     => esc_html__( 'Form section background', 'avix-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#f5f3ef',
				'selectors' => array( '{{WRAPPER}} .avix-cn' => '--cn-body-bg: {{VALUE}};' ),
				'condition' => array( 'body_theme' => 'light' ),
			)
		);
		$this->end_controls_section();
	}

	/**
	 * Non-empty trimmed lines of a textarea setting.
	 *
	 * @param mixed $value Setting value.
	 */
	private static function lines( $value ) {
		return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $value ) ), 'strlen' ) );
	}

	protected function render(): void {
		$s     = $this->get_settings_for_display();
		$tag   = 'h2' === ( $s['title_tag'] ?? 'h1' ) ? 'h2' : 'h1';
		$email = sanitize_email( (string) $s['email'] );
		$lines = self::lines( $s['bubble'] );
		$this->add_render_attribute(
			'root',
			array(
				'class'           => 'dark' === ( $s['body_theme'] ?? 'light' ) ? 'avix-cn' : 'avix-cn avix-cn--light',
				'data-avix-cn'    => '',
				'data-cn-lines'   => wp_json_encode( $lines ),
				'data-cn-copied'  => (string) $s['copied_label'],
			)
		);
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="avix-cn__hero">
				<div class="avix-cn__head">
					<?php if ( '' !== trim( (string) $s['eyebrow'] ) ) : ?>
						<p class="avix-cn__eyebrow"><i aria-hidden="true"></i><?php echo esc_html( $s['eyebrow'] ); ?></p>
					<?php endif; ?>
					<<?php echo esc_attr( $tag ); ?> class="avix-cn__title">
						<span class="avix-cn__line"><?php echo esc_html( $s['title_1'] ); ?></span>
						<span class="avix-cn__line avix-cn__line--light"><?php echo esc_html( $s['title_2'] ); ?></span>
					</<?php echo esc_attr( $tag ); ?>>
				</div>
				<div class="avix-cn__intro">
					<?php if ( '' !== trim( (string) $s['lede'] ) ) : ?>
						<p class="avix-cn__lede"><?php echo esc_html( $s['lede'] ); ?></p>
					<?php endif; ?>
					<div class="avix-cn__actions">
						<?php if ( '' !== trim( (string) $s['button_text'] ) && ! empty( $s['button_link']['url'] ) ) : ?>
							<?php
							$this->add_link_attributes( 'button', $s['button_link'] );
							$this->add_render_attribute( 'button', 'class', 'avix-cn__btn' );
							?>
							<a <?php $this->print_render_attribute_string( 'button' ); ?>>
								<span><?php echo esc_html( $s['button_text'] ); ?></span>
								<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 17 17 7M8 7h9v9"/></svg>
							</a>
						<?php endif; ?>
						<?php if ( $email ) : ?>
							<button class="avix-cn__chip" type="button" data-cn-copy="<?php echo esc_attr( $email ); ?>">
								<span><?php echo esc_html( $email ); ?></span>
								<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1"/></svg>
								<span class="avix-cn__tip" data-cn-tip><?php echo esc_html( $s['copy_label'] ); ?></span>
							</button>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<div class="avix-cn__body">
				<div class="avix-cn__form">
					<?php if ( '' !== trim( (string) $s['form_eyebrow'] ) ) : ?>
						<p class="avix-cn__eyebrow avix-cn__eyebrow--live"><i aria-hidden="true"></i><?php echo esc_html( $s['form_eyebrow'] ); ?></p>
					<?php endif; ?>
					<div class="avix-cn__form-body">
						<?php echo do_shortcode( (string) $s['shortcode'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode output. ?>
					</div>
					<?php if ( '' !== trim( (string) $s['form_note'] ) ) : ?>
						<p class="avix-cn__note"><?php echo wp_kses_post( $s['form_note'] ); ?></p>
					<?php endif; ?>
				</div>

				<aside class="avix-cn__side">
					<div class="avix-cn__stage" data-cn-stage aria-hidden="true">
						<canvas class="avix-cn__canvas"></canvas>
						<?php if ( $lines ) : ?>
							<p class="avix-cn__bubble" data-cn-bubble><?php echo esc_html( $lines[0] ); ?></p>
						<?php endif; ?>
					</div>
					<?php $meta = self::lines( $s['meta'] ); ?>
					<?php if ( $meta ) : ?>
						<ul class="avix-cn__meta">
							<?php foreach ( $meta as $i => $item ) : ?>
								<li<?php echo 0 === $i ? ' class="is-live"' : ''; ?>><?php echo esc_html( $item ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<div class="avix-cn__block">
						<p class="avix-cn__label"><?php echo esc_html( $s['direct_title'] ); ?></p>
						<?php if ( $email ) : ?>
							<a class="avix-cn__link" href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>
						<?php endif; ?>
						<?php $phone = trim( (string) ( $s['phone'] ?? '' ) ); ?>
						<?php if ( '' !== $phone ) : ?>
							<a class="avix-cn__link" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
						<?php endif; ?>
						<?php if ( '' !== trim( (string) $s['whatsapp_text'] ) && ! empty( $s['whatsapp_link']['url'] ) ) : ?>
							<?php
							$this->add_link_attributes( 'wa', $s['whatsapp_link'] );
							$this->add_render_attribute( 'wa', 'class', 'avix-cn__link' );
							?>
							<a <?php $this->print_render_attribute_string( 'wa' ); ?>><?php echo esc_html( $s['whatsapp_text'] ); ?></a>
						<?php endif; ?>
					</div>
					<?php $studio = self::lines( $s['studio_text'] ); ?>
					<?php if ( $studio ) : ?>
						<div class="avix-cn__block">
							<p class="avix-cn__label"><?php echo esc_html( $s['studio_title'] ); ?></p>
							<address class="avix-cn__address">
								<?php foreach ( $studio as $i => $row ) : ?>
									<span<?php echo count( $studio ) - 1 === $i && count( $studio ) > 2 ? ' class="avix-cn__muted"' : ''; ?>><?php echo esc_html( $row ); ?></span>
								<?php endforeach; ?>
							</address>
						</div>
					<?php endif; ?>
				</aside>

				<?php $steps = self::lines( $s['steps'] ); ?>
				<?php if ( $steps ) : ?>
					<div class="avix-cn__next">
						<p class="avix-cn__label"><?php echo esc_html( $s['steps_title'] ); ?></p>
						<ol class="avix-cn__steps">
							<?php foreach ( $steps as $n => $step ) : ?>
								<?php $parts = array_map( 'trim', explode( '|', $step, 2 ) ); ?>
								<li>
									<span class="avix-cn__step-n" aria-hidden="true"><?php echo esc_html( sprintf( '%02d', $n + 1 ) ); ?></span>
									<span class="avix-cn__step-text">
										<strong><?php echo esc_html( $parts[0] ); ?></strong>
										<?php if ( ! empty( $parts[1] ) ) : ?>
											<span><?php echo esc_html( $parts[1] ); ?></span>
										<?php endif; ?>
									</span>
								</li>
							<?php endforeach; ?>
						</ol>
					</div>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}
}
