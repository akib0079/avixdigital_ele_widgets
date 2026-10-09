<?php
/**
 * CLI tests for the SEO data importer's pure helpers (no WordPress needed):
 * the field diff, the URL path guard, row evaluation, backup pruning and the
 * bundled data file.
 *
 *   php tests/seo-importer.php
 *
 * @package AvixWidgets
 */

define( 'ABSPATH', __DIR__ . '/' );
require dirname( __DIR__ ) . '/includes/seo/class-seo-importer.php';

use AvixWidgets\SEO\SEO_Importer;

$failures = 0;
$checks   = 0;

/**
 * @param bool   $ok   Condition.
 * @param string $name Check name.
 * @param mixed  $info Shown on failure.
 */
function check( $ok, $name, $info = null ) {
	global $failures, $checks;
	++$checks;
	if ( $ok ) {
		echo "ok   - {$name}\n";
		return;
	}
	++$failures;
	echo "FAIL - {$name}\n";
	if ( null !== $info ) {
		echo '       ' . str_replace( "\n", "\n       ", var_export( $info, true ) ) . "\n";
	}
}

/* ---------------- normalize_path / relative_path ---------------- */

check( '/' === SEO_Importer::normalize_path( '' ), 'empty path is the root' );
check( '/' === SEO_Importer::normalize_path( '/' ), 'root stays root' );
check( '/service/web-development/' === SEO_Importer::normalize_path( 'service/web-development' ), 'leading and trailing slash added' );
check( '/service/web-development/' === SEO_Importer::normalize_path( '/Service//web-development/?x=1#top' ), 'case, double slash, query and fragment' );
check( "/caf\xc3\xa9/" === SEO_Importer::normalize_path( '/caf%C3%A9/' ), 'percent-decoding' );

check( '/' === SEO_Importer::relative_path( 'https://avixdigital.com/', 'https://avixdigital.com/' ), 'front page permalink' );
check( '/about-avixdigital/' === SEO_Importer::relative_path( 'https://avixdigital.com/about-avixdigital/', 'https://avixdigital.com/' ), 'page permalink' );
check( '/about/' === SEO_Importer::relative_path( 'https://x.test/wp/about/', 'https://x.test/wp/' ), 'sub-folder install' );
check( '/' === SEO_Importer::relative_path( 'https://x.test/wp', 'https://x.test/wp/' ), 'sub-folder root without slash' );
check( '/wpx/' === SEO_Importer::relative_path( 'https://x.test/wpx/', 'https://x.test/wp/' ), 'a sibling folder is not stripped' );

/* ---------------- field_diff ---------------- */

$diff = SEO_Importer::field_diff(
	array(
		'title'    => 'Old title',
		'metadesc' => 'Same description',
		'focuskw'  => '',
	),
	array(
		'title'            => 'New title',
		'metadesc'         => 'Same description',
		'focuskw'          => 'web development agency',
		'bctitle'          => null,
		'schema_page_type' => null,
	)
);
check( array( 'title', 'metadesc', 'focuskw' ) === array_keys( $diff ), 'diff lists only the fields the row sets, in FIELDS order', array_keys( $diff ) );
check( true === $diff['title']['change'] && 'Old title' === $diff['title']['current'] && 'New title' === $diff['title']['new'], 'changed field: current and new' );
check( false === $diff['metadesc']['change'], 'equal field is not a change' );
check( true === $diff['focuskw']['change'] && '' === $diff['focuskw']['current'], 'unset current value counts as empty' );
$diff = SEO_Importer::field_diff( array( 'title' => 'A' ), array( 'title' => 'A ' ) );
check( true === $diff['title']['change'], 'comparison is exact (trailing space is a change)' );
$diff = SEO_Importer::field_diff( array( 'og_image_id' => 123 ), array( 'og_image_id' => '123' ) );
check( false === $diff['og_image_id']['change'], 'numeric meta compares as string' );

/* ---------------- evaluate: guards ---------------- */

