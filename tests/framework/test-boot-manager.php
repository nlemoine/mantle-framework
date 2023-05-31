<?php

namespace Mantle\Tests\Framework;

use Mantle\Framework\Boot_Manager;
use Mantle\Testing\Concerns\Interacts_With_Hooks;
use PHPUnit\Framework\TestCase;

class Test_Boot_Manager extends TestCase {
	use Interacts_With_Hooks;

	public function setUp(): void {
		parent::setUp();

		$this->interacts_with_hooks_set_up();
	}

	public function tearDown(): void {
		$this->interacts_with_hooks_tear_down();

		parent::tearDown();
	}

	public function test_it_can_create_an_instance() {
		$this->assertInstanceOf( Boot_Manager::class, Boot_Manager::get_instance() );
	}

	public function test_it_will_set_instance_on_construct() {
		$manager = new Boot_Manager();

		$this->assertSame( $manager, Boot_Manager::get_instance() );
	}

	public function test_it_can_boot_application() {
		$this->expectApplied( 'mantle_boot_manager_before_boot' )->once();
		$this->expectApplied( 'mantle_boot_manager_booted' )->once();

		$manager = new Boot_Manager();

		$manager->boot();

		$app = $manager->application();

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
