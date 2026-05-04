# Task: Create `ignites-child` — Modern Redesign of ojars.kapteinis.lv

> **Provenance:** Brief authored by **Perplexity** (2026-05-04). Theme implemented by a **Claude Design** session from this brief — output zipped as `ignites-child.zip` and committed verbatim to this `alpha` branch by Claude Code on 2026-05-05. Reads in Perplexity's voice; the implementation commits are Claude's.

## Context & Goal

You are building a **ClassicPress child theme** called `ignites-child` for the personal blog at `ojars.kapteinis.lv`. The parent theme is **Ignites** (Bootstrap 5, PHP 8.4 compatible), hosted at `https://github.com/okapteinis/ignites/tree/nightly`.

The goal is to modernize the blog's visual design to feel like a well-crafted 2026 editorial site — inspired by the clean reading experience of **Perplexity's blog**, **Anthropic/Claude's blog**, and **OpenAI's blog** — while keeping the existing ClassicPress/WordPress template structure fully intact. No parent theme PHP files should be modified.

The blog is personal — it covers politics, culture, links roundups ("saites"), and general commentary written in **Latvian**. The tone should be **warm, editorial, intellectual** — not corporate, not startup-ish.

---

## Deliverable

A complete, installable child theme directory `ignites-child/` containing:

```
ignites-child/
├── style.css           ← child theme header + ALL CSS overrides
├── functions.php       ← enqueue parent + fonts, add reading time, dark mode
├── archive.php         ← improved archive/home loop with hero first post
├── single.php          ← improved single post with reading progress bar
└── template-parts/
    └── content.php     ← improved post card template
```

Zip the directory as `ignites-child.zip` for direct upload to ClassicPress > Appearance > Themes.

---

## Design Direction

**Art direction:** Warm editorial. Personal. Intellectual. Latvian.
**Palette:** Warm off-white surfaces, single teal accent (`#01696f`), dark warm near-black text.
**Typography:** Boska (display, headings) + Satoshi (body) — both from Fontshare CDN.
**Density:** Spacious. Generous line-height. Reading comfort first.
**Motion:** Minimal. Subtle only. Fade-in on scroll for post cards.
**Dark mode:** Yes — warm dark (`#171614` bg), toggled via sun/moon button in header, defaults to system preference.

---

## Reference Designs (what to emulate)

Study these patterns:

- **Perplexity blog** — warm off-white bg, large bold serif post titles, full-bleed hero image on single post, clean author+date row, generous margins
- **Anthropic/Claude blog** — pure-white + heavy typographic hierarchy, sticky table of contents sidebar, "Related posts" grid at bottom, inline code styling
- **OpenAI blog** — dark mode by default option, wide reading column, serif chapter headings, embedded diagrams comfortable in flow

---

## 1. Design Tokens (CSS Custom Properties)

Define all tokens in `:root` inside `style.css`. Both light and dark mode required.

```css
:root, [data-theme="light"] {
  --color-bg:             #f7f5f1;
  --color-surface:        #faf9f6;
  --color-surface-2:      #fcfbf9;
  --color-surface-offset: #f0ede8;
  --color-border:         rgba(0,0,0,0.09);
  --color-divider:        rgba(0,0,0,0.06);

  --color-text:           #28251d;
  --color-text-muted:     #6b6860;
  --color-text-faint:     #a8a69f;
  --color-text-inverse:   #f9f8f4;

  --color-primary:        #01696f;
  --color-primary-hover:  #0c4e54;
  --color-primary-highlight: #cedcd8;

  --font-display: 'Boska', 'Georgia', serif;
  --font-body:    'Satoshi', 'Inter', sans-serif;

  --text-xs:   clamp(0.75rem,  0.7rem  + 0.25vw, 0.875rem);
  --text-sm:   clamp(0.875rem, 0.8rem  + 0.35vw, 1rem);
  --text-base: clamp(1rem,     0.95rem + 0.25vw, 1.125rem);
  --text-lg:   clamp(1.125rem, 1rem    + 0.75vw, 1.5rem);
  --text-xl:   clamp(1.5rem,   1.2rem  + 1.25vw, 2.25rem);
  --text-2xl:  clamp(2rem,     1.2rem  + 2.5vw,  3.5rem);
  --text-3xl:  clamp(2.5rem,   1rem    + 4vw,    5rem);

  --space-2:  0.5rem;
  --space-3:  0.75rem;
  --space-4:  1rem;
  --space-6:  1.5rem;
  --space-8:  2rem;
  --space-12: 3rem;
  --space-16: 4rem;

  --radius-sm:   0.375rem;
  --radius-md:   0.5rem;
  --radius-lg:   0.75rem;
  --radius-full: 9999px;

  --shadow-sm: 0 1px 3px rgba(40,37,29,0.06);
  --shadow-md: 0 4px 14px rgba(40,37,29,0.08);
  --shadow-lg: 0 12px 32px rgba(40,37,29,0.12);

  --transition: 180ms cubic-bezier(0.16, 1, 0.3, 1);

  --content-narrow:  640px;
  --content-default: 900px;
  --content-wide:   1140px;
}

[data-theme="dark"] {
  --color-bg:             #171614;
  --color-surface:        #1c1b19;
  --color-surface-2:      #201f1d;
  --color-surface-offset: #22211f;
  --color-border:         rgba(255,255,255,0.08);
  --color-divider:        rgba(255,255,255,0.05);
  --color-text:           #cdccca;
  --color-text-muted:     #797876;
  --color-text-faint:     #5a5957;
  --color-text-inverse:   #2b2a28;
  --color-primary:        #4f98a3;
  --color-primary-hover:  #227f8b;
  --color-primary-highlight: #313b3b;
  --shadow-sm: 0 1px 3px rgba(0,0,0,0.25);
  --shadow-md: 0 4px 14px rgba(0,0,0,0.35);
  --shadow-lg: 0 12px 32px rgba(0,0,0,0.45);
}

@media (prefers-color-scheme: dark) {
  :root:not([data-theme]) {
    /* duplicate all [data-theme="dark"] values here */
  }
}
```

