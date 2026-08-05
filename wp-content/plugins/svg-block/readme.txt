=== SVG Block ===
Contributors:      Mr2P
Tags:              block, SVG, image, icon, button
Requires PHP:      7.1
Requires at least: 6.5
Tested up to:      7.0
Stable tag:        1.2.4
License:           GPL-3.0
License URI:       https://www.gnu.org/licenses/gpl-3.0.html
Donate link:       https://boldblocks.net?utm_source=wp.org&utm_campaign=readme&utm_medium=link&utm_content=SVG+Block+Donate

Display an SVG image as a block, which can be used for displaying images, icons, dividers, buttons

== Description ==

This SVG block allows you to display SVG images as inline HTML markup. You can either choose an icon from the icon library with more than 3000 icons or you can upload or input your custom SVG images directly in the block's setting.
It also allows you to upload SVG images to the WordPress media library, and load them into the icon library. Only SVG files that have XML declaration at the top like `<?xml version="1.0" encoding="utf-8"?>` can be uploaded to the WordPress media library.

=== Key Features ===

* Accessibility ready with 'img' role, automatically generates title and description from settings.
* Automatically sanitize SVG markup to make it safe and lightweight.
* Include almost all settings to customize the SVG image.
* Include a collection of common non-rectangular dividers.
* An icon library included icons from "Bootstrap Icons", "Ionicons", "Dashicons" and new "WordPress Icons".
* Allow uploading SVG images to the WordPress media library
* Automatically load SVG images from the media library into the icon library

=== Video tutorials ===

How to create an icon with custom styles using the icon library:

