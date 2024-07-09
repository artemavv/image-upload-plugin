<?php


class Iua_Core {

  
  public const OPTION_NAME_FULL = 'iua_options';
  
	public static $prefix = 'iua_';
	
  // names of HTML fields in the form
  public const FIELD_DATE_START       = 'report_date_start';
  public const FIELD_DATE_END         = 'report_date_end';
  
  // name of the submit button that triggers POST form
  public const BUTTON_SUMBIT = 'iua-button';
  
  // Actions triggered by buttons in backend area
  public const ACTION_SAVE_OPTIONS = 'Save settings';
  
  
  public static $option_names = [
    'max_free_images_for_public'      => 'integer',
    'max_free_images_for_clients'     => 'integer',
    'widget_product_groups'           => 'array'
  ];
  
	public static $default_option_values = [
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
    
		foreach ( self::$option_names as $option_name => $default_option_value ) {
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
				$out .= '<div class="notice-error settings-error notice is-dismissible"><p>'
				. '<strong>'
				. $message
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
  
  
  // code taken from https://www.php.net/manual/en/function.fputcsv.php
  public static function make_csv_line( array $fields) : string {
    
    $f = fopen('php://memory', 'r+');
    if (fputcsv($f, $fields) === false) {
        return false;
    }
    rewind($f);
    $csv_line = stream_get_contents($f);
    return rtrim($csv_line) . "\r\n";
  }
  
  
}
