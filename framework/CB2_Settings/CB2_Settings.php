<?php

/**
 * Settings for CB2
 *
 * Global settings & settings for availabilities
 * Get setting usage: $setting = CB2_Settings::get( 'bookings', 'max-slots');
 *
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */

class CB2_Settings
{

    /**
     * Instance of this class.
     *
     * @var object
     */
    protected static $instance = null;
    /**
     * Settings array
     *
     * @var object
     */
    protected static $plugin_settings;
    /**
     * Settings groups, 1 group is 1 metabox
     *
     * @var array
     */
    protected static $plugin_settings_groups;
    /**
     * Admin menu tabs
     *
     * @var array
     */
    protected static $plugin_settings_tabs;
    /**
     * Settings groups used in timeframe options
     *
     * @var array
     */
    protected static $timeframe_options = array();
    /**
     * Settings slug
     *
     * @var string
     */
    protected static $settings_prefix = 'cb2_settings_';
    /**
     * Metabox (setting groups) defaults
     *
     * @var array
     */
    protected static $metabox_defaults = array (
			'show_on' => array(
        'key' => 'options-page',
        'value' => array('commons-booking'),
      ),
			'show_names' => true,
		);
    /**
     * Return an instance of this class.
     *
     * @since 2.0.0
     *
     * @return object A single instance of this class.
     */
    public static function get_instance()
    {

        // If the single instance hasn't been set, set it now.
        if (null == self::$instance) {
            try {
                self::$instance = new self;
                self::initialize();
            } catch (Exception $err) {
                var_dump($err);
                do_action('cb2_settings_failed', $err);
                if (WP_DEBUG) {
                    throw $err->getMessage();
                }
            }
        }
        return self::$instance;
    }
    /**
     * Initialize
     *
     * @since 2.0.0
     *
     * @return void
     */
    public static function initialize()
    {
    	require_once(CB2_PLUGIN_ROOT . 'framework/CB2_Settings/includes/settings_groups.php');

			self::$plugin_settings_groups = $cb2_settings_groups;

			self::settings_tabs();

			// cmb2 action on metabox save
			add_action('cmb2_save_options-page_fields', array( 'CB2_Settings','todo_action_cmb2_save_object_type_fields'), 10, 4);

		}

    // define the cmb2_save_<object_type>_fields callback
    public static function todo_action_cmb2_save_object_type_fields($object_id, $this_cmb_id, $this_updated, $instance)
			{
					echo ("hello" );
					var_dump( $this_cmb_id );
					var_dump( $object_id );
			}
    /**
     * Plugins Settings GUI: Screen
     *
     * @since 2.0.0
		 *
     * @return string $html
     */

    public static function settings_screen( ) {
			$html = sprintf('
				<div class="wrap">
					<h2>%s</h2>
					<div id="tabs" class="settings-tab">
						<ul>
							%s
						</ul>
						%s
					</div>
				</div>
			</div>',
		esc_html( get_admin_page_title() ),
		CB2_Settings::render_admin_tabs(),
		CB2_Settings::render_plugin_settings_screen()
		);
		return $html;

		}
    /**
     * Add tabs
     *
     * @since 2.0.0
     */

    public static function settings_tabs( )
    {
			 /* CB2 */
			self::add_settings_tab(
					array(
					'title' => __('CB2', 'commons-booking-2'),
					'id' => 'cb2',
					'tab_show' => true, // or callback
					'content' => '<h2>Welcome</h2>' . self::tab_features() // or callback
					)
			);
			/* Tab: General */
			self::add_settings_tab(
					array(
					'title' => __('General', 'commons-booking-2'),
					'id' => 'general',
					'tab_show' => true, // or callback
					'content' => self::tab_general() // or callback
					)
			);
			/* Tab: bookings */
			self::add_settings_tab(
					array(
					'title' => __('Bookings', 'commons-booking-2'),
					'id' => 'bookings',
					'tab_show' => true, // or callback
					'content' => self::tab_bookings() // or callback
					)
			);
			/* Tab feature (conditional): Maps */
			self::add_settings_tab(
					array(
					'title' => __('Maps', 'commons-booking-2'),
					'id' => 'maps',
					'tab_show' => self::is_enabled('features', 'enable-maps'),
					'content' => self::tab_maps() // or callback
					)
			);
			/* Tab feature (conditional): Codes */
			self::add_settings_tab(
					array(
					'title' => __('Codes', 'commons-booking-2'),
					'id' => 'codes',
					'tab_show' => self::is_enabled('features', 'enable-codes'),
					'content' => self::tab_codes() // or callback
					)
			);
			/* Tab feature (conditional): Holidays */
			self::add_settings_tab(
					array(
					'title' => __('Holidays', 'commons-booking-2'),
					'id' => 'holidays',
					'tab_show' => self::is_enabled('features', 'enable-holidays'),
					'content' => self::tab_holidays() // or callback
					)
			);
			/* Tab: Strings */
			self::add_settings_tab(
					array(
					'title' => __('Strings', 'commons-booking-2'),
					'id' => 'strings',
					'tab_show' => true,
					'content' => self::tab_strings() // or callback
					)
			);

    }
    /**
     * Get settings group
     *
     * @since 2.0.0
		 *
		 * @param string $group_name
     *
     * @return array $group
     */

