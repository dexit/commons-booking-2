<?php
/**
 * Geocoding
 *
 * Handles editing of Locations (geoencoding).
 *
 * @package   Commons_Booking
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
class CB2_Geocoding
{

    /**
     * Constructor
     */
    public function __construct() {

        global $wpdb;

				add_action('cmb2_save_post_fields_geo_address', array($this, 'save_lat_lng'), 10, 3); // admin/includes/lib/cmb2/includes/CMB2.php
				add_action('admin_notices', array($this, 'admin_notice'));

    }

    /**
     * Fires after all fields have been saved.
     *
     *
     * @param int    $object_id   The ID of the current object (post)
     * @param string $updated     Array of field ids that were updated.
     *                            Will only include field ids that had values change.
     * @param array  $cmb         This CMB2 object
     */
    public function save_lat_lng($post_id, $updated, $cmb)
    {

        if (count($updated) < 1) { // nothing to see here
            return;
        }

        $address_field = array(
            'geo_address',
        );

        // if (!count(array_intersect($updated, $address_fields))) { // no updated address
        //     return;
        // }

        $data = $cmb->data_to_save;

        // Get the address ready for geocoding
        $address = $data['geo_address'];

        // Connect to OpenCage
        $geocode_api_key = CB_Settings::get('maps_api-key');

        $geocoder = new \OpenCage\Geocoder\Geocoder($geocode_api_key);

        $geo_result = $geocoder->geocode($address);

        // API key missing or invalid
        if (!$geo_result) {
            // we need to use query vars here because there is a redirection after save and before admin notices
            add_filter('redirect_post_location', array($this, 'add_missing_api_key_var'), 99);
            return;
        }

        // no results
        if (!$geo_result['total_results'] > 0) {
            add_filter('redirect_post_location', array($this, 'add_geocode_failed_var'), 99);
            return;
        }

        // Everything worked, save the lat and long

        $first = $geo_result['results'][0];

        $cmb->data_to_save['geo_latitude'] = $first['geometry']['lat'];
        $cmb->data_to_save['geo_longitude'] = $first['geometry']['lng'];

        $cmb->process_field(array
            (
                'name' => 'Latitude',
                'id' => 'geo_latitude',
                'type' => 'text',
            )
        );
        $cmb->process_field(array
            (
                'name' => 'Longitude',
                'id' => 'geo_longitude',
                'type' => 'text',
            )
        );

    }

    public function add_geocode_failed_var($location)
    {
        remove_filter('redirect_post_location', array($this, 'add_geocode_failed_var'), 99);
        return add_query_arg(array('geocode_failed' => '1'), $location);
    }

    public function add_missing_api_key_var($location)
    {
        remove_filter('redirect_post_location', array($this, 'add_missing_api_key_var'), 99);
        return add_query_arg(array('geo_api_missing' => '1'), $location);
    }

    public function admin_notice()
    {
        if (isset($_GET['geo_api_missing'])) {
            ?>
			<div class="error">
			    <p><?php _e('No valid OpenCage API key found. This location will not be shown on the map. Go to Settings to add your API key.', 'commons-booking');?></p>
			</div>
			<?php
}

        if (isset($_GET['geocode_failed'])) {
            ?>
			<div class="error">
			    <p><?php _e('Unable to find the latitude and longitude of the address. This location will not be shown on the map.', 'commons-booking');?></p>
			</div>
			<?php
}

    }
}


