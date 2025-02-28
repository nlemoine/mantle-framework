<?php
/**
 * Application class file.
 *
 * @package Mantle
 */

namespace Mantle\Application;

use Mantle\Container\Container;
use Mantle\Contracts\Application as Application_Contract;
use Mantle\Contracts\Container as Container_Contract;
use Mantle\Contracts\Kernel as Kernel_Contract;
use Mantle\Contracts\Support\Isolated_Service_Provider;
use Mantle\Events\Event_Service_Provider;
use Mantle\Framework\Manifest\Model_Manifest;
use Mantle\Framework\Manifest\Package_Manifest;
use Mantle\Framework\Providers\Console_Service_Provider;
use Mantle\Framework\Providers\Routing_Service_Provider;
use Mantle\Log\Log_Service_Provider;
use Mantle\Support\Arr;
use Mantle\Support\Environment;
use Mantle\Support\Service_Provider;
use Mantle\View\View_Service_Provider;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function Mantle\Support\Helpers\collect;

/**
 * Mantle Application
 */
class Application extends Container implements Application_Contract {
	/**
	 * Base path of the application.
	 *
	 * @var string
	 */
	protected $base_path;

	/**
	 * Application path of the application.
	 *
	 * @var string
	 */
	protected $app_path;

	/**
	 * Bootstrap path of the application.
	 *
	 * @var string
	 */
	protected $bootstrap_path;

	/**
	 * Storage path of the application.
	 *
	 * @var string
	 */
	protected $storage_path;

	/**
	 * Root URL of the application.
	 *
	 * @var string
	 */
	protected $root_url;

	/**
	 * Indicates if the application has been bootstrapped before.
	 *
	 * @var bool
	 */
	protected $has_been_bootstrapped = false;

	/**
	 * Indicates if the application has "booted".
	 *
	 * @var bool
	 */
	protected $booted = false;

	/**
	 * The array of booting callbacks.
	 *
	 * @var callable[]
	 */
	protected $booting_callbacks = [];

	/**
	 * The array of booted callbacks.
	 *
	 * @var callable[]
	 */
	protected $booted_callbacks = [];

	/**
	 * The array of terminating callbacks.
	 *
	 * @var callable[]
	 */
	protected $terminating_callbacks = [];

	/**
	 * All of the registered service providers.
	 *
	 * @var Service_Provider[]
	 */
	protected $service_providers = [];

	/**
	 * Environment file name.
	 *
	 * @var string
	 */
	protected $environment_file = '.env';

	/**
	 * The custom environment path defined by the developer.
	 *
	 * @var string
	 */
	protected $environment_path;

	/**
	 * Storage of the overridden environment name.
	 *
	 * @var string
	 */
	protected $environment;

	/**
	 * Indicates if the application is running in the console.
	 *
	 * @var bool
	 */
	protected $is_running_in_console;

	/**
	 * Constructor.
	 *
	 * @param string $base_path Base path to set.
	 * @param string $root_url Root URL of the application.
	 */
	public function __construct( string $base_path = '', string $root_url = null ) {
		if ( empty( $base_path ) && defined( 'MANTLE_BASE_DIR' ) ) {
			$base_path = \MANTLE_BASE_DIR;
		}

		if ( ! $root_url ) {
			$root_url = function_exists( 'home_url' ) ? \home_url() : '/';
		}

		$this->set_base_path( $base_path );
		$this->set_root_url( $root_url );
		$this->register_base_bindings();
		$this->register_base_service_providers();
		$this->register_core_aliases();
	}

	/**
	 * Set the base path of the application.
	 *
	 * @param string $path Path to set.
	 * @return static
	 */
	public function set_base_path( string $path ) {
		$this->base_path = $path;

		$this->instance( 'path', $this->get_base_path() );
		$this->instance( 'path.bootstrap', $this->get_bootstrap_path() );
		$this->instance( 'path.storage', $this->get_storage_path() );

		return $this;
	}

	/**
	 * Getter for the base path.
	 *
	 * @param string $path Path to append.
	 * @return string
	 */
	public function get_base_path( string $path = '' ): string {
		return $this->base_path . ( $path ? '/' . $path : '' );
	}

	/**
	 * Get the path to the application "app" directory.
	 *
	 * @param string $path Path to append, optional.
	 * @return string
	 */
	public function get_app_path( string $path = '' ): string {
		$app_path = $this->app_path ?: $this->get_base_path( 'app' );

		return $app_path . ( $path ? DIRECTORY_SEPARATOR . $path : $path );
	}

	/**
	 * Set the application directory.
	 *
	 * @param string $path Path to use.
	 * @return static
	 */
	public function set_app_path( string $path ) {
		$this->app_path = $path;

		$this->instance( 'path', $path );

		return $this;
	}