    public static function get_settings_group( $group_name )
    {
			$settings = CB2_Settings::$plugin_settings_groups;

			if (array_key_exists($group_name, $settings)) {
					return $settings[$group_name];
			}		else {
				return false;
			}
    }

    /**
     * Contents for the plugin settings tab "general"
     *
     * @since 2.0.0
     *
     * @return mixed
     */
    public static function tab_general( )
    {
			$metabox = self::get_settings_group( 'pages' );
			return self::render_settings_group_metabox ( $metabox );
    }
    /**
     * Contents for the plugin settings tab "features"
     *
     * @since 2.0.0
     *
     * @return mixed
     */
    public static function tab_features( )
    {
			$metabox = self::get_settings_group('features');
			return self::render_settings_group_metabox ( $metabox );
    }
    /**
     * Contents for the plugin settings tab "bookings"
     *
     * @since 2.0.0
     *
     * @return mixed
     */

    public static function tab_bookings( )
    {
			$metabox_permissions = self::get_settings_group('permissions');

			$metabox_booking_options = self::get_settings_group('booking_options');

			return self::render_settings_group_metabox ( $metabox_permissions ) . self::render_settings_group_metabox ( $metabox_booking_options );
    }
    /**
     * Contents for the plugin settings tab "maps"
     *
     * @since 2.0.0
     *
     * @return mixed
     */

    public static function tab_maps( )
    {
			$metabox = self::get_settings_group('maps');
    	return self::render_settings_group_metabox($metabox);

    }
    /**
     * Contents for the plugin settings tab "codes"
     *
     * @since 2.0.0
     *
     * @return mixed
     */

    public static function tab_codes( )
    {
			return 'Codes are here';
    }
    /**
     * Contents for the plugin settings tab "codes"
     *
     * @since 2.0.0
     *
     * @return mixed
     */

    public static function tab_holidays( )
    {
			return 'Holiday fun';
		}

    /**
     * Strings (Gui Strings for the front-end)
     *
     * @since 2.0.0
     *
     * @uses CB2_Strings
     *
     * @return array
     */
    public static function tab_strings()
    {

        $strings_array = CB2_Strings::get();
        $fields_array = array();

        // reformat array to fit our cmb2 settings fields
        foreach ($strings_array as $category => $fields) {
            // add title field
            $fields_array[] = array(
            'name' => $category,
            'id' => $category . '-title',
            'type' => 'title',
            );
            foreach ($fields as $field_name => $field_value) {

							$fields_array[] = array(
								'name' => $field_name,
								'id' => $category . '_' . $field_name,
								'type' => 'text',
								'default' => $field_value
							);
            } // end foreach fields

				} // end foreach strings_array

      $metabox = array(
        'title' => 'Strings',
        'id' => self::$settings_prefix . 'strings',
        'description' => 'Allows you to customize frontend strings and messages. Use it to rename "items" to "bikes" if you only have bikes to share. <br>N.B. Strings should NOT be used for translation, instead please <a href="#@TODO">help us to localize CB2 into your language</a>.',
          'fields' =>
							$fields_array
      );
    return self::render_settings_group_metabox($metabox);
    }

    /**
     * Add a settings tab
     *
     * @since 2.0.0
     *
     * @param array $args
     */
    public static function add_settings_tab( $tab=array() )
    {
      if ( ! empty ($tab) && is_array($tab) && $tab['tab_show'] == TRUE ) {
        self::$plugin_settings_tabs[$tab['id']] = $tab;
      }
		}
    /**
     * Check if a specific setting is enabled
     *
     * @since 2.0.0
     *
     * @param array $group The settings group
     * @param array $setting  The setting name
     *
     * @return bool
     */
		public static function is_enabled( $group, $setting ) {

			$setting = self::get( $group, $setting );

			if ( !empty ( $setting ) && $setting == 'on' ) {
            return true;
			} else {
				return FALSE;
			}
		}
    /**
     * Enable Settings to be overwritten in timeframe admin.
     *
     * @since 2.0.0
     *
     * @return void
     */

