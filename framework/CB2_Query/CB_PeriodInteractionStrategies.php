<?php
define( 'CB2_ANY_CLASS', NULL );


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_PeriodInteractionStrategy {
	/* CB_PeriodInteractionStrategy deals with
	 * the display of periods in the calendar
	 * given:
	 *   a UX display aim like show_single_item_availability
	 *   parameters like an CB_Item
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
	 *       'strategy' => 'CB_SingleItemAvailability',
	 *       'item'     => $current_item,
	 *       'require_location_open' => TRUE,
	 *     )
	 *   );
	 */
	private $wp_query;

	function __construct( $startdate = NULL, $enddate = NULL, $view_mode = NULL, $args = NULL ) {
		// Defaults
		if ( is_null( $startdate ) ) $startdate   = ( isset( $_GET['startdate'] ) ? new DateTime( $_GET['startdate'] ) : new DateTime() );
		if ( is_null( $enddate ) )   $enddate     = ( isset( $_GET['enddate'] )   ? new DateTime( $_GET['enddate'] )   : (clone $startdate)->add( new DateInterval('P1M') ) );
		if ( is_null( $view_mode ) ) $view_mode   = ( isset( $_GET['view_mode'] ) ? $_GET['view_mode'] : CB_Week::$static_post_type );
		if ( is_null( $args ) )      $args        = array();

		// Properties
		$this->startdate = $startdate;
		$this->enddate   = $enddate;
		$this->view_mode = $view_mode;

		// Construct args
		if ( ! isset( $args['post_status'] ) )    $args['post_status'] = CB_Post::$PUBLISH;
		if ( ! isset( $args['post_type'] ) )      $args['post_type'] = CB_PeriodItem::$all_post_types;
		if ( ! isset( $args['posts_per_page'] ) ) $args['posts_per_page'] = -1;
		if ( ! isset( $args['order'] ) )          $args['order'] = 'ASC'; // defaults to post_date
		if ( ! isset( $args['date_query'] ) )     $args['date_query'] = array();
		if ( ! isset( $args['date_query']['after'] ) )  $args['date_query']['after']  = $this->startdate->format( CB_Query::$datetime_format );
		if ( ! isset( $args['date_query']['before'] ) ) $args['date_query']['before'] = $this->enddate->format( CB_Query::$datetime_format );
		// This sets which CB_(ObjectType) is the resultant primary posts array
		// e.g. CB_Weeks generated from the CB_PeriodItem records
		if ( ! isset( $args['date_query']['compare'] ) ) $args['date_query']['compare'] = $this->view_mode;

		$this->logs = array();
		$this->args = $args;
		$this->query();

		// Expost the private WP_Query in WP_DEBUG mode
		if ( WP_DEBUG ) $this->debug_wp_query = $this->wp_query;
	}

	function log( $thing ) {
		array_push( $this->logs, $thing );
	}

	// -------------------------------------------- query functions
	function query() {
		$this->wp_query = new WP_Query( $this->args );
		$this->wp_query->display_strategy = $this;
		$this->query   = $this->wp_query->query;
		$this->request = $this->wp_query->request;

		// Process here before any loop_start re-organiastion
		CB_Query::ensure_correct_classes( $this->wp_query->posts, $this );
		$new_posts = array();
		foreach ( $this->wp_query->posts as $perioditem ) {
			if ( ! $perioditem instanceof CB_PeriodItem_Automatic ) {
				if ( WP_DEBUG ) {
					$period_entity   = $perioditem->period_entity;
					$Class           = get_class( $perioditem );
					$PSTClass        = get_class( $period_entity->period_status_type );
					$datetime_string = $perioditem->datetime_period_item_start->format( 'M-d' );
					$this->log( "$Class($PSTClass)::$datetime_string" );
				}
				$overlap_perioditems = $this->overlap_perioditems( $perioditem );
				$new_perioditem      = $this->process_perioditem( $perioditem, $overlap_perioditems );

				if ( is_null( $new_perioditem ) ) {
					$perioditem->remove();
					$this->log( 'removed' );
				} else {
					$this->markup( $new_perioditem );
				}
				$perioditem = $new_perioditem;
			}

			if ( ! is_null( $perioditem ) )
				array_push( $new_posts, $perioditem );
		}

		// Reset WP_Query
		$this->wp_query->posts       = $new_posts;
		$this->wp_query->post_count  = count( $this->wp_query->posts );
		$this->wp_query->found_posts = (boolean) $this->wp_query->post_count;
		$this->wp_query->post        = ( $this->wp_query->found_posts ? $this->wp_query->posts[0] : NULL );

		return $this->wp_query->post_count;
	}

	function have_posts() {
		return $this->wp_query->have_posts();
	}

	function the_post()   {
		return $this->wp_query->the_post();
	}

	// -------------------------------------------- period analysis functions
	function overlap_perioditems( $perioditem1 ) {
		$overlap_perioditems = array();
		foreach ( $this->wp_query->posts as $perioditem2 ) {
			if ( $this->overlaps( $perioditem1, $perioditem2 ) )
				array_push( $overlap_perioditems, $perioditem2 );
		}
		return $overlap_perioditems;
	}

	function overlaps_time( $perioditem1, $perioditem2 ) {
		return ( $perioditem1->datetime_period_item_start >= $perioditem2->datetime_period_item_start
			    && $perioditem1->datetime_period_item_start <= $perioditem2->datetime_period_item_end )
			||   ( $perioditem1->datetime_period_item_end   >= $perioditem2->datetime_period_item_start
			    && $perioditem1->datetime_period_item_end   <= $perioditem2->datetime_period_item_end );
  }

  function overlaps_object( $object1, $object2 ) {
		return is_null( $object1 ) || is_null( $object2 ) || $object1 == $object2;
  }

  function overlaps_perioditem_object( $perioditem1, $perioditem2, $property_name ) {
		$object1 = ( property_exists( $perioditem1, $property_name ) ? $perioditem1->$property_name : NULL );
		$object2 = ( property_exists( $perioditem2, $property_name ) ? $perioditem2->$property_name : NULL );
		return $this->overlaps_object( $object1, $object2 );
  }

  function overlaps_locaton( $perioditem1, $perioditem2 ) {
		return $this->overlaps_perioditem_object( $perioditem1, $perioditem2, 'location' );
  }

  function overlaps_item( $perioditem1, $perioditem2 ) {
		return $this->overlaps_perioditem_object( $perioditem1, $perioditem2, 'item' );
  }

  function overlaps( $perioditem1, $perioditem2 ) {
		return $perioditem1 != $perioditem2
			&&   $this->overlaps_time(    $perioditem1, $perioditem2 )
			&&   $this->overlaps_locaton( $perioditem1, $perioditem2 )
			&&   $this->overlaps_item(    $perioditem1, $perioditem2 );
  }

  function process_perioditem( $perioditem, $overlap_perioditems ) {
		if ( ! property_exists( $perioditem, '_cb2_processed' ) || ! $perioditem->_cb2_processed ) {
			$perioditem->priority_original   = $perioditem->period_entity->period_status_type->priority;
			$perioditem->overlap_perioditems = $overlap_perioditems;
			$perioditem->priority = $this->dynamic_priority( $perioditem, $overlap_perioditems );
			$this->set_processed( $perioditem );
			if ( is_null( $perioditem->priority ) ) $perioditem = NULL; // Delete
		}
		return $perioditem;
  }

  function markup( &$perioditem ) {
  }

  function set_processed( &$perioditem ) {
		$perioditem->_cb2_processed = TRUE;
  }

  function dynamic_priority( $perioditem, $overlap_perioditems ) {
		// Dictate the new display order
		// only relevant for partial overlap
		// for example a morning slot overlapping a full-day open period
		return $perioditem->period_entity->period_status_type->priority;
  }

  // ---------------------------------------------------- Filter methods
  protected function filter_can( $perioditems, $Class, $period_status_type_flags ) {
		$perioditems_filtered = array();
		foreach ( $perioditems as $perioditem ) {
			if ( ! $Class || is_a( $perioditem, $Class ) ) {
				if ( $perioditem->period_entity->period_status_type->flags & $period_status_type_flags )
					array_push( $perioditems_filtered, $perioditem );
			}
		}
		return $perioditems_filtered;
  }

  protected function filter_cannot( $perioditems, $Class, $period_status_type_flags ) {
		$perioditems_filtered = array();
		foreach ( $perioditems as $perioditem ) {
			if ( ! $Class || is_a( $perioditem, $Class ) ) {
				if ( ! $perioditem->period_entity->period_status_type->flags & $period_status_type_flags )
					array_push( $perioditems_filtered, $perioditem );
			}
		}
		return $perioditems_filtered;
  }

  protected function filter_entity( $perioditems, $entity_type, $entity ) {
		$perioditems_filtered = array();
		foreach ( $perioditems as $perioditem ) {
			if ( property_exists( $perioditem->period_entity, $entity_type )
				&& $perioditem->period_entity->$entity_type->is( $entity )
			) {
				array_push( $perioditems_filtered, $perioditem );
			}
		}
		return $perioditems_filtered;
  }

  protected function filter_higher_priority( $perioditems, $priority ) {
		$perioditems_filtered = array();
		foreach ( $perioditems as $perioditem ) {
			if ( $perioditem->period_entity->period_status_type->priority >= $priority ) {
				array_push( $perioditems_filtered, $perioditem );
			}
		}
		return $perioditems_filtered;
  }

  protected function intersect( $perioditem, $perioditems ) {
		if ( count( $perioditems ) ) {
			// TODO: period intersect()
			// this functionality can wait for partial overlap requirement
		} else {
			// No valid items for intersect sent through
			$perioditem = NULL;
		}
		return $perioditem;
  }

  protected function exclude( $perioditem, $perioditems ) {
		if ( count( $perioditems ) ) {
			// Assume that these completely cover the target for now
			$perioditem = NULL;
		} else {
			// TODO: period exclude()
			// this functionality can wait for partial overlap requirement
		}
		return $perioditem;
  }
}


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_Everything extends CB_PeriodInteractionStrategy {
	function __construct( $post ) {
		parent::__construct();
	}
}


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_SingleItemAvailability extends CB_PeriodInteractionStrategy {
	function __construct( $item = NULL, $startdate = NULL, $enddate = NULL, $view_mode = 'week', $args = array() ) {
		global $post;
		$this->item = ( $item ? $item : $post );

		if ( ! isset( $args['meta_query'] ) ) $args['meta_query'] = array();
		if ( ! isset( $args['meta_query']['item_ID_clause'] ) ) $args['meta_query']['item_ID_clause'] = array(
			'key'     => 'item_ID',
			'value'   => array( $this->item->ID, 0 ),
			'compare' => 'IN',
		);

		parent::__construct( $startdate, $enddate, $view_mode, $args );
	}

	function markup( &$perioditem ) {
		$period_status_type = $perioditem->period_entity->period_status_type;

		// Prevent any none-availability perioditems being selected
		if ( ! $perioditem instanceof CB_PeriodItem_Timeframe
			|| ! $period_status_type->can( CB2_ANY_ACTION )
		) {
			$perioditem->no_select = TRUE;
		}
	}

	function dynamic_priority( $perioditem, $overlaps ) {
		/* Show a single items availability
		* requiring its location to have an associated collect / return period
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
		* NOTE: an item's location might change during the period being shown
		* all periods, e.g. opening hours, for multiple locations may be included
		* and must be filtered according to the current relevant location
		* for the current item availability
		*/
		$priority           = parent::dynamic_priority( $perioditem, $overlaps );
		$period_status_type = $perioditem->period_entity->period_status_type;

		if ( $perioditem instanceof CB_PeriodItem_Timeframe
			&& $period_status_type->can( CB2_ANY_ACTION )
		) {
			// ---------------------------------- Item availability
			$location = $perioditem->period_entity->location;
			$item     = $perioditem->period_entity->item;

			if ( ! $this->item->is( $item ) )
				throw new Exception( 'CB_SingleItemAvailability: CB_PeriodItem_Timeframe for different item' );

			// Require THE location pickup / return period
			$location_cans  = $this->filter_can( $overlaps, 'CB_PeriodItem_Location', CB2_ANY_ACTION );
			$location_cans  = $this->filter_entity( $location_cans, 'location', $location );
			$perioditem     = $this->intersect( $perioditem, $location_cans );
			if ( is_null( $perioditem ) ) {
				$priority = NULL; // Delete it
				$this->log( 'No location actions available' );
			} else {
				// Avoid Blocking period-items
				$all_cannots    = $this->filter_cannot( $overlaps, CB2_ANY_CLASS, CB2_ANY_ACTION );
				$all_cannots    = $this->filter_higher_priority( $all_cannots, $priority ); // >=
				$perioditem     = $this->exclude( $perioditem, $all_cannots );
				if ( is_null( $perioditem ) ) {
					$priority = NULL; // Delete it
					$this->log( 'blocked by priority denial' );
				}
			}
		}

		else if ( $perioditem instanceof CB_PeriodItem_Location ) {
			// ---------------------------------- Irrelevant location removal
			$location = $perioditem->period_entity->location;

			// Is this a location of any current item availabilities?
			// note that we are allowing the possibility here
			// of this single item being concurrently available in 2 separate locations
			// at the same time
			$availabilities = $this->filter_can( $overlaps, 'CB_PeriodItem_Timeframe', CB2_ANY_ACTION );
			$availabilities = $this->filter_entity( $availabilities, 'item',     $this->item );
			$availabilities = $this->filter_entity( $availabilities, 'location', $location );
			if ( ! count( $availabilities ) ) {
				$priority = NULL; // Delete it
				$this->log( 'irrelevant location period item' );
			}
		}

		else if ( $perioditem instanceof CB_PeriodItem_Timeframe
			|| $perioditem instanceof CB_PeriodItem_Timeframe_User
		) {
			// ---------------------------------- Item mismatch checks
			$item = $perioditem->period_entity->item;
			if ( $item != $this->item )
				throw new Exception( 'CB_SingleItemAvailability: CB_PeriodItem_Timeframe for different item' );
		}

		return $priority;
	}
}
