<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'TLGBOptions' ) ) {
	class TLGBOptions {

		public function __construct() {
			add_action( 'wp_ajax_tlgbSaveUninstallOption', [ $this, 'saveUninstallOption' ] );
		}

		public static function getOptions() {
			$defaults = [
				'delete_data_on_uninstall' => false,
			];

			$options = get_option( 'tlgb_options', [] );

			return wp_parse_args( $options, $defaults );
		}

		public static function updateOptions( $new_options ) {
			$options = self::getOptions();
			$updated_options = array_merge( $options, $new_options );
			return update_option( 'tlgb_options', $updated_options );
		}
		
		public function saveUninstallOption() {
			check_ajax_referer( 'tlgbSaveUninstallOption', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Permission denied.', 'timeline-block' ) );
			}

			$enabled = isset( $_POST['enabled'] ) && 'true' === sanitize_text_field( wp_unslash( $_POST['enabled'] ) );

			self::updateOptions( [ 'delete_data_on_uninstall' => $enabled ] );

			wp_send_json_success( [
				'enabled' => $enabled,
				'message' => $enabled
					? __( 'All plugin data will be deleted when uninstalled.', 'timeline-block' )
					: __( 'Plugin data will be preserved when uninstalled.', 'timeline-block' )
			] );
		}
	}
}
