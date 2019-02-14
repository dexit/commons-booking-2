<?php
global $post;

// Load wordpress
$DR = $_SERVER['DOCUMENT_ROOT'];
define( 'WP_USE_THEMES', FALSE );
require_once( "$DR/wp-load.php" );
require_once( "$DR/wp-admin/includes/admin.php" ); // Screens etc.
require_once( '../framework/CB2_framework.php' );  // meta boxes support

auth_redirect();

// ------------------------------------------- Inputs
$ID              = ( isset( $_GET['ID'] )        ? $_GET['ID']        : NULL );
$post_type       = ( isset( $_GET['post_type'] ) ? $_GET['post_type'] : NULL );
$action          = ( isset( $_GET['action'] )    ? $_GET['action']    : 'edit' );
$context         = ( isset( $_GET['context'] )   ? $_GET['context']   : 'popup' );
$context_post_ID = ( isset( $_GET['context_post_ID'] ) ? $_GET['context_post_ID'] : NULL );
$template_args   = $_GET;

// ------------------------------------------- Main post
// works without a post
// works with pseudo posts like CB2_Day
$post = NULL;
if ( $Class = CB2_PostNavigator::post_type_Class( $post_type ) ) {
	if ( CB2_Database::posts_table( $Class ) )
		$post = CB2_Query::get_post_with_type( $post_type, $ID );
	else if ( method_exists($Class, 'factory_from_properties' ) )
		$post = $Class::factory_from_properties( $_REQUEST ); // e.g. CB2_Day(date)
	else
		throw new Exception( "Cannot instantiate / get [$Class]" );
}
$templates = CB2::templates( $context, $action, FALSE, $templates_considered );

// ------------------------------------------- extra template_args
// The outer post displaying a calendar with days in it
if ( $context_post_ID )
	$template_args[ 'context_post' ] = CB2_Query::get_post_with_type( $_GET[ 'context_post_type' ], $_GET[ 'context_post_ID' ] );

// ------------------------------------------- DEBUG
if ( WP_DEBUG ) {
	print( "<!-- $ID/$post_type/$context-$action -->\n" );
	print( "<!-- Templates considered (in priority order): \n  " . implode( ", \n  ", $templates_considered ) . "\n -->\n" );
	print( "<!--\n" );
	foreach( $_POST as $name => $value ) {
		if      ( is_string( $value ) ) print( "  $name => $value\n" );
		else if ( is_array(  $value ) ) print( "  $name => Array(...)\n" );
		else print( "  $name => ...\n" );
	}
	print( "\n-->\n" );
}

if ( $_POST ) {
	// Indicates that a save is being requested
	// TODO: split this out to a save_worker.php
	// and use a different form action in CB2::the_hidden_form()
	// and move the save*.php files to a workers/ directory
	try {
		cb2_get_template_part( CB2_TEXTDOMAIN, $templates, '', $template_args );
	} catch ( Exception $ex ) {
		http_response_code( 500 );
		$message = htmlspecialchars( $ex->getMessage() );
		print( "<result message='$message'>Server Error</result>" );
	}
} else {
	// Normal popup mode

	// is_admin() CMB2 bootstrap
	// Cannot do this earlier because the screen is not a valid admin URL for wp-load.php
	// this is the 2nd time CMB2 bootstrap is called
	define( 'WP_ADMIN', TRUE );
	$screen = WP_Screen::get( $post_type );
	set_current_screen( $screen );
	cmb2_bootstrap();

	print( "<div class='cb2-$context cb2-$context-$action cb2-$context-$action-$post_type'>" );
	cb2_get_template_part( CB2_TEXTDOMAIN, $templates, '', $template_args );
	print( "<script>setTimeout(function(){
				jQuery('#TB_window').trigger('cb2-popup-appeared');
			}, 0);
		</script>" );
	print( '</div>' );
}
