<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

function tlgb_uninstall_plugin() {
	$options = get_option( 'tlgb_options', [] );
	$is_delete_data = isset( $options['delete_data_on_uninstall'] ) ? $options['delete_data_on_uninstall'] : false;

	if ( ! $is_delete_data ) {
		return;
	}

	while ( true ) {
		$post_ids = get_posts( [
			'post_type'      => 'timeline_block',
			'post_status'    => 'any',
			'posts_per_page' => 100,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		] );

		if ( empty( $post_ids ) ) {
			break;
		}

		foreach ( $post_ids as $post_id ) {
			wp_delete_post( $post_id, true );
		}
	}
	delete_option( 'tlgb_options' );
} 

tlgb_uninstall_plugin();