	/**
	 * Getter for the bootstrap path.
	 *
	 * @param string $path Path to append.
	 * @return string
	 */
	public function get_bootstrap_path( string $path = '' ): string {
		return ( $this->bootstrap_path ?: $this->base_path . DIRECTORY_SEPARATOR . 'bootstrap' ) . $path;
	}

	/**
	 * Getter for the storage path.
	 *
	 * @param string $path Path to append.
	 * @return string
	 */
	public function get_storage_path( string $path = '' ): string {
		return ( $this->storage_path ?: $this->base_path . DIRECTORY_SEPARATOR . 'storage' ) . $path;
	}

	/**
	 * Set the root URL of the application.
	 *
	 * @param string $url Root URL to set.
	 */
	public function set_root_url( string $url ) {
		$this->root_url = $url;
	}

	/**
	 * Getter for the root URL.
	 * This would be the root URL to the WordPress installation.
	 *
	 * @param string $path Path to append.
	 * @return string
	 */
	public function get_root_url( string $path = '' ): string {
		return $this->root_url . ( $path ? '/' . $path : '' );
	}

	/**
	 * Get the cache folder root
	 * Folder that stores all compiled server-side assets for the application.
	 *
	 * @return string
	 */
	public function get_cache_path(): string {
		return $this->get_bootstrap_path( '/cache' );
	}

	/**
	 * Get the cached Composer packages path.
	 *
	 * Used to store all auto-loaded packages that are Composer dependencies.
	 *
	 * @return string
	 */
	public function get_cached_packages_path(): string {
		return $this->get_cache_path() . '/packages.php';
	}

	/**
	 * Get the cached model manifest path.
	 * Used to store all auto-registered models that are in the application.
	 *
	 * @return string
	 */
	public function get_cached_models_path(): string {
		return $this->get_cache_path() . '/models.php';
	}

	/**
	 * Determine if the application is cached.
	 *
	 * @return bool
	 */
	public function is_configuration_cached(): bool {
		return is_file( $this->get_cached_config_path() );
	}

	/**
	 * Retrieve the cached configuration path.
	 *
	 * @return string
	 */
	public function get_cached_config_path(): string {
		return $this->get_bootstrap_path() . '/' . Environment::get( 'APP_CONFIG_CACHE', 'cache/config.php' );
	}

	/**
	 * Determine if events are cached.
	 *
	 * @return bool
	 */
	public function is_events_cached(): bool {
		return is_file( $this->get_cached_events_path() );
	}

	/**
	 * Retrieve the cached configuration path.
	 *
	 * @return string
	 */
	public function get_cached_events_path(): string {
		return $this->get_bootstrap_path() . '/' . Environment::get( 'APP_EVENTS_CACHE', 'cache/events.php' );
	}

	/**
	 * Get the path to the application configuration files.
	 *
	 * @return string
	 */
	public function get_config_path(): string {
		return $this->base_path . '/config';
	}

	/**
	 * Determine if the application has been bootstrapped before.
	 *
	 * @return bool
	 */
	public function has_been_bootstrapped(): bool {
		return (bool) $this->has_been_bootstrapped;
	}

	/**
	 * Register the basic bindings into the container.
	 *
	 * @return void
	 */
	protected function register_base_bindings() {
		static::set_instance( $this );

		$this->instance( 'app', $this );
		$this->instance( Container::class, $this );
		$this->instance( Container_Contract::class, $this );
		$this->instance( static::class, $this );

		$this->singleton(
			Package_Manifest::class,
			fn( $app ) => new Package_Manifest( $this->get_base_path(), $this->get_cached_packages_path() ),
		);

		$this->singleton(
			Model_Manifest::class,
			fn ( $app ) => new Model_Manifest( $this->get_app_path(), $this->get_cached_models_path() ),
		);
	}

	/**
	 * Register the base service providers.
	 */
	protected function register_base_service_providers() {
		$this->register( Console_Service_Provider::class );
		$this->register( Event_Service_Provider::class );
		$this->register( Log_Service_Provider::class );
		$this->register( View_Service_Provider::class );
		$this->register( Routing_Service_Provider::class );
	}

