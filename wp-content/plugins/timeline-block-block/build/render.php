<?php
if (!defined('ABSPATH')) exit;
$id = wp_unique_id('BTimelineBlock-');

if ( ! empty( $attributes['timelines'] ) && is_array( $attributes['timelines'] ) ) {
    $tlgb_svg_allowed = [
        'svg'    => [
            'xmlns'   => true,
            'viewbox' => true,
            'viewBox' => true,
            'width'   => true,
            'height'  => true,
            'fill'    => true,
            'class'   => true,
            'id'      => true,
        ],
        'path'   => [
            'd'      => true,
            'fill'   => true,
            'stroke' => true,
            'class'  => true,
            'id'     => true,
        ],
        'g'      => [
            'fill'  => true,
            'class' => true,
            'id'    => true,
        ],
        'circle' => [
            'cx'    => true,
            'cy'    => true,
            'r'     => true,
            'fill'  => true,
            'class' => true,
            'id'    => true,
        ],
        'rect'   => [
            'x'      => true,
            'y'      => true,
            'width'  => true,
            'height' => true,
            'fill'   => true,
            'rx'     => true,
            'class'  => true,
            'id'     => true,
        ],
    ];
    foreach ( $attributes['timelines'] as &$tlgb_item ) {
        if ( isset( $tlgb_item['label'] ) )       { $tlgb_item['label']       = wp_kses_post( $tlgb_item['label'] ); }
        if ( isset( $tlgb_item['description'] ) ) { $tlgb_item['description'] = wp_kses_post( $tlgb_item['description'] ); }
        if ( isset( $tlgb_item['icon'] ) )        { $tlgb_item['icon']        = wp_kses( $tlgb_item['icon'], $tlgb_svg_allowed ); }
    }
    unset( $tlgb_item );
}

?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> id='<?php echo esc_attr($id); ?>'
  data-attributes='<?php echo esc_attr(wp_json_encode($attributes)); ?>'></div>