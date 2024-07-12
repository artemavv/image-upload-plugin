jQuery(document).ready(function() {
  
  
  jQuery(document).on('click', '.iua-submit', function (e) {

alert('MOO');

    var file_data = jQuery('#iua-client-image').prop('files')[0];
    
    console.log(file_data);
    requestImageUpload( file_data);
  });



  var requestImageUpload = function( file_data ) {

    var form_data = new FormData();

    form_data.append('file', file_data);
    form_data.append('action', 'ajax_iua_upload_image');

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
          alert(data.message);
          var spinner = false;
        }
      },
      error: function( jqXHR, textStatus, errorThrown ) {

        alert('Error: could not upload your image". Please try again.  ');
        console.log([jqXHR, textStatus, errorThrown]);
      },
      dataType: 'json'
    });
  }
});