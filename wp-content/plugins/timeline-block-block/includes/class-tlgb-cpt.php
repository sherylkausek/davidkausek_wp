<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if(!class_exists('TLGBCpt')) {
    class TLGBCpt {
        public function __construct() {
            add_action('init', [$this, 'registerCPT']);
            add_filter('manage_timeline_block_posts_columns', [$this, 'tlgb_timelineBlockManageColumns'], 10);
            add_action('manage_timeline_block_posts_custom_column', [$this, 'tlgb_timelineBlockManageCustomColumns'], 10, 2);
        }
    
        public function registerCPT() {
            // Note: 'timeline_block' is a generic CPT key. Consider prefixing (e.g. 'tlgb_timeline')
            // in a future major version to reduce collision risk with other plugins.
            register_post_type('timeline_block', [
                'labels' => [
                    'name' => __( 'Timeline Block', 'timeline-block' ),
                    'singular_name' => __( 'Timeline', 'timeline-block' ),
                    'add_new' => __( 'Add New', 'timeline-block' ),
                    'add_new_item' => __( 'Add New Timeline', 'timeline-block' ),
                    'new_item' => __( 'New Timeline', 'timeline-block' ),
                    'edit_item' => __( 'Edit Timeline', 'timeline-block' ),
                    'not_found' => __( 'No timelines found. Add one to get started.', 'timeline-block' ), // Fixes L-5 (placeholder copy)
                    'search_items' => __( 'Search Timeline', 'timeline-block' ),
                    'view_item' => __( 'View Timeline', 'timeline-block' ),
                    'menu_name' => __( 'Timeline', 'timeline-block' ),
                    'all_items' => __( 'Timeline Block ShortCodes', 'timeline-block' ),
                    'not_found_in_trash' => __( 'No Timeline found in trash', 'timeline-block' ),
                    'item_updated' => __( 'Timeline updated', 'timeline-block' ),
                ],
                'public' => true,
                'has_archive' => false, // Fixes L-3 (archive not intended)
                'publicly_queryable' => false, // Fixes L-3
                'exclude_from_search' => true, // Fixes L-3
                "show_in_rest" => true,
                "template_lock" => "all",
                'menu_icon' => TLGB_DIR_URL . 'assets/images/timeline.png',
                "template" => [["tlgb/b-timeline-block"]],
                'show_in_menu' => true,
            ]);
            
        }
    
        public function tlgb_timelineBlockManageColumns($defaults){
            unset($defaults['date']);
            $defaults['shortcode'] = __( 'ShortCode', 'timeline-block' );
            $defaults['date'] = __( 'Date', 'timeline-block' );
            return $defaults;
        }
    
        public function tlgb_timelineBlockManageCustomColumns($column_name, $post_ID){
            if ($column_name == 'shortcode') {
                echo '<div class="tlgbAdminShortcode" id="tlgbAdminShortcode-' . esc_attr($post_ID) . '" data-shortcode="[timeline_block id=' . esc_attr($post_ID) . ']">
                        <input value="[timeline_block id=' . esc_attr($post_ID) . ']" data-post-id="' . esc_attr($post_ID) . '" readonly>
                        <span class="tooltip">' . esc_html__( 'Copy To Clipboard', 'timeline-block' ) . '</span>
                      </div>';
            }
        }
    }
}