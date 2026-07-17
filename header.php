<?php
/**
 * The header for our theme — CHILD OVERRIDE of ignites-nightly/header.php.
 *
 * This is a faithful copy of the parent theme's header.php with ONE change
 * (ignites#49 F14): the `.site-title` is rendered as <h1> ONLY on the front
 * page, and as <p class="site-title"> on every other view — so single posts and
 * archives have exactly one <h1> (the entry-title / archive-title), not two.
 * `.site-title` is styled by CLASS in style.css, so the visual is identical.
 *
 * ⚠️ If the parent ignites-nightly/header.php changes, re-sync this copy (the only
 * intended delta is the site-title conditional below).
 *
 * @package Ignites_Child
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
<?php
if ( function_exists( 'wp_body_open' ) ) {
	wp_body_open();
} else {
	do_action( 'wp_body_open' );
}
?>
<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'ignites' ); ?></a>
    <div class="header-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <header id="masthead" class="site-header">
                        <div class="site-branding">
						    <?php the_custom_logo(); ?>
							    <?php if ( is_front_page() || is_home() ) : ?>
                                <h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
							    <?php else : ?>
                                <p class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></p>
							    <?php endif; ?>
						    <?php $ignites_description = get_bloginfo( 'description', 'display' );
						    if ( $ignites_description || is_customize_preview() ) :
							    ?>
                                <p class="site-description"><?php echo esc_html( $ignites_description ); ?></p>
						    <?php endif; ?>
                        </div><!-- .site-branding -->

                        <div class="hamburger-menu cursor-pointer">
                            <span></span>
                            <span></span>
                            <span></span>
                            <span></span>
                        </div><!-- .hamburger-menu -->

                        <nav id="site-navigation" class="main-navigation slide-in transition-5s">
                            <div class="close-navigation position-absolute transition-5s cursor-pointer">
                                <span class="lnr lnr-cross"></span>
                            </div>

		                    <?php wp_nav_menu( array( 'theme_location' => 'primary-menu', 'menu_id' => 'primary-menu', 'container' => 'ul', 'menu_class' => 'primary-menu' ) ); ?>
                        </nav><!-- #site-navigation -->

                    </header>
                </div>
            </div>
        </div>
    </div>
	<div id="content" class="site-content">
