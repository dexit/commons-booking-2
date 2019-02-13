<?php
/**
 * CB2_Extras Admin
 *
 *  This class contains all the snippet or extras that improve the experience on the backend
 *
 * @TODO: Lots of helper functions that *could* be implemented later
 *
 * @package   Commons Booking
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
class CB2_Extras_Admin {
	/**
	 * Initialize the snippet
	 */
	function initialize() {

		$this->check_wp_environment(); // check plugin environment and notify the user if things are not properly defined.

		if ( WP_DEBUG ) { // add a build version entry in the top menu
			add_action('admin_bar_menu', array ( $this, 'show_build_info'), 100);
		}

		// Activity Dashboard widget for your cpts
		// add_filter( 'dashboard_recent_posts_query_args', array( $this, 'cpt_activity_dashboard_support' ), 10, 1 );
		// Add bubble notification for cpt pending
		// add_action( 'admin_menu', array( $this, 'pending_cpt_bubble' ), 999 );

	}
		/**
	 * Add the recents post type in the activity widget<br>
	 * NOTE: add in $post_types your cpts
	 *
	 * @param array $query_args The content of the widget.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	function cpt_activity_dashboard_support( $query_args ) {
		if ( !is_array( $query_args[ 'post_type' ] ) ) {
			// Set default post type
			$query_args[ 'post_type' ] = array( 'page' );
		}
		$query_args[ 'post_type' ] = array_merge( $query_args[ 'post_type' ], $this->cpts );
		return $query_args;
	}
	 /**
	 * Show dev build date in admin toolbar
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function show_build_info($admin_bar){
			$admin_bar->add_menu( array(
				'id'    => 'cb2-build',
				'title' => 'CB' . CB2_VERSION . '-' . CB2_DEV_BUILD,
				'href'  => '#',
				'meta'  => array(
					'title' => CB2_DEV_BUILD,
				),
			));
		}
			/**
	 * Bubble Notification for pending cpt<br>
	 * NOTE: add in $post_types your cpts<br>
	 *
	 *        Reference:  http://wordpress.stackexchange.com/questions/89028/put-update-like-notification-bubble-on-multiple-cpts-menus-for-pending-items/95058
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	function pending_cpt_bubble() {
		global $menu;
		$post_types = $this->cpts;
		foreach ( $post_types as $type ) {
			if ( !post_type_exists( $type ) ) {
				continue;
			}
			// Count posts
			$cpt_count = wp_count_posts( $type );
			if ( $cpt_count->pending ) {
				// Menu link suffix, Post is different from the rest
				$suffix = ( 'post' === $type ) ? '' : '?post_type=' . $type;
				// Locate the key of
				$key = self::recursive_array_search_php( 'edit.php' . $suffix, $menu );
				// Not found, just in case
				if ( !$key ) {
					return;
				}
				// Modify menu item
				$menu[ $key ][ 0 ] .= sprintf(
						'<span class="update-plugins count-%1$s"><span class="plugin-count">%1$s</span></span>', $cpt_count->pending
				);
			}
		}
	}
	/**
	 * WP Plugin Environment check for requirements, print message if failed
	 *
	 * @since 2.0.0
	 * @uses WP_Admin_Notice
	 *
	 */
	function check_wp_environment(  ) {

		$notices_array = array();

		// check if pretty permalinks are enabled
		if ( ! get_option('permalink_structure') ) {  $notices_array[] = __( '"Pretty" Permalinks need to be enabled for Commons Booking to work.', 'commons-booking-2');
		}

		if ( $notices_array ) {
			foreach ( $notices_array as $notice ) {
				new WP_Admin_Notice( $notice , 'error' );
			}
		}

	}
	/**
	 * Required for the bubble notification<br>
	 *
	 *        Reference:  http://wordpress.stackexchange.com/questions/89028/put-update-like-notification-bubble-on-multiple-cpts-menus-for-pending-items/95058
	 *
	 *
	 * @param array $needle   First parameter.
	 * @param array $haystack Second parameter.
	 *
	 * @since 2.0.0
	 *
	 * @return mixed
	 */
	private function recursive_array_search_php( $needle, $haystack ) {
		foreach ( $haystack as $key => $value ) {
			$current_key = $key;
			if ( $needle === $value OR ( is_array( $value ) && self::recursive_array_search_php( $needle, $value ) !== false) ) {
				return $current_key;
			}
		}
		return false;
	}
			/**
	 * This method contain an example of code for caching a transient with an external request and parse the results.
	 *
	 * @return void
	 */
	public function transient_caching_example() {
		$key = 'siteapi_json_transient';
		// Let's see if we have a cached version
		$json_output = get_transient( $key );
		if ( $json_output === false || empty( $json_output ) ) {
			// If there's no cached version we ask
			$response = wp_remote_get( "http://www.siteapi.org/api/v1/projects?page=1" );
			if ( is_wp_error( $response ) ) {
				// In case API is down we return the last successful count
				return;
			}
			// If everything's okay, parse the body and json_decode it
			$json_output = json_decode( wp_remote_retrieve_body( $response ) );
			// Store the result in a transient, expires after 1 day
			// Also store it as the last successful using update_option
			set_transient( $key, $json_output, DAY_IN_SECONDS );
			update_option( $key, $json_output );
		}
		echo '<div class="siteapi-bridge-container">';
		foreach ( $json_output->projects as &$value ) {
			echo '<div class="siteapi-bridge-single">';
			// json_output is an object so use -> to call children
			echo '</div>';
		}
		echo '</div>';
	}
		/**
	 * Send a Push notification on the users browser using the Web Push plugin for WordPress
	 *
	 * CB2_Extras->web_push_notification( 'Title', 'Content', 'http://domain.tld');
	 *
	 * @param string $title   Title.
	 * @param string $content Content.
	 * @param string $url     URL.
	 * @param string $icon    Icon.
	 */
	public function web_push_notification( $title, $content, $url, $icon = '' ) {
		if ( class_exists( 'WebPush_Main' ) ) {
			if ( empty( $icon ) ) {
				$icon_option = get_option( 'webpush_icon' );
				if ( $icon_option === 'blog_icon' ) {
					$icon = get_site_icon_url();
				} elseif ( $icon_option !== 'blog_icon' && $icon_option !== '' && $icon_option !== 'post_icon' ) {
					$icon = $icon_option;
				}
			}
			WebPush_Main::sendNotification( $title, $content, $icon, $url, null );
		}
		return true;
	}
	}
$cb_extras_admin = new CB2_Extras_Admin();
$cb_extras_admin->initialize();
do_action( 'cb2_extras_admin_instance', $cb_extras_admin );
