<?php
// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_PeriodStatusType extends CB_PostNavigator implements JsonSerializable {
  public  static $database_table = 'cb2_period_status_types';
  public  static $all = array();
  public  static $standard_fields = array( 'name' );
  static  $static_post_type = 'periodstatustype';
  public  static $post_type_args = array(
		'menu_icon' => 'dashicons-admin-settings',
		'label'     => 'Period Status Types',
  );

  function post_type() {return self::$static_post_type;}

  public function __construct(
		$ID,
    $period_status_type_id = NULL,
    $name     = NULL,
    $colour   = NULL,
    $opacity  = NULL,
    $priority = NULL,
    $return   = NULL,
    $collect  = NULL,
    $use      = NULL,
    $system   = NULL
  ) {
		CB_Query::assign_all_parameters( $this, func_get_args(), __class__ );
		$this->id         = $period_status_type_id;
		$this->system     = $system;

    // WP_Post values
    $this->post_title = $name;
		$this->post_type  = self::$static_post_type;

		if ( ! is_null( $ID ) ) self::$all[$ID] = $this;
  }

  static function &factory_from_wp_post( $post ) {
		CB_Query::get_metadata_assign( $post ); // Retrieves ALL meta values

		if ( is_null( $post->priority ) ) throw new Exception( "post_status_type has no priority" );

		$object = self::factory(
			$post->ID,
		  $post->period_status_type_id,
			$post->post_title,
			$post->colour,
			$post->opacity,
			$post->priority,
			( $post->return  != '0' ),
			( $post->collect != '0' ),
			( $post->use     != '0' ),
			$post->system
		);

		CB_Query::copy_all_properties( $post, $object );

		return $object;
	}


  static function &factory(
		$ID,
    $period_status_type_id,
    $name     = NULL,
    $colour   = NULL,
    $opacity  = NULL,
    $priority = NULL,
    $return   = NULL,
    $collect  = NULL,
    $use      = NULL,
    $system   = NULL
  ) {
    // Design Patterns: Factory Singleton with Multiton
    if ( ! is_null( $ID ) &&  isset( self::$all[$ID] ) )
			$object = self::$all[$ID];
    else {
      $Class = 'CB_PeriodStatusType';
      // Hardcoded system status types
      // TODO: create a trigger preventing deletion of these
      switch ( $period_status_type_id ) {
        case PERIOD_STATUS_TYPE_AVAILABLE: $Class = 'CB_PeriodStatusType_Available'; break;
        case PERIOD_STATUS_TYPE_BOOKED:    $Class = 'CB_PeriodStatusType_Booked';    break;
        case PERIOD_STATUS_TYPE_CLOSED:    $Class = 'CB_PeriodStatusType_Closed';    break;
        case PERIOD_STATUS_TYPE_OPEN:      $Class = 'CB_PeriodStatusType_Open';      break;
        case PERIOD_STATUS_TYPE_REPAIR:    $Class = 'CB_PeriodStatusType_Repair';    break;
      }

			$reflection = new ReflectionClass( $Class );
			$object     = $reflection->newInstanceArgs( func_get_args() );
    }

    return $object;
  }

  function styles() {
    $styles = '';
    if ( $this->colour   ) $styles .= 'color:#'  . $this->colour           . ';';
    if ( $this->priority ) $styles .= 'z-index:' . $this->priority + 10000 . ';';
    if ( $this->opacity && $this->opacity != 100 ) $styles .= 'opacity:' . $this->opacity / 100    . ';';
    return $styles;
  }

  function add_actions( &$actions, $post ) {
		if ( property_exists( $post, 'system' ) && $post->system ) {
			array_unshift( $actions, '<b style="color:#000;">System Status Type</b>' );
			unset( $actions['trash'] );
		}
	}

  function classes() {
    return '';
  }

  function indicators() {
    $indicators = array();
    array_push( $indicators, ( $this->return  === TRUE ? 'return'  : 'no-return'  ) );
    array_push( $indicators, ( $this->collect === TRUE ? 'collect' : 'no-collect' ) );
    array_push( $indicators, ( $this->use     === TRUE ? 'use'     : 'no-use'     ) );

    return $indicators;
  }

  function jsonSerialize() {
    return array_merge( (array) $this, array(
      'styles'     => $this->styles(),
      'classes'    => $this->classes(),
      'indicators' => $this->indicators(),
    ) );
  }
}

CB_Query::register_schema_type( 'CB_PeriodStatusType' );

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_PeriodStatusType_Available extends CB_PeriodStatusType {
	public function __construct(...$args) {
		call_user_func_array( array( get_parent_class(), '__construct' ), func_get_args() );
	}
}
class CB_PeriodStatusType_Booked    extends CB_PeriodStatusType {
	public function __construct(...$args) {
		call_user_func_array( array( get_parent_class(), '__construct' ), func_get_args() );
	}
}
class CB_PeriodStatusType_Closed    extends CB_PeriodStatusType {
	public function __construct(...$args) {
		call_user_func_array( array( get_parent_class(), '__construct' ), func_get_args() );
	}
}
class CB_PeriodStatusType_Open      extends CB_PeriodStatusType {
	public function __construct(...$args) {
		call_user_func_array( array( get_parent_class(), '__construct' ), func_get_args() );
	}
}
class CB_PeriodStatusType_Repair    extends CB_PeriodStatusType {
	public function __construct() {
		call_user_func_array( array( get_parent_class(), '__construct' ), func_get_args() );
	}
}
