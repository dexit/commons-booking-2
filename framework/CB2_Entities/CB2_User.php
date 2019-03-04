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
    public static $posts_table    = FALSE;
    public static $postmeta_table = FALSE;
    public static $database_table = FALSE;
    public static $static_post_type = 'user'; // Pseudo, but required

  static function database_table_schemas( $prefix ) {
		// NOT MANAGED by CB2 $database_table = FALSE and 'managed' => FALSE
		// This wp_users table will not be created by CB2
		// it is here only for field definition understanding
		// the views in this case produce these wp_posts fields
		// and the system needs to unerstand their type
		// See:
		//   CB2_Query::to_object() which uses this conversion knowledge
		// when setting values on objects from string properties
		// WordPress wp_posts 4.x == 5.x
		$id_field = 'ID';
		$date_comment = 'Will remain a string when set with CB2_Query::to_object()';

		return array(
			array(
				'name'    => 'users',
				'managed' => FALSE, // Not created or removed. Here for data conversion only. Columns used by *_post views.
				'columns' => array(
					// TYPE, (SIZE), CB2_UNSIGNED, NOT NULL, CB2_AUTO_INCREMENT, DEFAULT, COMMENT
					$id_field       => array( CB2_BIGINT,  (20),  CB2_UNSIGNED, CB2_NOT_NULL, CB2_AUTO_INCREMENT ),
					'user_login'    => array( CB2_VARCHAR, (60),  NULL, CB2_NOT_NULL, NULL, '' ),
					'user_pass'     => array( CB2_VARCHAR, (255), NULL, CB2_NOT_NULL, NULL, '' ),
					'user_nicename' => array( CB2_VARCHAR, (50),  NULL, CB2_NOT_NULL, NULL, '' ),
					'user_email'    => array( CB2_VARCHAR, (100), NULL, CB2_NOT_NULL, NULL, '' ),
					'user_url'      => array( CB2_VARCHAR, (100), NULL, CB2_NOT_NULL, NULL, '' ),
					'user_registered' => array( CB2_DATETIME, NULL, NULL, CB2_NOT_NULL, NULL, '0000-00-00 00:00:00' ),
					'user_activation_key' => array( CB2_VARCHAR, (255), NULL, CB2_NOT_NULL, NULL, '' ),
					'user_status'   => array( CB2_INT,     (11),  NULL, CB2_NOT_NULL, NULL, '0' ),
					'display_name'  => array( CB2_VARCHAR, (250), NULL, CB2_NOT_NULL, NULL, '' ),
					'spam'          => array( CB2_TINYINT, (2),   NULL, CB2_NOT_NULL, NULL, '0' ),
					'deleted'       => array( CB2_TINYINT, (2),   NULL, CB2_NOT_NULL, NULL, '0' ),
				),
				'primary key'  => array( $id_field ),
				'unique keys'  => array(
					'user_login',
					'user_nicename',
					'user_email',
				),
			),
		);
  }

  public static function selector_metabox( String $context = 'normal', Array $classes = array(), $none = TRUE )
    {
        return array(
            'title' => __('User', 'commons-booking-2'),
            'show_names' => FALSE,
						'context'    => $context,
						'classes'    => $classes,
            'fields' => array(
                array(
                    'name'    => __('User', 'commons-booking-2'),
                    // Namespaced so it does not conflict with WordPress user_ID in forms
                    'id'      => 'cb2_user_ID',
                    'type'    => 'select',
                    'default' => (isset($_GET['cb2_user_ID']) ? $_GET['cb2_user_ID'] : null),
                    'options' => CB2_Forms::user_options( $none ),
                ),
                CB2_Query::metabox_nosave_indicator( 'cb2_user_ID' ),
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
				CB2_Query::assign_all_parameters( $this, func_get_args(), __class__ );

        // other values
				$this->perioditems  = array();
        $this->post_title   = $user_login;
        $this->post_type    = self::$static_post_type;

        // TODO: load User values
        $this->user_registered = '1971-01-01 00:00:00';
        $this->first_name      = 'TODO';
        $this->last_name       = 'todo';

        parent::__construct($this->perioditems);

        self::$all[$ID] = $this;
    }

    public static function &factory_from_properties(&$properties, &$instance_container = null, $force_properties = false)
    {
        $object = self::factory(
            ( isset( $properties['user_ID'] )    ? $properties['user_ID']    : $properties['ID'] ),
            ( isset( $properties['user_login'] ) ? $properties['user_login'] : NULL )
        );

        self::copy_all_wp_post_properties($properties, $object);

        return $object;
    }

    public static function factory_current()
    {
        $cb_user = NULL;
        $wp_user = wp_get_current_user();
        if ($wp_user instanceof WP_User && $wp_user->ID) {
            $cb_user = new self($wp_user->ID, $wp_user->user_login);
        }
        return $cb_user;
    }

    public static function factory( Int $ID, String $user_login = NULL )
    {
        // Design Patterns: Factory Singleton with Multiton
        $object = NULL;
				$key    = $ID;

        if ( $key && $ID != CB2_CREATE_NEW && isset( self::$all[$key] ) ) $object = self::$all[$key];
				else $object = new self($ID, $user_login);

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
