# Modifications to Ignites Theme

## PHP 8.4 Compatibility Update (v1.0.11)

**Date:** October 31, 2025
**Contributor:** Ojārs Kapteinis <ojars@kapteinis.lv>

---

## License Notice

This modified version of the Ignites WordPress theme is a derivative work based on the original Ignites theme by Fahem Ahmed, which is licensed under the GNU General Public License v2 or later.

**In accordance with the GPL license, these modifications are also licensed under:**
**GNU General Public License v2 or later**

You are free to:
- Use this modified theme for any purpose
- Study and modify the code
- Distribute the original or modified versions
- Distribute modified versions under the same GPL v2+ license

See: https://www.gnu.org/licenses/gpl-2.0.html

---

## Original Theme

- **Original Theme:** Ignites by Fahem Ahmed
- **Original Author URI:** http://dopetheme.com
- **Original Theme URI:** http://demos.dopetheme.com/wp/ignites
- **Original License:** GNU General Public License v2 or later
- **Original Version:** 1.0.10.1

---

## Modifications Made

### Version 1.0.11 - PHP 8.4 Compatibility Update

**Modified by:** Claude Code (Anthropic)
**Contributor:** Ojārs Kapteinis <ojars@kapteinis.lv>

#### Files Modified (5 files):

1. **inc/ignites_styles_scripts.php**
   - Replaced 6 instances of `null` with `[]` in wp_enqueue_style() calls
   - Fixes PHP 8.1+ TypeError when passing null to non-nullable array parameters
   - Lines modified: 7, 8, 9, 10, 11, 27

2. **inc/customizer/customizer.php**
   - Replaced deprecated `WP_Customize_Color_Control` class with array-based control
   - Ensures compatibility with WordPress 6.5+
   - Lines modified: 25-34

3. **inc/custom-functions.php**
   - Updated 4 loose comparisons to strict equality (`==` → `===`, `!=` → `!==`)
   - Updated 2 Bootstrap 3 classes to Bootstrap 4 (`pull-left` → `float-start`)
   - Improves type safety and modernizes CSS classes
   - Lines modified: 41, 42, 77, 95, 96, 130

4. **style.css**
   - Version: 1.0.10.1 → 1.0.11
   - Requires PHP: 5.2.4 → 7.4
   - Tested up to: 5.8 → 6.7

5. **readme.txt**
   - Version: 1.0.10.1 → 1.0.11
   - Requires PHP: 5.2 → 7.4
   - Tested up to: 5.8 → 6.7
   - Added changelog entry for v1.0.11

---

## Compatibility

- **PHP Support:** 7.4, 8.0, 8.1, 8.2, 8.3, 8.4
- **WordPress:** 5.0+
- **Tested with:** WordPress 6.7 and PHP 8.4
- **Backward Compatible:** Yes (PHP 7.4+)
- **Breaking Changes:** None

---

## Technical Details

### Critical Fixes
- ✅ Fixed deprecated WP_Customize_Color_Control usage
- ✅ Fixed null parameter TypeErrors in WordPress enqueue functions
- ✅ Improved type safety with strict comparisons
- ✅ Modernized Bootstrap CSS classes

### Code Quality Improvements
- Stricter type checking (=== instead of ==)
- Better WordPress API compliance
- Modern Bootstrap 4 class names
- PHP 8.4 ready

---

## Credits

**Original Theme Author:** Fahem Ahmed
**Modifications Contributor:** Ojārs Kapteinis
**Automated Analysis & Code Updates:** Claude Code by Anthropic

---

## No Warranty

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

---

## Source Code

The complete source code for this modified version is available at:
https://github.com/okapteinis/ignites/tree/nightly

Original source code available at:
https://github.com/okapteinis/ignites/tree/master

---

**Last Updated:** October 31, 2025
