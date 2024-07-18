<?php


class Iua_Core {

  public static $plugin_root;
  
  // options key used to save plugin settings
  public const OPTION_NAME_SETTINGS = 'iua_options';
  
  // options key used to save total generation statistics 
  public const OPTION_NAME_STATS = 'iua_statistics';
  
  // postmeta key used to save generation statistics for each separate products
  public const PRODUCT_META_STATS = 'iua_generation_statistics'; 
  
	public static $prefix = 'iua_';
	
  // names of HTML fields in the form
  public const FIELD_DATE_START       = 'report_date_start';
  public const FIELD_DATE_END         = 'report_date_end';
  
  // name of the submit button that triggers POST form
  public const BUTTON_SUMBIT = 'iua-button';
  
  // Actions triggered by buttons in backend area
  public const ACTION_SAVE_OPTIONS = 'Save settings';
  
  // Custom upload directory name inside WP_UPLOAD_DIR
  public const UPLOAD_DIR_NAME = 'iua-images';
  
  
  public static $error_messages = [];
  public static $messages = [];
  
  
  public static $option_names = [
    'api_url'                         => 'string',
    'api_key'                         => 'string',
    'max_free_images_for_public'      => 'integer',
    'max_free_images_for_clients'     => 'integer',
    'widget_product_groups'           => 'array'
  ];
  
	public static $default_option_values = [
    'api_url'                         => '',
    'api_key'                         => '',
    'max_free_images_for_public'      => 50,
    'max_free_images_for_clients'     => 150,
    'widget_product_groups'           => []
	];
    
  /**
   * List of settings used for each individual user profile.
   * 
   * Format: [ setting name => default setting value ]
   * 
   * @var array
   */
	public static $user_profile_settings = [
    'free_pictures_used'        => 0,
    'last_used_prompt'          => '',
	];
  
	public static $option_values = array();

	public static function init() {
		self::load_options();
	}

	public static function load_options() {
		$stored_options = get_option( 'iua_options', array() );
    
		foreach ( self::$default_option_values as $option_name => $default_option_value ) {
			if ( isset( $stored_options[$option_name] ) ) {
				self::$option_values[$option_name] = $stored_options[$option_name];
			}
			else {
				self::$option_values[$option_name] = $default_option_value;
			}
		}
	}

	protected function display_messages( $error_messages, $messages ) {
		$out = '';
		if ( count( $error_messages ) ) {
			foreach ( $error_messages as $message ) {
        
        if ( is_wp_error( $message ) ) {
          $message_text = $message->get_error_message();
        }
        else {
          $message_text = trim( $message );
        }
        
				$out .= '<div class="notice-error settings-error notice is-dismissible"><p>'
				. '<strong>'
				. $message_text
				. '</strong></p>'
				. '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>'
				. '</div>';
			}
		}
    
		if ( count( $messages ) ) {
			foreach ( $messages as $message ) {
				$out .= '<div class="notice-info notice is-dismissible"><p>'
				. '<strong>'
				. $message
				. '</strong></p>'
				. '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>'
				. '</div>';
			}
		}

		return $out;
	}
  

  
  /**
   * Returns HTML table rows each containing field, field name, and field description
   * 
   * @param array $field_set 
   * @return string HTML
   */
	public static function render_fields_row( $field_set ) {
    
    $out = '';
    
		foreach ( $field_set as $field ) {
			
			$value = $field['value'];
			
			if ( ( ! $value) && ( $field['type'] != 'checkbox' ) ) {
				$value = $field['default'] ?? '';
			}
			
			$out .= self::display_field_in_row( $field, $value );
		}
    
    return $out;
	}
	
