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
}
// --------------------------------------------------------------------
// --------------------------------------------------------------------
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
		'ID' => FALSE,
		'post_author' => TRUE,     // TRUE == Relevant to native records
		'post_date' => TRUE,
		'post_date_gmt' => FALSE,
		'post_content' => TRUE,
		'post_title' => TRUE,
		'post_excerpt' => TRUE,
		'post_status' => FALSE,
		'comment_status' => FALSE,
		'ping_status' => FALSE,
		'post_password' => FALSE,
		'post_name' => TRUE,
		'to_ping' => FALSE,
		'pinged' => FALSE,
		'post_modified' => TRUE,
		'post_modified_gmt' => FALSE,
		'post_content_filtered' => FALSE,
		'post_parent' => FALSE,
		'guid' => FALSE,
		'menu_order' => FALSE,
		'post_type' => TRUE,
		'post_mime_type' => FALSE,
		'comment_count' => FALSE,
		'filter' => FALSE,
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

  protected function __construct( $ID ) {
		CB2_Query::assign_all_parameters( $this, func_get_args(), __class__ );

		$this->periodinsts = array();

    parent::__construct( $this->periodinsts );
  }

  function add_periodinst( &$periodinst ) {
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
