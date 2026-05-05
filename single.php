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

							<?php if ( $ignites_prev_post || $ignites_next_post ) : ?>
								<nav class="navigation post-navigation" aria-label="<?php esc_attr_e( 'Raksta navigācija', 'ignites-child' ); ?>">
									<?php if ( $ignites_prev_post ) : ?>
										<a class="nav-previous" href="<?php echo esc_url( get_permalink( $ignites_prev_post->ID ) ); ?>" rel="prev">← <?php esc_html_e( 'Iepriekšējais', 'ignites-child' ); ?></a>
									<?php endif; ?>
									<?php if ( $ignites_next_post ) : ?>
										<a class="nav-next" href="<?php echo esc_url( get_permalink( $ignites_next_post->ID ) ); ?>" rel="next"><?php esc_html_e( 'Nākamais', 'ignites-child' ); ?> →</a>
									<?php endif; ?>
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