$row   = array(
	'post_id'          => 5810,
	'post_type'        => 'page',
	'path'             => '/about-avixdigital/',
	'title'            => 'About Us: Akib Zawayed and the Team | AvixDigital',
	'metadesc'         => 'Meet AvixDigital.',
	'focuskw'          => 'about AvixDigital',
	'bctitle'          => 'About AvixDigital',
	'schema_page_type' => 'AboutPage',
	'og_image'         => 'avixdigital-about-og.jpg',
	'requires'         => '',
	'requires_note'    => '',
);
$state = array(
	'exists'    => true,
	'post_type' => 'page',
	'status'    => 'publish',
	'path'      => '/about-avixdigital/',
	'title'     => 'About AvixDigital',
	'meta'      => array(
		'title'    => 'About AvixDigital | Web Development for Global Brands',
		'metadesc' => 'Meet AvixDigital.',
		'focuskw'  => 'about AvixDigital',
	),
	'media'     => array(
		'id'  => 9401,
		'url' => 'https://avixdigital.com/wp-content/uploads/2026/10/avixdigital-about-og.jpg',
	),
);

$e = SEO_Importer::evaluate( $row, $state );
check( 'ready' === $e['status'], 'a matching post with changes is ready', $e );
check( isset( $e['fields']['og_image'], $e['fields']['og_image_id'] ) && '9401' === $e['fields']['og_image_id']['new'], 'resolved @media gives the Open Graph URL and ID' );
check( false === $e['fields']['metadesc']['change'] && true === $e['fields']['title']['change'], 'diff inside a row' );

$s         = $state;
$s['path'] = '/about-us/';
$e         = SEO_Importer::evaluate( $row, $s );
check( 'skip' === $e['status'] && false !== strpos( $e['reason'], 'Path guard' ) && array() === $e['fields'], 'path guard: a moved post is skipped and nothing is diffed', $e );

$s         = $state;
$s['path'] = '/About-AvixDigital';
check( 'ready' === SEO_Importer::evaluate( $row, $s )['status'], 'path guard tolerates case and a missing trailing slash' );

$home      = array_merge(
	$row,
	array(
		'post_id' => 431,
		'path'    => '/',
	)
);
$s         = $state;
$s['path'] = '/';
check( 'ready' === SEO_Importer::evaluate( $home, $s )['status'], 'front page row at /' );
$s['path'] = '/digital-agency/';
check( 'skip' === SEO_Importer::evaluate( $home, $s )['status'], 'front page row skipped when the page is no longer the front page' );

$s           = $state;
$s['exists'] = false;
check( 'skip' === SEO_Importer::evaluate( $row, array( 'exists' => false ) )['status'], 'missing post is skipped' );

$s              = $state;
$s['post_type'] = 'avix_case_study';
$e              = SEO_Importer::evaluate( array_merge( $row, array( 'post_type' => '' ) ), $s );
check( 'skip' === $e['status'] && false !== strpos( $e['reason'], 'never written' ), 'case studies are never written', $e );

$s              = $state;
$s['post_type'] = 'post';
check( 'skip' === SEO_Importer::evaluate( $row, $s )['status'], 'post type mismatch is skipped' );

$s           = $state;
$s['status'] = 'draft';
check( 'skip' === SEO_Importer::evaluate( $row, $s )['status'], 'a draft is skipped (its permalink is not its path)' );

