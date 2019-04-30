<li id="post-<?php the_ID(); ?>" <?php CB2::post_class(); ?>>
	<div class="cb2-location-title"><?php CB2::the_title(); ?></div>
	<div class="cb2-location-address"><?php CB2::the_geo_address(); ?></div>

	<?php CB2::the_inner_loop( $template_args, NULL, 'list', $template_type ); ?>
</li>