	/**
	 * Generates HTML code for input row in table
	 * @param array $field
	 * @param array $value
   * @return string HTML
	 */
	public static function display_field_in_row($field, $value) {
    
		$label = $field['label']; // $label = __($field['label'], DDB_TEXT_DOMAIN);
		
		$value = htmlspecialchars($value);
		$field['id'] = str_replace( '_', '-', $field['name'] );
		
		// 1. Make HTML for input
		switch ($field['type']) {
			case 'text':
				$input_HTML = self::make_text_field( $field, $value );
				break;
			case 'dropdown':
				$input_HTML = self::make_dropdown_field( $field, $value );
				break;
			case 'textarea':
				$input_HTML = self::make_textarea_field( $field, $value );
				break;
			case 'checkbox':
				$input_HTML = self::make_checkbox_field( $field, $value );
				break;
			case 'hidden':
				$input_HTML = self::make_hidden_field( $field, $value );
				break;
			default:
				$input_HTML = '[Unknown field type "' . $field['type'] . '" ]';
		}
		
		
		// 2. Make HTML for table cell
		switch ( $field['type'] ) {
			case 'hidden':
				$table_cell_html = <<<EOT
		<td class="col-hidden" style="display:none;" >{$input_HTML}</td>
EOT;
				break;
			case 'text':
			case 'textarea':
			case 'checkbox':
			default:
				$table_cell_html = <<<EOT
		<td>{$input_HTML}</td>
EOT;
				
		}

		return $table_cell_html;
	}
  
  
  
	/**
	 * Generates HTML code with TR rows containing specified field set
   * 
	 * @param array $field
	 * @param mixed $value
   * @return string HTML
	 */
	public static function display_field_set( $field_set ) {
		foreach ( $field_set as $field ) {

			$value = $field['value'] ?? false;
			
      $field['id'] = str_replace( '_', '-', $field['name'] );

			echo self::make_field( $field, $value );
		}
	}
	
  
	/**
	 * Generates HTML code with TR row containing specified field input
   * 
	 * @param array $field
	 * @param mixed $value
   * @return string HTML
	 */
	public static function make_field( $field, $value ) {
		$label = $field['label'];
		
		if ( ! isset( $field['style'] ) ) {
			$field['style'] = '';
		}
		
		// 1. Make HTML for input
		switch ( $field['type'] ) {
			case 'checkbox':
				$input_html = self::make_checkbox_field( $field, $value );
				break;
			case 'text':
				$input_html = self::make_text_field( $field, $value );
				break;
      case 'number':
				$input_html = self::make_number_field( $field, $value );
				break;
			case 'date':
				$input_html = self::make_date_field( $field, $value );
				break;
			case 'dropdown':
				$input_html = self::make_dropdown_field( $field, $value );
				break;
			case 'textarea':
				$input_html = self::make_textarea_field( $field, $value );
				break;
			case 'hidden':
				$input_html = self::make_hidden_field( $field, $value );
				break;
			default:
				$input_html = '[Unknown field type "' . $field['type'] . '" ]';
		}
		
		if (isset($field['display'])) {
			$display = $field['display'] ? 'table-row' : 'none';
		}
		else {
			$display = 'table-row';
		}
		
		// 2. Make HTML for table row
		switch ($field['type']) {
			case 'checkbox':
				$table_row_html = <<<EOT
		<tr style="display:{$display}" >
			<td colspan="3" class="col-checkbox">{$input_html}<label for="iua_{$field['id']}">$label</label></td>
		</tr>
EOT;
				break;
			case 'hidden':
				$table_row_html = <<<EOT
		<tr style="display:none" >
			<td colspan="3" class="col-hidden">{$input_html}</td>
		</tr>
EOT;
				break;
			case 'dropdown':
			case 'text':
      case 'number':
			case 'textarea':
			default:
				if (isset($field['description']) && $field['description']) {
					$table_row_html = <<<EOT
		<tr style="display:{$display}" >
			<td class="col-name" style="{$field['style']}"><label for="iua_{$field['id']}">$label</label></td>
			<td class="col-input">{$input_html}</td>
			<td class="col-info">
				{$field['description']}
			</td>
		</tr>
EOT;
				}
				else {
				$table_row_html = <<<EOT
		<tr style="display:{$display}" >
			<td class="col-name" style="{$field['style']}"><label for="iua_{$field['id']}">$label</label></td>
			<td class="col-input">{$input_html}</td>
			<td class="col-info"></td>
		</tr>
EOT;
				}
		}

		
		return $table_row_html;
	}
	

