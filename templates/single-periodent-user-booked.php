
lah lah lah


<?php
global $post;
krumo($post);

the_author();

krumo( current_user_can( 'edit-post' ) );

$post->confirm(TRUE);
krumo($post);
krumo( CB2::is_confirmed() );
