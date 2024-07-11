<?php


class Iua_File_Handler extends Iua_Core { 
  
  public const UPLOAD_DIR_NAME = 'iua-images';

  
  public static function verify_uploads_directory() {
    global $wp_filesystem;

    require_once ( ABSPATH . '/wp-admin/includes/file.php' );
    
    WP_Filesystem();

    $plugin_upload_folder = WP_CONTENT_DIR . '/uploads/' . self::UPLOAD_DIR_NAME;

    return $wp_filesystem->exists( $plugin_upload_folder );
  }
  
  public static function create_uploads_directory() {
    
    $result = false;
      
    global $wp_filesystem;

    /* It is a Wordpress core file, that's why we manually include it */
    require_once ( ABSPATH . '/wp-admin/includes/file.php' );
    
    WP_Filesystem();

    $plugin_upload_folder = WP_CONTENT_DIR . '/uploads/' . self::UPLOAD_DIR_NAME;

    if ( ! $wp_filesystem->exists( $plugin_upload_folder ) ) {
       
       $folder_created = $wp_filesystem->mkdir( $plugin_upload_folder );

       if ( $folder_created ) {
         $result = true;     
       }
    }
    
    return $result;
  }

  public static function upload_client_image( $image_file_path, $client_id ) {
    
  }
}
