<?php

namespace Mantle\Tests\Framework;

use Mantle\Application\Application;
use Mantle\Framework\Boot_Manager;
use Mantle\Http\Request;
use Mantle\Testing\Concerns\Interacts_With_Hooks;
use PHPUnit\Framework\TestCase;

class Test_Boot_Manager extends TestCase {
	use Interacts_With_Hooks;

	public function setUp(): void {
		parent::setUp();

		Boot_Manager::set_instance( null );

		$this->interacts_with_hooks_set_up();
	}

	public function tearDown(): void {
		$this->interacts_with_hooks_tear_down();

		Boot_Manager::set_instance( null );

		parent::tearDown();
	}

	public function test_it_can_create_an_instance() {
		$this->assertInstanceOf( Boot_Manager::class, Boot_Manager::get_instance() );
		$this->assertNotNUll( Boot_Manager::get_instance()->get_base_path() );
	}

	public function test_it_can_be_used_by_helper() {
		$this->assertInstanceOf( Boot_Manager::class, boot_manager() );
	}

	public function test_it_will_set_instance_on_construct() {
		$manager = new Boot_Manager();

		$this->assertSame( $manager, Boot_Manager::get_instance() );
	}

	public function test_it_can_bind_custom_kernel() {
		Boot_Manager::instance( $app = new Application() )
			->bind( \Mantle\Contracts\Http\Kernel::class, Testable_Http_Kernel::class )
			->boot();

		// Ensure the custom kernel was bound.
		$this->assertInstanceOf(
			Testable_Http_Kernel::class,
			$app->make( \Mantle\Contracts\Http\Kernel::class ),
		);

		// Ensure the standard console kernel was still bound.
		$this->assertInstanceOf(
			\Mantle\Framework\Console\Kernel::class,
			$app->make( \Mantle\Contracts\Console\Kernel::class ),
		);
	}

	public function test_it_can_boot_application() {
		$this->expectApplied( 'mantle_boot_manager_before_boot' )->once();
		$this->expectApplied( 'mantle_boot_manager_booted' )->once();

		$manager = new Boot_Manager();

		$manager->boot();

		$app = $manager->get_application();

		$this->assertNotEmpty(
			$app->make( \Mantle\Contracts\Console\Kernel::class ),
		);

		$this->assertNotEmpty(
			$app->make( \Mantle\Contracts\Http\Kernel::class ),
		);

		$this->assertNotEmpty(
			$app->make( \Mantle\Contracts\Exceptions\Handler::class ),
		);
	}
}

class Testable_Http_Kernel implements \Mantle\Contracts\Http\Kernel {
	/**
	 * Run the HTTP Application.
	 *
	 * @param Request $request Request object.
	 */
	public function handle( Request $request ) {
		$_SERVER['__testable_http_kernel__'] = $request;
	}

	/**
	 * Terminate the HTTP request.
	 *
	 * @param Request  $request  Request object.
	 * @param mixed    $response Response object.
	 * @return void
	 */
	public function terminate( Request $request, mixed $response ): void {
		$_SERVER['__testable_http_kernel_terminate__'] = $request;
	}
}
