<?php

/**
 * API
 *
 * Reachable via mysite.de/cb2-api/
 *
 * @package   CommonsBooking2
 * @author
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
class CB2_API
{
	/**
	 * Constructor
	 *
	 * @since 2.0.0
	 */
	public function __construct()
	{
		add_action('rest_api_init', function () {
			register_rest_route('commons-booking-2/v1', '/items', array(
				'methods' => 'GET',
				'callback' => array($this, 'get_items'),
			));
		});
	}
	/**
	 * Do endpoint
	 *
	 * @since 2.0.0
	 */
	public static function get_items()
	{
		$strat = new CB2_AllItemAvailability(NULL, NULL, 'item');
		return $strat->get_api_data('1.0.0');
	}

}
