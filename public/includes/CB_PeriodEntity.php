<?php
class CB_PeriodEntity extends CB_PostNavigator implements JsonSerializable {
	public static $all = array();

	static function metaboxes() {
		$metaboxes = CB_Period::metaboxes();
		array_push( $metaboxes,
			array(
				'title' => __( 'Status', 'commons-booking-2' ),
				'context' => 'side',
				'show_names' => TRUE,
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
						'options' => array(
							'template' => 'available',
						),
					),
				),
			)
		);
		array_push( $metaboxes, CB_PeriodStatusType::selector_metabox() );
		array_push( $metaboxes, CB_Period::selector_metabox() );
		array_push( $metaboxes, CB_PeriodGroup::selector_metabox() );

		return $metaboxes;
	}

	static function metabox_calendar_options_cb( $field ) {
		global $post;

		$options = array( 'template' => 'available' );
		if ( $post ) $options[ 'query' ] = array(
			'meta_query' => array(
				'period_entity' => array(
					'meta_key'   => 'period_entity_ID',
					'meta_value' => $post->ID,
				),
			),
		);

		return $options;
	}

	static function &factory_from_wp_post( $post ) {
		// The WP_Post may have all its metadata loaded already
		// as the wordpress system adds all fields to the WP_Post dynamically
		if ( $post->ID ) CB_Query::get_metadata_assign( $post );
		if ( ! $post->period_status_type_ID ) throw new Exception( 'CB_PeriodEntity requires period_status_type_ID' );
		if ( ! $post->period_group_ID )       throw new Exception( 'CB_PeriodEntity requires period_group_ID' );

		$period_status_type = CB_Query::get_post_with_type( CB_PeriodStatusType::$static_post_type, $post->period_status_type_ID );
		//  period_group_ID can == <create new>
		$period_group = ( is_numeric( $post->period_group_ID ) ? CB_Query::get_post_with_type( CB_PeriodGroup::$static_post_type, $post->period_group_ID ) : NULL );

		$object = self::factory(
			$post->ID,
			$post->post_title,
			$period_group,
			$period_status_type
		);

		CB_Query::copy_all_wp_post_properties( $post, $object );

		return $object;
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
		if ( ! is_null( $ID ) && isset( self::$all[$ID] ) ) {
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

	function summary() {
		$html      = '<b>' . $this->post_title . '</b>';
		$html     .= $this->summary_actions();
		$html     .= '<br/>';
		$html     .= $this->period_group->summary_periods();
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

  function post_type() {return self::$static_post_type;}

	static function &factory_from_wp_post( $post ) {
		// The WP_Post may have all its metadata loaded already
		// as the wordpress system adds all fields to the WP_Post dynamically
		if ( $post->ID ) CB_Query::get_metadata_assign( $post );
		if ( ! $post->period_status_type_ID ) throw new Exception( 'CB_PeriodEntity requires period_status_type_ID' );
		if ( ! $post->period_group_ID )       throw new Exception( 'CB_PeriodEntity requires period_group_ID' );

		$period_status_type = CB_Query::get_post_with_type( CB_PeriodStatusType::$static_post_type, $post->period_status_type_ID );
		$period_group = ( is_numeric( $post->period_group_ID ) ? CB_Query::get_post_with_type( CB_PeriodGroup::$static_post_type, $post->period_group_ID ) : NULL );

		$object = self::factory(
			$post->ID,
			$post->post_title,
			$period_group,
			$period_status_type
		);

		CB_Query::copy_all_wp_post_properties( $post, $object );

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
		if ( ! is_null( $ID ) && isset( self::$all[$ID] ) ) {
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


  function post_type() {return self::$static_post_type;}

	static function &factory_from_wp_post( $post ) {
		// The WP_Post may have all its metadata loaded already
		// as the wordpress system adds all fields to the WP_Post dynamically
		if ( $post->ID ) CB_Query::get_metadata_assign( $post );
		if ( ! $post->period_status_type_ID ) throw new Exception( 'CB_PeriodEntity requires period_status_type_ID' );
		if ( ! $post->period_group_ID )       throw new Exception( 'CB_PeriodEntity requires period_group_ID' );
		if ( ! $post->location_ID )           throw new Exception( 'CB_PeriodEntity requires location_ID' );

		$period_status_type = CB_Query::get_post_with_type( CB_PeriodStatusType::$static_post_type, $post->period_status_type_ID );
		$period_group = ( is_numeric( $post->period_group_ID ) ? CB_Query::get_post_with_type( CB_PeriodGroup::$static_post_type, $post->period_group_ID ) : NULL );
		$location           = CB_Query::get_post_with_type( CB_Location::$static_post_type, $post->location_ID );

		$object = self::factory(
			$post->ID,
			$post->post_title,
			$period_group,
			$period_status_type,

			$location
		);

		CB_Query::copy_all_wp_post_properties( $post, $object );

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
		if ( ! is_null( $ID ) && isset( self::$all[$ID] ) ) {
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

  function post_type() {return self::$static_post_type;}

	static function &factory_from_wp_post( $post ) {
		// The WP_Post may have all its metadata loaded already
		// as the wordpress system adds all fields to the WP_Post dynamically
		if ( $post->ID ) CB_Query::get_metadata_assign( $post );
		if ( ! $post->period_status_type_ID ) throw new Exception( 'CB_PeriodEntity requires period_status_type_ID' );
		if ( ! $post->period_group_ID )       throw new Exception( 'CB_PeriodEntity requires period_group_ID' );
		if ( ! $post->location_ID )           throw new Exception( 'CB_PeriodEntity requires location_ID' );
		if ( ! $post->item_ID )               throw new Exception( 'CB_PeriodEntity requires item_ID' );

		$period_status_type = CB_Query::get_post_with_type( CB_PeriodStatusType::$static_post_type, $post->period_status_type_ID );
		$period_group = ( is_numeric( $post->period_group_ID ) ? CB_Query::get_post_with_type( CB_PeriodGroup::$static_post_type, $post->period_group_ID ) : NULL );
		$location           = CB_Query::get_post_with_type( CB_Location::$static_post_type,         $post->location_ID );
		$item               = CB_Query::get_post_with_type( CB_Item::$static_post_type,             $post->item_ID );

		$object = self::factory(
			$post->ID,
			$post->post_title,
			$period_group,
			$period_status_type,

			$location,
			$item
		);

		CB_Query::copy_all_wp_post_properties( $post, $object );

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
		if ( ! is_null( $ID ) && isset( self::$all[$ID] ) ) {
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

  function post_type() {return self::$static_post_type;}

	static function &factory_from_wp_post( $post ) {
		// The WP_Post may have all its metadata loaded already
		// as the wordpress system adds all fields to the WP_Post dynamically
		if ( $post->ID ) CB_Query::get_metadata_assign( $post );
		if ( ! $post->period_status_type_ID ) throw new Exception( 'CB_PeriodEntity requires period_status_type_ID' );
		if ( ! $post->period_group_ID )       throw new Exception( 'CB_PeriodEntity requires period_group_ID' );
		if ( ! $post->location_ID )           throw new Exception( 'CB_PeriodEntity requires location_ID' );
		if ( ! $post->item_ID )               throw new Exception( 'CB_PeriodEntity requires item_ID' );
		if ( ! $post->user_ID )               throw new Exception( 'CB_PeriodEntity requires user_ID' );

		$period_status_type = CB_Query::get_post_with_type( CB_PeriodStatusType::$static_post_type, $post->period_status_type_ID );
		$period_group = ( is_numeric( $post->period_group_ID ) ? CB_Query::get_post_with_type( CB_PeriodGroup::$static_post_type, $post->period_group_ID ) : NULL );
		$location           = CB_Query::get_post_with_type( CB_Location::$static_post_type,         $post->location_ID );
		$item               = CB_Query::get_post_with_type( CB_Item::$static_post_type,             $post->item_ID );
		$user               = CB_Query::get_user( $post->user_ID );

		$object = self::factory(
			$post->ID,
			$post->post_title,
			$period_group,
			$period_status_type,

			$location,
			$item,
			$user
		);

		CB_Query::copy_all_wp_post_properties( $post, $object );

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
		if ( ! is_null( $ID ) && isset( self::$all[$ID] ) ) {
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

