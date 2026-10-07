<?php
/**
 * Ticker: a full-width band of keywords gliding by, orange with white type by
 * default. The Avix pixel character rides on its top edge, walking against
 * the words so it stays in place, hops over a separator now and then and
 * stops to say hi when the band is hovered. A second band can cross it.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Widgets;

use AvixWidgets\Pixel_Pal;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

defined( 'ABSPATH' ) || exit;

class Ticker extends Widget_Base {

	/**
	 * Sets of words the server prints per half of the loop. The script
	 * re-counts them from the real widths; this keeps the loop seamless on
	 * most screens even before (or without) JavaScript.
	 */
	const SETS_PER_HALF = 2;

	public function get_name(): string {
		return 'avix-ticker';
	}

	public function get_title(): string {
		return esc_html__( 'Ticker', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-animation-text';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'ticker', 'marquee', 'keywords', 'band', 'scrolling text', 'banner', 'avix' );
	}

	public function get_style_depends(): array {
		return array( 'avix-ticker' );
	}

	public function get_script_depends(): array {
		return array( 'avix-ticker' );
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/* ------------------------------------------------------------------ */
	/* Controls                                                            */
	/* ------------------------------------------------------------------ */

	protected function register_controls(): void {
		$this->controls_words();
		$this->controls_motion();
		$this->controls_pal();
		$this->controls_style();
	}

	private function controls_words() {
		$this->start_controls_section( 'section_words', array( 'label' => esc_html__( 'Words', 'avix-widgets' ) ) );

		$this->add_control(
			'words',
			array(
				'label'       => esc_html__( 'Words', 'avix-widgets' ),
				'description' => esc_html__( 'One word or short phrase per line. They glide by in a loop, in capitals.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 8,
				'default'     => implode( "\n", $this->default_words() ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'separator',
			array(
				'label'   => esc_html__( 'Between the words', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'pixel',
				'options' => array(
					'pixel'     => esc_html__( 'Pixel spark', 'avix-widgets' ),
					'dot'       => esc_html__( 'Dot', 'avix-widgets' ),
					'arrow'     => esc_html__( 'Arrow', 'avix-widgets' ),
					'alternate' => esc_html__( 'Dot and arrow, alternating', 'avix-widgets' ),
				),
			)
		);

		$this->add_control(
			'outline_alt',
			array(
				'label'       => esc_html__( 'Outline every other word', 'avix-widgets' ),
				'description' => esc_html__( 'Every second word is drawn as an outline for a livelier rhythm.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
			)
		);

		$this->add_control(
			'uppercase',
			array(
				'label'   => esc_html__( 'Capital letters', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'label',
			array(
				'label'       => esc_html__( 'Name for screen readers', 'avix-widgets' ),
				'description' => esc_html__( 'Screen readers hear this name and then the words as a plain list; the moving band itself is hidden from them.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__( 'What this service covers', 'avix-widgets' ),
				'dynamic'     => array( 'active' => true ),
				'separator'   => 'before',
			)
		);

		$this->end_controls_section();
	}

	private function controls_motion() {
		$this->start_controls_section( 'section_motion', array( 'label' => esc_html__( 'Motion & Bands', 'avix-widgets' ) ) );

		$this->add_control(
			'speed',
			array(
				'label'       => esc_html__( 'Seconds per loop', 'avix-widgets' ),
				'description' => esc_html__( 'How long one full set of words takes to pass. Lower is faster. The band eases to a stop while hovered, and stands still for visitors who prefer reduced motion.', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 's' ),
				'range'       => array( 's' => array( 'min' => 6, 'max' => 120, 'step' => 1 ) ),
				'default'     => array( 'unit' => 's', 'size' => 30 ),
			)
		);

		$this->add_control(
			'direction',
			array(
				'label'   => esc_html__( 'Direction', 'avix-widgets' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'left',
				'toggle'  => false,
				'options' => array(
					'left'  => array(
						'title' => esc_html__( 'To the left', 'avix-widgets' ),
						'icon'  => 'eicon-arrow-left',
					),
					'right' => array(
						'title' => esc_html__( 'To the right', 'avix-widgets' ),
						'icon'  => 'eicon-arrow-right',
					),
				),
			)
		);

		$this->add_control(
			'show_toggle',
			array(
				'label'       => esc_html__( 'Pause button', 'avix-widgets' ),
				'description' => esc_html__( 'A small button at the end of the band lets visitors stop the words. Recommended for accessibility.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'tilt',
			array(
				'label'       => esc_html__( 'Tilt', 'avix-widgets' ),
				'description' => esc_html__( 'Turns the band a little for a dynamic, poster-like look. 0 keeps it level.', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'deg' ),
				'range'       => array( 'deg' => array( 'min' => -2, 'max' => 2, 'step' => 0.25 ) ),
				'default'     => array( 'unit' => 'deg', 'size' => 0 ),
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'show_counter',
			array(
				'label'       => esc_html__( 'Second band', 'avix-widgets' ),
				'description' => esc_html__( 'A second band in a contrasting colour runs the other way. With a tilt, the two bands cross.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
			)
		);

		$this->add_control(
			'counter_words',
			array(
				'label'       => esc_html__( 'Second band words', 'avix-widgets' ),
				'description' => esc_html__( 'One per line. Leave empty to reuse the words above.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 5,
				'default'     => '',
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_counter' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_pal() {
		$this->start_controls_section( 'section_pal', array( 'label' => esc_html__( 'Pixel Character', 'avix-widgets' ) ) );

		$this->add_control(
			'show_buddy',
			array(
				'label'       => esc_html__( 'Show pixel character', 'avix-widgets' ),
				'description' => esc_html__( 'Rides on the top edge of the band, walking against the words so it stays in place. Hover the band and it stops to say hi.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'show_hop',
			array(
				'label'       => esc_html__( 'Hop over separators', 'avix-widgets' ),
				'description' => esc_html__( 'Every few seconds it times a little jump to clear a separator passing under its feet.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'condition'   => array( 'show_buddy' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'pal_x',
			array(
				'label'       => esc_html__( 'Position along the band', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%' ),
				'range'       => array( '%' => array( 'min' => 5, 'max' => 95 ) ),
				'selectors'   => array( '{{WRAPPER}} .avix-tk' => '--tk-pal-x: calc({{SIZE}} / 100);' ),
				'condition'   => array( 'show_buddy' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'pal_size',
			array(
				'label'      => esc_html__( 'Character size', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 20, 'max' => 64 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-tk' => '--tk-pal-w: {{SIZE}}{{UNIT}};' ),
				'condition'  => array( 'show_buddy' => 'yes' ),
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
				'label'   => esc_html__( 'Band colour', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'orange',
				'options' => array(
					'orange' => esc_html__( 'Orange with white words', 'avix-widgets' ),
					'ink'    => esc_html__( 'Ink with white words', 'avix-widgets' ),
					'light'  => esc_html__( 'Light with dark words', 'avix-widgets' ),
				),
			)
		);

		$this->add_control(
			'shape',
			array(
				'label'   => esc_html__( 'Shape', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'bleed',
				'options' => array(
					'bleed' => esc_html__( 'Edge to edge', 'avix-widgets' ),
					'inset' => esc_html__( 'Rounded pill inside the page width', 'avix-widgets' ),
				),
			)
		);

		$colors = array(
			'tk_bg'     => array( esc_html__( 'Section background', 'avix-widgets' ), '--tk-bg' ),
			'tk_band'   => array( esc_html__( 'Band', 'avix-widgets' ), '--tk-band' ),
			'tk_ink'    => array( esc_html__( 'Words', 'avix-widgets' ), '--tk-ink' ),
			'tk_sep'    => array( esc_html__( 'Separators', 'avix-widgets' ), '--tk-sep' ),
			'tk_accent' => array( esc_html__( 'Pixel character', 'avix-widgets' ), '--tk-accent' ),
			'tk_band_2' => array( esc_html__( 'Second band', 'avix-widgets' ), '--tk-band-2' ),
			'tk_ink_2'  => array( esc_html__( 'Second band words', 'avix-widgets' ), '--tk-ink-2' ),
			'tk_sep_2'  => array( esc_html__( 'Second band separators', 'avix-widgets' ), '--tk-sep-2' ),
		);
		foreach ( $colors as $key => $color ) {
			$this->add_control(
				$key,
				array(
					'label'     => $color[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-tk' => $color[1] . ': {{VALUE}};' ),
				)
			);
		}

		$this->add_control(
			'seam',
			array(
				'label'       => esc_html__( 'Band on the bottom edge', 'avix-widgets' ),
				'description' => esc_html__( 'Removes the space under the band, so it sits on the edge like a seam between this section and the next. Give the section the background of the section above.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
				'separator'   => 'before',
			)
		);

		$this->add_responsive_control(
			'padding',
			array(
				'label'              => esc_html__( 'Padding', 'avix-widgets' ),
				'type'               => Controls_Manager::DIMENSIONS,
				'size_units'         => array( 'px', 'vh' ),
				'allowed_dimensions' => 'vertical',
				'description'        => esc_html__( 'Leave room on top for the pixel character.', 'avix-widgets' ),
				'selectors'          => array( '{{WRAPPER}} .avix-tk' => 'padding-top: {{TOP}}{{UNIT}}; padding-bottom: {{BOTTOM}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'band_space',
			array(
				'label'      => esc_html__( 'Band height (space above and below the words)', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 6, 'max' => 64 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-tk' => '--tk-band-py: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'word_gap',
			array(
				'label'      => esc_html__( 'Space around separators', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 6, 'max' => 96 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-tk' => '--tk-word-gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'max_width',
			array(
				'label'      => esc_html__( 'Pill width', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 480, 'max' => 1600 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-tk' => '--tk-max: {{SIZE}}{{UNIT}};' ),
				'condition'  => array( 'shape' => 'inset' ),
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

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'word_typography',
				'label'    => esc_html__( 'Words', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-tk .avix-tk__group',
			)
		);

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ */
	/* Render                                                              */
	/* ------------------------------------------------------------------ */

	protected function render(): void {
		$s     = $this->get_settings_for_display();
		$words = $this->lines( $s['words'] ?? '' );

		if ( ! $words ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="elementor-alert elementor-alert-info">' . esc_html__( 'Ticker: add at least one word (one per line).', 'avix-widgets' ) . '</div>';
			}
			return;
		}

		$counter = array();
		if ( 'yes' === ( $s['show_counter'] ?? '' ) ) {
			$counter = $this->lines( $s['counter_words'] ?? '' );
			$counter = $counter ? $counter : $words;
		}

		$theme     = in_array( $s['theme'] ?? '', array( 'orange', 'ink', 'light' ), true ) ? $s['theme'] : 'orange';
		$shape     = 'inset' === ( $s['shape'] ?? '' ) ? 'inset' : 'bleed';
		$direction = 'right' === ( $s['direction'] ?? '' ) ? 'right' : 'left';
		$separator = in_array( $s['separator'] ?? '', array( 'pixel', 'dot', 'arrow', 'alternate' ), true ) ? $s['separator'] : 'pixel';
		$pal       = 'yes' === ( $s['show_buddy'] ?? 'yes' );
		$speed     = isset( $s['speed']['size'] ) && '' !== $s['speed']['size'] ? (float) $s['speed']['size'] : 30;
		$speed     = max( 6, min( 120, $speed ) );
		$tilt      = isset( $s['tilt']['size'] ) && '' !== $s['tilt']['size'] ? (float) $s['tilt']['size'] : 0;
		$tilt      = round( max( -2, min( 2, $tilt ) ), 2 );
		$label     = trim( (string) ( $s['label'] ?? '' ) );

		$classes = array(
			'avix-tk',
			'avix-tk--' . $theme,
			'avix-tk--' . $shape,
			'avix-tk--dir-' . $direction,
			$pal ? 'avix-tk--has-pal' : 'avix-tk--no-pal',
		);
		if ( 'yes' === ( $s['uppercase'] ?? 'yes' ) ) {
			$classes[] = 'avix-tk--upper';
		}
		if ( 'yes' === ( $s['outline_alt'] ?? '' ) ) {
			$classes[] = 'avix-tk--outline';
		}
		if ( 0.0 !== (float) $tilt ) {
			$classes[] = 'avix-tk--tilt';
		}
		if ( $counter ) {
			$classes[] = 'avix-tk--counter';
		}
		if ( 'yes' === ( $s['seam'] ?? '' ) ) {
			$classes[] = 'avix-tk--seam';
		}

		// Alternating patterns (dot/arrow, solid/outline) need an even count to
		// stay in step across the loop, so an odd list is set twice per group.
		$alternates = 'alternate' === $separator || 'yes' === ( $s['outline_alt'] ?? '' );

		// The rise is how far the tilted band's ends climb, per unit of half its width.
		$style = sprintf(
			'--tk-dur:%1$ss;--tk-tilt:%2$sdeg;--tk-rise:%3$s;',
			$this->num( $speed * self::SETS_PER_HALF ),
			$this->num( $tilt ),
			$this->num( round( sin( deg2rad( abs( $tilt ) ) ), 4 ) )
		);

		$this->add_render_attribute(
			'root',
			array(
				'class'        => $classes,
				'style'        => $style,
				'aria-label'   => '' !== $label ? $label : esc_html__( 'Keywords', 'avix-widgets' ),
				'data-avix-tk' => wp_json_encode(
					array(
						'speed' => $speed,
						'dir'   => $direction,
						'pal'   => $pal,
						'hop'   => $pal && 'yes' === ( $s['show_hop'] ?? 'yes' ),
					)
				),
			)
		);
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="avix-tk__inner">
				<ul class="avix-tk__sr">
					<?php foreach ( $words as $word ) : ?>
						<li><?php echo esc_html( $word ); ?></li>
					<?php endforeach; ?>
				</ul>
				<div class="avix-tk__stage" data-tk-stage>
					<?php if ( $counter ) : ?>
						<div class="avix-tk__band avix-tk__band--alt" data-tk-band aria-hidden="true">
							<?php $this->render_viewport( $counter, $separator, $alternates ); ?>
						</div>
					<?php endif; ?>
					<div class="avix-tk__band avix-tk__band--main" data-tk-band>
						<?php $this->render_viewport( $words, $separator, $alternates ); ?>
						<?php
						if ( $pal ) {
							echo Pixel_Pal::render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup built and escaped in Pixel_Pal::render().
								array(
									'class' => 'avix-tk__pal',
									'hi'    => true,
									'attrs' => array( 'data-tk-pal' => '' ),
								)
							);
						}
						?>
						<?php if ( 'yes' === ( $s['show_toggle'] ?? 'yes' ) ) : ?>
							<div class="avix-tk__ctrl">
								<button class="avix-tk__toggle" type="button" data-tk-toggle aria-label="<?php esc_attr_e( 'Pause the moving words', 'avix-widgets' ); ?>" data-label-pause="<?php esc_attr_e( 'Pause the moving words', 'avix-widgets' ); ?>" data-label-play="<?php esc_attr_e( 'Play the moving words', 'avix-widgets' ); ?>">
									<svg class="avix-tk__pause" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 5h3.5v14H7zM13.5 5H17v14h-3.5z"/></svg>
									<svg class="avix-tk__play" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 5.5v13l10.5-6.5L8 5.5Z"/></svg>
								</button>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * The moving part of a band: a track of two identical halves, so sliding
	 * it by exactly half its width (one CSS animation) loops without a seam.
	 * A group holds one set of words, or two when an alternating pattern
	 * meets an odd count; data-tk-sets tells the script, so the pace holds.
	 */
	private function render_viewport( array $words, $separator, $alternates ) {
		$sets  = $alternates && 1 === count( $words ) % 2 ? 2 : 1;
		$group = $this->group_html( 2 === $sets ? array_merge( $words, $words ) : $words, $separator );
		?>
		<div class="avix-tk__viewport" aria-hidden="true" data-tk-sets="<?php echo esc_attr( $sets ); ?>">
			<div class="avix-tk__track" data-tk-track>
				<?php for ( $half = 0; $half < 2; $half++ ) : ?>
					<div class="avix-tk__half" data-tk-half>
						<?php echo str_repeat( $group, (int) ( self::SETS_PER_HALF / $sets ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in group_html(). ?>
					</div>
				<?php endfor; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * One group of words, each followed by its separator.
	 */
	private function group_html( array $words, $separator ) {
		$html = '<div class="avix-tk__group" data-tk-group>';
		foreach ( $words as $i => $word ) {
			$kind = 'alternate' === $separator ? ( 0 === $i % 2 ? 'dot' : 'arrow' ) : $separator;
			$html .= '<span class="avix-tk__item"><span class="avix-tk__word">' . esc_html( $word ) . '</span>' . $this->separator_html( $kind ) . '</span>';
		}
		return $html . '</div>';
	}

	private function separator_html( $kind ) {
		if ( 'arrow' === $kind ) {
			return '<span class="avix-tk__sep avix-tk__sep--arrow"><svg viewBox="0 0 34 12" focusable="false"><path d="M1 6h31M26.5 1 32 6l-5.5 5"/></svg></span>';
		}
		if ( 'dot' === $kind ) {
			return '<span class="avix-tk__sep avix-tk__sep--dot"></span>';
		}
		// A five-pixel spark on a 3×3 grid, the same pixel language as the character.
		return '<span class="avix-tk__sep avix-tk__sep--pixel"><svg viewBox="0 0 3 3" focusable="false"><rect x="1" y="0" width="1" height="1"/><rect x="0" y="1" width="3" height="1"/><rect x="1" y="2" width="1" height="1"/></svg></span>';
	}

	/**
	 * Splits a textarea into trimmed, non-empty lines.
	 */
	private function lines( $text ) {
		$lines = preg_split( '/\r\n|\r|\n/', (string) $text );
		$lines = array_map( 'trim', (array) $lines );
		return array_values(
			array_filter(
				$lines,
				static function ( $line ) {
					return '' !== $line;
				}
			)
		);
	}

	/**
	 * Locale-proof number for inline CSS (never a decimal comma).
	 */
	private function num( $value ) {
		$out = rtrim( rtrim( sprintf( '%.4F', (float) $value ), '0' ), '.' );
		return '' === $out || '-0' === $out ? '0' : $out;
	}

	/**
	 * The Shopify Plus keywords from the current service page (escaped on output).
	 */
	private function default_words() {
		return array(
			__( 'E-commerce', 'avix-widgets' ),
			__( 'Conversion', 'avix-widgets' ),
			__( 'Headless', 'avix-widgets' ),
			__( 'Shopify Plus', 'avix-widgets' ),
			__( 'B2B Commerce', 'avix-widgets' ),
			__( 'Scalability', 'avix-widgets' ),
			__( 'Automation', 'avix-widgets' ),
		);
	}
}
