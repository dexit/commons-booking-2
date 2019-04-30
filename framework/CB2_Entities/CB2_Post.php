<?php
/**
 * CB2 Post class
 *
 *
 *
 * @package   CommonsBooking2
 * @author    The CommonsBooking Team <commonsbooking@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */

class CB2_WordPress_Entity extends CB2_PostNavigator {
	protected function __construct( Int $ID, Array &$posts = NULL ) {
		// We never create CB2_WordPress_Entity
		// so $ID and $author_ID must always be valid
		parent::__construct( $ID, $posts );
	}
}

// --------------------------------------------------------------------
// --------------------------------------------------------------------
define( 'CB2_POST_PROPERTY_RELEVANT', TRUE );

class CB2_Post extends CB2_WordPress_Entity implements JsonSerializable {
  public static $PUBLISH        = 'publish';
  public static $AUTODRAFT      = 'auto-draft';
  public static $TRASH          = 'trash';
  public static $schema         = 'with-periodinsts'; //this-only, with-periodinsts
  public static $posts_table    = 'posts';    // not a pseudo class
  public static $postmeta_table = 'postmeta'; // not a pseudo class
  public static $database_table = FALSE;
  public static $no_metadata    = TRUE;       // Disable the metadata check
	public static $description    = 'CB2_Post details the wp_posts table for column description. It is not managed.<br/>CB2_Query::copy_all_wp_post_properties() uses this table config via CB2_Query::to_object(no date conversion).<br/>So DATETIMEs like post_date will remain strings in this case.';
	public static $supports = array(
		'title',
		'editor',
		'thumbnail',
		'custom-fields',
	);
	static $POST_PROPERTIES = array(
		'post_author'    => CB2_POST_PROPERTY_RELEVANT, // i.e. show in debug
		'post_date'      => CB2_POST_PROPERTY_RELEVANT,
		'post_content'   => CB2_POST_PROPERTY_RELEVANT,
		'post_title'     => CB2_POST_PROPERTY_RELEVANT,
		'post_excerpt'   => CB2_POST_PROPERTY_RELEVANT,
		'post_name'      => CB2_POST_PROPERTY_RELEVANT,
		'post_modified'  => CB2_POST_PROPERTY_RELEVANT,

		// Do not show these in debug, but copy them on to objects
		'post_date_gmt'  => NULL,
		'post_status'    => NULL,
		'comment_status' => NULL,
		'ping_status'    => NULL,
		'post_password'  => NULL,
		'to_ping'        => NULL,
		'pinged'         => NULL,
		'post_modified_gmt'     => NULL,
		'post_content_filtered' => NULL,
		'post_parent'    => NULL,
		'guid'           => NULL,
		'menu_order'     => NULL,
		'post_mime_type' => NULL,
		'comment_count'  => NULL,
		'filter'         => NULL,

		// Never overwrite these on objects, never show them in debug
		// CB2_PostNavigator::__construct() sets these
		'ID'             => FALSE,
		'post_type'      => FALSE,
		'filter'         => FALSE, // CB2_PostNavigator sets this to suppress
	);

  public function __toStringFor( $column_data_type, $column_name ) {
		return (string) $this->__toIntFor( $column_data_type, $column_name );
	}

	public function __toIntFor( $column_data_type, $column_name ) {
		// CB2_Post only has 1 id for any data
		// although it should be an _ID column
		return $this->id();
	}

