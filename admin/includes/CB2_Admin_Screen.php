<?php
/**
 * Admin interface
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
     * @var array
     */
    public $slug;
    /**
     * Admin screen tabs
     *
     * @var array
     */
    public $tabs;
    /**
     * Scripts for this screen
     *
     * @var array
     */
    public $scripts;
    /**
     * Styles for this screen
     *
     * @var array
     */
    public $styles;
    /**
     * File to include on this screen
     *
     * @var string
     */
    public $file = '';
    /**
     * menu
     *
     * @var array
     */
    public $menu_args = array();
    /**
     * Show on
     *
     * @var string
     */
    private $metabox_options_defaults = array (
    'show_on' => array(
        'key' => 'options-page',
        'value' => array('commons-booking-2'),
      ),
		'show_names' => true,
		'title' => '',
		'description' => ''
    );
    /**
     * Initialize the Admin screen
		 *
		 * @var array $menu_args
		 * @var array $scripts
		 * @var array $styles
     */
		public function __construct( ) {
			add_action('admin_menu', array($this, 'register_menu'));
			add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
			add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
			add_action('cmb2_save_options-page_fields', array($this, 'cmb2_submitted'), 10, 4);
		}
	/**
	 * Add javascript
	 */
	public function add_menu_item( $menu_args=array() ) {

			$menu_args_defaults = array(
				'page_title' => __('CB2', 'commons-booking-2'),
				'menu_title' => 'CB2 menu',
				'capability' => 'manage_options',
				'menu_slug' => 'slug',
				'function' => array( $this, 'the_content' ),
				'icon_url' => '',
				'position' => 6,
				'parent_slug' => '',
		);

		$this->menu_args = array_replace($menu_args_defaults, $menu_args);
		$this->slug = $this->menu_args['menu_slug'];


	}
	/**
	 * Add javascript
	 */
	public function add_script( $script=array() ) {

		$this->scripts[] = $script;

	}
	/**
	 * Add css style
	 */
	public function add_style( $style ) {

		$this->styles[] = $style;

	}

	public function init() {


	}

    /**
     * Register the menu entry
     */
    public function register_menu( ){

			if ( $this->menu_args['parent_slug'] == '' ) { // main level item
				add_menu_page(
					$this->menu_args['page_title'],
					$this->menu_args['menu_title'],
					$this->menu_args['capability'],
					$this->menu_args['menu_slug'],
					$this->menu_args['function'],
					$this->menu_args['icon_url'],
					$this->menu_args['position']
				);
			} else { // has parent, add submenu page
				add_submenu_page(
					$this->menu_args['parent_slug'],
					$this->menu_args['page_title'],
					$this->menu_args['menu_title'],
					$this->menu_args['capability'],
					$this->menu_args['menu_slug'],
					$this->menu_args['function']
				);
			}
    }
    /**
     * Add content
     *
     * @since 2.0.0
     *
     * @param string $file to include
     * @param string optional $tab_id
     * @param string optional $tab_title
     * @param bool optional $show
		 *
     */
    public function add_tabbed_content( $file, $tab_id='', $tab_title='', $show=TRUE ) {

			if ( !empty ( $file ) && $show == TRUE ) {
				$this->tabs[$tab_id] = array(
						'id' => $tab_id,
						'title' =>  $tab_title,
						'show' =>  $show,
						'file' => $file
					);
				}
		}
    /**
     * Add contents
     *
     * @since 2.0.0
     *
     * @param array $args
     */
    public function add_content( $file ) {
			if ( !empty ($file) ) {
				$this->content[] = $file;
			}
		}
    /**
     * Add meta box
     *
     * @since 2.0.0
     *
     * @param array $args
     */
    public function add_my_content( $function, $tab=FALSE ) {

		}
    /**
     * Enqueue Scripts
		 *
     * @param array $scripts name of script
     */
    public function enqueue_scripts( ){

			if ( ! empty ($this->scripts )) {
				foreach ($this->scripts as $script ) {
					wp_enqueue_script (...$script);
				}
			}
		}
    /**
     * Enqueue Styles
		 *
     * @param array $scripts name of script
     */
    public function enqueue_styles( ){

			if (!empty($this->styles)) {
					foreach ($this->styles as $style) {
							wp_enqueue_style(...$style);
					}
			}

		}
    /**
     * Get content
     *
     * @return mixed $content
     */
    public function the_content() {

			print ('<div class="wrap">');
			print ( 'enable maps' . CB2_Settings::is_enabled('features', 'enable-maps') );
			printf ('<h1 class="wp-heading">%s</h1>', esc_html(get_admin_page_title()));

			// non-tabbed content
			// if (!empty($this->content)) {
			// 		foreach ($this->content as $file) {
			// 				cb2_debug_maybe_print_path($file);
			// 				include $file;
			// 		}
			// }
			// tabbed content
			if (!empty( $this->tabs )) {
				echo $this->render_admin_tabs();
				foreach ($this->tabs as $tab) {
					printf ('<div id="tabs-%s" class="wrap">', $tab['id']);
    			cb2_debug_maybe_print_path($tab['file']);
					include $tab['file'];
					print('</div>');
				}

			}
			print ('</div>');
			print('enable maps' . CB2_Settings::is_enabled('features', 'enable-maps'));

		}

		public function cmb2_submitted() {
			new WP_Admin_Notice('Settings saved', 'updated');
		}

    /**
     * Get settings admin tabs
     *
     * @since 2.0.0
     *
     * @return mixed $html
     */
    public function render_admin_tabs()
    {
			$html = '<div id="tabs" class="settings-tab">
						<ul>';
			foreach ( $this->tabs as $key => $value) {
					$slug = $key;
					$html .= '<li><a href="#tabs-' . $slug . '">' . $value['title'] . '</a></li>';
			}
			$html .= '</ul>';
			return apply_filters( 'cb2' . $this->slug . 'tabs', $html);
    }
	/**
	 * Render a settings group
	 *
	 * @since 2.0.0
	 *
	 * @param array $metabox_args
	 *
	 * @return mixed
	 */
    public function render_settings_group_metabox( $metabox_args ){

			$args = array_merge ($this->metabox_options_defaults, $metabox_args );
			$html = sprintf( '
				<div class="postbox">
					<div class="inside">
					<h3>%s</h3>
					%s
					%s
					</div>
				</div>',
				$args['title'],
				$args['description'],
				cmb2_metabox_form( $args, $args['id'], array ('echo' => FALSE ))
			);

			echo $html;
    }
}

