<?php
/**
 * CB2 Item class
 *
 * Post type config
 * Post type selection metabox
 * Editor metaboxes
 * Content filters
 * Actions related to the item (booking)
 * Admin column setup
 *
 *
 * @package   CommonsBooking2
 * @author    The CommonsBooking Team <commonsbooking@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */


class CB2_Item extends CB2_Post implements JsonSerializable
{
		public static $all = array();
		public static $static_post_type   = 'item';
		public static $rewrite   = array( 'slug' => 'item' );
		public static $post_type_args = array(
            'menu_icon' => 'dashicons-video-alt',
            'supports' => array('title','thumbnail','editor','excerpt', 'custom-fields')
		);

    public static function selector_metabox( String $context = 'normal', Array $classes = array(), $none = TRUE )
    {
        return array(
            'title'      => __('Item', 'commons-booking-2'),
						'context'    => $context,
						'classes'    => $classes,
            'show_names' => false,
            'fields'     => array(
                array(
                    'name'    => __('Item', 'commons-booking-2'),
                    'id'      => 'item_ID',
                    'type'    => 'select',
                    'default' => (isset($_GET['item_ID']) ? $_GET['item_ID'] : null),
                    'options' => CB2_Forms::item_options( $none ),
                ),
								CB2_Query::metabox_nosave_indicator( 'item_ID' ),
            ),
        );
		}

	static function post_link_metabox( String $context = 'normal', Array $classes = array(), $none = TRUE ) {
		return array(
			'title'      => __( 'Item', 'commons-booking-2' ),
			'context'    => $context,
			'classes'    => $classes,
			'show_names' => FALSE,
			'fields'     => array(
				array(
					'name'      => __( 'Item', 'commons-booking-2' ),
					'id'        => 'item_ID',
					'type'      => 'post_link',
					'default'   => ( isset( $_GET['item_ID'] ) ? $_GET['item_ID'] : NULL ),
					'post_type' => CB2_Item::$static_post_type,
				),
				CB2_Query::metabox_nosave_indicator( 'item_ID' ),
			),
		);
	}

	static function post_view_metabox( String $context = 'normal', Array $classes = array(), $none = TRUE ) {
		$ID_field  = 'item_ID';
		$type_name = 'Item';

		return array(
			'title'      => __( "$type_name View", 'commons-booking-2' ),
			'context'    => 'normal',
			'show_on_cb' => array( 'CB2', 'is_published' ),
			'classes'    => array( 'cb2-object-summary-bar' ),
			'show_names' => FALSE,
			'fields'     => array(
				array(
					'name'      => __( $type_name, 'commons-booking-2' ),
					'id'        => $ID_field,
					'action'    => 'view',
					'title'     => __( 'view on website', 'commons-booking-2' ),
					'type'      => 'post_link',
					'default'   => ( isset( $_GET[$ID_field] ) ? $_GET[$ID_field] : NULL ),
					'post_type' => CB2_Item::$static_post_type,
				),
				CB2_Query::metabox_nosave_indicator( $ID_field ),
			),
		);
	}

	static function metaboxes() {
		$metaboxes = array(
			array(
				'title'      => __('Use a Location Opening Hours for Pickup/Return', 'commons-booking-2'),
				'context'    => 'side',
				'show_on_cb' => array( 'CB2', 'is_not_published' ),
				'show_names' => TRUE,
				'fields'     => array(
					array(
						'id'      => 'use_opening_hours',
						'name'    => __('Use Opening Hours', 'commons-booking-2'),
						'type'    => 'checkbox',
						'default' => TRUE,
					),
					array(
						'name'    => __('Location', 'commons-booking-2'),
						'id'      => 'opening_hours_location_ID',
						'type'    => 'select',
						'options' => CB2_Forms::location_options(),
					),
					CB2_Query::metabox_nosave_indicator( 'item_ID' ),
				),
			),
		);

		return apply_filters('cb2_item_metaboxes', $metaboxes);
	}

    public function post_type()
    {
        return self::$static_post_type;
    }

