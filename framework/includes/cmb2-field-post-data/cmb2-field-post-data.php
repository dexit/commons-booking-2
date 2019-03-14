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
        $name       = ( isset( $attributes['name'] ) ? $attributes['name'] : 'no name' );
        $ID         = ( isset( $attributes['ID'] )
					? $attributes['ID']
					: ( $field_escaped_value ? $field_escaped_value : $post->ID )
				);
        $post_type  = ( isset( $attributes['post_type'] )
					? $attributes['post_type']
					: $post->post_type
				);
        $field      = ( isset( $attributes['field'] )      ? $attributes['field']      : NULL );
        $text_paths = ( isset( $attributes['text_paths'] ) ? $attributes['text_paths'] : NULL );

				$this->ID        = $ID;
				$this->post_type = $post_type;

        if ( $ID ) {
					$query = new WP_Query( array(
						'p'         => $ID,
						'post_type' => $post_type,
					) );
					if ( is_array( $query->posts ) && count( $query->posts ) ) {
						$post = $query->posts[0];
						$data = '';
						if ( ! is_null( $field ) )      $data .= $post->{$field};
						if ( ! is_null( $text_paths ) ) $data .= self::array_walk_paths_string( $text_paths, '', $post );
						print( "<div class='post_data $field'>$data</div>");
					} else {
						print( '<div class="error">' . __( 'Post not found' ) . ": $name / $post_type / $ID</div>" );
					}
				} else {
					print( '<div class="error">' . __( 'No ID' ) . ": $name</div>" );
				}

        $field_type_object->_desc( true, true );
    }

		protected function array_walk_paths( Array &$array, $object ) {
			array_walk_recursive( $array, array( get_class(), 'array_walk_paths_string' ), $object );
		}

		protected function array_walk_paths_string( &$value, String $name, $object ) {
			if ( is_string( $value ) ) {
				if ( preg_match_all( '/%[^%]+%/', $value, $matches ) ) {
					foreach ( $matches[0] as $match ) {
						$replacement = self::object_value_path( $object, $match );
						$value       = str_replace( $match, $replacement, $value );
					}
				}
			}
			return $value;
		}

		protected function object_value_path( $object, $spec ) {
			// $spec = %object_property_name->object_property_name->...%
			// e.g. date->time
			$value = $spec;
			if ( is_string( $spec ) && preg_match( '/%[^%]+%/', $spec ) ) {
				$property_path = explode( '->', substr( $spec, 1, -1 ) );
				$properties    = (array) $object;
				foreach ( $property_path as $property_step ) {
					if ( is_array( $properties ) && isset( $properties[$property_step] ) ) {
						$value      = $properties[$property_step];
						$properties = (array) $value;
					} else if ( WP_DEBUG ) {
						krumo( $properties, $property_step );
						throw new Exception( "[$property_step] not found on object" );
					}
				}
				switch ( $property_step ) {
					case 'post_date':
					case 'post_modified':
						if ( $value ) {
							$date  = new DateTime( $value );
							$value = $date->format( get_option( 'date_format' ) );
						}
						break;
					case 'author':
						if ( $user = get_user_by( 'id', $value ) )
							$value = $user->user_login;
						break;
				}
			}
			return $value;
		}
}

new CMB2_Field_Post_Data();
