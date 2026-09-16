<?php
/**
 * Ignites Child — functions
 *
 * @package Ignites_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Per-category post footnote (RSS + Listmonk subscribe + social row) + the
// saites homepage exclusion — v1.3.0. Kept in its own file so this one's
// weekly-restorer marker block below stays untouched by footnote work.
require_once get_stylesheet_directory() . '/inc/post-footnote.php';

/**
 * Load child theme translations (closes ojars/ignites#4). The theme uses
 * the `ignites-child` text domain throughout — without this hook every
 * `__()`/`_e()`/`_n()` call returns the source string unchanged, no
 * matter what `.mo` files are placed in `languages/`.
 */
add_action( 'after_setup_theme', function () {
	load_child_theme_textdomain( 'ignites-child', get_stylesheet_directory() . '/languages' );
} );

/**
 * Enqueue parent + child stylesheets. Fonts are self-hosted via @font-face
 * declarations at the top of the child stylesheet — no third-party requests.
 */
function ignites_child_enqueue() {
	// Parent theme stylesheet (handle 'ignites-parent' lets us declare it as a child dep).
	wp_enqueue_style(
		'ignites-parent',
		get_template_directory_uri() . '/style.css',
		array(),
		wp_get_theme( get_template() )->get( 'Version' )
	);

	// Child stylesheet — depends on parent so cascade ordering is correct.
	// Version by file mtime (not the static theme Version) so the ?ver= query
	// busts browser + CDN caches on EVERY edit — a static ver kept serving
	// stale CSS after edits (the 2026-06-09 unstyled-switcher regression).
	// Serve the readable style.css, NOT style.min.css. The #34 csscompressor pass stripped the
	// required whitespace around +/- inside clamp() (e.g. `clamp(1.5rem,1.2rem+1.25vw,2.25rem)`),
	// which is INVALID CSS math → every `font-size: var(--text-*)` became invalid-at-computed-value
	// and inherited 16px (titles + menu shrank). Browser-proven: spaced clamp → 36px, no-space → 16px.
	// The minify saved ~0 bytes here (25,065 B vs the readable file), so it bought nothing. Re-enable
	// only with a clamp-safe minifier that preserves whitespace inside calc()/clamp(). (ignites#36)
	$child_min = get_stylesheet_directory() . '/style.min.css';
	$use_min   = false;
	$child_css = $use_min ? $child_min : get_stylesheet_directory() . '/style.css';
	wp_enqueue_style(
		'ignites-child',
		get_stylesheet_directory_uri() . ( $use_min ? '/style.min.css' : '/style.css' ),
		array( 'ignites-parent' ),
		(string) filemtime( $child_css ) // $child_css is always a present file (style.min.css or the required style.css)
	);
}
add_action( 'wp_enqueue_scripts', 'ignites_child_enqueue', 20 );

/* ============================================================================
 * Performance — critical-path trimming (ignites#30 + #31)
 * ----------------------------------------------------------------------------
 * Strategy (verified against this site, behind Cloudflare APO):
 *   1. Drop a duplicate render-blocking stylesheet request.
 *   2. Defer all jQuery + drop unused Bootstrap JS from the head.
 *   3. Stop misdirecting the browser's LCP hint onto post thumbnails.
 *   4. Inline the above-the-fold critical CSS and async-load the 33 KB Bootstrap
 *      stylesheet, so first paint isn't render-blocked by it.
 * Each step is its own named function (unhookable + unit-testable) hooked below.
 * ========================================================================== */

/**
 * Drop the duplicate child stylesheet request. The PARENT theme
 * (ignites-nightly/inc/ignites_styles_scripts.php) enqueues get_stylesheet_uri()
 * under handle 'ignites-style' — which resolves to the CHILD style.css, already
 * loaded as 'ignites-child' (mtime-versioned, parent-dependent). That made the
 * homepage request child/style.css twice (?ver=cp_… and ?ver=<mtime>), an extra
 * render-blocking round-trip flagged by Lighthouse. Dequeue the redundant copy at
 * a late priority (after the parent has registered it). Safe: nothing attaches
 * inline styles to 'ignites-style' and its only other reference (jetpack
 * content-options) is inert — Jetpack is not active.
 */
function ignites_child_dequeue_duplicate_style() {
	wp_dequeue_style( 'ignites-style' );
	wp_deregister_style( 'ignites-style' );
}
add_action( 'wp_enqueue_scripts', 'ignites_child_dequeue_duplicate_style', 99 );

/**
 * Defer scripts out of the render-blocking <head>:
 * - jQuery → footer. The parent enqueues 'jquery' in <head>, but its only consumer
 *   (ignites-main-js) is already footer-enqueued, and nothing in <head> uses jQuery
 *   (navigation.js / skip-link / the inline head scripts = 0 jQuery refs). Moving the
 *   meta handle + core + migrate to the footer group un-blocks ~1.3 s of head render.
 *   WP pulls jQuery back to <head> automatically IFF a plugin head-script declares it
 *   as a dependency, so the worst case is a no-op, never breakage.
 * - bootstrap-bundle JS is unused: no template uses Bootstrap data attributes
 *   (collapse, dropdown, modal, navbar-toggler — the menu toggle is navigation.js) and
 *   main.js never calls Bootstrap JS. Dequeue it (it was footer, so this is a pure
 *   byte/request saving, not a render-block fix).
 */
function ignites_child_defer_scripts() {
	wp_script_add_data( 'jquery', 'group', 1 );
	wp_script_add_data( 'jquery-core', 'group', 1 );
	wp_script_add_data( 'jquery-migrate', 'group', 1 );
	wp_dequeue_script( 'bootstrap-bundle' );
	wp_deregister_script( 'bootstrap-bundle' );
	// bootstrap.min.css (33 KB, 97% unused): the only classes the templates use — container,
	// row, col-lg-*, d-flex, justify-content-*, text-center, m-0, position-* — are all in the
	// inlined critical.css, so the file is fully redundant. Dequeue it entirely (ignites#32).
	wp_dequeue_style( 'bootstrap' );
	wp_deregister_style( 'bootstrap' );
}
add_action( 'wp_enqueue_scripts', 'ignites_child_defer_scripts', 99 );

/**
 * Drop jQuery (ignites#32). The parent's main.js was jQuery's only consumer (no plugin
 * or inline script uses it — verified). Swap it for a vanilla child main.js and dequeue
 * jQuery + jquery-migrate entirely (−31 KB). Behaviour is preserved: scroll-to-top (a
 * no-op anyway — the child hides .scroll-top with display:none!important), submenu
 * arrows, the mobile hamburger menu, and the widget .children class.
 */
