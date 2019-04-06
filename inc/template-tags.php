<?php
/**
 * Custom template tags for this theme
 *
 * Eventually, some of the functionality here could be replaced by core features.
 *
 * @package Ignites
 */

if ( ! function_exists( 'ignites_posted_on' ) ) :
	/**
	 * Prints HTML with meta information for the current post-date/time.
	 */
	function ignites_posted_on() {
		echo '<span class="posted-on">' . wp_kses_post(get_the_date()) . '</span>'; // WPCS: XSS OK.
	}
endif;

if ( ! function_exists( 'ignites_posted_by' ) ) :
	/**
	 * Prints HTML with meta information for the current author.
	 */
	function ignites_posted_by() {?>

		<span class="byline">
            <span class="author vcard">
                <a class="url fn n" href=" <?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) )?>"><?php echo esc_html( get_the_author() )?></a>
            </span>
		</span>

	<?php }
endif;

if ( ! function_exists( 'ignites_entry_footer' ) ) :
	/**
	 * Prints HTML with meta information for the categories, tags and comments.
	 */
	function ignites_entry_footer() {
		// Hide category and tag text for pages.
		if ( 'post' === get_post_type() ) {
			if (is_single()) {
				echo wp_kses_post ( get_the_tag_list( '<span class="tags-links"><b>Tags:</b> ', ',', '</span>' ));
			}else{
				echo '<div class="wrap-entry-meta">';
				echo '<div class="entry-avatar">'.get_avatar( get_the_author_meta( 'ID' ), 55 ).'</div>';
				echo '<div class="entry-meta">';
				ignites_posted_by();
				echo'<br>';
				ignites_posted_on();
				echo '</div></div>';
			}
		}
		echo '<div class="user-details">';
		if ( ! is_single() && ! post_password_required() && ( comments_open() || get_comments_number() ) ) {
			echo '<span class="comments-link">';
			comments_popup_link(
				sprintf(
					wp_kses(
					/* translators: %s: post title */
						__( 'Leave a Comment<span class="screen-reader-text"> on %s</span>', 'ignites' ),
						array(
							'span' => array(
								'class' => array(),
							),
						)
					),
					get_the_title()
				)
			);
			echo '</span>';
		}

		edit_post_link(
			sprintf(
				wp_kses(
				/* translators: %s: Name of current post. Only visible to screen readers */
					__( 'Edit <span class="screen-reader-text">%s</span>', 'ignites' ),
					array(
						'span' => array(
							'class' => array(),
						),
					)
				),
				get_the_title()
			),
			'<span class="edit-link">',
			'</span>'
		);
		echo '</div>';
	}
endif;

if ( ! function_exists( 'ignites_post_thumbnail' ) ) :
	/**
	 * Displays an optional post thumbnail.
	 *
	 * Wraps the post thumbnail in an anchor element on index views, or a div
	 * element when on single views.
	 */
	function ignites_post_thumbnail() {
		if ( post_password_required() || is_attachment() || ! has_post_thumbnail() ) {
			return;
		}

		if ( is_singular() ) :
			?>

			<div class="post-thumbnail">
				<?php the_post_thumbnail('ignites-landscape'); ?>
			</div><!-- .post-thumbnail -->

		<?php else : ?>

		<a class="post-thumbnail" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
			<?php
			the_post_thumbnail( 'ignites-landscape', array(
				'alt' => the_title_attribute( array(
					'echo' => false,
				) ),
			) );
			?>
		</a>

		<?php
		endif; // End is_singular().
	}
endif;
