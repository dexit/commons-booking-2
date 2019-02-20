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
		$href_click      = CB2::get_the_edit_post_url();
		$href_class      = '';

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

		<div class="cb-popup">
			<div class="cb2-geo-address"><?php CB2::the_geo_address(); ?></div>
			<ul class="cb2-items">
				<?php CB2::the_inner_loop(); ?>
			</ul>
		</div>
	</li>
<?php } ?>
