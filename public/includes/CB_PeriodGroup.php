<?php
class CB_PeriodGroup extends CB_PostNavigator implements JsonSerializable {
	// TODO: use this generic period class
  public static $database_table = 'cb2_period_groups';
	public static $all = array();
  static $static_post_type = 'periodgroup';
  public static $post_type_args = array(
		'menu_icon' => 'dashicons-admin-settings',
		'label'     => 'Period Groups',
  );
  public $periods = array();

  function post_type() {return self::$static_post_type;}

  static function &factory_from_wp_post( $post ) {
		CB_Query::get_metadata_assign( $post ); // Retrieves ALL meta values
		if ( ! $post->period_IDs ) throw new Exception( 'CB_PeriodGroup requires period_IDs list, which can be empty' );

		$object = self::factory(
			$post->ID,
			$post->period_group_id,
			$post->post_title
		);
		foreach ( explode( ',', $post->period_IDs) as $period_id ) {
			$period = CB_Query::get_post_type( 'period', $period_id );
			$object->add_period( $period );
		}

		CB_Query::copy_all_properties( $post, $object );

		return $object;
	}

  static function &factory(
		$ID              = NULL,
		$period_group_id = NULL,
		$name            = NULL
  ) {
    // Design Patterns: Factory Singleton with Multiton
		if ( ! is_null( $ID ) && isset( self::$all[$ID] ) ) {
			$object = self::$all[$ID];
    } else {
			$reflection = new ReflectionClass( __class__ );
			$object     = $reflection->newInstanceArgs( func_get_args() );
    }

    return $object;
  }

  public function __construct(
		$ID              = NULL,
		$period_group_id = NULL,
		$name            = NULL
  ) {
		CB_Query::assign_all_parameters( $this, func_get_args(), __class__ );
		$this->id = $period_group_id;
		parent::__construct( $this->periods );
		if ( ! is_null( $ID ) ) self::$all[$ID] = $this;
  }

  function add_period( $period ) {
		array_push( $this->periods, $period );
  }

  function classes() {
		return '';
  }

  function post_save_post() {
		global $wpdb;

		parent::post_save_post();

		$table = "{$wpdb->prefix}cb2_period_group_period";
		$wpdb->delete( $table, array(
			'period_group_id' => $this->id
		) );
		foreach ( $this->posts as $post ) {
			$wpdb->insert( $table, array(
				'period_group_id' => $this->id,
				'period_id'       => $post->id,
			) );
		}
  }

  function jsonSerialize() {
		return $this;
	}
}

CB_Query::register_schema_type( 'CB_PeriodGroup' );
