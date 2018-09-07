<style>
	label {
		cursor:pointer;
	}

	.cb2-calendar {
		width:100%;
		border-collapse:collapse;
	}

	/* ---------------------- layout */
	.cb2-calendar > tbody  > tr {
		padding:5px;
		margin:2px;
		overflow-y:hidden;
	}
	.cb2-calendar > .entry-content > table.cb2-subposts > tbody > tr > td.cb2-empty-pre-cell {
		border:none;
	}
	.cb2-calendar > .entry-content > table.cb2-subposts > tbody  > tr > td > .entry-content > table.cb2-subposts {
		position:relative;
		height:200px;
	}
	.cb2-calendar > .entry-content > table.cb2-subposts > tbody  > tr > td > .entry-content > table.cb2-subposts > tbody > tr {
		/* position:absolute; */
		border:1px solid #d7d7d7;
	}
	.cb2-calendar > .entry-content > table.cb2-subposts > tbody  > tr > td > .entry-content > table.cb2-subposts > tbody > tr > td {
		position:relative;
	}
	.cb2-calendar > .entry-content > table.cb2-subposts > tbody  > tr > td {
		border:1px solid #d7d7d7;
		vertical-align: top;
		overflow:hidden;
		padding:5px;
	}

	/* ---------------------- indicators */
	.cb2-perioditem-has-overlap {
		opacity: 0.5;
		color: #333;
		border: 1px dotted #000;
	}
	.cb2-perioditem-has-overlap .cb2-period-period-status-type-name .cb2-field-value {
		background-color:#777;
	}
	.cb2-indicators ul {
		list-style:none;
		padding:0px;
	}
	.cb2-indicators ul li {
		float:left;
	}
	.cb2-indicators ul .cb2-indicator-collect,
	.cb2-indicators ul .cb2-indicator-return,
	.cb2-indicators ul .cb2-indicator-use {
		display:none;
	}

	.cb2-indicator-no-collect {
		background-color:#88ff88;
	}
	.cb2-indicator-no-return {
		background-color:#ff8888;
	}
	.cb2-indicator-no-use {
		background-color:#ff0000;
	}

	/* ---------------------- fields */
	.type-PeriodItem .entry-header {
		display:none;
	}
	.type-day > .entry-header > .entry-title {
		color:#888;
		margin:0px;
	}
	.cb2-calendar .type-day.cb2-current > .entry-header > .entry-title {
		color:#111;
		font-weight:bold;
		background-color:#c7c7c7;
	}
	.cb2-calendar .type-week.cb2-current  > td:first-child,
	.cb2-calendar .type-month.cb2-current > td:first-child {
		border-left:3px double #dd0000;
	}
	.cb2-time {
		margin-right:4px;
		color:#444444;
	}
	.cb2-field-name {
		display:none;
	}
	.cb2-period-period-status-type-name .cb2-field-value {
		color:#fff;
		background-color:#000;
		font-size:10px;
		padding:1px 4px;
	}

	.cb2-booked {
		background-color:#ffdddd;
	}

	/* ---------------------- Entity types */
	.cb2-period-group-type {
		font-size: 10px;
		font-weight:bold;
		color:#fff;
		background-color:#000;
		margin-right:2px;
	}
	.cb2-period-group-type .cb2-field-value {
		writing-mode: vertical-rl;
		text-orientation: upright;
	}
	.cb2-period-group-type-A .cb2-period-group-type {
	}
	.cb2-period-group-type-G .cb2-period-group-type {
		background-color:#ff8888;
	}
	.cb2-period-group-type-L .cb2-period-group-type {
		background-color:#88ff88;
	}
	.cb2-period-group-type-I .cb2-period-group-type {
		background-color:#8888ff;
	}
	.cb2-period-group-type-U .cb2-period-group-type {
		background-color:#ffff88;
	}

	.cb2-help {
		color:#773333;
		font-family:sans;
		font-size:11px;
	}
	.cb2-submit {
		height:40px;
		font-weight:bold;
	}
	h2 {
		font-size: 16px;
		margin-bottom:0px;
		margin-top:4px;
	}
	h2 a {
		font-size:11px;
		text-decoration:none;
	}
	#cb2-calendar-actions {
		float:right;
	}
</style>

<h1>calendar</h1>
<?php
// --------------------------------------- Create new item
$post_location_ID = ( isset( $_POST['location_ID'] ) && $_POST['location_ID'] ? $_POST['location_ID'] : NULL );
$post_item_ID     = ( isset( $_POST['item_ID'] )     && $_POST['item_ID']     ? $_POST['item_ID']     : NULL );
$post_user_ID          = ( isset( $_POST['user_ID'] )          && $_POST['user_ID']          ? $_POST['user_ID']          : NULL );
$post_period_status_type_id = ( isset( $_POST['period_status_type_id'] ) && $_POST['period_status_type_id'] ? $_POST['period_status_type_id'] : NULL );