$e = SEO_Importer::evaluate( array_merge( $row, array( 'post_id' => 0 ) ), array( 'exists' => false ) );
check( 'skip' === $e['status'] && 0 === $e['post_id'] && false !== strpos( $e['reason'], 'No page or post at /about-avixdigital/' ), 'post_id 0 row with nothing at its path is reported and skipped', $e );
$e = SEO_Importer::evaluate( array_merge( $row, array( 'post_id' => 0 ) ), $state );
check( 'skip' === $e['status'], 'post_id 0 row whose state carries no resolved post ID is skipped' );
$e = SEO_Importer::evaluate( array_merge( $row, array( 'post_id' => 0 ) ), array_merge( $state, array( 'post_id' => 9001 ) ) );
check( 'ready' === $e['status'] && 9001 === $e['post_id'] && true === $e['by_path'], 'post_id 0 row resolved by its path is ready with the found post ID', $e );
$s         = array_merge( $state, array( 'post_id' => 9001, 'status' => 'draft' ) );
$e         = SEO_Importer::evaluate( array_merge( $row, array( 'post_id' => 0 ) ), $s );
check( 'skip' === $e['status'] && false !== strpos( $e['reason'], 'Not published' ), 'post_id 0 row whose post is still a draft is skipped', $e );
$e = SEO_Importer::evaluate( array_merge( $row, array( 'post_id' => 0, 'path' => '' ) ), array_merge( $state, array( 'post_id' => 9001 ) ) );
check( 'skip' === $e['status'] && false !== strpos( $e['reason'], 'no path' ), 'row without a path is skipped', $e );
$e = SEO_Importer::evaluate( $row, array_merge( $state, array( 'post_id' => 5810 ) ) );
check( 'ready' === $e['status'] && 5810 === $e['post_id'] && false === $e['by_path'], 'a row with a post ID is not marked as matched by path' );

/* ---------------- evaluate: media, held rows, unchanged ---------------- */

$s          = $state;
$s['media'] = array();
$e          = SEO_Importer::evaluate( $row, $s );
check( 'ready' === $e['status'] && ! isset( $e['fields']['og_image'] ) && 1 === count( $e['notes'] ) && false !== strpos( $e['notes'][0], '@media:avixdigital-about-og.jpg' ), 'unresolved @media is reported, skipped, and the row still applies', $e );

$held = array_merge(
	$row,
	array(
		'requires'      => 'rewrite',
		'requires_note' => 'After the rewrite.',
	)
);
$e    = SEO_Importer::evaluate( $held, $state );
check( 'held' === $e['status'] && 'After the rewrite.' === $e['requires_note'], 'a "requires" row is held back' );

$same = $state;
foreach ( array( 'title', 'metadesc', 'focuskw', 'bctitle', 'schema_page_type' ) as $f ) {
	$same['meta'][ $f ] = $row[ $f ];
}
$same['meta']['og_image']    = $state['media']['url'];
$same['meta']['og_image_id'] = '9401';
check( 'unchanged' === SEO_Importer::evaluate( $row, $same )['status'], 'a row equal to the current values is up to date' );
check( 'unchanged' === SEO_Importer::evaluate( $held, $same )['status'], 'a held row without changes is up to date, not held' );

$partial = array_merge(
	$row,
	array(
		'bctitle'          => null,
		'schema_page_type' => null,
		'og_image'         => null,
	)
);
$e       = SEO_Importer::evaluate( $partial, $state );
check( array( 'title', 'metadesc', 'focuskw' ) === array_keys( $e['fields'] ), 'null fields are left alone (not diffed, not cleared)', array_keys( $e['fields'] ) );

/* ---------------- clean_rows ---------------- */

$clean = SEO_Importer::clean_rows(
	array(
		'rows' => array(
			array(
				'post_id'          => '215',
				'post_type'        => 'Page',
				'path'             => 'pricing',
				'title'            => "  Website Development Cost <b>&</b> Pricing\n",
				'schema_page_type' => 'LocalBusiness',
				'og_image'         => '@media:../../etc/avixdigital-pricing-og.jpg',
				'requires'         => 'Privacy Policy!',
			),
			'not a row',
		),
	)
);
$r     = $clean['rows'][0];
$blank = SEO_Importer::clean_rows( array( 'rows' => array( array( 'post_id' => 0, 'post_type' => 'post', 'path' => '  ', 'title' => 'X' ) ) ) );
check( '' === $blank['rows'][0]['path'] && 'skip' === SEO_Importer::evaluate( $blank['rows'][0], array( 'exists' => true, 'post_id' => 431, 'post_type' => 'page', 'status' => 'publish', 'path' => '/' ) )['status'], 'clean_rows: a blank path stays blank, so a post_id 0 row never lands on the front page' );
check( 1 === count( $clean['rows'] ) && 215 === $r['post_id'] && 'page' === $r['post_type'] && '/pricing/' === $r['path'], 'clean_rows: id, type and path', $r );
check( 'Website Development Cost & Pricing' === $r['title'], 'clean_rows: tags and whitespace removed', $r['title'] );
check( null === $r['schema_page_type'] && 2 === count( $clean['notes'] ), 'clean_rows: unknown schema page type dropped with a note', $clean['notes'] );
check( 'avixdigital-pricing-og.jpg' === $r['og_image'], 'clean_rows: og_image keeps only the file name', $r['og_image'] );
check( 'privacypolicy' === $r['requires'] && '' !== $r['requires_note'], 'clean_rows: requires flag sanitised, note defaulted', $r );

