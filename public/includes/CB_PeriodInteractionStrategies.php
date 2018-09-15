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
	function __construct( $all_periods ) {
		$this->all_periods = $all_periods;
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
		return $this->overlaps_time(    $perioditem1, $perioditem2 )
			&&   $this->overlaps_locaton( $perioditem1, $perioditem2 )
			&&   $this->overlaps_item(    $perioditem1, $perioditem2 );
  }

  function process_all() {
		// Iterate over all the periods
		// re-arranging their priorities
		// or removing them
		foreach ( $this->all_periods as $perioditem )
			$this->process_period( $perioditem );
  }

  function process_period( CB_PeriodItem &$perioditem ) {
		$perioditem->priority = $this->dynamic_priority( $perioditem );
  }

  function dynamic_priority( $perioditem ) {
		// Dictate the new display order
		// only relevant for partial overlap
		// for example a morning slot overlapping a full-day open period
		return $perioditem->period_entity->period_status_type->priority;
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
	function __construct( $all_periods, CB_Item $item, $require_location_open = TRUE ) {
		parent::__construct( $all_periods );
		$this->item = $item;
		$this->require_location_open = $require_location_open;
	}

	function process_period( CB_PeriodItem &$period ) {
		if ( $period instanceof CB_PeriodItem_User ) {
			parent::process_period( $period );
		}
	}
}
