<?php
/**
 * Entrepôt Customizer functions.
 *
 * @package Entrepôt\inc
 *
 * @since 1.4.0
 */

/**
 * Registers the Entrepôt alternative source of themes.
 *
 * @since 1.4.0
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function entrepot_customize_register( $wp_customize ) {
	if ( ! is_multisite() ) {
        $wp_customize->add_section(
            new WP_Customize_Themes_Section(
                $wp_customize, 'entrepot_themes', array(
                    'title'       => __( 'Thèmes de l’Entrepôt', 'entrepot' ),
                    'action'      => 'entrepot',
                    'filter_type' => 'remote',
                    'capability'  => 'install_themes',
                    'panel'       => 'themes',
                    'priority'    => 10,
                )
            )
        );
    }
}
add_action( 'customize_register', 'entrepot_customize_register' );

/**
 * Load the Entrepôt Themes into the corresponding Customizer section.
 *
 * @since 1.4.0
 *
 * @param  array $themes List of themes data.
 * @param  array $args   List of the arguments of the themes query.
 * @return array         Populated List of Entrepôt themes data.
 */
function entrepot_load_themes( $themes = array(), $args = array() ) {
    if ( ! isset( $_POST['theme_action'] ) || 'entrepot' !== $_POST['theme_action'] ) {
        return $themes;
    }

    $themes = entrepot_get_repositories( '', 'themes' );

    // No Themes in the Entrepôt, stop!
    if ( ! $themes ) {
        return array();
    }

    // Prepare a list of installed themes to check against before the loop.
    $installed_themes = array();
    $wp_themes        = wp_get_themes();
    foreach ( $wp_themes as $theme ) {
        $installed_themes[] = $theme->get_stylesheet();
    }
    $update_php = self_admin_url( 'update.php?action=install-theme' );
    $locale     = get_user_locale();

    // Set up properties for themes available in the Entrepôt.
    foreach ( $themes as &$theme ) {
        $theme->install_url = add_query_arg(
            array(
                'theme'       => $theme->slug,
                '_wpnonce'    => wp_create_nonce( 'install-theme_' . $theme->slug ),
            ), $update_php
        );

        $theme->name        = wp_kses( $theme->name, array() );
        $theme->version     = '';
        $theme->description = entrepot_sanitize_repository_text( $theme->description->{$locale} );
        $theme->stars       = '';
        $theme->num_ratings = 0;
        $theme->preview_url = '';

        if ( ! empty( $theme->urls->preview_url ) ) {
            $theme->preview_url = set_url_scheme( $theme->urls->preview_url );
        }

        // Handle themes that are already installed as installed themes.
        if ( in_array( $theme->slug, $installed_themes, true ) ) {
            $theme->type = 'installed';
        } else {
            $theme->type = 'entrepot';
        }

        // Set active based on customized theme.
        $theme->active = ( isset( $_POST['customized_theme'] ) && $_POST['customized_theme'] === $theme->slug );

        // Map available theme properties to installed theme properties.
        $theme->id           = $theme->slug;
        $theme->screenshot   = array( $theme->screenshot );
        $theme->authorAndUri = wp_kses( $theme->author, array() );

        if ( ! empty( $theme->template ) ) {
            $theme->parent = $theme->template;
        } else {
            $theme->parent = false;
        }

        foreach ( array( 'slug', 'country', 'author', 'releases', 'issues', 'README', 'urls' ) as $rk ) {
            unset( $theme->{$rk} );
        }
    }

    return (object) array(
        'info' => array(
            'page'    => 1,
            'pages'   => 1,
            'results' => count( $themes ),
        ),
        'themes' => $themes,
    );
}
add_filter( 'customize_load_themes', 'entrepot_load_themes', 10, 2 );
