# Self-hosted fonts

Both fonts ship under **SIL Open Font License 1.1**, which explicitly permits self-hosting and bundling. License texts are kept alongside each family.

## Cormorant Garamond (display)

- Author: Christian Thalmann / Cormorant Project
- Source: Google Fonts variable build (`fonts.gstatic.com/s/cormorantgaramond/v21/...`), upstream at <https://github.com/CatharsisFonts/Cormorant>
- License: SIL OFL 1.1 — see `cormorant-garamond/OFL.txt`
- Format: variable woff2, weight axis 300-700
- Subsets: `latin` + `latin-ext` (covers Latvian + most European; cyrillic and vietnamese subsets dropped to keep payload small)

## Inter (body)

- Author: Rasmus Andersson / Inter Project
- Source: <https://rsms.me/inter/font-files/InterVariable.woff2> (and `-Italic.woff2`)
- License: SIL OFL 1.1 — see `inter/LICENSE.txt`
- Format: two variable woff2 files, weight axis 100-900, roman + true italic
- Note: this build deliberately uses the v4-style two-file split (roman + italic) rather than the older v3 `Inter[slnt,wght].woff2`. The v4 italic is a designed companion typeface, not a synthetic slnt-axis oblique — better blockquote rendering at the cost of one extra file (~388 KB vs synthesizing italic from a single ~352 KB file).

## License compliance

SIL OFL 1.1 §1 requires the license to ship with the redistributed binaries, which is what the `OFL.txt` / `LICENSE.txt` files in each subdirectory satisfy. Neither family includes a Reserved Font Name we'd violate — we ship the original woff2 unmodified and unrenamed.

## Why not Fontshare's Boska + Satoshi?

The original brief specified those, and they were used through 2026-05-05. Fontshare's Free Font EULA §02 prohibits self-hosting webfonts via `@font-face` ("transmit ... over the Internet in font serving ... [similar to] EOT/Cufon/sIFR"); the only authorized free-tier delivery mechanism is `api.fontshare.com`, which leaks every reader's IP and User-Agent to a Cloudflare-fronted CDN. Replacing them with SIL OFL fonts achieves the privacy-first goal that the rest of the kapteinis.lv stack already follows. See `ojars/ignites#6` and `#9` on Forgejo for the full audit trail.