function ignites_child_replace_main_js() {
	wp_dequeue_script( 'ignites-main-js' );
	wp_deregister_script( 'ignites-main-js' );
	wp_dequeue_script( 'jquery' );
	wp_dequeue_script( 'jquery-core' );
	wp_dequeue_script( 'jquery-migrate' );
	$path = get_stylesheet_directory() . '/assets/js/main.js';
	wp_enqueue_script(
		'ignites-child-main',
		get_stylesheet_directory_uri() . '/assets/js/main.js',
		array(),
		is_readable( $path ) ? filemtime( $path ) : null,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'ignites_child_replace_main_js', 100 );

/**
 * Drop WP core's auto fetchpriority=high from attachment images. Core tags the first
 * content image as its LCP guess, but our real LCP is the CSS masthead (header
 * ::before) — preloaded separately in the no-flash script. So the hint is misdirected
 * on below-the-fold, lazy-loaded images. Applies to every attachment image (the blog
 * has no content-image LCP); loading=lazy is preserved.
 *
 * @param array $attr Attachment <img> attributes.
 * @return array
 */
function ignites_child_strip_image_fetchpriority( $attr ) {
	unset( $attr['fetchpriority'] );
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'ignites_child_strip_image_fetchpriority', 20 );

/**
 * Inline the above-the-fold critical CSS (assets/css/critical.css: Bootstrap grid + the
 * theme's masthead/nav/typography/cards/@font-face/:root tokens, union light+dark) so the
 * full above-the-fold renders correctly from first paint while EVERY external stylesheet
 * — Bootstrap + the 4 theme sheets — loads asynchronously (see the async filter below).
 * Document stays ~16 KB br, inside Cloudflare's measured ~33 KB edge first flight, so the
 * critical render path is one self-contained response. is_readable() guards a fatal if the
 * file is ever missing.
 */
function ignites_child_inline_critical_css() {
	$crit = get_stylesheet_directory() . '/assets/css/critical.css';
	if ( ! is_readable( $crit ) ) {
		return;
	}
	$css = file_get_contents( $crit );
	// Inline CSS resolves url() against the DOCUMENT, not the source stylesheet, so the
	// sheets' relative asset paths must be made absolute or they 404 (ignites#32). Child
	// style.css used url("assets/…"); linearicons.css (parent) used url("../fonts/…").
	$child  = trailingslashit( get_stylesheet_directory_uri() );
	$parent = trailingslashit( get_template_directory_uri() );
	$css = str_replace(
		array( 'url("assets/', "url('assets/", 'url("../fonts/', "url('../fonts/" ),
		array( 'url("' . $child . 'assets/', "url('" . $child . 'assets/', 'url("' . $parent . 'assets/fonts/', "url('" . $parent . 'assets/fonts/' ),
		$css
	);
	echo "<style id=\"ignites-critical-css\">\n" . $css . "\n</style>\n";
}
// KEEP ENABLED (ignites#36): this block is LOAD-BEARING — since #34 dropped (dequeued)
// bootstrap.min.css, critical.css is the SOLE source of the Bootstrap grid + 5 of the 6 @font-face
// declarations. The FOUC + font-jump incident was caused by the ASYNC swap below (now disabled), NOT
// by this inline block. With the sheets render-blocking, first paint applies the full cascade
// (child body rule wins by source order → Inter / var(--text-base)), so no flash and no resize.
add_action( 'wp_head', 'ignites_child_inline_critical_css', 2 );

/**
 * Take the render-blocking stylesheets off the critical path (ignites#31 + #32). Each
 * listed <link> is rendered with media=print and swapped to media=all on load, with a
 * <noscript> fallback so JS-off clients still get it. First paint is driven entirely by
 * the inlined critical CSS above (Bootstrap grid + the theme's above-fold masthead/nav/
 * typography/cards/@font-face/:root tokens), so none of these need to block render.
 *
 * Handles async'd:
 *   - bootstrap        (33 KB, 97% unused — #31)
 *   - ignites-main-css, linearicons, ignites-parent, ignites-child (the 4 theme sheets
 *     responsible for the 1090 ms LCP render-delay measured in the PageSpeed report — #32)
 *
 * The media match tolerates single- OR double-quoted media="all" (WP/CP render style may
 * differ); if no recognisable media attribute is present the tag is returned unchanged
 * (still render-blocking, but never double-loaded).
 *
 * @param string $html   The <link> tag markup.
 * @param string $handle Registered style handle.
 * @return string
 */
function ignites_child_async_noncritical_css( $html, $handle ) {
	// Explicit allow-list (not deny-all) so a new critical sheet can't be async'd by mistake.
	// MAINTENANCE: when the theme enqueues a NEW non-critical stylesheet, add its handle here
	// (and cover its above-fold rules in critical.css) — else it stays render-blocking silently.
	$async_handles = array( 'ignites-main-css', 'linearicons', 'ignites-parent', 'ignites-child' );
	if ( ! in_array( $handle, $async_handles, true ) ) {
		return $html;
	}
	$async = preg_replace(
		'/ media=([\'"])all\1/',
		' media=${1}print${1} onload="this.media=\'all\'"',
		$html,
		1
	);
	// No recognisable media attribute → leave the tag render-blocking rather than
	// emit a render-blocking copy AND a duplicate <noscript> copy.
	if ( null === $async || $async === $html ) {
		return $html;
	}
	return $async . '<noscript>' . $html . "</noscript>\n";
}
// DISABLED 2026-06-15 (ignites#36, FOUC incident): see the note on ignites_child_inline_critical_css
// above. Async-loading the 4 theme sheets broke the parent→child cascade at first paint (FOUC + font
// jump). Sheets now load normally (render-blocking but correct). The other perf wins are untouched:
// Bootstrap CSS/JS dequeue, jQuery-drop + vanilla main.js, InterVariable subset, WebP, minify, CF cache.
// add_filter( 'style_loader_tag', 'ignites_child_async_noncritical_css', 10, 2 );

/**
 * Defer theme + jQuery scripts (ignites#32). ClassicPress 6.2.9 predates the WP 6.3
 * `strategy` enqueue arg, so the defer attribute is added via the loader-tag filter.
 * defer preserves execution order, so jquery-core → jquery-migrate → ignites-main-js
 * still run in dependency order. The inline no-flash/theme head script is unaffected
 * (inline, uses no jQuery), and comment-reply is left alone.
 *
 * @param string $tag    The <script> tag markup.
 * @param string $handle Registered script handle.
 * @return string
 */
function ignites_child_defer_script_tags( $tag, $handle ) {
	$defer = array( 'jquery-core', 'jquery-migrate', 'ignites-navigation', 'ignites-skip-link-focus-fix', 'ignites-main-js', 'ignites-child-main' );
	if ( in_array( $handle, $defer, true ) && false === strpos( $tag, ' defer' ) && false !== strpos( $tag, ' src=' ) ) {
		$tag = str_replace( ' src=', ' defer src=', $tag );
	}
	return $tag;
}
add_filter( 'script_loader_tag', 'ignites_child_defer_script_tags', 10, 2 );

/**
 * Serve WebP for uploaded images (ignites#32, no plugin — CF Polish is Pro-only). For
 * every generated size we write a .webp sibling (on upload + a one-shot backfill for
 * existing images) and wrap the rendered featured-image <img> in <picture> with a webp
 * <source> + the original as fallback. Uses GD imagewebp() (present on yuno).
 *
 * @param string $path Absolute image path.
 * @return void
 */
/**
 * Map a .png/.jpg/.jpeg path or URL to its .webp sibling (ignites#32). Shared by the
 * generator + the <picture> wrapper so the extension regex lives in one place.
 *
 * @param string $path Image path or URL.
 * @return string
 */
function ignites_child_webp_name( $path ) {
	return preg_replace( '/\.(png|jpe?g)$/i', '.webp', $path );
}

function ignites_child_make_webp_sibling( $path ) {
	if ( ! is_string( $path ) || ! is_readable( $path ) || ! function_exists( 'imagewebp' ) ) {
		return;
	}
	$webp = ignites_child_webp_name( $path );
	if ( $webp === $path || file_exists( $webp ) ) {
		return;
	}
	$ext = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
	$img = ( 'png' === $ext ) ? @imagecreatefrompng( $path ) : @imagecreatefromjpeg( $path );
	if ( ! $img ) {
		return;
	}
	if ( 'png' === $ext ) {
		imagepalettetotruecolor( $img );
		imagealphablending( $img, true );
		imagesavealpha( $img, true );
	}
	@imagewebp( $img, $webp, 82 );
	imagedestroy( $img );
}

add_filter( 'wp_generate_attachment_metadata', function ( $metadata, $attachment_id ) {
	$file = get_attached_file( $attachment_id );
	if ( $file ) {
		ignites_child_make_webp_sibling( $file );
		if ( ! empty( $metadata['sizes'] ) ) {
			$dir = trailingslashit( dirname( $file ) );
			foreach ( $metadata['sizes'] as $size ) {
				if ( ! empty( $size['file'] ) ) {
					ignites_child_make_webp_sibling( $dir . $size['file'] );
				}
			}
		}
	}
	return $metadata;
}, 10, 2 );

/**
 * Wrap a rendered attachment <img> in <picture> with a webp <source>, but only when the
 * .webp sibling actually exists on disk (so a failed conversion never serves a broken
 * source). Skips tags already inside a <picture>.
 *
 * @param string $html Image HTML.
 * @return string
 */
function ignites_child_wrap_img_webp( $html ) {
	if ( false === strpos( $html, '<img' ) || false !== strpos( $html, '<picture' ) ) {
		return $html;
	}
	$uploads = wp_upload_dir();
	return preg_replace_callback(
		'/<img\b[^>]*\bsrc=["\']([^"\']+\.(?:png|jpe?g))["\'][^>]*>/i',
		function ( $m ) use ( $uploads ) {
			$src       = $m[1];
			$webp_url  = ignites_child_webp_name( $src );
			$webp_path = ignites_child_webp_name( str_replace( $uploads['baseurl'], $uploads['basedir'], $src ) );
			if ( strpos( $src, $uploads['baseurl'] ) !== 0 || ! file_exists( $webp_path ) ) {
				return $m[0];
			}
			return '<picture><source srcset="' . esc_url( $webp_url ) . '" type="image/webp">' . $m[0] . '</picture>';
		},
		$html
	);
}
add_filter( 'post_thumbnail_html', 'ignites_child_wrap_img_webp', 20 );
add_filter( 'wp_get_attachment_image', 'ignites_child_wrap_img_webp', 20 );

/**
 * Estimate reading time in Latvian. Returns a localized string like "5 min lasīšana".
 *
 * @param int|WP_Post|null $post Post ID, object, or null for current.
 * @return string
 */
function ignites_child_reading_time( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	// Cache the integer minute count in post_meta, keyed PER LANGUAGE. The count is
	// computed over the ACTIVE-LANGUAGE segment only (see below), so a bilingual post
	// has a different value for LV vs EN. Cache is invalidated on save_post (see action
	// below). Formatting stays runtime so the i18n string honors current locale + plurals.
	$lang     = function_exists( 'qtranxf_getLanguage' ) ? qtranxf_getLanguage() : 'lv';
	$meta_key = '_ignites_child_reading_minutes_' . $lang;
	$minutes  = (int) get_post_meta( $post->ID, $meta_key, true );
	if ( $minutes <= 0 ) {
		// Count only the active language's words. post_content holds BOTH languages as
		// `[:lv]…[:en]…[:]`; counting the raw string ~doubled the time on bilingual posts
		// (review finding H1). qtranxf_use() extracts the current-language segment and
		// returns monolingual content unchanged, so LV-only / EN-only posts keep the same
		// count as before. The count method (preg_match_all) and 200 wpm divisor are
		// unchanged — only the input text (active-language segment) changes.
		$content = $post->post_content;
		if ( function_exists( 'qtranxf_use' ) ) {
			$content = qtranxf_use( $lang, $content, false );
		}
		$content = strip_shortcodes( $content );
		$content = wp_strip_all_tags( $content );
		preg_match_all( '/\p{L}[\p{L}\p{M}\p{Nd}\'-]*/u', $content, $matches );
		$words = count( $matches[0] );
		if ( $words <= 0 ) {
			return '';
		}
		$minutes = max( 1, (int) ceil( $words / 200 ) );
		update_post_meta( $post->ID, $meta_key, $minutes );
	}
	/* translators: %d: estimated reading time in minutes. The string is identical
	   for every minute count (no LV plural variation), so plain __() is correct —
	   _n() here only routed the string through the `ngettext` filter, which the
	   EN gettext map below never sees (the 2026-06-10 i18n-leak root cause). */
	return sprintf( __( '%d min lasīšana', 'ignites-child' ), $minutes );
}

/**
 * Single save_post invalidation handler: clears the cached reading-minutes
 * meta AND the EN-availability transients (nav categories + shared id cache)
 * so edits/translations take effect within one render. Skips revisions and
 * autosaves. (Merged from two separate closures — same guards, one hook.)
 */
add_action( 'save_post', function ( $post_id ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	// Per-language reading-minute caches + the legacy language-agnostic key (one-time
	// cleanup of values written before the active-language fix — review finding H1).
	delete_post_meta( $post_id, '_ignites_child_reading_minutes' );
	delete_post_meta( $post_id, '_ignites_child_reading_minutes_lv' );
	delete_post_meta( $post_id, '_ignites_child_reading_minutes_en' );
	delete_transient( 'ignites_child_en_avail_cats' );
	delete_transient( 'ignites_child_en_avail_ids' );
} );

/**
 * Latvian-formatted post date for the entry footer.
 */
function ignites_child_post_date( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	// "j. F Y" → e.g. "4. maijs 2026" when site locale is lv_LV.
	return get_the_date( 'j. F Y', $post );
}

/**
 * Inject dark-mode toggle markup just after <body>. Uses wp_body_open which
 * Ignites parent supports (ClassicPress 1.2+ / WP 5.2+).
 */
function ignites_child_render_theme_toggle() {
	?>
	<button type="button" data-theme-toggle aria-label="<?php esc_attr_e( 'Mainīt tēmu', 'ignites-child' ); ?>" hidden></button>
	<?php
}
// wp_footer (not wp_body_open) so the control renders on EVERY view — the parent
// theme only fires wp_body_open from index.php (home/blog-index), so singular pages
// and archives never got it. wp_footer fires on all templates. Priority 1 keeps the
// button in the DOM before the footer JS (priority 5) that wires it. position:fixed
// means footer-vs-body-open makes no visual difference (same bottom-right corner).
add_action( 'wp_footer', 'ignites_child_render_theme_toggle', 1 );

/**
 * Inline footer JS: dark-mode toggle (persistent via localStorage)
 * + reading-progress bar on single posts.
 */
function ignites_child_footer_inline_js() {
	?>
	<script id="ignites-child-runtime">
	(function () {
		var html = document.documentElement;
		var STORAGE_KEY = 'ignites-child-theme';
		var btn = document.querySelector('[data-theme-toggle]');

		// Resolve initial theme: stored > system preference.
		var stored = null;
		try { stored = localStorage.getItem(STORAGE_KEY); } catch (e) {}
		var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
		var theme = (stored === 'light' || stored === 'dark') ? stored : (prefersDark ? 'dark' : 'light');
		html.setAttribute('data-theme', theme);

		function sunIcon() {
			return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>';
		}
		function moonIcon() {
			return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
		}

		// Dynamic aria-label + aria-pressed reflect the actual current
		// theme so screen-reader users hear what pressing the toggle
		// will do, not just "Change theme". Closes ojars/ignites#7 W6.
		var LABEL_TO_DARK  = <?php echo wp_json_encode( __( 'Pārslēgt uz tumšo tēmu', 'ignites-child' ) ); ?>;
		var LABEL_TO_LIGHT = <?php echo wp_json_encode( __( 'Pārslēgt uz gaišo tēmu', 'ignites-child' ) ); ?>;
		function syncToggleA11y() {
			if (!btn) return;
			btn.setAttribute('aria-label', theme === 'dark' ? LABEL_TO_LIGHT : LABEL_TO_DARK);
			btn.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
		}

		if (btn) {
			btn.removeAttribute('hidden');
			btn.innerHTML = theme === 'dark' ? sunIcon() : moonIcon();
			syncToggleA11y();
			btn.addEventListener('click', function () {
				theme = (theme === 'dark') ? 'light' : 'dark';
				html.setAttribute('data-theme', theme);
				btn.innerHTML = theme === 'dark' ? sunIcon() : moonIcon();
				syncToggleA11y();
				try { localStorage.setItem(STORAGE_KEY, theme); } catch (e) {}
			});
		}

		// Reading progress bar — single posts only.
		if (document.body.classList.contains('single')) {
			var bar = document.createElement('div');
			bar.id = 'reading-progress';
			document.body.prepend(bar);
			var update = function () {
				var el = document.documentElement;
				var scrollTop = el.scrollTop || document.body.scrollTop;
				var scrollHeight = el.scrollHeight - el.clientHeight;
				bar.style.width = scrollHeight > 0 ? (scrollTop / scrollHeight * 100) + '%' : '0%';
			};
			window.addEventListener('scroll', update, { passive: true });
			window.addEventListener('resize', update);
			update();
		}
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'ignites_child_footer_inline_js', 5 );

/**
 * Shorten Latvian pagination labels: parent theme renders the WordPress
 * core "Next page" / "Previous page" strings, which Latvian translation
 * resolves to "Nākamā lapa" / "Iepriekšējā lapa". The "lapa" word is
 * redundant when the chevron already implies pagination — drop it.
 */
add_filter( 'gettext', function ( $translation, $text, $domain ) {
	$is_en = function_exists( 'qtranxf_getLanguage' ) && 'en' === qtranxf_getLanguage();

	// 1. WP-core paginate_links: drop redundant `lapa` from the chevron.
	// Gated on the `default` domain — the target strings are WP-core, and the
	// ungated strpos ran against every string of every domain on every request.
	if ( 'default' === $domain && false !== strpos( $translation, 'lapa' ) ) {
		$translation = str_replace(
			array( 'Nākamā lapa', 'Iepriekšējā lapa' ),
			array( 'Nākamā', 'Iepriekšējā' ),
			$translation
		);
	}

	// 2. Parent theme English strings → Latvian on the LV side. The
	// parent theme ships in English; the WP-core LV pack doesn't cover
	// these theme-specific strings, so we provide them. On the EN side
	// the original English source is the right answer — skip the swap.
	if ( ! $is_en && 'ignites' === $domain ) {
		$parent_translations = array(
			'Nothing Found'
				=> 'Nekas nav atrasts',
			'Sorry, but nothing matched your search terms. Please try again with some different keywords.'
				=> 'Diemžēl meklētajam neviens raksts neatbilst. Pamēģini ar citiem atslēgvārdiem.',
			'Search Results for: %s'
				=> 'Meklēšanas rezultāti: %s',
			'It seems we can&rsquo;t find what you&rsquo;re looking for. Perhaps searching can help.'
				=> 'Šķiet, šeit nekas neatbilst meklētajam. Iespējams, meklētājs palīdzēs.',
			'Skip to content'                                                                          => 'Pāriet uz saturu',
			'Search'                                                                                   => 'Meklēt',
			'Comment navigation'                                                                       => 'Komentāru navigācija',
			'Comments are closed.'                                                                     => 'Komentāri ir slēgti.',
			'Next post'                                                                                => 'Nākamais raksts',
			'Previous post'                                                                            => 'Iepriekšējais raksts',
			'Oops! That page can&rsquo;t be found.'                                                    => 'Ups! Šī lapa nav atrodama.',
			'It looks like nothing was found at this location. Maybe try one of the links below or a search?' => 'Šeit, šķiet, nekas nav atrasts. Pamēģini kādu no zemākajām saitēm vai meklētāju.',
		);
		if ( isset( $parent_translations[ $text ] ) ) {
			return $parent_translations[ $text ];
		}
	}

	// 2b. The handful of CHILD theme strings whose source text is English
	// (WP-core-style strings reused in our templates) → Latvian on the LV
	// side. Without this they leak English on / (e.g. "Pages:" on paginated
	// posts). Mirror image of the LV→EN map below.
	if ( ! $is_en && 'ignites-child' === $domain ) {
		$child_lv = array(
			'Pages:' => 'Lapas:',
			'Continue reading<span class="screen-reader-text"> "%s"</span>'
				=> 'Turpināt lasīt<span class="screen-reader-text"> "%s"</span>',
		);
		if ( isset( $child_lv[ $text ] ) ) {
			return $child_lv[ $text ];
		}
	}

	// 3. Child theme Latvian source strings → English on the EN side.
	// The strings below live in `__()` calls in Latvian (this theme's
	// source language is LV) and would render Latvian on /en/ without
	// this lookup. Inline map avoids a .mo file + msgfmt toolchain;
	// see ojars/ignites#10 for the wider qTranslate-XT integration.
	if ( $is_en && 'ignites-child' === $domain ) {
		$child_en = array(
			'Mainīt tēmu'                                                                      => 'Change theme',
			'Pārslēgt uz tumšo tēmu'                                                           => 'Switch to dark theme',
			'Pārslēgt uz gaišo tēmu'                                                           => 'Switch to light theme',
			'%d min lasīšana'                                                                  => '%d min read',
			'Meklēt'                                                                           => 'Search',
			'Meklēt…'                                                                          => 'Search…',
			'Raksta navigācija'                                                                => 'Post navigation',
			'Sociālie tīkli'                                                                   => 'Social networks',
			'404 — lapa nav atrasta'                                                           => '404 — page not found',
			'Iepriekšējais'                                                                    => 'Previous',
			'Nākamais'                                                                         => 'Next',
			'Šeit nekā nav. Pamēģini sākumlapu vai izmanto meklētāju zemāk.'                   => 'Nothing here. Try the homepage or use the search below.',
		);
		if ( isset( $child_en[ $text ] ) ) {
			return $child_en[ $text ];
		}
	}

	return $translation;
}, 10, 3 );

/**
 * Translate the displayed CATEGORY name on the EN side (the "SAITES" label above
 * post titles, rendered by get_the_category_list()). qTranslate term names are
 * NOT bracketed in wp_terms.name here, because the term-display path doesn't parse
 * `[:lv]…[:en]…[:]` brackets (they leak literally — infra-docs#258e); so the name
 * is translated in-theme instead, keyed by slug. CSS uppercases the label, so
 * natural case is fine. Falls back to the stored (LV) name for unmapped slugs.
 */
add_filter( 'get_the_categories', function ( $categories ) {
	if ( ! is_array( $categories ) || ! function_exists( 'qtranxf_getLanguage' ) || 'en' !== qtranxf_getLanguage() ) {
		return $categories;
	}
	$en = array(
		'saites'   => 'Links',
		'teksti'   => 'Blog',
		'podkasts' => 'Podcast',
		'bildes'   => 'Photos',
	);
	foreach ( $categories as $cat ) {
		if ( isset( $cat->slug, $en[ $cat->slug ] ) ) {
			$cat->name = $en[ $cat->slug ];
		}
	}
	return $categories;
}, 10, 1 );

/**
 * Mirror WP locale to qTranslate-XT's current language. Without this,
 * WP-core strings (Previous, Next, Skip to content), date formatting
 * (`j. F Y` → "februāris" vs "February"), and comment / form labels
 * stay fixed at the install locale regardless of which language URL
 * the user is on. Wired BEFORE the gettext filter so .mo loading sees
 * the right locale.
 */
add_filter( 'locale', function ( $locale ) {
	if ( function_exists( 'qtranxf_getLanguage' ) ) {
		$lang = qtranxf_getLanguage();
		if ( 'en' === $lang ) {
			return 'en_US';
		}
		if ( 'lv' === $lang ) {
			return 'lv';
		}
	}
	return $locale;
}, 100, 1 ); // priority 100 to override qtranslate-xt's qtranxf_localeForCurrentLanguage at 99

/*
 * NOTE: qTranslate-XT 3.16.1 Slugs-module / → /lv/ redirect-loop fix lives
 * in wp-content/mu-plugins/qtranslate-loop-fix.php — it MUST be loaded
 * before qtranslate-xt's plugins_loaded:2 hook fires, which is too early
 * for theme functions.php (themes load after plugins_loaded action).
 */

/**
 * WP boots `load_default_textdomain()` BEFORE the theme + sometimes
 * before qTranslate-XT settles the language for the request — the
 * default domain ends up loaded under en_US even though the URL is /
 * (LV). Forcing a reload at `after_setup_theme` re-runs the loader
 * with the now-correct locale so WP-core strings ("Next &raquo;",
 * "&laquo; Previous", "Skip to content" via parent theme, date i18n)
 * pick up lv.mo on the LV side and stay en_US on /en/.
 */
add_action( 'after_setup_theme', function () {
	if ( ! function_exists( 'qtranxf_getLanguage' ) ) {
		return;
	}
	unload_textdomain( 'default' );
	load_default_textdomain();
}, 99 );

/**
 * Floating language switcher — a circular control stacked directly ABOVE the
 * dark/light toggle, in the same bottom-right floating area, on EVERY view
 * (wp_footer). Replaces the former primary-menu language item (which appended
 * a `<li class="menu-language-switcher">`): same proven URL logic, new theme-
 * native presentation. Shows the TARGET language code — EN on Latvian pages,
 * LV on English — monochrome + typographic to mirror the sun/moon toggle.
 * CSS positions it: see [data-lang-switch] in style.css.
 */
function ignites_child_render_lang_switch() {
	if ( ! function_exists( 'qtranxf_getLanguage' ) || ! function_exists( 'qtranxf_convertURL' ) ) {
		return;
	}
	$current = qtranxf_getLanguage();
	$other   = ( 'lv' === $current ) ? 'en' : 'lv';
	// Empty URL → qtranxf_convertURL uses the current request context and runs the
	// qtranslate_convert_url (Slugs) filter for correct slug translation; passing an
	// explicit URL bypasses the slug lookup and returns /en/tema/podkasts/ instead of
	// /en/tema/podcast/ (#226). This is the URL form the old menu switcher used and
	// that the operator confirmed switched language correctly.
	$target = qtranxf_convertURL( '', $other, false, false );
	// qtranxf strips the trailing slash from path-mode roots; our permalink structure
	// forces trailing slashes, so normalise once here to avoid a canonical 301 per click.
	$parsed = wp_parse_url( $target );
	if ( $parsed ) {
		$path   = ! empty( $parsed['path'] ) ? trailingslashit( $parsed['path'] ) : '/';
		$target = ( isset( $parsed['scheme'] ) ? $parsed['scheme'] . '://' : '' )
		        . ( isset( $parsed['host'] ) ? $parsed['host'] : '' )
		        . $path
		        . ( isset( $parsed['query'] ) ? '?' . $parsed['query'] : '' )
		        . ( isset( $parsed['fragment'] ) ? '#' . $parsed['fragment'] : '' );
	}
	// aria-label authored in the CURRENT page language (offer the other language).
	$label = ( 'en' === $other )
		? __( 'Pārslēgt uz angļu valodu', 'ignites-child' )
		: __( 'Switch to Latvian', 'ignites-child' );
	// rel="noreferrer": navigating to the slug-free default-language root "/" otherwise
	// leaks a cross-language Referer that qTranslate's Slugs module uses to keep the
	// prior language (infra-docs#257) — suppressing it lets "/" resolve to default LV.
	// Wrapped in a <nav> landmark so the control isn't loose page content
	// (WCAG 1.3.1 "region" / axe — the switcher renders via wp_footer, outside
	// the parent's header/main/footer). The <a> keeps its own fixed positioning
	// ([data-lang-switch] in style.css), so the zero-box <nav> adds no layout.
	$nav_label = ( 'en' === $current )
		? __( 'Valodas izvēle', 'ignites-child' )  // page is LV → label in LV
		: __( 'Language', 'ignites-child' );        // page is EN → label in EN
	printf(
		'<nav class="lang-switch-nav" aria-label="%s"><a data-lang-switch href="%s" hreflang="%s" rel="noreferrer" aria-label="%s"><span aria-hidden="true">%s</span></a></nav>',
		esc_attr( $nav_label ),
		esc_url( $target ),
		esc_attr( $other ),
		esc_attr( $label ),
		esc_html( strtoupper( $other ) )
	);
}
add_action( 'wp_footer', 'ignites_child_render_lang_switch', 1 );

/*
 * Fonts are deliberately NOT preloaded (removed 2026-06-14, ignites#30).
 * Every @font-face uses font-display:swap, so a preload gave ~0 render benefit
 * (fallback text shows immediately, the web font swaps in when ready) but made
 * the 344 KB InterVariable.woff2 win the bandwidth-constrained mobile pipe AHEAD
 * of the real LCP element — the masthead background image — pushing LCP to ~6 s.
 * The LCP image is preloaded instead, in the no-flash script above (themed).
 */

/**
 * Theme-bundled favicon + Apple touch icon. Emits in <head> at default
 * priority so it appears alongside other meta tags.
 */
function ignites_child_favicon() {
	$base = get_stylesheet_directory_uri() . '/assets/images';
	// /favicon.ico at the SITE ROOT — browsers request it unconditionally, and it
	// was a 404 (ignites#49 F8). A physical multi-size .ico (16/32/48) is deployed
	// to the webroot; sizes="any" so it serves the legacy .ico slot.
	echo '<link rel="icon" href="/favicon.ico" sizes="any">' . "\n";
	echo '<link rel="icon" type="image/png" sizes="32x32" href="' . esc_url( $base . '/favicon-32.png' ) . '">' . "\n";
	echo '<link rel="icon" type="image/png" sizes="192x192" href="' . esc_url( $base . '/icon-192.png' ) . '">' . "\n";
	echo '<link rel="apple-touch-icon" sizes="180x180" href="' . esc_url( $base . '/apple-touch-icon.png' ) . '">' . "\n";
	// Web app manifest (F8) — icons (192 any + 512 any/maskable) + theme/bg colour.
	echo '<link rel="manifest" href="' . esc_url( get_stylesheet_directory_uri() . '/manifest.webmanifest' ) . '">' . "\n";
}
add_action( 'wp_head', 'ignites_child_favicon', 5 );

/**
 * Mastodon author attribution (fediverse:creator, Mastodon 4.3+). When a link
 * to this blog is shared anywhere in the fediverse, the preview card carries a
 * "More from @ojars@kapteinis.lv" byline. Static tag — single-author blog, so
 * no per-post logic. Deliberately independent of the ActivityPub plugin, which
 * stays deactivated (qtranslate-xt URL-filter recursion → OOM, infra-docs#225/
 * #226, reactivation tracked in infra-docs#357). The handle is public by
 * definition, so hardcoding it is fine. The other half of the handshake lives
 * in Mastodon: Preferences → Public profile → Verification → "Websites allowed
 * to credit you" must list ojars.kapteinis.lv, else the tag is ignored.
 */
function ignites_child_fediverse_creator() {
	echo '<meta name="fediverse:creator" content="@ojars@kapteinis.lv">' . "\n";
}
add_action( 'wp_head', 'ignites_child_fediverse_creator', 5 );

/**
 * Front-page meta description (SEO). No SEO plugin is installed, so this is the
 * single homepage <meta name="description">. Bilingual: the string carries both
 * languages and qTranslate-XT extracts the active one via qtranxf_use() +
 * qtranxf_getLanguage() (the old qtranxf_isAvailableIn is gone in 3.16.x — see
 * INFRA_REF §10). Front page only; per-post descriptions are a separate follow-up.
 * Fixes the PageSpeed/Lighthouse "Document does not have a meta description" (SEO 91).
 */
function ignites_child_meta_description() {
	if ( ! is_front_page() && ! is_home() ) {
		return;
	}
	$desc = '[:lv]Ojāra Kapteiņa blogs par self-hosting, decentralizēto tīmekli un mākslīgo intelektu, politiku un reliģiju, kā arī ikdienas saišu apkopojumi. 🇪🇺 Europe, Rīga.[:en]Ojārs Kapteinis\'s blog about self-hosting, the decentralized web and AI, politics and religion, plus daily link digests. 🇪🇺 Europe, Rīga.[:]';
	if ( function_exists( 'qtranxf_use' ) && function_exists( 'qtranxf_getLanguage' ) ) {
		$desc = qtranxf_use( qtranxf_getLanguage(), $desc, false );
	}
	echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
}
add_action( 'wp_head', 'ignites_child_meta_description', 3 );

/**
 * Active request language as a 2-letter code ('lv'|'en'), qTranslate-XT aware,
 * defaulting to 'lv' (the site default) when qTranslate is inactive.
 */
function ignites_child_lang() {
	if ( function_exists( 'qtranxf_getLanguage' ) ) {
		$l = qtranxf_getLanguage();
		if ( 'en' === $l ) {
			return 'en';
		}
	}
	return 'lv';
}

/**
 * Canonical URL for the current view (ignites#49 F6). ClassicPress/qTranslate
 * emit rel=canonical on single posts/pages already, but NOT on the front page —
 * so this fills that gap (front page only), keeping the LV canonical at "/" and
 * the EN canonical at "/en/". Other views keep the core/qTranslate canonical.
 */
function ignites_child_canonical() {
	if ( ! is_front_page() && ! is_home() ) {
		return;
	}
	$url = ( 'en' === ignites_child_lang() ) ? home_url( '/en/' ) : home_url( '/' );
	echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
}
add_action( 'wp_head', 'ignites_child_canonical', 3 );

/**
 * color-scheme + theme-color (ignites#49 F9). The theme ships light + dark
 * surfaces (@media prefers-color-scheme in style.css); advertise both so the
 * browser chrome / address bar matches the rendered palette. Colours mirror
 * --color-bg in style.css (light #f7f5f1 / dark #171614).
 */
function ignites_child_color_scheme_meta() {
	echo '<meta name="color-scheme" content="light dark">' . "\n";
	echo '<meta name="theme-color" media="(prefers-color-scheme: light)" content="#f7f5f1">' . "\n";
	echo '<meta name="theme-color" media="(prefers-color-scheme: dark)" content="#171614">' . "\n";
}
add_action( 'wp_head', 'ignites_child_color_scheme_meta', 4 );

/**
 * Open Graph + Twitter Card tags, LOCALISED per request language (ignites#49
 * F10, closes ignites#28 — the site previously emitted zero OG/twitter tags, so
 * every fediverse/Slack/iMessage/Twitter share degraded to a title-only or bare
 * card). Front page → og:type=website with the bilingual site description;
 * single post → og:type=article with the post title + excerpt + feature image
 * (falling back to the shared 1200×630 og-card). og:locale is lv_LV / en_US.
 * The fediverse:creator byline (emitted separately) composes with these.
 */
function ignites_child_opengraph() {
	if ( is_admin() || is_feed() ) {
		return;
	}
	$lang    = ignites_child_lang();
	$locale  = ( 'en' === $lang ) ? 'en_US' : 'lv_LV';
	$site    = 'Ojārs Kapteinis';
	$img_dir = get_stylesheet_directory_uri() . '/assets/images';
	$og_card = $img_dir . '/og-card.jpg';

	if ( is_singular( 'post' ) ) {
		$type  = 'article';
		$id    = get_the_ID();
		$title = wp_strip_all_tags( get_the_title( $id ) );
		$url   = ( 'en' === $lang ) ? ignites_child_canonical_en_url( $id ) : get_permalink( $id );
		if ( ! $url ) {
			$url = get_permalink( $id );
		}
		$excerpt = wp_strip_all_tags( get_the_excerpt( $id ) );
		$desc    = ( '' !== $excerpt ) ? $excerpt : $title;
		$thumb   = get_the_post_thumbnail_url( $id, 'large' );
		$image   = $thumb ? $thumb : $og_card;
		$img_w   = $thumb ? '' : '1200';
		$img_h   = $thumb ? '' : '630';
	} else {
		$type  = 'website';
		$title = $site;
		$url   = ( 'en' === $lang ) ? home_url( '/en/' ) : home_url( '/' );
		$bi    = '[:lv]Ojāra Kapteiņa blogs par self-hosting, decentralizēto tīmekli un mākslīgo intelektu, politiku un reliģiju, kā arī ikdienas saišu apkopojumi.[:en]Ojārs Kapteinis\'s blog about self-hosting, the decentralized web and AI, politics and religion, plus daily link digests.[:]';
		$desc  = ( function_exists( 'qtranxf_use' ) ) ? qtranxf_use( $lang, $bi, false ) : $bi;
		$image = $og_card;
		$img_w = '1200';
		$img_h = '630';
	}

	$tags = array(
		'og:type'        => $type,
		'og:site_name'   => $site,
		'og:locale'      => $locale,
		'og:title'       => $title,
		'og:description' => $desc,
		'og:url'         => $url,
		'og:image'       => $image,
	);
	foreach ( $tags as $prop => $val ) {
		if ( '' === (string) $val ) {
			continue;
		}
		echo '<meta property="' . esc_attr( $prop ) . '" content="' . esc_attr( $val ) . '">' . "\n";
	}
	if ( '' !== $img_w ) {
		echo '<meta property="og:image:width" content="' . esc_attr( $img_w ) . '">' . "\n";
		echo '<meta property="og:image:height" content="' . esc_attr( $img_h ) . '">' . "\n";
	}
	// Twitter Card (mirrors OG; summary_large_image for the 1.91:1 card).
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '">' . "\n";
	echo '<meta name="twitter:image" content="' . esc_url( $image ) . '">' . "\n";
}
add_action( 'wp_head', 'ignites_child_opengraph', 6 );

/**
 * JSON-LD structured data (ignites#49 F7). Front page → WebSite + Person (with
 * sameAs federated/social profiles); single post → BlogPosting. No SEO plugin is
 * installed, so this is the site's only schema.org output. inLanguage tracks the
 * active qTranslate language.
 */
function ignites_child_jsonld() {
	if ( is_admin() || is_feed() ) {
		return;
	}
	$lang    = ignites_child_lang();
	$bcp     = ( 'en' === $lang ) ? 'en-US' : 'lv-LV';
	$site    = 'Ojārs Kapteinis';
	$home    = home_url( '/' );
	$img_dir = get_stylesheet_directory_uri() . '/assets/images';
	$person  = array(
		'@type'  => 'Person',
		'name'   => $site,
		'url'    => $home,
		'sameAs' => array(
			'https://kapteinis.lv/@ojars',
			'https://pixel.kapteinis.lv/ojars',
			'https://book.kapteinis.lv/user/ojars',
			'https://git.kapteinis.lv/ojars',
		),
	);

	if ( is_singular( 'post' ) ) {
		$id    = get_the_ID();
		$url   = ( 'en' === $lang ) ? ignites_child_canonical_en_url( $id ) : get_permalink( $id );
		if ( ! $url ) {
			$url = get_permalink( $id );
		}
		$thumb = get_the_post_thumbnail_url( $id, 'large' );
		$data  = array(
			'@context'         => 'https://schema.org',
			'@type'            => 'BlogPosting',
			'headline'         => wp_strip_all_tags( get_the_title( $id ) ),
			'mainEntityOfPage' => $url,
			'url'              => $url,
			'datePublished'    => get_the_date( DATE_W3C, $id ),
			'dateModified'     => get_the_modified_date( DATE_W3C, $id ),
			'inLanguage'       => $bcp,
			'author'           => $person,
			'publisher'        => $person,
		);
		if ( $thumb ) {
			$data['image'] = $thumb;
		} else {
			$data['image'] = $img_dir . '/og-card.jpg';
		}
	} else {
		$data = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'WebSite',
			'name'        => $site,
			'url'         => ( 'en' === $lang ) ? home_url( '/en/' ) : $home,
			'inLanguage'  => $bcp,
			'author'      => $person,
			'publisher'   => $person,
		);
	}
	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}
add_action( 'wp_head', 'ignites_child_jsonld', 7 );

/**
 * CSP violation-report collector (ignites#49 Batch D / F5). The nginx snippet
 * `nginx-ojars-security-headers.conf` serves a Content-Security-Policy-Report-Only
 * header (non-enforcing) whose report-uri/report-to points here. This is the
 * OBSERVE half: it captures violations so the report-only policy can be tuned and
 * then promoted to enforcing once the log is clean. Endpoint is intentionally
 * public (browsers POST reports unauthenticated) but hardened: POST-only,
 * content-type-gated to the two report MIME types, body capped, and the log line
 * is a single compact record written via error_log() (NOT a web-served path —
 * reports are never downloadable). The wordpress FPM pool has no explicit
 * error_log, so type-0 error_log() surfaces via FastCGI stderr in the nginx vhost
 * error log — OBSERVE with:
 *   sudo grep CSP-REPORT /var/log/nginx/ojars.kapteinis.lv-error.log
 * CSP reports carry only the violated URI + directive, no user data. Remove this
 * route + the report-only header once the policy is promoted to enforcing.
 */
add_action( 'rest_api_init', function () {
	register_rest_route(
		'ignites/v1',
		'/csp-report',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => 'ignites_child_csp_report',
		)
	);
} );

function ignites_child_csp_report( $request ) {
	$ct = strtolower( (string) $request->get_header( 'content_type' ) );
	// Browsers send application/csp-report (report-uri) or application/reports+json (report-to).
	if ( false === strpos( $ct, 'csp-report' ) && false === strpos( $ct, 'reports+json' ) ) {
		return new WP_REST_Response( null, 415 );
	}
	$body = $request->get_body();
	if ( is_string( $body ) && '' !== $body ) {
		$body = substr( $body, 0, 4096 ); // cap — reports are small; defend against a flood
		$line = str_replace( array( "\n", "\r" ), ' ', $body ); // keep it one grep-able line
		// type 0 → PHP-FPM error log (not web-accessible). Grep: `grep CSP-REPORT`.
		error_log( 'CSP-REPORT ' . $line );
	}
	// 204: acknowledge without a body; browsers ignore the response anyway.
	return new WP_REST_Response( null, 204 );
}

/**
 * Cloudflare Web Analytics beacon — privacy-first, COOKIELESS reader counter.
 * Sets no cookies / no localStorage / no cross-site identifier, so it needs no
 * cookie-consent banner. Injected MANUALLY (deferred, in wp_footer) because
 * Cloudflare's zone-level "automatic" edge-injection was verified NOT firing on
 * this hostname (2026-06-10) — the manual beacon is the reliable method. Bound to
 * a DEDICATED RUM site (host=ojars.kapteinis.lv) so the blog's numbers stay
 * separate from the kapteinis.lv apex zone site.
 *
 * The beacon token is NOT hardcoded — it is read from the `ignites_cf_beacon_token`
 * WP option (set once via wp-cli; rotate with one `wp option update`, no code
 * deploy, never in git history). It is a public client-side property id (the
 * analogue of a GA G-XXXX tag) and necessarily appears in the rendered HTML, but
 * keeping it out of source means a vendor-semantics change (cf. the 2026 Google
 * Maps-key→Gemini-auth drift) is a config rotation, not a code+git-history fix.
 * If the option is unset the beacon simply doesn't render. The host guard keeps a
 * stray theme copy/preview from polluting the dedicated site.
 * Dashboard: Cloudflare → Web Analytics → ojars.kapteinis.lv. See INFRA_REF §10.
 */
function ignites_child_cf_web_analytics() {
	$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( strtolower( wp_unslash( $_SERVER['HTTP_HOST'] ) ) ) : '';
	if ( 'ojars.kapteinis.lv' !== $host ) {
		return;
	}
	$token = get_option( 'ignites_cf_beacon_token' );
	if ( ! $token ) {
		return;
	}
	$beacon = wp_json_encode( array( 'token' => $token ) );
	echo "<!-- Cloudflare Web Analytics -->\n";
	echo '<script defer src="https://static.cloudflareinsights.com/beacon.min.js" data-cf-beacon="' . esc_attr( $beacon ) . '"></script>' . "\n";
	echo "<!-- End Cloudflare Web Analytics -->\n";
}
add_action( 'wp_footer', 'ignites_child_cf_web_analytics', 20 );

/**
 * Default <html data-theme> based on system preference, applied before paint
 * to avoid the light→dark flash. Printed in <head>.
 */
function ignites_child_no_flash_script() {
	?>
	<script>
	(function () {
		try {
			var stored = localStorage.getItem('ignites-child-theme');
			var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
			var theme = (stored === 'light' || stored === 'dark') ? stored : (prefersDark ? 'dark' : 'light');
			document.documentElement.setAttribute('data-theme', theme);
			// Preload the LCP masthead for the RESOLVED theme. It's a CSS ::before
			// background (otherwise undiscoverable until the stylesheet parses), so we
			// prioritise it here instead of preloading the swap-fonts (ignites#30).
			var base = '<?php echo esc_url( get_stylesheet_directory_uri() ); ?>';
			var l = document.createElement('link');
			l.rel = 'preload'; l.as = 'image';
			l.href = base + '/assets/images/header-' + theme + '.webp';
			l.setAttribute('fetchpriority', 'high');
			document.head.appendChild(l);
		} catch (e) {}
	})();
	</script>
	<?php
}
add_action( 'wp_head', 'ignites_child_no_flash_script', 1 );

/**
 * Register Customizer settings for social profile URLs and handles.
 */
function ignites_child_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'ignites_child_social',
		array(
			'title'    => __( 'Social profiles', 'ignites-child' ),
			'priority' => 80,
		)
	);

	$networks = array(
		'mastodon' => 'Mastodon',
		'pixelfed' => 'PixelFed',
		'bookwyrm' => 'BookWyrm',
		'forgejo'  => 'Forgejo',
	);
	foreach ( $networks as $key => $label ) {
		$wp_customize->add_setting(
			'ignites_child_' . $key . '_url',
			array(
				'default'           => '',
				'sanitize_callback' => 'esc_url_raw',
			)
		);
		$wp_customize->add_control(
			'ignites_child_' . $key . '_url',
			array(
				/* translators: %s: name of the social network (Mastodon, PixelFed, etc.) */
				'label'   => sprintf( __( '%s URL', 'ignites-child' ), $label ),
				'section' => 'ignites_child_social',
				'type'    => 'url',
			)
		);

		$wp_customize->add_setting(
			'ignites_child_' . $key . '_handle',
			array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			'ignites_child_' . $key . '_handle',
			array(
				/* translators: %s: name of the social network */
				'label'   => sprintf( __( '%s handle (e.g. @ojars@social.example)', 'ignites-child' ), $label ),
				'section' => 'ignites_child_social',
				'type'    => 'text',
			)
		);
	}
}
add_action( 'customize_register', 'ignites_child_customize_register' );

