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

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'cmb2_init', array( $this, 'admin_screens' ) );
		// @TODO not working
		add_filter( 'cmb2_sanitize_toggle', 'cmb2_sanitize_checkbox', 20, 2 );

		/*
		* Admin Screens
		*/
		$settings_screen = new CB2_Admin_Screen();
		$settings_screen->add_menu_item(
				array(
						'page_title' => __('CB2 Settings', 'commons-booking-2'),
						'menu_title' => 'CB2 Settings',
				)
		);
		$settings_screen->add_script(
				array(
						'cb2_tabs_script',
						plugins_url('admin/assets/js/admin_tabs.js', CB2_PLUGIN_ABSOLUTE),
						array('jquery', 'jquery-ui-tabs'),
				)
		);
		$settings_screen->add_style(array(
				'cb2_tabs_style',
				plugins_url('admin/assets/css/admin_tabs.css', CB2_PLUGIN_ABSOLUTE),
		)
		);
		$settings_screen->add_tabbed_content(
				CB2_PLUGIN_ROOT . 'admin/views/settings_welcome.php', 'cb2',
				__('Welcome', 'commons-booking-2'), true
		);
		$settings_screen->add_tabbed_content(
				CB2_PLUGIN_ROOT . 'admin/views/settings_general.php', 'general',
				__('General', 'commons-booking-2'), true
		);
		$settings_screen->add_tabbed_content(
				CB2_PLUGIN_ROOT . 'admin/views/settings_maps.php', 'maps',
				__('Maps', 'commons-booking-2'), CB2_Settings::is_enabled('features', 'enable-maps')
		);
		$settings_screen->add_tabbed_content(
				CB2_PLUGIN_ROOT . 'admin/views/settings_codes.php', 'codes',
				__('Codes', 'commons-booking-2'), CB2_Settings::is_enabled('features', 'enable-codes')
		);
		$settings_screen->add_tabbed_content(
				CB2_PLUGIN_ROOT . 'admin/views/settings_holidays.php', 'holidays',
				__('Codes', 'commons-booking-2'), CB2_Settings::is_enabled('features', 'enable-holidays')
		);
		$settings_screen->add_tabbed_content(
				CB2_PLUGIN_ROOT . 'admin/views/settings_strings.php', 'strings',
				__('Strings', 'commons-booking-2'), true
		);


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
