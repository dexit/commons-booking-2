<?php
define( 'CB2_COLLECT', 1 );
define( 'CB2_USE',     2 );
define( 'CB2_RETURN',  4 );
define( 'CB2_ANY_ACTION', CB2_COLLECT | CB2_USE | CB2_RETURN );

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_PeriodStatusType extends CB2_DatabaseTable_PostNavigator implements JsonSerializable {
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
					'options' => CB2_Forms::period_status_type_options(),
				),
				array(
					'name' => __( '<a href="admin.php?page=cb2-periodstatustypes">add new</a>', 'commons-booking-2' ),
					'id' => 'exceptions',
					'type' => 'title',
				),
			),
		);
	}

  static function database_table_name() { return self::$database_table; }

  static function database_table_schema( $prefix ) {
		$id_field = CB2_Database::id_field( __class__ );

		return array(
			'name'    => self::$database_table,
			'columns' => array(
				// TYPE, (SIZE), CB2_UNSIGNED, NOT NULL, CB2_AUTO_INCREMENT, DEFAULT, COMMENT
				$id_field     => array( CB2_INT,     (11),   CB2_UNSIGNED, CB2_NOT_NULL, CB2_AUTO_INCREMENT ),
				'name'        => array( CB2_VARCHAR, (1024), NULL,     CB2_NOT_NULL ),
				'description' => array( CB2_VARCHAR, (1024), NULL,     NULL,     NULL, NULL ),
				'flags'       => array( CB2_BIT,     (32),   NULL,     CB2_NOT_NULL, NULL, 0 ),
				'colour'      => array( CB2_VARCHAR, (256),  NULL,     NULL,     NULL, NULL ),
				'opacity'     => array( CB2_TINYINT, (1),    NULL,     CB2_NOT_NULL, NULL, 100 ),
				'priority'    => array( CB2_INT,     (11),   NULL,     CB2_NOT_NULL, NULL, 1 ),
			),
			'primary key' => array( $id_field ),
			'triggers'    => array(
				'BEFORE UPDATE' => array( "
					if old.$id_field <= 6 then
						if not old.$id_field = new.$id_field then
							signal sqlstate '45000' set message_text = 'system period status type IDs cannot be updated';
						end if;
						if not old.name = new.name then
							signal sqlstate '45001' set message_text = 'system period status type names cannot be updated';
						end if;
					end if;"
				),
				'BEFORE DELETE' => array( "
					if old.$id_field <= 6 then
						signal sqlstate '45000' set message_text = 'system period status types cannot be removed';
					end if;"
				),
			),
		);
	}

	static function database_data() {
		return array(
			array( '1', 'available', '', '7', '#', '100', '2' ),
			array( '2', 'booked', NULL, '0', '#dd3333', '50', '6' ),
			array( '3', 'closed', 'rrr', '2', '#f7f7f7', '50', '3' ),
			array( '4', 'open', '', '7', '#456', '100', '1' ),
			array( '5', 'repair', NULL, '0', '#999', '100', '4' ),
			array( '6', 'holiday', ' ', '2', '#a7a7a7', '100', '5' ),
		);
	}

  static function database_views() {
		return array(
			'cb2_view_periodstatustype_posts' => "select (`p`.`period_status_type_id` + `pt`.`ID_base`) AS `ID`,1 AS `post_author`,'2018-01-01' AS `post_date`,'2018-01-01' AS `post_date_gmt`,`p`.`description` AS `post_content`,`p`.`name` AS `post_title`,'description' AS `post_excerpt`,'publish' AS `post_status`,'closed' AS `comment_status`,'closed' AS `ping_status`,'' AS `post_password`,`p`.`name` AS `post_name`,'' AS `to_ping`,'' AS `pinged`,'2018-01-01' AS `post_modified`,'2018-01-01' AS `post_modified_gmt`,'' AS `post_content_filtered`,0 AS `post_parent`,'' AS `guid`,0 AS `menu_order`,'periodstatustype' AS `post_type`,'' AS `post_mime_type`,0 AS `comment_count`,`p`.`period_status_type_id` AS `period_status_type_id`,`p`.`name` AS `name`,`p`.`description` AS `description`,`p`.`flags` AS `flags`,`p`.`colour` AS `colour`,`p`.`opacity` AS `opacity`,`p`.`priority` AS `priority`,(`p`.`period_status_type_id` <= 6) AS `system` from (`wp_cb2_period_status_types` `p` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = 'periodstatustype')))",
			'cb2_view_periodstatustypemeta'   => "select ((`pst`.`period_status_type_id` * 10) + `pt`.`ID_base`) AS `meta_id`,`pst`.`ID` AS `post_id`,`pst`.`ID` AS `periodstatustype_id`,'flags' AS `meta_key`,cast(`pst`.`flags` as unsigned) AS `meta_value` from (`wp_cb2_view_periodstatustype_posts` `pst` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = `pst`.`post_type`))) union all select (((`pst`.`period_status_type_id` * 10) + `pt`.`ID_base`) + 1) AS `meta_id`,`pst`.`ID` AS `post_id`,`pst`.`ID` AS `periodstatustype_id`,'colour' AS `meta_key`,`pst`.`colour` AS `meta_value` from (`wp_cb2_view_periodstatustype_posts` `pst` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = `pst`.`post_type`))) union all select (((`pst`.`period_status_type_id` * 10) + `pt`.`ID_base`) + 2) AS `meta_id`,`pst`.`ID` AS `post_id`,`pst`.`ID` AS `periodstatustype_id`,'opacity' AS `meta_key`,`pst`.`opacity` AS `meta_value` from (`wp_cb2_view_periodstatustype_posts` `pst` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = `pst`.`post_type`))) union all select (((`pst`.`period_status_type_id` * 10) + `pt`.`ID_base`) + 3) AS `meta_id`,`pst`.`ID` AS `post_id`,`pst`.`ID` AS `periodstatustype_id`,'priority' AS `meta_key`,`pst`.`priority` AS `meta_value` from (`wp_cb2_view_periodstatustype_posts` `pst` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = `pst`.`post_type`)))",
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
		CB2_Query::assign_all_parameters( $this, func_get_args(), __class__ );

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
      $Class = 'CB2_UserPeriodStatusType';
      $id    = CB2_PostNavigator::id_from_ID_with_post_type( $ID, CB2_PeriodStatusType::$static_post_type );
      // Hardcoded system status types
      switch ( $id ) {
        case CB2_PeriodStatusType_Available::$id: $Class = 'CB2_PeriodStatusType_Available'; break;
        case CB2_PeriodStatusType_Booked::$id:    $Class = 'CB2_PeriodStatusType_Booked';    break;
        case CB2_PeriodStatusType_Closed::$id:    $Class = 'CB2_PeriodStatusType_Closed';    break;
        case CB2_PeriodStatusType_Open::$id:      $Class = 'CB2_PeriodStatusType_Open';      break;
        case CB2_PeriodStatusType_Repair::$id:    $Class = 'CB2_PeriodStatusType_Repair';    break;
        case CB2_PeriodStatusType_Holiday::$id:   $Class = 'CB2_PeriodStatusType_Holiday';   break;
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


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_UserPeriodStatusType extends CB2_PeriodStatusType {
	public function __construct(...$args) {
		call_user_func_array( array( get_parent_class(), '__construct' ), $args );
	}
}

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_SystemPeriodStatusType extends CB2_PeriodStatusType {
	// These can be instanciated in 2 ways:
	//   load from DB: from a wp_post, with all its arguments
	//   save to DB:   from code to indicate the period status type to save
	// period_status_type_id is FIXED in the DB
	// so they are referred to by id
	protected function __construct(...$args) {
		if ( ! count( $args ) )
			throw new Exception( 'CB2_SystemPeriodStatusType requires an ID' );
		call_user_func_array( array( get_parent_class(), '__construct' ), $args );
		if ( $this->ID == CB2_CREATE_NEW )
			throw new Exception( 'CB2_SystemPeriodStatusType cannot be CB2_CREATE_NEW' );
		if ( $this->id() != $this::$id )
			throw new Exception( 'CB2_SystemPeriodStatusType id is incorrect' );
		$this->system = TRUE;
	}
}


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_PeriodStatusType_Available extends CB2_SystemPeriodStatusType {
	static $id = 1;
	public function __construct(...$args) {
		if ( ! count( $args ) ) {
			// No ID sent through: created for save, without arguments
			$ID   = CB2_PostNavigator::ID_from_id_post_type( self::$id, CB2_PeriodStatusType::$static_post_type );
			$args = array( $ID );
		}
		call_user_func_array( array( get_parent_class(), '__construct' ), $args );
	}
}

class CB2_PeriodStatusType_Booked    extends CB2_SystemPeriodStatusType {
	static $id = 2;
	public function __construct(...$args) {
		if ( ! count( $args ) ) {
			// No ID sent through: created for save, without arguments
			$ID   = CB2_PostNavigator::ID_from_id_post_type( self::$id, CB2_PeriodStatusType::$static_post_type );
			$args = array( $ID );
		}
		call_user_func_array( array( get_parent_class(), '__construct' ), $args );
	}
}

class CB2_PeriodStatusType_Closed    extends CB2_SystemPeriodStatusType {
	static $id = 3;
	public function __construct(...$args) {
		if ( ! count( $args ) ) {
			// No ID sent through: created for save, without arguments
			$ID   = CB2_PostNavigator::ID_from_id_post_type( self::$id, CB2_PeriodStatusType::$static_post_type );
			$args = array( $ID );
		}
		call_user_func_array( array( get_parent_class(), '__construct' ), $args );
	}
}

class CB2_PeriodStatusType_Open      extends CB2_SystemPeriodStatusType {
	static $id = 4;
	public function __construct(...$args) {
		if ( ! count( $args ) ) {
			// No ID sent through: created for save, without arguments
			$ID   = CB2_PostNavigator::ID_from_id_post_type( self::$id, CB2_PeriodStatusType::$static_post_type );
			$args = array( $ID );
		}
		call_user_func_array( array( get_parent_class(), '__construct' ), $args );
	}
}

class CB2_PeriodStatusType_Repair    extends CB2_SystemPeriodStatusType {
	static $id = 5;
	public function __construct(...$args) {
		if ( ! count( $args ) ) {
			// No ID sent through: created for save, without arguments
			$ID   = CB2_PostNavigator::ID_from_id_post_type( self::$id, CB2_PeriodStatusType::$static_post_type );
			$args = array( $ID );
		}
		call_user_func_array( array( get_parent_class(), '__construct' ), $args );
	}
}

class CB2_PeriodStatusType_Holiday   extends CB2_SystemPeriodStatusType {
	static $id = 6;
	public function __construct(...$args) {
		if ( ! count( $args ) ) {
			// No ID sent through: created for save, without arguments
			$ID   = CB2_PostNavigator::ID_from_id_post_type( self::$id, CB2_PeriodStatusType::$static_post_type );
			$args = array( $ID );
		}
		call_user_func_array( array( get_parent_class(), '__construct' ), $args );
	}
}
