<?php

/**
 * Basic class that contains common functions,
 * such as:
 * - installation / deinstallation
 * - meta & options management,
 * - adding pages to menu
 * etc
 */
class Iua_Plugin extends Iua_Core {

	const CHECK_RESULT_OK = 'ok';

	public function __construct( $plugin_root ) {

		Iua_Core::$plugin_root = $plugin_root;

		add_action( 'plugins_loaded', array($this, 'initialize'), 10 );

		add_action( 'admin_menu', array('Iua_Settings', 'add_page_to_menu') );

		add_action( 'admin_notices', array($this, 'display_admin_messages') );
		add_action( 'widgets_init', array($this, 'register_widgets') );

		add_action( 'wp_enqueue_scripts', array($this, 'add_frontend_scripts') );

		add_action( 'admin_enqueue_scripts', array($this, 'add_admin_styles_scripts') );

		add_action( 'add_meta_boxes', array($this, 'add_wc_product_meta_box') );

		add_action( 'save_post', array($this, 'save_meta_box_data') );

		add_action( 'wp_ajax_iua_upload_image', array($this, 'handle_widget_submission') );
		add_action( 'wp_ajax_nopriv_iua_upload_image', array($this, 'handle_widget_submission') );
		add_action( 'init', array('Iua_Core', 'set_user_cookie_identifier') );
	}

	public function initialize() {
		self::load_options();

		if ( !Iua_File_Handler::verify_uploads_directory() ) {
			self::$error_messages[] = new \WP_Error( 'FilesysError', __( 'Image upload folder is missing! Please try reinstalling "Image Upload API" plugin.', 'iua' ) );
		}
	}

	/**
	 *  on plugin activation:
	 *  - Add options
	 *  - check Wordpress and PHP versions
	 *  - create custom directory to save uploaded images
	 */
	public static function install() {
		self::install_plugin_options();

		if ( !Iua_File_Handler::create_uploads_directory() ) {
			self::$error_messages[] = new \WP_Error( 'FilesysError', __( 'Failed to create image upload folder.', 'iua' ) );
		}
	}

	/**
	 *  on plugin deactivation
	 */
	public static function uninstall() {
		
	}

	public function display_admin_messages() {
		echo self::display_messages( Iua_Core::$error_messages, Iua_Core::$messages );
	}

	public static function install_plugin_options() {
		add_option( 'iua_options', self::$default_option_values );
	}

	public function register_widgets() {
		register_widget( 'Iua_Product_Page_Widget' );
	}

	public function add_frontend_scripts() {

		$script_name = 'iua-front.js';

		$script_id = str_replace( '.', '-', $script_name );
		wp_enqueue_script( $script_id, plugins_url( "/js/$script_name", self::$plugin_root ), array('jquery'), IUA_VERSION, true );
		
		$script_settings = array(
			'ajax_url'            => admin_url( 'admin-ajax.php' ),
			'error_image_src'     => plugins_url( 'error-something-went-wrong.webp', self::$plugin_root )
		);
		wp_localize_script( $script_id, 'iua_settings', $script_settings );
		
		if ( file_exists( IUA_PATH . 'css/iua-front.css' ) ) {
			wp_enqueue_style( 'iua-front', IUA_URL . 'css/iua-front.css', false, IUA_VERSION );
		}
	}

	public function add_admin_styles_scripts() {
		if ( file_exists( IUA_PATH . 'js/iua-admin.js' ) ) {
			
			wp_enqueue_script( 'iua-chart', IUA_URL . 'js/chart.js', array('jquery'), IUA_VERSION, true );
			wp_enqueue_script( 'iua-admin', IUA_URL . 'js/iua-admin.js', array('jquery', 'iua-chart'), IUA_VERSION, true );
			
			wp_localize_script( 'iua-admin', 'iua_settings', array('ajax_url' => admin_url( 'admin-ajax.php' )) );
		}


		if ( file_exists( IUA_PATH . 'css/iua-admin.css' ) ) {
			wp_enqueue_style( 'iua-main', IUA_URL . 'css/iua-admin.css', false, IUA_VERSION );
		}
	}

	public function add_page_to_menu() {

		add_management_page(
						__( 'Image Uploading Dashboard' ), // page title.
						__( 'Image Uploading Dashboard' ), // menu title.
						'manage_options',
						'iua-settings', // menu slug.
						array($this, 'render_settings_page')	// callback.
		);
	}

	public function add_wc_product_meta_box() {
		add_meta_box(
						'edit-product-prompt',
						__( 'Image Generation for this product', 'iua' ),
						array($this, 'render_wc_product_meta_box'),
						'product'
		);
	}

	/*
	  public static function disable_plugin() {

	  if ( current_user_can('activate_plugins') && is_plugin_active( plugin_basename( Iua_Core::$plugin_root ) ) ) {
	  deactivate_plugins( plugin_basename( Iua_Core::$plugin_root ) );

	  // Hide the default "Plugin activated" notice
	  if ( isset( $_GET['activate'] ) ) {
	  unset( $_GET['activate'] );
	  }
	  }

	  }
	 */

	public function do_action() {

		$result = '';

		if ( isset( $_POST['iua-button'] ) ) {

			$start_date = filter_input( INPUT_POST, self::FIELD_DATE_START );
			$end_date = filter_input( INPUT_POST, self::FIELD_DATE_END );

			switch ( $_POST['iua-button'] ) {
				case self::ACTION_SAVE_OPTIONS:

					$stored_options = get_option( 'iua_options', array() );

					foreach ( self::$option_names as $option_name => $option_type ) {
						$stored_options[$option_name] = filter_input( INPUT_POST, $option_name );
					}

					update_option( 'iua_options', $stored_options );
					break;
			}
		}

		return $result;
	}

