<?php
/**
 * Ignites Child — search form (Latvian, theme-styled).
 *
 * Overrides the parent's white Bootstrap form-control + linearicon span.
 * Uses our --color-* tokens via the existing .search-form CSS so the
 * input is dark in dark mode, warm in light mode, with the teal
 * accent on focus.
 *
 * @package Ignites_Child
 */
?>
<form method="get" class="search-form" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="search-field"><?php esc_html_e( 'Meklēt', 'ignites-child' ); ?></label>
	<input id="search-field" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Meklēt…', 'ignites-child' ); ?>">
	<button type="submit" class="search-submit"><?php esc_html_e( 'Meklēt', 'ignites-child' ); ?></button>
</form>
