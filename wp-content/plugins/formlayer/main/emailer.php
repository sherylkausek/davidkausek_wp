<?php
namespace FormLayer;

if(!defined('ABSPATH')){
	exit;
}

class Emailer{

	static function build_fields($form_id, $submitted_data){
		$field_labels = \FormLayer\Util::get_form_field_labels($form_id);
		$field_types = \FormLayer\Util::get_form_field_types($form_id);
		$fields_text = "";
		$fields_html = '<div style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); border: 1px solid #e5e7eb;">';
		$fields_html .= '<div style="padding: 20px 30px 15px; border-bottom: 1px solid #e5e7eb; background-color: #ffffff;">';
		$fields_html .= '<h2 style="margin: 0; color: #111827; font-size: 18px; font-weight: 600;">Submission Details</h2>';
		$fields_html .= '</div>';
		$fields_html .= '<div style="padding: 25px 30px 10px;">';

		foreach($submitted_data as $key => $val) {
			if ($key === '__source_url') continue;

			$label = isset($field_labels[$key]) ? $field_labels[$key] : ucfirst(str_replace(['field_', '_'], ['', ' '], $key));
			$type = isset($field_types[$key]) ? $field_types[$key] : '';

			if(is_array($val)){
				$val = implode(', ', $val);
			}

			// Mask password in email
			if($type === 'password'){
				$val = '******';
			}

			$fields_text .= $label . ": " . $val . "\n";

			$fields_html .= '<div style="margin-bottom: 25px;">';
			$fields_html .= '<div style="font-weight: 600; color: #111827; font-size: 15px; margin-bottom: 8px; display: block;">' . esc_html($label) . '</div>';

			if(strpos($val, "\n") !== false) {
				$fields_html .= '<div style="color: #4b5563; font-size: 15px; background: #f9fafb; padding: 12px 16px; border-radius: 6px; border: 1px solid #e5e7eb; white-space: pre-wrap; margin: 0;">' . esc_html($val) . '</div>';
			} else {
				$fields_html .= '<div style="color: #4b5563; font-size: 15px; background: #f9fafb; padding: 12px 16px; border-radius: 6px; border: 1px solid #e5e7eb; margin: 0;">' . esc_html($val) . '</div>';
			}
			$fields_html .= '</div>';
		}

		$fields_html .= '</div>'; // End inner padding div
		$fields_html .= '</div>'; // End card div

		if (!empty($submitted_data['__source_url'])) {
			$fields_text .= "\nSource URL: " . $submitted_data['__source_url'] . "\n";
			$fields_html .= '<div style="margin-top: 20px; font-size: 15px; color: #4b5563;">';
			$fields_html .= '<strong style="color: #111827;">Source URL:</strong> <a href="' . esc_url($submitted_data['__source_url']) . '" style="color: #3b82f6; text-decoration: none;">' . esc_html($submitted_data['__source_url']) . '</a>';
			$fields_html .= '</div>';
		}

		return [
			'fields_html' => $fields_html,
			'fields_text' => $fields_text,
		];
	}

	// Strip CR/LF from a string to prevent email header injection
	static function strip_newlines($str){
		return str_replace(["\r", "\n", "%0d", "%0a"], '', (string)$str);
	}

	// Helper to build merge tags based on content type
	static function build_merge_tags($is_html, $fields_html, $fields_text, $form, $submitted_data){
		$tags = [
			'{all_fields}' => $is_html ? $fields_html : $fields_text,
			'{admin_email}' => get_option('admin_email'),
			'{form_title}' => $form->post_title,
			'{site_title}' => get_bloginfo('name'),
			'{site_url}' => get_site_url()
		];
		foreach($submitted_data as $key => $val){
			if(is_array($val)){
				$val = implode(', ', $val);
			}
			// Plain-text variant (used for headers) must have newlines stripped
			$tags['{'.$key.'}'] = $is_html ? esc_html($val) : self::strip_newlines($val);
		}
		return $tags;
	}

