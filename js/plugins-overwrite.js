/* global _, wpApiSettings, entrepotl10nPluginsOverwrite, JSON, ActiveXObject */

// Make sure the wp object exists.
window.wp       = window.wp || {};
window.entrepot = window.entrepot || _.extend( {}, _.pick( window.wp, 'apiRequest', 'template' ) );

( function( $, _, entrepot ){

	/**
	 * Notice template.
	 *
	 * @type {function} A function that lazily-compiles the template requested.
	 */
	entrepot.notice = entrepot.template( 'entrepot-notice' );

	/**
	 * Ajax Uploader.
	 *
	 * For a reason I ignore, it's not possible to use
	 * the WordPress apiRequest for this purpose.
	 *
	 * @param  {string}   path     The Rest endpoint.
	 * @param  {object}   data     The data to send.
	 * @param  {function} response Ajax Response
	 */
	entrepot.upload = function( path, data, response ) {
		var ajaxRequest, endpoint,
		    headers = {
		    	'X-Requested-With' : 'XMLHttpRequest',
		    	'X-WP-Nonce'       : wpApiSettings.nonce,
		    	'Cache-Control'    : 'no-cache, must-revalidate, max-age=0'
		    };

		endpoint = wpApiSettings.root + path.replace( /^\//, '' );
		data     = data || {};

		if ( 'undefined' !== typeof XMLHttpRequest ) {
			ajaxRequest = new XMLHttpRequest();
		} else {
			ajaxRequest = new ActiveXObject( 'Microsoft.XMLHTTP' );
		}

		ajaxRequest.onreadystatechange = function( event ) {
			if ( event.currentTarget && 4 === event.currentTarget.readyState ) {
				var r = JSON.parse( event.currentTarget.responseText ), status;

				if ( r.status ) {
					status = r.status;
				} else {
					status = event.currentTarget.status;
				}

				response && response( status, r );
			}
		};

		ajaxRequest.open( 'POST', endpoint );

		for ( var h in headers ) {
			ajaxRequest.setRequestHeader( h, headers[h] );
		}

		ajaxRequest.send( data );
	};

	$( document ).ready( function() {
		var template = entrepot.template( 'entrepot-plugin-version' ), list = $( '#list-plugin-versions' );

		entrepot.apiRequest( {
			path: 'wp/v2/plugins/',
			type: 'GET',
			dataType: 'json'
		} ).done( function( response ) {
			// Remove the loading gif.
			$( '#entrepot-loading-plugins' ).remove();

			$.each( response, function( id, pluginData ) {
				list.append( template( pluginData ) );
			} );

			$( 'img.plugin-icon' ).on( 'error', function( event ) {
				event.preventDefault();

				$( event.currentTarget ).parent().find( '.dashicons-admin-plugins' ).first().removeClass( 'hide' );
				$( event.currentTarget ).remove();
			} );

		// No plugins were found.
		} ).fail( function( response ) {
			var error = { id: 'list-plugin-versions-error', code: 400 };

			if ( ! response || ! response.responseJSON ) {
				error.message = entrepotl10nPluginsOverwrite.unknownError;
			} else {
				_.extend( error, {
					code: response.responseJSON.status,
					message: response.responseJSON.message
				} );
			}

			// Replace the loading gif.
			$( '#entrepot-loading-plugins' ).html( entrepot.notice( error ) );
		} );
	} );

	$( '#list-plugin-versions' ).on( 'change', 'input[type="file"]', function( event ) {
		var $fileInput   = $( event.currentTarget ), pluginSlug = $fileInput.prop( 'name' ), pluginData = new FormData(),
		    pluginId     = $fileInput.data( 'plugin-id' ), btnLabel = $fileInput.parent().find( 'label' ).first(), firstFile,
		    btnUpgrading = $fileInput.parent().find( '.update-now' ).first(), card = $fileInput.parents( '.plugin-card' );

		if ( ! event.currentTarget.files ) {
			return;
		}

		if ( card.find( '#notice-' + pluginSlug ).length ) {
			card.find( '#notice-' + pluginSlug ).remove();
		}

		firstFile = _.first( event.currentTarget.files );

		if ( ! ( /.zip./ ).test( firstFile.type ) || 'zip' !== firstFile.name.substr( ( firstFile.name.lastIndexOf( '.' ) + 1 ) ) ) {
			card.append( entrepot.notice( {
				id: 'notice-' + pluginSlug,
				code: 403,
				message: entrepotl10nPluginsOverwrite.filetypeError
			} ) );

			return;
		}

		btnLabel.addClass( 'updating' );
		btnUpgrading.addClass( 'updating-message' );

		pluginData.append( pluginSlug, firstFile );
		pluginData.append( 'id', pluginId );

		// Reset the File input.
		$fileInput.val( '' );

		entrepot.upload(
			'wp/v2/plugins/' + pluginSlug + '/?package=' + pluginId,
			pluginData,
			function( status, response ) {
				var feedback = { id: 'notice-' + pluginSlug, code: status };

				btnLabel.removeClass( 'updating' );
				btnUpgrading.removeClass( 'updating-message' );

				if ( 200 !== status ) {
					feedback.message = entrepotl10nPluginsOverwrite.unknownError;

					if ( response && response.message ) {
						feedback.message = response.message;
					}
				} else {
					_.extend( feedback, { message: response.message } );

					if ( response.newVersion && response.oldVersion ) {
						btnLabel.html( btnLabel.html().replace( response.oldVersion, response.newVersion ) );
					}
				}

				card.append( entrepot.notice( feedback ) );
				$( document ).trigger( 'wp-updates-notice-added' );
			}
		);
	} );

}( jQuery, _, window.entrepot ) );
