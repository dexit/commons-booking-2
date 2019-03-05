<?php
$calendar_atts = array(
	// item-ID not necessary because CB2_SingleItemAvailability uses the global post
	'schema-type'      => CB2_Week::$static_post_type,
	'display-strategy' => 'CB2_SingleItemAvailability',
	'selection-mode'   => 'range',
	'template-type'    => 'available',
);
?>
<form action='' method='POST'><div>
	<input type='hidden' name='name' value='<?php echo __('Booking of'); ?> <?php the_title(); ?>' />
	<input type='hidden' name='do_action' value='<?php echo CB2::the_Class(); ?>::book' />
	<input type='hidden' name='do_action_post_ID' value='<?php the_ID() ?>' />
	<input type='hidden' name='redirect' value='/periodent-user/%action_return_value%/' />
	<input type='submit' name='submit' value='<?php echo __('book the'); ?> <?php the_title(); ?>' />
	<?php echo CB2_Shortcodes::calendar_shortcode( $calendar_atts ); ?>
	<input type='submit' name='submit' value='<?php echo __('book the'); ?> <?php the_title(); ?>' />
</div></form>
