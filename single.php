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
            <div class="<?php ignites_layout_option(); ?>">
                <div id="primary" class="content-area">
                    <main id="main" class="site-main">
                        <?php
                        while (have_posts()) :
                            the_post();
                            get_template_part('template-parts/content', get_post_type());

                            $ignites_prev_post = get_adjacent_post(false, '', true);
                            $ignites_next_post = get_adjacent_post(false, '', false);

                            if (is_a($ignites_prev_post, 'WP_Post')) {
                                $ignites_prev_link = esc_url(get_permalink($ignites_prev_post->ID));
                            } else {
                                $ignites_prev_link = '#';
                            }

                            if (is_a($ignites_next_post, 'WP_Post')) {
                                $ignites_next_link = esc_url(get_permalink($ignites_next_post->ID));
                            } else {
                                $ignites_next_link = '#';
                            }
                        ?>
                            <div class="author-wrap">
                                <div class="author-img">
                                    <?php echo  get_avatar(get_the_author_meta('ID'), 120) ?>
                                </div>
                                <div class="author-details">
                                    <h2>
                                        Author:
                                        <?php ignites_posted_by(); ?>
                                    </h2>
                                    <p>
                                        <?php echo esc_html(the_author_meta('description')); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="navigation post-navigation container">
                                <div class="row">
                                    <div class="nav-previous col-md-6 text-left">
                                        <a href="<?php echo $ignites_prev_link; ?>" class="nav-txt"> <?php esc_html_e('Previous post', 'ignites'); ?> </a>
                                        <?php
                                        if (!empty($ignites_prev_post)) {
                                            echo '<a href="' . esc_url(get_permalink($ignites_prev_post->ID)) . '" title="' .
                                                esc_html($ignites_prev_post->post_title) . '">' . esc_html(wp_trim_words($ignites_prev_post->post_title, 4)) . '</a>';
                                        }
                                        ?>
                                    </div>
                                    <div class="nav-next col-md-6 text-right">
                                        <a href="<?php echo $ignites_next_link; ?>" class="nav-txt"> <?php esc_html_e('Next post', 'ignites'); ?> </a>
                                        <?php
                                        if (!empty($ignites_next_post)) {
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
            <?php get_sidebar(); ?>
        </div>
    </div>
</div>
<?php
get_footer();
