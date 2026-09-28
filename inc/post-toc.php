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
	list( $has_sections, $used_ids, $reserved_heading_ids ) = ignites_child_post_toc_id_index( $content );
	if ( ! $has_sections ) {
		return $content;
	}
	$updated = preg_replace_callback(
		'/<h([23])\b([^>]*)>(.*?)<\/h[23]>/isu',
		function ( $match ) use ( &$used_ids, $reserved_heading_ids, &$ignites_child_post_toc ) {
			$level      = (int) $match[1];
			$attributes = $match[2];
			$inner      = $match[3];
			$title      = ignites_child_normalize_heading_text( $inner );
			if ( '' === $title || ignites_child_is_sources_heading( $title ) ) {
				return $match[0];
			}

			list( $has_id, $id ) = ignites_child_parse_id_attribute( $attributes );
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

function ignites_child_post_toc() {
	global $ignites_child_post_toc;
	$items = is_array( $ignites_child_post_toc ) ? $ignites_child_post_toc : array();
	echo '<aside class="post-toc' . ( $items ? '' : ' is-empty' ) . '"' . ( $items ? '' : ' aria-hidden="true"' ) . '>';
	if ( $items ) {
		echo '<nav aria-label="' . esc_attr__( 'Satura rādītājs', 'ignites-child' ) . '">';
		echo '<h2 class="post-toc-title">' . esc_html__( 'Satura rādītājs', 'ignites-child' ) . '</h2><ol>';
		foreach ( $items as $item ) {
			echo '<li class="post-toc-level-' . (int) $item['level'] . '"><a href="#' . esc_attr( $item['id'] ) . '">' . esc_html( $item['title'] ) . '</a></li>';
		}
		echo '</ol></nav>';
	}
	echo '</aside>';
}