    protected function __construct($ID)
    {
				CB2_Query::assign_all_parameters( $this, func_get_args(), __class__ );
				parent::__construct($ID);
    }

    public static function factory_from_properties(&$properties, &$instance_container = null, Bool $force_properties = FALSE, Bool $set_create_new_post_properties = FALSE)
    {
        $object = self::factory(
            (int) (isset($properties['item_ID']) ? $properties['item_ID'] : $properties['ID']),
            $properties, $force_properties
        );

        return $object;
    }

    public static function factory( Int $ID, Array $properties = NULL, Bool $force_properties = FALSE, Bool $set_create_new_post_properties = FALSE )
    {
			return CB2_PostNavigator::createInstance( __class__, func_get_args(), $ID, $properties, $force_properties, $set_create_new_post_properties );
    }

    public function get_the_after_content() {
			$templates = $this->templates( 'single', 'booking' ); // single-item-booking.php
			return cb2_get_template_part( CB2_TEXTDOMAIN, $templates, '', array(), TRUE );
    }

    public function do_action_book( CB2_User $user, array $values )
    {
        // The booking times are based on the periodinsts selected
        $action     = 'book';
        $booking_ID = NULL;

        if (! isset($values['periodinst-timeframes'])) {
            krumo($values);
            throw new Exception("periodinst-timeframes required during [$action]");
        }
        if (! is_array($values['periodinst-timeframes'])) {
            krumo($values);
            throw new Exception("periodinst-timeframes not an array during [$action]");
        }
        if ( count($values['periodinst-timeframes']) == 0) {
            krumo($values);
            throw new Exception("periodinst-timeframes empty during [$action]");
        }

        // Book these availabilities
        $available_periodinsts = $values['periodinst-timeframes'];
        $name                  = __('Booking');
        $copy_period_group     = true;      // Default
        $count                 = count($available_periodinsts);
        $selection_mode        = ( isset( $values['selection_mode'] ) ? $values['selection_mode'] : 'range' );

        if (isset($values['name'])) {
            $name = str_replace(__('available'), __('booking'), $values['name']);
        }

        switch ( $selection_mode ) {
					case 'range':
						switch ( $count ) {
							case 1:
								$periodentity_booking = CB2_PeriodEntity_Timeframe_User::factory_booked_from_available_timeframe_item(
										$available_periodinsts[0],
										$user,
										$name,
										$copy_period_group
								);
								// Create object only (e.g. period_status_type will not be updated),
								// and fire wordpress post events
								$booking_ID = $periodentity_booking->save();
								break;
							case 2:
								// We want the earliest start and the latest end
								// in the case that one periodinst's time span completely contains the other,
								// we would send through the larger periodinst twice
								$available_periodinst_from = (
									$available_periodinsts[0]->datetime_period_inst_start->before( $available_periodinsts[1]->datetime_period_inst_start ) ?
									$available_periodinsts[0] :
									$available_periodinsts[1]
								);
								$available_periodinst_to = (
									$available_periodinsts[0]->datetime_period_inst_end->after( $available_periodinsts[1]->datetime_period_inst_end ) ?
									$available_periodinsts[0] :
									$available_periodinsts[1]
								);

								$periodentity_booking = CB2_PeriodEntity_Timeframe_User::factory_booked_from_available_timeframe_item_from_to(
										$available_periodinst_from,
										$available_periodinst_to,
										$user,
										$name,
										$copy_period_group
								);
								// Create object only (e.g. period_status_type will not be updated),
								// and fire wordpress post events
								$booking_ID = $periodentity_booking->save();
								break;
							default:
								throw new Exception( "Booking failed because too many [$count] periodinst-timeframes provided. 1 or 2 is acceptable only." );
						}
						break;
					default:
						foreach ($available_periodinsts as $available_periodinst) {
								$periodentity_booking = CB2_PeriodEntity_Timeframe_User::factory_booked_from_available_timeframe_item(
										$available_periodinst,
										$user,
										$name,
										$copy_period_group
								);
								// Create objects only (e.g. period_status_type will not be updated),
								// and fire wordpress post events
								$booking_ID = $periodentity_booking->save();
						}
						break;
				}

        return $booking_ID;
    }

