<?php
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

		$this->args = $args;
		$this->query();

		// Expost the private WP_Query in WP_DEBUG mode
		if ( WP_DEBUG ) $this->debug_wp_query = $this->wp_query;
	}

	// -------------------------------------------- query functions
	function query() {
		$this->wp_query = new WP_Query( $this->args );
		$this->wp_query->display_strategy = $this;
		$this->query   = $this->wp_query->query;
		$this->request = $this->wp_query->request;

		// Process here before any loop_start re-organiastion
		CB_Query::ensure_correct_classes( $this->wp_query->posts, $this );
		$this->wp_query->post = ( count( $this->wp_query->posts ) ? $this->wp_query->posts[0] : NULL );
		foreach ( $this->wp_query->posts as &$perioditem ) {
			if ( ! $perioditem instanceof CB_PeriodItem_Automatic ) {
				$overlap_perioditems = $this->overlap_perioditems( $perioditem );
				$this->process_perioditem( $perioditem, $overlap_perioditems );
			}
		}
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

  function process_perioditem( &$perioditem, $overlap_perioditems ) {
		if ( ! property_exists( $perioditem, '_cb2_processed' ) || ! $perioditem->_cb2_processed ) {
			$perioditem->priority_original   = $perioditem->priority;
			$perioditem->overlap_perioditems = $overlap_perioditems;
			$perioditem->priority = $this->dynamic_priority( $perioditem, $overlap_perioditems );
			$this->set_processed( $perioditem );
		}
		return $perioditem;
  }

  function set_processed( &$perioditem ) {
		$perioditem->_cb2_processed = TRUE;
  }

  function dynamic_priority( &$perioditem, $overlap_perioditems ) {
		// Dictate the new display order
		// only relevant for partial overlap
		// for example a morning slot overlapping a full-day open period
		return $perioditem->period_entity->period_status_type->priority;
  }

  // ---------------------------------------------------- Set methods
  function filter( $perioditems, $Class, $period_status_type_id = NULL ) {
		$perioditems_filtered = array();
		foreach ( $perioditems as &$perioditem ) {
			if ( is_a( $perioditem, $Class ) ) {
				if ( is_null( $period_status_type_id ) )
					array_push( $perioditems_filtered, $perioditem );
				else if ( $perioditem->period_status_type_id() == $period_status_type_id )
					array_push( $perioditems_filtered, $perioditem );
			}
		}
		return $perioditems_filtered;
  }

  function intersect( &$perioditem, $perioditems ) {
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
	 */
	function __construct( $item = NULL, $startdate = NULL, $enddate = NULL, $view_mode = 'week', $args = array() ) {
		global $post;
		$this->item      = ( $item ? $item : $post );

		if ( ! isset( $args['meta_query'] ) ) $args['meta_query'] = array();
		if ( ! isset( $args['meta_query']['item_ID_clause'] ) ) $args['meta_query']['item_ID_clause'] = array(
			'key'     => 'item_ID',
			'value'   => array( $this->item->ID, 0 ),
			'compare' => 'IN',
		);

		parent::__construct( $startdate, $enddate, $view_mode, $args );
	}

	function dynamic_priority( &$perioditem, $overlaps ) {
		$priority = 0;
		if ( $perioditem instanceof CB_PeriodItem_Timeframe ) {
			// Intersect with location open
			// TODO: Should we intersect with Collect flag instead?
			$location_opens = $this->filter( $overlaps, 'CB_PeriodItem_Location', CB_PeriodStatusType_Open::$id );
			$this->intersect( $perioditem, $location_opens );
		} else {
			// Return existing priority
			$priority = parent::dynamic_priority( $perioditem, $overlaps );
		}
		return $priority;
	}
}