	/**
	 * Generates HTML code for hidden input
	 * @param array $field
	 * @param array $value
	 */
	public static function make_hidden_field($field, $value) {
		$out = <<<EOT
			<input type="hidden" id="iua_{$field['id']}" name="{$field['name']}" value="{$value}">
EOT;
		return $out;
	}	
	
	/**
	 * Generates HTML code for text field input
	 * @param array $field
	 * @param array $value
	 */
	public static function make_text_field($field, $value) {
		$out = <<<EOT
			<input type="text" id="iua_{$field['id']}" name="{$field['name']}" value="{$value}" class="iua-text-field">
EOT;
		return $out;
	}
  
  /**
	 * Generates HTML code for number field input
	 * @param array $field
	 * @param array $value
	 */
	public static function make_number_field($field, $value) {
		$out = <<<EOT
			<input type="number" id="iua_{$field['id']}" name="{$field['name']}" value="{$value}" class="iua-number-field">
EOT;
		return $out;
	}
  
	/**
	 * Generates HTML code for date field input
	 * @param array $field
	 * @param array $value
	 */
	public static function make_date_field($field, $value) {
    
    $min = $field['min'] ?? '2023-01-01';
    
		$out = <<<EOT
			<input type="date" id="iua_{$field['id']}" name="{$field['name']}" value="{$value}" min="{$min}" class="iua-date-field">
EOT;
		return $out;
	}
	
	/**
	 * Generates HTML code for textarea input
	 * @param array $field
	 * @param array $value
	 */
	public static function make_textarea_field($field, $value) {
		$out = <<<EOT
			<textarea id="iua_{$field['id']}" name="{$field['name']}" cols="{$field['cols']}" rows="{$field['rows']}" value="">{$value}</textarea>
EOT;
		return $out;
	}
	
	/**
	 * Generates HTML code for dropdown list input
	 * @param array $field
	 * @param array $value
	 */
	public static function make_dropdown_field($field, $value) {
    
    $autocomplete = $field['autocomplete'] ?? false;
    
    $class = $autocomplete ? 'iua-autocomplete' : '';
    
    $out = "<select class='$class' name='{$field['name']}' id='iua_{$field['id']}' >";

		foreach ($field['options'] as $optionValue => $optionName) {
			$selected = ((string)$value == (string)$optionValue) ? 'selected="selected"' : '';
			$out .= '<option '. $selected .' value="' . $optionValue . '">' . $optionName .'</option>';
		}
		
		$out .= '</select>';
		return $out;
	}
	
	
	/**
	 * Generates HTML code for checkbox 
	 * @param array $field
	 */
	public static function make_checkbox_field($field, $value) {
		$chkboxValue = $value ? 'checked="checked"' : '';
		$out = <<<EOT
			<input type="checkbox" id="iua_{$field['id']}" name="{$field['name']}" {$chkboxValue} value="1" class="iua-checkbox-field"/>
EOT;
		return $out;
	}	
	
	public static function log($data) {

		$filename = pathinfo( __FILE__, PATHINFO_DIRNAME ) . DIRECTORY_SEPARATOR .'log.txt';
		if ( isset($_REQUEST['iua_log_to_screen']) && $_REQUEST['iua_log_to_screen'] == 1 ) {
			echo( 'log::<pre>' . print_r($data, 1) . '</pre>' );
		}
		else {
			file_put_contents($filename, date("Y-m-d H:i:s") . " | " . print_r($data,1) . "\r\n\r\n", FILE_APPEND);
		}
	}
  
  public static function get_plugin_upload_folder() {
    return WP_CONTENT_DIR . '/uploads/' . self::UPLOAD_DIR_NAME;
  }
  
  public static function get_plugin_upload_url() {
    $upload_dir = wp_upload_dir();
    return $upload_dir['baseurl'] . '/' . self::UPLOAD_DIR_NAME;
  }
  
