<?php
/**
 * CLI tests for the case-study theme options (no WordPress needed): the four required
 * Algenix options, the save filters that fill them, the copy in Elementor's page
 * settings, the Elementor and meta box save paths, the index page and the one-time
 * backfill.
 *
 * The theme drops every option whose Theme Options switch is off in Elementor's page
 * settings each time Elementor saves, and copies a meta box save into the page settings.
 * Small stand-ins for those steps (written for this test, not the theme's code) run
 * through a minimal hook registry. Case_Studies' own callbacks are registered with the
 * hook names, priorities and argument counts read from its init(), so a changed
 * registration changes what these tests see. Post meta lives in an in-memory store:
 * update_metadata() unslashes like WordPress and fires added/updated_post_meta, and
 * update_post_meta() sends a revision's write to its post like core.
 *
 *   php tests/case-study-theme-options.php
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Case_Studies {

	/** Stand-in for the case-study data layer: only what Case_Studies calls here. */
	final class Case_Study {

		const POST_TYPE = 'avix_case_study';
		const PREFIX    = 'avix_cs_';

		public static function meta_key( string $key ): string {
			return self::PREFIX . $key;
		}

		public static function field_key( string $meta_key ): string {
			return self::meta_key( 'header' ) === $meta_key ? 'header' : '';
		}

		public static function hero_theme( int $post_id ): string {
			return isset( $GLOBALS['avix_test']['hero'][ $post_id ] ) ? $GLOBALS['avix_test']['hero'][ $post_id ] : '';
		}

		public static function index_page_id(): int {
			return (int) $GLOBALS['avix_test']['index'];
		}

		public static function flush( int $id = 0 ): void {
			$GLOBALS['avix_test']['flushed'][] = $id;
		}

		public static function neighbours( int $id ): array {
			return array(
				'prev' => 0,
				'next' => 0,
			);
		}
	}
}

namespace {

	define( 'ABSPATH', __DIR__ . '/' );

	$GLOBALS['avix_test'] = array(
		'posts'      => array(),
		'meta'       => array(),
		'options'    => array(),
		'hero'       => array(),
		'writes'     => array(),
		'flushed'    => array(),
		'purged'     => array(),
		'index'      => 0,
		'logged_in'  => true,
		'throw'      => array(),
		'get_posts'  => array(),
		'get_posts_throw' => false,
		'save_post'  => false,
		'posted'     => array(),
	);
	$GLOBALS['avix_hooks'] = array();

