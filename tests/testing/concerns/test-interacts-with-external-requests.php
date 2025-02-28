<?php
namespace Mantle\Tests\Testing\Concerns;

use InvalidArgumentException;
use Mantle\Facade\Http;
use Mantle\Http_Client\Factory;
use Mantle\Http_Client\Pending_Request;
use Mantle\Testing\Mock_Http_Response;
use Mantle\Testing\Framework_Test_Case;
use Mantle\Testing\Mock_Http_Sequence;
use RuntimeException;

/**
 * Test for Mocking WP HTTP API Requests.
 *
 * @group testing
 */
class Test_Interacts_With_External_Requests extends Framework_Test_Case {
	public function test_fake_request() {
		$this->fake_request( 'https://testing.com/*' )
			->with_response_code( 404 )
			->with_body( 'test body' );

		$this->fake_request( 'https://github.com/*' )
			->with_response_code( 500 )
			->with_body( 'fake body' );

		$response = wp_remote_get( 'https://testing.com/' );
		$this->assertEquals( 'test body', wp_remote_retrieve_body( $response ) );
		$this->assertEquals( 404, wp_remote_retrieve_response_code( $response ) );

		$response = wp_remote_get( 'https://github.com/' );
		$this->assertEquals( 'fake body', wp_remote_retrieve_body( $response ) );
		$this->assertEquals( 500, wp_remote_retrieve_response_code( $response ) );
	}

	public function test_fake_all_requests() {
		$this->fake_request()
			->with_response_code( 206 )
			->with_body( 'another fake body' );

		$response = wp_remote_get( 'https://github.com/' );
		$this->assertEquals( 'another fake body', wp_remote_retrieve_body( $response ) );
		$this->assertEquals( 206, wp_remote_retrieve_response_code( $response ) );
	}

	public function test_fake_callback() {
		$this->fake_request(
			function() {
				return Mock_Http_Response::create()
					->with_response_code( 123 )
					->with_body( 'apples' );
			}
		);

		$response = wp_remote_get( 'https://alley.co/' );
		$this->assertEquals( 'apples', wp_remote_retrieve_body( $response ) );
		$this->assertEquals( 123, wp_remote_retrieve_response_code( $response ) );
	}

	public function test_fake_array_of_urls() {
		$this->fake_request(
			[
				'https://github.com/*'  => Mock_Http_Response::create()->with_body( 'github' ),
				'https://twitter.com/*' => Mock_Http_Response::create()->with_body( 'twitter' ),
			]
		);

		$response = wp_remote_get( 'https://github.com/' );
		$this->assertEquals( 'github', wp_remote_retrieve_body( $response ) );

		$response = wp_remote_get( 'https://twitter.com/' );
		$this->assertEquals( 'twitter', wp_remote_retrieve_body( $response ) );
	}

	public function test_fake_json_response() {
		$this->fake_request()->with_json( [ 1, 2, 3 ] );

		$response = wp_remote_get( 'https://github.com/' );
		$this->assertEquals( [ 1, 2, 3 ], json_decode( wp_remote_retrieve_body( $response ) ) );
	}

	public function test_permanent_redirect_response() {
		$this->fake_request()->with_redirect( 'https://wordpress.org/', 308 );

		$response = wp_remote_get( 'https://drupal.org/' );
		$this->assertEquals(
			'https://wordpress.org/',
			wp_remote_retrieve_header( $response, 'Location' )
		);

		$this->assertEquals( 308, wp_remote_retrieve_response_code( $response ) );
	}

	public function test_redirect_response() {
		$this->fake_request()->with_redirect( 'https://wordpress.org/' );

		$response = wp_remote_get( 'https://drupal.org/' );
		$this->assertEquals(
			'https://wordpress.org/',
			wp_remote_retrieve_header( $response, 'Location' )
		);

		$this->assertEquals( 301, wp_remote_retrieve_response_code( $response ) );
	}

	public function test_redirect_response_temporary() {
		$this->fake_request()->with_temporary_redirect( 'https://wordpress.org/' );

		$response = wp_remote_get( 'https://drupal.org/' );
		$this->assertEquals(
			'https://wordpress.org/',
			wp_remote_retrieve_header( $response, 'Location' )
		);

		$this->assertEquals( 302, wp_remote_retrieve_response_code( $response ) );
	}

	public function test_error_response() {
		$this->fake_request(
			function() {
				return new \WP_Error( 'http-error', 'Error!' );
			}
		);

		$response = wp_remote_get( 'https://alley.co/' );
		$this->assertWPError( $response );

		$this->assertRequestSent( 'https://alley.co/', 1 );
		$this->assertRequestNotSent( 'https://anothersite.com/' );
	}

