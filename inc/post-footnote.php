<?php
/**
 * Ignites Child — per-category post footnote: RSS line + Listmonk subscribe
 * + social row, plus the saites homepage exclusion. (v1.3.0)
 *
 * Feed URLs and Listmonk list UUIDs are HARDCODED by design: all 6 URLs are
 * verified live, while runtime get_category_feed_link() derivation was only
 * proven canonical on archive heads, not in single-post context. The
 * subscribe endpoint POSTs server-side to Listmonk's PUBLIC loopback API —
 * externally /api/* is SSOwat-gated, and loopback keeps credentials out of
 * WordPress entirely. The list UUID is resolved server-side from the post's
 * category + language; a client can never pick an arbitrary list.
 *
 * @package Ignites_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const IGNITES_FN_LISTMONK_URL = 'http://127.0.0.1:22940/api/public/subscription';

// Cloudflare Turnstile (managed mode). The sitekey is public by design (it
// ships in cached HTML); the SECRET lives in wp-config as
// IGNITES_TURNSTILE_SECRET, materialized at deploy from SOPS
// (cloudflare.turnstile_ojars_secret) — never in this repo.
const IGNITES_FN_TURNSTILE_SITEKEY = '0x4AAAAAAD3RFq5uNKHa8R4_';
const IGNITES_FN_TURNSTILE_VERIFY  = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

/**
 * Footnote config, keyed [category-slug][lang]. Copy is operator-authored
 * (the saites intro is lifted verbatim from the retired hand-typed footer).
 */
function ignites_child_footnote_map() {
	return array(
		'saites' => array(
			'lv' => array(
				'intro'     => array(
					'Šis ir ikdienas saišu apkopojums — saites uz rakstiem, kurus izlasīju iepriekšējā dienā.',
					'Padoms: maksas rakstu bieži var izlasīt, tā saiti sameklējot vietnē <a href="https://archive.ph" target="_blank" rel="noopener">archive.ph</a>; rakstu citā valodā var iztulkot ar <a href="https://hugo.lv/lv/Translate/Website" target="_blank" rel="noopener">hugo.lv</a>.',
				),
				'rss_text'  => 'Šo apkopojumu var lasīt arī RSS plūsmā:',
				'rss_label' => 'saites',
				'feed'      => 'https://ojars.kapteinis.lv/tema/saites/feed/',
				'sub_head'  => 'Saņem saites e-pastā',
				'sub_body'  => 'Īss ikdienas kopsavilkums ar to, ko izlasīju. Bez liekām vēstulēm — atrakstīties vari jebkurā brīdī.',
				'list_uuid' => 'b79d6df1-305c-434f-a061-6de43732c0bd',
			),
			'en' => array(
				'intro'     => array(
					'This is a daily link digest — links to the articles I read the previous day.',
					'Tip: a paywalled article can often be read by searching for its URL on <a href="https://archive.ph" target="_blank" rel="noopener">archive.ph</a>; an article in another language can be translated with <a href="https://hugo.lv/en/Translate/Website" target="_blank" rel="noopener">hugo.lv</a>.',
				),
				'rss_text'  => 'You can also follow this digest via RSS:',
				'rss_label' => 'links',
				'feed'      => 'https://ojars.kapteinis.lv/en/category/links/feed/',
				'sub_head'  => 'Get the links by email',
				'sub_body'  => 'A short daily roundup of what I read. No spam, unsubscribe anytime.',
				'list_uuid' => '6a611820-5a3b-4866-a8c4-8c82146d42da',
			),
		),
		'teksti' => array(
			'lv' => array(
				'intro'     => array(),
				'rss_text'  => 'Jaunos rakstus var lasīt arī RSS plūsmā:',
				'rss_label' => 'blogs',
				'feed'      => 'https://ojars.kapteinis.lv/tema/teksti/feed/',
				'sub_head'  => 'Seko jaunajiem rakstiem',
				'sub_body'  => 'Kad publicēju jaunu rakstu, atsūtīšu to tev e-pastā. Bez liekām vēstulēm.',
				'list_uuid' => '336641a6-8220-4307-8a90-44987e905675',
			),
			'en' => array(
				'intro'     => array(),
				'rss_text'  => 'Follow new posts via RSS:',
				'rss_label' => 'blog',
				'feed'      => 'https://ojars.kapteinis.lv/en/category/blog/feed/',
				'sub_head'  => 'Follow new posts',
				'sub_body'  => 'When I publish something new, I\'ll email it to you. No spam.',
				'list_uuid' => '23e2075b-4e90-480d-a892-e39cb1055c68',
			),
		),
		'podkasts' => array(
			'lv' => array(
				'intro'     => array(),
				'rss_text'  => 'Klausies un abonē:',
				'rss_label' => 'RSS',
				'feed'      => 'https://ojars.kapteinis.lv/tema/podkasts/feed/',
				'apple'     => 'https://itunes.apple.com/lv/podcast/podkasts-ojars-kapteinis/id1204929568',
				'sub_head'  => 'Uzzini par jaunām epizodēm',
				'sub_body'  => 'Kad iznāk jauna epizode, atsūtīšu ziņu e-pastā.',
				'list_uuid' => '31a0127d-84c1-4da7-a3e3-90e699e78faa',
			),
			'en' => array(
				'intro'     => array(),
				'rss_text'  => 'Listen and subscribe:',
				'rss_label' => 'RSS',
				'feed'      => 'https://ojars.kapteinis.lv/en/category/podcast/feed/',
				'apple'     => 'https://itunes.apple.com/lv/podcast/podkasts-ojars-kapteinis/id1204929568',
				'sub_head'  => 'Get new episodes',
				'sub_body'  => 'When a new episode is out, I\'ll drop you an email.',
				'list_uuid' => 'f230e0a0-0964-4cf7-b019-18b246a84984',
			),
		),
	);
}

