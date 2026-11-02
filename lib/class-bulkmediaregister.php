<?php
/**
 * Bulk Media Register
 *
 * @package    Bulk Media Register
 * @subpackage BulkMediaRegister Main function
/*  Copyright (c) 2020- Katsushi Kawamori (email : dodesyoswift312@gmail.com)
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

$bulkmediaregister = new BulkMediaRegister();

/** ==================================================
 * Class Main function
 *
 * @since 1.00
 */
class BulkMediaRegister {

	/** ==================================================
	 * Path
	 *
	 * @var $upload_dir  upload_dir.
	 */
	public $upload_dir;

	/** ==================================================
	 * Path
	 *
	 * @var $upload_url  upload_url.
	 */
	public $upload_url;

	/** ==================================================
	 * Path
	 *
	 * @var $upload_path  upload_path.
	 */
	public $upload_path;

	/** ==================================================
	 * Path
	 *
	 * @var $plugin_tmp_url  plugin_tmp_url.
	 */
	public $plugin_tmp_url;

	/** ==================================================
	 * Path
	 *
	 * @var $plugin_tmp_dir  plugin_tmp_dir.
	 */
	public $plugin_tmp_dir;

	/** ==================================================
	 * Construct
	 *
	 * @since 1.00
	 */
	public function __construct() {

		list( $this->upload_dir, $this->upload_url, $this->upload_path ) = $this->upload_dir_url_path();
		$this->plugin_tmp_url = $this->upload_url . '/bulk-media-register-tmp';
		$this->plugin_tmp_dir = $this->upload_dir . '/bulk-media-register-tmp';
		/* Make tmp dir */
		if ( ! is_dir( $this->plugin_tmp_dir ) ) {
			wp_mkdir_p( $this->plugin_tmp_dir );
		}

		/* Original hook */
		add_action( 'bmr_dir_select_box', array( $this, 'dir_select_box' ), 10, 4 );
		add_action( 'bmr_search_files', array( $this, 'search_files' ), 10, 2 );
		add_filter( 'bmr_regist', array( $this, 'regist' ), 10, 2 );
		add_action( 'bmr_delete_all_cash', array( $this, 'delete_all_cash' ) );
		add_action( 'bmr_per_page_set', array( $this, 'per_page_set' ), 10, 1 );
		add_action( 'bmr_mail_register_message', array( $this, 'mail_register_message' ), 10, 1 );

		/* Ajax */
		$action1 = 'bulkmediaregister-ajax-action';
		$action2 = 'bulkmediaregister_message';
		add_action( 'wp_ajax_' . $action1, array( $this, 'bulkmediaregister_update_callback' ) );
		add_action( 'wp_ajax_' . $action2, array( $this, 'bulkmediaregister_message_callback' ) );

		/* for robots_txt */
		add_filter( 'robots_txt', array( $this, 'custom_robots_txt' ), 9999 );
		add_filter( 'wp_get_attachment_url', array( $this, 'external_attachment_url' ), 10, 2 );
	}

	/** ==================================================
	 * Scan file
	 *
	 * @param string $dir  dir.
	 * @param string $extfilter  extfilter.
	 * @param bool   $recursive_search  recursive_search.
	 * @param string $excludes  excludes.
	 * @param string $search_text  search_text.
	 * @param string $exclude_files  exclude_files.
	 * @param array  $allowed_mimes  allowed_mimes.
	 * @param int    $uid  uid.
	 * @param int    $start_time  start_time.
	 * @param int    $exe_stop_diff_time  exe_stop_diff_time.
	 * @param array  $scan_errors  Directories that could not be read.
	 * @return array Files found during the scan.
	 * @since 1.00
	 */
	private function scan_file( $dir, $extfilter, $recursive_search, $excludes, $search_text, $exclude_files, $allowed_mimes, $uid, $start_time, $exe_stop_diff_time, &$scan_errors, &$scan_stats ) {

		$allowed_mimes = array_fill_keys( $allowed_mimes, true );
		$searches = ! empty( $search_text ) ? '/(' . preg_quote( $search_text, '/' ) . ')/i' : false;
		$directories = array( $dir );
		$list = array();
		$scan_stats = array(
			'directories_scanned' => 0,
			'extension_matches' => 0,
			'filter_matches' => 0,
			'mime_matches' => 0,
			'registered_files' => 0,
		);

		while ( ! empty( $directories ) ) {
			$current_dir = array_pop( $directories );
			try {
				$iterator = new DirectoryIterator( $current_dir );
				++$scan_stats['directories_scanned'];
				foreach ( $iterator as $fileinfo ) {
					if ( 0 < $exe_stop_diff_time && time() - $start_time >= $exe_stop_diff_time ) {
						update_user_option( $uid, 'bulkmediaregister_files_break', true );
						break 2;
					}
					if ( $fileinfo->isDot() ) {
						continue;
					}

					$fullpath = wp_normalize_path( $fileinfo->getPathname() );
					if ( $fileinfo->isDir() ) {
						if ( $recursive_search && ! $fileinfo->isLink() && preg_match( $excludes, $fullpath ) ) {
							$directories[] = $fullpath;
						}
						continue;
					}
					if ( 'all' !== $extfilter && strtolower( pathinfo( $fileinfo->getFilename(), PATHINFO_EXTENSION ) ) !== $extfilter ) {
						continue;
					}
					if ( ! $fileinfo->isFile() ) {
						continue;
					}
					++$scan_stats['extension_matches'];
					if ( ! preg_match( $excludes, $fullpath ) || ( false !== $searches && ! preg_match( $searches, $fullpath ) ) ) {
						continue;
					}
					++$scan_stats['filter_matches'];
					if ( isset( $exclude_files[ $fullpath ] ) ) {
						++$scan_stats['registered_files'];
						continue;
					}

					$check = wp_check_filetype( $fullpath );
					if ( isset( $allowed_mimes[ $check['type'] ] ) && ( 'all' === $extfilter || strtolower( $check['ext'] ) === $extfilter ) ) {
						++$scan_stats['mime_matches'];
						$list[] = $fullpath;
					}
				}
			} catch ( UnexpectedValueException $e ) {
				$scan_errors[] = $current_dir . ': ' . $e->getMessage();
			}
		}

		return $list;
	}

	/** ==================================================
	 * Recursive Directory Iterator
	 *
	 * @param string $dir  dir.
	 * @return object $iterator
	 * @since 1.31
	 */
	private function recursive_directory_iterator( $dir ) {

		try {
			$iterator = new RecursiveDirectoryIterator(
				$dir,
				FilesystemIterator::CURRENT_AS_FILEINFO |
				FilesystemIterator::KEY_AS_PATHNAME |
				FilesystemIterator::SKIP_DOTS
			);
		} catch ( UnexpectedValueException $e ) {
			echo '<div class="notice notice-error"><ul><li>' . esc_html__( 'If you see the error "Failed to open directory: No such file or directory" below, the file or directory may have been deleted from the server or the WordPress constant ABSPATH may not be set correctly. Check your ABSPATH in Site Health.', 'bulk-media-register' ) . '</li><li>' . esc_html( $e->getMessage() ) . '</li></ul></div>';
			exit;
		}

		return $iterator;
	}

