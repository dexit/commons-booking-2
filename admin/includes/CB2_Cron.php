<?php
/**
 * CB Cron functions
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 * @see 			CB2_Cronplus
 */
class CB2_Cron {

	/**
	 * Constructor
	 */
	public function __construct() {

	}
	/**
	 * Cron job: Extend the timeframes that have no defined ending to the cal_limit
	 *
	 * @uses CB2_Timeframes
	 * @uses CB2_Settings
	 */
	public function extend_timeframes() {

		global $wpdb;
		$timeframes_table = $wpdb->prefix . CB2_TIMEFRAMES_TABLE;

		// 1. get timeframes that have no end date
		$timeframe_args = array ( 'has_end_date' => 0, 'scope' => '' );

		$timeframes_object = new CB2_Timeframes( $timeframe_args );
		$timeframes = $timeframes_object->get();

		// 2. get cal limit from settings, calculate how many days to add
		$cal_limit =  intval ( CB2_Settings::get( 'calendar', 'limit' ) );
		$new_end_date = date ( 'Y-m-d', strtotime( '+' . $cal_limit . 'days' ) );
		$new_mod_date = date( 'Y-m-d H:i:s' );

		// 3. extend each timeframe
		$results_array = array();

		if ( is_array ( $timeframes ) ) {

			foreach ( $timeframes as $tf ) {

				if ( strtotime ( $tf->date_end ) < strtotime ( $new_end_date ) ) {

					$sql_timeframe_result = $wpdb->update(
						$timeframes_table,
							array(
								'date_end' => $new_end_date,
								'modified' => $new_mod_date
							),
							array(
								'timeframe_id' => $tf->timeframe_id
							),
							array(
								'%s',	// date_end
								'%s' // modified
							),
							array( '%d' )
						);

						$generate_slots_result = 0;

						if ( $sql_timeframe_result == 1 ) { // update successful

							$tf->date_end = $new_end_date;
							// 4. create slots
							$slots = new CB2_Slots( $tf->timeframe_id );
							$generate_slots_result = $slots->re_generate_slots_function( $tf );
						} // end if update successful

						// push results
						$results_array[] = array ( $sql_timeframe_result, $generate_slots_result );

				} // end if
			} // end foreach

		}

		// 5. provide debug info

	}

}
