<?php
namespace Mantle\Tests\Queue\Providers;

use Mantle\Testing\Framework_Test_Case;
use Mantle\Contracts\Queue\Can_Queue;
use Mantle\Contracts\Queue\Job;
use Mantle\Queue\Dispatchable;
use Mantle\Queue\Events\Job_Failed;
use Mantle\Queue\Queueable;
use Mantle\Queue\Providers\WordPress\Provider;
use Mantle\Queue\Providers\WordPress\Scheduler;
use Mantle\Testing\Concerns\Refresh_Database;
use RuntimeException;

/**
 * WordPress Cron Queue Provider Test
 *
 * @group queue
 * @group wordpress-queue
 */
class Test_WordPress_Cron_Queue extends Framework_Test_Case {
	use Refresh_Database;

	protected function setUp(): void {
		parent::setUp();

		// Provider::on_init();
	}

	public function test_wordpress_queue_job_from_class_async() {
		$_SERVER['__example_job'] = false;

		Example_Job::dispatch();

		$this->assertInCronQueue( Example_Job::class );
		$this->assertFalse( $_SERVER['__example_job'] );

		// Force the cron to be dispatched which will execute
		// the queued job.
		$this->dispatch_queue();

		$this->assertTrue( $_SERVER['__example_job'] );
	}

	public function test_wordpress_queue_job_from_class_sync() {
		$this->assertNotInCronQueue( Example_Job::class );

		$_SERVER['__example_job'] = false;

		Example_Job::dispatch_now();

		$this->assertTrue( $_SERVER['__example_job'] );
		$this->assertNotInCronQueue( Example_Job::class );
	}

	public function test_wordpress_queue_job_async_failure() {
		$_SERVER['__failed_run'] = false;

		$this->app['events']->listen( Job_Failed::class, fn () => $_SERVER['__failed_run'] = true );

		$this->assertNotInCronQueue( Failable_Job::class );

		Failable_Job::dispatch();

		$this->assertInCronQueue( Failable_Job::class );

		$this->dispatch_queue();

		$this->assertNotInCronQueue( Failable_Job::class );
		$this->assertTrue( $_SERVER['__failed_run'] );
	}

	// public function test_wordpress_queue_job_from_closure_async() {}
	// public function test_wordpress_queue_job_from_closure_sync() {}

	public function test_schedule_next_run_after_complete() {
		// Limit the queue batch size.
		$this->app['config']->set( 'queue.batch_size', 5 );

		for ( $i = 0; $i < 10; $i++ ) {
			Example_Job::dispatch();
		}

		$this->dispatch_queue();

		$this->assertInCronQueue( Scheduler::EVENT, [ 'default' ] );
	}
}

class Example_Job implements Job, Can_Queue {
	use Queueable, Dispatchable;

	public function handle() {
		$_SERVER['__example_job'] = true;
	}
}

class Failable_Job implements Job, Can_Queue {
	use Queueable, Dispatchable;

	public function handle() {
		throw new RuntimeException( 'Something went wrong' );
	}
}
