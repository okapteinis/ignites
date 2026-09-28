<?php
/** ID parsing and collision index for single-post section navigation. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ignites_child_parse_id_attribute( $attributes ) {
	if ( 1 !== preg_match( '/(^|\s)id\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+))/i', $attributes, $match ) ) {
		return array( false, '' );
	}
	foreach ( array( 2, 3, 4 ) as $index ) {
		if ( isset( $match[ $index ] ) && '' !== $match[ $index ] ) {
			return array( true, html_entity_decode( $match[ $index ], ENT_QUOTES, get_bloginfo( 'charset' ) ) );
		}
	}
	return array( true, '' );
}

function ignites_child_post_toc_id_index( $content ) {
	$used_ids             = array();
	$reserved_heading_ids = array();
	$excluded_heading_ids = array();
	$heading_data         = array();
	$has_eligible_heading = false;
	if ( preg_match_all( '/<h([23])\b([^>]*)>(.*?)<\/h[23]>/isu', $content, $headings, PREG_SET_ORDER ) ) {
		foreach ( $headings as $heading ) {
			list( $has_id, $id ) = ignites_child_parse_id_attribute( $heading[2] );
			if ( '' !== $id ) {
				$reserved_heading_ids[ $id ] = true;
			}
			$title = ignites_child_normalize_heading_text( $heading[3] );
			$eligible = '' !== $title && ! ignites_child_is_sources_heading( $title );
			$heading_data[] = array(
				'has_id'   => $has_id,
				'id'       => $id,
				'title'    => $title,
				'eligible' => $eligible,
			);
			if ( ! $eligible ) {
				if ( '' !== $id ) {
					$excluded_heading_ids[ $id ] = true;
				}
				continue;
			}
			$has_eligible_heading = true;
		}
	}
	if ( ! $has_eligible_heading ) {
		return array( false, array(), array(), array() );
	}

	if ( preg_match_all( '/<(?!h[23]\b)[a-z][a-z0-9:-]*\b[^>]*\s+id\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+))[^>]*>/isu', $content, $matches, PREG_SET_ORDER ) ) {
		foreach ( $matches as $match ) {
			foreach ( array( 1, 2, 3 ) as $index ) {
				if ( isset( $match[ $index ] ) && '' !== $match[ $index ] ) {
					$id = html_entity_decode( $match[ $index ], ENT_QUOTES, get_bloginfo( 'charset' ) );
					$used_ids[ $id ] = true;
					break;
				}
			}
		}
	}
	foreach ( array_keys( $excluded_heading_ids ) as $id ) {
		$used_ids[ $id ] = true;
	}
	return array( true, $used_ids, $reserved_heading_ids, $heading_data );
}
