<?php
	// General Admin period instance with popup to view and edit
	// Actually edits the Period
	global $post, $action, $cb2_popups;

	if ( CB2::is_top_priority() ) {
		$ID              = get_the_ID();
		$post_type       = get_post_type();
		$even_class      = ( isset( $template_args[ 'even_class' ] ) ? $template_args[ 'even_class' ] : '' );
		$classes         = array( $even_class );

		// PeriodItem relationship to Day
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
		$href_title_text = __( 'View / Edit ' ) . CB2::get_the_title( FALSE );
		$href_class      = '';
		$href_click      = CB2::get_the_edit_post_url();

		// AJAX Popup navigation
		if ( CB2_AJAX_POPUPS ) {
			$page      = 'cb2-load-template';
			$action    = 'edit'; // context = 'popup'
			$template_loader_url = plugins_url(
				"admin/load_template.php?page=$page&action=$action&ID=$ID&post_type=$post_type",
				dirname( __FILE__ )
			);
			$href_class = 'thickbox';
			$href_click = "$template_loader_url&title=$href_title_text";
		}
?>
	<li id="post-<?php the_ID(); ?>" <?php CB2::post_class( $classes ); ?>>
		<a class="cb2-details cb2-bald <?php print( $href_class ); ?>" title="<?php print( $href_title_text ); ?>" href="<?php print( $href_click ); ?>">
			<?php CB2::the_title(); ?>
			<?php CB2::the_logs(); ?>
		</a>
	</li>
<?php } ?>
