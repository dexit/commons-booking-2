<?php global $post; ?>
<li id="post-<?php the_ID(); ?>" <?php CB2::post_class( 'cb2-withitems' ); ?>>
	<?php CB2::the_post_template( $post->period_entity->item, $template_args, 'list', $template_type ); ?>
	<span class="cb-date"><?php CB2::the_validity_period(); ?></span>
	<span class="cb-timeframe-title"><?php CB2::the_title(); ?></span>
</li>
