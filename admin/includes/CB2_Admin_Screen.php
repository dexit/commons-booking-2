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
	 * Screens
	 *
	 * @var object
	 */
	public $screens;
	/**
	 * Tabs
	 *
	 * @var object
	 */
	public $tabs;
	/**
	 * Tabs
	 *
	 * @var object
	 */
	public $metaboxes;
	/**
	 * Slug
	 *
	 * @var string
	 */
	public $slug;
	/**
	 * Initialize the Admin screen
	 */
	public function __construct()
	{
			if (!apply_filters('cb2_admin_screen', true)) {
					return;
			}
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
	 * Add a new tab
	 *
	 * @param array $tab
	 */
	public function add_metabox( $metabox )
	{

	}
	/**
	 * Add a new tab
	 *
	 * @param return $html
	 */
	public function render_page( )
	{

	}
}

