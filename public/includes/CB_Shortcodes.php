<?php
/**
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
/**
 * This class contains the shortcode for map and calendar
 */
class CB_Shortcodes {

	private $default_query_args = array(
		'period_id'  => false,
		'location_id'  => false,
		'item_id'  => false,
		//'owner_id'  => false,
		//'location_cat'  => false,
		//'item_cat'   => false

	);

	/**
	 * Render a calendar (multiple periods on one calendar)
	 *
	 * @param array $atts
	 */
	public function calendar_shortcode ( $atts ) {

		$return = '';
		/*
		These fields pass in a single value or a list of comma separated values. They need to be converted to an array before merging with the default args.
		*/

		$array_atts_fields = array(
			'period_id',
			'location_id',
			'item_id',
			//'owner_id',
			//'location_cat',
			//'item_cat'
		);

		foreach($array_atts_fields as $field) {
			if (isset($atts[$field])) {
				$atts[$field] = explode(',', $atts[$field]);
			}
		}

		$args = shortcode_atts( $this->default_query_args, $atts, 'cb_calendar' );
		// cb_get_template_part( $plugin_slug, $slugs, $name = '', $template_args = array(), $return = false, $cache_args = array() )
		/*
		$this->set_context('calendar');
		$timeframes = $this->get_timeframes( $args );


		*/
		// Do query here

		global $post;
		// Show the PeriodItems for this Item
		// for the next month

		$item_ID     = 5;
		$startdate   = new DateTime();
		$enddate     = (clone $startdate)->add( new DateInterval('P1M') );
		$view_mode   = 'week'; // CB_Weeks

		// ask for item id AND anything without item ID

		$period_query = new WP_Query( array(
			'post_status'    => array(
				'publish',
				// PeriodItem-automatic (CB_PeriodItem_Automatic)
				// one is generated for each day between the dates
				// very useful for iterating through to show a calendar
				// They have a post_status = auto-draft
				'auto-draft'
			),
			// Although these PeriodItem-* are requested always
			// The compare below will decide
			// which generated CB_(Object) set will actually be the posts array
			'post_type'      => CB_PeriodItem::$all_post_types,
			//'post_type'      => 'perioditem',
			'posts_per_page' => -1,
			'order'          => 'ASC',        // defaults to post_date
			'date_query'     => array(
				'after'   => $startdate->format( 'c' ),
				'before'  => $enddate->format( 'c' ),
				// This sets which CB_(ObjectType) is the resultant primary posts array
				// e.g. CB_Weeks generated from the CB_PeriodItem records
				'compare' => $view_mode,
			),
			'meta_query' => array(
				// Restrict to the current CB_Item
				'item_ID_clause' => array(
					'key'     => 'item_ID',
					'value'   => array( $item_ID, CB_Query::$meta_NULL ),
					'compare' => 'IN',
					'type'    => 'NUMERIC', // This causes the 'NULL' to be changed to NULL
				),
				'location_ID_clause' => array( // TODO this is a problem because it's returning other items that have a location id set
					'key'     => 'location_ID',
					'value'   =>  0 ,
					'compare' => '!=',
					'type'    => 'NUMERIC', // This causes the 'NULL' to be changed to NULL
				),
				// This allows PeriodItem-* with no item_ID
				// It uses a NOT EXISTS
				// Items with an item_ID which is not $item_ID will not be returned
				'relation' => 'OR',
				'without_meta_item_ID' => CB_Query::$without_meta,
			)
		) );

		if ($period_query->have_posts()) {

			$return = '<div class="cb2-calendar"><header class="entry-header"><h1 class="entry-title">Calendar</h1></header>

				<table class="cb-calendar">
					<thead>
						<tr>';
							    for($i=1;$i<8;$i++) {
									$return .= '<th>' . date("D",mktime(0,0,0,3,28,2009)+$i * (3600*24)) . '</th>';
								}

						$return .= '</tr>
					</thead>
					<tbody>';
					while($period_query->have_posts()) {
						$period_query->the_post();

						// call ensure class
						//cb2_template_include_ensure_correct_class();
						$return .= cb_get_template_part(  CB2_TEXTDOMAIN, 'list', 'week', $args , true );
					}

					$return .= '</tbody>
				</table>
			</div>';

		}



		// reset posts
		wp_reset_postdata();

		return $return;
	}


}