    public static function cb2_enable_timeframe_option($group_id)
    {

        array_push( self::$timeframe_options, $group_id );

    }
    /**
     * Render the timeframe options in edit.php
     *
     * @since 2.0.0
     *
     * @return array
     */

    public static function do_availability_options_metaboxes()
    {
        foreach (self::$timeframe_options as $option) {
            // Add setting groups
            CB2_Settings::do_settings_group($option);
        }
    }
    /**
     * Return field names and values as key/value pair
     * The options that are available as timeframe options
     *
     * @since 2.0.0
     *
     * @return array
     */

    public static function get_timeframe_option_group_fields()
    {

        $fields = array();

        if (!empty(self::$timeframe_options) && is_array(self::$timeframe_options)) {

            foreach (self::$timeframe_options as $option) {

                $group = self::get_settings_group_fields($option);

                foreach ($group as $group_fields) {
                    $field = $group_fields['id'];
                    $val = self::get($option, $field);
                    $fields[$field] = $val;
                }
            }
            return $fields;

        }
    }
    /**
     * Render the admin settings screen tabs & groups
     *
     * @since 2.0.0
     */
    public static function render_plugin_settings_screen()
    {
				$tabs = self::$plugin_settings_tabs;
				$html = '';

        if (is_array($tabs)) {
            foreach ($tabs as $tab_id => $tab_content) {
              $html .= sprintf (
								'<div id="tabs-%s" class="wrap">%s</div>',
								$tab_id,
								$tab_content['content']
							);
						}
						return $html;
        }
    }
	/**
	 * Render a settings group
	 *
	 * @since 2.0.0
	 *
	 * @param array $metabox_args
	 *
	 * @return mixed
	 */
    public static function render_settings_group_metabox( $metabox_args )
    {
				$settings_group = array_replace( self::$metabox_defaults, $metabox_args );

				$html = sprintf( '
				 <div class="postbox">
						<div class="inside">
						<h3>%s</h3>
						%s
						%s
						</div>
					</div>' ,
					$settings_group['title'],
					$settings_group['description'],
					cmb2_metabox_form( $settings_group, $settings_group['id'], array ('echo' => FALSE ))
				);
			return $html;
    }

    /**
     * Get settings admin tabs
     *
     * @since 2.0.0
     *
     * @return mixed $html
     */
    public static function render_admin_tabs()
    {
			$html = '';
			foreach (self::$plugin_settings_tabs as $key => $value) {
					$slug = $key;
					$html .= '<li><a href="#tabs-' . $slug . '">' . $value['title'] . '</a></li>';
			}
			return apply_filters('cb2_do_admin_tabs', $html);
    }
    /**
     * Get settings slug, prefix for storing/retrieving options from the wp_options table
     *
     * @since 2.0.0
     *
     * @return string $slug
     */
    public static function get_plugin_settings_slug()
    {
        return self::$settings_prefix;
    }
    /**
     * Get a setting from the WP options table
     *
     * @since 2.0.0
     *
     * @param string $option_group
     * @param string $option      (optional)
     * @param string $checkbox    (optional, @TODO)
     *
     * @return string/array
     */
    public static function get($option_group, $option = false, $checkbox = false)
    {

        $option_group_name = self::$settings_prefix . $option_group;
        $option_array = get_option($option_group_name);

        if (is_array($option_array) && $option && array_key_exists($option, $option_array)) { // we want a specific setting on the page and key exists
            return $option_array[$option];
        } elseif (!$option && is_array($option_array)) {
            return $option_array;
        } else {
            // @TODO rework message system, so it does not block usage.
            // CB2_Object::throw_error( __FILE__, $options_page . ' is not a valid setting');
        }
    }
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
     * Strings (for possible overwrite in the backend
     *
     * @since 2.0.0
     *
     * @uses CB2_Strings
     *
     * @return array
     */
    public static function get_settings_template_cb2_strings()
    {

        $strings_array = CB2_Strings::get();
        $fields_array = array();

        // reformat array to fit our cmb2 settings fields
        foreach ($strings_array as $category => $fields) {
            // add title field
            $fields_array[] = array(
            'name' => $category,
            'id' => $category . '-title',
            'type' => 'title',
            );
            foreach ($fields as $field_name => $field_value) {

                  $fields_array[] = array(
                   'name' => $field_name,
                   'id' => $category . '_' . $field_name,
                   'type' => 'textarea_small',
                   'default' => $field_value
                  );
            } // end foreach fields

        } // end foreach strings_array

        $settings_template_cb_strings = array(
        'name' => __('Strings', 'commons-booking'),
        'slug' => 'strings',
        'fields' => $fields_array
        );

        return $settings_template_cb_strings;
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


}
add_action('cmb2_admin_init', array('CB2_Settings', 'get_instance')); // Settings rely on cmb2, so call it after that is initiatilised
