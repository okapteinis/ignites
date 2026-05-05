<?php
/**
 * Ignites Child — hero post card (first post on home/archive).
 *
 * Mirrors template-parts/content.php with `post-hero` class added so
 * the §3.3 CSS expands the card to full grid width and bumps the title
 * to text-2xl. Closes ojars/ignites#8 N6 (hero markup was inlined in
 * archive.php; pulling it into a template part dedupes the markup that
 * differs from the regular card by just one class.)
 *
 * @package Ignites_Child
 */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-hero' ); ?>>
	<?php ignites_post_thumbnail(); ?>
	<div class="wrap-content">
		<div class="entry-category">
			<?php
			/* translators: separator between linked category names — kept as plain ", " */
			echo wp_kses_post( get_the_category_list( _x( ', ', 'category list separator', 'ignites-child' ) ) );
			?>
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
			<?php
			$ignites_child_hero_rt = ignites_child_reading_time();
			if ( $ignites_child_hero_rt ) :
				?>
				<span class="reading-time"><?php echo esc_html( $ignites_child_hero_rt ); ?></span>
			<?php endif; ?>
			<?php
			$ignites_child_hero_tags = get_the_tag_list( '<span class="tags-links">', '', '</span>' );
			if ( $ignites_child_hero_tags ) {
				echo wp_kses_post( $ignites_child_hero_tags );
			}
			?>
		</footer>
	</div>
</article>
