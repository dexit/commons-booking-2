<?php
/**
 * Options Page Settings
 *
 * This file creates an options page located in under the WordPress "Settings" menu.
 *
 *
 * More information is available at this CMB2 Metatabs Option's wiki:
 * https://github.com/rogerlos/cmb2-metatabs-options/wiki
 *
 * @since 1.0.1 Revised comments
 * @since 1.0.0
 */
Class CB2_Options_Page {

	public $defaults = array ();
	public $options_key;
	public $boxes = array();
	public $tabs = array();
	/**
	 * Constructor
	 *
	 * @uses CB2_Settings
	 */
	public function __construct() {

		$this->options_key = CB2_Settings::$settings_prefix;

		// add action to hook option page creation to
		add_action('cmb2_admin_init', array( $this, 'create_meta_boxes') );
		// use filter to add an intro at the top of the options page
		add_filter('cmb2metatabs_before_form', array($this, 'intro_text'));

		// add_action('cmb2_save_options-page_fields', array( $this, 'reply') , 10, 2);
		add_action( 'cmb2_admin_init', array( $this, 'check_settings_valid'));


	}
	/**
	 * Check for validity of settings
	 *
	 * @TODO fires too late, admin notice is only shown after refresh.
	 */
	public function check_settings_valid( ) {

		// feature maps is enabled and api key NOT set
		if ( CB2_Settings::is_enabled( 'features_enable-maps' ) && empty ( CB2_Settings::get( 'maps_api-key' ) ) ) {
			new WP_Admin_Notice(__( '<strong>Notice</strong>: You need to provide a valid api key for geocoding to work.', 'commons-booking-2') , 'error');
	}

}
/**
 * Callback for 'cmb2_admin_init'.
 *
 * In this example, 'boxes' and 'tabs' call functions simply to separate "normal" CMB2 configuration
 * from unique CMO configuration.
 */
public function create_meta_boxes() {

    // configuration array
    $args = array(
        'key' => $this->options_key,
        'title' => 'CommonsBooking 2 Settings',
        'topmenu' => 'options-general.php',
        'cols' => 1,
        'boxes' => $this->do_boxes( ),
        'tabs' => $this->do_tabs(),
        'menuargs' => array(
        	'menu_title' => 'CB2',
        ),
    );

    // create the options page
    new Cmb2_Metatabs_Options($args);
}

/**
 * Callback for CMO filter.
 *
 * The two filters in CMO do not send any content; simply return your HTML.
 *
 * @return string
 */
	function intro_text()
	{
			return '<p>Welcome to cb2</p>';
	}

	/**
	 * Add boxes
	 *
	 *
	 */
	public function do_boxes( ){

		$args = array(
			'show_on' => array(
					'key' => 'options-page',
					'value' => array( CB2_Settings::$settings_prefix ),
			),
			'display_cb' => false,
			'admin_menu_hook' => false,
			'closed' => false,
		);

		$boxes[] = CB2_Settings::prepare_settings_metabox('features', $args);
		$boxes[] = CB2_Settings::prepare_settings_metabox('pages', $args);
		$boxes[] = CB2_Settings::prepare_settings_metabox('maps', $args);
		$boxes[] = CB2_Settings::prepare_settings_metabox('permissions', $args);
		$boxes[] = CB2_Settings::prepare_settings_metabox('bookingoptions', $args);
		$boxes[] = CB2_Settings::prepare_settings_metabox('emailtemplates', $args);
		$boxes[] = CB2_Settings::prepare_settings_metabox('extrametafields', $args);

		foreach ( $boxes as $box ) { // set the object type is necessary for options pages only
			$box->object_type( 'options-page' );
		}
		return apply_filters( 'cb2_settings_boxes', $boxes );
	}


	/**
	 * Add tabs
	 *
	 * Tabs are completely optional and removing them would result in the option metaboxes displaying sequentially.
	 * If you do configure tabs, all boxes whose context is "normal" or "advanced" must be in a tab to display.
	 *
	 * @return array
	 */
	public function do_tabs()
	{

			$this->tabs[] = array(
					'id' => 'start',
					'title' => 'CB2',

					'desc' => '',
					'boxes' => array(
						CB2_Settings::$settings_prefix .'_features',
					),
			);
			$this->tabs[] = array(
				'id' => 'general',
				'title' => __('General', 'commons-booking-2'),
				'desc' => '',
				'boxes' => array(
						CB2_Settings::$settings_prefix . '_pages',
				),
			);
			$this->tabs[] = array(
				'id' => 'bookings',
				'title' => __( 'Bookings', 'commons-booking-2' ),
				'desc' => '',
				'boxes' => array(
				CB2_Settings::$settings_prefix .'_permissions',
				CB2_Settings::$settings_prefix .'_bookingoptions',
				),
			);
			$this->tabs[] = array(
				'id' => 'templates',
				'title' => __('Templates', 'commons-booking-2' ),
				'desc' => '',
				'boxes' => array(
					CB2_Settings::$settings_prefix .'_emailtemplates',
				),
			);
			if ( CB2_Settings::is_enabled( 'features_enable-maps' ) ) {
				$this->tabs[] = array(
					'id' => 'maps',
					'title' => __( 'Maps', 'commons-booking-2'),
					'desc' => '',
					'boxes' => array(
						CB2_Settings::$settings_prefix .'_maps',
					),
				);
			}
			return apply_filters( 'cb2_settings_tabs', $this->tabs );
		}
	}

new CB2_Options_Page();
