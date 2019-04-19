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
		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );
		// Display an admin message
		add_action( 'admin_notices', array( $this, 'admin_message'));
		// Check for updates
		add_action( 'admin_init', array( $this, 'upgrade_procedure' ) );

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @param integer $blog_id ID of the new blog.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function activate_new_site( $blog_id ) {
		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}
		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();
	}
	/**
	 * Fired when the plugin is activated.
	 *
	 * @param boolean $network_wide True if active in a multiste, false if classic site.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			if ( $network_wide ) {
				// Get all blog ids
				$blogs = get_sites();
				foreach ( $blogs as $blog ) {
					switch_to_blog( $blog->blog_id );
					self::single_activate();
					restore_current_blog();
				}
				return;
			}
		}
		self::single_activate();
	}
	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @param boolean $network_wide True if WPMU superadmin uses
	 *                              "Network Deactivate" action, false if
	 *                              WPMU is disabled or plugin is
	 *                              deactivated on an individual blog.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function deactivate( $network_wide ) {
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			if ( $network_wide ) {
				// Get all blog ids
				$blogs = get_sites();
				foreach ( $blogs as $blog ) {
					switch_to_blog( $blog->blog_id );
					self::single_deactivate();
					restore_current_blog();
				}
				return;
			}
		}
		self::single_deactivate();
	}
	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since 1.0.0
	 * @uses	CB2_Database
	 *
	 * @return void
	 */
	public static function single_activate() {
		// Requirements Detection System - read the doc/example in the library file
		// new Plugin_Requirements( CB_NAME, CB_TEXTDOMAIN, array(
		// 	'WP' => new WordPress_Requirement( '4.6.0' )
		// 		) );
		// @TODO: Define activation functionality here

		/* install db tables via CB2_Database */
		$sql = CB2_Database::install();

		// set admin message
		set_transient('CB2_message_ActDeact', $sql, 0);

		self::add_roles();
		self::add_capabilities();
		self::upgrade_procedure();
		CB2_Settings::set_default_options();

		// Clear the permalinks
		flush_rewrite_rules();
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private static function single_deactivate() {
		// @TODO: Define deactivation functionality here
		// Clear the permalinks
		flush_rewrite_rules();
	}

	public static function add_roles() {
		add_role( 'cb2_contributor', __( 'CB2 Contributor' ) );
		add_role( 'cb2_subscriber',  __( 'CB2 Subscriber' ) );
	}

	public static function add_capabilities() {
		// Add the capabilites to all the roles
		$caps = array(
			'cb2_view_others_posts_in_backend' => TRUE,
			'cb2_view_linked_posts'            => TRUE,
			'cb2_edit_linked_posts'            => TRUE,
		);
		$roles = array(
			'administrator',
			'editor',
			'cb2_contributor',
		);
		foreach ( $roles as $role_name ) {
			$role = get_role( $role_name );
			foreach ( $caps as $cap_name => $default )
				$role->add_cap( $cap_name );
		}
	}

		/**
	 * Upgrade procedure
	 *
	 * @return void
	 */
	public static function upgrade_procedure() {
		if ( is_admin() ) {
			$version = get_option( 'cb2_version' );
			if ( version_compare( CB2_VERSION, $version, '>' ) ) {
				update_option( 'cb2_version', CB2_VERSION );
				delete_option( CB2_TEXTDOMAIN . '_fake-meta' );
			}
		}
	}

  /**
  * Display admin message
  *
  * @return void
  */
	public function admin_message( ) {
    /* Check transient, if available display notice */
    if (get_transient('CB2_message_ActDeact')) {
			$return = get_transient('CB2_message_ActDeact');
        ?>
        <div class="updated notice is-dismissible">
            <strong>CB2 activated</strong>. Install script says: <?php echo $return; ?>
        </div>
        <?php
        /* Delete transient, only display this notice once. */
        delete_transient('CB2_message_ActDeact');
    }
	}
}




new CB2_ActDeact();
