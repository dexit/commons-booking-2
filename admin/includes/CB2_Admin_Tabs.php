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

class CB2_Admin_Tabs
{
    /**
     * Admin screen tabs
     *
     * @var array
     */
    public $tabs;
    /**
     * Admin screen tabs
     *
     * @var array
     */
    public $page_slug;
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
		'title' => 'test',
		'description' => 'desc'
    );
    /**
     * Initialize the Admin screen
		 *
     */
		public function __construct( $page_slug ) {

			$this->page_slug = $page_slug;
			$this->enqueue_styles();
			$this->enqueue_scripts();

		}
	/**
	 * Add tab
	 *
	 * @since 2.0.0
	 *
	 * @param string $tab_id
	 * @param string $tab_title
	 * @param string $content
	 * @param bool optional $show conditionally render the tab
	 *
	 */
  	public function add_tab( $tab_id='', $tab_title='', $content, $show=TRUE ) {

			if ( $show == TRUE ) {
				$this->tabs[$tab_id] = array(
						'id' => $tab_id,
						'title' =>  $tab_title,
						'show' =>  $show,
						'content' => $content
					);
				}
			}
    /**
     * Enqueue Scripts
     */
    public function enqueue_scripts( ){

			wp_enqueue_script (
				'cb2_tabs_script',
				plugins_url('admin/assets/js/admin_tabs.js', CB2_PLUGIN_ABSOLUTE),
				array('jquery', 'jquery-ui-tabs')
				);
		}
    /**
     * Enqueue Styles
     */
    public function enqueue_styles( ){
			// admin tabs css is now in admin.css
		}
    /**
     * Get content
     *
     * @return mixed $content
     */
    public function render_content() {

			print ('<div class="wrap">');

			if (!empty( $this->tabs )) {
				echo $this->get_tabs();
				foreach ($this->tabs as $tab) {
					printf ('<div id="tabs-%s" class="wrap" aria-hidden="true">', $tab['id']);
					printf( $tab['content']);
					print('</div>');
				}
			}
			print ('</div>');

		}

    /**
     * Get settings admin tabs
     *
     * @since 2.0.0
     *
     * @return mixed $html
     */
    public function get_tabs()
    {
			$html = '<div id="tabs" class="settings-tab">
						<ul>';
			foreach ( $this->tabs as $key => $value) {
					$slug = $key;
					$html .= '<li><a href="#tabs-' . $slug . '">' . $value['title'] . '</a></li>';
			}
			$html .= '</ul>';
			return apply_filters( 'cb2' . $this->page_slug . 'tabs', $html);
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
	/**
	 * A settings group metabox
	 *
	 * @since 2.0.0
	 *
	 * @param array $metabox_args
	 *
	 * @return mixed
	 */
    public function add_metabox_settings_group ( $metabox_id, $tab='default' ){

			$metabox_args = CB2_Settings::get_settings_group( $metabox_id );
			$this->add_metabox( $metabox_args, $tab);
		}
	/**
	 * A settings group metabox
	 *
	 * @since 2.0.0
	 *
	 * @param array $metabox_args
	 *
	 * @return mixed
	 */
    public function add_metabox ( $metabox_args, $tab='default' ){

			$args = array_merge ($this->metabox_options_defaults, $metabox_args );
			$metabox_html = sprintf( '
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
			$this->html_els[$tab] .= $metabox_html;
		}
/**
 * render
 *
 * @since 2.0.0
 *
 * @param array $metabox_args
 *
 * @return mixed
 */
		public function render() {

			var_dump( $this->html_els );


			if (is_array ($this->html_els) ) {
				foreach ( $this->html_els as $el ) {
					echo "array";
					echo $el;
				}

			}

		}
}