---

## 2. `functions.php`

```php
<?php
function ignites_child_enqueue() {
    // Parent theme stylesheet
    wp_enqueue_style('ignites-parent', get_template_directory_uri() . '/style.css');

    // Fontshare: Boska + Satoshi
    wp_enqueue_style('ignites-fonts',
        'https://api.fontshare.com/v2/css?f[]=boska@400,500,700&f[]=satoshi@300,400,500,700&display=swap',
        [], null
    );

    // Child stylesheet
    wp_enqueue_style('ignites-child',
        get_stylesheet_directory_uri() . '/style.css',
        ['ignites-parent', 'ignites-fonts']
    );
}
add_action('wp_enqueue_scripts', 'ignites_child_enqueue', 20);
```

Additionally in `functions.php`:

- **Reading time function:** `ignites_child_reading_time()` — counts post words, returns string like "5 min lasīšana" (Latvian). Use `str_word_count(strip_tags(get_the_content()))` divided by 200.
- **Dark mode toggle:** inject a `<button data-theme-toggle aria-label="Mainīt tēmu">` into the header via `wp_body_open` action (or hook into `wp_footer` and move it via JS). Include the toggle JS inline via `wp_add_inline_script`.

---

## 3. `style.css` — Complete CSS Overrides

The `style.css` must begin with the child theme header comment, then the design tokens block above, then the following override sections in order:

### 3.1 Base Reset Overrides

Override the parent's `main.css` defaults:

```css
body {
  font-family: var(--font-body);
  font-size: var(--text-base);
  line-height: 1.75;
  color: var(--color-text);
  background-color: var(--color-bg);
  -webkit-font-smoothing: antialiased;
}

h1, h2, h3, h4, h5, h6,
.h1, .h2, .h3, .h4, .h5, .h6 {
  font-family: var(--font-display);
  color: var(--color-text);
  line-height: 1.2;
  font-weight: 700;
}

a { color: var(--color-primary); }
a:hover { color: var(--color-primary-hover); }

::selection {
  background: var(--color-primary-highlight);
  color: var(--color-text);
}
```

### 3.2 Header

The parent renders `.header-section > .container > .site-header` with `.site-branding` (title) and `.main-navigation`.

Target:
- `.header-section` — remove white background, use `--color-surface`, add bottom border using `--color-divider`, reduce padding from 30px to 16px top/bottom
- `.site-title a` — use `var(--font-display)`, `var(--text-xl)`, `var(--color-text)`, no underline
- `.primary-menu > li > a` — `var(--text-sm)`, letter-spacing 0.06em, `var(--color-text-muted)`, border-bottom 2px solid transparent, transition; on hover/active: `var(--color-primary)`, border-bottom color `var(--color-primary)`
- Add dark mode toggle button: position it absolutely at the right side of `.site-header`, style it as an icon button (24px, no background, `var(--color-text-muted)` color)

### 3.3 Archive / Home Post Loop

The parent renders posts inside `#main .site-main` as stacked `<article>` elements via `content.php`. Override:

