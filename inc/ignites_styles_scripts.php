<?php

$ignites_theme_info = wp_get_theme();
define( 'IGNITES_THEME_VERSION', ( WP_DEBUG ) ? time() : $ignites_theme_info->get( 'Version' ) );

function ignites_enqueue_scripts() {
	// Enqueue styles. The async/defer logic will be handled by the filter.
	wp_enqueue_style( 'bootstrap-css', get_template_directory_uri() . '/assets/css/bootstrap.min.css', array(), IGNITES_THEME_VERSION );
	wp_enqueue_style( 'ignites-google-font-css', 'https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700,800&display=swap', array(), null );
	wp_enqueue_style( 'linearicons', get_template_directory_uri() . '/assets/css/linearicons.css', array(), IGNITES_THEME_VERSION );
	wp_enqueue_style( 'ignites-main-css', get_template_directory_uri() . '/assets/css/main.css', array( 'bootstrap-css' ), IGNITES_THEME_VERSION );
	wp_enqueue_style( 'ignites-style', get_stylesheet_uri(), array( 'ignites-main-css' ), IGNITES_THEME_VERSION );

	// JavaScript files are already in the footer, which is good.
	// We will add 'defer' attribute via a filter for better performance.
	// Note: 'ignites-main-js' depends on 'jquery' and 'bootstrap', so 'bootstrap' should be enqueued before it.
	wp_enqueue_script( 'bootstrap', get_template_directory_uri() . '/assets/js/bootstrap.min.js', array(), IGNITES_THEME_VERSION, true ); // Bootstrap's JS
	wp_enqueue_script( 'ignites-navigation', get_template_directory_uri() .'/assets/js/navigation.js', array(), IGNITES_THEME_VERSION, true ); // Main navigation
	wp_enqueue_script( 'ignites-skip-link-focus-fix', get_template_directory_uri() . '/assets/js/skip-link-focus-fix.js', array(), IGNITES_THEME_VERSION, true ); // Accessibility
	wp_enqueue_script( 'ignites-main-js', get_template_directory_uri() . '/assets/js/main.js', array('jquery', 'bootstrap'), IGNITES_THEME_VERSION, true ); // Main JS

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'ignites_enqueue_scripts' );

function ignites_async_defer_css_js( $tag, $handle, $src ) {
	// Defer all scripts except jQuery
	if ( is_admin() ) {
		return $tag;
	}

	$js_handles_to_defer = array( 'bootstrap', 'ignites-navigation', 'ignites-skip-link-focus-fix', 'ignites-main-js' );
	if ( in_array( $handle, $js_handles_to_defer, true ) ) {
		return str_replace( ' src', ' defer="defer" src', $tag );
	}

	// Preload non-critical CSS
	$css_handles_to_preload = array( 'bootstrap-css', 'ignites-google-font-css', 'linearicons', 'ignites-main-css', 'ignites-style' );
	if ( in_array( $handle, $css_handles_to_preload, true ) ) {
		return str_replace( "rel='stylesheet'", "rel='preload' as='style' onload=\"this.onload=null;this.rel='stylesheet'\"", $tag );
	}

	return $tag;
}
add_filter( 'style_loader_tag', 'ignites_async_defer_css_js', 10, 3 );
add_filter( 'script_loader_tag', 'ignites_async_defer_css_js', 10, 3 );

function ignites_critical_css() {
	// These styles are for the header and navigation, ensuring the top part of the page renders immediately.
	// This is a small subset of styles from main.css and bootstrap.css.
	$critical_css = '
		body{background-color:#f4f7f6;color:#8c8d9e;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol";font-size:14px;line-height:1.625em}
		.container{--bs-gutter-x:1.5rem;width:100%;padding-right:calc(var(--bs-gutter-x)*.5);padding-left:calc(var(--bs-gutter-x)*.5);margin-right:auto;margin-left:auto}
		@media (min-width:992px){.container{max-width:960px}}
		.row{--bs-gutter-x:1.5rem;display:flex;flex-wrap:wrap;margin-top:0;margin-right:calc(-.5*var(--bs-gutter-x));margin-left:calc(-.5*var(--bs-gutter-x))}
		.row>*{flex-shrink:0;width:100%;max-width:100%;padding-right:calc(var(--bs-gutter-x)*.5);padding-left:calc(var(--bs-gutter-x)*.5);margin-top:0}
		.header-section{padding:30px 0;text-align:center;background-color:#fff}
		.site-branding{margin-bottom:30px}
		.site-title{margin-top:0}
		.site-description{margin-bottom:0}
		@media(max-width:991px){.site-header{position:relative;display:flex;align-items:center}.header-section{text-align:left}.site-branding{display:inline-block;margin-bottom:0;width:210px}}
		.hamburger-menu{display:none!important}
		@media(max-width:991px){.hamburger-menu{display:inline-block!important;position:absolute;width:22px;height:40px;margin-left:2px;right:0}}
		.hamburger-menu span{display:block;position:absolute;height:2px;width:100%;background:#000;opacity:1;left:0}
	';
	echo '<style>' . preg_replace( '/\s+/', ' ', $critical_css ) . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_head', 'ignites_critical_css', 1 );

function ignites_block_editor_styles() {
	wp_enqueue_style( 'ignites-block-editor-styles', get_template_directory_uri() . '/assets/css/style-editor.css', array(), IGNITES_THEME_VERSION );
}
add_action( 'enqueue_block_editor_assets', 'ignites_block_editor_styles' );
