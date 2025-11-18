# Linearicons Icon Font Fix - Private/Incognito Mode Compatibility

## Problem
The `lnr lnr-chevron-up` icon (scroll-to-top button) was not visible in private/incognito browser mode due to ad blocker and font loading restrictions.

## Solutions Implemented

### 1. Font-Display: Swap
- Added `font-display: swap` to the @font-face declaration in `assets/css/linearicons.css`
- This improves font loading performance and ensures text remains visible during webfont load

### 2. Font File Renaming (Ad Blocker Resistance)
- Renamed all font files from `Linearicons-Free.*` to `lnr-webfont.*`
- Ad blockers often block files with names like "icon", "social", or recognizable icon font names
- New file names:
  - `lnr-webfont.eot`
  - `lnr-webfont.woff2`
  - `lnr-webfont.woff`
  - `lnr-webfont.ttf`
  - `lnr-webfont.svg`

### 3. Font Preloading
- Added font preload in `inc/ignites_styles_scripts.php`
- Preloads the WOFF2 font file for faster initial rendering
- Critical for ensuring the scroll-to-top icon displays immediately

### 4. CSS Fallback
- Added Unicode arrow fallback (`↑`) in `assets/css/linearicons.css`
- If the icon font fails to load, a standard Unicode arrow is displayed
- Ensures functionality is never lost even if font loading fails completely

## Files Modified

### 1. `assets/css/linearicons.css`
- Updated @font-face src URLs to reference renamed font files
- Added `font-display: swap`
- Added fallback CSS for `.lnr-chevron-up` with Unicode arrow

### 2. `inc/ignites_styles_scripts.php`
- Added `ignites_preload_fonts()` function
- Preloads `lnr-webfont.woff2` in the document head

### 3. Font Files Renamed
- All 5 Linearicons font files in `assets/fonts/` directory

## Testing

### Test in Multiple Modes
1. **Normal browsing mode** - Icon should display correctly
2. **Private/Incognito mode** - Icon should display correctly
3. **Private mode + Ad blocker** - Icon should display correctly (or show fallback)
4. **With browser DevTools** - Check Network tab for successful font loading

### Expected Results
- Font files load with 200 status code
- No console errors related to font loading
- Scroll-to-top button displays chevron-up icon (or Unicode arrow if font blocked)

## Alternative: SVG Icon (Future Enhancement)

If icon fonts continue to cause issues, consider switching to inline SVG icons:

### SVG Chevron-Up Implementation

**In `footer.php`, replace:**
```html
<div class="scroll-top">
    <span class="lnr lnr-chevron-up"></span>
</div>
```

**With:**
```html
<div class="scroll-top">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="chevron-up-icon">
        <polyline points="18 15 12 9 6 15"></polyline>
    </svg>
</div>
```

**Update `assets/css/main.css`:**
```css
.scroll-top svg {
    width: 22px;
    height: 22px;
    vertical-align: middle;
}
```

### Advantages of SVG Icons
- ✅ Never blocked by ad blockers
- ✅ Perfect rendering at any size
- ✅ Can be styled with CSS (color, size, stroke-width)
- ✅ No external file dependencies
- ✅ Works in all browsers and modes
- ✅ Smaller file size than font files
- ✅ Better accessibility

## Browser Compatibility

All fixes are compatible with:
- Chrome/Edge 77+
- Firefox 75+
- Safari 15.4+
- Opera 64+

The fallback Unicode arrow works in all browsers including IE11.

## Performance Impact

- Font preload improves initial rendering speed
- Font-display: swap prevents invisible text during font load
- Renamed files avoid ad blocker overhead
- Overall: **Improved performance and reliability**

## Debugging Tips

If icons still don't display:

1. **Check font file paths** - Use browser DevTools Network tab
2. **Clear browser cache** - Hard refresh (Ctrl+Shift+R or Cmd+Shift+R)
3. **Disable ad blockers temporarily** - To isolate the issue
4. **Check console for errors** - Look for CORS or loading errors
5. **Verify CSS is loaded** - Check Elements tab for computed styles

## Maintenance Notes

- Font file names should NOT be changed back to original names
- Keep the preload link in sync with the primary WOFF2 font file
- If adding new icons, ensure fallback CSS is added
- Test all changes in private/incognito mode before deploying

## Credits

- Fix implemented: 2025-11-11
- Compatibility tested with common ad blockers (uBlock Origin, AdBlock Plus)
- PHP 8.4 compatible
- WordPress 6.0+ compatible

---

*For issues or questions, check browser DevTools Console and Network tabs first.*
