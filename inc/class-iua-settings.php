<?php

/**
 * This class displays plugin settings and statistics 
 * 
 */
class Iua_Settings extends Iua_Core {

	const CHECK_RESULT_OK = 'ok';

	public static function add_page_to_menu() {

		add_management_page(
			__( 'Image Generation Dashboard' ), // page title.
			__( 'Image Generation Dashboard' ), // menu title.
			'manage_options',
			'iua-settings', // menu slug.
			array('Iua_Settings', 'render_settings_page')	// callback.
		);
	}

	public static function do_action() {

		$result = '';

		if ( isset( $_POST['iua-button-save'] ) ) {

			switch ( $_POST['iua-button-save'] ) {
				case self::ACTION_SAVE_OPTIONS:

					$stored_options = get_option( 'iua_options', array() );

					foreach ( self::$option_names as $option_name => $option_type ) {
						if ( isset( $_POST[$option_name] ) ) {
							$stored_options[$option_name] = filter_input( INPUT_POST, $option_name );
						}
					}

					update_option( 'iua_options', $stored_options );
					break;
				case self::ACTION_SAVE_KEY:

					$stored_options = get_option( 'iua_options', array() );

					$new_key = filter_input( INPUT_POST, 'api_key' );

					if ( $new_key ) {
						$stored_options['api_key'] = $new_key;
						$stored_options['api_status'] = self::check_api_key( $new_key );
					}

					update_option( 'iua_options', $stored_options );
					break;
				case self::ACTION_DELETE_KEY:

					$stored_options = get_option( 'iua_options', array() );

					$stored_options['api_key'] = '';
					$stored_options['api_status'] = 'disonnected';

					update_option( 'iua_options', $stored_options );
					break;

				case self::ACTION_GENERATE_TEST_STATS:

					$users = get_users( ['number' => 10, 'fields' => 'ID'] );

					$mean = intval( filter_input( INPUT_POST, 'mean_generations_per_day' ) );

					$product_ids = array_map( 'intval', explode( ',', $_POST['products_to_use_for_test'] ) );

					$product_names = [];

					foreach ( $product_ids as $product_id ) {
						$product_names[] = get_the_title( $product_id );

						self::generate_test_stats_for_product( $product_id, $mean, $users );
					}
					$message = "Create $mean generations (on average) per day for past 2 months, for products: " . implode( ',', $product_names );

					$result .= '<div class="notice-info notice is-dismissible"><p>'
									. '<strong>'
									. $message
									. '</strong></p>'
									. '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>'
									. '</div>';

					break;
			}
		}

		return $result;
	}

	/**
	 * Creates $mean generations (on average) per day for past 2 months, for specified product
	 * 
	 * @param int $product_id
	 * @param int $mean
	 * @param array $users
	 */
	public static function generate_test_stats_for_product( int $product_id, int $mean, array $users ) {

		$test_stats = [];
		$now = time();

		// For each day in past 2 months
		for ( $days = 0; $days < 60; $days++ ) {

			$user_id = $users[array_rand( $users, 1 )];

			$client_session_id = 'test_' . $user_id;

			$max = $mean + 2;
			$min = ($mean - 2) < 0 ? 0 : $mean - 2;

			$count = rand( $min, $max );

			// Generate usage records for that day
			for ( $i = 1; $i < $count; $i++ ) {
				$time = $now - $days * ( 24 * 3600 ) - rand( 400, 72000 );
				$date = date( 'Y-m-d', $time );

				if ( !isset( $test_stats[$date] ) ) {
					$test_stats[$date] = array();
				}

				// Save the generated record
				$test_stats[$date][] = $client_session_id . '_' . $time;

				Iua_Core::record_api_usage_for_registered_user( $user_id, $time );
			}
		}

		update_post_meta( $product_id, self::PRODUCT_META_STATS, $test_stats );
	}