	/**
	 * Handler for the "iua_upload_image" AJAX action.
	 * 
	 * uploads the image supplied by user, and sends image URL + client prompt + product image to the API.
	 * 
	 * AJAX response is the URL of generated image.
	 * 
	 * If the generation is successful, records API use in the user's statistics.
	 */
	public function handle_widget_submission() {

		// Default AJAX response to return to the user's browser
		$ajax_result = [
			'success' => false,
			'image_src' => false
		];

		// 0. Gather info about the current submission
		$client_session_id = self::get_user_cookie_identifier();
		$client_prompt = filter_input( INPUT_POST, 'client_prompt' );
		$product_id = intval( filter_input( INPUT_POST, 'product_id' ) );

		$product_settings = get_post_meta( $product_id, Iua_Core::PRODUCT_SETTINGS, true );

		$product = wc_get_product( $product_id );

		$product_image_url = self::get_product_image_url( $product, $product_settings ); // filter_input( INPUT_POST, 'product_image' ); // 
		$product_prompt = self::get_product_prompt( $product, $product_settings );

		$final_prompt = $product_prompt . '. ' . $client_prompt;

		// 1. Get directory to upload client's file to
		$daily_upload_url = self::get_plugin_upload_url() . '/' . date( 'Y-m-d' );

		// 2. Upload the image to the selected directory (beacuse image file should be accessible from outside by the API)
		$file_name = Iua_File_Handler::upload_client_image( $_FILES['file'], $client_session_id );

		$client_file_url = $file_name ? ( $daily_upload_url . '/' . $file_name ) : false;

		if ( $client_file_url && $product_id && $product_image_url ) {

			$result = self::request_api( $product_image_url, $client_file_url, $final_prompt, $client_session_id );

			$json = json_decode( $result, true ); // returns object as an associative array

			if ( is_array( $json ) ) {
				self::record_api_usage_for_product( $product_id, $client_session_id );
				self::record_api_usage_for_user( $client_session_id );
				$ajax_result['success'] = true;
				$ajax_result['image_src'] = $json['link'];
			} elseif ( $result == 'Accepted' ) {
				$ajax_result['image_src'] = 'image404.jpg'; // TODO display something in case of API failure
			}
		} else {
			$ajax_result['error_message'] = 'Missing data: ';
			
			if ( !$client_file_url ) {
				$ajax_result['error_message'] .= ' client_file_url ';
			}
			if ( !$product_id ) {
				$ajax_result['error_message'] .= ' product_id ';
			}
			if ( !$product_image_url ) {
				$ajax_result['error_message'] .= ' product_image_url ';
			}
		}

		echo json_encode( $ajax_result );
		wp_die();
	}

	public function render_wc_product_meta_box( $post ) {


		// Add a nonce field so we can check for it later.
		wp_nonce_field( self::NONCE, self::NONCE );

		$iua_product_settings = (array) get_post_meta( $post->ID, self::PRODUCT_SETTINGS, true );

		$checkbox_value = 1; // enable by default. 

		if ( is_array( $iua_product_settings ) ) {
			if ( isset( $iua_product_settings['image_generation_enabled'] ) ) {
				$checkbox_value = $iua_product_settings['image_generation_enabled'] === false ? 0 : 1;
			}
		}

		$fields = array(
			array(
				'id' => 'image_generation_enabled',
				'name' => self::PRODUCT_SETTINGS . '[image_generation_enabled]',
				'type' => 'checkbox',
				'label' => 'Enable?',
				'default' => '',
				'value' => $checkbox_value,
				'description' => 'Enable image generation for this product'
			),
			array(
				'id' => 'product_prompt_for_generation',
				'name' => self::PRODUCT_SETTINGS . '[product_prompt_for_generation]',
				'type' => 'textarea',
				'cols' => 55,
				'rows' => 4,
				'label' => 'Prompt',
				'value' => $iua_product_settings['product_prompt_for_generation'] ?? '',
				'default' => '',
				'description' => 'Prompt to use for image generation'
			),
			array(
				'id' => 'product_image_url',
				'name' => self::PRODUCT_SETTINGS . '[product_image_url]',
				'type' => 'text',
				'label' => 'Image URL',
				'default' => '',
				'size' => 55,
				'value' => $iua_product_settings['product_image_url'] ?? '',
				'description' => 'Enter custom image to use as a base for generation (instead of the product featured image)'
			),
		);
		?>

		<div class="iua-fieldset">
				<table class="form-table">
						<?php self::display_field_set( $fields ); ?>	
				</table>

				<?php
			}

			/**
			 * Saves IUA settings for the specified WC Product.
			 * @param int $post_id
			 */
			public function save_meta_box_data( $post_id ) {

				// Check if our nonce is set.
				if ( !filter_input( INPUT_POST, self::NONCE ) ) {
					return;
				}

				// Verify that the nonce is valid.
				if ( !wp_verify_nonce( filter_input( INPUT_POST, self::NONCE ), self::NONCE ) ) {

					return;
				}

				// If this is an autosave, our form has not been submitted, so we don't want to do anything.
				if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
					return;
				}

				// Check the post type and user's permissions
				if ( filter_input( INPUT_POST, 'post_type' ) == 'product' && current_user_can( 'edit_page', $post_id ) ) {

					// it's safe for us to save the data now

					$iua_settings = $_POST[self::PRODUCT_SETTINGS];

					if ( is_array( $iua_settings ) ) {

						// special case for checkbox
						if ( !isset( $iua_settings['image_generation_enabled'] ) ) {
							$iua_settings['image_generation_enabled'] = false;
						} else {
							$iua_settings['image_generation_enabled'] = true;
						}

						update_post_meta( $post_id, self::PRODUCT_SETTINGS, $iua_settings );
					}
				}
			}
		}
		