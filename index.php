<?php
/**
 * @wordpress-plugin
 * Plugin Name: Block Search Replace Tool
 * Plugin URI: https://ballarinconsulting.com/acerca
 * Description: Adds an admin page inside the tools menu option with the functionality to perform search & replace actions over blocks that may be found in the content of a site.
 * Version: 0.0.4
 * Requires at least: 6.4
 * Requires PHP: 7
 * Author: David Ballarin Prunera
 * Author URI: https://ballarinconsulting.com/acerca
 * License: GNU General Public License v3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: blocksrtool
 */

/**
 * Require class files
 */
require_once 'inc/class-block-search-replace-tool.php';
require_once 'inc/class-block-style-variation-finder.php';


/**
 * If admin get plugin data and enqueue scripts and style
 * and initialize classes
 */
$plugin_uri = '';
if ( is_admin() ) {


    /**
     * Get plugin data
     */
	if( ! function_exists('get_plugin_data') ){
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	$plugin_data = get_plugin_data( __FILE__ );
    $plugin_uri = $plugin_data['PluginURI'];
    $version = $plugin_data['Version'];


    /**
     * Enqueue a script in the WordPress admin on edit.php.
     * https://developer.wordpress.org/reference/hooks/admin_enqueue_scripts/
     *
     * @param string $hook Hook suffix for the current admin page.
     */
    add_action( 'admin_enqueue_scripts', function( $hook ) use ( $version ) {
        if ( $hook == 'tools_page_blocksrtool' || $hook == 'tools_page_blocksvfinder') {
            wp_enqueue_style( 'blocksrtool_style', plugin_dir_url( __FILE__ ) . 
            'assets/css/style.css', $version );
        }
        if ( $hook == 'tools_page_blocksrtool' ) {
            wp_enqueue_script( 'blocksrtool_scripts', plugin_dir_url( __FILE__ ) . 
            'assets/js/scripts.js', [], $version );
        }
    });


    /**
     * Initialize classes
     */
    $blocksrtool = new BlockSearchReplaceTool( $plugin_uri );
    $blocksvfinder = new BlockStyleVariationFinder( $plugin_uri );
}
