<?php
/**
 * Ignites Child — entry footer (date + reading time + tags).
 *
 * Shared between template-parts/content.php and content-hero.php so
 * the trio of date/reading-time/tags renders identically in regular
 * cards and the hero card.
 *
 * @package Ignites_Child
 */
?>
<footer class="entry-footer">
	<span class="post-date"><?php echo esc_html( ignites_child_post_date() ); ?></span>
	<?php
	$ignites_child_rt = ignites_child_reading_time();
	if ( $ignites_child_rt ) :
		?>
		<span class="reading-time"><?php echo esc_html( $ignites_child_rt ); ?></span>
		<?php
	endif;

	$ignites_child_tags = get_the_tag_list( '<span class="tags-links">', '', '</span>' );
	if ( $ignites_child_tags ) {
		echo wp_kses_post( $ignites_child_tags );
	}
	?>
</footer>