	public static function check_api_key( $api_key ) {

		$test_product_image_url = "https://www.timberland.com.au/media/catalog/product/cache/51baa2c84f06c1cecdd4dbc499a02b45/a/5/a5wqq.p57_a5wqq_p57_01_1103657.jpg";
		$test_client_file_url = "https://upload.wikimedia.org/wikipedia/commons/a/a0/George_Lucas_cropped_2009.jpg";
		$test_prompt = "Timberland orange top";
		$client_session_id = 'test' . time();

		$result = self::request_api( $test_product_image_url, $test_client_file_url, $test_prompt, $client_session_id, $api_key );

		$json = json_decode( $result, true ); // returns object as an associative array
		
		//echo('$result<pre>' . print_r($result, 1) . '</pre>');
		//echo('$json<pre>' . print_r($json, 1) . '</pre>');

		if ( $result == 'Accepted' || ( is_array( $json ) && isset( $json['link'] ) ) ) {
			return 'success';
		}

		return 'fail';
	}

	public static function render_settings_page() {

		$action_results = '';

		if ( isset( $_POST['iua-button-save'] ) ) {
			$action_results = self::do_action();
		}

		echo $action_results;

		self::load_options();

		self::render_settings_form();
		
		if ( isset( $_GET['superuser']) ) {
			self::render_usage_generation_form();
		}
		
		self::render_chart();
		self::render_product_statistics();
		self::render_user_statistics();
	}

	public static function render_settings_form() {

		$global_settings_field_set = array(
			array(
				'name' => "max_free_images_for_public",
				'type' => 'number',
				'label' => 'Max number of free images for public users',
				'default' => '',
				'value' => self::$option_values['max_free_images_for_public'],
				'description' => 'Limit for public uploads is reset every ' . self::$option_values['accounting_time_period']
			),
			array(
				'name' => "max_free_images_for_clients",
				'type' => 'number',
				'label' => 'Max number of free images for clients',
				'default' => '',
				'value' => self::$option_values['max_free_images_for_clients'],
				'description' => 'Limit for each registered user is reset every ' . self::$option_values['accounting_time_period']
			),
			array(
				'name' => "accounting_time_period",
				'type' => 'dropdown',
				'options' => self::$available_time_periods,
				'label' => 'Time period for users\' API limits',
				'default' => '',
				'value' => self::$option_values['accounting_time_period'],
			)
		);
		
		if ( isset( $_GET['superuser']) ) {
			$global_settings_field_set[] = array(
				'name' => "api_url",
				'type' => 'text',
				'label' => 'Full URL to the image generation API',
				'default' => '',
				'value' => self::$option_values['api_url'],
			);
		}

		$api_settings_field_set = array(
			array(
				'name' => "api_key",
				'type' => 'text',
				'label' => 'Key to use for the image generation API',
				'default' => '',
				'value' => self::$option_values['api_key'],
			),
			array(
				'name' => "api_status",
				'type' => 'text',
				'label' => 'STATUS',
				'default' => '',
				'value' => self::$option_values['api_status'],
			)
		);
		?> 

		<form method="POST" >

				<h1><?php esc_html_e( 'Image Generation Dashboard', 'iua' ); ?></h1>

				<h2><?php esc_html_e( 'Settings', 'iua' ); ?></h2>

				<table class="iua-global-table">
						<tbody>
		<?php self::display_field_set( $global_settings_field_set ); ?>
						</tbody>
				</table>

				<p class="submit">  
						<button type="submit" id="iua-button-save" name="iua-button-save" class="button button-primary" value="<?php echo self::ACTION_SAVE_OPTIONS; ?>"><?php echo self::ACTION_SAVE_OPTIONS; ?></button>
				</p>

		</form>

		<form method="POST" >
				<h2><?php esc_html_e( 'API Connection', 'iua' ); ?></h2>

				<table class="iua-global-table">
						<tbody>
		<?php self::display_field_set( $api_settings_field_set ); ?>
						</tbody>
				</table>

				<p class="submit">  
						<button type="submit" id="iua-button-save-api-key" name="iua-button-save" class="button button-primary" value="<?php echo self::ACTION_SAVE_KEY; ?>" /><?php echo self::ACTION_SAVE_KEY; ?></button>
						<button type="submit" id="iua-button-delete-api-key" name="iua-button-save" class="button button-danger" style="background: #a52a41; color: white; margin-left: 140px; " value="<?php echo self::ACTION_DELETE_KEY; ?>"><?php echo self::ACTION_DELETE_KEY; ?></button>
				</p>

		</form>
		<?php
	}

