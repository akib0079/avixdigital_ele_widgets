<?php
/**
 * Intro Text: the brand intro with a word-by-word reveal, keywords that fan
 * out pictures, and the Avix pixel character playing on the logo.
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

class Intro_Text extends Widget_Base {

	use Media;

	public function get_name(): string {
		return 'avix-intro-text';
	}

	public function get_title(): string {
		return esc_html__( 'Intro Text', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-animated-headline';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'intro', 'text', 'tagline', 'about', 'reveal', 'logo', 'avatar', 'avix' );
	}

	public function get_style_depends(): array {
		return array( 'avix-intro-text' );
	}

	public function get_script_depends(): array {
		return array( 'avix-intro-text' );
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/* ------------------------------------------------------------------ */
	/* Controls                                                            */
	/* ------------------------------------------------------------------ */

	protected function register_controls(): void {
		$this->start_controls_section( 'section_mark', array( 'label' => esc_html__( 'Logo & Character', 'avix-widgets' ) ) );

		$this->add_control(
			'logo',
			array(
				'label'   => esc_html__( 'Logo', 'avix-widgets' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => array( 'url' => 'https://avixdigital.com/wp-content/uploads/2026/05/Untitled-design237.webp' ),
				'dynamic' => array( 'active' => true ),
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

		$this->add_responsive_control(
			'logo_size',
			array(
				'label'      => esc_html__( 'Logo size', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 40, 'max' => 180 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-it__inner' => '--it-logo: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'show_buddy',
			array(
				'label'       => esc_html__( 'Pixel character', 'avix-widgets' ),
				'description' => esc_html__( 'Peeks over the logo, hops on and sits there. Hover the logo and it starts rolling, with the character running on top to keep its balance. Click or tap it and the character gets flung into a flip. It also looks at, and talks about, any keyword a visitor hovers.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
			)
		);

		$lines = array(
			'line_peek'       => array( esc_html__( 'Peeking line', 'avix-widgets' ), 'Psst… hi! 👋' ),
			'line_hint'       => array( esc_html__( 'Hint (mouse)', 'avix-widgets' ), 'Hover the logo 👀' ),
			'line_hint_touch' => array( esc_html__( 'Hint (touch screens)', 'avix-widgets' ), 'Tap the logo 👀' ),
			'line_roll'       => array( esc_html__( 'While the logo rolls', 'avix-widgets' ), 'Whoa, whoa! 😅' ),
		);
		foreach ( $lines as $key => $line ) {
			$this->add_control(
				$key,
				array(
					'label'       => $line[0],
					'type'        => Controls_Manager::TEXT,
					'default'     => $line[1],
					'label_block' => true,
					'condition'   => array( 'show_buddy' => 'yes' ),
				)
			);
		}

		$this->add_control(
			'lines_launch',
			array(
				'label'       => esc_html__( 'Flip lines', 'avix-widgets' ),
				'description' => esc_html__( 'One per line, said in turn each time the logo is clicked or tapped.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 4,
				'default'     => "Wheee! 🎉\nAgain! Again!\n10/10 landing 🙌\nOkay, I'm dizzy 😵‍💫",
				'condition'   => array( 'show_buddy' => 'yes' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section( 'section_text', array( 'label' => esc_html__( 'Text', 'avix-widgets' ) ) );

		$this->add_control(
			'text_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Wrap a word in {curly braces} to make it a keyword, then add it under Keywords to give it pictures and a line for the character. Enter starts a new line.', 'avix-widgets' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->add_control(
			'tagline',
			array(
				'label'   => esc_html__( 'Tagline', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 4,
				'default' => 'We design conversion-focused {products}, architect technical {ecommerce} ecosystems, and scale global brands.',
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'tagline_tag',
			array(
				'label'   => esc_html__( 'Tagline HTML tag', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h2',
				'options' => array(
					'h1'  => 'H1',
					'h2'  => 'H2',
					'h3'  => 'H3',
					'p'   => 'p',
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
				'default' => 'Founded by CEO {Akib Zawayed}, our digital agency engineers flawless solutions for ambitious businesses.',
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section( 'section_keywords', array( 'label' => esc_html__( 'Keywords', 'avix-widgets' ) ) );

		$repeater = new Repeater();
		$repeater->add_control(
			'word',
			array(
				'label'       => esc_html__( 'Keyword', 'avix-widgets' ),
				'description' => esc_html__( 'Exactly as written between { } in the text.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			)
		);
		$repeater->add_control(
			'style',
			array(
				'label'   => esc_html__( 'Pictures', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'deck',
				'options' => array(
					'deck'  => esc_html__( 'Fan of 1–3 cards', 'avix-widgets' ),
					'round' => esc_html__( 'Round portrait', 'avix-widgets' ),
				),
			)
		);
		$repeater->add_control(
			'image_1',
			array(
				'label' => esc_html__( 'Picture 1', 'avix-widgets' ),
				'type'  => Controls_Manager::MEDIA,
			)
		);
		foreach ( array( 'image_2' => esc_html__( 'Picture 2', 'avix-widgets' ), 'image_3' => esc_html__( 'Picture 3', 'avix-widgets' ) ) as $key => $label ) {
			$repeater->add_control(
				$key,
				array(
					'label'     => $label,
					'type'      => Controls_Manager::MEDIA,
					'condition' => array( 'style' => 'deck' ),
				)
			);
		}
		$repeater->add_control(
			'line',
			array(
				'label'       => esc_html__( 'What the character says', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			)
		);
		$repeater->add_control(
			'link',
			array(
				'label' => esc_html__( 'Link (optional)', 'avix-widgets' ),
				'type'  => Controls_Manager::URL,
			)
		);

		$this->add_control(
			'keywords',
			array(
				'label'       => esc_html__( 'Keywords', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ word }}}',
				'default'     => $this->default_keywords(),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section( 'section_motion', array( 'label' => esc_html__( 'Motion', 'avix-widgets' ) ) );

		$this->add_control(
			'reveal',
			array(
				'label'       => esc_html__( 'Word-by-word reveal', 'avix-widgets' ),
				'description' => esc_html__( 'Words fade in from a soft blur when the section scrolls into view. Skipped for visitors who prefer reduced motion and in the editor.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'step',
			array(
				'label'     => esc_html__( 'Delay between words (ms)', 'avix-widgets' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 10, 'max' => 150, 'step' => 5 ) ),
				'default'   => array( 'size' => 40 ),
				'condition' => array( 'reveal' => 'yes' ),
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

		$this->add_responsive_control(
			'align',
			array(
				'label'                => esc_html__( 'Alignment', 'avix-widgets' ),
				'description'          => esc_html__( 'Centred by default, left-aligned on phones (easier to read on a narrow screen).', 'avix-widgets' ),
				'type'                 => Controls_Manager::CHOOSE,
				'options'              => array(
					'left'   => array(
						'title' => esc_html__( 'Left', 'avix-widgets' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => esc_html__( 'Center', 'avix-widgets' ),
						'icon'  => 'eicon-text-align-center',
					),
				),
				'selectors_dictionary' => array(
					'left'   => '--it-align: left; --it-items: flex-start;',
					'center' => '--it-align: center; --it-items: center;',
				),
				'selectors'            => array( '{{WRAPPER}} .avix-it__inner' => '{{VALUE}}' ),
			)
		);

		$this->add_control(
			'bg',
			array(
				'label'       => esc_html__( 'Background', 'avix-widgets' ),
				'description' => esc_html__( 'Also used for the thin outline that keeps the character visible on the logo.', 'avix-widgets' ),
				'type'        => Controls_Manager::COLOR,
				'selectors'   => array( '{{WRAPPER}} .avix-it' => 'background-color: {{VALUE}}; --it-bg: {{VALUE}};' ),
			)
		);

		$colors = array(
			'ink'    => array( esc_html__( 'Tagline & keywords', 'avix-widgets' ), '--it-ink' ),
			'muted'  => array( esc_html__( 'Intro text', 'avix-widgets' ), '--it-muted' ),
			'accent' => array( esc_html__( 'Accent', 'avix-widgets' ), '--it-accent' ),
		);
		foreach ( $colors as $key => $color ) {
			$this->add_control(
				$key,
				array(
					'label'     => $color[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-it' => $color[1] . ': {{VALUE}};' ),
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
				'selectors'          => array( '{{WRAPPER}} .avix-it__inner' => 'padding-top: {{TOP}}{{UNIT}}; padding-bottom: {{BOTTOM}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'max_width',
			array(
				'label'      => esc_html__( 'Content width', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 480, 'max' => 1600 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-it' => '--it-max: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'tagline_typography',
				'label'    => esc_html__( 'Tagline', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-it .avix-it__tagline',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'intro_typography',
				'label'    => esc_html__( 'Intro', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-it .avix-it__intro',
			)
		);

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ */
	/* Render                                                              */
	/* ------------------------------------------------------------------ */

	protected function render(): void {
		$s        = $this->get_settings_for_display();
		$buddy    = 'yes' === $s['show_buddy'];
		$keywords = $this->keyword_map( (array) $s['keywords'] );
		$step     = isset( $s['step']['size'] ) && '' !== $s['step']['size'] ? (int) $s['step']['size'] : 40;
		$launch   = array_values( array_filter( array_map( 'trim', preg_split( '/\R/u', (string) $s['lines_launch'] ) ) ) );

		$this->add_render_attribute(
			'root',
			array(
				'class'        => 'avix-it',
				'style'        => '--it-step:' . max( 10, min( 150, $step ) ) . 'ms;',
				'data-avix-it' => wp_json_encode(
					array(
						'reveal' => 'yes' === $s['reveal'],
						'lines'  => $buddy ? array(
							'peek'      => (string) $s['line_peek'],
							'hint'      => (string) $s['line_hint'],
							'hintTouch' => (string) $s['line_hint_touch'],
							'roll'      => (string) $s['line_roll'],
							'launch'    => $launch,
						) : null,
					)
				),
			)
		);

		$tag   = Utils::validate_html_tag( $s['tagline_tag'] );
		$index = 0;
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="avix-it__inner">
				<?php $this->render_mark( $s, $buddy ); ?>
				<?php if ( '' !== trim( (string) $s['tagline'] ) ) : ?>
					<<?php echo esc_attr( $tag ); ?> class="avix-it__tagline"><?php echo $this->words_html( (string) $s['tagline'], $keywords, $index ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in words_html(). ?></<?php echo esc_attr( $tag ); ?>>
				<?php endif; ?>
				<?php if ( '' !== trim( (string) $s['intro'] ) ) : ?>
					<p class="avix-it__intro"><?php echo $this->words_html( (string) $s['intro'], $keywords, $index ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in words_html(). ?></p>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}

	private function render_mark( array $s, $buddy ) {
		$logo = (array) $s['logo'];
		if ( empty( $logo['url'] ) ) {
			return;
		}
		$id  = $this->media_id( $logo );
		$src = $id ? wp_get_attachment_image_url( $id, 'medium' ) : '';
		$src = $src ? $src : $logo['url'];
		$alt = trim( (string) $s['logo_alt'] );
		?>
		<div class="avix-it__mark" data-it-mark>
			<?php if ( $buddy ) : ?>
				<button class="avix-it__logo" type="button" data-it-logo aria-label="<?php echo esc_attr( $alt ); ?>">
					<span class="avix-it__wheel" data-it-wheel><img src="<?php echo esc_url( $src ); ?>" alt="" width="176" height="176" decoding="async" draggable="false"></span>
				</button>
				<span class="avix-it__buddy" data-it-buddy aria-hidden="true">
					<span class="avix-it__sprite" data-it-sprite>
						<svg viewBox="0 0 10 12" focusable="false">
							<rect class="avix-it__b-leg avix-it__b-leg--l" x="2" y="7" width="2" height="5"/>
							<rect class="avix-it__b-leg avix-it__b-leg--r" x="6" y="7" width="2" height="5"/>
							<rect class="avix-it__b-body" x="2" y="3" width="6" height="4"/>
							<rect class="avix-it__b-head" x="3" y="0" width="4" height="3"/>
							<rect class="avix-it__b-arm avix-it__b-arm--l" x="0" y="3" width="2" height="3"/>
							<rect class="avix-it__b-arm avix-it__b-arm--r" x="8" y="3" width="2" height="3"/>
						</svg>
					</span>
				</span>
				<span class="avix-it__bubble" data-it-bubble aria-hidden="true"><span data-it-say></span></span>
			<?php else : ?>
				<span class="avix-it__logo"><span class="avix-it__wheel"><img src="<?php echo esc_url( $src ); ?>" alt="<?php echo esc_attr( $alt ); ?>" width="176" height="176" decoding="async"></span></span>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Keyword rows keyed by their lower-case word.
	 */
	private function keyword_map( array $rows ) {
		$map = array();
		foreach ( $rows as $row ) {
			$word = trim( (string) ( $row['word'] ?? '' ) );
			if ( '' !== $word ) {
				$map[ mb_strtolower( $word ) ] = $row;
			}
		}
		return $map;
	}

	/**
	 * Splits text into words (each its own span for the reveal) and turns
	 * {keywords} into interactive keywords. Everything is escaped here.
	 */
	private function words_html( $text, array $keywords, &$index ) {
		$lines = preg_split( '/\R/u', trim( $text ) );
		$out   = array();
		foreach ( $lines as $line ) {
			preg_match_all( '/[^\s{]*\{[^}]*\}[^\s{]*|\S+/u', $line, $matches );
			$words = array();
			foreach ( $matches[0] as $token ) {
				if ( preg_match( '/^([^{]*)\{([^}]*)\}(.*)$/su', $token, $parts ) && '' !== trim( $parts[2] ) ) {
					$html = esc_html( $parts[1] ) . $this->keyword_html( trim( $parts[2] ), $keywords, $index ) . esc_html( $parts[3] );
				} else {
					$html = esc_html( str_replace( array( '{', '}' ), '', $token ) );
				}
				$words[] = '<span class="avix-it__w" style="--i:' . (int) $index . '">' . $html . '</span>';
				++$index;
			}
			$out[] = implode( ' ', $words );
		}
		return implode( '<br>', $out );
	}

	private function keyword_html( $word, array $keywords, $index ) {
		$row    = $keywords[ mb_strtolower( $word ) ] ?? array();
		$round  = 'round' === ( $row['style'] ?? 'deck' );
		$images = array();
		foreach ( $round ? array( 'image_1' ) : array( 'image_1', 'image_2', 'image_3' ) as $key ) {
			$url = $this->card_url( (array) ( $row[ $key ] ?? array() ) );
			if ( '' !== $url ) {
				$images[] = $url;
			}
		}
		$link = (array) ( $row['link'] ?? array() );
		$tag  = empty( $link['url'] ) ? 'span' : 'a';
		$key  = 'kw-' . $index;

		$this->add_render_attribute(
			$key,
			array(
				'class'      => array( 'avix-it__kw', $round ? 'avix-it__kw--round' : 'avix-it__kw--deck' ),
				'data-it-kw' => '',
				'data-line'  => trim( (string) ( $row['line'] ?? '' ) ),
			)
		);
		if ( 'a' === $tag ) {
			$this->add_link_attributes( $key, $link );
		}

		$html = '<' . $tag . ' ' . $this->get_render_attribute_string( $key ) . '>'
			. str_replace( ' ', '&nbsp;', esc_html( $word ) );
		if ( $images ) {
			$html .= '<span class="avix-it__deck" aria-hidden="true">';
			foreach ( $images as $url ) {
				$html .= '<img class="avix-it__card" src="' . esc_url( $url ) . '" alt="" loading="lazy" decoding="async">';
			}
			$html .= '</span>';
		}
		return $html . '</' . $tag . '>';
	}

	/**
	 * A card-sized copy of a picture. GIFs stay original: WordPress's resized
	 * copies of a GIF are still images.
	 */
	private function card_url( array $media ) {
		$url = (string) ( $media['url'] ?? '' );
		if ( '' === $url || preg_match( '/\.gif(\?|$)/i', $url ) ) {
			return $url;
		}
		$id    = $this->media_id( $media );
		$sized = $id ? wp_get_attachment_image_url( $id, 'medium_large' ) : '';
		return $sized ? $sized : $url;
	}

	/**
	 * The keywords and pictures from the current homepage intro.
	 */
	private function default_keywords() {
		$site = 'https://avixdigital.com/wp-content/uploads/';
		// The deck card that used to be hotlinked from the old portfolio subdomain, now on the main site.
		$card = $site . '2026/10/avixdigital-intro-card.webp';
		return array(
			array(
				'word'    => 'products',
				'style'   => 'deck',
				'image_1' => array( 'url' => $site . '2026/03/Untitled-design227.webp' ),
				'image_2' => array( 'url' => $card ),
				'image_3' => array( 'url' => $site . '2026/03/Untitled-design227.webp' ),
				'line'    => 'Our favourite builds 👀',
			),
			array(
				'word'    => 'ecommerce',
				'style'   => 'deck',
				'image_1' => array( 'url' => $card ),
				'image_2' => array( 'url' => $site . '2026/06/Untitled-design235.png' ),
				'image_3' => array( 'url' => $card ),
				'line'    => 'Stores that actually sell 🛒',
			),
			array(
				'word'    => 'Akib Zawayed',
				'style'   => 'round',
				'image_1' => array( 'url' => $site . '2026/06/Untitled-design2-1024x1024.gif' ),
				'line'    => "That's my human! 🧡",
			),
		);
	}
}