    function post_post_update() {
			global $wpdb;

			if ( isset( $_POST['use_opening_hours'] ) ) {
				if ( $location_ID = $_POST['opening_hours_location_ID'] ) {
					$period_group_ID = $wpdb->get_var( $wpdb->prepare( "select period_group_ID
							from {$wpdb->prefix}cb2_location_period_groups
							where location_ID = %d and period_status_type_ID = %d limit 1",
						array( $location_ID, CB2_PeriodStatusType_Open::bigID(), )
					) );
					if ( $period_group_ID ) {
						$pickup_return_text = __( 'Pickup/Return' );
						$period_group       = CB2_PeriodGroup::factory( $period_group_ID );
						// We did not load the Periods so let us not wipe them
						$period_group->set_saveable( FALSE );
						$location           = CB2_Location::factory( $location_ID );
						$item_pickup_return = CB2_PeriodEntity_Timeframe::factory(
							CB2_CREATE_NEW,
							"$this->ID $pickup_return_text",
							$period_group,
							new CB2_PeriodStatusType_PickupReturn(),
							TRUE, NULL, NULL,
							$location, $this
						);
						if ( CB2_DEBUG_SAVE ) krumo( $item_pickup_return );
						$item_pickup_return->save();
					} else {
						if ( CB2_DEBUG_SAVE ) print( "<div class='cb2-WP_DEBUG'>no opening hours found for location [$location_ID]</div>" );
					}
				} else {
					if ( CB2_DEBUG_SAVE ) print( "<div class='cb2-WP_DEBUG'>no location sent when creating pickup/return times</div>" );
				}
			}
		}

    public function manage_columns($columns)
    {
        $columns['pickup_return'] = 'Pickup/Return <a href="admin.php?page=cb2-timeframes">view all</a>';
        $columns['bookings']     = 'Bookings <a href="admin.php?page=cb2-bookings">view all</a>';
        return $columns;
    }