/**
 * IDs of published items of $post_type that have an EN translation, per
 * qtranxf_getAvailableLanguages(). THE single shared EN-availability scan —
 * the nav category filter and the EN sitemap provider both build on it (they
 * previously each ran their own near-identical all-posts loop; the sitemap's
 * was uncached, so every bot hit of wp-sitemap-en-1.xml rescanned everything).
 * Cached per post_type in one 5-minute transient; invalidated by the merged
 * save_post handler above. Returns [] when qTranslate is inactive.
 */
function ignites_child_en_available_ids( $post_type ) {
	$cache = get_transient( 'ignites_child_en_avail_ids' );
	if ( ! is_array( $cache ) ) {
		$cache = array();
	}
	if ( isset( $cache[ $post_type ] ) ) {
		return (array) $cache[ $post_type ];
	}
	$en_ids = array();
	if ( function_exists( 'qtranxf_getAvailableLanguages' ) ) {
		$ids = get_posts( array(
			'post_type'        => $post_type,
			'post_status'      => 'publish',
			'numberposts'      => -1,
			'fields'           => 'ids',
			'suppress_filters' => true,
		) );
		foreach ( $ids as $id ) {
			$available = qtranxf_getAvailableLanguages( get_post_field( 'post_content', $id ) );
			if ( in_array( 'en', (array) $available, true ) ) {
				$en_ids[] = (int) $id;
			}
		}
	}
	$cache[ $post_type ] = $en_ids;
	set_transient( 'ignites_child_en_avail_ids', $cache, 5 * MINUTE_IN_SECONDS );
	return $en_ids;
}

