# Ignites — Code Review Findings (CHILD / `alpha`)

**Date:** 2026-06-15 · **Reviewer:** Claude Code (operator-driven) · **Runtime:** ClassicPress on yuno, php-fpm 8.3
**Scope:** security · WP/CP API correctness & logic · refactor · performance (review + safe LCP probe)
**Branch reviewed:** `origin/alpha` @ `116f5cd` (review worktree `review/alpha-audit`)

> The child theme is **exemplary**: every output sink is escaped (`esc_html`/`esc_attr`/`esc_url`/`wp_kses_post`), `ABSPATH` guard present, i18n consistent (`ignites-child` domain), qTranslate-XT integration deliberate and documented. **No Critical/High/Medium security findings.**

## Security / hygiene

| # | Sev | File:line | Issue | Fix |
|---|-----|-----------|-------|-----|
| C1 | Low | `functions.php:777` | `$_SERVER['HTTP_HOST']` is `wp_unslash`+`strtolower`'d and only `===`-compared to a literal (never output) — functionally safe, but not run through `sanitize_text_field()`. | Wrap in `sanitize_text_field()` for lint-cleanliness. Behavior-preserving. |

The `readfile($icon_path)` SVG inline in `page-about.php:74` uses a **hardcoded, theme-bundled** path (`assets/icons/<fixed-name>.svg`, `file_exists`-guarded) — not user input. Safe.

## Performance — verified already-optimal (no change)

Child carries the shipped perf work: critical-CSS inline, jQuery→vanilla `main.js`, Bootstrap dequeue, font subset + `swap`, webp `<picture>` wrapper, LCP-image preload. The async-swap filter (`functions.php:207`) is **intentionally disabled** (ignites#36) and minify is **off** (`$use_min=false`). **Both must stay as-is** — re-enabling either re-creates the 2026-06-15 FOUC/clamp incident. LCP bottleneck is server-side (origin TTFB / no `fastcgi_cache`) — see parent `REVIEW_FINDINGS.md`.

## Needs human decision (NOT changed — would alter visible behavior or a canonical patch)

| # | File | Observation | Why deferred to you |
|---|------|-------------|---------------------|
| H1 | `functions.php:348` `ignites_child_reading_time()` | Word count runs over the full `post_content`, which holds **both** `[:lv]…[:en]…` languages → bilingual posts show **~2× the real per-language reading time**. | Fixing means counting only the current language (`qtranxf_use`) **and** caching reading-minutes **per language** (current cache is language-agnostic post_meta). That changes the displayed numbers — a content decision, not a bug-fix. |
| H2 | `archive.php:30` hero logic | `1 === $post_count` makes the **first post of every paginated page** a full-width hero (page 2, 3, …), not just page 1. | If the hero should be page-1-only, gate with `! is_paged()`. May be intended. |
| H3 | `functions.php:1061` + `:1093` (hide-untranslated `pre_get_posts` + its `save_post`) | This block duplicates the all-posts EN scan that `ignites_child_en_available_ids()` already does (two full-table scans where the untranslated set = all − en-available). | **Do not refactor here:** the block is the canonical copy of `infra-scripts/yuno/patches/wordpress/0001-hide-untranslated-posts.php`, re-applied idempotently by `weekly-app-updates.sh`. Any DRY merge belongs in the infra-scripts patch (else it gets clobbered on the next weekly run). Worth an infra-scripts ticket. |
| H4 | `functions.php:207-232` `ignites_child_async_noncritical_css()` | Defined but `add_filter` commented out (disabled 2026-06-15). | Keep (documented + has clear re-enable conditions) or delete. Low. Recommend keep. |

## Applied fixes (this review)

Only C1 (child) is applied here; the actionable fixes are parent-side (P1–P9) — see the parent branch.
