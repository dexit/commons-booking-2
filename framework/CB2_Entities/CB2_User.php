<?php
/**
 * CB2 User class
 *
 *
 *
 * @package   CommonsBooking2
 * @author    The CommonsBooking Team <commonsbooking@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */

class CB2_User extends CB2_WordPress_Entity implements JsonSerializable
{
    public static $all    = array();
    public static $schema = 'with-perioditems'; //this-only, with-perioditems
    public static $posts_table    = false;
    public static $postmeta_table = false;
    public static $database_table = false;
    public static $static_post_type = 'user'; // Pseudo, but required

    public static function selector_metabox()
    {
        return array(
            'title' => __('User', 'commons-booking-2'),
            'show_names' => false,
            'fields' => array(
                array(
                    'name'    => __('User', 'commons-booking-2'),
                    'id'      => 'user_ID',
                    'type'    => 'select',
                    'default' => (isset($_GET['user_ID']) ? $_GET['user_ID'] : null),
                    'options' => CB2_Forms::user_options(),
                ),
            ),
        );
    }

    public function post_type()
    {
        return self::$static_post_type;
    }
    public function __toStringFor($column_data_type, $column_name)
    {
        return (string) $this->__toIntFor($column_data_type, $column_name);
    }

    public function __toIntFor($column_data_type, $column_name)
    {
        // CB2_Post only has 1 id for any data
        // although it should be an _ID column
        return $this->id();
    }
    public function id($why = '')
    {
        return $this->ID;
    }

    protected function __construct($ID, $user_login = null)
    {
        $this->perioditems    = array();

        if (! is_numeric($ID)) {
            throw new Exception("[$ID] is not numeric for [" . get_class($this) . ']');
        }

        // WP_Post values
        $this->ID         = (int) $ID;
        $this->user_login = $user_login;
        $this->post_title = $user_login;
        $this->post_type  = self::$static_post_type;

        parent::__construct($this->perioditems);

        self::$all[$ID] = $this;
    }

    public static function &factory_from_properties(&$properties, &$instance_container = null, $force_properties = false)
    {
        $object = self::factory(
            (isset($properties['user_ID']) ? $properties['user_ID'] : $properties['ID']),
            $properties['user_login']
        );

        self::copy_all_wp_post_properties($properties, $object);

        return $object;
    }

    public static function factory_current()
    {
        $cb_user = null;
        $wp_user = wp_get_current_user();
        if ($wp_user instanceof WP_User && $wp_user->ID) {
            $cb_user = new self($wp_user->ID, $wp_user->user_login);
        }
        return $cb_user;
    }

    public static function factory($ID, $user_login = null)
    {
        // Design Patterns: Factory Singleton with Multiton
        $object = null;

        if ($ID) {
            if (isset(self::$all[$ID])) {
                $object = self::$all[$ID];
            } else {
                $object = new self($ID, $user_login);
            }
        }

        return $object;
    }

    public function can($capability)
    {
        return user_can($this->ID, $capability);
    }

    public function add_perioditem(&$perioditem)
    {
        array_push($this->perioditems, $perioditem);
    }

    public function jsonSerialize()
    {
        $array = array(
      'ID' => $this->ID,
      'user_login' => $this->user_login,
    );
        if (self::$schema == 'with-perioditems') {
            $array[ 'perioditems' ] = &$this->perioditems;
        }

        return $array;
    }
}