/**
 * Set of category term_ids that have at least one EN-available post. Sibling of
 * the hide-untranslated archive filter (infra-docs#226): derives from the shared
 * ignites_child_en_available_ids() scan, cached at CATEGORY granularity in its
 * own 5-minute transient so the nav (rendered on every page) never re-derives.
 * Returns [] (→ all EN category items hidden) if qTranslate is inactive — the
 * EN filter below only runs when qtranxf says we're on /en/.
 */
function ignites_child_en_available_categories() {
	$cats = get_transient( 'ignites_child_en_avail_cats' );
	if ( false !== $cats ) {
		return (array) $cats;
	}
	$cats = array();
	foreach ( ignites_child_en_available_ids( 'post' ) as $id ) {
		foreach ( wp_get_post_categories( $id ) as $cat_id ) {
			$cats[ $cat_id ] = true; // dedupe on key
		}
	}
	$cats = array_map( 'intval', array_keys( $cats ) );
	set_transient( 'ignites_child_en_avail_cats', $cats, 5 * MINUTE_IN_SECONDS );
	return $cats;
}

/**
 * Hide primary-menu CATEGORY items that have no posts in the CURRENT language.
 * On the EN side, a category whose posts are all LV-only links to an empty
 * /en/category/<slug>/ page, so its nav item is dropped. LV (the source
 * language) always shows every item. Home / About-me (non-category) untouched.
 * wp_nav_menu_objects is a render-time filter → theme-side-safe (infra-docs#257).
 */
