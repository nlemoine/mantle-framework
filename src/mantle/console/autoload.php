<?php
/**
 * Autoloading for Console
 *
 * @package Mantle
 */

// Prevent loading if WP_CLI is already loaded or if the environment variable is
// set to disabled it.
if (
	class_exists( 'WP_CLI' )
	|| getenv( 'MANTLE_DISABLE_WP_CLI_MOCK' )
	|| ( defined( 'MANTLE_DISABLE_WP_CLI_MOCK' ) && MANTLE_DISABLE_WP_CLI_MOCK )
) {
	return;
}

defined( 'WP_CLI' ) || define( 'WP_CLI', true );
defined( 'MANTLE_WP_CLI' ) || define( 'MANTLE_WP_CLI', true );

require_once __DIR__ . '/wp-cli/class-wp-cli-shim.php';

/**
 * Create a WP_CLI class that will be used in place of the real thing.
 */
class WP_CLI extends \Mantle\Console\WP_CLI\WP_CLI_Shim {}
