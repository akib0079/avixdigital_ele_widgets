<?php
/**
 * Smart Header: fixed site header with the "Available" notch (where the Avix
 * pixel character hangs out and says hi), a services dropdown with real
 * platform logos, light/dark styles with logo swap, and a full-screen
 * mobile menu.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;

defined( 'ABSPATH' ) || exit;

class Smart_Header extends Widget_Base {

	use Media;

	const LOGO_ON_DARK  = 'https://avixdigital.com/wp-content/uploads/2026/05/Untitled-design244.png';
	const LOGO_ON_LIGHT = 'https://avixdigital.com/wp-content/uploads/2024/09/Untitled-design198.png';
	const SITE          = 'https://avixdigital.com/';

	public function get_name(): string {
		return 'avix-smart-header';
	}

	public function get_title(): string {
		return esc_html__( 'Smart Header', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-header';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'header', 'navbar', 'menu', 'navigation', 'sticky', 'logo', 'avix' );
	}

	public function get_style_depends(): array {
		return array( 'avix-smart-header' );
	}

	public function get_script_depends(): array {
		return array( 'avix-smart-header' );
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/* ------------------------------------------------------------------ */
	/* Controls                                                            */
	/* ------------------------------------------------------------------ */

	protected function register_controls(): void {
		$this->controls_logo();
		$this->controls_notch();
		$this->controls_menu();
		$this->controls_services();
		$this->controls_actions();
		$this->controls_behaviour();
		$this->controls_style();
	}

	private function controls_logo() {
		$this->start_controls_section( 'section_logo', array( 'label' => esc_html__( 'Logo', 'avix-widgets' ) ) );

		$this->add_control(
			'logo_on_dark',
			array(
				'label'       => esc_html__( 'Logo on dark (white logo)', 'avix-widgets' ),
				'description' => esc_html__( 'Shown when the header sits on a dark background.', 'avix-widgets' ),
				'type'        => Controls_Manager::MEDIA,
				'default'     => array( 'url' => self::LOGO_ON_DARK ),
			)
		);

		$this->add_control(
			'logo_on_light',
			array(
				'label'       => esc_html__( 'Logo on light (dark logo)', 'avix-widgets' ),
				'description' => esc_html__( 'Swapped in automatically on light backgrounds. Leave empty to use the one above everywhere.', 'avix-widgets' ),
				'type'        => Controls_Manager::MEDIA,
				'default'     => array( 'url' => self::LOGO_ON_LIGHT ),
			)
		);

		$this->add_control(
			'logo_alt',
			array(
				'label'   => esc_html__( 'Logo name (alt text)', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => 'Avix Digital',
			)
		);

		$this->add_control(
			'logo_link',
			array(
				'label'   => esc_html__( 'Logo link', 'avix-widgets' ),
				'type'    => Controls_Manager::URL,
				'default' => array( 'url' => home_url( '/' ) ),
			)
		);

		$this->add_responsive_control(
			'logo_height',
			array(
				'label'      => esc_html__( 'Logo height', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 20, 'max' => 80 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-sh' => '--sh-logo-h: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_notch() {
		$this->start_controls_section( 'section_notch', array( 'label' => esc_html__( 'Notch & Pixel Character', 'avix-widgets' ) ) );

		$this->add_control(
			'show_notch',
			array(
				'label'   => esc_html__( 'Show the notch', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'notch_text',
			array(
				'label'       => esc_html__( 'Text', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Available for New Projects', 'avix-widgets' ),
				'label_block' => true,
				'condition'   => array( 'show_notch' => 'yes' ),
			)
		);

		$this->add_control(
			'notch_text_mobile',
			array(
				'label'       => esc_html__( 'Shorter text for phones (optional)', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'Available for projects', 'avix-widgets' ),
				'label_block' => true,
				'condition'   => array( 'show_notch' => 'yes' ),
			)
		);

		$this->add_control(
			'notch_dot',
			array(
				'label'     => esc_html__( 'Blinking green dot', 'avix-widgets' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'show_notch' => 'yes' ),
			)
		);

		$this->add_control(
			'notch_link',
			array(
				'label'     => esc_html__( 'Link (optional)', 'avix-widgets' ),
				'type'      => Controls_Manager::URL,
				'condition' => array( 'show_notch' => 'yes' ),
			)
		);

		$this->add_control(
			'show_pal',
			array(
				'label'       => esc_html__( 'Pixel character', 'avix-widgets' ),
				'description' => esc_html__( 'Sits on the notch with its legs dangling, glances at the mouse and says "hi" in pixel letters every few seconds, and whenever the notch is hovered or tapped.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
				'condition'   => array( 'show_notch' => 'yes' ),
			)
		);

		$this->add_control(
			'hi_every',
			array(
				'label'       => esc_html__( 'Says hi every (seconds)', 'avix-widgets' ),
				'description' => esc_html__( 'A little random so it feels alive. 0 = only when hovered or tapped.', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 40, 'step' => 1 ) ),
				'default'     => array( 'size' => 9 ),
				'condition'   => array(
					'show_notch' => 'yes',
					'show_pal'   => 'yes',
				),
			)
		);

		$this->end_controls_section();
	}

	private function controls_menu() {
		$this->start_controls_section( 'section_menu', array( 'label' => esc_html__( 'Menu', 'avix-widgets' ) ) );

		$repeater = new Repeater();
		$repeater->add_control(
			'label',
			array(
				'label'   => esc_html__( 'Label', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Page', 'avix-widgets' ),
			)
		);
		$repeater->add_control(
			'type',
			array(
				'label'   => esc_html__( 'Type', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'link',
				'options' => array(
					'link'     => esc_html__( 'Link', 'avix-widgets' ),
					'services' => esc_html__( 'Services dropdown', 'avix-widgets' ),
				),
			)
		);
		$repeater->add_control(
			'link',
			array(
				'label'       => esc_html__( 'Link', 'avix-widgets' ),
				'description' => esc_html__( 'For the dropdown: optional (e.g. a services overview page).', 'avix-widgets' ),
				'type'        => Controls_Manager::URL,
			)
		);

		$this->add_control(
			'menu',
			array(
				'label'       => esc_html__( 'Menu items', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ label }}}',
				'default'     => array(
					array(
						'label' => 'Home',
						'link'  => array( 'url' => '/' ),
					),
					array(
						'label' => 'About Us',
						'link'  => array( 'url' => self::SITE . 'about-avixdigital/' ),
					),
					array(
						'label' => 'Services',
						'type'  => 'services',
					),
					array(
						'label' => 'Blog',
						'link'  => array( 'url' => self::SITE . 'blog/' ),
					),
					array(
						'label' => 'Pricing',
						'link'  => array( 'url' => self::SITE . 'pricing/' ),
					),
					array(
						'label' => 'Contact us',
						'link'  => array( 'url' => self::SITE . 'contact/' ),
					),
				),
			)
		);

		$this->add_control(
			'mark_current',
			array(
				'label'       => esc_html__( 'Underline the current page', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->end_controls_section();
	}

	private function controls_services() {
		$this->start_controls_section( 'section_services', array( 'label' => esc_html__( 'Services Dropdown', 'avix-widgets' ) ) );

		$repeater = new Repeater();
		$repeater->add_control(
			'icon',
			array(
				'label'   => esc_html__( 'Logo', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'code',
				'options' => array_merge(
					Brand_Icons::options(),
					array(
						'custom' => esc_html__( 'My own icon…', 'avix-widgets' ),
						'none'   => esc_html__( 'None', 'avix-widgets' ),
					)
				),
			)
		);
		$repeater->add_control(
			'icon_custom',
			array(
				'label'     => esc_html__( 'Icon', 'avix-widgets' ),
				'type'      => Controls_Manager::ICONS,
				'condition' => array( 'icon' => 'custom' ),
			)
		);
		$repeater->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Title', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			)
		);
		$repeater->add_control(
			'desc',
			array(
				'label' => esc_html__( 'Description', 'avix-widgets' ),
				'type'  => Controls_Manager::TEXTAREA,
				'rows'  => 2,
			)
		);
		$repeater->add_control(
			'link',
			array(
				'label' => esc_html__( 'Link', 'avix-widgets' ),
				'type'  => Controls_Manager::URL,
			)
		);

		$this->add_control(
			'services',
			array(
				'label'       => esc_html__( 'Services', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => array(
					array(
						'icon'  => 'code',
						'title' => 'Web Development',
						'desc'  => 'Scalable, lightning-fast applications built with the MERN stack and Next.js.',
						'link'  => array( 'url' => self::SITE . 'service/web-development/' ),
					),
					array(
						'icon'  => 'shopify',
						'title' => 'Shopify Plus',
						'desc'  => 'Premium e-commerce architecture utilizing custom Liquid coding and Headless frameworks.',
						'link'  => array( 'url' => self::SITE . 'service/shopify-plus/' ),
					),
					array(
						'icon'  => 'figma',
						'title' => 'UI/UX & Branding',
						'desc'  => 'High-converting bespoke interfaces and strategic brand identity systems.',
						'link'  => array( 'url' => self::SITE . 'service/uiux-and-brand-design/' ),
					),
					array(
						'icon'  => 'wordpress',
						'title' => 'WordPress Development',
						'desc'  => 'Zero-bloat custom PHP themes and speed-optimized visual builder setups.',
						'link'  => array( 'url' => self::SITE . 'service/wordpress-development/' ),
					),
				),
			)
		);

		$this->end_controls_section();
	}

	private function controls_actions() {
		$this->start_controls_section( 'section_actions', array( 'label' => esc_html__( 'Button & Mobile Menu', 'avix-widgets' ) ) );

		$this->add_control(
			'cta_text',
			array(
				'label'   => esc_html__( 'Button text', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Contact Us', 'avix-widgets' ),
			)
		);

		$this->add_control(
			'cta_link',
			array(
				'label'   => esc_html__( 'Button link', 'avix-widgets' ),
				'type'    => Controls_Manager::URL,
				'default' => array( 'url' => self::SITE . 'contact/' ),
			)
		);

		$this->add_control(
			'mobile_heading',
			array(
				'label'     => esc_html__( 'Mobile menu', 'avix-widgets' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'whatsapp_text',
			array(
				'label'   => esc_html__( 'WhatsApp text', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'WhatsApp Us', 'avix-widgets' ),
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
					'nofollow'    => 'on',
				),
			)
		);

		$this->add_control(
			'book_text',
			array(
				'label'   => esc_html__( 'Big button text', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Book an Appointment', 'avix-widgets' ),
			)
		);

		$this->add_control(
			'book_link',
			array(
				'label'   => esc_html__( 'Big button link', 'avix-widgets' ),
				'type'    => Controls_Manager::URL,
				'default' => array(
					'url'         => 'https://calendly.com/akibzawayed0079/meeting-for-quote',
					'is_external' => 'on',
					'nofollow'    => 'on',
				),
			)
		);

		$this->end_controls_section();
	}

	private function controls_behaviour() {
		$this->start_controls_section( 'section_behaviour', array( 'label' => esc_html__( 'Light & Dark', 'avix-widgets' ) ) );

		$this->add_control(
			'top_style',
			array(
				'label'       => esc_html__( 'At the top of the page', 'avix-widgets' ),
				'description' => esc_html__( 'The header is see-through at the top. Auto looks at what is behind it (a dark hero or photo gets white text and the white logo; a light page gets dark text and the dark logo). Add data-avix-header="dark" or "light" to any section to force it.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'auto',
				'options'     => array(
					'auto'  => esc_html__( 'Auto (match the page)', 'avix-widgets' ),
					'dark'  => esc_html__( 'Over a dark background', 'avix-widgets' ),
					'light' => esc_html__( 'Over a light background', 'avix-widgets' ),
				),
			)
		);

		$this->add_control(
			'scrolled_style',
			array(
				'label'       => esc_html__( 'After scrolling', 'avix-widgets' ),
				'description' => esc_html__( 'The frosted bar the header turns into once the page scrolls.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'dark',
				'options'     => array(
					'dark'  => esc_html__( 'Dark', 'avix-widgets' ),
					'light' => esc_html__( 'Light', 'avix-widgets' ),
				),
			)
		);

		$this->add_control(
			'mobile_style',
			array(
				'label'   => esc_html__( 'Mobile menu', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'dark',
				'options' => array(
					'dark'  => esc_html__( 'Dark', 'avix-widgets' ),
					'light' => esc_html__( 'Light', 'avix-widgets' ),
				),
			)
		);

		$this->add_control(
			'hide_on_scroll',
			array(
				'label'       => esc_html__( 'Hide while scrolling down', 'avix-widgets' ),
				'description' => esc_html__( 'Slides away while reading and comes back as soon as the visitor scrolls up.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
			)
		);

		$this->end_controls_section();
	}

	private function controls_style() {
		$this->start_controls_section(
			'style_section',
			array(
				'label' => esc_html__( 'Header', 'avix-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'accent',
			array(
				'label'     => esc_html__( 'Accent', 'avix-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .avix-sh'      => '--sh-accent: {{VALUE}};',
					'{{WRAPPER}} .avix-sh-menu' => '--sh-accent: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'side',
			array(
				'label'      => esc_html__( 'Side spacing', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 160 ),
					'%'  => array( 'min' => 0, 'max' => 12 ),
				),
				'selectors'  => array( '{{WRAPPER}} .avix-sh' => '--sh-side: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'link_typography',
				'label'    => esc_html__( 'Menu links', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-sh .avix-sh__link',
			)
		);

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ */
	/* Render                                                              */
	/* ------------------------------------------------------------------ */

	protected function render(): void {
		$s        = $this->get_settings_for_display();
		$id       = $this->get_id();
		$services = array_values(
			array_filter(
				(array) $s['services'],
				static function ( $row ) {
					return '' !== trim( (string) ( $row['title'] ?? '' ) );
				}
			)
		);
		$every    = isset( $s['hi_every']['size'] ) && '' !== $s['hi_every']['size'] ? (float) $s['hi_every']['size'] : 9;
		$top      = in_array( $s['top_style'], array( 'auto', 'dark', 'light' ), true ) ? $s['top_style'] : 'auto';
		$scrolled = 'light' === $s['scrolled_style'] ? 'light' : 'dark';
		$mobile   = 'light' === $s['mobile_style'] ? 'light' : 'dark';

		$this->add_render_attribute(
			'root',
			array(
				'class'       => array( 'avix-sh', 'avix-sh--on-' . ( 'light' === $top ? 'light' : 'dark' ) ),
				'id'          => 'avix-smart-header',
				'data-avix-sh' => wp_json_encode(
					array(
						'top'      => $top,
						'scrolled' => $scrolled,
						'mobile'   => $mobile,
						'hide'     => 'yes' === $s['hide_on_scroll'],
						'current'  => 'yes' === $s['mark_current'],
						'hiEvery'  => max( 0, min( 60, $every ) ),
					)
				),
			)
		);
		?>
		<header <?php $this->print_render_attribute_string( 'root' ); ?>>
			<?php $this->render_notch( $s ); ?>
			<div class="avix-sh__bar">
				<?php $this->render_logo( $s ); ?>
				<nav class="avix-sh__nav" aria-label="<?php esc_attr_e( 'Main', 'avix-widgets' ); ?>">
					<ul class="avix-sh__menu">
						<?php foreach ( (array) $s['menu'] as $i => $item ) : ?>
							<?php $this->render_menu_item( $item, $i, $services, $id ); ?>
						<?php endforeach; ?>
					</ul>
				</nav>
				<div class="avix-sh__actions">
					<?php if ( '' !== trim( (string) $s['cta_text'] ) ) : ?>
						<?php
						$this->add_link_attributes( 'cta', (array) $s['cta_link'] );
						$this->add_render_attribute( 'cta', 'class', 'avix-sh__cta' );
						?>
						<a <?php $this->print_render_attribute_string( 'cta' ); ?>>
							<span class="avix-sh__cta-text"><?php echo esc_html( $s['cta_text'] ); ?></span>
							<span class="avix-sh__cta-icon" aria-hidden="true"><?php echo $this->arrow_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup. ?></span>
						</a>
					<?php endif; ?>
					<button class="avix-sh__toggle" type="button" data-sh-toggle aria-expanded="false" aria-controls="avix-sh-menu-<?php echo esc_attr( $id ); ?>" aria-label="<?php esc_attr_e( 'Open menu', 'avix-widgets' ); ?>" data-label-open="<?php esc_attr_e( 'Open menu', 'avix-widgets' ); ?>" data-label-close="<?php esc_attr_e( 'Close menu', 'avix-widgets' ); ?>">
						<span class="avix-sh__toggle-line"></span>
						<span class="avix-sh__toggle-line"></span>
						<span class="avix-sh__toggle-line"></span>
					</button>
				</div>
			</div>
		</header>
		<?php
		$this->render_mobile_menu( $s, $services, $id, $mobile );
	}

	private function render_notch( array $s ) {
		if ( 'yes' !== $s['show_notch'] ) {
			return;
		}
		$text  = trim( (string) $s['notch_text'] );
		$short = trim( (string) $s['notch_text_mobile'] );
		$pal   = 'yes' === $s['show_pal'];
		$link  = (array) $s['notch_link'];
		$tag   = empty( $link['url'] ) ? 'div' : 'a';
		$this->add_render_attribute( 'notch', array( 'class' => array( 'avix-sh__notch', $pal ? 'has-pal' : '' ), 'data-sh-notch' => '' ) );
		if ( 'a' === $tag ) {
			$this->add_link_attributes( 'notch', $link );
		}
		?>
		<<?php echo esc_attr( $tag ); ?> <?php $this->print_render_attribute_string( 'notch' ); ?>>
			<?php if ( 'yes' === $s['notch_dot'] ) : ?>
				<span class="avix-sh__dot" aria-hidden="true"></span>
			<?php endif; ?>
			<span class="avix-sh__notch-text<?php echo '' !== $short ? ' has-short' : ''; ?>">
				<span class="avix-sh__notch-full"><?php echo esc_html( $text ); ?></span>
				<?php if ( '' !== $short ) : ?>
					<span class="avix-sh__notch-short" aria-hidden="true"><?php echo esc_html( $short ); ?></span>
				<?php endif; ?>
			</span>
			<?php if ( $pal ) : ?>
				<span class="avix-sh__pal" data-sh-pal aria-hidden="true">
					<svg class="avix-sh__hi" viewBox="0 0 5 5" focusable="false">
						<g class="avix-sh__hi-h"><rect x="0" y="0" width="1" height="5"/><rect x="1" y="2" width="2" height="1"/><rect x="2" y="3" width="1" height="2"/></g>
						<g class="avix-sh__hi-i"><rect x="4" y="0" width="1" height="1"/><rect x="4" y="2" width="1" height="3"/></g>
					</svg>
					<span class="avix-sh__sprite">
						<svg viewBox="0 0 10 12" focusable="false">
							<rect class="avix-sh__b-leg avix-sh__b-leg--l" x="2" y="7" width="2" height="5"/>
							<rect class="avix-sh__b-leg avix-sh__b-leg--r" x="6" y="7" width="2" height="5"/>
							<rect class="avix-sh__b-body" x="2" y="3" width="6" height="4"/>
							<rect class="avix-sh__b-head" x="3" y="0" width="4" height="3"/>
							<rect class="avix-sh__b-arm avix-sh__b-arm--l" x="0" y="3" width="2" height="3"/>
							<rect class="avix-sh__b-arm avix-sh__b-arm--r" x="8" y="3" width="2" height="3"/>
						</svg>
					</span>
				</span>
			<?php endif; ?>
		</<?php echo esc_attr( $tag ); ?>>
		<?php
	}

	private function render_logo( array $s ) {
		$on_dark  = (string) ( $s['logo_on_dark']['url'] ?? '' );
		$on_light = (string) ( $s['logo_on_light']['url'] ?? '' );
		$alt      = trim( (string) $s['logo_alt'] );
		$link     = (array) $s['logo_link'];
		$tag      = empty( $link['url'] ) ? 'span' : 'a';
		$this->add_render_attribute( 'logo', 'class', 'avix-sh__logo' );
		if ( 'a' === $tag ) {
			$this->add_link_attributes( 'logo', $link );
		}
		?>
		<<?php echo esc_attr( $tag ); ?> <?php $this->print_render_attribute_string( 'logo' ); ?>>
			<?php if ( '' !== $on_dark ) : ?>
				<img class="avix-sh__logo-img<?php echo '' !== $on_light ? ' avix-sh__logo-img--on-dark' : ''; ?>" src="<?php echo esc_url( $this->logo_src( (array) $s['logo_on_dark'] ) ); ?>" alt="<?php echo esc_attr( $alt ); ?>" decoding="async">
			<?php endif; ?>
			<?php if ( '' !== $on_light ) : ?>
				<img class="avix-sh__logo-img<?php echo '' !== $on_dark ? ' avix-sh__logo-img--on-light' : ''; ?>" src="<?php echo esc_url( $this->logo_src( (array) $s['logo_on_light'] ) ); ?>" alt="<?php echo esc_attr( '' !== $on_dark ? '' : $alt ); ?>" decoding="async">
			<?php endif; ?>
			<?php if ( '' === $on_dark && '' === $on_light ) : ?>
				<span class="avix-sh__logo-text"><?php echo esc_html( $alt ); ?></span>
			<?php endif; ?>
		</<?php echo esc_attr( $tag ); ?>>
		<?php
	}

	private function logo_src( array $media ) {
		$id  = $this->media_id( $media );
		$src = $id ? wp_get_attachment_image_url( $id, 'medium' ) : '';
		return $src ? $src : (string) ( $media['url'] ?? '' );
	}

	private function render_menu_item( array $item, $index, array $services, $id ) {
		$label = trim( (string) ( $item['label'] ?? '' ) );
		if ( '' === $label ) {
			return;
		}
		$link = (array) ( $item['link'] ?? array() );
		$key  = 'menu-' . $index;

		if ( 'services' === ( $item['type'] ?? 'link' ) && $services ) {
			$panel = 'avix-sh-panel-' . $id;
			?>
			<li class="avix-sh__drop" data-sh-drop>
				<?php if ( ! empty( $link['url'] ) ) : ?>
					<?php
					$this->add_link_attributes( $key, $link );
					$this->add_render_attribute( $key, array( 'class' => 'avix-sh__link avix-sh__drop-btn', 'aria-haspopup' => 'true', 'aria-expanded' => 'false', 'aria-controls' => $panel, 'data-sh-drop-btn' => '' ) );
					?>
					<a <?php $this->print_render_attribute_string( $key ); ?>><?php echo esc_html( $label ); ?><?php echo $this->chevron_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup. ?></a>
				<?php else : ?>
					<button class="avix-sh__link avix-sh__drop-btn" type="button" aria-expanded="false" aria-controls="<?php echo esc_attr( $panel ); ?>" data-sh-drop-btn><?php echo esc_html( $label ); ?><?php echo $this->chevron_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup. ?></button>
				<?php endif; ?>
				<div class="avix-sh__panel" id="<?php echo esc_attr( $panel ); ?>">
					<?php foreach ( $services as $i => $service ) : ?>
						<?php $this->render_service( $service, 'svc-' . $i, 'avix-sh__item' ); ?>
					<?php endforeach; ?>
				</div>
			</li>
			<?php
			return;
		}

		$this->add_link_attributes( $key, $link );
		$this->add_render_attribute( $key, 'class', 'avix-sh__link' );
		?>
		<li><a <?php $this->print_render_attribute_string( $key ); ?>><?php echo esc_html( $label ); ?></a></li>
		<?php
	}

	/**
	 * One service: brand tile + title (+ description on desktop).
	 */
	private function render_service( array $service, $key, $class ) {
		$link = (array) ( $service['link'] ?? array() );
		$tag  = empty( $link['url'] ) ? 'span' : 'a';
		$this->add_render_attribute( $key, 'class', $class );
		if ( 'a' === $tag ) {
			$this->add_link_attributes( $key, $link );
		}
		$desc = 'avix-sh__item' === $class ? trim( (string) ( $service['desc'] ?? '' ) ) : '';
		?>
		<<?php echo esc_attr( $tag ); ?> <?php $this->print_render_attribute_string( $key ); ?>>
			<?php $this->render_service_icon( $service ); ?>
			<span class="avix-sh__item-text">
				<span class="avix-sh__item-title"><?php echo esc_html( $service['title'] ); ?></span>
				<?php if ( '' !== $desc ) : ?>
					<span class="avix-sh__item-desc"><?php echo esc_html( $desc ); ?></span>
				<?php endif; ?>
			</span>
			<?php if ( 'avix-sh__item' === $class ) : ?>
				<span class="avix-sh__item-arrow" aria-hidden="true"><?php echo $this->arrow_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup. ?></span>
			<?php endif; ?>
		</<?php echo esc_attr( $tag ); ?>>
		<?php
	}

	private function render_service_icon( array $service ) {
		$icon = (string) ( $service['icon'] ?? 'code' );
		if ( 'none' === $icon ) {
			return;
		}
		echo '<span class="avix-sh__tile avix-sh__tile--' . esc_attr( $icon ) . '" aria-hidden="true">';
		if ( 'custom' === $icon ) {
			if ( ! empty( $service['icon_custom']['value'] ) ) {
				Icons_Manager::render_icon( $service['icon_custom'], array( 'aria-hidden' => 'true' ) );
			}
		} else {
			echo Brand_Icons::svg( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup, attributes escaped in Brand_Icons::svg().
		}
		echo '</span>';
	}

	private function render_mobile_menu( array $s, array $services, $id, $style ) {
		$index = 0;
		?>
		<div class="avix-sh-menu avix-sh-menu--<?php echo esc_attr( $style ); ?>" id="avix-sh-menu-<?php echo esc_attr( $id ); ?>" data-sh-menu aria-hidden="true">
			<nav class="avix-sh-menu__nav" aria-label="<?php esc_attr_e( 'Mobile', 'avix-widgets' ); ?>">
				<ul class="avix-sh-menu__list">
					<?php foreach ( (array) $s['menu'] as $i => $item ) : ?>
						<?php
						$label = trim( (string) ( $item['label'] ?? '' ) );
						if ( '' === $label ) {
							continue;
						}
						?>
						<li class="avix-sh-menu__item" style="--i:<?php echo (int) $index++; ?>">
							<?php if ( 'services' === ( $item['type'] ?? 'link' ) && $services ) : ?>
								<button class="avix-sh-menu__link avix-sh-menu__acc" type="button" aria-expanded="false" aria-controls="avix-sh-sub-<?php echo esc_attr( $id ); ?>" data-sh-acc>
									<?php echo esc_html( $label ); ?><?php echo $this->chevron_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup. ?>
								</button>
								<div class="avix-sh-menu__sub" id="avix-sh-sub-<?php echo esc_attr( $id ); ?>">
									<div class="avix-sh-menu__sub-inner">
										<?php foreach ( $services as $j => $service ) : ?>
											<?php $this->render_service( $service, 'msvc-' . $j, 'avix-sh-menu__sublink' ); ?>
										<?php endforeach; ?>
									</div>
								</div>
							<?php else : ?>
								<?php
								$key = 'mmenu-' . $i;
								$this->add_link_attributes( $key, (array) ( $item['link'] ?? array() ) );
								$this->add_render_attribute( $key, 'class', 'avix-sh-menu__link' );
								?>
								<a <?php $this->print_render_attribute_string( $key ); ?>><?php echo esc_html( $label ); ?></a>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>

				<div class="avix-sh-menu__actions" style="--i:<?php echo (int) $index; ?>">
					<?php if ( '' !== trim( (string) $s['whatsapp_text'] ) && ! empty( $s['whatsapp_link']['url'] ) ) : ?>
						<?php
						$this->add_link_attributes( 'whatsapp', (array) $s['whatsapp_link'] );
						$this->add_render_attribute( 'whatsapp', 'class', 'avix-sh-menu__whatsapp' );
						?>
						<a <?php $this->print_render_attribute_string( 'whatsapp' ); ?>>
							<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.305-.885-.653-1.482-1.459-1.656-1.756-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.086 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
							<?php echo esc_html( $s['whatsapp_text'] ); ?>
						</a>
					<?php endif; ?>
					<?php if ( '' !== trim( (string) $s['book_text'] ) && ! empty( $s['book_link']['url'] ) ) : ?>
						<?php
						$this->add_link_attributes( 'book', (array) $s['book_link'] );
						$this->add_render_attribute( 'book', 'class', 'avix-sh-menu__book' );
						?>
						<a <?php $this->print_render_attribute_string( 'book' ); ?>><?php echo esc_html( $s['book_text'] ); ?></a>
					<?php endif; ?>
				</div>
			</nav>
		</div>
		<?php
	}

	private function chevron_svg() {
		return '<svg class="avix-sh__chevron" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m6 9 6 6 6-6"/></svg>';
	}

	private function arrow_svg() {
		return '<svg viewBox="0 0 24 24" focusable="false"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>';
	}
}
