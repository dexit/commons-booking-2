<?php
// --------------------------------------- Defaults
$yesterday        = (new CB2_DateTime())->sub( 2 );
$plus3months      = (clone $yesterday)->add( 'P3M' );

// --------------------------------------- Query Parameters
$startdate_string = ( isset( $_GET['startdate'] )   ? $_GET['startdate'] : $yesterday->format(   CB2_Query::$datetime_format ) );
$enddate_string   = ( isset( $_GET['enddate']   )   ? $_GET['enddate']   : $plus3months->format( CB2_Query::$datetime_format ) );
$location_ID      = ( isset( $_GET['location_ID'] ) ? $_GET['location_ID'] : NULL );
$item_ID          = ( isset( $_GET['item_ID'] )     ? $_GET['item_ID']     : NULL );
$user_ID          = ( isset( $_GET['user_ID'] )     ? $_GET['user_ID']          : NULL );
$period_group_id  = ( isset( $_GET['period_group_id'] )  ? $_GET['period_group_id'] : NULL );
$period_status_type_ID = ( isset( $_GET['period_status_type_ID'] ) ? $_GET['period_status_type_ID'] : NULL );
$period_entity_ID = ( isset( $_GET['period_entity_ID'] ) ? $_GET['period_entity_ID'] : NULL );
$schema_type      = ( isset( $_GET['schema_type'] )      ? $_GET['schema_type'] : CB2_Week::$static_post_type );
$template_part    = ( isset( $_GET['template_part'] )    ? $_GET['template_part'] : NULL );
$display_strategy = ( isset( $_GET['display_strategy'] ) && $_GET['display_strategy'] ? $_GET['display_strategy'] : 'WP_Query' );
$show_debug       = isset( $_GET['show_debug'] );
$output_type      = ( isset( $_GET['output_type'] ) ? $_GET['output_type'] : 'HTML' );
$extended_class   = ( isset( $_GET['extended'] )    ? '' : 'none' );
$show_overridden_periods = isset( $_GET['show_overridden_periods'] );

// --------------------------------------- Query
$meta_query       = array();
$meta_query_items = array();
$post_status      = array( CB2_Post::$PUBLISH );
if ( $location_ID )
	$meta_query_items[ 'location_clause' ] = array(
		'key' => 'location_ID',
		'value' => array( $location_ID, 0 ),
	);