  /**
   * Gets URL for the product image selected by site admin for the generation
   * 
   * @param integer $product_id
   */
  public static function get_product_image_url( int $product_id ) {
  
    $image_url = false;
    
    if ( is_numeric( $product_id ) ) {
      if ( $product = wc_get_product( $product_id ) ) { // check for successful product search
        $image_id = $product->get_image_id();

        if ( $image_id ) {
          $info = wp_get_attachment_image_src( $image_id, 'full');
          $image_url = $info[0];
        }
      }
    }
    
    return $image_url;
  }

  
  /**
   * Gets product prompt used for image generation
   * 
   * @param integer $product_id
   */  
  public static function get_product_prompt( int $product_id ) {
    
    $product_prompt = '';
    if ( is_numeric( $product_id ) ) {
      if ( $product = wc_get_product( $product_id ) ) { // check for successful product search
        $product_prompt = 'Test apple';
      }
    }
    
    return $product_prompt;
  }
  
  public static function set_user_cookie_identifier() {
    if ( ! isset( $_COOKIE['iua_cookie'] ) ) {
      
      if ( is_user_logged_in() ) {
        $user_id = get_current_user_id();
        $user_hash = md5( $user_id );
        add_user_meta( $user_id, 'iua_hash', $user_hash , true );
      }
      else {
        $user_hash = md5( 'iua_user' . rand(40000, 90000) . time() );
      }
      
      setcookie( 'iua_cookie', 'sessionID_' . $user_hash, time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
    }
  }
  
  public static function get_user_cookie_identifier() {
    if ( isset( $_COOKIE['iua_cookie'] ) ) {
      return $_COOKIE['iua_cookie'];
    }
    
    return false;
  }
  
  /**
   * Update the saved number of API uses performed for the specified product 
   *
   * @param int $product_id 
   * @param string $client_session_id
   */
  public static function record_api_usage_for_product( int $product_id, string $client_session_id ) {
    
    // Save total API usage
    
    $stats = get_option( self::OPTION_NAME_STATS, array() );
    $current_api_usage_for_product = $stats[$product_id] ?? 0;
    $stats[$product_id] = $current_api_usage_for_product + 1;
    update_option( self::OPTION_NAME_STATS, $stats );
    
    $date = date('Y-m-d', strtotime("-1 day") );
    
    // Save API usage for the specified product and date
    $product_stats = get_post_meta( $product_id, self::PRODUCT_META_STATS, true );
    
    if ( ! $product_stats && ! is_array( $product_stats ) ) {
      $product_stats = [];
    }
    
    if ( ! isset( $product_stats[$date] ) ) {
      $product_stats[$date] = array(); // an image was generated for this product for the first time today
    }
    
    $product_stats[$date][] = $client_session_id . '_' . time(); // record the time of the generation event for the current user
    
    update_post_meta( $product_id, self::PRODUCT_META_STATS, $product_stats);
  }
  
  /**
   * Send request to the image generation API 
   * 
   * @param string $product_image_url
   * @param string $client_image_url
   * @param string $client_prompt
   * @param string $client_session_id
   * @return type
   */
  public static function request_api( string $product_image_url, string $client_image_url, string $client_prompt, string $client_session_id ) {
    
    self::load_options();
    /*
    $data = [
      'API_KEY'         => self::$option_values['api_key'],
      'sessionId'       => $client_session_id,
      'image'           => $product_image_url,
      'imageClient'     => $client_image_url,
      'txt'             => $client_prompt
    ];
    
    $request_url =  self::$option_values['api_url'];
    
    $json = json_encode( $data, JSON_UNESCAPED_SLASHES );
    
    self::wc_log('request_api(): sending data to API...', $data );
    
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $request_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json' ) );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json );

    
    $response = curl_exec($ch);

    self::wc_log('request_api(): received response from API', [ 'resp' => $response ] );
    
    curl_close($ch);
    */
    
    $response =  "{\"link\": \"https:\/\/serwer2478439.home.pl\/segmentacja\/406282ef-b9bf-418d-9cd1-a924ee954472\/file.jpeg\",\n\"sessionId\": \"sessionID_c4ca4238a0b923820dcc509a6f75849b\"\n}";
    return $response;
  }

  /**
   * Write into WooCommerce log. 
   * 
   * @param string $message
   * @param array $data
   */
  public static function wc_log( string $message, array $data ) {
    wc_get_logger()->info(
      $message,
      $data
    );
  }
  
}
