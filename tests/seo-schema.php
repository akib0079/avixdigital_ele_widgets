<?php
/**
 * CLI tests for the SEO schema module (plan A1-A5, A7, A9a): entity data, Organization
 * enrichment, the founder Person, widget Person nodes, the Service piece, the blog breadcrumb
 * and the case-study Service references. No WordPress needed: the few WordPress functions the
 * classes call are stubbed below, and a small stand-in for Yoast SEO's schema generator runs the
 * Yoast filters in Yoast's order (wpseo_schema_graph_pieces → wpseo_schema_needs_<id> →
 * generate → wpseo_schema_<id> → wpseo_schema_<type>), so the filter callbacks are tested the
 * way Yoast calls them.
 *
 *   php tests/seo-schema.php
 *
 * @package AvixWidgets
 */

namespace Yoast\WP\SEO\Generators\Schema {

	/** Stand-in for Yoast's abstract graph piece. */
	abstract class Abstract_Schema_Piece {
		public $context;
		public $helpers;
		abstract public function generate();
		abstract public function is_needed();
	}

	class Organization extends Abstract_Schema_Piece {
		public function is_needed() {
			return true;
		}
		public function generate() {
			return array(
				'@type'           => 'Organization',
				'@id'             => 'https://avixdigital.com/#organization',
				'name'            => 'AvixDigital',
				'url'             => 'https://avixdigital.com/',
				'sameAs'          => array( 'https://www.instagram.com/avixdigital_agency/' ),
				'aggregateRating' => array( '@type' => 'AggregateRating', 'ratingValue' => 5 ),
			);
		}
	}

	class WebPage extends Abstract_Schema_Piece {
		public function is_needed() {
			return true;
		}
		public function generate() {
			return array(
				'@type' => 'WebPage',
				'@id'   => $this->context->canonical,
			);
		}
	}

	class Article extends Abstract_Schema_Piece {
		public function is_needed() {
			return $GLOBALS['avix_test']['singular_post'];
		}
		public function generate() {
			return array(
				'@type'  => 'Article',
				'@id'    => $this->context->canonical . '#article',
				'author' => array(
					'name' => 'Avix Digital',
					'@id'  => 'https://avixdigital.com/#/schema/person/' . wp_hash( 'akib1' ),
				),
			);
		}
	}

	class Author extends Abstract_Schema_Piece {
		public function is_needed() {
			return $GLOBALS['avix_test']['singular_post'];
		}
		public function generate() {
			return array(
				'@type'  => array( 'Person' ),
				'@id'    => 'https://avixdigital.com/#/schema/person/' . wp_hash( 'akib1' ),
				'name'   => 'Avix Digital',
				'sameAs' => array( 'https://avixdigital.com' ),
				'url'    => '#',
			);
		}
	}
}

namespace {

	define( 'ABSPATH', __DIR__ . '/' );
	define( 'WPSEO_VERSION', '27.5-test' );
	define( 'OBJECT', 'OBJECT' );

	$GLOBALS['avix_test'] = array(
		'options'       => array(),
		'filters'       => array(),
		'singular'      => true,
		'singular_post' => false,
		'queried'       => 0,
		'post_author'   => 1,
		'author'        => false,
	);

	/* ---------------- WordPress stubs ---------------- */

