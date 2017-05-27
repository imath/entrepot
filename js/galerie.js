/* global Galerie */
( function( $ ) {

	if ( 'undefined' === typeof Galerie ) {
		return false;
	}

	$.getJSON( Galerie.url, function( data ) {
		// Shuffle results
		data.sort( function() { return 0.5 - Math.random() } );

		console.log( data );
	} );

} )( jQuery );
