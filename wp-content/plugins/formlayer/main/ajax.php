<?php
namespace FormLayer;

if(!defined('ABSPATH')){
	exit;
}

class Ajax{

	static function hooks(){
		add_action('wp_ajax_formlayer_submit_form', '\FormLayer\Ajax::handle_form_submit');
		add_action('wp_ajax_nopriv_formlayer_submit_form', '\FormLayer\Ajax::handle_form_submit');
		add_action('wp_ajax_formlayer_delete_form', '\FormLayer\Ajax::delete_form');
		add_action('wp_ajax_formlayer_save_settings', '\FormLayer\Ajax::save_settings');
		add_action('wp_ajax_formlayer_save_form', '\FormLayer\Ajax::save_form');
		add_action('wp_ajax_formlayer_load_form', '\FormLayer\Ajax::load_form');
		add_action('wp_ajax_formlayer_get_migration_source_forms', '\FormLayer\Ajax::get_migration_source_forms');
		add_action('wp_ajax_formlayer_run_form_migration', '\FormLayer\Ajax::run_form_migration');
	}

	static function delete_form(){
		check_ajax_referer('formlayer_admin_nonce', 'nonce');

		if(!current_user_can('manage_options')){
			wp_send_json_error(['message' => esc_html__('Insufficient permissions', 'formlayer')]);
		}
		
		$display_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
		if(empty($display_id)){
			wp_send_json_error(['message' => 'Invalid form ID']);
		}
		
		$form_id = \FormLayer\Util::get_post_id_by_display_id($display_id);
		if(empty($form_id)){
			wp_send_json_error(['message' => 'Form mapped ID not found']);
		}

		$form = get_post($form_id);
		if(!$form || 'formlayer_form' !== $form->post_type){
			wp_send_json_error(['message' => 'Form not found']);
		}
		
		wp_delete_post($form_id, true);
		
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$remaining = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'formlayer_form' AND post_status NOT IN ('trash', 'auto-draft')");
		if (intval($remaining) === 0) {
			update_option('formlayer_id_counter', 0);
		}

		wp_send_json_success(['message' => 'Form deleted']);
	}

	

	static function save_settings(){
		check_ajax_referer('formlayer_admin_nonce', 'nonce');

		if(!current_user_can('manage_options')){
			wp_send_json_error(['message' => esc_html__('Insufficient permissions', 'formlayer')]);
		}

		// $_POST['settings'] is sent as an array from JS — do NOT pass through
		// sanitize_text_field() which converts arrays to an empty string.
		// Each key/value is sanitized individually in the loop below.
		$raw_settings = isset($_POST['settings']) && is_array($_POST['settings']) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			? wp_unslash($_POST['settings']) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			: [];
		$to_save = [];

		foreach( (array) $raw_settings as $key => $value ){
			$to_save[ sanitize_key( $key ) ] = wp_strip_all_tags( (string) $value );
		}

		update_option('formlayer_settings', $to_save);

		wp_send_json_success(['message' => esc_html__('Settings saved successfully!', 'formlayer')]);
	}

