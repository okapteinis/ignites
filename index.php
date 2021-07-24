<?php

/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Ignites
 */

get_header();
?>
<?php
if (function_exists('wp_body_open')) {
	wp_body_open();
} else {
	do_action('wp_body_open');
}
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
						if (have_posts()) :

							if (is_home() && !is_front_page()) :
						?>
								<header>
									<h1 class="page-title screen-reader-text"><?php single_post_title(); ?></h1>
								</header>
							<?php
							endif;

							/* Start the Loop */
							while (have_posts()) :
								the_post();

								/*
									 * Include the Post-Type-specific template for the content.
									 * If you want to override this in a child theme, then include a file
									 * called content-___.php (where ___ is the Post Type name) and that will be used instead.
									 */
								get_template_part('template-parts/content', get_post_type());

							endwhile; ?>

							<div class="dope-pagination text-center">
								<?php
								if (!class_exists('Jetpack') || (class_exists('Jetpack') && !Jetpack::is_module_active('infinite-scroll'))) {
									ignites_num_post_nav();
								}
								?>
							</div>

						<?php else :

							get_template_part('template-parts/content', 'none');

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
