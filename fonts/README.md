# Fonts

This theme self-hosts **Boska** (display) as woff2 in this folder.

**Satoshi** (body) currently loads from Fontshare's CDN at `api.fontshare.com`. Fontshare's API blocks direct download from automated environments, so I couldn't bundle Satoshi for you in this build.

## To self-host Satoshi as well

1. Visit https://www.fontshare.com/fonts/satoshi
2. Download the family — extract the woff2 files for weights 300, 400, 500, 700.
3. Drop them into this folder as:
   - `satoshi-300.woff2`
   - `satoshi-400.woff2`
   - `satoshi-500.woff2`
   - `satoshi-700.woff2`
4. In `style.css`, add four `@font-face` blocks at the top mirroring the Boska ones, then delete the `wp_enqueue_style('ignites-child-fonts', ...)` call in `functions.php`.

After that, the theme has zero third-party network dependencies.
