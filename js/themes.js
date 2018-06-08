/* global entrepotl10nThemes */
( function( $, wp ){

    if ( undefined === typeof entrepotl10nThemes ) {
		return false;
    }

    var entrepot  = _.extend( window.entrepot || {}, _.pick( wp, 'themes' ) );
    var themeView = entrepot.themes.view.Theme;

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

    $( '.wp-filter .filter-links' ).append(
        $( '<li></li>' ).html(
            $( '<a></a>' ).html( entrepotl10nThemes.tabText )
                          .prop( 'href', '#' )
                          .attr( 'data-sort', 'entrepot' )
        )
    );

}( jQuery, window.wp || {} ) );
