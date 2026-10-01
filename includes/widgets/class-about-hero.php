<?php
/** About hero: editable content and a responsive platform-to-check marquee. */
namespace AvixWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;

defined( 'ABSPATH' ) || exit;

class About_Hero extends Widget_Base {
	use Media;
	public function get_name(): string { return 'avix-about-hero'; }
	public function get_title(): string { return esc_html__( 'About Hero', 'avix-widgets' ); }
	public function get_icon(): string { return 'eicon-banner'; }
	public function get_categories(): array { return array( 'avix-digital' ); }
	public function get_keywords(): array { return array( 'about', 'hero', 'banner', 'platforms', 'marquee', 'avix' ); }
	public function get_style_depends(): array { return array( 'avix-about-hero' ); }
	public function get_script_depends(): array { return array( 'avix-about-hero' ); }
	public function has_widget_inner_wrapper(): bool { return false; }

	protected function register_controls(): void {
		$this->start_controls_section( 'content', array( 'label' => esc_html__( 'Hero content', 'avix-widgets' ) ) );
		$this->text( 'eyebrow', 'Eyebrow', 'About Avix Digital' );
		$this->text( 'title', 'Headline', "We turn your biggest ideas\ninto [beautiful digital realities.]", Controls_Manager::TEXTAREA, array( 'description' => esc_html__( 'Use [brackets] for the accent colour. New lines become desktop line breaks; phones reflow automatically.', 'avix-widgets' ) ) );
		$this->add_control( 'title_tag', array( 'label' => esc_html__( 'Headline HTML tag', 'avix-widgets' ), 'type' => Controls_Manager::SELECT, 'default' => 'h1', 'options' => array( 'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'div' => 'div', 'p' => 'p' ) ) );
		$this->text( 'description', 'Description', "From stunning UI/UX designs to powerful Shopify stores, we build\nexperiences people love and drive real growth for your brand.", Controls_Manager::TEXTAREA );
		$this->text( 'button_text', 'Button label', 'Book a free session' );
		$this->add_control( 'button_link', array( 'label' => esc_html__( 'Button link', 'avix-widgets' ), 'type' => Controls_Manager::URL, 'dynamic' => array( 'active' => true ), 'default' => array( 'url' => 'https://calendly.com/akibzawayed0079/meeting-for-quote' ) ) );
		$this->toggle( 'show_arrow', 'Button arrow', 'yes' );
		$this->text( 'note', 'Text below button', 'A conversation with the team that builds your project.' );
		$this->end_controls_section();

		$this->start_controls_section( 'expertise', array( 'label' => esc_html__( 'Expertise marquee', 'avix-widgets' ) ) );
		$this->toggle( 'show_expertise', 'Show expertise section', 'yes' );
		$this->text( 'rail_heading', 'Heading above logos', 'Creative minds. [Technical hands.]' );
		$this->text( 'rail_footer', 'Caption below logos', 'Design, development & support. [One Avix team.]' );
		$this->add_control( 'brand_logo', array( 'label' => esc_html__( 'Central brand logo', 'avix-widgets' ), 'type' => Controls_Manager::MEDIA, 'dynamic' => array( 'active' => true ), 'default' => array( 'url' => AVIX_EW_URL . 'assets/images/avix-mark.webp' ) ) );
		$this->text( 'brand_label', 'Central brand label', 'avix digital' );
		$this->add_control( 'check_logo', array( 'label' => esc_html__( 'Custom check badge (optional)', 'avix-widgets' ), 'description' => esc_html__( 'Leave empty to use the approved scalloped check badge.', 'avix-widgets' ), 'type' => Controls_Manager::MEDIA, 'dynamic' => array( 'active' => true ) ) );
		$items = new Repeater();
		$items->add_control( 'name', array( 'label' => esc_html__( 'Platform name', 'avix-widgets' ), 'type' => Controls_Manager::TEXT, 'dynamic' => array( 'active' => true ), 'default' => 'Platform', 'label_block' => true ) );
		$items->add_control( 'icon', array( 'label' => esc_html__( 'Platform mark', 'avix-widgets' ), 'type' => Controls_Manager::SELECT, 'default' => 'code', 'options' => array_merge( Brand_Icons::options(), array( 'upload' => esc_html__( 'Upload a custom logo', 'avix-widgets' ) ) ) ) );
		$items->add_control( 'logo', array( 'label' => esc_html__( 'Custom logo', 'avix-widgets' ), 'type' => Controls_Manager::MEDIA, 'dynamic' => array( 'active' => true ), 'condition' => array( 'icon' => 'upload' ) ) );
		$items->add_control( 'color', array( 'label' => esc_html__( 'Mark colour (optional)', 'avix-widgets' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} {{CURRENT_ITEM}}' => '--aa-platform-color: {{VALUE}};' ) ) );
		$this->add_control( 'platforms', array( 'label' => esc_html__( 'Platforms', 'avix-widgets' ), 'type' => Controls_Manager::REPEATER, 'fields' => $items->get_controls(), 'title_field' => '{{{ name }}}', 'default' => array( array( 'name' => 'Shopify', 'icon' => 'shopify' ), array( 'name' => 'Webflow', 'icon' => 'webflow' ), array( 'name' => 'WordPress', 'icon' => 'wordpress' ), array( 'name' => 'Custom code', 'icon' => 'code' ) ) ) );
		$this->toggle( 'show_labels', 'Labels in moving row', 'yes' );
		$this->toggle( 'show_mobile_list', 'Persistent platform list on phones', 'yes' );
		$this->toggle( 'animate', 'Animate marquee', 'yes' );
		$this->slider( 'duration', 'Seconds per loop', '--aa-duration', 's', 8, 120, '.avix-about__expertise' );
		$this->toggle( 'pause_hover', 'Pause on hover or keyboard focus', '' );
		$this->text( 'pause_text', 'Pause label', 'Pause animation' );
		$this->text( 'resume_text', 'Resume label', 'Resume animation' );
		$this->end_controls_section();

		$this->section( 'layout_style', 'Section & layout' );
		$this->add_control( 'layout_help', array( 'type' => Controls_Manager::RAW_HTML, 'raw' => esc_html__( 'Set the parent Elementor container to Full Width with zero padding for edge-to-edge layout. This widget contains no header.', 'avix-widgets' ), 'content_classes' => 'elementor-panel-alert elementor-panel-alert-info' ) );
		$this->add_group_control( Group_Control_Background::get_type(), array( 'name' => 'background', 'types' => array( 'classic', 'gradient' ), 'selector' => '{{WRAPPER}} .avix-about' ) );
		$this->add_responsive_control( 'minimum_height', array( 'label' => esc_html__( 'Minimum section height', 'avix-widgets' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'vh', 'svh', 'px' ), 'range' => array( 'vh' => array( 'min' => 30, 'max' => 150 ), 'svh' => array( 'min' => 30, 'max' => 150 ), 'px' => array( 'min' => 200, 'max' => 1600 ) ), 'selectors' => array( '{{WRAPPER}} .avix-about' => 'min-height: {{SIZE}}{{UNIT}};' ) ) );
		foreach ( array(
			'content_width' => array( 'Content maximum width', '--aa-content-max', 500, 1800, '.avix-about' ),
			'section_top' => array( 'Top spacing', '--aa-top', 0, 300, '.avix-about__intro' ),
			'section_bottom' => array( 'Bottom spacing', '--aa-bottom', 0, 200, '.avix-about' ),
			'gutter' => array( 'Content side spacing', '--aa-gutter', 8, 160, '.avix-about__intro' ),
			'description_width' => array( 'Description maximum width', '--aa-description-max', 240, 1200, '.avix-about' ),
			'expertise_width' => array( 'Marquee maximum width', '--aa-expertise-max', 400, 2200, '.avix-about' ),
			'expertise_gap' => array( 'Space before expertise', '--aa-expertise-gap', 0, 200, '.avix-about__expertise' ),
		) as $id => $p ) { $this->slider( $id, $p[0], $p[1], 'px', $p[2], $p[3], $p[4] ); }
		$this->end_controls_section();

		$this->section( 'type_style', 'Headline & supporting text' );
		foreach ( array( 'title' => array( 'Headline', '.avix-about__title' ), 'description' => array( 'Description', '.avix-about__description' ), 'note' => array( 'Button note', '.avix-about__note' ), 'eyebrow' => array( 'Eyebrow', '.avix-about__eyebrow' ) ) as $id => $p ) { $this->typography( $id, $p[0], $p[1] ); }
		foreach ( array( 'title_color' => array( 'Headline colour', '--aa-ink' ), 'highlight_color' => array( 'Headline accent', '--aa-accent' ), 'description_color' => array( 'Description colour', '--aa-muted' ), 'note_color' => array( 'Note colour', '--aa-note-color' ), 'eyebrow_color' => array( 'Eyebrow text', '--aa-eyebrow-ink' ), 'eyebrow_background' => array( 'Eyebrow background', '--aa-eyebrow-bg' ), 'eyebrow_border' => array( 'Eyebrow border', '--aa-eyebrow-border' ), 'eyebrow_dot' => array( 'Eyebrow dot', '--aa-eyebrow-dot' ) ) as $id => $p ) { $this->color( $id, $p[0], $p[1] ); }
		$this->slider( 'description_gap', 'Description spacing', '--aa-description-gap', 'px', 0, 100, '.avix-about__description' );
		$this->slider( 'eyebrow_gap', 'Space after eyebrow', '--aa-eyebrow-gap', 'px', 0, 100, '.avix-about__eyebrow' );
		$this->end_controls_section();

		$this->section( 'button_style', 'Booking button' );
		$this->typography( 'button', 'Button typography', '.avix-about__cta' );
		$this->slider( 'button_gap', 'Space before button', '--aa-button-gap', 'px', 0, 120, '.avix-about__cta' );
		$this->add_responsive_control( 'button_padding', array( 'label' => esc_html__( 'Button padding', 'avix-widgets' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'selectors' => array( '{{WRAPPER}} .avix-about__cta' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->slider( 'button_radius', 'Corner radius', '--aa-button-radius', 'px', 0, 999 );
		$this->slider( 'arrow_size', 'Arrow circle size', '--aa-arrow-size', 'px', 28, 80, '.avix-about__cta-icon' );
		$this->slider( 'arrow_gap', 'Space before arrow', '--aa-arrow-gap', 'px', 4, 40, '.avix-about__cta' );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'button_shadow', 'selector' => '{{WRAPPER}} .avix-about__cta' ) );
		$this->start_controls_tabs( 'button_states' );
		$this->start_controls_tab( 'button_normal', array( 'label' => esc_html__( 'Normal', 'avix-widgets' ) ) );
		foreach ( array( 'button_background' => array( 'Background', '--aa-button-bg' ), 'button_color' => array( 'Text', '--aa-button-ink' ), 'arrow_background' => array( 'Arrow circle', '--aa-arrow-bg' ), 'arrow_color' => array( 'Arrow', '--aa-arrow-ink' ) ) as $id => $p ) { $this->color( $id, $p[0], $p[1] ); }
		$this->end_controls_tab();
		$this->start_controls_tab( 'button_hover', array( 'label' => esc_html__( 'Hover', 'avix-widgets' ) ) );
		foreach ( array( 'button_hover_background' => array( 'Background', '--aa-button-hover-bg' ), 'button_hover_color' => array( 'Text', '--aa-button-hover-ink' ), 'arrow_hover_background' => array( 'Arrow circle', '--aa-arrow-hover-bg' ) ) as $id => $p ) { $this->color( $id, $p[0], $p[1] ); }
		$this->end_controls_tab(); $this->end_controls_tabs(); $this->end_controls_section();

		$this->section( 'tile_style', 'Central logo tile' );
		foreach ( array( 'tile_width' => array( 'Tile width', '--aa-hub-w', 72, 280 ), 'tile_height' => array( 'Tile height', '--aa-hub-h', 72, 320 ), 'tile_logo_size' => array( 'Brand logo size', '--aa-brand-size', 24, 160 ), 'tile_radius' => array( 'Corner radius', '--aa-hub-radius', 0, 120 ), 'tile_ring' => array( 'Clearance around tile', '--aa-hub-ring', 0, 40 ) ) as $id => $p ) { $this->slider( $id, $p[0], $p[1], 'px', $p[2], $p[3], '.avix-about__expertise' ); }
		$this->color( 'tile_background', 'Tile background', '--aa-hub-bg' );
		$this->color( 'tile_label_color', 'Brand label', '--aa-brand-ink' );
		$this->typography( 'brand', 'Brand label typography', '.avix-about__hub span' );
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'tile_border', 'selector' => '{{WRAPPER}} .avix-about__hub' ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'tile_shadow', 'selector' => '{{WRAPPER}} .avix-about__hub' ) );
		$this->end_controls_section();

		$this->section( 'marquee_style', 'Marquee & captions' );
		foreach ( array( 'platform_spacing' => array( 'Platform slot width', '--aa-item', 72, 320 ), 'platform_size' => array( 'Platform logo size', '--aa-logo-size', 16, 100 ), 'check_size' => array( 'Check badge size', '--aa-check-size', 16, 100 ) ) as $id => $p ) { $this->slider( $id, $p[0], $p[1], 'px', $p[2], $p[3], '.avix-about__expertise' ); }
		foreach ( array( 'check_color' => array( 'Check badge colour', '--pf-icon-color' ), 'row_background' => array( 'Row surface / fade colour', '--aa-rail-surface' ), 'line_color' => array( 'Connector line', '--aa-line' ), 'platform_label_color' => array( 'Platform labels', '--aa-platform-ink' ), 'rail_heading_color' => array( 'Heading above logos', '--aa-rail-heading-ink' ), 'rail_heading_accent' => array( 'Bracketed heading text', '--aa-rail-heading-accent' ), 'rail_footer_color' => array( 'Footer caption', '--aa-rail-footer-ink' ), 'rail_footer_accent' => array( 'Bracketed caption text', '--aa-rail-footer-accent' ), 'pause_color' => array( 'Pause control', '--aa-pause-ink' ) ) as $id => $p ) { $this->color( $id, $p[0], $p[1] ); }
		foreach ( array( 'platform_labels' => array( 'Platform labels', '.avix-about__platform-name, {{WRAPPER}} .avix-about__platforms' ), 'rail_heading' => array( 'Heading above logos', '.avix-about__rail-heading p' ), 'rail_footer' => array( 'Footer caption', '.avix-about__rail-footer p' ), 'pause' => array( 'Pause control', '.avix-about__pause' ) ) as $id => $p ) { $this->typography( $id, $p[0], $p[1] ); }
		$this->end_controls_section();
	}

	private function text( $id, $label, $default, $type = Controls_Manager::TEXT, $extra = array() ) { $this->add_control( $id, array_merge( array( 'label' => esc_html__( $label, 'avix-widgets' ), 'type' => $type, 'default' => $default, 'dynamic' => array( 'active' => true ), 'label_block' => true ), $extra ) ); }
	private function toggle( $id, $label, $default ) { $this->add_control( $id, array( 'label' => esc_html__( $label, 'avix-widgets' ), 'type' => Controls_Manager::SWITCHER, 'default' => $default ) ); }
	private function section( $id, $label ) { $this->start_controls_section( $id, array( 'label' => esc_html__( $label, 'avix-widgets' ), 'tab' => Controls_Manager::TAB_STYLE ) ); }
	private function color( $id, $label, $variable ) { $this->add_control( $id, array( 'label' => esc_html__( $label, 'avix-widgets' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .avix-about' => $variable . ': {{VALUE}};' ) ) ); }
	private function slider( $id, $label, $variable, $unit, $min, $max, $selector = '.avix-about' ) { $this->add_responsive_control( $id, array( 'label' => esc_html__( $label, 'avix-widgets' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( $unit ), 'range' => array( $unit => array( 'min' => $min, 'max' => $max ) ), 'selectors' => array( '{{WRAPPER}} ' . $selector => $variable . ': {{SIZE}}{{UNIT}};' ) ) ); }
	private function typography( $id, $label, $selector ) { $this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => $id . '_typography', 'label' => esc_html__( $label, 'avix-widgets' ), 'selector' => '{{WRAPPER}} ' . $selector ) ); }

	/** Escape all text before adding our own accent spans and line breaks. */
	private function formatted( $text ) {
		$html = preg_replace( '/\[([^\[\]]+)\]/u', '<span class="avix-about__accent">$1</span>', esc_html( (string) $text ) );
		return str_replace( array( "\r\n", "\r", "\n" ), ' <br class="avix-about__desktop-break">', $html );
	}
	private function image( $media, $class ) {
		$media = (array) $media; $id = $this->media_id( $media );
		if ( $id ) { echo wp_get_attachment_image( $id, 'medium', false, array( 'class' => $class, 'alt' => '', 'loading' => 'eager', 'decoding' => 'async' ) ); }
		elseif ( ! empty( $media['url'] ) && esc_url( $media['url'] ) ) { echo '<img class="' . esc_attr( $class ) . '" src="' . esc_url( $media['url'] ) . '" alt="" width="160" height="160" decoding="async">'; }
	}
	private function check( $media ) {
		if ( ! empty( $media['url'] ) || ! empty( $media['id'] ) ) { $this->image( $media, 'avix-about__check-image' ); return; }
		static $badge;
		if ( null === $badge ) { $badge = file_get_contents( AVIX_EW_PATH . 'assets/images/check-badge.svg' ); }
		echo $badge; // Trusted bundled SVG only, never user-supplied source.
	}
	private function platform( $item, $outgoing, $check ) {
		echo '<div class="avix-about__platform elementor-repeater-item-' . esc_attr( sanitize_html_class( $item['_id'] ?? '' ) ) . '"><span class="avix-about__platform-icon">';
		if ( $outgoing ) { $this->check( $check ); }
		elseif ( 'upload' === ( $item['icon'] ?? '' ) && ( ! empty( $item['logo']['url'] ) || ! empty( $item['logo']['id'] ) ) ) { $this->image( $item['logo'], 'avix-about__platform-image' ); }
		else { $svg = Brand_Icons::svg( $item['icon'] ?? 'code' ); echo preg_replace( '/fill="(#[a-fA-F0-9]+)"/', 'fill="var(--aa-platform-color, $1)"', $svg ?: Brand_Icons::svg( 'code' ) ); }
		echo '</span><span class="avix-about__platform-name">' . esc_html( $item['name'] ) . '</span></div>';
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();
		$title = (string) ( $s['title'] ?? '' );
		$tag = in_array( $s['title_tag'] ?? '', array( 'h1', 'h2', 'h3', 'div', 'p' ), true ) ? $s['title_tag'] : 'h1';
		$id = 'avix-about-title-' . $this->get_id();
		$items = array_values( array_filter( (array) ( $s['platforms'] ?? array() ), static function ( $item ) { return is_array( $item ) && '' !== trim( (string) ( $item['name'] ?? '' ) ); } ) );
		$config = array( 'animate' => 'yes' === ( $s['animate'] ?? '' ), 'pauseHover' => 'yes' === ( $s['pause_hover'] ?? '' ), 'pause' => (string) ( $s['pause_text'] ?? 'Pause animation' ), 'resume' => (string) ( $s['resume_text'] ?? 'Resume animation' ) );
		$classes = 'avix-about' . ( 'yes' !== ( $s['show_labels'] ?? '' ) ? ' avix-about--no-labels' : '' ) . ( 'yes' !== ( $s['show_mobile_list'] ?? '' ) ? ' avix-about--no-mobile-list' : '' );
		?>
		<section class="<?php echo esc_attr( $classes ); ?>" data-avix-about="<?php echo esc_attr( wp_json_encode( $config ) ); ?>" <?php echo '' !== trim( $title ) ? 'aria-labelledby="' . esc_attr( $id ) . '"' : 'aria-label="' . esc_attr__( 'About Avix Digital', 'avix-widgets' ) . '"'; ?>>
			<div class="avix-about__intro">
				<?php if ( ! empty( $s['eyebrow'] ) ) : ?><p class="avix-about__eyebrow"><span aria-hidden="true"></span><?php echo esc_html( $s['eyebrow'] ); ?></p><?php endif; ?>
				<?php if ( '' !== trim( $title ) ) : ?><<?php echo $tag; ?> class="avix-about__title" id="<?php echo esc_attr( $id ); ?>"><?php echo $this->formatted( $title ); ?></<?php echo $tag; ?>><?php endif; ?>
				<?php if ( ! empty( $s['description'] ) ) : ?><p class="avix-about__description"><?php echo $this->formatted( $s['description'] ); ?></p><?php endif; ?>
				<?php if ( ! empty( $s['button_text'] ) && ! empty( $s['button_link']['url'] ) && esc_url( $s['button_link']['url'] ) ) :
					$link = $s['button_link']; $link['url'] = esc_url( $link['url'] ); $this->add_link_attributes( 'cta', $link ); $this->add_render_attribute( 'cta', 'class', 'avix-about__cta' ); ?>
					<a <?php $this->print_render_attribute_string( 'cta' ); ?>><span class="avix-about__cta-text"><?php echo esc_html( $s['button_text'] ); ?></span><?php if ( 'yes' === ( $s['show_arrow'] ?? '' ) ) : ?><span class="avix-about__cta-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M7 17 17 7M7 7h10v10"/></svg></span><?php endif; ?></a>
				<?php endif; ?>
				<?php if ( ! empty( $s['note'] ) ) : ?><p class="avix-about__note"><?php echo esc_html( $s['note'] ); ?></p><?php endif; ?>
			</div>
			<?php if ( 'yes' === ( $s['show_expertise'] ?? '' ) && $items ) : ?>
			<div class="avix-about__expertise">
				<?php if ( ! empty( $s['rail_heading'] ) ) : ?><div class="avix-about__rail-heading"><p><?php echo $this->formatted( $s['rail_heading'] ); ?></p></div><?php endif; ?>
				<div class="avix-about__marquee" aria-hidden="true">
					<div class="avix-about__line"></div>
					<?php foreach ( array( 'incoming', 'outgoing' ) as $lane ) : ?><div class="avix-about__lane avix-about__lane--<?php echo esc_attr( $lane ); ?>"><div class="avix-about__track" data-track="<?php echo esc_attr( $lane ); ?>"><div class="avix-about__group"><?php foreach ( $items as $item ) { $this->platform( $item, 'outgoing' === $lane, $s['check_logo'] ?? array() ); } ?></div></div></div><?php endforeach; ?>
					<div class="avix-about__hub"><?php $this->image( $s['brand_logo'] ?? array(), 'avix-about__brand-image' ); ?><?php if ( ! empty( $s['brand_label'] ) ) : ?><span><?php echo esc_html( $s['brand_label'] ); ?></span><?php endif; ?></div>
				</div>
				<ul class="avix-about__platforms" aria-label="<?php esc_attr_e( 'Our development expertise', 'avix-widgets' ); ?>"><?php foreach ( $items as $item ) : ?><li><span class="avix-about__static-check" aria-hidden="true">✓</span><?php echo esc_html( $item['name'] ); ?></li><?php endforeach; ?></ul>
				<div class="avix-about__rail-footer">
					<?php if ( ! empty( $s['rail_footer'] ) ) : ?><p><?php echo $this->formatted( $s['rail_footer'] ); ?></p><?php endif; ?>
					<button class="avix-about__pause" type="button" aria-label="<?php echo esc_attr( $config['pause'] ); ?>" aria-pressed="false" hidden><svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path class="pause-icon" d="M7 5v10M13 5v10"/><path class="play-icon" d="m7 4 9 6-9 6Z"/></svg><span><?php echo esc_html( $config['pause'] ); ?></span></button>
				</div>
			</div>
			<?php endif; ?>
		</section>
		<?php
	}
}
