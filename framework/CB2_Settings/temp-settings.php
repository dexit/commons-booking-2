    /**
     * Geo Code Service
     *
     * @since 2.0.0
     *
     * @return array
     */
    public static function get_settings_template_map_geocode()
    {

        $metabox = array(
        'name' => __('Map Geocode', 'commons-booking'),
        'slug' => 'map_geocode',
        'fields' => array(
        array(
					'name' => __('API Key', 'commons-booking'),
					'desc' => __('Get your API key at https://geocoder.opencagedata.com/users/sign_up, comma-separated', 'commons-booking'),
					'id' => 'api-key',
					'type' => 'text',
					'default' => '',
        		)
        	)
        );
        return $metabox;
    }
    /**
     * Booking settings template
     *
     * @since 2.0.0
     *
     * @return array
     */
    public static function get_settings_template_bookings()
    {

        $settings_bookings = array(
        'name' => __('Bookings', 'commons-booking'),
        'slug' => 'bookings',
        'fields' => array(
					array(
						'name' => __('Maximum slots', 'commons-booking'),
						'desc' => __('Maximum slots a user is allowed to book at once', 'commons-booking'),
						'id' => 'max-slots',
						'type' => 'text_small',
						'default' => 3
						),
					array(
						'name' => __('Consecutive slots', 'commons-booking'),
						'desc' => __('Slots must be consecutive', 'commons-booking'),
						'id' => 'consecutive-slots',
						'type' => 'checkbox',
						'default' => cmb2_set_checkbox_default_for_new_post(true)
					),
        array(
						'name' => __('Use booking codes', 'commons-booking'),
						'desc' => __('Create codes for every slot', 'commons-booking'),
						'id' => 'use-codes',
						'type' => 'checkbox',
						'default' => cmb2_set_checkbox_default_for_new_post(true)
						),
					)
        );
        return $settings_bookings;
    }
    /**
     * calendar settings template
     *
     * @since 2.0.0
     *
     * @return array
     */
    public static function get_settings_template_calendar()
    {

        $settings_calendar = array(
        'name' => __('Calendar', 'commons-booking'),
        'slug' => 'calendar',
        'fields' => array(
        array(
        'name' => __('Calendar limit', 'commons-booking'),
        'desc' => __('Calendar limit', 'commons-booking'),
        'id' => 'limit',
        'type' => 'text_small',
        'default' => '30',
        'description' => __('Limit calendars to X future days.')
        ),
        array(
                        'name' => __('Holidays', 'commons-booking'),
                        'desc' => __('Select country to show local holidays in the calendar and block those holidays from pickup/return.', 'commons-booking'),
                        'id' => 'holiday_provider',
                        'type' => 'select',
                        'show_option_none' => true,
                        'options' => array()
        ),
        array(
                        'name' => __('Allow booking over closed days & holidays', 'commons-booking'),
                        'desc' => __('E.g. Location is closed Saturday and Sunday, allow booking from Friday to Monday.', 'commons-booking'),
                        'id' => 'closed_days_booking',
                        'type' => 'checkbox'
        )
        )
        );
        return $settings_calendar;
    }
    /**
     * Pages settings template
     *
     * @since 2.0.0
     *
     * @return array
     */
    public static function get_settings_template_pages()
    {

        $settings_pages = array(
        'name' => __('Pages', 'commons-booking'),
        'slug' => 'pages',
        'fields' => array(
        array(
        'before_row' => __('Pages: Items and calendar', 'commons-booking'), // Headline
        'name' => __('Items page', 'commons-booking'),
        'desc' => __('Display list of items on this page', 'commons-booking'),
        'id' => 'item-page-id',
        'type' => 'select',
        'show_option_none' => true,
        'default' => 'none',
        'options' => cb_get_pages_dropdown(),
        ),
        array(
                        'name' => __('Locations page', 'commons-booking'),
                        'desc' => __('Display list of Locations on this page', 'commons-booking'),
                        'id' => 'location-page-id',
                        'type' => 'select',
                        'show_option_none' => true,
                        'default' => 'none',
                        'options' => cb_get_pages_dropdown(),
        ),
        array(
                        'name' => __('Calendar page', 'commons-booking'),
                        'desc' => __('Display the calendar on this page', 'commons-booking'),
                        'id' => 'calendar-page-id',
                        'type' => 'select',
                        'show_option_none' => true,
                        'default' => 'none',
                        'options' => cb_get_pages_dropdown(),
        ),
        array(
                        'before_row' => __('Pages: Bookings', 'commons-booking'), // Headline
                        'name' => __('Booking review page', 'commons-booking'),
                        'desc' => __('Shows the pending booking, prompts for confimation.', 'commons-booking'),
                        'id' => 'booking-review-page-id',
                        'type' => 'select',
                        'show_option_none' => true,
                        'default' => 'none',
                        'options' => cb_get_pages_dropdown(),
        ),
        array(
                        'name' => __('Booking confirmed page', 'commons-booking'),
                        'desc' => __('Displayed when the user has confirmed a booking.', 'commons-booking'),
                        'id' => 'booking-confirmed-page-id',
                        'type' => 'select',
                        'show_option_none' => true,
                        'default' => 'none',
                        'options' => cb_get_pages_dropdown(),
        ),
        array(
                        'name' => __('Booking page', 'commons-booking'),
                        'desc' => __('', 'commons-booking'),
                        'id' => 'booking-page-id',
                        'type' => 'select',
                        'show_option_none' => true,
                        'default' => 'none',
                        'options' => cb_get_pages_dropdown(),
        ),
        array(
                        'name' => __('My bookings page', 'commons-booking'),
                        'desc' => __('Shows the user´s bookings.', 'commons-booking'),
                        'id' => 'user-bookings-page-id',
                        'type' => 'select',
                        'show_option_none' => true,
                        'default' => 'none',
                        'options' => cb_get_pages_dropdown(),
        )
        )
        );
        return $settings_pages;
    }
    /**
     * Codes settings template
     *
     * @since 2.0.0
     *
     * @return array
     */
    public static function get_settings_template_codes()
    {

        $settings_codes = array(
        'name' => __('Codes', 'commons-booking'),
        'slug' => 'codes',
        'fields' => array(
        array(
        'name' => __('Codes', 'commons-booking'),
        'desc' => __('Booking codes, comma-seperated', 'commons-booking'),
        'id' => 'codes-pool',
        'type' => 'textarea_code',
        'default' => 'none',
        )
        )
        );
        return $settings_codes;
    }

  /**
     * Locations meta box: address
     *
     * @since 2.0.0
     *
     * @return array
     */
    public static function get_settings_template_location_address()
    {

        $settings_template_location_address = array(
        'name' => __('Address', 'commons-booking'),
        'slug' => 'location-address',
        'show_in_plugin_settings' => false,
        'fields' => array(
        array(
        'name' => __('Address Line 1', 'commons-booking'),
        'id' => 'location-address-line1',
        'type' => 'text'
        ),
        array(
                        'name' => __('Address Line 2 (Optional)', 'commons-booking'),
                        'id' => 'location-address-line2',
                        'type' => 'text'
        ),
        array(
                        'name' => __('State', 'commons-booking'),
                        'id' => 'location-address-state',
                        'type' => 'text'
        ),
        array(
                        'name' => __('Postcode', 'commons-booking'),
                        'id' => 'location-address-postcode',
                        'type' => 'text'
        ),
        array(
                        'name' => __('Country', 'commons-booking'),
                        'id' => 'location-address-country',
                        'type' => 'text'
        ),
        array(
                        'name' => __('Latitude', 'commons-booking'),
                        'id' => 'location-address-latitude',
                        'type' => 'hidden'
        ),
        array(
                        'name' => __('Longitude', 'commons-booking'),
                        'id' => 'location-address-longitude',
                        'type' => 'hidden'
        ),
        )
        );
        return $settings_template_location_address;
    }
    /**
     * Locations meta box: opening times template
     *
     * @since 2.0.0
     *
     * @return array
     */
    public static function get_settings_template_location_opening_times()
    {

        $settings_template_location_opening_times = array(
        'name' => __('Location Opening Times', 'commons-booking'),
        'slug' => 'location-opening-times',
        'show_in_plugin_settings' => false,
        'fields' => array(
        array(
        'before_row' => __('Monday', 'commons-booking'), // Headline
        'name' => __('Open on Mondays', 'commons-booking'),
        'id' => 'location-open-mon',
        'type' => 'checkbox',
        ),
        array(
                        'before_row' => __('Monday', 'commons-booking'), // Headline
                        'name' => __('Open on Mondays', 'commons-booking'),
                        'id' => 'location-open-mon',
                        'type' => 'checkbox',
        ),
        array(
                        'name' => __('Opening time', 'commons-booking'),
                        'id' => 'location-open-mon-from',
                        'type' => 'text_time',
                        'time_format' => 'H:i', // TODO: convert to get_option( 'time_format' )?
                        'classes' => 'mon-hidden'
        ),
        array(
                        'name' => __('Closing time', 'commons-booking'),
                        'id' => 'location-open-mon-til',
                        'type' => 'text_time',
                        'time_format' => 'H:i',
                        'classes' => 'mon-hidden'
        ),
        array(
                        'before_row' => __('tuesday', 'commons-booking'), // Headline
                        'name' => __('Open on tuesdays', 'commons-booking'),
                        'id' => 'location-open-tue',
                        'type' => 'checkbox',
        ),
        array(
                        'name' => __('Opening time', 'commons-booking'),
                        'id' => 'location-open-tue-from',
                        'type' => 'text_time',
                        'time_format' => 'H:i',
                        'classes' => 'tue-hidden'
        ),
        array(
                        'name' => __('Closing time', 'commons-booking'),
                        'id' => 'location-open-tue-til',
                        'type' => 'text_time',
                        'time_format' => 'H:i',
                        'classes' => 'tue-hidden'
        ),
        array(
                        'before_row' => __('wednesday', 'commons-booking'), // Headline
                        'name' => __('Open on wednesdays', 'commons-booking'),
                        'id' => 'location-open-wed',
                        'type' => 'checkbox',
        ),
        array(
                        'name' => __('Opening time', 'commons-booking'),
                        'id' => 'location-open-wed-from',
                        'type' => 'text_time',
                        'time_format' => 'H:i',
                        'classes' => 'wed-hidden'
        ),
        array(
                        'name' => __('Closing time', 'commons-booking'),
                        'id' => 'location-open-wed-til',
                        'type' => 'text_time',
                        'time_format' => 'H:i',
                        'classes' => 'wed-hidden'
        ),
        array(
                        'before_row' => __('thursday', 'commons-booking'), // Headline
                        'name' => __('Open on thursdays', 'commons-booking'),
                        'id' => 'location-open-thu',
                        'type' => 'checkbox',
        ),
        array(
                        'name' => __('Opening time', 'commons-booking'),
                        'id' => 'location-open-thu-from',
                        'type' => 'text_time',
                        'time_format' => 'H:i',
                        'classes' => 'thu-hidden'
        ),
        array(
                        'name' => __('Closing time', 'commons-booking'),
                        'id' => 'location-open-thu-til',
                        'type' => 'text_time',
                        'time_format' => 'H:i',
                        'classes' => 'thu-hidden'
        ),
        array(
                        'before_row' => __('friday', 'commons-booking'), // Headline
                        'name' => __('Open on fridays', 'commons-booking'),
                        'id' => 'location-open-fri',
                        'type' => 'checkbox',
        ),
        array(
                        'name' => __('Opening time', 'commons-booking'),
                        'id' => 'location-open-fri-from',
                        'type' => 'text_time',
                        'time_format' => 'H:i',
                        'classes' => 'fri-hidden'
        ),
        array(
                        'name' => __('Closing time', 'commons-booking'),
                        'id' => 'location-open-fri-til',
                        'type' => 'text_time',
                        'time_format' => 'H:i',
                        'classes' => 'fri-hidden'
        ),
        array(
                        'before_row' => __('saturday', 'commons-booking'), // Headline
                        'name' => __('Open on saturdays', 'commons-booking'),
                        'id' => 'location-open-sat',
                        'type' => 'checkbox',
        ),
        array(
                        'name' => __('Opening time', 'commons-booking'),
                        'id' => 'location-open-sat-from',
                        'type' => 'text_time',
                        'time_format' => 'H:i',
                        'classes' => 'sat-hidden'
        ),
        array(
                        'name' => __('Closing time', 'commons-booking'),
                        'id' => 'location-open-sat-til',
                        'type' => 'text_time',
                        'time_format' => 'H:i',
                        'classes' => 'sat-hidden'
        ),
        array(
                        'before_row' => __('sunday', 'commons-booking'), // Headline
                        'name' => __('Open on sundays', 'commons-booking'),
                        'id' => 'location-open-sun',
                        'type' => 'checkbox',
        ),
        array(
                        'name' => __('Opening time', 'commons-booking'),
                        'id' => 'location-open-sun-from',
                        'type' => 'text_time',
                        'time_format' => 'H:i',
                        'classes' => 'sun-hidden'
        ),
        array(
                        'name' => __('Closing time', 'commons-booking'),
                        'id' => 'location-open-sun-til',
                        'type' => 'text_time',
                        'time_format' => 'H:i',
                        'classes' => 'sun-hidden'
        ),
        )
        );
        return $settings_template_location_opening_times;
    }
    /**
     * Locations meta box: choose pickup mode template
     *
     * @since 2.0.0
     *
     * @return array
     */
    public static function get_settings_template_location_pickup_mode()
    {

        $settings_template_location_pickup_mode = array(
        'name' => __('Pickup mode', 'commons-booking'),
        'slug' => 'location-pickup-mode',
        'show_in_plugin_settings' => false,
        'fields' => array(
        array(
        'name' => __('Pickup mode', 'commons-booking'),
        'id' => 'location-pickup-mode',
        'type' => 'radio_inline',
        'options' => array(
         'personal_contact' => __('Contact the location for pickup', 'commons-booking'),
         'opening_times' => __('Fixed opening times for pickup', 'commons-booking'),
                        ),
                        'default' => 'personal_contact',
        ),
        )
        );
        return $settings_template_location_pickup_mode;
    }
    /**
     * Locations meta box: location contact (personal contact)
     *
     * @since 2.0.0
     *
     * @return array
     */
    public static function get_settings_template_location_personal_contact_info()
    {

        $settings_template_location_personal_contact_info = array(
        'name' => __('Personal contact', 'commons-booking'),
        'slug' => 'location-personal-contact-info',
        'show_in_plugin_settings' => false,
        'fields' => array(
        array(
        'name' => __('Public', 'commons-booking'),
        'id' => 'location-personal-contact-info-public',
        'type' => 'textarea_small',
        'default' => __('Please contact the location after booking. The contact information will be in your confirmation email.', 'commons-booking'),
        ),
        array(
                        'name' => __('Private', 'commons-booking'),
                        'id' => 'location-personal-contact-info-private',
                        'type' => 'textarea_small',
                        'default' => __('Contact info: Phone, mail, etc.', 'commons-booking'),
        ),
        )
        );
        return $settings_template_location_personal_contact_info;
    }


