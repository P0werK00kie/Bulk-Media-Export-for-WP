# Bulk Media Export

A small WordPress plugin that lets you select multiple media items and download them together as a single ZIP file — straight from the Media Library, or from a dedicated **Bulk Export** picker.

## Features

- **Bulk Export tab** — a self-contained screen under **Media → Bulk Export** with a thumbnail grid, checkboxes, search, and a media-type filter. No need to touch the core Media Library screen at all.
- **Media Library bulk action** — select items in the Media Library list view, choose **Download as ZIP** from the Bulk actions dropdown.
- Works with any attachment type (images, PDFs, audio, video, documents, etc.), not just images.
- No dependencies, no build step — plain PHP, vanilla JS, and CSS.

## Requirements

- WordPress 5.0+
- PHP 7.2+
- The PHP `ZipArchive` extension (standard on most hosts)

## Installation

1. Download or clone this repository.
2. Copy the `bulk-media-export` folder into your site's `wp-content/plugins/` directory.
3. In the WordPress admin, go to **Plugins** and activate **Bulk Media Export**.

## Usage

### Option 1: Bulk Export tab (recommended)

1. Go to **Media → Bulk Export** in the admin menu.
2. Optionally filter by media type or search by name.
3. Check the items you want (use **Select all (loaded)** / **Select none** as shortcuts, and **Load more** to bring in additional items).
4. Click **Export ZIP**. A ZIP file containing the selected items downloads immediately.

### Option 2: Media Library bulk action

1. Go to **Media → Library** and switch to list view.
2. Select the checkboxes next to the items you want.
3. Choose **Download as ZIP** from the **Bulk actions** dropdown and click **Apply**.

## Permissions

Both the Bulk Export tab and the bulk action require the `upload_files` capability — the same capability WordPress uses to gate access to the Media Library itself. Users without it will see a permission error.

## Project structure

```
bulk-media-export/
├── bulk-media-export.php     # Plugin bootstrap
├── includes/
│   ├── bulk-action.php       # Media Library "Download as ZIP" bulk action
│   ├── export.php            # Shared ZIP-streaming download handler
│   └── picker-page.php       # Bulk Export admin page + AJAX media listing
└── assets/
    ├── picker.css            # Bulk Export page styles
    └── picker.js             # Bulk Export page behavior (grid, selection, export)
```

## License

GPL v2 or later.