	static function handle_form_submit(){
	
		check_ajax_referer('formlayer-frontend', 'nonce');

		$display_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
		$form_id = \FormLayer\Util::get_post_id_by_display_id($display_id);
		
		if(empty($form_id)){
			// Try as raw post ID fallback
			$check_post = get_post($display_id);
			if($check_post && 'formlayer_form' === $check_post->post_type){
				$form_id = $display_id;
			}
		}

		if(empty($form_id)){
			wp_send_json_error(['message' => 'Form not found.']);
		}

		$form = get_post($form_id);

		// Check if form has captcha field & verify
		$form_data = json_decode($form->post_content, true);
		if (!is_array($form_data)) {
			$form_data = ['fields' => []];
		}
		
		$has_captcha = false;
		$captcha_provider = 'none';
		$fields = isset($form_data['fields']) ? (array)$form_data['fields'] : [];
		
		foreach($fields as $f){
			if(isset($f['type']) && $f['type'] === 'captcha'){
				$has_captcha = true;
				$captcha_provider = isset($f['captcha_provider']) ? $f['captcha_provider'] : 'hcaptcha';
				break;
			}
		}
		
		if($has_captcha && $captcha_provider !== 'none'){
			$global_settings = get_option('formlayer_settings', []);
			
			if($captcha_provider !== 'none'){
				$captcha_verified = false;
				$captcha_token = '';
				$secret_key = '';
				$verify_url = '';

				if($captcha_provider === 'hcaptcha'){
					$captcha_token = isset($_POST['h-captcha-response']) ? sanitize_text_field(wp_unslash($_POST['h-captcha-response'])) : '';
					$secret_key = isset($global_settings['captcha_h_secret_key']) ? $global_settings['captcha_h_secret_key'] : '';
					$verify_url = 'https://hcaptcha.com/siteverify';
				} else {
					// Pro captcha providers - delegate to Pro plugin via action
					$sanitized_post = array_map('sanitize_text_field', wp_unslash($_POST));
					$captcha_verified = apply_filters('formlayer_verify_pro_captcha', false, $captcha_provider, $sanitized_post, $global_settings);
				}

				if(!empty($captcha_token) && !empty($secret_key) && $captcha_provider === 'hcaptcha'){
					$response = wp_remote_post($verify_url, [
						'body' => [
							'secret' => $secret_key,
							'response' => $captcha_token
						]
					]);
					
					if(!is_wp_error($response)) {
						$body = json_decode(wp_remote_retrieve_body($response), true);
						if($body && !empty($body['success'])){
							$captcha_verified = true;
						}
					}
				}

				if(!$captcha_verified){
					wp_send_json_error(['message' => esc_html__('Captcha verification failed. Please try again.', 'formlayer')]);
				}
			}
		}

		$submitted_data = [];
		$submitted_data['__source_url'] = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])) : '';
		$excluded_keys = ['action', 'nonce', 'form_id', 'h-captcha-response', 'cf-turnstile-response', 'g-recaptcha-response'];
		
		foreach($_POST as $key => $value){
			if(!in_array($key, $excluded_keys)){
				if(is_array($value)){
					$submitted_data[$key] = array_map('sanitize_text_field', $value);
				} else {
					$submitted_data[$key] = sanitize_text_field(wp_unslash($value));
				}
			}
		}

		// --- Field Validation ---
		$global_settings = get_option('formlayer_settings', []);
		$fields = isset($form_data['fields']) ? $form_data['fields'] : [];
		
		foreach($fields as $field){
			$input_name = \FormLayer\Util::get_field_name($field);
			
			if(in_array($field['type'], ['file', 'image', 'camera'])){
				// Validate and sanitize $_FILES input before use (fixes InputNotValidated + InputNotSanitized warnings)
				$file_name = isset($_FILES[$input_name]['name']) ? sanitize_file_name($_FILES[$input_name]['name']) : '';
				$file_error = isset($_FILES[$input_name]['error']) ? (int) $_FILES[$input_name]['error'] : -1;
				$file_tmp = isset($_FILES[$input_name]['tmp_name']) ? sanitize_text_field($_FILES[$input_name]['tmp_name']) : '';
				$file_size = isset($_FILES[$input_name]['size']) ? (int) $_FILES[$input_name]['size'] : 0;
				$file_type = isset($_FILES[$input_name]['type']) ? sanitize_mime_type($_FILES[$input_name]['type']) : '';

				$file_data  = !empty($file_name) ? [
					'name' => $file_name,
					'error' => $file_error,
					'tmp_name' => $file_tmp,
					'size' => $file_size,
					'type' => $file_type,
				] : [];

				$file_uploaded = !empty($file_name) && $file_error === UPLOAD_ERR_OK;

				if(!empty($field['required']) && !$file_uploaded){
					wp_send_json_error(['message' => !empty($global_settings['msg_required']) ? $global_settings['msg_required'] : __('This field is required.', 'formlayer')]);
				}

				if(!empty($file_uploaded)){
					if(!function_exists('wp_handle_upload')){
						require_once(ABSPATH . 'wp-admin/includes/file.php');
					}

					if(in_array($field['type'], ['image', 'camera'])){
						$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
						$allowed_image_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
						if(!in_array($file_ext, $allowed_image_exts)){
							wp_send_json_error(['message' => __('Invalid image format. Allowed formats: JPG, JPEG, PNG, GIF, WEBP, BMP.', 'formlayer')]);
						}
					}

					$uploadedfile    = $file_data;
					$upload_overrides = ['test_form' => false];
					$movefile        = wp_handle_upload($uploadedfile, $upload_overrides);

					if($movefile && !isset($movefile['error'])){
						$submitted_data[$input_name] = $movefile['url'];
					} else {
						$error_message = isset($movefile['error']) ? $movefile['error'] : 'Unknown error';
						/* translators: %s: Error message returned by the file upload handler. */
						wp_send_json_error(['message' => sprintf(__('File upload error: %s', 'formlayer'), $error_message)]);
					}
				} else {
					$submitted_data[$input_name] = '';
				}
				continue;
			}

			$value = isset($submitted_data[$input_name]) ? $submitted_data[$input_name] : '';
			
			// Required check
			$is_empty = false;
			if (is_array($value)) {
				$is_empty = true;
				foreach ($value as $v) {
					if (!empty($v)) {
						$is_empty = false;
						break;
					}
				}
			} else {
				$is_empty = empty($value);
			}

			if(!empty($field['required']) && $is_empty){
				wp_send_json_error(['message' => !empty($global_settings['msg_required']) ? $global_settings['msg_required'] : __('This field is required.', 'formlayer')]);
			}
			
			if(!$is_empty){
				// Email check
				if($field['type'] === 'email' && !is_array($value)){
					if(!is_email($value)){
						wp_send_json_error(['message' => !empty($global_settings['msg_email']) ? $global_settings['msg_email'] : __('Please enter a valid email address.', 'formlayer')]);
					}
				}
				// URL check
				if($field['type'] === 'url' && !is_array($value) && !filter_var($value, FILTER_VALIDATE_URL)){
					wp_send_json_error(['message' => !empty($global_settings['msg_url']) ? $global_settings['msg_url'] : __('Please enter a valid URL.', 'formlayer')]);
				}
				// Number check
				if($field['type'] === 'number' && !is_array($value)){
					if(!is_numeric($value)){
						wp_send_json_error(['message' => !empty($global_settings['msg_number']) ? $global_settings['msg_number'] : __('Please enter a valid number.', 'formlayer')]);
					}
					
					// Min/Max check
					if(isset($field['min']) && $field['min'] !== '' && $value < $field['min']){
						/* translators: %s: minimum value */
						wp_send_json_error(['message' => sprintf(__('Minimum value is %s.', 'formlayer'), $field['min'])]);
					}
					if(isset($field['max']) && $field['max'] !== '' && $value > $field['max']){
						/* translators: %s: maximum value */
						wp_send_json_error(['message' => sprintf(__('Maximum value is %s.', 'formlayer'), $field['max'])]);
					}
					
					// Digit limit
					if(!empty($field['digit_limit']) && strlen((string)$value) > intval($field['digit_limit'])){
						/* translators: %d: maximum digit limit */
						wp_send_json_error(['message' => sprintf(__('Maximum digit limit is %d.', 'formlayer'), $field['digit_limit'])]);
					}
				}
				// Terms check
				if(($field['type'] === 'terms' || $field['type'] === 'gdpr') && $is_empty){
					wp_send_json_error(['message' => !empty($global_settings['msg_terms']) ? $global_settings['msg_terms'] : __('You must accept the terms.', 'formlayer')]);
				}
			}

			if($field['type'] === 'password' && !$is_empty){
				$submitted_data[$input_name] = wp_hash_password($submitted_data[$input_name]);
			}
		}

		// Email Notification (Admin)
		$admin_result = \FormLayer\Emailer::send_admin_notification($form, $submitted_data);
		$mail_sent = $admin_result['mail_sent'];
		$settings = $admin_result['settings'];

		// Pro Feature Email Confirmation (End User)
		do_action('formlayer_send_user_confirmation', $form, $submitted_data);

		// Trigger integrations via action hook
		$entry_id = apply_filters('formlayer_before_submission_response', 0, $form_id, $submitted_data);
		do_action('formlayer_after_submission', $form_id, $submitted_data, $entry_id);

		// Ensure clean buffer before sending JSON
		if (ob_get_length()) {
			ob_clean();
		}

		wp_send_json_success([
			'message'=> !empty($settings['confirmations']['message']) ? $settings['confirmations']['message'] : 'Thank you! Your form has been submitted successfully.',
			'settings'=> $settings,
			'mail_sent' => $mail_sent,
			'entry_id' => $entry_id,
		]);
	}

	static function save_form(){
		check_ajax_referer('formlayer_admin_nonce', 'nonce');

		if(!current_user_can('manage_options')){
			wp_send_json_error(['message' => esc_html__('Insufficient permissions', 'formlayer')]);
		}

		$display_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
		$form_id = !empty($display_id) ? \FormLayer\Util::get_post_id_by_display_id($display_id) : 0;
		$title = isset($_POST['title']) ? wp_strip_all_tags(wp_unslash($_POST['title'])) : 'Untitled Form'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		
		if (isset($_POST['fields']) && isset($_POST['settings'])) {
			$form_data = [
				'title' => $title,
				'fields' => json_decode(wp_unslash($_POST['fields']), true), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'settings' => json_decode(wp_unslash($_POST['settings']), true) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			];
			$form_json = json_encode($form_data, JSON_UNESCAPED_UNICODE);
		} else {
			// Same fix: wp_unslash only, no sanitize_text_field on the raw JSON string.
			$form_json = isset($_POST['form_data']) ? wp_unslash($_POST['form_data']) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}

		if (!$form_json) {
			wp_send_json_error(['message' => 'No form data provided.']);
		}

		if(!empty($form_id)){
			// Update existing form
			wp_update_post([
				'ID' => $form_id,
				'post_title' => $title,
				'post_content' => wp_slash($form_json),
			]);
		} else {
			// Create new form
			$form_id = wp_insert_post([
				'post_title' => $title,
				'post_content' => wp_slash($form_json),
				'post_type' => 'formlayer_form',
				'post_status' => 'publish',
			]);

			if ($form_id) {
				$counter = get_option('formlayer_id_counter', 0);
				$counter++;
				update_option('formlayer_id_counter', $counter);
				update_post_meta($form_id, '_formlayer_display_id', $counter);
			}
		}

		$display_id = get_post_meta($form_id, '_formlayer_display_id', true);

		ob_start();
		\FormLayer\Settings\UI::render_form_row(get_post($form_id));
		$row_html = ob_get_clean();

		wp_send_json_success([
			'form_id' => $display_id ? $display_id : $form_id,
			'display_id' => $display_id ? $display_id : $form_id,
			'row_html' => $row_html
		]);
	}

	static function load_form(){
		check_ajax_referer('formlayer_admin_nonce', 'nonce');

		if(!current_user_can('manage_options')){
			wp_send_json_error(['message' => esc_html__('Insufficient permissions', 'formlayer')]);
		}

		$display_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
		if (!$display_id) {
			wp_send_json_error(['message' => 'Invalid form ID.']);
		}

		$form_id = \FormLayer\Util::get_post_id_by_display_id($display_id);
		
		// Fallback for cases where shortcode hasn't kicked counter initially yet
		if(!$form_id) {
		    $form_id = $display_id;
		}

		$form = get_post($form_id);
		if (!$form || $form->post_type !== 'formlayer_form') {
			wp_send_json_error(['message' => 'Form not found.']);
		}

		$form_data = json_decode($form->post_content, true);
		$display_id = get_post_meta($form_id, '_formlayer_display_id', true);
		wp_send_json_success([
			'form_data' => $form_data,
			'display_id' => $display_id ? $display_id : $form_id
		]);
	}

	static function get_migration_source_forms() {
		check_ajax_referer('formlayer_admin_nonce', 'nonce');
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => esc_html__('Insufficient permissions', 'formlayer')]);
		}

		$source = isset($_GET['source']) ? sanitize_key($_GET['source']) : '';
		$forms = \FormLayer\Migration::get_source_forms($source);

		wp_send_json_success(['forms' => $forms]);
	}

	static function run_form_migration() {
		check_ajax_referer('formlayer_admin_nonce', 'nonce');
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => esc_html__('Insufficient permissions', 'formlayer')]);
		}

		$source = isset($_POST['source']) ? sanitize_key($_POST['source']) : '';
		$form_ids = isset($_POST['form_ids']) ? array_map('intval', $_POST['form_ids']) : [];

		if (empty($source) || empty($form_ids)) {
			wp_send_json_error(['message' => esc_html__('Invalid parameters', 'formlayer')]);
		}

		$result = \FormLayer\Migration::run_migration($source, $form_ids);

		wp_send_json_success($result);
	}
}