	/** ==================================================
	 * Search DB
	 *
	 * @param string $dir  Directory being scanned.
	 * @return array $exclude_files  Registered file paths keyed for lookup.
	 * @since 1.10
	 */
	private function search_db_files( $dir ) {

		global $wpdb;

		$upload_dir = realpath( $this->upload_dir );
		$search_dir = realpath( $dir );
		if ( false === $upload_dir || false === $search_dir ) {
			return array();
		}

		$upload_dir = wp_normalize_path( $upload_dir );
		$search_dir = wp_normalize_path( $search_dir );
		if ( $search_dir !== $upload_dir && 0 !== strpos( trailingslashit( $search_dir ), trailingslashit( $upload_dir ) ) ) {
			return array();
		}

		$relative_dir = ltrim( substr( $search_dir, strlen( $upload_dir ) ), '/' );
		$sql = "
			SELECT meta_value
			FROM {$wpdb->prefix}postmeta
			WHERE meta_key = '_wp_attached_file'
		";
		if ( '' !== $relative_dir ) {
			$sql .= $wpdb->prepare( ' AND meta_value LIKE %s', $wpdb->esc_like( $relative_dir . '/' ) . '%' );
		}
		$files = $wpdb->get_col( $sql );

		$exclude_files = array();
		foreach ( $files as $file ) {
			$file1 = wp_normalize_path( trailingslashit( $this->upload_dir ) . $file );
			$exclude_files[ $file1 ] = true;
			if ( strpos( $file, '-scaled.' ) ) {
				$basename1 = wp_basename( $file );
				$filetype = wp_check_filetype( $basename1 );
				$scaled = '-scaled.' . $filetype['ext'];
				$file2 = rtrim( $file1, $scaled ) . '.' . $filetype['ext'];
				$exclude_files[ $file2 ] = true;
			} else if ( strpos( $file, '-rotated.' ) ) {
				$basename1 = wp_basename( $file );
				$filetype = wp_check_filetype( $basename1 );
				$rotated = '-rotated.' . $filetype['ext'];
				$file2 = rtrim( $file1, $rotated ) . '.' . $filetype['ext'];
				$exclude_files[ $file2 ] = true;
			}
		}

		return $exclude_files;
	}

	/** ==================================================
	 * Excludes strings
	 *
	 * @param int $uid  uid.
	 * @return string $excludes  excludes.
	 * @since 1.10
	 */
	private function excludes_strings( $uid ) {

		$bulkmediaregister_settings = get_user_option( 'bulkmediaregister', $uid );

		$excludes = '/^(?!.*(-[0-9]+x[0-9]+\.|bulk-media-register-tmp|media-from-ftp-tmp|';
		global $blog_id;
		if ( is_multisite() && is_main_site( $blog_id ) ) {
			$excludes .= str_replace( '/', '\/', $this->upload_dir . '/sites/' ) . '|';
		}
		if ( ! empty( $bulkmediaregister_settings['exclude'] ) ) {
			$exclude_arr = explode( '|', $bulkmediaregister_settings['exclude'] );
			foreach ( $exclude_arr as $value ) {
				$exclude1 = str_replace( '.', '\.', $value );
				$exclude2 = str_replace( '/', '\/', $exclude1 );
				$excludes .= $exclude2 . '|';
			}
		}
		$excludes = rtrim( $excludes, '|' );
		$excludes .= ')).*$/';

		return $excludes;
	}

	/** ==================================================
	 * Scan directory
	 *
	 * @param string $dir  dir.
	 * @param string $excludes  excludes.
	 * @param int    $uid  uid.
	 * @param int    $start_time  start_time.
	 * @param int    $exe_stop_diff_time  exe_stop_diff_time.
	 * @return array $dirlist
	 * @since 1.00
	 */
	private function scan_dir( $dir, $excludes, $uid, $start_time, $exe_stop_diff_time ) {

		global $blog_id;
		$multisite_top_dir = null;
		if ( is_multisite() && is_main_site( $blog_id ) ) {
			$multisite_top_dir = $this->upload_dir . '/sites';
		}

		$iterator = $this->recursive_directory_iterator( $dir );

		$iterator = new RecursiveIteratorIterator(
			$iterator,
			RecursiveIteratorIterator::SELF_FIRST
		);

		$iterator = new RegexIterator( $iterator, $excludes, RecursiveRegexIterator::MATCH );

		$list = array();
		if ( ! empty( $iterator ) ) {
			foreach ( $iterator as $fileinfo ) {
				if ( $fileinfo->isDir() ) {
					$fullpath = $fileinfo->getPathname();
					if ( $fullpath <> $multisite_top_dir ) {
						$list[] = wp_normalize_path( $fullpath );
						if ( time() - $start_time >= $exe_stop_diff_time ) {
							update_user_option( $uid, 'bulkmediaregister_dirs_break', true );
							break;
						}
					}
				}
			}
		}

		arsort( $list );
		return $list;
	}

	/** ==================================================
	 * Directory select box
	 *
	 * @param string $searchdir  searchdir.
	 * @param string $extfilter  extfilter.
	 * @param int    $uid  uid.
	 * @param string $scriptname  scriptname.
	 * @since 1.00
	 */
	public function dir_select_box( $searchdir, $extfilter, $uid, $scriptname ) {

		$bulkmediaregister_settings = get_user_option( 'bulkmediaregister', $uid );

		if ( empty( $searchdir ) ) {
			$searchdir = $this->upload_path;
		}

		$wordpress_path = wp_normalize_path( ABSPATH );
		$document_root = ! empty( $_SERVER['DOCUMENT_ROOT'] ) ? realpath( $_SERVER['DOCUMENT_ROOT'] ) : realpath( ABSPATH );
		$search_source = isset( $bulkmediaregister_settings['search_source'] ) && 'document_root' === $bulkmediaregister_settings['search_source'] ? 'document_root' : 'uploads';

		delete_user_option( $uid, 'bulkmediaregister_dirs_break' );
		@set_time_limit( $bulkmediaregister_settings['max_execution_time'] );
		$start_time = time();
		$exe_stop_diff_time = intval( ini_get( 'max_execution_time' ) * 0.8 );
		$excludes = $this->excludes_strings( $uid );
		$dirs = $this->scan_dir( $this->upload_dir, $excludes, $uid, $start_time, $exe_stop_diff_time );

		?>
		<form method="post" action="<?php echo esc_url( $scriptname ); ?>">
		<?php wp_nonce_field( 'bmr_folder', 'bulk_media_register_folder' ); ?>
		<fieldset>
			<legend><?php esc_html_e( 'Folder source', 'bulk-media-register' ); ?></legend>
			<label><input type="radio" name="search_source" value="uploads" <?php checked( 'uploads', $search_source ); ?>><?php esc_html_e( 'Choose a folder inside uploads', 'bulk-media-register' ); ?></label>
			<label><input type="radio" name="search_source" value="document_root" <?php checked( 'document_root', $search_source ); ?>><?php esc_html_e( 'Enter a path relative to the public document root', 'bulk-media-register' ); ?></label>
		</fieldset>
		<div id="bulkmediaregister-uploads-source" <?php echo 'uploads' !== $search_source ? 'hidden' : ''; ?>>
		<div style="font-size: small; font-weight: bold;"><code><?php echo esc_html( $wordpress_path ); ?></code></div>
		<select name="searchdir">
		<?php
		foreach ( $dirs as $linkdir ) {
			if ( strstr( $linkdir, $wordpress_path ) ) {
				$linkpath = $this->mb_utf8( str_replace( $wordpress_path, '', $linkdir ) );
			} else {
				$linkpath = $this->upload_path . $this->mb_utf8( str_replace( $this->upload_dir, '', $linkdir ) );
			}
			if ( $searchdir === $linkpath ) {
				?>
				<option value="<?php echo esc_attr( $linkpath ); ?>" selected><?php echo esc_html( $linkpath ); ?></option>
				<?php
			} else {
				?>
				<option value="<?php echo esc_attr( $linkpath ); ?>"><?php echo esc_html( $linkpath ); ?></option>
				<?php
			}
		}
		if ( $searchdir === $this->upload_path ) {
			?>
			<option value="" selected><?php echo esc_html( $this->upload_path ); ?></option>
			<?php
		} else {
			?>
			<option value=""><?php echo esc_html( $this->upload_path ); ?></option>
			<?php
		}
		?>
		</select>
		</div>
		<div id="bulkmediaregister-document-root-source" <?php echo 'document_root' !== $search_source ? 'hidden' : ''; ?>>
		<label for="bulkmediaregister-searchdir-document-root"><?php esc_html_e( 'Path relative to public document root:', 'bulk-media-register' ); ?></label>
		<input id="bulkmediaregister-searchdir-document-root" name="searchdir_document_root" type="text" value="<?php echo esc_attr( isset( $bulkmediaregister_settings['custom_document_root_path'] ) ? $bulkmediaregister_settings['custom_document_root_path'] : '' ); ?>" placeholder="storage/2026" />
		<p class="description"><?php esc_html_e( 'Public document root:', 'bulk-media-register' ); ?> <code><?php echo esc_html( $document_root ); ?></code></p>
		</div>
		<p class="description">
		<?php
		if ( ! empty( $bulkmediaregister_settings['recursive_search'] ) ) {
			esc_html_e( 'Recursive search is enabled; files in subfolders will be included.', 'bulk-media-register' );
		} else {
			esc_html_e( 'Recursive search is disabled; only files directly in the selected folder will be included. Enable it in Settings to include subfolders.', 'bulk-media-register' );
		}
		?>
		</p>

		<select name="extension" style="width: 120px;">
		<?php
		$extensions = $this->scan_extensions( $uid );
		if ( 'all' === $extfilter ) {
			?>
			<option value="all" selected><?php echo esc_attr( __( 'All extensions', 'bulk-media-register' ) ); ?></option>
			<?php
		} else {
			?>
			<option value="all"><?php echo esc_attr( __( 'All extensions', 'bulk-media-register' ) ); ?></option>
			<?php
		}
		foreach ( $extensions as $extselect ) {
			if ( $extfilter === $extselect ) {
				?>
				<option value="<?php echo esc_attr( $extselect ); ?>" selected><?php echo esc_html( $extselect ); ?></option>
				<?php
			} else {
				?>
				<option value="<?php echo esc_attr( $extselect ); ?>"><?php echo esc_html( $extselect ); ?></option>
				<?php
			}
		}
		?>
		</select>

		<?php submit_button( __( 'Select', 'bulk-media-register' ), 'large', 'bulk-media-register-folder', false ); ?>
		</form>
		<?php
	}

