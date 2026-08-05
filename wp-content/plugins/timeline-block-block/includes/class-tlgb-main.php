<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if(!class_exists('TLGBTimeline')){
  class TLGBTimeline {
    public function __construct() {
      add_action('plugins_loaded', [__CLASS__, 'load_dependencies']);
      add_shortcode('timeline_block', [__CLASS__, 'tlgb_shortcode']);
    }
  
    public static function load_dependencies() {
      require_once TLGB_DIR_PATH . 'class-tlgb-block.php';
      require_once TLGB_DIR_PATH . 'includes/class-tlgb-cpt.php';
      include_once TLGB_DIR_PATH . 'includes/class-tlgb-admin.php';
      include_once TLGB_DIR_PATH . 'includes/class-tlgb-options.php';

      new TLGBPlugin();
      new TLGBCpt();
      new TLGBAdminMenu();
      new TLGBOptions();
    } 
  
    public static function tlgb_shortcode($atts) {
      $atts = shortcode_atts([
					'id' => 0,
				],$atts, 'timeline_block');

			$post_id = absint( $atts['id'] );

			if ( ! $post_id ) {
				return '<p>' . esc_html__( 'Invalid timeline ID.', 'timeline-block' ) . '</p>';
			}

			$post = get_post( $post_id );

			if ( ! $post || 'timeline_block' !== $post->post_type ) {
				return '<p>' . esc_html__( 'Timeline not found.', 'timeline-block' ) . '</p>';
			}

			$is_published = 'publish' === $post->post_status;
			$is_private_allowed = 'private' === $post->post_status && current_user_can( 'read_post', $post_id );

			if ( ! $is_published && ! $is_private_allowed ) {
				return '<p>' . esc_html__( 'Timeline not found.', 'timeline-block' ) . '</p>';
			}

			$blocks = parse_blocks( $post->post_content );

			if ( empty( $blocks ) ) {
				return '<p>' . esc_html__( 'No timeline content found.', 'timeline-block' ) . '</p>';
			}

			foreach ( $blocks as $block ) {
				if ( isset( $block['blockName'] ) && $block['blockName'] === 'tlgb/b-timeline-block' ) {
					return render_block( $block );
				}
			}

			return '<p>' . esc_html__( 'Timeline block not found in this post.', 'timeline-block' ) . '</p>';
    } 
  }
}