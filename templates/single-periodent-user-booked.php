<?php



global $post;

cb2_tag(
    CB2_Settings::get('messagetemplates_please-confirm'),
    'periodent-user',
    $this->booking_id,
    'notice'
);


// var_dump($post);

cb2_get_template_part(CB2_TEXTDOMAIN, 'item-summary', '', array('item_id' => $item_id), true);
cb2_get_template_part(CB2_TEXTDOMAIN, 'location-summary', '', array('location_id' => $location_id), true);
cb2_get_template_part(CB2_TEXTDOMAIN, 'user-summary', '', array('user_id' => $user_id), true);

// cb2_get_template_part(CB2_TEXTDOMAIN, 'booking-ui-confirm', '', array('user_id' => $user_id), true);



the_author();
echo('hello');
krumo( current_user_can( 'edit-post' ) );

// $post->confirm(TRUE);


krumo( CB2::is_confirmed() );
