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
	/* translators: %d: estimated reading time in minutes. */
	return sprintf( _n( '%d min lasīšana', '%d min lasīšana', $minutes, 'ignites-child' ), $minutes );
}

/**
 * Invalidate the cached reading-minutes meta on save so edits take
 * effect on next render. Skips revisions/autosaves.
 */
add_action( 'save_post', function ( $post_id ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	delete_post_meta( $post_id, '_ignites_child_reading_minutes' );
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
	if ( false !== strpos( $translation, 'lapa' ) ) {
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

/**
 * Preload the two render-critical font files (Cormorant Garamond latin
 * subset + Inter Variable roman) so they start downloading in parallel
 * with the CSS rather than waiting for the stylesheet to be parsed.
 * Latin-ext + italic stay lazy — most pages don't trigger them on
 * first paint. crossorigin is required even for same-origin font
 * preloads, otherwise the browser ignores the hint.
 */
function ignites_child_preload_fonts() {
	$base = get_stylesheet_directory_uri();
	echo '<link rel="preload" href="' . esc_url( $base . '/assets/fonts/inter/InterVariable.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	echo '<link rel="preload" href="' . esc_url( $base . '/assets/fonts/cormorant-garamond/cormorant-garamond-latin.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
}
add_action( 'wp_head', 'ignites_child_preload_fonts', 2 );

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
 * Set of category term_ids that have at least one EN-available post. Sibling of
 * the hide-untranslated archive filter (infra-docs#226): reuses the same
 * qtranxf_getAvailableLanguages() availability check but caches at CATEGORY
 * granularity in a 5-minute transient, so the nav (rendered on every page) never
 * re-scans all posts. Returns [] (→ all EN category items hidden) if qTranslate
 * is inactive — the EN filter below only runs when qtranxf says we're on /en/.
 */
function ignites_child_en_available_categories() {
	$cats = get_transient( 'ignites_child_en_avail_cats' );
	if ( false !== $cats ) {
		return (array) $cats;
	}
	$cats = array();
	if ( function_exists( 'qtranxf_getAvailableLanguages' ) ) {
		$ids = get_posts( array(
			'post_type'        => 'post',
			'post_status'      => 'publish',
			'numberposts'      => -1,
			'fields'           => 'ids',
			'suppress_filters' => true,
		) );
		foreach ( $ids as $id ) {
			$available = qtranxf_getAvailableLanguages( get_post_field( 'post_content', $id ) );
			if ( in_array( 'en', (array) $available, true ) ) {
				foreach ( wp_get_post_categories( $id ) as $cat_id ) {
					$cats[ $cat_id ] = true; // dedupe on key
				}
			}
		}
		$cats = array_map( 'intval', array_keys( $cats ) );
	}
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
 * Invalidate the EN-available-categories cache on publish/edit so a newly
 * translated post re-shows its menu item within one cache cycle.
 */
add_action( 'save_post', function ( $post_id ) {
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	delete_transient( 'ignites_child_en_avail_cats' );
} );
