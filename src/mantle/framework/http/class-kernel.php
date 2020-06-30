<?php
/**
 * Kernel class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Http;

use Exception;
use InvalidArgumentException;
use Mantle\Framework\Application;
use Mantle\Framework\Contracts\Http\Kernel as Kernel_Contract;
use Mantle\Framework\Contracts\Http\Routing\Router;
use Mantle\Framework\Contracts\Kernel as Core_Kernel_Contract;
use Mantle\Framework\Contracts\Providers\Route_Service_Provider as Route_Service_Provider_Contract;
use Mantle\Framework\Facade\Facade;
use Mantle\Framework\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Throwable;
use Mantle\Framework\Contracts\Exceptions\Handler as Exception_Handler;

/**
 * HTTP Kernel
 */
class Kernel implements Kernel_Contract, Core_Kernel_Contract {
	/**
	 * The application implementation.
	 *
	 * @var Application
	 */
	protected $app;

	/**
	 * Router instance.
	 *
	 * @var \Mantle\Framework\Http\Routing\Router
	 */
	protected $router;

	/**
	 * Request instance.
	 *
	 * @var Request
	 */
	protected $request;

	/**
	 * The bootstrap classes for the application.
	 *
	 * @var array
	 */
	protected $bootstrappers = [
		\Mantle\Framework\Bootstrap\Load_Configuration::class,
		\Mantle\Framework\Bootstrap\Register_Facades::class,
		\Mantle\Framework\Bootstrap\Register_Providers::class,
		\Mantle\Framework\Bootstrap\Boot_Providers::class,
	];

	/**
	 * The application's middleware stack.
	 *
	 * @var array
	 */
	protected $middleware = [];

	/**
	 * The application's route middleware groups.
	 *
	 * @var array
	 */
	protected $middleware_groups = [];

	/**
	 * The application's route middleware.
	 *
	 * @var array
	 */
	protected $route_middleware = [];

	/**
	 * Constructor.
	 *
	 * @param Application $app Application instance.
	 * @param Router      $router Router instance.
	 */
	public function __construct( Application $app, Router $router ) {
		$this->app    = $app;
		$this->router = $router;

		$this->sync_middleware_to_router();
	}

	/**
	 * Bootstrap the kernel and send the request through the router.
	 *
	 * @todo Add better error handling.
	 *
	 * @param Request $request Request instance.
	 */
	public function handle( Request $request ) {
		$this->request = $request;

		// Setup the Request Facade.
		$this->app->instance( 'request', $request );

		Facade::clear_resolved_instance( 'request' );

		try {
			$this->bootstrap();
		} catch ( Throwable $e ) {
			$this->report_exception( $e );

			$response = $this->render_exception( $request, $e );

			if ( $response instanceof Response ) {
				$response->send();
				exit;
			} else {
				\wp_die( 'Error booting HTTP Kernel: ' . $e->getMessage() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}

		\add_action( 'wp_loaded', [ $this, 'handle_request' ] );
	}

	/**
	 * Handle an incoming HTTP request.
	 *
	 * Send the request through the HTTP Router and optional send the response. Called on
	 * the 'wp_loaded' filter.
	 */
	public function handle_request() {
		$response = $this->send_request_through_router( $this->request );
		if ( ! $response ) {
			return;
		}

		$response->send();
		exit;
	}

	/**
	 * Bootstrap the console.
	 */
	public function bootstrap() {
		if ( ! $this->app->has_been_bootstrapped() ) {
			$this->app->bootstrap_with( $this->bootstrappers(), $this );
		}
	}

	/**
	 * Get the bootstrap classes for the application.
	 *
	 * @return array
	 */
	protected function bootstrappers(): array {
		return $this->bootstrappers;
	}

	/**
	 * Sync the current state of the middleware to the router.
	 *
	 * @return void
	 */
	protected function sync_middleware_to_router() {
		foreach ( $this->middleware_groups as $key => $middleware ) {
			$this->router->middleware_group( $key, $middleware );
		}

		foreach ( $this->route_middleware as $key => $middleware ) {
			$this->router->alias_middleware( $key, $middleware );
		}
	}

	/**
	 * Send the request through the router.
	 *
	 * @param Request $request Request object.
	 * @return Response|null
	 *
	 * @throws InvalidArgumentException Thrown on invalid router service provider instance.
	 */
	protected function send_request_through_router( Request $request ): ?Response {
		$provider = $this->app['router.service-provider'];

		if ( ! ( $provider instanceof Route_Service_Provider_Contract ) ) {
			throw new InvalidArgumentException( 'Unknown "router.service-provider" instance: ' . get_class( $provider ) );
		}

		try {
			$response = $this->router->dispatch( $request );
		} catch ( Throwable $e ) {
			// If no route found, allow the request to be passed down to WordPress.
			if ( $e instanceof ResourceNotFoundException && $provider->should_pass_through_requests( $request ) ) {
				return null;
			}

			$this->report_exception( $e );

			$response = $this->render_exception( $request, $e );
		}

		return $response;
	}

	/**
	 * Report the exception to the exception handler.
	 *
	 * @param Throwable $e Exception thrown.
	 * @return void
	 */
	protected function report_exception( Throwable $e ) {
		$this->app[ Exception_Handler::class ]->report( $e );
	}

	/**
	 * Render the exception to a response.
	 *
	 * @param Request   $request Request instance.
	 * @param Throwable $e
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function render_exception( $request, Throwable $e ) {
		return $this->app[ Exception_Handler::class ]->render( $request, $e );
	}
}
