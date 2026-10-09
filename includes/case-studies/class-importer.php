<?php
/**
 * Importer for the bundled case studies (Rehall, Ovabalance, Flow Storage):
 * creates or updates each post by slug from includes/case-studies/data/<slug>.json
 * with its meta, terms, featured image, Yoast fields, theme options and a
 * composed Elementor page (spotlights and gallery filled in), SPEC-CASE-STUDIES §7 / §11 WI-06.
 *
 * Media is referenced by upload name ("@media:avix-cs-rehall-hero-desktop.webp") and
 * found in the Media Library by file name, so nothing depends on attachment IDs.
 * Files that are not uploaded yet (for example the studio renders) are skipped and
 * listed in the report; the widgets then fall back to their defaults.
 *
 * A study whose data did not change is not written at all, so its post_modified
 * (the sitemap lastmod) stays put: the post remembers a hash of what it was imported
 * from (data file, starter layout, resolved media), and a post without that hash is
 * compared field by field with what the import would write.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Case_Studies;

defined( 'ABSPATH' ) || exit;

final class Importer {

	const POST_TYPE    = 'avix_case_study';
	const TAX_SERVICE  = 'avix_cs_service';
	const TAX_INDUSTRY = 'avix_cs_industry';

	/** Hidden post meta: the data-file slug a post was imported from. */
	const IMPORT_META = '_avix_cs_import';

	/** Hidden post meta: fingerprint() of the data a post was last imported from. */
	const HASH_META = '_avix_cs_import_hash';

	/** Part of the fingerprint: bump it when import_one() or compose() writes differently, so every study is written once more. */
	const HASH_VERSION = 1;

	/** Service terms the data files use: slug => array( name, service page ID on avixdigital.com ). */
	const SERVICE_TERMS = array(
		'shopify'           => array( 'Shopify', 8108 ),
		'webflow'           => array( 'Webflow', 7965 ),
		'web-development'   => array( 'Web development', 7965 ),
		'wordpress'         => array( 'WordPress', 8135 ),
		'uiux-brand-design' => array( 'UI/UX & brand design', 8121 ),
	);

	/** Rich (paragraph) fields: a JSON array is joined with blank lines; other lists with newlines. */
	const PARAGRAPH_FIELDS = array( 'challenge_body', 'approach_body', 'solution_body', 'outcome_body' );

	/** The /case-studies/ index page (§4; SEO wording from the SEO plan of Oct 2026, keyword map row /case-studies/). */
	const INDEX = array(
		'title'    => 'Case Studies',
		'slug'     => 'case-studies',
		'eyebrow'  => 'Selected work',
		'heading'  => 'Ecommerce & Website [Case Studies]',
		'text'     => 'Ecommerce case studies from live stores and websites we designed and built: Shopify and WooCommerce webshops for skiwear, camping gear and Greek natural products, a subscription-first supplement brand and a Webflow site that turns local searches into enquiries. Every screenshot comes from the live site, with the features that solved each problem marked.',
		'seo'      => 'Ecommerce Case Studies & Website Projects | AvixDigital',
		'desc'     => 'Five case studies for Dutch brands: Shopify, WooCommerce and Webflow builds, with annotated screenshots from the live sites and sourced, dated numbers.',
		'focuskw'  => 'ecommerce case studies',
		'og'       => 'avix-cs-index-og.jpg',
		'home_id'  => 431,
		'header'   => 'header-custom-275',
		'numbers'  => 'Numbers behind the work',
	);

	/**
	 * The index wording earlier versions wrote (v1.15.0). A value still equal to it was never
	 * edited by the owner, so a later import may replace it; anything else is left alone.
	 */
	const INDEX_PREVIOUS = array(
		'eyebrow' => 'Case studies',
		'heading' => 'Selected Website & [Ecommerce Projects]',
		'text'    => 'Live projects for brands in the Netherlands: a Shopify store for technical skiwear, a subscription-first supplement brand and a Webflow site that turns local searches into enquiries. Every screenshot comes from the live site, with the features that solved each problem marked.',
		'seo'     => 'Case Studies: Shopify & Webflow Projects | AvixDigital',
		'desc'    => 'Explore AvixDigital case studies: live Shopify and Webflow projects for brands in the Netherlands, with annotated screenshots of the features we built.',
	);

	/** Meta descriptions bundled by v1.15.1 to v1.16.0: like INDEX_PREVIOUS['desc'], still equal = never edited, so an import may replace it. */
	const INDEX_PREVIOUS_DESC = array(
		'Ecommerce case studies from AvixDigital: live stores and websites we built for growing brands, with annotated screenshots of the features that win customers.',
	);

	/**
	 * Case Study Hero settings a data file may set under layout.hero. fact_* keys are the
	 * facts-strip switches: "yes" shows that fact (credits stay hidden unless a file says so).
	 */
	const HERO_KEYS = array( 'eyebrow', 'fact_credits' );

	/** Resolved media for this request: name => array( id, url ) (empty array = not found). */
	private static $media = array();

	/** Alt text per upload name, from the data file being imported. */
	private static $alts = array();

	/** Media names that could not be found during the current import. */
	private static $missing = array();

	/* ------------------------------------------------------------------ */
	/* Public API                                                         */
	/* ------------------------------------------------------------------ */

	/**
	 * Slugs of the bundled data files, in page order (menu_order).
	 *
	 * @return string[]
	 */
	public static function slugs(): array {
		$files = glob( __DIR__ . '/data/*.json' );
		$order = array();
		foreach ( $files ? $files : array() as $file ) {
			$slug = basename( $file, '.json' );
			if ( ! preg_match( '/^[a-z0-9-]+$/', $slug ) ) {
				continue;
			}
			$data           = self::data( $slug );
			$order[ $slug ] = isset( $data['post']['menu_order'] ) ? (int) $data['post']['menu_order'] : 99;
		}
		uksort(
			$order,
			static function ( $a, $b ) use ( $order ) {
				return $order[ $a ] === $order[ $b ] ? strcmp( $a, $b ) : $order[ $a ] - $order[ $b ];
			}
		);
		return array_keys( $order );
	}

	/**
	 * One decoded data file, or array() when it is missing or invalid.
	 *
	 * @param string $slug Data file slug.
	 */
	public static function data( string $slug ): array {
		static $cache = array();
		if ( isset( $cache[ $slug ] ) ) {
			return $cache[ $slug ];
		}
		$file = __DIR__ . '/data/' . sanitize_file_name( $slug ) . '.json';
		$json = is_readable( $file ) ? file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local plugin file.
		$data = $json ? json_decode( $json, true ) : null;
		return $cache[ $slug ] = is_array( $data ) ? $data : array();
	}

	/**
	 * Imports case studies. Idempotent by slug: a second run updates the same posts
	 * (keeping the previous Elementor layout in a revision) instead of creating copies.
	 *
	 * @param array $args {
	 *     @type bool     $publish Publish the posts (default false: new posts are drafts, existing posts keep their status).
	 *     @type string[] $slugs   Data files to import (default all, see slugs()).
	 *     @type bool     $force   Write every study even when its data did not change (default false).
	 * }
	 * @return array slug => array( 'post_id' => int, 'created' => bool, 'unchanged' => bool, 'missing_media' => string[], 'status' => string, 'notes' => string[], 'error' => string )
	 */
	public static function run( array $args = array() ): array {
		$publish = ! empty( $args['publish'] );
		$force   = ! empty( $args['force'] );
		$all     = self::slugs();
		$slugs   = isset( $args['slugs'] ) && is_array( $args['slugs'] ) && $args['slugs'] ? array_values( array_intersect( $all, array_map( 'strval', $args['slugs'] ) ) ) : $all;
		$report  = array();

		if ( ! post_type_exists( self::POST_TYPE ) ) {
			foreach ( $slugs as $slug ) {
				$report[ $slug ] = self::row( 0, false, array(), '', array(), 'The Case Studies post type is not registered.' );
			}
			return $report;
		}

		foreach ( $slugs as $slug ) {
			try {
				$report[ $slug ] = self::import_one( $slug, $publish, $force );
			} catch ( \Throwable $e ) {
				$report[ $slug ] = self::row( 0, false, array(), '', array(), $e->getMessage() );
			}
		}
		if ( class_exists( __NAMESPACE__ . '\Case_Study' ) ) {
			Case_Study::flush();
		}
		return $report;
	}

	/**
	 * Finds an uploaded file by its upload name: `_wp_attached_file` LIKE '%/<name without ext>%',
	 * newest first. Only that exact name, WordPress's "-1" duplicate suffix or "-scaled" count as a
	 * match ("rehall" never matches "rehall-logo"); the same extension wins over another one.
	 * A name with a folder ("2025/12/rehall.webp") is matched from the uploads root.
	 *
	 * @param string $name Upload name, optionally prefixed with "@media:".
	 * @return array array( 'id' => int, 'url' => string ), or array() when not found.
	 */
	public static function resolve( string $name ): array {
		global $wpdb;
		$name = trim( str_replace( '\\', '/', $name ) );
		if ( 0 === strpos( $name, '@media:' ) ) {
			$name = substr( $name, 7 );
		}
		$name = ltrim( $name, '/' );
		if ( '' === $name ) {
			return array();
		}
		if ( isset( self::$media[ $name ] ) ) {
			return self::$media[ $name ];
		}

		$dir  = dirname( $name );
		$dir  = ( '.' === $dir || '' === $dir ) ? '' : trailingslashit( $dir );
		$file = basename( $name );
		$base = pathinfo( $file, PATHINFO_FILENAME );
		$ext  = strtolower( (string) pathinfo( $file, PATHINFO_EXTENSION ) );
		if ( '' === $base ) {
			return self::$media[ $name ] = array();
		}

		$select = "SELECT p.ID, pm.meta_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_type = 'attachment' AND pm.meta_key = '_wp_attached_file'";
		$order  = 'ORDER BY p.post_date DESC, p.ID DESC LIMIT 50';
		if ( $dir ) {
			$rows = $wpdb->get_results( $wpdb->prepare( "{$select} AND pm.meta_value LIKE %s {$order}", $wpdb->esc_like( $dir . $base ) . '%' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery -- table names and constant SQL only.
		} else {
			$rows = $wpdb->get_results( $wpdb->prepare( "{$select} AND ( pm.meta_value LIKE %s OR pm.meta_value LIKE %s ) {$order}", '%/' . $wpdb->esc_like( $base ) . '%', $wpdb->esc_like( $base ) . '%' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery -- table names and constant SQL only.
		}

		$pattern = '/^' . preg_quote( $base, '/' ) . '(?:-\d+)?(?:-scaled|-rotated)?$/i';
		$best    = 0;
		$rank    = PHP_INT_MAX;
		foreach ( (array) $rows as $i => $row ) {
			$path = (string) $row->meta_value;
			if ( $dir && 0 !== stripos( $path, $dir ) ) {
				continue;
			}
			if ( ! $dir && false !== strpos( $path, '/' ) && false === stripos( $path, '/' . $base ) ) {
				continue;
			}
			if ( ! preg_match( $pattern, (string) pathinfo( $path, PATHINFO_FILENAME ) ) ) {
				continue;
			}
			$r = ( strtolower( (string) pathinfo( $path, PATHINFO_EXTENSION ) ) === $ext ? 0 : 1000 ) + $i;
			if ( $r < $rank ) {
				$rank = $r;
				$best = (int) $row->ID;
			}
		}

		if ( ! $best ) {
			return self::$media[ $name ] = array();
		}
		self::$media[ $name ] = array(
			'id'  => $best,
			'url' => (string) wp_get_attachment_url( $best ),
		);
		self::maybe_alt( $best, $file );
		return self::$media[ $name ];
	}

	/**
	 * Creates the /case-studies/ index page (§4): the case-study grid on paper plus
	 * the site's Impact Numbers band (settings copied from the home page instance).
	 * An existing page keeps its Elementor content unless $args['reset'] is set; it only
	 * gets the in-place updates of upgrade_index_layout() (wording the owner never edited,
	 * the Impact Numbers fit).
	 *
	 * @param array $args { @type bool $publish Publish a new page (default false). @type bool $reset Rewrite the layout. }
	 * @return int Page ID (0 on failure).
	 */
	public static function index_page( array $args = array() ): int {
		$page = get_page_by_path( self::INDEX['slug'], OBJECT, 'page' );
		if ( $page && 'trash' === $page->post_status ) {
			wp_untrash_post( $page->ID );
			$page = get_post( $page->ID );
		}
		if ( ! $page ) {
			$id = wp_insert_post(
				array(
					'post_type'   => 'page',
					'post_status' => 'draft',
					'post_title'  => self::INDEX['title'],
					'post_name'   => self::INDEX['slug'],
					'post_author' => self::author(),
				),
				true
			);
			if ( is_wp_error( $id ) ) {
				return 0;
			}
			$page = get_post( $id );
		}
		$id = (int) $page->ID;

		if ( ! empty( $args['reset'] ) || ! Starter::has_layout( $id ) ) {
			if ( Starter::has_layout( $id ) ) {
				wp_save_post_revision( $id );
			}
			$layout = array(
				self::container( self::INDEX['title'], 'avix-case-study-grid', self::grid_settings() ),
				self::container( self::INDEX['numbers'], 'avix-impact-numbers', self::numbers_settings() ),
			);
			Starter::write( $id, Starter::fresh_ids( $layout ) );
			update_post_meta( $id, '_elementor_template_type', 'wp-page' );
		} else {
			self::upgrade_index_layout( $id );
		}

		$opts = get_post_meta( $id, 'algenix_options', true );
		$opts = is_array( $opts ) ? $opts : array();
		$want = array_merge(
			$opts,
			array(
				'body_style'     => 'fullscreen',
				'remove_margins' => '1',
				'header_type'    => 'custom',
				'header_style'   => self::INDEX['header'],
			)
		);
		if ( $want !== $opts ) {
			update_post_meta( $id, 'algenix_options', $want );
		}

		self::$alts = array( self::INDEX['og'] => 'AvixDigital case studies: live Shopify and Webflow projects' );
		// The owner's own Yoast wording wins; the bundled wording of an earlier version is replaced.
		foreach ( array(
			'_yoast_wpseo_title'    => array( self::INDEX_PREVIOUS['seo'] ),
			'_yoast_wpseo_metadesc' => array_merge( array( self::INDEX_PREVIOUS['desc'] ), self::INDEX_PREVIOUS_DESC ),
		) as $key => $previous ) {
			if ( in_array( (string) get_post_meta( $id, $key, true ), $previous, true ) ) {
				delete_post_meta( $id, $key );
			}
		}
		self::yoast( $id, self::INDEX['seo'], self::INDEX['desc'], '@media:' . self::INDEX['og'], false );
		if ( '' === trim( (string) get_post_meta( $id, '_yoast_wpseo_focuskw', true ) ) ) {
			update_post_meta( $id, '_yoast_wpseo_focuskw', self::INDEX['focuskw'] );
		}

		if ( ! empty( $args['publish'] ) && 'publish' !== $page->post_status ) {
			wp_update_post(
				array(
					'ID'          => $id,
					'post_status' => 'publish',
				)
			);
		}
		update_option( 'avix_cs_index_page', $id );
		if ( class_exists( __NAMESPACE__ . '\Case_Study' ) ) {
			Case_Study::flush();
		}
		return $id;
	}

	/* ------------------------------------------------------------------ */
	/* One study                                                          */
	/* ------------------------------------------------------------------ */

	/**
	 * Creates or updates one case study from its data file.
	 *
	 * @param string $slug    Data file slug.
	 * @param bool   $publish Publish it.
	 * @param bool   $force   Write it even when nothing changed.
	 */
	private static function import_one( string $slug, bool $publish, bool $force = false ): array {
		$data = self::data( $slug );
		if ( empty( $data['post']['title'] ) ) {
			return self::row( 0, false, array(), '', array(), 'Missing or invalid data file ' . $slug . '.json.' );
		}
		self::$missing = array();
		self::$alts    = isset( $data['alt'] ) && is_array( $data['alt'] ) ? $data['alt'] : array();
		$notes         = array();

		/* ---- Unchanged since the last import: write nothing, so post_modified (lastmod) stays ---- */
		$existing = self::find( $slug );
		$print    = self::fingerprint( $data );
		if ( $existing && ! $force && 'trash' !== $existing->post_status && ! ( $publish && 'publish' !== $existing->post_status ) ) {
			$stored = (string) get_post_meta( $existing->ID, self::HASH_META, true );
			if ( ( '' !== $stored && hash_equals( $stored, $print['hash'] ) ) || self::matches_post( $existing, $slug, $data ) ) {
				if ( $stored !== $print['hash'] ) {
					update_post_meta( $existing->ID, self::HASH_META, $print['hash'] );
				}
				$row              = self::row( (int) $existing->ID, false, $print['missing'], (string) $existing->post_status, array( 'Unchanged since the last import: nothing was written.' ), '' );
				$row['unchanged'] = true;
				return $row;
			}
		}

		/* ---- Post (always written as a draft first, so publish hooks see complete meta) ---- */
		$created = ! $existing;
		$postarr = array(
			'post_type'    => self::POST_TYPE,
			'post_title'   => (string) $data['post']['title'],
			'post_name'    => $slug,
			'post_excerpt' => isset( $data['post']['excerpt'] ) ? (string) $data['post']['excerpt'] : '',
			'menu_order'   => isset( $data['post']['menu_order'] ) ? (int) $data['post']['menu_order'] : 0,
		);
		if ( $existing ) {
			if ( 'trash' === $existing->post_status ) {
				wp_untrash_post( $existing->ID );
			}
			$postarr['ID'] = $existing->ID;
			$post_id       = wp_update_post( wp_slash( $postarr ), true );
		} else {
			$postarr['post_status']  = 'draft';
			$postarr['post_author']  = self::author();
			$postarr['post_content'] = '';
			$post_id                 = wp_insert_post( wp_slash( $postarr ), true );
		}
		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return self::row( 0, $created, array(), '', array(), is_wp_error( $post_id ) ? $post_id->get_error_message() : 'Could not save the post.' );
		}
		$post_id = (int) $post_id;
		update_post_meta( $post_id, self::IMPORT_META, $slug );

		/* ---- Meta ---- */
		$meta = isset( $data['meta'] ) && is_array( $data['meta'] ) ? $data['meta'] : array();
		foreach ( $meta as $key => $value ) {
			$note = self::write_meta( $post_id, (string) $key, $value );
			if ( $note ) {
				$notes[] = $note;
			}
		}

		/* ---- Terms ---- */
		$notes = array_merge( $notes, self::terms( $post_id, isset( $data['terms'] ) ? (array) $data['terms'] : array() ) );

		/* ---- Featured image: the first upload in the list that exists ---- */
		$chain = isset( $data['post']['featured'] ) ? (array) $data['post']['featured'] : array();
		$thumb = self::first_media( $chain );
		if ( $thumb ) {
			set_post_thumbnail( $post_id, $thumb['id'] );
		}

		/* ---- Yoast ---- */
		if ( ! empty( $data['yoast'] ) && is_array( $data['yoast'] ) ) {
			$y = $data['yoast'];
			self::yoast( $post_id, isset( $y['title'] ) ? (string) $y['title'] : '', isset( $y['metadesc'] ) ? (string) $y['metadesc'] : '', isset( $y['og_image'] ) ? (string) $y['og_image'] : '', true );
			// Focus keyphrase, and the short breadcrumb title (the client) for Yoast's BreadcrumbList.
			foreach ( array(
				'focuskw' => '_yoast_wpseo_focuskw',
				'bctitle' => '_yoast_wpseo_bctitle',
			) as $field => $key ) {
				if ( ! empty( $y[ $field ] ) ) {
					update_post_meta( $post_id, $key, wp_slash( sanitize_text_field( (string) $y[ $field ] ) ) );
				}
			}
		}

		/* ---- Theme options ---- */
		Starter::write_theme_options( $post_id, isset( $meta['header'] ) ? (string) $meta['header'] : '' );

		/* ---- Elementor page ---- */
		$layout = self::compose( isset( $data['layout'] ) && is_array( $data['layout'] ) ? $data['layout'] : array() );
		if ( $layout ) {
			if ( Starter::has_layout( $post_id ) ) {
				self::revision( $post_id );
			}
			Starter::write( $post_id, $layout );
		}

		/* ---- Publish last ---- */
		$status = get_post_status( $post_id );
		if ( $publish && 'publish' !== $status ) {
			wp_update_post(
				array(
					'ID'          => $post_id,
					'post_status' => 'publish',
				)
			);
			$status = get_post_status( $post_id );
		}
		if ( class_exists( __NAMESPACE__ . '\Case_Study' ) ) {
			Case_Study::flush( $post_id );
		}
		Starter::clear_cache( $post_id );
		update_post_meta( $post_id, self::HASH_META, $print['hash'] );

		return self::row( $post_id, $created, array_values( array_unique( self::$missing ) ), (string) $status, $notes, '' );
	}

	/* ------------------------------------------------------------------ */
	/* Change detection                                                   */
	/* ------------------------------------------------------------------ */

	/**
	 * What a study is imported from: the data file, the starter layout and the attachment
	 * every "@media:" name resolves to now (an upload added later is a change), plus
	 * HASH_VERSION. Also the names that do not resolve, for the report.
	 *
	 * @param array $data Data file.
	 * @return array array( 'hash' => string, 'missing' => string[] )
	 */
	private static function fingerprint( array $data ): array {
		$media = array();
		foreach ( self::media_names( $data ) as $name ) {
			$found          = self::resolve( $name );
			$media[ $name ] = $found ? (int) $found['id'] : 0;
		}

		// Missing media as the import reports it: the featured list only when none of it exists.
		$chain = isset( $data['post']['featured'] ) ? array_map( 'strval', (array) $data['post']['featured'] ) : array();
		$rest  = $data;
		unset( $rest['post']['featured'] );
		$missing = array();
		foreach ( self::media_names( $rest ) as $name ) {
			if ( empty( $media[ $name ] ) ) {
				$missing[] = preg_replace( '/^@media:/', '', $name );
			}
		}
		if ( $chain ) {
			$thumb = false;
			foreach ( $chain as $name ) {
				if ( ! empty( $media[ $name ] ) && wp_attachment_is_image( $media[ $name ] ) ) {
					$thumb = true;
					break;
				}
			}
			if ( ! $thumb ) {
				$missing[] = implode(
					' / ',
					array_map(
						static function ( $n ) {
							return preg_replace( '/^@media:/', '', $n );
						},
						$chain
					)
				);
			}
		}

		return array(
			'hash'    => md5( (string) wp_json_encode( array( self::HASH_VERSION, $data, Starter::bundled(), $media ) ) ),
			'missing' => array_values( array_unique( $missing ) ),
		);
	}

	/**
	 * Every "@media:" string in a data file, in order of appearance.
	 *
	 * @param mixed $value Data (walked recursively).
	 * @return string[]
	 */
	private static function media_names( $value ): array {
		$out = array();
		if ( is_string( $value ) ) {
			if ( 0 === strpos( $value, '@media:' ) ) {
				$out[] = $value;
			}
		} elseif ( is_array( $value ) ) {
			foreach ( $value as $item ) {
				foreach ( self::media_names( $item ) as $name ) {
					$out[ $name ] = $name;
				}
			}
			$out = array_values( $out );
		}
		return $out;
	}

	/**
	 * True when importing $data would leave the post as it is: post fields, meta, terms,
	 * featured image, Yoast fields, theme options and the Elementor layout (element and
	 * repeater IDs aside, which every import renews). Read-only; anything it cannot be
	 * sure of counts as a change, so the worst case is the old behaviour (a full write).
	 *
	 * @param \WP_Post $post Existing post.
	 * @param string   $slug Data file slug.
	 * @param array    $data Data file.
	 */
	private static function matches_post( \WP_Post $post, string $slug, array $data ): bool {
		$id = (int) $post->ID;

		/* ---- Post fields and the import marker ---- */
		$want = array(
			(string) $data['post']['title'],
			$slug,
			isset( $data['post']['excerpt'] ) ? (string) $data['post']['excerpt'] : '',
			isset( $data['post']['menu_order'] ) ? (int) $data['post']['menu_order'] : 0,
			$slug,
		);
		$have = array(
			(string) $post->post_title,
			(string) $post->post_name,
			(string) $post->post_excerpt,
			(int) $post->menu_order,
			(string) get_post_meta( $id, self::IMPORT_META, true ),
		);
		if ( $want !== $have ) {
			return false;
		}

		/* ---- Meta ---- */
		$meta = isset( $data['meta'] ) && is_array( $data['meta'] ) ? $data['meta'] : array();
		foreach ( $meta as $key => $value ) {
			$plan = self::meta_plan( (string) $key, $value );
			$mkey = 'avix_cs_' . $key;
			if ( 'update' === $plan['action'] && (string) get_post_meta( $id, $mkey, true ) !== (string) $plan['value'] ) {
				return false;
			}
			if ( 'delete' === $plan['action'] && metadata_exists( 'post', $id, $mkey ) ) {
				return false;
			}
		}

		/* ---- Terms ---- */
		$terms = isset( $data['terms'] ) ? (array) $data['terms'] : array();
		foreach ( array(
			self::TAX_SERVICE  => isset( $terms['service'] ) ? (array) $terms['service'] : array(),
			self::TAX_INDUSTRY => isset( $terms['industry'] ) ? (array) $terms['industry'] : array(),
		) as $tax => $items ) {
			if ( ! $items || ! taxonomy_exists( $tax ) ) {
				continue;
			}
			$want = array();
			foreach ( $items as $item ) {
				$slug_t = sanitize_title( (string) $item );
				$term   = get_term_by( 'slug', $slug_t, $tax );
				if ( ! $term ) {
					return false;
				}
				if ( self::TAX_SERVICE === $tax && isset( self::SERVICE_TERMS[ $slug_t ] ) && ! get_term_meta( $term->term_id, 'avix_service_page', true ) && 'page' === get_post_type( (int) self::SERVICE_TERMS[ $slug_t ][1] ) ) {
					return false;
				}
				$want[] = (int) $term->term_id;
			}
			$have = wp_get_object_terms( $id, $tax, array( 'fields' => 'ids' ) );
			if ( is_wp_error( $have ) ) {
				return false;
			}
			$want = array_values( array_unique( $want ) );
			$have = array_values( array_unique( array_map( 'intval', $have ) ) );
			sort( $want );
			sort( $have );
			if ( $want !== $have ) {
				return false;
			}
		}

		/* ---- Featured image ---- */
		$chain = isset( $data['post']['featured'] ) ? (array) $data['post']['featured'] : array();
		foreach ( $chain as $name ) {
			$found = self::resolve( (string) $name );
			if ( $found && wp_attachment_is_image( $found['id'] ) ) {
				if ( (int) get_post_thumbnail_id( $id ) !== (int) $found['id'] ) {
					return false;
				}
				break;
			}
		}

		/* ---- Yoast (written with overwrite) ---- */
		if ( ! empty( $data['yoast'] ) && is_array( $data['yoast'] ) ) {
			$y    = $data['yoast'];
			$want = array(
				'_yoast_wpseo_title'    => isset( $y['title'] ) ? (string) $y['title'] : '',
				'_yoast_wpseo_metadesc' => isset( $y['metadesc'] ) ? (string) $y['metadesc'] : '',
				'_yoast_wpseo_focuskw'  => ! empty( $y['focuskw'] ) ? sanitize_text_field( (string) $y['focuskw'] ) : '',
				'_yoast_wpseo_bctitle'  => ! empty( $y['bctitle'] ) ? sanitize_text_field( (string) $y['bctitle'] ) : '',
			);
			if ( ! empty( $y['og_image'] ) ) {
				$img = self::resolve( (string) $y['og_image'] );
				if ( $img ) {
					$want['_yoast_wpseo_opengraph-image']    = $img['url'];
					$want['_yoast_wpseo_opengraph-image-id'] = (string) $img['id'];
				}
			}
			foreach ( $want as $key => $value ) {
				if ( '' !== $value && (string) get_post_meta( $id, $key, true ) !== $value ) {
					return false;
				}
			}
		}

		/* ---- Theme options ---- */
		$opts = get_post_meta( $id, 'algenix_options', true );
		if ( ! is_array( $opts ) || ! $opts || array_merge( $opts, Starter::theme_defaults( isset( $meta['header'] ) ? (string) $meta['header'] : '', $id ) ) !== $opts ) {
			return false;
		}

		/* ---- Elementor layout ---- */
		$layout = self::compose( isset( $data['layout'] ) && is_array( $data['layout'] ) ? $data['layout'] : array() );
		if ( $layout ) {
			$raw  = get_post_meta( $id, '_elementor_data', true );
			$have = is_array( $raw ) ? $raw : json_decode( is_string( $raw ) ? $raw : '', true );
			$want = json_decode( (string) wp_json_encode( $layout ), true );
			if ( ! is_array( $have ) || ! is_array( $want ) || self::canonical( $want ) !== self::canonical( $have ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * An Elementor tree in comparable form: element IDs and repeater row IDs removed,
	 * keys sorted.
	 *
	 * @param mixed $value Tree or value.
	 * @return mixed
	 */
	private static function canonical( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}
		if ( isset( $value['elType'] ) ) {
			unset( $value['id'] );
		}
		unset( $value['_id'] );
		foreach ( $value as $key => $item ) {
			$value[ $key ] = self::canonical( $item );
		}
		ksort( $value );
		return $value;
	}

	/**
	 * Writes one meta field through the data layer's sanitiser. "@media:" values become
	 * attachment IDs; JSON lists become the stored line / paragraph format.
	 *
	 * @return string A note when the value was dropped or shortened, else ''.
	 */
	private static function write_meta( int $post_id, string $key, $value ): string {
		$plan = self::meta_plan( $key, $value );
		$mkey = 'avix_cs_' . $key;
		if ( '' !== $plan['missing'] ) {
			self::$missing[] = $plan['missing'];
		}
		if ( 'delete' === $plan['action'] ) {
			delete_post_meta( $post_id, $mkey );
		} elseif ( 'update' === $plan['action'] ) {
			update_post_meta( $post_id, $mkey, wp_slash( $plan['value'] ) );
		}
		return $plan['note'];
	}

	/**
	 * What write_meta() does with one data-file field, without writing it.
	 *
	 * @param string $key   FIELDS key.
	 * @param mixed  $value Data-file value.
	 * @return array array( 'action' => skip|delete|update, 'value' => sanitised value, 'note' => string, 'missing' => media name or '' )
	 */
	private static function meta_plan( string $key, $value ): array {
		$plan = array(
			'action'  => 'skip',
			'value'   => '',
			'note'    => '',
			'missing' => '',
		);
		$cs   = __NAMESPACE__ . '\Case_Study';
		if ( class_exists( $cs ) && ! isset( Case_Study::FIELDS[ $key ] ) ) {
			$plan['note'] = 'Unknown field "' . $key . '" skipped.';
			return $plan;
		}
		if ( is_array( $value ) ) {
			$value = implode( in_array( $key, self::PARAGRAPH_FIELDS, true ) ? "\n\n" : "\n", array_map( 'strval', $value ) );
		}
		$value = (string) $value;

		if ( 0 === strpos( $value, '@media:' ) ) {
			$found = self::resolve( $value );
			if ( ! $found ) {
				$plan['missing'] = substr( $value, 7 );
				return $plan; // Keep whatever is there (an owner may have picked an image by hand).
			}
			$value = (string) $found['id'];
		}

		$clean = class_exists( $cs ) ? Case_Study::sanitize( $key, $value ) : sanitize_textarea_field( $value );
		if ( '' === $clean || 0 === $clean ) {
			$plan['action'] = 'delete';
			$plan['note']   = '' === trim( $value ) ? '' : 'Field "' . $key . '" was rejected by its sanitiser and left empty.';
			return $plan;
		}
		$plan['action'] = 'update';
		$plan['value']  = $clean;

		if ( is_string( $clean ) && self::length( $clean ) < self::length( wp_strip_all_tags( $value ) ) - 2 ) {
			$plan['note'] = 'Field "' . $key . '" was shortened to ' . self::length( $clean ) . ' characters by its cap (source has ' . self::length( $value ) . ').';
		}
		return $plan;
	}

	/**
	 * Assigns service and industry terms, creating missing ones (services with their service page).
	 *
	 * @return string[] Notes.
	 */
	private static function terms( int $post_id, array $terms ): array {
		$notes = array();
		$map   = array(
			self::TAX_SERVICE  => isset( $terms['service'] ) ? (array) $terms['service'] : array(),
			self::TAX_INDUSTRY => isset( $terms['industry'] ) ? (array) $terms['industry'] : array(),
		);
		foreach ( $map as $tax => $items ) {
			if ( ! $items ) {
				continue;
			}
			if ( ! taxonomy_exists( $tax ) ) {
				$notes[] = 'Taxonomy ' . $tax . ' is not registered; terms skipped.';
				continue;
			}
			$ids = array();
			foreach ( $items as $item ) {
				$item = (string) $item;
				if ( self::TAX_SERVICE === $tax ) {
					$slug = sanitize_title( $item );
					$name = isset( self::SERVICE_TERMS[ $slug ] ) ? self::SERVICE_TERMS[ $slug ][0] : $item;
				} else {
					$name = $item;
					$slug = sanitize_title( $item );
				}
				$term = get_term_by( 'slug', $slug, $tax );
				if ( ! $term ) {
					$made = wp_insert_term( $name, $tax, array( 'slug' => $slug ) );
					if ( is_wp_error( $made ) ) {
						$notes[] = 'Term "' . $name . '": ' . $made->get_error_message();
						continue;
					}
					$term_id = (int) $made['term_id'];
				} else {
					$term_id = (int) $term->term_id;
				}
				if ( self::TAX_SERVICE === $tax && isset( self::SERVICE_TERMS[ $slug ] ) && ! get_term_meta( $term_id, 'avix_service_page', true ) ) {
					$page = (int) self::SERVICE_TERMS[ $slug ][1];
					if ( 'page' === get_post_type( $page ) ) {
						update_term_meta( $term_id, 'avix_service_page', $page );
					}
				}
				$ids[] = $term_id;
			}
			wp_set_object_terms( $post_id, $ids, $tax, false );
		}
		return $notes;
	}

	/**
	 * Yoast title, description and Open Graph image.
	 *
	 * @param bool $overwrite Replace values that are already set.
	 */
	private static function yoast( int $post_id, string $title, string $desc, string $og, bool $overwrite ): void {
		$set = static function ( $key, $value ) use ( $post_id, $overwrite ) {
			if ( '' === (string) $value ) {
				return;
			}
			if ( ! $overwrite && '' !== (string) get_post_meta( $post_id, $key, true ) ) {
				return;
			}
			update_post_meta( $post_id, $key, wp_slash( $value ) );
		};
		$set( '_yoast_wpseo_title', $title );
		$set( '_yoast_wpseo_metadesc', $desc );
		if ( '' !== $og ) {
			$img = self::resolve( $og );
			if ( $img ) {
				$set( '_yoast_wpseo_opengraph-image', $img['url'] );
				$set( '_yoast_wpseo_opengraph-image-id', (string) $img['id'] );
			} else {
				self::$missing[] = preg_replace( '/^@media:/', '', $og );
			}
		}
	}

	/* ------------------------------------------------------------------ */
	/* Elementor composition                                              */
	/* ------------------------------------------------------------------ */

	/**
	 * The bundled starter with this study's widget content: the hero text the data sets
	 * (layout.hero, see HERO_KEYS), the features chapter title, one spotlight section per
	 * feature (the starter's 2nd spotlight is the template for every later one, so its
	 * spacing carries over) and the gallery items. Colours and themes are never written
	 * here: every section follows its widget's theme defaults, so the editor's Theme
	 * switch re-themes an imported page like a new one.
	 *
	 * @param array $layout The data file's "layout" block.
	 */
	private static function compose( array $layout ): array {
		$starter = Starter::bundled();
		if ( ! $starter ) {
			return array();
		}
		$spots     = isset( $layout['spotlights'] ) ? array_values( (array) $layout['spotlights'] ) : array();
		$templates = array();
		foreach ( $starter as $section ) {
			if ( 'avix-case-study-spotlight' === self::widget_type( $section ) ) {
				$templates[] = $section;
			}
		}

		$out    = array();
		$placed = false;
		foreach ( $starter as $section ) {
			$type = self::widget_type( $section );

			if ( 'avix-case-study-spotlight' === $type ) {
				if ( $placed ) {
					continue;
				}
				$placed = true;
				if ( ! $spots ) {
					$out = array_merge( $out, $templates );
					continue;
				}
				foreach ( $spots as $i => $spot ) {
					$tpl = $templates[ min( $i, count( $templates ) - 1, 1 ) ];
					$out[] = self::spotlight_section( $tpl, (array) $spot, $i );
				}
				continue;
			}

			$settings = isset( $section['elements'][0]['settings'] ) && is_array( $section['elements'][0]['settings'] ) ? $section['elements'][0]['settings'] : array();
			if ( 'avix-case-study-hero' === $type && ! empty( $layout['hero'] ) && is_array( $layout['hero'] ) ) {
				foreach ( self::HERO_KEYS as $key ) {
					if ( ! isset( $layout['hero'][ $key ] ) || ! is_scalar( $layout['hero'][ $key ] ) ) {
						continue;
					}
					$value = $layout['hero'][ $key ];
					if ( 0 === strpos( $key, 'fact_' ) ) {
						// A facts-strip switch: "yes" shows the fact, anything else keeps it hidden.
						$settings[ $key ] = ( true === $value || in_array( strtolower( trim( (string) $value ) ), array( 'yes', '1', 'true', 'on' ), true ) ) ? 'yes' : '';
					} elseif ( '' !== trim( (string) $value ) ) {
						$settings[ $key ] = sanitize_text_field( (string) $value );
					}
				}
			}
			if ( 'avix-case-study-chapter' === $type && isset( $settings['chapter'] ) && 'features' === $settings['chapter'] && ! empty( $layout['features']['title'] ) ) {
				$settings['title'] = (string) $layout['features']['title'];
			}
			if ( 'avix-case-study-gallery' === $type && ! empty( $layout['gallery'] ) && is_array( $layout['gallery'] ) ) {
				$settings = array_merge( $settings, self::gallery_settings( $layout['gallery'] ) );
			}
			$section['elements'][0]['settings'] = $settings;
			$out[]                              = $section;
		}
		return Starter::fresh_ids( $out );
	}

	/**
	 * One spotlight section from a data-file feature.
	 *
	 * @param array $tpl  Starter section to copy (container + spotlight widget).
	 * @param array $spot Feature content.
	 * @param int   $i    Zero-based position.
	 */
	private static function spotlight_section( array $tpl, array $spot, int $i ): array {
		$s = isset( $tpl['elements'][0]['settings'] ) && is_array( $tpl['elements'][0]['settings'] ) ? $tpl['elements'][0]['settings'] : array();

		foreach ( array( 'tag', 'title', 'problem', 'built', 'attribution', 'device', 'mat', 'side', 'image_alt', 'url_label', 'attribution_text', 'source_text' ) as $key ) {
			if ( isset( $spot[ $key ] ) && '' !== (string) $spot[ $key ] ) {
				$s[ $key ] = (string) $spot[ $key ];
			}
		}
		if ( ! empty( $spot['source_link'] ) ) {
			$s['source_link'] = self::url_value( (string) $spot['source_link'] );
		}
		if ( ! empty( $spot['image'] ) ) {
			$img = self::media_value( (string) $spot['image'] );
			if ( $img ) {
				$s['image'] = $img;
			}
		}
		if ( ! empty( $spot['visual_max'] ) ) {
			$s['visual_max'] = array(
				'unit'  => 'px',
				'size'  => (int) $spot['visual_max'],
				'sizes' => array(),
			);
		}
		if ( ! empty( $spot['hotspots'] ) && is_array( $spot['hotspots'] ) ) {
			$rows = array();
			foreach ( $spot['hotspots'] as $h ) {
				$rows[] = array(
					'_id'       => '',
					'x'         => self::slider( isset( $h['x'] ) ? $h['x'] : 50, '%' ),
					'y'         => self::slider( isset( $h['y'] ) ? $h['y'] : 50, '%' ),
					'label'     => isset( $h['label'] ) ? (string) $h['label'] : '',
					'detail'    => isset( $h['detail'] ) ? (string) $h['detail'] : '',
					'placement' => isset( $h['placement'] ) ? (string) $h['placement'] : 'auto',
				);
			}
			$s['hotspots'] = $rows;
		}

		$tpl['elements'][0]['settings'] = $s;
		$label                          = 'Feature ' . ( $i + 1 ) . ( ! empty( $spot['tag'] ) ? ' · ' . $spot['tag'] : '' );
		if ( isset( $tpl['settings'] ) && is_array( $tpl['settings'] ) ) {
			$tpl['settings']['_title'] = $label;
		}
		return $tpl;
	}

	/**
	 * Gallery widget settings; items whose image is not uploaded yet are left out.
	 *
	 * @param array $gallery The data file's gallery block.
	 */
	private static function gallery_settings( array $gallery ): array {
		$s = array();
		foreach ( array( 'layout', 'mat', 'title', 'text', 'label' ) as $key ) {
			if ( isset( $gallery[ $key ] ) && '' !== (string) $gallery[ $key ] ) {
				$s[ $key ] = (string) $gallery[ $key ];
			}
		}
		$items = array();
		foreach ( isset( $gallery['items'] ) ? (array) $gallery['items'] : array() as $item ) {
			$img = self::media_value( isset( $item['image'] ) ? (string) $item['image'] : '' );
			if ( ! $img ) {
				continue;
			}
			$items[] = array(
				'_id'       => '',
				'image'     => $img,
				'device'    => isset( $item['device'] ) ? (string) $item['device'] : 'browser',
				'caption'   => isset( $item['caption'] ) ? (string) $item['caption'] : '',
				'url_label' => isset( $item['url_label'] ) ? (string) $item['url_label'] : '',
				'link'      => self::url_value( isset( $item['link'] ) ? (string) $item['link'] : '' ),
				'alt'       => isset( $item['alt'] ) ? (string) $item['alt'] : '',
			);
		}
		if ( $items ) {
			$s['items'] = $items;
		}
		return $s;
	}

	/**
	 * Case-study grid settings for the index page (§4).
	 */
	private static function grid_settings(): array {
		return array(
			'show_header'    => 'yes',
			'show_breadcrumb' => 'yes',
			'eyebrow'        => self::INDEX['eyebrow'],
			'title'          => self::INDEX['heading'],
			'title_tag'      => 'h1',
			'text'           => self::INDEX['text'],
			'count'          => 9,
			'show_featured'  => 'yes',
			'show_filters'   => 'yes',
			'show_load_more' => 'yes',
			'theme'          => 'paper',
		);
	}

	/**
	 * Impact Numbers settings: a copy of the home page instance when there is one,
	 * else the site's published numbers (200+ clients, 250+ projects, 150+ reviews, 22+ countries),
	 * fitted to the index page (see index_numbers()).
	 */
	private static function numbers_settings(): array {
		$home = 'page' === get_option( 'show_on_front' ) ? (int) get_option( 'page_on_front' ) : 0;
		foreach ( array_unique( array_filter( array( $home, (int) self::INDEX['home_id'] ) ) ) as $pid ) {
			if ( 'page' !== get_post_type( $pid ) ) {
				continue;
			}
			$raw  = get_post_meta( $pid, '_elementor_data', true );
			$data = is_array( $raw ) ? $raw : json_decode( is_string( $raw ) ? $raw : '', true );
			$hit  = is_array( $data ) ? self::find_widget( $data, 'avix-impact-numbers' ) : array();
			if ( $hit ) {
				return self::index_numbers( $hit );
			}
		}
		$rows = array();
		foreach ( array( array( '200', 'Clients supported worldwide' ), array( '250', 'Projects delivered' ), array( '150', 'Verified client reviews' ), array( '22', 'Countries served' ) ) as $m ) {
			$rows[] = array(
				'_id'    => '',
				'value'  => $m[0],
				'suffix' => '+',
				'label'  => $m[1],
			);
		}
		// The widget's own eyebrow already reads "Numbers behind the work"; no title keeps the home look.
		return self::index_numbers( array( 'metrics' => $rows ) );
	}

	/**
	 * The Impact Numbers copy as the index page needs it: on the grid's warm paper (the
	 * widget's "Warm grey" theme is #f5f3ef, so no white band runs under the cards), inside
	 * the grid's 1240px frame, without the hairline that would read as a seam between two
	 * paper sections, and with its labels tidied ("Countries has been served" reads
	 * "Countries served"). Only this copy changes: the home page keeps its text (§12.9).
	 *
	 * @param array $s Impact Numbers widget settings.
	 */
	private static function index_numbers( array $s ): array {
		$s['theme']   = 'soft';
		$s['divider'] = '';
		// Colour picks made for the white home band would fight the paper theme.
		foreach ( array( 'bg', 'line', 'icon_bg' ) as $key ) {
			unset( $s[ $key ] );
		}
		$s['content_width'] = array(
			'unit'  => 'px',
			'size'  => 1240,
			'sizes' => array(),
		);
		if ( ! empty( $s['metrics'] ) && is_array( $s['metrics'] ) ) {
			foreach ( $s['metrics'] as $i => $row ) {
				if ( is_array( $row ) && isset( $row['label'] ) && is_string( $row['label'] ) ) {
					$s['metrics'][ $i ]['label'] = (string) preg_replace( '/\s+has\s+been\s+served\b/i', ' served', $row['label'] );
				}
			}
		}
		return $s;
	}

	/**
	 * In-place updates for an index page that already has a layout (the admin import does
	 * not rewrite it): grid wording still equal to an earlier version's (INDEX_PREVIOUS) takes
	 * the current wording, and the Impact Numbers copy gets the index fit (index_numbers()).
	 * Anything the owner edited stays. The previous layout is kept in a revision, and nothing
	 * is written when nothing changes.
	 *
	 * @param int $id Index page ID.
	 */
	private static function upgrade_index_layout( int $id ): void {
		$raw  = get_post_meta( $id, '_elementor_data', true );
		$data = is_array( $raw ) ? $raw : json_decode( is_string( $raw ) ? $raw : '', true );
		if ( ! is_array( $data ) || ! $data ) {
			return;
		}
		$next = self::upgrade_index_elements( $data );
		if ( $next === $data ) {
			return;
		}
		self::revision( $id );
		Starter::write( $id, $next );
		update_post_meta( $id, '_elementor_template_type', 'wp-page' );
	}

	/**
	 * Walks an Elementor tree for upgrade_index_layout().
	 *
	 * @param array $elements Elementor elements.
	 */
	private static function upgrade_index_elements( array $elements ): array {
		foreach ( $elements as $i => $el ) {
			if ( ! is_array( $el ) ) {
				continue;
			}
			$type = isset( $el['widgetType'] ) ? (string) $el['widgetType'] : '';
			$s    = isset( $el['settings'] ) && is_array( $el['settings'] ) ? $el['settings'] : array();
			if ( 'avix-case-study-grid' === $type ) {
				foreach ( array(
					'eyebrow' => 'eyebrow',
					'title'   => 'heading',
					'text'    => 'text',
				) as $key => $field ) {
					if ( isset( $s[ $key ] ) && self::INDEX_PREVIOUS[ $field ] === (string) $s[ $key ] ) {
						$s[ $key ] = self::INDEX[ $field ];
					}
				}
				$elements[ $i ]['settings'] = $s;
			} elseif ( 'avix-impact-numbers' === $type ) {
				$elements[ $i ]['settings'] = self::index_numbers( $s );
			}
			if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
				$elements[ $i ]['elements'] = self::upgrade_index_elements( $el['elements'] );
			}
		}
		return $elements;
	}

	/* ------------------------------------------------------------------ */
	/* Helpers                                                            */
	/* ------------------------------------------------------------------ */

	/**
	 * Existing case study for a slug: the imported one first, then any post with that slug (also trashed).
	 */
	private static function find( string $slug ): ?\WP_Post {
		$statuses = array( 'publish', 'draft', 'pending', 'private', 'future', 'trash' );
		foreach ( array(
			array(
				'meta_key'   => self::IMPORT_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $slug, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			),
			array( 'name' => $slug ),
		) as $q ) {
			$found = get_posts(
				array_merge(
					array(
						'post_type'        => self::POST_TYPE,
						'post_status'      => $statuses,
						'numberposts'      => 1,
						'orderby'          => 'ID',
						'order'            => 'ASC',
						'suppress_filters' => true,
					),
					$q
				)
			);
			if ( $found ) {
				return $found[0];
			}
		}
		return null;
	}

	/**
	 * An Elementor media value for an "@media:" name, or array() (the name is then reported missing).
	 */
	private static function media_value( string $name ): array {
		if ( '' === $name ) {
			return array();
		}
		$found = self::resolve( $name );
		if ( ! $found ) {
			self::$missing[] = preg_replace( '/^@media:/', '', $name );
			return array();
		}
		return array(
			'url'    => $found['url'],
			'id'     => $found['id'],
			'size'   => '',
			'alt'    => '',
			'source' => 'library',
		);
	}

	/**
	 * The first upload of a fallback list that exists. Only when none exists is the list reported missing.
	 *
	 * @param string[] $names Upload names in order of preference.
	 */
	private static function first_media( array $names ): array {
		foreach ( $names as $name ) {
			$found = self::resolve( (string) $name );
			if ( $found && wp_attachment_is_image( $found['id'] ) ) {
				return $found;
			}
		}
		if ( $names ) {
			self::$missing[] = implode( ' / ', array_map( static function ( $n ) {
				return preg_replace( '/^@media:/', '', (string) $n );
			}, $names ) );
		}
		return array();
	}

	/**
	 * Sets the alt text from the data file when the attachment has none (never overwrites the owner's).
	 */
	private static function maybe_alt( int $id, string $file ): void {
		if ( empty( self::$alts[ $file ] ) || ! wp_attachment_is_image( $id ) ) {
			return;
		}
		if ( '' === trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ) ) {
			update_post_meta( $id, '_wp_attachment_image_alt', wp_slash( sanitize_text_field( self::$alts[ $file ] ) ) );
		}
	}

	/**
	 * Keeps the current Elementor layout in a revision before a re-import replaces it.
	 */
	private static function revision( int $post_id ): void {
		if ( ! wp_revisions_enabled( get_post( $post_id ) ) ) {
			return;
		}
		add_filter( 'wp_save_post_revision_check_for_changes', '__return_false' );
		wp_save_post_revision( $post_id );
		remove_filter( 'wp_save_post_revision_check_for_changes', '__return_false' );
	}

	/**
	 * First widget of a type in an Elementor tree: its settings (repeater ids cleared for regeneration).
	 */
	private static function find_widget( array $elements, string $type ): array {
		foreach ( $elements as $el ) {
			if ( ! is_array( $el ) ) {
				continue;
			}
			if ( isset( $el['widgetType'] ) && $type === $el['widgetType'] ) {
				return isset( $el['settings'] ) && is_array( $el['settings'] ) ? $el['settings'] : array();
			}
			if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
				$hit = self::find_widget( $el['elements'], $type );
				if ( $hit ) {
					return $hit;
				}
			}
		}
		return array();
	}

	/**
	 * A full-width, zero-padding container holding one widget (the README rule).
	 */
	private static function container( string $title, string $widget, array $settings ): array {
		return array(
			'id'       => '',
			'elType'   => 'container',
			'isInner'  => false,
			'settings' => array(
				'_title'         => $title,
				'content_width'  => 'full',
				'flex_direction' => 'column',
				'flex_gap'       => array(
					'unit'   => 'px',
					'size'   => 0,
					'column' => '0',
					'row'    => '0',
				),
				'padding'        => array(
					'unit'     => 'px',
					'top'      => '0',
					'right'    => '0',
					'bottom'   => '0',
					'left'     => '0',
					'isLinked' => true,
				),
			),
			'elements' => array(
				array(
					'id'         => '',
					'elType'     => 'widget',
					'widgetType' => $widget,
					'settings'   => $settings,
					'elements'   => array(),
				),
			),
		);
	}

	/**
	 * Widget type of a starter section (container with one widget).
	 */
	private static function widget_type( array $section ): string {
		return isset( $section['elements'][0]['widgetType'] ) ? (string) $section['elements'][0]['widgetType'] : '';
	}

	/**
	 * Elementor URL control value. Internal links, so no new tab or nofollow flags.
	 */
	private static function url_value( string $url ): array {
		return array(
			'url'               => $url,
			'is_external'       => '',
			'nofollow'          => '',
			'custom_attributes' => '',
		);
	}

	/**
	 * Elementor slider value.
	 *
	 * @param mixed  $size Number.
	 * @param string $unit Unit.
	 */
	private static function slider( $size, string $unit ): array {
		$size = (float) $size;
		return array(
			'unit'  => $unit,
			'size'  => floor( $size ) === $size ? (int) $size : $size,
			'sizes' => array(),
		);
	}

	/**
	 * Author for new posts: the current user, else the first administrator (WP-CLI, cron).
	 */
	private static function author(): int {
		$id = get_current_user_id();
		if ( $id ) {
			return $id;
		}
		$admins = get_users(
			array(
				'role'   => 'administrator',
				'number' => 1,
				'fields' => 'ID',
			)
		);
		return $admins ? (int) $admins[0] : 0;
	}

	/**
	 * Multibyte string length.
	 */
	private static function length( string $s ): int {
		return function_exists( 'mb_strlen' ) ? mb_strlen( $s, 'UTF-8' ) : strlen( $s );
	}

	/**
	 * One report row.
	 */
	private static function row( int $post_id, bool $created, array $missing, string $status, array $notes, string $error ): array {
		return array(
			'post_id'       => $post_id,
			'created'       => $created,
			'unchanged'     => false,
			'missing_media' => $missing,
			'status'        => $status,
			'notes'         => $notes,
			'error'         => $error,
		);
	}
}
