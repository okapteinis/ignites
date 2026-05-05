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

							<?php
							$ignites_child_post_count = 0;
							while ( have_posts() ) :
								the_post();
								$ignites_child_post_count++;

								if ( 1 === $ignites_child_post_count ) :
									?>
									<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-hero' ); ?>>
										<?php ignites_post_thumbnail(); ?>
										<div class="wrap-content">
											<div class="entry-category">
												<?php echo wp_kses_post( get_the_category_list( __( ', ', 'ignites-child' ) ) ); ?>
											</div>
											<header class="entry-header">
												<h2 class="entry-title">
													<a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a>
												</h2>
											</header>
											<div class="entry-content">
												<p class="entry-excerpt m-0"><?php echo wp_kses_post( get_the_excerpt() ); ?></p>
											</div>
											<footer class="entry-footer">
												<span class="post-date"><?php echo esc_html( ignites_child_post_date() ); ?></span>
												<?php $rt = ignites_child_reading_time(); if ( $rt ) : ?>
													<span class="reading-time"><?php echo esc_html( $rt ); ?></span>
												<?php endif; ?>
												<?php
												$tags = get_the_tag_list( '<span class="tags-links">', '', '</span>' );
												if ( $tags ) {
													echo wp_kses_post( $tags );
												}
												?>
											</footer>
										</div>
									</article>
									<?php
								else :
									get_template_part( 'template-parts/content', get_post_type() );
								endif;
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
