<?php
/**
 * The icon library
 *
 * @package   SVGBlock
 * @author    Phi Phan <mrphipv@gmail.com>
 * @copyright Copyright (c) 2022, Phi Phan
 */

namespace SVGBlock;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( is_readable( SVG_BLOCK_PATH . '/vendor/autoload.php' ) ) {
	require SVG_BLOCK_PATH . '/vendor/autoload.php';
}

/**
 * SVG allowed tags
 */
class SVGBlockAllowedTags extends \enshrined\svgSanitize\data\AllowedTags {
	/**
	 * Returns an array of tags
	 *
	 * @return array
	 */
	public static function getTags() {

		/**
		 * Tags that are allowed.
		 *
		 * @var array
		 */
		return apply_filters( 'svg_allowed_tags', parent::getTags() );
	}
}

/**
 * SVG allowed attributes
 */
class SVGBlockAllowedAttributes extends \enshrined\svgSanitize\data\AllowedAttributes {
	/**
	 * Returns an array of attributes
	 *
	 * @return array
	 */
	public static function getAttributes() {

		/**
		 * Attributes that are allowed.
		 *
		 * @var array
		 */
		return apply_filters( 'svg_allowed_attributes', parent::getAttributes() );
	}
}

if ( ! class_exists( IconLibrary::class ) ) :
	/**
	 * The controller class for icon library.
	 */
	class IconLibrary {
		/**
		 * Plugin instance
		 *
		 * @var IconLibrary
		 */
		private static $instance;

		/**
		 * The sanitizer
		 *
		 * @var \enshrined\svgSanitize\Sanitizer
		 */
		private $sanitizer;

		/**
		 * A dummy constructor
		 */
		private function __construct() {
			$this->sanitizer = new \enshrined\svgSanitize\Sanitizer();
		}

		/**
		 * Initialize the instance.
		 *
		 * @return IconLibrary
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new IconLibrary();
			}

			return self::$instance;
		}

		/**
		 * Run main hooks
		 *
		 * @return void
		 */
		public function run() {
			// Add rest api endpoint to query icon library.
			add_action( 'rest_api_init', [ $this, 'register_icon_library_endpoint' ] );

			// Allow uploading svg images to the media library.
			if ( apply_filters( 'boldblocks_svg_block_allow_upload_svg_image', true ) ) {
				// Allow SVG upload.
				add_filter( 'upload_mimes', [ $this, 'mime_types_support_svg' ] );

				// Handle upload via REST API.
				add_filter( 'wp_handle_sideload_prefilter', [ $this, 'handle_svg_upload' ] );

				// Handle SVG upload.
				add_filter( 'wp_handle_upload_prefilter', [ $this, 'handle_svg_upload' ] );

				// Display svg images.
				add_action( 'admin_head', [ $this, 'display_svg_thumb' ] );

				// Add metadata to svg images.
				add_filter( 'wp_update_attachment_metadata', [ $this, 'update_svg_metadata' ], 10, 2 );
			}
		}


		/**
		 * Build a custom endpoint to query icon library.
		 *
		 * @return void
		 */
		public function register_icon_library_endpoint() {
			register_rest_route(
				'svgblock/v1',
				'/getIconLibrary/',
				array(
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_icon_library' ],
					'permission_callback' => function () {
						return current_user_can( 'publish_posts' );
					},
				)
			);
		}

			/**
			 * Get icon library.
			 *
			 * @param WP_REST_Request $request The request object.
			 * @return void
			 */
		public function get_icon_library( $request ) {
			$icons = [];

			// Query svg images from the media library.
			$media_svg_images = $this->query_svg_images();

			if ( $media_svg_images ) {
				$icons = $media_svg_images;
			}

			if ( apply_filters( 'boldblocks_svg_block_load_icons_from_library', true ) ) {
				// icons file path.
				$icons_file = SVG_BLOCK_PATH . 'data/icon-library/icons.json';

				// Send the error if the icons file is not exists.
				if ( \file_exists( $icons_file ) ) {
					// Parse json.
					$icons_library = wp_json_file_decode( $icons_file, [ 'associative' => true ] );

					$icons = array_merge( $icons, $icons_library );
				}
			}

			wp_send_json(
				[
					'data'    => $icons,
					'success' => true,
				]
			);
		}

		/**
		 * Query SVG images from the library
		 *
		 * @return array
		 */
		private function query_svg_images() {
			$media_svgs = [];
			$images     = get_posts(
				[
					'post_type'      => 'attachment',
					'post_mime_type' => [ 'image/svg+xml' ],
					'post_status'    => 'any',
					'posts_per_page' => apply_filters( 'boldblocks_svg_block_limit_svgs', 500 ),
				]
			);

			if ( $images ) {
				foreach ( $images as $image ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
					$icon = file_get_contents( get_attached_file( $image->ID ) );
					if ( $icon ) {
						$media_svgs[] = [
							'name'       => $image->post_name,
							'title'      => $image->post_title,
							'icon'       => $icon,
							'categories' => [ 'Media Library' ],
							'provider'   => 'Media Library',
						];
					}
				}
			}

			return $media_svgs;
		}

		/**
		 * Add SVG mine types
		 *
		 * @param array $mimes
		 * @return array
		 */
		public function mime_types_support_svg( $mimes ) {
			if ( $this->current_user_can_upload_svg() ) {
				$mimes['svg'] = 'image/svg+xml';
			}

			return $mimes;
		}

		/**
		 * Handle SVG uplaod
		 *
		 * @param array $file An array of data for the uploaded file.
		 *
		 * @return mixed
		 */
		public function handle_svg_upload( $file ) {
			// Bail if there is no valid file path.
			if ( ! isset( $file['tmp_name'] ) ) {
				return $file;
			}

			$file_name   = isset( $file['name'] ) ? $file['name'] : '';
			$wp_filetype = wp_check_filetype_and_ext( $file['tmp_name'], $file_name );
			$type        = ! empty( $wp_filetype['type'] ) ? $wp_filetype['type'] : '';

			// Handle SVG file.
			if ( 'image/svg+xml' === $type ) {
				// Don't allow upload, if the current user cannot upload SVG.
				if ( ! $this->current_user_can_upload_svg() ) {
					$file['error'] = __(
						'Sorry, you are not allowed to upload SVG files.',
						'svg-block'
					);

					return $file;
				}

				if ( ! $this->sanitize( $file['tmp_name'] ) ) {
					$file['error'] = __(
						"Sorry, this file couldn't be sanitized so for security reasons wasn't uploaded.",
						'svg-block'
					);
				}
			}

			return $file;
		}

		/**
		 * Sanitize the SVG
		 *
		 * @param string $file Temp file path.
		 *
		 * @return bool|int
		 */
		private function sanitize( $file ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$dirty = file_get_contents( $file );

			// Is the SVG gzipped?
			$is_zipped = $this->is_gzipped( $dirty );

			// Decode it, if SVG is zipped.
			if ( $is_zipped ) {
				$dirty = gzdecode( $dirty );

				// Bail if decoding fails.
				if ( false === $dirty ) {
					return false;
				}
			}

			$this->sanitizer->setAllowedTags( new SVGBlockAllowedTags() );
			$this->sanitizer->setAllowedAttrs( new SVGBlockAllowedAttributes() );

			$clean = $this->sanitizer->sanitize( $dirty );

			if ( false === $clean ) {
				return false;
			}

			// If we were gzipped, we need to re-zip.
			if ( $is_zipped ) {
				$clean = gzencode( $clean );
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $file, $clean );

			return true;
		}

		/**
		 * Is gzipped?
		 *
		 * @param string $content
		 * @return boolean
		 */
		private function is_gzipped( $content ) {
			// phpcs:disable Generic.Strings.UnnecessaryStringConcat.Found
			if ( function_exists( 'mb_strpos' ) ) {
				return 0 === mb_strpos( $content, "\x1f" . "\x8b" . "\x08" );
			} else {
				return 0 === strpos( $content, "\x1f" . "\x8b" . "\x08" );
			}
			// phpcs:enable
		}

		/**
		 * Display SVG images
		 *
		 * @return void
		 */
		public function display_svg_thumb() {
			echo '<style>
				.wp-list-table .media-icon img[src$=".svg"], .editor-post-featured-image img[src$=".svg"] {
					width: 100% !important;
					height: auto !important;
				}
			</style>';
		}

		/**
		 * Generate width/height for uploaded SVG images
		 * https://css-tricks.com/snippets/wordpress/allow-svg-through-wordpress-media-uploader/#comment-1606112
		 *
		 * @param array $data metadata for uploaded image
		 * @param int   $id the attachment id
		 * @return void
		 */
		public function update_svg_metadata( $data, $id ) {
			// Filter makes sure that the post is an attachment.
			$attachment = get_post( $id );

			// The attachment mime_type.
			$mime_type = $attachment->post_mime_type;

			// If the attachment is an svg.
			if ( 'image/svg+xml' === $mime_type ) {
				// If the svg metadata are empty or the width is empty or the height is empty, then get the attributes from xml.
				if ( empty( $data ) || empty( $data['width'] ) || empty( $data['height'] ) ) {
					$xml = simplexml_load_file( get_attached_file( $id ) );
					if ( $xml ) {
						$attr          = $xml->attributes();
						$viewbox       = explode( ' ', $attr->viewBox );
						$data['width'] = isset( $attr->width ) && preg_match( '/\d+/', $attr->width, $value ) ? (int) $value[0] : ( count( $viewbox ) == 4 ? (int) $viewbox[2] : null );
						if ( intval( $data['width'] ) === 0 ) {
							$data['width'] = 1;
						}
						$data['height'] = isset( $attr->height ) && preg_match( '/\d+/', $attr->height, $value ) ? (int) $value[0] : ( count( $viewbox ) == 4 ? (int) $viewbox[3] : null );
						if ( intval( $data['height'] ) === 0 ) {
							$data['height'] = 1;
						}
					}
				}
			}

			return $data;
		}

		/**
		 * Check whether current user can upload SVG or not
		 *
		 * @return boolean
		 */
		private function current_user_can_upload_svg() {
			$upload_roles = apply_filters( 'svg_block_upload_roles', [ 'administrator' ] );

			$user               = wp_get_current_user();
			$current_user_roles = (array) $user->roles;

			return array_intersect( $upload_roles, $current_user_roles );
		}
	}

	// Kick start.
	IconLibrary::get_instance()->run();
endif;
