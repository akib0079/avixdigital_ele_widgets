<?php
/**
 * Case Study Next: the end of a case study. A white call-to-action card
 * ("Facing a similar challenge on Shopify?") with the page's only pixel
 * character seated on its top edge (it waves once when the card comes into
 * view, and again when the main button is hovered), followed by a large
 * card for the next case study: its studio render with a dark info panel,
 * in the language of the home page's Selected Work cards.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Widgets;

use AvixWidgets\Case_Studies\Case_Study;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Utils;
use Elementor\Widget_Base;

defined( 'ABSPATH' ) || exit;

class Case_Study_Next extends Widget_Base {

	use \AvixWidgets\Case_Studies\Source;

	const CONTACT = 'https://avixdigital.com/contact/';

	public function get_name(): string {
		return 'avix-case-study-next';
	}

	public function get_title(): string {
		return esc_html__( 'Case Study Next', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-post-navigation';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'case study', 'portfolio', 'next', 'cta', 'contact', 'navigation', 'pixel', 'avix' );
	}

	public function get_style_depends(): array {
		return array( 'avix-case-study-next' );
	}

	public function get_script_depends(): array {
		return array( 'avix-case-study-next' );
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	protected function is_dynamic_content(): bool {
		return true;
	}

	/* ------------------------------------------------------------------ */
	/* Controls                                                            */
	/* ------------------------------------------------------------------ */

	protected function register_controls(): void {
		$this->controls_source();
		$this->controls_cta();
		$this->controls_next();
		$this->controls_style();
		$this->controls_type();
	}

	private function controls_cta() {
		$this->start_controls_section( 'section_cta', array( 'label' => esc_html__( 'Call to action', 'avix-widgets' ) ) );

		$this->add_control(
			'show_cta',
			array(
				'label'   => esc_html__( 'Show', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'cta_title',
			array(
				'label'       => esc_html__( 'Headline', 'avix-widgets' ),
				'description' => esc_html__( 'Wrap words in [brackets] to highlight them in orange. {platform} and {client} come from the case study; without a platform the line reads “Facing a similar challenge?”.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => 'Facing a similar challenge on [{platform}]?',
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_cta' => 'yes' ),
			)
		);

		$this->add_control(
			'cta_title_tag',
			array(
				'label'     => esc_html__( 'Headline tag', 'avix-widgets' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'h2',
				'options'   => array(
					'h2'  => 'H2',
					'h3'  => 'H3',
					'div' => 'div',
					'p'   => 'p',
				),
				'condition' => array( 'show_cta' => 'yes' ),
			)
		);

		$this->add_control(
			'cta_text',
			array(
				'label'     => esc_html__( 'Text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXTAREA,
				'rows'      => 3,
				'default'   => 'Tell us what you are building. You will talk to the people who build it.',
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_cta' => 'yes' ),
			)
		);

		$this->add_control(
			'cta_button',
			array(
				'label'       => esc_html__( 'Main button text', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Discuss your project', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
				'separator'   => 'before',
				'condition'   => array( 'show_cta' => 'yes' ),
			)
		);

		$this->add_control(
			'cta_link',
			array(
				'label'     => esc_html__( 'Main button link', 'avix-widgets' ),
				'type'      => Controls_Manager::URL,
				'dynamic'   => array( 'active' => true ),
				'default'   => array( 'url' => self::CONTACT ),
				'condition' => array( 'show_cta' => 'yes' ),
			)
		);

		$this->add_control(
			'cta_button_2',
			array(
				'label'       => esc_html__( 'Second link text', 'avix-widgets' ),
				'description' => esc_html__( 'A quieter text link. Leave empty to hide it.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'View all case studies', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_cta' => 'yes' ),
			)
		);

		$this->add_control(
			'cta_link_2',
			array(
				'label'       => esc_html__( 'Second link', 'avix-widgets' ),
				'description' => esc_html__( 'Leave empty for the case studies page.', 'avix-widgets' ),
				'type'        => Controls_Manager::URL,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_cta' => 'yes' ),
			)
		);

		$this->add_control(
			'show_pixel',
			array(
				'label'       => esc_html__( 'Pixel character', 'avix-widgets' ),
				'description' => esc_html__( 'The only pixel character on case-study pages. It sits on the card and waves once when the card comes into view.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
				'condition'   => array( 'show_cta' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'pixel_size',
			array(
				'label'       => esc_html__( 'Character size', 'avix-widgets' ),
				'description' => esc_html__( 'Leave empty for 40px (32px on phones).', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 24, 'max' => 72 ) ),
				'selectors'   => array( '{{WRAPPER}} .avix-csn' => '--csn-pal-w: {{SIZE}}{{UNIT}};' ),
				'condition'   => array(
					'show_cta'   => 'yes',
					'show_pixel' => 'yes',
				),
			)
		);

		$this->end_controls_section();
	}

	private function controls_next() {
		$this->start_controls_section( 'section_next', array( 'label' => esc_html__( 'Next case study', 'avix-widgets' ) ) );

		$this->add_control(
			'show_next',
			array(
				'label'       => esc_html__( 'Show', 'avix-widgets' ),
				'description' => esc_html__( 'Follows the case studies’ order and loops around. Hidden until there are at least two published case studies.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'direction',
			array(
				'label'     => esc_html__( 'Show', 'avix-widgets' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'next',
				'options'   => array(
					'next'     => esc_html__( 'Next case study', 'avix-widgets' ),
					'previous' => esc_html__( 'Previous case study', 'avix-widgets' ),
					'both'     => esc_html__( 'Previous and next', 'avix-widgets' ),
				),
				'condition' => array( 'show_next' => 'yes' ),
			)
		);

		$this->add_control(
			'next_label',
			array(
				'label'     => esc_html__( 'Next label', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Next case study', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array(
					'show_next'  => 'yes',
					'direction!' => 'previous',
				),
			)
		);

		$this->add_control(
			'prev_label',
			array(
				'label'     => esc_html__( 'Previous label', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Previous case study', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array(
					'show_next'  => 'yes',
					'direction!' => 'next',
				),
			)
		);

		$this->add_control(
			'next_image',
			array(
				'label'       => esc_html__( 'Image', 'avix-widgets' ),
				'description' => esc_html__( 'Studio render: the dark device render of the live site, falling back to the card image and then the homepage screenshot.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'render',
				'options'     => array(
					'render'   => esc_html__( 'Studio render', 'avix-widgets' ),
					'featured' => esc_html__( 'Card & social image', 'avix-widgets' ),
					'hero'     => esc_html__( 'Homepage screenshot', 'avix-widgets' ),
				),
				'condition'   => array( 'show_next' => 'yes' ),
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

		$this->add_control(
			'theme',
			array(
				'label'   => esc_html__( 'Theme', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'paper',
				'options' => array(
					'paper' => esc_html__( 'Warm paper', 'avix-widgets' ),
					'white' => esc_html__( 'White', 'avix-widgets' ),
				),
			)
		);

		$colors = array(
			'bg'          => array( esc_html__( 'Background', 'avix-widgets' ), '--csn-bg' ),
			'card_bg'     => array( esc_html__( 'Call-to-action card', 'avix-widgets' ), '--csn-card' ),
			'ink'         => array( esc_html__( 'Headline', 'avix-widgets' ), '--csn-ink' ),
			'muted'       => array( esc_html__( 'Text', 'avix-widgets' ), '--csn-muted' ),
			'line'        => array( esc_html__( 'Lines', 'avix-widgets' ), '--csn-line' ),
			'accent'      => array( esc_html__( 'Accent', 'avix-widgets' ), '--csn-accent' ),
			'panel_bg'    => array( esc_html__( 'Next card panel', 'avix-widgets' ), '--csn-panel' ),
		);
		foreach ( $colors as $key => $color ) {
			$this->add_control(
				'color_' . $key,
				array(
					'label'     => $color[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-csn' => $color[1] . ': {{VALUE}};' ),
					'separator' => 'bg' === $key ? 'before' : '',
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
				'selectors'          => array( '{{WRAPPER}} .avix-csn' => '--csn-pad-top: {{TOP}}{{UNIT}}; --csn-pad-bottom: {{BOTTOM}}{{UNIT}};' ),
				'separator'          => 'before',
			)
		);

		$this->add_responsive_control(
			'max_width',
			array(
				'label'      => esc_html__( 'Content width', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 720, 'max' => 1600 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-csn' => '--csn-max: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_type() {
		$this->start_controls_section(
			'style_type',
			array(
				'label' => esc_html__( 'Typography', 'avix-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$groups = array(
			'cta_title' => array( esc_html__( 'Call-to-action headline', 'avix-widgets' ), '.avix-csn__title' ),
			'cta_text'  => array( esc_html__( 'Call-to-action text', 'avix-widgets' ), '.avix-csn__text' ),
			'button'    => array( esc_html__( 'Buttons', 'avix-widgets' ), '.avix-csn__btn' ),
			'name'      => array( esc_html__( 'Next case study name', 'avix-widgets' ), '.avix-csn__name' ),
			'summary'   => array( esc_html__( 'Next case study summary', 'avix-widgets' ), '.avix-csn__summary' ),
		);
		foreach ( $groups as $key => $group ) {
			$this->add_group_control(
				Group_Control_Typography::get_type(),
				array(
					'name'     => $key . '_typography',
					'label'    => $group[0],
					'selector' => '{{WRAPPER}} .avix-csn ' . $group[1],
				)
			);
		}

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ */
	/* Helpers                                                             */
	/* ------------------------------------------------------------------ */

	/**
	 * Escaped text with [accent] spans and line breaks.
	 *
	 * @param string $text Raw text.
	 */
	private function accent_html( $text ) {
		$html  = esc_html( trim( (string) $text ) );
		$html  = preg_replace( '/\[([^\[\]]+)\]/u', '<span class="avix-csn__accent">$1</span>', $html );
		$lines = preg_split( '/\r\n|\r|\n/', (string) $html );
		return implode( ' <br class="avix-csn__break">', array_map( 'trim', $lines ) );
	}

	/**
	 * The CTA headline with {platform} / {client} filled in. Without a
	 * platform, "… on [{platform}]?" folds back to "…?".
	 *
	 * @param string $title Raw title.
	 * @param array  $cs    Case study data.
	 */
	private function cta_title( $title, array $cs ) {
		$platform = trim( (string) ( $cs['platform'] ?? '' ) );
		$client   = trim( (string) ( $cs['client'] ?? '' ) );
		if ( '' === $platform ) {
			$title = (string) preg_replace( '/\s*\b(?:on|for|with|in)?\s*\[?\{platform\}\]?/iu', '', $title );
		}
		if ( '' === $client ) {
			$title = (string) preg_replace( '/\s*\b(?:for|like)?\s*\[?\{client\}\]?/iu', '', $title );
		}
		$title = str_replace( array( '{platform}', '{client}' ), array( $platform, $client ), $title );
		$title = (string) preg_replace( '/\[\s*\]/u', '', $title );
		return trim( (string) preg_replace( '/\s+([?!.,])/u', '$1', $title ) );
	}

	private function arrow( $dir = 'ne' ) {
		$paths = array(
			'ne' => 'M7 17 17 7M7 7h10v10',
			'e'  => 'M5 12h14M13 6l6 6-6 6',
			'w'  => 'M19 12H5M11 6l-6 6 6 6',
		);
		return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="' . esc_attr( $paths[ $dir ] ?? $paths['ne'] ) . '"/></svg>';
	}

	private function index_url() {
		if ( class_exists( '\AvixWidgets\Case_Studies\Case_Study' ) && method_exists( '\AvixWidgets\Case_Studies\Case_Study', 'index_url' ) ) {
			return (string) Case_Study::index_url();
		}
		return home_url( '/case-studies/' );
	}

	/**
	 * The neighbour post IDs to show, in display order.
	 *
	 * @param int    $id        Current case study.
	 * @param string $direction next|previous|both.
	 * @return array List of array( 'id' => int, 'dir' => 'next'|'prev' ).
	 */
	private function neighbours( $id, $direction ) {
		if ( ! $id || ! class_exists( '\AvixWidgets\Case_Studies\Case_Study' ) || ! method_exists( '\AvixWidgets\Case_Studies\Case_Study', 'neighbours' ) ) {
			return array();
		}
		$n    = (array) Case_Study::neighbours( $id );
		$prev = (int) ( $n['prev'] ?? 0 );
		$next = (int) ( $n['next'] ?? 0 );
		$list = array();
		if ( 'both' === $direction ) {
			if ( $prev && $prev !== $id && $prev !== $next ) {
				$list[] = array(
					'id'  => $prev,
					'dir' => 'prev',
				);
			}
			if ( $next && $next !== $id ) {
				$list[] = array(
					'id'  => $next,
					'dir' => 'next',
				);
			}
		} elseif ( 'previous' === $direction ) {
			if ( $prev && $prev !== $id ) {
				$list[] = array(
					'id'  => $prev,
					'dir' => 'prev',
				);
			}
		} elseif ( $next && $next !== $id ) {
			$list[] = array(
				'id'  => $next,
				'dir' => 'next',
			);
		}
		return $list;
	}

	/**
	 * The image for a neighbour card, by preference, with whether it is the
	 * studio render (dark, centred) or a screenshot (top-aligned).
	 *
	 * @param array  $n    Neighbour data.
	 * @param string $pick render|featured|hero.
	 * @return array array( id, is_render ).
	 */
	private function card_image( array $n, $pick ) {
		$render = (int) ( $n['hero_render'] ?? 0 );
		$card   = (int) ( $n['card_image'] ?? 0 );
		$thumb  = (int) ( $n['thumbnail_id'] ?? 0 );
		$hero   = (int) ( $n['hero_desktop'] ?? 0 );
		$orders = array(
			'render'   => array( $render, $card, $thumb, $hero ),
			'featured' => array( $thumb, $card, $render, $hero ),
			'hero'     => array( $hero, $card, $thumb, $render ),
		);
		foreach ( $orders[ $pick ] ?? $orders['render'] as $id ) {
			if ( $id && wp_attachment_is_image( $id ) ) {
				return array( $id, $render && $id === $render );
			}
		}
		return array( 0, false );
	}

	/**
	 * Card tags as a list of labels.
	 *
	 * @param mixed $tags String (comma list) or array.
	 */
	private function tags( $tags ) {
		$list = is_array( $tags ) ? $tags : explode( ',', (string) $tags );
		return array_slice( array_values( array_filter( array_map( 'trim', array_map( 'strval', $list ) ), 'strlen' ) ), 0, 4 );
	}

	private function is_editor() {
		if ( class_exists( '\AvixWidgets\Case_Studies\Case_Study' ) ) {
			return \AvixWidgets\Case_Studies\Case_Study::is_editor();
		}
		return class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor && \Elementor\Plugin::$instance->editor->is_edit_mode();
	}

	/* ------------------------------------------------------------------ */
	/* Render                                                              */
	/* ------------------------------------------------------------------ */

	protected function render(): void {
		$s  = $this->get_settings_for_display();
		$cs = (array) $this->cs();
		$id = (int) ( $cs['id'] ?? 0 );

		$show_cta = 'yes' === ( $s['show_cta'] ?? '' );
		$title    = $show_cta ? $this->cta_title( (string) ( $s['cta_title'] ?? '' ), $cs ) : '';
		$text     = $show_cta ? trim( (string) ( $s['cta_text'] ?? '' ) ) : '';
		$btn_text = $show_cta ? trim( (string) ( $s['cta_button'] ?? '' ) ) : '';
		$btn_url  = $show_cta ? esc_url( (string) ( $s['cta_link']['url'] ?? '' ) ) : '';
		$btn2     = $show_cta ? trim( (string) ( $s['cta_button_2'] ?? '' ) ) : '';
		$btn2_url = $show_cta ? esc_url( (string) ( $s['cta_link_2']['url'] ?? '' ) ) : '';
		if ( $show_cta && '' !== $btn2 && '' === $btn2_url ) {
			$btn2_url = esc_url( $this->index_url() );
		}
		$has_cta = $show_cta && ( '' !== $title || '' !== $text || ( '' !== $btn_text && '' !== $btn_url ) );

		// The owner can switch every character off on case-study pages.
		$mode     = method_exists( '\AvixWidgets\Case_Studies\Case_Studies', 'characters_mode' ) ? (string) \AvixWidgets\Case_Studies\Case_Studies::characters_mode() : (string) get_option( 'avix_cs_characters', 'one' );
		$show_pal = $has_cta && 'yes' === ( $s['show_pixel'] ?? '' ) && 'none' !== $mode && class_exists( '\AvixWidgets\Pixel_Pal' );

		$cards = array();
		if ( 'yes' === ( $s['show_next'] ?? '' ) ) {
			$direction = in_array( $s['direction'] ?? 'next', array( 'next', 'previous', 'both' ), true ) ? $s['direction'] : 'next';
			foreach ( $this->neighbours( $id, $direction ) as $item ) {
				$n = (array) Case_Study::get( $item['id'] );
				if ( empty( $n ) || '' === (string) ( $n['permalink'] ?? '' ) ) {
					continue;
				}
				$cards[] = array( $n, $item['dir'] );
			}
		}

		if ( ! $has_cta && ! $cards ) {
			if ( $this->is_editor() ) {
				$this->cs_alert( esc_html__( 'Case Study Next: the next case study appears once two case studies are published. Turn on the call to action to show it on its own.', 'avix-widgets' ) );
			}
			return;
		}

		$theme = 'white' === ( $s['theme'] ?? 'paper' ) ? 'white' : 'paper';
		$tag   = Utils::validate_html_tag( $s['cta_title_tag'] ?? 'h2' );
		$tid   = 'avix-csn-title-' . $this->get_id();

		$classes = array( 'avix-csn', 'avix-csn--' . $theme );
		if ( $show_pal ) {
			$classes[] = 'avix-csn--pal';
		}
		$this->add_render_attribute(
			'root',
			array(
				'class'         => $classes,
				'data-avix-csn' => wp_json_encode( array( 'pal' => $show_pal ) ),
			)
		);
		if ( $has_cta && '' !== $title ) {
			$this->add_render_attribute( 'root', 'aria-labelledby', $tid );
		} else {
			$this->add_render_attribute( 'root', 'aria-label', esc_html__( 'More case studies', 'avix-widgets' ) );
		}
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="avix-csn__inner">
				<?php if ( $has_cta ) : ?>
					<div class="avix-csn__cta avix-csn__rise" data-csn-cta>
						<?php if ( $show_pal ) : ?>
							<span class="avix-csn__perch" aria-hidden="true">
								<?php
								echo \AvixWidgets\Pixel_Pal::render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup escaped in Pixel_Pal::render().
									array(
										'class' => 'avix-csn__pal is-still',
										'hi'    => true,
									)
								);
								?>
							</span>
						<?php endif; ?>
						<div class="avix-csn__cta-copy">
							<?php if ( '' !== $title ) : ?>
								<<?php echo esc_html( $tag ); ?> id="<?php echo esc_attr( $tid ); ?>" class="avix-csn__title"><?php echo $this->accent_html( $title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in accent_html(). ?></<?php echo esc_html( $tag ); ?>>
							<?php endif; ?>
							<?php if ( '' !== $text ) : ?>
								<p class="avix-csn__text"><?php echo $this->accent_html( $text ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in accent_html(). ?></p>
							<?php endif; ?>
						</div>
						<?php if ( ( '' !== $btn_text && '' !== $btn_url ) || ( '' !== $btn2 && '' !== $btn2_url ) ) : ?>
							<div class="avix-csn__actions">
								<?php
								if ( '' !== $btn_text && '' !== $btn_url ) {
									$this->add_render_attribute(
										'btn',
										array(
											'class'           => array( 'avix-csn__btn', 'avix-csn__btn--primary' ),
											'data-csn-button' => '',
										)
									);
									$this->add_link_attributes( 'btn', $s['cta_link'] );
									?>
									<a <?php $this->print_render_attribute_string( 'btn' ); ?>><span class="avix-csn__btn-text"><?php echo esc_html( $btn_text ); ?></span><span class="avix-csn__btn-icon"><?php echo $this->arrow( 'ne' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span></a>
									<?php
								}
								if ( '' !== $btn2 && '' !== $btn2_url ) {
									$this->add_render_attribute( 'btn2', 'class', 'avix-csn__more' );
									if ( '' !== esc_url( (string) ( $s['cta_link_2']['url'] ?? '' ) ) ) {
										$this->add_link_attributes( 'btn2', $s['cta_link_2'] );
									} else {
										$this->add_render_attribute( 'btn2', 'href', $btn2_url );
									}
									?>
									<a <?php $this->print_render_attribute_string( 'btn2' ); ?>><span class="avix-csn__more-text"><?php echo esc_html( $btn2 ); ?></span><?php echo $this->arrow( 'e' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></a>
									<?php
								}
								?>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( $cards ) : ?>
					<div class="avix-csn__cards avix-csn__cards--<?php echo (int) count( $cards ); ?>">
						<?php
						foreach ( $cards as $i => $card ) {
							$this->render_card( $card[0], $card[1], $s, $i );
						}
						?>
					</div>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}

	/**
	 * One neighbour card: a single link (no nested links), image + panel.
	 *
	 * @param array  $n     Neighbour data.
	 * @param string $dir   next|prev.
	 * @param array  $s     Settings.
	 * @param int    $index Position.
	 */
	private function render_card( array $n, $dir, array $s, $index ) {
		$name = trim( (string) ( $n['client'] ?? '' ) );
		$name = '' !== $name ? $name : trim( wp_strip_all_tags( (string) ( $n['title'] ?? '' ) ) );
		$kick = 'prev' === $dir ? trim( (string) ( $s['prev_label'] ?? '' ) ) : trim( (string) ( $s['next_label'] ?? '' ) );
		$sum  = trim( wp_strip_all_tags( (string) ( $n['excerpt'] ?? '' ) ) );
		$tags = $this->tags( ! empty( $n['card_tags_list'] ) ? $n['card_tags_list'] : ( $n['card_tags'] ?? '' ) );
		$base = 'avix-csn-' . $this->get_id() . '-' . (int) $index;

		list( $img_id, $is_render ) = $this->card_image( $n, (string) ( $s['next_image'] ?? 'render' ) );

		$img = '';
		if ( $img_id ) {
			$alt = trim( (string) get_post_meta( $img_id, '_wp_attachment_image_alt', true ) );
			$img = wp_get_attachment_image(
				$img_id,
				'large',
				false,
				array(
					'class'    => 'avix-csn__img',
					'alt'      => '' !== $alt ? $alt : sprintf( /* translators: %s: client name. */ __( '%s website on desktop and mobile', 'avix-widgets' ), $name ),
					'loading'  => 'lazy',
					'decoding' => 'async',
					'sizes'    => '(max-width: 860px) 92vw, (max-width: 1240px) 70vw, 860px',
				)
			);
		}

		$classes = array( 'avix-csn__card', 'avix-csn__card--' . $dir );
		$classes[] = $is_render ? 'is-render' : ( $img ? 'is-shot' : 'is-empty' );
		?>
		<div class="avix-csn__item avix-csn__rise" style="--i:<?php echo (int) $index + 1; ?>;">
			<a class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" href="<?php echo esc_url( (string) $n['permalink'] ); ?>" aria-labelledby="<?php echo esc_attr( $base . '-k ' . $base . '-n' ); ?>">
				<span class="avix-csn__media">
					<?php
					if ( $img ) {
						echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image().
					} else {
						echo '<span class="avix-csn__placeholder" aria-hidden="true">' . esc_html( function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 1 ) : substr( $name, 0, 1 ) ) . '</span>';
					}
					?>
				</span>
				<span class="avix-csn__panel">
					<span class="avix-csn__kicker" id="<?php echo esc_attr( $base . '-k' ); ?>"><?php echo esc_html( $kick ); ?></span>
					<span class="avix-csn__name" id="<?php echo esc_attr( $base . '-n' ); ?>"><?php echo esc_html( $name ); ?></span>
					<?php if ( '' !== $sum ) : ?>
						<span class="avix-csn__summary"><?php echo esc_html( $sum ); ?></span>
					<?php endif; ?>
					<span class="avix-csn__foot">
						<?php if ( $tags ) : ?>
							<span class="avix-csn__tags">
								<?php foreach ( $tags as $t ) : ?>
									<span class="avix-csn__tag"><?php echo esc_html( $t ); ?></span>
								<?php endforeach; ?>
							</span>
						<?php endif; ?>
						<span class="avix-csn__go" aria-hidden="true"><?php echo $this->arrow( 'prev' === $dir ? 'w' : 'e' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span>
					</span>
				</span>
			</a>
		</div>
		<?php
	}
}
