<?php
/**
 * The template for displaying all pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site may use a
 * different template.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Ignites
 */

get_header();
?>
    <div class="main-content-section">
        <div class="container">
            <div class="row d-flex justify-content-center">
                <?php 
                    $side_layout =  get_theme_mod("ignites_sidebar_settings","right-sidebar");
                    if($side_layout == 'left-sidebar'){
                        get_sidebar('widget-sidebar');
                    }
                ?>
                <div class="<?php ignites_layout_option();?>">
                    <div id="primary" class="content-area">
                        <main id="main" class="site-main">
							<?php
                                while ( have_posts() ) :
                                    the_post();

                                    get_template_part( 'template-parts/content', 'page' );

                                    // If comments are open or we have at least one comment, load up the comment template.
                                    if ( comments_open() || get_comments_number() ) :
                                        comments_template();
                                    endif;

                                endwhile; // End of the loop.
							?>
                        </main><!-- #main -->
                    </div>
                </div>
                <?php 
                    if($side_layout == 'right-sidebar'){
                        get_sidebar('widget-sidebar');
                    }
                ?>
            </div>
        </div>
    </div>
<?php
get_footer();