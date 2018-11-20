<?php
/**
 * Hooks for activation/deactivation, installer, upgrade.
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
/**
 * This class contain the activate and deactive method and relates.
 */
class CB2_ActDeact {
	/**
	 * Initialize the Act/Deact
	 *
	 * @return void
	 */
	function __construct() {

		self::single_activate();

		add_action( 'admin_init', array( $this, 'upgrade_procedure' ) );
	}
	/**
	 * Fired when the plugin is activated.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	private static function single_activate() {

		// Requirements Detection System - read the doc/example in the library file
		// new Plugin_Requirements( CB2_NAME, CB2_TEXTDOMAIN, array(
		// 	'WP' => new WordPress_Requirement( '4.6.0' )
		// 		) );
		// @TODO: Define activation functionality here
		// add_role( 'advanced', __( 'Advanced' ) ); //Add a custom roles

		/* install db tables via CB2_DB */
		$return = CB2_Database::install_SQL();

		new WP_Admin_Notice('CB2 activated, install script says: <strong>' . $return . '</strong>');


		self::add_capabilities();
		self::upgrade_procedure();
		// Clear the permalinks
		// flush_rewrite_rules();
	}
	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	private static function single_deactivate() {
		// @TODO: Define deactivation functionality here
		// Clear the permalinks
		flush_rewrite_rules();
	}

		/**
	 * Add admin capabilities
	 *
	 * @return void
	 */
	public static function add_capabilities() {
		// Add the capabilites to all the roles
		$caps = array(
			'create_plugins',
			'read_demo',
			'read_private_demoes',
			'edit_demo',
			'edit_demoes',
			'edit_private_demoes',
			'edit_published_demoes',
			'edit_others_demoes',
			'publish_demoes',
			'delete_demo',
			'delete_demoes',
			'delete_private_demoes',
			'delete_published_demoes',
			'delete_others_demoes',
			'manage_demoes',
		);
		$roles = array(
			get_role( 'administrator' ),
			get_role( 'editor' ),
			get_role( 'author' ),
			get_role( 'contributor' ),
			get_role( 'subscriber' ),
		);
		foreach ( $roles as $role ) {
			foreach ( $caps as $cap ) {
				$role->add_cap( $cap );
			}
		}
		// Remove capabilities to specific roles
		$bad_caps = array(
			'create_demoes',
			'read_private_demoes',
			'edit_demo',
			'edit_demoes',
			'edit_private_demoes',
			'edit_published_demoes',
			'edit_others_demoes',
			'publish_demoes',
			'delete_demo',
			'delete_demoes',
			'delete_private_demoes',
			'delete_published_demoes',
			'delete_others_demoes',
			'manage_demoes',
		);
		$roles = array(
			get_role( 'author' ),
			get_role( 'contributor' ),
			get_role( 'subscriber' ),
		);
		foreach ( $roles as $role ) {
			foreach ( $bad_caps as $cap ) {
				$role->remove_cap( $cap );
			}
		}
	}

		/**
	 * Upgrade procedure
	 *
	 * @return void
	 */
	public static function upgrade_procedure() {
		if ( is_admin() ) {
			$version = get_option( 'commons-booking-version' );
			if ( version_compare( CB2_VERSION, $version, '>' ) ) {
				update_option( 'commons-booking-version', CB2_VERSION );
				delete_option( CB2_TEXTDOMAIN . '_fake-meta' );
			}
		}
	}

}
new CB2_ActDeact();
