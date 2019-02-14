<?php
/*
Plugin Name: Geo hCard Map
Plugin URI: https://wordpress.org/plugins/geo-hcard-map
Description: Creates a map from the hCard markup (.vcard) in the current webpage at shortcode [geo_hcard_map]
Version: 1.0
Author: Annesley Newholm
License: GPL2
Text Domain: geo-hcard-map
*/

// No script kiddies
if ( ! defined( 'ABSPATH' ) ) exit; 

define( 'GEO_HCARD_MAP', 'geo-hcard-map' );
define( 'GEO_HCARD_MAP_TITLE', 'GEO hCard Map' );
define( 'GEO_HCARD_MAP_VERSION', '1.0' );
define( 'GEO_HCARD_MAP_MINIMUM_PHP_VERSION', '4.0' );
define( 'GEO_HCARD_MAP_MINIMUM_WP_VERSION', '4.0' );

/* -------------------------------------------- Infrastructure -------------------------------------------- */
add_action( 'admin_menu', 'geo_hcard_map_add_admin_menu' );
add_action( 'admin_init', 'geo_hcard_map_settings_init' );

function geo_hcard_map_add_admin_menu() { 
  add_submenu_page( 'options-general.php', GEO_HCARD_MAP_TITLE, GEO_HCARD_MAP_TITLE, 'manage_options', GEO_HCARD_MAP, 'geo_hcard_map_options_page', 'dashicons-admin-home' );
}

function geo_hcard_map_settings_init() { 
  register_setting( 'geo_hcard_map_plugin_page', 'geo_hcard_map_settings' );
  add_settings_section(
    'geo_hcard_map_pluginPage_section', 
    '', 
    '', 
    'geo_hcard_map_plugin_page'
  );

  add_settings_field( 
    'geo_hcard_map_type', 
    'GEO hCard Map Type', 
    'geo_hcard_map_type_render', 
    'geo_hcard_map_plugin_page', 
    'geo_hcard_map_pluginPage_section' 
  );
}

function geo_hcard_map_options_page() { 
  ?>
  <form action='options.php' method='post'>
    <h2>GEO hCard Map settings</h2>
    
    <h3>Available short_codes</h3>
    <ul class="shortcodes-list">
      <li><strong>[geo_hcard_map]</strong> - generate an a map of all hCard microdata on the page.</li>
    </ul>
    
    <?php
      settings_fields( 'geo_hcard_map_plugin_page' );
      do_settings_sections( 'geo_hcard_map_plugin_page' );
      submit_button();
    ?>
  </form>
  <?php
}

function geo_hcard_map_type_render() {
  $options = get_option( 'geo_hcard_map_settings' );
  $optval  = '';
  if (isset($options['geo_hcard_map_type'])) $optval = $options['geo_hcard_map_type'];
  print( '<select name="geo_hcard_map_settings[geo_hcard_map_type]">' );
  print( '<option value="">-- select --</option>' );
  print( '<option ' . ($optval == 'osm' ? 'selected="1"' : '' ) . ' value="osm">Open Street Map (ODbL data license)</option>' );
  print( '</select>' );
}

/* -------------------------------------------- Infrastructure -------------------------------------------- */
function geo_hcard_map_load_admin_scripts() {
  wp_enqueue_script( GEO_HCARD_MAP . '-admin',        plugins_url( 'js/admin.js',  __FILE__ ),          array( 'jquery' ), GEO_HCARD_MAP_VERSION );
  wp_enqueue_style(  GEO_HCARD_MAP . '-admin-styles', plugins_url( 'css/admin.css', __FILE__ ), array(), GEO_HCARD_MAP_VERSION );
}
add_action( 'admin_enqueue_scripts', 'geo_hcard_map_load_admin_scripts' );

function geo_hcard_map_load_scripts() {
  // TODO: conditional inclusion
  $options = get_option( 'geo_hcard_map_settings' );
  wp_register_script( GEO_HCARD_MAP . '-plugin-options', plugins_url( 'js/options.js',  __FILE__ ) );
  wp_localize_script( GEO_HCARD_MAP . '-plugin-options', 'geo_hcard_map_settings', $options);
  wp_enqueue_script(  GEO_HCARD_MAP . '-plugin-options');
  
  wp_enqueue_script( GEO_HCARD_MAP . '-leaflet', plugins_url( 'leaflet/leaflet.js', __FILE__ ),  array( 'jquery' ), GEO_HCARD_MAP_VERSION );
  wp_enqueue_script( GEO_HCARD_MAP,              plugins_url( 'js/map.js',  __FILE__ ),          array( GEO_HCARD_MAP . '-leaflet', GEO_HCARD_MAP . '-plugin-options' ), GEO_HCARD_MAP_VERSION );
}
add_action( 'wp_enqueue_scripts', 'geo_hcard_map_load_scripts' );

