/* global entrepotl10nThemes */
( function( $, wp ){

    if ( undefined === typeof entrepotl10nThemes ) {
		return false;
    }

    var entrepot         = _.extend( window.entrepot || {}, _.pick( wp, 'themes' ) );
    var themesView       = entrepot.themes.view.Themes;
    var themeView        = entrepot.themes.view.Theme;
    var themeDetailsView = entrepot.themes.view.Details;

    /**
     * Themes' view overrides.
     */
    wp.themes.view.Themes = themesView.extend( {
        render: function() {
            // Shuffle Entrepôt Themes before they are rendered.
            if ( entrepot.themes.router && 'entrepot' === entrepot.themes.router.selectedTab ) {
                this.collection.reset( this.collection.shuffle(), { silent: true } );
            }

            // Render Themes.
            themesView.prototype.render.apply( this, arguments );
        }
    } );

    /**
     * Theme's view overrides.
     */
    wp.themes.view.Theme = themeView.extend( {
        render: function() {
            // First render the theme
            themeView.prototype.render.apply( this, arguments );

            // Then edit texts if needed.
            if ( entrepot.themes.router && 'entrepot' === entrepot.themes.router.selectedTab ) {
                if ( ! this.model.get( 'hasPreview' ) ) {
                    var btnDetails  = this.$el.find( '.install-theme-preview' );

                    this.$el.find( '.more-details' ).html( entrepotl10nThemes.moreText );

                    if ( btnDetails.length ) {
                        btnDetails.html( entrepotl10nThemes.btnText );
                    }
                }
            }
        }
    } );

    /**
     * Theme's Details view overrides.
     */
    wp.themes.view.Details = themeDetailsView.extend( {
        render: function() {
            // First render the theme
            themeDetailsView.prototype.render.apply( this, arguments );

            // Then edit texts if needed.
            var entrepotData = this.model.get( 'entrepotData' );

            if ( entrepotData ) {
                var extraActions = $( '<div></div>' ).prop( 'class', 'entrepot-actions' );

                $.each( entrepotData, function( i, ed ) {
                    extraActions.append( $( '<a></a>' ).html( ed.text ).prop( {
                        'href': ed.url,
                        'class': i + ' button'
                    } ) );
                } );

                this.$el.find( '.theme-actions' ).prepend( extraActions );
            }
        }
    } );

    $( '.wp-filter .filter-links' ).append(
        $( '<li></li>' ).html(
            $( '<a></a>' ).html( entrepotl10nThemes.tabText )
                          .prop( 'href', '#' )
                          .attr( 'data-sort', 'entrepot' )
        )
    );

}( jQuery, window.wp || {} ) );
