<?php
class CB_PeriodGroup extends CB_PostNavigator implements JsonSerializable {
	// TODO: use this generic period class
  public static $database_table = 'cb2_period_groups';
	public static $all = array();
  static $static_post_type = 'periodgroup';
  public static $post_type_args = array(
		'menu_icon' => 'dashicons-admin-settings',
		'label'     => 'Period Groups',
  );
  public $periods  = array();
	static function selector_metabox() {
		return array(
			'title'      => __( 'Existing Period Group', 'commons-booking-2' ),
			'show_names' => FALSE,
			'context'    => 'side',
			'fields'     => array(
				array(
					'name'    => __( 'PeriodGroup', 'commons-booking-2' ),
					'id'      => 'commons-booking-2' . '_user_ID',
					'type'    => 'select',
					'show_option_none' => TRUE,
					'default' => $_GET['period_group_ID'],
					'options' => CB_Forms::period_group_options(),
				),
			),
		);
	}

  function post_type() {return self::$static_post_type;}

  static function &factory_from_wp_post( $post ) {
		if ( $post->ID ) CB_Query::get_metadata_assign( $post ); // Retrieves ALL meta values
		if ( ! $post->period_IDs ) throw new Exception( 'CB_PeriodGroup requires period_IDs list, which can be empty' );

		$periods = array();
		foreach ( explode( ',', $post->period_IDs) as $period_id ) {
			$period = CB_Query::get_post_type( 'period', $period_id );
			array_push( $periods, $period );
		}

		$object = self::factory(
			$post->ID,
			$post->period_group_id,
			$post->post_title,
			$periods
		);

		CB_Query::copy_all_properties( $post, $object );

		return $object;
	}

  static function &factory(
		$ID              = NULL,
		$period_group_id = NULL,
		$name            = NULL,
		$periods         = NULL
  ) {
    // Design Patterns: Factory Singleton with Multiton
		if ( ! is_null( $ID ) && isset( self::$all[$ID] ) ) {
			$object = self::$all[$ID];
    } else {
			$reflection = new ReflectionClass( __class__ );
			$object     = $reflection->newInstanceArgs( func_get_args() );
    }

    return $object;
  }

  public function __construct(
		$ID              = NULL,
		$period_group_id = NULL,
		$name            = NULL,
		$periods         = NULL
  ) {
		CB_Query::assign_all_parameters( $this, func_get_args(), __class__ );
		$this->id      = $period_group_id;
		$this->periods = $periods;
		parent::__construct( $this->periods );
		if ( ! is_null( $ID ) ) self::$all[$ID] = $this;
  }

  function add_period( $period ) {
		array_push( $this->periods, $period );
  }

  function add_actions( &$actions, $post ) {
		unset( $actions['edit'] );
		unset( $actions['view'] );
	}

  function manage_columns( $columns ) {
		$columns['periods'] = 'Periods';
		$this->move_column_to_end( $columns, 'date' );
		return $columns;
	}

	function custom_columns( $column ) {
		$html = '';
		switch ( $column ) {
			case 'periods':
				$html .= ( '<ul class="cb2-period-list">' );
				$count = count( $this->periods );
				foreach ( $this->periods as $period ) {
					$edit_link   = "<a href='?page=cb-post-edit&post=$period->ID&post_type=period&action=edit'>edit</a>";

					$detach_link = '';
					if ( $count > 1 ) {
						$detach_text = 'detach';
						$detach_url  = "?page=cb-post-edit&post=$period->ID&post_type=periodgroup&action=detach";
						if ( $period->usage_once() ) {
							$detach_text = 'delete';
							$detach_url  = "?page=cb-post-edit&post=$period->ID&post_type=period&action=delete";
						}
						$detach_link = " | <a href='$detach_url'>$detach_text</a></li>";
					}

					$summary     = $period->summary();
					$html       .= "<li>$summary $edit_link $detach_link</li>";
				}
				$html .= ( '</ul>' );
				$html .= ( '<a href="?">add new period</a> | <a href="?">attach existing period</a>' );
				break;
		}
		return $html;
	}

  function classes() {
		return '';
  }

  function post_post_update() {
		global $wpdb;

		parent::post_save_post();

		$table = "{$wpdb->prefix}cb2_period_group_period";
		$wpdb->delete( $table, array(
			'period_group_id' => $this->id
		) );
		foreach ( $this->posts as $post ) {
			$wpdb->insert( $table, array(
				'period_group_id' => $this->id,
				'period_id'       => $post->id,
			) );
		}
  }

  function jsonSerialize() {
		return $this;
	}
}

CB_Query::register_schema_type( 'CB_PeriodGroup' );
