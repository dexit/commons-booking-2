<?php
	global $post;

	$ID              = get_the_ID();    // CB2_Day
	$post_type       = get_post_type(); // day
	$date            = CB2::get_the_date( 'c' ); // CB2_DateTime
	$context_post    = NULL;
	$has_context     = isset( $template_args['post'] );
	$action          = ( $has_context ? 'addto' : 'add' );
	if ( isset( $template_args['action'] ) ) $action = $template_args['action']; // Can be empty
	if ( ! is_admin() ) $action = NULL;
	$href_click      = NULL;            // TODO: direct add link?
	$href_title_text = __( $action );
	$href_class      = '';
	$template_args[ 'day' ] = $post;    // CB2_Day

	// Context settings
	$classes        = array();
	$out_of_period_entity_scope = FALSE;
	if ( $has_context ) {
		$context_post = $template_args['post'];
		if ( $context_post instanceof CB2_PeriodEntity ) {
			// In context with main post?
			if ( ( $context_post->entity_datetime_to   && $context_post->entity_datetime_to->lessThanOrEqual( $date  ) )
				|| ( $context_post->entity_datetime_from && $context_post->entity_datetime_from->after( $date ) )
			) {
				$out_of_period_entity_scope = TRUE;
				array_push( $classes, 'cb2-out-of-period-entity-scope' );
			}
		}
	}

	// AJAX Popup navigation
	if ( CB2_AJAX_POPUPS ) {
		$query_string  = CB2_Query::implode_query_string( array(
			'cb2_load_template' => 1,
			'page'         => 'cb2-post-edit', // To force is_admin()
			'context'      => 'popup',
			'template_type'=> $action,
			'ID'           => $ID,
			'post_type'    => $post_type,
			'date'         => $date,
			'title'        => $href_title_text,
			'context_post_ID'   => ( $context_post ? $context_post->ID        : NULL ),
			'context_post_type' => ( $context_post ? $context_post->post_type : NULL ),
		) );
		$href_click = admin_url( "admin.php?$query_string" );
		$href_class = 'thickbox';
	}
?>
<li id="post-<?php the_ID(); ?>" <?php CB2::post_class( $classes ); ?>>
	<?php CB2::the_title( '<div class="cb2-day-title">', '</div>' ); ?>
	<div class="cb2-overlaid-inner">
		<ul class="cb2-time-guide">
			<?php
				$datetime = CB2_DateTime::day_start();
				$day_end  = CB2_DateTime::day_end();
				while ( $datetime->before( $day_end ) ) {
					$percent_position = CB2_Day::day_percent_position( $datetime );
					$time_string      = $datetime->format( 'H:i' );
					// $time_id     = $datetime->format( 'H:i' );
					print( "<li style='top:$percent_position%'>$time_string</li>");
					$datetime->add( 'PT1H' );
				}
			?>
		</ul>

		<ul class="cb2-subposts">
			<?php CB2::the_inner_loop( $template_args, $post, 'list', $template_type ); ?>
		</ul>
	</div>

	<?php if ( $action ) { ?>
		<div class="cb2-add-period">
			<a class="cb2-details cb2-bald <?php print( $href_class ); ?>" title="<?php print( htmlentities( $href_title_text ) ); ?>" href="<?php print( $href_click ); ?>">
				<?php print( $href_title_text ); ?>
			</a>
		</div>
	<?php } ?>

	<?php CB2::the_context_menu(); ?>
</li>

