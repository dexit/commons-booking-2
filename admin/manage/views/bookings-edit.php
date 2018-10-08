<?php
/**
 * Template for the bookings edit screen.
 *
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
/**
 * Interface for editing bookings.
 */
?>
<?php

		$Bookings_Admin = new CB_Bookings_Admin();

		$defaults = $Bookings_Admin->default_fields;
		$Bookings_Admin->set_basename( basename(__FILE__) );
		$Bookings_Admin->handle_request( $_REQUEST ); // handle adding and updating

		global $wpdb;
		$bookings_table = $wpdb->prefix . CB_BOOKINGS_TABLE;

		$edit_slug = $Bookings_Admin->edit_slug; // set the slug from CB_Admin_Enque

    // this is default $item which will be used for new records
    $default = array(
        'booking_id' => 0,
        'booking_status' => ''
    );

		// if this is not post back we load item to edit or give new one to create
		$item_id = $Bookings_Admin->get_booking_id_from_request( $_REQUEST );
    $item = $Bookings_Admin->get_booking( $item_id );

		if( is_array($item) ) { // make sure that id exists
			add_meta_box('bookings_form_meta_box', __('Booking', 'commons-booking') , 'render_meta_box' , 'booking', 'normal', 'default');
			// here we adding our custom meta box
		} else {
			echo ( __('Booking not found', 'commons-booking' ) );
		}

    ?>
<div class="wrap">
    <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
    <h2><?php _e('Bookings', 'commons-booking')?> <a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=cb_bookings_table' );?>"><?php _e('back to list', 'commons-booking')?></a>
    </h2>

    <?php if (!empty($notice)): ?>
    <div id="notice" class="error"><p><?php echo $notice ?></p></div>
    <?php endif;?>
    <?php if (!empty($message)): ?>
    <div id="message" class="updated"><p><?php echo $message ?></p></div>
    <?php endif;?>

    <form id="form" method="POST">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>
        <?php /* NOTICE: here we storing id to determine will be item added or updated */ ?>
        <input type="hidden" name="booking_id" value="<?php echo $item[0]['booking_id'] ?>"/>

        <div class="metabox-holder" id="cb_admin_metaboxgrunt">
            <div id="post-body">
                <div id="post-body-content">
                    <?php /* And here we call our custom meta box */ ?>
                    <?php do_meta_boxes('booking', 'normal', $item); ?>
                    <input type="submit" value="<?php _e('Save', 'commons-booking')?>" id="submit" class="button-primary" name="submit">
                </div>
            </div>
        </div>
    </form>
</div>

<?php
// function to render the actual metabox contents
function render_meta_box( $slots ) {

	$info = $slots[0];
	$Bookings_Admin = new CB_Bookings_Admin; // we need the class to format out entries.

	?>
<table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
    <tbody>
		 <tr class="form-field">
        <th valign="top" scope="row">
            <label for="user"><?php _e('Name', 'commons-booking')?></label>
        </th>
        <td>
						<?php echo CB_Gui::col_format_user($info['user_id']); ?>
        </td>
    </tr>
		<!-- @TODO pull in more user info here -->
		 <tr class="form-field">
        <th valign="top" scope="row">
            <label for="date_time"><?php _e('Booking Date & time', 'commons-booking')?></label>
        </th>
        <td>
						<?php echo CB_Gui::col_format_date_time($info['booking_time']); ?>
        </td>
    </tr>
		 <tr class="form-field">
        <th valign="top" scope="row">
            <label for="item"><?php _e('Item', 'commons-booking')?></label>
        </th>
        <td>
						<?php echo CB_Gui::col_format_post($info['item_id']); ?>
        </td>
    </tr>
		 <tr class="form-field">
        <th valign="top" scope="row">
            <label for="location"><?php _e('Location', 'commons-booking')?></label>
        </th>
        <td>
						<?php echo CB_Gui::col_format_post($info['location_id']); ?>
        </td>
    </tr>
		 <tr class="form-field">
        <th valign="top" scope="row">
            <label for="status"><?php _e('Status', 'commons-booking')?></label>
        </th>
        <td>
 						<input id="booking_status" name="booking_status" type="text" style="width: 95%" value="<?php echo esc_attr($info['booking_status'])?>"
                   size="50" class="code" placeholder="<?php _e('Status', 'commons-booking')?>" required>
        </td>
    </tr>
		<?php foreach ( $slots as $slot ) { // loop through slots ?>
		 <tr class="form-field">
        <th valign="top" scope="row">
            <label for="date_time"><?php _e('Date & time', 'commons-booking')?></label>
        </th>
        <td>
						<?php echo CB_Gui::col_format_date($slot['date']); ?>:
						<?php echo CB_Gui::col_format_date($slot['time_start']); ?> -
						<?php echo CB_Gui::col_format_date($slot['time_end']); ?>
        </td>
    </tr>
		<?php } ?>
    </tbody>
</table>
<?php } ?>
