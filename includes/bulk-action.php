<?php
/**
 * Media Library List-view bulk action: "Download as ZIP".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

add_filter( 'bulk_actions-upload', 'bme_register_bulk_action' );
function bme_register_bulk_action( $actions ) {
	$actions['bme_download_zip'] = __( 'Download as ZIP', 'bulk-media-export' );
	return $actions;
}

/**
 * Handle the bulk action: redirect to our download endpoint with the
 * selected attachment IDs. Core's upload.php performs the actual
 * wp_redirect(), so we just need to return the target URL.
 */
add_filter( 'handle_bulk_actions-upload', 'bme_handle_bulk_action', 10, 3 );
function bme_handle_bulk_action( $redirect_to, $doaction, $post_ids ) {
	if ( 'bme_download_zip' !== $doaction || empty( $post_ids ) ) {
		return $redirect_to;
	}

	$post_ids = array_map( 'intval', $post_ids );

	$url = add_query_arg(
		array(
			'action' => 'bme_download_zip',
			'ids'    => implode( ',', $post_ids ),
		),
		admin_url( 'admin-post.php' )
	);

	// Note: wp_nonce_url() HTML-escapes its output (for use in href="..."),
	// which breaks a URL headed straight into wp_redirect(). Append the
	// nonce ourselves so the ampersands stay literal.
	return add_query_arg( '_wpnonce', wp_create_nonce( BME_NONCE_ACTION ), $url );
}
