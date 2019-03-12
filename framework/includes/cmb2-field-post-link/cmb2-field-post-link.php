<?php

/*
Plugin Name: CMB2 Field Type: Post_Link
Plugin URI: https://wordpress.org/plugins/commons-booking
GitHub Plugin URI: https://wordpress.org/plugins/commons-booking
Description: Post_Link field type for CMB2.
Version: 0.1.3
Author: Annesley Newholm
License: MIT
*/

define('CMB2_POST_LINK_NAME', 'post_link');

class CMB2_Field_Post_Link {

    /**
     * @var string Version
     */
    const VERSION = '0.1.0';

    /**
     * CMB2_Field_Post_Link constructor.
     */
    public function __construct() {
        add_filter( 'cmb2_render_post_link', [ $this, 'render_post_link_selector' ], 10, 5 );
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
    public function render_post_link_selector(
        CMB2_Field $field,
        $field_escaped_value,
        $object_id,
        $object_type,
        CMB2_Types $field_type_object
    ) {
        if ( version_compare( CMB2_VERSION, '2.2.2', '>=' ) ) {
            $field_type_object->type = new CMB2_Type_Text( $field_type_object );
        }

        // Post_Links
        $attributes = $field->args();
        $ID         = ( isset( $attributes['ID'] ) ? $attributes['ID'] : $field_escaped_value );
        $post_type  = ( isset( $attributes['post_type'] ) ? $attributes['post_type'] : 'post' );
				$target     = ( isset( $attributes['target'] ) ? $attributes['target'] : NULL );
				$action     = ( isset( $attributes['action'] ) ? $attributes['action'] : 'edit' );
				$title      = ( isset( $attributes['title'] )  ? $attributes['title']  : NULL );

				$this->ID        = $ID;
				$this->post_type = $post_type;

        if ( $ID ) {
					$query = new WP_Query( array(
						'p'         => $ID,
						'post_type' => $post_type,
					) );
					if ( is_array( $query->posts ) && count( $query->posts ) ) {
						$post          = $query->posts[0];
						$title         = ( $title ? $title : $post->post_title );
						switch ( $action ) {
							case 'edit':
								$url = $this->get_edit_post_link( $ID, $post_type );
								break;
							default:
								$url = get_the_permalink( $post );
						}
						$target_string = ( $target ? "target='$target'" : '' );
						$link_html     = "<a $target_string href='$url'>$title</a>";
						print( "<div><span class='post_type hidden'>$post_type<span class='colon'>: </span></span>$link_html</div>");
					} else {
						print( '<div class="error">' . __( 'Post not found' ) . ": $post_type / $ID</div>" );
					}
				} else {
					print( '<div class="error">' . __( 'No ID' ) . '</div>' );
				}

        $field_type_object->_desc( true, true );
    }

    function get_edit_post_link( $ID, $post_type, $context = 'link' ) {
			// Taken from WordPress link-template.php get_edit_post_link()
			if ( 'revision' === $post_type )
				$action = '';
			elseif ( 'display' == $context )
				$action = '&amp;action=edit';
			else
				$action = '&action=edit';

			$post_type_object = get_post_type_object( $post_type );
			if ( !$post_type_object )
				return;

			if ( !current_user_can( 'edit_post', $ID ) )
				return;

			if ( $post_type_object->_edit_link ) {
				$link = admin_url( sprintf( $post_type_object->_edit_link . $action, $ID ) );
			} else {
				$link = '';
			}

			return $link;
		}
}

new CMB2_Field_Post_Link();
