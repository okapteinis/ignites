<?php
/**
 * The template for displaying archive pages
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
				                <?php ignites_render_sidebar(); ?>
				                <div class="<?php echo esc_attr(ignites_layout_option()); ?>">
				
                    <div id="primary" class="content-area">
                        <main id="main" class="site-main">
							<?php if ( have_posts() ) : ?>
                                <header class="page-header">
									<?php
									the_archive_title( '<h1 class="page-title">', '</h1>' );
									the_archive_description( '<div class="archive-description">', '</div>' );
									?>
                                </header><!-- .page-header -->
								<?php
								/* Start the Loop */
								while ( have_posts() ) :
									the_post();
									/*
									 * Include the Post-Type-specific template for the content.
									 * If you want to override this in a child theme, then include a file
									 * called content-___.php (where ___ is the Post Type name) and that will be used instead.
									 */
									get_template_part( 'template-parts/content', get_post_type() );
								endwhile;?>
                                <div class="dope-pagination text-center">
									<?php ignites_num_post_nav(); ?>
                                </div>
							<?php else :
								get_template_part( 'template-parts/content', 'none' );
							endif;
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