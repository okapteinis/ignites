<?php
/**
 * Template Name: Par mani (About)
 *
 * Editorial about page with social links to fediverse profiles.
 *
 * @package Ignites_Child
 */

get_header();
?>
<div class="main-content-section">
	<div class="container">
		<div class="row d-flex justify-content-center">
			<div class="col-lg-12">
				<main id="main" class="site-main about-page">
					<?php while ( have_posts() ) : the_post(); ?>

						<header class="about-intro">
							<h1><?php the_title(); ?></h1>
							<?php if ( has_excerpt() ) : ?>
								<p class="lede"><?php echo wp_kses_post( get_the_excerpt() ); ?></p>
							<?php endif; ?>
						</header>

						<div class="entry-content">
							<?php the_content(); ?>
						</div>

						<section class="social-links" aria-label="<?php esc_attr_e( 'Sociālie tīkli', 'ignites-child' ); ?>">
							<?php
							$ignites_child_socials = array(
								array(
									'name'   => 'Mastodon',
									'url'    => get_theme_mod( 'ignites_child_mastodon_url', '' ),
									'handle' => get_theme_mod( 'ignites_child_mastodon_handle', '' ),
									'icon'   => 'mastodon.svg',
								),
								array(
									'name'   => 'PixelFed',
									'url'    => get_theme_mod( 'ignites_child_pixelfed_url', '' ),
									'handle' => get_theme_mod( 'ignites_child_pixelfed_handle', '' ),
									'icon'   => 'pixelfed.svg',
								),
								array(
									'name'   => 'BookWyrm',
									'url'    => get_theme_mod( 'ignites_child_bookwyrm_url', '' ),
									'handle' => get_theme_mod( 'ignites_child_bookwyrm_handle', '' ),
									'icon'   => 'bookwyrm.png',
								),
								array(
									'name'   => 'Forgejo',
									'url'    => get_theme_mod( 'ignites_child_forgejo_url', '' ),
									'handle' => get_theme_mod( 'ignites_child_forgejo_handle', '' ),
									'icon'   => 'forgejo.svg',
								),
							);
							?>
							<ul class="social-list">
								<?php foreach ( $ignites_child_socials as $s ) :
									if ( empty( $s['url'] ) ) {
										continue;
									}
									$icon_path = get_stylesheet_directory() . '/assets/icons/' . $s['icon'];
									$is_svg    = ( substr( $s['icon'], -4 ) === '.svg' ) && file_exists( $icon_path );
								?>
									<li>
										<a class="social-link" href="<?php echo esc_url( $s['url'] ); ?>" rel="me noopener" target="_blank">
											<span class="social-icon" aria-hidden="true">
												<?php
												if ( $is_svg ) {
													// Inline so fill="currentColor" inherits from .social-icon (theme-bundled file, trusted).
													readfile( $icon_path );
												} else {
													$icon_url = get_stylesheet_directory_uri() . '/assets/icons/' . $s['icon'];
													?>
													<img src="<?php echo esc_url( $icon_url ); ?>" alt="" width="28" height="28" loading="lazy" />
													<?php
												}
												?>
											</span>
											<span class="social-label">
												<span class="social-name"><?php echo esc_html( $s['name'] ); ?></span>
												<?php if ( ! empty( $s['handle'] ) ) : ?>
													<span class="social-handle"><?php echo esc_html( $s['handle'] ); ?></span>
												<?php endif; ?>
											</span>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						</section>

					<?php endwhile; ?>
				</main>
			</div>
		</div>
	</div>
</div>
<?php
get_footer();
