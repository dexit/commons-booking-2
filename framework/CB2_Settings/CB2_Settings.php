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
    static $plugin_settings;
    /**
     * Settings array
     *
     * @var object
     */
    static $page_slug = 'cb2_settings_page';
    /**
     * Settings groups, 1 group is 1 metabox
     *
     * @var array
     */
    static $plugin_settings_groups;
    /**
     * Admin menu tabs
     *
     * @var array
     */
    static $plugin_settings_tabs;
    /**
     * Settings groups used in timeframe options
     *
     * @var array
     */
    static $timeframe_options = array();
    /**
     * Settings slug
     *
     * @var string
     */
    static $settings_prefix = 'cb2_settings_';
    /**
     * Metabox (setting groups) defaults
     *
     * @var array
     */
    static $metabox_defaults = array (
			'show_on' => array(
        'key' => 'options-page',
        'value' => array('commons-booking-2'), /* plugin name */
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
			// add_action('cmb2_save_options-page_fields', array( 'CB2_Settings','todo_action_cmb2_save_object_type_fields'), 10, 4);

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
     * Check if a specific feature/setting checkbox is enabled
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

			if ( ! empty ( $setting ) && $setting == 'on' ) {
        return TRUE;
			} else {
				return FALSE;
			}
		}

    // define the cmb2_save_<object_type>_fields callback
    public static function todo_action_cmb2_save_object_type_fields($object_id, $this_cmb_id, $this_updated, $instance)
			{
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
     * Render the admin settings screen tabs & groups
     *
     * @since 2.0.0
     */
    public static function render_tab_contents()
    {

			$tabs = self::$plugin_settings_tabs;
			$html = '';
			CB2_Settings::render_admin_tabs();
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
	 * Plugins Settings GUI: Screen
	 *
	 * @since 2.0.0
	 *
	 * @return string $html
	 */
	public function render_settings_screen( ) {
        $html = sprintf(
            '
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
    esc_html(get_admin_page_title()),
    self::render_admin_tabs(),
    self::render_tab_contents()
		);
        return $html;
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


}
add_action('plugins_loaded', array('CB2_Settings', 'get_instance')); // Settings rely on cmb2, so call it after that is initiatilised
