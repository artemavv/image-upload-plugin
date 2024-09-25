<?php

class Iua_Core {

	public static $plugin_root;

	// options key used to save plugin settings
	public const OPTION_NAME_SETTINGS = 'iua_options';
	
	// options key used to save total generation statistics per product
	public const OPTION_NAME_STATS_PER_PRODUCT = 'iua_statistics_per_product';
	
	// options key used to save total generation statistics per public use (unregistered users)
	public const OPTION_NAME_STATS_PUBLIC = 'iua_statistics_public';
	
	// postmeta key used to save generation statistics for each separate product
	public const PRODUCT_META_STATS = 'iua_generation_statistics';
	
	// postmeta key used to save generation settings for each separate product
	public const PRODUCT_SETTINGS = 'iua_settings';
	
	// usermeta key used to save generation statistics for each individual user account
	public const USER_META_STATS = 'iua_generation_by_user';

	public static $prefix = 'iua_';

	// names of HTML fields in the form
	public const FIELD_DATE_START = 'report_date_start';
	public const FIELD_DATE_END = 'report_date_end';
	
	// name of the submit button that triggers POST form
	public const BUTTON_SUMBIT = 'iua-button';
	
	// used in the admin area in plugin metabox.
	const NONCE = 'iua_metabox_nonce';
	
	// Actions triggered by buttons in backend area
	public const ACTION_SAVE_OPTIONS = 'Save settings';
	public const ACTION_SAVE_KEY = 'Save API key';
	public const ACTION_DELETE_KEY = 'Delete API key';
	public const ACTION_GENERATE_TEST_STATS = 'Make test stats';
	
	// Custom upload directory name inside WP_UPLOAD_DIR
	public const UPLOAD_DIR_NAME = 'iua-images';
	
	// Options to use with time period dropdown
	public const DAY = 'day';
	public const WEEK = 'week';
	public const MONTH = 'month';

	public static $available_time_periods = [
		self::DAY => 'Daily',
		self::WEEK => 'Weekly',
		self::MONTH => 'Monthly'
	];
	
	public static $error_messages = [];
	public static $messages = [];
	
	public static $option_names = [
		'api_url' => 'string',
		'api_key' => 'string',
		'api_status' => 'string',
		'max_free_images_for_public' => 'integer',
		'max_free_images_for_clients' => 'integer',
		'accounting_time_period' => 'string',
		'widget_product_groups' => 'array'
	];
	
	public static $default_option_values = [
		'api_url' => '',
		'api_key' => '',
		'api_status' => '',
		'max_free_images_for_public' => 50,
		'max_free_images_for_clients' => 150,
		'accounting_time_period' => self::DAY,
		'widget_product_groups' => []
	];

