<?php
/**
 * Ignites Child — 404 page (Latvian, theme-styled).
 *
 * Overrides the parent's 404.php which has English text and a Bootstrap
 * white-card layout. Reuses the parent's container/site-main shell so
 * the rest of the page (header, banner, footer) stays consistent.
 *
 * @package Ignites_Child
 */

get_header();
?>
<div class="main-content-section">
	<div class="container">
		<div class="row d-flex justify-content-center">
			<div class="col-lg-8">
				<div id="primary" class="content-area">
					<main id="main" class="site-main">
						<section class="error-404 not-found">
							<header class="page-header">
								<h1 class="page-title"><?php esc_html_e( '404 — lapa nav atrasta', 'ignites-child' ); ?></h1>
							</header>
							<div class="page-content">
								<p><?php esc_html_e( 'Šeit nekā nav. Pamēģini sākumlapu vai izmanto meklētāju zemāk.', 'ignites-child' ); ?></p>
								<?php get_search_form(); ?>
							</div>
						</section>
					</main>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
get_footer();
