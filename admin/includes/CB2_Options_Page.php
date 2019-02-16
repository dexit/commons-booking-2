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
	public $visible_tabs = array();
	public $object_type;
	public $cmb_id;
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
		add_action( 'cmb2_save_options-page_fields', array( $this, 'check_settings_valid' ), 10, 1);
		add_action( 'cmb2_save_options-page_fields', array($this, 'set_tab_visibility'), 10, 1);

	}
	/**
	 * Check for validity of settings
	 *
	 * @uses CB2_Settings
	 */
	public function check_settings_valid( ) {

		if ( CB2_Settings::is_enabled( 'features_enable-maps' ) && empty ( CB2_Settings::get( 'maps_api-key') )  ) {
				add_settings_error('cb2_settings-notices', '', __( '<strong>Notice</strong>: You need to provide a valid api key for geocoding to work.', 'commons-booking-2') , 'error');
	}
}
	/**
	 * Enable/disable plugin features tabs
	 *
	 * @TODO: Super ugly.
	 *
	 */
	public function set_tab_visibility( ) {

		foreach ( $this->tabs as $key => $value ) {
			if ( $value['class'] == 'optional') { // optional tab, so check if visible
				if ( ! CB2_Settings::is_enabled( 'features_enable-' . $value['id'] ) ) {
			?>
      <script type='text/javascript'>
        jQuery(document).ready( function(){
           jQuery('#opt-tab-<?php echo $value['id']; ?>').hide();
        });
      </script>
      <?php

				}
			}
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
        	'menu_title' => 'CommonsBooking2',
        ),
    );

    // create the options page

		$this->admin_page = new Cmb2_Metatabs_Options($args);
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
		$boxes[] = CB2_Settings::prepare_settings_metabox('messagetemplates', $args);
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
	public function do_tabs() {

			$this->tabs[] = array(
					'id' => 'features',
					'title' => 'CB2',
					'desc' => '',
					'class' => '',
					'boxes' => array(
							CB2_Settings::$settings_prefix .'_features'
					),
			);
			$this->tabs[] = array(
				'id' => 'general',
				'title' => __('General', 'commons-booking-2'),
				'desc' => '',
				'class' => '',
				'boxes' => array(
						CB2_Settings::$settings_prefix . '_pages'
				),
			);
			$this->tabs[] = array(
				'id' => 'bookings',
				'title' => __( 'Bookings', 'commons-booking-2' ),
				'desc' => '',
				'class' => '',
				'boxes' => array(
					CB2_Settings::$settings_prefix .'_permissions',
					CB2_Settings::$settings_prefix .'_bookingoptions',
				),
			);
			$this->tabs[] = array(
				'id' => 'templates',
				'title' => __('Templates', 'commons-booking-2' ),
				'desc' => '',
				'class' => '',
				'boxes' => array(
					CB2_Settings::$settings_prefix .'_emailtemplates',
					CB2_Settings::$settings_prefix .'_messagetemplates',
				),
			);
			$this->tabs[] = array(
				'id' => 'maps',
				'title' => __( 'Maps', 'commons-booking-2'),
				'desc' => '',
				'class' => 'optional',
				'boxes' => array(
					CB2_Settings::$settings_prefix .'_maps',
				),
			);
			$this->tabs[] = array(
				'id' => 'extrametafields',
				'title' => __( 'Advanced', 'commons-booking-2'),
				'desc' => '',
				'class' => '',
				'boxes' => array(
					CB2_Settings::$settings_prefix .'_extrametafields',
				),
			);
			return apply_filters( 'cb2_settings_tabs', $this->tabs );
		}
	}

new CB2_Options_Page();
