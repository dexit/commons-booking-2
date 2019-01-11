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
class CB2_Shortcodes {

	private $default_query_args = array(
		'period_id'        => FALSE,
		'location_id'      => FALSE,
		'item_id'          => FALSE,
		'view_mode'        => 'week',
		'display-strategy' => 'CB2_Everything',
		'selection-mode'   => NULL,
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
			'display-strategy',
			'selection-mode',
		);

		/* TODO: is this for multiple value input?
		foreach($array_atts_fields as $field) {
			if (isset($atts[$field])) {
				$atts[$field] = explode(',', $atts[$field]);
			}
		}
		*/
		$args = shortcode_atts( $this->default_query_args, $atts, 'cb_calendar' );
		$display_strategy_classname = $args['display-strategy'];
		$selection_mode = ( isset( $args['selection-mode'] ) ? $args['selection-mode'] : 'none' );

		$display_strategy = new $display_strategy_classname( $post );
		// if ( WP_DEBUG ) krumo( $display_strategy );

		$html  = "<div class='cb2-selection-container cb2-selection-mode-$selection_mode'>";
		foreach ( $args as $name => $value ) {
			$name = str_replace( '-', '_', $name );
			$html .= "<input type='hidden' name='$name' value='$value'/>";
		}

		if ( $display_strategy->have_posts() ) {
				$html .= '<table class="cb2-calendar">';
					$html .= CB2::get_the_calendar_header( $display_strategy );
					$html .= '<tbody>';

					while ( $display_strategy->have_posts() ) {
						$display_strategy->the_post();
						$html .= cb2_get_template_part(  CB2_TEXTDOMAIN, 'list', 'week-available', $args , true );
					}

					$html .= '</tbody>';
					$html .= CB2::get_the_calendar_footer( $display_strategy );
				$html .= '</table>';
		} else {
			$html .= '<div>No Results</div>';
		}
		$html .= '</div>';

		// reset posts
		wp_reset_postdata();

		return $html;
	}
}
