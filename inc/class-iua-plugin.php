<?php

/**
 * Basic class that contains common functions,
 * such as:
 * - installation / deinstallation
 * - meta & options management,
 * - adding pages to menu
 * etc
 */
class Iua_Plugin extends Iua_Core {
	
	const CHECK_RESULT_OK = 'ok';
  
    
  public function __construct( $plugin_root ) {

		$this->plugin_root = $plugin_root;

		add_action( 'plugins_loaded', array( $this, 'initialize'), 10 );
	  
    if ( is_admin() ) {
      add_action('admin_enqueue_scripts', array($this, 'register_admin_styles_and_scripts') );
    }
    
		add_action( 'admin_menu', array( $this, 'add_page_to_menu' ) );
    
    add_action( 'widgets_init', array( $this, 'register_widgets' ) );
	}

	public function initialize() {
		self::load_options();
	}

	/* Add options on plugin activate */
	public static function install() {
		self::install_plugin_options();
	}
  
	public static function install_plugin_options() {
		add_option( 'iua_options', self::$default_option_values );
	}
  
  
	public function register_widgets() {		
    register_widget( 'Iua_Product_Page_Widget' );
	}
  
  public function register_admin_styles_and_scripts() {
    $file_src = plugins_url( 'css/iua-admin.css', $this->plugin_root );
    wp_enqueue_style( 'iua-admin', $file_src, array(), IUA_VERSION );
    
    wp_enqueue_script( 'iua-admin-js', plugins_url('/js/iua-admin.js', $this->plugin_root), array( 'jquery' ), IUA_VERSION, true );
    wp_localize_script( 'iua-admin-js', 'scs_settings', array(
      'ajax_url'			=> admin_url( 'admin-ajax.php' ),
    ) );
  }
  
	public function add_page_to_menu() {
    
		add_management_page(
			__( 'Image Uploading Dashboard' ),          // page title.
			__( 'Image Uploading Dashboard' ),          // menu title.
			'manage_options',
			'iua-settings',			                // menu slug.
			array( $this, 'render_settings_page' )   // callback.
		);
  }
  
  
  public function do_action() {
    
    $result = '';
    
    if ( isset( $_POST['iua-button'] ) ) {
      
      $start_date       = filter_input( INPUT_POST, self::FIELD_DATE_START );
      $end_date         = filter_input( INPUT_POST, self::FIELD_DATE_END );
      
      switch ( $_POST['iua-button'] ) {
        case self::ACTION_SAVE_OPTIONS:
         
          $stored_options = get_option( 'iua_options', array() );
          $stored_options['max_free_images_for_public'] = filter_input( INPUT_POST,'max_free_images_for_public');
          $stored_options['max_free_images_for_clients'] = filter_input( INPUT_POST,'max_free_images_for_clients');
          
          update_option( 'iua_options', $stored_options );
        break;
      }
    }
    
    return $result;
  }
  
	public function render_settings_page() {
    
    $action_results = '';
    
    if ( isset( $_POST['iua-button'] ) ) {
			$action_results = $this->do_action();
		}
    
    echo $action_results;
    
    self::load_options();
   
    $this->render_settings_form();
    
  }
  
  public function render_settings_form() {
    
    $global_settings_field_set = array(
      array(
				'name'        => "max_free_images_for_public",
				'type'        => 'text',
				'label'       => 'Max number of free images for public users',
				'default'     => '',
        'value'       => self::$option_values['max_free_images_for_public'],
        'description' => 'Public uploads are reset every day'
			),
      array(
				'name'        => "max_free_images_for_clients",
				'type'        => 'text',
				'label'       => 'Max number of free images for clients',
				'default'     => '',
        'value'       => self::$option_values['max_free_images_for_clients'],
			)
		);
    
    ?> 

    <form method="POST" >
    
      <h1><?php esc_html_e('Image Uploads Dashboard', 'iua'); ?></h1>
      
      
      <table class="iua-global-table">
        <tbody>
          <?php self::display_field_set( $global_settings_field_set ); ?>
        </tbody>
      </table>
      
      <h2><?php esc_html_e('Image Upload Statistics', 'iua'); ?></h2>
      
      <table class="iua-table">
        <thead>
          <th>Date</th>
          <th>Number</th>
        </thead>
        <tbody>
            <tr>
              <td>2024-01-01</td>
              <td>100 images uploaded</td>
            </tr>
        </tbody>
      </table>
      
      <p class="submit">  
       <input type="submit" id="iua-button-save" name="iua-button" class="button button-primary" value="<?php echo self::ACTION_SAVE_OPTIONS; ?>" />
      </p>
      
    </form>
    <?php 
  }
}