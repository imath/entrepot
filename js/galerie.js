/* global Galerie */
( function( $ ) {

	if ( 'undefined' === typeof Galerie ) {
		return false;
	}

	// Prevent the search tab to appear
	$( document ).ready( function() {
		$( 'body' ).removeClass( 'plugin-install-php' ).addClass( 'gallerie-install-php' );
	} );

	$.getJSON( Galerie.url, function( data ) {
		// Shuffle results
		data.sort( function() { return 0.5 - Math.random() } );

		console.log( data );
	} );

} )( jQuery );
