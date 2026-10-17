<?php
/**
 * Case Study Stack: the services and technology behind a case study. A
 * sticky "06 · Stack" label sits beside the services (linked to the matching
 * service pages), the client's industry and market, and a two-column list
 * of the tools used, each with its brand mark and one line on what it does
 * here. Tools only seen on the live site carry a "Seen on the live site" tag.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Utils;
use Elementor\Widget_Base;

defined( 'ABSPATH' ) || exit;

class Case_Study_Stack extends Widget_Base {

	use \AvixWidgets\Case_Studies\Source;

	/**
	 * A tool's brand marks, matched in its name (case-insensitive), most
	 * specific first. A rule claims the words it matched, so a broader rule
	 * further down cannot match them again: "Shopify Checkout Blocks" is
	 * Checkout Blocks, not Shopify. A name can carry two marks ("Judge.me &
	 * Trustpilot"), shown in the order they appear in the name (a pill always
	 * goes on the badge). Keys are a logo file (LOGOS) or a Brand_Icons mark,
	 * drawn inline.
	 */
	const MARKS = array(
		// Products named after their platform, before the platform itself.
		// Shopify's app only: WooCommerce's Cart & Checkout Blocks are also "checkout blocks".
		'checkout-blocks'    => '/\bshopify\s+checkout\s+blocks\b/i',
		'woo-discount-rules' => '/\bwoo(?:commerce)?\s+discount\s+rules\b|\bdiscount\s+rules\s+for\s+woo(?:commerce)?\b|\bflycart\b/i',
		'shoppay'            => '/\bshop\s?pay\b|\bshop\.app\b/i',
		'googlepay'          => '/\bgoogle\s?pay\b|\bg\s?pay\b/i',
		'globo-filters'      => '/\bglobo\b/i',
		'judgeme'            => '/\bjudge\.?me\b/i',
		'trustpilot'         => '/\btrust\s?pilot\b/i',
		'rebuy'              => '/\brebuy\b/i',
		'klaviyo'            => '/\bklaviyo\b/i',
		'gorgias'            => '/\bgorgias\b/i',
		'returnista'         => '/\breturnista\b/i',
		'juo'                => '/\bjuo\b/i',
		'smile'              => '/\bsmile\.io\b|\bsmile\s+(?:loyalty|rewards)\b|^\s*smile\s*$/i',
		'cookieyes'          => '/\bcookie\s?yes\b/i',
		'funnelkit'          => '/\bfunnel\s?kit\b|\bwoofunnels\b/i',
		'wployalty'          => '/\bwp\s?loyalty\b/i',
		'woocommerce'        => '/\bwoo\s?commerce\b|^\s*woo\s*$/i',
		'bricks'             => '/\bbricks(?:\s?builder)?\b(?![\s-]+(?:and|&|n|\'n\')[\s-]+mortar\b)/i',
		'webwinkelkeur'      => '/\bwebwinkel\s?keur\b/i',
		'bol'                => '/\bbol(?:\.com)?\b/i',
		'litespeed'          => '/\blite\s?speed\b/i',
		'flyingpress'        => '/\bflying\s?press\b/i',
		'elfsight'           => '/\belfsight\b/i',
		'swiper'             => '/\bswiper(?:\.?js)?\b/i',
		'gsap'               => '/\bgsap\b|\bgreen\s?sock\b|\bscrolltrigger\b/i',
		'liquid'             => '/\bliquid\b(?!\s*web\b)/i',
		'javascript'         => '/\bjava\s?script\b|\bvanilla\s+js\b/i',
		// Brand_Icons marks.
		'shopify'            => '/\bshopify/i',
		'webflow'            => '/\bwebflow/i',
		'wordpress'          => '/\bwordpress/i',
		'figma'              => '/\bfigma\b/i',
		'react'              => '/\breact\b/i',
		'nextjs'             => '/\bnext\.?js\b|^\s*next(?=\s*(?:v?\d|$|[(\/&+,]))/i',
		'nodejs'             => '/\bnode\.?js\b|^\s*node(?=\s*(?:v?\d|$|[(\/&+,]))/i',
		'elementor'          => '/\belementor/i',
	);

	/**
	 * "for WooCommerce", "on Shopify": the platform a product runs on, not a
	 * second tool. Ignored while the rest of the name has a mark of its own.
	 */
	const PLATFORM_QUALIFIER = '/\b(?:for|on)\s+(?:woo(?:commerce)?|shopify(?:\s+plus)?|wordpress|webflow|elementor)\b/i';

	/**
	 * Official logos in assets/images/brands/ (sources in its README.md):
	 * key => array( file, mode, width, height ). A "glyph" sits on the tile
	 * like the inline marks, in a width x height box; a "pill" is a wide
	 * mark with its own outline, a glyph on its own and its own badge in a
	 * pair; a "tile" is a square app icon that fills the tile.
	 */
	const LOGOS = array(
		'bol'                => array( 'bol.webp', 'tile', 36, 36 ),
		'bricks'             => array( 'bricks.webp', 'tile', 36, 36 ),
		'checkout-blocks'    => array( 'checkout-blocks.webp', 'tile', 36, 36 ),
		'cookieyes'          => array( 'cookieyes.svg', 'glyph', 21, 21 ),
		'elfsight'           => array( 'elfsight.svg', 'glyph', 20, 20 ),
		'flyingpress'        => array( 'flyingpress.svg', 'glyph', 22, 22 ),
		'funnelkit'          => array( 'funnelkit.svg', 'glyph', 23, 23 ),
		'globo-filters'      => array( 'globo-filters.webp', 'tile', 36, 36 ),
		'googlepay'          => array( 'googlepay.svg', 'pill', 30, 16 ),
		'gorgias'            => array( 'gorgias.svg', 'glyph', 20, 20 ),
		'gsap'               => array( 'gsap.webp', 'glyph', 20, 20 ),
		'javascript'         => array( 'javascript.svg', 'tile', 36, 36 ),
		'judgeme'            => array( 'judgeme.svg', 'tile', 36, 36 ),
		'juo'                => array( 'juo.svg', 'glyph', 21, 21 ),
		'klaviyo'            => array( 'klaviyo.svg', 'glyph', 19, 19 ),
		// Liquid's only mark is a thin light-grey outline: a larger box so it reads.
		'liquid'             => array( 'liquid.webp', 'glyph', 28, 28 ),
		'litespeed'          => array( 'litespeed.svg', 'glyph', 22, 22 ),
		'rebuy'              => array( 'rebuy.svg', 'glyph', 19, 19 ),
		'returnista'         => array( 'returnista.webp', 'tile', 36, 36 ),
		'shoppay'            => array( 'shoppay.svg', 'glyph', 20, 20 ),
		'smile'              => array( 'smile.webp', 'tile', 36, 36 ),
		'swiper'             => array( 'swiper.svg', 'glyph', 20, 20 ),
		'trustpilot'         => array( 'trustpilot.svg', 'glyph', 21, 21 ),
		'webwinkelkeur'      => array( 'webwinkelkeur.svg', 'glyph', 21, 21 ),
		'woo-discount-rules' => array( 'woo-discount-rules.webp', 'glyph', 22, 22 ),
		'woocommerce'        => array( 'woocommerce.webp', 'glyph', 21, 21 ),
		'wployalty'          => array( 'wployalty.webp', 'glyph', 21, 21 ),
	);

	/** The mark for a typeface: "Aa" set in it, no logo. */
	const TYPEFACE = 'typeface';

	/** Typeface names, and words that say a tool is one. */
	const TYPEFACE_NAME = '/\b(?:type\s?faces?|fonts?(?!\s*awesome)|typography|space\s+grotesk|inter(?!-)|roboto(?:\s+(?:flex|mono|slab|serif))?|open\s+sans|lato|montserrat|poppins|playfair(?:\s+display)?|source\s+(?:sans|serif|code)(?:\s+pro)?|ibm\s+plex(?:\s+\w+)?|dm\s+(?:sans|serif|mono)|manrope|nunito(?:\s+sans)?|raleway|work\s+sans|plus\s+jakarta\s+sans|satoshi|general\s+sans|clash\s+display|neue\s+montreal|helvetica(?:\s+neue)?|futura|garamond)\b/i';

	/**
	 * A description that says the tool is a typeface. Not "fonts": speed and
	 * CDN tools "preload the brand fonts".
	 */
	const TYPEFACE_TEXT = '/\btype\s?face\b/i';

	public function get_name(): string {
		return 'avix-case-study-stack';
	}

	public function get_title(): string {
		return esc_html__( 'Case Study Stack', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-bullet-list';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'case study', 'portfolio', 'stack', 'technology', 'tools', 'services', 'avix' );
	}

	public function get_style_depends(): array {
		return array( 'avix-case-study-stack' );
	}

	public function get_script_depends(): array {
		return array( 'avix-case-study-stack' );
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
		$this->controls_content();
		$this->controls_style();
		$this->controls_type();
	}

	private function controls_content() {
		$this->start_controls_section( 'section_content', array( 'label' => esc_html__( 'Stack', 'avix-widgets' ) ) );

		$this->add_control(
			'number',
			array(
				'label'   => esc_html__( 'Number', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '06',
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'label',
			array(
				'label'   => esc_html__( 'Label', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Stack', 'avix-widgets' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Headline', 'avix-widgets' ),
				'description' => esc_html__( 'Wrap words in [brackets] to highlight them in orange. Press Enter for a new line.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => esc_html__( 'Services & technology', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'   => esc_html__( 'Headline tag', 'avix-widgets' ),
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
			'services_label',
			array(
				'label'     => esc_html__( 'Services label', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Services', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'services',
			array(
				'label'       => esc_html__( 'Services', 'avix-widgets' ),
				'description' => esc_html__( 'Comma-separated. Leave empty to use the case study’s services (or its service categories).', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__( 'From case study: services', 'avix-widgets' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'service_links',
			array(
				'label'       => esc_html__( 'Link services to service pages', 'avix-widgets' ),
				'description' => esc_html__( 'A service whose category has a service page becomes a link to it.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'show_industry',
			array(
				'label'       => esc_html__( 'Industry row', 'avix-widgets' ),
				'description' => esc_html__( 'The client’s industry, and the market when the case study has one.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'industry_label',
			array(
				'label'     => esc_html__( 'Industry label', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Industry', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_industry' => 'yes' ),
			)
		);

		$this->add_control(
			'tech_label',
			array(
				'label'     => esc_html__( 'Technology heading', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Technology', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'stack',
			array(
				'label'       => esc_html__( 'Technology', 'avix-widgets' ),
				'description' => esc_html__( 'One tool per line: Name: what it does here. Add “| observed” at the end of a line for a tool you only saw on the live site. Up to 12. Leave empty to use the case study.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 8,
				'default'     => '',
				'placeholder' => "Shopify: storefront platform and checkout\nKlaviyo: email sign-up | observed",
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'observed_label',
			array(
				'label'       => esc_html__( 'Observed tag', 'avix-widgets' ),
				'description' => esc_html__( 'Shown on tools marked “| observed”, so visitors know they were seen on the live site rather than built by us.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Seen on the live site', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'sticky_label',
			array(
				'label'       => esc_html__( 'Sticky label', 'avix-widgets' ),
				'description' => esc_html__( 'On wide screens the label and headline stay in view while the list scrolls past.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'anchor',
			array(
				'label'       => esc_html__( 'Anchor', 'avix-widgets' ),
				'description' => esc_html__( 'The section’s #id. Leave empty for #stack.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => 'stack',
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
				'label'       => esc_html__( 'Theme', 'avix-widgets' ),
				'description' => esc_html__( 'Sets every colour in the section: background, text, lines, chips and icon tiles. Any colour you pick below still wins.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'white',
				'options'     => array(
					'white' => esc_html__( 'White', 'avix-widgets' ),
					'paper' => esc_html__( 'Warm paper', 'avix-widgets' ),
					'dark'  => esc_html__( 'Dark', 'avix-widgets' ),
				),
			)
		);

		$this->add_control(
			'colors_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Leave the colours empty to follow the theme. When you change the background, set the Headline and Text colours too, so nothing disappears.', 'avix-widgets' ),
				'content_classes' => 'elementor-descriptor',
				'separator'       => 'before',
			)
		);

		$colors = array(
			'bg'          => array( esc_html__( 'Background', 'avix-widgets' ), '--cst-bg' ),
			'ink'         => array( esc_html__( 'Headline', 'avix-widgets' ), '--cst-ink' ),
			'muted'       => array( esc_html__( 'Text', 'avix-widgets' ), '--cst-muted' ),
			'line'        => array( esc_html__( 'Lines', 'avix-widgets' ), '--cst-line' ),
			'accent'      => array( esc_html__( 'Accent', 'avix-widgets' ), '--cst-accent' ),
			'accent_text' => array( esc_html__( 'Small orange text', 'avix-widgets' ), '--cst-accent-text' ),
			'tile'        => array( esc_html__( 'Icon tiles', 'avix-widgets' ), '--cst-tile' ),
		);
		foreach ( $colors as $key => $color ) {
			$this->add_control(
				'color_' . $key,
				array(
					'label'     => $color[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-cst' => $color[1] . ': {{VALUE}};' ),
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
				'selectors'          => array( '{{WRAPPER}} .avix-cst' => '--cst-pad-top: {{TOP}}{{UNIT}}; --cst-pad-bottom: {{BOTTOM}}{{UNIT}};' ),
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
				'selectors'  => array( '{{WRAPPER}} .avix-cst' => '--cst-max: {{SIZE}}{{UNIT}};' ),
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
			'title'  => array( esc_html__( 'Headline', 'avix-widgets' ), '.avix-cst__title' ),
			'labels' => array( esc_html__( 'Row labels', 'avix-widgets' ), '.avix-cst__fact dt' ),
			'name'   => array( esc_html__( 'Tool names', 'avix-widgets' ), '.avix-cst__name' ),
			'text'   => array( esc_html__( 'Tool text', 'avix-widgets' ), '.avix-cst__text' ),
		);
		foreach ( $groups as $key => $group ) {
			$this->add_group_control(
				Group_Control_Typography::get_type(),
				array(
					'name'     => $key . '_typography',
					'label'    => $group[0],
					'selector' => '{{WRAPPER}} .avix-cst ' . $group[1],
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
		$html  = preg_replace( '/\[([^\[\]]+)\]/u', '<span class="avix-cst__accent">$1</span>', $html );
		$lines = preg_split( '/\r\n|\r|\n/', (string) $html );
		return implode( ' <br class="avix-cst__break">', array_map( 'trim', $lines ) );
	}

	/**
	 * Tool rows from "Name: text | observed" lines, at most 12.
	 *
	 * @param string $text Raw textarea.
	 * @return array List of array( name, text, observed ).
	 */
	private function stack_rows( $text ) {
		$rows = array();
		foreach ( preg_split( '/\R/u', (string) $text ) as $line ) {
			$line     = trim( $line );
			$observed = false;
			if ( preg_match( '/\|\s*observed\s*$/i', $line ) ) {
				$observed = true;
				$line     = trim( (string) preg_replace( '/\|\s*observed\s*$/i', '', $line ) );
			}
			if ( '' === $line ) {
				continue;
			}
			$parts  = explode( ':', $line, 2 );
			$rows[] = array( trim( $parts[0] ), isset( $parts[1] ) ? trim( $parts[1] ) : '', $observed );
		}
		return array_slice( $rows, 0, 12 );
	}

	/**
	 * Tool rows from the data layer (array of [name, text, observed]).
	 *
	 * @param mixed $value Meta value.
	 */
	private function meta_rows( $value ) {
		if ( is_string( $value ) ) {
			return $this->stack_rows( $value );
		}
		$rows = array();
		foreach ( (array) $value as $row ) {
			$row  = array_values( (array) $row );
			$name = trim( (string) ( $row[0] ?? '' ) );
			if ( '' === $name ) {
				continue;
			}
			$rows[] = array( $name, trim( (string) ( $row[1] ?? '' ) ), ! empty( $row[2] ) );
		}
		return array_slice( $rows, 0, 12 );
	}

	/**
	 * Services as chips: array( label, link ). An override is plain text;
	 * otherwise the case study's own list, linked where a service category
	 * of the same name has a service page; else the categories themselves.
	 *
	 * @param array $s  Settings.
	 * @param array $cs Case study data.
	 */
	private function services( array $s, array $cs ) {
		$links = 'yes' === ( $s['service_links'] ?? '' );
		$raw   = trim( (string) ( $s['services'] ?? '' ) );
		if ( '' !== $raw ) {
			$names = array_filter( array_map( 'trim', explode( ',', $raw ) ), 'strlen' );
			return array_map(
				static function ( $name ) {
					return array( $name, '' );
				},
				array_values( $names )
			);
		}

		$terms = array();
		foreach ( (array) ( $cs['terms']['service'] ?? array() ) as $term ) {
			$term = array_values( (array) $term );
			$name = trim( (string) ( $term[0] ?? '' ) );
			if ( '' !== $name ) {
				$terms[] = array(
					'name' => $name,
					'slug' => (string) ( $term[1] ?? '' ),
					'link' => (string) ( $term[2] ?? '' ),
				);
			}
		}

		$list = $cs['services'] ?? array();
		$list = is_array( $list ) ? $list : explode( ',', (string) $list );
		$list = array_values( array_filter( array_map( 'trim', array_map( 'strval', $list ) ), 'strlen' ) );

		$out = array();
		if ( $list ) {
			foreach ( $list as $name ) {
				$link = '';
				if ( $links ) {
					foreach ( $terms as $term ) {
						if ( 0 === strcasecmp( $term['name'], $name ) || sanitize_title( $name ) === $term['slug'] ) {
							$link = $term['link'];
							break;
						}
					}
				}
				$out[] = array( $name, $link );
			}
			return $out;
		}
		foreach ( $terms as $term ) {
			$out[] = array( $term['name'], $links ? $term['link'] : '' );
		}
		return $out;
	}

	/**
	 * The marks for a tool: up to two MARKS keys in the order they appear in
	 * the name, array( TYPEFACE ) for a typeface, or array() for the pixel
	 * square. Pure: no WordPress needed (tests/case-study-stack-marks.php).
	 *
	 * @param string $name Tool name.
	 * @param string $text What the tool does here (only read for typefaces).
	 * @return string[]
	 */
	public static function marks_for( string $name, string $text = '' ): array {
		// "Discount Rules for WooCommerce" is one tool; "Theme for Shopify" still gets Shopify.
		$keys = self::match_marks( (string) preg_replace( self::PLATFORM_QUALIFIER, ' ', $name ) );
		if ( ! $keys ) {
			$keys = self::match_marks( $name );
		}
		if ( ! $keys && ( preg_match( self::TYPEFACE_NAME, $name ) || preg_match( self::TYPEFACE_TEXT, $text ) ) ) {
			$keys = array( self::TYPEFACE );
		}
		return $keys;
	}

	/**
	 * MARKS keys found in a name, first mention first, at most two.
	 *
	 * @param string $name Tool name.
	 * @return string[]
	 */
	private static function match_marks( string $name ): array {
		$found = array();
		foreach ( self::MARKS as $key => $pattern ) {
			if ( ! preg_match_all( $pattern, $name, $hits, PREG_OFFSET_CAPTURE ) ) {
				continue;
			}
			foreach ( $hits[0] as $hit ) {
				// Claim the words, so a broader rule further down cannot match them.
				$name = substr_replace( $name, str_repeat( ' ', strlen( $hit[0] ) ), $hit[1], strlen( $hit[0] ) );
			}
			$found[ $key ] = $hits[0][0][1];
		}
		asort( $found );
		return array_slice( array_keys( $found ), 0, 2 );
	}

	/**
	 * The font family to set "Aa" in for a typeface tool, or '' when the
	 * name is not a family name ("Google Fonts", "Brand typography").
	 *
	 * @param string $name Tool name.
	 */
	public static function typeface_family( string $name ): string {
		$name = trim( (string) preg_replace( '/\(.*$/s', '', $name ) );
		if ( preg_match( '/\b(?:fonts?|type\s?faces?|typography)\b/i', $name ) || ! preg_match( '/^[\p{L}\p{N}][\p{L}\p{N} \-]{0,47}$/u', $name ) ) {
			return '';
		}
		return $name;
	}

	/**
	 * One mark as HTML: an official logo file as a decorative <img>, or an
	 * inline Brand_Icons SVG. '' when neither is available.
	 *
	 * @param string $key MARKS key.
	 */
	private function mark_html( $key ) {
		if ( isset( self::LOGOS[ $key ] ) ) {
			if ( ! defined( 'AVIX_EW_URL' ) ) {
				return '';
			}
			list( $file, $mode, $width, $height ) = self::LOGOS[ $key ];
			// The box size is a custom property, so a pair can scale it down.
			$size = 'tile' === $mode ? '' : sprintf( ' style="--cst-logo-w:%dpx;--cst-logo-h:%dpx;"', $width, $height );
			return sprintf(
				'<img class="avix-cst__logo no-pixel-reveal" src="%s" alt="" width="%d" height="%d" loading="lazy" decoding="async"%s>',
				esc_url( AVIX_EW_URL . 'assets/images/brands/' . $file ),
				$width,
				$height,
				$size
			);
		}
		if ( class_exists( __NAMESPACE__ . '\Brand_Icons' ) ) {
			return (string) Brand_Icons::svg( $key );
		}
		return '';
	}

	/**
	 * A tool's icon tile: array( modifier classes, inner HTML ). One mark
	 * sits on the tile (an app icon fills it); two share it, the first
	 * larger at the top left and the second smaller at the bottom right (a
	 * pill, like Google Pay, always goes second, the first at its height);
	 * a typeface gets "Aa" set in it; anything else keeps the pixel square.
	 *
	 * @param string $name Tool name.
	 * @param string $text What the tool does here.
	 */
	private function icon( $name, $text ) {
		$keys = self::marks_for( (string) $name, (string) $text );

		if ( array( self::TYPEFACE ) === $keys ) {
			$family = self::typeface_family( (string) $name );
			$style  = '' !== $family ? ' style="' . esc_attr( "font-family:'" . $family . "',var(--cst-font-display);" ) . '"' : '';
			return array( ' avix-cst__icon--type', '<span class="avix-cst__type"' . $style . '>Aa</span>' );
		}

		$marks = array();
		foreach ( $keys as $key ) {
			$html = $this->mark_html( $key );
			if ( '' !== $html ) {
				$marks[] = array( $html, self::LOGOS[ $key ][1] ?? 'glyph' );
			}
		}
		if ( ! $marks ) {
			return array( ' avix-cst__icon--px', '<i></i>' );
		}
		if ( 1 === count( $marks ) ) {
			return array( 'tile' === $marks[0][1] ? ' avix-cst__icon--tile' : '', $marks[0][0] );
		}
		// A pill only fits on the badge: "Google Pay & Shop Pay" draws like "Shop Pay & Google Pay".
		if ( 'pill' === $marks[0][1] && 'pill' !== $marks[1][1] ) {
			$marks = array_reverse( $marks );
		}
		$html = '';
		foreach ( $marks as $i => $mark ) {
			$mode  = in_array( $mark[1], array( 'tile', 'pill' ), true ) ? ' avix-cst__mark--' . $mark[1] : '';
			$html .= '<span class="avix-cst__mark avix-cst__mark--' . ( 0 === $i ? 'a' : 'b' ) . $mode . '">' . $mark[0] . '</span>';
		}
		// Next to a pill the first mark matches its height and keeps its clear space.
		$pill = 'pill' === $marks[1][1] && 'pill' !== $marks[0][1] ? ' avix-cst__icon--pill' : '';
		return array( ' avix-cst__icon--pair' . $pill, $html );
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

		$services = $this->services( $s, $cs );

		$industry = array();
		$market   = '';
		if ( 'yes' === ( $s['show_industry'] ?? '' ) ) {
			foreach ( (array) ( $cs['terms']['industry'] ?? array() ) as $term ) {
				$term = array_values( (array) $term );
				$name = trim( (string) ( $term[0] ?? '' ) );
				if ( '' !== $name ) {
					$industry[] = $name;
				}
			}
			$market = trim( (string) ( $cs['market'] ?? '' ) );
		}

		$raw   = trim( (string) ( $s['stack'] ?? '' ) );
		$tools = '' !== $raw ? $this->stack_rows( $raw ) : $this->meta_rows( $cs['stack'] ?? array() );

		if ( ! $services && ! $industry && '' === $market && ! $tools ) {
			if ( $this->is_editor() ) {
				$this->cs_alert( esc_html__( 'Case Study Stack: add services or tools to the case study (Facts and Stack tabs), or type them here.', 'avix-widgets' ) );
			}
			return;
		}

		$theme    = in_array( $s['theme'] ?? 'white', array( 'white', 'paper', 'dark' ), true ) ? $s['theme'] : 'white';
		$title    = trim( (string) ( $s['title'] ?? '' ) );
		$tag      = Utils::validate_html_tag( $s['title_tag'] ?? 'h2' );
		$title_id = 'avix-cst-title-' . $this->get_id();
		$number   = trim( (string) ( $s['number'] ?? '' ) );
		$label    = trim( (string) ( $s['label'] ?? '' ) );
		$sticky   = 'yes' === ( $s['sticky_label'] ?? '' );
		$anchor   = sanitize_html_class( trim( (string) ( $s['anchor'] ?? '' ) ) );
		$anchor   = '' !== $anchor ? $anchor : 'stack';
		$observed = trim( (string) ( $s['observed_label'] ?? '' ) );

		$classes = array( 'avix-cst', 'avix-cst--' . $theme );
		if ( 'dark' === $theme ) {
			$classes[] = 'avix-csk-on-dark';
		}
		if ( $sticky ) {
			$classes[] = 'avix-cst--sticky';
		}
		$this->add_render_attribute(
			'root',
			array(
				'class'         => $classes,
				'id'            => $anchor,
				'data-avix-cst' => wp_json_encode( array( 'sticky' => $sticky ) ),
			)
		);
		if ( '' !== $title ) {
			$this->add_render_attribute( 'root', 'aria-labelledby', $title_id );
		} else {
			$this->add_render_attribute( 'root', 'aria-label', '' !== $label ? $label : esc_html__( 'Services & technology', 'avix-widgets' ) );
		}
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="avix-cst__inner">
				<div class="avix-cst__grid">
					<div class="avix-cst__aside" data-cst-aside>
						<?php if ( '' !== $number || '' !== $label ) : ?>
							<p class="avix-cst__eyebrow avix-csk-eyebrow avix-cst__rise" style="--i:0;"><span class="avix-csk-eyebrow__px avix-cst__px" aria-hidden="true"></span><?php
							if ( '' !== $number ) {
								echo '<span class="avix-cst__num">' . esc_html( $number ) . '</span>';
							}
							if ( '' !== $number && '' !== $label ) {
								echo '<span class="avix-cst__dot" aria-hidden="true">·</span>';
							}
							if ( '' !== $label ) {
								echo '<span>' . esc_html( $label ) . '</span>';
							}
							?></p>
						<?php endif; ?>
						<?php if ( '' !== $title ) : ?>
							<<?php echo esc_html( $tag ); ?> id="<?php echo esc_attr( $title_id ); ?>" class="avix-cst__title avix-cst__rise" style="--i:1;"><?php echo $this->accent_html( $title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in accent_html(). ?></<?php echo esc_html( $tag ); ?>>
						<?php endif; ?>
					</div>
					<div class="avix-cst__main">
						<?php if ( $services || $industry || '' !== $market ) : ?>
							<dl class="avix-cst__facts avix-cst__rise" style="--i:2;">
								<?php if ( $services ) : ?>
									<div class="avix-cst__fact">
										<dt><?php echo esc_html( trim( (string) ( $s['services_label'] ?? '' ) ) ); ?></dt>
										<dd>
											<ul class="avix-cst__chips">
												<?php foreach ( $services as $service ) : ?>
													<li>
														<?php if ( '' !== esc_url( $service[1] ) ) : ?>
															<a class="avix-cst__chip avix-cst__chip--link" href="<?php echo esc_url( $service[1] ); ?>"><?php echo esc_html( $service[0] ); ?><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 17 17 7M7 7h10v10"/></svg></a>
														<?php else : ?>
															<span class="avix-cst__chip"><?php echo esc_html( $service[0] ); ?></span>
														<?php endif; ?>
													</li>
												<?php endforeach; ?>
											</ul>
										</dd>
									</div>
								<?php endif; ?>
								<?php if ( $industry || '' !== $market ) : ?>
									<div class="avix-cst__fact">
										<dt><?php echo esc_html( trim( (string) ( $s['industry_label'] ?? '' ) ) ); ?></dt>
										<dd class="avix-cst__industry">
											<?php if ( $industry ) : ?>
												<span class="avix-cst__industry-name"><?php echo esc_html( implode( ', ', $industry ) ); ?></span>
											<?php endif; ?>
											<?php if ( '' !== $market ) : ?>
												<span class="avix-cst__market"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 21s-6.5-5.6-6.5-11A6.5 6.5 0 0 1 18.5 10c0 5.4-6.5 11-6.5 11Z"/><circle cx="12" cy="10" r="2.3"/></svg><?php echo esc_html( $market ); ?></span>
											<?php endif; ?>
										</dd>
									</div>
								<?php endif; ?>
							</dl>
						<?php endif; ?>
						<?php if ( $tools ) : ?>
							<div class="avix-cst__tech">
								<?php if ( '' !== trim( (string) ( $s['tech_label'] ?? '' ) ) ) : ?>
									<?php // The count sits beside the heading, not in it, so the H3 reads "Technology" (not "Technology09"). ?>
								<div class="avix-cst__tech-head avix-cst__rise" style="--i:3;"><h3 class="avix-cst__tech-title"><?php echo esc_html( trim( (string) $s['tech_label'] ) ); ?></h3><span class="avix-cst__count" aria-hidden="true"><?php echo esc_html( str_pad( (string) count( $tools ), 2, '0', STR_PAD_LEFT ) ); ?></span></div>
								<?php endif; ?>
								<ul class="avix-cst__list">
									<?php foreach ( $tools as $i => $tool ) : ?>
										<?php $icon = $this->icon( $tool[0], $tool[1] ); ?>
										<li class="avix-cst__item" style="--i:<?php echo (int) $i; ?>;">
											<span class="avix-cst__icon<?php echo esc_attr( $icon[0] ); ?>" aria-hidden="true"><?php
											// Static brand SVG or logo <img> (URL escaped in mark_html()), "Aa" or a pixel square.
											echo $icon[1]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built in icon() from static markup and escaped values.
											?></span>
											<span class="avix-cst__copy">
												<span class="avix-cst__name"><?php echo esc_html( $tool[0] ); ?></span>
												<?php if ( '' !== $tool[1] ) : ?>
													<span class="avix-cst__text"><?php echo esc_html( $tool[1] ); ?></span>
												<?php endif; ?>
												<?php if ( $tool[2] && '' !== $observed ) : ?>
													<span class="avix-cst__tag"><?php echo esc_html( $observed ); ?></span>
												<?php endif; ?>
											</span>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</section>
		<?php
	}
}
