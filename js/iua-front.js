jQuery(document).ready(function(){


	jQuery(document).on('click', '.iua-submit', function( e ){

		const file_data = jQuery('#iua-client-image').prop('files')[0];
		//const product_image_url   = jQuery('#iua-product-image').attr('src');
		const client_prompt = jQuery('#iua-client-prompt').val();
		const product_id = jQuery('#iua-product-id').val();

		var $spinner = jQuery('#iua-spinner');
		var $product_image = jQuery('#iua-product-image');

		if ( file_data ) {
			requestImageUpload(product_id, client_prompt, file_data, $spinner, $product_image);
		}
		else {
			alert('Please select image to upload');
		}
	});


	/**
	 * Sends a request to upload user image. Shows/Hides spinner
	 * 
	 * @param int product_id
	 * @param string client_prompt
	 * @param array file_data
	 * @param HTMLElement spinner
	 * @param HTMLElement image
	 */
	var requestImageUpload = function( product_id, client_prompt, file_data, $spinner, $image ){

		var form_data = new FormData();

		form_data.append('file', file_data);
		form_data.append('action', 'iua_upload_image');
		form_data.append('product_id', product_id);
		form_data.append('client_prompt', client_prompt);

		$spinner.show();
		$image.css('filter', 'grayscale(100%)');

		jQuery.ajax({
			type: "POST",
			url: iua_settings.ajax_url,
			contentType: false,
			processData: false,
			data: form_data,
			success: function( data, textStatus, jqXHR ){
				if ( data.success ) {
					$spinner.hide();
					$image.css('filter', 'none');
					$image.attr('src', data.image_src);
				} else {
					console.log(data);
					$image.css('filter', 'none');
					$image.attr('src', iua_settings.error_image_src);
					$spinner.hide();
				}
			},
			error: function( jqXHR, textStatus, errorThrown ){

				//alert('Error: could not upload your image". Please try again.  ');
				console.log([jqXHR, textStatus, errorThrown]);
				$spinner.hide();
			},
			dataType: 'json'
		});
	}
});