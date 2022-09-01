<?php
namespace Mantle\Tests\Queue;

use Mantle\Queue\Wp_Cron_Provider;
use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testing\Framework_Test_Case;

use function Mantle\Queue\dispatch;

class Test_Closure_Jobs extends Framework_Test_Case {
	use Refresh_Database;

	protected function setUp(): void {
		parent::setUp();

		$this->app['queue']->add_provider( 'wordpress', Wp_Cron_Provider::class );
	}

	// public function test_closure_job() {
	// 	$job = fn () => $_SERVER['__closure_job'] = true;

	// 	// $this->app['queue']-
	// 	// dd($manager);

	// 	// $this->app->make( \Mantle\Contracts\Queue\Provider::class );

	// 	dispatch ( $job );


	// 	$this->app['queue.worker']->run( 1 );


	// 	$this->assertTrue( $_SERVER['__closure_job'] );
	// }
}