add_filter( 'wp_nav_menu_objects', function ( $items, $args ) {
	if ( ! function_exists( 'qtranxf_getLanguage' ) || 'en' !== qtranxf_getLanguage() ) {
		return $items; // LV / unknown: show everything
	}
	if ( ! is_array( $items ) ) {
		return $items;
	}
	$en_cats = ignites_child_en_available_categories();
	$dropped = array();
	foreach ( $items as $key => $item ) {
		if ( isset( $item->type, $item->object ) && 'taxonomy' === $item->type && 'category' === $item->object
			&& ! in_array( (int) $item->object_id, $en_cats, true ) ) {
			$dropped[] = (int) $item->ID;
			unset( $items[ $key ] );
		}
	}
	if ( $dropped ) { // drop children of a removed category item too
		foreach ( $items as $key => $item ) {
			if ( isset( $item->menu_item_parent ) && in_array( (int) $item->menu_item_parent, $dropped, true ) ) {
				unset( $items[ $key ] );
			}
		}
	}
	return $items;
}, 10, 2 );

/**
 * Canonical English URL for a post/page. The qTranslate Slugs module stores the
 * translated slug in post_meta `qtranslate_slug_en` (e.g. par-mani → about-me);
 * when present we swap the last path segment, otherwise the LV slug is reused
 * (e.g. the saites/links posts keep their slug). Always under the /en/ prefix.
 */