```css
/* Turn the post list into a responsive grid */
.home #main .site-main,
.archive #main .site-main,
.blog #main .site-main {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--space-6);
  align-items: start;
}

/* Pagination spans full width */
.home #main .site-main .dope-pagination,
.archive #main .site-main .dope-pagination,
.blog #main .site-main .dope-pagination {
  grid-column: 1 / -1;
}

/* First article = hero: full width, large title */
.home #main .site-main article:first-child,
.archive #main .site-main article:first-child,
.blog #main .site-main article:first-child {
  grid-column: 1 / -1;
}
.home #main .site-main article:first-child h2.entry-title,
.archive #main .site-main article:first-child h2.entry-title,
.blog #main .site-main article:first-child h2.entry-title {
  font-size: var(--text-2xl);
}

/* Post cards */
article.post, article.hentry {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  transition: box-shadow var(--transition), transform var(--transition);
  box-shadow: var(--shadow-sm);
}
article.post:hover, article.hentry:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}

/* Card inner padding */
.wrap-content {
  padding: var(--space-6);
}

/* Post title */
h2.entry-title {
  font-family: var(--font-display);
  font-size: var(--text-xl);
  line-height: 1.2;
  margin-bottom: var(--space-3);
}
h2.entry-title a {
  color: var(--color-text);
  text-decoration: none;
}
h2.entry-title a:hover { color: var(--color-primary); }

/* Excerpt */
.entry-summary p, .entry-content p.m-0 {
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  line-height: 1.7;
  margin-top: var(--space-2);
}
```

### 3.4 Post Meta (date, categories, reading time)

Style the post meta row (categories + date + reading time):

```css
.entry-category a,
.entry-category {
  font-size: var(--text-xs);
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--color-primary);
  font-weight: 600;
  text-decoration: none;
}

.entry-footer,
.post-meta {
  display: flex;
  align-items: center;
  gap: var(--space-4);
  flex-wrap: wrap;
  font-size: var(--text-xs);
  color: var(--color-text-faint);
  margin-top: var(--space-4);
  padding-top: var(--space-4);
  border-top: 1px solid var(--color-divider);
}
```

### 3.5 Tag Pills

```css
.tags-links a {
  display: inline-block;
  padding: 3px 12px;
  background: var(--color-surface-offset);
  border-radius: var(--radius-full);
  font-size: var(--text-xs);
  text-decoration: none;
  color: var(--color-text-muted);
  margin: 2px 2px 2px 0;
  border: 1px solid var(--color-border);
  transition: background var(--transition), color var(--transition);
}
.tags-links a:hover {
  background: var(--color-primary-highlight);
  color: var(--color-primary);
  border-color: transparent;
}
```

### 3.6 Single Post Reading View

The single post renders `h1.entry-title` + `.entry-content` inside `.single .site-main`.

```css
/* Constrain to readable column */
.single .site-main {
  max-width: var(--content-narrow);
  margin-inline: auto;
}

/* Large display title */
.single h1.entry-title {
  font-family: var(--font-display);
  font-size: var(--text-3xl);
  line-height: 1.1;
  margin-bottom: var(--space-6);
  color: var(--color-text);
}

/* Body text */
.single .entry-content {
  font-size: var(--text-lg);
  line-height: 1.85;
  color: var(--color-text);
}
.single .entry-content p { margin-bottom: var(--space-6); }
.single .entry-content h2 { font-size: var(--text-xl); margin: var(--space-12) 0 var(--space-4); }
.single .entry-content h3 { font-size: var(--text-lg); margin: var(--space-8) 0 var(--space-3); }
.single .entry-content a { color: var(--color-primary); text-decoration: underline; text-decoration-color: var(--color-primary-highlight); text-underline-offset: 3px; }
.single .entry-content a:hover { text-decoration-color: var(--color-primary); }

/* Block quotes */
.single .entry-content blockquote {
  margin: var(--space-8) 0;
  padding: var(--space-4) var(--space-6);
  border-left: 3px solid var(--color-primary);
  background: var(--color-surface);
  border-radius: 0 var(--radius-md) var(--radius-md) 0;
  font-style: italic;
  color: var(--color-text-muted);
}

/* Code */
.single .entry-content code {
  font-size: 0.875em;
  background: var(--color-surface-offset);
  border-radius: var(--radius-sm);
  padding: 0.15em 0.4em;
  color: var(--color-primary);
}
.single .entry-content pre {
  background: var(--color-surface-offset);
  border-radius: var(--radius-md);
  padding: var(--space-6);
  overflow-x: auto;
  margin: var(--space-6) 0;
}
```

