<?php
/**
 * Team: portrait cards for the people behind the projects, plus the Avix
 * pixel character's own card. It looks at whoever is hovered and waves.
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

class Team extends Widget_Base {

	use Media;

	const UPLOADS = 'https://avixdigital.com/wp-content/uploads/2026/09/';

	public function get_name(): string {
		return 'avix-team';
	}

	public function get_title(): string {
		return esc_html__( 'Team', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-person';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'team', 'people', 'staff', 'about', 'members', 'avix' );
	}

	public function get_style_depends(): array {
		return array( 'avix-team' );
	}

	public function get_script_depends(): array {
		return array( 'avix-team' );
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
			'eyebrow',
			array(
				'label'   => esc_html__( 'Eyebrow', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Meet the team', 'avix-widgets' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Title', 'avix-widgets' ),
				'description' => esc_html__( 'Wrap words in [brackets] to highlight them in orange.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => 'The people behind [your project]',
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

		$this->add_control(
			'text',
			array(
				'label'   => esc_html__( 'Text', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 3,
				'default' => esc_html__( 'Talk directly with the team planning and building your project, from the first brief through design, development and launch.', 'avix-widgets' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section( 'section_members', array( 'label' => esc_html__( 'Members', 'avix-widgets' ) ) );

		$repeater = new Repeater();
		$repeater->add_control(
			'photo',
			array(
				'label'   => esc_html__( 'Photo', 'avix-widgets' ),
				'type'    => Controls_Manager::MEDIA,
				'dynamic' => array( 'active' => true ),
			)
		);
		$repeater->add_control(
			'name',
			array(
				'label'       => esc_html__( 'Name', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);
		$repeater->add_control(
			'role',
			array(
				'label'       => esc_html__( 'Role', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);
		$repeater->add_control(
			'link',
			array(
				'label'       => esc_html__( 'Profile link (optional)', 'avix-widgets' ),
				'description' => esc_html__( 'LinkedIn or a bio page.', 'avix-widgets' ),
				'type'        => Controls_Manager::URL,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'members',
			array(
				'label'       => esc_html__( 'Members', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ name }}}',
				'default'     => array(
					array(
						'photo' => array( 'url' => self::UPLOADS . 'ChatGPT-Image-28-Sept-2026-09_34_48.png' ),
						'name'  => 'Akib Zawayed',
						'role'  => 'CEO, Lead Developer',
					),
					array(
						'photo' => array( 'url' => self::UPLOADS . 'ChatGPT-Image-28-Sept-2026-09_32_54.png' ),
						'name'  => 'Ayesha Akter',
						'role'  => 'Designer, Manager',
					),
					array(
						'photo' => array( 'url' => self::UPLOADS . 'ChatGPT-Image-28-Sept-2026-09_09_46.png' ),
						'name'  => 'Ranjan Deb',
						'role'  => 'Senior Fullstack Developer',
					),
					array(
						'photo' => array( 'url' => self::UPLOADS . 'ChatGPT-Image-28-Sept-2026-09_11_05.png' ),
						'name'  => 'Akib Zawayed',
						'role'  => 'Lead UIUX Designer',
					),
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section( 'section_pal', array( 'label' => esc_html__( 'Pixel Character Card', 'avix-widgets' ) ) );

		$this->add_control(
			'show_pal',
			array(
				'label'       => esc_html__( 'Show', 'avix-widgets' ),
				'description' => esc_html__( 'The Avix pixel character gets its own card: it waves now and then, looks at whoever is hovered, and jumps for joy when its own card is hovered.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'pal_title',
			array(
				'label'     => esc_html__( 'Line', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Hi! I’m on the team too', 'avix-widgets' ),
				'condition' => array( 'show_pal' => 'yes' ),
			)
		);

		$this->add_control(
			'pal_text',
			array(
				'label'     => esc_html__( 'Small print', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Pixel Avix, team mascot', 'avix-widgets' ),
				'condition' => array( 'show_pal' => 'yes' ),
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
			'bg'     => array( esc_html__( 'Background', 'avix-widgets' ), '--tm-bg' ),
			'ink'    => array( esc_html__( 'Headings', 'avix-widgets' ), '--tm-ink' ),
			'muted'  => array( esc_html__( 'Text', 'avix-widgets' ), '--tm-muted' ),
			'accent' => array( esc_html__( 'Accent', 'avix-widgets' ), '--tm-accent' ),
			'card'   => array( esc_html__( 'Cards', 'avix-widgets' ), '--tm-card' ),
		);
		foreach ( $colors as $key => $color ) {
			$this->add_control(
				$key,
				array(
					'label'     => $color[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-tm' => $color[1] . ': {{VALUE}};' ),
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
				'selectors'          => array( '{{WRAPPER}} .avix-tm' => 'padding-top: {{TOP}}{{UNIT}}; padding-bottom: {{BOTTOM}}{{UNIT}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'label'    => esc_html__( 'Title', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-tm .avix-tm__title',
			)
		);

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ */
	/* Render                                                              */
	/* ------------------------------------------------------------------ */

	protected function render(): void {
		$s       = $this->get_settings_for_display();
		$members = array_values(
			array_filter(
				(array) $s['members'],
				static function ( $row ) {
					return '' !== trim( (string) ( $row['name'] ?? '' ) ) || ! empty( $row['photo']['url'] );
				}
			)
		);
		$pal     = 'yes' === $s['show_pal'];
		$tag     = Utils::validate_html_tag( $s['title_tag'] );
		$title   = trim( (string) $s['title'] );

		$this->add_render_attribute( 'root', array( 'class' => 'avix-tm', 'data-avix-tm' => '' ) );
		if ( '' !== $title ) {
			$this->add_render_attribute( 'root', 'aria-labelledby', 'avix-tm-title-' . $this->get_id() );
		}
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="avix-tm__inner">
				<header class="avix-tm__head">
					<div class="avix-tm__head-main">
						<?php if ( '' !== trim( (string) $s['eyebrow'] ) ) : ?>
							<p class="avix-tm__eyebrow"><?php echo esc_html( $s['eyebrow'] ); ?></p>
						<?php endif; ?>
						<?php if ( '' !== $title ) : ?>
							<<?php echo esc_attr( $tag ); ?> class="avix-tm__title" id="avix-tm-title-<?php echo esc_attr( $this->get_id() ); ?>"><?php echo $this->accent_html( $title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in accent_html(). ?></<?php echo esc_attr( $tag ); ?>>
						<?php endif; ?>
					</div>
					<?php if ( '' !== trim( (string) $s['text'] ) ) : ?>
						<p class="avix-tm__text"><?php echo esc_html( $s['text'] ); ?></p>
					<?php endif; ?>
				</header>

				<ul class="avix-tm__grid" role="list">
					<?php foreach ( $members as $i => $member ) : ?>
						<li class="avix-tm__item" style="--i:<?php echo (int) $i; ?>">
							<?php $this->render_member( $member, $i ); ?>
						</li>
					<?php endforeach; ?>
					<?php if ( $pal ) : ?>
						<li class="avix-tm__item avix-tm__item--pal" style="--i:<?php echo (int) count( $members ); ?>">
							<div class="avix-tm__card avix-tm__card--pal" data-tm-pal-card>
								<div class="avix-tm__stage" aria-hidden="true">
									<span class="avix-tm__ledge"></span>
									<span class="avix-tm__pal" data-tm-pal>
										<svg viewBox="0 0 10 12" focusable="false">
											<rect class="avix-tm__b-leg avix-tm__b-leg--l" x="2" y="7" width="2" height="5"/>
											<rect class="avix-tm__b-leg avix-tm__b-leg--r" x="6" y="7" width="2" height="5"/>
											<rect class="avix-tm__b-body" x="2" y="3" width="6" height="4"/>
											<rect class="avix-tm__b-head" x="3" y="0" width="4" height="3"/>
											<rect class="avix-tm__b-arm avix-tm__b-arm--l" x="0" y="3" width="2" height="3"/>
											<rect class="avix-tm__b-arm avix-tm__b-arm--r" x="8" y="3" width="2" height="3"/>
										</svg>
									</span>
								</div>
								<div class="avix-tm__caption">
									<p class="avix-tm__name"><?php echo esc_html( $s['pal_title'] ); ?></p>
									<?php if ( '' !== trim( (string) $s['pal_text'] ) ) : ?>
										<p class="avix-tm__role"><?php echo esc_html( $s['pal_text'] ); ?></p>
									<?php endif; ?>
								</div>
							</div>
						</li>
					<?php endif; ?>
				</ul>
			</div>
		</section>
		<?php
	}

	private function render_member( array $member, $index ) {
		$name  = trim( (string) ( $member['name'] ?? '' ) );
		$role  = trim( (string) ( $member['role'] ?? '' ) );
		$link  = (array) ( $member['link'] ?? array() );
		$tag   = empty( $link['url'] ) ? 'div' : 'a';
		$key   = 'member-' . $index;
		$photo = (array) ( $member['photo'] ?? array() );
		$alt   = implode( ', ', array_filter( array( $name, $role ) ) );

		$this->add_render_attribute( $key, array( 'class' => 'avix-tm__card', 'data-tm-card' => '' ) );
		if ( 'a' === $tag ) {
			$this->add_link_attributes( $key, $link );
		}
		?>
		<<?php echo esc_attr( $tag ); ?> <?php $this->print_render_attribute_string( $key ); ?>>
			<span class="avix-tm__photo">
				<?php
				$id = $this->media_id( $photo );
				if ( $id ) {
					echo wp_get_attachment_image(
						$id,
						'medium_large',
						false,
						array(
							'alt'      => $alt,
							'loading'  => 'lazy',
							'decoding' => 'async',
							'sizes'    => '(max-width: 600px) 50vw, 280px',
						)
					);
				} elseif ( ! empty( $photo['url'] ) ) {
					printf( '<img src="%s" alt="%s" loading="lazy" decoding="async">', esc_url( $photo['url'] ), esc_attr( $alt ) );
				} else {
					echo '<span class="avix-tm__initials" aria-hidden="true">' . esc_html( $this->initials( $name ) ) . '</span>';
				}
				?>
				<?php if ( 'a' === $tag ) : ?>
					<span class="avix-tm__go" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M7 17 17 7M8 7h9v9"/></svg></span>
				<?php endif; ?>
			</span>
			<span class="avix-tm__caption">
				<span class="avix-tm__name"><?php echo esc_html( $name ); ?></span>
				<?php if ( '' !== $role ) : ?>
					<span class="avix-tm__role"><?php echo esc_html( $role ); ?></span>
				<?php endif; ?>
			</span>
		</<?php echo esc_attr( $tag ); ?>>
		<?php
	}

	private function initials( $name ) {
		$parts = preg_split( '/\s+/u', trim( $name ) );
		$out   = '';
		foreach ( array_slice( (array) $parts, 0, 2 ) as $part ) {
			$out .= mb_substr( $part, 0, 1 );
		}
		return mb_strtoupper( $out );
	}

	/**
	 * Escapes the text, then turns [words] into highlighted spans.
	 */
	private function accent_html( $text ) {
		return preg_replace( '/\[(.+?)\]/', '<span class="avix-tm__accent">$1</span>', esc_html( $text ) );
	}
}
