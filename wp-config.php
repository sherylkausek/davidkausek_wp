<?php
 // Added by SpeedyCache

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'kausekco_wp459' );

/** Database username */
define( 'DB_USER', 'kausekco_wp459' );

/** Database password */
define( 'DB_PASSWORD', 'Bd95p01@S(' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'sxt5licbqrvaaf9c7zosx7nxnb0h2ncwmboikrqoilx3vkxka7zbcbsm39co4f8m' );
define( 'SECURE_AUTH_KEY',  '4n55ewcwhjlktlajav8tfs12q8xck8zrycj4ownw7koh8jbcy3l1czsczey5xlce' );
define( 'LOGGED_IN_KEY',    'a8gelcdieyexwycgronacqbqkqfeyanlug6lhjzrtwplbzzaatrsa3z077urz2cj' );
define( 'NONCE_KEY',        'xwcblnnxaqkmcswtniy3lka224pkepkyhifcdjv7rxcs6vjvsm6c4byttdwp51ph' );
define( 'AUTH_SALT',        'sielw2vqf7ibvtwwhwt9dlutubiffoo3uifkbcjeiroeo7qvzn2xa1oy6kbp1byu' );
define( 'SECURE_AUTH_SALT', 'putzcahzpxgqwpbjumnfpkeolm17ojjxjbkqyk6j3sos1r6jlx6ikz56gsezvhw7' );
define( 'LOGGED_IN_SALT',   '7yhfupfodkinfduknsobaqqdojm1mut97fna1p8xlhajveygfbhk2od5ogd0hetq' );
define( 'NONCE_SALT',       'm2ry0jnysvkpaotlalms8ahuztgqcyjxk6e6e9jmgkcqon1iqaetspcnterzslov' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wpk3_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */

define( 'WP_HOME', 'https://davidkausek.com' );
define( 'WP_SITEURL', 'https://davidkausek.com' );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
