<?php
/**
 * Plugin Name:  WordPress Migration Plugin - Transferito
 * Plugin URI:   https://transferito.com/
 * Description:  Quickly transfer a WordPress site and database to another server.
 * Version:      10.6.1
 * Author:       Transferito
 * Author URI:   https://transferito.com/
 * License:      GPL2
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 */

require_once( ABSPATH . 'wp-includes/pluggable.php');

/**
 * Autoloader & Helper functions
 */
require_once('core/TransferitoAutoLoader.php');
require_once('core/helperFunctions.php');
require_once(plugin_dir_path( __FILE__ ) . 'vendor/autoload.php');


define( 'TRANSFERITO_PATH',            plugin_dir_path( __FILE__ ) );
define( 'TRANSFERITO_ABSPATH',         substr(ABSPATH, 0, -1) . DIRECTORY_SEPARATOR );
define( 'TRANSFERITO_UPLOAD_PATH',     TRANSFERITO_ABSPATH . 'transferito' );
define( 'TRANSFERITO_UPLOAD_URL',      site_url() . '/transferito' );
define( 'TRANSFERITO_ASSET_URL',       plugin_dir_url( __FILE__ ) . 'src/Views/Assets/' );
define( 'TRANSFERITO_CHUNK_SIZE',      (5 * 1024 * 1024) );
define( 'TRANSFERITO_VERSION',         '10.6.1' );
define( 'TRANSFERITO_MAX_ALLOWED',     (250 * 1024 * 1024) );
define( 'TRANSFERITO_ZIP_LIMIT',       (32 * 1024 * 1024) );
define( 'TRANSFERITO_DB_LIMIT',        (0.95 * 1024 * 1024) );
define( 'TRANSFERITO_ZIP_FILE_LIMIT',  (1.99 * 1024 * 1024 * 1024) );
define( 'TRANSFERITO_AWS_SECRET',      '10Jb1pE0toVDaEiheNvILVlYtqHG5M5bZUp523Tg' );
define( 'TRANSFERITO_AWS_ACCESS',      'AKIAXB3AHCOC5QFNW2NW' );
define( 'TRANSFERITO_AWS_BUCKET',      'transferito-uploads' );
define( 'TRANSFERITO_AWS_BASE_URL',    'https://transferito-uploads.s3.eu-west-2.amazonaws.com/' );

/**
 * Setup the plugin
 */
$settingSetup = new \Transferito\Models\Settings\Setup();
$transferController = new \Transferito\Controllers\Transfer();
