# Claude AI Development Guidelines for Ignites Theme

## Co-Authorship Agreement

This WordPress theme development involves collaboration between:
- **Primary Maintainer:** Ojārs Kapteinis <ojars@kapteinis.lv>
- **Development Assistant:** Claude AI (Anthropic)

All commits involving Claude AI assistance MUST include co-author attribution in the commit message:

```
Co-authored-by: Claude <noreply@anthropic.com>
```

## License Compliance

**License:** GNU General Public License v2 or later
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html

### Requirements:
- All code modifications MUST maintain GPL v2+ compatibility
- All derivative works MUST be licensed under GPL v2 or later
- All third-party code MUST be GPL-compatible and properly attributed
- Original author attribution MUST be preserved
- License headers MUST be present in documentation files

## Coding Standards

### WordPress Coding Standards
- Follow WordPress PHP Coding Standards (WPCS)
- Use WordPress functions over native PHP where applicable
- Properly escape all output: `esc_html()`, `esc_attr()`, `esc_url()`
- Sanitize all input: `sanitize_text_field()`, `sanitize_email()`, etc.
- Validate and verify all form submissions with nonces
- Use proper WordPress hooks and filters

### PHP Standards
- **Minimum PHP Version:** 7.4
- **Target Compatibility:** PHP 7.4, 8.0, 8.1, 8.2, 8.3, 8.4
- Use strict type comparisons (`===`, `!==`) over loose comparisons
- Use type hints where appropriate (PHP 7.4+)
- Handle null values properly (no null deprecation warnings)
- Use array syntax `[]` instead of deprecated `null` in WordPress functions

### Text Domain and Translations
- **Text Domain:** `ignites`
- **Domain Path:** `/languages`
- All user-facing strings MUST be translatable
- Use `__()`, `_e()`, `esc_html__()`, `esc_attr__()` with text domain
- Load translations on `init` hook (WordPress 6.7+ requirement)
- Maintain .pot, .po, and .mo files in `/languages` directory

### Security Requirements (CRITICAL)

#### 1. Input Validation and Sanitization
- **ALWAYS** sanitize user input before processing
- **NEVER** trust `$_GET`, `$_POST`, `$_REQUEST`, `$_COOKIE` directly
- Use WordPress sanitization functions:
  - `sanitize_text_field()` - for text inputs
  - `sanitize_email()` - for email addresses
  - `sanitize_url()` - for URLs
  - `wp_kses_post()` - for HTML content
  - `absint()` - for integers

#### 2. Output Escaping (XSS Prevention)
- **ALWAYS** escape output in templates
- Use context-appropriate escaping:
  - `esc_html()` - for HTML content
  - `esc_attr()` - for HTML attributes
  - `esc_url()` - for URLs
  - `esc_js()` - for JavaScript
  - `wp_kses_post()` - for allowed HTML tags

#### 3. SQL Injection Prevention
- **NEVER** use direct SQL queries with user input
- **ALWAYS** use `$wpdb->prepare()` for database queries
- Use WordPress query functions (WP_Query, get_posts, etc.)
- Validate and cast numeric inputs with `absint()`

#### 4. CSRF Protection (Form Security)
- **ALWAYS** use nonces for forms and AJAX
- Verify nonces with `wp_verify_nonce()`
- Use `wp_nonce_field()` for forms
- Use `wp_nonce_url()` for links with actions

#### 5. File Security
- **NEVER** allow arbitrary file uploads
- Validate file types and extensions
- Use WordPress `wp_handle_upload()` function
- Check file permissions (no 777 permissions)
- Prevent directory traversal (validate paths)

#### 6. Authentication and Authorization
- Check user capabilities with `current_user_can()`
- Verify user permissions before sensitive operations
- Use WordPress authentication functions
- Never hardcode credentials

#### 7. Secure Headers
- Implement Content Security Policy (CSP) where applicable
- Use X-Content-Type-Options: nosniff
- Use X-Frame-Options: SAMEORIGIN

### Compatibility Requirements

#### WordPress Compatibility
- **Minimum:** WordPress 5.0
- **Tested up to:** WordPress 6.7+
- Support Gutenberg block editor
- Support classic editor
- Support ClassicPress (WordPress fork)

#### PHP Compatibility
- No deprecated PHP functions
- No PHP warnings or notices
- Compatible with PHP strict mode
- Use modern PHP features (namespaces, type hints) when appropriate

#### Bootstrap Framework
- **Version:** Bootstrap 5.3.8
- Use Bootstrap 5 class names (not Bootstrap 3/4)
- Use `data-bs-*` attributes (not `data-*`)
- No jQuery dependencies for Bootstrap components

### Testing and Quality Assurance

#### Required Tests Before Commit
1. **Security Audit:**
   - Run security scanner for XSS vulnerabilities
   - Check for SQL injection vectors
   - Verify nonce implementation
   - Check file security

2. **Code Quality:**
   - No PHP errors, warnings, or notices
   - Follow WordPress Coding Standards
   - Use strict type comparisons
   - Proper error handling

