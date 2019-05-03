<li id="post-<?php the_ID(); ?>" <?php CB2::post_class(); ?>>
		<?php CB2::the_title( '<div class="cb2-day-title">', '</div>' ); ?>
		<ul class="cb2-subposts">
			<?php CB2::the_inner_loop( $template_args, NULL, 'list', 'available' ); ?>
		</ul>
	<?php CB2::the_context_menu(); ?>
</li>

