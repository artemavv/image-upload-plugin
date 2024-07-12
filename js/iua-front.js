jQuery(document).ready(function() {
  
  
  jQuery(document).on('click', '.iua-submit', function (e) {

    const file_data = jQuery('#iua-client-image').prop('files')[0];
    const product_image_url =jQuery('#iua-product-image').attr('src');
    
    console.log(file_data);
    console.log(product_image_url);
    requestImageUpload( product_image_url, file_data);
  });



  var requestImageUpload = function( product_image_url, file_data ) {

    var form_data = new FormData();

    form_data.append('file', file_data);
    form_data.append('action', 'iua_upload_image');
    form_data.append('product_image', product_image_url );
    form_data.append('client_prompt', 'test_prompt' );

    var spinner = true; // TODO Implement spinner 
    jQuery.ajax({
      type: "POST",
      url: iua_settings.ajax_url,
      contentType: false,
      processData: false,
      data: form_data,
      success: function(data, textStatus, jqXHR ) {
        if ( data.status === 'success') {
          alert('OK!');
          var spinner = false; // TODO Implement spinner 
        }
        else {
          console.log(data);
          var spinner = false;
        }
      },
      error: function( jqXHR, textStatus, errorThrown ) {

        //alert('Error: could not upload your image". Please try again.  ');
        console.log([jqXHR, textStatus, errorThrown]);
      },
      dataType: 'json'
    });
  }
});