/* global _, galeriel10n, galerie */

// Make sure the wp object exists.
window.wp = window.wp || {};
window.galerie = window.galerie || _.extend( {}, _.pick( window.wp, 'Backbone', 'template' ) );

( function( $ ) {

	if ( 'undefined' === typeof galeriel10n ) {
		return false;
	}

	// Prevent the search tab to appear
	$( document ).ready( function() {
		$( 'body' ).removeClass( 'plugin-install-php' ).addClass( 'gallerie-install-php' );
	} );

	// Set Views holder
	galerie.Views = galerie.Views || {};

	// Extend wp.Backbone.View with .prepare()
	galerie.View = galerie.Backbone.View.extend( {
		prepare: function() {
			if ( ! _.isUndefined( this.model ) && _.isFunction( this.model.toJSON ) ) {
				return this.model.toJSON();
			} else {
				return {};
			}
		}
	} );

	galerie.Views.Card = galerie.View.extend( {
		className:  'plugin-card',
		template: galerie.template( 'galerie-repository' ),

		initialize: function() {
			var description = this.model.get( 'description' ), presentation = '',
			    icon = galeriel10n.defaultIcon;

			if ( _.isUndefined( description[ galeriel10n.locale ] ) ) {
				presentation = description.en_US;
			} else {
				presentation = description[ galeriel10n.locale ];
			}

			if ( this.model.get( 'icon' ) ) {
				icon = this.model.get( 'icon' );
			}

			this.model.set( {
				presentation: presentation,
				icon:         icon
			 }, { silent: true } );

			// Add the Repository specific className
			if ( this.model.get( 'name' ) ) {
				this.el.className += ' plugin-card-' + this.model.get( 'name' );
			}
		}
	} );

	galerie.Views.List = galerie.View.extend( {
		initialize: function() {
			_.each( this.collection.models, function( repository ) {
				this.displayRepository( repository );
			}, this );
		},

		displayRepository: function( repository ) {
			this.views.add( new galerie.Views.Card( { model: repository } ) );
		}
	} );

	galerie.App = {
		init: function( data ) {
			this.views        = new Backbone.Collection();
			this.repositories = new Backbone.Collection( data );

			this.list = new galerie.Views.List( {
				el:           $( '#the-list' ),
				collection:   this.repositories
			} ).render();
		}
	};

	$.getJSON( galeriel10n.url, function( data ) {
		if ( ! data ) {
			return false;
		}

		// Shuffle results
		data.sort( function() { return 0.5 - Math.random(); } );

		// Init the App.
		galerie.App.init( data );

	} ).fail( function() {
		// Display an error message.
	} );

} )( jQuery );
