# Ignites Child

Modern editorial child theme for the Ignites parent. Targets `ojars.kapteinis.lv`.

## Install

1. Upload `ignites-child.zip` via **ClassicPress → Appearance → Themes → Add New → Upload**.
2. Activate **Ignites Child**. The parent theme **Ignites** must remain installed (not necessarily activated).
3. Done — the live blog now uses Boska + Satoshi typography, warm off-white surfaces, a single teal accent (`#01696f`), reading progress bar on single posts, and a dark-mode toggle pinned bottom-right.

## Files

- `style.css` — child theme header + design tokens + all overrides.
- `functions.php` — enqueues parent + Fontshare fonts; defines `ignites_child_reading_time()`; injects dark-mode toggle + reading-progress bar.
- `archive.php` — archive/home loop with the first post rendered as a full-width hero card.
- `single.php` — single-post template with author box and Latvian prev/next nav.
- `template-parts/content.php` — post card markup, includes Latvian date and reading time.

## Theme switching

The toggle persists to `localStorage['ignites-child-theme']`. Initial theme defaults to system preference (`prefers-color-scheme`). A no-flash script in `<head>` applies the resolved theme before first paint.

## Notes

- No parent files are modified.
- No new JS dependencies (no jQuery plugins).
- Uses the existing parent's `ignites_post_thumbnail`, `ignites_layout_option`, `ignites_num_post_nav` helpers.
