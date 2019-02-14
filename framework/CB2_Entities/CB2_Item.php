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
            'supports' => array('title','thumbnail','editor','excerpt')
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

	static function metaboxes() {

		$metaboxes = array();

		return apply_filters('cb2_item_metaboxes', $metaboxes);
	}

    public function post_type()
    {
        return self::$static_post_type;
    }

    public function __construct($ID)
    {
				CB2_Query::assign_all_parameters( $this, func_get_args(), __class__ );
				parent::__construct($ID);
				self::$all[$ID] = $this;

        // WP_Post values
        $this->post_type = self::$static_post_type;
    }

    public static function &factory_from_properties(&$properties, &$instance_container = null, $force_properties = false)
    {
        $object = self::factory(
            (isset($properties['item_ID']) ? $properties['item_ID'] : $properties['ID'])
        );

        self::copy_all_wp_post_properties($properties, $object);

        return $object;
    }

    public static function &factory( Int $ID )
    {
        // Design Patterns: Factory Singleton with Multiton
        $object = null;
        $key    = $ID;

        if ( $key && $ID != CB2_CREATE_NEW && isset( self::$all[$key] ) ) {
					$object = self::$all[$ID];
				} else {
					$reflection = new ReflectionClass( __class__ );
					$object     = $reflection->newInstanceArgs( func_get_args() );
				}

        return $object;
    }

    public function get_the_after_content()
    {
				// Booking form
				$Class       = get_class($this);
				$ID          = $this->ID;
				$form_action = '';
				$do_action   = 'book';
				$submit      = __('book the')   . " $this->post_title";
				$name        = __('Booking of') . " $this->post_title";
				$view_mode   = CB2_Week::$static_post_type;        // posts data reorganised into CB2_TimeClass hierarchy
				$selection_mode    = 'range'; // Only 1 range, from => to, can be selected
				$display_strategy  = 'CB2_SingleItemAvailability'; // posts filtered according to use case
				// TODO: initial CB2_Item booking calendar pagesize settings
				$startdate   = '';
				$enddate     = '';

				$shortcode_atts = array(
					'start-date'       => $startdate,
					'end-date'         => $enddate,
					'view-mode'        => $view_mode,
					'display-strategy' => $display_strategy,
					'selection-mode'   => $selection_mode,
        );
        $shortcode_atts_string = CB2_Query::implode( ' ', $shortcode_atts, '=', NULL, FALSE ); // Ignore empty

        $form = "<form action='$form_action' method='POST'><div>
						<input type='hidden' name='name' value='$name' />
						<input type='hidden' name='do_action' value='$Class::$do_action' />
						<input type='hidden' name='do_action_post_ID' value='$ID' />
						<input type='submit' name='submit' value='$submit' />
						[cb2_calendar $shortcode_atts_string]
						<input type='submit' name='submit' value='$submit' />
					</div></form>";
				$form = str_replace( "\n", '', $form ); // Prevent WordPress replacing with <br>

				return $form;
    }

    public function do_action_book(CB2_User $user, array $values)
    {
        // The booking times are based on the perioditems selected
        if (! isset($values['perioditem-timeframes'])) {
            krumo($values);
            throw new Exception("perioditem-timeframes required during [$action]");
        }
        if (! is_array($values['perioditem-timeframes'])) {
            krumo($values);
            throw new Exception("perioditem-timeframes not an array during [$action]");
        }
        if ( count($values['perioditem-timeframes']) == 0) {
            krumo($values);
            throw new Exception("perioditem-timeframes empty during [$action]");
        }

        // Book these availabilities
        $available_perioditems = $values['perioditem-timeframes'];
        $name                  = __('Booking');
        $copy_period_group     = true;      // Default
        $count                 = count($available_perioditems);
        $selection_mode        = ( isset( $values['selection_mode'] ) ? $values['selection_mode'] : 'range' );

        if (isset($values['name'])) {
            $name = str_replace(__('available'), __('booking'), $values['name']);
        }

        switch ( $selection_mode ) {
					case 'range':
						switch ( $count ) {
							case 1:
								$periodentity_booking = CB2_PeriodEntity_Timeframe_User::factory_booked_from_available_timeframe_item(
										$available_perioditems[0],
										$user,
										$name,
										$copy_period_group
								);
								// Create object only (e.g. period_status_type will not be updated),
								// and fire wordpress post events
								$periodentity_booking->save();
								break;
							case 2:
								// We want the earliest start and the latest end
								// in the case that one perioditem's time span completely contains the other,
								// we would send through the larger perioditem twice
								$available_perioditem_from = (
									$available_perioditems[0]->datetime_period_item_start->before( $available_perioditems[1]->datetime_period_item_start ) ?
									$available_perioditems[0] :
									$available_perioditems[1]
								);
								$available_perioditem_to = (
									$available_perioditems[0]->datetime_period_item_end->after( $available_perioditems[1]->datetime_period_item_end ) ?
									$available_perioditems[0] :
									$available_perioditems[1]
								);

								$periodentity_booking = CB2_PeriodEntity_Timeframe_User::factory_booked_from_available_timeframe_item_from_to(
										$available_perioditem_from,
										$available_perioditem_to,
										$user,
										$name,
										$copy_period_group
								);
								// Create object only (e.g. period_status_type will not be updated),
								// and fire wordpress post events
								$periodentity_booking->save();
								break;
							default:
								throw new Exception( "Booking failed because too many [$count] perioditem-timeframes provided. 1 or 2 is acceptable only." );
						}
						break;
					default:
						foreach ($available_perioditems as $available_perioditem) {
								$periodentity_booking = CB2_PeriodEntity_Timeframe_User::factory_booked_from_available_timeframe_item(
										$available_perioditem,
										$user,
										$name,
										$copy_period_group
								);
								// Create objects only (e.g. period_status_type will not be updated),
								// and fire wordpress post events
								$periodentity_booking->save();
						}
						break;
				}

        return "<div>processed ($count) perioditem availabile in to bookings</div>";
    }

    public function manage_columns($columns)
    {
        $columns['availability'] = 'Pickup/Return <a href="admin.php?page=cb2-timeframes">view all</a>';
        $columns['bookings']     = 'Bookings <a href="admin.php?page=cb2-bookings">view all</a>';
        return $columns;
    }

    public function custom_columns($column)
    {
        $wp_query_page_name = "paged-column-$column";
        $current_page       = (isset($_GET[$wp_query_page_name]) ? $_GET[$wp_query_page_name] : 1);
        $has_locations      = count(CB2_forms::location_options());

        switch ($column) {
            case 'availability':
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
                print("<div class='cb2-column-actions'>");
                $page         = 'cb2-post-new';
                $add_new_text = ('add new item availability');
                $post_title   = __('Pickup/Return for') . " $this->post_title";
                $add_link     = "admin.php?page=$page&item_ID=$this->ID&post_type=periodent-timeframe&period_status_type_id=1&post_title=$post_title";
                if ($has_locations) {
                    print("<a href='$add_link'>$add_new_text</a>");
                } else {
                    print('<span class="cb2-no-data-notice">' . __('Add a Location first') . '</span>');
                }
                print('</div>');
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
                print("<div class='cb2-column-actions'>");
                $page       = 'cb2-post-new';
                $post_title = __('Booking of') . " $this->post_title";
								$booked_ID  = CB2_PeriodStatusType_Booked::bigID();
                $add_new_booking_text = __('add new booking');
                if ( $has_locations ) {
                    $add_link   = "admin.php?page=$page&item_ID=$this->ID&post_type=periodent-user&period_status_type_ID=$booked_ID&post_title=$post_title";
                    print(" <a href='$add_link'>$add_new_booking_text</a>");
                    $page       = 'cb2_menu';
                    $view_booking_text = __('view in calendar');
                    $view_link  = "admin.php?page=$page&item_ID=$this->ID&period_status_type_ID=$booked_ID";
                    print(" | <a href='$view_link'>$view_booking_text</a>");
                } else {
                    print('<span class="cb2-no-data-notice">' . __('Add a Location first') . '</span>');
                }
                print('</div>');
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
        if ($period_count) {
            $action .= " <span class='cb2-usage-count-warning' title='Number of registered repair periods'>$period_count</span> ";
        }
        $action .= '</a></span>';

        $actions[ 'manage_repairs' ] = $action;
    }
    function get_api_data(string $version){
        $data = array(
            'id' => $this->ID,
            'name' => get_the_title($this),
            'url' => get_post_permalink($this),
            'owner_id' => get_the_author_meta('ID', $this->post_author),
            'availability' => array()
        );
        $excerpt = $this->post_excerpt;
        if($excerpt != NULL){
            $data['description'] = $excerpt;
        }
        if($this->perioditems != null){
            foreach($this->perioditems as $period_inst){
                $data['availability'][] = $period_inst->get_api_data($version);
            }
        }
        return $data;
    }
}
