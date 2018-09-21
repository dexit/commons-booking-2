<?php
/**
 * Commons_Booking
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
/**
 * Provide Import and Export of the settings of the plugin
 */
class CB_ImpExp {
	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 */
	public function __construct() {
		// Add the export settings method
		add_action( 'admin_init', array( $this, 'settings_export' ) );
		// Add the import settings method
		add_action( 'admin_init', array( $this, 'settings_import' ) );
	}
	/**
	 * Process a settings export from config
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function settings_export() {
		if ( empty( $_POST[ 'cb_action' ] ) || 'export_settings' !== $_POST[ 'cb_action' ] ) {
			return;
		}
		if ( !wp_verify_nonce( $_POST[ 'cb_export_nonce' ], 'cb_export_nonce' ) ) {
			return;
		}
		if ( !current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings[ 0 ] = get_option( CB2_TEXTDOMAIN . '-settings' );
		$settings[ 1 ] = get_option( CB2_TEXTDOMAIN . '-settings-second' );
		ignore_user_abort( true );
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=pn-settings-export-' . date( 'm-d-Y' ) . '.json' );
		header( "Expires: 0" );
		if ( version_compare( PHP_VERSION, '5.4.0', '>=' ) ) {
			echo wp_json_encode( $settings, JSON_PRETTY_PRINT );
		} else {
			echo wp_json_encode( $settings );
		}
		exit;
	}
	/**
	 * Process a settings import from a json file
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function settings_import() {
		if ( empty( $_POST[ 'cb_action' ] ) || 'import_settings' !== $_POST[ 'cb_action' ] ) {
			return;
		}
		if ( !wp_verify_nonce( $_POST[ 'cb_import_nonce' ], 'cb_import_nonce' ) ) {
			return;
		}
		if ( !current_user_can( 'manage_options' ) ) {
			return;
		}
		$extension = end( explode( '.', $_FILES[ 'import_file' ][ 'name' ] ) );
		if ( $extension !== 'json' ) {
			wp_die( __( 'Please upload a valid .json file', CB2_TEXTDOMAIN ) );
		}
		$import_file = $_FILES[ 'import_file' ][ 'tmp_name' ];
		if ( empty( $import_file ) ) {
			wp_die( __( 'Please upload a file to import', CB2_TEXTDOMAIN ) );
		}
		// Retrieve the settings from the file and convert the json object to an array.
		$settings = ( array ) json_decode( file_get_contents( $import_file ) );
		update_option( CB2_TEXTDOMAIN . '-settings', get_object_vars( $settings[ 0 ] ) );
		update_option( CB2_TEXTDOMAIN . '-settings-second', get_object_vars( $settings[ 1 ] ) );
		wp_safe_redirect( admin_url( 'options-general.php?page=' . CB2_TEXTDOMAIN ) );
		exit;
	}
}
new CB_ImpExp();
