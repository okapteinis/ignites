<?php
/**
 * Ignites Child — accessible citation sidenotes for long-form posts.
 *
 * Existing posts keep their hand-authored source list. On singular post views
 * only, numbered markers before the sources heading are supplemented with
 * responsive sidenotes. Feeds, excerpts, and newsletter content are untouched.
 *
 * @package Ignites_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Convert numbered citation markers into responsive sidenote markup.
 *
 * @param string $content Filtered post content.
 * @return string
 */
function ignites_child_add_sidenotes( $content ) {
	if ( is_admin() || is_feed() || ! is_singular( 'post' ) || ! in_the_loop() ) {
		return $content;
	}

	if ( ! preg_match( '/<h2\b[^>]*>\s*(?:Avoti\s+un\s+piezīmes|Sources\s+and\s+notes)\s*<\/h2>/iu', $content, $heading, PREG_OFFSET_CAPTURE ) ) {
		return $content;
	}

	$body_before_sources = substr( $content, 0, $heading[0][1] );
	$sources_section     = substr( $content, $heading[0][1] );
	if ( ! preg_match( '/<ol\b[^>]*>(.*?)<\/ol>/is', $sources_section, $list, PREG_OFFSET_CAPTURE ) ) {
		return $content;
	}

	$source_items = array();
	if ( preg_match_all( '/<li\b[^>]*>(.*?)<\/li>/is', $list[1][0], $items ) ) {
		foreach ( $items[1] as $index => $item ) {
			$source_items[ $index + 1 ] = wp_kses_post( trim( $item ) );
		}
	}
	if ( empty( $source_items ) ) {
		return $content;
	}

	$occurrences = array();
	$enhanced    = preg_replace_callback(
		'/\[(\d+)\]/u',
		function ( $match ) use ( $source_items, &$occurrences ) {
			$number = (int) $match[1];
			if ( ! isset( $source_items[ $number ] ) ) {
				return $match[0];
			}

			$occurrences[ $number ] = isset( $occurrences[ $number ] ) ? $occurrences[ $number ] + 1 : 1;
			$instance = $occurrences[ $number ];
			$id       = 'sidenote-' . get_the_ID() . '-' . $number . '-' . $instance;
			$label    = sprintf( __( 'Piezīme %d', 'ignites-child' ), $number );

			return '<label class="sidenote-number" for="' . esc_attr( $id ) . '" data-note="' . esc_attr( $number ) . '"><span class="screen-reader-text">' . esc_html( $label ) . '</span></label>'
				. '<input class="margin-toggle" type="checkbox" id="' . esc_attr( $id ) . '" aria-label="' . esc_attr( $label ) . '">'
				. '<span class="sidenote" role="note">' . $source_items[ $number ] . '</span>';
		},
		$body_before_sources
	);

	if ( null === $enhanced || $enhanced === $body_before_sources ) {
		return $content;
	}

	// The bottom list remains the canonical mobile, print, RSS, and fallback view.
	$sources_section = preg_replace( '/<h2\b([^>]*)>/i', '<h2 class="sidenote-sources-heading"$1>', $sources_section, 1 );
	$sources_section = preg_replace( '/<ol\b([^>]*)>/i', '<ol class="sidenote-sources"$1>', $sources_section, 1 );
	return $enhanced . $sources_section;
}
add_filter( 'the_content', 'ignites_child_add_sidenotes', 20 );