	/** ==================================================
	 * Scan extensions
	 *
	 * @param int $uid  uid.
	 * @return array $extensions
	 * @since 1.16
	 */
	private function scan_extensions( $uid ) {

		$extensions = array();
		$mimes = get_allowed_mime_types( $uid );
		foreach ( $mimes as $extselect => $mime ) {
			if ( strpos( $extselect, '|' ) ) {
				$extselects = explode( '|', $extselect );
				foreach ( $extselects as $extselect2 ) {
					$extensions[] = $extselect2;
				}
			} else {
				$extensions[] = $extselect;
			}
		}

		asort( $extensions );
		return $extensions;
	}

	/** ==================================================
	 * Search files
	 *
	 * @param int    $uid  uid.
	 * @param string $search_text  search_text.
	 * @since 1.00
	 */
	public function search_files( $uid, $search_text ) {

		$bulkmediaregister_settings = get_user_option( 'bulkmediaregister', $uid );

		$search_source = isset( $bulkmediaregister_settings['search_source'] ) ? $bulkmediaregister_settings['search_source'] : 'uploads';
		if ( 'document_root' === $search_source ) {
			$custom_path = isset( $bulkmediaregister_settings['custom_document_root_path'] ) ? $bulkmediaregister_settings['custom_document_root_path'] : '';
			$searchdir = $this->resolve_document_root_relative_directory( $custom_path );
			if ( false === $searchdir ) {
				update_user_option( $uid, 'bulkmediaregister_files', array() );
				echo '<div class="notice notice-error"><p>' . esc_html( sprintf( __( 'The custom path "%1$s" could not be resolved inside the public document root. Enter a relative path such as "storage/2026".', 'bulk-media-register' ), $custom_path ) ) . '</p></div>';
				return;
			}
		} elseif ( empty( $bulkmediaregister_settings['searchdir'] ) ) {
			$searchdir = $this->upload_dir;
		} else {
			$searchdir = ABSPATH . $bulkmediaregister_settings['searchdir'];
		}
		$extfilter = $bulkmediaregister_settings['extfilter'];
		$recursive_search = isset( $bulkmediaregister_settings['recursive_search'] ) ? (bool) $bulkmediaregister_settings['recursive_search'] : true;

		$allowed_mimes = array();
		$mimes = get_allowed_mime_types( $uid );
		foreach ( $mimes as $type => $mime ) {
			$allowed_mimes[] = $mime;
		}
		$allowed_mimes = array_unique( $allowed_mimes );
		$allowed_mimes = array_values( $allowed_mimes );

		delete_user_option( $uid, 'bulkmediaregister_files' );
		delete_user_option( $uid, 'bulkmediaregister_files_break' );
		$max_execution_time = isset( $bulkmediaregister_settings['max_execution_time'] ) ? absint( $bulkmediaregister_settings['max_execution_time'] ) : absint( ini_get( 'max_execution_time' ) );
		@set_time_limit( $max_execution_time );
		$start_time = time();
		$exe_stop_diff_time = 0 < $max_execution_time ? max( 1, intval( $max_execution_time * 0.8 ) ) : 0;
		$excludes = $this->excludes_strings( $uid );
		$exclude_files = $this->search_db_files( $searchdir );
		$scan_errors = array();
		$scan_stats = array();
		$files = $this->scan_file( $searchdir, $extfilter, $recursive_search, $excludes, $search_text, $exclude_files, $allowed_mimes, $uid, $start_time, $exe_stop_diff_time, $scan_errors, $scan_stats );
		$files_saved = update_user_option( $uid, 'bulkmediaregister_files', $files );
		$stored_files = get_user_option( 'bulkmediaregister_files', $uid );
		$stored_file_count = is_array( $stored_files ) ? count( $stored_files ) : 0;
		if ( 'all' !== $extfilter ) {
			echo '<div class="notice notice-info"><p>' . esc_html(
				sprintf(
					/* translators: 1: directories scanned, 2: extension matches, 3: files passing filters, 4: files accepted by WordPress, 5: files already registered, 6: extension, 7: files found, 8: saved file count */
					__( 'Scan diagnostics for .%6$s: %1$d directories scanned; %2$d matching files encountered; %3$d passed search filters; %4$d passed WordPress file-type checks; %5$d were already registered; %7$d files found; %8$d paths stored.', 'bulk-media-register' ),
					$scan_stats['directories_scanned'],
					$scan_stats['extension_matches'],
					$scan_stats['filter_matches'],
					$scan_stats['mime_matches'],
					$scan_stats['registered_files'],
					$extfilter,
					count( $files ),
					$stored_file_count
				)
			) . '</p></div>';
		}
		if ( count( $files ) !== $stored_file_count ) {
			echo '<div class="notice notice-error"><p>' . esc_html(
				sprintf(
					/* translators: 1: files found by scan, 2: paths read back from user options, 3: update result */
					__( 'Scan results could not be stored correctly: %1$d files found, %2$d paths read back from storage (user-option update returned %3$s).', 'bulk-media-register' ),
					count( $files ),
					$stored_file_count,
					$files_saved ? __( 'success', 'bulk-media-register' ) : __( 'false', 'bulk-media-register' )
				)
			) . '</p></div>';
		}
		if ( ! empty( $scan_errors ) ) {
			$error_count = count( $scan_errors );
			echo '<div class="notice notice-warning"><p>' . esc_html( sprintf( _n( 'Could not read %1$d directory during the scan. Files inside it were skipped.', 'Could not read %1$d directories during the scan. Files inside them were skipped.', $error_count, 'bulk-media-register' ), $error_count ) ) . '</p><ul>';
			foreach ( array_slice( $scan_errors, 0, 5 ) as $scan_error ) {
				echo '<li>' . esc_html( $scan_error ) . '</li>';
			}
			if ( 5 < $error_count ) {
				echo '<li>' . esc_html( sprintf( __( 'And %1$d more unreadable directories.', 'bulk-media-register' ), $error_count - 5 ) ) . '</li>';
			}
			echo '</ul></div>';
		}
	}

