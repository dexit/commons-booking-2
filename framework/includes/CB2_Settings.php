<?php
/**
 * Admin settings & Metaboxes for Commons Booking
 *
 * Global settings & settings for availabilities
 * Get setting usage: $setting = CB2_Settings::get( 'bookings', 'max-slots');
 *
 * All post type metaboxes are defined here.
 *
 * @package   Commons_Booking
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
     * Settings groups, 1 group is a metabox
     *
     * @var array
     */
    protected static $settings_groups;
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
     * @var array
     */
    protected static $settings_slug = CB_TEXTDOMAIN . '-settings-';
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
        if (null == self::$instance ) {
            try {
                self::$instance = new self;
                self::initialize();
            } catch ( Exception $err ) {
                do_action('cb2_settings_failed', $err);
                // if (WP_DEBUG ) {
                //     throw $err->getMessage();
                // }
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

        /* Add settings tabs */
        self::add_settings_tab('welcome', __('CB2', 'commons-booking-2'), 'Welcome');
        self::add_settings_tab('bookings', __('Bookings', 'commons-booking-2'), '');
        self::add_settings_tab('calendar', __('Calendar', 'commons-booking-2'), '');
        self::add_settings_tab('map', __('Map', 'commons-booking-2'), '');
        self::add_settings_tab('strings', __('Strings', 'commons-booking-2'), 'nothing yet');

        /* Add settings groups to tabs */
        self::add_settings_group(
            self::get_settings_template_bookings(),
            'bookings'
        );
        self::add_settings_group(
            self::get_settings_template_calendar(),
            'calendar'
        );
        self::add_settings_group(
            self::get_settings_template_map_geocode(),
            'map'
        );

        /* Add settings groups for cpts only  */
        self::add_settings_group(
            self::get_settings_template_location_opening_times()
        );
        self::add_settings_group(
            self::get_settings_template_location_pickup_mode()
        );
        self::add_settings_group(
            self::get_settings_template_location_personal_contact_info()
        );
        self::add_settings_group(
            self::get_settings_template_location_address()
        );


        /* Define setting groups that may be overwritten by timeframe (cb_timeframe_edit) */
        self::cb2_enable_timeframe_option('bookings');
        self::cb2_enable_timeframe_option('calendar');

    }


    /**
     * Add a settings group to plugin settings or a post type
     *
     * @since 2.0.0
     *
     * @param array $tab_id The id of the tab
     * @param array $group  The group config
     *
     * @return void
     */
    public static function add_settings_group( $group, $tab_id=false )
    {

        $slug = $group['slug'];
        self::$plugin_settings[ $slug ] = $group;

        if ($tab_id ) {
            self::$plugin_settings_tabs[ $tab_id ]['groups'][] = $slug;
        }


    }
    /**
     * Locations meta box: opening times template
     *
     * @since 2.0.0
     *
     * @return void
     */

    public static function cb2_enable_timeframe_option( $group_id )
    {

        array_push(self::$timeframe_options, $group_id);

    }
    /**
     * Render the timeframe options in the cb_timeframes_edit
     *
     * @since 2.0.0
     *
     * @return array
     */

    public static function do_timeframe_options()
    {
        foreach ( self::$timeframe_options as $option ) {
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

        if (!empty(self::$timeframe_options) && is_array(self::$timeframe_options) ) {

            foreach ( self::$timeframe_options as $option ) {

                $group = self::get_settings_group_fields($option);

                foreach ( $group as $group_fields ) {
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
    public static function do_admin_settings( )
    {

        $tabs = self::$plugin_settings_tabs;

        if (is_array($tabs) ) {

            foreach ( $tabs as $tab => $value ) {
                ?>
              <div id="tabs-<?php echo $tab ; ?>" class="wrap">
                <?php
                echo $value['description'];
                if (isset($value['groups']) && is_array($value['groups'])) {
                    foreach ( $value['groups'] as $group ) { // render the settings groups
                        self::do_settings_group($group);
                    }
                }
                ?>
                        </div>
            <?php
            }
        }

    }
    /**
     * Render a settings group
     *
     * @since 2.0.0
     *
     * @param string $group_id
     */
    public static function do_settings_group( $slug )
    {
        ?>
      <div class="metabox-holder">
       <div class="postbox">
        <div class="inside">
            <?php
            $cmb_bookings = new_cmb2_box(
                array(
                'id' => CB_TEXTDOMAIN  . '_options-' . $slug,
                'show_on' => array(
                'key' => 'options-page',
                'value' => array( 'commons-booking' ), ),
                'show_names' => true,
                'fields' => self::get_settings_group_fields($slug)
                )
            );

                        cmb2_metabox_form(CB_TEXTDOMAIN  . '_options-' . $slug, self::$settings_slug . $slug);
        ?>
        </div>
       </div>
      </div>
    <?php
    }
    /**
     * Add a settings tab
     *
     * @since 2.0.0
     *
     * @param array $args
     */
    public static function add_settings_tab( $tab_id, $title, $description )
    {

        self::$plugin_settings_tabs[$tab_id] = array(
        'title' => $title,
        'description' => $description
        );
    }
    /**
     * Add a settings tab
     *
     * @since 2.0.0
     *
     * @param array $args
     */
    public static function cb2_add_settings_to_cpt( $id, $title, $object_types = array(), $fields = array() )
    {
        $cmb = new_cmb2_box(
            array(
            'id' => $id,
            'title' => $title,
            'object_types' => $object_types, // Post type
            'fields' => $fields
            )
        );

    }
    /**
     * Get settings admin tabs
     *
     * @since 2.0.0
     *
     * @return mixed $html
     */
    public static function do_admin_tabs( )
    {

        $html = '';
        foreach ( self::$plugin_settings_tabs as $key => $value ) {
            $slug = $key;
            $html .= '<li><a href="#tabs-' . $slug . '">' . $value['title'] . '</a></li>';
        }
        return apply_filters('cb_do_admin_tabs', $html);
    }
    /**
     * Get a specific admin group
     *
     * @since  2.0.0
     * @param  string $slug slug of the settings group
     * @return array $metabox
     */
    public static function get_settings_group( $slug )
    {

        return self::$plugin_settings[$slug]['fields'];

    }
    /**
     * Get all the fields defined for a settings group @TODO
     *
     * @since 2.0.0
     *
     * @param  string $slug slug of the settings group
     * @return array $fields
     */
    public static function get_settings_group_fields( $slug )
    {

        return self::$plugin_settings[$slug]['fields'];

    }
    /**
     * Get settings slug, prefix for storing/retrieving options from the wp_options table
     *
     * @since 2.0.0
     *
     * @return string $slug
     */
    public static function get_plugin_settings_slug( )
    {

        return self::$settings_slug;

    }


    /**
     * Get a setting from the WP options table
     *
     * @since 2.0.0
     *
     * @param string $options_page
     * @param string $toption      (optional)
     * @param string $checkbox     (optional, @TODO)
     *
     * @return string/array
     */
    public static function get( $options_page, $option = false, $checkbox = false )
    {

        $options_page_name = self::$settings_slug . $options_page;

        $options_array = get_option($options_page_name);

        if (is_array($options_array) && $option && array_key_exists($option, $options_array)  ) { // we want a specific setting on the page and key exists
            return $options_array[ $option ];
        } elseif (! $option &&  is_array($options_array) ) {
            return $options_array;
        } else {
            // @TODO rework message system, so it does not block usage.
            // CB_Object::throw_error( __FILE__, $options_page . ' is not a valid setting');
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

        $settings_map_geocode = array(
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
        return $settings_map_geocode;
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
        'options' => CB2_Holidays::get_providers()
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
     * @uses CB_Strings
     *
     * @return array
     */
    public static function get_settings_template_cb_strings()
    {

        $strings_array = CB_Strings::get();
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
        'time_format' => 'H:i',
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
add_action('plugins_loaded', array( 'CB2_Settings', 'get_instance' ));
