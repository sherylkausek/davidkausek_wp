<?php
/*
 * FormLayer
 * https://formlayer.net
 * (c) FormLayer Team
 */

namespace FormLayer;

if(!defined('ABSPATH')){
	exit;
}

class Migration {
	
	static function init() {
		add_action('formlayer_render_tools_tab', '\FormLayer\Migration::render_migration_ui', 5);
	}

	static function render_migration_ui() {
		$cf7_detected = post_type_exists('wpcf7_contact_form');
		$wpforms_detected = post_type_exists('wpforms');
		$gravity_detected = self::is_gf_active();
		$fluent_detected  = self::is_ff_active();

		echo'<div class="formlayer-settings-card" style="margin-top: 30px;">
			<div class="formlayer-settings-header">
				<div style="display:flex;align-items:center;gap:12px;">
					<div style="background:#fdf2f8;color:#db2777;width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;">
						<span class="dashicons dashicons-migrate" style="font-size:20px;width:20px;height:20px;margin-top:2px;"></span>
					</div>
					<div>
						<h3 style="margin:0;font-size:18px;font-weight:700;">' . esc_html__( 'Migrate From Other Plugins', 'formlayer' ) . '</h3>
						<p style="margin:4px 0 0;font-size:13px;color:var(--formlayer-text-muted);">' . esc_html__( 'Seamlessly import your forms from Contact Form 7, WPForms, Gravity Forms, or Fluent Forms.', 'formlayer' ) . '</p>
					</div>
				</div>
			</div>

			<div class="formlayer-settings-body" style="padding:24px 32px 32px;">
				<div class="formlayer-setting-row-v" style="margin-bottom:20px;display:block;">
					<label style="font-weight:600;font-size:14px;color:#334155;margin-bottom:8px;display:block;">' . esc_html__( 'Select Source Plugin', 'formlayer' ) . '</label>

					<select id="formlayer-migration-source" class="formlayer-select" style="max-width:350px;height:40px;">
						<option value="">' . esc_html__( '-- Choose a plugin --', 'formlayer' ) . '</option>
						<option value="cf7" '.( $cf7_detected ? '' : 'disabled' ) . '>Contact Form 7 ' . ( $cf7_detected ? '(Detected)' : '(Not Active)' ) . '</option>
						<option value="wpforms" '.( $wpforms_detected ? '' : 'disabled' ) . '>WPForms ' . ( $wpforms_detected ? '(Detected)' : '(Not Active)' ) . '</option>
						<option value="gravity" '.( $gravity_detected ? '' : 'disabled' ) . '>Gravity Forms ' . ( $gravity_detected ? '(Detected)' : '(Not Active)' ) . '</option>
						<option value="fluent" '.($fluent_detected ? '' : 'disabled' ) . '>Fluent Forms ' . ( $fluent_detected ? '(Detected)' : '(Not Active)' ) . '</option>
					</select>
				</div>

				<div id="formlayer-migration-forms-container" style="display:none;margin-top:20px;">
					<label style="font-weight:600;font-size:14px;color:#334155;margin-bottom:8px;display:block;">' . esc_html__( 'Select Forms to Migrate', 'formlayer' ).'</label>

					<div id="formlayer-migration-forms-list" style="max-height:200px;overflow-y:auto;border:1px solid #cbd5e1;border-radius:8px;padding:12px;background:#fafafa;display:flex;flex-direction:column;gap:8px;margin-bottom:15px;"></div>

					<button id="formlayer-btn-run-migration" class="formlayer-btn formlayer-btn-primary" style="height:42px;">
						<span class="dashicons dashicons-update" style="font-size:18px;margin-top:2px;margin-right:4px;"></span>' . esc_html__( 'Migrate Selected Forms', 'formlayer' ) . '
					</button>
				</div>

				<div id="formlayer-migration-status" style="display:none;margin-top:20px;padding:15px;border-radius:8px;"></div>
			</div>
		</div>';
	}

	static function is_gf_active(){
		global $wpdb;
		$table_name = $wpdb->prefix . 'gf_form_meta';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
	}

	static function is_ff_active(){
		global $wpdb;
		$table_name = $wpdb->prefix . 'fluentform_forms';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
	}

	static function get_source_forms($source) {
		$forms = [];

		switch ($source) {
			case 'cf7':
				$posts = get_posts([
					'post_type' => 'wpcf7_contact_form',
					'posts_per_page' => -1,
					'post_status' => 'any'
				]);
				foreach ($posts as $p) {
					$forms[] = [
						'id' => $p->ID,
						'title' => $p->post_title
					];
				}
				break;

			case 'wpforms':
				$posts = get_posts([
					'post_type' => 'wpforms',
					'posts_per_page' => -1,
					'post_status' => 'any'
				]);
				foreach ($posts as $p) {
					$forms[] = [
						'id' => $p->ID,
						'title' => $p->post_title
					];
				}
				break;

			case 'gravity':
				$forms = self::get_gravity_forms_list();
				break;

			case 'fluent':
				$forms = self::get_fluentform_list();
				break;
		}

		return $forms;
	}

