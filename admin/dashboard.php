<div class="wrap">
<?php
global $wp_query;

// --------------------------------------- Defaults
$today            = CB2_DateTime::today();
$next_week_end    = CB2_DateTime::next_week_end();

// --------------------------------------- Query Parameters
$startdate_string = ( isset( $_GET['startdate'] )   ? $_GET['startdate'] : $today->format(       CB2_Query::$datetime_format ) );
$enddate_string   = ( isset( $_GET['enddate']   )   ? $_GET['enddate']   : $next_week_end->format( CB2_Query::$datetime_format ) );
$location_ID      = ( isset( $_GET['location_ID'] ) ? $_GET['location_ID'] : NULL );
$item_ID          = ( isset( $_GET['item_ID'] )     ? $_GET['item_ID']     : NULL );
$user_ID          = ( isset( $_GET['user_ID'] )     ? $_GET['user_ID']          : NULL );
$period_group_id  = ( isset( $_GET['period_group_id'] )  ? $_GET['period_group_id'] : NULL );
$period_status_type_ID = ( isset( $_GET['period_status_type_ID'] ) ? $_GET['period_status_type_ID'] : NULL );
$period_entity_ID = ( isset( $_GET['period_entity_ID'] ) ? $_GET['period_entity_ID'] : NULL );
$schema_type      = ( isset( $_GET['schema_type'] )      ? $_GET['schema_type'] : CB2_Week::$static_post_type );
$template_part    = ( isset( $_GET['template_part'] )    ? $_GET['template_part'] : NULL );
$Class_display_strategy = ( isset( $_GET['display_strategy'] ) && $_GET['display_strategy'] ? $_GET['display_strategy'] : 'WP_Query' );
$show_debug       = isset( $_GET['show_debug'] );
$output_type      = ( isset( $_GET['output_type'] ) ? $_GET['output_type'] : 'Calendar' );
$extended_class   = ( isset( $_GET['extended'] )    ? '' : 'none' );
$show_overridden_periods = isset( $_GET['show_overridden_periods'] );
$show_blocked_periods    = isset( $_GET['show_blocked_periods'] );

// --------------------------------------- Query
// We set the global $wp_query so that all template functions will work
// And also so pre_get_posts will not bulk with no global $wp_query
wp_reset_query();

$args     = array(
	'startdate'        => $startdate_string,
	'enddate'          => $enddate_string,
	'location_ID'      => $location_ID,
	'item_ID'          => $item_ID,
	'user_ID'          => $user_ID,
	'period_group_id'  => $period_group_id,
	'period_status_type_ID' => $period_status_type_ID,
	'period_entity_ID' => $period_entity_ID,
	'schema_type'      => $schema_type,
	'template_part'    => $template_part,
	'display_strategy' => $Class_display_strategy,
	'show_debug'       => $show_debug,
	'output_type'      => $output_type,
	'extended_class'   => $extended_class,
	'show_overridden_periods' => $show_overridden_periods,
	'show_blocked_periods'    => $show_blocked_periods,
);

// TODO: meta_query assembly needs to be placed in the Strategy and generally used everywhere
$meta_query       = array();
$meta_query_items = array();
if ( $location_ID )
	$meta_query_items[ 'location_ID_clause' ] = array(
		'key' => 'location_ID',
		'value' => array( $location_ID, 0 ),
	);
if ( $item_ID )
	$meta_query_items[ 'item_ID_clause' ] = array(
		'key' => 'item_ID',
		'value' => array( $item_ID, 0 ),
	);
if ( $period_status_type_ID )
	$meta_query_items[ 'period_status_type_clause' ] = array(
		'key' => 'period_status_type_ID',
		'value' => array( $period_status_type_ID, 0 ),
	);
if ( $period_entity_ID )
	$meta_query_items[ 'period_entity_clause' ] = array(
		'key' => 'period_entity_ID',
		'value' => array( $period_entity_ID, 0 ),
	);
if ( $show_blocked_periods )
	$meta_query['blocked_clause'] = 0; // Prevent it from defaulting
else
	$meta_query['blocked_clause'] = array(
		'key'     => 'blocked',
		'value'   => '0',
	);

if ( $meta_query_items ) {
	if ( ! isset( $meta_query_items[ 'relation' ] ) )
		$meta_query_items[ 'relation' ] = 'AND';
	$meta_query[ 'entities' ] = $meta_query_items;
}

$post_status = array( CB2_Post::$PUBLISH );
if ( $show_overridden_periods )
	array_push( $post_status, CB2_Post::$TRASH );

$args = array(
	'author'         => $user_ID,
	'post_status'    => $post_status,
	'post_type'      => CB2_PeriodItem::$all_post_types,
	'posts_per_page' => -1,
	'order'          => 'ASC',          // defaults to post_date
	'date_query'     => array(
		'after'   => $startdate_string,
		'before'  => $enddate_string,
		'compare' => $schema_type,
	),
	'meta_query' => $meta_query,        // Location, Item, User
);

// Construct
if ( $Class_display_strategy == 'WP_Query' ) {
	$wp_query = new WP_Query( $args );
} else {
	$wp_query = $Class_display_strategy::factory_from_query_args( $args );
}

$title_text   = __( 'Dashboard' );
print( "<h1>Commons Booking 2 $title_text</h1>" );

if ( (new CB2_DateTime( $startdate_string ))->after( new CB2_DateTime( $enddate_string ) ) ) // PHP 5.2.2
	print( '<div class="cb2-warning cb2-notice">start date is more than end date</div>' );
?>
	<div class="cb2-calendar">
		<div class="entry-content" style="width:100%;">
			<?php CB2::the_calendar_header( $wp_query ); ?>
			<ul class="cb2-subposts">
				<!-- usually weeks -->
				<?php CB2::the_inner_loop( array( 'action' => '' ), $wp_query, 'list', $template_part ); ?>
			</ul>
			<?php CB2::the_calendar_footer( $wp_query ); ?>
		</div><!-- .entry-content -->
	</div>
</div>
