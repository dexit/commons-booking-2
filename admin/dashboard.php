<?php
global $wp_query;

// --------------------------------------- Defaults
$months_to_show   = 1;
$today            = CB2_DateTime::today();
$plusXmonths      = $today->clone()->add( "P{$months_to_show}M" )->endTime();

// --------------------------------------- Query Parameters
$startdate_string = ( isset( $_GET['startdate'] )   ? $_GET['startdate'] : $today->format(       CB2_Query::$datetime_format ) );
$enddate_string   = ( isset( $_GET['enddate']   )   ? $_GET['enddate']   : $plusXmonths->format( CB2_Query::$datetime_format ) );
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

// --------------------------------------- Filter selection Form
$location_options  = CB2_Forms::select_options( CB2_Forms::location_options(), $location_ID );
$item_options      = CB2_Forms::select_options( CB2_Forms::item_options(), $item_ID );
$user_options      = CB2_Forms::select_options( CB2_Forms::user_options(), $user_ID );
$period_status_type_options = CB2_Forms::select_options( CB2_Forms::period_status_type_options(), $period_status_type_ID, TRUE );
$period_entity_options = CB2_Forms::select_options( CB2_Forms::period_entity_options(), $period_entity_ID, TRUE );
$output_options    = CB2_Forms::select_options( array( 'Calendar' => 'Calendar', 'Map' => 'Map', 'API/JSON' => 'API/JSON' ), $output_type );
$schema_options    = CB2_Forms::select_options( CB2_Forms::schema_options(), $schema_type );
$template_options  = CB2_Forms::select_options( array( 'available' => 'available' ), $template_part );
$Class_display_strategys = CB2_Forms::select_options( CB2_Query::subclasses( 'CB2_PeriodInteractionStrategy' ), $Class_display_strategy, 'WP_Query', TRUE );
$class_WP_DEBUG    = ( WP_DEBUG ? '' : 'hidden' );
$show_overridden_periods_checked = ( $show_overridden_periods ? 'checked="1"' : '' );
$show_blocked_periods_checked = ( $show_blocked_periods ? 'checked="1"' : '' );

$period_status_type_options_html = count_options( CB2_Forms::period_status_type_options() );
$period_entity_options_html      = count_options( CB2_Forms::period_entity_options() );

