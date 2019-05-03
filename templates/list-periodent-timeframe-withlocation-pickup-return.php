<?php global $post; ?>
<li id="post-<?php the_ID(); ?>" <?php CB2::post_class( 'cb-row' ); ?>>
	<?php CB2::the_post_template( $post->period_entity->location, $template_args, 'list', $template_type ); ?>
	<span class="cb-date"><?php CB2::the_validity_period(); ?></span>
</li>
