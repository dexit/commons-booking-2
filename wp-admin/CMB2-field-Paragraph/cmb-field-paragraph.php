<?php
function cmb2_render_callback_for_paragraph( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {
	if ( isset( $field->args['html'] ) ) {
		print( '<p' );

		if ( isset( $field->args['classes'] ) ) {
			$classes = ( is_array( $field->args['classes'] ) ? implode( ' ', $field->args['classes'] ) : $field->args['classes'] );
			print( " class='$classes'" );
		}
		print( '>' );
		print( $field->args['html'] );
		print( '</p>' );
	}
}
add_action( 'cmb2_render_paragraph', 'cmb2_render_callback_for_paragraph', 10, 5 );
