<?php

$cb2_settings_metaboxes =
	array(
		/* pages start */
		'pages' => array(
			'title' => 'Plugin pages',
			'id' => CB2_Settings::$settings_prefix . 'pages',
			'description' => 'Set up the plugin pages.',
			'fields' => array(
					array(
						'name' => __('Booking Finalize', 'commons-booking-2'),
						'desc' => __('Shows booking details, asks for user confirmation', 'commons-booking-2'),
						'id' => 'page-booking-finalize',
						'type' => 'select',
						'options' => cb2_form_get_pages()
						),
					array(
						'name' => __('Booking confirmation', 'commons-booking-2'),
						'desc' => __('Displays successful booking message, booking details and codes (if enabled).', 'commons-booking-2'),
						'id' => 'page-booking-confirmed',
						'type' => 'select',
						'options' => cb2_form_get_pages()
						)
				)
		),
		/* pages end */
);