function ignites_child_canonical_en_url( $id ) {
	$lv    = get_permalink( $id );
	$parts = wp_parse_url( $lv );
	if ( empty( $parts['host'] ) ) {
		return '';
	}
	$path    = isset( $parts['path'] ) ? $parts['path'] : '/';
	$en_slug = get_post_meta( $id, 'qtranslate_slug_en', true );
	if ( ! empty( $en_slug ) ) {
		// Callback, NOT a replacement string: `$`/`\` in the meta value would be
		// parsed as backreferences by preg_replace's replacement parser.
		// (preg_quote() is wrong here — it escapes PATTERN metacharacters and
		// would render literally in a replacement.)
		$path = preg_replace_callback(
			'#/[^/]+/?$#',
			function () use ( $en_slug ) {
				return '/' . $en_slug . '/';
			},
			$path
		);
	}
	$scheme = isset( $parts['scheme'] ) ? $parts['scheme'] : 'https';
	return $scheme . '://' . $parts['host'] . '/en' . $path;
}

/**
 * Dedicated sitemap provider for the English (/en/) URLs of genuinely EN-available
 * posts + pages, so search engines discover the translated content. Only content
 * that actually has an EN translation (qtranxf_getAvailableLanguages) is listed —
 * LV-only posts are NOT (their /en/ would be a duplicate LV fallback). Surfaces as
 * a `wp-sitemap-en-1.xml` sub-sitemap in the index.
 *
 * Mechanism note: ClassicPress's core sitemap providers expose NO append filter
 * (`wp_sitemaps_posts_url_list` does not exist here — only the short-circuit
 * `wp_sitemaps_posts_pre_url_list` + the per-entry `wp_sitemaps_posts_entry`), so a
 * custom provider is the correct way to add URLs. The LV↔EN alternation signal is
 * separately carried by the per-page <link rel="alternate" hreflang> in <head>
 * (qTranslate); the core renderer can't emit hreflang inside an entry.
 */
