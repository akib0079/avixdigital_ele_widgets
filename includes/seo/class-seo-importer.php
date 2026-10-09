<?php
/**
 * SEO data importer (plan task A6): Tools > Avix SEO: data.
 *
 * Reads includes/seo/data/seo-pages.json (built from the strategy keyword map,
 * the rows whose writer is the SEO importer) and writes each row's Yoast fields:
 * SEO title, meta description, focus keyphrase, breadcrumb title, schema page
 * type and the Open Graph image (URL and attachment ID, resolved from
 * "@media:<file name>" like the case-study importer does).
 *
 *  - The screen is a dry run: current value and new value per field. Nothing is
 *    written until Apply is pressed.
 *  - A row is skipped when its post ID no longer lives at the expected URL path,
 *    is not a published page or post, or is a case study (those get their Yoast
 *    fields from includes/case-studies/data/*.json).
 *  - A row with post_id 0 is matched by its expected path instead (a blog post
 *    that is not published yet can be listed before it exists). Until a published
 *    page or post lives at that path the row is reported and skipped.
 *  - A row flagged "requires" (a rewrite or an owner decision first) stays off
 *    until its own checkbox is ticked.
 *  - An Open Graph file that is not in the Media Library is reported and that
 *    field is skipped; the rest of the row still applies.
 *  - Apply first stores every value it is about to replace in an option
 *    avix_seo_backup_<unix time> (the last five are kept), then writes, then
 *    has Yoast rebuild the indexable of each post so the new head shows at once.
 *  - Restore puts a chosen backup back (after backing up what it replaces, so a
 *    restore can be undone too).
 *
 * Admin only: capability manage_options, a nonce on every action, no front-end
 * output. Other SEO tools can add a tab to the same screen through the
 * avix_seo_admin_tabs filter and the avix_seo_admin_tab_<slug> action.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\SEO;

defined( 'ABSPATH' ) || exit;

final class SEO_Importer {

	/** Tools page slug. */
	const PAGE = 'avix-seo-data';

	/** Who may see and run the importer. */
	const CAP = 'manage_options';

	/** admin-post.php actions (also the nonce actions). */
	const ACTION_APPLY   = 'avix_seo_apply';
	const ACTION_RESTORE = 'avix_seo_restore';

	/** Backup option name prefix, followed by the Unix time of the backup. */
	const BACKUP_PREFIX = 'avix_seo_backup_';

	/** How many backups are kept. */
	const KEEP = 5;

	/** Per-user transient holding the last apply / restore report. */
	const REPORT = 'avix_seo_report_';

	/** The data file, relative to this folder. */
	const DATA_FILE = 'data/seo-pages.json';

	/** Row field => Yoast post meta key, in the order the screen lists them. */
	const FIELDS = array(
		'title'            => '_yoast_wpseo_title',
		'metadesc'         => '_yoast_wpseo_metadesc',
		'focuskw'          => '_yoast_wpseo_focuskw',
		'bctitle'          => '_yoast_wpseo_bctitle',
		'schema_page_type' => '_yoast_wpseo_schema_page_type',
		'og_image'         => '_yoast_wpseo_opengraph-image',
		'og_image_id'      => '_yoast_wpseo_opengraph-image-id',
	);

	/** Text fields a data row may set (og_image is resolved separately). */
	const TEXT_FIELDS = array( 'title', 'metadesc', 'focuskw', 'bctitle', 'schema_page_type' );

	/** Yoast's schema page types (Search Appearance > Schema > Page type). */
	const PAGE_TYPES = array( 'WebPage', 'ItemPage', 'AboutPage', 'FAQPage', 'QAPage', 'ProfilePage', 'ContactPage', 'MedicalWebPage', 'CollectionPage', 'CheckoutPage', 'RealEstateListing', 'SearchResultsPage' );

	/** Post types the importer may write. Never the case studies. */
	const POST_TYPES = array( 'page', 'post' );

	/** The case-study post type, refused even if a data row names one. */
	const CASE_STUDY_TYPE = 'avix_case_study';

	public static function init(): void {
		if ( ! is_admin() ) {
			return;
		}
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_' . self::ACTION_APPLY, array( __CLASS__, 'handle_apply' ) );
		add_action( 'admin_post_' . self::ACTION_RESTORE, array( __CLASS__, 'handle_restore' ) );
	}

	/* ------------------------------------------------------------------ */
	/* Pure helpers (no WordPress calls; covered by tests/seo-importer.php) */
	/* ------------------------------------------------------------------ */

	/**
	 * A URL path in one comparable form: leading and trailing slash, no query or
	 * fragment, no repeated slashes, lower case, percent-decoding undone.
	 * "service/web-development" and "/Service/web-development/?x=1" both give
	 * "/service/web-development/"; "" gives "/".
	 *
	 * @param string $path Path or path with query.
	 */
	public static function normalize_path( string $path ): string {
		$path = trim( $path );
		foreach ( array( '#', '?' ) as $cut ) {
			$at = strpos( $path, $cut );
			if ( false !== $at ) {
				$path = substr( $path, 0, $at );
			}
		}
		$path = strtolower( rawurldecode( $path ) );
		$path = (string) preg_replace( '#/+#', '/', '/' . trim( $path, '/' ) . '/' );
		return '/' === $path ? '/' : $path;
	}

	/**
	 * The path of $url relative to the site root $home_url, normalised. A site in
	 * a sub-folder ("https://x.test/wp/") gives "/about/" for "https://x.test/wp/about/".
	 *
	 * @param string $url      Absolute or root-relative URL.
	 * @param string $home_url home_url( '/' ).
	 */
	public static function relative_path( string $url, string $home_url ): string {
		$path = (string) parse_url( $url, PHP_URL_PATH ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- pure helper, tested without WordPress.
		$base = rtrim( (string) parse_url( $home_url, PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- as above.
		if ( '' !== $base && 0 === stripos( $path, $base . '/' ) ) {
			$path = substr( $path, strlen( $base ) );
		} elseif ( '' !== $base && 0 === strcasecmp( $path, $base ) ) {
			$path = '/';
		}
		return self::normalize_path( $path );
	}

	/**
	 * Field-by-field comparison. Only fields the row sets (non-null in $wanted)
	 * are listed; null means "leave as it is". Values are compared as exact strings.
	 *
	 * @param array $current field => current value (string, '' when unset).
	 * @param array $wanted  field => new value (string) or null.
	 * @return array field => array( 'current' => string, 'new' => string, 'change' => bool ), in FIELDS order.
	 */
	public static function field_diff( array $current, array $wanted ): array {
		$out = array();
		foreach ( array_keys( self::FIELDS ) as $field ) {
			if ( ! array_key_exists( $field, $wanted ) || null === $wanted[ $field ] ) {
				continue;
			}
			$old           = isset( $current[ $field ] ) && is_scalar( $current[ $field ] ) ? (string) $current[ $field ] : '';
			$new           = (string) $wanted[ $field ];
			$out[ $field ] = array(
				'current' => $old,
				'new'     => $new,
				'change'  => $old !== $new,
			);
		}
		return $out;
	}

	/**
	 * Decides what happens to one (already sanitised) data row, given the post as it is now.
	 *
	 * @param array $row   Data row: post_id, post_type, path, the TEXT_FIELDS, og_image (file name or null), requires, requires_note.
	 * @param array $state The post now: exists (bool), post_id (the post state() found: for a row with post_id 0 the
	 *                     one at the row's path), post_type, status, path (normalised), title, meta (field => value),
	 *                     media (array( 'id' => int, 'url' => string ) for the row's og_image, or array() when not found).
	 * @return array {
	 *     @type int    $post_id       The row's post ID, or for a post_id 0 row the post found at its path (0 = none).
	 *     @type bool   $by_path       True when the post was found by the row's path (post_id 0).
	 *     @type string $path          Expected path.
	 *     @type string $title         Post title (for the screen).
	 *     @type string $status        ready | held | unchanged | skip
	 *     @type string $reason        Why a row is skipped.
	 *     @type array  $fields        field_diff() result.
	 *     @type array  $notes         Field-level notes (an Open Graph file that is not uploaded).
	 *     @type string $requires      The row's hold flag ('' when none).
	 *     @type string $requires_note What has to happen first.
	 * }
	 */
	public static function evaluate( array $row, array $state ): array {
		$out = array(
			'post_id'       => isset( $row['post_id'] ) ? (int) $row['post_id'] : 0,
			'by_path'       => false,
			'path'          => isset( $row['path'] ) && '' !== trim( (string) $row['path'] ) ? self::normalize_path( (string) $row['path'] ) : '',
			'title'         => isset( $state['title'] ) ? (string) $state['title'] : '',
			'status'        => 'skip',
			'reason'        => '',
			'fields'        => array(),
			'notes'         => array(),
			'requires'      => isset( $row['requires'] ) && is_string( $row['requires'] ) ? $row['requires'] : '',
			'requires_note' => isset( $row['requires_note'] ) && is_string( $row['requires_note'] ) ? $row['requires_note'] : '',
		);

		if ( '' === $out['path'] ) {
			$out['reason'] = 'The data row has no path.';
			return $out;
		}
		if ( $out['post_id'] <= 0 ) {
			// post_id 0: the post is the one state() found at the row's path.
			$out['by_path'] = true;
			$out['post_id'] = ! empty( $state['exists'] ) && isset( $state['post_id'] ) ? max( 0, (int) $state['post_id'] ) : 0;
			if ( ! $out['post_id'] ) {
				$out['reason'] = 'No page or post at ' . $out['path'] . ' yet (the row has post ID 0, so it is matched by its path): skipped until it is published.';
				return $out;
			}
		}
		if ( empty( $state['exists'] ) ) {
			$out['reason'] = 'No post with this ID.';
			return $out;
		}
		$type = isset( $state['post_type'] ) ? (string) $state['post_type'] : '';
		if ( self::CASE_STUDY_TYPE === $type || ! in_array( $type, self::POST_TYPES, true ) ) {
			$out['reason'] = 'Post type "' . $type . '" is never written by this importer.';
			return $out;
		}
		if ( ! empty( $row['post_type'] ) && $row['post_type'] !== $type ) {
			$out['reason'] = 'Expected a ' . $row['post_type'] . ', found a ' . $type . '.';
			return $out;
		}
		$status = isset( $state['status'] ) ? (string) $state['status'] : '';
		if ( 'publish' !== $status ) {
			$out['reason'] = 'Not published (status: ' . ( '' !== $status ? $status : 'unknown' ) . ').';
			return $out;
		}
		$actual = isset( $state['path'] ) ? self::normalize_path( (string) $state['path'] ) : '';
		if ( $actual !== $out['path'] ) {
			$out['reason'] = 'Path guard: expected ' . $out['path'] . ', the post is at ' . $actual . '.';
			return $out;
		}

		$wanted = array();
		foreach ( self::TEXT_FIELDS as $field ) {
			$value            = isset( $row[ $field ] ) && is_string( $row[ $field ] ) ? trim( $row[ $field ] ) : '';
			$wanted[ $field ] = '' !== $value ? $value : null;
		}
		$og = isset( $row['og_image'] ) && is_string( $row['og_image'] ) ? trim( $row['og_image'] ) : '';
		if ( '' !== $og ) {
			$media = isset( $state['media'] ) && is_array( $state['media'] ) ? $state['media'] : array();
			if ( ! empty( $media['id'] ) && ! empty( $media['url'] ) ) {
				$wanted['og_image']    = (string) $media['url'];
				$wanted['og_image_id'] = (string) (int) $media['id'];
			} else {
				$out['notes'][] = 'Open Graph image @media:' . $og . ' is not in the Media Library: skipped (upload it and run again).';
			}
		}

		$out['fields'] = self::field_diff( isset( $state['meta'] ) && is_array( $state['meta'] ) ? $state['meta'] : array(), $wanted );
		$changes       = array_filter(
			$out['fields'],
			static function ( $d ) {
				return ! empty( $d['change'] );
			}
		);
		if ( ! $changes ) {
			$out['status'] = 'unchanged';
		} else {
			$out['status'] = '' !== $out['requires'] ? 'held' : 'ready';
		}
		return $out;
	}

	/**
	 * Backup option names that fall outside the newest $keep. Names sort by their
	 * timestamp suffix (a same-second "_2" sorts after its base).
	 *
	 * @param string[] $names Backup option names.
	 * @param int      $keep  How many to keep.
	 * @return string[] Names to delete.
	 */
	public static function prune_list( array $names, int $keep ): array {
		$names = array_values( array_unique( array_map( 'strval', $names ) ) );
		usort( $names, array( __CLASS__, 'compare_backup_names' ) );
		return array_slice( $names, max( 0, $keep ) );
	}

	/**
	 * Newest first.
	 *
	 * @param string $a Backup option name.
	 * @param string $b Backup option name.
	 */
	public static function compare_backup_names( string $a, string $b ): int {
		$ka = self::backup_sort_key( $a );
		$kb = self::backup_sort_key( $b );
		if ( $ka === $kb ) {
			return strcmp( $b, $a );
		}
		return $ka < $kb ? 1 : -1;
	}

	/**
	 * array( time, sequence ) of a backup name, for sorting.
	 *
	 * @param string $name Backup option name.
	 */
	private static function backup_sort_key( string $name ): array {
		if ( preg_match( '/^' . preg_quote( self::BACKUP_PREFIX, '/' ) . '(\d+)(?:_(\d+))?$/', $name, $m ) ) {
			return array( (int) $m[1], isset( $m[2] ) ? (int) $m[2] : 1 );
		}
		return array( 0, 0 );
	}

	/**
	 * Sanitises the data rows of seo-pages.json. Unknown keys are dropped; a schema page
	 * type Yoast does not know is dropped with a note; og_image keeps only the file name.
	 *
	 * @param array $data Decoded JSON.
	 * @return array array( 'rows' => array, 'notes' => string[] ).
	 */
	public static function clean_rows( array $data ): array {
		$rows  = array();
		$notes = array();
		$list  = isset( $data['rows'] ) && is_array( $data['rows'] ) ? $data['rows'] : array();
		foreach ( $list as $i => $raw ) {
			if ( ! is_array( $raw ) ) {
				$notes[] = 'Row ' . ( $i + 1 ) . ' is not an object: ignored.';
				continue;
			}
			$row = array(
				'post_id'   => isset( $raw['post_id'] ) && is_numeric( $raw['post_id'] ) ? max( 0, (int) $raw['post_id'] ) : 0,
				'post_type' => isset( $raw['post_type'] ) && is_string( $raw['post_type'] ) ? strtolower( trim( $raw['post_type'] ) ) : '',
				// A blank path stays blank (never "/", the front page): such a row is skipped.
				'path'      => isset( $raw['path'] ) && is_string( $raw['path'] ) && '' !== trim( $raw['path'] ) ? self::normalize_path( $raw['path'] ) : '',
			);
			foreach ( self::TEXT_FIELDS as $field ) {
				$value         = isset( $raw[ $field ] ) && is_string( $raw[ $field ] ) ? self::plain_text( $raw[ $field ] ) : '';
				$row[ $field ] = '' !== $value ? $value : null;
			}
			if ( null !== $row['schema_page_type'] && ! in_array( $row['schema_page_type'], self::PAGE_TYPES, true ) ) {
				$notes[]                 = 'Row ' . ( $i + 1 ) . ': schema page type "' . $row['schema_page_type'] . '" is not a Yoast page type: left out.';
				$row['schema_page_type'] = null;
			}
			$og = isset( $raw['og_image'] ) && is_string( $raw['og_image'] ) ? trim( $raw['og_image'] ) : '';
			if ( 0 === strpos( $og, '@media:' ) ) {
				$og = substr( $og, 7 );
			}
			$og              = basename( str_replace( '\\', '/', $og ) );
			$row['og_image'] = preg_match( '/^[A-Za-z0-9._-]+$/', $og ) ? $og : null;
			if ( '' !== $og && null === $row['og_image'] ) {
				$notes[] = 'Row ' . ( $i + 1 ) . ': Open Graph file name "' . $og . '" is not a plain file name: left out.';
			}
			$row['requires']      = isset( $raw['requires'] ) && is_string( $raw['requires'] ) ? preg_replace( '/[^a-z0-9_-]/', '', strtolower( $raw['requires'] ) ) : '';
			$row['requires_note'] = isset( $raw['requires_note'] ) && is_string( $raw['requires_note'] ) ? self::plain_text( $raw['requires_note'] ) : '';
			if ( '' !== $row['requires'] && '' === $row['requires_note'] ) {
				$row['requires_note'] = 'Held back until it is confirmed.';
			}
			$rows[] = $row;
		}
		return array(
			'rows'  => $rows,
			'notes' => $notes,
		);
	}

	/**
	 * One line of plain text: tags removed, whitespace collapsed (sanitize_text_field()
	 * when WordPress is loaded).
	 *
	 * @param string $text Text.
	 */
	private static function plain_text( string $text ): string {
		if ( function_exists( 'sanitize_text_field' ) ) {
			return trim( sanitize_text_field( $text ) );
		}
		return trim( (string) preg_replace( '/\s+/u', ' ', strip_tags( $text ) ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- fallback outside WordPress (tests).
	}

	/* ------------------------------------------------------------------ */
	/* Data and state                                                      */
	/* ------------------------------------------------------------------ */

	/**
	 * The data file, decoded and cleaned.
	 *
	 * @return array array( 'rows' => array, 'notes' => string[], 'error' => string, 'meta' => array ).
	 */
	public static function data(): array {
		$file = __DIR__ . '/' . self::DATA_FILE;
		$json = is_readable( $file ) ? file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local plugin file.
		$data = $json ? json_decode( $json, true ) : null;
		if ( ! is_array( $data ) ) {
			return array(
				'rows'  => array(),
				'notes' => array(),
				'error' => sprintf( 'The data file %s is missing or not valid JSON.', 'includes/seo/' . self::DATA_FILE ),
				'meta'  => array(),
			);
		}
		$clean = self::clean_rows( $data );
		return array(
			'rows'  => $clean['rows'],
			'notes' => $clean['notes'],
			'error' => '',
			'meta'  => array(
				'generated' => isset( $data['generated'] ) && is_string( $data['generated'] ) ? $data['generated'] : '',
				'source'    => isset( $data['source'] ) && is_string( $data['source'] ) ? $data['source'] : '',
			),
		);
	}

	/**
	 * The post a row points at, as evaluate() needs it.
	 *
	 * @param array $row Clean data row.
	 */
	public static function state( array $row ): array {
		if ( $row['post_id'] > 0 ) {
			$id = (int) $row['post_id'];
		} else {
			$id = '' !== (string) $row['path'] ? self::find_by_path( (string) $row['path'], (string) $row['post_type'] ) : 0;
		}
		$post = $id > 0 ? get_post( $id ) : null;
		if ( ! $post instanceof \WP_Post ) {
			return array( 'exists' => false );
		}
		$meta = array();
		foreach ( self::FIELDS as $field => $key ) {
			$value          = get_post_meta( $post->ID, $key, true );
			$meta[ $field ] = is_scalar( $value ) ? (string) $value : '';
		}
		$link = 'publish' === $post->post_status ? (string) get_permalink( $post ) : '';
		return array(
			'exists'    => true,
			'post_id'   => (int) $post->ID,
			'post_type' => (string) $post->post_type,
			'status'    => (string) $post->post_status,
			'path'      => '' !== $link ? self::relative_path( $link, home_url( '/' ) ) : '',
			'title'     => wp_strip_all_tags( get_the_title( $post ) ),
			'meta'      => $meta,
			'media'     => ! empty( $row['og_image'] ) ? self::resolve_media( (string) $row['og_image'] ) : array(),
		);
	}

	/**
	 * The page or post that lives at a path, for rows with post_id 0: the front page for "/",
	 * else url_to_postid(), else get_page_by_path() (drafts too, so the screen can say it is
	 * not published yet). Only pages and posts, and only the row's post type when it names one.
	 *
	 * @param string $path      Expected path, relative to the site root.
	 * @param string $post_type 'page', 'post' or '' (either).
	 * @return int Post ID, 0 when nothing lives there.
	 */
	public static function find_by_path( string $path, string $post_type = '' ): int {
		$path  = self::normalize_path( $path );
		$types = in_array( $post_type, self::POST_TYPES, true ) ? array( $post_type ) : self::POST_TYPES;
		$ok    = static function ( $id ) use ( $types ) {
			return $id > 0 && in_array( get_post_type( $id ), $types, true );
		};
		if ( '/' === $path ) {
			$front = 'page' === get_option( 'show_on_front' ) ? (int) get_option( 'page_on_front' ) : 0;
			return $ok( $front ) ? $front : 0;
		}
		$id = (int) url_to_postid( home_url( $path ) );
		if ( $ok( $id ) ) {
			return $id;
		}
		foreach ( $types as $type ) {
			$post = get_page_by_path( trim( $path, '/' ), OBJECT, $type );
			if ( $post instanceof \WP_Post && $ok( (int) $post->ID ) ) {
				return (int) $post->ID;
			}
		}
		return 0;
	}

	/**
	 * The dry run for every row. A row that targets a post an earlier row already
	 * targets (e.g. a post_id 0 row whose path is a listed post) is skipped.
	 *
	 * @return array array( 'rows' => evaluated rows, 'notes' => string[], 'error' => string, 'meta' => array ).
	 */
	public static function plan(): array {
		$data = self::data();
		$rows = array();
		$seen = array();
		foreach ( $data['rows'] as $row ) {
			$eval            = self::evaluate( $row, self::state( $row ) );
			$eval['og_file'] = isset( $row['og_image'] ) ? (string) $row['og_image'] : '';
			if ( $eval['post_id'] > 0 && isset( $seen[ $eval['post_id'] ] ) ) {
				$eval['status'] = 'skip';
				$eval['reason'] = 'Another row of the data file already targets this post (' . $seen[ $eval['post_id'] ] . '): skipped.';
				$eval['fields'] = array();
			} elseif ( $eval['post_id'] > 0 ) {
				$seen[ $eval['post_id'] ] = $eval['path'];
			}
			$rows[] = $eval;
		}
		$data['rows'] = $rows;
		return $data;
	}

	/**
	 * An uploaded file by its upload name: the case-study importer's resolver when it is
	 * loaded (same matching rules as the case-study data), else an exact file-name match.
	 *
	 * @param string $name File name, e.g. avixdigital-home-og.jpg.
	 * @return array array( 'id' => int, 'url' => string ), or array() when not found.
	 */
	public static function resolve_media( string $name ): array {
		$name = basename( str_replace( '\\', '/', $name ) );
		if ( '' === $name ) {
			return array();
		}
		$found = array();
		if ( class_exists( '\AvixWidgets\Case_Studies\Importer' ) && method_exists( '\AvixWidgets\Case_Studies\Importer', 'resolve' ) ) {
			$found = \AvixWidgets\Case_Studies\Importer::resolve( '@media:' . $name );
		} else {
			global $wpdb;
			$base = pathinfo( $name, PATHINFO_FILENAME );
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT p.ID, pm.meta_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_type = 'attachment' AND pm.meta_key = '_wp_attached_file' AND ( pm.meta_value = %s OR pm.meta_value LIKE %s ) ORDER BY p.post_date DESC, p.ID DESC LIMIT 20", $name, '%/' . $wpdb->esc_like( $base ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- one lookup per file on an admin screen.
			foreach ( (array) $rows as $r ) {
				if ( preg_match( '/^' . preg_quote( $base, '/' ) . '(?:-\d+)?(?:-scaled)?\.[a-z0-9]+$/i', basename( (string) $r->meta_value ) ) ) {
					$found = array(
						'id'  => (int) $r->ID,
						'url' => (string) wp_get_attachment_url( (int) $r->ID ),
					);
					break;
				}
			}
		}
		if ( empty( $found['id'] ) || empty( $found['url'] ) || ! wp_attachment_is_image( (int) $found['id'] ) ) {
			return array();
		}
		return array(
			'id'  => (int) $found['id'],
			'url' => (string) $found['url'],
		);
	}

	/* ------------------------------------------------------------------ */
	/* Apply and restore                                                   */
	/* ------------------------------------------------------------------ */

	/**
	 * Applies the rows whose post IDs are in $selected (a held row only when it is
	 * selected, which is its checkbox). Backs up first; writes nothing when the
	 * backup cannot be stored.
	 *
	 * @param int[] $selected Post IDs ticked on the screen.
	 * @return array Report: array( 'kind' => 'apply', 'backup' => string, 'rows' => array, 'error' => string ).
	 */
	public static function apply( array $selected ): array {
		$selected = array_values( array_filter( array_map( 'intval', $selected ) ) );
		$plan     = self::plan();
		$report   = array(
			'kind'   => 'apply',
			'time'   => time(),
			'backup' => '',
			'rows'   => array(),
			'error'  => $plan['error'],
		);
		if ( '' !== $plan['error'] ) {
			return $report;
		}

		$todo = array();
		foreach ( $plan['rows'] as $row ) {
			if ( in_array( $row['post_id'], $selected, true ) && in_array( $row['status'], array( 'ready', 'held' ), true ) ) {
				$todo[] = $row;
			}
		}
		if ( ! $todo ) {
			$report['error'] = 'Nothing to apply: no selected row has a change.';
			return $report;
		}

		// 1. Back up every value about to be replaced (all rows of the key: exact restore).
		$snapshot = array();
		foreach ( $todo as $row ) {
			foreach ( $row['fields'] as $field => $d ) {
				if ( $d['change'] ) {
					$key                                 = self::FIELDS[ $field ];
					$snapshot[ $row['post_id'] ][ $key ] = self::meta_values( $row['post_id'], $key );
				}
			}
		}
		$backup = self::save_backup( $snapshot, 'apply' );
		if ( '' === $backup ) {
			$report['error'] = 'The backup could not be stored, so nothing was written.';
			return $report;
		}
		$report['backup'] = $backup;

		// 2. Write, 3. rebuild the Yoast indexable.
		foreach ( $todo as $row ) {
			$written = array();
			foreach ( $row['fields'] as $field => $d ) {
				if ( ! $d['change'] ) {
					continue;
				}
				update_post_meta( $row['post_id'], self::FIELDS[ $field ], wp_slash( $d['new'] ) );
				$written[ $field ] = array( $d['current'], $d['new'] );
			}
			$report['rows'][] = array(
				'post_id' => $row['post_id'],
				'path'    => $row['path'],
				'written' => $written,
				'notes'   => $row['notes'],
				'yoast'   => self::refresh( $row['post_id'] ),
			);
		}
		self::prune_backups();
		return $report;
	}

	/**
	 * Puts a backup back. What it replaces is backed up first (kind "restore").
	 *
	 * @param string $name Backup option name.
	 * @return array Report: array( 'kind' => 'restore', 'from' => string, 'backup' => string, 'rows' => array, 'error' => string ).
	 */
	public static function restore( string $name ): array {
		$report = array(
			'kind'   => 'restore',
			'time'   => time(),
			'from'   => $name,
			'backup' => '',
			'rows'   => array(),
			'error'  => '',
		);
		$data   = self::get_backup( $name );
		if ( ! $data ) {
			$report['error'] = 'That backup does not exist (any more).';
			return $report;
		}

		// Only our own keys on pages and posts that still exist.
		$keys = array_values( self::FIELDS );
		$todo = array();
		foreach ( $data['posts'] as $post_id => $values ) {
			$post_id = (int) $post_id;
			$type    = get_post_type( $post_id );
			if ( ! $type || ! in_array( $type, self::POST_TYPES, true ) || ! is_array( $values ) ) {
				$report['rows'][] = array(
					'post_id' => $post_id,
					'written' => array(),
					'notes'   => array( 'Skipped: the post no longer exists or is not a page or post.' ),
					'yoast'   => '',
				);
				continue;
			}
			foreach ( $values as $key => $list ) {
				if ( in_array( $key, $keys, true ) && is_array( $list ) ) {
					$todo[ $post_id ][ $key ] = array_values( $list );
				}
			}
		}
		if ( ! $todo ) {
			if ( ! $report['rows'] ) {
				$report['error'] = 'That backup holds no field this importer writes.';
			}
			return $report;
		}

		$snapshot = array();
		foreach ( $todo as $post_id => $values ) {
			foreach ( array_keys( $values ) as $key ) {
				$snapshot[ $post_id ][ $key ] = self::meta_values( $post_id, $key );
			}
		}
		if ( $snapshot ) {
			$report['backup'] = self::save_backup( $snapshot, 'restore', $name );
			if ( '' === $report['backup'] ) {
				$report['error'] = 'The current values could not be backed up, so nothing was restored.';
				return $report;
			}
		}

		foreach ( $todo as $post_id => $values ) {
			$written = array();
			foreach ( $values as $key => $list ) {
				$before = self::meta_values( $post_id, $key );
				if ( $before === $list ) {
					continue;
				}
				self::put_meta_values( $post_id, $key, $list );
				$written[ (string) array_search( $key, self::FIELDS, true ) ] = array( self::show_values( $before ), self::show_values( $list ) );
			}
			$link             = get_permalink( $post_id );
			$report['rows'][] = array(
				'post_id' => $post_id,
				'path'    => $link ? self::relative_path( (string) $link, home_url( '/' ) ) : '',
				'written' => $written,
				'notes'   => array(),
				'yoast'   => $written ? self::refresh( $post_id ) : '',
			);
		}
		self::prune_backups();
		return $report;
	}

	/**
	 * Every stored value of a meta key (unslashed, unserialised), in meta_id order.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 */
	private static function meta_values( int $post_id, string $key ): array {
		$values = get_post_meta( $post_id, $key, false );
		return is_array( $values ) ? array_values( $values ) : array();
	}

	/**
	 * Makes a meta key hold exactly $values. A single value is updated in place (its
	 * meta ID survives); anything else is deleted and added again in order.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @param array  $values  Values (unslashed).
	 */
	private static function put_meta_values( int $post_id, string $key, array $values ): void {
		$before = self::meta_values( $post_id, $key );
		if ( ! $values ) {
			delete_post_meta( $post_id, $key );
			return;
		}
		if ( 1 === count( $values ) && count( $before ) <= 1 ) {
			update_post_meta( $post_id, $key, wp_slash( $values[0] ) );
			return;
		}
		delete_post_meta( $post_id, $key );
		foreach ( $values as $value ) {
			add_post_meta( $post_id, $key, wp_slash( $value ) );
		}
	}

	/**
	 * A stored value list as one line for the report.
	 *
	 * @param array $values Meta values.
	 */
	private static function show_values( array $values ): string {
		if ( ! $values ) {
			return '';
		}
		return implode(
			' | ',
			array_map(
				static function ( $v ) {
					return is_scalar( $v ) ? (string) $v : wp_json_encode( $v );
				},
				$values
			)
		);
	}

	/**
	 * Makes Yoast rebuild the post's indexable (its head reads the indexable, not the
	 * meta), and asks LiteSpeed to purge the page. Without Yoast nothing needs rebuilding.
	 *
	 * @param int $post_id Post ID.
	 * @return string yoast | post-update | no-yoast | failed
	 */
	private static function refresh( int $post_id ): string {
		clean_post_cache( $post_id );
		$result = 'no-yoast';
		if ( defined( 'WPSEO_VERSION' ) ) {
			$result = '';
			if ( function_exists( 'YoastSEO' ) ) {
				try {
					$watcher = YoastSEO()->classes->get( 'Yoast\WP\SEO\Integrations\Watchers\Indexable_Post_Watcher' );
					if ( is_object( $watcher ) && method_exists( $watcher, 'build_indexable' ) ) {
						$watcher->build_indexable( $post_id );
						$result = 'yoast';
					}
				} catch ( \Throwable $error ) {
					$result = '';
				}
			}
			if ( '' === $result ) {
				// As the case-study importer does: a post update runs Yoast's own watcher.
				$updated = wp_update_post( array( 'ID' => $post_id ), true );
				$result  = is_wp_error( $updated ) || ! $updated ? 'failed' : 'post-update';
			}
		}
		do_action( 'litespeed_purge_post', $post_id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- LiteSpeed Cache's public API.
		return $result;
	}

	/* ------------------------------------------------------------------ */
	/* Backups                                                             */
	/* ------------------------------------------------------------------ */

	/**
	 * Stores a backup option (not autoloaded).
	 *
	 * @param array  $posts post ID => array( meta key => values ).
	 * @param string $kind  apply | restore.
	 * @param string $from  For a restore: the backup being put back.
	 * @return string Option name, '' on failure.
	 */
	private static function save_backup( array $posts, string $kind, string $from = '' ): string {
		$time = time();
		$name = self::BACKUP_PREFIX . $time;
		for ( $n = 2; false !== get_option( $name, false ) && $n < 100; $n++ ) {
			$name = self::BACKUP_PREFIX . $time . '_' . $n;
		}
		$ok = add_option(
			$name,
			array(
				'version' => 1,
				'time'    => $time,
				'user'    => get_current_user_id(),
				'kind'    => $kind,
				'from'    => $from,
				'posts'   => $posts,
			),
			'',
			false
		);
		return $ok ? $name : '';
	}

	/**
	 * One backup, or array() when it does not exist.
	 *
	 * @param string $name Option name.
	 */
	public static function get_backup( string $name ): array {
		if ( ! preg_match( '/^' . preg_quote( self::BACKUP_PREFIX, '/' ) . '\d+(?:_\d+)?$/', $name ) ) {
			return array();
		}
		$data = get_option( $name, array() );
		if ( ! is_array( $data ) || ! isset( $data['posts'] ) || ! is_array( $data['posts'] ) ) {
			return array();
		}
		return $data;
	}

	/**
	 * Backup option names, newest first.
	 *
	 * @return string[]
	 */
	public static function backups(): array {
		global $wpdb;
		$names = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( self::BACKUP_PREFIX ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- admin screen; options by prefix have no API.
		$names = array_values(
			array_filter(
				array_map( 'strval', (array) $names ),
				static function ( $n ) {
					return (bool) preg_match( '/^' . preg_quote( self::BACKUP_PREFIX, '/' ) . '\d+(?:_\d+)?$/', $n );
				}
			)
		);
		usort( $names, array( __CLASS__, 'compare_backup_names' ) );
		return $names;
	}

	/**
	 * Deletes all but the newest KEEP backups.
	 */
	private static function prune_backups(): void {
		foreach ( self::prune_list( self::backups(), self::KEEP ) as $name ) {
			delete_option( $name );
		}
	}

	/* ------------------------------------------------------------------ */
	/* Admin                                                               */
	/* ------------------------------------------------------------------ */

	public static function menu(): void {
		add_management_page(
			__( 'Avix SEO: data', 'avix-widgets' ),
			__( 'Avix SEO: data', 'avix-widgets' ),
			self::CAP,
			self::PAGE,
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * URL of the screen (optionally a tab).
	 *
	 * @param string $tab  Tab slug.
	 * @param array  $args Extra query args.
	 */
	public static function screen_url( string $tab = '', array $args = array() ): string {
		$args = array_merge( array( 'page' => self::PAGE ), '' !== $tab ? array( 'tab' => $tab ) : array(), $args );
		return add_query_arg( $args, admin_url( 'tools.php' ) );
	}

	public static function handle_apply(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'avix-widgets' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::ACTION_APPLY );
		$selected = isset( $_POST['rows'] ) && is_array( $_POST['rows'] ) ? array_map( 'absint', wp_unslash( $_POST['rows'] ) ) : array();
		$report   = self::apply( $selected );
		set_transient( self::REPORT . get_current_user_id(), $report, 30 * MINUTE_IN_SECONDS );
		wp_safe_redirect( self::screen_url( '', array( 'avix_seo' => 'apply' ) ) );
		exit;
	}

	public static function handle_restore(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'avix-widgets' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::ACTION_RESTORE );
		$name   = isset( $_POST['backup'] ) && is_string( $_POST['backup'] ) ? sanitize_key( wp_unslash( $_POST['backup'] ) ) : '';
		$report = self::restore( $name );
		set_transient( self::REPORT . get_current_user_id(), $report, 30 * MINUTE_IN_SECONDS );
		wp_safe_redirect( self::screen_url( '', array( 'avix_seo' => 'restore' ) ) . '#avix-seo-backups' );
		exit;
	}

	/**
	 * Tabs of the screen. Other SEO tools (the entity data) add theirs here and render
	 * them on the avix_seo_admin_tab_<slug> action.
	 *
	 * @return array slug => label
	 */
	private static function tabs(): array {
		$tabs = apply_filters( 'avix_seo_admin_tabs', array( 'data' => __( 'SEO data', 'avix-widgets' ) ) );
		$tabs = is_array( $tabs ) ? $tabs : array();
		$out  = array( 'data' => __( 'SEO data', 'avix-widgets' ) );
		foreach ( $tabs as $slug => $label ) {
			$slug = sanitize_key( (string) $slug );
			if ( '' !== $slug && is_scalar( $label ) ) {
				$out[ $slug ] = (string) $label;
			}
		}
		return $out;
	}

	public static function render(): void {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		$tabs = self::tabs();
		$tab  = isset( $_GET['tab'] ) && is_string( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'data'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation only.
		$tab  = isset( $tabs[ $tab ] ) ? $tab : 'data';

		echo '<div class="wrap avix-seo">';
		echo '<h1>' . esc_html__( 'Avix SEO: data', 'avix-widgets' ) . '</h1>';
		self::styles();
		if ( count( $tabs ) > 1 ) {
			echo '<nav class="nav-tab-wrapper" aria-label="' . esc_attr__( 'Avix SEO sections', 'avix-widgets' ) . '">';
			foreach ( $tabs as $slug => $label ) {
				echo '<a class="nav-tab' . ( $slug === $tab ? ' nav-tab-active' : '' ) . '" href="' . esc_url( self::screen_url( 'data' === $slug ? '' : $slug ) ) . '"' . ( $slug === $tab ? ' aria-current="page"' : '' ) . '>' . esc_html( $label ) . '</a>';
			}
			echo '</nav>';
		}
		if ( 'data' === $tab ) {
			self::render_data();
		} else {
			do_action( 'avix_seo_admin_tab_' . $tab );
		}
		echo '</div>';
	}

	/**
	 * The dry run, the apply form and the backups.
	 */
	private static function render_data(): void {
		self::render_report();

		$plan = self::plan();
		if ( '' !== $plan['error'] ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $plan['error'] ) . '</p></div>';
			return;
		}

		$counts = array_count_values( wp_list_pluck( $plan['rows'], 'status' ) );
		echo '<p class="avix-seo__lead">';
		echo esc_html__( 'Dry run of includes/seo/data/seo-pages.json: the current Yoast value and the new value of every field each row sets. Nothing is written until you press Apply. Fields a row does not set are left as they are.', 'avix-widgets' );
		if ( ! empty( $plan['meta']['generated'] ) ) {
			/* translators: %s: date. */
			echo ' ' . esc_html( sprintf( __( 'Data generated %s.', 'avix-widgets' ), $plan['meta']['generated'] ) );
		}
		echo '</p>';
		echo '<p class="avix-seo__counts">';
		foreach ( array(
			'ready'     => __( 'ready', 'avix-widgets' ),
			'held'      => __( 'held back', 'avix-widgets' ),
			'unchanged' => __( 'already up to date', 'avix-widgets' ),
			'skip'      => __( 'skipped', 'avix-widgets' ),
		) as $status => $label ) {
			echo '<span class="avix-seo__pill is-' . esc_attr( $status ) . '">' . esc_html( ( isset( $counts[ $status ] ) ? (int) $counts[ $status ] : 0 ) . ' ' . $label ) . '</span> ';
		}
		echo '</p>';
		if ( ! defined( 'WPSEO_VERSION' ) ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Yoast SEO is not active. Applying still writes the Yoast fields; Yoast shows them once it is active.', 'avix-widgets' ) . '</p></div>';
		}
		foreach ( $plan['notes'] as $note ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html( $note ) . '</p></div>';
		}

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( self::ACTION_APPLY );
		echo '<input type="hidden" name="action" value="' . esc_attr( self::ACTION_APPLY ) . '">';
		echo '<table class="widefat striped avix-seo__table"><thead><tr>';
		echo '<td class="check-column"><span class="screen-reader-text">' . esc_html__( 'Apply', 'avix-widgets' ) . '</span></td>';
		echo '<th scope="col">' . esc_html__( 'Page', 'avix-widgets' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Status', 'avix-widgets' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Fields: current → new', 'avix-widgets' ) . '</th>';
		echo '</tr></thead><tbody>';
		foreach ( $plan['rows'] as $row ) {
			self::render_row( $row );
		}
		echo '</tbody></table>';
		echo '<p class="submit"><button type="submit" class="button button-primary">' . esc_html__( 'Apply the ticked rows', 'avix-widgets' ) . '</button> ';
		echo '<span class="description">' . esc_html__( 'Every value that is replaced is backed up first (the last five backups are kept below).', 'avix-widgets' ) . '</span></p>';
		echo '</form>';

		self::render_backups();
	}

	/**
	 * One dry-run row.
	 *
	 * @param array $row Evaluated row.
	 */
	private static function render_row( array $row ): void {
		$id     = (int) $row['post_id'];
		$status = (string) $row['status'];
		$box_id = 'avix-seo-row-' . $id;
		echo '<tr class="avix-seo__row is-' . esc_attr( $status ) . '">';

		echo '<th scope="row" class="check-column">';
		if ( 'ready' === $status ) {
			echo '<input type="checkbox" name="rows[]" value="' . esc_attr( (string) $id ) . '" id="' . esc_attr( $box_id ) . '" checked>';
		} elseif ( 'held' === $status ) {
			echo '<input type="checkbox" name="rows[]" value="' . esc_attr( (string) $id ) . '" id="' . esc_attr( $box_id ) . '">';
		}
		echo '</th>';

		echo '<td class="avix-seo__page">';
		$label = '' !== $row['title'] ? $row['title'] : ( $id ? '#' . $id : $row['path'] );
		if ( in_array( $status, array( 'ready', 'held' ), true ) ) {
			echo '<label for="' . esc_attr( $box_id ) . '"><strong>' . esc_html( $label ) . '</strong></label>';
		} else {
			echo '<strong>' . esc_html( $label ) . '</strong>';
		}
		echo '<br><code>' . esc_html( $row['path'] ) . '</code> <span class="avix-seo__muted">' . esc_html( $id ? 'ID ' . $id : __( 'no post yet', 'avix-widgets' ) ) . '</span>';
		if ( ! empty( $row['by_path'] ) && $id ) {
			echo ' <span class="avix-seo__muted">' . esc_html__( '(matched by path)', 'avix-widgets' ) . '</span>';
		}
		if ( $id && ( 'skip' !== $status || get_post( $id ) ) ) {
			$edit  = get_edit_post_link( $id );
			$view  = get_permalink( $id );
			$links = array();
			if ( $edit ) {
				$links[] = '<a href="' . esc_url( $edit ) . '">' . esc_html__( 'Edit', 'avix-widgets' ) . '</a>';
			}
			if ( $view && 'publish' === get_post_status( $id ) ) {
				$links[] = '<a href="' . esc_url( $view ) . '" target="_blank" rel="noopener">' . esc_html__( 'View', 'avix-widgets' ) . '</a>';
			}
			if ( $links ) {
				echo '<br><span class="avix-seo__links">' . implode( ' · ', $links ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts above.
			}
		}
		echo '</td>';

		echo '<td class="avix-seo__status">';
		$labels = array(
			'ready'     => __( 'Ready', 'avix-widgets' ),
			'held'      => __( 'Held back', 'avix-widgets' ),
			'unchanged' => __( 'Up to date', 'avix-widgets' ),
			'skip'      => __( 'Skipped', 'avix-widgets' ),
		);
		echo '<span class="avix-seo__pill is-' . esc_attr( $status ) . '">' . esc_html( isset( $labels[ $status ] ) ? $labels[ $status ] : $status ) . '</span>';
		if ( 'held' === $status ) {
			echo '<p class="avix-seo__note">' . esc_html( $row['requires_note'] ) . ' ' . esc_html__( 'Tick the box to include it.', 'avix-widgets' ) . '</p>';
		}
		if ( 'skip' === $status && '' !== $row['reason'] ) {
			echo '<p class="avix-seo__note">' . esc_html( $row['reason'] ) . '</p>';
		}
		foreach ( $row['notes'] as $note ) {
			echo '<p class="avix-seo__note is-warning">' . esc_html( $note ) . '</p>';
		}
		echo '</td>';

		echo '<td class="avix-seo__fields">';
		if ( $row['fields'] ) {
			echo '<table class="avix-seo__diff"><tbody>';
			foreach ( $row['fields'] as $field => $d ) {
				echo '<tr class="' . ( $d['change'] ? 'is-change' : 'is-same' ) . '">';
				echo '<th scope="row">' . esc_html( self::field_label( $field ) ) . '</th>';
				if ( $d['change'] ) {
					echo '<td class="avix-seo__old">' . ( '' !== $d['current'] ? esc_html( $d['current'] ) : '<em class="avix-seo__muted">' . esc_html__( '(empty)', 'avix-widgets' ) . '</em>' ) . '</td>';
					echo '<td class="avix-seo__arrow" aria-hidden="true">→</td>';
					echo '<td class="avix-seo__new">' . esc_html( $d['new'] ) . self::length_hint( $field, $d['new'] ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- length_hint() escapes.
				} else {
					echo '<td class="avix-seo__same" colspan="3">' . esc_html( $d['current'] ) . ' <span class="avix-seo__muted">' . esc_html__( '(unchanged)', 'avix-widgets' ) . '</span></td>';
				}
				echo '</tr>';
			}
			echo '</tbody></table>';
		} elseif ( 'skip' !== $status ) {
			echo '<span class="avix-seo__muted">' . esc_html__( 'No fields set for this row.', 'avix-widgets' ) . '</span>';
		}
		echo '</td>';
		echo '</tr>';
	}

	/**
	 * Character count after a new title or description.
	 *
	 * @param string $field Field.
	 * @param string $value New value.
	 */
	private static function length_hint( string $field, string $value ): string {
		if ( ! in_array( $field, array( 'title', 'metadesc' ), true ) ) {
			return '';
		}
		$len = function_exists( 'mb_strlen' ) ? mb_strlen( $value, 'UTF-8' ) : strlen( $value );
		/* translators: %d: number of characters. */
		return ' <span class="avix-seo__muted">' . esc_html( sprintf( _n( '(%d character)', '(%d characters)', $len, 'avix-widgets' ), $len ) ) . '</span>';
	}

	/**
	 * Screen label of a field.
	 *
	 * @param string $field Field.
	 */
	private static function field_label( string $field ): string {
		$labels = array(
			'title'            => __( 'SEO title', 'avix-widgets' ),
			'metadesc'         => __( 'Meta description', 'avix-widgets' ),
			'focuskw'          => __( 'Focus keyphrase', 'avix-widgets' ),
			'bctitle'          => __( 'Breadcrumb title', 'avix-widgets' ),
			'schema_page_type' => __( 'Schema page type', 'avix-widgets' ),
			'og_image'         => __( 'Open Graph image', 'avix-widgets' ),
			'og_image_id'      => __( 'Open Graph image ID', 'avix-widgets' ),
		);
		return isset( $labels[ $field ] ) ? $labels[ $field ] : $field;
	}

	/**
	 * The report of the last apply or restore (shown once).
	 */
	private static function render_report(): void {
		if ( empty( $_GET['avix_seo'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
			return;
		}
		$key    = self::REPORT . get_current_user_id();
		$report = get_transient( $key );
		delete_transient( $key );
		if ( ! is_array( $report ) ) {
			return;
		}
		$restore = 'restore' === ( isset( $report['kind'] ) ? $report['kind'] : '' );
		if ( ! empty( $report['error'] ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $report['error'] ) . '</p></div>';
			return;
		}
		$count = 0;
		foreach ( $report['rows'] as $row ) {
			$count += count( $row['written'] );
		}
		echo '<div class="notice notice-success"><p><strong>';
		if ( $restore ) {
			/* translators: 1: number of fields, 2: backup name. */
			echo esc_html( sprintf( __( 'Restored %1$d fields from %2$s.', 'avix-widgets' ), $count, $report['from'] ) );
		} else {
			/* translators: %d: number of fields. */
			echo esc_html( sprintf( __( 'Applied %d fields.', 'avix-widgets' ), $count ) );
		}
		echo '</strong> ';
		if ( '' !== $report['backup'] ) {
			/* translators: %s: backup option name. */
			echo esc_html( sprintf( __( 'The previous values are in backup %s.', 'avix-widgets' ), $report['backup'] ) );
		}
		echo ' ' . esc_html__( 'Purge LiteSpeed and the CDN, then check the pages.', 'avix-widgets' ) . '</p>';
		echo '<ul class="avix-seo__report">';
		foreach ( $report['rows'] as $row ) {
			echo '<li><code>' . esc_html( isset( $row['path'] ) && '' !== $row['path'] ? $row['path'] : '#' . $row['post_id'] ) . '</code> ';
			$fields = array_map( array( __CLASS__, 'field_label' ), array_map( 'strval', array_keys( $row['written'] ) ) );
			echo esc_html( $fields ? implode( ', ', $fields ) : __( 'nothing to change', 'avix-widgets' ) );
			$yoast = array(
				'yoast'       => __( 'Yoast indexable rebuilt', 'avix-widgets' ),
				'post-update' => __( 'Yoast indexable rebuilt through a post update', 'avix-widgets' ),
				'no-yoast'    => __( 'Yoast not active', 'avix-widgets' ),
				'failed'      => __( 'Yoast indexable NOT rebuilt: save the page once in the editor', 'avix-widgets' ),
			);
			if ( ! empty( $row['yoast'] ) && isset( $yoast[ $row['yoast'] ] ) ) {
				echo ' <span class="avix-seo__muted">(' . esc_html( $yoast[ $row['yoast'] ] ) . ')</span>';
			}
			foreach ( $row['notes'] as $note ) {
				echo '<br><span class="avix-seo__note is-warning">' . esc_html( $note ) . '</span>';
			}
			echo '</li>';
		}
		echo '</ul></div>';
	}

	/**
	 * Backups with a Restore button.
	 */
	private static function render_backups(): void {
		echo '<h2 id="avix-seo-backups">' . esc_html__( 'Backups', 'avix-widgets' ) . '</h2>';
		$names = self::backups();
		if ( ! $names ) {
			echo '<p class="avix-seo__muted">' . esc_html__( 'No backups yet. Apply creates one before it writes anything.', 'avix-widgets' ) . '</p>';
			return;
		}
		echo '<p>' . esc_html__( 'Restore puts every value of the chosen backup back exactly as it was. The values it replaces are backed up first, so a restore can be undone too.', 'avix-widgets' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( self::ACTION_RESTORE );
		echo '<input type="hidden" name="action" value="' . esc_attr( self::ACTION_RESTORE ) . '">';
		echo '<table class="widefat striped avix-seo__backups"><thead><tr><td class="check-column"></td><th scope="col">' . esc_html__( 'Backup', 'avix-widgets' ) . '</th><th scope="col">' . esc_html__( 'Made by', 'avix-widgets' ) . '</th><th scope="col">' . esc_html__( 'Contents', 'avix-widgets' ) . '</th></tr></thead><tbody>';
		foreach ( $names as $i => $name ) {
			$data = self::get_backup( $name );
			if ( ! $data ) {
				continue;
			}
			$user   = ! empty( $data['user'] ) ? get_userdata( (int) $data['user'] ) : false;
			$fields = 0;
			foreach ( $data['posts'] as $values ) {
				$fields += is_array( $values ) ? count( $values ) : 0;
			}
			$radio = 'avix-seo-bk-' . $i;
			echo '<tr><th scope="row" class="check-column"><input type="radio" name="backup" value="' . esc_attr( $name ) . '" id="' . esc_attr( $radio ) . '"' . ( 0 === $i ? ' checked' : '' ) . '></th>';
			echo '<td><label for="' . esc_attr( $radio ) . '"><code>' . esc_html( $name ) . '</code></label><br>' . esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $data['time'] ) ) . '</td>';
			echo '<td>' . esc_html( 'restore' === $data['kind'] ? __( 'Restore (values it replaced)', 'avix-widgets' ) : __( 'Apply (values it replaced)', 'avix-widgets' ) );
			if ( ! empty( $data['from'] ) ) {
				echo '<br><span class="avix-seo__muted">' . esc_html( sprintf( /* translators: %s: backup name. */ __( 'restored %s', 'avix-widgets' ), (string) $data['from'] ) ) . '</span>';
			}
			if ( $user ) {
				echo '<br><span class="avix-seo__muted">' . esc_html( $user->display_name ) . '</span>';
			}
			echo '</td><td><details><summary>' . esc_html( sprintf( /* translators: 1: posts, 2: fields. */ __( '%1$d posts, %2$d fields', 'avix-widgets' ), count( $data['posts'] ), $fields ) ) . '</summary><ul class="avix-seo__bk-list">';
			foreach ( $data['posts'] as $post_id => $values ) {
				foreach ( (array) $values as $key => $list ) {
					$field = array_search( $key, self::FIELDS, true );
					echo '<li><code>#' . esc_html( (string) $post_id ) . '</code> ' . esc_html( false !== $field ? self::field_label( (string) $field ) : (string) $key ) . ': ' . ( is_array( $list ) && $list ? esc_html( self::show_values( $list ) ) : '<em class="avix-seo__muted">' . esc_html__( '(not set)', 'avix-widgets' ) . '</em>' ) . '</li>';
				}
			}
			echo '</ul></details></td></tr>';
		}
		echo '</tbody></table>';
		echo '<p class="submit"><button type="submit" class="button">' . esc_html__( 'Restore the chosen backup', 'avix-widgets' ) . '</button></p>';
		echo '</form>';
	}

	/**
	 * Screen styles (this screen only).
	 */
	private static function styles(): void {
		echo '<style>
.avix-seo__lead{max-width:780px}
.avix-seo__pill{display:inline-block;padding:2px 9px;border-radius:999px;background:#f0f0f1;font-size:12px;line-height:1.6;white-space:nowrap}
.avix-seo__pill.is-ready{background:#e7f5ec;color:#0a5c2b}
.avix-seo__pill.is-held{background:#fcf3e3;color:#7a4b00}
.avix-seo__pill.is-skip{background:#fbeaea;color:#8a1f1f}
.avix-seo__table td,.avix-seo__table th{vertical-align:top}
.avix-seo__page{width:22%}.avix-seo__status{width:18%}
.avix-seo__note{margin:6px 0 0;font-size:12px;color:#50575e}
.avix-seo__note.is-warning{color:#8a4b00}
.avix-seo__muted{color:#787c82}
.avix-seo__diff{border-collapse:collapse;width:100%}
.avix-seo__diff th{width:130px;padding:3px 8px 3px 0;text-align:left;font-weight:600;vertical-align:top}
.avix-seo__diff td{padding:3px 6px;vertical-align:top;word-break:break-word}
.avix-seo__diff .avix-seo__old{color:#8a1f1f;text-decoration:line-through;text-decoration-color:rgba(138,31,31,.45);width:40%}
.avix-seo__diff .avix-seo__new{color:#0a5c2b;width:40%}
.avix-seo__diff .avix-seo__arrow{width:14px;color:#787c82}
.avix-seo__diff tr.is-same th,.avix-seo__diff .avix-seo__same{color:#787c82;font-weight:400}
.avix-seo__report{margin:0 0 8px 1.2em;list-style:disc}
.avix-seo__bk-list{margin:6px 0 0;max-height:240px;overflow:auto}
</style>';
	}
}
