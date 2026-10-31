<?php
/**
 * Bulk Media Register
 *
 * @package    Bulk Media Register
 * @subpackage Bulk Media Register Management screen
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

$bulkmediaregisteradmin = new BulkMediaRegisterAdmin();

/** ==================================================
 * Management screen
 */
class BulkMediaRegisterAdmin {

	/** ==================================================
	 * Construct
	 *
	 * @since 1.00
	 */
	public function __construct() {

		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'check_and_deactivate_old_addon' ) );
		add_action( 'admin_notices', array( $this, 'display_integrated_addon_notice' ) );

		add_action( 'admin_menu', array( $this, 'add_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_custom_wp_admin_style1' ) );
		add_filter( 'plugin_action_links', array( $this, 'settings_link' ), 10, 2 );

		if ( ! class_exists( 'TT_BulkMediaRegister_List_Table' ) ) {
			require_once __DIR__ . '/class-tt-bulkmediaregister-list-table.php';
		}
	}

	/** ==================================================
	 * Add a "Settings" link to the plugins page
	 *
	 * @param  array  $links  links array.
	 * @param  string $file   file.
	 * @return array  $links  links array.
	 * @since 1.00
	 */
	public function settings_link( $links, $file ) {
		static $this_plugin;
		if ( empty( $this_plugin ) ) {
			$this_plugin = 'bulk-media-register/bulkmediaregister.php';
		}
		if ( $file == $this_plugin ) {
			$links[] = '<a href="' . admin_url( 'admin.php?page=bulkmediaregister' ) . '">Bulk Media Register</a>';
			$links[] = '<a href="' . admin_url( 'admin.php?page=bulkmediaregister-register' ) . '">' . __( 'Bulk Register', 'bulk-media-register' ) . '</a>';
			$links[] = '<a href="' . admin_url( 'admin.php?page=bulkmediaregister-selectregister' ) . '">' . __( 'Select Register', 'bulk-media-register' ) . '</a>';
			$links[] = '<a href="' . admin_url( 'admin.php?page=bulkmediaregister-settings' ) . '">' . __( 'Settings', 'bulk-media-register' ) . '</a>';
		}
		return $links;
	}

	/** ==================================================
	 * Add page
	 *
	 * @since 1.00
	 */
	public function add_pages() {
		add_menu_page(
			'Bulk Media Register',
			'Bulk Media Register',
			'upload_files',
			'bulkmediaregister',
			array( $this, 'manage_page' ),
			'dashicons-upload'
		);
		add_submenu_page(
			'bulkmediaregister',
			__( 'Bulk Register', 'bulk-media-register' ),
			__( 'Bulk Register', 'bulk-media-register' ),
			'upload_files',
			'bulkmediaregister-register',
			array( $this, 'register_page' )
		);
		add_submenu_page(
			'bulkmediaregister',
			__( 'Select Register', 'bulk-media-register' ),
			__( 'Select Register', 'bulk-media-register' ),
			'upload_files',
			'bulkmediaregister-selectregister',
			array( $this, 'select_register_page' )
		);
		add_submenu_page(
			'bulkmediaregister',
			__( 'Settings', 'bulk-media-register' ),
			__( 'Settings', 'bulk-media-register' ),
			'upload_files',
			'bulkmediaregister-settings',
			array( $this, 'settings_page' )
		);
		add_submenu_page(
			'bulkmediaregister',
			__( 'Cron Event', 'bulk-media-register' ),
			__( 'Cron Event', 'bulk-media-register' ),
			'upload_files',
			'bulkmediaregister-wpcron',
			array( $this, 'cron_page' )
		);
	}

	/** ==================================================
	 * Add Css and Script
	 *
	 * @since 1.00
	 */
	public function load_custom_wp_admin_style1() {
		if ( $this->is_my_plugin_screen1() ) {
			wp_enqueue_style( 'jquery-datetimepicker', plugin_dir_url( __DIR__ ) . '/css/jquery.datetimepicker.css', array(), '2.3.4' );
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-datetimepicker', plugin_dir_url( __DIR__ ) . '/js/jquery.datetimepicker.js', null, '2.3.4', true );
			wp_enqueue_script( 'bulkmediaregister-admin-js', plugin_dir_url( __DIR__ ) . 'js/jquery.bulkmediaregister.admin.js', array( 'jquery' ), '1.00', false );
		}
	}

	/** ==================================================
	 * For only admin style
	 *
	 * @since 1.00
	 */
	private function is_my_plugin_screen1() {
		$screen = get_current_screen();
		if ( is_object( $screen ) && 'bulk-media-register_page_bulkmediaregister-settings' === $screen->id ) {
			return true;
		} else {
			return false;
		}
	}

	/** ==================================================
	 * For only admin style
	 *
	 * @since 1.00
	 */
	private function is_my_plugin_screen2() {
		$screen = get_current_screen();
		if ( ( is_object( $screen ) && 'toplevel_page_bulkmediaregister' === $screen->id ) ||
				( is_object( $screen ) && 'bulk-media-register_page_bulkmediaregister-register' === $screen->id ) ||
				( is_object( $screen ) && 'bulk-media-register_page_bulkmediaregister-selectregister' === $screen->id ) ||
				( is_object( $screen ) && 'bulk-media-register_page_bulkmediaregister-settings' === $screen->id ) ) {
			return true;
		} else {
			return false;
		}
	}

	/** ==================================================
	 * Register
	 *
	 * @since 1.00
	 */
	public function register_page() {

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'bulk-media-register' ) );
		}

		do_action( 'bulk_media_register_notices' );

		$this->options_updated();

		$bulkmediaregister_settings = get_user_option( 'bulkmediaregister', get_current_user_id() );
		$scriptname = admin_url( 'admin.php?page=bulkmediaregister-register' );

		?>
		<div class="wrap">

		<h2>Bulk Media Register <a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmediaregister-register' ) ); ?>" style="text-decoration: none;"><?php esc_html_e( 'Bulk Register', 'bulk-media-register' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmediaregister-selectregister' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Select Register', 'bulk-media-register' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmediaregister-settings' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Settings', 'bulk-media-register' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmediaregister-wpcron' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Cron Event', 'bulk-media-register' ); ?></a>
			<?php
			if ( current_user_can( 'manage_options' ) ) {
				if ( class_exists( 'MovingMediaLibrary' ) ) {
					$movingmedialibrary_url = admin_url( 'admin.php?page=movingmedialibrary' );
				} elseif ( is_multisite() ) {
						$movingmedialibrary_url = network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=moving-media-library' );
				} else {
					$movingmedialibrary_url = admin_url( 'plugin-install.php?tab=plugin-information&plugin=moving-media-library' );
				}
				?>
				<a href="<?php echo esc_url( $movingmedialibrary_url ); ?>" class="page-title-action">Moving Media Library</a>
				<?php
			}
			?>
		</h2>
		<div style="clear: both;"></div>

		<div class="wrap">
			<div id="bulkmediaregister-loading-container">
				<div style="margin: 5px; padding: 5px;">
					<?php
					if ( isset( $_POST['bulk-media-register-search'] ) && ! empty( $_POST['bulk-media-register-search'] ) ) {
						if ( check_admin_referer( 'bmr_search', 'bulk_media_register_search' ) ) {
							$search_text = null;
							if ( ! empty( $_POST['search_text'] ) ) {
								$search_text = sanitize_text_field( wp_unslash( $_POST['search_text'] ) );
							}
							do_action( 'bmr_search_files', get_current_user_id(), $search_text );
							if ( get_user_option( 'bulkmediaregister_files_break', get_current_user_id() ) ) {
								echo '<div class="notice notice-warning is-dismissible"><ul><li>' . esc_html__( 'The search was interrupted because the timeout was approaching. After registering the following searched files, search the remaining files again. If the number of searches is extremely low, it\'s because you have too many files to search. You need to increase the execution time. Increase the "max_execution_time" in "php.ini".', 'bulk-media-register' ) . '</li></ul></div>';
							}
							$files = get_user_option( 'bulkmediaregister_files', get_current_user_id() );
							if ( ! empty( $files ) ) {
								wp_enqueue_script( 'jquery' );
								$handle  = 'bulkmediaregister-js';
								$action1 = 'bulkmediaregister-ajax-action';
								$action2 = 'bulkmediaregister_message';
								wp_enqueue_script( $handle, plugin_dir_url( __DIR__ ) . 'js/jquery.bulkmediaregister.js', array( 'jquery' ), '1.00', false );

								wp_localize_script(
									$handle,
									'bulkmediaregister',
									array(
										'ajax_url' => admin_url( 'admin-ajax.php' ),
										'action' => $action1,
										'nonce' => wp_create_nonce( $action1 ),
									)
								);
								wp_localize_script(
									$handle,
									'bulkmediaregister_mes',
									array(
										'ajax_url' => admin_url( 'admin-ajax.php' ),
										'action' => $action2,
										'nonce' => wp_create_nonce( $action2 ),
									)
								);
								wp_localize_script(
									$handle,
									'bulkmediaregister_data',
									array(
										'count' => count( $files ),
										'file' => wp_json_encode( $files, JSON_UNESCAPED_SLASHES ),
										'uid' => get_current_user_id(),
									)
								);

								wp_localize_script(
									$handle,
									'bulkmediaregister_text',
									array(
										'stop_button' => __( 'Stop', 'bulk-media-register' ),
										'stop_message' => __( 'Stopping now..', 'bulk-media-register' ),
									)
								);

								?>
								<div id="bulkmediaregister-register-container">
									<p class="description">
									<?php
									/* translators: %1$d -> file count */
									echo esc_html( sprintf( __( '%1$d files are ready to be registered. Press the button below to start the registration.', 'bulk-media-register' ), count( $files ) ) );
									?>
									</p>
									<?php submit_button( __( 'Register', 'bulk-media-register' ), 'primary', 'bulkmediaregister_ajax_update', true ); ?>
								</div>
								<?php
							} else {
								?>
								<div class="notice notice-warning is-dismissible"><ul><li><?php esc_html_e( 'File doesn&#8217;t exist?', 'bulk-media-register' ); ?></li></ul></div>
								<?php
							}
						}
					} else {
						do_action( 'bmr_dir_select_box', $bulkmediaregister_settings['searchdir'], $bulkmediaregister_settings['extfilter'], get_current_user_id(), $scriptname );
						if ( get_user_option( 'bulkmediaregister_dirs_break', get_current_user_id() ) ) {
							echo '<div class="notice notice-warning is-dismissible"><ul><li>' . esc_html__( 'Couldn\'t search all the folders. This is due to the fact that there were too many files to search. You need to increase the execution time. Increase the "max_execution_time" in "php.ini".', 'bulk-media-register' ) . '</li></ul></div>';
						}
						if ( isset( $_POST['bulk-media-register-folder'] ) && ! empty( $_POST['bulk-media-register-folder'] ) ) {
							?>
							<form method="post" action="<?php echo esc_url( $scriptname ); ?>">
							<?php wp_nonce_field( 'bmr_search', 'bulk_media_register_search' ); ?>
							<div style="margin: 5px; padding: 5px;">
							<input name="search_text" type="text" placeholder="<?php echo esc_attr__( 'Filter by text', 'bulk-media-register' ); ?>" style="width: 200px;">
							<?php submit_button( __( 'Search', 'bulk-media-register' ), 'large', 'bulk-media-register-search', false ); ?>
							</div>
							</form>
							<?php
						}
					}
					?>
				</div>
			</div>
		</div>

		<?php
	}

	/** ==================================================
	 * Select Register
	 *
	 * @since 1.10
	 */
	public function select_register_page() {

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'bulk-media-register' ) );
		}

		do_action( 'bulk_media_register_notices' );

		$this->options_updated();

		$search_text = get_user_option( 'bulkmediaregister_search_text', get_current_user_id() );
		if ( isset( $_POST['bulk-media-register-searchtext'] ) && ! empty( $_POST['bulk-media-register-searchtext'] ) ) {
			if ( check_admin_referer( 'bmr_search_text', 'bulk_media_register_search_text' ) ) {
				if ( ! empty( $_POST['search_text'] ) ) {
					$search_text = sanitize_text_field( wp_unslash( $_POST['search_text'] ) );
					update_user_option( get_current_user_id(), 'bulkmediaregister_search_text', $search_text );
				} else {
					delete_user_option( get_current_user_id(), 'bulkmediaregister_search_text' );
					$search_text = null;
				}
			}
		}

		$bulkmediaregister_settings = get_user_option( 'bulkmediaregister', get_current_user_id() );
		$scriptname = admin_url( 'admin.php?page=bulkmediaregister-selectregister' );

		?>
		<div class="wrap">

		<h2>Bulk Media Register <a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmediaregister-selectregister' ) ); ?>" style="text-decoration: none;"><?php esc_html_e( 'Select Register', 'bulk-media-register' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmediaregister-register' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Bulk Register', 'bulk-media-register' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmediaregister-settings' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Settings', 'bulk-media-register' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmediaregister-wpcron' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Cron Event', 'bulk-media-register' ); ?></a>
			<?php
			if ( current_user_can( 'manage_options' ) ) {
				if ( class_exists( 'MovingMediaLibrary' ) ) {
					$movingmedialibrary_url = admin_url( 'admin.php?page=movingmedialibrary' );
				} elseif ( is_multisite() ) {
						$movingmedialibrary_url = network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=moving-media-library' );
				} else {
					$movingmedialibrary_url = admin_url( 'plugin-install.php?tab=plugin-information&plugin=moving-media-library' );
				}
				?>
				<a href="<?php echo esc_url( $movingmedialibrary_url ); ?>" class="page-title-action">Moving Media Library</a>
				<?php
			}
			?>
		</h2>
		<div style="clear: both;"></div>

		<div class="wrap">
			<div id="selectmediaregister-loading-container">
				<div style="margin: 5px; padding: 5px;">
					<?php
					do_action( 'bmr_dir_select_box', $bulkmediaregister_settings['searchdir'], $bulkmediaregister_settings['extfilter'], get_current_user_id(), $scriptname );
					if ( get_user_option( 'bulkmediaregister_dirs_break', get_current_user_id() ) ) {
						echo '<div class="notice notice-warning is-dismissible"><ul><li>' . esc_html__( 'Couldn\'t search all the folders. This is due to the fact that there were too many files to search. You need to increase the execution time. Increase the "max_execution_time" in "php.ini".', 'bulk-media-register' ) . '</li></ul></div>';
					}
					?>
					<div style="margin: 0px; text-align: right;">
					<form method="post" action="<?php echo esc_url( $scriptname ); ?>">
					<?php
					wp_nonce_field( 'bmr_search_text', 'bulk_media_register_search_text' );
					if ( ! $search_text ) {
						?>
						<input name="search_text" type="text" value="" placeholder="<?php echo esc_attr__( 'Search', 'bulk-media-register' ); ?>">
						<?php
					} else {
						?>
						<input name="search_text" type="text" value="<?php echo esc_attr( $search_text ); ?>">
						<?php
					}
					submit_button( __( 'Search', 'bulk-media-register' ), 'large', 'bulk-media-register-searchtext', false );
					?>
					</form>
					</div>
					<form method="post" id="select_media_register_per_page_forms" action="<?php echo esc_url( $scriptname ); ?>">
					<?php
					wp_nonce_field( 'bmr_per_page', 'select_media_register' );
					do_action( 'bmr_per_page_set', get_current_user_id() );
					?>
					</form>
					<?php
					do_action( 'bmr_search_files', get_current_user_id(), $search_text );
					if ( get_user_option( 'bulkmediaregister_files_break', get_current_user_id() ) ) {
						echo '<div class="notice notice-warning is-dismissible"><ul><li>' . esc_html__( 'The search was interrupted because the timeout was approaching. After registering the following searched files, search the remaining files again. If the number of searches is extremely low, it\'s because you have too many files to search. You need to increase the execution time. Increase the "max_execution_time" in "php.ini".', 'bulk-media-register' ) . '</li></ul></div>';
					}
					submit_button( __( 'Register', 'bulk-media-register' ), 'primary', 'selectmediaregister_ajax_update1', true );
					$bulk_media_register_list_table = new TT_BulkMediaRegister_List_Table();
					$bulk_media_register_list_table->prepare_items();
					?>
					<form method="post" id="selectmediaregister_forms">
					<?php
					$bulk_media_register_list_table->display();
					?>
					</form>
					<?php
					submit_button( __( 'Register', 'bulk-media-register' ), 'primary', 'selectmediaregister_ajax_update2', true );
					if ( get_user_option( 'bulkmediaregister_lists_break', get_current_user_id() ) ) {
						echo '<div class="notice notice-warning is-dismissible"><ul><li>' . esc_html__( 'The search was interrupted because the timeout was approaching. After registering the following searched files, search the remaining files again. If the number of searches is extremely low, it\'s because you have too many files to search. You need to increase the execution time. Increase the "max_execution_time" in "php.ini".', 'bulk-media-register' ) . '</li></ul></div>';
					}
					?>
				</div>
			</div>
		</div>

		<?php
	}

	/** ==================================================
	 * Settings page
	 *
	 * @since 1.00
	 */
	public function settings_page() {

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'bulk-media-register' ) );
		}

		$this->options_updated();

		$bulkmediaregister_settings = get_user_option( 'bulkmediaregister', get_current_user_id() );

		$scriptname = admin_url( 'admin.php?page=bulkmediaregister-settings' );

		?>
		<div class="wrap">

		<h2>Bulk Media Register <a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmediaregister-settings' ) ); ?>" style="text-decoration: none;"><?php esc_html_e( 'Settings', 'bulk-media-register' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmediaregister-register' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Bulk Register', 'bulk-media-register' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmediaregister-selectregister' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Select Register', 'bulk-media-register' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmediaregister-wpcron' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Cron Event', 'bulk-media-register' ); ?></a>
			<?php
			if ( current_user_can( 'manage_options' ) ) {
				if ( class_exists( 'MovingMediaLibrary' ) ) {
					$movingmedialibrary_url = admin_url( 'admin.php?page=movingmedialibrary' );
				} elseif ( is_multisite() ) {
						$movingmedialibrary_url = network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=moving-media-library' );
				} else {
					$movingmedialibrary_url = admin_url( 'plugin-install.php?tab=plugin-information&plugin=moving-media-library' );
				}
				?>
				<a href="<?php echo esc_url( $movingmedialibrary_url ); ?>" class="page-title-action">Moving Media Library</a>
				<?php
			}
			?>
		</h2>
		<div style="clear: both;"></div>

			<div class="wrap">
				<form method="post" action="<?php echo esc_url( $scriptname ); ?>">
				<?php wp_nonce_field( 'bmr_settings', 'bulk_media_register_settings' ); ?>
				<details style="margin-bottom: 5px;" open>
				<summary style="cursor: pointer; padding: 10px; border: 1px solid #ddd; background: #f4f4f4; color: #000;"><strong><?php esc_html_e( 'Search', 'bulk-media-register' ); ?></strong></summary>
					<div style="display: block;padding:5px 5px">
					<input name="bulkmediaregister_recursive_search" type="checkbox" value="1" <?php checked( $bulkmediaregister_settings['recursive_search'], true ); ?>><?php esc_html_e( 'Recursively search files below specified folder.', 'bulk-media-register' ); ?>
					</div>
				</details>
				<details style="margin-bottom: 5px;" open>
				<summary style="cursor: pointer; padding: 10px; border: 1px solid #ddd; background: #f4f4f4; color: #000;"><strong><?php esc_html_e( 'Date', 'bulk-media-register' ); ?></strong></summary>
					<div style="display: block;padding:5px 5px">
					<input type="radio" name="bulkmediaregister_dateset" value="new" 
					<?php
					if ( 'new' === $bulkmediaregister_settings['dateset'] ) {
						echo 'checked';
					}
					?>
					>
					<?php esc_html_e( 'Update to use of the current date/time.', 'bulk-media-register' ); ?>
					</div>
					<div style="display: block;padding:5px 5px">
					<input type="radio" name="bulkmediaregister_dateset" value="server" 
					<?php
					if ( 'server' === $bulkmediaregister_settings['dateset'] ) {
						echo 'checked';
					}
					?>
					>
					<?php esc_html_e( 'Get the date/time of the file, and updated based on it.', 'bulk-media-register' ); ?>
					</div>
					<div style="display: block; padding:5px 5px">
					<input type="radio" name="bulkmediaregister_dateset" value="fixed" 
					<?php
					if ( 'fixed' === $bulkmediaregister_settings['dateset'] ) {
						echo 'checked';
					}
					?>
					>
					<?php esc_html_e( 'Update to use of fixed the date/time.', 'bulk-media-register' ); ?>
					</div>
					<div style="display: block; padding:5px 40px">
					<input type="text" id="datetimepicker-bulkmediaregister" name="bulkmediaregister_datefixed" value="<?php echo esc_attr( $bulkmediaregister_settings['datefixed'] ); ?>">
					</div>
					<div style="display: block; padding:5px 5px">
					<?php
					if ( class_exists( 'UploadMediaExifDate' ) ) {
						?>
						<ul style="display: block; padding:5px 20px">
						<li type="disc">
						<?php esc_html_e( '"Upload Media Exif Date" is activated. Register with the date of the EXIF information of the image.', 'bulk-media-register' ); ?>
						</li>
						</ul>
						<?php
					} else {
						if ( is_multisite() ) {
							$uploadmediaexifdate_url = network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=upload-media-exif-date' );
						} else {
							$uploadmediaexifdate_url = admin_url( 'plugin-install.php?tab=plugin-information&plugin=upload-media-exif-date' );
						}
						?>
						<ul style="display: block; padding:5px 20px">
						<li type="disc">
						<?php esc_html_e( 'If you want to register with the date of EXIF information of the image, please enable the following plugin.', 'bulk-media-register' ); ?>
						<a href="<?php echo esc_url( $uploadmediaexifdate_url ); ?>" class="page-title-action">Upload Media Exif Date</a>
						</li>
						</ul>
						<?php
					}
					?>
					</div>
				</details>
				<details style="margin-bottom: 5px;" open>
				<summary style="cursor: pointer; padding: 10px; border: 1px solid #ddd; background: #f4f4f4; color: #000;"><strong><?php esc_html_e( 'Exclude file & folder', 'bulk-media-register' ); ?></strong></summary>
					<div style="display: block;padding:5px 5px">
					<div><?php esc_html_e( 'Separate part of a file or folder string with "|".', 'bulk-media-register' ); ?></div>
					<textarea name="bulkmediaregister_exclude" rows="3" style="width: 100%;"><?php echo esc_textarea( $bulkmediaregister_settings['exclude'] ); ?></textarea>
					</div>
				</details>
				<details style="margin-bottom: 5px;" open>
				<summary style="cursor: pointer; padding: 10px; border: 1px solid #ddd; background: #f4f4f4; color: #000;"><strong><?php esc_html_e( 'Send Email', 'bulk-media-register' ); ?></strong></summary>
					<div style="display: block;padding:5px 5px">
					<input name="bulkmediaregister_mail_send" type="checkbox" value="1" <?php checked( $bulkmediaregister_settings['mail_send'], true ); ?>><?php esc_html_e( 'Registration results will be sent via email.', 'bulk-media-register' ); ?>
					</div>
				</details>
				<details style="margin-bottom: 5px;" open>
				<summary style="cursor: pointer; padding: 10px; border: 1px solid #ddd; background: #f4f4f4; color: #000;"><strong><?php esc_html_e( 'Note', 'bulk-media-register' ); ?></strong></summary>
					<div style="display: block;padding:5px 5px">
					<?php esc_html_e( 'If you want to use a multi-byte file name, use UTF-8. The file name is used as the title during registration, but is sanitized and changed to a different file name.', 'bulk-media-register' ); ?>
					</div>
				</details>
				<details style="margin-bottom: 5px;">
				<summary style="cursor: pointer; padding: 10px; border: 1px solid #ddd; background: #f4f4f4; color: #000;"><strong><?php esc_html_e( 'Execution time', 'bulk-media-register' ); ?></strong></summary>
					<div style="display:block; padding:5px 5px">
						<?php
						$def_max_execution_time = ini_get( 'max_execution_time' );
						$max_execution_time = $bulkmediaregister_settings['max_execution_time'];
						if ( ! @set_time_limit( $max_execution_time ) ) {
							$limit_seconds_html = '<font color="red">' . $def_max_execution_time . __( 'seconds', 'bulk-media-register' ) . '</font>';
							?>
							<p class="description">
							<?php
							/* translators: %1$s: limit max execution time */
							echo wp_kses_post( sprintf( __( 'This server has a fixed execution time at %1$s and cannot be changed.', 'bulk-media-register' ), $limit_seconds_html ) );
							?>
							</p>
							<input type="hidden" name="bulkmediaregister_max_execution_time" value="<?php echo esc_attr( $def_max_execution_time ); ?>" />
							<?php
						} else {
							$max_execution_time_text = __( 'The number of seconds a script is allowed to run.', 'bulk-media-register' ) . '(' . __( 'The max_execution_time value defined in the php.ini.', 'bulk-media-register' ) . '[<font color="red">' . $def_max_execution_time . '</font>])';
							?>
							<p class="description">
							<?php esc_html_e( 'Increase this value if the number of searches is drastically reduced during a large number of file searches.', 'bulk-media-register' ); ?>
							</p>
							<p class="description">
							<?php echo wp_kses_post( $max_execution_time_text ); ?>:<input type="number" step="1" min="1" max="9999" style="width: 80px;" name="bulkmediaregister_max_execution_time" value="<?php echo esc_attr( $max_execution_time ); ?>" />
							</p>
							<?php
						}
						?>
					</div>
				</details>
				<details style="margin-bottom: 5px;">
				<summary style="cursor: pointer; padding: 10px; border: 1px solid #ddd; background: #f4f4f4; color: #000;"><strong><?php esc_html_e( 'Remove Thumbnails Cache', 'bulk-media-register' ); ?></strong></summary>
					<div style="display:block; padding:5px 5px">
					<?php submit_button( __( 'Remove Thumbnails Cache', 'bulk-media-register' ), 'large', 'bulk_media_register_clear_cash', false, array( 'form' => 'thumbnails_cash_clear' ) ); ?>
					</div>
					<p class="description" style="display: block;padding:0px 15px">
					<?php esc_html_e( 'This item is required for "Select Register" only.', 'bulk-media-register' ); ?>
					<?php esc_html_e( 'Remove the cache of thumbnail used in the search screen. Please try out if trouble occurs in the search screen. It might become normal.', 'bulk-media-register' ); ?>
					</p>
				</details>
				<?php submit_button( __( 'Save Changes', 'bulk-media-register' ), 'large', 'bulk-media-register-settings-options-apply', true ); ?>
				</form>
				<form method="post" id="thumbnails_cash_clear" action="<?php echo esc_url( $scriptname ); ?>" />
					<?php wp_nonce_field( 'bmr_clear_cash', 'bulk_media_register_clear_cash' ); ?>
				</form>
			</div>

		</div>
		<?php
	}

	/** ==================================================
	 * Main
	 *
	 * @since 1.00
	 */
	public function manage_page() {

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'bulk-media-register' ) );
		}

		?>

		<div class="wrap">

		<h2>Bulk Media Register
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmediaregister-register' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Bulk Register', 'bulk-media-register' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmediaregister-selectregister' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Select Register', 'bulk-media-register' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmediaregister-settings' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Settings', 'bulk-media-register' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmediaregister-wpcron' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Cron Event', 'bulk-media-register' ); ?></a>
			<?php
			if ( current_user_can( 'manage_options' ) ) {
				if ( class_exists( 'MovingMediaLibrary' ) ) {
					$movingmedialibrary_url = admin_url( 'admin.php?page=movingmedialibrary' );
				} elseif ( is_multisite() ) {
						$movingmedialibrary_url = network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=moving-media-library' );
				} else {
					$movingmedialibrary_url = admin_url( 'plugin-install.php?tab=plugin-information&plugin=moving-media-library' );
				}
				?>
				<a href="<?php echo esc_url( $movingmedialibrary_url ); ?>" class="page-title-action">Moving Media Library</a>
				<?php
			}
			?>
		</h2>
		<div style="clear: both;"></div>

		<h3><?php esc_html_e( 'Bulk register files on the server to the Media Library.', 'bulk-media-register' ); ?></h3>

		<?php $this->credit(); ?>

		</div>
		<?php
	}

	/** ==================================================
	 * Credit
	 *
	 * @since 1.00
	 */
	private function credit() {

		$plugin_name    = null;
		$plugin_ver_num = null;
		$plugin_path    = plugin_dir_path( __DIR__ );
		$plugin_dir     = untrailingslashit( $plugin_path );
		$slugs          = explode( '/', wp_normalize_path( $plugin_dir ) );
		$slug           = end( $slugs );
		$files          = scandir( $plugin_dir );
		foreach ( $files as $file ) {
			if ( '.' === $file || '..' === $file || is_dir( $plugin_path . $file ) ) {
				continue;
			} else {
				$exts = explode( '.', $file );
				$ext  = strtolower( end( $exts ) );
				if ( 'php' === $ext ) {
					$plugin_datas = get_file_data(
						$plugin_path . $file,
						array(
							'name'    => 'Plugin Name',
							'version' => 'Version',
						)
					);
					if ( array_key_exists( 'name', $plugin_datas ) && ! empty( $plugin_datas['name'] ) && array_key_exists( 'version', $plugin_datas ) && ! empty( $plugin_datas['version'] ) ) {
						$plugin_name    = $plugin_datas['name'];
						$plugin_ver_num = $plugin_datas['version'];
						break;
					}
				}
			}
		}
		$plugin_version = __( 'Version:', 'bulk-media-register' ) . ' ' . $plugin_ver_num;
		/* translators: FAQ Link & Slug */
		$faq       = sprintf( esc_html__( 'https://wordpress.org/plugins/%s/faq', 'bulk-media-register' ), $slug );
		$support   = 'https://wordpress.org/support/plugin/' . $slug;
		$review    = 'https://wordpress.org/support/view/plugin-reviews/' . $slug;
		$translate = 'https://translate.wordpress.org/projects/wp-plugins/' . $slug;
		$facebook  = 'https://www.facebook.com/katsushikawamori/';
		$twitter   = 'https://twitter.com/dodesyo312';
		$youtube   = 'https://www.youtube.com/channel/UC5zTLeyROkvZm86OgNRcb_w';
		$donate    = sprintf( esc_html__( 'https://shop.riverforest-wp.info/donate/', 'bulk-media-register' ), $slug );

		?>
		<span style="font-weight: bold;">
		<div>
		<?php echo esc_html( $plugin_version ); ?> | 
		<a style="text-decoration: none;" href="<?php echo esc_url( $faq ); ?>" target="_blank" rel="noopener noreferrer">FAQ</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $support ); ?>" target="_blank" rel="noopener noreferrer">Support Forums</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $review ); ?>" target="_blank" rel="noopener noreferrer">Reviews</a>
		</div>
		<div>
		<a style="text-decoration: none;" href="<?php echo esc_url( $translate ); ?>" target="_blank" rel="noopener noreferrer">
		<?php
		/* translators: Plugin translation link */
		echo esc_html( sprintf( __( 'Translations for %s', 'bulk-media-register' ), $plugin_name ) );
		?>
		</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $facebook ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-facebook"></span></a> | <a style="text-decoration: none;" href="<?php echo esc_url( $twitter ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-twitter"></span></a> | <a style="text-decoration: none;" href="<?php echo esc_url( $youtube ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-video-alt3"></span></a>
		</div>
		</span>

		<div style="width: 250px; height: 180px; margin: 5px; padding: 5px; border: #CCC 2px solid;">
		<h3><?php esc_html_e( 'Please make a donation if you like my work or would like to further the development of this plugin.', 'bulk-media-register' ); ?></h3>
		<div style="text-align: right; margin: 5px; padding: 5px;"><span style="padding: 3px; color: #ffffff; background-color: #008000">Plugin Author</span> <span style="font-weight: bold;">Katsushi Kawamori</span></div>
		<button type="button" style="margin: 5px; padding: 5px;" onclick="window.open('<?php echo esc_url( $donate ); ?>')"><?php esc_html_e( 'Donate to this plugin &#187;', 'bulk-media-register' ); ?></button>
		</div>

		<?php
	}

	/** ==================================================
	 * Update wp_options table.
	 *
	 * @since 1.00
	 */
	private function options_updated() {

		$bulkmediaregister_settings = get_user_option( 'bulkmediaregister', get_current_user_id() );

		if ( isset( $_POST['bulk-media-register-settings-options-apply'] ) && ! empty( $_POST['bulk-media-register-settings-options-apply'] ) ) {
			if ( check_admin_referer( 'bmr_settings', 'bulk_media_register_settings' ) ) {
				if ( ! empty( $_POST['bulkmediaregister_recursive_search'] ) ) {
					$bulkmediaregister_settings['recursive_search'] = true;
				} else {
					$bulkmediaregister_settings['recursive_search'] = false;
				}
				if ( ! empty( $_POST['bulkmediaregister_dateset'] ) ) {
					$bulkmediaregister_settings['dateset'] = sanitize_text_field( wp_unslash( $_POST['bulkmediaregister_dateset'] ) );
				}
				if ( ! empty( $_POST['bulkmediaregister_datefixed'] ) ) {
					$bulkmediaregister_settings['datefixed'] = sanitize_text_field( wp_unslash( $_POST['bulkmediaregister_datefixed'] ) );
				}
				if ( isset( $_POST['bulkmediaregister_exclude'] ) ) {
					$bulkmediaregister_settings['exclude'] = sanitize_textarea_field( wp_unslash( $_POST['bulkmediaregister_exclude'] ) );
				}
				if ( ! empty( $_POST['bulkmediaregister_max_execution_time'] ) ) {
					$bulkmediaregister_settings['max_execution_time'] = absint( $_POST['bulkmediaregister_max_execution_time'] );
				}
				if ( ! empty( $_POST['bulkmediaregister_mail_send'] ) ) {
					$bulkmediaregister_settings['mail_send'] = true;
				} else {
					$bulkmediaregister_settings['mail_send'] = false;
				}
				update_user_option( get_current_user_id(), 'bulkmediaregister', $bulkmediaregister_settings );
				echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html( __( 'Settings', 'bulk-media-register' ) . ' --> ' . __( 'Changes saved.', 'bulk-media-register' ) ) . '</li></ul></div>';
			}
		}

		if ( isset( $_POST['bulk-media-register-folder'] ) && ! empty( $_POST['bulk-media-register-folder'] ) ) {
			if ( check_admin_referer( 'bmr_folder', 'bulk_media_register_folder' ) ) {
				if ( ! empty( $_POST['searchdir'] ) ) {
					$searchdir = sanitize_text_field( wp_unslash( $_POST['searchdir'] ) );
					$bulkmediaregister_settings['searchdir'] = $searchdir;
				} else {
					$bulkmediaregister_settings['searchdir'] = null;
				}
				if ( ! empty( $_POST['extension'] ) ) {
					$extfilter = sanitize_textarea_field( wp_unslash( $_POST['extension'] ) );
					$bulkmediaregister_settings['extfilter'] = strtolower( $extfilter );
				}
				update_user_option( get_current_user_id(), 'bulkmediaregister', $bulkmediaregister_settings );
			}
		}

		if ( isset( $_POST['bulk_media_register_clear_cash'] ) && ! empty( $_POST['bulk_media_register_clear_cash'] ) ) {
			if ( check_admin_referer( 'bmr_clear_cash', 'bulk_media_register_clear_cash' ) ) {
				do_action( 'bmr_delete_all_cash' );
				if ( 0 < get_option( 'bulkmediaregister_cash' ) ) {
					echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html( __( 'Thumbnails Cache', 'bulk-media-register' ) . ' --> ' . __( 'Delete', 'bulk-media-register' ) ) . '</li></ul></div>';
				} else {
					echo '<div class="notice notice-info is-dismissible"><ul><li>' . esc_html__( 'No Thumbnails Cache', 'bulk-media-register' ) . '</li></ul></div>';
				}
			}
		}

		if ( isset( $_POST['per_page_change'] ) && ! empty( $_POST['per_page_change'] ) ) {
			if ( check_admin_referer( 'bmr_per_page', 'select_media_register' ) ) {
				if ( ! empty( $_POST['per_page'] ) ) {
					$bulkmediaregister_settings['per_page'] = absint( $_POST['per_page'] );
					update_user_option( get_current_user_id(), 'bulkmediaregister', $bulkmediaregister_settings );
					echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html( __( 'Settings', 'bulk-media-register' ) . ' --> ' . __( 'Changes saved.', 'bulk-media-register' ) ) . '</li></ul></div>';
				}
			}
		}
	}

	/** ==================================================
	 * Settings register
	 *
	 * @since 1.00
	 */
	public function register_settings() {

		$dateset = 'new';
		if ( function_exists( 'wp_date' ) ) {
			$datefixed = wp_date( 'Y-m-d H:i' );
		} else {
			$datefixed = date_i18n( 'Y-m-d H:i' );
		}
		$exclude = 'woocommerce_uploads|wc-logs';

		if ( ! get_user_option( 'bulkmediaregister', get_current_user_id() ) ) {
			$bulkmediaregister_tbl = array(
				'searchdir' => null,
				'extfilter' => 'all',
				'recursive_search' => true,
				'per_page' => 20,
				'dateset' => $dateset,
				'datefixed' => $datefixed,
				'exclude' => $exclude,
				'max_execution_time' => ini_get( 'max_execution_time' ),
				'mail_send' => true,
			);
			update_user_option( get_current_user_id(), 'bulkmediaregister', $bulkmediaregister_tbl );
		} else {
			$bulkmediaregister_settings = get_user_option( 'bulkmediaregister', get_current_user_id() );
			if ( ! array_key_exists( 'max_execution_time', $bulkmediaregister_settings ) ) {
				$bulkmediaregister_settings['max_execution_time'] = ini_get( 'max_execution_time' );
				update_user_option( get_current_user_id(), 'bulkmediaregister', $bulkmediaregister_settings );
			}
			if ( ! array_key_exists( 'per_page', $bulkmediaregister_settings ) ) {
				$bulkmediaregister_settings['per_page'] = 20;
				update_user_option( get_current_user_id(), 'bulkmediaregister', $bulkmediaregister_settings );
			}
			if ( ! array_key_exists( 'extfilter', $bulkmediaregister_settings ) ) {
				$bulkmediaregister_settings['extfilter'] = 'all';
				update_user_option( get_current_user_id(), 'bulkmediaregister', $bulkmediaregister_settings );
			}
			if ( ! array_key_exists( 'mail_send', $bulkmediaregister_settings ) ) {
				$bulkmediaregister_settings['mail_send'] = true;
				update_user_option( get_current_user_id(), 'bulkmediaregister', $bulkmediaregister_settings );
			}
		}

		if ( ! get_option( 'bulkmediaregister_notice' ) ) {
			update_option( 'bulkmediaregister_notice', 1.08 );
		}
	}

	/** ==================================================
	 * Add on Wp Cron
	 *
	 * @since 1.00
	 */
	public function cron_page() {

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'bulk-media-register' ) );
		}

		?>
		<div class="wrap">

		<h2>Bulk Media Register <a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmediaregister-wpcron' ) ); ?>" style="text-decoration: none;"><?php esc_html_e( 'Cron Event', 'bulk-media-register' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmediaregister-register' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Bulk Register', 'bulk-media-register' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmediaregister-selectregister' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Select Register', 'bulk-media-register' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmediaregister-settings' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Settings', 'bulk-media-register' ); ?></a>
			<?php
			if ( current_user_can( 'manage_options' ) ) {
				if ( class_exists( 'MovingMediaLibrary' ) ) {
					$movingmedialibrary_url = admin_url( 'admin.php?page=movingmedialibrary' );
				} elseif ( is_multisite() ) {
						$movingmedialibrary_url = network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=moving-media-library' );
				} else {
					$movingmedialibrary_url = admin_url( 'plugin-install.php?tab=plugin-information&plugin=moving-media-library' );
				}
				?>
				<a href="<?php echo esc_url( $movingmedialibrary_url ); ?>" class="page-title-action">Moving Media Library</a>
				<?php
			}
			?>
		</h2>
		<div style="clear: both;"></div>

		<div class="wrap">
			<?php do_action( 'bmr_cron_page', get_current_user_id() ); ?>
		</div>
		<?php
	}

	/** ==================================================
	 * Detecting and Disabling Legacy Add-ons, and Setting Notification Flags
	 *
	 * @since 2.00
	 */
	public function check_and_deactivate_old_addon() {

		$addon_plugin_slug = 'bulk-media-register-add-on-wpcron/bulkmediaregisteraddonwpcron.php';

		if ( ! function_exists( 'deactivate_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( is_plugin_active( $addon_plugin_slug ) ) {
			deactivate_plugins( $addon_plugin_slug );

			set_transient( 'bmr_integrated_addon_deactivated_notice', true, 5 * MINUTE_IN_SECONDS );
		}
	}

	/** ==================================================
	 * Displaying a notification message about add-on deactivation in the admin panel
	 *
	 * @since 2.00
	 */
	public function display_integrated_addon_notice() {

		if ( get_transient( 'bmr_integrated_addon_deactivated_notice' ) ) {
			?>
			<div class="notice notice-info is-dismissible">
				<ul><li><?php echo esc_html_e( 'Since the add-on features have been integrated into the main plugin (Bulk Media Register), we have disabled the add-on (Bulk Media Register Add On Wp Cron). Please remove the no-longer-needed add-on (Bulk Media Register Add On Wp Cron) from your plugin list.', 'bulk-media-register' ); ?></li></ul>
			</div>
			<?php
			delete_transient( 'bmr_integrated_addon_deactivated_notice' );
		}
	}
}
