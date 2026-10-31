<?php
/**
 * Uninstall
 *
 * @package Bulk Media Register
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

global $wpdb;

/* For Single site */
if ( ! is_multisite() ) {
	delete_option( 'bulkmediaregister_notice' );
	delete_option( 'bulkmediaregister_cash' );
	$blogusers = get_users( array( 'fields' => array( 'ID' ) ) );
	foreach ( $blogusers as $user ) {
		delete_user_option( $user->ID, 'bulkmediaregister', false );
		delete_user_option( $user->ID, 'bulkmediaregister_files', false );
		delete_user_option( $user->ID, 'bulkmediaregister_output', false );
		delete_user_option( $user->ID, 'bulkmediaregister_files_break', false );
		delete_user_option( $user->ID, 'bulkmediaregister_dirs_break', false );
		delete_user_option( $user->ID, 'bulkmediaregister_lists_break', false );
		delete_user_option( $user->ID, 'bulkmediaregister_search_text', false );
		delete_user_option( $user->ID, 'bulkmediaregister_wpcron', false );
		delete_user_option( $user->ID, 'bulkmediaregister_wpcron_search_text', false );
		delete_user_option( $user->ID, 'bulkmediaregister_crondo', false );
		delete_user_option( $user->ID, 'bulkmediaregister_event_intervals', false );
	}
} else {
	/* For Multisite */
	$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->prefix}blogs" );
	$original_blog_id = get_current_blog_id();
	foreach ( $blog_ids as $blogid ) {
		switch_to_blog( $blogid );
		delete_option( 'bulkmediaregister_notice' );
		delete_option( 'bulkmediaregister_cash' );
		$blogusers = get_users(
			array(
				'blog_id' => $blogid,
				'fields' => array( 'ID' ),
			)
		);
		foreach ( $blogusers as $user ) {
			delete_user_option( $user->ID, 'bulkmediaregister', false );
			delete_user_option( $user->ID, 'bulkmediaregister_files', false );
			delete_user_option( $user->ID, 'bulkmediaregister_output', false );
			delete_user_option( $user->ID, 'bulkmediaregister_files_break', false );
			delete_user_option( $user->ID, 'bulkmediaregister_dirs_break', false );
			delete_user_option( $user->ID, 'bulkmediaregister_lists_break', false );
			delete_user_option( $user->ID, 'bulkmediaregister_search_text', false );
			delete_user_option( $user->ID, 'bulkmediaregister_wpcron', false );
			delete_user_option( $user->ID, 'bulkmediaregister_wpcron_search_text', false );
			delete_user_option( $user->ID, 'bulkmediaregister_crondo', false );
			delete_user_option( $user->ID, 'bulkmediaregister_event_intervals', false );
		}
	}
	switch_to_blog( $original_blog_id );
}
