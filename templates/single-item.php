
<?php // echo "template : single-item.php <br>"; ?>
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
$view_mode   = 'week'; // CB_Weeks

// ask for item id AND anything without item ID

$period_query = new WP_Query( array(
	'post_status'    => array(
		'publish',
		// PeriodItem-automatic (CB_PeriodItem_Automatic)
		// one is generated for each day between the dates
		// very useful for iterating through to show a calendar
		// They have a post_status = auto-draft
		'auto-draft'
	),
	// Although these PeriodItem-* are requested always
	// The compare below will decide
	// which generated CB_(Object) set will actually be the posts array
	'post_type'      => CB_PeriodItem::$all_post_types,
	//'post_type'      => 'perioditem',
	'posts_per_page' => -1,
	'order'          => 'ASC',        // defaults to post_date
	'date_query'     => array(
		'after'   => '2018-07-02',//$startdate->format( 'c' ),
		'before'  => $enddate->format( 'c' ),
		// This sets which CB_(ObjectType) is the resultant primary posts array
		// e.g. CB_Weeks generated from the CB_PeriodItem records
		'compare' => $view_mode,
	),
	'meta_query' => array(
		// Restrict to the current CB_Item
		'item_ID_clause' => array(
			'key'     => 'item_ID',
			'value'   => array( $item_ID, CB_Query::$meta_NULL ),
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
		'without_meta_item_ID' => CB_Query::$without_meta,
	)
) );

if ($period_query->have_posts()) { ?>

	<table class="cb-calendar">
		<thead>
			<tr>
				<?php
					// TODO: wordpress WeekStartsOn
					foreach ( CB_Query::$days as $dayname ) {
						print( "<th>$dayname</th>" );
					}
				?>
			</tr>
		</thead>
		<tbody>
			<?php the_inner_loop($period_query, 'list'); ?>
		</tbody>
	</table>


<?php } ?>


<?php
/*
while($period_query->have_posts()) :
	$period_query->the_post();
	the_title();
endwhile;