if ( class_exists( 'WP_Sitemaps_Provider' ) ) {
	class Ignites_Child_EN_Sitemap_Provider extends WP_Sitemaps_Provider {
		public function __construct() {
			$this->name        = 'en';
			$this->object_type = 'en';
		}
		public function get_url_list( $page_num, $object_subtype = '' ) {
			// Shared cached EN-availability scan (ignites_child_en_available_ids):
			// bots hit sitemaps often, and the previous inline loop rescanned every
			// post + page body on each request. Returns [] if qTranslate is inactive.
			$urls = array();
			foreach ( array( 'post', 'page' ) as $pt ) {
				foreach ( ignites_child_en_available_ids( $pt ) as $id ) {
					$en = ignites_child_canonical_en_url( $id );
					if ( $en ) {
						$urls[] = array( 'loc' => $en );
					}
				}
			}
			return $urls;
		}
		public function get_max_num_pages( $object_subtype = '' ) {
			return 1; // small EN set; well under the 2000-URL page cap
		}
	}
	// ClassicPress lacks wp_sitemaps_register_provider(); add to the server registry directly.
	add_action( 'init', function () {
		$server = wp_sitemaps_get_server();
		if ( $server && isset( $server->registry ) && method_exists( $server->registry, 'add_provider' ) ) {
			$server->registry->add_provider( 'en', new Ignites_Child_EN_Sitemap_Provider() );
		}
	}, 20 );
}

