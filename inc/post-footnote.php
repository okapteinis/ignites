<?php
/**
 * Ignites Child — per-category post footnote: post-context note + umbrella
 * hook + subscribe + follow-grid + reserved fediverse-comments slot, plus
 * the saites homepage exclusion. (v1.4.0 three-section redesign; subscribe
 * REST handler unchanged since v1.3.1.)
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

// App-level rate-limit exemption — mirrors the CF edge "IP Access Rules" skip
// (which exempts the same admin IP from the http_ratelimit phase), so operator
// testing doesn't self-block (v1.3.1 P1c). Turnstile still applies.
const IGNITES_FN_RL_EXEMPT_IPS = array( '46.109.199.200' );

/**
 * Footnote config, keyed [category-slug][lang]. Copy is operator-authored
 * (the saites intro is lifted verbatim from the retired hand-typed footer).
 */
function ignites_child_footnote_map() {
	return array(
		'saites' => array(
			'lv' => array(
				// Post-context note (v1.4.0): closes the post itself — saites is the
				// only category with one (podkasts' old prose was pure RSS/Apple
				// pointers, now S2 buttons — operator directive 2026-07-17: never
				// the same destination as both prose and button).
				'context'     => array(
					'Šis ir ikdienas saišu apkopojums — saites uz rakstiem, kurus izlasīju iepriekšējā dienā.',
					'Padoms: maksas rakstu bieži var izlasīt, tā saiti sameklējot vietnē <a href="https://archive.today" target="_blank" rel="noopener">archive.today</a>; rakstu citā valodā var iztulkot ar <a href="https://hugo.lv/lv/Translate/Website" target="_blank" rel="noopener">hugo.lv</a>.',
					'Izmantoju mākslīgo intelektu kā izpētes, faktu pārbaudes un rediģēšanas palīgrīku.',
				),
				'feed'        => 'https://ojars.kapteinis.lv/tema/saites/feed/',
				'sub_ask'     => 'Pieraksties, lai saņemtu manu ikdienas apkopojumu ar visu, ko izlasu. Neko vairāk.',
				'list_uuid'   => 'b79d6df1-305c-434f-a061-6de43732c0bd',
			),
			'en' => array(
				'context'     => array(
					'This is a daily link digest — links to the articles I read the previous day.',
					'Tip: a paywalled article can often be read by searching for its URL on <a href="https://archive.today" target="_blank" rel="noopener">archive.today</a>; an article in another language can be translated with <a href="https://hugo.lv/en/Translate/Website" target="_blank" rel="noopener">hugo.lv</a>.',
					'I use artificial intelligence as a research, fact-checking and editing assistant.',
				),
				'feed'        => 'https://ojars.kapteinis.lv/en/category/links/feed/',
				'sub_ask'     => 'Subscribe to my daily roundup of everything I read. Nothing else.',
				'list_uuid'   => '6a611820-5a3b-4866-a8c4-8c82146d42da',
			),
		),
		'teksti' => array(
			'lv' => array(
				'context'     => array(
					'Izmantoju mākslīgo intelektu kā izpētes, faktu pārbaudes un rediģēšanas palīgrīku.',
				),
				'feed'        => 'https://ojars.kapteinis.lv/tema/teksti/feed/',
				'sub_ask'     => 'Pieraksties, lai saņemtu manus rakstus. Neko vairāk.',
				'list_uuid'   => '336641a6-8220-4307-8a90-44987e905675',
			),
			'en' => array(
				'context'     => array(
					'I use artificial intelligence as a research, fact-checking and editing assistant.',
				),
				'feed'        => 'https://ojars.kapteinis.lv/en/category/blog/feed/',
				'sub_ask'     => 'Subscribe to my blog posts. Nothing else.',
				'list_uuid'   => '23e2075b-4e90-480d-a892-e39cb1055c68',
			),
		),
		'podkasts' => array(
			'lv' => array(
				'context'     => array(
					'Izmantoju mākslīgo intelektu kā izpētes, faktu pārbaudes un rediģēšanas palīgrīku.',
				),
				'feed'        => 'https://ojars.kapteinis.lv/tema/podkasts/feed/',
				'apple'       => 'https://itunes.apple.com/lv/podcast/podkasts-ojars-kapteinis/id1204929568',
				'sub_ask'     => 'Pieraksties, lai saņemtu manas jaunākās epizodes. Neko vairāk.',
				'list_uuid'   => '31a0127d-84c1-4da7-a3e3-90e699e78faa',
			),
			'en' => array(
				'context'     => array(
					'I use artificial intelligence as a research, fact-checking and editing assistant.',
				),
				'feed'        => 'https://ojars.kapteinis.lv/en/category/podcast/feed/',
				'apple'       => 'https://itunes.apple.com/lv/podcast/podkasts-ojars-kapteinis/id1204929568',
				'sub_ask'     => 'Subscribe to my newest podcast episodes. Nothing else.',
				'list_uuid'   => 'f230e0a0-0964-4cf7-b019-18b246a84984',
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
			// Umbrella hook heading over ALL footnote sections (operator-verbatim
			// LV, 2026-07-17 v1.4.0 redesign; replaces the v1.3.2 sub_hook).
			'hook'        => 'Tu neesi nonācis šeit veltīgi!',
			'fine'        => 'Visas adreses glabāju tikai savā privātajā datubāzē. Ar tām nedalīšos, neizmantošu citiem mērķiem un nesūtīšu mēstules. Pierakstīšanos jāapstiprina e-pastā, un atrakstīties var, kad vien ir tāda vēlme.',
			'ok'          => 'Pārbaudi e-pastu, lai apstiprinātu pierakstīšanos.',
			'ratelimited' => 'Par daudz mēģinājumu — uzgaidi brīdi.',
			'invalid'     => 'Lūdzu, ievadi derīgu e-pasta adresi.',
			'error'       => 'Neizdevās. Lūdzu, mēģini vēlāk.',
			'social'      => 'Sociālie tīkli',
			// TODO(operator): confirm final LV wording of the follow-section
			// heading before it's considered settled (task directive 2026-07-17).
			'social_head' => 'Seko man sociālajos tīklos',
			'nojs'        => 'Pierakstīties var arī <a href="https://vestule.kapteinis.lv/subscription/form" target="_blank" rel="noopener">abonēšanas lapā</a>.',
		),
		'en' => array(
			'placeholder' => 'email',
			'button'      => 'Subscribe',
			'hook'        => 'You didn\'t scroll all the way down here for nothing.',
			'fine'        => 'I keep every address in my own private database. I won\'t share it, use it for anything else, or send you spam. You\'ll confirm your subscription by email, and can unsubscribe whenever you want.',
			'ok'          => 'Check your email to confirm.',
			'ratelimited' => 'Too many tries — give it a minute.',
			'invalid'     => 'Please enter a valid email address.',
			'error'       => 'Something went wrong. Please try again later.',
			'social'      => 'Social networks',
			'social_head' => 'Follow me on socials',
			'nojs'        => 'You can also subscribe on the <a href="https://vestule.kapteinis.lv/subscription/form" target="_blank" rel="noopener">subscription page</a>.',
		),
	);
	return isset( $ui[ $lang ] ) ? $ui[ $lang ] : $ui['lv'];
}

/**
 * Social follow set — the SAME labelled-card solution as the /par-mani (About)
 * page (icon + name + handle). Bluesky RE-ADDED per the 2026-07-17 v1.4.0
 * redesign directive (supersedes the v1.3.2 no-Bluesky decision); its icon
 * was already vendored. URLs/handles mirror the About page's customizer
 * mods; icons are the theme-bundled set (bookwyrm.png matches About — in the
 * card layout the colored PNG reads fine, unlike the v1.3.1 icon-only row).
 */
function ignites_child_footnote_socials() {
	// rel="me" is an identity-verification assertion (Mastodon/IndieAuth) —
	// correct on these profile cards ONLY; non-profile cards (RSS/Apple) get
	// plain noopener via their own 'rel' below (/simplify 2026-07-17).
	return array(
		array( 'name' => 'Mastodon', 'url' => 'https://kapteinis.lv/@ojars',        'handle' => '@ojars@kapteinis.lv',       'icon' => 'mastodon.svg', 'rel' => 'me noopener' ),
		array( 'name' => 'PixelFed', 'url' => 'https://pixel.kapteinis.lv/ojars',   'handle' => '@ojars@pixel.kapteinis.lv', 'icon' => 'pixelfed.svg', 'rel' => 'me noopener' ),
		array( 'name' => 'BookWyrm', 'url' => 'https://book.kapteinis.lv/user/ojars', 'handle' => '@ojars@book.kapteinis.lv',  'icon' => 'bookwyrm.png', 'rel' => 'me noopener' ),
		array( 'name' => 'Forgejo',  'url' => 'https://git.kapteinis.lv/ojars',     'handle' => '@ojars@git.kapteinis.lv',   'icon' => 'forgejo.svg',  'rel' => 'me noopener' ),
		array( 'name' => 'Bluesky',  'url' => 'https://bsky.app/profile/ojars.kapteinis.lv', 'handle' => 'ojars.kapteinis.lv', 'icon' => 'bluesky.svg', 'rel' => 'me noopener' ),
	);
}

/**
 * SECTION 2 follow-grid cards: the 5 profile cards + a per-category/language
 * RSS card (feed URL straight from the hardcoded map above) + an Apple
 * Podcasts card on podcast posts only. The RSS card RETIRES the v1.3.x
 * standalone prose RSS line — a destination appears as a button OR prose,
 * never both (operator directive 2026-07-17).
 * RSS/Apple ship NAME-ONLY (handle => '' → the template skips the sub-line):
 * they have no @handle identity like the profiles, and a URL path is not a
 * reader-facing label (operator directive 2026-07-17 #2).
 * TODO(operator): a human descriptor sub-line can be added later by setting
 * 'handle' on these two cards.
 *
 * Takes the RESOLVED per-category/lang config (the template already holds it
 * as $ignites_fn) — not $slug/$lang, which would rebuild the whole map a
 * second time per render (/simplify 2026-07-17).
 */
function ignites_child_footnote_follow_cards( $cat ) {
	$cards = ignites_child_footnote_socials();

	$cards[] = array( 'name' => 'RSS', 'url' => $cat['feed'], 'handle' => '', 'icon' => 'rss-fill.svg', 'rel' => 'noopener' );

	if ( ! empty( $cat['apple'] ) ) {
		$cards[] = array( 'name' => 'Apple Podcasts', 'url' => $cat['apple'], 'handle' => '', 'icon' => 'applepodcasts.svg', 'rel' => 'noopener' );
	}

	return $cards;
}

/**
 * SECTION 3 — fediverse comments (RESERVED SLOT ONLY, v1.4.0).
 *
 * PINNED CONTRACT: the post's linked Mastodon status URL lives in the
 * IGNITES_FN_TOOT_META post meta ('ignites_fn_toot_url'). The later
 * fediverse-comments build (separate recon — reply fetch + the
 * "discuss on the fediverse" link) MUST read and write this SAME key.
 *
 * Today this renders NOTHING for every post (no live fetch, no JS): the
 * meta is never populated yet, and an empty meta short-circuits before any
 * output. The section's look is already specified by the shared .fn-section
 * family in assets/css/post-footnote.css, so the future content slots in
 * without a new visual system.
 */
const IGNITES_FN_TOOT_META = 'ignites_fn_toot_url';

function ignites_child_footnote_comments( $post_id ) {
	$toot = (string) get_post_meta( $post_id, IGNITES_FN_TOOT_META, true );
	if ( '' === $toot ) {
		return; // No linked toot — render nothing (currently: every post).
	}
	// Reserved: future build renders here as
	// <section class="fn-section fn-comments"><h3>…</h3>…</section>
	// using the same heading/spacing/card family as sections 1–2; resolve
	// the request language via ignites_child_footnote_lang() when copy lands.
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
	if ( ! in_array( $ip, IGNITES_FN_RL_EXEMPT_IPS, true )
		&& ( ! ignites_child_footnote_under_limit( 'ip_' . $ip, 5, HOUR_IN_SECONDS )
			|| ! ignites_child_footnote_under_limit( 'em_' . $email_key, 2, DAY_IN_SECONDS ) ) ) {
		// 429 carries its OWN copy so the anti-abuse brake stops masquerading
		// as a server failure (v1.3.1 P1b); exempt admin IPs skip both limits
		// (P1c — mirrors the CF edge skip). Turnstile still applies to all.
		return new WP_REST_Response( array( 'message' => $ui['ratelimited'] ), 429 );
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
