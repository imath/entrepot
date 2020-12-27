/* global _, entrepotl10n, entrepot */

// Make sure the wp object exists.
window.wp = window.wp || {};
window.entrepot = window.entrepot || _.extend( {}, _.pick( window.wp, 'Backbone', 'template' ) );

( function( $ ) {

	if ( 'undefined' === typeof entrepotl10n ) {
		return false;
	}

	// Prevent the search tab to appear
	$( document ).ready( function() {
		$( 'body' ).addClass( 'entrepot-install-php' );

		var search = $( '.plugin-install-php .wp-filter-search' ),
		    searchCss = {
				margin: 0,
				width: '280px',
				'font-size': '16px',
				'font-weight': 300,
				'line-height': 1.5,
				padding: '3px 5px',
				height: '32px'
			};

		if ( entrepotl10n.wpVersion >= 5.5 ) {
			searchCss = {
				display: 'inline-block',
				'margin-top': '10px',
				'vertical-align': 'top'
			};
		}

		$( search ).removeClass( 'wp-filter-search' )
		           .prop( 'id', 'entrepot-search' )
		           .css( searchCss );

		$( '#typeselector [value="tag"]' ).remove();
		$( '#the-list' ).css( { 'margin-top': '2em' } );
	} );

	// Set Views holder
	entrepot.Views = entrepot.Views || {};

	// Extend wp.Backbone.View with .prepare()
	entrepot.View = entrepot.Backbone.View.extend( {
		prepare: function() {
			if ( ! _.isUndefined( this.model ) && _.isFunction( this.model.toJSON ) ) {
				return this.model.toJSON();
			} else {
				return {};
			}
		}
	} );

	entrepot.Views.Card = entrepot.View.extend( {
		className:  'plugin-card',
		template: entrepot.template( 'entrepot-repository' ),

		initialize: function() {
			var description = this.model.get( 'description' ), presentation = '',
			    icon = entrepotl10n.defaultIcon, author;

			if ( _.isUndefined( description[ entrepotl10n.locale ] ) ) {
				presentation = description.en_US;
			} else {
				presentation = description[ entrepotl10n.locale ];
			}

			author = this.model.get( 'author' );
			if ( this.model.get( 'author_url' ) ) {
				author = '<a href="' + this.model.get( 'author_url' ) + '">' + author + '</a>';
			}

			author = entrepotl10n.byAuthor.replace( '%s', author );

			if ( this.model.get( 'icon' ) ) {
				icon = this.model.get( 'icon' );
			}

			this.model.set( {
				presentation: presentation,
				icon:         icon,
				author:       author
			 }, { silent: true } );

			// Add the Repository specific className
			if ( this.model.get( 'name' ) ) {
				this.el.className += ' plugin-card-' + this.model.get( 'slug' );
			}

			this.$el.prop( 'id', this.model.get( 'id' ) );
		}
	} );

	entrepot.Views.List = entrepot.View.extend( {
		initialize: function() {
			_.each( this.collection.models, function( repository ) {
				this.displayRepository( repository );
			}, this );

			$( '#entrepot-search' ).on( 'keyup input', _.bind( this.searchRepositories, this ) );
			$( '#typeselector' ).on( 'change', this.resetSearch );
		},

		displayRepository: function( repository ) {
			this.views.add( new entrepot.Views.Card( { model: repository } ) );
		},

		searchRepositories: function( event ) {
			var searchType = $( '#typeselector' ).val(), searchTerm = $( event.currentTarget ).val().toLowerCase();

			event.preventDefault();

			if ( ! searchTerm ) {
				$( '#the-list .plugin-card' ).removeClass( 'hide-if-js' );
			} else {
				_.each( this.collection.models, function( repository ) {
					var $repoID = $( '#' + repository.get( 'id' ) );

					if ( ( 'term' === searchType && -1 === repository.get( 'name' ).toLowerCase().indexOf( searchTerm ) && -1 === repository.get( 'presentation' ).toLowerCase().indexOf( searchTerm ) ) ||
							 ( 'author' === searchType && -1 === repository.get( 'author' ).toLowerCase().indexOf( searchTerm ) )
					) {
						$repoID.addClass( 'hide-if-js' );
					} else {
						$repoID.removeClass( 'hide-if-js' );
					}
				} );
			}
		},

		resetSearch: function( event ) {
			event.preventDefault();

			$( '#entrepot-search' ).val( '' );
			$( '#the-list .plugin-card' ).removeClass( 'hide-if-js' );
		}
	} );

	entrepot.App = {
		init: function( data ) {
			this.views        = new Backbone.Collection();
			this.repositories = new Backbone.Collection( data );

			this.list = new entrepot.Views.List( {
				el:           $( '#the-list' ),
				collection:   this.repositories
			} ).render();
		}
	};

	$.getJSON( entrepotl10n.url, function( data ) {
		if ( ! data ) {
			return false;
		}

		// Shuffle results
		data.sort( function() { return 0.5 - Math.random(); } );

		// Init the App.
		entrepot.App.init( data );

	} ).fail( function( xhr ) {
		$( '#the-list' ).append(
			'<div id="message" class="error"><p>' + $.parseJSON( xhr.responseText ) + '</p></div>'
		);
	} );

} )( jQuery );
