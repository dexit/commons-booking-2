<div class="wrap">
<?php
global $wp_query;

// We set the global $wp_query so that all template functions will work
// And also so pre_get_posts will not bulk with no global $wp_query
wp_reset_query();
$wp_query = CB2_PeriodInteractionStrategy::factory_from_args($_REQUEST, array(
    'display-strategy' => 'CB2_AllItemAvailability',
));


// --------------------------------------- Page
$title_text = __( 'Calendar' );
print( "<h1>$title_text</h1>" );
CB2_Forms::the_form( $_REQUEST );

// --------------------------------------- Debug
if ( WP_DEBUG ) {
	if ( $wp_query->post_count ) {
		// Details
		$post_types     = array();
		$extended_class = ( isset( $_REQUEST['extended'] )    ? '' : 'none' );
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
		$template_part_code = "'$_REQUEST[template_part]'";
		print( "<pre style='border:1px solid #000;padding:4px;'>\n" );
		print( "// WP_Query arguments for query using $_REQUEST[display_strategy]\n" );
		print( "// note that the [date_query][compare] value indicates the OO object reorganisation of the resultant posts array\n" );
		if ( $_REQUEST['display_strategy'] == 'WP_Query' )
			print( "\$wp_query = new WP_Query( array(\n" );
		else
			print( "\$wp_query = $_REQUEST[display_strategy]::factory_from_query_args( array(\n" );
		array_walk( $wp_query->query, array( 'CB2_Query', 'php_array' ) );
		print( ") );\n" );
		print( "\n// CB2::the_inner_loop() is a normal WordPress posts loop\n" );
		print( "// post templates often call CB2::the_inner_loop() on themselves also, thus traversing the object hierarchy\n" );
		print( htmlspecialchars( <<<HTML
print( '<table class="cb2-calendar">' );
CB2::the_calendar_header( \$wp_query );
print( '<tbody>' );
CB2::the_inner_loop( NULL, \$wp_query, $template_part_code );
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
		krumo( $wp_query );
		print( "</div></div>" );
	} else print( "<div>No posts returned!</div>" );
}

// --------------------------------------- Output
$startdate        = new CB2_DateTime( $_REQUEST['startdate'] );
$enddate          = new CB2_DateTime( $_REQUEST['enddate'] );
$template_args    = array();

switch ( $_REQUEST['output_type'] ) {
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
		print( '<ul>' );
		CB2::the_inner_loop( $template_args, $wp_query, $_REQUEST['context'], $_REQUEST['template_part'] );
		print( '</ul>' );
		print( geo_hcard_map_shortcode_handler( NULL ) );
		break;

	case 'Calendar':
		$the_calendar_pager = CB2::get_the_calendar_pager( $startdate, $enddate );
		?>
		<div class="cb2-calendar">
			<?php echo $the_calendar_pager; ?><br/>
			<div class="entry-content" style="width:100%;">
				<?php CB2::the_calendar_header( $wp_query ); ?>
				<ul class="cb2-subposts">
					<!-- usually weeks -->
					<?php CB2::the_inner_loop( $template_args, $wp_query, $_REQUEST['context'], $_REQUEST['template_part'] ); ?>
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
?>
</div>
