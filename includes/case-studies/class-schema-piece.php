<?php
/**
 * Yoast graph pieces: the CreativeWork of a case study and the ItemList of the
 * /case-studies/ index. Loaded only from SEO::graph_pieces(), i.e. only when
 * Yoast SEO is running, because they extend Yoast's abstract piece.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Case_Studies;

defined( 'ABSPATH' ) || exit;

if ( class_exists( '\Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece' ) && ! class_exists( __NAMESPACE__ . '\Schema_Piece', false ) ) {

	/**
	 * CreativeWork for case-study singles.
	 */
	class Schema_Piece extends \Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece {

		/** Filter suffix: wpseo_schema_avix_case_study, wpseo_schema_needs_avix_case_study. */
		public $identifier = 'avix_case_study';

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
			return is_singular( Case_Study::POST_TYPE ) && (bool) Case_Study::get( (int) get_queried_object_id() );
		}

		/**
		 * @return array|false
		 */
		public function generate() {
			$canonical = '';
			if ( is_object( $this->context ) && isset( $this->context->canonical ) && is_string( $this->context->canonical ) ) {
				$canonical = $this->context->canonical;
			}
			$data = SEO::creative_work( (int) get_queried_object_id(), $canonical, true );
			return $data ? $data : false;
		}
	}

	/**
	 * ItemList for the index page.
	 */
	class Schema_Index_Piece extends \Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece {

		/** Filter suffix: wpseo_schema_avix_case_study_list. */
		public $identifier = 'avix_case_study_list';

		/**
		 * @param mixed $context Yoast Meta_Tags_Context.
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
			return SEO::is_index() && (bool) Case_Study::ordered_ids();
		}

		/**
		 * @return array|false
		 */
		public function generate() {
			$canonical = '';
			if ( is_object( $this->context ) && isset( $this->context->canonical ) && is_string( $this->context->canonical ) ) {
				$canonical = $this->context->canonical;
			}
			$data = SEO::item_list( $canonical );
			return $data ? $data : false;
		}
	}
}