	static function get_gravity_forms_list() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'gf_form_meta';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
			return [];
		}
		
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results("SELECT form_id, display_meta FROM $table_name");
		$forms = [];
		foreach ($results as $row) {
			$meta = json_decode($row->display_meta, true);
			$title = isset($meta['title']) ? $meta['title'] : 'Gravity Form #' . $row->form_id;
			$forms[] = [
				'id' => $row->form_id,
				'title' => $title
			];
		}
		return $forms;
	}

	static function get_fluentform_list() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'fluentform_forms';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
			return [];
		}
		
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results("SELECT id, title FROM $table_name");
		$forms = [];
		foreach ($results as $row) {
			$forms[] = [
				'id' => $row->id,
				'title' => $row->title
			];
		}
		return $forms;
	}

	static function run_migration($source, $form_ids) {
		$migrated_count = 0;
		$migrated_list = [];

		foreach ($form_ids as $id) {
			$fields = false;
			$title = '';

			switch ($source) {
				case 'cf7':
					$post = get_post($id);
					if ($post && $post->post_type === 'wpcf7_contact_form') {
						$title = $post->post_title;
						$fields = self::parse_cf7_form_fields($post->post_content);
					}
					break;

				case 'wpforms':
					$post = get_post($id);
					if ($post && $post->post_type === 'wpforms') {
						$title = $post->post_title;
						$fields = self::parse_wpforms_fields($post->post_content, $id);
					}
					break;

				case 'gravity':
					$title = self::get_gravity_form_title($id);
					$fields = self::parse_gravity_form_fields($id);
					break;

				case 'fluent':
					$title = self::get_fluent_form_title($id);
					$fields = self::parse_fluentform_fields($id);
					break;
			}

			if ($fields !== false) {
				$form_data = [
					'title' => $title,
					'fields' => $fields,
					'settings' => [
						'notifications' => [
							'enabled' => true,
							'to_email' => '{admin_email}',
							'subject' => 'New Submission - ' . $title,
							'message' => '{all_fields}',
							'format' => 'html'
						],
						'confirmations' => [
							'type' => 'message',
							'message' => 'Thank you for your submission!',
							'hide_form' => true
						]
					]
				];

				$form_json = json_encode($form_data, JSON_UNESCAPED_UNICODE);
				$new_form_id = wp_insert_post([
					'post_title' => $title,
					'post_content' => wp_slash($form_json),
					'post_type' => 'formlayer_form',
					'post_status' => 'publish',
				]);

				if ($new_form_id) {
					$counter = get_option('formlayer_id_counter', 0);
					$counter++;
					update_option('formlayer_id_counter', $counter);
					update_post_meta($new_form_id, '_formlayer_display_id', $counter);
					$migrated_count++;
					$migrated_list[] = [
						'id' => $counter,
						'title' => $title
					];
				}
			}
		}

		return [
			'migrated_count' => $migrated_count,
			'migrated_list' => $migrated_list
		];
	}

	static function get_gravity_form_title($form_id) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'gf_form_meta';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$display_meta = $wpdb->get_var($wpdb->prepare("SELECT display_meta FROM $table_name WHERE form_id = %d", $form_id));
		if (!empty($display_meta)) {
			$meta = json_decode($display_meta, true);
			if (!empty($meta['title'])) {
				return $meta['title'];
			}
		}
		return 'Gravity Form #' . $form_id;
	}

	static function get_fluent_form_title($form_id) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'fluentform_forms';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$title = $wpdb->get_var($wpdb->prepare("SELECT title FROM $table_name WHERE id = %d", $form_id));
		return !empty($title) ? $title : 'Fluent Form #' . $form_id;
	}

	static function parse_cf7_form_fields($post_content) {
		$fields = [];
		$id_counter = 1;
		
		preg_match_all('/\[(text|email|textarea|number|select|checkbox|radio|submit)(\*?)\s+([a-zA-Z0-9\-_]+)([^\]]*)\]/', $post_content, $matches, PREG_SET_ORDER);
		
		foreach ($matches as $match) {
			$type = $match[1];
			$required = ($match[2] === '*');
			$name = $match[3];
			$options_str = $match[4];
			
			switch ($type) {
				case 'email':
					$fl_type = 'email';
					break;
				case 'textarea':
					$fl_type = 'textarea';
					break;
				case 'number':
					$fl_type = 'number';
					break;
				case 'select':
					$fl_type = 'dropdown';
					break;
				case 'checkbox':
					$fl_type = 'checkbox';
					break;
				case 'radio':
					$fl_type = 'radio';
					break;
				case 'submit':
					$fl_type = 'submit';
					break;
				default:
					$fl_type = 'text';
					break;
			}
			
			$options = [];
			if (in_array($fl_type, ['dropdown', 'checkbox', 'radio'])) {
				preg_match_all('/"([^"]+)"/', $options_str, $opt_matches);
				if (!empty($opt_matches[1])) {
					$options = $opt_matches[1];
				}
			}
			
			$label = ucfirst(str_replace(['-', '_'], ' ', $name));
			if ($fl_type === 'submit') {
				$label = !empty($options) ? $options[0] : 'Submit';
			}
			
			$fields[] = [
				'id' => 'f' . $id_counter++,
				'type' => $fl_type,
				'label' => $label,
				'placeholder' => '',
				'required' => $required,
				'name_attr' => 'field_' . $name,
				'options' => $options
			];
		}
		
		$has_submit = false;
		foreach ($fields as $f) {
			if ($f['type'] === 'submit') {
				$has_submit = true;
				break;
			}
		}
		if (!$has_submit) {
			$fields[] = [
				'id' => 'f' . $id_counter++,
				'type' => 'submit',
				'label' => 'Submit',
				'placeholder' => '',
				'required' => false,
				'name_attr' => 'submit_btn',
				'options' => []
			];
		}
		
		return $fields;
	}

	static function parse_wpforms_fields($post_content, $post_id) {
		$form_data = get_post_meta($post_id, 'wpforms_form_data', true);
		if (empty($form_data) && !empty($post_content)) {
			$form_data = json_decode($post_content, true);
		}
		if (is_string($form_data)) {
			$form_data = maybe_unserialize($form_data);
		}
		if (empty($form_data) || !is_array($form_data) || empty($form_data['fields'])) {
			return false;
		}
		
		$fields = [];
		$id_counter = 1;
		
		foreach ($form_data['fields'] as $wfid => $wf_field) {
			$type = isset($wf_field['type']) ? $wf_field['type'] : '';
			$label = isset($wf_field['label']) ? $wf_field['label'] : '';
			$required = !empty($wf_field['required']);
			
			switch ($type) {
				case 'email':
					$fl_type = 'email';
					break;
				case 'textarea':
					$fl_type = 'textarea';
					break;
				case 'number':
					$fl_type = 'number';
					break;
				case 'select':
					$fl_type = 'dropdown';
					break;
				case 'checkbox':
					$fl_type = 'checkbox';
					break;
				case 'radio':
					$fl_type = 'radio';
					break;
				case 'phone':
					$fl_type = 'phone';
					break;
				default:
					$fl_type = 'text';
					break;
			}
			
			$options = [];
			if (in_array($fl_type, ['dropdown', 'checkbox', 'radio']) && !empty($wf_field['choices'])) {
				foreach ($wf_field['choices'] as $choice) {
					if (isset($choice['label'])) {
						$options[] = $choice['label'];
					}
				}
			}
			
			$fields[] = [
				'id' => 'f' . $id_counter++,
				'type' => $fl_type,
				'label' => $label,
				'placeholder' => isset($wf_field['placeholder']) ? $wf_field['placeholder'] : '',
				'required' => $required,
				'name_attr' => 'field_' . $wfid,
				'options' => $options
			];
		}
		
		$fields[] = [
			'id' => 'f' . $id_counter++,
			'type' => 'submit',
			'label' => isset($form_data['settings']['submit_text']) ? $form_data['settings']['submit_text'] : 'Submit',
			'placeholder' => '',
			'required' => false,
			'name_attr' => 'submit_btn',
			'options' => []
		];
		
		return $fields;
	}

	static function parse_gravity_form_fields($form_id){
		global $wpdb;
		$table_name = $wpdb->prefix . 'gf_form_meta';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$display_meta = $wpdb->get_var($wpdb->prepare("SELECT display_meta FROM $table_name WHERE form_id = %d", $form_id));
		if (empty($display_meta)) return false;
		
		$meta = json_decode($display_meta, true);
		if (empty($meta) || empty($meta['fields'])) return false;
		
		$fields = [];
		$id_counter = 1;
		
		foreach ($meta['fields'] as $gf_field) {
			$type = isset($gf_field['type']) ? $gf_field['type'] : '';
			$label = isset($gf_field['label']) ? $gf_field['label'] : '';
			$required = !empty($gf_field['isRequired']);
			
			switch ($type) {
				case 'email':
					$fl_type = 'email';
					break;
				case 'textarea':
					$fl_type = 'textarea';
					break;
				case 'number':
					$fl_type = 'number';
					break;
				case 'select':
					$fl_type = 'dropdown';
					break;
				case 'checkbox':
					$fl_type = 'checkbox';
					break;
				case 'radio':
					$fl_type = 'radio';
					break;
				case 'phone':
					$fl_type = 'phone';
					break;
				default:
					$fl_type = 'text';
					break;
			}
			
			$options = [];
			if (in_array($fl_type, ['dropdown', 'checkbox', 'radio']) && !empty($gf_field['choices'])) {
				foreach ($gf_field['choices'] as $choice) {
					if (isset($choice['text'])) {
						$options[] = $choice['text'];
					}
				}
			}
			
			$fields[] = [
				'id' => 'f' . $id_counter++,
				'type' => $fl_type,
				'label' => $label,
				'placeholder' => isset($gf_field['placeholder']) ? $gf_field['placeholder'] : '',
				'required' => $required,
				'name_attr' => 'field_' . $gf_field['id'],
				'options' => $options
			];
		}
		
		$fields[] = [
			'id' => 'f' . $id_counter++,
			'type' => 'submit',
			'label' => isset($meta['button']['text']) ? $meta['button']['text'] : 'Submit',
			'placeholder' => '',
			'required' => false,
			'name_attr' => 'submit_btn',
			'options' => []
		];
		
		return $fields;
	}

	static function parse_fluentform_fields($form_id) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'fluentform_forms';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$form_fields_json = $wpdb->get_var($wpdb->prepare("SELECT form_fields FROM $table_name WHERE id = %d", $form_id));
		if (empty($form_fields_json)) return false;
		
		$form_fields = json_decode($form_fields_json, true);
		if (empty($form_fields) || empty($form_fields['fields'])) return false;
		
		$fields = [];
		$id_counter = 1;
		
		$all_raw_fields = [];
		self::extract_ff_fields_flat($form_fields['fields'], $all_raw_fields);
		
		foreach ($all_raw_fields as $ff_field) {
			$type = isset($ff_field['element']) ? $ff_field['element'] : '';
			
			$label = isset($ff_field['settings']['label']) ? $ff_field['settings']['label'] : (isset($ff_field['attributes']['name']) ? ucfirst($ff_field['attributes']['name']) : '');
			$required = !empty($ff_field['settings']['validation_rules']['required']['value']);
			
			switch ($type) {
				case 'input_email':
					$fl_type = 'email';
					break;
				case 'textarea':
					$fl_type = 'textarea';
					break;
				case 'input_number':
					$fl_type = 'number';
					break;
				case 'select':
					$fl_type = 'dropdown';
					break;
				case 'input_checkbox':
					$fl_type = 'checkbox';
					break;
				case 'input_radio':
					$fl_type = 'radio';
					break;
				case 'phone':
					$fl_type = 'phone';
					break;
				case 'input_url':
					$fl_type = 'url';
					break;
				case 'input_password':
					$fl_type = 'password';
					break;
				case 'input_date':
					$fl_type = 'date';
					break;
				case 'gdpr_agreement':
					$fl_type = 'gdpr';
					break;
				case 'terms_and_condition':
					$fl_type = 'terms';
					break;
				default:
					$fl_type = 'text';
					break;
			}
			
			$options = [];
			if (in_array($fl_type, ['dropdown', 'checkbox', 'radio']) && !empty($ff_field['settings']['options'])) {
				foreach ($ff_field['settings']['options'] as $choice) {
					if (isset($choice['label'])) {
						$options[] = $choice['label'];
					}
				}
			}
			
			$name_attr = isset($ff_field['attributes']['name']) ? $ff_field['attributes']['name'] : 'field_' . $id_counter;
			
			$fields[] = [
				'id' => 'f' . $id_counter++,
				'type' => $fl_type,
				'label' => $label,
				'placeholder' => isset($ff_field['attributes']['placeholder']) ? $ff_field['attributes']['placeholder'] : '',
				'required' => $required,
				'name_attr' => 'field_' . $name_attr,
				'options' => $options
			];
		}
		
		$fields[] = [
			'id' => 'f' . $id_counter++,
			'type' => 'submit',
			'label' => isset($form_fields['submitButton']['settings']['button_ui']['text']) ? $form_fields['submitButton']['settings']['button_ui']['text'] : 'Submit',
			'placeholder' => '',
			'required' => false,
			'name_attr' => 'submit_btn',
			'options' => []
		];
		
		return $fields;
	}

	static function extract_ff_fields_flat($fields, &$flat_list) {
		if (!is_array($fields)) return;
		foreach ($fields as $field) {
			if (isset($field['element']) && $field['element'] === 'container') {
				if (!empty($field['columns'])) {
					foreach ($field['columns'] as $column) {
						if (!empty($column['fields'])) {
							self::extract_ff_fields_flat($column['fields'], $flat_list);
						}
					}
				}
			} else {
				$flat_list[] = $field;
			}
		}
	}
}