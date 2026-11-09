<?php
/**
 * Plugin Name: Bulk Media Export
 * Description: Pick multiple media items (any file type) from the Media Library or a dedicated Bulk Export tab and download them as one ZIP file.
 * Version: 1.1.0
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Author: Your Site
 * License: GPL v2 or later
 * Text Domain: bulk-media-export
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'BME_NONCE_ACTION', 'bme_download_zip' );
define( 'BME_PLUGIN_FILE', __FILE__ );
define( 'BME_VERSION', '1.1.0' );

require_once __DIR__ . '/includes/export.php';
require_once __DIR__ . '/includes/bulk-action.php';
require_once __DIR__ . '/includes/picker-page.php';
