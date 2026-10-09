<?php
/**
 * The founder as one Person entity (SEO plan A3 and A4).
 *
 * - A Yoast graph piece prints the founder Person on every page, @id home + #person-<slug>
 *   (default /#person-akib-zawayed), from the entity data (Tools > Avix SEO: entity). The image
 *   is printed only when a portrait ID is set there.
 * - Blog posts by the founder's WordPress user(s) name that Person as Article author, and
 *   Yoast's own author Person for those users (url "#", default Gravatar) is not printed.
 *   No Person anywhere in the graph keeps a url that is not an http(s) URL.
 * - Widgets (Process Timeline, Founder, Compare & CEO Quote) ask widget_nodes() what to print:
 *   for the founder only the canonical node, and only when nothing printed it yet on this request
 *   (so with Yoast: nothing); other people get their own @id and worksFor → #organization. A
 *   per-request registry prints each @id once.
 *
 * Every front-end hook is a Yoast filter; the Yoast piece class loads lazily inside
 * wpseo_schema_graph_pieces, so this file is safe without Yoast.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\SEO;

defined( 'ABSPATH' ) || exit;

final class Person {

	/** @var array<string, bool> @ids printed on this request. */
	private static $printed = array();

	/** @var array<string, bool>|null Yoast schema-id suffixes of the founder's users. */
	private static $user_suffixes = null;

	public static function init(): void {
		add_filter( 'wpseo_schema_graph_pieces', array( __CLASS__, 'graph_pieces' ), 11, 2 );
		add_filter( 'wpseo_schema_article', array( __CLASS__, 'filter_article' ), 20, 2 );
		add_filter( 'wpseo_schema_needs_author', array( __CLASS__, 'needs_author' ), 20 );
		add_filter( 'wpseo_schema_person', array( __CLASS__, 'filter_person' ), 20, 2 );
	}

	/* ------------------------------------------------------------------ */
	/* Founder data                                                       */
	/* ------------------------------------------------------------------ */

	private static function entity(): array {
		return class_exists( __NAMESPACE__ . '\Entity' ) ? Entity::get() : array();
	}

	/**
	 * The site's Organization @id, as Yoast prints it.
	 */
	public static function organization_id(): string {
		return trailingslashit( home_url() ) . '#organization';
	}

	/**
	 * @id for a person slug: home + #person-<slug>.
	 *
	 * @param string $slug Person slug.
	 */
	public static function id_for( string $slug ): string {
		return trailingslashit( home_url() ) . '#person-' . $slug;
	}

	/**
	 * The founder's @id, or '' when the entity has no founder name.
	 */
	public static function founder_id(): string {
		$e = self::entity();
		if ( empty( $e['founder_name'] ) ) {
			return '';
		}
		return self::id_for( ! empty( $e['founder_slug'] ) ? (string) $e['founder_slug'] : 'akib-zawayed' );
	}

	/**
	 * The canonical founder Person node (no @context), or array() without a founder.
	 */
	public static function founder_node(): array {
		$e = self::entity();
		if ( empty( $e['founder_name'] ) ) {
			return array();
		}
		$image = array();
		$id    = isset( $e['founder_image_id'] ) ? (int) $e['founder_image_id'] : 0;
		if ( $id > 0 ) {
			$src = wp_get_attachment_image_src( $id, 'full' );
			if ( $src ) {
				$image = array(
					'url'    => (string) $src[0],
					'width'  => (int) $src[1],
					'height' => (int) $src[2],
				);
			}
		}
		return self::build_founder( $e, trailingslashit( home_url() ), $image );
	}

	/**
	 * Pure helper (unit-testable): the founder Person from entity data. Empty fields are left out.
	 *
	 * @param array  $e     Normalised entity data.
	 * @param string $home  Home URL with a trailing slash.
	 * @param array  $image Resolved portrait ( url, width, height ) or array().
	 */
	public static function build_founder( array $e, string $home, array $image = array() ): array {
		$name = isset( $e['founder_name'] ) ? trim( (string) $e['founder_name'] ) : '';
		if ( '' === $name ) {
			return array();
		}
		$slug = isset( $e['founder_slug'] ) && '' !== trim( (string) $e['founder_slug'] ) ? trim( (string) $e['founder_slug'] ) : 'akib-zawayed';
		$node = array(
			'@type' => 'Person',
			'@id'   => $home . '#person-' . $slug,
			'name'  => $name,
		);
		$alt  = isset( $e['founder_alternate'] ) ? trim( (string) $e['founder_alternate'] ) : '';
		if ( '' !== $alt && $alt !== $name ) {
			$node['alternateName'] = $alt;
		}
		$job = isset( $e['founder_job_title'] ) ? trim( (string) $e['founder_job_title'] ) : '';
		if ( '' !== $job ) {
			$node['jobTitle'] = $job;
		}
		$url = isset( $e['founder_url'] ) ? trim( (string) $e['founder_url'] ) : '';
		if ( '' !== $url && '/' === $url[0] && ( ! isset( $url[1] ) || '/' !== $url[1] ) ) {
			$url = rtrim( $home, '/' ) . $url;
		}
		if ( preg_match( '#^https?://[^/\s]+#i', $url ) ) {
			$node['url'] = $url;
		}
		if ( ! empty( $image['url'] ) ) {
			$node['image'] = array(
				'@type'      => 'ImageObject',
				'@id'        => $home . '#person-' . $slug . '-image',
				'url'        => (string) $image['url'],
				'contentUrl' => (string) $image['url'],
			);
			if ( ! empty( $image['width'] ) && ! empty( $image['height'] ) ) {
				$node['image']['width']  = (int) $image['width'];
				$node['image']['height'] = (int) $image['height'];
			}
			$node['image']['caption'] = $name;
		}
		$same = array();
		foreach ( isset( $e['founder_same_as'] ) ? (array) $e['founder_same_as'] : array() as $link ) {
			if ( is_string( $link ) && preg_match( '#^https?://[^/\s]+#i', trim( $link ) ) && ! in_array( trim( $link ), $same, true ) ) {
				$same[] = trim( $link );
			}
		}
		if ( $same ) {
			$node['sameAs'] = $same;
		}
		$node['worksFor'] = array( '@id' => $home . '#organization' );
		return $node;
	}

	/**
	 * True when a name or slug is the founder's (name, alternate name or Person ID).
	 *
	 * @param string $name Display name.
	 * @param string $slug Optional explicit slug (e.g. the Founder widget's Person ID).
	 */
	public static function is_founder( string $name, string $slug = '' ): bool {
		return self::matches_founder( self::entity(), $name, $slug );
	}

	/**
	 * Pure helper (unit-testable) behind is_founder().
	 *
	 * @param array  $e    Normalised entity data.
	 * @param string $name Display name.
	 * @param string $slug Optional explicit slug.
	 */
	public static function matches_founder( array $e, string $name, string $slug = '' ): bool {
		if ( empty( $e['founder_name'] ) ) {
			return false;
		}
		$known = array_filter(
			array(
				sanitize_title( (string) $e['founder_name'] ),
				sanitize_title( isset( $e['founder_alternate'] ) ? (string) $e['founder_alternate'] : '' ),
				sanitize_title( isset( $e['founder_slug'] ) ? (string) $e['founder_slug'] : '' ),
			),
			'strlen'
		);
		$slug  = sanitize_title( $slug );
		$name  = sanitize_title( $name );
		return ( '' !== $slug && in_array( $slug, $known, true ) ) || ( '' !== $name && in_array( $name, $known, true ) );
	}

	/**
	 * True when a WordPress user is (one of) the founder's accounts.
	 *
	 * @param int $user_id User ID.
	 */
	public static function is_founder_user( int $user_id ): bool {
		$e = self::entity();
		return $user_id > 0 && '' !== self::founder_id() && in_array( $user_id, array_map( 'intval', isset( $e['founder_user_ids'] ) ? (array) $e['founder_user_ids'] : array() ), true );
	}

	/* ------------------------------------------------------------------ */
	/* One @id per request                                                */
	/* ------------------------------------------------------------------ */

	/**
	 * Records an @id as printed (the founder graph piece calls this in the head).
	 *
	 * @param string $id Node @id.
	 */
	public static function mark_printed( string $id ): void {
		if ( '' !== $id ) {
			self::$printed[ $id ] = true;
		}
	}

	/**
	 * True when the @id was printed on this request.
	 *
	 * @param string $id Node @id.
	 */
	public static function is_printed( string $id ): bool {
		return isset( self::$printed[ $id ] );
	}

	/**
	 * Claims an @id: true the first time on this request, false afterwards.
	 *
	 * @param string $id Node @id.
	 */
	public static function claim( string $id ): bool {
		if ( '' === $id || isset( self::$printed[ $id ] ) ) {
			return false;
		}
		self::$printed[ $id ] = true;
		return true;
	}

	/**
	 * Empties the registry (tests).
	 */
	public static function reset_registry(): void {
		self::$printed       = array();
		self::$user_suffixes = null;
	}

	/* ------------------------------------------------------------------ */
	/* Widgets                                                            */
	/* ------------------------------------------------------------------ */

	/**
	 * The Person nodes a widget should print. Each item: name, and optionally jobTitle, image
	 * (URL), link (profile URL or site path) and slug (explicit person ID). The founder becomes
	 * the canonical founder node, printed only if no one printed it before on this request; other
	 * people get @id home + #person-<slug> (once each) and worksFor → #organization.
	 *
	 * @param array $people Widget people.
	 */
	public static function widget_nodes( array $people ): array {
		$nodes   = array();
		$founder = self::founder_id();
		$host    = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		foreach ( $people as $person ) {
			if ( ! is_array( $person ) ) {
				continue;
			}
			$name = self::plain( isset( $person['name'] ) ? (string) $person['name'] : '' );
			if ( '' === $name ) {
				continue;
			}
			$slug = isset( $person['slug'] ) ? sanitize_title( (string) $person['slug'] ) : '';
			if ( '' !== $founder && self::is_founder( $name, $slug ) ) {
				// The founder is one entity site-wide: never a second definition, never a widget job title.
				if ( self::claim( $founder ) ) {
					$node = self::founder_node();
					if ( $node ) {
						$nodes[] = $node;
					}
				}
				continue;
			}
			$slug = '' !== $slug ? $slug : sanitize_title( $name );
			if ( '' === $slug ) {
				continue;
			}
			$id = self::id_for( $slug );
			if ( ! self::claim( $id ) ) {
				continue;
			}
			$node = array(
				'@type' => 'Person',
				'@id'   => $id,
				'name'  => $name,
			);
			$job  = self::plain( isset( $person['jobTitle'] ) ? (string) $person['jobTitle'] : '' );
			if ( '' !== $job ) {
				$node['jobTitle'] = $job;
			}
			$image = self::clean_link( isset( $person['image'] ) ? (string) $person['image'] : '' );
			if ( '' !== $image ) {
				$node['image'] = $image;
			}
			$link = self::clean_link( isset( $person['link'] ) ? (string) $person['link'] : '' );
			if ( '' !== $link ) {
				if ( wp_parse_url( $link, PHP_URL_HOST ) === $host ) {
					$node['url'] = $link;
				} else {
					$node['sameAs'] = array( $link );
				}
			}
			$node['worksFor'] = array( '@id' => self::organization_id() );
			$nodes[]          = $node;
		}
		return $nodes;
	}

	/**
	 * Prints widget Person nodes as one JSON-LD block (nothing when empty).
	 *
	 * @param array $nodes Nodes from widget_nodes().
	 */
	public static function print_nodes( array $nodes ): void {
		$nodes = array_values( array_filter( $nodes, 'is_array' ) );
		if ( ! $nodes ) {
			return;
		}
		$data = 1 === count( $nodes )
			? array( '@context' => 'https://schema.org' ) + $nodes[0]
			: array(
				'@context' => 'https://schema.org',
				'@graph'   => $nodes,
			);
		$json = wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP );
		if ( $json ) {
			echo '<script type="application/ld+json">' . $json . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON with < > & hex-escaped.
		}
	}

	/**
	 * An absolute http(s) URL (site paths resolved against home_url()), or '' (also for "#").
	 *
	 * @param string $link URL or site path.
	 */
	public static function clean_link( string $link ): string {
		$link = trim( $link );
		if ( '' === $link ) {
			return '';
		}
		if ( '/' === $link[0] && ( ! isset( $link[1] ) || '/' !== $link[1] ) ) {
			$link = home_url( $link );
		}
		$link = esc_url_raw( $link, array( 'http', 'https' ) );
		return ( '' !== $link && preg_match( '#^https?://[^/\s]+#i', $link ) ) ? $link : '';
	}

	/**
	 * Plain text for JSON-LD: no tags, entities decoded, whitespace collapsed.
	 *
	 * @param string $text Raw text.
	 */
	private static function plain( string $text ): string {
		$text = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
	}

	/* ------------------------------------------------------------------ */
	/* Yoast                                                              */
	/* ------------------------------------------------------------------ */

	/**
	 * Adds the founder piece to Yoast's graph (piece class loaded only here, when Yoast runs).
	 *
	 * @param mixed $pieces  Graph pieces.
	 * @param mixed $context Yoast Meta_Tags_Context.
	 * @return mixed
	 */
	public static function graph_pieces( $pieces, $context = null ) {
		if ( ! is_array( $pieces ) || ! defined( 'WPSEO_VERSION' ) || '' === self::founder_id() ) {
			return $pieces;
		}
		$file = __DIR__ . '/schema/class-founder-graph-piece.php';
		if ( ! file_exists( $file ) ) {
			return $pieces;
		}
		require_once $file;
		if ( class_exists( __NAMESPACE__ . '\Founder_Graph_Piece', false ) ) {
			$pieces[] = new Founder_Graph_Piece( $context );
		}
		return $pieces;
	}

	/**
	 * wpseo_schema_article: the founder's posts name the founder Person as author.
	 *
	 * @param mixed $data    Article piece.
	 * @param mixed $context Yoast Meta_Tags_Context.
	 * @return mixed
	 */
	public static function filter_article( $data, $context = null ) {
		if ( ! is_array( $data ) || empty( $data['author'] ) || ! is_array( $data['author'] ) ) {
			return $data;
		}
		$founder = self::founder_id();
		if ( '' === $founder ) {
			return $data;
		}
		$e    = self::entity();
		$ref  = array(
			'name' => (string) $e['founder_name'],
			'@id'  => $founder,
		);
		$list = isset( $data['author'][0] ) && is_array( $data['author'][0] );
		$out  = array();
		foreach ( $list ? $data['author'] : array( $data['author'] ) as $author ) {
			$id    = is_array( $author ) && isset( $author['@id'] ) && is_string( $author['@id'] ) ? $author['@id'] : '';
			$out[] = ( '' !== $id && ( $id === $founder || self::is_founder_user_schema_id( $id ) ) ) ? $ref : $author;
		}
		$data['author'] = $list ? $out : $out[0];
		return $data;
	}

	/**
	 * wpseo_schema_needs_author: no Yoast author Person on the founder's posts (the founder node
	 * already describes the author).
	 *
	 * @param mixed $needed Whether Yoast prints its author piece.
	 * @return mixed
	 */
	public static function needs_author( $needed ) {
		if ( ! $needed || ! is_singular() ) {
			return $needed;
		}
		$post = get_queried_object();
		if ( $post instanceof \WP_Post && self::is_founder_user( (int) $post->post_author ) ) {
			return false;
		}
		return $needed;
	}

	/**
	 * wpseo_schema_person (also the type filter of every Person piece): keeps the founder node,
	 * drops Yoast's Person for a founder user (a second, different founder), and removes any url
	 * that is not an http(s) URL (author archives switched off give url "#").
	 *
	 * @param mixed $data    Person piece.
	 * @param mixed $context Yoast Meta_Tags_Context.
	 * @return mixed
	 */
	public static function filter_person( $data, $context = null ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}
		$id      = isset( $data['@id'] ) && is_string( $data['@id'] ) ? $data['@id'] : '';
		$founder = self::founder_id();
		// A Person that is also the site's Organization (Yoast "site represents a person") is never dropped.
		$is_site = in_array( 'Organization', isset( $data['@type'] ) ? (array) $data['@type'] : array(), true );
		if ( '' !== $founder && '' !== $id && $id !== $founder && ! $is_site && self::is_founder_user_schema_id( $id ) && ! self::is_user_archive( $context ) ) {
			return false;
		}
		if ( isset( $data['url'] ) && ( ! is_string( $data['url'] ) || ! preg_match( '#^https?://[^/\s]+#i', $data['url'] ) ) ) {
			unset( $data['url'] );
		}
		return $data;
	}

	/**
	 * True when an @id is Yoast's Person @id for one of the founder's users
	 * (site URL + "#/schema/person/" + wp_hash( user_login . user_id )).
	 *
	 * @param string $id Node @id.
	 */
	public static function is_founder_user_schema_id( string $id ): bool {
		if ( null === self::$user_suffixes ) {
			self::$user_suffixes = array();
			$e                   = self::entity();
			foreach ( isset( $e['founder_user_ids'] ) ? (array) $e['founder_user_ids'] : array() as $user_id ) {
				$user = get_userdata( (int) $user_id );
				if ( $user ) {
					self::$user_suffixes[ '#/schema/person/' . wp_hash( $user->user_login . (int) $user_id ) ] = true;
				}
			}
		}
		$at = strpos( $id, '#/schema/person/' );
		return false !== $at && isset( self::$user_suffixes[ substr( $id, $at ) ] );
	}

	/**
	 * True on an author archive (Yoast's ProfilePage needs its own Person there).
	 *
	 * @param mixed $context Yoast Meta_Tags_Context.
	 */
	private static function is_user_archive( $context ): bool {
		if ( is_object( $context ) && isset( $context->indexable ) && is_object( $context->indexable ) && isset( $context->indexable->object_type ) ) {
			return 'user' === $context->indexable->object_type;
		}
		return is_author();
	}
}
