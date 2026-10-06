<?php
/**
 * Founder: a big rounded card with the founder's photo on one side and a
 * personal quote on the other. A "Watch introduction" pill opens the video in
 * an accessible pop-up, and the pixel character peeks over the top of the
 * photo, popping up to say hi when the visitor reaches for play.
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

class Founder extends Widget_Base {

	use Media;

	const PHOTO = 'https://avixdigital.com/wp-content/uploads/2025/12/FullSizeRender-3.jpg';

	const MARK = 'https://avixdigital.com/wp-content/uploads/2026/05/Untitled-design237.webp';

	const VIDEO = 'https://www.youtube.com/watch?v=1EUSI-cBKgo';

	/**
	 * Pixel icons for the highlights, drawn on an 8×8 grid like the character.
	 */
	const ICONS = array(
		'star'   => array( '0 0 8 8', '<rect x="3" y="0" width="2" height="2"/><rect x="0" y="2" width="8" height="1"/><rect x="1" y="3" width="6" height="1"/><rect x="2" y="4" width="4" height="1"/><rect x="1" y="5" width="6" height="1"/><rect x="1" y="6" width="2" height="1"/><rect x="5" y="6" width="2" height="1"/><rect x="0" y="7" width="2" height="1"/><rect x="6" y="7" width="2" height="1"/>' ),
		'rocket' => array( '0 0 8 8', '<rect x="3" y="0" width="2" height="1"/><rect x="2" y="1" width="4" height="1"/><rect x="2" y="2" width="1" height="1"/><rect x="5" y="2" width="1" height="1"/><rect x="2" y="3" width="4" height="2"/><rect x="1" y="5" width="6" height="1"/><rect x="0" y="6" width="2" height="1"/><rect x="3" y="6" width="2" height="1"/><rect x="6" y="6" width="2" height="1"/><rect class="is-soft" x="3" y="7" width="2" height="1"/>' ),
		'globe'  => array( '0 -0.5 8 8', '<rect x="2" y="0" width="4" height="1"/><rect x="1" y="1" width="1" height="1"/><rect x="3" y="1" width="2" height="1"/><rect x="6" y="1" width="1" height="1"/><rect x="0" y="2" width="1" height="1"/><rect x="3" y="2" width="2" height="1"/><rect x="7" y="2" width="1" height="1"/><rect x="0" y="3" width="8" height="1"/><rect x="0" y="4" width="1" height="1"/><rect x="3" y="4" width="2" height="1"/><rect x="7" y="4" width="1" height="1"/><rect x="1" y="5" width="1" height="1"/><rect x="3" y="5" width="2" height="1"/><rect x="6" y="5" width="1" height="1"/><rect x="2" y="6" width="4" height="1"/>' ),
		'check'  => array( '0 -0.5 8 8', '<rect x="6" y="1" width="2" height="1"/><rect x="5" y="2" width="2" height="1"/><rect x="0" y="3" width="2" height="1"/><rect x="4" y="3" width="2" height="1"/><rect x="1" y="4" width="4" height="1"/><rect x="2" y="5" width="2" height="1"/>' ),
		'heart'  => array( '0 0 8 8', '<rect x="1" y="1" width="2" height="1"/><rect x="5" y="1" width="2" height="1"/><rect x="0" y="2" width="8" height="2"/><rect x="1" y="4" width="6" height="1"/><rect x="2" y="5" width="4" height="1"/><rect x="3" y="6" width="2" height="1"/>' ),
	);

	/**
	 * A pixel-art opening quotation mark (two glyphs on a 10×7 grid). Each
	 * pixel row is its own rect so the mark can assemble row by row.
	 */
	const QUOTE_MARK = array(
		array( 2, 0, 2, 1 ),
		array( 1, 1, 2, 1 ),
		array( 0, 2, 2, 1 ),
		array( 0, 3, 4, 4 ),
		array( 8, 0, 2, 1 ),
		array( 7, 1, 2, 1 ),
		array( 6, 2, 2, 1 ),
		array( 6, 3, 4, 4 ),
	);

	public function get_name(): string {
		return 'avix-founder';
	}

	public function get_title(): string {
		return esc_html__( 'Founder', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-testimonial';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'founder', 'ceo', 'quote', 'video', 'introduction', 'about', 'person', 'avatar', 'avix' );
	}

	public function get_style_depends(): array {
		return array( 'avix-founder' );
	}

	public function get_script_depends(): array {
		return array( 'avix-founder' );
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/* ------------------------------------------------------------------ */
	/* Controls                                                            */
	/* ------------------------------------------------------------------ */

	protected function register_controls(): void {
		$this->controls_layout();
		$this->controls_media();
		$this->controls_quote();
		$this->controls_badges();
		$this->controls_buddy();
		$this->controls_style();
	}

	private function controls_layout() {
		$this->start_controls_section( 'section_layout', array( 'label' => esc_html__( 'Layout', 'avix-widgets' ) ) );

		$this->add_control(
			'theme',
			array(
				'label'       => esc_html__( 'Card theme', 'avix-widgets' ),
				'description' => esc_html__( 'Dark gives the About page a dark beat between light sections.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'dark',
				'options'     => array(
					'dark'  => esc_html__( 'Dark', 'avix-widgets' ),
					'light' => esc_html__( 'Light (warm paper)', 'avix-widgets' ),
				),
			)
		);

		$this->add_control(
			'photo_side',
			array(
				'label'   => esc_html__( 'Photo side', 'avix-widgets' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'left',
				'toggle'  => false,
				'options' => array(
					'left'  => array(
						'title' => esc_html__( 'Left', 'avix-widgets' ),
						'icon'  => 'eicon-h-align-left',
					),
					'right' => array(
						'title' => esc_html__( 'Right', 'avix-widgets' ),
						'icon'  => 'eicon-h-align-right',
					),
				),
			)
		);

		$this->add_control(
			'layout_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'On narrow screens the photo always sits on top of the quote.', 'avix-widgets' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->end_controls_section();
	}

	private function controls_media() {
		$this->start_controls_section( 'section_media', array( 'label' => esc_html__( 'Photo & Video', 'avix-widgets' ) ) );

		$this->add_control(
			'photo',
			array(
				'label'       => esc_html__( 'Photo', 'avix-widgets' ),
				'description' => esc_html__( 'A portrait (4:5) works best. It is cropped to fill the card, so use Focus to keep the face in view. Without a photo, the video button moves under the quote and the pixel character is hidden.', 'avix-widgets' ),
				'type'        => Controls_Manager::MEDIA,
				'dynamic'     => array( 'active' => true ),
				'default'     => array( 'url' => self::PHOTO ),
			)
		);

		$this->add_control(
			'photo_alt',
			array(
				'label'       => esc_html__( 'Photo alt text', 'avix-widgets' ),
				'description' => esc_html__( 'Leave empty to use the alt text from the Media Library, or the name.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_responsive_control(
			'focus_x',
			array(
				'label'      => esc_html__( 'Focus (horizontal)', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array( '%' => array( 'min' => 0, 'max' => 100 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-fn' => '--fn-focus-x: {{SIZE}}%;' ),
			)
		);

		$this->add_responsive_control(
			'focus_y',
			array(
				'label'      => esc_html__( 'Focus (vertical)', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array( '%' => array( 'min' => 0, 'max' => 100 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-fn' => '--fn-focus-y: {{SIZE}}%;' ),
			)
		);

		$this->add_control(
			'video_heading',
			array(
				'label'     => esc_html__( 'Introduction video', 'avix-widgets' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'video',
			array(
				'label'       => esc_html__( 'Video link', 'avix-widgets' ),
				'description' => esc_html__( 'A YouTube or Vimeo link, or an MP4/WebM file. Nothing loads until the visitor presses play (YouTube uses its privacy-enhanced player). Leave empty to hide the button.', 'avix-widgets' ),
				'type'        => Controls_Manager::URL,
				'dynamic'     => array( 'active' => true ),
				'options'     => false,
				'default'     => array( 'url' => self::VIDEO ),
			)
		);

		$this->add_control(
			'play_label',
			array(
				'label'   => esc_html__( 'Button text', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Watch introduction', 'avix-widgets' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'play_note',
			array(
				'label'       => esc_html__( 'Small note after the text', 'avix-widgets' ),
				'description' => esc_html__( 'Optional, e.g. the video length "2 min".', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'dialog_label',
			array(
				'label'       => esc_html__( 'Pop-up name (screen readers)', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Introduction video', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
				'label_block' => true,
			)
		);

		$this->add_control(
			'close_label',
			array(
				'label'   => esc_html__( 'Close button (screen readers)', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Close video', 'avix-widgets' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_quote() {
		$this->start_controls_section( 'section_quote', array( 'label' => esc_html__( 'Quote & Name', 'avix-widgets' ) ) );

		$this->add_control(
			'eyebrow',
			array(
				'label'       => esc_html__( 'Eyebrow', 'avix-widgets' ),
				'description' => esc_html__( 'Also names the section for screen readers.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'From the founder', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'eyebrow_tag',
			array(
				'label'   => esc_html__( 'Eyebrow HTML tag', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h2',
				'options' => array(
					'h2'  => 'H2',
					'h3'  => 'H3',
					'p'   => 'p',
					'div' => 'div',
				),
			)
		);

		$this->add_control(
			'show_mark',
			array(
				'label'       => esc_html__( 'Pixel quotation mark', 'avix-widgets' ),
				'description' => esc_html__( 'An orange “ drawn in pixels that assembles as the card comes into view.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'quote',
			array(
				'label'       => esc_html__( 'Quote', 'avix-widgets' ),
				'description' => esc_html__( 'Wrap words in [brackets] to highlight them in orange. Press Enter for a new paragraph.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 5,
				'default'     => 'A useful website should make your offer clearer, help customers take the next step and [fit the way your team works.] We start with your goals, then plan the design, development and integrations needed to get there.',
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'name',
			array(
				'label'     => esc_html__( 'Name', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => 'MD Akib Zawayed',
				'dynamic'   => array( 'active' => true ),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'name_tag',
			array(
				'label'   => esc_html__( 'Name HTML tag', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'p',
				'options' => array(
					'h3'  => 'H3',
					'h4'  => 'H4',
					'p'   => 'p',
					'div' => 'div',
				),
			)
		);

		$this->add_control(
			'role',
			array(
				'label'   => esc_html__( 'Role', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => 'Founder & CEO, AvixDigital',
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'profile',
			array(
				'label'       => esc_html__( 'Profile link', 'avix-widgets' ),
				'description' => esc_html__( 'Optional (LinkedIn, About page…). Makes the name a link and is added to the structured data.', 'avix-widgets' ),
				'type'        => Controls_Manager::URL,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'show_badge',
			array(
				'label'     => esc_html__( 'Verified badge', 'avix-widgets' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'badge_image',
			array(
				'label'     => esc_html__( 'Badge image', 'avix-widgets' ),
				'type'      => Controls_Manager::MEDIA,
				'dynamic'   => array( 'active' => true ),
				'default'   => array( 'url' => self::MARK ),
				'condition' => array( 'show_badge' => 'yes' ),
			)
		);

		$this->add_control(
			'badge_text',
			array(
				'label'     => esc_html__( 'Badge text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Avix Verified Excellence', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_badge' => 'yes' ),
			)
		);

		$this->add_control(
			'schema',
			array(
				'label'       => esc_html__( 'Person structured data', 'avix-widgets' ),
				'description' => esc_html__( 'Adds JSON-LD for the founder (name, role, photo, profile, employer). Search engines see one person across the site when the ID below matches the home page.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'person_id',
			array(
				'label'       => esc_html__( 'Person ID', 'avix-widgets' ),
				'description' => esc_html__( 'Used as #person-{ID}. Keep "akib-zawayed" so it matches the Compare & CEO Quote and Process Timeline widgets.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'akib-zawayed',
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'schema' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_badges() {
		$this->start_controls_section( 'section_badges', array( 'label' => esc_html__( 'Highlights', 'avix-widgets' ) ) );

		$this->add_control(
			'show_badges',
			array(
				'label'   => esc_html__( 'Show highlights', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$items = new Repeater();
		$items->add_control(
			'icon',
			array(
				'label'   => esc_html__( 'Pixel icon', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'check',
				'options' => array(
					'star'   => esc_html__( 'Star', 'avix-widgets' ),
					'rocket' => esc_html__( 'Rocket', 'avix-widgets' ),
					'globe'  => esc_html__( 'Globe', 'avix-widgets' ),
					'check'  => esc_html__( 'Check', 'avix-widgets' ),
					'heart'  => esc_html__( 'Heart', 'avix-widgets' ),
					'none'   => esc_html__( 'None', 'avix-widgets' ),
				),
			)
		);
		$items->add_control(
			'text',
			array(
				'label'       => esc_html__( 'Text', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Scope agreed up front', 'avix-widgets' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'badges',
			array(
				'label'       => esc_html__( 'Items', 'avix-widgets' ),
				'description' => esc_html__( 'Short proof points. Two to four read best.', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $items->get_controls(),
				'title_field' => '{{{ text }}}',
				'default'     => array(
					array(
						'icon' => 'star',
						'text' => '5.0 Fiverr Pro rating',
					),
					array(
						'icon' => 'rocket',
						'text' => '250+ websites built',
					),
					array(
						'icon' => 'globe',
						'text' => 'Global reach',
					),
				),
				'condition'   => array( 'show_badges' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_buddy() {
		$this->start_controls_section( 'section_buddy', array( 'label' => esc_html__( 'Pixel Character', 'avix-widgets' ) ) );

		$this->add_control(
			'show_pal',
			array(
				'label'       => esc_html__( 'Show pixel character', 'avix-widgets' ),
				'description' => esc_html__( 'Peeks over the top of the photo. Hover or focus the video button and it pops up, looks at it and says hi.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_responsive_control(
			'pal_x',
			array(
				'label'      => esc_html__( 'Position along the photo', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array( '%' => array( 'min' => 10, 'max' => 90 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-fn' => '--fn-pal-x: {{SIZE}}%;' ),
				'condition'  => array( 'show_pal' => 'yes' ),
			)
		);

		$this->add_control(
			'pal_every',
			array(
				'label'       => esc_html__( 'Pop up and wave every (seconds)', 'avix-widgets' ),
				'description' => esc_html__( 'Roughly, while the card is on screen, and at most twice per visit so it never distracts from the quote. 0 = only when the visitor reaches for play.', 'avix-widgets' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 60,
				'default'     => 12,
				'condition'   => array( 'show_pal' => 'yes' ),
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

		$colors = array(
			'fn_bg'      => array( esc_html__( 'Section background', 'avix-widgets' ), '--fn-bg' ),
			'fn_card'    => array( esc_html__( 'Card background', 'avix-widgets' ), '--fn-card' ),
			'fn_ink'     => array( esc_html__( 'Quote and name', 'avix-widgets' ), '--fn-ink' ),
			'fn_muted'   => array( esc_html__( 'Muted text', 'avix-widgets' ), '--fn-muted' ),
			'fn_accent'  => array( esc_html__( 'Accent (button, character)', 'avix-widgets' ), '--fn-accent' ),
			'fn_soft'    => array( esc_html__( 'Highlighted words and icons', 'avix-widgets' ), '--fn-accent-text' ),
			'fn_line'    => array( esc_html__( 'Lines and chip borders', 'avix-widgets' ), '--fn-line' ),
			'fn_chip'    => array( esc_html__( 'Chip background', 'avix-widgets' ), '--fn-chip' ),
			'fn_play_bg' => array( esc_html__( 'Video button background', 'avix-widgets' ), '--fn-play-bg' ),
			'fn_play'    => array( esc_html__( 'Video button text', 'avix-widgets' ), '--fn-play-ink' ),
			'fn_note'    => array( esc_html__( 'Video button note', 'avix-widgets' ), '--fn-play-note' ),
		);
		foreach ( $colors as $key => $color ) {
			$this->add_control(
				$key,
				array(
					'label'     => $color[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-fn' => $color[1] . ': {{VALUE}};' ),
				)
			);
		}

		$this->add_control(
			'show_glow',
			array(
				'label'     => esc_html__( 'Orange glow and pixel grid', 'avix-widgets' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'padding',
			array(
				'label'              => esc_html__( 'Padding', 'avix-widgets' ),
				'description'        => esc_html__( 'When the character pops up it rises about twice its size above the card, into the section above if the top padding is smaller.', 'avix-widgets' ),
				'type'               => Controls_Manager::DIMENSIONS,
				'size_units'         => array( 'px', 'vh' ),
				'allowed_dimensions' => 'vertical',
				'selectors'          => array( '{{WRAPPER}} .avix-fn' => 'padding-top: {{TOP}}{{UNIT}}; padding-bottom: {{BOTTOM}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'max_width',
			array(
				'label'      => esc_html__( 'Card width', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 720, 'max' => 1440 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-fn' => '--fn-max: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'radius',
			array(
				'label'      => esc_html__( 'Card corner radius', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 48 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-fn' => '--fn-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'pal_size',
			array(
				'label'       => esc_html__( 'Character size', 'avix-widgets' ),
				'description' => esc_html__( 'Moves in steps of 10px so the pixel art stays sharp.', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array(
					'px' => array(
						'min'  => 20,
						'max'  => 70,
						'step' => 10,
					),
				),
				'selectors'   => array( '{{WRAPPER}} .avix-fn' => '--fn-pal-w: {{SIZE}}{{UNIT}};' ),
				'condition'   => array( 'show_pal' => 'yes' ),
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
			'quote_typography'   => array( esc_html__( 'Quote', 'avix-widgets' ), '.avix-fn__words' ),
			'name_typography'    => array( esc_html__( 'Name', 'avix-widgets' ), '.avix-fn__name' ),
			'role_typography'    => array( esc_html__( 'Role', 'avix-widgets' ), '.avix-fn__role' ),
			'eyebrow_typography' => array( esc_html__( 'Eyebrow', 'avix-widgets' ), '.avix-fn__eyebrow' ),
			'chip_typography'    => array( esc_html__( 'Highlights and badge', 'avix-widgets' ), '.avix-fn__chip' ),
		);
		foreach ( $type as $name => $group ) {
			$this->add_group_control(
				Group_Control_Typography::get_type(),
				array(
					'name'     => $name,
					'label'    => $group[0],
					'selector' => '{{WRAPPER}} .avix-fn ' . $group[1],
				)
			);
		}

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ */
	/* Render                                                              */
	/* ------------------------------------------------------------------ */

	protected function render(): void {
		$s          = $this->get_settings_for_display();
		$quote      = trim( (string) ( $s['quote'] ?? '' ) );
		$name       = trim( (string) ( $s['name'] ?? '' ) );
		$eyebrow    = trim( (string) ( $s['eyebrow'] ?? '' ) );
		$photo      = (array) ( $s['photo'] ?? array() );
		$photo_html = $this->photo_html( $photo, $s, $name );
		$has_photo  = '' !== $photo_html;
		$edit_mode  = \Elementor\Plugin::$instance->editor->is_edit_mode();
		$badges     = array();
		if ( 'yes' === ( $s['show_badges'] ?? '' ) ) {
			$badges = array_values(
				array_filter(
					(array) ( $s['badges'] ?? array() ),
					static function ( $row ) {
						return '' !== trim( (string) ( $row['text'] ?? '' ) );
					}
				)
			);
		}
		$has_author = '' !== $name || '' !== trim( (string) ( $s['role'] ?? '' ) ) || ( 'yes' === ( $s['show_badge'] ?? '' ) && '' !== trim( (string) ( $s['badge_text'] ?? '' ) ) );
		$has_body   = '' !== $eyebrow || '' !== $quote || $has_author || $badges;

		if ( ! $has_body && ! $has_photo ) {
			if ( $edit_mode ) {
				echo '<div class="elementor-alert elementor-alert-info">' . esc_html__( 'Founder: add a quote, a name or a photo.', 'avix-widgets' ) . '</div>';
			}
			return;
		}
		if ( ! $has_body && $edit_mode ) {
			echo '<div class="elementor-alert elementor-alert-info">' . esc_html__( 'Founder: only the photo is showing. Add a quote or a name to fill the card.', 'avix-widgets' ) . '</div>';
		}

		$id         = $this->get_id();
		$eyebrow_id = 'avix-fn-title-' . $id;
		$video      = $this->video( (string) ( $s['video']['url'] ?? '' ) );
		$pal        = 'yes' === ( $s['show_pal'] ?? '' ) && $has_photo;

		$classes = array( 'avix-fn', 'avix-fn--' . ( 'light' === ( $s['theme'] ?? '' ) ? 'light' : 'dark' ) );
		if ( 'right' === ( $s['photo_side'] ?? '' ) ) {
			$classes[] = 'avix-fn--photo-right';
		}
		if ( ! $has_photo ) {
			$classes[] = 'avix-fn--no-photo';
		}
		if ( ! $has_body ) {
			$classes[] = 'avix-fn--no-body';
		}
		if ( 'yes' !== ( $s['show_glow'] ?? '' ) ) {
			$classes[] = 'avix-fn--plain';
		}

		$short = '' !== $quote && false === strpos( $quote, "\n" ) && $this->length( preg_replace( '/[\[\]]/u', '', $quote ) ) < 90;
		$solo  = '' !== $quote && ! $has_author && ! $badges;

		$label  = trim( (string) ( $s['dialog_label'] ?? '' ) );
		$close  = trim( (string) ( $s['close_label'] ?? '' ) );
		$config = array(
			'video' => $video && 'link' !== $video['type'] ? $video : null,
			'every' => $pal ? max( 0, min( 60, (int) ( $s['pal_every'] ?? 12 ) ) ) : 0,
			// Translated defaults, so the pop-up is never unnamed when the fields are cleared.
			'label' => '' !== $label ? $label : __( 'Introduction video', 'avix-widgets' ),
			'close' => '' !== $close ? $close : __( 'Close video', 'avix-widgets' ),
		);

		$this->add_render_attribute(
			'root',
			array(
				'class'        => $classes,
				'data-avix-fn' => wp_json_encode( $config ),
			)
		);
		if ( '' !== $eyebrow ) {
			$this->add_render_attribute( 'root', 'aria-labelledby', $eyebrow_id );
		} else {
			$this->add_render_attribute( 'root', 'aria-label', '' !== $name ? $name : esc_html__( 'From the founder', 'avix-widgets' ) );
		}

		// Photo on the right: the text comes first in the DOM too, so the heading and
		// the profile link are read and tabbed to before the video button.
		$text_first = $has_photo && $has_body && 'right' === ( $s['photo_side'] ?? '' );
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="avix-fn__frame">
				<div class="avix-fn__card">
					<?php
					if ( ! $text_first ) {
						$this->render_media( $s, $photo_html, $video, $pal );
					}
					?>

					<?php if ( $has_body ) : ?>
					<div class="avix-fn__body">
						<?php if ( '' !== $eyebrow ) : ?>
							<?php $tag = Utils::validate_html_tag( $s['eyebrow_tag'] ?? 'h2' ); ?>
							<<?php echo esc_attr( $tag ); ?> class="avix-fn__eyebrow" id="<?php echo esc_attr( $eyebrow_id ); ?>" data-fn-reveal style="--fn-i: 0;"><?php echo esc_html( $eyebrow ); ?></<?php echo esc_attr( $tag ); ?>>
						<?php endif; ?>

						<?php // Plain divs, not figure/figcaption: the live theme turns figcaption into a small scroll box. ?>
						<div class="avix-fn__quote<?php echo $solo ? ' avix-fn__quote--solo' : ''; ?><?php echo $short ? ' avix-fn__quote--short' : ''; ?>">
							<?php if ( '' !== $quote ) : ?>
								<div class="avix-fn__say">
									<?php if ( 'yes' === ( $s['show_mark'] ?? '' ) ) : ?>
										<?php echo $this->mark_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup. ?>
									<?php endif; ?>
									<blockquote class="avix-fn__words<?php echo $short ? ' avix-fn__words--short' : ''; ?> is-style-plain has-background" data-fn-reveal style="--fn-i: 1;">
										<?php echo $this->quote_html( $quote ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in quote_html(). ?>
									</blockquote>
								</div>
							<?php endif; ?>
							<?php $this->render_author( $s, $name ); ?>
						</div>

						<?php if ( $badges ) : ?>
							<ul class="avix-fn__badges" data-fn-reveal style="--fn-i: 3;">
								<?php foreach ( $badges as $badge ) : ?>
									<?php $icon = (string) ( $badge['icon'] ?? '' ); ?>
									<li class="avix-fn__chip<?php echo isset( self::ICONS[ $icon ] ) ? '' : ' is-plain'; ?> elementor-repeater-item-<?php echo esc_attr( sanitize_html_class( (string) ( $badge['_id'] ?? '' ) ) ); ?>">
										<?php if ( isset( self::ICONS[ $icon ] ) ) : ?>
											<span class="avix-fn__icon" aria-hidden="true"><svg viewBox="<?php echo esc_attr( self::ICONS[ $icon ][0] ); ?>" focusable="false"><?php echo self::ICONS[ $icon ][1]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup. ?></svg></span>
										<?php endif; ?>
										<span><?php echo esc_html( $badge['text'] ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>

						<?php
						// No photo to sit on: the video button closes the text column instead.
						if ( ! $has_photo && $video ) {
							echo '<div class="avix-fn__cta" data-fn-reveal style="--fn-i: 4;">';
							$this->render_play( $s, $video, true );
							echo '</div>';
						}
						?>
					</div>
					<?php endif; ?>

					<?php
					if ( $text_first ) {
						$this->render_media( $s, $photo_html, $video, $pal );
					}
					?>
				</div>
			</div>
		</section>
		<?php
		if ( 'yes' === ( $s['schema'] ?? '' ) && '' !== $name ) {
			$this->print_schema( $s, $name );
		}
	}

	/**
	 * The photo with the play pill on it and the pixel character peeking over its top edge.
	 */
	private function render_media( array $s, $photo_html, $video, $pal ) {
		if ( '' === $photo_html ) {
			return;
		}
		?>
		<div class="avix-fn__media">
			<div class="avix-fn__shot<?php echo $video ? '' : ' avix-fn__shot--no-play'; ?>">
				<?php if ( $pal ) : ?>
					<span class="avix-fn__perch" data-fn-perch>
						<?php echo $this->pal_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Pixel_Pal::render(), static additions. ?>
					</span>
				<?php endif; ?>
				<div class="avix-fn__photo">
					<?php echo $photo_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in photo_html(). ?>
				</div>
				<?php
				if ( $video ) {
					$this->render_play( $s, $video );
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * The shared pixel character plus two eyes for its peeking pose. The eyes are
	 * extra head rects (class avix-pal__head), so they follow every glance, hop
	 * and turn of the head exactly; founder.css only shows them while it peeks.
	 */
	private function pal_html() {
		$html = \AvixWidgets\Pixel_Pal::render(
			array(
				'class' => 'avix-fn__pal',
				'hi'    => true,
				'attrs' => array( 'data-fn-pal' => '' ),
			)
		);
		$eyes = '<rect class="avix-pal__head avix-fn__eye" x="3.8" y="1" width="0.8" height="1"/>'
			. '<rect class="avix-pal__head avix-fn__eye" x="5.4" y="1" width="0.8" height="1"/>';
		$out  = preg_replace( '~(<rect class="avix-pal__head"[^>]*/>)~', '$1' . $eyes, $html, 1 );
		return is_string( $out ) ? $out : $html;
	}

	/**
	 * Character count that survives a missing mbstring extension.
	 */
	private function length( $text ) {
		return function_exists( 'mb_strlen' ) ? mb_strlen( $text, 'UTF-8' ) : strlen( $text );
	}

	private function render_play( array $s, array $video, $inline = false ) {
		$label = trim( (string) ( $s['play_label'] ?? '' ) );
		$note  = trim( (string) ( $s['play_note'] ?? '' ) );
		if ( '' === $label ) {
			$label = esc_html__( 'Watch introduction', 'avix-widgets' );
		}
		$this->add_render_attribute(
			'play',
			array(
				'class'        => 'avix-fn__play' . ( $inline ? ' avix-fn__play--inline' : '' ),
				'href'         => esc_url( $video['href'] ),
				'data-fn-play' => '',
			)
		);
		if ( 'link' === $video['type'] ) {
			$this->add_render_attribute(
				'play',
				array(
					'target' => '_blank',
					'rel'    => 'noopener',
				)
			);
		}
		?>
		<a <?php $this->print_render_attribute_string( 'play' ); ?>>
			<?php if ( 'link' === $video['type'] ) : ?>
				<span class="avix-fn__play-icon is-out" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M7 17 17 7M7 7h10v10"/></svg></span>
			<?php else : ?>
				<span class="avix-fn__play-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M8 5.5v13l11-6.5z"/></svg></span>
			<?php endif; ?>
			<?php
			// The note sits inside the text and is glued to the last word, so a wrapped
			// label never starts its second line with the "·" or leaves the note alone.
			$head = $label;
			$tail = '';
			if ( '' !== $note ) {
				$cut  = strrpos( $label, ' ' );
				$head = false === $cut ? '' : substr( $label, 0, $cut + 1 );
				$tail = false === $cut ? $label : substr( $label, $cut + 1 );
			}
			?>
			<span class="avix-fn__play-text"><?php echo esc_html( $head ); ?><?php if ( '' !== $note ) : ?><span class="avix-fn__play-tail"><?php echo esc_html( $tail ); ?> <span class="avix-fn__play-note"><?php echo esc_html( $note ); ?></span></span><?php endif; ?></span>
			<?php if ( 'link' === $video['type'] ) : ?>
				<span class="avix-fn__sr"><?php esc_html_e( '(opens in a new tab)', 'avix-widgets' ); ?></span>
			<?php endif; ?>
		</a>
		<?php
	}

	private function render_author( array $s, $name ) {
		$role    = trim( (string) ( $s['role'] ?? '' ) );
		$profile = (array) ( $s['profile'] ?? array() );
		$badge   = 'yes' === ( $s['show_badge'] ?? '' ) ? trim( (string) ( $s['badge_text'] ?? '' ) ) : '';
		if ( '' === $name && '' === $role && '' === $badge ) {
			return;
		}
		$linked = '' !== $name && '' !== esc_url( $profile['url'] ?? '' );
		if ( $linked ) {
			$this->add_link_attributes( 'profile', $profile );
			$this->add_render_attribute( 'profile', 'class', 'avix-fn__name-link' );
		}
		$tag = Utils::validate_html_tag( $s['name_tag'] ?? 'p' );
		?>
		<div class="avix-fn__author" data-fn-reveal style="--fn-i: 2;">
			<?php if ( '' !== $name || '' !== $role ) : ?>
				<div class="avix-fn__who">
					<?php if ( '' !== $name ) : ?>
						<<?php echo esc_attr( $tag ); ?> class="avix-fn__name">
							<?php if ( $linked ) : ?>
								<a <?php $this->print_render_attribute_string( 'profile' ); ?>><?php echo esc_html( $name ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $name ); ?>
							<?php endif; ?>
						</<?php echo esc_attr( $tag ); ?>>
					<?php endif; ?>
					<?php if ( '' !== $role ) : ?>
						<p class="avix-fn__role"><?php echo esc_html( $role ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<?php if ( '' !== $badge ) : ?>
				<?php $seal = $this->badge_image( (array) ( $s['badge_image'] ?? array() ) ); ?>
				<p class="avix-fn__chip avix-fn__verified<?php echo '' === $seal ? ' is-plain' : ''; ?>">
					<?php echo $seal; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in badge_image(). ?>
					<span><?php echo esc_html( $badge ); ?></span>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/* ------------------------------------------------------------------ */
	/* Helpers                                                             */
	/* ------------------------------------------------------------------ */

	/**
	 * Escaped paragraphs with [words] in the accent span.
	 */
	private function quote_html( $text ) {
		$out = array();
		foreach ( preg_split( '/\R+/', $text ) as $para ) {
			$para = trim( $para );
			if ( '' !== $para ) {
				$out[] = '<p>' . preg_replace( '/\[([^\[\]]+)\]/u', '<span class="avix-fn__accent">$1</span>', esc_html( $para ) ) . '</p>';
			}
		}
		return implode( '', $out );
	}

	private function mark_svg() {
		$rects = '';
		foreach ( self::QUOTE_MARK as $i => $r ) {
			$rects .= sprintf( '<rect x="%1$d" y="%2$d" width="%3$d" height="%4$d" style="--fn-p: %5$d;"/>', $r[0], $r[1], $r[2], $r[3], $i % 4 );
		}
		return '<svg class="avix-fn__mark" viewBox="0 0 10 7" aria-hidden="true" focusable="false">' . $rects . '</svg>';
	}

	private function photo_html( array $photo, array $s, $name ) {
		$url = (string) ( $photo['url'] ?? '' );
		$id  = $this->media_id( $photo );
		$alt = trim( (string) ( $s['photo_alt'] ?? '' ) );
		if ( '' === $alt && $id ) {
			$alt = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
		}
		if ( '' === $alt ) {
			$alt = $name;
		}
		$gif = (bool) preg_match( '/\.gif(\?.*)?$/i', $url );
		if ( $id && ! $gif ) {
			$html = wp_get_attachment_image(
				$id,
				'large',
				false,
				array(
					'class'    => 'avix-fn__img',
					'alt'      => $alt,
					'loading'  => 'lazy',
					'decoding' => 'async',
					'sizes'    => '(max-width: 820px) 94vw, 520px',
				)
			);
			if ( $html ) {
				return $html;
			}
		}
		// A URL that does not survive esc_url() (a typo, javascript:) would print a broken image.
		if ( '' === esc_url( $url ) ) {
			return '';
		}
		return sprintf( '<img class="avix-fn__img" src="%s" alt="%s" loading="lazy" decoding="async">', esc_url( $url ), esc_attr( $alt ) );
	}

	private function badge_image( array $media ) {
		$url = (string) ( $media['url'] ?? '' );
		$id  = $this->media_id( $media );
		if ( $id ) {
			$html = wp_get_attachment_image(
				$id,
				'thumbnail',
				false,
				array(
					'class'    => 'avix-fn__seal',
					'alt'      => '',
					'loading'  => 'lazy',
					'decoding' => 'async',
				)
			);
			if ( $html ) {
				return $html;
			}
		}
		if ( '' === esc_url( $url ) ) {
			return '';
		}
		return sprintf( '<img class="avix-fn__seal" src="%s" alt="" loading="lazy" decoding="async">', esc_url( $url ) );
	}

	/**
	 * Works out how to play a video link. Returns null when empty; type
	 * 'iframe' (YouTube via youtube-nocookie, Vimeo), 'file' (MP4/WebM) or
	 * 'link' (anything else: opens in a new tab). Nothing is requested from
	 * the provider until the visitor presses play.
	 */
	private function video( $url ) {
		$url = trim( $url );
		if ( '' === $url || '' === esc_url( $url ) ) {
			return null;
		}
		$query = array();
		wp_parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
		if ( preg_match( '~(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:[^#]*&)?v=|embed/|shorts/|live/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})~i', $url, $m ) ) {
			// Keep a start time from ?t=42, ?t=1m2s or ?start=42.
			$start = $this->seconds( (string) ( $query['t'] ?? ( $query['start'] ?? '' ) ) );
			return array(
				'type' => 'iframe',
				'href' => 'https://www.youtube.com/watch?v=' . $m[1] . ( $start ? '&t=' . $start . 's' : '' ),
				'src'  => 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?autoplay=1&rel=0&playsinline=1' . ( $start ? '&start=' . $start : '' ),
			);
		}
		if ( preg_match( '~vimeo\.com/(?:video/|channels/[^/]+/|groups/[^/]+/videos/)?(\d{5,})(?:/([a-f0-9]{6,}))?~i', $url, $m ) ) {
			// Unlisted videos only play with their privacy hash (vimeo.com/ID/HASH or ?h=HASH).
			$hash = ! empty( $m[2] ) ? $m[2] : (string) ( $query['h'] ?? '' );
			$hash = preg_match( '/^[a-f0-9]{6,}$/i', $hash ) ? $hash : '';
			return array(
				'type' => 'iframe',
				'href' => 'https://vimeo.com/' . $m[1] . ( '' !== $hash ? '/' . $hash : '' ),
				'src'  => 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1&dnt=1&title=0&byline=0' . ( '' !== $hash ? '&h=' . $hash : '' ),
			);
		}
		if ( preg_match( '~\.(mp4|webm|ogv|m4v|mov)(?:[?#].*)?$~i', $url ) ) {
			return array(
				'type' => 'file',
				'href' => esc_url_raw( $url ),
				'src'  => esc_url_raw( $url ),
			);
		}
		return array(
			'type' => 'link',
			'href' => esc_url_raw( $url ),
			'src'  => '',
		);
	}

	/**
	 * A start time in seconds from "42", "42s" or "1h2m3s"; 0 when unreadable.
	 */
	private function seconds( $value ) {
		$value = strtolower( trim( $value ) );
		if ( preg_match( '/^\d+$/', $value ) ) {
			return (int) $value;
		}
		if ( '' === $value || ! preg_match( '/^(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?$/', $value, $m ) ) {
			return 0;
		}
		return (int) ( $m[1] ?? 0 ) * 3600 + (int) ( $m[2] ?? 0 ) * 60 + (int) ( $m[3] ?? 0 );
	}

	/**
	 * Plain text for JSON-LD: no tags, entities decoded (wp_json_encode escapes).
	 */
	private function plain( $text ) {
		return trim( html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	}

	/**
	 * schema.org Person for the founder, sharing its @id with the home page
	 * (Compare & CEO Quote, Process Timeline), so it stays one entity.
	 */
	private function print_schema( array $s, $name ) {
		static $printed = array();
		$name = $this->plain( $name );
		$slug = sanitize_title( (string) ( $s['person_id'] ?? '' ) );
		if ( '' === $slug ) {
			$slug = sanitize_title( $name );
		}
		// One entity per page: a second Founder card for the same person adds nothing.
		if ( '' === $slug || isset( $printed[ $slug ] ) ) {
			return;
		}
		$printed[ $slug ] = true;
		$person = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Person',
			'@id'      => home_url( '/#person-' . $slug ),
			'name'     => $name,
			'worksFor' => array(
				'@type' => 'Organization',
				'name'  => $this->plain( get_bloginfo( 'name' ) ),
				'url'   => home_url( '/' ),
			),
		);
		$role = $this->plain( (string) ( $s['role'] ?? '' ) );
		if ( '' !== $role ) {
			$person['jobTitle'] = $role;
		}
		$photo = (array) ( $s['photo'] ?? array() );
		$id    = $this->media_id( $photo );
		$image = $id ? wp_get_attachment_image_url( $id, 'large' ) : (string) ( $photo['url'] ?? '' );
		if ( $image ) {
			$person['image'] = esc_url_raw( $image );
		}
		$link = trim( (string) ( $s['profile']['url'] ?? '' ) );
		if ( '' !== $link && '/' === $link[0] && ( ! isset( $link[1] ) || '/' !== $link[1] ) ) {
			$link = home_url( $link );
		}
		$link = esc_url_raw( $link, array( 'http', 'https' ) );
		if ( '' !== $link ) {
			$person[ wp_parse_url( $link, PHP_URL_HOST ) === wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ? 'url' : 'sameAs' ] = $link;
		}
		$json = wp_json_encode( $person, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP );
		if ( $json ) {
			echo '<script type="application/ld+json">' . $json . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON with < > & hex-escaped.
		}
	}
}
