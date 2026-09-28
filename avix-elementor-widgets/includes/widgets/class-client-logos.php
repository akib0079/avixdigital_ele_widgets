<?php
/**
 * Client Logos: a logo belt the Avix pixel character rides and narrates.
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

class Client_Logos extends Widget_Base {

	const UPLOADS = 'https://avixdigital.com/wp-content/uploads/';

	public function get_name(): string {
		return 'avix-client-logos';
	}

	public function get_title(): string {
		return esc_html__( 'Client Logos', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-logo';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'logos', 'clients', 'brands', 'marquee', 'carousel', 'trusted', 'avix' );
	}

	public function get_style_depends(): array {
		return array( 'avix-client-logos' );
	}

	public function get_script_depends(): array {
		return array( 'avix-client-logos' );
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/* ------------------------------------------------------------------ */
	/* Controls                                                            */
	/* ------------------------------------------------------------------ */

	protected function register_controls(): void {
		$this->start_controls_section( 'section_header', array( 'label' => esc_html__( 'Layout & Header', 'avix-widgets' ) ) );

		$this->add_control(
			'variant',
			array(
				'label'       => esc_html__( 'Design', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'story',
				'options'     => array(
					'story'   => esc_html__( 'Story belt (pixel character)', 'avix-widgets' ),
					'minimal' => esc_html__( 'Minimal marquee', 'avix-widgets' ),
				),
				'description' => esc_html__( 'Minimal: just the logos gliding by with a one-line heading. They turn to colour and the row eases to a stop on hover.', 'avix-widgets' ),
			)
		);

		$this->add_control(
			'head_position',
			array(
				'label'     => esc_html__( 'Heading position', 'avix-widgets' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'top',
				'options'   => array(
					'top'  => esc_html__( 'Above the logos', 'avix-widgets' ),
					'side' => esc_html__( 'Beside the logos (wide screens)', 'avix-widgets' ),
				),
				'condition' => array( 'variant' => 'minimal' ),
			)
		);

		$this->add_control(
			'eyebrow',
			array(
				'label'     => esc_html__( 'Eyebrow', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Our clients', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'variant' => 'story' ),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Title', 'avix-widgets' ),
				'description' => esc_html__( 'Wrap words in [brackets] to highlight them in orange.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => 'Trusted by [200+ clients] around the world',
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
					'p'   => 'p',
				),
			)
		);

		$this->add_control(
			'subtitle',
			array(
				'label'   => esc_html__( 'Subtitle', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 2,
				'default'   => esc_html__( 'Every logo here is a real launch. Pick one to hear its story.', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'variant' => 'story' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section( 'section_clients', array( 'label' => esc_html__( 'Clients', 'avix-widgets' ) ) );

		$this->add_control(
			'clients_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Story belt: what you add here is what the pixel character says when a visitor hovers or taps the logo. A case-study link makes the logo clickable (in both designs).', 'avix-widgets' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$repeater = new Repeater();
		$repeater->add_control(
			'logo',
			array(
				'label'   => esc_html__( 'Logo', 'avix-widgets' ),
				'type'    => Controls_Manager::MEDIA,
				'dynamic' => array( 'active' => true ),
			)
		);
		$repeater->add_control(
			'zoom',
			array(
				'label'       => esc_html__( 'Logo size', 'avix-widgets' ),
				'description' => esc_html__( 'Enlarge logos whose image file has a lot of empty space around them.', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%' ),
				'range'       => array( '%' => array( 'min' => 50, 'max' => 320, 'step' => 5 ) ),
				'default'     => array( 'unit' => '%', 'size' => 100 ),
			)
		);
		$repeater->add_control(
			'name',
			array(
				'label'       => esc_html__( 'Client name', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);
		$repeater->add_control(
			'project',
			array(
				'label'       => esc_html__( 'What we built', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'Shopify storefront', 'avix-widgets' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);
		$repeater->add_control(
			'country',
			array(
				'label'       => esc_html__( 'Country / city', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'Netherlands', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
			)
		);
		$repeater->add_control(
			'link',
			array(
				'label'   => esc_html__( 'Case study link (optional)', 'avix-widgets' ),
				'type'    => Controls_Manager::URL,
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'clients',
			array(
				'label'       => esc_html__( 'Clients', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ name }}}',
				'default'     => $this->default_clients(),
			)
		);

		$this->add_control(
			'mono',
			array(
				'label'       => esc_html__( 'Grey logos until hovered', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'speed',
			array(
				'label'       => esc_html__( 'Belt speed (px per second)', 'avix-widgets' ),
				'description' => esc_html__( '0 = a still row. Always still for visitors who prefer reduced motion.', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 120, 'step' => 2 ) ),
				'default'     => array( 'size' => 36 ),
			)
		);

		$this->add_control(
			'show_toggle',
			array(
				'label'       => esc_html__( 'Pause button', 'avix-widgets' ),
				'description' => esc_html__( 'Lets visitors stop the moving logos. Recommended for accessibility.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_responsive_control(
			'logo_height',
			array(
				'label'      => esc_html__( 'Logo height', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 16, 'max' => 96 ) ),
				'separator'  => 'before',
				'selectors'  => array( '{{WRAPPER}} .avix-cl__inner' => '--cl-logo-h: {{SIZE}}{{UNIT}};' ),
				'condition'  => array( 'variant' => 'minimal' ),
			)
		);

		$this->add_responsive_control(
			'logo_width',
			array(
				'label'      => esc_html__( 'Max logo width', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 60, 'max' => 320 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-cl__inner' => '--cl-logo-w: {{SIZE}}{{UNIT}};' ),
				'condition'  => array( 'variant' => 'minimal' ),
			)
		);

		$this->add_responsive_control(
			'logo_gap',
			array(
				'label'      => esc_html__( 'Space between logos', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 16, 'max' => 160 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-cl__inner' => '--cl-gap: {{SIZE}}{{UNIT}};' ),
				'condition'  => array( 'variant' => 'minimal' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_buddy',
			array(
				'label'     => esc_html__( 'Pixel Character', 'avix-widgets' ),
				'condition' => array( 'variant' => 'story' ),
			)
		);

		$this->add_control(
			'show_buddy',
			array(
				'label'       => esc_html__( 'Show the character', 'avix-widgets' ),
				'description' => esc_html__( 'Walks against the belt like a treadmill, hops forward to stay in view and jumps to any logo that is hovered, focused or tapped to tell its story.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'intro',
			array(
				'label'     => esc_html__( 'First line (mouse)', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Psst… hover a logo 👀', 'avix-widgets' ),
				'condition' => array( 'show_buddy' => 'yes' ),
			)
		);

		$this->add_control(
			'intro_touch',
			array(
				'label'     => esc_html__( 'First line (touch screens)', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Psst… tap a logo 👀', 'avix-widgets' ),
				'condition' => array( 'show_buddy' => 'yes' ),
			)
		);

		$this->add_control(
			'link_text',
			array(
				'label'     => esc_html__( 'Case study link text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'See the case study', 'avix-widgets' ),
				'condition' => array( 'show_buddy' => 'yes' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section( 'section_proof', array( 'label' => esc_html__( 'Review Badge', 'avix-widgets' ) ) );

		$this->add_control(
			'show_proof',
			array(
				'label'   => esc_html__( 'Show', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'proof_rating',
			array(
				'label'     => esc_html__( 'Rating', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => '5.0',
				'condition' => array( 'show_proof' => 'yes' ),
			)
		);

		$this->add_control(
			'proof_text',
			array(
				'label'     => esc_html__( 'Text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( '150+ real reviews on Fiverr', 'avix-widgets' ),
				'condition' => array( 'show_proof' => 'yes' ),
			)
		);

		$this->add_control(
			'proof_link',
			array(
				'label'     => esc_html__( 'Link', 'avix-widgets' ),
				'type'      => Controls_Manager::URL,
				'default'   => array(
					'url'         => 'https://www.fiverr.com/akib0079',
					'is_external' => 'on',
					'nofollow'    => 'on',
				),
				'condition' => array( 'show_proof' => 'yes' ),
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
				'label'   => esc_html__( 'Style', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'light',
				'options' => array(
					'light' => esc_html__( 'Light', 'avix-widgets' ),
					'dark'  => esc_html__( 'Dark', 'avix-widgets' ),
				),
			)
		);

		$colors = array(
			'cl_bg'        => array( esc_html__( 'Background', 'avix-widgets' ), '--cl-bg' ),
			'cl_ink'       => array( esc_html__( 'Headings', 'avix-widgets' ), '--cl-ink' ),
			'cl_muted'     => array( esc_html__( 'Secondary text', 'avix-widgets' ), '--cl-muted' ),
			'cl_accent'    => array( esc_html__( 'Accent', 'avix-widgets' ), '--cl-accent' ),
			'cl_tile'      => array( esc_html__( 'Logo tiles', 'avix-widgets' ), '--cl-tile' ),
			'cl_tile_line' => array( esc_html__( 'Tile border', 'avix-widgets' ), '--cl-tile-line' ),
		);
		foreach ( $colors as $key => $color ) {
			$this->add_control(
				$key,
				array(
					'label'     => $color[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-cl' => $color[1] . ': {{VALUE}};' ),
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
				'selectors'          => array( '{{WRAPPER}} .avix-cl' => 'padding-top: {{TOP}}{{UNIT}}; padding-bottom: {{BOTTOM}}{{UNIT}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'label'    => esc_html__( 'Title', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-cl .avix-cl__title',
			)
		);

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ */
	/* Render                                                              */
	/* ------------------------------------------------------------------ */

	protected function render(): void {
		$s       = $this->get_settings_for_display();
		$clients = array_values(
			array_filter(
				(array) $s['clients'],
				static function ( $row ) {
					return ! empty( $row['logo']['url'] ) || '' !== trim( (string) ( $row['name'] ?? '' ) );
				}
			)
		);

		if ( ! $clients ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="elementor-alert elementor-alert-info">' . esc_html__( 'Client Logos: add at least one client.', 'avix-widgets' ) . '</div>';
			}
			return;
		}

		$minimal  = 'minimal' === ( $s['variant'] ?? 'story' );
		$buddy    = ! $minimal && 'yes' === $s['show_buddy'];
		$title_id = 'avix-cl-title-' . $this->get_id();
		$classes  = array( 'avix-cl', $minimal ? 'avix-cl--minimal' : 'avix-cl--story', 'dark' === $s['theme'] ? 'avix-cl--dark' : 'avix-cl--light' );
		if ( 'yes' === $s['mono'] ) {
			$classes[] = 'avix-cl--mono';
		}
		if ( $minimal ) {
			$classes[] = 'avix-cl--head-' . ( 'side' === $s['head_position'] ? 'side' : 'top' );
		} elseif ( ! $buddy ) {
			$classes[] = 'avix-cl--no-buddy';
		}
		$speed = isset( $s['speed']['size'] ) && '' !== $s['speed']['size'] ? (float) $s['speed']['size'] : 36;

		$this->add_render_attribute(
			'root',
			array(
				'class'        => $classes,
				'data-avix-cl' => wp_json_encode(
					array(
						'story'      => ! $minimal,
						'speed'      => max( 0, min( 200, $speed ) ),
						'intro'      => $buddy ? (string) $s['intro'] : '',
						'introTouch' => $buddy ? (string) $s['intro_touch'] : '',
						'linkText'   => (string) $s['link_text'],
					)
				),
			)
		);
		if ( '' !== trim( (string) $s['title'] ) ) {
			$this->add_render_attribute( 'root', 'aria-labelledby', $title_id );
		}
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="avix-cl__inner">
				<?php if ( $minimal ) : ?>
					<div class="avix-cl__frame avix-cl__row">
						<?php $this->render_head( $s, $title_id, true ); ?>
						<?php $this->render_stage( $s, $clients, false, true ); ?>
					</div>
				<?php else : ?>
					<div class="avix-cl__frame">
						<?php $this->render_head( $s, $title_id, false ); ?>
					</div>
					<?php $this->render_stage( $s, $clients, $buddy, false ); ?>
				<?php endif; ?>
				<?php $this->render_foot( $s, $speed ); ?>
			</div>
		</section>
		<?php
	}

	private function render_head( array $s, $title_id, $minimal ) {
		$eyebrow  = $minimal ? '' : trim( (string) $s['eyebrow'] );
		$title    = trim( (string) $s['title'] );
		$subtitle = $minimal ? '' : trim( (string) $s['subtitle'] );
		if ( '' === $eyebrow . $title . $subtitle ) {
			return;
		}
		$tag = Utils::validate_html_tag( $s['title_tag'] );
		?>
		<header class="avix-cl__head">
			<?php if ( '' !== $eyebrow ) : ?>
				<p class="avix-cl__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $title ) : ?>
				<<?php echo esc_attr( $tag ); ?> class="avix-cl__title" id="<?php echo esc_attr( $title_id ); ?>"><?php echo $this->accent_html( $title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in accent_html(). ?></<?php echo esc_attr( $tag ); ?>>
			<?php endif; ?>
			<?php if ( '' !== $subtitle ) : ?>
				<p class="avix-cl__sub"><?php echo esc_html( $subtitle ); ?></p>
			<?php endif; ?>
		</header>
		<?php
	}

	private function render_stage( array $s, array $clients, $buddy, $minimal ) {
		?>
		<div class="avix-cl__stage" data-cl-stage>
			<div class="avix-cl__viewport" data-cl-viewport>
				<div class="avix-cl__track" data-cl-track>
					<ul class="avix-cl__set" data-cl-set>
						<?php foreach ( $clients as $i => $client ) : ?>
							<li><?php $this->render_tile( $client, $i, $minimal ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
			<?php if ( ! $minimal ) : ?>
				<span class="avix-cl__belt" data-cl-belt aria-hidden="true"></span>

				<?php if ( $buddy ) : ?>
					<div class="avix-cl__buddy" data-cl-buddy aria-hidden="true">
						<span class="avix-cl__sprite">
							<svg viewBox="0 0 10 12" focusable="false">
								<rect class="avix-cl__b-leg avix-cl__b-leg--l" x="2" y="7" width="2" height="5"/>
								<rect class="avix-cl__b-leg avix-cl__b-leg--r" x="6" y="7" width="2" height="5"/>
								<rect class="avix-cl__b-body" x="2" y="3" width="6" height="4"/>
								<rect class="avix-cl__b-head" x="3" y="0" width="4" height="3"/>
								<rect class="avix-cl__b-arm avix-cl__b-arm--l" x="0" y="3" width="2" height="3"/>
								<rect class="avix-cl__b-arm avix-cl__b-arm--r" x="8" y="3" width="2" height="3"/>
							</svg>
						</span>
					</div>
				<?php endif; ?>

				<div class="avix-cl__bubble" data-cl-bubble role="status" aria-live="polite" aria-hidden="true">
					<strong class="avix-cl__bubble-name" data-cl-bname></strong>
					<span class="avix-cl__bubble-meta" data-cl-bmeta hidden></span>
					<a class="avix-cl__bubble-link" data-cl-blink href="#" hidden><span><?php echo esc_html( $s['link_text'] ); ?></span><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg></a>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	private function render_foot( array $s, $speed ) {
		$toggle = $speed > 0 && 'yes' === ( $s['show_toggle'] ?? 'yes' );
		ob_start();
		$this->render_proof( $s );
		$proof = ob_get_clean();
		if ( '' === trim( $proof ) && ! $toggle ) {
			return;
		}
		?>
		<div class="avix-cl__frame">
			<div class="avix-cl__foot<?php echo '' === trim( $proof ) ? ' avix-cl__foot--solo' : ''; ?>">
				<?php echo $proof; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in render_proof(). ?>
				<?php if ( $toggle ) : ?>
					<button class="avix-cl__toggle" type="button" data-cl-toggle aria-label="<?php esc_attr_e( 'Pause the logos', 'avix-widgets' ); ?>" data-label-pause="<?php esc_attr_e( 'Pause the logos', 'avix-widgets' ); ?>" data-label-play="<?php esc_attr_e( 'Play the logos', 'avix-widgets' ); ?>">
						<svg class="avix-cl__pause" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 5h3.5v14H7zM13.5 5H17v14h-3.5z"/></svg>
						<svg class="avix-cl__play" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 5.5v13l10.5-6.5L8 5.5Z"/></svg>
					</button>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	private function render_tile( array $client, $index, $minimal = false ) {
		$name    = trim( (string) ( $client['name'] ?? '' ) );
		$project = trim( (string) ( $client['project'] ?? '' ) );
		$country = trim( (string) ( $client['country'] ?? '' ) );
		$logo    = (string) ( $client['logo']['url'] ?? '' );
		$link    = (array) ( $client['link'] ?? array() );
		$key     = 'tile-' . $index;
		// Minimal logos without a link are just images, not buttons.
		$tag = empty( $link['url'] ) ? ( $minimal ? 'span' : 'button' ) : 'a';

		$this->add_render_attribute(
			$key,
			array(
				'class'        => $minimal ? 'avix-cl__logo' : 'avix-cl__tile',
				'data-cl-tile' => '',
				'data-name'    => $name,
				'data-project' => $project,
				'data-country' => $country,
			)
		);
		if ( ! $minimal ) {
			$this->add_render_attribute( $key, 'aria-label', implode( ', ', array_filter( array( $name, $project, $country ) ) ) );
		}
		$zoom = isset( $client['zoom']['size'] ) && '' !== $client['zoom']['size'] ? (float) $client['zoom']['size'] : 100;
		if ( 100.0 !== $zoom ) {
			$this->add_render_attribute( $key, 'style', '--cl-zoom:' . round( max( 50, min( 320, $zoom ) ) / 100, 2 ) );
		}
		if ( 'a' === $tag ) {
			$this->add_link_attributes( $key, $link );
		} elseif ( 'button' === $tag ) {
			$this->add_render_attribute( $key, 'type', 'button' );
		}
		?>
		<<?php echo esc_attr( $tag ); ?> <?php $this->print_render_attribute_string( $key ); ?>>
			<?php if ( '' !== $logo ) : ?>
				<img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( $minimal ? $name : '' ); ?>" loading="lazy" decoding="async">
			<?php else : ?>
				<span class="avix-cl__tile-name"><?php echo esc_html( $name ); ?></span>
			<?php endif; ?>
		</<?php echo esc_attr( $tag ); ?>>
		<?php
	}

	private function render_proof( array $s ) {
		if ( 'yes' !== $s['show_proof'] ) {
			return;
		}
		$rating = trim( (string) $s['proof_rating'] );
		$text   = trim( (string) $s['proof_text'] );
		if ( '' === $rating && '' === $text ) {
			return;
		}
		$link = (array) $s['proof_link'];
		$tag  = empty( $link['url'] ) ? 'span' : 'a';
		if ( 'a' === $tag ) {
			$this->add_link_attributes( 'proof', $link );
		}
		$this->add_render_attribute( 'proof', 'class', 'avix-cl__proof' );
		?>
		<<?php echo esc_attr( $tag ); ?> <?php $this->print_render_attribute_string( 'proof' ); ?>>
			<?php if ( '' !== $rating ) : ?>
				<strong><?php echo esc_html( $rating ); ?></strong>
				<span class="avix-cl__stars" aria-hidden="true"><?php echo str_repeat( '<svg viewBox="0 0 24 24" focusable="false"><path d="m12 2.7 2.82 5.72 6.31.92-4.56 4.44 1.08 6.28L12 17.09l-5.65 2.97 1.08-6.28-4.56-4.44 6.31-.92L12 2.7Z"/></svg>', 5 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup. ?></span>
			<?php endif; ?>
			<?php if ( '' !== $text ) : ?>
				<span><?php echo esc_html( $text ); ?></span>
			<?php endif; ?>
			<?php if ( false !== stripos( $text, 'fiverr' ) ) : ?>
				<span class="avix-cl__fi" aria-hidden="true">fi</span>
			<?php endif; ?>
		</<?php echo esc_attr( $tag ); ?>>
		<?php
	}

	/**
	 * Escapes the text, then turns [words] into highlighted spans.
	 */
	private function accent_html( $text ) {
		$escaped = esc_html( $text );
		return preg_replace( '/\[(.+?)\]/', '<span class="avix-cl__accent">$1</span>', $escaped );
	}

	/**
	 * The clients in the current homepage marquee. Project and country are
	 * filled only where the site already says what was built.
	 */
	private function default_clients() {
		$u = self::UPLOADS;
		return array(
			array( 'logo' => array( 'url' => $u . '2025/12/Untitled-design-12.png' ), 'zoom' => array( 'unit' => '%', 'size' => 300 ), 'name' => 'Rehall', 'project' => 'Shopify theme engineering', 'country' => 'Netherlands', 'link' => array( 'url' => 'https://akib.avixdigital.com/rehall-com/' ) ),
			array( 'logo' => array( 'url' => $u . '2025/12/CultivIQ5.png' ), 'name' => 'CultivIQ' ),
			array( 'logo' => array( 'url' => $u . '2025/12/Untitled-design-8.png' ), 'zoom' => array( 'unit' => '%', 'size' => 300 ), 'name' => 'OvaBalance', 'project' => 'Shopify subscription store', 'link' => array( 'url' => 'https://akib.avixdigital.com/ovabalance-eu/' ) ),
			array( 'logo' => array( 'url' => $u . '2025/12/Logo_Long-Version2-scaled.png' ), 'name' => 'Mazl Capital' ),
			array( 'logo' => array( 'url' => $u . '2025/12/Untitled-design-9.png' ), 'zoom' => array( 'unit' => '%', 'size' => 180 ), 'name' => 'World of Alps' ),
			array( 'logo' => array( 'url' => $u . '2025/12/Logo-1-scaled.png' ), 'name' => 'dolo creative.co' ),
			array( 'logo' => array( 'url' => $u . '2025/12/Untitled-design4.png' ), 'zoom' => array( 'unit' => '%', 'size' => 110 ), 'name' => 'Alexander McManus' ),
			array( 'logo' => array( 'url' => $u . '2025/12/FCTRY-logo-transparent-black-scaled.webp' ), 'zoom' => array( 'unit' => '%', 'size' => 170 ), 'name' => 'FCTRY' ),
			array( 'logo' => array( 'url' => $u . '2025/12/Vertical-Logos.png' ), 'name' => 'PYOP' ),
			array( 'logo' => array( 'url' => $u . '2025/12/Techbridge_Favicon.webp' ), 'name' => 'Techbridge' ),
		);
	}
}