/**
 * Per-language UI strings for the subscribe block (button/placeholder/
 * messages/fine print). Kept out of the per-category map — identical for
 * all three categories.
 */
function ignites_child_footnote_ui( $lang ) {
	$ui = array(
		'lv' => array(
			'placeholder' => 'e-pasts',
			'button'      => 'Pierakstīties',
			'fine'        => 'Pierakstīšanos apstiprināsi e-pastā.',
			'ok'          => 'Gandrīz gatavs — apstiprini pierakstīšanos savā e-pastā.',
			'invalid'     => 'Lūdzu, ievadi derīgu e-pasta adresi.',
			'error'       => 'Neizdevās. Lūdzu, mēģini vēlāk.',
			'social'      => 'Sociālie tīkli',
			'nojs'        => 'Pierakstīties var arī <a href="https://vestule.kapteinis.lv/subscription/form" target="_blank" rel="noopener">abonēšanas lapā</a>.',
		),
		'en' => array(
			'placeholder' => 'email',
			'button'      => 'Subscribe',
			'fine'        => 'You\'ll confirm by email.',
			'ok'          => 'Almost done — confirm the subscription in your email.',
			'invalid'     => 'Please enter a valid email address.',
			'error'       => 'Something went wrong. Please try again later.',
			'social'      => 'Social networks',
			'nojs'        => 'You can also subscribe on the <a href="https://vestule.kapteinis.lv/subscription/form" target="_blank" rel="noopener">subscription page</a>.',
		),
	);
	return isset( $ui[ $lang ] ) ? $ui[ $lang ] : $ui['lv'];
}

/**
 * Social follow row — URLs recon-verified 2026-07-16 (webfinger / live
 * profiles). Bluesky is the Bridgy-Fed bridge of the Mastodon account with
 * a vanity handle — a real, followable profile. No Threads: no public
 * profile URL exists (operator decision 2026-07-16).
 */
