<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 * @version 1.0
 */


global $post;
// Show the PeriodItems for this Item
// for the next month

$item_ID     = $post->ID;
$startdate   = new DateTime();
$enddate     = (clone $startdate)->add( new DateInterval('P1M') );
$view_mode   = 'week'; // CB2_Weeks

// ask for item id AND anything without item ID

$period_query = new WP_Query( array(
	'post_status'    => CB2_Post::$PUBLISH,
	// Although these PeriodItem-* are requested always
	// The compare below will decide
	// which generated CB2_(Object) set will actually be the posts array
	'post_type'      => CB2_PeriodItem::$all_post_types,
	//'post_type'      => 'perioditem',
	'posts_per_page' => -1,
	'order'          => 'ASC',        // defaults to post_date
	'date_query'     => array(
		'after'   => '2018-07-02',//$startdate->format( CB2_Query::$datetime_format ),
		'before'  => $enddate->format( CB2_Query::$datetime_format ),
		// This sets which CB2_(ObjectType) is the resultant primary posts array
		// e.g. CB2_Weeks generated from the CB2_PeriodItem records
		'compare' => $view_mode,
	),
	'meta_query' => array(
		// Restrict to the current CB2_Item
		'item_ID_clause' => array(
			'key'     => 'item_ID',
			'value'   => array( $item_ID, 0 ),
			'compare' => 'IN',
			'type'    => 'NUMERIC', // This causes the 'NULL' to be changed to NULL
		),
		'location_ID_clause' => array( // TODO this is a problem because it's returning other items that have a location id set
			'key'     => 'location_ID',
			'value'   =>  0 ,
			'compare' => '!=',
			'type'    => 'NUMERIC', // This causes the 'NULL' to be changed to NULL
		),
		// This allows PeriodItem-* with no item_ID
		// It uses a NOT EXISTS
		// Items with an item_ID which is not $item_ID will not be returned
		'relation' => 'OR',
		'without_meta_item_ID' => CB2_Query::$without_meta,
	)
) );

if ( $period_query->have_posts() ) { ?>

	<table class="cb2-calendar">
		<?php CB2::the_calendar_header(); ?>
		<tbody>
			<?php CB2::the_inner_loop($period_query, 'list'); ?>
		</tbody>
		<?php CB2::the_calendar_header(); ?>
	</table>


<?php } ?>


<?php
/*
while($period_query->have_posts()) :
	$period_query->the_post();
	the_title();
endwhile;
