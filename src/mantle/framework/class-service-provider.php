<?php
/**
 * Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework;

use Mantle\Framework\Console\Command;
use Psr\Log\{LoggerAwareInterface, LoggerAwareTrait};

/**
 * Application Service Provider
 */
abstract class Service_Provider implements LoggerAwareInterface {
	use LoggerAwareTrait;

	/**
	 * The application instance.
	 *
	 * @var Application
	 */
	protected $app;

	/**
	 * Commands to register.
	 * Register commands through `Service_Provider::add_command()`.
	 *
	 * @var \Mantle\Framework\Console\Command[]
	 */
	protected $commands;

	/**
	 * Create a new service provider instance.
	 *
	 * @param Application $app Application Instance.
	 */
	public function __construct( Application $app ) {
		$this->app = $app;
	}

	/**
	 * Bootstrap services.
	 */
	public function boot() {
		$this->setLogger( $this->app['log']->get_default_logger() );
	}

	/**
	 * Register a wp-cli command.
	 *
	 * @param Command|string $command Command instance or class name to register.
	 * @return Service_Provider
	 */
	public function add_command( $command ): Service_Provider {
		if ( $command instanceof Command ) {
			$this->commands[] = $command;
		} else {
			$this->commands[] = $this->app->make( $command );
		}

		return $this;
	}

	/**
	 * Register the wp-cli commands for a service provider.
	 *
	 * @return Service_Provider
	 */
	public function register_commands(): Service_Provider {
		foreach ( (array) $this->commands as $command ) {
			$command->register();
		}

		return $this;
	}
}
