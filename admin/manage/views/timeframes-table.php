<?php
/**
 * Bookings List Table
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
/**
 * Display bookings in a list table.
 */
?>
<?php

		global $wpdb;

		$edit_slug = 'cb_timeframes_edit'; // set the slug from CB_Admin_Enque

    $table = new CB_Timeframes_Table();
    $table->prepare_items();

		// @TODO: use new WP_Admin_Notice
    $message = '';
    if ('delete' === $table->current_action()) {

        $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Items deleted: %d', 'commons-booking'), count($_REQUEST['timeframe_id'])) . '</p></div>';
    }
    ?>
<div class="wrap">

    <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
    <h2><?php _e('Timeframes', 'commons-booking')?> <a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=' . $edit_slug . '&edit=1'); ?>"><?php _e('Add new', 'commons-booking')?></a>
    </h2>
    <?php //echo new WP_Admin_Notice('', 'updated'); ?>

    <form id="persons-table" method="GET">
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
        <?php $table->display() ?>
    </form>

</div>
