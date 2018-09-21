<?php

/**
 * Booking codes
 *
 * Gets the comma-seperated codes list from pluginsettings ,
 * splits to array
 * Provides sanitize (use on save in settings)
 * Provides validate (use before assigning codes to period)
 *
 * @package   Commons_Booking
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
class CB2_Codes
{
    /**
     * Instance of this class.
     *
     * @var object
     */
    protected static $instance = null;
    /**
     * Booking codes array
     *
     * @var array
     */
    static $booking_codes = array();
    /**
     * Minimum code count
     *
     * @var array
     */
    static $minimum_code_count = 5;
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
                do_action('cb2_codes_failed', $err);
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
        self::$codes_array = self::get_codes_from_settings();
    }
    /**
     * Get the codes string from settings, save as array
     *
     * @uses CB2_Settings
     *
     * @return array $codes_array
     */
    public static function get_codes_from_settings( )
    {

        $codes_string = CB2_Settings::get('codes', 'codes-pool');
        $codes_array = explode(',', $codes_string);
        return $codes_array;
    }
    /**
     * Get a random code from the codes pool
     */
    public static function get_random_code( )
    {

        $count = count($this->codes_array);
        $random = rand(0, $count - 1);

        return esc_attr(trim($this->codes_array[ $random ]));

    }
    /**
     * Check if enough comma-seperated codes are defined in the codes pool before assigning a code to an period
     *
     * @return bool
     */
    public static function validate_before_assign( )
    {

        if (is_array(self::$booking_codes) && ( count(self::$booking_codes) >= self::$minimum_code_count ) ) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * Sanitize input in plugin settings form field
     *
     * @param  string $field_value
     * @return string cleaned-up-string
     */
    public static function sanitize_form( $field_value )
    {

        return $field_value;

    }
}