if ( $item_ID )
	$meta_query_items[ 'item_clause' ] = array(
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

if ( $meta_query_items ) {
	if ( ! isset( $meta_query_items[ 'relation' ] ) )
		$meta_query_items[ 'relation' ] = 'AND';
	$meta_query[ 'items' ] = $meta_query_items;
}

$args = array(
	'author'         => $user_ID,
	'post_status'    => $post_status,
	'post_type'      => CB2_PeriodItem::$all_post_types,
	'posts_per_page' => -1,
	'order'          => 'ASC',          // defaults to post_date
	'show_overridden_periods' => 'yes', // TODO: doesnt work yet: use the query string
	'date_query'     => array(
		'after'   => $startdate_string,
		'before'  => $enddate_string,
		'compare' => $schema_type,
	),
	'meta_query' => $meta_query,        // Location, Item, User
);
// Here we should instantiate with stated CB2_PeriodInteractionStrategy
// same as WP_Query
$query = new WP_Query( $args );

// --------------------------------------- Filter selection Form
$location_options  = CB2_Forms::select_options( CB2_Forms::location_options(), $location_ID );
$item_options      = CB2_Forms::select_options( CB2_Forms::item_options(), $item_ID );
$user_options      = CB2_Forms::select_options( CB2_Forms::user_options(), $user_ID );
$period_status_type_options = CB2_Forms::select_options( CB2_Forms::period_status_type_options(), $period_status_type_ID, TRUE );
$period_entity_options = CB2_Forms::select_options( CB2_Forms::period_entity_options(), $period_entity_ID, TRUE );
$output_options    = CB2_Forms::select_options( array( 'HTML' => 'HTML', 'JSON' => 'JSON' ), $output_type );
$schema_options    = CB2_Forms::select_options( CB2_Forms::schema_options(), $schema_type );
$template_options  = CB2_Forms::select_options( array( 'available' => 'available' ), $template_part );
$display_strategys = CB2_Forms::select_options( CB2_Forms::subclasses( 'CB2_PeriodInteractionStrategy' ), $display_strategy, TRUE, TRUE );
$class_WP_DEBUG    = ( WP_DEBUG ? '' : 'hidden' );
$show_overridden_periods_checked = ( $show_overridden_periods ? 'checked="1"' : '' );

$period_status_type_options_html = count_options( CB2_Forms::period_status_type_options() );
$period_entity_options_html      = count_options( CB2_Forms::period_entity_options() );

$calendar_text = __( 'Calendar' );
print( "<h1>$calendar_text <a class='cb2-WP_DEBUG $class_WP_DEBUG' href='admin.php?page=cb2-calendar&amp;extended=1'>" . basename( __FILE__ ) . ": extended debug form</a></h1>" );

$location_text = __( 'Location' );
$item_text     = __( 'Item' );
$user_text     = __( 'User' );
print( <<<HTML
	<form>
		<input name='page' type='hidden' value='cb2-calendar'/>
		<input type='text' name='startdate' value='$startdate_string'/> to
		<input type='text' name='enddate' value='$enddate_string'/>
		$location_text:<select name="location_ID">$location_options</select>
		$item_text:<select name="item_ID">$item_options</select>
		$user_text:<select name="user_ID">$user_options</select>
		<div style='display:$extended_class'>
			<input type="hidden" name="extended$extended_class" value="1"/>
			Period Status:
				$period_status_type_options_html
				<select name="period_status_type_ID">$period_status_type_options</select>
			Period Entity:
				$period_entity_options_html
				<select name="period_entity_ID">$period_entity_options</select>
			<br/>
			Output type:<select name="output_type">$output_options</select>
			Post Type:<select name="schema_type">$schema_options</select>
			Template Part:<select name="template_part">$template_options</select>
			<span class="cb2-todo">Display Strategy</span>:<select name="display_strategy">$display_strategys</select>
			<input id='show_overridden_periods' type='checkbox' $show_overridden_periods_checked name='show_overridden_periods'/> <label for='show_overridden_periods'>show overridden periods</label>
		</div>
		<input class="cb2-submit button" type="submit" value="Filter"/>
	</form>
HTML
);

if ( (new CB2_DateTime( $startdate_string ))->after( new CB2_DateTime( $enddate_string ) ) ) // PHP 5.2.2
	print( '<div class="cb2-warning cb2-notice">start date is more than end date</div>' );

// --------------------------------------- Debug
if ( WP_DEBUG ) {
	if ( $output_type == 'HTML' && ( $schema_type == 'location' || $schema_type == 'item' || $schema_type == 'user'  || $schema_type == 'form' ) )
		print( '<div class="cb2-help">Calendar rendering of locations / items / users / forms maybe better in JSON output type</div>' );
	if ( $query->post_count ) {
		$post_types     = array();
		$debug_wp_query = ( property_exists( $query, 'debug_wp_query' ) ? $query->debug_wp_query : $query );
		foreach ( $debug_wp_query->posts as $post )
			$post_types[$post->post_type] = $post->post_type;
		print( "<div class='cb2-WP_DEBUG' style='border:1px solid #000;padding:3px;background-color:#fff;margin:1em 0em;'>" );
		print( "<b>$query->post_count</b> posts returned" );
		print( ' containing only <b>[' . implode( ', ', $post_types ) . "]</b> post_types" );
		print( ' <a class="cb2-calendar-krumo-show">more...</a><div class="cb2-calendar-krumo" style="display:none;">' );
		print( "<div style='border:1px solid #000;padding:3px;background-color:#fff;margin:1em 0em;'>
			<div><b>NOTE</b>: the GROUP BY clause will fail if run with sql_mode=only_full_group_by</div>
			<div style='margin-left:5px;color:#448;'>$query->request</div></div>" );
		print( '<div class="cb2-todo">NOTE: krumo disabled because it is causing meta-data calls</div>' );
		// krumo( $query );
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
				<table class="cb2-subposts" style="width:98%;">
					<?php CB2::the_calendar_header( $query ); ?>
					<tbody>
						<?php CB2::the_inner_loop( $query, 'list', $template_part ); ?>
					</tbody>
					<?php CB2::the_calendar_footer( $query ); ?>
				</table>
			</div><!-- .entry-content -->
		</div>
	<?php
		break;
}

function count_options( $array, $class = 'ok' ) {
	$count = count( $array );
	return "<span class='cb2-usage-count-$class'>$count</span>";
}
?>
