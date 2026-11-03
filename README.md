# Bulk-Media-Register
WordPress plugin for registering server-side media files in the Media Library.

Forked from https://plugins.trac.wordpress.org/browser/bulk-media-register/

## Recent improvements

- Added an explicit folder-source radio choice: 'uploads' dropdown (as original) or a custom path relative to the displayed public document root (f.i. `public_html/some_media`).
- Improved recursive scanning, extension filtering, and scan diagnostics. Unreadable child directories no longer stop the entire scan.
- Fixed admin JavaScript loading on Bulk Register and guarded date-picker initialization on screens where that library is absent.
- Made Select Register build only the current page of rows and avoid reading audio/video metadata just to display scan results, keeping large result lists responsive.
- Replaced the oversized scan-results database option with a protected temporary results file and a small settings pointer. Existing legacy result formats remain readable.
- Corrected registration of external files by storing their absolute filesystem path instead of an uploads-relative path containing `../`, and generating URLs relative to the public document root.
- Registration no longer renames or moves source files. It sets attachment dates explicitly and reads audio/video metadata and file size from the source file.

For further infos about the initial release, see original [README](README.txt)
