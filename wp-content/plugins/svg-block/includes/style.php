<?php
/**
 * The Style handler
 *
 * @package   SVGBlock
 * @author    Phi Phan <mrphipv@gmail.com>
 * @copyright Copyright (c) 2025, Phi Phan
 */

namespace SVGBlock;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( Style::class ) ) :
	/**
	 * The controller class for style handler.
	 */
	class Style {
		/**
		 * Cache the breakpoints
		 *
		 * @var array
		 */
		private $breakpoints = [];

		/**
		 * Style handle
		 *
		 * @var string
		 */
		private $style_handle = 'boldblocks-svg-block-style';

		/**
		 * Plugin instance
		 *
		 * @var Style
		 */
		private static $instance;

		/**
		 * A dummy constructor
		 */
		private function __construct() {}

		/**
		 * Initialize the instance.
		 *
		 * @return Style
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new Style();
			}

			return self::$instance;
		}

		/**
		 * Run main hooks
		 *
		 * @return void
		 */
		public function run() {
			// Handle output.
			add_filter( 'render_block_boldblocks/svg-block', [ $this, 'svg_block_render_block' ], 10, 3 );

			// Enqueue responsive style.
			add_action( 'enqueue_block_assets', [ $this, 'enqueue_dynamic_style' ] );

			// Get block style.
			add_filter( 'render_block_boldblocks/svg-block', [ $this, 'render_block_style' ], 10, 2 );
		}

		/**
		 * Render the block on the server.
		 *
		 * @param string   $block_content
		 * @param array    $block
		 * @param WP_Block $block_instance
		 * @return string
		 */
		public function svg_block_render_block( $block_content, $block, $block_instance ) {
			$attrs        = $block['attrs'] ?? [];
			$link_to_post = $attrs['linkToPost'] ?? false;
			$title        = $attrs['title'] ?? '';
			$description  = $attrs['description'] ?? '';

			if ( ! $link_to_post && ! $title && ! $description ) {
				return $block_content;
			}

			$block_reader = new \WP_HTML_Tag_Processor( $block_content );
			if ( $link_to_post ) {
				if ( isset( $block_instance->context['postId'] ) ) {
					// Get post_id from the context first.
					$post_id = $block_instance->context['postId'];
				} else {
					// Fallback to the current post id.
					$post_id = get_queried_object_id();
				}

				$post_link = get_permalink( $post_id );
				if ( $post_link ) {
					if ( $block_reader->next_tag( 'a' ) ) {
						$block_reader->set_attribute( 'href', $post_link );
					}
				}
			}

			// Give the SVG an unique id.
			if ( $title || $description ) {
				if ( $block_reader->next_tag( 'svg' ) ) {
					$id = \uniqid();
					$block_reader->set_bookmark( 'svg' );
					$block_reader->set_attribute( 'id', "svg_block_{$id}" );
					$block_reader->set_attribute( 'role', 'img' );

					if ( $title ) {
						$block_reader->set_attribute( 'aria-labelledby', "svg_block_{$id}_title" );
						if ( $block_reader->next_tag( 'title' ) ) {
							$block_reader->set_attribute( 'id', "svg_block_{$id}_title" );

							$block_reader->seek( 'svg' );
						}
					}

					if ( $description ) {
						$block_reader->set_attribute( 'aria-describedby', "svg_block_{$id}_desc" );
						if ( $block_reader->next_tag( 'desc' ) ) {
							$block_reader->set_attribute( 'id', "svg_block_{$id}_desc" );
						}
					}

					$block_reader->release_bookmark( 'svg' );
				}
			}

			return $block_reader->get_updated_html();
		}

		/**
		 * Enqueue repsonsive style
		 *
		 * @return void
		 */
		public function enqueue_dynamic_style() {
			wp_add_inline_style( $this->style_handle, $this->get_responsive_styles() );
		}

		/**
		 * Enqueue dynamic style for classic themes
		 *
		 * @param string $style
		 * @return void
		 */
		private function enqueue_block_style_for_classic_themes( $style ) {
			// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			wp_register_style( $this->style_handle . '_classic', '' );
			wp_add_inline_style( $this->style_handle . '_classic', $style );
			wp_enqueue_style( $this->style_handle . '_classic' );
		}

		/**
		 * Build responsive styles
		 *
		 * @return string
		 */
		private function get_responsive_styles() {
			$style = '';

			$breakpoints   = $this->get_breakpoints();
			$md_breakpoint = absint( $breakpoints['md']['breakpoint'] ?? 768 );
			$lg_breakpoint = absint( $breakpoints['lg']['breakpoint'] ?? 1024 );
			$md_start      = "@media(min-width:{$md_breakpoint}px){";
			$lg_start      = "@media(min-width:{$lg_breakpoint}px){";
			$end           = '}';

			// Margin.
			$margin_style  = '.sm-svg-margin-top{margin-top:var(--svg--margin-top) !important;}.sm-svg-margin-right{margin-right:var(--svg--margin-right) !important;}.sm-svg-margin-bottom{margin-bottom:var(--svg--margin-bottom) !important;}.sm-svg-margin-left{margin-left:var(--svg--margin-left) !important;}';
			$margin_style .= "{$md_start}.md-svg-margin-top{margin-top:var(--svg--margin-top) !important;}.md-svg-margin-right{margin-right:var(--svg--margin-right) !important;}.md-svg-margin-bottom{margin-bottom:var(--svg--margin-bottom) !important;}.md-svg-margin-left{margin-left:var(--svg--margin-left) !important;}{$end}";
			$margin_style .= "{$lg_start}.lg-svg-margin-top{margin-top:var(--svg--margin-top) !important;}.lg-svg-margin-right{margin-right:var(--svg--margin-right) !important;}.lg-svg-margin-bottom{margin-bottom:var(--svg--margin-bottom) !important;}.lg-svg-margin-left{margin-left:var(--svg--margin-left) !important;}{$end}";
			$style        .= $margin_style;

			// Justify alignment.
			$justify_alignment_style  = '.sm-svg-justify-alignment{display: flex;justify-content:var(--svg--justify-alignment) !important;}';
			$justify_alignment_style .= "{$md_start}.md-svg-justify-alignment{display: flex;justify-content:var(--svg--justify-alignment) !important;}{$end}";
			$justify_alignment_style .= "{$lg_start}.lg-svg-justify-alignment{display: flex;justify-content:var(--svg--justify-alignment) !important;}{$end}";
			$style                   .= $justify_alignment_style;

			// Inner node.
			// Width.
			$width_style  = '.sm-svg-width{width:var(--svg--width) !important;}';
			$width_style .= "{$md_start}.md-svg-width{width:var(--svg--width) !important;}{$end}";
			$width_style .= "{$lg_start}.lg-svg-width{width:var(--svg--width) !important;}{$end}";
			$style       .= $width_style;

			// Height.
			$height_style  = '.sm-svg-height{height:var(--svg--height) !important;}';
			$height_style .= "{$md_start}.md-svg-height{height:var(--svg--height) !important;}{$end}";
			$height_style .= "{$lg_start}.lg-svg-height{height:var(--svg--height) !important;}{$end}";
			$style        .= $height_style;

			// Padding.
			$padding_style  = '.sm-svg-padding-top{padding-top:var(--svg--padding-top) !important;}.sm-svg-padding-right{padding-right:var(--svg--padding-right) !important;}.sm-svg-padding-bottom{padding-bottom:var(--svg--padding-bottom) !important;}.sm-svg-padding-left{padding-left:var(--svg--padding-left) !important;}';
			$padding_style .= "{$md_start}.md-svg-padding-top{padding-top:var(--svg--padding-top) !important;}.md-svg-padding-right{padding-right:var(--svg--padding-right) !important;}.md-svg-padding-bottom{padding-bottom:var(--svg--padding-bottom) !important;}.md-svg-padding-left{padding-left:var(--svg--padding-left) !important;}{$end}";
			$padding_style .= "{$lg_start}.lg-svg-padding-top{padding-top:var(--svg--padding-top) !important;}.lg-svg-padding-right{padding-right:var(--svg--padding-right) !important;}.lg-svg-padding-bottom{padding-bottom:var(--svg--padding-bottom) !important;}.lg-svg-padding-left{padding-left:var(--svg--padding-left) !important;}{$end}";
			$style         .= $padding_style;

			// Border.
			$border_style  = '.sm-svg-border{border:var(--svg--border) !important;}';
			$border_style .= "{$md_start}.md-svg-border{border:var(--svg--border) !important;}{$end}";
			$border_style .= "{$lg_start}.lg-svg-border{border:var(--svg--border) !important;}{$end}";
			$style        .= $border_style;

			// Border radius.
			$radius_style  = '.sm-svg-border-radius{border-radius: var(--svg--border-radius) !important;}';
			$radius_style .= "{$md_start}.md-svg-border-radius{border-radius: var(--svg--border-radius) !important;}{$end}";
			$radius_style .= "{$lg_start}.lg-svg-border-radius{border-radius: var(--svg--border-radius) !important;}{$end}";
			$style        .= $radius_style;

			return $style;
		}

		/**
		 * Render style
		 *
		 * @param string $block_content
		 * @param array  $block
		 * @return string
		 */
		public function render_block_style( $block_content, $block ) {
			// Buil selector.
			$selector = $this->generate_selector();

			// Get custom style.
			$block_styles = $this->get_block_style( $block, $selector );
			if ( empty( $block_styles['style'] ?? '' ) ) {
				// There is no style, but we have class-features.
				if ( ! empty( $block_styles['classes'] ) ) {
					$block_content = $this->add_class_to_block( $block_content, implode( ' ', $block_styles['classes'] ?? [] ) );
				}

				if ( ! empty( $block_styles['inner_classes'] ) ) {
					$block_content = $this->add_class_to_block( $block_content, implode( ' ', $block_styles['inner_classes'] ?? [] ), true );
				}

				return $block_content;
			}

			// Add style to the frontend.
			wp_add_inline_style( $this->style_handle, $block_styles['style'] );

			// Enqueue style for classic themes.
			if ( ! wp_is_block_theme() ) {
				$this->enqueue_block_style_for_classic_themes( $block_styles['style'] );
			}

			// Add classes to block wrapper element.
			$block_content = $this->add_class_to_block( $block_content, implode( ' ', $block_styles['classes'] ?? [] ) );

			// Add classes to the inner element.
			$block_content = $this->add_class_to_block( $block_content, implode( ' ', $block_styles['inner_classes'] ?? [] ), true );

			return $block_content;
		}

		/**
		 * Get block dynamic style
		 *
		 * @param array  $block
		 * @param string $selector
		 * @return string
		 */
		private function get_block_style( $block, $selector ) {
			$style = '';

			$class_selector     = '.' . $selector;
			$class_sub_selector = $class_selector . ' > *';

			$style_array            = [];
			$responsive_style_array = [];

			$classes       = [ $selector ];
			$inner_classes = [];

			// Build settings.
			$settings = array_merge( $block['attrs'], $block['attrs']['boldblocks'] );

			// Get responsive settings.
			$breakpoints = $this->get_breakpoints();

			// Reponsive features.
			$features = [
				'margin'           => [
					'func_build_responsive_style' => [ $this, 'build_spacing_style' ],
					'group'                       => 'spacing',
					'func_args'                   => [ 'var' => 'margin' ],
					'parent'                      => true,
				],
				'justifyAlignment' => [
					'func_build_responsive_style' => [ $this, 'build_justify_alignment_style' ],
					'parent'                      => true,
				],
				'zIndex'           => [
					'func_build_style' => [ $this, 'build_zindex_style' ],
					'parent'           => true,
				],
				'width'            => [
					'func_build_responsive_style' => [ $this, 'build_size_style' ],
					'func_args'                   => [ 'var' => 'width' ],
				],
				'height'           => [
					'func_build_responsive_style' => [ $this, 'build_size_style' ],
					'func_args'                   => [ 'var' => 'height' ],
				],
				'padding'          => [
					'func_build_responsive_style' => [ $this, 'build_spacing_style' ],
					'group'                       => 'spacing',
					'func_args'                   => [ 'var' => 'padding' ],
				],
				'border'           => [
					'func_build_responsive_style' => [ $this, 'build_border_style' ],
				],
				'borderRadius'     => [
					'func_build_responsive_style' => [ $this, 'build_border_radius_style' ],
				],
				'fillColor'        => [
					'func_build_style' => [ $this, 'build_color_style' ],
					'func_args'        => [ 'var' => '--svg--fill-color' ],
				],
				'strokeColor'      => [
					'func_build_style' => [ $this, 'build_color_style' ],
					'func_args'        => [ 'var' => '--svg--stroke-color' ],
				],
				'backgroundColor'  => [
					'func_build_style' => [ $this, 'build_color_style' ],
					'func_args'        => [ 'var' => '--svg--background-color' ],
				],
				'shadows'          => [
					'func_build_style' => [ $this, 'build_shadow_style' ],
				],
			];

			// Button style.
			if ( ! empty( $settings['useAsButton'] ) ) {
				$features['textColor'] = [
					'func_build_style' => [ $this, 'build_color_style' ],
					'func_args'        => [ 'var' => '--svg--text-color' ],
				];
				$features['svgWidth']  = [
					'func_build_responsive_style' => [ $this, 'build_size_style' ],
					'func_args'                   => [
						'var'     => 'svg-width',
						'classes' => 'svg-svg-width',
					],
				];
				$features['gap']       = [
					'func_build_responsive_style' => [ $this, 'build_size_style' ],
					'func_args'                   => [
						'var'     => 'gap',
						'classes' => 'svg-gap',
					],
				];
			}

			foreach ( $features as $feature => $feature_args ) {
				$group_name   = $feature_args['group'] ?? null;
				$setting_name = $feature_args['setting_name'] ?? $feature;
				if ( $this->is_valid_value( $group_name ) ) {
					$setting_value = $settings[ $group_name ][ $setting_name ] ?? null;
				} else {
					$setting_value = $settings[ $setting_name ] ?? null;
				}

				if ( ! $this->is_valid_value( $setting_value ) ) {
					continue;
				}

				$args = array_merge(
					[
						'settings'      => $settings,
						'setting_value' => $setting_value,
						'feature'       => $feature,
						'selector'      => ! empty( $feature_args['parent'] ) ? $class_selector : $class_sub_selector,
						'breakpoints'   => $breakpoints,
						'block'         => $block,
					],
					$feature_args
				);

				if ( \is_callable( $args['func_build_style'] ?? '' ) ) {
					$this->build_style( array_merge( $args, $feature_args['func_args'] ?? [], [ 'value' => $setting_value ] ), $style_array, $classes, $inner_classes );
				}

				if ( \is_callable( $args['func_build_responsive_style'] ?? '' ) && $this->is_valid_responsive_value( $args['setting_value'] ) ) {
					$this->build_responsive_style( array_merge( $args, $feature_args['func_args'] ?? [] ), $responsive_style_array, $classes, $inner_classes );
				}
			}

			if ( $style_array ) {
				foreach ( $style_array as $selector_type => $value ) {
					if ( count( $value ) > 0 ) {
						$style .= $selector_type . '{' . implode( '', $value ) . '}';
					}
				}
			}

			if ( $responsive_style_array ) {
				foreach ( $responsive_style_array as $responsive_style ) {
					$style .= $responsive_style;
				}
			}

			return [
				'style'         => $style,
				'classes'       => $classes,
				'inner_classes' => $inner_classes,
			];
		}

		/**
		 * Build custom style
		 *
		 * @param array $args
		 * @param array &$style_array
		 * @param array &$classes
		 * @param array &$inner_classes
		 * @return string
		 */
		private function build_style( $args, &$style_array, &$classes, &$inner_classes ) {
			$return_value     = false;
			$func_build_style = $args['func_build_style'] ?? '';

			if ( ! \is_callable( $func_build_style ) ) {
				return $return_value;
			}

			$value = $args['value'] ?? null;
			if ( ! $this->is_valid_value( $value ) ) {
				return $return_value;
			}

			// Build style.
			$feature_styles = $func_build_style( $args, $style_array, $classes, $inner_classes );

			if ( ! $feature_styles ) {
				return $return_value;
			}

			$keys   = [];
			$style  = '';
			$parent = $args['parent'] ?? false;
			foreach ( $feature_styles as $attr_key => $attr_value ) {
				$style .= "{$attr_key}:{$attr_value};";

				if ( ! in_array( $attr_key, $keys, true ) ) {
					$keys[] = $attr_key;
					if ( $parent ) {
						$classes[] = str_replace( '--svg--', 'svg-', $attr_key );
					} else {
						$inner_classes[] = str_replace( '--svg--', 'svg-', $attr_key );
					}
				}
			}

			// Add style to the style array.
			$this->add_to_style_array( $style, $style_array, $args );

			return $style ? $style : $return_value;
		}

		/**
		 * Add style to the style array
		 *
		 * @param string $style
		 * @param array  &$style_array
		 * @param array  $args
		 * @return void
		 */
		private function add_to_style_array( $style, &$style_array, $args ) {
			if ( $style ) {
				$selector = $args['selector'];

				if ( ! isset( $style_array[ $selector ] ) ) {
					$style_array[ $selector ] = [];
				}

				$style_array[ $selector ][] = $style;
			}
		}

		/**
		 * Build style for z-index
		 *
		 * @param array $args
		 * @param array &$style_array
		 * @param array &$classes
		 * @return array
		 */
		private function build_zindex_style( $args, &$style_array, &$classes ) {
			$styles = [];
			$value  = $args['value'] ?? null;
			if ( $this->is_valid_value( $value ) ) {
				$styles['--svg--zindex'] = $value;
			}

			return $styles;
		}

		/**
		 * Build style for a color property
		 *
		 * @param array $args
		 * @param array &$style_array
		 * @param array &$classes
		 * @param array &$inner_classes
		 * @return array
		 */
		private function build_color_style( $args, &$style_array, &$classes, &$inner_classes ) {
			$styles = [];
			$value  = $args['value'] ?? null;
			if ( $this->is_valid_value( $value ) ) {
				$color_value = $this->get_css_color_value( $value );
				if ( $color_value ) {
					$styles[ $args['var'] ] = $color_value;
				}
			}

			return $styles;
		}

		/**
		 * Build style for a shadow property
		 *
		 * @param array $args
		 * @param array &$style_array
		 * @param array &$classes
		 * @param array &$inner_classes
		 * @return array
		 */
		private function build_shadow_style( $args, &$style_array, &$classes, &$inner_classes ) {
			$styles = [];
			$value  = $args['value'] ?? null;
			if ( $value && is_array( $value ) ) {
				$shadows = array_map(
					function ( $shadow ) {
						$shadow_styles = [
							'inset'  => $shadow['inset'] ?? false ? 'inset' : '',
							'x'      => $shadow['x'] ?? '0px',
							'y'      => $shadow['y'] ?? '0px',
							'blur'   => $shadow['blur'] ?? '0px',
							'spread' => $shadow['spread'] ?? '0px',
						];

						$color = $this->get_css_color_value( $shadow['color'] ?? '' );
						if ( $color ) {
							$shadow_styles[] = $color;
						}

						return \implode( ' ', array_filter( $shadow_styles ) );
					},
					$value
				);

				$styles['--svg--shadow'] = implode( ',', $shadows );
			}

			return $styles;
		}

		/**
		 * Build responsive style
		 *
		 * @param array $args
		 * @param array &$responsive_style_array
		 * @param array &$classes
		 * @param array &$inner_classes
		 * @return string
		 */
		private function build_responsive_style( $args, &$responsive_style_array, &$classes, &$inner_classes ) {
			$func_build_responsive_style = $args['func_build_responsive_style'] ?? '';
			if ( ! \is_callable( $func_build_responsive_style ) ) {
				return '';
			}

			$setting_value = $args['setting_value'] ?? [];
			if ( ! $this->is_valid_responsive_value( $setting_value ) ) {
				return '';
			}

			$breakpoints = $args['breakpoints'];
			$selector    = $args['selector'];

			$responsive_styles = [];
			foreach ( $setting_value as $breakpoint => $value_by_breakpoint ) {
				$value = null;
				if ( $this->is_valid_value( $value_by_breakpoint['value'] ?? null ) ) {
					$value = $value_by_breakpoint['value'];
				} elseif ( isset( $value_by_breakpoint['inherit'] ) && is_string( $value_by_breakpoint['inherit'] ) ) {
					$value = $setting_value[ $value_by_breakpoint['inherit'] ]['value'] ?? null;
				}

				if ( $this->is_valid_value( $value ) ) {
					$style_by_breakpoint = $func_build_responsive_style(
						array_merge(
							$args,
							[
								'value'      => $value,
								'breakpoint' => $breakpoint,
							]
						)
					);
					if ( $style_by_breakpoint ) {
						$responsive_styles[ $breakpoint ] = $style_by_breakpoint;
					}
				}
			}

			$parent                = $args['parent'] ?? false;
			$style                 = '';
			$last_responsive_style = '';
			$fixed_classes         = $args['classes'] ?? '';
			foreach ( $responsive_styles as $breakpoint => $style_by_breakpoint ) {
				$keys             = [];
				$prop_classes     = [];
				$responsive_style = '';
				foreach ( $style_by_breakpoint as $attr_key => $attr_value ) {
					$responsive_style .= "{$attr_key}:{$attr_value};";

					if ( ! $fixed_classes && ! in_array( $attr_key, $keys, true ) ) {
						$keys[]         = $attr_key;
						$prop_classes[] = str_replace( '--svg--', 'svg-', $attr_key );
					}
				}

				if ( $responsive_style !== $last_responsive_style ) {
					$style_with_selector = "{$selector}{{$responsive_style}}";
					$min_query           = $breakpoints[ $breakpoint ]['mediaQuery'] ?? '';
					if ( $min_query ) {
						$style_with_selector = \str_replace( '##CONTENT##', $style_with_selector, $min_query );
					}

					// Add classes to block.
					foreach ( $prop_classes as $prop_class ) {
						if ( $parent ) {
							$classes[] = $breakpoint . '-' . $prop_class;
						} else {
							$inner_classes[] = $breakpoint . '-' . $prop_class;
						}
					}

					$style                .= $style_with_selector;
					$last_responsive_style = $responsive_style;
				}
			}

			// Add style to the style_array.
			if ( $style ) {
				$responsive_style_array[] = $style;

				if ( $fixed_classes ) {
					if ( $parent ) {
						$classes[] = $fixed_classes;
					} else {
						$inner_classes[] = $fixed_classes;
					}
				}
			}

			return $style;
		}

		/**
		 * Build style for spacing properties
		 *
		 * @param array $args
		 * @return string
		 */
		private function build_spacing_style( $args ) {
			$style_array = [];
			$value       = $args['value'];
			if ( is_array( $value ) ) {
				$property = $args['var'] ?? 'margin';
				if ( $this->is_valid_string_value( $value['top'] ?? null ) ) {
					$top_style = $this->get_spacing_value( $value['top'] );
					if ( $this->is_valid_value( $top_style ) ) {
						$style_array[ '--svg--' . $property . '-top' ] = $top_style;
					}
				}

				if ( $this->is_valid_string_value( $value['right'] ?? null ) ) {
					$right_style = $this->get_spacing_value( $value['right'] );
					if ( $this->is_valid_value( $right_style ) ) {
						$style_array[ '--svg--' . $property . '-right' ] = $right_style;
					}
				}

				if ( $this->is_valid_string_value( $value['bottom'] ?? null ) ) {
					$bottom_style = $this->get_spacing_value( $value['bottom'] );
					if ( $this->is_valid_value( $bottom_style ) ) {
						$style_array[ '--svg--' . $property . '-bottom' ] = $bottom_style;
					}
				}

				if ( $this->is_valid_string_value( $value['left'] ?? null ) ) {
					$left_style = $this->get_spacing_value( $value['left'] );
					if ( $this->is_valid_value( $left_style ) ) {
						$style_array[ '--svg--' . $property . '-left' ] = $left_style;
					}
				}
			}

			return $style_array;
		}

		/**
		 * Build style for size properties
		 *
		 * @param array $args
		 * @return string
		 */
		private function build_size_style( $args ) {
			$style_array = [];
			$value       = $args['value'] ?? [];
			if ( $this->is_valid_value( $value['value'] ?? null ) ) {
				$property = $args['var'] ?? '';

				$style_array[ '--svg--' . $property ] = $value['value'];
			}

			return $style_array;
		}

		/**
		 * Build style for justify alignment
		 *
		 * @param array $args
		 * @return string
		 */
		private function build_justify_alignment_style( $args ) {
			$style_array = [];
			$value       = $args['value'] ?? null;
			if ( $this->is_valid_value( $value ) ) {
				$style_array['--svg--justify-alignment'] = $value;
			}

			return $style_array;
		}

		/**
		 * Build style for border
		 *
		 * @param array $args
		 * @return string
		 */
		private function build_border_style( $args ) {
			$style_array = [];
			$value       = $args['value'] ?? null;
			if ( $this->is_valid_value( $value ) ) {
				$borders = [];
				if ( $this->is_valid_value( $value['width'] ?? null ) ) {
					$borders[] = $value['width'];
				}
				if ( $this->is_valid_value( $value['style'] ?? null ) ) {
					$borders[] = $value['style'];
				}
				if ( $this->is_valid_value( $value['color'] ?? null ) ) {
					$borders[] = $this->get_css_color_value( $value['color'] );
				}

				$border_style = implode( ' ', $borders );
				if ( $border_style ) {
					$style_array['--svg--border'] = $border_style;
				}
			}

			return $style_array;
		}

		/**
		 * Build style for border radius
		 *
		 * @param array $args
		 * @return string
		 */
		private function build_border_radius_style( $args ) {
			$style_array = [];
			$value       = $args['value'] ?? null;
			if ( $this->is_valid_value( $value ) ) {
				$top_left     = $value['top-left'] ?? null;
				$top_right    = $value['top-right'] ?? null;
				$bottom_right = $value['bottom-right'] ?? null;
				$bottom_left  = $value['bottom-left'] ?? null;

				if ( $top_left || $top_right || $bottom_right || $bottom_left ) {
					$top_left     = ! $this->is_valid_value( $top_left ) ? 0 : $top_left;
					$top_right    = ! $this->is_valid_value( $top_right ) ? 0 : $top_right;
					$bottom_right = ! $this->is_valid_value( $bottom_right ) ? 0 : $bottom_right;
					$bottom_left  = ! $this->is_valid_value( $bottom_left ) ? 0 : $bottom_left;

					if ( $top_left === $top_right && $top_right === $bottom_right && $bottom_right === $bottom_left ) {
						// All values are equal.
						$style_array['--svg--border-radius'] = $top_left;
					} elseif ( $top_left === $bottom_right && $top_right === $bottom_left ) {
						// Two values are equal.
						$style_array['--svg--border-radius'] = "{$top_left} {$top_right}";
					} else {
						// All values are different.
						$style_array['--svg--border-radius'] = "{$top_left} {$top_right} {$bottom_right} {$bottom_left}";
					}
				}
			}

			return $style_array;
		}

		/**
		 * Get breakpoints
		 *
		 * @return array
		 */
		private function get_breakpoints() {
			if ( ! $this->breakpoints ) {
				$breakpoints = [
					'sm' => [
						'breakpoint' => '576px',
						'mediaQuery' => '',
					],
					'md' => [
						'breakpoint' => '768px',
						'mediaQuery' => '@media (min-width: 768px){##CONTENT##}',
					],
					'lg' => [
						'breakpoint' =>  '1024px',
						'mediaQuery' => '@media (min-width: 1024px){##CONTENT##}',
					],
				];
				if ( class_exists( \BoldBlocks\ContentBlocksBuilder::class ) ) {
					$cbb_breakpoints = get_option( 'cbb_breakpoints' );
					if ( $cbb_breakpoints ) {
						$cbb_breakpoints = array_column( $cbb_breakpoints, null, 'prefix' );
						$sm_breakpoint   = $cbb_breakpoints['sm']['breakpoint'] ?? 576;
						$md_breakpoint   = $cbb_breakpoints['md']['breakpoint'] ?? 768;
						$lg_breakpoint   = $cbb_breakpoints['lg']['breakpoint'] ?? 1024;

						$breakpoints = [
							'sm' => [
								'breakpoint' => $sm_breakpoint . 'px',
								'mediaQuery' => '',
							],
							'md' => [
								'breakpoint' => $md_breakpoint . 'px',
								'mediaQuery' => '@media (min-width: ' . $md_breakpoint . 'px){##CONTENT##}',
							],
							'lg' => [
								'breakpoint' => $lg_breakpoint . 'px',
								'mediaQuery' => '@media (min-width: ' . $lg_breakpoint . 'px){##CONTENT##}',
							],
						];
					}
				}

				// Cache the result.
				$this->breakpoints = apply_filters( 'boldblocks_svg_block_get_breakpoints', $breakpoints );
			}

			return $this->breakpoints;
		}

		/**
		 * Generate block selector.
		 *
		 * @param string $block_name
		 * @param string $prefix
		 * @return string
		 */
		private function generate_selector() {
			// Generate an unique value.
			// $id = \uniqid();

			// Buil selector.
			return wp_unique_id( 'svg-b-' );
		}

		/**
		 * Add CSS class to block wrapper
		 *
		 * @param string  $block_content
		 * @param string  $classes
		 * @param boolean $is_inner
		 * @return string
		 */
		private function add_class_to_block( $block_content, $classes, $is_inner = false ) {
			$tags          = new \WP_HTML_Tag_Processor( $block_content );
			$selector_args = $is_inner ? [ 'class_name' => 'wp-block-boldblocks-svg-block__inner' ] : [];
			if ( $tags->next_tag( $selector_args ) ) {
				$tags->add_class( $classes );
			}

			return $tags->get_updated_html();
		}

		/**
		 * Detect a value is valid
		 *
		 * @param mixed $value
		 * @return boolean
		 */
		private function is_valid_value( $value ) {
			return isset( $value ) && $value !== '';
		}

		/**
		 * Detect a value is string
		 *
		 * @param mixed $value
		 * @return boolean
		 */
		private function is_valid_string_value( $value ) {
			return $this->is_valid_value( $value ) && is_string( $value );
		}

		/**
		 * Get value for spacing var
		 *
		 * @param string $value
		 * @return string
		 */
		private function get_spacing_value( $value ) {
			if ( ! $this->is_valid_value( $value ) ) {
				return '';
			}

			if ( strpos( $value, 'var:preset|spacing|' ) !== false ) {
				preg_match( '/var:preset\|spacing\|(.+)/', $value, $slug );

				if ( ! $slug ) {
					return $value;
				}

				return "var(--wp--preset--spacing--{$slug[1]})";
			}

			return $value;
		}

		/**
		 * Get CSS value for a color object.
		 *
		 * @param array $color_array
		 * @return string
		 */
		private function get_css_color_value( $color_array ) {
			$value = '';

			if ( is_array( $color_array ) && ! empty( $color_array ) ) {
				if ( $color_array['gradientSlug'] ?? false ) {
					$value = "var(--wp--preset--gradient--{$color_array['gradientSlug']}, {$color_array['gradientValue']})";
				} elseif ( $color_array['gradientValue'] ?? false ) {
					$value = $color_array['gradientValue'];
				} elseif ( $color_array['slug'] ?? false ) {
					$value = "var(--wp--preset--color--{$color_array['slug']}, {$color_array['value']})";
				} elseif ( $color_array['value'] ?? false ) {
					$value = $color_array['value'];
				}
			}

			return $value;
		}

		/**
		 * Detect a responsive value is valid
		 *
		 * @param mixed $value
		 * @return boolean
		 */
		private function is_valid_responsive_value( $value ) {
			return is_array( $value ) && ( ! empty( $value['lg']['value'] ) || ! empty( $value['md']['value'] ) || ! empty( $value['sm']['value'] ) );
		}
	}

	// Kick start.
	Style::get_instance()->run();
endif;
