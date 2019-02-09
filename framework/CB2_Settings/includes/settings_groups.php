<?php

// Individual settings groups (=metaboxes) stored in an array.
$cb2_settings_groups =
array(
	/* pages start */
	'pages' => array(
		'title' => 'Plugin pages',
		'id' => CB2_Settings::$settings_prefix . '_pages',
		'desc' => 'Set up the plugin pages.',
		'fields' => array(
			array(
				'name' => __('Booking', 'commons-booking-2'),
				'desc' => __('Shows a booking detail, user bookings list or booking confirmation ', 'commons-booking-2'),
				'id' => 'page-booking',
				'type' => 'select',
				'options' => cb2_form_get_pages()
				)
			)
		),
	/* pages end */
	/* features start */
	'features' => array(
		'title' => 'Plugin features',
		'id' => CB2_Settings::$settings_prefix . '_features',
		'desc' => 'Enable or disable plugin features site-wide. You can configure each feature´s settings after save.',
		'fields' => array(
			array(
				'name' => 'Hack',
				'desc' => 'Hack to fix a cmb2-bug. If a group contains only checkboxes, first one is always set to true. @TODO',
				'id' => 'enable-hack',
				'type' => 'hidden',
				'default' => 'hack',
			),
			array(
				'name' => __('Enable Maps', 'commons-booking-2'),
				'desc' => __('Enable maps and geocoding of adresses.', 'commons-booking-2'),
				'id' => 'enable-maps',
				'type' => 'checkbox',
				'default' => cmb2_set_checkbox_default_for_new_post(false),
			),
			array(
				'name' => __('Enable Codes', 'commons-booking-2'),
				'desc' => __('Enable codes for the booking process.', 'commons-booking-2'),
				'id' => 'enable-codes',
				'type' => 'checkbox',
				'default' => cmb2_set_checkbox_default_for_new_post(false),
			),
			array(
				'name' => __('Enable Holidays', 'commons-booking-2'),
				'desc' => __('Show holidays on the calendar, automatically close locations.', 'commons-booking-2'),
				'id' => 'enable-holidays',
				'type' => 'checkbox',
				'default' => cmb2_set_checkbox_default_for_new_post(false)
			)
		)
	),
	/* features end */
	/* permissions start */
	'permissions' => array(
		'title' => 'Permissions',
		'id' => CB2_Settings::$settings_prefix . '_permissions',
		'desc' => '@TODO: de-selecting all +save  fails',
		'fields' => array(
			array(
				'name' => __('Allow booking for', 'commons-booking-2'),
				'id' => 'user-roles',
				'type' => 'multicheck',
				'options' => cb2_form_get_user_roles(),
				'default' => cb2_form_get_user_roles(true),
				'select_all_button' => true
			),
		)
	),
	/* permissions end */
	/* booking options start */
	'booking_options' => array(
		'title' => 'Usage restrictions',
		'id' => CB2_Settings::$settings_prefix . '_booking_options',
		'desc' => '@todo',
		'fields' => array(
			array(
				'name' => __('Minimum usage time', 'commons-booking-2'),
				'id' => 'min-usage',
				'desc' => __('hh:mm', 'commons-booking-2'),
				'type' => 'text_time',
				'time_format' => 'H:i',
			),
			array(
				'name' => __('Maximum usage time', 'commons-booking-2'),
				'id' => 'max-usage',
				'desc' => __('hh:mm', 'commons-booking-2'),
				'type' => 'text_time',
				'time_format' => 'H:i',
			),
			array(
				'name' => __('Approval needed', 'commons-booking-2'),
				'desc' => __('Bookings need to be approved by an admin', 'commons-booking-2'),
				'id' => 'approval-needed',
				'type' => 'checkbox',
				'default' => cmb2_set_checkbox_default_for_new_post(false)
			)
		)
	),
	/* booking options end */
	/* maps start */
	'maps' => array(
		'title' => 'Maps',
		'id' => CB2_Settings::$settings_prefix . '_maps',
		'desc' => '@todo',
		'fields' => array(
			array(
				'name' => __('API Key', 'commons-booking-2'),
				'desc' => __('Get your API key at https://geocoder.opencagedata.com/users/sign_up, comma-separated', 'commons-booking-2'),
				'id' => 'api-key',
				'type' => 'text',
				'default' => '',
			)
		)
	),
	/* maps end */
	/* test start */
	'test' => array(
		'title' => 'test',
		'id' => 'test',
		'fields' => array(),
	),
	/* test end */
	/* extra_meta_fields start */
	'extra_meta_fields' => array(
		'title' => 'Extra meta fields',
		'id' => CB2_Settings::$settings_prefix . '_extra_meta_fields',
		'desc' => 'If you set up additional meta fields for item, location, user or booking (for example: use another plugin to add registration fields).<br> Enter the field names here to make them available for use as template tags: {{mytemplatetag}}.',
		'fields' => array(
			array(
				'name' => __('Item meta fields', 'commons-booking-2'),
				'desc' => __('Comma separated, e.g.: fieldname_1,fieldname_2,fieldname_3', 'commons-booking-2'),
				'id' => 'item',
				'type' => 'text',
				'default' => '',
			),
			array(
				'name' => __('Location meta fields', 'commons-booking-2'),
				'desc' => __('Comma separated, e.g.: fieldname_1,fieldname_2,fieldname_3', 'commons-booking-2'),
				'id' => 'location',
				'type' => 'text',
				'default' => '',
			),
			array(
				'name' => __('Booking meta fields', 'commons-booking-2'),
				'desc' => __('Comma separated, e.g.: fieldname_1,fieldname_2,fieldname_3', 'commons-booking-2'),
				'id' => 'booking',
				'type' => 'text',
				'default' => '',
			),
			array(
				'name' => __('User meta fields', 'commons-booking-2'),
				'desc' => __('Comma separated, e.g.: fieldname_1,fieldname_2,fieldname_3', 'commons-booking-2'),
				'id' => 'user',
				'type' => 'text',
				'default' => '',
			),
		)
	),
	/* extra_meta_fields end */
	/* email templates start */
	'email_templates' => array(
		'title' => __('Email templates', 'commons-booking-2'),
		'id' => CB2_Settings::$settings_prefix . '_email_templates',
		'desc' => 'Email templates. You can use html & {{template_tags}}',
		'fields' => array(
			array(
				'name' => __('Booking pending email subject', 'commons-booking-2'),
				'desc' => __('', 'commons-booking-2'),
				'id' => 'mail_booking_pending_subject',
				'type' => 'text',
				'default' => '',
			),
			array(
				'name' => __('Booking pending email body', 'commons-booking-2'),
				'desc' => __('', 'commons-booking-2'),
				'id' => 'mail_booking_pending_body',
				'type' => 'textarea',
				'default' => '',
			),
			array(
				'name' => __('Booking approved email subject', 'commons-booking-2'),
				'desc' => __('', 'commons-booking-2'),
				'id' => 'mail_booking_approved_subject',
				'type' => 'text',
				'default' => '',
			),
			array(
				'name' => __('Booking approved email body', 'commons-booking-2'),
				'desc' => __('', 'commons-booking-2'),
				'id' => 'mail_booking_approved_body',
				'type' => 'textarea',
				'default' => '',
			),
			array(
				'name' => __('Booking canceled email subject', 'commons-booking-2'),
				'desc' => __('', 'commons-booking-2'),
				'id' => 'mail_booking_canceled_subject',
				'type' => 'text',
				'default' => '',
			),
			array(
				'name' => __('Booking canceled email body', 'commons-booking-2'),
				'desc' => __('', 'commons-booking-2'),
				'id' => 'mail_booking_canceled_body',
				'type' => 'textarea',
				'default' => '',
			),
		)
	),
	/* email templates end */
);
