<?php
/**
 * SEO module loader: entity schema, the founder Person, Service schema in the
 * Yoast graph, breadcrumbs, 404 redirects and the SEO data importer.
 *
 * Every file defines one class in the AvixWidgets\SEO namespace with a
 * static init(). Files are optional, so the plugin never fatals while one is
 * missing, and nothing here may require Yoast at load time.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\SEO;

defined( 'ABSPATH' ) || exit;

foreach ( array(
	'class-entity.php'        => 'Entity',
	'class-person.php'        => 'Person',
	'class-service-piece.php' => 'Service_Piece',
	'class-breadcrumbs.php'   => 'Breadcrumbs',
	'class-redirects.php'     => 'Redirects',
	'class-seo-importer.php'  => 'SEO_Importer',
) as $avix_seo_file => $avix_seo_class ) {
	$avix_seo_path = __DIR__ . '/' . $avix_seo_file;
	if ( ! file_exists( $avix_seo_path ) ) {
		continue;
	}
	require_once $avix_seo_path;
	$avix_seo_fqcn = __NAMESPACE__ . '\\' . $avix_seo_class;
	if ( class_exists( $avix_seo_fqcn ) && method_exists( $avix_seo_fqcn, 'init' ) ) {
		$avix_seo_fqcn::init();
	}
}
unset( $avix_seo_file, $avix_seo_class, $avix_seo_path, $avix_seo_fqcn );
