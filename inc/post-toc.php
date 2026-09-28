<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ignites_child_add_post_toc_ids( $content ) {
	if ( is_admin() || is_feed() || ! is_singular( 'post' ) || ! in_the_loop() ) {
		return $content;
	}
	global $ignites_child_post_toc;
	$ignites_child_post_toc = array();
	list( $has_sections, $used_ids, $reserved_heading_ids, $heading_data ) = ignites_child_post_toc_id_index( $content );
	if ( ! $has_sections ) {
		return $content;
	}
	$heading_index = 0;
	$updated = preg_replace_callback(
		'/<h([23])\b([^>]*)>(.*?)<\/h[23]>/isu',
		function ( $match ) use ( &$used_ids, $reserved_heading_ids, $heading_data, &$heading_index, &$ignites_child_post_toc ) {
			$level      = (int) $match[1];
			$attributes = $match[2];
			$inner      = $match[3];
			$heading    = $heading_data[ $heading_index++ ];
			$title      = $heading['title'];
			if ( ! $heading['eligible'] ) {
				return $match[0];
			}

			$has_id      = $heading['has_id'];
			$id          = $heading['id'];
			$generated_id = '' === $id;
			if ( $generated_id ) {
				$id = sanitize_title( $title );
			}
			if ( '' === $id ) {
				$id = 'section-' . ( count( $used_ids ) + 1 );
			}
			$base_id = $id;
			$suffix  = 2;
			while ( isset( $used_ids[ $id ] ) || ( $generated_id && isset( $reserved_heading_ids[ $id ] ) ) ) {
				$id = $base_id . '-' . $suffix++;
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
			$ignites_child_post_toc[] = array( 'level' => $level, 'id' => $id, 'title' => $title );
			return '<h' . $level . $attributes . '>' . $inner . '</h' . $level . '>';
		},
		$content
	);
	return null === $updated ? $content : $updated;
}
add_filter( 'the_content', 'ignites_child_add_post_toc_ids', 18 );
