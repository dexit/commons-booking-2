<?php
global $post;
$item    = $post->period_entity->item;
$classes = array( 'cb2-template-indicators' );
?>
<li id="post-<?php the_ID(); ?>" <?php CB2::post_class( $classes ); ?> style='background-color:<?php CB2::the_colour( $item ); ?>'>&nbsp;</li>
