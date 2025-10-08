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
				the_content( sprintf(
					/* translators: %s: Name of current post. */
					wp_kses( __( 'Continue reading %s <span class="meta-nav">&rarr;</span>', 'ignites' ), array( 'span' => array( 'class' => array() ) ) ),
					the_title( '<span class="screen-reader-text">"', '"</span>', false )
				) );
			}else{
				the_content();
			}


			wp_link_pages( array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'ignites' ),
				'after'  => '</div>',
			) );
			?>
        </div><!-- .entry-content -->
    </div>
</article><!-- #post-<?php the_ID(); ?> -->
