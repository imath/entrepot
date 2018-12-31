<?php
/**
 * Entrepôt's Block Upgrader.
 *
 * @package Entrepôt\inc\classes
 *
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_Upgrader') ) {
	require ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
}

/**
 * Entrepôt class used to upgrade/install blocks.
 *
 * It is designed to upgrade/install blockss from remote zip URL hosted on GitHub.com.
 *
 * @since 1.5.0
 *
 * @see WP_Upgrader
 */
class Entrepot_Block_Upgrader extends WP_Upgrader {
	/**
	 * Block install/upgrade result.
	 *
	 * @since 1.5.0
	 * @var array|WP_Error $result
	 *
	 * @see WP_Upgrader::$result
	 */
	public $result;

	/**
	 * Initialize the upgrade strings.
	 *
	 * @since 1.5.0
	 */
	public function upgrade_strings() {
        $this->strings = array_merge( (array) $this->strings, array(
            'up_to_date'          => esc_html__( 'Le bloc a été mis à jour pour sa version la plus récente.', 'entrepot' ),
            'no_package'          => esc_html__( 'L’archive du paquet de la mise à jour n’est pas disponible.', 'entrepot' ),
            /* translators: %s: package URL */
            'downloading_package' => sprintf( esc_html__( 'Téléchargement de la mise à jour depuis %s...', 'entrepot' ), '<span class="code">%s</span>' ),
            'unpack_package'      => esc_html__( 'Décompression en cours de la mise à jour...', 'entrepot' ),
            'remove_old'          => esc_html__( 'Suppression de la précédente version du bloc...', 'entrepot' ),
            'remove_old_failed'   => esc_html__( 'La suppression de la précédente version du bloc a échoué.', 'entrepot' ),
            'process_failed'      => esc_html__( 'La mise à jour du bloc a échoué.', 'entrepot' ),
            'process_success'     => esc_html__( 'Mise à jour du bloc effectuée avec succès.', 'entrepot' ),
        ) );
	}

	/**
	 * Initialize the installation strings.
	 *
	 * @since 1.5.0
	 */
	public function install_strings() {
        $this->strings = array_merge( (array) $this->strings, array(
            'no_package'          => esc_html__( 'L’archive du paquet d’installation n’est pas disponible.', 'entrepot' ),
            /* translators: %s: package URL */
            'downloading_package' => sprintf( esc_html__( 'Téléchargement du paquet d’installation depuis %s...', 'entrepot' ), '<span class="code">%s</span>' ),
            'unpack_package'      => esc_html__( 'Décompression en cours du paquet d’installation...', 'entrepot' ),
            'installing_package'  => esc_html__( 'Installation du bloc en cours...', 'entrepot' ),
            'no_files'            => esc_html__( 'Le paquet d’installation ne contient aucun fichier.', 'entrepot' ),
            'process_failed'      => esc_html__( 'L’installation du bloc a échoué.', 'entrepot' ),
            'process_success'     => esc_html__( 'L’installation du bloc effectuée avec succès.', 'entrepot' ),
        ) );
	}

	/**
	 * Install a block package.
	 *
	 * @since 1.5.0
	 *
	 * @param string $package The full local path or URI of the package.
	 * @param array  $args {
	 *     Optional. Other arguments for installing a block package. Default empty array.
	 *
	 *     @type bool $clear_update_cache Whether to clear the block updates cache if successful.
	 *                                    Default true.
	 * }
	 * @return bool|WP_Error True if the installation was successful, false or a WP_Error otherwise.
	 */
	public function install( $package, $args = array() ) {
		$defaults = array(
			'clear_update_cache' => true,
		);
		$parsed_args = wp_parse_args( $args, $defaults );

		$this->init();
		$this->install_strings();

		add_filter('upgrader_source_selection', array( $this, 'check_package' ) );
		if ( $parsed_args['clear_update_cache'] ) {
			// Clear Blocks cache.
			add_action( 'upgrader_process_complete', 'entrepot_blocks_clear_cache', 9, 0 );
		}

		$this->run( array(
			'package'           => $package,
			'destination'       => trailingslashit( entrepot_blocks_dir() ) . wp_basename( $args['block'] ),
			'clear_destination' => false, // Do not overwrite files.
			'clear_working'     => true,
			'hook_extra' => array(
				'type'   => 'entrepot_block',
				'action' => 'install',
            ),
		) );

		remove_action( 'upgrader_process_complete', 'entrepot_blocks_clear_cache', 9 );
		remove_filter( 'upgrader_source_selection', array( $this, 'check_package' ) );

		if ( ! $this->result || is_wp_error( $this->result ) ) {
            return $this->result;
        }

		// Force refresh of block update information
		entrepot_blocks_clear_cache( $parsed_args['clear_update_cache'] );

		return true;
	}

	/**
	 * Check a source package to be sure it contains a block.
	 *
	 * This function is added to the {@see 'upgrader_source_selection'} filter by
	 * Entrepot_Block_Upgrader::install().
	 *
	 * @since 1.5.0
	 *
	 * @global WP_Filesystem_Base $wp_filesystem Subclass
	 *
	 * @param string $source The path to the downloaded package source.
	 * @return string|WP_Error The source as passed, or a WP_Error object
	 *                         if no blocks were found.
	 */
	public function check_package( $source ) {
		global $wp_filesystem;

		if ( is_wp_error( $source ) ) {
            return $source;
        }

		$working_directory = str_replace( $wp_filesystem->wp_content_dir(), trailingslashit( WP_CONTENT_DIR ), $source );

        // Sanity check, if the above fails, let's not prevent installation.
        if ( ! is_dir( $working_directory ) ) {
            return $source;
        }

		// Check the folder contains at least 1 valid block.
		$blocks_found = false;
		$files = glob( $working_directory . 'block.json' );
		if ( $files ) {
			foreach ( $files as $file ) {
                $json_data  = file_get_contents( $file );
                $block_data = json_decode( $json_data );
				if ( isset( $block_data->name ) && $block_data->name ) {
					$blocks_found = true;
					break;
				}
			}
		}

		if ( ! $blocks_found ) {
            return new WP_Error( 'incompatible_archive_no_blocks', $this->strings['incompatible_archive'], __( 'Aucun bloc valide n’a été trouvé.', 'entrepot' ) );
        }

		return $source;
	}
}
