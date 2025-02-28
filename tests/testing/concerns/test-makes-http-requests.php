<?php
namespace Mantle\Tests\Testing\Concerns;

use JsonSerializable;
use Mantle\Http\Response;
use Mantle\Framework\Providers\Routing_Service_Provider;
use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testing\Framework_Test_Case;
use Mantle\Testing\Test_Response;

/**
 * @group testing
 */
class Test_Makes_Http_Requests extends Framework_Test_Case {
	use Refresh_Database;

	public function test_get_home() {
		$this->get( home_url( '/' ) );
		$this->assertQueryTrue( 'is_home', 'is_front_page' );
	}

	public function test_get_singular() {
		$post_id = static::factory()->post->create();
		$this->get( get_permalink( $post_id ) )
			->assertQueryTrue( 'is_single', 'is_singular' )
			->assertQueriedObjectId( $post_id );
	}

	public function test_get_term() {
		$category_id = static::factory()->category->create();

		$this->get( get_term_link( $category_id, 'category' ) );
		$this->assertQueryTrue( 'is_archive', 'is_category' );
		$this->assertQueriedObjectId( $category_id );
	}

	public function test_wordpress_404() {
		$this
			->get( '/not-found/should-404/' )
			->assertNotFound();
	}

	/**
	 * Test checking against a Mantle route.
	 */
	public function test_get_mantle_route() {
		$_SERVER['__route_run'] = false;

		// Ensure routing is enabled.
		$this->assertNotNull( $this->app->get_provider( Routing_Service_Provider::class ) );

		// Register a route.
		$this->app['router']->get(
			'/test-route',
			function() {
				$_SERVER['__route_run'] = true;
				return 'yes';
			}
		);

		$this->get( '/test-route' )
			->assertOk()
			->assertContent( 'yes' );

		$this->assertTrue( $_SERVER['__route_run'] );
	}

	public function test_get_mantle_route_404() {
		// Ensure routing is enabled.
		$this->assertNotNull( $this->app->get_provider( Routing_Service_Provider::class ) );

		// Register a route.
		$this->app['router']->get(
			'/test-route-404',
			function() {
				return response()->make( 'not-found', 404 );
			}
		);

		$this->get( '/test-route-404' )
			->assertNotFound()
			->assertContent( 'not-found' );
	}

	public function test_post_mantle_route() {
		// Ensure routing is enabled.
		$this->assertNotNull( $this->app->get_provider( Routing_Service_Provider::class ) );

		// Register a route.
		$this->app['router']->post(
			'/test-post',
			function() {
				return new Response( 'yes', 201, [ 'test-header' => 'test-value' ] );
			}
		);

		$this->app['router']->get(
			'/404',
			function() {
				return new Response( 'yes', 404 );
			}
		);

		$this->post( '/test-post' )
			->assertCreated()
			->assertHeader( 'test-header', 'test-value' )
			->assertContent( 'yes' );

		$this->get( '/404' )->assertNotFound();
	}

	public function test_rest_api_route() {
		$post_id = static::factory()->post->create();

		$this->get( rest_url( "wp/v2/posts/{$post_id}" ) )
			->assertOk()
			->assertJsonPath( 'id', $post_id )
			->assertJsonPath( 'title.rendered', get_the_title( $post_id ) )
			->assertJsonPathExists( 'guid' )
			->assertJsonPathMissing( 'example_path' );
	}

	public function test_rest_api_route_error() {
		$this->get( rest_url( '/an/unknown/route' ) )
			->assertStatus( 404 )
			->assertNotFound();
	}

	public function test_redirect_response() {
		$this->app['router']->get(
			'/route-to-redirect/',
			fn () => redirect()->to( '/redirected/', 302, [ 'Other-Header' => '123' ] ),
		);

		$this->get( '/route-to-redirect/' )
			->assertHeader( 'location', home_url( '/redirected/' ) )
			->assertHeader( 'Location', home_url( '/redirected/' ) )
			->assertRedirect( '/redirected' )
			->assertHeader( 'Other-Header', '123' );
	}

	public function test_assert_json_structure() {
		$response = Test_Response::from_base_response(
			new Response( new JsonSerializableMixedResourcesStub() )
		);

		// Without structure
		$response->assertJsonStructure();

		// At root
		$response->assertJsonStructure( [ 'foo' ] );

		// Nested
		$response->assertJsonStructure( [ 'foobar' => [ 'foobar_foo', 'foobar_bar' ] ]);

		// Wildcard (repeating structure)
		$response->assertJsonStructure( [ 'bars' => [ '*' => [ 'bar', 'foo' ] ] ] );

		// Wildcard (numeric keys)
		$response->assertJsonStructure( [ 'numeric_keys' => [ '*' => ['bar', 'foo' ] ] ] );

		// Nested after wildcard
		$response->assertJsonStructure( [ 'baz' => [ '*' => [ 'foo', 'bar' => [ 'foo', 'bar' ] ] ] ] );
	}

	public function test_callbacks() {
		$_SERVER['__callback_before'] = false;
		$_SERVER['__callback_after']  = false;

		$this
			->before_request( fn () => $_SERVER['__callback_before'] = true )
			->after_request( fn ( $response ) => $_SERVER['__callback_after'] = $response )
			->get( '/' );

		$this->assertTrue( $_SERVER['__callback_before'] );
		$this->assertInstanceOf( Test_Response::class, $_SERVER['__callback_after'] );
	}

	public function test_multiple_requests() {
		// Re-run all test methods on this class in a single pass.
		foreach ( get_class_methods( $this ) as $method ) {
			if ( __FUNCTION__ === $method || 'test_' !== substr( $method, 0, 5 ) ) {
				continue;
			}

			$this->$method();
		}
	}
}

class JsonSerializableMixedResourcesStub implements JsonSerializable {
	public function jsonSerialize(): array {
		return [
			'foo'          => 'bar',
			'foobar'       => [
				'foobar_foo' => 'foo',
				'foobar_bar' => 'bar',
			],
			'0'            => [ 'foo' ],
			'bars'         => [
				[
					'bar' => 'foo 0',
					'foo' => 'bar 0',
				],
				[
					'bar' => 'foo 1',
					'foo' => 'bar 1',
				],
				[
					'bar' => 'foo 2',
					'foo' => 'bar 2',
				],
			],
			'baz'          => [
				[
					'foo' => 'bar 0',
					'bar' => [
						'foo' => 'bar 0',
						'bar' => 'foo 0',
					],
				],
				[
					'foo' => 'bar 1',
					'bar' => [
						'foo' => 'bar 1',
						'bar' => 'foo 1',
					],
				],
			],
			'barfoo'       => [
				[ 'bar' => [ 'bar' => 'foo 0' ] ],
				[
					'bar' => [
						'bar' => 'foo 0',
						'foo' => 'foo 0',
					],
				],
				[
					'bar' => [
						'foo' => 'bar 0',
						'bar' => 'foo 0',
						'rab' => 'rab 0',
					],
				],
			],
			'numeric_keys' => [
				2 => [
					'bar' => 'foo 0',
					'foo' => 'bar 0',
				],
				3 => [
					'bar' => 'foo 1',
					'foo' => 'bar 1',
				],
				4 => [
					'bar' => 'foo 2',
					'foo' => 'bar 2',
				],
			],
		];
	}
}
