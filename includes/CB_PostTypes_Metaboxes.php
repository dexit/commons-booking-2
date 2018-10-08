<?php
/**
 * Custom meta boxes for items & locations
 * @TODO: item metaboxes
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
/**
 * This class contain the Post Types and Taxonomy initialize code
 */
class CB_PostTypes_Metaboxes {

	public $prefix;
	public $slug;
	/**
	 * Initialize
	 *
	 * @since 2.0.0
	 *
	 *
	 */
	function __construct() {

		$this->slug = CB2_TEXTDOMAIN;
		$this->prefix = $prefix = '_' . $this->slug . '_';

		add_action( 'add_meta_boxes', array( $this, 'add_item_timeframe_metabox' ) );
		add_action( 'cmb2_admin_init', array( $this, 'add_cb_posttype_excerpt_metaboxes' ) );
		add_action( 'cmb2_admin_init', array( $this, 'add_location_metaboxes' ) );
	}
	/**
	 * Register item timeframe meta box
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function add_item_timeframe_metabox() {
		/**
		 * Metabox: We are using WP internal metabox instead of cmb2 here, since we just render the timeframes and do not save any stuff
		 */
		add_meta_box( 'timeframe_meta_box', __( 'Timeframes', 'commons-booking' ), array( $this, 'item_timeframe_metabox_wrapper'), 'cb_item' );
	}
	/**
	 * Register meta boxes for both cb_items & cb_locations
	 *
	 * @since 2.0.0
	 * @uses CMB2
	 *
	 * @return void
	 */
	public function add_cb_posttype_excerpt_metaboxes() {
		/**
		 * Metabox: Item/location short list info
		 */
		$cmb = new_cmb2_box( array(
			'id'            => 'cb-post-excerpt-metabox',
			'title'         => __( 'Excerpt', 'commons-booking' ),
			'object_types'  => array( 'cb_item', 'cb_location' ), // Post type
			'context'       => 'normal',
			'priority'      => 'low',
			'show_names'    => false, // Show field names on the left
			'cmb_styles' => false, // false to disable the CMB stylesheet
			'fields'				=> array (
				array(
					'name'    => 'Description',
					'desc'    => 'Short  description that will show up in lists.',
					'id'      => 'cb-post-excerpt',
					'type'    => 'textarea',
					'options' => array(),
					)
				)
			)
		);
	}
	/**
	 * Wrapper for CB_Gui in timeframe meta box
	 *
	 * @since 2.0.0
	 */
	public function item_timeframe_metabox_wrapper( ) {

		global $post;
		echo CB_Gui::col_format_timeframe( $post->ID );
	}

	/**
	 * Load CPT and Taxonomies on WordPress
	 *
	 * @since 2.0.0
	 * @uses CMB2
	 * @uses CB_Settings
	 *
	 * @return void
	 */
	public function add_location_metaboxes() {

		/**
		 * Metabox: Pickup mode - defined in Settings module
		 */
		$fields_location_pickup_mode = CB_Settings::get_settings_group( 'location-pickup-mode');

		CB_Settings::cb2_add_settings_to_cpt( 'location_pickup_mode', __('Location pickup mode', 'commons-booking'), array('cb_location'), $fields_location_pickup_mode );

		/**
		 * Metabox: Personal pickup contact info - defined in Settings module
		 */
		$fields_location_personal_contact_info = CB_Settings::get_settings_group( 'location-personal-contact-info');

		CB_Settings::cb2_add_settings_to_cpt('location_personal_contact_info', __('Location personal contact info', 'commons-booking'), array('cb_location'), $fields_location_personal_contact_info);

		/**
		 * Metabox: Opening times - defined in Settings module
		 */
		$fields_location_opening_times = CB_Settings::get_settings_group( 'location-opening-times');

		CB_Settings::cb2_add_settings_to_cpt('location_opening_times', __('Opening times', 'commons-booking'), array('cb_location'), $fields_location_opening_times);

		/**
		 * Metabox: location address - defined in Settings module
		 */

		$fields_location_address = CB_Settings::get_settings_group( 'location-address');

		CB_Settings::cb2_add_settings_to_cpt('location_address', __('Location Address', 'commons-booking'), array('cb_location'), $fields_location_address);

		}
}
new CB_PostTypes_Metaboxes();