	function add_filter( $hook, $callback, $priority = 10, $args = 1 ) {
		$GLOBALS['avix_test']['filters'][ $hook ][ $priority ][] = array( $callback, $args );
		return true;
	}
	function add_action( $hook, $callback, $priority = 10, $args = 1 ) {
		return add_filter( $hook, $callback, $priority, $args );
	}
	function apply_filters( $hook, $value, ...$rest ) {
		if ( empty( $GLOBALS['avix_test']['filters'][ $hook ] ) ) {
			return $value;
		}
		$by_priority = $GLOBALS['avix_test']['filters'][ $hook ];
		ksort( $by_priority );
		foreach ( $by_priority as $callbacks ) {
			foreach ( $callbacks as $cb ) {
				$value = call_user_func_array( $cb[0], array_slice( array_merge( array( $value ), $rest ), 0, $cb[1] ) );
			}
		}
		return $value;
	}
	function is_admin() {
		return false;
	}
	function get_option( $name, $default = false ) {
		return array_key_exists( $name, $GLOBALS['avix_test']['options'] ) ? $GLOBALS['avix_test']['options'][ $name ] : $default;
	}
	function home_url( $path = '' ) {
		return 'https://avixdigital.com' . ( '' === $path ? '' : '/' . ltrim( $path, '/' ) );
	}
	function trailingslashit( $s ) {
		return rtrim( $s, '/\\' ) . '/';
	}
	function untrailingslashit( $s ) {
		return rtrim( $s, '/\\' );
	}
	function sanitize_title( $s ) {
		$s = strtolower( trim( strip_tags( (string) $s ) ) );
		$s = preg_replace( '/[^a-z0-9\s-]/', '', $s );
		return trim( preg_replace( '/[\s-]+/', '-', $s ), '-' );
	}
	function sanitize_text_field( $s ) {
		return trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $s ) ) );
	}
	function sanitize_textarea_field( $s ) {
		return trim( wp_strip_all_tags( (string) $s ) );
	}
	function sanitize_email( $s ) {
		return filter_var( $s, FILTER_VALIDATE_EMAIL ) ? $s : '';
	}
	function esc_url_raw( $url, $protocols = null ) {
		$url = trim( (string) $url );
		if ( '' === $url ) {
			return '';
		}
		if ( preg_match( '#^([a-z][a-z0-9+.-]*):#i', $url, $m ) && ! in_array( strtolower( $m[1] ), $protocols ? $protocols : array( 'http', 'https' ), true ) ) {
			return '';
		}
		return str_replace( array( ' ', '"', "'", '<', '>' ), '', $url );
	}
	function wp_parse_url( $url, $component = -1 ) {
		return parse_url( $url, $component );
	}
	function wp_strip_all_tags( $s ) {
		return trim( strip_tags( preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', (string) $s ) ) );
	}
	function wp_hash( $s ) {
		return md5( 'salt' . $s );
	}
	function wp_json_encode( $data, $flags = 0 ) {
		return json_encode( $data, $flags );
	}
	function absint( $n ) {
		return abs( (int) $n );
	}
	function is_singular( $type = '' ) {
		if ( 'post' === $type ) {
			return $GLOBALS['avix_test']['singular_post'];
		}
		return $GLOBALS['avix_test']['singular'];
	}
	function is_author() {
		return $GLOBALS['avix_test']['author'];
	}
	function did_action( $hook ) {
		return 0;
	}
	function get_queried_object_id() {
		return $GLOBALS['avix_test']['queried'];
	}
	function get_queried_object() {
		$p              = new WP_Post();
		$p->ID          = $GLOBALS['avix_test']['queried'];
		$p->post_author = $GLOBALS['avix_test']['post_author'];
		return $p;
	}
	function get_permalink( $id ) {
		$map = array(
			6252 => 'https://avixdigital.com/blog/',
			7965 => 'https://avixdigital.com/service/web-development/',
			8855 => 'https://avixdigital.com/website-vs-web-application/',
		);
		return isset( $map[ $id ] ) ? $map[ $id ] : false;
	}
	function get_post_status( $id ) {
		return 6252 === (int) $id ? 'publish' : false;
	}
	function get_page_by_path( $path ) {
		if ( 'blog' !== $path ) {
			return null;
		}
		$p     = new WP_Post();
		$p->ID = 6252;
		return $p;
	}
	function get_post_meta( $id, $key, $single = false ) {
		return '';
	}
	function get_the_title( $id ) {
		return 6252 === (int) $id ? 'Blog &#038; News' : '';
	}
	function get_userdata( $id ) {
		if ( 1 !== (int) $id ) {
			return false;
		}
		return (object) array( 'user_login' => 'akib' );
	}
	function wp_get_attachment_image_src( $id, $size = 'full' ) {
		return 42 === (int) $id ? array( 'https://avixdigital.com/wp-content/uploads/akib.webp', 800, 800 ) : false;
	}
	function wp_get_attachment_image_url( $id, $size = 'full' ) {
		return 77 === (int) $id ? 'https://avixdigital.com/wp-content/uploads/hero.webp' : false;
	}
	function get_the_post_thumbnail_url( $id, $size = 'full' ) {
		return 'https://avixdigital.com/wp-content/uploads/featured.webp';
	}
	function wp_attachment_is_image( $id ) {
		return 42 === (int) $id;
	}
	class WP_Post {
		public $ID          = 0;
		public $post_author = 0;
	}

	$root = dirname( __DIR__ );
	require $root . '/includes/seo/class-entity.php';
	require $root . '/includes/seo/class-person.php';
	require $root . '/includes/seo/class-service-piece.php';
	require $root . '/includes/seo/class-breadcrumbs.php';
	require $root . '/includes/case-studies/class-seo.php';

	use AvixWidgets\SEO\Entity;
	use AvixWidgets\SEO\Person;
	use AvixWidgets\SEO\Service_Piece;
	use AvixWidgets\SEO\Breadcrumbs;

	Entity::init();
	Person::init();
	Service_Piece::init();
	Breadcrumbs::init();

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

	/**
	 * Yoast's generator, reduced: same filters, same order, pieces keyed by identifier.
	 */
	function yoast_graph( $context ) {
		$pieces = array(
			new \Yoast\WP\SEO\Generators\Schema\Article(),
			new \Yoast\WP\SEO\Generators\Schema\WebPage(),
			new \Yoast\WP\SEO\Generators\Schema\Organization(),
			new \Yoast\WP\SEO\Generators\Schema\Author(),
		);
		$pieces = apply_filters( 'wpseo_schema_graph_pieces', $pieces, $context );
		$todo   = array();
		foreach ( $pieces as $piece ) {
			$piece->context = $context;
			$id             = isset( $piece->identifier ) ? $piece->identifier : strtolower( str_replace( 'Yoast\WP\SEO\Generators\Schema\\', '', get_class( $piece ) ) );
			if ( apply_filters( 'wpseo_schema_needs_' . $id, $piece->is_needed() ) ) {
				$todo[ $id ] = $piece;
			}
		}
		$graph = array();
		foreach ( $todo as $id => $piece ) {
			$data = $piece->generate();
			if ( ! is_array( $data ) ) {
				continue;
			}
			$data  = apply_filters( 'wpseo_schema_' . $id, $data, $context, $piece, $todo );
			$types = is_array( $data ) && isset( $data['@type'] ) ? (array) $data['@type'] : array();
			foreach ( $types as $type ) {
				if ( strtolower( $type ) !== $id ) {
					$data = apply_filters( 'wpseo_schema_' . strtolower( $type ), $data, $context, $piece, $todo );
				}
			}
			if ( is_array( $data ) ) {
				$graph[] = $data;
			}
		}
		return $graph;
	}

	function nodes_of_type( array $graph, $type ) {
		return array_values(
			array_filter(
				$graph,
				static function ( $n ) use ( $type ) {
					return in_array( $type, (array) ( $n['@type'] ?? array() ), true );
				}
			)
		);
	}

	$founder_id = 'https://avixdigital.com/#person-akib-zawayed';

	/* ---------------- A1: entity data ---------------- */

	$e = Entity::get();
	check( '' === $e['address_street'] && '' === $e['address_locality'], 'Defaults: address empty (O2)' );
	check( '+880 1904 187508' === $e['telephone'] && 'info@avixdigital.com' === $e['contact_email'] && '2024' === $e['founding_date'], 'Defaults: telephone, email and founding year (lead decision)' );
	check( 'AvixDigital is a web design and development team building Shopify, WordPress, Webflow and custom web applications for brands in the Netherlands, the EU, the UK and the US.' === $e['org_description'], 'Defaults: Organization description (lead decision)' );
	check( array( 'European Union', 'Netherlands', 'Belgium', 'Germany', 'United Kingdom', 'United States' ) === $e['area_served'], 'Defaults: areas served from the plan' );
	check( 12 === count( $e['knows_about'] ) && 'Brand identity' === end( $e['knows_about'] ), 'Defaults: 12 knowsAbout topics' );
	check( 'Founder & CEO' === $e['founder_job_title'] && 'MD Akib Zawayed' === $e['founder_alternate'] && 6 === count( $e['founder_same_as'] ), 'Defaults: founder fields' );
	check( array( 1 ) === $e['founder_user_ids'] && 0 === $e['founder_image_id'], 'Defaults: user 1, no portrait (O4)' );

	$n = Entity::normalize( array( 'knows_about' => array(), 'founder_slug' => '', 'founder_user_ids' => array( '3', 'x', 3 ), 'junk' => 'x' ) );
	check( array() === $n['knows_about'], 'A saved empty list stays empty (no default refill)' );
	check( 'akib-zawayed' === $n['founder_slug'], 'An empty Person ID falls back to akib-zawayed' );
	check( ! isset( $n['junk'] ), 'Unknown keys dropped' );

	$clean = Entity::sanitize(
		array(
			'address_locality'  => 'Mymensingh <b>x</b>',
			'telephone'         => '+880 1904 <script>1</script>187508',
			'org_description'   => "Line one <b>bold</b>\n  line two",
			'founding_date'     => '24',
			'contact_email'     => 'nope',
			'contact_languages' => "English, Dutch\nEnglish",
			'area_served'       => "Place: Benelux\nNetherlands\n\n",
			'org_same_as'       => "https://clutch.co/x\njavascript:alert(1)\nnot a url\nhttps://CLUTCH.co/x/",
			'founder_name'      => 'Akib Zawayed',
			'founder_url'       => 'javascript:alert(1)',
			'founder_image_id'  => '41',
			'founder_slug'      => 'Akib Zawayed!',
			'founder_user_ids'  => '1, abc, 0, 7',
		)
	);
	check( 'Mymensingh x' === $clean['address_locality'], 'Sanitize: tags stripped' );
	check( 'Line one bold line two' === $clean['org_description'], 'Sanitize: description is one line of plain text', $clean['org_description'] );
	check( '' === $clean['founding_date'] && '2024' === Entity::founding_date( '2024' ) && '2024-03-15' === Entity::founding_date( ' 2024-03-15 ' ) && '' === Entity::founding_date( '2024-13' ) && '' === Entity::founding_date( 'since 2020' ), 'Sanitize: founding date is ISO 8601 (year, month or day) or empty' );
	check( '+880 1904 187508' === preg_replace( '/\s+/', ' ', $clean['telephone'] ), 'Sanitize: telephone keeps digits, spaces, +', $clean['telephone'] );
	check( '' === $clean['contact_email'] && 'sales' === $clean['contact_type'], 'Sanitize: bad email dropped, contact type defaults to sales' );
	check( array( 'English', 'Dutch' ) === $clean['contact_languages'], 'Sanitize: languages split on commas and lines, deduplicated' );
	check( array( 'https://clutch.co/x' ) === $clean['org_same_as'], 'Sanitize: only http(s) URLs, duplicates merged', $clean['org_same_as'] );
	check( '' === $clean['founder_url'] && 0 === $clean['founder_image_id'], 'Sanitize: unsafe URL and non-image ID dropped' );
	check( 'akib-zawayed' === $clean['founder_slug'] && array( 1, 7 ) === $clean['founder_user_ids'], 'Sanitize: slug and user IDs' );
	check( Entity::defaults() === Entity::sanitize( array( 'reset' => '1', 'founder_name' => 'X' ) ), 'Sanitize: reset returns the defaults' );
	check( '/about-avixdigital/' === Entity::link( '/about-avixdigital/' ) && '' === Entity::link( '//evil.example/' ), 'Links: site paths kept, protocol-relative refused' );

	/* ---------------- A2: Organization ---------------- */

	check( array( '@type' => 'Country', 'name' => 'Netherlands' ) === Entity::area_node( 'Netherlands' ), 'Area: country name → Country' );
	check( array( '@type' => 'Place', 'name' => 'European Union' ) === Entity::area_node( 'European Union' ), 'Area: EU → Place' );
	check( 'Country' === Entity::area_node( 'united kingdom' )['@type'] && 'Country' === Entity::area_node( 'UK' )['@type'], 'Area: case-insensitive, aliases' );
	check( array( '@type' => 'Place', 'name' => 'Benelux' ) === Entity::area_node( 'Place: Benelux' ) && 'Country' === Entity::area_node( 'country: Flanders' )['@type'], 'Area: prefix forces the type' );
	check( array() === Entity::area_node( '  ' ), 'Area: blank → nothing' );

	$empty = Entity::enrich(
		array( '@type' => 'Organization' ),
		array(
			'org_description'   => '',
			'founding_date'     => '',
			'contact_email'     => '',
			'telephone'         => '',
			'contact_languages' => array(),
			'area_served'       => array(),
			'knows_about'       => array(),
			'org_same_as'       => array(),
		),
		''
	);
	check( array( '@type' => 'Organization' ) === $empty, 'Empty entity fields add nothing', $empty );
	check( array() === Entity::postal_address( Entity::defaults() ), 'No PostalAddress while the address is empty' );
	$addr = Entity::postal_address( array( 'address_locality' => 'Mymensingh', 'address_country' => 'BD' ) );
	check( array( '@type' => 'PostalAddress', 'addressLocality' => 'Mymensingh', 'addressCountry' => 'BD' ) === $addr, 'PostalAddress prints only filled fields' );
	$kept = Entity::enrich( array( 'contactPoint' => array( 'x' ), 'sameAs' => array( 'https://www.instagram.com/avixdigital_agency' ) ), array_merge( Entity::defaults(), array( 'org_same_as' => array( 'https://www.instagram.com/avixdigital_agency/', 'https://clutch.co/profile/avixdigital' ) ) ), $founder_id );
	check( array( 'x' ) === $kept['contactPoint'], 'Existing node fields are never overwritten' );
	check( array( 'https://www.instagram.com/avixdigital_agency', 'https://clutch.co/profile/avixdigital' ) === $kept['sameAs'], 'sameAs merged without duplicates', $kept['sameAs'] );
	check( '+880 1904 187508' === $kept['telephone'] && 'info@avixdigital.com' === $kept['email'] && '2024' === $kept['foundingDate'] && 0 === strpos( $kept['description'], 'AvixDigital is a web design' ), 'Organization: description, email, telephone and foundingDate from the entity (Yoast Free does not print them)' );
	$yoast_set = Entity::enrich( array( 'description' => 'Yoast text', 'email' => 'hello@example.com', 'telephone' => '+31 1', 'foundingDate' => '2020' ), Entity::defaults(), '' );
	check( 'Yoast text' === $yoast_set['description'] && 'hello@example.com' === $yoast_set['email'] && '+31 1' === $yoast_set['telephone'] && '2020' === $yoast_set['foundingDate'], 'Organization: values Yoast already prints are kept' );
	$cleared = Entity::enrich( array(), array_merge( Entity::defaults(), array( 'org_description' => '', 'contact_email' => '', 'telephone' => '', 'founding_date' => '' ) ), '' );
	check( ! isset( $cleared['description'] ) && ! isset( $cleared['email'] ) && ! isset( $cleared['telephone'] ) && ! isset( $cleared['foundingDate'] ) && ! isset( $cleared['contactPoint'] ), 'Organization: emptied fields are never printed', $cleared );

	/* ---------------- A3/A5: Yoast graph on a service page ---------------- */

	$GLOBALS['avix_test']['queried'] = 7965;
	$context                         = (object) array( 'canonical' => 'https://avixdigital.com/service/web-development/' );
	// No Elementor here: feed the Service piece the hero settings it would read from the page.
	$service = Service_Piece::build(
		array(
			'title'          => 'Custom Web [Development]',
			'text'           => 'Lead <b>text</b> &amp; more.',
			'service_schema' => 'yes',
			'service_type'   => 'Custom web development',
			'area_served'    => "European Union\nUnited Kingdom\r\nUnited States\nBenelux",
			'visual'         => 'image',
			'image'          => array( 'id' => 77 ),
		),
		'https://avixdigital.com/service/web-development/',
		true
	);
	check( 'Custom Web Development' === $service['name'], 'Service: name from the headline without brackets', $service['name'] );
	check( 'Lead text & more.' === $service['description'], 'Service: description from the lead, plain text' );
	check( 'https://avixdigital.com/service/web-development/#service' === $service['@id'], 'Service: @id <page>#service' );
	check( array( 'Place', 'Country', 'Country', 'Place' ) === array_column( $service['areaServed'], '@type' ), 'Service: Country for countries, Place otherwise' );
	check( 'https://avixdigital.com/wp-content/uploads/hero.webp' === $service['image'], 'Service: hero image' );
	check( array( '@id' => 'https://avixdigital.com/#organization' ) === $service['provider'], 'Service: provider → #organization' );
	check( array( '@id' => 'https://avixdigital.com/service/web-development/' ) === $service['mainEntityOfPage'], 'Service: mainEntityOfPage reference in the graph' );
	$standalone = Service_Piece::build( array( 'service_name' => 'X', 'visual' => 'orbit' ), 'https://avixdigital.com/service/web-development/', false );
	check( 'https://avixdigital.com/service/web-development/' === $standalone['mainEntityOfPage'] && 'https://avixdigital.com/wp-content/uploads/featured.webp' === $standalone['image'], 'Service: standalone form; featured image when the hero has no image' );
	check( array() === Service_Piece::build( array( 'title' => '[ ]' ), 'https://x.test/', true ), 'Service: no name → nothing' );

	$tree = array(
		array(
			'elType'   => 'container',
			'elements' => array(
				array( 'elType' => 'widget', 'widgetType' => 'avix-page-hero', 'settings' => array( 'service_schema' => '' ) ),
				array( 'elType' => 'widget', 'widgetType' => 'template', 'settings' => array( 'template_id' => 5 ) ),
			),
		),
	);
	$found = Service_Piece::find_element(
		$tree,
		static function ( $id ) {
			return 5 === (int) $id ? array( array( 'id' => 'tpl', 'elType' => 'widget', 'widgetType' => 'avix-page-hero', 'settings' => array( 'service_schema' => 'yes' ) ) ) : array();
		}
	);
	check( 'tpl' === ( $found['id'] ?? '' ), 'Service: finds the first hero with the switch on, also inside a template' );
	check( array() === Service_Piece::find_element( array( array( 'widgetType' => 'avix-page-hero', 'settings' => array() ) ) ), 'Service: switch off → no element' );

	// Service graph piece (loaded lazily, as in wpseo_schema_graph_pieces) with a known node.
	require dirname( __DIR__ ) . '/includes/seo/schema/class-service-graph-piece.php';
	$prop = new ReflectionProperty( Service_Piece::class, 'current' );
	if ( PHP_VERSION_ID < 80100 ) {
		$prop->setAccessible( true );
	}
	$prop->setValue( null, $service );
	$graph = yoast_graph( $context );
	$orgs  = nodes_of_type( $graph, 'Organization' );
	check( 1 === count( $orgs ), 'Graph: exactly one Organization node' );
	$org = $orgs[0];
	check( 'Organization' === $org['@type'], 'Graph: @type stays Organization' );
	check( array( '@id' => $founder_id ) === $org['founder'], 'Graph: Organization.founder → founder @id' );
	check( isset( $org['contactPoint']['email'] ) && 'info@avixdigital.com' === $org['contactPoint']['email'] && '+880 1904 187508' === ( $org['contactPoint']['telephone'] ?? '' ), 'Graph: contactPoint with the email and telephone' );
	check( '2024' === ( $org['foundingDate'] ?? '' ) && 'info@avixdigital.com' === ( $org['email'] ?? '' ) && '+880 1904 187508' === ( $org['telephone'] ?? '' ) && '' !== ( $org['description'] ?? '' ), 'Graph: Organization description, email, telephone, foundingDate' );
	check( 6 === count( $org['areaServed'] ) && 12 === count( $org['knowsAbout'] ), 'Graph: areaServed and knowsAbout' );
	check( ! isset( $org['address'] ) && ! isset( $org['aggregateRating'] ) && ! isset( $org['review'] ), 'Graph: no address, no AggregateRating/Review for the business' );
	$people = nodes_of_type( $graph, 'Person' );
	check( 1 === count( $people ) && $founder_id === $people[0]['@id'], 'Graph: one Person, the founder' );
	check( ! isset( $people[0]['image'] ) && 'https://avixdigital.com/about-avixdigital/' === $people[0]['url'], 'Graph: founder without portrait, url = About page' );
	check( array( '@id' => 'https://avixdigital.com/#organization' ) === $people[0]['worksFor'], 'Graph: worksFor is an @id reference' );
	$services = nodes_of_type( $graph, 'Service' );
	check( 1 === count( $services ) && Service_Piece::in_graph(), 'Graph: Service piece printed (widget then prints nothing)' );
	$pages = nodes_of_type( $graph, 'WebPage' );
	check( array( '@id' => $service['@id'] ) === ( $pages[0]['mainEntity'] ?? null ), 'Graph: WebPage.mainEntity → #service' );
	check( Person::is_printed( $founder_id ), 'Graph: founder @id registered as printed' );

	/* ---------------- A4: widget Person nodes ---------------- */

	$nodes = Person::widget_nodes(
		array(
			array( 'name' => 'Akib Zawayed', 'jobTitle' => 'CEO, Lead Developer' ),
			array( 'name' => 'Ayesha Akter', 'jobTitle' => 'Designer, Manager', 'link' => '#' ),
			array( 'name' => 'Nurul Hasnat', 'jobTitle' => 'Lead UI/UX Designer', 'link' => '/about-avixdigital/', 'image' => 'https://avixdigital.com/n.webp' ),
			array( 'name' => 'MD Akib Zawayed', 'jobTitle' => 'Lead UIUX Designer' ),
			array( 'name' => 'Ranjan Deb', 'link' => 'https://www.linkedin.com/in/ranjan/' ),
			array( 'name' => 'Nurul Hasnat' ),
		)
	);
	$ids = array_column( $nodes, '@id' );
	check( array( 'https://avixdigital.com/#person-ayesha-akter', 'https://avixdigital.com/#person-nurul-hasnat', 'https://avixdigital.com/#person-ranjan-deb' ) === $ids, 'Widgets: founder cards add nothing after the graph; others once each', $ids );
	check( ! isset( $nodes[0]['url'] ) && ! isset( $nodes[0]['sameAs'] ), 'Widgets: a "#" link gives no url' );
	check( 'https://avixdigital.com/about-avixdigital/' === $nodes[1]['url'] && 'https://www.linkedin.com/in/ranjan/' === $nodes[2]['sameAs'][0], 'Widgets: own-site link → url, other site → sameAs' );
	check( array( '@id' => 'https://avixdigital.com/#organization' ) === $nodes[0]['worksFor'], 'Widgets: worksFor → #organization (no inline Organization)' );

	Person::reset_registry();
	$nodes = Person::widget_nodes( array( array( 'name' => 'Someone Else', 'slug' => 'akib-zawayed', 'jobTitle' => 'X' ), array( 'name' => 'Akib Zawayed', 'jobTitle' => 'CEO, Avix Digital' ) ) );
	check( 1 === count( $nodes ) && $founder_id === $nodes[0]['@id'] && 'Founder & CEO' === $nodes[0]['jobTitle'], 'Widgets without the graph: the canonical founder once, never the card\'s job title' );
	check( Person::matches_founder( Entity::get(), 'MD Akib Zawayed' ) && ! Person::matches_founder( Entity::get(), 'Nurul Hasnat' ) && ! Person::matches_founder( array( 'founder_name' => '' ), 'Akib Zawayed' ), 'Founder match: name, alternate name, no founder' );
	$built = Person::build_founder( array_merge( Entity::defaults(), array( 'founder_alternate' => '', 'founder_job_title' => '', 'founder_same_as' => array(), 'founder_url' => '' ) ), 'https://avixdigital.com/', array( 'url' => 'https://avixdigital.com/a.webp', 'width' => 800, 'height' => 800 ) );
	check( ! isset( $built['alternateName'] ) && ! isset( $built['jobTitle'] ) && ! isset( $built['sameAs'] ) && ! isset( $built['url'] ), 'Founder: empty fields left out' );
	check( 'ImageObject' === $built['image']['@type'] && 800 === $built['image']['width'], 'Founder: image only when a portrait is set' );

	/* ---------------- A3: blog post author ---------------- */

	Person::reset_registry();
	$prop->setValue( null, array() );
	$GLOBALS['avix_test']['singular_post'] = true;
	$GLOBALS['avix_test']['queried']       = 8855;
	$graph                                 = yoast_graph( (object) array( 'canonical' => 'https://avixdigital.com/website-vs-web-application/' ) );
	$articles                              = nodes_of_type( $graph, 'Article' );
	check( array( 'name' => 'Akib Zawayed', '@id' => $founder_id ) === $articles[0]['author'], 'Post: Article.author → founder @id', $articles[0]['author'] );
	$people = nodes_of_type( $graph, 'Person' );
	check( 1 === count( $people ) && $founder_id === $people[0]['@id'], 'Post: no second Person for user 1' );
	$hash = 0;
	foreach ( $graph as $node ) {
		if ( in_array( 'Person', (array) $node['@type'], true ) && isset( $node['url'] ) && '#' === $node['url'] ) {
			++$hash;
		}
	}
	check( 0 === $hash, 'Post: no Person with url "#"' );

	// A post by another user keeps Yoast's author, but never url "#".
	$GLOBALS['avix_test']['post_author'] = 2;
	check( true === Person::needs_author( true ), 'Other authors: Yoast author piece still needed' );
	$other = Person::filter_person( array( '@type' => 'Person', '@id' => 'https://avixdigital.com/#/schema/person/other', 'url' => '#' ) );
	check( is_array( $other ) && ! isset( $other['url'] ), 'Other authors: url "#" removed, node kept' );
	check( is_array( Person::filter_person( array( '@type' => array( 'Person', 'Organization' ), '@id' => 'https://avixdigital.com/#/schema/person/' . wp_hash( 'akib1' ) ) ) ), 'Site represents a person: that Person/Organization node is kept' );
	$GLOBALS['avix_test']['author'] = true;
	check( is_array( Person::filter_person( array( '@type' => 'Person', '@id' => 'https://avixdigital.com/#/schema/person/' . wp_hash( 'akib1' ) ) ) ), 'Author archive: Yoast\'s Person for the profile page is kept' );
	$GLOBALS['avix_test']['author'] = false;

	/* ---------------- A7: Home › Blog › Post ---------------- */

	$crumbs = Breadcrumbs::filter(
		array(
			array( 'url' => 'https://avixdigital.com/', 'text' => 'Home' ),
			array( 'url' => 'https://avixdigital.com/website-vs-web-application/', 'text' => 'Website vs Web Application' ),
		)
	);
	check( array( 'Home', 'Blog & News', 'Website vs Web Application' ) === array_column( $crumbs, 'text' ), 'Breadcrumbs: Blog inserted, title as plain text', $crumbs );
	check( $crumbs === Breadcrumbs::insert_crumb( $crumbs, 'https://avixdigital.com/blog', 'Blog', 6252 ), 'Breadcrumbs: never inserted twice' );
	check( 2 === count( Breadcrumbs::insert_crumb( array( array( 'url' => 'https://avixdigital.com/', 'text' => 'Home' ) ), 'https://avixdigital.com/blog/', 'Blog' ) ), 'Breadcrumbs: Home only → Home › Blog' );
	$GLOBALS['avix_test']['singular_post'] = false;
	$page_crumbs                           = array( array( 'url' => 'https://avixdigital.com/', 'text' => 'Home' ), array( 'url' => 'https://avixdigital.com/contact/', 'text' => 'Contact' ) );
	check( $page_crumbs === Breadcrumbs::filter( $page_crumbs ), 'Breadcrumbs: pages unchanged' );

	/* ---------------- A9a: case-study Service references ---------------- */

	$ids  = AvixWidgets\Case_Studies\SEO::service_ids();
	$refs = AvixWidgets\Case_Studies\SEO::service_refs( array( 'shopify', 'webflow', 'web-development', 'unknown', 'uiux-brand-design' ), $ids );
	check(
		array(
			array( '@id' => 'https://avixdigital.com/service/shopify-plus/#service' ),
			array( '@id' => 'https://avixdigital.com/service/web-development/#service' ),
			array( '@id' => 'https://avixdigital.com/service/uiux-and-brand-design/#service' ),
		) === $refs,
		'Case studies: service terms → Service @ids, deduplicated, unknown skipped',
		$refs
	);
	check( 'https://avixdigital.com/service/wordpress-development/#service' === $ids['wordpress'], 'Case studies: WordPress → /service/wordpress-development/#service' );

	echo "\n{$checks} checks, {$failures} failed\n";
	exit( $failures ? 1 : 0 );
}
