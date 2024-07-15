jQuery(document).ready(function() {
  
  
  jQuery(document).on('click', '.iua-submit', function (e) {

    const file_data           = jQuery('#iua-client-image').prop('files')[0];
    const product_image_url   = jQuery('#iua-product-image').attr('src');
    const product_prompt      = jQuery('#iua-product-prompt').val();
    
    var $spinner              = jQuery('#iua-spinner');
    var $product_image        = jQuery('#iua-product-image');
    
    requestImageUpload( product_image_url, product_prompt, file_data, $spinner, $product_image );
  });


  /**
   * Sends a request to upload user image. Shows/Hides spinner
   * 
   * @param string product_image_url
   * @param string product_prompt
   * @param array file_data
   * @param HTMLElement spinner
   * @param HTMLElement image
   */
  var requestImageUpload = function( product_image_url, product_prompt, file_data, $spinner, $image ) {

    var form_data = new FormData();

    form_data.append('file', file_data);
    form_data.append('action', 'iua_upload_image');
    form_data.append('product_image', product_image_url );
    form_data.append('client_prompt', 'test_prompt' );

    $spinner.show();
    $image.css( 'filter', 'grayscale(100%)' );

    jQuery.ajax({
      type: "POST",
      url: iua_settings.ajax_url,
      contentType: false,
      processData: false,
      data: form_data,
      success: function(data, textStatus, jqXHR ) {
        if ( data.success ) {
          $spinner.hide();
          $image.css( 'filter', 'none' );
          $image.attr( 'src', data.image_src );
        }
        else {
          console.log(data);
          $spinner.hide();
        }
      },
      error: function( jqXHR, textStatus, errorThrown ) {

        //alert('Error: could not upload your image". Please try again.  ');
        console.log([jqXHR, textStatus, errorThrown]);
        $spinner.hide();
      },
      dataType: 'json'
    });
  }
});