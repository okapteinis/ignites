<?php
/**
 * The template for displaying search results pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#search-result
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
                <div class="<?php echo esc_attr(ignites_layout_option()); ?>">
                    <div id="primary" class="content-area">
                        <main id="main" class="site-main">
                            <section id="primary" class="content-area">
                                <main id="main" class="site-main">
									<?php if ( have_posts() ) : ?>
                                        <header class="page-header">
                                            <h1 class="page-title">
												<?php
												/* translators: 1: WordPress version number*/
												printf( esc_html__( 'Search Results for: %s', 'ignites' ), '<span>' . get_search_query() . '</span>' );
												?>
                                            </h1>
                                        </header><!-- .page-header -->
										<?php
										/* Start the Loop */
										while ( have_posts() ) :
											the_post();
											/**
											 * Run the loop for the search to output the results.
											 * If you want to overload this in a child theme then include a file
											 * called content-search.php and that will be used instead.
											 */
											get_template_part( 'template-parts/content', 'search' );
										endwhile;?>
										<div class="dope-pagination text-center">
                                            <?php ignites_num_post_nav(); ?>
                                        </div>
									<?php else :
										get_template_part( 'template-parts/content', 'none' );
									endif;
									?>
                                </main><!-- #main -->
                            </section>
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