  static function database_table_schemas( $prefix ) {
		// NOT MANAGED by CB2 $database_table = FALSE and 'managed' => FALSE
		// This wp_posts table will not be created by CB2
		// it is here only for field definition understanding
		// the views in this case produce these wp_posts fields
		// and the system needs to unerstand their type
		// See:
		//   CB2_Query::to_object() which uses this conversion knowledge
		// when setting values on objects from string properties
		// WordPress wp_posts 4.x == 5.x
		$id_field = 'ID';
		$date_comment = 'Will remain a string when set with CB2_Query::to_object()';

		return array(
			array(
				'name'    => 'posts',
				'managed' => FALSE, // Not created or removed. Here for data conversion only. Columns used by *_post views.
				'columns' => array(
					// TYPE, (SIZE), CB2_UNSIGNED, NOT NULL, CB2_AUTO_INCREMENT, DEFAULT, COMMENT
					$id_field       => array( CB2_BIGINT, 20, CB2_UNSIGNED, CB2_NOT_NULL, CB2_AUTO_INCREMENT ),
					'post_author'   => array( CB2_BIGINT, 20, CB2_UNSIGNED, CB2_NOT_NULL, NULL, '0' ),
					'post_date'     => array( CB2_DATETIME, NULL, NULL,     CB2_NOT_NULL, NULL, '0000-00-00 00:00:00', $date_comment ),
					'post_date_gmt' => array( CB2_DATETIME, NULL, NULL,     CB2_NOT_NULL, NULL, '0000-00-00 00:00:00', $date_comment ),
					'post_content'  => array( CB2_LONGTEXT, NULL, NULL,     CB2_NOT_NULL ),
					'post_title'    => array( CB2_TEXT,     NULL, NULL,     CB2_NOT_NULL ),
					'post_excerpt'  => array( CB2_TEXT,     NULL, NULL,     CB2_NOT_NULL ),
					'post_status'   => array( CB2_VARCHAR, 20,  NULL,       CB2_NOT_NULL, NULL, 'publish' ),
					'comment_status' => array( CB2_VARCHAR, 20, NULL,       CB2_NOT_NULL, NULL, 'open' ),
					'ping_status'   => array( CB2_VARCHAR, 20,  NULL,       CB2_NOT_NULL, NULL, 'open' ),
					'post_password' => array( CB2_VARCHAR, 255, NULL,       CB2_NOT_NULL, NULL, '' ),
					'post_name'     => array( CB2_VARCHAR, 200, NULL,       CB2_NOT_NULL, NULL, '' ),
					'to_ping'       => array( CB2_TEXT,   NULL, NULL,       CB2_NOT_NULL ),
					'pinged'        => array( CB2_TEXT,   NULL, NULL,       CB2_NOT_NULL ),
					'post_modified' => array( CB2_DATETIME, NULL, NULL,     CB2_NOT_NULL, NULL, '0000-00-00 00:00:00', $date_comment ),
					'post_modified_gmt' => array( CB2_DATETIME, NULL, NULL, CB2_NOT_NULL, NULL, '0000-00-00 00:00:00', $date_comment ),
					'post_content_filtered' => array( CB2_LONGTEXT, NULL, NULL, CB2_NOT_NULL ),
					'post_parent'   => array( CB2_BIGINT,   20, CB2_UNSIGNED, CB2_NOT_NULL, NULL, '0' ),
					'guid'          => array( CB2_VARCHAR,  255,      NULL, CB2_NOT_NULL, NULL, '' ),
					'menu_order'    => array( CB2_INT,      11,       NULL, CB2_NOT_NULL, NULL, '0' ),
					'post_type'     => array( CB2_VARCHAR,  20,       NULL, CB2_NOT_NULL, NULL, 'post' ),
					'post_mime_type' => array( CB2_VARCHAR, 100,      NULL, CB2_NOT_NULL, NULL, '' ),
					'comment_count' => array( CB2_BIGINT,   20,       NULL, CB2_NOT_NULL, NULL, '0' ),
				),
				'primary key'  => array( $id_field ),
			),

			array(
				'name' => 'postmeta',
				'managed' => FALSE, // Not created or removed. Here for data conversion only. Columns used by *_post views.
				'columns' => array(
					'meta_id'    => array( CB2_BIGINT,  (20),  CB2_UNSIGNED, CB2_NOT_NULL, CB2_AUTO_INCREMENT ),
					'post_id'    => array( CB2_BIGINT,  (20),  CB2_UNSIGNED, CB2_NOT_NULL, NULL, '0' ),
					'meta_key'   => array( CB2_VARCHAR, (255), NULL,         NULL,         NULL, '' ),
					'meta_value' => array( CB2_LONGTEXT, NULL, NULL ),
				),
			),

			array(
				'name'    => 'extended_post_properties',
				'description' => 'Not a real database table. Just here to define some more conversions.',
				'managed' => FALSE,
				'pseudo'  => TRUE,
				'columns' => array(
					'filter' => array( CB2_VARCHAR, 20, NULL, CB2_NOT_NULL, NULL, 'raw' ),
				),
			),
		);
  }

  function id( $why = '' ) {return $this->ID;}

  protected function __construct( Int $ID, Array &$posts = NULL ) {
		CB2_Query::assign_all_parameters( $this, func_get_args(), __class__ );

		$this->periodinsts = array();

    parent::__construct( $ID, $this->periodinsts );
  }

  function add_periodinst( CB2_PeriodInst &$periodinst ) {
    if ( ! in_array( $periodinst, $this->periodinsts ) ) array_push( $this->periodinsts, $periodinst );
  }

  function get_field_this( $class = '', $date_format = NULL ) {
		$permalink = get_the_permalink( $this );
		return "<a href='$permalink' class='$class' title='view $this->post_title'>$this->post_title</a>";
	}

	function summary() {
		return ucfirst( $this->post_type() ) . "($this->ID)";
	}

  function jsonSerialize() {
    $array = array(
      'ID' => $this->ID,
      'post_title' => $this->post_title,
    );
    if ( self::$schema == 'with-periodinsts' )
			$array[ 'periodinsts' ] = &$this->periodinsts;

    return $array;
  }
}