### 3.7 Single Post — Reading Progress Bar

Add a thin (`3px`) fixed progress bar at the very top of the viewport on single posts:

```css
#reading-progress {
  position: fixed;
  top: 0; left: 0;
  width: 0%;
  height: 3px;
  background: var(--color-primary);
  z-index: 9999;
  transition: width 0.1s linear;
}
```

JS (inline via `wp_add_inline_script` on single posts only):

```js
(function() {
  if (!document.body.classList.contains('single')) return;
  var bar = document.createElement('div');
  bar.id = 'reading-progress';
  document.body.prepend(bar);
  window.addEventListener('scroll', function() {
    var el = document.documentElement;
    var scrollTop = el.scrollTop || document.body.scrollTop;
    var scrollHeight = el.scrollHeight - el.clientHeight;
    bar.style.width = scrollHeight > 0 ? (scrollTop / scrollHeight * 100) + '%' : '0%';
  });
})();
```

### 3.8 Post Navigation (Previous / Next)

The parent outputs `.navigation.post-navigation` with `.nav-previous` and `.nav-next` inside a Bootstrap row. Style it to look like a modern "next/prev" strip:

```css
.navigation.post-navigation {
  margin-top: var(--space-16);
  padding-top: var(--space-8);
  border-top: 1px solid var(--color-divider);
}
.nav-previous a, .nav-next a {
  color: var(--color-text-muted);
  text-decoration: none;
  font-size: var(--text-sm);
  transition: color var(--transition);
}
.nav-previous a:hover, .nav-next a:hover { color: var(--color-primary); }
.nav-txt {
  display: block;
  font-size: var(--text-xs);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--color-text-faint);
  margin-bottom: var(--space-2);
}
```

### 3.9 Author Box

The parent renders `.author-wrap` with `.author-img` (avatar) and `.author-details`. Since this is a personal blog, simplify:

```css
.author-wrap {
  display: flex;
  align-items: center;
  gap: var(--space-6);
  padding: var(--space-6);
  background: var(--color-surface);
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  margin: var(--space-12) 0;
}
.author-img img {
  border-radius: var(--radius-full);
  width: 60px;
  height: 60px;
  object-fit: cover;
}
.author-details h2 {
  font-size: var(--text-base);
  font-weight: 600;
  margin-bottom: var(--space-2);
}
.author-details p {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  max-width: 60ch;
}
```

### 3.10 Dark Mode Toggle Button

```css
[data-theme-toggle] {
  position: fixed;
  bottom: var(--space-6);
  right: var(--space-6);
  width: 40px;
  height: 40px;
  border-radius: var(--radius-full);
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  box-shadow: var(--shadow-md);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: var(--color-text-muted);
  transition: background var(--transition), box-shadow var(--transition);
  z-index: 1000;
}
[data-theme-toggle]:hover {
  background: var(--color-surface-offset);
  box-shadow: var(--shadow-lg);
  color: var(--color-text);
}
```

### 3.11 Footer

```css
.footer-section {
  background: var(--color-surface);
  border-top: 1px solid var(--color-divider);
  padding: var(--space-8) 0;
}
.site-footer, .site-info {
  color: var(--color-text-faint);
  font-size: var(--text-xs);
  text-align: center;
}
```

### 3.12 Scroll-to-top Button

The parent has `.scroll-top` in `footer.php`:

```css
.scroll-top {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-full);
  color: var(--color-text-muted);
  box-shadow: var(--shadow-sm);
  transition: box-shadow var(--transition), color var(--transition);
}
.scroll-top:hover {
  box-shadow: var(--shadow-md);
  color: var(--color-primary);
}
```

### 3.13 Responsive / Mobile

```css
@media (max-width: 767px) {
  .home #main .site-main,
  .archive #main .site-main,
  .blog #main .site-main {
    grid-template-columns: 1fr;
  }
  .home #main .site-main article:first-child {
    grid-column: 1;
  }
  .single h1.entry-title { font-size: var(--text-2xl); }
  .wrap-content { padding: var(--space-4); }
  .main-content-section { padding: var(--space-8) 0; }
}
```

---

## 4. `archive.php` — Hero First Post

Override the parent archive to **detect the first post** and render it differently. Use a loop counter:

