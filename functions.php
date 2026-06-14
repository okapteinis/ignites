<?php
/**
 * Ignites Child — functions
 *
 * @package Ignites_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
	$child_css = get_stylesheet_directory() . '/style.css';
	wp_enqueue_style(
		'ignites-child',
		get_stylesheet_directory_uri() . '/style.css',
		array( 'ignites-parent' ),
		file_exists( $child_css ) ? (string) filemtime( $child_css ) : wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'ignites_child_enqueue', 20 );

/**
 * Perf: drop the duplicate child stylesheet request. The PARENT theme
 * (ignites-nightly/inc/ignites_styles_scripts.php) enqueues get_stylesheet_uri()
 * under handle 'ignites-style' — which resolves to the CHILD style.css, already
 * loaded above as 'ignites-child' (mtime-versioned, parent-dependent). That made
 * the homepage request child/style.css twice (?ver=cp_… and ?ver=<mtime>), an
 * extra render-blocking CSS round-trip flagged by Lighthouse. Dequeue the
 * redundant copy at a late priority (after the parent has registered it).
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_dequeue_style( 'ignites-style' );
	wp_deregister_style( 'ignites-style' );
}, 99 );

/**
 * Perf (ignites#30): trim the critical path.
 * - jQuery → footer. The parent enqueues 'jquery' in <head>, but its only consumer
 *   (ignites-main-js) is already footer-enqueued, and nothing in <head> uses jQuery
 *   (navigation.js / skip-link / the inline head scripts = 0 jQuery refs). Moving the
 *   core/migrate files to the footer group un-blocks ~1.3 s of head render. WP pulls
 *   jQuery back to <head> automatically IFF a plugin head-script declares it as a dep,
 *   so worst case is a no-op, never breakage.
 * - bootstrap-bundle JS is unused: no template uses Bootstrap data attributes
 *   (collapse, dropdown, modal, navbar-toggler) and main.js never calls Bootstrap JS.
 *   Dequeue it (it was footer, so this is a pure byte/request saving, not render-block).
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_script_add_data( 'jquery', 'group', 1 );
	wp_script_add_data( 'jquery-core', 'group', 1 );
	wp_script_add_data( 'jquery-migrate', 'group', 1 );
	wp_dequeue_script( 'bootstrap-bundle' );
	wp_deregister_script( 'bootstrap-bundle' );
}, 99 );

/**
 * Perf (ignites#30): drop WP core's auto fetchpriority=high on post images.
 * Core tags the first content image as its LCP guess, but our real LCP is the CSS
 * masthead (header ::before) — preloaded separately. So the hint is misdirected on a
 * below-the-fold, lazy-loaded thumbnail. Keep loading=lazy; just remove fetchpriority.
 */
add_filter( 'wp_get_attachment_image_attributes', function ( $attr ) {
	unset( $attr['fetchpriority'] );
	return $attr;
}, 20 );

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
	// Cache the integer minute count in post_meta — formatting stays
	// runtime so the i18n string honors current locale + plural forms.
	// Cache is invalidated on save_post (see action below).
	$minutes = (int) get_post_meta( $post->ID, '_ignites_child_reading_minutes', true );
	if ( $minutes <= 0 ) {
		$content = strip_shortcodes( $post->post_content );
		$content = wp_strip_all_tags( $content );
		preg_match_all( '/\p{L}[\p{L}\p{M}\p{Nd}\'-]*/u', $content, $matches );
		$words = count( $matches[0] );
		if ( $words <= 0 ) {
			return '';
		}
		$minutes = max( 1, (int) ceil( $words / 200 ) );
		update_post_meta( $post->ID, '_ignites_child_reading_minutes', $minutes );
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
	delete_post_meta( $post_id, '_ignites_child_reading_minutes' );
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
	printf(
		'<a data-lang-switch href="%s" hreflang="%s" rel="noreferrer" aria-label="%s"><span aria-hidden="true">%s</span></a>',
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
	echo '<link rel="icon" type="image/png" sizes="32x32" href="' . esc_url( $base . '/favicon-32.png' ) . '">' . "\n";
	echo '<link rel="icon" type="image/png" sizes="192x192" href="' . esc_url( $base . '/icon-192.png' ) . '">' . "\n";
	echo '<link rel="apple-touch-icon" sizes="180x180" href="' . esc_url( $base . '/apple-touch-icon.png' ) . '">' . "\n";
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
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
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
