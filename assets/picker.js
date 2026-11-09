( function () {
	'use strict';

	var cfg = window.bmePicker || {};
	var i18n = cfg.i18n || {};

	var grid        = document.getElementById( 'bme-grid' );
	var filterType   = document.getElementById( 'bme-filter-type' );
	var filterSearch = document.getElementById( 'bme-filter-search' );
	var selectAllBtn = document.getElementById( 'bme-select-all' );
	var selectNoneBtn = document.getElementById( 'bme-select-none' );
	var loadMoreBtn  = document.getElementById( 'bme-load-more' );
	var shownCount   = document.getElementById( 'bme-shown-count' );
	var exportBtn    = document.getElementById( 'bme-export-btn' );
	var exportForm   = document.getElementById( 'bme-export-form' );
	var idsField     = document.getElementById( 'bme-selected-ids' );

	var selectedIds  = new Set();
	var paged        = 1;
	var totalFound   = 0;
	var loadedCount  = 0;
	var isLoading    = false;
	var searchTimer  = null;

	function fmt( str ) {
		var args = Array.prototype.slice.call( arguments, 1 );
		var i = 0;
		return str.replace( /%\d*\$?[ds]/g, function () {
			return args[ i++ ];
		} );
	}

	function fetchItems( reset ) {
		if ( isLoading ) {
			return;
		}
		isLoading = true;

		if ( reset ) {
			paged = 1;
			loadedCount = 0;
			grid.innerHTML = '';
		}

		var body = new URLSearchParams( {
			action:      'bme_get_media_items',
			nonce:       cfg.listNonce,
			paged:       String( paged ),
			mime_group:  filterType.value,
			search:      filterSearch.value,
		} );

		fetch( cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
			.then( function ( res ) { return res.json(); } )
			.then( function ( res ) {
				isLoading = false;

				if ( ! res || ! res.success ) {
					if ( reset ) {
						renderMessage( i18n.error || 'Error', 'bme-error' );
					}
					return;
				}

				totalFound = res.data.totalFound;
				renderItems( res.data.items );
				loadedCount += res.data.items.length;

				if ( reset && 0 === loadedCount ) {
					renderMessage( i18n.noItems || 'No items', 'bme-empty' );
				}

				loadMoreBtn.style.display = res.data.hasMore ? '' : 'none';
				updateShownCount();
			} )
			.catch( function () {
				isLoading = false;
				if ( reset ) {
					renderMessage( i18n.error || 'Error' , 'bme-error' );
				}
			} );
	}

	function renderMessage( text, className ) {
		var div = document.createElement( 'div' );
		div.className = className;
		div.textContent = text;
		grid.appendChild( div );
	}

	function renderItems( items ) {
		items.forEach( function ( item ) {
			grid.appendChild( buildItemEl( item ) );
		} );
	}

	function buildItemEl( item ) {
		var wrap = document.createElement( 'div' );
		wrap.className = 'bme-item' + ( item.icon ? ' is-icon' : '' );
		wrap.dataset.id = item.id;

		var checkbox = document.createElement( 'input' );
		checkbox.type = 'checkbox';
		checkbox.className = 'bme-item__checkbox';
		checkbox.checked = selectedIds.has( item.id );

		var thumbWrap = document.createElement( 'div' );
		thumbWrap.className = 'bme-item__thumb-wrap';

		var img = document.createElement( 'img' );
		img.src = item.thumb;
		img.alt = '';
		thumbWrap.appendChild( img );

		var name = document.createElement( 'div' );
		name.className = 'bme-item__filename';
		name.textContent = item.title || item.filename;

		wrap.appendChild( checkbox );
		wrap.appendChild( thumbWrap );
		wrap.appendChild( name );

		if ( selectedIds.has( item.id ) ) {
			wrap.classList.add( 'is-selected' );
		}

		function toggle() {
			if ( selectedIds.has( item.id ) ) {
				selectedIds.delete( item.id );
			} else {
				selectedIds.add( item.id );
			}
			checkbox.checked = selectedIds.has( item.id );
			wrap.classList.toggle( 'is-selected', selectedIds.has( item.id ) );
			updateSelectionUi();
		}

		wrap.addEventListener( 'click', function ( e ) {
			if ( e.target !== checkbox ) {
				toggle();
			}
		} );
		checkbox.addEventListener( 'change', toggle );

		return wrap;
	}

	function updateSelectionUi() {
		var count = selectedIds.size;
		exportBtn.disabled = 0 === count;
		exportBtn.textContent = fmt( i18n.exportLabel ? i18n.exportLabel + ' (%d selected)' : 'Export ZIP (%d selected)', count );
	}

	function updateShownCount() {
		shownCount.textContent = fmt( i18n.shown || '%1$d of %2$d shown', loadedCount, totalFound );
	}

	filterType.addEventListener( 'change', function () {
		fetchItems( true );
	} );

	filterSearch.addEventListener( 'input', function () {
		clearTimeout( searchTimer );
		searchTimer = setTimeout( function () {
			fetchItems( true );
		}, 350 );
	} );

	loadMoreBtn.addEventListener( 'click', function () {
		paged++;
		fetchItems( false );
	} );

	selectAllBtn.addEventListener( 'click', function () {
		grid.querySelectorAll( '.bme-item' ).forEach( function ( el ) {
			selectedIds.add( parseInt( el.dataset.id, 10 ) );
			el.classList.add( 'is-selected' );
			el.querySelector( '.bme-item__checkbox' ).checked = true;
		} );
		updateSelectionUi();
	} );

	selectNoneBtn.addEventListener( 'click', function () {
		selectedIds.clear();
		grid.querySelectorAll( '.bme-item' ).forEach( function ( el ) {
			el.classList.remove( 'is-selected' );
			el.querySelector( '.bme-item__checkbox' ).checked = false;
		} );
		updateSelectionUi();
	} );

	exportForm.addEventListener( 'submit', function () {
		idsField.value = Array.from( selectedIds ).join( ',' );
	} );

	fetchItems( true );
}() );
