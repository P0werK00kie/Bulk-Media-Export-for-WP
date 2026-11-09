<?php
/**
 * Dedicated "Bulk Export" admin page: a self-contained media picker so
 * users don't need to go through the core Media Library list table at all.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

add_action( 'admin_menu', 'bme_register_picker_page' );
function bme_register_picker_page() {
	add_submenu_page(
		'upload.php',
		__( 'Bulk Export', 'bulk-media-export' ),
		__( 'Bulk Export', 'bulk-media-export' ),
		'upload_files',
		'bme-bulk-export',
		'bme_render_picker_page'
	);
}

add_action( 'admin_enqueue_scripts', 'bme_enqueue_picker_assets' );
function bme_enqueue_picker_assets( $hook ) {
	if ( 'media_page_bme-bulk-export' !== $hook ) {
		return;
	}

	wp_enqueue_style( 'bme-picker', plugins_url( 'assets/picker.css', BME_PLUGIN_FILE ), array(), BME_VERSION );
	wp_enqueue_script( 'bme-picker', plugins_url( 'assets/picker.js', BME_PLUGIN_FILE ), array(), BME_VERSION, true );

	wp_localize_script(
		'bme-picker',
		'bmePicker',
		array(
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'listNonce'   => wp_create_nonce( 'bme_picker_ajax' ),
			'exportUrl'   => admin_url( 'admin-post.php' ),
			'exportNonce' => wp_create_nonce( BME_NONCE_ACTION ),
			'perPage'     => 60,
			'i18n'        => array(
				'selected'    => __( '%d selected', 'bulk-media-export' ),
				'shown'       => __( '%1$d of %2$d shown', 'bulk-media-export' ),
				'loadMore'    => __( 'Load more', 'bulk-media-export' ),
				'loading'     => __( 'Loading…', 'bulk-media-export' ),
				'noItems'     => __( 'No media items found.', 'bulk-media-export' ),
				'exportLabel' => __( 'Export ZIP', 'bulk-media-export' ),
				'error'       => __( 'Something went wrong loading media. Please try again.', 'bulk-media-export' ),
			),
		)
	);
}

/**
 * Reuses core's own Media Library mime-type groupings (the same keys that
 * feed WP_Query's post_mime_type arg) instead of hand-rolling a mapping.
 */
function bme_get_mime_filter_options() {
	if ( ! function_exists( 'get_post_mime_types' ) ) {
		require_once ABSPATH . 'wp-admin/includes/post.php';
	}

	$options = array( 'all' => __( 'All media items', 'bulk-media-export' ) );

	foreach ( get_post_mime_types() as $key => $labels ) {
		$options[ $key ] = $labels[0];
	}

	return $options;
}

function bme_render_picker_page() {
	if ( ! current_user_can( 'upload_files' ) ) {
		wp_die( esc_html__( 'You do not have permission to export media.', 'bulk-media-export' ) );
	}

	$mime_options = bme_get_mime_filter_options();
	?>
	<div class="wrap bme-wrap">
		<h1><?php esc_html_e( 'Bulk Export', 'bulk-media-export' ); ?></h1>
		<p><?php esc_html_e( 'Select media items below and download them together as a ZIP file.', 'bulk-media-export' ); ?></p>

		<div class="bme-toolbar">
			<select id="bme-filter-type">
				<?php foreach ( $mime_options as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>

			<input type="search" id="bme-filter-search" placeholder="<?php esc_attr_e( 'Search media…', 'bulk-media-export' ); ?>">

			<button type="button" id="bme-select-all" class="button"><?php esc_html_e( 'Select all (loaded)', 'bulk-media-export' ); ?></button>
			<button type="button" id="bme-select-none" class="button"><?php esc_html_e( 'Select none', 'bulk-media-export' ); ?></button>

			<span id="bme-shown-count" class="bme-count"></span>
		</div>

		<form id="bme-export-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="bme_download_zip">
			<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( BME_NONCE_ACTION ) ); ?>">
			<input type="hidden" name="ids" id="bme-selected-ids" value="">

			<div id="bme-grid" class="bme-grid" aria-live="polite"></div>

			<div class="bme-footer">
				<button type="button" id="bme-load-more" class="button" style="display:none;"><?php esc_html_e( 'Load more', 'bulk-media-export' ); ?></button>
				<button type="submit" id="bme-export-btn" class="button button-primary" disabled>
					<?php esc_html_e( 'Export ZIP (0 selected)', 'bulk-media-export' ); ?>
				</button>
			</div>
		</form>
	</div>
	<?php
}

add_action( 'wp_ajax_bme_get_media_items', 'bme_ajax_get_media_items' );
function bme_ajax_get_media_items() {
	check_ajax_referer( 'bme_picker_ajax', 'nonce', false );

	if ( ! current_user_can( 'upload_files' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'bulk-media-export' ) ), 403 );
	}

	$paged      = isset( $_POST['paged'] ) ? max( 1, absint( $_POST['paged'] ) ) : 1;
	$mime_group = isset( $_POST['mime_group'] ) ? sanitize_key( $_POST['mime_group'] ) : 'all';
	$search     = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

	$mime_options = bme_get_mime_filter_options();
	if ( ! array_key_exists( $mime_group, $mime_options ) ) {
		$mime_group = 'all';
	}

	$args = array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => 60,
		'paged'          => $paged,
		'orderby'        => 'date',
		'order'          => 'DESC',
	);

	if ( '' !== $search ) {
		$args['s'] = $search;
	}

	if ( 'all' !== $mime_group ) {
		$args['post_mime_type'] = $mime_group;
	}

	$query = new WP_Query( $args );
	$items = array();

	foreach ( $query->posts as $post ) {
		$thumb = wp_get_attachment_image_src( $post->ID, 'thumbnail' );
		$icon  = false;

		if ( ! $thumb ) {
			$thumb = array( wp_mime_type_icon( $post->ID ) );
			$icon  = true;
		}

		$items[] = array(
			'id'       => $post->ID,
			'thumb'    => $thumb[0],
			'title'    => get_the_title( $post ),
			'filename' => wp_basename( get_attached_file( $post->ID ) ),
			'mime'     => $post->post_mime_type,
			'icon'     => $icon,
		);
	}

	wp_send_json_success(
		array(
			'items'      => $items,
			'hasMore'    => $paged < $query->max_num_pages,
			'totalFound' => (int) $query->found_posts,
		)
	);
}
