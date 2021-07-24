<?php

/**
 * Posts numberic navigation
 */

if (!function_exists('ignites_num_post_nav')) :

	function ignites_num_post_nav()
	{
		global $wp_query;

		$big = 999999999; // need an unlikely integer

		echo wp_kses_post(paginate_links(array(
			'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
			'format' => '?paged=%#%',
			'current' => max(1, get_query_var('paged')),
			'total' => $wp_query->max_num_pages
		)));
	}
endif;



/**
 * Load all comments in the single post.
 */

if (!function_exists('ignites_post_comment')) :

	function ignites_post_comment($comment, $args, $depth)
	{
		// $GLOBALS['comment'] = $comment;

		if ('pingback' == $comment->comment_type || 'trackback' == $comment->comment_type) : ?>

			<li id="comment-<?php comment_ID(); ?>" <?php comment_class('media'); ?>>

				<article id="div-comment-<?php comment_ID(); ?>" class="comment-body media mb-5">
					<a class="pull-left" href="#">
						<?php if (0 != $args['avatar_size']) echo get_avatar($comment, $args['avatar_size']); ?>
					</a>

					<div class="media-body">
						<div class="media-body-wrap card">

							<div class="card-header">
								<div class="div">
									<?php /* translators: %s: author name*/ ?>
									<h4 class="mt-0"><?php printf(wp_kses('%s <span class="says">says:</span>', 'ignites'), sprintf('<cite class="fn">%s</cite>', get_comment_author_link(), 'ignites')); ?></h4>
									<div class="comment-meta">
										<a href="<?php echo esc_url(get_comment_link($comment->comment_ID)); ?>">
											<time datetime="<?php comment_time('c'); ?>">
												<?php
												/* translators: 1%s: date, 2%s: time. */
												printf(esc_html_x('%1$s at %2$s', '1: date, 2: time', 'ignites'), esc_html(get_comment_date()), esc_html(get_comment_time())); ?>
											</time>
										</a>
										<?php edit_comment_link(__('<span style="margin-left: 5px;" class="lnr lnr-pencil"></span> Edit', 'ignites'), '<span class="edit-link">', '</span>'); ?>
									</div>
								</div>
								<?php comment_reply_link(
									array_merge(
										$args,
										array(
											'add_below' => 'div-comment',
											'depth' 	=> $depth,
											'max_depth' => $args['max_depth'],
											'before' 	=> '<div class="reply comment-reply">',
											'after' 	=> '</div><!-- .reply -->'
										)
									)
								); ?>
							</div>

							<?php if ('0' == $comment->comment_approved) : ?>
								<p class="comment-awaiting-moderation"><?php esc_attr('Your comment is awaiting moderation.', 'ignites'); ?></p>
							<?php endif; ?>

							<div class="comment-content card-block">
								<?php comment_text(); ?>
							</div><!-- .comment-content -->

						</div>
					</div><!-- .media-body -->

				</article><!-- .comment-body -->


			<?php else : ?>

			<li id="comment-<?php comment_ID(); ?>" <?php comment_class(empty($args['has_children']) ? '' : 'parent'); ?>>
				<article id="div-comment-<?php comment_ID(); ?>" class="comment-body media mb-5">
					<a class="pull-left" href="#">
						<?php if (0 != $args['avatar_size']) echo get_avatar($comment, $args['avatar_size']); ?>
					</a>

					<div class="media-body">
						<div class="media-body-wrap card">

							<div class="card-header">
								<div class="div">
									<h4 class="mt-0"><?php printf(wp_kses('%s <span class="says">says:</span>', 'ignites'), sprintf('<cite class="fn">%s</cite>', get_comment_author_link())); ?></h4>
									<div class="comment-meta">
										<a href="<?php echo esc_url(get_comment_link($comment->comment_ID)); ?>">
											<time datetime="<?php comment_time('c'); ?>">
												<?php
												/* translators: 1%s: date, 2%s: time. */
												printf(esc_html_x('%1$s at %2$s', '1: date, 2: time', 'ignites'), esc_html(get_comment_date()), esc_html(get_comment_time())); ?>
											</time>
										</a>
										<?php edit_comment_link(__('<span style="margin-left: 5px;" class="lnr lnr-pencil"></span> Edit', 'ignites'), '<span class="edit-link">', '</span>'); ?>
									</div>
								</div>
								<?php comment_reply_link(
									array_merge(
										$args,
										array(
											'add_below' => 'div-comment',
											'depth' 	=> $depth,
											'max_depth' => $args['max_depth'],
											'before' 	=> '<div class="reply comment-reply">',
											'after' 	=> '</div><!-- .reply -->'
										)
									)
								); ?>
							</div>

							<?php if ('0' == $comment->comment_approved) : ?>
								<p class="comment-awaiting-moderation"><?php esc_html_e('Your comment is awaiting moderation.', 'ignites'); ?></p>
							<?php endif; ?>

							<div class="comment-content card-block">
								<?php comment_text(); ?>
							</div><!-- .comment-content -->

						</div>
					</div><!-- .media-body -->

				</article><!-- .comment-body -->

	<?php
		endif;
	}
endif;


/**
 * Layout Options
 */

if (!function_exists('ignites_layout_option')) :
	function ignites_layout_option()
	{

		$side_layout =  get_theme_mod("ignites_sidebar_settings");

		if ($side_layout == 'no-sidebar') {
			echo esc_html($layout_class = "col-lg-12 fullwidth-content");
		} else {
			echo esc_html($layout_class = "col-lg-8");
		}
	}
endif;


/**
 * Custom Excerpt Length
 */

function ignites_excerpt_length($length)
{
	return 58;
}
add_filter('excerpt_length', 'ignites_excerpt_length', 999);
