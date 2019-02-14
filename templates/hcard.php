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

		$marker = plugins_url( 'plugins/geo-hcard-map/images/spanner.png', CB2_PLUGIN_ABSOLUTE );
?>
	<li id="post-<?php the_ID(); ?>" <?php CB2::post_class( $classes ); ?>>
		<div class="adr">
			<span class="geo">
				<span class="latitude"><?php CB2::the_geo_latitude(); ?></span>
				<span class="longitude"><?php CB2::the_geo_longitude(); ?></span>
				<span class="icon"><?php print( $marker ); ?></span>
				<!-- span class="icon-shadow"></span -->
			</span>
			<a class="fn" href="<?php print( $href_click ); ?>"><?php CB2::the_title(); ?></a>
		</div>

		<div class="cb-popup">
			<a class="cb2-details cb2-bald <?php print( $href_class ); ?>" title="<?php print( $href_title_text ); ?>" href="<?php print( $href_click ); ?>">
			</a>
		</div>
	</li>
<?php } ?>
