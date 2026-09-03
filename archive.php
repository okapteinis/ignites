<?php
/**
 * Ignites Child — Archive / Home loop with hero first post.
 *
 * @package Ignites_Child
 */

get_header();
?>
<div class="main-content-section">
	<div class="container">
		<div class="row d-flex justify-content-center">
			<?php
			$side_layout = get_theme_mod( 'ignites_sidebar_settings', 'right-sidebar' );
			if ( 'left-sidebar' === $side_layout ) {
				get_sidebar( 'widget-sidebar' );
			}
			?>
			<div class="<?php ignites_layout_option(); ?>">
				<div id="primary" class="content-area">
					<main id="main" class="site-main">
						<?php if ( have_posts() ) : ?>
							<header class="page-header screen-reader-text">
								<?php the_archive_title( '<h1 class="page-title">', '</h1>' ); ?>
							</header>

							<?php
							$ignites_child_post_count = 0;
							while ( have_posts() ) :
								the_post();
								$ignites_child_post_count++;

								if ( 1 === $ignites_child_post_count ) {
									get_template_part( 'template-parts/content', 'hero' );
								} else {
									get_template_part( 'template-parts/content', get_post_type() );
								}
							endwhile;
							?>

							<div class="dope-pagination text-center">
								<?php ignites_num_post_nav(); ?>
							</div>

						<?php else : ?>
							<?php get_template_part( 'template-parts/content', 'none' ); ?>
						<?php endif; ?>
					</main>
				</div>
			</div>
			<?php
			if ( 'right-sidebar' === $side_layout ) {
				get_sidebar( 'widget-sidebar' );
			}
			?>
		</div>
	</div>
</div>
<?php
get_footer();
