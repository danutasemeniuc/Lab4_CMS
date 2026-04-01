<?php
/**
 * Plugin Name: USM Notes
 * Plugin URI:  https://github.com/usm/usm-notes
 * Description: Adaugă o secțiune „Notițe" cu priorități și o dată de reamintire. Suportă shortcode [usm_notes].
 * Version:     1.0.0
 * Author:      Student USM
 * Text Domain: usm-notes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Nu permite accesul direct la fișier
}

define( 'USM_NOTES_VERSION', '1.0.0' );
define( 'USM_NOTES_DIR', plugin_dir_path( __FILE__ ) );
define( 'USM_NOTES_URL', plugin_dir_url( __FILE__ ) );

require_once USM_NOTES_DIR . 'includes/class-usm-notes.php';

/**
 * Inițializarea pluginului după ce toate pluginurile sunt încărcate.
 */
function usm_notes_init() {
    $plugin = new USM_Notes();
    $plugin->init();
}
add_action( 'plugins_loaded', 'usm_notes_init' );
