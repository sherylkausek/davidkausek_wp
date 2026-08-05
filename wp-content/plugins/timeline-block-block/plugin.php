<?php
/**
 * Plugin Name: Timeline Block - Beautiful Timeline Builder
 * Description: Display timeline content on your site.
 * Version: 1.5.3
 * Requires at least: 6.5
 * Tested up to: 7.0
 * Author: bPlugins
 * Author URI: https://bplugins.com
 * License: GPLv2 or later
 * Requires PHP: 7.4
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: timeline-block
 * Domain Path: /languages
 */

// ABS PATH
if (!defined('ABSPATH')) {
  exit;
}

if (function_exists('tlgb_fs')) {
  tlgb_fs()->set_basename(false, __FILE__);
} else {
  // Constant
  $tlgb_http_host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
  define( 'TLGB_VERSION', 'localhost' === $tlgb_http_host ? time() : '1.5.3' );
  define('TLGB_DIR_URL', plugin_dir_url(__FILE__));
  define('TLGB_DIR_PATH', plugin_dir_path(__FILE__));
  
  if (!function_exists('tlgb_fs')) {
    function tlgb_fs() {
      global $tlgb_fs;

      if (!isset($tlgb_fs)) {
        
        require_once TLGB_DIR_PATH . '/vendor/freemius-lite/start.php';

        $tlgbConfig = [
          'id' => '17342',
          'slug' => 'timeline-block-block',
          'premium_slug' => 'timeline-block-block-pro',
          'type' => 'plugin',
          'public_key' => 'pk_624005a9d0c56ff46db6602f5f730',
          'is_premium' => false,
          'menu' => [
              'slug' => 'edit.php?post_type=timeline_block',
              'first-path' => "edit.php?post_type=timeline_block&page=tlgb-dashboard#/welcome",
              'contact' => false,
              'support' => false,
              'affiliation' => false
          ],
        ];
        $tlgb_fs = fs_lite_dynamic_init($tlgbConfig);
      }

      return $tlgb_fs;
    }

    if ( is_admin() ) {
        tlgb_fs();
        do_action('tlgb_fs_loaded');
    }
  }
  require_once TLGB_DIR_PATH. 'includes/class-tlgb-main.php';
  new TLGBTimeline();

  // Activation hook to register CPT and flush rewrite rules (L-3)
  register_activation_hook( __FILE__, function() {
      require_once TLGB_DIR_PATH . 'includes/class-tlgb-cpt.php';
      $cpt = new TLGBCpt();
      $cpt->registerCPT();
      flush_rewrite_rules();
  } );
  
} 