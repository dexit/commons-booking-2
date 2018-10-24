<?php
define( 'CB2_COLLECT', 1 );
define( 'CB2_USE',     2 );
define( 'CB2_RETURN',  4 );
define( 'CB2_ANY_ACTION', CB2_COLLECT | CB2_USE | CB2_RETURN );

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_PeriodStatusType extends CB_DatabaseTable_PostNavigator implements JsonSerializable {
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

  function database_table_name() { return self::$database_table; }

  function database_table_schema() {
		return array(
			'name'    => self::$database_table,
			'columns' => array(
				'period_status_type_id' => array( INT,     (11),   UNSIGNED, NOT_NULL, AUTO_INCREMENT ),
				'name'                  => array( VARCHAR, (1024), NULL,     NOT_NULL ),
				'description'           => array( VARCHAR, (1024), NULL,     NULL,     NULL, NULL ),
				'flags'                 => array( BIT,     (32),   NULL,     NOT_NULL, NULL, 0 ),
				'colour'                => array( VARCHAR, (256),  NULL,     NULL,     NULL, NULL ),
				'opacity'               => array( TINYINT, (1),    NULL,     NOT_NULL, NULL, 100 ),
				'priority'              => array( INT,     (11),   NULL,     NOT_NULL, NULL, 1 ),
			),
			'primary key' => array('period_status_type_id'),
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
						'type' => 'multicheck_inline',
						'options' => array(
							CB2_COLLECT => __( 'Collect', 'commons-booking-2' ),
							CB2_USE     => __( 'Use',     'commons-booking-2' ),
							CB2_RETURN  => __( 'Return',  'commons-booking-2' ),
						),
					),
				),
			),
		);
	}

  protected function __construct(
		// With PeriodStatusTypes we also want to
		// assume the existing ones, by id
		// rather than load them completely from the Database
		// thus creation by id is allowed without futher parameters
		$ID        = CB2_CREATE_NEW,
    $name      = NULL,
    $colour    = NULL,
    $opacity   = NULL,
    $priority  = NULL,
    $flags     = NULL,
    $system    = NULL
  ) {
		CB_Query::assign_all_parameters( $this, func_get_args(), __class__ );

		// Flags: these are extensible
    $this->collect   = $flags & CB2_COLLECT; // 1
    $this->use       = $flags & CB2_USE;     // 2
    $this->return    = $flags & CB2_RETURN;  // 4

    parent::__construct();
		if ( $ID ) self::$all[$ID] = $this;
  }

  static function &factory_from_properties( &$properties, &$instance_container = NULL ) {
		$object = self::factory(
			$properties['ID'],
			$properties['post_title'],
			$properties['colour'],
			$properties['opacity'],
			$properties['priority'],
			$properties['flags'],
			$properties['system']
		);

		self::copy_all_wp_post_properties( $properties, $object );

		return $object;
	}

  static function &factory(
		// With PeriodStatusTypes we also want to
		// assume the existing ones, by id
		// rather than load them completely from the Database
		// thus creation by id is allowed without futher parameters
		$ID        = CB2_CREATE_NEW,
    $name      = NULL,
    $colour    = NULL,
    $opacity   = NULL,
    $priority  = NULL,
    $return    = NULL,
    $collect   = NULL,
    $use       = NULL,
    $system    = NULL
  ) {
    // Design Patterns: Factory Singleton with Multiton
    if ( $ID && isset( self::$all[$ID] ) )
			$object = self::$all[$ID];
    else {
      $Class = 'CB_UserPeriodStatusType';
      $id    = CB_PostNavigator::id_from_ID_with_post_type( $ID, CB_PeriodStatusType::$static_post_type );
      // Hardcoded system status types
      switch ( $id ) {
        case CB_PeriodStatusType_Available::$id: $Class = 'CB_PeriodStatusType_Available'; break;
        case CB_PeriodStatusType_Booked::$id:    $Class = 'CB_PeriodStatusType_Booked';    break;
        case CB_PeriodStatusType_Closed::$id:    $Class = 'CB_PeriodStatusType_Closed';    break;
        case CB_PeriodStatusType_Open::$id:      $Class = 'CB_PeriodStatusType_Open';      break;
        case CB_PeriodStatusType_Repair::$id:    $Class = 'CB_PeriodStatusType_Repair';    break;
        case CB_PeriodStatusType_Holiday::$id:   $Class = 'CB_PeriodStatusType_Holiday';   break;
      }

			$reflection = new ReflectionClass( $Class );
			$object     = $reflection->newInstanceArgs( func_get_args() );
    }

    return $object;
  }

  function can( $actions ) {
		return $this->flags & $actions;
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
		$columns['collect']  = 'Collect';
		$columns['use']      = 'Use';
		$columns['return']   = 'Return';
		$columns['priority'] = 'Priority';
		$columns['colour']   = 'Colour';
		$columns['opacity']  = 'Opacity';
		$this->move_column_to_end( $columns, 'date' );
		return $columns;
	}

	function custom_columns( $column ) {
		$html = '';
		$css_opacity = ( is_numeric( $this->opacity ) ? $this->opacity / 100 : 1 );
		switch ( $column ) {
			case 'collect':
			case 'use':
			case 'return':
				$checked = ( $this->$column ? 'checked="1"' : '' );
				$html   .= "<input class='cb2-tick-only' type='checkbox' $checked />";
				break;
			case 'priority':
				$html .= "<span class='cb2-usage-count-ok' title='Higher priority Periods override each other'>$this->priority</span>";
				break;
			case 'colour':
				$html .= "<div class='cb2-colour-block' style='opacity:$css_opacity;background-color:$this->colour'></div>";
				break;
			case 'opacity':
				$html .= "<div>$this->opacity%</div>";
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
class CB_UserPeriodStatusType extends CB_PeriodStatusType {
	public function __construct(...$args) {
		call_user_func_array( array( get_parent_class(), '__construct' ), $args );
	}
}

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_SystemPeriodStatusType extends CB_PeriodStatusType {
	// These can be instanciated in 2 ways:
	//   load from DB: from a wp_post, with all its arguments
	//   save to DB:   from code to indicate the period status type to save
	// period_status_type_id is FIXED in the DB
	// so they are referred to by id
	protected function __construct(...$args) {
		if ( ! count( $args ) )
			throw new Exception( 'CB_SystemPeriodStatusType requires an ID' );
		call_user_func_array( array( get_parent_class(), '__construct' ), $args );
		if ( $this->ID == CB2_CREATE_NEW )
			throw new Exception( 'CB_SystemPeriodStatusType cannot be CB2_CREATE_NEW' );
		if ( $this->id() != $this::$id )
			throw new Exception( 'CB_SystemPeriodStatusType id is incorrect' );
		$this->system = TRUE;
	}
}


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_PeriodStatusType_Available extends CB_SystemPeriodStatusType {
	static $id = 1;
	public function __construct(...$args) {
		if ( ! count( $args ) ) {
			// No ID sent through: created for save, without arguments
			$ID   = CB_PostNavigator::ID_from_id_post_type( self::$id, CB_PeriodStatusType::$static_post_type );
			$args = array( $ID );
		}
		call_user_func_array( array( get_parent_class(), '__construct' ), $args );
	}
}

class CB_PeriodStatusType_Booked    extends CB_SystemPeriodStatusType {
	static $id = 2;
	public function __construct(...$args) {
		if ( ! count( $args ) ) {
			// No ID sent through: created for save, without arguments
			$ID   = CB_PostNavigator::ID_from_id_post_type( self::$id, CB_PeriodStatusType::$static_post_type );
			$args = array( $ID );
		}
		call_user_func_array( array( get_parent_class(), '__construct' ), $args );
	}
}

class CB_PeriodStatusType_Closed    extends CB_SystemPeriodStatusType {
	static $id = 3;
	public function __construct(...$args) {
		if ( ! count( $args ) ) {
			// No ID sent through: created for save, without arguments
			$ID   = CB_PostNavigator::ID_from_id_post_type( self::$id, CB_PeriodStatusType::$static_post_type );
			$args = array( $ID );
		}
		call_user_func_array( array( get_parent_class(), '__construct' ), $args );
	}
}

class CB_PeriodStatusType_Open      extends CB_SystemPeriodStatusType {
	static $id = 4;
	public function __construct(...$args) {
		if ( ! count( $args ) ) {
			// No ID sent through: created for save, without arguments
			$ID   = CB_PostNavigator::ID_from_id_post_type( self::$id, CB_PeriodStatusType::$static_post_type );
			$args = array( $ID );
		}
		call_user_func_array( array( get_parent_class(), '__construct' ), $args );
	}
}

class CB_PeriodStatusType_Repair    extends CB_SystemPeriodStatusType {
	static $id = 5;
	public function __construct(...$args) {
		if ( ! count( $args ) ) {
			// No ID sent through: created for save, without arguments
			$ID   = CB_PostNavigator::ID_from_id_post_type( self::$id, CB_PeriodStatusType::$static_post_type );
			$args = array( $ID );
		}
		call_user_func_array( array( get_parent_class(), '__construct' ), $args );
	}
}

class CB_PeriodStatusType_Holiday   extends CB_SystemPeriodStatusType {
	static $id = 6;
	public function __construct(...$args) {
		if ( ! count( $args ) ) {
			// No ID sent through: created for save, without arguments
			$ID   = CB_PostNavigator::ID_from_id_post_type( self::$id, CB_PeriodStatusType::$static_post_type );
			$args = array( $ID );
		}
		call_user_func_array( array( get_parent_class(), '__construct' ), $args );
	}
}
