<li id="post-<?php the_ID(); ?>" <?php CB2::post_class(); ?>>
	<form style="float:right;" action="<?php CB2::the_permalink(); ?>"><div>
		<input type="submit" value="<?php print( __( 'book' ) ); ?>"/>
	</div></form>

	<?php the_post_thumbnail(); ?>
	<div class="cb2-item-title"><?php CB2::the_title(); ?></div>

	<?php CB2::the_inner_loop( $template_args, NULL, 'list', $template_type ); ?>
</li>