```php
<?php get_header(); ?>
<div class="main-content-section">
  <div class="container">
    <div class="row d-flex justify-content-center">
      <div class="col-lg-10">
        <main id="main" class="site-main">
          <?php if (have_posts()) :
            $post_count = 0;
            while (have_posts()) : the_post();
              $post_count++;
              if ($post_count === 1) {
                // Hero post — render inline with extra classes
                echo '<article id="post-' . get_the_ID() . '" class="post hentry post-hero">';
                echo '<div class="wrap-content">';
                echo '<div class="entry-category">' . get_the_category_list(', ') . '</div>';
                echo '<h2 class="entry-title"><a href="' . get_permalink() . '">' . get_the_title() . '</a></h2>';
                echo '<p class="entry-excerpt">' . get_the_excerpt() . '</p>';
                echo '<div class="entry-footer">';
                echo '<span class="post-date">' . get_the_date('j. F Y') . '</span>';
                echo '<span class="reading-time">' . ignites_child_reading_time() . '</span>';
                echo '</div></div></article>';
              } else {
                get_template_part('template-parts/content', get_post_type());
              }
            endwhile;
          ?>
          <div class="dope-pagination text-center"><?php ignites_num_post_nav(); ?></div>
          <?php else : get_template_part('template-parts/content', 'none'); endif; ?>
        </main>
      </div>
    </div>
  </div>
</div>
<?php get_footer(); ?>
```

---

## 5. `template-parts/content.php`

Override to add reading time and cleaner meta output. Copy the parent's `content.php` into the child's `template-parts/` directory and add reading time in the entry footer area, next to the date. The function `ignites_child_reading_time()` should be called here if it's a single post or archive.

Key change — replace `ignites_entry_footer()` with a custom output that includes:
1. Post date: formatted as `j. F Y` (Latvian date format, e.g. "4. maijs 2026")
2. Reading time: `ignites_child_reading_time()`
3. Tags: existing `the_tags()` with the pill CSS class

---

## 6. Dark Mode JS (inline, in `functions.php`)

Inject via `wp_footer` or `wp_add_inline_script`:

```js
(function(){
  var html = document.documentElement;
  var btn = document.createElement('button');
  btn.setAttribute('data-theme-toggle', '');
  btn.setAttribute('aria-label', 'Mainīt tēmu');
  
  var saved = null;
  var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  var theme = saved || (prefersDark ? 'dark' : 'light');
  html.setAttribute('data-theme', theme);
  
  function sunIcon() {
    return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>';
  }
  function moonIcon() {
    return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
  }
  
  btn.innerHTML = theme === 'dark' ? sunIcon() : moonIcon();
  document.body.appendChild(btn);
  
  btn.addEventListener('click', function() {
    theme = theme === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', theme);
    btn.innerHTML = theme === 'dark' ? sunIcon() : moonIcon();
  });
})();
```

---

## 7. Things NOT to Change

- Do **not** modify any parent theme files
- Do **not** remove Bootstrap 5 — override its variables via CSS custom properties where needed
- Do **not** remove Linearicons — they power `.scroll-top` and the hamburger menu
- Do **not** change the nav menu registration or sidebar PHP logic
- Do **not** add jQuery-dependent scripts (ClassicPress includes jQuery but avoid new dependencies)

---

## 8. Quality Checklist Before Delivery

- [ ] `style.css` child theme header includes `Template: ignites`
- [ ] Both light **and** dark mode tokens defined and working
- [ ] Boska + Satoshi load from Fontshare (verify `api.fontshare.com/v2/css` URL)
- [ ] Archive page renders first post as hero (full-width, large title)
- [ ] Archive remaining posts in 2-column grid
- [ ] Single post has: reading progress bar, constrained 68ch column, large display title, styled blockquotes & code
- [ ] Tag links render as pills
- [ ] Dark mode toggle button: fixed bottom-right, sun/moon icon, defaults to system preference
- [ ] Reading time appears in post meta in Latvian: "X min lasīšana"
- [ ] Mobile (375px): single column, no overflow, touch targets ≥44px
- [ ] No pure `#000000` or `#ffffff` anywhere — use token values
- [ ] Zip produced as `ignites-child.zip`, uploadable directly to ClassicPress

---

## 9. Notes on ClassicPress Compatibility

ClassicPress 2.x is a fork of WordPress 4.9 with modern PHP support. It is **fully compatible** with:
- Standard `wp_enqueue_style` / `wp_enqueue_scripts` hooks
- Child theme `Template:` header convention
- All standard template parts (`get_template_part`, `get_header`, `get_footer`)
- `wp_body_open` action (supported since ClassicPress 1.2)
- Standard WP Loop functions (`have_posts`, `the_post`, `get_the_title`, etc.)

Do **not** use Block Editor (Gutenberg) specific APIs — ClassicPress uses the Classic Editor only.
