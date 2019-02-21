<?php
	global $post;

	$ID              = get_the_ID();    // CB2_Day
	$post_type       = get_post_type(); // day
	$date            = CB2::get_the_date( 'c' ); // CB2_DateTime
	$has_context     = isset( $template_args['post'] );
	$action          = ( $has_context ? 'addto' : 'add' );
	if ( isset( $template_args['action'] ) ) $action = $template_args['action']; // Can be empty
	if ( ! is_admin() ) $action = NULL;
	$href_click      = NULL;            // TODO: direct add link?
	$href_title_text = __( $action );
	$href_class      = '';
	$add_new_values  = array();
	$template_args[ 'day' ] = $post;    // CB2_Day

	// Context settings
	$classes        = array();
	$out_of_period_entity_scope = FALSE;
	if ( $has_context ) {
		$context_post = $template_args['post'];
		$add_new_values[ 'context_post_ID' ]   = $context_post->ID;
		$add_new_values[ 'context_post_type' ] = $context_post->post_type;
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
		$page      = 'cb2-load-template';
		$date      = urlencode( $date );
		$values_string = CB2_Query::implode( '&', $add_new_values, '=', NULL, TRUE, TRUE ); // urlencode values
		$template_loader_url = plugins_url(
			"admin/load_template.php?page=$page&action=$action&ID=$ID&date=$date&post_type=$post_type&$values_string",
			dirname( __FILE__ )
		);
		$href_class = 'thickbox';
		$href_click = "$template_loader_url&title=$href_title_text";
	}
?>
<li id="post-<?php the_ID(); ?>" <?php CB2::post_class( $classes ); ?>>
		<?php the_title( '<div class="day-title">', '</div>' ); ?>
	<div class="entry-content">
		<ul class="cb2-subposts">
			<?php CB2::the_inner_loop( $template_args, $post, 'list', $template_type ); ?>
		</ul>

		<?php if ( $action ) { ?>
			<div class="cb2-add-period">
				<a class="cb2-details cb2-bald <?php print( $href_class ); ?>" title="<?php print( $href_title_text ); ?>" href="<?php print( $href_click ); ?>">
					<?php print( $href_title_text ); ?>
				</a>
			</div>
		<?php } ?>
	</div><!-- .entry-content -->

	<?php CB2::the_context_menu(); ?>
</li>

