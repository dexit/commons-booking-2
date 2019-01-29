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

		// Load general admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_filter('cmb2_sanitize_toggle', 'sanitize_checkbox', 20, 2);

		/*
		* Admin Screens
		*/
		add_action('admin_menu', array( $this, 'plugin_settings_page_menu')); // Settings menu

	}
	public function plugin_settings_page_menu() {

		add_options_page(
				__('CB2 Settings', 'commons-booking-2'),
				'CB2 Settings',
				'manage_options',
				'cb2_settings',
				array($this, 'plugin_settings_page'),
				'',
				6,
				''
		);
	}
	public function plugin_settings_page() {

		$plugin_settings_page = new CB2_Admin_Tabs('cb2_settings'); // page contents

		$plugin_settings_page->add_tab(
				'mytab',
				'my Tab',
				CB2_Settings::render_settings_group( array('features') )
		);
		$plugin_settings_page->add_tab(
				'maps',
				'Maps',
				CB2_Settings::render_settings_group( array('maps') ),
				CB2_Settings::is_enabled('features', 'enable-maps')
		);
		$plugin_settings_page->render_content();
	}
	public function plugin_settings_page_saved() {
		echo ("<h1>hello</h1>");

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
		/* @TODO: retire */
		wp_enqueue_script( CB2_TEXTDOMAIN . '-admin-script', plugins_url( 'admin/assets/js/admin.js', CB2_PLUGIN_ABSOLUTE ), array( 'jquery' ), CB2_VERSION );
		}
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




}
$cb_enqueue_admin = new CB2_Enqueue_Admin();
$cb_enqueue_admin->initialize();
do_action( 'cb2_enqueue_admin_instance', $cb_enqueue_admin );
