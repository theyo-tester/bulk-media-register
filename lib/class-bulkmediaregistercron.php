<?php
/**
 * Bulk Media Register
 *
 * @package    Bulk Media Register
 * @subpackage BulkMediaRegisterCron Cron Main Functions
/*
	Copyright (c) 2020- Katsushi Kawamori (email : dodesyoswift312@gmail.com)
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; version 2 of the License.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$bulkmediaregistercron = new BulkMediaRegisterCron();

/** ==================================================
 * Main Functions
 */
class BulkMediaRegisterCron {

	/** ==================================================
	 * Construct
	 *
	 * @since 2.00
	 */
	public function __construct() {

		add_action( 'bulkmediaregister_cron', array( $this, 'crondo' ), 10, 1 );
		add_action( 'bulkmediaregister_cron_start', array( $this, 'cronstart' ), 10, 1 );
		add_action( 'bulkmediaregister_cron_stop', array( $this, 'cronstop' ), 10, 1 );
		register_deactivation_hook( plugin_dir_path( __DIR__ ) . 'bulkmediaregister.php', array( $this, 'cronallstop' ) );

		add_filter( 'bmr_cron_cli', array( $this, 'croncli' ), 10, 2 );
	}

	/** ==================================================
	 * Cron Start
	 *
	 * @param int $uid  uid.
	 * @since 2.00
	 */
	public function cronstart( $uid ) {

		$args = array( 'uid' => $uid );

		$schedule_name = get_user_option( 'bulkmediaregister_wpcron', $uid );

		$seconds = null;
		if ( ! empty( get_user_option( 'bulkmediaregister_event_intervals', $uid ) ) ) {
			$intervals_tbl = get_user_option( 'bulkmediaregister_event_intervals', $uid );
			if ( array_key_exists( $schedule_name, $intervals_tbl ) ) {
				$seconds = $intervals_tbl[ $schedule_name ]['interval'];
			}
		}
		if ( is_null( $seconds ) ) {
			$schedule_keys = wp_get_schedules();
			if ( array_key_exists( $schedule_name, $schedule_keys ) ) {
				$seconds = $schedule_keys[ $schedule_name ]['interval'];
			} else {
				return;
			}
		}

		if ( ! wp_next_scheduled( 'bulkmediaregister_cron', $args ) ) {
			wp_schedule_single_event( time() + $seconds, 'bulkmediaregister_cron', $args );
		}
	}

	/** ==================================================
	 * Cron Stop
	 *
	 * @param int $uid  uid.
	 * @since 2.00
	 */
	public function cronstop( $uid ) {

		$args = array( 'uid' => $uid );
		$unscheduled = wp_clear_scheduled_hook( 'bulkmediaregister_cron', $args );

		/* for deactivation */
		if ( ! $unscheduled ) {
			$crons = _get_cron_array();
			$bulk_arr = array_column( $crons, 'bulkmediaregister_cron' );
			if ( ! empty( $bulk_arr ) ) {
				$bulk_arr2 = array_keys( $bulk_arr[0] );
				$key = $bulk_arr2[0];
				$next = false;
				foreach ( $crons as $timestamp => $cron ) {
					if ( isset( $cron['bulkmediaregister_cron'][ $key ] ) ) {
						$next = $timestamp;
						break;
					}
				}
				if ( $next ) {
					unset( $crons[ $next ]['bulkmediaregister_cron'][ $key ] );
					if ( empty( $crons[ $next ]['bulkmediaregister_cron'] ) ) {
						unset( $crons[ $next ]['bulkmediaregister_cron'] );
					}
					if ( empty( $crons[ $next ] ) ) {
						unset( $crons[ $next ] );
					}
					_set_cron_array( $crons );
				}
			}
		}
	}

	/** ==================================================
	 * Cron All Stop
	 *
	 * @since 2.00
	 */
	public function cronallstop() {

		if ( ! is_multisite() ) {
			$blogusers = get_users( array( 'fields' => array( 'ID' ) ) );
			foreach ( $blogusers as $user ) {
				do_action( 'bulkmediaregister_cron_stop', $user->ID );
			}
		} else { /* For Multisite */
			global $wpdb;
			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->prefix}blogs" );
			$original_blog_id = get_current_blog_id();
			foreach ( $blog_ids as $blogid ) {
				$blogusers = get_users(
					array(
						'blog_id' => $blogid,
						'fields' => array( 'ID' ),
					)
				);
				foreach ( $blogusers as $user ) {
					do_action( 'bulkmediaregister_cron_stop', $user->ID );
				}
			}
			switch_to_blog( $original_blog_id );
		}
	}

	/** ==================================================
	 * Cron
	 *
	 * @param int $uid  uid.
	 * @since 2.00
	 */
	public function crondo( $uid ) {

		update_user_option( $uid, 'bulkmediaregister_crondo', true );

		$search_text = get_user_option( 'bulkmediaregister_wpcron_search_text', $uid );
		do_action( 'bmr_search_files', $uid, $search_text );
		$files = get_user_option( 'bulkmediaregister_files', $uid );
		if ( ! empty( $files ) ) {
			$start_time = time();
			$exe_stop_diff_time = intval( ini_get( 'max_execution_time' ) * 0.8 );
			foreach ( $files as $file ) {
				if ( time() - $start_time >= $exe_stop_diff_time ) {
					break;
				}
				do_action( 'bmr_regist', $file, $uid );
			}
			do_action( 'bmr_mail_register_message', $uid, true );
		}

		/* Next Schedule */
		do_action( 'bulkmediaregister_cron_start', $uid );

		delete_user_option( $uid, 'bulkmediaregister_crondo', false );
	}

	/** ==================================================
	 * Cron Commandline
	 *
	 * @param int    $uid  uid.
	 * @param string $search_text  search_text.
	 * @since 2.00
	 */
	public function croncli( $uid, $search_text ) {

		do_action( 'bmr_search_files', $uid, $search_text );
		$files = get_user_option( 'bulkmediaregister_files', $uid );
		$outputs = array();
		if ( ! empty( $files ) ) {
			foreach ( $files as $file ) {
				$outputs[] = apply_filters( 'bmr_regist', $file, $uid );
			}
			do_action( 'bmr_mail_register_message', $uid, true );
		}

		return $outputs;
	}
}
