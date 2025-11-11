<?php

$ignites_theme_info = wp_get_theme();
define( 'IGNITES_THEME_VERSION', ( WP_DEBUG ) ? time() : $ignites_theme_info->get( 'Version' ) );

function ignites_enqueue_scripts() {
	// Bootstrap 5.3.8 CSS
	wp_enqueue_style('bootstrap', get_template_directory_uri().'/assets/css/bootstrap.min.css',[],'5.3.8');
	wp_enqueue_style('ignites-main-css', get_template_directory_uri().'/assets/css/main.css',[],IGNITES_THEME_VERSION);
	wp_enqueue_style('ignites-google-font-css', '//fonts.googleapis.com/css?family=Open+Sans:300,400,600,700,800',[],IGNITES_THEME_VERSION);
	wp_enqueue_style('linearicons', get_template_directory_uri().'/assets/css/linearicons.css',[],IGNITES_THEME_VERSION);
	wp_enqueue_style('ignites-editor-css', get_template_directory_uri().'/assets/css/style-editor.css',[],IGNITES_THEME_VERSION);
	wp_enqueue_style('ignites-style', get_stylesheet_uri());

	// Bootstrap 5.3.8 JS Bundle (includes Popper.js v2, no jQuery dependency)
	wp_enqueue_script('bootstrap-bundle',get_template_directory_uri().'/assets/js/bootstrap.bundle.min.js', array(),'5.3.8',true);
	wp_enqueue_script( 'ignites-navigation', get_template_directory_uri() .'/assets/js/navigation.js', array(), IGNITES_THEME_VERSION, true );
	wp_enqueue_script( 'ignites-skip-link-focus-fix', get_template_directory_uri() . '/assets/js/skip-link-focus-fix.js', array(), IGNITES_THEME_VERSION, true );
	wp_enqueue_script( 'ignites-main-js', get_template_directory_uri() . '/assets/js/main.js', array(), IGNITES_THEME_VERSION, true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'ignites_enqueue_scripts' );

/**
 * Preload critical fonts for better performance
 * Ensures Linearicons font loads immediately, especially important for private/incognito mode
 *
 * @since 1.0.0
 */
function ignites_preload_fonts() {
	echo '<link rel="preload" href="' . esc_url( get_template_directory_uri() . '/assets/fonts/lnr-webfont.woff2' ) . '" as="font" type="font/woff2" crossorigin="anonymous">';
}
add_action( 'wp_head', 'ignites_preload_fonts', 1 );

function ignites_block_editor_styles() {
	wp_enqueue_style( 'ignites-block-editor-styles', get_template_directory_uri() . '/block-editor.css', [],IGNITES_THEME_VERSION);
}
add_action( 'enqueue_block_editor_assets', 'ignites_block_editor_styles' );
