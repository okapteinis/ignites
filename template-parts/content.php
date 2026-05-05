<?php
/**
 * Ignites Child — content template part (post card / single body).
 *
 * @package Ignites_Child
 */

?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<?php ignites_post_thumbnail(); ?>
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

		<div class="entry-content">
			<?php
			if ( is_home() || is_front_page() || is_search() || is_archive() ) :
				?>
				<p class="m-0 entry-excerpt"><?php the_excerpt(); ?></p>
				<?php
			else :
				the_content(
					sprintf(
						wp_kses(
							/* translators: %s: Name of current post. Only visible to screen readers */
							__( 'Continue reading<span class="screen-reader-text"> "%s"</span>', 'ignites-child' ),
							array( 'span' => array( 'class' => array() ) )
						),
						get_the_title()
					)
				);
				wp_link_pages(
					array(
						'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'ignites-child' ),
						'after'  => '</div>',
					)
				);
			endif;
			?>
		</div>

		<?php get_template_part( 'template-parts/entry', 'footer' ); ?>
	</div>
</article>
