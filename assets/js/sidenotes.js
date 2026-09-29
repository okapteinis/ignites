/**
 * Citation sidenotes: visible in the desktop margin, inline disclosure on mobile.
 */
(function () {
	'use strict';

	var toggles = Array.prototype.slice.call( document.querySelectorAll( '.single .margin-toggle' ) );
	var desktop = window.matchMedia( '(min-width: 1200px)' );
	var records = [];

	toggles.forEach( function ( toggle ) {
		var marker = toggle.nextElementSibling;
		var note = marker && marker.nextElementSibling;
		if ( ! marker || ! note || ! note.classList.contains( 'sidenote' ) ) {
			return;
		}
		records.push( { toggle: toggle, marker: marker, note: note, parent: note.parentNode } );
	} );

	if ( ! records.length ) {
		return;
	}

	document.documentElement.classList.add( 'has-sidenote-popovers' );

	function restoreNotes() {
		records.forEach( function ( record ) {
			record.note.classList.remove( 'is-rail-note' );
			record.note.style.removeProperty( 'left' );
			record.note.style.removeProperty( 'top' );
			record.note.style.removeProperty( 'width' );
			if ( record.note.parentNode !== record.parent ) {
				record.parent.insertBefore( record.note, record.marker.nextSibling );
			}
		} );
	}

	function positionNotes() {
		if ( ! desktop.matches || window.matchMedia( 'print' ).matches ) {
			restoreNotes();
			return;
		}

		var positioned = records.map( function ( record ) {
			var content = record.toggle.closest( '.entry-content' );
			var left = content ? content.getBoundingClientRect().right + window.scrollX + 40 : 0;
			return {
				record: record,
				content: content,
				y: record.marker.getBoundingClientRect().top + window.scrollY,
				left: left,
				width: Math.max( 120, Math.min( 280, window.innerWidth - left - 16 ) )
			};
		} ).filter( function ( item ) {
			return item.content;
		} ).sort( function ( a, b ) {
			return a.y - b.y;
		} );

		positioned.forEach( function ( item ) {
			var record = item.record;
			if ( record.note.parentNode !== document.body ) {
				document.body.appendChild( record.note );
			}
			record.note.classList.add( 'is-rail-note' );
			record.note.style.width = Math.round( item.width ) + 'px';
		} );

		// Read every note height before writing any coordinates to avoid forced
		// layout once per note on a long article.
		positioned.forEach( function ( item ) {
			item.height = item.record.note.getBoundingClientRect().height;
		} );

		var previousBottom = 0;
		var gap = 10;
		positioned.forEach( function ( item ) {
			var top = Math.max( item.y, previousBottom + gap );
			item.record.note.style.left = Math.round( item.left ) + 'px';
			item.record.note.style.top = Math.round( top ) + 'px';
			previousBottom = top + item.height;
		} );
	}

	positionNotes();
	var positionFrame = null;
	function schedulePosition() {
		if ( positionFrame !== null ) {
			return;
		}
		positionFrame = window.requestAnimationFrame( function () {
			positionFrame = null;
			positionNotes();
		} );
	}

	window.addEventListener( 'resize', schedulePosition );
	window.addEventListener( 'load', schedulePosition );
	window.addEventListener( 'beforeprint', restoreNotes );
	window.addEventListener( 'afterprint', positionNotes );
	if ( window.ResizeObserver ) {
		var contentObserver = new ResizeObserver( schedulePosition );
		var observedContent = [];
		records.forEach( function ( record ) {
			var content = record.toggle.closest( '.entry-content' );
			if ( content && observedContent.indexOf( content ) === -1 ) {
				observedContent.push( content );
				contentObserver.observe( content );
			}
		} );
	}
	if ( document.fonts && document.fonts.ready ) {
		document.fonts.ready.then( schedulePosition );
	}
	if ( desktop.addEventListener ) {
		desktop.addEventListener( 'change', schedulePosition );
	} else if ( desktop.addListener ) {
		desktop.addListener( schedulePosition );
	}
}());
