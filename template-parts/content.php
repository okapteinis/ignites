<?php
/**
 * Ignites Child — content template part (post card / single body).
 *
 * @package Ignites_Child
 */

$ignites_excerpt_view          = is_home() || is_front_page() || is_search() || is_archive();
$ignites_single_post_content   = null;
$more_link_text                = '';
if ( ! $ignites_excerpt_view ) {
	$more_link_text = sprintf(
		wp_kses(
			/* translators: %s: Name of current post. Only visible to screen readers */
			__( 'Continue reading<span class="screen-reader-text"> "%s"</span>', 'ignites-child' ),
			array( 'span' => array( 'class' => array() ) )
		),
		get_the_title()
	);
}
if ( is_singular( 'post' ) ) {
	$ignites_single_post_content = apply_filters( 'the_content', get_the_content( $more_link_text ) );
}
?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<?php
	// Post meta `_ignites_thumb_archive_only`: show the featured image on home/archive/search
	// cards but not on the open post (og:image + JSON-LD still use it).
	if ( ! ( is_singular() && get_post_meta( get_the_ID(), '_ignites_thumb_archive_only', true ) ) ) {
		ignites_post_thumbnail();
	}
	?>
	<div class="wrap-content">
		<div class="entry-category">
			<?php
			/* translators: separator between linked category names — kept as plain ", " */
			echo wp_kses_post( get_the_category_list( _x( ', ', 'category list separator', 'ignites-child' ) ) );
			?>
		</div>

		<header class="entry-header">
			<?php
			if ( is_singular() ) :
				the_title( '<h1 class="entry-title">', '</h1>' );
			else :
				the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
			endif;
			?>
		</header>

		<?php if ( is_singular( 'post' ) ) : ?>
		<div class="post-reading-layout">
			<?php ignites_child_post_toc(); ?>
			<div class="post-reading-main">
		<?php endif; ?>
		<div class="entry-content">
			<?php
			if ( $ignites_excerpt_view ) :
				// get_the_excerpt() (not the_excerpt()) — the_excerpt() emits its own
				// wpautop <p>, which nested inside ours produced invalid <p><p> markup.
				// Matches template-parts/content-hero.php.
				?>
				<p class="entry-excerpt m-0"><?php echo wp_kses_post( get_the_excerpt() ); ?></p>
				<?php
			else :
				if ( is_singular( 'post' ) ) {
					echo str_replace( ']]>', ']]&gt;', $ignites_single_post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the_content filters sanitize/format post HTML.
				} else {
					the_content( $more_link_text );
				}
				wp_link_pages(
					array(
						'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'ignites-child' ),
						'after'  => '</div>',
					)
				);
			endif;
			?>
		</div>

		<?php
		if ( is_singular( 'post' ) ) {
			get_template_part( 'template-parts/post', 'footnote' );
		}
		get_template_part( 'template-parts/entry', 'footer' );
		?>
		<?php if ( is_singular( 'post' ) ) : ?>
			</div>
		</div>
		<?php endif; ?>
	</div>
</article>