$post_name = ( isset( $_POST['period_group_name'] ) ? $_POST['period_group_name'] : '' );
$post_datetime_part_period_start = ( isset( $_POST['datetime_part_period_start'] ) ? $_POST['datetime_part_period_start'] : '2018-07-02 09:00:00' );
$post_datetime_part_period_end   = ( isset( $_POST['datetime_part_period_end'] ) ? $_POST['datetime_part_period_end'] : '2018-07-02 13:00:00' );
$post_datetime_from              = ( isset( $_POST['datetime_from'] ) ? $_POST['datetime_from'] : NULL );
$post_datetime_to                = ( isset( $_POST['datetime_to'] ) ? $_POST['datetime_to'] : NULL );

$post_recurrence_type      = ( isset( $_POST['recurrence_type'] ) && $_POST['recurrence_type'] ? $_POST['recurrence_type'] : NULL );
$post_recurrence_frequency = ( isset( $_POST['recurrence_frequency'] ) && $_POST['recurrence_frequency'] ? $_POST['recurrence_frequency'] : NULL );
$post_recurrence_sequence  = ( isset( $_POST['recurrence_sequence'] ) && $_POST['recurrence_sequence'] ? $_POST['recurrence_sequence'] : NULL );

// --------------------------------------- Query Parameters
$startdate_string = ( isset( $_GET['startdate'] )   ? $_GET['startdate'] : '2018-09-01 00:00:00' );
$enddate_string   = ( isset( $_GET['enddate']   )   ? $_GET['enddate']   : '2018-10-01 00:00:00' );
$location_ID      = ( isset( $_GET['location_ID'] ) ? $_GET['location_ID'] : NULL );
$item_ID          = ( isset( $_GET['item_ID'] )     ? $_GET['item_ID']     : NULL );
$user_ID          = ( isset( $_GET['user_ID'] )     ? $_GET['user_ID']          : NULL );
$period_group_id  = ( isset( $_GET['period_group_id'] ) ? $_GET['period_group_id'] : NULL );
$period_status_type_id = ( isset( $_GET['period_status_type_id'] ) ? $_GET['period_status_type_id'] : NULL );
$schema_type      = ( isset( $_GET['schema_type'] )   ? $_GET['schema_type'] : CB_Week::$static_post_type );
$template_part    = ( isset( $_GET['template_part'] ) ? $_GET['template_part'] : NULL );
$no_auto_draft    = isset( $_GET['no_auto_draft'] );

$output_type      = ( isset( $_GET['output_type'] ) ? $_GET['output_type'] : 'HTML' );

// --------------------------------------- Query
$meta_query       = array();
$meta_query_items = array();
$post_status      = array( 'publish' );
if ( ! $no_auto_draft )          array_push( $post_status, 'auto-draft' );
if ( $location_ID )           $meta_query_items[ 'location_clause' ]    = array( 'key' => 'location_ID', 'value' => $location_ID );
if ( $item_ID )               $meta_query_items[ 'item_clause' ]        = array( 'key' => 'item_ID',     'value' => $item_ID );
if ( $period_status_type_id ) $meta_query_items[ 'period_status_type_clause' ] = array( 'key' => 'period_status_type_id', 'value' => $period_status_type_id );

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
	'posts_per_page' => -1,             // Not supported with CB_Query (always current month response)
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
print( "<input name='startdate' value='$startdate_string'/>" );
print( "<input name='enddate' value='$enddate_string'/><br/>" );
print( 'Location: <select name="location_ID">'  . CB_Forms::select_options( CB_Forms::location_options(), $location_ID ) . '</select>' );
print( 'Item: <select name="item_ID">'          . CB_Forms::select_options( CB_Forms::item_options(), $item_ID ) . '</select>' );
print( 'User: <select name="user_ID">'          . CB_Forms::select_options( CB_Forms::user_options(), $user_ID ) . '</select>' );
print( '<br/>' );
print( 'Period Status: <select name="period_status_type_id">' . CB_Forms::select_options( CB_Forms::period_status_type_options(), $period_status_type_id, TRUE ) . '</select>' );
print( "<input id='no_auto_draft' type='checkbox' name='no_auto_draft'/> <label for='no_auto_draft'>Exclude pseudo-periods (A)</label>" );
print( '<br/>' );
print( 'Output type:<select name="output_type">'     . CB_Forms::select_options( array( 'HTML' => 'HTML', 'JSON' => 'JSON' ), $output_type ) . '</select>' );
print( 'Post Type:<select name="schema_type">'       . CB_Forms::select_options( CB_Forms::schema_options(), $schema_type ) . '</select>' );
print( 'Template Part:<select name="template_part">' . CB_Forms::select_options( array( 'available' => 'available' ), $template_part ) . '</select>' );
print( '<br/>' );
print( '<input class="cb2-submit" type="submit" value="Filter"/>' );
print( '</form>' );


// --------------------------------------- HTML calendar output
// Title
print( "<hr/>" );
if ( $output_type == 'HTML' && ( $schema_type == 'location' || $schema_type == 'item' || $schema_type == 'user'  || $schema_type == 'form' ) )
	print( '<div class="cb2-help">Calendar rendering of locations / items / users / forms maybe better in JSON output type</div>' );

switch ( $output_type ) {
	case 'JSON':
		print( '<pre>' );
		print( wp_json_encode( $query, JSON_PRETTY_PRINT ) );
		print( '</pre>' );
		break;
	case 'HTML':
		// print( 'HTML templorarily disabled because of performance problems' );
		// exit();
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
