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
	 * Provides methods to examine relevance of periodinsts
	 * e.g.
	 *   2 periodinsts do not relate if they are for
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

	protected function __construct( CB2_DateTime $startdate = NULL, CB2_DateTime $enddate = NULL, String $schema_type = NULL, Array $query = NULL ) {
		// Defaults
		if ( is_null( $startdate ) )   $startdate   = ( isset( $_REQUEST['startdate'] )   ? new CB2_DateTime( $_REQUEST['startdate'] ) : new CB2_DateTime() );
		if ( is_null( $enddate ) )     $enddate     = ( isset( $_REQUEST['enddate'] )     ? new CB2_DateTime( $_REQUEST['enddate'] )   : (clone $startdate)->add( new DateInterval('P1M') ) );
		if ( is_null( $schema_type ) ) $schema_type = ( isset( $_REQUEST['schema_type'] )
			? $_REQUEST['schema_type']
			: CB2_Week::$static_post_type
		);
		if ( is_null( $query ) )     $query         = array();

		// Properties
		$this->startdate   = $startdate;
		$this->enddate     = $enddate;
		$this->schema_type = $schema_type;

		// --------------------------------------- Checks
		if ( WP_DEBUG && ( $startdate->after( $enddate ) ) )
			throw new Exception( 'start date is more than end date' );

		// Construct args
		if ( ! isset( $query['post_status'] ) )    $query['post_status'] = CB2_Post::$PUBLISH;
		if ( ! isset( $query['post_type'] ) )      $query['post_type']   = CB2_PeriodInst::$all_post_types;
		if ( ! isset( $query['posts_per_page'] ) ) $query['posts_per_page'] = -1;
		if ( ! isset( $query['order'] ) )          $query['order'] = 'ASC'; // defaults to post_date
		if ( ! isset( $query['date_query'] ) )     $query['date_query'] = array();
		if ( ! CB2_Query::key_exists_recursive( $query['date_query'], 'after' ) )
			array_push( $query['date_query'], array(
				'column' => 'post_modified_gmt',
				'after'  => $this->startdate->format( CB2_Query::$datetime_format )
			) );
		if ( ! CB2_Query::key_exists_recursive( $query['date_query'], 'before' ) )
			array_push( $query['date_query'], array(
				'column' => 'post_date_gmt',
				'before' => $this->enddate->format( CB2_Query::$datetime_format )
			) );
		// This sets which CB2_(ObjectType) is the resultant primary posts array
		// e.g. CB2_Weeks generated from the CB2_PeriodInst records
		if ( ! isset( $query['date_query']['compare'] ) ) $query['date_query']['compare'] = $this->schema_type;
		// Single periodinst blocking
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

	public static function factory_from_args( Array &$inputs, Array $defaults = array() ) {
		$wp_query               = NULL;
		$wp_query_args          = self::array_to_wp_query_args( $inputs, $defaults );
		if ( ! isset( $inputs['display_strategy'] ) || ! $inputs['display_strategy'] )
			$inputs['display_strategy'] = 'WP_Query';
		$Class_display_strategy = $inputs['display_strategy'];

		if ( $Class_display_strategy == 'WP_Query' ) {
			$wp_query = new WP_Query( $wp_query_args );
		} else {
			$wp_query = $Class_display_strategy::factory_from_query_args( $wp_query_args );
		}
		if ( WP_DEBUG ) $wp_query->inputs = $inputs;

		return $wp_query;
	}

	public static function sanitize_input_keys( $key ) {
		return preg_replace( '/-/', '_', $key );
	}

	private static function array_to_wp_query_args( Array &$inputs, Array $defaults = array() ) {
		// Inputs override defaults
		// TODO: Integrate this with cb2_pre_get_posts_query_string_extensions()
		$inputs = array_merge( $defaults, $inputs );
		$inputs = CB2_Query::array_walk_keys( $inputs, array( get_class(), 'sanitize_input_keys' ) );

		// --------------------------------------- Query Parameters
		$interval_to_show = ( isset( $inputs['interval_to_show'] ) ? $inputs['interval_to_show'] : 'P1M' );
		$today            = CB2_DateTime::today();
		$plusXmonths      = $today->clone()->add( $interval_to_show )->endTime();
		if ( ! isset( $inputs['startdate'] ) )   $inputs['startdate']   = $today->format( CB2_Query::$datetime_format );
		if ( ! isset( $inputs['enddate']   ) )   $inputs['enddate']     = $plusXmonths->format( CB2_Query::$datetime_format );
		if ( ! isset( $inputs['location_ID'] ) ) $inputs['location_ID'] = NULL;
		if ( ! isset( $inputs['item_ID'] ) )     $inputs['item_ID']     = NULL;
		if ( ! isset( $inputs['user_ID'] ) )     $inputs['user_ID']     = NULL;
		if ( ! isset( $inputs['period_group_id'] ) )  $inputs['period_group_id'] = NULL;
		if ( ! isset( $inputs['period_status_type_ID'] ) ) $inputs['period_status_type_ID'] = NULL;
		if ( ! isset( $inputs['period_entity_ID'] ) ) $inputs['period_entity_ID'] = NULL;
		if ( ! isset( $inputs['schema_type'] ) )      $inputs['schema_type'] = CB2_Week::$static_post_type;
		if ( ! isset( $inputs['show_blocked_periods'] ) ) $inputs['show_blocked_periods'] = FALSE;
		if ( ! isset( $inputs['show_overridden_periods'] ) ) $inputs['show_overridden_periods'] = FALSE;

		$meta_query       = array();
		$meta_query_items = array();
		if ( $inputs['location_ID'] )
			$meta_query_items[ 'location_ID_clause' ] = array(
				'key' => 'location_ID',
				'value' => array( $inputs['location_ID'], 0 ),
			);
		if ( $inputs['item_ID'] )
			$meta_query_items[ 'item_ID_clause' ] = array(
				'key' => 'item_ID',
				'value' => array( $inputs['item_ID'], 0 ),
			);
		if ( $inputs['period_status_type_ID'] )
			$meta_query_items[ 'period_status_type_clause' ] = array(
				'key' => 'period_status_type_ID',
				'value' => array( $inputs['period_status_type_ID'], 0 ),
			);
		if ( $inputs['period_entity_ID'] )
			$meta_query_items[ 'period_entity_clause' ] = array(
				'key' => 'period_entity_ID',
				'value' => array( $inputs['period_entity_ID'], 0 ),
			);
		if ( $inputs['show_blocked_periods'] )
			$meta_query['blocked_clause'] = 0; // Prevent it from defaulting
		else
			$meta_query['blocked_clause'] = array(
				'key'     => 'blocked',
				'value'   => '0',
			);

		if ( $meta_query_items ) {
			if ( ! isset( $meta_query_items[ 'relation' ] ) )
				$meta_query_items[ 'relation' ] = 'AND';
			$meta_query[ 'entities' ] = $meta_query_items;
		}

		$post_status = array( CB2_Post::$PUBLISH );
		if ( $inputs['show_overridden_periods'] )
			array_push( $post_status, CB2_Post::$TRASH );

		// --------------------------------------- Output
		return array(
			'author'         => $inputs['user_ID'],
			'post_status'    => $post_status,
			'post_type'      => CB2_PeriodInst::$all_post_types,
			'posts_per_page' => -1,
			'order'          => 'ASC',          // defaults to post_date
			'date_query'     => array(
				array(
					// post_modified_gmt is the end date of the period instance
					'column' => 'post_modified_gmt',
					'after'  => $inputs['startdate'],
				),
				array(
					// post_gmt is the start date of the period instance
					'column' => 'post_date_gmt',
					'before' => $inputs['enddate'],
				),
				'compare' => $inputs['schema_type'],
			),
			'meta_query' => $meta_query,        // Location, Item, User
		);
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
		foreach ( $this->wp_query->posts as $periodinst ) {
			if ( WP_DEBUG ) {
				$period_entity   = $periodinst->period_entity;
				$Class           = get_class( $periodinst );
				$PSTClass        = get_class( $period_entity->period_status_type );
				$datetime_string = $periodinst->datetime_period_inst_start->format( 'M-d' );
			}
			$overlap_periodinsts = $this->overlap_periodinsts( $periodinst );
			$this->process_periodinst( $periodinst, $overlap_periodinsts );
			$this->markup( $periodinst );
			array_push( $new_posts, $periodinst );
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
	function overlap_periodinsts( CB2_PeriodInst $periodinst1 ) {
		$overlap_periodinsts = array();
		foreach ( $this->wp_query->posts as $periodinst2 ) {
			if ( $this->overlaps( $periodinst1, $periodinst2 ) )
				array_push( $overlap_periodinsts, $periodinst2 );
		}
		return $overlap_periodinsts;
	}

	function overlaps_time( CB2_PeriodInst $periodinst1, CB2_PeriodInst $periodinst2 ) {
		return ( $periodinst1->datetime_period_inst_start->moreThanOrEqual( $periodinst2->datetime_period_inst_start )
			    && $periodinst1->datetime_period_inst_start->lessThanOrEqual( $periodinst2->datetime_period_inst_end ) )
			||   ( $periodinst1->datetime_period_inst_end->moreThanOrEqual(   $periodinst2->datetime_period_inst_start )
			    && $periodinst1->datetime_period_inst_end->lessThanOrEqual(   $periodinst2->datetime_period_inst_end ) );
  }

  function overlaps_object( $object1, $object2 ) {
		return is_null( $object1 ) || is_null( $object2 ) || $object1 == $object2;
  }

  function overlaps_periodinst_object( CB2_PeriodEntity $periodentity1, CB2_PeriodEntity $periodentity2, String $property_name ) {
		$object1 = ( property_exists( $periodentity1, $property_name ) ? $periodentity1->$property_name : NULL );
		$object2 = ( property_exists( $periodentity2, $property_name ) ? $periodentity2->$property_name : NULL );
		return $this->overlaps_object( $object1, $object2 );
  }

  function overlaps_locaton( CB2_PeriodInst $periodinst1, CB2_PeriodInst $periodinst2 ) {
		return $this->overlaps_periodinst_object( $periodinst1->period_entity, $periodinst2->period_entity, 'location' );
  }

  function overlaps_item( CB2_PeriodInst $periodinst1, CB2_PeriodInst $periodinst2 ) {
		return $this->overlaps_periodinst_object( $periodinst1->period_entity, $periodinst2->period_entity, 'item' );
  }

  function overlaps_user( CB2_PeriodInst $periodinst1, CB2_PeriodInst $periodinst2 ) {
		// TODO: overlapping users?
		return $this->overlaps_periodinst_object( $periodinst1->period_entity, $periodinst2->period_entity, 'user' );
  }

  function overlaps( CB2_PeriodInst $periodinst1, CB2_PeriodInst $periodinst2 ) {
		return ( $periodinst1 != $periodinst2 )
			&&   $this->overlaps_time(    $periodinst1, $periodinst2 )
			&&   $this->overlaps_locaton( $periodinst1, $periodinst2 )
			&&   $this->overlaps_item(    $periodinst1, $periodinst2 );
  }

  function process_periodinst( CB2_PeriodInst &$periodinst, Array $overlap_periodinsts ) {
		if ( ! property_exists( $periodinst, '_cb2_processed' ) || ! $periodinst->_cb2_processed ) {
			$periodinst->priority_original   = $periodinst->period_entity->period_status_type->priority;
			$periodinst->overlap_periodinsts = $overlap_periodinsts;
			// Can be NULL, indicating that this item is always overridden, even if alone
			$periodinst->priority = $this->dynamic_priority( $periodinst, $overlap_periodinsts );

			// Set the top_priority_overlap_period
			// based on the comparative priorities
			foreach ( $overlap_periodinsts as $overlap_periodinst ) {
				if ( $overlap_periodinst->priority() > $periodinst->priority() ) {
					$periodinst->priority_overlap_periods[ $overlap_periodinst->priority() ] = $overlap_periodinst;
					if ( is_null( $periodinst->top_priority_overlap_period )
						|| $overlap_periodinst->priority() > $periodinst->top_priority_overlap_period->priority()
					)
						$periodinst->top_priority_overlap_period = $overlap_periodinst;
				}
			}

			$this->set_processed( $periodinst );
		}
  }

  function markup( CB2_PeriodInst &$periodinst ) {
  }

  function set_processed( CB2_PeriodInst &$periodinst ) {
		$periodinst->_cb2_processed = TRUE;
  }

  function dynamic_priority( CB2_PeriodInst &$periodinst, Array $overlaps ) {
		// Dictate the new display order
		// only relevant for partial overlap
		// for example a morning slot overlapping a full-day open period
		return $periodinst->period_entity->period_status_type->priority;
  }

  // ---------------------------------------------------- Filter methods
  protected function filter_can( Array $periodinsts, Int $period_status_type_flags, String $Class = NULL ) {
		$periodinsts_filtered = array();
		foreach ( $periodinsts as $periodinst ) {
			if ( ! $Class || is_a( $periodinst, $Class ) ) {
				if ( $periodinst->period_entity->period_status_type->flags & $period_status_type_flags )
					array_push( $periodinsts_filtered, $periodinst );
			}
		}
		return $periodinsts_filtered;
  }

  protected function filter_cannot( Array $periodinsts, Int $period_status_type_flags, String $Class = NULL ) {
		$periodinsts_filtered = array();
		foreach ( $periodinsts as $periodinst ) {
			if ( ! $Class || is_a( $periodinst, $Class ) ) {
				if ( ! $periodinst->period_entity->period_status_type->flags & $period_status_type_flags )
					array_push( $periodinsts_filtered, $periodinst );
			}
		}
		return $periodinsts_filtered;
  }

  protected function filter_entity( Array $periodinsts, String $entity_type, CB2_WordPress_Entity $entity = NULL ) {
		$periodinsts_filtered      = array();
		$any_period_with_this_type = is_null( $entity );
		foreach ( $periodinsts as $periodinst ) {
			if ( property_exists( $periodinst->period_entity, $entity_type )
				&& ( $any_period_with_this_type || $periodinst->period_entity->$entity_type->is( $entity ) )
			) {
				array_push( $periodinsts_filtered, $periodinst );
			}
		}
		return $periodinsts_filtered;
  }

  protected function filter_higher_priority( Array $periodinsts, Int $priority ) {
		$periodinsts_filtered = array();
		foreach ( $periodinsts as $periodinst ) {
			if ( $periodinst->period_entity->period_status_type->priority >= $priority ) {
				array_push( $periodinsts_filtered, $periodinst );
			}
		}
		return $periodinsts_filtered;
  }

  protected function intersect( CB2_PeriodInst $periodinst, Array $periodinsts ) {
		if ( count( $periodinsts ) ) {
			// TODO: period intersect()
			// this functionality can wait for partial overlap requirement
		} else {
			// No valid items for intersect sent through
			$periodinst = NULL;
		}
		return $periodinst;
  }

  protected function exclude( CB2_PeriodInst $periodinst, Array $periodinsts ) {
		if ( count( $periodinsts ) ) {
			// Assume that these completely cover the target for now
			$periodinst = NULL;
		} else {
			// TODO: period exclude()
			// this functionality can wait for partial overlap requirement
		}
		return $periodinst;
  }

	function jsonSerialize() {
		// Need to manually do this because normally it is triggered by the loop_start action
		CB2_Query::reorganise_posts_structure( $this->wp_query );
		return $this->wp_query->posts;
	}
	function get_api_data(string $version)
	{
		$schema_type = isset( $this->wp_query->query['date_query']['compare'])? $this->wp_query->query['date_query']['compare'] : NULL;
		if($schema_type != 'item'){
			throw new Exception("View mode is set to [$schema_type]. Api data can only be created from an AllItemAvailability strategy that is in 'item' view mode.");
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
			'locations' => array(
				'type' => 'FeatureCollection',
				'features' => array()
				)
		);
		$locationMap = array();
		$ownerMap = array();
		foreach ($this->wp_query->posts as $item) {
			$data['items'][] = $item->get_api_data($version);
			// get location data from items (this can not be done by the items themselves as we store the locations in a top-level array within the data structure)
			if ($item->periodinsts != null) {
				foreach ($item->periodinsts as $period_inst) {
					$location = $period_inst->period_entity->location;
					if (!array_key_exists($location->ID, $locationMap)) {
						$locationData = $location->get_api_data($version);
						$data['locations']['features'][] = $locationData;
						$locationMap[$location->ID] = $locationData;
					}
				}
			}
			// get owner data (this is done here for the same reason as for the location data)
			$owner_id = $item->post_author;
			if (!array_key_exists($owner_id, $ownerMap)) {
				$owner_data = array(
					'name' => get_the_author_meta('user_nicename', $owner_id),
					'url' => get_author_posts_url($owner_id),
					'uid' => (string)$owner_id
				);
				$ownerMap[$owner_id] = $owner_data;
				$data['owners'][] = $owner_data;
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
	*   the location has a close periodinst in the morning
	*   and the item is available for the full day
	* then the item availability rejects/adopts the partial period
	*
	* NOTE: an item's location might change during the dates being shown.
	* all periods, e.g. opening hours, for multiple locations may be included
	* and must be filtered according to the current relevant location
	* for the current item availability
	*/

	static function factory_from_query_args( Array $args ) {
		$startdate   = CB2_Query::value_recursive( $args['date_query'], 'after' );
		$enddate     = CB2_Query::value_recursive( $args['date_query'], 'before' );
		$schema_type = CB2_Query::value_recursive( $args['date_query'], 'compare' );

		$startdate   = ( $startdate ? new CB2_DateTime( $startdate ) : NULL );
		$enddate     = ( $enddate   ? new CB2_DateTime( $enddate )   : NULL );

		return new self( $startdate, $enddate, $schema_type, $args );
	}

	function __construct( CB2_DateTime $startdate = NULL, CB2_DateTime $enddate = NULL, String $schema_type = 'week', Array $query = array() ) {
		parent::__construct( $startdate, $enddate, $schema_type, $query );
	}

	function markup( CB2_PeriodInst &$periodinst ) {
		$period_status_type = $periodinst->period_entity->period_status_type;

		// Prevent any none-availability periodinsts being selected
		if ( ! $periodinst instanceof CB2_PeriodInst_Timeframe
			|| ! $period_status_type->can( CB2_ANY_ACTION )
		) {
			$periodinst->no_select = TRUE;
		}
	}

	function dynamic_priority( CB2_PeriodInst &$periodinst, Array $overlaps ) {
		$priority           = parent::dynamic_priority( $periodinst, $overlaps );
		$period_status_type = $periodinst->period_entity->period_status_type;

		if ( $periodinst instanceof CB2_PeriodInst_Timeframe
			&& $period_status_type->can( CB2_ANY_ACTION )
		) {
			// ---------------------------------- Item availability
			$location = $periodinst->period_entity->location;
			$item     = $periodinst->period_entity->item;

			// Require THE location pickup / return period
			$location_cans  = $this->filter_can( $overlaps, CB2_ANY_ACTION, 'CB2_PeriodInst_Location' );
			$location_cans  = $this->filter_entity( $location_cans, 'location', $location );
			$location_ok    = $this->intersect( $periodinst, $location_cans );
			if ( is_null( $location_ok ) ) {
				$priority = NULL;
				$periodinst->log( 'No location actions available' );
			} else {
				// Avoid Blocking periodinsts
				$all_cannots    = $this->filter_cannot( $overlaps, CB2_ANY_ACTION );
				$all_cannots    = $this->filter_higher_priority( $all_cannots, $priority ); // >=
				$overrides_ok   = $this->exclude( $periodinst, $all_cannots );
				if ( is_null( $overrides_ok ) ) {
					$priority = NULL;
					$periodinst->log( 'Blocked by priority denial' );
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
		global $post;
		$item_ID     = ( isset( $args['meta_query']['entities']['item_ID_clause']['value'][0] )
			? $args['meta_query']['entities']['item_ID_clause']['value'][0]
			: NULL
		);
		$startdate   = CB2_Query::value_recursive( $args['date_query'], 'after' );
		$enddate     = CB2_Query::value_recursive( $args['date_query'], 'before' );
		$schema_type = CB2_Query::value_recursive( $args['date_query'], 'compare' );
		$post_status = CB2_Query::isset( $args, 'post_status' );
		$show_overridden_periods = ( $post_status == CB2_Post::$TRASH
			|| ( is_array( $post_status ) && in_array( CB2_Post::$TRASH, $post_status ) ) );

		$item = NULL;
		if ( is_null( $item_ID ) ) {
			// Try to use the global $post
			if ( $post && $post->post_type == CB2_Item::$static_post_type )
				$item = $post;
			else
				throw new Exception( "CB2_SingleItemAvailability::factory_from_query_args() requires global CB2_Item post or ['meta_query']['entities']['item_ID_clause']['value'][0]" );
		} else {
			$item = CB2_Query::get_post_with_type( CB2_Item::$static_post_type, $item_ID );
		}

		$startdate = ( $startdate ? new CB2_DateTime( $startdate ) : NULL );
		$enddate   = ( $enddate   ? new CB2_DateTime( $enddate )   : NULL );

		return new self( $item, $startdate, $enddate, $schema_type, $show_overridden_periods, $args );
	}

	function __construct( CB2_Item $item = NULL, CB2_DateTime $startdate = NULL, CB2_DateTime $enddate = NULL, String $schema_type = 'week', Bool $show_overridden_periods = FALSE, Array $query = array() ) {
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

		parent::__construct( $startdate, $enddate, $schema_type, $query );
	}

	function dynamic_priority( CB2_PeriodInst &$periodinst, Array $overlaps ) {
		$priority = parent::dynamic_priority( $periodinst, $overlaps );

		if ( $periodinst instanceof CB2_PeriodInst_Timeframe
			|| $periodinst instanceof CB2_PeriodInst_Timeframe_User
		) {
			// ---------------------------------- Item mismatch checks
			$item = $periodinst->period_entity->item;
			if ( $item != $this->item )
				throw new Exception( 'CB2_SingleItemAvailability: CB2_PeriodInst_Timeframe for different item' );
		}

		// TODO: replace this with an understanding of where the item is
		// and when it changes, and remove / change Location accordingly
		/*
		if ( $periodinst instanceof CB2_PeriodInst_Location ) {
			// ---------------------------------- Irrelevant location removal
			// https://github.com/wielebenwir/commons-booking-2/issues/59
			$location = $periodinst->period_entity->location;

			// Is this a location of any current item availabilities?
			// note that we are allowing the possibility here
			// of this single item being concurrently available in 2 separate locations
			$availabilities = $this->filter_can( $overlaps, CB2_ANY_ACTION, 'CB2_PeriodInst_Timeframe' );
			$availabilities = $this->filter_entity( $availabilities, 'item',     $this->item );
			$availabilities = $this->filter_entity( $availabilities, 'location', $location );
			if ( ! count( $availabilities ) ) {
				$priority = NULL; // Delete it
				$periodinst->log( 'Irrelevant location period instance' );
			}
		}
		*/

		return $priority;
	}
}