	/**
	 * Register the core aliases.
	 */
	protected function register_core_aliases() {
		$core_aliases = [
			'app'           => [ static::class, \Mantle\Contracts\Application::class ],
			'config'        => [ \Mantle\Config\Repository::class, \Mantle\Contracts\Config\Repository::class ],
			'events'        => [ \Mantle\Events\Dispatcher::class, \Mantle\Contracts\Events\Dispatcher::class ],
			'files'         => [ \Mantle\Filesystem\Filesystem::class ],
			'filesystem'    => [ \Mantle\Filesystem\Filesystem_Manager::class, \Mantle\Contracts\Filesystem\Filesystem_Manager::class ],
			'log'           => [ \Mantle\Log\Log_Manager::class, \Psr\Log\LoggerInterface::class ],
			'queue'         => [ \Mantle\Queue\Queue_Manager::class, \Mantle\Contracts\Queue\Queue_Manager::class ],
			'redirect'      => [ \Mantle\Http\Routing\Redirector::class ],
			'request'       => [ \Mantle\Http\Request::class, \Symfony\Component\HttpFoundation\Request::class ],
			'router'        => [ \Mantle\Http\Routing\Router::class, \Mantle\Contracts\Http\Routing\Router::class ],
			'router.entity' => [ \Mantle\Http\Routing\Entity_Router::class, \Mantle\Contracts\Http\Routing\Entity_Router::class ],
			'url'           => [ \Mantle\Http\Routing\Url_Generator::class, \Mantle\Contracts\Http\Routing\Url_Generator::class ],
			'view.loader'   => [ \Mantle\Http\View\View_Finder::class, \Mantle\Contracts\Http\View\View_Finder::class ],
			'view'          => [ \Mantle\Http\View\Factory::class, \Mantle\Contracts\Http\View\Factory::class ],
		];

		foreach ( $core_aliases as $key => $aliases ) {
			foreach ( $aliases as $alias ) {
				$this->alias( $key, $alias );
			}
		}
	}

	/**
	 * Flush the container of all bindings and resolved instances.
	 */
	public function flush() {
		parent::flush();

		$this->booted_callbacks  = [];
		$this->booting_callbacks = [];
		$this->service_providers = [];
	}

	/**
	 * Run the given array of bootstrap classes.
	 *
	 * Bootstrap classes should implement `Mantle\Contracts\Bootstrapable`.
	 *
	 * @param string[]        $bootstrappers Class names of packages to boot.
	 * @param Kernel_Contract $kernel Kernel instance.
	 */
	public function bootstrap_with( array $bootstrappers, Kernel_Contract $kernel ) {
		$this->has_been_bootstrapped = true;

		foreach ( $bootstrappers as $bootstrapper ) {
			$this->make( $bootstrapper )->bootstrap( $this, $kernel );
		}
	}

	/**
	 * Register all of the configured providers.
	 */
	public function register_configured_providers() {
		// Get providers from the application config.
		$providers = collect( $this->make( 'config' )->get( 'app.providers', [] ) );

		// Include providers from the package manifest.
		$providers->push( ...$this->make( Package_Manifest::class )->providers() );

		// Only register service providers that implement Isolated_Service_Provider
		// when in isolation mode.
		if ( $this->is_running_in_console_isolation() ) {
			$providers = $providers->filter(
				fn ( string $provider ) => in_array(
					Isolated_Service_Provider::class,
					class_implements( $provider ),
					true,
				)
			);
		}

		$providers->each( [ $this, 'register' ] );
	}

	/**
	 * Get an instance of a service provider.
	 *
	 * @param string $name Provider class name.
	 * @return Service_Provider|null
	 */
	public function get_provider( string $name ): ?Service_Provider {
		$providers = Arr::where(
			$this->get_providers(),
			function( Service_Provider $provider ) use ( $name ) {
				return $provider instanceof $name;
			}
		);

		return array_shift( $providers );
	}

	/**
	 * Get all service providers.
	 *
	 * @return Service_Provider[]
	 */
	public function get_providers(): array {
		return $this->service_providers;
	}

