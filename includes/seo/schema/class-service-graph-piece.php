<?php
/**
 * Yoast graph piece: the Service a service page describes (SEO plan A5), from the page's Page
 * Hero widget (Structured data > Service schema). Loaded only from Service_Piece::graph_pieces(),
 * i.e. only while Yoast SEO runs, because it extends Yoast's abstract.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\SEO;

defined( 'ABSPATH' ) || exit;

if ( class_exists( '\Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece' ) && ! class_exists( __NAMESPACE__ . '\Service_Graph_Piece', false ) ) {

	/**
	 * Service for pages whose Page Hero has the Service schema switch on.
	 */
	class Service_Graph_Piece extends \Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece {

		/** Filter suffix: wpseo_schema_avix_service, wpseo_schema_needs_avix_service. */
		public $identifier = 'avix_service';

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
			return (bool) Service_Piece::current();
		}

		/**
		 * @return array|false
		 */
		public function generate() {
			$node = Service_Piece::current();
			if ( ! $node ) {
				return false;
			}
			// The page the Service is the main entity of: Yoast's WebPage @id (its canonical).
			if ( is_object( $this->context ) && isset( $this->context->canonical ) && is_string( $this->context->canonical ) && '' !== $this->context->canonical ) {
				$node['mainEntityOfPage'] = array( '@id' => $this->context->canonical );
			}
			// The Page Hero prints no free-standing Service block after this.
			Service_Piece::mark_in_graph();
			return $node;
		}
	}
}
