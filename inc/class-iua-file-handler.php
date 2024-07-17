<?php


class Iua_File_Handler extends Iua_Core { 
  
  /**
   * Check if our custom directory for file uploads exist.
   * @return bool
   */
  public static function verify_uploads_directory() {
    return is_dir( self::get_plugin_upload_folder() );
  }
  
  /**
   * When this plugin is activated, we need to create custom directory 
   * to store images uploaded by users.
   *
   * @return boolean
   */
  public static function create_uploads_directory() {
    
    $result = false;
      
    $plugin_upload_folder = self::get_plugin_upload_folder();

    if ( ! is_dir( $plugin_upload_folder) ) {

       $folder_created = wp_mkdir_p( $plugin_upload_folder );

       if ( $folder_created ) {
         $result = true;     
       }
    }
    
    return $result;
  }

  /**
   * Saves file uploaded through widget form into custom uploads folder.
   * Returns file name if upload is successful.
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
   * @return string $file_name
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
        $result = move_uploaded_file( $uploaded_file['tmp_name'], "$daily_upload_folder/$new_file_name" ) ? $new_file_name : false;
      }
    }
    
    return $result;
  }
  
}
