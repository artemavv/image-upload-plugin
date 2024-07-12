<?php


class Iua_File_Handler extends Iua_Core { 
  
  public const UPLOAD_DIR_NAME = 'iua-images';

  public static function get_plugin_upload_folder() {
    return WP_CONTENT_DIR . '/uploads/' . self::UPLOAD_DIR_NAME;
  }
  
  public static function verify_uploads_directory() {
    global $wp_filesystem;

    require_once ( ABSPATH . '/wp-admin/includes/file.php' );
    
    WP_Filesystem();

    return $wp_filesystem->exists( self::get_plugin_upload_folder() );
  }
  
  /**
   * When this plugin is activated, we need to create custom directory 
   * to store images uploaded by users.
   * 
   * @global object $wp_filesystem
   * @return boolean
   */
  public static function create_uploads_directory() {
    
    $result = false;
      
    global $wp_filesystem;

    /* It is a Wordpress core file, that's why we manually include it */
    require_once ( ABSPATH . '/wp-admin/includes/file.php' );
    
    WP_Filesystem();

    $plugin_upload_folder = self::get_plugin_upload_folder();

    if ( ! $wp_filesystem->exists( $plugin_upload_folder ) ) {
       
       $folder_created = $wp_filesystem->mkdir( $plugin_upload_folder );

       if ( $folder_created ) {
         $result = true;     
       }
    }
    
    return $result;
  }

  /**
   * Saves file uploaded through widget form into custom uploads folder.
   * 
   * $uploaded_file = $_FILES['file'];
   * 
   * $uploaded_file = array(
			'name' => ...
			'type' => ...
			'tmp_name' => ...
			'error' => ...
			'size' => ...
		);
   * 
   * @param array $uploaded_file
   * @param string $client_id
   */
  public static function upload_client_image( $uploaded_file, $client_id ) {
    
    $result = false;
      
    if ( $uploaded_file['error'] == UPLOAD_ERR_OK ) {
      
      $path_parts = pathinfo( $uploaded_file['name'] );
      
      $extension = $path_parts['extension'];
        
      global $wp_filesystem;
      require_once ( ABSPATH . '/wp-admin/includes/file.php' );    
      WP_Filesystem();

      $daily_upload_folder = self::get_plugin_upload_folder() . '/' . date('Y-m-d');

      $folder_created = $wp_filesystem->exists( $daily_upload_folder ) ? true : $wp_filesystem->mkdir( $daily_upload_folder );
      
      if ( $folder_created ) {
        $new_file_name = $client_id . '_' . time() . '.' . $extension;
        $result = move_uploaded_file( $uploaded_file['tmp_name'], "$daily_upload_folder/$new_file_name" );
      }
    }
    
    return $result;
  }
  
}
