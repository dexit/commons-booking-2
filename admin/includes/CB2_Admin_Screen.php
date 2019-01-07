<?php
/**
 * Admin interface
 *
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */

class CB2_Admin_Screen
{
	 /**
	 * Admin screen slug
	 *
	 * @var string
	 */
	public $title;
	/**
	 * Admin screen slug
	 *
	 * @var string
	 */
	public $slug;
	/**
	 * Admin screen tabs
	 *
	 * @var array
	 */
	public $tabs;
	/**
	 * Admin screen content
	 *
	 * @var object
	 */
	public $other_content;
	/**
	 * Show on
	 *
	 * @var string
	 */
	public $show_on;
	/**
	 * Show on
	 *
	 * @var string
	 */
  private $metabox_options_defaults = array (
			'show_on' => array(
        'key' => 'options-page',
        'value' => array('commons-booking'),
      ),
			'show_names' => true,
		);
	/**
	 * Initialize the Admin screen
	 */
	public function __construct()
	{

	}
	/**
	 * Add a new tab
	 *
	 * @param array $tab
	 */
	public function add_tab( $tab )
	{

	}
	/**
	 * Add a new metabox
	 *
	 * @param array $metabox
	 * @param string|bool $target tab
	 */
	public function add_metabox( $metabox, $tab = '' )
	{

	}
	/**
	 * Add a new metabox
	 *
	 * @param mixed $content
	 * @param string|bool $target tab
	 */
	public function add_content( $content, $tab = '' )
	{

	}
	/**
	 * Render the page
	 *
	 * @param return $html
	 */
	public function render_admin_screen( )
	{

	}
}

