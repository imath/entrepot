/* global JSON, entrepotNoticesl10n */
window.entrepot = window.entrepot || {};

( function( $ ) {

	if ( 'undefined' === typeof entrepotNoticesl10n ) {
		return false;
	}

	/**
	 * The Notices object.
	 *
	 * @type {Object}
	 */
	window.entrepot.Notices = {

		/**
		 * Gets a variable form the local storage.
		 *
		 * @param  {string}  type     The object type to get.
		 * @param  {string}  property The property of the object to get.
		 * @return {mixed}            The value of the property to get.
		 */
		getStorage:  function( type, property ) {
			var store = localStorage.getItem( type );

			if ( store ) {
				store = JSON.parse( store );
			} else {
				store = {};
			}

			if ( undefined !== property && store[property] ) {
				return store[property];
			}

			return store;
		},

		/**
		 * Saves a variable into the locale storage.
		 *
		 * @param {string}  type     The object type to store.
		 * @param {string}  property The property of the object to store.
		 * @param {mixed}   value    The value of the property to save.
		 */
		setStorage: function( type, property, value ) {
			var store = this.getStorage( type );

			if ( undefined === value && undefined !== store[ property ] ) {
				delete store[ property ];
			} else {
				// Set property
				store[ property ] = value;
			}

			localStorage.setItem( type, JSON.stringify( store ) );

			return localStorage.getItem( type ) !== null;
		},

		/**
		 * Update the Notice counts & UI.
		 *
		 * @param  {integer} number The amount of trashed notices.
		 * @param  {string} type    The type of the trashed notices.
		 * @return {void}
		 */
		refreshCount: function( number, type ) {
			var count = parseInt( $( '#show-entrepot-notices-link .count' ).html(), 10 ), countType, parent;

			if ( type ) {
				countType = parseInt( $( '#tab-link-entrepot-notice-' + type + ' .count' ).html(), 10 );
			}

			if ( ! number ) {
				number = 0;
			}

			count -= number;
			countType -= number;

			if ( count <= 0 ) {
				if ( $( '#show-entrepot-notices-link' ).hasClass( 'screen-meta-active' ) ) {
					$( '#show-entrepot-notices-link' ).trigger( 'click' );
				}

				$( '#screen-entrepot-notices-wrap' ).remove();
				$( '#entrepot-notices-link-wrap' ).remove();
			} else {
				$( '#show-entrepot-notices-link .count' ).html( count );

				if ( ! type ) {
					return;
				}

				if ( countType <= 0 ) {
					parent = $( '#tab-link-entrepot-notice-' + type ).parent();
					$( '#tab-link-entrepot-notice-' + type ).remove();

					if ( parent.children().length ) {
						$( parent ).find( 'a' ).first().trigger( 'click' );
					}

				} else {
					$( '#tab-link-entrepot-notice-' + type + ' .count' ).html( countType );
				}
			}
		},

		/**
		 * Adds a new WP Screen Meta for the Notices center.
		 *
		 * @return {void}
		 */
		injectNoticesCenter: function() {
			var self = this;

			if ( ! $( '#screen-meta-links' ).length ) {
				$( '#screen-meta' ).after( $( '<div></div>' ).prop( 'id', 'screen-meta-links' ) );
			}

			$( '#screen-meta' ).append(
				$( '<div></div>' ).prop( { id: 'screen-entrepot-notices-wrap', tabindex: '-1' } )
				                  .addClass( 'hidden' )
				                  .append( $( '<div></div>' ).prop( 'id', 'entrepot-notices-back' ) )
				                  .append(
				                  	$( '<div></div>' ).prop( 'id', 'entrepot-notices-columns' )
				                                      .append(
				                                      	$( '<div></div>' ).addClass( 'contextual-help-tabs' ).html( $( '<ul></ul>' ) )
				                                      )
				                                      .append(
				                                      	$( '<div></div>' ).addClass( 'contextual-help-tabs-wrap' )
				                                      )
				                  )
			);

			$( '#screen-meta-links' ).append(
				$( '<div></div>' ).prop( 'id', 'entrepot-notices-link-wrap' )
				                  .addClass( 'hide-if-no-js screen-meta-toggle' )
				                  .html(
				                  	$( '<button></button>' ).prop( {
				                                            	type : 'button',
				                                            	id   : 'show-entrepot-notices-link'
				                                            } )
				                                            .attr( 'aria-controls', 'screen-entrepot-notices-wrap' )
				                                            .attr( 'aria-expanded', false )
				                                            .addClass( 'button show-entrepot-notices show-settings' )
				                                            .html( self.strings.tabTitle )
				                  )
			);
		},

		/**
		 * Fill the Notices center with active notices.
		 *
		 * @return {void}
		 */
		populateNotices: function() {
			var self = this;
			    self.encodedNotices = {};

			$.each( self.notices, function( type, notice ) {
				var trashable = '';
				    self.encodedNotices[ type ] = [];

				if ( type !== 'upgrade' ) {
					trashable = ' class="entrepot-notice-trashable"';
				}

				if ( ! notice.length ) {
					return;
				}

				$( '#screen-entrepot-notices-wrap .contextual-help-tabs ul' ).append(
					$( '<li></li>' ).prop( 'id', 'tab-link-entrepot-notice-' + type )
					                .addClass( type )
					                .html(
					                	$( '<a></a>' ).prop( 'href', '#tab-panel-entrepot-notice-' + type )
					                                  .attr( 'aria-controls', 'tab-panel-entrepot-notice-' + type )
					                                  .html( self.strings.tabLiTitles[ type ] )
					                )
				);

				$.each( notice, function( i, n ) {
					if ( ! n.id ) {
						return;
					}

					var trashedNotices = self.getStorage( 'notices', type );

					if ( trashedNotices && -1 !== $.inArray( n.id, trashedNotices ) ) {
						return;
					}

					self.encodedNotices[ type ].push( {
						'id'          : n.id,
						'content'     : n.short_text,
						'fullContent' : n.full_text
					} );
				} );

				if ( ! self.encodedNotices[ type ].length ) {
					self.refreshCount( notice.length, type );
					return;
				} else if ( self.encodedNotices[ type ].length !== notice.length ) {
					self.refreshCount( notice.length - self.encodedNotices[ type ].length, type );
				}

				$( '#screen-entrepot-notices-wrap .contextual-help-tabs-wrap' ).append(
					$( '<div></div>' ).prop( 'id', 'tab-panel-entrepot-notice-' + type )
					                  .addClass( 'help-tab-content entrepot-' + type )
				);

				$.each( self.encodedNotices[ type ], function( j, en ) {
					$( '#tab-panel-entrepot-notice-' + type ).append( '<div' + trashable + ' data-id="' + en.id + '" data-type="' + type + '">' + en.content.replace( '</p>', ' <a href="#" class="show-notice"><span class="screen-reader-text">' + self.strings.show + '</span></a></p>'  ) + '</div>' );
				} );
			} );

			// Activate the first available Notice type.
			$( '#screen-entrepot-notices-wrap .contextual-help-tabs ul li' ).first().addClass( 'active' );
			$( '#screen-entrepot-notices-wrap .contextual-help-tabs-wrap div' ).first().addClass( 'active' );

			// Add buttons to trash notices.
			$.each( $( '#screen-entrepot-notices-wrap .entrepot-notice-trashable' ), function( k, trashable ) {
				var $el = $( trashable );

				$el.append(
					$( '<button></button>' ).prop( 'type', 'button' )
					                        .addClass( 'entrepot-notice-trash' )
					                        .html(
					                        	$( '<span></span>' ).addClass( 'screen-reader-text' )
					                                                .text( self.strings.trash || '' )
					                        )
				);
			} );
		},

		/**
		 * Uses the local storage to disable notices (Trash).
		 *
		 * @param  {object} event The click on the trash icon of a notice.
		 * @return {void}
		 */
		trashNotice: function( event ) {
			event.preventDefault();

			var $el = $( event.currentTarget ).parent(), stored = this.getStorage( 'notices', $el.data( 'type' ) );

			if ( ! stored.length ) {
				stored = [];
			}

			stored.push( $el.data( 'id' ) );
			this.setStorage( 'notices', $el.data( 'type' ), stored );

			$el.fadeTo( 100, 0, function() {
				$el.slideUp( 100, function() {
					$el.remove();
				} );
			} );

			this.refreshCount( 1, $el.data( 'type' ) );
		},

		/**
		 * Display the full notice.
		 *
		 * @param  {object} event The click on the trash icon of a notice.
		 * @return {void}
		 */
		showNotice: function( event ) {
			event.preventDefault();

			var noticeData = $( event.currentTarget ).closest( '.entrepot-notice-trashable' ).data();

			if ( ! noticeData.type || ! noticeData.id ) {
				return;
			}

			if ( this.encodedNotices[ noticeData.type ] ) {
				var notices = this.encodedNotices[ noticeData.type ];

				$.each( notices, function( n, notice ) {
					if ( notice.id !== noticeData.id || $( '[data-notice-id="' + noticeData.id + '"]' ).length ) {
						return;
					}

					var output = $( notice.fullContent ).addClass( 'notice is-dismissible' ).attr( 'data-notice-id', notice.id );

					// Writes the notice and makes it dismissible.
					$( '#wpbody-content .wrap h1' ).after( output );
					$( document ).trigger( 'wp-updates-notice-added' );
				} );
			}
		},

		/**
		 * Display the list of notices for the clicked tab.
		 *
		 * @param  {object} event The click on one of the Notice type tabs.
		 * @return {void}
		 */
		switchActiveTab: function( event ) {
			var link = $( this ),
				panel;

			event.preventDefault();

			// Don't do anything if the click is for the tab already showing.
			if ( link.is( '.active a' ) ) {
				return false;
			}

			// Links
			$( '#screen-entrepot-notices-wrap .contextual-help-tabs .active' ).removeClass( 'active' );
			link.parent( 'li' ).addClass( 'active' );

			panel = $( link.attr( 'href' ) );

			// Panels
			$( '#screen-entrepot-notices-wrap .help-tab-content').not( panel ).removeClass( 'active' ).hide();
			panel.addClass( 'active' ).show();
		},

		/**
		 * Init the UI and listen to user actions.
		 *
		 * @return {void}
		 */
		init: function() {
			var self = this;

			$.extend( this, entrepotNoticesl10n, { hasNotice: false } );

			$.each( this.notices, function( n, type ) {
				if ( type.length ) {
					self.hasNotice = true;
				}
			} );

			// No notices, no need to carry on.
			if ( ! this.hasNotice ) {
				return;
			}

			// UI.
			this.injectNoticesCenter();
			this.populateNotices();

			// Events.
			$( '#screen-entrepot-notices-wrap .contextual-help-tabs' ).on( 'click', 'a', this.switchActiveTab );
			$( '#screen-entrepot-notices-wrap' ).on( 'click', 'button.entrepot-notice-trash', this.trashNotice.bind( this ) );
			$( '#screen-entrepot-notices-wrap' ).on( 'click', 'a.show-notice', this.showNotice.bind( this ) );
		}
	};

	window.entrepot.Notices.init();

} )( jQuery );
