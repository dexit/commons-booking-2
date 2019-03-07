<li id="post-<?php the_ID(); ?>" <?php CB2::post_class(); ?>>
	<?php CB2::the_title( '<div class="day-title">', '</div>' ); ?>
	<ul class="cb2-subposts">
		<?php CB2::the_inner_loop( $template_args, NULL, 'list', $template_type ); ?>
	</ul>
</li>