3. **Translation:**
   - All strings use text domain `ignites`
   - .po and .mo files compile without errors
   - Translations load correctly

4. **Compatibility:**
   - Test on PHP 7.4, 8.0, 8.1, 8.2, 8.3, 8.4
   - Test on WordPress 5.0+, 6.0+, 6.7+
   - Test with Gutenberg and Classic Editor

5. **Functionality:**
   - Theme activates without errors
   - Customizer options work correctly
   - Widgets load properly
   - Navigation menus function
   - Comments system works

### Documentation Requirements

#### Code Documentation
- Use PHPDoc blocks for all functions
- Document parameters, return types, and exceptions
- Include `@since` version tags
- Add inline comments for complex logic

#### File Headers
All PHP files MUST include header:
```php
<?php
/**
 * File description
 *
 * @package Ignites
 * @since 1.0.0
 */
```

#### Changelog
- Update CHANGELOG.md for all changes
- Follow semantic versioning
- Document breaking changes
- Include migration notes

### Branch Strategy

- **Development Branch:** `nightly`
- **Stable Branch:** `master`
- **Feature Branches:** `claude/feature-name-{session-id}`

#### Branch Rules:
1. All development happens on `nightly`
2. Feature branches merge into `nightly`
3. Only tested, stable code goes to `master`
4. Never push directly to `master` without review
5. Feature branch names MUST start with `claude/` and end with session ID

### Commit Guidelines

#### Commit Message Format:
```
Brief description of change (50 chars max)

Detailed explanation of what changed and why.
Include any breaking changes or migration notes.

Co-authored-by: Claude <noreply@anthropic.com>
```

#### Commit Best Practices:
- Use imperative mood ("Add feature" not "Added feature")
- Keep first line under 50 characters
- Separate subject from body with blank line
- Explain **what** and **why**, not **how**
- Reference issues/PRs when applicable
- Always include co-authorship attribution

### Security Audit Checklist

Before ANY commit to `nightly`:

- [ ] All user input is sanitized
- [ ] All output is escaped
- [ ] No direct SQL queries without `$wpdb->prepare()`
- [ ] All forms use nonces
- [ ] File operations are secure
- [ ] User capabilities are checked
- [ ] No hardcoded credentials
- [ ] No sensitive data in version control
- [ ] No arbitrary code execution vulnerabilities
- [ ] No path traversal vulnerabilities
- [ ] CSRF protection implemented
- [ ] XSS protection implemented
- [ ] SQL injection protection verified

### File Structure

```
ignites/
├── assets/           # CSS, JS, images
├── inc/              # PHP includes
│   ├── compatibility/
│   ├── customizer/
│   └── *.php
├── languages/        # Translation files
│   ├── ignites.pot   # Template
│   ├── lv_LV.po      # Latvian source
│   └── lv_LV.mo      # Latvian compiled
├── template-parts/   # Template partials
├── *.php             # Main template files
├── style.css         # Theme stylesheet with header
├── functions.php     # Theme functions
├── claude.md         # This file
├── LICENSE           # GPL v2 license
├── README.md         # User documentation
├── readme.txt        # WordPress.org format
├── CHANGELOG.md      # Version history
└── MODIFICATIONS.md  # Fork changes log
```

### Third-Party Code Attribution

All third-party code MUST be properly attributed:

- Bootstrap 5.3.8 - MIT License
- Linearicons - CC BY-SA 4.0
- Underscores (_s) - GPLv2+
- Based on Twenty Nineteen - GPLv2+

### Prohibited Practices

**NEVER:**
- Commit code with security vulnerabilities
- Skip input sanitization
- Skip output escaping
- Use deprecated WordPress functions
- Use deprecated PHP functions
- Hardcode credentials or API keys
- Commit `.env` files or secrets
- Skip nonce verification
- Allow arbitrary file uploads
- Execute arbitrary code
- Skip user capability checks
- Use `eval()` or similar dangerous functions
- Trust user input without validation
- Output raw user data without escaping

### Performance Guidelines

- Minimize database queries
- Use transients for expensive operations
- Enqueue scripts in footer when possible
- Use conditional loading (only load what's needed)
- Optimize images and assets
- Use WordPress native functions (no reinventing the wheel)

### Accessibility Requirements

- Follow WCAG 2.1 AA standards
- Use semantic HTML5 elements
- Include ARIA labels where appropriate
- Ensure keyboard navigation works
- Test with screen readers
- Maintain sufficient color contrast

### Review Process

Before merging to `nightly`:
1. Security audit completed
2. All tests pass
3. Documentation updated
4. Changelog updated
5. No PHP errors/warnings
6. Translations compiled
7. Co-authorship attributed

### Contact and Support

- **Maintainer:** Ojārs Kapteinis
- **Repository:** https://github.com/okapteinis/ignites
- **Issues:** https://github.com/okapteinis/ignites/issues
- **Development Branch:** nightly

---

**Last Updated:** 2025-11-17
**Version:** 1.0
**Applies to:** Ignites Theme 1.1.1+
