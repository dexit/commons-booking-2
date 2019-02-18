<?php
define( 'CB2_ANY_CLASS', NULL );


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_PeriodInteractionStrategy extends CB2_PostNavigator implements JsonSerializable {
	/* CB2_PeriodInteractionStrategy deals with
	 * the display of periods in the calendar
	 * given:
	 *   a UX display aim like show_single_item_pickup_return
	 *   parameters like an CB2_Item
	 *   an output callback like book
	 *
	 * Provides methods to examine relevance of perioditems
	 * e.g.
	 *   2 perioditems do not relate if they are for
	 *   different locations or items
	 * And period overlap adoption:
	 *   where the period adopts the overlap times with another period
	 *
	 * Usage:
	 *   new WP_Query(
	 *     ...
	 *     'period-interaction' => array(
	 *       'strategy' => 'CB2_SingleItemAvailability',
	 *       'item'     => $current_item,
	 *       'require_location_open' => TRUE,
	 *     )
	 *   );
	 */
	private $wp_query;

	function __construct( CB2_DateTime $startdate = NULL, CB2_DateTime $enddate = NULL, String $view_mode = NULL, Array $query = NULL ) {
		// Defaults
		if ( is_null( $startdate ) ) $startdate   = ( isset( $_GET['startdate'] ) ? new CB2_DateTime( $_GET['startdate'] ) : new CB2_DateTime() );
		if ( is_null( $enddate ) )   $enddate     = ( isset( $_GET['enddate'] )   ? new CB2_DateTime( $_GET['enddate'] )   : (clone $startdate)->add( new DateInterval('P1M') ) );
		if ( is_null( $view_mode ) ) $view_mode   = ( isset( $_GET['view_mode'] ) ? $_GET['view_mode'] : CB2_Week::$static_post_type );
		if ( is_null( $query ) )     $query       = array();

		// Properties
		$this->startdate = $startdate;
		$this->enddate   = $enddate;
		$this->view_mode = $view_mode;

		// Construct args
		if ( ! isset( $query['post_status'] ) )    $query['post_status'] = CB2_Post::$PUBLISH;
		if ( ! isset( $query['post_type'] ) )      $query['post_type']   = CB2_PeriodItem::$all_post_types;
		if ( ! isset( $query['posts_per_page'] ) ) $query['posts_per_page'] = -1;
		if ( ! isset( $query['order'] ) )          $query['order'] = 'ASC'; // defaults to post_date
		if ( ! isset( $query['date_query'] ) )     $query['date_query'] = array();
		if ( ! isset( $query['date_query']['after'] ) )  $query['date_query']['after']  = $this->startdate->format( CB2_Query::$datetime_format );
		if ( ! isset( $query['date_query']['before'] ) ) $query['date_query']['before'] = $this->enddate->format( CB2_Query::$datetime_format );
		// This sets which CB2_(ObjectType) is the resultant primary posts array
		// e.g. CB2_Weeks generated from the CB2_PeriodItem records
		if ( ! isset( $query['date_query']['compare'] ) ) $query['date_query']['compare'] = $this->view_mode;
		// Single period item blocking
		if ( ! isset( $query['meta_query']['blocked_clause'] ) ) $query['meta_query']['blocked_clause'] = array(
			'key'     => 'blocked',
			'value'   => '0',
		);
		// While preiod blocking
		if ( ! isset( $query['meta_query']['enabled_clause'] ) ) $query['meta_query']['enabled_clause'] = array(
			'key'     => 'enabled',
			'value'   => '1',
		);
		$filter = strtolower( get_class( $this ) ) . '_query';
		$query  = apply_filters( $filter, $query );

		// Retrieve all the posts immediately (like WP_Query)
		$this->query = $query;
		$this->query( $this->query );

		// Expose the private WP_Query in WP_DEBUG mode
		if ( WP_DEBUG ) $this->debug_wp_query = $this->wp_query;
	}

	// -------------------------------------------- query functions
	function query( Array $query = NULL ) {
		if ( is_null( $query ) ) $query = $this->query;

		$this->wp_query = new WP_Query( $query );
		$this->wp_query->display_strategy = $this;
		$this->query   = $this->wp_query->query;
		$this->request = $this->wp_query->request;

		// Process here before any loop_start re-organiastion
		CB2_Query::ensure_correct_classes( $this->wp_query->posts, $this );
		$new_posts = array();
		foreach ( $this->wp_query->posts as $perioditem ) {
			if ( WP_DEBUG ) {
				$period_entity   = $perioditem->period_entity;
				$Class           = get_class( $perioditem );
				$PSTClass        = get_class( $period_entity->period_status_type );
				$datetime_string = $perioditem->datetime_period_item_start->format( 'M-d' );
			}
			$overlap_perioditems = $this->overlap_perioditems( $perioditem );
			$this->process_perioditem( $perioditem, $overlap_perioditems );
			$this->markup( $perioditem );
			array_push( $new_posts, $perioditem );
		}

		// Reset WP_Query
		$this->wp_query->posts       = $new_posts;
		$this->wp_query->post_count  = count( $this->wp_query->posts );
		$this->wp_query->found_posts = (boolean) $this->wp_query->post_count;
		$this->wp_query->post        = ( $this->wp_query->found_posts ? $this->wp_query->posts[0] : NULL );

		// Some stats for inner_loop and things
		$this->posts       = &$this->wp_query->posts;
		$this->post_count  = $this->wp_query->post_count;
		$this->found_posts = $this->wp_query->found_posts;

		return $this->post_count;
	}

	function have_posts() {
		CB2_Query::reorganise_posts_structure( $this->wp_query );
		return $this->wp_query->have_posts();
	}

	function &the_post()   {
		$post = $this->wp_query->the_post();
		$this->in_the_loop = $this->wp_query->in_the_loop;
		return $post;
	}

	function get_queried_object_id() {
		return $this->wp_query->get_queried_object_id();
	}

	function reset_postdata() {
		return $this->wp_query->reset_postdata();
	}

	function is_post_type_archive( $post_types = '' ) {
		return FALSE;
	}

	function is_singular() {
		return FALSE;
	}

	// -------------------------------------------- period analysis functions
	function overlap_perioditems( CB2_PeriodItem $perioditem1 ) {
		$overlap_perioditems = array();
		foreach ( $this->wp_query->posts as $perioditem2 ) {
			if ( $this->overlaps( $perioditem1, $perioditem2 ) )
				array_push( $overlap_perioditems, $perioditem2 );
		}
		return $overlap_perioditems;
	}

	function overlaps_time( CB2_PeriodItem $perioditem1, CB2_PeriodItem $perioditem2 ) {
		return ( $perioditem1->datetime_period_item_start->moreThanOrEqual( $perioditem2->datetime_period_item_start )
			    && $perioditem1->datetime_period_item_start->lessThanOrEqual( $perioditem2->datetime_period_item_end ) )
			||   ( $perioditem1->datetime_period_item_end->moreThanOrEqual(   $perioditem2->datetime_period_item_start )
			    && $perioditem1->datetime_period_item_end->lessThanOrEqual(   $perioditem2->datetime_period_item_end ) );
  }

  function overlaps_object( $object1, $object2 ) {
		return is_null( $object1 ) || is_null( $object2 ) || $object1 == $object2;
  }

  function overlaps_perioditem_object( CB2_PeriodItem $perioditem1, CB2_PeriodItem $perioditem2, String $property_name ) {
		$object1 = ( property_exists( $perioditem1, $property_name ) ? $perioditem1->$property_name : NULL );
		$object2 = ( property_exists( $perioditem2, $property_name ) ? $perioditem2->$property_name : NULL );
		return $this->overlaps_object( $object1, $object2 );
  }

  function overlaps_locaton( CB2_PeriodItem $perioditem1, CB2_PeriodItem $perioditem2 ) {
		return $this->overlaps_perioditem_object( $perioditem1, $perioditem2, 'location' );
  }

  function overlaps_item( CB2_PeriodItem $perioditem1, CB2_PeriodItem $perioditem2 ) {
		return $this->overlaps_perioditem_object( $perioditem1, $perioditem2, 'item' );
  }

  function overlaps( CB2_PeriodItem $perioditem1, CB2_PeriodItem $perioditem2 ) {
		return $perioditem1 != $perioditem2
			&&   $this->overlaps_time(    $perioditem1, $perioditem2 )
			&&   $this->overlaps_locaton( $perioditem1, $perioditem2 )
			&&   $this->overlaps_item(    $perioditem1, $perioditem2 );
  }

  function process_perioditem( CB2_PeriodItem &$perioditem, Array $overlap_perioditems ) {
		if ( ! property_exists( $perioditem, '_cb2_processed' ) || ! $perioditem->_cb2_processed ) {
			$perioditem->priority_original   = $perioditem->period_entity->period_status_type->priority;
			$perioditem->overlap_perioditems = $overlap_perioditems;
			// Can be NULL, indicating that this item is always overridden, even if alone
			$perioditem->priority = $this->dynamic_priority( $perioditem, $overlap_perioditems );
			$this->set_processed( $perioditem );
		}
  }

  function markup( CB2_PeriodItem &$perioditem ) {
  }

  function set_processed( CB2_PeriodItem &$perioditem ) {
		$perioditem->_cb2_processed = TRUE;
  }

  function dynamic_priority( CB2_PeriodItem &$perioditem, Array $overlaps ) {
		// Dictate the new display order
		// only relevant for partial overlap
		// for example a morning slot overlapping a full-day open period
		return $perioditem->period_entity->period_status_type->priority;
  }

  // ---------------------------------------------------- Filter methods
  protected function filter_can( Array $perioditems, Int $period_status_type_flags, String $Class = NULL ) {
		$perioditems_filtered = array();
		foreach ( $perioditems as $perioditem ) {
			if ( ! $Class || is_a( $perioditem, $Class ) ) {
				if ( $perioditem->period_entity->period_status_type->flags & $period_status_type_flags )
					array_push( $perioditems_filtered, $perioditem );
			}
		}
		return $perioditems_filtered;
  }

  protected function filter_cannot( Array $perioditems, Int $period_status_type_flags, String $Class = NULL ) {
		$perioditems_filtered = array();
		foreach ( $perioditems as $perioditem ) {
			if ( ! $Class || is_a( $perioditem, $Class ) ) {
				if ( ! $perioditem->period_entity->period_status_type->flags & $period_status_type_flags )
					array_push( $perioditems_filtered, $perioditem );
			}
		}
		return $perioditems_filtered;
  }

  protected function filter_entity( Array $perioditems, String $entity_type, CB2_WordPress_Entity $entity = NULL ) {
		$perioditems_filtered      = array();
		$any_period_with_this_type = is_null( $entity );
		foreach ( $perioditems as $perioditem ) {
			if ( property_exists( $perioditem->period_entity, $entity_type )
				&& ( $any_period_with_this_type || $perioditem->period_entity->$entity_type->is( $entity ) )
			) {
				array_push( $perioditems_filtered, $perioditem );
			}
		}
		return $perioditems_filtered;
  }

  protected function filter_higher_priority( Array $perioditems, Int $priority ) {
		$perioditems_filtered = array();
		foreach ( $perioditems as $perioditem ) {
			if ( $perioditem->period_entity->period_status_type->priority >= $priority ) {
				array_push( $perioditems_filtered, $perioditem );
			}
		}
		return $perioditems_filtered;
  }

  protected function intersect( CB2_PeriodItem $perioditem, Array $perioditems ) {
		if ( count( $perioditems ) ) {
			// TODO: period intersect()
			// this functionality can wait for partial overlap requirement
		} else {
			// No valid items for intersect sent through
			$perioditem = NULL;
		}
		return $perioditem;
  }

  protected function exclude( CB2_PeriodItem $perioditem, Array $perioditems ) {
		if ( count( $perioditems ) ) {
			// Assume that these completely cover the target for now
			$perioditem = NULL;
		} else {
			// TODO: period exclude()
			// this functionality can wait for partial overlap requirement
		}
		return $perioditem;
  }

	function jsonSerialize() {
		// Need to manually do this because normally it is triggered by the loop_start action
		CB2_Query::reorganise_posts_structure( $this->wp_query );
		return $this->wp_query->posts;
	}
	function get_api_data(string $version)
	{
		$view_mode = isset( $this->wp_query->query['date_query']['compare'])? $this->wp_query->query['date_query']['compare'] : NULL;
		if($view_mode != 'item'){
			throw new Exception("View mode is set to [$view_mode]. Api data can only be created from an AllItemAvailability strategy that is in 'item' view mode.");
		}
		CB2_Query::reorganise_posts_structure($this->wp_query);
		$data = array(
			'version' => $version,
			'project' => array(
				'name' => get_bloginfo( 'name' ),
				'url' => network_site_url( '/' ),
				'description' => get_bloginfo( 'description' ),
				'language' => get_locale()
			),
			'items' => array(),
			'owners' => array(),
			'locations' => array()
		);
		foreach ($this->wp_query->posts as $item) {
			$data['items'][] = $item->get_api_data($version);
			// get location data from items (this can not be done by the items themselves as we store the locations in a top-level array within the data structure)
			if ($item->perioditems != null) {
				foreach ($item->perioditems as $period_inst) {
					$location = $period_inst->period_entity->location;
					if (!array_key_exists($location->ID, $data['locations'])) {
						$data['locations'][$location->ID] = $location->get_api_data($version);
					}
				}
			}
			// get owner data (this is done here for the same reason as for the location data)
			$owner_id = $item->post_author;
			if (!array_key_exists($owner_id, $data['owners'])) {
				$owner_data = array(
					'name' => get_the_author_meta('user_nicename', $owner_id),
					'url' => get_author_posts_url($owner_id)
				);
				$data['owners'][$owner_id] = $owner_data;
			}
		}
		return $data;
	}
}


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_Everything extends CB2_PeriodInteractionStrategy {
	// Direct WP_Query
	static function factory_from_query_args( Array $args ) {
		return new self( $args );
	}

	function __construct( Array $query = NULL ) {
		parent::__construct( NULL, NULL, NULL, $query );
	}
}


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_AllItemAvailability extends CB2_PeriodInteractionStrategy {
	/* Show item(s) availability
	* requiring their location to have an associated collect / return period
	* e.g. open
	*
	* Allowing any matching no-collect / no-return period types
	* with higher priority than available
	* to prevent availability
	* for example:
	*   holiday
	*   repair
	*   close
	*   booked
	*
	* In the case that there is a partial overlap, e.g.
	*   the location has a close perioditem in the morning
	*   and the item is available for the full day
	* then the item availability rejects/adopts the partial period
	*
	* NOTE: an item's location might change during the dates being shown.
	* all periods, e.g. opening hours, for multiple locations may be included
	* and must be filtered according to the current relevant location
	* for the current item availability
	*/

	static function factory_from_query_args( Array $args ) {
		$startdate = ( isset( $args['date_query']['after'] )   ? $args['date_query']['after']   : NULL );
		$enddate   = ( isset( $args['date_query']['before'] )  ? $args['date_query']['before']  : NULL );
		$view_mode = ( isset( $args['date_query']['compare'] ) ? $args['date_query']['compare'] : NULL );

		$startdate = ( $startdate ? new CB2_DateTime( $startdate ) : NULL );
		$enddate   = ( $enddate   ? new CB2_DateTime( $enddate )   : NULL );

		return new self( $startdate, $enddate, $view_mode, $args );
	}

	function __construct( CB2_DateTime $startdate = NULL, CB2_DateTime $enddate = NULL, String $view_mode = 'week', Array $query = array() ) {
		parent::__construct( $startdate, $enddate, $view_mode, $query );
	}

	function markup( CB2_PeriodItem &$perioditem ) {
		$period_status_type = $perioditem->period_entity->period_status_type;

		// Prevent any none-availability perioditems being selected
		if ( ! $perioditem instanceof CB2_PeriodItem_Timeframe
			|| ! $period_status_type->can( CB2_ANY_ACTION )
		) {
			$perioditem->no_select = TRUE;
		}
	}

	function dynamic_priority( CB2_PeriodItem &$perioditem, Array $overlaps ) {
		$priority           = parent::dynamic_priority( $perioditem, $overlaps );
		$period_status_type = $perioditem->period_entity->period_status_type;

		if ( $perioditem instanceof CB2_PeriodItem_Timeframe
			&& $period_status_type->can( CB2_ANY_ACTION )
		) {
			// ---------------------------------- Item availability
			$location = $perioditem->period_entity->location;
			$item     = $perioditem->period_entity->item;

			// Require THE location pickup / return period
			$location_cans  = $this->filter_can( $overlaps, CB2_ANY_ACTION, 'CB2_PeriodItem_Location' );
			$location_cans  = $this->filter_entity( $location_cans, 'location', $location );
			$location_ok    = $this->intersect( $perioditem, $location_cans );
			if ( is_null( $location_ok ) ) {
				$priority = NULL;
				$perioditem->log( 'No location actions available' );
			} else {
				// Avoid Blocking perioditems
				$all_cannots    = $this->filter_cannot( $overlaps, CB2_ANY_ACTION );
				$all_cannots    = $this->filter_higher_priority( $all_cannots, $priority ); // >=
				$overrides_ok   = $this->exclude( $perioditem, $all_cannots );
				if ( is_null( $overrides_ok ) ) {
					$priority = NULL;
					$perioditem->log( 'Blocked by priority denial' );
				}
			}
		}

		return $priority;
	}
}



// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_SingleItemAvailability extends CB2_AllItemAvailability {
	// Standard situation when viewing a single item with the intention to book it
	static function factory_from_query_args( Array $args ) {
		$item_ID     = ( isset( $args['meta_query']['entities']['item_ID_clause']['value'][0] ) ? $args['meta_query']['entities']['item_ID_clause']['value'][0] : NULL );
		$startdate   = ( isset( $args['date_query']['after'] )   ? $args['date_query']['after']   : NULL );
		$enddate     = ( isset( $args['date_query']['before'] )  ? $args['date_query']['before']  : NULL );
		$view_mode   = ( isset( $args['date_query']['compare'] ) ? $args['date_query']['compare'] : NULL );
		$post_status = ( isset( $args['post_status'] ) ? $args['post_status'] : NULL );
		$show_overridden_periods = ( $post_status == CB2_Post::$TRASH
			|| ( is_array( $post_status ) && in_array( CB2_Post::$TRASH, $post_status ) ) );

		if ( is_null( $item_ID ) )
			throw new Exception( "CB2_SingleItemAvailability::factory_from_query_args() requires ['meta_query']['entities']['item_ID_clause']['value'][0]" );

		$item      = CB2_Query::get_post_with_type( CB2_Item::$static_post_type, $item_ID );
		$startdate = ( $startdate ? new CB2_DateTime( $startdate ) : NULL );
		$enddate   = ( $enddate   ? new CB2_DateTime( $enddate )   : NULL );

		return new self( $item, $startdate, $enddate, $view_mode, $show_overridden_periods, $args );
	}

	function __construct( CB2_Item $item = NULL, CB2_DateTime $startdate = NULL, CB2_DateTime $enddate = NULL, String $view_mode = 'week', Bool $show_overridden_periods = FALSE, Array $query = array() ) {
		global $post;
		$this->item = ( $item ? $item : $post );
		if ( ! $this->item instanceof CB2_Item )
			throw new Exception( 'global post must be a CB2_Item for the CB2_SingleItemAvailability' );

		if ( ! isset( $query['meta_query'] ) ) $query['meta_query'] = array();
		if ( ! isset( $query['meta_query']['entities']['item_ID_clause'] ) )
			$query['meta_query']['entities']['item_ID_clause'] = array(
				'key'     => 'item_ID',
				'value'   => array( $this->item->ID, 0 ),
				'compare' => 'IN',
			);
		if ( $show_overridden_periods ) {
			$post_status = ( isset( $query['post_status'] ) ? $query['post_status'] : array( CB2_Post::$PUBLISH ) );
			if ( ! is_array( $post_status ) ) $post_status = array( $post_status );
			if ( ! in_array( CB2_Post::$TRASH, $post_status ) ) array_push( $post_status, CB2_Post::$TRASH );
			$query['post_status'] = $post_status;
		}

		parent::__construct( $startdate, $enddate, $view_mode, $query );
	}

	function dynamic_priority( CB2_PeriodItem &$perioditem, Array $overlaps ) {
		$priority = parent::dynamic_priority( $perioditem, $overlaps );

		if ( $perioditem instanceof CB2_PeriodItem_Timeframe
			|| $perioditem instanceof CB2_PeriodItem_Timeframe_User
		) {
			// ---------------------------------- Item mismatch checks
			$item = $perioditem->period_entity->item;
			if ( $item != $this->item )
				throw new Exception( 'CB2_SingleItemAvailability: CB2_PeriodItem_Timeframe for different item' );
		}

		// TODO: replace this with an understanding of where the item is
		// and when it changes, and remove / change Location accordingly
		/*
		if ( $perioditem instanceof CB2_PeriodItem_Location ) {
			// ---------------------------------- Irrelevant location removal
			// https://github.com/wielebenwir/commons-booking-2/issues/59
			$location = $perioditem->period_entity->location;

			// Is this a location of any current item availabilities?
			// note that we are allowing the possibility here
			// of this single item being concurrently available in 2 separate locations
			$availabilities = $this->filter_can( $overlaps, CB2_ANY_ACTION, 'CB2_PeriodItem_Timeframe' );
			$availabilities = $this->filter_entity( $availabilities, 'item',     $this->item );
			$availabilities = $this->filter_entity( $availabilities, 'location', $location );
			if ( ! count( $availabilities ) ) {
				$priority = NULL; // Delete it
				$perioditem->log( 'Irrelevant location period item' );
			}
		}
		*/

		return $priority;
	}
}
