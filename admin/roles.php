<?php
print( "<h1>Roles &amp; Capabilities</h1>" );
$reset_roles         = isset( $_GET['reset_roles'] ); // Checkbox
$reset_roles_checked = ( $reset_roles ? 'checked="1"' : '' );
print( '<div class="cb2-actions">' );
if ( WP_DEBUG ) print( "<form><div>
		<input type='hidden' name='page' value='cb2-roles'/>
		<input type='hidden' name='section' value='reset_roles'>
		<input onclick='$processing' class='cb2-submit' type='submit' value='reset roles &amp; capabilities'/>
	</div></form>" );
print( '</div><hr/>' );

if ( isset( $_GET['section'] ) ) {
	switch ( $_GET['section'] ) {
		case 'reset_roles':
			CB2_ActDeact::add_roles();
			CB2_ActDeact::add_capabilities();
			print( '<div>Role reset successful</div>' );
			break;
		default:
			print( "<div>commad [$_GET[section]] not understood</div>" );
	}
}
