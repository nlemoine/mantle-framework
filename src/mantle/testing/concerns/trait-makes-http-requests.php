<?php
/**
 * This file contains the Makes_Http_Requests trait
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use Mantle\Database\Model\Model;
use Mantle\Framework\Http\Kernel as HttpKernel;
use Mantle\Http\Request;
use Mantle\Support\Str;
use Mantle\Testing\Exceptions\Exception;
use Mantle\Testing\Exceptions\WP_Redirect_Exception;
use Mantle\Testing\Http\Pending_Request;
use Mantle\Testing\Test_Response;
use Mantle\Testing\Utils;
use WP;
use WP_Query;

/**
 * Trait for Test_Case classes which want to make http-like requests against
 * WordPress.
 */
trait Makes_Http_Requests {
	/**
	 * Additional headers for the request.
	 *
	 * @var array
	 */
	protected array $default_headers = [];

	/**
	 * Additional cookies for the request.
	 *
	 * @var array
	 */
	protected array $default_cookies = [];

	/**
	 * The array of callbacks to be run before the event is started.
	 *
	 * @var array
	 */
	protected $before_callbacks = [];

	/**
	 * The array of callbacks to be run after the event is finished.
	 *
	 * @var array
	 */
	protected $after_callbacks = [];

	/**
	 * Setup the trait in the test case.
	 */
	public function makes_http_requests_set_up() {
		global $wp_rest_server, $wp_actions;

		// Clear out the existing REST Server to allow for REST API routes to be re-registered.
		$wp_rest_server = null; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals

		// Mark 'rest_api_init' as an un-run action.
		unset( $wp_actions['rest_api_init'] );

		// Clear before/after callbacks.
		$this->before_callbacks = [];
		$this->after_callbacks  = [];
	}

	/**
	 * Create a new pending request.
	 *
	 * @return Pending_Request
	 */
	public function pending_request(): Pending_Request {
		return new Pending_Request( $this );
	}

	/**
	 * Set the default headers for the request.
	 *
	 * @param array<string, string> $headers Headers for the request.
	 * @return $this
	 */
	public function default_headers( array $headers ) {
		$this->default_headers = $headers;

		return $this;
	}

	/**
	 * Flush all the configured default headers.
	 *
	 * @return $this
	 */
	public function flush_headers() {
		return $this->default_headers( [] );
	}

	/**
	 * Set the default cookies for the request.
	 *
	 * @param array<string, string> $cookies Cookies for the request.
	 * @return $this
	 */
	public function default_cookies( array $cookies ) {
		$this->default_cookies = $cookies;

		return $this;
	}

	/**
	 * Flush the default cookies for the request.
	 *
	 * @return static
	 */
	public function flush_default_cookies() {
		$this->default_cookies = [];

		return $this;
	}

	/**
	 * Define additional headers to be sent with the request.
	 *
	 * @param array<string, mixed> $headers Headers for the request.
	 * @return Pending_Request
	 */
	public function with_headers( array $headers ): Pending_Request {
		return $this->pending_request()->with_headers( array_merge(
			$this->default_headers,
			$headers,
		) );
	}

	/**
	 * Make a request with a set of cookies.
	 *
	 * @param array $cookies Cookies to be sent with the request.
	 * @return Pending_Request
	 */
	public function with_cookies( array $cookies ): Pending_Request {
		return $this->pending_request()->with_cookies( array_merge(
			$this->default_cookies,
			$cookies,
		) );
	}

	/**
	 * Make a request with a specific cookie.
	 *
	 * @param string $name  Cookie name.
	 * @param string $value Cookie value.
	 * @return Pending_Request
	 */
	public function with_cookie( string $name, string $value ): Pending_Request {
		return $this->with_cookies( [ $name => $value ] );
	}

	/**
	 * Automatically follow any redirects returned from the response.
	 *
	 * @return Pending_Request
	 */
	public function following_redirects(): Pending_Request {
		return $this->pending_request()->following_redirects();
	}

	/**
	 * Set the referer header and previous URL session value in order to simulate
	 * a previous request.
	 *
	 * @param string $url URL for the referer header.
	 * @return $this
	 */
	public function from( string $url ) {
		return $this->with_header( 'referer', $url );
	}

	/**
	 * Add a header to be sent with the request.
	 *
	 * @param string $name  Header name (key).
	 * @param string $value Header value.
	 * @return $this
	 */
	public function with_header( string $name, string $value ) {
		$this->default_headers[ $name ] = $value;

		return $this;
	}

	/**
	 * Call a given Closure/method before requests and inject its dependencies.
	 *
	 * @param callable|string $callback Callback to invoke.
	 * @return static
	 */
	public function before_request( $callback ) {
		$this->before_callbacks[] = $callback;

		return $this;
	}

	/**
	 * Call a given Closure/method after requests and inject its dependencies.
	 *
	 * Callback will be invoked with a 'response' argument.
	 *
	 * @param callable|string $callback Callback to invoke.
	 * @return static
	 */
	public function after_request( $callback ) {
		$this->after_callbacks[] = $callback;

		return $this;
	}

	/**
	 * Call all of the "before" callbacks for the requests.
	 */
	public function call_before_callbacks() {
		foreach ( $this->before_callbacks as $callback ) {
			$this->app->call( $callback );
		}
	}

	/**
	 * Call all of the "after" callbacks for the request.
	 *
	 * @param Test_Response $response Response object.
	 */
	public function call_after_callbacks( Test_Response $response ) {
		foreach ( $this->after_callbacks as $callback ) {
			$this->app->call(
				$callback,
				[
					'response' => $response,
				]
			);
		}
	}
}
