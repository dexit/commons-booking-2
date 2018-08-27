<?php
class CB_PeriodEntity extends CB_PostNavigator implements JsonSerializable {
	public static $all = array();

	static function &factory_from_wp_post( $post ) {
		// The WP_Post may have all its metadata loaded already
		// as the wordpress system adds all fields to the WP_Post dynamically
		CB_Query::get_metadata_assign( $post );
		if ( ! $post->period_status_type_ID ) throw new Exception( 'CB_PeriodEntity requires period_status_type_ID' );
		if ( ! $post->period_group_ID )       throw new Exception( 'CB_PeriodEntity requires period_group_ID' );

		$period_status_type = CB_Query::get_post_type( 'periodstatustype', $post->period_status_type_ID );
		$period_group       = CB_Query::get_post_type( 'periodgroup',      $post->period_group_ID );

		$object = self::factory(
			$post->ID,
			$post->post_title,
			$period_group,
			$period_status_type
		);

		CB_Query::copy_all_properties( $post, $object );

		return $object;
	}

  static function &factory(
		$ID,
    $name,
		$period_group,
		$period_status_type
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
		$timeframe_id,
    $name,
		$period_group,              // CB_PeriodGroup {[CB_Period, ...]}
    $period_status_type         // CB_PeriodStatusType
  ) {
		$this->ID                 = $timeframe_id;
		$this->id                 = $timeframe_id;
		$this->timeframe_id       = $timeframe_id;
    $this->name               = $name;
		$this->period_group       = $period_group;
		$this->period_status_type = $period_status_type;
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

  function post_type() {return self::$static_post_type;}

  public function __construct(
		$timeframe_id,
		$name,
		$period_group,              // CB_PeriodGroup {[CB_Period, ...]}
    $period_status_type         // CB_PeriodStatusType
  ) {
		parent::__construct(
			$timeframe_id,
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

  function post_type() {return self::$static_post_type;}

	static function &factory_from_wp_post( $post ) {
		// The WP_Post may have all its metadata loaded already
		// as the wordpress system adds all fields to the WP_Post dynamically
		CB_Query::get_metadata_assign( $post );
		if ( ! $post->period_status_type_ID ) throw new Exception( 'CB_PeriodEntity requires period_status_type_ID' );
		if ( ! $post->period_group_ID )       throw new Exception( 'CB_PeriodEntity requires period_group_ID' );
		if ( ! $post->location_ID )           throw new Exception( 'CB_PeriodEntity requires location_ID' );

		$period_status_type = CB_Query::get_post_type( 'periodstatustype', $post->period_status_type_ID );
		$period_group       = CB_Query::get_post_type( 'periodgroup',      $post->period_group_ID );
		$location           = CB_Query::get_post_type( 'location',         $post->location_ID );

		$object = self::factory(
			$post->ID,
			$post->post_title,
			$period_group,
			$period_status_type,

			$location
		);

		CB_Query::copy_all_properties( $post, $object );

		return $object;
	}

  static function &factory(
		$ID,
    $name,
		$period_group,
		$period_status_type,

		$location
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
		$timeframe_id,
		$name,
		$period_group,              // CB_PeriodGroup {[CB_Period, ...]}
    $period_status_type,        // CB_PeriodStatusType

    $location                   // CB_Location
  ) {
		parent::__construct(
			$timeframe_id,
			$name,
			$period_group,
			$period_status_type
    );

		$this->location = $location;
    $this->location->add_period( $this );
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

  function post_type() {return self::$static_post_type;}

	static function &factory_from_wp_post( $post ) {
		// The WP_Post may have all its metadata loaded already
		// as the wordpress system adds all fields to the WP_Post dynamically
		CB_Query::get_metadata_assign( $post );
		if ( ! $post->period_status_type_ID ) throw new Exception( 'CB_PeriodEntity requires period_status_type_ID' );
		if ( ! $post->period_group_ID )       throw new Exception( 'CB_PeriodEntity requires period_group_ID' );
		if ( ! $post->location_ID )           throw new Exception( 'CB_PeriodEntity requires location_ID' );
		if ( ! $post->item_ID )               throw new Exception( 'CB_PeriodEntity requires item_ID' );

		$period_status_type = CB_Query::get_post_type( 'periodstatustype', $post->period_status_type_ID );
		$period_group       = CB_Query::get_post_type( 'periodgroup',      $post->period_group_ID );
		$location           = CB_Query::get_post_type( 'location',         $post->location_ID );
		$item               = CB_Query::get_post_type( 'item',             $post->item_ID );

		$object = self::factory(
			$post->ID,
			$post->post_title,
			$period_group,
			$period_status_type,

			$location,
			$item
		);

		CB_Query::copy_all_properties( $post, $object );

		return $object;
	}

  static function &factory(
		$ID,
    $name,
		$period_group,
		$period_status_type,

		$location,
		$item
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
		$timeframe_id,
		$name,
		$period_group,              // CB_PeriodGroup {[CB_Period, ...]}
    $period_status_type,        // CB_PeriodStatusType

    $location,                  // CB_Location
    $item                       // CB_Item
  ) {
		parent::__construct(
			$timeframe_id,
			$name,
			$period_group,
			$period_status_type,
			$location,
			$item
    );

		$this->location = $location;
    $this->location->add_period( $this );
    array_push( $this->posts, $this->location );
		$this->item = $item;
    $this->item->add_period( $this );
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

  function post_type() {return self::$static_post_type;}

	static function &factory_from_wp_post( $post ) {
		// The WP_Post may have all its metadata loaded already
		// as the wordpress system adds all fields to the WP_Post dynamically
		CB_Query::get_metadata_assign( $post );
		if ( ! $post->period_status_type_ID ) throw new Exception( 'CB_PeriodEntity requires period_status_type_ID' );
		if ( ! $post->period_group_ID )       throw new Exception( 'CB_PeriodEntity requires period_group_ID' );
		if ( ! $post->location_ID )           throw new Exception( 'CB_PeriodEntity requires location_ID' );
		if ( ! $post->item_ID )               throw new Exception( 'CB_PeriodEntity requires item_ID' );
		if ( ! $post->user_ID )               throw new Exception( 'CB_PeriodEntity requires user_ID' );

		$period_status_type = CB_Query::get_post_type( 'periodstatustype', $post->period_status_type_ID );
		$period_group       = CB_Query::get_post_type( 'periodgroup',      $post->period_group_ID );
		$location           = CB_Query::get_post_type( 'location',         $post->location_ID );
		$item               = CB_Query::get_post_type( 'item',             $post->item_ID );
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

		CB_Query::copy_all_properties( $post, $object );

		return $object;
	}

  static function &factory(
		$ID,
    $name,
		$period_group,
		$period_status_type,

		$location,
		$item,
		$user
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
		$timeframe_id,
		$name,
		$period_group,              // CB_PeriodGroup {[CB_Period, ...]}
    $period_status_type,        // CB_PeriodStatusType

    $location,                  // CB_Location
    $item,                      // CB_Item
    $user                       // CB_User
  ) {
		parent::__construct(
			$timeframe_id,
			$name,
			$period_group,
			$period_status_type,
			$location,
			$item,
			$user
    );

		$this->location = $location;
    $this->location->add_period( $this );
    array_push( $this->posts, $this->location );
		$this->item = $item;
    $this->item->add_period( $this );
    array_push( $this->posts, $this->item );
		$this->user = $item;
    $this->user->add_period( $this );
    array_push( $this->posts, $this->user );
  }
}
CB_Query::register_schema_type( 'CB_PeriodEntity_Timeframe_User' );

