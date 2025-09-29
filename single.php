<?php

/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
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
            <div class="<?php ignites_layout_option(); ?>">
                <div id="primary" class="content-area">
                    <main id="main" class="site-main">
                        <?php
                        while (have_posts()) :
                            the_post();
                            get_template_part('template-parts/content', get_post_type());

                            $ignites_prev_post = get_adjacent_post(false, '', true);
                            $ignites_next_post = get_adjacent_post(false, '', false);

                        ?>
                            <div class="author-wrap">
                                <div class="author-img">
                                    <?php echo  get_avatar(get_the_author_meta('ID'), 120) ?>
                                </div>
                                <div class="author-details">
                                    <h3>
										<?php
										printf(
											/* translators: %s: author name */
											esc_html__( 'Author: %s', 'ignites' ),
											wp_kses_post( get_the_author_posts_link() )
										);
										?>
                                    </h3>
                                    <p>
                                        <?php echo esc_html(the_author_meta('description')); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="navigation post-navigation container">
                                <div class="row">
                                    <div class="nav-previous col-md-6 text-left">
                                        <?php
                                        if (!empty($ignites_prev_post)) {
											echo '<a href="' . esc_url(get_permalink($ignites_prev_post->ID)) . '" class="nav-txt">' . esc_html__('Previous post', 'ignites') . '</a>';
                                            echo '<a href="' . esc_url(get_permalink($ignites_prev_post->ID)) . '" title="' .
                                                esc_html($ignites_prev_post->post_title) . '">' . esc_html(wp_trim_words($ignites_prev_post->post_title, 4)) . '</a>';
                                        }
                                        ?>
                                    </div>
                                    <div class="nav-next col-md-6 text-right">
                                        <?php
                                        if (!empty($ignites_next_post)) {
											echo '<a href="' . esc_url(get_permalink($ignites_next_post->ID)) . '" class="nav-txt">' . esc_html__('Next post', 'ignites') . '</a>';
                                            echo '<a href="' . esc_url(get_permalink($ignites_next_post->ID)) . '" title="' .
                                                esc_html($ignites_next_post->post_title) . '">' . esc_html(wp_trim_words($ignites_next_post->post_title, 4)) . '</a>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>

                        <?php
                            // If comments are open or we have at least one comment, load up the comment template.
                            if (comments_open() || get_comments_number()) :
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