	/**
	 * Register a Service Provider
	 *
	 * @param Service_Provider|string $provider Provider instance or class name to register.
	 * @return Application
	 */
	public function register( $provider ): Application {
		$provider_name = is_string( $provider ) ? $provider : get_class( $provider );

		if ( ! empty( $this->service_providers[ $provider_name ] ) ) {
			return $this;
		}

		if ( is_string( $provider ) ) {
			$provider = new $provider( $this );
		}

		if ( ! ( $provider instanceof Service_Provider ) ) {
			\wp_die( 'Provider is not instance of Service_Provider: ' . $provider_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		$provider->register();
		$this->service_providers[ $provider_name ] = $provider;
		return $this;
	}

	/**
	 * Determine if the application has booted.
	 *
	 * @return bool
	 */
	public function is_booted(): bool {
		return $this->booted;
	}

	/**
	 * Boot the application's service providers.
	 *
	 * @return static
	 */
	public function boot() {
		if ( $this->is_booted() ) {
			return $this;
		}

		// Fire the 'booting' callbacks.
		$this->fire_app_callbacks( $this->booting_callbacks );

		foreach ( $this->service_providers as $provider ) {
			$provider->boot_provider();
		}

		$this->booted = true;

		// Fire the 'booted' callbacks.
		$this->fire_app_callbacks( $this->booted_callbacks );

		return $this;
	}

	/**
	 * Set and retrieve the environment file name.
	 *
	 * @param string $file File name to set.
	 * @return string
	 */
	public function environment_file( string $file = null ): string {
		if ( $file ) {
			$this->environment_file = $file;
		}

		return $this->environment_file ?: '.env';
	}

	/**
	 * Set and retrieve the environment path to use.
	 *
	 * @param string $path Path to set, optional.
	 * @return string
	 */
	public function environment_path( string $path = null ): ?string {
		if ( $path ) {
			$this->environment_path = $path;
		}

		return $this->environment_path;
	}

	/**
	 * Get the Application's Environment
	 *
	 * @return string
	 */
	public function environment(): string {
		if ( ! empty( $this->environment ) ) {
			return $this->environment;
		}

		return Environment::get( 'ENV', function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : '' );
	}

	/**
	 * Check if the Application's Environment matches a list.
	 *
	 * @param string|array ...$environments Environments to check.
	 * @return bool
	 */
	public function is_environment( ...$environments ): bool {
		return in_array( $this->environment(), (array) $environments, true );
	}

	/**
	 * Get the application namespace.
	 *
	 * @return string
	 *
	 * @throws RuntimeException If the config is not set yet.
	 */
	public function get_namespace(): string {
		if ( ! isset( $this['config'] ) ) {
			throw new RuntimeException( 'Configurations not set yet.' );
		}

		return (string) $this['config']->get( 'app.namespace', 'App' );
	}

	/**
	 * Check if the application is running in the console.
	 *
	 * @return bool
	 */
	public function is_running_in_console(): bool {
		if ( $this->is_running_in_console_isolation() ) {
			return true;
		}

		if ( null === $this->is_running_in_console ) {
			$this->is_running_in_console = Environment::get( 'APP_RUNNING_IN_CONSOLE' ) || ( defined( 'WP_CLI' ) && WP_CLI && ! wp_doing_cron() );
		}

		return $this->is_running_in_console;
	}

	/**
	 * Check if the application is running in console isolation mode.
	 *
	 * @return bool
	 */
	public function is_running_in_console_isolation(): bool {
		return defined( 'MANTLE_ISOLATION_MODE' ) && MANTLE_ISOLATION_MODE;
	}

	/**
	 * Set the environment for the application.
	 *
	 * @param string $environment Environment to set.
	 * @return static
	 */
	public function set_environment( string $environment ) {
		$this->environment = $environment;
		return $this;
	}

	/**
	 * Throw an HttpException with the given data.
	 *
	 * @param int    $code HTTP status code.
	 * @param string $message Response message.
	 * @param array  $headers Response headers.
	 *
	 * @throws NotFoundHttpException Thrown on 404 error.
	 * @throws HttpException Thrown on other HTTP error.
	 */
	public function abort( int $code, string $message = '', array $headers = [] ) {
		if ( 404 === $code ) {
			throw new NotFoundHttpException( $message, null, 404, $headers );
		} else {
			throw new HttpException( $code, $message, null, $headers );
		}
	}

	/**
	 * Register a new boot listener.
	 *
	 * @param callable $callback Callback for the listener.
	 * @return static
	 */
	public function booting( callable $callback ): static {
		$this->booting_callbacks[] = $callback;
		return $this;
	}

	/**
	 * Register a new "booted" listener.
	 *
	 * @param callable $callback Callback for the listener.
	 * @return static
	 */
	public function booted( callable $callback ): static {
		$this->booted_callbacks[] = $callback;

		if ( $this->is_booted() ) {
			$this->fire_app_callbacks( [ $callback ] );
		}

		return $this;
	}

	/**
	 * Register a new terminating callback.
	 *
	 * @param callable $callback Callback for the listener.
	 * @return static
	 */
	public function terminating( callable $callback ): static {
		$this->terminating_callbacks[] = $callback;
		return $this;
	}

	/**
	 * Terminate the application.
	 *
	 * @return void
	 */
	public function terminate(): void {
		$this->fire_app_callbacks( $this->terminating_callbacks );
	}

	/**
	 * Call the booting callbacks for the application.
	 *
	 * @param callable[] $callbacks Callbacks to fire.
	 */
	protected function fire_app_callbacks( array $callbacks ) {
		foreach ( $callbacks as $callback ) {
			$callback( $this );
		}
	}
}