function ignites_child_footnote_socials() {
	return array(
		array( 'name' => 'Mastodon', 'url' => 'https://kapteinis.lv/@ojars', 'icon' => 'mastodon.svg' ),
		array( 'name' => 'PixelFed', 'url' => 'https://pixel.kapteinis.lv/ojars', 'icon' => 'pixelfed.svg' ),
		array( 'name' => 'BookWyrm', 'url' => 'https://book.kapteinis.lv/user/ojars', 'icon' => 'bookwyrm.png' ),
		array( 'name' => 'Forgejo', 'url' => 'https://git.kapteinis.lv/ojars', 'icon' => 'forgejo.svg' ),
		array( 'name' => 'Bluesky', 'url' => 'https://bsky.app/profile/ojars.kapteinis.lv', 'icon' => 'bluesky.svg' ),
	);
}

/**
 * Current front-end language, constrained to the two the site serves.
 */
function ignites_child_footnote_lang() {
	$lang = function_exists( 'qtranxf_getLanguage' ) ? qtranxf_getLanguage() : 'lv';
	return in_array( $lang, array( 'lv', 'en' ), true ) ? $lang : 'lv';
}

/**
 * Resolve a post to its footnote category slug (teksti|saites|podkasts), or
 * '' when the post is in none of them (bildes and everything else render no
 * footnote). Recon: every post is in at most ONE of the three categories.
 */
function ignites_child_footnote_slug( $post_id ) {
	$map = ignites_child_footnote_map();
	foreach ( (array) get_the_category( $post_id ) as $cat ) {
		if ( isset( $map[ $cat->slug ] ) ) {
			return $cat->slug;
		}
	}
	return '';
}

/**
 * Inline a theme-bundled icon (page-about.php pattern): SVGs are emitted
 * inline so fill="currentColor" inherits; the one PNG (bookwyrm) falls back
 * to an <img>. Trusted theme files only — $file comes from our own arrays.
 */
function ignites_child_footnote_icon( $file ) {
	$path = get_stylesheet_directory() . '/assets/icons/' . $file;
	if ( substr( $file, -4 ) === '.svg' && file_exists( $path ) ) {
		readfile( $path );
		return;
	}
	printf(
		'<img src="%s" alt="" width="20" height="20" loading="lazy" />',
		esc_url( get_stylesheet_directory_uri() . '/assets/icons/' . $file )
	);
}

/**
 * Assets — single posts only (the footnote renders nowhere else).
 */
function ignites_child_footnote_assets() {
	if ( ! is_singular( 'post' ) || '' === ignites_child_footnote_slug( get_the_ID() ) ) {
		return;
	}
	$css = get_stylesheet_directory() . '/assets/css/post-footnote.css';
	$js  = get_stylesheet_directory() . '/assets/js/post-footnote.js';
	wp_enqueue_style(
		'ignites-post-footnote',
		get_stylesheet_directory_uri() . '/assets/css/post-footnote.css',
		array( 'ignites-child' ),
		(string) filemtime( $css )
	);
	wp_enqueue_script(
		'ignites-post-footnote',
		get_stylesheet_directory_uri() . '/assets/js/post-footnote.js',
		array(),
		(string) filemtime( $js ),
		true
	);
	// Turnstile renders its own challenge at view time, so it is safe in
	// APO-cached pages (unlike a baked-in nonce). ?ver= suppressed — the URL
	// is Cloudflare's evergreen endpoint.
	wp_enqueue_script( 'cf-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null, true );
}
add_action( 'wp_enqueue_scripts', 'ignites_child_footnote_assets', 20 );

/* ============================================================================
 * Subscribe REST endpoint
 * ========================================================================== */

/**
 * Real client IP for rate limiting. nginx ngx_http_realip
 * (cloudflare-real-ip.conf, set_real_ip_from = the CF ranges,
 * real_ip_header CF-Connecting-IP) already rewrites REMOTE_ADDR to the real
 * visitor IP for CF-proxied requests — so REMOTE_ADDR here IS the validated
 * client IP. Never read the CF header in PHP: unvalidated, it is spoofable
 * on any non-CF path, and behind the validated realip layer it is redundant.
 */
function ignites_child_footnote_client_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
}

/**
 * Transient counter rate limit. Returns true when the caller is still under
 * $max events per $window seconds. The transient TTL starts at the first
 * event, so the window is fixed, not sliding — fine at these thresholds.
 */
