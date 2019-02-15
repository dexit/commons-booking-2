<?php

// Individual settings groups (=metaboxes) stored in an array.
$cb2_settings_metaboxes =
array(
	/* pages start */
	'pages' => array(
		'title' => 'Plugin pages',
		'id' => 'pages',
		'desc' => 'Set up the plugin pages.',
		'fields' => array(
			array(
				'name' => __('Booking', 'commons-booking-2'),
				'desc' => __('Shows a booking detail, user bookings list or booking confirmation ', 'commons-booking-2'),
				'id' => 'pages_page-booking',
				'type' => 'select',
				'options' => cb2_form_get_pages()
				)
			)
		),
	/* pages end */
	/* features start */
	'features' => array(
		'title' => 'Plugin features',
		'id' => 'features',
		'desc' => 'Enable or disable plugin features site-wide. You can configure each feature´s settings after save.',
		'fields' => array(
			array(
				'name' => 'Hack',
				'desc' => 'Hack to fix a cmb2-bug. If a group contains only checkboxes, first one is always set to true. @TODO',
				'id' => 'features_enable-hack',
				'type' => 'hidden',
				'save_field' => false,
			),
			array(
				'name' => __('Enable Maps', 'commons-booking-2'),
				'desc' => __('Enable maps and geocoding of adresses.', 'commons-booking-2'),
				'id' => 'features_enable-maps',
				'type' => 'checkbox',
				'default' => cmb2_set_checkbox_default_for_new_post(false),
			),
			array(
				'name' => __('Enable Codes', 'commons-booking-2'),
				'desc' => __('Enable codes for the booking process.', 'commons-booking-2'),
				'id' => 'features_enable-codes',
				'type' => 'checkbox',
				'default' => cmb2_set_checkbox_default_for_new_post(false),
			),
			array(
				'name' => __('Enable Holidays', 'commons-booking-2'),
				'desc' => __('Show holidays on the calendar, automatically close locations.', 'commons-booking-2'),
				'id' => 'features_enable-holidays',
				'type' => 'checkbox',
				'default' => cmb2_set_checkbox_default_for_new_post(false)
			)
		)
	),
	/* features end */
	/* permissions start */
	'permissions' => array(
		'title' => 'Permissions',
		'id' => 'permissions',
		'desc' => '@TODO: de-selecting all +save  fails',
		'fields' => array(
			array(
				'name' => __('Allow booking for', 'commons-booking-2'),
				'id' => 'permissions_user-roles',
				'type' => 'multicheck',
				'options' => cb2_form_get_user_roles(),
				'default' => cb2_form_get_user_roles(true),
				'select_all_button' => true
			),
		)
	),
	/* permissions end */
	/* booking options start */
	'bookingoptions' => array(
		'title' => 'Usage restrictions',
		'id' => 'bookingoptions',
		'desc' => '@todo',
		'fields' => array(
			array(
				'name' => __('Minimum usage time', 'commons-booking-2'),
				'id' => 'bookingoptions_min-usage',
				'desc' => __('hh:mm', 'commons-booking-2'),
				'type' => 'text_time',
				'time_format' => 'H:i',
			),
			array(
				'name' => __('Maximum usage time', 'commons-booking-2'),
				'id' => 'bookingoptions_max-usage',
				'desc' => __('hh:mm', 'commons-booking-2'),
				'type' => 'text_time',
				'time_format' => 'H:i',
			),
			array(
				'name' => __('Approval needed', 'commons-booking-2'),
				'desc' => __('Bookings need to be approved by an admin', 'commons-booking-2'),
				'id' => 'bookingoptions_approval-needed',
				'type' => 'checkbox',
				'default' => cmb2_set_checkbox_default_for_new_post(false)
			)
		)
	),
	/* booking options end */
	/* maps start */
	'maps' => array(
		'title' => 'Maps',
		'id' => 'maps',
		'desc' => '@todo',
		'fields' => array(
			array(
				'name' => __('API Key', 'commons-booking-2'),
				'desc' => __('Get your API key at https://geocoder.opencagedata.com/users/sign_up, comma-separated', 'commons-booking-2'),
				'id' => 'maps_api-key',
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
	'extrametafields' => array(
		'title' => 'Extra meta fields',
		'id' => 'extrametafields',
		'desc' => 'If you set up additional meta fields for item, location, user or booking (for example: use another plugin to add registration fields).<br> Enter the field names here to make them available for use as template tags: {{mytemplatetag}}.',
		'fields' => array(
			array(
				'name' => __('Item meta fields', 'commons-booking-2'),
				'desc' => __('Comma separated, e.g.: fieldname_1,fieldname_2,fieldname_3', 'commons-booking-2'),
				'id' => 'extrametafields_item',
				'type' => 'text',
				'default' => '',
			),
			array(
				'name' => __('Location meta fields', 'commons-booking-2'),
				'desc' => __('Comma separated, e.g.: fieldname_1,fieldname_2,fieldname_3', 'commons-booking-2'),
				'id' => 'extrametafields_location',
				'type' => 'text',
				'default' => '',
			),
			array(
				'name' => __('Booking meta fields', 'commons-booking-2'),
				'desc' => __('Comma separated, e.g.: fieldname_1,fieldname_2,fieldname_3', 'commons-booking-2'),
				'id' => 'extrametafields_booking',
				'type' => 'text',
				'default' => '',
			),
			array(
				'name' => __('User meta fields', 'commons-booking-2'),
				'desc' => __('Comma separated, e.g.: fieldname_1,fieldname_2,fieldname_3', 'commons-booking-2'),
				'id' => 'extrametafields_user',
				'type' => 'text',
				'default' => '',
			),
		)
	),
	/* extra_meta_fields end */
	/* email templates start */
	'emailtemplates' => array(
		'title' => __('Email templates', 'commons-booking-2'),
		'id' => 'emailtemplates',
		'desc' => 'Email templates. You can use html & {{template_tags}}',
		'fields' => array(
			array(
				'name' => __('Booking pending email subject', 'commons-booking-2'),
				'desc' => __('', 'commons-booking-2'),
				'id' => 'emailtemplates_mail-booking-pending-subject',
				'type' => 'text',
				'default' => '',
			),
			array(
				'name' => __('Booking pending email body', 'commons-booking-2'),
				'desc' => __('', 'commons-booking-2'),
				'id' => 'emailtemplates_mail-booking-pending-body',
				'type' => 'textarea',
				'default' => '',
			),
			array(
				'name' => __('Booking approved email subject', 'commons-booking-2'),
				'desc' => __('', 'commons-booking-2'),
				'id' => 'emailtemplates_mail-booking-approved-subject',
				'type' => 'text',
				'default' => '',
			),
			array(
				'name' => __('Booking approved email body', 'commons-booking-2'),
				'desc' => __('', 'commons-booking-2'),
				'id' => 'emailtemplates_mail-booking-approved-body',
				'type' => 'textarea',
				'default' => '',
			),
			array(
				'name' => __('Booking canceled email subject', 'commons-booking-2'),
				'desc' => __('', 'commons-booking-2'),
				'id' => 'emailtemplates_mail-booking-canceled-subject',
				'type' => 'text',
				'default' => '',
			),
			array(
				'name' => __('Booking canceled email body', 'commons-booking-2'),
				'desc' => __('', 'commons-booking-2'),
				'id' => 'emailtemplates_mail-booking-canceled-body',
				'type' => 'textarea',
				'default' => '',
			),
		)
	),
	/* email templates end */
);
