<?php
new Fake_Page(
	  array(
    'slug' => 'fake_slug',
    'post_title' => 'Fake Page Title',
    'post_content' => testing()
	  )
);

function testing() {

//     // return ( CB_Strings::get_string( 'category', 'key' ) );

// // see https://github.com/flegfleg/cb-temp/blob/master/classes/CB_Object.php#L161
// $args = array (
// 	'item_id' => get_the_id(), // This template is called in the loop, so you need to supply the id
// 	'has_bookable_slots' => TRUE,
// 	'discard_empty' => TRUE,
// );

// $args = array();
// $tf = new CB_Timeframes();
// $tf->set_context('timeframe'); // either 1) 'timeframe' (default): group by timeframe or 2) 'calendar': group by date or 3) 'admin_table'
// $timeframes = $tf->get_timeframes( $args );
// var_dump($timeframes);





// $setting = CB_Settings::get( 'bookings', 'max-slots');

// $codes_string = CB_Settings::get( 'codes', 'codes-pool');


}