	public static function render_chart() {
		?>
			<div>
				<canvas id="iua-chart"></canvas>
			</div> 
		<?php
	}
	
	public static function render_usage_generation_form() {

		$test_stats_field_set = array(
			array(
				'name' => "mean_generations_per_day",
				'type' => 'number',
				'label' => 'How many image generations to add per day',
				'default' => '',
				'value' => '0',
			),
			array(
				'name' => "products_to_use_for_test",
				'type' => 'text',
				'label' => 'Products ID to use for test',
				'default' => '',
				'value' => '',
			)
		);
		?> 

		<form method="POST" >

				<h2><?php esc_html_e( 'Generate test statistics', 'iua' ); ?></h2>

				<table class="iua-global-table">
						<tbody>
		<?php self::display_field_set( $test_stats_field_set ); ?>
						</tbody>
				</table>

				<p class="submit">  
						<button type="submit" id="iua-button-save" name="iua-button-save" class="button button-primary" value="<?php echo self::ACTION_GENERATE_TEST_STATS; ?>"><?php echo self::ACTION_GENERATE_TEST_STATS; ?></button>
				</p>

		</form>

		<?php
	}

	/**
	 * Gets raw product statistics 
	 * 
	 * @return array
	 */
	public static function get_products_used_for_generation() {
		global $wpdb;

		$wp = $wpdb->prefix;

		$statistics_meta_key = self::PRODUCT_META_STATS;

		$query_sql = "SELECT p.`ID`, p.`post_title` as 'product_name', pm.`meta_value` AS 'stats' from {$wp}posts AS p
      LEFT JOIN `{$wp}postmeta` AS pm on p.`ID` = pm.`post_id`
      WHERE pm.`meta_key` = '$statistics_meta_key'
      AND p.post_type = 'product' ";

		$products = array();

		$sql_results = $wpdb->get_results( $query_sql, ARRAY_A );

		foreach ( $sql_results as $row ) {
			$products[$row['ID']] = [
				'stats' => unserialize( $row['stats'] ),
				'name' => $row['product_name']
			];
		}

		return $products;
	}

	/**
	 * Retrieves API usage statistics by registered users
	 * 
	 * Stats of each user has keys: 'day', 'week', 'momth', 'last_use'
	 * 
	 * @return array [ user_id => user_stats ]
	 */
	public static function get_users_statistics() {

		global $wpdb;

		$wp = $wpdb->prefix;

		$statistics_meta_key = self::USER_META_STATS;

		$query_sql = "SELECT u.`ID`, u.`user_login`, um.`meta_value` AS 'stats' from {$wp}users AS u
      LEFT JOIN `{$wp}usermeta` AS um on u.`ID` = um.`user_id`
      WHERE um.`meta_key` = '$statistics_meta_key' ";

		$user_stats = array();

		$sql_results = $wpdb->get_results( $query_sql, ARRAY_A );

		foreach ( $sql_results as $row ) {
			$user_stats[$row['user_login']] = unserialize( $row['stats'] );
		}

		return $user_stats;
	}

	/**
	 * 
	 * @return array
	 */
	public static function get_generation_statistics( array $products ) {


		//$total_stats = get_option( self::OPTION_NAME_STATS, array() );

		$generation_stats = array();

		$cutoff_date = date( "Y-m-d", strtotime( "-30 days" ) );
		$day = date( "Y-m-d" );
		$i = 0;

		while ( $day > $cutoff_date ) {

			$day = date( "Y-m-d", strtotime( "-$i day" ) );
			$i++;

			$generation_stats[$day] = array(
				'total' => 0,
				'products' => array()
			);

			foreach ( $products as $product_id => $product_data ) { // $product_data is an array with 'product_name' and 'stats'
				if ( isset( $product_data['stats'][$day] ) ) {

					$amount = count( $product_data['stats'][$day] );

					$generation_stats[$day]['products'][$product_id] = $amount;
					$generation_stats[$day]['total'] += $amount;
				} else {
					$generation_stats[$day]['products'][$product_id] = 0;
				}
			}

			if ( $i > 100 )
				break; // safeguard against infinite loop
		}


		return $generation_stats;
	}

