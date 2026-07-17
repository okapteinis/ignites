<?php
/**
 * Ignites Child — per-category post footnote (single posts only).
 *
 * v1.4.0 three-section redesign. Rendered from template-parts/content.php
 * immediately before the entry-footer part. Renders nothing unless the post
 * is in one of teksti/saites/podkasts (by slug — bildes and everything else
 * skip). Structure:
 *
 *   0. post-context note   — .fn-context (saites only; closes the post)
 *   umbrella hook heading  — .fn-hook (ties the sections into one family)
 *   S1 subscribe           — .fn-section.fn-subscribe (form UNCHANGED from
 *                            v1.3.1: same classes/data-attrs; only the
 *                            container moved)
 *   S2 follow              — .fn-section.fn-follow: About-page card grid,
 *                            5 profiles + RSS (+ Apple Podcasts on podcast)
 *   S3 fediverse comments  — reserved slot; renders empty until the post
 *                            carries the IGNITES_FN_TOOT_META toot link
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
<aside class="post-footnote">

	<?php foreach ( $ignites_fn['context'] as $ignites_fn_para ) : ?>
		<p class="fn-context"><em><?php echo wp_kses( $ignites_fn_para, $ignites_fn_kses ); ?></em></p>
	<?php endforeach; ?>

	<h2 class="fn-hook"><?php echo esc_html( $ignites_fn_ui['hook'] ); ?></h2>

	<section class="fn-section fn-subscribe">
		<p class="fn-subscribe-body"><?php echo esc_html( $ignites_fn['sub_ask'] ); ?></p>
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
	</section>

	<?php // The About-page (/par-mani) labelled-card solution: same .social-links/
	// .social-list/.social-link classes (styled in style.css) so cards render
	// identically; footnote-local spacing overrides live in post-footnote.css. ?>
	<section class="fn-section fn-follow social-links" aria-label="<?php echo esc_attr( $ignites_fn_ui['social'] ); ?>">
		<h2><?php echo esc_html( $ignites_fn_ui['social_head'] ); ?></h2>
		<ul class="social-list">
			<?php foreach ( ignites_child_footnote_follow_cards( $ignites_fn_slug, $ignites_fn_lang ) as $ignites_fn_s ) : ?>
				<li>
					<a class="social-link" href="<?php echo esc_url( $ignites_fn_s['url'] ); ?>" rel="me noopener" target="_blank">
						<span class="social-icon" aria-hidden="true"><?php ignites_child_footnote_icon( $ignites_fn_s['icon'] ); ?></span>
						<span class="social-label">
							<span class="social-name"><?php echo esc_html( $ignites_fn_s['name'] ); ?></span>
							<?php if ( '' !== $ignites_fn_s['handle'] ) : ?>
								<span class="social-handle"><?php echo esc_html( $ignites_fn_s['handle'] ); ?></span>
							<?php endif; ?>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>

	<?php
	// S3 — reserved fediverse-comments slot: outputs NOTHING until the post
	// carries a linked toot in the pinned IGNITES_FN_TOOT_META post meta.
	ignites_child_footnote_comments( get_the_ID(), $ignites_fn_lang );
	?>

</aside>
