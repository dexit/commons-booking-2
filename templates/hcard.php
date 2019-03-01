<?php
	// General Admin period instance with popup to view and edit
	// Actually edits the Period
	global $post, $action;

	if ( CB2::is_top_priority() && CB2::has_geo() ) {
		$ID              = get_the_ID();
		$post_type       = get_post_type();
		$classes         = array( 'vcard', 'cb2-hidden' );

		// Direct navigation to normal WordPress page option
		$href_title_text = __( 'View / Edit ' ) . CB2::get_the_title( FALSE );
		$ajax_url        = CB2::get_the_edit_post_url();
		$href_class      = '';

		// AJAX Popup navigation
		if ( CB2_AJAX_POPUPS && is_admin() ) {
			$query_string  = CB2_Query::implode_query_string( array(
				'cb2_load_template' => 1,
				'page'         => 'cb2-post-edit', // To force is_admin()
				'context'      => 'popup',
				'template_type'=> 'edit',
				'ID'           => $ID,
				'post_type'    => $post_type,
			) );
			$href_click = admin_url( "admin.php?$query_string" );
			$href_class = 'thickbox cb2-todo';
		}

		$marker        = plugins_url( 'plugins/geo-hcard-map/images/spanner.png',       CB2_PLUGIN_ABSOLUTE );
		$marker_shadow = plugins_url( 'plugins/geo-hcard-map/images/marker-shadow.png', CB2_PLUGIN_ABSOLUTE );
?>
	<li id="post-<?php the_ID(); ?>" <?php CB2::post_class( $classes ); ?>>
		<div class="adr">
			<span class="geo">
				<span class="latitude"><?php CB2::the_geo_latitude(); ?></span>
				<span class="longitude"><?php CB2::the_geo_longitude(); ?></span>
				<span class="icon"><?php print( $marker ); ?></span>
				<span class="icon-shadow"><?php print( $marker_shadow ); ?></span>
			</span>
			<a class="fn <?php print( $href_class ); ?>" href="<?php print( $href_click ); ?>"><?php CB2::the_title(); ?></a>
		</div>

		<div class="cb-popup cb2-popup">
			<div><?php CB2::the_geo_address(); ?></div>
		</div>
	</li>
<?php } ?>
