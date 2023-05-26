<?php
/**
 * Loads_Base_Configuration trait file
 *
 * @package Mantle
 */

namespace Mantle\Application\Concerns;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidFileException;
use Mantle\Application\Application;
use Mantle\Config\Repository;
use Mantle\Framework\Console\Kernel;
use Mantle\Support\Environment;

/**
 * Load a base configuration for Mantle to operate.
 *
 * @mixin \Mantle\Application\Application
 */
trait Loads_Base_Configuration {
	/**
	 * Load the base configuration for the application.
	 */
	public function load_base_configuration() {
		$cached = $this->get_cached_config_path();

		// Check if a cached configuration file exists. If found, load it.
		if ( is_file( $cached ) ) {
			$items = require $cached;

			$loaded_from_cache = true;
		} else {
			$items = $this->get_default_configuration();
		}

		$config = new Repository( (array) $items );

		// Set the global config alias.
		$this->instance( 'config', $config );

		// Load configuration files if the config hasn't been loaded from cache.
		if ( isset( $loaded_from_cache ) ) {
			$config->set( 'config.loaded_from_cache', true );
		}
	}

	/**
	 * Retrieve the default configuration for Mantle.
	 *
	 * @return array
	 */
	protected function get_default_configuration(): array {
		return [
			'app'        => [
				'debug'     => defined( 'WP_DEBUG' ) && WP_DEBUG,
				'providers' => [
					// Framework Providers (mirrors config/app.php).
					Mantle\Filesystem\Filesystem_Service_Provider::class,
					Mantle\Database\Factory_Service_Provider::class,
					Mantle\Framework\Providers\Error_Service_Provider::class,
					Mantle\Database\Model_Service_Provider::class,
					Mantle\Queue\Queue_Service_Provider::class,
					Mantle\Query_Monitor\Query_Monitor_Service_Provider::class,
					Mantle\New_Relic\New_Relic_Service_Provider::class,
					Mantle\Database\Pagination\Paginator_Service_Provider::class,
					Mantle\Cache\Cache_Service_Provider::class,

					// Featherkit Providers.
					Mantle\Application\App_Service_Provider::class,
					Mantle\Assets\Asset_Service_Provider::class,
					Mantle\Framework\Providers\Event_Service_Provider::class,
					Mantle\Framework\Providers\Route_Service_Provider::class,
				],
				'namespace' => 'App',
			],
			'cache'      => [
				'default' => 'wordpress',
				'stores'  => [
					'wordpress' => [
						'driver' => 'wordpress',
					],
					'array'     => [
						'driver' => 'array',
					],
				],
			],
			'filesystem' => [
				'default' => 'local',
				'disks'   => [
					'local' => [
						'driver' => 'local',
					],
				],
			],
			'logging'    => [
				'default'  => 'stack',
				'channels' => [
					'stack'     => [
						'driver'   => 'stack',
						'channels' => [ 'error_log' ],
					],

					'error_log' => [
						'driver' => 'error_log',
						'level'  => 'error',
					],
				],
			],
			'queue'      => [
				'default'    => 'wordpress',
				'batch_size' => 100,
				'wordpress'  => [
					'delay' => 0,
				],
			],
			'view'       => [],
		];
	}
}
