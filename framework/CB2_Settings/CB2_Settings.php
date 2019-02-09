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
    	require_once(CB2_PLUGIN_ROOT . 'framework/CB2_Settings/includes/settings_groups.php');
			self::$plugin_settings_groups = $cb2_settings_groups;

		}
    /**
     * Get a setting from the WP options table
     *
     * @since 2.0.0
     *
     * @param string $option_group
     * @param string $option      (optional)
     *
     * @return string/array
     */
    public static function get($option_group, $option = false)
    {

        $option_group_name = self::$settings_prefix . '_' . $option_group;
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
     * Get settings group fields
     *
     * @since 2.0.0
		 *
		 * @param string $group_name
     *
     * @return array $group
     */

    public static function get_settings_group_fields( $group_name )
    {
			$settings = CB2_Settings::$plugin_settings_groups;

			if (array_key_exists($group_name, $settings)) {
					return $settings[$group_name]['fields'];
			}		else {
				return false;
			}
		}
		public static function format_for_options_page( $settings_group ) {

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

			$setting = self::get( $group, $setting );

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
	/**
	 * A settings group metaboxes
	 *
	 * @since 2.0.0
	 *
	 * @param array $metabox_args
	 *
	 * @return mixed
	 */
    public static function render_settings_group ( $settings_group_ids = array() ){

			$metabox_html = '';
			$group_ids = (array) $settings_group_ids;

			foreach ( $group_ids as $group_id ) {

				$metabox_args = CB2_Settings::get_settings_group( $group_id );
				$args = array_merge( self::$metabox_defaults, $metabox_args);

				$metabox_html .= sprintf('
								<div class="postbox">
									<div class="inside">
									<h3>%s</h3>
									%s
									%s
									</div>
								</div>',
						$args['title'],
						$args['description'],
						cmb2_metabox_form($args, $args['id'], array('echo' => false))
				);
			}
			return $metabox_html;
		}
	/**
	 * A settings group metabox
	 *
	 * @since 2.0.0
	 *
	 * @param array $metabox_args
	 *
	 * @return mixed
	 */
    public function add_metabox ( $metabox_args, $tab='default' ){

			$args = array_merge (self::$metabox_options_defaults, $metabox_args );
			$metabox_html = sprintf( '
				<div class="postbox">
					<div class="inside">
					<h3>%s</h3>
					%s
					%s
					</div>
				</div>',
				$args['title'],
				$args['description'],
				cmb2_metabox_form( $args, $args['id'], array ('echo' => FALSE ))
			);
			return $metabox_html;
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
		public function test() {
			echo "hello";
		}


}
add_action('plugins_loaded', array('CB2_Settings', 'get_instance'));
