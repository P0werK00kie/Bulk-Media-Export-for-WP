<?php
/**
 * Streams the selected media items as a single ZIP download.
 *
 * Reachable from both the Media Library bulk-action (GET redirect) and the
 * Bulk Export tab (POST form), so IDs/nonce are read from $_REQUEST rather
 * than $_GET to support either flow through one handler.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

add_action( 'admin_post_bme_download_zip', 'bme_download_zip' );
function bme_download_zip() {
	if ( ! current_user_can( 'upload_files' ) ) {
		wp_die( esc_html__( 'You do not have permission to export media.', 'bulk-media-export' ) );
	}

	check_admin_referer( BME_NONCE_ACTION );

	if ( ! class_exists( 'ZipArchive' ) ) {
		wp_die( esc_html__( 'The PHP ZipArchive extension is required on this server.', 'bulk-media-export' ) );
	}

	$raw_ids = isset( $_REQUEST['ids'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['ids'] ) ) : '';
	$ids     = array_filter( array_map( 'intval', explode( ',', $raw_ids ) ) );

	if ( empty( $ids ) ) {
		wp_die( esc_html__( 'No media items were selected.', 'bulk-media-export' ) );
	}

	$zip_path = wp_tempnam( 'bulk-media-export.zip' );

	$zip = new ZipArchive();
	if ( true !== $zip->open( $zip_path, ZipArchive::OVERWRITE ) ) {
		wp_die( esc_html__( 'Could not create the ZIP file.', 'bulk-media-export' ) );
	}

	$used_names = array();
	$added      = 0;

	foreach ( $ids as $id ) {
		if ( 'attachment' !== get_post_type( $id ) ) {
			continue;
		}

		// Any attachment type is exportable; this is a read-only export of
		// files already on disk, not a new upload/execution path, so we
		// deliberately don't restrict by mime type here (deleted: an
		// image/*-only check used to live in this spot).
		$mime = get_post_mime_type( $id );
		if ( ! $mime ) {
			continue;
		}

		$file = get_attached_file( $id );
		if ( ! $file || ! file_exists( $file ) ) {
			continue;
		}

		$name = bme_unique_filename_in_list( $used_names, basename( $file ) );
		$used_names[] = $name;

		$zip->addFile( $file, $name );
		$added++;
	}

	$zip->close();

	if ( ! $added ) {
		@unlink( $zip_path );
		wp_die( esc_html__( 'None of the selected items could be found on disk.', 'bulk-media-export' ) );
	}

	nocache_headers();
	header( 'Content-Type: application/zip' );
	header( 'Content-Disposition: attachment; filename="media-export-' . gmdate( 'Y-m-d-His' ) . '.zip"' );
	header( 'Content-Length: ' . filesize( $zip_path ) );

	readfile( $zip_path );
	@unlink( $zip_path );
	exit;
}

/**
 * Small helper: build a unique filename within a ZIP given names already used.
 */
function bme_unique_filename_in_list( $used_names, $filename ) {
	if ( ! in_array( $filename, $used_names, true ) ) {
		return $filename;
	}

	$ext  = pathinfo( $filename, PATHINFO_EXTENSION );
	$base = pathinfo( $filename, PATHINFO_FILENAME );
	$i    = 1;

	do {
		$candidate = $ext ? "{$base}-{$i}.{$ext}" : "{$base}-{$i}";
		$i++;
	} while ( in_array( $candidate, $used_names, true ) );

	return $candidate;
}
