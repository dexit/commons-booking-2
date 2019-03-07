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

		// add_role( 'advanced', __( 'Advanced' ) ); //Add a custom roles
		add_role(
			'cb2_contributor',
			__( 'CB2 Contributor' ),
			array(
				'read'         => true,  // true allows this capability
				'edit_posts'   => true,
				'delete_posts' => false, // Use false to explicitly deny
			)
		);
		self::add_capabilities();
		self::upgrade_procedure();
		self::add_bookingpage();
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
		/**
	 * Add admin capabilities
	 *
	 * @todo
	 *
	 * @return void
	 */
	public static function add_bookingpage() {
		if (!CB2_Settings::get('pages_page-booking')) { // page set

    global $wpdb;
    // Create post object
    $booking_page = array(
        'post_title' => __('Booking', 'commmons-booking-2'),
        'post_content' => 'Bookings page',
        'post_status' => 'publish',
        'post_author' => 1,
        'post_type' => 'page',
    );

    // Insert the post into the database
    wp_insert_post($booking_page);
}



	}
		/**
	 * Add admin capabilities
	 *
	 * @todo
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
