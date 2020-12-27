( function() {
	if ( 'undefined' === typeof entrepotRepositories ) {
		return false;
	}

	if ( ! entrepotRepositories.plugins ) {
		return false;
	}

	entrepotRepositories.plugins.forEach( function( r ) {
		var container    = document.querySelector( '[src="' + r.icon + '"]' ).closest( 'p' ),
		    contentParts = container.innerHTML.split( '<br>' ), warnings = '';


		if ( r.wp && '' !== r.wp ) {
			warnings += '<br><span class="attention">' + entrepotRepositories.warnings.WP.replace( 'wpVersion', r.wp ) + '</span>';
		}

		if ( r.php && '' !== r.php ) {
			warnings += '<br><span class="attention">' + entrepotRepositories.warnings.PHP.replace( 'phpVersion', r.php ) + '</span>';
		}

		if ( '' !== warnings ) {
			container.innerHTML = contentParts[0] + warnings;
		}
	} );
} )();
