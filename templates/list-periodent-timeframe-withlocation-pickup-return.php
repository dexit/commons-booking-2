<?php
global $post;
CB2::the_post_template( $post->period_entity->location, $template_args, 'list', $template_type );
?>