$title_text = __( 'Dashboard' );
$filename   = basename( __FILE__ );
print( "<h1>Commons Booking 2 $title_text <span>
		<a class='cb2-WP_DEBUG $class_WP_DEBUG' href='admin.php?page=cb2_menu&amp;extended=1'>$filename: extended debug form</a>
		| <a href='options-general.php?page=cb2-options'>settings</a>
	</span></h1>" );

$location_text = __( 'Location' );
$item_text     = __( 'Item' );
$user_text     = __( 'User' );
print( <<<HTML
	<form>
		<input name='page' type='hidden' value='cb2_menu'/>
		<input type='text' name='startdate' value='$startdate_string'/> to
		<input type='text' name='enddate' value='$enddate_string'/>
		$location_text:<select name="location_ID">$location_options</select>
		$item_text:<select name="item_ID">$item_options</select>
		$user_text:<select name="user_ID">$user_options</select>
		<div style='display:$extended_class'>
			<input type="hidden" name="extended$extended_class" value="1"/>
			Period Status Type:
				$period_status_type_options_html
				<select name="period_status_type_ID">$period_status_type_options</select>
			Period Entity:
				$period_entity_options_html
				<select name="period_entity_ID">$period_entity_options</select>
			<br/>
			Output Type:      <select name="output_type">$output_options</select>
			Schema Hierarchy: <select name="schema_type">$schema_options</select>
			Template Part:    <select name="template_part">$template_options</select>
			Display Strategy: <select name="display_strategy">$Class_display_strategys</select>
			<br/>
			<input id='show_overridden_periods' type='checkbox' $show_overridden_periods_checked name='show_overridden_periods'/> <label for='show_overridden_periods'>show overridden periods</label><br/>
			<input id='show_blocked_periods'    type='checkbox' $show_blocked_periods_checked    name='show_blocked_periods'/>    <label for='show_blocked_periods'>show blocked periods</label>
		</div>
		<input class="cb2-submit button" type="submit" value="Filter"/>
	</form>
HTML
);

if ( (new CB2_DateTime( $startdate_string ))->after( new CB2_DateTime( $enddate_string ) ) ) // PHP 5.2.2
	print( '<div class="cb2-warning cb2-notice">start date is more than end date</div>' );

// --------------------------------------- Debug
if ( WP_DEBUG ) {
	if ( $output_type == 'Calendar' && ( $schema_type == 'location' || $schema_type == 'item' || $schema_type == 'user'  || $schema_type == 'form' ) )
		print( '<div class="cb2-help">Calendar rendering of locations / items / users / forms maybe better in JSON output type</div>' );
	if ( $wp_query->post_count ) {
		// Details
		$post_types     = array();
		$debug_wp_query = ( property_exists( $wp_query, 'debug_wp_query' ) ? $wp_query->debug_wp_query : $wp_query );
		foreach ( $debug_wp_query->posts as $post )
			$post_types[$post->post_type] = $post->post_type;
		print( "<div class='cb2-WP_DEBUG' style='display:$extended_class;border:1px solid #000;padding:3px;background-color:#fff;margin:1em 0em;'>" );
		print( "<b>$wp_query->post_count</b> posts returned" );
		print( ' containing only <b>[' . implode( ', ', $post_types ) . "]</b> post_types" );
		print( ' <span class="dashicons-before dashicons-admin-tools" style="color:#aaa;"></span><a class="cb2-calendar-krumo-show">more...</a>' );
		print( '<div class="cb2-calendar-krumo" style="display:none;">' );
		print( "<div style='border:1px solid #000;padding:3px;background-color:#fff;margin:1em 0em;'>
			<div><b>NOTE</b>: the GROUP BY clause will fail if run with sql_mode=only_full_group_by</div>
			<div style='margin-left:5px;color:#448;'>$wp_query->request</div></div>" );

		// Code output
		$template_part_code = ( $template_part ? ", 'list', '$template_part'" : '' );
		print( "<pre style='border:1px solid #000;padding:4px;'>\n" );
		print( "// WP_Query arguments for query using $Class_display_strategy\n" );
		print( "// note that the [date_query][compare] value indicates the OO object reorganisation of the resultant posts array\n" );
		if ( $Class_display_strategy == 'WP_Query' )
			print( "\$wp_query = new WP_Query( array(\n" );
		else
			print( "\$wp_query = $Class_display_strategy::factory_from_query_args( array(\n" );
		array_walk( $wp_query->query, array( 'CB2_Query', 'php_array' ) );
		print( ") );\n" );
		print( "\n// CB2::the_inner_loop() is a normal WordPress posts loop\n" );
		print( "// post templates often call CB2::the_inner_loop() on themselves also, thus traversing the object hierarchy\n" );
		print( htmlspecialchars( <<<HTML
print( '<table class="cb2-calendar">' );
CB2::the_calendar_header( \$wp_query );
print( '<tbody>' );
CB2::the_inner_loop( NULL, \$wp_query$template_part_code );
print( '</tbody>' );
CB2::the_calendar_footer( \$wp_query );
print( '</table>' );
HTML
		) );
		print( '</pre>' );

		// Object
		//if ( $wp_query instanceof WP_Query )
		//	CB2_Query::reorganise_posts_structure( $wp_query ); // For debug purposes
		//print( '<div class="cb2-todo">NOTE: krumo disabled because it is causing meta-data calls</div>' );
		//krumo( $wp_query );
		print( "</div></div>" );
	} else print( "<div>No posts returned!</div>" );
}

// --------------------------------------- Output
switch ( $output_type ) {
	case 'API/JSON':
		print( '<pre>' );
		if ( $wp_query instanceof WP_Query ) {
			// This is necessary here because loop_start or CB2_PeriodInteractionStrategy::jsonSerialize()
			// usually does the reorganisation
			CB2_Query::reorganise_posts_structure( $wp_query );
		}
		print( wp_json_encode( $wp_query, JSON_PRETTY_PRINT ) );
		print( '</pre>' );
		break;

	case 'Map':
		print( '<div class="cb2-help">Map rendering not complete</div>' );
		break;

	case 'Calendar':
		$startdate = new CB2_DateTime( $startdate_string );
		$enddate   = new CB2_DateTime( $enddate_string );
		$the_calendar_pager = CB2::get_the_calendar_pager( $startdate, $enddate );
		$template_args = array(
			'action' => 'add', // + add in CB2_Day
		);
		?>
		<div class="cb2-calendar">
			<?php echo $the_calendar_pager; ?><br/>
			<div class="entry-content" style="width:100%;">
				<?php CB2::the_calendar_header( $wp_query ); ?>
				<ul class="cb2-subposts">
					<!-- usually weeks -->
					<?php CB2::the_inner_loop( $template_args, $wp_query, 'list', $template_part ); ?>
				</ul>
				<?php CB2::the_calendar_footer( $wp_query ); ?>
			</div><!-- .entry-content -->
			<br/><?php echo $the_calendar_pager; ?>
		</div>
	<?php
		break;

	default:
		print( "<div class='cb2-help'>Output type [$output_type] not understood</div>" );
}

function count_options( $array, $class = 'ok' ) {
	$count = count( $array );
	return "<span class='cb2-usage-count-$class'>$count</span>";
}
?>