function ignites_child_footnote_under_limit( $key, $max, $window ) {
	$key   = 'ign_fn_' . md5( $key );
	$count = (int) get_transient( $key );
	if ( $count >= $max ) {
		return false;
	}
	// set_transient preserves the original expiry only on some object caches;
	// re-setting with the full window on each hit slightly over-throttles,
	// which is the safe direction for an abuse brake.
	set_transient( $key, $count + 1, $window );
	return true;
}

add_action( 'rest_api_init', function () {
	// Nonce is fetched at submit time because pages are cached (Cloudflare
	// APO): a nonce baked into cached HTML outlives its 24h validity and
	// starts rejecting legitimate visitors. no-store keeps this tiny GET out
	// of every cache layer.
	register_rest_route( 'ignites/v1', '/subscribe-nonce', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'callback'            => function () {
			$resp = new WP_REST_Response( array( 'nonce' => wp_create_nonce( 'ignites_subscribe' ) ) );
			$resp->header( 'Cache-Control', 'no-store' );
			return $resp;
		},
	) );

	register_rest_route( 'ignites/v1', '/subscribe', array(
		'methods'             => 'POST',
		'permission_callback' => '__return_true',
		'callback'            => 'ignites_child_footnote_subscribe',
		'args'                => array(
			'email' => array( 'type' => 'string', 'required' => true ),
			'post'  => array( 'type' => 'integer', 'required' => true ),
			'lang'  => array( 'type' => 'string', 'required' => false ),
			'nonce' => array( 'type' => 'string', 'required' => true ),
			'turnstile' => array( 'type' => 'string', 'required' => false ),
			// Honeypot — real users never fill it; its name mimics a website field.
			'fn_website' => array( 'type' => 'string', 'required' => false ),
		),
	) );
} );

/**
 * Subscribe handler. Validation order: honeypot (fake success — never teach
 * a bot which field tripped it) → nonce → email → category resolve → rate
 * limits → loopback Listmonk POST. Listmonk sends the bilingual double-opt-in
 * confirmation itself (send_optin_confirmation=True); no mail logic here.
 */
