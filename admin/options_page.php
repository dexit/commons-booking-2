<?php
// main CB2 options page
global $wpdb;

print( '<h1>Commons Booking 2 Settings <a href="admin.php?page=cb2-menu">dashboard</a></h1>' );
print( '<ul>' );
$capability_default = 'manage_options';

foreach ( cb2_admin_pages() as $menu_slug => $details ) {
	$parent_slug  = ( isset( $details['parent_slug'] )  ? $details['parent_slug']  : CB2_MENU_SLUG );
	$page_title   = ( isset( $details['page_title'] )   ? preg_replace( '/\%.+\%/', '', $details['page_title'] ) : '' );
	$menu_title   = ( isset( $details['menu_title'] )   ? $details['menu_title']   : $page_title );
	$capability   = ( isset( $details['capability'] )   ? $details['capability']   : $capability_default );
	$function     = ( isset( $details['function'] )     ? $details['function']     : 'cb2_settings_list_page' );
	$advanced     = ( isset( $details['advanced'] )     ? $details['advanced']     : FALSE );
	$first        = ( isset( $details['first'] )        ? $details['first']        : FALSE );
	$menu_visible = ( isset( $details['menu_visible'] ) ? $details['menu_visible'] : ! $advanced );
	$description  = ( isset( $details['description'] )  ? $details['description']  : FALSE );

	// Menu adornments
	$class        = '';
	$indent       = '';
	$count_bubble = '';
	if ( isset( $details['count'] ) ) {
		$count       = $wpdb->get_var( $details['count'] );
		$count_class = ( isset( $details['count_class'] ) ? "cb2-usage-count-$details[count_class]" : 'cb2-usage-count-info' );
		if ( $count ) $count_bubble .= " <span class='$count_class'>$count</span>";
	}
	if ( isset( $details['indent'] ) )   $indent = str_repeat( '&nbsp;&nbsp;', $details['indent'] ) . '•&nbsp;';
	if ( isset( $details['first']) )     $class .= " $details[first]";
	if ( isset( $details['advanced'] ) ) $class .= ' cb2-advanced-menu-item';
	if ( current_user_can( $capability ) ) {
		print( "<li>$indent<a class='$class' href='admin.php?page=$menu_slug'>$menu_title</a> $count_bubble" );
	} else {
		print( "<li class='$class'>$indent$menu_title $count_bubble" );
	}
	if ( isset( $details['description'] ) ) print( "<p class='cb2-description'>$details[description]</p>" );
	print( "</li>" );
}
print( '</ul>' );

