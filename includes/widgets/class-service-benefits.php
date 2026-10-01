<?php
/** Editable service benefits with responsive cards and a finite avatar greeting. */
namespace AvixWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;

defined( 'ABSPATH' ) || exit;

class Service_Benefits extends Widget_Base {
	use Media;
	public function get_name(): string { return 'avix-service-benefits'; }
	public function get_title(): string { return esc_html__( 'Service Benefits', 'avix-widgets' ); }
	public function get_icon(): string { return 'eicon-posts-grid'; }
	public function get_categories(): array { return array( 'avix-digital' ); }
	public function get_keywords(): array { return array( 'services', 'benefits', 'cards', 'about', 'avix' ); }
	public function get_style_depends(): array { return array( 'avix-service-benefits' ); }
	public function get_script_depends(): array { return array( 'avix-service-benefits' ); }
	public function has_widget_inner_wrapper(): bool { return false; }
	protected function is_dynamic_content(): bool { return true; }

	protected function register_controls(): void {
		$this->start_controls_section( 'intro', array( 'label' => __( 'Introduction', 'avix-widgets' ) ) );
		$this->text( 'eyebrow_one', 'First eyebrow', 'Made for your business' );
		$this->text( 'eyebrow_two', 'Second eyebrow', 'Built around your customers' );
		$this->text( 'title', 'Heading', "Websites, stores & brands.\nBuilt around [your business.]", Controls_Manager::TEXTAREA, array( 'description' => __( 'Use [brackets] for accent text. New lines create desktop breaks; smaller layouts reflow.', 'avix-widgets' ) ) );
		$this->add_control( 'title_tag', array( 'label' => __( 'Heading HTML tag', 'avix-widgets' ), 'type' => Controls_Manager::SELECT, 'default' => 'h2', 'options' => array( 'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'div' => 'div' ) ) );
		$this->text( 'description', 'Description', 'Our web design, Shopify development and UI/UX services help people understand your brand, find what they need, and take the next step.', Controls_Manager::TEXTAREA );
		$this->end_controls_section();

		$this->start_controls_section( 'services', array( 'label' => __( 'Service cards', 'avix-widgets' ) ) );
		$items = new Repeater();
		foreach ( array( 'title' => array( 'Title', 'Service', Controls_Manager::TEXT ), 'description' => array( 'Benefit description', '', Controls_Manager::TEXTAREA ), 'image_alt' => array( 'Image alternative text', '', Controls_Manager::TEXT ), 'link_text' => array( 'Link label', 'Explore this service', Controls_Manager::TEXT ) ) as $id => $p ) {
			$items->add_control( $id, array( 'label' => __( $p[0], 'avix-widgets' ), 'type' => $p[2], 'default' => $p[1], 'dynamic' => array( 'active' => true ), 'label_block' => true ) );
		}
		$items->add_control( 'image', array( 'label' => __( 'Artwork', 'avix-widgets' ), 'type' => Controls_Manager::MEDIA, 'dynamic' => array( 'active' => true ) ) );
		$items->add_control( 'link', array( 'label' => __( 'Service link', 'avix-widgets' ), 'type' => Controls_Manager::URL, 'dynamic' => array( 'active' => true ) ) );
		$items->add_control( 'accent', array( 'label' => __( 'Link hover colour (optional)', 'avix-widgets' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} {{CURRENT_ITEM}}' => '--ab-link-hover: {{VALUE}};' ) ) );
		$this->add_control( 'cards', array( 'label' => __( 'Services', 'avix-widgets' ), 'type' => Controls_Manager::REPEATER, 'fields' => $items->get_controls(), 'title_field' => '{{{ title }}}', 'default' => array(
			array( 'title' => 'Shopify & e-commerce', 'description' => 'A smoother path to purchase. Custom Shopify and Shopify Plus stores built for easier product discovery, confident checkout and day-to-day management.', 'image' => array( 'url' => AVIX_EW_URL . 'assets/images/service-benefits/service-3-768.webp' ), 'image_alt' => 'Avix Digital illustration of a new Shopify store project notification', 'link_text' => 'Explore Shopify development', 'link' => array( 'url' => 'https://avixdigital.com/service/shopify-plus/' ) ),
			array( 'title' => 'Web development', 'description' => 'A website your team can grow with. Responsive Webflow, WordPress and custom builds with fast pages, clear content and a solid technical SEO foundation.', 'image' => array( 'url' => AVIX_EW_URL . 'assets/images/service-benefits/service-2-768.webp' ), 'image_alt' => 'Avix Digital illustration of an upward performance trend', 'link_text' => 'Explore web development', 'link' => array( 'url' => 'https://avixdigital.com/service/web-development/' ) ),
			array( 'title' => 'UI/UX & brand design', 'description' => 'A clearer story for your brand. UI/UX design and visual identity that help customers recognise you, find their way and feel confident taking the next step.', 'image' => array( 'url' => AVIX_EW_URL . 'assets/images/service-benefits/service-1-768.webp' ), 'image_alt' => 'Illustration of website, storefront and performance interfaces in Avix orange', 'link_text' => 'Explore UI/UX & branding', 'link' => array( 'url' => 'https://avixdigital.com/service/uiux-and-brand-design/' ) ),
		) ) );
		$this->add_control( 'card_tag', array( 'label' => __( 'Card heading HTML tag', 'avix-widgets' ), 'type' => Controls_Manager::SELECT, 'default' => 'h3', 'options' => array( 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'div' => 'div' ) ) );
		$this->toggle( 'show_arrows', 'Show link arrows', 'yes' );
		$this->end_controls_section();

		$this->start_controls_section( 'guide', array( 'label' => __( 'Team guide & interaction', 'avix-widgets' ) ) );
		$this->toggle( 'show_help', 'Show team guide', 'yes' );
		$this->toggle( 'show_avatar', 'Show avatar', 'yes' );
		$this->add_control( 'avatar', array( 'label' => __( 'Custom avatar (optional)', 'avix-widgets' ), 'description' => __( 'Leave empty for the existing Avix pixel character. The built-in character supports the greeting animation.', 'avix-widgets' ), 'type' => Controls_Manager::MEDIA, 'dynamic' => array( 'active' => true ) ) );
		$this->text( 'help_title', 'Guide heading', 'Not sure where to start?' );
		$this->text( 'help_text', 'Guide text', 'We’ll help you choose the right service.' );
		$this->text( 'help_link_text', 'Guide link label', 'Talk to our team' );
		$this->add_control( 'help_link', array( 'label' => __( 'Guide link', 'avix-widgets' ), 'type' => Controls_Manager::URL, 'dynamic' => array( 'active' => true ), 'default' => array( 'url' => 'https://avixdigital.com/contact/' ) ) );
		$this->toggle( 'hover_motion', 'Subtle card lift & image zoom', 'yes' );
		$this->toggle( 'avatar_motion', 'Brief avatar greeting', 'yes' );
		$this->end_controls_section();

		$this->section( 'layout', 'Section & layout' );
		$this->add_control( 'layout_note', array( 'type' => Controls_Manager::RAW_HTML, 'raw' => __( 'Use a full-width parent container with zero padding. Cards adapt to the space available, including narrow Elementor columns.', 'avix-widgets' ), 'content_classes' => 'elementor-panel-alert elementor-panel-alert-info' ) );
		$this->add_group_control( Group_Control_Background::get_type(), array( 'name' => 'background', 'types' => array( 'classic', 'gradient' ), 'selector' => '{{WRAPPER}} .avix-benefits' ) );
		$this->dimensions( 'section_padding', 'Section padding', '.avix-benefits', 'padding' );
		foreach ( array( 'width' => array( 'Content maximum width', '--ab-max', 300, 1800 ), 'intro_width' => array( 'Introduction maximum width', '--ab-intro-max', 240, 1400 ), 'lead_width' => array( 'Description maximum width', '--ab-lead-max', 200, 1200 ), 'gap' => array( 'Card gap', '--ab-gap', 0, 100 ), 'grid_gap' => array( 'Space before cards', '--ab-grid-gap', 0, 150 ), 'help_gap' => array( 'Space before team guide', '--ab-help-gap', 0, 150 ) ) as $id => $p ) { $this->slider( $id, $p[0], $p[1], $p[2], $p[3] ); }
		$this->add_control( 'columns', array( 'label' => __( 'Desktop columns', 'avix-widgets' ), 'description' => __( 'Blank preserves the approved responsive layout. Tablet and mobile layouts automatically use one card per row.', 'avix-widgets' ), 'type' => Controls_Manager::SELECT, 'options' => array( '' => 'Automatic', '1' => '1', '2' => '2', '3' => '3', '4' => '4' ), 'selectors' => array( '{{WRAPPER}} .avix-benefits' => '--ab-columns: {{VALUE}};' ) ) );
		$this->end_controls_section();

		$this->section( 'type', 'Typography & colours' );
		foreach ( array( 'title' => array( 'Heading', '.avix-benefits__title' ), 'lead' => array( 'Introduction text', '.avix-benefits__lead' ), 'eyebrows' => array( 'Eyebrows', '.avix-benefits__eyebrows p' ), 'card_title' => array( 'Card titles', '.avix-benefits__card-title' ), 'description' => array( 'Card descriptions', '.avix-benefits__description' ), 'link' => array( 'Service links', '.avix-benefits__link' ), 'help' => array( 'Guide text', '.avix-benefits__help p' ) ) as $id => $p ) {
			$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => $id . '_typography', 'label' => __( $p[0], 'avix-widgets' ), 'selector' => '{{WRAPPER}} .avix-benefits ' . $p[1] ) );
		}
		foreach ( array( 'ink' => array( 'Headings', '--ab-ink' ), 'muted' => array( 'Body text', '--ab-muted' ), 'accent' => array( 'Heading accent', '--ab-accent' ), 'orange' => array( 'Eyebrow icons & avatar', '--ab-orange' ), 'link_colour' => array( 'Link text', '--ab-link-ink' ), 'link_hover' => array( 'Link hover & focus', '--ab-link-hover' ) ) as $id => $p ) { $this->color( $id, $p[0], $p[1] ); }
		$this->end_controls_section();

		$this->section( 'card_style', 'Cards & artwork' );
		$this->dimensions( 'card_padding', 'Card padding', '.avix-benefits__card', 'padding' );
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'card_border', 'selector' => '{{WRAPPER}} .avix-benefits__card' ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'card_shadow', 'selector' => '{{WRAPPER}} .avix-benefits__card' ) );
		$this->color( 'card_background', 'Card background', '--ab-card-bg' );
		$this->color( 'card_hover_border', 'Hover / focus border', '--ab-hover-border' );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'card_hover_shadow', 'selector' => '{{WRAPPER}} .avix-benefits__card:hover' ) );
		foreach ( array( 'card_radius' => array( 'Card corner radius', '--ab-card-radius', 0, 80 ), 'image_radius' => array( 'Image corner radius', '--ab-image-radius', 0, 80 ), 'image_gap' => array( 'Space above artwork', '--ab-image-gap', 0, 100 ), 'description_gap' => array( 'Space above card description', '--ab-description-gap', 0, 100 ), 'avatar_size' => array( 'Avatar width', '--ab-avatar-size', 20, 100 ) ) as $id => $p ) { $this->slider( $id, $p[0], $p[1], $p[2], $p[3] ); }
		$this->color( 'image_background', 'Artwork background', '--ab-image-bg' );
		$this->add_responsive_control( 'image_ratio', array( 'label' => __( 'Artwork aspect ratio', 'avix-widgets' ), 'type' => Controls_Manager::SELECT, 'options' => array( '' => 'Default (3:2)', '1 / 1' => '1:1', '3 / 2' => '3:2', '4 / 3' => '4:3', '16 / 9' => '16:9' ), 'selectors' => array( '{{WRAPPER}} .avix-benefits' => '--ab-image-ratio: {{VALUE}};' ) ) );
		$this->add_control( 'image_fit', array( 'label' => __( 'Artwork fit', 'avix-widgets' ), 'type' => Controls_Manager::SELECT, 'default' => 'contain', 'options' => array( 'contain' => 'Contain (preserve artwork)', 'cover' => 'Cover (crop to fill)' ), 'selectors' => array( '{{WRAPPER}} .avix-benefits__visual img' => 'object-fit: {{VALUE}};' ) ) );
		$this->end_controls_section();
	}

	private function text( $id, $label, $default, $type = Controls_Manager::TEXT, $extra = array() ) { $this->add_control( $id, array_merge( array( 'label' => __( $label, 'avix-widgets' ), 'type' => $type, 'default' => $default, 'dynamic' => array( 'active' => true ), 'label_block' => true ), $extra ) ); }
	private function toggle( $id, $label, $default ) { $this->add_control( $id, array( 'label' => __( $label, 'avix-widgets' ), 'type' => Controls_Manager::SWITCHER, 'default' => $default ) ); }
	private function section( $id, $label ) { $this->start_controls_section( $id, array( 'label' => __( $label, 'avix-widgets' ), 'tab' => Controls_Manager::TAB_STYLE ) ); }
	private function color( $id, $label, $variable ) { $this->add_control( $id, array( 'label' => __( $label, 'avix-widgets' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .avix-benefits' => $variable . ': {{VALUE}};' ) ) ); }
	private function slider( $id, $label, $variable, $min, $max ) { $this->add_responsive_control( $id, array( 'label' => __( $label, 'avix-widgets' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => $min, 'max' => $max ) ), 'selectors' => array( '{{WRAPPER}} .avix-benefits' => $variable . ': {{SIZE}}{{UNIT}};' ) ) ); }
	private function dimensions( $id, $label, $selector, $property ) { $this->add_responsive_control( $id, array( 'label' => __( $label, 'avix-widgets' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em', '%' ), 'selectors' => array( '{{WRAPPER}} ' . $selector => $property . ': {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) ); }
	private function formatted( $text ) { return str_replace( array( "\r\n", "\r", "\n" ), ' <br class="avix-benefits__desktop-break">', preg_replace( '/\[([^\[\]]+)\]/u', '<span>$1</span>', esc_html( (string) $text ) ) ); }
	private function arrow() { echo '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 17 17 7M7 7h10v10"/></svg>'; }
	private function link( $key, $text, $link, $arrow, $help = false ) {
		$link = (array) $link;
		if ( '' === trim( (string) $text ) || empty( $link['url'] ) || ! esc_url( $link['url'] ) ) { return; }
		$link['url'] = esc_url( $link['url'] );
		$this->add_link_attributes( $key, $link );
		if ( ! empty( $link['is_external'] ) ) { $this->add_render_attribute( $key, 'rel', 'noopener noreferrer' ); }
		$this->add_render_attribute( $key, 'class', 'avix-benefits__link' . ( $help ? ' avix-benefits__link--help' : '' ) );
		echo '<a '; $this->print_render_attribute_string( $key ); echo '><span>' . esc_html( $text ) . '</span>';
		if ( $arrow ) { $this->arrow(); }
		echo '</a>';
	}
	private function image( $media, $alt, $class = '' ) {
		$media = (array) $media; $id = $this->media_id( $media );
		if ( $id ) {
			$attrs = array( 'class' => $class, 'loading' => 'lazy', 'decoding' => 'async' );
			if ( '' !== (string) $alt ) { $attrs['alt'] = $alt; }
			echo wp_get_attachment_image( $id, 'large', false, $attrs );
		} elseif ( ! empty( $media['url'] ) && esc_url( $media['url'] ) ) {
			$url = $media['url']; $srcset = '';
			// Only our own bundled artwork has predictable derivatives.
			if ( preg_match( '~^' . preg_quote( AVIX_EW_URL, '~' ) . 'assets/images/service-benefits/(service-[123])-768\.webp$~', $url, $match ) ) {
				foreach ( array( 384, 768, 1152 ) as $width ) { $srcset .= ( $srcset ? ', ' : '' ) . AVIX_EW_URL . 'assets/images/service-benefits/' . $match[1] . '-' . $width . '.webp ' . $width . 'w'; }
			}
			echo '<img class="' . esc_attr( $class ) . '" src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy" decoding="async"' . ( $srcset ? ' width="1536" height="1024" srcset="' . esc_attr( $srcset ) . '" sizes="auto, (max-width: 600px) 100vw, 50vw"' : '' ) . '>';
		}
	}
	private function avatar() {
		echo '<svg viewBox="0 0 10 11" focusable="false"><rect x="2" y="7" width="2" height="4"/><rect x="6" y="7" width="2" height="4"/><rect x="2" y="3" width="6" height="4"/><rect x="3" y="0" width="4" height="3"/><rect x="0" y="4" width="2" height="3"/><rect class="avix-benefits__arm" x="8" y="4" width="2" height="3"/></svg>';
	}
	protected function render(): void {
		$s = $this->get_settings_for_display();
		$tag = in_array( $s['title_tag'] ?? '', array( 'h1', 'h2', 'h3', 'div' ), true ) ? $s['title_tag'] : 'h2';
		$card_tag = in_array( $s['card_tag'] ?? '', array( 'h2', 'h3', 'h4', 'div' ), true ) ? $s['card_tag'] : 'h3';
		$id = 'avix-benefits-title-' . $this->get_id();
		$has_title = '' !== trim( (string) ( $s['title'] ?? '' ) );
		$config = array( 'avatarMotion' => 'yes' === ( $s['avatar_motion'] ?? '' ) );
		$classes = 'avix-benefits' . ( 'yes' !== ( $s['hover_motion'] ?? '' ) ? ' avix-benefits--static' : '' );
		$cards = array_filter( (array) ( $s['cards'] ?? array() ), 'is_array' );
		?>
		<section class="<?php echo esc_attr( $classes ); ?>" data-avix-benefits="<?php echo esc_attr( wp_json_encode( $config ) ); ?>" <?php echo $has_title ? 'aria-labelledby="' . esc_attr( $id ) . '"' : 'aria-label="' . esc_attr__( 'Our service benefits', 'avix-widgets' ) . '"'; ?>>
		<div class="avix-benefits__inner"><div class="avix-benefits__intro">
			<?php if ( ! empty( $s['eyebrow_one'] ) || ! empty( $s['eyebrow_two'] ) ) : ?><div class="avix-benefits__eyebrows">
			<?php if ( ! empty( $s['eyebrow_one'] ) ) : ?><p><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 8h16v12H4zM8 8V5h8v3M4 13h16M10 12v3h4v-3"/></svg><?php echo esc_html( $s['eyebrow_one'] ); ?></p><?php endif; ?>
			<?php if ( ! empty( $s['eyebrow_two'] ) ) : ?><p><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M17 4a4 4 0 0 1 0 7M20 21v-2a4 4 0 0 0-3-3.9"/></svg><?php echo esc_html( $s['eyebrow_two'] ); ?></p><?php endif; ?></div><?php endif; ?>
			<?php if ( $has_title ) : ?><<?php echo $tag; ?> class="avix-benefits__title" id="<?php echo esc_attr( $id ); ?>"><?php echo $this->formatted( $s['title'] ); ?></<?php echo $tag; ?>><?php endif; ?>
			<?php if ( ! empty( $s['description'] ) ) : ?><p class="avix-benefits__lead"><?php echo esc_html( $s['description'] ); ?></p><?php endif; ?>
		</div>
		<?php if ( $cards ) : ?><div class="avix-benefits__grid">
			<?php foreach ( $cards as $index => $card ) : $cid = 'avix-benefits-card-' . $this->get_id() . '-' . $index; $ctitle = (string) ( $card['title'] ?? '' ); ?>
			<article class="avix-benefits__card elementor-repeater-item-<?php echo esc_attr( sanitize_html_class( $card['_id'] ?? '' ) ); ?>" <?php if ( '' !== trim( $ctitle ) ) { echo 'aria-labelledby="' . esc_attr( $cid ) . '"'; } ?>>
				<?php if ( '' !== trim( $ctitle ) ) : ?><<?php echo $card_tag; ?> class="avix-benefits__card-title" id="<?php echo esc_attr( $cid ); ?>"><?php echo esc_html( $ctitle ); ?></<?php echo $card_tag; ?>><?php endif; ?>
				<?php if ( ! empty( $card['image']['url'] ) || ! empty( $card['image']['id'] ) ) : ?><div class="avix-benefits__visual"><?php $this->image( $card['image'], $card['image_alt'] ?? '' ); ?></div><?php endif; ?>
				<?php if ( ! empty( $card['description'] ) ) : ?><p class="avix-benefits__description"><?php echo esc_html( $card['description'] ); ?></p><?php endif; ?>
				<?php $this->link( 'card-link-' . $index, $card['link_text'] ?? '', $card['link'] ?? array(), 'yes' === ( $s['show_arrows'] ?? '' ) ); ?>
			</article><?php endforeach; ?>
		</div><?php endif; ?>
		<?php if ( 'yes' === ( $s['show_help'] ?? '' ) ) : ?><div class="avix-benefits__help<?php echo 'yes' !== ( $s['show_avatar'] ?? '' ) ? ' avix-benefits__help--no-avatar' : ''; ?>" data-benefits-help>
			<?php if ( 'yes' === ( $s['show_avatar'] ?? '' ) ) : ?><span class="avix-benefits__buddy" aria-hidden="true"><?php if ( ! empty( $s['avatar']['url'] ) || ! empty( $s['avatar']['id'] ) ) { $this->image( $s['avatar'], '' ); } else { $this->avatar(); } ?></span><?php endif; ?>
			<?php if ( ! empty( $s['help_title'] ) || ! empty( $s['help_text'] ) ) : ?><p><?php if ( ! empty( $s['help_title'] ) ) : ?><strong><?php echo esc_html( $s['help_title'] ); ?></strong> <?php endif; ?><?php echo esc_html( $s['help_text'] ?? '' ); ?></p><?php endif; ?>
			<?php $this->link( 'help-link', $s['help_link_text'] ?? '', $s['help_link'] ?? array(), 'yes' === ( $s['show_arrows'] ?? '' ), true ); ?>
		</div><?php endif; ?>
		</div></section>
		<?php
	}
}
