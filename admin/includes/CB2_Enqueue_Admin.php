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
		if ( !apply_filters( 'cb2_enqueue_admin_instance', true ) ) {
			return;
		}

		$plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . CB2_TEXTDOMAIN . '.php' );

		// Load general admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// setting default value for checkbox (https: //github.com/CMB2/CMB2/wiki/Tips-&-Tricks#setting-a-default-value-for-a-checkbox)
		add_filter('cmb2_sanitize_toggle', 'cmb2_sanitize_checkbox', 20, 2);

		add_action('cmb2_admin_init', array($this, 'add_metabox_to_pages'));


	}
	public function add_metabox_to_pages() {

		// @TODO: add metabox of settings to pages.
		// $args = array(
    // 	'object_types' => array('item')
		// );

		// $box = CB2_Settings::prepare_settings_metabox('features', $args, 'cb2_settings_');

	}


		/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since 2.0.0
	 */
	public function enqueue_admin_styles() {

		wp_enqueue_style(CB2_TEXTDOMAIN . '-framework-styles', plugins_url('framework/assets/css/framework.css', CB2_PLUGIN_ABSOLUTE), array(), CB2_VERSION);

		wp_enqueue_style( CB2_TEXTDOMAIN . '-admin-styles', plugins_url( 'admin/assets/css/admin.css', CB2_PLUGIN_ABSOLUTE ), array( 'dashicons' ), CB2_VERSION );
		geo_hcard_map_load_styles();
	}


		/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since 2.0.0
	 *
	 * @return mixed Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {
		/*
		if ( !isset( $this->admin_view_page ) ) {
			return;
		}


		$screen = get_current_screen();
		if ( $this->admin_view_page === $screen->id ) {
		}
		*/
		wp_enqueue_script( CB2_TEXTDOMAIN . '-admin-forms-script', plugins_url( 'admin/assets/js/admin_forms.js', CB2_PLUGIN_ABSOLUTE ), array( 'jquery' ), CB2_VERSION );
		geo_hcard_map_load_scripts();
	}



}
$cb_enqueue_admin = new CB2_Enqueue_Admin();
$cb_enqueue_admin->initialize();
do_action( 'cb2_enqueue_admin_instance', $cb_enqueue_admin );


