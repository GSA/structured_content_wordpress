<?php
/**
 *
 * @package   Structured Content
 * @author    Phillihp Harmon <sitessupport@gsa.gov>
 * @license   GPL-2.0+
 * @link      http://sites.usa.gov/plugins#structured
 * @copyright 2015 CTACorp
 *
 * @wordpress-plugin
 * Plugin Name:       Structured Content
 * Plugin URI:        http://sites.usa.gov/plugins/
 * Description:       Our plugin focuses on creating structured content following the <a href='http://gsa.github.io/Open-And-Structured-Content-Models/' target='_blank'>Government Open and Structured Content</a> definition so that it can be easily syndicated and shared.
 * Version:           0.1
 * Author:            Phillihp Harmon
 * Author URI:        http://sites.usa.gov/plugins/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

//PLUGIN CLASS FILE
require_once( plugin_dir_path( __FILE__ ) . 'public/class-structured-content.php' );
require_once( plugin_dir_path( __FILE__ ) . 'admin/structured-content-dialog.php' );

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 */
register_activation_hook( __FILE__, array( 'Structured_Content', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Structured_Content', 'deactivate' ) );

add_action('plugins_loaded', array('Structured_Content', 'get_instance'));
add_action('rss2_item', array('Structured_Content', 'custom_rss_fields'));

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

	add_action( 'admin_init', 'oasc_register_settings' );
	require_once( plugin_dir_path( __FILE__ ) . 'admin/structured-content-admin.php' );
	add_action( 'plugins_loaded', array( 'Structured_Content_Admin', 'get_instance' ) );
}

function oasc_register_settings() {
    register_setting( 'oasc-myoption-group', 'namespace-url' );
    add_settings_section('oasc_plugin_main', 'Main Settings', 'oasc_plugin_section_text', 'structured-content');
    add_settings_field('plugin_text_string', 'Namespace URL', 'oasc_plugin_setting_string', 'structured-content', 'oasc_plugin_main');
}

function oasc_plugin_section_text() {
	echo "<p>Please enter your namespace URL</p>";
}

function oasc_plugin_setting_string() {
	$options = get_option('namespace-url');
	echo "<input id='plugin_text_string' size='50' name='namespace-url' value='".get_option('namespace-url')."' />";
}


add_action('plugins_loaded', array('Structured_Content_Dialog', 'get_instance'));
add_action('wp_ajax_get_data', array('Structured_Content_Dialog', 'get_data'));

function initActions() {
    add_feed('article', array('Structured_Content', 'stream_article'));
    add_feed('article_list', array('Structured_Content', 'stream_article_list'));
}

add_action( 'init', 'initActions' );

update_option('namespace-url', "https://sites.usa.gov/wp-content/uploads/2015/04/oasc-definition.xml");

?>