	public static function render_product_statistics() {

		$products = self::get_products_used_for_generation();
		$generation_stats = self::get_generation_statistics( $products );
		?>

		<h2><?php esc_html_e( 'Image Generation Statistics', 'iua' ); ?></h2>

		<table id="iua-product-statistics" class="iua-table">
				<thead>
				<th>Date</th>
				<th>Total generations</th>
		<?php foreach ( $products as $product_id => $product_data ): ?>
					<th><?php echo $product_data['name']; ?></th>
		<?php endforeach; ?>
		</thead>
		<tbody>
				<?php foreach ( $generation_stats as $date => $row ): ?>
					<tr>
							<td><?php echo $date; ?></td>
							<td><?php echo $row['total']; ?></td>
					<?php foreach ( $row['products'] as $product_id => $product_generations ): ?>
								<th><?php echo $product_generations; ?></th>
			<?php endforeach; ?>
					</tr>
						<?php endforeach; ?>
		</tbody>
		</table>

				<?php
			}

			public static function render_user_statistics() {

				// returns array ( userd_id => user_stats )
				$user_stats = self::get_users_statistics();

				$quota_clients = self::$option_values['max_free_images_for_clients'];
				$quota_public = self::$option_values['max_free_images_for_public'];
				?>

		<h2><?php esc_html_e( 'Statistics by user', 'iua' ); ?></h2>

		<p>Current time period for API quota: <strong><?php echo( self::$option_values['accounting_time_period'] ); ?></strong></p>

		<table class="iua-table">
				<thead>
				<th>User ID</th>
				<th>Today</th>
				<th>This week</th>
				<th>This month</th>
				<th>Last time used</th>
				<th>Remaining API quota</th>
		</thead>
		<tbody>
		<?php foreach ( $user_stats as $user_login => $row ): ?>
			<?php if ( self::is_valid_stats_row( $row ) ) : ?>
				<?php
				$remaining_uses = self::calculate_quota_balance( $row, $quota_clients );

				$today = self::calculate_uses_in_period( $row, self::DAY );
				$this_week = self::calculate_uses_in_period( $row, self::WEEK );
				$this_month = self::calculate_uses_in_period( $row, self::MONTH );
				?>
						<tr>
								<td><?php echo $user_login; ?></td>
								<td><?php echo $today; ?></td>
								<td><?php echo $this_week; ?></td>
								<td><?php echo $this_month; ?></td>
								<td><?php echo date( 'Y-m-d H:i:s', array_pop( $row['latest_uses'] ) ); ?></td>
								<td><?php echo $remaining_uses; ?></td>
						</tr>
			<?php else: ?>
						<tr>
								<td><?php echo $user_login; ?></td>
								<td>?</td>
								<td>?</td>
								<td>?</td>
								<td>?</td>
								<td>?</td>
						</tr>
			<?php endif; ?>
		<?php endforeach; ?>
		<?php
		/* Special case: shared stats for unregisteted visitors */

		$unr_row = self::get_unregistered_users_statistics();
		$unr_remaining_uses = self::calculate_quota_balance( $unr_row, $quota_public );

		$unr_today = self::calculate_uses_in_period( $unr_row, self::DAY );
		$unr_this_week = self::calculate_uses_in_period( $unr_row, self::WEEK );
		$unr_this_month = self::calculate_uses_in_period( $unr_row, self::MONTH );
		?>
				<tr>
						<td>Unregistered</td>
						<td><?php echo $unr_today; ?></td>
						<td><?php echo $unr_this_week; ?></td>
						<td><?php echo $unr_this_month; ?></td>
						<td><?php echo date( 'Y-m-d H:i:s', array_pop( $unr_row['latest_uses'] ) ); ?></td>
						<td><?php echo $unr_remaining_uses; ?></td>
				</tr>
		</tbody>
		</table>

		<?php
	}
}
