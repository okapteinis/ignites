<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @link https://codex.wordpress.org/Creating_an_Error_404_Page
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
                            <section class="error-404 not-found">
                                <header class="page-header">
                                    <h1 class="page-title"><?php esc_html_e( 'Oops! That page can&rsquo;t be found.', 'ignites' ); ?></h1>
                                </header><!-- .page-header -->
                                <div class="page-content">
                                    <p><?php esc_html_e( 'It looks like nothing was found at this location. Maybe try one of the links below or a search?', 'ignites' ); ?></p>
									<?php
									    get_search_form();
									?>
                                </div><!-- .page-content -->
                            </section><!-- .error-404 -->
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