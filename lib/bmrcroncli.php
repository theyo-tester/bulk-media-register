<?php
/**
 * Bulk Media Register
 *
 * @package    Bulk Media Register
 * @subpackage BulkMediaRegisterCron Command line bmrcroncli.php
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

list( $abspath, $pluginpath ) = explode( 'wp-content', __DIR__, 2 );
$wp_load_path = $abspath . 'wp-load.php';

/** ==================================================
 * Path for wp-load.php
 *
 * If you cannot get the path to $wp_load_path,
 * please describe it directly below.
 */
require_once $wp_load_path;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** ==================================================
 * Cron Commandline
 *
 * @param int    $uid  User ID.
 * @param string $search_text  search_text.
 * @since 2.00
 */
$uid = get_option( 'bulkmediaregister_wpcron_userid', 1 );
$search_text = get_user_option( 'bulkmediaregister_wpcron_search_text', $uid );
$outputs = apply_filters( 'bmr_cron_cli', $uid, $search_text );