// =========================================================================
// Hide untranslated posts from /en/ archive views (infra-docs#226 close-out).
// qTranslate-XT shows fallback LV content for posts without an EN translation;
// this filter excludes those posts from main-query archive listings when
// browsing in EN, so /en/ shows only posts that have actual English content.
// Untouched: LV archives, single post views, admin queries, REST/cron.
//
// Canonical source: infra-scripts/yuno/patches/wordpress/0001-hide-untranslated-posts.php
// Re-applied idempotently by weekly-app-updates.sh `_restore_wp_theme_snippet`.
// =========================================================================
add_action( 'pre_get_posts', function( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) return;
    if ( $query->is_singular() ) return; // single posts: let qTranslate handle LV fallback
    if ( ! function_exists( 'qtranxf_getAvailableLanguages' ) ) return;
    if ( ! function_exists( 'qtranxf_getLanguage' ) || qtranxf_getLanguage() !== 'en' ) return;

    // 5-minute transient cache amortises the all-posts scan across archive renders.
    $untranslated = get_transient( 'ignites_child_en_untranslated' );
    if ( $untranslated === false ) {
        $all = get_posts( [
            'post_type'        => 'post',
            'post_status'      => 'publish',
            'numberposts'      => -1,
            'fields'           => 'ids',
            'suppress_filters' => true,
        ] );
        $untranslated = array_values( array_filter( $all, function( $id ) {
            $available = qtranxf_getAvailableLanguages( get_post_field( 'post_content', $id ) );
            return ! in_array( 'en', (array) $available, true );
        } ) );
        set_transient( 'ignites_child_en_untranslated', $untranslated, 5 * MINUTE_IN_SECONDS );
    }

    if ( ! empty( $untranslated ) ) {
        $query->set( 'post__not_in', $untranslated );
    }
} );

// Drop the cache on real publish-post saves only — autosaves, revisions, drafts,
// nav menu items, attachments, etc. don't change the untranslated set and would
// invalidate the 5-min transient on every editor keystroke / draft autosave.
// Without these guards the cache barely amortizes the 449-post scan.
add_action( 'save_post', function( $post_id ) {
    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) return;
    if ( get_post_type( $post_id ) !== 'post' || get_post_status( $post_id ) !== 'publish' ) return;
    delete_transient( 'ignites_child_en_untranslated' );
} );
