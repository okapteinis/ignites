<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Ignites
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">

	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'ignites' ); ?></a>
    <div class="header-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <header id="masthead" class="site-header">
                        <div class="site-branding">
						    <?php
						    the_custom_logo();?>
                                <h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
						    <?php $ignites_description = get_bloginfo( 'description', 'display' );
						    if ( $ignites_description || is_customize_preview() ) :
							    ?>
                                <p class="site-description"><?php echo $ignites_description; /* WPCS: xss ok. */ ?></p>
						    <?php endif; ?>
                        </div><!-- .site-branding -->

                        <button class="hamburger-menu cursor-pointer" aria-controls="site-navigation" aria-expanded="false">
                            <span></span>
                            <span></span>
                            <span></span>
                            <span></span>
                        </button><!-- .hamburger-menu -->

                        <nav id="site-navigation" class="main-navigation slide-in transition-5s">
                            <button class="close-navigation position-absolute transition-5s cursor-pointer" aria-controls="site-navigation" aria-expanded="false">
                                <span class="lnr lnr-cross"></span>
                            </button>

		                    <?php wp_nav_menu( array( 'theme_location' => 'primary-menu', 'menu_id' => 'primary-menu', 'container' => 'ul', 'menu_class' => 'primary-menu' ) ); ?>
                        </nav><!-- #site-navigation -->

                    </header>
                </div>
            </div>
        </div>
    </div>
	<?php
	// Flush the buffer to start sending the page to the browser.
	// This allows the browser to start rendering the head and header
	// while the server is still processing the main content.
	flush();
	?>
	<div id="content" class="site-content">
