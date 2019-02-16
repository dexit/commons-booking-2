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

class CB2_Settings {
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
    static $plugin_settings_metaboxes;
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
    static $settings_prefix = 'cb2_settings';
    /**
     * Metabox (setting groups) defaults
     *
     * @var array
     */
    public static $metabox_defaults = array (

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
    	require_once(CB2_PLUGIN_ROOT . 'framework/CB2_Settings/includes/settings_metaboxes.php');
			self::$plugin_settings_metaboxes = $cb2_settings_metaboxes;

		}
    /**
     * Get a setting from the WP options table
		 *
		 * Support for overwrite: if you supply a post_id, the plugin setting will be overwritten
		 * if the same setting exists for the post.
		 *
		 * Usage:
		 * $settings_global = CB2_Settings::get( 'bookingoptions_min-usage' ); -> Global settings
		 * $settings_item = CB2_Settings::get( 'bookingoptions_min-usage', 73); -> id must be provided for override
     *
     * @param string $option_id
     * @param string $post_id (optional)
		 * @param string default
     *
     * @return string/array $options
     */
    public static function get( $option_id, $post_id = false, $default='' ) {
			$option_array = get_option( self::$settings_prefix ); // the unserialised plugin settings array

			// first, check if the settings is overwritten in post meta
			if ( ( $post_id ) && cb2_post_exists ( $post_id ) ) { // post exists

				$meta_key = '_' . self::$settings_prefix . '_' . $option_id; // the name
				$post_type = get_post_type ( $post_id );

				if ( in_array( $meta_key, (array) get_post_custom_keys( $post_id) )) { // check if set in post meta
					return get_post_meta( $post_id, $meta_key , true);
				}

			} // otherwise return global plugin settings
			elseif (array_key_exists($option_id, (array) $option_array)) { // key exists in settings table

    		return $option_array[$option_id];
			} else {
				return $default;
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
			$settings = CB2_Settings::$plugin_settings_metaboxes;

			if (array_key_exists($group_name, $settings)) {
					return $settings[$group_name];
			}		else {
				return false;
			}
		}
	/**
	 *  Set cb2_options to defaults
	 *
	 * @TODO
	 *
	 */
	public function set_default_options() {

	}

	/**
	 *  Create the box array from a settings group, format ids and create cmb2 boxes
	 *
	 * @usage
	 *
	 * @param string $settings_group_id ID of the settings group
	 * @param array  $args cmb2 metabox args
	 * @param bool $options_page format for use on options page
	 *
	 */
	public static function prepare_settings_metabox( $settings_group_id, $args, $options_page=TRUE ) {

		$group = self::get_settings_group( $settings_group_id );
		$group_metabox = array_replace( $args, $group);

		// if options page: metabox ids must be prefixed e.g. cb2_settings_bookingoptions
		if( $options_page ) {
				$group_metabox['id'] = self::$settings_prefix . '_'. $group_metabox['id'];
		}	else { // field ids must be prefixed anywhere else: e.g. _cb2-settings_booking-options_max-slots
			foreach ($group_metabox['fields'] as $field_group_key => $field_group) {
					foreach ($field_group as $key => $value) {
							if ($key == 'id') {
									$group_metabox['fields'][$field_group_key][$key] = '_' .self::$settings_prefix . '_' . $value;
							}
					}
			}
		}
		// Add additional rows
		$group_metabox = self::prepend_description_as_row( $group_metabox ); // add description
		$group_metabox = self::append_reset_button_row( $group_metabox ); // add reset button
		$cmb = new_cmb2_box( $group_metabox ); // create box
		return $cmb;
	}



	/**
	 * If array contains a description, create a new row contaning this text
	 *
	 * @param array $metabox_array
	 * @return array $metabox_array with the description added as row
	 */
	public static function prepend_description_as_row( $metabox_array ) {

		if (array_key_exists('desc', $metabox_array)) { // attach settings group description as new "title" field row

			$desc_field = array(
					'id' => $metabox_array['id'] . '_desc',
					'desc' => $metabox_array['desc'],
					'type' => 'title',
					'classes' => 'cb2_form_desc',

			);
			array_unshift($metabox_array['fields'], $desc_field);
		}
		return $metabox_array;
	}
	/**
	 * Adds a reset button
	 *
	 * @param array $metabox_array
	 * @return array $metabox_array with the reset added as row
	 */
	public static function append_reset_button_row( $metabox_array ) {

		$reset_field = array(
				'id' => $metabox_array['id'] . '_reset',
				'desc' => __('<b>Reset<b> to plugin defaults @TODO', 'commons-booking-2'),
				'type' => 'title',
				'classes' => 'cb2_form_reset',
		);
		$metabox_array['fields'][] = $reset_field;

		return $metabox_array;
	}

    /**
     * Get settings group fields
     *
     * @since 2.0.0
		 *
		 * @param string $group_name
     *
     * @return array $fields
     */

    public static function get_settings_group_fields( $group_name )
    {
			$settings = CB2_Settings::$plugin_settings_metaboxes;

			if (array_key_exists($group_name, $settings)) {
					return $settings[$group_name]['fields'];
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
     * @param array $setting The setting name
     *
     * @return bool
     */
		public static function is_enabled( $setting ) {

			$setting = self::get( $setting );

			if ( ! empty ( $setting ) && $setting == 'on' ) {
        return TRUE;
			} else {
				return FALSE;
			}
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
}
add_action('plugins_loaded', array('CB2_Settings', 'get_instance'));
