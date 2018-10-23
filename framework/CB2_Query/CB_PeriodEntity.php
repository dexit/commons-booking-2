<?php
abstract class CB_PeriodEntity extends CB_DatabaseTable_PostNavigator implements JsonSerializable {
	public static $all = array();

  function database_table_schema_root( String $table_name, String $primary_id_column, Array $columns = array(), Array $constraints = array() ) {
		$base_columns = array(
			$primary_id_column      => array( BIGINT, (20), UNSIGNED, NOT_NULL, NULL, AUTO_INCREMENT ),
			'period_group_id'       => array( INT,    (11), UNSIGNED, NOT_NULL ),
			'period_status_type_id' => array( INT,    (11), UNSIGNED, NOT_NULL ),
			'enabled'               => array( BIT,    (1),  NULL,     NOT_NULL, NULL, NULL, 1 ),
		);
		$columns = array_merge( $base_columns, $columns );

		$base_constraints = array(
			'period_group_id'       => array( 'cb2_period_groups',       'period_group_id' ),
			'period_status_type_id' => array( 'cb2_period_status_types', 'period_status_type_id' ),
		);
		$constraints = array_merge( $base_constraints, $constraints );

		return array(
			'name'        => $table_name,
			'columns'     => $columns,
			'primary key' => array( $primary_id_column ),
			'foreign key constraints' => $constraints,
		);
  }

	static function metaboxes() {
		$metaboxes = CB_Period::metaboxes();
		array_push( $metaboxes,
			array(
				// TODO: link this in to the Publish meta-box status instead
				'title' => __( 'Enabled', 'commons-booking-2' ),
				'context' => 'side',
				'show_names' => FALSE,
				'closed'     => TRUE,
				'fields' => array(
					array(
						'name' => __( 'Enabled', 'commons-booking-2' ),
						'id' => 'enabled',
						'type' => 'checkbox',
						'default' => 1,
					),
				),
			)
		);
		array_push( $metaboxes,
			array(
				'title'      => __( 'Calendar view', 'commons-booking-2' ),
				'context'    => 'normal',
				'show_names' => FALSE,
				'show_on_cb' => array( 'CB_Period', 'metabox_show_when_published' ),
				'fields' => array(
					array(
						'name'    => __( 'Timeframe', 'commons-booking-2' ),
						'id'      => 'calendar',
						'type'    => 'calendar',
						'options_cb' => array( 'CB_PeriodEntity', 'metabox_calendar_options_cb' ),
					),
				),
			)
		);
		array_push( $metaboxes, CB_PeriodStatusType::selector_metabox() );
		array_push( $metaboxes, CB_Period::selector_metabox() );

		return $metaboxes;
	}

	static function metabox_calendar_options_cb( $field ) {
		global $post;

		$options = array( 'template' => 'available' );
		if ( $post ) $options[ 'query' ] = array(
			'meta_query' => array(
				'period_entity_ID_clause' => array(
					'key'     => 'period_entity_ID',
					'value'   => array( $post->ID, 0 ),
					'compare' => 'IN',
				),
			),
		);

		return $options;
	}

	static function &factory_from_properties( &$properties, &$instance_container = NULL ) {
		$object = self::factory(
			$properties['ID'],
			$properties['post_title'],
			CB_PostNavigator::get_or_create_new( $properties, 'period_group_ID',       $instance_container ),
			CB_PostNavigator::get_or_create_new( $properties, 'period_status_type_ID', $instance_container )
		);

		self::copy_all_wp_post_properties( $properties, $object );

		return $object;
	}

