<?php
/**
 * Ignites Child — Single post (delegates to parent's structure but uses our content template).
 *
 * @package Ignites_Child
 */

get_header();
?>
<div class="main-content-section">
	<div class="container">
		<div class="row d-flex justify-content-center">
			<div class="col-lg-12">
				<div id="primary" class="content-area">
					<main id="main" class="site-main">
						<?php
						while ( have_posts() ) :
							the_post();
							get_template_part( 'template-parts/content', get_post_type() );

							$ignites_prev_post = get_adjacent_post( false, '', true );
							$ignites_next_post = get_adjacent_post( false, '', false );
							?>

							<div class="author-wrap">
								<div class="author-img">
									<?php echo get_avatar( get_the_author_meta( 'ID' ), 120 ); ?>
								</div>
								<div class="author-details">
									<h2><?php the_author_posts_link(); ?></h2>
									<p><?php echo esc_html( get_the_author_meta( 'description' ) ); ?></p>
								</div>
							</div>

							<?php if ( $ignites_prev_post || $ignites_next_post ) : ?>
								<nav class="navigation post-navigation container" aria-label="<?php esc_attr_e( 'Posts', 'ignites-child' ); ?>">
									<div class="row">
										<div class="nav-previous col-md-6 text-start">
											<?php if ( $ignites_prev_post ) : ?>
												<span class="nav-txt">← <?php esc_html_e( 'Iepriekšējais raksts', 'ignites-child' ); ?></span>
												<a href="<?php echo esc_url( get_permalink( $ignites_prev_post->ID ) ); ?>" rel="prev"><?php echo esc_html( $ignites_prev_post->post_title ); ?></a>
											<?php endif; ?>
										</div>
										<div class="nav-next col-md-6 text-end">
											<?php if ( $ignites_next_post ) : ?>
												<span class="nav-txt"><?php esc_html_e( 'Nākamais raksts', 'ignites-child' ); ?> →</span>
												<a href="<?php echo esc_url( get_permalink( $ignites_next_post->ID ) ); ?>" rel="next"><?php echo esc_html( $ignites_next_post->post_title ); ?></a>
											<?php endif; ?>
										</div>
									</div>
								</nav>
							<?php endif; ?>

							<?php
							if ( comments_open() || get_comments_number() ) :
								comments_template();
							endif;
						endwhile;
						?>
					</main>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
get_footer();
