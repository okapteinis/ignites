/**
 * Citation sidenotes: anchored desktop popovers with native mobile disclosure.
 */
(function () {
	'use strict';

	var toggles = Array.prototype.slice.call( document.querySelectorAll( '.single .margin-toggle' ) );
	var desktop = window.matchMedia( '(min-width: 1200px)' );
	var records = [];
	var byToggle = new WeakMap();
	var openRecord = null;
	var positionFrame = null;

	toggles.forEach( function ( toggle ) {
		var marker = toggle.nextElementSibling;
		var note = marker && marker.nextElementSibling;
		if ( ! marker || ! note || ! note.classList.contains( 'sidenote' ) ) {
			return;
		}
		var record = { toggle: toggle, marker: marker, note: note, parent: note.parentNode };
		records.push( record );
		byToggle.set( toggle, record );
	} );

	if ( ! records.length ) {
		return;
	}

	document.documentElement.classList.add( 'has-sidenote-popovers' );

	function restoreNote( record ) {
		record.note.classList.remove( 'is-popover-open' );
		record.note.style.removeProperty( 'left' );
		record.note.style.removeProperty( 'top' );
		if ( record.note.parentNode !== record.parent ) {
			record.parent.insertBefore( record.note, record.marker.nextSibling );
		}
	}

	function setPosition( record ) {
		var content = record.toggle.closest( '.entry-content' );
		if ( ! content || ! desktop.matches || ! record.toggle.checked ) {
			return;
		}

		var markerRect = record.marker.getBoundingClientRect();
		var contentRect = content.getBoundingClientRect();
		var noteRect = record.note.getBoundingClientRect();
		var gap = parseFloat( window.getComputedStyle( content ).getPropertyValue( '--sidenote-gap' ) ) || 40;
		var edge = 16;
		var maxLeft = Math.max( edge, window.innerWidth - noteRect.width - edge );
		var left = Math.max( edge, Math.min( contentRect.right + gap, maxLeft ) );
		var maxTop = Math.max( edge, window.innerHeight - noteRect.height - edge );
		var top = Math.max( edge, Math.min( markerRect.top, maxTop ) );

		record.note.style.left = Math.round( left ) + 'px';
		record.note.style.top = Math.round( top ) + 'px';
	}

	function openNote( record ) {
		record.note.classList.add( 'is-popover-open' );
		if ( record.note.parentNode !== document.body ) {
			document.body.appendChild( record.note );
		}
		setPosition( record );
	}

	function closeToggle( toggle, restoreFocus ) {
		var record = byToggle.get( toggle );
		if ( ! record ) {
			return;
		}
		toggle.checked = false;
		restoreNote( record );
		if ( openRecord === record ) {
			openRecord = null;
		}
		if ( restoreFocus ) {
			toggle.focus();
		}
	}

	function schedulePosition() {
		if ( positionFrame !== null ) {
			return;
		}
		positionFrame = window.requestAnimationFrame( function () {
			positionFrame = null;
			if ( openRecord && openRecord.toggle.checked && desktop.matches ) {
				setPosition( openRecord );
			}
		} );
	}

	function handleViewportChange() {
		if ( ! desktop.matches ) {
			if ( openRecord ) {
				restoreNote( openRecord );
				openRecord = null;
			}
			return;
		}
		if ( ! openRecord || ! openRecord.toggle.checked ) {
			openRecord = records.find( function ( record ) { return record.toggle.checked; } ) || null;
		}
		if ( openRecord ) {
			records.forEach( function ( record ) {
				if ( record !== openRecord && record.toggle.checked ) {
					closeToggle( record.toggle, false );
				}
			} );
			openNote( openRecord );
		}
	}

	records.forEach( function ( record ) {
			record.toggle.addEventListener( 'change', function () {
				if ( record.toggle.checked && desktop.matches ) {
					if ( openRecord && openRecord !== record ) {
						closeToggle( openRecord.toggle, false );
					}
					openRecord = record;
					openNote( record );
				} else if ( record.toggle.checked ) {
					openRecord = null;
					restoreNote( record );
				} else {
					if ( openRecord === record ) {
						openRecord = null;
					}
					restoreNote( record );
				}
		} );
	} );

	document.addEventListener( 'click', function ( event ) {
		if ( ! desktop.matches || ! openRecord || ( event.target.closest && event.target.closest( '.margin-toggle, .sidenote-number, .sidenote.is-popover-open' ) ) ) {
			return;
		}
		closeToggle( openRecord.toggle, false );
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( event.key !== 'Escape' || ! desktop.matches ) {
			return;
		}
		if ( openRecord ) {
			event.preventDefault();
			closeToggle( openRecord.toggle, true );
		}
	} );

	window.addEventListener( 'resize', handleViewportChange );
	window.addEventListener( 'scroll', schedulePosition, true );
	if ( desktop.addEventListener ) {
		desktop.addEventListener( 'change', handleViewportChange );
	} else if ( desktop.addListener ) {
		desktop.addListener( handleViewportChange );
	}
}());
