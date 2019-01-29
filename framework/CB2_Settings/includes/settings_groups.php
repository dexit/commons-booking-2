<?php

// Individual settings groups (=metaboxes) stored in an array.

$cb2_settings_groups =
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
        /* features start */
        'features' => array(
                    'title' => 'Plugin features',
                    'id' => CB2_Settings::$settings_prefix . 'features',
                    'description' => 'Enable or disable plugin features site-wide. You can configure each feature´s settings after save.',
                    'fields' => array(

                        array(
                            'name' => __('Enable Maps', 'commons-booking-2'),
                            'description' => __('Enable maps and geocoding of adresses.', 'commons-booking-2'),
                            'id' => 'enable-maps',
                            'type' => 'checkbox',
                            'default' => cmb2_set_checkbox_default_for_new_post(false)
                            ),
                        array(
														'name' => __('Enable Codes', 'commons-booking-2'),
														'description' => __('Enable codes for the booking process.', 'commons-booking-2'),
														'id' => 'enable-codes',
														'type' => 'checkbox',
														'default' => cmb2_set_checkbox_default_for_new_post(false),
														),
                        array(
														'name' => __('Enable Holidays', 'commons-booking-2'),
														'description' => __('Show holidays on the calendar, automatically close locations.', 'commons-booking-2'),
														'id' => 'enable-holidays',
														'type' => 'checkbox',
														'default' => cmb2_set_checkbox_default_for_new_post(false),
														),

        )
                    ),
                /* features end */
                /* permissions start */
                'permissions' => array(
                        'title' => 'Permissions',
                        'id' => CB2_Settings::$settings_prefix . 'permissions',
                        'description' => 'this is the description',
                        'fields' => array(
                                array(
                                        'name' => __('Allow booking for', 'commons-booking-2'),
                                        'id' => 'user-roles',
                                        'type' => 'multicheck',
                                        'options' => cb2_form_get_user_roles()
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
                /* permissions end */
                /* booking options start */
                'booking_options' => array(
                        'title' => 'Usage restrictions',
                        'id' => CB2_Settings::$settings_prefix . 'booking-options',
                        'description' => 'this is the description',
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
                'id' => CB2_Settings::$settings_prefix . 'maps',
                'description' => 'this is the description',
          'fields' => array(
                    array(
                    'name' => __('API Key', 'commons-booking-2'),
                    'desc' => __('Get your API key at https://geocoder.opencagedata.com/users/sign_up, comma-separated', 'commons-booking-2'),
                    'id' => 'api-key',
                    'type' => 'text',
                    'default' => '',
                    ),
                )
              ),
    );
