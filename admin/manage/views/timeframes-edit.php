<?php
/**
 * Template for the timeframes edit screen.
 *
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
/**
 * Interface for adding/editing timeframes.
 */
?>
<?php

		$Timeframes_Admin = new CB_Timeframes_Admin();

		$Timeframes_Admin->set_basename( basename(__FILE__) );
		$timeframe_id = $Timeframes_Admin->handle_request( $_REQUEST ); // handle adding and updating

		$edit_slug = $Timeframes_Admin->edit_slug; // set the slug from CB_Admin_Enque
		$item = $Timeframes_Admin->settings_args;

		if ( $item['timeframe_id'] ) {
			$item = $Timeframes_Admin->get_single_timeframe( $item['timeframe_id'] );
		}

		if( is_array($item) ) { // make sure that id exists
			// here we adding our custom meta box
		} else {
			echo ( __('Timeframe not found', 'commons-booking' ) );
		}

    ?>
<div class="wrap">
	<?php // meta box holder ?>
    <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
    <h1 class="wp-heading-inline">
			<?php echo $Timeframes_Admin->do_title(); ?>
    </h1>
		<h2><?php echo $Timeframes_Admin->do_subtitle(); ?></h2>
    <form id="form" method="POST">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>
        <?php /* NOTICE: here we storing id to determine will be item added or updated */ ?>
        <input type="hidden" name="timeframe_id" value="<?php echo $item['timeframe_id']; ?>"/>

        <div class="metabox-holder" id="cb_admin_metabox">
             <div id="post-body">
                 <div id="post-body-content">
                    <?php /* And here we call our custom meta box */ ?>
                    <?php do_meta_boxes('timeframe', 'normal', $item ); ?>
										<?php $Timeframes_Admin->do_form_footer(); ?>

                </div>
            </div>
        </div>
    </form>
</div>

