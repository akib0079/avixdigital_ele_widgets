<?php
/**
 * Single post with the Avix article template (includes/blog/class-blog.php).
 *
 * Classic themes (Algenix on avixdigital.com): the theme's own get_header()
 * and get_footer() print the ThemeREX header and footer layouts exactly as on
 * every other page; the article sits inside the theme's .content area. Block
 * themes: the header and footer template parts.
 *
 * @package AvixWidgets
 */

defined( 'ABSPATH' ) || exit;

$avix_art_block_theme = \AvixWidgets\Blog\Blog::is_block_theme();

if ( $avix_art_block_theme ) {
	\AvixWidgets\Blog\Blog::block_theme_open();
} else {
	get_header();
}

while ( have_posts() ) {
	the_post();
	\AvixWidgets\Blog\Blog::render( get_post() );
}

if ( $avix_art_block_theme ) {
	\AvixWidgets\Blog\Blog::block_theme_close();
} else {
	get_footer();
}
