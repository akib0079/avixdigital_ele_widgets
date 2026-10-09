<?php
/**
 * Yoast graph piece: the canonical founder Person (SEO plan A3). Loaded only from
 * Person::graph_pieces(), i.e. only while Yoast SEO runs, because it extends Yoast's abstract.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\SEO;

defined( 'ABSPATH' ) || exit;

if ( class_exists( '\Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece' ) && ! class_exists( __NAMESPACE__ . '\Founder_Graph_Piece', false ) ) {

	/**
	 * The founder Person on every page.
	 */
	class Founder_Graph_Piece extends \Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece {

		/** Filter suffix: wpseo_schema_avix_founder, wpseo_schema_needs_avix_founder. */
		public $identifier = 'avix_founder';

		/**
		 * @param mixed $context Yoast Meta_Tags_Context (Yoast also sets it before is_needed()).
		 */
		public function __construct( $context = null ) {
			if ( null !== $context ) {
				$this->context = $context;
			}
		}

		/**
		 * @return bool
		 */
		public function is_needed() {
			return '' !== Person::founder_id();
		}

		/**
		 * @return array|false
		 */
		public function generate() {
			$node = Person::founder_node();
			if ( ! $node ) {
				return false;
			}
			// Widgets rendered later on this request print no second founder node.
			Person::mark_printed( (string) $node['@id'] );
			return $node;
		}
	}
}
