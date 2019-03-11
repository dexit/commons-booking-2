<?php

/*
Plugin Name: CMB2 Field Type: Post_Data
Plugin URI: https://wordpress.org/plugins/commons-booking
GitHub Plugin URI: https://wordpress.org/plugins/commons-booking
Description: Post_Data field type for CMB2.
Version: 0.1.3
Author: Annesley Newholm
License: MIT
*/

define('CMB2_POST_DATA_NAME', 'post_data');

class CMB2_Field_Post_Data {

    /**
     * @var string Version
     */
    const VERSION = '0.1.0';

    /**
     * CMB2_Field_Post_Data constructor.
     */
    public function __construct() {
        add_filter( 'cmb2_render_post_data', [ $this, 'render_post_data_selector' ], 10, 5 );
    }

    /**
     * Render the field
     *
     * @param $field
     * @param $field_escaped_value
     * @param $object_id
     * @param $object_type
     * @param $field_type_object
     */
    public function render_post_data_selector(
        CMB2_Field $field,
        $field_escaped_value,
        $object_id,
        $object_type,
        CMB2_Types $field_type_object
    ) {
				global $post;

        if ( version_compare( CMB2_VERSION, '2.2.2', '>=' ) ) {
            $field_type_object->type = new CMB2_Type_Text( $field_type_object );
        }

        // Post_Datas
        $attributes = $field->args();
        $ID         = ( isset( $attributes['ID'] )
					? $attributes['ID']
					: ( $field_escaped_value ? $field_escaped_value : $post->ID )
				);
        $post_type  = ( isset( $attributes['post_type'] )
					? $attributes['post_type']
					: $post->post_type
				);
        $field      = ( isset( $attributes['field'] ) ? $attributes['field'] : 'post_title' );

				$this->ID        = $ID;
				$this->post_type = $post_type;

        if ( $ID ) {
					$query = new WP_Query( array(
						'p'         => $ID,
						'post_type' => $post_type,
					) );
					if ( is_array( $query->posts ) && count( $query->posts ) ) {
						$post          = $query->posts[0];
						$data          = $post->{$field};
						print( "<div class='post_data $field'>$data</div>");
					} else {
						print( '<div class="error">' . __( 'Post not found' ) . ": $post_type / $ID</div>" );
					}
				} else {
					print( '<div class="error">' . __( 'No ID' ) . '</div>' );
				}

        $field_type_object->_desc( true, true );
    }
}

new CMB2_Field_Post_Data();
