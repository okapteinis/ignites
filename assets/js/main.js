/*
 * ignites-child main.js — vanilla replacement for the parent's jQuery main.js
 * (ignites#32: drops the 31KB jQuery + jquery-migrate dependency; main.js was its only
 * consumer). Faithful behaviour: scroll-to-top, submenu arrows, mobile hamburger menu
 * (open/close + Esc + click-outside), widget .children class.
 */
( function () {
	'use strict';
	var d = document;

	function closeMenu() {
		var ham = d.querySelector( '.hamburger-menu' );
		var ov  = d.querySelector( '.body-overlay' );
		var nav = d.querySelector( '.main-navigation' );
		if ( ham ) { ham.classList.remove( 'cross' ); }
		if ( ov )  { ov.classList.remove( 'is-active' ); }
		if ( nav ) { nav.classList.remove( 'is-active' ); }
		d.body.style.overflow = '';
	}

	function ready() {
		// Scroll-to-top button
		var top = d.querySelector( '.scroll-top' );
		if ( top ) {
			window.addEventListener( 'scroll', function () {
				top.classList.toggle( 'is-visible', window.scrollY > 600 );
			}, { passive: true } );
			top.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				window.scrollTo( { top: 0, behavior: 'smooth' } );
			} );
		}

		// Submenu arrows on menu items that have children
		d.querySelectorAll( '.main-navigation .menu-item-has-children > a' ).forEach( function ( a ) {
			if ( ! a.querySelector( '.arrow' ) ) {
				var span = d.createElement( 'span' );
				span.className = 'arrow';
				a.appendChild( span );
			}
		} );
		d.addEventListener( 'click', function ( e ) {
			var arrow = e.target.closest && e.target.closest( '.arrow' );
			if ( arrow ) {
				var item = arrow.closest( '.menu-item-has-children' );
				var sub  = item && item.querySelector( '.sub-menu' );
				if ( sub ) { sub.classList.toggle( 'show' ); }
			}
		} );

		// Mobile hamburger menu — toggle open/close
		var ham = d.querySelector( '.hamburger-menu' );
		if ( ham ) {
			ham.addEventListener( 'click', function ( e ) {
				e.stopPropagation();
				var nav = d.querySelector( '.main-navigation' );
				var open = nav && nav.classList.contains( 'is-active' );
				if ( open ) {
					closeMenu();
				} else {
					var ov = d.querySelector( '.body-overlay' );
					if ( ov )  { ov.classList.add( 'is-active' ); }
					if ( nav ) { nav.classList.add( 'is-active' ); }
					ham.classList.add( 'cross' );
					d.body.style.overflow = 'hidden';
				}
			} );
		}
		d.querySelectorAll( '.close-navigation' ).forEach( function ( el ) {
			el.addEventListener( 'click', closeMenu );
		} );
		d.addEventListener( 'keyup', function ( e ) {
			if ( e.key === 'Escape' || e.keyCode === 27 ) { closeMenu(); }
		} );
		d.addEventListener( 'click', function ( e ) {
			var inMenu = e.target.closest && ( e.target.closest( '.hamburger-menu' ) || e.target.closest( '.main-navigation' ) );
			var nav = d.querySelector( '.main-navigation' );
			if ( ! inMenu && nav && nav.classList.contains( 'is-active' ) ) { closeMenu(); }
		} );

		// Widget: mark parents of .children
		d.querySelectorAll( '.widget .children' ).forEach( function ( el ) {
			if ( el.parentElement ) { el.parentElement.classList.add( 'haschildren' ); }
		} );
	}

	if ( d.readyState === 'loading' ) {
		d.addEventListener( 'DOMContentLoaded', ready );
	} else {
		ready();
	}
} )();