  protected static function factory_from_perioditem(
		CB_PeriodItem $perioditem,
		$new_periodentity_Class,
		$new_period_status_type_Class,
		$name = NULL,

		$copy_period_group    = TRUE,
		CB_Location $location = NULL,
		CB_Item     $item     = NULL,
		CB_User     $user     = NULL
	) {
		$period_entity = $perioditem->period_entity;

		// PeriodGroup, Period and refrences
		$period_group  = NULL;
		if ( $copy_period_group ) {
			// We do not want to clone the period_group
			// only the period item *instance*
			// TODO: contiguous bookings
			$datetime_now = new DateTime();
			$period = new CB_Period(
				CB2_CREATE_NEW,
				$perioditem->name,
				$perioditem->datetime_period_item_start, // datetime_part_period_start
				$perioditem->datetime_period_item_end,   // datetime_part_period_end
				$datetime_now                            // datetime_from
			);
			$period_group = new CB_PeriodGroup(
				CB2_CREATE_NEW,
				$perioditem->name,
				array( $period ) // periods
			);
		} else {
			// Linking means that:
			// changing the original availability period will change the booking as well!
			$period_group = $period_entity->period_group;
		}

		// new PeriodEntity
		$new_period_entity = $new_periodentity_Class::factory(
			CB2_CREATE_NEW,
			$name,
			$period_group,
			new $new_period_status_type_Class(),

			( $location ? $location : $period_entity->location ),
			( $item     ? $item     : $period_entity->item ),
			( $user     ? $user     : $period_entity->user )
		);

		return $new_period_entity;
  }

  static function &factory(
		$ID,
    $name,
		$period_group,       // Can be NULL, indicating <create new>
		$period_status_type,

		//here to prevent static inheritance warning
		$location = NULL,
		$item     = NULL,
		$user     = NULL
	) {
    // Design Patterns: Factory Singleton with Multiton
		if ( $ID && isset( self::$all[$ID] ) ) {
			$object = self::$all[$ID];
    } else {
			$reflection = new ReflectionClass( __class__ );
			$object     = $reflection->newInstanceArgs( func_get_args() );
    }

    return $object;
  }

  static function factory_subclass(
		$ID,
		$name,
		$period_group,       // CB_PeriodGroup
		$period_status_type, // CB_PeriodStatusType

    $location = NULL,   // CB_Location
    $item     = NULL,   // CB_Item
    $user     = NULL    // CB_User
  ) {
		// provides appropriate sub-class based on final object parameters
		$object = NULL;
		if      ( $user )     $object = CB_PeriodEntity_Timeframe_User::factory(
				$ID,
				$name,
				$period_group,
				$period_status_type,
				$location,
				$item,
				$user
			);
		else if ( $item )     $object = CB_PeriodEntity_Timeframe::factory(
				$ID,
				$name,
				$period_group,
				$period_status_type,
				$location,
				$item,
				$user
			);
		else if ( $location ) $object = CB_PeriodEntity_Location::factory(
				$ID,
				$name,
				$period_group,
				$period_status_type,
				$location,
				$item,
				$user
			);
		else                  $object = CB_PeriodEntity_Global::factory(
				$ID,
				$name,
				$period_group,
				$period_status_type,
				$location,
				$item,
				$user
			);

		return $object;
  }

  public function __construct(
		$ID,
    $name,
		$period_group,              // CB_PeriodGroup {[CB_Period, ...]}
    $period_status_type         // CB_PeriodStatusType
  ) {
		parent::__construct();

		$this->ID                 = $ID;
    $this->name               = $name;
		$this->period_group       = $period_group;
		$this->period_status_type = $period_status_type;
  }

  function add_actions( &$actions, $post ) {
		if ( isset( $actions['inline hide-if-no-js'] ) )
			$actions['inline hide-if-no-js'] = str_replace( ' class="', ' class="cb2-todo ', $actions['inline hide-if-no-js'] );
	}

	function manage_columns( $columns ) {
		if ( ! $this->period_group )
			throw new Exception( '[' . get_class( $this ) . "] [$this->ID] has no period_group" );
		return $this->period_group->manage_columns( $columns );
	}

	function custom_columns( $column ) {
		if ( ! $this->period_group )
			throw new Exception( '[' . get_class( $this ) . "] [$this->ID] has no period_group" );
		return $this->period_group->custom_columns( $column );
	}

  function classes() {
    $classes  = '';
    $classes .= $this->period_status_type->classes();
    $classes .= ' cb2-' . $this->post_type();
    return $classes;
  }