<?php
// function to render the timeframe settings meta box
function render_timeframe_calendar_meta_box( $item ) {

	?><table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
    <tbody>
		<tr class="form-field form-field-header" style="display:none">
        <td valign="top" colspan="4">
						<input id="booking_enabled" name="booking_enabled" type="checkbox" value="booking_enabled" <?php if ( $item['booking_enabled'] ) { echo "CHECKED"; }; ?> class="checkbox">
            <label for="booking_enabled"><?php _e('Enable Bookings', 'commons-booking')?></label>
        </td>
		</tr>
		<tr class="form-field">
        <td>
            <label for="location_id"><?php _e('Location', 'commons-booking')?></label>
        </td>
        <td>
					<?php echo CB_Gui::edit_table_post_select_html('cb_location', 'location_id', $item['location_id'] ); ?>
        </td>
				<?php // add class hidden if item already selected
					$item_select_class = ($item['item_id'] == 0  ) ? '' : 'hidden';
				 ?>
        <td valign="top" class="<?php echo $item_select_class; ?>">
            <label for="item_id"><?php _e('Item', 'commons-booking')?></label>
        </td>
				<td class="<?php echo $item_select_class; ?>">
					<?php echo CB_Gui::edit_table_post_select_html('cb_item', 'item_id', $item['item_id'] ); ?>
				</td>
    </tr>
		<tr class="form-field form-field-header row-alternate">
        <td valign="top" colspan="4">
						<input id="calendar_enabled" name="calendar_enabled" type="checkbox" value="calendar_enabled" <?php if (! empty ( $item['calendar_enabled']) ) { echo "CHECKED"; }; ?> class="checkbox">
            <label for="calendar_enabled"><?php _e('Enable booking calendar', 'commons-booking')?></label>
        </td>
		</tr>
		 <tr class="form-field row-alternate">
        <td valign="top">
            <label for="date_start"><?php _e('Start Date', 'commons-booking')?></label>
        </td>
        <td>
 						<input id="date_start" name="date_start" type="date" value="<?php echo esc_attr($item['date_start'])?>" class="date">
        </td>
 				<td valign="top" colspan="2">
				 		<input id="has_end_date" name="has_end_date" type="checkbox" value="has_end_date" <?php if ($item['has_end_date']) { echo "CHECKED"; }; ?> class="checkbox">
            <label for="has_end_date"><?php _e('Set an end date', 'commons-booking')?></label>
        </td>
    </tr>
		 <tr class="form-field row-alternate">
		    <td valign="top" colspan="1">
            <label for="date_end"><?php _e('End Date', 'commons-booking')?></label>
        </td>
        <td colspan="3">
						 <input id="date_end" name="date_end" type="date" value="<?php
						 if (  $item['has_end_date'] == 1 ) { echo esc_attr($item['date_end']); } ?>" class="date">
        </td>
		</tr>
		 <tr class="form-field">
        <td valign="top">
          <label for="slot_template_select"><?php _e('Booking Mode', 'commons-booking')?></label>
				</td>
				<td valign="top">
					<?php echo CB_Gui::edit_table_slot_template_select_html('slot_template_group_id', $item['slot_template_group_id'] ); ?>
        </td>
				<td></td>
				<td></td>
    </tr>
		<tr class="form-field row-alternate">
		        <td valign="top">
            <label for="codes_enabled"><?php _e('Codes', 'commons-booking')?></label>
        </td>
        <td>
						<input id="codes_enabled" name="codes_enabled" type="checkbox" value="codes_enabled"
						<?php if ( $item['codes_enabled'] ) { echo "CHECKED"; }; ?> class="checkbox">
            <label for="codes_enabled"><?php _e('Enable booking codes', 'commons-booking')?></label>
        </td>
				<td></td>
				<td></td>
		</tr>
		 <tr class="form-field">
        <td valign="top">
            <label for="description"><?php _e('Description', 'commons-booking')?></label>
        </td>
        <td>
 						<input id="description" name="description" type="text" value="<?php echo esc_attr($item['description'])?>" class="description">
        </td>
        <td valign="top">
            <label for="owner_id"><?php _e('Owner', 'commons-booking')?></label>
        </td>
        <td>
				<?php echo CB_Gui::edit_table_owner_select_html( array('subscriber', 'editor', 'admin'), $item['owner_id'] ); ?>
        </td>
    </tr>
    </tbody>
</table>
<?php }
// function to render the timeframe generate slots/calendar meta box
function render_timeframe_generate_slots_meta_box( $item ) {
?>

<table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
		<?php if ( $item['calendar_enabled'] == 1 ) { // we use a calendar ?>
    <tbody>
			<tr class="form-field">
				<td colspan="4">
					<ul>
						<li><?php printf ( __('<strong>Start date</strong>: %s', 'commons-booking'), CB_Gui::col_format_date( $item['date_start'] ) ); ?></li>
						<li><?php // end date
					if ( $item['has_end_date'] != 1 ) {
						printf ( __('<strong>End date</strong>: No end date. Users will be able to book %d days in advance (%s).', 'commons-booking'), CB_Settings::get( 'calendar', 'limit'), CB_Gui::settings_admin_url( 'calendar' ) ); //@TODO get from settings
					} else {
						printf ( __('<strong<End date</strong>: %s', 'commons-booking'), CB_Gui::col_format_date( $item['date_end'] ) );
					}
					?></li>
						<li><?php // slot
						echo __('<strong>Slots</strong>: The following slot(s) will be created for each day: ', 'commons-booking');
						echo CB_Gui::list_slot_templates_html( $item['slot_template_group_id']);
					?></li>
					<?php //location opening times
					if ($item['exclude_location_closed']) { ?>
					<li>
					<?php
					printf( __('Opening days of the Location (%s): ', 'commons-booking'),
						CB_Gui::col_format_post( $item['location_id'], __( 'Edit', 'commons-booking' ) ) );
						echo CB_Gui::list_location_opening_times_html( $item['location_id']);
					?>
					</li>
					<?php } // end if location opening times ?>
					<?php // holidays
					if ($item['exclude_holiday_closed']) { ?>
					<li>
					<?php
					$holiday_provider = CB_Settings::get('calendar', 'holiday_provider');
						if ( ! empty ( $holiday_provider ) ) {
							 printf ( __('Holidays in %s will be closed from booking (%s).', 'commons-booking' ), $holiday_provider, CB_Gui::settings_admin_url( 'calendar' ) );
						} else {
							printf ( __('No holiday provider selected (Configure in %s).', 'commons-booking'), CB_Gui::settings_admin_url( 'calendar' ) );
						}
					?>
					<li>
					<?php } // end if location opening times ?>
					</ul>
				</td>
			</tr>
	<?php if ( isset( $item['availability'] ) &&  $item['availability']['total'] > 0 )  { // slots already have been created, so ask the user how to handle them ?>
		 <tr class="form-field cb-form-notice">
        <td valign="top" colspan="4" class="warning">
					<?php printf ( __('%d Slots have already been created for this timeframe.', 'commons-booking'),
				$item['availability']['total'] ); ?>
        </td>
		</tr>
		<tr class="form-field cb-form-danger">
        <td valign="top" colspan="4">
						<input id="regenerate_all_slots" name="regenerate_all_slots" type="checkbox" value="regenerate_all_slots" class="checkbox">
            <label for="regenerate_all_slots"><?php _e('Regenerate all slots (delete existing)', 'commons-booking')?></label>
        </td>
		</tr>
		<?php } // end if slots present ?>
		</tbody>
	<?php } else { // no calendar @TODO: delete existing slots ?>
		<tbody>
			<tr class="form-field">
				<td colspan="4">
				<?php echo __( 'No calendar will be created. Users can book the item by contacting the item owner.', 'commons-booking'); ?>
				</td>
			</tr>
		</tbody>
	<?php } // endif calendar_enabled ?>
	</table>

<?php

}
// function to render the timeframe generate slots/calendar meta box
function render_timeframe_options_meta_box( $item ) {
	CB_Settings::do_timeframe_options();
}


