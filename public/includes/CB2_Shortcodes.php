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
		global $post;

		$html = '';
		/*
		These fields pass in a single value or a list of comma separated values. They need to be converted to an array before merging with the default args.
		*/

		$array_atts_fields = array(
			'period_id',
			'location_id',
			'item_id',
			'startdate',
			'enddate',
			'view_mode',
		);

		foreach($array_atts_fields as $field) {
			if (isset($atts[$field])) {
				$atts[$field] = explode(',', $atts[$field]);
			}
		}
		$args = shortcode_atts( $this->default_query_args, $atts, 'cb_calendar' );

		$display_strategy = new CB_SingleItemAvailability( $post );
		if ( WP_DEBUG && FALSE ) {
			//krumo( $display_strategy );
			print( "<div style='border:1px solid #000;padding:3px;font-size:10px;background-color:#fff;margin:1em 0em;'>$display_strategy->request</div>" );
		}

		if ($display_strategy->have_posts()) {
			$html = '<div class="cb2-calendar"><header class="entry-header"><h1 class="entry-title">Calendar</h1></header>
				<table class="cb-calendar">';
					$html .= get_the_calendar_header( $display_strategy );
					$html .= '<tbody>';

					while($display_strategy->have_posts()) {
						$display_strategy->the_post();

						// call ensure class
						// cb2_the_content();
						$html .= cb_get_template_part(  CB2_TEXTDOMAIN, 'list', 'week-available', $args , true );
					}

					$html .= '</tbody>';
					$html .= get_the_calendar_footer( $display_strategy );
				$html .= '</table>
			</div>';
		}



		// reset posts
		wp_reset_postdata();

		return $html;
	}
}
