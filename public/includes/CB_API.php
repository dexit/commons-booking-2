<?php
/**
 * API
 *
 * Reachable via mysite.de/cb-api/
 *
 * @package   Commons_Booking
 * @author
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
class CB_API {

	public $api_uri = 'cb-api';
	public $query_args = array();

	/**
	 * Constructor
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		// add wp hooks
		add_action( 'parse_request', array ($this, 'do_endpoint' ), 0);

	}
	/**
	 * Do endpoint
	 *
	 * @since 2.0.0
	 */
	public static function do_endpoint () {

		global $wp;
    $query_vars = $wp->query_vars;

    // if matches endpoint uri
    if ($wp->request == $this->api_uri ) {

        $this->process_endpoint( $_REQUEST ); // process request
        // wp_redirect(home_url()); // maybe redirect home
				exit;

    }
	}
/**
 * Process endpoint
 *
 * @since 2.0.0
 *
 * @return string $json
 */
public function process_endpoint( $request ) {

	// @TODO query args -> args
	$this->query_args = array();

	// get timeframes
	$data = $this->get_timeframes();
	// var_dump ( $data );
	$json = wp_json_encode ($data);

	echo $json; // for now


}
/**
 * Get timeframes via CB_Timeframes
 *
 * @since 2.0.0
 *
 * @uses CB_Timeframes
 */
public function get_timeframes() {

	$timeframes_object = new CB_Timeframes();
	$timeframes_object->set_context( 'api' ); // see: CB_Object:550 -- i would still need to update the query function to your specs.

	$timeframes = $timeframes_object->get( $this->query_args );
	return $timeframes;
}

}
