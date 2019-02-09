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
		// use CMO filter to add an intro at the top of the options page
		add_filter('cmb2metatabs_before_form', array($this, 'intro_text'));

		add_action('cmb2_save_options-page_fields', array( $this, 'reply') , 10, 2);

	}
public function reply( $id, $args ) {

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
	public function do_boxes(  )
	{
		$this->prepare_settings_metabox('features');
		$this->prepare_settings_metabox('pages');
		$this->prepare_settings_metabox('maps');
		$this->prepare_settings_metabox('permissions');
		$this->prepare_settings_metabox('booking_options');
		$this->prepare_settings_metabox('email_templates');
		$this->prepare_settings_metabox('extra_meta_fields');

		return apply_filters( 'cb2_settings_boxes', $this->boxes );
	}
	/**
	 *  Create the box array from a settings group and add to $boxes
	 *
	 * This is typical CMB2, but note two crucial extra items:
	 *
	 * - the ['show_on'] property is configured
	 * - a call to object_type method
	 *
	 * @uses CB2_Settings
	 */
	public function prepare_settings_metabox( $settings_group_id, $context='options-page', $args=array() ) {

		// we will be adding this to all boxes
		$defaults = array(
				'show_on' => array(
    		'key' => 'options-page',
    		'value' => array( CB2_Settings::$settings_prefix ),
				),
			'display_cb' => false,
			'admin_menu_hook' => false,
			'closed' => false,
		);

		$group = CB2_Settings::get_settings_group( $settings_group_id );
		$group_metabox = array_replace( $defaults, $group);

		$group_metabox = $this->prepend_description_as_row( $group_metabox );

		$cmb = new_cmb2_box( $group_metabox );
		$cmb->object_type('options-page'); // critical, see wiki for why
		$this->boxes[] = $cmb;
	}
	/**
	 * If array contains a description, create a new row contaning this text
	 *
	 * @param array $metabox_array
	 * @return array $metabox_array with the description added as row
	 */
	public function prepend_description_as_row( $metabox_array ) {

		if (array_key_exists('description', $metabox_array)) { // attach settings group description as new "title" field row

			$dummy_field = array(
					'id' => $metabox_array['id'] . '_description',
					'desc' => $metabox_array['description'],
					'type' => 'title',
			);

			array_unshift($metabox_array['fields'], $dummy_field);
		}
		return $metabox_array;
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
							$this->options_key .'_features',
					),
			);
			$this->tabs[] = array(
					'id' => 'bookings',
					'title' => __( 'Bookings', 'commons-booking-2' ),
					'desc' => '',
					'boxes' => array(
							$this->options_key .'_booking_options',
							$this->options_key .'_permissions',
					),
			);
			$this->tabs[] = array(
					'id' => 'general',
					'title' => __( 'General', 'commons-booking-2' ),
					'desc' => '',
					'boxes' => array(
							$this->options_key .'_pages',
					),
			);
			$this->tabs[] = array(
					'id' => 'templates',
					'title' => __('Templates', 'commons-booking-2' ),
					'desc' => '',
					'boxes' => array(
							$this->options_key .'_email_templates',
					),
			);
			if ( ! CB2_Settings::is_enabled( 'features', 'maps' ) ) {
				$this->tabs[] = array(
						'id' => 'maps',
						'title' => __( 'Maps', 'commons-booking-2'),
						'desc' => '',
						'boxes' => array(
								$this->options_key .'_maps',
						),
				);
			}
			return apply_filters( 'cb2_settings_tabs', $this->tabs );
		}
	}

new CB2_Options_Page();