[youtube https://www.youtube.com/watch?v=WJZXLyMXK0c]

How to create a non-rectangular background section:

[youtube https://www.youtube.com/watch?v=nVs4WzKFa7s]

How to create icon buttons:

[youtube https://youtu.be/NJkJipoDT4g]

Please take a look at [these custom block patterns](https://boldpatterns.net/keywords/svg?utm_source=wp.org&utm_campaign=readme&utm_medium=link&utm_content=SVG+Block) that use this block to see how it can be applied to real-world sites.

If this plugin is useful for you, please do a quick review and [rate it](https://wordpress.org/support/plugin/svg-block/reviews/#new-post) on WordPress.org to help us spread the word. I would very much appreciate it.

Please check out my other plugins if you're interested:

- **[Content Blocks Builder](https://wordpress.org/plugins/content-blocks-builder)** - This plugin turns the Block Editor into a powerful page builder by allowing you to create blocks, variations, and patterns directly in the Block Editor without needing a code editor.
- **[Meta Field Block](https://wordpress.org/plugins/display-a-meta-field-as-block)** - A block to display custom fields as blocks on the front end. It supports custom fields for posts, terms, users, and setting fields. It can also be used in the Query Loop block.
- **[Icon separator](https://wordpress.org/plugins/icon-separator)** - A tiny block just like the core/separator block but with the ability to add an icon.
- **[Breadcrumb Block](https://wordpress.org/plugins/breadcrumb-block)** - A simple breadcrumb trail block that supports JSON-LD structured data and is compatible with WooCommerce.
- **[Block Enhancements](https://wordpress.org/plugins/block-enhancements)** - Adds practical features to blocks like icons, box shadows, transforms, etc.
- **[Counting Number Block](https://wordpress.org/plugins/counting-number-block)** - A block to display numbers with a counting effect
- **[Better YouTube Embed Block](https://wordpress.org/plugins/better-youtube-embed-block)** - A block to solve the performance issue with embedded YouTube videos. It can also embed multiple videos and playlists.

The plugin is developed using @wordpress/create-block.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress


== Frequently Asked Questions ==

= What problem does this plugin solve? =

It helps to input inline SVG images as blocks easily and safely. You can use SVGs from the icon library or upload your own. It also supports uploading SVG images to the media library and displaying them with this block.

= When should we use this plugin? =

- When you need to quickly input some icons. It bundles with more than 3000 icons from some popular icons library such as "WordPress", "Dashicons", "Ionicons", and "Bootstrap icons".
- Or you need to use an SVG image as a divider, or separator. It also bundles with some common non-rectangular dividers like tilts, curves, triangles...
- You can also use it as an image block but for SVG format only like images from undraw.co.
- You can also use it as a button with an icon, and it can be nested inside the Query Loop with the 'Link to post' enabled.

= Why needs this plugin? =

It's super easy to use. It's accessibility-ready. It comes with lots of additional features like responsive width and height, responsive spacing (padding and margin), responsive justify alignment, flip, revert, responsive border, border-radius, box-shadow builder...

= Who needs this plugin? =

Anyone can use this plugin.

== Screenshots ==

1. Create an arrow background section

2. Add an icon and add styling to it

3. Create a curved background section

4. Use the responsive height feature

5. Use the SVG block as the image block

6. Block's settings

7. Block's placeholder

== Changelog ==

= 1.2.4 =
*Release Date - 07 May 2026*

* Improved - Updated enshrined/svg-sanitize to the latest version
* Improved - Updated inspector control style

= 1.2.3 =
*Release Date - 19 November 2025*

* Fixed - Missing default breakpoint values that caused responsive height styles to stop working.

= 1.2.2 =
*Release Date - 15 November 2025*

* Fixed - Responsive height style was not working
* Added - A new hook `apply_filters( 'boldblocks_svg_block_limit_svgs', 500 )` allows users to load more SVGs icons from the media library

= 1.2.1 =
*Release Date - 27 August 2025*

* Fixed - Enqueued dynamic styles for classic themes

= 1.2.0 =
*Release Date - 18 August 2025*

* Improved – Inline styles are now rendered as dynamic styles for cleaner markup
* Improved – Revamped the inspector settings
* Improved – Added support for negative margins

= 1.1.25 =
*Release Date - 14 November 2024*

* Added    - Add typography support feature when using the block as a button
* Security - Sanitize the SVG files upload via REST API
* Updated  - Required PHP 7.1

= 1.1.24 =
*Release Date - 24 October 2024*

* Updated  - SDK to implement minor UI changes and remove deprecated code
* Improved - Replaced classnames with clsx
* Updated  - Tested compatibility with WP 6.7 and set minimum requirement to WP 6.5

= 1.1.23 =
*Release Date - 03 July 2024*

* Improved - Refactor code to make the placeholder look good when installing the block from the inserter
* Improved - Adjust vertical spacing style for some inspector controls
* Added    - Support clientNavigation interactivity

= 1.1.22 =
*Release Date - 30 May 2024*

* Updated  - Update the unique ID for the SVG on the server
* Refactor - Vertical spacing
* Improved - Adjust the styling for the replacement SVG dropdown in the contentOnly mode

= 1.1.21 =
*Release Date - 22 May 2024*

* Improved - The SVG URL input UI component
* Updated  - Put the shadow panel inside the Border panel
* Improved - Allow inputting alpha value for colors
* Fixed    - Fix some small styling issues

= 1.1.20 =
*Release Date - 28 April 2024*

* Improved - Uploading SVGs: sanitize and allow only the administrator can upload SVG

= 1.1.19 =
*Release Date - 19 April 2024*

* Added   - Allow inputting SVG data, URL, button text on the content only locking
* Updated - Refactor code

= 1.1.18 =
*Release Date - 01 April 2024*

* Added   - Allow the ability to exclude the icon library from the icon popup
* Updated - Update SDK
* Updated - Icon Library

= 1.1.17 =
*Release Date - 31 October 2023*

* Added   - Allow uploading SVG images to the WordPress media library and load those images into its icon library
* Updated - Update icons from the latest version of third-party providers
* Updated - Update SDK

= 1.1.16 =
*Release Date - 22 September 2023*

* Added   - New setting named `linkToPost`. The block now can be used in a Query Loop and acts like a link/button with an icon that links to the post
* Updated - Update 'Requires at least' to 6.3 for the new HTML API, and new default size controls
* Fixed   - Change border from BorderBoxControl to BorderControl

= 1.1.15 =
*Release Date - 08 September 2023*

* FIX - Generate empty variables for margin

= 1.1.14 =
*Release Date - 03 September 2023*

* DEV - Use the default control for border, spacing settings
* DEV - Disable toggle off for width, icon width, gap between icon and text

= 1.1.13 =
*Release Date - 09 August 2023*

* DEV - Update new style for the icon library popup in WP 6.3
* DEV - Refactor code

= 1.1.12 =
*Release Date - 28 June 2023*

* DEV - Use ToolsPanel for inspector controls
* DEV - Improve performance

= 1.1.11 =
*Release Date - 11 March 2023*

* DEV - Update icon library
* DEV - Add more clear help texts for the title and the description
* DEV - Refactor for WP 6.2

= 1.1.10 =
*Release Date - 09 February 2023*

* DEV - Add better support for the 'Use as button' feature

= 1.1.9 =
*Release Date - 03 February 2023*

* DEV - Add SVGO GUI tool to the help text
* DEV - Update SDK

= 1.1.8 =
*Release Date - 27 October 2022*

* DEV - Allow clear color for the ColorGradientControl

= 1.1.7 =
*Release Date - 04 October 2022*

* FIX - placeholder's style is not loading in the site editor

= 1.1.6 =
*Release Date - 03 October 2022*

* FIX - Focus on the search box on the first load

= 1.1.5 =
*Release Date - 29 September 2022*

* REFACTOR - Redesign the placeholder for the block
* DEV - Add divider icons to the icon library
* REFACTOR - Using a data store for the icon library
* FIX - Remove empty style variables and add deprecated for them

= 1.1.4 =
*Release Date - 18 September 2022*

* FIX - Compatibility issue with the Gutenberg plugin
* DEV - Refactor for localization

= 1.1.3 =
*Release Date - 31 August 2022*

* DEV - Add stack context

= 1.1.2 =
*Release Date - 30 August 2022*

* DEV - Change the default value for box-shadow
* FIX - Reset icon lists by clicking on the reset search button

= 1.1.1 =
*Release Date - 27 July 2022*

* FIX - The block makes the page in 'dirty' state

= 1.1.0 =
*Release Date - 26 July 2022*

* DEV - Allow the ability to turn the block into a button with text & icon
* DEV - Add 'auto' value to the options of width and height
* DEV - Double click on the icon on the modal to insert the icon

= 1.0.13 =
*Release Date - 01 July 2022*

* DEV - Add box-sizing as border-box by default

= 1.0.12 =
*Release Date - 30 Jun 2022*

* FIX - Convert inline style to style object

= 1.0.11 =
*Release Date - 19 Jun 2022*

* DEV - Update SDK
* DEV - Refactor icon library modal
* DEV - Allow uploading SVG files

= 1.0.10 =
*Release Date - 12 May 2022*

* REFACTOR Update sdk

= 1.0.9 =
*Release Date - 30 April 2022*

* FIX - Missing color slug

= 1.0.8 =
*Release Date - 30 April 2022*

* DEV - Upgrade color format

= 1.0.7 =
*Release Date - 28 April 2022*

* DEV - Add title to block registration in JS

= 1.0.6 =
*Release Date - 21 April 2022*

* DEV - Optimize code for performance

= 1.0.5 =
*Release Date - 19 April 2022*

* REFACTOR - Move the PanelColorGradientSettings out of PanelBody

= 1.0.4 =
*Release Date - 15 April 2022*

* DEV - Icon library modal: focus the search box on open, press enter to insert icon

= 1.0.3 =
*Release Date - 12 April 2022*

* FIX - Remove old cached icons due to storage limitation issue.

= 1.0.2 =
*Release Date - 10 April 2022*

* DEV - Update icons pack

= 1.0.1 =
*Release Date - 07 April 2022*

* FIX - Fix typo issue

= 1.0.0 =
*Release Date - 05 April 2022*
