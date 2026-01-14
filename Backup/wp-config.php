<?php
//Begin Really Simple Security session cookie settings
@ini_set('session.cookie_httponly', true);
@ini_set('session.cookie_secure', true);
@ini_set('session.use_only_cookies', true);
//END Really Simple Security cookie settings
//Begin Really Simple Security key
define('RSSSL_KEY', 'VeEJfZtxEnSggQHXHdWJJwK9qG0mG760nspyoo5f7QGkXvNqXAJoFLtvUleiiNe7');
//END Really Simple Security key

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
define( 'DB_NAME', 'dputr_wp_database' );

/** MySQL database username */
define( 'DB_USER', 'dputr_dputr' );

/** MySQL database password */
define( 'DB_PASSWORD', 'W@Oj8R!{kVVN' );

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
define( 'AUTH_KEY',         'IP$4wGhee1Q*B2GJ*Qi!<uyTwC&a]=eKdQEmV(=G/WB{+T$QPNDz^%_rl&lyyIc$' );
define( 'SECURE_AUTH_KEY',  '8/9:GKuVPCD%v.E3${Yn#oj[0vvlRUH(,*yrj*Gju2j,z+0%EDJrgiii^6r~~ nT' );
define( 'LOGGED_IN_KEY',    '|b^u_5~J6L`jYU/>gFKhl(<CRMr%{Ew1%:XJKtKJpN WMy9rI S2DD2NHN&tpmw=' );
define( 'NONCE_KEY',        '2v<Tsn5/7J q<RCUdSK~Jx%uSB_f[9]03`1jI0>x^Ae5Ym }nKgBgj<WlniBk<`t' );
define( 'AUTH_SALT',        ';:FX~IL5lfp8{1M~&K<o}m);+/n,_Mp0.B#qgZPdz<_eG&g0m|%|OhS^nS{1;vY?' );
define( 'SECURE_AUTH_SALT', '33qJk(bW~7M?&BD&pU]?^+(mTzY5C=fF?ZhyGJVJ+qCEq -E2#leMV*m((L1~yGh' );
define( 'LOGGED_IN_SALT',   '^2_s?UC)h7XX%H-Ai(Kd]m7.o]eP4]D3[hG$f/?*)%+1KQX]*h7o!l)M_JcInt!x' );
define( 'NONCE_SALT',       'oIBU#=<NlOV #~[43]?(Ch``r;{5SY.4Za&,P)L)%mF_:IE>O3s7:DSn}$7Az?KL' );

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
$table_prefix = 'wp_';

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

define('WP_HOME', 'https://dputr.tasikmalayakota.go.id/');
define('WP_SITEURL', 'https://dputr.tasikmalayakota.go.id/');

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
