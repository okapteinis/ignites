<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