	/** ==================================================
	 * Resolve a path relative to the public document root.
	 *
	 * @param string $relative_path  Path relative to the document root.
	 * @return string|false
	 */
	private function resolve_document_root_relative_directory( $relative_path ) {

		$relative_path = wp_normalize_path( trim( $relative_path ) );
		if ( '' === $relative_path || '/' === substr( $relative_path, 0, 1 ) || preg_match( '#^[A-Za-z]:/#', $relative_path ) || in_array( '..', explode( '/', $relative_path ), true ) ) {
			return false;
		}

		$base_dir = ! empty( $_SERVER['DOCUMENT_ROOT'] ) ? realpath( $_SERVER['DOCUMENT_ROOT'] ) : realpath( ABSPATH );
		if ( false === $base_dir ) {
			return false;
		}

		$resolved_dir = realpath( trailingslashit( $base_dir ) . $relative_path );
		if ( false === $resolved_dir || ! is_dir( $resolved_dir ) || ! is_readable( $resolved_dir ) ) {
			return false;
		}

		$base_dir = wp_normalize_path( $base_dir );
		$resolved_dir = wp_normalize_path( $resolved_dir );
		if ( $base_dir !== $resolved_dir && 0 !== strpos( trailingslashit( $resolved_dir ), trailingslashit( $base_dir ) ) ) {
			return false;
		}

		return $resolved_dir;
	}

