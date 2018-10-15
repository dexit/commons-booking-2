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
	static function selector_metabox() {
		return array(
			'title'      => __( 'Period Status Type', 'commons-booking-2' ),
			'show_names' => FALSE,
			'context'    => 'side',
			'closed'     => true,
			'fields'     => array(
				array(
					'name'    => __( 'PeriodStatusType', 'commons-booking-2' ),
					'id'      => 'period_status_type_ID',
					'type'    => 'radio',
					'default' => ( isset( $_GET['period_status_type_ID'] ) ? $_GET['period_status_type_ID'] : NULL ),
					'options' => CB_Forms::period_status_type_options(),
				),
				array(
					'name' => __( '<a href="admin.php?page=cb2-periodstatustypes">add new</a>', 'commons-booking-2' ),
					'id' => 'exceptions',
					'type' => 'title',
				),
			),
		);
	}

  function post_type() {return self::$static_post_type;}

	static function metaboxes() {
		return array(
			array(
				'title' => __( 'Colour', 'commons-booking-2' ),
				'context' => 'side',
				'fields' => array(
					array(
						'name' => __( 'Colour', 'commons-booking-2' ),
						'id' => 'colour',
						'type' => 'colorpicker',
						'default' => '#000000',
					),
					array(
						'name' => __( 'Opacity', 'commons-booking-2' ),
						'id' => 'opacity',
						'type' => 'select',
						'options' => array(
							'1.0' => '1.0',
							'0.9' => '0.9',
							'0.8' => '0.8',
							'0.7' => '0.7',
							'0.6' => '0.6',
							'0.5' => '0.5',
						),
					),
				),
			),

			array(
				'title' => __( '<span class="cb2-todo">Item interaction</span>', 'commons-booking-2' ),
				'fields' => array(
					array(
						'name' => __( 'Flags', 'commons-booking-2' ),
						'id' => 'flags',
						'type' => 'multicheck',
						'options' => array(
							'1' => __( 'Collect', 'commons-booking-2' ),
							'2' => __( 'Use',     'commons-booking-2' ),
							'4' => __( 'Return',  'commons-booking-2' ),
						),
					),
				),
			),
		);
	}

  public function __construct(
		$ID,
    $name,
    $colour,
    $opacity,
    $priority,
    $return,
    $collect,
    $use,
    $system
  ) {
		CB_Query::assign_all_parameters( $this, func_get_args(), __class__ );
		$this->system     = $system;

    // WP_Post values
    $this->post_title = $name;
		$this->post_type  = self::$static_post_type;

		if ( ! is_null( $ID ) ) self::$all[$ID] = $this;
  }

  static function &factory_from_wp_post( $post, $instance_container = NULL ) {
		if ( $post->ID ) CB_Query::get_metadata_assign( $post ); // Retrieves ALL meta values

		if ( is_null( $post->priority ) ) throw new Exception( "post_status_type has no priority" );

		$object = self::factory(
			$post->ID,
			$post->post_title,
			$post->colour,
			$post->opacity,
			$post->priority,
			( $post->return  != '0' ),
			( $post->collect != '0' ),
			( $post->use     != '0' ),
			$post->system
		);

		CB_Query::copy_all_wp_post_properties( $post, $object );

		return $object;
	}


  static function &factory(
		$ID,
    $name,
    $colour,
    $opacity,
    $priority,
    $return,
    $collect,
    $use,
    $system
  ) {
    // Design Patterns: Factory Singleton with Multiton
    if ( ! is_null( $ID ) &&  isset( self::$all[$ID] ) )
			$object = self::$all[$ID];
    else {
      $Class = 'CB_PeriodStatusType';
      $id    = CB_Query::id_from_ID_with_post_type( $ID, CB_PeriodStatusType::$static_post_type );
      // Hardcoded system status types
      switch ( $id ) {
        case CB2_PERIOD_STATUS_TYPE_AVAILABLE: $Class = 'CB_PeriodStatusType_Available'; break;
        case CB2_PERIOD_STATUS_TYPE_BOOKED:    $Class = 'CB_PeriodStatusType_Booked';    break;
        case CB2_PERIOD_STATUS_TYPE_CLOSED:    $Class = 'CB_PeriodStatusType_Closed';    break;
        case CB2_PERIOD_STATUS_TYPE_OPEN:      $Class = 'CB_PeriodStatusType_Open';      break;
        case CB2_PERIOD_STATUS_TYPE_REPAIR:    $Class = 'CB_PeriodStatusType_Repair';    break;
        case CB2_PERIOD_STATUS_TYPE_HOLIDAY:   $Class = 'CB_PeriodStatusType_Holiday';   break;
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
		unset( $actions['view'] );
	}

  function manage_columns( $columns ) {
		$columns['collect']  = '<span class="cb2-todo">Collect</span>';
		$columns['use']      = '<span class="cb2-todo">Use</span>';
		$columns['return']   = '<span class="cb2-todo">Return</span>';
		$columns['priority'] = '<span>Priority</span>';
		$columns['colour']   = '<span>Colour</span>';
		$this->move_column_to_end( $columns, 'date' );
		return $columns;
	}

	function custom_columns( $column ) {
		$html = '';
		switch ( $column ) {
			case 'collect':
			case 'use':
			case 'return':
				$html .= "<input class='cb2-tick-only' type='checkbox' checked='$this->$column' />";
				break;
			case 'priority':
				$html .= "<span class='cb2-usage-count-ok' title='Higher priority Periods override each other'>$this->priority</span>";
				break;
			case 'colour':
				$html .= "<div class='cb2-colour-block' style='background-color:$this->colour'></div>";
				break;
		}
		return $html;
	}

	function classes() {
    return "cb2-$this->name";
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
class CB_PeriodStatusType_Holiday   extends CB_PeriodStatusType {
	public function __construct() {
		call_user_func_array( array( get_parent_class(), '__construct' ), func_get_args() );
	}
}
