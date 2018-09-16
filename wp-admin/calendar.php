<?php
// --------------------------------------- Defaults
$yesterday        = (new DateTime())->sub( new DateInterval( 'P2D' ) );
$plus3months      = (clone $yesterday)->add( new DateInterval( 'P3M' ) );

// --------------------------------------- Query Parameters
$startdate_string = ( isset( $_GET['startdate'] )   ? $_GET['startdate'] : $yesterday->format(   CB_Query::$datetime_format ) );
$enddate_string   = ( isset( $_GET['enddate']   )   ? $_GET['enddate']   : $plus3months->format( CB_Query::$datetime_format ) );
$location_ID      = ( isset( $_GET['location_ID'] ) ? $_GET['location_ID'] : NULL );
$item_ID          = ( isset( $_GET['item_ID'] )     ? $_GET['item_ID']     : NULL );
$user_ID          = ( isset( $_GET['user_ID'] )     ? $_GET['user_ID']          : NULL );
$period_group_id  = ( isset( $_GET['period_group_id'] ) ? $_GET['period_group_id'] : NULL );
$period_status_type_ID = ( isset( $_GET['period_status_type_ID'] ) ? $_GET['period_status_type_ID'] : NULL );
$period_entity_ID = ( isset( $_GET['period_entity_ID'] ) ? $_GET['period_entity_ID'] : NULL );
$schema_type      = ( isset( $_GET['schema_type'] )   ? $_GET['schema_type'] : CB_Week::$static_post_type );
$template_part    = ( isset( $_GET['template_part'] ) ? $_GET['template_part'] : NULL );
$no_auto_draft    = isset( $_GET['no_auto_draft'] );
$output_type      = ( isset( $_GET['output_type'] ) ? $_GET['output_type'] : 'HTML' );
$extended_class   = ( isset( $_GET['extended'] ) ? '' : 'none' );

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
if ( $period_entity_ID )
	$meta_query_items[ 'period_entity_clause' ] = array(
		'key' => 'period_entity_ID',
		'value' => $period_entity_ID
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
$location_options = CB_Forms::select_options( CB_Forms::location_options(), $location_ID );
$item_options     = CB_Forms::select_options( CB_Forms::item_options(), $item_ID );
$user_options     = CB_Forms::select_options( CB_Forms::user_options(), $user_ID );
$period_status_type_options = CB_Forms::select_options( CB_Forms::period_status_type_options(), $period_status_type_ID, TRUE );
$period_entity_options = CB_Forms::select_options( CB_Forms::period_entity_options(), $period_entity_ID, TRUE );
$output_options   = CB_Forms::select_options( array( 'HTML' => 'HTML', 'JSON' => 'JSON' ), $output_type );
$schema_options   = CB_Forms::select_options( CB_Forms::schema_options(), $schema_type );
$template_options = CB_Forms::select_options( array( 'available' => 'available' ), $template_part );

print( <<<HTML
	<h1>Calendar</h1>
	<form>
		<input name='page' type='hidden' value='cb2-calendar'/>
		<input type='text' name='startdate' value='$startdate_string'/> =&gt;
		<input type='text' name='enddate' value='$enddate_string'/>
		Location:<select name="location_ID">$location_options</select>
		Item:<select name="item_ID">$item_options</select>
		User:<select name="user_ID">$user_options</select>
		Period Status:<select name="period_status_type_ID">$period_status_type_options</select>
		<div style='display:$extended_class'>
			<input type="hidden" name="extended$extended_class" value="1"/>
			Period Entity:<select name="period_entity_ID">$period_entity_options</select>
			Output type:<select name="output_type">$output_options</select>
			Post Type:<select name="schema_type">$schema_options</select>
			Template Part:<select name="template_part">$template_options</select>
			<br/>
			<input id='no_auto_draft' type='checkbox' name='no_auto_draft'/> <label for='no_auto_draft'>Exclude pseudo-periods (A)</label>
			<input id='show_overridden_periods' type='checkbox' name='show_overridden_periods'/> <label for='show_overridden_periods'>show overridden periods</label>
			<br/>
		</div>
		<input class="cb2-submit button" type="submit" value="Filter"/>
		<a href="admin.php?page=cb2-calendar&amp;extended=1">extended</a>
	</form>
HTML
);

// --------------------------------------- Debug
if ( WP_DEBUG ) {
	if ( $output_type == 'HTML' && ( $schema_type == 'location' || $schema_type == 'item' || $schema_type == 'user'  || $schema_type == 'form' ) )
		print( '<div class="cb2-help">Calendar rendering of locations / items / users / forms maybe better in JSON output type</div>' );
	$post_count = count( $query->posts );
	if ( $post_count ) {
		$post_types = array();
		foreach ( $query->posts as $post )
			$post_types[$post->post_type] = $post->post_type;
		print( "<div class='cb2-WP_DEBUG' style='border:1px solid #000;padding:3px;background-color:#fff;margin:1em 0em;'>" );
		print( "<b>$post_count</b> posts returned" );
		print( ' containing only <b>[' . implode( ', ', $post_types ) . "]</b> post_types" );
		print( ' <a class="cb2-calendar-krumo-show">more...</a><div class="cb2-calendar-krumo" style="display:none;">' );
		print( "<div style='border:1px solid #000;padding:3px;background-color:#fff;margin:1em 0em;'>
			<div><b>NOTE</b>: the GROUP BY clause will fail if run with sql_mode=only_full_group_by</div>
			<div style='margin-left:5px;color:#448;'>$query->request</div></div>" );
		krumo( $args );
		print( "</div></div>" );
	} else print( "<div>No posts returned!</div>" );
}

// --------------------------------------- HTML calendar output
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
				<table class="cb2-subposts" style="width:98%;"><tbody>
					<thead><tr>
						<?php
							// TODO: wordpress WeekStartsOn
							foreach ( CB_Query::$days as $dayname ) {
								print( "<th>$dayname</th>" );
							}
						?>
					</tr></thead>
					<tbody>
						<?php the_inner_loop( $query, 'list', $template_part ); ?>
					</tbody>
					<thead><tr>
						<?php
							// TODO: wordpress WeekStartsOn
							foreach ( CB_Query::$days as $dayname ) {
								print( "<th>$dayname</th>" );
							}
						?>
					</tr></thead>
				</tbody></table>
			</div><!-- .entry-content -->
		</div>
	<?php
		break;
}
?>
