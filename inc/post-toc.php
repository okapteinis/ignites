<?php
/**
 * Ignites Child — section navigation for single posts.
 *
 * @package Ignites_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Read a quoted or unquoted ID attribute from heading attributes. */
function ignites_child_parse_id_attribute( $attributes ) {
	if ( 1 !== preg_match( '/(^|\s)id\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+))/i', $attributes, $match ) ) {
		return array(
			'found' => false,
			'value' => '',
		);
	}

	$id = '';
	foreach ( array( 2, 3, 4 ) as $value_index ) {
		if ( isset( $match[ $value_index ] ) && '' !== $match[ $value_index ] ) {
			$id = html_entity_decode( $match[ $value_index ], ENT_QUOTES, get_bloginfo( 'charset' ) );
			break;
		}
	}
	return array(
		'found' => true,
		'value' => $id,
	);
}

/**
 * Add stable fragment IDs to article section headings and collect TOC entries.
 *
 * @param string $content Filtered post content.
 * @return string
 */
function ignites_child_add_post_toc_ids( $content ) {
	if ( is_admin() || is_feed() || ! is_singular( 'post' ) || ! in_the_loop() ) {
		return $content;
	}

	global $ignites_child_post_toc;
	$ignites_child_post_toc = array();
	$used_ids               = array();
	$reserved_heading_ids   = array();
	$excluded_heading_ids   = array();
	$has_eligible_heading   = false;
	if ( preg_match_all( '/<h([23])\b([^>]*)>(.*?)<\/h[23]>/isu', $content, $headings, PREG_SET_ORDER ) ) {
		foreach ( $headings as $heading ) {
			$heading_id = ignites_child_parse_id_attribute( $heading[2] );
			if ( '' !== $heading_id['value'] ) {
				$reserved_heading_ids[ $heading_id['value'] ] = true;
			}
			$title = ignites_child_normalize_heading_text( $heading[3] );
			if ( '' === $title || ignites_child_is_sources_heading( $title ) ) {
				if ( '' !== $heading_id['value'] ) {
					$excluded_heading_ids[ $heading_id['value'] ] = true;
				}
				continue;
			}
			$has_eligible_heading = true;
		}
	}
	if ( ! $has_eligible_heading ) {
		return $content;
	}
	if ( preg_match_all( '/<(?!h[23]\b)[a-z][a-z0-9:-]*\b[^>]*\s+id\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+))[^>]*>/isu', $content, $existing_ids, PREG_SET_ORDER ) ) {
		foreach ( $existing_ids as $existing_id_match ) {
			$existing_id = '';
			foreach ( array( 1, 2, 3 ) as $value_index ) {
				if ( isset( $existing_id_match[ $value_index ] ) && '' !== $existing_id_match[ $value_index ] ) {
					$existing_id = $existing_id_match[ $value_index ];
					break;
				}
			}
			$existing_id = html_entity_decode( $existing_id, ENT_QUOTES, get_bloginfo( 'charset' ) );
			if ( '' !== $existing_id ) {
				$used_ids[ $existing_id ] = true;
			}
		}
	}
	foreach ( array_keys( $excluded_heading_ids ) as $id ) {
		$used_ids[ $id ] = true;
	}
	$updated                    = preg_replace_callback(
		'/<h([23])\b([^>]*)>(.*?)<\/h[23]>/isu',
		function ( $match ) use ( &$used_ids, $reserved_heading_ids, &$ignites_child_post_toc ) {
			$level      = (int) $match[1];
			$attributes = $match[2];
			$inner      = $match[3];
			$title      = ignites_child_normalize_heading_text( $inner );

			if ( '' === $title || ignites_child_is_sources_heading( $title ) ) {
				return $match[0];
			}

			$heading_id = ignites_child_parse_id_attribute( $attributes );
			$id         = $heading_id['value'];
			$has_id     = $heading_id['found'];
			$generated_id = '' === $id;
			if ( '' === $id ) {
				$id = sanitize_title( $title );
			}
			if ( '' === $id ) {
				$id = 'section-' . ( count( $used_ids ) + 1 );
			}
			$base_id = $id;
			$suffix  = 2;
			while ( isset( $used_ids[ $id ] ) || ( $generated_id && isset( $reserved_heading_ids[ $id ] ) ) ) {
				$id = $base_id . '-' . $suffix;
				++$suffix;
			}
			$used_ids[ $id ] = true;

			if ( $has_id ) {
				$attributes = preg_replace_callback(
					'/(^|\s)id\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s"\'=<>`]+)/i',
					function ( $attribute ) use ( $id ) {
						return $attribute[1] . 'id="' . esc_attr( $id ) . '"';
					},
					$attributes,
					1
				);
			} else {
				$attributes .= ' id="' . esc_attr( $id ) . '"';
			}
			$ignites_child_post_toc[] = array(
				'level' => $level,
				'id'    => $id,
				'title' => $title,
			);

			return '<h' . $level . $attributes . '>' . $inner . '</h' . $level . '>';
		},
		$content
	);

	return null === $updated ? $content : $updated;
}
add_filter( 'the_content', 'ignites_child_add_post_toc_ids', 18 );

/** Render the reserved contents rail on wide single-post layouts. */
function ignites_child_post_toc() {
	global $ignites_child_post_toc;
	$items = is_array( $ignites_child_post_toc ) ? $ignites_child_post_toc : array();

	echo '<aside class="post-toc' . ( empty( $items ) ? ' is-empty' : '' ) . '"' . ( empty( $items ) ? ' aria-hidden="true"' : '' ) . '>';
	if ( ! empty( $items ) ) {
		echo '<nav aria-label="' . esc_attr__( 'Satura rādītājs', 'ignites-child' ) . '">';
		echo '<h2 class="post-toc-title">' . esc_html__( 'Satura rādītājs', 'ignites-child' ) . '</h2><ol>';
		foreach ( $items as $item ) {
			echo '<li class="post-toc-level-' . (int) $item['level'] . '"><a href="#' . esc_attr( $item['id'] ) . '">' . esc_html( $item['title'] ) . '</a></li>';
		}
		echo '</ol></nav>';
	}
	echo '</aside>';
}
