<?php
/**
 * Boot_Manager class file
 *
 * @package Mantle
 */

namespace Mantle\Framework;

use Mantle\Application\Application;
use Mantle\Contracts;
use Mantle\Contracts\Framework\Boot_Manager as Contract;

/**
 * Boot Manager
 *
 * Used to instantiate the application and load the framework given the current
 * context.
 */
class Boot_Manager implements Contract {
	/**
	 * Current instance of the manager.
	 *
	 * @var Boot_Manager|null
	 */
	protected static ?Boot_Manager $instance = null;

	/**
	 * Retrieve the instance of the manager.
	 *
	 * @param Contracts\Application|null $app Application instance.
	 * @return Boot_Manager
	 */
	public static function get_instance( ?Contracts\Application $app = null ): Boot_Manager {
		if ( is_null( static::$instance ) ) {
			static::$instance = new static( $app );
		}

		return static::$instance;
	}

	/**
	 * Set the instance of the manager.
	 *
	 * @param Boot_Manager $instance Instance of the manager.
	 * @return void
	 */
	public static function set_instance( Boot_Manager $instance ): void {
		static::$instance = $instance;
	}

	/**
	 * Constructor.
	 *
	 * @param Contracts\Application|null $app Application instance.
	 */
	public function __construct( protected ?Contracts\Application $app = null ) {
		static::set_instance( $this );
	}

	/**
	 * Boot the application given the current context.
	 *
	 * @return void
	 */
	public function boot(): void {
		$this->boot_application();

		$this->app['events']->dispatch( 'mantle_boot_manager_booted', $this->app );
	}

	/**
	 * Boot the application and attach the relevant container classes.
	 *
	 * @return void
	 */
	protected function boot_application(): void {
		if ( is_null( $this->app ) ) {
			$this->app = new Application();
		}

		// Bail if the application is already booted.
		if ( $this->app->is_booted() ) {
			return;
		}

		/**
		 * Fired before the application is booted.
		 *
		 * @param \Mantle\Contracts\Application $app Application instance.
		 */
		do_action( 'mantle_boot_manager_before_boot', $this->app );

		$this->app->singleton_if(
			Contracts\Console\Kernel::class,
			\Mantle\Framework\Console\Kernel::class,
		);

		$this->app->singleton_if(
			Contracts\Http\Kernel::class,
			\Mantle\Framework\Http\Kernel::class,
		);

		$this->app->singleton_if(
			Contracts\Exceptions\Handler::class,
			\Mantle\Framework\Exceptions\Handler::class,
		);
	}

	/**
	 * Retrieve the application instance.
	 *
	 * @return Contracts\Application|null
	 */
	public function application(): ?Contracts\Application {
		return $this->app;
	}
}
