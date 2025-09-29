/**
 * File customizer.js.
 *
 * Theme Customizer enhancements for a better user experience.
 *
 * Contains handlers to make Theme Customizer preview reload changes asynchronously.
 */

( function() {
	// Site title and description.
	wp.customize( 'blogname', function( value ) {
		value.bind( function( to ) {
			const siteTitle = document.querySelector( '.site-title a' );
			if ( siteTitle ) {
				siteTitle.textContent = to;
			}
		} );
	} );
	wp.customize( 'blogdescription', function( value ) {
		value.bind( function( to ) {
			const siteDescription = document.querySelector( '.site-description' );
			if ( siteDescription ) {
				siteDescription.textContent = to;
			}
		} );
	} );

	// Header text color.
	wp.customize( 'header_textcolor', function( value ) {
		value.bind( function( to ) {
			const siteTitle = document.querySelector( '.site-title' );
			const siteDescription = document.querySelector( '.site-description' );

			if ( 'blank' === to ) {
				siteTitle?.classList.add( 'screen-reader-text' );
				siteDescription?.classList.add( 'screen-reader-text' );
			} else {
				siteTitle?.classList.remove( 'screen-reader-text' );
				siteDescription?.classList.remove( 'screen-reader-text' );
			}
		} );
	} );

	wp.customize( 'header_bg_color', function( value ) {
		value.bind( function( to ) {
			document.querySelector( '.header-section' ).style.backgroundColor = to;
		} );
	} );

	wp.customize( 'copyright_txt', function( value ) {
		value.bind( function( newvalue ) {
			document.getElementById( 'copyright-txt' ).innerHTML = newvalue;
		} );
	} );
} )();
