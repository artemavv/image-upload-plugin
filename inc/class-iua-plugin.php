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

		Iua_Core::$plugin_root = $plugin_root;

		add_action( 'plugins_loaded', array( $this, 'initialize'), 10 );
	  
    if ( is_admin() ) {
      add_action( 'admin_enqueue_scripts', array($this, 'register_admin_styles_and_scripts') );
    }
    
		add_action( 'admin_menu', array( 'Iua_Settings', 'add_page_to_menu' ) );
    
    add_action( 'admin_notices', array( $this, 'display_admin_messages' ) );
    add_action( 'widgets_init', array( $this, 'register_widgets' ) );
    add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts' ) );
    

    add_action( 'wp_ajax_iua_upload_image', array( $this, 'handle_widget_submission' ) );
    add_action( 'wp_ajax_nopriv_iua_upload_image', array( $this, 'handle_widget_submission' ) );
    add_action( 'init', array( 'Iua_Core', 'set_user_cookie_identifier' ) );
    
	}

	public function initialize() {
		self::load_options();
    
    if ( ! Iua_File_Handler::verify_uploads_directory() ) {
      self::$error_messages[] = new \WP_Error( 'FilesysError', __( 'Image upload folder is missing! Please try reinstalling "Image Upload API" plugin.', 'iua' ) );
    }
	}

	/**
   *  on plugin activation:
   *  - Add options
   *  - check Wordpress and PHP versions
   *  - create custom directory to save uploaded images
   */
	public static function install() {
		self::install_plugin_options();
   
    if ( ! Iua_File_Handler::create_uploads_directory() ) {
      self::$error_messages[] = new \WP_Error( 'FilesysError', __( 'Failed to create image upload folder.', 'iua' ) );
    }
	}
  
  /**
   *  on plugin deactivation
   */
	public static function uninstall() {
		
	}
  
  public function display_admin_messages() {
    echo self::display_messages( Iua_Core::$error_messages, Iua_Core::$messages );
  }
  
	public static function install_plugin_options() {
		add_option( 'iua_options', self::$default_option_values );
	}
  
	public function register_widgets() {		
    register_widget( 'Iua_Product_Page_Widget' );
	}
  
  public function register_admin_styles_and_scripts() {
    $file_src = plugins_url( 'css/iua-admin.css', self::$plugin_root );
    wp_enqueue_style( 'iua-admin', $file_src, array(), IUA_VERSION );
    
    wp_enqueue_script( 'iua-admin-js', plugins_url('/js/iua-admin.js', self::$plugin_root), array( 'jquery' ), IUA_VERSION, true );
    wp_localize_script( 'iua-admin-js', 'scs_settings', array(
      'ajax_url'			=> admin_url( 'admin-ajax.php' ),
    ) );
  }
  
  public function add_scripts( ) {
    
    $script_name = 'iua-front.js';
    
		$script_id = str_replace( '.', '-', $script_name );
		wp_enqueue_script( $script_id, plugins_url("/js/$script_name", self::$plugin_root), array( 'jquery' ), IUA_VERSION, true );
    wp_localize_script( $script_id, 'iua_settings', array( 'ajax_url'			=> admin_url( 'admin-ajax.php' ) ) );
		
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
 
  
  /*
  public static function disable_plugin() {
    
    if ( current_user_can('activate_plugins') && is_plugin_active( plugin_basename( Iua_Core::$plugin_root ) ) ) {
      deactivate_plugins( plugin_basename( Iua_Core::$plugin_root ) );

      // Hide the default "Plugin activated" notice
      if ( isset( $_GET['activate'] ) ) {
        unset( $_GET['activate'] );
      }
    }
    
  }
  */
  
  public function do_action() {
    
    $result = '';
    
    if ( isset( $_POST['iua-button'] ) ) {
      
      $start_date       = filter_input( INPUT_POST, self::FIELD_DATE_START );
      $end_date         = filter_input( INPUT_POST, self::FIELD_DATE_END );
      
      switch ( $_POST['iua-button'] ) {
        case self::ACTION_SAVE_OPTIONS:
         
          $stored_options = get_option( 'iua_options', array() );
          
          foreach ( self::$option_names as $option_name => $option_type ) {
            $stored_options[ $option_name ] = filter_input( INPUT_POST, $option_name );
          }
          
          update_option( 'iua_options', $stored_options );
        break;
      }
    }
    
    return $result;
  }
  
  /**
   * Handler for the "iua_upload_image" AJAX action.
   * 
   * uploads the image supplied by user, and sends image URL + client prompt + product image to the API.
   * 
   * AJAX response is the URL of generated image.
   * 
   * If the generation is successful, records API use in the user's statistics.
   */
  public function handle_widget_submission() {
    
    // Default AJAX response to return to the user's browser
    $ajax_result = [
      'success'     => false,
      'image_src'   => false
    ];
    
    // 0. Gather info about the current submission
    $client_session_id    = self::get_user_cookie_identifier();
    $client_prompt        = filter_input( INPUT_POST, 'client_prompt' );
    $product_id           = intval( filter_input( INPUT_POST, 'product_id' ) );
    $product_image_url    = self::get_product_image_url( $product_id ); // filter_input( INPUT_POST, 'product_image' ); // 
    
    // 1. Get directory to upload client's file to
    $daily_upload_url = self::get_plugin_upload_url() . '/' . date('Y-m-d');
    
    // 2. Upload the image to the selected directory (beacuse image file should be accessible from outside by the API)
    $file_name = Iua_File_Handler::upload_client_image( $_FILES['file'], $client_session_id );
    
    $client_file_url = $file_name ? ( $daily_upload_url . '/' . $file_name ) : false;
    
    
    if ( $client_file_url && $product_id && $product_image_url ) {
      
      $result = self::request_api( $product_image_url, $client_file_url, $client_prompt, $client_session_id );
    
      $json = json_decode( $result, true ); // returns object as an associative array
      
      //echo('TEST');

      //echo $json;
      if ( is_array( $json) ) {
        
        self::record_api_usage_for_product( $product_id, $client_session_id );
        self::record_api_usage_for_user( $client_session_id );
        $ajax_result['success'] = true;
        $ajax_result['image_src'] = $json['link'];
      }
      elseif ( $result == 'Accepted' ) {
        $ajax_result['image_src'] = 'image404.jpg'; // TODO display something in case of API failure
      }
    }
    else {
      $ajax_result['error_message'] = 'Missing data: ';
      if  ( ! $client_file_url )        {  $ajax_result['error_message'] .= ' client_file_url '; }
      if  ( ! $product_id )             {  $ajax_result['error_message'] .= ' product_id '; }
      if  ( ! $product_image_url )      {  $ajax_result['error_message'] .= ' product_image_url '; }
      
    }
    
    

    echo json_encode( $ajax_result );
    wp_die();
  }
}