    public function custom_columns($column)
    {
        $wp_query_page_name = "paged-column-$column";
        $current_page       = (isset($_GET[$wp_query_page_name]) ? $_GET[$wp_query_page_name] : 1);
        $has_locations      = count(CB2_forms::location_options());

        switch ($column) {
            case 'pickup_return':
                $wp_query           = new WP_Query(array(
                    'post_type'   => 'periodent-timeframe',
                    'meta_query'  => array(
											'entities' => array(
                        'item_ID_clause' => array(
                            'key'   => 'item_ID',
                            'value' => $this->ID,
                        ),
                        'period_status_type_clause' => array(
                            'key'   => 'period_status_type_id',
                            'value' => CB2_PeriodStatusType_PickupReturn::$id,
                        ),
                        'relation' => 'AND',
											),
                    ),
                    'posts_per_page' => CB2_ADMIN_COLUMN_POSTS_PER_PAGE,
                    'page'           => $current_page,
                ));

                if ($wp_query->have_posts()) {
                    print('<ul class="cb2-admin-column-ul">');
                    CB2::the_inner_loop( NULL, $wp_query, 'admin', 'summary');
                    print('</ul>');
                } else {
                    print('<div>' . __('No Item Availability') . '</div>');
                }

                if ( current_user_can( 'edit_post', $this->ID ) ) {
									print("<div class='cb2-column-actions'>");
									$page         = 'cb2-post-new';
									$add_new_text = ('add new pickup return times');
									$post_title   = __('Pickup/Return for') . " $this->post_title";
									$add_link     = "admin.php?page=$page&item_ID=$this->ID&post_type=periodent-timeframe&period_status_type_id=1&post_title=$post_title";
									if ($has_locations) {
											print("<a href='$add_link'>$add_new_text</a>");
									} else {
											print('<span class="cb2-no-data-notice">' . __('Add a Location first') . '</span>');
									}
									print('</div>');
								}

                break;

            case 'bookings':
                $wp_query = new WP_Query(array(
                    'post_type'   => 'periodent-user',
                    'meta_query'  => array(
											'entities' => array(
												'item_ID_clause' => array(
                            'key'   => 'item_ID',
                            'value' => $this->ID,
                        ),
                        'period_status_type_clause' => array(
                            'key'   => 'period_status_type_id',
                            'value' => CB2_PeriodStatusType_Booked::$id,
                        ),
                        'relation' => 'AND',
											),
                    ),
                    'posts_per_page' => CB2_ADMIN_COLUMN_POSTS_PER_PAGE,
                    'page'           => $current_page,
                ));

                if ($wp_query->have_posts()) {
                    print('<ul class="cb2-admin-column-ul">');
                    CB2::the_inner_loop( NULL, $wp_query, 'admin', 'summary');
                    print('</ul>');
                } else {
                    print('<div>' . __('No Bookings') . '</div>');
                }

                if ( current_user_can( 'edit_post', $this->ID ) ) {
									print("<div class='cb2-column-actions'>");
									$page       = 'cb2-post-new';
									$post_title = __('Booking of') . " $this->post_title";
									$booked_ID  = CB2_PeriodStatusType_Booked::bigID();
									$add_new_booking_text = __('add new booking');
									if ( $has_locations ) {
											$add_link   = "admin.php?page=$page&item_ID=$this->ID&post_type=periodent-user&period_status_type_ID=$booked_ID&post_title=$post_title";
											print(" <a href='$add_link'>$add_new_booking_text</a>");
											if ( $wp_query->post_count ) {
												$page       = 'cb2-menu';
												$view_booking_text = __('view in calendar');
												$view_link  = "admin.php?page=$page&item_ID=$this->ID&period_status_type_ID=$booked_ID";
												print(" | <a href='$view_link'>$view_booking_text</a>");
											}
									} else {
											print('<span class="cb2-no-data-notice">' . __('Add a Location first') . '</span>');
									}
									print('</div>');
								}

                break;
        }

        print('<div class="cb2-column-pagination">' . paginate_links(array(
            'base'         => 'admin.php%_%',
            'total'        => $wp_query->max_num_pages,
            'current'      => $current_page,
            'format'       => "?$wp_query_page_name=%#%",
        )) . '</div>');
    }

    public function row_actions(&$actions, $post)
    {
        $wp_query = new WP_Query(array(
            'post_type'   => 'periodent-user',
            'meta_query'  => array(
                'item_ID_clause' => array(
                    'key'   => 'item_ID',
                    'value' => $this->ID,
                ),
                'relation' => 'AND',
                'period_status_type_clause' => array(
                    'key'   => 'period_status_type_id',
                    'value' => CB2_PeriodStatusType_Repair::$id,
                ),
            ),
        ));
        $period_count = $wp_query->post_count;

        $action = "<span style='white-space:nowrap;'><a href='admin.php?page=cb2-repairs&item_ID=$this->ID'>Repairs";
        if ($period_count)
            $action .= " <span class='cb2-usage-count-warning' title='Number of registered repair periods'>$period_count</span> ";
        $action .= '</a></span>';

				if ( WP_DEBUG ) $action .= " <span class='cb2-WP_DEBUG-small'>$this->post_author</span>";

        $actions[ 'manage_repairs' ] = $action;
    }

    function get_api_data(string $version){
        $data = array(
            'uid' => get_the_guid($this),
            'name' => get_the_title($this),
            'url' => get_post_permalink($this),
            'owner_uid' => (string)get_the_author_meta('ID', $this->post_author)
        );
        $excerpt = $this->post_excerpt;
        if($excerpt != NULL){
            $data['description'] = $excerpt;
        }
        if($this->periodinsts != null){
            foreach($this->periodinsts as $period_inst){
                $data['availability'][] = $period_inst->get_api_data($version);
            }
        }
        $data = apply_filters('cb2_api_add_item_metadata', $data, $this);
        return $data;
    }
}
