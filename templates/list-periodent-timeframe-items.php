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
$href_click      = get_the_permalink( $item );
$href_title_text = __( 'View' );

// AJAX Popup navigation
if ( CB2_AJAX_POPUPS && FALSE ) {
	$ID        = get_the_ID( $item );
	$post_type = get_post_type( $item );
	$page      = 'cb2-load-template';
	$action    = 'edit'; // context = 'popup'
	$template_loader_url = plugins_url(
		"admin/load_template.php?page=$page&action=$action&ID=$ID&post_type=$post_type",
		dirname( __FILE__ )
	);
	$href_class = 'thickbox';
	$href_click = "$template_loader_url&title=$href_title_text";
}

$classes = array( 'cb2-item-name', 'cb2-template-items' );
?>
<li id="post-<?php the_ID(); ?>" <?php CB2::post_class( $classes ); ?> style='background-color:<?php CB2::the_colour( $item ); ?>'>
	<a class="cb2-details cb2-bald <?php print( $href_class ); ?>" title="<?php print( $href_title_text ); ?>" href="<?php print( $href_click ); ?>">
		<?php CB2::the_short_name( $item ); ?>
	</a>
</li>