	/* ---------------- Hook registry ---------------- */

	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		$GLOBALS['avix_hooks'][ $hook ][ $priority ][] = array( $callback, (int) $accepted_args );
		return true;
	}
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		return add_filter( $hook, $callback, $priority, $accepted_args );
	}
	function remove_filter( $hook, $callback, $priority = 10 ) {
		foreach ( isset( $GLOBALS['avix_hooks'][ $hook ][ $priority ] ) ? $GLOBALS['avix_hooks'][ $hook ][ $priority ] : array() as $i => $entry ) {
			if ( $entry[0] === $callback ) {
				unset( $GLOBALS['avix_hooks'][ $hook ][ $priority ][ $i ] );
				return true;
			}
		}
		return false;
	}
	/** Callbacks by ascending priority, each with only as many arguments as it accepts. */
	function avix_run_hook( $hook, array $args, bool $filter ) {
		$value = $filter ? $args[0] : null;
		if ( empty( $GLOBALS['avix_hooks'][ $hook ] ) ) {
			return $value;
		}
		$by_priority = $GLOBALS['avix_hooks'][ $hook ];
		ksort( $by_priority );
		foreach ( $by_priority as $entries ) {
			foreach ( $entries as $entry ) {
				if ( $filter ) {
					$args[0] = $value;
				}
				$out = call_user_func_array( $entry[0], array_slice( $args, 0, $entry[1] ) );
				if ( $filter ) {
					$value = $out;
				}
			}
		}
		return $value;
	}
	function apply_filters( $hook, ...$args ) {
		return avix_run_hook( $hook, $args, true );
	}
	function do_action( $hook, ...$args ) {
		if ( 'litespeed_purge_post' === $hook ) {
			$GLOBALS['avix_test']['purged'][] = (int) $args[0];
		}
		avix_run_hook( $hook, $args, false );
	}

	/* ---------------- WordPress stubs ---------------- */

	function wp_slash( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'wp_slash', $value );
		}
		return is_string( $value ) ? addslashes( $value ) : $value;
	}
	function wp_unslash( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'wp_unslash', $value );
		}
		return is_string( $value ) ? stripslashes( $value ) : $value;
	}
	function is_user_logged_in() {
		return (bool) $GLOBALS['avix_test']['logged_in'];
	}
	function get_post_type( $id ) {
		return isset( $GLOBALS['avix_test']['posts'][ (int) $id ] ) ? $GLOBALS['avix_test']['posts'][ (int) $id ]['type'] : false;
	}
	function get_post_status( $id ) {
		return isset( $GLOBALS['avix_test']['posts'][ (int) $id ] ) ? $GLOBALS['avix_test']['posts'][ (int) $id ]['status'] : false;
	}
	/** Like core: the parent ID for a revision or autosave, else false. */
	function wp_is_post_revision( $id ) {
		$post = isset( $GLOBALS['avix_test']['posts'][ (int) $id ] ) ? $GLOBALS['avix_test']['posts'][ (int) $id ] : null;
		return $post && 'revision' === $post['type'] ? (int) $post['parent'] : false;
	}
	/** Like core: a revision's own meta, never its parent's. */
	function get_post_meta( $id, $key, $single = false ) {
		if ( ! empty( $GLOBALS['avix_test']['throw'][ (int) $id ] ) ) {
			throw new \RuntimeException( 'meta read failed' );
		}
		$meta = $GLOBALS['avix_test']['meta'];
		return isset( $meta[ (int) $id ] ) && array_key_exists( $key, $meta[ (int) $id ] ) ? $meta[ (int) $id ][ $key ] : '';
	}
	function metadata_exists( $type, $id, $key ) {
		return isset( $GLOBALS['avix_test']['meta'][ (int) $id ] ) && array_key_exists( $key, $GLOBALS['avix_test']['meta'][ (int) $id ] );
	}
	/** Like update_metadata(): unslashes, returns false for an identical value, then fires the meta hooks. */
	function update_metadata( $type, $id, $key, $value ) {
		$id    = (int) $id;
		$value = wp_unslash( $value );
		$meta  = &$GLOBALS['avix_test']['meta'];
		$added = ! isset( $meta[ $id ] ) || ! array_key_exists( $key, $meta[ $id ] );
		if ( ! $added && $meta[ $id ][ $key ] === $value ) {
			return false;
		}
		$meta[ $id ][ $key ]              = $value;
		$GLOBALS['avix_test']['writes'][] = array( $id, $key );
		do_action( $added ? 'added_post_meta' : 'updated_post_meta', 1, $id, $key, $value );
		return true;
	}
	/** Like core: a revision's write goes to its post. */
	function update_post_meta( $id, $key, $value ) {
		$parent = wp_is_post_revision( $id );
		return update_metadata( 'post', $parent ? $parent : $id, $key, $value );
	}
	function delete_post_meta( $id, $key ) {
		$id = (int) $id;
		if ( ! isset( $GLOBALS['avix_test']['meta'][ $id ] ) || ! array_key_exists( $key, $GLOBALS['avix_test']['meta'][ $id ] ) ) {
			return false;
		}
		unset( $GLOBALS['avix_test']['meta'][ $id ][ $key ] );
		do_action( 'deleted_post_meta', array( 1 ), $id, $key, '' );
		return true;
	}
	/**
	 * Honours the arguments the code passes: post_type, post_status ('any' leaves out
	 * trash and auto-draft, an array or a string must match), post_parent, name and
	 * posts_per_page. Records each call's arguments.
	 */
	function get_posts( $args ) {
		$GLOBALS['avix_test']['get_posts'][] = $args;
		if ( ! empty( $GLOBALS['avix_test']['get_posts_throw'] ) ) {
			throw new \RuntimeException( 'query failed' );
		}
		$status = isset( $args['post_status'] ) ? $args['post_status'] : 'publish';
		$ids    = array();
		foreach ( $GLOBALS['avix_test']['posts'] as $id => $post ) {
			if ( $post['type'] !== $args['post_type'] ) {
				continue;
			}
			if ( 'any' === $status ? in_array( $post['status'], array( 'trash', 'auto-draft' ), true ) : ! in_array( $post['status'], (array) $status, true ) ) {
				continue;
			}
			if ( isset( $args['post_parent'] ) && (int) $args['post_parent'] !== $post['parent'] ) {
				continue;
			}
			if ( isset( $args['name'] ) && $args['name'] !== $post['name'] ) {
				continue;
			}
			$ids[] = $id;
		}
		sort( $ids );
		$limit = isset( $args['posts_per_page'] ) ? (int) $args['posts_per_page'] : 5;
		return $limit > 0 ? array_slice( $ids, 0, $limit ) : $ids;
	}
	function get_option( $name, $default = false ) {
		return array_key_exists( $name, $GLOBALS['avix_test']['options'] ) ? $GLOBALS['avix_test']['options'][ $name ] : $default;
	}
	function update_option( $name, $value, $autoload = null ) {
		$GLOBALS['avix_test']['options'][ $name ] = $value;
		return true;
	}

	$class_file = dirname( __DIR__ ) . '/includes/case-studies/class-case-studies.php';
	require $class_file;

	use AvixWidgets\Case_Studies\Case_Studies;

	/* ---------------- Case_Studies' registrations, read from init() ---------------- */

	$registrations = array();
	preg_match_all(
		"/add_(?:filter|action)\(\s*'([^']+)'\s*,\s*array\(\s*__CLASS__\s*,\s*'([A-Za-z_]+)'\s*\)\s*(?:,\s*([A-Z_]+|\d+)\s*)?(?:,\s*(\d+)\s*)?\)/",
		(string) file_get_contents( $class_file ),
		$found,
		PREG_SET_ORDER
	);
	foreach ( $found as $m ) {
		$priority        = ! isset( $m[3] ) || '' === $m[3] ? 10 : ( ctype_digit( $m[3] ) ? (int) $m[3] : constant( $m[3] ) );
		$args            = ! isset( $m[4] ) || '' === $m[4] ? 1 : (int) $m[4];
		$registrations[] = array( $m[1], $m[2], $priority, $args );
		add_filter( $m[1], array( Case_Studies::class, $m[2] ), $priority, $args );
	}

	/** Stand-in for an Elementor document (an autosave revision reports its post as the main ID). */
	final class Avix_Test_Document {
		private $main_id;
		public function __construct( int $main_id ) {
			$this->main_id = $main_id;
		}
		public function get_main_id() {
			return $this->main_id;
		}
	}

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

	/** Adds a post to the store. */
	function add_test_post( int $id, string $type, string $status, array $meta = array(), string $hero = '', int $parent = 0, string $name = '' ) {
		$GLOBALS['avix_test']['posts'][ $id ] = array(
			'type'   => $type,
			'status' => $status,
			'parent' => $parent,
			'name'   => $name,
		);
		$GLOBALS['avix_test']['meta'][ $id ]  = $meta;
		$GLOBALS['avix_test']['hero'][ $id ]  = $hero;
	}

	/** Writes since the last call, as "id:key". */
	function take_writes(): array {
		$out = array();
		foreach ( $GLOBALS['avix_test']['writes'] as $w ) {
			$out[] = $w[0] . ':' . $w[1];
		}
		$GLOBALS['avix_test']['writes'] = array();
		return $out;
	}

	/** Runs $fn with error_log() sent to a temporary file; returns what was logged. */
	function capture_log( callable $fn ): string {
		$log      = tempnam( sys_get_temp_dir(), 'avix-cs-test' );
		$previous = ini_set( 'error_log', $log );
		$fn();
		ini_set( 'error_log', (string) $previous );
		$logged = (string) file_get_contents( $log );
		unlink( $log );
		return $logged;
	}

	/** Theme option keys the stand-in theme walks (the four required ones and one other), with the theme's std. */
	const THEME_STD = array(
		'body_style'       => 'wide',
		'remove_margins'   => 0,
		'header_type'      => 'default',
		'header_style'     => '',
		'sidebar_position' => 'right',
	);

	/**
	 * Stand-in for the theme's Elementor save step (algenix_elm_page_options_save): a key
	 * whose switch was not posted is dropped; a posted one takes the posted value, or,
	 * with no value posted, the earlier value or the theme's std. Then the
	 * algenix_filter_update_post_options filter runs with two arguments, like the theme.
	 */
	function theme_elementor_save( int $post_id, array $posted ) {
		$meta = get_post_meta( $post_id, 'algenix_options', true );
		$meta = is_array( $meta ) ? $meta : array();
		foreach ( THEME_STD as $k => $std ) {
			if ( isset( $posted[ "algenix_options_override_{$k}" ] ) ) {
				$meta[ $k ] = isset( $posted[ "algenix_options_field_{$k}" ] )
					? $posted[ "algenix_options_field_{$k}" ]
					: ( ! empty( $meta[ $k ] ) && 'inherit' !== strtolower( (string) $meta[ $k ] ) ? $meta[ $k ] : $std );
			} else {
				unset( $meta[ $k ] );
			}
		}
		update_post_meta( $post_id, 'algenix_options', apply_filters( 'algenix_filter_update_post_options', $meta, $post_id ) );
	}

	/** The theme on elementor/documents/ajax_save/return_data (10). */
	function theme_return_data( $response, $document ) {
		theme_elementor_save( (int) $document->get_main_id(), $GLOBALS['avix_test']['posted'] );
		return $response;
	}
	add_filter( 'elementor/documents/ajax_save/return_data', 'theme_return_data', 10, 2 );

	/** The theme on elementor/settings/page/success_response_data (10). */
	function theme_settings_save( $response, $id, $data ) {
		theme_elementor_save( (int) $id, $data );
		return $response;
	}
	add_filter( 'elementor/settings/page/success_response_data', 'theme_settings_save', 10, 3 );

	/**
	 * The theme's algenix_elm_update_post_options on algenix_filter_update_post_options (10):
	 * while save_post runs, copies the options into the page settings of $post_id (for a
	 * revision, the revision's own, still empty settings), with no slashing.
	 */
	function theme_classic_mirror( $meta, $post_id, $post_type = '' ) {
		if ( ! $GLOBALS['avix_test']['save_post'] ) {
			return $meta;
		}
		$elm = get_post_meta( $post_id, '_elementor_page_settings', true );
		$elm = is_array( $elm ) ? $elm : array();
		foreach ( array_keys( $elm ) as $k ) {
			if ( false !== strpos( $k, 'algenix_options_' ) ) {
				unset( $elm[ $k ] );
			}
		}
		foreach ( $meta as $k => $v ) {
			$elm[ "algenix_options_field_{$k}" ]    = $v;
			$elm[ "algenix_options_override_{$k}" ] = '1';
		}
		update_post_meta( $post_id, '_elementor_page_settings', apply_filters( 'algenix_filter_elementor_update_page_settings', $elm, $post_id ) );
		return $meta;
	}
	add_filter( 'algenix_filter_update_post_options', 'theme_classic_mirror', 10, 3 );

	/**
	 * The theme's meta box handler (algenix_options_override_save_options) for one
	 * save_post: keys left on Inherit are skipped, the rest come from the form, then the
	 * filter (three arguments) and update_post_meta(), with no slashing.
	 */
	function theme_classic_handler( int $post_id, array $fields, array $inherit ) {
		$meta = array();
		foreach ( array_keys( THEME_STD ) as $k ) {
			if ( in_array( $k, $inherit, true ) ) {
				continue;
			}
			$meta[ $k ] = isset( $fields[ $k ] ) ? $fields[ $k ] : '';
		}
		$meta = apply_filters( 'algenix_filter_update_post_options', $meta, $post_id, 'avix_case_study' );
		update_post_meta( $post_id, 'algenix_options', $meta );
	}

	/** A classic save: save_post for the post, then for the revision WordPress saves after it. */
	function classic_save( int $post_id, int $revision_id, array $fields, array $inherit ) {
		$GLOBALS['avix_test']['save_post'] = true;
		theme_classic_handler( $post_id, $fields, $inherit );
		theme_classic_handler( $revision_id, $fields, $inherit );
		$GLOBALS['avix_test']['save_post'] = false;
	}

	/**
	 * One Elementor save as the live site runs it: the theme before the save
	 * (elementor/ajax/register_actions, on the main post), Elementor storing the posted
	 * page settings (on the autosave for an autosave) and the layout, then
	 * elementor/documents/ajax_save/return_data: the theme (10), Case_Studies (20).
	 */
	function elementor_save( int $post_id, array $posted, $elements = null, int $autosave_id = 0 ) {
		$GLOBALS['avix_test']['posted'] = $posted;
		theme_elementor_save( $post_id, $posted );
		$target = $autosave_id ? $autosave_id : $post_id;
		if ( $posted ) {
			update_metadata( 'post', $target, '_elementor_page_settings', wp_slash( $posted ) );
		} else {
			delete_post_meta( $target, '_elementor_page_settings' );
		}
		if ( null !== $elements && ! $autosave_id ) {
			update_post_meta( $post_id, '_elementor_data', $elements );
		}
		return apply_filters( 'elementor/documents/ajax_save/return_data', array( 'status' => 'publish' ), new Avix_Test_Document( $post_id ) );
	}

	function page_settings( int $id ): array {
		$s = get_post_meta( $id, '_elementor_page_settings', true );
		return is_array( $s ) ? $s : array();
	}

	function theme_options( int $id ): array {
		$o = get_post_meta( $id, 'algenix_options', true );
		return is_array( $o ) ? $o : array();
	}

	$dark  = array(
		'body_style'     => 'fullscreen',
		'remove_margins' => '1',
		'header_type'    => 'custom',
		'header_style'   => 'header-custom-266',
	);
	$light = array_merge( $dark, array( 'header_style' => 'header-custom-275' ) );
	$keys  = Case_Studies::THEME_KEYS;

	/* ---------------- registrations ---------------- */

	$expect = array(
		array( 'algenix_filter_update_post_options', 'theme_fill_options', 5, 3 ),
		array( 'algenix_filter_elementor_update_page_settings', 'theme_page_settings', PHP_INT_MAX, 2 ),
		array( 'elementor/documents/ajax_save/return_data', 'on_elementor_save', 20, 2 ),
		array( 'elementor/settings/page/success_response_data', 'on_elementor_settings_save', 20, 3 ),
		array( 'elementor/settings/post/success_response_data', 'on_elementor_settings_save', 20, 3 ),
		array( 'admin_init', 'maybe_backfill_theme_options', 10, 1 ),
		array( 'added_post_meta', 'on_meta_change', 10, 4 ),
		array( 'updated_post_meta', 'on_meta_change', 10, 4 ),
		array( 'deleted_post_meta', 'on_meta_change', 10, 4 ),
	);
	foreach ( $expect as $want ) {
		check( in_array( $want, $registrations, true ), "init() registers {$want[1]} on {$want[0]} at priority {$want[2]} with {$want[3]} argument(s)" );
	}

	/* ---------------- fill_required ---------------- */

	check( $dark === Case_Studies::fill_required( array(), $dark ), 'empty options get all four required keys' );
	$filled = Case_Studies::fill_required(
		array(
			'body_style'     => '',
			'remove_margins' => null,
			'header_type'    => 'inherit',
			'header_style'   => 'INHERIT',
		),
		$dark
	);
	check( $dark === $filled, "'', null, inherit and INHERIT are filled", $filled );
	$explicit = array(
		'body_style'     => 'boxed',
		'remove_margins' => '0',
		'header_type'    => 'default',
		'header_style'   => 'header-custom-300',
	);
	check( $explicit === Case_Studies::fill_required( $explicit, $dark ), "explicit values stay: boxed, '0', another header type and style" );
	$filled = Case_Studies::fill_required( array( 'remove_margins' => 0 ), $dark );
	check( 0 === $filled['remove_margins'] && 'fullscreen' === $filled['body_style'], 'integer 0 on its own is kept (the page settings tell an unpicked value apart, see unpicked_keys)' );
	$filled = Case_Studies::fill_required( array( 'remove_margins' => false ), $dark );
	check( false === $filled['remove_margins'], 'false is an explicit choice too' );
	$other  = array(
		'sidebar_position' => 'hide',
		'color_scheme'     => 'inherit',
		'header_position'  => '',
		'expand_content'   => array( 'x' ),
	);
	$filled = Case_Studies::fill_required( $other, $dark );
	check( array_merge( $other, $dark ) === $filled, 'other keys are untouched, inherit and empty ones included, and keep their order', $filled );
	foreach ( array( null, '', 'garbage', false, 0 ) as $bad ) {
		check( $dark === Case_Studies::fill_required( $bad, $dark ), 'non-array options (' . var_export( $bad, true ) . ') count as empty' );
	}
	$filled = Case_Studies::fill_required( array( 'body_style' => array( 'boxed' ) ), $dark );
	check( 'fullscreen' === $filled['body_style'], 'a non-scalar required value is replaced' );
	$once = Case_Studies::fill_required( array( 'body_style' => 'boxed', 'sidebar_position' => 'hide' ), $dark );
	check( $once === Case_Studies::fill_required( $once, $dark ), 'fill_required is idempotent' );
	$complete = array_merge( array( 'sidebar_position' => 'hide' ), $dark );
	check( $complete === Case_Studies::fill_required( $complete, $dark ), 'complete options come back identical (===)' );
	$filled = Case_Studies::fill_required( array_merge( $explicit, array( 'sidebar_position' => 'hide' ) ), $dark, array( 'body_style', 'header_type', 'sidebar_position' ) );
	check(
		array(
			'body_style'       => 'fullscreen',
			'remove_margins'   => '0',
			'header_type'      => 'custom',
			'header_style'     => 'header-custom-300',
			'sidebar_position' => 'hide',
		) === $filled,
		'forced keys are set in place whatever they hold; a forced key that is not required is ignored',
		$filled
	);

	/* ---------------- mirror_settings ---------------- */

	$mirrored = Case_Studies::mirror_settings( array(), $dark, $keys );
	check(
		array(
			'algenix_options_override_body_style'     => '1',
			'algenix_options_field_body_style'        => 'fullscreen',
			'algenix_options_override_remove_margins' => '1',
			'algenix_options_field_remove_margins'    => '1',
			'algenix_options_override_header_type'    => '1',
			'algenix_options_field_header_type'       => 'custom',
			'algenix_options_override_header_style'   => '1',
			'algenix_options_field_header_style'      => 'header-custom-266',
		) === $mirrored,
		'mirror sets the override switch and the field for each key',
		$mirrored
	);
	$unrelated = array(
		'template'                                  => 'default',
		'custom_css'                                => 'selector .x:before { content: "\f101"; }',
		'algenix_options_override_sidebar_position' => '1',
		'algenix_options_field_sidebar_position'    => 'hide',
		'background_background'                     => 'classic',
	);
	$mirrored  = Case_Studies::mirror_settings( $unrelated, array_merge( array( 'sidebar_position' => 'left' ), $dark ), $keys );
	check( array_intersect_key( $mirrored, $unrelated ) === $unrelated, 'unrelated page settings are kept, including other theme overrides', $mirrored );
	check( 'hide' === $mirrored['algenix_options_field_sidebar_position'], 'keys outside the list are not mirrored' );
	$stale    = array(
		'algenix_options_override_body_style'   => '',
		'algenix_options_field_body_style'      => 'wide',
		'algenix_options_override_header_style' => '1',
		'algenix_options_field_header_style'    => 'header-custom-266',
	);
	$mirrored = Case_Studies::mirror_settings( $stale, $light, $keys );
	check( '1' === $mirrored['algenix_options_override_body_style'] && 'fullscreen' === $mirrored['algenix_options_field_body_style'], 'a switch left on Inherit is turned on with the value' );
	check( 'header-custom-275' === $mirrored['algenix_options_field_header_style'], 'a stale field is updated' );
	$mirrored = Case_Studies::mirror_settings(
		$stale,
		array(
			'body_style'     => 'inherit',
			'remove_margins' => '',
			'header_type'    => 'custom',
		),
		$keys
	);
	check(
		'' === $mirrored['algenix_options_override_body_style'] && 'wide' === $mirrored['algenix_options_field_body_style']
		&& ! isset( $mirrored['algenix_options_override_remove_margins'] ) && 'header-custom-266' === $mirrored['algenix_options_field_header_style']
		&& '1' === $mirrored['algenix_options_override_header_type'],
		'inherit, empty and missing keys are skipped and their settings left alone',
		$mirrored
	);
	$mirrored = Case_Studies::mirror_settings(
		array(),
		array(
			'remove_margins' => 0,
			'body_style'     => 'boxed',
		),
		$keys
	);
	check( '0' === $mirrored['algenix_options_field_remove_margins'] && 'boxed' === $mirrored['algenix_options_field_body_style'], "explicit values are mirrored as strings (0 becomes '0')" );
	check( '1' === Case_Studies::mirror_settings( array(), array( 'remove_margins' => 1 ), $keys )['algenix_options_field_remove_margins'], "integer 1 becomes '1'" );
	check( '1' === Case_Studies::mirror_settings( array(), array( 'remove_margins' => true ), $keys )['algenix_options_field_remove_margins'], "true becomes '1'" );
	$once = Case_Studies::mirror_settings( $unrelated, $dark, $keys );
	check( $once === Case_Studies::mirror_settings( $once, $dark, $keys ), 'settings already in sync come back identical (===): no write' );
	check( $unrelated === Case_Studies::mirror_settings( $unrelated, array(), $keys ), 'empty options change nothing' );

	/* ---------------- merge_page_settings ---------------- */

	$merged = Case_Studies::merge_page_settings(
		$unrelated,
		array(
			'algenix_options_override_body_style' => '1',
			'algenix_options_field_body_style'    => 'fullscreen',
			'template'                            => 'elementor_canvas',
		)
	);
	check(
		array(
			'template'                            => 'default',
			'custom_css'                          => $unrelated['custom_css'],
			'background_background'               => 'classic',
			'algenix_options_override_body_style' => '1',
			'algenix_options_field_body_style'    => 'fullscreen',
		) === $merged,
		"merge_page_settings: the post's own settings stay, its theme keys are replaced by the theme's, other keys from the theme are ignored",
		$merged
	);

	/* ---------------- unpicked_keys / owns_header / is_unset_option ---------------- */

	$unpicked = Case_Studies::unpicked_keys(
		array(
			'algenix_options_override_body_style'     => '1',
			'algenix_options_override_remove_margins' => '1',
			'algenix_options_field_remove_margins'    => '',
			'algenix_options_override_header_type'    => '1',
			'algenix_options_field_header_type'       => 'inherit',
			'algenix_options_override_header_style'   => '1',
			'algenix_options_field_header_style'      => 'header-custom-300',
		),
		$keys
	);
	check( array( 'body_style', 'remove_margins', 'header_type' ) === $unpicked, "unpicked: switch on with no field, '' or inherit; a picked field is not", $unpicked );
	check(
		array() === Case_Studies::unpicked_keys(
			array(
				'algenix_options_override_body_style' => '',
				'algenix_options_field_header_type'   => '',
			),
			$keys
		),
		'a switch that is off (or missing) is never unpicked'
	);
	check( Case_Studies::owns_header( array() ) && Case_Studies::owns_header( array( 'header_type' => 'inherit', 'header_style' => '' ) ), 'owns_header: unset header keys are the plugin\'s' );
	check( Case_Studies::owns_header( array( 'header_type' => 'custom', 'header_style' => 'header-custom-266' ) ) && Case_Studies::owns_header( array( 'header_type' => 'custom', 'header_style' => 'header-custom-275' ) ), 'owns_header: custom with the dark or light header' );
	check( ! Case_Studies::owns_header( array( 'header_type' => 'custom', 'header_style' => 'header-custom-300' ) ), 'owns_header: another custom header is the editor\'s' );
	check( ! Case_Studies::owns_header( array( 'header_type' => 'default', 'header_style' => 'header-custom-266' ) ), 'owns_header: the default header type is the editor\'s' );
	foreach ( array( null, '', 'inherit', 'Inherit', ' inherit ', array(), array( 'a' ) ) as $v ) {
		check( Case_Studies::is_unset_option( $v ), 'unset: ' . str_replace( "\n", '', var_export( $v, true ) ) );
	}
	foreach ( array( '0', 0, false, 'boxed', '1', 1 ) as $v ) {
		check( ! Case_Studies::is_unset_option( $v ), 'set: ' . var_export( $v, true ) );
	}

	/* ---------------- required_theme_options / default_theme_options ---------------- */

	add_test_post( 101, 'avix_case_study', 'publish', array(), 'dark' );
	add_test_post( 102, 'avix_case_study', 'publish', array(), 'light' );
	add_test_post( 103, 'avix_case_study', 'publish', array( 'avix_cs_header' => 'light' ) );
	add_test_post( 104, 'avix_case_study', 'publish', array( 'algenix_options' => array( 'sidebar_position' => 'hide', 'body_style' => 'boxed' ) ), 'dark' );
	check( $dark === Case_Studies::required_theme_options( 101 ), 'dark hero: Header Home (266)' );
	check( $light === Case_Studies::required_theme_options( 102 ), 'light hero: the light header (275)' );
	check( $light === Case_Studies::required_theme_options( 103 ), 'no hero, Header style light: the light header' );
	check( Case_Studies::THEME_KEYS === array_keys( Case_Studies::required_theme_options( 101 ) ), 'THEME_KEYS lists the required keys in order' );
	check( array_merge( array( 'sidebar_position' => 'hide' ), $dark ) === Case_Studies::default_theme_options( 104 ), 'default_theme_options keeps other keys and sets the required ones (unchanged behaviour)' );

	/* ---------------- theme_fill_options (the filter) ---------------- */

	add_test_post( 200, 'page', 'publish' );
	add_test_post( 201, 'revision', 'inherit', array(), '', 102, '102-v1' );
	add_test_post( 202, 'revision', 'inherit', array(), '', 200, '200-v1' );
	check( 102 === Case_Studies::main_id( 201 ) && 101 === Case_Studies::main_id( 101 ) && 0 === Case_Studies::main_id( 0 ), 'main_id: a revision gives its post, anything else itself' );
	check( array( 'x' => 1 ) === Case_Studies::theme_fill_options( array( 'x' => 1 ), 200 ), 'another post type: options unchanged' );
	check( 'garbage' === Case_Studies::theme_fill_options( 'garbage', 200 ), 'another post type: a non-array passes through untouched' );
	check( $light === Case_Studies::theme_fill_options( array(), 201, 'avix_case_study' ), "a revision of a case study is filled with its case study's required set (light hero)" );
	check( array() === Case_Studies::theme_fill_options( array(), 202 ), 'a revision of a page: unchanged' );
	check( array() === Case_Studies::theme_fill_options( array(), 0 ), 'no post ID: unchanged' );
	check( $dark === Case_Studies::theme_fill_options( '', 101 ), 'case study with non-array options: the required set' );
	check( $light === Case_Studies::theme_fill_options( array(), 102 ), 'case study with all four dropped: filled, header from the hero' );
	check( $light === apply_filters( 'algenix_filter_update_post_options', array(), 102 ), 'through the registered filter with two arguments (the Elementor path)' );
	$classic = Case_Studies::theme_fill_options(
		array(
			'remove_margins'   => 0,
			'header_type'      => 'custom',
			'header_style'     => 'header-custom-300',
			'sidebar_position' => 'hide',
		),
		101,
		'avix_case_study'
	);
	check(
		array(
			'remove_margins'   => 0,
			'header_type'      => 'custom',
			'header_style'     => 'header-custom-300',
			'sidebar_position' => 'hide',
			'body_style'       => 'fullscreen',
		) === $classic,
		'meta box save (three arguments): inherited body style filled, explicit choices kept',
		$classic
	);

	/* ---------------- the classic meta box save (post, then its revision) ---------------- */

	$custom_css = 'selector .x:before { content: "\f101"; }';
	$cs_page    = array(
		'template'                                  => 'default',
		'custom_css'                                => $custom_css,
		'algenix_options_override_sidebar_position' => '1',
		'algenix_options_field_sidebar_position'    => 'hide',
	);
	$form       = array(
		'remove_margins'   => '1',
		'header_type'      => 'custom',
		'header_style'     => 'header-custom-275',
		'sidebar_position' => 'hide',
	);
	add_test_post(
		501,
		'avix_case_study',
		'publish',
		array(
			'algenix_options'          => array_merge( $light, array( 'sidebar_position' => 'hide' ) ),
			'_elementor_page_settings' => $cs_page,
		),
		'light'
	);
	add_test_post( 502, 'revision', 'inherit', array(), '', 501, '501-v1' );
	classic_save( 501, 502, $form, array( 'body_style' ) );
	$o = theme_options( 501 );
	$s = page_settings( 501 );
	check( 'fullscreen' === $o['body_style'] && '1' === $o['remove_margins'] && 'header-custom-275' === $o['header_style'] && 'hide' === $o['sidebar_position'], 'body style left on Inherit in the meta box: still there after the revision pass', $o );
	check( '1' === $s['algenix_options_override_body_style'] && 'fullscreen' === $s['algenix_options_field_body_style'], 'and Elementor shows it on (the theme mirror at 10 saw the filled value)', $s );
	check( $custom_css === $s['custom_css'] && 'default' === $s['template'], 'other page settings survive the revision pass byte for byte (backslash in custom CSS)', $s );
	check( array() === page_settings( 502 ), "the revision's own page settings are untouched (the theme's write went to the post)" );

	classic_save( 501, 502, array_merge( $form, array( 'body_style' => 'boxed', 'remove_margins' => '0', 'header_style' => 'header-custom-300' ) ), array() );
	$o = theme_options( 501 );
	check( 'boxed' === $o['body_style'] && '0' === $o['remove_margins'] && 'header-custom-300' === $o['header_style'], 'explicit meta box choices survive both passes', $o );

	remove_filter( 'algenix_filter_elementor_update_page_settings', array( Case_Studies::class, 'theme_page_settings' ), PHP_INT_MAX );
	classic_save( 501, 502, $form, array( 'body_style' ) );
	$s = page_settings( 501 );
	check( ! isset( $s['custom_css'] ), 'the stand-in reproduces the theme: without theme_page_settings the revision pass drops the custom CSS', $s );
	add_filter( 'algenix_filter_elementor_update_page_settings', array( Case_Studies::class, 'theme_page_settings' ), PHP_INT_MAX, 2 );
	check( array( 'x' => '1' ) === Case_Studies::theme_page_settings( array( 'x' => '1' ), 202 ) && 'y' === Case_Studies::theme_page_settings( 'y', 502 ), 'theme_page_settings: a page revision or a non-array is passed through' );

	/* ---------------- the Elementor save path (Rehall, post 9257) ---------------- */

	add_test_post(
		9257,
		'avix_case_study',
		'publish',
		array(
			'algenix_options'          => array_merge( $dark, array( 'sidebar_position' => 'hide' ) ),
			'_elementor_page_settings' => $cs_page,
		),
		'dark'
	);
	$posted = page_settings( 9257 );
	remove_filter( 'algenix_filter_update_post_options', array( Case_Studies::class, 'theme_fill_options' ), 5 );
	theme_elementor_save( 9257, $posted );
	check( array( 'sidebar_position' => 'hide' ) === theme_options( 9257 ), 'the stand-in reproduces the bug: without the filter the four keys are dropped', theme_options( 9257 ) );
	add_filter( 'algenix_filter_update_post_options', array( Case_Studies::class, 'theme_fill_options' ), 5, 3 );
	$GLOBALS['avix_test']['meta'][9257]['algenix_options']          = array_merge( $dark, array( 'sidebar_position' => 'hide' ) );
	$GLOBALS['avix_test']['meta'][9257]['_elementor_page_settings'] = $posted;
	take_writes();

	$response = elementor_save( 9257, $posted );
	check( array( 'status' => 'publish' ) === $response, 'the return_data filter chain returns the response unchanged' );
	check( array_merge( $dark, array( 'sidebar_position' => 'hide' ) ) == theme_options( 9257 ), 'first save with every switch on Inherit: the four keys survive', theme_options( 9257 ) ); // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- order-insensitive.
	$settings = page_settings( 9257 );
	check( '1' === $settings['algenix_options_override_body_style'] && 'fullscreen' === $settings['algenix_options_field_body_style'] && 'header-custom-266' === $settings['algenix_options_field_header_style'], 'after the save the page settings carry the overrides', $settings );
	check( $custom_css === $settings['custom_css'] && 'default' === $settings['template'] && 'hide' === $settings['algenix_options_field_sidebar_position'], 'other page settings survive byte for byte (backslash in custom CSS)', $settings );

	take_writes();
	elementor_save( 9257, page_settings( 9257 ) );
	check( array() === take_writes(), 'next save from a fresh editor load writes nothing (all in sync)' );
	check( array() === array_filter( array( Case_Studies::sync_elementor_settings( 9257 ), Case_Studies::fill_theme_options( 9257 ), Case_Studies::sync_header( 9257 ) ) ), 'sync_elementor_settings, fill_theme_options and sync_header report no change' );

	$posted = array_merge(
		page_settings( 9257 ),
		array(
			'algenix_options_field_body_style'     => 'boxed',
			'algenix_options_field_remove_margins' => '0',
		)
	);
	elementor_save( 9257, $posted );
	$o = theme_options( 9257 );
	$s = page_settings( 9257 );
	check( 'boxed' === $o['body_style'] && '0' === $o['remove_margins'], 'an explicit Elementor choice (boxed, margins off) is kept', $o );
	check( 'boxed' === $s['algenix_options_field_body_style'] && '0' === $s['algenix_options_field_remove_margins'], 'and stays in the page settings' );

	$posted = page_settings( 9257 );
	unset( $posted['algenix_options_override_body_style'], $posted['algenix_options_override_remove_margins'] );
	elementor_save( 9257, $posted );
	$o = theme_options( 9257 );
	$s = page_settings( 9257 );
	check( 'fullscreen' === $o['body_style'] && '1' === $o['remove_margins'], 'switching an override back to Inherit restores the required value', $o );
	check( '1' === $s['algenix_options_override_body_style'] && 'fullscreen' === $s['algenix_options_field_body_style'], 'and the switch is on again in the page settings', $s );

	$GLOBALS['avix_test']['hero'][9257] = 'light';
	$posted                             = page_settings( 9257 ); // The open editor still posts the dark header.
	elementor_save( 9257, $posted, '[{"hero":"light"}]' );
	check( 'header-custom-275' === theme_options( 9257 )['header_style'], 'switching the hero to light: the header follows although the panel posted the old header', theme_options( 9257 ) );
	check( 'header-custom-275' === page_settings( 9257 )['algenix_options_field_header_style'], 'the page settings show the light header' );
	elementor_save( 9257, $posted );
	check( 'header-custom-275' === theme_options( 9257 )['header_style'] && 'header-custom-275' === page_settings( 9257 )['algenix_options_field_header_style'], 'a later save from the same open editor (layout unchanged, dark header posted) keeps the light header' );
	check( 'hide' === theme_options( 9257 )['sidebar_position'], 'an unrelated overridden option is never touched' );

	$posted = array_merge( page_settings( 9257 ), array( 'algenix_options_field_header_style' => 'header-custom-300' ) );
	elementor_save( 9257, $posted, '[{"hero":"light","v":2}]' );
	check( 'header-custom-300' === theme_options( 9257 )['header_style'] && 'header-custom-300' === page_settings( 9257 )['algenix_options_field_header_style'], 'another header picked in the panel survives the save, even with a layout change', theme_options( 9257 ) );
	elementor_save( 9257, page_settings( 9257 ) );
	check( 'header-custom-300' === theme_options( 9257 )['header_style'], 'and the next save' );
	$posted = array_merge( page_settings( 9257 ), array( 'algenix_options_field_header_type' => 'default' ) );
	elementor_save( 9257, $posted );
	check( 'default' === theme_options( 9257 )['header_type'] && 'default' === page_settings( 9257 )['algenix_options_field_header_type'], "the theme's default header type picked in the panel survives" );

	// Settings-only save (elementor/settings/page/success_response_data): a switch turned off.
	$posted = array_merge( page_settings( 9257 ), array( 'algenix_options_field_header_type' => 'custom', 'algenix_options_field_header_style' => 'header-custom-266' ) );
	unset( $posted['algenix_options_override_body_style'] );
	update_metadata( 'post', 9257, '_elementor_page_settings', wp_slash( $posted ) );
	$response = apply_filters( 'elementor/settings/page/success_response_data', array( 'ok' => 1 ), 9257, $posted );
	check( array( 'ok' => 1 ) === $response, 'settings-only save: response unchanged' );
	check( 'fullscreen' === theme_options( 9257 )['body_style'] && '1' === page_settings( 9257 )['algenix_options_override_body_style'], 'settings-only save: the switch turned off is filled and on again in the page settings', page_settings( 9257 ) );
	check( 'header-custom-275' === theme_options( 9257 )['header_style'], 'settings-only save: the posted stale dark header follows the light hero' );

	take_writes();
	check( 'x' === Case_Studies::on_elementor_save( 'x', new Avix_Test_Document( 200 ) ) && array() === take_writes(), 'a page saved in Elementor: response unchanged, nothing written' );
	check( 'x' === Case_Studies::on_elementor_save( 'x', null ) && 'x' === Case_Studies::on_elementor_save( 'x', new stdClass() ), 'no document or no get_main_id(): response unchanged' );
	check( false === Case_Studies::sync_elementor_settings( 200 ), 'sync_elementor_settings skips other post types' );
	check( false === Case_Studies::sync_elementor_settings( 101 ), 'sync_elementor_settings skips a case study without theme options' );

	// The switches turned on in Elementor with no value picked: the theme stores its std (wide, 0, default).
	add_test_post(
		9258,
		'avix_case_study',
		'publish',
		array(
			'algenix_options'          => array( 'sidebar_position' => 'hide' ),
			'_elementor_page_settings' => array_merge( $cs_page, array( 'template' => 'elementor_header_footer' ) ),
		),
		'light'
	);
	$posted = page_settings( 9258 );
	foreach ( $keys as $k ) {
		$posted[ "algenix_options_override_{$k}" ] = '1';
	}
	remove_filter( 'algenix_filter_update_post_options', array( Case_Studies::class, 'theme_fill_options' ), 5 );
	theme_elementor_save( 9258, $posted );
	check( 'wide' === theme_options( 9258 )['body_style'] && 0 === theme_options( 9258 )['remove_margins'] && 'default' === theme_options( 9258 )['header_type'], "the stand-in reproduces the theme: switches on with nothing picked store its std", theme_options( 9258 ) );
	add_filter( 'algenix_filter_update_post_options', array( Case_Studies::class, 'theme_fill_options' ), 5, 3 );
	elementor_save( 9258, $posted );
	$o = theme_options( 9258 );
	$s = page_settings( 9258 );
	check( array_merge( array( 'sidebar_position' => 'hide' ), $light ) == $o, 'unpicked switches: the required values, not wide / 0 / default', $o ); // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- order-insensitive.
	check( 'fullscreen' === $s['algenix_options_field_body_style'] && '1' === $s['algenix_options_field_remove_margins'] && 'custom' === $s['algenix_options_field_header_type'] && $custom_css === $s['custom_css'], 'and the panel now shows them picked', $s );

	// An autosave: the theme writes the post's options from the autosave's posted settings.
	add_test_post( 9259, 'revision', 'inherit', array( '_elementor_page_settings' => $cs_page ), '', 9258, '9258-autosave-v1' );
	elementor_save( 9258, $cs_page, null, 9259 );
	check( array_merge( array( 'sidebar_position' => 'hide' ), $light ) == theme_options( 9258 ), 'an autosave with every switch on Inherit keeps the four keys on the post', theme_options( 9258 ) ); // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- order-insensitive.
	check( 'fullscreen' === page_settings( 9258 )['algenix_options_field_body_style'], "and the post's page settings keep the overrides" );

	// Every setting at its default: Elementor removes the page settings, the save creates them with the copy.
	add_test_post( 9260, 'avix_case_study', 'publish', array( 'algenix_options' => $dark, '_elementor_page_settings' => array( 'template' => 'default' ) ), 'dark' );
	elementor_save( 9260, array() );
	check( $dark === theme_options( 9260 ) && Case_Studies::mirror_settings( array(), $dark, $keys ) === page_settings( 9260 ), 'empty posted settings (Elementor deletes them): options kept, page settings created with the copy', page_settings( 9260 ) );

	/* ---------------- writes through the meta hook (Starter, importer, apply_theme_options) ---------------- */

	// A new post without page settings (save_post inside wp_insert_post(), before an importer adds its meta).
	add_test_post( 300, 'avix_case_study', 'publish', array(), 'dark' );
	Case_Studies::apply_theme_options( 300 );
	check( $dark === theme_options( 300 ) && ! metadata_exists( 'post', 300, '_elementor_page_settings' ), 'options written from a meta hook never create the page settings (an importer\'s own row would be hidden behind ours)', page_settings( 300 ) );
	update_metadata( 'post', 300, '_elementor_page_settings', wp_slash( $cs_page ) ); // The importer's add_post_meta().
	check( $cs_page === page_settings( 300 ), "the importer's page settings are the only row and stay as imported" );
	update_post_meta( 300, '_elementor_data', '[{"imported":1}]' );
	$s = page_settings( 300 );
	check( '1' === $s['algenix_options_override_body_style'] && 'header-custom-266' === $s['algenix_options_field_header_style'] && $custom_css === $s['custom_css'] && 'hide' === $s['algenix_options_field_sidebar_position'], 'once the layout is written they get the copy, their own settings intact', $s );

	add_test_post( 301, 'avix_case_study', 'draft', array( '_elementor_page_settings' => array() ), 'light' );
	update_post_meta( 301, 'algenix_options', array_merge( array( 'sidebar_position' => 'hide' ), $light ) );
	check( Case_Studies::mirror_settings( array(), $light, $keys ) === page_settings( 301 ), 'options written with update_post_meta() are mirrored right away into page settings that exist', page_settings( 301 ) );
	add_test_post( 302, 'avix_case_study', 'draft', array( '_elementor_page_settings' => $cs_page ), 'light' );
	Case_Studies::apply_theme_options( 302 );
	check( $light === theme_options( 302 ) && 'header-custom-275' === page_settings( 302 )['algenix_options_field_header_style'] && $custom_css === page_settings( 302 )['custom_css'], 'apply_theme_options: options written and mirrored' );
	add_test_post( 303, 'avix_case_study', 'draft', array( 'algenix_options' => array( 'body_style' => 'fullscreen', 'custom_text' => 'a\\b' ), '_elementor_page_settings' => array() ), 'dark' );
	Case_Studies::sync_header( 303 );
	check( 'header-custom-266' === page_settings( 303 )['algenix_options_field_header_style'] && 'fullscreen' === page_settings( 303 )['algenix_options_field_body_style'], 'sync_header: header written and mirrored' );
	check( 'a\\b' === theme_options( 303 )['custom_text'], 'sync_header keeps a backslash in another key' );
	Case_Studies::fill_theme_options( 303 );
	check( 'a\\b' === theme_options( 303 )['custom_text'] && '1' === theme_options( 303 )['remove_margins'], 'fill_theme_options keeps a backslash in another key' );

	// The Starter on a new auto-draft (Starter::write(): page settings, then the layout).
	add_test_post( 304, 'avix_case_study', 'auto-draft', array(), 'light' );
	update_post_meta( 304, '_elementor_page_settings', array() );
	update_post_meta( 304, '_elementor_data', '[{"starter":1}]' );
	check( $light === theme_options( 304 ) && Case_Studies::mirror_settings( array(), $light, $keys ) === page_settings( 304 ), 'Starter order: options written and mirrored, so the first editor load shows the switches on', page_settings( 304 ) );
	// The importer: options first (save_post), then Starter::write().
	add_test_post( 305, 'avix_case_study', 'draft', array(), 'dark' );
	Case_Studies::apply_theme_options( 305 );
	update_post_meta( 305, '_elementor_page_settings', array() );
	check( array() === page_settings( 305 ), 'importer order: no copy before the layout' );
	update_post_meta( 305, '_elementor_data', '[{"import":1}]' );
	check( Case_Studies::mirror_settings( array(), $dark, $keys ) === page_settings( 305 ), 'importer order: unchanged options are mirrored when the layout is written', page_settings( 305 ) );

	// Page settings that are not an array: logged and left for a person to repair.
	$broken = 'a:1:{s:10:"custom_css";s:3:"x{}";}';
	add_test_post( 306, 'avix_case_study', 'publish', array( '_elementor_page_settings' => $broken ), 'dark' );
	$logged = capture_log(
		static function () {
			Case_Studies::apply_theme_options( 306 );
		}
	);
	check( $broken === get_post_meta( 306, '_elementor_page_settings', true ) && $dark === theme_options( 306 ), 'non-array page settings are never overwritten', get_post_meta( 306, '_elementor_page_settings', true ) );
	check( false !== strpos( $logged, 'page settings of post 306 are not an array' ), 'and the skip is logged', $logged );

	/* ---------------- a header the editor picked, layout written outside the editor ---------------- */

	// A revision restore, an Elementor data upgrade or an importer rewriting _elementor_data.
	$picked = array_merge( $dark, array( 'header_style' => 'header-custom-300' ) );
	add_test_post( 700, 'avix_case_study', 'publish', array( 'algenix_options' => $picked, '_elementor_page_settings' => Case_Studies::mirror_settings( $cs_page, $picked, $keys ) ), 'light' );
	update_post_meta( 700, '_elementor_data', '[{"restored":1}]' );
	check( 'header-custom-300' === theme_options( 700 )['header_style'] && 'header-custom-300' === page_settings( 700 )['algenix_options_field_header_style'], 'another custom header stays in both stores when the layout is written directly', array( theme_options( 700 ), page_settings( 700 ) ) );
	$picked = array_merge( $dark, array( 'header_type' => 'default' ) );
	add_test_post( 701, 'avix_case_study', 'publish', array( 'algenix_options' => $picked, '_elementor_page_settings' => Case_Studies::mirror_settings( $cs_page, $picked, $keys ) ), 'light' );
	update_post_meta( 701, '_elementor_data', '[{"restored":1}]' );
	check( 'default' === theme_options( 701 )['header_type'] && 'default' === page_settings( 701 )['algenix_options_field_header_type'] && 'header-custom-266' === theme_options( 701 )['header_style'], "the theme's default header type stays in both stores too", array( theme_options( 701 ), page_settings( 701 ) ) );
	check( false === Case_Studies::sync_header( 700 ) && false === Case_Studies::sync_header( 701 ), 'sync_header reports no change for a header the editor picked' );
	add_test_post( 702, 'avix_case_study', 'publish', array( 'algenix_options' => $dark, '_elementor_page_settings' => Case_Studies::mirror_settings( array(), $dark, $keys ) ), 'light' );
	update_post_meta( 702, '_elementor_data', '[{"hero":"light"}]' );
	check( 'header-custom-275' === theme_options( 702 )['header_style'] && 'header-custom-275' === page_settings( 702 )['algenix_options_field_header_style'], "the plugin's own header still follows the hero on a direct layout write" );
	add_test_post( 703, 'avix_case_study', 'publish', array( 'algenix_options' => array(), '_elementor_page_settings' => array() ), 'dark' );
	update_post_meta( 703, '_elementor_data', '[{"x":1}]' );
	check( $dark === theme_options( 703 ), 'options wiped to an empty array: a layout write brings back the required keys' );

	/* ---------------- the index page ---------------- */

	$GLOBALS['avix_test']['index'] = 600;
	add_test_post( 600, 'page', 'publish', array( '_elementor_page_settings' => array( 'custom_css' => $custom_css ) ) );
	$index_options = $dark;
	update_post_meta( 600, 'algenix_options', $index_options );
	check( Case_Studies::mirror_settings( array( 'custom_css' => $custom_css ), $dark, $keys ) === page_settings( 600 ), 'index page: options the importer writes are mirrored into its page settings', page_settings( 600 ) );
	elementor_save( 600, page_settings( 600 ) );
	check( $index_options == theme_options( 600 ), 'index page: an Elementor save keeps the four keys', theme_options( 600 ) ); // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- order-insensitive.
	$posted = page_settings( 600 );
	unset( $posted['algenix_options_override_body_style'] );
	elementor_save( 600, $posted );
	check( ! isset( theme_options( 600 )['body_style'] ) && ! isset( page_settings( 600 )['algenix_options_override_body_style'] ), "index page: a switch the owner turns off stays off (never filled)" );
	check( array() === Case_Studies::theme_fill_options( array(), 600 ), 'index page: theme_fill_options leaves it alone' );

	/* ---------------- backfill ---------------- */

	$GLOBALS['avix_test']['flushed'] = array();
	$GLOBALS['avix_test']['purged']  = array();
	$GLOBALS['avix_test']['posts']   = array();
	$GLOBALS['avix_test']['meta']    = array();
	$GLOBALS['avix_test']['options'] = array();
	take_writes();

	// Wiped by an Elementor save (the live bug), page settings without overrides, and an autosave.
	add_test_post(
		401,
		'avix_case_study',
		'publish',
		array(
			'algenix_options'          => array(
				'sidebar_position' => 'hide',
				'custom_text'      => 'a\\b',
			),
			'_elementor_page_settings' => array(
				'template'   => 'default',
				'custom_css' => $custom_css,
			),
		),
		'dark'
	);
	add_test_post( 451, 'revision', 'inherit', array( '_elementor_page_settings' => array( 'custom_css' => $custom_css ) ), '', 401, '401-autosave-v1' );
	add_test_post( 452, 'revision', 'inherit', array( '_elementor_page_settings' => array( 'custom_css' => $custom_css ) ), '', 401, '401-v1' );
	// Already complete and mirrored.
	add_test_post(
		402,
		'avix_case_study',
		'publish',
		array(
			'algenix_options'          => $dark,
			'_elementor_page_settings' => Case_Studies::mirror_settings( array(), $dark, $keys ),
		),
		'dark'
	);
	// Draft, light hero, inherit and empty values, no page settings at all.
	add_test_post(
		403,
		'avix_case_study',
		'draft',
		array(
			'algenix_options' => array(
				'body_style'     => 'inherit',
				'remove_margins' => '1',
				'header_type'    => 'custom',
				'header_style'   => '',
			),
		),
		'light'
	);
	// Explicit choices, set through the meta box before Elementor, not mirrored.
	add_test_post( 404, 'avix_case_study', 'private', array( 'algenix_options' => $explicit ), 'dark' );
	// No theme options at all.
	add_test_post( 405, 'avix_case_study', 'pending', array(), 'dark' );
	// Left alone: trash, auto-draft, a page.
	add_test_post( 406, 'avix_case_study', 'trash', array( 'algenix_options' => array( 'sidebar_position' => 'hide' ) ), 'dark' );
	add_test_post( 407, 'avix_case_study', 'auto-draft', array( 'algenix_options' => array( 'sidebar_position' => 'hide' ) ), 'dark' );
	add_test_post( 408, 'page', 'publish', array( 'algenix_options' => array( 'sidebar_position' => 'hide' ) ) );
	// Switches turned on in Elementor before the fix with nothing picked: the theme's std was stored.
	add_test_post(
		409,
		'avix_case_study',
		'publish',
		array(
			'algenix_options'          => array(
				'body_style'     => 'wide',
				'remove_margins' => 0,
				'header_type'    => 'default',
				'custom_text'    => 'a\\b',
			),
			'_elementor_page_settings' => array(
				'algenix_options_override_body_style'     => '1',
				'algenix_options_override_remove_margins' => '1',
				'algenix_options_override_header_type'    => '1',
			),
		),
		'dark'
	);
	// A post whose meta cannot be read: logged and skipped.
	add_test_post( 410, 'avix_case_study', 'publish', array( 'algenix_options' => array( 'sidebar_position' => 'hide' ) ), 'dark' );
	$GLOBALS['avix_test']['throw'][410] = true;
	// Every switch was off: the theme saved an empty array (Rehall's likely state). The index page has another key.
	add_test_post( 411, 'avix_case_study', 'publish', array( 'algenix_options' => array(), '_elementor_page_settings' => array( 'custom_css' => $custom_css ) ), 'light' );
	// Page settings that are not an array: logged and left alone, the options are still filled.
	add_test_post( 412, 'avix_case_study', 'publish', array( 'algenix_options' => array(), '_elementor_page_settings' => $broken ), 'dark' );
	// The index page, options written by the importer before the fix (no page settings), and two autosaves.
	$index_options = array_merge( $dark, array( 'sidebar_position' => 'left' ) );
	add_test_post( 600, 'page', 'publish', array( 'algenix_options' => $index_options ) );
	add_test_post( 651, 'revision', 'inherit', array( '_elementor_page_settings' => array( 'custom_css' => $custom_css ) ), '', 600, '600-autosave-v1' );
	add_test_post( 652, 'revision', 'inherit', array(), '', 600, '600-autosave-v1' );
	$before = $GLOBALS['avix_test']['meta'];

	$GLOBALS['avix_test']['logged_in'] = false;
	do_action( 'admin_init' );
	check( false === get_option( Case_Studies::OPTION_THEME_SYNC ) && array() === take_writes(), 'a logged-out admin-ajax request does not run the backfill' );

	$GLOBALS['avix_test']['logged_in'] = true;
	$GLOBALS['avix_test']['get_posts'] = array();
	$logged                            = capture_log(
		static function () {
			do_action( 'admin_init' );
		}
	);
	$query = $GLOBALS['avix_test']['get_posts'][0];
	check( 'avix_case_study' === $query['post_type'] && 'any' === $query['post_status'] && -1 === $query['posts_per_page'] && 'ids' === $query['fields'], 'the backfill asks for every case study, any status, IDs only', $query );
	check( Case_Studies::THEME_SYNC . ':1' === get_option( Case_Studies::OPTION_THEME_SYNC ), 'a post failed: the schema is not stored, the run is counted', get_option( Case_Studies::OPTION_THEME_SYNC ) );
	check( false !== strpos( $logged, 'theme options backfill failed for post 410' ), 'a post that throws is logged', $logged );
	check( $before[410] === $GLOBALS['avix_test']['meta'][410], 'and skipped, the others still run' );
	check( array_merge( array( 'sidebar_position' => 'hide', 'custom_text' => 'a\\b' ), $dark ) === theme_options( 401 ), 'wiped case study: four keys back, other keys kept byte for byte (backslash)', theme_options( 401 ) );
	$s = page_settings( 401 );
	check( '1' === $s['algenix_options_override_header_style'] && 'header-custom-266' === $s['algenix_options_field_header_style'] && $custom_css === $s['custom_css'] && 'default' === $s['template'], 'wiped case study: mirrored, other page settings intact', $s );
	$s = page_settings( 451 );
	check( '1' === $s['algenix_options_override_body_style'] && 'fullscreen' === $s['algenix_options_field_body_style'] && $custom_css === $s['custom_css'], 'its Elementor autosave gets the overrides too, custom CSS intact', $s );
	check( $before[452] === $GLOBALS['avix_test']['meta'][452], 'a normal revision is left alone' );
	check( $before[402] === $GLOBALS['avix_test']['meta'][402], 'complete case study: untouched' );
	check( $light === theme_options( 403 ), 'draft with inherit and empty values: filled, light header from the hero', theme_options( 403 ) );
	check( 'fullscreen' === page_settings( 403 )['algenix_options_field_body_style'], 'draft: page settings created with the overrides' );
	check( $explicit === theme_options( 404 ), 'explicit choices survive the backfill' );
	check( 'boxed' === page_settings( 404 )['algenix_options_field_body_style'] && 'header-custom-300' === page_settings( 404 )['algenix_options_field_header_style'], 'and are mirrored as they are' );
	check( $dark === theme_options( 405 ) && '1' === page_settings( 405 )['algenix_options_override_body_style'], 'case study without theme options: the required set, mirrored' );
	foreach ( array( 406, 407, 408 ) as $id ) {
		check( $before[ $id ] === $GLOBALS['avix_test']['meta'][ $id ], "post {$id} (trash, auto-draft or page) is untouched" );
	}
	check( array_merge( $dark, array( 'custom_text' => 'a\\b' ) ) == theme_options( 409 ), 'switches on with nothing picked (wide, 0, default): the required values, other key byte for byte', theme_options( 409 ) ); // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- order-insensitive.
	check( 'fullscreen' === page_settings( 409 )['algenix_options_field_body_style'] && '1' === page_settings( 409 )['algenix_options_field_remove_margins'] && 'custom' === page_settings( 409 )['algenix_options_field_header_type'], 'and mirrored as picked values' );
	check( $light === theme_options( 411 ), "options wiped to an empty array: the required keys only, not the index page's other keys", theme_options( 411 ) );
	elementor_save( 411, page_settings( 411 ) );
	check( $light == theme_options( 411 ), 'and the next Elementor save keeps them as they are (the page does not change twice)', theme_options( 411 ) ); // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- order-insensitive.
	check( $broken === get_post_meta( 412, '_elementor_page_settings', true ) && $dark === theme_options( 412 ), 'non-array page settings: left as they are, the options still filled' );
	check( false !== strpos( $logged, 'page settings of post 412 are not an array' ), 'and logged', $logged );
	check( $index_options === theme_options( 600 ) && Case_Studies::mirror_settings( array(), $index_options, $keys ) === page_settings( 600 ), 'index page: mirrored, not filled', page_settings( 600 ) );
	$s = page_settings( 651 );
	check( '1' === $s['algenix_options_override_body_style'] && 'header-custom-266' === $s['algenix_options_field_header_style'] && $custom_css === $s['custom_css'], "index page: its autosave is mirrored too, custom CSS intact", $s );
	check( Case_Studies::mirror_settings( array(), $index_options, $keys ) === page_settings( 652 ), 'index page: an autosave without page settings gets them', page_settings( 652 ) );
	elementor_save( 600, page_settings( 651 ), null, 651 );
	check( $dark == array_intersect_key( theme_options( 600 ), $dark ), 'index page: a save from an editor that opened the old autosave keeps the four keys', theme_options( 600 ) ); // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- order-insensitive.
	$flushed = array_values( array_unique( $GLOBALS['avix_test']['flushed'] ) );
	sort( $flushed );
	check( array( 401, 403, 404, 405, 409, 411, 412 ) === $flushed, 'caches flushed for the changed case studies only', $flushed );
	check( in_array( 401, $GLOBALS['avix_test']['purged'], true ) && ! in_array( 402, $GLOBALS['avix_test']['purged'], true ), 'LiteSpeed purge for a changed published post, none for an unchanged one' );

	// Later requests: retried while a post fails, the schema stored once all went through.
	take_writes();
	$GLOBALS['avix_test']['get_posts'] = array();
	capture_log(
		static function () {
			do_action( 'admin_init' );
		}
	);
	$runs = array_filter(
		$GLOBALS['avix_test']['get_posts'],
		static function ( $q ) {
			return 'avix_case_study' === $q['post_type'];
		}
	);
	check( Case_Studies::THEME_SYNC . ':2' === get_option( Case_Studies::OPTION_THEME_SYNC ) && 1 === count( $runs ), 'the next logged-in request runs it again while the post still fails', get_option( Case_Studies::OPTION_THEME_SYNC ) );
	check( array() === take_writes(), 'and writes nothing for the posts already done (idempotent)' );
	$GLOBALS['avix_test']['throw'] = array();
	capture_log(
		static function () {
			do_action( 'admin_init' );
		}
	);
	$writes = take_writes();
	check( Case_Studies::THEME_SYNC === get_option( Case_Studies::OPTION_THEME_SYNC ), 'once the post goes through, the schema is stored', get_option( Case_Studies::OPTION_THEME_SYNC ) );
	check( array( '410:algenix_options', '410:_elementor_page_settings' ) === $writes, 'and only the post that failed before is written', $writes );
	$GLOBALS['avix_test']['get_posts'] = array();
	do_action( 'admin_init' );
	check( array() === take_writes() && array() === $GLOBALS['avix_test']['get_posts'], 'then the stored schema skips the backfill (one get_option)' );
	$changed = -1;
	capture_log(
		static function () use ( &$changed ) {
			$changed = Case_Studies::backfill_theme_options();
		}
	);
	check( 0 === $changed && array() === take_writes(), 'running it again by hand changes nothing' );

	// A post that keeps failing: given up after THEME_SYNC_RUNS runs.
	$GLOBALS['avix_test']['options'] = array();
	$GLOBALS['avix_test']['throw']   = array( 402 => true );
	$states                          = array();
	$logged                          = capture_log(
		static function () use ( &$states ) {
			for ( $i = 0; $i < Case_Studies::THEME_SYNC_RUNS; $i++ ) {
				do_action( 'admin_init' );
				$states[] = get_option( Case_Studies::OPTION_THEME_SYNC );
			}
		}
	);
	check( array( '1:1', '1:2', '1' ) === $states, 'a post that keeps failing: retried, then given up after ' . Case_Studies::THEME_SYNC_RUNS . ' runs', $states );
	check( false !== strpos( $logged, 'given up after 3 runs, still failing: 402' ), 'giving up is logged with the failing IDs', $logged );
	$failed = null;
	capture_log(
		static function () use ( &$failed ) {
			Case_Studies::backfill_theme_options( $failed );
		}
	);
	check( array( 402 ) === $failed, 'backfill_theme_options() reports the failed IDs', $failed );
	$GLOBALS['avix_test']['throw'] = array();

	// get_posts() itself throwing never reaches wp-admin.
	$GLOBALS['avix_test']['options']         = array();
	$GLOBALS['avix_test']['get_posts_throw'] = true;
	$threw                                   = false;
	$logged                                  = capture_log(
		static function () use ( &$threw ) {
			try {
				do_action( 'admin_init' );
			} catch ( \Throwable $e ) {
				$threw = true;
			}
		}
	);
	$GLOBALS['avix_test']['get_posts_throw'] = false;
	check( ! $threw && Case_Studies::THEME_SYNC . ':1' === get_option( Case_Studies::OPTION_THEME_SYNC ), 'a failing query is caught, logged and retried later', array( $threw, get_option( Case_Studies::OPTION_THEME_SYNC ), $logged ) );
	check( false !== strpos( $logged, 'theme options backfill failed: query failed' ), 'and logged', $logged );

	/* ---------------- Theme Options box: open and save ---------------- */

	if ( ! function_exists( 'algenix_storage_get' ) ) {
		function algenix_storage_get( $key ) {
			return $GLOBALS['ALGENIX_STORAGE'][ $key ] ?? null;
		}
	}
	if ( ! function_exists( 'absint' ) ) {
		function absint( $value ) {
			return abs( (int) $value );
		}
	}
	if ( ! function_exists( 'sanitize_key' ) ) {
		function sanitize_key( $key ) {
			return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
		}
	}
	$modes = static function () {
		$GLOBALS['ALGENIX_STORAGE'] = array( 'options' => array() );
		foreach ( Case_Studies::THEME_KEYS as $key ) {
			$GLOBALS['ALGENIX_STORAGE']['options'][ $key ] = array( 'override' => array( 'mode' => 'page,post' ) );
		}
		Case_Studies::theme_override_modes();
		return $GLOBALS['ALGENIX_STORAGE']['options']['body_style']['override']['mode'];
	};
	add_test_post( 520, 'avix_case_study', 'publish' );
	add_test_post( 521, 'page', 'publish' );
	$get  = $_GET;
	$post = $_POST;

	$_GET  = array( 'post' => '520' );
	$_POST = array();
	check( 'page,post,avix_case_study' === $modes(), 'the box lists case-study fields when the edit screen opens', $modes() );

	// The save posts to post.php with no query string; the theme then saves only the fields the modes list.
	$_GET  = array();
	$_POST = array( 'post_ID' => '520', 'post_type' => 'avix_case_study' );
	check( 'page,post,avix_case_study' === $modes(), 'and when the edit screen is saved', $modes() );

	$_POST = array( 'post_ID' => '521', 'post_type' => 'page' );
	check( 'page,post' === $modes(), 'other post types are left alone', $modes() );

	$_GET  = $get;
	$_POST = $post;
	unset( $GLOBALS['ALGENIX_STORAGE'] );

	echo "\n{$checks} checks, {$failures} failed\n";
	exit( $failures ? 1 : 0 );
}
