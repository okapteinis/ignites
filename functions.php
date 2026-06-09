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
	// Version by file mtime (not the theme Version header) so the ?ver= query
	// busts browser + CDN caches on EVERY edit. Using the static theme Version
	// meant a CSS change under an unchanged ?ver=1.1.1 kept serving stale CSS
	// from cache (the 2026-06-09 floating-switcher regression).
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
add_action( 'wp_body_open', 'ignites_child_render_theme_toggle' );

/**
 * Floating language switcher — a circular control stacked directly ABOVE the
 * dark/light toggle in the same bottom-right corner (same wp_body_open hook;
 * CSS positions it above the toggle, see [data-lang-switch] in style.css).
 *
 * Replaces qTranslate-XT's nav-menu language item (suppressed by the filter
 * below) with a theme-native control. Shows the TARGET language code — EN on
 * Latvian pages, LV on English pages — monochrome + typographic to mirror the
 * sun/moon toggle. Uses qTranslate-XT helpers for current-language detection
 * and URL generation; no plugin files are touched.
 */
function ignites_child_render_lang_switch() {
	// Degrade gracefully if qTranslate-XT is inactive — render nothing rather
	// than a broken control. (function_exists, not a hook — the helpers are
	// callable at template time; only qtranslate's plugins_loaded:2 INIT hooks
	// are unreachable theme-side, see infra-docs#257.)
	if ( ! function_exists( 'qtranxf_getLanguage' ) || ! function_exists( 'qtranxf_convertURL' ) ) {
		return;
	}

	$current = qtranxf_getLanguage();                       // 'lv' | 'en'
	$target  = ( 'en' === $current ) ? 'lv' : 'en';

	// Convert the CURRENT front-end URL to the target language via the plugin
	// helper. qtranxf_convertURL('') strips the trailing slash from path-mode
	// roots (infra-docs#258d) → pass the real URL and trailingslashit the
	// result (only when there's no query string) to avoid a 301 hop on click.
	$current_url = home_url( add_query_arg( array() ) );
	$target_url  = qtranxf_convertURL( $current_url, $target );
	if ( false === strpos( (string) $target_url, '?' ) ) {
		$target_url = trailingslashit( $target_url );
	}

	// aria-label is authored in the CURRENT page language (so it reads
	// correctly even before .mo lookup): on a LV page we offer English, etc.
	$label = ( 'en' === $target )
		? __( 'Pārslēgt uz angļu valodu', 'ignites-child' )
		: __( 'Switch to Latvian', 'ignites-child' );
	?>
	<a data-lang-switch href="<?php echo esc_url( $target_url ); ?>" hreflang="<?php echo esc_attr( $target ); ?>" rel="alternate" aria-label="<?php echo esc_attr( $label ); ?>"><span aria-hidden="true"><?php echo esc_html( strtoupper( $target ) ); ?></span></a>
	<?php
}
add_action( 'wp_body_open', 'ignites_child_render_lang_switch' );

/**
 * Suppress qTranslate-XT's language switcher from the nav menus — its menu
 * presentation is replaced by the floating control above. wp_nav_menu_objects
 * runs at render time, so this theme-side filter registers in time (unlike
 * qTranslate's plugins_loaded:2 init hooks — infra-docs#257).
 *
 * Drops the switcher container (`menu-language-switcher`), any flat per-language
 * items (`lang-item`), and any direct children of the container — covering both
 * the single-item and dropdown switcher variants.
 */
add_filter( 'wp_nav_menu_objects', function ( $items ) {
	if ( ! is_array( $items ) ) {
		return $items;
	}
	$switcher_ids = array();
	foreach ( $items as $item ) {
		$classes = isset( $item->classes ) ? (array) $item->classes : array();
		if ( in_array( 'menu-language-switcher', $classes, true ) || in_array( 'lang-item', $classes, true ) ) {
			$switcher_ids[] = (int) $item->ID;
		}
	}
	foreach ( $items as $key => $item ) {
		$classes     = isset( $item->classes ) ? (array) $item->classes : array();
		$is_switcher = in_array( 'menu-language-switcher', $classes, true ) || in_array( 'lang-item', $classes, true );
		$is_child    = isset( $item->menu_item_parent ) && in_array( (int) $item->menu_item_parent, $switcher_ids, true );
		if ( $is_switcher || $is_child ) {
			unset( $items[ $key ] );
		}
	}
	return $items;
}, 10, 1 );

/**
 * Belt-and-suspenders: if qTranslate-XT injects the switcher as raw markup
 * rather than as a menu object (so the objects filter above can't see it),
 * strip the `<li … menu-language-switcher …>…</li>` element from the rendered
 * menu HTML. Runs late (priority 100) so it sees qTranslate's output. No-op
 * when the objects filter already removed the item.
 */
add_filter( 'wp_nav_menu_items', function ( $items_html ) {
	if ( ! is_string( $items_html ) || false === strpos( $items_html, 'menu-language-switcher' ) ) {
		return $items_html;
	}
	return preg_replace( '#<li[^>]*\bmenu-language-switcher\b[^>]*>.*?</li>#is', '', $items_html );
}, 100, 1 );

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
	// 1. WP-core paginate_links: drop redundant `lapa` from the chevron.
	if ( false !== strpos( $translation, 'lapa' ) ) {
		$translation = str_replace(
			array( 'Nākamā lapa', 'Iepriekšējā lapa' ),
			array( 'Nākamā', 'Iepriekšējā' ),
			$translation
		);
	}

	// 2. Parent theme strings (English-only) — translate to Latvian for
	// the search results / empty-state pages. Keyed on source `$text`
	// so the lookup is exact and the `ignites` text domain is implied
	// by these specific strings being ours to handle.
	$parent_translations = array(
		'Nothing Found'
			=> 'Nekas nav atrasts',
		'Sorry, but nothing matched your search terms. Please try again with some different keywords.'
			=> 'Diemžēl meklētajam neviens raksts neatbilst. Pamēģini ar citiem atslēgvārdiem.',
		'Search Results for: %s'
			=> 'Meklēšanas rezultāti: %s',
		'It seems we can&rsquo;t find what you&rsquo;re looking for. Perhaps searching can help.'
			=> 'Šķiet, šeit nekas neatbilst meklētajam. Iespējams, meklētājs palīdzēs.',
	);
	if ( 'ignites' === $domain && isset( $parent_translations[ $text ] ) ) {
		return $parent_translations[ $text ];
	}

	return $translation;
}, 10, 3 );

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
