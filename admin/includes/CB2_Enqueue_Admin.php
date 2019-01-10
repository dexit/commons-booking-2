<?php
/**
 * Administration Enqueue
 *
 * Admin-related scripts, styles
 * WP Backend settings menu, plugin screen action menu
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
/**
 * This class contains the Enqueues for the backend
 */
class CB2_Enqueue_Admin {
		/**
	 * Slug of the plugin screen.
	 *
	 * @var string
	 */
	protected $admin_view_page = null;
		/**
	 * Initialize the class
	 */
	public function initialize() {
		if ( !apply_filters( 'commons_booking_cb_enqueue_admin_initialize', true ) ) {
			return;
		}

		$plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . CB2_TEXTDOMAIN . '.php' );

		// Add the manage menu & options page entry
		add_action( 'admin_menu', array( $this, 'add_plugin_settings_menu') );
		// Add an action link pointing to the options page.
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );
		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		// @TODO not working
		add_filter( 'cmb2_sanitize_toggle', array( $this, 'cmb2_sanitize_checkbox' ), 20, 2 );
	}

		/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since 2.0.0
	 *
	 * @return mixed Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {
		if ( !isset( $this->admin_view_page ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( $this->admin_view_page === $screen->id || strpos( $_SERVER[ 'REQUEST_URI' ], 'index.php' ) || strpos( $_SERVER[ 'REQUEST_URI' ], get_bloginfo( 'wpurl' ) . '/wp-admin/' ) ) {
			wp_enqueue_style( CB2_TEXTDOMAIN . '-settings-styles', plugins_url( 'admin/assets/css/settings.css', CB2_PLUGIN_ABSOLUTE ), array( 'dashicons' ), CB2_VERSION );
		}
		wp_enqueue_style( CB2_TEXTDOMAIN . '-admin-styles', plugins_url( 'admin/assets/css/admin.css', CB2_PLUGIN_ABSOLUTE ), array( 'dashicons' ), CB2_VERSION );
	}
		/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since 2.0.0
	 *
	 * @return mixed Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {
		if ( !isset( $this->admin_view_page ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->admin_view_page === $screen->id ) {
			new WP_Admin_Notice('Saved', 'error');

			wp_enqueue_script( CB2_TEXTDOMAIN . '-settings-script', plugins_url( 'admin/assets/js/settings.js', CB2_PLUGIN_ABSOLUTE ), array( 'jquery', 'jquery-ui-tabs' ), CB2_VERSION );
		}
		wp_enqueue_script( CB2_TEXTDOMAIN . '-admin-script', plugins_url( 'admin/assets/js/admin.js', CB2_PLUGIN_ABSOLUTE ), array( 'jquery' ), CB2_VERSION );
	}
	/**
	 * Register the plugin settings menu.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function add_plugin_settings_menu() {

		/** Settings menu
		 *  @TODO: Temporarily a main menu item,
		 *  possible conflict with WP_Admin_Integration?
		 */
		// $this->admin_view_page = add_submenu_page( CB2_MENU_SLUG, __( 'Settings', CB2_TEXTDOMAIN ), __( 'Settings', CB2_TEXTDOMAIN ), 'manage_options', 'cb_settings_page', array( $this, 'display_plugin_admin_page' ) );
		$this->admin_view_page = add_menu_page( __('CommonsBooking 2 Settings', 'commons-booking-2'), __('Settings', 'commons-booking-2'), 'manage_options', 'cb2_settings_page', array($this, 'display_plugin_admin_page'));
	}
	/**
	 * Render the settings page for this plugin.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function display_plugin_admin_page() {

		 echo CB2_Settings::render_settings_screen();
	}
	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since 2.0.0
	 *
	 * @param array $links Array of links.
	 *
	 * @return array
	 */
	public function add_action_links( $links ) {
		return array_merge(
				array(
			'settings' => '<a href="' . admin_url( 'options-general.php?page=' . CB2_TEXTDOMAIN ) . '">' . __( 'Settings' ) . '</a>',
				), $links
		);
	}
/**
 * Fixed checkbox issue with default is true.
 *
 * @param  mixed $override_value Sanitization/Validation override value to return.
 * @param  mixed $value          The value to be saved to this field.
 * @return mixed
 */
function cmb2_sanitize_checkbox( $override_value, $value ) {
    // Return 0 instead of false if null value given. This hack for
		// checkbox or checkbox-like can be setting true as default value.
    return is_null( $value ) ? 0 : $value;
	}
}
$cb_enqueue_admin = new CB2_Enqueue_Admin();
$cb_enqueue_admin->initialize();
do_action( 'cb2_enqueue_admin_instance', $cb_enqueue_admin );
