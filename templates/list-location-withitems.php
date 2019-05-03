<li id="post-<?php the_ID(); ?>" <?php CB2::post_class( 'cb-location-wrapper cb-box vcard' ); ?>>
	<h2 class="cb-big">
		<a href="<?php CB2::the_permalink(); ?>" title="<?php CB2::the_title_attribute(); ?>"><?php CB2::the_title(); ?></a>
		<a class="url fn org" href="<?php CB2::the_permalink(); ?>"><?php CB2::the_title(); ?></a>
	</h2>
	<div class="align-right"><?php the_post_thumbnail(); ?></div>
	<div class="cb-list-location-description cb-popup"><?php CB2::the_geo_address(); ?></div>
	<div class="cb-list-location-openinghours cb-popup">
		<span class="cb2-label"><?php echo __( 'Opening Hours' ); ?></span>
		<?php CB2::the_opening_hours(); ?>
	</div>

	<!-- div class="cb-list-location-content">// TODO: cb-list-location-content (?)</div -->

	<div class="cb-list-location-location adr">
		<div class="geo">
			<span class="latitude"><?php CB2::the_geo_latitude(); ?></span>
			<span class="longitude"><?php CB2::the_geo_longitude(); ?></span>
			<span class="icon"><?php CB2::the_icon(); ?></span>
			<span class="icon-shadow"><?php CB2::the_icon_shadow(); ?></span>
		</div>
	</div>

	<div class="cb-list-location-items cb-popup">
		<ul class="cb-table">
			<?php CB2::the_inner_loop( $template_args, NULL, 'list', $template_type ); ?>
    </ul>
  </div>
</li>
