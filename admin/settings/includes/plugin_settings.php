<?php
/**
 * Commons_Booking Settings
 * All available settings (plugin, post type) are stored here
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */

$cb_settings_array = array (

	'bookings' => array (
		'general' => array (
			array (
				'name'             => __( 'Maximum slots', 'commons-booking' ),
				'desc'             => __( 'Maximum slots a user is allowed to book at once', 'commons-booking' ),
				'id'               => 'max-slots',
				'type'             => 'text_small',
				'default'          => 3
			),
			array(
				'name'             => __( 'Consecutive slots', 'commons-booking' ),
				'desc'             => __( 'Slots must be consecutive', 'commons-booking' ),
				'id'               => 'consecutive-slots',
				'type'             => 'checkbox',
				'default' 				=> cmb2_set_checkbox_default_for_new_post( true )
			),
			array(
				'name'             => __( 'Use booking codes', 'commons-booking' ),
				'desc'             => __( 'Create codes for every slot', 'commons-booking' ),
				'id'               => 'use-codes',
				'type'             => 'checkbox',
				'default' 				=> cmb2_set_checkbox_default_for_new_post( true )
			),
		)
		), // end bookings

		'pages' => array(
			'posttypes' => array (
				array(
				'before_row'       => __('Pages: Items and calendar', 'commons-booking' ), // Headline
				'name'             => __( 'Items page', 'commons-booking' ),
				'desc'             => __( 'Display list of items on this page', 'commons-booking' ),
				'id'               => 'item-page-id',
				'type'             => 'select',
				'show_option_none' => true,
				'default'          => 'none',
				'options'          => cb_get_pages_dropdown(),
				),
				array(
				'name'             => __( 'Locations page', 'commons-booking' ),
				'desc'             => __( 'Display list of Locations on this page', 'commons-booking' ),
				'id'               => 'location-page-id',
				'type'             => 'select',
				'show_option_none' => true,
				'default'          => 'none',
				'options'          => cb_get_pages_dropdown(),
				),
				array(
				'name'             => __( 'Calendar page', 'commons-booking' ),
				'desc'             => __( 'Display the calendar on this page', 'commons-booking' ),
				'id'               => 'calendar-page-id',
				'type'             => 'select',
				'show_option_none' => true,
				'default'          => 'none',
				'options'          => cb_get_pages_dropdown(),
				)
			),
			'bookings' => array(
				array(
				'name'             => __( 'Booking review page', 'commons-booking' ),
				'desc'             => __( 'Shows the pending booking, prompts for confimation.', 'commons-booking' ),
				'id'               => 'booking-review-page-id',
				'type'             => 'select',
				'show_option_none' => true,
				'default'          => 'none',
				'options'          => cb_get_pages_dropdown(),
				),
				array(
				'name'             => __( 'Booking confirmed page', 'commons-booking' ),
				'desc'             => __( 'Displayed when the user has confirmed a booking.', 'commons-booking' ),
				'id'               => 'booking-confirmed-page-id',
				'type'             => 'select',
				'show_option_none' => true,
				'default'          => 'none',
				'options'          => cb_get_pages_dropdown(),
				),
				array(
				'name'             => __( 'Booking page', 'commons-booking' ),
				'desc'             => __( '', 'commons-booking' ),
				'id'               => 'booking-page-id',
				'type'             => 'select',
				'show_option_none' => true,
				'default'          => 'none',
				'options'          => cb_get_pages_dropdown(),
				),
				array(
				'name'             => __( 'My bookings page', 'commons-booking' ),
				'desc'             => __( 'Shows the user´s bookings.', 'commons-booking' ),
				'id'               => 'user-bookings-page-id',
				'type'             => 'select',
				'show_option_none' => true,
				'default'          => 'none',
				'options'          => cb_get_pages_dropdown(),
				)
			)
		), // end pages

		// 'address' => array (
		// 	'address' => array (
		// 			array (
		// 			'name' => __( 'Address (1)', 'commons-booking'),
		// 			'id' => 'address-1',
		// 			'type' => 'text',
		// 			),
		// 			array(
		// 			'name' => __( 'Address (2)', 'commons-booking'),
		// 			'id' => 'address-2',
		// 			'type' => 'text',
		// 			),
		// 			array(
		// 			'name' => __( 'Address (3)', 'commons-booking'),
		// 			'id' => 'address-3',
		// 			'type' => 'text',
		// 			),
		// 			array(
		// 			'name' => __( 'City', 'commons-booking'),
		// 			'id' => 'city',
		// 			'type' => 'text',
		// 			),
		// 			array(
		// 			'name' => __( 'Region', 'commons-booking'),
		// 			'id' => 'region',
		// 			'type' => 'text',
		// 			),
		// 			array(
		// 			'name' => __( 'Zip Code', 'commons-booking'),
		// 			'id' => 'zip',
		// 			'type' => 'text',
		// 			),
		// 			array(
		// 			'name' => __( 'Country', 'commons-booking'),
		// 			'id' => 'country',
		// 			'type' => 'text',
		// 			),
		//	)
		// ) // end address
);
