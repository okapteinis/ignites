<?php

	if ( ! function_exists( 'ignites_setup' ) ) :
		function ignites_setup() {

	        // Make theme available for translation.
			load_theme_textdomain( 'ignites', get_template_directory() . '/languages' );

			// Add default posts and comments RSS feed links to head.
			add_theme_support( 'automatic-feed-links' );


			// Let WordPress manage the document title.
			add_theme_support( 'title-tag' );


			// Enable support for Post Thumbnails on posts and pages.
			add_theme_support( 'post-thumbnails' );

			// Enable Wide angle image in gutenberg

			add_theme_support( 'align-wide' );

			// This theme uses wp_nav_menu() in one location.
			register_nav_menus( array(
				'primary-menu' => esc_html__( 'Primary Menu', 'ignites' ),
			) );

			/*
			 * Switch default core markup for search form, comment form, and comments
			 * to output valid HTML5.
			 */
			add_theme_support(
				'html5',
				array(
					'comment-form',
					'comment-list',
					'caption',
					'widgets-block-editor'
				)
			);

			// Set up the WordPress core custom background feature.
			add_theme_support( 'custom-background', apply_filters( 'ignites_custom_background_args', array(
				'default-color' => 'f4f7f6',
			) ) );

			// Add theme support for selective refresh for widgets.
			add_theme_support( 'customize-selective-refresh-widgets' );

			// Custom Thumbnail Image Size
			add_image_size("ignites-landscape",730,400,true);

			//Add support for core custom logo.
			add_theme_support( 'custom-logo', array(
				'height'      => 94,
				'width'       => 200,
				'flex-width'  => true,
				'flex-height' => true,
			) );
		}
	endif;
	add_action( 'after_setup_theme', 'ignites_setup' );

	/**
	 * Set the content width in pixels, based on the theme's design and stylesheet.
	 *
	 * Priority 0 to make it available to lower priority callbacks.
	 *
	 */
	function ignites_content_width() {
		$GLOBALS['content_width'] = apply_filters( 'ignites_content_width', 640 );
	}
	add_action( 'after_setup_theme', 'ignites_content_width', 0 );

	/**
	 * Enqueue scripts and styles.
	 */

	require get_template_directory() .'/inc/ignites_styles_scripts.php';

	/**
	 * Ignites Widgets.
	 */
	require get_template_directory() .'/inc/ignites_widgets.php';

	/**
	 * Custom template tags for this theme.
	 */
	require get_template_directory() . '/inc/template-tags.php';

	/**
	 * Functions which enhance the theme by hooking into WordPress.
	 */
	require get_template_directory() . '/inc/template-functions.php';

	/**
	 * Custom Functions for the Theme.
	 */
	require get_template_directory() . '/inc/custom-functions.php';

	/**
	 * Customizer additions.
	 */
	require get_template_directory() . '/inc/customizer/customizer.php';

	/**
	 * Implement the Custom Header feature.
	 */
	require get_template_directory() . '/inc/customizer/custom-header.php';

	/**
	 * Load Jetpack compatibility file.
	 */
	if ( defined( 'JETPACK__VERSION' ) ) {
		require get_template_directory() . '/inc/compatibility/jetpack.php';
	}

