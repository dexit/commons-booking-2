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
  public static $all = array();
  public static $PUBLISH        = 'publish';
  public static $AUTODRAFT      = 'auto-draft';
  public static $schema         = 'with-perioditems'; //this-only, with-perioditems
  public static $posts_table    = FALSE;
  public static $postmeta_table = FALSE;
  public static $database_table = FALSE;
  public static $supports = array(
		'title',
		'editor',
		'thumbnail',
	);
	static $POST_PROPERTIES = array(
		'ID' => FALSE,
		'post_author' => TRUE,     // TRUE == Relevant to native records
		'post_date' => TRUE,
		'post_date_gmt' => FALSE,
		'post_content' => TRUE,
		'post_title' => TRUE,
		'post_excerpt' => FALSE,
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

	function id( $why = '' ) {return $this->ID;}

  protected function __construct( $ID ) {
    $this->perioditems = array();

    if ( ! is_numeric( $ID ) ) throw new Exception( "[$ID] is not numeric for [" . get_class( $this ) . ']' );

    // WP_Post values
    $this->ID = (int) $ID;

    parent::__construct( $this->perioditems );

    self::$all[$ID] = $this;
  }

  function add_perioditem( &$perioditem ) {
    array_push( $this->perioditems, $perioditem );
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
    if ( self::$schema == 'with-perioditems' )
			$array[ 'perioditems' ] = &$this->perioditems;

    return $array;
  }
}