	function summary() {
		$classes = $this->classes();
		$html  = "<div class='$classes'>";
		$html .= '<b>' . $this->post_title . '</b>';
		$html .= $this->summary_actions();
		$html .= '<br/>';
		$html .= $this->period_group->summary_periods();
		$html .= "</div>";
		return $html;
	}

	function summary_actions() {
		$post_type = $this->post_type();
		$page      = 'cb-post-edit';
		$action    = 'edit';
		$edit_link = "?page=$page&post=$this->ID&post_type=$post_type&action=$action";
		return " <a href='$edit_link'>edit</a>";
	}

	function jsonSerialize() {
    return $this;
  }
}

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_PeriodEntity_Global extends CB_PeriodEntity {
  public static $database_table = 'cb2_global_period_groups';
  static $static_post_type      = 'periodent-global';
	static function metaboxes() {
		return parent::metaboxes();
	}

  function database_table_name() { return self::$database_table; }

  function database_table_schema() {
		return $this->database_table_schema_root( self::$database_table, 'global_period_group_id' );
  }

  function post_type() {return self::$static_post_type;}

	static function &factory_from_properties( &$properties, &$instance_container = NULL ) {
		$object = self::factory(
			$properties['ID'],
			$properties['post_title'],
			CB_PostNavigator::get_or_create_new( $properties, 'period_group_ID',       $instance_container ),
			CB_PostNavigator::get_or_create_new( $properties, 'period_status_type_ID', $instance_container )
		);

		self::copy_all_wp_post_properties( $properties, $object );

		return $object;
	}

  static function &factory(
		$ID,
    $name,
		$period_group,
		$period_status_type,

		//here to prevent static inheritance warning
		$location = NULL,
		$item     = NULL,
		$user     = NULL
	) {
    // Design Patterns: Factory Singleton with Multiton
		if ( $ID && isset( self::$all[$ID] ) ) {
			$object = self::$all[$ID];
    } else {
			$reflection = new ReflectionClass( __class__ );
			$object     = $reflection->newInstanceArgs( func_get_args() );
    }

    return $object;
  }

  public function __construct(
		$ID,
		$name,
		$period_group,              // CB_PeriodGroup {[CB_Period, ...]}
    $period_status_type         // CB_PeriodStatusType
  ) {
		parent::__construct(
			$ID,
			$name,
			$period_group,
			$period_status_type
    );
  }
}
CB_Query::register_schema_type( 'CB_PeriodEntity_Global' );

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_PeriodEntity_Location extends CB_PeriodEntity {
  public static $database_table = 'cb2_location_period_groups';
  static $static_post_type      = 'periodent-location';
	static function metaboxes() {
		$metaboxes = parent::metaboxes();
		array_unshift( $metaboxes, CB_Location::selector_metabox() );
		// array_push(    $metaboxes, CB_Location::summary_metabox() );
		return $metaboxes;
	}


  function database_table_name() { return self::$database_table; }

  function database_table_schema() {
		return $this->database_table_schema_root( self::$database_table, 'location_period_group_id', array(
			'location_ID' => array( BIGINT, (20), UNSIGNED, NOT_NULL ),
		), array(
			'location_ID' => array( 'posts', 'ID' ),
		) );
  }

  function post_type() {return self::$static_post_type;}

	static function &factory_from_properties( &$properties, &$instance_container = NULL ) {
		$object = self::factory(
			$properties['ID'],
			$properties['post_title'],
			CB_PostNavigator::get_or_create_new( $properties, 'period_group_ID',       $instance_container ),
			CB_PostNavigator::get_or_create_new( $properties, 'period_status_type_ID', $instance_container ),

			CB_PostNavigator::get_or_create_new( $properties, 'location_ID',           $instance_container )
		);

		self::copy_all_wp_post_properties( $properties, $object );

		return $object;
	}

  static function &factory(
		$ID,
		$name,
		$period_group,
		$period_status_type,
		$location = NULL,
		$item     = NULL,
		$user     = NULL
  ) {
    // Design Patterns: Factory Singleton with Multiton
		if ( $ID && isset( self::$all[$ID] ) ) {
			$object = self::$all[$ID];
    } else {
			$reflection = new ReflectionClass( __class__ );
			$object     = $reflection->newInstanceArgs( func_get_args() );
    }

    return $object;
  }

  public function __construct(
		$ID,
		$name,
		$period_group,              // CB_PeriodGroup {[CB_Period, ...]}
    $period_status_type,        // CB_PeriodStatusType

    $location                   // CB_Location
  ) {
		parent::__construct(
			$ID,
			$name,
			$period_group,
			$period_status_type
    );

		$this->location = $location;
    array_push( $this->posts, $this->location );
  }
}
CB_Query::register_schema_type( 'CB_PeriodEntity_Location' );

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_PeriodEntity_Timeframe extends CB_PeriodEntity {
  public static $database_table = 'cb2_timeframe_period_groups';
  static $static_post_type      = 'periodent-timeframe';
	static function metaboxes() {
		$metaboxes = parent::metaboxes();
		array_unshift( $metaboxes, CB_Item::selector_metabox() );
		array_unshift( $metaboxes, CB_Location::selector_metabox() );
		return $metaboxes;
	}

  function database_table_name() { return self::$database_table; }

  function database_table_schema() {
		return $this->database_table_schema_root( self::$database_table, 'location_period_group_id', array(
			'location_ID' => array( BIGINT, (20), UNSIGNED, NOT_NULL ),
			'item_ID'     => array( BIGINT, (20), UNSIGNED, NOT_NULL ),
		), array(
			'location_ID' => array( 'posts', 'ID' ),
			'item_ID'     => array( 'posts', 'ID' ),
		) );
  }

  function post_type() {return self::$static_post_type;}

	static function &factory_from_properties( &$properties, &$instance_container = NULL ) {
		$object = self::factory(
			$properties['ID'],
			$properties['post_title'],
			CB_PostNavigator::get_or_create_new( $properties, 'period_group_ID',       $instance_container ),
			CB_PostNavigator::get_or_create_new( $properties, 'period_status_type_ID', $instance_container ),

			CB_PostNavigator::get_or_create_new( $properties, 'location_ID',           $instance_container ),
			CB_PostNavigator::get_or_create_new( $properties, 'item_ID',               $instance_container )
		);

		self::copy_all_wp_post_properties( $properties, $object );

		return $object;
	}

  static function &factory(
		$ID,
		$name,
		$period_group,
		$period_status_type,
		$location = NULL,
		$item     = NULL,
		$user     = NULL
  ) {
    // Design Patterns: Factory Singleton with Multiton
		if ( $ID && isset( self::$all[$ID] ) ) {
			$object = self::$all[$ID];
    } else {
			$reflection = new ReflectionClass( __class__ );
			$object     = $reflection->newInstanceArgs( func_get_args() );
    }

    return $object;
  }

  public function __construct(
		$ID,
		$name,
		$period_group,              // CB_PeriodGroup {[CB_Period, ...]}
    $period_status_type,        // CB_PeriodStatusType

    $location,                  // CB_Location
    $item                       // CB_Item
  ) {
		parent::__construct(
			$ID,
			$name,
			$period_group,
			$period_status_type,
			$location,
			$item
    );

		$this->location = $location;
    array_push( $this->posts, $this->location );
		$this->item = $item;
    array_push( $this->posts, $this->item );
  }
}
CB_Query::register_schema_type( 'CB_PeriodEntity_Timeframe' );

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_PeriodEntity_Timeframe_User extends CB_PeriodEntity {
  public static $database_table = 'cb2_timeframe_user_period_groups';
  static $static_post_type      = 'periodent-user';
	static function metaboxes() {
		$metaboxes = parent::metaboxes();
		array_unshift( $metaboxes, CB_User::selector_metabox() );
		array_unshift( $metaboxes, CB_Item::selector_metabox() );
		array_unshift( $metaboxes, CB_Location::selector_metabox() );
		return $metaboxes;
	}

  function database_table_name() { return self::$database_table; }

  function database_table_schema() {
		return $this->database_table_schema_root( self::$database_table, 'location_period_group_id', array(
			'location_ID' => array( BIGINT, (20), UNSIGNED, NOT_NULL ),
			'item_ID'     => array( BIGINT, (20), UNSIGNED, NOT_NULL ),
			'user_ID'     => array( BIGINT, (20), UNSIGNED, NOT_NULL ),
		), array(
			'location_ID' => array( 'posts', 'ID' ),
			'item_ID'     => array( 'posts', 'ID' ),
			'user_ID'     => array( 'users', 'ID' ),
		) );
  }

  function post_type() {return self::$static_post_type;}

	static function &factory_from_properties( &$properties, &$instance_container = NULL ) {
		$object = self::factory(
			$properties['ID'],
			$properties['post_title'],
			CB_PostNavigator::get_or_create_new( $properties, 'period_group_ID',       $instance_container ),
			CB_PostNavigator::get_or_create_new( $properties, 'period_status_type_ID', $instance_container ),

			CB_PostNavigator::get_or_create_new( $properties, 'location_ID',           $instance_container ),
			CB_PostNavigator::get_or_create_new( $properties, 'item_ID',               $instance_container ),
			CB_PostNavigator::get_or_create_new( $properties, 'user_ID',               $instance_container )
		);

		self::copy_all_wp_post_properties( $properties, $object );

		return $object;
	}

  static function &factory(
		$ID,
		$name,
		$period_group,
		$period_status_type,

		$location = NULL,
		$item     = NULL,
		$user     = NULL
  ) {
    // Design Patterns: Factory Singleton with Multiton
		if ( $ID && isset( self::$all[$ID] ) ) {
			$object = self::$all[$ID];
    } else {
			$reflection = new ReflectionClass( __class__ );
			$object     = $reflection->newInstanceArgs( func_get_args() );
    }

    return $object;
  }

  static function factory_booked_from_available_timeframe_item( CB_PeriodItem_Timeframe $perioditem_available, CB_User $user, $name = 'booking', $copy_period_group = TRUE ) {
		if ( ! $perioditem_available->period_entity->period_status_type instanceof CB_PeriodStatusType_Available )
			throw new Exception( 'Tried to morph into perioditem-user from non-available status [' . $perioditem_available->period_status_type->name . ']' );
		if ( ! $user )
			throw new Exception( 'Tried to morph into periodentity-user without user]' );

		return CB_PeriodEntity::factory_from_perioditem(
			$perioditem_available,
			CB_PeriodEntity_Timeframe_User,
			CB_PeriodStatusType_Booked,
			$name,

			$copy_period_group,
			NULL, // Copy location from $perioditem_available
			NULL, // Copy location from $perioditem_available
			$user
		);
  }

  public function __construct(
		$ID,
		$name,
		$period_group,              // CB_PeriodGroup {[CB_Period, ...]}
    $period_status_type,        // CB_PeriodStatusType

    $location,                  // CB_Location
    $item,                      // CB_Item
    $user                       // CB_User
  ) {
		parent::__construct(
			$ID,
			$name,
			$period_group,
			$period_status_type,
			$location,
			$item,
			$user
    );

		$this->location = $location;
    array_push( $this->posts, $this->location );
		$this->item = $item;
    array_push( $this->posts, $this->item );
		$this->user = $user;
    array_push( $this->posts, $this->user );
  }

  function add_actions( &$actions, $post ) {
		parent::add_actions( $actions, $post );
		$actions['contact'] = "<a class='cb2-todo' href='#'>" . __( 'Contact User' ) . '</a>';
	}

	function summary_actions() {
		$actions = parent::summary_actions();
		$view_link = get_permalink( $this->ID );
		$actions  .= " | <a href='$view_link'>view</a>";
		return $actions;
	}
}
CB_Query::register_schema_type( 'CB_PeriodEntity_Timeframe_User' );

