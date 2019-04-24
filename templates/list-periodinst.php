<?php
	// General Admin period instance with popup to view and edit
	// Actually edits the Period
	global $post, $action, $cb2_popups;

	if ( CB2::is_top_priority() ) {
		$ID              = get_the_ID();
		$post_type       = get_post_type();
		$even_class      = ( isset( $template_args[ 'even_class' ] ) ? $template_args[ 'even_class' ] : '' );
		$classes         = array( $even_class );
		$permalink       = get_the_permalink();

		// PeriodInst relationship to Day
		if ( isset( $template_args[ 'day' ] ) ) {
			$day  = $template_args[ 'day' ];
			$days = $post->days;
			$day_count = count( $days );
			if ( $day_count > 1 ) {
				array_push( $classes, 'cb2-multi-day' );
				if ( $days[0] == $day )
					array_push( $classes, 'cb2-first-day' );
				else if ( $days[$day_count-1] == $day )
					array_push( $classes, 'cb2-last-day' );
				else
					array_push( $classes, 'cb2-middle-day' );
			}
		}

		// Direct navigation to normal WordPress page option
		$action_text      = __( 'View / Edit ' );
		$view_text        = __( 'view on website' );
		$title_text       = CB2::get_the_title(); // HTML, includes period_status_type_name
		$title_text      .= "<a target='_blank' href='$permalink'>$view_text</a>";
		$href_title_text  = "<span class='cb2-action'>$action_text</span>";
		$href_title_text .= "<span class='cb2-title'>$title_text</span>";
		$href_class       = '';
		$href_click       = CB2::get_the_edit_post_url();

		// AJAX Popup navigation
		// https://codex.wordpress.org/AJAX_in_Plugins
		if ( CB2_AJAX_POPUPS ) {
			$query_string  = CB2_Query::implode_query_string( array(
				'cb2_load_template' => 1,
				'page'         => 'cb2-post-edit', // To force is_admin()
				'context'      => 'popup',
				'template_type'=> 'edit',
				'ID'           => $ID,
				'post_type'    => $post_type,
				'title'        => $href_title_text,
			) );
			$href_click    = admin_url( "admin.php?$query_string" );
			$href_class    = 'thickbox';
		}
?>
	<li id="post-<?php the_ID(); ?>" <?php CB2::post_class( $classes ); ?> style="<?php CB2::the_styles(); ?>">
		<a class="cb2-details cb2-bald <?php print( $href_class ); ?>" title="<?php print( $href_title_text ); ?>" href="<?php print( $href_click ); ?>">
			<?php CB2::the_title(); ?>
			<?php CB2::the_debug_popup(); ?>
			<?php if ( CB2::is_confirmed() ) print( '<span class="cb2-confirmed-check" />' ); ?>
			<?php if ( CB2::is_approved() )  print( '<span class="cb2-approved-check" />' ); ?>
		</a>
	</li>
<?php } ?>
