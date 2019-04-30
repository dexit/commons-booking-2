<li id="post-<?php the_ID(); ?>" <?php CB2::post_class(); ?> style="<?php CB2::the_styles(); ?>">
<?php
	global $post;
	CB2::the_post_template( $post->period_entity->item, $template_args, 'list', $template_type );
?>
</li>