function geo_hcard_map_load_styles() {
  // TODO: conditional inclusion
  wp_enqueue_style(  'leaflet',                 plugins_url( 'leaflet/leaflet.css', __FILE__ ), array(), GEO_HCARD_MAP_VERSION );
  wp_enqueue_style(  GEO_HCARD_MAP . '-styles', plugins_url( 'css/style.css', __FILE__ ), array(), GEO_HCARD_MAP_VERSION );
}
add_action( 'wp_enqueue_scripts',  'geo_hcard_map_load_styles' );

function geo_hcard_map_plugins_loaded(){
  add_shortcode( str_replace( '-', '_', GEO_HCARD_MAP ), 'geo_hcard_map_shortcode_handler');
}
add_action( 'plugins_loaded', 'geo_hcard_map_plugins_loaded' , 20 );

function geo_hcard_map_shortcode_handler( $tag, $content = NULL ) {
  return '<div id="' . GEO_HCARD_MAP . '">&nbsp;</div>';
}

function geo_hcard_map_plugin_activation() {
  if ( version_compare( phpversion(), GEO_HCARD_MAP_MINIMUM_PHP_VERSION, '<' ) ) {
    load_plugin_textdomain( GEO_HCARD_MAP );
    add_option( GEO_HCARD_MAP . '_show_activation_php_version_fail_message', TRUE );
    $ok = FALSE;
  }
  else if ( version_compare( $GLOBALS['wp_version'], GEO_HCARD_MAP_MINIMUM_WP_VERSION, '<' ) ) {
    load_plugin_textdomain( GEO_HCARD_MAP );
    add_option( GEO_HCARD_MAP . '_show_activation_version_fail_message', TRUE );
    $ok = FALSE;
  } 
  else {
    add_option( GEO_HCARD_MAP . '_show_activation_message', TRUE );
  }
}
register_activation_hook(   __FILE__, 'geo_hcard_map_plugin_activation' );

function geo_hcard_map_admin_notices() {
  if ( get_option( GEO_HCARD_MAP . '_show_activation_php_version_fail_message' ) ) {
    delete_option( GEO_HCARD_MAP . '_show_activation_php_version_fail_message' );
    print( '<div class="error"><p><strong>' . GEO_HCARD_MAP . ' requires PHP ' . GEO_HCARD_MAP_MINIMUM_PHP_VERSION . ' or higher.</strong>' );
    print( 'Please <a href="http://php.net/manual/en/migration5.php">upgrade PHP</a> to a current version.</p></div>' );
  }

  if ( get_option( GEO_HCARD_MAP . '_show_activation_version_fail_message' ) ) {
    delete_option( GEO_HCARD_MAP . '_show_activation_version_fail_message' );
    print( '<div class="error"><p><strong>' . GEO_HCARD_MAP . ' requires WordPress ' . GEO_HCARD_MAP_MINIMUM_WP_VERSION . ' or higher.</strong>' );
    print( 'Please <a href="https://codex.wordpress.org/Upgrading_WordPress">upgrade WordPress</a> to a current version.</p></div>' );
  }

  $options = get_option( 'geo_hcard_map_settings' );
  $optval  = '';
  if (isset($options['geo_hcard_map_type'])) $optval = $options['geo_hcard_map_type'];
  
  if ( empty( $optval ) ) {
    print( '<div id="' . GEO_HCARD_MAP . '-settings-notice" class="update-nag is-dismissible"><p><a href="admin.php?page=geo_hcard_map">Geo hCard map</a>: which mapping system would you like to use?</p>' );
    print( '<form action="options.php" method="post" class="geo-hcard-map-ajax">' );
    settings_fields( 'geo_hcard_map_plugin_page' );
    do_settings_sections( 'geo_hcard_map_plugin_page' );
    submit_button();
    print( '</form>' );
    print( '<div class="submitting">submitting...</div>' );
    print( '<div class="success">Thanks, enjoy!</div>' );
    print( '</div>' );
  }
}
add_action( 'admin_notices', 'geo_hcard_map_admin_notices' );

