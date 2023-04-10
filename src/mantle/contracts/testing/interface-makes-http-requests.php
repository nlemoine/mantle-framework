<?php

namespace Mantle\Contracts\Testing;

use Mantle\Testing\Test_Response;

interface Makes_Http_Requests {
	/**
	 * Visit the given URI with a GET request.
	 *
	 * @param mixed $uri     Request URI.
	 * @param array $headers Request headers.
	 * @return Test_Response
	 */
	public function get( $uri, array $headers = [] );

	/**
	 * Visit the given URI with a PUT request.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function put( $uri, array $data = [], array $headers = [] );

	/**
	 * Visit the given URI with a PUT request, expecting a JSON response.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function put_json( $uri, array $data = [], array $headers = [] );

	/**
	 * Visit the given URI with a PATCH request.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function patch( $uri, array $data = [], array $headers = [] );

	/**
	 * Visit the given URI with a PATCH request, expecting a JSON response.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function patch_json( $uri, array $data = [], array $headers = [] );

	/**
	 * Visit the given URI with a DELETE request.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function delete( $uri, array $data = [], array $headers = [] );

	/**
	 * Visit the given URI with a DELETE request, expecting a JSON response.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function delete_json( $uri, array $data = [], array $headers = [] );

	/**
	 * Visit the given URI with a OPTIONS request.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function options( $uri, array $data = [], array $headers = [] );

	/**
	 * Visit the given URI with a OPTIONS request, expecting a JSON response.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function options_json( $uri, array $data = [], array $headers = [] );

	/**
	 * Call all of the "before" callbacks for the requests.
	 */
	public function call_before_callbacks();

	/**
	 * Call all of the "after" callbacks for the request.
	 *
	 * @param Test_Response $response Response object.
	 */
	public function call_after_callbacks( Test_Response $response );
}