	public function test_sequence() {
		$this->fake_request(
			Mock_Http_Sequence::create()
				->push_status( 200 )
				->push_status( 400 )
				->push_status( 500 )
		);

		$http = new Factory();

		$this->assertEquals( 200, $http->get( 'https://example.com/sequence/' )->status() );
		$this->assertEquals( 400, $http->get( 'https://example.com/sequence/' )->status() );
		$this->assertEquals( 500, $http->get( 'https://example.com/sequence/' )->status() );
	}

	public function test_sequence_array() {
		$this->fake_request(
			[
				'github.com/*' => Mock_Http_Sequence::create()
					->push_status( 200 )
					->push_status( 400 )
					->push_status( 500 ),
				'alley.co/*' => Mock_Http_Sequence::create()
					->push_status( 200 )
					->push_status( 403 ),
			]
		);

		$http = new Pending_Request();

		$this->assertEquals( 200, $http->get( 'https://github.com/request/' )->status() );
		$this->assertEquals( 400, $http->get( 'https://github.com/request/' )->status() );

		$this->assertEquals( 200, $http->get( 'https://alley.co/test/' )->status() );
		$this->assertEquals( 403, $http->get( 'https://alley.co/test/' )->status() );
	}

	public function test_sequence_exception_empty() {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'No more responses in sequence.' );

		$this->fake_request(
			Mock_Http_Sequence::create()
				->push_status( 200 )
				->push_status( 400 )
		);

		$http = new Factory();

		$this->assertEquals( 200, $http->get( 'https://example.com/sequence/' )->status() );
		$this->assertEquals( 400, $http->get( 'https://example.com/sequence/' )->status() );
		$this->assertEquals( 500, $http->get( 'https://example.com/sequence/' )->status() );
	}

	public function test_sequence_fallback_empty() {
		$this->fake_request(
			Mock_Http_Sequence::create()
				->push_status( 200 )
				->push_status( 400 )
				->when_empty( Mock_Http_Response::create()->with_status( 202 ) )
		);

		$http = new Factory();

		$this->assertEquals( 200, $http->get( 'https://example.com/sequence/' )->status() );
		$this->assertEquals( 400, $http->get( 'https://example.com/sequence/' )->status() );

		// These two should use the fallback response.
		$this->assertEquals( 202, $http->get( 'https://example.com/sequence/' )->status() );
		$this->assertEquals( 202, $http->get( 'https://example.com/sequence/' )->status() );
	}

	public function test_prevent_stray_requests() {
		$this->prevent_stray_requests(
			Mock_Http_Response::create()->with_status( 201 ),
		);

		$this->assertEquals( 201, Http::get( 'https://example.com/' )->status() );
		$this->assertRequestSent();
	}

	public function test_prevent_stray_requests_callback() {
		$this->prevent_stray_requests(
			fn () => Mock_Http_Response::create()->with_status( 400 ),
		);

		$this->assertEquals( 400, Http::get( 'https://example.com/' )->status() );
		$this->assertRequestSent();
	}

	public function test_prevent_stray_requests_no_fallback() {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Attempted request to [https://example.org/path/] without a matching fake.' );

		$this->prevent_stray_requests();

		Http::get( 'https://example.org/path/' );
	}

	public function test_file_as_response() {
		$this->fake_request(
			fn() => Mock_Http_Response::create()->with_file( MANTLE_PHPUNIT_FIXTURES_PATH . '/images/alley.jpg' )
		);

		$filename = sys_get_temp_dir() . '/' . time() . '-alley.jpg';

		$response = Http::stream( $filename )->get( 'https://alley.com/wp-content/uploads/2021/12/NSF_Cover.png?w=960' );

		$this->assertEquals( 200, $response->status() );
		$this->assertNotEmpty( file_get_contents( $filename ) );
		$this->assertEquals( 'image/jpeg', $response->header( 'Content-Type' ) );
	}

	public function test_streamed_response() {
		$this->fake_request(
			fn() => Mock_Http_Response::create()->with_file( MANTLE_PHPUNIT_FIXTURES_PATH . '/images/alley.jpg' )
		);

		$response = Http::stream()->get( 'https://alley.com/wp-content/uploads/2021/12/NSF_Cover.png?w=960' );

		$this->assertEquals( 200, $response->status() );
		$this->assertNotEmpty( $response->file_contents() );
		$this->assertEquals( 'image/jpeg', $response->header( 'Content-Type' ) );
	}

	public function test_unknown_file_as_response() {
		$this->expectException( InvalidArgumentException::class );

		$this->fake_request(
			Mock_Http_Response::create()->with_file( 'unknown' )
		);
	}
}