/* ---------------- prune_list ---------------- */

$names = array( 'avix_seo_backup_1700000003', 'avix_seo_backup_1700000001', 'avix_seo_backup_1700000005', 'avix_seo_backup_1700000002', 'avix_seo_backup_1700000004', 'avix_seo_backup_1700000005_2', 'avix_seo_backup_1700000000' );
check( array( 'avix_seo_backup_1700000001', 'avix_seo_backup_1700000000' ) === SEO_Importer::prune_list( $names, 5 ), 'prune keeps the newest five (same-second suffix is newer)', SEO_Importer::prune_list( $names, 5 ) );
check( array() === SEO_Importer::prune_list( array_slice( $names, 0, 3 ), 5 ), 'nothing to prune under the limit' );
check( array( 'avix_seo_backup_1700000005' ) === SEO_Importer::prune_list( array( 'avix_seo_backup_1700000005', 'avix_seo_backup_1700000005_2' ), 1 ), 'a same-second second backup outranks the first' );
check( array( 'avix_seo_backup_999999999' ) === SEO_Importer::prune_list( array( 'avix_seo_backup_999999999', 'avix_seo_backup_1000000000' ), 1 ), 'timestamps compare as numbers, not strings' );

/* ---------------- the bundled data file ---------------- */

$json = json_decode( (string) file_get_contents( dirname( __DIR__ ) . '/includes/seo/data/seo-pages.json' ), true );
check( is_array( $json ) && ! empty( $json['rows'] ), 'seo-pages.json is valid JSON with rows' );
$clean = SEO_Importer::clean_rows( is_array( $json ) ? $json : array() );
check( array() === $clean['notes'], 'seo-pages.json rows clean without notes', $clean['notes'] );
$ids = array();
foreach ( $clean['rows'] as $r ) {
	$ids[] = $r['post_id'];
	check( in_array( $r['post_type'], array( 'page', 'post' ), true ), "row {$r['post_id']}: page or post only" );
	if ( null !== $r['title'] ) {
		check( mb_strlen( $r['title'] ) <= 60, "row {$r['post_id']}: title at most 60 characters", mb_strlen( $r['title'] ) );
	}
	if ( null !== $r['metadesc'] ) {
		$len = mb_strlen( $r['metadesc'] );
		check( $len >= 120 && $len <= 155, "row {$r['post_id']}: meta description 120 to 155 characters", $len );
	}
}
$explicit = array_values( array_filter( $ids ) );
check( count( $explicit ) === count( array_unique( $explicit ) ), 'seo-pages.json: one row per post' );
check( count( $clean['rows'] ) === count( array_unique( array_column( $clean['rows'], 'path' ) ) ), 'seo-pages.json: one row per path' );
$by = array();
foreach ( $clean['rows'] as $r ) {
	$by[ $r['post_id'] ] = $r;
}
check( isset( $by[7262] ) && 'rewrite' === $by[7262]['requires'], 'post 7262 is held until its rewrite' );
check( isset( $by[3] ) && 'privacy-policy' === $by[3]['requires'], 'privacy policy (3) is held until the new policy' );
check( isset( $by[431] ) && '/' === $by[431]['path'], 'home row expects /' );
foreach ( array( 9257, 9258, 9259, 9350, 9352 ) as $cs ) {
	check( ! isset( $by[ $cs ] ), "case study {$cs} is not in the importer data" );
}

echo "\n{$checks} checks, {$failures} failed\n";
exit( $failures ? 1 : 0 );
