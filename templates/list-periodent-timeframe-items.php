<?php
global $post;

$period_entity  = $post->period_entity;
$period         = $post->period;
$location       = $period_entity->location;
$item           = $period_entity->item;
$item_permalink = get_permalink( $item );
$date_period    = $period->summary_date_period();

// Direct navigation to normal WordPress page option
$href_class      = '';
$href_click      = CB2::get_the_permalink( $item );
$href_title_text = __( 'View' );

// AJAX Popup navigation
if ( CB2_AJAX_POPUPS && FALSE ) {
	$ID        = get_the_ID( $item );
	$post_type = get_post_type( $item );
	$query_string  = CB2_Query::implode_query_string( array(
		'cb2_load_template' => 1,
		'page'         => 'cb2-post-edit', // To force is_admin()
		'context'      => 'popup',
		'template_type'=> 'edit',
		'ID'           => $ID,
		'post_type'    => $post_type,
		'title'        => $href_title_text,
	) );
	$href_click = admin_url( "admin.php?$query_string" );
	$href_class = 'thickbox';
}

$classes = array( 'cb2-item-name', 'cb2-template-items' );
?>
<li id="post-<?php the_ID(); ?>" <?php CB2::post_class( $classes ); ?> style='background-color:<?php CB2::the_colour( $item ); ?>'>
	<a class="cb2-details cb2-bald <?php print( $href_class ); ?>" title="<?php print( $href_title_text ); ?>" href="<?php print( $href_click ); ?>">
		<?php CB2::the_short_name( $item ); ?>
	</a>
</li>
