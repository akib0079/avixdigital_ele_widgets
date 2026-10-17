<?php
/**
 * SEO entity data (SEO plan A1 and A2).
 *
 * - The option avix_seo_entity: address, contact point, areas served, topics, extra profile
 *   links for the business, and the founder's Person fields. Edited on Tools > "Avix SEO: entity"
 *   (manage_options).
 * - wpseo_schema_organization: adds those fields to Yoast's own Organization node (@type stays
 *   Organization) with founder → the founder Person's @id. Description, email, telephone and
 *   foundingDate are printed from here too, because Yoast SEO Free disables those Site
 *   representation fields; a value Yoast already prints (e.g. with Premium) is kept. Name,
 *   alternateName and logo stay Yoast's. Empty fields are never printed. AggregateRating and
 *   Review are never printed for the business.
 *
 * Without Yoast nothing is printed: every hook here is a Yoast filter or an admin hook.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\SEO;

defined( 'ABSPATH' ) || exit;

final class Entity {

	/** Option name. */
	const OPTION = 'avix_seo_entity';

	/** Settings group (options.php). */
	const GROUP = 'avix_seo_entity_group';

	/** Admin page slug under Tools. */
	const PAGE = 'avix-seo-entity';

	/** @var array|null Normalised option, per request. */
	private static $cache = null;

	/** @var string[]|null Lower-cased country names. */
	private static $countries = null;

	public static function init(): void {
		add_filter( 'wpseo_schema_organization', array( __CLASS__, 'filter_organization' ), 20, 2 );
		add_action( 'add_option_' . self::OPTION, array( __CLASS__, 'flush' ) );
		add_action( 'update_option_' . self::OPTION, array( __CLASS__, 'flush' ) );
		add_action( 'delete_option_' . self::OPTION, array( __CLASS__, 'flush' ) );
		if ( is_admin() ) {
			add_action( 'admin_init', array( __CLASS__, 'register_setting' ) );
			add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		}
	}

	/* ------------------------------------------------------------------ */
	/* Data                                                               */
	/* ------------------------------------------------------------------ */

	/**
	 * Defaults (SEO plan A1, with the lead's decisions of 2026-10-08: founding year, description,
	 * email and telephone). The street address stays empty until the owner confirms one official
	 * address (decision O2).
	 */
	public static function defaults(): array {
		return array(
			'org_description'   => 'AvixDigital is a web design and development team building Shopify, WordPress, Webflow and custom web applications for brands in the Netherlands, the EU, the UK and the US.',
			'founding_date'     => '2024',
			'address_street'    => '',
			'address_locality'  => '',
			'address_postcode'  => '',
			'address_country'   => '',
			'telephone'         => '+880 1904 187508',
			'contact_email'     => 'info@avixdigital.com',
			'contact_type'      => 'sales',
			'contact_languages' => array( 'English' ),
			'area_served'       => array( 'European Union', 'Netherlands', 'Belgium', 'Germany', 'United Kingdom', 'United States' ),
			'knows_about'       => array( 'Shopify', 'Shopify Plus', 'Liquid', 'WordPress', 'WooCommerce', 'Elementor', 'Bricks Builder', 'Webflow', 'React', 'Next.js', 'UI/UX design', 'Brand identity' ),
			'org_same_as'       => array(),
			'founder_name'      => 'Akib Zawayed',
			'founder_alternate' => 'MD Akib Zawayed',
			'founder_job_title' => 'Founder & CEO',
			'founder_url'       => '/about-avixdigital/',
			'founder_image_id'  => 0,
			'founder_same_as'   => array(
				'https://www.linkedin.com/in/akib0079/',
				'https://github.com/akib0079',
				'https://www.behance.net/akib0079',
				'https://www.fiverr.com/akib0079',
				'https://www.upwork.com/freelancers/akibzawayed',
				'https://www.youtube.com/@akib0079',
			),
			'founder_slug'      => 'akib-zawayed',
			'founder_user_ids'  => array( 1 ),
		);
	}

	/**
	 * The entity data: saved values over the defaults (a saved empty value stays empty).
	 */
	public static function get(): array {
		if ( null === self::$cache ) {
			$saved       = get_option( self::OPTION, array() );
			self::$cache = self::normalize( is_array( $saved ) ? $saved : array() );
		}
		return self::$cache;
	}

	/**
	 * Forgets the per-request copy (after the option changes).
	 */
	public static function flush(): void {
		self::$cache = null;
	}

	/**
	 * Pure helper (unit-testable): saved values merged over the defaults, each field in its type.
	 * Unknown keys are dropped.
	 *
	 * @param array $saved Stored option.
	 */
	public static function normalize( array $saved ): array {
		$out = array();
		foreach ( self::defaults() as $key => $default ) {
			$value = array_key_exists( $key, $saved ) ? $saved[ $key ] : $default;
			if ( is_int( $default ) ) {
				$out[ $key ] = max( 0, (int) ( is_scalar( $value ) ? $value : 0 ) );
			} elseif ( is_array( $default ) ) {
				$list = array();
				foreach ( is_array( $value ) ? $value : array() as $item ) {
					if ( is_scalar( $item ) && '' !== trim( (string) $item ) ) {
						$list[] = 'founder_user_ids' === $key ? (int) $item : trim( (string) $item );
					}
				}
				$out[ $key ] = array_values( array_unique( $list ) );
			} else {
				$out[ $key ] = is_scalar( $value ) ? trim( (string) $value ) : '';
			}
		}
		if ( '' === $out['founder_slug'] ) {
			$out['founder_slug'] = 'akib-zawayed';
		}
		return $out;
	}

	/* ------------------------------------------------------------------ */
	/* Organization (Yoast)                                               */
	/* ------------------------------------------------------------------ */

	/**
	 * wpseo_schema_organization: adds the entity fields to the site's Organization node only.
	 *
	 * @param mixed $data    Organization piece.
	 * @param mixed $context Yoast Meta_Tags_Context.
	 * @return mixed
	 */
	public static function filter_organization( $data, $context = null ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}
		$id = isset( $data['@id'] ) && is_string( $data['@id'] ) ? $data['@id'] : '';
		if ( '' !== $id && '#organization' !== substr( $id, -13 ) ) {
			return $data;
		}
		$founder = class_exists( __NAMESPACE__ . '\Person' ) ? Person::founder_id() : '';
		return self::enrich( $data, self::get(), $founder );
	}

	/**
	 * Pure helper (unit-testable): the Organization node with the entity fields added. Fields the
	 * node already has are kept; empty entity fields add nothing.
	 *
	 * @param array  $org        Organization node.
	 * @param array  $entity     Normalised entity data.
	 * @param string $founder_id The founder Person's @id ('' = no founder link).
	 */
	public static function enrich( array $org, array $entity, string $founder_id = '' ): array {
		$entity = self::normalize( $entity );
		// Yoast SEO Free greys out these Site representation fields, so they come from here.
		foreach ( array(
			'description'  => 'org_description',
			'email'        => 'contact_email',
			'telephone'    => 'telephone',
			'foundingDate' => 'founding_date',
		) as $prop => $key ) {
			if ( empty( $org[ $prop ] ) && '' !== $entity[ $key ] ) {
				$org[ $prop ] = $entity[ $key ];
			}
		}
		if ( empty( $org['address'] ) ) {
			$address = self::postal_address( $entity );
			if ( $address ) {
				$org['address'] = $address;
			}
		}
		if ( empty( $org['contactPoint'] ) ) {
			$point = self::contact_point( $entity );
			if ( $point ) {
				$org['contactPoint'] = $point;
			}
		}
		if ( empty( $org['areaServed'] ) ) {
			$areas = self::area_nodes( $entity['area_served'] );
			if ( $areas ) {
				$org['areaServed'] = $areas;
			}
		}
		if ( empty( $org['knowsAbout'] ) && $entity['knows_about'] ) {
			$org['knowsAbout'] = $entity['knows_about'];
		}
		if ( empty( $org['founder'] ) && '' !== $founder_id ) {
			$org['founder'] = array( '@id' => $founder_id );
		}
		$same_as = self::merge_urls( isset( $org['sameAs'] ) ? (array) $org['sameAs'] : array(), $entity['org_same_as'] );
		if ( $same_as ) {
			$org['sameAs'] = $same_as;
		}
		// Self-serving review markup for the business is never printed (Google ignores or penalises it).
		unset( $org['aggregateRating'], $org['review'] );
		return $org;
	}

	/**
	 * PostalAddress from the address fields, or array() when all are empty.
	 *
	 * @param array $entity Normalised entity data.
	 */
	public static function postal_address( array $entity ): array {
		$map     = array(
			'address_street'   => 'streetAddress',
			'address_locality' => 'addressLocality',
			'address_postcode' => 'postalCode',
			'address_country'  => 'addressCountry',
		);
		$address = array();
		foreach ( $map as $key => $prop ) {
			$value = isset( $entity[ $key ] ) ? trim( (string) $entity[ $key ] ) : '';
			if ( '' !== $value ) {
				$address[ $prop ] = $value;
			}
		}
		return $address ? array( '@type' => 'PostalAddress' ) + $address : array();
	}

	/**
	 * ContactPoint, or array() without an email or telephone.
	 *
	 * @param array $entity Normalised entity data.
	 */
	public static function contact_point( array $entity ): array {
		$email = isset( $entity['contact_email'] ) ? trim( (string) $entity['contact_email'] ) : '';
		$phone = isset( $entity['telephone'] ) ? trim( (string) $entity['telephone'] ) : '';
		if ( '' === $email && '' === $phone ) {
			return array();
		}
		$type  = isset( $entity['contact_type'] ) && '' !== trim( (string) $entity['contact_type'] ) ? trim( (string) $entity['contact_type'] ) : 'sales';
		$point = array(
			'@type'       => 'ContactPoint',
			'contactType' => $type,
		);
		if ( '' !== $email ) {
			$point['email'] = $email;
		}
		if ( '' !== $phone ) {
			$point['telephone'] = $phone;
		}
		$languages = isset( $entity['contact_languages'] ) ? array_values( array_filter( (array) $entity['contact_languages'], 'strlen' ) ) : array();
		if ( $languages ) {
			$point['availableLanguage'] = $languages;
		}
		return $point;
	}

	/**
	 * areaServed nodes for a list of names.
	 *
	 * @param array $names One area per item.
	 */
	public static function area_nodes( array $names ): array {
		$nodes = array();
		foreach ( $names as $name ) {
			$node = self::area_node( (string) $name );
			if ( $node ) {
				$nodes[] = $node;
			}
		}
		return $nodes;
	}

	/**
	 * Pure helper (unit-testable): one area as a schema.org Country when the name is a country,
	 * else a Place. A "Country:", "Place:", "AdministrativeArea:", "State:" or "City:" prefix
	 * forces the type.
	 *
	 * @param string $name Area name.
	 */
	public static function area_node( string $name ): array {
		$name = trim( (string) preg_replace( '/\s+/u', ' ', $name ) );
		if ( '' === $name ) {
			return array();
		}
		$type = '';
		if ( preg_match( '/^(Country|Place|AdministrativeArea|State|City)\s*:\s*(.+)$/i', $name, $m ) ) {
			$types = array(
				'country'            => 'Country',
				'place'              => 'Place',
				'administrativearea' => 'AdministrativeArea',
				'state'              => 'State',
				'city'               => 'City',
			);
			$type  = $types[ strtolower( $m[1] ) ];
			$name  = trim( $m[2] );
		}
		if ( '' === $type ) {
			$type = self::is_country( $name ) ? 'Country' : 'Place';
		}
		return array(
			'@type' => $type,
			'name'  => $name,
		);
	}

	/**
	 * True when the name is a country (English short name or a common alias).
	 *
	 * @param string $name Area name.
	 */
	public static function is_country( string $name ): bool {
		if ( null === self::$countries ) {
			$file            = __DIR__ . '/schema/countries.php';
			$list            = file_exists( $file ) ? include $file : array();
			self::$countries = array();
			foreach ( is_array( $list ) ? $list : array() as $country ) {
				self::$countries[ self::lower( (string) $country ) ] = true;
			}
		}
		return isset( self::$countries[ self::lower( trim( $name ) ) ] );
	}

	/**
	 * Pure helper (unit-testable): URL lists merged in order without duplicates (scheme and host
	 * case and a trailing slash are ignored when comparing).
	 *
	 * @param array $first  URLs kept first (e.g. Yoast's sameAs).
	 * @param array $second URLs added after.
	 */
	public static function merge_urls( array $first, array $second ): array {
		$out  = array();
		$seen = array();
		foreach ( array_merge( $first, $second ) as $url ) {
			if ( ! is_string( $url ) || '' === trim( $url ) ) {
				continue;
			}
			$url = trim( $url );
			$key = self::url_key( $url );
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			$out[]        = $url;
		}
		return $out;
	}

	private static function url_key( string $url ): string {
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) ) {
			return rtrim( $url, '/' );
		}
		$key = strtolower( $parts['host'] ) . ( isset( $parts['path'] ) ? rtrim( $parts['path'], '/' ) : '' );
		if ( isset( $parts['query'] ) ) {
			$key .= '?' . $parts['query'];
		}
		return $key;
	}

	private static function lower( string $text ): string {
		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text );
	}

	/* ------------------------------------------------------------------ */
	/* Sanitising                                                         */
	/* ------------------------------------------------------------------ */

	/**
	 * register_setting() sanitize callback: every field from the form, cleaned. A "reset" field
	 * puts the defaults back. options.php has already unslashed the input.
	 *
	 * @param mixed $input Submitted value.
	 */
	public static function sanitize( $input ): array {
		if ( ! is_array( $input ) ) {
			return self::get();
		}
		if ( ! empty( $input['reset'] ) ) {
			return self::defaults();
		}
		$text = static function ( $key ) use ( $input ) {
			return isset( $input[ $key ] ) && is_scalar( $input[ $key ] ) ? sanitize_text_field( (string) $input[ $key ] ) : '';
		};
		$out  = array(
			'org_description'   => isset( $input['org_description'] ) && is_scalar( $input['org_description'] ) ? trim( (string) preg_replace( '/\s+/u', ' ', sanitize_textarea_field( (string) $input['org_description'] ) ) ) : '',
			'founding_date'     => self::founding_date( $text( 'founding_date' ) ),
			'address_street'    => $text( 'address_street' ),
			'address_locality'  => $text( 'address_locality' ),
			'address_postcode'  => $text( 'address_postcode' ),
			'address_country'   => $text( 'address_country' ),
			'telephone'         => trim( (string) preg_replace( '/[^0-9+().\-\s]/', '', $text( 'telephone' ) ) ),
			'contact_email'     => sanitize_email( $text( 'contact_email' ) ),
			'contact_type'      => $text( 'contact_type' ),
			'contact_languages' => self::text_list( $input['contact_languages'] ?? array(), true ),
			'area_served'       => self::text_list( $input['area_served'] ?? array() ),
			'knows_about'       => self::text_list( $input['knows_about'] ?? array() ),
			'org_same_as'       => self::url_list( $input['org_same_as'] ?? array() ),
			'founder_name'      => $text( 'founder_name' ),
			'founder_alternate' => $text( 'founder_alternate' ),
			'founder_job_title' => $text( 'founder_job_title' ),
			'founder_url'       => self::link( $text( 'founder_url' ) ),
			'founder_image_id'  => 0,
			'founder_same_as'   => self::url_list( $input['founder_same_as'] ?? array() ),
			'founder_slug'      => sanitize_title( $text( 'founder_slug' ) ),
			'founder_user_ids'  => array(),
		);
		if ( '' === $out['contact_type'] ) {
			$out['contact_type'] = 'sales';
		}
		if ( '' === $out['founding_date'] && '' !== $text( 'founding_date' ) && function_exists( 'add_settings_error' ) ) {
			add_settings_error( self::OPTION, 'founding_date', __( 'The founding date was not saved: use a year (2024) or a date (2024-03-15).', 'avix-widgets' ) );
		}
		$image = absint( $input['founder_image_id'] ?? 0 );
		if ( $image && function_exists( 'wp_attachment_is_image' ) && wp_attachment_is_image( $image ) ) {
			$out['founder_image_id'] = $image;
		}
		foreach ( self::text_list( $input['founder_user_ids'] ?? array(), true ) as $user ) {
			$user = absint( $user );
			if ( $user ) {
				$out['founder_user_ids'][] = $user;
			}
		}
		return self::normalize( $out );
	}

	/**
	 * Pure helper (unit-testable): a list from a textarea (one per line; with $commas also split
	 * on commas) or an array, each item cleaned, no empties, no duplicates.
	 *
	 * @param mixed $value  Textarea text or array.
	 * @param bool  $commas Also split on commas.
	 */
	public static function text_list( $value, bool $commas = false ): array {
		if ( is_array( $value ) ) {
			$items = $value;
		} else {
			$items = preg_split( $commas ? '/[\r\n,]+/' : '/\r\n|\r|\n/', (string) $value );
		}
		$out = array();
		foreach ( (array) $items as $item ) {
			if ( ! is_scalar( $item ) ) {
				continue;
			}
			$item = sanitize_text_field( (string) $item );
			if ( '' !== $item && ! in_array( $item, $out, true ) ) {
				$out[] = $item;
			}
		}
		return $out;
	}

	/**
	 * A list of absolute http(s) URLs from a textarea or array.
	 *
	 * @param mixed $value Textarea text or array.
	 */
	public static function url_list( $value ): array {
		$out = array();
		foreach ( self::text_list( $value ) as $url ) {
			if ( ! preg_match( '#^https?://#i', $url ) ) {
				continue;
			}
			$url = esc_url_raw( $url, array( 'http', 'https' ) );
			if ( '' !== $url && wp_parse_url( $url, PHP_URL_HOST ) ) {
				$out[] = $url;
			}
		}
		return self::merge_urls( $out, array() );
	}

	/**
	 * Pure helper (unit-testable): an ISO 8601 date as schema.org foundingDate expects it
	 * ("2024", "2024-03" or "2024-03-15"); anything else ''.
	 *
	 * @param string $value Field value.
	 */
	public static function founding_date( string $value ): string {
		$value = trim( $value );
		return preg_match( '/^(1[89]|2[0-9])[0-9]{2}(-(0[1-9]|1[0-2])(-(0[1-9]|[12][0-9]|3[01]))?)?$/', $value ) ? $value : '';
	}

	/**
	 * A site-relative path ("/about-avixdigital/") or an absolute http(s) URL; anything else ''.
	 *
	 * @param string $value Field value.
	 */
	public static function link( string $value ): string {
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}
		if ( '/' === $value[0] && ( ! isset( $value[1] ) || '/' !== $value[1] ) ) {
			$path = (string) wp_parse_url( $value, PHP_URL_PATH );
			return '/' . ltrim( (string) preg_replace( '#[^A-Za-z0-9/_\-.~%]#', '', $path ), '/' );
		}
		if ( ! preg_match( '#^https?://#i', $value ) ) {
			return '';
		}
		$url = esc_url_raw( $value, array( 'http', 'https' ) );
		return '' !== $url && wp_parse_url( $url, PHP_URL_HOST ) ? $url : '';
	}

	/**
	 * Absolute URL for a stored link (site-relative paths resolved against home_url()).
	 *
	 * @param string $value Stored link.
	 */
	public static function absolute( string $value ): string {
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}
		if ( '/' === $value[0] && ( ! isset( $value[1] ) || '/' !== $value[1] ) ) {
			return home_url( $value );
		}
		return $value;
	}

	/* ------------------------------------------------------------------ */
	/* Admin screen: Tools > Avix SEO: entity                             */
	/* ------------------------------------------------------------------ */

	public static function register_setting(): void {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'show_in_rest'      => false,
			)
		);
	}

	public static function admin_menu(): void {
		add_management_page(
			esc_html__( 'Avix SEO: entity', 'avix-widgets' ),
			esc_html__( 'Avix SEO: entity', 'avix-widgets' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render_page' )
		);
	}

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to manage these settings.', 'avix-widgets' ) );
		}
		$e    = self::get();
		$name = static function ( $key ) {
			return self::OPTION . '[' . $key . ']';
		};
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Avix SEO: entity', 'avix-widgets' ); ?></h1>
			<?php settings_errors(); ?>
			<p><?php esc_html_e( 'Facts about the business and its founder that the plugin adds to the Yoast SEO schema graph: to the Organization node, and as one founder Person that blog posts and the team widgets point to. Only fill in facts that are true and confirmed. Empty fields are never printed. Name, logo and social profiles stay in Yoast SEO > Settings > Site representation; description, email, telephone and founding date are printed from here because Yoast SEO Free does not print them (a value Yoast prints itself is kept).', 'avix-widgets' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
				<?php settings_fields( self::GROUP ); ?>
				<h2><?php esc_html_e( 'Organization', 'avix-widgets' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					self::textarea_row( $name( 'org_description' ), __( 'Description', 'avix-widgets' ), $e['org_description'], __( 'One or two plain sentences about the business (Organization.description).', 'avix-widgets' ) );
					self::text_row( $name( 'founding_date' ), __( 'Founding date', 'avix-widgets' ), $e['founding_date'], __( 'Year (2024) or full date (2024-03-15). Empty = not printed.', 'avix-widgets' ) );
					self::text_row( $name( 'contact_email' ), __( 'Email', 'avix-widgets' ), $e['contact_email'], __( 'Printed on the Organization and in its ContactPoint.', 'avix-widgets' ), 'email' );
					self::text_row( $name( 'telephone' ), __( 'Telephone', 'avix-widgets' ), $e['telephone'], __( 'International format (+880 ...). Printed on the Organization and in its ContactPoint.', 'avix-widgets' ) );
					self::text_row( $name( 'address_street' ), __( 'Street address', 'avix-widgets' ), $e['address_street'], __( 'Leave the address empty until one official address is confirmed.', 'avix-widgets' ) );
					self::text_row( $name( 'address_locality' ), __( 'City', 'avix-widgets' ), $e['address_locality'] );
					self::text_row( $name( 'address_postcode' ), __( 'Postcode', 'avix-widgets' ), $e['address_postcode'] );
					self::text_row( $name( 'address_country' ), __( 'Country', 'avix-widgets' ), $e['address_country'], __( 'Two-letter code (BD) or the country name.', 'avix-widgets' ) );
					self::text_row( $name( 'contact_type' ), __( 'Contact type', 'avix-widgets' ), $e['contact_type'] );
					self::list_row( $name( 'contact_languages' ), __( 'Contact languages', 'avix-widgets' ), $e['contact_languages'], __( 'One per line. Only languages the team really works in.', 'avix-widgets' ) );
					self::list_row( $name( 'area_served' ), __( 'Areas served', 'avix-widgets' ), $e['area_served'], __( 'One per line. Country names become Country, anything else a Place; start a line with "Place:" or "Country:" to choose.', 'avix-widgets' ) );
					self::list_row( $name( 'knows_about' ), __( 'Knows about', 'avix-widgets' ), $e['knows_about'], __( 'Platforms and skills, one per line.', 'avix-widgets' ) );
					self::list_row( $name( 'org_same_as' ), __( 'More profiles (sameAs)', 'avix-widgets' ), $e['org_same_as'], __( 'Directory profiles of the business (Clutch, Sortlist...), one URL per line, once they are live. Added to the social profiles from Yoast without duplicates.', 'avix-widgets' ) );
					?>
				</table>
				<h2><?php esc_html_e( 'Founder', 'avix-widgets' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					self::text_row( $name( 'founder_name' ), __( 'Name', 'avix-widgets' ), $e['founder_name'], __( 'Empty = no founder Person and no founder link.', 'avix-widgets' ) );
					self::text_row( $name( 'founder_alternate' ), __( 'Alternate name', 'avix-widgets' ), $e['founder_alternate'] );
					self::text_row( $name( 'founder_job_title' ), __( 'Job title', 'avix-widgets' ), $e['founder_job_title'] );
					self::text_row( $name( 'founder_url' ), __( 'Profile page', 'avix-widgets' ), $e['founder_url'], __( 'A path on this site (/about-avixdigital/) or a full URL.', 'avix-widgets' ) );
					self::text_row( $name( 'founder_image_id' ), __( 'Portrait (media ID)', 'avix-widgets' ), (string) $e['founder_image_id'], __( '0 = no image. Use a real, approved photo only.', 'avix-widgets' ), 'number' );
					self::list_row( $name( 'founder_same_as' ), __( 'Profiles (sameAs)', 'avix-widgets' ), $e['founder_same_as'], __( 'One URL per line: only profiles that belong to the founder.', 'avix-widgets' ) );
					self::text_row( $name( 'founder_slug' ), __( 'Person ID', 'avix-widgets' ), $e['founder_slug'], __( 'The @id is the home URL + #person-{ID}. Keep "akib-zawayed": the widgets use the same ID.', 'avix-widgets' ) );
					self::text_row( $name( 'founder_user_ids' ), __( 'WordPress users who are the founder', 'avix-widgets' ), implode( ', ', $e['founder_user_ids'] ), __( 'User IDs, comma separated. Posts by these users name the founder Person as author.', 'avix-widgets' ) );
					?>
				</table>
				<p class="submit">
					<?php submit_button( __( 'Save entity', 'avix-widgets' ), 'primary', 'submit', false ); ?>
					<button type="submit" class="button" name="<?php echo esc_attr( $name( 'reset' ) ); ?>" value="1" onclick="return window.confirm('<?php echo esc_js( __( 'Put every field back to the plugin defaults?', 'avix-widgets' ) ); ?>');"><?php esc_html_e( 'Reset to defaults', 'avix-widgets' ); ?></button>
				</p>
			</form>
			<h2><?php esc_html_e( 'Preview', 'avix-widgets' ); ?></h2>
			<p><?php esc_html_e( 'What the plugin adds to the Organization node, and the founder Person node (both inside the Yoast SEO graph).', 'avix-widgets' ); ?></p>
			<?php
			$preview = array(
				'organization_additions' => self::enrich( array(), $e, class_exists( __NAMESPACE__ . '\Person' ) ? Person::founder_id() : '' ),
				'founder'                => class_exists( __NAMESPACE__ . '\Person' ) ? Person::founder_node() : array(),
			);
			?>
			<pre style="max-width:960px;overflow:auto;background:#fff;border:1px solid #c3c4c7;padding:12px;"><?php echo esc_html( (string) wp_json_encode( $preview, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ); ?></pre>
		</div>
		<?php
	}

	/**
	 * One text input row.
	 */
	private static function text_row( string $name, string $label, string $value, string $help = '', string $type = 'text' ): void {
		$id = sanitize_html_class( str_replace( array( '[', ']' ), array( '-', '' ), $name ) );
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<input type="<?php echo esc_attr( $type ); ?>" class="regular-text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>"<?php echo 'number' === $type ? ' min="0" step="1"' : ''; ?>>
				<?php if ( '' !== $help ) : ?>
					<p class="description"><?php echo esc_html( $help ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * One textarea row for a paragraph of plain text.
	 */
	private static function textarea_row( string $name, string $label, string $value, string $help = '' ): void {
		$id = sanitize_html_class( str_replace( array( '[', ']' ), array( '-', '' ), $name ) );
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<textarea class="large-text" rows="3" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
				<?php if ( '' !== $help ) : ?>
					<p class="description"><?php echo esc_html( $help ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * One textarea row (one item per line).
	 */
	private static function list_row( string $name, string $label, array $values, string $help = '' ): void {
		$id = sanitize_html_class( str_replace( array( '[', ']' ), array( '-', '' ), $name ) );
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<textarea class="large-text code" rows="<?php echo (int) max( 3, min( 12, count( $values ) + 1 ) ); ?>" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>"><?php echo esc_textarea( implode( "\n", $values ) ); ?></textarea>
				<?php if ( '' !== $help ) : ?>
					<p class="description"><?php echo esc_html( $help ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}
}
