<?php
/**
 * Custom template tags for this theme
 *
 * Eventually, some of the functionality here could be replaced by core features.
 *
 * @package Ignites
 */

if ( ! function_exists( 'ignites_get_posted_on' ) ) :
	/**
	 * Returns HTML with meta information for the current post-date/time.
	 */
	function ignites_get_posted_on() {
		$time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';
		if ( get_the_time( 'U' ) !== get_the_modified_time( 'U' ) ) {
			$time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time><time class="updated" datetime="%3$s">%4$s</time>';
		}

		$time_string = sprintf(
			$time_string,
			esc_attr( get_the_date( DATE_W3C ) ),
			esc_html( get_the_date() ),
			esc_attr( get_the_modified_date( DATE_W3C ) ),
			esc_html( get_the_modified_date() )
		);

		return '<span class="posted-on">' . $time_string . '</span>';
	}
endif;

if ( ! function_exists( 'ignites_get_posted_by' ) ) :
	/**
	 * Returns HTML with meta information for the current author.
	 */
	function ignites_get_posted_by() {
		return sprintf(
			'<span class="byline"><span class="author vcard"><a class="url fn n" href="%1$s">%2$s</a></span></span>',
			esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
			esc_html( get_the_author() )
		);
	}
endif;

if ( ! function_exists( 'ignites_entry_footer' ) ) :
	/**
	 * Prints HTML with meta information for the categories, tags and comments.
	 */
	function ignites_entry_footer() {
		$footer_meta = '';

		// Hide category and tag text for pages.
		if ( 'post' === get_post_type() ) {
			if ( is_single() ) {
				/* translators: used between list items, there is a space after the comma */
				$tags_list = get_the_tag_list( '', esc_html_x( ', ', 'list item separator', 'ignites' ) );
				if ( $tags_list ) {
					$footer_meta .= sprintf(
						'<span class="tags-links"><strong>%1$s</strong> %2$s</span>',
						esc_html__( 'Tags:', 'ignites' ),
						$tags_list
					);
				}
			} else {
				$footer_meta .= '<div class="wrap-entry-meta">';
				$footer_meta .= '<div class="entry-avatar">' . get_avatar( get_the_author_meta( 'ID' ), 55 ) . '</div>';
				$footer_meta .= '<div class="entry-meta">';
				$footer_meta .= ignites_get_posted_by();
				$footer_meta .= '<br>';
				$footer_meta .= ignites_get_posted_on();
				$footer_meta .= '</div></div>';
			}
		}

		$user_details_html = '';
		if ( ! is_single() && ! post_password_required() && ( comments_open() || get_comments_number() ) ) {
			$user_details_html .= '<span class="comments-link">';
			$user_details_html .= wp_kses_post(
				get_comments_popup_link(
					sprintf(
						/* translators: %s: post title */
						__( 'Leave a Comment<span class="screen-reader-text"> on %s</span>', 'ignites' ),
						get_the_title()
					),
					__( '1 Comment', 'ignites' ),
					__( '% Comments', 'ignites' ),
					'', // CSS class
					sprintf(
						/* translators: %s: post title */
						__( 'Comments are off for %s', 'ignites' ),
						get_the_title()
					)
				)
			);
			$user_details_html .= '</span>';
		}

		$edit_link = get_edit_post_link();
		if ( $edit_link ) {
			$user_details_html .= '<span class="edit-link"><a href="' . esc_url( $edit_link ) . '">' .
				sprintf(
					wp_kses(
						/* translators: %s: Name of current post. Only visible to screen readers */
						__( 'Edit <span class="screen-reader-text">%s</span>', 'ignites' ),
						array( 'span' => array( 'class' => array() ) )
					),
					get_the_title()
				) . '</a></span>';
		}

		if ( $user_details_html ) {
			$footer_meta .= '<div class="user-details">' . $user_details_html . '</div>';
		}

		echo wp_kses_post( $footer_meta );
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
