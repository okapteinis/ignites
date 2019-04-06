<?php
/**
 * Template part for displaying results in search pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Ignites
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<?php ignites_post_thumbnail(); ?>
    <div class="wrap-content">
        <div class="entry-category">
			<?php
			    echo wp_kses_post(get_the_category_list( "&#44; "));
			?>
        </div>
        <header class="entry-header">
			<?php
			if ( is_singular() ) :
				the_title( '<h1 class="entry-title">', '</h1>' );
			else :
				the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
			endif;

			if ( 'post' === get_post_type() ) :
				?>
			<?php endif; ?>
        </header><!-- .entry-header -->

        <div class="entry-content">
			<?php

			if(is_home()|| is_front_page()|| is_search() || is_archive()){?>
                <p class="m-0"><?php the_excerpt(); ?></p>
				<?php
			}else{
				the_content( sprintf(
					wp_kses(
					/* translators: %s: Name of current post. Only visible to screen readers */
						__( 'Continue reading<span class="screen-reader-text"> "%s"</span>', 'ignites' ),
						array(
							'span' => array(
								'class' => array(),
							),
						)
					),
					get_the_title()
				) );
			}


			wp_link_pages( array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'ignites' ),
				'after'  => '</div>',
			) );
			?>
        </div><!-- .entry-content -->

        <footer class="entry-footer">
			<?php ignites_entry_footer(); ?>
        </footer><!-- .entry-footer -->
    </div>
</article><!-- #post-<?php the_ID(); ?> -->
