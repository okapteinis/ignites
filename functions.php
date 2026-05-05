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
	wp_enqueue_style(
		'ignites-child',
		get_stylesheet_directory_uri() . '/style.css',
		array( 'ignites-parent' ),
		wp_get_theme()->get( 'Version' )
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
	$content = strip_shortcodes( $post->post_content );
	$content = wp_strip_all_tags( $content );
	$words   = str_word_count( $content );
	if ( $words <= 0 ) {
		return '';
	}
	$minutes = max( 1, (int) ceil( $words / 200 ) );
	/* translators: %d: estimated reading time in minutes. */
	return sprintf( _n( '%d min lasīšana', '%d min lasīšana', $minutes, 'ignites-child' ), $minutes );
}

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

		if (btn) {
			btn.removeAttribute('hidden');
			btn.innerHTML = theme === 'dark' ? sunIcon() : moonIcon();
			btn.addEventListener('click', function () {
				theme = (theme === 'dark') ? 'light' : 'dark';
				html.setAttribute('data-theme', theme);
				btn.innerHTML = theme === 'dark' ? sunIcon() : moonIcon();
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
				'label'   => $label . ' URL',
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
				'label'   => $label . ' handle (e.g. @ojars@social.example)',
				'section' => 'ignites_child_social',
				'type'    => 'text',
			)
		);
	}
}
add_action( 'customize_register', 'ignites_child_customize_register' );