	// Email Notification (Admin)
	static function send_admin_notification($form, $submitted_data){
		$form_id = $form->ID;
		$form_data = json_decode($form->post_content, true);
		$settings = isset($form_data['settings']) ? $form_data['settings'] : [];

		$built = self::build_fields($form_id, $submitted_data);
		$fields_html = $built['fields_html'];
		$fields_text = $built['fields_text'];

		$mail_sent = false;
		if (!empty($settings['notifications']['enabled'])) {
			$to = !empty($settings['notifications']['to_email']) && strpos($settings['notifications']['to_email'], '{admin_email}') === false ? $settings['notifications']['to_email'] : get_option('admin_email');
			$subject = !empty($settings['notifications']['subject']) ? $settings['notifications']['subject'] : 'New Form Submission';
			$message_body = !empty($settings['notifications']['message']) ? $settings['notifications']['message'] : "You have a new submission:\n\n{all_fields}";

			$is_html = false;
			$format = isset($settings['notifications']['format']) ? $settings['notifications']['format'] : 'html';

			if($format === 'html'){
				$is_html = true;
			}

			$merge_tags = self::build_merge_tags($is_html, $fields_html, $fields_text, $form, $submitted_data);

			if($is_html){
				// Make the default text look nice in HTML if it's present
				$message_body = str_replace("You have a new submission:", '<h2 style="margin: 0 0 20px 0; color: #111827; font-size: 20px; font-weight: 600;">You have a new submission</h2>', $message_body);
				$message_body = str_replace("You have a new submission<br><br>", '<h2 style="margin: 0 0 20px 0; color: #111827; font-size: 20px; font-weight: 600;">You have a new submission</h2>', $message_body);
				$message_body = str_replace("You have a new submission: \n ", '<h2 style="margin: 0 0 20px 0; color: #111827; font-size: 20px; font-weight: 600;">You have a new submission</h2>', $message_body);

				$message_body = wpautop($message_body);
				$message_body = str_replace('<p>{all_fields}</p>', '{all_fields}', $message_body);
			} else {
				$message_body = str_ireplace(['<br>', '<br/>', '<br />'], "\n", $message_body);
				$message_body = wp_strip_all_tags($message_body);
			}

			$to = strtr($to, $merge_tags);
			$subject = self::strip_newlines(strtr($subject, $merge_tags));
			$message_body = strtr($message_body, $merge_tags);

			if($is_html){
				$message_body = '<div style="background-color: #f9fafb; padding: 40px 20px; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; color: #374151; line-height: 1.6;">' . '<div style="max-width: 600px; margin: 0 auto;">' . $message_body .
				'<div style="margin-top: 30px; text-align: center; font-size: 13px; color: #6b7280;">' . 'Powered by <a href="https://formlayer.net" style="color: #3b82f6; text-decoration: none;">FormLayer</a>' . '</div>' .'</div>' .'</div>';
			}

			$headers = [];
			$from_name = !empty($settings['notifications']['from_name']) ? self::strip_newlines(strtr($settings['notifications']['from_name'], $merge_tags)) : get_bloginfo('name');
			$from_email = !empty($settings['notifications']['from_email']) ? self::strip_newlines(strtr($settings['notifications']['from_email'], $merge_tags)) : get_option('admin_email');

			if($from_email === '{admin_email}'){
				$from_email = get_option('admin_email');
			}

			// Validate from_email is a valid single address
			if(!is_email($from_email)){
				$from_email = get_option('admin_email');
			}

			// Validate to is a valid single email address
			$to = is_email($to) ? $to : get_option('admin_email');

			$headers[] = "From: " . $from_name . " <" . $from_email . ">";

			if(!empty($settings['notifications']['reply_to'])){
				$reply_to = self::strip_newlines(strtr($settings['notifications']['reply_to'], $merge_tags));
				if(is_email($reply_to)){
					$headers[] = "Reply-To: " . $reply_to;
				}
			}

			if(!empty($settings['notifications']['bcc'])){
				$bcc_raw = self::strip_newlines(strtr($settings['notifications']['bcc'], $merge_tags));
				$bcc_parts = array_map('trim', explode(',', $bcc_raw));
				$bcc_valid = array_filter($bcc_parts, 'is_email');
				if(!empty($bcc_valid)){
					$headers[] = "Bcc: " . implode(', ', $bcc_valid);
				}
			}

			$content_type_filter = function() { return 'text/html'; };
			if($is_html){
				add_filter('wp_mail_content_type', $content_type_filter);
			}

			$mail_sent = @wp_mail($to, $subject, $message_body, $headers);

			if($is_html){
				remove_filter('wp_mail_content_type', $content_type_filter);
			}
		}

		return [
			'mail_sent' => $mail_sent,
			'settings' => $settings,
		];
	}}