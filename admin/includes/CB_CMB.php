<?php
/**
 * Custom meta boxes for items & locations
 * @TODO not in use right now, maybe depreciate
 * meta boxes are defined in CB_PostTypes_Metaboxes
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
/**
 * All the CMB related code.
 */
class CB_CMB {
	/**
	 * Initialize CMB2.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		require_once( 'lib/cmb2/init.php' );
		require_once( 'lib/cmb2-grid/Cmb2GridPluginLoad.php' );
		require_once( 'lib/cmb2-tabs/cmb2-tabs.php' );
		add_action( 'cmb2_init', array( $this, 'cmb_demo_metaboxes' ) );
	}
	/**
	 * NOTE:     Your metabox on Demo CPT
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function cmb_demo_metaboxes() {
		// Start with an underscore to hide fields from custom fields list
		$prefix = '_demo_';
		$cmb_demo = new_cmb2_box( array(
			'id' => $prefix . 'metabox',
			'title' => __( 'Demo Metabox', CB2_TEXTDOMAIN ),
			'object_types' => array( 'demo', ), // Post type
			'context' => 'normal',
			'priority' => 'high',
			'show_names' => true, // Show field names on the left
						'vertical_tabs' => true, // Set vertical tabs, default false
			'tabs' => array(
				array(
					'id' => 'tab-1',
					'icon' => 'dashicons-admin-site',
					'title' => 'Tab 1',
					'fields' => array(
						$prefix . CB2_TEXTDOMAIN . '_text',
						$prefix . CB2_TEXTDOMAIN . '_text2'
					),
				),
				array(
					'id' => 'tab-2',
					'icon' => 'dashicons-align-left',
					'title' => 'Tab 2',
					'fields' => array(
						$prefix . CB2_TEXTDOMAIN . '_textsmall',
						$prefix . CB2_TEXTDOMAIN . '_textsmall2'
					),
				),
			)
						) );
		$cmb2Grid = new \Cmb2Grid\Grid\Cmb2Grid( $cmb_demo );
		$row = $cmb2Grid->addRow();
				$field1 = $cmb_demo->add_field( array(
			'name' => __( 'Text', CB2_TEXTDOMAIN ),
			'desc' => __( 'field description (optional)', CB2_TEXTDOMAIN ),
			'id' => $prefix . CB2_TEXTDOMAIN . '_text',
			'type' => 'text'
				) );
		$field2 = $cmb_demo->add_field( array(
			'name' => __( 'Text 2', CB2_TEXTDOMAIN ),
			'desc' => __( 'field description (optional)', CB2_TEXTDOMAIN ),
			'id' => $prefix . CB2_TEXTDOMAIN . '_text2',
			'type' => 'text'
				) );
		$field3 = $cmb_demo->add_field( array(
			'name' => __( 'Text Small', CB2_TEXTDOMAIN ),
			'desc' => __( 'field description (optional)', CB2_TEXTDOMAIN ),
			'id' => $prefix . CB2_TEXTDOMAIN . '_textsmall',
			'type' => 'text_small'
				) );
		$field4 = $cmb_demo->add_field( array(
			'name' => __( 'Text Small 2', CB2_TEXTDOMAIN ),
			'desc' => __( 'field description (optional)', CB2_TEXTDOMAIN ),
			'id' => $prefix . CB2_TEXTDOMAIN . '_textsmall2',
			'type' => 'text_small'
				) );
				$row->addColumns( array( $field1, $field2 ) );
		$row = $cmb2Grid->addRow();
		$row->addColumns( array( $field3, $field4 ) );
			}
}
new CB_CMB();
