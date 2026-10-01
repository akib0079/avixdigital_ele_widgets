<?php
/**
 * FAQ & Quote: a minimal, crawlable accordion (native <details>) with
 * FAQPage structured data and a small "Request a quote" card, meant to sit
 * just above the footer.
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

class Faq extends Widget_Base {

	/** Only one FAQPage block per page (Google reads the first). */
	private static $schema_printed = false;

	/** Anchors used on this page, so two widgets never share an id. */
	private static $anchors = array();

	public function get_name(): string {
		return 'avix-faq';
	}

	public function get_title(): string {
		return esc_html__( 'FAQ & Quote', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-accordion';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'faq', 'questions', 'accordion', 'quote', 'cta', 'schema', 'avix' );
	}

	public function get_style_depends(): array {
		return array( 'avix-faq' );
	}

	public function get_script_depends(): array {
		return array( 'avix-faq' );
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/* ------------------------------------------------------------------ */
	/* Controls                                                            */
	/* ------------------------------------------------------------------ */

	protected function register_controls(): void {
		$this->controls_header();
		$this->controls_items();
		$this->controls_cta();
		$this->controls_style();
	}

	private function controls_header() {
		$this->start_controls_section( 'section_header', array( 'label' => esc_html__( 'Header', 'avix-widgets' ) ) );

		$this->add_control(
			'tag',
			array(
				'label'   => esc_html__( 'Tag', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'FAQ', 'avix-widgets' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Title', 'avix-widgets' ),
				'description' => esc_html__( 'Wrap words in [square brackets] to colour them with the accent.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => 'Questions, [answered].',
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
			'intro',
			array(
				'label'   => esc_html__( 'Intro', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 3,
				'default' => esc_html__( 'Straight answers to what clients ask us before a project starts.', 'avix-widgets' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_items() {
		$this->start_controls_section( 'section_items', array( 'label' => esc_html__( 'Questions', 'avix-widgets' ) ) );

		$repeater = new Repeater();
		$repeater->add_control(
			'question',
			array(
				'label'       => esc_html__( 'Question', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);
		$repeater->add_control(
			'answer',
			array(
				'label'   => esc_html__( 'Answer', 'avix-widgets' ),
				'type'    => Controls_Manager::WYSIWYG,
				'dynamic' => array( 'active' => true ),
			)
		);
		$repeater->add_control(
			'anchor',
			array(
				'label'       => esc_html__( 'Anchor', 'avix-widgets' ),
				'description' => esc_html__( 'Link straight to this answer with #anchor (it opens itself). Empty = made from the question.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'pricing',
			)
		);

		$this->add_control(
			'items',
			array(
				'label'       => esc_html__( 'Questions', 'avix-widgets' ),
				'description' => esc_html__( 'Use the questions people really ask, answered in 1–3 sentences. Links to service pages inside answers help SEO.', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ question }}}',
				'default'     => $this->default_items(),
			)
		);

		$this->add_control(
			'question_tag',
			array(
				'label'   => esc_html__( 'Question HTML tag', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h3',
				'options' => array(
					'h3'   => 'H3',
					'h4'   => 'H4',
					'span' => 'span',
				),
			)
		);

		$this->add_control(
			'open_first',
			array(
				'label'   => esc_html__( 'First answer open', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'exclusive',
			array(
				'label'   => esc_html__( 'One answer open at a time', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'schema',
			array(
				'label'       => esc_html__( 'FAQPage structured data', 'avix-widgets' ),
				'description' => esc_html__( 'JSON-LD for search engines and AI answers. Printed once per page. Note: Google now shows FAQ rich results mainly for government and health sites.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
			)
		);

		$this->end_controls_section();
	}

	private function controls_cta() {
		$this->start_controls_section( 'section_cta', array( 'label' => esc_html__( 'Request a Quote', 'avix-widgets' ) ) );

		$this->add_control(
			'show_cta',
			array(
				'label'   => esc_html__( 'Show the quote card', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'cta_title',
			array(
				'label'     => esc_html__( 'Title', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Still have questions?', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_cta' => 'yes' ),
			)
		);

		$this->add_control(
			'cta_text',
			array(
				'label'     => esc_html__( 'Text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXTAREA,
				'rows'      => 2,
				'default'   => esc_html__( 'Tell us what you’re building. You’ll get straight answers and a clear scope, timeline and price.', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_cta' => 'yes' ),
			)
		);

		$this->add_control(
			'cta_button_text',
			array(
				'label'     => esc_html__( 'Button text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Request a quote', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_cta' => 'yes' ),
			)
		);

		$this->add_control(
			'cta_link',
			array(
				'label'     => esc_html__( 'Button link', 'avix-widgets' ),
				'type'      => Controls_Manager::URL,
				'dynamic'   => array( 'active' => true ),
				'default'   => array( 'url' => 'https://avixdigital.com/contact/' ),
				'condition' => array( 'show_cta' => 'yes' ),
			)
		);

		$this->add_control(
			'cta_note',
			array(
				'label'     => esc_html__( 'Small print', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Free estimate · No obligation', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_cta' => 'yes' ),
			)
		);

		$this->add_control(
			'show_buddy',
			array(
				'label'       => esc_html__( 'Pixel character on the card', 'avix-widgets' ),
				'description' => esc_html__( 'Sits on the card edge, swings its legs and waves when the card is hovered.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
				'condition'   => array( 'show_cta' => 'yes' ),
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
				'label'   => esc_html__( 'Style', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'dark',
				'options' => array(
					'dark'  => esc_html__( 'Dark', 'avix-widgets' ),
					'light' => esc_html__( 'Light', 'avix-widgets' ),
				),
			)
		);

		$colors = array(
			'faq_bg'     => array( esc_html__( 'Background', 'avix-widgets' ), '--faq-bg' ),
			'faq_accent' => array( esc_html__( 'Accent', 'avix-widgets' ), '--faq-accent' ),
			'faq_hl'     => array( esc_html__( 'Title [highlight]', 'avix-widgets' ), '--faq-hl' ),
			'faq_ink'    => array( esc_html__( 'Headings & questions', 'avix-widgets' ), '--faq-ink' ),
			'faq_muted'  => array( esc_html__( 'Body text', 'avix-widgets' ), '--faq-muted' ),
			'faq_line'   => array( esc_html__( 'Dividers', 'avix-widgets' ), '--faq-line' ),
			'faq_card'   => array( esc_html__( 'Quote card', 'avix-widgets' ), '--faq-card' ),
		);
		foreach ( $colors as $key => $color ) {
			$this->add_control(
				$key,
				array(
					'label'     => $color[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-faq' => $color[1] . ': {{VALUE}};' ),
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
				'selectors'          => array( '{{WRAPPER}} .avix-faq' => 'padding-top: {{TOP}}{{UNIT}}; padding-bottom: {{BOTTOM}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'max_width',
			array(
				'label'      => esc_html__( 'Content width', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 700, 'max' => 1600 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-faq' => '--faq-max: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'buddy_size',
			array(
				'label'      => esc_html__( 'Character size', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 18, 'max' => 56 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-faq' => '--faq-buddy-w: {{SIZE}}{{UNIT}};' ),
				'condition'  => array(
					'show_cta'   => 'yes',
					'show_buddy' => 'yes',
				),
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
			'title_typography'    => array( esc_html__( 'Title', 'avix-widgets' ), '.avix-faq__title' ),
			'question_typography' => array( esc_html__( 'Question', 'avix-widgets' ), '.avix-faq__q-text' ),
			'answer_typography'   => array( esc_html__( 'Answer', 'avix-widgets' ), '.avix-faq__a' ),
		);
		foreach ( $type as $name => $group ) {
			$this->add_group_control(
				Group_Control_Typography::get_type(),
				array(
					'name'     => $name,
					'label'    => $group[0],
					'selector' => '{{WRAPPER}} .avix-faq ' . $group[1],
				)
			);
		}

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ */
	/* Render                                                              */
	/* ------------------------------------------------------------------ */

	protected function render(): void {
		$s     = $this->get_settings_for_display();
		$items = array();
		foreach ( (array) $s['items'] as $row ) {
			$question = trim( (string) ( $row['question'] ?? '' ) );
			if ( '' === $question ) {
				continue;
			}
			$items[] = array(
				'question' => $question,
				'answer'   => trim( wpautop( wp_kses_post( (string) ( $row['answer'] ?? '' ) ) ) ),
				'anchor'   => (string) ( $row['anchor'] ?? '' ),
			);
		}

		$edit = \Elementor\Plugin::$instance->editor->is_edit_mode();
		if ( ! $items ) {
			if ( $edit ) {
				echo '<div class="elementor-alert elementor-alert-info">' . esc_html__( 'FAQ & Quote: add at least one question.', 'avix-widgets' ) . '</div>';
			}
			return;
		}

		$id        = $this->get_id();
		$title     = trim( (string) $s['title'] );
		$title_id  = 'avix-faq-title-' . $id;
		$title_tag = Utils::validate_html_tag( $s['title_tag'] );
		$q_tag     = Utils::validate_html_tag( $s['question_tag'] );
		$exclusive = 'yes' === $s['exclusive'];
		$cta       = 'yes' === $s['show_cta'] && '' !== trim( (string) $s['cta_button_text'] ) && '' !== esc_url( $s['cta_link']['url'] ?? '' );

		$classes = array( 'avix-faq', 'light' === $s['theme'] ? 'avix-faq--light' : 'avix-faq--dark' );
		if ( ! $cta ) {
			$classes[] = 'avix-faq--solo';
		}
		$this->add_render_attribute(
			'root',
			array(
				'class'         => $classes,
				'data-avix-faq' => '',
			)
		);
		if ( $exclusive ) {
			$this->add_render_attribute( 'root', 'data-faq-exclusive', '' );
		}
		if ( '' !== $title ) {
			$this->add_render_attribute( 'root', 'aria-labelledby', $title_id );
		}
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="avix-faq__frame">
				<div class="avix-faq__head" data-faq-reveal>
					<?php if ( '' !== trim( (string) $s['tag'] ) ) : ?>
						<p class="avix-faq__tag"><b>/</b> <?php echo esc_html( $s['tag'] ); ?></p>
					<?php endif; ?>
					<?php if ( '' !== $title ) : ?>
						<<?php echo esc_attr( $title_tag ); ?> class="avix-faq__title" id="<?php echo esc_attr( $title_id ); ?>"><?php echo $this->accent_html( $title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in accent_html(). ?></<?php echo esc_attr( $title_tag ); ?>>
					<?php endif; ?>
					<?php if ( '' !== trim( (string) $s['intro'] ) ) : ?>
						<p class="avix-faq__intro"><?php echo esc_html( $s['intro'] ); ?></p>
					<?php endif; ?>
				</div>

				<div class="avix-faq__list">
					<?php foreach ( $items as $i => $item ) : ?>
						<details class="avix-faq__item" id="<?php echo esc_attr( $this->anchor( $item, $i ) ); ?>" data-faq-item data-faq-reveal style="--faq-delay: <?php echo (int) min( $i, 8 ) * 60; ?>ms;"<?php echo $exclusive ? ' name="avix-faq-' . esc_attr( $id ) . '"' : ''; ?><?php echo 0 === $i && 'yes' === $s['open_first'] ? ' open' : ''; ?>>
							<summary class="avix-faq__q">
								<<?php echo esc_attr( $q_tag ); ?> class="avix-faq__q-text"><?php echo esc_html( $item['question'] ); ?></<?php echo esc_attr( $q_tag ); ?>>
								<span class="avix-faq__icon" aria-hidden="true"></span>
							</summary>
							<?php if ( '' !== $item['answer'] ) : ?>
								<div class="avix-faq__a"><?php echo $item['answer']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post() above. ?></div>
							<?php endif; ?>
						</details>
					<?php endforeach; ?>
				</div>

				<?php
				if ( $cta ) {
					$this->render_cta( $s );
				}
				?>
			</div>
		</section>
		<?php
		if ( 'yes' === $s['schema'] ) {
			$this->print_schema( $items );
		}
	}

	private function render_cta( array $s ) {
		$this->add_link_attributes( 'cta', $s['cta_link'] );
		$this->add_render_attribute( 'cta', 'class', 'avix-faq__btn' );
		$buddy = 'yes' === $s['show_buddy'];
		?>
		<div class="avix-faq__cta" data-faq-reveal style="--faq-delay: 120ms;">
			<?php if ( $buddy ) : ?>
				<span class="avix-faq__buddy" aria-hidden="true">
					<span class="avix-faq__sprite">
						<svg viewBox="0 0 10 12" focusable="false">
							<rect class="avix-faq__b-leg avix-faq__b-leg--l" x="2" y="7" width="2" height="5"/>
							<rect class="avix-faq__b-leg avix-faq__b-leg--r" x="6" y="7" width="2" height="5"/>
							<rect class="avix-faq__b-body" x="2" y="3" width="6" height="4"/>
							<rect class="avix-faq__b-head" x="3" y="0" width="4" height="3"/>
							<rect class="avix-faq__b-arm avix-faq__b-arm--l" x="0" y="3" width="2" height="3"/>
							<rect class="avix-faq__b-arm avix-faq__b-arm--r" x="8" y="3" width="2" height="3"/>
						</svg>
					</span>
				</span>
			<?php endif; ?>
			<?php if ( '' !== trim( (string) $s['cta_title'] ) ) : ?>
				<p class="avix-faq__cta-title"><?php echo esc_html( $s['cta_title'] ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== trim( (string) $s['cta_text'] ) ) : ?>
				<p class="avix-faq__cta-text"><?php echo esc_html( $s['cta_text'] ); ?></p>
			<?php endif; ?>
			<a <?php $this->print_render_attribute_string( 'cta' ); ?>>
				<span><?php echo esc_html( $s['cta_button_text'] ); ?></span>
				<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
			</a>
			<?php if ( '' !== trim( (string) $s['cta_note'] ) ) : ?>
				<p class="avix-faq__note"><?php echo esc_html( $s['cta_note'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Escaped title with [words] wrapped in the accent span.
	 */
	private function accent_html( $text ) {
		return preg_replace( '/\[([^\[\]]+)\]/', '<span class="avix-faq__accent">$1</span>', esc_html( $text ) );
	}

	/**
	 * Stable, page-unique id for a question: the custom anchor, else
	 * "faq-" + the question slug.
	 */
	private function anchor( array $item, $index ) {
		$base = sanitize_title( $item['anchor'] );
		if ( '' === $base ) {
			// Non-Latin questions slug to %-encoded bytes: use a plain numbered id instead.
			$slug = sanitize_title( $item['question'] );
			$base = '' !== $slug && false === strpos( $slug, '%' ) ? 'faq-' . substr( $slug, 0, 60 ) : 'faq-' . $this->get_id() . '-' . ( $index + 1 );
		}
		$base   = rtrim( $base, '-' );
		$anchor = $base;
		for ( $n = 2; isset( self::$anchors[ $anchor ] ); $n++ ) {
			$anchor = $base . '-' . $n;
		}
		self::$anchors[ $anchor ] = true;
		return $anchor;
	}

	/**
	 * schema.org FAQPage. Answers keep only the tags Google accepts.
	 */
	private function print_schema( array $items ) {
		if ( self::$schema_printed ) {
			return;
		}
		$allowed  = array(
			'a'      => array( 'href' => true ),
			'h2'     => array(),
			'h3'     => array(),
			'h4'     => array(),
			'h5'     => array(),
			'h6'     => array(),
			'div'    => array(),
			'p'      => array(),
			'br'     => array(),
			'ul'     => array(),
			'ol'     => array(),
			'li'     => array(),
			'b'      => array(),
			'strong' => array(),
			'i'      => array(),
			'em'     => array(),
		);
		$entities = array();
		foreach ( $items as $item ) {
			$answer = trim( wp_kses( $item['answer'], $allowed ) );
			if ( '' === trim( wp_strip_all_tags( $answer ) ) ) {
				continue;
			}
			$entities[] = array(
				'@type'          => 'Question',
				'name'           => wp_strip_all_tags( $item['question'] ),
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $answer,
				),
			);
		}
		if ( ! $entities ) {
			return;
		}
		$json = wp_json_encode(
			array(
				'@context'   => 'https://schema.org',
				'@type'      => 'FAQPage',
				'mainEntity' => $entities,
			),
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
		);
		if ( $json ) {
			self::$schema_printed = true;
			echo '<script type="application/ld+json">' . $json . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON with < > & hex-escaped.
		}
	}

	private function default_items() {
		return array(
			array(
				'question' => 'How much does a project cost?',
				'answer'   => '<p>It depends on scope: the number of templates, integrations and custom features. Every quote is itemised, so you can see exactly what you’re paying for and adjust the scope to fit your budget.</p>',
			),
			array(
				'question' => 'How long does it take to build a website or store?',
				'answer'   => '<p>That depends on scope too. A focused landing page moves much faster than a custom Shopify Plus build with integrations. Your quote includes a milestone plan, so you always know what happens next and when.</p>',
			),
			array(
				'question' => 'Do you work with Shopify Plus and Webflow?',
				'answer'   => '<p>Yes. We design and build custom Shopify and Shopify Plus stores and Webflow sites, and recommend the platform that fits your business rather than forcing one.</p>',
			),
			array(
				'question' => 'Can you redesign or migrate our existing site?',
				'answer'   => '<p>Yes. We audit what you have, keep what already works and map every redirect before launch, so your search rankings and traffic carry over to the new site.</p>',
			),
			array(
				'question' => 'Do you offer support after launch?',
				'answer'   => '<p>Yes. After launch we stay on for fixes, performance tuning and new features, so your site keeps improving instead of standing still.</p>',
			),
		);
	}
}
