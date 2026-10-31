=== Bulk Media Register ===
Contributors: Katsushi Kawamori
Donate link: https://shop.riverforest-wp.info/donate/
Tags: files, ftp, import, media, uploads
Requires at least: 5.3
Requires PHP: 8.0
Tested up to: 7.1
Stable tag: 2.01
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Bulk register files on the server to the Media Library.

== Description ==

Bulk register files on the server to the Media Library.

= Register to media library =
* Maintain folder structure.
* This create a thumbnail of the image file.
* This create a metadata(Images, Videos, Audios).
* Change the date/time.
* Register via the wp cron event.
* Regsiter via the Command Line

= Schedule for wp cron event=
* Can add intervals of schedule.

= Sibling plugin =
* [Moving Media Library](https://wordpress.org/plugins/moving-media-library/).
* [Media from FTP](https://wordpress.org/plugins/media-from-ftp/).
* [Media from ZIP](https://wordpress.org/plugins/media-from-zip/).

= Note =
* If you want to use a multi-byte file name, use UTF-8. The file name is used as the title during registration, but is sanitized and changed to a different file name.

= How it works =
[youtube https://youtu.be/Va92SMlFDxk]

== Installation ==

1. Upload `bulk-media-register` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

none

== Screenshots ==

1. Search for Bulk Register
2. Bulk Register
3. Select Register
4. Register to Media Library
5. Settings
6. Cron Event

== Changelog ==

= [2.01] 2026/10/28 =
* Fix - Security fix for path traversal and file handling vulnerability (Thanks to Rafael Honorato).

= [2.00] 2026/10/21 =
* Added - Register via the wp cron event.
* Added - Register via the Command Line.

= [1.41] 2025/04/16 =
* Fix - Loading the management screen.

= [1.40] 2024/03/03 =
* Fix - Added nonce when sorting.
* Fix - Changed file operations to WP_Filesystem.

= 1.39 =
Fixed CSV file output issues.

= 1.38 =
Fixed CSV file output issues.

= 1.37 =
Fixed CSV file output issues.

= 1.36 =
Fixed a problem with sending e-mail.

= 1.35 =
Supported WordPress 6.4.
PHP 8.0 is now required.

= 1.34 =
Fixed translation.

= 1.33 =
The result CSV file is now attached to the registration result email.

= 1.32 =
Fixed problem of Path traversal.

= 1.31 =
Added exception handling for directory and file searches.
An option to send email or not has been added to the settings.

= 1.30 =
Registration results are now sent via email.
Fixed a path problem when searching for files.

= 1.26 =
"Exclude file & folder" saving issue fixed.
Removed code related to version checking.

= 1.25 =
Fixed a timeout problem during registration on poor servers.

= 1.24 =
Fixed a pagination problem when searching for text.
Moved the position of the pagination setting.

= 1.23 =
Added error handling when a file is not selected.
Fixed translation.

= 1.22 =
Fixed an issue that caused rotated files to be searched again.

= 1.21 =
Fixed uninstall.

= 1.20 =
Supported XAMPP.

= 1.19 =
Supported XAMPP.

= 1.18 =
Fixed an issue with database prefixes.

= 1.17 =
Fixed the length of the "filter by text" input field.

= 1.16 =
Added extension filter.
Added filter by text to the "Bulk Register".

= 1.15 =
Fixed problem of metadta.

= 1.14 =
Fixed problem of metadta.

= 1.13 =
Change readme.txt
Small changes in Javascript.

= 1.12 =
Added disallow search in the temporary directory by "robots.txt".

= 1.11 =
"Select Register", search error handler has been added.

= 1.10 =
Changed file search method.
Added "Select Register".

= 1.09 =
Added "Execution time" to the settings.

= 1.08 =
Changed file search method.
Changed the exclusion method.

= 1.07 =
Fixed search problem.

= 1.06 =
Added a message when the file does not exist.

= 1.05 =
Changed file search method.

= 1.04 =
A folder search box has been added to the settings screen.

= 1.03 =
Fixed problem of metadta.
Add add-on link.

= 1.02 =
Fixed search timeout issue.

= 1.01 =
Fixed problem of metadta.
Splited the hooks.

= 1.00 =
Initial release.

== Upgrade Notice ==

= 1.32 =
Security measures.

= 1.00 =
Initial release.

== Upgrade Notice ==

= 2.01 =
Measures Against Path Traversal. This was reported to us by Rafael Honorato
