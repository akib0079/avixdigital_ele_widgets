<?php
/**
 * Case Studies admin: the tabbed "Case study details" meta box built from
 * Case_Study::FIELDS, the starter-layout buttons, the "Settings & import"
 * page, the service-page term field and the list-table columns (SPEC §1.1,
 * §1.2, §5, WI-04). Loaded only in wp-admin.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Case_Studies;

defined( 'ABSPATH' ) || exit;

final class Admin {

	const PAGE          = 'avix-cs-settings';
	const SETTINGS      = 'avix_cs_settings';
	const NONCE         = 'avix_cs_nonce';
	const NONCE_ACTION  = 'avix_cs_save';
	const REPORT_OPTION = 'avix_cs_import_report';

	/** Tab labels, in order. */
	const TABS = array(
		'overview' => 'Overview',
		'facts'    => 'Facts',
		'story'    => 'Story',
		'results'  => 'Results',
		'stack'    => 'Stack',
	);

	/**
	 * How each tab is laid out: titled sections of FIELDS keys. 'cols' = grid
	 * columns for short fields, 'media' = picker cards, 'metrics' = the 4 rows.
	 * FIELDS keys missing here are appended to their tab automatically.
	 */
	const LAYOUT = array(
		'overview' => array(
			array(
				'title' => 'Headline',
				'note'  => 'The hero at the top of the page.',
				'keys'  => array( 'client', 'display_title', 'summary' ),
			),
			array(
				'title' => 'Images',
				'note'  => 'Real screenshots of the live site, plus the studio render when you have one.',
				'keys'  => array( 'hero_render', 'hero_desktop', 'hero_mobile', 'logo' ),
				'media' => true,
			),
			array(
				'title' => 'Look',
				'keys'  => array( 'accent', 'header' ),
				'cols'  => 2,
			),
		),
		'facts'    => array(
			array(
				'title' => 'Facts bar',
				'note'  => 'The row of facts under the hero. Empty facts are left out.',
				'keys'  => array( 'year', 'platform', 'services', 'role', 'market', 'credits' ),
				'cols'  => 2,
			),
			array(
				'title' => 'Live site',
				'keys'  => array( 'live_url', 'live_label' ),
				'cols'  => 2,
			),
			array(
				'title' => 'Cards',
				'note'  => 'Used on the /case-studies/ grid and the "Next case study" card.',
				'keys'  => array( 'card_tags', 'card_label', 'card_value' ),
				'cols'  => 2,
			),
		),
		'story'    => array(
			array(
				'title' => '01 · The challenge',
				'keys'  => array( 'challenge_statement', 'challenge_body', 'constraints' ),
			),
			array(
				'title' => '02 · Approach',
				'keys'  => array( 'approach_intro', 'approach_body', 'implementations' ),
			),
			array(
				'title' => '03 · What we built',
				'note'  => 'The feature spotlights themselves are edited in Elementor.',
				'keys'  => array( 'solution_body' ),
			),
		),
		'results'  => array(
			array(
				'title' => '04 · The impact',
				'keys'  => array( 'outcome_statement', 'outcome_body', 'outcome_pillars', 'proof' ),
			),
			array(
				'title'   => 'Metrics',
				'note'    => 'Only real, sourced numbers. A metric shows only when value, label and source are all filled in.',
				'metrics' => true,
				'keys'    => array(),
			),
			array(
				'title' => 'Client quote',
				'note'  => 'A real quote only. Leave empty and no quote block is shown.',
				'keys'  => array( 'quote_text', 'quote_name', 'quote_role', 'quote_source_url' ),
				'cols'  => 'quote',
			),
		),
		'stack'    => array(
			array(
				'title' => '06 · Services & technology',
				'keys'  => array( 'stack' ),
			),
		),
	);

	public static function init(): void {
		add_action( 'add_meta_boxes_' . Case_Study::POST_TYPE, array( __CLASS__, 'meta_boxes' ), 20 );
		add_action( 'do_meta_boxes', array( __CLASS__, 'remove_custom_fields_box' ), 99, 1 );
		add_action( 'save_post_' . Case_Study::POST_TYPE, array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
		add_filter( 'post_updated_messages', array( __CLASS__, 'updated_messages' ) );

		// Starter buttons and the importer post to admin-post.php.
		add_action( 'admin_post_avix_cs_starter', array( __CLASS__, 'handle_starter' ) );
		add_action( 'admin_post_avix_cs_import', array( __CLASS__, 'handle_import' ) );

		// Settings & import.
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );

		// List table.
		add_filter( 'manage_' . Case_Study::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . Case_Study::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
		add_filter( 'manage_edit-' . Case_Study::POST_TYPE . '_sortable_columns', array( __CLASS__, 'sortable' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'list_order' ) );

		// Service term: "Service page".
		add_action( Case_Study::TAX_SERVICE . '_add_form_fields', array( __CLASS__, 'term_add_field' ) );
		add_action( Case_Study::TAX_SERVICE . '_edit_form_fields', array( __CLASS__, 'term_edit_field' ) );
		add_action( 'created_' . Case_Study::TAX_SERVICE, array( __CLASS__, 'term_save' ) );
		add_action( 'edited_' . Case_Study::TAX_SERVICE, array( __CLASS__, 'term_save' ) );
		add_filter( 'manage_edit-' . Case_Study::TAX_SERVICE . '_columns', array( __CLASS__, 'term_columns' ) );
		add_filter( 'manage_' . Case_Study::TAX_SERVICE . '_custom_column', array( __CLASS__, 'term_column' ), 10, 3 );
	}

	/* ------------------------------------------------------------------ */
	/* Assets                                                             */
	/* ------------------------------------------------------------------ */

	/**
	 * Our assets only on our screens.
	 *
	 * @param string $hook Admin page hook.
	 */
	public static function enqueue( $hook ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen ) {
			return;
		}
		$ours = Case_Study::POST_TYPE === $screen->post_type || ( isset( $screen->taxonomy ) && Case_Study::TAX_SERVICE === $screen->taxonomy );
		if ( ! $ours ) {
			return;
		}
		$is_edit = 'post' === $screen->base;

		wp_enqueue_style( 'avix-cs-admin', self::url( 'assets/admin/case-study-admin.css' ), $is_edit ? array( 'wp-color-picker' ) : array(), self::ver( 'assets/admin/case-study-admin.css' ) );

		if ( $is_edit || self::PAGE === self::current_page() ) {
			$deps = array( 'jquery' );
			if ( $is_edit ) {
				wp_enqueue_media();
				$deps[] = 'wp-color-picker';
			}
			wp_enqueue_script( 'avix-cs-admin', self::url( 'assets/admin/case-study-admin.js' ), $deps, self::ver( 'assets/admin/case-study-admin.js' ), true );
			wp_localize_script(
				'avix-cs-admin',
				'avixCsAdmin',
				array(
					'postId' => $is_edit ? (int) get_the_ID() : 0,
					'i18n'   => array(
						'choose'       => __( 'Choose image', 'avix-widgets' ),
						'replace'      => __( 'Replace', 'avix-widgets' ),
						'use'          => __( 'Use this image', 'avix-widgets' ),
						'confirmReset' => __( "Replace this page's Elementor layout with the starter layout?\n\nA revision is saved first, so you can restore the current layout from Revisions.", 'avix-widgets' ),
						'leave'        => __( 'You have unsaved changes. Update the case study first, then apply the layout.', 'avix-widgets' ),
						'year'         => __( 'Use four digits, e.g. 2025. Anything else is not saved.', 'avix-widgets' ),
						'url'          => __( 'Start with https:// (or http://). Anything else is not saved.', 'avix-widgets' ),
						'hex'          => __( 'Use a hex colour like #fb6007. Anything else is not saved.', 'avix-widgets' ),
						'running'      => __( 'Importing…', 'avix-widgets' ),
					),
				)
			);
		}
	}

	private static function url( string $rel ): string {
		$base = defined( 'AVIX_EW_URL' ) ? AVIX_EW_URL : plugin_dir_url( dirname( __DIR__ ) );
		return $base . $rel;
	}

	private static function ver( string $rel ): string {
		$path  = dirname( __DIR__, 2 ) . '/' . $rel;
		$mtime = file_exists( $path ) ? (int) filemtime( $path ) : 0;
		$ver   = defined( 'AVIX_EW_VERSION' ) ? AVIX_EW_VERSION : '0';
		return $mtime ? $ver . '.' . $mtime : $ver;
	}

	private static function current_page(): string {
		return isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- screen detection only.
	}

	/* ------------------------------------------------------------------ */
	/* Meta boxes                                                         */
	/* ------------------------------------------------------------------ */

	/**
	 * @param \WP_Post $post Post.
	 */
	public static function meta_boxes( $post ) {
		add_meta_box( 'avix-cs-details', __( 'Case study details', 'avix-widgets' ), array( __CLASS__, 'render_box' ), Case_Study::POST_TYPE, 'normal', 'high' );

		// The excerpt is the card summary and the meta description fallback: say so.
		remove_meta_box( 'postexcerpt', Case_Study::POST_TYPE, 'normal' );
		add_meta_box( 'postexcerpt', __( 'Card summary (excerpt)', 'avix-widgets' ), array( __CLASS__, 'render_excerpt_box' ), Case_Study::POST_TYPE, 'normal', 'high' );
	}

	/**
	 * Meta is edited in one place only: our box, never "Custom Fields".
	 *
	 * @param string $post_type Post type of the screen.
	 */
	public static function remove_custom_fields_box( $post_type ) {
		if ( Case_Study::POST_TYPE === $post_type ) {
			remove_meta_box( 'postcustom', Case_Study::POST_TYPE, 'normal' );
			remove_meta_box( 'postcustom', Case_Study::POST_TYPE, 'advanced' );
		}
	}

	/**
	 * @param \WP_Post $post Post.
	 */
	public static function render_excerpt_box( $post ) {
		$value = (string) $post->post_excerpt;
		$len   = function_exists( 'mb_strlen' ) ? mb_strlen( $value ) : strlen( $value );
		echo '<div class="avix-cs-field avix-cs-field--excerpt">';
		echo '<div class="avix-cs-field__head"><label for="excerpt">' . esc_html__( 'Summary for cards and search results', 'avix-widgets' ) . '</label>';
		echo '<span class="avix-cs-count" data-for="excerpt" aria-live="off">' . (int) $len . ' / 200</span></div>';
		echo '<textarea rows="3" cols="40" name="excerpt" id="excerpt" data-max="200" data-soft="1">' . esc_textarea( $value ) . '</textarea>';
		echo '<p class="avix-cs-help">' . esc_html__( 'Up to about 200 characters. Shown on the case-study cards and used as the meta description when Yoast has none.', 'avix-widgets' ) . '</p>';
		echo '</div>';
	}

	/**
	 * The tabbed details box.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function render_box( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE );
		$values = array();
		foreach ( Case_Study::FIELDS as $key => $field ) {
			$values[ $key ] = get_post_meta( $post->ID, Case_Study::meta_key( $key ), true );
		}

		echo '<div class="avix-cs-box" data-avix-cs-box>';
		self::render_starter_bar( $post );

		$layout = self::layout();
		$first  = true;

		echo '<div class="avix-cs-tabs" role="tablist" aria-label="' . esc_attr__( 'Case study details', 'avix-widgets' ) . '">';
		foreach ( self::TABS as $tab => $label ) {
			list( $filled, $total ) = self::tab_progress( $tab, $values );
			printf(
				'<button type="button" class="avix-cs-tab%1$s" role="tab" id="avix-cs-tab-%2$s" aria-controls="avix-cs-panel-%2$s" aria-selected="%3$s" tabindex="%4$s" data-tab="%2$s">%5$s <span class="avix-cs-tab__count" data-tab-count="%2$s">%6$s</span></button>',
				$first ? ' is-active' : '',
				esc_attr( $tab ),
				$first ? 'true' : 'false',
				$first ? '0' : '-1',
				esc_html( self::tab_label( $tab, $label ) ),
				esc_html( $filled . '/' . $total )
			);
			$first = false;
		}
		echo '</div>';

		$first = true;
		foreach ( self::TABS as $tab => $label ) {
			printf(
				'<div class="avix-cs-panel%1$s" role="tabpanel" id="avix-cs-panel-%2$s" aria-labelledby="avix-cs-tab-%2$s" data-panel="%2$s" tabindex="0">',
				$first ? ' is-active' : '',
				esc_attr( $tab )
			);
			echo '<h3 class="avix-cs-panel__title screen-reader-text">' . esc_html( self::tab_label( $tab, $label ) ) . '</h3>';
			foreach ( $layout[ $tab ] as $section ) {
				self::render_section( $section, $values );
			}
			echo '</div>';
			$first = false;
		}

		echo '<p class="avix-cs-box__foot">' . esc_html__( 'Every field is optional. Empty fields are left out of the page, never shown as placeholders. Widgets in Elementor can still override any of these on one page.', 'avix-widgets' ) . '</p>';
		echo '</div>';
	}

	/**
	 * Filled / total for a tab. The select (header) is excluded; metrics count as complete rows.
	 *
	 * @param string $tab    Tab key.
	 * @param array  $values Meta values.
	 */
	private static function tab_progress( string $tab, array $values ): array {
		$filled = 0;
		$total  = 0;
		foreach ( Case_Study::group_fields( $tab ) as $key => $field ) {
			if ( isset( $field['row'] ) || 'select' === $field['type'] ) {
				continue;
			}
			$total++;
			if ( ! Case_Study::is_empty_value( is_scalar( $values[ $key ] ) ? trim( (string) $values[ $key ] ) : '' ) ) {
				$filled++;
			}
		}
		if ( 'results' === $tab ) {
			for ( $n = 1; $n <= 4; $n++ ) {
				$total++;
				$complete = true;
				foreach ( array( 'value', 'label', 'source' ) as $part ) {
					$v = $values[ 'metric_' . $n . '_' . $part ];
					if ( ! is_scalar( $v ) || '' === trim( (string) $v ) ) {
						$complete = false;
					}
				}
				if ( $complete ) {
					$filled++;
				}
			}
		}
		return array( $filled, $total );
	}

	private static function tab_label( string $tab, string $fallback ): string {
		$labels = array(
			'overview' => __( 'Overview', 'avix-widgets' ),
			'facts'    => __( 'Facts', 'avix-widgets' ),
			'story'    => __( 'Story', 'avix-widgets' ),
			'results'  => __( 'Results', 'avix-widgets' ),
			'stack'    => __( 'Stack', 'avix-widgets' ),
		);
		return isset( $labels[ $tab ] ) ? $labels[ $tab ] : $fallback;
	}

	/**
	 * LAYOUT plus any FIELDS key it does not mention, appended to its tab.
	 */
	private static function layout(): array {
		$layout = self::LAYOUT;
		$seen   = array();
		foreach ( $layout as $sections ) {
			foreach ( $sections as $section ) {
				foreach ( $section['keys'] as $key ) {
					$seen[ $key ] = true;
				}
			}
		}
		foreach ( Case_Study::FIELDS as $key => $field ) {
			if ( isset( $seen[ $key ] ) || isset( $field['row'] ) ) {
				continue;
			}
			$group = isset( $layout[ $field['group'] ] ) ? $field['group'] : 'overview';
			$last  = count( $layout[ $group ] ) - 1;
			if ( $last < 0 || ! empty( $layout[ $group ][ $last ]['extra'] ) ) {
				$layout[ $group ][ $last ]['keys'][] = $key;
			} else {
				$layout[ $group ][] = array(
					'title' => __( 'More', 'avix-widgets' ),
					'keys'  => array( $key ),
					'extra' => true,
				);
			}
		}
		return $layout;
	}

	/**
	 * @param array $section Section definition.
	 * @param array $values  Current meta values.
	 */
	private static function render_section( array $section, array $values ) {
		echo '<section class="avix-cs-section">';
		echo '<header class="avix-cs-section__head"><h4 class="avix-cs-section__title">' . esc_html( $section['title'] ) . '</h4>';
		if ( ! empty( $section['note'] ) ) {
			echo '<p class="avix-cs-section__note">' . esc_html( $section['note'] ) . '</p>';
		}
		echo '</header>';

		if ( ! empty( $section['metrics'] ) ) {
			self::render_metrics( $values );
			echo '</section>';
			return;
		}

		$class = 'avix-cs-grid';
		if ( ! empty( $section['media'] ) ) {
			$class .= ' avix-cs-grid--media';
		} elseif ( isset( $section['cols'] ) ) {
			$class .= ' avix-cs-grid--' . sanitize_html_class( (string) $section['cols'] );
		}
		echo '<div class="' . esc_attr( $class ) . '">';
		foreach ( $section['keys'] as $key ) {
			if ( isset( Case_Study::FIELDS[ $key ] ) ) {
				self::render_field( $key, Case_Study::FIELDS[ $key ], $values[ $key ] );
			}
		}
		echo '</div></section>';
	}

	/**
	 * One field: label, counter, control, help.
	 *
	 * @param string $key   FIELDS key.
	 * @param array  $field Definition.
	 * @param mixed  $value Stored value.
	 * @param bool   $compact Metric-row variant (label hidden visually).
	 */
	private static function render_field( string $key, array $field, $value, bool $compact = false ) {
		$id    = 'avix-cs-' . str_replace( '_', '-', $key );
		$name  = 'avix_cs[' . $key . ']';
		$value = is_scalar( $value ) ? (string) $value : '';
		$max   = isset( $field['max'] ) ? (int) $field['max'] : 0;
		$type  = $field['type'];
		$help  = isset( $field['help'] ) ? $field['help'] : '';
		$ph    = isset( $field['placeholder'] ) ? $field['placeholder'] : '';

		$classes = 'avix-cs-field avix-cs-field--' . $type;
		if ( in_array( $type, array( 'textarea', 'rich' ), true ) || ( 'text' === $type && $max >= 120 && ! $compact ) ) {
			$classes .= ' is-wide';
		}
		if ( $compact ) {
			$classes .= ' is-compact';
		}

		if ( 'image' === $type ) {
			self::render_media_field( $key, $field, (int) $value, $id, $name );
			return;
		}

		$count = ( $compact || 'select' === $type ) ? '' : ' data-count';
		echo '<div class="' . esc_attr( $classes ) . '" data-field="' . esc_attr( $key ) . '"' . $count . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed attribute.
		echo '<div class="avix-cs-field__head">';
		echo '<label for="' . esc_attr( $id ) . '"' . ( $compact ? ' class="avix-cs-field__label is-compact"' : ' class="avix-cs-field__label"' ) . '>' . esc_html( $field['label'] );
		if ( ! empty( $field['accent'] ) ) {
			echo ' <span class="avix-cs-badge" title="' . esc_attr__( 'Wrap one phrase in [brackets] to make it orange.', 'avix-widgets' ) . '">[ ]</span>';
		}
		echo '</label>';
		$len = function_exists( 'mb_strlen' ) ? mb_strlen( $value ) : strlen( $value );
		if ( $max > 0 && ! in_array( $type, array( 'color', 'year', 'select' ), true ) ) {
			echo '<span class="avix-cs-count" data-for="' . esc_attr( $id ) . '" aria-live="off">' . (int) $len . ' / ' . (int) $max . '</span>';
		}
		echo '</div>';

		$describe = '' !== $help ? ' aria-describedby="' . esc_attr( $id . '-help' ) . '"' : '';
		$maxattr  = $max > 0 ? ' maxlength="' . (int) $max . '" data-max="' . (int) $max . '"' : '';

		switch ( $type ) {
			case 'textarea':
			case 'rich':
				$rows = isset( $field['rows'] ) ? (int) $field['rows'] : 4;
				echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="' . (int) $rows . '" class="avix-cs-input"' . $maxattr . $describe . ' placeholder="' . esc_attr( $ph ) . '">' . esc_textarea( $value ) . '</textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributes built from ints/escaped ids.
				break;

			case 'select':
				$current = '' !== $value ? $value : ( isset( $field['default'] ) ? $field['default'] : '' );
				echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" class="avix-cs-input"' . $describe . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped id.
				foreach ( $field['options'] as $opt => $opt_label ) {
					echo '<option value="' . esc_attr( $opt ) . '"' . selected( $current, $opt, false ) . '>' . esc_html( $opt_label ) . '</option>';
				}
				echo '</select>';
				break;

			case 'color':
				echo '<input type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="avix-cs-input avix-cs-color" data-validate="hex" maxlength="7" placeholder="' . esc_attr( $ph ) . '"' . $describe . ' autocomplete="off" spellcheck="false">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped id.
				break;

			case 'year':
				echo '<input type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="avix-cs-input avix-cs-input--year" inputmode="numeric" maxlength="4" data-validate="year" placeholder="' . esc_attr( $ph ) . '"' . $describe . ' autocomplete="off">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped id.
				break;

			case 'url':
				echo '<input type="url" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="avix-cs-input" data-validate="url" placeholder="' . esc_attr( $ph ) . '"' . $describe . ' autocomplete="off" spellcheck="false">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped id.
				break;

			default:
				echo '<input type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="avix-cs-input"' . $maxattr . ' placeholder="' . esc_attr( $ph ) . '"' . $describe . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributes built from ints/escaped ids.
		}

		echo '<p class="avix-cs-error" id="' . esc_attr( $id . '-error' ) . '" role="status" hidden></p>';
		if ( '' !== $help ) {
			echo '<p class="avix-cs-help" id="' . esc_attr( $id . '-help' ) . '">' . self::help_html( $help ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in help_html().
		}
		echo '</div>';
	}

	/**
	 * Help text with line formats (e.g. "Title: text", "Label | https://url") set in <code>.
	 */
	private static function help_html( string $help ): string {
		$html = esc_html( $help );
		$formats = array(
			'Title: text',
			'Label | https://url',
			'Name: what it does here',
			'| observed',
			'[brackets]',
			'{platform}',
			'#rrggbb',
			'&lt;strong&gt;',
			'&lt;em&gt;',
		);
		foreach ( $formats as $format ) {
			$html = str_replace( $format, '<code>' . $format . '</code>', $html );
		}
		return $html;
	}

	/**
	 * Image picker card with preview.
	 */
	private static function render_media_field( string $key, array $field, int $value, string $id, string $name ) {
		$valid = $value && wp_attachment_is_image( $value );
		$src   = $valid ? wp_get_attachment_image_src( $value, 'medium_large' ) : false;
		$meta  = $valid ? wp_get_attachment_metadata( $value ) : array();
		$dark  = in_array( $key, array( 'logo', 'hero_render' ), true );
		$ratio = 'hero_mobile' === $key ? 'phone' : ( 'logo' === $key ? 'logo' : 'wide' );
		$dims  = ( is_array( $meta ) && ! empty( $meta['width'] ) ) ? (int) $meta['width'] . ' × ' . (int) $meta['height'] : '';
		$file  = $valid ? wp_basename( (string) get_attached_file( $value ) ) : '';

		printf(
			'<div class="avix-cs-media%1$s%2$s" data-field="%3$s" data-media data-count data-ratio="%4$s">',
			$valid ? ' has-image' : '',
			$dark ? ' is-dark' : '',
			esc_attr( $key ),
			esc_attr( $ratio )
		);
		echo '<div class="avix-cs-field__head"><span class="avix-cs-field__label" id="' . esc_attr( $id ) . '-label">' . esc_html( $field['label'] ) . '</span></div>';
		echo '<button type="button" class="avix-cs-media__preview" data-media-open aria-describedby="' . esc_attr( $id ) . '-help" aria-labelledby="' . esc_attr( $id ) . '-label">';
		if ( $src ) {
			echo '<img src="' . esc_url( $src[0] ) . '" alt="" loading="lazy" decoding="async">';
		}
		echo '<span class="avix-cs-media__empty"><span class="dashicons dashicons-format-image" aria-hidden="true"></span>' . esc_html__( 'Choose image', 'avix-widgets' ) . '</span>';
		echo '</button>';
		echo '<div class="avix-cs-media__meta"><span class="avix-cs-media__file" data-media-file>' . esc_html( $file ) . ( '' !== $dims ? ' · ' . esc_html( $dims ) : '' ) . '</span></div>';
		echo '<div class="avix-cs-media__actions">';
		echo '<button type="button" class="button button-small" data-media-open>' . ( $valid ? esc_html__( 'Replace', 'avix-widgets' ) : esc_html__( 'Choose image', 'avix-widgets' ) ) . '</button>';
		echo '<button type="button" class="button-link avix-cs-media__remove" data-media-remove' . ( $valid ? '' : ' hidden' ) . '>' . esc_html__( 'Remove', 'avix-widgets' ) . '</button>';
		echo '</div>';
		echo '<input type="hidden" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $valid ? (string) $value : '' ) . '" data-media-input>';
		echo '<p class="avix-cs-help" id="' . esc_attr( $id . '-help' ) . '">' . esc_html( $field['help'] ) . '</p>';
		echo '</div>';
	}

	/**
	 * The four metric rows: value, label, source.
	 *
	 * @param array $values Current meta values.
	 */
	private static function render_metrics( array $values ) {
		echo '<div class="avix-cs-metrics">';
		echo '<div class="avix-cs-metrics__head" aria-hidden="true"><span></span><span>' . esc_html__( 'Value', 'avix-widgets' ) . '</span><span>' . esc_html__( 'Label', 'avix-widgets' ) . '</span><span>' . esc_html__( 'Source (required)', 'avix-widgets' ) . '</span></div>';
		for ( $n = 1; $n <= 4; $n++ ) {
			echo '<div class="avix-cs-metric" data-metric="' . (int) $n . '" data-count>';
			echo '<span class="avix-cs-metric__num" aria-hidden="true">' . esc_html( sprintf( '%02d', $n ) ) . '</span>';
			foreach ( array( 'value', 'label', 'source' ) as $part ) {
				$key   = 'metric_' . $n . '_' . $part;
				$field = Case_Study::FIELDS[ $key ];
				/* translators: 1: metric number, 2: part label. */
				$field['label'] = sprintf( __( 'Metric %1$d %2$s', 'avix-widgets' ), $n, strtolower( $field['label'] ) );
				$field['help']  = '';
				self::render_field( $key, $field, $values[ $key ], true );
			}
			echo '<p class="avix-cs-metric__state" data-metric-state hidden>' . esc_html__( 'Hidden on the page until value, label and source are all filled in.', 'avix-widgets' ) . '</p>';
			echo '</div>';
		}
		echo '<p class="avix-cs-help">' . esc_html__( 'Value: e.g. 3, Up to 20%, 1:1 · Source: where the number comes from (the live site, the portfolio, the client).', 'avix-widgets' ) . '</p>';
		echo '</div>';
	}

	/**
	 * Layout status and the Apply / Reset starter buttons.
	 *
	 * @param \WP_Post $post Post.
	 */
	private static function render_starter_bar( $post ) {
		$has_layout = Case_Studies::has_elementor_layout( (int) $post->ID );
		$sections   = 0;
		if ( $has_layout ) {
			$data = json_decode( (string) get_post_meta( $post->ID, '_elementor_data', true ), true );
			$sections = is_array( $data ) ? count( $data ) : 0;
		}
		$spots   = count( Case_Study::spotlights( (int) $post->ID ) );
		$starter = class_exists( __NAMESPACE__ . '\Starter' ) && method_exists( __NAMESPACE__ . '\Starter', 'apply' );
		$base    = admin_url( 'admin-post.php' );
		$nonce   = wp_create_nonce( 'avix_cs_starter_' . $post->ID );

		echo '<div class="avix-cs-starter' . ( $has_layout ? ' has-layout' : '' ) . '">';
		echo '<div class="avix-cs-starter__status"><span class="avix-cs-starter__dot" aria-hidden="true"></span><div>';
		if ( $has_layout ) {
			echo '<strong>' . esc_html__( 'Elementor layout', 'avix-widgets' ) . '</strong> ';
			/* translators: 1: number of sections, 2: number of feature spotlights. */
			echo '<span>' . esc_html( sprintf( _n( '%1$d section', '%1$d sections', $sections, 'avix-widgets' ), $sections ) . ' · ' . sprintf( _n( '%d feature spotlight', '%d feature spotlights', $spots, 'avix-widgets' ), $spots ) ) . '</span>';
		} else {
			echo '<strong>' . esc_html__( 'No Elementor layout yet', 'avix-widgets' ) . '</strong> ';
			echo '<span>' . esc_html__( 'Visitors see a plain page built from these details until you apply the starter layout.', 'avix-widgets' ) . '</span>';
		}
		echo '</div></div>';

		echo '<div class="avix-cs-starter__actions">';
		if ( ! $starter ) {
			echo '<span class="avix-cs-muted">' . esc_html__( 'Starter layout is not available in this build.', 'avix-widgets' ) . '</span>';
		} elseif ( $has_layout ) {
			$url = add_query_arg(
				array(
					'action'   => 'avix_cs_starter',
					'post'     => (int) $post->ID,
					'mode'     => 'reset',
					'_wpnonce' => $nonce,
				),
				$base
			);
			echo '<a class="button avix-cs-starter__reset" href="' . esc_url( $url ) . '" data-starter="reset">' . esc_html__( 'Reset to starter layout', 'avix-widgets' ) . '</a>';
		} else {
			$url = add_query_arg(
				array(
					'action'   => 'avix_cs_starter',
					'post'     => (int) $post->ID,
					'mode'     => 'apply',
					'_wpnonce' => $nonce,
				),
				$base
			);
			echo '<a class="button button-primary avix-cs-starter__apply" href="' . esc_url( $url ) . '" data-starter="apply">' . esc_html__( 'Apply starter layout', 'avix-widgets' ) . '</a>';
		}
		if ( $has_layout && did_action( 'elementor/loaded' ) && 'auto-draft' !== $post->post_status ) {
			echo '<a class="button button-primary avix-cs-starter__edit" href="' . esc_url( admin_url( 'post.php?post=' . (int) $post->ID . '&action=elementor' ) ) . '">' . esc_html__( 'Edit with Elementor', 'avix-widgets' ) . '</a>';
		}
		echo '</div></div>';
	}

	/* ------------------------------------------------------------------ */
	/* Save                                                               */
	/* ------------------------------------------------------------------ */

	/**
	 * Saves the box through the FIELDS sanitisers. Empty or invalid values are deleted.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post.
	 */
	public static function save( $post_id, $post ) {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE_ACTION ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		if ( ! $post || Case_Study::POST_TYPE !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$input = isset( $_POST['avix_cs'] ) && is_array( $_POST['avix_cs'] ) ? wp_unslash( $_POST['avix_cs'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- every value goes through Case_Study::sanitize().

		$dropped = array();
		foreach ( Case_Study::FIELDS as $key => $field ) {
			if ( ! array_key_exists( $key, $input ) ) {
				continue;
			}
			$raw   = is_scalar( $input[ $key ] ) ? (string) $input[ $key ] : '';
			$clean = Case_Study::sanitize( $key, $raw );
			$meta  = Case_Study::meta_key( $key );

			if ( Case_Study::is_empty_value( $clean ) ) {
				delete_post_meta( $post_id, $meta );
				if ( '' !== trim( $raw ) ) {
					$dropped[] = $key;
				}
				continue;
			}
			update_post_meta( $post_id, $meta, is_string( $clean ) ? wp_slash( $clean ) : $clean );
		}

		if ( $dropped ) {
			set_transient(
				'avix_cs_dropped_' . get_current_user_id(),
				array(
					'post'   => (int) $post_id,
					'fields' => $dropped,
				),
				120
			);
		}
	}

	/* ------------------------------------------------------------------ */
	/* Starter + importer actions                                         */
	/* ------------------------------------------------------------------ */

	public static function handle_starter() {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified below.
		check_admin_referer( 'avix_cs_starter_' . $post_id );
		if ( ! $post_id || Case_Study::POST_TYPE !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'You are not allowed to change this case study.', 'avix-widgets' ), '', array( 'response' => 403 ) );
		}
		$reset  = isset( $_GET['mode'] ) && 'reset' === sanitize_key( wp_unslash( $_GET['mode'] ) );
		$result = 'failed';

		if ( class_exists( __NAMESPACE__ . '\Starter' ) && method_exists( __NAMESPACE__ . '\Starter', 'apply' ) ) {
			try {
				// Starter::apply() keeps the current layout in a revision before a reset.
				if ( ! $reset && Case_Studies::has_elementor_layout( $post_id ) ) {
					$result = 'exists';
				}
				if ( 'exists' !== $result ) {
					$ok     = Starter::apply( $post_id, $reset );
					$result = $ok ? ( $reset ? 'reset' : 'applied' ) : 'failed';
				}
			} catch ( \Throwable $error ) {
				error_log( 'Avix case studies: starter failed: ' . $error->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				$result = 'failed';
			}
			Case_Study::flush( $post_id );
			Case_Studies::purge( $post_id, true );
		}

		wp_safe_redirect( add_query_arg( 'avix_cs_starter', $result, get_edit_post_link( $post_id, 'raw' ) ) );
		exit;
	}

	public static function handle_import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to import case studies.', 'avix-widgets' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'avix_cs_import' );

		$back  = add_query_arg(
			array(
				'post_type' => Case_Study::POST_TYPE,
				'page'      => self::PAGE,
			),
			admin_url( 'edit.php' )
		);
		$known = self::importer_slugs();
		$slugs = isset( $_POST['slugs'] ) && is_array( $_POST['slugs'] ) ? array_map( 'sanitize_title', wp_unslash( $_POST['slugs'] ) ) : array();
		$slugs = array_values( array_intersect( $slugs, array_keys( $known ) ) );

		if ( ! self::importer_ready() ) {
			wp_safe_redirect( add_query_arg( 'avix_cs_import', 'missing', $back ) . '#avix-cs-import' );
			exit;
		}
		if ( ! $slugs ) {
			wp_safe_redirect( add_query_arg( 'avix_cs_import', 'none', $back ) . '#avix-cs-import' );
			exit;
		}

		try {
			$report = Importer::run(
				array(
					'publish' => ! empty( $_POST['publish'] ),
					'force'   => ! empty( $_POST['force'] ),
					'slugs'   => count( $slugs ) === count( $known ) ? array() : $slugs,
				)
			);
			if ( ! empty( $_POST['index_page'] ) ) {
				Importer::index_page( array( 'publish' => true, 'reset' => true ) );
			}
		} catch ( \Throwable $error ) {
			error_log( 'Avix case studies: import failed: ' . $error->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			update_option(
				self::REPORT_OPTION,
				array(
					'time'   => time(),
					'error'  => $error->getMessage(),
					'report' => array(),
				),
				false
			);
			wp_safe_redirect( add_query_arg( 'avix_cs_import', 'error', $back ) . '#avix-cs-import' );
			exit;
		}

		update_option(
			self::REPORT_OPTION,
			array(
				'time'   => time(),
				'error'  => '',
				'report' => is_array( $report ) ? $report : array(),
			),
			false
		);
		Case_Study::flush();
		wp_safe_redirect( add_query_arg( 'avix_cs_import', 'done', $back ) . '#avix-cs-import' );
		exit;
	}

	private static function importer_ready(): bool {
		return class_exists( __NAMESPACE__ . '\Importer' ) && method_exists( __NAMESPACE__ . '\Importer', 'run' );
	}

	/**
	 * Importer::slugs() as slug => label.
	 */
	private static function importer_slugs(): array {
		if ( ! class_exists( __NAMESPACE__ . '\Importer' ) || ! method_exists( __NAMESPACE__ . '\Importer', 'slugs' ) ) {
			return array();
		}
		try {
			$raw = Importer::slugs();
		} catch ( \Throwable $error ) {
			return array();
		}
		$out = array();
		foreach ( (array) $raw as $k => $v ) {
			if ( is_string( $k ) && ! is_numeric( $k ) ) {
				$slug  = sanitize_title( $k );
				$label = is_scalar( $v ) ? (string) $v : $k;
			} else {
				$slug  = sanitize_title( is_scalar( $v ) ? (string) $v : '' );
				$label = $slug;
			}
			if ( '' !== $slug ) {
				$out[ $slug ] = $label;
			}
		}
		return $out;
	}

	/* ------------------------------------------------------------------ */
	/* Notices                                                            */
	/* ------------------------------------------------------------------ */

	public static function notices() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || Case_Study::POST_TYPE !== $screen->post_type ) {
			return;
		}

		if ( 'post' === $screen->base ) {
			$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.

			$dropped = get_transient( 'avix_cs_dropped_' . get_current_user_id() );
			if ( is_array( $dropped ) && $post_id && (int) $dropped['post'] === $post_id ) {
				delete_transient( 'avix_cs_dropped_' . get_current_user_id() );
				$items = array();
				foreach ( (array) $dropped['fields'] as $key ) {
					if ( isset( Case_Study::FIELDS[ $key ] ) ) {
						$items[] = '<li><strong>' . esc_html( self::field_label( $key ) ) . '</strong>: ' . esc_html( self::field_rule( $key ) ) . '</li>';
					}
				}
				if ( $items ) {
					echo '<div class="notice notice-warning is-dismissible avix-cs-notice"><p>' . esc_html__( 'These values were not saved because they are not valid:', 'avix-widgets' ) . '</p><ul>' . implode( '', $items ) . '</ul></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- items escaped above.
				}
			}

			$starter = isset( $_GET['avix_cs_starter'] ) ? sanitize_key( wp_unslash( $_GET['avix_cs_starter'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
			$edit    = $post_id ? admin_url( 'post.php?post=' . $post_id . '&action=elementor' ) : '';
			$msgs    = array(
				'applied' => array( 'success', __( 'Starter layout applied.', 'avix-widgets' ) ),
				'reset'   => array( 'success', __( 'Layout reset to the starter. The previous layout is saved under Revisions.', 'avix-widgets' ) ),
				'exists'  => array( 'info', __( 'This case study already has a layout, so nothing was changed. Use "Reset to starter layout" to replace it.', 'avix-widgets' ) ),
				'failed'  => array( 'error', __( 'The starter layout could not be applied. Check that a starter template is set in Case Studies → Settings & import.', 'avix-widgets' ) ),
			);
			if ( isset( $msgs[ $starter ] ) ) {
				echo '<div class="notice notice-' . esc_attr( $msgs[ $starter ][0] ) . ' is-dismissible"><p>' . esc_html( $msgs[ $starter ][1] );
				if ( in_array( $starter, array( 'applied', 'reset' ), true ) && $edit && did_action( 'elementor/loaded' ) ) {
					echo ' <a href="' . esc_url( $edit ) . '">' . esc_html__( 'Open in Elementor', 'avix-widgets' ) . '</a>';
				}
				echo '</p></div>';
			}
		}
	}

	private static function field_label( string $key ): string {
		$field = Case_Study::FIELDS[ $key ];
		if ( isset( $field['row'] ) ) {
			/* translators: 1: metric number, 2: part. */
			return sprintf( __( 'Metric %1$d %2$s', 'avix-widgets' ), (int) $field['row'], strtolower( $field['label'] ) );
		}
		return $field['label'];
	}

	private static function field_rule( string $key ): string {
		switch ( Case_Study::FIELDS[ $key ]['type'] ) {
			case 'year':
				return __( 'use four digits, e.g. 2025', 'avix-widgets' );
			case 'url':
				return __( 'only http:// and https:// links are allowed', 'avix-widgets' );
			case 'color':
				return __( 'use a hex colour like #fb6007', 'avix-widgets' );
			case 'image':
				return __( 'pick an image from the media library', 'avix-widgets' );
			default:
				return __( 'the value was empty after cleaning', 'avix-widgets' );
		}
	}

	/**
	 * Case-study wording for the "post updated" messages.
	 *
	 * @param array $messages Messages per post type.
	 */
	public static function updated_messages( $messages ) {
		$post = get_post();
		if ( ! $post || Case_Study::POST_TYPE !== $post->post_type ) {
			return $messages;
		}
		$view = ' <a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html__( 'View case study', 'avix-widgets' ) . '</a>';
		$prev = ' <a target="_blank" href="' . esc_url( get_preview_post_link( $post ) ) . '">' . esc_html__( 'Preview case study', 'avix-widgets' ) . '</a>';

		$messages[ Case_Study::POST_TYPE ] = array(
			0  => '',
			1  => __( 'Case study updated.', 'avix-widgets' ) . $view,
			2  => __( 'Custom field updated.', 'avix-widgets' ),
			3  => __( 'Custom field deleted.', 'avix-widgets' ),
			4  => __( 'Case study updated.', 'avix-widgets' ),
			5  => isset( $_GET['revision'] ) ? __( 'Case study restored from a revision.', 'avix-widgets' ) : false, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			6  => __( 'Case study published.', 'avix-widgets' ) . $view,
			7  => __( 'Case study saved.', 'avix-widgets' ),
			8  => __( 'Case study submitted.', 'avix-widgets' ) . $prev,
			9  => __( 'Case study scheduled.', 'avix-widgets' ),
			10 => __( 'Case study draft updated.', 'avix-widgets' ) . $prev,
		);
		return $messages;
	}

	/* ------------------------------------------------------------------ */
	/* Settings & import page                                             */
	/* ------------------------------------------------------------------ */

	public static function menu() {
		add_submenu_page(
			'edit.php?post_type=' . Case_Study::POST_TYPE,
			__( 'Case studies: settings & import', 'avix-widgets' ),
			__( 'Settings & import', 'avix-widgets' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render_settings' )
		);
	}

	public static function register_settings() {
		register_setting(
			self::SETTINGS,
			Case_Studies::OPTION_INDEX,
			array(
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => static function ( $value ) {
					$id = absint( $value );
					return ( $id && 'page' === get_post_type( $id ) ) ? $id : 0;
				},
			)
		);
		register_setting(
			self::SETTINGS,
			Case_Studies::OPTION_STARTER,
			array(
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => static function ( $value ) {
					$id = absint( $value );
					return ( $id && 'elementor_library' === get_post_type( $id ) ) ? $id : 0;
				},
			)
		);
		register_setting(
			self::SETTINGS,
			Case_Studies::OPTION_CHARACTERS,
			array(
				'type'              => 'string',
				'default'           => 'one',
				'sanitize_callback' => static function ( $value ) {
					$value = is_string( $value ) ? sanitize_key( $value ) : '';
					return in_array( $value, array( 'one', 'none', 'site' ), true ) ? $value : 'one';
				},
			)
		);
	}

	public static function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$index_opt = absint( get_option( Case_Studies::OPTION_INDEX, 0 ) );
		$index     = Case_Study::index_page_id();
		$starter   = absint( get_option( Case_Studies::OPTION_STARTER, 0 ) );
		$chars     = Case_Studies::characters_mode();

		echo '<div class="wrap avix-cs-settings">';
		echo '<h1 class="avix-cs-settings__title"><span class="avix-cs-settings__px" aria-hidden="true"></span>' . esc_html__( 'Case studies', 'avix-widgets' ) . ' <span>' . esc_html__( 'Settings & import', 'avix-widgets' ) . '</span></h1>';
		echo '<p class="avix-cs-settings__lead">' . esc_html__( 'Where the case studies live, which layout new ones start from, and how many pixel characters appear on these pages.', 'avix-widgets' ) . '</p>';

		settings_errors( self::SETTINGS );

		echo '<form method="post" action="options.php" class="avix-cs-settings__form">';
		settings_fields( self::SETTINGS );

		// Index page.
		echo '<section class="avix-cs-card"><header class="avix-cs-card__head"><h2>' . esc_html__( 'Index page', 'avix-widgets' ) . '</h2>';
		echo '<p>' . esc_html__( 'The page that lists every case study. Used for breadcrumbs, schema, the "View all" links and cache purges.', 'avix-widgets' ) . '</p></header>';
		echo '<div class="avix-cs-card__body"><label class="avix-cs-label" for="avix-cs-index">' . esc_html__( 'Page', 'avix-widgets' ) . '</label>';
		$dropdown = wp_dropdown_pages(
			array(
				'name'              => Case_Studies::OPTION_INDEX, // phpcs:ignore WordPress.Arrays.ArrayKeySpacingRestrictions
				'id'                => 'avix-cs-index',
				'selected'          => $index_opt, // phpcs:ignore WordPress.Arrays.ArrayKeySpacingRestrictions
				'show_option_none'  => __( 'Detect automatically (the page at /case-studies/)', 'avix-widgets' ),
				'option_none_value' => '0',
				'post_status'       => array( 'publish', 'draft', 'private' ),
				'echo'              => 0,
			)
		);
		echo $dropdown ? $dropdown : '<p class="avix-cs-muted">' . esc_html__( 'No pages yet.', 'avix-widgets' ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core dropdown markup.
		echo '<p class="avix-cs-help">';
		if ( $index ) {
			/* translators: %s: page title. */
			echo esc_html( sprintf( __( 'In use: %s', 'avix-widgets' ), Case_Study::plain( get_the_title( $index ) ) ) ) . ' · <a href="' . esc_url( get_permalink( $index ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'View', 'avix-widgets' ) . '</a> · <a href="' . esc_url( get_edit_post_link( $index ) ) . '">' . esc_html__( 'Edit', 'avix-widgets' ) . '</a>';
		} else {
			echo esc_html__( 'No index page found yet. Create a page with the slug "case-studies" and add the Case Study Grid widget.', 'avix-widgets' );
		}
		echo '</p></div></section>';

		// Starter template.
		$templates = get_posts(
			array(
				'post_type'        => 'elementor_library',
				'post_status'      => 'publish',
				'posts_per_page'   => 200,
				'orderby'          => 'title',
				'order'            => 'ASC',
				'suppress_filters' => true,
			)
		);
		echo '<section class="avix-cs-card"><header class="avix-cs-card__head"><h2>' . esc_html__( 'Starter layout', 'avix-widgets' ) . '</h2>';
		echo '<p>' . esc_html__( 'New case studies start with this Elementor layout. Pick a saved template to redesign the starter visually, or keep the bundled one.', 'avix-widgets' ) . '</p></header>';
		echo '<div class="avix-cs-card__body"><label class="avix-cs-label" for="avix-cs-starter">' . esc_html__( 'Template', 'avix-widgets' ) . '</label>';
		echo '<select id="avix-cs-starter" name="' . esc_attr( Case_Studies::OPTION_STARTER ) . '">';
		echo '<option value="0"' . selected( $starter, 0, false ) . '>' . esc_html__( 'Bundled starter (hero, 3 chapters, 3 spotlights, results, gallery, stack, next)', 'avix-widgets' ) . '</option>';
		foreach ( $templates as $template ) {
			$type = (string) get_post_meta( $template->ID, '_elementor_template_type', true );
			$name = Case_Study::plain( get_the_title( $template ) );
			echo '<option value="' . (int) $template->ID . '"' . selected( $starter, (int) $template->ID, false ) . '>' . esc_html( ( '' !== $name ? $name : '#' . $template->ID ) . ( '' !== $type ? ' (' . $type . ')' : '' ) ) . '</option>';
		}
		echo '</select>';
		echo '<p class="avix-cs-help">' . esc_html__( 'Only new case studies use it. Existing ones keep their layout unless you press "Reset to starter layout" on their edit screen.', 'avix-widgets' ) . '</p>';
		echo '</div></section>';

		// Characters.
		$modes = array(
			'one'  => array( __( 'One character', 'avix-widgets' ), __( 'Recommended. The header and footer characters step aside; one character sits on the closing call-to-action card and waves once. The index page keeps the footer one.', 'avix-widgets' ) ),
			'none' => array( __( 'No characters', 'avix-widgets' ), __( 'A quieter, editorial look: no pixel character anywhere on case-study pages.', 'avix-widgets' ) ),
			'site' => array( __( 'Like the rest of the site', 'avix-widgets' ), __( 'Header and footer keep their characters too.', 'avix-widgets' ) ),
		);
		echo '<section class="avix-cs-card"><header class="avix-cs-card__head"><h2 id="avix-cs-chars-title">' . esc_html__( 'Pixel character on case-study pages', 'avix-widgets' ) . '</h2>';
		echo '<p>' . esc_html__( 'The rest of the site is not affected.', 'avix-widgets' ) . '</p></header>';
		echo '<div class="avix-cs-card__body"><fieldset class="avix-cs-choices" aria-labelledby="avix-cs-chars-title">';
		foreach ( $modes as $mode => $copy ) {
			echo '<label class="avix-cs-choice"><input type="radio" name="' . esc_attr( Case_Studies::OPTION_CHARACTERS ) . '" value="' . esc_attr( $mode ) . '"' . checked( $chars, $mode, false ) . '>';
			echo '<span class="avix-cs-choice__body"><strong>' . esc_html( $copy[0] ) . '</strong><span>' . esc_html( $copy[1] ) . '</span></span></label>';
		}
		echo '</fieldset></div></section>';

		echo '<p class="avix-cs-settings__submit">';
		submit_button( __( 'Save settings', 'avix-widgets' ), 'primary', 'submit', false );
		echo '</p></form>';

		self::render_importer();
		echo '</div>';
	}

	/**
	 * Importer panel: per-slug status, Run import (drafts), missing media from the last run.
	 */
	private static function render_importer() {
		$slugs  = self::importer_slugs();
		$ready  = self::importer_ready();
		$stored = get_option( self::REPORT_OPTION, array() );
		$report = is_array( $stored ) && isset( $stored['report'] ) && is_array( $stored['report'] ) ? $stored['report'] : array();
		$state  = isset( $_GET['avix_cs_import'] ) ? sanitize_key( wp_unslash( $_GET['avix_cs_import'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.

		echo '<section class="avix-cs-card avix-cs-import" id="avix-cs-import"><header class="avix-cs-card__head"><h2>' . esc_html__( 'Import the case studies', 'avix-widgets' ) . '</h2>';
		echo '<p>' . esc_html__( 'Creates or updates the bundled case studies as drafts: text, terms, images that are already in the media library, Yoast titles and the layout. Running it again updates the same posts; a study whose data did not change is left untouched. Nothing is published.', 'avix-widgets' ) . '</p></header>';
		echo '<div class="avix-cs-card__body">';

		$notes = array(
			'done'    => array( 'success', __( 'Import finished. Review the drafts below before publishing.', 'avix-widgets' ) ),
			'none'    => array( 'warning', __( 'Pick at least one case study to import.', 'avix-widgets' ) ),
			'missing' => array( 'error', __( 'The importer is not available in this build.', 'avix-widgets' ) ),
			'error'   => array( 'error', __( 'The import stopped with an error. Nothing after the error was changed.', 'avix-widgets' ) ),
		);
		if ( isset( $notes[ $state ] ) ) {
			echo '<div class="notice notice-' . esc_attr( $notes[ $state ][0] ) . ' inline"><p>' . esc_html( $notes[ $state ][1] );
			if ( 'error' === $state && ! empty( $stored['error'] ) ) {
				echo ' <code>' . esc_html( $stored['error'] ) . '</code>';
			}
			echo '</p></div>';
		}

		if ( ! $ready || ! $slugs ) {
			echo '<p class="avix-cs-muted">' . esc_html__( 'The importer is not available in this build.', 'avix-widgets' ) . '</p></div></section>';
			return;
		}

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" data-import-form>';
		wp_nonce_field( 'avix_cs_import' );
		echo '<input type="hidden" name="action" value="avix_cs_import">';
		echo '<table class="widefat avix-cs-import__table"><thead><tr>';
		echo '<td class="check-column"><input type="checkbox" data-import-all checked aria-label="' . esc_attr__( 'Select all', 'avix-widgets' ) . '"></td>';
		echo '<th scope="col">' . esc_html__( 'Case study', 'avix-widgets' ) . '</th><th scope="col">' . esc_html__( 'Status', 'avix-widgets' ) . '</th><th scope="col">' . esc_html__( 'Last import', 'avix-widgets' ) . '</th><th scope="col">' . esc_html__( 'Missing media', 'avix-widgets' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $slugs as $slug => $label ) {
			$row     = isset( $report[ $slug ] ) && is_array( $report[ $slug ] ) ? $report[ $slug ] : array();
			$post_id = self::post_for_slug( $slug, isset( $row['post_id'] ) ? (int) $row['post_id'] : 0 );
			$status  = $post_id ? get_post_status( $post_id ) : '';
			$missing = isset( $row['missing_media'] ) && is_array( $row['missing_media'] ) ? array_values( array_filter( array_map( 'strval', $row['missing_media'] ) ) ) : array();

			echo '<tr>';
			echo '<th scope="row" class="check-column"><input type="checkbox" name="slugs[]" value="' . esc_attr( $slug ) . '" checked id="avix-cs-imp-' . esc_attr( $slug ) . '"></th>';
			echo '<td><label for="avix-cs-imp-' . esc_attr( $slug ) . '"><strong>' . esc_html( $post_id ? Case_Study::plain( get_the_title( $post_id ) ) : ucwords( str_replace( '-', ' ', $label ) ) ) . '</strong></label><br><code class="avix-cs-slug">/case-studies/' . esc_html( $slug ) . '/</code></td>';

			echo '<td>';
			if ( ! $post_id ) {
				echo '<span class="avix-cs-pill">' . esc_html__( 'Not imported', 'avix-widgets' ) . '</span>';
			} else {
				$obj   = get_post_status_object( (string) $status );
				$class = 'publish' === $status ? 'is-live' : 'is-draft';
				echo '<span class="avix-cs-pill ' . esc_attr( $class ) . '">' . esc_html( $obj ? $obj->label : (string) $status ) . '</span>';
				echo '<span class="avix-cs-rowlinks"><a href="' . esc_url( get_edit_post_link( $post_id ) ) . '">' . esc_html__( 'Edit', 'avix-widgets' ) . '</a>';
				$view = 'publish' === $status ? get_permalink( $post_id ) : get_preview_post_link( $post_id );
				echo ' · <a href="' . esc_url( $view ) . '" target="_blank" rel="noopener">' . esc_html( 'publish' === $status ? __( 'View', 'avix-widgets' ) : __( 'Preview', 'avix-widgets' ) ) . '</a></span>';
			}
			echo '</td>';

			echo '<td>';
			if ( $row ) {
				if ( ! empty( $row['unchanged'] ) ) {
					echo esc_html__( 'Unchanged (nothing written)', 'avix-widgets' );
				} else {
					echo esc_html( ! empty( $row['created'] ) ? __( 'Created', 'avix-widgets' ) : __( 'Updated', 'avix-widgets' ) );
				}
			} else {
				echo '<span class="avix-cs-muted">—</span>';
			}
			echo '</td>';

			echo '<td>';
			if ( ! $row ) {
				echo '<span class="avix-cs-muted">' . esc_html__( 'Run an import to check', 'avix-widgets' ) . '</span>';
			} elseif ( ! $missing ) {
				echo '<span class="avix-cs-ok">' . esc_html__( 'All media found', 'avix-widgets' ) . '</span>';
			} else {
				/* translators: %d: number of missing files. */
				echo '<details class="avix-cs-missing"><summary>' . esc_html( sprintf( _n( '%d file to upload', '%d files to upload', count( $missing ), 'avix-widgets' ), count( $missing ) ) ) . '</summary><ul>';
				foreach ( $missing as $file ) {
					echo '<li><code>' . esc_html( $file ) . '</code></li>';
				}
				echo '</ul></details>';
			}
			echo '</td></tr>';
		}
		echo '</tbody></table>';

		echo '<p class="avix-cs-import__opts">';
		echo '<label><input type="checkbox" name="publish" value="1"> ' . esc_html__( 'Publish the case studies (otherwise they are saved as drafts)', 'avix-widgets' ) . '</label><br>';
		echo '<label><input type="checkbox" name="index_page" value="1"> ' . esc_html__( 'Create or update the /case-studies/ page (published)', 'avix-widgets' ) . '</label><br>';
		echo '<label><input type="checkbox" name="force" value="1"> ' . esc_html__( 'Write every study again, even when its data file did not change (puts back hand edits; updates the modified date)', 'avix-widgets' ) . '</label>';
		echo '</p>';
		echo '<div class="avix-cs-import__foot">';
		echo '<button type="submit" class="button button-primary" data-import-run>' . esc_html__( 'Run import', 'avix-widgets' ) . '</button>';
		echo '<span class="spinner" data-import-spinner></span>';
		if ( ! empty( $stored['time'] ) ) {
			/* translators: %s: human time difference. */
			echo '<span class="avix-cs-muted">' . esc_html( sprintf( __( 'Last run %s ago.', 'avix-widgets' ), human_time_diff( (int) $stored['time'] ) ) ) . '</span>';
		}
		echo '</div>';
		echo '<p class="avix-cs-help">' . esc_html__( 'Missing media: upload the listed files to the media library (same file names), then run the import again. Missing studio renders are fine: the pages fall back to the framed screenshots.', 'avix-widgets' ) . '</p>';
		echo '</form></div></section>';
	}

	/**
	 * The case study for an importer slug (any status but trash).
	 */
	private static function post_for_slug( string $slug, int $hint = 0 ): int {
		if ( $hint && Case_Study::POST_TYPE === get_post_type( $hint ) && 'trash' !== get_post_status( $hint ) ) {
			return $hint;
		}
		$found = get_posts(
			array(
				'post_type'        => Case_Study::POST_TYPE,
				'name'             => $slug,
				'post_status'      => array( 'publish', 'draft', 'pending', 'future', 'private' ),
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => true,
			)
		);
		return $found ? (int) $found[0] : 0;
	}

	/* ------------------------------------------------------------------ */
	/* List table                                                         */
	/* ------------------------------------------------------------------ */

	/**
	 * @param array $columns Columns.
	 */
	public static function columns( $columns ) {
		$out = array();
		if ( isset( $columns['cb'] ) ) {
			$out['cb'] = $columns['cb'];
		}
		$out['avix_cs_thumb']  = '<span class="screen-reader-text">' . esc_html__( 'Image', 'avix-widgets' ) . '</span>';
		$out['title']          = isset( $columns['title'] ) ? $columns['title'] : __( 'Title', 'avix-widgets' );
		$out['avix_cs_client'] = __( 'Client', 'avix-widgets' );
		$tax_key               = 'taxonomy-' . Case_Study::TAX_SERVICE;
		if ( isset( $columns[ $tax_key ] ) ) {
			$out[ $tax_key ] = __( 'Services', 'avix-widgets' );
		}
		$out['avix_cs_year']  = __( 'Year', 'avix-widgets' );
		$out['avix_cs_order'] = __( 'Order', 'avix-widgets' );
		foreach ( $columns as $key => $label ) {
			if ( ! isset( $out[ $key ] ) && 'taxonomy-' . Case_Study::TAX_INDUSTRY !== $key ) {
				$out[ $key ] = $label;
			}
		}
		return $out;
	}

	/**
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public static function column( $column, $post_id ) {
		switch ( $column ) {
			case 'avix_cs_thumb':
				$render = absint( get_post_meta( $post_id, Case_Study::meta_key( 'hero_render' ), true ) );
				$thumb  = (int) get_post_thumbnail_id( $post_id );
				$desk   = absint( get_post_meta( $post_id, Case_Study::meta_key( 'hero_desktop' ), true ) );
				$img    = $render ? $render : ( $thumb ? $thumb : $desk );
				if ( $img && wp_attachment_is_image( $img ) ) {
					echo wp_get_attachment_image( $img, array( 96, 96 ), false, array( 'class' => 'avix-cs-thumb', 'alt' => '', 'loading' => 'lazy' ) );
				} else {
					echo '<span class="avix-cs-thumb is-empty" aria-hidden="true"></span>';
				}
				break;

			case 'avix_cs_client':
				$client = (string) get_post_meta( $post_id, Case_Study::meta_key( 'client' ), true );
				$live   = Case_Study::clean_url( (string) get_post_meta( $post_id, Case_Study::meta_key( 'live_url' ), true ) );
				echo '' !== $client ? esc_html( $client ) : '<span aria-hidden="true">—</span>';
				if ( '' !== $live ) {
					echo '<br><a class="avix-cs-domain" href="' . esc_url( $live ) . '" target="_blank" rel="noopener">' . esc_html( Case_Study::domain( $live ) ) . ' ↗</a>';
				}
				break;

			case 'avix_cs_year':
				$year = (string) get_post_meta( $post_id, Case_Study::meta_key( 'year' ), true );
				echo '' !== $year ? esc_html( $year ) : '<span aria-hidden="true">—</span>';
				break;

			case 'avix_cs_order':
				echo '<span class="avix-cs-order">' . (int) get_post_field( 'menu_order', $post_id ) . '</span>';
				break;
		}
	}

	/**
	 * @param array $columns Sortable columns.
	 */
	public static function sortable( $columns ) {
		$columns['avix_cs_order'] = array( 'menu_order', false );
		$columns['avix_cs_year']  = array( 'avix_cs_year', true );
		return $columns;
	}

	/**
	 * The list follows the site order (menu_order, then newest) unless a column is clicked.
	 *
	 * @param \WP_Query $query Query.
	 */
	public static function list_order( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() || Case_Study::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'edit' !== $screen->base ) {
			return;
		}
		$orderby = $query->get( 'orderby' );
		if ( 'avix_cs_year' === $orderby ) {
			$query->set( 'meta_key', Case_Study::meta_key( 'year' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- admin list sort.
			$query->set( 'orderby', 'meta_value_num' );
			return;
		}
		if ( empty( $orderby ) ) {
			$query->set(
				'orderby',
				array(
					'menu_order' => 'ASC',
					'date'       => 'DESC',
				)
			);
		}
	}

	/* ------------------------------------------------------------------ */
	/* Service term: "Service page"                                       */
	/* ------------------------------------------------------------------ */

	private static function pages_dropdown( int $selected ): string {
		return (string) wp_dropdown_pages(
			array(
				'name'              => 'avix_service_page', // phpcs:ignore WordPress.Arrays.ArrayKeySpacingRestrictions
				'id'                => 'avix-service-page',
				'selected'          => $selected, // phpcs:ignore WordPress.Arrays.ArrayKeySpacingRestrictions
				'show_option_none'  => __( '— No link —', 'avix-widgets' ),
				'option_none_value' => '0',
				'echo'              => 0,
			)
		);
	}

	public static function term_add_field() {
		wp_nonce_field( 'avix_cs_term', 'avix_cs_term_nonce' );
		echo '<div class="form-field term-avix-service-page-wrap"><label for="avix-service-page">' . esc_html__( 'Service page', 'avix-widgets' ) . '</label>';
		echo self::pages_dropdown( 0 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core dropdown markup.
		echo '<p>' . esc_html__( 'Service chips on case studies link to this page, e.g. Shopify → the Shopify service page.', 'avix-widgets' ) . '</p></div>';
	}

	/**
	 * @param \WP_Term $term Term.
	 */
	public static function term_edit_field( $term ) {
		$page = absint( get_term_meta( $term->term_id, Case_Study::SERVICE_PAGE_META, true ) );
		echo '<tr class="form-field term-avix-service-page-wrap"><th scope="row"><label for="avix-service-page">' . esc_html__( 'Service page', 'avix-widgets' ) . '</label></th><td>';
		wp_nonce_field( 'avix_cs_term', 'avix_cs_term_nonce' );
		echo self::pages_dropdown( $page ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core dropdown markup.
		echo '<p class="description">' . esc_html__( 'Service chips on case studies link to this page, e.g. Shopify → the Shopify service page.', 'avix-widgets' ) . '</p></td></tr>';
	}

	/**
	 * @param int $term_id Term ID.
	 */
	public static function term_save( $term_id ) {
		if ( ! isset( $_POST['avix_cs_term_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['avix_cs_term_nonce'] ) ), 'avix_cs_term' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_term', $term_id ) || ! isset( $_POST['avix_service_page'] ) ) {
			return;
		}
		$page = absint( wp_unslash( $_POST['avix_service_page'] ) );
		if ( $page && 'page' === get_post_type( $page ) ) {
			update_term_meta( $term_id, Case_Study::SERVICE_PAGE_META, $page );
		} else {
			delete_term_meta( $term_id, Case_Study::SERVICE_PAGE_META );
		}
	}

	/**
	 * @param array $columns Term list columns.
	 */
	public static function term_columns( $columns ) {
		$out = array();
		foreach ( $columns as $key => $label ) {
			$out[ $key ] = $label;
			if ( 'slug' === $key ) {
				$out['avix_service_page'] = __( 'Service page', 'avix-widgets' );
			}
		}
		if ( ! isset( $out['avix_service_page'] ) ) {
			$out['avix_service_page'] = __( 'Service page', 'avix-widgets' );
		}
		return $out;
	}

	/**
	 * @param string $content Column content.
	 * @param string $column  Column key.
	 * @param int    $term_id Term ID.
	 */
	public static function term_column( $content, $column, $term_id ) {
		if ( 'avix_service_page' !== $column ) {
			return $content;
		}
		$page = absint( get_term_meta( $term_id, Case_Study::SERVICE_PAGE_META, true ) );
		if ( ! $page || ! get_post( $page ) ) {
			return '<span aria-hidden="true">—</span>';
		}
		return '<a href="' . esc_url( get_edit_post_link( $page ) ) . '">' . esc_html( Case_Study::plain( get_the_title( $page ) ) ) . '</a>';
	}
}
