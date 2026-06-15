# Ignites — Code Review Findings (PARENT / `nightly`)

**Date:** 2026-06-15 · **Reviewer:** Claude Code (operator-driven) · **Runtime:** ClassicPress on yuno, php-fpm 8.3 (CLI 8.4)
**Scope:** security · WP/CP API correctness & logic · refactor · performance (review + safe LCP probe — per operator decision)
**Branch reviewed:** `origin/nightly` @ `cc6b552` (review worktree `review/nightly-audit`)

> **Lint:** all 24 `.php` files across both branches pass `php8.3 -l` (live runtime) — zero parse errors.
> **Posture:** this is a mature, multi-pass-reviewed theme. **No Critical or High security findings.** No SQL, no `eval`/`extract`/`base64`-on-input, no unsanitized superglobal output, no nonce-less mutations, no custom `WP_Query` (so no `wp_reset_postdata` gaps). Findings below are Low/Medium hardening + cleanup.

## Security

| # | Sev | File:line | Issue | Fix |
|---|-----|-----------|-------|-----|
| P1 | Low | `header.php:48` | `echo $ignites_description; /* WPCS: xss ok. */` — stock `_s` pattern; `get_bloginfo('description','display')` is admin-only and not HTML-escaped at output. | `echo esc_html( $ignites_description );`, drop the suppression comment. Behavior-preserving (tagline is plain text). |
| P2 | Low | `functions.php`, `inc/*.php` (8 includes) | No `if ( ! defined('ABSPATH') ) exit;` guard on the function-definition files (child `functions.php` has one). Defense-in-depth against direct file access. | Add the guard to `functions.php` + each `inc/**.php`. Template files (loaded by WP) left as-is per `_s` convention. |
| P3 | Low | `inc/customizer/customizer.php:142` | `background-color: <?php echo esc_html(get_theme_mod('header_bg_color')); ?>` — `esc_html` is the wrong escaper for a CSS-value context. **Mitigated**: the setting's `sanitize_callback` is `sanitize_hex_color`, so the stored value is always `#rrggbb`/empty. | Optional defense-in-depth: validate/`absint`-style hex at output, or leave (input-sanitized). |

`search.php:30` (`get_search_query()` into `printf`) is **safe** — `get_search_query()` defaults to `esc_attr`-escaped output; no reflected-XSS path. No change.

## Logic / WP-CP API correctness

| # | Sev | File:line | Issue | Fix |
|---|-----|-----------|-------|-----|
| P4 | Low | `comments.php:43` | `_nx( …, …, esc_html($ignites_comments_number), … )` — the 3rd arg drives **plural selection**, not output; wrapping it in `esc_html()` is pointless and semantically wrong (returns a string into an int-context). The *output* arg (line 47, `number_format_i18n`) is correctly escaped. | Pass the raw int: `(int) $ignites_comments_number`. |
| P5 | Low | `footer.php:26` | `echo date("Y");` — raw PHP `date()` instead of the WP API. | `echo esc_html( date_i18n('Y') );` |

## Dead code / refactor

| # | Sev | File:line | Issue | Fix |
|---|-----|-----------|-------|-----|
| P6 | Low | `template-parts/content-search.php:28-30` | Empty `if ( 'post' === get_post_type() ) : ?> <?php endif;` — does nothing. | Remove the empty conditional. |
| P7 | Low | `inc/custom-functions.php:34` | Commented-out `// $GLOBALS['comment'] = $comment;` dead line. | Remove. |
| P8 | Low | `inc/custom-functions.php:162-164` | `echo esc_html($layout_class = "col-lg-12 fullwidth-content");` — dead assignment (`$layout_class` unused) + needless `esc_html()` on a static literal. | `echo 'col-lg-12 fullwidth-content';` / `echo 'col-lg-8';` |
| P9 | Info | `search.php:29` | Translator comment says "WordPress version number" — copy-paste leftover; the `%s` is the search query. | Correct the comment. |

## Performance — verified state (review + safe LCP probe)

Most of the brief's perf asks are **already shipped** (PRs #30–#35) or were **deliberately reverted** (ignites#36, the 2026-06-15 FOUC/clamp incident). Verified live + in `origin`:

- ✅ Inline critical CSS (`critical.css`, ~25.4 KB) · ✅ Bootstrap CSS+JS + jQuery fully dequeued · ✅ Inter/Cormorant subset + `font-display:swap` · ✅ LCP masthead webp (60–69 KB) preloaded `fetchpriority=high` · ✅ `filemtime()` cache-busting · ✅ Google Fonts CDN removed (privacy) · ✅ `style-editor.css` moved to editor-only enqueue.
- ⛔ **Do NOT re-introduce** the `media=print` async swap (`functions.php:207`, disabled) or CSS minify (`$use_min=false`) — both caused the live "you broke my blog" incident; minify is "permanently OFF" per ignites#36.

**LCP probe result (the only failing metric):** the bottleneck is **origin TTFB on CF cache-miss**, not the theme. yuno has **no `fastcgi_cache`, no page-cache plugin, `WP_CACHE` unset** → every cache-miss runs full ClassicPress + qTranslate-XT. Edge is cached (cf-cache HIT, 57 ms). **Recommendation (out of theme scope):** add nginx `fastcgi_cache` microcaching in front of php-fpm for `ojars.kapteinis.lv`. Ticketed separately. **No theme change moves LCP further without re-opening the #36 incident class.**

## Needs human decision (not changed)

See child branch `REVIEW_FINDINGS.md` — the items requiring a call are child-side (reading-time bilingual word-count, hero-on-paged-archives, the canonical infra-scripts patch block).
