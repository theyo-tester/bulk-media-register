<?php
/**
 * Bulk Media Register Wpcron
 *
 * @package    Bulk Media Register
 * @subpackage BulkMediaRegisterCronAdmin Manage functions
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

$bulkmediaregistercronadmin = new BulkMediaRegisterCronAdmin();

/** ==================================================
 * Manage Functions
 */
class BulkMediaRegisterCronAdmin {

	/** ==================================================
	 * Construct
	 *
	 * @since 2.00
	 */
	public function __construct() {

		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'cron_schedules', array( $this, 'bulkmediaregister_add_intervals' ) );
		add_action( 'bmr_cron_page', array( $this, 'cron_admin_page' ), 10, 1 );
	}

	/** ==================================================
	 * Intervals
	 *
	 * @param string $schedules  schedules.
	 * @return array $schedules
	 * @since 2.00
	 */
	public function bulkmediaregister_add_intervals( $schedules ) {

		$schedules = get_user_option( 'bulkmediaregister_event_intervals', get_current_user_id() );
		if ( empty( $schedules ) ) {
			$schedules = array();
		}

		return $schedules;
	}

	/** ==================================================
	 * Wp Cron Admin page
	 *
	 * @param int $uid  uid.
	 * @since 2.00
	 */
	public function cron_admin_page( $uid ) {

		$this->options_updated();

		$schedule_name = get_user_option( 'bulkmediaregister_wpcron', $uid );
		$search_text = get_user_option( 'bulkmediaregister_wpcron_search_text', $uid );
		$uid_option = get_option( 'bulkmediaregister_wpcron_userid', 1 );

		$scriptname = admin_url( 'admin.php?page=bulkmediaregister-wpcron' );

		?>
		<details style="margin-bottom: 5px;" open>
		<summary style="cursor: pointer; padding: 10px; border: 1px solid #ddd; background: #f4f4f4; color: #000;"><strong><?php esc_html_e( 'Scheduling', 'bulk-media-register' ); ?></strong></summary>
		<div style="display: block;padding:5px 5px">
		<table border="0" style="display:block;padding:5px 10px">
		<tr>
		<th align="left">
		<?php esc_html_e( 'Interval', 'bulk-media-register' ); ?>
		</th>
		<?php $this->bulkmediaregister_add_schedule_head_html( $scriptname ); ?>
		</tr>
		<?php
		$schedule_keys = wp_get_schedules();
		$count_schedules = 0;
		foreach ( $schedule_keys as $schedule_key => $schedule_values ) {
			?>
			<tr>
			<td><input type="radio" name="bulkmediaregister_cron_schedule" form="bulkmediaregister_schedule_form" value="<?php echo esc_attr( $schedule_key ); ?>" <?php checked( $schedule_key, $schedule_name ); ?>>
			<?php echo esc_html( $schedule_values['display'] ); ?></td>
			<?php
			$this->bulkmediaregister_add_schedule_html( $count_schedules, $schedule_key, count( $schedule_keys ) );
			?>
			</tr>
			<?php
			++$count_schedules;
		}
		?>
		</table>
		<form method="post" id="bulkmediaregister_schedule_form" action="<?php echo esc_url( $scriptname ); ?>" />
		<?php wp_nonce_field( 'bmr_schedule', 'bulk_media_register_schedule' ); ?>
		<?php submit_button( __( 'Save Interval', 'bulk-media-register' ), 'large', 'save_interval', false ); ?>
		</form>
		</div>
		</details>

		<details style="margin-bottom: 5px;" open>
		<summary style="cursor: pointer; padding: 10px; border: 1px solid #ddd; background: #f4f4f4; color: #000;"><strong><?php esc_html_e( 'Filter by text', 'bulk-media-register' ); ?></strong></summary>
		<div style="display: block;padding:5px 5px">
		<form method="post" action="<?php echo esc_url( $scriptname ); ?>" />
		<?php wp_nonce_field( 'bmr_cron_search_text', 'bulk_media_register_cron_search_text' ); ?>
		<input type="text" name="cron_search_text" value="<?php echo esc_attr( $search_text ); ?>">
		<?php submit_button( __( 'Save', 'bulk-media-register' ), 'large', 'save_search_text', false ); ?>
		</form>
		</div>
		</details>

		<details style="margin-bottom: 5px;" open>
		<summary style="cursor: pointer; padding: 10px; border: 1px solid #ddd; background: #f4f4f4; color: #000;"><strong><?php esc_html_e( 'Cron Event', 'bulk-media-register' ); ?></strong></summary>
		<div style="display: block;padding:5px 5px">
		<p class="description">
		<?php esc_html_e( 'If you want to register the command line script to the server\'s Cron, do not start it.', 'bulk-media-register' ); ?>
		</p>
		<form method="post" action="<?php echo esc_url( $scriptname ); ?>" />
		<?php
		wp_nonce_field( 'bmr_crondo', 'bulk_media_register_crondo' );
		$args = array( 'uid' => $uid );
		if ( wp_next_scheduled( 'bulkmediaregister_cron', $args ) ) {
			?>
			<div><?php esc_html_e( 'Next run', 'bulk-media-register' ); ?> : 
			<?php echo esc_html( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', wp_next_scheduled( 'bulkmediaregister_cron', $args ) ) ) ); ?>
			</div>
			<?php
			submit_button( __( 'Stop Cron', 'bulk-media-register' ), 'primary', 'stop_cron', false );
		} elseif ( get_user_option( 'bulkmediaregister_crondo', $uid ) ) {
			?>
				<strong style="color: red;">
				<?php echo esc_html_e( 'Running Cron event.', 'bulk-media-register' ); ?>
				</strong>
				<?php
		} else {
			submit_button( __( 'Start Cron', 'bulk-media-register' ), 'primary', 'start_cron', false );
		}
		?>
		</form>
		</div>
		</details>

		<details style="margin-bottom: 5px;">
		<summary style="cursor: pointer; padding: 10px; border: 1px solid #ddd; background: #f4f4f4; color: #000;"><strong><?php esc_html_e( 'Command line', 'bulk-media-register' ); ?></strong></summary>
		<div style="display: block;padding:5px 5px">
			<p class="description">
			<?php esc_html_e( 'On the command line, you can run the following script. In the following cases, the path to php must be set. If you have not, specify the full path to php.', 'bulk-media-register' ); ?>
			</p>
			<?php
			$cl_scriptname = 'php ' . wp_normalize_path( __DIR__ . '/bmrcroncli.php' );
			?>
			<input type="text" style="width: 100%;" readonly="readonly" value="<?php echo esc_html( $cl_scriptname ); ?>">
			<hr>
			<p class="description">
			<?php esc_html_e( 'On the command line, you have to specify the user ID. Please set bellow.', 'bulk-media-register' ); ?>
			</p>
			<table border="0" style="display:block;padding:5px 10px">
			<tr>
			<th></th>
			<th align="left">
			<?php esc_html_e( 'User', 'bulk-media-register' ); ?>
			</th>
			<th align="right">ID</th>
			</tr>
			<form method="post" action="<?php echo esc_url( $scriptname ); ?>" />
			<?php wp_nonce_field( 'bmr_cron_userid', 'bulk_media_register_cron_userid' ); ?>
			<?php
			$users = get_users(
				array(
					'orderby' => 'nicename',
					'order' => 'ASC',
				)
			);
			foreach ( $users as $user ) {
				if ( user_can( $user->ID, 'upload_files' ) ) {
					?>
					<tr>
					<td><input type="radio" name="cron_userid" value="<?php echo esc_attr( $user->ID ); ?>" 
					<?php
					if ( $uid_option == $user->ID ) {
						echo 'checked';
					}
					?>
					></td>
					<td><?php echo esc_html( $user->display_name ); ?></td>
					<td align="right"><?php echo esc_html( $user->ID ); ?></td>
					</tr>
					<?php
				}
			}
			?>
			</table>
			<?php submit_button( __( 'Save', 'bulk-media-register' ), 'large', 'save_userid', false ); ?>
			</form>
		</div>
		</details>

		<?php
	}

	/** ==================================================
	 * Schedule add & delete form
	 *
	 * @param string $scriptname  scriptname.
	 * @since 2.00
	 */
	private function bulkmediaregister_add_schedule_head_html( $scriptname ) {

		?>
		<th align="left">
		<form method="post" id="bulkmediaregister_settings_schedule_delete_form" action="<?php echo esc_url( $scriptname ); ?>" />
		<?php wp_nonce_field( 'bmr_add_schedule_delete', 'bulk_media_register_add_schedule_delete' ); ?>
		<?php submit_button( __( 'Delete', 'bulk-media-register' ), 'large', 'delete_interval', false ); ?>
		</form>
		</th>
		<th>
		<form method="post" id="bulkmediaregister_settings_schedule_add_form" action="<?php echo esc_url( $scriptname ); ?>" />
		<?php wp_nonce_field( 'bmr_add_schedule', 'bulk_media_register_add_schedule' ); ?>
		<?php submit_button( __( 'Add', 'bulk-media-register' ), 'large', 'add_interval', false ); ?>
		</form>
		</th>
		<?php
	}

	/** ==================================================
	 * Schedule html
	 *
	 * @param int    $count_schedules  count_schedules.
	 * @param string $schedule_key  schedule_key.
	 * @param int    $maxcount  maxcount.
	 * @since 2.00
	 */
	private function bulkmediaregister_add_schedule_html( $count_schedules, $schedule_key, $maxcount ) {

		$bulkmediaregister_cron_intervals_tbl = get_user_option( 'bulkmediaregister_event_intervals', get_current_user_id() );
		if ( empty( $bulkmediaregister_cron_intervals_tbl ) ) {
			$bulkmediaregister_cron_intervals_tbl = array();
		}
		?>
		<td>
		<?php
		if ( array_key_exists( $schedule_key, $bulkmediaregister_cron_intervals_tbl ) ) {
			?>
			<input type="checkbox" name="bulkmediaregister_cron_schedule_delete[]" form="bulkmediaregister_settings_schedule_delete_form" value="<?php echo esc_attr( $schedule_key ); ?>">
			<?php
		} else {
			?>
			<input type="checkbox" disabled='disabled'>
			<?php
		}
		?>
		</td>
		<?php
		if ( 0 == $count_schedules ) {
			?>
			<td rowspan="<?php echo esc_html( $maxcount ); ?>">
			<div style="display:block;padding:5px 30px">
				<div style="display:block;padding:5px 15px">
				<?php echo esc_html( __( 'Slug', 'bulk-media-register' ) . ' : ' ); ?>
				<input type="text" name="bulkmediaregister_cron_schedule_innername" form="bulkmediaregister_settings_schedule_add_form" />
				</div>
				<div style="display:block;padding:5px 15px">
				<?php echo esc_html( __( 'Interval(Seconds)', 'bulk-media-register' ) . ' : ' ); ?>
				<input type="number" step="1" min="1" name="bulkmediaregister_cron_schedule_secounds" form="bulkmediaregister_settings_schedule_add_form" />
				</div>
				<div style="display:block;padding:5px 15px">
				<?php echo esc_html__( 'Display name', 'bulk-media-register' ) . ' : '; ?>
				<input type="text" name="bulkmediaregister_cron_schedule_viewname" form="bulkmediaregister_settings_schedule_add_form" />
				</div>
			</div>
			</td>
			<?php
		}
	}

	/** ==================================================
	 * Update wp_options table.
	 *
	 * @since 2.00
	 */
	private function options_updated() {

		if ( isset( $_POST['add_interval'] ) && ! empty( $_POST['add_interval'] ) ) {
			if ( check_admin_referer( 'bmr_add_schedule', 'bulk_media_register_add_schedule' ) ) {
				if ( ! empty( $_POST['bulkmediaregister_cron_schedule_innername'] ) && ! empty( $_POST['bulkmediaregister_cron_schedule_secounds'] ) && ! empty( $_POST['bulkmediaregister_cron_schedule_viewname'] ) ) {
					$bulkmediaregister_cron_intervals_tbl = get_user_option( 'bulkmediaregister_event_intervals', get_current_user_id() );
					if ( empty( $bulkmediaregister_cron_intervals_tbl ) ) {
						$bulkmediaregister_cron_intervals_tbl = array();
					}
					$innername = sanitize_text_field( wp_unslash( $_POST['bulkmediaregister_cron_schedule_innername'] ) );
					$secounds = intval( $_POST['bulkmediaregister_cron_schedule_secounds'] );
					$viewname = sanitize_text_field( wp_unslash( $_POST['bulkmediaregister_cron_schedule_viewname'] ) );
					$bulkmediaregister_cron_intervals_tbl[ $innername ] = array(
						'interval' => $secounds,
						'display' => $viewname,
					);
					update_user_option( get_current_user_id(), 'bulkmediaregister_event_intervals', $bulkmediaregister_cron_intervals_tbl );
					echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html( __( 'Interval', 'bulk-media-register' ) . ' --> ' . __( 'Add', 'bulk-media-register' ) ) . '</li></ul></div>';
				}
			}
		}

		if ( isset( $_POST['delete_interval'] ) && ! empty( $_POST['delete_interval'] ) ) {
			if ( check_admin_referer( 'bmr_add_schedule_delete', 'bulk_media_register_add_schedule_delete' ) ) {
				if ( ! empty( $_POST['bulkmediaregister_cron_schedule_delete'] ) ) {
					$delete_keys = array_map( 'sanitize_text_field', wp_unslash( $_POST['bulkmediaregister_cron_schedule_delete'] ) );
					$bulkmediaregister_cron_intervals_tbl = get_user_option( 'bulkmediaregister_event_intervals', get_current_user_id() );
					$schedule_name = get_user_option( 'bulkmediaregister_wpcron', get_current_user_id() );
					foreach ( $delete_keys as $key ) {
						if ( $key === $schedule_name ) {
							update_user_option( get_current_user_id(), 'bulkmediaregister_wpcron', 'hourly' );
						}
						unset( $bulkmediaregister_cron_intervals_tbl[ $key ] );
					}
					update_user_option( get_current_user_id(), 'bulkmediaregister_event_intervals', $bulkmediaregister_cron_intervals_tbl );
					echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html( __( 'Interval', 'bulk-media-register' ) . ' --> ' . __( 'Delete', 'bulk-media-register' ) ) . '</li></ul></div>';
				}
			}
		}

		if ( isset( $_POST['save_interval'] ) && ! empty( $_POST['save_interval'] ) ) {
			if ( check_admin_referer( 'bmr_schedule', 'bulk_media_register_schedule' ) ) {
				if ( ! empty( $_POST['bulkmediaregister_cron_schedule'] ) ) {
					$schedule_name = sanitize_text_field( wp_unslash( $_POST['bulkmediaregister_cron_schedule'] ) );
					update_user_option( get_current_user_id(), 'bulkmediaregister_wpcron', $schedule_name );
					echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html( __( 'Interval', 'bulk-media-register' ) . ' --> ' . __( 'Changes saved.', 'bulk-media-register' ) ) . '</li></ul></div>';
				}
			}
		}

		if ( isset( $_POST['save_search_text'] ) && ! empty( $_POST['save_search_text'] ) ) {
			if ( check_admin_referer( 'bmr_cron_search_text', 'bulk_media_register_cron_search_text' ) ) {
				if ( ! empty( $_POST['cron_search_text'] ) ) {
					$cron_search_text = sanitize_text_field( wp_unslash( $_POST['cron_search_text'] ) );
					update_user_option( get_current_user_id(), 'bulkmediaregister_wpcron_search_text', $cron_search_text );
				} else {
					update_user_option( get_current_user_id(), 'bulkmediaregister_wpcron_search_text', null );
				}
				echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html( __( 'Filter by text', 'bulk-media-register' ) . ' --> ' . __( 'Changes saved.', 'bulk-media-register' ) ) . '</li></ul></div>';
			}
		}

		if ( isset( $_POST['start_cron'] ) && ! empty( $_POST['start_cron'] ) ) {
			if ( check_admin_referer( 'bmr_crondo', 'bulk_media_register_crondo' ) ) {
				do_action( 'bulkmediaregister_cron_start', get_current_user_id() );
				echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html__( 'Cron Event has started.', 'bulk-media-register' ) . '</li></ul></div>';
			}
		}

		if ( isset( $_POST['stop_cron'] ) && ! empty( $_POST['stop_cron'] ) ) {
			if ( check_admin_referer( 'bmr_crondo', 'bulk_media_register_crondo' ) ) {
				do_action( 'bulkmediaregister_cron_stop', get_current_user_id() );
				echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html__( 'Cron Event has stoped.', 'bulk-media-register' ) . '</li></ul></div>';
			}
		}

		if ( isset( $_POST['save_userid'] ) && ! empty( $_POST['save_userid'] ) ) {
			if ( check_admin_referer( 'bmr_cron_userid', 'bulk_media_register_cron_userid' ) ) {
				if ( ! empty( $_POST['cron_userid'] ) ) {
					$cron_userid = absint( $_POST['cron_userid'] );
					update_option( 'bulkmediaregister_wpcron_userid', $cron_userid );
				}
				echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html( __( 'Execution user ID', 'bulk-media-register' ) . ' --> ' . __( 'Changes saved.', 'bulk-media-register' ) ) . '</li></ul></div>';
			}
		}
	}

	/** ==================================================
	 * Settings register
	 *
	 * @since 2.00
	 */
	public function register_settings() {

		if ( ! get_user_option( 'bulkmediaregister_wpcron', get_current_user_id() ) ) {
			update_user_option( get_current_user_id(), 'bulkmediaregister_wpcron', 'hourly' );
		}
		if ( ! get_user_option( 'bulkmediaregister_wpcron_search_text', get_current_user_id() ) ) {
			update_user_option( get_current_user_id(), 'bulkmediaregister_wpcron_search_text', null );
		}
	}
}
