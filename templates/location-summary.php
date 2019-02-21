<?php
/**
 * Item summary @TODO
 *
 * @package   CommonsBooking2
 * @author    Annesley Newholm <annesley_newholm@yahoo.it>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 *
 * @see       CB2_Enqueue::cb_template_chooser()
 */

$location = CB2_Query::get_post_with_type('location', $template_args['location_id'] );
$location_thumb = (has_post_thumbnail($location->ID)) ? get_the_post_thumbnail($location->ID, 'thumbnail') : '';
// @TODO format opening hours
$opening_hours_query = new WP_Query(array(
    'post_type' => 'periodent-location',
    'meta_query' => array(
        'location_ID_clause' => array(
            'key' => 'location_ID',
            'value' => $location->ID
        ),
        'relation' => 'AND',
        'period_status_type_clause' => array(
            'key' => 'period_status_type_id',
            'value' => CB2_PeriodStatusType_Open::$id,
        ),
    ),
));


?>
<div class="cb2-summary cb2-location-summary">
	<h3><?php echo __('Pickup & Return at:', 'commons-booking-2'); ?>
		 <a href="<?php echo esc_url(get_permalink($location->ID)); ?>"><?php echo $location->post_title; ?></a>
	</h3>
	<div class="cb2-location-thumb cb2-summary-image"><?php echo ($location_thumb); ?></div>
	<p class="cb2-location-excerpt"><?php echo ($location->post_excerpt); ?></p>
	<dl class="cb2-location-address">
    <dt><?php echo __('Address', 'commons-booking-2'); ?></dt>
		<dd><?php echo $location->geo_address ?></dd>
	</dl>
	<dl class="cb2-location-opening-hours">
		<dt><?php echo __('Opening hours', 'commons-booking-2'); ?></dt>
		<?php if ($opening_hours_query->have_posts()) {
					print('<ul class="cb2-admin-column-ul">');
					CB2::the_inner_loop(null, $opening_hours_query, 'admin', 'summary');
					print('</ul>');
			} else {
					print('<div>' . __('No Opening Hours') . '</div>');
			}
		?>
		<dd></dd>
	</dl>
</div>


