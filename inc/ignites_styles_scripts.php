<?php

$ignites_theme_info = wp_get_theme();
define( 'IGNITES_THEME_VERSION', ( WP_DEBUG ) ? time() : $ignites_theme_info->get( 'Version' ) );

function ignites_enqueue_scripts() {
	wp_enqueue_style('bootstrap-css', get_template_directory_uri().'/assets/css/bootstrap.min.css',null,IGNITES_THEME_VERSION);
	wp_enqueue_style('ignites-main-css', get_template_directory_uri().'/assets/css/main.css',null,IGNITES_THEME_VERSION);
	wp_enqueue_style('ignites-google-font-css', 'https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700,800',null,IGNITES_THEME_VERSION);
	wp_enqueue_style('linearicons', get_template_directory_uri().'/assets/css/linearicons.css',null,IGNITES_THEME_VERSION);
	wp_enqueue_style('ignites-editor-css', get_template_directory_uri().'/assets/css/style-editor.css',null,IGNITES_THEME_VERSION);
	wp_enqueue_style('ignites-style', get_stylesheet_uri());

	wp_enqueue_script('bootstrap', get_template_directory_uri().'/assets/js/bootstrap.min.js', array(), IGNITES_THEME_VERSION, true);
	wp_enqueue_script( 'ignites-navigation', get_template_directory_uri() .'/assets/js/navigation.js', array(), IGNITES_THEME_VERSION, true );
	wp_enqueue_script( 'ignites-skip-link-focus-fix', get_template_directory_uri() . '/assets/js/skip-link-focus-fix.js', array(), IGNITES_THEME_VERSION, true );
	wp_enqueue_script( 'ignites-main-js', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), IGNITES_THEME_VERSION, true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'ignites_enqueue_scripts' );

function ignites_block_editor_styles() {
	wp_enqueue_style( 'ignites-block-editor-styles', get_template_directory_uri() . '/block-editor.css', null,IGNITES_THEME_VERSION);
}
add_action( 'enqueue_block_editor_assets', 'ignites_block_editor_styles' );

/**
 * Add preconnect for Google Fonts.
 */
function ignites_google_fonts_preconnect( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = 'https://fonts.googleapis.com';
		$urls[] = 'https://fonts.gstatic.com';
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'ignites_google_fonts_preconnect', 10, 2 );
