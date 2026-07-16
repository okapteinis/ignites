<?php
/**
 * Ignites Child — per-category post footnote (single posts only).
 *
 * Rendered from template-parts/content.php immediately before the
 * entry-footer part. Renders nothing unless the post is in one of
 * teksti/saites/podkasts (by slug — bildes and everything else skip).
 *
 * @package Ignites_Child
 */

if ( ! is_singular( 'post' ) ) {
	return;
}

$ignites_fn_slug = ignites_child_footnote_slug( get_the_ID() );
if ( '' === $ignites_fn_slug ) {
	return;
}

$ignites_fn_lang = ignites_child_footnote_lang();
$ignites_fn_map  = ignites_child_footnote_map();
$ignites_fn      = $ignites_fn_map[ $ignites_fn_slug ][ $ignites_fn_lang ];
$ignites_fn_ui   = ignites_child_footnote_ui( $ignites_fn_lang );

$ignites_fn_kses = array(
	'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ),
);
?>
<aside class="post-footnote" aria-label="<?php echo esc_attr( $ignites_fn_ui['social'] ); ?>">

	<?php foreach ( $ignites_fn['intro'] as $ignites_fn_para ) : ?>
		<p class="fn-intro"><em><?php echo wp_kses( $ignites_fn_para, $ignites_fn_kses ); ?></em></p>
	<?php endforeach; ?>

	<p class="fn-rss"><em>
		<span class="fn-icon" aria-hidden="true"><?php ignites_child_footnote_icon( 'rss-fill.svg' ); ?></span>
		<?php echo esc_html( $ignites_fn['rss_pre'] ); ?><a href="<?php echo esc_url( $ignites_fn['feed'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $ignites_fn['rss_label'] ); ?></a><?php
		if ( ! empty( $ignites_fn['apple'] ) ) {
			echo esc_html( $ignites_fn['apple_join'] ) . '<a href="' . esc_url( $ignites_fn['apple'] ) . '" target="_blank" rel="noopener">Apple Podcasts</a>';
		}
		?>.
	</em></p>

	<div class="fn-subscribe">
		<h2 class="fn-subscribe-head"><?php echo esc_html( $ignites_fn['sub_head'] ); ?></h2>
		<p class="fn-subscribe-body"><?php echo esc_html( $ignites_fn['sub_body'] ); ?></p>
		<form class="fn-subscribe-form"
			method="get" action="https://vestule.kapteinis.lv/subscription/form"
			data-rest="<?php echo esc_url( rest_url( 'ignites/v1/subscribe' ) ); ?>"
			data-nonce-url="<?php echo esc_url( rest_url( 'ignites/v1/subscribe-nonce' ) ); ?>"
			data-post="<?php echo esc_attr( get_the_ID() ); ?>"
			data-lang="<?php echo esc_attr( $ignites_fn_lang ); ?>"
			data-msg-error="<?php echo esc_attr( $ignites_fn_ui['error'] ); ?>">
			<label class="screen-reader-text" for="fn-email"><?php echo esc_attr( $ignites_fn_ui['placeholder'] ); ?></label>
			<input type="email" id="fn-email" name="email" required
				placeholder="<?php echo esc_attr( $ignites_fn_ui['placeholder'] ); ?>" />
			<input type="text" name="fn_website" class="fn-hp" tabindex="-1" autocomplete="off" aria-hidden="true" />
			<button type="submit"><?php echo esc_html( $ignites_fn_ui['button'] ); ?></button>
			<?php // interaction-only: invisible unless CF actually challenges; theme synced by JS before render (v1.3.1 P2). ?>
			<div class="cf-turnstile"
				data-sitekey="<?php echo esc_attr( IGNITES_FN_TURNSTILE_SITEKEY ); ?>"
				data-language="<?php echo esc_attr( $ignites_fn_lang ); ?>"
				data-appearance="interaction-only"
				data-size="flexible"
				data-theme="auto"></div>
			<p class="fn-msg" role="status" aria-live="polite" hidden></p>
			<p class="fn-fine"><?php echo esc_html( $ignites_fn_ui['fine'] ); ?></p>
			<noscript><p class="fn-fine"><?php echo wp_kses( $ignites_fn_ui['nojs'], $ignites_fn_kses ); ?></p></noscript>
		</form>
	</div>

	<ul class="fn-social" aria-label="<?php echo esc_attr( $ignites_fn_ui['social'] ); ?>">
		<?php foreach ( ignites_child_footnote_socials() as $ignites_fn_s ) : ?>
			<li>
				<a href="<?php echo esc_url( $ignites_fn_s['url'] ); ?>" rel="me noopener" target="_blank"
					aria-label="<?php echo esc_attr( $ignites_fn_s['name'] ); ?>" title="<?php echo esc_attr( $ignites_fn_s['name'] ); ?>">
					<span class="fn-icon" aria-hidden="true"><?php ignites_child_footnote_icon( $ignites_fn_s['icon'] ); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>

</aside>