// function to render the timeframe generate slots meta box
function render_timeframe_view_meta_box( $item ) {

	?>
	<table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">

    <tbody>
			<?php if ( $item['booking_enabled'] != 1 ) { ?>

			<tr class="form-field cb-form-notice">
        <td valign="top" colspan="4" class="warning">
					<?php echo __('Booking is not enabled, users will not be allowed to book.', 'commons-booking'); ?>
				</td>
			</tr>
			<?php	} // end if $item['booking_enabled'] ?>
			<tr class="form-field cb-form-info-availability">
        <td valign="top" colspan="4">
					<?php
						if ( isset ($item['availability']) ) {
					echo CB_Gui::col_format_availability( $item['availability'] );
					}
					?>. Using template: <?php echo CB_Gui::list_slot_templates_html( $item['slot_template_group_id'], FALSE); ?>
        </td>
    </tr>
		 <tr class="form-field">
        <td valign="top">
            <label for="description"><?php _e('Description', 'commons-booking')?></label>
        </td>
        <td>
 					<?php echo esc_attr($item['description']); ?>
        </td>
        <td valign="top">
            <label for="owner_id"><?php _e('Owner', 'commons-booking')?></label>
        </td>
        <td>
				<?php echo CB_Gui::col_format_user( $item['owner_id'] ); ?>
        </td>
    </tr>
		 <tr class="form-field">
        <td valign="top" colspan="4">
					<a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=cb_timeframes_edit&edit=1&timeframe_id=' . $item['timeframe_id'] );?>"><?php _e('Edit', 'commons-booking')?></a>
					<a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=cb_bookings_table&timeframe_id=' . $item['timeframe_id'] );?>"><?php _e('View Bookings', 'commons-booking')?></a>
					<a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=cb_timeframes_edit&timeframe_id=' . $item['timeframe_id'] );?>&timeframe_options"><?php _e('Advanced options', 'commons-booking')?></a>
        </td>
    	</tr>
    </tbody>
</table>
@TODO: render calendar here.
<?php
	$args = array ( 'timeframe_id' => $item['timeframe_id'] );
	$timeframe_object = new CB_Timeframes( $args );
	$CB_Timeframes = $timeframe_object->get( );
	cb_get_template_part(  CB2_TEXTDOMAIN, 'timeframe', 'admin', $CB_Timeframes );



?>
<?php
} // render_timeframe_view_meta_box

?>