	/**
	 * List of settings used for each individual user profile.
	 * 
	 * Format: [ setting name => default setting value ]
	 * 
	 * @var array
	 */
	public static $user_profile_settings = [
		'free_pictures_used' => 0,
		'last_used_prompt' => '',
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
			} else {
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
				} else {
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

			if ( (!$value) && ( $field['type'] != 'checkbox' ) ) {
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
	public static function display_field_in_row( $field, $value ) {

		$label = $field['label']; // $label = __($field['label'], DDB_TEXT_DOMAIN);

		$value = htmlspecialchars( $value );
		$field['id'] = str_replace( '_', '-', $field['name'] );

		// 1. Make HTML for input
		switch ( $field['type'] ) {
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

		if ( !isset( $field['style'] ) ) {
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

		if ( isset( $field['display'] ) ) {
			$display = $field['display'] ? 'table-row' : 'none';
		} else {
			$display = 'table-row';
		}

		// 2. Make HTML for table row
		switch ( $field['type'] ) {
			/* case 'checkbox':
			  $table_row_html = <<<EOT
			  <tr style="display:{$display}" >
			  <td colspan="3" class="col-checkbox">{$input_html}<label for="iua_{$field['id']}">$label</label></td>
			  </tr>
			  EOT;
			  break; */
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
			case 'checkbox':
			default:
				if ( isset( $field['description'] ) && $field['description'] ) {
					$table_row_html = <<<EOT
		<tr style="display:{$display}" >
			<td class="col-name" style="{$field['style']}"><label for="iua_{$field['id']}">$label</label></td>
			<td class="col-input">{$input_html}</td>
			<td class="col-info">
				{$field['description']}
			</td>
		</tr>
EOT;
				} else {
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
	public static function make_hidden_field( $field, $value ) {
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
	public static function make_text_field( $field, $value ) {

		$size = $field['size'] ?? 25;

		$out = <<<EOT
			<input type="text" id="iua_{$field['id']}" name="{$field['name']}" size="{$size}"value="{$value}" class="iua-text-field">
EOT;
		return $out;
	}

	/**
	 * Generates HTML code for number field input
	 * @param array $field
	 * @param array $value
	 */
	public static function make_number_field( $field, $value ) {
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
	public static function make_date_field( $field, $value ) {

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
	public static function make_textarea_field( $field, $value ) {
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
	public static function make_dropdown_field( $field, $value ) {

		$autocomplete = $field['autocomplete'] ?? false;

		$class = $autocomplete ? 'iua-autocomplete' : '';

		$out = "<select class='$class' name='{$field['name']}' id='iua_{$field['id']}' >";

		foreach ( $field['options'] as $optionValue => $optionName ) {
			$selected = ((string) $value == (string) $optionValue) ? 'selected="selected"' : '';
			$out .= '<option ' . $selected . ' value="' . $optionValue . '">' . $optionName . '</option>';
		}

		$out .= '</select>';
		return $out;
	}

	/**
	 * Generates HTML code for checkbox 
	 * @param array $field
	 */
	public static function make_checkbox_field( $field, $value ) {
		$chkboxValue = $value ? 'checked="checked"' : '';
		$out = <<<EOT
			<input type="checkbox" id="iua_{$field['id']}" name="{$field['name']}" {$chkboxValue} value="1" class="iua-checkbox-field"/>
EOT;
		return $out;
	}

	public static function log( $data ) {

		$filename = pathinfo( __FILE__, PATHINFO_DIRNAME ) . DIRECTORY_SEPARATOR . 'log.txt';
		if ( isset( $_REQUEST['iua_log_to_screen'] ) && $_REQUEST['iua_log_to_screen'] == 1 ) {
			echo( 'log::<pre>' . print_r( $data, 1 ) . '</pre>' );
		} else {
			file_put_contents( $filename, date( "Y-m-d H:i:s" ) . " | " . print_r( $data, 1 ) . "\r\n\r\n", FILE_APPEND );
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
	 */
	public static function get_product_image_url( $product, $product_settings ) {

		$image_url = false;

		if ( is_array( $product_settings ) && isset( $product_settings['product_image_url'] ) && $product_settings['product_image_url'] ) {
			$image_url = $product_settings['product_image_url'];
		} else {
			$image_id = $product->get_image_id();

			if ( $image_id ) {
				$info = wp_get_attachment_image_src( $image_id, 'full' );
				$image_url = $info[0];
			}
		}

		return $image_url;
	}

	/**
	 * Gets product prompt used for image generation
	 * 
	 */
	public static function get_product_prompt( $product, $product_settings ) {

		$product_prompt = $product_settings['product_prompt_for_generation'] ?? '';

		if ( $product_prompt ) {
			$product_prompt = esc_html( trim( strip_tags( $product_prompt ) ) );
		} else {
			$product_prompt = esc_html( trim( strip_tags( $product->get_short_description() ) ) );
		}

		return $product_prompt;
	}

	public static function set_user_cookie_identifier() {
		if ( !isset( $_COOKIE['iua_cookie'] ) ) {

			if ( is_user_logged_in() ) {
				$user_id = get_current_user_id();
				$user_hash = md5( $user_id );
				add_user_meta( $user_id, 'iua_hash', $user_hash, true );
			} else {
				$user_hash = md5( 'iua_user' . rand( 40000, 90000 ) . time() );
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
		// TODO: is this stats even used anywhere? 

		$stats = get_option( self::OPTION_NAME_STATS_PER_PRODUCT, array() );
		$current_api_usage_for_product = $stats[$product_id] ?? 0;
		$stats[$product_id] = $current_api_usage_for_product + 1;
		update_option( self::OPTION_NAME_STATS_PER_PRODUCT, $stats );

		$date = date( 'Y-m-d', strtotime( "-1 day" ) );

		// Save API usage for the specified product and date
		$product_stats = get_post_meta( $product_id, self::PRODUCT_META_STATS, true );

		if ( !$product_stats && !is_array( $product_stats ) ) {
			$product_stats = [];
		}

		if ( !isset( $product_stats[$date] ) ) {
			$product_stats[$date] = array(); // an image was generated for this product for the first time today
		}

		$product_stats[$date][] = $client_session_id . '_' . time(); // record the time of the generation event for the current user

		update_post_meta( $product_id, self::PRODUCT_META_STATS, $product_stats );
	}

	/**
	 * Update the saved number of API uses performed by specified user
	 *
	 * @param string $client_session_id
	 */
	public static function record_api_usage_for_user( string $client_session_id ) {


		$user_id = is_user_logged_in() ? get_current_user_id() : 0;

		if ( $user_id ) {

			self::record_api_usage_for_registered_user( $user_id );
		} elseif ( $client_session_id ) {

			/**
			 * API usage data for anonymous users is saved as array with keys: 
			 * [
			 *  'shared' - number of uses combined, for all users
			 *  $client_session_id => API usage by visitor with that session ID.
			 * ]
			 */
			$public_use_stats = get_option( self::OPTION_NAME_STATS_PUBLIC );

			if ( isset( $public_use_stats[$client_session_id] ) ) {
				$public_use_stats[$client_session_id] = self::add_latest_api_use_in_stats( $public_use_stats[$client_session_id] );
			} else { // API is used by this visitor for the first time, create fresh record
				$public_use_stats[$client_session_id] = array(
					'past_months' => 0,
					'latest_uses' => array(time())
				);
			}

			if ( isset( $public_use_stats['shared'] ) ) {
				$public_use_stats['shared'] = self::add_latest_api_use_in_stats( $public_use_stats['shared'] );
			} else { // API is used for the first time, create fresh record
				$public_use_stats['shared'] = array(
					'past_months' => 0,
					'latest_uses' => array(time())
				);
			}

			update_option( self::OPTION_NAME_STATS_PUBLIC, $public_use_stats );
		}
	}

	/**
	 * Records a new event of API usage in stats, for two cases:
	 * 
	 * a) assuming that time of use was now: $time = false
	 * b) at arbitrary time in the past: $time = timestamp
	 * 
	 * @param int $user_id
	 * @param int $time
	 */
	public static function record_api_usage_for_registered_user( string $user_id, $time = false ) {

		/**
		 * Data is saved as array with keys: 
		 * [
		 *  'past_months' => number of uses older than 30 days
		 *  'latest_uses' => timestamps of the last times API was requested by this user.
		 * ]
		 */
		$stats = get_user_meta( $user_id, self::USER_META_STATS, true );

		$stats_created = is_array( $stats ) && isset( $stats['latest_uses'] ) && is_array( $stats['latest_uses'] );

		if ( !$time ) { // the time of use was just now
			if ( !$stats_created ) { // need to create a fresh stats
				$stats = array(
					'past_months' => 0,
					'latest_uses' => array(time())
				);
			} else {
				$stats = self::add_latest_api_use_in_stats( $stats );
			}

			update_user_meta( $user_id, self::USER_META_STATS, $stats );
		} else { // the time of use was somewhere in the past
			$month = 30 * 24 * 3600; // seconds in a month 

			if ( time() - $time > $month ) { // need to record usage earlier than a month
				if ( !$stats_created ) { // need to create a fresh stats
					$updated_stats = array(
						'past_months' => 1,
						'latest_uses' => array()
					);
				} else { // need to update existing stats
					$updated_stats = array(
						'past_months' => $stats['past_months'] + 1, //  usage event was earlier than a month
						'latest_uses' => $stats['latest_uses']
					);
				}
			} else {	// need to record an usage event within the last month
				if ( !$stats_created ) {
					$updated_stats = array(
						'past_months' => 0,
						'latest_uses' => array($time)
					);
				} else {
					$updated_stats = $stats;
					array_push( $updated_stats['latest_uses'], $time );
				}
			}

			update_user_meta( $user_id, self::USER_META_STATS, $updated_stats );
		}
	}

	/**
	 * Records a new event of API usage in stats, assuming that time of use was now.
	 * @param array $stats
	 */
	public static function add_latest_api_use_in_stats( $stats ) {

		// first, count uses that were past 1 month ago
		$old_uses = $stats['past_months'];
		$recent_stats = array();

		$current_time = time();
		$month = 30 * 24 * 3600; // seconds in a month 

		if ( isset( $stats['latest_uses'] ) && is_array( $stats['latest_uses'] ) ) {

			foreach ( $stats['latest_uses'] as $past_use ) { // each record is a timestamp
				// check if this use was more than a month ago
				if ( $current_time - $past_use > $month ) {
					// archive that record, do not keep in the list
					$old_uses++;
				} else { // keep recent uses in the list
					$recent_stats[] = $past_use;
				}
			}

			$recent_stats[] = time(); // add the latest use
		}

		$updated_stats = array(
			'past_months' => $old_uses,
			'latest_uses' => $recent_stats
		);

		return $updated_stats;
	}

	public static function is_valid_stats_row( $row ) {
		if ( is_array( $row ) ) {
			if ( isset( $row['latest_uses'] ) && is_array( $row['latest_uses'] ) && isset( $row['past_months'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks if the provided timestamp was taken at the current day/week/month
	 * @param int $timestamp
	 * @param string $period "day" or "week" or "month"
	 */
	public static function is_current_time_period( $timestamp, $period ) {
		$result = true;

		$provided = new DateTime(); // given date
		$provided->setTimestamp( $timestamp );

		$dt = new \DateTime(); // date to compare with

		if ( $period == self::DAY ) {
			$dt->setTime( 0, 0, 0 );
			$result = $provided > $dt; // compare provided timestamp with the start of the day. If timestamp is bigger then it is from current day.
		} elseif ( $period == self::WEEK ) {
			$start_of_week = get_option( 'start_of_week' ); // 0 is Sunday, 1 is Monday

			$days = [
				'sunday',
				'monday',
				'tuesday',
				'wednesday',
				'thursday',
				'friday',
				'saturday',
			];
			$day_name = $days[$start_of_week];
			$dt->modify( " $day_name this week" );
			$result = $provided > $dt; // compare provided timestamp with the start of the week.
		} elseif ( $period == self::MONTH ) {
			$dt->modify( "first day of this month" );
			$result = $provided > $dt; // compare provided timestamp with the start of the month.
		}


		return $result;
	}

	/**
	 * Retrieves stats for the API use by current visitor
	 * 
	 * @return array
	 */
	public static function get_usage_stats_for_current_user() {

		$user_id = is_user_logged_in() ? get_current_user_id() : 0;

		if ( $user_id ) {

			// @see record_api_usage_for_user() for comments
			$stats = get_user_meta( $user_id, self::USER_META_STATS, true );
		} else {
			$stats = self::get_unregistered_users_statistics();
		}

		return $stats;
	}
	
	/**
	 * Retrieves stats for the shared use by unregistered visitors
	 * 
	 * @return array
	 */
	public static function get_unregistered_users_statistics() {
		$public_use_stats = get_option( self::OPTION_NAME_STATS_PUBLIC );
		
		if ( is_array($public_use_stats) || isset($public_use_stats['shared']) ) {
			$stats = $public_use_stats['shared'];
		}
		else { // data is missing, return empry stats structure
			$stats = array(
				'past_months' => 0,
				'latest_uses' => array()
			);
		}
		
		return $stats;
	}

	/**
	 * Find out how many uses are available for the current visitor, for two cases:
	 * 
	 * a) this is a logged-in visitor (registered user)
	 * b) this is an anonymous visitor (unregistered user)
	 * 
	 * @return integer
	 */
	public static function calculate_remaining_uses( $stats = false ) {

		if ( false === $stats ) {
			$stats = Iua_Core::get_usage_stats_for_current_user(); // for anonymous users, returns shared stats
		}

		$user_id = is_user_logged_in() ? get_current_user_id() : 0;

		if ( $user_id ) {
			$quota = intval( self::$option_values['max_free_images_for_clients'] );
		} else {
			$quota = intval( self::$option_values['max_free_images_for_public'] );
		}

		echo('7777');
		
		echo('<pre>' . print_r( $stats , 1 ) . '</pre>' );
		$remaining = self::calculate_quota_balance( $stats, $quota );

		return $remaining;
	}

	/**
	 * Find out how many uses were in the specified period
	 * 
	 * 
	 * @return integer
	 */
	public static function calculate_uses_in_period( array $stats, $period ) {

		$count = 0;
		$start_of_period = self::get_start_of_period( $period );

		foreach ( $stats['latest_uses'] as $use ) {
			if ( $use > $start_of_period ) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Returns timestamp for the start of the current day/week/month
	 * 
	 * 
	 * @return integer
	 */
	public static function get_start_of_period( $period ) {
		switch ( $period ) {
			case self::DAY:
				return time() - (24 * 3600);
			case self::WEEK:
				return time() - (7 * 24 * 3600);
			case self::MONTH:
			default:
				return time() - (30 * 24 * 3600);
		}
	}

	/**
	 * Counts past uses during the accountinng time period (current day, current week, current months)
	 * and returns remaining uses (according to the specified quota)
	 * 
	 * @param array $stats
	 * @param int $quota
	 * @return int
	 */
	public static function calculate_quota_balance( array $stats, int $quota ) {

		$balance = $quota;

		$period = self::$option_values['accounting_time_period'];

		if ( isset( $stats['latest_uses'] ) && is_array( $stats['latest_uses'] ) ) {

			foreach ( $stats['latest_uses'] as $past_use ) { // each record is a timestamp
				if ( self::is_current_time_period( $past_use, $period ) ) { // check if this timestamp is inside accounting time period (day, week, month)
					$balance--;
				}
			}
		}

		return $balance >= 0 ? $balance : 0;
	}

	/**
	 * Send request to the image generation API 
	 * 
	 * @param string $product_image_url
	 * @param string $client_image_url
	 * @param string $client_prompt
	 * @param string $client_session_id
	 * @param string $api_key Optional custom API key to use
	 * @return type
	 */
	public static function request_api( string $product_image_url, string $client_image_url, string $client_prompt, string $client_session_id, string $api_key = '' ) {

		self::load_options();

		$data = [
			'API_KEY' => $api_key ? $api_key : self::$option_values['api_key'],
			'sessionId' => $client_session_id,
			'image' => $product_image_url,
			'imageClient' => $client_image_url,
			'txt' => $client_prompt
		];

		$request_url = self::$option_values['api_url'];

		$json = json_encode( $data, JSON_UNESCAPED_SLASHES );

		self::wc_log( 'request_api(): sending data to API...', $data );

		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_URL, $request_url );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json') );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $json );

		$response = curl_exec( $ch );

		if ( curl_errno( $ch ) ) {
			self::wc_log( 'request_api(): curl Error --- ' . curl_error( $ch ), array('errorno' => curl_errno( $ch )) );
		}

		self::wc_log( 'request_api(): received response from API', ['resp' => $response] );

		curl_close( $ch );

		//$response =  "{\"link\": \"https:\/\/serwer2478439.home.pl\/segmentacja\/406282ef-b9bf-418d-9cd1-a924ee954472\/file.jpeg\",\n\"sessionId\": \"sessionID_c4ca4238a0b923820dcc509a6f75849b\"\n}";
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