	/**
	 * Check whether a canonical path is inside uploads, WordPress, or the document root.
	 *
	 * @param string $path Canonical filesystem path.
	 * @return bool
	 */
	private function path_is_inside_allowed_roots( $path, $additional_root = false ) {

		$roots = array( $this->upload_dir, ABSPATH );
		if ( $additional_root ) {
			$roots[] = $additional_root;
		}
		if ( ! empty( $_SERVER['DOCUMENT_ROOT'] ) ) {
			$roots[] = $_SERVER['DOCUMENT_ROOT'];
		}

		foreach ( $roots as $root ) {
			$root = realpath( $root );
			if ( false === $root ) {
				continue;
			}
			$root = wp_normalize_path( $root );
			if ( '/' !== $root && ( $path === $root || 0 === strpos( trailingslashit( $path ), trailingslashit( $root ) ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get a path relative to the uploads directory.
	 *
	 * @param string $file Canonical filesystem path.
	 * @return string|false
	 */
	private function upload_relative_file_path( $file ) {

		$base_parts = explode( '/', trim( wp_normalize_path( realpath( $this->upload_dir ) ), '/' ) );
		$file_parts = explode( '/', trim( wp_normalize_path( realpath( $file ) ), '/' ) );
		$common_parts = 0;
		$limit = min( count( $base_parts ), count( $file_parts ) );
		while ( $common_parts < $limit && $base_parts[ $common_parts ] === $file_parts[ $common_parts ] ) {
			++$common_parts;
		}

		return str_repeat( '../', count( $base_parts ) - $common_parts ) . implode( '/', array_slice( $file_parts, $common_parts ) );
	}

	/**
	 * Build the public URL for a file beneath uploads or the web document root.
	 *
	 * @param string $file Canonical filesystem path.
	 * @return string
	 */
	public function public_file_url( $file, $document_root = false ) {

		$file = wp_normalize_path( realpath( $file ) );
		$uploads_root = wp_normalize_path( realpath( $this->upload_dir ) );
		if ( $uploads_root === $file || 0 === strpos( trailingslashit( $file ), trailingslashit( $uploads_root ) ) ) {
			return trailingslashit( $this->upload_url ) . ltrim( $this->upload_relative_file_path( $file ), '/' );
		}

		$document_root = $document_root ? realpath( $document_root ) : ( ! empty( $_SERVER['DOCUMENT_ROOT'] ) ? realpath( $_SERVER['DOCUMENT_ROOT'] ) : false );
		if ( false === $document_root ) {
			return trailingslashit( $this->upload_url ) . $this->upload_relative_file_path( $file );
		}

		$document_root = wp_normalize_path( $document_root );
		if ( $document_root !== $file && 0 !== strpos( trailingslashit( $file ), trailingslashit( $document_root ) ) ) {
			return trailingslashit( $this->upload_url ) . $this->upload_relative_file_path( $file );
		}
		$relative_path = ltrim( substr( $file, strlen( trailingslashit( $document_root ) ) ), '/' );
		$site_url_parts = wp_parse_url( home_url( '/' ) );
		$origin = $site_url_parts['scheme'] . '://' . $site_url_parts['host'];
		if ( ! empty( $site_url_parts['port'] ) ) {
			$origin .= ':' . $site_url_parts['port'];
		}
		$encoded_path = implode( '/', array_map( 'rawurlencode', explode( '/', $relative_path ) ) );

		return trailingslashit( $origin ) . $encoded_path;
	}

	/**
	 * Use the web-root URL saved for externally stored attachments.
	 *
	 * @param string $url Attachment URL.
	 * @param int    $attach_id Attachment ID.
	 * @return string
	 */
	public function external_attachment_url( $url, $attach_id ) {

		$stored_url = get_post_meta( $attach_id, '_bulkmediaregister_external_url', true );
		if ( $stored_url ) {
			return $stored_url;
		}

		$attached_file = get_attached_file( $attach_id );
		$real_file = $attached_file ? realpath( $attached_file ) : false;
		if ( false === $real_file || ! is_file( $real_file ) ) {
			return $url;
		}

		$real_file = wp_normalize_path( $real_file );
		$author_id = (int) get_post_field( 'post_author', $attach_id );
		$settings = get_user_option( 'bulkmediaregister', $author_id );
		$document_root = isset( $settings['custom_document_root_base'] ) ? $settings['custom_document_root_base'] : false;
		if ( ! $this->path_is_inside_allowed_roots( $real_file, $document_root ) ) {
			return $url;
		}

		$uploads_root = wp_normalize_path( realpath( $this->upload_dir ) );
		if ( $uploads_root === $real_file || 0 === strpos( trailingslashit( $real_file ), trailingslashit( $uploads_root ) ) ) {
			return $url;
		}

		return $this->public_file_url( $real_file, $document_root );
	}

	/** ==================================================
	 * Multibyte UTF-8
	 *
	 * @param string $str  str.
	 * @return string $str
	 * @since 1.00
	 */
	private function mb_utf8( $str ) {

		if ( function_exists( 'mb_convert_encoding' ) ) {
			$encoding = implode( ',', mb_list_encodings() );
			$str = mb_convert_encoding( $str, 'UTF-8', $encoding );
		}

		return $str;
	}

	/** ==================================================
	 * Regist
	 *
	 * @param array $file  file.
	 * @param int   $uid  uid.
	 * @since 1.00
	 */
	public function regist( $file, $uid ) {

		$bulkmediaregister_settings = get_user_option( 'bulkmediaregister', $uid );

		if ( function_exists( 'wp_date' ) ) {
			$now_date_time = wp_date( 'Y-m-d H:i:s' );
		} else {
			$now_date_time = date_i18n( 'Y-m-d H:i:s' );
		}

		$filetype = wp_check_filetype( $file );
		$ext = $filetype['ext'];
		$mime_type = $filetype['type'];
		$file_type = wp_ext2type( $ext );
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
		$wp_filesystem = new WP_Filesystem_Direct( false );

		$title = wp_basename( $file, '.' . $ext );
		/* for utf8mb4 charcter */
		$title = $this->utf8mb4_html_numeric_encode( $title );
		$filename_org = wp_basename( $file );
		$foldername_org = rtrim( $file, $filename_org );
		$filename = sanitize_file_name( $filename_org );
		if ( $filename <> $filename_org ) {
			$file_org = $file;
			$file = $foldername_org . $filename;
			$wp_filesystem->move( $file_org, $file );
		}
		$document_root = isset( $bulkmediaregister_settings['custom_document_root_base'] ) ? $bulkmediaregister_settings['custom_document_root_base'] : false;
		$new_url_attach = $this->public_file_url( $file, $document_root );

		/* File Regist */
		$newfile_post = array(
			'post_title' => $title,
			'post_content' => '',
			'post_author' => $uid,
			'guid' => $new_url_attach,
			'post_status' => 'inherit',
			'post_type' => 'attachment',
			'post_mime_type' => $mime_type,
		);
		$attach_id = wp_insert_attachment( $newfile_post, $file );

		$metapath_name = $this->upload_relative_file_path( $file );
		update_post_meta( $attach_id, '_wp_attached_file', $metapath_name );
		$uploads_root = wp_normalize_path( realpath( $this->upload_dir ) );
		$real_file = wp_normalize_path( realpath( $file ) );
		if ( $uploads_root !== $real_file && 0 !== strpos( trailingslashit( $real_file ), trailingslashit( $uploads_root ) ) ) {
			update_post_meta( $attach_id, '_bulkmediaregister_external_url', $new_url_attach );
		}

		/* Date Time Regist */
		if ( function_exists( 'wp_date' ) ) {
			$postdategmt = wp_date( 'Y-m-d H:i:s', null, new DateTimeZone( 'UTC' ) );
		} else {
			$postdategmt = date_i18n( 'Y-m-d H:i:s', false, true );
		}
		if ( 'server' === $bulkmediaregister_settings['dateset'] ) {
			$datetime = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', filemtime( $file ) ) );
			$postdategmt = get_gmt_from_date( $datetime );
		}
		if ( 'new' <> $bulkmediaregister_settings['dateset'] ) {
			if ( 'fixed' === $bulkmediaregister_settings['dateset'] ) {
				$postdategmt = get_gmt_from_date( $bulkmediaregister_settings['datefixed'] );
			}
			$postdate = get_date_from_gmt( $postdategmt );
			$up_post = array(
				'ID' => $attach_id,
				'post_date' => $postdate,
				'post_date_gmt' => $postdategmt,
				'post_modified' => $postdate,
				'post_modified_gmt' => $postdategmt,
			);
			wp_update_post( $up_post );
		}

		/* for wp_read_audio_metadata and wp_read_video_metadata */
		include_once ABSPATH . 'wp-admin/includes/media.php';
		/* for wp_generate_attachment_metadata */
		include_once ABSPATH . 'wp-admin/includes/image.php';

		/* Meta data Regist */
		@set_time_limit( 300 );
		$metadata = wp_generate_attachment_metadata( $attach_id, $file );
		if ( ! empty( $metadata ) && is_array( $metadata ) ) {
			$metadata['file'] = $metapath_name;
		}
		/* for 'big_image_size_threshold' and 'wp_image_maybe_exif_rotate' */
		if ( ! empty( $metadata ) && array_key_exists( 'original_image', $metadata ) && ! empty( $metadata['original_image'] ) ) {
			$metapath_scaled_rotated_file_name = str_replace( $this->upload_dir . '/', '', $metadata['file'] );
			update_post_meta( $attach_id, '_wp_attached_file', $metapath_scaled_rotated_file_name );
			$metadata['file'] = $metapath_scaled_rotated_file_name;
		}
		wp_update_attachment_metadata( $attach_id, $metadata );

		/* Thumbnail urls */
		list( $image_thumbnail, $imagethumburls ) = $this->thumbnail_urls( $attach_id, $metadata );
		/* Output datas*/
		list( $attachment_link, $attachment_url, $original_image_url, $original_filename, $stamptime, $file_size, $length ) = $this->output_datas( $attach_id, $metadata, $file_type, $file );

		$bulkmediaregister_output = array(
			'title' => $title,
			'attach_id' => $attach_id,
			'image_thumbnail' => $image_thumbnail,
			'imagethumburls' => $imagethumburls,
			'attachment_link' => $attachment_link,
			'attachment_url' => $attachment_url,
			'filename' => $filename,
			'original_image_url' => $original_image_url,
			'original_filename' => $original_filename,
			'mime_type' => $mime_type,
			'ext' => $ext,
			'file_type' => $file_type,
			'stamptime' => $stamptime,
			'file_size' => $file_size,
			'length' => $length,
		);

		if ( $bulkmediaregister_settings['mail_send'] ) {
			$messages = get_user_option( 'bulkmediaregister_messages', $uid );
			if ( ! $messages ) {
				$messages = array();
			}
			$csvs = get_user_option( 'bulkmediaregister_csvs', $uid );
			if ( ! $csvs ) {
				$csvs[0] = array(
					'ID',
					__( 'Title', 'bulk-media-register' ),
					__( 'Permalink:', 'bulk-media-register' ),
					'URL',
					__( 'File name:', 'bulk-media-register' ),
					__( 'Original URL:', 'bulk-media-register' ),
					__( 'Original File name:', 'bulk-media-register' ),
					__( 'Date/Time', 'bulk-media-register' ),
					__( 'File type:', 'bulk-media-register' ),
					__( 'File size:', 'bulk-media-register' ),
					__( 'Length:', 'bulk-media-register' ),
				);
			}
			list( $messages, $csvs ) = $this->mail_messages( $messages, $csvs, $bulkmediaregister_output );
			update_user_option( $uid, 'bulkmediaregister_messages', $messages );
			update_user_option( $uid, 'bulkmediaregister_csvs', $csvs );
		}

		return $bulkmediaregister_output;
	}

	/** ==================================================
	 * Mail messages
	 *
	 * @param array $messages  messages.
	 * @param array $csvs  csvs.
	 * @param array $output output args.
	 *
	 * @since 1.30
	 */
	private function mail_messages( $messages, $csvs, $output ) {

		if ( $output ) {

			$original_image_url = null;
			$original_filename = null;
			$length = null;
			$img_url = array();

			$message = 'ID: ' . $output['attach_id'] . "\n";
			$message .= __( 'Title', 'bulk-media-register' ) . ': ' . $output['title'] . "\n";
			$message .= __( 'Permalink:', 'bulk-media-register' ) . ' ' . $output['attachment_link'] . "\n";
			$message .= 'URL: ' . $output['attachment_url'] . "\n";
			$message .= __( 'File name:', 'bulk-media-register' ) . ' ' . $output['filename'] . "\n";
			if ( ! empty( $output['original_image_url'] ) ) {
				$message .= __( 'Original URL:', 'bulk-media-register' ) . ' ' . $output['original_image_url'] . "\n";
				$message .= __( 'Original File name:', 'bulk-media-register' ) . ' ' . $output['original_filename'] . "\n";
				$original_image_url = $output['original_image_url'];
				$original_filename = $output['original_filename'];
			}
			$message .= __( 'Date/Time', 'bulk-media-register' ) . ': ' . $output['stamptime'] . "\n";
			$message .= __( 'File type:', 'bulk-media-register' ) . ' ' . $output['mime_type'] . "\n";
			$message .= __( 'File size:', 'bulk-media-register' ) . ' ' . $output['file_size'] . "\n";
			if ( ( 'image' === $output['file_type'] || 'pdf' === strtolower( $output['ext'] ) ) && ! empty( $output['imagethumburls'] ) ) {
				if ( ! empty( $output['imagethumburls'] ) ) {
					$message .= __( 'Images', 'bulk-media-register' ) . ': ' . "\n";
					foreach ( $output['imagethumburls'] as $thumbsize => $imagethumburl ) {
						$message .= $thumbsize . ': ' . $imagethumburl . "\n";
						$img_url[] = $imagethumburl;
					}
				}
			} elseif ( 'video' === $output['file_type'] || 'audio' === $output['file_type'] ) {
					$message .= __( 'Length:', 'bulk-media-register' ) . ' ' . $output['length'] . "\n";
					$length = $output['length'];
			}
			$message .= "\n";
			if ( function_exists( 'mb_convert_encoding' ) ) {
				$message = mb_convert_encoding( $message, 'UTF-8', 'auto' );
			}
			$messages[] = $message;

			$csv = array(
				$output['attach_id'],
				$output['title'],
				$output['attachment_link'],
				$output['attachment_url'],
				$output['filename'],
				$original_image_url,
				$original_filename,
				$output['stamptime'],
				$output['mime_type'],
				$output['file_size'],
				$length,
			);
			foreach ( $img_url as $value ) {
				array_push( $csv, $value );
			}

			$csvs[] = $csv;
		}

		return array( $messages, $csvs );
	}

	/** ==================================================
	 * Mail sent for Register Message
	 *
	 * @param int $uid  User ID.
	 * @since 1.30
	 */
	public function mail_register_message( $uid ) {

		$bulkmediaregister_settings = get_user_option( 'bulkmediaregister', $uid );
		if ( $bulkmediaregister_settings['mail_send'] ) {

			if ( function_exists( 'wp_date' ) ) {
				$now_date_time = wp_date( 'Y-m-d H:i:s' );
			} else {
				$now_date_time = date_i18n( 'Y-m-d H:i:s' );
			}

			$messages = get_user_option( 'bulkmediaregister_messages', $uid );

			$csvs = get_user_option( 'bulkmediaregister_csvs', $uid );
			$max_col_count = 0;
			foreach ( $csvs as $value ) {
				if ( $max_col_count < count( $value ) ) {
					$max_col_count = count( $value );
				}
			}
			$start_comma = count( $csvs[0] );
			$plus_comma = $max_col_count - count( $csvs[0] );
			for ( $i = $start_comma; $i < $start_comma + $plus_comma; $i++ ) {
				$csvs[0][ $i ] = __( 'Images', 'bulk-media-register' ) . strval( $i - $start_comma + 1 );
			}

			$csv_filename = sanitize_file_name( 'registered' . $now_date_time . '.csv' );
			$csv_file = wp_normalize_path( $this->plugin_tmp_dir . '/' ) . $csv_filename;

			$filter = null;
			if ( get_locale() == 'ja' ) {
				$filter = 'php://filter/write=' . urlencode( 'convert.iconv.utf-8/cp932//TRANSLIT' ) . '/resource=';
			}
			$file = new SplFileObject( $filter . $csv_file, 'a' );
			foreach ( $csvs as $line ) {
				$file->fputcsv( $line );
			}
			$file = null;

			if ( $messages ) {
				/* translators: Date and Time */
				$message_head = sprintf( __( 'Bulk Media Register : %s', 'bulk-media-register' ), $now_date_time ) . "\r\n\r\n";
				/* translators: count of media */
				$message_head .= sprintf( __( '%1$d media were registered. Attached the registration data as a CSV file[%2$s].', 'bulk-media-register' ), count( $messages ), $csv_filename ) . "\r\n\r\n";

				$to = get_userdata( $uid )->user_email;
				/* translators: blogname for subject */
				$subject = sprintf( __( '[%s] Media Register', 'bulk-media-register' ), get_option( 'blogname' ) );
				wp_mail( $to, $subject, $message_head . implode( $messages ), null, $csv_file );
				delete_user_option( $uid, 'bulkmediaregister_messages' );
				delete_user_option( $uid, 'bulkmediaregister_csvs' );
				wp_delete_file( $csv_file );
			}
		}
	}

	/** ==================================================
	 * Thumbnail urls
	 *
	 * @param int    $attach_id  attach_id.
	 * @param array  $metadata  metadata.
	 * @return array $image_thumbnail(string), $imagethumburls(array)
	 * @since 1.01
	 */
	private function thumbnail_urls( $attach_id, $metadata ) {

		$image_attr_thumbnail = wp_get_attachment_image_src( $attach_id, 'thumbnail', true );
		$image_thumbnail = $image_attr_thumbnail[0];

		$imagethumburls = array();
		if ( ! empty( $metadata ) && array_key_exists( 'sizes', $metadata ) ) {
			$thumbnails  = $metadata['sizes'];
			$media_url  = trailingslashit( dirname( wp_get_attachment_url( $attach_id ) ) );
			foreach ( $thumbnails as $key => $key2 ) {
				$imagethumburls[ $key ] = $media_url . $key2['file'];
			}
		}

		return array( $image_thumbnail, $imagethumburls );
	}

	/** ==================================================
	 * Output datas
	 *
	 * @param int    $attach_id  attach_id.
	 * @param array  $metadata  metadata.
	 * @param string $file_type  file_type.
	 * @param string $file  fullpath_filename.
	 * @return array (string) $attachment_link, $attachment_url, $original_image_url, $original_filename, $stamptime, $file_size, $length
	 * @since 1.01
	 */
	private function output_datas( $attach_id, $metadata, $file_type, $file ) {

		$attachment_link = get_attachment_link( $attach_id );

		$attachment_url = wp_get_attachment_url( $attach_id );

		if ( ! empty( $metadata ) && array_key_exists( 'original_image', $metadata ) && ! empty( $metadata['original_image'] ) ) {
			$original_image_url = wp_get_original_image_url( $attach_id );
			$original_filename = wp_basename( $original_image_url );
		} else {
			$original_image_url = null;
			$original_filename = null;
		}

		$stamptime = get_the_time( 'Y-n-j ', $attach_id ) . get_the_time( 'G:i:s', $attach_id );

		if ( ! empty( $metadata ) && array_key_exists( 'filesize', $metadata ) && ! empty( $metadata['filesize'] ) ) {
			$file_size = $metadata['filesize'];
		} else {
			$file_size = @filesize( $file );
		}
		if ( ! $file_size ) {
			$file_size = __( 'Could not retrieve.', 'bulk-media-register' );
		} else {
			$file_size = size_format( $file_size );
		}

		$length = null;
		if ( 'video' === $file_type || 'audio' === $file_type ) {
			if ( ! empty( $metadata ) && array_key_exists( 'length_formatted', $metadata ) && ! empty( $metadata['length_formatted'] ) ) {
				$length = $metadata['length_formatted'];
			} else {
				$length = __( 'Could not retrieve.', 'bulk-media-register' );
			}
		}

		return array( $attachment_link, $attachment_url, $original_image_url, $original_filename, $stamptime, $file_size, $length );
	}

	/** ==================================================
	 * Files Callback
	 *
	 * @since 1.00
	 */
	public function bulkmediaregister_update_callback() {

		$action1 = 'bulkmediaregister-ajax-action';
		if ( check_ajax_referer( $action1, 'nonce', false ) ) {
			if ( current_user_can( 'upload_files' ) ) {
				if ( ! empty( $_POST['file'] ) ) {
					$raw_file = sanitize_text_field( wp_unslash( $_POST['file'] ) );
					/* Use `realpath()` to normalize the path to an absolute path and check whether it is within the upload directory */
					$real_file = realpath( $raw_file );
					$uid = get_current_user_id();
					$settings = get_user_option( 'bulkmediaregister', $uid );
					$document_root = isset( $settings['custom_document_root_base'] ) ? $settings['custom_document_root_base'] : false;
					if ( ! $real_file || ! is_file( $real_file ) || ! is_readable( $real_file ) || ! $this->path_is_inside_allowed_roots( wp_normalize_path( $real_file ), $document_root ) ) {
						status_header( '403' );
						echo 'Forbidden';
						wp_die();
					}

					/* Files whose names begin with a period (such as .htaccess) are rejected because they can be exploited for access control purposes. */
					$basename = wp_basename( $real_file );
					if ( '.' === substr( $basename, 0, 1 ) ) {
						status_header( '403' );
						echo 'Forbidden';
						wp_die();
					}

					$file = $real_file;
				} else {
					return;
				}
				/* Do not use an external UID; enforce the currently logged-in user ID. */
				if ( is_file( $file ) ) {
					$output = apply_filters( 'bmr_regist', $file, $uid );

					$output_html = '<div style="border-bottom: 1px solid; padding-top: 5px; padding-bottom: 5px;">';
					$output_html .= '<img width="40" height="40" src="' . $output['image_thumbnail'] . '" style="float: left; margin: 5px;">';
					$output_html .= '<div style="overflow: hidden;">';
					$output_html .= '<div>ID: ' . $output['attach_id'] . '</div>';
					$output_html .= '<div>' . __( 'Title', 'bulk-media-register' ) . ': ' . $output['title'] . '</div>';
					$output_html .= '<div>' . __( 'Permalink:', 'bulk-media-register' ) . ' <a href="' . $output['attachment_link'] . '" target="_blank" rel="noopener noreferrer" style="text-decoration: none; word-break: break-all;">' . $output['attachment_link'] . '</a></div>';
					$output_html .= '<div>URL: <a href="' . $output['attachment_url'] . '" target="_blank" rel="noopener noreferrer" style="text-decoration: none; word-break: break-all;">' . $output['attachment_url'] . '</a></div>';
					$output_html .= '<div>' . __( 'File name:', 'bulk-media-register' ) . ' ' . $output['filename'] . '</div>';
					if ( ! empty( $output['original_image_url'] ) ) {
						$output_html .= '<div>' . __( 'Original URL', 'bulk-media-register' ) . ': <a href="' . $output['original_image_url'] . '" target="_blank" rel="noopener noreferrer" style="text-decoration: none; word-break: break-all;">' . $output['original_image_url'] . '</a></div>';
						$output_html .= '<div>' . __( 'Original File name', 'bulk-media-register' ) . ': ' . $output['original_filename'] . '</div>';
					}
					$output_html .= '<div>' . __( 'Date/Time', 'bulk-media-register' ) . ': ' . $output['stamptime'] . '</div>';
					$output_html .= '<div>' . __( 'File type:', 'bulk-media-register' ) . ' ' . $output['mime_type'] . '</div>';
					$output_html .= '<div>' . __( 'File size:', 'bulk-media-register' ) . ' ' . $output['file_size'] . '</div>';
					if ( ( 'image' === $output['file_type'] || 'pdf' === strtolower( $output['ext'] ) ) && ! empty( $output['imagethumburls'] ) ) {
						$output_html .= '<div>' . __( 'Images', 'bulk-media-register' ) . ': ';
						foreach ( $output['imagethumburls'] as $thumbsize => $imagethumburl ) {
							$output_html .= '[<a href="' . $imagethumburl . '" target="_blank" rel="noopener noreferrer" style="text-decoration: none; word-break: break-all;">' . $thumbsize . '</a>]';
						}
						$output_html .= '</div>';
					} elseif ( 'video' === $output['file_type'] || 'audio' === $output['file_type'] ) {
							$output_html .= '<div>' . __( 'Length:', 'bulk-media-register' ) . ' ' . $output['length'] . '</div>';
					}
					$output_html .= '</div></div>';
				} else {
					$error_string = __( 'No file!', 'bulk-media-register' );
					$output_html = '<div>' . esc_html( $file ) . ': <span style="color: red;">' . esc_html( $error_string ) . '</span></div>';
				}

				header( 'Content-type: text/html; charset=UTF-8' );
				$allowed_output_html = array(
					'a'   => array(
						'href' => array(),
						'target' => array(),
						'rel' => array(),
						'style' => array(),
					),
					'img'   => array(
						'src' => array(),
						'width' => array(),
						'height' => array(),
						'style' => array(),
					),
					'div'   => array(
						'style' => array(),
						'class' => array(),
					),
					'font'   => array(
						'color' => array(),
					),
					'ul' => array(),
					'li' => array(),
					'span'   => array(
						'class' => array(),
						'style' => array(),
					),
				);
				echo wp_kses( $output_html, $allowed_output_html );
			}
		} else {
			status_header( '403' );
			echo 'Forbidden';
		}

		wp_die();
	}

	/** ==================================================
	 * Messages Callback
	 *
	 * @since 1.00
	 */
	public function bulkmediaregister_message_callback() {

		$action2 = 'bulkmediaregister_message';
		if ( check_ajax_referer( $action2, 'nonce', false ) ) {
			$error_count = 0;
			$error_update = null;
			$success_count = 0;
			$uid = 0;
			if ( ! empty( $_POST['error_count'] ) ) {
				$error_count = absint( $_POST['error_count'] );
			}
			if ( ! empty( $_POST['error_update'] ) ) {
				$error_update = sanitize_text_field( wp_unslash( $_POST['error_update'] ) );
			}
			if ( ! empty( $_POST['success_count'] ) ) {
				$success_count = absint( $_POST['success_count'] );
			}
			/* Do not use an external UID; enforce the currently logged-in user ID. */
			$uid = get_current_user_id();

			$output_html = null;
			if ( $error_count > 0 ) {
				/* translators: error message %1$d files count */
				$error_message = sprintf( __( 'Errored to the registration of %1$d files.', 'bulk-media-register' ), $error_count );
				$output_html .= '<div class="notice notice-error is-dismissible"><ul><li>' . $error_message . ' : ' . $error_update . '</li></ul></div>';
			}
			if ( $success_count > 0 ) {
				/* translators: success message %1$d files count */
				$success_message = sprintf( __( 'Succeeded to the registration of %1$d files for Media Library.', 'bulk-media-register' ), $success_count );
				$output_html .= '<div class="notice notice-success is-dismissible"><ul><li>' . $success_message . '</li></ul></div>';
				do_action( 'bmr_mail_register_message', $uid );
			}

			header( 'Content-type: text/html; charset=UTF-8' );
			$allowed_output_html = array(
				'div'   => array(
					'class' => array(),
				),
				'ul' => array(),
				'li' => array(),
			);
			echo wp_kses( $output_html, $allowed_output_html );
		}

		wp_die();
	}

	/** ==================================================
	 * Delete all cache
	 *
	 * @since 1.10
	 */
	public function delete_all_cash() {

		global $wpdb;
		$del_transients = $wpdb->get_results(
			$wpdb->prepare(
				"
						SELECT	option_value
						FROM	{$wpdb->prefix}options
						WHERE	option_value LIKE %s
						",
				'%' . $wpdb->esc_like( $this->plugin_tmp_url ) . '%'
			)
		);

		$del_cash_count = 0;
		foreach ( $del_transients as $del_transient ) {
			$delfile = pathinfo( $del_transient->option_value );
			$del_cash_thumb_key = $delfile['filename'];
			$value_del_cash = get_transient( $del_cash_thumb_key );
			if ( false <> $value_del_cash ) {
				delete_transient( $del_cash_thumb_key );
				++$del_cash_count;
			}
		}

		$del_cash_thumb_filename = $this->plugin_tmp_dir . '/*';
		foreach ( glob( $del_cash_thumb_filename ) as $val ) {
			wp_delete_file( $val );
			++$del_cash_count;
		}

		update_option( 'bulkmediaregister_cash', $del_cash_count );
	}

	/** ==================================================
	 * UTF8 mb4
	 *
	 * @param string $str  str.
	 * @return string $ret
	 * @since 1.00
	 */
	private function utf8mb4_html_numeric_encode( $str ) {

		if ( function_exists( 'mb_language' ) ) {
			$length = mb_strlen( $str, 'UTF-8' );
			$ret = '';

			for ( $i = 0; $i < $length; ++$i ) {
				$buf = mb_substr( $str, $i, 1, 'UTF-8' );

				if ( 4 === mb_strlen( $buf, '8bit' ) ) {
					$buf = mb_encode_numericentity( $buf, array( 0x10000, 0x10FFFF, 0, 0xFFFFFF ), 'UTF-8' );
				}

				$ret .= $buf;
			}
		} else {
			$ret = $str;
		}

		return $ret;
	}

	/** ==================================================
	 * Upload Path
	 *
	 * @return array $upload_dir,$upload_url,$upload_path  uploadpath.
	 * @since 1.00
	 */
	private function upload_dir_url_path() {

		$wp_uploads = wp_upload_dir();

		$relation_path_true = strpos( $wp_uploads['baseurl'], '../' );
		if ( $relation_path_true > 0 ) {
			$relationalpath = substr( $wp_uploads['baseurl'], $relation_path_true );
			$basepath       = substr( $wp_uploads['baseurl'], 0, $relation_path_true );
			$upload_url     = $this->realurl( $basepath, $relationalpath );
			$upload_dir     = wp_normalize_path( realpath( $wp_uploads['basedir'] ) );
		} else {
			$upload_url = $wp_uploads['baseurl'];
			$upload_dir = wp_normalize_path( $wp_uploads['basedir'] );
		}

		if ( is_ssl() ) {
			$upload_url = str_replace( 'http:', 'https:', $upload_url );
		}

		if ( $relation_path_true > 0 ) {
			$upload_path = $relationalpath;
		} else {
			$upload_path = str_replace( site_url( '/' ), '', $upload_url );
		}

		$upload_dir  = untrailingslashit( $upload_dir );
		$upload_url  = untrailingslashit( $upload_url );
		$upload_path = untrailingslashit( $upload_path );

		return array( $upload_dir, $upload_url, $upload_path );
	}

	/** ==================================================
	 * Real Url
	 *
	 * @param  string $base  base.
	 * @param  string $relationalpath relationalpath.
	 * @return string $realurl realurl.
	 * @since  1.00
	 */
	private function realurl( $base, $relationalpath ) {

		$parse = array(
			'scheme'   => null,
			'user'     => null,
			'pass'     => null,
			'host'     => null,
			'port'     => null,
			'query'    => null,
			'fragment' => null,
		);
		$parse = wp_parse_url( $base );

		if ( strpos( $parse['path'], '/', ( strlen( $parse['path'] ) - 1 ) ) !== false ) {
			$parse['path'] .= '.';
		}

		if ( preg_match( '#^https?://#', $relationalpath ) ) {
			return $relationalpath;
		} elseif ( preg_match( '#^/.*$#', $relationalpath ) ) {
			return $parse['scheme'] . '://' . $parse['host'] . $relationalpath;
		} else {
			$base_path = explode( '/', dirname( $parse['path'] ) );
			$rel_path  = explode( '/', $relationalpath );
			foreach ( $rel_path as $rel_dir_name ) {
				if ( '.' === $rel_dir_name ) {
					array_shift( $base_path );
					array_unshift( $base_path, '' );
				} elseif ( '..' === $rel_dir_name ) {
					array_pop( $base_path );
					if ( count( $base_path ) === 0 ) {
						$base_path = array( '' );
					}
				} else {
					array_push( $base_path, $rel_dir_name );
				}
			}
			$path = implode( '/', $base_path );
			return $parse['scheme'] . '://' . $parse['host'] . $path;
		}
	}

	/** ==================================================
	 * Robots txt
	 *
	 * @param string $output  output.
	 * @return string $output  output.
	 * @since 1.12
	 */
	public function custom_robots_txt( $output ) {

		$public = get_option( 'blog_public' );
		if ( '0' != $public ) {
			if ( is_multisite() ) {
				global $blog_id;
				$siteurl = get_blog_details( $blog_id )->siteurl;
			} else {
				$siteurl = site_url();
			}
			$plugin_disallow_tmp_dir = str_replace( home_url(), '', $siteurl ) . '/' . $this->upload_path . '/bulk-media-register-tmp/';
			$output .= "\n" . 'Disallow: ' . $plugin_disallow_tmp_dir . "\n";
		}

		return $output;
	}

	/** ==================================================
	 * Per page input
	 *
	 * @param int $uid  user ID.
	 * @since 1.24
	 */
	public function per_page_set( $uid ) {

		$bulkmediaregister_settings = get_user_option( 'bulkmediaregister', $uid );
		?>
		<div style="margin: 0px; text-align: right;">
			<?php esc_html_e( 'Number of items per page:', 'bulk-media-register' ); ?><input type="number" step="1" min="1" max="9999" style="width: 80px;" name="per_page" value="<?php echo esc_attr( $bulkmediaregister_settings['per_page'] ); ?>" form="select_media_register_per_page_forms" />
			<?php submit_button( __( 'Change', 'bulk-media-register' ), 'large', 'per_page_change', false, array( 'form' => 'select_media_register_per_page_forms' ) ); ?>
		</div>
		<?php
	}
}