function ignites_child_footnote_subscribe( WP_REST_Request $req ) {
	$lang = in_array( $req->get_param( 'lang' ), array( 'lv', 'en' ), true ) ? $req->get_param( 'lang' ) : 'lv';
	$ui   = ignites_child_footnote_ui( $lang );

	if ( '' !== trim( (string) $req->get_param( 'fn_website' ) ) ) {
		return new WP_REST_Response( array( 'message' => $ui['ok'] ), 200 );
	}

	if ( ! wp_verify_nonce( (string) $req->get_param( 'nonce' ), 'ignites_subscribe' ) ) {
		return new WP_REST_Response( array( 'message' => $ui['error'] ), 403 );
	}

	$email = sanitize_email( (string) $req->get_param( 'email' ) );
	if ( ! is_email( $email ) || strlen( $email ) > 254 ) {
		return new WP_REST_Response( array( 'message' => $ui['invalid'] ), 400 );
	}

	// Cloudflare Turnstile — REQUIRED and FAIL-CLOSED (operator directive
	// 2026-07-16): a missing secret, missing/oversized token, siteverify
	// error, timeout, or success!==true ALWAYS rejects. No fall-through.
	if ( ! defined( 'IGNITES_TURNSTILE_SECRET' ) || '' === IGNITES_TURNSTILE_SECRET ) {
		error_log( 'ignites footnote subscribe: IGNITES_TURNSTILE_SECRET undefined - rejecting (fail-closed)' );
		return new WP_REST_Response( array( 'message' => $ui['error'] ), 503 );
	}
	$ip       = ignites_child_footnote_client_ip();
	$ts_token = (string) $req->get_param( 'turnstile' );
	if ( '' === $ts_token || strlen( $ts_token ) > 2048 ) {
		return new WP_REST_Response( array( 'message' => $ui['error'] ), 403 );
	}
	$verify  = wp_remote_post( IGNITES_FN_TURNSTILE_VERIFY, array(
		'timeout' => 8,
		'body'    => array(
			'secret'   => IGNITES_TURNSTILE_SECRET,
			'response' => $ts_token,
			'remoteip' => $ip,
		),
	) );
	$verdict = is_wp_error( $verify ) ? null : json_decode( (string) wp_remote_retrieve_body( $verify ), true );
	if ( ! is_array( $verdict ) || true !== ( isset( $verdict['success'] ) ? $verdict['success'] : false ) ) {
		return new WP_REST_Response( array( 'message' => $ui['error'] ), 403 );
	}

	$slug = ignites_child_footnote_slug( (int) $req->get_param( 'post' ) );
	if ( '' === $slug ) {
		return new WP_REST_Response( array( 'message' => $ui['error'] ), 400 );
	}
	$map  = ignites_child_footnote_map();
	$uuid = $map[ $slug ][ $lang ]['list_uuid'];

	// Anti-abuse: confirmation-email bombing brake. Per-IP 5/hour, per-email
	// 2/day. Rejections are polite and generic (429 body == the ok message
	// shape) so probing reveals nothing. The per-email KEY strips a +tag
	// subaddress so user+1@/user+2@ don't mint fresh keys against the same
	// inbox (2026-07-16 security-review finding); the exact address the user
	// typed still goes to Listmonk untouched.
	$email_key = strtolower( preg_replace( '/\+[^@]*@/', '@', $email ) );
	if ( ! ignites_child_footnote_under_limit( 'ip_' . $ip, 5, HOUR_IN_SECONDS )
		|| ! ignites_child_footnote_under_limit( 'em_' . $email_key, 2, DAY_IN_SECONDS ) ) {
		return new WP_REST_Response( array( 'message' => $ui['error'] ), 429 );
	}

	// Payload safety: $email passed is_email (no CR/LF/quotes survive),
	// name is a fixed '', the UUID comes from our own map, and
	// wp_json_encode escapes the rest — no header/JSON injection path.
	$resp = wp_remote_post( IGNITES_FN_LISTMONK_URL, array(
		'timeout' => 8,
		'headers' => array( 'Content-Type' => 'application/json' ),
		'body'    => wp_json_encode( array(
			'email'      => $email,
			'name'       => '',
			'list_uuids' => array( $uuid ),
		) ),
	) );

	if ( is_wp_error( $resp ) ) {
		error_log( 'ignites footnote subscribe: listmonk unreachable: ' . $resp->get_error_message() );
		return new WP_REST_Response( array( 'message' => $ui['error'] ), 502 );
	}

	$code = (int) wp_remote_retrieve_response_code( $resp );
	if ( 200 === $code || 409 === $code ) {
		// 409/duplicate is reported as success — anti-enumeration.
		return new WP_REST_Response( array( 'message' => $ui['ok'] ), 200 );
	}

	error_log( 'ignites footnote subscribe: listmonk HTTP ' . $code . ': ' . substr( (string) wp_remote_retrieve_body( $resp ), 0, 200 ) );
	return new WP_REST_Response( array( 'message' => $ui['error'] ), 502 );
}

/* ============================================================================
 * Homepage exclusion — saites off the main page (LV and EN)
 * ========================================================================== */

/**
 * Hide the saites digest from the posts index (/ and /en/) ONLY. The
 * category archive, its category feed, and the site-wide /feed/ keep it:
 * is_home() is false on category archives, and is_feed() bails for every
 * feed request. category__not_in MERGES with anything another filter set —
 * the hide-untranslated filter in functions.php uses post__not_in, so the
 * two compose without touching each other's keys.
 */
add_action( 'pre_get_posts', function ( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( ! $query->is_home() || $query->is_feed() ) {
		return;
	}
	$excluded   = (array) $query->get( 'category__not_in' );
	$excluded[] = 1476; // saites — term_id verified in recon.
	$query->set( 'category__not_in', array_values( array_unique( $excluded ) ) );
} );
