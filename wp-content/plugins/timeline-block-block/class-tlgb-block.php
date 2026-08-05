<?php
if (!defined('ABSPATH')) exit;
if (!class_exists('TLGBPlugin')) {
    class TLGBPlugin {

      public function __construct() {
        add_action('init', [$this, 'init']); 
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_assets']);
      }

      public function init() {
        wp_register_script(
          'tlgb-timeline-js',
          TLGB_DIR_URL . 'assets/js/timeline.min.js',
          [],
          TLGB_VERSION,
          true
        );

        wp_register_style(
          'tlgb-timeline-css',
          TLGB_DIR_URL . 'assets/css/timeline.min.css',
          [],
          TLGB_VERSION
        );

        $block = register_block_type( TLGB_DIR_PATH . 'build' );
        $handle = ( $block && ! empty( $block->editor_script_handles ) ) ? $block->editor_script_handles[0] : 'tlgb-b-timeline-block-editor-script';
        wp_set_script_translations( $handle, 'timeline-block', TLGB_DIR_PATH . 'languages' );
      }

      public function enqueue_editor_assets() {
        wp_enqueue_script('tlgb-timeline-js');
      }
    }
}