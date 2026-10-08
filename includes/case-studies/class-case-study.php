<?php
/**
 * Case Study data layer: the single source of truth for the case-study meta
 * fields (FIELDS), their sanitisers, and the read API every case-study widget,
 * the cards, the SEO layer and the admin code against (SPEC-CASE-STUDIES §1.3–1.4).
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Case_Studies;

defined( 'ABSPATH' ) || exit;

final class Case_Study {

	const POST_TYPE    = 'avix_case_study';
	const TAX_SERVICE  = 'avix_cs_service';
	const TAX_INDUSTRY = 'avix_cs_industry';

	/** Every meta key is this prefix + a FIELDS key (no leading underscore, so Elementor Pro can read them). */
	const PREFIX = 'avix_cs_';

	/** Term meta on avix_cs_service: the page ID that service chips link to. */
	const SERVICE_PAGE_META = 'avix_service_page';

	/** Inline HTML allowed in the TAK ("rich") fields. */
	const RICH_TAGS = array(
		'a'      => array(
			'href'   => true,
			'title'  => true,
			'target' => true,
			'rel'    => true,
		),
		'strong' => array(),
		'em'     => array(),
	);

	/**
	 * Field definitions, in admin tab order.
	 *
	 * type: text | textarea | rich (textarea with a/strong/em) | image (attachment ID) | url (http/https) |
	 *       color (#hex) | year (4 digits) | select (one of options)
	 * max:  character cap applied on save (0 = none)
	 * group: overview | facts | story | results | stack (the admin tabs)
	 * accent: true when [brackets] mark the orange accent phrase
	 * limit: list fields keep at most this many items on read
	 */
	const FIELDS = array(
		/* ---- Overview ---- */
		'client'              => array(
			'type'        => 'text',
			'max'         => 80,
			'label'       => 'Client',
			'group'       => 'overview',
			'help'        => 'Client name as it appears in the facts bar and on cards, e.g. Rehall.',
			'placeholder' => 'Rehall',
		),
		'display_title'       => array(
			'type'        => 'text',
			'max'         => 160,
			'label'       => 'Display title',
			'group'       => 'overview',
			'accent'      => true,
			'help'        => 'The hero headline in Title Case. Wrap one phrase in [brackets] to highlight it in orange. Empty = post title.',
			'placeholder' => 'A Bilingual Store for [Technical Skiwear]',
		),
		'summary'             => array(
			'type'  => 'textarea',
			'max'   => 400,
			'rows'  => 3,
			'label' => 'Summary',
			'group' => 'overview',
			'help'  => 'The hero lead: one or two sentences. Empty = the excerpt.',
		),
		'header'              => array(
			'type'    => 'select',
			'max'     => 0,
			'label'   => 'Header style',
			'group'   => 'overview',
			'options' => array(
				'auto'  => 'Automatic (matches the hero)',
				'dark'  => 'Dark (Header Home)',
				'light' => 'Light (mani header)',
			),
			'default' => 'auto',
			'help'    => 'The header always matches the Case Study Hero\'s Style > Theme: switch the hero to Light in Elementor and save, and the light header follows. This setting only picks the header for a layout without a Case Study Hero.',
		),
		'logo'                => array(
			'type'  => 'image',
			'max'   => 0,
			'label' => 'Client logo',
			'group' => 'overview',
			'help'  => 'White or mono logo (transparent PNG or SVG). A light hero shows it in ink (the widget\'s Logo on Light setting).',
		),
		'hero_render'         => array(
			'type'  => 'image',
			'max'   => 0,
			'label' => 'Studio render',
			'group' => 'overview',
			'help'  => 'Dark studio render of the live site on devices (3:2). Used by the hero, the grid card and the Next card. Empty = featured image, then the desktop screenshot.',
		),
		'hero_desktop'        => array(
			'type'  => 'image',
			'max'   => 0,
			'label' => 'Desktop screenshot',
			'group' => 'overview',
			'help'  => 'Live homepage on desktop, 16:10. Empty = featured image.',
		),
		'hero_mobile'         => array(
			'type'  => 'image',
			'max'   => 0,
			'label' => 'Phone screenshot',
			'group' => 'overview',
			'help'  => 'Live homepage on a phone, 390 × 844.',
		),
		'accent'              => array(
			'type'        => 'color',
			'max'         => 7,
			'label'       => 'Client accent colour',
			'group'       => 'overview',
			'help'        => 'The client brand colour, mixed softly into the screenshot mats. Format #rrggbb.',
			'placeholder' => '#ece9ec',
		),

		/* ---- Facts ---- */
		'year'                => array(
			'type'        => 'year',
			'max'         => 4,
			'label'       => 'Year',
			'group'       => 'facts',
			'help'        => 'Four digits, e.g. 2025.',
			'placeholder' => '2025',
		),
		'services'            => array(
			'type'        => 'text',
			'max'         => 160,
			'label'       => 'Services',
			'group'       => 'facts',
			'help'        => 'Comma list, verbatim from the portfolio: Shopify, Theme engineering, UX',
			'placeholder' => 'Shopify, Theme engineering',
		),
		'platform'            => array(
			'type'        => 'text',
			'max'         => 40,
			'label'       => 'Platform',
			'group'       => 'facts',
			'help'        => 'Shopify, Webflow… Also fills {platform} in the closing call to action.',
			'placeholder' => 'Shopify',
		),
		'role'                => array(
			'type'  => 'text',
			'max'   => 80,
			'label' => 'Our role',
			'group' => 'facts',
			'help'  => 'What AvixDigital did, e.g. End-to-end Webflow development.',
		),
		'market'              => array(
			'type'        => 'text',
			'max'         => 80,
			'label'       => 'Market',
			'group'       => 'facts',
			'help'        => 'Only when observed on the live site, e.g. Netherlands & Belgium.',
			'placeholder' => 'Netherlands',
		),
		'live_url'            => array(
			'type'        => 'url',
			'max'         => 0,
			'label'       => 'Live site URL',
			'group'       => 'facts',
			'help'        => 'Full https:// address of the live site.',
			'placeholder' => 'https://',
		),
		'live_label'          => array(
			'type'        => 'text',
			'max'         => 60,
			'label'       => 'Live site label',
			'group'       => 'facts',
			'help'        => 'Shown in the browser bar and the facts. Empty = the domain of the live URL.',
			'placeholder' => 'rehall.com',
		),
		'credits'             => array(
			'type'  => 'text',
			'max'   => 200,
			'label' => 'Partner credits',
			'group' => 'facts',
			'help'  => 'e.g. Design: Studio Koers. Hidden unless the hero turns credits on.',
		),
		'card_tags'           => array(
			'type'        => 'text',
			'max'         => 120,
			'label'       => 'Card tags',
			'group'       => 'facts',
			'help'        => 'Comma list for the grid and Next cards: Shopify, Theme engineering',
			'placeholder' => 'Shopify, Theme engineering',
		),
		'card_label'          => array(
			'type'        => 'text',
			'max'         => 40,
			'label'       => 'Card line label',
			'group'       => 'facts',
			'help'        => 'The small label on the card line, e.g. Designed for.',
			'placeholder' => 'Designed for',
		),
		'card_value'          => array(
			'type'        => 'text',
			'max'         => 60,
			'label'       => 'Card line value',
			'group'       => 'facts',
			'help'        => 'The orange value on the card line, e.g. Faster discovery.',
			'placeholder' => 'Faster discovery',
		),

		/* ---- Story ---- */
		'challenge_statement' => array(
			'type'   => 'textarea',
			'max'    => 300,
			'rows'   => 2,
			'label'  => 'Challenge statement',
			'group'  => 'story',
			'accent' => true,
			'help'   => 'One or two sentences shown large. Wrap one phrase in [brackets] for the orange accent.',
		),
		'challenge_body'      => array(
			'type'  => 'rich',
			'max'   => 3000,
			'rows'  => 6,
			'label' => 'Challenge',
			'group' => 'story',
			'help'  => 'Paragraphs separated by a blank line. Links, <strong> and <em> are allowed.',
		),
		'constraints'         => array(
			'type'        => 'text',
			'max'         => 200,
			'label'       => 'Constraints',
			'group'       => 'story',
			'limit'       => 6,
			'help'        => 'Comma list shown as chips, at most 6: Two languages, Large catalogue',
			'placeholder' => 'Two languages, Large catalogue',
		),
		'approach_intro'      => array(
			'type'  => 'textarea',
			'max'   => 600,
			'rows'  => 3,
			'label' => 'Approach intro',
			'group' => 'story',
			'help'  => 'The lead under "The solution & execution".',
		),
		'approach_body'       => array(
			'type'  => 'rich',
			'max'   => 3000,
			'rows'  => 6,
			'label' => 'Approach',
			'group' => 'story',
			'help'  => 'Paragraphs separated by a blank line. Links, <strong> and <em> are allowed.',
		),
		'implementations'     => array(
			'type'        => 'textarea',
			'max'         => 4000,
			'rows'        => 6,
			'label'       => 'Key implementations',
			'group'       => 'story',
			'limit'       => 6,
			'help'        => 'One card per line, Title: text (split on the first colon). At most 6.',
			'placeholder' => "Mega menu: Shop by audience and activity in one panel.\nBilingual store: Dutch and English with one catalogue.",
		),
		'solution_body'       => array(
			'type'  => 'rich',
			'max'   => 2000,
			'rows'  => 4,
			'label' => 'What we built (intro)',
			'group' => 'story',
			'help'  => 'The lead above the feature spotlights. Links, <strong> and <em> are allowed.',
		),

		/* ---- Results ---- */
		'outcome_statement'   => array(
			'type'   => 'textarea',
			'max'    => 400,
			'rows'   => 2,
			'label'  => 'Outcome statement',
			'group'  => 'results',
			'accent' => true,
			'help'   => 'Shown large on the dark impact section. [Brackets] mark the orange accent.',
		),
		'outcome_body'        => array(
			'type'  => 'rich',
			'max'   => 3000,
			'rows'  => 5,
			'label' => 'Outcome',
			'group' => 'results',
			'help'  => 'Paragraphs separated by a blank line. Links, <strong> and <em> are allowed.',
		),
		'outcome_pillars'     => array(
			'type'        => 'textarea',
			'max'         => 400,
			'rows'        => 4,
			'label'       => 'Outcome pillars',
			'group'       => 'results',
			'limit'       => 4,
			'help'        => 'One per line, at most 4.',
			'placeholder' => "Faster discovery\nOne catalogue, two languages",
		),
		'proof'               => array(
			'type'        => 'textarea',
			'max'         => 1200,
			'rows'        => 4,
			'label'       => 'Proof you can check',
			'group'       => 'results',
			'limit'       => 5,
			'help'        => 'One per line, Label | https://url. At most 5. Lines without a valid link are skipped.',
			'placeholder' => 'Live store | https://rehall.com',
		),
		'metric_1_value'      => array(
			'type'  => 'text',
			'max'   => 16,
			'label' => 'Value',
			'group' => 'results',
			'row'   => 1,
			'help'  => 'e.g. 3, Up to 20%, 1:1',
		),
		'metric_1_label'      => array(
			'type'  => 'text',
			'max'   => 90,
			'label' => 'Label',
			'group' => 'results',
			'row'   => 1,
			'help'  => 'What the number counts.',
		),
		'metric_1_source'     => array(
			'type'  => 'text',
			'max'   => 180,
			'label' => 'Source',
			'group' => 'results',
			'row'   => 1,
			'help'  => 'Required: a metric without a source is never shown.',
		),
		'metric_2_value'      => array(
			'type'  => 'text',
			'max'   => 16,
			'label' => 'Value',
			'group' => 'results',
			'row'   => 2,
			'help'  => 'e.g. 3, Up to 20%, 1:1',
		),
		'metric_2_label'      => array(
			'type'  => 'text',
			'max'   => 90,
			'label' => 'Label',
			'group' => 'results',
			'row'   => 2,
			'help'  => 'What the number counts.',
		),
		'metric_2_source'     => array(
			'type'  => 'text',
			'max'   => 180,
			'label' => 'Source',
			'group' => 'results',
			'row'   => 2,
			'help'  => 'Required: a metric without a source is never shown.',
		),
		'metric_3_value'      => array(
			'type'  => 'text',
			'max'   => 16,
			'label' => 'Value',
			'group' => 'results',
			'row'   => 3,
			'help'  => 'e.g. 3, Up to 20%, 1:1',
		),
		'metric_3_label'      => array(
			'type'  => 'text',
			'max'   => 90,
			'label' => 'Label',
			'group' => 'results',
			'row'   => 3,
			'help'  => 'What the number counts.',
		),
		'metric_3_source'     => array(
			'type'  => 'text',
			'max'   => 180,
			'label' => 'Source',
			'group' => 'results',
			'row'   => 3,
			'help'  => 'Required: a metric without a source is never shown.',
		),
		'metric_4_value'      => array(
			'type'  => 'text',
			'max'   => 16,
			'label' => 'Value',
			'group' => 'results',
			'row'   => 4,
			'help'  => 'e.g. 3, Up to 20%, 1:1',
		),
		'metric_4_label'      => array(
			'type'  => 'text',
			'max'   => 90,
			'label' => 'Label',
			'group' => 'results',
			'row'   => 4,
			'help'  => 'What the number counts.',
		),
		'metric_4_source'     => array(
			'type'  => 'text',
			'max'   => 180,
			'label' => 'Source',
			'group' => 'results',
			'row'   => 4,
			'help'  => 'Required: a metric without a source is never shown.',
		),
		'quote_text'          => array(
			'type'  => 'textarea',
			'max'   => 600,
			'rows'  => 3,
			'label' => 'Quote',
			'group' => 'results',
			'help'  => 'A real client quote only. Empty = no quote block.',
		),
		'quote_name'          => array(
			'type'  => 'text',
			'max'   => 80,
			'label' => 'Quote name',
			'group' => 'results',
			'help'  => 'Required for the quote to show.',
		),
		'quote_role'          => array(
			'type'  => 'text',
			'max'   => 80,
			'label' => 'Quote role',
			'group' => 'results',
			'help'  => 'e.g. Founder, Rehall.',
		),
		'quote_source_url'    => array(
			'type'        => 'url',
			'max'         => 0,
			'label'       => 'Quote source link',
			'group'       => 'results',
			'help'        => 'Link to the original review when it came from a platform.',
			'placeholder' => 'https://',
		),

		/* ---- Stack ---- */
		'stack'               => array(
			'type'        => 'textarea',
			'max'         => 2000,
			'rows'        => 10,
			'label'       => 'Stack',
			'group'       => 'stack',
			'limit'       => 12,
			'help'        => 'One per line, Name: what it does here. End a line with | observed for a tool seen on the live site. At most 12.',
			'placeholder' => "Shopify: Store, checkout and markets\nKlaviyo: Email sign-up and flows | observed",
		),
	);

	/** Per-request caches. */
	private static $cache      = array();
	private static $ordered    = null;
	private static $spotlights = array();
	private static $index_id   = null;

	/* ------------------------------------------------------------------ */
	/* Fields                                                             */
	/* ------------------------------------------------------------------ */

	/**
	 * Meta key for a FIELDS key: "client" → "avix_cs_client".
	 */
	public static function meta_key( string $key ): string {
		return self::PREFIX . $key;
	}

	/**
	 * FIELDS key for a meta key, or '' when it is not one of ours.
	 */
	public static function field_key( string $meta_key ): string {
		if ( 0 !== strpos( $meta_key, self::PREFIX ) ) {
			return '';
		}
		$key = substr( $meta_key, strlen( self::PREFIX ) );
		return isset( self::FIELDS[ $key ] ) ? $key : '';
	}

	/**
	 * Fields of one admin group, in order.
	 */
	public static function group_fields( string $group ): array {
		$out = array();
		foreach ( self::FIELDS as $key => $field ) {
			if ( $group === $field['group'] ) {
				$out[ $key ] = $field;
			}
		}
		return $out;
	}

	/**
	 * Sanitises one value through its FIELDS definition. Invalid input becomes
	 * '' (or 0 for images), which the save routines then delete.
	 *
	 * @param string $key   FIELDS key.
	 * @param mixed  $value Raw value.
	 * @return string|int
	 */
	public static function sanitize( string $key, $value ) {
		if ( ! isset( self::FIELDS[ $key ] ) ) {
			return '';
		}
		$field = self::FIELDS[ $key ];

		if ( 'image' === $field['type'] ) {
			if ( is_array( $value ) ) {
				$value = isset( $value['id'] ) ? $value['id'] : 0;
			}
			$id = absint( is_scalar( $value ) ? $value : 0 );
			return ( $id && wp_attachment_is_image( $id ) ) ? $id : 0;
		}

		if ( ! is_scalar( $value ) ) {
			return '';
		}
		$value = str_replace( array( "\r\n", "\r" ), "\n", (string) $value );
		$max   = isset( $field['max'] ) ? (int) $field['max'] : 0;

		switch ( $field['type'] ) {
			case 'textarea':
				$value = self::cap( trim( sanitize_textarea_field( $value ) ), $max );
				break;

			case 'rich':
				$value = self::clean_rich( $value, $max );
				break;

			case 'url':
				$value = self::clean_url( $value );
				break;

			case 'color':
				$value = trim( $value );
				if ( '' !== $value && '#' !== $value[0] ) {
					$value = '#' . $value;
				}
				$value = (string) sanitize_hex_color( strtolower( $value ) );
				break;

			case 'year':
				$value = trim( $value );
				$value = preg_match( '/^\d{4}$/', $value ) ? $value : '';
				break;

			case 'select':
				$value = trim( $value );
				$value = isset( $field['options'][ $value ] ) ? $value : '';
				break;

			default:
				$value = self::cap( trim( sanitize_text_field( $value ) ), $max );
		}

		return $value;
	}

	/**
	 * True when a sanitised value means "not set" (deleted on save, so the default applies).
	 *
	 * @param mixed $value Sanitised value.
	 */
	public static function is_empty_value( $value ): bool {
		return '' === $value || 0 === $value || '0' === $value || null === $value || false === $value;
	}

	/**
	 * An http(s) URL, or ''. Done without wp_http_validate_url(), which resolves
	 * DNS on every save and drops valid links when the lookup fails.
	 */
	public static function clean_url( string $url ): string {
		$url = trim( $url );
		if ( '' === $url || preg_match( '/\s/', $url ) ) {
			return '';
		}
		$url   = esc_url_raw( $url, array( 'http', 'https' ) );
		$parts = $url ? wp_parse_url( $url ) : false;
		if ( ! is_array( $parts ) || empty( $parts['host'] ) || empty( $parts['scheme'] ) || ! in_array( strtolower( $parts['scheme'] ), array( 'http', 'https' ), true ) ) {
			return '';
		}
		if ( isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
			return '';
		}
		return $url;
	}

	/**
	 * TAK fields: a/strong/em only, links limited to http(s), mailto, # and
	 * site-relative paths (anything else is unwrapped to its text), then capped
	 * without leaving half a tag behind.
	 */
	public static function clean_rich( string $value, int $max = 0 ): string {
		$value = (string) preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', trim( $value ) );
		$value = wp_kses( $value, self::RICH_TAGS );
		$value = (string) preg_replace_callback(
			'#<a\b([^>]*)>(.*?)</a>#is',
			static function ( $m ) {
				if ( preg_match( '#\bhref\s*=\s*(["\'])(.*?)\1#is', $m[1], $href ) && preg_match( '#^(https?://|mailto:|/|\#)#i', trim( $href[2] ) ) ) {
					return $m[0];
				}
				return $m[2];
			},
			$value
		);
		if ( $max > 0 && self::length( $value ) > $max ) {
			$value = self::cap( $value, $max );
			$value = (string) preg_replace( '/<[^>]*$/', '', $value );
		}
		return trim( force_balance_tags( $value ) );
	}

	private static function length( string $value ): int {
		return function_exists( 'mb_strlen' ) ? (int) mb_strlen( $value, 'UTF-8' ) : strlen( $value );
	}

	/**
	 * Multibyte-safe length cap.
	 */
	private static function cap( string $value, int $max ): string {
		if ( $max <= 0 ) {
			return $value;
		}
		if ( function_exists( 'mb_substr' ) ) {
			return mb_strlen( $value, 'UTF-8' ) > $max ? rtrim( mb_substr( $value, 0, $max, 'UTF-8' ) ) : $value;
		}
		return strlen( $value ) > $max ? rtrim( substr( $value, 0, $max ) ) : $value;
	}

	/* ------------------------------------------------------------------ */
	/* Read API                                                           */
	/* ------------------------------------------------------------------ */

	/**
	 * Everything a widget needs about one case study, parsed. Cached per request.
	 * Returns array() when $id is not a published (or, for editors, previewable) case study.
	 */
	public static function get( int $id ): array {
		if ( $id <= 0 ) {
			return array();
		}
		if ( isset( self::$cache[ $id ] ) ) {
			return self::$cache[ $id ];
		}

		$post = get_post( $id );
		if ( ! $post || self::POST_TYPE !== $post->post_type || ! self::viewable( $post ) ) {
			self::$cache[ $id ] = array();
			return array();
		}

		$d = array(
			'id'             => $id,
			'title'          => self::plain( apply_filters( 'the_title', $post->post_title, $id ) ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core hook.
			'excerpt'        => self::plain( $post->post_excerpt ),
			'permalink'      => (string) get_permalink( $post ),
			'thumbnail_id'   => (int) get_post_thumbnail_id( $post ),
			'status'         => (string) $post->post_status,
			'menu_order'     => (int) $post->menu_order,
			'date_published' => (string) get_post_time( 'c', true, $post ),
			'date_modified'  => (string) get_post_modified_time( 'c', true, $post ),
		);

		foreach ( self::FIELDS as $key => $field ) {
			$raw = get_post_meta( $id, self::meta_key( $key ), true );
			if ( 'image' === $field['type'] ) {
				$aid       = absint( is_scalar( $raw ) ? $raw : 0 );
				$d[ $key ] = ( $aid && wp_attachment_is_image( $aid ) ) ? $aid : 0;
				continue;
			}
			$d[ $key ] = is_scalar( $raw ) ? trim( (string) $raw ) : '';
		}

		// Defaults and fallbacks.
		if ( '' === $d['header'] || ! isset( self::FIELDS['header']['options'][ $d['header'] ] ) ) {
			$d['header'] = self::FIELDS['header']['default'];
		}
		$d['accent']   = (string) sanitize_hex_color( $d['accent'] );
		$d['year']     = preg_match( '/^\d{4}$/', $d['year'] ) ? $d['year'] : '';
		$d['live_url'] = self::clean_url( $d['live_url'] );
		if ( '' === $d['live_label'] ) {
			$d['live_label'] = self::domain( $d['live_url'] );
		}
		$d['quote_source_url'] = self::clean_url( $d['quote_source_url'] );

		$d['display_title_plain'] = '' !== $d['display_title'] ? self::strip_accent( $d['display_title'] ) : $d['title'];
		if ( ! $d['hero_desktop'] ) {
			$d['hero_desktop'] = $d['thumbnail_id'];
		}
		$d['card_image'] = $d['hero_render'] ? $d['hero_render'] : ( $d['thumbnail_id'] ? $d['thumbnail_id'] : $d['hero_desktop'] );

		// Lists.
		$d['card_tags_list']  = self::commas( $d['card_tags'] );
		$d['services']        = self::commas( $d['services'] );
		$d['constraints']     = array_slice( self::commas( $d['constraints'] ), 0, 6 );
		$d['implementations'] = array_slice( self::pairs( $d['implementations'], ':' ), 0, 6 );
		$d['outcome_pillars'] = array_slice( self::lines( $d['outcome_pillars'] ), 0, 4 );

		$proof = array();
		foreach ( self::pairs( $d['proof'], '|' ) as $row ) {
			$url = self::clean_url( $row[1] );
			if ( '' !== $row[0] && '' !== $url ) {
				$proof[] = array( $row[0], $url );
			}
		}
		$d['proof'] = array_slice( $proof, 0, 5 );

		$stack = array();
		foreach ( self::lines( $d['stack'] ) as $line ) {
			$observed = false;
			if ( preg_match( '/\|\s*observed\s*$/i', $line ) ) {
				$observed = true;
				$line     = trim( preg_replace( '/\|\s*observed\s*$/i', '', $line ) );
			}
			$pair = self::pairs( $line, ':' );
			if ( $pair && '' !== $pair[0][0] ) {
				$stack[] = array( $pair[0][0], $pair[0][1], $observed );
			}
		}
		$d['stack'] = array_slice( $stack, 0, 12 );

		foreach ( array( 'challenge_body', 'approach_body', 'solution_body', 'outcome_body' ) as $key ) {
			$paras = array();
			foreach ( self::paras( $d[ $key ] ) as $para ) {
				$para = self::clean_rich( $para );
				if ( '' !== $para ) {
					$paras[] = preg_replace( '/\s*\n\s*/', "<br>\n", $para );
				}
			}
			$d[ $key ] = $paras;
		}

		// Metrics: only complete rows (a number without a source is never shown).
		$d['metrics'] = array();
		for ( $n = 1; $n <= 4; $n++ ) {
			$value  = $d[ 'metric_' . $n . '_value' ];
			$label  = $d[ 'metric_' . $n . '_label' ];
			$source = $d[ 'metric_' . $n . '_source' ];
			if ( '' !== $value && '' !== $label && '' !== $source ) {
				$d['metrics'][] = array( $value, $label, $source );
			}
		}

		$d['quote'] = ( '' !== $d['quote_text'] && '' !== $d['quote_name'] )
			? array(
				'text'       => $d['quote_text'],
				'name'       => $d['quote_name'],
				'role'       => $d['quote_role'],
				'source_url' => $d['quote_source_url'],
			)
			: array();

		$d['terms'] = array(
			'service'  => self::service_terms( $id ),
			'industry' => self::industry_terms( $id ),
		);

		self::$cache[ $id ] = $d;
		return $d;
	}

	/**
	 * Which case study a widget shows (§1.4): the picked one, else this page's,
	 * else (editor only) the newest, else 0.
	 */
	public static function current_id( string $picked = '' ): int {
		$picked = absint( $picked );
		if ( $picked && self::is_case_study( $picked, true ) ) {
			return $picked;
		}

		$editor = self::is_editor();

		if ( ! is_admin() || wp_doing_ajax() ) {
			$queried = get_queried_object();
			if ( $queried instanceof \WP_Post && self::POST_TYPE === $queried->post_type ) {
				return (int) $queried->ID;
			}
		}

		if ( $editor ) {
			$doc_id = self::editor_post_id();
			if ( $doc_id && self::is_case_study( $doc_id, false ) ) {
				return $doc_id;
			}
		}

		$post = get_post();
		if ( $post && self::POST_TYPE === $post->post_type && ( 'publish' === $post->post_status || current_user_can( 'edit_post', $post->ID ) ) ) {
			return (int) $post->ID;
		}

		if ( $editor ) {
			$ids = self::ordered_ids();
			if ( $ids ) {
				$newest = get_posts(
					array(
						'post_type'        => self::POST_TYPE,
						'post_status'      => 'publish',
						'posts_per_page'   => 1,
						'orderby'          => 'date',
						'order'            => 'DESC',
						'fields'           => 'ids',
						'no_found_rows'    => true,
						'suppress_filters' => true,
					)
				);
				return $newest ? (int) $newest[0] : (int) $ids[0];
			}
		}

		return 0;
	}

	/**
	 * Elementor edit or preview mode (including the editor's AJAX renders).
	 */
	public static function is_editor(): bool {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! isset( \Elementor\Plugin::$instance ) ) {
			return false;
		}
		$plugin = \Elementor\Plugin::$instance;
		if ( isset( $plugin->editor ) && is_object( $plugin->editor ) && method_exists( $plugin->editor, 'is_edit_mode' ) && $plugin->editor->is_edit_mode() ) {
			return true;
		}
		if ( isset( $plugin->preview ) && is_object( $plugin->preview ) && method_exists( $plugin->preview, 'is_preview_mode' ) && $plugin->preview->is_preview_mode() ) {
			return true;
		}
		return false;
	}

	/**
	 * Published IDs in display order: menu_order ASC, then newest first.
	 */
	public static function ordered_ids(): array {
		if ( null === self::$ordered ) {
			self::$ordered = array_map(
				'intval',
				get_posts(
					array(
						'post_type'        => self::POST_TYPE,
						'post_status'      => 'publish',
						'posts_per_page'   => 200,
						'orderby'          => array(
							'menu_order' => 'ASC',
							'date'       => 'DESC',
						),
						'fields'           => 'ids',
						'no_found_rows'    => true,
						'suppress_filters' => true,
					)
				)
			);
		}
		return self::$ordered;
	}

	/**
	 * Previous and next published case study, looping around. Both 0 when there
	 * are fewer than 2. A draft being previewed gets the first/last as neighbours.
	 */
	public static function neighbours( int $id ): array {
		$ids = self::ordered_ids();
		$pos = array_search( $id, $ids, true );
		if ( false === $pos ) {
			if ( ! $ids ) {
				return array(
					'prev' => 0,
					'next' => 0,
				);
			}
			$last = $ids[ count( $ids ) - 1 ];
			return array(
				'prev' => $last,
				'next' => $ids[0],
			);
		}
		$count = count( $ids );
		if ( $count < 2 ) {
			return array(
				'prev' => 0,
				'next' => 0,
			);
		}
		return array(
			'prev' => $ids[ ( $pos - 1 + $count ) % $count ],
			'next' => $ids[ ( $pos + 1 ) % $count ],
		);
	}

	/**
	 * The /case-studies/ index page: option avix_cs_index_page, else the page at /case-studies/.
	 */
	public static function index_page_id(): int {
		if ( null !== self::$index_id ) {
			return self::$index_id;
		}
		$id   = absint( get_option( 'avix_cs_index_page', 0 ) );
		$page = $id ? get_post( $id ) : null;
		if ( ! $page || 'page' !== $page->post_type || in_array( $page->post_status, array( 'trash', 'auto-draft' ), true ) ) {
			$page = get_page_by_path( 'case-studies', OBJECT, 'page' );
			if ( $page && in_array( $page->post_status, array( 'trash', 'auto-draft' ), true ) ) {
				$page = null;
			}
		}
		self::$index_id = $page ? (int) $page->ID : 0;
		return self::$index_id;
	}

	/**
	 * Permalink of the index page, else /case-studies/.
	 */
	public static function index_url(): string {
		$id = self::index_page_id();
		if ( $id && 'publish' === get_post_status( $id ) ) {
			return (string) get_permalink( $id );
		}
		return home_url( '/case-studies/' );
	}

	/**
	 * Title of the index page, else "Case studies".
	 */
	public static function index_title(): string {
		$id    = self::index_page_id();
		$title = $id ? self::plain( get_the_title( $id ) ) : '';
		return '' !== $title ? $title : __( 'Case studies', 'avix-widgets' );
	}

	/**
	 * Spotlight widgets of a post in document order: [ id, title, tag ]. Cached per request.
	 */
	public static function spotlights( int $post_id ): array {
		if ( $post_id <= 0 ) {
			return array();
		}
		if ( isset( self::$spotlights[ $post_id ] ) ) {
			return self::$spotlights[ $post_id ];
		}
		$data = get_post_meta( $post_id, '_elementor_data', true );
		if ( is_string( $data ) && '' !== $data ) {
			$data = json_decode( $data, true );
		}
		$out = array();
		if ( is_array( $data ) ) {
			self::walk_spotlights( $data, $out );
		}
		self::$spotlights[ $post_id ] = $out;
		return $out;
	}

	/**
	 * The Style > Theme of the first Case Study Hero in the post's Elementor
	 * layout: 'light' or 'dark', or '' when the layout has no hero. Read fresh
	 * (no cache): it runs right after Elementor saves the layout. The header
	 * follows it (Case_Studies::header_tone()).
	 */
	public static function hero_theme( int $post_id ): string {
		if ( $post_id <= 0 ) {
			return '';
		}
		$data = get_post_meta( $post_id, '_elementor_data', true );
		if ( is_string( $data ) && '' !== $data ) {
			$data = json_decode( $data, true );
		}
		if ( ! is_array( $data ) ) {
			return '';
		}
		$hero = self::find_widget( $data, 'avix-case-study-hero' );
		if ( null === $hero ) {
			return '';
		}
		// Elementor saves only the settings that differ from the control default.
		$theme = isset( $hero['theme'] ) && is_string( $hero['theme'] ) && '' !== $hero['theme'] ? $hero['theme'] : self::control_default( 'avix-case-study-hero', 'theme', 'dark' );
		return 'light' === $theme ? 'light' : 'dark';
	}

	/**
	 * Settings of the first widget of $type in document order, or null.
	 *
	 * @param array  $elements Elementor elements.
	 * @param string $type     widgetType.
	 */
	private static function find_widget( array $elements, string $type ) {
		foreach ( $elements as $el ) {
			if ( ! is_array( $el ) ) {
				continue;
			}
			if ( isset( $el['widgetType'] ) && $type === $el['widgetType'] ) {
				return isset( $el['settings'] ) && is_array( $el['settings'] ) ? $el['settings'] : array();
			}
			if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
				$found = self::find_widget( $el['elements'], $type );
				if ( null !== $found ) {
					return $found;
				}
			}
		}
		return null;
	}

	/**
	 * A widget control's default from Elementor's registry, else $fallback.
	 */
	private static function control_default( string $widget, string $control, string $fallback ): string {
		if ( ! did_action( 'init' ) || ! class_exists( '\Elementor\Plugin' ) || ! isset( \Elementor\Plugin::$instance ) || ! isset( \Elementor\Plugin::$instance->widgets_manager ) ) {
			return $fallback;
		}
		try {
			$type = \Elementor\Plugin::$instance->widgets_manager->get_widget_types( $widget );
			if ( is_object( $type ) && method_exists( $type, 'get_controls' ) ) {
				$c = $type->get_controls( $control );
				if ( is_array( $c ) && isset( $c['default'] ) && is_string( $c['default'] ) && '' !== $c['default'] ) {
					return $c['default'];
				}
			}
		} catch ( \Throwable $error ) {
			return $fallback;
		}
		return $fallback;
	}

	/**
	 * Depth-first walk of Elementor elements, collecting spotlights.
	 */
	private static function walk_spotlights( array $elements, array &$out ): void {
		foreach ( $elements as $el ) {
			if ( ! is_array( $el ) ) {
				continue;
			}
			if ( isset( $el['widgetType'] ) && 'avix-case-study-spotlight' === $el['widgetType'] && ! empty( $el['id'] ) ) {
				$settings = isset( $el['settings'] ) && is_array( $el['settings'] ) ? $el['settings'] : array();
				$out[]    = array(
					'id'    => (string) $el['id'],
					'title' => isset( $settings['title'] ) && is_string( $settings['title'] ) ? self::strip_accent( self::plain( $settings['title'] ) ) : '',
					'tag'   => isset( $settings['tag'] ) && is_string( $settings['tag'] ) ? self::plain( $settings['tag'] ) : '',
				);
			}
			if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
				self::walk_spotlights( $el['elements'], $out );
			}
		}
	}

	/**
	 * "https://www.flowstorage.nl/x" → "flowstorage.nl".
	 */
	public static function domain( string $url ): string {
		$url = trim( $url );
		if ( '' === $url ) {
			return '';
		}
		if ( ! preg_match( '#^[a-z][a-z0-9+.-]*://#i', $url ) ) {
			$url = 'https://' . ltrim( $url, '/' );
		}
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! is_string( $host ) || '' === $host ) {
			return '';
		}
		return preg_replace( '/^www\./i', '', strtolower( $host ) );
	}

	/**
	 * Escapes first, then turns [x] into <span class="$class">x</span>.
	 */
	public static function accent_html( string $text, string $class ): string {
		return (string) preg_replace( '/\[([^\[\]]+)\]/u', '<span class="' . esc_attr( $class ) . '">$1</span>', esc_html( $text ) );
	}

	/**
	 * Removes [accent] brackets, keeping the words.
	 */
	public static function strip_accent( string $text ): string {
		return trim( (string) preg_replace( '/\[([^\[\]]+)\]/u', '$1', $text ) );
	}

	/**
	 * Paragraphs: split on blank lines, trimmed, empties dropped.
	 */
	public static function paras( string $text ): array {
		$text = str_replace( array( "\r\n", "\r" ), "\n", $text );
		$out  = array();
		foreach ( preg_split( '/\n[ \t]*\n/', $text ) as $para ) {
			$para = trim( $para );
			if ( '' !== $para ) {
				$out[] = $para;
			}
		}
		return $out;
	}

	/**
	 * Lines: split on newlines, trimmed, empties dropped.
	 */
	public static function lines( string $text ): array {
		$text = str_replace( array( "\r\n", "\r" ), "\n", $text );
		$out  = array();
		foreach ( explode( "\n", $text ) as $line ) {
			$line = trim( $line );
			if ( '' !== $line ) {
				$out[] = $line;
			}
		}
		return $out;
	}

	/**
	 * "a: b" lines → array( array( 'a', 'b' ) ), split on the first $sep. A line without $sep gives array( line, '' ).
	 */
	public static function pairs( string $text, string $sep ): array {
		$out = array();
		if ( '' === $sep ) {
			return $out;
		}
		foreach ( self::lines( $text ) as $line ) {
			$at = strpos( $line, $sep );
			if ( false === $at ) {
				$out[] = array( $line, '' );
				continue;
			}
			$out[] = array( trim( substr( $line, 0, $at ) ), trim( substr( $line, $at + strlen( $sep ) ) ) );
		}
		return $out;
	}

	/**
	 * Comma list → trimmed, de-duplicated strings.
	 */
	public static function commas( string $text ): array {
		$out = array();
		foreach ( explode( ',', str_replace( "\n", ',', $text ) ) as $item ) {
			$item = trim( $item );
			if ( '' !== $item && ! in_array( $item, $out, true ) ) {
				$out[] = $item;
			}
		}
		return $out;
	}

	/**
	 * Clears the per-request caches (after a save or an import in the same request).
	 */
	public static function flush( int $id = 0 ): void {
		if ( $id ) {
			unset( self::$cache[ $id ], self::$spotlights[ $id ] );
		} else {
			self::$cache      = array();
			self::$spotlights = array();
		}
		self::$ordered  = null;
		self::$index_id = null;
	}

	/* ------------------------------------------------------------------ */
	/* Helpers                                                            */
	/* ------------------------------------------------------------------ */

	/**
	 * True for a case study the current visitor may see: published, or any
	 * non-trashed status for someone who can edit it (preview, editor).
	 */
	private static function viewable( \WP_Post $post ): bool {
		if ( 'publish' === $post->post_status ) {
			if ( post_password_required( $post ) && ! current_user_can( 'edit_post', $post->ID ) ) {
				return false;
			}
			return true;
		}
		if ( 'trash' === $post->post_status ) {
			return false;
		}
		return current_user_can( 'edit_post', $post->ID );
	}

	/**
	 * Case-study post check for current_id().
	 */
	private static function is_case_study( int $id, bool $published_only ): bool {
		$post = get_post( $id );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return false;
		}
		if ( 'publish' === $post->post_status ) {
			return true;
		}
		return ! $published_only && 'trash' !== $post->post_status && current_user_can( 'edit_post', $id );
	}

	/**
	 * The post being edited or previewed in Elementor.
	 */
	private static function editor_post_id(): int {
		$id = 0;
		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->documents ) && is_object( \Elementor\Plugin::$instance->documents ) ) {
			$doc = \Elementor\Plugin::$instance->documents->get_current();
			if ( $doc && method_exists( $doc, 'get_main_id' ) ) {
				$id = (int) $doc->get_main_id();
			}
		}
		if ( ! $id ) {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only context detection.
			foreach ( array( 'editor_post_id', 'elementor-preview', 'post', 'initial_document_id' ) as $param ) {
				if ( isset( $_REQUEST[ $param ] ) && is_scalar( $_REQUEST[ $param ] ) ) {
					$id = absint( $_REQUEST[ $param ] );
					if ( $id ) {
						break;
					}
				}
			}
			// phpcs:enable
		}
		return $id;
	}

	/**
	 * Service terms with their linked service page.
	 */
	private static function service_terms( int $id ): array {
		$terms = get_the_terms( $id, self::TAX_SERVICE );
		$out   = array();
		if ( ! is_array( $terms ) ) {
			return $out;
		}
		foreach ( $terms as $term ) {
			$page = absint( get_term_meta( $term->term_id, self::SERVICE_PAGE_META, true ) );
			$link = ( $page && 'publish' === get_post_status( $page ) ) ? (string) get_permalink( $page ) : '';
			$out[] = array( self::plain( $term->name ), $term->slug, $link );
		}
		return $out;
	}

	/**
	 * Industry terms.
	 */
	private static function industry_terms( int $id ): array {
		$terms = get_the_terms( $id, self::TAX_INDUSTRY );
		$out   = array();
		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$out[] = array( self::plain( $term->name ), $term->slug );
			}
		}
		return $out;
	}

	/**
	 * Plain text: tags stripped, entities decoded, whitespace collapsed.
	 *
	 * @param mixed $text Text.
	 */
	public static function plain( $text ): string {
		$text = html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES, 'UTF-8' );
		return trim( (string) preg_replace( '/[ \t]+/', ' ', $text ) );
	}
}
