<h1>calendar</h1>
<?php
// --------------------------------------- Query Parameters
$startdate_string = ( isset( $_GET['startdate'] )   ? $_GET['startdate'] : '2018-09-01 00:00:00' );
$enddate_string   = ( isset( $_GET['enddate']   )   ? $_GET['enddate']   : '2018-10-01 00:00:00' );
$location_ID      = ( isset( $_GET['location_ID'] ) ? $_GET['location_ID'] : NULL );
$item_ID          = ( isset( $_GET['item_ID'] )     ? $_GET['item_ID']     : NULL );
$user_ID          = ( isset( $_GET['user_ID'] )     ? $_GET['user_ID']          : NULL );
$period_group_id  = ( isset( $_GET['period_group_id'] ) ? $_GET['period_group_id'] : NULL );
$period_status_type_ID = ( isset( $_GET['period_status_type_ID'] ) ? $_GET['period_status_type_ID'] : NULL );
$schema_type      = ( isset( $_GET['schema_type'] )   ? $_GET['schema_type'] : CB_Week::$static_post_type );
$template_part    = ( isset( $_GET['template_part'] ) ? $_GET['template_part'] : NULL );
$no_auto_draft    = isset( $_GET['no_auto_draft'] );
$output_type      = ( isset( $_GET['output_type'] ) ? $_GET['output_type'] : 'HTML' );

// --------------------------------------- Query
$meta_query       = array();
$meta_query_items = array();
$post_status      = array( 'publish' );
if ( ! $no_auto_draft ) array_push( $post_status, 'auto-draft' );
if ( $location_ID )
	$meta_query_items[ 'location_clause' ] = array(
		'key' => 'location_ID',
		'value' => $location_ID
	);
if ( $item_ID )
	$meta_query_items[ 'item_clause' ] = array(
		'key' => 'item_ID',
		'value' => $item_ID
	);
if ( $period_status_type_ID )
	$meta_query_items[ 'period_status_type_clause' ] = array(
		'key' => 'period_status_type_ID',
		'value' => $period_status_type_ID
	);

if ( $meta_query_items ) {
	// Include the auto-draft which do not have meta
	$meta_query[ 'relation' ]       = 'OR';
	$meta_query[ 'without_meta' ]   = CB_Query::$without_meta;
	$meta_query_items[ 'relation' ] = 'AND';
	$meta_query[ 'items' ]          = $meta_query_items;
}

$args = array(
	'author'         => $user_ID,
	'post_status'    => $post_status,   // auto-draft indicates the pseudo Period A created for each day
	'post_type'      => CB_PeriodItem::$all_post_types,
	'posts_per_page' => -1,
	'order'          => 'ASC',          // defaults to post_date
	'show_overridden_periods' => 'yes', // TODO: doesnt work yet: use the query string
	'date_query'     => array(
		'after'   => $startdate_string,   // TODO: Needs to compare enddate > after
		'before'  => $enddate_string,     // TODO: Needs to compare startdate < before
		'compare' => $schema_type,
	),
	'meta_query' => $meta_query,        // Location, Item, User
);
$query = new WP_Query( $args );

// --------------------------------------- Filter selection Form
print( '<form>' );
print( "<input name='page' type='hidden' value='cb2-calendar'/>" );
print( "<input name='startdate' value='$startdate_string'/> =&gt; " );
print( "<input name='enddate' value='$enddate_string'/><br/>" );
print( 'Location: <select name="location_ID">'  . CB_Forms::select_options( CB_Forms::location_options(), $location_ID ) . '</select> ' );
print( 'Item: <select name="item_ID">'          . CB_Forms::select_options( CB_Forms::item_options(), $item_ID ) . '</select> ' );
print( 'User: <select name="user_ID">'          . CB_Forms::select_options( CB_Forms::user_options(), $user_ID ) . '</select> ' );
print( 'Period Status: <select name="period_status_type_ID">' . CB_Forms::select_options( CB_Forms::period_status_type_options(), $period_status_type_ID, TRUE ) . '</select> ' );
print( '<br/>' );
print( 'Output type:<select name="output_type">'     . CB_Forms::select_options( array( 'HTML' => 'HTML', 'JSON' => 'JSON' ), $output_type ) . '</select>' );
print( 'Post Type:<select name="schema_type">'       . CB_Forms::select_options( CB_Forms::schema_options(), $schema_type ) . '</select>' );
print( 'Template Part:<select name="template_part">' . CB_Forms::select_options( array( 'available' => 'available' ), $template_part ) . '</select>' );
print( '<br/>' );
print( " <input id='no_auto_draft' type='checkbox' name='no_auto_draft'/> <label for='no_auto_draft'>Exclude pseudo-periods (A)</label>" );
print( " <input id='show_overridden_periods' type='checkbox' name='show_overridden_periods'/> <label for='show_overridden_periods'>show overridden periods</label>" );
print( '<br/>' );
print( '<input class="cb2-submit" type="submit" value="Filter"/>' );
print( '</form>' );

// --------------------------------------- HTML calendar output
print( "<hr/>" );
if ( $output_type == 'HTML' && ( $schema_type == 'location' || $schema_type == 'item' || $schema_type == 'user'  || $schema_type == 'form' ) )
	print( '<div class="cb2-help">Calendar rendering of locations / items / users / forms maybe better in JSON output type</div>' );
$post_count = count( $query->posts );
if ( $post_count ) {
	$post_types = array();
	foreach ( $query->posts as $post )
		$post_types[$post->post_type] = $post->post_type;
	print( "<div><b>$post_count</b> posts returned" );
	print( ' containing only <b>[' . implode( ', ', $post_types ) . "]</b> post_types</div>" );
	print( "<div style='border:1px solid #000;padding:3px;background-color:#fff'>
		<div><b>NOTE</b>: the GROUP BY clause will fail if run with sql_mode=only_full_group_by</div>
		<div style='margin-left:5px;color:#448;'>$query->request</div>
		</div>" );
} else print( "<div>No posts returned!</div>" );
krumo( $query );

switch ( $output_type ) {
	case 'JSON':
		print( '<pre>' );
		print( wp_json_encode( $query, JSON_PRETTY_PRINT ) );
		print( '</pre>' );
		break;
	case 'HTML':
		?>
		<div class="cb2-calendar">
			<div class="entry-content">
				<table class="cb2-subposts"><tbody>
					<?php the_inner_loop( $query, 'list', $template_part ); ?>
				</tbody></table>
			</div><!-- .entry-content -->
		</div>
	<?php
		break;
}
?>
